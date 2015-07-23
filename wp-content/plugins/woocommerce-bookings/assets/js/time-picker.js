jQuery(document).ready(function($) {

	$('.block-picker').on('click', 'a', function(){
		var value  = $(this).data('value');
		var target = $(this).closest('div').find('input');

		target.val( value ).change();
		$(this).closest('ul').find('a').removeClass('selected');
		$(this).addClass('selected');

		return false;
	});

	$('#wc_bookings_field_resource, #wc_bookings_field_duration').change(function(){
		show_available_time_blocks( this );
	});
	$('.wc-bookings-booking-form').on( 'date-selected', function() {
		show_available_time_blocks( this );
	});

	var xhr;

	function show_available_time_blocks( element ) {
		var $form               = $(element).closest('form');
		var block_picker        = $form.find('.block-picker');
		var fieldset            = $form.find('.wc_bookings_field_start_date');

		var year  = parseInt( fieldset.find( 'input.booking_date_year' ).val(), 10 );
		var month = parseInt( fieldset.find( 'input.booking_date_month' ).val(), 10 );
		var day   = parseInt( fieldset.find( 'input.booking_date_day' ).val(), 10 );

		if ( ! year || ! month || ! day ) {
			return;
		}

		// clear blocks
		block_picker.closest('div').find('input').val( '' ).change();
		block_picker.closest('div').block({message: null, overlayCSS: {background: '#fff url(' + booking_form_params.ajax_loader_url + ') no-repeat center', backgroundSize: '16px 16px', opacity: 0.6}}).show();

		// Get blocks via ajax
		if ( xhr ) xhr.abort();

		xhr = $.ajax({
			type: 		'POST',
			url: 		booking_form_params.ajax_url,
			data: 		{
				action: 'wc_bookings_get_blocks',
				form:   $form.serialize()
			},
			success: function( code ) {
				block_picker.html( code );
				resize_blocks();
				block_picker.closest('div').unblock();
			},
			dataType: 	"html"
		});
	}

	function resize_blocks() {
		max_width = 0;

		$('.block-picker a').each(function() {
			width = $(this).width();
			if ( width > max_width) {
				max_width = width
			}
		});

		$('.block-picker a').width( max_width );
	}
});
