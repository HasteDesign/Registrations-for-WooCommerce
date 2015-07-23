<?php
wp_enqueue_script( 'wc-bookings-month-picker' );
extract( $field );
?>
<div class="form-field form-field-wide">
	<label for="<?php echo $name; ?>"><?php echo $label; ?>:</label>
	<ul class="block-picker">
		<?php
			foreach ( $blocks as $block ) {
				echo '<li data-block="' . esc_attr( date( 'Ym', $block ) ) . '"><a href="#" data-value="' . date( 'Y-m', $block ) . '">' . date_i18n( 'M y', $block ) . '</a></li>';
			}
		?>
	</ul>
	<input type="hidden" name="<?php echo $name; ?>_yearmonth" id="<?php echo $name; ?>" />
</div>