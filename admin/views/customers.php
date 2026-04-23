<?php
/**
 * Customers admin page.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

$base_url     = admin_url( 'admin.php?page=sodek-gb-customers' );
$search_query = isset( $search ) ? $search : '';

$format_money = static function ( $amount ) {
	if ( function_exists( 'wc_price' ) ) {
		return wp_strip_all_tags( wc_price( (float) $amount ) );
	}

	return '$' . number_format_i18n( (float) $amount, 2 );
};

$format_date = static function ( $date_string ) {
	if ( empty( $date_string ) ) {
		return __( 'None yet', 'glowbook' );
	}

	return date_i18n( get_option( 'date_format' ), strtotime( $date_string ) );
};

$format_booking_when = static function ( $booking ) {
	if ( empty( $booking['booking_date'] ) ) {
		return '—';
	}

	$label = date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) );

	if ( ! empty( $booking['start_time'] ) ) {
		$label .= ' at ' . date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) );
	}

	return $label;
};

$profile_context = null;

if ( ! empty( $selected_customer ) ) {
	$profile             = $selected_customer['customer'];
	$cards               = $selected_customer['cards'];
	$recent              = $selected_customer['recent_bookings'];
	$upcoming            = $selected_customer['upcoming_bookings'];
	$past                = $selected_customer['past_bookings'];
	$wp_user             = $selected_customer['wp_user'];
	$profile_name        = trim( ( $profile['first_name'] ?? '' ) . ' ' . ( $profile['last_name'] ?? '' ) ) ?: __( 'Guest Customer', 'glowbook' );
	$portal_ready        = ! empty( $profile['email_opt_in'] );
	$sms_ready           = ! empty( $profile['sms_opt_in'] );
	$has_wp_link         = ! empty( $wp_user );
	$latest_booking_link = ! empty( $profile['latest_booking_id'] ) ? admin_url( 'post.php?post=' . $profile['latest_booking_id'] . '&action=edit' ) : '';
	$back_url            = add_query_arg(
		array_filter(
			array(
				'page' => 'sodek-gb-customers',
				's'    => $search_query,
				'paged' => $current_page > 1 ? $current_page : null,
			)
		),
		admin_url( 'admin.php' )
	);

	$profile_context = compact(
		'profile',
		'cards',
		'recent',
		'upcoming',
		'past',
		'wp_user',
		'profile_name',
		'portal_ready',
		'sms_ready',
		'has_wp_link',
		'latest_booking_link',
		'back_url'
	);
}
?>
<div class="wrap sodek-gb-admin-wrap sodek-gb-customers-page">
	<?php if ( $profile_context ) : ?>
		<?php extract( $profile_context, EXTR_SKIP ); ?>
		<?php
		$account_state       = Sodek_GB_Customer::get_account_state( $profile );
		$account_state_label = Sodek_GB_Customer::get_account_state_label( $profile );
		$account_state_copy  = Sodek_GB_Customer::get_account_state_description( $profile );
		?>
		<div class="sodek-gb-customer-hero sodek-gb-customer-hero-profile">
			<div class="sodek-gb-customer-hero-copy">
				<a class="sodek-gb-back-link" href="<?php echo esc_url( $back_url ); ?>">&larr; <?php esc_html_e( 'Back to customer directory', 'glowbook' ); ?></a>
				<span class="sodek-gb-customer-kicker"><?php esc_html_e( 'Customer Profile', 'glowbook' ); ?></span>
				<h1><?php echo esc_html( $profile_name ); ?></h1>
				<p><?php esc_html_e( 'Full customer context for bookings, payment profile, portal readiness, and WordPress account linkage.', 'glowbook' ); ?></p>
				<div class="sodek-gb-customer-flag-row">
					<span class="sodek-gb-customer-flag is-account-<?php echo esc_attr( $account_state ); ?>">
						<?php echo esc_html( $account_state_label ); ?>
					</span>
					<span class="sodek-gb-customer-flag <?php echo $portal_ready ? 'is-positive' : ''; ?>">
						<?php echo $portal_ready ? esc_html__( 'Portal ready', 'glowbook' ) : esc_html__( 'Portal inactive', 'glowbook' ); ?>
					</span>
					<span class="sodek-gb-customer-flag <?php echo $sms_ready ? 'is-positive' : ''; ?>">
						<?php echo $sms_ready ? esc_html__( 'SMS enabled', 'glowbook' ) : esc_html__( 'SMS off', 'glowbook' ); ?>
					</span>
					<span class="sodek-gb-customer-flag <?php echo $has_wp_link ? 'is-positive' : ''; ?>">
						<?php echo $has_wp_link ? esc_html__( 'Linked to WordPress', 'glowbook' ) : esc_html__( 'No WP link yet', 'glowbook' ); ?>
					</span>
				</div>
			</div>
			<div class="sodek-gb-customer-hero-note sodek-gb-customer-hero-note-profile">
				<div class="sodek-gb-customer-avatar sodek-gb-customer-avatar-large">
					<?php echo esc_html( strtoupper( substr( $profile_name, 0, 1 ) ) ); ?>
				</div>
				<div>
					<strong><?php printf( esc_html__( 'Customer #%d', 'glowbook' ), (int) $profile['id'] ); ?></strong>
					<span><?php echo esc_html( $profile['email'] ?: ( $profile['phone'] ?: __( 'No primary contact saved yet.', 'glowbook' ) ) ); ?></span>
				</div>
			</div>
		</div>

		<div class="sodek-gb-customer-profile-summary">
				<div class="sodek-gb-customer-summary-card">
					<span><?php esc_html_e( 'Account type', 'glowbook' ); ?></span>
					<strong><?php echo esc_html( $account_state_label ); ?></strong>
				</div>
				<div class="sodek-gb-customer-summary-card">
					<span><?php esc_html_e( 'Joined', 'glowbook' ); ?></span>
					<strong><?php echo esc_html( ! empty( $profile['created_at'] ) ? $format_date( $profile['created_at'] ) : '—' ); ?></strong>
				</div>
			<div class="sodek-gb-customer-summary-card">
				<span><?php esc_html_e( 'Total bookings', 'glowbook' ); ?></span>
				<strong><?php echo esc_html( number_format_i18n( (int) ( $profile['total_bookings'] ?? 0 ) ) ); ?></strong>
			</div>
			<div class="sodek-gb-customer-summary-card">
				<span><?php esc_html_e( 'Total spent', 'glowbook' ); ?></span>
				<strong><?php echo esc_html( $format_money( $profile['total_spent'] ?? 0 ) ); ?></strong>
			</div>
			<div class="sodek-gb-customer-summary-card">
				<span><?php esc_html_e( 'Saved cards', 'glowbook' ); ?></span>
				<strong><?php echo esc_html( number_format_i18n( is_array( $cards ) ? count( $cards ) : 0 ) ); ?></strong>
			</div>
		</div>

		<div class="sodek-gb-customer-profile-grid">
			<div class="sodek-gb-customer-profile-main-column">
				<div class="sodek-gb-customer-panel">
					<div class="sodek-gb-panel-heading">
						<h3><?php esc_html_e( 'Recent Bookings', 'glowbook' ); ?></h3>
						<p><?php esc_html_e( 'The latest appointment activity for this customer, including service, status, and total value.', 'glowbook' ); ?></p>
					</div>
					<?php if ( empty( $recent ) ) : ?>
						<p><em><?php esc_html_e( 'No bookings found for this customer yet.', 'glowbook' ); ?></em></p>
					<?php else : ?>
						<div class="sodek-gb-customer-booking-table-wrap">
							<table class="widefat striped sodek-gb-mini-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Date', 'glowbook' ); ?></th>
										<th><?php esc_html_e( 'Service', 'glowbook' ); ?></th>
										<th><?php esc_html_e( 'Status', 'glowbook' ); ?></th>
										<th><?php esc_html_e( 'Total', 'glowbook' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $recent as $booking ) : ?>
										<tr>
											<td>
												<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $booking['id'] . '&action=edit' ) ); ?>">
													<?php echo esc_html( $format_date( $booking['booking_date'] ) ); ?>
												</a>
											</td>
											<td><?php echo esc_html( $booking['service']['title'] ?? '—' ); ?></td>
											<td><span class="sodek-gb-status sodek-gb-status-<?php echo esc_attr( sanitize_html_class( strtolower( $booking['status'] ) ) ); ?>"><?php echo esc_html( ucfirst( $booking['status'] ) ); ?></span></td>
											<td><?php echo esc_html( $format_money( $booking['total_price'] ?? 0 ) ); ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endif; ?>
				</div>

				<div class="sodek-gb-customer-split">
					<div class="sodek-gb-customer-panel">
						<div class="sodek-gb-panel-heading">
							<h3><?php esc_html_e( 'Upcoming', 'glowbook' ); ?></h3>
							<p><?php esc_html_e( 'Appointments that still need attention, preparation, or follow-up.', 'glowbook' ); ?></p>
						</div>
						<?php if ( empty( $upcoming ) ) : ?>
							<p><em><?php esc_html_e( 'No upcoming appointments.', 'glowbook' ); ?></em></p>
						<?php else : ?>
							<ul class="sodek-gb-booking-list">
								<?php foreach ( $upcoming as $booking ) : ?>
									<li>
										<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $booking['id'] . '&action=edit' ) ); ?>"><?php echo esc_html( $booking['service']['title'] ?? __( 'Booking', 'glowbook' ) ); ?></a>
										<span><?php echo esc_html( $format_booking_when( $booking ) ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
					<div class="sodek-gb-customer-panel">
						<div class="sodek-gb-panel-heading">
							<h3><?php esc_html_e( 'Past', 'glowbook' ); ?></h3>
							<p><?php esc_html_e( 'Completed or older appointments that help tell this customer story.', 'glowbook' ); ?></p>
						</div>
						<?php if ( empty( $past ) ) : ?>
							<p><em><?php esc_html_e( 'No past appointments yet.', 'glowbook' ); ?></em></p>
						<?php else : ?>
							<ul class="sodek-gb-booking-list">
								<?php foreach ( $past as $booking ) : ?>
									<li>
										<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $booking['id'] . '&action=edit' ) ); ?>"><?php echo esc_html( $booking['service']['title'] ?? __( 'Booking', 'glowbook' ) ); ?></a>
										<span><?php echo esc_html( $format_date( $booking['booking_date'] ) . ' / ' . ucfirst( $booking['status'] ) ); ?></span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<aside class="sodek-gb-customer-profile-side-column">
				<div class="sodek-gb-customer-panel">
					<div class="sodek-gb-panel-heading">
						<h3><?php esc_html_e( 'Account Strategy', 'glowbook' ); ?></h3>
						<p><?php echo esc_html( $account_state_copy ); ?></p>
					</div>
					<div class="sodek-gb-customer-account-note">
						<strong><?php echo esc_html( $account_state_label ); ?></strong>
						<span><?php esc_html_e( 'GlowBook supports guest booking, portal access, and linked WordPress accounts as one hybrid customer model.', 'glowbook' ); ?></span>
					</div>
				</div>

				<div class="sodek-gb-customer-panel">
					<div class="sodek-gb-panel-heading">
						<h3><?php esc_html_e( 'Profile Details', 'glowbook' ); ?></h3>
						<p><?php esc_html_e( 'Contact and preference data stored against this customer record.', 'glowbook' ); ?></p>
					</div>
					<dl class="sodek-gb-customer-meta">
						<div><dt><?php esc_html_e( 'Email', 'glowbook' ); ?></dt><dd><?php echo esc_html( $profile['email'] ?: '—' ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Phone', 'glowbook' ); ?></dt><dd><?php echo esc_html( $profile['phone'] ?: '—' ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Hair type', 'glowbook' ); ?></dt><dd><?php echo esc_html( $profile['hair_type'] ?: '—' ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Hair length', 'glowbook' ); ?></dt><dd><?php echo esc_html( $profile['hair_length'] ?: '—' ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Portal email opt-in', 'glowbook' ); ?></dt><dd><?php echo $portal_ready ? esc_html__( 'Yes', 'glowbook' ) : esc_html__( 'No', 'glowbook' ); ?></dd></div>
						<div><dt><?php esc_html_e( 'Portal SMS opt-in', 'glowbook' ); ?></dt><dd><?php echo $sms_ready ? esc_html__( 'Yes', 'glowbook' ) : esc_html__( 'No', 'glowbook' ); ?></dd></div>
					</dl>
				</div>

				<div class="sodek-gb-customer-panel">
					<div class="sodek-gb-panel-heading">
						<h3><?php esc_html_e( 'WordPress Link', 'glowbook' ); ?></h3>
						<p><?php esc_html_e( 'Use the linked account for WordPress authentication and portal continuity.', 'glowbook' ); ?></p>
					</div>
					<?php if ( $wp_user ) : ?>
						<p><strong><?php echo esc_html( $wp_user->display_name ); ?></strong></p>
						<p><?php echo esc_html( $wp_user->user_email ); ?></p>
						<p><a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $wp_user->ID ) ); ?>" class="button"><?php esc_html_e( 'View WP User', 'glowbook' ); ?></a></p>
					<?php else : ?>
						<p><?php esc_html_e( 'This customer is not linked to a WordPress user yet.', 'glowbook' ); ?></p>
						<?php if ( ! empty( $profile['email'] ) ) : ?>
							<p><a href="<?php echo esc_url( admin_url( 'user-new.php?email=' . rawurlencode( $profile['email'] ) ) ); ?>" class="button button-primary"><?php esc_html_e( 'Create WP User', 'glowbook' ); ?></a></p>
						<?php endif; ?>
					<?php endif; ?>
				</div>

				<div class="sodek-gb-customer-panel">
					<div class="sodek-gb-panel-heading">
						<h3><?php esc_html_e( 'Saved Cards', 'glowbook' ); ?></h3>
						<p><?php esc_html_e( 'Only safe summaries are stored here. Full card details remain with Square.', 'glowbook' ); ?></p>
					</div>
					<?php if ( empty( $cards ) ) : ?>
						<p><em><?php esc_html_e( 'No saved cards.', 'glowbook' ); ?></em></p>
					<?php else : ?>
						<ul class="sodek-gb-card-list">
							<?php foreach ( $cards as $card ) : ?>
								<li>
									<div>
										<strong><?php echo esc_html( strtoupper( $card['card_brand'] ) ); ?></strong>
										<span><?php printf( esc_html__( 'ending in %s', 'glowbook' ), esc_html( $card['card_last4'] ) ); ?></span>
									</div>
									<div class="sodek-gb-card-list-meta">
										<span><?php echo esc_html( $card['card_exp_month'] . '/' . $card['card_exp_year'] ); ?></span>
										<?php if ( ! empty( $card['is_default'] ) ) : ?>
											<span class="sodek-gb-status sodek-gb-status-success"><?php esc_html_e( 'Default', 'glowbook' ); ?></span>
										<?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<?php if ( $latest_booking_link ) : ?>
					<div class="sodek-gb-customer-panel">
						<div class="sodek-gb-panel-heading">
							<h3><?php esc_html_e( 'Quick Action', 'glowbook' ); ?></h3>
							<p><?php esc_html_e( 'Jump directly into the most recent appointment record for edits or support.', 'glowbook' ); ?></p>
						</div>
						<p><a href="<?php echo esc_url( $latest_booking_link ); ?>" class="button"><?php esc_html_e( 'Open latest booking', 'glowbook' ); ?></a></p>
					</div>
				<?php endif; ?>
			</aside>
		</div>
	<?php else : ?>
		<div class="sodek-gb-customer-hero">
			<div class="sodek-gb-customer-hero-copy">
				<span class="sodek-gb-customer-kicker"><?php esc_html_e( 'GlowBook CRM', 'glowbook' ); ?></span>
				<h1><?php esc_html_e( 'Customers', 'glowbook' ); ?></h1>
				<p>
					<?php esc_html_e( 'Browse customer profiles, review booking history, inspect portal readiness, and understand how each client connects to WordPress and Square.', 'glowbook' ); ?>
				</p>
			</div>
			<div class="sodek-gb-customer-hero-note">
				<strong><?php echo esc_html( number_format_i18n( $total ) ); ?></strong>
				<span><?php esc_html_e( 'profiles available in your customer directory right now.', 'glowbook' ); ?></span>
			</div>
		</div>

		<div class="sodek-gb-customer-stats-grid">
			<div class="sodek-gb-customer-stat-card">
				<span class="sodek-gb-customer-stat-label"><?php esc_html_e( 'Total Customers', 'glowbook' ); ?></span>
				<strong class="sodek-gb-customer-stat-number"><?php echo esc_html( number_format_i18n( $customer_stats['total_customers'] ?? 0 ) ); ?></strong>
				<p><?php esc_html_e( 'Everyone currently stored in the GlowBook customer table.', 'glowbook' ); ?></p>
			</div>
			<div class="sodek-gb-customer-stat-card">
				<span class="sodek-gb-customer-stat-label"><?php esc_html_e( 'Portal Ready', 'glowbook' ); ?></span>
				<strong class="sodek-gb-customer-stat-number"><?php echo esc_html( number_format_i18n( $customer_stats['portal_enabled'] ?? 0 ) ); ?></strong>
				<p><?php esc_html_e( 'Customers already opted in for portal email access.', 'glowbook' ); ?></p>
			</div>
			<div class="sodek-gb-customer-stat-card">
				<span class="sodek-gb-customer-stat-label"><?php esc_html_e( 'Linked WP Users', 'glowbook' ); ?></span>
				<strong class="sodek-gb-customer-stat-number"><?php echo esc_html( number_format_i18n( $customer_stats['linked_wp_users'] ?? 0 ) ); ?></strong>
				<p><?php esc_html_e( 'Profiles already connected to a WordPress account.', 'glowbook' ); ?></p>
			</div>
			<div class="sodek-gb-customer-stat-card">
				<span class="sodek-gb-customer-stat-label"><?php esc_html_e( 'SMS Opt-in', 'glowbook' ); ?></span>
				<strong class="sodek-gb-customer-stat-number"><?php echo esc_html( number_format_i18n( $customer_stats['sms_opt_in_customers'] ?? 0 ) ); ?></strong>
				<p><?php esc_html_e( 'Customers who can receive booking updates by text.', 'glowbook' ); ?></p>
			</div>
		</div>

		<form method="get" class="sodek-gb-customer-toolbar">
			<input type="hidden" name="page" value="sodek-gb-customers">
			<div class="sodek-gb-customer-toolbar-copy">
				<h2><?php esc_html_e( 'Customer Directory', 'glowbook' ); ?></h2>
				<p><?php esc_html_e( 'Search by customer name, email address, or phone number to jump straight into a profile.', 'glowbook' ); ?></p>
			</div>
			<div class="sodek-gb-customer-toolbar-search">
				<label class="screen-reader-text" for="sodek-gb-customer-search"><?php esc_html_e( 'Search customers', 'glowbook' ); ?></label>
				<input id="sodek-gb-customer-search" type="search" name="s" value="<?php echo esc_attr( $search_query ); ?>" placeholder="<?php esc_attr_e( 'Search by name, email, or phone', 'glowbook' ); ?>">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Search', 'glowbook' ); ?></button>
				<?php if ( '' !== $search_query ) : ?>
					<a href="<?php echo esc_url( $base_url ); ?>" class="button"><?php esc_html_e( 'Clear', 'glowbook' ); ?></a>
				<?php endif; ?>
			</div>
		</form>

		<section class="sodek-gb-customer-list-card sodek-gb-customer-list-card-full">
			<div class="sodek-gb-card-header">
				<div>
					<h2><?php esc_html_e( 'Directory', 'glowbook' ); ?></h2>
					<p><?php esc_html_e( 'Open a dedicated profile page for the customer you want to inspect.', 'glowbook' ); ?></p>
				</div>
				<span class="sodek-gb-card-pill">
					<?php
					printf(
						/* translators: %s: count */
						esc_html__( '%s result(s)', 'glowbook' ),
						number_format_i18n( $total )
					);
					?>
				</span>
			</div>

			<?php if ( empty( $customers ) ) : ?>
				<div class="sodek-gb-empty-state">
					<h3><?php esc_html_e( 'No customers found', 'glowbook' ); ?></h3>
					<p><?php esc_html_e( 'Try another search or clear the current filter to see more customer profiles.', 'glowbook' ); ?></p>
				</div>
			<?php else : ?>
				<div class="sodek-gb-customer-directory-list">
					<?php foreach ( $customers as $entry ) : ?>
						<?php
						$profile_url = add_query_arg(
							array(
								'page'        => 'sodek-gb-customers',
								'customer_id' => $entry['id'],
								's'           => $search_query,
								'paged'       => $current_page,
							),
							admin_url( 'admin.php' )
						);
						?>
						<a class="sodek-gb-customer-row" href="<?php echo esc_url( $profile_url ); ?>">
							<div class="sodek-gb-customer-row-main">
								<div class="sodek-gb-customer-avatar">
									<?php echo esc_html( strtoupper( substr( $entry['name'], 0, 1 ) ) ); ?>
								</div>
								<div class="sodek-gb-customer-row-copy">
									<div class="sodek-gb-customer-row-topline">
										<strong><?php echo esc_html( $entry['name'] ); ?></strong>
										<span class="sodek-gb-customer-row-meta"><?php printf( esc_html__( 'Customer #%d', 'glowbook' ), (int) $entry['id'] ); ?></span>
									</div>
									<div class="sodek-gb-customer-contact-stack">
										<?php if ( ! empty( $entry['email'] ) ) : ?>
											<span><?php echo esc_html( $entry['email'] ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $entry['phone'] ) ) : ?>
											<span><?php echo esc_html( $entry['phone'] ); ?></span>
										<?php endif; ?>
									</div>
								</div>
							</div>
							<div class="sodek-gb-customer-row-stats">
								<div>
									<span><?php esc_html_e( 'Bookings', 'glowbook' ); ?></span>
									<strong><?php echo esc_html( number_format_i18n( $entry['total_bookings'] ) ); ?></strong>
								</div>
								<div>
									<span><?php esc_html_e( 'Spent', 'glowbook' ); ?></span>
									<strong><?php echo esc_html( $format_money( $entry['total_spent'] ) ); ?></strong>
								</div>
								<div>
									<span><?php esc_html_e( 'Last booking', 'glowbook' ); ?></span>
									<strong><?php echo esc_html( $format_date( $entry['last_booking'] ) ); ?></strong>
								</div>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom">
					<div class="tablenav-pages">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => add_query_arg(
										array(
											'page'  => 'sodek-gb-customers',
											's'     => $search_query,
											'paged' => '%#%',
										),
										admin_url( 'admin.php' )
									),
									'format'    => '',
									'current'   => $current_page,
									'total'     => $total_pages,
									'prev_text' => '&laquo;',
									'next_text' => '&raquo;',
								)
							)
						);
						?>
					</div>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</div>

