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
	 * Create a simple subscription product object.
	 *
	 * @access public
	 * @param mixed $product
	 */
	public function __construct( $product, $args = array() ) {

		parent::__construct( $product, $args = array() );

        $this->parent_product_type = $this->product_type;

        $this->product_type = 'registrations';

		// $this->subscription_variation_level_meta_data = array(
		// 	'subscription_price'             => 0,
		// 	'subscription_period'            => '',
		// 	'subscription_period_interval'   => 'day',
		// 	'subscription_length'            => 0,
		// 	'subscription_trial_length'      => 0,
		// 	'subscription_trial_period'      => 'day',
		// 	'subscription_sign_up_fee'       => 0,
		// 	'subscription_payment_sync_date' => 0,
		// );
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
		if ( $this->product_type == $type || ( is_array( $type ) && in_array( $this->product_type, $type ) ) ) {
			return true;
		} elseif ( $this->parent_product_type == $type || ( is_array( $type ) && in_array( $this->parent_product_type, $type ) ) ) {
			return true;
		} else {
			return false;
		}
	}
}
