<div class="wrap">
	<h2 class="nav-tab-wrapper">
		<a href="<?php echo add_query_arg( array( 'post_type' => 'wc_booking', 'page' => 'wc_bookings_settings', 'tab' => 'availability' ), admin_url( 'edit.php' ) ); ?>" class="nav-tab<?php echo ( 'availability' == $current_tab ) ? ' nav-tab-active' : ''; ?>"><?php _e( 'Availability', 'woocommerce-bookings' ); ?></a>
	</h2>

	<div id="content">

		<?php
			include( 'html-global-availability-settings.php' );
		?>

	</div>

</div>
