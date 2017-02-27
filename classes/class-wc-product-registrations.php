<?php
/**
 * Registration Product Variation Class
 *
 * The subscription product variation class extends the WC_Product_Variation product class
 * to create subscription product variations.
 *
 * @class 		WC_Product_Registrations
 * @package		Registrations for WooCommerce
 * @category	Class
 * @since		1.3
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Product_Registrations extends WC_Product_Variable {

	var $product_type;

	/**
	 * Create a variable registration product object.
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product ) {

		parent::__construct( $product );

        $this->parent_product_type = $this->product_type;

        $this->product_type = 'registrations';

		add_filter( 'woocommerce_add_to_cart_handler', array( &$this, 'add_to_cart_handler' ), 10, 2 );

		/**
		 * Optional filter to prevent past events
		 */
		add_filter( 'woocommerce_add_to_cart_validation', __CLASS__ . '::validate_registration', 10, 5 );
	}

    /**
	 * Checks the product type to see if it is either this product's type or the parent's
	 * product type.
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
					$days_to_prevent = -1;
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

				if ( $days_to_prevent != -1 ) {
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

	/**
	 * Return the formated schedule of given registration variation by id.
	 *
	 * @access public
	 * @param int $variation_id The variation ID to display schedule
	 * @return
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
	 * @param int $variation_id The variation ID to display schedule
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
								case 'saturday': $content .= __( 'saturday', 'registrations-for-woocommerce' );
								break;
								case 'monday': $content .= __( 'monday', 'registrations-for-woocommerce' );
								break;
								case 'sunday': $content .= __( 'sunday', 'registrations-for-woocommerce' );
								break;
								case 'tuesday': $content .= __( 'tuesday', 'registrations-for-woocommerce' );
								break;
								case 'wednesday': $content .= __( 'wednesday', 'registrations-for-woocommerce' );
								break;
								case 'thursday': $content .= __( 'thursday', 'registrations-for-woocommerce' );
								break;
								case 'friday': $content .= __( 'friday', 'registrations-for-woocommerce' );
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
