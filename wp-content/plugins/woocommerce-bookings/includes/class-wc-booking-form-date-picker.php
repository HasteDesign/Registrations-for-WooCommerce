<?php
/**
 * Class dependencies
 */
if ( ! class_exists( 'WC_Booking_Form_Picker' ) ) {
	include_once( 'class-wc-booking-form-picker.php' );
}

/**
 * Date Picker class
 */
class WC_Booking_Form_Date_Picker extends WC_Booking_Form_Picker {

	private $field_type = 'date-picker';
	private $field_name = 'start_date';

	/**
	 * Constructor
	 * @param object $booking_form The booking form which called this picker
	 */
	public function __construct( $booking_form ) {
		$this->booking_form                  = $booking_form;
		$this->args                          = array();
		$this->args['type']                  = $this->field_type;
		$this->args['name']                  = $this->field_name;
		$this->args['min_date']              = $this->booking_form->product->get_min_date();
		$this->args['max_date']              = $this->booking_form->product->get_max_date();
		$this->args['default_availability']  = $this->booking_form->product->get_default_availability();
		$this->args['label']                 = $this->get_field_label( __( 'Date', 'woocommerce-bookings' ) );
		$this->args['min_date_js']           = $this->get_min_date();
		$this->args['max_date_js']           = $this->get_max_date();
		$this->args['display']               = $this->booking_form->product->wc_booking_calendar_display_mode;
		$this->args['availability_rules']    = array();
		$this->args['availability_rules'][0] = $this->booking_form->product->get_availability_rules();
		
		if ( $this->booking_form->product->has_resources() ) {
			foreach ( $this->booking_form->product->get_resources() as $resource ) {
				$this->args['availability_rules'][ $resource->ID ] = $this->booking_form->product->get_availability_rules( $resource->ID );
			}
		}

		$this->find_fully_booked_blocks();
	}

	/**
	 * Finds days which are fully booked already so they can be blocked on the date picker
	 * @return array()
	 */
	protected function find_fully_booked_blocks() {
		// Bare existing bookings into consideration for datepicker
		$fully_booked_days = array();
		$find_bookings_for = array( $this->booking_form->product->id );

		if ( $this->booking_form->product->has_resources() ) {
			foreach (  $this->booking_form->product->get_resources() as $resource ) {
				$find_bookings_for[] = $resource->ID;
			}
		}

		$existing_bookings  = WC_Bookings_Controller::get_bookings_for_objects(
			$find_bookings_for,
			array(
				'unpaid',
				'pending',
				'confirmed',
				'paid',
				'complete'
			)
		);

		// Use the existing bookings to find days which are fully booked
		foreach ( $existing_bookings as $existing_booking ) {
			$start_date  = $existing_booking->start;
			$end_date    = $existing_booking->is_all_day() ? strtotime( 'tomorrow midnight', $existing_booking->end ) : $existing_booking->end;
			$product_id  = $existing_booking->get_product_id();
			$resource_id = $existing_booking->get_resource_id();
			$check_date  = $start_date; // Take it from the top

			// Loop over all booked days in this booking
			while ( $check_date < $end_date ) {
				if ( $check_date >= current_time( 'timestamp' ) ) {
					$js_date = date( 'Y-n-j', $check_date );

					if ( $this->booking_form->product->has_resources() ) {

						// Skip if we've already found this resource is unavailable
						if ( ! empty( $fully_booked_days[ $js_date ][ $resource_id ] ) ) {
							$check_date = strtotime( "+1 day", $check_date );
							continue;
						}

						$availability = $this->booking_form->product->has_available_block_within_range( strtotime( 'midnight', $check_date ), strtotime( 'tomorrow midnight', $check_date ), 0 );

						if ( ! $availability || empty( $availability[ $resource_id ] ) ) {
							$fully_booked_days[ $js_date ][ $resource_id ] = true;
						}

					} else {

						// Skip if we've already found this product is unavailable
						if ( ! empty( $fully_booked_days[ $js_date ] ) ) {
							$check_date = strtotime( "+1 day", $check_date );
							continue;
						}

						if ( ! $this->booking_form->product->has_available_block_within_range( strtotime( 'midnight', $check_date ), strtotime( 'tomorrow midnight', $check_date ), 0 ) ) {
							$fully_booked_days[ $js_date ][0] = true;
						}

					}
				}
				$check_date = strtotime( "+1 day", $check_date );
			}
		}

		$this->args['fully_booked_days'] = $fully_booked_days;
	}
}