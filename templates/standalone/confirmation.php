<?php
/**
 * Booking Confirmation Page Template.
 *
 * @package GlowBook
 * @since   2.0.0
 *
 * @var array  $booking         Booking data.
 * @var array  $staff           Staff member data.
 * @var string $receipt_url     Payment receipt URL.
 * @var bool   $deposit_paid    Whether deposit was paid.
 * @var float  $balance_amount  Balance due.
 * @var array  $calendar_links  Calendar add links.
 * @var string $business_name   Business name.
 * @var string $business_phone  Business phone.
 * @var string $business_email  Business email.
 * @var string $business_address Business address.
 * @var string $portal_url      Customer portal URL.
 * @var bool   $can_reschedule  Whether booking can be rescheduled.
 * @var bool   $can_cancel      Whether booking can be cancelled.
 */

defined( 'ABSPATH' ) || exit;

$service = $booking['service'] ?? array();
?>

<div class="sodek-gb-confirmation">
    <div class="sodek-gb-container">
        <!-- Success Message -->
        <div class="sodek-gb-confirmation-header">
            <div class="sodek-gb-success-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>

            <h1><?php esc_html_e( 'Booking Confirmed!', 'glowbook' ); ?></h1>
            <p class="sodek-gb-confirmation-subtitle">
                <?php
                printf(
                    /* translators: %s: customer email */
                    esc_html__( 'A confirmation email has been sent to %s', 'glowbook' ),
                    '<strong>' . esc_html( $booking['customer_email'] ) . '</strong>'
                );
                ?>
            </p>

            <div class="sodek-gb-confirmation-meta">
                <span class="sodek-gb-confirmation-pill">
                    <?php esc_html_e( 'Confirmation', 'glowbook' ); ?>
                    <strong>#<?php echo esc_html( $booking['id'] ); ?></strong>
                </span>
                <?php if ( ! empty( $service['title'] ) ) : ?>
                    <span class="sodek-gb-confirmation-pill is-soft"><?php echo esc_html( $service['title'] ); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="sodek-gb-confirmation-grid">
            <div class="sodek-gb-confirmation-card sodek-gb-confirmation-card-primary">
                <div class="sodek-gb-confirmation-section-head">
                    <span class="sodek-gb-section-kicker"><?php esc_html_e( 'Appointment', 'glowbook' ); ?></span>
                    <h2><?php esc_html_e( 'Your booking details', 'glowbook' ); ?></h2>
                </div>

                <div class="sodek-gb-confirmation-details">
                    <div class="sodek-gb-detail-row sodek-gb-detail-service">
                        <span class="sodek-gb-detail-label"><?php esc_html_e( 'Service', 'glowbook' ); ?></span>
                        <span class="sodek-gb-detail-value"><?php echo esc_html( $service['title'] ?? '' ); ?></span>
                    </div>

                    <?php if ( $staff ) : ?>
                        <div class="sodek-gb-detail-row">
                            <span class="sodek-gb-detail-label"><?php esc_html_e( 'Stylist', 'glowbook' ); ?></span>
                            <span class="sodek-gb-detail-value"><?php echo esc_html( $staff['name'] ); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="sodek-gb-detail-row sodek-gb-detail-datetime">
                        <span class="sodek-gb-detail-label"><?php esc_html_e( 'Date & Time', 'glowbook' ); ?></span>
                        <span class="sodek-gb-detail-value">
                            <strong><?php echo esc_html( date_i18n( 'l, F j, Y', strtotime( $booking['booking_date'] ) ) ); ?></strong>
                            <br>
                            <?php
                            echo esc_html(
                                date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) .
                                ' - ' .
                                date_i18n( get_option( 'time_format' ), strtotime( $booking['end_time'] ) )
                            );
                            ?>
                        </span>
                    </div>

                    <div class="sodek-gb-detail-row">
                        <span class="sodek-gb-detail-label"><?php esc_html_e( 'Duration', 'glowbook' ); ?></span>
                        <span class="sodek-gb-detail-value">
                            <?php
                            $duration = ( $service['duration'] ?? 0 ) + ( $booking['addons_duration'] ?? 0 );
                            echo esc_html( Sodek_GB_Booking_Page::format_duration( $duration ) );
                            ?>
                        </span>
                    </div>

                    <?php if ( ! empty( $booking['addons'] ) ) : ?>
                        <div class="sodek-gb-detail-row">
                            <span class="sodek-gb-detail-label"><?php esc_html_e( 'Add-ons', 'glowbook' ); ?></span>
                            <span class="sodek-gb-detail-value">
                                <?php
                                $addon_names = array_map( function( $addon ) {
                                    return $addon['title'];
                                }, $booking['addons'] );
                                echo esc_html( implode( ', ', $addon_names ) );
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="sodek-gb-detail-row">
                        <span class="sodek-gb-detail-label"><?php esc_html_e( 'Confirmation #', 'glowbook' ); ?></span>
                        <span class="sodek-gb-detail-value sodek-gb-booking-id">#<?php echo esc_html( $booking['id'] ); ?></span>
                    </div>
                </div>
            </div>

            <div class="sodek-gb-confirmation-side">
                <div class="sodek-gb-confirmation-card sodek-gb-confirmation-payment">
                    <div class="sodek-gb-confirmation-section-head">
                        <span class="sodek-gb-section-kicker"><?php esc_html_e( 'Receipt', 'glowbook' ); ?></span>
                        <h3><?php esc_html_e( 'Payment Summary', 'glowbook' ); ?></h3>
                    </div>

                    <div class="sodek-gb-payment-lines">
                        <div class="sodek-gb-payment-line">
                            <span><?php echo esc_html( $service['title'] ?? '' ); ?></span>
                            <span><?php echo wp_kses_post( Sodek_GB_Booking_Page::format_price( $service['price'] ?? 0 ) ); ?></span>
                        </div>

                        <?php if ( ! empty( $booking['addons'] ) ) : ?>
                            <?php foreach ( $booking['addons'] as $addon ) : ?>
                                <div class="sodek-gb-payment-line">
                                    <span><?php echo esc_html( $addon['title'] ); ?></span>
                                    <span><?php echo wp_kses_post( Sodek_GB_Booking_Page::format_price( $addon['price'] ) ); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="sodek-gb-payment-line sodek-gb-total">
                            <span><?php esc_html_e( 'Total', 'glowbook' ); ?></span>
                            <span><?php echo wp_kses_post( Sodek_GB_Booking_Page::format_price( $booking['total_price'] ) ); ?></span>
                        </div>

                        <?php if ( $deposit_paid ) : ?>
                            <div class="sodek-gb-payment-line sodek-gb-paid">
                                <span><?php esc_html_e( 'Paid Today', 'glowbook' ); ?></span>
                                <span class="sodek-gb-text-success">
                                    -<?php echo wp_kses_post( Sodek_GB_Booking_Page::format_price( $booking['deposit_amount'] ) ); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if ( $balance_amount > 0 ) : ?>
                            <div class="sodek-gb-payment-line sodek-gb-balance">
                                <span><?php esc_html_e( 'Balance Due at Appointment', 'glowbook' ); ?></span>
                                <span><?php echo wp_kses_post( Sodek_GB_Booking_Page::format_price( $balance_amount ) ); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ( $receipt_url ) : ?>
                        <a href="<?php echo esc_url( $receipt_url ); ?>" target="_blank" class="sodek-gb-receipt-link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <?php esc_html_e( 'View Receipt', 'glowbook' ); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="sodek-gb-confirmation-card sodek-gb-confirmation-card-secondary">
                    <div class="sodek-gb-confirmation-section-head">
                        <span class="sodek-gb-section-kicker"><?php esc_html_e( 'Next steps', 'glowbook' ); ?></span>
                        <h3><?php esc_html_e( 'Add to your calendar', 'glowbook' ); ?></h3>
                    </div>

                    <div class="sodek-gb-calendar-buttons">
                        <a href="<?php echo esc_url( $calendar_links['google'] ); ?>" target="_blank" class="sodek-gb-btn sodek-gb-btn-outline">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Google Calendar
                        </a>
                        <a href="<?php echo esc_url( $calendar_links['ical'] ); ?>" download="appointment.ics" class="sodek-gb-btn sodek-gb-btn-outline">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            Apple Calendar
                        </a>
                        <a href="<?php echo esc_url( $calendar_links['outlook'] ); ?>" target="_blank" class="sodek-gb-btn sodek-gb-btn-outline">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7.88 12.04q0 .45-.11.87-.1.41-.33.74-.22.33-.58.52-.37.2-.87.2t-.85-.2q-.35-.21-.57-.55-.22-.33-.33-.75-.1-.42-.1-.86t.1-.87q.1-.43.34-.76.22-.34.59-.54.36-.2.87-.2t.86.2q.35.21.57.55.22.34.31.77.1.43.1.88zM24 12v9.38q0 .46-.33.8-.33.32-.8.32H7.13q-.46 0-.8-.33-.32-.33-.32-.8V18H1q-.41 0-.7-.3-.3-.29-.3-.7V7q0-.41.3-.7Q.58 6 1 6h6.5V2.55q0-.44.3-.75.3-.3.75-.3h12.9q.44 0 .75.3.3.3.3.75V12zm-6-8.25v3h3v-3h-3zm0 4.5v3h3v-3h-3zm0 4.5v1.83l3.05-1.83H18zm-5.25-9v3h3.75v-3H12.75zm0 4.5v3h3.75v-3H12.75zm0 4.5v2.03l2.41 1.5 1.34-.8v-2.73H12.75zM9 3.75V6h2.25V3.75H9zM6 .75v4.9l1.5-.15V1.5L6 .75zm0 5.25h-3v1.5h3V6zM3.75 9v3h3v-3h-3zm0 4.5v3h3v-3h-3z"/>
                            </svg>
                            Outlook
                        </a>
                    </div>

                    <?php if ( $business_address ) : ?>
                        <div class="sodek-gb-location-section">
                            <h3><?php esc_html_e( 'Location', 'glowbook' ); ?></h3>
                            <div class="sodek-gb-location-info">
                                <p><strong><?php echo esc_html( $business_name ); ?></strong></p>
                                <p><?php echo nl2br( esc_html( $business_address ) ); ?></p>
                                <?php if ( $business_phone ) : ?>
                                    <p>
                                        <a href="tel:<?php echo esc_attr( $business_phone ); ?>">
                                            <?php echo esc_html( $business_phone ); ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="sodek-gb-confirmation-actions">
            <?php if ( $can_reschedule ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'booking_id', $booking['id'], $portal_url . 'reschedule/' ) ); ?>" class="sodek-gb-btn sodek-gb-btn-secondary">
                    <?php esc_html_e( 'Reschedule', 'glowbook' ); ?>
                </a>
            <?php endif; ?>

            <?php if ( $can_cancel ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'booking_id', $booking['id'], $portal_url . 'cancel/' ) ); ?>" class="sodek-gb-btn sodek-gb-btn-link sodek-gb-text-danger">
                    <?php esc_html_e( 'Cancel Booking', 'glowbook' ); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- View All Appointments -->
        <div class="sodek-gb-confirmation-footer">
            <a href="<?php echo esc_url( $portal_url ); ?>" class="sodek-gb-btn sodek-gb-btn-primary">
                <?php esc_html_e( 'View My Appointments', 'glowbook' ); ?>
            </a>
            <a href="<?php echo esc_url( Sodek_GB_Standalone_Booking::get_booking_url() ); ?>" class="sodek-gb-btn sodek-gb-btn-link">
                <?php esc_html_e( 'Book Another Appointment', 'glowbook' ); ?>
            </a>
        </div>
    </div>
</div>
