<?php
/**
 * Twilio SMS Gateway.
 *
 * Handles SMS sending via Twilio API.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Twilio class.
 */
class Sodek_GB_Twilio {

    /**
     * Twilio API base URL.
     *
     * @var string
     */
    const API_URL = 'https://api.twilio.com/2010-04-01';

    /**
     * Account SID.
     *
     * @var string
     */
    private $account_sid;

    /**
     * Auth Token.
     *
     * @var string
     */
    private $auth_token;

    /**
     * From phone number.
     *
     * @var string
     */
    private $from_number;

    /**
     * Whether SMS is enabled.
     *
     * @var bool
     */
    private $enabled;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->enabled     = (bool) get_option( 'sodek_gb_sms_enabled', false );
        $this->account_sid = get_option( 'sodek_gb_twilio_account_sid', '' );
        $this->auth_token  = get_option( 'sodek_gb_twilio_auth_token', '' );
        $this->from_number = get_option( 'sodek_gb_twilio_from_number', '' );
    }

    /**
     * Check if Twilio is configured and enabled.
     *
     * @return bool
     */
    public function is_configured() {
        return $this->enabled
            && ! empty( $this->account_sid )
            && ! empty( $this->auth_token )
            && ! empty( $this->from_number );
    }

    /**
     * Send an SMS message.
     *
     * @param string $to      Recipient phone number (E.164 format).
     * @param string $message Message content.
     * @param array  $args    Optional. Additional arguments.
     * @return array|WP_Error Response data or error.
     */
    public function send( $to, $message, $args = array() ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error(
                'twilio_not_configured',
                __( 'Twilio is not configured.', 'glowbook' )
            );
        }

        // Normalize phone number
        $to = $this->normalize_phone( $to );

        if ( ! $to ) {
            return new WP_Error(
                'invalid_phone',
                __( 'Invalid phone number.', 'glowbook' )
            );
        }

        // Check rate limiting
        $rate_limited = $this->check_rate_limit( $to );
        if ( is_wp_error( $rate_limited ) ) {
            return $rate_limited;
        }

        // Build request
        $endpoint = sprintf(
            '%s/Accounts/%s/Messages.json',
            self::API_URL,
            $this->account_sid
        );

        $body = array(
            'To'   => $to,
            'From' => $this->from_number,
            'Body' => $message,
        );

        // Add optional parameters
        if ( ! empty( $args['status_callback'] ) ) {
            $body['StatusCallback'] = $args['status_callback'];
        }

        // Make request
        $response = wp_remote_post(
            $endpoint,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $this->account_sid . ':' . $this->auth_token ),
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ),
                'body'    => $body,
            )
        );

        if ( is_wp_error( $response ) ) {
            $this->log_message( $to, $message, 'failed', $response->get_error_message() );
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code >= 400 ) {
            $error_message = $body['message'] ?? __( 'Unknown error', 'glowbook' );
            $this->log_message( $to, $message, 'failed', $error_message );

            return new WP_Error(
                'twilio_error',
                $error_message,
                array(
                    'status_code' => $status_code,
                    'response'    => $body,
                )
            );
        }

        // Log successful message
        $this->log_message( $to, $message, 'sent', null, $body['sid'] ?? null );

        return array(
            'success' => true,
            'sid'     => $body['sid'] ?? null,
            'status'  => $body['status'] ?? 'queued',
        );
    }

    /**
     * Send a verification code.
     *
     * @param string $to   Recipient phone number.
     * @param string $code Verification code.
     * @return array|WP_Error
     */
    public function send_verification( $to, $code ) {
        $business_name = get_bloginfo( 'name' );

        $message = sprintf(
            /* translators: 1: verification code, 2: business name */
            __( 'Your verification code for %2$s is: %1$s. This code expires in 10 minutes.', 'glowbook' ),
            $code,
            $business_name
        );

        return $this->send( $to, $message, array( 'type' => 'verification' ) );
    }

    /**
     * Send a booking confirmation.
     *
     * @param string $to      Recipient phone number.
     * @param array  $booking Booking data.
     * @return array|WP_Error
     */
    public function send_booking_confirmation( $to, $booking ) {
        $business_name = get_bloginfo( 'name' );
        $service_name = $booking['service']['title'] ?? __( 'Appointment', 'glowbook' );
        $date = date_i18n( 'M j', strtotime( $booking['booking_date'] ) );
        $time = date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) );

        $message = sprintf(
            /* translators: 1: business name, 2: service name, 3: date, 4: time, 5: booking ID */
            __( '%1$s: Your %2$s appointment is confirmed for %3$s at %4$s. Confirmation #%5$d', 'glowbook' ),
            $business_name,
            $service_name,
            $date,
            $time,
            $booking['id']
        );

        return $this->send( $to, $message, array(
            'type'       => 'confirmation',
            'booking_id' => $booking['id'],
        ) );
    }

    /**
     * Send a booking reminder.
     *
     * @param string $to      Recipient phone number.
     * @param array  $booking Booking data.
     * @param int    $hours   Hours until appointment.
     * @return array|WP_Error
     */
    public function send_booking_reminder( $to, $booking, $hours ) {
        $business_name = get_bloginfo( 'name' );
        $service_name = $booking['service']['title'] ?? __( 'Appointment', 'glowbook' );
        $time = date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) );

        if ( $hours >= 24 ) {
            $time_text = sprintf(
                /* translators: %s: time */
                __( 'tomorrow at %s', 'glowbook' ),
                $time
            );
        } else {
            $time_text = sprintf(
                /* translators: 1: hours, 2: time */
                __( 'in %1$d hours at %2$s', 'glowbook' ),
                $hours,
                $time
            );
        }

        $message = sprintf(
            /* translators: 1: business name, 2: service name, 3: time description */
            __( '%1$s Reminder: Your %2$s appointment is %3$s. Reply STOP to unsubscribe.', 'glowbook' ),
            $business_name,
            $service_name,
            $time_text
        );

        return $this->send( $to, $message, array(
            'type'       => 'reminder',
            'booking_id' => $booking['id'],
        ) );
    }

    /**
     * Send a cancellation notification.
     *
     * @param string $to      Recipient phone number.
     * @param array  $booking Booking data.
     * @return array|WP_Error
     */
    public function send_cancellation( $to, $booking ) {
        $business_name = get_bloginfo( 'name' );
        $service_name = $booking['service']['title'] ?? __( 'Appointment', 'glowbook' );
        $date = date_i18n( 'M j', strtotime( $booking['booking_date'] ) );

        $message = sprintf(
            /* translators: 1: business name, 2: service name, 3: date */
            __( '%1$s: Your %2$s appointment on %3$s has been cancelled. Questions? Reply to this message.', 'glowbook' ),
            $business_name,
            $service_name,
            $date
        );

        return $this->send( $to, $message, array(
            'type'       => 'cancellation',
            'booking_id' => $booking['id'],
        ) );
    }

    /**
     * Send a reschedule notification.
     *
     * @param string $to       Recipient phone number.
     * @param array  $booking  Booking data.
     * @param string $new_date New date.
     * @param string $new_time New time.
     * @return array|WP_Error
     */
    public function send_reschedule( $to, $booking, $new_date, $new_time ) {
        $business_name = get_bloginfo( 'name' );
        $service_name = $booking['service']['title'] ?? __( 'Appointment', 'glowbook' );
        $date = date_i18n( 'M j', strtotime( $new_date ) );
        $time = date_i18n( get_option( 'time_format' ), strtotime( $new_time ) );

        $message = sprintf(
            /* translators: 1: business name, 2: service name, 3: new date, 4: new time */
            __( '%1$s: Your %2$s appointment has been rescheduled to %3$s at %4$s.', 'glowbook' ),
            $business_name,
            $service_name,
            $date,
            $time
        );

        return $this->send( $to, $message, array(
            'type'       => 'reschedule',
            'booking_id' => $booking['id'],
        ) );
    }

    /**
     * Normalize phone number to E.164 format.
     *
     * @param string $phone Phone number.
     * @return string|false
     */
    private function normalize_phone( $phone ) {
        // Remove all non-digit characters except leading +
        $phone = preg_replace( '/[^\d+]/', '', $phone );

        // If no country code, assume US (+1)
        if ( ! preg_match( '/^\+/', $phone ) ) {
            // Remove leading 1 if present
            $phone = preg_replace( '/^1/', '', $phone );

            // Check valid length for US number
            if ( strlen( $phone ) === 10 ) {
                $phone = '+1' . $phone;
            } else {
                return false;
            }
        }

        // Validate E.164 format (up to 15 digits)
        if ( preg_match( '/^\+[1-9]\d{1,14}$/', $phone ) ) {
            return $phone;
        }

        return false;
    }

    /**
     * Check rate limiting for phone number.
     *
     * @param string $phone Phone number.
     * @return true|WP_Error
     */
    private function check_rate_limit( $phone ) {
        $transient_key = 'sodek_gb_sms_rate_' . md5( $phone );
        $count = get_transient( $transient_key );

        $max_per_hour = (int) get_option( 'sodek_gb_sms_rate_limit', 3 );

        if ( $count !== false && $count >= $max_per_hour ) {
            return new WP_Error(
                'rate_limited',
                __( 'Too many messages sent. Please try again later.', 'glowbook' )
            );
        }

        // Increment counter
        if ( $count === false ) {
            set_transient( $transient_key, 1, HOUR_IN_SECONDS );
        } else {
            set_transient( $transient_key, $count + 1, HOUR_IN_SECONDS );
        }

        return true;
    }

    /**
     * Log SMS message.
     *
     * @param string      $phone   Phone number.
     * @param string      $message Message content.
     * @param string      $status  Status (sent, failed, pending).
     * @param string|null $error   Error message if failed.
     * @param string|null $sid     Twilio SID.
     * @param int|null    $booking_id Associated booking ID.
     */
    private function log_message( $phone, $message, $status, $error = null, $sid = null, $booking_id = null ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_sms_log';

        $wpdb->insert(
            $table,
            array(
                'phone'           => $phone,
                'message_type'    => 'general',
                'message_content' => $message,
                'twilio_sid'      => $sid,
                'status'          => $status,
                'error_message'   => $error,
                'booking_id'      => $booking_id,
                'created_at'      => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
        );
    }

    /**
     * Test Twilio connection.
     *
     * @return array|WP_Error
     */
    public function test_connection() {
        if ( empty( $this->account_sid ) || empty( $this->auth_token ) ) {
            return new WP_Error(
                'missing_credentials',
                __( 'Twilio credentials are not configured.', 'glowbook' )
            );
        }

        $endpoint = sprintf(
            '%s/Accounts/%s.json',
            self::API_URL,
            $this->account_sid
        );

        $response = wp_remote_get(
            $endpoint,
            array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $this->account_sid . ':' . $this->auth_token ),
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        if ( $status_code === 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            return array(
                'success'      => true,
                'account_name' => $body['friendly_name'] ?? '',
                'status'       => $body['status'] ?? 'active',
            );
        }

        return new WP_Error(
            'connection_failed',
            __( 'Failed to connect to Twilio. Please check your credentials.', 'glowbook' )
        );
    }

    /**
     * Handle incoming webhook from Twilio.
     *
     * @param array $data Webhook data.
     * @return array
     */
    public function handle_webhook( $data ) {
        $message_sid = $data['MessageSid'] ?? '';
        $status = $data['MessageStatus'] ?? '';
        $from = $data['From'] ?? '';
        $body = $data['Body'] ?? '';

        // Check for opt-out keywords
        $opt_out_keywords = array( 'stop', 'unsubscribe', 'cancel', 'quit' );
        if ( in_array( strtolower( trim( $body ) ), $opt_out_keywords, true ) ) {
            $this->handle_opt_out( $from );
        }

        // Update message status in log
        if ( $message_sid && $status ) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'sodek_gb_sms_log',
                array( 'status' => $status ),
                array( 'twilio_sid' => $message_sid ),
                array( '%s' ),
                array( '%s' )
            );
        }

        return array( 'success' => true );
    }

    /**
     * Handle opt-out request.
     *
     * @param string $phone Phone number.
     */
    private function handle_opt_out( $phone ) {
        global $wpdb;

        // Find customer by phone
        $customer_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}sodek_gb_customers WHERE phone = %s",
                $this->normalize_phone( $phone )
            )
        );

        if ( $customer_id ) {
            $wpdb->update(
                $wpdb->prefix . 'sodek_gb_customers',
                array( 'sms_opt_in' => 0 ),
                array( 'id' => $customer_id ),
                array( '%d' ),
                array( '%d' )
            );

            do_action( 'sodek_gb_customer_sms_opt_out', $customer_id, $phone );
        }
    }
}
