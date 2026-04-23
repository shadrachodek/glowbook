<?php
/**
 * Standalone Booking Page Template.
 *
 * Simple booking flow: Category -> Service -> Date & Time (with add-ons)
 *
 * @package GlowBook
 * @since   2.0.0
 *
 * @var array  $services_grouped Services grouped by category.
 * @var array  $all_staff        All active staff members.
 * @var array  $preselected      Pre-selected values from URL.
 * @var array  $customer         Current customer profile (if logged in).
 * @var array  $payment_config   Payment gateway configuration.
 * @var array  $settings         Plugin settings.
 * @var string $nonce            Security nonce.
 */

defined( 'ABSPATH' ) || exit;

$default_booking_prep_text = "I do not wash hair or prewash extensions.\n\nYou are welcome to bring your own human hair or braiding hair.\n\nPlease wear a mask. Avoid coloring your hair within 48 hours of your appointment, and avoid trimming before service.\n\nWalk-ins are welcome for quick 1 hour styles.";
$default_booking_terms_text = "If you need help with anything not covered here, please email hairbymedey@gmail.com or book a virtual consultation.\n\nBy completing your booking, you agree to the booking terms above. Deposits are non-refundable.\n\nThank you for taking the time to read through everything. I look forward to braiding your hair and making your experience amazing.";
$booking_prep_text  = trim( (string) get_option( 'sodek_gb_booking_prep_text', $default_booking_prep_text ) );
$booking_terms_text = trim( (string) get_option( 'sodek_gb_booking_terms_text', $default_booking_terms_text ) );
?>