<style>
.sodek-gb-customers-page {
    --sodek-gb-ink: #1f2328;
    --sodek-gb-muted: #667085;
    --sodek-gb-line: #dde3ea;
    --sodek-gb-surface: #ffffff;
    --sodek-gb-surface-soft: #f7efe6;
    --sodek-gb-surface-tint: #fcfaf7;
    --sodek-gb-accent: #b67831;
    --sodek-gb-accent-deep: #8a5a21;
    --sodek-gb-shadow: 0 20px 45px rgba(16, 24, 40, 0.06);
    color: var(--sodek-gb-ink);
}
.sodek-gb-customer-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 20px;
    align-items: end;
    padding: 28px 30px;
    margin: 18px 0;
    background: linear-gradient(135deg, #fffaf5 0%, #f7efe6 100%);
    border: 1px solid #eadfce;
    border-radius: 22px;
    box-shadow: var(--sodek-gb-shadow);
}
.sodek-gb-customer-hero-profile {
    align-items: start;
}
.sodek-gb-back-link {
    display: inline-flex;
    margin-bottom: 14px;
    color: var(--sodek-gb-accent-deep);
    text-decoration: none;
    font-weight: 600;
}
.sodek-gb-back-link:hover {
    color: var(--sodek-gb-accent);
}
.sodek-gb-customer-kicker {
    display: inline-flex;
    margin-bottom: 10px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--sodek-gb-accent-deep);
}
.sodek-gb-customer-hero h1 {
    margin: 0 0 8px;
    font-size: 34px;
    line-height: 1.08;
}
.sodek-gb-customer-hero p {
    margin: 0;
    max-width: 760px;
    font-size: 14px;
    line-height: 1.65;
    color: var(--sodek-gb-muted);
}
.sodek-gb-customer-hero-note {
    display: grid;
    gap: 6px;
    min-width: 220px;
    padding: 18px 20px;
    background: rgba(255, 255, 255, 0.82);
    border: 1px solid rgba(182, 120, 49, 0.16);
    border-radius: 18px;
    text-align: right;
}
.sodek-gb-customer-hero-note-profile {
    display: flex;
    align-items: center;
    gap: 14px;
    text-align: left;
}
.sodek-gb-customer-hero-note strong {
    font-size: 30px;
    line-height: 1;
}
.sodek-gb-customer-hero-note span {
    color: var(--sodek-gb-muted);
    font-size: 13px;
    line-height: 1.5;
}
.sodek-gb-customer-stats-grid,
.sodek-gb-customer-profile-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 18px;
}
.sodek-gb-customer-stat-card,
.sodek-gb-customer-toolbar,
.sodek-gb-customer-list-card,
.sodek-gb-customer-panel,
.sodek-gb-customer-summary-card {
    background: var(--sodek-gb-surface);
    border: 1px solid var(--sodek-gb-line);
    border-radius: 20px;
    box-shadow: 0 12px 30px rgba(16, 24, 40, 0.04);
}
.sodek-gb-customer-stat-card,
.sodek-gb-customer-summary-card {
    padding: 18px 20px;
}
.sodek-gb-customer-stat-label {
    display: inline-flex;
    margin-bottom: 16px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--sodek-gb-muted);
}
.sodek-gb-customer-stat-number {
    display: block;
    margin-bottom: 10px;
    font-size: 34px;
    line-height: 1;
}
.sodek-gb-customer-stat-card p {
    margin: 0;
    color: var(--sodek-gb-muted);
    font-size: 13px;
    line-height: 1.55;
}
.sodek-gb-customer-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 18px;
    padding: 18px 20px;
    margin-bottom: 18px;
}
.sodek-gb-customer-toolbar-copy h2,
.sodek-gb-card-header h2,
.sodek-gb-panel-heading h3 {
    margin: 0;
}
.sodek-gb-customer-toolbar-copy p,
.sodek-gb-card-header p,
.sodek-gb-panel-heading p {
    margin: 6px 0 0;
    color: var(--sodek-gb-muted);
}
.sodek-gb-customer-toolbar-search {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}
.sodek-gb-customer-toolbar-search input[type="search"] {
    width: 360px;
    max-width: 100%;
    min-height: 42px;
    padding: 0 14px;
    border: 1px solid #ccd5df;
    border-radius: 12px;
}
.sodek-gb-customer-list-card {
    padding: 20px;
}
.sodek-gb-customer-list-card-full {
    margin-top: 0;
}
.sodek-gb-card-header {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    align-items: flex-start;
    margin-bottom: 16px;
}
.sodek-gb-card-pill {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    padding: 0 12px;
    background: #f6f2eb;
    border: 1px solid #eadfce;
    border-radius: 999px;
    color: var(--sodek-gb-accent-deep);
    font-weight: 600;
}
.sodek-gb-customer-directory-list {
    display: grid;
    gap: 12px;
}
.sodek-gb-customer-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 18px;
    padding: 16px 18px;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    text-decoration: none;
    color: inherit;
    background: #fff;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}
