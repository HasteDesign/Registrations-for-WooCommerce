<?php
/**
 * Registrations Checkout Class
 *
 * Add custom checkout fields for registrations product types and store that
 * data as order meta.
 *
 * @package		Registrations for WooCommerce\WC_Registrations_Checkout
 * @author		Allyson Souza
 * @since		1.0.0
 */

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class WC_Registrations_Checkout {

	/**
	 * Holds the Registration Fields
	 *
	 * @since 2.0.5
	 */
	protected static $settings;
	

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 */
	public static function init() {
		self::$settings = array(
			'participant_fields' => array(
				array(
					'class'         => array( 'participant-name', 'form-row-wide' ),
					'label'         => __( 'First name', 'registrations-for-woocommerce' ),
					'name'          => 'name',
					'type'          => 'text',
					'required'      => true,
				),
				array(
					'class'         => array( 'participant-surname', 'form-row-wide' ),
					'label'         => __( 'Last name', 'registrations-for-woocommerce' ),
					'name'          => 'surname',
					'type'          => 'text',
				),
				array(
					'class'         => array( 'participant-email', 'form-row-wide' ),
					'label'         => __( 'Email address', 'registrations-for-woocommerce' ),
					'name'          => 'email',
					'type'          => 'text',
					'required'      => true,
				),
			),
		);

		// Add fields to checkout
		add_action( 'woocommerce_after_order_notes', __CLASS__ . '::registrations_checkout_fields' );

		// Display participant fields in checkout
		add_action( 'registrations_display_participant_fields', __CLASS__ . '::registrations_display_participant_fields', 10, 2 );

		// Process the checkout for registration product type
		add_action( 'woocommerce_checkout_process',  __CLASS__ . '::registrations_checkout_process');

		// Generate order meta based on order registrations and participants
		add_action( 'woocommerce_checkout_update_order_meta', __CLASS__ . '::registrations_checkout_field_update_order_meta' );

		// Display registration order meta on admin order page
		add_action( 'woocommerce_admin_order_data_after_billing_address', __CLASS__ . '::registrations_field_display_admin_order_meta', 10, 1 );

		// Prettifies the name of the variable on order details
		add_filter( 'woocommerce_order_items_meta_get_formatted', __CLASS__.'::prettify_variable_date_name', 10, 2 );
	}

	/**
	 * Prettifies the name of the variable item in the order details back to the default WordPress date format to present it to the user.
	 *
	 * @since 1.0.7
	 *
	 * @param array  $formatted an array containing what's set to display
	 * @param object $order     the woocommerce order object
	 * @return array            the array now containing the name prettified
	 */
	public static function prettify_variable_date_name( $formatted, $order ) {
		foreach ( $formatted as $key => $value ) {
			$date = '';

			if ( json_decode( $value['value'] ) ) {
				$date = WC_Registrations_Helpers::get_formatted_date( $value['value'], get_option( 'date_format' ) );
			}

			$value['value'] = $date . ' Hi World!';
			$formatted[$key] = $value;
		}

		return $formatted;
	}

	/**
	 * Display specific registrations checkout fields, if there's any registration
	 * product_type in cart.
	 *
	 * @since 1.0.0
	 *
	 * @param object $checkout The current checkout object.
	 */
	public static function registrations_checkout_fields( $checkout ) {
		$cart = WC()->cart->get_cart();
		$registrations = 1;

		/**
		 * Loop trough each cart item, if it's a product that haves a parent, check if the parent
		 * product is of type registrations, if yes, then display the registrations additional
		 * checkout fields.
		 */
		foreach( $cart as $cart_item_key => $values ) {
			$product = $values['data'];
			$parent   = $product->get_parent_id() ? wc_get_product( $product->get_parent_id() ) : '';

			// Check if product have a parent (variable/registrations)
			if( ! empty( $parent ) ) {

				// Check if product parent is of type registrations
				if ( $product->get_type() === 'variation' && $parent->get_type() === 'registrations' ) {
					self::registrations_the_checkout_fields( $product, $values['quantity'], $parent, $checkout );
				}
			}
		}
	}

	/**
	 * Display registrations checkout fields
	 * 
	 * @since 2.1
	 */
	public static function registrations_the_checkout_fields( $product, $quantity, $parent, $checkout ) {
		// Generate fields for each participant/quantity set in product
		for ( $count = 1; $count <= $quantity; $count++ ) {

			// Display the header if it's the first participant to be displayed
			if ( $count == 1 ) {
				$date = get_post_meta( $product->get_id(), 'attribute_dates', true );

				// Check if there's a date defined, if there's no date, display only the product name.
				if ( $date ) {
					echo '<div id="registrations_fields"><h3>' . sprintf( __( 'Participants in %s - %s', 'registrations-for-woocommerce' ),  $parent->get_title(), esc_html( apply_filters( 'woocommerce_variation_option_name', $date ) ) ) . '</h3>';
				} else {
					echo '<div id="registrations_fields"><h3>' . sprintf( __( 'Participants in %s', 'registrations-for-woocommerce' ), $parent->get_title() ) . '</h3>';
				}
			}

			echo '<h4>' . sprintf( __( 'Participant #%u', 'registrations-for-woocommerce' ), $count ) . '</h4>';

			do_action( 'registrations_display_participant_fields', $checkout, $count );

			if ( $count == $quantity ) {
				echo '</div>';
			}
		}
	}

	/**
	 * Display participant fields
	 * 
	 * Display WooCommerce form fields for participants in registrations checkout.
	 *
	 * @since 1.0.0
	 *
	 * @param object $checkout			Current checkout object
	 * @param int $current_participant	Current participant number
	 */
	public static function registrations_display_participant_fields( $checkout, $current_participant ) {
		$participant_fields = apply_filters( 'registrations_participant_fields', self::$settings['participant_fields'] );
		$participant_key    = 'participant_%s_%d';

		if ( $participant_fields ) {
			foreach( $participant_fields as $field ) {
				$field_key = sprintf( $participant_key, $field['name'], $current_participant );
				unset( $field['name'] );
				woocommerce_form_field( $field_key, $field, $checkout->get_value( $field_key ) );
			}
		}
	}

	/**
	 * Process the ckecout validation for registration product type.
	 *
	 * @since 1.0
	 */
	public static function registrations_checkout_process() {
		$participant_fields = apply_filters( 'registrations_participant_fields', self::$settings['participant_fields'] );
		$participant_key    = 'participant_%s_%d';

		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];
			$parent   = ! empty( $_product->get_parent_id() ) ? wc_get_product( $_product->get_parent_id() ) : '';

			if ( $_product->get_type() === 'variation' && $parent->get_type() === 'registrations' ) {
				$qty = $values['quantity'];

				for ( $count = 1; $count <= $qty; $count++ ) {
					foreach( $participant_fields as $field ) {
						$field_key = sprintf( $participant_key, $field['name'], $count );
						if ( ! empty( $field['required'] ) && empty( $_POST[$field_key] ) ) {
							wc_add_notice( sprintf( __( 'Please enter a correct %s to participant #%u ', 'registrations-for-woocommerce' ), strtolower( $field['label'] ), $count ), 'error' );
						}
					}
					do_action( 'registrations_checkout_proccess_fields', $count );
				}
			}
		}
	}

	/**
	 * Update order meta adding specific registration info, like participant name, email.
	 *
	 * @since 1.0
	 *
	 */
	public static function registrations_checkout_field_update_order_meta( $order_id ) {
		$registrations = 1;
		$registrations_meta = [];

		$participant_fields = apply_filters( 'registrations_participant_fields', self::$settings['participant_fields'] );
		$participant_key    = 'participant_%s_%d';

		// Loop trough cart items
		foreach( WC()->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];
			$participants = array( 'date' => '', 'participants' => array() );
			$users = [];
			$parent   = ! empty( $_product->get_parent_id() ) ? wc_get_product( $_product->get_parent_id() ) : '';

			// Check if is registration product type
			if ( $_product->get_type() === 'variation' && $parent->get_type() === 'registrations' ) {
				$qty = $values['quantity'];
				$meta_value = '';
				$title = str_replace( " - ", " _ ", $parent->get_title() ); //don't allow '-' in the product title when storing.

				// Run loop for each quantity of the product
				for( $count = 1; $count <= $qty; $count++ ) {
					// Get the variation meta date (JSON)
					$date = get_post_meta( $_product->get_id(), 'attribute_dates', true );
					$meta_name = ( $date ) ? "$title - $date" : $title;

					$participants['date'] = $meta_name;

					// Process the fields
					$participant = [];
					foreach( $participant_fields as $field ) {
						$sanitize = ( 'email' === $field['name'] ) ? 'sanitize_email' : 'sanitize_text_field';
						$field_key = sprintf($participant_key, $field['name'], $count);

						if ( ! empty($_POST[$field_key]) ) {
							$participant[$field['name']] = call_user_func( $sanitize, $_POST[$field_key] );
						}
					}

					$participant = apply_filters( 'registrations_checkout_fields_order_meta_value', $participant, $count );

					do_action( 'registrations_participant_created', $participant );

					$participants['participants'][] = $participant;
				}

				do_action( 'registrations_participants_created', $participants );

				$registrations_meta[] = $participants;

				update_post_meta( $order_id, '_registrations_order_meta', maybe_serialize( $registrations_meta ) );

				do_action( 'registrations_order_meta_created', $order_id );
			}
		}
	}

	/**
	 * Display additional registration product type data to order views, displaying
	 * registered participant data that are stored serialized.
	 *
	 * @since 1.0
	 *
	 * @param  object 	$order The current order to display additional meta.
	 */
	public static function registrations_field_display_admin_order_meta( $order ) {
		$registration_meta = maybe_unserialize( get_post_meta( $order->get_id(), '_registrations_order_meta', true ) );

		$participant_fields = apply_filters( 'registrations_participant_fields', self::$settings['participant_fields'] );

		if ( ! empty( $registration_meta ) ) {
			do_action( 'registrations_before_admin_order_meta' );

			foreach ( $registration_meta as $registration ) {
				if( ! empty( $registration['date'] ) ) {
					$meta_name = str_replace(" _ ", " - ", explode( ' - ', $registration['date'] )); //str_replace adds the '-' back into the string after exploding.
					echo '<p><strong>'. $meta_name[0] . ' - '. esc_html( apply_filters( 'woocommerce_variation_option_name', $meta_name[1] ) ) .':</strong></p>';
				}

				if( ! empty( $registration['participants'] ) ) {
					$count = 1;
					foreach ( $registration['participants'] as $participant ) {
					?>
						<p id="participant-<?php echo $count; ?>" class="participant">
						<?php foreach ( $participant_fields as $field ) : ?>
							<?php if ( ! empty($participant[$field['name']]) ) : ?>
							<span class="participant-<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?>: <?php echo esc_html( $participant[$field['name']] ); ?><br>
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

WC_Registrations_Checkout::init();
