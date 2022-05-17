<?php

namespace Haste\RegistrationsForWoo\Admin;

use Haste\RegistrationsForWoo\Products;

defined( 'ABSPATH' ) || exit;

class ProductEdit {

	public static function init() {
		add_filter( 'product_type_selector', __CLASS__ . '::add_registrations_to_select' );
		add_filter( 'woocommerce_product_data_tabs', __CLASS__ . '::registrations_dates_tab' );
		add_action( 'admin_head', __CLASS__ . '::registrations_dates_tab_icon' );
		add_action( 'woocommerce_product_data_panels', __CLASS__ . '::show_dates_tab_content' );
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::enqueueScripts' );
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
		?>
		<div id="registration_dates" class="panel woocommerce_options_panel wc-metaboxes-wrapper">
			<div id="registrations-root"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue settings page scripts.
	 * 
	 * Enqueue registration settings scripts, with wp-element as dependency
	 * in order to make WordPress Core React available.
	 * 
	 * @see: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-element/
	 *
	 * @return [type]
	 */
	public static function enqueueScripts() {
		$screen = get_current_screen();

		if ( 'post' === $screen->base && 'product' === $screen->post_type ) {
			
			wp_enqueue_script(
				'registrations-settings',
				plugins_url( '../../assets/js/product.js', __FILE__ ),
				array( 'wp-element' ),
				'',
				true
			);

			wp_enqueue_style(
				'registrations-settings',
				plugins_url( '../../assets/css/product.css', __FILE__ ),
				array(),
				''
			);
		}
	}
}