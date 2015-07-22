jQuery(document).ready(function($){
	$.extend({
		showHideRegistrationMeta: function(){
			if ( $('select#product-type').val() == 'registrations' ) {
				$('.hide_if_virtual').show();

				$('.show_if_variable').show();
				$('.show_if_registration').show();
				$('.hide_if_registration').hide();
			} else {
				$('.show_if_registration').hide();
			}
		}
	});

	// Grants correct fields display when product already saved as registration product
	$.showHideRegistrationMeta();

	// Show/Hide fields when product type changes
	$('body').bind('woocommerce-product-type-change',function(){
		$.showHideRegistrationMeta();
	});

	/*
	$('.options_group.pricing ._sale_price_field .description').prepend('<span id="sale-price-period" style="display: none;"></span>');

	// Move the subscription pricing section to the same location as the normal pricing section
	$('.options_group.subscription_pricing').not('.variable_subscription_pricing .options_group.subscription_pricing').insertBefore($('.options_group.pricing'));
	$('.show_if_subscription.clear').insertAfter($('.options_group.subscription_pricing'));

	// Move the subscription variation pricing section to a better location in the DOM on load
	if($('#variable_product_options .variable_subscription_pricing').length > 0) {
		$.moveSubscriptionVariationFields();
	}
	*/

	// Called when a variation is added
	$('#variable_product_options').on('woocommerce_variations_added',function(){
		//$.moveSubscriptionVariationFields();
		$.showHideRegistrationMeta();
	});

	// Add date values to hidden field
	$('.event_date').on( 'change', function() {
		var value = $('#hidden_date').val() + $(this).val() + '|';
		$('#hidden_date').val( value );
		console.log( $('.dates').children('input').serialize() );
	});

	/*
	if($('.options_group.pricing').length > 0) {
		$.setSalePeriod();
		$.showHideSubscriptionMeta();
		$.showHideVariableSubscriptionMeta();
		$.setSubscriptionLengths();
		$.setTrialPeriods();
	}

	// Update subscription ranges when subscription period or interval is changed
	$('#woocommerce-product-data').on('change','[name^="_subscription_period"], [name^="_subscription_period_interval"], [name^="variable_subscription_period"], [name^="variable_subscription_period_interval"]',function(){
		$.setSubscriptionLengths();
		$.setSyncOptions();
		$.setSalePeriod();
	});

	$('#woocommerce-product-data').on('propertychange keyup input paste change','[name^="_subscription_trial_length"], [name^="variable_subscription_trial_length"]',function(){
		$.setTrialPeriods();
	});

	$('body').bind('woocommerce-product-type-change',function(){
		$.showHideSubscriptionMeta();
		$.showHideVariableSubscriptionMeta();
	});

	$('input#_downloadable, input#_virtual').change(function(){
		$.showHideSubscriptionMeta();
		$.showHideVariableSubscriptionMeta();
	});
	*/

	// Make sure the "Used for variations" checkbox is visible when adding attributes to a variable subscription
	if ('true' == WCRegistrations.isWCPre23){
		$('button.add_attribute').on('click', function(){
			$.showHideRegistrationMeta();
		});
	} else {
		// WC 2.3 - run after the Ajax request has inserted variation HTML
		$('body').on('woocommerce_added_attribute', function(){
			$.showHideRegistrationMeta();
		});
	}

	/*
	if($.getParameterByName('select_subscription')=='true'){
		$('select#product-type option[value="'+WCSubscriptions.productType+'"]').attr('selected', 'selected');
		$('select#product-type').select().change();
	}

	// Before saving a subscription product, validate the trial period
	$('#post').submit(function(e){
		if ( WCSubscriptions.subscriptionLengths !== undefined ){
			var trialLength = $('#_subscription_trial_length').val(),
				selectedTrialPeriod = $('#_subscription_trial_period').val();

			if ( parseInt(trialLength) >= WCSubscriptions.subscriptionLengths[selectedTrialPeriod].length ) {
				alert(WCSubscriptions.trialTooLongMessages[selectedTrialPeriod]);
				$('#ajax-loading').hide();
				$('#publish').removeClass('button-primary-disabled');
				e.preventDefault();
			}
		}
	});

	// On "Manage Subscriptions" page, handle editing a date
	$('.date-picker-div').siblings('a.edit-timestamp').click(function(e) {
		var $pickerDiv = $(this).siblings('.date-picker-div'),
			$editDiv = $(this).parents('.edit-date-div');

		if ($pickerDiv.is(":hidden")) {
			$editDiv.css({visibility:'visible'});
			$pickerDiv.slideDown('fast');
			$(this).hide();
		} else {
			$editDiv.removeAttr( 'style' );
			$pickerDiv.slideUp('fast');
		}

		e.preventDefault();
	});

	$('.cancel-timestamp', '.date-picker-div').click(function(e) {
		var $pickerDiv = $(this).parents('.date-picker-div'),
			$editDiv = $(this).parents('.edit-date-div');

		$editDiv.removeAttr( 'style' );
		$pickerDiv.slideUp('fast');
		$pickerDiv.siblings('a.edit-timestamp').show();
		e.preventDefault();
	});

	$('.save-timestamp', '.date-picker-div').click(function (e) {
		var $pickerDiv = $(this).parents('.date-picker-div'),
			$editDiv = $pickerDiv.parents('.edit-date-div');
			$timeDiv = $editDiv.siblings('.next-payment-date');
			$subscriptionRow = $pickerDiv.parents('tr');

		$pickerDiv.slideUp('fast');
		$pickerDiv.parents('.row-actions').css({'background-image': 'url('+WCSubscriptions.ajaxLoaderImage+')'});

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'wcs_update_next_payment_date',
				wcs_subscription_key: $('.subscription_key',$subscriptionRow).val(),
				wcs_day: $('[name="edit-day"]', $pickerDiv).val(),
				wcs_month: $('[name="edit-month"]', $pickerDiv).val(),
				wcs_year: $('[name="edit-year"]', $pickerDiv).val(),
				wcs_nonce: WCSubscriptions.ajaxDateChangeNonce
			},
			success: function(response){
				response = $.parseJSON(response);
				if('error'==response.status){ // Output error message
					$editDiv.css({'background-image':''});
					$(response.message).hide().prependTo($timeDiv.parent()).slideDown('fast').fadeIn('fast');
					$pickerDiv.slideDown('fast');
					setTimeout(function() {
						$('.error',$timeDiv.parent()).slideUp();
					}, 4000);
				} else { // Update displayed payment date
					$editDiv.removeAttr( 'style' );
					$timeDiv.fadeOut('fast',function(){
						$timeDiv.html(response.dateToDisplay);
						$timeDiv.attr('title',response.timestamp);
						$timeDiv.fadeIn('fast');
						$pickerDiv.siblings('a.edit-timestamp').fadeIn('fast');
						$(response.message).hide().prependTo($timeDiv.parent()).slideDown('fast').fadeIn('fast');
						setTimeout(function() {
							$('.updated',$timeDiv.parent()).slideUp();
						}, 3000);
					});
				}
			}
		});

		e.preventDefault();
	});

	// Prefill recurring values for a subscription when a subscription product is added to an order
	$('#woocommerce-order-items button.add_shop_order_item').click(function(){

		var add_item_ids = $('select#add_item_id').val();

		if ( add_item_ids ) {
			var count = add_item_ids.length,
				size = $('table.woocommerce_order_items tbody tr.item').size();

			$.each( add_item_ids, function( index, value ) {

				var data = {
					action:      'woocommerce_subscriptions_prefill_order_item_meta',
					item_to_add: value,
					index:       size,
					security:    WCSubscriptions.EditOrderNonce
				};

				$.post( WCSubscriptions.ajaxUrl, data, function(response) {
					var $item_row;

					response = $.parseJSON(response);

					// Item is a subscription
					if ( response.html.length > 0 ) {
						$.hideAddItemButton();
						$('#recurring_order_totals').slideUp(1,function(){
							$(this).show(200,function(){
								$(this).slideDown();
							});
						});
					}

					var interval = setInterval(function() { // Can only insert the item row once it is available
						$item_row = $('#order_items_list .item[rel="'+response.item_index+'"]');

						if ( $item_row.length > 0 ) {

							$('tbody.meta_items',$item_row).append(response.html);

							if (! $.isEmptyObject(response.line_totals)) {
								$('input[name="line_subtotal\\['+response.item_index+'\\]"]').val(response.line_totals.line_subtotal);
								$('input[name="line_total\\['+response.item_index+'\\]"]').val(response.line_totals.line_total);
							}

							clearInterval(interval);
						}
					},200);
				});

				size++;
			});
		}

	});

	// Show recurring totals & hide add item button in WC 2.1
	$('#woocommerce-order-items button.add_order_item').click(function(){
		var interval = setInterval(function() { // Can only insert the item row once it is available
			var $item_row = $('#order_items_list .item:last');

			if( $item_row.length > 0 ) {
				if( $('input[value^="_recurring_"]',$item_row).size() > 0){
					$.hideAddItemButton();
					$('#recurring_order_totals').slideUp(1,function(){
						$(this).show(200,function(){
							$(this).slideDown();
						});
					});
				}

				clearInterval(interval);
			}
		},200);
	});

	// Calculate subscription line item taxes when line taxes are calculated
	$('button.calc_line_taxes').on('click', function(e){
		$.calculateRecurringTaxes();
		e.preventDefault();
	}).hover(function() {
		$('.meta_items [value="_recurring_line_subtotal_tax"]').parent().next().children('input[name^="meta_value"]').css('background-color', '#d8c8d2');
		$('.meta_items [value="_recurring_line_tax"]').parent().next().children('input[name^="meta_value"]').css('background-color', '#d8c8d2');
		$('#_order_recurring_shipping_total, #_order_recurring_shipping_tax_total, #_order_recurring_tax_total').css('background-color', '#d8c8d2');
	}, function() {
		$('.meta_items [value="_recurring_line_subtotal_tax"]').parent().next().children('input[name^="meta_value"]').css('background-color', '');
		$('.meta_items [value="_recurring_line_tax"]').parent().next().children('input[name^="meta_value"]').css('background-color', '');
		$('#_order_recurring_shipping_total, #_order_recurring_shipping_tax_total, #_order_recurring_tax_total').css('background-color', '');
	});

	// Calculate recurring order totals when order totals are calculated
	$('button.calc_totals').on('click', function(e){

		e.preventDefault();
	});

	// Hide add item button in WC 2.2
	$( 'body' ).on( 'wc_backbone_modal_response', function( e, target ) {
		if ( '#wc-modal-add-products' !== target ) {
			return;
		}
		var interval = setInterval(function() {
			var $item_row = $('#order_line_items .item:last');

			if($item_row.length > 0) {
				if($('input[value^="_recurring_"]',$item_row).size() > 0){
					$.hideAddItemButton();
					$('#woocommerce-order-totals').show();
					$('#recurring_order_totals').slideUp(1,function(){
						$(this).show(200,function(){
							$(this).slideDown();
						});
					});
				}

				clearInterval(interval);
			}
		},200);
	});

	// Calculate tax/totals on WC 2.2
	$( '#woocommerce-order-items' )
		.on( 'click', 'button.calculate-tax-action', function() {
			$.calculateRecurringTaxes();
		})
		.on( 'click', 'button.calculate-action', function() {
			$.calculateRecurringTotals();
		});

	// Move recurring order totals to end of order totals meta box
	if ('true' == WCSubscriptions.isWCPre22){
		$('#recurring_order_totals').remove().insertAfter($("#woocommerce-order-totals .totals_group:last"));
	}

	// If there are changes to any subscription related meta and the order's payment gateway doesn't support it, throw a confirmation.
	$('#post').on('submit', function(){
		if($.subscriptionMetaChanged() && $('[name="gateway_supports_subscription_changes"]').val() == 'false') {
			return confirm(WCSubscriptions.changeMetaWarning);
		}
	});

	// Notify store manager that deleting an order via the Orders screen also deletes subscriptions associated with the orders
	$('#posts-filter').submit(function(){
		if($('[name="post_type"]').val()=='shop_order'&&($('[name="action"]').val()=='trash'||$('[name="action2"]').val()=='trash')){
			var containsSubscription = false;
			$('[name="post[]"]:checked').each(function(){
				if(true===$('.contains_subscription',$('#post-'+$(this).val())).data('contains_subscription')){
					containsSubscription = true;
				}
				return (false === containsSubscription);
			});
			if(containsSubscription){
				return confirm(WCSubscriptions.bulkTrashWarning);
			}
		}
	});

	$('.order_actions .submitdelete').click(function(){
		if($('[name="contains_subscription"]').val()=='true'){
			return confirm(WCSubscriptions.bulkTrashWarning);
		}
	});

	$(window).load(function(){
		if($('[name="contains_subscription"]').length > 0 && $('[name="contains_subscription"]').val()=='true'){
			$.hideAddItemButton();
			// Show the Recurring Order Totals meta box in WC 2.2
			if ('false' == WCSubscriptions.isWCPre22) {
				$('#woocommerce-order-totals').show();
			}
		} else if ('false' == WCSubscriptions.isWCPre22) {
			$('#woocommerce-order-totals').hide();
		}
	});

	$('.remove_row').on('click',function(){
		var $itemRow = $(this).parents('tr.item');

		// If we're removing the last item, show the add item button
		if($('#order_items_list:visible').size() == 1){
			$.showAddItemButton();
		}

		// If we're removing a subscription, throw notice that subscription will need to be removed
		if($('[value^="_recurring_"]',$itemRow.html).size() > 0 || $('[value^="_subscription_"]',$itemRow.html).size() > 0){
			return confirm(WCSubscriptions.removeItemWarning);
		}
	});

	// Add a tax row
	$('a.add_recurring_tax_row').click(function(e){

		var data = {
			action: 	'woocommerce_subscriptions_add_line_tax',
			order_id: 	WCSubscriptions.postId,
			size:		$('#recurring_tax_rows .tax_row').size(),
			security: 	WCSubscriptions.EditOrderNonce
		};

		$('#recurring_tax_rows').closest('.totals_group').block({ message: null, overlayCSS: { background: '#fff url(' + WCSubscriptions.ajaxLoaderImage + ') no-repeat center', opacity: 0.6 } });

		$.ajax({
			url: WCSubscriptions.ajaxUrl,
			data: data,
			type: 'POST',
			success: function( response ) {
				$('#recurring_tax_rows').append( response ).closest('.totals_group').unblock();
			}
		});

		e.preventDefault();
	});

	// Delete a tax row
	$('#recurring_tax_rows').on('click','a.delete_recurring_tax_row',function(e){
		var $tax_row = $(this).closest('.tax_row'),
			tax_row_id = $tax_row.attr( 'data-order_item_id' );

		var data = {
			tax_row_id: tax_row_id,
			action: 	'woocommerce_subscriptions_remove_line_tax',
			security: 	WCSubscriptions.EditOrderNonce
		};

		$('#recurring_tax_rows').closest('.totals_group').block({ message: null, overlayCSS: { background: '#fff url(' + WCSubscriptions.ajaxLoaderImage + ') no-repeat center', opacity: 0.6 } });

		$.ajax({
			url: WCSubscriptions.ajaxUrl,
			data: data,
			type: 'POST',
			success: function( response ) {
				$tax_row.remove();
				$('#recurring_tax_rows').closest('.totals_group').unblock();
			}
		});

		e.preventDefault();
	});

	// Editing a variable product
	$('#variable_product_options').on('change','[name^="variable_regular_price"]',function(){
		var matches = $(this).attr('name').match(/\[(.*?)\]/);

		if (matches) {
			var loopIndex = matches[1];
			$('[name="variable_subscription_price['+loopIndex+']"]').val($(this).val());
		}
	});

	// Editing a variable product
	$('#variable_product_options').on('change','[name^="variable_subscription_price"]',function(){
		var matches = $(this).attr('name').match(/\[(.*?)\]/);

		if (matches) {
			var loopIndex = matches[1];
			$('[name="variable_regular_price['+loopIndex+']"]').val($(this).val());
		}
	});

	// Notify store manager that deleting an user via the Users screen also removed them from any subscriptions.
	$('.users-php .submitdelete').on('click',function(){
		return confirm(WCSubscriptions.deleteUserWarning);
	});
	*/

	/* Manage Subscriptions filters */
	// if( $('#subscriptions-filter select#dropdown_customers').length > 0 ) {
	// 	$('#subscriptions-filter select#dropdown_customers').css('width', '250px').ajaxChosen({
	// 		method: 		'GET',
	// 		url: 			WCSubscriptions.ajaxUrl,
	// 		dataType:      'json',
	// 		afterTypeDelay: 350,
	// 		minTermLength:  1,
	// 		data: {
	// 			action:   'woocommerce_json_search_customers',
	// 			security: WCSubscriptions.searchCustomersNonce,
	// 			default:  WCSubscriptions.searchCustomersLabel
	// 		}
	// 	}, function (data) {
	//
	// 		var terms = {};
	//
	// 		$.each(data, function (i, val) {
	// 			terms[i] = val;
	// 		});
	//
	// 		return terms;
	// 	});
	// }
	//
	// if( $('#subscriptions-filter select#dropdown_products_and_variations').length > 0 ) {
	// 	$('#subscriptions-filter select#dropdown_products_and_variations').ajaxChosen({
	// 		method: 	'GET',
	// 		url: 		WCSubscriptions.ajaxUrl,
	// 		dataType: 	'json',
	// 		afterTypeDelay: 350,
	// 		data: {
	// 			action:   'woocommerce_json_search_products_and_variations',
	// 			security: WCSubscriptions.searchProductsNonce
	// 		}
	// 	}, function (data) {
	//
	// 		var terms = {};
	//
	// 		$.each(data, function (i, val) {
	// 			terms[i] = val;
	// 		});
	//
	// 		return terms;
	// 	});
	// }

	// WC >= 2.1 variation bulk edit handling
	// $(document).on('variable_subscription_sign_up_fee variable_subscription_period_interval variable_subscription_period variable_subscription_trial_period variable_subscription_trial_length variable_subscription_length', 'select#field_to_edit', function(event) {
	// 	var value;
	//
	// 	switch( event.type ) {
	// 		case 'variable_subscription_period':
	// 		case 'variable_subscription_trial_period':
	// 			value = prompt( WCSubscriptions.bulkEditPeriodMessage );
	// 			break;
	// 		case 'variable_subscription_period_interval':
	// 			value = prompt( WCSubscriptions.bulkEditIntervalhMessage );
	// 			break;
	// 		case 'variable_subscription_trial_length':
	// 		case 'variable_subscription_length':
	// 			value = prompt( WCSubscriptions.bulkEditLengthMessage );
	// 			break;
	// 		default:
	// 			value = prompt( woocommerce_admin_meta_boxes_variations.i18n_enter_a_value );
	// 			break;
	// 	}
	//
	// 	if (value) {
	// 		$( ':input[name^="' + event.type + '["]').val( value ).change();
	// 	}
	// });

	// We're on the Subscriptions settings page
	// if($('#woocommerce_subscriptions_allow_switching').length > 0 ){
	// 	var allowSwitching = $('#woocommerce_subscriptions_allow_switching').val(),
	// 		$switchSettingsRows = $('#woocommerce_subscriptions_allow_switching').parents('tr').siblings('tr'),
	// 		$syncProratationRow = $('#woocommerce_subscriptions_prorate_synced_payments').parents('tr'),
	// 		$suspensionExtensionRow = $('#woocommerce_subscriptions_recoup_suspension').parents('tr');
	//
	// 	if('no'==allowSwitching){
	// 		$switchSettingsRows.hide();
	// 	}
	//
	// 	$('#woocommerce_subscriptions_allow_switching').on('change',function(){
	// 		if('no'==$(this).val()){
	// 			$switchSettingsRows.children('td, th').animate({paddingTop:0, paddingBottom:0}).wrapInner('<div />').children().slideUp(function(){
	// 				$(this).closest('tr').hide();
	// 				$(this).replaceWith($(this).html());
	// 			});
	// 		} else if('no'==allowSwitching) { // switching was previously disable, so settings will be hidden
	// 			$switchSettingsRows.fadeIn();
	// 			$switchSettingsRows.children('td, th').css({paddingTop:0, paddingBottom:0}).animate({paddingTop:'15px', paddingBottom:'15px'}).wrapInner('<div style="display: none;"/>').children().slideDown(function(){
	// 				$switchSettingsRows.children('td, th').removeAttr('style');
	// 				$(this).replaceWith($(this).html());
	// 			});
	// 		}
	// 		allowSwitching = $(this).val();
	// 	});
	//
	//
	// 	// Show/hide suspension extension setting
	// 	if ($('#woocommerce_subscriptions_max_customer_suspensions').val() > 0) {
	// 		$suspensionExtensionRow.show();
	// 	} else {
	// 		$suspensionExtensionRow.hide();
	// 	}
	//
	// 	$('#woocommerce_subscriptions_max_customer_suspensions').on('change', function(){
	// 		if ($(this).val() > 0) {
	// 			$suspensionExtensionRow.show();
	// 		} else {
	// 			$suspensionExtensionRow.hide();
	// 		}
	// 	});
	//
	// 	// Show/hide sync proration setting
	// 	if ($('#woocommerce_subscriptions_sync_payments').is(':checked')) {
	// 		$syncProratationRow.show();
	// 	} else {
	// 		$syncProratationRow.hide();
	// 	}
	//
	// 	$('#woocommerce_subscriptions_sync_payments').on('change', function(){
	// 		if ($(this).is(':checked')) {
	// 			$syncProratationRow.show();
	// 		} else {
	// 			$syncProratationRow.hide();
	// 		}
	// 	});
	// }

	// Don't display the variation notice for variable subscription products
	// $( 'body' ).on( 'woocommerce-display-product-type-alert', function(e, select_val) {
	// 	if (select_val=='variable-subscription') {
	// 		return false;
	// 	}
	// });
});
