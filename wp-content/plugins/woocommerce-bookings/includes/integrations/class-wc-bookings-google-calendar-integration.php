<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Google Calendar Integration.
 */
class WC_Bookings_Google_Calendar_Integration extends WC_Integration {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		global $woocommerce;

		$this->plugin_id          = 'wc_bookings_';
		$this->id                 = 'google_calendar';
		$this->method_title       = __( 'Google Calendar', 'woocommerce-bookings' );
		$this->method_description = __( 'WooCommerce Bookings - Google Calendar integration.', 'woocommerce-bookings' );

		// API.
		$this->oauth_uri     = 'https://accounts.google.com/o/oauth2/';
		$this->calendars_uri = 'https://www.googleapis.com/calendar/v3/calendars/';
		$this->api_scope     = 'https://www.googleapis.com/auth/calendar';
		$this->redirect_uri  = $woocommerce->api_request_url( 'wc_bookings_google_calendar' );

		// Define user set variables.
		$this->client_id     = $this->get_option( 'client_id' );
		$this->client_secret = $this->get_option( 'client_secret' );
		$this->calendar_id   = $this->get_option( 'calendar_id' );
		$this->debug         = $this->get_option( 'debug' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Actions.
		add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_wc_bookings_google_calendar' , array( $this, 'oauth_redirect' ) );
		add_action( 'woocommerce_booking_confirmed', array( $this, 'sync_booking' ) );
		add_action( 'woocommerce_booking_paid', array( $this, 'sync_booking' ) );
		add_action( 'woocommerce_booking_complete', array( $this, 'sync_booking' ) );
		add_action( 'woocommerce_booking_cancelled', array( $this, 'remove_booking' ) );
		add_action( 'woocommerce_booking_process_meta', array( $this, 'sync_edited' ) );
		add_action( 'trashed_post', array( $this, 'remove_booking' ) );
		add_action( 'untrashed_post', array( $this, 'sync_edited' ) );

		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}

