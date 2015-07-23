<?php

class WC_Bookings_Calendar {

	private $bookings;

	/**
	 * Output the calendar view
	 */
	public function output() {
		wp_enqueue_script( 'chosen' );

		$product_filter = isset( $_REQUEST['filter_bookings'] ) ? absint( $_REQUEST['filter_bookings'] ) : '';
		$view           = isset( $_REQUEST['view'] ) && $_REQUEST['view'] == 'day' ? 'day' : 'month';

		if ( $view == 'day' ) {
			$day            = isset( $_REQUEST['calendar_day'] ) ? wc_clean( $_REQUEST['calendar_day'] ) : date( 'Y-m-d' );

			$this->bookings = WC_Bookings_Controller::get_bookings_in_date_range(
				strtotime( 'midnight', strtotime( $day ) ),
				strtotime( 'midnight +1 day', strtotime( $day ) ),
				$product_filter
			);
		} else {
			$month          = isset( $_REQUEST['calendar_month'] ) ? absint( $_REQUEST['calendar_month'] ) : date( 'n' );
			$year           = isset( $_REQUEST['calendar_year'] ) ? absint( $_REQUEST['calendar_year'] ) : date( 'Y' );

			if ( $year < ( date( 'Y' ) - 10 ) || $year > 2100 )
				$year = date( 'Y' );

			if ( $month > 12 ) {
				$month = 1;
				$year ++;
			}

			if ( $month < 1 ) {
				$month = 1;
				$year --;
			}

			$start_week = (int) date( 'W', strtotime( "first day of $year-$month" ) );
			$end_week   = (int) date( 'W', strtotime( "last day of $year-$month" ) );

			if ( $end_week == 1 )
				$end_week = 52;

			$this->bookings = WC_Bookings_Controller::get_bookings_in_date_range(
				strtotime( $year . 'W' . str_pad( $start_week, 2, '0', STR_PAD_LEFT ) ),
				strtotime( '+1 week', strtotime( "last day of $year-$month" ) ),
				$product_filter
			);
		}

		include( 'views/html-calendar-' . $view . '.php' );

		wc_enqueue_js( '$( "select#calendar-bookings-filter" ).chosen();' );
	}

	/**
	 * List bookings for a day
	 *
	 * @param  [type] $day
	 * @param  [type] $month
	 * @param  [type] $year
	 * @return [type]
	 */
	public function list_bookings( $day, $month, $year ) {
		$date_start = strtotime( "$year-$month-$day 00:00" );
		$date_end   = strtotime( "$year-$month-$day 23:59" );

		foreach ( $this->bookings as $booking ) {
			if (
				( $booking->start >= $date_start && $booking->start < $date_end ) ||
				( $booking->start < $date_start && $booking->end > $date_end ) ||
				( $booking->end > $date_start && $booking->end <= $date_end )
				) {
				echo '<li><a href="' . admin_url( 'post.php?post=' . $booking->id . '&action=edit' ) . '">';
					echo '<strong>#' . $booking->id . ' - ';
					if ( $product = $booking->get_product() ) {
						echo $product->get_title();
					}
					echo '</strong>';
					echo '<ul>';
						if ( ( $customer = $booking->get_customer() ) && ! empty( $customer->name ) ) {
							echo '<li>' . __( 'Booked by', 'woocommerce-bookings' ) . ' ' . $customer->name . '</li>';
						}
						echo '<li>';
						if ( $booking->is_all_day() )
							echo __( 'All Day', 'woocommerce-bookings' );
						else
							echo $booking->get_start_date( '', 'g:ia' ) . '&mdash;' . $booking->get_end_date( '', 'g:ia' );
						echo '</li>';
						if ( $resource = $booking->get_resource() )
							echo '<li>' . __( 'Resource #', 'woocommerce-bookings' ) . $resource->ID . ' - ' . $resource->post_title . '</li>';
					echo '</ul></a>';
				echo '</li>';
			}
		}
	}

