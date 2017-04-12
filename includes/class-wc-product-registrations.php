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
	public function __construct() {
		// Register Registrations for WooCommerce Data Store
		add_filter( 'woocommerce_data_stores', __CLASS__ . '::add_data_store', 10, 1 );
	}

	/**
	 * Register data stores for WooCommerce 3.0+
	 *
	 * @since 2.0.0
	 */
	public static function add_data_store( $data_stores ) {
		$data_stores['registrations']                   = 'WC_Product_Registrations_Data_Store_CPT';
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
}
