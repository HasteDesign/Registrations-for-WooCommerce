<?php
/**
 * Create new bookings page
 */
class WC_Bookings_Create {

	private $errors = array();

	/**
	 * Output the form
	 */
	public function output() {
		global $woocommerce;

		$this->errors = array();
		$step         = 1;

		try {

			if ( ! empty( $_POST ) && ! check_admin_referer( 'create_booking_notification' ) ) {
				throw new Exception( __( 'Error - please try again', 'woocommerce-bookings' ) );
			}

			if ( ! empty( $_POST['create_booking'] ) ) {

				$customer_id         = absint( $_POST['customer_id'] );
				$bookable_product_id = absint( $_POST['bookable_product_id'] );
				$booking_order       = wc_clean( $_POST['booking_order'] );

				if ( ! $bookable_product_id ) {
					throw new Exception( __( 'Please choose a bookable product', 'woocommerce-bookings' ) );
				}

				if ( $booking_order === 'existing' ) {
					$order_id      = absint( $_POST['booking_order_id'] );
					$booking_order = $order_id;

					if ( ! $booking_order || get_post_type( $booking_order ) !== 'shop_order' ) {
						throw new Exception( __( 'Invalid order ID provided', 'woocommerce-bookings' ) );
					}
				}

				$step++;
				$product      = get_product( $bookable_product_id );
				$booking_form = new WC_Booking_Form( $product );

			} elseif ( ! empty( $_POST['create_booking_2'] ) ) {

				$customer_id         = absint( $_POST['customer_id'] );
				$bookable_product_id = absint( $_POST['bookable_product_id'] );
				$booking_order       = wc_clean( $_POST['booking_order'] );
				$product             = get_product( $bookable_product_id );
				$booking_form        = new WC_Booking_Form( $product );
				$booking_data        = $booking_form->get_posted_data( $_POST );
				$booking_cost        = ( $cost = $booking_form->calculate_booking_cost( $_POST ) ) && ! is_wp_error( $cost ) ? number_format( $cost, 2, '.', '' ) : 0;
				$create_order        = false;

				// Data to go into the booking
				$new_booking_data = array(
					'user_id'     => $customer_id,
					'product_id'  => $product->id,
					'resource_id' => isset( $booking_data['_resource_id'] ) ? $booking_data['_resource_id'] : '',
					'persons'     => $booking_data['_persons'],
					'cost'        => $booking_cost,
					'start_date'  => $booking_data['_start_date'],
					'end_date'    => $booking_data['_end_date'],
					'all_day'     => $booking_data['_all_day'] ? 1 : 0
				);

				// Create order
				if ( $booking_order === 'new' ) {
					$create_order = true;
					$order_id     = $this->create_order( $booking_cost, $customer_id );

					if ( ! $order_id ) {
						throw new Exception( __( 'Error: Could not create order', 'woocommerce-bookings' ) );
					}
				} elseif ( $booking_order > 0 ) {
					$order_id = absint( $booking_order );

					if ( ! $order_id || get_post_type( $order_id ) !== 'shop_order' ) {
						throw new Exception( __( 'Invalid order ID provided', 'woocommerce-bookings' ) );
					}

					$order = new WC_Order( $order_id );

					update_post_meta( $order_id, '_order_total', $order->get_total() + $booking_cost );
					update_post_meta( $order_id, '_booking_order', '1' );
				} else {
					$order_id = 0;
				}

				if ( $order_id ) {
		           	$item_id  = woocommerce_add_order_item( $order_id, array(
				 		'order_item_name' 		=> $product->get_title(),
				 		'order_item_type' 		=> 'line_item'
				 	) );

				 	if ( ! $item_id ) {
						throw new Exception( __( 'Error: Could not create item', 'woocommerce-bookings' ) );
				 	}

				 	// Add line item meta
				 	woocommerce_add_order_item_meta( $item_id, '_qty', 1 );
				 	woocommerce_add_order_item_meta( $item_id, '_tax_class', $product->get_tax_class() );
				 	woocommerce_add_order_item_meta( $item_id, '_product_id', $product->id );
				 	woocommerce_add_order_item_meta( $item_id, '_variation_id', '' );
				 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal', $booking_cost );
				 	woocommerce_add_order_item_meta( $item_id, '_line_total', $booking_cost );
				 	woocommerce_add_order_item_meta( $item_id, '_line_tax', 0 );
				 	woocommerce_add_order_item_meta( $item_id, '_line_subtotal_tax', 0 );

				 	// We have an item id
					$new_booking_data['order_item_id'] = $item_id;

					// Add line item data
					foreach ( $booking_data as $key => $value ) {
						if ( strpos( $key, '_' ) !== 0 ) {
							woocommerce_add_order_item_meta( $item_id, get_wc_booking_data_label( $key, $product ), $value );
						}
					}
				}

				// Create the booking itself
				$new_booking = get_wc_booking( $new_booking_data );
				$new_booking ->create( $create_order ? 'unpaid' : 'pending' );

				wp_safe_redirect( admin_url( 'post.php?post=' . ( $create_order ? $order_id : $new_booking->id ) . '&action=edit' ) );
				exit;

			}
		} catch ( Exception $e ) {
			$this->errors[] = $e->getMessage();
		}

		switch ( $step ) {
			case 1 :
				include( 'views/html-create-booking-page.php' );
			break;
			case 2 :
				include( 'views/html-create-booking-page-2.php' );
			break;
		}
	}

