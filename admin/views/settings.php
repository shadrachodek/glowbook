<?php
/**
 * Settings page view.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

// Get current payment mode
$payment_mode = get_option( 'sodek_gb_payment_mode', 'woocommerce' );
$portal_page_id = (int) get_option( 'sodek_gb_portal_page_id' );
$booking_page_id = (int) get_option( 'sodek_gb_booking_page_id' );
$confirmation_page_id = (int) get_option( 'sodek_gb_confirmation_page_id' );
$sms_enabled = (bool) get_option( 'sodek_gb_sms_enabled', false );
$whatsapp_enabled = (bool) get_option( 'sodek_gb_whatsapp_enabled', false );
$email_layer_mode = function_exists( 'WC' ) ? __( 'WooCommerce-styled email layer available', 'glowbook' ) : __( 'GlowBook triggers active, but email wrapper still expects WooCommerce styling support', 'glowbook' );
$default_booking_prep_text = "I do not wash hair or prewash extensions.\n\nYou are welcome to bring your own human hair or braiding hair.\n\nPlease wear a mask. Avoid coloring your hair within 48 hours of your appointment, and avoid trimming before service.\n\nWalk-ins are welcome for quick 1 hour styles.";
$default_booking_terms_text = "If you need help with anything not covered here, please email hairbymedey@gmail.com or book a virtual consultation.\n\nBy completing your booking, you agree to the booking terms above. Deposits are non-refundable.\n\nThank you for taking the time to read through everything. I look forward to braiding your hair and making your experience amazing.";
?>
<div class="wrap sodek-gb-admin-wrap">
    <div class="sodek-gb-admin-hero sodek-gb-settings-hero">
        <div>
            <span class="sodek-gb-admin-kicker"><?php esc_html_e( 'Operations and Experience', 'glowbook' ); ?></span>
            <h1><?php esc_html_e( 'GlowBook Settings', 'glowbook' ); ?></h1>
            <p><?php esc_html_e( 'Control payments, routing, branding, reminders, portal behavior, and customer experience from one place.', 'glowbook' ); ?></p>
        </div>
        <div class="sodek-gb-admin-hero-note">
            <strong><?php esc_html_e( 'Release control center', 'glowbook' ); ?></strong>
            <span><?php esc_html_e( 'Use these settings to lock in the standalone flow, confirm payment rules, and verify the live customer journey before launch.', 'glowbook' ); ?></span>
        </div>
    </div>

    <div class="sodek-gb-settings-readiness">
        <div class="sodek-gb-settings-readiness-card">
            <span class="sodek-gb-settings-readiness-kicker"><?php esc_html_e( 'Customer Accounts', 'glowbook' ); ?></span>
            <strong><?php esc_html_e( 'Hybrid model active', 'glowbook' ); ?></strong>
            <p><?php esc_html_e( 'Customers can book as guests, return through portal verification, and optionally link to a WordPress account later.', 'glowbook' ); ?></p>
        </div>
        <div class="sodek-gb-settings-readiness-card">
            <span class="sodek-gb-settings-readiness-kicker"><?php esc_html_e( 'Notification Readiness', 'glowbook' ); ?></span>
            <strong><?php echo esc_html( $email_layer_mode ); ?></strong>
            <p>
                <?php
                printf(
                    /* translators: 1: SMS state, 2: WhatsApp state */
                    esc_html__( 'SMS is %1$s and WhatsApp is %2$s. Use this page to finish the final live QA matrix for booking, reminder, reschedule, cancellation, refund, and portal login.', 'glowbook' ),
                    $sms_enabled ? esc_html__( 'enabled', 'glowbook' ) : esc_html__( 'disabled', 'glowbook' ),
                    $whatsapp_enabled ? esc_html__( 'enabled', 'glowbook' ) : esc_html__( 'disabled', 'glowbook' )
                );
                ?>
            </p>
        </div>
        <div class="sodek-gb-settings-readiness-card">
            <span class="sodek-gb-settings-readiness-kicker"><?php esc_html_e( 'Portal Routing', 'glowbook' ); ?></span>
            <strong>
                <?php
                echo esc_html(
                    ( $portal_page_id && $booking_page_id && $confirmation_page_id )
                        ? __( 'Core customer pages configured', 'glowbook' )
                        : __( 'Some customer pages still need assignment', 'glowbook' )
                );
                ?>
            </strong>
            <p><?php esc_html_e( 'Booking, confirmation, and portal pages should all be assigned so customers can move cleanly across the standalone experience.', 'glowbook' ); ?></p>
        </div>
    </div>

    <form method="post" action="options.php" class="sodek-gb-settings-form">
        <?php settings_fields( 'sodek_gb_settings' ); ?>

        <section class="sodek-gb-settings-section">
        <div class="sodek-gb-settings-section-heading">
            <div>
                <h2 class="title"><?php esc_html_e( 'Payment Settings', 'glowbook' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Configure how customers pay for bookings.', 'glowbook' ); ?></p>
            </div>
        </div>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="sodek_gb_payment_mode"><?php esc_html_e( 'Payment Mode', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_payment_mode" name="sodek_gb_payment_mode">
                        <option value="woocommerce" <?php selected( $payment_mode, 'woocommerce' ); ?>><?php esc_html_e( 'WooCommerce Checkout', 'glowbook' ); ?></option>
                        <option value="standalone" <?php selected( $payment_mode, 'standalone' ); ?>><?php esc_html_e( 'Standalone (Square Direct)', 'glowbook' ); ?></option>
                    </select>
                    <p class="description">
                        <strong><?php esc_html_e( 'WooCommerce Checkout:', 'glowbook' ); ?></strong> <?php esc_html_e( 'Bookings are added to cart and paid through WooCommerce checkout. Supports all WooCommerce payment gateways.', 'glowbook' ); ?><br>
                        <strong><?php esc_html_e( 'Standalone:', 'glowbook' ); ?></strong> <?php esc_html_e( 'Customers pay directly on the booking page without going through WooCommerce cart/checkout. Requires Square.', 'glowbook' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_customer_payment_rules_enabled"><?php esc_html_e( 'Customer Payment Rules', 'glowbook' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="sodek_gb_customer_payment_rules_enabled" name="sodek_gb_customer_payment_rules_enabled" value="1" <?php checked( (int) get_option( 'sodek_gb_customer_payment_rules_enabled', 1 ), 1 ); ?>>
                        <?php esc_html_e( 'Use dedicated returning/new customer payment amounts on the standalone booking page', 'glowbook' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'When enabled, GlowBook uses fixed standalone booking-time payment rules for returning and new customers. When disabled, it falls back to the service deposit settings.', 'glowbook' ); ?>
                    </p>
                </td>
            </tr>
            <tr class="sodek-gb-customer-payment-rule-row">
                <th scope="row">
                    <label for="sodek_gb_enforce_customer_payment_type"><?php esc_html_e( 'Auto-Enforce Customer Type', 'glowbook' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="sodek_gb_enforce_customer_payment_type" name="sodek_gb_enforce_customer_payment_type" value="1" <?php checked( (int) get_option( 'sodek_gb_enforce_customer_payment_type', 0 ), 1 ); ?>>
                        <?php esc_html_e( 'Automatically decide returning vs new customer from booking history', 'glowbook' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Leave this off while building customer history. When off, customers can choose Returning Customer or New Customer themselves and staff can verify manually later.', 'glowbook' ); ?>
                    </p>
                </td>
            </tr>
            <tr class="sodek-gb-customer-payment-rule-row">
                <th scope="row">
                    <label for="sodek_gb_returning_customer_payment_amount"><?php esc_html_e( 'Returning Customer Amount', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="number" id="sodek_gb_returning_customer_payment_amount" name="sodek_gb_returning_customer_payment_amount" min="0" step="0.01" value="<?php echo esc_attr( get_option( 'sodek_gb_returning_customer_payment_amount', 50 ) ); ?>" class="small-text">
                    <p class="description"><?php esc_html_e( 'Fixed amount returning customers pay during standalone booking.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr class="sodek-gb-customer-payment-rule-row">
                <th scope="row">
                    <label for="sodek_gb_new_customer_payment_amount"><?php esc_html_e( 'New Customer Amount', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="number" id="sodek_gb_new_customer_payment_amount" name="sodek_gb_new_customer_payment_amount" min="0" step="0.01" value="<?php echo esc_attr( get_option( 'sodek_gb_new_customer_payment_amount', 150 ) ); ?>" class="small-text">
                    <p class="description"><?php esc_html_e( 'Fixed retainer amount new customers pay during standalone booking.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_booking_prep_text"><?php esc_html_e( 'Booking Prep Notice', 'glowbook' ); ?></label>
                </th>
                <td>
                    <textarea id="sodek_gb_booking_prep_text" name="sodek_gb_booking_prep_text" rows="6" cols="50" class="large-text"><?php echo esc_textarea( get_option( 'sodek_gb_booking_prep_text', $default_booking_prep_text ) ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Shown on the category and service steps to help customers prepare before choosing an appointment.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_booking_terms_text"><?php esc_html_e( 'Booking Notice / Terms', 'glowbook' ); ?></label>
                </th>
                <td>
                    <textarea id="sodek_gb_booking_terms_text" name="sodek_gb_booking_terms_text" rows="6" cols="50" class="large-text"><?php echo esc_textarea( get_option( 'sodek_gb_booking_terms_text', $default_booking_terms_text ) ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Displayed on the booking page before checkout so customers can review your inquiry, booking, and non-refundable payment terms.', 'glowbook' ); ?></p>
                </td>
            </tr>
        </table>
        </section>

        <div id="sodek-gb-square-settings" class="sodek-gb-payment-gateway-settings" <?php echo 'standalone' !== $payment_mode ? 'style="display:none;"' : ''; ?>>
            <h3><?php esc_html_e( 'Square Payment Gateway', 'glowbook' ); ?></h3>
            <p class="description">
                <?php
                printf(
                    /* translators: %s: Square Developer URL */
                    esc_html__( 'Get your Square credentials from the %s.', 'glowbook' ),
                    '<a href="https://developer.squareup.com/apps" target="_blank" rel="noopener">' . esc_html__( 'Square Developer Dashboard', 'glowbook' ) . '</a>'
                );
                ?>
            </p>

            <table class="form-table">
                <?php
                // Get the Square gateway and render its settings
                if ( class_exists( 'Sodek_GB_Square_Gateway' ) ) {
                    $square_gateway = new Sodek_GB_Square_Gateway();
                    $square_gateway->render_settings_fields();
                }
                ?>
            </table>
        </div>

        <script>
        jQuery(function($) {
            var $paymentMode = $('#sodek_gb_payment_mode');
            var $squareSettings = $('#sodek-gb-square-settings');
            var $environmentSelect = $('#sodek_gb_square_environment');
            var $customerPaymentRules = $('#sodek_gb_customer_payment_rules_enabled');

            function toggleCustomerPaymentRules() {
                $('.sodek-gb-customer-payment-rule-row').toggle($customerPaymentRules.is(':checked'));
            }

            // Toggle Square settings based on payment mode
            $paymentMode.on('change', function() {
                if ($(this).val() === 'standalone') {
                    $squareSettings.slideDown();
                } else {
                    $squareSettings.slideUp();
                }
            });

            // Toggle credentials based on environment
            $environmentSelect.on('change', function() {
                var env = $(this).val();
                if (env === 'sandbox') {
                    $('.sodek-gb-square-sandbox').show();
                    $('.sodek-gb-square-production').hide();
                } else {
                    $('.sodek-gb-square-sandbox').hide();
                    $('.sodek-gb-square-production').show();
                }
            });

            $customerPaymentRules.on('change', toggleCustomerPaymentRules);
            toggleCustomerPaymentRules();

            // Test connection button
            $('.sodek-gb-test-square-connection').on('click', function() {
                var $btn = $(this);
                var $status = $btn.siblings('.sodek-gb-connection-status');

                $btn.prop('disabled', true).text('<?php esc_html_e( 'Testing...', 'glowbook' ); ?>');
                $status.html('<span class="spinner is-active" style="float:none;margin:0;"></span>');

                $.post(ajaxurl, {
                    action: 'sodek_gb_test_square_connection',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'sodek_gb_admin_nonce' ) ); ?>'
                }, function(response) {
                    $btn.prop('disabled', false).text('<?php esc_html_e( 'Test Connection', 'glowbook' ); ?>');

                    if (response.success) {
                        $status.html('<span style="color:#00a32a;">&#10004; ' + response.data.message + '</span>');
                    } else {
                        $status.html('<span style="color:#d63638;">&#10006; ' + response.data.message + '</span>');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text('<?php esc_html_e( 'Test Connection', 'glowbook' ); ?>');
                    $status.html('<span style="color:#d63638;">&#10006; <?php esc_html_e( 'Request failed', 'glowbook' ); ?></span>');
                });
            });
        });
        </script>

        <div class="sodek-gb-settings-divider"></div>

        <section class="sodek-gb-settings-section">
        <div class="sodek-gb-settings-section-heading">
            <div>
                <h2 class="title"><?php esc_html_e( 'Page Settings', 'glowbook' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Select the pages where you have placed the GlowBook shortcodes. This allows proper redirects after booking.', 'glowbook' ); ?></p>
            </div>
        </div>

        <?php
        // Get all pages for dropdown
        $pages = get_pages( array( 'post_status' => 'publish' ) );
        ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="sodek_gb_confirmation_page_id"><?php esc_html_e( 'Confirmation Page', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_confirmation_page_id" name="sodek_gb_confirmation_page_id">
                        <option value=""><?php esc_html_e( '— Select Page —', 'glowbook' ); ?></option>
                        <?php foreach ( $pages as $page ) : ?>
                            <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( get_option( 'sodek_gb_confirmation_page_id' ), $page->ID ); ?>>
                                <?php echo esc_html( $page->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'Page containing the [glowbook_confirmation] shortcode. Customers are redirected here after booking.', 'glowbook' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_portal_page_id"><?php esc_html_e( 'Customer Portal Page', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_portal_page_id" name="sodek_gb_portal_page_id">
                        <option value=""><?php esc_html_e( '— Select Page —', 'glowbook' ); ?></option>
                        <?php foreach ( $pages as $page ) : ?>
                            <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( get_option( 'sodek_gb_portal_page_id' ), $page->ID ); ?>>
                                <?php echo esc_html( $page->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'Page containing the [glowbook_portal] shortcode. Customers can view and manage their bookings here.', 'glowbook' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_booking_page_id"><?php esc_html_e( 'Booking Page', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_booking_page_id" name="sodek_gb_booking_page_id">
                        <option value=""><?php esc_html_e( '— Select Page —', 'glowbook' ); ?></option>
                        <?php foreach ( $pages as $page ) : ?>
                            <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( get_option( 'sodek_gb_booking_page_id' ), $page->ID ); ?>>
                                <?php echo esc_html( $page->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'Page containing the [glowbook_booking] shortcode. This is your main booking page.', 'glowbook' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        </section>

        <div class="sodek-gb-settings-divider"></div>

        <section class="sodek-gb-settings-section">
        <div class="sodek-gb-settings-section-heading">
            <div>
                <h2 class="title"><?php esc_html_e( 'Appearance', 'glowbook' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Customize how the booking form looks on your website. The form will inherit your theme\'s fonts automatically.', 'glowbook' ); ?></p>
            </div>
        </div>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="sodek_gb_primary_color"><?php esc_html_e( 'Primary Color', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="color" id="sodek_gb_primary_color" name="sodek_gb_primary_color"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_primary_color', '#2271b1' ) ); ?>" class="sodek-gb-color-picker">
                    <input type="text" id="sodek_gb_primary_color_text"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_primary_color', '#2271b1' ) ); ?>"
                        class="small-text" style="width: 80px;">
                    <button type="button" class="button sodek-gb-reset-color" data-default="#2271b1"><?php esc_html_e( 'Reset', 'glowbook' ); ?></button>
                    <p class="description"><?php esc_html_e( 'Used for buttons, selected states, and accents.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_button_style"><?php esc_html_e( 'Button Style', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_button_style" name="sodek_gb_button_style">
                        <option value="filled" <?php selected( get_option( 'sodek_gb_button_style', 'filled' ), 'filled' ); ?>><?php esc_html_e( 'Filled', 'glowbook' ); ?></option>
                        <option value="outline" <?php selected( get_option( 'sodek_gb_button_style' ), 'outline' ); ?>><?php esc_html_e( 'Outline', 'glowbook' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Choose between filled or outline button style.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_border_radius"><?php esc_html_e( 'Border Radius', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_border_radius" name="sodek_gb_border_radius">
                        <option value="none" <?php selected( get_option( 'sodek_gb_border_radius' ), 'none' ); ?>><?php esc_html_e( 'None (Square)', 'glowbook' ); ?></option>
                        <option value="small" <?php selected( get_option( 'sodek_gb_border_radius' ), 'small' ); ?>><?php esc_html_e( 'Small (4px)', 'glowbook' ); ?></option>
                        <option value="medium" <?php selected( get_option( 'sodek_gb_border_radius', 'medium' ), 'medium' ); ?>><?php esc_html_e( 'Medium (8px)', 'glowbook' ); ?></option>
                        <option value="large" <?php selected( get_option( 'sodek_gb_border_radius' ), 'large' ); ?>><?php esc_html_e( 'Large (12px)', 'glowbook' ); ?></option>
                        <option value="rounded" <?php selected( get_option( 'sodek_gb_border_radius' ), 'rounded' ); ?>><?php esc_html_e( 'Rounded (24px)', 'glowbook' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Corner roundness for form elements and cards.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Theme Integration', 'glowbook' ); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="sodek_gb_inherit_theme_colors" value="1"
                                <?php checked( get_option( 'sodek_gb_inherit_theme_colors', 0 ), 1 ); ?>>
                            <?php esc_html_e( 'Try to inherit colors from theme (experimental)', 'glowbook' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'When enabled, the plugin will attempt to use your theme\'s link/accent color. May not work with all themes.', 'glowbook' ); ?></p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Preview', 'glowbook' ); ?></th>
                <td>
                    <div id="sodek-gb-style-preview" style="padding: 20px; background: #f6f7f7; border-radius: 8px; max-width: 400px;">
                        <p style="margin-top: 0;"><strong><?php esc_html_e( 'Sample Button:', 'glowbook' ); ?></strong></p>
                        <button type="button" id="sodek-gb-preview-btn" class="button" style="padding: 12px 24px; cursor: default;">
                            <?php esc_html_e( 'Book & Pay Deposit', 'glowbook' ); ?>
                        </button>
                        <p style="margin-top: 15px; margin-bottom: 5px;"><strong><?php esc_html_e( 'Sample Time Slot:', 'glowbook' ); ?></strong></p>
                        <div style="display: flex; gap: 8px;">
                            <span id="sodek-gb-preview-slot" style="padding: 10px 16px; border: 1px solid #c3c4c7; cursor: default;">9:00 AM</span>
                            <span id="sodek-gb-preview-slot-selected" style="padding: 10px 16px; cursor: default;">10:00 AM</span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        </section>

        <script>
        jQuery(function($) {
            var $colorPicker = $('#sodek_gb_primary_color');
            var $colorText = $('#sodek_gb_primary_color_text');
            var $buttonStyle = $('#sodek_gb_button_style');
            var $borderRadius = $('#sodek_gb_border_radius');
            var $previewBtn = $('#sodek-gb-preview-btn');
            var $previewSlot = $('#sodek-gb-preview-slot');
            var $previewSlotSelected = $('#sodek-gb-preview-slot-selected');

            function getRadiusValue() {
                var radiusMap = { 'none': '0', 'small': '4px', 'medium': '8px', 'large': '12px', 'rounded': '24px' };
                return radiusMap[$borderRadius.val()] || '8px';
            }

            function updatePreview() {
                var color = $colorPicker.val();
                var style = $buttonStyle.val();
                var radius = getRadiusValue();

                // Update button preview
                if (style === 'filled') {
                    $previewBtn.css({
                        'background-color': color,
                        'color': '#fff',
                        'border': '2px solid ' + color,
                        'border-radius': radius
                    });
                } else {
                    $previewBtn.css({
                        'background-color': 'transparent',
                        'color': color,
                        'border': '2px solid ' + color,
                        'border-radius': radius
                    });
                }

                // Update slot preview
                $previewSlot.css({ 'border-radius': radius });
                $previewSlotSelected.css({
                    'background-color': color,
                    'color': '#fff',
                    'border': '1px solid ' + color,
                    'border-radius': radius
                });
            }

            $colorPicker.on('input', function() {
                $colorText.val($(this).val());
                updatePreview();
            });

            $colorText.on('input', function() {
                var val = $(this).val();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    $colorPicker.val(val);
                    updatePreview();
                }
            });

            $buttonStyle.on('change', updatePreview);
            $borderRadius.on('change', updatePreview);

            $('.sodek-gb-reset-color').on('click', function() {
                var defaultColor = $(this).data('default');
                $colorPicker.val(defaultColor);
                $colorText.val(defaultColor);
                updatePreview();
            });

            updatePreview();
        });
        </script>

        <hr style="margin: 30px 0;">

        <h2 class="title"><?php esc_html_e( 'WooCommerce Button Text', 'glowbook' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Customize the "Add to Cart" button text for GlowBook services in WooCommerce.', 'glowbook' ); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="sodek_gb_button_text_type"><?php esc_html_e( 'Button Text', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_button_text_type" name="sodek_gb_button_text_type">
                        <option value="default" <?php selected( get_option( 'sodek_gb_button_text_type', 'default' ), 'default' ); ?>><?php esc_html_e( 'Default (Add to Cart)', 'glowbook' ); ?></option>
                        <option value="book_now" <?php selected( get_option( 'sodek_gb_button_text_type' ), 'book_now' ); ?>><?php esc_html_e( 'Book Now', 'glowbook' ); ?></option>
                        <option value="book_appointment" <?php selected( get_option( 'sodek_gb_button_text_type' ), 'book_appointment' ); ?>><?php esc_html_e( 'Book Appointment', 'glowbook' ); ?></option>
                        <option value="schedule" <?php selected( get_option( 'sodek_gb_button_text_type' ), 'schedule' ); ?>><?php esc_html_e( 'Schedule Now', 'glowbook' ); ?></option>
                        <option value="custom" <?php selected( get_option( 'sodek_gb_button_text_type' ), 'custom' ); ?>><?php esc_html_e( 'Custom Text', 'glowbook' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Choose the button text for GlowBook service products. You can override this per service.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr id="sodek_gb_button_text_custom_row" style="<?php echo 'custom' !== get_option( 'sodek_gb_button_text_type' ) ? 'display:none;' : ''; ?>">
                <th scope="row">
                    <label for="sodek_gb_button_text_custom"><?php esc_html_e( 'Custom Button Text', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="text" id="sodek_gb_button_text_custom" name="sodek_gb_button_text_custom"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_button_text_custom', '' ) ); ?>"
                        class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Reserve Your Spot', 'glowbook' ); ?>">
                </td>
            </tr>
        </table>

        <script>
        jQuery(function($) {
            $('#sodek_gb_button_text_type').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#sodek_gb_button_text_custom_row').show();
                } else {
                    $('#sodek_gb_button_text_custom_row').hide();
                }
            });
        });
        </script>

        <hr style="margin: 30px 0;">

        <h2 class="title"><?php esc_html_e( 'Display Settings', 'glowbook' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Control what information is displayed on the booking form.', 'glowbook' ); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Show Duration', 'glowbook' ); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="sodek_gb_show_duration" value="1"
                                <?php checked( get_option( 'sodek_gb_show_duration', 1 ), 1 ); ?>>
                            <?php esc_html_e( 'Display service duration on booking form', 'glowbook' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'When enabled, the service duration (e.g., "45 minutes") will be shown on the product page and booking form.', 'glowbook' ); ?></p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Product Gallery', 'glowbook' ); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="sodek_gb_use_theme_gallery" value="1"
                                <?php checked( get_option( 'sodek_gb_use_theme_gallery', 1 ), 1 ); ?>>
                            <?php esc_html_e( 'Use theme\'s default product gallery', 'glowbook' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'When enabled, bookable services will use your theme\'s product image and gallery styles instead of the custom GlowBook gallery.', 'glowbook' ); ?></p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Product Layout', 'glowbook' ); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="sodek_gb_fullwidth_layout" value="1"
                                <?php checked( get_option( 'sodek_gb_fullwidth_layout', 1 ), 1 ); ?>>
                            <?php esc_html_e( 'Use full-width booking layout', 'glowbook' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'When enabled, bookable services use a clean, centered layout with image on top and booking form below. Disabling this uses your theme\'s default 2-column product layout.', 'glowbook' ); ?></p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Service Cards', 'glowbook' ); ?></th>
                <td>
                    <fieldset>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" name="sodek_gb_show_service_image" value="1"
                                <?php checked( get_option( 'sodek_gb_show_service_image', 0 ), 1 ); ?>>
                            <?php esc_html_e( 'Show service images on booking page', 'glowbook' ); ?>
                        </label>
                        <label style="display: block;">
                            <input type="checkbox" name="sodek_gb_show_service_deposit" value="1"
                                <?php checked( get_option( 'sodek_gb_show_service_deposit', 0 ), 1 ); ?>>
                            <?php esc_html_e( 'Show deposit amount on service cards', 'glowbook' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Control what information is displayed on service cards in the standalone booking page.', 'glowbook' ); ?></p>
                    </fieldset>
                </td>
            </tr>
        </table>

        <hr style="margin: 30px 0;">

        <h2 class="title"><?php esc_html_e( 'Booking Rules', 'glowbook' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="sodek_gb_timezone"><?php esc_html_e( 'Business Timezone', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_timezone" name="sodek_gb_timezone" style="min-width: 300px;">
                        <?php
                        $current_tz = get_option( 'sodek_gb_timezone', wp_timezone_string() );
                        echo wp_timezone_choice( $current_tz, get_user_locale() );
                        ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'Select your business timezone. All booking times will be displayed and stored in this timezone.', 'glowbook' ); ?>
                        <br>
                        <?php
                        printf(
                            /* translators: %s: WordPress timezone */
                            esc_html__( 'WordPress site timezone: %s', 'glowbook' ),
                            '<code>' . esc_html( wp_timezone_string() ) . '</code>'
                        );
                        ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_time_slot_interval"><?php esc_html_e( 'Time Slot Interval', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_time_slot_interval" name="sodek_gb_time_slot_interval">
                        <option value="15" <?php selected( get_option( 'sodek_gb_time_slot_interval' ), 15 ); ?>>15 <?php esc_html_e( 'minutes', 'glowbook' ); ?></option>
                        <option value="30" <?php selected( get_option( 'sodek_gb_time_slot_interval' ), 30 ); ?>>30 <?php esc_html_e( 'minutes', 'glowbook' ); ?></option>
                        <option value="60" <?php selected( get_option( 'sodek_gb_time_slot_interval' ), 60 ); ?>>1 <?php esc_html_e( 'hour', 'glowbook' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'How often time slots are shown in the booking form.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_min_booking_notice"><?php esc_html_e( 'Minimum Booking Notice', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="number" id="sodek_gb_min_booking_notice" name="sodek_gb_min_booking_notice"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_min_booking_notice', 24 ) ); ?>" min="0" step="1" class="small-text">
                    <?php esc_html_e( 'hours', 'glowbook' ); ?>
                    <p class="description"><?php esc_html_e( 'How far in advance customers must book.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_max_booking_advance"><?php esc_html_e( 'Maximum Booking Advance', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="number" id="sodek_gb_max_booking_advance" name="sodek_gb_max_booking_advance"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_max_booking_advance', 60 ) ); ?>" min="1" step="1" class="small-text">
                    <?php esc_html_e( 'days', 'glowbook' ); ?>
                    <p class="description"><?php esc_html_e( 'How far into the future customers can book.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_buffer_before"><?php esc_html_e( 'Default Buffer Before', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="number" id="sodek_gb_buffer_before" name="sodek_gb_buffer_before"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_buffer_before', 0 ) ); ?>" min="0" step="5" class="small-text">
                    <?php esc_html_e( 'minutes', 'glowbook' ); ?>
                    <p class="description"><?php esc_html_e( 'Default preparation time before appointments.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_buffer_after"><?php esc_html_e( 'Default Buffer After', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="number" id="sodek_gb_buffer_after" name="sodek_gb_buffer_after"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_buffer_after', 15 ) ); ?>" min="0" step="5" class="small-text">
                    <?php esc_html_e( 'minutes', 'glowbook' ); ?>
                    <p class="description"><?php esc_html_e( 'Default cleanup time after appointments.', 'glowbook' ); ?></p>
                </td>
            </tr>
        </table>

        <h2 class="title"><?php esc_html_e( 'Deposit Settings', 'glowbook' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="sodek_gb_default_deposit_type"><?php esc_html_e( 'Default Deposit Type', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_default_deposit_type" name="sodek_gb_default_deposit_type">
                        <option value="percentage" <?php selected( get_option( 'sodek_gb_default_deposit_type' ), 'percentage' ); ?>><?php esc_html_e( 'Percentage', 'glowbook' ); ?></option>
                        <option value="fixed" <?php selected( get_option( 'sodek_gb_default_deposit_type' ), 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount', 'glowbook' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_default_deposit_value"><?php esc_html_e( 'Default Deposit Value', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="number" id="sodek_gb_default_deposit_value" name="sodek_gb_default_deposit_value"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_default_deposit_value', 50 ) ); ?>" min="0" step="0.01" class="small-text">
                    <p class="description"><?php esc_html_e( 'Default deposit amount for new services.', 'glowbook' ); ?></p>
                </td>
            </tr>
        </table>

        <h2 class="title"><?php esc_html_e( 'Reminder Settings', 'glowbook' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Email Reminders', 'glowbook' ); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="sodek_gb_reminder_24h_enabled" value="1"
                                <?php checked( get_option( 'sodek_gb_reminder_24h_enabled', 1 ), 1 ); ?>>
                            <?php esc_html_e( 'Send reminder 24 hours before appointment', 'glowbook' ); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="sodek_gb_reminder_2h_enabled" value="1"
                                <?php checked( get_option( 'sodek_gb_reminder_2h_enabled', 1 ), 1 ); ?>>
                            <?php esc_html_e( 'Send reminder 2 hours before appointment', 'glowbook' ); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>

        <h2 class="title"><?php esc_html_e( 'WhatsApp Notifications', 'glowbook' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="sodek_gb_whatsapp_enabled"><?php esc_html_e( 'Enable WhatsApp Notifications', 'glowbook' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="sodek_gb_whatsapp_enabled" name="sodek_gb_whatsapp_enabled" value="1"
                            <?php checked( get_option( 'sodek_gb_whatsapp_enabled' ), 1 ); ?>>
                        <?php esc_html_e( 'Send booking notifications to WhatsApp Business', 'glowbook' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_whatsapp_number"><?php esc_html_e( 'WhatsApp Business Number', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="tel" id="sodek_gb_whatsapp_number" name="sodek_gb_whatsapp_number"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_whatsapp_number', '' ) ); ?>"
                        class="regular-text" placeholder="+1234567890">
                    <p class="description"><?php esc_html_e( 'Enter the WhatsApp Business phone number with country code (e.g., +1234567890). Notifications will be sent to this number when new bookings are made.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Notify On', 'glowbook' ); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="sodek_gb_whatsapp_notify_new" value="1"
                                <?php checked( get_option( 'sodek_gb_whatsapp_notify_new', 1 ), 1 ); ?>>
                            <?php esc_html_e( 'New booking', 'glowbook' ); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="sodek_gb_whatsapp_notify_cancelled" value="1"
                                <?php checked( get_option( 'sodek_gb_whatsapp_notify_cancelled', 1 ), 1 ); ?>>
                            <?php esc_html_e( 'Booking cancelled', 'glowbook' ); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="sodek_gb_whatsapp_notify_rescheduled" value="1"
                                <?php checked( get_option( 'sodek_gb_whatsapp_notify_rescheduled', 1 ), 1 ); ?>>
                            <?php esc_html_e( 'Booking rescheduled', 'glowbook' ); ?>
                        </label>
                    </fieldset>
                    <p class="description"><?php esc_html_e( 'Select which booking events should trigger a WhatsApp notification.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_whatsapp_callmebot_key"><?php esc_html_e( 'CallMeBot API Key', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="text" id="sodek_gb_whatsapp_callmebot_key" name="sodek_gb_whatsapp_callmebot_key"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_whatsapp_callmebot_key', '' ) ); ?>"
                        class="regular-text" placeholder="123456">
                    <p class="description">
                        <?php esc_html_e( 'To get your API key:', 'glowbook' ); ?><br>
                        1. <?php esc_html_e( 'Save this number to your phone:', 'glowbook' ); ?> <code>+34 644 71 81 99</code><br>
                        2. <?php esc_html_e( 'Send this message via WhatsApp:', 'glowbook' ); ?> <code>I allow callmebot to send me messages</code><br>
                        3. <?php esc_html_e( 'Enter the API key you receive here.', 'glowbook' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                    <h3 style="margin: 0;"><?php esc_html_e( 'Message Templates', 'glowbook' ); ?></h3>
                    <p class="description" style="font-weight: normal;">
                        <?php esc_html_e( 'Customize the WhatsApp notification messages. Available placeholders:', 'glowbook' ); ?><br>
                        <code>{customer_name}</code>, <code>{customer_email}</code>, <code>{customer_phone}</code>, <code>{service}</code>, <code>{date}</code>, <code>{time}</code>, <code>{deposit}</code>, <code>{total}</code>, <code>{notes}</code>, <code>{site_name}</code><br>
                        <?php esc_html_e( 'For cancelled:', 'glowbook' ); ?> <code>{reason}</code> |
                        <?php esc_html_e( 'For rescheduled:', 'glowbook' ); ?> <code>{old_date}</code>, <code>{old_time}</code>
                    </p>
                </th>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_whatsapp_msg_new"><?php esc_html_e( 'New Booking Message', 'glowbook' ); ?></label>
                </th>
                <td>
                    <?php
                    $default_new = "🆕 *NEW BOOKING*\n\n📅 *Date:* {date}\n🕐 *Time:* {time}\n💇 *Service:* {service}\n👤 *Customer:* {customer_name}\n📧 *Email:* {customer_email}\n📱 *Phone:* {customer_phone}\n💰 *Deposit:* {deposit}\n📝 *Notes:* {notes}\n\n_{site_name}_";
                    ?>
                    <textarea id="sodek_gb_whatsapp_msg_new" name="sodek_gb_whatsapp_msg_new" rows="8" cols="50" class="large-text"><?php echo esc_textarea( get_option( 'sodek_gb_whatsapp_msg_new', $default_new ) ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_whatsapp_msg_cancelled"><?php esc_html_e( 'Cancelled Booking Message', 'glowbook' ); ?></label>
                </th>
                <td>
                    <?php
                    $default_cancelled = "❌ *BOOKING CANCELLED*\n\n📅 *Date:* {date}\n🕐 *Time:* {time}\n💇 *Service:* {service}\n👤 *Customer:* {customer_name}\n📱 *Phone:* {customer_phone}\n\n_{site_name}_";
                    ?>
                    <textarea id="sodek_gb_whatsapp_msg_cancelled" name="sodek_gb_whatsapp_msg_cancelled" rows="6" cols="50" class="large-text"><?php echo esc_textarea( get_option( 'sodek_gb_whatsapp_msg_cancelled', $default_cancelled ) ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_whatsapp_msg_rescheduled"><?php esc_html_e( 'Rescheduled Booking Message', 'glowbook' ); ?></label>
                </th>
                <td>
                    <?php
                    $default_rescheduled = "🔄 *BOOKING RESCHEDULED*\n\n💇 *Service:* {service}\n👤 *Customer:* {customer_name}\n📱 *Phone:* {customer_phone}\n\n📅 *New Date:* {date}\n🕐 *New Time:* {time}\n\n_{site_name}_";
                    ?>
                    <textarea id="sodek_gb_whatsapp_msg_rescheduled" name="sodek_gb_whatsapp_msg_rescheduled" rows="6" cols="50" class="large-text"><?php echo esc_textarea( get_option( 'sodek_gb_whatsapp_msg_rescheduled', $default_rescheduled ) ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Test Notifications', 'glowbook' ); ?></th>
                <td>
                    <div class="sodek-gb-whatsapp-test">
                        <button type="button" class="button sodek-gb-send-whatsapp-test" data-type="new">
                            <?php esc_html_e( 'Send New Booking Test', 'glowbook' ); ?>
                        </button>
                        <button type="button" class="button sodek-gb-send-whatsapp-test" data-type="cancelled">
                            <?php esc_html_e( 'Send Cancellation Test', 'glowbook' ); ?>
                        </button>
                        <button type="button" class="button sodek-gb-send-whatsapp-test" data-type="rescheduled">
                            <?php esc_html_e( 'Send Reschedule Test', 'glowbook' ); ?>
                        </button>
                    </div>
                    <p class="description"><?php esc_html_e( 'Send test notifications to the WhatsApp number above. Save settings first if you changed the number.', 'glowbook' ); ?></p>
                    <div id="sodek-gb-whatsapp-test-result" style="margin-top: 10px; display: none;"></div>
                </td>
            </tr>
        </table>

        <script>
        jQuery(function($) {
            $('.sodek-gb-send-whatsapp-test').on('click', function() {
                var $btn = $(this);
                var type = $btn.data('type');
                var $result = $('#sodek-gb-whatsapp-test-result');
                var phone = $('#sodek_gb_whatsapp_number').val();

                if (!phone) {
                    $result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Please enter a WhatsApp number first.', 'glowbook' ); ?></p></div>').show();
                    return;
                }

                $btn.prop('disabled', true).text('<?php esc_html_e( 'Sending...', 'glowbook' ); ?>');
                $result.hide();

                $.post(ajaxurl, {
                    action: 'sodek_gb_send_whatsapp_test',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'sodek_gb_whatsapp_test' ) ); ?>',
                    type: type,
                    phone: phone
                }, function(response) {
                    $btn.prop('disabled', false);

                    if (type === 'new') {
                        $btn.text('<?php esc_html_e( 'Send New Booking Test', 'glowbook' ); ?>');
                    } else if (type === 'cancelled') {
                        $btn.text('<?php esc_html_e( 'Send Cancellation Test', 'glowbook' ); ?>');
                    } else {
                        $btn.text('<?php esc_html_e( 'Send Reschedule Test', 'glowbook' ); ?>');
                    }

                    if (response.success) {
                        var html = '<div class="notice notice-success inline"><p>' + response.data.message + '</p>';
                        if (response.data.link) {
                            html += '<p><a href="' + response.data.link + '" target="_blank" class="button button-primary"><?php esc_html_e( 'Open WhatsApp', 'glowbook' ); ?></a></p>';
                        }
                        if (response.data.preview) {
                            html += '<details style="margin-top:10px;"><summary><?php esc_html_e( 'Message Preview', 'glowbook' ); ?></summary><pre style="background:#f5f5f5;padding:10px;white-space:pre-wrap;margin-top:5px;">' + response.data.preview + '</pre></details>';
                        }
                        html += '</div>';
                        $result.html(html).show();
                    } else {
                        $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>').show();
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).text($btn.data('original-text'));
                    $result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Request failed. Please try again.', 'glowbook' ); ?></p></div>').show();
                });
            });
        });
        </script>

        <h2 class="title"><?php esc_html_e( 'Cancellation Policy', 'glowbook' ); ?></h2>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="sodek_gb_cancellation_notice"><?php esc_html_e( 'Cancellation Notice Required', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="number" id="sodek_gb_cancellation_notice" name="sodek_gb_cancellation_notice"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_cancellation_notice', 24 ) ); ?>" min="0" step="1" class="small-text">
                    <?php esc_html_e( 'hours', 'glowbook' ); ?>
                    <p class="description"><?php esc_html_e( 'How many hours before appointment customers can cancel/reschedule.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_cancellation_refund_policy"><?php esc_html_e( 'Refund Policy (Within Notice Period)', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_cancellation_refund_policy" name="sodek_gb_cancellation_refund_policy">
                        <option value="full" <?php selected( get_option( 'sodek_gb_cancellation_refund_policy' ), 'full' ); ?>><?php esc_html_e( 'Full refund of deposit', 'glowbook' ); ?></option>
                        <option value="partial" <?php selected( get_option( 'sodek_gb_cancellation_refund_policy' ), 'partial' ); ?>><?php esc_html_e( 'Partial refund', 'glowbook' ); ?></option>
                        <option value="credit" <?php selected( get_option( 'sodek_gb_cancellation_refund_policy' ), 'credit' ); ?>><?php esc_html_e( 'Store credit only', 'glowbook' ); ?></option>
                        <option value="none" <?php selected( get_option( 'sodek_gb_cancellation_refund_policy' ), 'none' ); ?>><?php esc_html_e( 'No refund', 'glowbook' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Refund policy when customer cancels within the required notice period.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr id="sodek_gb_partial_refund_row" style="<?php echo 'partial' !== get_option( 'sodek_gb_cancellation_refund_policy' ) ? 'display:none;' : ''; ?>">
                <th scope="row">
                    <label for="sodek_gb_partial_refund_percent"><?php esc_html_e( 'Partial Refund Amount', 'glowbook' ); ?></label>
                </th>
                <td>
                    <input type="number" id="sodek_gb_partial_refund_percent" name="sodek_gb_partial_refund_percent"
                        value="<?php echo esc_attr( get_option( 'sodek_gb_partial_refund_percent', 50 ) ); ?>" min="0" max="100" step="5" class="small-text">
                    <?php esc_html_e( '% of deposit', 'glowbook' ); ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_late_cancellation_policy"><?php esc_html_e( 'Late Cancellation Policy', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_late_cancellation_policy" name="sodek_gb_late_cancellation_policy">
                        <option value="no_refund" <?php selected( get_option( 'sodek_gb_late_cancellation_policy', 'no_refund' ), 'no_refund' ); ?>><?php esc_html_e( 'No refund (deposit forfeited)', 'glowbook' ); ?></option>
                        <option value="credit" <?php selected( get_option( 'sodek_gb_late_cancellation_policy' ), 'credit' ); ?>><?php esc_html_e( 'Store credit only', 'glowbook' ); ?></option>
                        <option value="allow_once" <?php selected( get_option( 'sodek_gb_late_cancellation_policy' ), 'allow_once' ); ?>><?php esc_html_e( 'Allow one late cancellation with credit', 'glowbook' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'Policy when customer cancels after the notice period has passed.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_noshow_policy"><?php esc_html_e( 'No-Show Policy', 'glowbook' ); ?></label>
                </th>
                <td>
                    <select id="sodek_gb_noshow_policy" name="sodek_gb_noshow_policy">
                        <option value="forfeit" <?php selected( get_option( 'sodek_gb_noshow_policy', 'forfeit' ), 'forfeit' ); ?>><?php esc_html_e( 'Deposit forfeited', 'glowbook' ); ?></option>
                        <option value="charge_full" <?php selected( get_option( 'sodek_gb_noshow_policy' ), 'charge_full' ); ?>><?php esc_html_e( 'Charge full service amount (if card on file)', 'glowbook' ); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e( 'How to handle no-show customers.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_allow_customer_cancel"><?php esc_html_e( 'Allow Customer Self-Cancel', 'glowbook' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="sodek_gb_allow_customer_cancel" name="sodek_gb_allow_customer_cancel" value="1"
                            <?php checked( get_option( 'sodek_gb_allow_customer_cancel', 1 ), 1 ); ?>>
                        <?php esc_html_e( 'Allow customers to cancel their own bookings from My Account', 'glowbook' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_allow_customer_reschedule"><?php esc_html_e( 'Allow Customer Self-Reschedule', 'glowbook' ); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="sodek_gb_allow_customer_reschedule" name="sodek_gb_allow_customer_reschedule" value="1"
                            <?php checked( get_option( 'sodek_gb_allow_customer_reschedule', 1 ), 1 ); ?>>
                        <?php esc_html_e( 'Allow customers to reschedule their own bookings from My Account', 'glowbook' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sodek_gb_cancellation_policy_text"><?php esc_html_e( 'Cancellation Policy Text', 'glowbook' ); ?></label>
                </th>
                <td>
                    <textarea id="sodek_gb_cancellation_policy_text" name="sodek_gb_cancellation_policy_text" rows="4" cols="50" class="large-text"><?php echo esc_textarea( get_option( 'sodek_gb_cancellation_policy_text', '' ) ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'This text will be displayed to customers during booking. Leave blank to auto-generate from settings.', 'glowbook' ); ?></p>
                </td>
            </tr>
        </table>

        <script>
        jQuery(function($) {
            $('#sodek_gb_cancellation_refund_policy').on('change', function() {
                if ($(this).val() === 'partial') {
                    $('#sodek_gb_partial_refund_row').show();
                } else {
                    $('#sodek_gb_partial_refund_row').hide();
                }
            });
        });
        </script>

        <?php
        // Google Calendar Integration settings
        Sodek_GB_Google_Calendar::render_settings();
        ?>

        <div class="sodek-gb-settings-submit">
            <?php submit_button( __( 'Save GlowBook Settings', 'glowbook' ) ); ?>
        </div>
    </form>
</div>

<style>
.sodek-gb-admin-wrap .sodek-gb-admin-hero,
.sodek-gb-admin-wrap .sodek-gb-settings-section,
.sodek-gb-settings-readiness-card,
.sodek-gb-payment-gateway-settings {
    background: #fff;
    border: 1px solid #dde3ea;
    border-radius: 22px;
    box-shadow: 0 18px 36px rgba(16, 24, 40, 0.05);
}
.sodek-gb-settings-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, .42fr);
    gap: 22px;
    padding: 28px 30px;
    margin: 18px 0 20px;
    background: linear-gradient(135deg, #fffaf5 0%, #f7efe6 100%);
    border-color: #eadfce;
}
.sodek-gb-admin-kicker {
    display: inline-flex;
    margin-bottom: 10px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: #8a5a21;
}
.sodek-gb-settings-hero h1 {
    margin: 0 0 8px;
    font-size: 34px;
    line-height: 1.08;
}
.sodek-gb-settings-hero p {
    margin: 0;
    color: #667085;
    line-height: 1.7;
}
.sodek-gb-admin-hero-note {
    display: grid;
    gap: 8px;
    align-self: end;
    padding: 18px 20px;
    background: rgba(255,255,255,.84);
    border: 1px solid rgba(182,120,49,.16);
    border-radius: 18px;
}
.sodek-gb-settings-readiness {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 20px;
}
.sodek-gb-settings-readiness-card {
    padding: 20px 22px;
}
.sodek-gb-settings-readiness-kicker {
    display: inline-flex;
    margin-bottom: 10px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: #8a5a21;
}
.sodek-gb-settings-readiness-card strong {
    display: block;
    margin-bottom: 8px;
    font-size: 18px;
}
.sodek-gb-settings-readiness-card p {
    margin: 0;
    color: #667085;
    line-height: 1.7;
}
.sodek-gb-settings-form {
    display: grid;
    gap: 20px;
}
.sodek-gb-settings-section,
.sodek-gb-payment-gateway-settings {
    padding: 24px 26px;
}
.sodek-gb-settings-section-heading {
    margin-bottom: 12px;
}
.sodek-gb-settings-section-heading h2,
.sodek-gb-payment-gateway-settings h3 {
    margin: 0 0 6px;
    font-size: 24px;
}
.sodek-gb-settings-section-heading p,
.sodek-gb-payment-gateway-settings .description {
    margin: 0;
    color: #667085;
    line-height: 1.7;
}
.sodek-gb-settings-section .form-table th,
.sodek-gb-payment-gateway-settings .form-table th {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #8a5a21;
}
.sodek-gb-settings-section .form-table td p.description,
.sodek-gb-payment-gateway-settings .form-table td p.description {
    color: #667085;
    line-height: 1.65;
}
.sodek-gb-settings-divider {
    height: 1px;
    background: linear-gradient(90deg, rgba(222,227,234,0) 0%, rgba(222,227,234,1) 15%, rgba(222,227,234,1) 85%, rgba(222,227,234,0) 100%);
    margin: 2px 0;
}
.sodek-gb-settings-submit {
    padding-bottom: 8px;
}
@media screen and (max-width: 1200px) {
    .sodek-gb-settings-readiness {
        grid-template-columns: 1fr;
    }
    .sodek-gb-settings-hero {
        grid-template-columns: 1fr;
    }
}
</style>

<style>
.sodek-gb-settings-readiness {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin: 18px 0 26px;
}
.sodek-gb-settings-readiness-card {
    padding: 18px 20px;
    background: #fff;
    border: 1px solid #dde3ea;
    border-radius: 18px;
    box-shadow: 0 12px 28px rgba(16, 24, 40, 0.04);
}
.sodek-gb-settings-readiness-kicker {
    display: inline-flex;
    margin-bottom: 12px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #8a5a21;
}
.sodek-gb-settings-readiness-card strong {
    display: block;
    margin-bottom: 10px;
    font-size: 18px;
    line-height: 1.3;
}
.sodek-gb-settings-readiness-card p {
    margin: 0;
    color: #667085;
    line-height: 1.65;
}
@media screen and (max-width: 960px) {
    .sodek-gb-settings-readiness {
        grid-template-columns: 1fr;
    }
}
</style>
