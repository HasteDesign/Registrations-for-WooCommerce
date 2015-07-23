<?php
/**
 * My Bookings
 *
 * Shows bookings on the account page
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<h2><?php echo apply_filters( 'woocommerce_my_account_bookings_title', __( 'My bookings', 'woocommerce-bookings' ) ); ?></h2>

<table class="shop_table my_account_bookings">
	<thead>
		<tr>
			<th scope="col"><?php _e( 'ID', 'woocommerce-bookings' ); ?></th>
			<th scope="col"><?php _e( 'Booked', 'woocommerce-bookings' ); ?></th>
			<th scope="col"><?php _e( 'Order', 'woocommerce-bookings' ); ?></th>
			<th scope="col"><?php _e( 'Start Date', 'woocommerce-bookings' ); ?></th>
			<th scope="col"><?php _e( 'End Date', 'woocommerce-bookings' ); ?></th>
			<th scope="col"><?php _e( 'Status', 'woocommerce-bookings' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $bookings as $booking ) : ?>
			<tr>
				<td><?php echo $booking->get_id(); ?></td>
				<td>
					<?php if ( $booking->get_product() ) : ?>
					<a href="<?php echo get_permalink( $booking->get_product()->id ); ?>">
						<?php echo $booking->get_product()->get_title(); ?>
					</a>
					<?php endif; ?>
				</td>
				<td>
					<?php if ( $booking->get_order() ) : ?>
					<a href="<?php echo $booking->get_order()->get_view_order_url(); ?>">
						<?php echo $booking->get_order()->get_order_number(); ?>
					</a>
					<?php endif; ?>
				</td>
				<td><?php echo $booking->get_start_date(); ?></td>
				<td><?php echo $booking->get_end_date(); ?></td>
				<td><?php echo $booking->get_status( false ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>