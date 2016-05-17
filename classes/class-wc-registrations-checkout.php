<?php
/**
 * Registrations Checkout Class
 *
 * Adds custom fields for Registrations info to be sent.
 *
 * @package		Registrations for WooCommerce
 * @subpackage	WC_Registrations_Checkout
 * @category	Class
 * @author		Allyson Souza
 * @since		1.0
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

					echo "<h3>" . sprintf( __( 'Participant #%u', $registrations ), $registrations ) . '</h3>';

					woocommerce_form_field( 'participant_name_' . $registrations , array(
						'type'          => 'text',
						'class'         => array('participant-name form-row-wide'),
						'label'         => __( 'Name', 'woocommerce-registrations' ),
						'placeholder'   => __( 'Mary Anna', 'woocommerce-registrations'),
						), $checkout->get_value( 'participant_name_' . $registrations )
					);

					woocommerce_form_field( 'participant_surname_' . $registrations , array(
						'type'          => 'text',
						'class'         => array('participant-surname form-row-wide'),
						'label'         => __( 'Surname', 'woocommerce-registrations' ),
						'placeholder'   => __( 'Smith', 'woocommerce-registrations'),
					), $checkout->get_value( 'participant_surname_' . $registrations )
					);

					woocommerce_form_field( 'participant_email_' . $registrations , array(
						'type'          => 'email',
						'class'         => array('participant-email form-row-wide'),
						'label'         => __( 'Email', 'woocommerce-registrations' ),
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
					// Check if field is set, if it's not set add an error.
					if ( ! $_POST['participant_name_' . $registrations ] ) {
						wc_add_notice( sprintf( __( 'Please enter a correct name to participant #%u ', 'woocommerce-registrations' ), $registrations ) );
					}

					if ( ! $_POST['participant_email_' . $registrations ] ) {
						wc_add_notice( sprintf( __( 'Please enter a correct email to participant #%u ', 'woocommerce-registrations' ), $registrations ) );
					}
				}
			}
		}
	}

	public static function registrations_checkout_field_update_order_meta( $order_id ) {
		global $woocommerce;
		$registrations = 1;

		// Loop trough cart items
		foreach( $woocommerce->cart->get_cart() as $cart_item_key => $values ) {
			$_product = $values['data'];
			$users = [];

			// Check if is registration product type
			if( $_product->is_type( 'variation' ) && $_product->parent->is_type( 'registrations' ) ) {
				$qty = $values['quantity'];
				$meta_value = '';
				$title = $_product->parent->post->post_title;

				// Run loop for each quantity of the product
				for( $i = 1; $i <= $qty; $i++, $registrations++ ) {
					//Get the variation meta date (JSON)
					$date = get_post_meta( $_product->variation_id, 'attribute_dates', true );
					$date ? $meta_name = $title . ' - ' . $date : $meta_name = $title;

					//Participant Name and Participant Email
					if (! empty( $_POST['participant_name_' . $registrations ] ) &&
					 	! empty( $_POST['participant_surname_' . $registrations ] ) &&
						! empty( $_POST['participant_email_' . $registrations ] ) ) {

						//Ckeck if it's not the first data to be added
						if( $i !== 1 ) {
							$meta_value .= ','. sanitize_text_field( $_POST['participant_name_' . $registrations ] );
							$meta_value .= ','. sanitize_text_field( $_POST['participant_email_' . $registrations ] );
						} else {
							$meta_value = sanitize_text_field( $_POST['participant_name_' . $registrations ] );
							$meta_value .= ','. sanitize_text_field( $_POST['participant_email_' . $registrations ] );
						}

						$user = WC_Registrations_Checkout::create_registration_user( sanitize_text_field( $_POST['participant_name_' . $registrations ] ), sanitize_text_field( $_POST['participant_surname_' . $registrations ] ), sanitize_text_field( $_POST['participant_email_' . $registrations ] ));

						if( !empty( $user ) ) {
							$users[] = $user;
						}
					}

				}

				//Update post meta
				update_post_meta( $order_id, $meta_name, $meta_value );

				/*
				 * Create a registration group and add users to this group
				 */
				WC_Registrations_Checkout::create_registration_group( $title, $users );

			}
		}
	}

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

	public static function create_registration_user( $name, $surname, $email ) {
		$user_id = username_exists( $email );

		if ( !$user_id && email_exists( $email ) == false ) {
			$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
			$user_id = wp_create_user( $email, $random_password, $email );

			if ( is_wp_error( $user_id ) ) {
			    if (WP_DEBUG === true) {
					$message = $user_id->get_error_message();
			        error_log( print_r( $message, true ) );
			    }
				return false;
			} else {
				$user_id = wp_update_user( array( 'ID' => $user_id, 'first_name' => $name, 'last_name' => $surname ) );
				wp_new_user_notification( $user_id, 'both' );
				return $user_id;
			}
		} else {
			return $user_id;
		}
	}

	public static function registrations_field_display_admin_order_meta( $order ){
		foreach( $order->get_items() as $item ) {
			$date = get_post_meta( $item['variation_id'], 'attribute_dates', true );

			if( $date ) {
				$meta_name = $item['name'] . ' - ' . $date;
			} else {
				$meta_name = $item['name'];
			}

			$meta_value = get_post_meta( $order->id, $meta_name, true );

			if( $meta_value ) {
				$meta_names = explode( ' - ', $meta_name );
				echo '<p><strong>'. $meta_names[0] . ' - '. esc_html( apply_filters( 'woocommerce_variation_option_name', $meta_names[1] ) ) .':</strong></p>';
				$meta_values = explode( ',', $meta_value );

				$i = 1;
				foreach( $meta_values as $value ) {
					if( $i % 2 == 0 ) {
						//Display email
						echo $value . '<br>';
					} else {
						//Display Name
						echo $value . ' - ';
					}
					$i++;
				}
			}
		}
	}
}

WC_Registrations_Checkout::init();
