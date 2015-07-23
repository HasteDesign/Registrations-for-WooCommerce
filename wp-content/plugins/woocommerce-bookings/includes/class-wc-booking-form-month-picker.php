<?php
/**
 * Class dependencies
 */
if ( ! class_exists( 'WC_Booking_Form_Picker' ) ) {
	include_once( 'class-wc-booking-form-picker.php' );
}

/**
 * Month Picker class
 */
class WC_Booking_Form_Month_Picker extends WC_Booking_Form_Picker {

	private $field_type = 'month-picker';
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
		$this->args['label']                 = $this->get_field_label( __( 'Month', 'woocommerce-bookings' ) );
		$this->args['blocks']                = $this->get_booking_blocks();
		$this->args['availability_rules']    = array();
		$this->args['availability_rules'][0] = $this->booking_form->product->get_availability_rules();
		
		if ( $this->booking_form->product->has_resources() ) {
			foreach ( $this->booking_form->product->get_resources() as $resource ) {
				$this->args['availability_rules'][ $resource->ID ] = $this->booking_form->product->get_availability_rules( $resource->ID );
			}
		}		
	}

	/**
	 * Return the available blocks for this booking in array format
	 * 
	 * @return array Array of blocks
	 */
	public function get_booking_blocks() {
		extract( $this->args );

		if ( $min_date['value'] === 0 ) {
			$min_date['value'] = 1;
		}

		// Generate a range of blocks for months
		$from       = strtotime( date( 'Y-m-01', strtotime( "+{$min_date['value']} {$min_date['unit']}" ) ) );
		$to         = strtotime( date( 'Y-m-t', strtotime( "+{$max_date['value']} {$max_date['unit']}" ) ) );
		
		return $this->booking_form->product->get_blocks_in_range( $from, $to );
	}		
}