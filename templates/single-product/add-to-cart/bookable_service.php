<?php
/**
 * Bookable Service Add to Cart template.
 *
 * Enhanced booking experience for beauty salon services.
 * Features step-based flow with separate date and time selection.
 *
 * @package GlowBook
 * @version 2.0.0
 * @var WC_Product_Bookable_Service $product
 */
// Template v2.0 - Separate Date/Time steps

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product || ! $product->is_purchasable() ) {
	return;
}

// Get service ID and validate
$is_booking = get_post_meta( $product->get_id(), '_sodek_gb_is_booking_product', true );
$service_id = get_post_meta( $product->get_id(), '_sodek_gb_service_id', true );

if ( 'yes' !== $is_booking || ! $service_id ) {
	wc_get_template( 'single-product/add-to-cart/simple.php' );
	return;
}

// Get service with product deposit override applied
$service = Sodek_GB_WC_Product::get_service_with_product_deposit( $product->get_id() );
if ( ! $service ) {
	echo '<div class="sodek-gb-error woocommerce-error" role="alert">';
	esc_html_e( 'This service is not properly configured. Please contact the administrator.', 'glowbook' );
	echo '</div>';
	return;
}

// Get add-ons for this service
$addons = Sodek_GB_Addon::get_addons_for_service( $service_id );
$has_addons = ! empty( $addons );

// Get deposit settings
$deposit_type = get_post_meta( $service_id, '_sodek_gb_deposit_type', true ) ?: 'percentage';
$deposit_value = (float) ( get_post_meta( $service_id, '_sodek_gb_deposit_value', true ) ?: 50 );

// Check if this is an add-on only service (no base price)
$is_addon_only = ( $service['price'] <= 0 && $has_addons );

// Initial deposit is based on base price only - will be recalculated with add-ons via JS
// For add-on only services, start at 0
$initial_deposit = $is_addon_only ? 0 : $service['deposit_amount'];
$balance = $service['price'] - $initial_deposit;

// Determine number of steps: With addons = 4 (Extras, Date, Time, Confirm), Without = 3 (Date, Time, Confirm)
$total_steps = $has_addons ? 4 : 3;

// Display settings - check for per-product override first, then fall back to global
$show_duration_override = get_post_meta( $product->get_id(), '_sodek_gb_show_duration_override', true );
if ( '' !== $show_duration_override ) {
    $show_duration = ( 'yes' === $show_duration_override );
} else {
    $show_duration = get_option( 'sodek_gb_show_duration', 1 );
}

// Product layout setting
$product_layout = get_post_meta( $product->get_id(), '_sodek_gb_product_layout', true ) ?: 'default';

do_action( 'woocommerce_before_add_to_cart_form' );

// Get product description for policies section
$product_description = $product->get_description();
$has_policies = ! empty( trim( wp_strip_all_tags( $product_description ) ) );
?>

<?php if ( $has_policies ) : ?>
<!-- Booking Policies Accordion -->
<div class="sodek-gb-policies-accordion">
	<button type="button" class="sodek-gb-policies-toggle" aria-expanded="false" aria-controls="sodek-gb-policies-content">
		<span class="sodek-gb-policies-icon">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="12" cy="12" r="10"></circle>
				<line x1="12" y1="16" x2="12" y2="12"></line>
				<line x1="12" y1="8" x2="12.01" y2="8"></line>
			</svg>
		</span>
		<span class="sodek-gb-policies-title"><?php esc_html_e( 'Important Booking Information', 'glowbook' ); ?></span>
		<span class="sodek-gb-policies-arrow">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<polyline points="6 9 12 15 18 9"></polyline>
			</svg>
		</span>
	</button>
	<div id="sodek-gb-policies-content" class="sodek-gb-policies-content" aria-hidden="true">
		<div class="sodek-gb-policies-inner">
			<?php echo wp_kses_post( wpautop( $product_description ) ); ?>
		</div>
	</div>
</div>
<?php endif; ?>

