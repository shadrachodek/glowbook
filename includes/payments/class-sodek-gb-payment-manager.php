<?php
/**
 * Payment Manager.
 *
 * Orchestrates payment modes and gateway management.
 *
 * @package GlowBook
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Payment_Manager class.
 */
class Sodek_GB_Payment_Manager {

    /**
     * Available gateways.
     *
     * @var array
     */
    private static $gateways = array();

    /**
     * Payment mode (woocommerce or standalone).
     *
     * @var string
     */
    private static $payment_mode = 'woocommerce';

    /**
     * Initialize the payment manager.
     *
     * @return void
     */
    public static function init(): void {
        self::$payment_mode = get_option( 'sodek_gb_payment_mode', 'woocommerce' );

        // Register gateways
        self::register_gateways();

        // Initialize standalone checkout if in standalone mode
        if ( self::is_standalone_mode() ) {
            Sodek_GB_Standalone_Checkout::init();
        }

        // AJAX handler for testing connection
        add_action( 'wp_ajax_sodek_gb_test_square_connection', array( __CLASS__, 'ajax_test_square_connection' ) );
    }

    /**
     * Register available payment gateways.
     *
     * @return void
     */
    private static function register_gateways(): void {
        self::$gateways['square'] = new Sodek_GB_Square_Gateway();

        /**
         * Filter to add custom payment gateways.
         *
         * @param array $gateways Registered gateways.
         */
        self::$gateways = apply_filters( 'sodek_gb_payment_gateways', self::$gateways );
    }

    /**
     * Check if standalone mode is enabled.
     *
     * @return bool
     */
    public static function is_standalone_mode(): bool {
        return 'standalone' === self::$payment_mode;
    }

    /**
     * Check if WooCommerce mode is enabled.
     *
     * @return bool
     */
    public static function is_woocommerce_mode(): bool {
        return 'woocommerce' === self::$payment_mode;
    }

    /**
     * Get the current payment mode.
     *
     * @return string
     */
    public static function get_payment_mode(): string {
        return self::$payment_mode;
    }

    /**
     * Get a specific gateway.
     *
     * @param string $gateway_id Gateway ID.
     * @return Sodek_GB_Payment_Gateway_Interface|null
     */
    public static function get_gateway( string $gateway_id ): ?Sodek_GB_Payment_Gateway_Interface {
        return self::$gateways[ $gateway_id ] ?? null;
    }

    /**
     * Get all registered gateways.
     *
     * @return array
     */
    public static function get_gateways(): array {
        return self::$gateways;
    }

    /**
     * Get available gateways (configured and enabled).
     *
     * @return array
     */
    public static function get_available_gateways(): array {
        $available = array();

        foreach ( self::$gateways as $id => $gateway ) {
            if ( $gateway->is_available() ) {
                $available[ $id ] = $gateway;
            }
        }

        return $available;
    }

    /**
     * Get the default gateway.
     *
     * @return Sodek_GB_Payment_Gateway_Interface|null
     */
    public static function get_default_gateway(): ?Sodek_GB_Payment_Gateway_Interface {
        $available = self::get_available_gateways();

        if ( empty( $available ) ) {
            return null;
        }

        // Return the first available gateway
        return reset( $available );
    }

    /**
     * Check if any gateway is available for standalone payments.
     *
     * @return bool
     */
    public static function has_available_gateway(): bool {
        return ! empty( self::get_available_gateways() );
    }

    /**
     * Enqueue payment scripts based on mode.
     *
     * @return void
     */
    public static function enqueue_payment_scripts(): void {
        if ( ! self::is_standalone_mode() ) {
            return;
        }

        foreach ( self::get_available_gateways() as $gateway ) {
            $gateway->enqueue_scripts();
        }
    }

    /**
     * Get client configuration for all available gateways.
     *
     * @return array
     */
    public static function get_client_config(): array {
        $config = array(
            'paymentMode'     => self::$payment_mode,
            'isStandalone'    => self::is_standalone_mode(),
            'hasGateway'      => self::has_available_gateway(),
            'gateways'        => array(),
            'defaultGateway'  => '',
        );

        foreach ( self::get_available_gateways() as $id => $gateway ) {
            $config['gateways'][ $id ] = $gateway->get_client_config();

            if ( empty( $config['defaultGateway'] ) ) {
                $config['defaultGateway'] = $id;
            }
        }

        return $config;
    }

    /**
     * AJAX handler for testing Square connection.
     *
     * @return void
     */
    public static function ajax_test_square_connection(): void {
        check_ajax_referer( 'sodek_gb_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'glowbook' ) ) );
        }

        $gateway = self::get_gateway( 'square' );

        if ( ! $gateway ) {
            wp_send_json_error( array( 'message' => __( 'Square gateway not found.', 'glowbook' ) ) );
        }

        // Get debug info for troubleshooting
        $debug_info = method_exists( $gateway, 'get_debug_info' ) ? $gateway->get_debug_info() : array();

        if ( ! $gateway->is_configured() ) {
            wp_send_json_error( array(
                'message' => __( 'Square is not configured. ', 'glowbook' ) .
                    ( $debug_info['wc_square_active'] ? __( 'WooCommerce Square is active but credentials may not be loaded.', 'glowbook' ) : __( 'Please configure Square credentials.', 'glowbook' ) ),
                'debug'   => $debug_info,
            ) );
        }

        $result = $gateway->test_connection();

        if ( $result['success'] ) {
            wp_send_json_success( array(
                'message' => $result['message'],
                'debug'   => $debug_info,
            ) );
        } else {
            wp_send_json_error( array(
                'message' => $result['message'],
                'debug'   => $debug_info,
            ) );
        }
    }

    /**
     * Process a payment through the specified gateway.
     *
     * @param string $gateway_id Gateway ID.
     * @param float  $amount     Payment amount.
     * @param array  $metadata   Payment metadata.
     * @return array
     */
    public static function process_payment( string $gateway_id, float $amount, array $metadata ): array {
        $gateway = self::get_gateway( $gateway_id );

        if ( ! $gateway ) {
            return array(
                'success' => false,
                'error'   => array(
                    'code'    => 'gateway_not_found',
                    'message' => __( 'Payment gateway not found.', 'glowbook' ),
                ),
            );
        }

        if ( ! $gateway->is_available() ) {
            return array(
                'success' => false,
                'error'   => array(
                    'code'    => 'gateway_unavailable',
                    'message' => __( 'Payment gateway is not available.', 'glowbook' ),
                ),
            );
        }

        return $gateway->create_payment( $amount, $metadata );
    }

    /**
     * Process a refund through the specified gateway.
     *
     * @param string $gateway_id Gateway ID.
     * @param string $payment_id Original payment ID.
     * @param float  $amount     Refund amount.
     * @param string $reason     Refund reason.
     * @return array
     */
    public static function process_refund( string $gateway_id, string $payment_id, float $amount, string $reason = '' ): array {
        $gateway = self::get_gateway( $gateway_id );

        if ( ! $gateway ) {
            return array(
                'success' => false,
                'error'   => array(
                    'code'    => 'gateway_not_found',
                    'message' => __( 'Payment gateway not found.', 'glowbook' ),
                ),
            );
        }

        return $gateway->refund_payment( $payment_id, $amount, $reason );
    }
}
