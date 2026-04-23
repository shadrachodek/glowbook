<?php
/**
 * WooCommerce My Account integration for bookings.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_My_Account class.
 */
class Sodek_GB_My_Account {

    /**
     * Endpoint slug.
     */
    const ENDPOINT = 'bookings';

    /**
     * Initialize.
     */
    public static function init() {
        // Add endpoint
        add_action( 'init', array( __CLASS__, 'add_endpoint' ) );

        // Add menu item
        add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_item' ) );

        // Add content
        add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( __CLASS__, 'endpoint_content' ) );

        // Handle actions (cancel, reschedule)
        add_action( 'init', array( __CLASS__, 'handle_actions' ) );

        // AJAX handlers
        add_action( 'wp_ajax_sodek_gb_get_available_slots', array( __CLASS__, 'ajax_get_available_slots' ) );
        add_action( 'wp_ajax_sodek_gb_reschedule_booking', array( __CLASS__, 'ajax_reschedule_booking' ) );

        // Register shortcode
        add_shortcode( 'sodek_gb_my_bookings', array( __CLASS__, 'shortcode_my_bookings' ) );
    }

    /**
     * Add endpoint.
     */
    public static function add_endpoint() {
        add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
    }

    /**
     * Add menu item.
     *
     * @param array $items Menu items.
     * @return array
     */
    public static function add_menu_item( $items ) {
        // Insert before logout
        $logout = false;
        if ( isset( $items['customer-logout'] ) ) {
            $logout = $items['customer-logout'];
            unset( $items['customer-logout'] );
        }

        $items[ self::ENDPOINT ] = __( 'My Bookings', 'glowbook' );

        if ( $logout ) {
            $items['customer-logout'] = $logout;
        }

        return $items;
    }

    /**
     * Endpoint content.
     */
    public static function endpoint_content() {
        self::render_bookings_page();
    }

    /**
     * Shortcode for my bookings.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function shortcode_my_bookings( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please log in to view your bookings.', 'glowbook' ) . '</p>';
        }

        ob_start();
        self::render_bookings_page();
        return ob_get_clean();
    }

    /**
     * Render bookings page.
     */
    private static function render_bookings_page() {
        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );

        if ( ! $user ) {
            echo '<p>' . esc_html__( 'Please log in to view your bookings.', 'glowbook' ) . '</p>';
            return;
        }

        // Get bookings for this user
        $upcoming_bookings = self::get_customer_bookings( $user->user_email, 'upcoming' );
        $past_bookings = self::get_customer_bookings( $user->user_email, 'past' );

        // Check for messages
        $message = '';
        $message_type = '';
        if ( isset( $_GET['sodek_gb_message'] ) ) {
            switch ( $_GET['sodek_gb_message'] ) {
                case 'cancelled':
                    $message = __( 'Your booking has been cancelled.', 'glowbook' );
                    $message_type = 'success';
                    break;
                case 'rescheduled':
                    $message = __( 'Your booking has been rescheduled.', 'glowbook' );
                    $message_type = 'success';
                    break;
                case 'cancel_error':
                    $message = __( 'Unable to cancel this booking. Please contact us.', 'glowbook' );
                    $message_type = 'error';
                    break;
                case 'policy_error':
                    $message = __( 'This booking cannot be cancelled due to our cancellation policy.', 'glowbook' );
                    $message_type = 'error';
                    break;
            }
        }

        // Enqueue scripts for reschedule modal
        wp_enqueue_script( 'sodek-gb-my-account', SODEK_GB_PLUGIN_URL . 'public/js/my-account.js', array( 'jquery', 'wp-api-fetch' ), SODEK_GB_VERSION, true );
        wp_localize_script( 'sodek-gb-my-account', 'sodekGbMyAccount', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'sodek_gb_my_account' ),
            'strings' => array(
                'selectDate'    => __( 'Select a new date', 'glowbook' ),
                'selectTime'    => __( 'Select a new time', 'glowbook' ),
                'loading'       => __( 'Loading...', 'glowbook' ),
                'noSlots'       => __( 'No available times on this date', 'glowbook' ),
                'confirmCancel' => __( 'Are you sure you want to cancel this booking?', 'glowbook' ),
                'confirmReschedule' => __( 'Reschedule to this time?', 'glowbook' ),
            ),
        ) );

        include SODEK_GB_PLUGIN_DIR . 'templates/my-account/bookings.php';
    }

    /**
     * Get customer bookings.
     *
     * @param string $email Customer email.
     * @param string $type  Type: 'upcoming', 'past', or 'all'.
     * @return array
     */
    public static function get_customer_bookings( $email, $type = 'all' ) {
        global $wpdb;

        $today = current_time( 'Y-m-d' );

        $args = array(
            'post_type'      => 'sodek_gb_booking',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_sodek_gb_customer_email',
                    'value' => $email,
                ),
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => '_sodek_gb_booking_date',
            'order'          => 'upcoming' === $type ? 'ASC' : 'DESC',
        );

        if ( 'upcoming' === $type ) {
            $args['meta_query'][] = array(
                'key'     => '_sodek_gb_booking_date',
                'value'   => $today,
                'compare' => '>=',
                'type'    => 'DATE',
            );
            $args['post_status'] = array( 'publish' );
            $args['meta_query'][] = array(
                'key'     => '_sodek_gb_status',
                'value'   => array( 'pending', 'confirmed' ),
                'compare' => 'IN',
            );
        } elseif ( 'past' === $type ) {
            $args['meta_query']['relation'] = 'AND';
            $args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_sodek_gb_booking_date',
                    'value'   => $today,
                    'compare' => '<',
                    'type'    => 'DATE',
                ),
                array(
                    'key'     => '_sodek_gb_status',
                    'value'   => array( 'completed', 'cancelled', 'no-show' ),
                    'compare' => 'IN',
                ),
            );
        }

        $posts = get_posts( $args );
        $bookings = array();

        foreach ( $posts as $post ) {
            $booking = Sodek_GB_Booking::get_booking( $post->ID );
            if ( $booking ) {
                $bookings[] = $booking;
            }
        }

        return $bookings;
    }

    /**
     * Handle cancel/reschedule actions.
     */
    public static function handle_actions() {
        // Handle cancel
        if ( isset( $_GET['sodek_gb_cancel_booking'] ) && isset( $_GET['_wpnonce'] ) ) {
            if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'sodek_gb_cancel_booking' ) ) {
                $booking_id = absint( $_GET['sodek_gb_cancel_booking'] );
                $result = self::cancel_booking( $booking_id );

                $redirect = remove_query_arg( array( 'sodek_gb_cancel_booking', '_wpnonce' ) );
                if ( is_wp_error( $result ) ) {
                    $message = 'policy_error' === $result->get_error_code() ? 'policy_error' : 'cancel_error';
                    $redirect = add_query_arg( 'sodek_gb_message', $message, $redirect );
                } else {
                    $redirect = add_query_arg( 'sodek_gb_message', 'cancelled', $redirect );
                }

                wp_redirect( $redirect );
                exit;
            }
        }
    }

    /**
     * Cancel a booking.
     *
     * @param int $booking_id Booking ID.
     * @return bool|WP_Error
     */
    public static function cancel_booking( $booking_id ) {
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return new WP_Error( 'invalid_booking', __( 'Invalid booking.', 'glowbook' ) );
        }

        // Verify ownership
        $user = wp_get_current_user();
        if ( $booking['customer_email'] !== $user->user_email ) {
            return new WP_Error( 'unauthorized', __( 'You are not authorized to cancel this booking.', 'glowbook' ) );
        }

        // Check cancellation policy
        $can_cancel = self::can_cancel_booking( $booking );
        if ( is_wp_error( $can_cancel ) ) {
            return $can_cancel;
        }

        // Determine if this is a late cancellation
        $is_late = is_array( $can_cancel ) && ! empty( $can_cancel['is_late'] );

        // Get refund details
        $refund_details = self::get_refund_details( $booking, $is_late );

        // If late cancellation with allow_once policy, mark as used
        if ( $is_late && 'allow_once' === get_option( 'sodek_gb_late_cancellation_policy' ) ) {
            update_user_meta( $user->ID, '_sodek_gb_used_late_cancel', current_time( 'mysql' ) );
        }

        // Cancel the booking
        Sodek_GB_Booking::update_status( $booking_id, Sodek_GB_Booking::STATUS_CANCELLED );

        // Store refund info on the booking
        update_post_meta( $booking_id, '_sodek_gb_cancellation_type', $is_late ? 'late' : 'standard' );
        update_post_meta( $booking_id, '_sodek_gb_refund_type', $refund_details['refund_type'] );
        update_post_meta( $booking_id, '_sodek_gb_refund_amount', $refund_details['refund_amount'] );
        update_post_meta( $booking_id, '_sodek_gb_credit_amount', $refund_details['credit_amount'] );
        update_post_meta( $booking_id, '_sodek_gb_cancelled_at', current_time( 'mysql' ) );
        update_post_meta( $booking_id, '_sodek_gb_cancelled_by', 'customer' );

        // Process refund/credit
        self::process_refund_or_credit( $booking_id, $booking, $refund_details );

        // Handle refund if applicable
        do_action( 'sodek_gb_booking_cancelled_by_customer', $booking_id, $booking, $refund_details );

        return true;
    }

    /**
     * Process refund or store credit.
     *
     * @param int   $booking_id     Booking ID.
     * @param array $booking        Booking data.
     * @param array $refund_details Refund details.
     */
    private static function process_refund_or_credit( $booking_id, $booking, $refund_details ) {
        if ( 'credit' === $refund_details['refund_type'] && $refund_details['credit_amount'] > 0 ) {
            // Add store credit to customer
            $user_id = get_current_user_id();
            $current_credit = (float) get_user_meta( $user_id, '_sodek_gb_store_credit', true );
            $new_credit = $current_credit + $refund_details['credit_amount'];
            update_user_meta( $user_id, '_sodek_gb_store_credit', $new_credit );

            // Log the credit
            $credit_log = get_user_meta( $user_id, '_sodek_gb_credit_log', true ) ?: array();
            $credit_log[] = array(
                'date'       => current_time( 'mysql' ),
                'amount'     => $refund_details['credit_amount'],
                'type'       => 'cancellation',
                'booking_id' => $booking_id,
            );
            update_user_meta( $user_id, '_sodek_gb_credit_log', $credit_log );

            update_post_meta( $booking_id, '_sodek_gb_credit_issued', 'yes' );
        }

        if ( 'refund' === $refund_details['refund_type'] && $refund_details['refund_amount'] > 0 ) {
            // Auto-refund via WooCommerce if order exists
            $order_id = $booking['order_id'] ?? 0;
            if ( $order_id && class_exists( 'WC_Order' ) ) {
                $order = wc_get_order( $order_id );
                if ( $order ) {
                    // Create refund request (admin will need to process)
                    update_post_meta( $booking_id, '_sodek_gb_refund_requested', 'yes' );
                    update_post_meta( $booking_id, '_sodek_gb_refund_requested_amount', $refund_details['refund_amount'] );

                    // Add order note
                    $order->add_order_note(
                        sprintf(
                            /* translators: 1: refund amount, 2: booking ID */
                            __( 'Booking #%2$d cancelled. Refund of %1$s requested.', 'glowbook' ),
                            wc_price( $refund_details['refund_amount'] ),
                            $booking_id
                        )
                    );
                }
            }
        }
    }

    /**
     * Check if booking can be cancelled based on policy.
     *
     * @param array $booking Booking data.
     * @param bool  $check_policy_only Only check if within policy (not disabled).
     * @return bool|WP_Error
     */
    public static function can_cancel_booking( $booking, $check_policy_only = false ) {
        // Check if customer cancellation is allowed
        if ( ! $check_policy_only && ! get_option( 'sodek_gb_allow_customer_cancel', 1 ) ) {
            return new WP_Error(
                'disabled',
                __( 'Online cancellations are not available. Please contact us to cancel.', 'glowbook' )
            );
        }

        $policy_hours = (int) get_option( 'sodek_gb_cancellation_notice', 24 );

        $booking_datetime = strtotime( $booking['date'] . ' ' . $booking['start_time'] );
        $now = current_time( 'timestamp' );
        $hours_until = ( $booking_datetime - $now ) / 3600;

        // Check if it's a late cancellation
        if ( $hours_until < $policy_hours ) {
            $late_policy = get_option( 'sodek_gb_late_cancellation_policy', 'no_refund' );

            // If allow_once policy, check if customer has used their one-time pass
            if ( 'allow_once' === $late_policy ) {
                $user_id = get_current_user_id();
                $used_late_cancel = get_user_meta( $user_id, '_sodek_gb_used_late_cancel', true );

                if ( $used_late_cancel ) {
                    return new WP_Error(
                        'late_policy_used',
                        sprintf(
                            /* translators: %d: number of hours */
                            __( 'Bookings must be cancelled at least %d hours in advance. You have already used your one-time late cancellation.', 'glowbook' ),
                            $policy_hours
                        )
                    );
                }
            }

            // Return a special status indicating late cancellation (not an error, but different handling)
            return array(
                'allowed'         => true,
                'is_late'         => true,
                'hours_until'     => $hours_until,
                'policy_hours'    => $policy_hours,
                'late_policy'     => $late_policy,
            );
        }

        return true;
    }

    /**
     * Get refund details based on cancellation type.
     *
     * @param array $booking     Booking data.
     * @param bool  $is_late     Whether this is a late cancellation.
     * @return array Refund details.
     */
    public static function get_refund_details( $booking, $is_late = false ) {
        $deposit_amount = floatval( $booking['deposit_amount'] );
        $deposit_paid = ! empty( $booking['deposit_paid'] );

        if ( ! $deposit_paid || $deposit_amount <= 0 ) {
            return array(
                'refund_type'   => 'none',
                'refund_amount' => 0,
                'credit_amount' => 0,
                'message'       => __( 'No deposit was paid.', 'glowbook' ),
            );
        }

        if ( $is_late ) {
            $late_policy = get_option( 'sodek_gb_late_cancellation_policy', 'no_refund' );

            switch ( $late_policy ) {
                case 'credit':
                case 'allow_once':
                    return array(
                        'refund_type'   => 'credit',
                        'refund_amount' => 0,
                        'credit_amount' => $deposit_amount,
                        'message'       => sprintf(
                            /* translators: %s: credit amount */
                            __( 'A store credit of %s will be added to your account.', 'glowbook' ),
                            wc_price( $deposit_amount )
                        ),
                    );
                default: // no_refund
                    return array(
                        'refund_type'   => 'none',
                        'refund_amount' => 0,
                        'credit_amount' => 0,
                        'message'       => __( 'Your deposit is forfeited due to late cancellation.', 'glowbook' ),
                    );
            }
        }

        // Within policy cancellation
        $refund_policy = get_option( 'sodek_gb_cancellation_refund_policy', 'full' );

        switch ( $refund_policy ) {
            case 'full':
                return array(
                    'refund_type'   => 'refund',
                    'refund_amount' => $deposit_amount,
                    'credit_amount' => 0,
                    'message'       => sprintf(
                        /* translators: %s: refund amount */
                        __( 'Your deposit of %s will be refunded.', 'glowbook' ),
                        wc_price( $deposit_amount )
                    ),
                );
            case 'partial':
                $percent = (int) get_option( 'sodek_gb_partial_refund_percent', 50 );
                $refund_amount = $deposit_amount * ( $percent / 100 );
                return array(
                    'refund_type'   => 'refund',
                    'refund_amount' => $refund_amount,
                    'credit_amount' => 0,
                    'message'       => sprintf(
                        /* translators: 1: percentage, 2: refund amount */
                        __( '%1$d%% of your deposit (%2$s) will be refunded.', 'glowbook' ),
                        $percent,
                        wc_price( $refund_amount )
                    ),
                );
            case 'credit':
                return array(
                    'refund_type'   => 'credit',
                    'refund_amount' => 0,
                    'credit_amount' => $deposit_amount,
                    'message'       => sprintf(
                        /* translators: %s: credit amount */
                        __( 'A store credit of %s will be added to your account.', 'glowbook' ),
                        wc_price( $deposit_amount )
                    ),
                );
            default: // none
                return array(
                    'refund_type'   => 'none',
                    'refund_amount' => 0,
                    'credit_amount' => 0,
                    'message'       => __( 'No refund is available per our cancellation policy.', 'glowbook' ),
                );
        }
    }

    /**
     * Check if booking can be rescheduled.
     *
     * @param array $booking Booking data.
     * @return bool|WP_Error
     */
    public static function can_reschedule_booking( $booking ) {
        // Check if customer rescheduling is allowed
        if ( ! get_option( 'sodek_gb_allow_customer_reschedule', 1 ) ) {
            return new WP_Error(
                'disabled',
                __( 'Online rescheduling is not available. Please contact us to reschedule.', 'glowbook' )
            );
        }

        // Use same notice period as cancellation
        $policy_hours = (int) get_option( 'sodek_gb_cancellation_notice', 24 );

        $booking_datetime = strtotime( $booking['date'] . ' ' . $booking['start_time'] );
        $now = current_time( 'timestamp' );
        $hours_until = ( $booking_datetime - $now ) / 3600;

        if ( $hours_until < $policy_hours ) {
            return new WP_Error(
                'policy_error',
                sprintf(
                    /* translators: %d: number of hours */
                    __( 'Bookings must be rescheduled at least %d hours in advance.', 'glowbook' ),
                    $policy_hours
                )
            );
        }

        return true;
    }

    /**
     * AJAX: Get available slots for rescheduling.
     */
    public static function ajax_get_available_slots() {
        check_ajax_referer( 'sodek_gb_my_account', 'nonce' );

        $booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
        $date = isset( $_POST['date'] ) ? sanitize_text_field( $_POST['date'] ) : '';

        if ( ! $booking_id || ! $date ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'glowbook' ) ) );
        }

        $booking = Sodek_GB_Booking::get_booking( $booking_id );
        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'glowbook' ) ) );
        }

        // Get available slots
        $slots = Sodek_GB_Availability::get_available_slots( $date, $booking['service_id'], $booking_id );

        wp_send_json_success( array( 'slots' => $slots ) );
    }

    /**
     * AJAX: Reschedule booking.
     */
    public static function ajax_reschedule_booking() {
        check_ajax_referer( 'sodek_gb_my_account', 'nonce' );

        $booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
        $new_date = isset( $_POST['new_date'] ) ? sanitize_text_field( $_POST['new_date'] ) : '';
        $new_time = isset( $_POST['new_time'] ) ? sanitize_text_field( $_POST['new_time'] ) : '';

        if ( ! $booking_id || ! $new_date || ! $new_time ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'glowbook' ) ) );
        }

        $booking = Sodek_GB_Booking::get_booking( $booking_id );
        if ( ! $booking ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'glowbook' ) ) );
        }

        // Verify ownership
        $user = wp_get_current_user();
        if ( $booking['customer_email'] !== $user->user_email ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'glowbook' ) ) );
        }

        // Check reschedule policy
        $can_reschedule = self::can_reschedule_booking( $booking );
        if ( is_wp_error( $can_reschedule ) ) {
            wp_send_json_error( array( 'message' => $can_reschedule->get_error_message() ) );
        }

        // Check if new slot is available
        if ( ! Sodek_GB_Availability::is_slot_available( $new_date, $new_time, $booking['service_id'], $booking_id ) ) {
            wp_send_json_error( array( 'message' => __( 'This time slot is no longer available.', 'glowbook' ) ) );
        }

        // Calculate new end time
        $service = Sodek_GB_Service::get_service( $booking['service_id'] );
        $start = strtotime( $new_time );
        $new_end_time = gmdate( 'H:i', $start + ( $service['duration'] * 60 ) );

        // Store old date/time for notification
        $old_date = $booking['booking_date'];
        $old_time = $booking['start_time'];

        // Update booking
        update_post_meta( $booking_id, '_sodek_gb_booking_date', $new_date );
        update_post_meta( $booking_id, '_sodek_gb_start_time', $new_time );
        update_post_meta( $booking_id, '_sodek_gb_end_time', $new_end_time );

        // Update booked slots table
        global $wpdb;
        $table = $wpdb->prefix . 'sodek_gb_booked_slots';
        $wpdb->update(
            $table,
            array(
                'booking_date' => $new_date,
                'start_time'   => $new_time,
                'end_time'     => $new_end_time,
            ),
            array( 'booking_id' => $booking_id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        // Trigger reschedule action
        do_action( 'sodek_gb_booking_rescheduled', $booking_id, $old_date, $old_time, $new_date, $new_time );

        wp_send_json_success( array(
            'message' => __( 'Booking rescheduled successfully!', 'glowbook' ),
            'new_date' => date_i18n( get_option( 'date_format' ), strtotime( $new_date ) ),
            'new_time' => date_i18n( get_option( 'time_format' ), strtotime( $new_time ) ),
        ) );
    }
}
