<?php
/**
 * Shortcodes.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Shortcodes class.
 */
class Sodek_GB_Shortcodes {

    /**
     * Initialize.
     */
    public static function init() {
        add_shortcode( 'sodek_gb_booking_form', array( __CLASS__, 'booking_form' ) );
        add_shortcode( 'sodek_gb_services', array( __CLASS__, 'services_list' ) );
        add_shortcode( 'glowbook_booking', array( __CLASS__, 'standalone_booking' ) );
        add_shortcode( 'glowbook_portal', array( __CLASS__, 'customer_portal' ) );
        add_shortcode( 'glowbook_confirmation', array( __CLASS__, 'booking_confirmation' ) );
    }

    /**
     * Standalone booking page shortcode.
     *
     * Usage: [glowbook_booking]
     * This renders the full multi-step booking experience.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function standalone_booking( $atts ) {
        $atts = shortcode_atts( array(), $atts, 'glowbook_booking' );

        // Enqueue required assets
        self::enqueue_standalone_assets();

        // Get page data
        $data = self::get_booking_page_data();

        // Extract for template
        extract( $data );

        ob_start();
        include SODEK_GB_PLUGIN_DIR . 'templates/standalone/booking-page.php';
        return ob_get_clean();
    }

    /**
     * Customer portal shortcode.
     *
     * Usage: [glowbook_portal]
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function customer_portal( $atts ) {
        $atts = shortcode_atts( array(), $atts, 'glowbook_portal' );

        // Enqueue required assets
        self::enqueue_standalone_assets();
        self::enqueue_portal_assets();

        ob_start();
        Sodek_GB_Customer_Portal::render_content();
        return ob_get_clean();
    }

    /**
     * Enqueue standalone booking assets.
     */
    private static function enqueue_standalone_assets() {
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

        $staff_selector_path = SODEK_GB_PLUGIN_DIR . 'public/js/staff-selector.js';
        if ( file_exists( $staff_selector_path ) ) {
            wp_enqueue_script(
                'sodek-gb-staff-selector',
                SODEK_GB_PLUGIN_URL . 'public/js/staff-selector.js',
                array( 'jquery', 'sodek-gb-standalone' ),
                SODEK_GB_VERSION,
                true
            );
        }

        // Localize script
        $booking_slug = get_option( 'sodek_gb_booking_slug', 'book' );
        $business_date = class_exists( 'Sodek_GB_Availability' )
            ? Sodek_GB_Availability::current_date( 'Y-m-d' )
            : current_time( 'Y-m-d' );
        $business_timezone = get_option( 'sodek_gb_timezone', '' );

        if ( empty( $business_timezone ) ) {
            $business_timezone = wp_timezone_string();
        }

        // Get Square config (nested under gateways.square)
        $square_app_id = '';
        $square_location_id = '';
        if ( class_exists( 'Sodek_GB_Payment_Manager' ) ) {
            $payment_config = Sodek_GB_Payment_Manager::get_client_config();
            if ( ! empty( $payment_config['gateways']['square'] ) ) {
                $square_config = $payment_config['gateways']['square'];
                $square_app_id = isset( $square_config['applicationId'] ) ? $square_config['applicationId'] : '';
                $square_location_id = isset( $square_config['locationId'] ) ? $square_config['locationId'] : '';
            }
        }

        wp_localize_script( 'sodek-gb-standalone', 'sodekGBStandalone', array(
            'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
            'restUrl'           => rest_url( 'sodek-gb/v1/' ),
            'nonce'             => wp_create_nonce( 'sodek_gb_standalone_payment' ),
            'bookingNonce'      => wp_create_nonce( 'sodek_gb_standalone_booking' ),
            'paymentNonce'      => wp_create_nonce( 'sodek_gb_standalone_payment' ),
            'restNonce'         => wp_create_nonce( 'wp_rest' ),
            'bookingUrl'        => home_url( '/' . $booking_slug . '/' ),
            'confirmationUrl'   => home_url( '/' . $booking_slug . '/confirmation/' ),
            'portalUrl'         => home_url( '/' . get_option( 'sodek_gb_portal_slug', 'my-appointments' ) . '/' ),
            'currency'          => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
            'currencySymbol'    => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
            'dateFormat'        => get_option( 'date_format' ),
            'timeFormat'        => get_option( 'time_format' ),
            'businessDate'      => $business_date,
            'businessTimezone'  => $business_timezone,
            'minAdvanceBooking' => (int) get_option( 'sodek_gb_min_advance_booking', 0 ),
            'maxAdvanceBooking' => (int) get_option( 'sodek_gb_max_advance_booking', 60 ),
            'depositEnabled'    => (bool) get_option( 'sodek_gb_deposit_enabled', false ),
            'depositPercent'    => (int) get_option( 'sodek_gb_deposit_percent', 50 ),
            'customerPaymentRulesEnabled' => (bool) get_option( 'sodek_gb_customer_payment_rules_enabled', 1 ),
            'enforceCustomerPaymentType' => (bool) get_option( 'sodek_gb_enforce_customer_payment_type', 0 ),
            'returningCustomerPaymentAmount' => (float) get_option( 'sodek_gb_returning_customer_payment_amount', 50 ),
            'newCustomerPaymentAmount' => (float) get_option( 'sodek_gb_new_customer_payment_amount', 150 ),
            'squareAppId'       => $square_app_id,
            'squareLocationId'  => $square_location_id,
            'showStaffPhotos'   => (bool) get_option( 'sodek_gb_show_staff_photos', true ),
            'showStaffBios'     => (bool) get_option( 'sodek_gb_show_staff_bios', true ),
            'showAnyAvailable'  => (bool) get_option( 'sodek_gb_show_any_available', true ),
            'i18n'              => array(
                'selectService'    => __( 'Select a Service', 'glowbook' ),
                'selectStaff'      => __( 'Select a Stylist', 'glowbook' ),
                'anyAvailable'     => __( 'Any Available', 'glowbook' ),
                'selectDateTime'   => __( 'Select Date & Time', 'glowbook' ),
                'yourDetails'      => __( 'Your Details', 'glowbook' ),
                'payment'          => __( 'Payment', 'glowbook' ),
                'next'             => __( 'Next', 'glowbook' ),
                'back'             => __( 'Back', 'glowbook' ),
                'bookNow'          => __( 'Book Now', 'glowbook' ),
                'processing'       => __( 'Processing...', 'glowbook' ),
                'noSlotsAvailable' => __( 'No time slots available for this date.', 'glowbook' ),
            ),
        ) );

        // Square payment SDK - always enqueue for standalone booking pages
        if ( class_exists( 'Sodek_GB_Payment_Manager' ) ) {
            $square_gateway = Sodek_GB_Payment_Manager::get_gateway( 'square' );
            if ( $square_gateway ) {
                // Force enqueue Square scripts even if not "available"
                // This ensures the payment form works when using WC Square credentials
                self::force_enqueue_square_scripts( $square_gateway );
            }
        }
    }

