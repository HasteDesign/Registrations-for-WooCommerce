<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * WC_Bookings_Cart class.
 */
class WC_Bookings_Cart {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_booking_add_to_cart', array( $this, 'add_to_cart' ), 30 );
		add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 10, 1 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_cart_item' ), 10, 3 );
		add_action( 'woocommerce_add_order_item_meta', array( $this, 'order_item_meta' ), 10, 2 );
		add_filter( 'add_to_cart_redirect', array( $this, 'add_to_cart_redirect' ) );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_booking_requires_confirmation' ), 20, 2 );
	}

	/**
	 * Add to cart for bookings
	 */
	public function add_to_cart() {
		global $product;

		// Prepare form
		$booking_form = new WC_Booking_Form( $product );

		// Get template
		woocommerce_get_template( 'single-product/add-to-cart/booking.php', array( 'booking_form' => $booking_form ), 'woocommerce-bookings', WC_BOOKINGS_TEMPLATE_PATH );
	}

	/**
	 * When a booking is added to the cart, validate it
	 *
	 * @param mixed $passed
	 * @param mixed $product_id
	 * @param mixed $qty
	 * @return bool
	 */
	public function validate_add_cart_item( $passed, $product_id, $qty ) {
		global $woocommerce;

		$product      = get_product( $product_id );

		if ( $product->product_type !== 'booking' ) {
			return $passed;
		}

		$booking_form = new WC_Booking_Form( $product );
		$data         = $booking_form->get_posted_data();
		$validate     = $booking_form->is_bookable( $data );

		if ( is_wp_error( $validate ) ) {
			wc_add_notice( $validate->get_error_message(), 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Adjust the price of the booking product based on booking properties
	 *
	 * @access public
	 * @param mixed $cart_item
	 * @return array cart item
	 */
	public function add_cart_item( $cart_item ) {
		if ( ! empty( $cart_item['booking'] ) && ! empty( $cart_item['booking']['_cost'] ) ) {
			$cart_item['data']->set_price( $cart_item['booking']['_cost'] );
		}
		return $cart_item;
	}

	/**
	 * Get data from the session and add to the cart item's meta
	 *
	 * @access public
	 * @param mixed $cart_item
	 * @param mixed $values
	 * @return array cart item
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {
		if ( ! empty( $values['booking'] ) ) {
			$cart_item['booking'] = $values['booking'];
			$cart_item            = $this->add_cart_item( $cart_item );
		}
		return $cart_item;
	}

	/**
	 * Add posted data to the cart item
	 *
	 * @access public
	 * @param mixed $cart_item_meta
	 * @param mixed $product_id
	 * @return void
	 */
	public function add_cart_item_data( $cart_item_meta, $product_id ) {
		$product = get_product( $product_id );

		if ( 'booking' !== $product->product_type ) {
			return $cart_item_meta;
		}

		$booking_form                       = new WC_Booking_Form( $product );
		$cart_item_meta['booking']          = $booking_form->get_posted_data( $_POST );
		$cart_item_meta['booking']['_cost'] = $booking_form->calculate_booking_cost( $_POST );
		
		return $cart_item_meta;
	}

	/**
	 * Put meta data into format which can be displayed
	 *
	 * @access public
	 * @param mixed $other_data
	 * @param mixed $cart_item
	 * @return array meta
	 */
	public function get_item_data( $other_data, $cart_item ) {
		if ( ! empty( $cart_item['booking'] ) ) {
			foreach ( $cart_item['booking'] as $key => $value ) {

				if ( substr( $key, 0, 1 ) !== '_' )
					$other_data[] = array(
						'name'    => get_wc_booking_data_label( $key, $cart_item['data'] ),
						'value'   => $value,
						'display' => ''
					);
			}
		}
		return $other_data;
	}

	/**
	 * order_item_meta function.
	 *
	 * @param mixed $item_id
	 * @param mixed $values
	 */
	public function order_item_meta( $item_id, $values ) {

		if ( ! empty( $values['booking'] ) ) {
			$product = $values['data'];

			// Create the new booking
			$new_booking_data = array(
				'order_item_id' => $item_id, // Order item ID
				'product_id'    => $values['product_id'], // Booking ID
				'cost'          => $values['booking']['_cost'], // Cost of this booking
				'start_date'    => $values['booking']['_start_date'],
				'end_date'      => $values['booking']['_end_date'],
				'all_day'       => $values['booking']['_all_day']
			);

			// Check if the booking has resources
			if ( isset( $values['booking']['_resource_id'] ) ) {
				$new_booking_data['resource_id'] = $values['booking']['_resource_id']; // ID of the resource
			}

			// Checks if the booking allows persons
			if ( isset( $values['booking']['_persons'] ) ) {
				$new_booking_data['persons'] = $values['booking']['_persons']; // Count of persons making booking
			}

			$booking_status = 'unpaid';

			// Set as pending when the booking requires confirmation
			if ( wc_booking_requires_confirmation( $values['product_id'] ) ) {
				$booking_status = 'pending';
			}

			$new_booking = get_wc_booking( $new_booking_data );
			$new_booking->create( $booking_status );

			// Add summary of details to line item
			foreach ( $values['booking'] as $key => $value ) {
				if ( strpos( $key, '_' ) !== 0 ) {
					woocommerce_add_order_item_meta( $item_id, get_wc_booking_data_label( $key, $product ), $value );
				}
			}
		}
	}

	/**
	 * Redirects directly to the cart the products they need confirmation
	 *
	 * @param string $url
	 */
	public function add_to_cart_redirect( $url ) {
		if (
			isset( $_REQUEST['add-to-cart'] )
			&& is_numeric( $_REQUEST['add-to-cart'] )
			&& wc_booking_requires_confirmation( intval( $_REQUEST['add-to-cart'] ) )
		) {
			// Remove add to cart messages
			wc_clear_notices();

			// Go to checkout
			return WC()->cart->get_checkout_url();
		}

		return $url;
	}

	/**
	 * Remove all bookings that requires confirmation.
	 *
	 * @return void
	 */
	protected function remove_booking_that_requires_confirmation() {
		foreach( WC()->cart->cart_contents as $item_key => $item ) {
			if ( wc_booking_requires_confirmation( $item['product_id'] ) ) {
				WC()->cart->set_quantity( $item_key, 0 );
			}
		}
	}

	/**
	 * Removes all products when cart have a booking which requires confirmation
	 *
	 * @param  bool $passed
	 * @param  int  $product_id
	 *
	 * @return bool
	 */
	public function validate_booking_requires_confirmation( $passed, $product_id ) {

		if ( wc_booking_requires_confirmation( $product_id ) ) {

			// Remove any other cart items.
			WC()->cart->empty_cart();

		} elseif ( wc_booking_cart_requires_confirmation() ) {
			// Remove bookings that requires confirmation.
			$this->remove_booking_that_requires_confirmation();

			wc_add_notice( __( 'A booking that requires confirmation has been removed from your cart. It is not possible to complete the purchased along with a booking that doesn\'t require confirmation.', 'woocommerce-bookings' ), 'notice' );
		}

		return $passed;
	}
}

new WC_Bookings_Cart();
