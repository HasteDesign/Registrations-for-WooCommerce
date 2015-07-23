<?php
/**
 * Admin functions for the bookings post type
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Bookings_CPT' ) ) :

/**
 * WC_Admin_CPT_Product Class
 */
class WC_Bookings_CPT {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->type = 'wc_booking';

		// Post title fields
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );

		// Admin Columns
		add_filter( 'manage_edit-' . $this->type . '_columns', array( $this, 'edit_columns' ) );
		add_action( 'manage_' . $this->type . '_posts_custom_column', array( $this, 'custom_columns' ), 2 );
		add_filter( 'manage_edit-' . $this->type . '_sortable_columns', array( $this, 'custom_columns_sort' ) );
		add_filter( 'request', array( $this, 'custom_columns_orderby' ) );

		// Filtering
		add_action( 'restrict_manage_posts', array( $this, 'booking_filters' ) );
		add_filter( 'parse_query', array( $this, 'booking_filters_query' ) );
		add_filter( 'get_search_query', array( $this, 'search_label' ) );

		// Search
		add_filter( 'parse_query', array( $this, 'search_custom_fields' ) );

		// Actions
		add_filter( 'bulk_actions-edit-' . $this->type, array( $this, 'bulk_actions' ) );
		add_action( 'load-edit.php', array( $this, 'bulk_action' ) );
		add_action( 'admin_footer', array( $this, 'bulk_admin_footer' ), 10 );
		add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );

		// Sync
		add_action( 'woocommerce_booking_cancelled', array( $this, 'cancel_order' ) );
		add_action( 'before_delete_post', array( $this, 'delete_post' ) );
		add_action( 'wp_trash_post', array( $this, 'trash_post' ) );
		add_action( 'untrash_post', array( $this, 'untrash_post' ) );
	}

	/**
	 * Remove edit from the bulk actions.
	 *
	 * @access public
	 * @param mixed $actions
	 * @return array
	 */
	public function bulk_actions( $actions ) {

		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		return $actions;
	}

	/**
	 * Add extra bulk action options to mark orders as complete or processing
	 *
	 * Using Javascript until WordPress core fixes: http://core.trac.wordpress.org/ticket/16031
	 *
	 * @access public
	 * @return void
	 */
	public function bulk_admin_footer() {
		global $post_type;

		if ( $this->type == $post_type ) {
			?>
			<script type="text/javascript">
				jQuery( document ).ready( function ( $ ) {
					$( '<option value="confirm_bookings"><?php _e( 'Confirm bookings', 'woocommerce-bookings' )?></option>' ).appendTo( 'select[name="action"], select[name="action2"]' );

					$( '<option value="unconfirm_bookings"><?php _e( 'Unconfirm bookings', 'woocommerce-bookings' )?></option>' ).appendTo( 'select[name="action"], select[name="action2"]' );

					$( '<option value="cancel_bookings"><?php _e( 'Cancel bookings', 'woocommerce-bookings' )?></option>' ).appendTo( 'select[name="action"], select[name="action2"]' );

					$( '<option value="mark_paid_bookings"><?php _e( 'Mark bookings as paid', 'woocommerce-bookings' )?></option>' ).appendTo( 'select[name="action"], select[name="action2"]' );

					$( '<option value="mark_unpaid_bookings"><?php _e( 'Mark bookings as unpaid', 'woocommerce-bookings' )?></option>' ).appendTo( 'select[name="action"], select[name="action2"]' );
				});
			</script>
			<?php
		}
	}

	/**
	 * Process the new bulk actions for changing order status
	 *
	 * @access public
	 * @return void
	 */
	public function bulk_action() {
		$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
		$action = $wp_list_table->current_action();

		switch ( $action ) {
			case 'confirm_bookings' :
				$new_status = 'confirmed';
				$report_action = 'bookings_confirmed';
				break;
			case 'unconfirm_bookings' :
				$new_status = 'pending';
				$report_action = 'bookings_unconfirmed';
				break;
			case 'mark_paid_bookings' :
				$new_status = 'paid';
				$report_action = 'bookings_marked_paid';
				break;
			case 'mark_unpaid_bookings' :
				$new_status = 'unpaid';
				$report_action = 'bookings_marked_unpaid';
				break;
			case 'cancel_bookings' :
				$new_status = 'cancelled';
				$report_action = 'bookings_cancelled';
				break;
			break;

			default:
				return;
		}

		$changed = 0;

		$post_ids = array_map( 'absint', (array) $_REQUEST['post'] );

		foreach ( $post_ids as $post_id ) {
			$booking = get_wc_booking( $post_id );
			if ( $booking->get_status() !== $new_status ) {
				$booking->update_status( $new_status );
			}
			$changed++;
		}

		$sendback = add_query_arg( array( 'post_type' => $this->type, $report_action => true, 'changed' => $changed, 'ids' => join( ',', $post_ids ) ), '' );
		wp_redirect( $sendback );
		exit();
	}

	/**
	 * Show confirmation message that order status changed for number of orders
	 *
	 * @access public
	 * @return void
	 */
	public function bulk_admin_notices() {
		global $post_type, $pagenow;

		if ( isset( $_REQUEST['bookings_confirmed'] ) || isset( $_REQUEST['bookings_marked_paid'] ) || isset( $_REQUEST['bookings_marked_unpaid'] ) || isset( $_REQUEST['bookings_unconfirmed'] ) || isset( $_REQUEST['bookings_cancelled'] ) ) {
			$number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0;

			if ( 'edit.php' == $pagenow && $this->type == $post_type ) {
				$message = sprintf( _n( 'Booking status changed.', '%s booking statuses changed.', $number ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . $message . '</p></div>';
			}
		}
	}

	/**
	 * Change title boxes in admin.
	 * @param  string $text
	 * @param  object $post
	 * @return string
	 */
	public function enter_title_here( $text, $post ) {
		if ( $post->post_type == 'wc_booking' ) {
			return __( 'Booking Title', 'woocommerce-bookings' );
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

		$columns                    = array();
		$columns["booking_status"]  = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'woocommerce-bookings' ) . '">' . esc_attr__( 'Status', 'woocommerce-bookings' ) . '</span>';
		$columns["booking_id"]      = __( 'ID', 'woocommerce-bookings' );
		$columns["booked_product"]  = __( 'Booked Product', 'woocommerce-bookings' );
		$columns["customer"]        = __( 'Booked By', 'woocommerce-bookings' );
		$columns["order"]           = __( 'Order', 'woocommerce-bookings' );
		$columns["start_date"]      = __( 'Start Date', 'woocommerce-bookings' );
		$columns["end_date"]        = __( 'End Date', 'woocommerce-bookings' );
		$columns["booking_actions"] = __( 'Actions', 'woocommerce-bookings' );

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
			'booking_id'     => 'booking_id',
			'booked_product' => 'booked_product',
			'booking_status' => 'status',
			'start_date'     => 'start_date',
			'end_date'       => 'end_date'
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Define our custom columns shown in admin.
	 * @param  string $column
	 */
	public function custom_columns( $column ) {
		global $post, $woocommerce, $booking;

		if ( empty( $booking ) || $booking->id != $post->ID ) {
			$booking = get_wc_booking( $post->ID );
		}

		switch ( $column ) {
			case 'booking_status' :
				echo $booking->get_status( false );
				break;
			case 'booking_id' :
				printf( '<a href="%s">' . __( 'Booking #%d', 'woocommerce-bookings' ) . '</a>', admin_url( 'post.php?post=' . $post->ID . '&action=edit' ), $post->ID );
				break;
			case 'customer' :
				$customer = $booking->get_customer();

				if ( $customer ) {
					echo '<a href="mailto:' .  $customer->email . '">' . $customer->name . '</a>';
				} else {
					echo '-';
				}
				break;
			case 'booked_product' :
				$product  = $booking->get_product();
				$resource = $booking->get_resource();

				if ( $product ) {
					echo '<a href="' . admin_url( 'post.php?post=' . $product->id . '&action=edit' ) . '">' . $product->post->post_title . '</a>';
					if ( $resource ) {
						echo ' (<a href="' . admin_url( 'post.php?post=' . $resource->ID . '&action=edit' ) . '">' . $resource->post_title . '</a>)';
					}
				} else {
					echo '-';
				}
				break;
			case 'order' :
				$order = $booking->get_order();

				if ( $order ) {
					echo '<a href="' . admin_url( 'post.php?post=' . $order->id . '&action=edit' ) . '">' . $order->get_order_number() . '</a> - ' . esc_html__( $order->status, 'woocommerce-bookings' );
				} else {
					echo '-';
				}
				break;
			case 'start_date' :
				echo $booking->get_start_date();
				break;
			case 'end_date' :
				echo $booking->get_end_date();
				break;
			case 'booking_actions' :
				echo '<p>';
				$actions = array();

				$actions['view'] = array(
					'url' 		=> admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
					'name' 		=> __( 'View', 'woocommerce-bookings' ),
					'action' 	=> "view"
				);

				if ( in_array( $booking->get_status(), array( 'pending' ) ) ) {
					$actions['confirm'] = array(
						'url' 		=> wp_nonce_url( admin_url( 'admin-ajax.php?action=wc-booking-confirm&booking_id=' . $post->ID ), 'wc-booking-confirm' ),
						'name' 		=> __( 'Confirm', 'woocommerce-bookings' ),
						'action' 	=> "confirm"
					);
				}

				$actions = apply_filters( 'woocommerce_admin_booking_actions', $actions, $booking );

				foreach ( $actions as $action ) {
					printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
				}
				echo '</p>';
				break;
		}
	}

	/**
	 * Product column orderby
	 *
	 * http://scribu.net/wordpress/custom-sortable-columns.html#comment-4732
	 *
	 * @access public
	 * @param mixed $vars
	 * @return array
	 */
	public function custom_columns_orderby( $vars ) {
		if ( isset( $vars['orderby'] ) ) {
			if ( 'booking_id' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'orderby' 	=> 'ID'
				) );
			}

			if ( 'booked_product' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_booking_product_id',
					'orderby' 	=> 'meta_value_num'
				) );
			}

			if ( 'status' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'orderby' 	=> 'post_status'
				) );
			}

			if ( 'start_date' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_booking_start',
					'orderby' 	=> 'meta_value_num'
				) );
			}

			if ( 'end_date' == $vars['orderby'] ) {
				$vars = array_merge( $vars, array(
					'meta_key' 	=> '_booking_end',
					'orderby' 	=> 'meta_value_num'
				) );
			}
		}

		return $vars;
	}

	/**
	 * Show a filter box
	 */
	public function booking_filters() {
		global $typenow, $wp_query;

		if ( $typenow != $this->type ) {
			return;
		}

		$filters = array();

		$products = WC_Bookings_Admin::get_booking_products();

		foreach ( $products as $product ) {
			$filters[ $product->ID ] = $product->post_title;

			$resources = wc_booking_get_product_resources( $product->ID );

			foreach ( $resources as $resource ) {
				$filters[ $resource->ID ] = '&nbsp;&nbsp;&nbsp;' . $resource->post_title;
			}
		}

		$output = '';

		if ( $filters ) {
			$output .= '<select name="filter_bookings">';
			$output .= '<option value="">' . __( 'All Bookable Products', 'woocommerce-bookings' ) . '</option>';

			foreach ( $filters as $filter_id => $filter ) {
				$output .= '<option value="' . absint( $filter_id ) . '" ';

				if ( isset( $_REQUEST['filter_bookings'] ) ) {
					$output .= selected( $filter_id, $_REQUEST['filter_bookings'], false );
				}

				$output .= '>' . esc_html( $filter ) . '</option>';
			}

			$output .= '</select>';
		}

		echo $output;
	}

	/**
	 * Filter the products in admin based on options
	 *
	 * @param mixed $query
	 */
	public function booking_filters_query( $query ) {
		global $typenow, $wp_query;

		if ( $typenow == $this->type ) {
			if ( ! empty( $_REQUEST['filter_bookings'] ) && empty( $query->query_vars['suppress_filters'] ) ) {
				$query->query_vars['meta_value'] = absint( $_REQUEST['filter_bookings'] );

				if ( get_post_type( $_REQUEST['filter_bookings'] ) == 'bookable_resource' ) {
					$query->query_vars['meta_key'] = '_booking_resource_id';
				} else {
					$query->query_vars['meta_key'] = '_booking_product_id';
				}
			}
		}
	}

	/**
	 * Search custom fields
	 *
	 * @param mixed $wp
	 */
	public function search_custom_fields( $wp ) {
		global $pagenow, $wpdb;

		if ( 'edit.php' != $pagenow || empty( $wp->query_vars['s'] ) || $wp->query_vars['post_type'] != $this->type ) {
			return $wp;
		}

		$search_fields = array_map( 'wc_clean', array(
			'_billing_first_name',
			'_billing_last_name',
			'_billing_company',
			'_billing_address_1',
			'_billing_address_2',
			'_billing_city',
			'_billing_postcode',
			'_billing_country',
			'_billing_state',
			'_billing_email',
			'_billing_phone',
			'_shipping_first_name',
			'_shipping_last_name',
			'_shipping_address_1',
			'_shipping_address_2',
			'_shipping_city',
			'_shipping_postcode',
			'_shipping_country',
			'_shipping_state'
		) );

		// Search orders
		$order_ids = $wpdb->get_col(
			$wpdb->prepare( "
				SELECT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_key IN ('" . implode( "','", $search_fields ) . "')
				AND meta_value LIKE '%%%s%%'",
				esc_attr( $_GET['s'] )
			)
		);

		$order_ids[] = 0;

		// Remove s - we don't want to search order name
		unset( $wp->query_vars['s'] );

		// so we know we're doing this
		$booking_ids = array_merge(
			$wpdb->get_col(
				"SELECT order_id
					FROM {$wpdb->prefix}woocommerce_order_items
					WHERE order_item_id IN (
						SELECT post_id FROM {$wpdb->postmeta}
						WHERE meta_key = '_booking_order_item_id'
						AND meta_value IN (" . implode( ',', $order_ids ) . ")
				);"
			),
			$wpdb->get_col(
				$wpdb->prepare( "
					SELECT ID
						FROM {$wpdb->posts}
						WHERE post_title LIKE '%%%s%%'
						OR ID = %d
					;",
					esc_attr( $_GET['s'] ),
					absint( $_GET['s'] )
				)
			),
			array( 0 )
		);

		// Search by found posts
		$wp->query_vars['post__in']       = $booking_ids;
		$wp->query_vars['booking_search'] = true;
	}

	/**
	 * Change the label when searching orders.
	 *
	 * @access public
	 * @param mixed $query
	 * @return string
	 */
	public function search_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' != $pagenow ) {
			return $query;
		}

		if ( $typenow != $this->type ) {
			return $query;
		}

		if ( ! get_query_var( 'booking_search' ) ) {
			return $query;
		}

		return $_GET['s'];
	}

	/**
	 * Cancel order with bookings
	 * @param  int $booking_id
	 */
	public function cancel_order( $booking_id ) {
		global $wpdb;

		// Prevents infinite loop during synchronization
		update_post_meta( $booking_id, '_booking_status_sync', true );

		$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d", $booking_id ) );

		$order = new WC_Order( $order_id );

		if ( '' != $order->id && false == get_post_meta( $order->id, '_booking_status_sync', true ) ) {

			// Only cancel if the order has 1 booking
			if ( 1 === count( $order->get_items() ) ) {
				$order->update_status( 'cancelled' );
			}
		}

		delete_post_meta( $booking_id, '_booking_status_sync' );
	}

	/**
	 * Removes parent order to the booking being deleted.
	 *
	 * @param mixed $booking_id ID of post being deleted
	 */
	public function delete_post( $booking_id ) {
		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		if ( $booking_id > 0 && $this->type == get_post_type( $booking_id ) ) {
			global $wpdb;

			// Prevents infinite loop during synchronization
			update_post_meta( $booking_id, '_booking_delete_sync', true );

			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d", $booking_id ) );

			if ( '' != $order_id && false == get_post_meta( $order_id, '_booking_delete_sync', true ) ) {
				wp_delete_post( $order_id, true );
			}

			delete_post_meta( $booking_id, '_booking_delete_sync' );
		}
	}

	/**
	 * Trash order with bookings
	 *
	 * @param mixed $booking_id
	 */
	public function trash_post( $booking_id ) {
		if ( $booking_id > 0 && $this->type == get_post_type( $booking_id ) ) {
			global $wpdb;

			// Prevents infinite loop during synchronization
			update_post_meta( $booking_id, '_booking_trash_sync', true );

			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d", $booking_id ) );

			if ( '' != $order_id && false == get_post_meta( $order_id, '_booking_trash_sync', true ) ) {
				wp_trash_post( $order_id );
			}

			delete_post_meta( $booking_id, '_booking_trash_sync' );
		}
	}

	/**
	 * Untrash order with bookings
	 *
	 * @param mixed $booking_id
	 */
	public function untrash_post( $booking_id ) {
		if ( $booking_id > 0 && $this->type == get_post_type( $booking_id ) ) {
			global $wpdb;

			// Prevents infinite loop during synchronization
			update_post_meta( $booking_id, '_booking_untrash_sync', true );

			$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_parent FROM {$wpdb->posts} WHERE ID = %d", $booking_id ) );

			if ( '' != $order_id && false == get_post_meta( $order_id, '_booking_trash_sync', true ) ) {
				wp_untrash_post( $order_id );
			}

			delete_post_meta( $booking_id, '_booking_untrash_sync' );
		}
	}
}

endif;

return new WC_Bookings_CPT();
