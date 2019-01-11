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

}