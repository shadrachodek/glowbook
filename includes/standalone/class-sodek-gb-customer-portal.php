<?php
/**
 * Customer Portal Handler.
 *
 * Handles the /my-appointments/ customer self-service portal.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Customer_Portal class.
 */
class Sodek_GB_Customer_Portal {

    /**
     * Current customer.
     *
     * @var array|null
     */
    private static $current_customer = null;

    /**
     * Initialize the customer portal.
     */
    public static function init() {
        // Add body class
        add_filter( 'body_class', array( __CLASS__, 'add_body_class' ) );

        // AJAX handlers
        add_action( 'wp_ajax_sodek_gb_portal_login', array( __CLASS__, 'handle_login' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_portal_login', array( __CLASS__, 'handle_login' ) );

        add_action( 'wp_ajax_sodek_gb_portal_verify', array( __CLASS__, 'handle_verify' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_portal_verify', array( __CLASS__, 'handle_verify' ) );

        add_action( 'wp_ajax_sodek_gb_portal_logout', array( __CLASS__, 'handle_logout_ajax' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_portal_logout', array( __CLASS__, 'handle_logout_ajax' ) );

        add_action( 'wp_ajax_sodek_gb_portal_reschedule', array( __CLASS__, 'handle_reschedule' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_portal_reschedule', array( __CLASS__, 'handle_reschedule' ) );

        add_action( 'wp_ajax_sodek_gb_get_reschedule_dates', array( __CLASS__, 'handle_get_reschedule_dates' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_get_reschedule_dates', array( __CLASS__, 'handle_get_reschedule_dates' ) );

        add_action( 'wp_ajax_sodek_gb_get_reschedule_times', array( __CLASS__, 'handle_get_reschedule_times' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_get_reschedule_times', array( __CLASS__, 'handle_get_reschedule_times' ) );

        add_action( 'wp_ajax_sodek_gb_portal_cancel', array( __CLASS__, 'handle_cancel' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_portal_cancel', array( __CLASS__, 'handle_cancel' ) );

        add_action( 'wp_ajax_sodek_gb_get_cancel_policy', array( __CLASS__, 'handle_get_cancel_policy' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_get_cancel_policy', array( __CLASS__, 'handle_get_cancel_policy' ) );

        add_action( 'wp_ajax_sodek_gb_portal_pay_balance', array( __CLASS__, 'handle_pay_balance' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_portal_pay_balance', array( __CLASS__, 'handle_pay_balance' ) );

        add_action( 'wp_ajax_sodek_gb_portal_delete_card', array( __CLASS__, 'handle_delete_card' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_portal_delete_card', array( __CLASS__, 'handle_delete_card' ) );

        add_action( 'wp_ajax_sodek_gb_portal_set_default_card', array( __CLASS__, 'handle_set_default_card' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_portal_set_default_card', array( __CLASS__, 'handle_set_default_card' ) );

        add_action( 'wp_ajax_sodek_gb_update_profile', array( __CLASS__, 'handle_update_profile' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_update_profile', array( __CLASS__, 'handle_update_profile' ) );

        add_action( 'wp_ajax_sodek_gb_update_preferences', array( __CLASS__, 'handle_update_preferences' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_update_preferences', array( __CLASS__, 'handle_update_preferences' ) );

        add_action( 'wp_ajax_sodek_gb_update_notifications', array( __CLASS__, 'handle_update_notifications' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_update_notifications', array( __CLASS__, 'handle_update_notifications' ) );

        // Start session for portal
        add_action( 'init', array( __CLASS__, 'maybe_start_session' ), 1 );
    }

    /**
     * Maybe start session for portal authentication.
     */
    public static function maybe_start_session() {
        if ( session_status() === PHP_SESSION_NONE && ! headers_sent() ) {
            session_start();
        }
    }

    /**
     * Add body class for portal page.
     *
     * @param array $classes Body classes.
     * @return array
     */
    public static function add_body_class( $classes ) {
        if ( get_query_var( 'sodek_gb_page' ) === 'portal' ) {
            $classes[] = 'sodek-gb-portal-page';
            $classes[] = 'sodek-gb-standalone';
        }
        return $classes;
    }

    /**
     * Render the portal page (for rewrite rule).
     */
    public static function render() {
        // Check if portal is enabled
        if ( ! get_option( 'sodek_gb_portal_enabled', true ) ) {
            wp_safe_redirect( home_url() );
            exit;
        }

        self::render_content();
    }

    /**
     * Render portal content (for shortcode).
     * Returns output via buffer, does not redirect.
     */
    public static function render_content() {
        // Check if portal is enabled
        if ( ! get_option( 'sodek_gb_portal_enabled', true ) ) {
            echo '<p>' . esc_html__( 'Customer portal is currently disabled.', 'glowbook' ) . '</p>';
            return;
        }

        // Check authentication
        $customer = self::get_authenticated_customer();

        if ( ! $customer ) {
            self::render_login();
            return;
        }

        self::$current_customer = $customer;

        // Get action from query string (for shortcode usage)
        $action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'dashboard';

        // Also check query var (for rewrite rule usage)
        if ( get_query_var( 'sodek_gb_action' ) ) {
            $action = get_query_var( 'sodek_gb_action' );
        }

        switch ( $action ) {
            case 'booking':
                self::render_booking_detail();
                break;

            case 'reschedule':
                self::render_reschedule();
                break;

            case 'cancel':
                self::render_cancel();
                break;

            case 'profile':
                self::render_profile();
                break;

            case 'logout':
                self::handle_logout();
                break;

            default:
                self::render_dashboard();
                break;
        }
    }

    /**
     * Get authenticated customer from session or login.
     *
     * @return array|null
     */
    private static function get_authenticated_customer() {
        $token = self::consume_portal_token_from_request();

        if ( $token ) {
            $customer_id = Sodek_GB_Customer::validate_login_token( $token );
            if ( $customer_id ) {
                self::persist_customer_session( $customer_id, $token );
                return Sodek_GB_Customer::get_by_id( $customer_id );
            }
        }

        if ( ! empty( $_SESSION['sodek_gb_portal_logged_out'] ) || ! empty( $_COOKIE['sodek_gb_portal_logged_out'] ) ) {
            return null;
        }

        // Check session
        if ( isset( $_SESSION['sodek_gb_customer_id'] ) ) {
            $customer = Sodek_GB_Customer::get_by_id( $_SESSION['sodek_gb_customer_id'] );
            if ( $customer ) {
                return $customer;
            }
        }

        // Check cookie token
        if ( isset( $_COOKIE['sodek_gb_portal_token'] ) ) {
            $customer_id = Sodek_GB_Customer::validate_login_token( $_COOKIE['sodek_gb_portal_token'] );
            if ( $customer_id ) {
                $_SESSION['sodek_gb_customer_id'] = $customer_id;
                return Sodek_GB_Customer::get_by_id( $customer_id );
            }
        }

        // Check if WordPress user is logged in
        if ( is_user_logged_in() ) {
            $customer = Sodek_GB_Customer::get_by_user_id( get_current_user_id() );
            if ( $customer ) {
                $_SESSION['sodek_gb_customer_id'] = $customer['id'];
                return $customer;
            }

            // Try by email
            $user = wp_get_current_user();
            $customer = Sodek_GB_Customer::get_by_email( $user->user_email );
            if ( $customer ) {
                Sodek_GB_Customer::link_to_user( $customer['id'], $user->ID );
                $_SESSION['sodek_gb_customer_id'] = $customer['id'];
                return $customer;
            }

            $customer = self::recover_customer_from_bookings( $user->user_email, '' );
            if ( $customer ) {
                Sodek_GB_Customer::link_to_user( $customer['id'], $user->ID );
                $_SESSION['sodek_gb_customer_id'] = $customer['id'];
                return $customer;
            }
        }

        return null;
    }

    /**
     * Public accessor for the current authenticated portal customer.
     *
     * Supports REST and other integration layers that need to resolve the
     * currently authenticated customer using the same portal session/token rules.
     *
     * @return array|null
     */
    public static function get_logged_in_customer() {
        return self::get_authenticated_customer();
    }

    /**
     * Render login page.
     */
    private static function render_login() {
        add_filter( 'pre_get_document_title', function() {
            return sprintf(
                /* translators: %s: site name */
                __( 'My Appointments - %s', 'glowbook' ),
                get_bloginfo( 'name' )
            );
        } );

        $data = array(
            'nonce'        => wp_create_nonce( 'sodek_gb_portal' ),
            'sms_enabled'  => (bool) get_option( 'sodek_gb_sms_enabled', false ),
        );

        self::load_template( 'portal/login', $data );
    }

    /**
     * Render dashboard.
     */
    private static function render_dashboard() {
        add_filter( 'pre_get_document_title', function() {
            return sprintf(
                /* translators: %s: site name */
                __( 'My Appointments - %s', 'glowbook' ),
                get_bloginfo( 'name' )
            );
        } );

        $customer = self::$current_customer;

        $data = array(
            'customer'          => $customer,
            'upcoming_bookings' => Sodek_GB_Customer::get_upcoming_bookings( $customer['id'] ),
            'past_bookings'     => Sodek_GB_Customer::get_past_bookings( $customer['id'], 10 ),
            'saved_cards'       => Sodek_GB_Customer::get_cards( $customer['id'] ),
            'nonce'             => wp_create_nonce( 'sodek_gb_portal' ),
            'booking_url'       => Sodek_GB_Standalone_Booking::get_booking_url(),
        );

        self::load_template( 'portal/dashboard', $data );
    }

    /**
     * Render booking detail.
     */
    private static function render_booking_detail() {
        $booking_id = get_query_var( 'sodek_gb_booking_id' );
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        // Verify ownership
        if ( ! $booking || ! self::customer_owns_booking( $booking ) ) {
            wp_safe_redirect( Sodek_GB_Standalone_Booking::get_portal_url() );
            exit;
        }

        add_filter( 'pre_get_document_title', function() use ( $booking ) {
            return sprintf(
                /* translators: 1: service name, 2: site name */
                __( '%1$s Appointment - %2$s', 'glowbook' ),
                $booking['service']['title'] ?? __( 'Your', 'glowbook' ),
                get_bloginfo( 'name' )
            );
        } );

        $staff = null;
        if ( ! empty( $booking['staff_id'] ) ) {
            $staff = Sodek_GB_Staff::get_staff( $booking['staff_id'] );
        }

        $data = array(
            'booking'        => $booking,
            'staff'          => $staff,
            'customer'       => self::$current_customer,
            'can_reschedule' => self::can_reschedule( $booking ),
            'can_cancel'     => self::can_cancel( $booking ),
            'balance_due'    => (float) get_post_meta( $booking_id, '_sodek_gb_balance_amount', true ),
            'nonce'          => wp_create_nonce( 'sodek_gb_portal' ),
        );

        self::load_template( 'portal/booking-detail', $data );
    }

    /**
     * Render reschedule page.
     */
    private static function render_reschedule() {
        $booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking || ! self::customer_owns_booking( $booking ) || ! self::can_reschedule( $booking ) ) {
            wp_safe_redirect( Sodek_GB_Standalone_Booking::get_portal_url() );
            exit;
        }

        add_filter( 'pre_get_document_title', function() {
            return sprintf(
                /* translators: %s: site name */
                __( 'Reschedule Appointment - %s', 'glowbook' ),
                get_bloginfo( 'name' )
            );
        } );

        $data = array(
            'booking'  => $booking,
            'customer' => self::$current_customer,
            'nonce'    => wp_create_nonce( 'sodek_gb_portal' ),
        );

        self::load_template( 'portal/reschedule', $data );
    }

    /**
     * Render cancel confirmation page.
     */
    private static function render_cancel() {
        $booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking || ! self::customer_owns_booking( $booking ) || ! self::can_cancel( $booking ) ) {
            wp_safe_redirect( Sodek_GB_Standalone_Booking::get_portal_url() );
            exit;
        }

        // Calculate refund/credit amount
        $cancellation_info = self::get_cancellation_info( $booking );

        add_filter( 'pre_get_document_title', function() {
            return sprintf(
                /* translators: %s: site name */
                __( 'Cancel Appointment - %s', 'glowbook' ),
                get_bloginfo( 'name' )
            );
        } );

        $data = array(
            'booking'           => $booking,
            'customer'          => self::$current_customer,
            'cancellation_info' => $cancellation_info,
            'nonce'             => wp_create_nonce( 'sodek_gb_portal' ),
        );

        self::load_template( 'portal/cancel', $data );
    }

    /**
     * Render profile page.
     */
    private static function render_profile() {
        add_filter( 'pre_get_document_title', function() {
            return sprintf(
                /* translators: %s: site name */
                __( 'My Profile - %s', 'glowbook' ),
                get_bloginfo( 'name' )
            );
        } );

        $data = array(
            'customer' => self::$current_customer,
            'nonce'    => wp_create_nonce( 'sodek_gb_portal' ),
        );

        self::load_template( 'portal/profile', $data );
    }

    /**
     * Handle logout.
     */
    private static function handle_logout() {
        unset( $_SESSION['sodek_gb_customer_id'] );
        unset( $_SESSION['sodek_gb_portal_token'] );
        $_SESSION['sodek_gb_portal_logged_out'] = 1;
        setcookie( 'sodek_gb_portal_token', '', time() - 3600, '/' );
        setcookie( 'sodek_gb_portal_logged_out', '1', time() + DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true );

        wp_safe_redirect( Sodek_GB_Standalone_Booking::get_portal_url() );
        exit;
    }

    /**
     * Handle logout via AJAX.
     */
    public static function handle_logout_ajax() {
        check_ajax_referer( 'sodek_gb_portal_logout', 'nonce' );

        unset( $_SESSION['sodek_gb_customer_id'] );
        unset( $_SESSION['sodek_gb_portal_token'] );
        $_SESSION['sodek_gb_portal_logged_out'] = 1;
        setcookie( 'sodek_gb_portal_token', '', time() - 3600, '/' );
        setcookie( 'sodek_gb_portal_logged_out', '1', time() + DAY_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN ?: '', is_ssl(), true );

        wp_send_json_success( array(
            'redirect_url' => Sodek_GB_Standalone_Booking::get_portal_url(),
        ) );
    }

    /**
     * Handle login AJAX request.
     */
    public static function handle_login() {
        check_ajax_referer( 'sodek_gb_portal', 'nonce' );

        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

        if ( ! $email && ! $phone ) {
            wp_send_json_error( array( 'message' => __( 'Please enter the email address you used when booking.', 'glowbook' ) ) );
        }

        if ( $phone && ! get_option( 'sodek_gb_sms_enabled', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Phone sign-in is not available right now. Please use your booking email address instead.', 'glowbook' ) ) );
        }

        // Find customer
        $customer = null;
        if ( $email ) {
            $customer = Sodek_GB_Customer::get_by_email( $email );
        } elseif ( $phone ) {
            $customer = Sodek_GB_Customer::get_by_phone( $phone );
        }

        if ( ! $customer ) {
            $customer = self::recover_customer_from_bookings( $email, $phone );
        }

        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'No account found with this email address. Please book an appointment first.', 'glowbook' ) ) );
        }

        // If SMS is enabled and phone provided, send verification code
        if ( $phone && get_option( 'sodek_gb_sms_enabled', false ) ) {
            $code = Sodek_GB_Customer::generate_verification_code( $customer['id'] );
            // TODO: Send SMS with code via Twilio
            // Sodek_GB_SMS::send_verification( $customer['phone'], $code );

            wp_send_json_success( array(
                'require_verification' => true,
                'customer_id'          => $customer['id'],
                'message'              => __( 'A verification code has been sent to your phone.', 'glowbook' ),
            ) );
        }

        // For email login without SMS, generate magic link or log in directly
        if ( $email ) {
            // Generate login token
            $token = Sodek_GB_Customer::generate_login_token( $customer['id'] );

            // Send magic link email
            $sent = self::send_magic_link_email( $customer, $token );

            if ( ! $sent ) {
                wp_send_json_error(
                    array(
                        'message' => __( 'We could not send your sign-in email right now. Please try again in a moment or contact support if the problem continues.', 'glowbook' ),
                    )
                );
            }

            wp_send_json_success( array(
                'require_verification' => false,
                'magic_link_sent'      => true,
                'message'              => __( 'A login link has been sent to your email address.', 'glowbook' ),
            ) );
        }
    }

    /**
     * Handle phone verification AJAX request.
     */
    public static function handle_verify() {
        check_ajax_referer( 'sodek_gb_portal', 'nonce' );

        $customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
        $code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';

        if ( ! $customer_id || ! $code ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'glowbook' ) ) );
        }

        $result = Sodek_GB_Customer::verify_code( $customer_id, $code );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        $token = Sodek_GB_Customer::generate_login_token( $customer_id );
        self::persist_customer_session( $customer_id, $token );

        wp_send_json_success( array(
            'message'     => __( 'Verification successful!', 'glowbook' ),
            'redirect_url'=> Sodek_GB_Standalone_Booking::get_portal_url(),
        ) );
    }

    /**
     * Handle reschedule AJAX request.
     */
    public static function handle_reschedule() {
        check_ajax_referer( 'sodek_gb_portal', 'nonce' );

        $customer = self::get_authenticated_customer();
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'glowbook' ) ) );
        }

        $booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
        $new_date = isset( $_POST['new_date'] ) ? sanitize_text_field( wp_unslash( $_POST['new_date'] ) ) : '';
        $new_time = isset( $_POST['new_time'] ) ? sanitize_text_field( wp_unslash( $_POST['new_time'] ) ) : '';

        if ( ! $new_date && isset( $_POST['date'] ) ) {
            $new_date = sanitize_text_field( wp_unslash( $_POST['date'] ) );
        }

        if ( ! $new_time && isset( $_POST['time'] ) ) {
            $new_time = sanitize_text_field( wp_unslash( $_POST['time'] ) );
        }

        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking || ! self::customer_owns_booking( $booking, $customer ) ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'glowbook' ) ) );
        }

        if ( ! self::can_reschedule( $booking ) ) {
            wp_send_json_error( array( 'message' => __( 'This booking cannot be rescheduled.', 'glowbook' ) ) );
        }

        // Validate new slot is available
        $staff_id = $booking['staff_id'];
        $service_id = $booking['service']['id'];
        $addon_ids = ! empty( $booking['addon_ids'] ) && is_array( $booking['addon_ids'] )
            ? array_map( 'absint', $booking['addon_ids'] )
            : array();

        if ( $staff_id ) {
            $is_available = Sodek_GB_Staff_Availability::is_staff_available_for_slot( $staff_id, $service_id, $new_date, $new_time, $addon_ids );
        } else {
            $is_available = Sodek_GB_Availability::is_slot_available( $new_date, $new_time, $service_id, $addon_ids );
        }

        if ( ! $is_available ) {
            wp_send_json_error( array( 'message' => __( 'This time slot is not available.', 'glowbook' ) ) );
        }

        // Calculate new end time in the business timezone.
        $duration     = $booking['service']['duration'] + ( $booking['addons_duration'] ?? 0 );
        $new_end_time = Sodek_GB_Availability::create_datetime( $new_date . ' ' . $new_time )
            ->modify( '+' . (int) $duration . ' minutes' )
            ->format( 'H:i' );

        // Store old info for notification
        $old_date = $booking['booking_date'];
        $old_time = $booking['start_time'];

        // Update booking
        update_post_meta( $booking_id, '_sodek_gb_booking_date', $new_date );
        update_post_meta( $booking_id, '_sodek_gb_start_time', $new_time );
        update_post_meta( $booking_id, '_sodek_gb_end_time', $new_end_time );

        // Update booked slots table
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'sodek_gb_booked_slots',
            array(
                'slot_date'  => $new_date,
                'start_time' => $new_time,
                'end_time'   => $new_end_time,
            ),
            array( 'booking_id' => $booking_id )
        );

        // Notify waitlist for old slot
        Sodek_GB_Waitlist::notify_for_slot( $old_date, $old_time, $service_id, $staff_id );

        // Notify downstream listeners with both old and new schedule details.
        do_action( 'sodek_gb_booking_rescheduled', $booking_id, $old_date, $old_time, $new_date, $new_time );

        wp_send_json_success( array(
            'message' => __( 'Your appointment has been rescheduled.', 'glowbook' ),
            'booking' => array(
                'date' => $new_date,
                'time' => $new_time,
            ),
        ) );
    }

    /**
     * Handle cancel AJAX request.
     */
    public static function handle_cancel() {
        check_ajax_referer( 'sodek_gb_portal', 'nonce' );

        $customer = self::get_authenticated_customer();
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'glowbook' ) ) );
        }

        $booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking || ! self::customer_owns_booking( $booking, $customer ) ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'glowbook' ) ) );
        }

        if ( ! self::can_cancel( $booking ) ) {
            wp_send_json_error( array( 'message' => __( 'This booking cannot be cancelled.', 'glowbook' ) ) );
        }

        // Get cancellation info
        $cancellation_info = self::get_cancellation_info( $booking );

        // Update booking status
        Sodek_GB_Booking::update_status( $booking_id, 'cancelled' );

        // Record cancellation details
        update_post_meta( $booking_id, '_sodek_gb_cancelled_at', current_time( 'mysql' ) );
        update_post_meta( $booking_id, '_sodek_gb_cancelled_by', 'customer' );
        update_post_meta( $booking_id, '_sodek_gb_cancellation_type', $cancellation_info['type'] );
        update_post_meta( $booking_id, '_sodek_gb_refund_type', $cancellation_info['refund_type'] );
        update_post_meta( $booking_id, '_sodek_gb_refund_amount', $cancellation_info['refund_amount'] );

        // Notify waitlist
        $staff_id = $booking['staff_id'];
        $service_id = $booking['service']['id'];
        Sodek_GB_Waitlist::notify_for_slot( $booking['booking_date'], $booking['start_time'], $service_id, $staff_id );

        // Notify downstream listeners with the full booking context.
        do_action( 'sodek_gb_booking_cancelled', $booking_id, $booking, 'customer' );

        wp_send_json_success( array(
            'message'     => __( 'Your appointment has been cancelled.', 'glowbook' ),
            'refund_info' => $cancellation_info,
        ) );
    }

    /**
     * Handle pay balance AJAX request.
     */
    public static function handle_pay_balance() {
        check_ajax_referer( 'sodek_gb_portal', 'nonce' );

        $customer = self::get_authenticated_customer();
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'glowbook' ) ) );
        }

