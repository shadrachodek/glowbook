<?php
/**
 * Staff Availability Management.
 *
 * Handles per-staff schedules, time off, and availability calculations.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Staff_Availability class.
 */
class Sodek_GB_Staff_Availability {

    /**
     * Initialize hooks.
     */
    public static function init() {
        // Initialize default schedules for staff when marked as staff
        add_action( 'updated_user_meta', array( __CLASS__, 'maybe_init_staff_schedule' ), 10, 4 );
    }

    /**
     * Initialize staff schedule when user is marked as staff.
     *
     * @param int    $meta_id    Meta ID.
     * @param int    $user_id    User ID.
     * @param string $meta_key   Meta key.
     * @param mixed  $meta_value Meta value.
     */
    public static function maybe_init_staff_schedule( $meta_id, $user_id, $meta_key, $meta_value ) {
        if ( '_sodek_gb_is_staff' !== $meta_key || '1' !== $meta_value ) {
            return;
        }

        // Check if schedule exists
        $schedule = self::get_staff_schedule( $user_id );

        if ( empty( $schedule ) ) {
            self::init_default_schedule( $user_id );
        }
    }

    /**
     * Initialize default schedule for a staff member.
     *
     * Copies from business hours.
     *
     * @param int $staff_id Staff user ID.
     */
    public static function init_default_schedule( $staff_id ) {
        $business_schedule = Sodek_GB_Availability::get_weekly_schedule();

        foreach ( $business_schedule as $day => $hours ) {
            self::set_day_schedule( $staff_id, $day, array(
                'start_time'   => $hours['start_time'],
                'end_time'     => $hours['end_time'],
                'is_available' => $hours['is_available'],
            ) );
        }
    }

