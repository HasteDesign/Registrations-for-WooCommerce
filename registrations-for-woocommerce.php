<?php
/**
 * Plugin Name: Registrations for WooCommerce
 * Plugin URI: https://www.hastedesign.com.br/lab/registrations-for-woocommerce/
 * Description: Add registration product type to your WooCommerce.
 * Version: 2.0.5
 * Author: Haste - design and technology, Allyson Souza, Anyssa Ferreira
 * Author URI: http://www.hastedesign.com.br
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: registrations-for-woocommerce
 * Domain Path: /languages
 * WC tested up to: 3.5
 * WC requires at least: 3.1
 *
 * Copyright 2018 Haste Design.  (email: contato@hastedesign.com.br)
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

defined( 'ABSPATH' ) || exit;

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) || ! function_exists( 'is_woocommerce_active' ) ) {
	require_once( 'includes/woo-includes/woo-functions.php' );
}

/**
 * Check if WooCommerce is active
 *
 * @since 0.0.1
 */
if ( ! is_woocommerce_active() ) {
	add_action( 'admin_notices', 'WC_Registrations::woocommerce_inactive_notice' );
	return;
}

// Registrations Classes
require_once( 'includes/class-wc-registrations-admin.php' );
require_once( 'includes/class-wc-registrations-cart.php' );
//require_once( 'includes/admin/class-registrations-settings.php' );
require_once( 'includes/admin/class-wc-registrations-orders.php' );
require_once( 'includes/reports/class-wc-reports-manager.php' );

/**
 * The main registrations products class.
 *
 * @since 1.0
 */
class WC_Registrations {

	/**
	 * Plugin name
	 * 
	 * @var string $name
	 */
	public static $name = 'registrations';

	/**
	 * Activation transient
	 * 
	 * @var string $name
	 */
	public static $activation_transient = 'woocommerce_registrations_activated';

	/**
	 * Plugin main file
	 * 
	 * @var string $name
	 */
	public static $plugin_file = __FILE__;

	/**
	 * Version number
	 * 
	 * @var string $name
	 */
	public static $version = '2.0.5';

	/**
	 * Hook into action and filters
	 *
	 * @since 1.0
	 **/
	public static function init() {
		// Fired on deactivation of Registrations for WooCommerce
		register_deactivation_hook( __FILE__, __CLASS__ . '::deactivate_woocommerce_registrations' );

		// Activates Registrations for WooCommerce
		add_action( 'admin_init', __CLASS__ . '::maybe_activate_woocommerce_registrations' );

		// Changes "add to cart" to "sign up now"
		add_action( 'woocommerce_registrations_add_to_cart', __CLASS__ . '::registrations_add_to_cart', 10 );

		// Load translation
		add_action( 'plugins_loaded', __CLASS__ . '::load_plugin_textdomain' );

		// Load WooCommerce dependant classes
		add_action( 'plugins_loaded', __CLASS__ . '::load_dependant_classes' );

		// Register a new data store
		add_filter( 'woocommerce_data_stores', __CLASS__ . '::register_data_stores', 10, 1 );
	}

	/**
	 * Deletes the woocommerce_registrations_is_active option and fires an action.
	 *
	 * @since 0.1
	 */
	public static function deactivate_woocommerce_registrations() {
		delete_option( 'woocommerce_registrations_is_active' );
		do_action( 'woocommerce_registrations_deactivated' );
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
	 * Load translations
	 *
	 * @since 1.0
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( 'registrations-for-woocommerce', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Activate Registrations for WooCommerce if it's not activated yet.
	 *
	 * @since 1.0
	 */
	public static function maybe_activate_woocommerce_registrations() {
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
	 * Register data stores for registrations.
	 * 
	 * @since 2.0
	 * 
	 * @param  array  $data_stores
	 * @return array
	 */
	public static function register_data_stores( $data_stores = array() ) {
	    $data_stores['product-registrations'] = 'WC_Product_Variable_Data_Store_CPT';
	    return $data_stores;
	}

	/**
	 * Load registrations add to cart right template
	 * 
	 * @since 1.0
	 */
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
	 * When WooCommerce is inactive display a notice.
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
}

WC_Registrations::init();
