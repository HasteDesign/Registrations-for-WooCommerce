jQuery(document).ready(function($) {
	function bookingsIsBookable( date ) {
		var $form                      = $( this ).closest('form');
		var availability               = $( this ).data( 'availability' );
		var default_availability       = $( this ).data( 'default-availability' );
		var fully_booked_days          = $( this ).data( 'fully-booked-days' );
		var check_availability_against = wc_bookings_booking_form.check_availability_against;

		// Get selected resource
		if ( $form.find('select#wc_bookings_field_resource').val() > 0 ) {
			var resource_id = $form.find('select#wc_bookings_field_resource').val();
		} else {
			var resource_id = 0;
		}

		// Get days needed for block - this affects availability
		var duration = wc_bookings_booking_form.booking_duration;
		var the_date = new Date( date );
		var year     = the_date.getFullYear();
		var month    = the_date.getMonth() + 1;
		var day      = the_date.getDate();

		// Fully booked?
		if ( fully_booked_days[ year + '-' + month + '-' + day ] ) {
			if ( fully_booked_days[ year + '-' + month + '-' + day ][0] || fully_booked_days[ year + '-' + month + '-' + day ][ resource_id ] ) {
				return [ false, 'fully_booked', booking_form_params.i18n_date_unavailable ];
			}
		}

		if ( '' + year + month + day < wc_bookings_booking_form.current_time ) {
			return [ false, 'not_bookable', '' ];
		}

		if ( $form.find('#wc_bookings_field_duration').size() > 0 && wc_bookings_booking_form.duration_unit != 'minute' && wc_bookings_booking_form.duration_unit != 'hour' ) {
			var user_duration = $form.find('#wc_bookings_field_duration').val();
			var days_needed   = duration * user_duration;
		} else {
			var days_needed   = duration;
		}

		if ( days_needed < 1 || check_availability_against == 'start' ) {
			days_needed = 1;
		}

		var bookable = default_availability;

		// Loop all the days we need to check for this block
		for ( var i = 0; i < days_needed; i++ ) {
			var the_date     = new Date( date );
			the_date.setDate( the_date.getDate() + i );

			var year        = the_date.getFullYear();
			var month       = the_date.getMonth() + 1;
			var day         = the_date.getDate();
			var day_of_week = the_date.getDay();
			var week        = $.datepicker.iso8601Week( the_date );

			// Reset bookable for each day being checked
			bookable = default_availability;

			// Sunday is 0, Monday is 1, and so on.
			if ( day_of_week == 0 ) {
				day_of_week = 7;
			}

			$.each( availability[ resource_id ], function( index, rule ) {
				var type  = rule[0];
				var rules = rule[1];
				try {
					switch ( type ) {
						case 'months':
							if ( typeof rules[ month ] != 'undefined' ) {
								bookable = rules[ month ];

								return false;
							}
						break;
						case 'weeks':
							if ( typeof rules[ week ] != 'undefined' ) {
								bookable = rules[ week ];

								return false;
							}
						break;
						case 'days':
							if ( typeof rules[ day_of_week ] != 'undefined' ) {
								bookable = rules[ day_of_week ];

								return false;
							}
						break;
						case 'custom':
							if ( typeof rules[ year ][ month ][ day ] != 'undefined' ) {
								bookable = rules[ year ][ month ][ day ];

								return false;
							}
						break;
					}
				} catch( err ) {}

				return true;
			});

			// Fully booked in entire block?
			if ( fully_booked_days[ year + '-' + month + '-' + day ] ) {
				if ( fully_booked_days[ year + '-' + month + '-' + day ][0] || fully_booked_days[ year + '-' + month + '-' + day ][ resource_id ] ) {
					bookable = false;
				}
			}

			if ( ! bookable ) {
				break;
			}
		}

		if ( ! bookable ) {
			return [ bookable, 'not_bookable', '' ];
		} else {
			return [ bookable, 'bookable', '' ];
		}
	}

	function bookingsDatePickerOnSelect( date ) {
		var $fieldset = $(this).closest('fieldset');
		var $picker   = $fieldset.find( '.picker' );
		var $form     = $(this).closest('form');

		if ( $picker.data( 'display' ) !== 'always_visible' ) {
			$(this).hide();
		}

		date = date.split('-');
		$fieldset.find( 'input.booking_date_year' ).val( date[0] );
		$fieldset.find( 'input.booking_date_month' ).val( date[1] );
		$fieldset.find( 'input.booking_date_day' ).val( date[2] ).change();
		$form.find( '.wc-bookings-booking-form').trigger( 'date-selected', date );
	}

	$( ".wc-bookings-date-picker legend small.wc-bookings-date-picker-choose-date" ).show().click(function(){
		$( this ).closest('fieldset').find('.picker').slideToggle();
	});

	function bookingsDatePickerInit( picker ) {
		$( picker ).empty().removeClass('hasDatepicker').datepicker({
			dateFormat: $.datepicker.ISO_8601,
			showWeek: false,
			showOn: "both",
			beforeShowDay: bookingsIsBookable,
			onSelect: bookingsDatePickerOnSelect,
			minDate: $( picker ).data('min_date'),
			maxDate: $( picker ).data('max_date'),
			numberOfMonths: 1,
			showButtonPanel: false,
			showOtherMonths: true,
    		selectOtherMonths: true,
			closeText: wc_bookings_booking_form.closeText,
			currentText: wc_bookings_booking_form.currentText,
			monthNames: wc_bookings_booking_form.monthNames,
			monthNamesShort: wc_bookings_booking_form.monthNamesShort,
			dayNames: wc_bookings_booking_form.dayNames,
			dayNamesShort: wc_bookings_booking_form.dayNamesShort,
			dayNamesMin: wc_bookings_booking_form.dayNamesMin,
			firstDay: wc_bookings_booking_form.firstDay,
			gotoCurrent: true
		});

		$('.ui-datepicker-current-day').removeClass('ui-datepicker-current-day');
	}

	$( ".wc-bookings-date-picker" ).each(function(){
		var $picker   = $(this).find( '.picker' );
		var $fieldset = $(this).closest('fieldset');

		bookingsDatePickerInit( $picker );

		if ( $picker.data( 'display' ) == 'always_visible' ) {
			$( '.wc-bookings-date-picker-date-fields', $fieldset ).hide();
			$( '.wc-bookings-date-picker-choose-date', $fieldset ).hide();
		} else {
			$picker.hide();
		}
	});

	$('.booking_date_year, .booking_date_month, .booking_date_day').bind( 'input', function(){
		var $fieldset = $(this).closest('fieldset');
		var $picker   = $fieldset.find( '.picker' );
		var year      = parseInt( $fieldset.find( 'input.booking_date_year' ).val(), 10 );
		var month     = parseInt( $fieldset.find( 'input.booking_date_month' ).val(), 10 );
		var day       = parseInt( $fieldset.find( 'input.booking_date_day' ).val(), 10 );
		var $form     = $(this).closest('form');

		if ( year && month && day ) {
			var date = new Date( year, month, day );
			$picker.datepicker( "setDate", date );
			$form.find( '.wc-bookings-booking-form').trigger( 'date-selected', date );
		}
	});

	$('#wc_bookings_field_duration, #wc_bookings_field_resource').change(function(){
		var $picker = $( this ).closest('form').find( '.picker' );

		bookingsDatePickerInit( $picker );
	});

});