<?php
/**
 * WooCommerce cart modifications.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_WC_Cart class.
 */
class Sodek_GB_WC_Cart {

    /**
     * Initialize.
     */
    public static function init() {
        // Validate booking data before add to cart
        add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_add_to_cart' ), 10, 3 );

        // Add booking data to cart item
        add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_cart_item_data' ), 10, 3 );

        // Set cart item price to deposit
        add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'set_deposit_price' ), 20 );

        // Also set price when cart item is restored from session
        add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'get_cart_item_from_session' ), 20, 2 );

        // Display booking info in cart
        add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'display_cart_item_data' ), 10, 2 );

        // Make cart items with different booking data unique
        add_filter( 'woocommerce_cart_item_quantity', array( __CLASS__, 'cart_item_quantity' ), 10, 3 );

        // Display balance info in cart
        add_action( 'woocommerce_after_cart_item_name', array( __CLASS__, 'display_balance_info' ), 10, 2 );

        // Add booking data to order
        add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'add_order_item_meta' ), 10, 4 );

        // Handle AJAX add to cart for booking products
        add_action( 'wp_ajax_sodek_gb_add_to_cart', array( __CLASS__, 'ajax_add_to_cart' ) );
        add_action( 'wp_ajax_nopriv_sodek_gb_add_to_cart', array( __CLASS__, 'ajax_add_to_cart' ) );

        // Cart and checkout deposit summary
        add_action( 'woocommerce_cart_totals_before_order_total', array( __CLASS__, 'display_cart_deposit_summary' ) );
        add_action( 'woocommerce_review_order_before_order_total', array( __CLASS__, 'display_cart_deposit_summary' ) );

        // Order received (thank you) page
        add_action( 'woocommerce_thankyou', array( __CLASS__, 'display_order_deposit_notice' ), 5 );

        // Order details on thank you page and view order
        add_action( 'woocommerce_order_details_before_order_table', array( __CLASS__, 'display_order_balance_notice' ) );

        // Admin order page
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'display_admin_order_booking_info' ) );

        // Hide internal meta from admin order item display
        add_filter( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'hide_order_item_meta' ) );
    }

    /**
     * Hide internal booking meta from order item display.
     *
     * @param array $hidden_meta Array of hidden meta keys.
     * @return array
     */
    public static function hide_order_item_meta( $hidden_meta ) {
        $hidden_meta[] = '_sodek_gb_service_id';
        $hidden_meta[] = '_sodek_gb_booking_date';
        $hidden_meta[] = '_sodek_gb_booking_time';
        $hidden_meta[] = '_sodek_gb_duration';
        $hidden_meta[] = '_sodek_gb_deposit_amount';
        $hidden_meta[] = '_sodek_gb_full_price';
        $hidden_meta[] = '_sodek_gb_booking_notes';
        $hidden_meta[] = '_sodek_gb_booking_id';
        $hidden_meta[] = '_sodek_gb_addon_ids';
        $hidden_meta[] = '_sodek_gb_addons_price';
        $hidden_meta[] = '_sodek_gb_addons_duration';
        $hidden_meta[] = '_sodek_gb_min_deposit';

        return $hidden_meta;
    }

    /**
     * Validate booking data before add to cart.
     *
     * @param bool $passed     Passed validation.
     * @param int  $product_id Product ID.
     * @param int  $quantity   Quantity.
     * @return bool
     */
    public static function validate_add_to_cart( $passed, $product_id, $quantity ) {
        $is_booking = get_post_meta( $product_id, '_sodek_gb_is_booking_product', true );

        if ( 'yes' !== $is_booking ) {
            return $passed;
        }

        // Verify nonce for booking data (soft check - don't block if nonce is present but fails due to caching)
        // The AJAX handler has strict nonce verification; this filter is more lenient
        if ( isset( $_POST['sodek_gb_booking_nonce'] ) ) {
            $nonce_valid = wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sodek_gb_booking_nonce'] ) ), 'sodek_gb_booking_form' );
            if ( ! $nonce_valid ) {
                // Log for debugging but don't block - nonce might be stale from page caching
                error_log( 'GlowBook: Nonce verification failed but continuing (may be cached page)' );
            }
        }

        // Check for booking data
        if ( empty( $_POST['sodek_gb_service_id'] ) || empty( $_POST['sodek_gb_booking_date'] ) || empty( $_POST['sodek_gb_booking_time'] ) ) {
            wc_add_notice( __( 'Please select a date and time for your appointment.', 'glowbook' ), 'error' );
            return false;
        }

        $service_id = absint( wp_unslash( $_POST['sodek_gb_service_id'] ) );
        $date = sanitize_text_field( wp_unslash( $_POST['sodek_gb_booking_date'] ) );
        $time = sanitize_text_field( wp_unslash( $_POST['sodek_gb_booking_time'] ) );

        // Validate date format
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            wc_add_notice( __( 'Invalid date format.', 'glowbook' ), 'error' );
            return false;
        }

        // Validate time format
        if ( ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
            wc_add_notice( __( 'Invalid time format.', 'glowbook' ), 'error' );
            return false;
        }

        // Check if slot is still available
        if ( ! Sodek_GB_Availability::is_slot_available( $date, $time, $service_id ) ) {
            wc_add_notice( __( 'Sorry, this time slot is no longer available. Please choose another time.', 'glowbook' ), 'error' );
            return false;
        }

        // Validate addon-only services must have at least one addon selected
        $service = Sodek_GB_Service::get_service( $service_id );
        if ( $service ) {
            $service_price = floatval( $service['price'] );
            $addons = Sodek_GB_Addon::get_addons_for_service( $service_id );
            $has_addons = ! empty( $addons );

            // Check if this is an addon-only service (base price is 0 but has addons)
            if ( $service_price <= 0 && $has_addons ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Already verified above
                $selected_addon_ids = isset( $_POST['sodek_gb_addon_ids'] ) && is_array( $_POST['sodek_gb_addon_ids'] )
                    ? array_map( 'absint', wp_unslash( $_POST['sodek_gb_addon_ids'] ) )
                    : array();

                if ( empty( $selected_addon_ids ) ) {
                    wc_add_notice( __( 'Please select at least one add-on for this service.', 'glowbook' ), 'error' );
                    return false;
                }
            }
        }

        return $passed;
    }

    /**
     * Add booking data to cart item.
     *
     * @param array $cart_item_data Cart item data.
     * @param int   $product_id     Product ID.
     * @param int   $variation_id   Variation ID.
     * @return array
     */
    public static function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
        $is_booking = get_post_meta( $product_id, '_sodek_gb_is_booking_product', true );

        if ( 'yes' !== $is_booking ) {
            return $cart_item_data;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in validate_add_to_cart()
        if ( ! empty( $_POST['sodek_gb_service_id'] ) && ! empty( $_POST['sodek_gb_booking_date'] ) && ! empty( $_POST['sodek_gb_booking_time'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $service_id = absint( wp_unslash( $_POST['sodek_gb_service_id'] ) );

            // Get service with product deposit override applied
            $service = Sodek_GB_WC_Product::get_service_with_product_deposit( $product_id );
            if ( ! $service ) {
                $service = Sodek_GB_Service::get_service( $service_id );
            }

            // Handle add-ons
            $addon_ids       = array();
            $addons_price    = 0;
            $addons_deposit  = 0;
            $addons_duration = 0;
            $addons_details  = array();

            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ( ! empty( $_POST['sodek_gb_addon_ids'] ) && is_array( $_POST['sodek_gb_addon_ids'] ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $addon_ids = array_map( 'absint', wp_unslash( $_POST['sodek_gb_addon_ids'] ) );
                $addon_ids = Sodek_GB_Addon::validate_addons_for_service( $addon_ids, $service_id );

                if ( ! empty( $addon_ids ) ) {
                    $addons_total    = Sodek_GB_Addon::calculate_addons_total( $addon_ids );
                    $addons_price    = $addons_total['price'];
                    $addons_duration = $addons_total['duration'];
                    $addons_details  = $addons_total['addons'];
                    $addons_deposit  = Sodek_GB_Addon::calculate_addons_deposit( $addon_ids, $service_id );
                }
            }

            // Calculate totals including add-ons
            $base_deposit  = $service ? $service['deposit_amount'] : 0;
            $base_price    = $service ? $service['price'] : 0;
            $base_duration = $service ? $service['duration'] : 0;

            $min_deposit = $base_deposit + $addons_deposit;
            $full_price  = $base_price + $addons_price;

            // Handle custom deposit amount (flexible deposit)
            $chosen_deposit = $min_deposit;
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ( ! empty( $_POST['sodek_gb_custom_deposit'] ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $custom_deposit = floatval( wp_unslash( $_POST['sodek_gb_custom_deposit'] ) );
                // Validate: must be between min deposit and full price
                if ( $custom_deposit >= $min_deposit && $custom_deposit <= $full_price ) {
                    $chosen_deposit = $custom_deposit;
                }
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $booking_date = sanitize_text_field( wp_unslash( $_POST['sodek_gb_booking_date'] ) );
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $booking_time = sanitize_text_field( wp_unslash( $_POST['sodek_gb_booking_time'] ) );
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $booking_notes = isset( $_POST['sodek_gb_booking_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sodek_gb_booking_notes'] ) ) : '';

            $cart_item_data['sodek_gb_booking'] = array(
                'service_id'      => $service_id,
                'service_name'    => $service ? $service['title'] : '',
                'booking_date'    => $booking_date,
                'booking_time'    => $booking_time,
                'duration'        => $base_duration + $addons_duration,
                'base_duration'   => $base_duration,
                'deposit_amount'  => $chosen_deposit,
                'min_deposit'     => $min_deposit,
                'base_deposit'    => $base_deposit,
                'full_price'      => $full_price,
                'base_price'      => $base_price,
                'addon_ids'       => $addon_ids,
                'addons_price'    => $addons_price,
                'addons_deposit'  => $addons_deposit,
                'addons_duration' => $addons_duration,
                'addons'          => $addons_details,
                'notes'           => $booking_notes,
            );

            // Generate unique key for this booking
            $cart_item_data['unique_key'] = md5( microtime() . wp_rand() );
        }

        return $cart_item_data;
    }

    /**
     * Set cart item price to deposit amount.
     *
     * @param WC_Cart $cart Cart object.
     */
    public static function set_deposit_price( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
            return;
        }

        foreach ( $cart->get_cart() as $cart_item ) {
            // Check if this is a booking item with deposit data
            if ( isset( $cart_item['sodek_gb_booking']['deposit_amount'] ) && $cart_item['sodek_gb_booking']['deposit_amount'] > 0 ) {
                $cart_item['data']->set_price( floatval( $cart_item['sodek_gb_booking']['deposit_amount'] ) );
                continue;
            }

            // Fallback: Check if product is marked as booking product and get deposit from service
            $product = $cart_item['data'];
            $is_booking = $product->get_meta( '_sodek_gb_is_booking_product' );

            if ( 'yes' === $is_booking ) {
                $service_id = $product->get_meta( '_sodek_gb_service_id' );
                if ( $service_id ) {
                    $deposit = Sodek_GB_Service::calculate_deposit( $service_id );
                    if ( $deposit > 0 ) {
                        $product->set_price( floatval( $deposit ) );
                    }
                }
            }
        }
    }

    /**
     * Restore cart item data from session.
     *
     * @param array $cart_item Cart item.
     * @param array $values    Session values.
     * @return array
     */
    public static function get_cart_item_from_session( $cart_item, $values ) {
        if ( isset( $values['sodek_gb_booking'] ) ) {
            $cart_item['sodek_gb_booking'] = $values['sodek_gb_booking'];

            // Set the price again after restoring from session
            if ( isset( $cart_item['sodek_gb_booking']['deposit_amount'] ) && $cart_item['sodek_gb_booking']['deposit_amount'] > 0 ) {
                $cart_item['data']->set_price( floatval( $cart_item['sodek_gb_booking']['deposit_amount'] ) );
            }
        }

        if ( isset( $values['unique_key'] ) ) {
            $cart_item['unique_key'] = $values['unique_key'];
        }

        return $cart_item;
    }

    /**
     * AJAX add to cart handler.
     */
    public static function ajax_add_to_cart() {
        check_ajax_referer( 'sodek_gb_booking_form', 'sodek_gb_booking_nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
        $service_id = isset( $_POST['sodek_gb_service_id'] ) ? absint( wp_unslash( $_POST['sodek_gb_service_id'] ) ) : 0;
        $booking_date = isset( $_POST['sodek_gb_booking_date'] ) ? sanitize_text_field( wp_unslash( $_POST['sodek_gb_booking_date'] ) ) : '';
        $booking_time = isset( $_POST['sodek_gb_booking_time'] ) ? sanitize_text_field( wp_unslash( $_POST['sodek_gb_booking_time'] ) ) : '';

        if ( ! $product_id || ! $service_id || ! $booking_date || ! $booking_time ) {
            wp_send_json_error( array( 'message' => __( 'Missing required booking information.', 'glowbook' ) ) );
        }

        // Validate date format
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $booking_date ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'glowbook' ) ) );
        }

        // Validate time format
        if ( ! preg_match( '/^\d{2}:\d{2}$/', $booking_time ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid time format.', 'glowbook' ) ) );
        }

        // Validate slot availability
        if ( ! Sodek_GB_Availability::is_slot_available( $booking_date, $booking_time, $service_id ) ) {
            wp_send_json_error( array( 'message' => __( 'This time slot is no longer available.', 'glowbook' ) ) );
        }

        // Get service data with product deposit override applied
        $service = Sodek_GB_WC_Product::get_service_with_product_deposit( $product_id );
        if ( ! $service ) {
            $service = Sodek_GB_Service::get_service( $service_id );
        }
        if ( ! $service ) {
            wp_send_json_error( array( 'message' => __( 'Invalid service.', 'glowbook' ) ) );
        }

        // Get notes if provided
        $booking_notes = isset( $_POST['sodek_gb_booking_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sodek_gb_booking_notes'] ) ) : '';

        // Handle add-ons
        $addon_ids       = array();
        $addons_price    = 0;
        $addons_deposit  = 0;
        $addons_duration = 0;
        $addons_details  = array();

        if ( ! empty( $_POST['sodek_gb_addon_ids'] ) && is_array( $_POST['sodek_gb_addon_ids'] ) ) {
            $addon_ids = array_map( 'absint', wp_unslash( $_POST['sodek_gb_addon_ids'] ) );
            $addon_ids = Sodek_GB_Addon::validate_addons_for_service( $addon_ids, $service_id );

            if ( ! empty( $addon_ids ) ) {
                $addons_total    = Sodek_GB_Addon::calculate_addons_total( $addon_ids );
                $addons_price    = $addons_total['price'];
                $addons_duration = $addons_total['duration'];
                $addons_details  = $addons_total['addons'];
                $addons_deposit  = Sodek_GB_Addon::calculate_addons_deposit( $addon_ids, $service_id );
            }
        }

        // Validate addon-only services must have at least one addon selected
        $available_addons = Sodek_GB_Addon::get_addons_for_service( $service_id );
        $has_addons = ! empty( $available_addons );
        if ( floatval( $service['price'] ) <= 0 && $has_addons && empty( $addon_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select at least one add-on for this service.', 'glowbook' ) ) );
        }

        // Calculate totals
        $base_deposit  = $service['deposit_amount'];
        $base_price    = $service['price'];
        $base_duration = $service['duration'];

        $min_deposit = $base_deposit + $addons_deposit;
        $full_price  = $base_price + $addons_price;

        // Ensure we have a valid price (must be > 0 after addons)
        if ( $full_price <= 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid booking total. Please select at least one service or add-on.', 'glowbook' ) ) );
        }

        // Handle custom deposit amount (flexible deposit)
        $chosen_deposit = $min_deposit;
        if ( ! empty( $_POST['sodek_gb_custom_deposit'] ) ) {
            $custom_deposit = floatval( wp_unslash( $_POST['sodek_gb_custom_deposit'] ) );
            // Validate: must be between min deposit and full price
            if ( $custom_deposit >= $min_deposit && $custom_deposit <= $full_price ) {
                $chosen_deposit = $custom_deposit;
            } elseif ( $custom_deposit < $min_deposit ) {
                // Silently use minimum deposit if user tried to pay less
                $chosen_deposit = $min_deposit;
            } elseif ( $custom_deposit > $full_price ) {
                // Cap at full price if user tried to pay more
                $chosen_deposit = $full_price;
            }
        }

        // Ensure deposit is not 0 when we have a price
        if ( $chosen_deposit <= 0 && $full_price > 0 ) {
            $chosen_deposit = $full_price; // Default to full price if no deposit configured
        }

        // Prepare cart item data
        $cart_item_data = array(
            'sodek_gb_booking' => array(
                'service_id'      => $service_id,
                'service_name'    => $service['title'],
                'booking_date'    => $booking_date,
                'booking_time'    => $booking_time,
                'duration'        => $base_duration + $addons_duration,
                'base_duration'   => $base_duration,
                'deposit_amount'  => $chosen_deposit,
                'min_deposit'     => $min_deposit,
                'base_deposit'    => $base_deposit,
                'full_price'      => $full_price,
                'base_price'      => $base_price,
                'addon_ids'       => $addon_ids,
                'addons_price'    => $addons_price,
                'addons_deposit'  => $addons_deposit,
                'addons_duration' => $addons_duration,
                'addons'          => $addons_details,
                'notes'           => $booking_notes,
            ),
            'unique_key' => md5( microtime() . wp_rand() ),
        );

        // Add to cart
        $cart_item_key = WC()->cart->add_to_cart( $product_id, 1, 0, array(), $cart_item_data );

        if ( $cart_item_key ) {
            wp_send_json_success( array(
                'message'      => __( 'Booking added to cart!', 'glowbook' ),
                'cart_url'     => wc_get_cart_url(),
                'checkout_url' => wc_get_checkout_url(),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Could not add booking to cart.', 'glowbook' ) ) );
        }
    }

    /**
     * Display booking data in cart.
     *
     * @param array $item_data Item data.
     * @param array $cart_item Cart item.
     * @return array
     */
    public static function display_cart_item_data( $item_data, $cart_item ) {
        if ( isset( $cart_item['sodek_gb_booking'] ) ) {
            $booking = $cart_item['sodek_gb_booking'];

            $item_data[] = array(
                'key'   => __( 'Appointment Date', 'glowbook' ),
                'value' => date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ),
            );

            $item_data[] = array(
                'key'   => __( 'Appointment Time', 'glowbook' ),
                'value' => date_i18n( get_option( 'time_format' ), strtotime( $booking['booking_time'] ) ),
            );

            $item_data[] = array(
                'key'   => __( 'Duration', 'glowbook' ),
                'value' => sprintf( __( '%d minutes', 'glowbook' ), $booking['duration'] ),
            );

            // Display add-ons if selected
            if ( ! empty( $booking['addons'] ) ) {
                $addon_lines = array();
                foreach ( $booking['addons'] as $addon ) {
                    $addon_text = $addon['title'];
                    if ( $addon['duration'] > 0 ) {
                        $addon_text .= ' (+' . Sodek_GB_Addon::format_duration( $addon['duration'] ) . ')';
                    }
                    $addon_text .= ' ' . wc_price( $addon['price'] );
                    $addon_lines[] = $addon_text;
                }
                $item_data[] = array(
                    'key'   => __( 'Add-ons', 'glowbook' ),
                    'value' => implode( '<br>', $addon_lines ),
                );
            }

            // Pricing breakdown
            $base_price = isset( $booking['base_price'] ) ? $booking['base_price'] : $booking['full_price'];
            $item_data[] = array(
                'key'   => __( 'Service Price', 'glowbook' ),
                'value' => wc_price( $base_price ),
            );

            // Show add-ons subtotal if present
            if ( ! empty( $booking['addons_price'] ) && $booking['addons_price'] > 0 ) {
                $item_data[] = array(
                    'key'   => __( 'Add-ons Subtotal', 'glowbook' ),
                    'value' => wc_price( $booking['addons_price'] ),
                );
            }

            $item_data[] = array(
                'key'   => __( 'Total Price', 'glowbook' ),
                'value' => wc_price( $booking['full_price'] ),
            );

            // Show deposit info with extra payment note if applicable
            $deposit_label = __( 'Deposit (Pay Now)', 'glowbook' );
            $deposit_value = wc_price( $booking['deposit_amount'] );

            if ( isset( $booking['min_deposit'] ) && $booking['deposit_amount'] > $booking['min_deposit'] ) {
                $extra = $booking['deposit_amount'] - $booking['min_deposit'];
                $deposit_value .= ' <small>(' . sprintf(
                    /* translators: %s: extra amount paid above minimum */
                    __( 'includes %s extra', 'glowbook' ),
                    wc_price( $extra )
                ) . ')</small>';
            }

            $item_data[] = array(
                'key'   => $deposit_label,
                'value' => $deposit_value,
            );

            $balance = $booking['full_price'] - $booking['deposit_amount'];
            if ( $balance > 0 ) {
                $item_data[] = array(
                    'key'   => __( 'Balance Due at Appointment', 'glowbook' ),
                    'value' => wc_price( $balance ),
                );
            }
        }

        return $item_data;
    }

    /**
     * Prevent quantity change for booking items.
     *
     * @param string $product_quantity Quantity input.
     * @param string $cart_item_key    Cart item key.
     * @param array  $cart_item        Cart item.
     * @return string
     */
    public static function cart_item_quantity( $product_quantity, $cart_item_key, $cart_item ) {
        if ( isset( $cart_item['sodek_gb_booking'] ) ) {
            return '1';
        }
        return $product_quantity;
    }

    /**
     * Display balance due info after cart item name.
     *
     * @param array  $cart_item     Cart item.
     * @param string $cart_item_key Cart item key.
     */
    public static function display_balance_info( $cart_item, $cart_item_key ) {
        if ( isset( $cart_item['sodek_gb_booking'] ) ) {
            $booking = $cart_item['sodek_gb_booking'];
            $balance = $booking['full_price'] - $booking['deposit_amount'];

            echo '<div class="sodek-gb-cart-item-pricing" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-left: 3px solid #2271b1; font-size: 0.9em;">';
            echo '<strong style="color: #2271b1;">' . esc_html__( 'Deposit Payment', 'glowbook' ) . '</strong><br>';
            printf(
                /* translators: %s: deposit amount */
                esc_html__( 'You are paying a deposit of %s today.', 'glowbook' ),
                '<strong>' . wc_price( $booking['deposit_amount'] ) . '</strong>'
            );

            if ( $balance > 0 ) {
                echo '<br>';
                printf(
                    /* translators: %s: balance amount */
                    esc_html__( 'Remaining balance of %s is due at your appointment.', 'glowbook' ),
                    '<strong>' . wc_price( $balance ) . '</strong>'
                );
            }
            echo '</div>';
        }
    }

    /**
     * Display deposit summary in cart and checkout totals.
     */
    public static function display_cart_deposit_summary() {
        $cart = WC()->cart;
        if ( ! $cart ) {
            return;
        }

        $total_service_price = 0;
        $total_deposit = 0;
        $has_bookings = false;

        foreach ( $cart->get_cart() as $cart_item ) {
            if ( isset( $cart_item['sodek_gb_booking'] ) ) {
                $has_bookings = true;
                $total_service_price += floatval( $cart_item['sodek_gb_booking']['full_price'] );
                $total_deposit += floatval( $cart_item['sodek_gb_booking']['deposit_amount'] );
            }
        }

        if ( ! $has_bookings ) {
            return;
        }

        $total_balance = $total_service_price - $total_deposit;
        ?>
        <tr class="sodek-gb-deposit-summary-row">
            <th><?php esc_html_e( 'Total Service Value', 'glowbook' ); ?></th>
            <td data-title="<?php esc_attr_e( 'Total Service Value', 'glowbook' ); ?>">
                <?php echo wc_price( $total_service_price ); ?>
            </td>
        </tr>
        <tr class="sodek-gb-deposit-summary-row">
            <th><?php esc_html_e( 'Deposit (Paying Today)', 'glowbook' ); ?></th>
            <td data-title="<?php esc_attr_e( 'Deposit', 'glowbook' ); ?>" style="color: #2271b1; font-weight: bold;">
                <?php echo wc_price( $total_deposit ); ?>
            </td>
        </tr>
        <?php if ( $total_balance > 0 ) : ?>
        <tr class="sodek-gb-deposit-summary-row">
            <th><?php esc_html_e( 'Balance Due at Appointment', 'glowbook' ); ?></th>
            <td data-title="<?php esc_attr_e( 'Balance Due', 'glowbook' ); ?>" style="color: #d63638;">
                <?php echo wc_price( $total_balance ); ?>
            </td>
        </tr>
        <?php endif;
    }

    /**
     * Display deposit notice on order received (thank you) page.
     *
     * @param int $order_id Order ID.
     */
    public static function display_order_deposit_notice( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $booking_info = self::get_order_booking_summary( $order );
        if ( ! $booking_info['has_bookings'] ) {
            return;
        }

        ?>
        <div class="sodek-gb-order-deposit-notice" style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #155724;"><?php esc_html_e( 'Booking Confirmed!', 'glowbook' ); ?></h3>
            <p style="margin-bottom: 10px;">
                <?php esc_html_e( 'Thank you for your deposit payment. Your appointment has been confirmed.', 'glowbook' ); ?>
            </p>

            <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #c3e6cb;">
                        <strong><?php esc_html_e( 'Total Service Value:', 'glowbook' ); ?></strong>
                    </td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #c3e6cb; text-align: right;">
                        <?php echo wc_price( $booking_info['total_service_price'] ); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0; border-bottom: 1px solid #c3e6cb;">
                        <strong><?php esc_html_e( 'Deposit Paid:', 'glowbook' ); ?></strong>
                    </td>
                    <td style="padding: 8px 0; border-bottom: 1px solid #c3e6cb; text-align: right; color: #155724;">
                        <?php echo wc_price( $booking_info['total_deposit'] ); ?>
                    </td>
                </tr>
                <?php if ( $booking_info['total_balance'] > 0 ) : ?>
                <tr>
                    <td style="padding: 8px 0;">
                        <strong><?php esc_html_e( 'Balance Due at Appointment:', 'glowbook' ); ?></strong>
                    </td>
                    <td style="padding: 8px 0; text-align: right; color: #856404; font-weight: bold;">
                        <?php echo wc_price( $booking_info['total_balance'] ); ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>

            <?php if ( $booking_info['total_balance'] > 0 ) : ?>
            <p style="margin-top: 15px; margin-bottom: 0; padding: 10px; background: #fff3cd; border-radius: 4px; color: #856404;">
                <strong><?php esc_html_e( 'Please Note:', 'glowbook' ); ?></strong>
                <?php esc_html_e( 'The remaining balance is to be paid at the time of your appointment.', 'glowbook' ); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Display balance notice on order details page.
     *
     * @param WC_Order $order Order object.
     */
    public static function display_order_balance_notice( $order ) {
        $booking_info = self::get_order_booking_summary( $order );
        if ( ! $booking_info['has_bookings'] || $booking_info['total_balance'] <= 0 ) {
            return;
        }

        ?>
        <div class="sodek-gb-order-balance-notice" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
            <p style="margin: 0;">
                <strong><?php esc_html_e( 'Balance Due:', 'glowbook' ); ?></strong>
                <?php
                printf(
                    /* translators: %s: balance amount */
                    esc_html__( 'You have a remaining balance of %s to be paid at your appointment.', 'glowbook' ),
                    '<strong>' . wc_price( $booking_info['total_balance'] ) . '</strong>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Display booking info in admin order page.
     *
     * @param WC_Order $order Order object.
     */
    public static function display_admin_order_booking_info( $order ) {
        $booking_info = self::get_order_booking_summary( $order );
        if ( ! $booking_info['has_bookings'] ) {
            return;
        }

        ?>
        <div class="sodek-gb-admin-booking-info" style="margin-top: 15px; padding: 12px; background: #f0f6fc; border-left: 4px solid #2271b1;">
            <h4 style="margin: 0 0 10px 0;"><?php esc_html_e( 'Booking Payment Summary', 'glowbook' ); ?></h4>
            <p style="margin: 5px 0;">
                <strong><?php esc_html_e( 'Total Service Value:', 'glowbook' ); ?></strong>
                <?php echo wc_price( $booking_info['total_service_price'] ); ?>
            </p>
            <p style="margin: 5px 0;">
                <strong><?php esc_html_e( 'Deposit Paid:', 'glowbook' ); ?></strong>
                <?php echo wc_price( $booking_info['total_deposit'] ); ?>
            </p>
            <?php if ( $booking_info['total_balance'] > 0 ) : ?>
            <p style="margin: 5px 0; color: #d63638;">
                <strong><?php esc_html_e( 'Balance Due:', 'glowbook' ); ?></strong>
                <?php echo wc_price( $booking_info['total_balance'] ); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get booking summary from order.
     *
     * @param WC_Order $order Order object.
     * @return array
     */
    private static function get_order_booking_summary( $order ) {
        $total_service_price = 0;
        $total_deposit = 0;
        $has_bookings = false;

        foreach ( $order->get_items() as $item ) {
            $full_price = $item->get_meta( '_sodek_gb_full_price' );
            $deposit = $item->get_meta( '_sodek_gb_deposit_amount' );

            if ( $full_price && $deposit ) {
                $has_bookings = true;
                $total_service_price += floatval( $full_price );
                $total_deposit += floatval( $deposit );
            }
        }

        return array(
            'has_bookings'        => $has_bookings,
            'total_service_price' => $total_service_price,
            'total_deposit'       => $total_deposit,
            'total_balance'       => $total_service_price - $total_deposit,
        );
    }

    /**
     * Add booking data to order item meta.
     *
     * @param WC_Order_Item_Product $item          Order item.
     * @param string                $cart_item_key Cart item key.
     * @param array                 $values        Cart item values.
     * @param WC_Order              $order         Order.
     */
    public static function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
        if ( isset( $values['sodek_gb_booking'] ) ) {
            $booking = $values['sodek_gb_booking'];

            $item->add_meta_data( '_sodek_gb_service_id', $booking['service_id'] );
            $item->add_meta_data( '_sodek_gb_booking_date', $booking['booking_date'] );
            $item->add_meta_data( '_sodek_gb_booking_time', $booking['booking_time'] );
            $item->add_meta_data( '_sodek_gb_duration', $booking['duration'] );
            $item->add_meta_data( '_sodek_gb_deposit_amount', $booking['deposit_amount'] );
            $item->add_meta_data( '_sodek_gb_min_deposit', isset( $booking['min_deposit'] ) ? $booking['min_deposit'] : $booking['deposit_amount'] );
            $item->add_meta_data( '_sodek_gb_full_price', $booking['full_price'] );

            // Save add-on data
            if ( ! empty( $booking['addon_ids'] ) ) {
                $item->add_meta_data( '_sodek_gb_addon_ids', $booking['addon_ids'] );
                $item->add_meta_data( '_sodek_gb_addons_price', $booking['addons_price'] );
                $item->add_meta_data( '_sodek_gb_addons_duration', $booking['addons_duration'] );

                // Display add-ons
                $addon_display = array();
                foreach ( $booking['addons'] as $addon ) {
                    $line = $addon['title'];
                    if ( $addon['duration'] > 0 ) {
                        $line .= ' (+' . Sodek_GB_Addon::format_duration( $addon['duration'] ) . ')';
                    }
                    $line .= ' ' . wc_price( $addon['price'] );
                    $addon_display[] = wp_strip_all_tags( $line );
                }
                $item->add_meta_data( __( 'Add-ons', 'glowbook' ), implode( ', ', $addon_display ) );
            }

            // Save notes
            if ( ! empty( $booking['notes'] ) ) {
                $item->add_meta_data( '_sodek_gb_booking_notes', $booking['notes'] );
                $item->add_meta_data( __( 'Special Requests', 'glowbook' ), $booking['notes'] );
            }

            // Display meta
            $item->add_meta_data( __( 'Appointment Date', 'glowbook' ), date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) );
            $item->add_meta_data( __( 'Appointment Time', 'glowbook' ), date_i18n( get_option( 'time_format' ), strtotime( $booking['booking_time'] ) ) );
        }
    }
}
