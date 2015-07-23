<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Addons integration class.
 */
class WC_Bookings_Addons {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_product_addons_panel_before_options', array( $this, 'addon_options' ), 20, 3 );
		add_filter( 'woocommerce_product_addons_save_data', array( $this, 'save_addon_options' ), 20, 2 );
		add_filter( 'woocommerce_product_addon_cart_item_data', array( $this, 'addon_price' ), 20, 4 );
		add_filter( 'woocommerce_product_addons_adjust_price', array( $this, 'adjust_price' ), 20, 2 );
		add_filter( 'booking_form_calculated_booking_cost', array( $this, 'adjust_booking_cost' ), 10, 3 );
	}

	/**
	 * Show options
	 */
	public function addon_options( $post, $addon, $loop ) {
		?>
		<tr class="show_if_booking">
			<td class="addon_wc_booking_person_qty_multiplier addon_required" width="50%">
				<label for="addon_wc_booking_person_qty_multiplier_<?php echo $loop; ?>"><?php _e( 'Bookings: Multiply cost by person count', 'woocommerce-bookings' ); ?></label>
				<input type="checkbox" id="addon_wc_booking_person_qty_multiplier_<?php echo $loop; ?>" name="addon_wc_booking_person_qty_multiplier[<?php echo $loop; ?>]" <?php checked( ! empty( $addon['wc_booking_person_qty_multiplier'] ), true ) ?> />
			</td>
			<td class="addon_wc_booking_block_qty_multiplier addon_required" width="50%">
				<label for="addon_wc_booking_block_qty_multiplier_<?php echo $loop; ?>"><?php _e( 'Bookings: Multiply cost by block count', 'woocommerce-bookings' ); ?></label>
				<input type="checkbox" id="addon_wc_booking_block_qty_multiplier_<?php echo $loop; ?>" name="addon_wc_booking_block_qty_multiplier[<?php echo $loop; ?>]" <?php checked( ! empty( $addon['wc_booking_block_qty_multiplier'] ), true ) ?> />
			</td>
		</tr>
		<?php
	}

	/**
	 * Save options
	 */
	public function save_addon_options( $data, $i ) {
		$data['wc_booking_person_qty_multiplier'] = isset( $_POST['addon_wc_booking_person_qty_multiplier'][ $i ] ) ? 1 : 0;
		$data['wc_booking_block_qty_multiplier']  = isset( $_POST['addon_wc_booking_block_qty_multiplier'][ $i ] ) ? 1 : 0;

		return $data;
	}

	/**
	 * Change addon price based on settings
	 * @return float
	 */
	public function addon_price( $cart_item_data, $addon, $product_id, $post_data ) {
		$product = get_product( $product_id );

		if ( $product->is_type( 'booking' ) ) {
			$booking_form = new WC_Booking_Form( $product );
			$booking_data = $booking_form->get_posted_data( $post_data );

			foreach ( $cart_item_data as $key => $data ) {
				if ( ! empty( $addon['wc_booking_person_qty_multiplier'] ) && ! empty( $booking_data['_persons'] ) && array_sum( $booking_data['_persons'] ) ) {
					$cart_item_data[ $key ]['price'] = $data['price'] * array_sum( $booking_data['_persons'] );
				}
				if ( ! empty( $addon['wc_booking_block_qty_multiplier'] ) && ! empty( $booking_data['_duration'] ) ) {
					$cart_item_data[ $key ]['price'] = $data['price'] * $booking_data['_duration'];
				}
			}
		}
		return $cart_item_data;
	}	

	/**
	 * Don't adjust price for bookings since the booking form class adds the costs itself
	 * @return bool
	 */
	public function adjust_price( $bool, $cart_item ) {
		if ( $cart_item['data']->is_type( 'booking' ) ) {
			return false;
		} 
		return $bool;
	}

	/**
	 * Adjust the final booking cost
	 */
	public function adjust_booking_cost( $booking_cost, $booking_form, $posted ) {
		// Product add-ons
		$addons     = $GLOBALS['Product_Addon_Cart']->add_cart_item_data( array(), $booking_form->product->id, $posted );
		$addon_cost = 0;

		if ( ! empty( $addons['addons'] ) ) {
			foreach ( $addons['addons'] as $addon ) {
				$addon_cost += $addon['price'];
			}
		}
		
		return $booking_cost + $addon_cost;
	}
}

new WC_Bookings_Addons();