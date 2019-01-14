<?php
/**
 * Registrations Cart Class
 *
 * Registratons add to cart handler and settings
 *
 * @package		Registrations for WooCommerce
 * @category	Class
 * @author		Allyson Souza
 * @since		2.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Registrations_Cart {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 * @access public
	 */
	public static function init() {
		// Define the add_to_cart handler
		add_filter( 'woocommerce_add_to_cart_handler', __CLASS__ . '::add_to_cart_handler', 10, 2 );

		// Optional filter to prevent past events
		add_filter( 'woocommerce_add_to_cart_validation', __CLASS__ . '::validate_registration', 10, 5 );

		// Filter item name in cart and order
		add_filter( 'woocommerce_product_variation_title',  __CLASS__ . '::format_registration_variation_on_titles', 10, 4 ); 
	}

	/**
	 * Set the add_to_cart handler type to variable
	 * @param string $handler Current product type
	 * @param object $product Current product
	 */
	public static function add_to_cart_handler( $handler, $product ) {
		if ( 'registrations' === $handler ) {
			$handler = 'variable';
		}

		return $handler;
	}

	/**
	 * Optionally validates an attemp to put an item on the cart to validate if the event
	 * is not on the past or after the maximum registration date.
	 *
	 * @access public
	 * @param bool 	$passed if the validation has passed up to this point
	 * @param int 	$product_id the woocommerce's product id
	 * @param int 	$quantity the amount that was put into the cart
	 * @param int 	$variation_id the current woocommerce's variation id
	 *
	 * @return bool $passed the new validation status
	 */
	public static function validate_registration( $passed, $product_id, $quantity = null, $variation_id = null, $variations = null ) {

		if ( $variation_id != null ) {
			$prevent_past_events = get_post_meta( $product_id, '_prevent_past_events', true );

			if ( $prevent_past_events == 'yes' ) {

				$days_to_prevent = get_post_meta( $product_id, '_days_to_prevent', true );

				if ( empty( $days_to_prevent ) && $days_to_prevent != '0' ) {
					$days_to_prevent = 0;
				}

				$date = get_post_meta( $variation_id , 'attribute_dates', true );
				$decoded_date = json_decode($date);
				$event_date = '';

				if ( $decoded_date->type == 'single' ) {
					$event_date = $decoded_date->date;
				} else {
					$event_date = $decoded_date->dates[0];
				}

				$current_time = date( 'd-m-Y', time() );
				$target_date = $current_time;
				$max_date = $current_time;

				if ( $days_to_prevent >= 0 ) {
					$target_date = date( 'd-m-Y', strtotime( '-' . $days_to_prevent . ' days' . $event_date ) );
				}

				if ( strtotime( $current_time ) > strtotime( $target_date ) || strtotime( $current_time ) > strtotime( $max_date ) ) {
					$passed = false;
					wc_add_notice( __( 'The selected date is no longer available.', 'registrations-for-woocommerce' ), 'error' );
				}
			}
		}
		return $passed;
	}

	public static function format_registration_variation_on_titles( $rtrim, $product, $title_base, $title_suffix ) {
		if ( json_decode( $title_suffix ) ) {
			return $title_base . ' - ' . WC_Registrations_Helpers::get_formatted_date( $title_suffix );
		}

		return $rtrim;
	}
}
WC_Registrations_Cart::init();
