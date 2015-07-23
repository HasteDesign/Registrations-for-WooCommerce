<div class="options_group show_if_booking">

	<?php
		$duration_type = get_post_meta( $post_id, '_wc_booking_duration_type', true );
		$duration      = max( absint( get_post_meta( $post_id, '_wc_booking_duration', true ) ), 1 );
		$duration_unit = get_post_meta( $post_id, '_wc_booking_duration_unit', true );
	?>
	<p class="form-field">
		<label for="_wc_booking_duration_type"><?php _e( 'Booking duration', 'woocommerce-bookings' ); ?></label>
		<select name="_wc_booking_duration_type" id="_wc_booking_duration_type" class="" style="margin-right: 7px;">
			<option value="fixed" <?php selected( $duration_type, 'fixed' ); ?>><?php _e( 'Fixed blocks of', 'woocommerce-bookings' ); ?></option>
			<option value="customer" <?php selected( $duration_type, 'customer' ); ?>><?php _e( 'Customer defined blocks of', 'woocommerce-bookings' ); ?></option>
		</select>
		<input type="number" name="_wc_booking_duration" id="_wc_booking_duration" value="<?php echo $duration; ?>" step="1" min="1" style="margin-right: 7px; width: 4em;">
		<select name="_wc_booking_duration_unit" id="_wc_booking_duration_unit" class="short" style="margin-right: 7px;">
			<option value="month" <?php selected( $duration_unit, 'month' ); ?>><?php _e( 'Month(s)', 'woocommerce-bookings' ); ?></option>
			<option value="day" <?php selected( $duration_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-bookings' ); ?></option>
			<option value="hour" <?php selected( $duration_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-bookings' ); ?></option>
			<option value="minute" <?php selected( $duration_unit, 'minute' ); ?>><?php _e( 'Minutes(s)', 'woocommerce-bookings' ); ?></option>
		</select>
	</p>

	<div id="min_max_duration">

		<?php woocommerce_wp_text_input( array( 'id' => '_wc_booking_min_duration', 'label' => __( 'Minimum duration', 'woocommerce-bookings' ), 'description' => __( 'The minimum allowed duration the user can input.', 'woocommerce-bookings' ), 'value' => max( absint( get_post_meta( $post_id, '_wc_booking_min_duration', true ) ), 1 ), 'desc_tip' => true, 'type' => 'number', 'custom_attributes' => array(
			'min'   => '',
			'step' 	=> '1'
		) ) ); ?>

		<?php woocommerce_wp_text_input( array( 'id' => '_wc_booking_max_duration', 'label' => __( 'Maximum duration', 'woocommerce-bookings' ), 'description' => __( 'The maximum allowed duration the user can input.', 'woocommerce-bookings' ), 'value' => max( absint( get_post_meta( $post_id, '_wc_booking_max_duration', true ) ), 1 ), 'desc_tip' => true, 'type' => 'number', 'custom_attributes' => array(
			'min'   => '',
			'step' 	=> '1'
		) ) ); ?>

	</div>

	<?php
		woocommerce_wp_select( array( 'id' => '_wc_booking_calendar_display_mode', 'label' => __( 'Calendar display mode', 'woocommerce-bookings' ), 'description' => __( 'Choose how the calendar is displayed on the booking form.', 'woocommerce-bookings' ), 'options' => array(
				''               => __( 'Display calendar on click', 'woocommerce-bookings' ),
				'always_visible' => __( 'Calendar always visible', 'woocommerce-bookings' )
			), 'desc_tip' => true, 'class' => 'select' ) );
	?>

	<?php
		woocommerce_wp_checkbox( array( 'id' => '_wc_booking_requires_confirmation', 'label' => __( 'Requires confirmation?', 'woocommerce-bookings' ), 'description' => __( 'Check this box if the booking requires admin approval/confirmation. Payment will not be taken during checkout.', 'woocommerce-bookings' ) ) );
	?>

	<script type="text/javascript">
		jQuery('._tax_status_field').closest('.show_if_simple').addClass('show_if_booking');
	</script>
</div>