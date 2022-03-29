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
 * @package     Registrations for WooCommerce
 * @author      Allyson Souza
 * @since       1.0
 */

namespace Haste\RegistrationsForWoo;

use Haste\RegistrationsForWoo\Admin,
	Haste\RegistrationsForWoo\Products,
	Haste\RegistrationsForWoo\DataTransfer,
	Haste\RegistrationsForWoo\Checkout;


defined( 'ABSPATH' ) || exit;

require 'vendor/autoload.php';

/**
 * The main registrations products class.
 *
 * This class is intended to install and create the ground of registrations for WooCommerce to work.
 *
 * @package     Registrations for WooCommerce\WC_Registrations_Admin
 * @author      Allyson Souza
 * @since       1.0
 */
final class RegistrationsForWoo {

	/**
	 * Plugin name
	 *
	 * @var string $name
	 */
	public static $name = 'registrations';

	/**
	 * Plugin main file
	 *
	 * @var string $name
	 */
	public static $plugin_file = __FILE__;

	/**
	 * Hook into action and filters
	 *
	 * @since 1.0
	 **/
	public static function init() {
		Admin\Notices::init();
		Products\Cart::init();
		DataTransfer\Import::init();
		DataTransfer\Export::init();
		Admin\Orders::init();
		Checkout\Checkout::init();
		Admin\Product::init();

		// Fired on deactivation of Registrations for WooCommerce
		register_deactivation_hook( __FILE__, __CLASS__ . '::deactivate_woocommerce_registrations' );

		// Add the "Registrations" product type
		add_action( 'admin_init', __CLASS__ . '::create_registration_product_type' );

		// Load translation
		add_action( 'plugins_loaded', __CLASS__ . '::load_plugin_textdomain' );

		// Load includes
		add_action( 'plugins_loaded', __CLASS__ . '::includes' );

		// Register a new data store
		add_filter( 'woocommerce_data_stores', __CLASS__ . '::register_data_stores', 10, 1 );
	}

	/**
	 * Check if WooCommerce is activated.
	 */
	public static function is_woocommerce_activated() {
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( in_array( 'woocommerce/woocommerce.php', $active_plugins, true ) ) {
			return true;
		}

		add_action( 'admin_notices', 'Haste\RegistrationsForWoo\Admin\Notices::woocommerce_inactive_notice' );

		return false;
	}

	/**
	 * Deletes the woocommerce_registrations_is_active option and fires an action.
	 *
	 * @since 0.1
	 */
	public static function deactivate_woocommerce_registrations() {
		delete_option( 'rfwoo_is_active' );
		do_action( 'woocommerce_registrations_deactivated' );
	}

	/**
	 * Loads classes
	 *
	 * @since 1.2.4
	 */
	public static function includes() {
		require_once( 'src/Products/WC_Product_Registrations.php' );
	}

	/**
	 * Load translations
	 *
	 * @since 1.0
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain( 'registrations-for-woocommerce', false, basename( dirname( __FILE__ ) ) . '/languages/' );
	}


	/**
	 * Add the "Registrations" product type
	 *
	 * @return void
	 */
	public static function create_registration_product_type() {
		return ! get_term_by( 'slug', self::$name, 'product_type' ) ? wp_insert_term( self::$name, 'product_type' ) : false;
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
}

if ( RegistrationsForWoo::is_woocommerce_activated() ) {
	RegistrationsForWoo::init();
}
