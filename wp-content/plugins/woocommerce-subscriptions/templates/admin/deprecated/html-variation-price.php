<?php
/**
 * Outputs a subscription variation's pricing fields for WooCommerce prior to 2.3
 *
 * @var int $loop
 * @var WP_POST $variation
 * @var string $subscription_period
 * @var array $variation_data array of variation data
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<tr class="variable_subscription_pricing show_if_variable-subscription">
	<td colspan="2">
		<label><?php printf( __( 'Subscription Price (%s)', 'woocommerce-subscriptions' ), get_woocommerce_currency_symbol() ) ?></label>
		<?php
		// Subscription Price
		woocommerce_wp_text_input( array(
			'id'            => 'variable_subscription_price[' . $loop . ']',
			'class'         => 'wc_input_subscription_price',
			'wrapper_class' => '_subscription_price_field',
			'label'         => sprintf( __( 'Subscription Price (%s)', 'woocommerce-subscriptions' ), get_woocommerce_currency_symbol() ),
			'placeholder'   => __( 'e.g. 5.90', 'woocommerce-subscriptions' ),
			'value'         => get_post_meta( $variation->ID, '_subscription_price', true ),
			'type'          => 'number',
			'custom_attributes' => array(
					'step' => 'any',
					'min'  => '0',
				)
			)
		);

		// Subscription Period Interval
		woocommerce_wp_select( array(
			'id'            => 'variable_subscription_period_interval[' . $loop . ']',
			'class'         => 'wc_input_subscription_period_interval',
			'wrapper_class' => '_subscription_period_interval_field',
			'label'         => __( 'Subscription Periods', 'woocommerce-subscriptions' ),
			'options'       => WC_Subscriptions_Manager::get_subscription_period_interval_strings(),
			'value'         => get_post_meta( $variation->ID, '_subscription_period_interval', true ),
			)
		);

		// Billing Period
		woocommerce_wp_select( array(
			'id'            => 'variable_subscription_period[' . $loop . ']',
			'class'         => 'wc_input_subscription_period',
			'wrapper_class' => '_subscription_period_field',
			'label'         => __( 'Billing Period', 'woocommerce-subscriptions' ),
			'value'         => $subscription_period,
			'description'   => __( 'for', 'woocommerce-subscriptions' ),
			'options'       => WC_Subscriptions_Manager::get_subscription_period_strings(),
			)
		);

		// Subscription Length
		woocommerce_wp_select( array(
			'id'            => 'variable_subscription_length[' . $loop . ']',
			'class'         => 'wc_input_subscription_length',
			'wrapper_class' => '_subscription_length_field',
			'label'         => __( 'Subscription Length', 'woocommerce-subscriptions' ),
			'options'       => WC_Subscriptions_Manager::get_subscription_ranges( $subscription_period ),
			'value'         => get_post_meta( $variation->ID, '_subscription_length', true ),
			)
		);
?>
	</td>
</tr>
<tr class="variable_subscription_trial show_if_variable-subscription variable_subscription_trial_sign_up">
	<td class="sign-up-fee-cell show_if_variable-subscription">
<?php
		// Sign-up Fee
		woocommerce_wp_text_input( array(
			'id'            => 'variable_subscription_sign_up_fee[' . $loop . ']',
			'class'         => 'wc_input_subscription_intial_price',
			'wrapper_class' => '_subscription_sign_up_fee_field',
			'label'         => sprintf( __( 'Sign-up Fee (%s)', 'woocommerce-subscriptions' ), get_woocommerce_currency_symbol() ),
			'placeholder'   => __( 'e.g. 9.90', 'woocommerce-subscriptions' ),
			'value'         => get_post_meta( $variation->ID, '_subscription_sign_up_fee', true ),
			'type'          => 'number',
			'custom_attributes' => array(
					'step' => 'any',
					'min'  => '0',
				)
			)
		);
?>	</td>
	<td colspan="1" class="show_if_variable-subscription">
		<label><?php _e( 'Free Trial', 'woocommerce-subscriptions' ); ?></label>
<?php
		// Trial Length
		woocommerce_wp_text_input( array(
			'id'            => 'variable_subscription_trial_length[' . $loop . ']',
			'class'         => 'wc_input_subscription_trial_length',
			'wrapper_class' => '_subscription_trial_length_field',
			'label'         => __( 'Free Trial', 'woocommerce-subscriptions' ),
			'placeholder'   => __( 'e.g. 3', 'woocommerce-subscriptions' ),
			'value'         => get_post_meta( $variation->ID, '_subscription_trial_length', true ),
			)
		);

		// Trial Period
		woocommerce_wp_select( array(
			'id'            => 'variable_subscription_trial_period[' . $loop . ']',
			'class'         => 'wc_input_subscription_trial_period',
			'wrapper_class' => '_subscription_trial_period_field',
			'label'         => __( 'Subscription Trial Period', 'woocommerce-subscriptions' ),
			'options'       => WC_Subscriptions_Manager::get_available_time_periods(),
			'description'   => sprintf( __( 'An optional period of time to wait before charging the first recurring payment. Any sign up fee will still be charged at the outset of the subscription. %s', 'woocommerce-subscriptions' ), self::get_trial_period_validation_message() ),
			'desc_tip'      => true,
			'value'         => WC_Subscriptions_Product::get_trial_period( $variation->ID ), // Explicity set value in to ensure backward compatibility
			)
		);?>
	</td>
</tr>
