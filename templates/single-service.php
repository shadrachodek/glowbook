<?php
/**
 * Single Service Template.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$service_id     = get_the_ID();
	$price          = get_post_meta( $service_id, '_sodek_gb_price', true );
	$duration       = get_post_meta( $service_id, '_sodek_gb_duration', true );
	$deposit_type   = get_post_meta( $service_id, '_sodek_gb_deposit_type', true );
	$deposit_value  = get_post_meta( $service_id, '_sodek_gb_deposit_value', true );
	$deposit_amount = Sodek_GB_Service::calculate_deposit( $service_id );

	// Get booking page URL
	$booking_page_id = get_option( 'sodek_gb_booking_page_id' );
	if ( $booking_page_id ) {
		$booking_url = add_query_arg( 'service', $service_id, get_permalink( $booking_page_id ) );
	} else {
		$booking_slug = get_option( 'sodek_gb_booking_slug', 'book' );
		$booking_url  = home_url( '/' . $booking_slug . '/?service=' . $service_id );
	}
	?>

	<div class="sodek-gb-single-service">
		<div class="sodek-gb-service-container">

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="sodek-gb-service-image">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>

			<div class="sodek-gb-service-content">
				<header class="sodek-gb-service-header">
					<h1 class="sodek-gb-service-title"><?php the_title(); ?></h1>

					<div class="sodek-gb-service-meta">
						<?php if ( $price ) : ?>
							<span class="sodek-gb-service-price">
								<?php
								if ( function_exists( 'wc_price' ) ) {
									echo wc_price( $price );
								} else {
									echo '$' . number_format( (float) $price, 2 );
								}
								?>
							</span>
						<?php endif; ?>

						<?php if ( $duration ) : ?>
							<span class="sodek-gb-service-duration">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
									<circle cx="12" cy="12" r="10"></circle>
									<polyline points="12 6 12 12 16 14"></polyline>
								</svg>
								<?php
								printf(
									/* translators: %d: duration in minutes */
									esc_html__( '%d minutes', 'glowbook' ),
									absint( $duration )
								);
								?>
							</span>
						<?php endif; ?>
					</div>
				</header>

				<div class="sodek-gb-service-description">
					<?php the_content(); ?>
				</div>

				<?php if ( $deposit_amount && $deposit_amount < $price ) : ?>
					<div class="sodek-gb-service-deposit-info">
						<strong><?php esc_html_e( 'Deposit Required:', 'glowbook' ); ?></strong>
						<?php
						if ( function_exists( 'wc_price' ) ) {
							echo wc_price( $deposit_amount );
						} else {
							echo '$' . number_format( (float) $deposit_amount, 2 );
						}

						if ( 'percentage' === $deposit_type ) {
							printf( ' (%d%%)', absint( $deposit_value ) );
						}
						?>
						<p class="sodek-gb-deposit-note">
							<?php
							$balance = $price - $deposit_amount;
							printf(
								/* translators: %s: balance amount */
								esc_html__( 'Pay the remaining %s at your appointment.', 'glowbook' ),
								function_exists( 'wc_price' ) ? wc_price( $balance ) : '$' . number_format( $balance, 2 )
							);
							?>
						</p>
					</div>
				<?php endif; ?>

				<div class="sodek-gb-service-actions">
					<a href="<?php echo esc_url( $booking_url ); ?>" class="sodek-gb-book-button">
						<?php esc_html_e( 'Book Now', 'glowbook' ); ?>
					</a>
				</div>
			</div>

		</div>
	</div>

	<style>
		.sodek-gb-single-service {
			padding: 40px 20px;
			max-width: 1200px;
			margin: 0 auto;
		}

		.sodek-gb-service-container {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 40px;
			align-items: start;
		}

		@media (max-width: 768px) {
			.sodek-gb-service-container {
				grid-template-columns: 1fr;
			}
		}

		.sodek-gb-service-image img {
			width: 100%;
			height: auto;
			border-radius: 12px;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
		}

		.sodek-gb-service-header {
			margin-bottom: 24px;
		}

		.sodek-gb-service-title {
			font-size: 32px;
			font-weight: 700;
			margin: 0 0 16px 0;
			color: #1a1a1a;
		}

		.sodek-gb-service-meta {
			display: flex;
			align-items: center;
			gap: 20px;
			flex-wrap: wrap;
		}

		.sodek-gb-service-price {
			font-size: 24px;
			font-weight: 700;
			color: #C4A35A;
		}

		.sodek-gb-service-duration {
			display: flex;
			align-items: center;
			gap: 6px;
			color: #666;
			font-size: 15px;
		}

		.sodek-gb-service-duration svg {
			opacity: 0.7;
		}

		.sodek-gb-service-description {
			font-size: 16px;
			line-height: 1.7;
			color: #444;
			margin-bottom: 24px;
		}

		.sodek-gb-service-deposit-info {
			background: #f8f8f8;
			padding: 16px 20px;
			border-radius: 8px;
			margin-bottom: 24px;
			border-left: 4px solid #C4A35A;
		}

		.sodek-gb-deposit-note {
			margin: 8px 0 0;
			font-size: 14px;
			color: #666;
		}

		.sodek-gb-service-actions {
			margin-top: 24px;
		}

		.sodek-gb-book-button {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 16px 40px;
			background: #C4A35A;
			color: #fff;
			font-size: 16px;
			font-weight: 600;
			text-decoration: none;
			border-radius: 8px;
			transition: background 0.2s ease, transform 0.2s ease;
		}

		.sodek-gb-book-button:hover {
			background: #A88A45;
			color: #fff;
			transform: translateY(-2px);
		}
	</style>

	<?php
endwhile;

get_footer();
