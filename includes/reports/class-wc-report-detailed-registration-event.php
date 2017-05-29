<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * WC_Report_Customer_List.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin/Reports
 * @version     2.1.0
 */
class WC_Report_Detailed_Registration_Event extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {

		parent::__construct( array(
			'singular'  => __( 'Customer', 'woocommerce' ),
			'plural'    => __( 'Customers', 'woocommerce' ),
			'ajax'      => false
		) );
	}

	/**
	 * No items found text.
	 */
	public function no_items() {
		_e( 'No customers found.', 'woocommerce' );
	}

	/**
	 * Output the report.
	 */
	public function output_report() {

		$this->prepare_items();

		$this->display();
	}

	/**
	 * Get column value.
	 *
	 * @param  WP_User $user.
	 * @param  string  $column_name.
	 * @return string
	 */
	public function column_default( $row, $column_name ) {
		global $wpdb;

		switch ( $column_name ) {

			case 'name' :
				return $row['name'];

			case 'email' :
				return $row['email'];

		}

		return '';
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'name'    => __( 'Name', 'registrations-for-woocommerce' ),
			'email'   => __( 'Email', 'registrations-for-woocommerce' ),
		);

		return $columns;
	}

	/**
	 * Prepare customer list items.
	 */
	public function prepare_items() {

		$args2 = array(
			'post_type'   => 'shop_order',
			'post_status' => array( 'wc-processing', 'wc-completed' ),
		);

		$orders_query = get_posts( $args2 );

		$orders = array();
		$variations = array();
		$products = array();

		foreach ( $orders_query as $order_query ) {
			$order = wc_get_order( $order_query );
			$orders[] = $order;
		}

		$details = get_query_var( 'details', -1 );
		parse_str( $_SERVER['QUERY_STRING'] );

		$variation = wc_get_product( $details );
		$found = array();

		// Only save the oders that contain this variation
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				if ( $variation->get_id() == $item->get_variation_id() ) {
					$found[] = $order;
				}
			}
		}


		$registred = array();

		$variation_date = get_post_meta( $variation->get_id(), 'attribute_dates', true );

		foreach ( $found as $order ) {
			// Grab the registrations data
			$registration_meta = maybe_unserialize( get_post_meta( $order->get_id(), '_registrations_order_meta', true ) );
			if ( ! empty( $registration_meta ) ) {
				foreach ( $registration_meta as $registration ) {
					if ( ! empty( $registration['date'] ) ) {
						// Filter just only for the correct variation
						if ( strpos( $registration['date'], $variation_date ) ) {
							if( ! empty( $registration['participants'] ) ) {
							foreach ( $registration['participants'] as $participant ) {
								array_push( $registred, array(
									'name' => $participant['name'] . ' ' . $participant['surname'],
									'email' => $participant['email']
									));
								}
							}
						}
					}
				}
			}
		}

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->items = $registred;

		/**
		 * Pagination.
		 */
		$this->set_pagination_args( array(
			'total_items' => count( $found ),
			'per_page'    => count( $found ),
			'total_pages' => 1
		) );
	}
}
