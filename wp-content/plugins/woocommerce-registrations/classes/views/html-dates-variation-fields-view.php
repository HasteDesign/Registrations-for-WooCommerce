<?php
global $woocommerce, $thepostid;

if ( ! $event_start_time = get_post_meta( $variation->ID, '_event_start_time', true ) ) {
    $event_start_time = '';
}

if ( ! $event_end_time = get_post_meta( $variation->ID, '_event_end_time', true ) ) {
    $event_end_time = '';
}

// When called via Ajax
if ( ! function_exists( 'woocommerce_wp_text_input' ) ) {
    require_once( $woocommerce->plugin_path() . '/admin/post-types/writepanels/writepanels-init.php' );
}

if ( ! isset( $thepostid ) ) {
    $thepostid = $variation->post_parent;
}
?>
<div class="show_if_registration">
    <p class="form-row form-row-first">
        <label><?php _e( 'Start Time', 'woocommerce-registrations'); ?> <a class="tips" href="#">[?]</a></label>
        <input type="time" name="_event_start_time" step="900" class="wc_input_event_start_time" value="<?php echo $event_start_time; ?>">
        <label><?php _e( 'End Time', 'woocommerce-registrations'); ?> <a class="tips" href="#">[?]</a></label>
        <input type="time" name="_event_end_time" step="900" class="wc_input_event_end_time" value="<?php echo $event_start_time; ?>">
    </p>
    <p class="form-row form-row-last show_if_range_date">
        <label><?php _e( 'Days of Week when this event occurs.', 'woocommerce-registrations'); ?> <a class="tips" href="#">[?]</a></label>
        <label><input type="checkbox" name="week_days[]" value="monday" /><?php _e( 'Monday', 'woocommerce-registrations' ); ?></label>
        <label><input type="checkbox" name="week_days[]" value="sunday" /><?php _e( 'Sunday', 'woocommerce-registrations' ); ?></label>
        <label><input type="checkbox" name="week_days[]" value="tuesday" /><?php _e( 'Tuesday', 'woocommerce-registrations' ); ?></label>
        <label><input type="checkbox" name="week_days[]" value="wednesday" /><?php _e( 'Wednesday', 'woocommerce-registrations' ); ?></label>
        <label><input type="checkbox" name="week_days[]" value="thursday" /><?php _e( 'Thursday', 'woocommerce-registrations' ); ?></label>
        <label><input type="checkbox" name="week_days[]" value="friday" /><?php _e( 'Friday', 'woocommerce-registrations' ); ?></label>
        <label><input type="checkbox" name="week_days[]" value="saturday" /><?php _e( 'Saturday', 'woocommerce-registrations' ); ?></label>
    </p>
</div>
