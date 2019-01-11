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

/**
 * The main registrations products class.
 *
 * This class is intended to install and create the ground of registrations for WooCommerce to work.
 *
 * @package		Registrations for WooCommerce\WC_Registrations_Admin
 * @author		Allyson Souza
 * @since		1.0
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
	public static $activation_transient = 'registrations_for_woocommerce_activated';

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

		// Welcome notice
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::activation_notices' );

		// Load translation
		add_action( 'plugins_loaded', __CLASS__ . '::load_plugin_textdomain' );

		// Load includes
		add_action( 'plugins_loaded', __CLASS__ . '::includes' );

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
	 * Loads classes
	 *
	 * @since 1.2.4
	 */
	public static function includes() {
		require_once( 'includes/class-wc-product-registrations.php' );
		require_once( 'includes/class-wc-registrations-checkout.php' );
		require_once( 'includes/class-wc-registrations-admin.php' );
		require_once( 'includes/class-wc-registrations-cart.php' );
		require_once( 'includes/class-wc-registrations-helpers.php' );
		require_once( 'includes/admin/class-wc-registrations-orders.php' );
		require_once( 'includes/reports/class-wc-reports-manager.php' );
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
			do_action( 'registrations_for_woocommerce_activated' );
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

	/**
	 * Display notices on registrations activation
	 *
	 * @since 1.0
	 */
	public static function activation_notices() {
		global $woocommerce, $post;

		$is_activation_screen  = ( get_transient( self::$activation_transient ) == true ) ? true : false;

		if ( $is_activation_screen ) {
			
			if ( ! isset( $_GET['page'] ) || 'wcs-about' != $_GET['page'] ) {
				add_action( 'admin_notices', __CLASS__ . '::admin_installed_notice' );
			}

			delete_transient( self::$activation_transient );
		}
	}

	/**
	 * Display a welcome message when Registrations is activated
	 *
	 * @since 1.0
	 */
	public static function admin_installed_notice() {
		?>
		<div class="updated notice notice-success is-dismissible">
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

WC_Registrations::init();
