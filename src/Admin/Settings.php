<?php

namespace Haste\RegistrationsForWoo\Admin;

defined( 'ABSPATH' ) || exit;

class Settings {

	public static function init() {
		add_action( 'admin_menu', __CLASS__ . '::createSettings' );
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
}
