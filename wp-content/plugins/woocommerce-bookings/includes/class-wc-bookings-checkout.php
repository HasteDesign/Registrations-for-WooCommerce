<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WC_Bookings_Checkout class.
 */
class WC_Bookings_Checkout {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'remove_payment_methods' ) );
	}

	/**
	 * Removes all payment methods when cart has a booking that requires confirmation.
	 *
	 * @param  array $available_gateways
	 * @return array
	 */
	public function remove_payment_methods( $available_gateways ) {

		if ( wc_booking_cart_requires_confirmation() ) {
			unset( $available_gateways );

			$available_gateways = array();
			$available_gateways['wc-booking-gateway'] = new WC_Bookings_Gateway();
		}

		return $available_gateways;
	}
}

new WC_Bookings_Checkout();
