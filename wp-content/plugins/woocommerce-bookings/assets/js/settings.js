jQuery( document ).ready( function ( $ ) {
	// Open/close
	$( '.wc-metaboxes-wrapper' ).on( 'click', '.wc-metabox h3', function ( event ) {
		// If the user clicks on some form input inside the h3, like a select list (for variations), the box should not be toggled
		if ( $( event.target ).filter( ':input, option' ).length ) {
			return;
		}

		$( this ).next( '.wc-metabox-content' ).toggle();
	})
	.on( 'click', '.expand_all', function () {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > table' ).show();
		return false;
	})
	.on( 'click', '.close_all', function () {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > table' ).hide();
		return false;
	});

	$( '.wc-metabox.closed' ).each( function () {
		$( this ).find( '.wc-metabox-content' ).hide();
	});

	$( '.wc-metabox > h3' ).on( 'click', function () {
		$( this ).parent( '.wc-metabox' ).toggleClass( 'closed' ).toggleClass( 'open' );
	});
});
