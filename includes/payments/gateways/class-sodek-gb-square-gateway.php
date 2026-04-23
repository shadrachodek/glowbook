<?php
/**
 * Square Payment Gateway.
 *
 * Handles Square payment processing using the Square Payments API.
 * Supports both standalone credentials and WooCommerce Square integration.
 *
 * @package GlowBook
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Square_Gateway class.
 *
 * When WooCommerce Square is available, this gateway uses the WC Square Gateway API
 * directly for payment processing, leveraging automatic token refresh and proper
 * SDK handling. Falls back to raw HTTP requests when using manual credentials.
 */
class Sodek_GB_Square_Gateway extends Sodek_GB_Gateway_Abstract {

    /**
     * Square API version.
     */
    const API_VERSION = '2024-01-18';

    /**
     * Sandbox API base URL.
     */
    const SANDBOX_API_URL = 'https://connect.squareupsandbox.com/v2';

    /**
     * Production API base URL.
     */
    const PRODUCTION_API_URL = 'https://connect.squareup.com/v2';

    /**
     * Sandbox Web Payments SDK URL.
     */
    const SANDBOX_SDK_URL = 'https://sandbox.web.squarecdn.com/v1/square.js';

    /**
     * Production Web Payments SDK URL.
     */
    const PRODUCTION_SDK_URL = 'https://web.squarecdn.com/v1/square.js';

    /**
     * WooCommerce Square production application ID.
     */
    const WC_SQUARE_APP_ID = 'sq0idp-wGVapF8sNt9PLrdj5znuKA';

    /**
     * Application ID.
     *
     * @var string
     */
    private $application_id = '';

    /**
     * Access token.
     *
     * @var string
     */
    private $access_token = '';

    /**
     * Location ID.
     *
     * @var string
     */
    private $location_id = '';

    /**
     * Whether using WooCommerce Square credentials.
     *
     * @var bool
     */
    private $using_wc_square = false;

    /**
     * WooCommerce Square Gateway API instance.
     *
     * @var \WooCommerce\Square\Gateway\API|null
     */
    private $wc_square_api = null;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id          = 'square';
        $this->title       = __( 'Square', 'glowbook' );
        $this->environment = get_option( 'sodek_gb_square_environment', 'sandbox' );

        $this->load_credentials();

        // Auto-enable if using WooCommerce Square credentials
        if ( $this->using_wc_square ) {
            $this->enabled = true;
        } else {
            $this->enabled = (bool) get_option( 'sodek_gb_square_enabled', false );
        }