.sodek-gb-customer-row:hover,
.sodek-gb-customer-row:focus {
    border-color: #d9c3a6;
    box-shadow: 0 14px 30px rgba(16, 24, 40, 0.08);
    transform: translateY(-1px);
}
.sodek-gb-customer-row-main {
    display: flex;
    gap: 14px;
    align-items: center;
    min-width: 0;
}
.sodek-gb-customer-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, #2f3338 0%, #54585f 100%);
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    flex: 0 0 44px;
}
.sodek-gb-customer-avatar-large {
    width: 62px;
    height: 62px;
    border-radius: 18px;
    font-size: 24px;
    flex-basis: 62px;
}
.sodek-gb-customer-row-copy {
    min-width: 0;
}
.sodek-gb-customer-row-topline {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}
.sodek-gb-customer-row-topline strong {
    font-size: 15px;
}
.sodek-gb-customer-row-meta,
.sodek-gb-customer-contact-stack span {
    color: var(--sodek-gb-muted);
    font-size: 13px;
}
.sodek-gb-customer-contact-stack {
    display: grid;
    gap: 4px;
    margin-top: 6px;
}
.sodek-gb-customer-row-stats {
    display: flex;
    gap: 18px;
    align-items: center;
    flex-wrap: wrap;
}
.sodek-gb-customer-row-stats div {
    display: grid;
    gap: 4px;
    min-width: 92px;
}
.sodek-gb-customer-row-stats span {
    color: var(--sodek-gb-muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.sodek-gb-customer-row-stats strong {
    font-size: 13px;
    color: var(--sodek-gb-ink);
}
.sodek-gb-customer-flag-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 12px;
}
.sodek-gb-customer-flag {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0 10px;
    border-radius: 999px;
    background: #f3f4f6;
    color: #475467;
    font-size: 12px;
    font-weight: 600;
}
.sodek-gb-customer-flag.is-positive {
    background: #eaf8ef;
    color: #166534;
}
.sodek-gb-customer-flag.is-account-wordpress {
    background: #edf4ff;
    color: #1d4ed8;
}
.sodek-gb-customer-flag.is-account-portal {
    background: #fff3e6;
    color: #9a5b00;
}
.sodek-gb-customer-flag.is-account-guest {
    background: #f3f4f6;
    color: #475467;
}
.sodek-gb-customer-account-note {
    display: grid;
    gap: 8px;
    padding: 14px 16px;
    border-radius: 16px;
    background: #faf7f2;
    border: 1px solid #eadfce;
}
.sodek-gb-customer-account-note strong {
    font-size: 15px;
}
.sodek-gb-customer-account-note span {
    color: var(--sodek-gb-muted);
    line-height: 1.6;
}
.sodek-gb-customer-summary-card span {
    display: block;
    margin-bottom: 10px;
    color: var(--sodek-gb-muted);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.sodek-gb-customer-summary-card strong {
    font-size: 22px;
    line-height: 1.15;
}
.sodek-gb-customer-profile-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
    gap: 20px;
    align-items: start;
}
.sodek-gb-customer-profile-main-column,
.sodek-gb-customer-profile-side-column,
.sodek-gb-customer-split {
    display: grid;
    gap: 16px;
}
.sodek-gb-customer-split {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}
.sodek-gb-customer-panel {
    padding: 20px;
}
.sodek-gb-panel-heading {
    margin-bottom: 16px;
}
.sodek-gb-customer-meta {
    display: grid;
    gap: 14px;
    margin: 0;
}
.sodek-gb-customer-meta div {
    display: grid;
    gap: 5px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eef1f5;
}
.sodek-gb-customer-meta div:last-child {
    padding-bottom: 0;
    border-bottom: none;
}
.sodek-gb-customer-meta dt {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--sodek-gb-muted);
}
.sodek-gb-customer-meta dd {
    margin: 0;
    font-size: 14px;
    color: var(--sodek-gb-ink);
}
.sodek-gb-card-list,
.sodek-gb-booking-list {
    margin: 0;
    padding: 0;
    list-style: none;
}
.sodek-gb-card-list li,
.sodek-gb-booking-list li {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eef1f5;
}
.sodek-gb-card-list li:last-child,
.sodek-gb-booking-list li:last-child {
    border-bottom: none;
}
.sodek-gb-card-list li span,
.sodek-gb-booking-list li span {
    color: var(--sodek-gb-muted);
}
.sodek-gb-card-list-meta {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.sodek-gb-customer-booking-table-wrap {
    overflow-x: auto;
}
.sodek-gb-mini-table {
    border: 1px solid #e7ecf2;
    border-radius: 16px;
    overflow: hidden;
}
.sodek-gb-mini-table th,
.sodek-gb-mini-table td {
    padding: 12px 14px;
    vertical-align: middle;
}
.sodek-gb-mini-table td a {
    font-weight: 600;
}
.sodek-gb-empty-state {
    padding: 42px 24px;
    text-align: center;
    border: 1px dashed #d7dfe8;
    border-radius: 18px;
    background: var(--sodek-gb-surface-tint);
}
@media screen and (max-width: 1200px) {
    .sodek-gb-customer-stats-grid,
    .sodek-gb-customer-profile-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .sodek-gb-customer-profile-grid,
    .sodek-gb-customer-split {
        grid-template-columns: 1fr;
    }
}
@media screen and (max-width: 960px) {
    .sodek-gb-customer-hero,
    .sodek-gb-customer-toolbar,
    .sodek-gb-customer-row {
        grid-template-columns: 1fr;
    }
    .sodek-gb-customer-hero,
    .sodek-gb-customer-toolbar {
        display: grid;
    }
    .sodek-gb-customer-hero-note,
    .sodek-gb-customer-hero-note-profile {
        text-align: left;
    }
}
@media screen and (max-width: 782px) {
    .sodek-gb-customers-page {
        margin-right: 10px;
    }
    .sodek-gb-customer-stats-grid,
    .sodek-gb-customer-profile-summary {
        grid-template-columns: 1fr;
    }
    .sodek-gb-customer-toolbar-search,
    .sodek-gb-customer-row-stats,
    .sodek-gb-card-list li,
    .sodek-gb-booking-list li,
    .sodek-gb-customer-hero-note-profile {
        flex-direction: column;
        align-items: flex-start;
    }
    .sodek-gb-customer-row {
        padding: 14px;
    }
    .sodek-gb-customer-row-main {
        align-items: flex-start;
    }
}
</style>
