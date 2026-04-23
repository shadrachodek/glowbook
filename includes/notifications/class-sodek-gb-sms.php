<?php
/**
 * SMS Notification Orchestrator.
 *
 * Handles SMS notifications for bookings and customer communications.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_SMS class.
 */
class Sodek_GB_SMS {

    /**
     * Twilio instance.
     *
     * @var Sodek_GB_Twilio
     */
    private static $twilio;

    /**
     * Initialize SMS system.
     */
    public static function init() {
        // Only initialize if SMS is enabled
        if ( ! get_option( 'sodek_gb_sms_enabled', false ) ) {
            return;
        }

        self::$twilio = new Sodek_GB_Twilio();

        // Hook into booking events
        add_action( 'sodek_gb_booking_created', array( __CLASS__, 'send_booking_confirmation' ), 10, 2 );
        add_action( 'sodek_gb_booking_confirmed', array( __CLASS__, 'send_booking_confirmation' ), 10, 2 );
        add_action( 'sodek_gb_booking_rescheduled', array( __CLASS__, 'send_reschedule_notification' ), 10, 5 );
        add_action( 'sodek_gb_booking_cancelled', array( __CLASS__, 'send_cancellation_notification' ), 10, 3 );

        // Reminder cron
        add_action( 'sodek_gb_send_reminders', array( __CLASS__, 'process_reminders' ) );

        // Schedule reminder cron if not already scheduled
        if ( ! wp_next_scheduled( 'sodek_gb_send_reminders' ) ) {
            wp_schedule_event( time(), 'hourly', 'sodek_gb_send_reminders' );
        }

        // Webhook handler
        add_action( 'wp_ajax_nopriv_sodek_gb_twilio_webhook', array( __CLASS__, 'handle_webhook' ) );
    }

    /**
     * Check if SMS is available.
     *
     * @return bool
     */
    public static function is_available() {
        if ( ! self::$twilio ) {
            self::$twilio = new Sodek_GB_Twilio();
        }

        return self::$twilio->is_configured();
    }

    /**
     * Send booking confirmation SMS.
     *
     * @param int   $booking_id Booking ID.
     * @param array $booking    Booking data.
     */
    public static function send_booking_confirmation( $booking_id, $booking = null ) {
        if ( ! self::is_available() ) {
            return;
        }

        if ( ! is_array( $booking ) ) {
            $booking = Sodek_GB_Booking::get_booking( $booking_id );
        }

        if ( ! $booking ) {
            return;
        }

        // Check customer SMS opt-in
        $customer = self::get_booking_customer( $booking_id );
        if ( ! $customer || ! self::customer_opted_in( $customer ) ) {
            return;
        }

        // Check if confirmation SMS is enabled
        if ( ! get_option( 'sodek_gb_sms_confirmation', true ) ) {
            return;
        }

        $phone = $customer['phone'] ?? '';
        if ( empty( $phone ) ) {
            return;
        }

        $result = self::$twilio->send_booking_confirmation( $phone, $booking );

        // Log result
        self::log_notification( $booking_id, 'confirmation', $result );

        do_action( 'sodek_gb_sms_sent', 'confirmation', $booking_id, $result );
    }

    /**
     * Send reschedule notification SMS.
     *
     * @param int    $booking_id  Booking ID.
     * @param array  $old_booking Old booking data.
     * @param string $new_date    New date.
     * @param string $new_time    New time.
     */
    public static function send_reschedule_notification( $booking_id, $old_booking = null, $new_date = '', $new_time = '', $unused = null ) {
        if ( ! self::is_available() ) {
            return;
        }

        $customer = self::get_booking_customer( $booking_id );
        if ( ! $customer || ! self::customer_opted_in( $customer ) ) {
            return;
        }

        if ( ! get_option( 'sodek_gb_sms_reschedule', true ) ) {
            return;
        }

        $phone = $customer['phone'] ?? '';
        if ( empty( $phone ) ) {
            return;
        }

        $booking = Sodek_GB_Booking::get_booking( $booking_id );
        if ( ! $booking ) {
            return;
        }

        if ( '' === $new_date ) {
            $new_date = $booking['booking_date'] ?? '';
        }

        if ( '' === $new_time ) {
            $new_time = $booking['start_time'] ?? '';
        }

        $result = self::$twilio->send_reschedule( $phone, $booking, $new_date, $new_time );

        self::log_notification( $booking_id, 'reschedule', $result );

        do_action( 'sodek_gb_sms_sent', 'reschedule', $booking_id, $result );
    }