	/**
	 * Create order
	 * @param  float $total
	 * @param  int $customer_id
	 * @return int
	 */
	public function create_order( $total, $customer_id ) {
		if ( function_exists( 'wc_create_order' ) ) {
			$order = wc_create_order( array(
				'customer_id' => absint( $customer_id )
			) );
			$order_id = $order->id;
			$order->set_total( $total );
			update_post_meta( $order->id, '_booking_order', '1' );
		} else {
			$order_data = apply_filters( 'woocommerce_new_order_data', array(
				'post_type' 	=> 'shop_order',
				'post_title' 	=> sprintf( __( 'Order &ndash; %s', 'woocommerce-bookings' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Order date parsed by strftime', 'woocommerce-bookings' ) ) ),
				'post_status' 	=> 'publish',
				'ping_status'	=> 'closed',
				'post_excerpt' 	=> '',
				'post_author' 	=> 1,
				'post_password'	=> uniqid( 'order_' )	// Protects the post just in case
			) );

			$order_id = wp_insert_post( $order_data, true );

			update_post_meta( $order_id, '_order_shipping', 0 );
			update_post_meta( $order_id, '_order_discount', 0 );
			update_post_meta( $order_id, '_cart_discount', 0 );
			update_post_meta( $order_id, '_order_tax', 0 );
			update_post_meta( $order_id, '_order_shipping_tax', 0 );
			update_post_meta( $order_id, '_order_total', $total );
			update_post_meta( $order_id, '_order_key', apply_filters('woocommerce_generate_order_key', uniqid('order_') ) );
			update_post_meta( $order_id, '_customer_user', absint( $customer_id ) );
			update_post_meta( $order_id, '_order_currency', get_woocommerce_currency() );
			update_post_meta( $order_id, '_prices_include_tax', get_option( 'woocommerce_prices_include_tax' ) );
			update_post_meta( $order_id, '_booking_order', '1' );
			wp_set_object_terms( $order_id, 'pending', 'shop_order_status' );
		}

		do_action( 'woocommerce_new_booking_order', $order_id );

		return $order_id;
	}

	/**
	 * Output any errors
	 */
	public function show_errors() {
		foreach ( $this->errors as $error )
			echo '<div class="error"><p>' . esc_html( $error ) . '</p></div>';
	}
}