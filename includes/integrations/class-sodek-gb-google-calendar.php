<?php
/**
 * Google Calendar Integration.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Google_Calendar class.
 *
 * Handles one-way sync of bookings to Google Calendar.
 */
class Sodek_GB_Google_Calendar {

    /**
     * Google OAuth2 endpoints.
     */
    const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const CALENDAR_API = 'https://www.googleapis.com/calendar/v3';

    /**
     * Initialize.
     */
    public static function init() {
        // Admin settings
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

        // Handle OAuth callback
        add_action( 'admin_init', array( __CLASS__, 'handle_oauth_callback' ) );

        // Sync hooks
        add_action( 'sodek_gb_booking_created', array( __CLASS__, 'sync_booking_to_calendar' ), 10, 2 );
        add_action( 'sodek_gb_booking_status_changed', array( __CLASS__, 'handle_status_change' ), 10, 3 );
        add_action( 'sodek_gb_booking_rescheduled', array( __CLASS__, 'handle_reschedule' ), 10, 5 );

        // AJAX handlers
        add_action( 'wp_ajax_sodek_gb_disconnect_google', array( __CLASS__, 'ajax_disconnect' ) );
    }

    /**
     * Register settings.
     */
    public static function register_settings() {
        register_setting( 'sodek_gb_settings', 'sodek_gb_google_client_id' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_google_client_secret' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_google_calendar_id' );
        register_setting( 'sodek_gb_settings', 'sodek_gb_google_sync_enabled' );
    }

    /**
     * Check if Google Calendar is configured.
     *
     * @return bool
     */
    public static function is_configured() {
        return ! empty( get_option( 'sodek_gb_google_client_id' ) )
            && ! empty( get_option( 'sodek_gb_google_client_secret' ) );
    }

    /**
     * Check if connected to Google.
     *
     * @return bool
     */
    public static function is_connected() {
        return ! empty( get_option( 'sodek_gb_google_access_token' ) );
    }

    /**
     * Check if sync is enabled.
     *
     * @return bool
     */
    public static function is_sync_enabled() {
        return self::is_connected() && get_option( 'sodek_gb_google_sync_enabled', false );
    }

    /**
     * Get OAuth redirect URI.
     *
     * @return string
     */
    public static function get_redirect_uri() {
        return admin_url( 'admin.php?page=sodek-gb-settings' );
    }

    /**
     * Get authorization URL.
     *
     * @return string
     */
    public static function get_auth_url() {
        $params = array(
            'client_id'     => get_option( 'sodek_gb_google_client_id' ),
            'redirect_uri'  => self::get_redirect_uri(),
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/calendar',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => wp_create_nonce( 'sodek_gb_google_auth' ),
        );

        return self::AUTH_URL . '?' . http_build_query( $params );
    }