		// Active logs.
		if ( 'yes' == $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = $woocommerce->logger();
			}
		}
	}

	/**
	 * Initialize integration settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'client_id' => array(
				'title'       => __( 'Client ID', 'woocommerce-bookings' ),
				'type'        => 'text',
				'description' => __( 'Enter with your Google Client ID.', 'woocommerce-bookings' ),
				'desc_tip'    => true,
				'default'     => ''
			),
			'client_secret' => array(
				'title'       => __( 'Client Secret', 'woocommerce-bookings' ),
				'type'        => 'text',
				'description' => __( 'Enter with your Google Client Secret.', 'woocommerce-bookings' ),
				'desc_tip'    => true,
				'default'     => ''
			),
			'calendar_id' => array(
				'title'       => __( 'Calendar ID', 'woocommerce-bookings' ),
				'type'        => 'text',
				'description' => __( 'Enter with your Calendar ID.', 'woocommerce-bookings' ),
				'desc_tip'    => true,
				'default'     => ''
			),
			'authorization' => array(
				'title'       => __( 'Authorization', 'woocommerce-bookings' ),
				'type'        => 'google_calendar_authorization'
			),
			'testing' => array(
				'title'       => __( 'Testing', 'woocommerce-bookings' ),
				'type'        => 'title',
				'description' => ''
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce-bookings' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-bookings' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Google Calendar events, such as API requests, inside %s', 'woocommerce-bookings' ), '<code>woocommerce/logs/' . $this->id . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.txt</code>' )
			)
		);
	}

	/**
	 * Validate the Google Calendar Authorization field.
	 *
	 * @param  mixed $key
	 *
	 * @return string
	 */
	public function validate_google_calendar_authorization_field( $key ) {
		return '';
	}

	/**
	 * Generate the oogle Calendar Authorization field.
	 *
	 * @param  mixed $key
	 * @param  array $data
	 *
	 * @return string
	 */
	public function generate_google_calendar_authorization_html( $key, $data ) {
		$options       = $this->plugin_id . $this->id . '_';
		$id            = $options . $key;
		$client_id     = isset( $_POST[ $options . 'client_id' ] ) ? sanitize_text_field( $_POST[ $options . 'client_id' ] ) : $this->client_id;
		$client_secret = isset( $_POST[ $options . 'client_secret' ] ) ? sanitize_text_field( $_POST[ $options . 'client_secret' ] ) : $this->client_secret;
		$calendar_id   = isset( $_POST[ $options . 'calendar_id' ] ) ? sanitize_text_field( $_POST[ $options . 'calendar_id' ] ) : $this->calendar_id;
		$access_token  = $this->get_access_token();

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php echo wp_kses_post( $data['title'] ); ?>
			</th>
			<td class="forminp">
				<?php
					if ( ! $access_token && ( $client_id && $client_secret && $calendar_id ) ) :
						$oauth_url = add_query_arg(
							array(
								'scope'           => $this->api_scope,
								'redirect_uri'    => $this->redirect_uri,
								'response_type'   => 'code',
								'client_id'       => $client_id,
								'approval_prompt' => 'force',
								'access_type'     => 'offline',
							),
							$this->oauth_uri . 'auth'
						);
			 	?>
					<p class="submit"><a class="button button-primary" href="<?php echo esc_url( $oauth_url ); ?>"><?php _e( 'Connect with Google', 'woocommerce-bookings' ); ?></a></p>
				<?php elseif ( $access_token ) : ?>
					<p><?php _e( 'Successfully authenticated.', 'woocommerce-bookings' ); ?></p>
					<p class="submit"><a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'logout' => 'true' ), $this->redirect_uri ) ); ?>"><?php _e( 'Disconnect', 'woocommerce-bookings' ); ?></a></p>
				<?php else : ?>
					<p><?php _e( 'Unable to authenticate, you must enter with your <strong>Client ID</strong>, <strong>Client Secret</strong> and <strong>Calendar ID</strong>.', 'woocommerce-bookings' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Admin Options.
	 *
	 * @return string
	 */
	public function admin_options() {
		echo '<h3>' . $this->method_title . '</h3>';
		echo wpautop( $this->method_description );

		echo '<p>' . sprintf( __( 'To use this integration you need create a project in %s. Once your project has been created, you must enable the <strong>Google Calendar API</strong> in <strong>APIs & auth > APIs</strong>, finally in <strong>APIs & auth > Credentials</strong> you must create an OAuth client for a <strong>Web application</strong> and set the <strong>Authorized redirect URI</strong> as <code>%s</code>.', 'woocommerce-bookings' ), '<a href="https://console.developers.google.com/project" target="_blank">' . __( 'Google Developers Console', 'woocommerce-bookings' ) . '</a>', $this->redirect_uri ) . '</p>';

		echo '<table class="form-table">';
			$this->generate_settings_html();
		echo '</table>';

		echo '<div><input type="hidden" name="section" value="' . $this->id . '" /></div>';
	}

	/**
	 * Get Access Token.
	 *
	 * @param  string $code Authorization code.
	 *
	 * @return string       Access token.
	 */
	protected function get_access_token( $code = '' ) {

		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Getting Google API Access Token...' );
		}

		$access_token = get_transient( 'wc_bookings_gcalendar_access_token' );

		if ( ! $code && false !== $access_token ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Access Token recovered by transients: ' . print_r( $access_token, true ) );
			}

			return $access_token;
		}

		$refresh_token = get_option( 'wc_bookings_gcalendar_refresh_token' );

		if ( ! $code && $refresh_token ) {

			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Generating a new Access Token...' );
			}

			$data = array(
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'refresh_token' => $refresh_token,
				'grant_type'    => 'refresh_token'
			);

			$params = array(
				'body'      => http_build_query( $data ),
				'sslverify' => false,
				'timeout'   => 60,
				'headers'   => array(
					'Content-Type' => 'application/x-www-form-urlencoded'
				)
			);

			$response = wp_remote_post( $this->oauth_uri . 'token', $params );

			if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {
				$response_data = json_decode( $response['body'] );
				$access_token  = sanitize_text_field( $response_data->access_token );

				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Google API Access Token generated successfully: ' . print_r( $access_token, true ) );
				}

				// Set the transient.
				set_transient( 'wc_bookings_gcalendar_access_token', $access_token, 3500 );

				return $access_token;
			} else {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Error while generating the Access Token: ' . print_r( $response, true ) );
				}
			}
		} else if ( '' != $code ) {

			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Renewing the Access Token...' );
			}

			$data = array(
				'code'          => $code,
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'redirect_uri'  => $this->redirect_uri,
				'grant_type'    => 'authorization_code'
			);

			$params = array(
				'body'      => http_build_query( $data ),
				'sslverify' => false,
				'timeout'   => 60,
				'headers'   => array(
					'Content-Type' => 'application/x-www-form-urlencoded'
				)
			);

			$response = wp_remote_post( $this->oauth_uri . 'token', $params );

			if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {
				$response_data = json_decode( $response['body'] );
				$access_token  = sanitize_text_field( $response_data->access_token );

				// Add refresh token.
				update_option( 'wc_bookings_gcalendar_refresh_token', $response_data->refresh_token );

				// Set the transient.
				set_transient( 'wc_bookings_gcalendar_access_token', $access_token, 3500 );

				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Google API Access Token renewed successfully: ' . print_r( $access_token, true ) );
				}

				return $access_token;
			} else {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Error while renewing the Access Token: ' . print_r( $response, true ) );
				}
			}
		}

		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Failed to retrieve and generate the Access Token' );
		}

		return '';
	}

	/**
	 * OAuth Logout.
	 *
	 * @return bool
	 */
	protected function oauth_logout() {
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Leaving the Google Calendar app...' );
		}

		$refresh_token = get_option( 'wc_bookings_gcalendar_refresh_token' );

		if ( $refresh_token ) {
			$params = array(
				'sslverify' => false,
				'timeout'   => 60,
				'headers'   => array(
					'Content-Type' => 'application/x-www-form-urlencoded'
				)
			);

			$response = wp_remote_get( $this->oauth_uri . 'revoke?token=' . $refresh_token, $params );

			if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {
				delete_option( 'wc_bookings_gcalendar_refresh_token' );
				delete_transient( 'wc_bookings_gcalendar_access_token' );

				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Leave the Google Calendar app successfully' );
				}

				return true;
			} else {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Error when leaving the Google Calendar app: ' . print_r( $response, true ) );
				}
			}
		}

		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Failed to leave the Google Calendar app' );
		}

		return false;
	}

	/**
	 * Process the oauth redirect.
	 *
	 * @return void
	 */
	public function oauth_redirect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Permission denied!', 'woocommerce-bookings' ) );
		}

		$redirect_args = array(
			'page'    => 'wc-settings',
			'tab'     => 'integration',
			'section' => $this->id
		);

		// OAuth.
		if ( isset( $_GET['code'] ) ) {
			$code         = sanitize_text_field( $_GET['code'] );
			$access_token = $this->get_access_token( $code );

			if ( '' != $access_token ) {
				$redirect_args['wc_gcalendar_oauth'] = 'success';

				wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
				exit;
			}
		}
		if ( isset( $_GET['error'] ) ) {

			$redirect_args['wc_gcalendar_oauth'] = 'fail';

			wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
			exit;
		}

		// Logout.
		if ( isset( $_GET['logout'] ) ) {
			$logout = $this->oauth_logout();
			$redirect_args['wc_gcalendar_logout'] = ( $logout ) ? 'success' : 'fail';

			wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
			exit;
		}

		wp_die( __( 'Invalid request!', 'woocommerce-bookings' ) );
	}

	/**
	 * Display admin screen notices.
	 *
	 * @return string
	 */
	public function admin_notices() {
		$screen = get_current_screen();

		if ( 'woocommerce_page_wc-settings' == $screen->id && isset( $_GET['wc_gcalendar_oauth'] ) ) {
			if ( 'success' == $_GET['wc_gcalendar_oauth'] ) {
				echo '<div class="updated fade"><p><strong>' . __( 'Google Calendar', 'woocommerce-bookings' ) . '</strong> ' . __( 'Account connected successfully!', 'woocommerce-bookings' ) . '</p></div>';
			} else {
				echo '<div class="error fade"><p><strong>' . __( 'Google Calendar', 'woocommerce-bookings' ) . '</strong> ' . __( 'Failed to connect to your account, please try again, if the problem persists, turn on Debug Log option and see what is happening.', 'woocommerce-bookings' ) . '</p></div>';
			}
		}

		if ( 'woocommerce_page_wc-settings' == $screen->id && isset( $_GET['wc_gcalendar_logout'] ) ) {
			if ( 'success' == $_GET['wc_gcalendar_logout'] ) {
				echo '<div class="updated fade"><p><strong>' . __( 'Google Calendar', 'woocommerce-bookings' ) . '</strong> ' . __( 'Account disconnected successfully!', 'woocommerce-bookings' ) . '</p></div>';
			} else {
				echo '<div class="error fade"><p><strong>' . __( 'Google Calendar', 'woocommerce-bookings' ) . '</strong> ' . __( 'Failed to disconnect to your account, please try again, if the problem persists, turn on Debug Log option and see what is happening.', 'woocommerce-bookings' ) . '</p></div>';
			}
		}
	}

	/**
	 * Sync Booking with Google Calendar
	 *
	 * @param  int $booking_id Booking ID
	 *
	 * @return void
	 */
	public function sync_booking( $booking_id ) {
		$event_id     = get_post_meta( $booking_id, '_wc_bookings_gcalendar_event_id', true );
		$booking      = get_wc_booking( $booking_id );
		$api_url      = $this->calendars_uri . $this->calendar_id . '/events';
		$access_token = $this->get_access_token();
		$timezone     = wc_booking_get_timezone_string();
		$product      = $booking->get_product();

		// Needs a product
		if ( ! $product || ! $product->exists() ) {
			return;
		}

		$summary      = '#' . $booking->id . ' - ' . $product->get_title();
		$description  = '';

		// Add resources in description
		if ( $resource = $booking->get_resource() ) {
			$description .= __( 'Resource #', 'woocommerce-bookings' ) . $resource->ID . ' - ' . $resource->post_title;
		}

		// Add persons in description
		if ( $booking->has_persons() ) {
			$description .= ( '' != $description ) ? PHP_EOL . PHP_EOL : '';

			foreach ( $booking->get_persons() as $id => $qty ) {
				if ( 0 === $qty ) {
					continue;
				}

				$person_type = ( 0 < $id ) ? get_the_title( $id ) : __( 'Person(s)', 'woocommerce-bookings' );
				$description .= sprintf( __( '%s: %d', 'woocommerce-bookings'), $person_type, $qty ) . PHP_EOL;
			}
		}

		// Set the event data
		$data = array(
			'summary'     => $summary,
			'description' => $description
		);

		// Set the event start and end dates
		if ( $booking->is_all_day() ) {
			$data['start'] = array(
				'date' => date( 'Y-m-d', $booking->start ),
			);

			$data['end'] = array(
				'date' => date( 'Y-m-d', $booking->end ),
			);

		} else {
			$data['start'] = array(
				'dateTime' => date( 'Y-m-d\TH:i:s', $booking->start ),
				'timeZone' => $timezone
			);

			$data['end'] = array(
				'dateTime' => date( 'Y-m-d\TH:i:s', $booking->end ),
				'timeZone' => $timezone
			);
		}

		$data = apply_filters( 'woocommerce_bookings_gcalendar_sync', $data, $booking );

		// Connection params
		$params = array(
			'method'    => 'POST',
			'body'      => json_encode( $data ),
			'sslverify' => false,
			'timeout'   => 60,
			'headers'   => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token
			)
		);

		// Update event
		if ( $event_id ) {
			$api_url .= '/' . $event_id;
			$params['method'] = 'PUT';
		}

		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Synchronizing booking #' . $booking->id . ' with Google Calendar...' );
		}

		$response = wp_remote_post( $api_url, $params );

		if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Booking synchronized successfully!' );
			}

			// Updated the Google Calendar event ID
			$response_data = json_decode( $response['body'], true );
			update_post_meta( $booking->id, '_wc_bookings_gcalendar_event_id', $response_data['id'] );

		} else {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Error while synchronizing the booking #' . $booking->id . ': ' . print_r( $response, true ) );
			}
		}
	}

	/**
	 * Remove/cancel the booking in Google Calendar
	 *
	 * @param  int $booking_id Booking ID
	 *
	 * @return void
	 */
	public function remove_booking( $booking_id ) {
		$event_id = get_post_meta( $booking_id, '_wc_bookings_gcalendar_event_id', true );

		if ( $event_id ) {
			$booking      = get_wc_booking( $booking_id );
			$api_url      = $this->calendars_uri . $this->calendar_id . '/events/' . $event_id;
			$access_token = $this->get_access_token();
			$params       = array(
				'method'    => 'DELETE',
				'sslverify' => false,
				'timeout'   => 60,
				'headers'   => array(
					'Authorization' => 'Bearer ' . $access_token
				)
			);

			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Removing booking #' . $booking->id . ' with Google Calendar...' );
			}

			$response = wp_remote_post( $api_url, $params );

			if ( ! is_wp_error( $response ) && 204 == $response['response']['code'] ) {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Booking removed successfully!' );
				}

				// Remove event ID
				delete_post_meta( $booking->id, '_wc_bookings_gcalendar_event_id' );

			} else {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Error while removing the booking #' . $booking->id . ': ' . print_r( $response, true ) );
				}
			}
		}
	}

	/**
	 * Sync Booking with Google Calendar when booking is edited
	 *
	 * @param  int $booking_id Booking ID
	 *
	 * @return void
	 */
	public function sync_edited( $booking_id ) {
		global $wpdb;

		$status = $wpdb->get_var( $wpdb->prepare( "SELECT post_status FROM $wpdb->posts WHERE post_type = 'wc_booking' AND ID = %d", $booking_id ) );

		if ( 'cancelled' == $status ) {
			$this->remove_booking( $booking_id );
		} else if ( in_array( $status, array( 'confirmed', 'paid', 'complete' ) ) ) {
			$this->sync_booking( $booking_id );
		}
	}
}
