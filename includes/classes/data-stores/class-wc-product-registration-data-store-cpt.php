<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Variable Product Data Store: Stored in CPT.
 *
 * @version  3.0.0
 * @category Class
 * @author   WooThemes
 */
class WC_Product_Registrations_Data_Store_CPT extends WC_Product_Variable_Data_Store_CPT implements WC_Object_Data_Store_Interface, WC_Product_Variable_Data_Store_Interface {
	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
	    return 'registrations';
	}
}
