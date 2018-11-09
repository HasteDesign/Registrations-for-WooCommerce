<?php
/**
 * Registrations Admin Class
 *
 * Add Registration product type with dates tab, saving dates as attributes
 * to be used as variations to sell registrations.
 *
 * @package		Registrations for WooCommerce\WC_Registrations_Admin
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
	 	// Enqueue scripts in product edit page
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::enqueue_styles_scripts' );

	    // Add registrations to the product select box
	    add_filter( 'product_type_selector', __CLASS__ . '::add_registrations_to_select' );

		// Add variations custom fields (time and days)
	    add_action( 'woocommerce_product_after_variable_attributes', __CLASS__ . '::variable_registration_pricing_fields', 10, 3 );

		// Saves registrations meta fields
	    add_action( 'woocommerce_process_product_meta_registrations', __CLASS__ . '::save_variable_fields', 11 );
		add_action( 'woocommerce_ajax_save_product_variations', __CLASS__ . '::save_variable_fields' );

		// Add registrations dates tab
		add_filter( 'woocommerce_product_data_tabs', __CLASS__ . '::registration_dates_tab' );

		// Load the view to display dates tab content
        add_action( 'woocommerce_product_data_panels', __CLASS__. '::show_dates_tab_content' );

		// Filter dates variations options name and display correctly for each date type (single, multiple, and range)
		add_filter( 'woocommerce_variation_option_name', __CLASS__ . '::registration_variation_option_name' );
		add_filter( 'woocommerce_attribute', __CLASS__ . '::registration_variation_filter_additional_information', 10, 3 );
		add_filter( 'woocommerce_display_item_meta', __CLASS__ . '::registration_filter_display_item_meta', 10, 3 );

		// Filter dates attribute name
		add_filter( 'woocommerce_attribute_label', __CLASS__ . '::registration_attribute_label', 10, 3 );

		// Filter past events
		add_action( 'woocommerce_product_options_inventory_product_data', __CLASS__.'::past_events_fields' );
		add_action( 'woocommerce_process_product_meta', __CLASS__.'::past_events_fields_save' );
	}

	/**
	 * Create the interface in the "Edit Product" admin page for the past event filter.
	 *
	 * @since 1.0.7
	 */
	public static function past_events_fields() {

		global $woocommerce, $post;

		echo '<div class="options_group registration_inventory">';

		woocommerce_wp_checkbox(
			array(
				'id'            => '_prevent_past_events',
				'wrapper_class' => 'show_if_registration',
				'label'         => __( 'Prevent registrations to past events', 'registrations-for-woocommerce' ),
				'description'   => __( 'If you want to prevent this event from being registred if a requirement is met.', 'registrations-for-woocommerce' )
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => '_days_to_prevent',
				'label'             => __( 'Days before', 'registrations-for-woocommerce' ),
				'wrapper_class'     => 'show_if_registration',
				'placeholder'       => '',
				'description'       => __( 'Number of days before the event to prevent registration purchase. Affects all variations. [0 means allowed up to the same day]', 'registrations-for-woocommerce' ),
				'type'              => 'number',
				'custom_attributes' => array(
						'step' 	=> 'any',
						'min'	=> '0'
					)
			)
		);

		echo '</div>';

	}

	/**
	 * Adds the option to block event purchases from past or after a certain date.
	 *
	 * @since 1.0.7
	 */
	public static function past_events_fields_save( $post_id ) {
		$_prevent_past_events = isset( $_POST['_prevent_past_events'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_prevent_past_events', $_prevent_past_events );

		$_days_to_prevent = $_POST['_days_to_prevent'];
		update_post_meta( $post_id, '_days_to_prevent', esc_attr( $_days_to_prevent ) );
	}

    /**
	 * Adds all necessary admin styles.
	 *
	 * @since 1.0
	 *
	 * @param array Array of Product types & their labels, excluding the Subscription product type.
	 * @return array Array of Product types & their labels, including the Subscription product type.
	 */
	public static function enqueue_styles_scripts() {
		global $woocommerce, $post;

		// Get admin screen id
		$screen = get_current_screen();

		$is_woocommerce_screen = ( in_array( $screen->id, array( 'product', 'edit-shop_order', 'shop_order', 'users', 'woocommerce_page_wc-settings' ) ) ) ? true : false;
		$is_activation_screen  = ( get_transient( WC_Registrations::$activation_transient ) == true ) ? true : false;

		if ( $is_woocommerce_screen ) {

			$dependencies = array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' );

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

			// Registrations for WooCommerce Admin - admin.js
			wp_enqueue_script( 'woocommerce_registrations_admin', plugin_dir_url( WC_Registrations::$plugin_file ) . '/assets/js/admin.js', $dependencies, filemtime( plugin_dir_path( WC_Registrations::$plugin_file ) . 'assets/js/admin.js' ) );
			wp_localize_script( 'woocommerce_registrations_admin', 'WCRegistrations', apply_filters( 'woocommerce_registrations_admin_script_parameters', $script_params ) );

			// Registrations for WooCommerce Ajax - wc-registrations-ajax.js
			wp_enqueue_script( 'woocommerce_registrations_ajax', plugin_dir_url( WC_Registrations::$plugin_file ) . '/assets/js/wc-registrations-ajax.js', $dependencies, filemtime( plugin_dir_path( WC_Registrations::$plugin_file ) . 'assets/js/wc-registrations-ajax.js' ) );
			wp_localize_script( 'woocommerce_registrations_ajax', 'WCRegistrations', apply_filters( 'woocommerce_registrations_admin_script_parameters', $script_params ) );

			// JQuery UI Datepicker
			wp_enqueue_style( 'jquery-ui-datepicker' );
		}

		// Maybe add the admin notice
		if ( $is_activation_screen ) {

			$woocommerce_plugin_dir_file = self::get_woocommerce_plugin_dir_file();

			if ( ! empty( $woocommerce_plugin_dir_file ) ) {

				wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', self::get_woocommerce_plugin_dir_file() ), array(), WC_Registrations::$version );

				if ( ! isset( $_GET['page'] ) || 'wcs-about' != $_GET['page'] ) {
					add_action( 'admin_notices', __CLASS__ . '::admin_installed_notice' );
				}

			}
			delete_transient( WC_Registrations::$activation_transient );
		}

		if ( $is_woocommerce_screen || $is_activation_screen ) {
			wp_enqueue_style( 'woocommerce_admin_styles', $woocommerce->plugin_url() . '/assets/css/admin.css', array(), WC_Registrations::$version );
		}
	}

	/**
	 * Add the 'registration' product type to the WooCommerce product type select box.
	 *
	 * @since 1.0
	 *
	 * @param array Array of Product types & their labels, excluding the Course product type.
	 * @return array Array of Product types & their labels, including the Course product type.
	 */
	public static function add_registrations_to_select( $product_types ){
		$product_types[ WC_Registrations::$name ] = __( 'Registration', 'registrations-for-woocommerce' );

		return $product_types;
	}

	/**
	 * Output the registration specific fields on the "Edit Product" admin page.
	 *
	 * @since 1.0
	 */
	public static function variable_registration_pricing_fields( $loop, $variation_data, $variation ) {
		include( 'views/html-dates-variation-fields-view.php' );
		do_action( 'woocommerce_registrations_after_variation', $loop, $variation_data, $variation );
	}

  /**
	 * Save meta data for registration product type when the "Edit Product" form is submitted.
	 *
	 * @since 1.0
	 *
	 * @param array Array of Product types & their labels, excluding the Course product type.
	 * @return array Array of Product types & their labels, including the Course product type.
	 */
	public static function save_variable_fields( $post_id ) {
		// Run WooCommerce core saving routine
		if ( ! class_exists( 'WC_Meta_Box_Product_Data' ) ) { // WC < 2.1
			process_product_meta_variable( $post_id );
		} elseif ( ! is_ajax() ) { // WC < 2.4
			WC_Meta_Box_Product_Data::save_variations( $post_id, get_post( $post_id ) );
		}

		if ( ! isset( $_REQUEST['variable_post_id'] ) ) {
			return;
		}

		$variable_post_ids = $_POST['variable_post_id'];

		isset( $_POST['_event_start_time'] ) ? $_event_start_time = $_POST['_event_start_time'] : $_event_start_time = null;
		isset( $_POST['_event_end_time']   ) ? $_event_end_time =   $_POST['_event_end_time'] : $_event_end_time = null;
		isset( $_POST['_week_days']        ) ? $_week_days =        $_POST['_week_days'] : $_week_days = null ;

		$max_loop = max( array_keys( $variable_post_ids ) );

		// Save each variations details
		for ( $i = 0; $i <= $max_loop; $i++ ) {

			if ( ! isset( $variable_post_ids[ $i ] ) ) {
				continue;
			}

			$variation_id = (int) $variable_post_ids[$i];

			if ( isset( $_event_start_time[$i] ) ) {
				update_post_meta( $variation_id, '_event_start_time', stripslashes( $_event_start_time[$i] ) );
			}

			if( isset( $_event_end_time[$i] ) ) {
				update_post_meta( $variation_id, '_event_end_time', stripslashes( $_event_end_time[$i] ) );
			}

			if( isset( $_week_days[$i] ) ) {
				update_post_meta( $variation_id, '_week_days', $_week_days[$i] );
			}
		}
	}

	/**
	 * Register dates tab.
	 *
	 * Register dates tab to be displayed if product type is registration.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $tabs WooCommerce default registered tabs.
	 * @return array $tabs WooCommerce tabs with dates additional tab.
	 */
	public static function registration_dates_tab( $tabs ) {
		// Adds the new dates tab
		$tabs['dates'] = array(
			'label' 	=> __( 'Dates', 'registrations-for-woocommerce' ),
			'target' 	=> 'registration_dates',
			'class' 	=> array( 'show_if_registration' )
		);

		return $tabs;
	}

	/**
	 * Load date tab view
	 *
	 * @since 1.0.0
	 */
    public static function show_dates_tab_content() {
		include_once( 'views/html-dates-view.php' );
    }

	/**
	 * Format registration variation option name.
	 *
	 * Format registration variation option name, that is a JSON encoded string,
	 * making an human friendly date format to display.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $option      Registration variation option name. (JSON encoded)
	 * @param  string $date_format PHP date format.
	 * @return string $option      Formated registrations variation option name.
	 */
	public static function registration_variation_option_name( $option, $date_format = null ) {
		if( $date_format == null ) {
			$date_format = get_option( 'date_format' );
		}

		$opt = json_decode( stripslashes( $option ) );
		if( $opt ) {
			return self::format_variations_dates( $opt, $date_format );
		} else {
			return $option;
		}
	}

	/**
	 * Filter dates exhibition on additional information tab.
	 *
	 * Filter the dates exhibition on additional information tab on product
	 * single page.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $values_sanitized    attribute sanitized string
	 * @param  array  $attribute           current attribute to be displayed
	 * @param  array  $values              attribute values array
	 * @return string $values_sanitized    filtered date attribute according to the site date_format
	 */
	public static function registration_variation_filter_additional_information( $values_sanitized, $attribute, $values ) {
		if( $attribute['name'] === 'Dates' ) {
			$dates = array();
			$date_format = get_option( 'date_format' );

			$attribute['name'] === 'Dates';

			foreach( $attribute->get_options() as $date ) {
				$opt = json_decode( stripslashes( $date ) );

				if( $opt ) {
					 $dates[] = self::format_variations_dates( $opt, $date_format );
				}
			}

			return wptexturize( implode( ', ', $dates ) );
		} else {
			return $values_sanitized;
		}
	}

	/**
	 * Filter dates exhibition on order details item meta
	 *
	 * Filter the dates exhibition on order details item meta section
	 * on checkout, after a successfull purchase.
	 *
	 * @since  2.0.0
	 *
	 * @param  string $values_sanitized    attribute sanitized string
	 * @param  array  $attribute           current attribute to be displayed
	 * @param  array  $values              attribute values array
	 * @return string $values_sanitized    filtered date attribute according to the site date_format
	 */
	public static function registration_filter_display_item_meta( $html, $item, $args ) {
		$strings = array();
		$html    = '';

		foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
			$value = $args['autop'] ? wp_kses_post( $meta->display_value ) : apply_filters( 'woocommerce_variation_option_name', wp_kses_post( make_clickable( trim( strip_tags( $meta->display_value ) ) ) ) );
			$strings[] = '<strong class="wc-item-meta-label">' . wp_kses_post( $meta->display_key ) . ':</strong> ' . $value;
		}

		if ( $strings ) {
			$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
		}

		if ( $args['echo'] ) {
			echo $html;
		} else {
			return $html;
		}
	}

	/**
	 * Filter dates attribute name/label in multiple places.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $label
	 * @param  array  $name
	 * @param  array  $product
	 * @return string $label	filtered date attribute name
	 */
	public static function registration_attribute_label( $label, $name, $product ) {
		if ( $name === 'Dates' || $name === 'dates' ) {
			return __( 'Dates', 'registrations-for-woocommerce' );
		}

		return $label;
	}

	/**
	 * Filter dates variations options name.
	 *
	 * Display dates variations options names for each date type
	 * (single, multiple, and range) formating then correctly to given
	 * date format.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $opt         JSON decoded with registrations type and date.
	 * @param  string $date_format PHP date format to
	 * @return string $opt         Formated registrations variation option name.
	 */
	public static function format_variations_dates( $opt, $date_format ) {
		if ( $opt ) {
			if ( $opt->type == 'single' ) {

				return date_i18n( $date_format, strtotime( $opt->date ) );

			} elseif ( $opt->type == 'multiple' ) {

				$date_option = '';
				$size = count( $opt->dates );

				for( $i = 0; $i < $size ; $i++ ) {
					if( $date_option == '' ) {
						$date_option .= date_i18n( $date_format, strtotime( $opt->dates[ $i ] ) );
					} else {
						$date_option .= ', ' . date_i18n( $date_format, strtotime( $opt->dates[ $i ] ) );
					}
				}

				return $date_option;

			} elseif ( $opt->type == 'range' ) {
				return date_i18n( $date_format, strtotime( $opt->dates[0] ) ) . ' ' . __( 'to' , 'registrations-for-woocommerce' ) . ' ' . date_i18n( $date_format, strtotime( $opt->dates[1] ) );
			} else {
				return $opt;
			}
		}
	}

	/**
	 * Searches through the list of active plugins to find WooCommerce. Just in case
	 * WooCommerce resides in a folder other than /woocommerce/
	 *
	 * @since 1.0
	 */
	public static function get_woocommerce_plugin_dir_file() {

		$woocommerce_plugin_file = '';

		foreach ( get_option( 'active_plugins', array() ) as $plugin ) {
			if ( substr( $plugin, strlen( '/woocommerce.php' ) * -1 ) === '/woocommerce.php' ) {
				$woocommerce_plugin_file = $plugin;
				break;
			}
		}

		return $woocommerce_plugin_file;
	}

	/**
	 * Display a welcome message when the Registrations is activated.
	 *
	 * @since 1.0
	 */
	public static function admin_installed_notice() {
		?>
		<div id="message" class="updated woocommerce-message wc-connect registrations-for-woocommerce-activated">
			<div class="squeezer">
				<h4><?php printf( __( '%sRegistrations for WooCommerce Installed%s &#8211; %sYou\'re ready to start selling registrations!%s', 'registrations-for-woocommerce' ), '<strong>', '</strong>', '<em>', '</em>' ); ?></h4>

				<p class="submit">
					<a href="https://twitter.com/share" class="twitter-share-button" data-url="https://wordpress.org/plugins/registrations-for-woocommerce/" data-text="<?php _e( 'Sell course and events registrations with #WooCommerce', 'registrations-for-woocommerce' ); ?>" data-via="HasteDesign" data-size="large">Tweet</a>
					<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
				</p>
			</div>
		</div>
		<?php
	}
}

WC_Registrations_Admin::init();
