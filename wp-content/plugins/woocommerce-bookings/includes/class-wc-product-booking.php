<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( 'class-wc-product-booking-resource.php' );

/**
 * Class for the booking product type
 */
class WC_Product_Booking extends WC_Product {
	private $availability_rules = array();

	/**
	 * Constructor
	 */
	public function __construct( $product ) {
		$this->product_type = 'booking';
		parent::__construct( $product );
	}

	/**
	 * We want to sell bookings one at a time
	 * @return boolean
	 */
	public function is_sold_individually() {
		return true;
	}

	/**
	 * Bookings can always be purchased regardless of price.
	 * @return boolean
	 */
	public function is_purchasable() {
		return true;
	}

	/**
	 * Get tje qty available to book per block.
	 * @return boolean
	 */
	public function get_qty() {
		return $this->wc_booking_qty ? absint( $this->wc_booking_qty ) : 1;
	}

	/**
	 * See if this booking product has persons enabled.
	 * @return boolean
	 */
	public function has_persons() {
		return $this->wc_booking_has_persons === 'yes';
	}

	/**
	 * See if this booking product has person types enabled.
	 * @return boolean
	 */
	public function has_person_types() {
		return $this->wc_booking_has_person_types === 'yes';
	}

	/**
	 * See if persons affect the booked qty
	 * @return boolean
	 */
	public function has_person_qty_multiplier() {
		return $this->has_persons() && $this->wc_booking_person_qty_multiplier === 'yes';
	}

	/**
	 * Get persons allowed per group
	 * @return int
	 */
	public function get_min_persons() {
		return absint( $this->wc_booking_min_persons_group );
	}

	/**
	 * Get persons allowed per group
	 * @return int
	 */
	public function get_max_persons() {
		return absint( $this->wc_booking_max_persons_group );
	}

	/**
	 * See if this booking product has reasources enabled.
	 * @return boolean
	 */
	public function has_resources() {
		return $this->wc_booking_has_resources == 'yes';
	}

	/**
	 * The base cost will either be the 'base' cost or the base cost + cheapest resource
	 * @return string
	 */
	public function get_base_cost() {
		$base = ( $this->wc_booking_base_cost * $this->get_min_duration() ) + $this->wc_booking_cost;

		if ( $this->has_resources() ) {
			$resources = $this->get_resources();
			$cheapest  = null;

			foreach ( $resources as $resource ) {
				if ( is_null( $cheapest ) || ( $resource->get_base_cost() + $resource->get_block_cost() ) < $cheapest ) {
					$cheapest = $resource->get_base_cost() + $resource->get_block_cost();
				}
			}
			$base += $cheapest;
		}
		if ( $this->has_persons() && $this->has_person_types() ) {
			$persons = $this->get_person_types();
			$cheapest  = null;
			foreach ( $persons as $person ) {
				$cost = $person->cost * $person->min;
				if ( is_null( $cheapest ) || $cost < $cheapest ) {
					if ( $cost ) {
						$cheapest = $cost;
					}
				}
			}
			$base += $cheapest ? $cheapest : 0;
		}

		return $base;
	}

	/**
	 * Return if booking has extra costs
	 * @return bool
	 */
	public function has_additional_costs() {
		$has_additional_costs = $this->has_additional_costs === 'yes';

		if ( $this->has_persons() && $this->wc_booking_person_cost_multiplier === 'yes' ) {
			$has_additional_costs = true;
		}

		if ( $this->get_min_duration() > 1 && $this->wc_booking_base_cost ) {
			$has_additional_costs = true;
		}

		return $has_additional_costs;
	}

	/**
	 * get duration
	 * @return string
	 */
	public function get_duration() {
		return $this->wc_booking_duration;
	}

	/**
	 * get duration
	 * @return string
	 */
	public function get_duration_unit() {
		return $this->wc_booking_duration_unit;
	}

	/**
	 * get duration type
	 * @return string
	 */
	public function is_duration_type( $type ) {
		return $this->wc_booking_duration_type === $type;
	}

	/**
	 * Get duration setting
	 * @return int
	 */
	public function get_min_duration() {
		return absint( $this->wc_booking_min_duration );
	}

	/**
	 * Get duration setting
	 * @return int
	 */
	public function get_max_duration() {
		return absint( $this->wc_booking_max_duration );
	}

