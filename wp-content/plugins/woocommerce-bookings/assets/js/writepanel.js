jQuery(document).ready(function($) {

	$('#bookings_availability, #bookings_pricing').on('change', '.wc_booking_availability_type select, .wc_booking_pricing_type select', function(){
		var value = $(this).val();
		var row   = $(this).closest('tr');

		$(row).find('.from_date, .from_day_of_week, .from_month, .from_week, .from_time, .from').hide();
		$(row).find('.to_date, .to_day_of_week, .to_month, .to_week, .to_time, .to').hide();

		if ( value == 'custom' ) {
			$(row).find('.from_date, .to_date').show();
		}
		if ( value == 'months' ) {
			$(row).find('.from_month, .to_month').show();
		}
		if ( value == 'weeks' ) {
			$(row).find('.from_week, .to_week').show();
		}
		if ( value == 'days' ) {
			$(row).find('.from_day_of_week, .to_day_of_week').show();
		}
		if ( value.match( "^time" ) ) {
			$(row).find('.from_time, .to_time').show();
		}
		if ( value == 'persons' || value == 'duration' || value == 'blocks' ) {
			$(row).find('.from, .to').show();
		}
	});

	$('body').on('row_added', function(){
		$('.wc_booking_availability_type select, .wc_booking_pricing_type select').change();

		$( '.date-picker' ).datepicker({
			dateFormat: 'yy-mm-dd',
			numberOfMonths: 1,
			showButtonPanel: true,
			showOn: 'button',
			buttonImage: wc_bookings_writepanel_js_params.calendar_image,
			buttonImageOnly: true
		});
	});

	$('body').on( 'woocommerce-product-type-change', function( type ) {
		if ( type !== 'booking' ) {
			$('#_wc_booking_has_persons').removeAttr( 'checked' );
			$('#_wc_booking_has_resources').removeAttr( 'checked' );
		}
		wc_bookings_trigger_change_events();
	});

	function wc_bookings_trigger_change_events() {
		$('.wc_booking_availability_type select, .wc_booking_pricing_type select, #_wc_booking_duration_type, #_wc_booking_duration_unit, #_wc_booking_has_persons, #_wc_booking_has_resources, #_wc_booking_has_person_types').change();
	}

	$( 'input#_virtual' ).change( function () {
		wc_bookings_trigger_change_events();
	});

	$('#_wc_booking_duration_type').change(function() {
		if ( $(this).val() == 'customer' ) {
			$( '#min_max_duration' ).show();
		} else {
			$( '#min_max_duration' ).hide();
		}
	});

	$( '#_wc_booking_duration_unit' ).change(function() {
		$('.availability_time, ._wc_booking_first_block_time_field').show();

		if ( $(this).val() != 'hour' && $(this).val() != 'minute' ) {
			$('.availability_time, ._wc_booking_first_block_time_field').hide();
		}
	});

	$('#_wc_booking_has_persons').change(function() {
		if ( $(this).is( ':checked' ) ) {
			$( '#persons-options, .bookings_persons_tab' ).show();
		} else {
			$( '#persons-options, .bookings_persons_tab' ).hide();
		}

		$('ul.wc-tabs li:visible').eq(0).find('a').click();
	});

	$('#_wc_booking_has_person_types').change(function() {
		if ( $(this).is( ':checked' ) ) {
			$( '#persons-types' ).show();
		} else {
			$( '#persons-types' ).hide();
		}
	});

	$('#_wc_booking_has_resources').change(function() {
		if ( $(this).is( ':checked' ) ) {
			$( '.bookings_resources_tab' ).show();
		} else {
			$( '.bookings_resources_tab' ).hide();
		}

		$('ul.wc-tabs li:visible').eq(0).find('a').click();
	});

	wc_bookings_trigger_change_events();

	$('#availability_rows, #pricing_rows').sortable({
		items:'tr',
		cursor:'move',
		axis:'y',
		handle: '.sort',
		scrollSensitivity:40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'wc-metabox-sortable-placeholder',
		start:function(event,ui){
			ui.item.css('background-color','#f6f6f6');
		},
		stop:function(event,ui){
			ui.item.removeAttr('style');
		}
	});

	$( '.date-picker' ).datepicker({
		dateFormat: 'yy-mm-dd',
		numberOfMonths: 1,
		showButtonPanel: true,
		showOn: 'button',
		buttonImage: wc_bookings_writepanel_js_params.calendar_image,
		buttonImageOnly: true
	});

	$( '.add_row' ).click(function(){
		$(this).closest('table').find('tbody').append( $( this ).data( 'row' ) );
		$('body').trigger('row_added');
		return false;
	});

	$('body').on('click', 'td.remove', function(){
		$(this).closest('tr').remove();
		return false;
	});

	$('#bookings_persons').on('change', 'input.person_name', function(){
		$(this).closest('.woocommerce_booking_person').find('span.person_name').text( $(this).val() );
	});

	// Add a person type
	jQuery('#bookings_persons').on('click', 'button.add_person', function(){
		jQuery('.woocommerce_bookable_persons').block({ message: null, overlayCSS: { background: '#fff url(' + wc_bookings_writepanel_js_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		var loop = jQuery('.woocommerce_booking_person').size();

		var data = {
			action:   'woocommerce_add_bookable_person',
			post_id:  wc_bookings_writepanel_js_params.post,
			loop:     loop,
			security: wc_bookings_writepanel_js_params.nonce_add_person
		};

		jQuery.post( wc_bookings_writepanel_js_params.ajax_url, data, function( response ) {
			jQuery('.woocommerce_bookable_persons').append( response ).unblock();
			jQuery('.woocommerce_bookable_persons #message').hide();
			$( '.woocommerce_bookable_persons' ).sortable( persons_sortable_options );
		});

		return false;
	});

	// Remove a person type
	jQuery('#bookings_persons').on('click', 'button.remove_booking_person', function(e){
		e.preventDefault();
		var answer = confirm( wc_bookings_writepanel_js_params.i18n_remove_person );
		if ( answer ) {

			var el = jQuery(this).parent().parent();

			var person = jQuery(this).attr('rel');

			if ( person > 0 ) {

				jQuery(el).block({ message: null, overlayCSS: { background: '#fff url(' + wc_bookings_writepanel_js_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

				var data = {
					action:    'woocommerce_remove_bookable_person',
					person_id: person,
					security:  wc_bookings_writepanel_js_params.nonce_delete_person
				};

				jQuery.post( wc_bookings_writepanel_js_params.ajax_url, data, function( response ) {
					jQuery(el).fadeOut('300', function(){
						jQuery(el).remove();
					});
				});

			} else {
				jQuery(el).fadeOut('300', function(){
					jQuery(el).remove();
				});
			}

		}
		return false;
	});

	var persons_sortable_options = {
		items: '.woocommerce_booking_person',
		cursor: 'move',
		axis: 'y',
		handle: 'h3',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'wc-metabox-sortable-placeholder',
		start: function( event, ui ) {
			ui.item.css( 'background-color', '#f6f6f6' );
		},
		stop: function ( event, ui ) {
			ui.item.removeAttr( 'style' );
			person_row_indexes();
		}
	};

	$( '.woocommerce_bookable_persons' ).sortable( persons_sortable_options );

	function person_row_indexes() {
		$('.woocommerce_bookable_persons .woocommerce_booking_person').each(function(index, el){
			$('.person_menu_order', el).val( parseInt( $(el).index('.woocommerce_bookable_persons .woocommerce_booking_person'), 10 ) );
		});
	};

	$('#bookings_resources').on('change', 'input.resource_name', function(){
		$(this).closest('.woocommerce_booking_resource').find('span.resource_name').text( $(this).val() );
	});

	// Add a resource
	jQuery('#bookings_resources').on('click', 'button.add_resource', function(){
		jQuery('.woocommerce_bookable_resources').block({ message: null, overlayCSS: { background: '#fff url(' + wc_bookings_writepanel_js_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

		var loop              = jQuery('.woocommerce_booking_resource').size();
		var add_resource_id   = jQuery('.add_resource_id').val();
		var add_resource_name = '';

		if ( ! add_resource_id ) {
			add_resource_name = prompt( wc_bookings_writepanel_js_params.i18n_new_resource_name );

			if ( ! add_resource_name ) {
				return false;
			}
		}

		var data = {
			action:            'woocommerce_add_bookable_resource',
			post_id:           wc_bookings_writepanel_js_params.post,
			loop:              loop,
			add_resource_id:   add_resource_id,
			add_resource_name: add_resource_name,
			security:          wc_bookings_writepanel_js_params.nonce_add_resource
		};

		jQuery.post( wc_bookings_writepanel_js_params.ajax_url, data, function( response ) {
			if ( response.error ) {
				alert( response.error );
			} else {
				jQuery( '.woocommerce_bookable_resources' ).append( response.html ).unblock();
				jQuery( '.woocommerce_bookable_resources' ).sortable( resources_sortable_options );
				if ( add_resource_id ) {
					jQuery( '.add_resource_id' ).find( 'option[value=' + add_resource_id + ']' ).remove();
				}
			}
		});

		return false;
	});

	// Remove a resource
	jQuery('#bookings_resources').on('click', 'button.remove_booking_resource', function(e){
		e.preventDefault();
		var answer = confirm( wc_bookings_writepanel_js_params.i18n_remove_resource );
		if ( answer ) {

			var el       = jQuery(this).parent().parent();
			var resource = jQuery(this).attr('rel');

			jQuery(el).block({ message: null, overlayCSS: { background: '#fff url(' + wc_bookings_writepanel_js_params.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center', opacity: 0.6 } });

			var data = {
				action:      'woocommerce_remove_bookable_resource',
				post_id:     wc_bookings_writepanel_js_params.post,
				resource_id: resource,
				security:    wc_bookings_writepanel_js_params.nonce_delete_resource
			};

			jQuery.post( wc_bookings_writepanel_js_params.ajax_url, data, function( response ) {
				jQuery(el).fadeOut('300', function(){
					jQuery(el).remove();
				});
			});
		}
		return false;
	});

	var resources_sortable_options = {
		items: '.woocommerce_booking_resource',
		cursor: 'move',
		axis: 'y',
		handle: 'h3',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65,
		placeholder: 'wc-metabox-sortable-placeholder',
		start: function( event, ui ) {
			ui.item.css( 'background-color', '#f6f6f6' );
		},
		stop: function ( event, ui ) {
			ui.item.removeAttr( 'style' );
			resource_row_indexes();
		}
	};

	$( '.woocommerce_bookable_resources' ).sortable( resources_sortable_options );

	function resource_row_indexes() {
		$('.woocommerce_bookable_resources .woocommerce_booking_resource').each(function(index, el){
			$('.resource_menu_order', el).val( parseInt( $(el).index('.woocommerce_bookable_resources .woocommerce_booking_resource'), 10 ) );
		});
	};
});
