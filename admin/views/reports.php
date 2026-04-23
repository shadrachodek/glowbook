<?php
/**
 * Reports and Analytics page.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

// Date range
$range = isset( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '30days';
$custom_start = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : '';
$custom_end = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : '';

// Calculate date range
switch ( $range ) {
    case '7days':
        $start_date = date( 'Y-m-d', strtotime( '-7 days' ) );
        $end_date = date( 'Y-m-d' );
        $label = __( 'Last 7 Days', 'glowbook' );
        break;
    case '30days':
        $start_date = date( 'Y-m-d', strtotime( '-30 days' ) );
        $end_date = date( 'Y-m-d' );
        $label = __( 'Last 30 Days', 'glowbook' );
        break;
    case 'this_month':
        $start_date = date( 'Y-m-01' );
        $end_date = date( 'Y-m-t' );
        $label = __( 'This Month', 'glowbook' );
        break;
    case 'last_month':
        $start_date = date( 'Y-m-01', strtotime( 'first day of last month' ) );
        $end_date = date( 'Y-m-t', strtotime( 'last day of last month' ) );
        $label = __( 'Last Month', 'glowbook' );
        break;
    case 'this_year':
        $start_date = date( 'Y-01-01' );
        $end_date = date( 'Y-12-31' );
        $label = __( 'This Year', 'glowbook' );
        break;
    case 'custom':
        $start_date = $custom_start ?: date( 'Y-m-d', strtotime( '-30 days' ) );
        $end_date = $custom_end ?: date( 'Y-m-d' );
        $label = __( 'Custom Range', 'glowbook' );
        break;
    default:
        $start_date = date( 'Y-m-d', strtotime( '-30 days' ) );
        $end_date = date( 'Y-m-d' );
        $label = __( 'Last 30 Days', 'glowbook' );
}

// Get report data
$report_data = sodek_gb_get_report_data( $start_date, $end_date );
?>
<div class="wrap sodek-gb-admin-wrap sodek-gb-reports-wrap">
    <div class="sodek-gb-admin-hero sodek-gb-reports-hero">
        <div>
            <span class="sodek-gb-admin-kicker"><?php esc_html_e( 'Performance and Trends', 'glowbook' ); ?></span>
            <h1><?php esc_html_e( 'GlowBook Reports', 'glowbook' ); ?></h1>
            <p><?php esc_html_e( 'Track booking volume, revenue, cancellations, service demand, and appointment patterns across the date range you care about.', 'glowbook' ); ?></p>
        </div>
        <div class="sodek-gb-admin-hero-note">
            <strong><?php echo esc_html( $label ); ?></strong>
            <span>
                <?php
                printf(
                    /* translators: 1: start date, 2: end date */
                    esc_html__( 'Current window: %1$s to %2$s.', 'glowbook' ),
                    date_i18n( get_option( 'date_format' ), strtotime( $start_date ) ),
                    date_i18n( get_option( 'date_format' ), strtotime( $end_date ) )
                );
                ?>
            </span>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="sodek-gb-reports-filter">
        <form method="get" action="">
            <input type="hidden" name="page" value="sodek-gb-reports">

            <select name="range" id="sodek-gb-range-select">
                <option value="7days" <?php selected( $range, '7days' ); ?>><?php esc_html_e( 'Last 7 Days', 'glowbook' ); ?></option>
                <option value="30days" <?php selected( $range, '30days' ); ?>><?php esc_html_e( 'Last 30 Days', 'glowbook' ); ?></option>
                <option value="this_month" <?php selected( $range, 'this_month' ); ?>><?php esc_html_e( 'This Month', 'glowbook' ); ?></option>
                <option value="last_month" <?php selected( $range, 'last_month' ); ?>><?php esc_html_e( 'Last Month', 'glowbook' ); ?></option>
                <option value="this_year" <?php selected( $range, 'this_year' ); ?>><?php esc_html_e( 'This Year', 'glowbook' ); ?></option>
                <option value="custom" <?php selected( $range, 'custom' ); ?>><?php esc_html_e( 'Custom Range', 'glowbook' ); ?></option>
            </select>

            <span id="sodek-gb-custom-dates" style="<?php echo 'custom' !== $range ? 'display:none;' : ''; ?>">
                <input type="date" name="start_date" value="<?php echo esc_attr( $start_date ); ?>">
                <span><?php esc_html_e( 'to', 'glowbook' ); ?></span>
                <input type="date" name="end_date" value="<?php echo esc_attr( $end_date ); ?>">
            </span>

            <button type="submit" class="button"><?php esc_html_e( 'Apply', 'glowbook' ); ?></button>

            <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array(
                'sodek_gb_export' => 'csv',
                'start_date'      => $start_date,
                'end_date'        => $end_date,
            ), admin_url( 'admin.php' ) ), 'sodek_gb_export_bookings' ) ); ?>" class="button button-secondary" style="margin-left: 10px;">
                <span class="dashicons dashicons-download" style="vertical-align: middle; margin-top: -2px;"></span>
                <?php esc_html_e( 'Export CSV', 'glowbook' ); ?>
            </a>
        </form>

        <p class="sodek-gb-date-range-label">
            <?php
            printf(
                /* translators: 1: start date, 2: end date */
                esc_html__( 'Showing data from %1$s to %2$s', 'glowbook' ),
                '<strong>' . date_i18n( get_option( 'date_format' ), strtotime( $start_date ) ) . '</strong>',
                '<strong>' . date_i18n( get_option( 'date_format' ), strtotime( $end_date ) ) . '</strong>'
            );
            ?>
        </p>
    </div>

    <!-- Key Metrics -->
    <div class="sodek-gb-dashboard-stats sodek-gb-reports-stats">
        <div class="sodek-gb-stat-box">
            <span class="sodek-gb-stat-number"><?php echo esc_html( $report_data['total_bookings'] ); ?></span>
            <span class="sodek-gb-stat-label"><?php esc_html_e( 'Total Bookings', 'glowbook' ); ?></span>
        </div>
        <div class="sodek-gb-stat-box">
            <span class="sodek-gb-stat-number"><?php echo wc_price( $report_data['total_deposits'] ); ?></span>
            <span class="sodek-gb-stat-label"><?php esc_html_e( 'Deposits Collected', 'glowbook' ); ?></span>
        </div>
        <div class="sodek-gb-stat-box">
            <span class="sodek-gb-stat-number"><?php echo wc_price( $report_data['total_revenue'] ); ?></span>
            <span class="sodek-gb-stat-label"><?php esc_html_e( 'Total Revenue', 'glowbook' ); ?></span>
        </div>
        <div class="sodek-gb-stat-box">
            <span class="sodek-gb-stat-number"><?php echo esc_html( $report_data['completion_rate'] ); ?>%</span>
            <span class="sodek-gb-stat-label"><?php esc_html_e( 'Completion Rate', 'glowbook' ); ?></span>
        </div>
        <div class="sodek-gb-stat-box">
            <span class="sodek-gb-stat-number"><?php echo esc_html( $report_data['cancellation_rate'] ); ?>%</span>
            <span class="sodek-gb-stat-label"><?php esc_html_e( 'Cancellation Rate', 'glowbook' ); ?></span>
        </div>
    </div>

    <div class="sodek-gb-reports-grid">
        <!-- Bookings by Status -->
        <div class="sodek-gb-report-card">
            <h3><?php esc_html_e( 'Bookings by Status', 'glowbook' ); ?></h3>
            <div class="sodek-gb-status-breakdown">
                <?php foreach ( $report_data['status_breakdown'] as $status => $count ) : ?>
                    <div class="sodek-gb-status-row">
                        <span class="sodek-gb-status sodek-gb-status-<?php echo esc_attr( $status ); ?>">
                            <?php echo esc_html( ucfirst( str_replace( '-', ' ', $status ) ) ); ?>
                        </span>
                        <span class="sodek-gb-status-count"><?php echo esc_html( $count ); ?></span>
                        <div class="sodek-gb-status-bar">
                            <div class="sodek-gb-status-fill sodek-gb-fill-<?php echo esc_attr( $status ); ?>"
                                style="width: <?php echo $report_data['total_bookings'] > 0 ? ( $count / $report_data['total_bookings'] * 100 ) : 0; ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Popular Services -->
        <div class="sodek-gb-report-card">
            <h3><?php esc_html_e( 'Popular Services', 'glowbook' ); ?></h3>
            <?php if ( ! empty( $report_data['popular_services'] ) ) : ?>
                <table class="sodek-gb-report-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Service', 'glowbook' ); ?></th>
                            <th><?php esc_html_e( 'Bookings', 'glowbook' ); ?></th>
                            <th><?php esc_html_e( 'Revenue', 'glowbook' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $report_data['popular_services'] as $service ) : ?>
                            <tr>
                                <td><?php echo esc_html( $service['name'] ); ?></td>
                                <td><?php echo esc_html( $service['count'] ); ?></td>
                                <td><?php echo wc_price( $service['revenue'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="sodek-gb-no-data"><?php esc_html_e( 'No data available.', 'glowbook' ); ?></p>
            <?php endif; ?>
        </div>

        <!-- Bookings Over Time Chart -->
        <div class="sodek-gb-report-card sodek-gb-report-wide">
            <h3><?php esc_html_e( 'Bookings Over Time', 'glowbook' ); ?></h3>
            <div class="sodek-gb-chart-container">
                <canvas id="sodek-gb-bookings-chart"></canvas>
            </div>
        </div>

        <!-- Revenue Over Time -->
        <div class="sodek-gb-report-card sodek-gb-report-wide">
            <h3><?php esc_html_e( 'Revenue Over Time', 'glowbook' ); ?></h3>
            <div class="sodek-gb-chart-container">
                <canvas id="sodek-gb-revenue-chart"></canvas>
            </div>
        </div>

        <!-- Busiest Days -->
        <div class="sodek-gb-report-card">
            <h3><?php esc_html_e( 'Busiest Days of Week', 'glowbook' ); ?></h3>
            <div class="sodek-gb-days-chart">
                <?php
                $max_day_count = max( array_values( $report_data['bookings_by_day'] ) ) ?: 1;
                $day_names = array(
                    0 => __( 'Sun', 'glowbook' ),
                    1 => __( 'Mon', 'glowbook' ),
                    2 => __( 'Tue', 'glowbook' ),
                    3 => __( 'Wed', 'glowbook' ),
                    4 => __( 'Thu', 'glowbook' ),
                    5 => __( 'Fri', 'glowbook' ),
                    6 => __( 'Sat', 'glowbook' ),
                );
                ?>
                <?php for ( $i = 0; $i <= 6; $i++ ) : ?>
                    <div class="sodek-gb-day-bar">
                        <div class="sodek-gb-day-fill" style="height: <?php echo ( $report_data['bookings_by_day'][ $i ] / $max_day_count * 100 ); ?>%;"></div>
                        <span class="sodek-gb-day-count"><?php echo esc_html( $report_data['bookings_by_day'][ $i ] ); ?></span>
                        <span class="sodek-gb-day-name"><?php echo esc_html( $day_names[ $i ] ); ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Peak Hours -->
        <div class="sodek-gb-report-card">
            <h3><?php esc_html_e( 'Peak Booking Hours', 'glowbook' ); ?></h3>
            <?php if ( ! empty( $report_data['peak_hours'] ) ) : ?>
                <table class="sodek-gb-report-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Hour', 'glowbook' ); ?></th>
                            <th><?php esc_html_e( 'Bookings', 'glowbook' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( array_slice( $report_data['peak_hours'], 0, 5 ) as $hour => $count ) : ?>
                            <tr>
                                <td><?php echo esc_html( date_i18n( 'g A', strtotime( $hour . ':00' ) ) ); ?></td>
                                <td><?php echo esc_html( $count ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="sodek-gb-no-data"><?php esc_html_e( 'No data available.', 'glowbook' ); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Range selector
    document.getElementById('sodek-gb-range-select').addEventListener('change', function() {
        var customDates = document.getElementById('sodek-gb-custom-dates');
        if (this.value === 'custom') {
            customDates.style.display = 'inline';
        } else {
            customDates.style.display = 'none';
        }
    });

    // Bookings Chart
    var bookingsCtx = document.getElementById('sodek-gb-bookings-chart');
    if (bookingsCtx) {
        new Chart(bookingsCtx, {
            type: 'line',
            data: {
                labels: <?php echo wp_json_encode( array_keys( $report_data['daily_bookings'] ) ); ?>,
                datasets: [{
                    label: '<?php echo esc_js( __( 'Bookings', 'glowbook' ) ); ?>',
                    data: <?php echo wp_json_encode( array_values( $report_data['daily_bookings'] ) ); ?>,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Revenue Chart
    var revenueCtx = document.getElementById('sodek-gb-revenue-chart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: <?php echo wp_json_encode( array_keys( $report_data['daily_revenue'] ) ); ?>,
                datasets: [{
                    label: '<?php echo esc_js( __( 'Revenue', 'glowbook' ) ); ?>',
                    data: <?php echo wp_json_encode( array_values( $report_data['daily_revenue'] ) ); ?>,
                    backgroundColor: '#5cb85c'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>

<style>
.sodek-gb-reports-wrap {
    max-width: 1400px;
}

.sodek-gb-reports-wrap .sodek-gb-admin-hero,
.sodek-gb-reports-filter,
.sodek-gb-report-card,
.sodek-gb-stat-box {
    background: #fff;
    border: 1px solid #dde3ea;
    border-radius: 22px;
    box-shadow: 0 18px 36px rgba(16, 24, 40, 0.05);
}

.sodek-gb-reports-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(260px, .38fr);
    gap: 20px;
    padding: 28px 30px;
    margin: 18px 0 20px;
    background: linear-gradient(135deg, #fffaf5 0%, #f7efe6 100%);
    border-color: #eadfce;
}

.sodek-gb-admin-kicker {
    display: inline-flex;
    margin-bottom: 10px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: #8a5a21;
}

.sodek-gb-reports-hero h1 {
    margin: 0 0 8px;
    font-size: 34px;
    line-height: 1.08;
}

.sodek-gb-reports-hero p {
    margin: 0;
    color: #667085;
    line-height: 1.7;
}

.sodek-gb-admin-hero-note {
    display: grid;
    gap: 8px;
    align-self: end;
    padding: 18px 20px;
    background: rgba(255,255,255,.84);
    border: 1px solid rgba(182,120,49,.16);
    border-radius: 18px;
}

.sodek-gb-reports-filter {
    padding: 22px 24px;
    margin-bottom: 20px;
}

.sodek-gb-reports-filter form {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.sodek-gb-date-range-label {
    margin-top: 10px;
    color: #646970;
}

.sodek-gb-reports-stats {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 16px;
}

.sodek-gb-reports-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 20px;
}

.sodek-gb-report-card {
    padding: 22px 24px;
}

.sodek-gb-report-card h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #f0f0f1;
    font-size: 22px;
}

.sodek-gb-report-wide {
    grid-column: span 2;
}

.sodek-gb-chart-container {
    height: 250px;
}

.sodek-gb-status-breakdown {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.sodek-gb-status-row {
    display: grid;
    grid-template-columns: 100px 50px 1fr;
    align-items: center;
    gap: 10px;
}

.sodek-gb-status-bar {
    height: 20px;
    background: #f0f0f1;
    border-radius: 3px;
    overflow: hidden;
}

.sodek-gb-status-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.sodek-gb-fill-pending { background: #f0ad4e; }
.sodek-gb-fill-confirmed { background: #5cb85c; }
.sodek-gb-fill-completed { background: #5bc0de; }
.sodek-gb-fill-cancelled { background: #d9534f; }
.sodek-gb-fill-no-show { background: #777; }

.sodek-gb-report-table {
    width: 100%;
    border-collapse: collapse;
}

.sodek-gb-report-table th,
.sodek-gb-report-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #f0f0f1;
}

.sodek-gb-report-table th {
    font-weight: 600;
    color: #646970;
}

.sodek-gb-reports-stats .sodek-gb-stat-box {
    padding: 22px 18px;
}

.sodek-gb-reports-stats .sodek-gb-stat-label {
    margin-top: 8px;
    display: block;
}

.sodek-gb-days-chart {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    height: 150px;
    padding-top: 20px;
}

.sodek-gb-day-bar {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    height: 100%;
    position: relative;
}

.sodek-gb-day-fill {
    width: 30px;
    background: #2271b1;
    border-radius: 3px 3px 0 0;
    position: absolute;
    bottom: 40px;
    transition: height 0.3s ease;
}

.sodek-gb-day-count {
    position: absolute;
    bottom: 20px;
    font-size: 12px;
    font-weight: 600;
}

.sodek-gb-day-name {
    position: absolute;
    bottom: 0;
    font-size: 12px;
    color: #646970;
}

.sodek-gb-no-data {
    color: #646970;
    text-align: center;
    padding: 20px;
    font-style: italic;
}

@media screen and (max-width: 1200px) {
    .sodek-gb-reports-hero,
    .sodek-gb-reports-stats {
        grid-template-columns: 1fr;
    }

    .sodek-gb-reports-grid {
        grid-template-columns: 1fr;
    }

    .sodek-gb-report-wide {
        grid-column: span 1;
    }
}
</style>

<?php
/**
 * Get report data.
 *
 * @param string $start_date Start date.
 * @param string $end_date   End date.
 * @return array
 */
function sodek_gb_get_report_data( $start_date, $end_date ) {
    global $wpdb;

    $data = array(
        'total_bookings'    => 0,
        'total_deposits'    => 0,
        'total_revenue'     => 0,
        'completion_rate'   => 0,
        'cancellation_rate' => 0,
        'status_breakdown'  => array(
            'pending'   => 0,
            'confirmed' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'no-show'   => 0,
        ),
        'popular_services'  => array(),
        'daily_bookings'    => array(),
        'daily_revenue'     => array(),
        'bookings_by_day'   => array_fill( 0, 7, 0 ),
        'peak_hours'        => array(),
    );

    // Get all bookings in date range
    $args = array(
        'post_type'      => 'sodek_gb_booking',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_sodek_gb_booking_date',
                'value'   => array( $start_date, $end_date ),
                'compare' => 'BETWEEN',
                'type'    => 'DATE',
            ),
        ),
    );

    $bookings = get_posts( $args );
    $data['total_bookings'] = count( $bookings );

    $service_stats = array();
    $completed_count = 0;
    $cancelled_count = 0;

    // Initialize daily data
    $current = strtotime( $start_date );
    $end = strtotime( $end_date );
    while ( $current <= $end ) {
        $date_key = date( 'M j', $current );
        $data['daily_bookings'][ $date_key ] = 0;
        $data['daily_revenue'][ $date_key ] = 0;
        $current = strtotime( '+1 day', $current );
    }

    // Process bookings
    foreach ( $bookings as $booking_post ) {
        $booking = Sodek_GB_Booking::get_booking( $booking_post->ID );

        if ( ! $booking ) {
            continue;
        }

        $status = $booking['status'];
        $deposit = floatval( $booking['deposit_amount'] );
        $total_price = floatval( $booking['total_price'] ?? 0 );
        $balance = max( 0, $total_price - $deposit );
        $service_id = $booking['service']['id'] ?? 0;
        $service_name = $booking['service']['title'] ?? __( 'Unknown', 'glowbook' );
        $booking_date = sanitize_text_field( $booking['booking_date'] ?? '' );
        $start_time = sanitize_text_field( $booking['start_time'] ?? '' );

        // Status breakdown
        if ( isset( $data['status_breakdown'][ $status ] ) ) {
            $data['status_breakdown'][ $status ]++;
        }

        // Completion/cancellation counts
        if ( 'completed' === $status ) {
            $completed_count++;
            $data['total_deposits'] += $deposit;
            $data['total_revenue'] += $deposit + $balance;
        } elseif ( 'cancelled' === $status ) {
            $cancelled_count++;
        } elseif ( in_array( $status, array( 'pending', 'confirmed' ), true ) ) {
            $data['total_deposits'] += $deposit;
        }

        // Service stats
        if ( $service_id ) {
            if ( ! isset( $service_stats[ $service_id ] ) ) {
                $service_stats[ $service_id ] = array(
                    'name'    => $service_name,
                    'count'   => 0,
                    'revenue' => 0,
                );
            }
            $service_stats[ $service_id ]['count']++;
            if ( 'completed' === $status ) {
                $service_stats[ $service_id ]['revenue'] += $deposit + $balance;
            }
        }

        // Daily bookings
        if ( ! empty( $booking_date ) ) {
            $booking_timestamp = strtotime( $booking_date );
            if ( false !== $booking_timestamp ) {
                $date_key = date( 'M j', $booking_timestamp );
                if ( isset( $data['daily_bookings'][ $date_key ] ) ) {
                    $data['daily_bookings'][ $date_key ]++;
                    if ( 'completed' === $status ) {
                        $data['daily_revenue'][ $date_key ] += $deposit + $balance;
                    }
                }

                // Bookings by day of week
                $day_of_week = (int) date( 'w', $booking_timestamp );
                $data['bookings_by_day'][ $day_of_week ]++;
            }
        }

        // Peak hours
        if ( ! empty( $start_time ) ) {
            $hour_timestamp = strtotime( $start_time );
            if ( false !== $hour_timestamp ) {
                $hour = (int) date( 'G', $hour_timestamp );
                if ( ! isset( $data['peak_hours'][ $hour ] ) ) {
                    $data['peak_hours'][ $hour ] = 0;
                }
                $data['peak_hours'][ $hour ]++;
            }
        }
    }

    // Calculate rates
    if ( $data['total_bookings'] > 0 ) {
        $data['completion_rate'] = round( ( $completed_count / $data['total_bookings'] ) * 100 );
        $data['cancellation_rate'] = round( ( $cancelled_count / $data['total_bookings'] ) * 100 );
    }

    // Sort and limit popular services
    usort( $service_stats, function( $a, $b ) {
        return $b['count'] - $a['count'];
    } );
    $data['popular_services'] = array_slice( array_values( $service_stats ), 0, 5 );

    // Sort peak hours
    arsort( $data['peak_hours'] );

    return $data;
}
