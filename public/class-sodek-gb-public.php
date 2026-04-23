<?php
/**
 * Public/Frontend functionality.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Public class.
 */
class Sodek_GB_Public {

    /**
     * Initialize.
     */
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
    }

    /**
     * Enqueue frontend scripts and styles.
     */
    public static function enqueue_scripts() {
        // Only load on pages that need it
        if ( ! self::should_load_assets() ) {
            return;
        }

        wp_enqueue_style(
            'sodek-gb-public',
            SODEK_GB_PLUGIN_URL . 'public/css/public.css',
            array(),
            SODEK_GB_VERSION
        );

        // Add custom CSS based on appearance settings.
        $custom_css = self::generate_custom_css();
        if ( ! empty( $custom_css ) ) {
            wp_add_inline_style( 'sodek-gb-public', $custom_css );
        }

        wp_enqueue_script(
            'sodek-gb-public',
            SODEK_GB_PLUGIN_URL . 'public/js/public.js',
            array( 'jquery', 'wp-api-fetch' ),
            SODEK_GB_VERSION,
            true
        );

        // Determine payment mode
        $payment_mode = get_option( 'sodek_gb_payment_mode', 'woocommerce' );

        // Enqueue Square scripts if in standalone mode
        if ( 'standalone' === $payment_mode && class_exists( 'Sodek_GB_Payment_Manager' ) ) {
            $gateway = Sodek_GB_Payment_Manager::get_gateway( 'square' );
            if ( $gateway && $gateway->is_available() ) {
                $gateway->enqueue_scripts();
            }
        }

        wp_localize_script( 'sodek-gb-public', 'sodekGbPublic', array(
            'apiUrl'       => rest_url( 'sodek-gb/v1/' ),
            'nonce'        => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'dateFormat'   => get_option( 'date_format' ),
            'timeFormat'   => get_option( 'time_format' ),
            'startOfWeek'  => (int) get_option( 'start_of_week', 0 ),
            'paymentMode'  => $payment_mode,
            'paymentNonce' => wp_create_nonce( 'sodek_gb_standalone_payment' ),
            'strings'      => array(
                'selectDate'      => __( 'Select a date', 'glowbook' ),
                'selectTime'      => __( 'Select a time', 'glowbook' ),
                'noSlots'         => __( 'No available times on this date', 'glowbook' ),
                'loading'         => __( 'Loading...', 'glowbook' ),
                'error'           => __( 'An error occurred. Please try again.', 'glowbook' ),
                'bookNow'         => __( 'Book & Pay Deposit', 'glowbook' ),
                'selectedSlot'    => __( 'Selected', 'glowbook' ),
                'deposit'         => __( 'Deposit', 'glowbook' ),
                'balanceDue'      => __( 'Balance due at appointment', 'glowbook' ),
                'minutes'         => __( 'minutes', 'glowbook' ),
                'prevMonth'       => __( 'Previous month', 'glowbook' ),
                'nextMonth'       => __( 'Next month', 'glowbook' ),
                'monthNames'      => array(
                    __( 'January', 'glowbook' ),
                    __( 'February', 'glowbook' ),
                    __( 'March', 'glowbook' ),
                    __( 'April', 'glowbook' ),
                    __( 'May', 'glowbook' ),
                    __( 'June', 'glowbook' ),
                    __( 'July', 'glowbook' ),
                    __( 'August', 'glowbook' ),
                    __( 'September', 'glowbook' ),
                    __( 'October', 'glowbook' ),
                    __( 'November', 'glowbook' ),
                    __( 'December', 'glowbook' ),
                ),
                'dayNames'        => array(
                    __( 'Sun', 'glowbook' ),
                    __( 'Mon', 'glowbook' ),
                    __( 'Tue', 'glowbook' ),
                    __( 'Wed', 'glowbook' ),
                    __( 'Thu', 'glowbook' ),
                    __( 'Fri', 'glowbook' ),
                    __( 'Sat', 'glowbook' ),
                ),
                'paidInFull'           => __( 'Paid in Full!', 'glowbook' ),
                'balanceAtAppointment' => __( 'Balance at Appointment:', 'glowbook' ),
                'minimum'              => __( 'Minimum', 'glowbook' ),
                'selectAddonsFirst'    => __( 'Select add-ons first', 'glowbook' ),
                'selectAddonRequired'  => __( 'Please select at least one add-on for this service.', 'glowbook' ),
                /* translators: %s: minimum deposit amount */
                'minDepositRequired'   => __( 'Minimum deposit of %s is required.', 'glowbook' ),
                'securityError'        => __( 'Security check failed. Please refresh the page and try again.', 'glowbook' ),
                'processing'           => __( 'Processing your booking...', 'glowbook' ),
                'success'              => __( 'Redirecting to checkout...', 'glowbook' ),
                'paymentProcessing'    => __( 'Processing payment...', 'glowbook' ),
                'paymentSuccess'       => __( 'Payment successful! Redirecting...', 'glowbook' ),
                'paymentError'         => __( 'Payment failed. Please try again.', 'glowbook' ),
                'cardError'            => __( 'Please check your card details and try again.', 'glowbook' ),
            ),
            'currency'     => array(
                'symbol'    => get_woocommerce_currency_symbol(),
                'position'  => get_option( 'woocommerce_currency_pos', 'left' ),
                'decimals'  => wc_get_price_decimals(),
                'separator' => wc_get_price_decimal_separator(),
                'thousand'  => wc_get_price_thousand_separator(),
            ),
            'checkoutUrl'  => wc_get_checkout_url(),
            'cartUrl'      => wc_get_cart_url(),
        ) );
    }

    /**
     * Check if assets should be loaded.
     *
     * @return bool
     */
    private static function should_load_assets() {
        global $post;

        // Always load on WooCommerce pages.
        if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() ) ) {
            return true;
        }

        // Load on My Account page (for bookings endpoint).
        if ( function_exists( 'is_account_page' ) && is_account_page() ) {
            return true;
        }

        // Check for shortcode in content
        if ( $post && has_shortcode( $post->post_content, 'sodek_gb_booking_form' ) ) {
            return true;
        }

        // Check for booking product page
        if ( is_singular( 'product' ) ) {
            $product = wc_get_product( get_the_ID() );
            if ( $product ) {
                // Check for bookable_service product type
                if ( 'bookable_service' === $product->get_type() ) {
                    return true;
                }
                // Also check legacy meta for backwards compatibility
                $is_booking = get_post_meta( $product->get_id(), '_sodek_gb_is_booking_product', true );
                if ( 'yes' === $is_booking ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Generate custom CSS based on appearance settings.
     *
     * @return string Custom CSS.
     */
    private static function generate_custom_css() {
        $primary_color        = get_option( 'sodek_gb_primary_color', '#2271b1' );
        $button_style         = get_option( 'sodek_gb_button_style', 'filled' );
        $border_radius        = get_option( 'sodek_gb_border_radius', 'medium' );
        $inherit_theme_colors = get_option( 'sodek_gb_inherit_theme_colors', false );

        $css = '';

        // Start building CSS custom properties.
        $css_vars = array();

        // Primary color and derived colors.
        if ( $primary_color && '#2271b1' !== $primary_color ) {
            $css_vars['--sodek-gb-color-primary'] = $primary_color;
            $css_vars['--sodek-gb-color-primary-hover'] = self::adjust_color_brightness( $primary_color, -15 );
            $css_vars['--sodek-gb-color-primary-light'] = self::adjust_color_brightness( $primary_color, 85 );
        }

        // Border radius.
        $radius_values = array(
            'none'    => '0',
            'small'   => '4px',
            'medium'  => '8px',
            'large'   => '12px',
            'rounded' => '24px',
        );
        if ( isset( $radius_values[ $border_radius ] ) && 'medium' !== $border_radius ) {
            $css_vars['--sodek-gb-radius-sm'] = 'none' === $border_radius ? '0' : ( 'small' === $border_radius ? '2px' : ( 'large' === $border_radius ? '8px' : ( 'rounded' === $border_radius ? '16px' : '4px' ) ) );
            $css_vars['--sodek-gb-radius-md'] = $radius_values[ $border_radius ];
            $css_vars['--sodek-gb-radius-lg'] = 'none' === $border_radius ? '0' : ( 'small' === $border_radius ? '6px' : ( 'large' === $border_radius ? '16px' : ( 'rounded' === $border_radius ? '32px' : '12px' ) ) );
        }

        // Inherit theme colors (fonts are already inherited, this adds background/text inheritance).
        if ( $inherit_theme_colors ) {
            $css_vars['--sodek-gb-color-background'] = 'inherit';
            $css_vars['--sodek-gb-color-text'] = 'inherit';
            $css_vars['--sodek-gb-color-text-light'] = 'inherit';
        }

        // Build CSS variable declarations.
        if ( ! empty( $css_vars ) ) {
            $css .= ".sodek-gb-booking-form,\n";
            $css .= ".sodek-gb-service-selector,\n";
            $css .= ".sodek-gb-my-bookings {\n";
            foreach ( $css_vars as $var => $value ) {
                $css .= "    {$var}: {$value};\n";
            }
            $css .= "}\n\n";
        }

        // Button style variations.
        if ( 'outline' === $button_style ) {
            $bg_color = $primary_color ?: '#2271b1';
            $css .= ".sodek-gb-booking-form .sodek-gb-book-button,\n";
            $css .= ".sodek-gb-service-selector .sodek-gb-service-book-btn {\n";
            $css .= "    background-color: transparent;\n";
            $css .= "    color: {$bg_color};\n";
            $css .= "    border: 2px solid {$bg_color};\n";
            $css .= "}\n\n";

            $css .= ".sodek-gb-booking-form .sodek-gb-book-button:hover:not(:disabled),\n";
            $css .= ".sodek-gb-service-selector .sodek-gb-service-book-btn:hover {\n";
            $css .= "    background-color: {$bg_color};\n";
            $css .= "    color: #ffffff;\n";
            $css .= "}\n\n";
        }

        return $css;
    }

    /**
     * Adjust color brightness.
     *
     * @param string $hex    Hex color code.
     * @param int    $percent Percentage to adjust (-100 to 100).
     * @return string Adjusted hex color.
     */
    private static function adjust_color_brightness( $hex, $percent ) {
        // Remove # if present.
        $hex = ltrim( $hex, '#' );

        // Convert to RGB.
        $r = hexdec( substr( $hex, 0, 2 ) );
        $g = hexdec( substr( $hex, 2, 2 ) );
        $b = hexdec( substr( $hex, 4, 2 ) );

        // Adjust brightness.
        if ( $percent > 0 ) {
            // Lighten.
            $r = $r + ( 255 - $r ) * $percent / 100;
            $g = $g + ( 255 - $g ) * $percent / 100;
            $b = $b + ( 255 - $b ) * $percent / 100;
        } else {
            // Darken.
            $r = $r + $r * $percent / 100;
            $g = $g + $g * $percent / 100;
            $b = $b + $b * $percent / 100;
        }

        // Ensure values are within bounds.
        $r = max( 0, min( 255, round( $r ) ) );
        $g = max( 0, min( 255, round( $g ) ) );
        $b = max( 0, min( 255, round( $b ) ) );

        // Convert back to hex.
        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }

    /**
     * Get booking form HTML.
     *
     * @param int $service_id Service ID.
     * @return string
     */
    public static function get_booking_form( $service_id ) {
        $service = Sodek_GB_Service::get_service( $service_id );

        if ( ! $service ) {
            return '<p class="sodek-gb-error">' . esc_html__( 'Service not found.', 'glowbook' ) . '</p>';
        }

        ob_start();
        include SODEK_GB_PLUGIN_DIR . 'templates/booking-form.php';
        return ob_get_clean();
    }
}
