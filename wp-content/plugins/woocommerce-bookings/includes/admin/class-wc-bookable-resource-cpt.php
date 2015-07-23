<?php
/**
 * Admin functions for the bookings post type
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Bookable_Resource_CPT' ) ) :

/**
 * WC_Admin_CPT_Product Class
 */
class WC_Bookable_Resource_CPT {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'bookable_resource';

		// Post title fields
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );

		// Admin Columns
		add_filter( 'manage_edit-' . $this->type . '_columns', array( $this, 'edit_columns' ) );
		add_action( 'manage_' . $this->type . '_posts_custom_column', array( $this, 'custom_columns' ), 2 );
		add_filter( 'manage_edit-' . $this->type . '_sortable_columns', array( $this, 'custom_columns_sort' ) );
	}

	/**
	 * Change title boxes in admin.
	 * @param  string $text
	 * @param  object $post
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		if ( $post->post_type == 'bookable_resource' ) {
			return __( 'Bookable resource name', 'woocommerce-bookings' );
		}
		return $text;
	}

	/**
	 * Change the columns shown in admin.
	 */
	public function edit_columns( $existing_columns ) {
		global $woocommerce;

		if ( empty( $existing_columns ) && ! is_array( $existing_columns ) ) {
			$existing_columns = array();
		}

		unset( $existing_columns['comments'], $existing_columns['title'], $existing_columns['date'] );

		$columns                     = array();
		$columns["resource_name"]    = __( 'Name', 'woocommerce-bookings' );
		$columns["parents"]          = __( 'Parent products', 'woocommerce-bookings' );
		$columns["resource_actions"] = __( 'Actions', 'woocommerce-bookings' );

		return array_merge( $existing_columns, $columns );
	}

	/**
	 * Make product columns sortable
	 *
	 * https://gist.github.com/906872
	 *
	 * @access public
	 * @param mixed $columns
	 * @return array
	 */
	public function custom_columns_sort( $columns ) {
		$custom = array(
			'resource_name' => 'title',
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Define our custom columns shown in admin.
	 * @param  string $column
	 */
	public function custom_columns( $column ) {
		global $post, $wpdb;

		switch ( $column ) {
			case 'resource_name' :
				printf( '<a href="%s">' . __( '%s', 'woocommerce-bookings' ) . '</a>', admin_url( 'post.php?post=' . $post->ID . '&action=edit' ), $post->post_title );
			break;
			case 'parents' :
				$parents      = $wpdb->get_col( $wpdb->prepare( "SELECT product_id FROM {$wpdb->prefix}wc_booking_relationships WHERE resource_id = %d ORDER BY sort_order;", $post->ID ) );
				$parent_posts = array();
				foreach ( $parents as $parent_id ) {
					$parent_posts[] = '<a href="' . admin_url( 'post.php?post=' . $parent_id . '&action=edit' ) . '">' . get_the_title( $parent_id ) . '</a>';
				}
				echo $parent_posts ? implode( ', ', $parent_posts ) : __( 'N/A', 'woocommerce-bookings' );
			break;
			case 'resource_actions' :
				echo '<p>';
				$actions         = array();
				$actions['edit'] = array(
					'url' 		=> admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
					'name' 		=> __( 'Edit', 'woocommerce-bookings' ),
					'action' 	=> "edit"
				);
				$actions         = apply_filters( 'woocommerce_admin_bookable_resource_actions', $actions, $post );

				foreach ( $actions as $action ) {
					printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
				}
				echo '</p>';
			break;
		}
	}
}

endif;

return new WC_Bookable_Resource_CPT();
