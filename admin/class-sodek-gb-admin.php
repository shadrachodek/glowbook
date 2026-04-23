<?php
/**
 * Admin functionality.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Admin class.
 */
class Sodek_GB_Admin {

    /**
     * Import source meta key for posts/terms/users.
     *
     * @var string
     */
    private const IMPORT_SOURCE_META_KEY = '_sodek_gb_import_source_key';

    /**
     * Booking admin role slug.
     *
     * @var string
     */
    public const BOOKING_ADMIN_ROLE = 'sodek_gb_booking_admin';

    /**
     * Menu access capability for booking admin users.
     *
     * @var string
     */
    private const BOOKING_ADMIN_ACCESS_CAP = 'sodek_gb_access_booking_admin';

    /**
     * Initialize.
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'ensure_booking_admin_role' ) );
        add_action( 'admin_menu', array( __CLASS__, 'add_menu_pages' ) );
        add_action( 'admin_menu', array( __CLASS__, 'limit_admin_menu_for_booking_admin' ), 999 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_export_bookings' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_import_export' ) );
        add_action( 'admin_init', array( __CLASS__, 'redirect_booking_admin_pages' ) );

        // Dashboard widget
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_dashboard_widget' ) );

        // Admin notices
        add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

        // Fix parent menu highlighting for taxonomy and CPT pages
        add_filter( 'parent_file', array( __CLASS__, 'fix_parent_menu' ) );
        add_filter( 'submenu_file', array( __CLASS__, 'fix_submenu_highlight' ) );
        add_filter( 'admin_body_class', array( __CLASS__, 'add_admin_body_class' ) );
        add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
    }

    /**
     * Ensure booking admin role and capabilities exist.
     */
    public static function ensure_booking_admin_role() {
        $caps = self::get_booking_admin_capabilities();

        $role = get_role( self::BOOKING_ADMIN_ROLE );
        if ( ! $role ) {
            add_role( self::BOOKING_ADMIN_ROLE, __( 'GlowBook Booking Admin', 'glowbook' ), $caps );
            $role = get_role( self::BOOKING_ADMIN_ROLE );
        }

        if ( $role ) {
            foreach ( $caps as $cap => $grant ) {
                if ( $grant ) {
                    $role->add_cap( $cap );
                }
            }
        }

        foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) {
            $existing_role = get_role( $role_name );
            if ( ! $existing_role ) {
                continue;
            }

            $existing_role->add_cap( self::BOOKING_ADMIN_ACCESS_CAP );
            foreach ( array_keys( self::get_booking_post_caps() ) as $cap ) {
                $existing_role->add_cap( $cap );
            }
        }
    }

    /**
     * Handle CSV export of bookings.
     */
    public static function handle_export_bookings() {
        if ( ! isset( $_GET['sodek_gb_export'] ) || 'csv' !== $_GET['sodek_gb_export'] ) {
            return;
        }

        if ( ! self::current_user_can_manage_booking_admin() ) {
            wp_die( esc_html__( 'You do not have permission to export bookings.', 'glowbook' ) );
        }

        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'sodek_gb_export_bookings' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'glowbook' ) );
        }

        // Get date range
        $start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : gmdate( 'Y-m-d', strtotime( '-30 days' ) );
        $end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : gmdate( 'Y-m-d' );
        $status     = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

        // Query bookings
        $args = array(
            'post_type'      => 'sodek_gb_booking',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_sodek_gb_booking_date',
                    'value'   => array( $start_date, $end_date ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ),
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => '_sodek_gb_booking_date',
            'order'          => 'ASC',
        );

        if ( $status ) {
            $args['meta_query'][] = array(
                'key'   => '_sodek_gb_status',
                'value' => $status,
            );
        }

        $bookings = get_posts( $args );

        // Set headers for CSV download
        $filename = 'glowbook-bookings-' . gmdate( 'Y-m-d' ) . '.csv';
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $output = fopen( 'php://output', 'w' );

        // Add BOM for Excel UTF-8 compatibility
        fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

        // CSV headers
        fputcsv( $output, array(
            __( 'Booking ID', 'glowbook' ),
            __( 'Date', 'glowbook' ),
            __( 'Time', 'glowbook' ),
            __( 'Service', 'glowbook' ),
            __( 'Duration (min)', 'glowbook' ),
            __( 'Customer Name', 'glowbook' ),
            __( 'Customer Email', 'glowbook' ),
            __( 'Customer Phone', 'glowbook' ),
            __( 'Status', 'glowbook' ),
            __( 'Deposit', 'glowbook' ),
            __( 'Total Price', 'glowbook' ),
            __( 'Balance Due', 'glowbook' ),
            __( 'Deposit Paid', 'glowbook' ),
            __( 'Balance Paid', 'glowbook' ),
            __( 'Order ID', 'glowbook' ),
            __( 'Notes', 'glowbook' ),
            __( 'Created', 'glowbook' ),
        ) );

        // Add booking rows
        foreach ( $bookings as $booking_post ) {
            $booking = Sodek_GB_Booking::get_booking( $booking_post->ID );
            if ( ! $booking ) {
                continue;
            }

            $balance = floatval( $booking['total_price'] ) - floatval( $booking['deposit_amount'] );

            fputcsv( $output, array(
                $booking['id'],
                $booking['booking_date'],
                $booking['start_time'],
                $booking['service']['title'] ?? '',
                $booking['service']['duration'] ?? '',
                $booking['customer_name'],
                $booking['customer_email'],
                $booking['customer_phone'],
                ucfirst( $booking['status'] ),
                number_format( floatval( $booking['deposit_amount'] ), 2 ),
                number_format( floatval( $booking['total_price'] ), 2 ),
                number_format( $balance, 2 ),
                $booking['deposit_paid'] ? __( 'Yes', 'glowbook' ) : __( 'No', 'glowbook' ),
                $booking['balance_paid'] ? __( 'Yes', 'glowbook' ) : __( 'No', 'glowbook' ),
                $booking['order_id'] ?: '',
                $booking['notes'],
                $booking['created_at'],
            ) );
        }

        fclose( $output );
        exit;
    }

    /**
     * Get the required capability for admin menus.
     * Uses manage_woocommerce if WooCommerce is active, otherwise falls back to manage_options.
     *
     * @return string
     */
    public static function get_capability() {
        return class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options';
    }

    /**
     * Get the booking admin menu capability.
     *
     * @return string
     */
    public static function get_booking_admin_capability() {
        return self::BOOKING_ADMIN_ACCESS_CAP;
    }

    /**
     * Check whether the current user can access booking admin surfaces.
     *
     * @return bool
     */
    public static function current_user_can_manage_booking_admin() {
        return current_user_can( self::get_capability() ) || current_user_can( self::get_booking_admin_capability() );
    }

    /**
     * Check if the current user is a booking admin role user.
     *
     * @param WP_User|null $user Optional user.
     * @return bool
     */
    public static function is_booking_admin_user( $user = null ) {
        $user = $user instanceof WP_User ? $user : wp_get_current_user();

        return $user instanceof WP_User && in_array( self::BOOKING_ADMIN_ROLE, (array) $user->roles, true );
    }

    /**
     * Add admin menu pages.
     */
    public static function add_menu_pages() {
        $capability         = self::get_capability();
        $booking_capability = self::get_booking_admin_capability();

        // Main menu
        add_menu_page(
            __( 'GlowBook', 'glowbook' ),
            __( 'GlowBook', 'glowbook' ),
            $booking_capability,
            'sodek-gb-dashboard',
            array( __CLASS__, 'render_dashboard_page' ),
            'dashicons-calendar-alt',
            56
        );

        // Dashboard submenu
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Dashboard', 'glowbook' ),
            __( 'Dashboard', 'glowbook' ),
            $booking_capability,
            'sodek-gb-dashboard',
            array( __CLASS__, 'render_dashboard_page' )
        );

        // Bookings (CPT)
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'All Bookings', 'glowbook' ),
            __( 'All Bookings', 'glowbook' ),
            $booking_capability,
            'edit.php?post_type=sodek_gb_booking'
        );

        // Services (CPT)
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Services', 'glowbook' ),
            __( 'Services', 'glowbook' ),
            $capability,
            'edit.php?post_type=sodek_gb_service'
        );

        // Service Categories
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Service Categories', 'glowbook' ),
            __( 'Categories', 'glowbook' ),
            $capability,
            'edit-tags.php?taxonomy=sodek_gb_service_cat&post_type=sodek_gb_service'
        );

        // Add-ons (CPT)
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Service Add-ons', 'glowbook' ),
            __( 'Add-ons', 'glowbook' ),
            $capability,
            'edit.php?post_type=sodek_gb_addon'
        );

        // Calendar View
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Calendar', 'glowbook' ),
            __( 'Calendar', 'glowbook' ),
            $booking_capability,
            'sodek-gb-calendar',
            array( __CLASS__, 'render_calendar_page' )
        );

        // Availability
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Availability', 'glowbook' ),
            __( 'Availability', 'glowbook' ),
            $booking_capability,
            'sodek-gb-availability',
            array( __CLASS__, 'render_availability_page' )
        );

        // Staff
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Staff', 'glowbook' ),
            __( 'Staff', 'glowbook' ),
            $capability,
            'sodek-gb-staff',
            array( __CLASS__, 'render_staff_page' )
        );

        // Reports
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Reports', 'glowbook' ),
            __( 'Reports', 'glowbook' ),
            $booking_capability,
            'sodek-gb-reports',
            array( __CLASS__, 'render_reports_page' )
        );

        // Transactions
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Transactions', 'glowbook' ),
            __( 'Transactions', 'glowbook' ),
            $capability,
            'sodek-gb-transactions',
            array( __CLASS__, 'render_transactions_page' )
        );

        // Customers
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Customers', 'glowbook' ),
            __( 'Customers', 'glowbook' ),
            $booking_capability,
            'sodek-gb-customers',
            array( __CLASS__, 'render_customers_page' )
        );

        // Import / Export
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Import / Export', 'glowbook' ),
            __( 'Import / Export', 'glowbook' ),
            $capability,
            'sodek-gb-import-export',
            array( __CLASS__, 'render_import_export_page' )
        );

        // Settings
        add_submenu_page(
            'sodek-gb-dashboard',
            __( 'Settings', 'glowbook' ),
            __( 'Settings', 'glowbook' ),
            $capability,
            'sodek-gb-settings',
            array( __CLASS__, 'render_settings_page' )
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook Current page hook.
     */
    public static function enqueue_scripts( $hook ) {
        $screen = get_current_screen();

        // Debug: Log hook on our pages
        if ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'sodek-gb' ) !== false ) {
            error_log( 'GlowBook Admin: Hook = "' . $hook . '" for page = "' . sanitize_text_field( $_GET['page'] ) . '"' );
        }

        // Only on our plugin pages
        // Hook format: {$parent_slug}_page_{$page_slug} for submenu pages
        $our_pages = array(
            'toplevel_page_sodek-gb-dashboard',
            'glowbook_page_sodek-gb-calendar',
            'glowbook_page_sodek-gb-availability',
            'glowbook_page_sodek-gb-settings',
            'glowbook_page_sodek-gb-staff',
            'glowbook_page_sodek-gb-reports',
            'glowbook_page_sodek-gb-transactions',
            'glowbook_page_sodek-gb-customers',
            'glowbook_page_sodek-gb-import-export',
            // Alternative hook format (parent slug based)
            'sodek-gb-dashboard_page_sodek-gb-calendar',
            'sodek-gb-dashboard_page_sodek-gb-availability',
            'sodek-gb-dashboard_page_sodek-gb-settings',
            'sodek-gb-dashboard_page_sodek-gb-staff',
            'sodek-gb-dashboard_page_sodek-gb-reports',
            'sodek-gb-dashboard_page_sodek-gb-transactions',
            'sodek-gb-dashboard_page_sodek-gb-customers',
            'sodek-gb-dashboard_page_sodek-gb-import-export',
            'sodek_gb_booking',
            'sodek_gb_service',
        );

        if ( ! in_array( $hook, $our_pages, true ) && ( ! $screen || ! in_array( $screen->post_type, array( 'sodek_gb_booking', 'sodek_gb_service' ), true ) ) ) {
            return;
        }

        wp_enqueue_style(
            'sodek-gb-admin',
            SODEK_GB_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            SODEK_GB_VERSION
        );

        wp_enqueue_script(
            'sodek-gb-admin',
            SODEK_GB_PLUGIN_URL . 'admin/js/admin.js',
            array( 'jquery', 'wp-api-fetch' ),
            SODEK_GB_VERSION,
            true
        );

        wp_localize_script( 'sodek-gb-admin', 'sodekGbAdmin', array(
            'apiUrl'     => rest_url( 'sodek-gb/v1/' ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'adminNonce' => wp_create_nonce( 'sodek_gb_admin_nonce' ),
            'isBookingAdminUser' => self::is_booking_admin_user(),
            'strings'    => array(
                'confirmBlock'  => __( 'Are you sure you want to block this date?', 'glowbook' ),
                'confirmDelete' => __( 'Are you sure you want to remove this block?', 'glowbook' ),
                'saving'        => __( 'Saving...', 'glowbook' ),
                'saved'         => __( 'Saved!', 'glowbook' ),
                'error'         => __( 'Error occurred.', 'glowbook' ),
            ),
        ) );

        // Calendar page - FullCalendar
        $is_calendar_page = ( 'glowbook_page_sodek-gb-calendar' === $hook || 'sodek-gb-dashboard_page_sodek-gb-calendar' === $hook );
        if ( isset( $_GET['page'] ) && 'sodek-gb-calendar' === $_GET['page'] ) {
            $is_calendar_page = true;
            error_log( 'GlowBook Admin: Loading calendar scripts (forced by page param)' );
        }
        if ( $is_calendar_page ) {
            wp_enqueue_style(
                'fullcalendar',
                'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css',
                array(),
                '6.1.8'
            );

            wp_enqueue_script(
                'fullcalendar',
                'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js',
                array(),
                '6.1.8',
                true
            );

            wp_enqueue_script(
                'sodek-gb-calendar',
                SODEK_GB_PLUGIN_URL . 'admin/js/calendar.js',
                array( 'fullcalendar', 'wp-api-fetch' ),
                SODEK_GB_VERSION . '.' . time(), // Cache bust
                true
            );

            // Get all services for the filter
            $services = self::get_all_services();

            wp_localize_script( 'sodek-gb-calendar', 'sodekGbCalendar', array(
                'apiUrl'        => rest_url( 'sodek-gb/v1/' ),
                'nonce'         => wp_create_nonce( 'wp_rest' ),
                'editUrl'       => admin_url( 'post.php?action=edit&post=' ),
                'services'      => $services,
                'statusColors'  => array(
                    'pending'   => '#f0ad4e',
                    'confirmed' => '#5cb85c',
                    'completed' => '#5bc0de',
                    'cancelled' => '#d9534f',
                    'no-show'   => '#777777',
                ),
                'strings'       => array(
                    'allServices'       => __( 'All Services', 'glowbook' ),
                    'confirmReschedule' => __( 'Reschedule this booking to %s at %s?', 'glowbook' ),
                    'rescheduleSuccess' => __( 'Booking rescheduled successfully.', 'glowbook' ),
                    'rescheduleFailed'  => __( 'Failed to reschedule booking.', 'glowbook' ),
                    'loading'           => __( 'Loading...', 'glowbook' ),
                ),
            ) );
        }
    }

    /**
     * Remove unrelated admin menus for booking admin users.
     */
    public static function limit_admin_menu_for_booking_admin() {
        if ( ! self::is_booking_admin_user() ) {
            return;
        }

        $remove_pages = array(
            'index.php',
            'edit.php',
            'upload.php',
            'edit.php?post_type=page',
            'edit-comments.php',
            'profile.php',
            'themes.php',
            'plugins.php',
            'users.php',
            'tools.php',
            'options-general.php',
            'woocommerce',
            'wc-admin',
            'elementor',
        );

        foreach ( $remove_pages as $slug ) {
            remove_menu_page( $slug );
        }
    }

    /**
     * Redirect booking admins away from unrelated admin pages.
     */
    public static function redirect_booking_admin_pages() {
        if ( ! self::is_booking_admin_user() || wp_doing_ajax() ) {
            return;
        }

        global $pagenow;

        $dashboard_url = admin_url( 'admin.php?page=sodek-gb-dashboard' );
        $allowed_pages = array(
            'sodek-gb-dashboard',
            'sodek-gb-calendar',
            'sodek-gb-availability',
            'sodek-gb-reports',
            'sodek-gb-customers',
        );

        if ( 'index.php' === $pagenow ) {
            wp_safe_redirect( $dashboard_url );
            exit;
        }

        if ( 'admin.php' === $pagenow ) {
            $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
            if ( ! in_array( $page, $allowed_pages, true ) ) {
                wp_safe_redirect( $dashboard_url );
                exit;
            }
        }

        if ( 'profile.php' === $pagenow ) {
            wp_safe_redirect( $dashboard_url );
            exit;
        }

        if ( in_array( $pagenow, array( 'edit.php', 'post.php', 'post-new.php' ), true ) ) {
            $post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

            if ( empty( $post_type ) && ! empty( $_GET['post'] ) ) {
                $post_type = get_post_type( (int) $_GET['post'] );
            }

            if ( Sodek_GB_Booking::POST_TYPE !== $post_type ) {
                wp_safe_redirect( $dashboard_url );
                exit;
            }
        }
    }

    /**
     * Add body class for booking admin experience.
     *
     * @param string $classes Classes.
     * @return string
     */
    public static function add_admin_body_class( $classes ) {
        if ( self::is_booking_admin_user() ) {
            $classes .= ' sodek-gb-booking-admin';
        }

        return $classes;
    }

    /**
     * Redirect booking admins to GlowBook after login.
     *
     * @param string           $redirect_to Redirect URL.
     * @param string           $requested   Requested redirect.
     * @param WP_User|WP_Error $user        User.
     * @return string
     */
    public static function login_redirect( $redirect_to, $requested, $user ) {
        if ( $user instanceof WP_User && in_array( self::BOOKING_ADMIN_ROLE, (array) $user->roles, true ) ) {
            return admin_url( 'admin.php?page=sodek-gb-dashboard' );
        }

        return $redirect_to;
    }

    /**
     * Get booking post capabilities.
     *
     * @return array
     */
    private static function get_booking_post_caps() {
        return array_fill_keys(
            array(
                'edit_sodek_gb_booking',
                'read_sodek_gb_booking',
                'delete_sodek_gb_booking',
                'edit_sodek_gb_bookings',
                'edit_others_sodek_gb_bookings',
                'publish_sodek_gb_bookings',
                'read_private_sodek_gb_bookings',
                'delete_sodek_gb_bookings',
                'delete_private_sodek_gb_bookings',
                'delete_published_sodek_gb_bookings',
                'delete_others_sodek_gb_bookings',
                'edit_private_sodek_gb_bookings',
                'edit_published_sodek_gb_bookings',
            ),
            true
        );
    }

    /**
     * Get the capability map for the booking admin role.
     *
     * @return array
     */
    private static function get_booking_admin_capabilities() {
        return array_merge(
            array(
                'read'                            => true,
                self::BOOKING_ADMIN_ACCESS_CAP    => true,
            ),
            self::get_booking_post_caps()
        );
    }

    /**
     * Get all services for dropdown.
     *
     * @return array
     */
    private static function get_all_services() {
        $args = array(
            'post_type'      => 'sodek_gb_service',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        $query = new WP_Query( $args );
        $services = array();

        foreach ( $query->posts as $post ) {
            $services[] = array(
                'id'    => $post->ID,
                'title' => $post->post_title,
            );
        }

        return $services;
    }

    /**
     * Register settings.
     */
    public static function register_settings() {
        // Page Settings
        register_setting( 'sodek_gb_settings', 'sodek_gb_confirmation_page_id', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ) );

        register_setting( 'sodek_gb_settings', 'sodek_gb_portal_page_id', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ) );

        register_setting( 'sodek_gb_settings', 'sodek_gb_booking_page_id', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ) );

        // Payment Mode
        register_setting( 'sodek_gb_settings', 'sodek_gb_payment_mode', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'woocommerce',
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_customer_payment_rules_enabled', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 1,
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_enforce_customer_payment_type', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_returning_customer_payment_amount', array(
            'type'              => 'number',
            'sanitize_callback' => array( __CLASS__, 'sanitize_amount' ),
            'default'           => 50,
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_new_customer_payment_amount', array(
            'type'              => 'number',
            'sanitize_callback' => array( __CLASS__, 'sanitize_amount' ),
            'default'           => 150,
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_booking_prep_text', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => '',
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_booking_terms_text', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default'           => '',
        ) );

        // Square Settings
        register_setting( 'sodek_gb_settings', 'sodek_gb_square_enabled', array(
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        ) );

        register_setting( 'sodek_gb_settings', 'sodek_gb_square_environment', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'sandbox',
        ) );

        // Square Sandbox Credentials
        register_setting( 'sodek_gb_settings', 'sodek_gb_square_sandbox_app_id', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        register_setting( 'sodek_gb_settings', 'sodek_gb_square_sandbox_access_token', array(
            'type'              => 'string',
            'sanitize_callback' => array( __CLASS__, 'sanitize_square_token' ),
        ) );

        register_setting( 'sodek_gb_settings', 'sodek_gb_square_sandbox_location_id', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        // Square Production Credentials
        register_setting( 'sodek_gb_settings', 'sodek_gb_square_production_app_id', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        register_setting( 'sodek_gb_settings', 'sodek_gb_square_production_access_token', array(
            'type'              => 'string',
            'sanitize_callback' => array( __CLASS__, 'sanitize_square_token' ),
        ) );

        register_setting( 'sodek_gb_settings', 'sodek_gb_square_production_location_id', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        // Appearance settings
        register_setting( 'sodek_gb_settings', 'sodek_gb_primary_color', array(
            'sanitize_callback' => 'sanitize_hex_color',
            'default'           => '#2271b1',
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_button_style', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'filled',
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_border_radius', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'medium',
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_inherit_theme_colors', array(
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ) );

        // General settings
        register_setting( 'sodek_gb_settings', 'sodek_gb_timezone', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => wp_timezone_string(),
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_time_slot_interval' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_min_booking_notice' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_max_booking_advance' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_buffer_before' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_buffer_after' );

        // Deposit settings
        register_setting( 'sodek_gb_settings', 'sodek_gb_default_deposit_type' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_default_deposit_value' );

        // Reminder settings
        register_setting( 'sodek_gb_settings', 'sodek_gb_reminder_24h_enabled' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_reminder_2h_enabled' );

        // WhatsApp settings
        register_setting( 'sodek_gb_settings', 'sodek_gb_whatsapp_enabled' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_whatsapp_number', array(
            'sanitize_callback' => array( __CLASS__, 'sanitize_phone_number' ),
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_whatsapp_notify_new' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_whatsapp_notify_cancelled' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_whatsapp_notify_rescheduled' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_whatsapp_callmebot_key', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_whatsapp_msg_new', array(
            'sanitize_callback' => 'sanitize_textarea_field',
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_whatsapp_msg_cancelled', array(
            'sanitize_callback' => 'sanitize_textarea_field',
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_whatsapp_msg_rescheduled', array(
            'sanitize_callback' => 'sanitize_textarea_field',
        ) );

        // Cancellation settings
        register_setting( 'sodek_gb_settings', 'sodek_gb_cancellation_notice' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_cancellation_refund_policy' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_partial_refund_percent' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_late_cancellation_policy' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_noshow_policy' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_allow_customer_cancel' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_allow_customer_reschedule' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_cancellation_policy_text' );

        // Button Text settings
        register_setting( 'sodek_gb_settings', 'sodek_gb_button_text_type', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'default',
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_button_text_custom', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ) );

        // Display settings
        register_setting( 'sodek_gb_settings', 'sodek_gb_show_duration', array(
            'sanitize_callback' => 'absint',
            'default'           => 1,
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_use_theme_gallery', array(
            'sanitize_callback' => 'absint',
            'default'           => 1,
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_fullwidth_layout', array(
            'sanitize_callback' => 'absint',
            'default'           => 1,
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_show_service_image', array(
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ) );
        register_setting( 'sodek_gb_settings', 'sodek_gb_show_service_deposit', array(
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ) );
    }

    /**
     * Render dashboard page.
     */
    public static function render_dashboard_page() {
        // Get today's bookings
        $today = gmdate( 'Y-m-d' );
        $today_bookings = self::get_bookings_by_date( $today );

        // Get upcoming bookings (next 7 days)
        $upcoming = self::get_upcoming_bookings( 7 );

        // Get stats
        $stats = self::get_dashboard_stats();

        include SODEK_GB_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render availability page.
     */
    public static function render_availability_page() {
        // Handle form submission
        if ( isset( $_POST['sodek_gb_save_schedule'], $_POST['sodek_gb_schedule_nonce'] ) &&
             wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sodek_gb_schedule_nonce'] ) ), 'sodek_gb_save_schedule' ) ) {
            self::save_weekly_schedule();
        }

        if ( isset( $_POST['sodek_gb_save_daily_limits'], $_POST['sodek_gb_daily_limits_nonce'] ) &&
             wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sodek_gb_daily_limits_nonce'] ) ), 'sodek_gb_save_daily_limits' ) ) {
            self::save_daily_booking_limits();
        }

        if ( isset( $_POST['sodek_gb_block_date'], $_POST['sodek_gb_block_nonce'] ) &&
             wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sodek_gb_block_nonce'] ) ), 'sodek_gb_block_date' ) ) {
            self::handle_block_date();
        }

        $schedule = Sodek_GB_Availability::get_weekly_schedule();
        $default_daily_limit = Sodek_GB_Availability::get_default_daily_booking_limit();
        $weekday_daily_limits = Sodek_GB_Availability::get_weekday_daily_booking_limits();
        $date_limit_overrides = Sodek_GB_Availability::get_daily_booking_limit_overrides();
        $days = array(
            0 => __( 'Sunday', 'glowbook' ),
            1 => __( 'Monday', 'glowbook' ),
            2 => __( 'Tuesday', 'glowbook' ),
            3 => __( 'Wednesday', 'glowbook' ),
            4 => __( 'Thursday', 'glowbook' ),
            5 => __( 'Friday', 'glowbook' ),
            6 => __( 'Saturday', 'glowbook' ),
        );

        // Get blocked dates for current and next month
        $current_year = (int) gmdate( 'Y' );
        $current_month = (int) gmdate( 'm' );
        $blocked_dates = Sodek_GB_Availability::get_blocked_dates( $current_year, $current_month );

        include SODEK_GB_PLUGIN_DIR . 'admin/views/availability.php';
    }

    /**
     * Render calendar page.
     */
    public static function render_calendar_page() {
        include SODEK_GB_PLUGIN_DIR . 'admin/views/calendar.php';
    }

    /**
     * Render staff page.
     */
    public static function render_staff_page() {
        include SODEK_GB_PLUGIN_DIR . 'admin/views/staff.php';
    }

    /**
     * Render reports page.
     */
    public static function render_reports_page() {
        include SODEK_GB_PLUGIN_DIR . 'admin/views/reports.php';
    }

    /**
     * Render customers page.
     */
    public static function render_customers_page() {
        $search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $per_page     = 20;
        $customer_id  = isset( $_GET['customer_id'] ) ? absint( $_GET['customer_id'] ) : 0;

        $customers_index   = self::get_customers_index( $search, $current_page, $per_page );
        $customers         = $customers_index['items'];
        $total             = $customers_index['total'];
        $total_pages       = max( 1, (int) ceil( $total / $per_page ) );
        $selected_customer = $customer_id ? self::get_customer_profile_data( $customer_id ) : null;
        $customer_stats    = self::get_customer_admin_stats();

        include SODEK_GB_PLUGIN_DIR . 'admin/views/customers.php';
    }

    /**
     * Render import/export page.
     */
    public static function render_import_export_page() {
        $datasets = self::get_import_export_datasets();
        include SODEK_GB_PLUGIN_DIR . 'admin/views/import-export.php';
    }

    /**
     * Get paginated customer list for admin.
     *
     * @param string $search       Search term.
     * @param int    $current_page Current page.
     * @param int    $per_page     Items per page.
     * @return array
     */
    private static function get_customers_index( $search = '', $current_page = 1, $per_page = 20 ) {
        global $wpdb;

        $table   = $wpdb->prefix . 'sodek_gb_customers';
        $offset  = max( 0, ( $current_page - 1 ) * $per_page );
        $where   = '1=1';
        $params  = array();

        if ( '' !== $search ) {
            $like     = '%' . $wpdb->esc_like( $search ) . '%';
            $where   .= ' AND (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR phone LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $count_sql = "SELECT COUNT(*) FROM $table WHERE $where";
        $list_sql  = "
            SELECT c.*
            FROM $table c
            WHERE $where
            ORDER BY c.updated_at DESC, c.id DESC
            LIMIT %d OFFSET %d
        ";

        if ( ! empty( $params ) ) {
            $total     = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $query_sql = $wpdb->prepare( $list_sql, array_merge( $params, array( $per_page, $offset ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        } else {
            $total     = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $query_sql = $wpdb->prepare( $list_sql, $per_page, $offset );
        }

        $rows = $wpdb->get_results( $query_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $items = array();

        foreach ( $rows as $row ) {
            $customer_id    = (int) $row['id'];
            $recent_booking = self::get_latest_booking_for_customer( $customer_id, $row );

            $items[] = array(
                'id'             => $customer_id,
                'name'           => trim( ( $row['first_name'] ?? '' ) . ' ' . ( $row['last_name'] ?? '' ) ) ?: __( 'Guest', 'glowbook' ),
                'email'          => $row['email'] ?? '',
                'phone'          => $row['phone'] ?? '',
                'total_bookings' => (int) ( $row['total_bookings'] ?? 0 ),
                'total_spent'    => (float) ( $row['total_spent'] ?? 0 ),
                'last_booking'   => $recent_booking['booking_date'] ?? '',
                'last_booking_id'=> $recent_booking['id'] ?? 0,
                'created_at'     => $row['created_at'] ?? '',
                'updated_at'     => $row['updated_at'] ?? '',
                'wp_user_id'     => isset( $row['wp_user_id'] ) ? (int) $row['wp_user_id'] : 0,
                'email_opt_in'   => ! empty( $row['email_opt_in'] ),
                'sms_opt_in'     => ! empty( $row['sms_opt_in'] ),
            );
        }

        return array(
            'items' => $items,
            'total' => $total,
        );
    }

    /**
     * Get full profile payload for one customer.
     *
     * @param int $customer_id Customer ID.
     * @return array|null
     */
    private static function get_customer_profile_data( $customer_id ) {
        $customer = Sodek_GB_Customer::get_by_id( $customer_id );

        if ( ! $customer ) {
            return null;
        }

        $cards            = Sodek_GB_Customer::get_cards( $customer_id );
        $recent_bookings  = Sodek_GB_Customer::get_bookings( $customer_id, '', 10 );
        $upcoming_bookings= Sodek_GB_Customer::get_upcoming_bookings( $customer_id, 5 );
        $past_bookings    = Sodek_GB_Customer::get_past_bookings( $customer_id, 5 );
        $wp_user          = ! empty( $customer['wp_user_id'] ) ? get_user_by( 'id', (int) $customer['wp_user_id'] ) : false;
        $customer_meta    = array();

        if ( ! empty( $customer['id'] ) ) {
            $customer_meta = array(
                'square_customer_id' => Sodek_GB_Customer::get_meta( $customer_id, 'square_customer_id', '' ),
            );
        }

        return array(
            'customer'         => $customer,
            'cards'            => $cards,
            'recent_bookings'  => $recent_bookings,
            'upcoming_bookings'=> $upcoming_bookings,
            'past_bookings'    => $past_bookings,
            'wp_user'          => $wp_user,
            'meta'             => $customer_meta,
        );
    }

    /**
     * Get customer stats for admin page header.
     *
     * @return array
     */
    private static function get_customer_admin_stats() {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';

        return array(
            'total_customers'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            'portal_enabled'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE email IS NOT NULL AND email <> ''" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            'linked_wp_users'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE wp_user_id IS NOT NULL AND wp_user_id > 0" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            'sms_opt_in_customers'=> (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE sms_opt_in = 1" ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        );
    }

    /**
     * Get latest booking for a customer row.
     *
     * @param int   $customer_id Customer ID.
     * @param array $customer    Customer row.
     * @return array|null
     */
    private static function get_latest_booking_for_customer( $customer_id, $customer ) {
        $bookings = Sodek_GB_Customer::get_bookings( $customer_id, '', 1 );

        if ( ! empty( $bookings ) ) {
            return $bookings[0];
        }

        return null;
    }

    /**
     * Render transactions page.
     */
    public static function render_transactions_page() {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_transactions';

        // Get transactions with pagination
        $per_page = 20;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $offset = ( $current_page - 1 ) * $per_page;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
        $total_pages = ceil( $total / $per_page );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $transactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        $completed_count = 0;
        $pending_count   = 0;
        $failed_count    = 0;
        $refunded_count  = 0;
        $gross_volume    = 0.0;

        foreach ( (array) $transactions as $txn ) {
            $gross_volume += (float) $txn->amount;
            switch ( $txn->status ) {
                case 'completed':
                    $completed_count++;
                    break;
                case 'pending':
                    $pending_count++;
                    break;
                case 'failed':
                    $failed_count++;
                    break;
                case 'refunded':
                    $refunded_count++;
                    break;
            }
        }
        ?>
        <div class="wrap sodek-gb-admin-wrap sodek-gb-transactions-page">
            <div class="sodek-gb-admin-hero">
                <div>
                    <span class="sodek-gb-admin-kicker"><?php esc_html_e( 'Revenue and Reconciliation', 'glowbook' ); ?></span>
                    <h1><?php esc_html_e( 'Payment Transactions', 'glowbook' ); ?></h1>
                    <p><?php esc_html_e( 'Review deposits, balance payments, refunds, and card references in one place so support and reconciliation stay straightforward.', 'glowbook' ); ?></p>
                </div>
                <div class="sodek-gb-admin-hero-note">
                    <strong><?php echo esc_html( number_format_i18n( (int) $total ) ); ?></strong>
                    <span><?php esc_html_e( 'transaction records are currently in the ledger.', 'glowbook' ); ?></span>
                </div>
            </div>

            <div class="sodek-gb-transaction-stats">
                <div class="sodek-gb-transaction-stat-card">
                    <span><?php esc_html_e( 'Gross volume on this page', 'glowbook' ); ?></span>
                    <strong><?php echo esc_html( '$' . number_format_i18n( $gross_volume, 2 ) ); ?></strong>
                </div>
                <div class="sodek-gb-transaction-stat-card">
                    <span><?php esc_html_e( 'Completed', 'glowbook' ); ?></span>
                    <strong><?php echo esc_html( number_format_i18n( $completed_count ) ); ?></strong>
                </div>
                <div class="sodek-gb-transaction-stat-card">
                    <span><?php esc_html_e( 'Pending / Failed', 'glowbook' ); ?></span>
                    <strong><?php echo esc_html( number_format_i18n( $pending_count + $failed_count ) ); ?></strong>
                </div>
                <div class="sodek-gb-transaction-stat-card">
                    <span><?php esc_html_e( 'Refunded', 'glowbook' ); ?></span>
                    <strong><?php echo esc_html( number_format_i18n( $refunded_count ) ); ?></strong>
                </div>
            </div>

            <div class="sodek-gb-admin-surface">
                <div class="sodek-gb-admin-surface-header">
                    <div>
                        <h2><?php esc_html_e( 'Latest Transactions', 'glowbook' ); ?></h2>
                        <p><?php esc_html_e( 'Track booking linkage, payment status, receipt URLs, and safe card references without leaving the admin.', 'glowbook' ); ?></p>
                    </div>
                </div>

                <div class="sodek-gb-transaction-table-wrap">
                    <table class="wp-list-table widefat striped sodek-gb-transaction-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Reference', 'glowbook' ); ?></th>
                                <th><?php esc_html_e( 'Booking', 'glowbook' ); ?></th>
                                <th><?php esc_html_e( 'Amount', 'glowbook' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'glowbook' ); ?></th>
                                <th><?php esc_html_e( 'Customer', 'glowbook' ); ?></th>
                                <th><?php esc_html_e( 'Card', 'glowbook' ); ?></th>
                                <th><?php esc_html_e( 'Date', 'glowbook' ); ?></th>
                                <th><?php esc_html_e( 'Receipt', 'glowbook' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $transactions ) ) : ?>
                                <tr>
                                    <td colspan="8"><?php esc_html_e( 'No transactions found.', 'glowbook' ); ?></td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $transactions as $txn ) : ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo esc_html( $txn->id ); ?></strong><br>
                                            <code><?php echo esc_html( $txn->transaction_id ); ?></code>
                                            <?php if ( $txn->square_payment_id ) : ?>
                                                <br><small><?php printf( esc_html__( 'Square: %s', 'glowbook' ), esc_html( $txn->square_payment_id ) ); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ( $txn->booking_id ) : ?>
                                                <a href="<?php echo esc_url( get_edit_post_link( $txn->booking_id ) ); ?>">
                                                    #<?php echo esc_html( $txn->booking_id ); ?>
                                                </a>
                                            <?php else : ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo esc_html( $txn->currency . ' ' . number_format( $txn->amount, 2 ) ); ?></strong></td>
                                        <td>
                                            <span class="sodek-gb-status sodek-gb-status-<?php echo esc_attr( sanitize_html_class( strtolower( $txn->status ) ) ); ?>">
                                                <?php echo esc_html( ucfirst( $txn->status ) ); ?>
                                            </span>
                                            <?php if ( $txn->error_message ) : ?>
                                                <br><small class="sodek-gb-transaction-error"><?php echo esc_html( $txn->error_message ); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo esc_html( $txn->customer_name ?: '—' ); ?>
                                            <?php if ( $txn->customer_email ) : ?>
                                                <br><small><?php echo esc_html( $txn->customer_email ); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ( $txn->square_card_brand ) : ?>
                                                <?php echo esc_html( $txn->square_card_brand ); ?>
                                                <?php if ( $txn->square_card_last4 ) : ?>
                                                    <br><small><?php printf( esc_html__( 'ending in %s', 'glowbook' ), esc_html( $txn->square_card_last4 ) ); ?></small>
                                                <?php endif; ?>
                                            <?php else : ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html( date_i18n( 'M j, Y g:i a', strtotime( $txn->created_at ) ) ); ?></td>
                                        <td>
                                            <?php if ( $txn->square_receipt_url ) : ?>
                                                <a href="<?php echo esc_url( $txn->square_receipt_url ); ?>" target="_blank" rel="noopener" class="button button-small">
                                                    <?php esc_html_e( 'View', 'glowbook' ); ?>
                                                </a>
                                            <?php else : ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ( $total_pages > 1 ) : ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links( array(
                                'base'      => add_query_arg( 'paged', '%#%' ),
                                'format'    => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total'     => $total_pages,
                                'current'   => $current_page,
                            ) );
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <style>
        .sodek-gb-transactions-page .sodek-gb-admin-hero,
        .sodek-gb-transactions-page .sodek-gb-admin-surface,
        .sodek-gb-transaction-stat-card {
            background: #fff;
            border: 1px solid #dde3ea;
            border-radius: 22px;
            box-shadow: 0 18px 36px rgba(16, 24, 40, 0.05);
        }
        .sodek-gb-transactions-page .sodek-gb-admin-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(240px, 0.34fr);
            gap: 18px;
            padding: 28px 30px;
            margin: 18px 0;
            background: linear-gradient(135deg, #fffaf5 0%, #f7efe6 100%);
            border-color: #eadfce;
        }
        .sodek-gb-transactions-page .sodek-gb-admin-kicker {
            display: inline-flex;
            margin-bottom: 10px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #8a5a21;
        }
        .sodek-gb-transactions-page .sodek-gb-admin-hero h1 {
            margin: 0 0 8px;
            font-size: 34px;
        }
        .sodek-gb-transactions-page .sodek-gb-admin-hero p,
        .sodek-gb-transactions-page .sodek-gb-admin-surface-header p {
            margin: 0;
            color: #667085;
            line-height: 1.65;
        }
        .sodek-gb-transactions-page .sodek-gb-admin-hero-note {
            display: grid;
            gap: 8px;
            align-self: end;
            padding: 18px 20px;
            background: rgba(255,255,255,0.84);
            border: 1px solid rgba(182, 120, 49, 0.16);
            border-radius: 18px;
        }
        .sodek-gb-transaction-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }
        .sodek-gb-transaction-stat-card {
            padding: 18px 20px;
        }
        .sodek-gb-transaction-stat-card span {
            display: block;
            margin-bottom: 10px;
            color: #667085;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .sodek-gb-transaction-stat-card strong {
            font-size: 28px;
            line-height: 1.1;
        }
        .sodek-gb-transactions-page .sodek-gb-admin-surface {
            padding: 22px;
        }
        .sodek-gb-transactions-page .sodek-gb-admin-surface-header {
            margin-bottom: 16px;
        }
        .sodek-gb-transaction-table-wrap {
            overflow-x: auto;
        }
        .sodek-gb-transaction-table code {
            font-size: 11px;
        }
        .sodek-gb-transaction-error {
            color: #b42318;
        }
        @media screen and (max-width: 1100px) {
            .sodek-gb-transactions-page .sodek-gb-admin-hero,
            .sodek-gb-transaction-stats {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }

    /**
     * Save weekly schedule.
     */
    private static function save_weekly_schedule() {
        if ( ! self::current_user_can_manage_booking_admin() ) {
            return;
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per field below
        $post_schedule = isset( $_POST['schedule'] ) ? wp_unslash( $_POST['schedule'] ) : array();
        $schedule = array();

        for ( $day = 0; $day <= 6; $day++ ) {
            $schedule[ $day ] = array(
                'start_time'   => isset( $post_schedule[ $day ]['start_time'] ) ? sanitize_text_field( $post_schedule[ $day ]['start_time'] ) : '09:00:00',
                'end_time'     => isset( $post_schedule[ $day ]['end_time'] ) ? sanitize_text_field( $post_schedule[ $day ]['end_time'] ) : '18:00:00',
                'is_available' => isset( $post_schedule[ $day ]['is_available'] ) ? 1 : 0,
            );
        }

        Sodek_GB_Availability::update_weekly_schedule( $schedule );

        add_settings_error( 'sodek_gb_messages', 'schedule_saved', __( 'Schedule saved.', 'glowbook' ), 'success' );
    }

    /**
     * Save global daily booking limits.
     */
    private static function save_daily_booking_limits() {
        if ( ! self::current_user_can_manage_booking_admin() ) {
            return;
        }

        $default_limit = isset( $_POST['default_daily_limit'] ) ? absint( $_POST['default_daily_limit'] ) : 3;
        update_option( 'sodek_gb_daily_booking_limit_default', $default_limit );

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per field below.
        $posted_weekdays = isset( $_POST['daily_limits'] ) ? wp_unslash( $_POST['daily_limits'] ) : array();
        $weekday_limits  = array();

        for ( $day = 0; $day <= 6; $day++ ) {
            $raw_limit = isset( $posted_weekdays[ $day ] ) ? trim( (string) $posted_weekdays[ $day ] ) : '';
            if ( '' !== $raw_limit ) {
                $weekday_limits[ $day ] = absint( $raw_limit );
            }
        }

        update_option( 'sodek_gb_daily_booking_limit_weekdays', $weekday_limits );

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized per row below.
        $posted_overrides = isset( $_POST['date_limit_overrides'] ) ? wp_unslash( $_POST['date_limit_overrides'] ) : array();
        $overrides        = array();

        if ( is_array( $posted_overrides ) ) {
            foreach ( $posted_overrides as $row ) {
                if ( empty( $row['date'] ) ) {
                    continue;
                }

                $date = sanitize_text_field( $row['date'] );
                if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                    continue;
                }

                $overrides[ $date ] = array(
                    'limit' => isset( $row['limit'] ) ? absint( $row['limit'] ) : $default_limit,
                    'note'  => isset( $row['note'] ) ? sanitize_text_field( $row['note'] ) : '',
                );
            }
        }

        ksort( $overrides );
        update_option( 'sodek_gb_daily_booking_limit_overrides', $overrides );

        add_settings_error( 'sodek_gb_messages', 'daily_limits_saved', __( 'Daily booking limits saved.', 'glowbook' ), 'success' );
    }

    /**
     * Handle blocking a date.
     */
    private static function handle_block_date() {
        if ( ! self::current_user_can_manage_booking_admin() ) {
            return;
        }

        $date = isset( $_POST['block_date'] ) ? sanitize_text_field( wp_unslash( $_POST['block_date'] ) ) : '';
        $reason = isset( $_POST['block_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['block_reason'] ) ) : '';

        if ( $date ) {
            Sodek_GB_Availability::block_date( $date, $reason );
            add_settings_error( 'sodek_gb_messages', 'date_blocked', __( 'Date blocked.', 'glowbook' ), 'success' );
        }
    }

    /**
     * Render settings page.
     */
    public static function render_settings_page() {
        include SODEK_GB_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Get datasets supported by import/export tools.
     *
     * @return array
     */
    private static function get_import_export_datasets() {
        return array(
            'full_backup'  => array(
                'label'       => __( 'Full Backup', 'glowbook' ),
                'description' => __( 'Exports or imports categories, services, add-ons, customers, staff, availability, and bookings in one package.', 'glowbook' ),
            ),
            'categories'   => array(
                'label'       => __( 'Categories', 'glowbook' ),
                'description' => __( 'Service categories and hierarchy.', 'glowbook' ),
            ),
            'services'     => array(
                'label'       => __( 'Services', 'glowbook' ),
                'description' => __( 'Service catalog, pricing, durations, images, and category assignments.', 'glowbook' ),
            ),
            'addons'       => array(
                'label'       => __( 'Add-ons', 'glowbook' ),
                'description' => __( 'Add-on pricing, durations, images, and service assignments.', 'glowbook' ),
            ),
            'customers'    => array(
                'label'       => __( 'Customers', 'glowbook' ),
                'description' => __( 'Customer profiles and portal preferences. Saved cards are intentionally excluded.', 'glowbook' ),
            ),
            'staff'        => array(
                'label'       => __( 'Staff', 'glowbook' ),
                'description' => __( 'Staff users, profile data, and service capabilities.', 'glowbook' ),
            ),
            'availability' => array(
                'label'       => __( 'Availability', 'glowbook' ),
                'description' => __( 'Business schedule, blocked dates, and staff schedules/time off.', 'glowbook' ),
            ),
            'bookings'     => array(
                'label'       => __( 'Bookings', 'glowbook' ),
                'description' => __( 'All bookings with customer, staff, payment, and add-on references.', 'glowbook' ),
            ),
        );
    }

    /**
     * Handle JSON import/export actions.
     */
    public static function handle_import_export() {
        if ( ! is_admin() || ! current_user_can( self::get_capability() ) ) {
            return;
        }

        $page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ?? '' ) );
        if ( 'sodek-gb-import-export' !== $page ) {
            return;
        }

        if ( isset( $_GET['sodek_gb_export_json'] ) ) {
            check_admin_referer( 'sodek_gb_export_json' );

            $dataset = sanitize_key( wp_unslash( $_GET['dataset'] ?? '' ) );
            self::send_export_file( $dataset );
        }

        if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['sodek_gb_import_json'] ) ) {
            return;
        }

        check_admin_referer( 'sodek_gb_import_json' );

        $dataset = sanitize_key( wp_unslash( $_POST['dataset'] ?? '' ) );
        if ( empty( $dataset ) || ! array_key_exists( $dataset, self::get_import_export_datasets() ) ) {
            self::set_admin_notice( 'error', __( 'Please choose a valid import dataset.', 'glowbook' ) );
            wp_safe_redirect( admin_url( 'admin.php?page=sodek-gb-import-export' ) );
            exit;
        }

        if ( empty( $_FILES['import_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['import_file']['tmp_name'] ) ) {
            self::set_admin_notice( 'error', __( 'Please choose a JSON file to import.', 'glowbook' ) );
            wp_safe_redirect( admin_url( 'admin.php?page=sodek-gb-import-export' ) );
            exit;
        }

        $raw = file_get_contents( $_FILES['import_file']['tmp_name'] );
        $payload = json_decode( $raw, true );

        if ( ! is_array( $payload ) || empty( $payload['data'] ) ) {
            self::set_admin_notice( 'error', __( 'The uploaded file is not a valid GlowBook import package.', 'glowbook' ) );
            wp_safe_redirect( admin_url( 'admin.php?page=sodek-gb-import-export' ) );
            exit;
        }

        try {
            $summary = self::import_payload( $dataset, $payload );
            $message = sprintf(
                /* translators: 1: dataset label, 2: import summary */
                __( '%1$s import completed. %2$s', 'glowbook' ),
                self::get_import_export_datasets()[ $dataset ]['label'],
                $summary
            );
            self::set_admin_notice( 'success', $message );
        } catch ( Exception $e ) {
            self::set_admin_notice( 'error', $e->getMessage() );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=sodek-gb-import-export' ) );
        exit;
    }

    /**
     * Send an export file to the browser.
     *
     * @param string $dataset Dataset key.
     */
    private static function send_export_file( $dataset ) {
        $datasets = self::get_import_export_datasets();

        if ( empty( $dataset ) || ! isset( $datasets[ $dataset ] ) ) {
            wp_die( esc_html__( 'Invalid export dataset.', 'glowbook' ) );
        }

        $payload = array(
            'meta' => array(
                'plugin'          => 'GlowBook',
                'version'         => SODEK_GB_VERSION,
                'dataset'         => $dataset,
                'site_url'        => home_url(),
                'exported_at_gmt' => gmdate( 'c' ),
            ),
            'data' => self::build_export_data( $dataset ),
        );

        $filename = sprintf( 'glowbook-%s-%s.json', $dataset, gmdate( 'Y-m-d-His' ) );

        // Clear any earlier buffered output so JSON downloads stay valid.
        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        exit;
    }

    /**
     * Build export data for a dataset.
     *
     * @param string $dataset Dataset key.
     * @return array
     */
    private static function build_export_data( $dataset ) {
        switch ( $dataset ) {
            case 'full_backup':
                return array(
                    'categories'   => self::export_categories(),
                    'services'     => self::export_services(),
                    'addons'       => self::export_addons(),
                    'customers'    => self::export_customers(),
                    'staff'        => self::export_staff(),
                    'availability' => self::export_availability(),
                    'bookings'     => self::export_bookings(),
                );
            case 'categories':
                return self::export_categories();
            case 'services':
                return self::export_services();
            case 'addons':
                return self::export_addons();
            case 'customers':
                return self::export_customers();
            case 'staff':
                return self::export_staff();
            case 'availability':
                return self::export_availability();
            case 'bookings':
                return self::export_bookings();
            default:
                return array();
        }
    }

    /**
     * Import a payload.
     *
     * @param string $dataset Dataset key.
     * @param array  $payload Payload.
     * @return string
     */
    private static function import_payload( $dataset, $payload ) {
        $data = $payload['data'];
        $meta = $payload['meta'] ?? array();
        $summary = array();

        switch ( $dataset ) {
            case 'full_backup':
                $summary[] = self::import_categories( $data['categories'] ?? array(), $meta );
                $summary[] = self::import_services( $data['services'] ?? array(), $meta );
                $summary[] = self::import_addons( $data['addons'] ?? array(), $meta );
                $summary[] = self::import_staff( $data['staff'] ?? array(), $meta );
                $summary[] = self::import_customers( $data['customers'] ?? array(), $meta );
                $summary[] = self::import_availability( $data['availability'] ?? array(), $meta );
                $summary[] = self::import_bookings( $data['bookings'] ?? array(), $meta );
                break;
            case 'categories':
                $summary[] = self::import_categories( $data, $meta );
                break;
            case 'services':
                $summary[] = self::import_services( $data, $meta );
                break;
            case 'addons':
                $summary[] = self::import_addons( $data, $meta );
                break;
            case 'customers':
                $summary[] = self::import_customers( $data, $meta );
                break;
            case 'staff':
                $summary[] = self::import_staff( $data, $meta );
                break;
            case 'availability':
                $summary[] = self::import_availability( $data, $meta );
                break;
            case 'bookings':
                $summary[] = self::import_bookings( $data, $meta );
                break;
        }

        return implode( ' ', array_filter( $summary ) );
    }

    /**
     * Export categories.
     *
     * @return array
     */
    private static function export_categories() {
        $terms = get_terms(
            array(
                'taxonomy'   => 'sodek_gb_service_cat',
                'hide_empty' => false,
            )
        );

        $data = array();
        foreach ( $terms as $term ) {
            $parent = $term->parent ? get_term( $term->parent, 'sodek_gb_service_cat' ) : null;
            $data[] = array(
                'legacy_id'   => (int) $term->term_id,
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'parent_slug' => $parent && ! is_wp_error( $parent ) ? $parent->slug : '',
            );
        }

        return $data;
    }

    /**
     * Export services.
     *
     * @return array
     */
    private static function export_services() {
        $services = Sodek_GB_Service::get_all_services();
        $data = array();

        foreach ( $services as $service ) {
            $post = get_post( $service['id'] );
            $data[] = array(
                'legacy_id'              => (int) $service['id'],
                'title'                  => $post ? $post->post_title : $service['title'],
                'slug'                   => $post ? $post->post_name : sanitize_title( $service['title'] ),
                'content'                => $post ? $post->post_content : '',
                'excerpt'                => $post ? $post->post_excerpt : '',
                'menu_order'             => $post ? (int) $post->menu_order : 0,
                'featured_image_url'     => $service['featured_image'] ?? '',
                'category_slugs'         => wp_list_pluck( $service['categories'] ?? array(), 'slug' ),
                'duration'               => (int) $service['duration'],
                'buffer_before'          => (int) $service['buffer_before'],
                'buffer_after'           => (int) $service['buffer_after'],
                'max_daily_bookings'     => $service['max_daily_bookings'],
                'price'                  => (float) $service['price'],
                'deposit_type'           => $service['deposit_type'],
                'deposit_value'          => (float) $service['deposit_value'],
                'show_on_frontend'       => ! empty( $service['show_on_frontend'] ),
                'enable_staff_selection' => ! empty( $service['enable_staff_selection'] ),
                'show_image_override'    => $service['show_image_override'] ?? 'global',
                'show_deposit_override'  => $service['show_deposit_override'] ?? 'global',
            );
        }

        return $data;
    }

    /**
     * Export add-ons.
     *
     * @return array
     */
    private static function export_addons() {
        $addons = Sodek_GB_Addon::get_all_addons();
        $data = array();

        foreach ( $addons as $addon ) {
            $post = get_post( $addon['id'] );
            $service_slugs = array();

            if ( ! empty( $addon['services'] ) ) {
                foreach ( $addon['services'] as $service_id ) {
                    $service_post = get_post( $service_id );
                    if ( $service_post ) {
                        $service_slugs[] = $service_post->post_name;
                    }
                }
            }

            $data[] = array(
                'legacy_id'     => (int) $addon['id'],
                'title'         => $post ? $post->post_title : $addon['title'],
                'slug'          => $post ? $post->post_name : sanitize_title( $addon['title'] ),
                'price'         => (float) $addon['price'],
                'duration'      => (int) $addon['duration'],
                'description'   => $addon['description'],
                'image_url'     => $addon['image_url'] ?? '',
                'all_services'  => ! empty( $addon['all_services'] ),
                'service_slugs' => array_values( array_unique( array_filter( $service_slugs ) ) ),
            );
        }

        return $data;
    }

    /**
     * Export customers.
     *
     * @return array
     */
    private static function export_customers() {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';
        $rows = $wpdb->get_results( "SELECT * FROM $table ORDER BY id ASC", ARRAY_A );
        $data = array();
        $exported_lookup = array();

        foreach ( $rows as $row ) {
            $export_row = self::build_export_customer_row( $row );
            $data[] = $export_row;

            $lookup_key = self::build_customer_export_lookup_key( $export_row['email'] ?? '', $export_row['phone'] ?? '' );
            if ( $lookup_key ) {
                $exported_lookup[ $lookup_key ] = true;
            }
        }

        $booking_posts = get_posts(
            array(
                'post_type'      => 'sodek_gb_booking',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            )
        );

        foreach ( $booking_posts as $booking_id ) {
            $customer_email = Sodek_GB_Customer::normalize_email( get_post_meta( $booking_id, '_sodek_gb_customer_email', true ) );
            $customer_phone = Sodek_GB_Customer::normalize_phone( get_post_meta( $booking_id, '_sodek_gb_customer_phone', true ) );
            $lookup_key     = self::build_customer_export_lookup_key( $customer_email, $customer_phone );

            if ( empty( $lookup_key ) || isset( $exported_lookup[ $lookup_key ] ) ) {
                continue;
            }

            $customer_id = (int) get_post_meta( $booking_id, '_sodek_gb_customer_profile_id', true );
            if ( ! $customer_id ) {
                $customer_id = (int) get_post_meta( $booking_id, '_sodek_gb_customer_id', true );
            }

            $customer_row = $customer_id ? Sodek_GB_Customer::get_by_id( $customer_id ) : null;

            if ( $customer_row ) {
                $export_row = self::build_export_customer_row( $customer_row );
            } else {
                $customer_name = sanitize_text_field( get_post_meta( $booking_id, '_sodek_gb_customer_name', true ) );
                $linked_user   = $customer_email ? get_user_by( 'email', $customer_email ) : false;

                $export_row = array(
                    'phone'                     => $customer_phone,
                    'phone_country_code'        => '',
                    'email'                     => $customer_email,
                    'first_name'                => self::extract_first_name( $customer_name ),
                    'last_name'                 => self::extract_last_name( $customer_name ),
                    'wp_user_id'                => $linked_user ? (int) $linked_user->ID : null,
                    'preferred_staff_id'        => null,
                    'hair_type'                 => '',
                    'hair_length'               => '',
                    'allergies'                 => '',
                    'notes'                     => '',
                    'sms_opt_in'                => 1,
                    'email_opt_in'              => 1,
                    'phone_verified'            => 0,
                    'phone_verified_at'         => null,
                    'total_bookings'            => 0,
                    'total_spent'               => 0,
                    'created_at'                => get_post_field( 'post_date', $booking_id ),
                    'preferred_staff_email'     => '',
                    'preferred_staff_legacy_id' => 0,
                    'wp_user_email'             => $linked_user ? $linked_user->user_email : '',
                    'legacy_id'                 => $customer_id,
                    'meta'                      => array(),
                );
            }

            $data[] = $export_row;
            $exported_lookup[ $lookup_key ] = true;
        }

        return $data;
    }

    /**
     * Build a normalized customer export row.
     *
     * @param array $row Customer database row.
     * @return array
     */
    private static function build_export_customer_row( $row ) {
        $customer_id      = (int) ( $row['id'] ?? 0 );
        $meta             = $customer_id ? get_option( 'sodek_gb_customer_meta_' . $customer_id, array() ) : array();
        $preferred_staff  = ! empty( $row['preferred_staff_id'] ) ? get_userdata( (int) $row['preferred_staff_id'] ) : null;
        $linked_user      = ! empty( $row['wp_user_id'] ) ? get_userdata( (int) $row['wp_user_id'] ) : null;

        unset( $row['id'], $row['last_verification_code'], $row['verification_code_expires'] );

        $row['preferred_staff_email']     = $preferred_staff ? $preferred_staff->user_email : '';
        $row['preferred_staff_legacy_id'] = ! empty( $row['preferred_staff_id'] ) ? (int) $row['preferred_staff_id'] : 0;
        $row['wp_user_email']             = $linked_user ? $linked_user->user_email : '';
        $row['legacy_id']                 = $customer_id;
        $row['meta']                      = is_array( $meta ) ? $meta : array();

        return $row;
    }

    /**
     * Build a stable lookup key for exported customers.
     *
     * @param string $email Customer email.
     * @param string $phone Customer phone.
     * @return string
     */
    private static function build_customer_export_lookup_key( $email, $phone ) {
        $email = Sodek_GB_Customer::normalize_email( $email );
        $phone = Sodek_GB_Customer::normalize_phone( $phone );

        if ( ! empty( $email ) ) {
            return 'email:' . $email;
        }

        if ( ! empty( $phone ) ) {
            return 'phone:' . $phone;
        }

        return '';
    }

    /**
     * Export staff.
     *
     * @return array
     */
    private static function export_staff() {
        $staff_members = Sodek_GB_Staff::get_all_staff();
        $data = array();

        foreach ( $staff_members as $staff ) {
            $service_slugs = array();
            foreach ( (array) $staff['services'] as $service_id ) {
                $service_post = get_post( (int) $service_id );
                if ( $service_post ) {
                    $service_slugs[] = $service_post->post_name;
                }
            }

            $data[] = array(
                'legacy_id'     => (int) $staff['id'],
                'email'         => $staff['email'],
                'name'          => $staff['name'],
                'phone'         => $staff['phone'],
                'bio'           => $staff['bio'],
                'photo_url'     => $staff['photo'] ? wp_get_attachment_url( (int) $staff['photo'] ) : '',
                'is_staff'      => ! empty( $staff['is_staff'] ),
                'is_active'     => ! empty( $staff['is_active'] ),
                'color'         => $staff['color'],
                'service_slugs' => array_values( array_unique( array_filter( $service_slugs ) ) ),
            );
        }

        return $data;
    }

    /**
     * Export availability data.
     *
     * @return array
     */
    private static function export_availability() {
        global $wpdb;

        $staff_members = Sodek_GB_Staff::get_all_staff();
        $staff_data = array();

        foreach ( $staff_members as $staff ) {
            $staff_data[] = array(
                'legacy_id' => (int) $staff['id'],
                'email'     => $staff['email'],
                'schedule'  => Sodek_GB_Staff_Availability::get_staff_schedule( $staff['id'] ),
                'time_off'  => Sodek_GB_Staff_Availability::get_time_off( $staff['id'] ),
            );
        }

        $table = $wpdb->prefix . 'sodek_gb_availability_overrides';
        $overrides = $wpdb->get_results(
            "SELECT override_date, start_time, end_time, type, reason FROM $table ORDER BY override_date ASC, start_time ASC",
            ARRAY_A
        );

        return array(
            'weekly_schedule' => Sodek_GB_Availability::get_weekly_schedule(),
            'overrides'       => $overrides,
            'staff'           => $staff_data,
        );
    }

    /**
     * Export bookings.
     *
     * @return array
     */
    private static function export_bookings() {
        $posts = get_posts(
            array(
                'post_type'      => 'sodek_gb_booking',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'ASC',
            )
        );

        $data = array();
        foreach ( $posts as $post ) {
            $booking = Sodek_GB_Booking::get_booking( $post->ID );
            if ( ! $booking ) {
                continue;
            }

            $staff = ! empty( $booking['staff_id'] ) ? get_userdata( (int) $booking['staff_id'] ) : null;

            $data[] = array(
                'legacy_id'       => (int) $booking['id'],
                'title'           => $booking['title'],
                'service_slug'    => $booking['service'] ? get_post_field( 'post_name', (int) $booking['service_id'] ) : '',
                'booking_date'    => $booking['booking_date'],
                'start_time'      => $booking['start_time'],
                'end_time'        => $booking['end_time'],
                'customer_legacy_id' => ! empty( $booking['customer_id'] ) ? (int) $booking['customer_id'] : 0,
                'customer_name'   => $booking['customer_name'],
                'customer_email'  => $booking['customer_email'],
                'customer_phone'  => $booking['customer_phone'],
                'staff_email'     => $staff ? $staff->user_email : '',
                'staff_legacy_id' => $staff ? (int) $staff->ID : 0,
                'status'          => $booking['status'],
                'deposit_amount'  => (float) $booking['deposit_amount'],
                'total_price'     => (float) $booking['total_price'],
                'deposit_paid'    => ! empty( $booking['deposit_paid'] ),
                'balance_paid'    => ! empty( $booking['balance_paid'] ),
                'notes'           => $booking['notes'],
                'admin_notes'     => $booking['admin_notes'],
                'order_id'        => (int) $booking['order_id'],
                'payment_method'  => get_post_meta( $post->ID, '_sodek_gb_payment_method', true ),
                'transaction_id'  => get_post_meta( $post->ID, '_sodek_gb_transaction_id', true ),
                'balance_paid_at' => get_post_meta( $post->ID, '_sodek_gb_balance_paid_at', true ),
                'payment_source'  => get_post_meta( $post->ID, '_sodek_gb_balance_payment_source', true ),
                'payment_label'   => get_post_meta( $post->ID, '_sodek_gb_balance_payment_method_label', true ),
                'addon_slugs'     => array_values(
                    array_filter(
                        array_map(
                            function( $addon ) {
                                return get_post_field( 'post_name', (int) $addon['id'] );
                            },
                            $booking['addons']
                        )
                    )
                ),
                'created_at'      => $booking['created_at'],
            );
        }

        return $data;
    }

    /**
     * Import categories.
     *
     * @param array $items Categories.
     * @return string
     */
    private static function import_categories( $items, $meta = array() ) {
        if ( ! is_array( $items ) ) {
            return '';
        }

        $created = 0;
        $updated = 0;

        foreach ( $items as $item ) {
            $slug = sanitize_title( $item['slug'] ?? $item['name'] ?? '' );
            $name = sanitize_text_field( $item['name'] ?? '' );

            if ( empty( $slug ) || empty( $name ) ) {
                continue;
            }

            $source_key = self::build_entity_import_source_key( $meta, 'category', $item, $slug );
            $existing_id = self::find_term_id_by_import_source_key( $source_key );
            $existing = $existing_id ? get_term( $existing_id, 'sodek_gb_service_cat' ) : get_term_by( 'slug', $slug, 'sodek_gb_service_cat' );
            if ( $existing && ! is_wp_error( $existing ) ) {
                wp_update_term(
                    $existing->term_id,
                    'sodek_gb_service_cat',
                    array(
                        'name'        => $name,
                        'description' => sanitize_textarea_field( $item['description'] ?? '' ),
                    )
                );
                update_term_meta( $existing->term_id, self::IMPORT_SOURCE_META_KEY, $source_key );
                $updated++;
            } else {
                $inserted = wp_insert_term(
                    $name,
                    'sodek_gb_service_cat',
                    array(
                        'slug'        => $slug,
                        'description' => sanitize_textarea_field( $item['description'] ?? '' ),
                    )
                );
                if ( ! is_wp_error( $inserted ) && ! empty( $inserted['term_id'] ) ) {
                    update_term_meta( (int) $inserted['term_id'], self::IMPORT_SOURCE_META_KEY, $source_key );
                }
                $created++;
            }
        }

        foreach ( $items as $item ) {
            if ( empty( $item['parent_slug'] ) || empty( $item['slug'] ) ) {
                continue;
            }

            $term = get_term_by( 'slug', sanitize_title( $item['slug'] ), 'sodek_gb_service_cat' );
            $parent = get_term_by( 'slug', sanitize_title( $item['parent_slug'] ), 'sodek_gb_service_cat' );

            if ( $term && $parent && ! is_wp_error( $term ) && ! is_wp_error( $parent ) ) {
                wp_update_term(
                    $term->term_id,
                    'sodek_gb_service_cat',
                    array(
                        'parent' => $parent->term_id,
                    )
                );
            }
        }

        return sprintf(
            __( 'Categories created: %1$d, updated: %2$d.', 'glowbook' ),
            $created,
            $updated
        );
    }

    /**
     * Import services.
     *
     * @param array $items Services.
     * @return string
     */
    private static function import_services( $items, $meta = array() ) {
        if ( ! is_array( $items ) ) {
            return '';
        }

        $created = 0;
        $updated = 0;

        foreach ( $items as $item ) {
            $slug = sanitize_title( $item['slug'] ?? $item['title'] ?? '' );
            $title = sanitize_text_field( $item['title'] ?? '' );

            if ( empty( $slug ) || empty( $title ) ) {
                continue;
            }

            $source_key = self::build_entity_import_source_key( $meta, 'service', $item, $slug );
            $post_id = self::find_post_id_by_import_source_key( 'sodek_gb_service', $source_key );
            if ( ! $post_id ) {
                $post_id = self::find_post_id_by_slug( 'sodek_gb_service', $slug );
            }
            $postarr = array(
                'post_type'    => 'sodek_gb_service',
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_content' => wp_kses_post( $item['content'] ?? '' ),
                'post_excerpt' => sanitize_textarea_field( $item['excerpt'] ?? '' ),
                'menu_order'   => isset( $item['menu_order'] ) ? absint( $item['menu_order'] ) : 0,
                'post_status'  => 'publish',
            );

            if ( $post_id ) {
                $postarr['ID'] = $post_id;
                wp_update_post( $postarr );
                $updated++;
            } else {
                $post_id = wp_insert_post( $postarr );
                if ( is_wp_error( $post_id ) || ! $post_id ) {
                    continue;
                }
                $created++;
            }

            update_post_meta( $post_id, '_sodek_gb_duration', absint( $item['duration'] ?? 60 ) );
            update_post_meta( $post_id, '_sodek_gb_buffer_before', absint( $item['buffer_before'] ?? 0 ) );
            update_post_meta( $post_id, '_sodek_gb_buffer_after', absint( $item['buffer_after'] ?? 15 ) );
            update_post_meta( $post_id, '_sodek_gb_max_daily_bookings', sanitize_text_field( $item['max_daily_bookings'] ?? '' ) );
            update_post_meta( $post_id, '_sodek_gb_price', (float) ( $item['price'] ?? 0 ) );
            update_post_meta( $post_id, '_sodek_gb_deposit_type', sanitize_key( $item['deposit_type'] ?? 'fixed' ) );
            update_post_meta( $post_id, '_sodek_gb_deposit_value', (float) ( $item['deposit_value'] ?? 0 ) );
            update_post_meta( $post_id, '_sodek_gb_show_on_frontend', ! empty( $item['show_on_frontend'] ) ? 'yes' : 'no' );
            update_post_meta( $post_id, '_sodek_gb_enable_staff_selection', ! empty( $item['enable_staff_selection'] ) ? 'yes' : 'no' );
            update_post_meta( $post_id, '_sodek_gb_show_image_override', sanitize_key( $item['show_image_override'] ?? 'global' ) );
            update_post_meta( $post_id, '_sodek_gb_show_deposit_override', sanitize_key( $item['show_deposit_override'] ?? 'global' ) );
            update_post_meta( $post_id, self::IMPORT_SOURCE_META_KEY, $source_key );

            $term_ids = array();
            if ( ! empty( $item['category_slugs'] ) && is_array( $item['category_slugs'] ) ) {
                foreach ( $item['category_slugs'] as $category_slug ) {
                    $term = get_term_by( 'slug', sanitize_title( $category_slug ), 'sodek_gb_service_cat' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $term_ids[] = (int) $term->term_id;
                    }
                }
            }
            wp_set_object_terms( $post_id, $term_ids, 'sodek_gb_service_cat' );

            self::maybe_attach_image_to_post( $post_id, esc_url_raw( $item['featured_image_url'] ?? '' ), true );
        }

        return sprintf(
            __( 'Services created: %1$d, updated: %2$d.', 'glowbook' ),
            $created,
            $updated
        );
    }

    /**
     * Import add-ons.
     *
     * @param array $items Add-ons.
     * @return string
     */
    private static function import_addons( $items, $meta = array() ) {
        if ( ! is_array( $items ) ) {
            return '';
        }

        $created = 0;
        $updated = 0;

        foreach ( $items as $item ) {
            $slug = sanitize_title( $item['slug'] ?? $item['title'] ?? '' );
            $title = sanitize_text_field( $item['title'] ?? '' );

            if ( empty( $slug ) || empty( $title ) ) {
                continue;
            }

            $source_key = self::build_entity_import_source_key( $meta, 'addon', $item, $slug );
            $post_id = self::find_post_id_by_import_source_key( 'sodek_gb_addon', $source_key );
            if ( ! $post_id ) {
                $post_id = self::find_post_id_by_slug( 'sodek_gb_addon', $slug );
            }
            $postarr = array(
                'post_type'   => 'sodek_gb_addon',
                'post_title'  => $title,
                'post_name'   => $slug,
                'post_status' => 'publish',
            );

            if ( $post_id ) {
                $postarr['ID'] = $post_id;
                wp_update_post( $postarr );
                $updated++;
            } else {
                $post_id = wp_insert_post( $postarr );
                if ( is_wp_error( $post_id ) || ! $post_id ) {
                    continue;
                }
                $created++;
            }

            update_post_meta( $post_id, '_sodek_gb_addon_price', (float) ( $item['price'] ?? 0 ) );
            update_post_meta( $post_id, '_sodek_gb_addon_duration', absint( $item['duration'] ?? 0 ) );
            update_post_meta( $post_id, '_sodek_gb_addon_description', sanitize_textarea_field( $item['description'] ?? '' ) );
            update_post_meta( $post_id, '_sodek_gb_addon_all_services', ! empty( $item['all_services'] ) ? 'yes' : 'no' );
            update_post_meta( $post_id, self::IMPORT_SOURCE_META_KEY, $source_key );

            $service_ids = array();
            if ( empty( $item['all_services'] ) && ! empty( $item['service_slugs'] ) && is_array( $item['service_slugs'] ) ) {
                foreach ( $item['service_slugs'] as $service_slug ) {
                    $service_id = self::resolve_related_post_id( 'sodek_gb_service', 'service', array( 'slug' => $service_slug ), $meta );
                    if ( $service_id ) {
                        $service_ids[] = (int) $service_id;
                    }
                }
            }
            update_post_meta( $post_id, '_sodek_gb_addon_services', array_values( array_unique( $service_ids ) ) );

            $image_id = self::maybe_attach_image_to_post( $post_id, esc_url_raw( $item['image_url'] ?? '' ), false );
            if ( $image_id ) {
                update_post_meta( $post_id, '_sodek_gb_addon_image_id', $image_id );
            }
        }

        return sprintf(
            __( 'Add-ons created: %1$d, updated: %2$d.', 'glowbook' ),
            $created,
            $updated
        );
    }

    /**
     * Import customers.
     *
     * @param array $items Customers.
     * @return string
     */
    private static function import_customers( $items, $meta = array() ) {
        if ( ! is_array( $items ) ) {
            return '';
        }

        $created = 0;
        $updated = 0;

        foreach ( $items as $item ) {
            $email = sanitize_email( $item['email'] ?? '' );
            $phone = self::sanitize_phone_number( $item['phone'] ?? '' );

            $source_key = self::build_entity_import_source_key( $meta, 'customer', $item, $email ?: $phone );
            $existing = self::find_customer_by_import_source_key( $source_key );
            if ( ! $existing && $email ) {
                $existing = Sodek_GB_Customer::get_by_email( $email );
            }
            if ( ! $existing && $phone ) {
                $existing = Sodek_GB_Customer::get_by_phone( $phone );
            }

            $linked_user = $email ? get_user_by( 'email', $email ) : false;
            $preferred_staff = self::resolve_staff_user(
                $item['preferred_staff_email'] ?? '',
                $item['preferred_staff_legacy_id'] ?? 0,
                $meta
            );

            $customer_data = array(
                'phone'              => $phone,
                'phone_country_code' => sanitize_text_field( $item['phone_country_code'] ?? '+1' ),
                'email'              => $email,
                'first_name'         => sanitize_text_field( $item['first_name'] ?? '' ),
                'last_name'          => sanitize_text_field( $item['last_name'] ?? '' ),
                'wp_user_id'         => $linked_user ? (int) $linked_user->ID : null,
                'preferred_staff_id' => $preferred_staff ? (int) $preferred_staff->ID : null,
                'hair_type'          => sanitize_text_field( $item['hair_type'] ?? '' ),
                'hair_length'        => sanitize_text_field( $item['hair_length'] ?? '' ),
                'allergies'          => sanitize_textarea_field( $item['allergies'] ?? '' ),
                'notes'              => sanitize_textarea_field( $item['notes'] ?? '' ),
                'sms_opt_in'         => ! empty( $item['sms_opt_in'] ) ? 1 : 0,
                'email_opt_in'       => ! empty( $item['email_opt_in'] ) ? 1 : 0,
            );

            if ( $existing ) {
                Sodek_GB_Customer::update( (int) $existing['id'], $customer_data );
                $customer_id = (int) $existing['id'];
                $updated++;
            } else {
                $customer_id = (int) Sodek_GB_Customer::create( $customer_data );
                if ( ! $customer_id ) {
                    continue;
                }
                $created++;
            }

            if ( ! empty( $item['meta'] ) && is_array( $item['meta'] ) ) {
                foreach ( $item['meta'] as $meta_key => $meta_value ) {
                    Sodek_GB_Customer::update_meta( $customer_id, sanitize_key( $meta_key ), $meta_value );
                }
            }
            Sodek_GB_Customer::update_meta( $customer_id, 'import_source_key', $source_key );
        }

        return sprintf(
            __( 'Customers created: %1$d, updated: %2$d.', 'glowbook' ),
            $created,
            $updated
        );
    }

    /**
     * Import staff.
     *
     * @param array $items Staff.
     * @return string
     */
    private static function import_staff( $items, $meta = array() ) {
        if ( ! is_array( $items ) ) {
            return '';
        }

        $created = 0;
        $updated = 0;

        foreach ( $items as $item ) {
            $email = sanitize_email( $item['email'] ?? '' );
            $name = sanitize_text_field( $item['name'] ?? '' );

            if ( empty( $email ) || empty( $name ) ) {
                continue;
            }

            $source_key = self::build_entity_import_source_key( $meta, 'staff', $item, $email );
            $user_id = self::find_user_id_by_import_source_key( $source_key );
            $user = $user_id ? get_user_by( 'id', $user_id ) : get_user_by( 'email', $email );
            if ( $user ) {
                wp_update_user(
                    array(
                        'ID'           => $user->ID,
                        'display_name' => $name,
                    )
                );
                $user_id = $user->ID;
                $updated++;
            } else {
                $user_id = wp_create_user( $email, wp_generate_password( 20, true ), $email );
                if ( is_wp_error( $user_id ) ) {
                    continue;
                }

                wp_update_user(
                    array(
                        'ID'           => $user_id,
                        'display_name' => $name,
                        'role'         => Sodek_GB_Staff::ROLE_NAME,
                    )
                );
                $created++;
            }

            update_user_meta( $user_id, '_sodek_gb_is_staff', ! empty( $item['is_staff'] ) ? '1' : '0' );
            update_user_meta( $user_id, '_sodek_gb_staff_active', ! empty( $item['is_active'] ) ? '1' : '0' );
            update_user_meta( $user_id, '_sodek_gb_staff_phone', sanitize_text_field( $item['phone'] ?? '' ) );
            update_user_meta( $user_id, '_sodek_gb_staff_bio', sanitize_textarea_field( $item['bio'] ?? '' ) );
            update_user_meta( $user_id, '_sodek_gb_staff_color', sanitize_hex_color( $item['color'] ?? '#3788d8' ) ?: '#3788d8' );
            update_user_meta( $user_id, self::IMPORT_SOURCE_META_KEY, $source_key );

            $service_ids = array();
            if ( ! empty( $item['service_slugs'] ) && is_array( $item['service_slugs'] ) ) {
                foreach ( $item['service_slugs'] as $service_slug ) {
                    $service_id = self::resolve_related_post_id( 'sodek_gb_service', 'service', array( 'slug' => $service_slug ), $meta );
                    if ( $service_id ) {
                        $service_ids[] = (int) $service_id;
                    }
                }
            }
            update_user_meta( $user_id, '_sodek_gb_staff_services', array_values( array_unique( $service_ids ) ) );

            $photo_id = self::maybe_attach_image_to_post( 0, esc_url_raw( $item['photo_url'] ?? '' ), false );
            if ( $photo_id ) {
                update_user_meta( $user_id, '_sodek_gb_staff_photo', $photo_id );
            }
        }

        return sprintf(
            __( 'Staff created: %1$d, updated: %2$d.', 'glowbook' ),
            $created,
            $updated
        );
    }

    /**
     * Import availability.
     *
     * @param array $data Availability data.
     * @param array $meta Payload metadata.
     * @return string
     */
    private static function import_availability( $data, $meta = array() ) {
        if ( ! is_array( $data ) ) {
            return '';
        }

        global $wpdb;

        if ( ! empty( $data['weekly_schedule'] ) && is_array( $data['weekly_schedule'] ) ) {
            Sodek_GB_Availability::update_weekly_schedule( $data['weekly_schedule'] );
        }

        $overrides_table = $wpdb->prefix . 'sodek_gb_availability_overrides';
        if ( isset( $data['overrides'] ) && is_array( $data['overrides'] ) ) {
            $wpdb->query( "DELETE FROM $overrides_table" );
            foreach ( $data['overrides'] as $override ) {
                Sodek_GB_Availability::add_override(
                    array(
                        'date'       => sanitize_text_field( $override['override_date'] ?? '' ),
                        'start_time' => sanitize_text_field( $override['start_time'] ?? '' ),
                        'end_time'   => sanitize_text_field( $override['end_time'] ?? '' ),
                        'type'       => sanitize_key( $override['type'] ?? 'block' ),
                        'reason'     => sanitize_text_field( $override['reason'] ?? '' ),
                    )
                );
            }
        }

        if ( ! empty( $data['staff'] ) && is_array( $data['staff'] ) ) {
            foreach ( $data['staff'] as $staff_item ) {
                $user = self::resolve_staff_user(
                    $staff_item['email'] ?? '',
                    $staff_item['legacy_id'] ?? 0,
                    $meta
                );
                if ( ! $user ) {
                    continue;
                }

                if ( ! empty( $staff_item['schedule'] ) && is_array( $staff_item['schedule'] ) ) {
                    Sodek_GB_Staff_Availability::update_schedule( $user->ID, $staff_item['schedule'] );
                }

                if ( isset( $staff_item['time_off'] ) && is_array( $staff_item['time_off'] ) ) {
                    $table = $wpdb->prefix . 'sodek_gb_staff_time_off';
                    $wpdb->delete( $table, array( 'staff_id' => $user->ID ), array( '%d' ) );

                    foreach ( $staff_item['time_off'] as $time_off ) {
                        Sodek_GB_Staff_Availability::add_time_off(
                            $user->ID,
                            sanitize_text_field( $time_off['date_start'] ?? '' ),
                            sanitize_text_field( $time_off['date_end'] ?? '' ),
                            sanitize_text_field( $time_off['reason'] ?? '' )
                        );
                    }
                }
            }
        }

        return __( 'Availability schedule, blocked dates, and staff schedules imported.', 'glowbook' );
    }

    /**
     * Import bookings.
     *
     * @param array $items Booking items.
     * @param array $meta  Payload metadata.
     * @return string
     */
    private static function import_bookings( $items, $meta = array() ) {
        if ( ! is_array( $items ) ) {
            return '';
        }

        $created = 0;
        $updated = 0;

        foreach ( $items as $item ) {
            $service_id = self::resolve_related_post_id(
                'sodek_gb_service',
                'service',
                $item,
                $meta,
                $item['service_slug'] ?? ''
            );
            if ( ! $service_id ) {
                continue;
            }

            $email = sanitize_email( $item['customer_email'] ?? '' );
            $phone = self::sanitize_phone_number( $item['customer_phone'] ?? '' );
            $customer_id = self::resolve_booking_customer_id( $item, $meta, $email, $phone );

            $addon_ids = array();
            foreach ( (array) ( $item['addon_slugs'] ?? array() ) as $addon_slug ) {
                $addon_id = self::resolve_related_post_id( 'sodek_gb_addon', 'addon', array( 'slug' => $addon_slug ), $meta, $addon_slug );
                if ( $addon_id ) {
                    $addon_ids[] = (int) $addon_id;
                }
            }

            $staff_id = 0;
            if ( ! empty( $item['staff_email'] ) || ! empty( $item['staff_legacy_id'] ) ) {
                $staff_user = self::resolve_staff_user( $item['staff_email'] ?? '', $item['staff_legacy_id'] ?? 0, $meta );
                $staff_id = $staff_user ? (int) $staff_user->ID : 0;
            }

            $booking_data = array(
                'service_id'     => $service_id,
                'booking_date'   => sanitize_text_field( $item['booking_date'] ?? '' ),
                'start_time'     => sanitize_text_field( $item['start_time'] ?? '' ),
                'end_time'       => sanitize_text_field( $item['end_time'] ?? '' ),
                'customer_name'  => sanitize_text_field( $item['customer_name'] ?? '' ),
                'customer_email' => $email,
                'customer_phone' => $phone,
                'customer_id'    => $customer_id,
                'status'         => sanitize_key( $item['status'] ?? Sodek_GB_Booking::STATUS_PENDING ),
                'addon_ids'      => $addon_ids,
                'total_price'    => (float) ( $item['total_price'] ?? 0 ),
                'deposit_amount' => (float) ( $item['deposit_amount'] ?? 0 ),
                'deposit_paid'   => ! empty( $item['deposit_paid'] ),
                'balance_paid'   => ! empty( $item['balance_paid'] ),
                'notes'          => sanitize_textarea_field( $item['notes'] ?? '' ),
                'payment_method' => sanitize_text_field( $item['payment_method'] ?? '' ),
                'transaction_id' => sanitize_text_field( $item['transaction_id'] ?? '' ),
                'staff_id'       => $staff_id,
                'order_id'       => absint( $item['order_id'] ?? 0 ),
            );

            $source_key = self::build_import_source_key( $meta, $item );
            $existing_id = self::find_booking_by_source_key( $source_key );

            if ( $existing_id ) {
                self::update_imported_booking( $existing_id, $booking_data, $item, $source_key );
                $updated++;
            } else {
                $booking_id = Sodek_GB_Booking::create_booking( $booking_data );
                if ( is_wp_error( $booking_id ) || ! $booking_id ) {
                    continue;
                }

                update_post_meta( $booking_id, '_sodek_gb_admin_notes', sanitize_textarea_field( $item['admin_notes'] ?? '' ) );
                update_post_meta( $booking_id, '_sodek_gb_import_source_key', $source_key );

                if ( ! empty( $item['balance_paid_at'] ) ) {
                    update_post_meta( $booking_id, '_sodek_gb_balance_paid_at', sanitize_text_field( $item['balance_paid_at'] ) );
                }
                if ( ! empty( $item['payment_source'] ) ) {
                    update_post_meta( $booking_id, '_sodek_gb_balance_payment_source', sanitize_text_field( $item['payment_source'] ) );
                }
                if ( ! empty( $item['payment_label'] ) ) {
                    update_post_meta( $booking_id, '_sodek_gb_balance_payment_method_label', sanitize_text_field( $item['payment_label'] ) );
                }

                self::set_post_created_date( $booking_id, sanitize_text_field( $item['created_at'] ?? '' ) );
                $created++;
            }
        }

        return sprintf(
            __( 'Bookings created: %1$d, updated: %2$d.', 'glowbook' ),
            $created,
            $updated
        );
    }

    /**
     * Update an imported booking.
     *
     * @param int    $booking_id    Booking ID.
     * @param array  $booking_data  Normalized booking data.
     * @param array  $item          Raw item.
     * @param string $source_key    Source key.
     */
    private static function update_imported_booking( $booking_id, $booking_data, $item, $source_key ) {
        $service = Sodek_GB_Service::get_service( $booking_data['service_id'] );
        $title = sprintf(
            '%s - %s %s',
            $booking_data['customer_name'],
            $service ? $service['title'] : '',
            date_i18n( 'M j, Y', strtotime( $booking_data['booking_date'] ) )
        );

        wp_update_post(
            array(
                'ID'         => $booking_id,
                'post_title' => $title,
            )
        );

        update_post_meta( $booking_id, '_sodek_gb_service_id', $booking_data['service_id'] );
        update_post_meta( $booking_id, '_sodek_gb_booking_date', $booking_data['booking_date'] );
        update_post_meta( $booking_id, '_sodek_gb_start_time', $booking_data['start_time'] );
        update_post_meta( $booking_id, '_sodek_gb_end_time', $booking_data['end_time'] );
        update_post_meta( $booking_id, '_sodek_gb_customer_name', $booking_data['customer_name'] );
        update_post_meta( $booking_id, '_sodek_gb_customer_email', $booking_data['customer_email'] );
        update_post_meta( $booking_id, '_sodek_gb_customer_phone', $booking_data['customer_phone'] );
        update_post_meta( $booking_id, '_sodek_gb_customer_id', $booking_data['customer_id'] );
        update_post_meta( $booking_id, '_sodek_gb_order_id', $booking_data['order_id'] );
        update_post_meta( $booking_id, '_sodek_gb_status', $booking_data['status'] );
        update_post_meta( $booking_id, '_sodek_gb_total_price', $booking_data['total_price'] );
        update_post_meta( $booking_id, '_sodek_gb_deposit_amount', $booking_data['deposit_amount'] );
        update_post_meta( $booking_id, '_sodek_gb_deposit_paid', $booking_data['deposit_paid'] ? '1' : '0' );
        update_post_meta( $booking_id, '_sodek_gb_balance_paid', $booking_data['balance_paid'] ? '1' : '0' );
        update_post_meta( $booking_id, '_sodek_gb_customer_notes', $booking_data['notes'] );
        update_post_meta( $booking_id, '_sodek_gb_admin_notes', sanitize_textarea_field( $item['admin_notes'] ?? '' ) );
        update_post_meta( $booking_id, '_sodek_gb_import_source_key', $source_key );
        update_post_meta( $booking_id, '_sodek_gb_payment_method', $booking_data['payment_method'] );
        update_post_meta( $booking_id, '_sodek_gb_transaction_id', $booking_data['transaction_id'] );
        update_post_meta( $booking_id, '_sodek_gb_addon_ids', $booking_data['addon_ids'] );

        if ( ! empty( $booking_data['staff_id'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_staff_id', $booking_data['staff_id'] );
        } else {
            delete_post_meta( $booking_id, '_sodek_gb_staff_id' );
        }

        $addons_price = 0;
        $addons_duration = 0;
        foreach ( $booking_data['addon_ids'] as $addon_id ) {
            $addon = Sodek_GB_Addon::get_addon( $addon_id );
            if ( $addon ) {
                $addons_price += (float) $addon['price'];
                $addons_duration += (int) $addon['duration'];
            }
        }

        update_post_meta( $booking_id, '_sodek_gb_addons_total_price', $addons_price );
        update_post_meta( $booking_id, '_sodek_gb_addons_total_duration', $addons_duration );

        if ( ! empty( $item['balance_paid_at'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_balance_paid_at', sanitize_text_field( $item['balance_paid_at'] ) );
        }
        if ( ! empty( $item['payment_source'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_balance_payment_source', sanitize_text_field( $item['payment_source'] ) );
        }
        if ( ! empty( $item['payment_label'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_balance_payment_method_label', sanitize_text_field( $item['payment_label'] ) );
        }

        self::sync_booking_slot( $booking_id );
        self::set_post_created_date( $booking_id, sanitize_text_field( $item['created_at'] ?? '' ) );
    }

    /**
     * Find a post by slug.
     *
     * @param string $post_type Post type.
     * @param string $slug      Slug.
     * @return int
     */
    private static function find_post_id_by_slug( $post_type, $slug ) {
        $post = get_page_by_path( $slug, OBJECT, $post_type );
        return $post ? (int) $post->ID : 0;
    }

    /**
     * Find a post by import source key.
     *
     * @param string $post_type   Post type.
     * @param string $source_key  Source key.
     * @return int
     */
    private static function find_post_id_by_import_source_key( $post_type, $source_key ) {
        if ( empty( $source_key ) ) {
            return 0;
        }

        $posts = get_posts(
            array(
                'post_type'      => $post_type,
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'   => self::IMPORT_SOURCE_META_KEY,
                        'value' => $source_key,
                    ),
                ),
            )
        );

        return ! empty( $posts ) ? (int) $posts[0] : 0;
    }

    /**
     * Find a term by import source key.
     *
     * @param string $source_key Source key.
     * @return int
     */
    private static function find_term_id_by_import_source_key( $source_key ) {
        if ( empty( $source_key ) ) {
            return 0;
        }

        $terms = get_terms(
            array(
                'taxonomy'   => 'sodek_gb_service_cat',
                'hide_empty' => false,
                'number'     => 1,
                'fields'     => 'ids',
                'meta_query' => array(
                    array(
                        'key'   => self::IMPORT_SOURCE_META_KEY,
                        'value' => $source_key,
                    ),
                ),
            )
        );

        return ! empty( $terms ) && ! is_wp_error( $terms ) ? (int) $terms[0] : 0;
    }

    /**
     * Find a user by import source key.
     *
     * @param string $source_key Source key.
     * @return int
     */
    private static function find_user_id_by_import_source_key( $source_key ) {
        if ( empty( $source_key ) ) {
            return 0;
        }

        $users = get_users(
            array(
                'number'     => 1,
                'fields'     => 'ID',
                'meta_key'   => self::IMPORT_SOURCE_META_KEY,
                'meta_value' => $source_key,
            )
        );

        return ! empty( $users ) ? (int) $users[0] : 0;
    }

    /**
     * Find a customer by import source key.
     *
     * @param string $source_key Source key.
     * @return array|null
     */
    private static function find_customer_by_import_source_key( $source_key ) {
        if ( empty( $source_key ) ) {
            return null;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';
        $like  = 'sodek_gb_customer_meta_%';

        $option_name = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value LIKE %s LIMIT 1",
                $like,
                '%' . $wpdb->esc_like( $source_key ) . '%'
            )
        );

        if ( empty( $option_name ) ) {
            return null;
        }

        $customer_id = (int) str_replace( 'sodek_gb_customer_meta_', '', $option_name );

        if ( $customer_id <= 0 ) {
            return null;
        }

        return Sodek_GB_Customer::get_by_id( $customer_id );
    }

    /**
     * Build a stable source key for imported entities.
     *
     * @param array  $meta          Payload metadata.
     * @param string $entity_type   Entity type.
     * @param array  $item          Entity item.
     * @param string $fallback_key  Fallback identifier.
     * @return string
     */
    private static function build_entity_import_source_key( $meta, $entity_type, $item, $fallback_key = '' ) {
        $site_url  = sanitize_text_field( $meta['site_url'] ?? 'unknown-site' );
        $legacy_id = absint( $item['legacy_id'] ?? 0 );

        if ( $legacy_id ) {
            return $site_url . '|' . $entity_type . '|' . $legacy_id;
        }

        $fallback_key = sanitize_title( (string) $fallback_key );

        if ( ! empty( $fallback_key ) ) {
            return $site_url . '|' . $entity_type . '|fallback|' . $fallback_key;
        }

        return md5( $site_url . '|' . $entity_type . '|' . wp_json_encode( $item ) );
    }

    /**
     * Resolve a related service/add-on post ID during import.
     *
     * @param string $post_type     Post type.
     * @param string $entity_type   Entity type.
     * @param array  $item          Related item.
     * @param array  $meta          Payload metadata.
     * @param string $fallback_slug Fallback slug.
     * @return int
     */
    private static function resolve_related_post_id( $post_type, $entity_type, $item, $meta = array(), $fallback_slug = '' ) {
        $source_key = self::build_entity_import_source_key( $meta, $entity_type, $item, $fallback_slug ?: ( $item['slug'] ?? '' ) );
        $post_id    = self::find_post_id_by_import_source_key( $post_type, $source_key );

        if ( $post_id ) {
            return $post_id;
        }

        $slug = sanitize_title( $fallback_slug ?: ( $item['slug'] ?? '' ) );

        return $slug ? self::find_post_id_by_slug( $post_type, $slug ) : 0;
    }

    /**
     * Resolve a staff user for imported relationships.
     *
     * @param string $staff_email     Staff email.
     * @param int    $staff_legacy_id Legacy ID.
     * @param array  $meta            Payload metadata.
     * @return WP_User|false
     */
    private static function resolve_staff_user( $staff_email, $staff_legacy_id, $meta = array() ) {
        $staff_email = sanitize_email( $staff_email );
        $source_key  = self::build_entity_import_source_key( $meta, 'staff', array( 'legacy_id' => absint( $staff_legacy_id ) ), $staff_email );
        $user_id     = self::find_user_id_by_import_source_key( $source_key );

        if ( $user_id ) {
            return get_user_by( 'id', $user_id );
        }

        return $staff_email ? get_user_by( 'email', $staff_email ) : false;
    }

    /**
     * Resolve booking customer relationship during import.
     *
     * @param array  $item  Booking item.
     * @param array  $meta  Payload metadata.
     * @param string $email Customer email.
     * @param string $phone Customer phone.
     * @return int
     */
    private static function resolve_booking_customer_id( $item, $meta, $email, $phone ) {
        $source_key = self::build_entity_import_source_key( $meta, 'customer', array( 'legacy_id' => absint( $item['customer_legacy_id'] ?? 0 ) ), $email ?: $phone );
        $customer   = self::find_customer_by_import_source_key( $source_key );

        if ( $customer ) {
            return (int) $customer['id'];
        }

        if ( $email ) {
            $customer = Sodek_GB_Customer::get_or_create_by_email(
                $email,
                array(
                    'phone'      => $phone,
                    'first_name' => self::extract_first_name( $item['customer_name'] ?? '' ),
                    'last_name'  => self::extract_last_name( $item['customer_name'] ?? '' ),
                )
            );
        } elseif ( $phone ) {
            $customer = Sodek_GB_Customer::get_or_create_by_phone(
                $phone,
                array(
                    'first_name' => self::extract_first_name( $item['customer_name'] ?? '' ),
                    'last_name'  => self::extract_last_name( $item['customer_name'] ?? '' ),
                )
            );
        } else {
            $customer = null;
        }

        if ( ! empty( $customer['id'] ) ) {
            Sodek_GB_Customer::update_meta( (int) $customer['id'], 'import_source_key', $source_key );
            return (int) $customer['id'];
        }

        return 0;
    }

    /**
     * Build a stable source key for imported bookings.
     *
     * @param array $meta Payload metadata.
     * @param array $item Booking item.
     * @return string
     */
    private static function build_import_source_key( $meta, $item ) {
        $site_url = sanitize_text_field( $meta['site_url'] ?? 'unknown-site' );
        $legacy_id = absint( $item['legacy_id'] ?? 0 );

        if ( $legacy_id ) {
            return $site_url . '|' . $legacy_id;
        }

        return md5( wp_json_encode( $item ) );
    }

    /**
     * Find a booking by import source key.
     *
     * @param string $source_key Source key.
     * @return int
     */
    private static function find_booking_by_source_key( $source_key ) {
        $posts = get_posts(
            array(
                'post_type'      => 'sodek_gb_booking',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'   => '_sodek_gb_import_source_key',
                        'value' => $source_key,
                    ),
                ),
            )
        );

        return ! empty( $posts ) ? (int) $posts[0] : 0;
    }

    /**
     * Attach an image to a post or return an attachment ID.
     *
     * @param int    $post_id       Post ID.
     * @param string $image_url     Image URL.
     * @param bool   $set_thumbnail Whether to set featured image.
     * @return int
     */
    private static function maybe_attach_image_to_post( $post_id, $image_url, $set_thumbnail = false ) {
        $image_url = trim( $image_url );
        if ( empty( $image_url ) ) {
            return 0;
        }

        $attachment_id = attachment_url_to_postid( $image_url );

        if ( ! $attachment_id && preg_match( '#^https?://#i', $image_url ) ) {
            if ( ! function_exists( 'media_sideload_image' ) ) {
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';
            }

            $attachment_id = media_sideload_image( $image_url, $post_id ?: 0, null, 'id' );
            if ( is_wp_error( $attachment_id ) ) {
                $attachment_id = 0;
            }
        }

        if ( $set_thumbnail && $post_id && $attachment_id ) {
            set_post_thumbnail( $post_id, $attachment_id );
        }

        return (int) $attachment_id;
    }

    /**
     * Set a post's created date after import.
     *
     * @param int    $post_id    Post ID.
     * @param string $created_at Date string.
     */
    private static function set_post_created_date( $post_id, $created_at ) {
        if ( empty( $created_at ) ) {
            return;
        }

        $timestamp = strtotime( $created_at );
        if ( ! $timestamp ) {
            return;
        }

        wp_update_post(
            array(
                'ID'            => $post_id,
                'post_date'     => gmdate( 'Y-m-d H:i:s', $timestamp ),
                'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $timestamp ),
            )
        );
    }

    /**
     * Extract first name from a full name string.
     *
     * @param string $full_name Full name.
     * @return string
     */
    private static function extract_first_name( $full_name ) {
        $parts = preg_split( '/\s+/', trim( (string) $full_name ) );
        return sanitize_text_field( $parts[0] ?? '' );
    }

    /**
     * Extract last name from a full name string.
     *
     * @param string $full_name Full name.
     * @return string
     */
    private static function extract_last_name( $full_name ) {
        $parts = preg_split( '/\s+/', trim( (string) $full_name ) );
        if ( empty( $parts ) ) {
            return '';
        }

        array_shift( $parts );

        return sanitize_text_field( trim( implode( ' ', $parts ) ) );
    }

    /**
     * Sync booking data into the booked slots table.
     *
     * @param int $booking_id Booking ID.
     */
    private static function sync_booking_slot( $booking_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_booked_slots';
        $wpdb->delete( $table, array( 'booking_id' => $booking_id ), array( '%d' ) );

        $status = get_post_meta( $booking_id, '_sodek_gb_status', true );
        if ( Sodek_GB_Booking::STATUS_CANCELLED === $status ) {
            return;
        }

        $wpdb->insert(
            $table,
            array(
                'booking_id' => $booking_id,
                'slot_date'  => get_post_meta( $booking_id, '_sodek_gb_booking_date', true ),
                'start_time' => get_post_meta( $booking_id, '_sodek_gb_start_time', true ),
                'end_time'   => get_post_meta( $booking_id, '_sodek_gb_end_time', true ),
                'service_id' => get_post_meta( $booking_id, '_sodek_gb_service_id', true ),
                'status'     => $status,
            ),
            array( '%d', '%s', '%s', '%s', '%d', '%s' )
        );
    }

    /**
     * Store an admin notice for the current user.
     *
     * @param string $type    Notice type.
     * @param string $message Notice message.
     */
    private static function set_admin_notice( $type, $message ) {
        set_transient(
            'sodek_gb_admin_notice_' . get_current_user_id(),
            array(
                'type'    => $type,
                'message' => $message,
            ),
            MINUTE_IN_SECONDS
        );
    }

    /**
     * Get bookings by date.
     *
     * @param string $date Date.
     * @return array
     */
    private static function get_bookings_by_date( $date ) {
        $args = array(
            'post_type'      => Sodek_GB_Booking::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => '_sodek_gb_booking_date',
                    'value' => $date,
                ),
                array(
                    'key'     => '_sodek_gb_status',
                    'value'   => 'cancelled',
                    'compare' => '!=',
                ),
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => '_sodek_gb_start_time',
            'order'          => 'ASC',
        );

        $query = new WP_Query( $args );
        $bookings = array();

        foreach ( $query->posts as $post ) {
            $bookings[] = Sodek_GB_Booking::get_booking( $post->ID );
        }

        return $bookings;
    }

    /**
     * Get upcoming bookings.
     *
     * @param int $days Number of days.
     * @return array
     */
    private static function get_upcoming_bookings( $days = 7 ) {
        $start_date = gmdate( 'Y-m-d' );
        $end_date = gmdate( 'Y-m-d', strtotime( "+{$days} days" ) );

        $args = array(
            'post_type'      => Sodek_GB_Booking::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_sodek_gb_booking_date',
                    'value'   => array( $start_date, $end_date ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ),
                array(
                    'key'     => '_sodek_gb_status',
                    'value'   => array( 'pending', 'confirmed' ),
                    'compare' => 'IN',
                ),
            ),
            'orderby'        => array(
                '_sodek_gb_booking_date' => 'ASC',
                '_sodek_gb_start_time'   => 'ASC',
            ),
        );

        $query = new WP_Query( $args );
        $bookings = array();

        foreach ( $query->posts as $post ) {
            $bookings[] = Sodek_GB_Booking::get_booking( $post->ID );
        }

        return $bookings;
    }

    /**
     * Get dashboard stats.
     *
     * @return array
     */
    private static function get_dashboard_stats() {
        global $wpdb;

        $today = gmdate( 'Y-m-d' );
        $week_start = gmdate( 'Y-m-d', strtotime( 'monday this week' ) );
        $week_end = gmdate( 'Y-m-d', strtotime( 'sunday this week' ) );
        $month_start = gmdate( 'Y-m-01' );
        $month_end = gmdate( 'Y-m-t' );

        // Today's bookings count
        $today_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'sodek_gb_booking' AND p.post_status = 'publish'
                AND pm.meta_key = '_sodek_gb_booking_date' AND pm.meta_value = %s",
                $today
            )
        );

        // This week's bookings
        $week_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE p.post_type = 'sodek_gb_booking' AND p.post_status = 'publish'
                AND pm.meta_key = '_sodek_gb_booking_date' AND pm.meta_value BETWEEN %s AND %s",
                $week_start,
                $week_end
            )
        );

        // Pending bookings
        $pending_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'sodek_gb_booking' AND p.post_status = 'publish'
            AND pm.meta_key = '_sodek_gb_status' AND pm.meta_value = 'pending'"
        );

        // Monthly revenue (from deposits)
        $monthly_revenue = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(pm2.meta_value) FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_sodek_gb_deposit_amount'
                JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_sodek_gb_deposit_paid' AND pm3.meta_value = '1'
                WHERE p.post_type = 'sodek_gb_booking' AND p.post_status = 'publish'
                AND pm.meta_key = '_sodek_gb_booking_date' AND pm.meta_value BETWEEN %s AND %s",
                $month_start,
                $month_end
            )
        );

        return array(
            'today_count'     => (int) $today_count,
            'week_count'      => (int) $week_count,
            'pending_count'   => (int) $pending_count,
            'monthly_revenue' => (float) $monthly_revenue,
        );
    }

    /**
     * Add dashboard widget.
     */
    public static function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'sodek_gb_dashboard_widget',
            __( 'Today\'s Appointments', 'glowbook' ),
            array( __CLASS__, 'render_dashboard_widget' )
        );
    }

    /**
     * Render dashboard widget.
     */
    public static function render_dashboard_widget() {
        $today = gmdate( 'Y-m-d' );
        $bookings = self::get_bookings_by_date( $today );

        if ( empty( $bookings ) ) {
            echo '<p>' . esc_html__( 'No appointments scheduled for today.', 'glowbook' ) . '</p>';
            return;
        }

        echo '<ul class="sodek-gb-widget-bookings">';
        foreach ( $bookings as $booking ) {
            printf(
                '<li><strong>%s</strong> - %s (%s)<br><small>%s</small></li>',
                esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ),
                esc_html( $booking['customer_name'] ),
                esc_html( $booking['service']['title'] ),
                esc_html( ucfirst( $booking['status'] ) )
            );
        }
        echo '</ul>';

        printf(
            '<p><a href="%s" class="button">%s</a></p>',
            esc_url( admin_url( 'admin.php?page=sodek-gb-dashboard' ) ),
            esc_html__( 'View All Bookings', 'glowbook' )
        );
    }

    /**
     * Admin notices.
     */
    public static function admin_notices() {
        $stored_notice = get_transient( 'sodek_gb_admin_notice_' . get_current_user_id() );
        if ( ! empty( $stored_notice['message'] ) ) {
            add_settings_error(
                'sodek_gb_messages',
                'sodek_gb_import_export_notice',
                $stored_notice['message'],
                $stored_notice['type'] ?? 'success'
            );
            delete_transient( 'sodek_gb_admin_notice_' . get_current_user_id() );
        }

        settings_errors( 'sodek_gb_messages' );

        // Check if database needs upgrade and show notice/trigger upgrade
        self::maybe_upgrade_database();
    }

    /**
     * Check and upgrade database if needed.
     */
    private static function maybe_upgrade_database() {
        global $wpdb;

        // Check if customers table exists
        $table_name = $wpdb->prefix . 'sodek_gb_customers';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

        if ( ! $table_exists ) {
            // Run activation to create tables
            if ( class_exists( 'Sodek_GB_Activator' ) ) {
                Sodek_GB_Activator::activate();
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'GlowBook database tables have been created/updated.', 'glowbook' ) . '</p></div>';
            }
        }
    }

    /**
     * Sanitize phone number.
     *
     * @param string $phone Phone number.
     * @return string
     */
    public static function sanitize_phone_number( $phone ) {
        // Keep only digits and + sign
        $phone = preg_replace( '/[^0-9+]/', '', $phone );

        // Ensure it starts with + if it has a country code
        if ( ! empty( $phone ) && $phone[0] !== '+' && strlen( $phone ) > 10 ) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Sanitize a monetary amount.
     *
     * @param mixed $amount Amount.
     * @return float
     */
    public static function sanitize_amount( $amount ) {
        $amount = is_scalar( $amount ) ? (float) $amount : 0;

        return max( 0, round( $amount, 2 ) );
    }

    /**
     * Sanitize Square access token.
     *
     * Encrypts the token if a new value is provided.
     *
     * @param string $token Access token.
     * @return string Encrypted token or empty string.
     */
    public static function sanitize_square_token( $token ) {
        // If empty, return empty (don't overwrite existing)
        if ( empty( $token ) ) {
            return '';
        }

        // If it looks like it's already encrypted (starts with encrypted pattern), return as-is
        // This prevents double-encryption
        if ( strlen( $token ) > 50 && preg_match( '/^[A-Za-z0-9+\/=]+$/', $token ) ) {
            // Check if this is a base64-encoded encrypted value vs a Square token
            // Square tokens typically start with 'EAAA' or similar
            if ( strpos( $token, 'EAAA' ) !== 0 && strpos( $token, 'sq0' ) !== 0 ) {
                return $token;
            }
        }

        // Encrypt the new token
        if ( class_exists( 'Sodek_GB_Gateway_Abstract' ) ) {
            return Sodek_GB_Gateway_Abstract::encrypt_token( $token );
        }

        return $token;
    }

    /**
     * Fix parent menu highlighting for CPT and taxonomy pages.
     *
     * @param string $parent_file Parent file.
     * @return string
     */
    public static function fix_parent_menu( $parent_file ) {
        global $current_screen;

        if ( ! $current_screen ) {
            return $parent_file;
        }

        // For our CPTs and taxonomy
        $our_types = array( 'sodek_gb_booking', 'sodek_gb_service', 'sodek_gb_addon' );
        $our_taxonomies = array( 'sodek_gb_service_cat' );

        if ( in_array( $current_screen->post_type, $our_types, true ) ||
             in_array( $current_screen->taxonomy, $our_taxonomies, true ) ) {
            return 'sodek-gb-dashboard';
        }

        return $parent_file;
    }

    /**
     * Fix submenu highlighting.
     *
     * @param string $submenu_file Submenu file.
     * @return string
     */
    public static function fix_submenu_highlight( $submenu_file ) {
        global $current_screen;

        if ( ! $current_screen ) {
            return $submenu_file;
        }

        // Fix taxonomy submenu
        if ( 'sodek_gb_service_cat' === $current_screen->taxonomy ) {
            return 'edit-tags.php?taxonomy=sodek_gb_service_cat&post_type=sodek_gb_service';
        }

        return $submenu_file;
    }
}
