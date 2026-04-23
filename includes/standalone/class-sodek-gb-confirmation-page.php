<?php
/**
 * Confirmation Page Handler.
 *
 * Handles the booking confirmation page.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Confirmation_Page class.
 */
class Sodek_GB_Confirmation_Page {

    /**
     * Initialize the confirmation page.
     */
    public static function init() {
        // Add body class
        add_filter( 'body_class', array( __CLASS__, 'add_body_class' ) );
    }

    /**
     * Add body class for confirmation page.
     *
     * @param array $classes Body classes.
     * @return array
     */
    public static function add_body_class( $classes ) {
        if ( get_query_var( 'sodek_gb_page' ) === 'confirmation' ) {
            $classes[] = 'sodek-gb-confirmation-page';
            $classes[] = 'sodek-gb-standalone';
        }
        return $classes;
    }

    /**
     * Render the confirmation page.
     */
    public static function render() {
        $key = get_query_var( 'sodek_gb_key' );

        if ( ! $key ) {
            wp_safe_redirect( Sodek_GB_Standalone_Booking::get_booking_url() );
            exit;
        }

        // Find booking by confirmation key
        $booking = self::get_booking_by_key( $key );

        if ( ! $booking ) {
            // Show not found page
            self::render_not_found();
            return;
        }

        // Set page title
        add_filter( 'pre_get_document_title', function() {
            return sprintf(
                /* translators: %s: site name */
                __( 'Booking Confirmed - %s', 'glowbook' ),
                get_bloginfo( 'name' )
            );
        } );

        // Get additional data
        $data = self::get_confirmation_data( $booking );

        // Load template
        self::load_template( 'confirmation', $data );
    }

    /**
     * Get booking by confirmation key.
     *
     * @param string $key Confirmation key.
     * @return array|null
     */
    private static function get_booking_by_key( $key ) {
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
            return null;
        }

        return Sodek_GB_Booking::get_booking( $booking_id );
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
        if ( ! empty( $booking['staff_id'] ) ) {
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

        // Portal URL. Include a short-lived portal token when possible so a
        // customer can open their portal directly after a successful booking.
        $portal_url  = Sodek_GB_Standalone_Booking::get_portal_url();
        $customer_id = ! empty( $booking['customer_id'] ) ? absint( $booking['customer_id'] ) : 0;

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
            'can_reschedule'  => self::can_reschedule( $booking ),
            'can_cancel'      => self::can_cancel( $booking ),
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
        $ics_content = self::generate_ics( $title, $description, $location, $start_utc, $end_utc, $booking['id'] );
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
    private static function generate_ics( $title, $description, $location, $start, $end, $booking_id ) {
        $uid = 'booking-' . $booking_id . '@' . wp_parse_url( home_url(), PHP_URL_HOST );

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
        $ics .= "SUMMARY:" . self::escape_ics( $title ) . "\r\n";
        $ics .= "DESCRIPTION:" . self::escape_ics( $description ) . "\r\n";
        $ics .= "LOCATION:" . self::escape_ics( $location ) . "\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Escape string for ICS format.
     *
     * @param string $string String to escape.
     * @return string
     */
    private static function escape_ics( $string ) {
        $string = str_replace( array( '\\', ',', ';', "\n" ), array( '\\\\', '\\,', '\\;', '\\n' ), $string );
        return $string;
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

        // Check status
        if ( ! in_array( $booking['status'], array( 'pending', 'confirmed' ), true ) ) {
            return false;
        }

        // Check notice period
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

        // Check status
        if ( ! in_array( $booking['status'], array( 'pending', 'confirmed' ), true ) ) {
            return false;
        }

        // Check notice period
        $notice_hours = (int) get_option( 'sodek_gb_cancellation_notice', 24 );
        $booking_datetime = strtotime( $booking['booking_date'] . ' ' . $booking['start_time'] );

        return $booking_datetime > strtotime( "+{$notice_hours} hours" );
    }

    /**
     * Render not found page.
     */
    private static function render_not_found() {
        add_filter( 'pre_get_document_title', function() {
            return __( 'Booking Not Found', 'glowbook' );
        } );

        get_header();
        ?>
        <div class="sodek-gb-confirmation sodek-gb-not-found">
            <div class="sodek-gb-container">
                <div class="sodek-gb-message-box sodek-gb-error">
                    <h1><?php esc_html_e( 'Booking Not Found', 'glowbook' ); ?></h1>
                    <p><?php esc_html_e( 'We could not find a booking with this confirmation link. It may have expired or been cancelled.', 'glowbook' ); ?></p>
                    <a href="<?php echo esc_url( Sodek_GB_Standalone_Booking::get_booking_url() ); ?>" class="sodek-gb-btn sodek-gb-btn-primary">
                        <?php esc_html_e( 'Book a New Appointment', 'glowbook' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        get_footer();
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

        // Load header
        get_header();

        // Include template
        include $template_path;

        // Load footer
        get_footer();
    }
}
