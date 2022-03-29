<?php

namespace Haste\RegistrationsForWoo\Admin;

use Haste\RegistrationsForWoo\Products;

/**
 * Registrations Admin Class
 *
 * Manage panel features and resources of Registrations and provide some general helper functions.
 *
 * @package     Registrations for WooCommerce\WC_Registrations_Admin
 * @author      Allyson Souza
 * @since       1.0
 */
class Product {
	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 */
	public static function init() {
		// Load registration add to cart template
		add_action( 'woocommerce_registrations_add_to_cart', __CLASS__ . '::registrations_add_to_cart_template', 10 );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::enqueue_styles_scripts' );

		// Product edit
		add_filter( 'product_type_selector', __CLASS__ . '::add_registrations_to_select' );
		add_action( 'woocommerce_product_after_variable_attributes', __CLASS__ . '::variable_registration_pricing_fields', 10, 3 );
		add_filter( 'woocommerce_product_data_tabs', __CLASS__ . '::registrations_dates_tab' );
		add_action( 'admin_head', __CLASS__ . '::registrations_dates_tab_icon' );
		add_action( 'woocommerce_product_data_panels', __CLASS__ . '::show_dates_tab_content' );
		add_action( 'woocommerce_product_options_inventory_product_data', __CLASS__ . '::past_events_fields' );

		// Saves registrations meta (product and variations)
		add_action( 'woocommerce_save_product_variation', __CLASS__ . '::save_variation_meta', 10, 2 );
		add_action( 'woocommerce_ajax_save_product_variation', __CLASS__ . '::save_variation_meta', 10, 2 );
		add_action( 'woocommerce_process_product_meta', __CLASS__ . '::save_product_meta' );

		// Filter dates variations options name and display correctly for each date type (single, multiple, and range)
		add_filter( 'woocommerce_variation_option_name', __CLASS__ . '::registration_variation_option_name' );
		add_filter( 'woocommerce_attribute', __CLASS__ . '::registration_variation_filter_additional_information', 10, 3 );
		add_filter( 'woocommerce_display_item_meta', __CLASS__ . '::registration_filter_display_item_meta', 10, 3 );
		add_filter( 'woocommerce_attribute_label', __CLASS__ . '::registration_attribute_label', 10, 3 );
	}

	/**
	 * Enqueue styles.
	 *
	 * @since 1.0
	 */
	public static function enqueue_styles_scripts() {

		if ( self::is_woocommerce_screen() ) {

			$dependencies  = self::product_edit_script_dependencies();
			$script_params = self::product_edit_script_params();

			// Registrations for WooCommerce Admin - admin.js
			wp_enqueue_script( 'woocommerce_registrations_admin', plugin_dir_url( \Haste\RegistrationsForWoo\RegistrationsForWoo::$plugin_file ) . '/assets/js/admin.js', $dependencies, filemtime( plugin_dir_path( \Haste\RegistrationsForWoo\RegistrationsForWoo::$plugin_file ) . 'assets/js/admin.js' ) );
			wp_localize_script( 'woocommerce_registrations_admin', 'WCRegistrations', apply_filters( 'woocommerce_registrations_admin_script_parameters', $script_params ) );

			// Registrations for WooCommerce Ajax - wc-registrations-ajax.js
			wp_enqueue_script( 'woocommerce_registrations_ajax', plugin_dir_url( \Haste\RegistrationsForWoo\RegistrationsForWoo::$plugin_file ) . '/assets/js/wc-registrations-ajax.js', $dependencies, filemtime( plugin_dir_path( \Haste\RegistrationsForWoo\RegistrationsForWoo::$plugin_file ) . 'assets/js/wc-registrations-ajax.js' ) );
			wp_localize_script( 'woocommerce_registrations_ajax', 'WCRegistrations', apply_filters( 'woocommerce_registrations_admin_script_parameters', $script_params ) );

			// jQuery UI Datepicker
			wp_enqueue_style( 'jquery-ui-datepicker' );
		}
	}

