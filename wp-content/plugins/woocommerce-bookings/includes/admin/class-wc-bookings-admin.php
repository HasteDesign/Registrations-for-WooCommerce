<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Booking admin
 */
class WC_Bookings_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'bookings_link_header' ) );
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'bookings_link' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'include_post_type_handlers' ) );
		add_action( 'admin_init', array( $this, 'include_meta_box_handlers' ) );
		add_action( 'admin_init', array( $this, 'redirect_new_add_booking_url' ) );
		add_filter( 'product_type_options', array( $this, 'product_type_options' ) );
		add_filter( 'product_type_selector' , array( $this, 'product_type_selector' ) );
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'add_tab' ), 5 );
		add_action( 'woocommerce_product_write_panels', array( $this, 'booking_panels' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'styles_and_scripts' ) );
		add_action( 'woocommerce_process_product_meta', array( $this,'save_product_data' ), 20 );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'booking_data' ) );
		add_filter( 'product_type_options', array( $this, 'booking_product_type_options' ) );
		add_action( 'load-options-general.php', array( $this, 'reset_ics_exporter_timezone_cache' ) );

		// Ajax
		add_action( 'wp_ajax_woocommerce_add_bookable_resource', array( $this, 'add_bookable_resource' ) );
		add_action( 'wp_ajax_woocommerce_remove_bookable_resource', array( $this, 'remove_bookable_resource' ) );

		add_action( 'wp_ajax_woocommerce_add_bookable_person', array( $this, 'add_bookable_person' ) );
		add_action( 'wp_ajax_woocommerce_remove_bookable_person', array( $this, 'remove_bookable_person' ) );

		include( 'class-wc-bookings-menus.php' );
	}

	/**
	 * Change messages when a post type is updated.
	 *
	 * @param  array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages['wc_booking'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Booking updated.', 'woocommerce-bookings' ),
			2 => __( 'Custom field updated.', 'woocommerce-bookings' ),
			3 => __( 'Custom field deleted.', 'woocommerce-bookings' ),
			4 => __( 'Booking updated.', 'woocommerce' ),
			5 => '',
			6 => __( 'Booking updated.', 'woocommerce-bookings' ),
			7 => __( 'Booking saved.', 'woocommerce-bookings' ),
			8 => __( 'Booking submitted.', 'woocommerce-bookings' ),
			9 => '',
			10 => ''
		);

		return $messages;
	}

	/**
	 * Header for bookings link TD
	 */
	public function bookings_link_header() {
		?><th>&nbsp;</th><?php
	}

	/**
	 * Link to bookings on order edit page
	 */
	public function bookings_link( $_product, $item, $item_id ) {
		global $wpdb;

		if ( $_product && $_product->is_type( 'booking' ) ) {
			$booking_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_booking_order_item_id' AND meta_value = %d;", $item_id ) );
			if ( $booking_id ) {
				?>
				<td>
					<a href="<?php echo admin_url( 'post.php?post=' . $booking_id . '&action=edit' ); ?>"><?php _e( 'View booking &rarr;', 'woocommerce-bookings' ); ?></a>
				</td>
				<?php
			} else {
				echo '<td></td>';
			}
		} else {
				echo '<td></td>';
			}
	}

	/**
	 * Include CPT handlers
	 */
	public function include_post_type_handlers() {
		include( 'class-wc-bookings-cpt.php' );
		include( 'class-wc-bookable-resource-cpt.php' );
	}

	/**
	 * Include meta box handlers
	 */
	public function include_meta_box_handlers() {
		include( 'class-wc-bookings-meta-boxes.php' );
	}

	/**
	 * Redirect the default add booking url to the custom one
	 */
	public function redirect_new_add_booking_url() {
		global $pagenow;

		if ( 'post-new.php' == $pagenow && isset( $_GET['post_type'] ) && 'wc_booking' == $_GET['post_type'] ) {
			wp_redirect( admin_url( 'edit.php?post_type=wc_booking&page=create_booking' ), '301' );
		}
	}

	/**
	 * Get booking products
	 * @return array
	 */
	public static function get_booking_products() {
		return get_posts( apply_filters( 'get_booking_products_args', array(
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'booking'
				)
			),
			'suppress_filters' => true
		) ) );
	}

	/**
	 * Get booking products
	 * @return array
	 */
	public static function get_booking_resources() {
		return get_posts( apply_filters( 'get_booking_resources_args', array(
			'post_status'      => 'publish',
			'post_type'        => 'bookable_resource',
			'posts_per_page'   => -1,
			'orderby'          => 'menu_order',
			'order'            => 'asc',
			'suppress_filters' => true
		) ) );
	}

	/**
	 * Add resource
	 */
	public function add_bookable_resource() {
		global $wpdb;

		header( 'Content-Type: application/json; charset=utf-8' );

		check_ajax_referer( "add-bookable-resource", 'security' );

		$post_id           = intval( $_POST['post_id'] );
		$loop              = intval( $_POST['loop'] );
		$add_resource_id   = intval( $_POST['add_resource_id'] );
		$add_resource_name = wc_clean( $_POST['add_resource_name'] );

		if ( $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->prefix}wc_booking_relationships WHERE product_id = %d AND resource_id = %d;", $post_id, $add_resource_id ) ) ) {
			die( json_encode( array( 'error' => __( 'The resource has already been linked to this product', 'woocommerce-bookings' ) ) ) );
		}

		// Add resource
		if ( ! $add_resource_id ) {
			$resource = array(
				'post_title'   => $add_resource_name,
				'post_content' => '',
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
				'post_type'    => 'bookable_resource'
			);
			$resource_id = wp_insert_post( $resource );
		} else {
			$resource_id = $add_resource_id;
		}

		// Return html
		if ( $resource_id ) {

			// Link resource to product
			$wpdb->insert(
				"{$wpdb->prefix}wc_booking_relationships",
				array(
					'product_id'  => $post_id,
					'resource_id' => $resource_id,
					'sort_order'  => $loop
				)
			);

			$resource = get_post( $resource_id );
			ob_start();
			include( 'views/html-booking-resource.php' );
			die( json_encode( array( 'html' => ob_get_clean() ) ) );
		}

		die( json_encode( array( 'error' => __( 'Unable to add resource', 'woocommerce-bookings' ) ) ) );
	}

	/**
	 * Remove resource
	 */
	public function remove_bookable_resource() {
		global $wpdb;

		check_ajax_referer( "delete-bookable-resource", 'security' );

		$post_id     = absint( $_POST['post_id'] );
		$resource_id = absint( $_POST['resource_id'] );

		$wpdb->delete(
			"{$wpdb->prefix}wc_booking_relationships",
			array(
				'product_id'  => $post_id,
				'resource_id' => $resource_id
			)
		);

		die();
	}

	/**
	 * Add person type
	 */
	public function add_bookable_person() {
		global $woocommerce;

		check_ajax_referer( 'add-bookable-person', 'security' );

		$post_id = intval( $_POST['post_id'] );
		$loop    = intval( $_POST['loop'] );

		$person_type = array(
			'post_title'   => sprintf( __( 'Person Type #%d', 'woocommerce-bookings' ), ( $loop + 1 ) ),
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => get_current_user_id(),
			'post_parent'  => $post_id,
			'post_type'    => 'bookable_person',
			'menu_order'   => $loop
		);

		$person_type_id = wp_insert_post( $person_type );

		if ( $person_type_id ) {
			$person_type = get_post( $person_type_id );

			include( 'views/html-booking-person.php' );
		}

		die();
	}

	/**
	 * Remove person type
	 */
	public function remove_bookable_person() {
		check_ajax_referer( 'delete-bookable-person', 'security' );
		$person_type_id = intval( $_POST['person_id'] );
		$person_type    = get_post( $person_type_id );

		if ( $person_type && 'bookable_person' == $person_type->post_type ) {
			wp_delete_post( $person_type_id );
		}

		die();
	}

	/**
	 * Tweak product type options
	 * @param  array $options
	 * @return array
	 */
	public function product_type_options( $options ) {
		$options['virtual']['wrapper_class'] .= ' show_if_booking';
		return $options;
	}

	/**
	 * Add the booking product type
	 */
	public function product_type_selector( $types ) {
		$types[ 'booking' ] = __( 'Bookable product', 'woocommerce-bookings' );
		return $types;
	}

	/**
	 * Show the booking tab
	 */
	public function add_tab() {
		include( 'views/html-booking-tab.php' );
	}

	/**
	 * Show the booking data view
	 */
	public function booking_data() {
		global $post;
		$post_id = $post->ID;
		include( 'views/html-booking-data.php' );
	}

	/**
	 * Show the booking panels views
	 */
	public function booking_panels() {
		global $post;

		$post_id = $post->ID;

		wp_enqueue_script( 'wc_bookings_writepanel_js' );

		include( 'views/html-booking-resources.php' );
		include( 'views/html-booking-availability.php' );
		include( 'views/html-booking-pricing.php' );
		include( 'views/html-booking-persons.php' );
	}

	/**
	 * Add admin styles
	 */
	public function styles_and_scripts() {
		global $post, $woocommerce, $wp_scripts;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wc_bookings_admin_styles', WC_BOOKINGS_PLUGIN_URL . '/assets/css/admin.css', null, WC_BOOKINGS_VERSION );

		if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<' ) ) {
			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

			wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css', null, WC_VERSION );
			wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css' );
		}

		wp_register_script( 'wc_bookings_writepanel_js', WC_BOOKINGS_PLUGIN_URL . '/assets/js/writepanel' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), WC_BOOKINGS_VERSION, true );

		wp_register_script( 'wc_bookings_settings_js', WC_BOOKINGS_PLUGIN_URL . '/assets/js/settings' . $suffix . '.js', array( 'jquery' ), WC_BOOKINGS_VERSION, true );

		$params = array(
			'i18n_remove_person'     => esc_js( __( 'Are you sure you want to remove this person type?', 'woocommerce-bookings' ) ),
			'nonce_delete_person'    => wp_create_nonce( 'delete-bookable-person' ),
			'nonce_add_person'       => wp_create_nonce( 'add-bookable-person' ),

			'i18n_remove_resource'   => esc_js( __( 'Are you sure you want to remove this resource?', 'woocommerce-bookings' ) ),
			'nonce_delete_resource'  => wp_create_nonce( 'delete-bookable-resource' ),
			'nonce_add_resource'     => wp_create_nonce( 'add-bookable-resource' ),

			'i18n_new_resource_name' => esc_js( __( 'Enter a name for the new resource', 'woocommerce-bookings' ) ),
			'post'                   => isset( $post->ID ) ? $post->ID : '',
			'plugin_url'             => $woocommerce->plugin_url(),
			'ajax_url'               => admin_url( 'admin-ajax.php' ),
			'calendar_image'         => $woocommerce->plugin_url() . '/assets/images/calendar.png',
		);

		wp_localize_script( 'wc_bookings_writepanel_js', 'wc_bookings_writepanel_js_params', $params );
	}

	/**
	 * Save Booking data for the product
	 *
	 * @param  int $post_id
	 */
	public function save_product_data( $post_id ) {
		global $wpdb;

		$product_type         = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );
		$has_additional_costs = false;

		if ( 'booking' !== $product_type ) {
			return;
		}

		// Save meta
		$meta_to_save = array(
			'_wc_booking_base_cost'                  => 'float',
			'_wc_booking_cost'                       => 'float',
			'_wc_booking_min_duration'               => 'int',
			'_wc_booking_max_duration'               => 'int',
			'_wc_booking_calendar_display_mode'      => '',

			'_wc_booking_qty'                        => 'int',

			'_wc_booking_has_persons'                => 'issetyesno',
			'_wc_booking_person_qty_multiplier'      => 'yesno',
			'_wc_booking_person_cost_multiplier'     => 'yesno',
			'_wc_booking_min_persons_group'          => 'int',
			'_wc_booking_max_persons_group'          => 'int',
			'_wc_booking_has_person_types'           => 'yesno',

			'_wc_booking_has_resources'              => 'issetyesno',
			'_wc_booking_resources_assignment'       => '',
			'_wc_booking_duration_type'              => '',
			'_wc_booking_duration'                   => 'int',
			'_wc_booking_duration_unit'              => '',
			'_wc_booking_max_date'                   => 'max_date',
			'_wc_booking_max_date_unit'              => 'max_date_unit',
			'_wc_booking_min_date'                   => 'int',
			'_wc_booking_min_date_unit'              => '',
			'_wc_booking_first_block_time'           => '',
			'_wc_booking_requires_confirmation'      => 'yesno',
			'_wc_booking_default_date_availability'  => '',
			'_wc_booking_check_availability_against' => '',
			'_wc_booking_resouce_label'              => ''
		);

		foreach ( $meta_to_save as $meta_key => $sanitize ) {
			$value = ! empty( $_POST[ $meta_key ] ) ? $_POST[ $meta_key ] : '';
			switch ( $sanitize ) {
				case 'int' :
					$value = absint( $value );
					break;
				case 'float' :
					$value = floatval( $value );
					break;
				case 'yesno' :
					$value = $value == 'yes' ? 'yes' : 'no';
					break;
				case 'issetyesno' :
					$value = $value ? 'yes' : 'no';
					break;
				case 'max_date' :
					$value = absint( $value );
					if ( $value == 0 )
						$value = 1;
					break;
				default :
					$value = sanitize_text_field( $value );
			}
			update_post_meta( $post_id, $meta_key, $value );
		}

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

		// Pricing
		$pricing = array();
		$row_size     = isset( $_POST[ "wc_booking_pricing_type" ] ) ? sizeof( $_POST[ "wc_booking_pricing_type" ] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			$pricing[ $i ]['type']          = wc_clean( $_POST[ "wc_booking_pricing_type" ][ $i ] );
			$pricing[ $i ]['cost']          = wc_clean( $_POST[ "wc_booking_pricing_cost" ][ $i ] );
			$pricing[ $i ]['modifier']      = wc_clean( $_POST[ "wc_booking_pricing_cost_modifier" ][ $i ] );
			$pricing[ $i ]['base_cost']     = wc_clean( $_POST[ "wc_booking_pricing_base_cost" ][ $i ] );
			$pricing[ $i ]['base_modifier'] = wc_clean( $_POST[ "wc_booking_pricing_base_cost_modifier" ][ $i ] );

			switch ( $pricing[ $i ]['type'] ) {
				case 'custom' :
					$pricing[ $i ]['from'] = wc_clean( $_POST[ "wc_booking_pricing_from_date" ][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST[ "wc_booking_pricing_to_date" ][ $i ] );
				break;
				case 'months' :
					$pricing[ $i ]['from'] = wc_clean( $_POST[ "wc_booking_pricing_from_month" ][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST[ "wc_booking_pricing_to_month" ][ $i ] );
				break;
				case 'weeks' :
					$pricing[ $i ]['from'] = wc_clean( $_POST[ "wc_booking_pricing_from_week" ][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST[ "wc_booking_pricing_to_week" ][ $i ] );
				break;
				case 'days' :
					$pricing[ $i ]['from'] = wc_clean( $_POST[ "wc_booking_pricing_from_day_of_week" ][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST[ "wc_booking_pricing_to_day_of_week" ][ $i ] );
				break;
				case 'time' :
					$pricing[ $i ]['from'] = wc_booking_sanitize_time( $_POST[ "wc_booking_pricing_from_time" ][ $i ] );
					$pricing[ $i ]['to']   = wc_booking_sanitize_time( $_POST[ "wc_booking_pricing_to_time" ][ $i ] );
				break;
				default :
					$pricing[ $i ]['from'] = wc_clean( $_POST[ "wc_booking_pricing_from" ][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST[ "wc_booking_pricing_to" ][ $i ] );
				break;
			}

			if ( $pricing[ $i ]['cost'] > 0 ) {
				$has_additional_costs = true;
			}
		}

		update_post_meta( $post_id, '_wc_booking_pricing', $pricing );

		// Resources
		if ( isset( $_POST['resource_id'] ) && isset( $_POST['_wc_booking_has_resources'] ) ) {
			$resource_ids         = $_POST['resource_id'];
			$resource_menu_order  = $_POST['resource_menu_order'];
			$resource_base_cost   = $_POST['resource_cost'];
			$resource_block_cost  = $_POST['resource_block_cost'];
			$max_loop             = max( array_keys( $_POST['resource_id'] ) );
			$resource_base_costs  = array();
			$resource_block_costs = array();

			for ( $i = 0; $i <= $max_loop; $i ++ ) {
				if ( ! isset( $resource_ids[ $i ] ) ) {
					continue;
				}

				$resource_id = absint( $resource_ids[ $i ] );

				$wpdb->update(
					"{$wpdb->prefix}wc_booking_relationships",
					array(
						'sort_order'  => $resource_menu_order[ $i ]
					),
					array(
						'product_id'  => $post_id,
						'resource_id' => $resource_id
					)
				);

				$resource_base_costs[ $resource_id ]  = wc_clean( $resource_base_cost[ $i ] );
				$resource_block_costs[ $resource_id ] = wc_clean( $resource_block_cost[ $i ] );

				if ( ( $resource_base_cost[ $i ] + $resource_block_cost[ $i ] ) > 0 ) {
					$has_additional_costs = true;
				}
			}

			update_post_meta( $post_id, '_resource_base_costs', $resource_base_costs );
			update_post_meta( $post_id, '_resource_block_costs', $resource_block_costs );
		}

		// Person Types
		if ( isset( $_POST['person_id'] ) && isset( $_POST['_wc_booking_has_persons'] ) ) {
			$person_ids         = $_POST['person_id'];
			$person_menu_order  = $_POST['person_menu_order'];
			$person_name        = $_POST['person_name'];
			$person_cost        = $_POST['person_cost'];
			$person_description = $_POST['person_description'];
			$person_min         = $_POST['person_min'];
			$person_max         = $_POST['person_max'];

			$max_loop = max( array_keys( $_POST['person_id'] ) );

			for ( $i = 0; $i <= $max_loop; $i ++ ) {
				if ( ! isset( $person_ids[ $i ] ) ) {
					continue;
				}

				$person_id = absint( $person_ids[ $i ] );

				if ( empty( $person_name[ $i ] ) ) {
					$person_name[ $i ] = sprintf( __( 'Person Type #%d', 'woocommerce-bookings' ), ( $i + 1 ) );
				}

				$wpdb->update(
					$wpdb->posts,
					array(
						'post_title'   => stripslashes( $person_name[ $i ] ),
						'post_excerpt' => stripslashes( $person_description[ $i ] ),
						'menu_order'   => $person_menu_order[ $i ] ),
					array(
						'ID' => $person_id
					),
					array(
						'%s',
						'%s',
						'%d'
					),
					array( '%d' )
				);

				update_post_meta( $person_id, 'cost', woocommerce_clean( $person_cost[ $i ] ) );
				update_post_meta( $person_id, 'min', woocommerce_clean( $person_min[ $i ] ) );
				update_post_meta( $person_id, 'max', woocommerce_clean( $person_max[ $i ] ) );

				if ( $person_cost[ $i ] > 0 ) {
					$has_additional_costs = true;
				}
			}
		}

		update_post_meta( $post_id, '_has_additional_costs', ( $has_additional_costs ? 'yes' : 'no' ) );
		update_post_meta( $post_id, '_regular_price', '' );
		update_post_meta( $post_id, '_sale_price', '' );

		// Set price so filters work - using get_base_cost()
		$bookable_product = get_product( $post_id );
		update_post_meta( $post_id, '_price', $bookable_product->get_base_cost() );
	}

	/**
	 * Add extra product type options
	 * @param  array $options
	 * @return array
	 */
	public function booking_product_type_options( $options ) {
		return array_merge( $options, array(
			'wc_booking_has_persons' => array(
				'id'            => '_wc_booking_has_persons',
				'wrapper_class' => 'show_if_booking',
				'label'         => __( 'Has persons', 'woocommerce-bookings' ),
				'description'   => __( 'Enable this if this bookable product can be booked by a customer defined number of persons.', 'woocommerce-bookings' ),
				'default'       => 'no'
			),
			'wc_booking_has_resources' => array(
				'id'            => '_wc_booking_has_resources',
				'wrapper_class' => 'show_if_booking',
				'label'         => __( 'Has resources', 'woocommerce-bookings' ),
				'description'   => __( 'Enable this if this bookable product has multiple bookable resources, for example room types or instructors.', 'woocommerce-bookings' ),
				'default'       => 'no'
			),
		) );
	}

	/**
	 * Reset the ics exporter timezone string cache.
	 *
	 * @return void
	 */
	public function reset_ics_exporter_timezone_cache() {
		if ( isset( $_GET['settings-updated'] ) && 'true' == $_GET['settings-updated'] ) {
			wp_cache_delete( 'wc_bookings_timezone_string' );
		}
	}
}

new WC_Bookings_Admin();
