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

	var $product_type;

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'registrations';
	}

	/**
	 * Create a variable registration product object.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product ) {
		parent::__construct( $product );
        $this->parent_product_type = $this->product_type;
        $this->product_type = 'registrations';

		/**
		 * Register the custom data store
		 */
		add_filter( 'woocommerce_data_stores', array( $this, 'register_data_stores' ) );

		/**
		 * Handle the add to cart for this product type
		 */
		add_filter( 'woocommerce_add_to_cart_handler', array( &$this, 'add_to_cart_handler' ), 10, 2 );
	}


	/**
	 * Register data stores for registrations.
	 *
	 * @param  array  $data_stores
	 * @return array
	 */
	public function register_data_stores( $data_stores = array() ) {
	    $data_stores['product-registrations'] = 'WC_Product_Variable_Data_Store_CPT';
	    return $data_stores;
	}

    /**
	 * Checks the product type to see if it is either this product's type or the parent's
	 * product type.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param mixed $type Array or string of types
	 * @return bool
	 */
	public function registrations_is_type( $type ) {
		if ( $this->product_type == $type || ( is_array( $type ) && in_array( $this->product_type, $type ) ) ) {
			return true;
		} elseif ( $this->parent_product_type == $type || ( is_array( $type ) && in_array( $this->parent_product_type, $type ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Checks the product type to see if it is either this product's type or the parent's
	 * product type.
	 *
	 * @access public
	 * @param string $product_type A string representation of a product type
	 * @return string $handler
	 */
	public function add_to_cart_handler( $handler, $product ) {
		if ( 'registrations' === $handler ) {
			$handler = 'variable';
		}
		return $handler;
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

	/**
	 * Return whic days of week the current registration will occurr.
	 *
	 * @since 1.0.0
	 *
	 * @param int $variation_id The variation ID to display schedule
	 */
	public function registration_days_of_week( $variation_id ) {
		$date = get_post_meta( $variation_id , 'attribute_dates', true );

		if ( !empty( $date ) ) {
			$opt = json_decode( stripslashes( $date ) );

			if ( $opt ) {
				if ( $opt->type == 'single' ) {
					echo date_i18n( 'l', strtotime( $opt->date ) );
				}
				elseif ( $opt->type == 'multiple' ) {

					$date_option = '';
					$size = count( $opt->dates );

					for ( $i = 0; $i < $size ; $i++ ) {
						if ( $date_option == '' ) {
							$date_option .= date_i18n( 'l', strtotime( $opt->dates[ $i ] ) );
						} else {
							$date_option .= ', ' . date_i18n( 'l', strtotime( $opt->dates[ $i ] ) );
						}
					}

					echo $date_option;
				}
				elseif ( $opt->type == 'range' ) {
					if ( $week_days = get_post_meta( $variation_id, '_week_days', true ) ) {
						$content = '';
						$count = 0;

					    foreach( $week_days as $day ) {
							if( $count > 0 ) {
								$content .= ', ';
							}

							switch( $day ) {
								case 'sunday': $content .= __( 'sunday', 'registrations-for-woocommerce' );
								break;
								case 'monday': $content .= __( 'monday', 'registrations-for-woocommerce' );
								break;
								case 'tuesday': $content .= __( 'tuesday', 'registrations-for-woocommerce' );
								break;
								case 'wednesday': $content .= __( 'wednesday', 'registrations-for-woocommerce' );
								break;
								case 'thursday': $content .= __( 'thursday', 'registrations-for-woocommerce' );
								break;
								case 'friday': $content .= __( 'friday', 'registrations-for-woocommerce' );
								break;
								case 'saturday': $content .= __( 'saturday', 'registrations-for-woocommerce' );
								break;
								default:
							}

							$count++;
						}

						echo $content;
					}
				}
				else {
					return $opt;
				}
			}
		}
	}

	/**
	 * Sync a variable product with it's children. These sync functions sync
	 * upwards (from child to parent) when the variation is saved.
	 *
	 * @param WC_Product|int $product Product object or ID for which you wish to sync.
	 * @param bool $save If true, the prouduct object will be saved to the DB before returning it.
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

			wc_do_deprecated_action( 'woocommerce_variable_product_sync', array( $product->get_id(), $product->get_visible_children() ), '3.0', 'woocommerce_variable_product_sync_data, woocommerce_new_product or woocommerce_update_product' );
		}
		return $product;
	}

	/**
	 * Sync parent stock status with the status of all children and save.
	 *
	 * @param WC_Product|int $product Product object or ID for which you wish to sync.
	 * @param bool $save If true, the prouduct object will be saved to the DB before returning it.
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