    /**
     * Get staff member's weekly schedule.
     *
     * @param int $staff_id Staff user ID.
     * @return array Schedule indexed by day of week (0-6).
     */
    public static function get_staff_schedule( $staff_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_staff_availability';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE staff_id = %d ORDER BY day_of_week ASC",
                $staff_id
            ),
            ARRAY_A
        );

        $schedule = array();
        foreach ( $results as $row ) {
            $schedule[ (int) $row['day_of_week'] ] = array(
                'start_time'   => $row['start_time'],
                'end_time'     => $row['end_time'],
                'is_available' => (bool) $row['is_available'],
            );
        }

        return $schedule;
    }

    /**
     * Set schedule for a specific day.
     *
     * @param int   $staff_id Staff user ID.
     * @param int   $day      Day of week (0=Sunday, 6=Saturday).
     * @param array $data     Schedule data (start_time, end_time, is_available).
     * @return bool
     */
    public static function set_day_schedule( $staff_id, $day, $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_staff_availability';

        // Check if exists
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE staff_id = %d AND day_of_week = %d",
                $staff_id,
                $day
            )
        );

        if ( $existing ) {
            return (bool) $wpdb->update(
                $table,
                array(
                    'start_time'   => $data['start_time'],
                    'end_time'     => $data['end_time'],
                    'is_available' => $data['is_available'] ? 1 : 0,
                ),
                array(
                    'staff_id'    => $staff_id,
                    'day_of_week' => $day,
                )
            );
        }

        return (bool) $wpdb->insert(
            $table,
            array(
                'staff_id'     => $staff_id,
                'day_of_week'  => $day,
                'start_time'   => $data['start_time'],
                'end_time'     => $data['end_time'],
                'is_available' => $data['is_available'] ? 1 : 0,
            )
        );
    }

    /**
     * Update full weekly schedule for staff.
     *
     * @param int   $staff_id Staff user ID.
     * @param array $schedule Schedule data indexed by day of week.
     * @return bool
     */
    public static function update_schedule( $staff_id, $schedule ) {
        foreach ( $schedule as $day => $data ) {
            self::set_day_schedule( $staff_id, $day, $data );
        }
        return true;
    }

    /**
     * Get staff time off entries.
     *
     * @param int    $staff_id   Staff user ID.
     * @param string $start_date Optional start date filter (Y-m-d).
     * @param string $end_date   Optional end date filter (Y-m-d).
     * @return array
     */
    public static function get_time_off( $staff_id, $start_date = '', $end_date = '' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_staff_time_off';

        $where = array( "staff_id = %d" );
        $params = array( $staff_id );

        if ( $start_date ) {
            $where[] = "date_end >= %s";
            $params[] = $start_date;
        }

        if ( $end_date ) {
            $where[] = "date_start <= %s";
            $params[] = $end_date;
        }

        $where_sql = implode( ' AND ', $where );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE $where_sql ORDER BY date_start ASC",
                $params
            ),
            ARRAY_A
        );
    }

    /**
     * Add time off for staff member.
     *
     * @param int    $staff_id   Staff user ID.
     * @param string $date_start Start date (Y-m-d).
     * @param string $date_end   End date (Y-m-d).
     * @param string $reason     Optional reason.
     * @return int|false Time off ID or false.
     */
    public static function add_time_off( $staff_id, $date_start, $date_end, $reason = '' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_staff_time_off';

        $result = $wpdb->insert(
            $table,
            array(
                'staff_id'   => $staff_id,
                'date_start' => $date_start,
                'date_end'   => $date_end,
                'reason'     => $reason,
            )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Delete time off entry.
     *
     * @param int $time_off_id Time off ID.
     * @param int $staff_id    Staff user ID (for security).
     * @return bool
     */
    public static function delete_time_off( $time_off_id, $staff_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_staff_time_off';

        return (bool) $wpdb->delete(
            $table,
            array(
                'id'       => $time_off_id,
                'staff_id' => $staff_id,
            )
        );
    }

    /**
     * Check if staff is on time off for a specific date.
     *
     * @param int    $staff_id Staff user ID.
     * @param string $date     Date (Y-m-d).
     * @return bool
     */
    public static function is_on_time_off( $staff_id, $date ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_staff_time_off';

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table
                WHERE staff_id = %d
                AND %s BETWEEN date_start AND date_end",
                $staff_id,
                $date
            )
        );

        return $count > 0;
    }

    /**
     * Check if staff is available on a specific date.
     *
     * Considers weekly schedule and time off.
     *
     * @param int    $staff_id Staff user ID.
     * @param string $date     Date (Y-m-d).
     * @return bool
     */
    public static function is_available_on_date( $staff_id, $date ) {
        // Check time off first
        if ( self::is_on_time_off( $staff_id, $date ) ) {
            return false;
        }

        // Check weekly schedule
        $day_of_week = (int) Sodek_GB_Availability::create_datetime( $date )->format( 'w' );
        $schedule = self::get_staff_schedule( $staff_id );

        // If no schedule set, assume available (falls back to business hours)
        if ( empty( $schedule ) ) {
            return true;
        }

        if ( ! isset( $schedule[ $day_of_week ] ) ) {
            return false;
        }

        return $schedule[ $day_of_week ]['is_available'];
    }

    /**
     * Get staff hours for a specific date.
     *
     * @param int    $staff_id Staff user ID.
     * @param string $date     Date (Y-m-d).
     * @return array|null Array with start_time and end_time, or null if not available.
     */
    public static function get_hours_for_date( $staff_id, $date ) {
        if ( ! self::is_available_on_date( $staff_id, $date ) ) {
            return null;
        }

        $day_of_week = (int) gmdate( 'w', strtotime( $date ) );
        $schedule = self::get_staff_schedule( $staff_id );

        // Fall back to business hours if no staff-specific schedule
        if ( empty( $schedule ) || ! isset( $schedule[ $day_of_week ] ) ) {
            $business_schedule = Sodek_GB_Availability::get_weekly_schedule();
            if ( isset( $business_schedule[ $day_of_week ] ) && $business_schedule[ $day_of_week ]['is_available'] ) {
                return array(
                    'start_time' => $business_schedule[ $day_of_week ]['start_time'],
                    'end_time'   => $business_schedule[ $day_of_week ]['end_time'],
                );
            }
            return null;
        }

        if ( ! $schedule[ $day_of_week ]['is_available'] ) {
            return null;
        }

        return array(
            'start_time' => $schedule[ $day_of_week ]['start_time'],
            'end_time'   => $schedule[ $day_of_week ]['end_time'],
        );
    }

    /**
     * Get available time slots for a staff member on a date.
     *
     * @param int    $staff_id   Staff user ID.
     * @param string $date       Date (Y-m-d).
     * @param int    $service_id Service ID.
     * @param array  $addon_ids  Selected add-on IDs.
     * @return array
     */
    public static function get_available_slots( $staff_id, $date, $service_id, $addon_ids = array() ) {
        if ( Sodek_GB_Availability::is_day_fully_booked( $date ) ) {
            return array();
        }

        if ( Sodek_GB_Availability::is_service_fully_booked( $service_id, $date ) ) {
            return array();
        }

        // Check if staff is available on this date
        $hours = self::get_hours_for_date( $staff_id, $date );
        if ( ! $hours ) {
            return array();
        }

        // Get service details
        $service = Sodek_GB_Service::get_service( $service_id );
        if ( ! $service ) {
            return array();
        }

        // Check minimum booking notice
        $min_notice = (int) get_option( 'sodek_gb_min_booking_notice', 24 );
        $min_dt = Sodek_GB_Availability::create_datetime( 'now' );
        $min_dt->modify( "+{$min_notice} hours" );
        $today = Sodek_GB_Availability::current_date( 'Y-m-d' );
        $tz = Sodek_GB_Availability::get_timezone();

        // Get booked slots for this staff on this date
        $booked = self::get_staff_booked_slots( $staff_id, $date );

        $addon_ids       = Sodek_GB_Addon::validate_addons_for_service( array_map( 'absint', (array) $addon_ids ), $service_id );
        $addons_duration = Sodek_GB_Availability::get_addons_duration( $addon_ids );
        $booking_duration = (int) $service['duration'] + $addons_duration;
        $slot_duration    = $booking_duration + (int) $service['buffer_before'] + (int) $service['buffer_after'];
        $interval = (int) get_option( 'sodek_gb_time_slot_interval', 30 );

        // Generate available slots
        $slots = array();
        $current_time = Sodek_GB_Availability::create_datetime( $date . ' ' . $hours['start_time'] )->getTimestamp();
        $end_time = Sodek_GB_Availability::create_datetime( $date . ' ' . $hours['end_time'] )->getTimestamp();

        $service_start_offset = $service['buffer_before'] * 60;

        while ( $current_time + ( $slot_duration * 60 ) <= $end_time ) {
            $slot_start_dt = new DateTime( '@' . ( $current_time + $service_start_offset ) );
            $slot_start_dt->setTimezone( $tz );
            $slot_start = $slot_start_dt->format( 'H:i' );

            $slot_end_dt = new DateTime( '@' . ( $current_time + $service_start_offset + ( $booking_duration * 60 ) ) );
            $slot_end_dt->setTimezone( $tz );
            $slot_end = $slot_end_dt->format( 'H:i' );

            // Check if slot conflicts with existing bookings
            if ( ! self::slot_conflicts( $date, $current_time, $slot_duration, $booked, $service_id ) ) {
                // Check minimum booking notice for today
                if ( $today === $date ) {
                    $slot_timestamp = Sodek_GB_Availability::create_datetime( $date . ' ' . $slot_start )->getTimestamp();
                    if ( $slot_timestamp > $min_dt->getTimestamp() ) {
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
     * Get booked slots for a staff member on a date.
     *
     * @param int    $staff_id Staff user ID.
     * @param string $date     Date (Y-m-d).
     * @return array
     */
    public static function get_staff_booked_slots( $staff_id, $date ) {
        global $wpdb;

        $slots_table = $wpdb->prefix . 'sodek_gb_booked_slots';
        $posts_table = $wpdb->posts;
        $meta_table = $wpdb->postmeta;

        // Join with bookings to get staff-filtered slots
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.start_time, s.end_time, s.service_id
                FROM $slots_table s
                INNER JOIN $meta_table m ON s.booking_id = m.post_id
                WHERE s.slot_date = %s
                AND s.status NOT IN ('cancelled')
                AND m.meta_key = '_sodek_gb_staff_id'
                AND m.meta_value = %d",
                $date,
                $staff_id
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
     * @param int   $service_id    Service ID for buffer calculation.
     * @return bool
     */
    private static function slot_conflicts( $date, $slot_start, $slot_duration, $booked, $service_id ) {
        $slot_end = $slot_start + ( $slot_duration * 60 );

        foreach ( $booked as $booking ) {
            if ( empty( $booking['start_time'] ) || empty( $booking['end_time'] ) ) {
                continue;
            }

            // Get service buffers
            $buffer_before = 0;
            $buffer_after = 0;

            if ( ! empty( $booking['service_id'] ) ) {
                $service = Sodek_GB_Service::get_service( $booking['service_id'] );
                if ( $service ) {
                    $buffer_before = isset( $service['buffer_before'] ) ? intval( $service['buffer_before'] ) * 60 : 0;
                    $buffer_after = isset( $service['buffer_after'] ) ? intval( $service['buffer_after'] ) * 60 : 0;
                }
            }

            $booking_start = Sodek_GB_Availability::create_datetime( $date . ' ' . Sodek_GB_Availability::normalize_time( $booking['start_time'] ) )->getTimestamp() - $buffer_before;
            $booking_end = Sodek_GB_Availability::create_datetime( $date . ' ' . Sodek_GB_Availability::normalize_time( $booking['end_time'] ) )->getTimestamp() + $buffer_after;

            // Check overlap
            if ( $slot_start < $booking_end && $slot_end > $booking_start ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available staff for a service on a date/time.
     *
     * @param int    $service_id Service ID.
     * @param string $date       Date (Y-m-d).
     * @param string $time       Time (H:i).
     * @param array  $addon_ids  Selected add-on IDs.
     * @return array Staff user IDs.
     */
    public static function get_available_staff_for_slot( $service_id, $date, $time, $addon_ids = array() ) {
        // Get all staff who can perform this service
        $staff_list = Sodek_GB_Staff::get_staff_for_service( $service_id );
        $available = array();

        foreach ( $staff_list as $staff ) {
            $slots = self::get_available_slots( $staff['id'], $date, $service_id, $addon_ids );

            foreach ( $slots as $slot ) {
                if ( $slot['start'] === $time ) {
                    $available[] = $staff;
                    break;
                }
            }
        }

        return $available;
    }

    /**
     * Check if a specific staff member is available for a slot.
     *
     * @param int    $staff_id   Staff user ID.
     * @param int    $service_id Service ID.
     * @param string $date       Date (Y-m-d).
     * @param string $time       Time (H:i).
     * @param array  $addon_ids  Selected add-on IDs.
     * @return bool
     */
    public static function is_staff_available_for_slot( $staff_id, $service_id, $date, $time, $addon_ids = array() ) {
        $slots = self::get_available_slots( $staff_id, $date, $service_id, $addon_ids );

        foreach ( $slots as $slot ) {
            if ( $slot['start'] === $time ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get first available staff for a slot (for "Any Available" selection).
     *
     * @param int    $service_id Service ID.
     * @param string $date       Date (Y-m-d).
     * @param string $time       Time (H:i).
     * @param string $mode       Selection mode: round-robin, least-busy, first-available.
     * @param array  $addon_ids  Selected add-on IDs.
     * @return int|null Staff user ID or null.
     */
    public static function get_auto_assigned_staff( $service_id, $date, $time, $mode = '', $addon_ids = array() ) {
        if ( ! $mode ) {
            $mode = get_option( 'sodek_gb_default_staff_assignment', 'round-robin' );
        }

        $available_staff = self::get_available_staff_for_slot( $service_id, $date, $time, $addon_ids );

        if ( empty( $available_staff ) ) {
            return null;
        }

        switch ( $mode ) {
            case 'least-busy':
                return self::get_least_busy_staff( $available_staff, $date );

            case 'round-robin':
                return self::get_round_robin_staff( $available_staff, $service_id );

            case 'first-available':
            default:
                return $available_staff[0]['id'];
        }
    }

    /**
     * Get least busy staff member for a date.
     *
     * @param array  $staff_list Available staff.
     * @param string $date       Date (Y-m-d).
     * @return int Staff user ID.
     */
    private static function get_least_busy_staff( $staff_list, $date ) {
        $least_bookings = PHP_INT_MAX;
        $selected_staff = $staff_list[0]['id'];

        foreach ( $staff_list as $staff ) {
            $bookings_count = Sodek_GB_Staff::get_bookings_count( $staff['id'], 'today' );

            if ( $bookings_count < $least_bookings ) {
                $least_bookings = $bookings_count;
                $selected_staff = $staff['id'];
            }
        }

        return $selected_staff;
    }

    /**
     * Get staff using round-robin assignment.
     *
     * @param array $staff_list Available staff.
     * @param int   $service_id Service ID.
     * @return int Staff user ID.
     */
    private static function get_round_robin_staff( $staff_list, $service_id ) {
        // Get the last assigned staff for this service
        $last_assigned = get_option( 'sodek_gb_last_assigned_staff_' . $service_id, 0 );

        // Find the next staff in the list
        $found_last = false;
        foreach ( $staff_list as $staff ) {
            if ( $found_last ) {
                update_option( 'sodek_gb_last_assigned_staff_' . $service_id, $staff['id'] );
                return $staff['id'];
            }

            if ( $staff['id'] == $last_assigned ) {
                $found_last = true;
            }
        }

        // If we didn't find the next one, start from beginning
        $selected = $staff_list[0]['id'];
        update_option( 'sodek_gb_last_assigned_staff_' . $service_id, $selected );

        return $selected;
    }

    /**
     * Get combined available slots from all staff for a service on a date.
     *
     * @param int    $service_id Service ID.
     * @param string $date       Date (Y-m-d).
     * @param array  $addon_ids  Selected add-on IDs.
     * @return array Unique time slots.
     */
    public static function get_combined_available_slots( $service_id, $date, $addon_ids = array() ) {
        $staff_list = Sodek_GB_Staff::get_staff_for_service( $service_id );

        if ( empty( $staff_list ) ) {
            // No staff configured, use business hours
            return Sodek_GB_Availability::get_available_slots( $date, $service_id );
        }

        $all_slots = array();

        foreach ( $staff_list as $staff ) {
            $slots = self::get_available_slots( $staff['id'], $date, $service_id, $addon_ids );

            foreach ( $slots as $slot ) {
                $key = $slot['start'];
                if ( ! isset( $all_slots[ $key ] ) ) {
                    $all_slots[ $key ] = $slot;
                }
            }
        }

        // Sort by time and return values
        ksort( $all_slots );
        return array_values( $all_slots );
    }

    /**
     * Get staff availability summary for admin display.
     *
     * @param int $staff_id Staff user ID.
     * @return array
     */
    public static function get_availability_summary( $staff_id ) {
        $schedule = self::get_staff_schedule( $staff_id );
        $days = array(
            0 => __( 'Sunday', 'glowbook' ),
            1 => __( 'Monday', 'glowbook' ),
            2 => __( 'Tuesday', 'glowbook' ),
            3 => __( 'Wednesday', 'glowbook' ),
            4 => __( 'Thursday', 'glowbook' ),
            5 => __( 'Friday', 'glowbook' ),
            6 => __( 'Saturday', 'glowbook' ),
        );

        $summary = array();

        foreach ( $days as $day_num => $day_name ) {
            if ( isset( $schedule[ $day_num ] ) && $schedule[ $day_num ]['is_available'] ) {
                $start = date_i18n( get_option( 'time_format' ), strtotime( $schedule[ $day_num ]['start_time'] ) );
                $end = date_i18n( get_option( 'time_format' ), strtotime( $schedule[ $day_num ]['end_time'] ) );
                $summary[ $day_name ] = "$start - $end";
            } else {
                $summary[ $day_name ] = __( 'Off', 'glowbook' );
            }
        }

        return $summary;
    }
}
