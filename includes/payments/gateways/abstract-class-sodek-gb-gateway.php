<?php
/**
 * Abstract Payment Gateway.
 *
 * Base class for payment gateways with common functionality.
 *
 * @package GlowBook
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Gateway_Abstract class.
 */
abstract class Sodek_GB_Gateway_Abstract implements Sodek_GB_Payment_Gateway_Interface {

    /**
     * Gateway ID.
     *
     * @var string
     */
    protected $id = '';

    /**
     * Gateway title.
     *
     * @var string
     */
    protected $title = '';

    /**
     * Whether the gateway is enabled.
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Current environment (sandbox or production).
     *
     * @var string
     */
    protected $environment = 'sandbox';

    /**
     * Encryption cipher method.
     */
    const CIPHER_METHOD = 'aes-256-cbc';

    /**
     * Get the gateway ID.
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Get the gateway title.
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * Get the current environment.
     *
     * @return string
     */
    public function get_environment(): string {
        return $this->environment;
    }

    /**
     * Check if the gateway is available.
     *
     * @return bool
     */
    public function is_available(): bool {
        return $this->enabled && $this->is_configured();
    }

    /**
     * Encrypt a token using WordPress salts.
     *
     * @param string $token Token to encrypt.
     * @return string Encrypted token (base64 encoded).
     */
    public static function encrypt_token( string $token ): string {
        if ( empty( $token ) ) {
            return '';
        }

        $key = self::get_encryption_key();
        $iv  = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::CIPHER_METHOD ) );

        $encrypted = openssl_encrypt( $token, self::CIPHER_METHOD, $key, 0, $iv );

        if ( false === $encrypted ) {
            return '';
        }

        // Combine IV and encrypted data, then base64 encode
        return base64_encode( $iv . $encrypted );
    }

    /**
     * Decrypt a token.
     *
     * @param string $encrypted_token Encrypted token (base64 encoded).
     * @return string Decrypted token.
     */
    public static function decrypt_token( string $encrypted_token ): string {
        if ( empty( $encrypted_token ) ) {
            return '';
        }

        $key  = self::get_encryption_key();
        $data = base64_decode( $encrypted_token );

        if ( false === $data ) {
            return '';
        }

        $iv_length = openssl_cipher_iv_length( self::CIPHER_METHOD );
        $iv        = substr( $data, 0, $iv_length );
        $encrypted = substr( $data, $iv_length );

        $decrypted = openssl_decrypt( $encrypted, self::CIPHER_METHOD, $key, 0, $iv );

        return false === $decrypted ? '' : $decrypted;
    }

    /**
     * Get the encryption key from WordPress salts.
     *
     * @return string
     */
    protected static function get_encryption_key(): string {
        $key = '';

        if ( defined( 'AUTH_KEY' ) ) {
            $key .= AUTH_KEY;
        }
        if ( defined( 'SECURE_AUTH_KEY' ) ) {
            $key .= SECURE_AUTH_KEY;
        }

        // Use SHA-256 to get a consistent key length
        return hash( 'sha256', $key, true );
    }

    /**
     * Generate an idempotency key.
     *
     * @param string $prefix Optional prefix.
     * @return string
     */
    protected function generate_idempotency_key( string $prefix = '' ): string {
        return $prefix . wp_generate_uuid4();
    }

    /**
     * Log a message.
     *
     * @param string $message Message to log.
     * @param string $level   Log level (debug, info, warning, error).
     * @return void
     */
    protected function log( string $message, string $level = 'info' ): void {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return;
        }

        $log_message = sprintf(
            '[GlowBook %s] [%s] %s',
            strtoupper( $this->id ),
            strtoupper( $level ),
            $message
        );

        if ( function_exists( 'wc_get_logger' ) ) {
            $logger  = wc_get_logger();
            $context = array( 'source' => 'glowbook-' . $this->id );

            switch ( $level ) {
                case 'debug':
                    $logger->debug( $message, $context );
                    break;
                case 'warning':
                    $logger->warning( $message, $context );
                    break;
                case 'error':
                    $logger->error( $message, $context );
                    break;
                default:
                    $logger->info( $message, $context );
            }
        } else {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( $log_message );
        }
    }

    /**
     * Make an HTTP request.
     *
     * @param string $url     Request URL.
     * @param array  $args    Request arguments.
     * @param string $method  HTTP method (GET, POST, PUT, DELETE).
     * @return array|WP_Error Response or error.
     */
    protected function make_request( string $url, array $args = array(), string $method = 'POST' ) {
        $defaults = array(
            'method'  => $method,
            'timeout' => 45,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        );

        $args = wp_parse_args( $args, $defaults );

        if ( 'POST' === $method || 'PUT' === $method ) {
            return wp_remote_post( $url, $args );
        }

        return wp_remote_get( $url, $args );
    }

    /**
     * Format an error response.
     *
     * @param string $code    Error code.
     * @param string $message Error message.
     * @return array
     */
    protected function error_response( string $code, string $message ): array {
        return array(
            'success' => false,
            'error'   => array(
                'code'    => $code,
                'message' => $message,
            ),
        );
    }

    /**
     * Format a success response.
     *
     * @param array $data Response data.
     * @return array
     */
    protected function success_response( array $data ): array {
        return array(
            'success' => true,
            'data'    => $data,
        );
    }

    /**
     * Validate an amount.
     *
     * @param float $amount Amount to validate.
     * @return bool
     */
    protected function validate_amount( float $amount ): bool {
        return $amount > 0;
    }

    /**
     * Sanitize amount for API (convert to cents for most gateways).
     *
     * @param float $amount Amount in dollars.
     * @return int Amount in cents.
     */
    protected function amount_to_cents( float $amount ): int {
        return absint( round( $amount * 100 ) );
    }

    /**
     * Convert cents to dollars.
     *
     * @param int $cents Amount in cents.
     * @return float Amount in dollars.
     */
    protected function cents_to_amount( int $cents ): float {
        return floatval( $cents / 100 );
    }
}
