<?php

namespace Haste\RegistrationsForWoo\DataTransfer;

/**
 * Registrations Import Class
 *
 * Manage Registrations import, adding and managing custom columns
 *
 * @package     Registrations for WooCommerce\WC_Registrations_Admin
 * @author      Allyson Souza
 * @since       1.0
 */
class Import {

	/**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 */
	public static function init() {
		add_filter( 'woocommerce_product_importer_parsed_data', __CLASS__ . '::filter_dates_attribute', 10, 2 );
	}

	/**
	 * Process the data read from the CSV file.
	 * This just saves the value in meta data, but you can do anything you want here with the data.
	 *
	 * @param array $data    CSV data read for the product.
	 * @param WC_Product_CSV_Importer $importer    WooCommerce Product CSV importer
	 * @return array $data    WooCommerce Product CSV importer
	 */
	public static function filter_dates_attribute( $data, $importer ) {

		if ( ! empty( $data['raw_attributes'] ) ) {

			foreach ( $data['raw_attributes'] as $key => $attribute ) {

				if ( $attribute['name'] === 'Dates' ) {

					foreach ( $attribute['value'] as $key => $value ) {
						if ( $value === 'Do Not Apply' ) {
							continue;
						}

						// Range Date
						if ( strpos( $value, 'to' ) !== false ) {
							$json['type']               = 'range';
							$json['dates']              = array_map( 'self::convert_date_format', explode( 'to', $value ) );
							$attribute['value'][ $key ] = json_encode( $json );
							continue;
						}

						// Multiple Date
						if ( strpos( $value, 'and' ) !== false ) {
							$json['type']               = 'multiple';
							$json['dates']              = array_map( 'self::convert_date_format', explode( 'and', $value ) );
							$attribute['value'][ $key ] = json_encode( $json );
							continue;
						}

						// Single Date
						$json['type']               = 'single';
						$json['date']               = self::convert_date_format( $value );
						$attribute['value'][ $key ] = json_encode( $json );
					}

					$data['raw_attributes'][ $key ] = $attribute;
				}
			}
		}

		return $data;
	}

	/**
	 * Convert date from one format to another.
	 *
	 * @param string $date  A date string to be converted.
	 */
	private static function convert_date_format( $date ) {
		$date     = trim( $date );
		$date     = DateTime::createFromFormat( 'm/d/Y', $date );
		$new_date = $date->format( 'Y-m-d' );
		return $new_date;
	}
}
