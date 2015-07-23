<?php
/**
 * Customer booking confirmed email
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

echo $email_heading . "\n\n";

if ( $booking->get_order() ) {
	echo sprintf( __( 'Hello %s', 'woocommerce-bookings' ), $booking->get_order()->billing_first_name ) . "\n\n";
}

echo __(  'Your booking for has been confirmed. The details of your booking are shown below.', 'woocommerce-bookings' ) . "\n\n";

echo "****************************************************\n\n";

echo sprintf( __( 'Booked: %s', 'woocommerce-bookings'), $booking->get_product()->get_title() ) . "\n";
echo sprintf( __( 'Booking ID: %s', 'woocommerce-bookings'), $booking->get_id() ) . "\n";

if ( $booking->has_resources() && ( $resource = $booking->get_resource() ) ) {
	echo sprintf( __( 'Booking Type: %s', 'woocommerce-bookings'), $resource->post_title ) . "\n";
}

echo sprintf( __( 'Booking Start Date: %s', 'woocommerce-bookings'), $booking->get_start_date() ) . "\n";
echo sprintf( __( 'Booking End Date: %s', 'woocommerce-bookings'), $booking->get_end_date() ) . "\n";

if ( $booking->has_persons() ) {
	foreach ( $booking->get_persons() as $id => $qty ) {
		if ( 0 === $qty ) {
			continue;
		}

		$person_type = ( 0 < $id ) ? get_the_title( $id ) : __( 'Person(s)', 'woocommerce-bookings' );
		echo sprintf( __( '%s: %d', 'woocommerce-bookings'), $person_type, $qty ) . "\n";
	}
}

echo "\n****************************************************\n\n";

if ( $order = $booking->get_order() ) {
	if ( $order->status == 'pending' ) {
		echo sprintf( __( 'To pay for this booking please use the following link: %s', 'woocommerce-bookings' ), $order->get_checkout_payment_url() ) . "\n\n";
	}

	do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text );

	echo sprintf( __( 'Order number: %s', 'woocommerce-bookings'), $order->get_order_number() ) . "\n";
	echo sprintf( __( 'Order date: %s', 'woocommerce-bookings'), date_i18n( wc_date_format(), strtotime( $order->order_date ) ) ) . "\n";

	do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text );

	echo "\n";

	switch ( $order->status ) {
		case "completed" :
			echo $order->email_order_items_table( $order->is_download_permitted(), false, true, '', '', true );
		break;
		case "processing" :
			echo $order->email_order_items_table( $order->is_download_permitted(), true, true, '', '', true );
		break;
		default :
			echo $order->email_order_items_table( $order->is_download_permitted(), true, false, '', '', true );
		break;
	}

	echo "----------\n\n";

	if ( $totals = $order->get_order_item_totals() ) {
		foreach ( $totals as $total ) {
			echo $total['label'] . "\t " . $total['value'] . "\n";
		}
	}

	echo "\n****************************************************\n\n";

	do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text );
}

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );