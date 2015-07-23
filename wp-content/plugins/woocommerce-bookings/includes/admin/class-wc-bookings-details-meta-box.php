<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Bookings_Details_Meta_Box {
	public $id;
	public $title;
	public $context;
	public $priority;
	public $post_types;

	public function __construct() {
		$this->id = 'woocommerce-booking-data';
		$this->title = __( 'Booking details', 'woocommerce-bookings' );
		$this->context = 'normal';
		$this->priority = 'high';
		$this->post_types = array( 'wc_booking' );

		add_action( 'save_post', array( $this, 'meta_box_save' ), 10, 1 );
	}

	public function meta_box_inner( $post ) {
		wp_nonce_field( 'wc_bookings_details_meta_box', 'wc_bookings_details_meta_box_nonce' );

		// Scripts.
		wp_enqueue_script( 'ajax-chosen' );
		wp_enqueue_script( 'chosen' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		$customer_id = get_post_meta( $post->ID, '_booking_customer_id', true );
		$order_parent_id = apply_filters( 'woocommerce_order_number', _x( '#', 'hash before order number', 'woocommerce-bookings' ) . $post->post_parent, $post->post_parent );

		?>
		<style type="text/css">
			#post-body-content, #titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv { display:none }
		</style>
		<div class="panel-wrap woocommerce">
			<div id="booking_data" class="panel">

			<h2><?php _e( 'Booking Details', 'woocommerce-bookings' ); ?></h2>
			<p class="booking_number"><?php

				printf( __( 'Booking number: #%s.', 'woocommerce-bookings' ), esc_html( $post->ID ) );

				if ( $post->post_parent ) {
					$order = new WC_Order( $post->post_parent );
					printf( ' ' . __( 'Order number: %s.', 'woocommerce-bookings' ), '<a href="' . admin_url( 'post.php?post=' . absint( $post->post_parent ) . '&action=edit' ) . '">' . esc_html( $order->get_order_number() ) . '</a>' );
				}

			?></p>

			<div class="booking_data_column_container">
				<div class="booking_data_column">

					<h4><?php _e( 'General Details', 'woocommerce-bookings' ); ?></h4>

					<p class="form-field form-field-wide">
						<label for="_booking_order_id"><?php _e( 'Order ID:', 'woocommerce-bookings' ); ?></label>
						<select id="_booking_order_id" name="_booking_order_id" class="ajax_chosen_select_booking_order_id" data-placeholder="<?php _e( 'Select an order&hellip;', 'woocommerce-bookings' ); ?>">
							<option value=""><?php _e( 'N/A', 'woocommerce-bookings' ); ?></option>
							<?php
								if ( $post->post_parent ) {
									echo '<option value="' . esc_attr( $post->post_parent ) . '" ' . selected( 1, 1, false ) . '>' . $order_parent_id . ' &ndash; ' . esc_html( get_the_title( $post->post_parent ) ) . '</option>';
								}
							?>
						</select>
					</p>

					<p class="form-field form-field-wide"><label for="booking_date"><?php _e( 'Date created:', 'woocommerce-bookings' ); ?></label>
						<input type="text" class="date-picker-field" name="booking_date" id="booking_date" maxlength="10" value="<?php echo date_i18n( 'Y-m-d', strtotime( $post->post_date ) ); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" /> @ <input type="text" class="hour" placeholder="<?php _e( 'h', 'woocommerce-bookings' ); ?>" name="booking_date_hour" id="booking_date_hour" maxlength="2" size="2" value="<?php echo date_i18n( 'H', strtotime( $post->post_date ) ); ?>" pattern="\-?\d+(\.\d{0,})?" />:<input type="text" class="minute" placeholder="<?php _e( 'm', 'woocommerce-bookings' ); ?>" name="booking_date_minute" id="booking_date_minute" maxlength="2" size="2" value="<?php echo date_i18n( 'i', strtotime( $post->post_date ) ); ?>" pattern="\-?\d+(\.\d{0,})?" />
					</p>

					<?php
						$statuses = array(
							'unpaid' => __( 'unpaid', 'woocommerce-bookings' ),
							'pending' => __( 'pending', 'woocommerce-bookings' ),
							'confirmed' => __( 'confirmed', 'woocommerce-bookings' ),
							'paid' => __( 'paid', 'woocommerce-bookings' ),
							'cancelled' => __( 'cancelled', 'woocommerce-bookings' ),
							'complete' => __( 'complete', 'woocommerce-bookings' )
						);
					?>

					<p class="form-field form-field-wide">
						<label for="_booking_status"><?php _e( 'Booking Status:', 'woocommerce-bookings' ); ?></label>
						<select id="_booking_status" name="_booking_status">
							<?php
								foreach ( $statuses as $key => $value ) {
									echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $post->post_status, false ) . '>' . esc_html__( $value, 'woocommerce-bookings' ) . '</option>';
								}
							?>
						</select>
					</p>

					<p class="form-field form-field-wide">
						<label for="_booking_customer_id"><?php _e( 'Customer:', 'woocommerce-bookings' ); ?></label>
						<select id="_booking_customer_id" name="_booking_customer_id" class="ajax_chosen_select_customer">
							<option value=""><?php _e( 'Guest', 'woocommerce-bookings' ); ?></option>
							<?php
								if ( $customer_id ) {
									$user = get_user_by( 'id', $customer_id );
									echo '<option value="' . esc_attr( $user->ID ) . '" ' . selected( 1, 1, false ) . '>' . esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) . ')</option>';
								}
							?>
						</select>
					</p>

					<?php do_action( 'woocommerce_admin_booking_data_after_booking_details', $post->ID ); ?>

				</div>
				<div class="booking_data_column">

					<h4><?php _e( 'Booking Specification', 'woocommerce-bookings' ); ?></h4>

					<?php

					$bookable_products = array( '' => __( 'N/A', 'woocommerce-bookings' ) );

					$products = WC_Bookings_Admin::get_booking_products();

					foreach ( $products as $product ) {
						$bookable_products[ $product->ID ] = $product->post_title;

						$resources = wc_booking_get_product_resources( $product->ID );

						foreach ( $resources as $resource ) {
							$bookable_products[ $product->ID . '=>' . $resource->ID ] = '&nbsp;&nbsp;&nbsp;' . $resource->post_title;
						}
					}

					$product_id  = get_post_meta( $post->ID, '_booking_product_id', true );
					$resource_id = get_post_meta( $post->ID, '_booking_resource_id', true );

					woocommerce_wp_select( array( 'id' => 'product_or_resource_id', 'label' => __( 'Booked Product', 'woocommerce-bookings' ), 'options' => $bookable_products, 'value' => ( $resource_id ? $product_id . '=>' . $resource_id : $product_id ) ) );

					woocommerce_wp_text_input( array( 'id' => '_booking_parent_id', 'label' => __( 'Parent Booking ID', 'woocommerce-bookings' ), 'placeholder' => 'N/A' ) );

					$persons = get_post_meta( $post->ID, '_booking_persons', true );

					if ( ! empty( $persons ) && is_array( $persons ) ) {

						echo '<br class="clear" />';
						echo '<h4>' . __( 'Person(s)', 'woocommerce-bookings' ) . '</h4>';

						foreach ( $persons as $person_id => $person_count ) {
							woocommerce_wp_text_input( array( 'id' => '_booking_person_' . $person_id, 'label' => get_the_title( $person_id ), 'placeholder' => '0', 'value' => $person_count, 'wrapper_class' => 'booking-person' ) );
						}
					}
					?>
				</div>
				<div class="booking_data_column">

					<h4><?php _e( 'Booking Date/Time', 'woocommerce-bookings' ); ?></h4>

					<?php

					woocommerce_wp_text_input( array( 'id' => 'booking_start_date', 'label' => __( 'Start date', 'woocommerce-bookings' ), 'placeholder' => 'yyyy-mm-dd', 'value' => date( 'Y-m-d', strtotime( get_post_meta( $post->ID, '_booking_start', true ) ) ), 'class' => 'date-picker-field' ) );

					woocommerce_wp_text_input( array( 'id' => 'booking_end_date', 'label' => __( 'End date', 'woocommerce-bookings' ), 'placeholder' => 'yyyy-mm-dd', 'value' => date( 'Y-m-d', strtotime( get_post_meta( $post->ID, '_booking_end', true ) ) ), 'class' => 'date-picker-field' ) );

					woocommerce_wp_checkbox( array( 'id' => '_booking_all_day', 'label' => __( 'All day', 'woocommerce-bookings' ), 'description' => __( 'Check this box if the booking is for all day.', 'woocommerce-bookings' ), 'value' => get_post_meta( $post->ID, '_booking_all_day', true ) ? 'yes' : 'no' ) );

					woocommerce_wp_text_input( array( 'id' => 'booking_start_time', 'label' => __( 'Start time', 'woocommerce-bookings' ), 'placeholder' => 'hh:mm', 'value' => date( 'H:i', strtotime( get_post_meta( $post->ID, '_booking_start', true ) ) ), 'class' => 'datepicker' ) );

					woocommerce_wp_text_input( array( 'id' => 'booking_end_time', 'label' => __( 'End time', 'woocommerce-bookings' ), 'placeholder' => 'hh:mm', 'value' => date( 'H:i', strtotime( get_post_meta( $post->ID, '_booking_end', true ) ) ) ) );

					?>

				</div>
			</div>
			<div class="clear"></div>
		</div>

		<?php
			wc_enqueue_js( "
				$( '#_booking_all_day' ).change( function () {
					if ( $( this ).is( ':checked' ) ) {
						$( '#booking_start_time, #booking_end_time' ).closest( 'p' ).hide();
					} else {
						$( '#booking_start_time, #booking_end_time' ).closest( 'p' ).show();
					}
				}).change();

				$( 'select#_booking_order_id' ).ajaxChosen({
					method:         'GET',
					url:            '" . admin_url( 'admin-ajax.php' ) . "',
					dataType:       'json',
					afterTypeDelay: 100,
					minTermLength:  1,
					data: {
						action:   'wc_bookings_json_search_order',
						security: '" . wp_create_nonce( 'search-booking-order' ) . "'
					}
				}, function ( data ) {

					var orders = {};

					$.each( data, function ( i, val ) {
						orders[i] = val;
					});

					return orders;
				});

				$( 'select#_booking_status' ).chosen({
					disable_search: true
				});

				$( 'select.ajax_chosen_select_customer' ).ajaxChosen({
					method:         'GET',
					url:            '" . admin_url( 'admin-ajax.php' ) . "',
					dataType:       'json',
					afterTypeDelay: 100,
					minTermLength:  1,
					data: {
						action:   'woocommerce_json_search_customers',
						security: '" . wp_create_nonce( 'search-customers' ) . "'
					}
				}, function ( data ) {

					var terms = {};

					$.each( data, function ( i, val ) {
						terms[i] = val;
					});

					return terms;
				});

				$( 'select#product_or_resource_id' ).chosen();

				$( '.date-picker-field' ).datepicker({
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 1,
					showButtonPanel: true,
				});
			" );
	}

	public function meta_box_save( $post_id ) {
		if ( ! isset( $_POST['wc_bookings_details_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['wc_bookings_details_meta_box_nonce'], 'wc_bookings_details_meta_box' ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! in_array( $_POST['post_type'], $this->post_types ) ) {
			return $post_id;
		}

		global $wpdb;

		// Save simple fields
		$booking_order_id = absint( $_POST['_booking_order_id'] );
		$booking_status   = wc_clean( $_POST['_booking_status'] );
		$customer_id      = absint( $_POST['_booking_customer_id'] );
		$product_id       = wc_clean( $_POST['product_or_resource_id'] );
		$parent_id        = absint( $_POST['_booking_parent_id'] );
		$all_day          = isset( $_POST['_booking_all_day'] ) ? '1' : '0';

		// Update post_parent and status via query to prevent endless loops
		$wpdb->update( $wpdb->posts, array( 'post_parent' => $booking_order_id ), array( 'ID' => $post_id ) );
		$wpdb->update( $wpdb->posts, array( 'post_status' => $booking_status ), array( 'ID' => $post_id ) );

		// Cancel order on save
		if ( 'cancelled' == $booking_status ) {
			$order = new WC_Order( $booking_order_id );
			$order->update_status( 'cancelled' );
		}

		// Save product and resource
		if ( strstr( $product_id, '=>' ) ) {
			list( $product_id, $resource_id ) = explode( '=>', $product_id );
		} else {
			$resource_id = 0;
		}
		
		update_post_meta( $post_id, '_booking_resource_id', $resource_id );
		update_post_meta( $post_id, '_booking_product_id', $product_id );

		// Update meta
		update_post_meta( $post_id, '_booking_customer_id', $customer_id );
		update_post_meta( $post_id, '_booking_parent_id', $parent_id );
		update_post_meta( $post_id, '_booking_all_day', $all_day );

		// Persons
		$persons = get_post_meta( $post_id, '_booking_persons', true );

		if ( ! empty( $persons ) ) {
			$booking_persons = array();

			foreach ( array_keys( $persons ) as $person_id ) {
				$booking_persons[ $person_id ] = absint( $_POST[ '_booking_person_' . $person_id ] );
			}

			update_post_meta( $post_id, '_booking_persons', $booking_persons );
		}

		// Update date
		if ( empty( $_POST['booking_date'] ) ) {
			$date = current_time('timestamp');
		} else {
			$date = strtotime( $_POST['booking_date'] . ' ' . (int) $_POST['booking_date_hour'] . ':' . (int) $_POST['booking_date_minute'] . ':00' );
		}

		$date = date_i18n( 'Y-m-d H:i:s', $date );

		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_date = %s, post_date_gmt = %s WHERE ID = %s", $date, get_gmt_from_date( $date ), $post_id ) );

		// Do date and time magic and save them in one field
		$start_date = explode( '-', wc_clean( $_POST['booking_start_date'] ) );
		$end_date   = explode( '-', wc_clean( $_POST['booking_end_date'] ) );
		$start_time = explode( ':', wc_clean( $_POST['booking_start_time'] ) );
		$end_time   = explode( ':', wc_clean( $_POST['booking_end_time'] ) );

		$start = mktime( $start_time[0], $start_time[1], 0, $start_date[1], $start_date[2], $start_date[0] );
		$end   = mktime( $end_time[0], $end_time[1], 0, $end_date[1], $end_date[2], $end_date[0] );

		update_post_meta( $post_id, '_booking_start', date( 'YmdHis', $start ) );
		update_post_meta( $post_id, '_booking_end', date( 'YmdHis', $end ) );

		do_action( 'woocommerce_booking_process_meta', $post_id );
	}
}

return new WC_Bookings_Details_Meta_Box();
