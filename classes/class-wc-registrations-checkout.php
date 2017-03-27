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
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 */
	public static function init() {
		/**
		 * Add fields to checkout
		 *
		 * @since 1.0.0
		 */
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
			$decoded = json_decode( $value['value'] );
			$str = '';

			if ( isset( $decoded->date ) || isset( $decoded->dates ) ) {
				$str = WC_Registrations_Admin::format_variations_dates( $decoded, get_option( 'date_format' ) );
			}

			$value['value'] = $str;
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
		global $woocommerce;
		$cart = $woocommerce->cart->get_cart();
		$registrations = 1;

		foreach( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];

			/**
			 * Loop trough each product of type registration
			 *
			 */
			if ( $_product->is_type( 'variation' ) && $_product->parent->is_type( 'registrations' ) ) {
				$qty = $values['quantity'];

				/**
				 * Generate fields for each participant/quantity set in product
				 */
				for ( $i = 1; $i <= $qty; $i++, $registrations++ ) {

					/**
					 * Display the fields header if it's the first participant to be displayed
					 */
					if ( $i == 1 ) {
						$date = get_post_meta( $_product->variation_id, 'attribute_dates', true );

						/**
						 * Check if there's a date defined, if there's no date, display only the product name.
						 */
						if ( $date ) {
							echo '<div id="registrations_fields"><h3>' . sprintf( __( 'Participants in %s - %s', 'registrations-for-woocommerce' ),  $_product->parent->post->post_title, esc_html( apply_filters( 'woocommerce_variation_option_name', $date ) ) ) . '</h3>';
						} else {
							echo '<div id="registrations_fields"><h3>' . sprintf( __( 'Participants in %s', 'registrations-for-woocommerce' ), $_product->parent->post->post_title ) . '</h3>';
						}
					}

					echo "<h4>" . sprintf( __( 'Participant #%u', 'registrations-for-woocommerce' ), $registrations ) . '</h4>';

					do_action( 'registrations_display_participant_fields', $checkout, $registrations );

					if ( $i == $qty ) {
						echo '</div>';
					}
				}
			}
		}
	}

	/**
	 * Display WooCommerce form fields for participants in registrations checkout.
	 *
	 * @since 1.0.0
	 *
	 * @param int $registrations	The current participant number
	 */
	public static function registrations_display_participant_fields( $checkout, $current_participant ) {
		woocommerce_form_field( 'participant_name_' . $current_participant , array(
			'type'          => 'text',
			'class'         => array('participant-name form-row-wide'),
			'label'         => __( 'Name', 'registrations-for-woocommerce' ),
			'placeholder'   => __( 'Mary Anna', 'registrations-for-woocommerce'),
			), $checkout->get_value( 'participant_name_' . $current_participant )
		);

		woocommerce_form_field( 'participant_surname_' . $current_participant , array(
			'type'          => 'text',
			'class'         => array('participant-surname form-row-wide'),
			'label'         => __( 'Surname', 'registrations-for-woocommerce' ),
			'placeholder'   => __( 'Smith', 'registrations-for-woocommerce'),
		), $checkout->get_value( 'participant_surname_' . $current_participant )
		);

		woocommerce_form_field( 'participant_email_' . $current_participant , array(
			'type'          => 'email',
			'class'         => array('participant-email form-row-wide'),
			'label'         => __( 'Email', 'registrations-for-woocommerce' ),
			'placeholder'   => __( 'mary@anna.com.br', 'registrations-for-woocommerce'),
			), $checkout->get_value( 'participant_email_' . $current_participant )
		);
	}

	/**
	 * Process the ckecout validation for registration product type.
	 *
	 * @since 1.0
	 *
	 */
	public static function registrations_checkout_process() {
		global $woocommerce;
		$registrations = 1;

		foreach ( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];

			if ( $_product->is_type( 'variation' ) && $_product->parent->is_type( 'registrations' ) ) {
				$qty = $values['quantity'];

				for ( $i = 1; $i <= $qty; $i++, $registrations++ ) {
					// Check if field is set, if it's not set add an error.
					if ( ! $_POST['participant_name_' . $registrations] ) {
						wc_add_notice( sprintf( __( 'Please enter a correct name to participant #%u ', 'registrations-for-woocommerce' ), $registrations ), 'error' );
					}

					if ( ! $_POST['participant_email_' . $registrations ] ) {
						wc_add_notice( sprintf( __( 'Please enter a correct email to participant #%u ', 'registrations-for-woocommerce' ), $registrations ), 'error' );
					}

					do_action( 'registrations_checkout_process_fields', $registrations );
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
		global $woocommerce;
		$registrations = 1;
		$registrations_meta = [];

		// Loop trough cart items
		foreach( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];
			$participants = array( 'date' => '', 'participants' => '' );
			$users = [];

			// Check if is registration product type
			if( $_product->is_type( 'variation' ) && $_product->parent->is_type( 'registrations' ) ) {
				$qty = $values['quantity'];
				$meta_value = '';
				$title = $_product->parent->post->post_title;

				// Run loop for each quantity of the product
				for( $i = 1; $i <= $qty; $i++, $registrations++ ) {
					// Get the variation meta date (JSON)
					$date = get_post_meta( $_product->variation_id, 'attribute_dates', true );
					$date ? $meta_name = $title . ' - ' . $date : $meta_name = $title;

					$participants['date'] = $meta_name;

					// Participant Name and Participant Email
					if (! empty( $_POST['participant_name_' . $registrations ] ) &&
					 	! empty( $_POST['participant_surname_' . $registrations ] ) &&
						! empty( $_POST['participant_email_' . $registrations ] ) ) {

						$participant = [];

						$participant['name'] = sanitize_text_field( $_POST['participant_name_' . $registrations ] );
						$participant['surname'] = sanitize_text_field( $_POST['participant_surname_' . $registrations ] );
						$participant['email'] = sanitize_email( $_POST['participant_email_' . $registrations ] );

						$participant = apply_filters( 'registrations_checkout_fields_order_meta_value', $participant, $registrations );

						$user = WC_Registrations_Checkout::create_registration_user( $participant['name'], $participant['surname'], $participant['email'] );

						if( !empty( $user ) ) {
							$users[] = $user;
							$participant['ID'] = $user;
						}

						$participants['participants'][] = $participant;
					}
				}

				$registrations_meta[] = $participants;

				// Update post meta
				update_post_meta( $order_id, '_registrations_order_meta', maybe_serialize( $registrations_meta ) );

				// Create a registration group and add users to this group
				WC_Registrations_Checkout::create_registration_group( $title, $users );

			}
		}
	}

	/**
	 * Integration with Groups plugin. If is groups active, creates a new group
	 * based in registration product, adding the participant users to that group.
	 *
	 * @since 1.0
	 * @param  string 	$group_name The name of group to be created.
  	 * @param  array 	$users      An array of users to be added to group.
	 */
	public static function create_registration_group( $group_name, $users ) {
		// Check if Groups plugin is active
		if ( is_plugin_active( 'groups/groups.php' ) ) {
			Groups_Group::create( array( 'name' => $group_name ) );

			if ( $group = Groups_Group::read_by_name( $group_name ) ) {
			    $group_id = $group->group_id;
			}

			if( !empty( $group_id ) ) {
				foreach( $users as $user_id ) {
					Groups_User_Group::create( array( 'user_id' => $user_id, 'group_id' => $group_id ) );
				}
			}
		}
	}

	/**
	 * Integration with Groups plugin. If groups is active, creates a new group.
	 *
	 * @since 1.0.0
	 *
	 * @param  string 	$name    	The user name
	 * @param  string 	$surname 	The user surname
	 * @param  string 	$email   	The user email
	 * @return int		$user_id   	The user ID
	 */
	public static function create_registration_user( $name, $surname, $email ) {
		$user_id = username_exists( $email );

		if ( !$user_id && email_exists( $email ) == false ) {
			$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
			$user_id = wp_create_user( $email, $random_password, $email );

			if ( is_wp_error( $user_id ) ) {
			    if ( WP_DEBUG === true ) {
					$message = $user_id->get_error_message();
			        error_log( print_r( $message, true ) );
			    }
				return false;
			} else {
				$user_id = wp_update_user( array( 'ID' => $user_id, 'first_name' => $name, 'last_name' => $surname ) );
				wp_new_user_notification( $user_id );
				return $user_id;
			}
		} else {
			return $user_id;
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
		$registration_meta = maybe_unserialize( get_post_meta( $order->id, '_registrations_order_meta', true ) );

		if ( ! empty( $registration_meta ) ) {
			do_action( 'registrations_before_admin_order_meta' );

			foreach ( $registration_meta as $registration ) {
				if( ! empty( $registration['date'] ) ) {
					$meta_name = explode( ' - ', $registration['date'] );
					echo '<p><strong>'. $meta_name[0] . ' - '. esc_html( apply_filters( 'woocommerce_variation_option_name', $meta_name[1] ) ) .':</strong></p>';
				}

				if( ! empty( $registration['participants'] ) ) {
					foreach ( $registration['participants'] as $participant ) {
						echo '<p>';
						echo sprintf( __( 'Name: %s %s', 'registrations-for-woocommerce' ), $participant['name'], $participant['surname'] ) . '<br>';
						echo sprintf( __( 'Email: %s', 'registrations-for-woocommerce' ), $participant['email'] ) . '<br>';
						do_action( 'registrations_admin_order_meta_participant_fields', $participant );
						echo '</p>';
					}
				}
			}

			do_action( 'registrations_after_admin_order_meta' );
		}
	}
}

WC_Registrations_Checkout::init();
