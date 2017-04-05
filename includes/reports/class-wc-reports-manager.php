<?php

class WC_Reports_Manager {

	public static function init() {
		add_filter( 'woocommerce_admin_reports', array( __CLASS__ , 'add_wc_custom_report' ), 10, 1 );
	}

	public static function add_wc_custom_report( $reports ) {

		$reports['registrations'] = array(
				'title'  => __( 'Events', 'registrations-for-woocommerce' ),
				'reports' => array(
					"list_registration_events" => array(
						'title'       => '',
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' ),
					),
				),
		);

		return $reports;
	}

	public static function get_report( $name ) {

		$name  = sanitize_title( str_replace( '_', '-', $name ) );
		$class = 'WC_Report_' . str_replace( '-', '_', $name );
		include_once( plugin_dir_path( __FILE__ ) . 'class-wc-report-' . $name . '.php' );
		if ( ! class_exists( $class ) ) {
			return;
		}
		$report = new $class();
		$report->output_report();
	}

}

WC_Reports_Manager::init();
