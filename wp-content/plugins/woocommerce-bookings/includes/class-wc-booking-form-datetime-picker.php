<?php
/**
 * Class dependencies
 */
if ( ! class_exists( 'WC_Booking_Form_Date_Picker' ) ) {
	include_once( 'class-wc-booking-form-date-picker.php' );
}

/**
 * Date and time Picker class
 */
class WC_Booking_Form_Datetime_Picker extends WC_Booking_Form_Date_Picker {

	private $field_type = 'datetime-picker';
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
		$this->args['first_block_time']      = $this->booking_form->product->wc_booking_first_block_time;
		$this->args['label']                 = $this->get_field_label( __( 'Date', 'woocommerce-bookings' ) );
		$this->args['min_date_js']           = $this->get_min_date();
		$this->args['max_date_js']           = $this->get_max_date();
		$this->args['interval']              = $this->booking_form->product->wc_booking_duration;
		$this->args['display']               = $this->booking_form->product->wc_booking_calendar_display_mode;
		$this->args['availability_rules']    = array();
		$this->args['availability_rules'][0] = $this->booking_form->product->get_availability_rules();
		
		if ( $this->booking_form->product->has_resources() ) {
			foreach ( $this->booking_form->product->get_resources() as $resource ) {
				$this->args['availability_rules'][ $resource->ID ] = $this->booking_form->product->get_availability_rules( $resource->ID );
			}
		}

		if ( 'hour' === $this->booking_form->product->wc_booking_duration_unit ) {
			$this->args['interval'] = $this->args['interval'] * 60;
		}

		$this->find_fully_booked_blocks();
	}
}