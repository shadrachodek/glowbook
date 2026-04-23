<?php
/**
 * Booking reminders cron handler.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Reminders class.
 */
class Sodek_GB_Reminders {

    /**
     * Initialize.
     */
    public static function init() {
        // Hook into cron event
        add_action( 'sodek_gb_send_reminders', array( __CLASS__, 'process_reminders' ) );
    }

    /**
     * Process reminders.
     */
    public static function process_reminders() {
        $reminder_24h_enabled = get_option( 'sodek_gb_reminder_24h_enabled', 1 );
        $reminder_2h_enabled = get_option( 'sodek_gb_reminder_2h_enabled', 1 );

        if ( $reminder_24h_enabled ) {
            self::send_reminders( '24h', 24 * 60, 23 * 60 ); // Between 24h and 23h before
        }

        if ( $reminder_2h_enabled ) {
            self::send_reminders( '2h', 2 * 60, 1.5 * 60 ); // Between 2h and 1.5h before
        }
    }

    /**
     * Send reminders for bookings within time window.
     *
     * @param string $reminder_type Type identifier.
     * @param int    $max_minutes   Maximum minutes until appointment.
     * @param int    $min_minutes   Minimum minutes until appointment.
     */
    private static function send_reminders( $reminder_type, $max_minutes, $min_minutes ) {
        global $wpdb;

        $timezone = wp_timezone();
        $now      = new DateTimeImmutable( 'now', $timezone );
        $max_time = $now->modify( '+' . (int) $max_minutes . ' minutes' )->format( 'Y-m-d H:i:s' );
        $min_time = $now->modify( '+' . (int) $min_minutes . ' minutes' )->format( 'Y-m-d H:i:s' );

        // Get confirmed bookings in window that haven't received this reminder
        $bookings_table = $wpdb->prefix . 'sodek_gb_booked_slots';
        $reminders_table = $wpdb->prefix . 'sodek_gb_sent_reminders';

        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT bs.booking_id, bs.slot_date, bs.start_time
                FROM $bookings_table bs
                LEFT JOIN $reminders_table sr ON bs.booking_id = sr.booking_id AND sr.reminder_type = %s
                WHERE bs.status = 'confirmed'
                AND CONCAT(bs.slot_date, ' ', bs.start_time) BETWEEN %s AND %s
                AND sr.id IS NULL",
                $reminder_type,
                $min_time,
                $max_time
            ),
            ARRAY_A
        );

        foreach ( $bookings as $booking_slot ) {
            $booking_id = $booking_slot['booking_id'];

            // Double-check booking status
            $status = get_post_meta( $booking_id, '_sodek_gb_status', true );
            if ( Sodek_GB_Booking::STATUS_CONFIRMED !== $status ) {
                continue;
            }

            Sodek_GB_Emails::send_reminder( $booking_id, $reminder_type );

            // Record that reminder was sent
            self::record_reminder( $booking_id, $reminder_type );

        }
    }

    /**
     * Record that a reminder was sent.
     *
     * @param int    $booking_id    Booking ID.
     * @param string $reminder_type Reminder type.
     * @return bool
     */
    private static function record_reminder( $booking_id, $reminder_type ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_sent_reminders';

        $result = $wpdb->insert(
            $table,
            array(
                'booking_id'    => $booking_id,
                'reminder_type' => $reminder_type,
                'sent_at'       => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s' )
        );

        return (bool) $result;
    }

    /**
     * Check if reminder was already sent.
     *
     * @param int    $booking_id    Booking ID.
     * @param string $reminder_type Reminder type.
     * @return bool
     */
    public static function was_reminder_sent( $booking_id, $reminder_type ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_sent_reminders';

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE booking_id = %d AND reminder_type = %s",
                $booking_id,
                $reminder_type
            )
        );

        return $count > 0;
    }

    /**
     * Get reminders sent for a booking.
     *
     * @param int $booking_id Booking ID.
     * @return array
     */
    public static function get_sent_reminders( $booking_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_sent_reminders';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT reminder_type, sent_at FROM $table WHERE booking_id = %d ORDER BY sent_at DESC",
                $booking_id
            ),
            ARRAY_A
        );
    }

    /**
     * Manually send a reminder.
     *
     * @param int    $booking_id    Booking ID.
     * @param string $reminder_type Reminder type.
     * @return bool
     */
    public static function send_manual_reminder( $booking_id, $reminder_type = 'manual' ) {
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return false;
        }

        Sodek_GB_Emails::send_reminder( $booking_id, $reminder_type );

        self::record_reminder( $booking_id, $reminder_type );

        return true;
    }

    /**
     * Clear reminders for a booking.
     *
     * @param int $booking_id Booking ID.
     * @return bool
     */
    public static function clear_reminders( $booking_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_sent_reminders';

        return (bool) $wpdb->delete( $table, array( 'booking_id' => $booking_id ), array( '%d' ) );
    }
}