	/**
	 * List bookings on a day
	 */
	public function list_bookings_for_day() {
		$bookings_by_time = array();
		$all_day_bookings = array();
		$unqiue_ids       = array();

		foreach ( $this->bookings as $booking ) {
			if ( $booking->is_all_day() ) {
				$all_day_bookings[] = $booking;
			} else {
				$start_time = $booking->get_start_date( '', 'Gi' );

				if ( ! isset( $bookings_by_time[ $start_time ] ) )
					$bookings_by_time[ $start_time ] = array();

				$bookings_by_time[ $start_time ][] = $booking;
			}
			$unqiue_ids[] = $booking->product_id . $booking->resource_id;
		}

		ksort( $bookings_by_time );

		$unqiue_ids = array_flip( $unqiue_ids );
		$index      = 0;
		$colours    = array( '#3498db', '#34495e', '#1abc9c', '#2ecc71', '#f1c40f', '#e67e22', '#e74c3c', '#2980b9', '#8e44ad', '#2c3e50', '#16a085', '#27ae60', '#f39c12', '#d35400', '#c0392b' );

		foreach ( $unqiue_ids as $key => $value ) {
			if ( isset( $colours[ $index ] ) )
				$unqiue_ids[ $key ] = $colours[ $index ];
			else
				$unqiue_ids[ $key ] = $this->random_color();

			$index++;
		}

		$column = 0;

		foreach ( $all_day_bookings as $booking ) {
			echo '<li data-tip="' . $this->get_tip( $booking ) . '" style="background: ' . $unqiue_ids[ $booking->product_id . $booking->resource_id ] . '; left:' . 100 * $column . 'px; top: 0; bottom: 0;"><a href="' . admin_url( 'post.php?post=' . $booking->id . '&action=edit' ) . '">#' . $booking->id . '</a></li>';
			$column++;
		}

		$start_column = $column;
		$last_end     = 0;

		foreach ( $bookings_by_time as $bookings ) {
			foreach ( $bookings as $booking ) {

				$start_time = $booking->get_start_date( '', 'Gi' );
				$end_time   = $booking->get_end_date( '', 'Gi' );
				$height     = ( $end_time - $start_time ) / 1.66666667;

				if ( $last_end > $start_time )
					$column++;
				else
					$column = $start_column;

				echo '<li data-tip="' . $this->get_tip( $booking ) . '" style="background: ' . $unqiue_ids[ $booking->product_id . $booking->resource_id ] . '; left:' . 100 * $column . 'px; top: ' . ( $start_time * 60 ) / 100 . 'px; height: ' . $height . 'px;"><a href="' . admin_url( 'post.php?post=' . $booking->id . '&action=edit' ) . '">#' . $booking->id . '</a></li>';

				if ( $end_time > $last_end )
					$last_end = $end_time;
			}
		}
	}

	/**
	 * Get a random colour
	 */
	public function random_color() {
		return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
	}

	/**
	 * Get a tooltip in day view
	 * @param  object $booking
	 * @return string
	 */
	public function get_tip( $booking ) {
		$return = "";

		$return .= '#' . $booking->id . ' - ';
		if ( $product = $booking->get_product() ) {
			$return .= $product->get_title();
		}
		if ( ( $customer = $booking->get_customer() ) && ! empty( $customer->name ) ) {
			$return .= '<br/>' . __( 'Booked by', 'woocommerce-bookings' ) . ' ' . $customer->name;
		}
		if ( $resource = $booking->get_resource() )
			$return .= '<br/>' . __( 'Resource #', 'woocommerce-bookings' ) . $resource->ID . ' - ' . $resource->post_title;

		return esc_attr( $return );
	}

	/**
	 * Filters products for narrowing search
	 */
	public function product_filters() {
		$filters = array();

		$products = WC_Bookings_Admin::get_booking_products();

		foreach ( $products as $product ) {
			$filters[ $product->ID ] = $product->post_title;

			$resources = wc_booking_get_product_resources( $product->ID );

			foreach ( $resources as $resource ) {
				$filters[ $resource->ID ] = '&nbsp;&nbsp;&nbsp;' . $resource->post_title;
			}
		}

		return $filters;
	}

	/**
	 * Filters resources for narrowing search
	 */
	public function resources_filters() {
		$filters = array();

		$resources = WC_Bookings_Admin::get_booking_resources();

		foreach ( $resources as $resource ) {
			$filters[ $resource->ID ] = $resource->post_title;
		}

		return $filters;
	}

}