<form class="cart sodek-gb-booking-cart sodek-gb-layout-<?php echo esc_attr( $product_layout ); ?>" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data">

	<div class="sodek-gb-product-booking-form sodek-gb-booking-flow" data-service-id="<?php echo esc_attr( $service_id ); ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-has-addons="<?php echo $has_addons ? 'yes' : 'no'; ?>" data-addon-only="<?php echo $is_addon_only ? 'yes' : 'no'; ?>" data-deposit-type="<?php echo esc_attr( $deposit_type ); ?>" data-deposit-value="<?php echo esc_attr( $deposit_value ); ?>" data-layout="<?php echo esc_attr( $product_layout ); ?>">
		<?php wp_nonce_field( 'sodek_gb_booking_form', 'sodek_gb_booking_nonce' ); ?>
		<input type="hidden" name="sodek_gb_service_id" value="<?php echo esc_attr( $service_id ); ?>">
		<input type="hidden" name="sodek_gb_booking_date" id="sodek_gb_booking_date" value="">
		<input type="hidden" name="sodek_gb_booking_time" id="sodek_gb_booking_time" value="">

		<!-- Sticky Price Header -->
		<div class="sodek-gb-price-header">
			<div class="sodek-gb-price-header-inner">
				<?php if ( $show_duration ) : ?>
				<div class="sodek-gb-service-quick-info">
					<span class="sodek-gb-service-duration-badge">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="12" cy="12" r="10"></circle>
							<polyline points="12 6 12 12 16 14"></polyline>
						</svg>
						<?php echo esc_html( $service['duration'] ); ?> <?php esc_html_e( 'min', 'glowbook' ); ?>
					</span>
				</div>
				<?php endif; ?>
				<div class="sodek-gb-running-total">
					<span class="sodek-gb-running-total-label"><?php esc_html_e( 'Total:', 'glowbook' ); ?></span>
					<span class="sodek-gb-running-total-value" data-base-price="<?php echo esc_attr( $service['price'] ); ?>"><?php echo wc_price( $service['price'] ); ?></span>
				</div>
			</div>
		</div>

		<!-- Progress Steps -->
		<div class="sodek-gb-booking-steps" role="navigation" aria-label="<?php esc_attr_e( 'Booking progress', 'glowbook' ); ?>">
			<?php if ( $has_addons ) : ?>
			<div class="sodek-gb-step sodek-gb-step-active" data-step="1">
				<span class="sodek-gb-step-number">1</span>
				<span class="sodek-gb-step-label"><?php esc_html_e( 'Extras', 'glowbook' ); ?></span>
			</div>
			<div class="sodek-gb-step-connector"></div>
			<div class="sodek-gb-step" data-step="2">
				<span class="sodek-gb-step-number">2</span>
				<span class="sodek-gb-step-label"><?php esc_html_e( 'Date', 'glowbook' ); ?></span>
			</div>
			<div class="sodek-gb-step-connector"></div>
			<div class="sodek-gb-step" data-step="3">
				<span class="sodek-gb-step-number">3</span>
				<span class="sodek-gb-step-label"><?php esc_html_e( 'Time', 'glowbook' ); ?></span>
			</div>
			<div class="sodek-gb-step-connector"></div>
			<div class="sodek-gb-step" data-step="4">
				<span class="sodek-gb-step-number">4</span>
				<span class="sodek-gb-step-label"><?php esc_html_e( 'Confirm', 'glowbook' ); ?></span>
			</div>
			<?php else : ?>
			<div class="sodek-gb-step sodek-gb-step-active" data-step="1">
				<span class="sodek-gb-step-number">1</span>
				<span class="sodek-gb-step-label"><?php esc_html_e( 'Date', 'glowbook' ); ?></span>
			</div>
			<div class="sodek-gb-step-connector"></div>
			<div class="sodek-gb-step" data-step="2">
				<span class="sodek-gb-step-number">2</span>
				<span class="sodek-gb-step-label"><?php esc_html_e( 'Time', 'glowbook' ); ?></span>
			</div>
			<div class="sodek-gb-step-connector"></div>
			<div class="sodek-gb-step" data-step="3">
				<span class="sodek-gb-step-number">3</span>
				<span class="sodek-gb-step-label"><?php esc_html_e( 'Confirm', 'glowbook' ); ?></span>
			</div>
			<?php endif; ?>
		</div>

		<?php if ( $has_addons ) : ?>
		<!-- Step 1: Add-ons Selection -->
		<div class="sodek-gb-booking-section sodek-gb-section-addons sodek-gb-section-active" data-section="addons">
			<div class="sodek-gb-section-header">
				<h3 class="sodek-gb-section-title">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M12 2v20M2 12h20"></path>
					</svg>
					<?php esc_html_e( 'Enhance Your Look', 'glowbook' ); ?>
				</h3>
				<p class="sodek-gb-section-subtitle"><?php esc_html_e( 'Add extras to make your appointment even better', 'glowbook' ); ?></p>
			</div>

			<div class="sodek-gb-addons-list">
				<?php foreach ( $addons as $addon ) : ?>
				<label class="sodek-gb-addon-card <?php echo ! empty( $addon['image_url'] ) ? 'has-image' : ''; ?>">
					<input type="radio" name="sodek_gb_addon_ids[]" value="<?php echo esc_attr( $addon['id'] ); ?>"
						   data-price="<?php echo esc_attr( $addon['price'] ); ?>"
						   data-duration="<?php echo esc_attr( $addon['duration'] ); ?>"
						   class="sodek-gb-addon-radio">
					<span class="sodek-gb-addon-card-inner">
						<?php if ( ! empty( $addon['image_url'] ) ) : ?>
						<span class="sodek-gb-addon-card-image">
							<img src="<?php echo esc_url( $addon['image_url'] ); ?>" alt="<?php echo esc_attr( $addon['title'] ); ?>">
						</span>
						<?php endif; ?>
						<span class="sodek-gb-addon-card-content">
							<span class="sodek-gb-addon-card-name"><?php echo esc_html( $addon['title'] ); ?></span>
							<?php if ( ! empty( $addon['description'] ) ) : ?>
							<span class="sodek-gb-addon-card-desc"><?php echo esc_html( $addon['description'] ); ?></span>
							<?php endif; ?>
							<span class="sodek-gb-addon-card-meta">
								<?php if ( $show_duration && ! empty( $addon['duration'] ) && $addon['duration'] > 0 ) : ?>
									<?php
									// Format duration (convert to hours if >= 60 min)
									$addon_duration = intval( $addon['duration'] );
									$duration_text = '';
									if ( $addon_duration >= 60 ) {
										$hours = floor( $addon_duration / 60 );
										$mins  = $addon_duration % 60;
										if ( $mins > 0 ) {
											$duration_text = $hours . ' hr ' . $mins . ' min';
										} else {
											$duration_text = $hours . ' ' . ( $hours > 1 ? 'hours' : 'hour' );
										}
									} else {
										$duration_text = $addon_duration . ' min';
									}
									?>
									<span class="sodek-gb-addon-card-price">+ <?php echo esc_html( $duration_text ); ?> @ <?php echo wc_price( $addon['price'] ); ?></span>
								<?php else : ?>
									<span class="sodek-gb-addon-card-price">+<?php echo wc_price( $addon['price'] ); ?></span>
								<?php endif; ?>
							</span>
						</span>
						<span class="sodek-gb-addon-card-check">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
								<polyline points="20 6 9 17 4 12"></polyline>
							</svg>
						</span>
					</span>
				</label>
				<?php endforeach; ?>
			</div>

			<div class="sodek-gb-addons-summary" style="display: none;">
				<div class="sodek-gb-addons-summary-inner">
					<span class="sodek-gb-addons-summary-count"></span>
					<span class="sodek-gb-addons-summary-total"></span>
				</div>
			</div>

			<div class="sodek-gb-section-actions">
				<button type="button" class="sodek-gb-btn-secondary sodek-gb-skip-addons">
					<?php esc_html_e( 'Skip extras', 'glowbook' ); ?>
				</button>
				<button type="button" class="sodek-gb-btn-primary sodek-gb-continue-to-date">
					<?php esc_html_e( 'Continue', 'glowbook' ); ?>
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M5 12h14M12 5l7 7-7 7"></path>
					</svg>
				</button>
			</div>
		</div>
		<?php endif; ?>

		<!-- Date Selection Step -->
		<div class="sodek-gb-booking-section sodek-gb-section-date <?php echo ! $has_addons ? 'sodek-gb-section-active' : ''; ?>" data-section="date">
			<div class="sodek-gb-section-header">
				<h3 class="sodek-gb-section-title">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
						<line x1="16" y1="2" x2="16" y2="6"></line>
						<line x1="8" y1="2" x2="8" y2="6"></line>
						<line x1="3" y1="10" x2="21" y2="10"></line>
					</svg>
					<?php esc_html_e( 'Pick Your Date', 'glowbook' ); ?>
				</h3>
				<p class="sodek-gb-section-subtitle"><?php esc_html_e( 'Choose when you\'d like to come in', 'glowbook' ); ?></p>
			</div>

			<div class="sodek-gb-date-picker-card">
				<div id="sodek-gb-calendar-inline" class="sodek-gb-calendar" role="application" aria-label="<?php esc_attr_e( 'Calendar', 'glowbook' ); ?>">
					<div class="sodek-gb-loading" aria-live="polite">
						<span class="sodek-gb-loading-spinner"></span>
						<?php esc_html_e( 'Loading available dates...', 'glowbook' ); ?>
					</div>
				</div>
			</div>

			<?php if ( $has_addons ) : ?>
			<div class="sodek-gb-section-actions">
				<button type="button" class="sodek-gb-btn-back sodek-gb-back-to-addons">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M19 12H5M12 19l-7-7 7-7"></path>
					</svg>
					<?php esc_html_e( 'Back', 'glowbook' ); ?>
				</button>
			</div>
			<?php endif; ?>
		</div>

		<!-- Time Selection Step -->
		<div class="sodek-gb-booking-section sodek-gb-section-time" data-section="time" style="display: none;">
			<div class="sodek-gb-section-header">
				<h3 class="sodek-gb-section-title">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<circle cx="12" cy="12" r="10"></circle>
						<polyline points="12 6 12 12 16 14"></polyline>
					</svg>
					<?php esc_html_e( 'Pick Your Time', 'glowbook' ); ?>
				</h3>
				<p class="sodek-gb-section-subtitle">
					<span class="sodek-gb-selected-date-display"></span>
				</p>
			</div>

			<div class="sodek-gb-time-picker-card">
				<div id="sodek-gb-time-slots" class="sodek-gb-time-slots" role="listbox" aria-label="<?php esc_attr_e( 'Available times', 'glowbook' ); ?>">
					<div class="sodek-gb-loading" aria-live="polite">
						<span class="sodek-gb-loading-spinner"></span>
						<?php esc_html_e( 'Loading available times...', 'glowbook' ); ?>
					</div>
				</div>
			</div>

			<div class="sodek-gb-section-actions">
				<button type="button" class="sodek-gb-btn-back sodek-gb-back-to-date">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M19 12H5M12 19l-7-7 7-7"></path>
					</svg>
					<?php esc_html_e( 'Change Date', 'glowbook' ); ?>
				</button>
			</div>
		</div>

		<!-- Confirmation Step -->
		<div class="sodek-gb-booking-section sodek-gb-section-confirm" data-section="confirm" style="display: none;">
			<div class="sodek-gb-section-header">
				<h3 class="sodek-gb-section-title">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
						<polyline points="22 4 12 14.01 9 11.01"></polyline>
					</svg>
					<?php esc_html_e( 'Confirm Your Booking', 'glowbook' ); ?>
				</h3>
				<p class="sodek-gb-section-subtitle"><?php esc_html_e( 'Review your appointment details', 'glowbook' ); ?></p>
			</div>

			<!-- Appointment Summary Card -->
			<div class="sodek-gb-confirmation-card">
				<div class="sodek-gb-confirmation-datetime">
					<div class="sodek-gb-confirmation-date">
						<span class="sodek-gb-confirmation-icon">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
								<line x1="16" y1="2" x2="16" y2="6"></line>
								<line x1="8" y1="2" x2="8" y2="6"></line>
								<line x1="3" y1="10" x2="21" y2="10"></line>
							</svg>
						</span>
						<span class="sodek-gb-summary-date"><?php esc_html_e( 'Date not selected', 'glowbook' ); ?></span>
					</div>
					<div class="sodek-gb-confirmation-time">
						<span class="sodek-gb-confirmation-icon">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<circle cx="12" cy="12" r="10"></circle>
								<polyline points="12 6 12 12 16 14"></polyline>
							</svg>
						</span>
						<span class="sodek-gb-summary-time"><?php esc_html_e( 'Time not selected', 'glowbook' ); ?></span>
					</div>
					<?php if ( $show_duration ) : ?>
					<div class="sodek-gb-confirmation-duration">
						<span class="sodek-gb-confirmation-icon">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
								<path d="M5 22h14"></path>
								<path d="M5 2h14"></path>
								<path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"></path>
								<path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"></path>
							</svg>
						</span>
						<span class="sodek-gb-summary-duration-value"><?php echo esc_html( $service['duration'] ); ?></span> <?php esc_html_e( 'minutes', 'glowbook' ); ?>
					</div>
					<?php endif; ?>
				</div>

				<?php if ( $has_addons ) : ?>
				<div class="sodek-gb-confirmation-addons" style="display: none;">
					<h4><?php esc_html_e( 'Selected Extras', 'glowbook' ); ?></h4>
					<ul class="sodek-gb-summary-addons-list"></ul>
				</div>
				<?php endif; ?>

				<!-- Pricing Breakdown -->
				<div class="sodek-gb-confirmation-pricing">
					<div class="sodek-gb-pricing-row">
						<span><?php esc_html_e( 'Service', 'glowbook' ); ?></span>
						<span><?php echo wc_price( $service['price'] ); ?></span>
					</div>
					<div class="sodek-gb-pricing-row sodek-gb-pricing-addons" style="display: none;">
						<span><?php esc_html_e( 'Extras', 'glowbook' ); ?></span>
						<span class="sodek-gb-summary-addons-price-value"></span>
					</div>
					<div class="sodek-gb-pricing-row sodek-gb-pricing-total">
						<span><?php esc_html_e( 'Total', 'glowbook' ); ?></span>
						<span class="sodek-gb-summary-total-value" data-base-price="<?php echo esc_attr( $service['price'] ); ?>"><?php echo wc_price( $service['price'] ); ?></span>
					</div>
				</div>
			</div>

			<!-- Special Notes -->
			<div class="sodek-gb-notes-card">
				<label for="sodek_gb_booking_notes" class="sodek-gb-notes-label">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
						<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
					</svg>
					<?php esc_html_e( 'Special Requests', 'glowbook' ); ?>
					<span class="sodek-gb-optional-badge"><?php esc_html_e( 'Optional', 'glowbook' ); ?></span>
				</label>
				<textarea name="sodek_gb_booking_notes" id="sodek_gb_booking_notes" rows="3" placeholder="<?php esc_attr_e( 'Tell us about your hair type, any allergies, or special requests...', 'glowbook' ); ?>"></textarea>
			</div>

			<!-- Deposit Selection - Hidden until there's an amount to pay -->
			<div class="sodek-gb-deposit-card" <?php echo ( $initial_deposit <= 0 && $is_addon_only ) ? 'style="display: none;"' : ''; ?>>
				<div class="sodek-gb-deposit-input-section">
					<label for="sodek_gb_custom_deposit" class="sodek-gb-deposit-label">
						<?php esc_html_e( 'Deposit Amount', 'glowbook' ); ?>
					</label>
					<div class="sodek-gb-deposit-input-wrapper">
						<span class="sodek-gb-currency-symbol"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
						<input type="number"
							   id="sodek_gb_custom_deposit"
							   name="sodek_gb_custom_deposit"
							   class="sodek-gb-deposit-input"
							   min="<?php echo esc_attr( $initial_deposit ); ?>"
							   max="<?php echo esc_attr( $service['price'] ); ?>"
							   value="<?php echo esc_attr( $initial_deposit ); ?>"
							   step="0.01"
							   data-min-deposit="<?php echo esc_attr( $initial_deposit ); ?>"
							   data-base-price="<?php echo esc_attr( $service['price'] ); ?>"
							   data-deposit-type="<?php echo esc_attr( $deposit_type ); ?>"
							   data-deposit-value="<?php echo esc_attr( $deposit_value ); ?>"
							   aria-describedby="sodek-gb-deposit-hint">
					</div>
					<p class="sodek-gb-deposit-hint" id="sodek-gb-deposit-hint">
						<?php
						printf(
							/* translators: 1: minimum deposit amount, 2: maximum (full price) amount */
							esc_html__( 'Min: %1$s — Max: %2$s', 'glowbook' ),
							'<span class="sodek-gb-min-deposit-display">' . wc_price( $initial_deposit ) . '</span>',
							'<span class="sodek-gb-max-deposit-display">' . wc_price( $service['price'] ) . '</span>'
						);
						?>
					</p>
				</div>

				<div class="sodek-gb-deposit-breakdown">
					<div class="sodek-gb-deposit-row sodek-gb-deposit-paying">
						<span><?php esc_html_e( 'Pay now', 'glowbook' ); ?></span>
						<strong class="sodek-gb-chosen-deposit"><?php echo wc_price( $initial_deposit ); ?></strong>
					</div>
					<div class="sodek-gb-deposit-row sodek-gb-deposit-remaining">
						<span><?php esc_html_e( 'Due at appointment', 'glowbook' ); ?></span>
						<strong class="sodek-gb-remaining-balance"><?php echo wc_price( $balance ); ?></strong>
					</div>
				</div>

				<p class="sodek-gb-deposit-error" style="display: none;" role="alert">
					<?php
					printf(
						/* translators: %s: minimum deposit amount */
						esc_html__( 'Minimum deposit required is %s', 'glowbook' ),
						'<strong class="sodek-gb-min-deposit-display">' . wc_price( $initial_deposit ) . '</strong>'
					);
					?>
				</p>
			</div>

			<div class="sodek-gb-section-actions sodek-gb-confirm-actions">
				<button type="button" class="sodek-gb-btn-back sodek-gb-back-to-time">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<path d="M19 12H5M12 19l-7-7 7-7"></path>
					</svg>
					<?php esc_html_e( 'Change Time', 'glowbook' ); ?>
				</button>
			</div>
		</div>

		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<?php
		// Square payment form for standalone mode
		if ( class_exists( 'Sodek_GB_Payment_Manager' ) && Sodek_GB_Payment_Manager::is_standalone_mode() ) :
			$square_gateway = Sodek_GB_Payment_Manager::get_gateway( 'square' );
			if ( $square_gateway && $square_gateway->is_available() ) :
		?>
		<!-- Square Payment Form -->
		<div class="sodek-gb-payment-section">
			<div class="sodek-gb-section-header">
				<h3 class="sodek-gb-section-title">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
						<line x1="1" y1="10" x2="23" y2="10"></line>
					</svg>
					<?php esc_html_e( 'Payment Details', 'glowbook' ); ?>
				</h3>
				<p class="sodek-gb-section-subtitle"><?php esc_html_e( 'Enter your card information', 'glowbook' ); ?></p>
			</div>

			<div class="sodek-gb-square-payment-wrapper">
				<div id="sodek-gb-square-card-container" class="sodek-gb-square-card-container">
					<!-- Square Web Payments SDK will inject the card form here -->
				</div>
				<div id="sodek-gb-payment-errors" class="sodek-gb-payment-errors" role="alert" aria-live="polite"></div>
			</div>

			<div class="sodek-gb-payment-secure-notice">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
					<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
				</svg>
				<?php esc_html_e( 'Your payment is secured with industry-standard encryption', 'glowbook' ); ?>
			</div>
		</div>
		<?php
			endif;
		endif;
		?>

		<!-- Submit Button - Fixed at bottom -->
		<div class="sodek-gb-submit-wrapper">
			<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="sodek-gb-book-button sodek-gb-submit-btn single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>" disabled aria-disabled="true">
				<span class="sodek-gb-btn-content">
					<span class="sodek-gb-btn-text"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></span>
					<span class="sodek-gb-btn-price"><?php echo $initial_deposit > 0 ? wc_price( $initial_deposit ) : wc_price( $service['price'] ); ?></span>
				</span>
				<span class="sodek-gb-btn-loading" aria-hidden="true">
					<svg class="sodek-gb-spinner" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
						<circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
						<path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
					</svg>
					<span class="sodek-gb-loading-text"><?php esc_html_e( 'Processing...', 'glowbook' ); ?></span>
				</span>
			</button>
			<p class="sodek-gb-secure-notice">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
					<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
				</svg>
				<?php
				if ( class_exists( 'Sodek_GB_Payment_Manager' ) && Sodek_GB_Payment_Manager::is_standalone_mode() ) {
					esc_html_e( 'Secure payment powered by Square', 'glowbook' );
				} else {
					esc_html_e( 'Secure booking powered by WooCommerce', 'glowbook' );
				}
				?>
			</p>
		</div>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</div>

</form>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
