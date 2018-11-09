/* global WCRegistrations */
jQuery( function( $ ) {

	var wc_meta_boxes_product_registrations = {
		/**
		 * Initialize event binding and call show/hide functions
		 */
		load: function() {
			console.log('load');
		},
		init: function() {
			// Dates Tab Events
			$( '#registration_dates' )
			.on( 'change', 	'.event_date', 			this.update_hidden_field )
			.on( 'click', 	'.add_date_field', 		this.add_date )
			.on( 'click', 	'.remove_date', 		this.remove_date )
			.on( 'click', 	'.add_day', 			this.add_day )
			.on( 'click', 	'.remove_day', 			this.remove_day )
			.on( 'change', 	'.wc_input_event_start_date', this.validate_range_date )
			.on( 'change', 	'.wc_input_event_end_date',   this.validate_range_date );

			$( '#_prevent_past_events').on( 'change', this.display_past_event_days );
			$( 'input#_manage_stock' ).on( 'change', this.show_hide_stock_options );

			// Variations Tab Events
			$( '#variable_product_options' ).on( 'woocommerce_variations_added' , function() {
				wc_meta_boxes_product_registrations.default_registration_values();
				wc_meta_boxes_product_registrations.show_hide_registration_meta();
			});

			$( '#woocommerce-product-data' ).on( 'woocommerce_variations_loaded', this.handle_range_date_meta );

			$( '#variable_product_options' ).on( 'change', 'select[name^="attribute_dates"]', this.handle_range_date_meta );

			$( 'body' ).on( 'woocommerce-product-type-change', this.show_hide_registration_meta );

			// Active tab
			$( 'body' ).on( 'woocommerce-product-type-change', this.active_tab );

			// Re-count the hidden inputs index when new attribute added
			if ( 'true' == WCRegistrations.isWCPre23 ){
				$( 'button.add_attribute' ).on( 'click', this.adjust_attributes_index );
			} else {
				// WC 2.3 - run after the Ajax request has inserted variation HTML
				$( 'body' ).on( 'woocommerce_added_attribute' , this.adjust_attributes_index );
			}

			// Make sure the "Used for variations" checkbox is visible when adding attributes to a variable subscription
			if ( 'true' == WCRegistrations.isWCPre23){
				$( 'button.add_attribute' ).on( 'click' , this.show_hide_registration_meta );
			} else {
				// WC 2.3 - run after the Ajax request has inserted variation HTML
				$( 'body' ).on( 'woocommerce_added_attribute' , this.show_hide_registration_meta );
			}

			this.show_hide_registration_meta();
			this.remove_date_attribute();
			this.adjust_attributes_index();
			this.update_hidden_field();
			this.datepicker_init();
			this.active_tab();
		},

		/**
		 * Set the active tab in product meta boxes, on woocommerce-product-type-change and on page first load
		 */
		active_tab: function() {
			$( 'ul.wc-tabs li:visible' ).eq( 0 ).find( 'a' ).click();
		},

		/**
		 * Show/Hide fields for registrations product-type
		 */
		show_hide_registration_meta: function() {
			if ( $( 'select#product-type' ).val() === 'registrations' ) {
				// Hide
				$( '.hide_if_virtual' ).hide();
				$( '.hide_if_variable' ).hide();
				$( '.hide_if_registration' ).hide();
				$( 'div.stock_fields' ).hide();
				// Show
				$( '.show_if_variable' ).show();
				$( '.show_if_registration' ).show();
				// Check
				$( 'input#_downloadable' ).prop( 'checked', false );
			} else {
				$( '.show_if_registration' ).hide();
			}

			if ( ! $( '#_prevent_past_events' ).is( ':checked' ) ) {
				$( 'p._days_to_prevent_field' ).hide();
			}

			wc_meta_boxes_product_registrations.show_hide_stock_options();
		},

		show_hide_stock_options: function() {
			if ( $( this ).is( ':checked' ) ) {
				$( 'div.stock_fields' ).show();
				$( 'p.stock_status_field' ).hide();
			} else {
				if ( $( 'select#product-type' ).val() === 'registrations' ) {
					$( 'div.stock_fields' ).hide();
					$( 'p.stock_status_field' ).hide();
				}
			}
		},

		display_past_event_days: function() {
			if( $(this).is(':checked') ) {
				$( '.registration_inventory p.show_if_registration' ).show();
			} else {
				$( '.registration_inventory p.show_if_registration' ).not($(this).parent()).hide();
			}
		},

		/**
		 * Initialize registrations fields with default values
		 */
		default_registration_values: function() {
			if ( $( 'select#product-type' ).val() == 'registrations' ) {
				// Toggle Virtual
				checkbox = $( 'input[name^="variable_is_virtual"]' );
				checkbox.attr( 'checked', true );
				$( 'input.variable_is_virtual' ).change();

				// Toggle Stock
				checkbox = $( 'input[name^="variable_manage_stock"]' );
				checkbox.attr( 'checked', true );
				$( 'input.variable_manage_stock' ).change();
			}
		},

		/**
		 * Add new date element from specified type in select
		 *
		 * @param {Object} event [description]
		 */
		add_date: function( event ) {
			// Get the date type defined on select
			var value = $( 'select[name="date_select"]' ).val();

			var el = $( 'script.template-' + value ).html();
			$( '.dates' ).append( el );
			wc_meta_boxes_product_registrations.dates_ids();
			wc_meta_boxes_product_registrations.datepicker_init();
			$( '.event_date' ).trigger( 'change' );
			event.preventDefault();
		},

		/**
		 * Add new day for multiple date date-type
		 *
		 * @param {Object} event [description]
		 */
		add_day: function( event ) {
			el = $( 'script.template-multiple_date_inputs' ).html();
			$( this ).parent().before( el );
			event.preventDefault();
		},

		/**
		 * Remove day of multiple date date-type
		 *
		 * @param {Object} event [description]
		 */
		remove_day: function( event ) {
			$( this ).parent().remove();
			event.preventDefault();
		},

		/**
		 * Remove one date
		 *
		 * @param {Object} event [description]
		 */
		remove_date: function( event ) {
			$( this ).parent().parent( 'div' ).remove();
			wc_meta_boxes_product_registrations.update_hidden_field();
			event.preventDefault();
		},

		/**
		 * Update hidden field values looping through all date fields (single/multiple/range)
		 */
		update_hidden_field: function() {
			//Cleanup Hidden Date Value
			$('#hidden_date').attr( 'value', '' );

			//Loop trough all date sections and catch your values
			$( '.dates' ).children().each( function() {
				if( $( this ).hasClass( 'single_date' ) )
				{
					wc_meta_boxes_product_registrations.single_date_value( this );
				}
				else if ( $( this ).hasClass( 'multiple_date' ) )
				{
					wc_meta_boxes_product_registrations.multiple_date_value( this );
				}
				else if ( $( this ).hasClass( 'range_date' ) )
				{
					wc_meta_boxes_product_registrations.range_date_value( this );
				}
			});

			/*
			* If hidden_value is not empty, set name to WooCommerce save the attribute and enable hidden_variation
			*/
			wc_meta_boxes_product_registrations.toggle_hidden_name_and_variation();
		},

		/**
		 * Check if name is empty and change variation disabled to false
		 */
		toggle_hidden_name_and_variation: function () {
			if( $('#hidden_date').attr( 'value' ) !== '' ) {

				$('#hidden_name').attr( 'value', 'Dates' );
				$('#hidden_variation').prop( "disabled", false );

			} else {

				$('#hidden_name').removeAttr( 'value' );
				$('#hidden_variation').prop( "disabled", true );
			}
		},

		/**
		 * Add single date value in JSON format to #hidden_date hidden field
		 *
		 * @param {$Object} el [current date element of update_hidden_field loop]
		 */
		single_date_value: function( el ) {
			var json_base = '{"type":"single","date":';
			var value = "";

			if( $( '#hidden_date' ).val() !== '' ) {
				value = $( '#hidden_date' ).val() + '|';
			}

			value += json_base + '"' + $( el ).find( 'input' ).val() + '"}';

			$( '#hidden_date' ).val( value );
		},

		/**
		 * Add multiple date value in JSON format to #hidden_date hidden field
		 *
		 * @param {$Object} el [current date element of update_hidden_field loop]
		 */
		multiple_date_value: function( el ) {
			var json_base = '{"type":"multiple","dates":[';
			var dates = null;
			var value = "";

			if( $( '#hidden_date' ).val() !== '' ) {
				value = $( '#hidden_date' ).val() + '|' + json_base;
			} else {
				value = json_base;
			}

			$( el ).find( 'input' ).each( function() {
				if( dates !== null ) {
					dates += ',"' + $( this ).val() + '"';
				} else {
					dates = '"' + $( this ).val() + '"';
				}
			});

			value += dates + ']}';

			$( '#hidden_date' ).val( value );
		},

		/**
		 * Add range date value in JSON format to #hidden_date hidden field
		 *
		 * @param {$Object} el [current date element of update_hidden_field loop]
		 */
		range_date_value: function( el ) {
			var json_base = '{"type":"range","dates":[';
			var dates = null;
			var value = "";

			if( $('#hidden_date').val() !== '' ) {
				value = $('#hidden_date').val() + '|' + json_base;
			} else {
				value = json_base;
			}

			$( el ).find( 'input' ).each( function() {
				if( dates !== null ) {
					dates += ',"' + $( this ).val() + '"';
				} else {
					dates = '"' + $( this ).val() + '"';
				}
			});

			value += dates + ']}';

			$('#hidden_date').val( value );
		},

		/**
		 * Hide date attribute from woocommerce_attribute tab. (Make users only able to edit date attributes trough dates tab)
		 */
		remove_date_attribute: function() {
			//get the attribute with "Dates" name
			var $strong = $('.woocommerce_attribute').find('input[value="Dates"]');

			if( $strong ) {
				//var $parent = $( $strong ).parent().parent().parent().parent().parent().parent();
				var $parent = $( $strong ).parent().closest( '.woocommerce_attribute' );

				$strong.val('');
				$parent.hide();
				this.attribute_row_indexes();
			}
		},

		/**
		 * Count the current number of dates created and display a simple #id
		 */
		dates_ids: function() {
			var length = 0;

			$( 'div.dates' ).children('div').each( function () {
				$( this ).find('h3').text( $( this ).find('h3').text().replace('#0', '#' + length ) );
				length += 1;
			});
		},

		/**
		 * Adjust attributes index to correct salvation of date attributes when new attributes added
		 */
		adjust_attributes_index: function () {
			var length = 0;

			$( '.product_attributes' ).children('.woocommerce_attribute').each( function () {
					length += 1;
			});

			$('#hidden_name').attr('name','attribute_names[' + length + ']');
			$('#hidden_position').attr('name','attribute_position[' + length + ']');
			$('#hidden_taxonomy').attr('name','attribute_is_taxonomy[' + length + ']');
			$('#hidden_visibility').attr('name','attribute_visibility[' + length + ']');
			$('#hidden_variation').attr('name','attribute_variation[' + length + ']');
			$('#hidden_date').attr('name','attribute_values[' + length + ']');

			$('#hidden_position').val( length );
		},

		/**
		 * Copied from woocommerce \assets\js\admin\meta-boxes-product.js
		 */
		attribute_row_indexes: function () {
			$('.product_attributes .woocommerce_attribute').each(function(index, el){
				$('.attribute_position', el).val( parseInt( $(el).index('.product_attributes .woocommerce_attribute') ) );
			});
		},

		/**
		 * Handle show/hide for range date-type variations meta fields [days of week]
		 */
		handle_range_date_meta: function () {
			$( '.woocommerce_variation' ).each( function () {
				var value = $( this ).find( 'option:selected' ).val();

				if( value.indexOf( 'range' ) !== -1 ) {
					$( this ).find( '.show_if_range_date').show();
				} else {
					$( this ).find( '.show_if_range_date').hide();
				}
			});
		},

		validate_range_date: function() {
			var $this = $( this ),
				$startField = $this.parent().find( '.wc_input_event_start_date' ),
				$endField = $this.parent().find( '.wc_input_event_end_date' ),
				start_val = $startField.val(),
				end_val = $endField.val(),
				start = new Date( start_val ),
				end = new Date( end_val );

			if( start >= end ) {
				if ( this.classList.contains('wc_input_event_start_date') ) {
					$endField.val(start_val);
				} else {
					$startField.val(end_val);
				}
			} else if ( this.classList.contains('wc_input_event_start_date') && ! $endField.val() ) {
				$endField.val(start_val);
			}
		},

		/**
		 * Call jQuery UI Datepicker for browser compatibility
		 */
		datepicker_init: function() {
			//Check if browser change the type property
			if ( $('[type="date"]').prop('type') !== 'date' ) {
			    $('[type="date"]').datepicker();
			}
			$('input.event_date.fixed_datepicker').each(function() {
				$(this).datepicker({
					dateFormat: $(this).attr('date_format')
				});
			});
		},
	};

	wc_meta_boxes_product_registrations.init();
});
