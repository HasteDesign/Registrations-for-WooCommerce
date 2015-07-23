<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles email sending
 */
class WC_Bookings_Emails {

	/**
	 * Constructor sets up actions
	 */
	public function __construct() {
		global $woocommerce;

		add_filter( 'woocommerce_email_classes', array( $this, 'init_emails' ) );

		// Email Actions
		$email_actions = array( 
			'woocommerce_new_booking', 
			'woocommerce_booking_confirmed', 
			'woocommerce_booking_pending_to_cancelled',
			'woocommerce_booking_confirmed_to_cancelled',
			'woocommerce_booking_paid_to_cancelled'
		);
		
		foreach ( $email_actions as $action ) {
			add_action( $action, array( $woocommerce, 'send_transactional_email'), 10, 10 );
		}

		add_filter( 'woocommerce_email_attachments', array( $this, 'attach_ics_file' ), 10, 3 );
	}

	/**
	 * Include our mail templates
	 *
	 * @param  array $emails
	 * @return array
	 */
	public function init_emails( $emails ) {
		if ( ! isset( $emails['WC_Email_New_Booking'] ) ) {
			$emails['WC_Email_New_Booking'] = include( 'emails/class-wc-email-new-booking.php' );
		}

		if ( ! isset( $emails['WC_Email_Booking_Reminder'] ) ) {
			$emails['WC_Email_Booking_Reminder'] = include( 'emails/class-wc-email-booking-reminder.php' );
		}

		if ( ! isset( $emails['WC_Email_Booking_Confirmed'] ) ) {
			$emails['WC_Email_Booking_Confirmed'] = include( 'emails/class-wc-email-booking-confirmed.php' );
		}

		if ( ! isset( $emails['WC_Email_Booking_Notification'] ) ) {
			$emails['WC_Email_Booking_Notification'] = include( 'emails/class-wc-email-booking-notification.php' );
		}

		if ( ! isset( $emails['WC_Email_Booking_Cancelled'] ) ) {
			$emails['WC_Email_Booking_Cancelled'] = include( 'emails/class-wc-email-booking-cancelled.php' );
		}

		return $emails;
	}

	/**
	 * Attach the .ics files in the emails.
	 *
	 * @param  array  $attachments
	 * @param  string $email_id
	 * @param  mixed  $booking
	 *
	 * @return array
	 */
	public function attach_ics_file( $attachments, $email_id, $booking ) {
		$available = apply_filters( 'woocommerce_bookings_emails_ics', array( 'booking_confirmed', 'booking_reminder' ) );

		if ( in_array( $email_id, $available ) ) {
			$generate = new WC_Bookings_ICS_Exporter;
			$attachments[] = $generate->get_booking_ics( $booking );
		}

		return $attachments;
	}
}

new WC_Bookings_Emails();
