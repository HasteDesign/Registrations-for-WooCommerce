<?php
namespace Haste\RegistrationsForWoo\Admin;

use Haste\RegistrationsForWoo\Checkout;

defined( 'ABSPATH' ) || exit;

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

		// Display registration order meta on admin order page
		add_action( 'woocommerce_admin_order_data_after_billing_address', __CLASS__ . '::registrations_field_display_admin_order_meta', 10, 1 );
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
			echo self::registrations_field_display_admin_order_meta( $the_order );
		}
	}


	/**
	 * Display additional registration product type data to order views, displaying
	 * registered participant data that are stored serialized.
	 *
	 * @since 1.0
	 *
	 * @param  object   $order The current order to display additional meta.
	 */
	public static function registrations_field_display_admin_order_meta( $order ) {
		$registration_meta = maybe_unserialize( get_post_meta( $order->get_id(), '_registrations_order_meta', true ) );

		$participant_fields = apply_filters( 'registrations_participant_fields', Checkout\Checkout::$settings['participant_fields'] );

		if ( ! empty( $registration_meta ) ) {
			do_action( 'registrations_before_admin_order_meta' );

			foreach ( $registration_meta as $registration ) {
				if ( ! empty( $registration['date'] ) ) {
					$meta_name = str_replace( ' _ ', ' - ', explode( ' - ', $registration['date'] ) ); //str_replace adds the '-' back into the string after exploding.
					echo '<p><strong>' . $meta_name[0] . ' - ' . esc_html( apply_filters( 'woocommerce_variation_option_name', $meta_name[1] ) ) . ':</strong></p>';
				}

				if ( ! empty( $registration['participants'] ) ) {
					$count = 1;
					foreach ( $registration['participants'] as $participant ) {
						?>
						<p id="participant-<?php echo $count; ?>" class="participant">
						<?php foreach ( $participant_fields as $field ) : ?>
							<?php if ( ! empty( $participant[ $field['name'] ] ) ) : ?>
							<span class="participant-<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>: <?php echo esc_html( $participant[ $field['name'] ] ); ?><br>
							<?php endif; ?>
						<?php endforeach; ?>
						<?php do_action( 'registrations_admin_order_meta_participant_fields', $participant ); ?>
						</p>
						<?php
						$count++;
					}
				}
			}

			do_action( 'registrations_after_admin_order_meta' );
		}
	}
}