<div class="sodek-gb-booking alignwide loading" data-nonce="<?php echo esc_attr( $nonce ); ?>">

    <!-- Step 1: Select Category -->
    <div class="sodek-gb-step sodek-gb-step-category active" data-step="category">
        <div class="sodek-gb-step-header">
            <h2 class="sodek-gb-step-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <?php esc_html_e( 'Select Category', 'glowbook' ); ?>
            </h2>
        </div>

        <?php if ( '' !== $booking_prep_text ) : ?>
            <div class="sodek-gb-booking-notice sodek-gb-booking-notice-prep">
                <span class="sodek-gb-booking-notice-kicker"><?php esc_html_e( 'Before your appointment', 'glowbook' ); ?></span>
                <div class="sodek-gb-booking-notice-copy"><?php echo wp_kses_post( wpautop( esc_html( $booking_prep_text ) ) ); ?></div>
            </div>
        <?php endif; ?>

        <div class="sodek-gb-category-list">
            <?php if ( ! empty( $services_grouped ) ) : ?>
                <?php foreach ( $services_grouped as $group ) :
                    $category = $group['category'];
                    $category_id = isset( $category['id'] ) ? $category['id'] : 0;
                    $category_slug = isset( $category['slug'] ) ? $category['slug'] : sanitize_title( $category['name'] );
                    $category_name = isset( $category['name'] ) ? $category['name'] : __( 'General Services', 'glowbook' );
                    $category_description = isset( $category['description'] ) ? $category['description'] : '';
                    if ( $category_slug === 'uncategorized' || empty( $category_name ) ) {
                        $category_name = __( 'Other Services', 'glowbook' );
                    }
                ?>
                    <div class="sodek-gb-category-row"
                         data-category-id="<?php echo esc_attr( $category_id ); ?>"
                         data-category-slug="<?php echo esc_attr( $category_slug ); ?>">
                        <div class="sodek-gb-category-info">
                            <span class="sodek-gb-category-name"><?php echo esc_html( $category_name ); ?></span>
                            <?php if ( ! empty( $category_description ) ) : ?>
                                <span class="sodek-gb-category-description"><?php echo esc_html( $category_description ); ?></span>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="sodek-gb-btn sodek-gb-btn-select">
                            <?php esc_html_e( 'SELECT', 'glowbook' ); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="sodek-gb-no-items"><?php esc_html_e( 'No categories available.', 'glowbook' ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Step 2: Select Appointment (Service) -->
    <div class="sodek-gb-step sodek-gb-step-service" data-step="service">
        <div class="sodek-gb-step-header">
            <button type="button" class="sodek-gb-back-link" data-back="category">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <?php esc_html_e( 'SELECT CATEGORY', 'glowbook' ); ?>
            </button>
            <h2 class="sodek-gb-step-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <?php esc_html_e( 'Select Appointment', 'glowbook' ); ?>
            </h2>
        </div>

        <div class="sodek-gb-service-loading" aria-live="polite" aria-busy="true">
            <span class="sodek-gb-service-loading-kicker"><?php esc_html_e( 'Preparing your options', 'glowbook' ); ?></span>
            <strong class="sodek-gb-service-loading-title"><?php esc_html_e( 'Loading services for this category', 'glowbook' ); ?></strong>
            <span class="sodek-gb-service-loading-copy"><?php esc_html_e( 'We’re pulling together the appointments that match your selection.', 'glowbook' ); ?></span>
        </div>

        <div class="sodek-gb-selected-category-name"></div>

        <?php if ( '' !== $booking_prep_text ) : ?>
            <div class="sodek-gb-booking-notice sodek-gb-booking-notice-prep sodek-gb-booking-notice-compact">
                <span class="sodek-gb-booking-notice-kicker"><?php esc_html_e( 'Service prep', 'glowbook' ); ?></span>
                <div class="sodek-gb-booking-notice-copy"><?php echo wp_kses_post( wpautop( esc_html( $booking_prep_text ) ) ); ?></div>
            </div>
        <?php endif; ?>

        <?php
        $global_show_image   = get_option( 'sodek_gb_show_service_image', 0 );
        $global_show_deposit = get_option( 'sodek_gb_show_service_deposit', 0 );
        ?>
        <div class="sodek-gb-service-list">
            <?php foreach ( $services_grouped as $group ) :
                $category = $group['category'];
                $category_id = isset( $category['id'] ) ? $category['id'] : 0;
                $services = $group['services'];
                foreach ( $services as $service ) :
                    $service_slug = ! empty( $service['slug'] ) ? $service['slug'] : sanitize_title( $service['title'] );
                    $price = isset( $service['price'] ) ? floatval( $service['price'] ) : 0;
                    $duration = isset( $service['duration'] ) ? intval( $service['duration'] ) : 60;
                    $description = isset( $service['description'] ) ? $service['description'] : '';
                    $thumbnail = isset( $service['thumbnail'] ) ? $service['thumbnail'] : '';
                    $deposit_type   = isset( $service['deposit_type'] ) ? $service['deposit_type'] : 'fixed';
                    $deposit_value  = isset( $service['deposit_value'] ) ? floatval( $service['deposit_value'] ) : 0;
                    $deposit_amount = isset( $service['deposit_amount'] ) ? floatval( $service['deposit_amount'] ) : $price;

                    // Determine show/hide for image (per-service override or global)
                    $image_override = isset( $service['show_image_override'] ) ? $service['show_image_override'] : 'global';
                    if ( 'show' === $image_override ) {
                        $show_image = true;
                    } elseif ( 'hide' === $image_override ) {
                        $show_image = false;
                    } else {
                        $show_image = (bool) $global_show_image;
                    }

                    // Determine show/hide for deposit (per-service override or global)
                    $deposit_override = isset( $service['show_deposit_override'] ) ? $service['show_deposit_override'] : 'global';
                    if ( 'show' === $deposit_override ) {
                        $show_deposit = true;
                    } elseif ( 'hide' === $deposit_override ) {
                        $show_deposit = false;
                    } else {
                        $show_deposit = (bool) $global_show_deposit;
                    }
            ?>
                <div class="sodek-gb-service-row"
                     data-service-id="<?php echo esc_attr( $service['id'] ); ?>"
                     data-service-slug="<?php echo esc_attr( $service_slug ); ?>"
                     data-category-id="<?php echo esc_attr( $category_id ); ?>"
                     data-category-slug="<?php echo esc_attr( $category_slug ); ?>"
                     data-price="<?php echo esc_attr( $price ); ?>"
                     data-duration="<?php echo esc_attr( $duration ); ?>"
                     data-deposit-type="<?php echo esc_attr( $deposit_type ); ?>"
                     data-deposit-value="<?php echo esc_attr( $deposit_value ); ?>"
                     data-deposit-amount="<?php echo esc_attr( $deposit_amount ); ?>"
                     data-description="<?php echo esc_attr( wp_strip_all_tags( $description ) ); ?>">

                    <?php if ( $show_image && $thumbnail ) :
                        $thumbnail_full = isset( $service['thumbnail_full'] ) ? $service['thumbnail_full'] : $thumbnail;
                    ?>
                        <div class="sodek-gb-service-thumb sodek-gb-lightbox-trigger"
                             data-lightbox-src="<?php echo esc_url( $thumbnail_full ); ?>"
                             data-lightbox-title="<?php echo esc_attr( $service['title'] ); ?>">
                            <img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $service['title'] ); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="sodek-gb-service-info">
                        <h4 class="sodek-gb-service-name"><?php echo esc_html( $service['title'] ); ?></h4>
                        <div class="sodek-gb-service-pricing<?php echo $price <= 0 ? ' is-empty' : ''; ?>">
                            <?php if ( $price > 0 ) : ?>
                                <span class="sodek-gb-service-price"><?php echo '$' . number_format( $price, 2 ); ?></span>
                            <?php endif; ?>
                            <?php if ( $show_deposit && $deposit_amount > 0 && $deposit_amount < $price ) : ?>
                                <span class="sodek-gb-service-deposit"><?php echo sprintf( esc_html__( '%s deposit', 'glowbook' ), '$' . number_format( $deposit_amount, 2 ) ); ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ( $description ) : ?>
                            <p class="sodek-gb-service-desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $description ), 20 ) ); ?></p>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="sodek-gb-btn sodek-gb-btn-select">
                        <?php esc_html_e( 'SELECT', 'glowbook' ); ?>
                    </button>
                </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Step 3: Date & Time (with Add-ons) -->
    <div class="sodek-gb-step sodek-gb-step-datetime" data-step="datetime">
        <div class="sodek-gb-step-header">
            <button type="button" class="sodek-gb-back-link" data-back="service">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <?php esc_html_e( 'SELECT APPOINTMENT', 'glowbook' ); ?>
            </button>
            <h2 class="sodek-gb-step-title"><?php esc_html_e( 'Date & Time', 'glowbook' ); ?></h2>
        </div>

        <!-- Selected Appointment Card -->
        <div class="sodek-gb-appointment-card">
            <span class="sodek-gb-label"><?php esc_html_e( 'APPOINTMENT', 'glowbook' ); ?></span>
            <div class="sodek-gb-appointment-content">
                <span class="sodek-gb-appointment-thumb sodek-gb-lightbox-trigger" data-lightbox-src="" data-lightbox-title="">
                    <img src="" alt="">
                </span>
                <div class="sodek-gb-appointment-details">
                    <h4 class="sodek-gb-appointment-title"></h4>
                    <p class="sodek-gb-appointment-price"></p>
                    <p class="sodek-gb-appointment-desc"></p>
                </div>
                <button type="button" class="sodek-gb-appointment-remove" aria-label="<?php esc_attr_e( 'Remove', 'glowbook' ); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Add-ons Section -->
        <div class="sodek-gb-addons-section" style="display:none;">
            <span class="sodek-gb-label"><?php esc_html_e( 'ADD TO APPOINTMENT', 'glowbook' ); ?></span>
            <div class="sodek-gb-addons-list" id="sodek-gb-addons-list"></div>
        </div>

        <!-- Calendar and Time Slots -->
        <div class="sodek-gb-datetime-grid">
            <div class="sodek-gb-calendar-section">
                <div class="sodek-gb-calendar" data-month="<?php echo esc_attr( date( 'n' ) ); ?>" data-year="<?php echo esc_attr( date( 'Y' ) ); ?>">
                    <div class="sodek-gb-calendar-header">
                        <button type="button" class="sodek-gb-calendar-nav sodek-gb-calendar-prev" aria-label="<?php esc_attr_e( 'Previous month', 'glowbook' ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>
                        <span class="sodek-gb-calendar-month"><?php echo esc_html( date( 'F Y' ) ); ?></span>
                        <button type="button" class="sodek-gb-calendar-nav sodek-gb-calendar-next" aria-label="<?php esc_attr_e( 'Next month', 'glowbook' ); ?>">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                    <div class="sodek-gb-calendar-weekdays">
                        <span>S</span>
                        <span>M</span>
                        <span>T</span>
                        <span>W</span>
                        <span>T</span>
                        <span>F</span>
                        <span>S</span>
                    </div>
                    <div class="sodek-gb-calendar-days">
                        <!-- Populated via JS -->
                    </div>
                </div>
            </div>

            <div class="sodek-gb-timeslots-section">
                <div class="sodek-gb-selected-date">
                    <span class="sodek-gb-date-display"></span>
                    <div class="sodek-gb-timezone">
                        <span class="sodek-gb-label"><?php esc_html_e( 'TIME ZONE:', 'glowbook' ); ?></span>
                        <a href="#" class="sodek-gb-timezone-link"><?php echo esc_html( get_option( 'sodek_gb_timezone', '' ) ?: wp_timezone_string() ); ?></a>
                    </div>
                </div>
                <div class="sodek-gb-timeslots" id="sodek-gb-timeslots">
                    <p class="sodek-gb-select-date-msg"><?php esc_html_e( 'Select a date to see available times.', 'glowbook' ); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4: Customer Details -->
    <div class="sodek-gb-step sodek-gb-step-details" data-step="details">
        <div class="sodek-gb-step-header">
            <button type="button" class="sodek-gb-back-link" data-back="datetime">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <?php esc_html_e( 'DATE & TIME', 'glowbook' ); ?>
            </button>
            <h2 class="sodek-gb-step-title"><?php esc_html_e( 'Your Details', 'glowbook' ); ?></h2>
        </div>

        <!-- Booking Summary -->
        <div class="sodek-gb-booking-summary">
            <div class="sodek-gb-summary-service"></div>
            <div class="sodek-gb-summary-addons"></div>
            <div class="sodek-gb-summary-datetime"></div>
            <div class="sodek-gb-summary-total"></div>
        </div>

        <form id="sodek-gb-customer-form" class="sodek-gb-customer-form">
            <div class="sodek-gb-form-row">
                <div class="sodek-gb-form-group">
                    <label for="sodek-gb-first-name"><?php esc_html_e( 'First Name', 'glowbook' ); ?> <span class="required">*</span></label>
                    <input type="text" id="sodek-gb-first-name" name="first_name" required
                           value="<?php echo esc_attr( isset( $customer['first_name'] ) ? $customer['first_name'] : '' ); ?>">
                </div>
                <div class="sodek-gb-form-group">
                    <label for="sodek-gb-last-name"><?php esc_html_e( 'Last Name', 'glowbook' ); ?> <span class="required">*</span></label>
                    <input type="text" id="sodek-gb-last-name" name="last_name" required
                           value="<?php echo esc_attr( isset( $customer['last_name'] ) ? $customer['last_name'] : '' ); ?>">
                </div>
            </div>

            <div class="sodek-gb-form-row">
                <div class="sodek-gb-form-group">
                    <label for="sodek-gb-email"><?php esc_html_e( 'Email', 'glowbook' ); ?> <span class="required">*</span></label>
                    <input type="email" id="sodek-gb-email" name="email" required
                           value="<?php echo esc_attr( isset( $customer['email'] ) ? $customer['email'] : '' ); ?>">
                </div>
                <div class="sodek-gb-form-group">
                    <label for="sodek-gb-phone"><?php esc_html_e( 'Phone', 'glowbook' ); ?></label>
                    <input type="tel" id="sodek-gb-phone" name="phone"
                           value="<?php echo esc_attr( isset( $customer['phone'] ) ? $customer['phone'] : '' ); ?>">
                </div>
            </div>

            <div class="sodek-gb-form-group">
                <label for="sodek-gb-notes"><?php esc_html_e( 'Notes (Optional)', 'glowbook' ); ?></label>
                <textarea id="sodek-gb-notes" name="notes" rows="3"></textarea>
            </div>

            <?php if ( ! empty( $payment_config['hasGateway'] ) ) : ?>
                <?php
                $square_config = isset( $payment_config['gateways']['square'] ) ? $payment_config['gateways']['square'] : array();
                $has_square_config = ! empty( $square_config['applicationId'] ) && ! empty( $square_config['locationId'] );
                ?>

                <!-- Deposit Amount Selector -->
                <div class="sodek-gb-deposit-section">
                    <h3><?php esc_html_e( 'Payment Amount', 'glowbook' ); ?></h3>
                    <p class="sodek-gb-deposit-description">
                        <?php esc_html_e( 'Payment is collected during booking. GlowBook will show the required amount for your booking below. You can also choose 50% or pay in full now to reduce your balance at the appointment.', 'glowbook' ); ?>
                    </p>
                    <p class="sodek-gb-customer-payment-status" aria-live="polite"></p>

                    <div class="sodek-gb-deposit-summary">
                        <div class="sodek-gb-deposit-row">
                            <span><?php esc_html_e( 'Service Total:', 'glowbook' ); ?></span>
                            <span class="sodek-gb-service-total">$0.00</span>
                        </div>
                        <div class="sodek-gb-deposit-row">
                            <span class="sodek-gb-deposit-row-label"><?php esc_html_e( 'Required Payment:', 'glowbook' ); ?></span>
                            <span class="sodek-gb-min-deposit">$0.00</span>
                        </div>
                    </div>

                    <div class="sodek-gb-deposit-input-wrap">
                        <label for="sodek-gb-deposit-input"><?php esc_html_e( 'Your Payment:', 'glowbook' ); ?></label>
                        <div class="sodek-gb-deposit-input-group">
                            <span class="sodek-gb-currency-symbol">$</span>
                            <input type="number" id="sodek-gb-deposit-input" class="sodek-gb-deposit-input" min="0" step="0.01" value="0" inputmode="decimal">
                        </div>
                    </div>

                    <div class="sodek-gb-deposit-quick-options">
                        <button type="button" class="sodek-gb-deposit-option" data-percent="returning">
                            <span class="sodek-gb-deposit-option-label"><?php esc_html_e( 'Returning Customer', 'glowbook' ); ?></span>
                            <span class="sodek-gb-deposit-option-amount">$0.00</span>
                        </button>
                        <button type="button" class="sodek-gb-deposit-option" data-percent="new">
                            <span class="sodek-gb-deposit-option-label"><?php esc_html_e( 'New Customer', 'glowbook' ); ?></span>
                            <span class="sodek-gb-deposit-option-amount">$0.00</span>
                        </button>
                        <button type="button" class="sodek-gb-deposit-option" data-percent="50">
                            <span class="sodek-gb-deposit-option-label"><?php esc_html_e( '50%', 'glowbook' ); ?></span>
                            <span class="sodek-gb-deposit-option-amount">$0.00</span>
                        </button>
                        <button type="button" class="sodek-gb-deposit-option" data-percent="100">
                            <span class="sodek-gb-deposit-option-label"><?php esc_html_e( 'Full', 'glowbook' ); ?></span>
                            <span class="sodek-gb-deposit-option-amount">$0.00</span>
                        </button>
                    </div>

                    <div class="sodek-gb-deposit-balance">
                        <span><?php esc_html_e( 'Balance due at appointment:', 'glowbook' ); ?></span>
                        <strong class="sodek-gb-balance-display">$0.00</strong>
                    </div>

                    <input type="hidden" name="custom_deposit" id="sodek-gb-custom-deposit" value="0">
                </div>

                <div class="sodek-gb-payment-section">
                    <div class="sodek-gb-payment-head">
                        <span class="sodek-gb-payment-kicker"><?php esc_html_e( 'Secure checkout', 'glowbook' ); ?></span>
                        <h3><?php esc_html_e( 'Payment Method', 'glowbook' ); ?></h3>
                        <p class="sodek-gb-payment-copy"><?php esc_html_e( 'Pay during booking with card to confirm your appointment. Your payment details are encrypted and handled securely by Square.', 'glowbook' ); ?></p>
                    </div>
                    <?php if ( $has_square_config ) : ?>
                        <div class="sodek-gb-payment-shell">
                            <div class="sodek-gb-payment-shell-head" aria-hidden="true">
                                <span class="sodek-gb-payment-chip"><?php esc_html_e( 'Card details', 'glowbook' ); ?></span>
                                <span class="sodek-gb-payment-supported"><?php esc_html_e( 'Visa, Mastercard, Amex, Discover', 'glowbook' ); ?></span>
                            </div>
                            <div id="sodek-gb-square-card-container" class="sodek-gb-card-container">
                                <!-- Square card form will be mounted here -->
                            </div>
                            <div class="sodek-gb-payment-trust" aria-hidden="true">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                </svg>
                                <span><?php esc_html_e( 'Encrypted card entry powered by Square', 'glowbook' ); ?></span>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="sodek-gb-payment-error">
                            <?php
                            // Show specific error if sandbox mode without app ID
                            $error_msg = __( 'Payment gateway is not configured.', 'glowbook' );
                            if ( isset( $square_config['environment'] ) && 'sandbox' === $square_config['environment'] && empty( $square_config['applicationId'] ) ) {
                                $error_msg = __( 'Square sandbox mode requires a Sandbox Application ID. Please configure it in WooCommerce > Settings > Payments > Square.', 'glowbook' );
                            }
                            echo esc_html( $error_msg );
                            ?>
                        </div>
                    <?php endif; ?>
                    <div id="sodek-gb-payment-errors" class="sodek-gb-payment-errors" role="alert" aria-live="polite"></div>
                    <input type="hidden" name="card_token" id="sodek_gb_card_token" value="">
                    <input type="hidden" name="verification_token" id="sodek_gb_verification_token" value="">
                </div>
            <?php else : ?>
                <div class="sodek-gb-payment-error" role="alert">
                    <?php esc_html_e( 'Online booking payments are not available right now. Please contact us before booking so we can help you reserve an appointment.', 'glowbook' ); ?>
                </div>
            <?php endif; ?>

            <?php if ( '' !== $booking_terms_text ) : ?>
                <div class="sodek-gb-booking-notice sodek-gb-booking-terms">
                    <span class="sodek-gb-booking-notice-kicker"><?php esc_html_e( 'Booking terms', 'glowbook' ); ?></span>
                    <div class="sodek-gb-booking-notice-copy"><?php echo wp_kses_post( wpautop( esc_html( $booking_terms_text ) ) ); ?></div>
                </div>
            <?php endif; ?>

            <button type="submit" class="sodek-gb-btn sodek-gb-btn-primary sodek-gb-btn-block sodek-gb-btn-book" <?php disabled( empty( $payment_config['hasGateway'] ) ); ?>>
                <?php echo ! empty( $payment_config['hasGateway'] ) ? esc_html__( 'Book Appointment', 'glowbook' ) : esc_html__( 'Booking Temporarily Unavailable', 'glowbook' ); ?>
            </button>
        </form>
    </div>

</div>

<!-- Image Lightbox -->
<div class="sodek-gb-lightbox" id="sodek-gb-lightbox">
    <div class="sodek-gb-lightbox-content">
        <button type="button" class="sodek-gb-lightbox-close" aria-label="<?php esc_attr_e( 'Close', 'glowbook' ); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <img src="" alt="" id="sodek-gb-lightbox-image">
        <div class="sodek-gb-lightbox-caption" id="sodek-gb-lightbox-caption"></div>
    </div>
</div>

<!-- Hidden data for JS -->
<script type="application/json" id="sodek-gb-services-data"><?php echo wp_json_encode( $services_grouped ); ?></script>
<script type="application/json" id="sodek-gb-preselected"><?php echo wp_json_encode( $preselected ); ?></script>
