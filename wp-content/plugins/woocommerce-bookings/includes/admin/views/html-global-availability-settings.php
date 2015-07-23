<?php
// Save the field values
if ( ! empty( $_POST['bookings_availability_submitted'] ) ) {
	$availability = array();
	$row_size     = isset( $_POST[ 'wc_booking_availability_type' ] ) ? sizeof( $_POST[ 'wc_booking_availability_type' ] ) : 0;
	for ( $i = 0; $i < $row_size; $i ++ ) {
		$availability[ $i ]['type']     = wc_clean( $_POST[ 'wc_booking_availability_type' ][ $i ] );
		$availability[ $i ]['bookable'] = wc_clean( $_POST[ 'wc_booking_availability_bookable' ][ $i ] );

		switch ( $availability[ $i ]['type'] ) {
			case 'custom' :
				$availability[ $i ]['from'] = wc_clean( $_POST[ 'wc_booking_availability_from_date' ][ $i ] );
				$availability[ $i ]['to']   = wc_clean( $_POST[ 'wc_booking_availability_to_date' ][ $i ] );
			break;
			case 'months' :
				$availability[ $i ]['from'] = wc_clean( $_POST[ 'wc_booking_availability_from_month' ][ $i ] );
				$availability[ $i ]['to']   = wc_clean( $_POST[ 'wc_booking_availability_to_month' ][ $i ] );
			break;
			case 'weeks' :
				$availability[ $i ]['from'] = wc_clean( $_POST[ 'wc_booking_availability_from_week' ][ $i ] );
				$availability[ $i ]['to']   = wc_clean( $_POST[ 'wc_booking_availability_to_week' ][ $i ] );
			break;
			case 'days' :
				$availability[ $i ]['from'] = wc_clean( $_POST[ 'wc_booking_availability_from_day_of_week' ][ $i ] );
				$availability[ $i ]['to']   = wc_clean( $_POST[ 'wc_booking_availability_to_day_of_week' ][ $i ] );
			break;
			case 'time' :
			case 'time:1' :
			case 'time:2' :
			case 'time:3' :
			case 'time:4' :
			case 'time:5' :
			case 'time:6' :
			case 'time:7' :
				$availability[ $i ]['from'] = wc_booking_sanitize_time( $_POST[ 'wc_booking_availability_from_time' ][ $i ] );
				$availability[ $i ]['to']   = wc_booking_sanitize_time( $_POST[ 'wc_booking_availability_to_time' ][ $i ] );
			break;
		}
	}
	update_option( 'wc_global_booking_availability', $availability );
	echo '<div class="updated"><p>' . __( 'Settings saved', 'woocommerce-bookings' ) . '</p></div>';
}
?>

<form method="post" action="" id="bookings_settings">
	<input type="hidden" name="bookings_availability_submitted" value="1" />
	<div id="poststuff">
		<div class="postbox">
			<h3 class="hndle"><?php _e( 'Global availability', 'woocommerce-bookings' ); ?></h3>
			<div class="inside">
				<p class=""><?php _e( 'The availability rules you define here will affect all bookable products in your store.', 'woocommerce-bookings' ); ?></p>
				<div class="table_grid" id="bookings_availability">
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
								$values = get_option( 'wc_global_booking_availability' );
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
	</div>
	<p class="submit">
		<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'woocommerce-bookings' ); ?>" />
	</p>
</form>