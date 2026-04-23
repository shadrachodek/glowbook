<?php
/**
 * Admin dashboard view.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap sodek-gb-admin-wrap">
    <h1><?php esc_html_e( 'GlowBook Dashboard', 'glowbook' ); ?></h1>

    <div class="sodek-gb-dashboard-stats">
        <div class="sodek-gb-stat-box">
            <span class="sodek-gb-stat-number"><?php echo esc_html( $stats['today_count'] ); ?></span>
            <span class="sodek-gb-stat-label"><?php esc_html_e( 'Today\'s Appointments', 'glowbook' ); ?></span>
        </div>
        <div class="sodek-gb-stat-box">
            <span class="sodek-gb-stat-number"><?php echo esc_html( $stats['week_count'] ); ?></span>
            <span class="sodek-gb-stat-label"><?php esc_html_e( 'This Week', 'glowbook' ); ?></span>
        </div>
        <div class="sodek-gb-stat-box">
            <span class="sodek-gb-stat-number"><?php echo esc_html( $stats['pending_count'] ); ?></span>
            <span class="sodek-gb-stat-label"><?php esc_html_e( 'Pending', 'glowbook' ); ?></span>
        </div>
        <div class="sodek-gb-stat-box">
            <span class="sodek-gb-stat-number"><?php echo wc_price( $stats['monthly_revenue'] ); ?></span>
            <span class="sodek-gb-stat-label"><?php esc_html_e( 'Monthly Deposits', 'glowbook' ); ?></span>
        </div>
    </div>

    <div class="sodek-gb-dashboard-columns">
        <div class="sodek-gb-dashboard-column">
            <h2><?php esc_html_e( 'Today\'s Schedule', 'glowbook' ); ?> - <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $today ) ) ); ?></h2>

            <?php if ( empty( $today_bookings ) ) : ?>
                <p class="sodek-gb-no-bookings"><?php esc_html_e( 'No appointments scheduled for today.', 'glowbook' ); ?></p>
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
                            <td>
                                <strong><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?></strong>
                                <br><small><?php echo esc_html( $booking['service']['duration'] ); ?> min</small>
                            </td>
                            <td>
                                <?php echo esc_html( $booking['customer_name'] ); ?>
                                <br><small><?php echo esc_html( $booking['customer_phone'] ); ?></small>
                            </td>
                            <td><?php echo esc_html( $booking['service']['title'] ); ?></td>
                            <td>
                                <span class="sodek-gb-status sodek-gb-status-<?php echo esc_attr( $booking['status'] ); ?>">
                                    <?php echo esc_html( ucfirst( $booking['status'] ) ); ?>
                                </span>
                            </td>
                            <td>
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
            <h2><?php esc_html_e( 'Upcoming Appointments', 'glowbook' ); ?></h2>

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
                            <td>
                                <strong><?php echo esc_html( date_i18n( 'M j', strtotime( $booking['booking_date'] ) ) ); ?></strong>
                                <br><small><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?></small>
                            </td>
                            <td><?php echo esc_html( $booking['customer_name'] ); ?></td>
                            <td><?php echo esc_html( $booking['service']['title'] ); ?></td>
                            <td>
                                <span class="sodek-gb-status sodek-gb-status-<?php echo esc_attr( $booking['status'] ); ?>">
                                    <?php echo esc_html( ucfirst( $booking['status'] ) ); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p style="margin-top: 15px;">
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=sodek_gb_booking' ) ); ?>" class="button">
                    <?php esc_html_e( 'View All Bookings', 'glowbook' ); ?>
                </a>
            </p>
        </div>
    </div>
</div>
