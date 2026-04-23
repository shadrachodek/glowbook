<?php
/**
 * My Account - Bookings template.
 *
 * @package GlowBook
 * @var array  $upcoming_bookings Upcoming bookings
 * @var array  $past_bookings     Past bookings
 * @var string $message           Message to display
 * @var string $message_type      Message type (success/error)
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="sodek-gb-my-bookings" role="region" aria-label="<?php esc_attr_e( 'My Bookings', 'glowbook' ); ?>">
	<?php if ( $message ) : ?>
		<div class="sodek-gb-message sodek-gb-message-<?php echo esc_attr( $message_type ); ?>" role="alert">
			<?php echo esc_html( $message ); ?>
		</div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Upcoming Appointments', 'glowbook' ); ?></h2>

	<?php if ( empty( $upcoming_bookings ) ) : ?>
		<p class="sodek-gb-no-bookings"><?php esc_html_e( 'You have no upcoming appointments.', 'glowbook' ); ?></p>
		<p><a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="button"><?php esc_html_e( 'Book an Appointment', 'glowbook' ); ?></a></p>
	<?php else : ?>
		<div class="sodek-gb-bookings-list" role="list" aria-label="<?php esc_attr_e( 'Upcoming appointments list', 'glowbook' ); ?>">
			<?php foreach ( $upcoming_bookings as $booking ) :
				$can_cancel     = Sodek_GB_My_Account::can_cancel_booking( $booking );
				$can_reschedule = Sodek_GB_My_Account::can_reschedule_booking( $booking );
				$cancel_url     = wp_nonce_url(
					add_query_arg( 'sodek_gb_cancel_booking', $booking['id'] ),
					'sodek_gb_cancel_booking'
				);
			?>
				<article class="sodek-gb-booking-card sodek-gb-booking-status-<?php echo esc_attr( $booking['status'] ); ?>" role="listitem" aria-label="<?php echo esc_attr( sprintf( __( 'Booking for %s', 'glowbook' ), $booking['service']['title'] ) ); ?>">
                    <div class="sodek-gb-booking-header">
                        <div class="sodek-gb-booking-service">
                            <h3><?php echo esc_html( $booking['service']['title'] ); ?></h3>
                            <span class="sodek-gb-booking-status-badge sodek-gb-status-<?php echo esc_attr( $booking['status'] ); ?>">
                                <?php echo esc_html( ucfirst( $booking['status'] ) ); ?>
                            </span>
                        </div>
                    </div>

                    <div class="sodek-gb-booking-details">
                        <div class="sodek-gb-booking-datetime">
                            <div class="sodek-gb-booking-date">
                                <span class="sodek-gb-icon">&#128197;</span>
                                <span><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ); ?></span>
                            </div>
                            <div class="sodek-gb-booking-time">
                                <span class="sodek-gb-icon">&#128337;</span>
                                <span><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?></span>
                            </div>
                            <div class="sodek-gb-booking-duration">
                                <span class="sodek-gb-icon">&#9202;</span>
                                <span><?php echo esc_html( $booking['service']['duration'] ); ?> <?php esc_html_e( 'minutes', 'glowbook' ); ?></span>
                            </div>
                        </div>

                        <div class="sodek-gb-booking-pricing">
                            <div class="sodek-gb-price-row">
                                <span><?php esc_html_e( 'Service Price:', 'glowbook' ); ?></span>
                                <span><?php echo wc_price( $booking['total_price'] ); ?></span>
                            </div>
                            <div class="sodek-gb-price-row">
                                <span><?php esc_html_e( 'Deposit Paid:', 'glowbook' ); ?></span>
                                <span class="sodek-gb-paid"><?php echo wc_price( $booking['deposit_amount'] ); ?></span>
                            </div>
                            <?php $balance = $booking['total_price'] - $booking['deposit_amount']; ?>
                            <?php if ( $balance > 0 ) : ?>
                            <div class="sodek-gb-price-row sodek-gb-balance-row">
                                <span><?php esc_html_e( 'Balance Due:', 'glowbook' ); ?></span>
                                <span class="sodek-gb-balance"><?php echo wc_price( $balance ); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ( ! empty( $booking['notes'] ) ) : ?>
                        <div class="sodek-gb-booking-notes">
                            <strong><?php esc_html_e( 'Your Notes:', 'glowbook' ); ?></strong>
                            <p><?php echo esc_html( $booking['notes'] ); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="sodek-gb-booking-actions">
                        <?php if ( true === $can_reschedule ) : ?>
                            <button type="button" class="button sodek-gb-reschedule-btn"
                                data-booking-id="<?php echo esc_attr( $booking['id'] ); ?>"
                                data-service-id="<?php echo esc_attr( $booking['service_id'] ); ?>">
                                <?php esc_html_e( 'Reschedule', 'glowbook' ); ?>
                            </button>
                        <?php endif; ?>

                        <?php if ( true === $can_cancel ) : ?>
                            <a href="<?php echo esc_url( $cancel_url ); ?>"
                               class="button sodek-gb-cancel-btn"
                               onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to cancel this booking?', 'glowbook' ) ); ?>');">
                                <?php esc_html_e( 'Cancel Booking', 'glowbook' ); ?>
                            </a>
                        <?php endif; ?>

                        <?php if ( $booking['order_id'] ) : ?>
                            <a href="<?php echo esc_url( wc_get_order( $booking['order_id'] )->get_view_order_url() ); ?>" class="button">
                                <?php esc_html_e( 'View Order', 'glowbook' ); ?>
                            </a>
                        <?php endif; ?>

                        <a href="<?php echo esc_url( Sodek_GB_Confirmation::get_ics_url( $booking['id'] ) ); ?>" class="button sodek-gb-calendar-btn" download title="<?php esc_attr_e( 'Add to Calendar', 'glowbook' ); ?>">
                            <span class="sodek-gb-icon">&#128197;</span>
                            <?php esc_html_e( 'Add to Calendar', 'glowbook' ); ?>
                        </a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $past_bookings ) ) : ?>
		<h2><?php esc_html_e( 'Past Appointments', 'glowbook' ); ?></h2>
		<div class="sodek-gb-bookings-list sodek-gb-past-bookings" role="list" aria-label="<?php esc_attr_e( 'Past appointments list', 'glowbook' ); ?>">
			<?php foreach ( $past_bookings as $booking ) : ?>
				<article class="sodek-gb-booking-card sodek-gb-booking-past sodek-gb-booking-status-<?php echo esc_attr( $booking['status'] ); ?>" role="listitem">
                    <div class="sodek-gb-booking-header">
                        <div class="sodek-gb-booking-service">
                            <h3><?php echo esc_html( $booking['service']['title'] ); ?></h3>
                            <span class="sodek-gb-booking-status-badge sodek-gb-status-<?php echo esc_attr( $booking['status'] ); ?>">
                                <?php echo esc_html( ucfirst( $booking['status'] ) ); ?>
                            </span>
                        </div>
                    </div>

                    <div class="sodek-gb-booking-details">
                        <div class="sodek-gb-booking-datetime">
                            <div class="sodek-gb-booking-date">
                                <span class="sodek-gb-icon">&#128197;</span>
                                <span><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ); ?></span>
                            </div>
                            <div class="sodek-gb-booking-time">
                                <span class="sodek-gb-icon">&#128337;</span>
                                <span><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?></span>
                            </div>
                        </div>
                    </div>

					<div class="sodek-gb-booking-actions">
						<a href="<?php echo esc_url( home_url( '/services/' ) ); ?>" class="button">
							<?php esc_html_e( 'Book Again', 'glowbook' ); ?>
						</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<!-- Reschedule Modal -->
<div id="sodek-gb-reschedule-modal" class="sodek-gb-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="sodek-gb-modal-title">
	<div class="sodek-gb-modal-overlay" aria-hidden="true"></div>
	<div class="sodek-gb-modal-content">
		<button type="button" class="sodek-gb-modal-close" aria-label="<?php esc_attr_e( 'Close dialog', 'glowbook' ); ?>">&times;</button>
		<h3 id="sodek-gb-modal-title"><?php esc_html_e( 'Reschedule Appointment', 'glowbook' ); ?></h3>

		<input type="hidden" id="sodek-gb-reschedule-booking-id" value="">
		<input type="hidden" id="sodek-gb-reschedule-service-id" value="">

		<div class="sodek-gb-reschedule-calendar">
			<label for="sodek-gb-reschedule-date"><?php esc_html_e( 'Select New Date:', 'glowbook' ); ?></label>
			<input type="date" id="sodek-gb-reschedule-date" min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" aria-describedby="sodek-gb-reschedule-date-help">
			<p id="sodek-gb-reschedule-date-help" class="screen-reader-text"><?php esc_html_e( 'Choose a new date for your appointment', 'glowbook' ); ?></p>
		</div>

		<div class="sodek-gb-reschedule-slots" style="display: none;" aria-hidden="true">
			<label id="sodek-gb-reschedule-time-label"><?php esc_html_e( 'Select New Time:', 'glowbook' ); ?></label>
			<div id="sodek-gb-reschedule-time-slots" class="sodek-gb-time-slots" role="listbox" aria-labelledby="sodek-gb-reschedule-time-label"></div>
		</div>

		<div class="sodek-gb-modal-actions">
			<button type="button" class="button sodek-gb-modal-cancel"><?php esc_html_e( 'Cancel', 'glowbook' ); ?></button>
			<button type="button" class="button button-primary" id="sodek-gb-confirm-reschedule" disabled aria-disabled="true">
				<?php esc_html_e( 'Confirm Reschedule', 'glowbook' ); ?>
			</button>
		</div>
	</div>
</div>
