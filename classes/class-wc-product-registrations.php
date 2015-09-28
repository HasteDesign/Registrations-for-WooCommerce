<?php
/**
 * Subscription Product Variation Class
 *
 * The subscription product variation class extends the WC_Product_Variation product class
 * to create subscription product variations.
 *
 * @class 		WC_Product_Subscription
 * @package		WooCommerce Subscriptions
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
	 * Return the formated schedule of given registration variation by id.
	 *
	 * @access public
	 * @param int $variation_id The variation ID to display schedule
	 * @return
	 */
	public function registration_schedule( $variation_id ) {
		$start = get_post_meta( $variation_id , '_event_start_time', true );
		$end = get_post_meta( $variation_id , '_event_end_time', true );

		if( !empty( $start ) && !empty( $end ) ) {
			$schedule = sprintf( __( 'From %s to %s' , 'woocommerce-registrations' ), $start, $end );
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

		if( !empty( $date ) ) {
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

		if( !empty( $date ) ) {
			$opt = json_decode( stripslashes( $date ) );

			if ( $opt ) {
				if ( $opt->type == 'single' ) {
					echo date_i18n( 'l', strtotime( $opt->date ) );
				}
				elseif ( $opt->type == 'multiple' ) {

					$date_option = '';
					$size = count( $opt->dates );

					for( $i = 0; $i < $size ; $i++ ) {
						if( $date_option == '' ) {
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
								case 'saturday': $content .= __( 'saturday', 'woocommerce-registrations' );
								break;
								case 'monday': $content .= __( 'monday', 'woocommerce-registrations' );
								break;
								case 'sunday': $content .= __( 'sunday', 'woocommerce-registrations' );
								break;
								case 'tuesday': $content .= __( 'tuesday', 'woocommerce-registrations' );
								break;
								case 'wednesday': $content .= __( 'wednesday', 'woocommerce-registrations' );
								break;
								case 'thursday': $content .= __( 'thursday', 'woocommerce-registrations' );
								break;
								case 'friday': $content .= __( 'friday', 'woocommerce-registrations' );
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
