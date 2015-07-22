<?php
/**
 * Registrations Admin Class
 *
 * Adds a Subscription setting tab and saves subscription settings. Adds a Subscriptions Management page. Adds
 * Welcome messages and pointers to streamline learning process for new users.
 *
 * @package		WooCommerce Registrations
 * @subpackage	WC_Registrations_Admin
 * @category	Class
 * @author		Allyson Souza
 * @since		1.0
 */
class WC_Registrations_Admin {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 */
	public static function init() {
        /*
	     * Admin Panel
	     */
	 	// Enqueue scripts in product edit page
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::enqueue_styles_scripts' );

	    // Add subscriptions to the product select box
	    add_filter( 'product_type_selector', __CLASS__ . '::add_registrations_to_select' );

	    // Add registration fields to general tab
	    add_action( 'woocommerce_product_options_general_product_data', __CLASS__ . '::registrations_general_fields' );

		// Add registration fields to general tab
	    add_action( 'woocommerce_product_after_variable_attributes', __CLASS__ . '::variable_registration_pricing_fields', 10, 3 );

		// Saves registrations meta fields
	    add_action( 'woocommerce_process_product_meta_course', __CLASS__ . '::save_registrations_meta', 11 );

		add_filter( 'woocommerce_product_data_tabs', __CLASS__ . '::registration_dates_tab' );

        add_action( 'woocommerce_product_data_panels', __CLASS__. '::show_dates_tab_content' );
	}

    /**
	 * Adds all necessary admin styles.
	 *
	 * @param array Array of Product types & their labels, excluding the Subscription product type.
	 * @return array Array of Product types & their labels, including the Subscription product type.
	 * @since 1.0
	 */
	public static function enqueue_styles_scripts() {
		global $woocommerce, $post;

		// Get admin screen id
		$screen = get_current_screen();

		$is_woocommerce_screen = ( in_array( $screen->id, array( 'product', 'edit-shop_order', 'shop_order', 'users', 'woocommerce_page_wc-settings' ) ) ) ? true : false;
		$is_activation_screen  = ( get_transient( WC_Registrations::$activation_transient ) == true ) ? true : false;

		if ( $is_woocommerce_screen ) {

			$dependencies = array( 'jquery' );

			// Version juggling
			if ( WC_Registrations::is_woocommerce_pre_2_1() ) { // WC 2.0
				$woocommerce_admin_script_handle = 'woocommerce_writepanel';
			} elseif ( WC_Registrations::is_woocommerce_pre_2_2() ) { // WC 2.1
				$woocommerce_admin_script_handle = 'woocommerce_admin_meta_boxes';
			} else {
				$woocommerce_admin_script_handle = 'wc-admin-meta-boxes';
			}

			if( $screen->id == 'product' ) {
				$dependencies[] = $woocommerce_admin_script_handle;

				if ( ! WC_Registrations::is_woocommerce_pre_2_2() ) {
					$dependencies[] = 'wc-admin-product-meta-boxes';
					$dependencies[] = 'wc-admin-variation-meta-boxes';
				}

				$script_params = array(
					'productType'              => WC_Registrations::$name,
				);
			}

			$script_params['ajaxLoaderImage'] = $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif';
			$script_params['ajaxUrl']         = admin_url('admin-ajax.php');
			$script_params['isWCPre21']       = var_export( WC_Registrations::is_woocommerce_pre_2_1(), true );
			$script_params['isWCPre22']       = var_export( WC_Registrations::is_woocommerce_pre_2_2(), true );
			$script_params['isWCPre23']       = var_export( WC_Registrations::is_woocommerce_pre_2_3(), true );

			wp_enqueue_script( 'woocommerce_registrations_admin', plugin_dir_url( WC_Registrations::$plugin_file ) . '/js/admin.js', $dependencies, filemtime( plugin_dir_path( WC_Registrations::$plugin_file ) . 'js/admin.js' ) );
			wp_localize_script( 'woocommerce_registrations_admin', 'WCRegistrations', apply_filters( 'woocommerce_registrations_admin_script_parameters', $script_params ) );
		}

		// Maybe add the admin notice
		if ( $is_activation_screen ) {

			$woocommerce_plugin_dir_file = self::get_woocommerce_plugin_dir_file();

			if ( ! empty( $woocommerce_plugin_dir_file ) ) {

				wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', self::get_woocommerce_plugin_dir_file() ), array(), WC_Subscriptions::$version );

				if ( ! isset( $_GET['page'] ) || 'wcs-about' != $_GET['page'] ) {
					add_action( 'admin_notices', __CLASS__ . '::admin_installed_notice' );
				}

			}
			delete_transient( WC_Registrations::$activation_transient );
		}

		if ( $is_woocommerce_screen || $is_activation_screen ) {
			wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css', array(), WC_Registrations::$version );
			wp_enqueue_style( 'woocommerce_subscriptions_admin', plugin_dir_url( WC_Registrations::$plugin_file ) . 'css/admin.css', array( 'woocommerce_admin_styles' ), WC_Registrations::$version );
		}

	}

