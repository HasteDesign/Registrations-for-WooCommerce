<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * WC_Report_Detailed_Registration_Event.
 *
 * @author      Shirkit, Allyson Souza
 * @category    Admin
 * @package     Registrations for WooCommerce/Reports
 * @version     1.0.0
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
				
			case 'phone' :
				return $row['phone'];

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
			'phone'   => __( 'Phone', 'registrations-for-woocommerce' ),
			'email'   => __( 'Email', 'registrations-for-woocommerce' ),
		);

		return $columns;
	}
	
	/**
	 * Get All orders IDs for a given product ID.
	 *
	 * @param  integer  $product_id (required)
	 * @param  array    $order_status (optional) Default is 'wc-completed'
	 *
	 * @return array
	 */
	static function get_orders_ids_by_product_id( $product_id, $order_status = array( 'wc-completed' ) ){
		global $wpdb;

		$results = $wpdb->get_col("
			SELECT order_items.order_id
			FROM {$wpdb->prefix}woocommerce_order_items as order_items
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
			LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
			WHERE posts.post_type = 'shop_order'
			AND posts.post_status IN ( '" . implode( "','", $order_status ) . "' )
			AND order_items.order_item_type = 'line_item'
			AND order_item_meta.meta_key = '_variation_id'
			AND order_item_meta.meta_value = '$product_id'
		");

		return $results;
	}

	/**
	 * Prepare customer list items.
	 */
	public function prepare_items() {

		$details = get_query_var( 'details', -1 );
		parse_str( $_SERVER['QUERY_STRING'] );

		$variation = wc_get_product( $details );
		$orders_ids = $this->get_orders_ids_by_product_id( $details, array( 'wc-processing', 'wc-completed' ));

		$registred = array();

		$variation_date = get_post_meta( $variation->get_id(), 'attribute_dates', true );

		foreach ( $orders_ids as $order_id ) {
			$order = wc_get_order ( $order_id);
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
									'email' => $participant['email'],
									'phone' => $participant['phone']
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
			'total_items' => count( $registred ),
			'per_page'    => count( $registred ),
			'total_pages' => 1
		) );
	}
}
