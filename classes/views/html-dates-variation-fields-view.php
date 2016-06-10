<?php
global $woocommerce, $thepostid;

if ( ! $event_start_time = get_post_meta( $variation->ID, '_event_start_time', true ) ) {
    $event_start_time = '';
}

if ( ! $event_end_time = get_post_meta( $variation->ID, '_event_end_time', true ) ) {
    $event_end_time = '';
}

if ( ! $week_days = get_post_meta( $variation->ID, '_week_days', true ) ) {
    $week_days = [];
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
        <label><?php _e( 'Start Time', 'registrations-for-woocommerce'); ?> <a class="tips" href="#">[?]</a></label>
        <input type="time" name="_event_start_time[<?php echo $loop; ?>]" step="900" class="wc_input_event_start_time" value="<?php echo $event_start_time; ?>">
        <label><?php _e( 'End Time', 'registrations-for-woocommerce'); ?> <a class="tips" href="#">[?]</a></label>
        <input type="time" name="_event_end_time[<?php echo $loop; ?>]" step="900" class="wc_input_event_end_time" value="<?php echo $event_end_time; ?>">
    </p>
    <p class="form-row form-row-last show_if_range_date">
        <label><?php _e( 'Days of Week when this event occurs.', 'registrations-for-woocommerce'); ?> <a class="tips" href="#">[?]</a></label>
        <label><input type="checkbox" name="_week_days[<?php echo $loop; ?>][]" <?php if( in_array( 'monday', $week_days ) ) { echo 'checked'; } ?>    value="monday" /><?php _e( 'Monday', 'registrations-for-woocommerce' ); ?></label>
        <label><input type="checkbox" name="_week_days[<?php echo $loop; ?>][]" <?php if( in_array( 'sunday', $week_days ) ) { echo 'checked'; } ?>    value="sunday" /><?php _e( 'Sunday', 'registrations-for-woocommerce' ); ?></label>
        <label><input type="checkbox" name="_week_days[<?php echo $loop; ?>][]" <?php if( in_array( 'tuesday', $week_days ) ) { echo 'checked'; } ?>   value="tuesday" /><?php _e( 'Tuesday', 'registrations-for-woocommerce' ); ?></label>
        <label><input type="checkbox" name="_week_days[<?php echo $loop; ?>][]" <?php if( in_array( 'wednesday', $week_days ) ) { echo 'checked'; } ?> value="wednesday" /><?php _e( 'Wednesday', 'registrations-for-woocommerce' ); ?></label>
        <label><input type="checkbox" name="_week_days[<?php echo $loop; ?>][]" <?php if( in_array( 'thursday', $week_days ) ) { echo 'checked'; } ?>  value="thursday" /><?php _e( 'Thursday', 'registrations-for-woocommerce' ); ?></label>
        <label><input type="checkbox" name="_week_days[<?php echo $loop; ?>][]" <?php if( in_array( 'friday', $week_days ) ) { echo 'checked'; } ?>    value="friday" /><?php _e( 'Friday', 'registrations-for-woocommerce' ); ?></label>
        <label><input type="checkbox" name="_week_days[<?php echo $loop; ?>][]" <?php if( in_array( 'saturday', $week_days ) ) { echo 'checked'; } ?>  value="saturday" /><?php _e( 'Saturday', 'registrations-for-woocommerce' ); ?></label>
    </p>
</div>
