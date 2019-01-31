<?php
/**
 * Registrations Export Class
 *
 * Manage Registrations export, filtering date format to export
 *
 * @package		Registrations for WooCommerce\WC_Registrations_Admin
 * @author		Allyson Souza
 * @since		1.0
 */
class WC_Registrations_Export {
    
    /**
	 * Bootstraps the class and hooks required actions & filters.
	 *
	 * @since 1.0
	 */
	public static function init() {
        add_filter( 'woocommerce_product_export_row_data', __CLASS__ . '::filter_dates_attribute', 10, 2 );
    }

    /**
     * 
     */
    public static function filter_dates_attribute( $row, $product ) {
        $formatted_dates = array();
        $i = 1;

        

        for ( $i = 1; $i; ) {
            
            if ( isset( $row['attributes:name' . $i] ) ) {
                if ( $row['attributes:name' . $i] === __( 'Dates', 'registrations-for-woocommerce' ) ) {
                    
                    error_log( print_r( $row['attributes:value' . $i], true ) );
                    // Removing \ before JSON commas before explode
                    $row['attributes:value' . $i] = str_replace( '\,', ',', $row['attributes:value' . $i] );
                    $dates = explode( ', ', $row['attributes:value' . $i] );
                    error_log( print_r( $row['attributes:value' . $i], true ) );

                    foreach( $dates as $date ) {
                        
                        $formatted = WC_Registrations_Helpers::get_formatted_date( $date, 'm/d/Y' );
                        
                        if( empty( $formatted ) ) {
                            $formatted_dates[] = $date;
                        } else {
                            $formatted_dates[] = $formatted;
                        }
                    }

                    $row['attributes:value' . $i] = self::implode_values( $formatted_dates );
                    return $row;
                }

                $i++;
                continue;
            }

            $i = 0;
        }

        return $row;
    }

    /**
     * Implode value adding and instead of commas inside the values
     * 
     * @param array $values  Array of attribute values
     * @param string          Array of imploded attribute values.
     */
	static protected function implode_values( $values ) {
		$values_to_implode = array();

		foreach ( $values as $value ) {
			$value               = (string) is_scalar( $value ) ? $value : '';
			$values_to_implode[] = str_replace( ',', ' and', $value );
		}

		return implode( ', ', $values_to_implode );
	}
}

WC_Registrations_Export::init();