<?php
/**
 * Registration Product Variation Class
 *
 * The registrations product variation class extends the WC_Product_Variation product class
 * to create registrations product variations.
 *
 * @package		Registrations for WooCommerce\WC_Product_Registrations
 * @since		1.3
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Product_Registrations extends WC_Product_Variable {
	/**
	 * Array of children variation IDs. Determined by children.
	 *
	 * @var array
	 */
	protected $children = null;

	/**
	 * Array of visible children variation IDs. Determined by children.
	 *
	 * @var array
	 */
	protected $visible_children = null;

	/**
	 * Array of variation attributes IDs. Determined by children.
	 *
	 * @var array
	 */
	protected $variation_attributes = null;

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'registrations';
	}

	/**
	 * Checks the product type to see if it is either this product's type or the parent's
	 * product type.
	 *
	 * @access public
	 * @param mixed $type Array or string of types
	 * @return bool
	 */
	public function is_type( $type ) {
		if ( 'registrations' == $type || ( is_array( $type ) && in_array( 'registrations', $type ) ) ) {
			return true;
		} else {
			return parent::is_type( $type );
		}
	}

	/**
	 * Return the formated schedule of given registration variation by id.
	 *
	 * @since 1.0.1
	 *
	 * @access public
	 * @param int $variation_id The variation ID to display schedule
	 */
	public function registration_schedule( $variation_id ) {
		$start = get_post_meta( $variation_id , '_event_start_time', true );
		$end = get_post_meta( $variation_id , '_event_end_time', true );

		if ( !empty( $start ) && !empty( $end ) ) {
			$schedule = sprintf( __( 'From %s to %s' , 'registrations-for-woocommerce' ), $start, $end );
			echo $schedule;
		}
	}

	/**
	 * Return the formated date of given registration variation by id.
	 *
	 * @since 1.0.0
	 *
	 * @param int $variation_id The variation ID to display schedule.
	 */
	public function registration_date( $variation_id, $date_format = null  ) {
		$date = get_post_meta( $variation_id , 'attribute_dates', true );

		if ( !empty( $date ) ) {
			$formated_date = WC_Registrations_Admin::registration_variation_option_name( $date, $date_format );
			echo $formated_date;
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Sync with child variations.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Sync a variable product with it's children. These sync functions sync
	 * upwards (from child to parent) when the variation is saved.
	 *
	 * @param WC_Product|int $product Product object or ID for which you wish to sync.
	 * @param bool           $save If true, the product object will be saved to the DB before returning it.
	 * @return WC_Product Synced product object.
	 */
	public static function sync( $product, $save = true ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( $product );
		}
		if ( is_a( $product, 'WC_Product_Registrations' ) ) {
			$data_store = WC_Data_Store::load( 'product-variable' );
			$data_store->sync_price( $product );
			$data_store->sync_stock_status( $product );
			self::sync_attributes( $product ); // Legacy update of attributes.

			do_action( 'woocommerce_variable_product_sync_data', $product );

			if ( $save ) {
				$product->save();
			}

			wc_do_deprecated_action(
				'woocommerce_variable_product_sync', array(
					$product->get_id(),
					$product->get_visible_children(),
				), '3.0', 'woocommerce_variable_product_sync_data, woocommerce_new_product or woocommerce_update_product'
			);
		}

		return $product;
	}

	/**
	 * Sync parent stock status with the status of all children and save.
	 *
	 * @param WC_Product|int $product Product object or ID for which you wish to sync.
	 * @param bool           $save If true, the product object will be saved to the DB before returning it.
	 * @return WC_Product Synced product object.
	 */
	public static function sync_stock_status( $product, $save = true ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			$product = wc_get_product( $product );
		}
		if ( is_a( $product, 'WC_Product_Registrations' ) ) {
			$data_store = WC_Data_Store::load( 'product-variable' );
			$data_store->sync_stock_status( $product );

			if ( $save ) {
				$product->save();
			}
		}

		return $product;
	}
}
