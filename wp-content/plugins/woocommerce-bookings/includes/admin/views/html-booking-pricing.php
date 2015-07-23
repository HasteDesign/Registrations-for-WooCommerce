<div id="bookings_pricing" class="panel woocommerce_options_panel">
	<div class="options_group">

		<?php woocommerce_wp_text_input( array( 'id' => '_wc_booking_cost', 'label' => __( 'Base cost', 'woocommerce-bookings' ), 'description' => __( 'One-off cost for the booking as a whole.', 'woocommerce-bookings' ), 'value' => get_post_meta( $post_id, '_wc_booking_cost', true ), 'type' => 'number', 'desc_tip' => true, 'custom_attributes' => array(
			'min'   => '',
			'step' 	=> '0.01'
		) ) ); ?>

		<?php woocommerce_wp_text_input( array( 'id' => '_wc_booking_base_cost', 'label' => __( 'Block cost', 'woocommerce-bookings' ), 'description' => __( 'This is the cost per block booked. All other costs (for resources and persons) are added to this.', 'woocommerce-bookings' ), 'value' => get_post_meta( $post_id, '_wc_booking_base_cost', true ), 'type' => 'number', 'desc_tip' => true, 'custom_attributes' => array(
			'min'   => '',
			'step' 	=> '0.01'
		) ) ); ?>

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
						<th colspan="2"><?php _e( 'Base cost', 'woocommerce-bookings' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Enter a cost for this rule. Applied to the booking as a whole.', 'woocommerce-bookings' ); ?>">[?]</a></th>
						<th colspan="2"><?php _e( 'Booking cost', 'woocommerce-bookings' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Enter a cost for this rule. Applied to each booking block.', 'woocommerce-bookings' ); ?>">[?]</a></th>
						<th class="remove" width="1%">&nbsp;</th>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<th colspan="9">
							<a href="#" class="button button-primary add_row" data-row="<?php
								ob_start();
								include( 'html-booking-pricing-fields.php' );
								$html = ob_get_clean();
								echo esc_attr( $html );
							?>"><?php _e( 'Add Range', 'woocommerce-bookings' ); ?></a>
							<span class="description"><?php _e( 'All matching rules will be applied to the booking.', 'woocommerce-bookings' ); ?></span>
						</th>
					</tr>
				</tfoot>
				<tbody id="pricing_rows">
					<?php
						$values = get_post_meta( $post_id, '_wc_booking_pricing', true );
						if ( ! empty( $values ) && is_array( $values ) ) {
							foreach ( $values as $pricing ) {
								include( 'html-booking-pricing-fields.php' );
							}
						}
					?>
				</tbody>
			</table>
		</div>
	</div>
</div>