    /**
     * Enqueue customer portal assets for shortcode usage.
     */
    private static function enqueue_portal_assets() {
        $portal_style_version  = file_exists( SODEK_GB_PLUGIN_DIR . 'public/css/customer-portal.css' )
            ? (string) filemtime( SODEK_GB_PLUGIN_DIR . 'public/css/customer-portal.css' )
            : SODEK_GB_VERSION;

        wp_enqueue_style(
            'sodek-gb-portal',
            SODEK_GB_PLUGIN_URL . 'public/css/customer-portal.css',
            array( 'sodek-gb-standalone' ),
            $portal_style_version
        );

        if ( class_exists( 'Sodek_GB_Payment_Manager' ) && Sodek_GB_Payment_Manager::is_standalone_mode() ) {
            Sodek_GB_Payment_Manager::enqueue_payment_scripts();
        }
    }

    /**
     * Force enqueue Square payment scripts.
     *
     * This bypasses the is_available() check to ensure scripts load
     * when using WooCommerce Square credentials.
     *
     * @param Sodek_GB_Square_Gateway $gateway Square gateway instance.
     */
    private static function force_enqueue_square_scripts( $gateway ) {
        // Determine SDK URL based on environment
        $environment = method_exists( $gateway, 'is_using_wc_square' ) && $gateway->is_using_wc_square()
            ? ( function_exists( 'wc_square' ) && wc_square()->get_settings_handler()->is_sandbox() ? 'sandbox' : 'production' )
            : get_option( 'sodek_gb_square_environment', 'sandbox' );

        $sdk_url = 'sandbox' === $environment
            ? 'https://sandbox.web.squarecdn.com/v1/square.js'
            : 'https://web.squarecdn.com/v1/square.js';

        // Square Web Payments SDK
        wp_enqueue_script(
            'square-web-payments-sdk',
            $sdk_url,
            array(),
            null,
            true
        );

        // Our Square integration script
        wp_enqueue_script(
            'sodek-gb-square-payment',
            SODEK_GB_PLUGIN_URL . 'public/js/square-payment.js',
            array( 'jquery', 'square-web-payments-sdk' ),
            SODEK_GB_VERSION,
            true
        );

        // Square payment styles
        wp_enqueue_style(
            'sodek-gb-square-payment',
            SODEK_GB_PLUGIN_URL . 'public/css/square-payment.css',
            array(),
            SODEK_GB_VERSION
        );

        // Get config from the gateway
        $config = $gateway->get_client_config();

        // Add SDK URL to config for debugging
        $config['sdkUrl'] = $sdk_url;
        $config['sdkEnvironment'] = $environment;

        wp_localize_script( 'sodek-gb-square-payment', 'sodekGbSquare', $config );
    }

