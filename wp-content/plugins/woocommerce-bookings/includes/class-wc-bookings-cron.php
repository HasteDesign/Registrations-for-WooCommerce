<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Cron job handler
 */
class WC_Bookings_Cron {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wc-booking-reminder', array( $this, 'send_booking_reminder' ) );
		add_action( 'wc-booking-complete', array( $this, 'mark_booking_complete' ) );
	}

	/**
	 * Send booking reminder email
	 */
	public function send_booking_reminder( $booking_id ) {
		global $woocommerce;

		$mailer   = $woocommerce->mailer();
		$reminder = $mailer->emails['WC_Email_Booking_Reminder'];
		$reminder ->trigger( $booking_id );
	}

	/**
	 * Change the booking status
	 */
	public function mark_booking_complete( $booking_id ) {
		$booking = get_wc_booking( $booking_id );
		$booking->update_status( 'complete' );
	}
}

new WC_Bookings_Cron();

