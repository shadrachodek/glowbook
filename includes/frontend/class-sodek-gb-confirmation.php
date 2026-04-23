<?php
/**
 * Booking Confirmation Page.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Confirmation class.
 */
class Sodek_GB_Confirmation {

    /**
     * Initialize.
     */
    public static function init() {
        // Enhance WooCommerce thank you page
        add_action( 'woocommerce_thankyou', array( __CLASS__, 'display_booking_confirmation' ), 15 );

        // Add ICS download endpoint
        add_action( 'init', array( __CLASS__, 'add_ics_endpoint' ) );
        add_action( 'template_redirect', array( __CLASS__, 'handle_ics_download' ) );

        // Shortcode for standalone confirmation page
        add_shortcode( 'sodek_gb_booking_confirmation', array( __CLASS__, 'shortcode_confirmation' ) );
    }

    /**
     * Add ICS download endpoint.
     */
    public static function add_ics_endpoint() {
        add_rewrite_endpoint( 'booking-calendar', EP_ROOT );
    }

    /**
     * Display booking confirmation on thank you page.
     *
     * @param int $order_id Order ID.
     */
    public static function display_booking_confirmation( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $booking_ids = $order->get_meta( '_sodek_gb_booking_ids' );

        if ( empty( $booking_ids ) ) {
            return;
        }

        foreach ( $booking_ids as $booking_id ) {
            $booking = Sodek_GB_Booking::get_booking( $booking_id );

            if ( ! $booking ) {
                continue;
            }

            self::render_confirmation_card( $booking );
        }
    }

