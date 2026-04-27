<?php
/**
 * Availability and slot generation.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Availability class.
 */
class Sodek_GB_Availability {

    /**
     * Get the configured business timezone.
     *
     * @return DateTimeZone
     */
    public static function get_timezone() {
        $tz_string = get_option( 'sodek_gb_timezone', '' );

        if ( empty( $tz_string ) ) {
            $tz_string = wp_timezone_string();
        }

        try {
            return new DateTimeZone( $tz_string );
        } catch ( Exception $e ) {
            // Fallback to WordPress timezone
            return wp_timezone();
        }
    }

    /**
     * Get current date in business timezone.
     *
     * @param string $format Date format.
     * @return string
     */
    public static function current_date( $format = 'Y-m-d' ) {
        try {
            $datetime = new DateTime( 'now', self::get_timezone() );
            return $datetime->format( $format );
        } catch ( Exception $e ) {
            return gmdate( $format );
        }
    }

    /**
     * Get current time in business timezone.
     *
     * @param string $format Time format.
     * @return string
     */
    public static function current_time( $format = 'H:i' ) {
        try {
            $datetime = new DateTime( 'now', self::get_timezone() );
            return $datetime->format( $format );
        } catch ( Exception $e ) {
            return gmdate( $format );
        }
    }

    /**
     * Create a DateTime object in business timezone.
     *
     * @param string $datetime_string Date/time string.
     * @return DateTime
     */
    public static function create_datetime( $datetime_string ) {
        try {
            return new DateTime( $datetime_string, self::get_timezone() );
        } catch ( Exception $e ) {
            // Fallback to current time if string is invalid
            return new DateTime( 'now', self::get_timezone() );
        }
    }

    /**
     * Get weekly schedule.
     *
     * @return array
     */
    public static function get_weekly_schedule() {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_availability';
        $results = $wpdb->get_results( "SELECT * FROM $table ORDER BY day_of_week ASC", ARRAY_A );

        $schedule = array();
        foreach ( $results as $row ) {
            $schedule[ $row['day_of_week'] ] = array(
                'start_time'   => $row['start_time'],
                'end_time'     => $row['end_time'],
                'is_available' => (bool) $row['is_available'],
            );
        }

        return $schedule;
    }

    /**
     * Update weekly schedule.
     *
     * @param array $schedule Schedule data indexed by day of week.
     * @return bool
     */
    public static function update_weekly_schedule( $schedule ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_availability';

        foreach ( $schedule as $day => $data ) {
            $wpdb->update(
                $table,
                array(
                    'start_time'   => $data['start_time'],
                    'end_time'     => $data['end_time'],
                    'is_available' => $data['is_available'] ? 1 : 0,
                ),
                array( 'day_of_week' => $day ),
                array( '%s', '%s', '%d' ),
                array( '%d' )
            );
        }

        return true;
    }