    /**
     * Handle OAuth callback.
     */
    public static function handle_oauth_callback() {
        if ( ! isset( $_GET['page'] ) || 'sodek-gb-settings' !== $_GET['page'] ) {
            return;
        }

        // Handle authorization code
        if ( isset( $_GET['code'] ) && isset( $_GET['state'] ) ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['state'] ) ), 'sodek_gb_google_auth' ) ) {
                add_settings_error( 'sodek_gb_messages', 'google_auth_failed', __( 'Google authorization failed: Invalid state.', 'glowbook' ), 'error' );
                return;
            }

            $tokens = self::exchange_code_for_tokens( sanitize_text_field( $_GET['code'] ) );

            if ( is_wp_error( $tokens ) ) {
                add_settings_error( 'sodek_gb_messages', 'google_auth_failed', $tokens->get_error_message(), 'error' );
                return;
            }

            // Store tokens
            update_option( 'sodek_gb_google_access_token', $tokens['access_token'] );
            update_option( 'sodek_gb_google_refresh_token', $tokens['refresh_token'] ?? '' );
            update_option( 'sodek_gb_google_token_expires', time() + $tokens['expires_in'] );

            add_settings_error( 'sodek_gb_messages', 'google_connected', __( 'Successfully connected to Google Calendar!', 'glowbook' ), 'success' );

            // Redirect to remove code from URL
            wp_redirect( remove_query_arg( array( 'code', 'state', 'scope' ) ) );
            exit;
        }
    }

    /**
     * Exchange authorization code for tokens.
     *
     * @param string $code Authorization code.
     * @return array|WP_Error
     */
    private static function exchange_code_for_tokens( $code ) {
        $response = wp_remote_post( self::TOKEN_URL, array(
            'body' => array(
                'code'          => $code,
                'client_id'     => get_option( 'sodek_gb_google_client_id' ),
                'client_secret' => get_option( 'sodek_gb_google_client_secret' ),
                'redirect_uri'  => self::get_redirect_uri(),
                'grant_type'    => 'authorization_code',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'google_token_error', $body['error_description'] ?? $body['error'] );
        }

        return $body;
    }

    /**
     * Refresh access token.
     *
     * @return bool|WP_Error
     */
    private static function refresh_access_token() {
        $refresh_token = get_option( 'sodek_gb_google_refresh_token' );

        if ( empty( $refresh_token ) ) {
            return new WP_Error( 'no_refresh_token', __( 'No refresh token available.', 'glowbook' ) );
        }

        $response = wp_remote_post( self::TOKEN_URL, array(
            'body' => array(
                'refresh_token' => $refresh_token,
                'client_id'     => get_option( 'sodek_gb_google_client_id' ),
                'client_secret' => get_option( 'sodek_gb_google_client_secret' ),
                'grant_type'    => 'refresh_token',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            // Clear tokens if refresh fails
            delete_option( 'sodek_gb_google_access_token' );
            delete_option( 'sodek_gb_google_token_expires' );
            return new WP_Error( 'refresh_failed', $body['error_description'] ?? $body['error'] );
        }

        update_option( 'sodek_gb_google_access_token', $body['access_token'] );
        update_option( 'sodek_gb_google_token_expires', time() + $body['expires_in'] );

        return true;
    }

    /**
     * Get valid access token.
     *
     * @return string|WP_Error
     */
    private static function get_access_token() {
        $token = get_option( 'sodek_gb_google_access_token' );
        $expires = get_option( 'sodek_gb_google_token_expires' );

        if ( empty( $token ) ) {
            return new WP_Error( 'not_connected', __( 'Not connected to Google.', 'glowbook' ) );
        }

        // Refresh if expired or expiring soon
        if ( $expires && $expires < time() + 60 ) {
            $result = self::refresh_access_token();
            if ( is_wp_error( $result ) ) {
                return $result;
            }
            $token = get_option( 'sodek_gb_google_access_token' );
        }

        return $token;
    }

    /**
     * Make API request.
     *
     * @param string $endpoint API endpoint.
     * @param string $method   HTTP method.
     * @param array  $data     Request data.
     * @return array|WP_Error
     */
    private static function api_request( $endpoint, $method = 'GET', $data = array() ) {
        $token = self::get_access_token();

        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $args = array(
            'method'  => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ),
        );

        if ( ! empty( $data ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        $response = wp_remote_request( self::CALENDAR_API . $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code >= 400 ) {
            return new WP_Error(
                'api_error',
                $body['error']['message'] ?? __( 'API request failed.', 'glowbook' )
            );
        }

        return $body;
    }

    /**
     * Get user's calendars.
     *
     * @return array|WP_Error
     */
    public static function get_calendars() {
        $result = self::api_request( '/users/me/calendarList' );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $result['items'] ?? array();
    }

    /**
     * Sync booking to Google Calendar.
     *
     * @param int   $booking_id Booking ID.
     * @param array $booking    Booking data.
     */
    public static function sync_booking_to_calendar( $booking_id, $booking = null ) {
        if ( ! self::is_sync_enabled() ) {
            return;
        }

        if ( ! $booking ) {
            $booking = Sodek_GB_Booking::get_booking( $booking_id );
        }

        if ( ! $booking ) {
            return;
        }

        $calendar_id = get_option( 'sodek_gb_google_calendar_id', 'primary' );

        // Build event data
        $event = self::build_event_data( $booking );

        // Check if event already exists
        $existing_event_id = get_post_meta( $booking_id, '_sodek_gb_google_event_id', true );

        if ( $existing_event_id ) {
            // Update existing event
            $result = self::api_request(
                '/calendars/' . urlencode( $calendar_id ) . '/events/' . $existing_event_id,
                'PUT',
                $event
            );
        } else {
            // Create new event
            $result = self::api_request(
                '/calendars/' . urlencode( $calendar_id ) . '/events',
                'POST',
                $event
            );
        }

        if ( is_wp_error( $result ) ) {
            error_log( 'GlowBook Google Calendar sync error: ' . $result->get_error_message() );
            return;
        }

        // Store event ID
        if ( ! empty( $result['id'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_google_event_id', $result['id'] );
        }
    }

    /**
     * Build Google Calendar event data from booking.
     *
     * @param array $booking Booking data.
     * @return array
     */
    private static function build_event_data( $booking ) {
        $service_name = $booking['service']['title'] ?? __( 'Booking', 'glowbook' );
        $customer_name = $booking['customer_name'] ?? __( 'Customer', 'glowbook' );
        $customer_email = $booking['customer_email'] ?? '';
        $customer_phone = $booking['customer_phone'] ?? '';
        $notes = $booking['notes'] ?? '';

        // Build description
        $description = sprintf(
            "%s\n\n%s: %s\n%s: %s\n%s: %s",
            __( 'Booking Details', 'glowbook' ),
            __( 'Customer', 'glowbook' ),
            $customer_name,
            __( 'Email', 'glowbook' ),
            $customer_email,
            __( 'Phone', 'glowbook' ),
            $customer_phone
        );

        if ( $notes ) {
            $description .= "\n\n" . __( 'Notes', 'glowbook' ) . ":\n" . $notes;
        }

        $description .= "\n\n" . sprintf(
            __( 'View booking: %s', 'glowbook' ),
            admin_url( 'post.php?post=' . $booking['id'] . '&action=edit' )
        );

        // Get timezone
        $timezone = wp_timezone_string();

        return array(
            'summary'     => sprintf( '%s - %s', $service_name, $customer_name ),
            'description' => $description,
            'start'       => array(
                'dateTime' => $booking['date'] . 'T' . $booking['start_time'],
                'timeZone' => $timezone,
            ),
            'end'         => array(
                'dateTime' => $booking['date'] . 'T' . $booking['end_time'],
                'timeZone' => $timezone,
            ),
            'colorId'     => self::get_status_color( $booking['status'] ),
            'reminders'   => array(
                'useDefault' => false,
                'overrides'  => array(
                    array( 'method' => 'popup', 'minutes' => 60 ),
                    array( 'method' => 'popup', 'minutes' => 15 ),
                ),
            ),
        );
    }

    /**
     * Get Google Calendar color ID for status.
     *
     * @param string $status Booking status.
     * @return string
     */
    private static function get_status_color( $status ) {
        $colors = array(
            'pending'   => '5',  // Yellow
            'confirmed' => '10', // Green
            'completed' => '7',  // Cyan
            'cancelled' => '11', // Red
            'no-show'   => '8',  // Gray
        );

        return $colors[ $status ] ?? '1';
    }

    /**
     * Handle booking status change.
     *
     * @param int    $booking_id Booking ID.
     * @param string $new_status New status.
     * @param string $old_status Old status.
     */
    public static function handle_status_change( $booking_id, $new_status, $old_status = '' ) {
        if ( ! self::is_sync_enabled() ) {
            return;
        }

        $event_id = get_post_meta( $booking_id, '_sodek_gb_google_event_id', true );
        if ( ! $event_id ) {
            return;
        }

        $calendar_id = get_option( 'sodek_gb_google_calendar_id', 'primary' );

        if ( 'cancelled' === $new_status ) {
            // Delete event from calendar
            self::api_request(
                '/calendars/' . urlencode( $calendar_id ) . '/events/' . $event_id,
                'DELETE'
            );
            delete_post_meta( $booking_id, '_sodek_gb_google_event_id' );
        } else {
            // Update event with new status color
            self::sync_booking_to_calendar( $booking_id );
        }
    }

    /**
     * Handle booking reschedule.
     *
     * @param int    $booking_id Booking ID.
     * @param string $old_date   Old date.
     * @param string $old_time   Old time.
     * @param string $new_date   New date.
     * @param string $new_time   New time.
     */
    public static function handle_reschedule( $booking_id, $old_date, $old_time, $new_date, $new_time ) {
        self::sync_booking_to_calendar( $booking_id );
    }

    /**
     * AJAX: Disconnect from Google.
     */
    public static function ajax_disconnect() {
        check_ajax_referer( 'sodek_gb_google_disconnect', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'glowbook' ) ) );
        }

        delete_option( 'sodek_gb_google_access_token' );
        delete_option( 'sodek_gb_google_refresh_token' );
        delete_option( 'sodek_gb_google_token_expires' );

        wp_send_json_success( array( 'message' => __( 'Disconnected from Google Calendar.', 'glowbook' ) ) );
    }

    /**
     * Render settings section.
     */
    public static function render_settings() {
        $is_configured = self::is_configured();
        $is_connected = self::is_connected();
        ?>
        <h2 class="title"><?php esc_html_e( 'Google Calendar Integration', 'glowbook' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="sodek_gb_google_client_id"><?php esc_html_e( 'Client ID', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="text" id="sodek_gb_google_client_id" name="sodek_gb_google_client_id"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_google_client_id' ) ); ?>" class="regular-text">
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %s: Google Cloud Console URL */
                            esc_html__( 'Create credentials at %s', 'glowbook' ),
                            '<a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>'
                        );
                        ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_google_client_secret"><?php esc_html_e( 'Client Secret', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="password" id="sodek_gb_google_client_secret" name="sodek_gb_google_client_secret"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_google_client_secret' ) ); ?>" class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Authorized Redirect URI', 'glowbook' ); ?></th>
                <td>
                    <code><?php echo esc_html( self::get_redirect_uri() ); ?></code>
                    <p class="description"><?php esc_html_e( 'Add this URI to your Google Cloud Console OAuth settings.', 'glowbook' ); ?></p>
                </td>
            </tr>

            <?php if ( $is_configured ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Connection Status', 'glowbook' ); ?></th>
                    <td>
                        <?php if ( $is_connected ) : ?>
                            <span style="color: green;">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e( 'Connected to Google Calendar', 'glowbook' ); ?>
                            </span>
                            <br><br>
                            <button type="button" id="sodek-gb-disconnect-google" class="button">
                                <?php esc_html_e( 'Disconnect', 'glowbook' ); ?>
                            </button>
                        <?php else : ?>
                            <a href="<?php echo esc_url( self::get_auth_url() ); ?>" class="button button-primary">
                                <?php esc_html_e( 'Connect to Google Calendar', 'glowbook' ); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php if ( $is_connected ) : ?>
                    <tr>
                        <th scope="row">
                            <label for="sodek_gb_google_calendar_id"><?php esc_html_e( 'Calendar', 'glowbook' ); ?></label>
                        </th>
                        <td>
                            <?php
                            $calendars = self::get_calendars();
                            $selected = get_option( 'sodek_gb_google_calendar_id', 'primary' );
                            ?>
                            <?php if ( ! is_wp_error( $calendars ) ) : ?>
                                <select id="sodek_gb_google_calendar_id" name="sodek_gb_google_calendar_id">
                                    <?php foreach ( $calendars as $calendar ) : ?>
                                        <option value="<?php echo esc_attr( $calendar['id'] ); ?>" <?php selected( $selected, $calendar['id'] ); ?>>
                                            <?php echo esc_html( $calendar['summary'] ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else : ?>
                                <p class="description" style="color: red;">
                                    <?php esc_html_e( 'Unable to load calendars.', 'glowbook' ); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Enable Sync', 'glowbook' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="sodek_gb_google_sync_enabled" value="1"
                                    <?php checked( get_option( 'sodek_gb_google_sync_enabled' ), '1' ); ?>>
                                <?php esc_html_e( 'Automatically sync bookings to Google Calendar', 'glowbook' ); ?>
                            </label>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
        </table>

        <script>
        jQuery(function($) {
            $('#sodek-gb-disconnect-google').on('click', function() {
                if (!confirm('<?php echo esc_js( __( 'Are you sure you want to disconnect from Google Calendar?', 'glowbook' ) ); ?>')) {
                    return;
                }

                $.post(ajaxurl, {
                    action: 'sodek_gb_disconnect_google',
                    nonce: '<?php echo wp_create_nonce( 'sodek_gb_google_disconnect' ); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
