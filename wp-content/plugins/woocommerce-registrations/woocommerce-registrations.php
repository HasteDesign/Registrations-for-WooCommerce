<?php
/**
 * Plugin Name: WooCommerce Registrations
 * Plugin URI: http://www.hastedesign.com.br
 * Description: Add registration product type to your Woocommerce.
 * Author: Haste Design, Allyson Souza, Anyssa Ferreira
 * Author URI: http://www.hastedesign.com.br
 * Version: 0.0.1
 *
 * Copyright 2015 Haste Design.  (email : contato@hastedesign.com.br)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package		WooCommerce Course Products
 * @author		Allyson Souza
 * @since			1.0
 */

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) || ! function_exists( 'is_woocommerce_active' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Check if WooCommerce is active, and if it isn't, disable Registrations.
 *
 * @since 0.0.1
 */
if ( ! is_woocommerce_active() || version_compare( get_option( 'woocommerce_db_version' ), '2.1', '<' ) ) {
	add_action( 'admin_notices', 'WC_Registrations::woocommerce_inactive_notice' );
	return;
}

/**
 * The main registration products class.
 *
 * @since 0.1
 */
class WC_Registrations {

	public static $name = 'registrations';
	public static $activation_transient = 'woocommerce_registrations_activated';
	public static $plugin_file = __FILE__;
	public static $text_domain = 'deprecated-use-woocommerce-subscriptions-string';
	public static $version = '0.0.1';

	/**
	 * Set up the class, including it's hooks & filters, when the file is loaded.
	 *
	 * @since 1.0
	 **/
	public static function init() {

		add_action( 'admin_init', __CLASS__ . '::maybe_activate_woocommerce_registrations' );
		register_deactivation_hook( __FILE__, __CLASS__ . '::deactivate_woocommerce_registrations' );

		// Overide the WC default "Add to Cart" text to "Sign Up Now" (in various places/templates)
		//add_filter( 'woocommerce_order_button_text', __CLASS__ . '::order_button_text' );
		//add_action( 'woocommerce_subscription_add_to_cart', __CLASS__ . '::subscription_add_to_cart', 30 );
		//add_action( 'wcopc_subscription_add_to_cart', __CLASS__ . '::wcopc_subscription_add_to_cart' ); // One Page Checkout compatibility

		// Enqueue front-end styles
		//add_filter( 'woocommerce_enqueue_styles', __CLASS__ . '::enqueue_styles', 10, 1 );

		// Load translation files
		add_action( 'plugins_loaded', __CLASS__ . '::load_plugin_textdomain' );

		// Load dependant files
		//add_action( 'plugins_loaded', __CLASS__ . '::load_dependant_classes' );

		// Attach hooks which depend on WooCommerce constants
		//add_action( 'plugins_loaded', __CLASS__ . '::attach_dependant_hooks' );

		// WooCommerce 2.0 Notice
		//add_action( 'admin_notices', __CLASS__ . '::woocommerce_dependancy_notice' );

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

		add_action( 'woocommerce_product_write_panel_tabs', __CLASS__ . '::registration_dates_tab' );
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

	public static function registration_dates_tab() {
		echo '<li class="dates_tab show_if_registration"><a href="#dates_tab_data">' . __( 'Dates' , 'woocommerce-registrations') . '</a></li>';
	}
	/*
	 * Plugin House Keeping
	 */

	/**
	 * Called when WooCommerce is inactive to display an inactive notice.
	 *
	 * @since 0.1
	 */
	public static function woocommerce_inactive_notice() {
		if ( current_user_can( 'activate_plugins' ) ) :
			if ( ! is_woocommerce_active() ) : ?>
<div id="message" class="error">
	<p><?php printf( __( '%sWooCommerce Registrations is inactive.%s The %sWooCommerce plugin%s must be active for WooCommerce Subscriptions to work. Please %sinstall & activate WooCommerce%s', 'woocommerce-subscriptions' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . admin_url( 'plugins.php' ) . '">', '&nbsp;&raquo;</a>' ); ?></p>
</div>
		<?php elseif ( version_compare( get_option( 'woocommerce_db_version' ), '2.1', '<' ) ) : ?>
<div id="message" class="error">
	<p><?php printf( __( '%sWooCommerce Registrations is inactive.%s This version of Subscriptions requires WooCommerce 2.1 or newer. Please %supdate WooCommerce to version 2.1 or newer%s', 'woocommerce-subscriptions' ), '<strong>', '</strong>', '<a href="' . admin_url( 'plugins.php' ) . '">', '&nbsp;&raquo;</a>' ); ?></p>
</div>
		<?php endif; ?>
	<?php endif;
	}

	/**
	 * Checks on each admin page load if Course Products plugin is activated.
	 *
	 * @since 0.1
	 */
	public static function maybe_activate_woocommerce_registrations(){
		global $wpdb;

		$is_active = get_option( 'woocommerce_registrations_is_active', false );

		if ( $is_active == false ) {

			// Add the "Course" product type
			if ( ! get_term_by( 'slug', self::$name, 'product_type' ) ) {
				wp_insert_term( self::$name, 'product_type' );
			}

			add_option( 'woocommerce_registrations_is_active', true );

			//set_transient( self::$activation_transient, true, 60 * 60 );
			//do_action( 'woocommerce_registrations_activated' );
		}

	}

	/**
	 * Called when the plugin is deactivated. Deletes the course product type and fires an action.
	 *
	 * @since 0.1
	 */
	public static function deactivate_woocommerce_registrations() {

		delete_option( 'woocommerce_registrations_is_active' );

		//do_action( 'woocommerce_registrations_deactivated' );
	}

	/**
	 * Called on plugins_loaded to load any translation files.
	 *
	 * @since 0.1
	 */
	public static function load_plugin_textdomain(){

		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-course-products' );

		// Allow upgrade safe, site specific language files in /wp-content/languages/woocommerce-subscriptions/
		load_textdomain( 'woocommerce-course-products', WP_LANG_DIR.'/woocommerce/woocommerce-course-products-'.$locale.'.mo' );

		$plugin_rel_path = apply_filters( 'woocommerce_registrations_translation_file_rel_path', dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Then check for a language file in /wp-content/plugins/woocommerce-subscriptions/languages/ (this will be overriden by any file already loaded)
		load_plugin_textdomain( 'woocommerce-course-products', false, $plugin_rel_path );
	}

	/**
	 * Loads classes that depend on WooCommerce base classes.
	 *
	 * @since 1.2.4
	 */
	public static function load_dependant_classes() {
		global $woocommerce;

		if ( version_compare( $woocommerce->version, '2.0', '>=' ) ) {

			require_once( 'classes/class-wc-product-subscription.php' );

			require_once( 'classes/class-wc-product-subscription-variation.php' );

			require_once( 'classes/class-wc-product-variable-subscription.php' );
		}
	}

	/**
	 * Displays a notice to upgrade if using less than the ideal version of WooCommerce
	 *
	 * @since 1.0
	 */
	public static function woocommerce_dependancy_notice() {
		global $woocommerce;

		if ( version_compare( $woocommerce->version, '2.0', '<' ) && current_user_can( 'install_plugins' ) ) { ?>
			<div id="message" class="error">
				<p><?php printf( __( '%sYou have an out-of-date version of WooCommerce installed%s. WooCommerce Registrations no longer supports versions of WooCommerce prior to 2.0. Please %supgrade WooCommerce to version 2.0 or newer%s to avoid issues.', 'woocommerce-registrations' ), '<strong>', '</strong>', '<a href="' . admin_url( 'plugins.php' ) . '">', '</a>' ); ?></p>
			</div>
			<?php
		} elseif ( version_compare( $woocommerce->version, '2.0.16', '<' ) && current_user_can( 'install_plugins' ) ) { ?>
			<div id="message" class="error">
				<p><?php printf( __( '%sYou have an out-of-date version of WooCommerce installed%s. WooCommerce Registrations requires WooCommerce 2.0.16 or newer. Please %supdate WooCommerce to the latest version%s.', 'woocommerce-registrations' ), '<strong>', '</strong>', '<a href="' . admin_url( 'plugins.php' ) . '">', '</a>' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Check is the installed version of WooCommerce is 2.3 or older.
	 *
	 * @since 1.5.17
	 */
	public static function is_woocommerce_pre_2_3() {

		if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '2.3', '<' ) ) {
			$woocommerce_is_pre_2_3 = true;
		} else {
			$woocommerce_is_pre_2_3 = false;
		}

		return $woocommerce_is_pre_2_3;
	}

	/**
	 * Check is the installed version of WooCommerce is 2.2 or older.
	 *
	 * @since 1.5.10
	 */
	public static function is_woocommerce_pre_2_2() {

		if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, '2.2', '<' ) ) {
			$woocommerce_is_pre_2_2 = true;
		} else {
			$woocommerce_is_pre_2_2 = false;
		}

		return $woocommerce_is_pre_2_2;
	}

	/**
	 * Check is the installed version of WooCommerce is 2.1 or older.
	 *
	 * Only for use when we need to check version. If the code in question relys on a specific
	 * WC2.1 only function or class, then it's better to check that function or class exists rather
	 * than using this more generic check.
	 *
	 * @since 1.4.5
	 */
	public static function is_woocommerce_pre_2_1() {

		if ( ! defined( 'WC_VERSION' ) ) {
			$woocommerce_is_pre_2_1 = true;
		} else {
			$woocommerce_is_pre_2_1 = false;
		}

		return $woocommerce_is_pre_2_1;
	}

	/**
	 * Add WooCommerce error or success notice regardless of the version of WooCommerce running.
	 *
	 * @param  string $message The text to display in the notice.
	 * @param  string $notice_type The singular name of the notice type - either error, success or notice. [optional]
	 * @since version 1.4.5
	 */
	public static function add_notice( $message, $notice_type = 'success' ) {
		global $woocommerce;

		if ( function_exists( 'wc_add_notice' ) ) {

			wc_add_notice( $message, $notice_type );

		} else { // WC < 2.1

			if ( 'error' === $notice_type ) {
				$woocommerce->add_error( $message );
			} else {
				$woocommerce->add_message( $message );
			}

			$woocommerce->set_messages();

		}
	}

	/**
	 * Print WooCommerce messages regardless of the version of WooCommerce running.
	 *
	 * @since version 1.4.5
	 */
	public static function print_notices() {
		global $woocommerce;

		if ( function_exists( 'wc_print_notices' ) ) {

			wc_print_notices();

		} else { // WC < 2.1

			$woocommerce->show_messages();

		}
	}

	/* Deprecated Functions */

	/**
	 * Was called when a plugin is activated using official register_activation_hook() API
	 *
	 * Upgrade routine is now in @see maybe_activate_woocommerce_registrations()
	 *
	 * @since 1.0
	 */
	public static function activate_woocommerce_subscriptions(){
		_deprecated_function( __METHOD__, '1.1', __CLASS__ . '::maybe_activate_woocommerce_registrations()' );
	}

	/**
	 * Override the WooCommerce "Add to Cart" text with "Sign Up Now"
	 *
	 * @since 1.0
	 * @deprecated 1.5
	 */
	public static function add_to_cart_text( $button_text, $product_type = '' ) {
		global $product;

		_deprecated_function( __METHOD__, '1.1', 'WC_Product::add_to_cart_text()' );

		if ( WC_Subscriptions_Product::is_subscription( $product ) || in_array( $product_type, array( 'subscription', 'subscription-variation' ) ) ) {
			$button_text = get_option( WC_Subscriptions_Admin::$option_prefix . '_add_to_cart_button_text', __( 'Sign Up Now', 'woocommerce-subscriptions' ) );
		}

		return $button_text;
	}

	/**
	 * Subscriptions are individual items so override the WC_Product is_sold_individually function
	 * to reflect this.
	 *
	 * @since 1.0
	 * @deprecated 1.5
	 */
	public static function is_sold_individually( $is_individual, $product ) {

		_deprecated_function( __CLASS__ . '::' . __FUNCTION__, '1.1', 'WC_Product::is_sold_individually()' );

		return $is_individual;
	}
}

WC_Registrations::init();