    /**
     * Send cancellation notification SMS.
     *
     * @param int    $booking_id   Booking ID.
     * @param array  $booking      Booking data.
     * @param string $cancelled_by Who cancelled (customer, admin).
     */
    public static function send_cancellation_notification( $booking_id, $booking = null, $cancelled_by = 'customer' ) {
        if ( ! self::is_available() ) {
            return;
        }

        if ( ! is_array( $booking ) ) {
            $booking = Sodek_GB_Booking::get_booking( $booking_id );
        }

        if ( ! $booking ) {
            return;
        }

        $customer = self::get_booking_customer( $booking_id );
        if ( ! $customer || ! self::customer_opted_in( $customer ) ) {
            return;
        }

        if ( ! get_option( 'sodek_gb_sms_cancellation', true ) ) {
            return;
        }

        $phone = $customer['phone'] ?? '';
        if ( empty( $phone ) ) {
            return;
        }

        $result = self::$twilio->send_cancellation( $phone, $booking );

        self::log_notification( $booking_id, 'cancellation', $result );

        do_action( 'sodek_gb_sms_sent', 'cancellation', $booking_id, $result );
    }

    /**
     * Process booking reminders.
     */
    public static function process_reminders() {
        if ( ! self::is_available() ) {
            return;
        }

        // Get reminder timing settings
        $reminder_hours = get_option( 'sodek_gb_sms_reminder_hours', array( 24, 2 ) );
        if ( ! is_array( $reminder_hours ) ) {
            $reminder_hours = array( 24 );
        }

        foreach ( $reminder_hours as $hours ) {
            self::send_reminders_for_time( (int) $hours );
        }
    }

