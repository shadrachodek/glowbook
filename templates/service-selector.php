<?php
/**
 * Service selector template.
 *
 * @package GlowBook
 * @var array $services Array of services
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="sodek-gb-booking-form sodek-gb-service-selector" role="region" aria-label="<?php esc_attr_e( 'Service Booking', 'glowbook' ); ?>">
	<div class="sodek-gb-service-select-wrapper">
		<label for="sodek-gb-service-select"><?php esc_html_e( 'Select a Service:', 'glowbook' ); ?></label>
		<select id="sodek-gb-service-select" class="sodek-gb-service-select" aria-describedby="sodek-gb-service-help">
			<option value=""><?php esc_html_e( '— Choose a service —', 'glowbook' ); ?></option>
			<?php foreach ( $services as $service ) : ?>
				<option value="<?php echo esc_attr( $service['id'] ); ?>"
					data-duration="<?php echo esc_attr( $service['duration'] ); ?>"
					data-price="<?php echo esc_attr( $service['price'] ); ?>"
					data-deposit="<?php echo esc_attr( $service['deposit_amount'] ); ?>"
					data-product-id="<?php echo esc_attr( $service['wc_product_id'] ); ?>">
					<?php echo esc_html( $service['title'] ); ?> — <?php echo wp_strip_all_tags( wc_price( $service['price'] ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p id="sodek-gb-service-help" class="screen-reader-text"><?php esc_html_e( 'Select a service to see available dates and times', 'glowbook' ); ?></p>
	</div>

	<div class="sodek-gb-service-details" aria-hidden="true">
		<div class="sodek-gb-service-info sodek-gb-service-info-dynamic">
			<h3 class="sodek-gb-service-title"></h3>
			<p>
				<strong><?php esc_html_e( 'Duration:', 'glowbook' ); ?></strong>
				<span class="sodek-gb-service-duration"></span>
			</p>
			<div class="sodek-gb-price-info" aria-label="<?php esc_attr_e( 'Pricing Information', 'glowbook' ); ?>">
				<p class="sodek-gb-service-deposit"></p>
				<p class="sodek-gb-service-balance"></p>
			</div>
		</div>

		<form method="post" class="sodek-gb-booking-form-inner" action="<?php echo esc_url( wc_get_cart_url() ); ?>">
			<?php wp_nonce_field( 'sodek_gb_booking_form', 'sodek_gb_booking_nonce' ); ?>
			<input type="hidden" name="sodek_gb_service_id" id="sodek_gb_service_id" value="">
			<input type="hidden" name="sodek_gb_booking_date" id="sodek_gb_booking_date" value="">
			<input type="hidden" name="sodek_gb_booking_time" id="sodek_gb_booking_time" value="">
			<input type="hidden" name="add-to-cart" id="sodek_gb_product_id" value="">

			<fieldset class="sodek-gb-date-picker-section">
				<legend class="screen-reader-text"><?php esc_html_e( 'Step 1: Select a date', 'glowbook' ); ?></legend>
				<label id="sodek-gb-date-label-selector"><?php esc_html_e( 'Select Date:', 'glowbook' ); ?></label>
				<div class="sodek-gb-calendar" role="application" aria-labelledby="sodek-gb-date-label-selector"></div>
			</fieldset>

			<fieldset class="sodek-gb-time-slots-container" style="display: none;" aria-hidden="true">
				<legend class="screen-reader-text"><?php esc_html_e( 'Step 2: Select a time', 'glowbook' ); ?></legend>
				<label id="sodek-gb-time-label-selector"><?php esc_html_e( 'Select Time:', 'glowbook' ); ?></label>
				<div id="sodek-gb-time-slots" class="sodek-gb-time-slots" role="listbox" aria-labelledby="sodek-gb-time-label-selector"></div>
			</fieldset>

			<div class="sodek-gb-notes-field" style="display: none;" aria-hidden="true">
				<label for="sodek_gb_booking_notes_selector"><?php esc_html_e( 'Special Requests / Notes', 'glowbook' ); ?></label>
				<textarea
					name="sodek_gb_booking_notes"
					id="sodek_gb_booking_notes_selector"
					rows="4"
					placeholder="<?php esc_attr_e( 'Any special requests or notes for your appointment...', 'glowbook' ); ?>"
					aria-describedby="sodek-gb-notes-description-selector"
				></textarea>
				<p class="description" id="sodek-gb-notes-description-selector"><?php esc_html_e( 'Optional: Share any relevant details for your appointment.', 'glowbook' ); ?></p>
			</div>

			<div class="sodek-gb-booking-summary" style="display: none;" aria-hidden="true" role="status" aria-live="polite">
				<h4><?php esc_html_e( 'Your Appointment', 'glowbook' ); ?></h4>
				<p>
					<span class="sodek-gb-summary-service"></span>
				</p>
				<p>
					<span class="sodek-gb-summary-date"></span> <?php esc_html_e( 'at', 'glowbook' ); ?>
					<span class="sodek-gb-summary-time"></span>
				</p>
			</div>

			<button type="submit" class="sodek-gb-book-button button" disabled aria-disabled="true">
				<?php esc_html_e( 'Book & Pay Deposit', 'glowbook' ); ?>
			</button>
		</form>
	</div>
</div>

<script>
jQuery(function($) {
	$('#sodek-gb-service-select').on('change', function() {
		var $option = $(this).find(':selected');
		var $details = $('.sodek-gb-service-details');

		if (!$option.val()) {
			$details.removeClass('visible').attr('aria-hidden', 'true');
			return;
		}

		$('#sodek_gb_service_id').val($option.val());
		$('#sodek_gb_product_id').val($option.data('product-id'));

		var duration = $option.data('duration');
		var deposit = $option.data('deposit');
		var price = $option.data('price');
		var balance = price - deposit;

		$('.sodek-gb-service-title').text($option.text().split(' — ')[0]);
		$('.sodek-gb-service-duration').text(duration + ' <?php esc_html_e( 'minutes', 'glowbook' ); ?>');

		$details.addClass('visible').attr('aria-hidden', 'false');
	});
});
</script>
