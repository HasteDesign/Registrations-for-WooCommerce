<?php

namespace Haste\RegistrationsForWoo\Admin;

defined( 'ABSPATH' ) || exit;

class Settings {

	public static function init() {
		add_action( 'admin_menu', __CLASS__ . '::createSettings' );
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::enqueueScripts' );
	}

	public static function createSettings() {
		add_menu_page(
			__( 'Registrations', 'registrations-for-woo' ),
			__( 'Registrations', 'registrations-for-woo' ),
			'manage_options',
			'registrations',
			__CLASS__ . '::renderPage'
		);
	}

	public static function renderPage() {
		echo '<div id="registrations-root"></div>';
	}

	public static function enqueueScripts() {
		// wp_enqueue_script( 'react' );
		// wp_enqueue_script( 'react-dom' );
		wp_enqueue_script(
			'registrations-settings',
			plugins_url( '../../assets/js/registrations-settings.js', __FILE__ ),
			array( 'react', 'react-dom' ),
			'',
			true
		);
	}
}
