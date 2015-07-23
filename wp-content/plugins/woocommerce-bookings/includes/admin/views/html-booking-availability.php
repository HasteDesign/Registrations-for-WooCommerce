<div id="bookings_availability" class="panel woocommerce_options_panel">
	<div class="options_group">
		<?php woocommerce_wp_text_input( array( 'id' => '_wc_booking_qty', 'label' => __( 'Max bookings per block', 'woocommerce-bookings' ), 'description' => __( 'The maximum bookings allowed for each block. Can be overridden at resource level.', 'woocommerce-bookings' ), 'value' => max( absint( get_post_meta( $post_id, '_wc_booking_qty', true ) ), 1 ), 'desc_tip' => true, 'type' => 'number', 'custom_attributes' => array(
			'min'   => '',
			'step' 	=> '1'
		) ) ); ?>
		<?php
			$min_date      = absint( get_post_meta( $post_id, '_wc_booking_min_date', true ) );
			$min_date_unit = get_post_meta( $post_id, '_wc_booking_min_date_unit', true );
		?>
		<p class="form-field">
			<label for="_wc_booking_min_date"><?php _e( 'Minimum block bookable', 'woocommerce-bookings' ); ?></label>
			<input type="number" name="_wc_booking_min_date" id="_wc_booking_min_date" value="<?php echo $min_date; ?>" step="1" min="0" style="margin-right: 7px; width: 4em;">
			<select name="_wc_booking_min_date_unit" id="_wc_booking_min_date_unit" class="short" style="margin-right: 7px;">
				<option value="month" <?php selected( $min_date_unit, 'month' ); ?>><?php _e( 'Month(s)', 'woocommerce-bookings' ); ?></option>
				<option value="week" <?php selected( $min_date_unit, 'week' ); ?>><?php _e( 'Week(s)', 'woocommerce-bookings' ); ?></option>
				<option value="day" <?php selected( $min_date_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-bookings' ); ?></option>
				<option value="hour" <?php selected( $min_date_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-bookings' ); ?></option>
			</select> <?php _e( 'into the future', 'woocommerce-bookings' ); ?>
		</p>
		<?php
			$max_date = get_post_meta( $post_id, '_wc_booking_max_date', true );
			if ( $max_date == '' )
				$max_date = 12;
			$max_date      = max( absint( $max_date ), 1 );
			$max_date_unit = get_post_meta( $post_id, '_wc_booking_max_date_unit', true );
		?>
		<p class="form-field">
			<label for="_wc_booking_max_date"><?php _e( 'Maximum block bookable', 'woocommerce-bookings' ); ?></label>
			<input type="number" name="_wc_booking_max_date" id="_wc_booking_max_date" value="<?php echo $max_date; ?>" step="1" min="1" style="margin-right: 7px; width: 4em;">
			<select name="_wc_booking_max_date_unit" id="_wc_booking_max_date_unit" class="short" style="margin-right: 7px;">
				<option value="month" <?php selected( $max_date_unit, 'month' ); ?>><?php _e( 'Month(s)', 'woocommerce-bookings' ); ?></option>
				<option value="week" <?php selected( $max_date_unit, 'week' ); ?>><?php _e( 'Week(s)', 'woocommerce-bookings' ); ?></option>
				<option value="day" <?php selected( $max_date_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-bookings' ); ?></option>
				<option value="hour" <?php selected( $max_date_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-bookings' ); ?></option>
			</select> <?php _e( 'into the future', 'woocommerce-bookings' ); ?>
		</p>
		<?php 
			woocommerce_wp_select( 
				array( 
					'id'          => '_wc_booking_default_date_availability',
					'label'       => __( 'All dates are...', 'woocommerce-bookings' ), 
					'description' => '', 
					'value'       => get_post_meta( $post_id, '_wc_booking_default_date_availability', true ), 
					'options' => array(
						'available'     => __( 'available by default', 'woocommerce-bookings' ),
						'non-available' => __( 'not-available by default', 'woocommerce-bookings' )
					), 
					'description' => __( 'This option affects how you use the rules below.', 'woocommerce-bookings' ) 
				) 
			); 

			woocommerce_wp_select( 
				array( 
					'id'          => '_wc_booking_check_availability_against',
					'label'       => __( 'Check rules against...', 'woocommerce-bookings' ), 
					'description' => '', 
					'value'       => get_post_meta( $post_id, '_wc_booking_check_availability_against', true ), 
					'options' => array(
						''      => __( 'All blocks being booked', 'woocommerce-bookings' ),
						'start' => __( 'The starting block only', 'woocommerce-bookings' )
					), 
					'description' => __( 'This option affects how bookings are checked for availability.', 'woocommerce-bookings' ) 
				) 
			); 
		?>
		<p class="form-field _wc_booking_first_block_time_field">
			<label for="_wc_booking_first_block_time"><?php _e( 'First block starts at...', 'woocommerce-bookings' ); ?></label>
			<input type="time" name="_wc_booking_first_block_time" id="_wc_booking_first_block_time" value="<?php echo get_post_meta( $post_id, '_wc_booking_first_block_time', true ); ?>" placeholder="HH:MM" />
		</p>
	</div>
	<div class="options_group">
		<div class="table_grid">
			<table class="widefat">
				<thead>
					<tr>
						<th class="sort" width="1%">&nbsp;</th>
						<th><?php _e( 'Range type', 'woocommerce-bookings' ); ?></th>
						<th><?php _e( 'From', 'woocommerce-bookings' ); ?></th>
						<th><?php _e( 'To', 'woocommerce-bookings' ); ?></th>
						<th><?php _e( 'Bookable', 'woocommerce-bookings' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'If not bookable, users won\'t be able to choose this block for their booking.', 'woocommerce-bookings' ); ?>">[?]</a></th>
						<th class="remove" width="1%">&nbsp;</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th colspan="6">
							<a href="#" class="button button-primary add_row" data-row="<?php
								ob_start();
								include( 'html-booking-availability-fields.php' );
								$html = ob_get_clean();
								echo esc_attr( $html );
							?>"><?php _e( 'Add Range', 'woocommerce-bookings' ); ?></a>
							<span class="description"><?php _e( 'Rules further down the table will override those at the top.', 'woocommerce-bookings' ); ?></span>
						</th>
					</tr>
				</tfoot>
				<tbody id="availability_rows">
					<?php
						$values = get_post_meta( $post_id, '_wc_booking_availability', true );
						if ( ! empty( $values ) && is_array( $values ) ) {
							foreach ( $values as $availability ) {
								include( 'html-booking-availability-fields.php' );
							}
						}
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>