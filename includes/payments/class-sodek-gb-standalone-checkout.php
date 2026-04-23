<?php
/**
 * Standalone Checkout Handler.
 *
 * Handles direct payments without WooCommerce cart/checkout.
 *
 * @package GlowBook
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Standalone_Checkout class.
 */
class Sodek_GB_Standalone_Checkout {

    /**
     * Amount returning customers must pay up front.
     */
    private const RETURNING_CUSTOMER_DEPOSIT = 50.0;

    /**
     * Minimum amount new customers must pay up front.
     */
    private const NEW_CUSTOMER_DEPOSIT = 150.0;

    /**
     * Initialize the standalone checkout.
     *
     * @return void
     */
    public static function init(): void {
        // AJAX handlers
        add_action( 'wp_ajax_sodek_gb_standalone_payment', array( __CLASS__, 'process_payment' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_standalone_payment', array( __CLASS__, 'process_payment' ) );
    }

    /**
     * Check whether customer-specific booking payment rules are enabled.
     *
     * @return bool
     */
    private static function customer_payment_rules_enabled(): bool {
        return (bool) get_option( 'sodek_gb_customer_payment_rules_enabled', 1 );
    }

    /**
     * Check whether customer type should be enforced from booking history.
     *
     * @return bool
     */
    private static function enforce_customer_payment_type(): bool {
        return (bool) get_option( 'sodek_gb_enforce_customer_payment_type', 0 );
    }

    /**
     * Get the configured returning customer payment amount.
     *
     * @return float
     */
    private static function get_returning_customer_payment_amount(): float {
        return max( 0, (float) get_option( 'sodek_gb_returning_customer_payment_amount', self::RETURNING_CUSTOMER_DEPOSIT ) );
    }

    /**
     * Get the configured new customer payment amount.
     *
     * @return float
     */
    private static function get_new_customer_payment_amount(): float {
        return max( 0, (float) get_option( 'sodek_gb_new_customer_payment_amount', self::NEW_CUSTOMER_DEPOSIT ) );
    }

    /**
     * Get the service-based minimum payment amount.
     *
     * @param int   $service_id   Service ID.
     * @param float $total_price  Total booking price.
     * @return float
     */
    private static function get_service_minimum_payment_amount( int $service_id, float $total_price ): float {
        $deposit_type  = get_post_meta( $service_id, '_sodek_gb_deposit_type', true ) ?: 'fixed';
        $deposit_value = (float) get_post_meta( $service_id, '_sodek_gb_deposit_value', true );

        if ( 'percentage' === $deposit_type ) {
            return round( $total_price * ( $deposit_value / 100 ), 2 );
        }

        return min( $deposit_value > 0 ? $deposit_value : $total_price, $total_price );
    }

    /**
     * Get the required booking-time payment amount.
     *
     * @param int   $service_id             Service ID.
     * @param float $total_price            Total booking price.
     * @param bool  $is_returning_customer  Whether the customer is returning.
     * @return float
     */
    private static function get_required_booking_payment_amount( int $service_id, float $total_price, bool $is_returning_customer ): float {
        if ( ! self::customer_payment_rules_enabled() ) {
            return self::get_service_minimum_payment_amount( $service_id, $total_price );
        }

        if ( $is_returning_customer ) {
            return min( $total_price, self::get_returning_customer_payment_amount() );
        }

        return self::get_new_customer_payment_amount();
    }

    /**
     * Process a standalone payment.
     *
     * @return void
     */
    public static function process_payment(): void {
        // Prevent PHP errors from being displayed as HTML (breaks JSON)
        @ini_set( 'display_errors', '0' );
        @ini_set( 'html_errors', '0' );

        // Clean ALL output buffers to ensure clean JSON response
        while ( ob_get_level() > 0 ) {
            ob_end_clean();
        }

        // Start fresh output buffer
        ob_start();

        try {
            self::do_process_payment();
        } catch ( Exception $e ) {
            // Clean any output generated during error
            if ( ob_get_level() > 0 ) {
                ob_end_clean();
            }
            wp_send_json_error( array(
                'message' => __( 'An unexpected error occurred. Please try again.', 'glowbook' ),
                'code'    => 'exception',
                'debug'   => defined( 'WP_DEBUG' ) && WP_DEBUG ? $e->getMessage() : null,
            ) );
        } catch ( Error $e ) {
            // Clean any output generated during error
            if ( ob_get_level() > 0 ) {
                ob_end_clean();
            }
            wp_send_json_error( array(
                'message' => __( 'An unexpected error occurred. Please try again.', 'glowbook' ),
                'code'    => 'fatal_error',
                'debug'   => defined( 'WP_DEBUG' ) && WP_DEBUG ? $e->getMessage() : null,
            ) );
        }

        // Clean buffer before response (should not reach here normally)
        if ( ob_get_level() > 0 ) {
            ob_end_clean();
        }
    }

    /**
     * Internal payment processing logic.
     *
     * @return void
     */
    private static function do_process_payment(): void {
        // Verify nonce
        if ( ! check_ajax_referer( 'sodek_gb_standalone_payment', 'nonce', false ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed. Please refresh the page and try again.', 'glowbook' ),
                'code'    => 'invalid_nonce',
            ) );
        }

        // Validate required fields
        $required_fields = array( 'service_id', 'booking_date', 'booking_time', 'card_token' );
        foreach ( $required_fields as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                wp_send_json_error( array(
                    'message' => sprintf(
                        /* translators: %s: field name */
                        __( 'Missing required field: %s', 'glowbook' ),
                        $field
                    ),
                    'code' => 'missing_field',
                ) );
            }
        }

        // Sanitize input
        $service_id   = absint( $_POST['service_id'] );
        $booking_date = sanitize_text_field( wp_unslash( $_POST['booking_date'] ) );
        $booking_time = sanitize_text_field( wp_unslash( $_POST['booking_time'] ) );
        $card_token   = sanitize_text_field( wp_unslash( $_POST['card_token'] ) );
        $notes        = isset( $_POST['booking_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['booking_notes'] ) ) : '';
        // addon_ids comes as JSON string from frontend
        $addon_ids = array();
        if ( ! empty( $_POST['addon_ids'] ) ) {
            $raw_addon_ids = wp_unslash( $_POST['addon_ids'] );
            // Handle JSON string format from JavaScript
            if ( is_string( $raw_addon_ids ) ) {
                $decoded = json_decode( $raw_addon_ids, true );
                if ( is_array( $decoded ) ) {
                    $addon_ids = array_map( 'absint', $decoded );
                }
            } elseif ( is_array( $raw_addon_ids ) ) {
                $addon_ids = array_map( 'absint', $raw_addon_ids );
            }
        }

        // Get customer info (from logged in user or form)
        $customer_email = '';
        $customer_name  = '';
        $customer_phone = '';

        if ( is_user_logged_in() ) {
            $user           = wp_get_current_user();
            $customer_email = $user->user_email;
            $customer_name  = $user->display_name;
            $customer_phone = get_user_meta( $user->ID, 'billing_phone', true );
        }

        // Override with form data if provided
        if ( ! empty( $_POST['customer_email'] ) ) {
            $customer_email = sanitize_email( wp_unslash( $_POST['customer_email'] ) );
        }
        if ( ! empty( $_POST['customer_name'] ) ) {
            $customer_name = sanitize_text_field( wp_unslash( $_POST['customer_name'] ) );
        }
        if ( ! empty( $_POST['customer_phone'] ) ) {
            $customer_phone = sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) );
        }

        // Validate customer email
        if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please provide a valid email address.', 'glowbook' ),
                'code'    => 'invalid_email',
            ) );
        }

        // Validate customer name
        if ( empty( $customer_name ) || strlen( $customer_name ) < 2 ) {
            wp_send_json_error( array(
                'message' => __( 'Please provide your name.', 'glowbook' ),
                'code'    => 'invalid_name',
            ) );
        }

        // Validate phone number if provided (basic validation)
        if ( ! empty( $customer_phone ) && ! self::is_valid_phone( $customer_phone ) ) {
            wp_send_json_error( array(
                'message' => __( 'Please provide a valid phone number.', 'glowbook' ),
                'code'    => 'invalid_phone',
            ) );
        }

        // Validate service exists
        $service = Sodek_GB_Service::get_service( $service_id );
        if ( ! $service ) {
            wp_send_json_error( array(
                'message' => __( 'Service not found.', 'glowbook' ),
                'code'    => 'service_not_found',
            ) );
        }

        $addon_ids = Sodek_GB_Addon::validate_addons_for_service( $addon_ids, $service_id );
        $addons_duration = Sodek_GB_Availability::get_addons_duration( $addon_ids );

        // Normalize the booking time to 24h format (handles "9:00 AM" -> "09:00")
        $start_time = Sodek_GB_Availability::normalize_time( $booking_time );

        // Validate slot availability with detailed reason
        $availability_check = Sodek_GB_Availability::check_slot_availability( $booking_date, $start_time, $service_id, $addon_ids );

        if ( ! $availability_check['available'] ) {
            $error_data = array(
                'message' => $availability_check['message'],
                'code'    => $availability_check['reason'],
            );

            // Include debug info in development mode
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG && isset( $availability_check['debug'] ) ) {
                $error_data['debug'] = $availability_check['debug'];
            }

            wp_send_json_error( $error_data );
        }

        // Calculate end time for booking using business timezone
        $duration = (int) $service['duration'] + $addons_duration;
        try {
            $tz = Sodek_GB_Availability::get_timezone();
            $start_dt = new DateTime( $booking_date . ' ' . $start_time, $tz );
            $start_dt->modify( "+{$duration} minutes" );
            $end_time = $start_dt->format( 'H:i:s' );
        } catch ( Exception $e ) {
            // Fallback: calculate end time using simple math
            $start_parts = explode( ':', $start_time );
            $start_minutes = ( (int) $start_parts[0] * 60 ) + (int) $start_parts[1];
            $end_minutes = $start_minutes + $duration;
            $end_hour = floor( $end_minutes / 60 ) % 24;
            $end_min = $end_minutes % 60;
            $end_time = sprintf( '%02d:%02d:00', $end_hour, $end_min );
        }

        // Generate a unique reference ID for this booking (moved earlier for lock)
        $reference_id = 'GB-' . wp_generate_password( 8, false, false );

        // Acquire slot lock to prevent race conditions
        // This holds the slot for 5 minutes while payment is processed
        if ( ! Sodek_GB_Availability::acquire_slot_lock( $booking_date, $start_time, $end_time, $service_id, $reference_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'This time slot was just taken by another customer. Please select another time.', 'glowbook' ),
                'code'    => 'slot_locked',
            ) );
        }

        if ( ! Sodek_GB_Availability::has_daily_checkout_capacity( $booking_date ) ) {
            Sodek_GB_Availability::release_slot_lock( $reference_id );

            wp_send_json_error( array(
                'message' => __( 'This date has reached the daily booking limit. Please select another date.', 'glowbook' ),
                'code'    => 'day_fully_booked',
            ) );
        }

        // Calculate total amount server-side (don't trust client)
        $base_price = (float) $service['price'];
        $addons_price = 0;

        if ( ! empty( $addon_ids ) ) {
            foreach ( $addon_ids as $addon_id ) {
                $addon = Sodek_GB_Addon::get_addon( $addon_id );
                if ( $addon ) {
                    $addons_price += (float) $addon['price'];
                }
            }
        }

        $total_price = $base_price + $addons_price;

        $selected_customer_type = isset( $_POST['customer_type'] ) ? sanitize_key( wp_unslash( $_POST['customer_type'] ) ) : 'new';
        $is_returning_customer  = self::customer_payment_rules_enabled() && ! self::enforce_customer_payment_type()
            ? ( 'returning' === $selected_customer_type )
            : Sodek_GB_Customer::is_returning_customer( $customer_email, $customer_phone );
        $required_deposit = self::get_required_booking_payment_amount( $service_id, $total_price, $is_returning_customer );

        // Get custom deposit from form or use the required minimum
        $custom_deposit = isset( $_POST['custom_deposit'] ) ? floatval( $_POST['custom_deposit'] ) : $required_deposit;

        // Validate deposit amount
        if ( $custom_deposit < $required_deposit ) {
            $custom_deposit = $required_deposit;
        }
        if ( ( ! self::customer_payment_rules_enabled() || $is_returning_customer ) && $custom_deposit > $total_price ) {
            $custom_deposit = $total_price;
        }

        $balance_due = max( 0, $total_price - $custom_deposit );
        $name_parts   = preg_split( '/\s+/', trim( $customer_name ), 2 );
        $customer_profile = Sodek_GB_Customer::get_or_create_by_email(
            $customer_email,
            array(
                'first_name' => $name_parts[0] ?? $customer_name,
                'last_name'  => $name_parts[1] ?? '',
                'phone'      => $customer_phone,
            )
        );
        $customer_id = ! empty( $customer_profile['id'] ) ? (int) $customer_profile['id'] : 0;

        // Get verification token if provided (3DS)
        $verification_token = isset( $_POST['verification_token'] ) ? sanitize_text_field( wp_unslash( $_POST['verification_token'] ) ) : '';

        // Get currency - prefer WooCommerce, fall back to option
        $currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'sodek_gb_currency', 'USD' );

        // Get environment from Square gateway (handles WC Square vs manual credentials)
        $square_gateway = Sodek_GB_Payment_Manager::get_gateway( 'square' );
        $environment = 'sandbox';
        if ( $square_gateway && method_exists( $square_gateway, 'get_environment' ) ) {
            $environment = $square_gateway->get_environment();
        } elseif ( function_exists( 'wc_square' ) && wc_square()->get_settings_handler()->is_connected() ) {
            $environment = wc_square()->get_settings_handler()->is_sandbox() ? 'sandbox' : 'production';
        } else {
            $environment = get_option( 'sodek_gb_square_environment', 'sandbox' );
        }

        // Create transaction record first (pending status)
        $transaction_id = Sodek_GB_Transaction::create( array(
            'gateway'          => 'square',
            'environment'      => $environment,
            'amount'           => $custom_deposit,
            'currency'         => $currency,
            'transaction_type' => Sodek_GB_Transaction::TYPE_PAYMENT,
            'status'           => Sodek_GB_Transaction::STATUS_PENDING,
            'customer_email'   => $customer_email,
            'customer_name'    => $customer_name,
            'request_data'     => array(
                'service_id'    => $service_id,
                'booking_date'  => $booking_date,
                'booking_time'  => $booking_time,
                'addon_ids'     => $addon_ids,
                'reference_id'  => $reference_id,
            ),
        ) );

        if ( ! $transaction_id ) {
            wp_send_json_error( array(
                'message' => __( 'Failed to create transaction record.', 'glowbook' ),
                'code'    => 'transaction_create_failed',
            ) );
        }

        // Build comprehensive booking metadata for Square
        // This ensures all details are stored with the payment for recovery if needed
        $booking_metadata = array(
            'glowbook_version'  => SODEK_GB_VERSION,
            'service_id'        => (string) $service_id,
            'service_name'      => $service['title'],
            'service_duration'  => (string) $service['duration'],
            'booking_date'      => $booking_date,
            'booking_time'      => $booking_time,
            'end_time'          => $end_time,
            'customer_name'     => $customer_name,
            'customer_email'    => $customer_email,
            'customer_phone'    => $customer_phone,
            'total_price'       => (string) $total_price,
            'deposit_amount'    => (string) $custom_deposit,
            'required_deposit'  => (string) $required_deposit,
            'customer_type'     => $is_returning_customer ? 'returning' : 'new',
            'balance_due'       => (string) $balance_due,
            'addon_ids'         => ! empty( $addon_ids ) ? implode( ',', $addon_ids ) : '',
            'notes'             => substr( $notes, 0, 200 ), // Square metadata value limit
            'site_url'          => home_url(),
        );

        // Process payment through Square with full metadata
        $payment_result = Sodek_GB_Payment_Manager::process_payment( 'square', $custom_deposit, array(
            'source_id'          => $card_token,
            'verification_token' => $verification_token,
            'customer_email'     => $customer_email,
            'reference_id'       => $reference_id,
            'metadata'           => $booking_metadata,
            'note'               => sprintf(
                /* translators: 1: service name, 2: booking date, 3: booking time, 4: customer name */
                __( '%4$s - %1$s on %2$s at %3$s', 'glowbook' ),
                $service['title'],
                $booking_date,
                $booking_time,
                $customer_name
            ),
        ) );

        if ( ! $payment_result['success'] ) {
            // Release the slot lock since payment failed
            Sodek_GB_Availability::release_slot_lock( $reference_id );

            // Update transaction as failed
            Sodek_GB_Transaction::update( $transaction_id, array(
                'status'        => Sodek_GB_Transaction::STATUS_FAILED,
                'error_code'    => $payment_result['error']['code'] ?? 'payment_failed',
                'error_message' => $payment_result['error']['message'] ?? __( 'Payment failed', 'glowbook' ),
            ) );

            wp_send_json_error( array(
                'message' => $payment_result['error']['message'] ?? __( 'Payment failed. Please try again.', 'glowbook' ),
                'code'    => $payment_result['error']['code'] ?? 'payment_failed',
            ) );
        }

        // Payment successful - update transaction
        Sodek_GB_Transaction::update( $transaction_id, array(
            'status'             => Sodek_GB_Transaction::STATUS_COMPLETED,
            'square_payment_id'  => $payment_result['data']['payment_id'],
            'square_receipt_url' => $payment_result['data']['receipt_url'],
            'square_card_brand'  => $payment_result['data']['card_brand'],
            'square_card_last4'  => $payment_result['data']['card_last4'],
            'response_data'      => $payment_result['data'],
        ) );

        // Create the booking
        $booking_data = array(
            'service_id'      => $service_id,
            'booking_date'    => $booking_date,
            'start_time'      => $start_time,
            'customer_name'   => $customer_name,
            'customer_email'  => $customer_email,
            'customer_phone'  => $customer_phone,
            'customer_id'     => $customer_id,
            'notes'           => $notes,
            'total_price'     => $total_price,
            'deposit_amount'  => $custom_deposit,
            'required_deposit'=> $required_deposit,
            'deposit_paid'    => true,
            'balance_paid'    => ( $balance_due <= 0 ),
            'customer_type'   => $is_returning_customer ? 'returning' : 'new',
            'status'          => 'confirmed',
            'addon_ids'       => $addon_ids,
            'payment_method'  => 'standalone_square',
            'transaction_id'  => $reference_id,
        );

        // Use existing booking creation method
        $booking_id = Sodek_GB_Booking::create_booking( $booking_data );

        if ( ! $booking_id ) {
            // Retry booking creation up to 2 more times
            for ( $retry = 1; $retry <= 2 && ! $booking_id; $retry++ ) {
                usleep( 500000 ); // Wait 0.5 seconds between retries
                $booking_id = Sodek_GB_Booking::create_booking( $booking_data );
            }
        }

        if ( ! $booking_id ) {
            // All retries failed - payment succeeded but booking creation failed
            // Don't refund - all booking details are stored in Square payment metadata
            // Admin can recreate the booking from Square Dashboard

            // Release the slot lock
            Sodek_GB_Availability::release_slot_lock( $reference_id );

            // Log and track the failure for admin review
            self::track_booking_failure( $transaction_id, $payment_result['data'], $booking_data, $booking_metadata );

            // Update transaction with error but keep as completed (payment went through)
            Sodek_GB_Transaction::update( $transaction_id, array(
                'status'        => Sodek_GB_Transaction::STATUS_COMPLETED,
                'error_message' => __( 'Booking creation failed - pending admin review', 'glowbook' ),
            ) );

            // Notify admin
            self::notify_admin_booking_failure( $transaction_id, $payment_result['data'], $booking_data );

            // Return success to customer with their receipt (payment worked)
            wp_send_json_success( array(
                'message'          => __( 'Your payment was successful! We encountered a technical issue creating your booking record, but don\'t worry - our team has been notified and will confirm your appointment within 1 hour. Please save your receipt.', 'glowbook' ),
                'booking_pending'  => true,
                'payment_id'       => $payment_result['data']['payment_id'],
                'reference_id'     => $reference_id,
                'receipt_url'      => $payment_result['data']['receipt_url'],
                'booking'          => array(
                    'date'    => $booking_date,
                    'time'    => $booking_time,
                    'service' => $service['title'],
                    'deposit' => $custom_deposit,
                    'total'   => $total_price,
                ),
            ) );
        }

        // Link transaction to booking
        Sodek_GB_Transaction::update( $transaction_id, array(
            'booking_id' => $booking_id,
        ) );

        if ( $customer_id ) {
            Sodek_GB_Customer::record_booking( $customer_id, $total_price );
        }

        // Reserve the slot (permanent)
        Sodek_GB_Availability::reserve_slot( $booking_id, $booking_date, $start_time, $end_time, $service_id );

        // Release the temporary lock (no longer needed after permanent reservation)
        Sodek_GB_Availability::release_slot_lock( $reference_id );

        // Generate and store confirmation key for the confirmation page
        $confirmation_key = wp_hash( $booking_id . $customer_email . $booking_date );
        update_post_meta( $booking_id, '_sodek_gb_confirmation_key', $confirmation_key );

        // Store additional meta for the confirmation page
        update_post_meta( $booking_id, '_sodek_gb_receipt_url', $payment_result['data']['receipt_url'] );
        update_post_meta( $booking_id, '_sodek_gb_deposit_paid', '1' );
        update_post_meta( $booking_id, '_sodek_gb_balance_amount', $balance_due );

        // Send confirmation emails
        do_action( 'sodek_gb_booking_confirmed', $booking_id );

        // Get the booking confirmation URL
        // If confirmation page is set in settings, use shortcode-based URL with ?key=
        // Otherwise fallback to rewrite-based URL
        $confirmation_page_id = get_option( 'sodek_gb_confirmation_page_id', 0 );

        if ( $confirmation_page_id ) {
            $confirmation_url = add_query_arg( 'key', $confirmation_key, get_permalink( $confirmation_page_id ) );
        } else {
            // Fallback to rewrite-based URL format: /book/confirmation/{key}/
            $booking_slug = get_option( 'sodek_gb_booking_slug', 'book' );
            $confirmation_url = home_url( '/' . $booking_slug . '/confirmation/' . $confirmation_key . '/' );
        }

        wp_send_json_success( array(
            'message'          => __( 'Your booking has been confirmed!', 'glowbook' ),
            'booking_id'       => $booking_id,
            'reference_id'     => $reference_id,
            'receipt_url'      => $payment_result['data']['receipt_url'],
            'confirmation_url' => $confirmation_url,
            'booking'          => array(
                'date'    => $booking_date,
                'time'    => $booking_time,
                'service' => $service['title'],
                'deposit' => $custom_deposit,
                'total'   => $total_price,
                'balance' => $balance_due,
            ),
        ) );
    }

    /**
     * Get the nonce for standalone payment.
     *
     * @return string
     */
    public static function get_nonce(): string {
        return wp_create_nonce( 'sodek_gb_standalone_payment' );
    }

    /**
     * Render the standalone payment form container.
     *
     * @return void
     */
    public static function render_payment_form(): void {
        if ( ! Sodek_GB_Payment_Manager::is_standalone_mode() ) {
            return;
        }

        if ( ! Sodek_GB_Payment_Manager::has_available_gateway() ) {
            return;
        }

        ?>
        <div class="sodek-gb-standalone-payment">
            <div id="sodek-gb-square-card-container" class="sodek-gb-card-container">
                <!-- Square Card form will be mounted here -->
            </div>
            <div id="sodek-gb-payment-errors" class="sodek-gb-payment-errors" role="alert" aria-live="polite"></div>
            <input type="hidden" name="card_token" id="sodek_gb_card_token" value="">
            <input type="hidden" name="verification_token" id="sodek_gb_verification_token" value="">
        </div>
        <?php
    }

    /**
     * Validate phone number format.
     *
     * Accepts common formats:
     * - US: (123) 456-7890, 123-456-7890, 1234567890
     * - International: +1 123 456 7890, +44 20 1234 5678
     *
     * @param string $phone Phone number to validate.
     * @return bool
     */
    private static function is_valid_phone( string $phone ): bool {
        // Remove common formatting characters for validation
        $digits_only = preg_replace( '/[^0-9]/', '', $phone );

        // Must have at least 10 digits (US standard) and no more than 15 (international max)
        $digit_count = strlen( $digits_only );
        if ( $digit_count < 10 || $digit_count > 15 ) {
            return false;
        }

        // Check for valid phone pattern (allows various formats)
        // This regex accepts: +1 (123) 456-7890, 123.456.7890, 123 456 7890, etc.
        $pattern = '/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,3}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/';

        return (bool) preg_match( $pattern, $phone );
    }

    /**
     * Track booking failure for admin to recreate.
     *
     * All booking details are also stored in Square payment metadata,
     * so admin can recreate the booking from Square Dashboard if needed.
     *
     * @param int   $transaction_id   Transaction ID.
     * @param array $payment_data     Payment data from Square.
     * @param array $booking_data     Booking data that failed.
     * @param array $booking_metadata Metadata sent to Square.
     */
    private static function track_booking_failure( int $transaction_id, array $payment_data, array $booking_data, array $booking_metadata ): void {
        $failure_record = array(
            'id'               => uniqid( 'bf_' ),
            'timestamp'        => current_time( 'mysql' ),
            'status'           => 'pending', // pending, resolved, refunded
            'transaction_id'   => $transaction_id,
            'payment_id'       => $payment_data['payment_id'] ?? '',
            'receipt_url'      => $payment_data['receipt_url'] ?? '',
            'booking_data'     => $booking_data,
            'square_metadata'  => $booking_metadata,
            'error'            => 'Booking creation failed after successful payment',
        );

        // Use WooCommerce logger if available
        if ( function_exists( 'wc_get_logger' ) ) {
            $logger = wc_get_logger();
            $logger->error( 'Booking creation failed - pending admin action', array(
                'source' => 'glowbook-standalone',
            ) + $failure_record );
        }

        // Also log to WordPress error log
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'GlowBook Booking Failure (Pending): ' . wp_json_encode( $failure_record ) );
        }

        // Store in database for admin review
        $pending_bookings = get_option( 'sodek_gb_pending_bookings', array() );
        $pending_bookings[] = $failure_record;

        // Keep only last 100 pending bookings
        if ( count( $pending_bookings ) > 100 ) {
            $pending_bookings = array_slice( $pending_bookings, -100 );
        }

        update_option( 'sodek_gb_pending_bookings', $pending_bookings );
    }

    /**
     * Notify admin about booking failure that needs manual creation.
     *
     * All booking details are stored in Square payment metadata for easy recovery.
     *
     * @param int   $transaction_id Transaction ID.
     * @param array $payment_data   Payment data from Square.
     * @param array $booking_data   Booking data that failed.
     */
    private static function notify_admin_booking_failure( int $transaction_id, array $payment_data, array $booking_data ): void {
        $admin_email = get_option( 'admin_email' );
        $site_name   = get_bloginfo( 'name' );
        $currency    = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD';

        $subject = sprintf(
            /* translators: %s: site name */
            __( '[%s] ACTION NEEDED: Create Booking for %s', 'glowbook' ),
            $site_name,
            $booking_data['customer_name'] ?? 'Customer'
        );

        $message = __(
            "A customer's payment was successful but the booking record failed to create.\n\n" .
            "Please create the booking manually using the details below.\n" .
            "All details are also stored in the Square payment metadata.\n\n",
            'glowbook'
        );

        $message .= "═══════════════════════════════════════\n";
        $message .= __( "BOOKING DETAILS TO CREATE", 'glowbook' ) . "\n";
        $message .= "═══════════════════════════════════════\n\n";

        $message .= __( "Customer:", 'glowbook' ) . "\n";
        $message .= sprintf( "  • %s: %s\n", __( 'Name', 'glowbook' ), $booking_data['customer_name'] ?? 'Unknown' );
        $message .= sprintf( "  • %s: %s\n", __( 'Email', 'glowbook' ), $booking_data['customer_email'] ?? 'Unknown' );
        $message .= sprintf( "  • %s: %s\n\n", __( 'Phone', 'glowbook' ), $booking_data['customer_phone'] ?? 'Not provided' );

        $message .= __( "Appointment:", 'glowbook' ) . "\n";
        $message .= sprintf( "  • %s: %s (ID: %s)\n", __( 'Service', 'glowbook' ),
            get_the_title( $booking_data['service_id'] ?? 0 ) ?: 'Unknown',
            $booking_data['service_id'] ?? 'N/A'
        );
        $message .= sprintf( "  • %s: %s\n", __( 'Date', 'glowbook' ), $booking_data['booking_date'] ?? 'Unknown' );
        $message .= sprintf( "  • %s: %s\n\n", __( 'Time', 'glowbook' ), $booking_data['start_time'] ?? 'Unknown' );

        $message .= __( "Payment:", 'glowbook' ) . "\n";
        $message .= sprintf( "  • %s: %s\n", __( 'Total', 'glowbook' ), $currency . ' ' . number_format( $booking_data['total_price'] ?? 0, 2 ) );
        $message .= sprintf( "  • %s: %s\n", __( 'Deposit Paid', 'glowbook' ), $currency . ' ' . number_format( $booking_data['deposit_amount'] ?? 0, 2 ) );
        $message .= sprintf( "  • %s: %s\n", __( 'Balance Due', 'glowbook' ), $currency . ' ' . number_format( max( 0, ( $booking_data['total_price'] ?? 0 ) - ( $booking_data['deposit_amount'] ?? 0 ) ), 2 ) );
        $message .= sprintf( "  • %s: %s\n", __( 'Payment ID', 'glowbook' ), $payment_data['payment_id'] ?? 'Unknown' );
        $message .= sprintf( "  • %s: %s\n\n", __( 'Receipt', 'glowbook' ), $payment_data['receipt_url'] ?? 'Not available' );

        if ( ! empty( $booking_data['notes'] ) ) {
            $message .= __( "Notes:", 'glowbook' ) . "\n";
            $message .= "  " . $booking_data['notes'] . "\n\n";
        }

        $message .= "═══════════════════════════════════════\n\n";

        $message .= sprintf(
            __( "Square Dashboard: https://squareup.com/dashboard/sales/transactions/%s\n\n", 'glowbook' ),
            $payment_data['payment_id'] ?? ''
        );

        $message .= __( "After creating the booking, please mark this as resolved in:\n", 'glowbook' );
        $message .= admin_url( 'admin.php?page=glowbook-pending-bookings' ) . "\n";

        wp_mail( $admin_email, $subject, $message );
    }

    /**
     * Get pending bookings that need admin attention.
     *
     * @return array
     */
    public static function get_pending_bookings(): array {
        return get_option( 'sodek_gb_pending_bookings', array() );
    }

    /**
     * Mark a pending booking as resolved.
     *
     * @param string $failure_id The failure record ID.
     * @param string $status     New status: 'resolved' or 'refunded'.
     * @param int    $booking_id Optional booking ID if created.
     * @return bool
     */
    public static function resolve_pending_booking( string $failure_id, string $status = 'resolved', int $booking_id = 0 ): bool {
        $pending_bookings = get_option( 'sodek_gb_pending_bookings', array() );

        foreach ( $pending_bookings as $key => $record ) {
            if ( isset( $record['id'] ) && $record['id'] === $failure_id ) {
                $pending_bookings[ $key ]['status'] = $status;
                $pending_bookings[ $key ]['resolved_at'] = current_time( 'mysql' );
                if ( $booking_id ) {
                    $pending_bookings[ $key ]['booking_id'] = $booking_id;
                }
                update_option( 'sodek_gb_pending_bookings', $pending_bookings );
                return true;
            }
        }

        return false;
    }
}
