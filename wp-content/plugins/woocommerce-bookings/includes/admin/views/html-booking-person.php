<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="woocommerce_booking_person wc-metabox closed">
	<h3>
		<button type="button" class="remove_booking_person button" rel="<?php echo esc_attr( $person_type_id ); ?>"><?php _e( 'Remove', 'woocommerce-bookings' ); ?></button>
		<div class="handlediv" title="<?php _e( 'Click to toggle', 'woocommerce-bookings' ); ?>"></div>

		<strong>#<?php echo esc_html( $person_type_id ); ?> &mdash; <span class="person_name"><?php echo $person_type->post_title; ?></span></strong>

		<input type="hidden" name="person_id[<?php echo $loop; ?>]" value="<?php echo esc_attr( $person_type_id ); ?>" />
		<input type="hidden" class="person_menu_order" name="person_menu_order[<?php echo $loop; ?>]" value="<?php echo $loop; ?>" />
	</h3>
	<table cellpadding="0" cellspacing="0" class="wc-metabox-content">
		<tbody>
			<tr>
				<td>
					<label><?php _e( 'Person Type Name', 'woocommerce-bookings' ); ?>:</label>
					<input type="text" class="short person_name" name="person_name[<?php echo $loop; ?>]" value="<?php echo esc_attr( $person_type->post_title ); ?>" placeholder="<?php _e( 'Person Type #', 'woocommerce-bookings' ) . $loop; ?>" />
				</td>
				<td>
					<label><?php _e( 'Base Cost', 'woocommerce-bookings' ); ?>:</label>
					<input type="number" class="short" name="person_cost[<?php echo $loop; ?>]" value="<?php echo esc_attr( $person_type->cost ); ?>" placeholder="0.00" step="0.01" />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<label><?php _e( 'Description', 'woocommerce-bookings' ); ?>:</label>
					<input type="text" class="person_description" name="person_description[<?php echo $loop; ?>]" value="<?php echo esc_attr( $person_type->post_excerpt ); ?>" />
				</td>
			</tr>
			<tr>
				<td>
					<label><?php _e( 'Min', 'woocommerce-bookings' ); ?>:</label>
					<input type="number" class="short" name="person_min[<?php echo $loop; ?>]" value="<?php echo esc_attr( $person_type->min ); ?>" min="0" />
				</td>
				<td>
					<label><?php _e( 'Max', 'woocommerce-bookings' ); ?>:</label>
					<input type="number" class="short" name="person_max[<?php echo $loop; ?>]" value="<?php echo esc_attr( $person_type->max ); ?>" min="0" />
				</td>
			</tr>
		</tbody>
	</table>
</div>