    /**
     * Send reminders for bookings at a specific time window.
     *
     * @param int $hours Hours before appointment.
     */
    private static function send_reminders_for_time( $hours ) {
        global $wpdb;

        $timezone     = wp_timezone();
        $now          = new DateTimeImmutable( 'now', $timezone );
        $target_start = $now->modify( '+' . (int) $hours . ' hours' )->format( 'Y-m-d H:i:s' );
        $target_end   = $now->modify( '+' . (int) $hours . ' hours +1 hour' )->format( 'Y-m-d H:i:s' );

        // Get bookings in the time window that haven't received this reminder
        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.ID, p.post_title,
                        pm_date.meta_value as booking_date,
                        pm_time.meta_value as start_time
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm_date ON p.ID = pm_date.post_id
                    AND pm_date.meta_key = '_sodek_gb_booking_date'
                INNER JOIN {$wpdb->postmeta} pm_time ON p.ID = pm_time.post_id
                    AND pm_time.meta_key = '_sodek_gb_start_time'
                LEFT JOIN {$wpdb->postmeta} pm_reminder ON p.ID = pm_reminder.post_id
                    AND pm_reminder.meta_key = '_sodek_gb_reminder_%d_sent'
                WHERE p.post_type = 'sodek_gb_booking'
                AND p.post_status IN ('pending', 'confirmed', 'publish')
                AND pm_reminder.meta_value IS NULL
                AND CONCAT(pm_date.meta_value, ' ', pm_time.meta_value) BETWEEN %s AND %s",
                $hours,
                $target_start,
                $target_end
            ),
            ARRAY_A
        );

        if ( empty( $bookings ) ) {
            return;
        }

        foreach ( $bookings as $booking_data ) {
            $booking_id = $booking_data['ID'];
            $booking = Sodek_GB_Booking::get_booking( $booking_id );

            if ( ! $booking ) {
                continue;
            }

            $customer = self::get_booking_customer( $booking_id );
            if ( ! $customer || ! self::customer_opted_in( $customer ) ) {
                continue;
            }

            $phone = $customer['phone'] ?? '';
            if ( empty( $phone ) ) {
                continue;
            }

            $result = self::$twilio->send_booking_reminder( $phone, $booking, $hours );

            // Mark reminder as sent
            update_post_meta( $booking_id, "_sodek_gb_reminder_{$hours}_sent", current_time( 'mysql' ) );

            self::log_notification( $booking_id, "reminder_{$hours}h", $result );

            do_action( 'sodek_gb_sms_sent', 'reminder', $booking_id, $result );
        }
    }

    /**
     * Send verification code.
     *
     * @param string $phone Phone number.
     * @param string $code  Verification code.
     * @return array|WP_Error
     */
    public static function send_verification( $phone, $code ) {
        if ( ! self::is_available() ) {
            return new WP_Error(
                'sms_unavailable',
                __( 'SMS service is not available.', 'glowbook' )
            );
        }

        return self::$twilio->send_verification( $phone, $code );
    }

    /**
     * Send custom SMS.
     *
     * @param string $phone      Phone number.
     * @param string $message    Message content.
     * @param int    $booking_id Optional booking ID.
     * @return array|WP_Error
     */
    public static function send( $phone, $message, $booking_id = null ) {
        if ( ! self::is_available() ) {
            return new WP_Error(
                'sms_unavailable',
                __( 'SMS service is not available.', 'glowbook' )
            );
        }

        $result = self::$twilio->send( $phone, $message );

        if ( $booking_id ) {
            self::log_notification( $booking_id, 'custom', $result );
        }

        return $result;
    }

    /**
     * Get customer for a booking.
     *
     * @param int $booking_id Booking ID.
     * @return array|null
     */
    private static function get_booking_customer( $booking_id ) {
        $customer_id = get_post_meta( $booking_id, '_sodek_gb_customer_id', true );

        if ( $customer_id ) {
            return Sodek_GB_Customer::get( $customer_id );
        }

        // Fallback to booking email/phone
        $email = get_post_meta( $booking_id, '_sodek_gb_customer_email', true );
        $phone = get_post_meta( $booking_id, '_sodek_gb_customer_phone', true );

        if ( $phone ) {
            $customer = Sodek_GB_Customer::get_by_phone( $phone );
            if ( $customer ) {
                return $customer;
            }
        }

        if ( $email ) {
            $customer = Sodek_GB_Customer::get_by_email( $email );
            if ( $customer ) {
                return $customer;
            }
        }

        // Return basic data from booking meta
        return array(
            'phone'      => $phone,
            'email'      => $email,
            'sms_opt_in' => true, // Assume opted in if no customer record
        );
    }

    /**
     * Check if customer has opted in to SMS.
     *
     * @param array $customer Customer data.
     * @return bool
     */
    private static function customer_opted_in( $customer ) {
        return ! empty( $customer['sms_opt_in'] );
    }

    /**
     * Log SMS notification.
     *
     * @param int          $booking_id Booking ID.
     * @param string       $type       Notification type.
     * @param array|WP_Error $result   Send result.
     */
    private static function log_notification( $booking_id, $type, $result ) {
        $log = get_post_meta( $booking_id, '_sodek_gb_sms_log', true ) ?: array();

        $log[] = array(
            'type'   => $type,
            'time'   => current_time( 'mysql' ),
            'status' => is_wp_error( $result ) ? 'failed' : 'sent',
            'sid'    => is_array( $result ) ? ( $result['sid'] ?? null ) : null,
            'error'  => is_wp_error( $result ) ? $result->get_error_message() : null,
        );

        update_post_meta( $booking_id, '_sodek_gb_sms_log', $log );
    }

    /**
     * Handle Twilio webhook.
     */
    public static function handle_webhook() {
        // Verify Twilio signature if configured
        if ( get_option( 'sodek_gb_twilio_verify_webhook', true ) ) {
            if ( ! self::verify_twilio_signature() ) {
                wp_send_json_error( 'Invalid signature', 403 );
                return;
            }
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $data = wp_unslash( $_POST );

        if ( ! self::$twilio ) {
            self::$twilio = new Sodek_GB_Twilio();
        }

        $result = self::$twilio->handle_webhook( $data );

        // Return TwiML response
        header( 'Content-Type: text/xml' );
        echo '<?xml version="1.0" encoding="UTF-8"?><Response></Response>';
        exit;
    }

    /**
     * Verify Twilio webhook signature.
     *
     * @return bool
     */
    private static function verify_twilio_signature() {
        $signature = $_SERVER['HTTP_X_TWILIO_SIGNATURE'] ?? '';
        $url = home_url( $_SERVER['REQUEST_URI'] ?? '' );
        $auth_token = get_option( 'sodek_gb_twilio_auth_token', '' );

        if ( empty( $signature ) || empty( $auth_token ) ) {
            return false;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $params = wp_unslash( $_POST );
        ksort( $params );

        $data = $url;
        foreach ( $params as $key => $value ) {
            $data .= $key . $value;
        }

        $computed = base64_encode( hash_hmac( 'sha1', $data, $auth_token, true ) );

        return hash_equals( $computed, $signature );
    }

    /**
     * Get SMS statistics for admin dashboard.
     *
     * @param string $period Period (today, week, month).
     * @return array
     */
    public static function get_stats( $period = 'month' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_sms_log';

        switch ( $period ) {
            case 'today':
                $date_filter = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $date_filter = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            default:
                $date_filter = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }

        $stats = $wpdb->get_row(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' OR status = 'delivered' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                COUNT(DISTINCT phone) as unique_recipients
            FROM {$table}
            WHERE {$date_filter}",
            ARRAY_A
        );

        return array(
            'total'             => (int) ( $stats['total'] ?? 0 ),
            'sent'              => (int) ( $stats['sent'] ?? 0 ),
            'failed'            => (int) ( $stats['failed'] ?? 0 ),
            'unique_recipients' => (int) ( $stats['unique_recipients'] ?? 0 ),
            'success_rate'      => $stats['total'] > 0
                ? round( ( $stats['sent'] / $stats['total'] ) * 100, 1 )
                : 0,
        );
    }

    /**
     * Test SMS sending.
     *
     * @param string $phone Test phone number.
     * @return array|WP_Error
     */
    public static function send_test( $phone ) {
        if ( ! self::is_available() ) {
            return new WP_Error(
                'sms_unavailable',
                __( 'SMS service is not configured.', 'glowbook' )
            );
        }

        $message = sprintf(
            /* translators: %s: business name */
            __( 'This is a test message from %s. If you received this, SMS notifications are working correctly!', 'glowbook' ),
            get_bloginfo( 'name' )
        );

        return self::$twilio->send( $phone, $message );
    }
}
