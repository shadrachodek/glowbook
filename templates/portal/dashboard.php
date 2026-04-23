<?php
/**
 * Customer Portal Dashboard Template.
 *
 * @package GlowBook
 * @since   2.0.0
 *
 * @var array  $customer           Customer data.
 * @var array  $upcoming_bookings  Upcoming appointments.
 * @var array  $past_bookings      Past appointments.
 * @var array  $saved_cards        Saved payment cards.
 * @var string $nonce              Security nonce.
 * @var string $booking_url        Booking page URL.
 */

defined( 'ABSPATH' ) || exit;
?>

<script>
var sodekGbPortal = {
    ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>'
};
</script>

<div class="sodek-gb-portal">
    <div class="sodek-gb-container">
        <!-- Header -->
        <div class="sodek-gb-portal-header">
            <div class="sodek-gb-portal-welcome">
                <span class="sodek-gb-portal-kicker"><?php esc_html_e( 'Customer portal', 'glowbook' ); ?></span>
                <h1>
                    <?php
                    printf(
                        /* translators: %s: customer first name */
                        esc_html__( 'Welcome back, %s!', 'glowbook' ),
                        esc_html( $customer['first_name'] ?: __( 'there', 'glowbook' ) )
                    );
                    ?>
                </h1>
                <p><?php esc_html_e( 'Manage your appointments and preferences', 'glowbook' ); ?></p>
            </div>
            <div class="sodek-gb-portal-actions">
                <a href="<?php echo esc_url( $booking_url ); ?>" class="sodek-gb-btn sodek-gb-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <?php esc_html_e( 'Book Appointment', 'glowbook' ); ?>
                </a>
                <button type="button" id="sodek-gb-logout" class="sodek-gb-btn sodek-gb-btn-outline">
                    <?php esc_html_e( 'Sign Out', 'glowbook' ); ?>
                </button>
            </div>
        </div>

        <div class="sodek-gb-portal-stats">
            <div class="sodek-gb-portal-stat">
                <span class="sodek-gb-portal-stat-label"><?php esc_html_e( 'Upcoming', 'glowbook' ); ?></span>
                <strong class="sodek-gb-portal-stat-value"><?php echo esc_html( count( $upcoming_bookings ) ); ?></strong>
            </div>
            <div class="sodek-gb-portal-stat">
                <span class="sodek-gb-portal-stat-label"><?php esc_html_e( 'Completed', 'glowbook' ); ?></span>
                <strong class="sodek-gb-portal-stat-value"><?php echo esc_html( count( $past_bookings ) ); ?></strong>
            </div>
            <div class="sodek-gb-portal-stat">
                <span class="sodek-gb-portal-stat-label"><?php esc_html_e( 'Saved cards', 'glowbook' ); ?></span>
                <strong class="sodek-gb-portal-stat-value"><?php echo esc_html( count( $saved_cards ) ); ?></strong>
            </div>
        </div>

        <!-- Tabs -->
        <div class="sodek-gb-portal-tabs">
            <button type="button" class="sodek-gb-tab active" data-tab="upcoming">
                <?php esc_html_e( 'Upcoming', 'glowbook' ); ?>
                <?php if ( count( $upcoming_bookings ) > 0 ) : ?>
                    <span class="sodek-gb-badge"><?php echo count( $upcoming_bookings ); ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="sodek-gb-tab" data-tab="history">
                <?php esc_html_e( 'History', 'glowbook' ); ?>
            </button>
            <button type="button" class="sodek-gb-tab" data-tab="profile">
                <?php esc_html_e( 'Profile', 'glowbook' ); ?>
            </button>
        </div>

        <!-- Tab Content -->
        <div class="sodek-gb-portal-content">
            <!-- Upcoming Appointments -->
            <div id="sodek-gb-tab-upcoming" class="sodek-gb-tab-panel active">
                <?php if ( empty( $upcoming_bookings ) ) : ?>
                    <div class="sodek-gb-empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <h3><?php esc_html_e( 'No Upcoming Appointments', 'glowbook' ); ?></h3>
                        <p><?php esc_html_e( "You don't have any appointments scheduled.", 'glowbook' ); ?></p>
                        <a href="<?php echo esc_url( $booking_url ); ?>" class="sodek-gb-btn sodek-gb-btn-primary">
                            <?php esc_html_e( 'Book Now', 'glowbook' ); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="sodek-gb-bookings-list">
                        <?php foreach ( $upcoming_bookings as $booking ) : ?>
                            <?php
                            $service = $booking['service'] ?? array();
                            $staff = null;
                            if ( ! empty( $booking['staff_id'] ) ) {
                                $staff = Sodek_GB_Staff::get_staff( $booking['staff_id'] );
                            }
                            $can_reschedule = Sodek_GB_Customer_Portal::can_modify_booking( $booking, 'reschedule' );
                            $can_cancel = Sodek_GB_Customer_Portal::can_modify_booking( $booking, 'cancel' );
                            $balance_due = (float) get_post_meta( $booking['id'], '_sodek_gb_balance_amount', true );
                            ?>
                            <div class="sodek-gb-booking-card" data-booking-id="<?php echo esc_attr( $booking['id'] ); ?>">
                                <div class="sodek-gb-booking-date">
                                    <span class="sodek-gb-date-month">
                                        <?php echo esc_html( date_i18n( 'M', strtotime( $booking['booking_date'] ) ) ); ?>
                                    </span>
                                    <span class="sodek-gb-date-day">
                                        <?php echo esc_html( date_i18n( 'j', strtotime( $booking['booking_date'] ) ) ); ?>
                                    </span>
                                    <span class="sodek-gb-date-weekday">
                                        <?php echo esc_html( date_i18n( 'D', strtotime( $booking['booking_date'] ) ) ); ?>
                                    </span>
                                </div>

                                <div class="sodek-gb-booking-info">
                                    <h3 class="sodek-gb-booking-service"><?php echo esc_html( $service['title'] ?? '' ); ?></h3>

                                    <div class="sodek-gb-booking-meta">
                                        <span class="sodek-gb-booking-time">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            <?php
                                            echo esc_html(
                                                date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) )
                                            );
                                            ?>
                                        </span>

                                        <?php if ( $staff ) : ?>
                                            <span class="sodek-gb-booking-staff">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                    <circle cx="12" cy="7" r="4"></circle>
                                                </svg>
                                                <?php echo esc_html( $staff['name'] ); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ( ! empty( $booking['addons'] ) ) : ?>
                                        <div class="sodek-gb-booking-addons">
                                            <?php foreach ( $booking['addons'] as $addon ) : ?>
                                                <span class="sodek-gb-addon-tag"><?php echo esc_html( $addon['title'] ); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="sodek-gb-booking-status-row">
                                        <span class="sodek-gb-status sodek-gb-status-<?php echo esc_attr( $booking['status'] ); ?>">
                                            <?php echo esc_html( ucfirst( $booking['status'] ) ); ?>
                                        </span>

                                        <?php if ( $balance_due > 0 ) : ?>
                                            <span class="sodek-gb-balance-due">
                                                <?php
                                                printf(
                                                    /* translators: %s: balance amount */
                                                    esc_html__( 'Balance due: %s', 'glowbook' ),
                                                    wp_kses_post( Sodek_GB_Booking_Page::format_price( $balance_due ) )
                                                );
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="sodek-gb-booking-actions">
                                    <?php if ( $balance_due > 0 ) : ?>
                                        <button type="button"
                                            class="sodek-gb-btn sodek-gb-btn-primary sodek-gb-btn-sm sodek-gb-pay-balance"
                                            data-booking-id="<?php echo esc_attr( $booking['id'] ); ?>"
                                            data-amount="<?php echo esc_attr( $balance_due ); ?>"
                                            data-service-title="<?php echo esc_attr( $service['title'] ?? '' ); ?>"
                                            data-booking-date="<?php echo esc_attr( date_i18n( 'l, F j', strtotime( $booking['booking_date'] ) ) ); ?>"
                                            data-booking-time="<?php echo esc_attr( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?>">
                                            <?php esc_html_e( 'Pay Balance', 'glowbook' ); ?>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ( $can_reschedule ) : ?>
                                        <button type="button"
                                            class="sodek-gb-btn sodek-gb-btn-outline sodek-gb-btn-sm sodek-gb-reschedule"
                                            data-booking-id="<?php echo esc_attr( $booking['id'] ); ?>"
                                            data-service-title="<?php echo esc_attr( $service['title'] ?? '' ); ?>"
                                            data-booking-date="<?php echo esc_attr( date_i18n( 'l, F j', strtotime( $booking['booking_date'] ) ) ); ?>"
                                            data-booking-time="<?php echo esc_attr( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?>">
                                            <?php esc_html_e( 'Reschedule', 'glowbook' ); ?>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ( $can_cancel ) : ?>
                                        <button type="button"
                                            class="sodek-gb-btn sodek-gb-btn-link sodek-gb-btn-sm sodek-gb-text-danger sodek-gb-cancel"
                                            data-booking-id="<?php echo esc_attr( $booking['id'] ); ?>"
                                            data-service-title="<?php echo esc_attr( $service['title'] ?? '' ); ?>"
                                            data-booking-date="<?php echo esc_attr( date_i18n( 'l, F j', strtotime( $booking['booking_date'] ) ) ); ?>"
                                            data-booking-time="<?php echo esc_attr( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?>">
                                            <?php esc_html_e( 'Cancel', 'glowbook' ); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- History -->
            <div id="sodek-gb-tab-history" class="sodek-gb-tab-panel">
                <?php if ( empty( $past_bookings ) ) : ?>
                    <div class="sodek-gb-empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <h3><?php esc_html_e( 'No Past Appointments', 'glowbook' ); ?></h3>
                        <p><?php esc_html_e( 'Your appointment history will appear here.', 'glowbook' ); ?></p>
                    </div>
                <?php else : ?>
                    <div class="sodek-gb-bookings-list sodek-gb-history-list">
                        <?php foreach ( $past_bookings as $booking ) : ?>
                            <?php
                            $service = $booking['service'] ?? array();
                            $staff = null;
                            if ( ! empty( $booking['staff_id'] ) ) {
                                $staff = Sodek_GB_Staff::get_staff( $booking['staff_id'] );
                            }
                            ?>
                            <div class="sodek-gb-booking-card sodek-gb-past-booking">
                                <div class="sodek-gb-booking-date">
                                    <span class="sodek-gb-date-month">
                                        <?php echo esc_html( date_i18n( 'M', strtotime( $booking['booking_date'] ) ) ); ?>
                                    </span>
                                    <span class="sodek-gb-date-day">
                                        <?php echo esc_html( date_i18n( 'j', strtotime( $booking['booking_date'] ) ) ); ?>
                                    </span>
                                    <span class="sodek-gb-date-year">
                                        <?php echo esc_html( date_i18n( 'Y', strtotime( $booking['booking_date'] ) ) ); ?>
                                    </span>
                                </div>

                                <div class="sodek-gb-booking-info">
                                    <h3 class="sodek-gb-booking-service"><?php echo esc_html( $service['title'] ?? '' ); ?></h3>

                                    <div class="sodek-gb-booking-meta">
                                        <?php if ( $staff ) : ?>
                                            <span class="sodek-gb-booking-staff">
                                                <?php echo esc_html( $staff['name'] ); ?>
                                            </span>
                                        <?php endif; ?>

                                        <span class="sodek-gb-booking-price">
                                            <?php echo wp_kses_post( Sodek_GB_Booking_Page::format_price( $booking['total_price'] ) ); ?>
                                        </span>
                                    </div>

                                    <span class="sodek-gb-status sodek-gb-status-<?php echo esc_attr( $booking['status'] ); ?>">
                                        <?php echo esc_html( ucfirst( $booking['status'] ) ); ?>
                                    </span>
                                </div>

                                <div class="sodek-gb-booking-actions">
                                    <button type="button"
                                        class="sodek-gb-btn sodek-gb-btn-outline sodek-gb-btn-sm sodek-gb-rebook"
                                        data-service-id="<?php echo esc_attr( $booking['service_id'] ); ?>"
                                        data-staff-id="<?php echo esc_attr( $booking['staff_id'] ?? '' ); ?>">
                                        <?php esc_html_e( 'Book Again', 'glowbook' ); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Profile -->
            <div id="sodek-gb-tab-profile" class="sodek-gb-tab-panel">
                <div class="sodek-gb-profile-sections">
                    <!-- Personal Info -->
                    <div class="sodek-gb-profile-section">
                        <h3><?php esc_html_e( 'Personal Information', 'glowbook' ); ?></h3>
                        <form id="sodek-gb-profile-form" class="sodek-gb-profile-form">
                            <?php wp_nonce_field( 'sodek_gb_update_profile', 'profile_nonce' ); ?>

                            <div class="sodek-gb-form-row">
                                <div class="sodek-gb-form-group">
                                    <label for="sodek-gb-first-name"><?php esc_html_e( 'First Name', 'glowbook' ); ?></label>
                                    <input type="text" id="sodek-gb-first-name" name="first_name"
                                        class="sodek-gb-input"
                                        value="<?php echo esc_attr( $customer['first_name'] ?? '' ); ?>">
                                </div>
                                <div class="sodek-gb-form-group">
                                    <label for="sodek-gb-last-name"><?php esc_html_e( 'Last Name', 'glowbook' ); ?></label>
                                    <input type="text" id="sodek-gb-last-name" name="last_name"
                                        class="sodek-gb-input"
                                        value="<?php echo esc_attr( $customer['last_name'] ?? '' ); ?>">
                                </div>
                            </div>

                            <div class="sodek-gb-form-row">
                                <div class="sodek-gb-form-group">
                                    <label for="sodek-gb-email"><?php esc_html_e( 'Email', 'glowbook' ); ?></label>
                                    <input type="email" id="sodek-gb-email" name="email"
                                        class="sodek-gb-input"
                                        value="<?php echo esc_attr( $customer['email'] ?? '' ); ?>">
                                </div>
                                <div class="sodek-gb-form-group">
                                    <label for="sodek-gb-phone"><?php esc_html_e( 'Phone', 'glowbook' ); ?></label>
                                    <input type="tel" id="sodek-gb-phone" name="phone"
                                        class="sodek-gb-input"
                                        value="<?php echo esc_attr( $customer['phone'] ?? '' ); ?>"
                                        readonly>
                                    <p class="sodek-gb-form-help"><?php esc_html_e( 'Contact us to change your phone number.', 'glowbook' ); ?></p>
                                </div>
                            </div>

                            <button type="submit" class="sodek-gb-btn sodek-gb-btn-primary">
                                <?php esc_html_e( 'Save Changes', 'glowbook' ); ?>
                            </button>
                        </form>
                    </div>

                    <!-- Preferences -->
                    <div class="sodek-gb-profile-section">
                        <h3><?php esc_html_e( 'Hair Profile', 'glowbook' ); ?></h3>
                        <form id="sodek-gb-preferences-form" class="sodek-gb-profile-form">
                            <?php wp_nonce_field( 'sodek_gb_update_preferences', 'preferences_nonce' ); ?>

                            <div class="sodek-gb-form-row">
                                <div class="sodek-gb-form-group">
                                    <label for="sodek-gb-hair-type"><?php esc_html_e( 'Hair Type', 'glowbook' ); ?></label>
                                    <select id="sodek-gb-hair-type" name="hair_type" class="sodek-gb-select">
                                        <option value=""><?php esc_html_e( 'Select...', 'glowbook' ); ?></option>
                                        <option value="1" <?php selected( $customer['hair_type'] ?? '', '1' ); ?>>
                                            <?php esc_html_e( 'Type 1 - Straight', 'glowbook' ); ?>
                                        </option>
                                        <option value="2a" <?php selected( $customer['hair_type'] ?? '', '2a' ); ?>>
                                            <?php esc_html_e( 'Type 2A - Wavy', 'glowbook' ); ?>
                                        </option>
                                        <option value="2b" <?php selected( $customer['hair_type'] ?? '', '2b' ); ?>>
                                            <?php esc_html_e( 'Type 2B - Wavy', 'glowbook' ); ?>
                                        </option>
                                        <option value="2c" <?php selected( $customer['hair_type'] ?? '', '2c' ); ?>>
                                            <?php esc_html_e( 'Type 2C - Wavy', 'glowbook' ); ?>
                                        </option>
                                        <option value="3a" <?php selected( $customer['hair_type'] ?? '', '3a' ); ?>>
                                            <?php esc_html_e( 'Type 3A - Curly', 'glowbook' ); ?>
                                        </option>
                                        <option value="3b" <?php selected( $customer['hair_type'] ?? '', '3b' ); ?>>
                                            <?php esc_html_e( 'Type 3B - Curly', 'glowbook' ); ?>
                                        </option>
                                        <option value="3c" <?php selected( $customer['hair_type'] ?? '', '3c' ); ?>>
                                            <?php esc_html_e( 'Type 3C - Curly', 'glowbook' ); ?>
                                        </option>
                                        <option value="4a" <?php selected( $customer['hair_type'] ?? '', '4a' ); ?>>
                                            <?php esc_html_e( 'Type 4A - Coily', 'glowbook' ); ?>
                                        </option>
                                        <option value="4b" <?php selected( $customer['hair_type'] ?? '', '4b' ); ?>>
                                            <?php esc_html_e( 'Type 4B - Coily', 'glowbook' ); ?>
                                        </option>
                                        <option value="4c" <?php selected( $customer['hair_type'] ?? '', '4c' ); ?>>
                                            <?php esc_html_e( 'Type 4C - Coily', 'glowbook' ); ?>
                                        </option>
                                    </select>
                                </div>
                                <div class="sodek-gb-form-group">
                                    <label for="sodek-gb-hair-length"><?php esc_html_e( 'Hair Length', 'glowbook' ); ?></label>
                                    <select id="sodek-gb-hair-length" name="hair_length" class="sodek-gb-select">
                                        <option value=""><?php esc_html_e( 'Select...', 'glowbook' ); ?></option>
                                        <option value="short" <?php selected( $customer['hair_length'] ?? '', 'short' ); ?>>
                                            <?php esc_html_e( 'Short (above shoulders)', 'glowbook' ); ?>
                                        </option>
                                        <option value="medium" <?php selected( $customer['hair_length'] ?? '', 'medium' ); ?>>
                                            <?php esc_html_e( 'Medium (shoulder length)', 'glowbook' ); ?>
                                        </option>
                                        <option value="long" <?php selected( $customer['hair_length'] ?? '', 'long' ); ?>>
                                            <?php esc_html_e( 'Long (below shoulders)', 'glowbook' ); ?>
                                        </option>
                                        <option value="extra-long" <?php selected( $customer['hair_length'] ?? '', 'extra-long' ); ?>>
                                            <?php esc_html_e( 'Extra Long (waist length+)', 'glowbook' ); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="sodek-gb-form-group">
                                <label for="sodek-gb-allergies"><?php esc_html_e( 'Allergies or Sensitivities', 'glowbook' ); ?></label>
                                <textarea id="sodek-gb-allergies" name="allergies"
                                    class="sodek-gb-textarea"
                                    rows="3"
                                    placeholder="<?php esc_attr_e( 'Let us know about any allergies or product sensitivities...', 'glowbook' ); ?>"><?php echo esc_textarea( $customer['allergies'] ?? '' ); ?></textarea>
                            </div>

                            <div class="sodek-gb-form-group">
                                <label for="sodek-gb-notes"><?php esc_html_e( 'Additional Notes', 'glowbook' ); ?></label>
                                <textarea id="sodek-gb-notes" name="notes"
                                    class="sodek-gb-textarea"
                                    rows="3"
                                    placeholder="<?php esc_attr_e( 'Any other preferences or information we should know...', 'glowbook' ); ?>"><?php echo esc_textarea( $customer['notes'] ?? '' ); ?></textarea>
                            </div>

                            <button type="submit" class="sodek-gb-btn sodek-gb-btn-primary">
                                <?php esc_html_e( 'Save Preferences', 'glowbook' ); ?>
                            </button>
                        </form>
                    </div>

                    <!-- Saved Cards -->
                    <?php if ( get_option( 'sodek_gb_enable_cards_on_file', false ) ) : ?>
                        <div class="sodek-gb-profile-section">
                            <h3><?php esc_html_e( 'Saved Payment Methods', 'glowbook' ); ?></h3>

                            <?php if ( empty( $saved_cards ) ) : ?>
                                <p class="sodek-gb-empty-message">
                                    <?php esc_html_e( 'No saved payment methods yet.', 'glowbook' ); ?>
                                </p>
                            <?php else : ?>
                                <div class="sodek-gb-cards-list">
                                    <?php foreach ( $saved_cards as $card ) : ?>
                                        <div class="sodek-gb-card-item" data-card-id="<?php echo esc_attr( $card['id'] ); ?>">
                                            <div class="sodek-gb-card-info">
                                                <span class="sodek-gb-card-brand"><?php echo esc_html( ucfirst( $card['card_brand'] ) ); ?></span>
                                                <span class="sodek-gb-card-number">•••• <?php echo esc_html( $card['card_last4'] ); ?></span>
                                                <span class="sodek-gb-card-expiry">
                                                    <?php echo esc_html( $card['card_exp_month'] . '/' . $card['card_exp_year'] ); ?>
                                                </span>
                                                <?php if ( $card['is_default'] ) : ?>
                                                    <span class="sodek-gb-card-default"><?php esc_html_e( 'Default', 'glowbook' ); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="sodek-gb-card-actions">
                                                <?php if ( ! $card['is_default'] ) : ?>
                                                    <button type="button" class="sodek-gb-btn sodek-gb-btn-link sodek-gb-set-default-card">
                                                        <?php esc_html_e( 'Set Default', 'glowbook' ); ?>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="sodek-gb-btn sodek-gb-btn-link sodek-gb-text-danger sodek-gb-delete-card">
                                                    <?php esc_html_e( 'Remove', 'glowbook' ); ?>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Notification Preferences -->
                    <div class="sodek-gb-profile-section">
                        <h3><?php esc_html_e( 'Notification Preferences', 'glowbook' ); ?></h3>
                        <form id="sodek-gb-notifications-form" class="sodek-gb-profile-form">
                            <?php wp_nonce_field( 'sodek_gb_update_notifications', 'notifications_nonce' ); ?>

                            <div class="sodek-gb-checkbox-group">
                                <label class="sodek-gb-checkbox-label">
                                    <input type="checkbox" name="email_opt_in" value="1"
                                        <?php checked( $customer['email_opt_in'] ?? true, true ); ?>>
                                    <span><?php esc_html_e( 'Email reminders and updates', 'glowbook' ); ?></span>
                                </label>
                                <label class="sodek-gb-checkbox-label">
                                    <input type="checkbox" name="sms_opt_in" value="1"
                                        <?php checked( $customer['sms_opt_in'] ?? true, true ); ?>>
                                    <span><?php esc_html_e( 'SMS reminders (when available)', 'glowbook' ); ?></span>
                                </label>
                            </div>

                            <button type="submit" class="sodek-gb-btn sodek-gb-btn-primary">
                                <?php esc_html_e( 'Save Preferences', 'glowbook' ); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div id="sodek-gb-reschedule-modal" class="sodek-gb-modal" style="display: none;">
    <div class="sodek-gb-modal-backdrop"></div>
    <div class="sodek-gb-modal-content">
        <div class="sodek-gb-modal-header">
            <div>
                <span class="sodek-gb-modal-kicker"><?php esc_html_e( 'Appointment update', 'glowbook' ); ?></span>
                <h2><?php esc_html_e( 'Reschedule Appointment', 'glowbook' ); ?></h2>
            </div>
            <button type="button" class="sodek-gb-modal-close">&times;</button>
        </div>
        <div class="sodek-gb-modal-body">
            <div class="sodek-gb-modal-intro">
                <div class="sodek-gb-modal-summary">
                    <span class="sodek-gb-modal-summary-label"><?php esc_html_e( 'Updating', 'glowbook' ); ?></span>
                    <h3 id="sodek-gb-reschedule-service"><?php esc_html_e( 'Appointment', 'glowbook' ); ?></h3>
                    <p id="sodek-gb-reschedule-meta"><?php esc_html_e( 'Choose a new time that works for you.', 'glowbook' ); ?></p>
                </div>
                <p class="sodek-gb-modal-helper"><?php esc_html_e( 'Select a new date first, then choose one of the available time slots below.', 'glowbook' ); ?></p>
            </div>
            <div class="sodek-gb-reschedule-layout">
                <section class="sodek-gb-modal-panel">
                    <div class="sodek-gb-modal-panel-header">
                        <span class="sodek-gb-modal-panel-kicker"><?php esc_html_e( 'Step 1', 'glowbook' ); ?></span>
                        <h4><?php esc_html_e( 'Pick a date', 'glowbook' ); ?></h4>
                    </div>
                    <div id="sodek-gb-reschedule-calendar" class="sodek-gb-mini-calendar"></div>
                </section>
                <section class="sodek-gb-modal-panel">
                    <div class="sodek-gb-modal-panel-header">
                        <span class="sodek-gb-modal-panel-kicker"><?php esc_html_e( 'Step 2', 'glowbook' ); ?></span>
                        <h4><?php esc_html_e( 'Choose a time', 'glowbook' ); ?></h4>
                    </div>
                    <div id="sodek-gb-reschedule-times" class="sodek-gb-time-slots"></div>
                </section>
            </div>
            <div id="sodek-gb-reschedule-feedback" class="sodek-gb-modal-feedback" hidden></div>
        </div>
        <div class="sodek-gb-modal-footer">
            <button type="button" class="sodek-gb-btn sodek-gb-btn-outline sodek-gb-modal-cancel">
                <?php esc_html_e( 'Cancel', 'glowbook' ); ?>
            </button>
            <button type="button" id="sodek-gb-confirm-reschedule" class="sodek-gb-btn sodek-gb-btn-primary" disabled>
                <?php esc_html_e( 'Confirm New Time', 'glowbook' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div id="sodek-gb-cancel-modal" class="sodek-gb-modal" style="display: none;">
    <div class="sodek-gb-modal-backdrop"></div>
    <div class="sodek-gb-modal-content">
        <div class="sodek-gb-modal-header">
            <div>
                <span class="sodek-gb-modal-kicker"><?php esc_html_e( 'Cancellation', 'glowbook' ); ?></span>
                <h2><?php esc_html_e( 'Cancel Appointment', 'glowbook' ); ?></h2>
            </div>
            <button type="button" class="sodek-gb-modal-close">&times;</button>
        </div>
        <div class="sodek-gb-modal-body">
            <div class="sodek-gb-modal-intro">
                <div class="sodek-gb-modal-summary sodek-gb-modal-summary-danger">
                    <span class="sodek-gb-modal-summary-label"><?php esc_html_e( 'Cancelling', 'glowbook' ); ?></span>
                    <h3 id="sodek-gb-cancel-service"><?php esc_html_e( 'Appointment', 'glowbook' ); ?></h3>
                    <p id="sodek-gb-cancel-meta"><?php esc_html_e( 'Review the policy before you continue.', 'glowbook' ); ?></p>
                </div>
            </div>
            <div class="sodek-gb-cancel-warning">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div class="sodek-gb-cancel-copy">
                    <p class="sodek-gb-cancel-title"><?php esc_html_e( 'Are you sure you want to cancel this appointment?', 'glowbook' ); ?></p>
                    <p class="sodek-gb-cancel-description"><?php esc_html_e( 'If you continue, this time will be released and your booking will no longer be held.', 'glowbook' ); ?></p>
                </div>
                <div id="sodek-gb-cancel-policy" class="sodek-gb-cancel-policy"></div>
            </div>
            <div id="sodek-gb-cancel-feedback" class="sodek-gb-modal-feedback" hidden></div>
        </div>
        <div class="sodek-gb-modal-footer">
            <button type="button" class="sodek-gb-btn sodek-gb-btn-outline sodek-gb-modal-cancel">
                <?php esc_html_e( 'Keep Appointment', 'glowbook' ); ?>
            </button>
            <button type="button" id="sodek-gb-confirm-cancel" class="sodek-gb-btn sodek-gb-btn-danger">
                <?php esc_html_e( 'Yes, Cancel', 'glowbook' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- Pay Balance Modal -->
<div id="sodek-gb-pay-modal" class="sodek-gb-modal" style="display: none;">
    <div class="sodek-gb-modal-backdrop"></div>
    <div class="sodek-gb-modal-content">
        <div class="sodek-gb-modal-header">
            <div>
                <span class="sodek-gb-modal-kicker"><?php esc_html_e( 'Payment', 'glowbook' ); ?></span>
                <h2><?php esc_html_e( 'Pay Balance', 'glowbook' ); ?></h2>
            </div>
            <button type="button" class="sodek-gb-modal-close">&times;</button>
        </div>
        <div class="sodek-gb-modal-body">
            <div class="sodek-gb-modal-intro">
                <div class="sodek-gb-modal-summary">
                    <span class="sodek-gb-modal-summary-label"><?php esc_html_e( 'Paying for', 'glowbook' ); ?></span>
                    <h3 id="sodek-gb-pay-service"><?php esc_html_e( 'Appointment', 'glowbook' ); ?></h3>
                    <p id="sodek-gb-pay-meta"><?php esc_html_e( 'Use a saved card or securely enter a new one.', 'glowbook' ); ?></p>
                </div>
            </div>
            <div class="sodek-gb-pay-amount">
                <div>
                    <span class="sodek-gb-pay-label"><?php esc_html_e( 'Amount Due', 'glowbook' ); ?></span>
                    <p class="sodek-gb-pay-note"><?php esc_html_e( 'This completes the remaining balance for your appointment.', 'glowbook' ); ?></p>
                </div>
                <span id="sodek-gb-pay-amount-value" class="sodek-gb-amount"></span>
            </div>

            <?php if ( ! empty( $saved_cards ) ) : ?>
                <div class="sodek-gb-saved-cards-select">
                    <label for="sodek-gb-pay-card-select"><?php esc_html_e( 'Pay with saved card', 'glowbook' ); ?></label>
                    <select id="sodek-gb-pay-card-select" class="sodek-gb-select">
                        <option value=""><?php esc_html_e( 'Select a card...', 'glowbook' ); ?></option>
                        <?php foreach ( $saved_cards as $card ) : ?>
                            <option value="<?php echo esc_attr( $card['id'] ); ?>"
                                <?php selected( $card['is_default'], true ); ?>>
                                <?php echo esc_html( ucfirst( $card['card_brand'] ) . ' •••• ' . $card['card_last4'] ); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="new"><?php esc_html_e( 'Use a new card', 'glowbook' ); ?></option>
                    </select>
                </div>
            <?php endif; ?>

            <div id="sodek-gb-pay-new-card" class="sodek-gb-new-card-form" <?php echo ! empty( $saved_cards ) ? 'style="display:none;"' : ''; ?>>
                <div id="sodek-gb-pay-card-container"></div>
            </div>
            <div class="sodek-gb-pay-security">
                <span class="sodek-gb-pay-security-badge"><?php esc_html_e( 'Secure checkout', 'glowbook' ); ?></span>
                <p><?php esc_html_e( 'Your payment details are processed securely and are never stored in plain text.', 'glowbook' ); ?></p>
            </div>
            <div id="sodek-gb-pay-feedback" class="sodek-gb-modal-feedback" hidden></div>
        </div>
        <div class="sodek-gb-modal-footer">
            <button type="button" class="sodek-gb-btn sodek-gb-btn-outline sodek-gb-modal-cancel">
                <?php esc_html_e( 'Cancel', 'glowbook' ); ?>
            </button>
            <button type="button" id="sodek-gb-confirm-pay" class="sodek-gb-btn sodek-gb-btn-primary">
                <?php esc_html_e( 'Pay Now', 'glowbook' ); ?>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabs = document.querySelectorAll('.sodek-gb-tab');
    const panels = document.querySelectorAll('.sodek-gb-tab-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetId = 'sodek-gb-tab-' + this.dataset.tab;

            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(targetId).classList.add('active');
        });
    });

    // Logout
    document.getElementById('sodek-gb-logout').addEventListener('click', async function() {
        try {
            const response = await fetch(sodekGbPortal.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'sodek_gb_portal_logout',
                    nonce: '<?php echo esc_js( wp_create_nonce( 'sodek_gb_portal_logout' ) ); ?>'
                })
            });
            const data = await response.json();
            window.location.href = data?.data?.redirect_url || '<?php echo esc_url( Sodek_GB_Standalone_Booking::get_portal_url() ); ?>';
        } catch (error) {
            console.error('Logout error:', error);
        }
    });

    // Modals
    const rescheduleModal = document.getElementById('sodek-gb-reschedule-modal');
    const cancelModal = document.getElementById('sodek-gb-cancel-modal');
    const payModal = document.getElementById('sodek-gb-pay-modal');
    const payCardSelect = document.getElementById('sodek-gb-pay-card-select');
    const payNewCard = document.getElementById('sodek-gb-pay-new-card');
    const payCardContainer = document.getElementById('sodek-gb-pay-card-container');
    const confirmPayBtn = document.getElementById('sodek-gb-confirm-pay');
    let currentBookingId = null;
    let currentBookingContext = null;
    let selectedDate = null;
    let selectedTime = null;
    let portalSquarePayments = null;
    let portalSquareCard = null;
    let portalSquareReady = false;
    let portalSquareMounting = false;

    function getBookingContext(source) {
        return {
            serviceTitle: source.dataset.serviceTitle || '<?php echo esc_js( __( 'Appointment', 'glowbook' ) ); ?>',
            bookingDate: source.dataset.bookingDate || '',
            bookingTime: source.dataset.bookingTime || ''
        };
    }

    function renderBookingMeta(context) {
        return [context.bookingDate, context.bookingTime].filter(Boolean).join(' at ');
    }

    function updateModalSummary(prefix, context) {
        const serviceEl = document.getElementById('sodek-gb-' + prefix + '-service');
        const metaEl = document.getElementById('sodek-gb-' + prefix + '-meta');

        if (serviceEl) {
            serviceEl.textContent = context.serviceTitle;
        }

        if (metaEl) {
            metaEl.textContent = renderBookingMeta(context) || metaEl.dataset.fallback || '';
        }
    }

    function clearModalFeedback(modal) {
        const feedback = modal.querySelector('.sodek-gb-modal-feedback');
        if (feedback) {
            feedback.hidden = true;
            feedback.className = 'sodek-gb-modal-feedback';
            feedback.textContent = '';
        }
    }

    function showModalFeedback(modal, message, type = 'error') {
        const feedback = modal.querySelector('.sodek-gb-modal-feedback');
        if (!feedback) return;

        feedback.hidden = false;
        feedback.className = 'sodek-gb-modal-feedback sodek-gb-modal-feedback-' + type;
        feedback.textContent = message;
    }

    async function initializePortalSquareCard() {
        if (portalSquareReady || portalSquareMounting) {
            return portalSquareReady;
        }

        if (!payCardContainer) {
            return false;
        }

        if (typeof window.Square === 'undefined' || typeof window.sodekGbSquare === 'undefined') {
            showModalFeedback(payModal, '<?php echo esc_js( __( 'Card payments are not available right now. Please try a saved card or contact the salon for help.', 'glowbook' ) ); ?>');
            return false;
        }

        portalSquareMounting = true;

        try {
            portalSquarePayments = window.Square.payments(
                window.sodekGbSquare.applicationId,
                window.sodekGbSquare.locationId
            );

            portalSquareCard = await portalSquarePayments.card({
                style: {
                    input: {
                        fontFamily: "'DM Sans', sans-serif",
                        fontSize: '16px',
                        color: '#201915'
                    }
                }
            });

            await portalSquareCard.attach('#sodek-gb-pay-card-container');
            portalSquareReady = true;
            return true;
        } catch (error) {
            console.error('GlowBook portal: Square init failed', error);
            showModalFeedback(payModal, '<?php echo esc_js( __( 'We could not load the secure card form. Please choose a saved card or try again shortly.', 'glowbook' ) ); ?>');
            return false;
        } finally {
            portalSquareMounting = false;
        }
    }

    async function tokenizePortalCard() {
        if (!portalSquareReady || !portalSquareCard) {
            const initialized = await initializePortalSquareCard();
            if (!initialized) {
                return null;
            }
        }

        try {
            const tokenResult = await portalSquareCard.tokenize();

            if (tokenResult.status === 'OK') {
                return tokenResult.token;
            }

            const squareErrors = Array.isArray(tokenResult.errors) ? tokenResult.errors : [];
            const message = squareErrors.length ? squareErrors.map(error => error.message).join(' ') : '<?php echo esc_js( __( 'Your card details could not be verified. Please check them and try again.', 'glowbook' ) ); ?>';
            showModalFeedback(payModal, message);
            return null;
        } catch (error) {
            console.error('GlowBook portal: Square tokenization failed', error);
            showModalFeedback(payModal, '<?php echo esc_js( __( 'We could not process your card details. Please try again.', 'glowbook' ) ); ?>');
            return null;
        }
    }

    async function verifyPortalBuyer(token, amount) {
        if (!portalSquarePayments || !token || !amount) {
            return null;
        }

        try {
            const verificationResult = await portalSquarePayments.verifyBuyer(token, {
                amount: String(amount),
                billingContact: {},
                currencyCode: (window.sodekGbSquare && window.sodekGbSquare.currency) ? window.sodekGbSquare.currency : 'USD',
                intent: 'CHARGE'
            });

            return verificationResult && verificationResult.token ? verificationResult.token : null;
        } catch (error) {
            console.error('GlowBook portal: Square verification failed', error);
            return null;
        }
    }

    function portalUsesNewCard() {
        return !payCardSelect || payCardSelect.value === '' || payCardSelect.value === 'new';
    }

    async function updatePortalPaymentMode() {
        const usingNewCard = portalUsesNewCard();

        if (payNewCard) {
            payNewCard.style.display = usingNewCard ? '' : 'none';
        }

        if (confirmPayBtn) {
            confirmPayBtn.textContent = usingNewCard
                ? '<?php echo esc_js( __( 'Pay Now', 'glowbook' ) ); ?>'
                : '<?php echo esc_js( __( 'Pay Selected Card', 'glowbook' ) ); ?>';
            confirmPayBtn.disabled = false;
        }

        if (usingNewCard) {
            const initialized = await initializePortalSquareCard();
            if (!initialized && confirmPayBtn) {
                confirmPayBtn.disabled = true;
            }
        }
    }

    function openModal(modal) {
        clearModalFeedback(modal);
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.sodek-gb-modal-close, .sodek-gb-modal-cancel, .sodek-gb-modal-backdrop').forEach(el => {
        el.addEventListener('click', function() {
            closeModal(this.closest('.sodek-gb-modal'));
        });
    });

    // Reschedule
    document.querySelectorAll('.sodek-gb-reschedule').forEach(btn => {
        btn.addEventListener('click', function() {
            currentBookingId = this.dataset.bookingId;
            currentBookingContext = getBookingContext(this);
            selectedDate = null;
            selectedTime = null;
            document.getElementById('sodek-gb-confirm-reschedule').disabled = true;
            updateModalSummary('reschedule', currentBookingContext);
            loadRescheduleCalendar();
            openModal(rescheduleModal);
        });
    });

    async function loadRescheduleCalendar() {
        // Simplified calendar - in production, use a proper calendar library
        const calendarEl = document.getElementById('sodek-gb-reschedule-calendar');
        const timesEl = document.getElementById('sodek-gb-reschedule-times');
        calendarEl.innerHTML = '<p class="sodek-gb-loading"><?php esc_html_e( 'Loading available dates...', 'glowbook' ); ?></p>';
        timesEl.innerHTML = '';

        try {
            const response = await fetch(sodekGbPortal.ajaxUrl + '?' + new URLSearchParams({
                action: 'sodek_gb_get_reschedule_dates',
                booking_id: currentBookingId,
                nonce: '<?php echo esc_js( wp_create_nonce( 'sodek_gb_portal' ) ); ?>'
            }));
            const data = await response.json();

            if (data.success) {
                renderMiniCalendar(calendarEl, data.data.dates);
            }
        } catch (error) {
            calendarEl.innerHTML = '<p class="sodek-gb-error"><?php esc_html_e( 'Failed to load dates.', 'glowbook' ); ?></p>';
            showModalFeedback(rescheduleModal, '<?php echo esc_js( __( 'We could not load available dates right now. Please try again.', 'glowbook' ) ); ?>');
        }
    }

    function renderMiniCalendar(container, availableDates) {
        const today = new Date();
        const currentMonth = today.getMonth();
        const currentYear = today.getFullYear();

        let html = '<div class="sodek-gb-calendar-grid">';

        // Header
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        html += '<div class="sodek-gb-calendar-header">' + monthNames[currentMonth] + ' ' + currentYear + '</div>';

        // Day names
        const dayNames = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
        html += '<div class="sodek-gb-calendar-days">';
        dayNames.forEach(day => {
            html += '<span>' + day + '</span>';
        });
        html += '</div>';

        // Days
        html += '<div class="sodek-gb-calendar-dates">';
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        for (let i = 0; i < firstDay; i++) {
            html += '<span class="sodek-gb-empty"></span>';
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = currentYear + '-' + String(currentMonth + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
            const isAvailable = availableDates.includes(dateStr);
            const isPast = new Date(dateStr) < new Date(today.toDateString());

            let classes = 'sodek-gb-date';
            if (isPast) classes += ' sodek-gb-past';
            else if (isAvailable) classes += ' sodek-gb-available';

            html += '<span class="' + classes + '" data-date="' + dateStr + '">' + day + '</span>';
        }

        html += '</div></div>';
        container.innerHTML = html;

        // Date click handlers
        container.querySelectorAll('.sodek-gb-date.sodek-gb-available').forEach(el => {
            el.addEventListener('click', function() {
                container.querySelectorAll('.sodek-gb-date').forEach(d => d.classList.remove('sodek-gb-selected'));
                this.classList.add('sodek-gb-selected');
                selectedDate = this.dataset.date;
                loadTimeSlots(selectedDate);
            });
        });
    }

    async function loadTimeSlots(date) {
        const timesEl = document.getElementById('sodek-gb-reschedule-times');
        timesEl.innerHTML = '<p class="sodek-gb-loading"><?php esc_html_e( 'Loading times...', 'glowbook' ); ?></p>';

        try {
            const response = await fetch(sodekGbPortal.ajaxUrl + '?' + new URLSearchParams({
                action: 'sodek_gb_get_reschedule_times',
                booking_id: currentBookingId,
                date: date,
                nonce: '<?php echo esc_js( wp_create_nonce( 'sodek_gb_portal' ) ); ?>'
            }));
            const data = await response.json();

            if (data.success && data.data.times.length > 0) {
                let html = '<div class="sodek-gb-times-grid">';
                data.data.times.forEach(time => {
                    html += '<button type="button" class="sodek-gb-time-btn" data-time="' + time.value + '">' + time.label + '</button>';
                });
                html += '</div>';
                timesEl.innerHTML = html;

                timesEl.querySelectorAll('.sodek-gb-time-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        timesEl.querySelectorAll('.sodek-gb-time-btn').forEach(b => b.classList.remove('sodek-gb-selected'));
                        this.classList.add('sodek-gb-selected');
                        selectedTime = this.dataset.time;
                        document.getElementById('sodek-gb-confirm-reschedule').disabled = false;
                    });
                });
            } else {
                timesEl.innerHTML = '<p class="sodek-gb-no-times"><?php esc_html_e( 'No available times for this date.', 'glowbook' ); ?></p>';
            }
        } catch (error) {
            timesEl.innerHTML = '<p class="sodek-gb-error"><?php esc_html_e( 'Failed to load times.', 'glowbook' ); ?></p>';
            showModalFeedback(rescheduleModal, '<?php echo esc_js( __( 'We could not load the available times. Please try a different date or try again shortly.', 'glowbook' ) ); ?>');
        }
    }

    document.getElementById('sodek-gb-confirm-reschedule').addEventListener('click', async function() {
        if (!selectedDate || !selectedTime) return;

        this.disabled = true;
        this.innerHTML = '<span class="sodek-gb-spinner"></span>';

        try {
                const response = await fetch(sodekGbPortal.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'sodek_gb_portal_reschedule',
                        booking_id: currentBookingId,
                        new_date: selectedDate,
                        new_time: selectedTime,
                        nonce: '<?php echo esc_js( wp_create_nonce( 'sodek_gb_portal' ) ); ?>'
                    })
                });
            const data = await response.json();

            if (data.success) {
                window.location.reload();
            } else {
                showModalFeedback(rescheduleModal, data.data.message || '<?php echo esc_js( __( 'Failed to reschedule.', 'glowbook' ) ); ?>');
            }
        } catch (error) {
            showModalFeedback(rescheduleModal, '<?php echo esc_js( __( 'An error occurred.', 'glowbook' ) ); ?>');
        } finally {
            this.disabled = false;
            this.innerHTML = '<?php esc_html_e( 'Confirm New Time', 'glowbook' ); ?>';
        }
    });

    // Cancel
    document.querySelectorAll('.sodek-gb-cancel').forEach(btn => {
        btn.addEventListener('click', async function() {
            currentBookingId = this.dataset.bookingId;
            currentBookingContext = getBookingContext(this);
            updateModalSummary('cancel', currentBookingContext);
            openModal(cancelModal);

            // Get cancellation policy
            try {
                const response = await fetch(sodekGbPortal.ajaxUrl + '?' + new URLSearchParams({
                    action: 'sodek_gb_get_cancel_policy',
                    booking_id: currentBookingId,
                    nonce: '<?php echo esc_js( wp_create_nonce( 'sodek_gb_portal' ) ); ?>'
                }));
                const data = await response.json();

                if (data.success) {
                    document.getElementById('sodek-gb-cancel-policy').innerHTML = data.data.policy_text;
                }
            } catch (error) {
                showModalFeedback(cancelModal, '<?php echo esc_js( __( 'We could not load the cancellation policy. You can still try again in a moment.', 'glowbook' ) ); ?>');
            }
        });
    });

    document.getElementById('sodek-gb-confirm-cancel').addEventListener('click', async function() {
        this.disabled = true;
        this.innerHTML = '<span class="sodek-gb-spinner"></span>';

        try {
                const response = await fetch(sodekGbPortal.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'sodek_gb_portal_cancel',
                        booking_id: currentBookingId,
                        nonce: '<?php echo esc_js( wp_create_nonce( 'sodek_gb_portal' ) ); ?>'
                    })
                });
            const data = await response.json();

            if (data.success) {
                window.location.reload();
            } else {
                showModalFeedback(cancelModal, data.data.message || '<?php echo esc_js( __( 'Failed to cancel.', 'glowbook' ) ); ?>');
            }
        } catch (error) {
            showModalFeedback(cancelModal, '<?php echo esc_js( __( 'An error occurred.', 'glowbook' ) ); ?>');
        } finally {
            this.disabled = false;
            this.innerHTML = '<?php esc_html_e( 'Yes, Cancel', 'glowbook' ); ?>';
        }
    });

    // Pay Balance
    document.querySelectorAll('.sodek-gb-pay-balance').forEach(btn => {
        btn.addEventListener('click', async function() {
            currentBookingId = this.dataset.bookingId;
            currentBookingContext = getBookingContext(this);
            const amount = this.dataset.amount;
            updateModalSummary('pay', currentBookingContext);
            document.getElementById('sodek-gb-pay-amount-value').textContent = '$' + parseFloat(amount).toFixed(2);
            openModal(payModal);
            await updatePortalPaymentMode();
        });
    });

    if (payCardSelect) {
        payCardSelect.addEventListener('change', function() {
            updatePortalPaymentMode();
        });
    }

    if (confirmPayBtn) {
        confirmPayBtn.addEventListener('click', async function() {
            if (!currentBookingId) {
                showModalFeedback(payModal, '<?php echo esc_js( __( 'Please reopen the payment window and try again.', 'glowbook' ) ); ?>');
                return;
            }

            const originalLabel = this.textContent;
            this.disabled = true;
            this.innerHTML = '<span class="sodek-gb-spinner"></span>';

            try {
                const params = new URLSearchParams({
                    action: 'sodek_gb_portal_pay_balance',
                    booking_id: currentBookingId,
                    nonce: '<?php echo esc_js( wp_create_nonce( 'sodek_gb_portal' ) ); ?>'
                });

                if (payCardSelect && payCardSelect.value && payCardSelect.value !== 'new') {
                    params.append('saved_card_id', payCardSelect.value);
                } else {
                    const cardToken = await tokenizePortalCard();
                    if (!cardToken) {
                        return;
                    }

                    const amount = parseFloat(
                        (document.getElementById('sodek-gb-pay-amount-value').textContent || '0').replace(/[^0-9.]/g, '')
                    ) || 0;
                    const verificationToken = await verifyPortalBuyer(cardToken, amount.toFixed(2));

                    params.append('card_token', cardToken);
                    if (verificationToken) {
                        params.append('verification_token', verificationToken);
                    }
                }

                const response = await fetch(sodekGbPortal.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });
                const data = await response.json();

                if (data.success) {
                    showModalFeedback(payModal, data.data.message || '<?php echo esc_js( __( 'Balance paid successfully!', 'glowbook' ) ); ?>', 'success');
                    showToast(data.data.message || '<?php echo esc_js( __( 'Balance paid successfully!', 'glowbook' ) ); ?>', 'success');

                    if (data.data.receipt_url) {
                        window.open(data.data.receipt_url, '_blank', 'noopener');
                    }

                    window.setTimeout(() => window.location.reload(), 900);
                } else {
                    showModalFeedback(payModal, data.data.message || '<?php echo esc_js( __( 'Payment failed.', 'glowbook' ) ); ?>');
                }
            } catch (error) {
                console.error('GlowBook portal: balance payment failed', error);
                showModalFeedback(payModal, '<?php echo esc_js( __( 'We could not complete your payment. Please try again.', 'glowbook' ) ); ?>');
            } finally {
                this.disabled = false;
                this.textContent = originalLabel;
            }
        });
    }

    // Rebook
    document.querySelectorAll('.sodek-gb-rebook').forEach(btn => {
        btn.addEventListener('click', function() {
            const serviceId = this.dataset.serviceId;
            const staffId = this.dataset.staffId;
            let url = '<?php echo esc_url( $booking_url ); ?>?service=' + serviceId;
            if (staffId) url += '&staff=' + staffId;
            window.location.href = url;
        });
    });

    // Profile form
    document.getElementById('sodek-gb-profile-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="sodek-gb-spinner"></span>';

        try {
            const formData = new FormData(this);
            formData.append('action', 'sodek_gb_update_profile');

            const response = await fetch(sodekGbPortal.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showToast('<?php esc_attr_e( 'Profile updated!', 'glowbook' ); ?>', 'success');
            } else {
                showToast(data.data.message || '<?php esc_attr_e( 'Failed to update.', 'glowbook' ); ?>', 'error');
            }
        } catch (error) {
            showToast('<?php esc_attr_e( 'An error occurred.', 'glowbook' ); ?>', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<?php esc_html_e( 'Save Changes', 'glowbook' ); ?>';
        }
    });

    // Preferences form
    document.getElementById('sodek-gb-preferences-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="sodek-gb-spinner"></span>';

        try {
            const formData = new FormData(this);
            formData.append('action', 'sodek_gb_update_preferences');

            const response = await fetch(sodekGbPortal.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                showToast('<?php esc_attr_e( 'Preferences saved!', 'glowbook' ); ?>', 'success');
            } else {
                showToast(data.data.message || '<?php esc_attr_e( 'Failed to save.', 'glowbook' ); ?>', 'error');
            }
        } catch (error) {
            showToast('<?php esc_attr_e( 'An error occurred.', 'glowbook' ); ?>', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<?php esc_html_e( 'Save Preferences', 'glowbook' ); ?>';
        }
    });

    const notificationsForm = document.getElementById('sodek-gb-notifications-form');

    if (notificationsForm) {
        notificationsForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="sodek-gb-spinner"></span>';

            try {
                const formData = new FormData(this);
                formData.append('action', 'sodek_gb_update_notifications');

                const response = await fetch(sodekGbPortal.ajaxUrl, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    showToast('<?php esc_attr_e( 'Notification preferences saved!', 'glowbook' ); ?>', 'success');
                } else {
                    showToast(data.data.message || '<?php esc_attr_e( 'Failed to save.', 'glowbook' ); ?>', 'error');
                }
            } catch (error) {
                showToast('<?php esc_attr_e( 'An error occurred.', 'glowbook' ); ?>', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<?php esc_html_e( 'Save Preferences', 'glowbook' ); ?>';
            }
        });
    }

    document.querySelectorAll('.sodek-gb-set-default-card').forEach(btn => {
        btn.addEventListener('click', async function() {
            const cardItem = this.closest('.sodek-gb-card-item');
            if (!cardItem) return;

            const cardId = cardItem.dataset.cardId;
            this.disabled = true;

            try {
                const response = await fetch(sodekGbPortal.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'sodek_gb_portal_set_default_card',
                        card_id: cardId,
                        nonce: '<?php echo esc_js( wp_create_nonce( 'sodek_gb_portal' ) ); ?>'
                    })
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.data.message || '<?php echo esc_js( __( 'Default card updated.', 'glowbook' ) ); ?>', 'success');
                    window.setTimeout(() => window.location.reload(), 500);
                } else {
                    showToast(data.data.message || '<?php echo esc_js( __( 'We could not update your default card.', 'glowbook' ) ); ?>', 'error');
                }
            } catch (error) {
                console.error('GlowBook portal: set default card failed', error);
                showToast('<?php echo esc_js( __( 'We could not update your default card.', 'glowbook' ) ); ?>', 'error');
            } finally {
                this.disabled = false;
            }
        });
    });

    document.querySelectorAll('.sodek-gb-delete-card').forEach(btn => {
        btn.addEventListener('click', async function() {
            const cardItem = this.closest('.sodek-gb-card-item');
            if (!cardItem) return;

            if (!window.confirm('<?php echo esc_js( __( 'Remove this saved card from your account?', 'glowbook' ) ); ?>')) {
                return;
            }

            const cardId = cardItem.dataset.cardId;
            this.disabled = true;

            try {
                const response = await fetch(sodekGbPortal.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'sodek_gb_portal_delete_card',
                        card_id: cardId,
                        nonce: '<?php echo esc_js( wp_create_nonce( 'sodek_gb_portal' ) ); ?>'
                    })
                });
                const data = await response.json();

                if (data.success) {
                    showToast(data.data.message || '<?php echo esc_js( __( 'Saved card removed.', 'glowbook' ) ); ?>', 'success');
                    cardItem.style.opacity = '0';
                    window.setTimeout(() => window.location.reload(), 350);
                } else {
                    showToast(data.data.message || '<?php echo esc_js( __( 'We could not remove that card.', 'glowbook' ) ); ?>', 'error');
                }
            } catch (error) {
                console.error('GlowBook portal: delete card failed', error);
                showToast('<?php echo esc_js( __( 'We could not remove that card.', 'glowbook' ) ); ?>', 'error');
            } finally {
                this.disabled = false;
            }
        });
    });

    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = 'sodek-gb-toast sodek-gb-toast-' + type;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('sodek-gb-show'), 10);
        setTimeout(() => {
            toast.classList.remove('sodek-gb-show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
</script>