    /**
     * Get booking page data.
     *
     * @return array
     */
    private static function get_booking_page_data() {
        // Get services grouped by category
        $services_grouped = array();
        if ( class_exists( 'Sodek_GB_Service' ) ) {
            $services_grouped = Sodek_GB_Service::get_services_grouped_by_category();
        }

        // Get all staff
        $all_staff = array();
        if ( class_exists( 'Sodek_GB_Staff' ) ) {
            $all_staff = Sodek_GB_Staff::get_active_staff();
        }

        // Pre-selected values from URL (support both 'service' and 'service_id')
        $service_id = 0;
        if ( isset( $_GET['service_id'] ) ) {
            $service_id = absint( $_GET['service_id'] );
        } elseif ( isset( $_GET['service'] ) ) {
            $service_id = absint( $_GET['service'] );
        }

        $preselected = array(
            'service_id' => $service_id,
            'staff_id'   => isset( $_GET['staff_id'] ) ? absint( $_GET['staff_id'] ) : ( isset( $_GET['staff'] ) ? absint( $_GET['staff'] ) : 0 ),
            'date'       => isset( $_GET['date'] ) ? sanitize_text_field( $_GET['date'] ) : '',
            'time'       => isset( $_GET['time'] ) ? sanitize_text_field( $_GET['time'] ) : '',
            'waitlist'   => isset( $_GET['waitlist'] ) ? absint( $_GET['waitlist'] ) : 0,
        );

        // Get customer info if logged in
        $customer = null;
        if ( is_user_logged_in() && class_exists( 'Sodek_GB_Customer' ) ) {
            $customer = Sodek_GB_Customer::get_by_user_id( get_current_user_id() );
            if ( ! $customer ) {
                $user = wp_get_current_user();
                $customer = Sodek_GB_Customer::get_by_email( $user->user_email );
            }
        }

        // Get payment configuration
        $payment_config = array( 'hasGateway' => false );
        if ( class_exists( 'Sodek_GB_Payment_Manager' ) ) {
            $payment_config = Sodek_GB_Payment_Manager::get_client_config();
        }

        // Settings
        $settings = array(
            'show_staff_selection' => true,
            'show_any_available'   => (bool) get_option( 'sodek_gb_show_any_available', true ),
            'show_staff_photos'    => (bool) get_option( 'sodek_gb_show_staff_photos', true ),
            'show_staff_bios'      => (bool) get_option( 'sodek_gb_show_staff_bios', true ),
            'allow_guest_booking'  => (bool) get_option( 'sodek_gb_allow_guest_booking', true ),
            'require_phone'        => (bool) get_option( 'sodek_gb_sms_enabled', false ),
            'enable_cards_on_file' => (bool) get_option( 'sodek_gb_enable_cards_on_file', true ),
            'deposit_enabled'      => (bool) get_option( 'sodek_gb_deposit_enabled', false ),
            'deposit_percent'      => (int) get_option( 'sodek_gb_deposit_percent', 50 ),
        );

        return array(
            'services_grouped' => $services_grouped,
            'all_staff'        => $all_staff,
            'preselected'      => $preselected,
            'customer'         => $customer,
            'payment_config'   => $payment_config,
            'settings'         => $settings,
            'nonce'            => wp_create_nonce( 'sodek_gb_standalone_booking' ),
        );
    }

