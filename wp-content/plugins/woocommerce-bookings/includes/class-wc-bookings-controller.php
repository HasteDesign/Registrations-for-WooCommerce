<?php
/**
 * Gets bookings
 */
class WC_Bookings_Controller {

	/**
	 * Get latest bookings
	 *
	 * @param int $numberitems Number of objects returned (default to unlimited)
	 * @param int $offset The number of objects to skip (as a query offset)
	 * @return array of WC_Booking objects
	 */
	public static function get_latest_bookings( $numberitems = -1, $offset = 0 ) {
		$booking_ids = get_posts( array(
			'numberposts' => $numberitems,
			'offset'      => $offset,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
			'post_type'   => 'wc_booking',
			'post_status' => array( 'unpaid', 'pending', 'confirmed', 'paid', 'cancelled' ),
			'fields'      => 'ids',
		) );

		$bookings = array();

		foreach ( $booking_ids as $booking_id ) {
			$bookings[] = get_wc_booking( $booking_id );
		}

		return $bookings;
	}

	/**
	 * Return all bookings for a product in a given range
	 * @param  int $product_id
	 * @param  timestamp $start_date
	 * @param  timestamp $end_date
	 * @param  int product_or_resource_id
	 * @return array of bookings
	 */
	public static function get_bookings_in_date_range( $start_date, $end_date , $product_or_resource_id = '' ) {
		$default_args = array(
			'numberposts'      => -1,
			'post_type'        => 'wc_booking',
			'post_status'      => array( 'unpaid', 'pending', 'confirmed', 'paid', 'complete' ),
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => false,
			'meta_query'       => array()
		);

		if ( $product_or_resource_id ) {
			if ( get_post_type( $product_or_resource_id ) === 'bookable_resource' ) {
				$default_args['meta_query'][] = array(
					'key'     => '_booking_resource_id',
					'value'   => $product_or_resource_id
				);
			} else {
				$default_args['meta_query'][] = array(
					'key'     => '_booking_product_id',
					'value'   => $product_or_resource_id
				);
			}
		}

		// NOT ALL DAY
		$args = $default_args;
		$args['meta_query'][] = array(
			'key'     => '_booking_start',
			'value'   => date( 'YmdHis', $end_date ),
			'compare' => '<',
			'type'    => 'NUMERIC'
		);
		$args['meta_query'][] = array(
			'key'     => '_booking_end',
			'value'   => date( 'YmdHis', $start_date ),
			'compare' => '>',
			'type'    => 'NUMERIC'
		);
		$args['meta_query'][] = array(
			'key'     => '_booking_all_day',
			'value'   => '0'
		);

		$booking_ids = get_posts( apply_filters( 'get_bookings_in_date_range_args', $args ) );

		// ALL DAY
		$args = $default_args;
		$args['meta_query'][] = array(
			'key'     => '_booking_start',
			'value'   => date( 'Ymd', $end_date ),
			'compare' => '<=',
			'type'    => 'DATE'
		);
		$args['meta_query'][] = array(
			'key'     => '_booking_end',
			'value'   => date( 'Ymd', $start_date ),
			'compare' => '>=',
			'type'    => 'DATE'
		);
		$args['meta_query'][] = array(
			'key'     => '_booking_all_day',
			'value'   => '1'
		);

		$booking_ids = array_unique( array_merge( $booking_ids, get_posts( apply_filters( 'get_bookings_in_date_range_args', $args ) ) ) );

		// Get objects
		$bookings    = array();

		foreach ( $booking_ids as $booking_id ) {
			$bookings[] = get_wc_booking( $booking_id );
		}

		return $bookings;
	}

	/**
	 * Gets bookings for product ids and resource ids
	 * @param  array  $ids
	 * @param  array  $status
	 * @return array of WC_Booking objects
	 */
	public static function get_bookings_for_objects( $ids = array(), $status = array( 'confirmed', 'paid' ) ) {
		$booking_ids = get_posts( array(
			'numberposts'   => -1,
			'offset'        => 0,
			'orderby'       => 'post_date',
			'order'         => 'DESC',
			'post_type'     => 'wc_booking',
			'post_status'   => $status,
			'fields'        => 'ids',
			'no_found_rows' => true,
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key'     => '_booking_product_id',
					'value'   => array_map( 'absint', $ids ),
					'compare' => 'IN',
				),
				array(
					'key'     => '_booking_resource_id',
					'value'   => array_map( 'absint', $ids ),
					'compare' => 'IN',
				)
			)
		) );

		$bookings    = array();

		foreach ( $booking_ids as $booking_id ) {
			$bookings[] = get_wc_booking( $booking_id );
		}

		return $bookings;
	}

	/**
	 * Gets bookings for a resource
	 * 
	 * @param  int $resource_id ID
	 * @param  array  $status
	 * @return array of WC_Booking objects
	 */
	public static function get_bookings_for_resource( $resource_id, $status = array( 'confirmed', 'paid' ) ) {
		$booking_ids = get_posts( array(
			'numberposts'   => -1,
			'offset'        => 0,
			'orderby'       => 'post_date',
			'order'         => 'DESC',
			'post_type'     => 'wc_booking',
			'post_status'   => $status,
			'fields'        => 'ids',
			'no_found_rows' => true,
			'meta_query' => array(
				array(
					'key'     => '_booking_resource_id',
					'value'   => absint( $resource_id )
				)
			)
		) );

		$bookings    = array();

		foreach ( $booking_ids as $booking_id ) {
			$bookings[] = get_wc_booking( $booking_id );
		}

		return $bookings;
	}

	/**
	 * Gets bookings for a product by ID
	 *
	 * @param int $product_id The id of the product that we want bookings for
	 * @return array of WC_Booking objects
	 */
	public static function get_bookings_for_product( $product_id, $status = array( 'confirmed', 'paid' ) ) {
		$booking_ids = get_posts( array(
			'numberposts'   => -1,
			'offset'        => 0,
			'orderby'       => 'post_date',
			'order'         => 'DESC',
			'post_type'     => 'wc_booking',
			'post_status'   => $status,
			'fields'        => 'ids',
			'no_found_rows' => true,
			'meta_query' => array(
				array(
					'key'     => '_booking_product_id',
					'value'   => absint( $product_id )
				)
			)
		) );

		$bookings    = array();

		foreach ( $booking_ids as $booking_id ) {
			$bookings[] = get_wc_booking( $booking_id );
		}

		return $bookings;
	}

	/**
	 * Gets bookings for a user by ID
	 *
	 * @param int $user_id The id of the user that we want bookings for
	 * @return array of WC_Booking objects
	 */
	public static function get_bookings_for_user( $user_id ) {
		$booking_ids = get_posts( array(
			'numberposts'   => -1,
			'offset'        => 0,
			'orderby'       => 'post_date',
			'order'         => 'DESC',
			'post_type'     => 'wc_booking',
			'post_status'   => array( 'unpaid', 'pending', 'confirmed', 'paid', 'cancelled', 'complete' ),
			'fields'        => 'ids',
			'no_found_rows' => true,
			'meta_query' => array(
				array(
					'key'     => '_booking_customer_id',
					'value'   => absint( $user_id ),
					'compare' => 'IN',
				)
			)
		) );

		$bookings    = array();

		foreach ( $booking_ids as $booking_id ) {
			$bookings[] = get_wc_booking( $booking_id );
		}

		return $bookings;
	}
}