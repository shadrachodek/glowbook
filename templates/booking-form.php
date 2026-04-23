<?php
/**
 * Booking form template.
 *
 * @package GlowBook
 * @var array $service Service data
 */

defined( 'ABSPATH' ) || exit;

// Verify we have a valid WooCommerce product for checkout.
if ( empty( $service['wc_product_id'] ) ) {
	echo '<div class="sodek-gb-error woocommerce-error" role="alert">';
	esc_html_e( 'This service is not properly configured for booking. Please contact the administrator.', 'glowbook' );
	echo '</div>';
	return;
}

// Verify service has valid pricing.
if ( empty( $service['price'] ) || $service['price'] <= 0 ) {
	echo '<div class="sodek-gb-error woocommerce-error" role="alert">';
	esc_html_e( 'Service pricing is not configured. Please contact the administrator.', 'glowbook' );
	echo '</div>';
	return;
}

// Calculate balance.
$balance = $service['price'] - $service['deposit_amount'];
?>
<div class="sodek-gb-booking-form" data-service-id="<?php echo esc_attr( $service['id'] ); ?>" role="region" aria-label="<?php esc_attr_e( 'Service Booking', 'glowbook' ); ?>">
	<div class="sodek-gb-service-info">
		<h3><?php echo esc_html( $service['title'] ); ?></h3>

		<?php if ( $service['description'] ) : ?>
			<div class="sodek-gb-service-description">
				<?php echo wp_kses_post( wpautop( $service['description'] ) ); ?>
			</div>
		<?php endif; ?>

		<p>
			<strong><?php esc_html_e( 'Duration:', 'glowbook' ); ?></strong>
			<span><?php echo esc_html( $service['duration'] ); ?> <?php esc_html_e( 'minutes', 'glowbook' ); ?></span>
		</p>

		<div class="sodek-gb-price-info" aria-label="<?php esc_attr_e( 'Pricing Information', 'glowbook' ); ?>">
			<p>
				<strong><?php esc_html_e( 'Full Price:', 'glowbook' ); ?></strong>
				<span><?php echo wc_price( $service['price'] ); ?></span>
			</p>
			<p>
				<strong><?php esc_html_e( 'Deposit Required:', 'glowbook' ); ?></strong>
				<span><?php echo wc_price( $service['deposit_amount'] ); ?></span>
			</p>
			<?php if ( $balance > 0 ) : ?>
			<p class="sodek-gb-balance-info">
				<strong><?php esc_html_e( 'Balance due at appointment:', 'glowbook' ); ?></strong>
				<span><?php echo wc_price( $balance ); ?></span>
			</p>
			<?php endif; ?>
		</div>
	</div>

	<form method="post" class="sodek-gb-booking-form-inner cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', '' ) ); ?>">
		<?php wp_nonce_field( 'sodek_gb_booking_form', 'sodek_gb_booking_nonce' ); ?>
		<input type="hidden" name="sodek_gb_service_id" value="<?php echo esc_attr( $service['id'] ); ?>">
		<input type="hidden" name="sodek_gb_booking_date" id="sodek_gb_booking_date" value="">
		<input type="hidden" name="sodek_gb_booking_time" id="sodek_gb_booking_time" value="">
		<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $service['wc_product_id'] ); ?>">
		<input type="hidden" name="quantity" value="1">

		<?php
		// Get available add-ons for this service - shown first before date selection
		$addons = Sodek_GB_Addon::get_addons_for_service( $service['id'] );
		if ( ! empty( $addons ) ) :
		?>
		<fieldset class="sodek-gb-addons-section">
			<legend><?php esc_html_e( 'Enhance Your Service (Optional)', 'glowbook' ); ?></legend>
			<p class="description"><?php esc_html_e( 'Select any add-ons you\'d like to include with your appointment.', 'glowbook' ); ?></p>
			<div class="sodek-gb-addons-list">
				<?php foreach ( $addons as $addon ) : ?>
				<label class="sodek-gb-addon-item <?php echo ! empty( $addon['image_url'] ) ? 'has-image' : ''; ?>">
					<input type="checkbox" name="sodek_gb_addon_ids[]" value="<?php echo esc_attr( $addon['id'] ); ?>"
						   data-price="<?php echo esc_attr( $addon['price'] ); ?>"
						   data-duration="<?php echo esc_attr( $addon['duration'] ); ?>">
					<?php if ( ! empty( $addon['image_url'] ) ) : ?>
					<span class="sodek-gb-addon-image">
						<img src="<?php echo esc_url( $addon['image_url'] ); ?>" alt="<?php echo esc_attr( $addon['title'] ); ?>">
					</span>
					<?php endif; ?>
					<span class="sodek-gb-addon-info">
						<span class="sodek-gb-addon-name"><?php echo esc_html( $addon['title'] ); ?></span>
						<?php if ( $addon['description'] ) : ?>
							<span class="sodek-gb-addon-desc"><?php echo esc_html( $addon['description'] ); ?></span>
						<?php endif; ?>
						<span class="sodek-gb-addon-meta">
							<span class="sodek-gb-addon-price">+ <?php echo wc_price( $addon['price'] ); ?></span>
							<?php if ( $addon['duration'] > 0 ) : ?>
								<span class="sodek-gb-addon-duration">+ <?php echo esc_html( $addon['duration'] ); ?> <?php esc_html_e( 'min', 'glowbook' ); ?></span>
							<?php endif; ?>
						</span>
					</span>
				</label>
				<?php endforeach; ?>
			</div>
			<div class="sodek-gb-addons-total" style="display: none;">
				<strong><?php esc_html_e( 'Add-ons Total:', 'glowbook' ); ?></strong>
				<span class="sodek-gb-addons-total-price"></span>
				<span class="sodek-gb-addons-total-duration"></span>
			</div>
		</fieldset>
		<?php endif; ?>

		<fieldset class="sodek-gb-date-picker-section">
			<legend class="screen-reader-text"><?php esc_html_e( 'Step 1: Select a date', 'glowbook' ); ?></legend>
			<label id="sodek-gb-date-label"><?php esc_html_e( 'Select Date:', 'glowbook' ); ?></label>
			<div class="sodek-gb-calendar" role="application" aria-labelledby="sodek-gb-date-label">
				<div class="sodek-gb-loading" aria-live="polite"><?php esc_html_e( 'Loading calendar...', 'glowbook' ); ?></div>
			</div>
		</fieldset>

		<fieldset class="sodek-gb-time-slots-container" style="display: none;" aria-hidden="true">
			<legend class="screen-reader-text"><?php esc_html_e( 'Step 2: Select a time', 'glowbook' ); ?></legend>
			<label id="sodek-gb-time-label"><?php esc_html_e( 'Select Time:', 'glowbook' ); ?></label>
			<div id="sodek-gb-time-slots" class="sodek-gb-time-slots" role="listbox" aria-labelledby="sodek-gb-time-label"></div>
		</fieldset>

		<div class="sodek-gb-notes-field" style="display: none;" aria-hidden="true">
			<label for="sodek_gb_booking_notes"><?php esc_html_e( 'Special Requests / Notes', 'glowbook' ); ?></label>
			<textarea
				name="sodek_gb_booking_notes"
				id="sodek_gb_booking_notes"
				rows="4"
				placeholder="<?php esc_attr_e( 'Please share any relevant information about your hair (type, length, current condition) or special requests for your appointment...', 'glowbook' ); ?>"
				aria-describedby="sodek-gb-notes-description"
			></textarea>
			<p class="description" id="sodek-gb-notes-description"><?php esc_html_e( 'Optional: Help us prepare for your appointment by sharing details about your hair or any preferences.', 'glowbook' ); ?></p>
		</div>

		<div class="sodek-gb-booking-summary" style="display: none;" aria-hidden="true" role="status" aria-live="polite">
			<h4><?php esc_html_e( 'Your Appointment', 'glowbook' ); ?></h4>
			<p>
				<strong><?php echo esc_html( $service['title'] ); ?></strong>
			</p>
			<p>
				<span class="sodek-gb-summary-date"></span> <?php esc_html_e( 'at', 'glowbook' ); ?>
				<span class="sodek-gb-summary-time"></span>
			</p>
			<p class="sodek-gb-summary-duration">
				<?php esc_html_e( 'Duration:', 'glowbook' ); ?>
				<span class="sodek-gb-summary-duration-value"><?php echo esc_html( $service['duration'] ); ?></span> <?php esc_html_e( 'min', 'glowbook' ); ?>
			</p>
			<div class="sodek-gb-summary-addons" style="display: none;">
				<p><strong><?php esc_html_e( 'Add-ons:', 'glowbook' ); ?></strong></p>
				<ul class="sodek-gb-summary-addons-list"></ul>
			</div>
			<div class="sodek-gb-summary-pricing">
				<p>
					<?php esc_html_e( 'Service Price:', 'glowbook' ); ?>
					<span><?php echo wc_price( $service['price'] ); ?></span>
				</p>
				<p class="sodek-gb-summary-addons-price" style="display: none;">
					<?php esc_html_e( 'Add-ons:', 'glowbook' ); ?>
					<span class="sodek-gb-summary-addons-price-value"></span>
				</p>
				<p class="sodek-gb-summary-total">
					<strong><?php esc_html_e( 'Total:', 'glowbook' ); ?></strong>
					<strong class="sodek-gb-summary-total-value"
					        data-base-price="<?php echo esc_attr( $service['price'] ); ?>">
						<?php echo wc_price( $service['price'] ); ?>
					</strong>
				</p>
			</div>

			<!-- Flexible Deposit Section -->
			<div class="sodek-gb-deposit-section">
				<h5><?php esc_html_e( 'Choose Your Deposit Amount', 'glowbook' ); ?></h5>
				<p class="description"><?php esc_html_e( 'Pay the minimum deposit or more to reduce your balance at the appointment.', 'glowbook' ); ?></p>

				<div class="sodek-gb-deposit-input-wrapper">
					<label for="sodek_gb_custom_deposit" class="screen-reader-text"><?php esc_html_e( 'Deposit amount', 'glowbook' ); ?></label>
					<span class="sodek-gb-currency-symbol"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
					<input type="number"
					       id="sodek_gb_custom_deposit"
					       name="sodek_gb_custom_deposit"
					       class="sodek-gb-deposit-input"
					       min="<?php echo esc_attr( $service['deposit_amount'] ); ?>"
					       max="<?php echo esc_attr( $service['price'] ); ?>"
					       value="<?php echo esc_attr( $service['deposit_amount'] ); ?>"
					       step="0.01"
					       data-min-deposit="<?php echo esc_attr( $service['deposit_amount'] ); ?>"
					       data-base-price="<?php echo esc_attr( $service['price'] ); ?>"
					       data-deposit-type="<?php echo esc_attr( get_post_meta( $service['id'], '_sodek_gb_deposit_type', true ) ?: 'percentage' ); ?>"
					       data-deposit-value="<?php echo esc_attr( get_post_meta( $service['id'], '_sodek_gb_deposit_value', true ) ?: 50 ); ?>"
					       aria-describedby="sodek-gb-deposit-range-info">
					<span id="sodek-gb-deposit-range-info" class="sodek-gb-deposit-range-info">
						<?php
						printf(
							/* translators: 1: minimum deposit amount, 2: maximum (full price) amount */
							esc_html__( 'Min: %1$s — Full: %2$s', 'glowbook' ),
							wc_price( $service['deposit_amount'] ),
							wc_price( $service['price'] )
						);
						?>
					</span>
				</div>

				<div class="sodek-gb-deposit-quick-options">
					<button type="button" class="sodek-gb-deposit-option" data-amount="min">
						<?php esc_html_e( 'Minimum', 'glowbook' ); ?>
					</button>
					<button type="button" class="sodek-gb-deposit-option" data-amount="50">
						<?php esc_html_e( '50%', 'glowbook' ); ?>
					</button>
					<button type="button" class="sodek-gb-deposit-option" data-amount="75">
						<?php esc_html_e( '75%', 'glowbook' ); ?>
					</button>
					<button type="button" class="sodek-gb-deposit-option" data-amount="full">
						<?php esc_html_e( 'Pay in Full', 'glowbook' ); ?>
					</button>
				</div>

				<div class="sodek-gb-deposit-summary-box">
					<div class="sodek-gb-deposit-paying">
						<span><?php esc_html_e( 'Paying Now:', 'glowbook' ); ?></span>
						<strong class="sodek-gb-chosen-deposit"><?php echo wc_price( $service['deposit_amount'] ); ?></strong>
					</div>
					<div class="sodek-gb-deposit-remaining">
						<span><?php esc_html_e( 'Balance at Appointment:', 'glowbook' ); ?></span>
						<strong class="sodek-gb-remaining-balance"><?php echo wc_price( $balance ); ?></strong>
					</div>
				</div>
			</div>
		</div>

		<button type="submit" class="sodek-gb-book-button button" disabled aria-disabled="true">
			<?php esc_html_e( 'Book & Pay Deposit', 'glowbook' ); ?>
		</button>
	</form>
</div>
