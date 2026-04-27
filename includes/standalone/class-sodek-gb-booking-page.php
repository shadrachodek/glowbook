<?php
/**
 * Booking Page Handler.
 *
 * Handles the /book/ page rendering and logic.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Booking_Page class.
 */
class Sodek_GB_Booking_Page {

    /**
     * Initialize the booking page.
     */
    public static function init() {
        // Add body class for booking page
        add_filter( 'body_class', array( __CLASS__, 'add_body_class' ) );
    }

    /**
     * Add body class for booking page.
     *
     * @param array $classes Body classes.
     * @return array
     */
    public static function add_body_class( $classes ) {
        if ( get_query_var( 'sodek_gb_page' ) === 'booking' ) {
            $classes[] = 'sodek-gb-booking-page';
            $classes[] = 'sodek-gb-standalone';
        }
        return $classes;
    }

    /**
     * Render the booking page.
     */
    public static function render() {
        // Set page title
        add_filter( 'pre_get_document_title', function() {
            return sprintf(
                /* translators: %s: site name */
                __( 'Book an Appointment - %s', 'glowbook' ),
                get_bloginfo( 'name' )
            );
        } );

        // Get data for the page
        $data = self::get_page_data();

        // Load template
        self::load_template( 'booking-page', $data );
    }

    /**
     * Get page data.
     *
     * @return array
     */
    private static function get_page_data() {
        // Get services grouped by category
        $services_grouped = Sodek_GB_Service::get_services_grouped_by_category();

        // Get all staff
        $all_staff = Sodek_GB_Staff::get_active_staff();

        // Pre-selected values from URL (support both 'service' and 'service_id' parameters)
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
        if ( is_user_logged_in() ) {
            $customer = Sodek_GB_Customer::get_by_user_id( get_current_user_id() );
            if ( ! $customer ) {
                $user = wp_get_current_user();
                $customer = Sodek_GB_Customer::get_by_email( $user->user_email );
            }
        }

        // Get payment configuration
        $payment_config = Sodek_GB_Payment_Manager::get_client_config();

        // Settings
        $settings = array(
            'show_staff_selection' => true,
            'show_any_available'   => (bool) get_option( 'sodek_gb_show_any_available', true ),
            'show_staff_photos'    => (bool) get_option( 'sodek_gb_show_staff_photos', true ),
            'show_staff_bios'      => (bool) get_option( 'sodek_gb_show_staff_bios', true ),
            'allow_guest_booking'  => (bool) get_option( 'sodek_gb_allow_guest_booking', true ),
            'require_phone'        => (bool) get_option( 'sodek_gb_sms_enabled', false ),
            'enable_cards_on_file' => (bool) get_option( 'sodek_gb_enable_cards_on_file', true ),
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
     * Load template file.
     *
     * @param string $template Template name (without .php).
     * @param array  $data     Data to pass to template.
     */
    private static function load_template( $template, $data = array() ) {
        // Allow theme override
        $theme_template = locate_template( "glowbook/standalone/{$template}.php" );

        if ( $theme_template ) {
            $template_path = $theme_template;
        } else {
            $template_path = SODEK_GB_PLUGIN_DIR . "templates/standalone/{$template}.php";
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

    /**
     * Get services for a specific staff member.
     *
     * @param int $staff_id Staff user ID.
     * @return array
     */
    public static function get_services_for_staff( $staff_id ) {
        $staff = Sodek_GB_Staff::get_staff( $staff_id );

        if ( ! $staff ) {
            return array();
        }

        $all_services = Sodek_GB_Service::get_all_services();

        // If staff has no specific services, they can do all
        if ( empty( $staff['services'] ) ) {
            return $all_services;
        }

        return array_filter( $all_services, function( $service ) use ( $staff ) {
            return in_array( $service['id'], $staff['services'], true ) ||
                   in_array( (string) $service['id'], $staff['services'], true );
        } );
    }

    /**
     * Check if staff selection should be shown for a service.
     *
     * @param int $service_id Service ID.
     * @return bool
     */
    public static function should_show_staff_selection( $service_id ) {
        // Check per-service setting
        $per_service = get_post_meta( $service_id, '_sodek_gb_allow_staff_selection', true );

        if ( '' !== $per_service ) {
            return (bool) $per_service;
        }

        // Fall back to global setting
        return true;
    }

    /**
     * Get available dates for a service and optional staff.
     *
     * @param int    $service_id Service ID.
     * @param int    $staff_id   Optional staff ID.
     * @param int    $year       Year.
     * @param int    $month      Month.
     * @return array
     */
    public static function get_available_dates( $service_id, $staff_id, $year, $month ) {
        $dates         = array();
        $days_in_month = cal_days_in_month( CAL_GREGORIAN, $month, $year );
        $max_advance   = (int) get_option( 'sodek_gb_max_booking_advance', 60 );
        $today         = Sodek_GB_Availability::current_date( 'Y-m-d' );
        $max_dt        = Sodek_GB_Availability::create_datetime( 'now' );
        $max_dt->modify( "+{$max_advance} days" );
        $max_date = $max_dt->format( 'Y-m-d' );

        for ( $day = 1; $day <= $days_in_month; $day++ ) {
            $date = sprintf( '%d-%02d-%02d', $year, $month, $day );

            // Skip past dates and dates beyond max advance
            if ( $date < $today || $date > $max_date ) {
                continue;
            }

            // Check if there are available slots
            if ( $staff_id ) {
                $slots = Sodek_GB_Staff_Availability::get_available_slots( $staff_id, $date, $service_id );
            } else {
                $slots = Sodek_GB_Staff_Availability::get_combined_available_slots( $service_id, $date );
            }

            if ( ! empty( $slots ) ) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /**
     * Format price for display.
     *
     * @param float $price Price amount.
     * @return string
     */
    public static function format_price( $price ) {
        if ( function_exists( 'wc_price' ) ) {
            return wc_price( $price );
        }

        return '$' . number_format( $price, 2 );
    }

    /**
     * Format duration for display.
     *
     * @param int $minutes Duration in minutes.
     * @return string
     */
    public static function format_duration( $minutes ) {
        if ( $minutes < 60 ) {
            /* translators: %d: number of minutes */
            return sprintf( _n( '%d min', '%d mins', $minutes, 'glowbook' ), $minutes );
        }

        $hours = floor( $minutes / 60 );
        $remaining_mins = $minutes % 60;

        if ( $remaining_mins === 0 ) {
            /* translators: %d: number of hours */
            return sprintf( _n( '%d hour', '%d hours', $hours, 'glowbook' ), $hours );
        }

        /* translators: 1: hours, 2: minutes */
        return sprintf( __( '%1$dh %2$dm', 'glowbook' ), $hours, $remaining_mins );
    }
}