        $booking_id = isset( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : 0;
        $card_token = isset( $_POST['card_token'] ) ? sanitize_text_field( wp_unslash( $_POST['card_token'] ) ) : '';
        $saved_card_id = isset( $_POST['saved_card_id'] ) ? absint( $_POST['saved_card_id'] ) : 0;
        $verification_token = isset( $_POST['verification_token'] ) ? sanitize_text_field( wp_unslash( $_POST['verification_token'] ) ) : '';

        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking || ! self::customer_owns_booking( $booking, $customer ) ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'glowbook' ) ) );
        }

        $balance = (float) get_post_meta( $booking_id, '_sodek_gb_balance_amount', true );

        if ( $balance <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'No balance due on this booking.', 'glowbook' ) ) );
        }

        $source_id = '';
        $payment_method = 'online_card';

        if ( $saved_card_id > 0 ) {
            $saved_cards = Sodek_GB_Customer::get_cards( $customer['id'] );
            foreach ( $saved_cards as $saved_card ) {
                if ( (int) $saved_card['id'] === $saved_card_id ) {
                    $source_id = ! empty( $saved_card['card_id'] ) ? sanitize_text_field( $saved_card['card_id'] ) : '';
                    $payment_method = 'saved_card';
                    break;
                }
            }

            if ( empty( $source_id ) ) {
                wp_send_json_error( array( 'message' => __( 'The selected saved card is no longer available.', 'glowbook' ) ) );
            }
        } else {
            $source_id = $card_token;
        }

        if ( empty( $source_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Please choose a saved card or enter a new card to continue.', 'glowbook' ) ) );
        }

        $square_gateway = Sodek_GB_Payment_Manager::get_gateway( 'square' );
        $environment    = $square_gateway && method_exists( $square_gateway, 'get_environment' )
            ? $square_gateway->get_environment()
            : get_option( 'sodek_gb_square_environment', 'sandbox' );
        $currency       = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'sodek_gb_currency', 'USD' );

        $transaction_id = Sodek_GB_Transaction::create(
            array(
                'gateway'          => 'square',
                'environment'      => $environment,
                'amount'           => $balance,
                'currency'         => $currency,
                'transaction_type' => Sodek_GB_Transaction::TYPE_PAYMENT,
                'status'           => Sodek_GB_Transaction::STATUS_PENDING,
                'customer_email'   => $booking['customer_email'],
                'customer_name'    => $booking['customer_name'],
                'booking_id'       => $booking_id,
                'request_data'     => array(
                    'booking_id'      => $booking_id,
                    'customer_id'     => $customer['id'],
                    'saved_card_id'   => $saved_card_id,
                    'payment_context' => 'portal_balance',
                ),
            )
        );

        // Process payment
        $result = Sodek_GB_Payment_Manager::process_payment( 'square', $balance, array(
            'source_id'          => $source_id,
            'customer_email'     => $booking['customer_email'],
            'verification_token' => $verification_token,
            'reference_id'       => 'GB-BAL-' . $booking_id,
            'note'               => sprintf( __( 'Balance payment for booking #%d', 'glowbook' ), $booking_id ),
            'metadata'           => array(
                'booking_id'      => (string) $booking_id,
                'payment_context' => 'portal_balance',
                'customer_id'     => (string) $customer['id'],
                'balance_amount'  => (string) $balance,
            ),
        ) );

        if ( ! $result['success'] ) {
            if ( $transaction_id ) {
                Sodek_GB_Transaction::update(
                    $transaction_id,
                    array(
                        'status'        => Sodek_GB_Transaction::STATUS_FAILED,
                        'error_code'    => $result['error']['code'] ?? 'payment_failed',
                        'error_message' => $result['error']['message'] ?? __( 'Payment failed.', 'glowbook' ),
                    )
                );
            }

            wp_send_json_error( array(
                'message' => $result['error']['message'] ?? __( 'Payment failed.', 'glowbook' ),
            ) );
        }

        if ( $transaction_id ) {
            Sodek_GB_Transaction::update(
                $transaction_id,
                array(
                    'status'             => Sodek_GB_Transaction::STATUS_COMPLETED,
                    'square_payment_id'  => $result['data']['payment_id'] ?? '',
                    'square_receipt_url' => $result['data']['receipt_url'] ?? '',
                    'square_card_brand'  => $result['data']['card_brand'] ?? '',
                    'square_card_last4'  => $result['data']['card_last4'] ?? '',
                    'response_data'      => $result['data'] ?? array(),
                    'booking_id'         => $booking_id,
                )
            );
        }

        // Update booking
        update_post_meta( $booking_id, '_sodek_gb_balance_amount', 0 );
        update_post_meta( $booking_id, '_sodek_gb_balance_paid', '1' );
        update_post_meta( $booking_id, '_sodek_gb_balance_paid_at', current_time( 'mysql' ) );
        update_post_meta( $booking_id, '_sodek_gb_balance_payment_id', $result['data']['payment_id'] );
        update_post_meta( $booking_id, '_sodek_gb_balance_payment_method', $payment_method );
        update_post_meta( $booking_id, '_sodek_gb_balance_received_by', 'customer_portal' );
        update_post_meta( $booking_id, '_sodek_gb_balance_receipt_url', $result['data']['receipt_url'] ?? '' );

        wp_send_json_success( array(
            'message'     => __( 'Balance paid successfully!', 'glowbook' ),
            'receipt_url' => $result['data']['receipt_url'],
        ) );
    }

    /**
     * Handle saved-card deletion from the customer portal.
     *
     * @return void
     */
    public static function handle_delete_card() {
        check_ajax_referer( 'sodek_gb_portal', 'nonce' );

        $customer = self::get_authenticated_customer();
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'glowbook' ) ) );
        }

        $card_id = isset( $_POST['card_id'] ) ? absint( $_POST['card_id'] ) : 0;
        $card    = Sodek_GB_Customer::get_card( $card_id, $customer['id'] );

        if ( ! $card ) {
            wp_send_json_error( array( 'message' => __( 'Saved card not found.', 'glowbook' ) ) );
        }

        $remote_delete = apply_filters( 'sodek_gb_delete_saved_card', null, $card, $customer['id'] );

        if ( is_wp_error( $remote_delete ) ) {
            wp_send_json_error( array( 'message' => $remote_delete->get_error_message() ) );
        }

        $deleted = Sodek_GB_Customer::delete_card( $card_id, $customer['id'] );

        if ( ! $deleted ) {
            wp_send_json_error( array( 'message' => __( 'We could not remove that card. Please try again.', 'glowbook' ) ) );
        }

        wp_send_json_success( array(
            'message' => __( 'Saved card removed.', 'glowbook' ),
        ) );
    }

    /**
     * Handle default-card updates from the customer portal.
     *
     * @return void
     */
    public static function handle_set_default_card() {
        check_ajax_referer( 'sodek_gb_portal', 'nonce' );

        $customer = self::get_authenticated_customer();
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'glowbook' ) ) );
        }

        $card_id = isset( $_POST['card_id'] ) ? absint( $_POST['card_id'] ) : 0;
        $card    = Sodek_GB_Customer::get_card( $card_id, $customer['id'] );

        if ( ! $card ) {
            wp_send_json_error( array( 'message' => __( 'Saved card not found.', 'glowbook' ) ) );
        }

        $updated = Sodek_GB_Customer::set_default_card( $card_id, $customer['id'] );

        if ( ! $updated ) {
            wp_send_json_error( array( 'message' => __( 'We could not update your default card. Please try again.', 'glowbook' ) ) );
        }

        wp_send_json_success( array(
            'message' => __( 'Default card updated.', 'glowbook' ),
        ) );
    }

    /**
     * Get reschedule dates for the selected booking.
     */
    public static function handle_get_reschedule_dates() {
        check_ajax_referer( 'sodek_gb_portal', 'nonce' );

        $customer = self::get_authenticated_customer();
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'glowbook' ) ) );
        }

        $booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
        $booking    = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking || ! self::customer_owns_booking( $booking, $customer ) || ! self::can_reschedule( $booking ) ) {
            wp_send_json_error( array( 'message' => __( 'This booking cannot be rescheduled.', 'glowbook' ) ) );
        }

        $staff_id   = $booking['staff_id'];
        $service_id = $booking['service_id'];
        $dates      = array();
        $current    = new DateTime();
        $end        = new DateTime( '+60 days' );

        while ( $current <= $end ) {
            $date_str = $current->format( 'Y-m-d' );

            if ( $staff_id ) {
                $slots = Sodek_GB_Staff_Availability::get_available_slots( $staff_id, $date_str, $service_id );
            } else {
                $slots = Sodek_GB_Availability::get_available_slots( $date_str, $service_id );
            }

            if ( ! empty( $slots ) ) {
                $dates[] = $date_str;
            }

            $current->modify( '+1 day' );
        }

        wp_send_json_success( array( 'dates' => $dates ) );
    }

    /**
     * Get reschedule times for a selected booking/date combination.
     */
    public static function handle_get_reschedule_times() {
        check_ajax_referer( 'sodek_gb_portal', 'nonce' );

        $customer = self::get_authenticated_customer();
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'glowbook' ) ) );
        }

        $booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
        $date       = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : '';
        $booking    = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking || ! self::customer_owns_booking( $booking, $customer ) || ! self::can_reschedule( $booking ) ) {
            wp_send_json_error( array( 'message' => __( 'This booking cannot be rescheduled.', 'glowbook' ) ) );
        }

        $staff_id   = $booking['staff_id'];
        $service_id = $booking['service_id'];

        if ( $staff_id ) {
            $slots = Sodek_GB_Staff_Availability::get_available_slots( $staff_id, $date, $service_id );
        } else {
            $slots = Sodek_GB_Availability::get_available_slots( $date, $service_id );
        }

        $times = array_map(
            function( $slot ) {
                $time = is_array( $slot ) ? $slot['time'] : $slot;

                return array(
                    'value' => $time,
                    'label' => date_i18n( get_option( 'time_format' ), strtotime( $time ) ),
                );
            },
            is_array( $slots ) ? $slots : array()
        );

        wp_send_json_success( array( 'times' => $times ) );
    }

    /**
     * Get cancellation policy copy for a booking.
     */
    public static function handle_get_cancel_policy() {
        check_ajax_referer( 'sodek_gb_portal', 'nonce' );

        $customer = self::get_authenticated_customer();
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'glowbook' ) ) );
        }

        $booking_id = isset( $_GET['booking_id'] ) ? absint( $_GET['booking_id'] ) : 0;
        $booking    = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking || ! self::customer_owns_booking( $booking, $customer ) ) {
            wp_send_json_error( array( 'message' => __( 'Booking not found.', 'glowbook' ) ) );
        }

        $info                = self::get_cancellation_info( $booking );
        $info['policy_text'] = $info['message'] ?? '';

        wp_send_json_success( $info );
    }

    /**
     * Update core profile details.
     */
    public static function handle_update_profile() {
        check_ajax_referer( 'sodek_gb_update_profile', 'profile_nonce' );

        $customer = self::get_authenticated_customer();
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'glowbook' ) ) );
        }

        $data = array(
            'first_name' => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
            'last_name'  => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
            'email'      => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
        );

        $updated = Sodek_GB_Customer::update( $customer['id'], $data );

        if ( ! $updated ) {
            wp_send_json_error( array( 'message' => __( 'We could not update your profile. Please try again.', 'glowbook' ) ) );
        }

        wp_send_json_success( array( 'message' => __( 'Profile updated.', 'glowbook' ) ) );
    }

    /**
     * Update customer hair/preferences fields.
     */
    public static function handle_update_preferences() {
        check_ajax_referer( 'sodek_gb_update_preferences', 'preferences_nonce' );

        $customer = self::get_authenticated_customer();
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'glowbook' ) ) );
        }

        $data = array(
            'hair_type'   => isset( $_POST['hair_type'] ) ? sanitize_text_field( wp_unslash( $_POST['hair_type'] ) ) : '',
            'hair_length' => isset( $_POST['hair_length'] ) ? sanitize_text_field( wp_unslash( $_POST['hair_length'] ) ) : '',
            'allergies'   => isset( $_POST['allergies'] ) ? sanitize_textarea_field( wp_unslash( $_POST['allergies'] ) ) : '',
            'notes'       => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
        );

        $updated = Sodek_GB_Customer::update( $customer['id'], $data );

        if ( ! $updated ) {
            wp_send_json_error( array( 'message' => __( 'We could not save your preferences. Please try again.', 'glowbook' ) ) );
        }

        wp_send_json_success( array( 'message' => __( 'Preferences saved.', 'glowbook' ) ) );
    }

    /**
     * Update customer notification preferences.
     */
    public static function handle_update_notifications() {
        check_ajax_referer( 'sodek_gb_update_notifications', 'notifications_nonce' );

        $customer = self::get_authenticated_customer();
        if ( ! $customer ) {
            wp_send_json_error( array( 'message' => __( 'Please log in to continue.', 'glowbook' ) ) );
        }

        $data = array(
            'email_opt_in' => isset( $_POST['email_opt_in'] ) ? 1 : 0,
            'sms_opt_in'   => isset( $_POST['sms_opt_in'] ) ? 1 : 0,
        );

        $updated = Sodek_GB_Customer::update( $customer['id'], $data );

        if ( ! $updated ) {
            wp_send_json_error( array( 'message' => __( 'We could not update your notification preferences. Please try again.', 'glowbook' ) ) );
        }

        wp_send_json_success( array( 'message' => __( 'Notification preferences saved.', 'glowbook' ) ) );
    }

    /**
     * Send magic link email for login.
     *
     * @param array  $customer Customer data.
     * @param string $token    Login token.
     * @return bool
     */
    private static function send_magic_link_email( $customer, $token ) {
        $login_url = add_query_arg(
            array( 'portal_token' => $token ),
            Sodek_GB_Standalone_Booking::get_portal_url()
        );

        $subject = sprintf(
            /* translators: %s: site name */
            __( 'Your login link for %s', 'glowbook' ),
            get_bloginfo( 'name' )
        );

        $customer_name = Sodek_GB_Customer::get_full_name( $customer['id'] );
        $site_name     = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $message       = sprintf(
            /* translators: 1: customer name, 2: site name, 3: login URL */
            __(
                'Hi %1$s,',
                'glowbook'
            ) . '<br><br>' .
            /* translators: %s: site name */
            esc_html__( 'Use the secure link below to sign in to your appointments portal at %s.', 'glowbook' ) . '<br><br>' .
            '<a href="%3$s" style="display:inline-block;padding:12px 20px;border-radius:999px;background:#1f1f1f;color:#ffffff;text-decoration:none;font-weight:600;">' .
            esc_html__( 'Open My Appointments', 'glowbook' ) .
            '</a><br><br>' .
            esc_html__( 'If the button does not work, copy and paste this link into your browser:', 'glowbook' ) . '<br>' .
            '<span style="word-break:break-word;color:#7a5c44;">%3$s</span><br><br>' .
            esc_html__( 'This sign-in link expires in 1 hour.', 'glowbook' ) . '<br><br>' .
            esc_html__( 'Best regards,', 'glowbook' ) . '<br>%2$s',
            esc_html( $customer_name ),
            esc_html( $site_name ),
            esc_url( $login_url )
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        return wp_mail( $customer['email'], $subject, $message, $headers );
    }

    /**
     * Consume a portal token from the query string when present.
     *
     * @return string
     */
    private static function consume_portal_token_from_request() {
        if ( ! empty( $_GET['portal_token'] ) ) {
            return sanitize_text_field( wp_unslash( $_GET['portal_token'] ) );
        }

        if ( ! empty( $_GET['magic_token'] ) ) {
            return sanitize_text_field( wp_unslash( $_GET['magic_token'] ) );
        }

        return '';
    }

    /**
     * Persist authenticated customer state in session and cookie.
     *
     * @param int    $customer_id Customer ID.
     * @param string $token       Login token.
     * @return void
     */
    private static function persist_customer_session( $customer_id, $token ) {
        $_SESSION['sodek_gb_customer_id'] = $customer_id;
        unset( $_SESSION['sodek_gb_portal_logged_out'] );

        setcookie(
            'sodek_gb_portal_token',
            $token,
            time() + WEEK_IN_SECONDS,
            COOKIEPATH ?: '/',
            COOKIE_DOMAIN ?: '',
            is_ssl(),
            true
        );

        setcookie(
            'sodek_gb_portal_logged_out',
            '',
            time() - 3600,
            COOKIEPATH ?: '/',
            COOKIE_DOMAIN ?: '',
            is_ssl(),
            true
        );
    }

    /**
     * Recover a customer profile from existing booking records.
     *
     * @param string $email Email address.
     * @param string $phone Phone number.
     * @return array|null
     */
    private static function recover_customer_from_bookings( $email = '', $phone = '' ) {
        $booking = self::find_booking_for_identifier( $email, $phone );

        if ( ! $booking ) {
            return null;
        }

        $profile_id = (int) get_post_meta( $booking['id'], '_sodek_gb_customer_profile_id', true );
        if ( $profile_id ) {
            $existing_customer = Sodek_GB_Customer::get_by_id( $profile_id );
            if ( $existing_customer ) {
                return $existing_customer;
            }
        }

        $customer_data = array(
            'email'      => $booking['customer_email'] ?: $email,
            'phone'      => $booking['customer_phone'] ?: $phone,
            'first_name' => '',
            'last_name'  => '',
        );

        if ( ! empty( $booking['customer_name'] ) ) {
            $name_parts                  = preg_split( '/\s+/', trim( $booking['customer_name'] ) );
            $customer_data['first_name'] = array_shift( $name_parts ) ?: '';
            $customer_data['last_name']  = implode( ' ', $name_parts );
        }

        if ( ! empty( $customer_data['email'] ) ) {
            $customer = Sodek_GB_Customer::get_or_create_by_email( $customer_data['email'], $customer_data );
        } elseif ( ! empty( $customer_data['phone'] ) ) {
            $customer = Sodek_GB_Customer::get_or_create_by_phone( $customer_data['phone'], $customer_data );
        } else {
            $customer = null;
        }

        if ( ! $customer ) {
            return null;
        }

        update_post_meta( $booking['id'], '_sodek_gb_customer_profile_id', $customer['id'] );

        if ( empty( get_post_meta( $booking['id'], '_sodek_gb_customer_id', true ) ) ) {
            update_post_meta( $booking['id'], '_sodek_gb_customer_id', $customer['id'] );
        }

        return $customer;
    }

    /**
     * Find the latest booking matching the provided customer identifier.
     *
     * @param string $email Email address.
     * @param string $phone Phone number.
     * @return array|false
     */
    private static function find_booking_for_identifier( $email = '', $phone = '' ) {
        $email = Sodek_GB_Customer::normalize_email( $email );
        $phone = Sodek_GB_Customer::normalize_phone( $phone );

        $meta_query = array( 'relation' => 'OR' );

        if ( ! empty( $email ) ) {
            $meta_query[] = array(
                'key'   => '_sodek_gb_customer_email',
                'value' => $email,
            );
        }

        if ( ! empty( $phone ) ) {
            $meta_query[] = array(
                'key'   => '_sodek_gb_customer_phone',
                'value' => $phone,
            );
        }

        if ( count( $meta_query ) === 1 ) {
            return false;
        }

        $query = new WP_Query( array(
            'post_type'      => 'sodek_gb_booking',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => $meta_query,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ) );

        if ( empty( $query->posts ) ) {
            return false;
        }

        return Sodek_GB_Booking::get_booking( $query->posts[0] );
    }

    /**
     * Check if customer owns a booking.
     *
     * @param array      $booking  Booking data.
     * @param array|null $customer Customer data (uses current if null).
     * @return bool
     */
    private static function customer_owns_booking( $booking, $customer = null ) {
        if ( ! $customer ) {
            $customer = self::$current_customer;
        }

        if ( ! $customer ) {
            return false;
        }

        // Check by customer profile ID
        $profile_id = get_post_meta( $booking['id'], '_sodek_gb_customer_profile_id', true );
        if ( $profile_id && (int) $profile_id === (int) $customer['id'] ) {
            return true;
        }

        // Check by email
        if ( $booking['customer_email'] && $customer['email'] && strtolower( $booking['customer_email'] ) === strtolower( $customer['email'] ) ) {
            return true;
        }

        // Check by phone
        if ( $booking['customer_phone'] && $customer['phone'] ) {
            $booking_phone = Sodek_GB_Customer::normalize_phone( $booking['customer_phone'] );
            $customer_phone = Sodek_GB_Customer::normalize_phone( $customer['phone'] );
            if ( $booking_phone === $customer_phone ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if booking can be rescheduled.
     *
     * @param array $booking Booking data.
     * @return bool
     */
    private static function can_reschedule( $booking ) {
        if ( ! get_option( 'sodek_gb_allow_reschedule', true ) ) {
            return false;
        }

        if ( ! in_array( $booking['status'], array( 'pending', 'confirmed' ), true ) ) {
            return false;
        }

        $notice_hours = (int) get_option( 'sodek_gb_reschedule_notice', 24 );
        $booking_datetime = strtotime( $booking['booking_date'] . ' ' . $booking['start_time'] );

        return $booking_datetime > strtotime( "+{$notice_hours} hours" );
    }

    /**
     * Check if booking can be cancelled.
     *
     * @param array $booking Booking data.
     * @return bool
     */
    private static function can_cancel( $booking ) {
        if ( ! get_option( 'sodek_gb_allow_cancel', true ) ) {
            return false;
        }

        if ( ! in_array( $booking['status'], array( 'pending', 'confirmed' ), true ) ) {
            return false;
        }

        $booking_datetime = strtotime( $booking['booking_date'] . ' ' . $booking['start_time'] );
        $notice_hours     = self::get_cancellation_notice_hours();

        if ( $booking_datetime <= time() ) {
            return false;
        }

        return $booking_datetime > strtotime( "+{$notice_hours} hours" );
    }

    /**
     * Get cancellation info including refund/penalty details.
     *
     * @param array $booking Booking data.
     * @return array
     */
    private static function get_cancellation_info( $booking ) {
        $notice_hours = self::get_cancellation_notice_hours();
        $booking_datetime = strtotime( $booking['booking_date'] . ' ' . $booking['start_time'] );
        $hours_until = ( $booking_datetime - time() ) / 3600;

        $deposit_paid = get_post_meta( $booking['id'], '_sodek_gb_deposit_paid', true );
        $deposit_amount = (float) get_post_meta( $booking['id'], '_sodek_gb_deposit_amount', true );
        $refund_policy = self::get_cancellation_refund_policy();

        // Late cancellation (within notice period)
        if ( $hours_until < $notice_hours ) {
            return array(
                'type'          => 'late',
                'is_late'       => true,
                'refund_type'   => 'none',
                'refund_amount' => 0,
                'penalty'       => $deposit_amount,
                'message'       => sprintf(
                    /* translators: %d: notice hours */
                    __( 'Cancellations within %d hours forfeit the deposit.', 'glowbook' ),
                    $notice_hours
                ),
            );
        }

        if ( 'partial' === $refund_policy ) {
            $partial_percent = self::get_cancellation_refund_percent();
            $refund_amount   = round( $deposit_amount * ( $partial_percent / 100 ), 2 );

            return array(
                'type'          => 'standard',
                'is_late'       => false,
                'refund_type'   => $deposit_paid ? 'refund' : 'none',
                'refund_amount' => $deposit_paid ? $refund_amount : 0,
                'penalty'       => 0,
                'message'       => $deposit_paid
                    ? sprintf(
                        /* translators: 1: refund amount, 2: percentage */
                        __( 'You will receive a %2$d%% refund (%1$s).', 'glowbook' ),
                        Sodek_GB_Booking_Page::format_price( $refund_amount ),
                        $partial_percent
                    )
                    : __( 'No charges will apply.', 'glowbook' ),
            );
        }

        if ( 'none' === $refund_policy ) {
            return array(
                'type'          => 'standard',
                'is_late'       => false,
                'refund_type'   => 'none',
                'refund_amount' => 0,
                'penalty'       => 0,
                'message'       => __( 'Your deposit is non-refundable.', 'glowbook' ),
            );
        }

        // Standard cancellation (full refund if deposit paid)
        return array(
            'type'          => 'standard',
            'is_late'       => false,
            'refund_type'   => $deposit_paid ? 'refund' : 'none',
            'refund_amount' => $deposit_paid ? $deposit_amount : 0,
            'penalty'       => 0,
            'message'       => $deposit_paid
                ? __( 'Your deposit will be refunded.', 'glowbook' )
                : __( 'No charges will apply.', 'glowbook' ),
        );
    }

    /**
     * Public wrapper so other layers can use the same customer cancellation gate.
     *
     * @param array $booking Booking data.
     * @return bool
     */
    public static function customer_can_cancel_booking( $booking ) {
        return self::can_cancel( $booking );
    }

    /**
     * Public wrapper so other layers can use the same customer cancellation policy.
     *
     * @param array $booking Booking data.
     * @return array
     */
    public static function get_customer_cancellation_info( $booking ) {
        return self::get_cancellation_info( $booking );
    }

    /**
     * Public compatibility wrapper for portal template booking actions.
     *
     * @param array  $booking Booking data.
     * @param string $action  Supported action: reschedule|cancel.
     * @return bool
     */
    public static function can_modify_booking( $booking, $action ) {
        if ( 'cancel' === $action ) {
            return self::can_cancel( $booking );
        }

        return self::can_reschedule( $booking );
    }

    /**
     * Get cancellation notice hours with backward-compatible fallback.
     *
     * @return int
     */
    private static function get_cancellation_notice_hours() {
        return (int) get_option( 'sodek_gb_cancellation_notice', get_option( 'sodek_gb_cancel_notice', 24 ) );
    }

    /**
     * Get refund policy with backward-compatible fallback.
     *
     * @return string
     */
    private static function get_cancellation_refund_policy() {
        return (string) get_option( 'sodek_gb_cancellation_refund', get_option( 'sodek_gb_cancel_refund_policy', 'full' ) );
    }

    /**
     * Get partial refund percentage with backward-compatible fallback.
     *
     * @return int
     */
    private static function get_cancellation_refund_percent() {
        return (int) get_option( 'sodek_gb_cancellation_refund_percent', get_option( 'sodek_gb_cancel_refund_percent', 50 ) );
    }

    /**
     * Load template file.
     *
     * @param string $template Template name (without .php).
     * @param array  $data     Data to pass to template.
     */
    private static function load_template( $template, $data = array() ) {
        // Allow theme override
        $theme_template = locate_template( "glowbook/{$template}.php" );

        if ( $theme_template ) {
            $template_path = $theme_template;
        } else {
            $template_path = SODEK_GB_PLUGIN_DIR . "templates/{$template}.php";
        }

        if ( ! file_exists( $template_path ) ) {
            wp_die( __( 'Template not found.', 'glowbook' ) );
        }

        // Extract data for template
        extract( $data );

        self::render_page_start();

        // Include template
        include $template_path;

        self::render_page_end();
    }

    /**
     * Render the standalone page shell start.
     */
    private static function render_page_start() {
        if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta charset="<?php bloginfo( 'charset' ); ?>" />
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <?php wp_head(); ?>
            </head>
            <body <?php body_class(); ?>>
            <?php
            wp_body_open();
            return;
        }

        get_header();
    }

    /**
     * Render the standalone page shell end.
     */
    private static function render_page_end() {
        if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
            wp_footer();
            ?>
            </body>
            </html>
            <?php
            return;
        }

        get_footer();
    }
}