	/**
	 * Adds 'registration' product type to select
	 *
	 * @since 1.0
	 *
	 * @param array Array of Product types & their labels, excluding the Course product type.
	 * @return array Array of Product types & their labels, including the Course product type.
	 */
	public static function add_registrations_to_select( $product_types ) {
		$product_types[ \Haste\RegistrationsForWoo\RegistrationsForWoo::$name ] = __( 'Registration', 'registrations-for-woocommerce' );

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
	 * Create the interface in the "Edit Product" admin page for the past event filter.
	 *
	 * @since 1.0.7
	 */
	public static function past_events_fields() {
		echo '<div class="options_group registration_inventory">';

		woocommerce_wp_text_input(
			array(
				'id'                => '_days_to_prevent',
				'label'             => __( 'Allow registrations until', 'registrations-for-woocommerce' ),
				'wrapper_class'     => 'show_if_registration',
				'placeholder'       => '',
				'description'       => __( 'day(s) before the event.', 'registrations-for-woocommerce' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
				),
			)
		);

		echo '</div>';
	}

	/**
	 * Save product meta
	 *
	 * @since 1.0.7
	 */
	public static function save_product_meta( $post_id ) {
		// Days to prevent
		$_days_to_prevent = isset( $_POST['_days_to_prevent'] ) ? $_POST['_days_to_prevent'] : '';
		update_post_meta( $post_id, '_days_to_prevent', esc_attr( $_days_to_prevent ) );
	}

	/**
	 * Save variation meta
	 *
	 * @since 1.0
	 */
	public static function save_variation_meta( $variation_id, $i ) {
		// Start time
		$event_start_time = isset( $_POST['_event_start_time'][ $i ] ) ? $_POST['_event_start_time'][ $i ] : '';
		if ( ! empty( $event_start_time ) ) {
			update_post_meta( $variation_id, '_event_start_time', stripslashes( $event_start_time ) );
		}

		// End time
		$event_end_time = isset( $_POST['_event_end_time'][ $i ] ) ? $_POST['_event_end_time'][ $i ] : '';
		if ( ! empty( $event_end_time ) ) {
			update_post_meta( $variation_id, '_event_end_time', stripslashes( $event_end_time ) );
		}

		// Week days
		$week_days = isset( $_POST['_week_days'][ $i ] ) ? $_POST['_week_days'][ $i ] : '';
		if ( ! empty( $week_days ) ) {
			update_post_meta( $variation_id, '_week_days', $week_days );
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
	public static function registrations_dates_tab( $tabs ) {
		// Adds the new dates tab
		$tabs['dates'] = array(
			'label'  => __( 'Dates', 'registrations-for-woocommerce' ),
			'target' => 'registration_dates',
			'class'  => array( 'show_if_registration' ),
		);

		return $tabs;
	}

	/**
	 * Registrations dates tab icon
	 *
	 * @since 2.1
	 */
	public static function registrations_dates_tab_icon() {
		?>
		<style>
			#woocommerce-product-data ul.wc-tabs li.dates_options a:before { font-family: WooCommerce; content: '\e00e'; }
		</style>
		<?php
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
		// If variation $option is a JSON, then try to get the formatted date
		if ( json_decode( $option ) ) {
			$date = Products\Formatter::get_formatted_date( $option, $date_format );

			return $date;
		}

		return $option;
	}

	/**
	 * Filter dates exhibition
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
		if ( $attribute['name'] === 'Dates' ) {
			$dates       = array();
			$date_format = get_option( 'date_format' );

			foreach ( $attribute->get_options() as $date ) {
				$dates[] = Products\Formatter::get_formatted_date( $date );

			}

			return wptexturize( implode( ', ', $dates ) );
		} else {
			return $values_sanitized;
		}
	}

	/**
	 * Filter dates exhibition
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
			$value     = $args['autop'] ? wp_kses_post( $meta->display_value ) : apply_filters( 'woocommerce_variation_option_name', wp_kses_post( make_clickable( trim( strip_tags( $meta->display_value ) ) ) ) );
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
	 * @return string $label    filtered date attribute name
	 */
	public static function registration_attribute_label( $label, $name, $product ) {
		if ( $name === 'Dates' || $name === 'dates' ) {
			return __( 'Dates', 'registrations-for-woocommerce' );
		}

		return $label;
	}

	/**
	 * Load registrations add to cart right template
	 *
	 * @since 1.0
	 */
	public static function registrations_add_to_cart_template() {
		global $product;

		// Enqueue variation scripts
		wp_enqueue_script( 'wc-add-to-cart-variation' );

		// Get Available variations?
		$get_variations       = sizeof( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
		$available_variations = $get_variations ? $product->get_available_variations() : false;

		// Load the template
		wc_get_template(
			'single-product/add-to-cart/registration.php',
			array(
				'available_variations' => $available_variations,
				'attributes'           => apply_filters( 'registrations_available_attributes', $product->get_variation_attributes(), $product->get_id() ),
				'selected_attributes'  => $product->get_default_attributes(),
			),
			'',
			plugin_dir_path( \Haste\RegistrationsForWoo\RegistrationsForWoo::$plugin_file ) . 'templates/'
		);
	}

	/**
	 * Return script dependency array
	 *
	 * Verify wich panel page is been displayed and return the right array with
	 * script dependencies.
	 *
	 * @return Array $dependencies  An array with dependency scripts for registrations.
	 */
	private static function product_edit_script_dependencies() {
		$dependencies = array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' );

		if ( get_current_screen()->id == 'product' ) {
			$dependencies[] = 'wc-admin-meta-boxes';
			$dependencies[] = 'wc-admin-product-meta-boxes';
			$dependencies[] = 'wc-admin-variation-meta-boxes';
		}

		return $dependencies;
	}

	/**
	 * Return script params
	 *
	 * Define script params for registrations js
	 *
	 * @return array $script_params An array of parameters to be passed to scripts
	 */
	private static function product_edit_script_params() {
		global $woocommerce;

		if ( get_current_screen()->id == 'product' ) {
			$script_params = array(
				'productType' => \Haste\RegistrationsForWoo\RegistrationsForWoo::$name,
			);
		}

		$script_params['ajaxLoaderImage'] = $woocommerce->plugin_url() . '/assets/images/ajax-loader.gif';
		$script_params['ajaxUrl']         = admin_url( 'admin-ajax.php' );

		return $script_params;
	}

	public static function is_woocommerce_screen() {
		$screen = get_current_screen();
		return in_array( $screen->id, array( 'product', 'edit-shop_order', 'shop_order', 'users', 'woocommerce_page_wc-settings' ) ) ? true : false;
	}

}
