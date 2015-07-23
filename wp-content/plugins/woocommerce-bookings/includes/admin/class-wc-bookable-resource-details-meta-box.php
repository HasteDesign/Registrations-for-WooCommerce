<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Bookable_Resource_Details_Meta_Box {
	public $id;
	public $title;
	public $context;
	public $priority;
	public $post_types;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id         = 'woocommerce-bookable-resource-data';
		$this->title      = __( 'Resource details', 'woocommerce-bookings' );
		$this->context    = 'normal';
		$this->priority   = 'high';
		$this->post_types = array( 'bookable_resource' );

		add_action( 'save_post', array( $this, 'meta_box_save' ), 10, 1 );
	}

	/**
	 * Show meta box
	 */
	public function meta_box_inner( $post ) {
		$post_id = $post->ID;
		wp_enqueue_script( 'wc_bookings_writepanel_js' );
		wp_nonce_field( 'bookable_resource_details_meta_box', 'bookable_resource_details_meta_box_nonce' );
		?>
		<style type="text/css">
			#minor-publishing-actions, #visibility { display:none }
		</style>
		<div class="woocommerce_options_panel woocommerce">
			<div class="panel-wrap" id="bookings_availability">
				<div class="options_group">
					<?php woocommerce_wp_text_input( array( 'id' => '_wc_booking_qty', 'label' => __( 'Available Quantity', 'woocommerce-bookings' ), 'description' => __( 'The quantity of this resource available at any given time.', 'woocommerce-bookings' ), 'value' => max( absint( get_post_meta( $post_id, 'qty', true ) ), 1 ), 'desc_tip' => true, 'type' => 'number', 'custom_attributes' => array(
						'min'   => '',
						'step' 	=> '1'
					) ) ); ?>
				</div>
				<div class="options_group">
					<div class="table_grid">
						<table class="widefat">
							<thead>
								<tr>
									<th class="sort" width="1%">&nbsp;</th>
									<th><?php _e( 'Range type', 'woocommerce-bookings' ); ?></th>
									<th><?php _e( 'From', 'woocommerce-bookings' ); ?></th>
									<th><?php _e( 'To', 'woocommerce-bookings' ); ?></th>
									<th><?php _e( 'Bookable', 'woocommerce-bookings' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'If not bookable, users won\'t be able to choose this block for their booking.', 'woocommerce-bookings' ); ?>">[?]</a></th>
									<th class="remove" width="1%">&nbsp;</th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th colspan="6">
										<a href="#" class="button button-primary add_row" data-row="<?php
											ob_start();
											include( 'views/html-booking-availability-fields.php' );
											$html = ob_get_clean();
											echo esc_attr( $html );
										?>"><?php _e( 'Add Range', 'woocommerce-bookings' ); ?></a>
										<span class="description"><?php _e( 'Rules further down the table will override those at the top.', 'woocommerce-bookings' ); ?></span>
									</th>
								</tr>
							</tfoot>
							<tbody id="availability_rows">
								<?php
									$values = get_post_meta( $post_id, '_wc_booking_availability', true );
									if ( ! empty( $values ) && is_array( $values ) ) {
										foreach ( $values as $availability ) {
											include( 'views/html-booking-availability-fields.php' );
										}
									}
								?>
							</tbody>
						</table>
					</div>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save handler
	 */
	public function meta_box_save( $post_id ) {
		if ( ! isset( $_POST['bookable_resource_details_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['bookable_resource_details_meta_box_nonce'], 'bookable_resource_details_meta_box' ) ) {
			return $post_id;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		if ( ! in_array( $_POST['post_type'], $this->post_types ) ) {
			return $post_id;
		}

		// Qty field
		update_post_meta( $post_id, 'qty', wc_clean( $_POST['_wc_booking_qty'] ) );

		// Availability
		$availability = array();
		$row_size     = isset( $_POST[ "wc_booking_availability_type" ] ) ? sizeof( $_POST[ "wc_booking_availability_type" ] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			$availability[ $i ]['type']     = wc_clean( $_POST[ "wc_booking_availability_type" ][ $i ] );
			$availability[ $i ]['bookable'] = wc_clean( $_POST[ "wc_booking_availability_bookable" ][ $i ] );

			switch ( $availability[ $i ]['type'] ) {
				case 'custom' :
					$availability[ $i ]['from'] = wc_clean( $_POST[ "wc_booking_availability_from_date" ][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST[ "wc_booking_availability_to_date" ][ $i ] );
				break;
				case 'months' :
					$availability[ $i ]['from'] = wc_clean( $_POST[ "wc_booking_availability_from_month" ][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST[ "wc_booking_availability_to_month" ][ $i ] );
				break;
				case 'weeks' :
					$availability[ $i ]['from'] = wc_clean( $_POST[ "wc_booking_availability_from_week" ][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST[ "wc_booking_availability_to_week" ][ $i ] );
				break;
				case 'days' :
					$availability[ $i ]['from'] = wc_clean( $_POST[ "wc_booking_availability_from_day_of_week" ][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST[ "wc_booking_availability_to_day_of_week" ][ $i ] );
				break;
				case 'time' :
				case 'time:1' :
				case 'time:2' :
				case 'time:3' :
				case 'time:4' :
				case 'time:5' :
				case 'time:6' :
				case 'time:7' :
					$availability[ $i ]['from'] = wc_booking_sanitize_time( $_POST[ "wc_booking_availability_from_time" ][ $i ] );
					$availability[ $i ]['to']   = wc_booking_sanitize_time( $_POST[ "wc_booking_availability_to_time" ][ $i ] );
				break;
			}
		}
		update_post_meta( $post_id, '_wc_booking_availability', $availability );
	}
}

return new WC_Bookable_Resource_Details_Meta_Box();
