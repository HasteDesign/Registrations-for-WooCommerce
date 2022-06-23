<?php

namespace Haste\RegistrationsForWoo\Admin;

defined('ABSPATH') || exit;

class Settings
{

	/**
	 * Initialize hooks.
	 * 
	 * @return [type]
	 */
	public static function init()
	{
		add_action('admin_menu', __CLASS__ . '::createSettings');
		add_action('admin_enqueue_scripts', __CLASS__ . '::enqueueScripts');
	}

	/**
	 * Add settings menu page.
	 * 
	 * @return [type]
	 */
	public static function createSettings()
	{
		add_menu_page(
			__('Registrations', 'registrations-for-woo'),
			__('Registrations', 'registrations-for-woo'),
			'manage_options',
			'registrations',
			__CLASS__ . '::renderPage'
		);
	}

	/**
	 * Render React root element.
	 * 
	 * @return [type]
	 */
	public static function renderPage()
	{
		echo '<div id="registrations-root"></div>';
	}

	/**
	 * Enqueue settings page scripts.
	 * 
	 * Enqueue registration settings scripts, with wp-element as dependency
	 * in order to make WordPress Core React available.
	 * 
	 * @see: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-element/
	 *
	 * @return [type]
	 */
	public static function enqueueScripts()
	{
		if (isset($_GET['page']) && 'registrations' === $_GET['page']) {

			wp_enqueue_script(
				'registrations-settings',
				plugins_url('../../assets/js/settings.js', __FILE__),
				array('wp-element'),
				'',
				true
			);

			$plugin_url = plugins_url('../../assets/', __FILE__);
			wp_add_inline_script('registrations-settings', "var assets_path =  '$plugin_url'", 'before');

			wp_enqueue_style(' add_google_fonts ', 'https://fonts.googleapis.com/css2?family=Rubik:wght@400;700&display=swap', false);
			wp_enqueue_style(' add_google_fonts2 ', 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap', false);

			wp_enqueue_style(
				'registrations-settings',
				plugins_url('../../assets/css/admin-settings.css', __FILE__),
				array(),
				''
			);
		}
	}
}
