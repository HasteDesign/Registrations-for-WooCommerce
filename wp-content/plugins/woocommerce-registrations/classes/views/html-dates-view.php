<?php
    /*
     *  Turmas/Dates - Admin Tab View
     */
    global $thepostid, $post, $woocommerce, $wc_product_attributes;

    if( empty( $thepostid ) ) {
        $thepostid = $post->ID;
    }

    // Product attributes - taxonomies and custom, ordered, with visibility and variation attributes set
    $attributes           = maybe_unserialize( get_post_meta( $thepostid, '_product_attributes', true ) );

    // echo '<pre>';
    // var_dump( $attributes['pa_dates'] );
    // echo '</pre>';

    // Output All Set Attributes
    // if ( ! empty( $attributes ) ) {
    //     $attribute_keys  = array_keys( $attributes );
    //     $attribute_total = sizeof( $attribute_keys );
    //
    //     for ( $i = 0; $i < $attribute_total; $i ++ ) {
    //         $attribute     = $attributes[ $attribute_keys[ $i ] ];
    //         $position      = empty( $attribute['position'] ) ? 0 : absint( $attribute['position'] );
    //         $taxonomy      = '';
    //         $metabox_class = array();
    //
    //         if ( $attribute['is_taxonomy'] ) {
    //             $taxonomy = $attribute['name'];
    //
    //             if ( ! taxonomy_exists( $taxonomy ) ) {
    //                 continue;
    //             }
    //
    //             $attribute_taxonomy = $wc_product_attributes[ $taxonomy ];
    //             $metabox_class[]    = 'taxonomy';
    //             $metabox_class[]    = $taxonomy;
    //             $attribute_label    = wc_attribute_label( $taxonomy );
    //         } else {
    //             $attribute_label    = apply_filters( 'woocommerce_attribute_label', $attribute['name'], $attribute['name'] );
    //         }
    //
    //     }
    // }
?>
    <div id="registration_dates" class="panel woocommerce_options_panel">
        <div class="general_dates">

        <!-- Hidden Fields -->
        <input type="hidden" class="attribute_name" name="attribute_names[0]" value="pa_dates">
        <input type="hidden" name="attribute_position[0]" class="attribute_position" value="0">
        <input type="hidden" name="attribute_is_taxonomy[0]" value="0">
        <input type="hidden" class="checkbox" checked="checked" name="attribute_visibility[0]" value="0">
        <input type="hidden" class="checkbox" name="attribute_variation[0]" value="1">
        <input type="hidden" id="hidden_date" name="attribute_values[0]" value="">

        <!-- BEGIN: Templates -->

        <!-- Single Date -->
        <script type="text/template" class="template-single_date">
            <div class="single_date options_group">
                <h3><?php _e( 'Single Day', 'woocommerce-registrations'); ?></h3>
                <p class="form-field">
                    <label for="event_start_date"><?php _e( 'Event Day', 'woocommerce-registrations'); ?></label>
                    <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="2011-09-29">
                    <button style="float:right;" type="button" class="remove_date button"><?php _e( 'Remove', 'woocommerce-registrations' ); ?></button>
                </p>
            </div>
        </script>

        <!-- Multiple Date -->
        <script type="text/template" class="template-multiple_date">
            <div class="multiple_date options_group">
                <h3><?php _e( 'Multiple Days', 'woocommerce-registrations'); ?></h3>
                <p class="form-field multiple_date_inputs">
                    <label for="event_start_date"><?php _e( 'Day', 'woocommerce-registrations'); ?></label>
                    <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="2011-09-29">
                    <button type="button" class="remove_day button"><?php _e( 'Remove Day', 'woocommerce-registrations' ); ?></button>
                </p>
                <p class="form-field" >
                    <button style="float:right;" type="button" class="remove_date button"><?php _e( 'Remove All', 'woocommerce-registrations' ); ?></button>
                    <button style="float:right;" type="button" class="add_day button"><?php _e( 'Add Day', 'woocommerce-registrations' ); ?></button>
                </p>
            </div>
        </script>

        <!-- Range Date -->
        <script type="text/template" class="template-range_date">
            <div class="range_date options_group">
                <h3><?php _e( 'Range Date', 'woocommerce-registrations'); ?></h3>
                <p class="form-field">
                    <label for="event_range_date"><?php _e( 'Event start and end date', 'woocommerce-registrations'); ?></label>
                    <span class="conjuncao"><?php _e( 'From', 'woocommerce-registrations' ); ?></span>
                    <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="2011-09-29" >
                    <span class="conjuncao"><?php _e( 'to', 'woocommerce-registrations' ); ?></span>
                    <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="2011-09-29" >
                    <button style="float:right;" type="button" class="remove_date button"><?php _e( 'Remove', 'woocommerce-registrations' ); ?></button>
                </p>
            </div>
        </script>

        <!-- Multiple Date - Day -->
        <script type="text/template" class="template-multiple_date_inputs">
            <p class="form-field multiple_date_inputs">
                <label for="event_start_date"><?php _e( 'Day', 'woocommerce-registrations'); ?></label>
                <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="2011-09-29">
                <button type="button" class="remove_day button"><?php _e( 'Remove Day', 'woocommerce-registrations' ); ?></button>
            </p>
        </script>
        <!-- END: Templates -->

        <!-- Dates -->
        <div class="options_group dates"></div>

        <!-- Toolbar -->
        <p class="toolbar">
            <button type="button" class="button save_date_attributes"><?php _e( 'Save Dates', 'woocommerce-registrations' ); ?></button>
            <button style="float:right" type="button" class="button add_date_field"><?php _e( 'Add New Date', 'woocommerce-registrations' ); ?></button>
            <select style="float:right" name="date_select" class="date_select">
				<option value="single_date"><?php _e( 'Single Date', 'woocommerce-registrations'); ?></option>
                <option value="multiple_date"><?php _e( 'Multiple Date', 'woocommerce-registrations'); ?></option>
                <option value="range_date"><?php _e( 'Range Date', 'woocommerce-registrations'); ?></option>
            </select>
        </p>

        </div>
    </div>
