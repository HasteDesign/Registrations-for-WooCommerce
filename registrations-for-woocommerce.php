<?php
/**
 * Plugin Name: Registrations for WooCommerce
 * Plugin URI: http://www.hastedesign.com.br
 * Description: Add registration product type to your WooCommerce.
 * Author: Haste - design and technology, Allyson Souza, Anyssa Ferreira
 * Author URI: http://www.hastedesign.com.br
 * Version: 1.0.7
 * Text Domain: registrations-for-woocommerce
 * Domain Path: /languages
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
 * @package		Registrations for WooCommerce
 * @author		Allyson Souza
 * @since		1.0
 */

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) || ! function_exists( 'is_woocommerce_active' ) ) {
	require_once( 'includes/woo-includes/woo-functions.php' );
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

require_once( 'includes/class-wc-registrations-admin.php' );

require_once( 'includes/class-wc-registrations-cart.php' );

require_once( 'includes/admin/class-registrations-settings.php' );

require_once( 'includes/reports/class-wc-reports-manager.php' );

/**
 * The main registrations products class.
 *
 * @since 1.0
 */
class WC_Registrations {

	public static $name = 'registrations';
	public static $activation_transient = 'woocommerce_registrations_activated';
	public static $plugin_file = __FILE__;
	public static $version = '1.0.6';

	/**
	 * Set up the class, including it's hooks & filters, when the file is loaded.
	 *
	 * @since 1.0
	 **/
	public static function init() {
		add_action( 'admin_init', __CLASS__ . '::maybe_activate_woocommerce_registrations' );
		register_deactivation_hook( __FILE__, __CLASS__ . '::deactivate_woocommerce_registrations' );

		// Override the WC default "Add to Cart" text to "Sign Up Now" (in various places/templates)
		add_action( 'woocommerce_registrations_add_to_cart', __CLASS__ . '::registrations_add_to_cart', 10 );

		// Load translation files
		add_action( 'plugins_loaded', __CLASS__ . '::load_plugin_textdomain' );

		// Load dependant files
		add_action( 'plugins_loaded', __CLASS__ . '::load_dependant_classes' );

		// Register the custom data store
		add_filter( 'woocommerce_data_stores', __CLASS__ . '::register_data_stores', 10, 1 );
	}

	/**
	 * Register data stores for registrations.
	 *
	 * @param  array  $data_stores
	 * @return array
	 */
	public static function register_data_stores( $data_stores = array() ) {
	    $data_stores['product-registrations'] = 'WC_Product_Variable_Data_Store_CPT';
	    return $data_stores;
	}

	public static function registrations_add_to_cart() {
		global $product;

		// Enqueue variation scripts
		wp_enqueue_script( 'wc-add-to-cart-variation' );

		// Get Available variations?
		$get_variations = sizeof( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );

		// Load the template
		wc_get_template(
			'single-product/add-to-cart/registration.php',
			array(
				'available_variations' => $get_variations ? $product->get_available_variations() : false,
				'attributes'           => $product->get_variation_attributes(),
				'selected_attributes'  => $product->get_default_attributes(),
			),
			'',
			plugin_dir_path( __FILE__ ) . 'templates/'
		);
	}

	/**
	 * Called when WooCommerce is inactive to display an inactive notice.
	 *
	 * @since 1.0
	 */
	public static function woocommerce_inactive_notice() {
		if ( current_user_can( 'activate_plugins' ) ) :
			if ( ! is_woocommerce_active() ) : ?>
				<div id="message" class="error">
					<p><?php printf( __( '%sRegistrations for WooCommerce is inactive.%s The %sWooCommerce plugin%s must be active for Registrations for WooCommerce to work. Please %sinstall & activate WooCommerce%s', 'registrations-for-woocommerce' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . admin_url( 'plugin-install.php?s=WooCommerce&tab=search&type=term' ) . '">', '&nbsp;&raquo;</a>' ); ?></p>
				</div>
						<?php elseif ( version_compare( get_option( 'woocommerce_db_version' ), '2.1', '<' ) ) : ?>
				<div id="message" class="error">
					<p><?php printf( __( '%sRegistrations for WooCommerce is inactive.%s This version of Registrations requires WooCommerce 2.1 or newer. Please %supdate WooCommerce to version 2.1 or newer%s', 'registrations-for-woocommerce' ), '<strong>', '</strong>', '<a href="' . admin_url( 'plugin-install.php?s=WooCommerce&tab=search&type=term' ) . '">', '&nbsp;&raquo;</a>' ); ?></p>
				</div>
			<?php endif; ?>
		<?php endif;
	}

	/**
	 * Checks on each admin page load if Registrations for WooCommerce is activated.
	 *
	 * @since 1.0
	 */
	public static function maybe_activate_woocommerce_registrations(){
		global $wpdb;

		$is_active = get_option( 'woocommerce_registrations_is_active', false );

		if ( $is_active == false ) {

			// Add the "Registrations" product type
			if ( ! get_term_by( 'slug', self::$name, 'product_type' ) ) {
				wp_insert_term( self::$name, 'product_type' );
			}

			add_option( 'woocommerce_registrations_is_active', true );
			set_transient( self::$activation_transient, true, 60 * 60 );
			do_action( 'woocommerce_registrations_activated' );
		}

	}

	/**
	 * Called when the plugin is deactivated. Deletes the woocommerce_registrations_is_active and fires an action.
	 *
	 * @since 0.1
	 */
	public static function deactivate_woocommerce_registrations() {
		delete_option( 'woocommerce_registrations_is_active' );
		do_action( 'woocommerce_registrations_deactivated' );
	}

	/**
	 * Called on plugins_loaded to load any translation files.
	 *
	 * @since 1.0
	 */
	public static function load_plugin_textdomain(){
		load_plugin_textdomain( 'registrations-for-woocommerce', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Loads classes that depend on WooCommerce base classes.
	 *
	 * @since 1.2.4
	 */
	public static function load_dependant_classes() {
		global $woocommerce;

		if ( version_compare( $woocommerce->version, '2.0', '>=' ) ) {
			require_once( 'includes/class-wc-product-registrations.php' );
			require_once( 'includes/class-wc-registrations-checkout.php' );
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
				<p><?php printf( __( '%sYou have an out-of-date version of WooCommerce installed%s. Registrations for WooCommerce no longer supports versions of WooCommerce prior to 2.0. Please %supgrade WooCommerce to version 2.0 or newer%s to avoid issues.', 'registrations-for-woocommerce' ), '<strong>', '</strong>', '<a href="' . admin_url( 'plugins.php' ) . '">', '</a>' ); ?></p>
			</div>
			<?php
		} elseif ( version_compare( $woocommerce->version, '2.0.16', '<' ) && current_user_can( 'install_plugins' ) ) { ?>
			<div id="message" class="error">
				<p><?php printf( __( '%sYou have an out-of-date version of WooCommerce installed%s. Registrations for WooCommerce requires WooCommerce 2.0.16 or newer. Please %supdate WooCommerce to the latest version%s.', 'registrations-for-woocommerce' ), '<strong>', '</strong>', '<a href="' . admin_url( 'plugins.php' ) . '">', '</a>' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Check is the installed version of WooCommerce is 2.3 or older.
	 *
	 * @since 1.0
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
	 * @since 1.0
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
	 * @since 1.0
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
	 * @since version 1.0
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
	 * @since version 1.0
	 */
	public static function print_notices() {
		global $woocommerce;

		if ( function_exists( 'wc_print_notices' ) ) {

			wc_print_notices();

		} else { // WC < 2.1

			$woocommerce->show_messages();

		}
	}
}
WC_Registrations::init();
