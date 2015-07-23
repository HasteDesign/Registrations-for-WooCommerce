<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * WC_Bookings_Menus
 */
class WC_Bookings_Menus {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'current_screen', array( $this, 'buffer' ) );
		add_filter( 'woocommerce_screen_ids', array( $this, 'woocommerce_screen_ids' ) );
		add_action( 'admin_menu', array( $this, 'remove_default_add_booking_url' ), 10 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 49 );
		add_filter( 'menu_order', array( $this, 'menu_order' ), 20 );
		add_filter( 'admin_url', array( $this, 'add_new_booking_url' ), 10, 2 );
	}

	/**
	 * output buffer
	 */
	public function buffer() {
		$screen = get_current_screen();
		if ( $screen->id == 'wc_booking_page_create_booking' )
			ob_start();
	}

	/**
	 * Screen IDS
	 * @param  array  $ids
	 * @return array
	 */
	public function woocommerce_screen_ids( $ids ) {
		return array_merge( $ids, array(
			'edit-wc_booking',
			'edit-bookable_resource',
			'bookable_resource',
			'wc_booking',
			'wc_booking_page_booking_calendar',
			'wc_booking_page_booking_notification',
			'wc_booking_page_create_booking',
			'wc_booking_page_wc_bookings_settings',
		) );
	}

	/**
	 * Removes the default add new booking link from the main admin menu
	 */
	public function remove_default_add_booking_url() {
		global $submenu;

		if ( isset( $submenu['edit.php?post_type=wc_booking'] ) ) {
			foreach ( $submenu['edit.php?post_type=wc_booking'] as $key => $value ) {
				if ( 'post-new.php?post_type=wc_booking' == $value[2] ) {
					unset( $submenu['edit.php?post_type=wc_booking'][ $key ] );
					return;
				}
			}
		}
	}

	/**
	 * Add a submenu for managing bookings pages
	 */
	public function admin_menu() {
		$create_booking_page = add_submenu_page( 'edit.php?post_type=wc_booking', __( 'Create Booking', 'woocommerce-bookings' ), __( 'Create Booking', 'woocommerce-bookings' ), 'manage_bookings', 'create_booking', array( $this, 'create_booking_page' ) );
		$calendar_page       = add_submenu_page( 'edit.php?post_type=wc_booking', __( 'Calendar', 'woocommerce-bookings' ), __( 'Calendar', 'woocommerce-bookings' ), 'manage_bookings', 'booking_calendar', array( $this, 'calendar_page' ) );
		$notification_page   = add_submenu_page( 'edit.php?post_type=wc_booking', __( 'Send Notification', 'woocommerce-bookings' ), __( 'Send Notification', 'woocommerce-bookings' ), 'manage_bookings', 'booking_notification', array( $this, 'notifications_page' ) );
		$settings_page       = add_submenu_page( 'edit.php?post_type=wc_booking', __( 'WC Bookings Settings', 'woocommerce-bookings' ), __( 'Settings', 'woocommerce-bookings' ), 'manage_woocommerce', 'wc_bookings_settings', array( $this, 'settings_page' ) );

		// Add action for screen options on this new page
		add_action( 'admin_print_scripts-' . $create_booking_page, array( $this, 'create_booking_page_scripts' ) );
		add_action( 'admin_print_scripts-' . $calendar_page, array( $this, 'calendar_page_scripts' ) );
	}

	/**
	 * Create booking scripts
	 */
	public function create_booking_page_scripts() {
		global $wc_bookings;
		$wc_bookings->booking_form_styles();
	}

	/**
	 * Create booking page
	 */
	public function create_booking_page() {
		require_once( 'class-wc-bookings-create.php' );
		$page = new WC_Bookings_Create();
		$page->output();
	}

	/**
	 * calendar_page_scripts
	 */
	public function calendar_page_scripts() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
	}

	/**
	 * Output the calendar page
	 */
	public function calendar_page() {
		require_once( 'class-wc-bookings-calendar.php' );
		$page = new WC_Bookings_Calendar();
		$page->output();
	}

	/**
	 * Provides an email notification form
	 */
	public function notifications_page() {
		global $woocommerce;

		if ( ! empty( $_POST ) && check_admin_referer( 'send_booking_notification' ) ) {
			$notification_product_id = absint( $_POST['notification_product_id'] );
			$notification_subject    = wc_clean( stripslashes( $_POST['notification_subject'] ) );
			$notification_message    = wp_kses_post( stripslashes( $_POST['notification_message'] ) );

			try {

				if ( ! $notification_product_id )
					throw new Exception( __( 'Please choose a product', 'woocommerce-bookings' ) );

				if ( ! $notification_message )
					throw new Exception( __( 'Please enter a message', 'woocommerce-bookings' ) );

				if ( ! $notification_subject )
					throw new Exception( __( 'Please enter a subject', 'woocommerce-bookings' ) );

				$bookings            = WC_Bookings_Controller::get_bookings_for_product( $notification_product_id );
				$mailer              = $woocommerce->mailer();
				$notification        = $mailer->emails['WC_Email_Booking_Notification'];
				$attachments         = array();

				foreach ( $bookings as $booking ) {
					// Add .ics file
					if ( isset( $_POST['notification_ics'] ) ) {
						$generate = new WC_Bookings_ICS_Exporter;
						$attachments[] = $generate->get_booking_ics( $booking );
					}

					$notification->trigger( $booking->id, $notification_subject, $notification_message, $attachments );
				}

				echo '<div class="updated fade"><p>' . __( 'Notification sent successfully', 'woocommerce-bookings' ) . '</p></div>';

			} catch( Exception $e ) {
				echo '<div class="error"><p>' . $e->getMessage() . '</p></div>';
			}
		}

		$booking_products = WC_Bookings_Admin::get_booking_products();

		include( 'views/html-notifications-page.php' );
	}

	/**
	 * Output the settings page
	 */
	public function settings_page() {
		global $wpdb;

		wp_enqueue_script( 'wc_bookings_writepanel_js' );
		wp_enqueue_script( 'wc_bookings_settings_js' );

		$current_tab = isset( $_GET['tab'] ) ? sanitize_title( $_GET['tab'] ) : 'availability';

		include( 'views/html-settings-page.php' );
	}

	/**
	 * Reorder the WC menu items in admin.
	 *
	 * @param mixed $menu_order
	 * @return array
	 */
	public function menu_order( $menu_order ) {
		// Initialize our custom order array
		$new_menu_order = array();

		// Get index of product menu
		$booking_menu = array_search( 'edit.php?post_type=wc_booking', $menu_order );

		// Loop through menu order and do some rearranging
		foreach ( $menu_order as $index => $item ) :
			if ( ( ( 'edit.php?post_type=product' ) == $item ) ) :
				$new_menu_order[] = $item;
				$new_menu_order[] = 'edit.php?post_type=wc_booking';
				unset( $menu_order[ $booking_menu ] );
			else :
				$new_menu_order[] = $item;
			endif;
		endforeach;

		// Return order
		return $new_menu_order;
	}

	/**
	 * Filters the add new booking url to point to our custom page
	 * @param string $url original url
	 * @param string $path requested path that we can match against
	 * @return string new url
	 */
	public function add_new_booking_url( $url, $path ) {
		if ( 'post-new.php?post_type=wc_booking' == $path ) {
			return admin_url( 'edit.php?post_type=wc_booking&page=create_booking' );
		}

		return $url;
	}
}

new WC_Bookings_Menus();