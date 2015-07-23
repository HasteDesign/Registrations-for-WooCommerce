jQuery(document).ready(function($) {

	$('.block-picker').on('click', 'a', function(){
		var value  = $(this).data('value');
		var target = $(this).closest('div').find('input');

		target.val( value ).change();
		$(this).closest('ul').find('a').removeClass('selected');
		$(this).addClass('selected');

		return false;
	});

	max_width = 0;

	$('.block-picker a').each(function() {
		width = $(this).width();
		if ( width > max_width)
			max_width = width
	});

	$('.block-picker a').width( max_width );

});