<?php
/**
 * Waitlist Management.
 *
 * Handles waitlist entries for fully booked time slots.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Waitlist class.
 */
class Sodek_GB_Waitlist {

    /**
     * Waitlist statuses.
     */
    const STATUS_WAITING  = 'waiting';
    const STATUS_NOTIFIED = 'notified';
    const STATUS_BOOKED   = 'booked';
    const STATUS_EXPIRED  = 'expired';
    const STATUS_REMOVED  = 'removed';

    /**
     * Time preferences.
     */
    const TIME_ANY       = 'any';
    const TIME_MORNING   = 'morning';
    const TIME_AFTERNOON = 'afternoon';
    const TIME_EVENING   = 'evening';

    /**
     * Add customer to waitlist.
     *
     * @param array $data Waitlist entry data.
     * @return int|false Entry ID or false.
     */
    public static function add( $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_waitlist';

        // Check if customer is already on waitlist for this date/service
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table
                WHERE customer_id = %d
                AND service_id = %d
                AND requested_date = %s
                AND status = %s",
                $data['customer_id'],
                $data['service_id'],
                $data['requested_date'],
                self::STATUS_WAITING
            )
        );

        if ( $existing ) {
            // Update existing entry
            $wpdb->update(
                $table,
                array(
                    'staff_id'        => $data['staff_id'] ?? null,
                    'time_preference' => $data['time_preference'] ?? self::TIME_ANY,
                ),
                array( 'id' => $existing )
            );
            return (int) $existing;
        }

        // Set expiration (end of requested date)
        $expires_at = $data['requested_date'] . ' 23:59:59';

        $result = $wpdb->insert(
            $table,
            array(
                'customer_id'     => $data['customer_id'],
                'service_id'      => $data['service_id'],
                'staff_id'        => $data['staff_id'] ?? null,
                'requested_date'  => $data['requested_date'],
                'time_preference' => $data['time_preference'] ?? self::TIME_ANY,
                'status'          => self::STATUS_WAITING,
                'expires_at'      => $expires_at,
            )
        );

        if ( ! $result ) {
            return false;
        }

        $entry_id = $wpdb->insert_id;

        do_action( 'sodek_gb_waitlist_added', $entry_id, $data );

        return $entry_id;
    }

    /**
     * Get waitlist entry by ID.
     *
     * @param int $entry_id Entry ID.
     * @return array|null
     */
    public static function get( $entry_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_waitlist';

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $entry_id ),
            ARRAY_A
        );
    }

    /**
     * Get waitlist entries for a date and service.
     *
     * @param string   $date       Date (Y-m-d).
     * @param int      $service_id Service ID.
     * @param int|null $staff_id   Optional staff ID filter.
     * @return array
     */
    public static function get_for_date( $date, $service_id, $staff_id = null ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_waitlist';

        $where = "requested_date = %s AND service_id = %d AND status = %s";
        $params = array( $date, $service_id, self::STATUS_WAITING );

        if ( $staff_id ) {
            $where .= " AND (staff_id = %d OR staff_id IS NULL)";
            $params[] = $staff_id;
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE $where ORDER BY created_at ASC",
                $params
            ),
            ARRAY_A
        );
    }

    /**
     * Get waitlist entries for a customer.
     *
     * @param int    $customer_id Customer ID.
     * @param string $status      Optional status filter.
     * @return array
     */
    public static function get_for_customer( $customer_id, $status = '' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_waitlist';

        $where = "customer_id = %d";
        $params = array( $customer_id );

        if ( $status ) {
            $where .= " AND status = %s";
            $params[] = $status;
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE $where ORDER BY requested_date ASC",
                $params
            ),
            ARRAY_A
        );
    }

    /**
     * Update waitlist entry status.
     *
     * @param int    $entry_id Entry ID.
     * @param string $status   New status.
     * @return bool
     */
    public static function update_status( $entry_id, $status ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_waitlist';

        $data = array( 'status' => $status );

        if ( self::STATUS_NOTIFIED === $status ) {
            $data['notified_at'] = current_time( 'mysql' );
        }

        $result = (bool) $wpdb->update(
            $table,
            $data,
            array( 'id' => $entry_id )
        );

        if ( $result ) {
            do_action( 'sodek_gb_waitlist_status_changed', $entry_id, $status );
        }

        return $result;
    }

    /**
     * Remove customer from waitlist.
     *
     * @param int $entry_id    Entry ID.
     * @param int $customer_id Customer ID (for security).
     * @return bool
     */
    public static function remove( $entry_id, $customer_id ) {
        return self::update_status( $entry_id, self::STATUS_REMOVED );
    }

    /**
     * Process waitlist when a slot becomes available.
     *
     * Called when a booking is cancelled or rescheduled.
     *
     * @param string $date       Date (Y-m-d).
     * @param string $time       Time (H:i).
     * @param int    $service_id Service ID.
     * @param int    $staff_id   Staff ID.
     */
    public static function notify_for_slot( $date, $time, $service_id, $staff_id = null ) {
        $entries = self::get_for_date( $date, $service_id, $staff_id );

        if ( empty( $entries ) ) {
            return;
        }

        // Determine time period
        $hour = (int) substr( $time, 0, 2 );
        $time_period = self::TIME_ANY;

        if ( $hour < 12 ) {
            $time_period = self::TIME_MORNING;
        } elseif ( $hour < 17 ) {
            $time_period = self::TIME_AFTERNOON;
        } else {
            $time_period = self::TIME_EVENING;
        }

        foreach ( $entries as $entry ) {
            // Check time preference
            if ( $entry['time_preference'] !== self::TIME_ANY && $entry['time_preference'] !== $time_period ) {
                continue;
            }

            // Notify customer
            self::send_notification( $entry, $date, $time, $service_id, $staff_id );

            // Mark as notified
            self::update_status( $entry['id'], self::STATUS_NOTIFIED );
        }
    }

    /**
     * Send waitlist notification to customer.
     *
     * @param array  $entry      Waitlist entry.
     * @param string $date       Date (Y-m-d).
     * @param string $time       Time (H:i).
     * @param int    $service_id Service ID.
     * @param int    $staff_id   Staff ID.
     */
    private static function send_notification( $entry, $date, $time, $service_id, $staff_id ) {
        $customer = Sodek_GB_Customer::get_by_id( $entry['customer_id'] );

        if ( ! $customer || empty( $customer['email'] ) ) {
            return;
        }

        $service = Sodek_GB_Service::get_service( $service_id );
        $service_name = $service ? $service['title'] : __( 'Service', 'glowbook' );

        $booking_url = add_query_arg(
            array(
                'service_id' => $service_id,
                'date'       => $date,
                'time'       => $time,
                'waitlist'   => $entry['id'],
            ),
            home_url( '/' . get_option( 'sodek_gb_booking_slug', 'book' ) . '/' )
        );

        $subject = sprintf(
            /* translators: %s: service name */
            __( 'Good news! A slot is available for %s', 'glowbook' ),
            $service_name
        );

        $message = sprintf(
            /* translators: 1: customer name, 2: service name, 3: date, 4: time, 5: booking URL */
            __(
                "Hi %1\$s,\n\nGreat news! A slot has opened up for %2\$s on %3\$s at %4\$s.\n\nBook now before it's taken:\n%5\$s\n\nThis slot won't last long, so book soon!\n\nBest regards,\n%6\$s",
                'glowbook'
            ),
            Sodek_GB_Customer::get_full_name( $entry['customer_id'] ),
            $service_name,
            date_i18n( get_option( 'date_format' ), strtotime( $date ) ),
            date_i18n( get_option( 'time_format' ), strtotime( $time ) ),
            $booking_url,
            get_bloginfo( 'name' )
        );

        wp_mail( $customer['email'], $subject, $message );

        do_action( 'sodek_gb_waitlist_notification_sent', $entry, $date, $time );
    }

    /**
     * Clean up expired waitlist entries.
     *
     * Should be called via cron.
     */
    public static function cleanup_expired() {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_waitlist';

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table SET status = %s WHERE status = %s AND expires_at < %s",
                self::STATUS_EXPIRED,
                self::STATUS_WAITING,
                current_time( 'mysql' )
            )
        );
    }

    /**
     * Get waitlist statistics.
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_waitlist';

        return array(
            'total_waiting'  => (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE status = %s", self::STATUS_WAITING )
            ),
            'total_notified' => (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE status = %s", self::STATUS_NOTIFIED )
            ),
            'total_booked'   => (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE status = %s", self::STATUS_BOOKED )
            ),
            'total_expired'  => (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE status = %s", self::STATUS_EXPIRED )
            ),
        );
    }

    /**
     * Mark waitlist entry as booked.
     *
     * @param int $entry_id   Entry ID.
     * @param int $booking_id Booking ID.
     * @return bool
     */
    public static function mark_as_booked( $entry_id, $booking_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_waitlist';

        return (bool) $wpdb->update(
            $table,
            array(
                'status' => self::STATUS_BOOKED,
            ),
            array( 'id' => $entry_id )
        );
    }

    /**
     * Get time preference options.
     *
     * @return array
     */
    public static function get_time_preferences() {
        return array(
            self::TIME_ANY       => __( 'Any time', 'glowbook' ),
            self::TIME_MORNING   => __( 'Morning (before 12pm)', 'glowbook' ),
            self::TIME_AFTERNOON => __( 'Afternoon (12pm - 5pm)', 'glowbook' ),
            self::TIME_EVENING   => __( 'Evening (after 5pm)', 'glowbook' ),
        );
    }
}
