<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div class="wrap woocommerce">
	<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
	<h2><?php _e( 'Create Booking', 'woocommerce-bookings' ); ?></h2>

	<?php $this->show_errors(); ?>

	<form method="POST">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label><?php _e( 'Booking Data', 'woocommerce-bookings' ); ?></label>
					</th>
					<td>
						<div class="wc-bookings-booking-form">
							<?php $booking_form->output(); ?>
							<div class="wc-bookings-booking-cost" style="display:none"></div>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">&nbsp;</th>
					<td>
						<input type="submit" name="create_booking_2" class="button-primary" value="<?php _e( 'Create Booking', 'woocommerce-bookings' ); ?>" />
						<input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer_id ); ?>" />
						<input type="hidden" name="bookable_product_id" value="<?php echo esc_attr( $bookable_product_id ); ?>" />
						<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $bookable_product_id ); ?>" />
						<input type="hidden" name="booking_order" value="<?php echo esc_attr( $booking_order ); ?>" />
						<?php wp_nonce_field( 'create_booking_notification' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</div>