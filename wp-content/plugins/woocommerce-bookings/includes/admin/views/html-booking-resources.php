<div id="bookings_resources" class="woocommerce_options_panel panel wc-metaboxes-wrapper">

	<div class="options_group" id="resource_options">

		<?php woocommerce_wp_text_input( array( 'id' => '_wc_booking_resouce_label', 'placeholder' => __( 'Type', 'woocommerce-bookings' ), 'label' => __( 'Label', 'woocommerce-bookings' ), 'desc_tip' => true, 'description' => __( 'The label shown on the frontend if the resource is customer defined.', 'woocommerce-bookings' ) ) ); ?>

		<?php woocommerce_wp_select( array( 'id' => '_wc_booking_resources_assignment', 'label' => __( 'Resources are...', 'woocommerce-bookings' ), 'description' => '', 'desc_tip' => true, 'value' => get_post_meta( $post_id, '_wc_booking_resources_assignment', true ), 'options' => array(
			'customer' 	  => __( 'Customer selected', 'woocommerce-bookings' ),
			'automatic'   => __( 'Automatically assigned', 'woocommerce-bookings' ),
		), 'description' => __( 'Customer selected resources allow customers to choose one from the booking form.', 'woocommerce-bookings' ) ) ); ?>

	</div>

	<div class="options_group">

		<div class="toolbar">
			<h3><?php _e( 'Resources', 'woocommerce-bookings' ); ?></h3>
			<a href="#" class="close_all"><?php _e( 'Close all', 'woocommerce-bookings' ); ?></a><a href="#" class="expand_all"><?php _e( 'Expand all', 'woocommerce-bookings' ); ?></a>
		</div>

		<div class="woocommerce_bookable_resources wc-metaboxes">

			<div id="message" class="inline woocommerce-message updated" style="margin: 1em 0;">
				<p><?php _e( 'Resources are used if you have multiple bookable items, e.g. room types, instructors or ticket types. Availability for resources is global across all bookable products.', 'woocommerce-bookings' ); ?></p>
			</div>

			<?php
			global $post, $wpdb;

			$all_resources = get_posts( array(
				'post_type'      => 'bookable_resource',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'asc'
			) );

			$product_resources = $wpdb->get_col( $wpdb->prepare( "SELECT resource_id FROM {$wpdb->prefix}wc_booking_relationships WHERE product_id = %d ORDER BY sort_order;", $post->ID ) );
			$loop              = 0;

			if ( $product_resources ) {
				$resource_base_costs  = get_post_meta( $post_id, '_resource_base_costs', true );
				$resource_block_costs = get_post_meta( $post_id, '_resource_block_costs', true );

				foreach ( $product_resources as $resource_id ) {
					$resource            = get_post( $resource_id );
					$resource_base_cost  = isset( $resource_base_costs[ $resource_id ] ) ? $resource_base_costs[ $resource_id ] : '';
					$resource_block_cost = isset( $resource_block_costs[ $resource_id ] ) ? $resource_block_costs[ $resource_id ] : '';
					
					include( 'html-booking-resource.php' );
					
					$loop++;
				}
			}
			?>
		</div>

		<p class="toolbar">
			<button type="button" class="button button-primary add_resource"><?php _e( 'Add/link Resource', 'woocommerce-bookings' ); ?></button>
			<select name="add_resource_id" class="add_resource_id">
				<option value=""><?php _e( 'New resource', 'woocommerce-bookings' ); ?></option>
				<?php
					if ( $all_resources ) {
				    	foreach ( $all_resources as $resource ) {
				    		echo '<option value="' . esc_attr( $resource->ID ) . '">#' . $resource->ID . ' - ' . esc_html( $resource->post_title ) . '</option>';
				    	}
				    }
				?>
			</select>
			<a href="<?php echo admin_url( 'edit.php?post_type=bookable_resource' ); ?>" target="_blank"><?php _e( 'Manage Resources', 'woocommerce-bookings' ); ?></a>
		</p>
	</div>
</div>