<?php
namespace Haste\RegistrationsForWoo\Products;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Registrations Cart Class
 *
 * Registratons add to cart handler and settings
 *
 * @package     Registrations for WooCommerce
 * @category    Class
 * @author      Allyson Souza
 * @since       2.0
 */

class Cart {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 * @access public
	 */
	public static function init() {
		// Define the add_to_cart handler
		add_filter( 'woocommerce_add_to_cart_handler', __CLASS__ . '::add_to_cart_handler', 10, 2 );

		// Check if registration should be displayed
		add_filter( 'woocommerce_variation_is_visible', __CLASS__ . '::registration_is_visible', 10, 4 );

		// Filter item name in cart and order
		add_filter( 'woocommerce_product_variation_title', __CLASS__ . '::format_registration_variation_on_titles', 10, 4 );
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
	 * Registration is visible
	 *
	 * Check if a registration date must be displayed in dropdown. Hide past events.
	 *
	 * @access public
	 * @param bool  $visible        if the validation has passed up to this point
	 * @param int   $variation_id   the current woocommerce's variation id
	 * @param int   $product_id     the woocommerce's product id
	 * @param int   $variation      the WC_Product_Variation object
	 *
	 * @return bool                 if the variation should ou should not be displayed.
	 */
	public static function registration_is_visible( $visible, $variation_id, $product_id, $variation ) {
		$attributes = $variation->get_variation_attributes();

		if ( isset( $attributes['attribute_dates'] ) ) {
			$days_to_prevent = \WC_Product_Registrations::allowed_days_to_register_before( $product_id );
			$event_date      = self::get_variation_date( $variation->get_id() );

			$current_time = date( 'd-m-Y', time() );
			$target_date  = $current_time;
			$max_date     = $current_time;

			if ( $days_to_prevent >= 0 ) {
				$target_date = date( 'd-m-Y', strtotime( '-' . $days_to_prevent . ' days' . $event_date ) );
			}

			if ( strtotime( $current_time ) > strtotime( $target_date ) || strtotime( $current_time ) > strtotime( $max_date ) ) {
				return false;
			}
		}

		return $visible;
	}
		/**
	 * Get variation date
	 *
	 * Get variation date from variation ID.
	 *
	 * @since 2.1
	 *
	 * @return string $event_date   The variation date in YYYY-MM-DD format.
	 */
	private static function get_variation_date( $variation_id ) {
		$date         = get_post_meta( $variation_id, 'attribute_dates', true );
		$decoded_date = json_decode( $date );
		$event_date   = '';

		if ( 'single' === $decoded_date->type ) {
			$event_date = $decoded_date->date;
		} else {
			$event_date = $decoded_date->dates[0];
		}

		return $event_date;
	}
	/**
	 * Format dates on product titles
	 *
	 * Format registration dates in product title, preventing the JSON format to be displayed.
	 *
	 * @since 2.1
	 *
	 * @return string $rtrim    The product title with date formatted.
	 */
	public static function format_registration_variation_on_titles( $rtrim, $product, $title_base, $title_suffix ) {
		if ( json_decode( $title_suffix ) ) {
			return $title_base . ' - ' . \WC_Registrations_Helpers::get_formatted_date( $title_suffix );
		}

		return $rtrim;
	}
}

