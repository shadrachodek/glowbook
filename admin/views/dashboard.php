<?php
/**
 * Admin dashboard view.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$first_name   = $current_user->first_name ?: $current_user->display_name;
?>
<div class="wrap sodek-gb-admin-wrap">
	<div class="sodek-gb-admin-heading">
		<div>
			<span class="sodek-gb-admin-eyebrow"><?php esc_html_e( 'Booking overview', 'glowbook' ); ?></span>
			<h1><?php esc_html_e( 'Good day', 'glowbook' ); ?>, <?php echo esc_html( $first_name ); ?></h1>
			<p><?php esc_html_e( 'Here\'s what is happening with your appointments.', 'glowbook' ); ?></p>
		</div>
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sodek_gb_booking' ) ); ?>" class="button button-primary sodek-gb-dashboard-primary-action"><?php esc_html_e( 'Add booking', 'glowbook' ); ?></a>
	</div>

	<div class="sodek-gb-dashboard-stats">
		<div class="sodek-gb-stat-box">
			<span class="sodek-gb-stat-number"><?php echo esc_html( $stats['today_count'] ); ?></span>
			<span class="sodek-gb-stat-label"><?php esc_html_e( 'Appointments today', 'glowbook' ); ?></span>
		</div>
		<div class="sodek-gb-stat-box">
			<span class="sodek-gb-stat-number"><?php echo esc_html( $stats['week_count'] ); ?></span>
			<span class="sodek-gb-stat-label"><?php esc_html_e( 'This week', 'glowbook' ); ?></span>
		</div>
		<div class="sodek-gb-stat-box">
			<span class="sodek-gb-stat-number"><?php echo esc_html( $stats['pending_count'] ); ?></span>
			<span class="sodek-gb-stat-label"><?php esc_html_e( 'Awaiting confirmation', 'glowbook' ); ?></span>
		</div>
		<div class="sodek-gb-stat-box">
			<span class="sodek-gb-stat-number"><?php echo wp_kses_post( wc_price( $stats['monthly_revenue'] ) ); ?></span>
			<span class="sodek-gb-stat-label"><?php esc_html_e( 'Deposits this month', 'glowbook' ); ?></span>
		</div>
	</div>

	<div class="sodek-gb-dashboard-columns">
		<div class="sodek-gb-dashboard-column">
			<div class="sodek-gb-dashboard-panel-head">
				<div><span><?php esc_html_e( 'Today', 'glowbook' ); ?></span><h2><?php esc_html_e( 'Today\'s schedule', 'glowbook' ); ?></h2></div>
				<strong><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $today ) ) ); ?></strong>
			</div>

			<?php if ( empty( $today_bookings ) ) : ?>
				<div class="sodek-gb-no-bookings"><strong><?php esc_html_e( 'Your day is open', 'glowbook' ); ?></strong><span><?php esc_html_e( 'No appointments are scheduled for today.', 'glowbook' ); ?></span></div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Time', 'glowbook' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'glowbook' ); ?></th>
							<th><?php esc_html_e( 'Service', 'glowbook' ); ?></th>
							<th><?php esc_html_e( 'Status', 'glowbook' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'glowbook' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $today_bookings as $booking ) : ?>
						<tr>
							<td data-colname="<?php esc_attr_e( 'Time', 'glowbook' ); ?>">
								<strong><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?></strong>
								<br><small><?php echo esc_html( $booking['service']['duration'] ); ?> min</small>
							</td>
							<td data-colname="<?php esc_attr_e( 'Customer', 'glowbook' ); ?>">
								<?php echo esc_html( $booking['customer_name'] ); ?>
								<br><small><?php echo esc_html( $booking['customer_phone'] ); ?></small>
							</td>
							<td data-colname="<?php esc_attr_e( 'Service', 'glowbook' ); ?>"><?php echo esc_html( $booking['service']['title'] ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Status', 'glowbook' ); ?>">
								<span class="sodek-gb-status sodek-gb-status-<?php echo esc_attr( $booking['status'] ); ?>">
									<?php echo esc_html( ucfirst( $booking['status'] ) ); ?>
								</span>
							</td>
							<td data-colname="<?php esc_attr_e( 'Actions', 'glowbook' ); ?>">
								<a href="<?php echo esc_url( get_edit_post_link( $booking['id'] ) ); ?>" class="button button-small">
									<?php esc_html_e( 'View', 'glowbook' ); ?>
								</a>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<div class="sodek-gb-dashboard-column">
			<div class="sodek-gb-dashboard-panel-head">
				<div><span><?php esc_html_e( 'Coming up', 'glowbook' ); ?></span><h2><?php esc_html_e( 'Upcoming appointments', 'glowbook' ); ?></h2></div>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=sodek_gb_booking' ) ); ?>"><?php esc_html_e( 'View all', 'glowbook' ); ?></a>
			</div>

			<?php if ( empty( $upcoming ) ) : ?>
				<p class="sodek-gb-no-bookings"><?php esc_html_e( 'No upcoming appointments.', 'glowbook' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Date/Time', 'glowbook' ); ?></th>
							<th><?php esc_html_e( 'Customer', 'glowbook' ); ?></th>
							<th><?php esc_html_e( 'Service', 'glowbook' ); ?></th>
							<th><?php esc_html_e( 'Status', 'glowbook' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $upcoming as $booking ) : ?>
						<tr>
							<td data-colname="<?php esc_attr_e( 'Date/Time', 'glowbook' ); ?>">
								<strong><?php echo esc_html( date_i18n( 'M j', strtotime( $booking['booking_date'] ) ) ); ?></strong>
								<br><small><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?></small>
							</td>
							<td data-colname="<?php esc_attr_e( 'Customer', 'glowbook' ); ?>"><?php echo esc_html( $booking['customer_name'] ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Service', 'glowbook' ); ?>"><?php echo esc_html( $booking['service']['title'] ); ?></td>
							<td data-colname="<?php esc_attr_e( 'Status', 'glowbook' ); ?>">
								<span class="sodek-gb-status sodek-gb-status-<?php echo esc_attr( $booking['status'] ); ?>">
									<?php echo esc_html( ucfirst( $booking['status'] ) ); ?>
								</span>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

		</div>
	</div>
</div>