	/**
	 * Get product price
	 * @return string
	 */
	public function get_price() {
		if ( $this->price ) {
			return apply_filters( 'woocommerce_get_price', $this->price, $this );
		} else {
			return apply_filters( 'woocommerce_get_price', $this->get_base_cost(), $this );
		}
	}

	/**
	 * Get price HTML
	 * @return string
	 */
	public function get_price_html( $price = '' ) {
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		$display_price    = $tax_display_mode == 'incl' ? $this->get_price_including_tax( 1, $this->get_base_cost() ) : $this->get_price_excluding_tax( 1, $this->get_base_cost() );

		if ( $display_price ) {
			if ( $this->has_additional_costs() ) {
				$price_html = sprintf( __( 'From: %s', 'woocommerce-bookings' ), woocommerce_price( $display_price ) ) . $this->get_price_suffix();
			} else {
				$price_html = woocommerce_price( $display_price ) . $this->get_price_suffix();
			}
		} elseif ( ! $this->has_additional_costs() ) {
			$price_html = __( 'Free', 'woocommerce-bookings' );
		} else {
			$price_html = '';
		}
		return apply_filters( 'woocommerce_get_price_html', $price_html, $this );
	}

	/**
	 * Get Min date
	 * @return array
	 */
	public function get_min_date() {
		$min_date['value'] = ! empty( $this->wc_booking_min_date ) ? absint( $this->wc_booking_min_date ) : 0;
		$min_date['unit']  = ! empty( $this->wc_booking_min_date_unit ) ? $this->wc_booking_min_date_unit : 'month';
		return $min_date;
	}

	/**
	 * Get max date
	 * @return array
	 */
	public function get_max_date() {
		$max_date['value'] = ! empty( $this->wc_booking_max_date ) ? absint( $this->wc_booking_max_date ) : 1;
		$max_date['unit']  = ! empty( $this->wc_booking_max_date_unit ) ? $this->wc_booking_max_date_unit : 'month';
		return $max_date;
	}

	/**
	 * Get max year
	 * @return string
	 */
	private function get_max_year() {
		// Find max to get first
		$max_date = $this->get_max_date();
		$max_date_timestamp = strtotime( "+{$max_date['value']} {$max_date['unit']}" );
		$max_year = date( 'Y', $max_date_timestamp );
		if ( ! $max_year ) {
			$max_year = date( 'Y' );
		}
		return $max_year;
	}

	/**
	 * Get person type by ID
	 * @param  int $id
	 * @return WP_POST object
	 */
	public function get_person( $id ) {
		$id = absint( $id );

		if ( $id ) {
			$person = get_post( $id );

			if ( 'bookable_person' == $person->post_type && $person->post_parent == $this->id ) {
				return $person;
			}
		}

		return false;
	}

	/**
	 * Get all person types
	 * @return array of WP_Post objects
	 */
	public function get_person_types() {
		$persons = get_posts( array(
			'post_parent'    => $this->id,
			'post_type'      => 'bookable_person',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'asc'
		) );

		return $persons;
	}

	/**
	 * Get resource by ID
	 * @param  int $id
	 * @return WC_Product_Booking_Resource object
	 */
	public function get_resource( $id ) {
		global $wpdb;

		$id = absint( $id );

		if ( $id ) {
			$resource = get_post( $id );
			$relationship_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}wc_booking_relationships WHERE product_id = %d AND resource_id = %d", $this->id, $id ) );

			if ( is_object( $resource ) && $resource->post_type == 'bookable_resource' && 0 < $relationship_id ) {
				return new WC_Product_Booking_Resource( $resource, $this->id );
			}
		}

