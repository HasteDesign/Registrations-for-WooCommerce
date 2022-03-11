<?php

namespace Haste\RegistrationsForWoo\Admin;

defined( 'ABSPATH' ) || exit;

class Notices {

	/**
	 * Activation transient
	 * 
	 * @var string $name
	 */
	public static $activation_transient = 'registrations_for_woocommerce_activated';

	public static function init() {
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::activation_notice' );
	}

	/**
	 * When WooCommerce is inactive display a notice.
	 *
	 * @since 1.0
	 */
	public static function woocommerce_inactive_notice() {
		if ( current_user_can( 'activate_plugins' ) ) { ?>
			<div id="message" class="error">
				<p><?php printf( __( '%sRegistrations for WooCommerce is inactive.%s The %sWooCommerce plugin%s must be active for Registrations for WooCommerce to work. Please %sinstall & activate WooCommerce%s', 'registrations-for-woocommerce' ), '<strong>', '</strong>', '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>', '<a href="' . admin_url( 'plugin-install.php?s=WooCommerce&tab=search&type=term' ) . '">', '&nbsp;&raquo;</a>' ); ?></p>
			</div>
		<?php
		}
	}

	/**
	 * Display notices on registrations activation
	 *
	 * @since 1.0
	 */
	public static function activation_notice() {
		global $woocommerce, $post;

		$is_activation_screen  = ( get_transient( self::$activation_transient ) == true ) ? true : false;

		if ( $is_activation_screen ) {
			
			if ( ! isset( $_GET['page'] ) || 'wcs-about' != $_GET['page'] ) {
				add_action( 'admin_notices', __CLASS__ . '::admin_installed_notice' );
			}

			delete_transient( self::$activation_transient );
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
				<h4><?php printf( __( '%sRegistrations for WooCommerce Installed%s &#8211; %sYou\'re ready to start selling registrations!%s', 'registrations-for-woocommerce' ), '<strong>', '</strong>', '<em>', '</em>' ); ?></h4>

				<p class="submit">
					<a href="https://twitter.com/share" class="twitter-share-button" data-url="https://wordpress.org/plugins/registrations-for-woocommerce/" data-text="<?php _e( 'Sell course and events registrations with #WooCommerce', 'registrations-for-woocommerce' ); ?>" data-via="HasteDesign" data-size="large">Tweet</a>
					<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
				</p>
			</div>
		</div>
		<?php
	}
}