        add_filter( 'sodek_gb_process_card_nonce', array( $this, 'process_card_nonce' ), 10, 3 );
        add_filter( 'sodek_gb_delete_saved_card', array( $this, 'delete_saved_card' ), 10, 3 );
    }

    /**
     * Load credentials based on environment.
     * Prioritizes WooCommerce Square credentials if available and configured.
     *
     * @return void
     */
    private function load_credentials(): void {
        $credential_source = get_option( 'sodek_gb_square_credential_source', 'auto' );

        // Try WooCommerce Square first if set to auto or woocommerce
        if ( in_array( $credential_source, array( 'auto', 'woocommerce' ), true ) && $this->load_wc_square_credentials() ) {
            $this->using_wc_square = true;
            return;
        }

        // Fall back to manual credentials
        $this->load_manual_credentials();
    }

    /**
     * Load credentials from WooCommerce Square plugin.
     *
     * @return bool True if credentials were loaded successfully.
     */
    private function load_wc_square_credentials(): bool {
        // First, try to use the WooCommerce Square API if available
        if ( function_exists( 'wc_square' ) ) {
            try {
                $settings = wc_square()->get_settings_handler();

                if ( $settings && $settings->is_connected() ) {
                    $access_token = $settings->get_access_token();
                    $location_id = $settings->get_location_id();

                    $this->log( 'WC Square: connected=' . ( $settings->is_connected() ? 'yes' : 'no' ) .
                        ', has_token=' . ( ! empty( $access_token ) ? 'yes' : 'no' ) .
                        ', location_id=' . ( ! empty( $location_id ) ? substr( $location_id, 0, 8 ) . '...' : 'empty' ), 'debug' );

                    if ( ! empty( $access_token ) && ! empty( $location_id ) ) {
                        $is_sandbox = $settings->is_sandbox();
                        $this->environment = $is_sandbox ? 'sandbox' : 'production';

                        if ( $is_sandbox ) {
                            // For sandbox, use the sandbox application ID from settings
                            $this->application_id = $settings->get_option( 'sandbox_application_id' );
                            $this->log( 'WC Square: Sandbox mode, app_id=' . ( ! empty( $this->application_id ) ? substr( $this->application_id, 0, 12 ) . '...' : 'NOT SET' ), 'debug' );
                        } else {
                            // For production, use the WC Square app ID
                            $this->application_id = self::WC_SQUARE_APP_ID;
                            $this->log( 'WC Square: Production mode, using WC_SQUARE_APP_ID', 'debug' );
                        }

                        if ( ! empty( $this->application_id ) ) {
                            $this->access_token = $access_token;
                            $this->location_id  = $location_id;
                            return true;
                        } else {
                            $this->log( 'WC Square: Application ID is empty for ' . $this->environment . ' mode', 'error' );
                        }
                    }
                }
            } catch ( Exception $e ) {
                $this->log( 'WC Square: Exception - ' . $e->getMessage(), 'error' );
                // Fall through to direct option access
            }
        }

        // Fallback: Try to access WooCommerce Square options directly
        $wc_square_settings = get_option( 'wc_square_settings', array() );
        $access_tokens = get_option( 'wc_square_access_tokens', array() );

        if ( empty( $wc_square_settings ) || empty( $access_tokens ) ) {
            return false;
        }

        // Determine environment
        $is_sandbox = isset( $wc_square_settings['enable_sandbox'] ) && 'yes' === $wc_square_settings['enable_sandbox'];
        $env_key = $is_sandbox ? 'sandbox' : 'production';
        $this->environment = $env_key;

        // Get access token
        $encrypted_token = isset( $access_tokens[ $env_key ] ) ? $access_tokens[ $env_key ] : '';
        if ( empty( $encrypted_token ) ) {
            return false;
        }

        // Decrypt the token if WooCommerce Square encryption is available
        $access_token = $encrypted_token;
        if ( class_exists( 'WooCommerce\Square\Utilities\Encryption_Utility' ) ) {
            try {
                $encryption = new \WooCommerce\Square\Utilities\Encryption_Utility();
                $access_token = $encryption->decrypt_data( $encrypted_token );
            } catch ( Exception $e ) {
                // Use as-is if decryption fails
            }
        }

        // Get location ID
        $location_key = $env_key . '_location_id';
        $location_id = isset( $wc_square_settings[ $location_key ] ) ? $wc_square_settings[ $location_key ] : '';
        if ( empty( $location_id ) ) {
            return false;
        }

        // Get application ID
        if ( $is_sandbox ) {
            $this->application_id = isset( $wc_square_settings['sandbox_application_id'] ) ? $wc_square_settings['sandbox_application_id'] : '';
            if ( empty( $this->application_id ) ) {
                return false;
            }
        } else {
            $this->application_id = self::WC_SQUARE_APP_ID;
        }

        $this->access_token = $access_token;
        $this->location_id  = $location_id;

        return true;
    }

    /**
     * Load manual credentials from GlowBook settings.
     *
     * @return void
     */
    private function load_manual_credentials(): void {
        $prefix = 'sandbox' === $this->environment ? 'sandbox' : 'production';

        $this->application_id = get_option( "sodek_gb_square_{$prefix}_app_id", '' );
        $this->location_id    = get_option( "sodek_gb_square_{$prefix}_location_id", '' );

        // Access token is encrypted
        $encrypted_token    = get_option( "sodek_gb_square_{$prefix}_access_token", '' );
        $this->access_token = self::decrypt_token( $encrypted_token );
        $this->using_wc_square = false;
    }

    /**
     * Check if WooCommerce Square is available and configured.
     *
     * @return bool
     */
    public static function is_wc_square_available(): bool {
        if ( ! function_exists( 'wc_square' ) ) {
            return false;
        }

        try {
            $settings = wc_square()->get_settings_handler();
            return $settings && $settings->is_connected() && $settings->get_location_id();
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Check if currently using WooCommerce Square credentials.
     *
     * @return bool
     */
    public function is_using_wc_square(): bool {
        return $this->using_wc_square;
    }

    /**
     * Get the current environment (sandbox or production).
     *
     * @return string
     */
    public function get_environment(): string {
        return $this->environment;
    }

    /**
     * Get WooCommerce Square Gateway API instance.
     *
     * Uses WC Square's API class which handles:
     * - Automatic token refresh on expiry
     * - Square SDK request/response handling
     * - Proper error handling
     *
     * @return \WooCommerce\Square\Gateway\API|null
     */
    private function get_wc_square_api() {
        if ( ! $this->using_wc_square || ! function_exists( 'wc_square' ) ) {
            return null;
        }

        if ( null === $this->wc_square_api ) {
            try {
                $settings = wc_square()->get_settings_handler();
                if ( $settings && $settings->is_connected() ) {
                    // Use WC Square's Gateway API class
                    $this->wc_square_api = new \WooCommerce\Square\Gateway\API(
                        $settings->get_access_token(),
                        $settings->get_location_id(),
                        $settings->is_sandbox()
                    );
                }
            } catch ( \Exception $e ) {
                $this->log( 'Failed to initialize WC Square API: ' . $e->getMessage(), 'error' );
                return null;
            }
        }

        return $this->wc_square_api;
    }

    /**
     * Get debug info for troubleshooting.
     *
     * @return array
     */
    public function get_debug_info(): array {
        $debug = array(
            'gateway_id'           => $this->id,
            'enabled'              => $this->enabled,
            'environment'          => $this->environment,
            'using_wc_square'      => $this->using_wc_square,
            'has_application_id'   => ! empty( $this->application_id ),
            'application_id_start' => ! empty( $this->application_id ) ? substr( $this->application_id, 0, 12 ) : '',
            'has_access_token'     => ! empty( $this->access_token ),
            'has_location_id'      => ! empty( $this->location_id ),
            'is_configured'        => $this->is_configured(),
            'is_available'         => $this->is_available(),
            'wc_square_active'     => function_exists( 'wc_square' ),
            'wc_square_options'    => ! empty( get_option( 'wc_square_settings' ) ),
            'square_sdk_available' => class_exists( '\Square\SquareClient' ),
            'can_use_sdk'          => $this->can_use_square_sdk(),
        );

        // Check WC Square sandbox settings if in sandbox mode
        if ( 'sandbox' === $this->environment && function_exists( 'wc_square' ) ) {
            $settings = wc_square()->get_settings_handler();
            $sandbox_app_id = $settings->get_option( 'sandbox_application_id' );
            $debug['wc_square_sandbox_app_id_set'] = ! empty( $sandbox_app_id );
            if ( empty( $sandbox_app_id ) ) {
                $debug['error_hint'] = 'Sandbox Application ID not configured in WooCommerce > Settings > Square';
            }
        }

        return $debug;
    }

    /**
     * Check if the gateway is properly configured.
     *
     * @return bool
     */
    public function is_configured(): bool {
        return ! empty( $this->application_id ) &&
               ! empty( $this->access_token ) &&
               ! empty( $this->location_id );
    }

    /**
     * Get the API base URL.
     *
     * @return string
     */
    private function get_api_url(): string {
        return 'sandbox' === $this->environment ? self::SANDBOX_API_URL : self::PRODUCTION_API_URL;
    }

    /**
     * Get the Web Payments SDK URL.
     *
     * @return string
     */
    private function get_sdk_url(): string {
        return 'sandbox' === $this->environment ? self::SANDBOX_SDK_URL : self::PRODUCTION_SDK_URL;
    }

    /**
     * Create a payment.
     *
     * When using WooCommerce Square credentials, this uses the Square PHP SDK
     * through WC Square's client for automatic token refresh and proper handling.
     * Falls back to raw HTTP requests when using manual credentials.
     *
     * @param float $amount   Payment amount.
     * @param array $metadata Additional payment metadata.
     * @return array Result with 'success' boolean and 'data' or 'error'.
     */
    public function create_payment( float $amount, array $metadata ): array {
        if ( ! $this->is_configured() ) {
            return $this->error_response( 'not_configured', __( 'Square gateway is not configured.', 'glowbook' ) );
        }

        if ( ! $this->validate_amount( $amount ) ) {
            return $this->error_response( 'invalid_amount', __( 'Invalid payment amount.', 'glowbook' ) );
        }

        if ( empty( $metadata['source_id'] ) ) {
            return $this->error_response( 'missing_source', __( 'Payment source (card token) is required.', 'glowbook' ) );
        }

        // Use WC Square SDK when available for automatic token refresh
        if ( $this->using_wc_square && $this->can_use_square_sdk() ) {
            return $this->create_payment_via_sdk( $amount, $metadata );
        }

        // Fall back to raw HTTP requests
        return $this->create_payment_via_http( $amount, $metadata );
    }

    /**
     * Check if we can use the Square PHP SDK.
     *
     * @return bool
     */
    private function can_use_square_sdk(): bool {
        return function_exists( 'wc_square' ) && class_exists( '\Square\SquareClient' );
    }

    /**
     * Create payment using Square PHP SDK (via WooCommerce Square).
     *
     * This method leverages WooCommerce Square's infrastructure for:
     * - Automatic OAuth token refresh on expiry
     * - Proper Square SDK error handling
     * - Consistent payment processing
     *
     * @param float $amount      Payment amount.
     * @param array $metadata    Payment metadata.
     * @param int   $retry_count Number of retries attempted (prevents infinite recursion).
     * @return array Result with 'success' boolean and 'data' or 'error'.
     */
    private function create_payment_via_sdk( float $amount, array $metadata, int $retry_count = 0 ): array {
        try {
            $settings = wc_square()->get_settings_handler();
            $is_sandbox = $settings->is_sandbox();

            // Initialize Square SDK client
            $client = new \Square\SquareClient([
                'accessToken' => $settings->get_access_token(),
                'environment' => $is_sandbox ? \Square\Environment::SANDBOX : \Square\Environment::PRODUCTION,
            ]);

            $currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'sodek_gb_currency', 'USD' );
            $idempotency_key = $this->generate_idempotency_key( 'gb_' );

            // Build the payment request using Square SDK models
            $amount_money = new \Square\Models\Money();
            $amount_money->setAmount( $this->amount_to_cents( $amount ) );
            $amount_money->setCurrency( $currency );

            $payment_request = new \Square\Models\CreatePaymentRequest(
                sanitize_text_field( $metadata['source_id'] ),
                $idempotency_key
            );

            $payment_request->setAmountMoney( $amount_money );
            $payment_request->setLocationId( $settings->get_location_id() );
            $payment_request->setAutocomplete( true );

            // Add optional fields
            if ( ! empty( $metadata['customer_email'] ) ) {
                $payment_request->setBuyerEmailAddress( sanitize_email( $metadata['customer_email'] ) );
            }

            if ( ! empty( $metadata['reference_id'] ) ) {
                $payment_request->setReferenceId( sanitize_text_field( $metadata['reference_id'] ) );
            }

            if ( ! empty( $metadata['note'] ) ) {
                $payment_request->setNote( sanitize_text_field( substr( $metadata['note'], 0, 500 ) ) );
            }

            if ( ! empty( $metadata['verification_token'] ) ) {
                $payment_request->setVerificationToken( sanitize_text_field( $metadata['verification_token'] ) );
            }

            $square_customer_id = $this->get_square_customer_id_for_payment( $metadata );
            if ( ! empty( $square_customer_id ) && method_exists( $payment_request, 'setCustomerId' ) ) {
                $payment_request->setCustomerId( $square_customer_id );
            }

            // Add booking metadata to Square payment for tracking/recovery
            // Note: setMetadata() may not be available in all Square SDK versions
            if ( ! empty( $metadata['metadata'] ) && is_array( $metadata['metadata'] ) && method_exists( $payment_request, 'setMetadata' ) ) {
                $square_metadata = array();
                foreach ( $metadata['metadata'] as $key => $value ) {
                    // Square metadata: keys max 40 chars, values max 500 chars
                    $safe_key = sanitize_key( substr( $key, 0, 40 ) );
                    $safe_value = sanitize_text_field( substr( (string) $value, 0, 500 ) );
                    if ( ! empty( $safe_key ) && $safe_value !== '' ) {
                        $square_metadata[ $safe_key ] = $safe_value;
                    }
                }
                if ( ! empty( $square_metadata ) ) {
                    $payment_request->setMetadata( $square_metadata );
                }
            }

            $this->log( 'Creating payment via SDK: ' . wp_json_encode( array(
                'amount'          => $amount,
                'currency'        => $currency,
                'reference_id'    => $metadata['reference_id'] ?? '',
                'idempotency_key' => $idempotency_key,
                'using_sdk'       => true,
            ) ), 'info' );

            // Make the API call
            $api_response = $client->getPaymentsApi()->createPayment( $payment_request );

            if ( ! $api_response->isSuccess() ) {
                $errors = $api_response->getErrors();
                $error_message = $this->parse_sdk_errors( $errors );
                $error_code = ! empty( $errors ) ? $errors[0]->getCode() : 'payment_failed';

                // Check for token expiry and trigger refresh (max 1 retry to prevent infinite loop)
                if ( in_array( $error_code, array( 'ACCESS_TOKEN_EXPIRED', 'UNAUTHORIZED' ), true ) && $retry_count < 1 ) {
                    $this->log( 'Access token expired, triggering refresh...', 'info' );
                    wc_square()->get_connection_handler()->refresh_connection();

                    // Retry once after token refresh
                    return $this->create_payment_via_sdk( $amount, $metadata, $retry_count + 1 );
                }

                $this->log( "SDK Payment failed: [{$error_code}] {$error_message}", 'error' );
                return $this->error_response( $error_code, $error_message );
            }

            $payment = $api_response->getResult()->getPayment();

            $this->log( 'SDK Payment successful: ' . $payment->getId(), 'info' );

            return $this->success_response( array(
                'payment_id'      => $payment->getId(),
                'status'          => $payment->getStatus(),
                'receipt_url'     => $payment->getReceiptUrl() ?? '',
                'card_brand'      => $payment->getCardDetails() ? $payment->getCardDetails()->getCard()->getCardBrand() : '',
                'card_last4'      => $payment->getCardDetails() ? $payment->getCardDetails()->getCard()->getLast4() : '',
                'amount'          => $this->cents_to_amount( $payment->getAmountMoney()->getAmount() ),
                'currency'        => $payment->getAmountMoney()->getCurrency(),
                'created_at'      => $payment->getCreatedAt(),
                'idempotency_key' => $idempotency_key,
            ) );

        } catch ( \Exception $e ) {
            $this->log( 'SDK Exception: ' . $e->getMessage(), 'error' );

            // Fall back to HTTP request on SDK errors
            $this->log( 'Falling back to HTTP request...', 'info' );
            return $this->create_payment_via_http( $amount, $metadata );
        }
    }

    /**
     * Parse Square SDK errors.
     *
     * @param array $errors Array of Square\Models\Error objects.
     * @return string Combined error message.
     */
    private function parse_sdk_errors( array $errors ): string {
        if ( empty( $errors ) ) {
            return __( 'An unknown error occurred.', 'glowbook' );
        }

        $messages = array();
        foreach ( $errors as $error ) {
            $messages[] = $error->getDetail() ?? $error->getCode() ?? __( 'Unknown error', 'glowbook' );
        }

        return implode( ' ', $messages );
    }

    /**
     * Create payment using raw HTTP requests.
     *
     * Fallback method when Square SDK is not available.
     *
     * @param float $amount   Payment amount.
     * @param array $metadata Payment metadata.
     * @return array Result with 'success' boolean and 'data' or 'error'.
     */
    private function create_payment_via_http( float $amount, array $metadata ): array {
        $idempotency_key = $this->generate_idempotency_key( 'gb_' );
        $currency        = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'sodek_gb_currency', 'USD' );

        $payment_data = array(
            'idempotency_key' => $idempotency_key,
            'source_id'       => sanitize_text_field( $metadata['source_id'] ),
            'amount_money'    => array(
                'amount'   => $this->amount_to_cents( $amount ),
                'currency' => $currency,
            ),
            'location_id'     => $this->location_id,
            'autocomplete'    => true,
        );

        // Add buyer email if provided
        if ( ! empty( $metadata['customer_email'] ) ) {
            $payment_data['buyer_email_address'] = sanitize_email( $metadata['customer_email'] );
        }

        // Add reference ID (booking/order reference)
        if ( ! empty( $metadata['reference_id'] ) ) {
            $payment_data['reference_id'] = sanitize_text_field( $metadata['reference_id'] );
        }

        // Add note
        if ( ! empty( $metadata['note'] ) ) {
            $payment_data['note'] = sanitize_text_field( substr( $metadata['note'], 0, 500 ) );
        }

        // Add verification token if provided (for 3DS/SCA)
        if ( ! empty( $metadata['verification_token'] ) ) {
            $payment_data['verification_token'] = sanitize_text_field( $metadata['verification_token'] );
        }

        $square_customer_id = $this->get_square_customer_id_for_payment( $metadata );
        if ( ! empty( $square_customer_id ) ) {
            $payment_data['customer_id'] = $square_customer_id;
        }

        // Add booking metadata for tracking/recovery
        if ( ! empty( $metadata['metadata'] ) && is_array( $metadata['metadata'] ) ) {
            $square_metadata = array();
            foreach ( $metadata['metadata'] as $key => $value ) {
                // Square metadata: keys max 40 chars, values max 500 chars
                $safe_key = sanitize_key( substr( $key, 0, 40 ) );
                $safe_value = sanitize_text_field( substr( (string) $value, 0, 500 ) );
                if ( ! empty( $safe_key ) && $safe_value !== '' ) {
                    $square_metadata[ $safe_key ] = $safe_value;
                }
            }
            if ( ! empty( $square_metadata ) ) {
                $payment_data['metadata'] = $square_metadata;
            }
        }

        $this->log( 'Creating payment via HTTP: ' . wp_json_encode( array(
            'amount'          => $amount,
            'currency'        => $currency,
            'reference_id'    => $metadata['reference_id'] ?? '',
            'idempotency_key' => $idempotency_key,
            'using_sdk'       => false,
        ) ), 'info' );

        $response = $this->make_api_request( '/payments', $payment_data );

        if ( is_wp_error( $response ) ) {
            $this->log( 'Payment API error: ' . $response->get_error_message(), 'error' );
            return $this->error_response( 'api_error', $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code >= 400 || ! empty( $body['errors'] ) ) {
            $error_message = $this->parse_api_errors( $body['errors'] ?? array() );
            $error_code    = $body['errors'][0]['code'] ?? 'payment_failed';

            $this->log( "Payment failed: [{$error_code}] {$error_message}", 'error' );

            return $this->error_response( $error_code, $error_message );
        }

        $payment = $body['payment'] ?? array();

        $this->log( 'Payment successful: ' . ( $payment['id'] ?? 'unknown' ), 'info' );

        return $this->success_response( array(
            'payment_id'       => $payment['id'] ?? '',
            'status'           => $payment['status'] ?? '',
            'receipt_url'      => $payment['receipt_url'] ?? '',
            'card_brand'       => $payment['card_details']['card']['card_brand'] ?? '',
            'card_last4'       => $payment['card_details']['card']['last_4'] ?? '',
            'amount'           => $this->cents_to_amount( $payment['amount_money']['amount'] ?? 0 ),
            'currency'         => $payment['amount_money']['currency'] ?? $currency,
            'created_at'       => $payment['created_at'] ?? '',
            'idempotency_key'  => $idempotency_key,
        ) );
    }

    /**
     * Refund a payment.
     *
     * When using WooCommerce Square credentials, this uses the Square PHP SDK
     * for automatic token refresh. Falls back to raw HTTP requests when not available.
     *
     * @param string $payment_id Payment ID to refund.
     * @param float  $amount     Amount to refund.
     * @param string $reason     Reason for refund.
     * @return array Result with 'success' boolean and 'data' or 'error'.
     */
    public function refund_payment( string $payment_id, float $amount, string $reason = '' ): array {
        if ( ! $this->is_configured() ) {
            return $this->error_response( 'not_configured', __( 'Square gateway is not configured.', 'glowbook' ) );
        }

        if ( empty( $payment_id ) ) {
            return $this->error_response( 'missing_payment_id', __( 'Payment ID is required for refund.', 'glowbook' ) );
        }

        if ( ! $this->validate_amount( $amount ) ) {
            return $this->error_response( 'invalid_amount', __( 'Invalid refund amount.', 'glowbook' ) );
        }

        // Use WC Square SDK when available
        if ( $this->using_wc_square && $this->can_use_square_sdk() ) {
            return $this->refund_payment_via_sdk( $payment_id, $amount, $reason );
        }

        // Fall back to raw HTTP requests
        return $this->refund_payment_via_http( $payment_id, $amount, $reason );
    }

    /**
     * Refund payment using Square PHP SDK.
     *
     * @param string $payment_id  Payment ID to refund.
     * @param float  $amount      Amount to refund.
     * @param string $reason      Reason for refund.
     * @param int    $retry_count Number of retries attempted (prevents infinite recursion).
     * @return array Result with 'success' boolean and 'data' or 'error'.
     */
    private function refund_payment_via_sdk( string $payment_id, float $amount, string $reason = '', int $retry_count = 0 ): array {
        try {
            $settings = wc_square()->get_settings_handler();
            $is_sandbox = $settings->is_sandbox();

            $client = new \Square\SquareClient([
                'accessToken' => $settings->get_access_token(),
                'environment' => $is_sandbox ? \Square\Environment::SANDBOX : \Square\Environment::PRODUCTION,
            ]);

            $currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'sodek_gb_currency', 'USD' );
            $idempotency_key = $this->generate_idempotency_key( 'gbr_' );

            $amount_money = new \Square\Models\Money();
            $amount_money->setAmount( $this->amount_to_cents( $amount ) );
            $amount_money->setCurrency( $currency );

            $refund_request = new \Square\Models\RefundPaymentRequest(
                $idempotency_key,
                $amount_money
            );

            $refund_request->setPaymentId( sanitize_text_field( $payment_id ) );

            if ( ! empty( $reason ) ) {
                $refund_request->setReason( sanitize_text_field( substr( $reason, 0, 192 ) ) );
            }

            $this->log( "Processing SDK refund for payment {$payment_id}: {$amount} {$currency}", 'info' );

            $api_response = $client->getRefundsApi()->refundPayment( $refund_request );

            if ( ! $api_response->isSuccess() ) {
                $errors = $api_response->getErrors();
                $error_message = $this->parse_sdk_errors( $errors );
                $error_code = ! empty( $errors ) ? $errors[0]->getCode() : 'refund_failed';

                // Check for token expiry (max 1 retry to prevent infinite loop)
                if ( in_array( $error_code, array( 'ACCESS_TOKEN_EXPIRED', 'UNAUTHORIZED' ), true ) && $retry_count < 1 ) {
                    $this->log( 'Access token expired, triggering refresh...', 'info' );
                    wc_square()->get_connection_handler()->refresh_connection();
                    return $this->refund_payment_via_sdk( $payment_id, $amount, $reason, $retry_count + 1 );
                }

                $this->log( "SDK Refund failed: [{$error_code}] {$error_message}", 'error' );
                return $this->error_response( $error_code, $error_message );
            }

            $refund = $api_response->getResult()->getRefund();

            $this->log( 'SDK Refund successful: ' . $refund->getId(), 'info' );

            return $this->success_response( array(
                'refund_id'  => $refund->getId(),
                'status'     => $refund->getStatus(),
                'amount'     => $this->cents_to_amount( $refund->getAmountMoney()->getAmount() ),
                'created_at' => $refund->getCreatedAt(),
            ) );

        } catch ( \Exception $e ) {
            $this->log( 'SDK Refund Exception: ' . $e->getMessage(), 'error' );
            return $this->refund_payment_via_http( $payment_id, $amount, $reason );
        }
    }

    /**
     * Refund payment using raw HTTP requests.
     *
     * @param string $payment_id Payment ID to refund.
     * @param float  $amount     Amount to refund.
     * @param string $reason     Reason for refund.
     * @return array Result with 'success' boolean and 'data' or 'error'.
     */
    private function refund_payment_via_http( string $payment_id, float $amount, string $reason = '' ): array {
        $idempotency_key = $this->generate_idempotency_key( 'gbr_' );
        $currency        = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'sodek_gb_currency', 'USD' );

        $refund_data = array(
            'idempotency_key' => $idempotency_key,
            'payment_id'      => sanitize_text_field( $payment_id ),
            'amount_money'    => array(
                'amount'   => $this->amount_to_cents( $amount ),
                'currency' => $currency,
            ),
        );

        if ( ! empty( $reason ) ) {
            $refund_data['reason'] = sanitize_text_field( substr( $reason, 0, 192 ) );
        }

        $this->log( "Processing HTTP refund for payment {$payment_id}: {$amount} {$currency}", 'info' );

        $response = $this->make_api_request( '/refunds', $refund_data );

        if ( is_wp_error( $response ) ) {
            $this->log( 'Refund API error: ' . $response->get_error_message(), 'error' );
            return $this->error_response( 'api_error', $response->get_error_message() );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code >= 400 || ! empty( $body['errors'] ) ) {
            $error_message = $this->parse_api_errors( $body['errors'] ?? array() );
            $error_code    = $body['errors'][0]['code'] ?? 'refund_failed';

            $this->log( "Refund failed: [{$error_code}] {$error_message}", 'error' );

            return $this->error_response( $error_code, $error_message );
        }

        $refund = $body['refund'] ?? array();

        $this->log( 'Refund successful: ' . ( $refund['id'] ?? 'unknown' ), 'info' );

        return $this->success_response( array(
            'refund_id'  => $refund['id'] ?? '',
            'status'     => $refund['status'] ?? '',
            'amount'     => $this->cents_to_amount( $refund['amount_money']['amount'] ?? 0 ),
            'created_at' => $refund['created_at'] ?? '',
        ) );
    }

    /**
     * Make a Square API request.
     *
     * @param string $endpoint API endpoint.
     * @param array  $data     Request data.
     * @param string $method   HTTP method.
     * @return array|WP_Error Response or error.
     */
    private function make_api_request( string $endpoint, array $data = array(), string $method = 'POST' ) {
        $url = $this->get_api_url() . $endpoint;

        $args = array(
            'headers' => array(
                'Authorization'  => 'Bearer ' . $this->access_token,
                'Content-Type'   => 'application/json',
                'Square-Version' => self::API_VERSION,
            ),
        );

        if ( ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        return $this->make_request( $url, $args, $method );
    }

    /**
     * Parse Square API errors.
     *
     * @param array $errors Array of error objects.
     * @return string Combined error message.
     */
    private function parse_api_errors( array $errors ): string {
        if ( empty( $errors ) ) {
            return __( 'An unknown error occurred.', 'glowbook' );
        }

        $messages = array();
        foreach ( $errors as $error ) {
            $messages[] = $error['detail'] ?? $error['code'] ?? __( 'Unknown error', 'glowbook' );
        }

        return implode( ' ', $messages );
    }

    /**
     * Create a card on file from a Square card token/nonce.
     *
     * @param mixed  $result      Existing filter result.
     * @param string $card_nonce  Square source/card token.
     * @param int    $customer_id GlowBook customer ID.
     * @return array|WP_Error|mixed
     */
    public function process_card_nonce( $result, $card_nonce, $customer_id ) {
        if ( null !== $result ) {
            return $result;
        }

        if ( ! $this->is_available() || empty( $card_nonce ) || empty( $customer_id ) ) {
            return new WP_Error(
                'square_card_unavailable',
                __( 'Card saving is not available right now.', 'glowbook' )
            );
        }

        $customer = Sodek_GB_Customer::get_by_id( $customer_id );

        if ( ! $customer ) {
            return new WP_Error(
                'customer_not_found',
                __( 'Customer profile not found.', 'glowbook' )
            );
        }

        $square_customer_id = $this->get_or_create_square_customer_id( $customer );

        if ( is_wp_error( $square_customer_id ) ) {
            return $square_customer_id;
        }

        $payload = array(
            'idempotency_key' => $this->generate_idempotency_key( 'gbc_' ),
            'source_id'       => sanitize_text_field( $card_nonce ),
            'card'            => array(
                'customer_id' => $square_customer_id,
                'reference_id' => 'gb_customer_' . absint( $customer_id ),
            ),
        );

        $full_name = trim( Sodek_GB_Customer::get_full_name( $customer_id ) );
        if ( '' !== $full_name ) {
            $payload['card']['cardholder_name'] = $full_name;
        }

        $response = $this->square_api_request( '/cards', $payload, 'POST' );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = $response['body'];
        $card = $body['card'] ?? array();

        if ( empty( $card['id'] ) ) {
            return new WP_Error(
                'square_card_save_failed',
                __( 'Square did not return a saved card reference.', 'glowbook' )
            );
        }

        return array(
            'gateway'        => 'square',
            'card_id'        => sanitize_text_field( $card['id'] ),
            'card_brand'     => $this->normalize_card_brand( $card['card_brand'] ?? '' ),
            'card_last4'     => sanitize_text_field( $card['last_4'] ?? '' ),
            'card_exp_month' => isset( $card['exp_month'] ) ? absint( $card['exp_month'] ) : null,
            'card_exp_year'  => isset( $card['exp_year'] ) ? absint( $card['exp_year'] ) : null,
        );
    }

    /**
     * Disable a saved Square card before removing the local record.
     *
     * @param mixed $result      Existing filter result.
     * @param array $card        Local saved card row.
     * @param int   $customer_id GlowBook customer ID.
     * @return bool|WP_Error|mixed
     */
    public function delete_saved_card( $result, $card, $customer_id ) {
        if ( null !== $result ) {
            return $result;
        }

        if ( empty( $card ) || ! is_array( $card ) ) {
            return new WP_Error(
                'card_not_found',
                __( 'Saved card not found.', 'glowbook' )
            );
        }

        if ( 'square' !== ( $card['gateway'] ?? 'square' ) ) {
            return true;
        }

        if ( empty( $card['card_id'] ) ) {
            return true;
        }

        $response = $this->square_api_request(
            '/cards/' . rawurlencode( sanitize_text_field( $card['card_id'] ) ) . '/disable',
            array(),
            'POST'
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return true;
    }

    /**
     * Get or create the Square customer for a GlowBook customer.
     *
     * @param array $customer GlowBook customer row.
     * @return string|WP_Error
     */
    private function get_or_create_square_customer_id( array $customer ) {
        $customer_id = absint( $customer['id'] ?? 0 );

        if ( ! $customer_id ) {
            return new WP_Error(
                'customer_not_found',
                __( 'Customer profile not found.', 'glowbook' )
            );
        }

        $existing_square_customer_id = Sodek_GB_Customer::get_meta( $customer_id, 'square_customer_id', '' );

        if ( ! empty( $existing_square_customer_id ) ) {
            return sanitize_text_field( $existing_square_customer_id );
        }

        $payload = array(
            'reference_id' => 'gb_customer_' . $customer_id,
        );

        if ( ! empty( $customer['email'] ) ) {
            $payload['email_address'] = sanitize_email( $customer['email'] );
        }

        $square_phone_number = $this->format_square_phone_number( $customer );
        if ( ! empty( $square_phone_number ) ) {
            $payload['phone_number'] = $square_phone_number;
        }

        if ( ! empty( $customer['first_name'] ) ) {
            $payload['given_name'] = sanitize_text_field( $customer['first_name'] );
        }

        if ( ! empty( $customer['last_name'] ) ) {
            $payload['family_name'] = sanitize_text_field( $customer['last_name'] );
        }

        $response = $this->square_api_request( '/customers', $payload, 'POST' );

        if ( is_wp_error( $response ) && 'INVALID_PHONE_NUMBER' === $response->get_error_code() && ! empty( $payload['phone_number'] ) ) {
            unset( $payload['phone_number'] );
            $response = $this->square_api_request( '/customers', $payload, 'POST' );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $square_customer_id = $response['body']['customer']['id'] ?? '';

        if ( empty( $square_customer_id ) ) {
            return new WP_Error(
                'square_customer_create_failed',
                __( 'Square customer profile could not be created.', 'glowbook' )
            );
        }

        Sodek_GB_Customer::update_meta( $customer_id, 'square_customer_id', sanitize_text_field( $square_customer_id ) );

        return sanitize_text_field( $square_customer_id );
    }

    /**
     * Resolve the Square customer ID required for card-on-file charges.
     *
     * @param array $metadata Payment metadata payload.
     * @return string
     */
    private function get_square_customer_id_for_payment( array $metadata ) {
        $source_id = (string) ( $metadata['source_id'] ?? '' );

        if ( '' === $source_id || 0 !== strpos( $source_id, 'ccof:' ) ) {
            return '';
        }

        $customer_id = absint( $metadata['customer_id'] ?? 0 );

        if ( ! $customer_id && ! empty( $metadata['metadata']['customer_id'] ) ) {
            $customer_id = absint( $metadata['metadata']['customer_id'] );
        }

        if ( ! $customer_id ) {
            return '';
        }

        $customer = Sodek_GB_Customer::get_by_id( $customer_id );

        if ( ! $customer ) {
            return '';
        }

        $square_customer_id = $this->get_or_create_square_customer_id( $customer );

        return is_wp_error( $square_customer_id ) ? '' : sanitize_text_field( $square_customer_id );
    }

    /**
     * Format a GlowBook customer phone number for Square.
     *
     * GlowBook stores local digits and country code separately; Square expects
     * an E.164-style value when a phone number is provided.
     *
     * @param array $customer GlowBook customer row.
     * @return string
     */
    private function format_square_phone_number( array $customer ) {
        $phone = trim( (string) ( $customer['phone'] ?? '' ) );

        if ( '' === $phone ) {
            return '';
        }

        $phone = preg_replace( '/[^0-9+]/', '', $phone );

        if ( '' === $phone ) {
            return '';
        }

        if ( '+' === substr( $phone, 0, 1 ) ) {
            return $phone;
        }

        $country_code = preg_replace( '/[^0-9]/', '', (string) ( $customer['phone_country_code'] ?? '' ) );

        if ( '' !== $country_code ) {
            return '+' . $country_code . ltrim( $phone, '0' );
        }

        if ( 10 === strlen( $phone ) ) {
            return '+1' . $phone;
        }

        return '+' . ltrim( $phone, '+' );
    }

    /**
     * Make a Square API request with token refresh retry support.
     *
     * @param string $endpoint    API endpoint.
     * @param array  $data        Request data.
     * @param string $method      HTTP method.
     * @param int    $retry_count Retry count.
     * @return array|WP_Error
     */
    private function square_api_request( string $endpoint, array $data = array(), string $method = 'POST', int $retry_count = 0 ) {
        $url = $this->get_api_url() . $endpoint;

        $args = array(
            'method'  => $method,
            'timeout' => 45,
            'headers' => array(
                'Authorization'  => 'Bearer ' . $this->access_token,
                'Content-Type'   => 'application/json',
                'Square-Version' => self::API_VERSION,
            ),
        );

        if ( ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'square_request_failed',
                $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( in_array( $status_code, array( 401, 403 ), true ) && $this->using_wc_square && function_exists( 'wc_square' ) && $retry_count < 1 ) {
            try {
                wc_square()->get_connection_handler()->refresh_connection();
                $this->load_credentials();
                return $this->square_api_request( $endpoint, $data, $method, $retry_count + 1 );
            } catch ( Exception $e ) {
                return new WP_Error(
                    'square_token_refresh_failed',
                    $e->getMessage()
                );
            }
        }

        if ( $status_code >= 400 || ! empty( $body['errors'] ) ) {
            return new WP_Error(
                $body['errors'][0]['code'] ?? 'square_api_error',
                $this->parse_api_errors( $body['errors'] ?? array() )
            );
        }

        return array(
            'status_code' => $status_code,
            'body'        => is_array( $body ) ? $body : array(),
        );
    }

    /**
     * Normalize card brand values for consistent UI display.
     *
     * @param string $brand Card brand from Square.
     * @return string
     */
    private function normalize_card_brand( string $brand ): string {
        return strtolower( str_replace( ' ', '_', sanitize_text_field( $brand ) ) );
    }

    /**
     * Enqueue frontend scripts.
     *
     * @return void
     */
    public function enqueue_scripts(): void {
        if ( ! $this->is_available() ) {
            return;
        }

        // Square Web Payments SDK
        wp_enqueue_script(
            'square-web-payments-sdk',
            $this->get_sdk_url(),
            array(),
            null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
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

        wp_localize_script( 'sodek-gb-square-payment', 'sodekGbSquare', $this->get_client_config() );
    }

    /**
     * Get client configuration for JavaScript.
     *
     * @return array
     */
    public function get_client_config(): array {
        // Get currency - prefer WooCommerce, fall back to option
        $currency = 'USD';
        if ( function_exists( 'get_woocommerce_currency' ) ) {
            $currency = get_woocommerce_currency();
        } else {
            $currency = get_option( 'sodek_gb_currency', 'USD' );
        }

        // Get country code - prefer WooCommerce, fall back to option
        $country_code = 'US';
        if ( function_exists( 'WC' ) && WC()->countries ) {
            $country_code = WC()->countries->get_base_country();
        } else {
            $country_code = get_option( 'sodek_gb_country_code', 'US' );
        }

        return array(
            'applicationId'  => $this->application_id,
            'locationId'     => $this->location_id,
            'environment'    => $this->environment,
            'currency'       => $currency,
            'countryCode'    => $country_code,
            'usingWcSquare'  => $this->using_wc_square,
            'strings'        => array(
                'cardNumber'        => __( 'Card Number', 'glowbook' ),
                'expirationDate'    => __( 'Expiration Date', 'glowbook' ),
                'cvv'               => __( 'CVV', 'glowbook' ),
                'postalCode'        => __( 'Postal Code', 'glowbook' ),
                'paymentError'      => __( 'Payment processing failed. Please try again.', 'glowbook' ),
                'cardDeclined'      => __( 'Your card was declined. Please try another card.', 'glowbook' ),
                'invalidCard'       => __( 'Please check your card details and try again.', 'glowbook' ),
                'networkError'      => __( 'Network error. Please check your connection and try again.', 'glowbook' ),
                'processingPayment' => __( 'Processing payment...', 'glowbook' ),
            ),
        );
    }

    /**
     * Render settings fields for admin.
     *
     * @return void
     */
    public function render_settings_fields(): void {
        $environment       = get_option( 'sodek_gb_square_environment', 'sandbox' );
        $enabled           = get_option( 'sodek_gb_square_enabled', false );
        $credential_source = get_option( 'sodek_gb_square_credential_source', 'auto' );
        $wc_square_available = self::is_wc_square_available();

        // Sandbox credentials
        $sandbox_app_id       = get_option( 'sodek_gb_square_sandbox_app_id', '' );
        $sandbox_location_id  = get_option( 'sodek_gb_square_sandbox_location_id', '' );
        $sandbox_token_set    = ! empty( get_option( 'sodek_gb_square_sandbox_access_token', '' ) );

        // Production credentials
        $prod_app_id       = get_option( 'sodek_gb_square_production_app_id', '' );
        $prod_location_id  = get_option( 'sodek_gb_square_production_location_id', '' );
        $prod_token_set    = ! empty( get_option( 'sodek_gb_square_production_access_token', '' ) );
        ?>
        <tr>
            <th scope="row">
                <label for="sodek_gb_square_enabled"><?php esc_html_e( 'Enable Square', 'glowbook' ); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" id="sodek_gb_square_enabled" name="sodek_gb_square_enabled" value="1" <?php checked( $enabled, true ); ?>>
                    <?php esc_html_e( 'Enable Square payment gateway', 'glowbook' ); ?>
                </label>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="sodek_gb_square_credential_source"><?php esc_html_e( 'Credential Source', 'glowbook' ); ?></label>
            </th>
            <td>
                <select id="sodek_gb_square_credential_source" name="sodek_gb_square_credential_source">
                    <option value="auto" <?php selected( $credential_source, 'auto' ); ?>>
                        <?php esc_html_e( 'Auto-detect (WooCommerce Square if available)', 'glowbook' ); ?>
                    </option>
                    <option value="woocommerce" <?php selected( $credential_source, 'woocommerce' ); ?> <?php disabled( ! $wc_square_available ); ?>>
                        <?php esc_html_e( 'Use WooCommerce Square', 'glowbook' ); ?>
                    </option>
                    <option value="manual" <?php selected( $credential_source, 'manual' ); ?>>
                        <?php esc_html_e( 'Manual configuration', 'glowbook' ); ?>
                    </option>
                </select>
                <?php if ( $wc_square_available ) : ?>
                    <p class="description" style="color: #46b450;">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e( 'WooCommerce Square is connected and ready to use.', 'glowbook' ); ?>
                    </p>
                <?php else : ?>
                    <p class="description">
                        <?php esc_html_e( 'WooCommerce Square is not configured. Install and connect it, or use manual configuration below.', 'glowbook' ); ?>
                    </p>
                <?php endif; ?>
            </td>
        </tr>

        <tr class="sodek-gb-square-manual-config" <?php echo ( 'manual' !== $credential_source && $wc_square_available ) ? 'style="display:none;"' : ''; ?>>
            <th scope="row">
                <label for="sodek_gb_square_environment"><?php esc_html_e( 'Environment', 'glowbook' ); ?></label>
            </th>
            <td>
                <select id="sodek_gb_square_environment" name="sodek_gb_square_environment">
                    <option value="sandbox" <?php selected( $environment, 'sandbox' ); ?>><?php esc_html_e( 'Sandbox (Testing)', 'glowbook' ); ?></option>
                    <option value="production" <?php selected( $environment, 'production' ); ?>><?php esc_html_e( 'Production (Live)', 'glowbook' ); ?></option>
                </select>
                <p class="description"><?php esc_html_e( 'Use Sandbox for testing, Production for live payments.', 'glowbook' ); ?></p>
            </td>
        </tr>

        <?php
        $show_manual = 'manual' === $credential_source || ! $wc_square_available;
        $hide_sandbox = ! $show_manual || 'sandbox' !== $environment;
        $hide_production = ! $show_manual || 'production' !== $environment;
        ?>

        <tr class="sodek-gb-square-credentials sodek-gb-square-manual-config sodek-gb-square-sandbox" <?php echo $hide_sandbox ? 'style="display:none;"' : ''; ?>>
            <th scope="row" colspan="2">
                <h4 style="margin: 0;"><?php esc_html_e( 'Sandbox Credentials', 'glowbook' ); ?></h4>
            </th>
        </tr>
        <tr class="sodek-gb-square-credentials sodek-gb-square-manual-config sodek-gb-square-sandbox" <?php echo $hide_sandbox ? 'style="display:none;"' : ''; ?>>
            <th scope="row">
                <label for="sodek_gb_square_sandbox_app_id"><?php esc_html_e( 'Application ID', 'glowbook' ); ?></label>
            </th>
            <td>
                <input type="text" id="sodek_gb_square_sandbox_app_id" name="sodek_gb_square_sandbox_app_id" value="<?php echo esc_attr( $sandbox_app_id ); ?>" class="regular-text" placeholder="sandbox-sq0idb-...">
            </td>
        </tr>
        <tr class="sodek-gb-square-credentials sodek-gb-square-manual-config sodek-gb-square-sandbox" <?php echo $hide_sandbox ? 'style="display:none;"' : ''; ?>>
            <th scope="row">
                <label for="sodek_gb_square_sandbox_access_token"><?php esc_html_e( 'Access Token', 'glowbook' ); ?></label>
            </th>
            <td>
                <input type="password" id="sodek_gb_square_sandbox_access_token" name="sodek_gb_square_sandbox_access_token" value="" class="regular-text" placeholder="<?php echo $sandbox_token_set ? '••••••••••••••••' : 'EAAAl...'; ?>" autocomplete="new-password">
                <?php if ( $sandbox_token_set ) : ?>
                    <p class="description"><?php esc_html_e( 'Access token is saved. Enter a new value to replace it.', 'glowbook' ); ?></p>
                <?php else : ?>
                    <p class="description"><?php esc_html_e( 'Your Square access token will be encrypted before storage.', 'glowbook' ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <tr class="sodek-gb-square-credentials sodek-gb-square-manual-config sodek-gb-square-sandbox" <?php echo $hide_sandbox ? 'style="display:none;"' : ''; ?>>
            <th scope="row">
                <label for="sodek_gb_square_sandbox_location_id"><?php esc_html_e( 'Location ID', 'glowbook' ); ?></label>
            </th>
            <td>
                <input type="text" id="sodek_gb_square_sandbox_location_id" name="sodek_gb_square_sandbox_location_id" value="<?php echo esc_attr( $sandbox_location_id ); ?>" class="regular-text" placeholder="L...">
            </td>
        </tr>

        <tr class="sodek-gb-square-credentials sodek-gb-square-manual-config sodek-gb-square-production" <?php echo $hide_production ? 'style="display:none;"' : ''; ?>>
            <th scope="row" colspan="2">
                <h4 style="margin: 0;"><?php esc_html_e( 'Production Credentials', 'glowbook' ); ?></h4>
            </th>
        </tr>
        <tr class="sodek-gb-square-credentials sodek-gb-square-manual-config sodek-gb-square-production" <?php echo $hide_production ? 'style="display:none;"' : ''; ?>>
            <th scope="row">
                <label for="sodek_gb_square_production_app_id"><?php esc_html_e( 'Application ID', 'glowbook' ); ?></label>
            </th>
            <td>
                <input type="text" id="sodek_gb_square_production_app_id" name="sodek_gb_square_production_app_id" value="<?php echo esc_attr( $prod_app_id ); ?>" class="regular-text" placeholder="sq0idp-...">
            </td>
        </tr>
        <tr class="sodek-gb-square-credentials sodek-gb-square-manual-config sodek-gb-square-production" <?php echo $hide_production ? 'style="display:none;"' : ''; ?>>
            <th scope="row">
                <label for="sodek_gb_square_production_access_token"><?php esc_html_e( 'Access Token', 'glowbook' ); ?></label>
            </th>
            <td>
                <input type="password" id="sodek_gb_square_production_access_token" name="sodek_gb_square_production_access_token" value="" class="regular-text" placeholder="<?php echo $prod_token_set ? '••••••••••••••••' : 'EAAAl...'; ?>" autocomplete="new-password">
                <?php if ( $prod_token_set ) : ?>
                    <p class="description"><?php esc_html_e( 'Access token is saved. Enter a new value to replace it.', 'glowbook' ); ?></p>
                <?php else : ?>
                    <p class="description"><?php esc_html_e( 'Your Square access token will be encrypted before storage.', 'glowbook' ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <tr class="sodek-gb-square-credentials sodek-gb-square-manual-config sodek-gb-square-production" <?php echo $hide_production ? 'style="display:none;"' : ''; ?>>
            <th scope="row">
                <label for="sodek_gb_square_production_location_id"><?php esc_html_e( 'Location ID', 'glowbook' ); ?></label>
            </th>
            <td>
                <input type="text" id="sodek_gb_square_production_location_id" name="sodek_gb_square_production_location_id" value="<?php echo esc_attr( $prod_location_id ); ?>" class="regular-text" placeholder="L...">
            </td>
        </tr>

        <tr>
            <th scope="row"><?php esc_html_e( 'Test Connection', 'glowbook' ); ?></th>
            <td>
                <button type="button" class="button sodek-gb-test-square-connection">
                    <?php esc_html_e( 'Test Connection', 'glowbook' ); ?>
                </button>
                <span class="sodek-gb-connection-status"></span>
                <p class="description"><?php esc_html_e( 'Save settings first, then test the connection.', 'glowbook' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Validate settings input.
     *
     * @param array $input Settings input.
     * @return array Validated settings.
     */
    public function validate_settings( array $input ): array {
        $validated = array();

        $validated['sodek_gb_square_enabled'] = ! empty( $input['sodek_gb_square_enabled'] );

        $validated['sodek_gb_square_credential_source'] = in_array( $input['sodek_gb_square_credential_source'] ?? '', array( 'auto', 'woocommerce', 'manual' ), true )
            ? $input['sodek_gb_square_credential_source']
            : 'auto';

        $validated['sodek_gb_square_environment'] = in_array( $input['sodek_gb_square_environment'] ?? '', array( 'sandbox', 'production' ), true )
            ? $input['sodek_gb_square_environment']
            : 'sandbox';

        // Sandbox credentials
        $validated['sodek_gb_square_sandbox_app_id']      = sanitize_text_field( $input['sodek_gb_square_sandbox_app_id'] ?? '' );
        $validated['sodek_gb_square_sandbox_location_id'] = sanitize_text_field( $input['sodek_gb_square_sandbox_location_id'] ?? '' );

        // Only update token if a new one is provided
        if ( ! empty( $input['sodek_gb_square_sandbox_access_token'] ) ) {
            $validated['sodek_gb_square_sandbox_access_token'] = self::encrypt_token( $input['sodek_gb_square_sandbox_access_token'] );
        }

        // Production credentials
        $validated['sodek_gb_square_production_app_id']      = sanitize_text_field( $input['sodek_gb_square_production_app_id'] ?? '' );
        $validated['sodek_gb_square_production_location_id'] = sanitize_text_field( $input['sodek_gb_square_production_location_id'] ?? '' );

        // Only update token if a new one is provided
        if ( ! empty( $input['sodek_gb_square_production_access_token'] ) ) {
            $validated['sodek_gb_square_production_access_token'] = self::encrypt_token( $input['sodek_gb_square_production_access_token'] );
        }

        return $validated;
    }

    /**
     * Test the Square API connection.
     *
     * @return array Result with 'success' and 'message'.
     */
    public function test_connection(): array {
        if ( ! $this->is_configured() ) {
            return array(
                'success' => false,
                'message' => __( 'Square is not configured. Please enter all credentials.', 'glowbook' ),
            );
        }

        // Use SDK when available
        if ( $this->using_wc_square && $this->can_use_square_sdk() ) {
            return $this->test_connection_via_sdk();
        }

        return $this->test_connection_via_http();
    }

    /**
     * Test connection using Square PHP SDK.
     *
     * @param int $retry_count Number of retries attempted (prevents infinite recursion).
     * @return array Result with 'success' and 'message'.
     */
    private function test_connection_via_sdk( int $retry_count = 0 ): array {
        try {
            $settings = wc_square()->get_settings_handler();
            $is_sandbox = $settings->is_sandbox();

            $client = new \Square\SquareClient([
                'accessToken' => $settings->get_access_token(),
                'environment' => $is_sandbox ? \Square\Environment::SANDBOX : \Square\Environment::PRODUCTION,
            ]);

            $api_response = $client->getLocationsApi()->retrieveLocation( $settings->get_location_id() );

            if ( ! $api_response->isSuccess() ) {
                $errors = $api_response->getErrors();
                $error_message = $this->parse_sdk_errors( $errors );

                // Check for token expiry (max 1 retry to prevent infinite loop)
                $error_code = ! empty( $errors ) ? $errors[0]->getCode() : '';
                if ( in_array( $error_code, array( 'ACCESS_TOKEN_EXPIRED', 'UNAUTHORIZED' ), true ) && $retry_count < 1 ) {
                    wc_square()->get_connection_handler()->refresh_connection();
                    return $this->test_connection_via_sdk( $retry_count + 1 );
                }

                return array(
                    'success' => false,
                    'message' => $error_message,
                );
            }

            $location = $api_response->getResult()->getLocation();

            return array(
                'success' => true,
                'message' => sprintf(
                    /* translators: 1: location name, 2: credential source */
                    __( 'Connected to: %1$s (via WooCommerce Square SDK)', 'glowbook' ),
                    $location->getName() ?? __( 'Unknown location', 'glowbook' )
                ),
            );

        } catch ( \Exception $e ) {
            return array(
                'success' => false,
                'message' => $e->getMessage(),
            );
        }
    }

    /**
     * Test connection using raw HTTP requests.
     *
     * @return array Result with 'success' and 'message'.
     */
    private function test_connection_via_http(): array {
        $response = $this->make_api_request( '/locations/' . $this->location_id, array(), 'GET' );

        if ( is_wp_error( $response ) ) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code >= 400 || ! empty( $body['errors'] ) ) {
            $error_message = $this->parse_api_errors( $body['errors'] ?? array() );
            return array(
                'success' => false,
                'message' => $error_message,
            );
        }

        $location = $body['location'] ?? array();
        $source_label = $this->using_wc_square
            ? __( 'via WooCommerce Square', 'glowbook' )
            : __( 'via manual configuration', 'glowbook' );

        return array(
            'success' => true,
            'message' => sprintf(
                /* translators: 1: location name, 2: credential source */
                __( 'Connected to: %1$s (%2$s)', 'glowbook' ),
                $location['name'] ?? __( 'Unknown location', 'glowbook' ),
                $source_label
            ),
        );
    }
}
