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

    echo '<pre>';
    var_dump( $attributes['pa_dates'] );
    echo '</pre>';

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
        <div class="options_group dates">

        <!-- Hidden Fields -->
        <input type="hidden" class="attribute_name" name="attribute_names[0]" value="pa_dates">
        <input type="hidden" name="attribute_position[0]" class="attribute_position" value="0">
        <input type="hidden" name="attribute_is_taxonomy[0]" value="0">
        <input type="hidden" class="checkbox" checked="checked" name="attribute_visibility[0]" value="1">
        <input type="hidden" class="checkbox" name="attribute_variation[0]" value="1">
        <input type="hidden" id="hidden_date" name="attribute_values[0]" value="">

        <div class="options_group dates">
            <!-- Simple Date -->
            <div class="simple_date">
                <p class="form-field event_start_date_field ">
                    <label for="event_start_date"><?php _e( 'Date', 'woocommerce-registrations'); ?></label>
                    <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="2011-09-29">
                </p>
            </div>

            <!-- Multiple Date -->
            <div class="multiple_date">
                <p class="form-field event_start_date_field ">
                    <label for="event_start_date"><?php _e( 'Day', 'woocommerce-registrations'); ?></label>
                    <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="" placeholder="10/07/2015">
                </p>
            </div>

            <!-- Range Date -->
            <div class="range_date">
                <p class="form-field event_start_date_field ">
                    <span class="conjuncao"><?php _e( 'From', 'woocommerce-registrations' ); ?></span>
                    <label for="event_start_date"><?php _e( 'Date', 'woocommerce-registrations'); ?></label>
                    <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="" placeholder="10/07/2015">
                    <span class="conjuncao"><?php _e( 'to', 'woocommerce-registrations' ); ?></span>
                    <label for="event_start_date"><?php _e( 'Date', 'woocommerce-registrations'); ?></label>
                    <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="" placeholder="10/07/2015">
                </p>
            </div>
        </div>

        <!-- Toolbar -->
        <p class="toolbar">
            <button type="button" class="button save_date_attributes"><?php _e( 'Save Dates', 'woocommerce-registrations' ); ?></button>
            <button style="float:right" type="button" class="button add_date_field"><?php _e( 'Add New Date', 'woocommerce-registrations' ); ?></button>
            <select style="float:right" name="date_select" class="date_select">
				<option value=""><?php _e( 'Simple Date', 'woocommerce-registrations'); ?></option>
                <option value=""><?php _e( 'Multiple Date', 'woocommerce-registrations'); ?></option>
                <option value=""><?php _e( 'Range Date', 'woocommerce-registrations'); ?></option>
            </select>
        </p>

        </div>
    </div>
