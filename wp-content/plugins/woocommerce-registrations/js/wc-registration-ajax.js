// Save attributes and update variations
$('.save_attributes').on('click', function(){

    $('.product_attributes').block({ message: null, overlayCSS: { background: '#fff', opacity: 0.6 } });

    var data = {
        post_id: 		woocommerce_admin_meta_boxes.post_id,
        data:			$('.product_attributes').find('input, select, textarea').serialize(),
        action: 		'woocommerce_save_attributes',
        security: 		woocommerce_admin_meta_boxes.save_attributes_nonce
    };

    $.post( woocommerce_admin_meta_boxes.ajax_url, data, function( response ) {

        var this_page = window.location.toString();

        this_page = this_page.replace( 'post-new.php?', 'post.php?post=' + woocommerce_admin_meta_boxes.post_id + '&action=edit&' );

        // Load variations panel
        $('#variable_product_options').block({ message: null, overlayCSS: { background: '#fff', opacity: 0.6 } });
        $('#variable_product_options').load( this_page + ' #variable_product_options_inner', function() {
            $('#variable_product_options').unblock();
        } );

        $('.product_attributes').unblock();

    });

});