		return false;
	}

	/**
	 * How resources are assigned
	 * @return string customer or automatic
	 */
	public function is_resource_assignment_type( $type ) {
		return $this->wc_booking_resources_assignment === $type;
	}

	/**
	 * Get all resources
	 * @return array of WP_Post objects
	 */
	public function get_resources() {
		$resources = wc_booking_get_product_resources( $this->id );

		return $resources;
	}

	/**
	 * Get an array of blocks within in a specified date range - might be days, might be blocks within days, depending on settings.
	 * @return array
	 */
	public function get_blocks_in_range( $start_date, $end_date, $interval = null, $resource_id = 0 ) {
		$blocks = array();

		// For day, minute and hour blocks we need to loop through each day in the range
		if ( in_array( $this->get_duration_unit(), array( 'day', 'minute', 'hour' ) ) ) {
			$check_date = $start_date;

			while ( $check_date < $end_date ) {
				if ( in_array( $this->get_duration_unit(), array( 'day' ) ) && ! $this->check_availability_rules_against_date( $check_date, $resource_id ) ) {
					$check_date = strtotime( "+1 day", $check_date );
					continue;
				}

				// For mins and hours find valid blocks within THIS DAY ($check_date)
				if ( in_array( $this->get_duration_unit(), array( 'minute', 'hour' ) ) ) {

					if ( empty( $interval ) ) {
						$interval = 'hour' === $this->get_duration_unit() ? $this->wc_booking_duration * 60 : $this->wc_booking_duration;
					}

					$first_block_time_minute = $this->wc_booking_first_block_time ? ( date( 'H', strtotime( $this->wc_booking_first_block_time ) ) * 60 ) + date( 'i', strtotime( $this->wc_booking_first_block_time ) ) : 0;

					// Min date
					if ( $min = $this->get_min_date() ) {
						$min_date = strtotime( "+{$min['value']} {$min['unit']}", current_time( 'timestamp' ) );
					} else {
						$min_date = current_time( 'timestamp' );
					}

					// Work out what minutes are actually bookable on this day
					$bookable_minutes = $this->get_default_availability() ? range( $first_block_time_minute, 1440 ) : array();
					$rules            = $this->get_availability_rules( $resource_id );

					// Since we evaluate all time rules and don't break out when one matches, reverse the array
					$rules            = array_reverse( $rules );

					foreach ( $rules as $rule ) {
						$type  = $rule[0];
						$rules = $rule[1];

						if ( strrpos( $type, 'time' ) === 0 ) {
							if ( ! empty( $rules['day'] ) ) {
								if ( $rules['day'] != date( 'N', $check_date ) ) {
									continue;
								}
							}
							$from_hour    = absint( date( 'H', strtotime( $rules['from'] ) ) );
							$from_min     = absint( date( 'i', strtotime( $rules['from'] ) ) );
							$to_hour      = absint( date( 'H', strtotime( $rules['to'] ) ) );
							$to_min       = absint( date( 'i', strtotime( $rules['to'] ) ) );
							$minute_range = array( ( $from_hour * 60 ) + $from_min, ( $to_hour * 60 ) + $to_min );
							$merge_ranges = array();

							if ( $minute_range[0] > $minute_range[1] ) {
								$merge_ranges[] = array( $minute_range[0], 1440 );
								$merge_ranges[] = array( 0, $minute_range[1] );
							} else {
								$merge_ranges[] = array( $minute_range[0], $minute_range[1] );
							}

							foreach ( $merge_ranges as $range ) {
								if ( $bookable = $rules['rule'] ) {
									// If this time range is bookable, add to bookable minutes
									$bookable_minutes = array_merge( $bookable_minutes, range( $range[0], $range[1] ) );
								} else {
									// If this time range is not bookable, remove from bookable minutes
									$bookable_minutes = array_diff( $bookable_minutes, range( $range[0] + 1, $range[1] - 1 ) );
								}
							}
						}
					}

					$bookable_minutes = array_unique( $bookable_minutes );
					sort( $bookable_minutes );

					// Break bookable minutes into sequences - bookings cannot have breaks
					$bookable_minute_blocks     = array();
					$bookable_minute_block_from = current( $bookable_minutes );

					foreach ( $bookable_minutes as $key => $minute ) {
						if ( isset( $bookable_minutes[ $key + 1 ] ) ) {
							if ( $bookable_minutes[ $key + 1 ] - 1 === $minute ) {
								continue;
							} else {
								// There was a break in the sequence
								$bookable_minute_blocks[]   = array( $bookable_minute_block_from, $minute );
								$bookable_minute_block_from = $bookable_minutes[ $key + 1 ];
							}
						} else {
							// We're at the end of the bookable minutes
							$bookable_minute_blocks[] = array( $bookable_minute_block_from, $minute );
						}
					}

					// Loop the blocks of bookable minutes and add a block if there is enough room to book
					foreach ( $bookable_minute_blocks as $time_block ) {
						$time_block_start   = strtotime( "midnight +{$time_block[0]} minutes", $check_date );
						$minutes_in_block   = $time_block[1] - $time_block[0];
						$intervals_in_block = floor( $minutes_in_block / $interval );

						for ( $i = 0; $i < $intervals_in_block; $i ++ ) {
							$from_interval = $i * $interval;
							$start_time    = strtotime( "+{$from_interval} minutes", $time_block_start );
							if ( $start_time >= $end_date ) {
								break 2;
							}
							if ( $start_time > $min_date ) {
								$blocks[] = $start_time;
							}
						}
					}

				// For days, the day is the block
				} else {
					$blocks[] = $check_date;
				}

				// Check next day
				$check_date = strtotime( "+1 day", $check_date );
			}

		// For months, loop each month in the range to find blocks
		} elseif ( 'month' === $this->get_duration_unit() ) {

			// Generate a range of blocks for months
			$from       = strtotime( date( 'Y-m-01', $start_date ) );
			$to         = strtotime( date( 'Y-m-t', $end_date ) );
			$month_diff = 0;
			$month_from = $from;

			while ( ( $month_from = strtotime( "+1 MONTH", $month_from ) ) <= $to ) {
			    $month_diff ++;
			}

			for ( $i = 0; $i <= $month_diff; $i ++ ) {
				$year  = date( 'Y', ( $i ? strtotime( "+ {$i} month", $from ) : $from ) );
				$month = date( 'n', ( $i ? strtotime( "+ {$i} month", $from ) : $from ) );

				if ( ! $this->check_availability_rules_against_date( strtotime( "{$year}-{$month}-01" ), $resource_id ) ) {
					continue;
				}

				$blocks[] = strtotime( "+ {$i} month", $from );
			}

		}
		return $blocks;
	}

	/**
	 * Return an array of resources which can be booked for a defined start/end date
	 * @param  string $start_date
	 * @param  string $end_date
	 * @param  int $resource_id
	 * @param  integer $qty being booked
	 * @return bool|WP_ERROR if no blocks available, or int count of bookings that can be made, or array of available resources
	 */
	public function get_available_bookings( $start_date, $end_date, $resource_id = '', $qty = 1 ) {
		// Check the date is not in the past
		if ( date( 'Ymd', $start_date ) < date( 'Ymd', current_time( 'timestamp' ) ) ) {
			return false;
		}

		// Check we have a resource if needed
		$booking_resource = $resource_id ? $this->get_resource( $resource_id ) : null;

		if ( $this->has_resources() && ! is_numeric( $resource_id ) ) {
			return false;
		}

		$min_date   = $this->get_min_date();
		$max_date   = $this->get_max_date();
		$check_from = strtotime( "midnight +{$min_date['value']} {$min_date['unit']}", current_time('timestamp') );
		$check_to   = strtotime( "+{$max_date['value']} {$max_date['unit']}", current_time('timestamp') );

		// Min max checks
		if ( 'month' === $this->get_duration_unit() ) {
			$check_to = strtotime( 'midnight', strtotime( date( 'Y-m-t', $check_to ) ) );
		}
		if ( $end_date < $check_from || $start_date > $check_to ) {
			return false;
		}

		// Get availability of each resource - no resource has been chosen yet
		if ( $this->has_resources() && ! $resource_id ) {
			$resources           = $this->get_resources();
			$available_resources = array();

			foreach ( $resources as $resource ) {
				$availability = $this->get_available_bookings( $start_date, $end_date, $resource->ID, $qty );

				if ( $availability && ! is_wp_error( $availability ) ) {
					$available_resources[ $resource->ID ] = $availability;
				}
			}

			if ( empty( $available_resources ) ) {
				return new WP_Error( 'Error', __( 'This block cannot be booked.', 'woocommerce-bookings' ) );
			}

			return $available_resources;

		// If we are checking for bookings for a specific resource, or have none...
		} else {
			$available_qtys = array();
			$check_date     = $start_date;

			while ( $check_date < $end_date ) {
				if ( ! $this->check_availability_rules_against_date( $check_date, $resource_id ) ) {
					return false;
				}
				if ( 'start' === $this->wc_booking_check_availability_against ) {
					break; // Only need to check first day
				}
				$check_date = strtotime( "+1 day", $check_date );
			}

			if ( in_array( $this->get_duration_unit(), array( 'minute', 'hour' ) ) && ! $this->check_availability_rules_against_time( $start_date, $end_date, $resource_id ) ) {
				return false;
			}

			$blocks = $this->get_blocks_in_range( $start_date, $end_date, '', $resource_id );

			if ( ! $blocks ) {
				return false;
			}

			/**
			 * Grab all existing bookings for the date range
			 * @var array
			 */
			if ( $this->has_resources() && $resource_id ) {
				$existing_bookings = WC_Bookings_Controller::get_bookings_in_date_range( $start_date, $end_date, $resource_id );
			} else {
				$existing_bookings = WC_Bookings_Controller::get_bookings_in_date_range( $start_date, $end_date, $this->id );
			}

			foreach ( $blocks as $block ) {
				$available_qty       = $this->has_resources() && $booking_resource->has_qty() ? $booking_resource->get_qty() : $this->get_qty();
				$qty_booked_in_block = 0;

				foreach ( $existing_bookings as $existing_booking ) {
					if ( $existing_booking->is_booked_on_day( $block ) ) {
						$qty_to_add = $this->has_person_qty_multiplier() ? max( 1, array_sum( $existing_booking->get_persons() ) ) : 1;
						if ( $this->has_resources() ) {
							if ( $existing_booking->get_resource_id() === absint( $resource_id ) || ( ! $booking_resource->has_qty() && $existing_booking->get_resource() && ! $existing_booking->get_resource()->has_qty() ) ) {
								$qty_booked_in_block += $qty_to_add;
							}
						} else {
							$qty_booked_in_block += $qty_to_add;
						}
					}
				}

				$available_qty = $available_qty - $qty_booked_in_block;

				if ( $available_qty < $qty ) {
					if ( in_array( $this->get_duration_unit(), array( 'hour', 'minute' ) ) ) {
						return new WP_Error( 'Error', sprintf(
							_n( 'There is %d place remaining', 'There are %d places remaining', $available_qty , 'woocommerce-bookings' ),
							$available_qty
						) );
					} elseif ( ! $available_qtys ) {
						return new WP_Error( 'Error', sprintf(
							_n( 'There is %d place remaining on %s', 'There are %d places remaining on %s', $available_qty , 'woocommerce-bookings' ),
							$available_qty,
							date_i18n( get_option( 'date_format' ), $block )
						) );
					} else {
						return new WP_Error( 'Error', sprintf(
							_n( 'There is %d place remaining on %s', 'There are %d places remaining on %s', $available_qty , 'woocommerce-bookings' ),
							max( $available_qtys ),
							date_i18n( get_option( 'date_format' ), $block )
						) );
					}
				}

				$available_qtys[] = $available_qty;
			}

			return min( $available_qtys );
		}
	}

	/**
	 * Similar to get_available_bookings(), only we're interested if there is an available block WITHIN the range (not for the range as a whole)
	 * @param  string $start_date
	 * @param  string $end_date
	 * @param  int $resource_id
	 * @param  integer $qty being booked
	 * @return bool|array
	 */
	public function has_available_block_within_range( $start_date, $end_date, $resource_id = '', $qty = 1 ) {
		// Check we have a resource if needed
		$booking_resource = $resource_id ? $this->get_resource( $resource_id ) : null;

		if ( $this->has_resources() && ! is_numeric( $resource_id ) ) {
			return false;
		}

		// Get availability of each resource - no resource has been chosen yet
		if ( $this->has_resources() && ! $resource_id ) {
			$resources           = $this->get_resources();
			$available_resources = array();

			foreach ( $resources as $resource ) {
				if ( $this->has_available_block_within_range( $start_date, $end_date, $resource->ID, $qty ) ) {
					$available_resources[ $resource->ID ] = true;
				}
			}

			if ( empty( $available_resources ) ) {
				return false;
			}

			return $available_resources;

		// If we are checking for bookings for a specific resource, or have none...
		} else {
			$available_qtys = array();
			$blocks         = $this->get_blocks_in_range( $start_date, $end_date, '', $resource_id );
			$interval       = 'hour' === $this->get_duration_unit() ? $this->get_duration() * 60 : $this->get_duration();

			if ( ! $blocks ) {
				return false;
			}

			/**
			 * Grab all existing bookings for the date range
			 * @var array
			 */
			if ( $this->has_resources() && $resource_id ) {
				$existing_bookings = WC_Bookings_Controller::get_bookings_in_date_range( $start_date, $end_date, $resource_id );
			} else {
				$existing_bookings = WC_Bookings_Controller::get_bookings_in_date_range( $start_date, $end_date, $this->id );
			}

			foreach ( $blocks as $block ) {
				$available_qty       = $this->has_resources() && $booking_resource->has_qty() ? $booking_resource->get_qty() : $this->get_qty();
				$qty_booked_in_block = 0;

				foreach ( $existing_bookings as $existing_booking ) {
					if ( ! $existing_booking->is_booked_on_day( $block ) ) {
						continue;
					}

					$block_end   = strtotime( "+{$interval} minutes", $block );
					$booking_end = $existing_booking->is_all_day() ? strtotime( 'tomorrow midnight', $existing_booking->end ) : $existing_booking->end;

					if (
						( $existing_booking->start >= $block && $existing_booking->start < $block_end ) ||
						( $existing_booking->start < $block && $booking_end > $block_end ) ||
						( $booking_end > $block && $booking_end <= $block_end )
						) {

						$qty_to_add = $this->has_person_qty_multiplier() ? max( 1, array_sum( $existing_booking->get_persons() ) ) : 1;
						if ( $this->has_resources() ) {
							if ( $existing_booking->get_resource_id() === absint( $resource_id ) || ( ! $booking_resource->has_qty() && $existing_booking->get_resource() && ! $existing_booking->get_resource()->has_qty() ) ) {
								$qty_booked_in_block += $qty_to_add;
							}
						} else {
							$qty_booked_in_block += $qty_to_add;
						}
					}
				}

				$available_qty = $available_qty - $qty_booked_in_block;

				if ( $available_qty >= $qty ) {
					return true;
				}
			}
			return false;
		}
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private function get_custom_range( $from, $to, $value ) {
		$availability = array();
		$from_date    = strtotime( $from );
		$to_date      = strtotime( $to );

		if ( empty( $to ) || empty( $from ) || $to_date < $from_date )
			return;

		// We have at least 1 day, even if from_date == to_date
		$numdays = 1 + ( $to_date - $from_date ) / 60 / 60 / 24;

		for ( $i = 0; $i < $numdays; $i ++ ) {
			$year  = date( 'Y', strtotime( "+{$i} days", $from_date ) );
			$month = date( 'n', strtotime( "+{$i} days", $from_date ) );
			$day   = date( 'j', strtotime( "+{$i} days", $from_date ) );

			$availability[ $year ][ $month ][ $day ] = $value;
		}

		return $availability;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private function get_months_range( $from, $to, $value ) {
		$months = array();
		$diff   = $to - $from;
		$diff   = ( $diff < 0 ) ? 12 + $diff : $diff;
		$month  = $from;

		for ( $i = 0; $i <= $diff; $i ++ ) {
			$months[ $month ] = $value;

			$month ++;

			if ( $month > 52 )
				$month = 1;
		}

		return $months;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private function get_weeks_range( $from, $to, $value ) {
		$weeks = array();
		$diff  = $to - $from;
		$diff  = ( $diff < 0 ) ? 52 + $diff : $diff;
		$week  = $from;

		for ( $i = 0; $i <= $diff; $i ++ ) {
			$weeks[ $week ] = $value;

			$week ++;

			if ( $week > 52 )
				$week = 1;
		}

		return $weeks;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private function get_days_range( $from, $to, $value ) {
		$day_of_week  = $from;
		$diff         = $to - $from;
		$diff         = ( $diff < 0 ) ? 7 + $diff : $diff;
		$days         = array();

		for ( $i = 0; $i <= $diff; $i ++ ) {
			$days[ $day_of_week ] = $value;

			$day_of_week ++;

			if ( $day_of_week > 7 ) {
				$day_of_week = 1;
			}
		}

		return $days;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private function get_time_range( $from, $to, $value, $day = 0 ) {
		return array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value,
			'day'  => $day
		);
	}

	/**
	 * Process some rules and return them
	 * @param  array $rules
	 * @return array
	 */
	public function process_rules( $rules ) {
		$processed_rules = array();

		if ( empty( $rules ) ) {
			return $processed_rules;
		}

		// See what types of rules we have before getting the rules themselves
		$rule_types = array();

		foreach ( $rules as $fields ) {
			if ( empty( $fields['bookable'] ) ) {
				continue;
			}
			$rule_types[] = $fields['type'];
		}
		$rule_types = array_filter( $rule_types );

		// Go through rules
		foreach ( $rules as $fields ) {
			if ( empty( $fields['bookable'] ) ) {
				continue;
			}
			$type_function     = strrpos( $fields['type'], 'time:' ) === 0 ? 'get_time_range' : 'get_' . $fields['type'] . '_range';
			$type_availability = $this->$type_function( $fields['from'], $fields['to'], $fields['bookable'] === 'yes' ? true : false );

			// Ensure day gets specified for time: rules
			if ( strrpos( $fields['type'], 'time:' ) === 0 ) {
				list( , $day ) = explode( ':', $fields['type'] );
				$type_availability['day'] = absint( $day );
			}

			// Enable days when user defines time rules, but not day rules
			if ( ! in_array( 'custom', $rule_types ) && ! in_array( 'days', $rule_types ) && ! in_array( 'months', $rule_types ) && ! in_array( 'weeks', $rule_types ) ) {
				if ( strrpos( $fields['type'], 'time:' ) === 0 ) {
					list( , $day ) = explode( ':', $fields['type'] );
					if ( $fields['bookable'] === 'yes' ) {
						$processed_rules[] = array( 'days', $this->get_days_range( $day, $day, true ) );
					}
				} elseif ( strrpos( $fields['type'], 'time' ) === 0 ) {
					if ( $fields['bookable'] === 'yes' ) {
						$processed_rules[] = array( 'days', $this->get_days_range( 0, 7, true ) );
					}
				}
			}
			if ( $type_availability ) {
				$processed_rules[] = array( $fields['type'], $type_availability );
			}
		}

		return $processed_rules;
	}

	/**
	 * Get array of rules.
	 * @return array
	 */
	public function get_availability_rules( $for_resource = 0 ) {
		if ( empty( $this->availability_rules[ $for_resource ] ) ) {
			$this->availability_rules[ $for_resource ] = array();

			// Rule types
			$resource_rules = array();
			$product_rules  = $this->wc_booking_availability;
			$global_rules   = get_option( 'wc_global_booking_availability', array() );

			// Get availability of each resource - no resource has been chosen yet
			if ( $this->has_resources() && ! $for_resource ) {
				$resources      = $this->get_resources();

				foreach ( $resources as $resource ) {
					$resource_rule = (array) get_post_meta( $resource->ID, '_wc_booking_availability', true );
					$resource_rules = array_merge( $resource_rules, $resource_rule );
				}

			// Standard handling
			} elseif ( $for_resource ) {
				$resource_rules = (array) get_post_meta( $for_resource, '_wc_booking_availability', true );
			}

			// Merge and reverse order so lower rules are evaluated first
			$this->availability_rules[ $for_resource ] = array_filter( array_reverse( array_merge( $this->process_rules( $resource_rules ), $this->process_rules( $product_rules ), $this->process_rules( $global_rules ) ) ) );
		}
		return apply_filters( 'woocommerce_booking_get_availability_rules', $this->availability_rules[ $for_resource ], $for_resource, $this );
	}

	/**
	 * See if dates are by default bookable
	 * @return bool
	 */
	public function get_default_availability() {
		return $this->wc_booking_default_date_availability === 'available';
	}

	/**
	 * Check a date against the availability rules
	 * @param  string $check_date date to check
	 * @return bool available or not
	 */
	public function check_availability_rules_against_date( $check_date, $resource_id ) {
		$year        = date( 'Y', $check_date );
		$month       = absint( date( 'm', $check_date ) );
		$day         = absint( date( 'd', $check_date ) );
		$day_of_week = absint( date( 'N', $check_date ) );
		$week        = absint( date( 'W', $check_date ) );
		$bookable    = $this->get_default_availability();

		foreach ( $this->get_availability_rules( $resource_id ) as $rule ) {
			$type  = $rule[0];
			$rules = $rule[1];

			switch ( $type ) {
				case 'months' :
					if ( isset( $rules[ $month ] ) ) {
						$bookable = $rules[ $month ];
						break 2;
					}
				break;
				case 'weeks':
					if ( isset( $rules[ $week ] ) ) {
						$bookable = $rules[ $week ];
						break 2;
					}
				break;
				case 'days' :
					if ( isset( $rules[ $day_of_week ] ) ) {
						$bookable = $rules[ $day_of_week ];
						break 2;
					}
				break;
				case 'custom' :
					if ( isset( $rules[ $year ][ $month ][ $day ] ) ) {
						$bookable = $rules[ $year ][ $month ][ $day ];
						break 2;
					}
				break;
			}
		}

		return $bookable;
	}

	/**
	 * Check a time against the availability rules
	 * @param  string $start_time timestamp to check
	 * @param  string $end_time timestamp to check
	 * @return bool available or not
	 */
	public function check_availability_rules_against_time( $start_time, $end_time, $resource_id ) {
		$bookable   = $this->get_default_availability();
		$start_time = is_numeric( $start_time ) ? $start_time : strtotime( $start_time );
		$end_time   = is_numeric( $end_time ) ? $end_time : strtotime( $end_time );

		foreach ( $this->get_availability_rules( $resource_id ) as $rule ) {
			$type  = $rule[0];
			$rules = $rule[1];

			if ( strrpos( $type, 'time' ) === 0 ) {

				if ( ! empty( $rules['day'] ) ) {
					if ( $rules['day'] != date( 'N', $start_time ) ) {
						continue;
					}
				}

				$start_time_hi      = date( 'YmdHis', $start_time );
				$end_time_hi        = date( 'YmdHis', $end_time );
				$rule_start_time_hi = date( 'YmdHis', strtotime( $rules['from'], $start_time ) );
				$rule_end_time_hi   = date( 'YmdHis', strtotime( $rules['to'], $start_time ) );

				// Reverse time rule - The end time is tomorrow e.g. 16:00 today - 12:00 tomorrow
				if ( $rule_end_time_hi <= $rule_start_time_hi ) {

					if ( $end_time_hi > $rule_start_time_hi ) {
						$bookable = $rules['rule'];
						break;
					}
					if ( $start_time_hi >= $rule_start_time_hi && $end_time_hi >= $rule_end_time_hi ) {
						$bookable = $rules['rule'];
						break;
					}
					if ( $start_time_hi <= $rule_start_time_hi && $end_time_hi <= $rule_end_time_hi ) {
						$bookable = $rules['rule'];
						break;
					}

				// Normal rule
				} else {
					if ( $start_time_hi >= $rule_start_time_hi && $end_time_hi <= $rule_end_time_hi ) {
						$bookable = $rules['rule'];
						break;
					}
				}
			}
		}

		return $bookable;
	}

	/**
	 * Get duration range
	 * @param  [type] $from
	 * @param  [type] $to
	 * @param  [type] $value
	 * @return [type]
	 */
	private function get_duration_range( $from, $to, $value ) {
		$availability = array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value
			);
		return $availability;
	}

	/**
	 * Get Persons range
	 * @param  [type] $from
	 * @param  [type] $to
	 * @param  [type] $value
	 * @return [type]
	 */
	private function get_persons_range( $from, $to, $value ) {
		$availability = array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value
			);
		return $availability;
	}

	/**
	 * Get blocks range
	 * @param  [type] $from
	 * @param  [type] $to
	 * @param  [type] $value
	 * @return [type]
	 */
	private function get_blocks_range( $from, $to, $value ) {
		$availability = array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value
			);
		return $availability;
	}

	/**
	 * Get array of costs
	 *
	 * @return array
	 */
	public function get_costs() {
		$costs = array();

		// Go through rules
		foreach ( $this->wc_booking_pricing as $fields ) {
			if ( empty( $fields['cost'] ) && empty( $fields['base_cost'] ) ) {
				continue;
			}

			$cost          = $fields['cost'];
			$modifier      = $fields['modifier'];
			$base_cost     = $fields['base_cost'];
			$base_modifier = $fields['base_modifier'];
			$type_function = "get_{$fields['type']}_range";
			$type_costs    = $this->$type_function( $fields['from'], $fields['to'], array(
				'base'  => array( $base_modifier, $base_cost ),
				'block' => array( $modifier, $cost )
			) );

			if ( $type_costs ) {
				$costs[] = array( $fields['type'], $type_costs );
			}
		}

		return $costs;
	}

	/**
	 * Checks if a product requires confirmation.
	 *
	 * @return bool
	 */
	public function requires_confirmation() {
		$requires_confirmation = ( 'yes' == $this->wc_booking_requires_confirmation ) ? true : false;

		return apply_filters( 'woocommerce_booking_requires_confirmation', $requires_confirmation, $this );
	}

	/**
	 * Get the add to cart button text for the single page
	 *
	 * @return string
	 */
	public function single_add_to_cart_text() {
		if ( 'yes' == $this->wc_booking_requires_confirmation ) {
			return apply_filters( 'woocommerce_booking_single_check_availability_text', __( 'Check Availability', 'woocommerce-bookings' ), $this );
		}

		return apply_filters( 'woocommerce_booking_single_add_to_cart_text', __( 'Book now', 'woocommerce-bookings' ), $this );
	}
}
