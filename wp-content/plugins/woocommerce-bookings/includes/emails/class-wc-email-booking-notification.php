<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Booking Notifications
 *
 * An email sent manually for bookings.
 *
 * @class 		WC_Email_Booking_Notification
 * @extends 	WC_Email
 */
class WC_Email_Booking_Notification extends WC_Email {

	/**
	 * Constructor
	 */
	function __construct() {

		$this->id 				= 'booking_notification';
		$this->title 			= __( 'Booking Notification', 'woocommerce-bookings' );
		$this->description		= __( 'Booking notification emails are sent manually from WooCommerce > Bookings > Send Notification.', 'woocommerce-bookings' );

		$this->heading 			= ''; // Controlled via form
		$this->subject      	= ''; // Controlled via form

		$this->template_html 	= 'emails/customer-booking-notification.php';
		$this->template_plain 	= 'emails/plain/customer-booking-notification.php';

		// Call parent constructor
		parent::__construct();

		// Other settings
		$this->template_base = WC_BOOKINGS_TEMPLATE_PATH;
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $booking_id, $notification_subject, $notification_message, $attachments = array() ) {
		global $woocommerce;

		if ( $booking_id ) {
			$this->object    = get_wc_booking( $booking_id );
			$this->find[]    = '{product_title}';
			$this->replace[] = $this->object->get_product()->get_title();

			if ( $this->object->get_order() ) {
				$this->find[]    = '{order_date}';
				$this->replace[] = date_i18n( woocommerce_date_format(), strtotime( $this->object->get_order()->order_date ) );

				$this->find[]    = '{order_number}';
				$this->replace[] = $this->object->get_order()->get_order_number();

				$this->find[]    = '{customer_name}';
				$this->replace[] = $this->object->get_order()->billing_first_name . ' ' . $this->object->get_order()->billing_last_name;

				$this->find[]    = '{customer_first_name}';
				$this->replace[] = $this->object->get_order()->billing_first_name;

				$this->find[]    = '{customer_last_name}';
				$this->replace[] = $this->object->get_order()->billing_last_name;

				$this->recipient = $this->object->get_order()->billing_email;
			} else {
				$this->find[]    = '{order_date}';
				$this->replace[] = date_i18n( woocommerce_date_format(), strtotime( $this->object->booking_date ) );

				$this->find[]    = '{order_number}';
				$this->replace[] = __( 'N/A', 'woocommerce-bookings' );

				$this->find[]    = '{customer_name}';
				$this->replace[] = __( 'N/A', 'woocommerce-bookings' );

				$this->find[]    = '{customer_first_name}';
				$this->replace[] = __( 'N/A', 'woocommerce-bookings' );

				$this->find[]    = '{customer_last_name}';
				$this->replace[] = __( 'N/A', 'woocommerce-bookings' );

				if ( $this->object->customer_id && ( $customer = get_user_by( 'id', $this->object->customer_id ) ) ) {
					$this->recipient = $customer->user_email;
				}
			}
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() )
			return;

		$this->heading              = str_replace( $this->find, $this->replace, $notification_subject );
		$this->subject              = str_replace( $this->find, $this->replace, $notification_subject );
		$this->notification_message = str_replace( $this->find, $this->replace, $notification_message );
		$attachments                = apply_filters( 'woocommerce_email_attachments', $attachments, $this->id, $this->object );

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $attachments );
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		ob_start();
		woocommerce_get_template( $this->template_html, array(
			'booking' 		=> $this->object,
			'email_heading' => $this->get_heading(),
			'notification_message' => $this->notification_message
		), 'woocommerce-bookings/', $this->template_base );
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		ob_start();
		woocommerce_get_template( $this->template_plain, array(
			'booking' 		=> $this->object,
			'email_heading' => $this->get_heading(),
			'notification_message' => $this->notification_message
		), 'woocommerce-bookings/', $this->template_base );
		return ob_get_clean();
	}

    /**
     * Initialise Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable/Disable', 'woocommerce-bookings' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable this email notification', 'woocommerce-bookings' ),
				'default' 		=> 'yes'
			),
			'email_type' => array(
				'title' 		=> __( 'Email type', 'woocommerce-bookings' ),
				'type' 			=> 'select',
				'description' 	=> __( 'Choose which format of email to send.', 'woocommerce-bookings' ),
				'default' 		=> 'html',
				'class'			=> 'email_type',
				'options'		=> array(
					'plain'		 	=> __( 'Plain text', 'woocommerce-bookings' ),
					'html' 			=> __( 'HTML', 'woocommerce-bookings' ),
					'multipart' 	=> __( 'Multipart', 'woocommerce-bookings' ),
				)
			)
		);
    }
}

return new WC_Email_Booking_Notification();