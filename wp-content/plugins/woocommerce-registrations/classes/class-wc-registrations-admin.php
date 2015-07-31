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
	    add_action( 'woocommerce_process_product_meta_registrations', __CLASS__ . '::save_variable_fields', 11 );

		add_filter( 'woocommerce_product_data_tabs', __CLASS__ . '::registration_dates_tab' );

		add_filter( 'woocommerce_variation_option_name', __CLASS__ . '::registration_variation_option_name' );

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

			// WooCommerce Registrations Admin - admin.js
			wp_enqueue_script( 'woocommerce_registrations_admin', plugin_dir_url( WC_Registrations::$plugin_file ) . '/js/admin.js', $dependencies, filemtime( plugin_dir_path( WC_Registrations::$plugin_file ) . 'js/admin.js' ) );
			wp_localize_script( 'woocommerce_registrations_admin', 'WCRegistrations', apply_filters( 'woocommerce_registrations_admin_script_parameters', $script_params ) );

			// WooCommerce Registrations Ajax - wc-registration-ajax.js
			wp_enqueue_script( 'woocommerce_registrations_ajax', plugin_dir_url( WC_Registrations::$plugin_file ) . '/js/wc-registration-ajax.js', $dependencies, filemtime( plugin_dir_path( WC_Registrations::$plugin_file ) . 'js/wc-registration-ajax.js' ) );
			wp_localize_script( 'woocommerce_registrations_ajax', 'WCRegistrations', apply_filters( 'woocommerce_registrations_admin_script_parameters', $script_params ) );
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
			//wp_enqueue_style( 'woocommerce_subscriptions_admin', plugin_dir_url( WC_Registrations::$plugin_file ) . 'css/admin.css', array( 'woocommerce_admin_styles' ), WC_Registrations::$version );
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

		$product_types[ WC_Registrations::$name ] = __( 'Registration', 'woocommerce-registrations' );

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

		include( 'views/html-dates-variation-fields-view.php' );

		do_action( 'woocommerce_variable_subscription_pricing', $loop, $variation_data, $variation );
	}

  /**
	 * Save meta data for simple course product type when the "Edit Product" form is submitted.
	 *
	 * @param array Array of Product types & their labels, excluding the Course product type.
	 * @return array Array of Product types & their labels, including the Course product type.
	 * @since 0.1
	 */
	public static function save_variable_fields( $post_id ) {

		// Call save_variations method, because product_type is registration not variation
		if ( class_exists( 'WC_Meta_Box_Product_Data' ) ) {
			WC_Meta_Box_Product_Data::save_variations( $post_id, get_post( $post_id ) );
		}

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
		include_once( 'views/html-dates-view.php' );
    }

	public static function registration_variation_option_name( $option ) {
			error_log( $option );
			$opt = json_decode( $option );
			if( $opt ) {
				return self::format_variations_dates( $opt );
			} else {
				return $option;
			}
	}

	public static function format_variations_dates( $opt ) {
		if ( $opt ) {
			if ( $opt->type == 'single' ) {

				return date_i18n( get_option( 'date_format' ), strtotime( $opt->date ) );

			} elseif ( $opt->type == 'multiple' ) {

				$date_option = '';
				$size = count( $opt->dates );

				for( $i = 0; $i < $size ; $i ++ ) {
					if( $date_option == '' ) {
						$date_option .= date_i18n( get_option( 'date_format' ), strtotime( $opt->dates[ $i ] ) );
					} else {
						$date_option .= ', ' . date_i18n( get_option( 'date_format' ), strtotime( $opt->dates[ $i ] ) );
					}
				}

				return $date_option;

			} elseif ( $opt->type == 'range' ) {
				return date_i18n( get_option( 'date_format' ), strtotime( $opt->dates[0] ) ) . __(' to ', 'woocommerce-registrations') . date_i18n( get_option( 'date_format' ), strtotime( $opt->dates[1] ) );
			} else {
				return $opt;
			}
		}
	}
}

WC_Registrations_Admin::init();
