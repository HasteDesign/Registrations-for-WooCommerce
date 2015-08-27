<?php
/**
 * Registrations Checkout Class
 *
 * Adds custom fields for Registrations info to be sent.
 *
 * @package		WooCommerce Registrations
 * @subpackage	WC_Registrations_Checkout
 * @category	Class
 * @author		Allyson Souza
 * @since		1.0
 */
class WC_Registrations_Checkout {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 */
	public static function init() {
		/*
		 * TO-DO: Syntax Error: Unexpected < - gerado na validação de compra quando estes filtros estão ativos
		/**
		 * Add the field to the checkout
		 */
		add_action( 'woocommerce_after_order_notes', __CLASS__ . '::registrations_checkout_fields' );

		/**
		 * Process the checkout
		 */
		add_action( 'woocommerce_checkout_process',  __CLASS__ . '::registrations_checkout_process');

		/**
		 * Update the order meta with field value
		 */
		add_action( 'woocommerce_checkout_update_order_meta', __CLASS__ . '::registrations_checkout_field_update_order_meta' );

		/**
		 * Display field value on the order edit page
		 */
		add_action( 'woocommerce_admin_order_data_after_billing_address', __CLASS__ . '::registrations_field_display_admin_order_meta', 10, 1 );
	}

	/**
	 * Adds all necessary admin styles.
	 *
	 * @param array Array of Product types & their labels, excluding the Subscription product type.
	 * @return array Array of Product types & their labels, including the Subscription product type.
	 * @since 1.0
	 */
	public static function registrations_checkout_fields( $checkout ) {
		global $woocommerce;
		$cart = $woocommerce->cart->get_cart();
		$registrations = 1;

		foreach( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];
			error_log( print_r( $_product , true ) );

			if( $_product->is_type( 'variation' ) && $_product->parent->is_type( 'registrations' ) ) {
				$qty = $values['quantity'];

				for( $i = 1; $i <= $qty; $i++, $registrations++ ) {

					if( $i == 1 ) {
						$date = get_post_meta( $_product->variation_id, 'attribute_dates', true );

						if( $date ) {
							echo '<div id="registrations_fields"><h2>' . sprintf( __( 'Participants in %s - %s', 'woocommerce-registrations' ),  $_product->parent->post->post_title, esc_html( apply_filters( 'woocommerce_variation_option_name', $date ) ) ) . '</h2>';
						} else {
							echo '<div id="registrations_fields"><h2>' . sprintf( __( 'Participants in %s', 'woocommerce-registrations' ), $_product->parent->post->post_title ) . '</h2>';
						}
					}

					woocommerce_form_field( 'participant_name_' . $registrations , array(
						'type'          => 'text',
						'class'         => array('participant-name form-row-wide'),
						'label'         => sprintf( __( '#%u Participant Name', 'woocommerce-registrations' ), $registrations),
						'placeholder'   => __( 'Mary Anna', 'woocommerce-registrations'),
						), $checkout->get_value( 'participant_name_' . $registrations )
					);

					woocommerce_form_field( 'participant_email_' . $registrations , array(
						'type'          => 'email',
						'class'         => array('participant-email form-row-wide'),
						'label'         => sprintf( __( '#%u Participant Email', 'woocommerce-registrations' ), $registrations ),
						'placeholder'   => __( 'mary@anna.com.br', 'woocommerce-registrations'),
						), $checkout->get_value( 'participant_email_' . $registrations )
					);

					if( $i == $qty ) {
						echo '</div>';
					}
				}
			}
		}
	}

	public static function registrations_checkout_process() {
		global $woocommerce;
		$registrations = 1;

		foreach( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];

			if( $_product->is_type( 'variation' ) && $_product->parent->is_type( 'registrations' ) ) {
				$qty = $values['quantity'];

				for( $i = 1; $i <= $qty; $i++, $registrations++ ) {
					// Check if set, if its not set add an error.
					if ( ! $_POST['participant_name_' . $registrations ] ) {
						/*
						 * TO-DO: Funções de localização com variável
						 */
						wc_add_notice( sprintf( __( 'Please enter a correct name to participant #%u ', 'woocommerce-registrations' ), $registrations ) );
					}

					if ( ! $_POST['participant_email_' . $registrations ] ) {
						/*
						 * TO-DO: Funções de localização com variável
						 */
						wc_add_notice( sprintf( __( 'Please enter a correct email to participant #%u ', 'woocommerce-registrations' ), $registrations ) );
					}
				}
			}
		}
	}

	public static function registrations_checkout_field_update_order_meta( $order_id ) {
		global $woocommerce;
		$registrations = 1;

		foreach( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];

			if( $_product->is_type( 'variation' ) && $_product->parent->is_type( 'registrations' ) ) {
				$qty = $values['quantity'];

				for( $i = 1; $i <= $qty; $i++, $registrations++ ) {
					$date = get_post_meta( $_product->variation_id, 'attribute_dates', true );

					if( $date ) {
						$date = $_product->parent->post->post_title . ' - ' . esc_html( apply_filters( 'woocommerce_variation_option_name', $date ) );
					}

					//Participant Name
					if ( ! empty( $_POST['participant_name_' . $registrations ] ) ) {
						update_post_meta( $order_id, '#' . $registrations . ' Participant Name - ' . $date , sanitize_text_field( $_POST['participant_name_' . $registrations ] ) );
					}

					//Participant Email
					if ( ! empty( $_POST['participant_email_' . $registrations ] ) ) {
						update_post_meta( $order_id, '#' . $registrations . ' Participant Email  - ' . $date , sanitize_text_field( $_POST['participant_email_' . $registrations ] ) );
					}
				}
			}
		}
	}

	public static function registrations_field_display_admin_order_meta( $order ){
		$names = 1;
		$emails = 1;

		do {
			$name = get_post_meta( $order->id, '#' . $names . ' Participant Name', true );

			if( !empty( $name ) ) {
				echo '<p><strong>'. sprintf( __( '#%u Participant Name' ), $names ) .':</strong> ' . $name . '</p>';
				$names++;
			} else {
				$names = 0;
			}

			$email = get_post_meta( $order->id, '#' . $emails . ' Participant Email', true );

			if( !empty( $email ) ) {
				echo '<p><strong>'. sprintf( __( '#%u Participant Email' ), $emails ) .':</strong> ' . $email . '</p>';
				$emails++;
			} else {
				$emails = 0;
			}

		} while( $names && $emails );
	}

}

WC_Registrations_Checkout::init();
