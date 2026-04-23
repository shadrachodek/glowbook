<?php
/**
 * WhatsApp Notifications.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_WhatsApp class.
 */
class Sodek_GB_WhatsApp {

    /**
     * Initialize.
     */
    public static function init() {
        // Hook into booking events
        add_action( 'sodek_gb_booking_created', array( __CLASS__, 'send_new_booking_notification' ), 10, 2 );
        add_action( 'sodek_gb_booking_confirmed', array( __CLASS__, 'send_new_booking_notification' ), 10, 2 );
        add_action( 'sodek_gb_booking_status_changed', array( __CLASS__, 'handle_status_change' ), 10, 3 );
        add_action( 'sodek_gb_booking_rescheduled', array( __CLASS__, 'send_rescheduled_notification' ), 10, 5 );

        // Admin AJAX for sending test messages
        add_action( 'wp_ajax_sodek_gb_send_whatsapp_test', array( __CLASS__, 'ajax_send_test_message' ) );
    }

    /**
     * Check if WhatsApp notifications are enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return (bool) get_option( 'sodek_gb_whatsapp_enabled', false );
    }

    /**
     * Get the WhatsApp number.
     *
     * @return string
     */
    public static function get_whatsapp_number() {
        return get_option( 'sodek_gb_whatsapp_number', '' );
    }

    /**
     * Format phone number for WhatsApp API.
     *
     * @param string $phone Phone number.
     * @return string
     */
    public static function format_phone_number( $phone ) {
        // Remove all non-numeric characters except +
        $phone = preg_replace( '/[^0-9+]/', '', $phone );

        // If starts with 0, assume Nigerian number and add country code
        if ( strpos( $phone, '0' ) === 0 ) {
            $phone = '+234' . substr( $phone, 1 );
        }

        // Ensure it starts with +
        if ( strpos( $phone, '+' ) !== 0 ) {
            // If it's 13 digits starting with 234, add +
            if ( strpos( $phone, '234' ) === 0 ) {
                $phone = '+' . $phone;
            }
        }

        // Remove + for API calls (most APIs want just digits)
        return ltrim( $phone, '+' );
    }

    /**
     * Send new booking notification.
     *
     * @param int   $booking_id Booking ID.
     * @param array $booking    Booking data.
     */
    public static function send_new_booking_notification( $booking_id, $booking = null ) {
        if ( ! self::is_enabled() || ! get_option( 'sodek_gb_whatsapp_notify_new', true ) ) {
            return;
        }

        if ( ! $booking ) {
            $booking = Sodek_GB_Booking::get_booking( $booking_id );
        }

        if ( ! $booking ) {
            return;
        }

        $message = self::get_new_booking_message( $booking );
        self::send_message( $message );
    }

    /**
     * Handle booking status change.
     *
     * @param int    $booking_id Booking ID.
     * @param string $new_status New status.
     * @param string $old_status Old status.
     */
    public static function handle_status_change( $booking_id, $new_status, $old_status = '' ) {
        if ( ! self::is_enabled() ) {
            return;
        }

        if ( 'cancelled' === $new_status && get_option( 'sodek_gb_whatsapp_notify_cancelled', true ) ) {
            $booking = Sodek_GB_Booking::get_booking( $booking_id );
            if ( $booking ) {
                $message = self::get_cancelled_booking_message( $booking );
                self::send_message( $message );
            }
        }
    }

    /**
     * Send rescheduled notification.
     *
     * @param int    $booking_id Booking ID.
     * @param string $old_date   Old date.
     * @param string $old_time   Old time.
     */
    public static function send_rescheduled_notification( $booking_id, $old_date = '', $old_time = '', $new_date = '', $new_time = '' ) {
        if ( ! self::is_enabled() || ! get_option( 'sodek_gb_whatsapp_notify_rescheduled', true ) ) {
            return;
        }

        $booking = Sodek_GB_Booking::get_booking( $booking_id );
        if ( ! $booking ) {
            return;
        }

        $message = self::get_rescheduled_booking_message( $booking, $old_date, $old_time );
        self::send_message( $message );
    }