    /**
     * Render booking confirmation card.
     *
     * @param array $booking Booking data.
     */
    private static function render_confirmation_card( $booking ) {
        $service = $booking['service'] ?? null;
        $ics_url = self::get_ics_url( $booking['id'] );
        $google_url = self::get_google_calendar_url( $booking );
        $cancellation_policy = self::get_cancellation_policy_text();
        ?>
        <div class="sodek-gb-confirmation-card">
            <div class="sodek-gb-confirmation-header">
                <span class="sodek-gb-confirmation-icon">&#10003;</span>
                <h2><?php esc_html_e( 'Your Appointment is Confirmed!', 'glowbook' ); ?></h2>
                <p class="sodek-gb-confirmation-id">
                    <?php
                    printf(
                        /* translators: %s: booking ID */
                        esc_html__( 'Booking #%s', 'glowbook' ),
                        esc_html( $booking['id'] )
                    );
                    ?>
                </p>
            </div>

            <div class="sodek-gb-confirmation-details">
                <div class="sodek-gb-confirmation-row">
                    <div class="sodek-gb-confirmation-label"><?php esc_html_e( 'Service', 'glowbook' ); ?></div>
                    <div class="sodek-gb-confirmation-value"><?php echo esc_html( $service['title'] ?? __( 'Service', 'glowbook' ) ); ?></div>
                </div>

                <div class="sodek-gb-confirmation-row">
                    <div class="sodek-gb-confirmation-label"><?php esc_html_e( 'Date', 'glowbook' ); ?></div>
                    <div class="sodek-gb-confirmation-value">
                        <?php
                        $booking_date = $booking['booking_date'] ?? $booking['date'] ?? gmdate( 'Y-m-d' );
                        echo esc_html(
                            date_i18n(
                                'l, F j, Y',
                                strtotime( $booking_date )
                            )
                        );
                        ?>
                    </div>
                </div>

                <div class="sodek-gb-confirmation-row">
                    <div class="sodek-gb-confirmation-label"><?php esc_html_e( 'Time', 'glowbook' ); ?></div>
                    <div class="sodek-gb-confirmation-value">
                        <?php
                        echo esc_html(
                            date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) )
                            . ' - '
                            . date_i18n( get_option( 'time_format' ), strtotime( $booking['end_time'] ) )
                        );
                        ?>
                    </div>
                </div>

                <div class="sodek-gb-confirmation-row">
                    <div class="sodek-gb-confirmation-label"><?php esc_html_e( 'Duration', 'glowbook' ); ?></div>
                    <div class="sodek-gb-confirmation-value">
                        <?php
                        printf(
                            /* translators: %d: duration in minutes */
                            esc_html__( '%d minutes', 'glowbook' ),
                            $service['duration'] ?? 60
                        );
                        ?>
                    </div>
                </div>

                <hr class="sodek-gb-confirmation-divider">

                <div class="sodek-gb-confirmation-row sodek-gb-confirmation-payment">
                    <div class="sodek-gb-confirmation-label"><?php esc_html_e( 'Deposit Paid', 'glowbook' ); ?></div>
                    <div class="sodek-gb-confirmation-value"><?php echo wc_price( $booking['deposit_amount'] ); ?></div>
                </div>

                <?php if ( $booking['balance_amount'] > 0 ) : ?>
                    <div class="sodek-gb-confirmation-row sodek-gb-confirmation-balance">
                        <div class="sodek-gb-confirmation-label"><?php esc_html_e( 'Balance Due at Appointment', 'glowbook' ); ?></div>
                        <div class="sodek-gb-confirmation-value sodek-gb-balance-due"><?php echo wc_price( $booking['balance_amount'] ); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="sodek-gb-confirmation-actions">
                <h4><?php esc_html_e( 'Add to Your Calendar', 'glowbook' ); ?></h4>
                <div class="sodek-gb-calendar-buttons">
                    <a href="<?php echo esc_url( $ics_url ); ?>" class="sodek-gb-calendar-btn sodek-gb-btn-ics" download>
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e( 'Download .ics', 'glowbook' ); ?>
                    </a>
                    <a href="<?php echo esc_url( $google_url ); ?>" class="sodek-gb-calendar-btn sodek-gb-btn-google" target="_blank" rel="noopener">
                        <span class="sodek-gb-google-icon"></span>
                        <?php esc_html_e( 'Google Calendar', 'glowbook' ); ?>
                    </a>
                </div>
            </div>

            <?php if ( $cancellation_policy ) : ?>
                <div class="sodek-gb-confirmation-policy">
                    <h4><?php esc_html_e( 'Cancellation Policy', 'glowbook' ); ?></h4>
                    <p><?php echo wp_kses_post( $cancellation_policy ); ?></p>
                </div>
            <?php endif; ?>

            <div class="sodek-gb-confirmation-footer">
                <p>
                    <?php esc_html_e( 'A confirmation email has been sent to your email address.', 'glowbook' ); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url( wc_get_account_endpoint_url( 'bookings' ) ); ?>">
                        <?php esc_html_e( 'View My Bookings', 'glowbook' ); ?>
                    </a>
                </p>
            </div>
        </div>

        <style>
        .sodek-gb-confirmation-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin: 30px 0;
            overflow: hidden;
        }

        .sodek-gb-confirmation-header {
            background: linear-gradient(135deg, #5cb85c 0%, #449d44 100%);
            color: #fff;
            padding: 30px;
            text-align: center;
        }

        .sodek-gb-confirmation-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            font-size: 30px;
            margin-bottom: 15px;
        }

        .sodek-gb-confirmation-header h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
            color: #fff;
        }

        .sodek-gb-confirmation-id {
            margin: 0;
            opacity: 0.9;
        }

        .sodek-gb-confirmation-details {
            padding: 25px 30px;
        }

        .sodek-gb-confirmation-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .sodek-gb-confirmation-row:last-child {
            border-bottom: none;
        }

        .sodek-gb-confirmation-label {
            color: #666;
            font-weight: 500;
        }

        .sodek-gb-confirmation-value {
            font-weight: 600;
            text-align: right;
        }

        .sodek-gb-confirmation-divider {
            margin: 15px 0;
            border: none;
            border-top: 2px dashed #e0e0e0;
        }

        .sodek-gb-balance-due {
            color: #d9534f;
            font-size: 18px;
        }

        .sodek-gb-confirmation-actions {
            background: #f9f9f9;
            padding: 25px 30px;
            text-align: center;
        }

        .sodek-gb-confirmation-actions h4 {
            margin: 0 0 15px 0;
            font-size: 16px;
        }

        .sodek-gb-calendar-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .sodek-gb-calendar-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .sodek-gb-btn-ics {
            background: #2271b1;
            color: #fff;
        }

        .sodek-gb-btn-ics:hover {
            background: #135e96;
            color: #fff;
        }

        .sodek-gb-btn-google {
            background: #fff;
            color: #333;
            border: 1px solid #ddd;
        }

        .sodek-gb-btn-google:hover {
            background: #f5f5f5;
            color: #333;
        }

        .sodek-gb-google-icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%234285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="%2334A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="%23FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="%23EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>') no-repeat center;
            background-size: contain;
        }

        .sodek-gb-confirmation-policy {
            padding: 20px 30px;
            border-top: 1px solid #e0e0e0;
            background: #fff8e5;
        }

        .sodek-gb-confirmation-policy h4 {
            margin: 0 0 10px 0;
            color: #856404;
        }

        .sodek-gb-confirmation-policy p {
            margin: 0;
            color: #856404;
            font-size: 14px;
        }

        .sodek-gb-confirmation-footer {
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }

        .sodek-gb-confirmation-footer p {
            margin: 5px 0;
        }

        @media (max-width: 600px) {
            .sodek-gb-confirmation-row {
                flex-direction: column;
                gap: 5px;
            }

            .sodek-gb-confirmation-value {
                text-align: left;
            }

            .sodek-gb-calendar-buttons {
                flex-direction: column;
            }

            .sodek-gb-calendar-btn {
                justify-content: center;
            }
        }
        </style>
        <?php
    }

    /**
     * Get ICS download URL.
     *
     * @param int $booking_id Booking ID.
     * @return string
     */
    public static function get_ics_url( $booking_id ) {
        $token = self::generate_ics_token( $booking_id );
        return add_query_arg(
            array(
                'sodek_gb_ics'   => $booking_id,
                'token'     => $token,
            ),
            home_url( '/' )
        );
    }

    /**
     * Generate ICS token for security.
     *
     * @param int $booking_id Booking ID.
     * @return string
     */
    private static function generate_ics_token( $booking_id ) {
        $booking = Sodek_GB_Booking::get_booking( $booking_id );
        if ( ! $booking ) {
            return '';
        }

        $booking_date = $booking['booking_date'] ?? $booking['date'] ?? '';
        return wp_hash( $booking_id . $booking['customer_email'] . $booking_date );
    }

    /**
     * Handle ICS file download.
     */
    public static function handle_ics_download() {
        if ( ! isset( $_GET['sodek_gb_ics'] ) || ! isset( $_GET['token'] ) ) {
            return;
        }

        $booking_id = absint( $_GET['sodek_gb_ics'] );
        $token = sanitize_text_field( $_GET['token'] );

        // Verify token
        $expected_token = self::generate_ics_token( $booking_id );
        if ( ! hash_equals( $expected_token, $token ) ) {
            wp_die( esc_html__( 'Invalid request.', 'glowbook' ) );
        }

        $booking = Sodek_GB_Booking::get_booking( $booking_id );
        if ( ! $booking ) {
            wp_die( esc_html__( 'Booking not found.', 'glowbook' ) );
        }

        $ics_content = self::generate_ics( $booking );

        header( 'Content-Type: text/calendar; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="appointment-' . $booking_id . '.ics"' );
        header( 'Cache-Control: no-cache, must-revalidate' );

        echo $ics_content;
        exit;
    }

    /**
     * Generate ICS file content.
     *
     * @param array $booking Booking data.
     * @return string
     */
    private static function generate_ics( $booking ) {
        $service = $booking['service'] ?? null;
        $site_name = get_bloginfo( 'name' );
        $site_url = home_url();

        // Support both 'date' and 'booking_date' keys
        $booking_date = $booking['booking_date'] ?? $booking['date'] ?? gmdate( 'Y-m-d' );
        $start_time = $booking['start_time'] ?? '09:00';
        $end_time = $booking['end_time'] ?? '10:00';

        $dtstart = gmdate( 'Ymd\THis\Z', strtotime( $booking_date . ' ' . $start_time ) );
        $dtend = gmdate( 'Ymd\THis\Z', strtotime( $booking_date . ' ' . $end_time ) );
        $dtstamp = gmdate( 'Ymd\THis\Z' );
        $uid = 'sodek-gb-' . $booking['id'] . '@' . wp_parse_url( $site_url, PHP_URL_HOST );

        $summary = $service['title'] ?? __( 'Appointment', 'glowbook' );
        $summary .= ' - ' . $site_name;

        // Calculate balance if not provided
        $deposit_amount = $booking['deposit_amount'] ?? 0;
        $total_price = $booking['total_price'] ?? $deposit_amount;
        $balance_amount = $booking['balance_amount'] ?? ( $total_price - $deposit_amount );

        // Format prices (use wc_price if available, otherwise simple format)
        $deposit_formatted = function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $deposit_amount ) ) : '$' . number_format( $deposit_amount, 2 );
        $balance_formatted = function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $balance_amount ) ) : '$' . number_format( $balance_amount, 2 );

        $description = sprintf(
            "%s\\n\\n%s: %s\\n%s: %s",
            __( 'Your appointment details', 'glowbook' ),
            __( 'Deposit Paid', 'glowbook' ),
            $deposit_formatted,
            __( 'Balance Due', 'glowbook' ),
            $balance_formatted
        );

        if ( ! empty( $booking['notes'] ) ) {
            $description .= "\\n\\n" . __( 'Notes', 'glowbook' ) . ": " . str_replace( array( "\r\n", "\n", "\r" ), "\\n", $booking['notes'] );
        }

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//GlowBook//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:" . $uid . "\r\n";
        $ics .= "DTSTAMP:" . $dtstamp . "\r\n";
        $ics .= "DTSTART:" . $dtstart . "\r\n";
        $ics .= "DTEND:" . $dtend . "\r\n";
        $ics .= "SUMMARY:" . self::ics_escape( $summary ) . "\r\n";
        $ics .= "DESCRIPTION:" . self::ics_escape( $description ) . "\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "BEGIN:VALARM\r\n";
        $ics .= "TRIGGER:-PT1H\r\n";
        $ics .= "ACTION:DISPLAY\r\n";
        $ics .= "DESCRIPTION:Reminder: " . self::ics_escape( $summary ) . "\r\n";
        $ics .= "END:VALARM\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Escape string for ICS format.
     *
     * @param string $string String to escape.
     * @return string
     */
    private static function ics_escape( $string ) {
        return preg_replace( '/([\,;])/', '\\\$1', $string );
    }

    /**
     * Get Google Calendar add event URL.
     *
     * @param array $booking Booking data.
     * @return string
     */
    public static function get_google_calendar_url( $booking ) {
        $service = $booking['service'] ?? null;
        $site_name = get_bloginfo( 'name' );

        // Support both 'date' and 'booking_date' keys
        $booking_date = $booking['booking_date'] ?? $booking['date'] ?? gmdate( 'Y-m-d' );
        $start_time = $booking['start_time'] ?? '09:00';
        $end_time = $booking['end_time'] ?? '10:00';

        $start = gmdate( 'Ymd\THis\Z', strtotime( $booking_date . ' ' . $start_time ) );
        $end = gmdate( 'Ymd\THis\Z', strtotime( $booking_date . ' ' . $end_time ) );

        $title = ( $service['title'] ?? __( 'Appointment', 'glowbook' ) ) . ' - ' . $site_name;

        // Calculate balance if not provided
        $deposit_amount = $booking['deposit_amount'] ?? 0;
        $total_price = $booking['total_price'] ?? $deposit_amount;
        $balance_amount = $booking['balance_amount'] ?? ( $total_price - $deposit_amount );

        // Format prices (use wc_price if available, otherwise simple format)
        $deposit_formatted = function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $deposit_amount ) ) : '$' . number_format( $deposit_amount, 2 );
        $balance_formatted = function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $balance_amount ) ) : '$' . number_format( $balance_amount, 2 );

        $details = sprintf(
            "%s\n\n%s: %s\n%s: %s",
            __( 'Your appointment details', 'glowbook' ),
            __( 'Deposit Paid', 'glowbook' ),
            $deposit_formatted,
            __( 'Balance Due', 'glowbook' ),
            $balance_formatted
        );

        $params = array(
            'action'  => 'TEMPLATE',
            'text'    => $title,
            'dates'   => $start . '/' . $end,
            'details' => $details,
            'sf'      => 'true',
        );

        return 'https://calendar.google.com/calendar/render?' . http_build_query( $params );
    }

    /**
     * Get cancellation policy text.
     *
     * @return string
     */
    private static function get_cancellation_policy_text() {
        $custom_text = get_option( 'sodek_gb_cancellation_policy_text' );

        if ( $custom_text ) {
            return $custom_text;
        }

        // Auto-generate from settings
        $notice_hours = (int) get_option( 'sodek_gb_cancellation_notice', 24 );
        $refund_policy = get_option( 'sodek_gb_cancellation_refund_policy', 'full' );

        $text = sprintf(
            /* translators: %d: number of hours */
            __( 'Cancellations must be made at least %d hours before your appointment.', 'glowbook' ),
            $notice_hours
        );

        switch ( $refund_policy ) {
            case 'full':
                $text .= ' ' . __( 'Full deposit refund for cancellations within this period.', 'glowbook' );
                break;
            case 'partial':
                $percent = (int) get_option( 'sodek_gb_partial_refund_percent', 50 );
                $text .= ' ' . sprintf(
                    /* translators: %d: refund percentage */
                    __( '%d%% of your deposit will be refunded for cancellations within this period.', 'glowbook' ),
                    $percent
                );
                break;
            case 'credit':
                $text .= ' ' . __( 'Store credit will be issued for cancellations within this period.', 'glowbook' );
                break;
            default:
                $text .= ' ' . __( 'Deposits are non-refundable.', 'glowbook' );
        }

        return $text;
    }

    /**
     * Shortcode for standalone booking confirmation.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function shortcode_confirmation( $atts ) {
        $atts = shortcode_atts(
            array(
                'booking_id' => 0,
            ),
            $atts
        );

        $booking_id = absint( $atts['booking_id'] );

        if ( ! $booking_id && isset( $_GET['booking'] ) ) {
            $booking_id = absint( $_GET['booking'] );
        }

        if ( ! $booking_id ) {
            return '<p>' . esc_html__( 'No booking specified.', 'glowbook' ) . '</p>';
        }

        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return '<p>' . esc_html__( 'Booking not found.', 'glowbook' ) . '</p>';
        }

        // Verify user has access
        $user = wp_get_current_user();
        if ( $user->ID && $booking['customer_email'] !== $user->user_email ) {
            return '<p>' . esc_html__( 'You do not have permission to view this booking.', 'glowbook' ) . '</p>';
        }

        ob_start();
        self::render_confirmation_card( $booking );
        return ob_get_clean();
    }
}