  /**
	 * Add the 'registration' product type to the WooCommerce product type select box.
	 *
	 * @param array Array of Product types & their labels, excluding the Course product type.
	 * @return array Array of Product types & their labels, including the Course product type.
	 * @since 0.1
	 */
	public static function add_registrations_to_select( $product_types ){

		$product_types[ WC_Registrations::$name ] = __( 'Registration', 'woocommerce-course-products' );

		return $product_types;
	}

  /**
	 * Output the subscription specific pricing fields on the "Edit Product" admin page.
	 *
	 * @since 0.1
	 */
	public static function registrations_general_fields() {
		global $post;

		echo '<div class="registrations_pricing show_if_registration">';

		// Subscription Price
		woocommerce_wp_text_input( array(
			'id'          => '_registration_price',
			'class'       => 'wc_input_registration_price wc_input_price show_if_registration',
			'label'       => sprintf( __( 'Registration Price (%s)', 'woocommerce-registrations' ), get_woocommerce_currency_symbol() ),
			'placeholder' => __( 'e.g. 5.90', 'woocommerce-registrations' ),
			'type'        => 'text',
			'custom_attributes' => array(
					'step' => 'any',
					'min'  => '0',
				)
			)
		);

		do_action( 'woocommerce_registrations_options_pricing' );

		echo '</div>';
		echo '<div class="show_if_registration clear"></div>';
	}

	/**
	 * Output the registration specific fields on the "Edit Product" admin page.
	 *
	 * @since 0.1
	 */
	public static function variable_registration_pricing_fields( $loop, $variation_data, $variation ) {
		global $woocommerce, $thepostid;

		// Set month as the default billing period
		if ( ! $event_start_date = get_post_meta( $variation->ID, '_event_start_date', true ) ) {
			$event_start_date = '';
		}

		// When called via Ajax
		if ( ! function_exists( 'woocommerce_wp_text_input' ) ) {
			require_once( $woocommerce->plugin_path() . '/admin/post-types/writepanels/writepanels-init.php' );
		}

		if ( ! isset( $thepostid ) ) {
			$thepostid = $variation->post_parent;
		}

		woocommerce_wp_text_input( array(
			'id'          => '_event_start_date',
			'class'       => 'wc_input_event_start_date show_if_registration',
			'label'       => __( 'Event Start Date', 'woocommerce-registrations' ),
			'placeholder' => __( '10/07/2015', 'woocommerce-registrations' ),
			'type'        => 'date',
		'value'       => get_post_meta( $variation->ID, '_event_start_date', true )
			)
		);

		woocommerce_wp_text_input( array(
			'id'          => '_event_end_date',
			'class'       => 'wc_input_event_start_date show_if_registration',
			'label'       => __( 'Event Start Date', 'woocommerce-registrations' ),
			'placeholder' => __( '10/07/2015', 'woocommerce-registrations' ),
			'type'        => 'date',
		'value'       => get_post_meta( $variation->ID, '_event_start_date', true )
			)
		);

		do_action( 'woocommerce_variable_subscription_pricing', $loop, $variation_data, $variation );
	}

