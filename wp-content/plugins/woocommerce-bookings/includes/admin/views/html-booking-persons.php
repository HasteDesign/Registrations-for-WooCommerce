<div id="bookings_persons" class="woocommerce_options_panel panel wc-metaboxes-wrapper">

	<div class="options_group" id="persons-options">

		<?php woocommerce_wp_text_input( array( 'id' => '_wc_booking_min_persons_group', 'label' => __( 'Min persons', 'woocommerce-bookings' ), 'description' => __( 'The minimum number of persons per booking.', 'woocommerce-bookings' ), 'value' => max( absint( get_post_meta( $post_id, '_wc_booking_min_persons_group', true ) ), 1 ), 'desc_tip' => true, 'type' => 'number', 'custom_attributes' => array(
			'min'   => '',
			'step' 	=> '1'
		) ) ); ?>

		<?php woocommerce_wp_text_input( array( 'id' => '_wc_booking_max_persons_group', 'label' => __( 'Max persons', 'woocommerce-bookings' ), 'description' => __( 'The maximum number of persons per booking.', 'woocommerce-bookings' ), 'value' => max( absint( get_post_meta( $post_id, '_wc_booking_max_persons_group', true ) ), 1 ), 'desc_tip' => true, 'type' => 'number', 'custom_attributes' => array(
			'min'   => '',
			'step' 	=> '1'
		) ) ); ?>

		<?php woocommerce_wp_checkbox( array( 'id' => '_wc_booking_person_cost_multiplier', 'label' => __( 'Multiply all costs by person count', 'woocommerce-bookings' ), 'description' => __( 'Enable this to multiply the cost of the booking by the person count.', 'woocommerce-bookings' ), 'desc_tip' => true, 'value' => get_post_meta( $post_id, '_wc_booking_person_cost_multiplier', true ) ) ); ?>

		<?php woocommerce_wp_checkbox( array( 'id' => '_wc_booking_person_qty_multiplier', 'label' => __( 'Count persons as bookings', 'woocommerce-bookings' ), 'description' => __( 'Enable this to count each person as a booking until the max bookings per block (above) is reached.', 'woocommerce-bookings' ), 'desc_tip' => true, 'value' => get_post_meta( $post_id, '_wc_booking_person_qty_multiplier', true ) ) ); ?>

		<?php woocommerce_wp_checkbox( array( 'id' => '_wc_booking_has_person_types', 'label' => __( 'Enable person types', 'woocommerce-bookings' ), 'description' => __( 'Person types allow you to offer different booking costs for different types of individuals, for example, adults and children.', 'woocommerce-bookings' ), 'desc_tip' => true, 'value' => get_post_meta( $post_id, '_wc_booking_has_person_types', true ) ) ); ?>

	</div>

	<div class="options_group" id="persons-types">

		<div class="toolbar">
			<h3><?php _e( 'Person types', 'woocommerce-bookings' ); ?></h3>
			<a href="#" class="close_all"><?php _e( 'Close all', 'woocommerce-bookings' ); ?></a><a href="#" class="expand_all"><?php _e( 'Expand all', 'woocommerce-bookings' ); ?></a>
		</div>

		<div class="woocommerce_bookable_persons wc-metaboxes">

			<?php
				global $post;

				$person_types = get_posts( array(
					'post_type'      => 'bookable_person',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'asc',
					'post_parent'    => $post->ID
				) );

				if ( sizeof( $person_types ) == 0 ) {
					echo '<div id="message" class="inline woocommerce-message" style="margin: 1em 0;">';
						echo '<div class="squeezer">';
							echo '<h4>' . __( 'Person types allow you to offer different booking costs for different types of individuals, for example, adults and children.', 'woocommerce-bookings' ) . '</h4>';
						echo '</div>';
					echo '</div>';
				}

				if ( $person_types ) {
					$loop = 0;

					foreach ( $person_types as $person_type ) {
						$person_type_id = absint( $person_type->ID );
						include( 'html-booking-person.php' );
						$loop++;
					}
				}
			?>
		</div>

		<p class="toolbar">
			<button type="button" class="button button-primary add_person"><?php _e( 'Add Person Type', 'woocommerce-bookings' ); ?></button>
		</p>
	</div>
</div>