    /**
     * Get placeholder replacements for a booking.
     *
     * @param array $booking Booking data.
     * @return array
     */
    private static function get_placeholders( $booking ) {
        $service_title = isset( $booking['service']['title'] ) ? $booking['service']['title'] : 'N/A';
        $service_price = isset( $booking['service']['price'] ) ? strip_tags( wc_price( $booking['service']['price'] ) ) : 'N/A';
        $deposit = isset( $booking['deposit_amount'] ) ? strip_tags( wc_price( $booking['deposit_amount'] ) ) : 'N/A';

        return array(
            '{customer_name}'  => $booking['customer_name'],
            '{customer_email}' => $booking['customer_email'],
            '{customer_phone}' => $booking['customer_phone'],
            '{service}'        => $service_title,
            '{date}'           => date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ),
            '{time}'           => date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ),
            '{deposit}'        => $deposit,
            '{total}'          => $service_price,
            '{notes}'          => ! empty( $booking['notes'] ) ? $booking['notes'] : '-',
            '{site_name}'      => get_bloginfo( 'name' ),
        );
    }

    /**
     * Get new booking message.
     *
     * @param array $booking Booking data.
     * @return string
     */
    public static function get_new_booking_message( $booking ) {
        $default = "🆕 *NEW BOOKING*\n\n📅 *Date:* {date}\n🕐 *Time:* {time}\n💇 *Service:* {service}\n👤 *Customer:* {customer_name}\n📧 *Email:* {customer_email}\n📱 *Phone:* {customer_phone}\n💰 *Deposit:* {deposit}\n📝 *Notes:* {notes}\n\n_{site_name}_";

        $template = get_option( 'sodek_gb_whatsapp_msg_new', $default );
        if ( empty( $template ) ) {
            $template = $default;
        }

        $placeholders = self::get_placeholders( $booking );
        return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
    }

    /**
     * Get cancelled booking message.
     *
     * @param array $booking Booking data.
     * @return string
     */
    public static function get_cancelled_booking_message( $booking ) {
        $default = "❌ *BOOKING CANCELLED*\n\n📅 *Date:* {date}\n🕐 *Time:* {time}\n💇 *Service:* {service}\n👤 *Customer:* {customer_name}\n📱 *Phone:* {customer_phone}\n\n_{site_name}_";

        $template = get_option( 'sodek_gb_whatsapp_msg_cancelled', $default );
        if ( empty( $template ) ) {
            $template = $default;
        }

        $placeholders = self::get_placeholders( $booking );

        // Add cancellation reason if available
        $reason = get_post_meta( $booking['id'], '_sodek_gb_cancellation_reason', true );
        $placeholders['{reason}'] = $reason ? $reason : '-';

        return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
    }

    /**
     * Get rescheduled booking message.
     *
     * @param array  $booking  Booking data.
     * @param string $old_date Old date.
     * @param string $old_time Old time.
     * @return string
     */
    public static function get_rescheduled_booking_message( $booking, $old_date = '', $old_time = '' ) {
        $default = "🔄 *BOOKING RESCHEDULED*\n\n💇 *Service:* {service}\n👤 *Customer:* {customer_name}\n📱 *Phone:* {customer_phone}\n\n📅 *New Date:* {date}\n🕐 *New Time:* {time}\n\n_{site_name}_";

        $template = get_option( 'sodek_gb_whatsapp_msg_rescheduled', $default );
        if ( empty( $template ) ) {
            $template = $default;
        }

        $placeholders = self::get_placeholders( $booking );

        // Add old date/time placeholders
        if ( $old_date && $old_time ) {
            $placeholders['{old_date}'] = date_i18n( get_option( 'date_format' ), strtotime( $old_date ) );
            $placeholders['{old_time}'] = date_i18n( get_option( 'time_format' ), strtotime( $old_time ) );
        } else {
            $placeholders['{old_date}'] = '-';
            $placeholders['{old_time}'] = '-';
        }

        return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
    }

    /**
     * Send WhatsApp message.
     *
     * @param string $message Message to send.
     * @param string $phone   Phone number (optional, defaults to settings).
     * @return bool|WP_Error
     */
    public static function send_message( $message, $phone = '' ) {
        if ( empty( $phone ) ) {
            $phone = self::get_whatsapp_number();
        }

        if ( empty( $phone ) ) {
            return new WP_Error( 'no_phone', __( 'No WhatsApp number configured.', 'glowbook' ) );
        }

        $phone = self::format_phone_number( $phone );

        // Log the message attempt
        self::log_message( $phone, $message );

        // Check if CallMeBot API key is configured
        $callmebot_key = get_option( 'sodek_gb_whatsapp_callmebot_key', '' );

        if ( ! empty( $callmebot_key ) ) {
            return self::send_via_callmebot( $phone, $message );
        }

        // Fallback: Generate wa.me link (for manual sending)
        return self::generate_whatsapp_link( $phone, $message );
    }

    /**
     * Send via CallMeBot (free service).
     *
     * @param string $phone   Phone number.
     * @param string $message Message.
     * @return bool|WP_Error
     */
    private static function send_via_callmebot( $phone, $message ) {
        $api_key = get_option( 'sodek_gb_whatsapp_callmebot_key', '' );

        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', __( 'CallMeBot API key not configured.', 'glowbook' ) );
        }

        $url = add_query_arg(
            array(
                'phone'  => $phone,
                'text'   => urlencode( $message ),
                'apikey' => $api_key,
            ),
            'https://api.callmebot.com/whatsapp.php'
        );

        $response = wp_remote_get( $url, array(
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code === 200 && ( strpos( $body, 'Message queued' ) !== false || strpos( $body, 'Message Sent' ) !== false ) ) {
            return true;
        }

        return new WP_Error( 'send_failed', $body );
    }

    /**
     * Send via UltraMsg.
     *
     * @param string $phone   Phone number.
     * @param string $message Message.
     * @return bool|WP_Error
     */
    private static function send_via_ultramsg( $phone, $message ) {
        $instance_id = get_option( 'sodek_gb_whatsapp_ultramsg_instance', '' );
        $token = get_option( 'sodek_gb_whatsapp_ultramsg_token', '' );

        if ( empty( $instance_id ) || empty( $token ) ) {
            return new WP_Error( 'missing_credentials', __( 'UltraMsg credentials not configured.', 'glowbook' ) );
        }

        $url = "https://api.ultramsg.com/{$instance_id}/messages/chat";

        $response = wp_remote_post( $url, array(
            'timeout' => 30,
            'body'    => array(
                'token' => $token,
                'to'    => $phone,
                'body'  => $message,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['sent'] ) && $body['sent'] === 'true' ) {
            return true;
        }

        return new WP_Error( 'send_failed', isset( $body['message'] ) ? $body['message'] : __( 'Unknown error', 'glowbook' ) );
    }

    /**
     * Send via WhatsApp Cloud API (Meta).
     *
     * @param string $phone   Phone number.
     * @param string $message Message.
     * @return bool|WP_Error
     */
    private static function send_via_whatsapp_cloud_api( $phone, $message ) {
        $phone_number_id = get_option( 'sodek_gb_whatsapp_phone_number_id', '' );
        $access_token = get_option( 'sodek_gb_whatsapp_access_token', '' );

        if ( empty( $phone_number_id ) || empty( $access_token ) ) {
            return new WP_Error( 'missing_credentials', __( 'WhatsApp Cloud API credentials not configured.', 'glowbook' ) );
        }

        $url = "https://graph.facebook.com/v17.0/{$phone_number_id}/messages";

        $response = wp_remote_post( $url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( array(
                'messaging_product' => 'whatsapp',
                'to'                => $phone,
                'type'              => 'text',
                'text'              => array(
                    'body' => $message,
                ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['messages'][0]['id'] ) ) {
            return true;
        }

        $error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'Unknown error', 'glowbook' );
        return new WP_Error( 'send_failed', $error_message );
    }

    /**
     * Generate WhatsApp link (fallback).
     *
     * @param string $phone   Phone number.
     * @param string $message Message.
     * @return string
     */
    public static function generate_whatsapp_link( $phone, $message ) {
        return 'https://wa.me/' . $phone . '?text=' . urlencode( $message );
    }

    /**
     * Log message.
     *
     * @param string $phone   Phone number.
     * @param string $message Message.
     */
    private static function log_message( $phone, $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf(
                '[GlowBook WhatsApp] Sending to %s: %s',
                $phone,
                substr( $message, 0, 100 ) . '...'
            ) );
        }
    }

    /**
     * AJAX handler for sending test messages.
     */
    public static function ajax_send_test_message() {
        check_ajax_referer( 'sodek_gb_whatsapp_test', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'glowbook' ) ) );
        }

        $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'new';
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : self::get_whatsapp_number();

        if ( empty( $phone ) ) {
            wp_send_json_error( array( 'message' => __( 'No phone number provided.', 'glowbook' ) ) );
        }

        // Create sample booking data
        $sample_booking = array(
            'id'             => 12345,
            'booking_date'   => gmdate( 'Y-m-d', strtotime( '+2 days' ) ),
            'start_time'     => '10:00:00',
            'end_time'       => '12:00:00',
            'customer_name'  => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '+2348012345678',
            'status'         => 'confirmed',
            'deposit_amount' => 5000,
            'notes'          => 'Medium length box braids, prefer brown color',
            'service'        => array(
                'id'    => 1,
                'title' => 'Box Braids - Medium',
                'price' => 25000,
            ),
        );

        switch ( $type ) {
            case 'new':
                $message = self::get_new_booking_message( $sample_booking );
                break;

            case 'cancelled':
                $message = self::get_cancelled_booking_message( $sample_booking );
                break;

            case 'rescheduled':
                $old_date = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
                $old_time = '14:00:00';
                $message = self::get_rescheduled_booking_message( $sample_booking, $old_date, $old_time );
                break;

            default:
                wp_send_json_error( array( 'message' => __( 'Invalid notification type.', 'glowbook' ) ) );
        }

        $result = self::send_message( $message, $phone );

        if ( is_wp_error( $result ) ) {
            // If sending failed, return the wa.me link as fallback
            $link = self::generate_whatsapp_link( self::format_phone_number( $phone ), $message );
            wp_send_json_success( array(
                'message'  => __( 'Click the link to send via WhatsApp:', 'glowbook' ),
                'link'     => $link,
                'fallback' => true,
                'preview'  => $message,
            ) );
        }

        wp_send_json_success( array(
            'message' => __( 'Test message sent successfully!', 'glowbook' ),
            'preview' => $message,
        ) );
    }

    /**
     * Get sample messages for all types.
     *
     * @return array
     */
    public static function get_sample_messages() {
        $sample_booking = array(
            'id'             => 12345,
            'booking_date'   => gmdate( 'Y-m-d', strtotime( '+2 days' ) ),
            'start_time'     => '10:00:00',
            'end_time'       => '12:00:00',
            'customer_name'  => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '+2348012345678',
            'status'         => 'confirmed',
            'deposit_amount' => 5000,
            'notes'          => 'Medium length box braids, prefer brown color',
            'service'        => array(
                'id'    => 1,
                'title' => 'Box Braids - Medium',
                'price' => 25000,
            ),
        );

        return array(
            'new'         => self::get_new_booking_message( $sample_booking ),
            'cancelled'   => self::get_cancelled_booking_message( $sample_booking ),
            'rescheduled' => self::get_rescheduled_booking_message(
                $sample_booking,
                gmdate( 'Y-m-d', strtotime( '+1 day' ) ),
                '14:00:00'
            ),
        );
    }
}
