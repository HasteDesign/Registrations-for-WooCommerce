/* global WCRegistrations */
jQuery( function( $ ) {

	var wc_meta_boxes_product_registrations = {
		/**
		 * Initialize show/hide registration fields
		 */
		init: function() {
			// Dates Tab Events
			$( '#registration_dates' )
				.on( 'change','.event_date', this.update_hidden_field )
				.on( 'click', '.add_date_field', this.add_date_field )
				.on( 'click', '.remove_date', this.remove_date )
				.on( 'click', '.add_day', this.add_day )
				.on( 'click', '.remove_day', this.remove_day );

			// Variations Tab Events
			$( '#variable_product_options' ).on( 'woocommerce_variations_added' , function() {
				this.default_registration_values();
				this.show_hide_registration_meta();
			});

			// Re-count the hidden inputs index when new attribute added
			if ( 'true' == WCRegistrations.isWCPre23 ){
				$('button.add_attribute').on('click', this.adjustAttributesIndex() );
			} else {
				// WC 2.3 - run after the Ajax request has inserted variation HTML
				$( 'body' ).on( 'woocommerce_added_attribute' , this.adjustAttributesIndex() );
			}

			// Make sure the "Used for variations" checkbox is visible when adding attributes to a variable subscription
			if ( 'true' == WCRegistrations.isWCPre23){
				$( 'button.add_attribute' ).on( 'click' , this.show_hide_registration_meta );
			} else {
				// WC 2.3 - run after the Ajax request has inserted variation HTML
				$( 'body' ).on( 'woocommerce_added_attribute' , this.show_hide_registration_meta );
			}


			this.show_hide_registration_meta();
		},

		show_hide_registration_meta: function() {
			if ( $( 'select#product-type' ).val() == 'registrations' ) {
				$( '.hide_if_virtual' ).hide();
				$( '.show_if_variable' ).show();
				$( '.show_if_registration' ).show();
				$( '.hide_if_registration' ).hide();

				$( 'input#_manage_stock' ).change();
				$( 'input#_downloadable' ).prop( 'checked', false );
			} else {
				$('.show_if_registration').hide();
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

		add_date: function( event ) {
			// Get the date type defined on select
			var value = $( 'select[name="date_select"]' ).val();

			el = $( 'script.template-' + value ).html();
			//el = $('.date_models').children( '.' + value ).clone();
			$( '.dates' ).append( el );

			event.preventDefault();
		},

		add_day: function( event ) {
			el = $( 'script.template-multiple_date_inputs' ).html();
			$( this ).parent().before( el );
			event.preventDefault();
		},

		remove_day: function( event ) {
			$( this ).parent().remove();
			event.preventDefault();
		},

		remove_date: function( event ) {
			$( this ).parent().parent( 'div' ).remove();
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
		},

		single_date_value: function( el ) {
			var json_base = '{"type":"single","date":';
			var value = "";

			if( $( '#hidden_date' ).val() !== '' ) {
				value = $( '#hidden_date' ).val() + '|';
			}

			value += json_base + '"' + $( el ).find( 'input' ).val() + '"}';

			$( '#hidden_date' ).val( value );
			console.log( $( '#hidden_date' ).val() );
		},

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
				if( dates != null ) {
					dates += ',"' + $( this ).val() + '"';
				} else {
					dates = '"' + $( this ).val() + '"';
				}
			});

			value += dates + ']}';

			$( '#hidden_date' ).val( value );
			console.log( $( '#hidden_date' ).val() );
		},

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
				if( dates != null ) {
					dates += ',"' + $( this ).val() + '"';
				} else {
					dates = '"' + $( this ).val() + '"';
				}
			});

			value += dates + ']}';

			$('#hidden_date').val( value );
			console.log( $('#hidden_date').val() );
		},

		remove_date_attribute: function () {
			var $strong = $('.woocommerce_attribute').children('h3').children("strong:contains('Dates')");

			if( $strong ) {
				var $parent = $( $strong ).parent().parent();

				$parent.find('select, input[type=text]').val('');
				$parent.hide();
				$.attribute_row_indexes();
			}
		},

		adjust_attributes_index: function () {
			var length = 0;

			$( '.product_attributes' ).children('.woocommerce_attribute').each( function () {
				//if( $( this ).css( 'display' ) != 'none' ) {
					length += 1;
				//}
			});

			console.log( 'Attributes length: ' + length );

			$('#hidden_name').attr('name','attribute_names[' + length + ']');
			$('#hidden_position').attr('name','attribute_position[' + length + ']');
			$('#hidden_taxonomy').attr('name','attribute_is_taxonomy[' + length + ']');
			$('#hidden_visibility').attr('name','attribute_visibility[' + length + ']');
			$('#hidden_variation').attr('name','attribute_variation[' + length + ']');
			$('#hidden_date').attr('name','attribute_values[' + length + ']');

			$('#hidden_position').val( length );
		},

		attribute_row_indexes: function () {
			$('.product_attributes .woocommerce_attribute').each(function(index, el){
				$('.attribute_position', el).val( parseInt( $(el).index('.product_attributes .woocommerce_attribute') ) );
			});
		},

		handle_range_date_meta: function () {
			$( 'li.variations_tab a' ).on( 'click', function () {
				$( '.woocommerce_variation' ).each( function () {
					var value = $( this ).find( 'option:selected' ).val();

					if( value.indexOf( 'range' ) !== -1 ) {
						$( this ).find( '.show_if_range_date').show();
					} else {
						$( this ).find( '.show_if_range_date').hide();
					}
				});
			});
		}
	});

	wc_meta_boxes_product_registrations.init();

});
