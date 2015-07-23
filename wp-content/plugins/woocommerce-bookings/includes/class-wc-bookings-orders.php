<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Handles order status transitions and keeps bookings in sync
 */
class WC_Bookings_Orders {

	/**
	 * Constructor sets up actions
	 */
	public function __construct() {
		// Displaying user bookings on the frontend
		add_action( 'woocommerce_before_my_account', array( $this, 'my_bookings' ) );

		// Complete booking orders if virtual
		add_action( 'woocommerce_payment_complete_order_status', array( $this, 'complete_order' ), 10, 2 );

		// When an order is processed, on-hold or completed, we can mark publish the pending bookings
		add_action( 'woocommerce_order_status_processing', array( $this, 'publish_bookings' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'publish_bookings' ), 10, 1 );

		// When an order is cancelled, cancel the bookings
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_bookings' ), 10, 1 );

		// Status transitions
		add_action( 'before_delete_post', array( $this, 'delete_post' ) );
		add_action( 'wp_trash_post', array( $this, 'trash_post' ) );
		add_action( 'untrash_post', array( $this, 'untrash_post' ) );

		// Prevent pending being cancelled
		add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'prevent_cancel' ) );

		// Control the my orders actions.
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'my_orders_actions' ), 10, 2 );

		// Sync order user with booking user
		add_action( "updated_post_meta", array( $this, 'updated_post_meta' ), 10, 4 );
	}

	/**
	 * Show a users bookings
	 */
	public function my_bookings() {
		$bookings = WC_Bookings_Controller::get_bookings_for_user( get_current_user_id() );

		if ( $bookings ) {
			woocommerce_get_template( 'myaccount/my-bookings.php', array( 'bookings' => $bookings ), 'woocommerce-bookings/', WC_BOOKINGS_TEMPLATE_PATH );
		}
	}

	/**
	 * Called when an order is paid
	 * @param  int $order_id
	 */
	public function publish_bookings( $order_id ) {
		global $wpdb;

		$order    = new WC_Order( $order_id );
		$bookings = array();

		foreach ( $order->get_items() as $order_item_id => $item ) {
			if ( 'line_item' == $item['type'] ) {
				$bookings = array_merge( $bookings, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_booking_order_item_id' AND meta_value = %d", $order_item_id ) ) );
			}
		}

		foreach ( $bookings as $booking_id ) {
			$booking = get_wc_booking( $booking_id );
			$booking->paid();
		}
	}

	/**
	 * Complete virtual booking orders
	 */
	public function complete_order( $order_status, $order_id ) {
		$order = new WC_Order( $order_id );

		if ( 'processing' == $order_status && ( 'on-hold' == $order->status || 'pending' == $order->status || 'failed' == $order->status ) ) {

			$virtual_booking_order = null;

			if ( count( $order->get_items() ) > 0 ) {

				foreach( $order->get_items() as $item ) {

					if ( 'line_item' == $item['type'] ) {

						$_product = $order->get_product_from_item( $item );

						if ( ! $_product->is_virtual() || ! $_product->is_type( 'booking' ) ) {
							// once we've found one non-virtual product we know we're done, break out of the loop
							$virtual_booking_order = false;
							break;
						} else {
							$virtual_booking_order = true;
						}
					}
				}
			}

			// virtual order, mark as completed
			if ( $virtual_booking_order ) {
				return 'completed';
			}
		}

		// non-virtual order, return original status
		return $order_status;
	}

	/**
	 * Cancel bookings with order
	 * @param  int $order_id
	 */
	public function cancel_bookings( $order_id ) {
		global $wpdb;

		$order    = new WC_Order( $order_id );
		$bookings = array();

		// Prevents infinite loop during synchronization
		update_post_meta( $order_id, '_booking_status_sync', true );

		foreach ( $order->get_items() as $order_item_id => $item ) {
			if ( 'line_item' == $item['type'] ) {
				$bookings = array_merge( $bookings, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_booking_order_item_id' AND meta_value = %d", $order_item_id ) ) );
			}
		}

		foreach ( $bookings as $booking_id ) {
			if ( get_post_meta( $booking_id, '_booking_status_sync', true ) ) {
				continue;
			}

			$booking = get_wc_booking( $booking_id );
			$booking->update_status( 'cancelled' );
		}

		delete_post_meta( $order_id, '_booking_status_sync' );
	}

	/**
	 * Removes bookings related to the order being deleted.
	 *
	 * @param mixed $order_id ID of post being deleted
	 */
	public function delete_post( $order_id ) {
		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		if ( $order_id > 0 && 'shop_order' == get_post_type( $order_id ) ) {
			global $wpdb;

			$order    = new WC_Order( $order_id );
			$bookings = array();

			// Prevents infinite loop during synchronization
			update_post_meta( $order_id, '_booking_delete_sync', true );

			foreach ( $order->get_items() as $order_item_id => $item ) {
				if ( 'line_item' == $item['type'] ) {
					$bookings = array_merge( $bookings, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_booking_order_item_id' AND meta_value = %d", $order_item_id ) ) );
				}
			}

			foreach ( $bookings as $booking_id ) {
				if ( get_post_meta( $booking_id, '_booking_delete_sync', true ) ) {
					continue;
				}

				wp_delete_post( $booking_id, true );
			}

			delete_post_meta( $order_id, '_booking_delete_sync' );
		}
	}

	/**
	 * Trash bookings with orders
	 *
	 * @param mixed $order_id
	 */
	public function trash_post( $order_id ) {
		if ( $order_id > 0 && 'shop_order' == get_post_type( $order_id ) ) {
			global $wpdb;

			$order    = new WC_Order( $order_id );
			$bookings = array();

			// Prevents infinite loop during synchronization
			update_post_meta( $order_id, '_booking_trash_sync', true );

			foreach ( $order->get_items() as $order_item_id => $item ) {
				if ( 'line_item' == $item['type'] ) {
					$bookings = array_merge( $bookings, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_booking_order_item_id' AND meta_value = %d", $order_item_id ) ) );
				}
			}

			foreach ( $bookings as $booking_id ) {
				if ( get_post_meta( $booking_id, '_booking_trash_sync', true ) ) {
					continue;
				}

				wp_trash_post( $booking_id );
			}

			delete_post_meta( $order_id, '_booking_trash_sync' );
		}
	}

	/**
	 * Untrash bookings with orders
	 *
	 * @param mixed $order_id
	 */
	public function untrash_post( $order_id ) {
		if ( $order_id > 0 && 'shop_order' == get_post_type( $order_id ) ) {
			global $wpdb;

			$order    = new WC_Order( $order_id );
			$bookings = array();

			// Prevents infinite loop during synchronization
			update_post_meta( $order_id, '_booking_untrash_sync', true );

			foreach ( $order->get_items() as $order_item_id => $item ) {
				if ( 'line_item' == $item['type'] ) {
					$bookings = array_merge( $bookings, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_booking_order_item_id' AND meta_value = %d", $order_item_id ) ) );
				}
			}

			foreach ( $bookings as $booking_id ) {
				if ( get_post_meta( $booking_id, '_booking_untrash_sync', true ) ) {
					continue;
				}

				wp_untrash_post( $booking_id );
			}

			delete_post_meta( $order_id, '_booking_untrash_sync' );
		}
	}

	/**
	 * Stops WC cancelling unpaid bookings orders
	 * @param  bool $return
	 * @param  object $order
	 * @return bool
	 */
	public function prevent_cancel( $return, $order ) {
		if ( '1' === get_post_meta( $order->id, '_booking_order', true ) ) {
			return false;
		}

		return $return;
	}

	/**
	 * My Orders custom actions.
	 * Remove the pay button when the booking requires confirmation.
	 *
	 * @param  array $actions
	 * @param  WC_Order $order
	 * @return array
	 */
	public function my_orders_actions( $actions, $order ) {
		global $wpdb;

		if ( 'pending' == $order->status && 'wc-booking-gateway' == $order->payment_method ) {
			$status = array();

			foreach ( $order->get_items() as $order_item_id => $item ) {
				if ( 'line_item' == $item['type'] ) {
					$_status = $wpdb->get_col( $wpdb->prepare( "
						SELECT posts.post_status
						FROM {$wpdb->postmeta} AS postmeta
							LEFT JOIN {$wpdb->posts} AS posts ON (postmeta.post_id = posts.ID)
						WHERE postmeta.meta_key = '_booking_order_item_id'
						AND postmeta.meta_value = %d
					", $order_item_id ) );

					$status = array_merge( $status, $_status );
				}
			}

			if ( in_array( 'pending', $status ) && isset( $actions['pay'] ) ) {
				unset( $actions['pay'] );
			}
		}

		return $actions;
	}

	/**
	 * Sync customer between order + booking
	 */
	public function updated_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
		if ( '_customer_user' === $meta_key && 'shop_order' === get_post_type( $object_id ) ) {
			global $wpdb;

			$order    = new WC_Order( $object_id );
			$bookings = array();

			foreach ( $order->get_items() as $order_item_id => $item ) {
				if ( 'line_item' == $item['type'] ) {
					$bookings = array_merge( $bookings, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_booking_order_item_id' AND meta_value = %d", $order_item_id ) ) );
				}
			}

			foreach ( $bookings as $booking_id ) {
				update_post_meta( $booking_id, '_booking_customer_id', $_meta_value );
			}
		}
	}
}

new WC_Bookings_Orders();