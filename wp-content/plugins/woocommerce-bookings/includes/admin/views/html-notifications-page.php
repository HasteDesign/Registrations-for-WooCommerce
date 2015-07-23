<div class="wrap woocommerce">
	<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br /></div>
	<h2><?php _e( 'Send Notification', 'woocommerce-bookings' ); ?></h2>

	<p><?php _e( 'You may send an email notification to all customers who have a <strong>future</strong> booking for a particular product. This will use the default template specified under <code>WooCommerce > Settings > Emails</code>.', 'woocommerce-bookings' ); ?></p>

	<form method="POST">
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="notification_product_id"><?php _e( 'Booking Product', 'woocommerce-bookings' ); ?></label>
					</th>
					<td>
						<select id="notification_product_id" name="notification_product_id">
							<option value=""><?php _e( 'Select a booking product...', 'woocommerce-bookings' ); ?></option>
							<?php foreach ( $booking_products as $product ) : ?>
								<option value="<?php echo $product->ID; ?>"><?php echo $product->post_title; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="notification_subject"><?php _e( 'Subject', 'woocommerce-bookings' ); ?></label>
					</th>
					<td>
						<input type="text" placeholder="<?php _e( 'Email subject', 'woocommerce-bookings' ); ?>" name="notification_subject" id="notification_subject" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="notification_message"><?php _e( 'Message', 'woocommerce-bookings' ); ?></label>
					</th>
					<td>
						<textarea id="notification_message" name="notification_message" class="large-text code" placeholder="<?php _e( 'The message you wish to send', 'woocommerce-bookings' ); ?>"></textarea>
						<span class="description"><?php _e( 'The following tags can be inserted in your message/subject and will be replaced dynamically' , 'woocommerce-bookings' ); ?>: <code>{product_title} {order_date} {order_number} {customer_name} {customer_first_name} {customer_last_name}</code></span>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<?php _e( 'Attachment', 'woocommerce-bookings' ); ?>
					</th>
					<td>
						<label><input type="checkbox" name="notification_ics" id="notification_ics" /> <?php _e( 'Attach <code>.ics</code> file', 'woocommerce-bookings' ); ?></label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">&nbsp;</th>
					<td>
						<input type="submit" name="send" class="button-primary" value="<?php _e( 'Send Notification', 'woocommerce-bookings' ); ?>" />
						<?php wp_nonce_field( 'send_booking_notification' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</div>