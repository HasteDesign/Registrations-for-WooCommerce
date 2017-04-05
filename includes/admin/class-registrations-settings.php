<?php
/**
 * Registrations for WooCommerce admin settings.
 *
 * @package Registrations for WooCommerce/Admin/Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registrations_Settings class.
 */
class Registrations_Settings {

	/**
	 * Initialize the settings.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 59 );
		add_action( 'admin_init', array( $this, 'plugin_settings' ) );
	}

	/**
	 * Add the settings page.
	 */
	public function settings_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Registrations for WooCommerce - Settings', 'registrations-for-woocommerce' ),
			__( 'Registrations Settings', 'registrations-for-woocommerce' ),
			'manage_options',
			'registrations-for-woocommerce',
			array( $this, 'html_settings_page' )
		);
	}

	/**
	 * Render the settings page for this plugin.
	 */
	public function html_settings_page() {
		include dirname( __FILE__ ) . '/views/html-settings-page.php';
	}

	/**
	 * Plugin settings form fields.
	 */
	public function plugin_settings() {
		$option = 'registrations_settings';

		// Set Custom Fields cection.
		add_settings_section(
			'checkout_section',
			__( 'Checkout', 'registrations-for-woocommerce' ),
			array( $this, 'section_options_callback' ),
			$option
		);

		// Create user.
		add_settings_field(
			'create_user',
			__( 'Create user for participants', 'registrations-for-woocommerce' ),
			array( $this, 'checkbox_element_callback' ),
			$option,
			'checkout_section',
			array(
				'menu' => $option,
				'id' => 'create_user',
				'description' => __( 'If checked Registrations fo WooCommerce will create a user for each participant in a registration.', 'registrations-for-woocommerce' ),
			)
		);

		// Set Custom Fields cection.
		add_settings_section(
			'product_section',
			__( 'Product', 'registrations-for-woocommerce' ),
			array( $this, 'section_options_callback' ),
			$option
		);

		// Date display options.
		add_settings_field(
			'date_display',
			__( 'Date display option', 'registrations-for-woocommerce' ),
			array( $this, 'select_element_callback' ),
			$option,
			'product_section',
			array(
				'menu' => $option,
				'id' => 'date_display',
				'description' => __( 'Select the date display type in product pages.', 'registrations-for-woocommerce' ),
				'options' => array(
					0 => __( 'Select', 'registrations-for-woocommerce' ),
					1 => __( 'Radio', 'registrations-for-woocommerce' ),
					2 => __( 'List', 'registrations-for-woocommerce' ),
					3 => __( 'Table', 'registrations-for-woocommerce' ),
				),
			)
		);

		// Register settings.
		register_setting( $option, $option, array( $this, 'validate_options' ) );
	}

	/**
	 * Section null fallback.
	 */
	public function section_options_callback() {

	}

	/**
	 * Checkbox element fallback.
	 *
	 * @param array $args Callback arguments.
	 */
	public function checkbox_element_callback( $args ) {
		$menu    = $args['menu'];
		$id      = $args['id'];
		$options = get_option( $menu );

		if ( isset( $options[ $id ] ) ) {
			$current = $options[ $id ];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : '0';
		}

		include dirname( __FILE__ ) . '/views/html-checkbox-field.php';
	}

	/**
	 * Select element fallback.
	 *
	 * @param array $args Callback arguments.
	 */
	public function select_element_callback( $args ) {
		$menu    = $args['menu'];
		$id      = $args['id'];
		$options = get_option( $menu );

		if ( isset( $options[ $id ] ) ) {
			$current = $options[ $id ];
		} else {
			$current = isset( $args['default'] ) ? $args['default'] : 0;
		}

		include dirname( __FILE__ ) . '/views/html-select-field.php';
	}

	/**
	 * Valid options.
	 *
	 * @param  array $input options to valid.
	 *
	 * @return array        validated options.
	 */
	public function validate_options( $input ) {
		$output = array();

		// Loop through each of the incoming options.
		foreach ( $input as $key => $value ) {
			// Check to see if the current option has a value. If so, process it.
			if ( isset( $input[ $key ] ) ) {
				$output[ $key ] = woocommerce_clean( $input[ $key ] );
			}
		}

		return $output;
	}
}

new Registrations_Settings();