  /**
	 * Save meta data for simple course product type when the "Edit Product" form is submitted.
	 *
	 * @param array Array of Product types & their labels, excluding the Course product type.
	 * @return array Array of Product types & their labels, including the Course product type.
	 * @since 0.1
	 */
	public static function save_registrations_meta( $post_id ) {

		if ( ! isset( $_POST['product-type'] ) || ! in_array( $_POST['product-type'], apply_filters( 'woocommerce_registrations_types', array( WC_Registrations::$name ) ) ) ) {
			return;
		}

		$course_price = wc_format_decimal( $_REQUEST['_course_price'] );
		//$sale_price         = wc_format_decimal( $_REQUEST['_sale_price'] );

		update_post_meta( $post_id, '_course_price', $course_price );

		/*
		Set sale details - these are ignored by WC core for the subscription product type
		update_post_meta( $post_id, '_regular_price', $subscription_price );
		update_post_meta( $post_id, '_sale_price', $sale_price );

		$date_from = ( isset( $_POST['_sale_price_dates_from'] ) ) ? strtotime( $_POST['_sale_price_dates_from'] ) : '';
		$date_to   = ( isset( $_POST['_sale_price_dates_to'] ) ) ? strtotime( $_POST['_sale_price_dates_to'] ) : '';

		$now = gmdate( 'U' );

		if ( ! empty( $date_to ) && empty( $date_from ) ) {
			$date_from = $now;
		}

		update_post_meta( $post_id, '_sale_price_dates_from', $date_from );
		update_post_meta( $post_id, '_sale_price_dates_to', $date_to );

		// Update price if on sale
		if ( ! empty( $sale_price ) && ( ( empty( $date_to ) && empty( $date_from ) ) || ( $date_from < $now && ( empty( $date_to ) || $date_to > $now ) ) ) ) {
			$price = $sale_price;
		} else {
			$price = $subscription_price;
		}

		update_post_meta( $post_id, '_price', stripslashes( $price ) );

		// Make sure trial period is within allowable range
		$subscription_ranges = WC_Subscriptions_Manager::get_subscription_ranges();

		$max_trial_length = count( $subscription_ranges[ $_POST['_subscription_trial_period'] ] ) - 1;

		$_POST['_subscription_trial_length'] = absint( $_POST['_subscription_trial_length'] );

		if ( $_POST['_subscription_trial_length'] > $max_trial_length ) {
			$_POST['_subscription_trial_length'] = $max_trial_length;
		}

		update_post_meta( $post_id, '_subscription_trial_length', $_POST['_subscription_trial_length'] );

		$_REQUEST['_subscription_sign_up_fee'] = wc_format_decimal( $_REQUEST['_subscription_sign_up_fee'] );

		$subscription_fields = array(
			'_subscription_sign_up_fee',
			'_subscription_period',
			'_subscription_period_interval',
			'_subscription_length',
			'_subscription_trial_period',
			'_subscription_limit',
		);

		foreach ( $subscription_fields as $field_name ) {
			update_post_meta( $post_id, $field_name, stripslashes( $_REQUEST[ $field_name ] ) );
		}
		*/
	}

	public static function registration_dates_tab( $tabs ) {
		// Adds the new tab

		$tabs['dates'] = array(
			'label' 	=> __( 'Dates', 'woocommerce-registrations' ),
			'target' 	=> 'registration_dates',
			'class' 	=> array('show_if_registration')
		);

		return $tabs;
	}

    public static function show_dates_tab_content() {
        global $thepostid, $post, $woocommerce;

        if( empty( $thepostid ) ) {
            $thepostid = $post->ID;
        }

        $event_start_date = get_post_meta( $thepostid, '_event_start_date', true );

        if( empty( $event_start_date ) ) {
            $event_start_date = ' ';
        }

        $event_end_date = get_post_meta( $thepostid, '_event_end_date', true );

        if( empty( $event_end_date ) ) {
            $event_end_date = ' ';
        }

        echo '<div id="registration_dates" class="panel woocommerce_options_panel">';
            echo '<div class="options_group dates">';

            woocommerce_wp_text_input(
                array(
        			'id'          => 'event_start_date',
        			'class'       => 'wc_input_event_start_date',
        			'label'       => __( 'Event Start Date', 'woocommerce-registrations' ),
        			'placeholder' => __( '10/07/2015', 'woocommerce-registrations' ),
        			'type'        => 'date',
                    'value'       => $event_start_date
    			)
    		);

    		woocommerce_wp_text_input(
                array(
        			'id'          => 'event_end_date',
        			'class'       => 'wc_input_event_end_date',
        			'label'       => __( 'Event End Date', 'woocommerce-registrations' ),
        			'placeholder' => __( '10/07/2015', 'woocommerce-registrations' ),
        			'type'        => 'date',
                    'value'       => $event_end_date
    			)
    		);

            echo '</div>';
        echo '</div>';
    }
}

WC_Registrations_Admin::init();
