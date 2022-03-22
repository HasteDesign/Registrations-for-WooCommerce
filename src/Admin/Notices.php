<?php

namespace Haste\RegistrationsForWoo\Admin;

defined( 'ABSPATH' ) || exit;

class Notices {
	/**
	 * Activation transient
	 *
	 * @var string $name
	 */
	public static $display_notices = 'rfwoo_activated';

	public static function init() {
		add_action( 'admin_init', __CLASS__ . '::set_transient' );
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::activation_notice' );
	}

	/**
	 * Display notices on registrations activation
	 *
	 * @since 1.0
	 */
	public static function activation_notice() {
		if ( get_transient( self::$display_notices ) ) {

			if ( ! isset( $_GET['page'] ) || 'wcs-about' !== $_GET['page'] ) {
				add_action( 'admin_notices', __CLASS__ . '::admin_installed_notice' );
			}

			delete_transient( self::$display_notices );
		}
	}

	/**
	 *
	 * Create an option and transient to indicate if plugin is active
	 *
	 * @since 1.0
	 */
	public static function set_transient() {
		if ( ! get_option( 'rfwoo_is_active', false ) ) {

			add_option( 'rfwoo_is_active', true );

			set_transient( self::$display_notices, true, 60 * 60 );

		}
	}

	/**
	 * When WooCommerce is inactive display a notice.
	 *
	 * @since 1.0
	 */
	public static function woocommerce_inactive_notice() {
		if ( current_user_can( 'activate_plugins' ) ) { ?>
			<div id="message" class="error">
				<p><?php printf( __( '%1$sRegistrations for WooCommerce is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for Registrations for WooCommerce to work. Please %5$sinstall & activate WooCommerce%6$s', 'registrations-for-woocommerce' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . admin_url( 'plugin-install.php?s=WooCommerce&tab=search&type=term' ) . '">', '&nbsp;&raquo;</a>' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Display a welcome message when Registrations is activated
	 *
	 * @since 1.0
	 */
	public static function admin_installed_notice() {
		?>
		<div class="updated notice notice-success is-dismissible">
			<div class="squeezer">
				<h4><?php printf( __( '%1$sRegistrations for WooCommerce Installed%2$s &#8211; %3$sYou\'re ready to start selling registrations!%4$s', 'registrations-for-woocommerce' ), '<strong>', '</strong>', '<em>', '</em>' ); ?></h4>

				<p class="submit">
					<a href="https://twitter.com/share" class="twitter-share-button" data-url="https://wordpress.org/plugins/registrations-for-woocommerce/" data-text="<?php _e( 'Sell course and events registrations with #WooCommerce', 'registrations-for-woocommerce' ); ?>" data-via="HasteDesign" data-size="large">Tweet</a>
					<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
				</p>
			</div>
		</div>
		<?php
	}
}