    /**
     * Get date overrides.
     *
     * @param string $start_date Start date (Y-m-d).
     * @param string $end_date   End date (Y-m-d).
     * @return array
     */
    public static function get_overrides( $start_date, $end_date ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_availability_overrides';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE override_date BETWEEN %s AND %s ORDER BY override_date, start_time",
                $start_date,
                $end_date
            ),
            ARRAY_A
        );
    }

    /**
     * Add an override.
     *
     * @param array $data Override data.
     * @return int|false Insert ID or false.
     */
    public static function add_override( $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_availability_overrides';

        $result = $wpdb->insert(
            $table,
            array(
                'override_date' => $data['date'],
                'start_time'    => $data['start_time'] ?? null,
                'end_time'      => $data['end_time'] ?? null,
                'type'          => $data['type'] ?? 'block',
                'reason'        => $data['reason'] ?? null,
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Delete an override.
     *
     * @param int $override_id Override ID.
     * @return bool
     */
    public static function delete_override( $override_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_availability_overrides';

        return (bool) $wpdb->delete( $table, array( 'id' => $override_id ), array( '%d' ) );
    }

    /**
     * Block an entire date.
     *
     * @param string $date   Date (Y-m-d).
     * @param string $reason Optional reason.
     * @return int|false
     */
    public static function block_date( $date, $reason = '' ) {
        return self::add_override( array(
            'date'       => $date,
            'start_time' => null,
            'end_time'   => null,
            'type'       => 'block',
            'reason'     => $reason,
        ) );
    }

    /**
     * Check if a date is available.
     *
     * @param string $date Date (Y-m-d).
     * @return bool
     */
    public static function is_date_available( $date ) {
        global $wpdb;

        // Check for full-day block override
        $overrides_table = $wpdb->prefix . 'sodek_gb_availability_overrides';
        $full_block = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $overrides_table
                WHERE override_date = %s
                AND type = 'block'
                AND start_time IS NULL",
                $date
            )
        );

        if ( $full_block > 0 ) {
            return false;
        }

        // Check weekly schedule
        $date_obj = self::create_datetime( $date );
        $day_of_week = (int) $date_obj->format( 'w' );
        $schedule = self::get_weekly_schedule();

        if ( ! isset( $schedule[ $day_of_week ] ) || ! $schedule[ $day_of_week ]['is_available'] ) {
            // Check for open override
            $open_override = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $overrides_table
                    WHERE override_date = %s
                    AND type = 'open'",
                    $date
                )
            );
            return $open_override > 0;
        }

        return true;
    }

    /**
     * Get the default business-wide daily booking limit.
     *
     * @return int 0 means unlimited.
     */
    public static function get_default_daily_booking_limit() {
        return max( 0, (int) get_option( 'sodek_gb_daily_booking_limit_default', 3 ) );
    }

    /**
     * Get weekday daily booking limit overrides.
     *
     * @return array
     */
    public static function get_weekday_daily_booking_limits() {
        $limits = get_option( 'sodek_gb_daily_booking_limit_weekdays', array() );
        return is_array( $limits ) ? array_map( 'absint', $limits ) : array();
    }

    /**
     * Get specific date daily booking limit overrides.
     *
     * @return array
     */
    public static function get_daily_booking_limit_overrides() {
        $overrides = get_option( 'sodek_gb_daily_booking_limit_overrides', array() );
        return is_array( $overrides ) ? $overrides : array();
    }

    /**
     * Get business-wide booking limit for a specific date.
     *
     * Priority: specific date override, weekday override, default.
     *
     * @param string $date Date (Y-m-d).
     * @return int 0 means unlimited.
     */
    public static function get_daily_booking_limit( $date ) {
        $overrides = self::get_daily_booking_limit_overrides();
        if ( isset( $overrides[ $date ] ) && is_array( $overrides[ $date ] ) ) {
            return max( 0, (int) ( $overrides[ $date ]['limit'] ?? 0 ) );
        }

        $date_obj = self::create_datetime( $date );
        $day      = (int) $date_obj->format( 'w' );
        $limits   = self::get_weekday_daily_booking_limits();

        if ( array_key_exists( $day, $limits ) ) {
            return max( 0, (int) $limits[ $day ] );
        }

        return self::get_default_daily_booking_limit();
    }

    /**
     * Get active business-wide booking count for a date.
     *
     * @param string $date Date (Y-m-d).
     * @return int
     */
    public static function get_daily_bookings_count( $date ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_booked_slots';

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT booking_id) FROM $table
                WHERE slot_date = %s
                AND status NOT IN ('cancelled')",
                $date
            )
        );
    }

    /**
     * Check if the business-wide daily limit has been reached.
     *
     * @param string $date Date (Y-m-d).
     * @return bool
     */
    public static function is_day_fully_booked( $date ) {
        $limit = self::get_daily_booking_limit( $date );
        if ( $limit <= 0 ) {
            return false;
        }

        return self::get_daily_bookings_count( $date ) >= $limit;
    }

    /**
     * Get remaining business-wide bookings available for a date.
     *
     * @param string $date Date (Y-m-d).
     * @return int|null Null means unlimited.
     */
    public static function get_remaining_daily_slots( $date ) {
        $limit = self::get_daily_booking_limit( $date );
        if ( $limit <= 0 ) {
            return null;
        }

        return max( 0, $limit - self::get_daily_bookings_count( $date ) );
    }

    /**
     * Check daily capacity while including active checkout locks.
     *
     * This protects business-wide daily limits from concurrent checkouts for
     * different times on the same date.
     *
     * @param string $date Date (Y-m-d).
     * @return bool
     */
    public static function has_daily_checkout_capacity( $date ) {
        $limit = self::get_daily_booking_limit( $date );
        if ( $limit <= 0 ) {
            return true;
        }

        return ( self::get_daily_bookings_count( $date ) + self::get_active_daily_locks_count( $date ) ) <= $limit;
    }

    /**
     * Get active temporary checkout locks for a date.
     *
     * @param string $date Date (Y-m-d).
     * @return int
     */
    private static function get_active_daily_locks_count( $date ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_slot_locks';
        self::ensure_locks_table();
        self::cleanup_expired_locks();

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT reference_id) FROM $table
                WHERE slot_date = %s
                AND expires_at > UTC_TIMESTAMP()",
                $date
            )
        );
    }

    /**
     * Get bookings count for a service on a specific date.
     *
     * @param int    $service_id Service ID.
     * @param string $date       Date (Y-m-d).
     * @return int
     */
    public static function get_service_bookings_count( $service_id, $date ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_booked_slots';

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table
                WHERE service_id = %d
                AND slot_date = %s
                AND status NOT IN ('cancelled')",
                $service_id,
                $date
            )
        );
    }

    /**
     * Check if service has reached max daily bookings.
     *
     * @param int    $service_id Service ID.
     * @param string $date       Date (Y-m-d).
     * @return bool True if limit reached, false otherwise.
     */
    public static function is_service_fully_booked( $service_id, $date ) {
        $service = Sodek_GB_Service::get_service( $service_id );
        if ( ! $service ) {
            return true;
        }

        $max_daily = isset( $service['max_daily_bookings'] ) ? (int) $service['max_daily_bookings'] : 0;

        // 0 means unlimited
        if ( $max_daily <= 0 ) {
            return false;
        }

        $current_count = self::get_service_bookings_count( $service_id, $date );

        return $current_count >= $max_daily;
    }

    /**
     * Get remaining slots for a service on a date.
     *
     * @param int    $service_id Service ID.
     * @param string $date       Date (Y-m-d).
     * @return int|null Remaining count or null for unlimited.
     */
    public static function get_remaining_service_slots( $service_id, $date ) {
        $service = Sodek_GB_Service::get_service( $service_id );
        if ( ! $service ) {
            return 0;
        }

        $max_daily = isset( $service['max_daily_bookings'] ) ? (int) $service['max_daily_bookings'] : 0;

        // 0 means unlimited
        if ( $max_daily <= 0 ) {
            return null;
        }

        $current_count = self::get_service_bookings_count( $service_id, $date );

        return max( 0, $max_daily - $current_count );
    }

    /**
     * Get available time slots for a date.
     *
     * @param string $date       Date (Y-m-d).
     * @param int    $service_id Service ID.
     * @param array  $addon_ids  Selected add-on IDs.
     * @return array
     */
    public static function get_available_slots( $date, $service_id, $addon_ids = array() ) {
        // Validate date
        if ( ! self::is_date_available( $date ) ) {
            return array();
        }

        // Check business-wide daily booking limit before generating times.
        if ( self::is_day_fully_booked( $date ) ) {
            return array();
        }

        // Check minimum booking notice
        $min_notice = (int) get_option( 'sodek_gb_min_booking_notice', 24 );
        $now = self::create_datetime( 'now' );
        $min_datetime = clone $now;
        $min_datetime->modify( "+{$min_notice} hours" );
        $today = self::current_date( 'Y-m-d' );

        if ( $date < $min_datetime->format( 'Y-m-d' ) ) {
            // Allow same-day if time is still valid
            if ( $today !== $date ) {
                return array();
            }
        }

        // Get service details
        $service = Sodek_GB_Service::get_service( $service_id );
        if ( ! $service ) {
            return array();
        }

        // Check if service has reached max daily bookings
        if ( self::is_service_fully_booked( $service_id, $date ) ) {
            return array();
        }

        // Get business hours for this date
        $hours = self::get_hours_for_date( $date );
        if ( empty( $hours ) ) {
            return array();
        }

        // Get booked slots and active checkout locks for this date.
        $booked = array_merge( self::get_booked_slots( $date ), self::get_locked_slots( $date ) );

        $addon_ids = Sodek_GB_Addon::validate_addons_for_service( array_map( 'absint', (array) $addon_ids ), $service_id );
        $addons_duration = self::get_addons_duration( $addon_ids );

        // Calculate total slot duration (service + selected add-ons + buffers)
        $service_duration = (int) $service['duration'];
        $booking_duration = $service_duration + $addons_duration;
        $buffer_before = isset( $service['buffer_before'] ) ? (int) $service['buffer_before'] : 0;
        $buffer_after = isset( $service['buffer_after'] ) ? (int) $service['buffer_after'] : 0;
        $slot_duration = $booking_duration + $buffer_before + $buffer_after;
        $interval = (int) get_option( 'sodek_gb_time_slot_interval', 30 );

        // Generate available slots
        $slots = array();
        $current_time = self::create_datetime( $date . ' ' . $hours['start_time'] )->getTimestamp();
        $end_time     = self::create_datetime( $date . ' ' . $hours['end_time'] )->getTimestamp();

        // Adjust for buffer before
        $service_start_offset = $buffer_before * 60;

        // Get timezone for formatting
        $tz = self::get_timezone();

        while ( $current_time + ( $slot_duration * 60 ) <= $end_time ) {
            $slot_start_dt = new DateTime( '@' . ( $current_time + $service_start_offset ) );
            $slot_start_dt->setTimezone( $tz );
            $slot_start = $slot_start_dt->format( 'H:i' );

            $slot_end_dt = new DateTime( '@' . ( $current_time + $service_start_offset + ( $booking_duration * 60 ) ) );
            $slot_end_dt->setTimezone( $tz );
            $slot_end = $slot_end_dt->format( 'H:i' );

            // Check if slot conflicts with existing bookings
            if ( ! self::slot_conflicts( $date, $current_time, $slot_duration, $booked ) ) {
                // Check minimum booking notice for today
                if ( $today === $date ) {
                    $slot_timestamp = self::create_datetime( $date . ' ' . $slot_start )->getTimestamp();
                    if ( $slot_timestamp > $min_datetime->getTimestamp() ) {
                        $slots[] = array(
                            'start' => $slot_start,
                            'end'   => $slot_end,
                        );
                    }
                } else {
                    $slots[] = array(
                        'start' => $slot_start,
                        'end'   => $slot_end,
                    );
                }
            }

            $current_time += $interval * 60;
        }

        return $slots;
    }

    /**
     * Get business hours for a specific date.
     *
     * @param string $date Date (Y-m-d).
     * @return array|null
     */
    private static function get_hours_for_date( $date ) {
        global $wpdb;

        // Check for open override with specific hours
        $overrides_table = $wpdb->prefix . 'sodek_gb_availability_overrides';
        $override = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $overrides_table
                WHERE override_date = %s
                AND type = 'open'
                AND start_time IS NOT NULL
                LIMIT 1",
                $date
            ),
            ARRAY_A
        );

        if ( $override ) {
            return array(
                'start_time' => $override['start_time'],
                'end_time'   => $override['end_time'],
            );
        }

        // Get weekly schedule
        $date_obj = self::create_datetime( $date );
        $day_of_week = (int) $date_obj->format( 'w' );
        $schedule = self::get_weekly_schedule();

        if ( isset( $schedule[ $day_of_week ] ) && $schedule[ $day_of_week ]['is_available'] ) {
            return array(
                'start_time' => $schedule[ $day_of_week ]['start_time'],
                'end_time'   => $schedule[ $day_of_week ]['end_time'],
            );
        }

        return null;
    }

    /**
     * Get booked slots for a date.
     *
     * @param string $date Date (Y-m-d).
     * @return array
     */
    private static function get_booked_slots( $date ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_booked_slots';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT start_time, end_time, service_id FROM $table
                WHERE slot_date = %s
                AND status NOT IN ('cancelled')",
                $date
            ),
            ARRAY_A
        );
    }

    /**
     * Check if a slot conflicts with existing bookings.
     *
     * @param int   $slot_start    Slot start timestamp.
     * @param int   $slot_duration Slot duration in minutes.
     * @param array $booked        Booked slots.
     * @return bool
     */
    private static function slot_conflicts( $date, $slot_start, $slot_duration, $booked ) {
        $slot_end = $slot_start + ( $slot_duration * 60 );

        foreach ( $booked as $booking ) {
            // Skip bookings with missing or invalid data
            if ( empty( $booking['start_time'] ) || empty( $booking['end_time'] ) ) {
                continue;
            }

            // Get service buffers (default to 0 if service was deleted)
            $buffer_before = 0;
            $buffer_after = 0;

            if ( ! empty( $booking['service_id'] ) ) {
                $service = Sodek_GB_Service::get_service( $booking['service_id'] );
                if ( $service ) {
                    $buffer_before = isset( $service['buffer_before'] ) ? intval( $service['buffer_before'] ) * 60 : 0;
                    $buffer_after = isset( $service['buffer_after'] ) ? intval( $service['buffer_after'] ) * 60 : 0;
                }
            }

            $booking_start = self::create_datetime( $date . ' ' . self::normalize_time( $booking['start_time'] ) )->getTimestamp() - $buffer_before;
            $booking_end = self::create_datetime( $date . ' ' . self::normalize_time( $booking['end_time'] ) )->getTimestamp() + $buffer_after;

            // Check overlap
            if ( $slot_start < $booking_end && $slot_end > $booking_start ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get total selected add-on duration.
     *
     * @param array $addon_ids Add-on IDs.
     * @return int Duration in minutes.
     */
    public static function get_addons_duration( $addon_ids ) {
        $duration = 0;

        foreach ( array_map( 'absint', (array) $addon_ids ) as $addon_id ) {
            if ( ! $addon_id ) {
                continue;
            }

            $addon = Sodek_GB_Addon::get_addon( $addon_id );
            if ( $addon ) {
                $duration += (int) $addon['duration'];
            }
        }

        return $duration;
    }

    /**
     * Check if a specific time slot is available.
     *
     * @param string $date       Date (Y-m-d).
     * @param string $start_time Start time (H:i or H:i:s).
     * @param int    $service_id Service ID.
     * @param array  $addon_ids  Selected add-on IDs.
     * @return bool|array True if available, or array with 'available' => false and 'reason'.
     */
    public static function is_slot_available( $date, $start_time, $service_id, $addon_ids = array() ) {
        // Normalize time format to H:i (remove seconds, ensure consistent format)
        $normalized_time = self::normalize_time( $start_time );

        // First check if there's an active lock on this slot
        if ( self::is_slot_locked( $date, $normalized_time, $service_id ) ) {
            return false;
        }

        $available_slots = self::get_available_slots( $date, $service_id, $addon_ids );

        foreach ( $available_slots as $slot ) {
            // Normalize both times for comparison
            if ( self::normalize_time( $slot['start'] ) === $normalized_time ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check slot availability with detailed reason for unavailability.
     *
     * @param string $date       Date (Y-m-d).
     * @param string $start_time Start time.
     * @param int    $service_id Service ID.
     * @param array  $addon_ids  Selected add-on IDs.
     * @return array Array with 'available' bool and 'reason' string.
     */
    public static function check_slot_availability( $date, $start_time, $service_id, $addon_ids = array() ) {
        $normalized_time = self::normalize_time( $start_time );
        $today = self::current_date( 'Y-m-d' );

        // Check if date is in the past
        if ( strtotime( $date ) < strtotime( $today ) ) {
            return array(
                'available' => false,
                'reason'    => 'date_past',
                'message'   => __( 'This date is in the past.', 'glowbook' ),
            );
        }

        // Check if date is available (not blocked)
        if ( ! self::is_date_available( $date ) ) {
            return array(
                'available' => false,
                'reason'    => 'date_blocked',
                'message'   => __( 'This date is not available for bookings.', 'glowbook' ),
            );
        }

        // Check business-wide daily booking limit.
        if ( self::is_day_fully_booked( $date ) ) {
            return array(
                'available' => false,
                'reason'    => 'day_fully_booked',
                'message'   => __( 'This date has reached the daily booking limit.', 'glowbook' ),
            );
        }

        // Check if service has reached max daily bookings
        if ( self::is_service_fully_booked( $service_id, $date ) ) {
            return array(
                'available' => false,
                'reason'    => 'service_fully_booked',
                'message'   => __( 'This service is fully booked for this date.', 'glowbook' ),
            );
        }

        // Check if slot is locked by another user
        if ( self::is_slot_locked( $date, $normalized_time, $service_id ) ) {
            return array(
                'available' => false,
                'reason'    => 'slot_locked',
                'message'   => __( 'This time slot is currently being booked by another customer.', 'glowbook' ),
            );
        }

        // Get available slots and check if requested time is among them
        $available_slots = self::get_available_slots( $date, $service_id, $addon_ids );

        if ( empty( $available_slots ) ) {
            return array(
                'available' => false,
                'reason'    => 'no_slots',
                'message'   => __( 'No time slots are available for this date.', 'glowbook' ),
            );
        }

        foreach ( $available_slots as $slot ) {
            if ( self::normalize_time( $slot['start'] ) === $normalized_time ) {
                return array(
                    'available' => true,
                    'reason'    => null,
                    'message'   => null,
                );
            }
        }

        return array(
            'available' => false,
            'reason'    => 'slot_not_available',
            'message'   => __( 'This specific time slot is not available. It may be outside business hours or already booked.', 'glowbook' ),
            'debug'     => array(
                'requested_time'  => $normalized_time,
                'available_count' => count( $available_slots ),
                'available_times' => array_column( $available_slots, 'start' ),
            ),
        );
    }

    /**
     * Normalize time format to HH:MM (24-hour with leading zeros).
     *
     * Handles:
     * - "9:00" -> "09:00"
     * - "09:00:00" -> "09:00"
     * - "9:00 AM" -> "09:00"
     * - "2:30 PM" -> "14:30"
     *
     * @param string $time Time in various formats.
     * @return string Normalized time in HH:MM format.
     */
    public static function normalize_time( $time ) {
        if ( empty( $time ) ) {
            return '00:00';
        }

        $time = trim( $time );

        // Handle AM/PM format first (e.g., "9:00 AM", "2:30 PM")
        if ( preg_match( '/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $time, $matches ) ) {
            $hour = (int) $matches[1];
            $minute = $matches[2];
            $meridiem = strtoupper( $matches[3] );

            if ( $meridiem === 'PM' && $hour < 12 ) {
                $hour += 12;
            } elseif ( $meridiem === 'AM' && $hour === 12 ) {
                $hour = 0;
            }

            return sprintf( '%02d:%s', $hour, $minute );
        }

        // Handle 24h format with optional seconds (e.g., "09:00", "9:00", "09:00:00")
        if ( preg_match( '/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $time, $matches ) ) {
            return sprintf( '%02d:%s', (int) $matches[1], $matches[2] );
        }

        // Try to parse with strtotime for other formats
        $timestamp = strtotime( $time );
        if ( $timestamp !== false ) {
            $dt = new DateTime( '@' . $timestamp );
            $dt->setTimezone( self::get_timezone() );
            return $dt->format( 'H:i' );
        }

        // Return as-is if we can't parse (will likely cause a mismatch, but at least we tried)
        return $time;
    }

    /**
     * Check if a slot has an active lock.
     *
     * @param string $date       Date (Y-m-d).
     * @param string $start_time Start time (H:i).
     * @param string $end_time   End time (H:i).
     * @param int    $service_id Service ID.
     * @return bool
     */
    public static function is_slot_locked( $date, $start_time, $service_id, $end_time = '' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_slot_locks';

        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
            return false;
        }

        // Normalize time format
        $start_time = self::normalize_time( $start_time );
        $end_time = $end_time ? self::normalize_time( $end_time ) : '';

        // Clean up expired locks first
        self::cleanup_expired_locks();

        if ( $end_time ) {
            return ! empty( self::get_overlapping_lock( $date, $start_time, $end_time ) );
        }

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table
                WHERE slot_date = %s
                AND start_time = %s
                AND service_id = %d
                AND expires_at > UTC_TIMESTAMP()",
                $date,
                $start_time,
                $service_id
            )
        );

        return $count > 0;
    }

    /**
     * Acquire a lock on a slot before payment processing.
     * This prevents race conditions where two users book the same slot.
     *
     * @param string $date         Date (Y-m-d).
     * @param string $start_time   Start time (H:i).
     * @param string $end_time     End time (H:i).
     * @param int    $service_id   Service ID.
     * @param string $reference_id Unique reference for this booking attempt.
     * @param int    $lock_minutes How long to hold the lock (default 5 minutes).
     * @return bool True if lock acquired, false if slot already locked.
     */
    public static function acquire_slot_lock( $date, $start_time, $end_time, $service_id, $reference_id, $lock_minutes = 5 ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_slot_locks';

        // Normalize time formats
        $start_time = self::normalize_time( $start_time );
        $end_time = self::normalize_time( $end_time );

        // Create table if it doesn't exist
        self::ensure_locks_table();

        // Clean up expired locks
        self::cleanup_expired_locks();

        $existing = self::get_overlapping_lock( $date, $start_time, $end_time );
        if ( $existing ) {
            if ( $existing['reference_id'] === $reference_id ) {
                // Extend our own lock
                $wpdb->update(
                    $table,
                    array( 'expires_at' => gmdate( 'Y-m-d H:i:s', strtotime( "+{$lock_minutes} minutes" ) ) ),
                    array( 'reference_id' => $reference_id ),
                    array( '%s' ),
                    array( '%s' )
                );
                return true;
            }

            return false;
        }

        // Acquire lock using INSERT with conflict handling
        $result = $wpdb->insert(
            $table,
            array(
                'slot_date'    => $date,
                'start_time'   => $start_time,
                'end_time'     => $end_time,
                'service_id'   => $service_id,
                'reference_id' => $reference_id,
                'expires_at'   => gmdate( 'Y-m-d H:i:s', strtotime( "+{$lock_minutes} minutes" ) ),
                'created_at'   => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
        );

        return (bool) $result;
    }

    /**
     * Release a slot lock.
     *
     * @param string $reference_id Reference ID of the lock to release.
     * @return bool
     */
    public static function release_slot_lock( $reference_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_slot_locks';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
            return false;
        }

        return (bool) $wpdb->delete(
            $table,
            array( 'reference_id' => $reference_id ),
            array( '%s' )
        );
    }

    /**
     * Get active checkout locks for a date.
     *
     * @param string $date Date (Y-m-d).
     * @return array
     */
    private static function get_locked_slots( $date ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_slot_locks';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
            return array();
        }

        self::cleanup_expired_locks();

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT start_time, end_time, service_id
                FROM $table
                WHERE slot_date = %s
                AND expires_at > UTC_TIMESTAMP()",
                $date
            ),
            ARRAY_A
        );
    }

    /**
     * Get an active lock that overlaps a requested time range.
     *
     * @param string $date       Date (Y-m-d).
     * @param string $start_time Requested start time.
     * @param string $end_time   Requested end time.
     * @return array|null
     */
    private static function get_overlapping_lock( $date, $start_time, $end_time ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_slot_locks';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
            return null;
        }

        $start_time = self::normalize_time( $start_time );
        $end_time = self::normalize_time( $end_time );
        self::cleanup_expired_locks();

        $lock = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT reference_id, start_time, end_time
                FROM $table
                WHERE slot_date = %s
                AND expires_at > UTC_TIMESTAMP()
                AND start_time < %s
                AND end_time > %s
                LIMIT 1",
                $date,
                $end_time,
                $start_time
            ),
            ARRAY_A
        );

        return $lock ?: null;
    }

    /**
     * Reserve a slot (create permanent booking record).
     * Called after successful payment.
     *
     * @param int    $booking_id Booking ID.
     * @param string $date       Date (Y-m-d).
     * @param string $start_time Start time (H:i or H:i:s).
     * @param string $end_time   End time (H:i or H:i:s).
     * @param int    $service_id Service ID.
     * @return bool
     */
    public static function reserve_slot( $booking_id, $date, $start_time, $end_time, $service_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_booked_slots';

        // Normalize time format to H:i:s
        $start_time = self::normalize_time( $start_time ) . ':00';
        $end_time = self::normalize_time( $end_time ) . ':00';

        $result = $wpdb->insert(
            $table,
            array(
                'booking_id' => $booking_id,
                'slot_date'  => $date,
                'start_time' => $start_time,
                'end_time'   => $end_time,
                'service_id' => $service_id,
                'status'     => 'confirmed',
            ),
            array( '%d', '%s', '%s', '%s', '%d', '%s' )
        );

        return (bool) $result;
    }

    /**
     * Clean up expired slot locks.
     */
    private static function cleanup_expired_locks() {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_slot_locks';

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
            return;
        }

        $wpdb->query( "DELETE FROM $table WHERE expires_at < UTC_TIMESTAMP()" );
    }

    /**
     * Ensure the slot locks table exists.
     */
    private static function ensure_locks_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_slot_locks';
        $charset_collate = $wpdb->get_charset_collate();

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) {
            return;
        }

        $sql = "CREATE TABLE $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            slot_date date NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            service_id bigint(20) UNSIGNED NOT NULL,
            reference_id varchar(50) NOT NULL,
            expires_at datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY reference_id (reference_id),
            KEY slot_lookup (slot_date, start_time, service_id),
            KEY expires_at (expires_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Get available dates for a month.
     *
     * @param int $year      Year.
     * @param int $month     Month.
     * @param int   $service_id Service ID.
     * @param array $addon_ids  Selected add-on IDs.
     * @return array
     */
    public static function get_available_dates( $year, $month, $service_id, $addon_ids = array() ) {
        $dates = array();
        $days_in_month = cal_days_in_month( CAL_GREGORIAN, $month, $year );
        $max_advance = (int) get_option( 'sodek_gb_max_booking_advance', 60 );

        // Calculate dates in business timezone
        $now = self::create_datetime( 'now' );
        $today = self::current_date( 'Y-m-d' );
        $max_dt = clone $now;
        $max_dt->modify( "+{$max_advance} days" );
        $max_date = $max_dt->format( 'Y-m-d' );

        for ( $day = 1; $day <= $days_in_month; $day++ ) {
            $date = sprintf( '%d-%02d-%02d', $year, $month, $day );

            // Skip past dates and dates beyond max advance
            if ( $date < $today || $date > $max_date ) {
                continue;
            }

            // Check if date has available slots
            if ( self::is_date_available( $date ) ) {
                $slots = self::get_available_slots( $date, $service_id, $addon_ids );
                if ( ! empty( $slots ) ) {
                    $dates[] = $date;
                }
            }
        }

        return $dates;
    }

    /**
     * Get blocked dates for calendar display.
     *
     * @param int $year  Year.
     * @param int $month Month.
     * @return array
     */
    public static function get_blocked_dates( $year, $month ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_availability_overrides';
        $start_date = sprintf( '%d-%02d-01', $year, $month );
        $end_date = sprintf( '%d-%02d-%02d', $year, $month, cal_days_in_month( CAL_GREGORIAN, $month, $year ) );

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT override_date, reason FROM $table
                WHERE override_date BETWEEN %s AND %s
                AND type = 'block'
                AND start_time IS NULL",
                $start_date,
                $end_date
            ),
            ARRAY_A
        );

        $blocked = array();
        foreach ( $results as $row ) {
            $blocked[ $row['override_date'] ] = $row['reason'] ?: __( 'Blocked', 'glowbook' );
        }

        return $blocked;
    }
}
