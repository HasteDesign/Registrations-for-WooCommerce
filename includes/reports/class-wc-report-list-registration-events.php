<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * WC_Report_List_Registration_Events.
 *
 * @author      Shirkit, Allyson Souza
 * @category    Admin
 * @package     Registrations for WooCommerce/Reports
 * @version     1.0.0
 */
class WC_Report_List_Registration_Events extends WP_List_Table {

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

		$details = get_query_var( 'details', -1 );
		parse_str( $_SERVER['QUERY_STRING'] );

		if ( $details != -1 ) {

			require_once( plugin_dir_path( __FILE__ ) . 'class-wc-report-detailed-registration-event.php' );
			$correct = new WC_Report_Detailed_Registration_Event();
			$correct->output_report();

		} else {

			$this->prepare_items();
			$this->display();
		}
	}

	/**
	 * Get column value.
	 *
	 * @param WP_User $user
	 * @param string $column_name
	 * @return string
	 */
	public function column_default( $row, $column_name ) {
		global $wpdb;
		$parent   = ! empty( $row->get_parent_id() ) ? wc_get_product( $row->get_parent_id() ) : '';

		switch ( $column_name ) {

			case 'variation_id' :
				return $row->get_id();

			case 'variation_name':
				return $parent->registration_date( $row->get_id() );

			case 'product_name' :
				return $parent->get_title();


			case 'user_actions' :
				ob_start();
				?><p>
					<?php
						$actions = array();

						$actions['view'] = array(
							'url'       => add_query_arg( 'details', $row->get_id() ),
							'name'      => __( 'Customers', 'woocommerce' ),
							'action'    => "view"
						);

						foreach ( $actions as $action ) {
							printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
						}

					?>
				</p><?php
				$user_actions = ob_get_contents();
				ob_end_clean();

				return $user_actions;
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
			'variation_id'   => __( 'Variation ID', 'woocommerce' ),
			'variation_name' => __( 'Date', 'woocommerce' ),
			'product_name'   => __( 'Product Name', 'woocommerce' ),
			'user_actions'   => __( 'Actions', 'woocommerce' )
		);

		return $columns;
	}

	public function prepare_items() {
		$args1 = array(
			'post_type' => 'product',
			'product_type' => WC_Registrations::$name,
		);

		$parent_variantions_products = get_posts( $args1 );

		$dates = array();

		foreach ( $parent_variantions_products as $product_query ) {
			$product = wc_get_product( $product_query );
			foreach ( $product->get_children() as $variation ) {
				$dates[] = wc_get_product($variation);
			}
		}

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->items = $dates;

		$this->set_pagination_args( array(
			'total_items' => count( $dates ),
			'per_page'    => count( $dates ),
			'total_pages' => 1
		) );
	}
}
