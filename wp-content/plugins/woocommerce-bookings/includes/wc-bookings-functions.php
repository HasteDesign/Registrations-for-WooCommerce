<?php

/**
 * Get a booking object
 * @param  int $id
 * @return object
 */
function get_wc_booking( $id ) {
	return new WC_Booking( $id );
}

/**
 * Santiize and format a string into a valid 24 hour time
 * @return string
 */
function wc_booking_sanitize_time( $raw_time ) {
	$time = wc_clean( $raw_time );
	$time = date( 'H:i', strtotime( $time ) );
	return $time;
}

/**
 * Convert key to a nice readable label
 * @param  string $key
 * @return string
 */
function get_wc_booking_data_label( $key, $product ) {
	switch ( $key ) {
		case "type" :
			return $product->wc_booking_resouce_label ? $product->wc_booking_resouce_label : __( 'Booking Type', 'woocommerce-bookings' );
		case "date" :
			return __( 'Booking Date', 'woocommerce-bookings' );
		case "time" :
			return __( 'Booking Time', 'woocommerce-bookings' );
		case "duration" :
			return __( 'Duration', 'woocommerce-bookings' );
		case "persons" :
			return __( 'Person(s)', 'woocommerce-bookings' );
		default :
			return $key;
	}
}

/**
 * Validate and create a new booking manually.
 *
 * @see WC_Booking::new_booking() for available $new_booking_data args
 * @param  int $product_id you are booking
 * @param  array $new_booking_data
 * @param  string $status
 * @param  boolean $exact If false, the function will look for the next available block after your start date if the date is unavailable.
 * @return mixed WC_Booking object on success or false on fail
 */
function create_wc_booking( $product_id, $new_booking_data = array(), $status = 'confirmed', $exact = false ) {
	// Merge booking data
	$defaults = array(
		'product_id'  => $product_id, // Booking ID
		'start_date'  => '',
		'end_date'    => '',
		'resource_id' => '',
	);

	$new_booking_data = wp_parse_args( $new_booking_data, $defaults );
	$product          = get_product( $product_id );
	$start_date       = $new_booking_data['start_date'];
	$end_date         = $new_booking_data['end_date'];
	$max_date         = $product->get_max_date();

	// If not set, use next available
	if ( ! $start_date ) {
		$min_date   = $product->get_min_date();
		$start_date = strtotime( "+{$min_date['value']} {$min_date['unit']}", current_time( 'timestamp' ) );
	}

	// If not set, use next available + block duration
	if ( ! $end_date ) {
		$end_date = strtotime( "+{$product->wc_booking_duration} {$product->wc_booking_duration_unit}", $start_date );
	}

	$searching = true;
	$date_diff = $end_date - $start_date;

	while( $searching ) {

		$available_bookings = $product->get_available_bookings( $start_date, $end_date, $new_booking_data['resource_id'], $data['_qty'] );

		if ( $available_bookings && ! is_wp_error( $available_bookings ) ) {

			if ( ! $new_booking_data['resource_id'] && is_array( $available_bookings ) ) {
				$new_booking_data['resource_id'] = current( array_keys( $available_bookings ) );
			}

			$searching = false;

		} else {
			if ( $exact )
				return false;

			$start_date += $date_diff;
			$end_date   += $date_diff;

			if ( $end_date > strtotime( "+{$max_date['value']} {$max_date['unit']}" ) )
				return false;
		}
	}

	// Set dates
	$new_booking_data['start_date'] = $start_date;
	$new_booking_data['end_date']   = $end_date;

	// Create it
	$new_booking = get_wc_booking( $new_booking_data );
	$new_booking ->create( $status );

	return $new_booking;
}

/**
 * Check if product/booking requires confirmation.
 *
 * @param  int $id Product ID.
 *
 * @return bool
 */
function wc_booking_requires_confirmation( $id ) {
	$product = get_product( $id );

	if (
		is_object( $product )
		&& 'booking' == $product->product_type
		&& $product->requires_confirmation()
	) {
		return true;
	}

	return false;
}

/**
 * Check if the cart has booking that requires confirmation.
 *
 * @return bool
 */
function wc_booking_cart_requires_confirmation() {
	$requires = false;

	foreach ( WC()->cart->cart_contents as $item ) {
		if ( wc_booking_requires_confirmation( $item['product_id'] ) ) {
			$requires = true;
			break;
		}
	}

	return $requires;
}

/**
 * Check if the order has booking that requires confirmation.
 *
 * @param  WC_Order $order
 *
 * @return bool
 */
function wc_booking_order_requires_confirmation( $order ) {
	$requires = false;

	if ( $order ) {
		foreach ( $order->get_items() as $item ) {
			if ( wc_booking_requires_confirmation( $item['product_id'] ) ) {
				$requires = true;
				break;
			}
		}
	}

	return $requires;
}

/**
 * Get timezone string.
 *
 * inspired by https://wordpress.org/plugins/event-organiser/
 *
 * @return string
 */
function wc_booking_get_timezone_string() {
	$timezone = wp_cache_get( 'wc_bookings_timezone_string' );

	if ( false === $timezone ) {
		$timezone   = get_option( 'timezone_string' );
		$gmt_offset = get_option( 'gmt_offset' );

		// Remove old Etc mappings. Fallback to gmt_offset.
		if ( ! empty( $timezone ) && false !== strpos( $timezone, 'Etc/GMT' ) ) {
			$timezone = '';
		}

		if ( empty( $timezone ) && 0 != $gmt_offset ) {
			// Use gmt_offset
			$gmt_offset   *= 3600; // convert hour offset to seconds
			$allowed_zones = timezone_abbreviations_list();

			foreach ( $allowed_zones as $abbr ) {
				foreach ( $abbr as $city ) {
					if ( $city['offset'] == $gmt_offset ) {
						$timezone = $city['timezone_id'];
						break 2;
					}
				}
			}
		}

		// Issue with the timezone selected, set to 'UTC'
		if ( empty( $timezone ) ) {
			$timezone = 'UTC';
		}

		// Cache the timezone string.
		wp_cache_set( 'wc_bookings_timezone_string', $timezone );
	}

	return $timezone;
}

/**
 * Get bookable product resources.
 *
 * @param int $product_id product ID.
 *
 * @return array Resources objects list.
 */
function wc_booking_get_product_resources( $product_id ) {
	global $wpdb;

	$resources = array();
	$posts     = $wpdb->get_results(
		$wpdb->prepare( "
			SELECT posts.ID, posts.post_title
			FROM {$wpdb->prefix}wc_booking_relationships AS relationships
				LEFT JOIN $wpdb->posts AS posts
				ON posts.ID = relationships.resource_id
			WHERE relationships.product_id = %d
			ORDER BY sort_order ASC
		", $product_id )
	);

	foreach ( $posts as $resource ) {
		$resources[] = new WC_Product_Booking_Resource( $resource, $product_id );
	}

	return $resources;
}