    /**
     * Booking form shortcode.
     *
     * Usage: [sodek_gb_booking_form service_id="123"]
     * Or: [sodek_gb_booking_form] to show service selector first
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function booking_form( $atts ) {
        $atts = shortcode_atts( array(
            'service_id' => 0,
            'show_info'  => 'yes',
        ), $atts, 'sodek_gb_booking_form' );

        $service_id = absint( $atts['service_id'] );

        // Force load scripts
        wp_enqueue_style( 'sodek-gb-public' );
        wp_enqueue_script( 'sodek-gb-public' );

        ob_start();

        if ( $service_id ) {
            // Show form for specific service
            $service = Sodek_GB_Service::get_service( $service_id );

            if ( ! $service ) {
                echo '<p class="sodek-gb-error">' . esc_html__( 'Service not found.', 'glowbook' ) . '</p>';
            } else {
                include SODEK_GB_PLUGIN_DIR . 'templates/booking-form.php';
            }
        } else {
            // Show service selector then form
            $services = Sodek_GB_Service::get_all_services();

            if ( empty( $services ) ) {
                echo '<p class="sodek-gb-error">' . esc_html__( 'No services available for booking.', 'glowbook' ) . '</p>';
            } else {
                include SODEK_GB_PLUGIN_DIR . 'templates/service-selector.php';
            }
        }

        return ob_get_clean();
    }

    /**
     * Services list shortcode.
     *
     * Usage: [sodek_gb_services columns="3"]
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function services_list( $atts ) {
        $atts = shortcode_atts( array(
            'columns' => 3,
        ), $atts, 'sodek_gb_services' );

        $services = Sodek_GB_Service::get_all_services();

        if ( empty( $services ) ) {
            return '<p class="sodek-gb-error">' . esc_html__( 'No services available.', 'glowbook' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="sodek-gb-services-grid sodek-gb-columns-<?php echo esc_attr( $atts['columns'] ); ?>">
            <?php foreach ( $services as $service ) : ?>
            <div class="sodek-gb-service-card">
                <?php if ( $service['thumbnail'] ) : ?>
                <div class="sodek-gb-service-image">
                    <img src="<?php echo esc_url( $service['thumbnail'] ); ?>" alt="<?php echo esc_attr( $service['title'] ); ?>">
                </div>
                <?php endif; ?>

                <div class="sodek-gb-service-content">
                    <h3 class="sodek-gb-service-title"><?php echo esc_html( $service['title'] ); ?></h3>

                    <div class="sodek-gb-service-meta">
                        <span class="sodek-gb-service-duration">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <?php echo esc_html( $service['duration'] ); ?> <?php esc_html_e( 'min', 'glowbook' ); ?>
                        </span>
                        <span class="sodek-gb-service-price"><?php echo wc_price( $service['price'] ); ?></span>
                    </div>

                    <?php if ( $service['description'] ) : ?>
                    <div class="sodek-gb-service-description">
                        <?php echo wp_kses_post( wpautop( $service['description'] ) ); ?>
                    </div>
                    <?php endif; ?>

                    <div class="sodek-gb-service-deposit">
                        <?php
                        printf(
                            /* translators: %s: deposit amount */
                            esc_html__( 'Deposit: %s', 'glowbook' ),
                            wc_price( $service['deposit_amount'] )
                        );
                        ?>
                    </div>

                    <a href="<?php echo esc_url( add_query_arg( 'service', $service['id'], get_permalink() ) ); ?>" class="sodek-gb-book-button button">
                        <?php esc_html_e( 'Book Now', 'glowbook' ); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Booking confirmation shortcode.
     *
     * Usage: [glowbook_confirmation]
     * Displays booking confirmation when accessed with ?key=... parameter.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public static function booking_confirmation( $atts ) {
        $atts = shortcode_atts( array(), $atts, 'glowbook_confirmation' );

        // Get confirmation key from URL
        $key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';

        if ( empty( $key ) ) {
            return '<div class="sodek-gb-confirmation-error"><p>' . esc_html__( 'No booking confirmation key provided.', 'glowbook' ) . '</p></div>';
        }

        // Find booking by confirmation key
        global $wpdb;
        $booking_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_sodek_gb_confirmation_key'
                AND meta_value = %s
                LIMIT 1",
                $key
            )
        );

        if ( ! $booking_id ) {
            return '<div class="sodek-gb-confirmation-error"><p>' . esc_html__( 'Booking not found or confirmation link has expired.', 'glowbook' ) . '</p></div>';
        }

        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return '<div class="sodek-gb-confirmation-error"><p>' . esc_html__( 'Booking not found.', 'glowbook' ) . '</p></div>';
        }

        // Enqueue required assets
        self::enqueue_standalone_assets();

        // Get confirmation data
        $data = self::get_confirmation_data( $booking );

        // Extract for template
        extract( $data );

        ob_start();
        include SODEK_GB_PLUGIN_DIR . 'templates/standalone/confirmation.php';
        return ob_get_clean();
    }

    /**
     * Get confirmation page data.
     *
     * @param array $booking Booking data.
     * @return array
     */
    private static function get_confirmation_data( $booking ) {
        // Get staff info
        $staff = null;
        if ( ! empty( $booking['staff_id'] ) && class_exists( 'Sodek_GB_Staff' ) ) {
            $staff = Sodek_GB_Staff::get_staff( $booking['staff_id'] );
        }

        // Get payment info
        $receipt_url = get_post_meta( $booking['id'], '_sodek_gb_receipt_url', true );
        $deposit_paid = get_post_meta( $booking['id'], '_sodek_gb_deposit_paid', true );
        $balance_amount = get_post_meta( $booking['id'], '_sodek_gb_balance_amount', true );

        // Generate calendar links
        $calendar_links = self::generate_calendar_links( $booking, $staff );

        // Business info
        $business_name = get_bloginfo( 'name' );
        $business_phone = get_option( 'sodek_gb_business_phone', '' );
        $business_email = get_option( 'admin_email' );
        $business_address = get_option( 'sodek_gb_business_address', '' );

        // Portal URL. When possible, include a short-lived customer token so the
        // customer can open the portal directly from the confirmation page.
        $portal_url   = self::get_portal_url();
        $customer_id  = ! empty( $booking['customer_id'] ) ? absint( $booking['customer_id'] ) : 0;

        if ( ! $customer_id && ! empty( $booking['customer_email'] ) && class_exists( 'Sodek_GB_Customer' ) ) {
            $customer = Sodek_GB_Customer::get_by_email( $booking['customer_email'] );
            if ( $customer && ! empty( $customer['id'] ) ) {
                $customer_id = (int) $customer['id'];
            }
        }

        if ( $customer_id && class_exists( 'Sodek_GB_Customer' ) ) {
            $portal_token = Sodek_GB_Customer::generate_login_token( $customer_id );
            if ( $portal_token ) {
                $portal_url = add_query_arg( 'portal_token', $portal_token, $portal_url );
            }
        }

        return array(
            'booking'         => $booking,
            'staff'           => $staff,
            'receipt_url'     => $receipt_url,
            'deposit_paid'    => $deposit_paid,
            'balance_amount'  => (float) $balance_amount,
            'calendar_links'  => $calendar_links,
            'business_name'   => $business_name,
            'business_phone'  => $business_phone,
            'business_email'  => $business_email,
            'business_address'=> $business_address,
            'portal_url'      => $portal_url,
            'can_reschedule'  => self::can_modify_booking( $booking, 'reschedule' ),
            'can_cancel'      => self::can_modify_booking( $booking, 'cancel' ),
        );
    }

    /**
     * Generate calendar links for the booking.
     *
     * @param array      $booking Booking data.
     * @param array|null $staff   Staff data.
     * @return array
     */
    private static function generate_calendar_links( $booking, $staff ) {
        $service_name = $booking['service']['title'] ?? __( 'Appointment', 'glowbook' );
        $start_datetime = $booking['booking_date'] . 'T' . $booking['start_time'];
        $end_datetime = $booking['booking_date'] . 'T' . $booking['end_time'];

        // Convert to UTC for calendar links
        $start_utc = gmdate( 'Ymd\THis\Z', strtotime( $start_datetime ) );
        $end_utc = gmdate( 'Ymd\THis\Z', strtotime( $end_datetime ) );

        $title = sprintf(
            /* translators: 1: service name, 2: business name */
            __( '%1$s at %2$s', 'glowbook' ),
            $service_name,
            get_bloginfo( 'name' )
        );

        $description = '';
        if ( $staff ) {
            $description .= sprintf( __( 'With: %s', 'glowbook' ), $staff['name'] ) . "\n";
        }
        $description .= sprintf( __( 'Confirmation: #%d', 'glowbook' ), $booking['id'] );

        $location = get_option( 'sodek_gb_business_address', get_bloginfo( 'name' ) );

        // Google Calendar
        $google_url = add_query_arg(
            array(
                'action'   => 'TEMPLATE',
                'text'     => rawurlencode( $title ),
                'dates'    => $start_utc . '/' . $end_utc,
                'details'  => rawurlencode( $description ),
                'location' => rawurlencode( $location ),
            ),
            'https://www.google.com/calendar/render'
        );

        // Apple/iCal (.ics file)
        $ics_content = self::generate_ics_content( $title, $description, $location, $start_utc, $end_utc, $booking['id'] );
        $ics_url = 'data:text/calendar;charset=utf8,' . rawurlencode( $ics_content );

        // Outlook
        $outlook_url = add_query_arg(
            array(
                'path'      => '/calendar/action/compose',
                'rru'       => 'addevent',
                'subject'   => rawurlencode( $title ),
                'startdt'   => $start_utc,
                'enddt'     => $end_utc,
                'body'      => rawurlencode( $description ),
                'location'  => rawurlencode( $location ),
            ),
            'https://outlook.live.com/calendar/0/deeplink/compose'
        );

        return array(
            'google'  => $google_url,
            'ical'    => $ics_url,
            'outlook' => $outlook_url,
        );
    }

    /**
     * Generate ICS file content.
     *
     * @param string $title       Event title.
     * @param string $description Event description.
     * @param string $location    Event location.
     * @param string $start       Start time (UTC).
     * @param string $end         End time (UTC).
     * @param int    $booking_id  Booking ID.
     * @return string
     */
    private static function generate_ics_content( $title, $description, $location, $start, $end, $booking_id ) {
        $uid = 'booking-' . $booking_id . '@' . wp_parse_url( home_url(), PHP_URL_HOST );
        $description = str_replace( array( '\\', ',', ';', "\n" ), array( '\\\\', '\\,', '\\;', '\\n' ), $description );
        $title = str_replace( array( '\\', ',', ';', "\n" ), array( '\\\\', '\\,', '\\;', '\\n' ), $title );
        $location = str_replace( array( '\\', ',', ';', "\n" ), array( '\\\\', '\\,', '\\;', '\\n' ), $location );

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//GlowBook//Booking//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}\r\n";
        $ics .= "DTSTAMP:" . gmdate( 'Ymd\THis\Z' ) . "\r\n";
        $ics .= "DTSTART:{$start}\r\n";
        $ics .= "DTEND:{$end}\r\n";
        $ics .= "SUMMARY:{$title}\r\n";
        $ics .= "DESCRIPTION:{$description}\r\n";
        $ics .= "LOCATION:{$location}\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Check if booking can be modified.
     *
     * @param array  $booking Booking data.
     * @param string $action  Action type (reschedule or cancel).
     * @return bool
     */
    private static function can_modify_booking( $booking, $action ) {
        $option_key = 'reschedule' === $action ? 'sodek_gb_allow_reschedule' : 'sodek_gb_allow_cancel';
        $notice_key = 'reschedule' === $action ? 'sodek_gb_reschedule_notice' : 'sodek_gb_cancellation_notice';

        if ( ! get_option( $option_key, true ) ) {
            return false;
        }

        // Check status
        if ( ! in_array( $booking['status'], array( 'pending', 'confirmed' ), true ) ) {
            return false;
        }

        // Check notice period
        $notice_hours = (int) get_option( $notice_key, 24 );
        $booking_datetime = strtotime( $booking['booking_date'] . ' ' . $booking['start_time'] );

        return $booking_datetime > strtotime( "+{$notice_hours} hours" );
    }

    /**
     * Get portal URL.
     *
     * @return string
     */
    private static function get_portal_url() {
        $portal_page_id = get_option( 'sodek_gb_portal_page_id', 0 );

        if ( $portal_page_id ) {
            return get_permalink( $portal_page_id );
        }

        // Fallback to slug-based URL
        $portal_slug = get_option( 'sodek_gb_portal_slug', 'my-appointments' );
        return home_url( '/' . $portal_slug . '/' );
    }

    /**
     * Get confirmation page URL.
     *
     * @param string $key Confirmation key.
     * @return string
     */
    public static function get_confirmation_url( $key ) {
        $confirmation_page_id = get_option( 'sodek_gb_confirmation_page_id', 0 );

        if ( $confirmation_page_id ) {
            return add_query_arg( 'key', $key, get_permalink( $confirmation_page_id ) );
        }

        // Fallback to rewrite-based URL
        $booking_slug = get_option( 'sodek_gb_booking_slug', 'book' );
        return home_url( '/' . $booking_slug . '/confirmation/' . $key . '/' );
    }
}
