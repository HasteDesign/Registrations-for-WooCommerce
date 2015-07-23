<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Bookings_Save_Meta_Box {
	public $id;
	public $title;
	public $context;
	public $priority;
	public $post_types;

	public function __construct() {
		$this->id         = 'woocommerce-booking-save';
		$this->title      = __( 'Save Booking', 'woocommerce-bookings' );
		$this->context    = 'side';
		$this->priority   = 'default';
		$this->post_types = array( 'wc_booking' );

		add_action( 'save_post', array( $this, 'meta_box_save' ), 10, 1 );
	}

	public function meta_box_inner( $post ) {
		wp_nonce_field( 'wc_bookings_save_booking_meta_box', 'wc_bookings_save_booking_meta_box_nonce' );

		?>
		<input type="submit" class="button save_order button-primary tips" name="save" value="<?php _e( 'Save Booking', 'woocommerce-bookings' ); ?>" data-tip="<?php _e( 'Save/update the booking', 'woocommerce-bookings' ); ?>" />
		<?php
	}

	public function meta_box_save( $post_id ) {
		if ( ! isset( $_POST['wc_bookings_save_booking_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['wc_bookings_save_booking_meta_box_nonce'], 'wc_bookings_save_booking_meta_box' ) ) {
			return $post_id;
		}

      	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
      	}

		if ( ! in_array( $_POST['post_type'], $this->post_types ) ) {
			return $post_id;
		}
	}
}

return new WC_Bookings_Save_Meta_Box();