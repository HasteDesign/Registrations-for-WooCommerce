<?php
/**
 * General registrations helper functions
 *
 * @since 2.1
 */

class WC_Registrations_Helpers {

	public static function is_woocommerce_screen() {
		$screen = get_current_screen();
		return in_array( $screen->id, array( 'product', 'edit-shop_order', 'shop_order', 'users', 'woocommerce_page_wc-settings' ) ) ? true : false;
	}

	/**
	 * Get formatted date
	 *
	 * Return the formatted date variation option for each date type
	 * (single, multiple, and range) correctly to given date format or default
	 * WordPress set date format.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $json         JSON with date in registrations notation (ex. {type:"single",date:"31-12-2022"} )
	 * @param  string $format       PHP date format to
	 * @return string               Formated registrations variation option name. Returns an empty strings in failure.
	 */
	public static function get_formatted_date( $json, $date_format = '' ) {
		$date_format = $date_format ? $date_format : get_option( 'date_format' );
		$option      = json_decode( stripslashes( $json ) );

		if ( $option ) {
			if ( $option->type == 'single' ) {

				return date_i18n( $date_format, strtotime( $option->date ) );

			} elseif ( $option->type == 'multiple' ) {

				$date_option = self::get_formatted_date_multiple( $option, $date_format );

				return $date_option;

			} elseif ( $option->type == 'range' ) {
				return date_i18n( $date_format, strtotime( $option->dates[0] ) ) . ' ' . __( 'to', 'registrations-for-woocommerce' ) . ' ' . date_i18n( $date_format, strtotime( $option->dates[1] ) );
			}
		}

		return '';
	}

	/**
	 * Get formatted multiple date
	 *
	 * @since 2.1
	 * @return string $date_option  A formatted string with multiple days date.
	 */
	public static function get_formatted_date_multiple( $option, $date_format ) {
		$date_option = '';

		foreach ( $option->dates as $date ) {
			if ( $date_option == '' ) {
				$date_option .= date_i18n( $date_format, strtotime( $date ) );
			} else {
				$date_option .= ', ' . date_i18n( $date_format, strtotime( $date ) );
			}
		}

		return $date_option;
	}
}
