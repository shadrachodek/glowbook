<?php
/**
 * Standalone Booking Orchestrator.
 *
 * Main class for coordinating the standalone booking experience.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Standalone_Booking class.
 */
class Sodek_GB_Standalone_Booking {

    /**
     * Singleton instance.
     *
     * @var Sodek_GB_Standalone_Booking
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return Sodek_GB_Standalone_Booking
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the standalone booking system.
     */
    public static function init() {
        $instance = self::instance();

        // Register rewrite rules
        add_action( 'init', array( $instance, 'add_rewrite_rules' ), 10 );
        add_filter( 'query_vars', array( $instance, 'add_query_vars' ) );
        add_action( 'template_redirect', array( $instance, 'handle_page_requests' ) );

        // Register AJAX handlers
        add_action( 'wp_ajax_sodek_gb_standalone_booking', array( $instance, 'handle_booking_submission' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_standalone_booking', array( $instance, 'handle_booking_submission' ) );

        add_action( 'wp_ajax_sodek_gb_get_staff_slots', array( $instance, 'get_staff_available_slots' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_get_staff_slots', array( $instance, 'get_staff_available_slots' ) );
        add_action( 'wp_ajax_sodek_gb_get_service_addons', array( $instance, 'get_service_addons' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_get_service_addons', array( $instance, 'get_service_addons' ) );
        add_action( 'wp_ajax_sodek_gb_check_customer_status', array( $instance, 'check_customer_status' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_check_customer_status', array( $instance, 'check_customer_status' ) );

        // Enqueue scripts on booking page
        add_action( 'wp_enqueue_scripts', array( $instance, 'maybe_enqueue_scripts' ) );

        // Initialize sub-components
        Sodek_GB_Booking_Page::init();
        Sodek_GB_Confirmation_Page::init();
        Sodek_GB_Customer_Portal::init();
    }

    /**
     * Check if standalone mode is enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return (bool) get_option( 'sodek_gb_standalone_enabled', true );
    }

    /**
     * Add rewrite rules for booking pages.
     */
    public function add_rewrite_rules() {
        $booking_slug = get_option( 'sodek_gb_booking_slug', 'book' );
        $portal_slug = get_option( 'sodek_gb_portal_slug', 'my-appointments' );

        // Booking page: /book/
        add_rewrite_rule(
            '^' . $booking_slug . '/?$',
            'index.php?sodek_gb_page=booking',
            'top'
        );

        // Confirmation page: /book/confirmation/{key}/
        add_rewrite_rule(
            '^' . $booking_slug . '/confirmation/([^/]+)/?$',
            'index.php?sodek_gb_page=confirmation&sodek_gb_key=$matches[1]',
            'top'
        );

        // Customer portal: /my-appointments/
        add_rewrite_rule(
            '^' . $portal_slug . '/?$',
            'index.php?sodek_gb_page=portal',
            'top'
        );

        // Portal sub-pages
        add_rewrite_rule(
            '^' . $portal_slug . '/([^/]+)/?$',
            'index.php?sodek_gb_page=portal&sodek_gb_action=$matches[1]',
            'top'
        );

        // Portal booking detail: /my-appointments/booking/{id}/
        add_rewrite_rule(
            '^' . $portal_slug . '/booking/([0-9]+)/?$',
            'index.php?sodek_gb_page=portal&sodek_gb_action=booking&sodek_gb_booking_id=$matches[1]',
            'top'
        );
    }

    /**
     * Add query variables.
     *
     * @param array $vars Query variables.
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'sodek_gb_page';
        $vars[] = 'sodek_gb_key';
        $vars[] = 'sodek_gb_action';
        $vars[] = 'sodek_gb_booking_id';
        return $vars;
    }

    /**
     * Handle page requests.
     */
    public function handle_page_requests() {
        $page = get_query_var( 'sodek_gb_page' );

        if ( ! $page ) {
            return;
        }

        switch ( $page ) {
            case 'booking':
                Sodek_GB_Booking_Page::render();
                exit;

            case 'confirmation':
                Sodek_GB_Confirmation_Page::render();
                exit;

            case 'portal':
                Sodek_GB_Customer_Portal::render();
                exit;
        }
    }

    /**
     * Maybe enqueue scripts on booking pages.
     */
    public function maybe_enqueue_scripts() {
        $page = get_query_var( 'sodek_gb_page' );

        if ( ! $page ) {
            return;
        }

        $standalone_style_version  = file_exists( SODEK_GB_PLUGIN_DIR . 'public/css/standalone-booking.css' )
            ? (string) filemtime( SODEK_GB_PLUGIN_DIR . 'public/css/standalone-booking.css' )
            : SODEK_GB_VERSION;
        $standalone_script_version = file_exists( SODEK_GB_PLUGIN_DIR . 'public/js/standalone-booking.js' )
            ? (string) filemtime( SODEK_GB_PLUGIN_DIR . 'public/js/standalone-booking.js' )
            : SODEK_GB_VERSION;

        // Common styles
        wp_enqueue_style(
            'sodek-gb-standalone',
            SODEK_GB_PLUGIN_URL . 'public/css/standalone-booking.css',
            array(),
            $standalone_style_version
        );

        // Common scripts
        wp_enqueue_script(
            'sodek-gb-standalone',
            SODEK_GB_PLUGIN_URL . 'public/js/standalone-booking.js',
            array( 'jquery' ),
            $standalone_script_version,
            true
        );

        // Localize script
        wp_localize_script( 'sodek-gb-standalone', 'sodekGBStandalone', $this->get_js_config() );

        // Page-specific assets
        if ( 'booking' === $page ) {
            $this->enqueue_booking_assets();
        } elseif ( 'portal' === $page ) {
            $this->enqueue_portal_assets();
        }
    }

    /**
     * Enqueue booking page specific assets.
     */
    private function enqueue_booking_assets() {
        // Staff selector
        wp_enqueue_script(
            'sodek-gb-staff-selector',
            SODEK_GB_PLUGIN_URL . 'public/js/staff-selector.js',
            array( 'jquery', 'sodek-gb-standalone' ),
            SODEK_GB_VERSION,
            true
        );

        // Phone verification
        wp_enqueue_script(
            'sodek-gb-phone-verification',
            SODEK_GB_PLUGIN_URL . 'public/js/phone-verification.js',
            array( 'jquery', 'sodek-gb-standalone' ),
            SODEK_GB_VERSION,
            true
        );

        // Square payment SDK
        if ( Sodek_GB_Payment_Manager::is_standalone_mode() ) {
            Sodek_GB_Payment_Manager::enqueue_payment_scripts();
        }
    }

    /**
     * Enqueue portal page specific assets.
     */
    private function enqueue_portal_assets() {
        wp_enqueue_style(
            'sodek-gb-portal',
            SODEK_GB_PLUGIN_URL . 'public/css/customer-portal.css',
            array( 'sodek-gb-standalone' ),
            SODEK_GB_VERSION
        );

        if ( class_exists( 'Sodek_GB_Payment_Manager' ) && Sodek_GB_Payment_Manager::is_standalone_mode() ) {
            Sodek_GB_Payment_Manager::enqueue_payment_scripts();
        }
    }

    /**
     * Get JavaScript configuration.
     *
     * @return array
     */
    private function get_js_config() {
        $booking_slug = get_option( 'sodek_gb_booking_slug', 'book' );
        $business_date = class_exists( 'Sodek_GB_Availability' )
            ? Sodek_GB_Availability::current_date( 'Y-m-d' )
            : current_time( 'Y-m-d' );
        $business_timezone = get_option( 'sodek_gb_timezone', '' );

        if ( empty( $business_timezone ) ) {
            $business_timezone = wp_timezone_string();
        }

        return array(
            'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
            'restUrl'         => rest_url( 'sodek-gb/v1/' ),
            'nonce'           => wp_create_nonce( 'sodek_gb_standalone_booking' ),
            'bookingNonce'    => wp_create_nonce( 'sodek_gb_standalone_booking' ),
            'paymentNonce'    => wp_create_nonce( 'sodek_gb_standalone_payment' ),
            'restNonce'       => wp_create_nonce( 'wp_rest' ),
            'bookingUrl'      => home_url( '/' . $booking_slug . '/' ),
            'confirmationUrl' => home_url( '/' . $booking_slug . '/confirmation/' ),
            'portalUrl'       => home_url( '/' . get_option( 'sodek_gb_portal_slug', 'my-appointments' ) . '/' ),
            'currency'        => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
            'currencySymbol'  => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
            'dateFormat'      => get_option( 'date_format' ),
            'timeFormat'      => get_option( 'time_format' ),
            'businessDate'    => $business_date,
            'businessTimezone'=> $business_timezone,
            'showStaffPhotos' => (bool) get_option( 'sodek_gb_show_staff_photos', true ),
            'showStaffBios'   => (bool) get_option( 'sodek_gb_show_staff_bios', true ),
            'showAnyAvailable'=> (bool) get_option( 'sodek_gb_show_any_available', true ),
            'smsEnabled'      => (bool) get_option( 'sodek_gb_sms_enabled', false ),
            'customerPaymentRulesEnabled' => (bool) get_option( 'sodek_gb_customer_payment_rules_enabled', 1 ),
            'enforceCustomerPaymentType' => (bool) get_option( 'sodek_gb_enforce_customer_payment_type', 0 ),
            'returningCustomerPaymentAmount' => (float) get_option( 'sodek_gb_returning_customer_payment_amount', 50 ),
            'newCustomerPaymentAmount' => (float) get_option( 'sodek_gb_new_customer_payment_amount', 150 ),
            'i18n'            => array(
                'selectService'   => __( 'Select a Service', 'glowbook' ),
                'selectStaff'     => __( 'Select a Stylist', 'glowbook' ),
                'anyAvailable'    => __( 'Any Available', 'glowbook' ),
                'selectDateTime'  => __( 'Select Date & Time', 'glowbook' ),
                'yourDetails'     => __( 'Your Details', 'glowbook' ),
                'payment'         => __( 'Payment', 'glowbook' ),
                'confirmation'    => __( 'Confirmation', 'glowbook' ),
                'next'            => __( 'Next', 'glowbook' ),
                'back'            => __( 'Back', 'glowbook' ),
                'bookNow'         => __( 'Book Now', 'glowbook' ),
                'processing'      => __( 'Processing...', 'glowbook' ),
                'noSlotsAvailable'=> __( 'No time slots available for this date.', 'glowbook' ),
                'selectDate'      => __( 'Please select a date.', 'glowbook' ),
                'selectTime'      => __( 'Please select a time.', 'glowbook' ),
                'requiredField'   => __( 'This field is required.', 'glowbook' ),
                'invalidEmail'    => __( 'Please enter a valid email address.', 'glowbook' ),
                'invalidPhone'    => __( 'Please enter a valid phone number.', 'glowbook' ),
                'paymentError'    => __( 'Payment failed. Please try again.', 'glowbook' ),
                'verifyPhone'     => __( 'Verify Phone', 'glowbook' ),
                'codeSent'        => __( 'Verification code sent!', 'glowbook' ),
                'enterCode'       => __( 'Enter the code sent to your phone', 'glowbook' ),
                'resendCode'      => __( 'Resend Code', 'glowbook' ),
            ),
        );
    }

    /**
     * Handle booking submission via AJAX.
     */
    public function handle_booking_submission() {
        check_ajax_referer( 'sodek_gb_standalone_booking', 'nonce' );

        // Validate required fields
        $required = array( 'service_id', 'booking_date', 'booking_time', 'customer_email', 'customer_name' );

        foreach ( $required as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                wp_send_json_error( array(
                    'message' => sprintf( __( 'Missing required field: %s', 'glowbook' ), $field ),
                    'code'    => 'missing_field',
                ) );
            }
        }

        // Sanitize input
        $service_id    = absint( $_POST['service_id'] );
        $booking_date  = sanitize_text_field( wp_unslash( $_POST['booking_date'] ) );
        $booking_time  = sanitize_text_field( wp_unslash( $_POST['booking_time'] ) );
        $staff_id      = isset( $_POST['staff_id'] ) ? absint( $_POST['staff_id'] ) : 0;
        $customer_email = sanitize_email( wp_unslash( $_POST['customer_email'] ) );
        $customer_name  = sanitize_text_field( wp_unslash( $_POST['customer_name'] ) );
        $customer_phone = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
        $notes         = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';
        $addon_ids     = isset( $_POST['addon_ids'] ) ? array_map( 'absint', (array) $_POST['addon_ids'] ) : array();
        $card_token    = isset( $_POST['card_token'] ) ? sanitize_text_field( wp_unslash( $_POST['card_token'] ) ) : '';
        $custom_deposit = isset( $_POST['custom_deposit'] ) ? floatval( $_POST['custom_deposit'] ) : 0;

        // Validate service
        $service = Sodek_GB_Service::get_service( $service_id );
        if ( ! $service ) {
            wp_send_json_error( array(
                'message' => __( 'Service not found.', 'glowbook' ),
                'code'    => 'service_not_found',
            ) );
        }

        $addon_ids = Sodek_GB_Addon::validate_addons_for_service( $addon_ids, $service_id );

        // Auto-assign staff if not selected
        if ( ! $staff_id && get_option( 'sodek_gb_show_any_available', true ) ) {
            $staff_id = Sodek_GB_Staff_Availability::get_auto_assigned_staff( $service_id, $booking_date, $booking_time );
        }

        // Validate slot availability
        if ( $staff_id ) {
            $is_available = Sodek_GB_Staff_Availability::is_staff_available_for_slot( $staff_id, $service_id, $booking_date, $booking_time );
        } else {
            $is_available = Sodek_GB_Availability::is_slot_available( $booking_date, $booking_time, $service_id );
        }

        if ( ! $is_available ) {
            wp_send_json_error( array(
                'message' => __( 'This time slot is no longer available. Please select another time.', 'glowbook' ),
                'code'    => 'slot_unavailable',
            ) );
        }

        // Get or create customer profile
        $customer = null;
        if ( $customer_phone ) {
            $customer = Sodek_GB_Customer::get_or_create_by_phone( $customer_phone, array(
                'email'      => $customer_email,
                'first_name' => explode( ' ', $customer_name )[0],
                'last_name'  => implode( ' ', array_slice( explode( ' ', $customer_name ), 1 ) ),
            ) );
        } elseif ( $customer_email ) {
            $customer = Sodek_GB_Customer::get_or_create_by_email( $customer_email, array(
                'first_name' => explode( ' ', $customer_name )[0],
                'last_name'  => implode( ' ', array_slice( explode( ' ', $customer_name ), 1 ) ),
            ) );
        }

        // Calculate totals
        $base_price = (float) $service['price'];
        $addons_price = 0;
        $addons_duration = 0;

        if ( ! empty( $addon_ids ) ) {
            foreach ( $addon_ids as $addon_id ) {
                $addon = Sodek_GB_Addon::get_addon( $addon_id );
                if ( $addon ) {
                    $addons_price += (float) $addon['price'];
                    $addons_duration += (int) $addon['duration'];
                }
            }
        }

        $total_price = $base_price + $addons_price;

        // Calculate deposit
        $deposit_type = get_post_meta( $service_id, '_sodek_gb_deposit_type', true ) ?: 'percentage';
        $deposit_value = (float) ( get_post_meta( $service_id, '_sodek_gb_deposit_value', true ) ?: 50 );

        if ( 'percentage' === $deposit_type ) {
            $min_deposit = round( $total_price * ( $deposit_value / 100 ), 2 );
        } else {
            $min_deposit = min( $deposit_value, $total_price );
        }

        // Validate custom deposit
        if ( $custom_deposit < $min_deposit ) {
            $custom_deposit = $min_deposit;
        }
        if ( $custom_deposit > $total_price ) {
            $custom_deposit = $total_price;
        }

        // Process payment if card token provided
        $payment_result = null;
        $reference_id = 'GB-' . wp_generate_password( 8, false, false );

        if ( $card_token && $custom_deposit > 0 ) {
            $verification_token = isset( $_POST['verification_token'] ) ? sanitize_text_field( wp_unslash( $_POST['verification_token'] ) ) : '';

            $payment_result = Sodek_GB_Payment_Manager::process_payment( 'square', $custom_deposit, array(
                'source_id'          => $card_token,
                'verification_token' => $verification_token,
                'customer_email'     => $customer_email,
                'reference_id'       => $reference_id,
                'note'               => sprintf(
                    __( 'Booking deposit for %1$s on %2$s at %3$s', 'glowbook' ),
                    $service['title'],
                    $booking_date,
                    $booking_time
                ),
            ) );

            if ( ! $payment_result['success'] ) {
                wp_send_json_error( array(
                    'message' => $payment_result['error']['message'] ?? __( 'Payment failed. Please try again.', 'glowbook' ),
                    'code'    => $payment_result['error']['code'] ?? 'payment_failed',
                ) );
            }
        }

        // Calculate end time
        $duration = $service['duration'] + $addons_duration;
        $end_time = gmdate( 'H:i', strtotime( $booking_time ) + ( $duration * 60 ) );

        // Create booking
        $booking_data = array(
            'service_id'      => $service_id,
            'booking_date'    => $booking_date,
            'start_time'      => $booking_time,
            'end_time'        => $end_time,
            'customer_name'   => $customer_name,
            'customer_email'  => $customer_email,
            'customer_phone'  => $customer_phone,
            'customer_id'     => $customer ? $customer['id'] : 0,
            'staff_id'        => $staff_id,
            'status'          => 'confirmed',
        );

        $booking_id = Sodek_GB_Booking::create_booking( $booking_data );

        if ( is_wp_error( $booking_id ) ) {
            wp_send_json_error( array(
                'message' => $booking_id->get_error_message(),
                'code'    => 'booking_failed',
            ) );
        }

        // Save additional booking meta
        update_post_meta( $booking_id, '_sodek_gb_customer_notes', $notes );
        update_post_meta( $booking_id, '_sodek_gb_total_price', $total_price );
        update_post_meta( $booking_id, '_sodek_gb_deposit_amount', $custom_deposit );
        update_post_meta( $booking_id, '_sodek_gb_balance_amount', $total_price - $custom_deposit );
        update_post_meta( $booking_id, '_sodek_gb_deposit_paid', $payment_result ? '1' : '0' );
        update_post_meta( $booking_id, '_sodek_gb_balance_paid', $custom_deposit >= $total_price ? '1' : '0' );
        update_post_meta( $booking_id, '_sodek_gb_transaction_reference', $reference_id );
        update_post_meta( $booking_id, '_sodek_gb_staff_id', $staff_id );

        if ( $customer ) {
            update_post_meta( $booking_id, '_sodek_gb_customer_profile_id', $customer['id'] );
        }

        // Save add-ons
        if ( ! empty( $addon_ids ) ) {
            update_post_meta( $booking_id, '_sodek_gb_addon_ids', $addon_ids );
            update_post_meta( $booking_id, '_sodek_gb_addons_total_price', $addons_price );
            update_post_meta( $booking_id, '_sodek_gb_addons_total_duration', $addons_duration );
        }

        // Save payment details
        if ( $payment_result ) {
            update_post_meta( $booking_id, '_sodek_gb_payment_id', $payment_result['data']['payment_id'] );
            update_post_meta( $booking_id, '_sodek_gb_receipt_url', $payment_result['data']['receipt_url'] );
        }

        // Update booked slot
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'sodek_gb_booked_slots',
            array(
                'booking_id'  => $booking_id,
                'slot_date'   => $booking_date,
                'start_time'  => $booking_time,
                'end_time'    => $end_time,
                'service_id'  => $service_id,
                'status'      => 'confirmed',
            )
        );

        // Update customer stats
        if ( $customer ) {
            Sodek_GB_Customer::record_booking( $customer['id'], $custom_deposit );
        }

        // Generate confirmation key
        $confirmation_key = wp_hash( $booking_id . $customer_email . $booking_date );
        update_post_meta( $booking_id, '_sodek_gb_confirmation_key', $confirmation_key );

        // Trigger booking confirmed action (sends emails)
        do_action( 'sodek_gb_booking_confirmed', $booking_id );

        // Build confirmation URL
        $booking_slug = get_option( 'sodek_gb_booking_slug', 'book' );
        $confirmation_url = home_url( "/{$booking_slug}/confirmation/{$confirmation_key}/" );

        wp_send_json_success( array(
            'message'          => __( 'Your booking has been confirmed!', 'glowbook' ),
            'booking_id'       => $booking_id,
            'confirmation_key' => $confirmation_key,
            'confirmation_url' => $confirmation_url,
            'receipt_url'      => $payment_result ? $payment_result['data']['receipt_url'] : '',
            'booking'          => array(
                'date'        => $booking_date,
                'time'        => $booking_time,
                'service'     => $service['title'],
                'staff'       => $staff_id ? Sodek_GB_Staff::get_staff( $staff_id )['name'] : '',
                'deposit'     => $custom_deposit,
                'total'       => $total_price,
                'balance'     => $total_price - $custom_deposit,
            ),
        ) );
    }

    /**
     * Get available slots for a specific staff member via AJAX.
     */
    public function get_staff_available_slots() {
        check_ajax_referer( 'sodek_gb_standalone_booking', 'nonce' );

        $service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;
        $staff_id = isset( $_POST['staff_id'] ) ? absint( $_POST['staff_id'] ) : 0;
        $date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

        if ( ! $service_id || ! $date ) {
            wp_send_json_error( array( 'message' => __( 'Missing required parameters.', 'glowbook' ) ) );
        }

        if ( $staff_id ) {
            // Get slots for specific staff
            $slots = Sodek_GB_Staff_Availability::get_available_slots( $staff_id, $date, $service_id );
        } else {
            // Get combined slots from all staff
            $slots = Sodek_GB_Staff_Availability::get_combined_available_slots( $service_id, $date );
        }

        wp_send_json_success( array(
            'slots'      => $slots,
            'date'       => $date,
            'service_id' => $service_id,
            'staff_id'   => $staff_id,
            'daily_limit' => Sodek_GB_Availability::get_daily_booking_limit( $date ),
            'daily_remaining' => Sodek_GB_Availability::get_remaining_daily_slots( $date ),
        ) );
    }

    /**
     * Get add-ons for a specific service via AJAX.
     */
    public function get_service_addons() {
        check_ajax_referer( 'sodek_gb_standalone_booking', 'nonce' );

        $service_id = isset( $_POST['service_id'] ) ? absint( $_POST['service_id'] ) : 0;

        if ( ! $service_id ) {
            wp_send_json_error( array( 'message' => __( 'Missing service ID.', 'glowbook' ) ) );
        }

        $service = Sodek_GB_Service::get_service( $service_id );
        if ( ! $service ) {
            wp_send_json_error( array( 'message' => __( 'Service not found.', 'glowbook' ) ) );
        }

        $addons = Sodek_GB_Addon::get_addons_for_service( $service_id );

        wp_send_json_success( array(
            'service_id' => $service_id,
            'count'      => count( $addons ),
            'html'       => self::render_addons_markup( $addons ),
        ) );
    }

    /**
     * Determine whether a customer should be treated as new or returning.
     */
    public function check_customer_status() {
        check_ajax_referer( 'sodek_gb_standalone_booking', 'nonce' );

        $email = isset( $_POST['customer_email'] ) ? sanitize_email( wp_unslash( $_POST['customer_email'] ) ) : '';
        $phone = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';

        $is_returning = Sodek_GB_Customer::is_returning_customer( $email, $phone );

        wp_send_json_success( array(
            'customer_type' => $is_returning ? 'returning' : 'new',
            'is_returning'  => $is_returning,
        ) );
    }

    /**
     * Render add-ons markup for a service.
     *
     * @param array $addons Add-on records.
     * @return string
     */
    public static function render_addons_markup( $addons ) {
        if ( empty( $addons ) ) {
            return '';
        }

        ob_start();

        $visible_count = 0;

        foreach ( $addons as $addon ) :
            $addon_price    = isset( $addon['price'] ) ? (float) $addon['price'] : 0;
            $addon_duration = isset( $addon['duration'] ) ? (int) $addon['duration'] : 0;
            $is_hidden      = $visible_count >= 6;
            $visible_count++;
            $addon_image      = isset( $addon['image_url'] ) ? $addon['image_url'] : '';
            $addon_image_full = isset( $addon['image_url_full'] ) ? $addon['image_url_full'] : $addon_image;
            ?>
            <label class="sodek-gb-addon-row <?php echo $is_hidden ? 'sodek-gb-addon-hidden' : ''; ?>"
                   data-addon-id="<?php echo esc_attr( $addon['id'] ); ?>"
                   data-price="<?php echo esc_attr( $addon_price ); ?>"
                   data-duration="<?php echo esc_attr( $addon_duration ); ?>">
                <input type="checkbox" name="addons[]" value="<?php echo esc_attr( $addon['id'] ); ?>">
                <?php if ( $addon_image ) : ?>
                    <span class="sodek-gb-addon-thumb sodek-gb-lightbox-trigger"
                          data-lightbox-src="<?php echo esc_url( $addon_image_full ); ?>"
                          data-lightbox-title="<?php echo esc_attr( $addon['title'] ); ?>">
                        <img src="<?php echo esc_url( $addon_image ); ?>" alt="<?php echo esc_attr( $addon['title'] ); ?>">
                    </span>
                <?php endif; ?>
                <span class="sodek-gb-addon-info">
                    <span class="sodek-gb-addon-name"><?php echo esc_html( $addon['title'] ); ?></span>
                    <span class="sodek-gb-addon-meta">
                        <?php
                        $meta_parts = array();
                        if ( $addon_duration > 0 ) {
                            $hours = floor( $addon_duration / 60 );
                            $mins  = $addon_duration % 60;
                            if ( $hours > 0 && $mins > 0 ) {
                                $meta_parts[] = sprintf( '%d hour %d minutes', $hours, $mins );
                            } elseif ( $hours > 0 ) {
                                $meta_parts[] = sprintf( '%d hour', $hours );
                            } else {
                                $meta_parts[] = sprintf( '%d minutes', $mins );
                            }
                        }
                        $meta_parts[] = '@ $' . number_format( $addon_price, 2 );
                        echo '+ ' . esc_html( implode( ' ', $meta_parts ) );
                        ?>
                    </span>
                </span>
            </label>
            <?php
        endforeach;

        if ( count( $addons ) > 6 ) :
            ?>
            <button type="button" class="sodek-gb-show-all-addons" id="sodek-gb-show-all-addons">
                <?php esc_html_e( 'SHOW ALL ADD-ONS', 'glowbook' ); ?>
            </button>
            <?php
        endif;

        return (string) ob_get_clean();
    }

    /**
     * Get booking page URL.
     *
     * @param array $args Optional query arguments.
     * @return string
     */
    public static function get_booking_url( $args = array() ) {
        // Check if booking page is set in settings
        $booking_page_id = get_option( 'sodek_gb_booking_page_id', 0 );

        if ( $booking_page_id ) {
            $url = get_permalink( $booking_page_id );
        } else {
            // Fallback to slug-based URL
            $slug = get_option( 'sodek_gb_booking_slug', 'book' );
            $url = home_url( '/' . $slug . '/' );
        }

        if ( ! empty( $args ) ) {
            $url = add_query_arg( $args, $url );
        }

        return $url;
    }

    /**
     * Get portal URL.
     *
     * @param string $action Optional action.
     * @return string
     */
    public static function get_portal_url( $action = '' ) {
        // Check if portal page is set in settings
        $portal_page_id = get_option( 'sodek_gb_portal_page_id', 0 );

        if ( $portal_page_id ) {
            $url = get_permalink( $portal_page_id );
            if ( $action ) {
                $url = add_query_arg( 'action', $action, $url );
            }
        } else {
            // Fallback to slug-based URL
            $slug = get_option( 'sodek_gb_portal_slug', 'my-appointments' );
            $url = home_url( '/' . $slug . '/' );

            if ( $action ) {
                $url .= $action . '/';
            }
        }

        return $url;
    }

    /**
     * Get confirmation URL for a booking.
     *
     * @param int $booking_id Booking ID.
     * @return string
     */
    public static function get_confirmation_url( $booking_id ) {
        $key = get_post_meta( $booking_id, '_sodek_gb_confirmation_key', true );

        if ( ! $key ) {
            $booking = Sodek_GB_Booking::get_booking( $booking_id );
            $key = wp_hash( $booking_id . $booking['customer_email'] . $booking['booking_date'] );
            update_post_meta( $booking_id, '_sodek_gb_confirmation_key', $key );
        }

        // Check if confirmation page is set in settings
        $confirmation_page_id = get_option( 'sodek_gb_confirmation_page_id', 0 );

        if ( $confirmation_page_id ) {
            return add_query_arg( 'key', $key, get_permalink( $confirmation_page_id ) );
        }

        // Fallback to slug-based URL
        $slug = get_option( 'sodek_gb_booking_slug', 'book' );
        return home_url( "/{$slug}/confirmation/{$key}/" );
    }
}
