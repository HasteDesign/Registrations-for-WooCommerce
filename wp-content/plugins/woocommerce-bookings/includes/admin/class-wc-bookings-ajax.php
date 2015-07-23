<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Booking admin
 */
class WC_Bookings_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_wc-booking-confirm', array( $this, 'mark_booking_confirmed' ) );
		add_action( 'wp_ajax_wc_bookings_calculate_costs', array( $this, 'calculate_costs' ) );
		add_action( 'wp_ajax_nopriv_wc_bookings_calculate_costs', array( $this, 'calculate_costs' ) );
		add_action( 'wp_ajax_wc_bookings_get_blocks', array( $this, 'get_time_blocks_for_date' ) );
		add_action( 'wp_ajax_nopriv_wc_bookings_get_blocks', array( $this, 'get_time_blocks_for_date' ) );
		add_action( 'wp_ajax_wc_bookings_json_search_order', array( $this, 'json_search_order' ) );
	}

	/**
	 * Mark a booking confirmed
	 */
	public function mark_booking_confirmed() {
		if ( ! current_user_can( 'manage_bookings' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-bookings' ) );
		}
		if ( ! check_admin_referer( 'wc-booking-confirm' ) ) {
			wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-bookings' ) );
		}
		$booking_id = isset( $_GET['booking_id'] ) && (int) $_GET['booking_id'] ? (int) $_GET['booking_id'] : '';
		if ( ! $booking_id ) {
			die;
		}

		$booking = get_wc_booking( $booking_id );
    	if ( $booking->get_status() !== 'confirmed' ) {
    		$booking->update_status( 'confirmed' );
    	}

		wp_safe_redirect( wp_get_referer() );
	}

	/**
	 * Calculate costs
	 *
	 * Take posted booking form values and then use these to quote a price for what has been chosen.
	 * Returns a string which is appended to the booking form.
	 */
	public function calculate_costs() {

		$posted = array();

		parse_str( $_POST['form'], $posted );

		$booking_id = $posted['add-to-cart'];
		$product    = get_product( $booking_id );

		if ( ! $product ) {
			die( json_encode( array(
				'result' => 'ERROR',
				'html'   => '<span class="booking-error">' . __( 'This booking is unavailable.', 'woocommerce-bookings' ) . '</span>'
			) ) );
		}

		$booking_form     = new WC_Booking_Form( $product );
		$cost             = $booking_form->calculate_booking_cost( $posted );

		if ( is_wp_error( $cost ) ) {
			die( json_encode( array(
				'result' => 'ERROR',
				'html'   => '<span class="booking-error">' . $cost->get_error_message() . '</span>'
			) ) );
		}

		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		$display_price    = $tax_display_mode == 'incl' ? $product->get_price_including_tax( 1, $cost ) : $product->get_price_excluding_tax( 1, $cost );

		die( json_encode( array(
			'result' => 'SUCCESS',
			'html'   => __( 'Booking cost', 'woocommerce-bookings' ) . ': <strong>' . woocommerce_price( $display_price ) . $product->get_price_suffix() . '</strong>'
		) ) );
	}

	/**
	 * Get a list of time blocks available on a date
	 */
	public function get_time_blocks_for_date() {
		$posted = array();

		parse_str( $_POST['form'], $posted );

		if ( empty( $posted['add-to-cart'] ) ) {
			return false;
		}

		$booking_id   = $posted['add-to-cart'];
		$product      = get_product( $booking_id );
		$booking_form = new WC_Booking_Form( $product );

		if ( ! empty( $posted['wc_bookings_field_start_date_year'] ) && ! empty( $posted['wc_bookings_field_start_date_month'] ) && ! empty( $posted['wc_bookings_field_start_date_day'] ) ) {
			$year      = max( date('Y'), absint( $posted['wc_bookings_field_start_date_year'] ) );
			$month     = absint( $posted['wc_bookings_field_start_date_month'] );
			$day       = absint( $posted['wc_bookings_field_start_date_day'] );
			$timestamp = strtotime( "{$year}-{$month}-{$day}" );
		}

		if ( ! $product || empty( $timestamp ) ) {
			return false;
		}

		if ( ! empty( $posted['wc_bookings_field_duration'] ) ) {
			$interval = $posted['wc_bookings_field_duration'] * $product->wc_booking_duration;
		} else {
			$interval = $product->wc_booking_duration;
		}

		$base_interval = $product->wc_booking_duration;

		if ( 'hour' === $product->get_duration_unit() ) {
			$interval      = $interval * 60;
			$base_interval = $base_interval * 60;
		}

		$first_block_time = $product->wc_booking_first_block_time;
		$from             = $time_from = strtotime( $first_block_time ? $first_block_time : 'midnight', $timestamp );
		$to               = strtotime( "tomorrow midnight", $timestamp );
		$block_html       = '';
		$time_format      = get_option( 'time_format' );
		$blocks           = $product->get_blocks_in_range( $from, $to, $base_interval, ( ! empty( $posted['wc_bookings_field_resource'] ) ? $posted['wc_bookings_field_resource'] : 0 ) );

		if ( $blocks ) {
			foreach ( $blocks as $block ) {

				// Get availability for the block
				if ( $product->has_resources() ) {
					if ( $product->is_resource_assignment_type( 'customer' ) ) {
						if ( ! empty( $posted['wc_bookings_field_resource'] ) && ( $resource = $product->get_resource( absint( $posted['wc_bookings_field_resource'] ) ) ) ) {
							$resource_id = $resource->ID;
						} else {
							$resource_id = 0;
						}
						$available_bookings = $product->get_available_bookings( $block, strtotime( "+{$interval} minutes", $block ), $resource_id, 1 );
					} else {
						// Assign an available resource automatically
						$available_bookings = $product->get_available_bookings( $block, strtotime( "+{$interval} minutes", $block ), 0, 1 );
					}
				} else {
					$available_bookings = $product->get_available_bookings( $block, strtotime( "+{$interval} minutes", $block ), '', 1 );
				}

				if ( is_wp_error( $available_bookings ) ) {
					continue;
				} elseif ( ! $available_bookings ) {
					continue;
				}

				$block_html .= '<li class="block" data-block="' . esc_attr( date( 'Hi', $block ) ) . '"><a href="#" data-value="' . date( 'G:i', $block ) . '">' . date_i18n( $time_format, $block ) . '</a></li>';
			}
		}

		if ( empty( $block_html ) ) {
			$block_html .= '<li>' . __( 'No blocks available.', 'woocommerce-bookings' ) . '</li>';
		}

		die( $block_html );
	}

	/**
	 * Search for customers and return json
	 */
	public function json_search_order() {
		global $wpdb;

		check_ajax_referer( 'search-booking-order', 'security' );

		header( 'Content-Type: application/json; charset=utf-8' );

		$term = wc_clean( stripslashes( $_GET['term'] ) );

		if ( empty( $term ) ) {
			die();
		}

		$found_orders = array();

		$term = apply_filters( 'woocommerce_booking_json_search_order_number', $term );

		$query_orders = $wpdb->get_results( $wpdb->prepare( "
			SELECT ID, post_title FROM {$wpdb->posts} AS posts
			WHERE posts.post_type = 'shop_order'
			AND posts.ID LIKE %s
		", '%' . $term . '%' ) );

		if ( $query_orders ) {
			foreach ( $query_orders as $item ) {
				$order_number = apply_filters( 'woocommerce_order_number', _x( '#', 'hash before order number', 'woocommerce-bookings' ) . $item->ID, $item->ID );
				$found_orders[ $item->ID ] = $order_number . ' &ndash; ' . esc_html( $item->post_title );
			}
		}

		echo json_encode( $found_orders );
		die();
	}
}

new WC_Bookings_Ajax();