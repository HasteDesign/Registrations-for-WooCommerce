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
    $value = "";
    $dates = null;
    $name = "";

    // Output All Set Attributes
    if ( !empty( $attributes ) ) {
        $attribute_keys  = array_keys( $attributes );
        $attribute_total = sizeof( $attribute_keys );

        for ( $i = 0; $i < $attribute_total; $i ++ ) {
            $attribute     = $attributes[ $attribute_keys[ $i ] ];

            if( $attribute['name'] == 'Dates') {
                $value = trim( $attribute['value'], '"');
                $value = htmlspecialchars( $value, ENT_COMPAT, false);
                $dates = explode( WC_DELIMITER, $attribute['value'] );
            }
        }
    }

    if ( !empty( $dates ) ) {
        $name = 'Dates';
    }
?>
    <div id="registration_dates" class="panel woocommerce_options_panel">
        <div class="general_dates">

            <!-- Hidden Fields -->
            <input type="hidden" id="hidden_name" class="attribute_name" name="attribute_names[0]" value="<?php echo $name; ?>">
            <input type="hidden" id="hidden_position" name="attribute_position[0]" class="attribute_position" value="0">
            <input type="hidden" id="hidden_taxonomy" name="attribute_is_taxonomy[0]" value="0">
            <input type="hidden" id="hidden_visibility" class="checkbox" checked="checked" name="attribute_visibility[0]" value="0">
            <input type="hidden" id="hidden_variation" class="checkbox" name="attribute_variation[0]" value="1" disabled="true">
            <input type="hidden" id="hidden_date" name="attribute_values[0]" value="">

            <!-- BEGIN: Templates -->

            <!-- Single Date -->
            <script type="text/template" class="template-single_date">
                <div class="single_date options_group">
                    <h3><?php echo '#0 - ' . __( 'Single Day', 'woocommerce-registrations'); ?></h3>
                    <p class="form-field">
                        <label for="event_start_date"><?php _e( 'Event Day', 'woocommerce-registrations'); ?></label>
                        <input type="date" class="wc_input_event_date event_date" name="event_start_date" value="<?php echo date("Y-m-d");?>">
                        <button style="float:right;" type="button" class="remove_date button"><?php _e( 'Remove', 'woocommerce-registrations' ); ?></button>
                    </p>
                </div>
            </script>

            <!-- Multiple Date -->
            <script type="text/template" class="template-multiple_date">
                <div class="multiple_date options_group">
                    <h3><?php  echo '#0 - ' . __( 'Multiple Days', 'woocommerce-registrations'); ?></h3>
                    <p class="form-field multiple_date_inputs">
                        <label for="event_start_date"><?php _e( 'Day', 'woocommerce-registrations'); ?></label>
                        <input type="date" class="wc_input_event_date event_date" name="event_start_date" value="<?php echo date("Y-m-d");?>">
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
                    <h3><?php  echo '#0 - ' . __( 'Range Date', 'woocommerce-registrations'); ?></h3>
                    <p class="form-field">
                        <label for="event_range_date"><?php _e( 'Event start and end date', 'woocommerce-registrations'); ?></label>
                        <span class="conjuncao"><?php _e( 'From', 'woocommerce-registrations' ); ?></span>
                        <input type="date" class="wc_input_event event_start_date event_date" name="event_start_date" value="<?php echo date("Y-m-d");?>" >
                        <span class="conjuncao"><?php _e( 'to', 'woocommerce-registrations' ); ?></span>
                        <?php
                            // Tomorrow's Date
                            $datetime = new DateTime('tomorrow');
                        ?>
                        <input type="date" class="wc_input_event event_end_date event_date" name="event_end_date" value="<?php echo $datetime->format('Y-m-d'); ?>">
                        <button style="float:right;" type="button" class="remove_date button"><?php _e( 'Remove', 'woocommerce-registrations' ); ?></button>
                    </p>
                    <p class="validation_message" style="display: none;">
                    <?php _e('Please set the end date after the start date.', 'woocommerce_registrations' ); ?>
                    </p>
                </div>
            </script>

            <!-- Multiple Date - Day -->
            <script type="text/template" class="template-multiple_date_inputs">
                <p class="form-field multiple_date_inputs">
                    <label for="event_start_date"><?php _e( 'Day', 'woocommerce-registrations'); ?></label>
                    <input type="date" class="wc_input_event_date event_date" name="event_date" value="2011-09-29">
                    <button type="button" class="remove_day button"><?php _e( 'Remove Day', 'woocommerce-registrations' ); ?></button>
                </p>
            </script>

            <!-- END: Templates -->

            <!-- Dates -->
            <div class="options_group dates">
                <?php
                //Display existent dates
                if( !empty( $dates ) ) {

                    $date_id = 0;

                    foreach ( $dates as $date ) {

                        $date = json_decode( $date );

                        if( !empty( $date ) ) {

                            if( $date->type == 'single' ) :
                ?>
                    <div class="single_date options_group">
                        <h3><?php echo '#' . $date_id . ' - ' . __( 'Single Day', 'woocommerce-registrations'); ?></h3>
                        <p class="form-field">
                            <label for="event_start_date"><?php _e( 'Event Day', 'woocommerce-registrations'); ?></label>
                            <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="<?php echo $date->date; ?>">
                            <button style="float:right;" type="button" class="remove_date button"><?php _e( 'Remove', 'woocommerce-registrations' ); ?></button>
                        </p>
                    </div>
                <?php
                            elseif ( $date->type == 'multiple' ) :
                ?>
                <div class="multiple_date options_group">
                    <h3><?php echo '#' . $date_id . ' - ' . __( 'Multiple Days', 'woocommerce-registrations'); ?></h3>
                    <?php
                        //$days = explode( ',', $date->dates );

                            foreach( $date->dates as $day ) :
                    ?>
                        <p class="form-field multiple_date_inputs">
                            <label for="event_start_date"><?php _e( 'Day', 'woocommerce-registrations'); ?></label>
                            <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="<?php echo $day; ?>">
                            <button type="button" class="remove_day button"><?php _e( 'Remove Day', 'woocommerce-registrations' ); ?></button>
                        </p>
                    <?php
                            endforeach;
                    ?>
                    <p class="form-field" >
                        <button style="float:right;" type="button" class="remove_date button"><?php _e( 'Remove All', 'woocommerce-registrations' ); ?></button>
                        <button style="float:right;" type="button" class="add_day button"><?php _e( 'Add Day', 'woocommerce-registrations' ); ?></button>
                    </p>
                </div>
                <?php
                            elseif ( $date->type == 'range' ) :
                ?>
                <div class="range_date options_group">
                    <h3><?php echo '#' . $date_id . ' - ' . __( 'Range Date', 'woocommerce-registrations'); ?></h3>
                    <p class="form-field">
                        <label for="event_range_date"><?php _e( 'Event start and end date', 'woocommerce-registrations'); ?></label>
                        <span class="conjuncao"><?php _e( 'From', 'woocommerce-registrations' ); ?></span>
                        <input type="date" class="wc_input_event_start_date event_date" name="event_start_date" id="event_start_date" value="<?php echo $date->dates[0]; ?>" >
                        <span class="conjuncao"><?php _e( 'to', 'woocommerce-registrations' ); ?></span>
                        <input type="date" class="wc_input_event_end_date event_date" name="event_start_date" id="event_end_date" value="<?php echo $date->dates[1]; ?>" >
                        <button style="float:right;" type="button" class="remove_date button"><?php _e( 'Remove', 'woocommerce-registrations' ); ?></button>
                    </p>
                </div>
                <?php
                            endif;

                            $date_id++;
                        }
                    }
                }
                ?>
            </div>

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

        <!-- Dialog - Saved Dates -->
        <div id="saved-dates-message" style="display: none;">
            <h3><?php _e( 'Saved Dates', 'woocommerce-registrations' ); ?></h3>
            <p><?php _e( 'Your dates have been saved. Go into the Variations Tab and create a variation for each of your dates and continue to configure your registration date specific information.', 'woocommerce-registrations' ); ?></p>
        </div>

    </div>
