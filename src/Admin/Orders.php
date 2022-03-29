<?php
namespace Haste\RegistrationsForWoo\Admin;

/**
 * Registrations Orders Class
 *
 * Add Registration details to the Orders page.
 *
 * @package   Registrations for WooCommerce
 * @category  Class
 * @author    Aaron Lowndes
 * @since   2.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Orders {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 * @access public
	 */
	public static function init() {
		// Add new shop order column
		add_filter( 'manage_edit-shop_order_columns', __CLASS__ . '::add_registration_details_column' );
		// And fill the shop order column with the registrations data
		add_action( 'manage_shop_order_posts_custom_column', __CLASS__ . '::add_registration_details_column_content' );
	}

	/**
	 * Create the new column in the "Orders" admin page for registrations.
	 *
	 * @since 1.0.7
	 */
	public static function add_registration_details_column( $columns ) {
		$new_columns = ( is_array( $columns ) ) ? $columns : array();
		unset( $new_columns['billing_address'] );
		$new_columns['registration_booked'] = 'Registration(s)';
		$new_columns['billing_address']     = $columns['billing_address'];
		return $new_columns;
	}

	/**
	 * Adds the data to the new column in the Orders admin page.
	 *
	 * @since 1.0.7
	 */
	public static function add_registration_details_column_content( $column ) {
		global $post, $the_order;

		if ( empty( $the_order ) || $the_order->get_id() != $post->ID ) {
			$the_order = wc_get_order( $post->ID );
		}

		if ( $column === 'registration_booked' ) {
			echo \WC_Registrations_Checkout::registrations_field_display_admin_order_meta( $the_order );
		}
	}
}
