<?php
/**
 * Plugin activation handler.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Activator class.
 */
class Sodek_GB_Activator {

    /**
     * Database version.
     *
     * @var string
     */
    const DB_VERSION = '2.0.0';

    /**
     * Activate the plugin.
     */
    public static function activate() {
        self::create_tables();
        self::create_default_options();
        self::create_pages();
        self::schedule_cron_events();

        if ( ! class_exists( 'Sodek_GB_Admin' ) && file_exists( SODEK_GB_PLUGIN_DIR . 'admin/class-sodek-gb-admin.php' ) ) {
            require_once SODEK_GB_PLUGIN_DIR . 'admin/class-sodek-gb-admin.php';
        }

        if ( class_exists( 'Sodek_GB_Admin' ) && method_exists( 'Sodek_GB_Admin', 'ensure_booking_admin_role' ) ) {
            Sodek_GB_Admin::ensure_booking_admin_role();
        }

        // Flush rewrite rules after CPT registration
        flush_rewrite_rules();

        // Store plugin version
        update_option( 'sodek_gb_version', SODEK_GB_VERSION );
        update_option( 'sodek_gb_db_version', self::DB_VERSION );
    }

    /**
     * Create required pages.
     * Public so it can be called to create missing pages.
     */
    public static function create_pages() {
        $pages = array(
            'booking' => array(
                'title'   => __( 'Book an Appointment', 'glowbook' ),
                'content' => '<!-- wp:shortcode -->[glowbook_booking]<!-- /wp:shortcode -->',
                'option'  => 'sodek_gb_booking_page_id',
                'slug'    => 'book',
            ),
            'portal' => array(
                'title'   => __( 'My Appointments', 'glowbook' ),
                'content' => '<!-- wp:shortcode -->[glowbook_portal]<!-- /wp:shortcode -->',
                'option'  => 'sodek_gb_portal_page_id',
                'slug'    => 'my-appointments',
            ),
        );

        foreach ( $pages as $key => $page ) {
            $page_id = get_option( $page['option'] );

            // Check if page exists and is published
            if ( $page_id && get_post_status( $page_id ) === 'publish' ) {
                continue;
            }

            // Check if page with slug exists
            $existing = get_page_by_path( $page['slug'] );
            if ( $existing ) {
                update_option( $page['option'], $existing->ID );
                continue;
            }

            // Create the page
            $new_page_id = wp_insert_post( array(
                'post_title'   => $page['title'],
                'post_content' => $page['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_name'    => $page['slug'],
            ) );

            if ( $new_page_id && ! is_wp_error( $new_page_id ) ) {
                update_option( $page['option'], $new_page_id );
            }
        }
    }

    /**
     * Deactivate the plugin.
     */
    public static function deactivate() {
        self::clear_cron_events();
        flush_rewrite_rules();
    }

    /**
     * Create database tables.
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Weekly availability schedule
        $table_availability = $wpdb->prefix . 'sodek_gb_availability';
        $sql_availability = "CREATE TABLE $table_availability (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            day_of_week tinyint(1) NOT NULL COMMENT '0=Sunday, 6=Saturday',
            start_time time NOT NULL,
            end_time time NOT NULL,
            is_available tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY day_of_week (day_of_week),
            KEY is_available (is_available)
        ) $charset_collate;";

        // Availability overrides for specific dates
        $table_overrides = $wpdb->prefix . 'sodek_gb_availability_overrides';
        $sql_overrides = "CREATE TABLE $table_overrides (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            override_date date NOT NULL,
            start_time time DEFAULT NULL,
            end_time time DEFAULT NULL,
            type varchar(20) NOT NULL DEFAULT 'block' COMMENT 'block or open',
            reason varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY override_date (override_date),
            KEY type (type),
            UNIQUE KEY date_time (override_date, start_time, end_time)
        ) $charset_collate;";

        // Booking time slots (for quick lookups)
        $table_slots = $wpdb->prefix . 'sodek_gb_booked_slots';
        $sql_slots = "CREATE TABLE $table_slots (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) UNSIGNED NOT NULL,
            slot_date date NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            service_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'confirmed',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY slot_date (slot_date),
            KEY service_id (service_id),
            KEY status (status),
            KEY date_time (slot_date, start_time, end_time)
        ) $charset_collate;";

        // Reminder tracking
        $table_reminders = $wpdb->prefix . 'sodek_gb_sent_reminders';
        $sql_reminders = "CREATE TABLE $table_reminders (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id bigint(20) UNSIGNED NOT NULL,
            reminder_type varchar(50) NOT NULL,
            sent_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY booking_id (booking_id),
            KEY reminder_type (reminder_type),
            UNIQUE KEY booking_reminder (booking_id, reminder_type)
        ) $charset_collate;";

        // Payment transactions
        $table_transactions = $wpdb->prefix . 'sodek_gb_transactions';
        $sql_transactions = "CREATE TABLE $table_transactions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            transaction_id varchar(100) NOT NULL,
            booking_id bigint(20) UNSIGNED DEFAULT NULL,
            gateway varchar(50) NOT NULL DEFAULT 'square',
            environment varchar(20) NOT NULL DEFAULT 'sandbox',
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            transaction_type varchar(30) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'pending',
            customer_email varchar(255) DEFAULT NULL,
            customer_name varchar(255) DEFAULT NULL,
            square_payment_id varchar(100) DEFAULT NULL,
            square_receipt_url varchar(500) DEFAULT NULL,
            square_card_brand varchar(50) DEFAULT NULL,
            square_card_last4 varchar(4) DEFAULT NULL,
            error_code varchar(100) DEFAULT NULL,
            error_message text DEFAULT NULL,
            request_data longtext DEFAULT NULL,
            response_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY transaction_id (transaction_id),
            KEY booking_id (booking_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Customer profiles (phone-based identity)
        $table_customers = $wpdb->prefix . 'sodek_gb_customers';
        $sql_customers = "CREATE TABLE $table_customers (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            phone varchar(20) NOT NULL,
            phone_country_code varchar(5) DEFAULT '+1',
            email varchar(255) DEFAULT NULL,
            first_name varchar(100) DEFAULT NULL,
            last_name varchar(100) DEFAULT NULL,
            wp_user_id bigint(20) UNSIGNED DEFAULT NULL,
            preferred_staff_id bigint(20) UNSIGNED DEFAULT NULL,
            hair_type varchar(50) DEFAULT NULL,
            hair_length varchar(50) DEFAULT NULL,
            allergies text DEFAULT NULL,
            notes text DEFAULT NULL,
            sms_opt_in tinyint(1) NOT NULL DEFAULT 1,
            email_opt_in tinyint(1) NOT NULL DEFAULT 1,
            total_bookings int(11) NOT NULL DEFAULT 0,
            total_spent decimal(10,2) NOT NULL DEFAULT 0.00,
            no_show_count int(11) NOT NULL DEFAULT 0,
            phone_verified tinyint(1) NOT NULL DEFAULT 0,
            phone_verified_at datetime DEFAULT NULL,
            last_verification_code varchar(6) DEFAULT NULL,
            verification_code_expires datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY phone (phone),
            KEY email (email),
            KEY wp_user_id (wp_user_id),
            KEY preferred_staff_id (preferred_staff_id)
        ) $charset_collate;";

        // Saved payment methods (cards on file)
        $table_customer_cards = $wpdb->prefix . 'sodek_gb_customer_cards';
        $sql_customer_cards = "CREATE TABLE $table_customer_cards (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) UNSIGNED NOT NULL,
            gateway varchar(50) NOT NULL DEFAULT 'square',
            card_id varchar(100) NOT NULL,
            card_brand varchar(50) DEFAULT NULL,
            card_last4 varchar(4) DEFAULT NULL,
            card_exp_month int(2) DEFAULT NULL,
            card_exp_year int(4) DEFAULT NULL,
            is_default tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY gateway_card (gateway, card_id),
            KEY customer_id (customer_id)
        ) $charset_collate;";

        // Waitlist for fully booked slots
        $table_waitlist = $wpdb->prefix . 'sodek_gb_waitlist';
        $sql_waitlist = "CREATE TABLE $table_waitlist (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) UNSIGNED NOT NULL,
            service_id bigint(20) UNSIGNED NOT NULL,
            staff_id bigint(20) UNSIGNED DEFAULT NULL,
            requested_date date NOT NULL,
            time_preference varchar(20) NOT NULL DEFAULT 'any',
            status varchar(20) NOT NULL DEFAULT 'waiting',
            notified_at datetime DEFAULT NULL,
            expires_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY customer_id (customer_id),
            KEY service_id (service_id),
            KEY staff_id (staff_id),
            KEY requested_date (requested_date),
            KEY status (status)
        ) $charset_collate;";

        // SMS log
        $table_sms_log = $wpdb->prefix . 'sodek_gb_sms_log';
        $sql_sms_log = "CREATE TABLE $table_sms_log (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            phone varchar(20) NOT NULL,
            message_type varchar(50) NOT NULL,
            message_content text DEFAULT NULL,
            twilio_sid varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            error_message text DEFAULT NULL,
            booking_id bigint(20) UNSIGNED DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY phone (phone),
            KEY message_type (message_type),
            KEY status (status),
            KEY booking_id (booking_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // Staff availability (per-staff schedules)
        $table_staff_availability = $wpdb->prefix . 'sodek_gb_staff_availability';
        $sql_staff_availability = "CREATE TABLE $table_staff_availability (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            staff_id bigint(20) UNSIGNED NOT NULL,
            day_of_week tinyint(1) NOT NULL COMMENT '0=Sunday, 6=Saturday',
            start_time time NOT NULL,
            end_time time NOT NULL,
            is_available tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY staff_day (staff_id, day_of_week),
            KEY staff_id (staff_id),
            KEY day_of_week (day_of_week)
        ) $charset_collate;";

        // Staff time off
        $table_staff_time_off = $wpdb->prefix . 'sodek_gb_staff_time_off';
        $sql_staff_time_off = "CREATE TABLE $table_staff_time_off (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            staff_id bigint(20) UNSIGNED NOT NULL,
            date_start date NOT NULL,
            date_end date NOT NULL,
            reason varchar(255) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY staff_id (staff_id),
            KEY date_start (date_start),
            KEY date_end (date_end)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_availability );
        dbDelta( $sql_overrides );
        dbDelta( $sql_slots );
        dbDelta( $sql_reminders );
        dbDelta( $sql_transactions );
        dbDelta( $sql_customers );
        dbDelta( $sql_customer_cards );
        dbDelta( $sql_waitlist );
        dbDelta( $sql_sms_log );
        dbDelta( $sql_staff_availability );
        dbDelta( $sql_staff_time_off );

        // Insert default availability (Mon-Sat 9am-6pm)
        self::insert_default_availability();
    }

    /**
     * Insert default weekly availability.
     */
    private static function insert_default_availability() {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_availability';

        // Check if records already exist
        $count = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        if ( $count > 0 ) {
            return;
        }

        // Default: Mon-Sat 9am-6pm, Sunday closed
        $default_schedule = array(
            array( 'day_of_week' => 0, 'start_time' => '00:00:00', 'end_time' => '00:00:00', 'is_available' => 0 ), // Sunday - Closed
            array( 'day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_available' => 1 ), // Monday
            array( 'day_of_week' => 2, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_available' => 1 ), // Tuesday
            array( 'day_of_week' => 3, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_available' => 1 ), // Wednesday
            array( 'day_of_week' => 4, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_available' => 1 ), // Thursday
            array( 'day_of_week' => 5, 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_available' => 1 ), // Friday
            array( 'day_of_week' => 6, 'start_time' => '09:00:00', 'end_time' => '17:00:00', 'is_available' => 1 ), // Saturday
        );

        foreach ( $default_schedule as $day ) {
            $wpdb->insert( $table, $day );
        }
    }

    /**
     * Create default plugin options.
     */
    private static function create_default_options() {
        $defaults = array(
            // General booking settings
            'sodek_gb_time_slot_interval'      => 30,              // Minutes between slots
            'sodek_gb_min_booking_notice'      => 24,              // Hours in advance required
            'sodek_gb_max_booking_advance'     => 60,              // Days in advance allowed
            'sodek_gb_default_deposit_type'    => 'percentage',    // 'percentage' or 'fixed'
            'sodek_gb_default_deposit_value'   => 50,              // 50% or $50
            'sodek_gb_reminder_24h_enabled'    => 1,               // 24 hour reminder
            'sodek_gb_reminder_2h_enabled'     => 1,               // 2 hour reminder
            'sodek_gb_cancellation_notice'     => 24,              // Hours before to allow cancellation
            'sodek_gb_buffer_before'           => 0,               // Default buffer before appointment
            'sodek_gb_buffer_after'            => 15,              // Default buffer after appointment

            // Standalone booking settings
            'sodek_gb_booking_slug'            => 'book',          // Booking page URL slug
            'sodek_gb_portal_slug'             => 'my-appointments', // Customer portal URL slug
            'sodek_gb_standalone_enabled'      => 1,               // Enable standalone booking
            'sodek_gb_show_any_available'      => 1,               // Show "Any Available" staff option
            'sodek_gb_show_staff_photos'       => 1,               // Show staff photos in selection
            'sodek_gb_show_staff_bios'         => 1,               // Show staff bios in selection
            'sodek_gb_default_staff_assignment'=> 'round-robin',   // round-robin, least-busy, manual
            'sodek_gb_allow_guest_booking'     => 1,               // Allow booking without account
            'sodek_gb_enable_cards_on_file'    => 1,               // Allow saving cards for future use
            'sodek_gb_enforce_customer_payment_type' => 0,          // Let customers choose returning/new until enough history exists
            'sodek_gb_daily_booking_limit_default' => 3,            // Business-wide bookings per day; 0 means unlimited

            // Customer portal settings
            'sodek_gb_portal_enabled'          => 1,               // Enable customer portal
            'sodek_gb_allow_reschedule'        => 1,               // Allow customers to reschedule
            'sodek_gb_allow_cancel'            => 1,               // Allow customers to cancel
            'sodek_gb_reschedule_notice'       => 24,              // Hours before to allow reschedule

            // Email notification settings
            'sodek_gb_email_confirmation'      => 1,               // Send confirmation email
            'sodek_gb_email_reminder_hours'    => 24,              // Hours before to send reminder
            'sodek_gb_email_cancellation'      => 1,               // Send cancellation email

            // SMS settings (infrastructure ready, disabled by default)
            'sodek_gb_sms_enabled'             => 0,               // SMS notifications disabled
            'sodek_gb_twilio_account_sid'      => '',              // Twilio Account SID
            'sodek_gb_twilio_auth_token'       => '',              // Twilio Auth Token
            'sodek_gb_twilio_phone_number'     => '',              // Twilio Phone Number
            'sodek_gb_sms_rate_limit'          => 3,               // Max SMS per hour per phone
        );

        foreach ( $defaults as $option => $value ) {
            if ( false === get_option( $option ) ) {
                add_option( $option, $value );
            }
        }
    }

    /**
     * Schedule cron events.
     */
    private static function schedule_cron_events() {
        // Note: Custom cron interval is registered at file load (bottom of this file)
        // to ensure it's available before wp_schedule_event() is called.
        if ( ! wp_next_scheduled( 'sodek_gb_send_reminders' ) ) {
            wp_schedule_event( time(), 'sodek_gb_fifteen_minutes', 'sodek_gb_send_reminders' );
        }
    }

    /**
     * Clear cron events.
     */
    private static function clear_cron_events() {
        $timestamp = wp_next_scheduled( 'sodek_gb_send_reminders' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'sodek_gb_send_reminders' );
        }
    }

    /**
     * Add custom cron intervals.
     *
     * @param array $schedules Existing schedules.
     * @return array
     */
    public static function add_cron_intervals( $schedules ) {
        $schedules['sodek_gb_fifteen_minutes'] = array(
            'interval' => 900, // 15 minutes in seconds
            'display'  => __( 'Every 15 Minutes', 'glowbook' ),
        );
        return $schedules;
    }
}

// Register cron intervals early
add_filter( 'cron_schedules', array( 'Sodek_GB_Activator', 'add_cron_intervals' ) );
