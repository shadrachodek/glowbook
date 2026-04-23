<?php
/**
 * Payment Gateway Interface.
 *
 * Defines the contract that all payment gateways must implement.
 *
 * @package GlowBook
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Payment_Gateway_Interface interface.
 */
interface Sodek_GB_Payment_Gateway_Interface {

    /**
     * Get the gateway ID.
     *
     * @return string
     */
    public function get_id(): string;

    /**
     * Get the gateway title.
     *
     * @return string
     */
    public function get_title(): string;

    /**
     * Check if the gateway is available.
     *
     * @return bool
     */
    public function is_available(): bool;

    /**
     * Check if the gateway is properly configured.
     *
     * @return bool
     */
    public function is_configured(): bool;

    /**
     * Get the current environment (sandbox or production).
     *
     * @return string
     */
    public function get_environment(): string;

    /**
     * Create a payment.
     *
     * @param float $amount   Payment amount.
     * @param array $metadata Additional payment metadata.
     * @return array Result with 'success' boolean and 'data' or 'error'.
     */
    public function create_payment( float $amount, array $metadata ): array;

    /**
     * Refund a payment.
     *
     * @param string $payment_id Payment ID to refund.
     * @param float  $amount     Amount to refund.
     * @param string $reason     Reason for refund.
     * @return array Result with 'success' boolean and 'data' or 'error'.
     */
    public function refund_payment( string $payment_id, float $amount, string $reason = '' ): array;

    /**
     * Enqueue frontend scripts.
     *
     * @return void
     */
    public function enqueue_scripts(): void;

    /**
     * Get client configuration for JavaScript.
     *
     * @return array
     */
    public function get_client_config(): array;

    /**
     * Render settings fields for admin.
     *
     * @return void
     */
    public function render_settings_fields(): void;

    /**
     * Validate settings input.
     *
     * @param array $input Settings input.
     * @return array Validated settings.
     */
    public function validate_settings( array $input ): array;
}
