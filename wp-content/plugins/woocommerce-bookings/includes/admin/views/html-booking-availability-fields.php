<?php
	$intervals = array();

	$intervals['months'] = array(
		'1'  => __( 'January', 'woocommerce-bookings' ),
		'2'  => __( 'Febuary', 'woocommerce-bookings' ),
		'3'  => __( 'March', 'woocommerce-bookings' ),
		'4'  => __( 'April', 'woocommerce-bookings' ),
		'5'  => __( 'May', 'woocommerce-bookings' ),
		'6'  => __( 'June', 'woocommerce-bookings' ),
		'7'  => __( 'July', 'woocommerce-bookings' ),
		'8'  => __( 'August', 'woocommerce-bookings' ),
		'9'  => __( 'September', 'woocommerce-bookings' ),
		'10' => __( 'October', 'woocommerce-bookings' ),
		'11' => __( 'November', 'woocommerce-bookings' ),
		'12' => __( 'December', 'woocommerce-bookings' )
	);

	$intervals['days'] = array(
		'1' => __( 'Monday', 'woocommerce-bookings' ),
		'2' => __( 'Tuesday', 'woocommerce-bookings' ),
		'3' => __( 'Wednesday', 'woocommerce-bookings' ),
		'4' => __( 'Thursday', 'woocommerce-bookings' ),
		'5' => __( 'Friday', 'woocommerce-bookings' ),
		'6' => __( 'Saturday', 'woocommerce-bookings' ),
		'7' => __( 'Sunday', 'woocommerce-bookings' )
	);

	for ( $i = 1; $i <= 52; $i ++ ) {
		$intervals['weeks'][ $i ] = sprintf( __( 'Week %s' ), $i );
	}

	if ( ! isset( $availability['type'] ) ) {
		$availability['type'] = 'custom';
	}
?>
<tr>
	<td class="sort">&nbsp;</td>
	<td>
		<div class="select wc_booking_availability_type">
			<select name="wc_booking_availability_type[]">
				<option value="custom" <?php selected( $availability['type'], 'custom' ); ?>><?php _e( 'Custom date range', 'woocommerce-bookings' ); ?></option>
				<option value="months" <?php selected( $availability['type'], 'months' ); ?>><?php _e( 'Range of months', 'woocommerce-bookings' ); ?></option>
				<option value="weeks" <?php selected( $availability['type'], 'weeks' ); ?>><?php _e( 'Range of weeks', 'woocommerce-bookings' ); ?></option>
				<option value="days" <?php selected( $availability['type'], 'days' ); ?>><?php _e( 'Range of days', 'woocommerce-bookings' ); ?></option>
				<optgroup label="<?php _e( 'Time Ranges', 'woocommerce-bookings' ); ?>">
					<option value="time" <?php selected( $availability['type'], 'time' ); ?>><?php _e( 'Time Range (all week)', 'woocommerce-bookings' ); ?></option>
					<?php foreach ( $intervals['days'] as $key => $label ) : ?>
						<option value="time:<?php echo $key; ?>" <?php selected( $availability['type'], 'time:' . $key ) ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</optgroup>
			</select>
		</div>
	</td>
	<td>
		<div class="select from_day_of_week">
			<select name="wc_booking_availability_from_day_of_week[]">
				<?php foreach ( $intervals['days'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['from'] ) && $availability['from'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select from_month">
			<select name="wc_booking_availability_from_month[]">
				<?php foreach ( $intervals['months'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['from'] ) && $availability['from'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select from_week">
			<select name="wc_booking_availability_from_week[]">
				<?php foreach ( $intervals['weeks'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['from'] ) && $availability['from'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="from_date">
			<input type="text" class="date-picker" name="wc_booking_availability_from_date[]" value="<?php if ( $availability['type'] == 'custom' && ! empty( $availability['from'] ) ) echo $availability['from'] ?>" />
		</div>
		<div class="from_time">
			<input type="time" class="time-picker" name="wc_booking_availability_from_time[]" value="<?php if ( strrpos( $availability['type'], 'time' ) === 0 && ! empty( $availability['from'] ) ) echo $availability['from'] ?>" placeholder="HH:MM" />
		</div>
	</td>
	<td>
		<div class="select to_day_of_week">
			<select name="wc_booking_availability_to_day_of_week[]">
				<?php foreach ( $intervals['days'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['to'] ) && $availability['to'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select to_month">
			<select name="wc_booking_availability_to_month[]">
				<?php foreach ( $intervals['months'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['to'] ) && $availability['to'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="select to_week">
			<select name="wc_booking_availability_to_week[]">
				<?php foreach ( $intervals['weeks'] as $key => $label ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( isset( $availability['to'] ) && $availability['to'] == $key, true ) ?>><?php echo $label; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="to_date">
			<input type="text" class="date-picker" name="wc_booking_availability_to_date[]" value="<?php if ( $availability['type'] == 'custom' && ! empty( $availability['to'] ) ) echo $availability['to']; ?>" />
		</div>

		<div class="to_time">
			<input type="time" class="time-picker" name="wc_booking_availability_to_time[]" value="<?php if ( strrpos( $availability['type'], 'time' ) === 0 && ! empty( $availability['to'] ) ) echo $availability['to']; ?>" placeholder="HH:MM" />
		</div>
	</td>
	<td>
		<div class="select">
			<select name="wc_booking_availability_bookable[]">
				<option value="no" <?php selected( isset( $availability['bookable'] ) && $availability['bookable'] == 'no', true ) ?>><?php _e( 'No', 'woocommerce-bookings' ) ;?></option>
				<option value="yes" <?php selected( isset( $availability['bookable'] ) && $availability['bookable'] == 'yes', true ) ?>><?php _e( 'Yes', 'woocommerce-bookings' ) ;?></option>
			</select>
		</div>
	</td>
	<td class="remove">&nbsp;</td>
</tr>