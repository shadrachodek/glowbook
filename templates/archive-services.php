<?php
/**
 * Archive Services Template.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

get_header();

$booking_page_id = get_option( 'sodek_gb_booking_page_id' );
$booking_slug    = get_option( 'sodek_gb_booking_slug', 'book' );
?>

<div class="sodek-gb-services-archive">
	<div class="sodek-gb-archive-container">

		<header class="sodek-gb-archive-header">
			<h1 class="sodek-gb-archive-title"><?php esc_html_e( 'Our Services', 'glowbook' ); ?></h1>
			<p class="sodek-gb-archive-description">
				<?php esc_html_e( 'Browse our services and book your appointment online.', 'glowbook' ); ?>
			</p>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="sodek-gb-services-grid">
				<?php
				while ( have_posts() ) :
					the_post();

					$service_id     = get_the_ID();
					$price          = get_post_meta( $service_id, '_sodek_gb_price', true );
					$duration       = get_post_meta( $service_id, '_sodek_gb_duration', true );

					// Build booking URL
					if ( $booking_page_id ) {
						$booking_url = add_query_arg( 'service', $service_id, get_permalink( $booking_page_id ) );
					} else {
						$booking_url = home_url( '/' . $booking_slug . '/?service=' . $service_id );
					}
					?>

					<div class="sodek-gb-service-card">
						<?php if ( has_post_thumbnail() ) : ?>
							<div class="sodek-gb-service-card-image">
								<a href="<?php the_permalink(); ?>">
									<?php the_post_thumbnail( 'medium_large' ); ?>
								</a>
							</div>
						<?php endif; ?>

						<div class="sodek-gb-service-card-content">
							<h2 class="sodek-gb-service-card-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>

							<div class="sodek-gb-service-card-meta">
								<?php if ( $price ) : ?>
									<span class="sodek-gb-service-card-price">
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
									<span class="sodek-gb-service-card-duration">
										<?php
										printf(
											/* translators: %d: duration in minutes */
											esc_html__( '%d min', 'glowbook' ),
											absint( $duration )
										);
										?>
									</span>
								<?php endif; ?>
							</div>

							<?php if ( has_excerpt() ) : ?>
								<div class="sodek-gb-service-card-excerpt">
									<?php the_excerpt(); ?>
								</div>
							<?php endif; ?>

							<div class="sodek-gb-service-card-actions">
								<a href="<?php echo esc_url( $booking_url ); ?>" class="sodek-gb-book-button">
									<?php esc_html_e( 'Book Now', 'glowbook' ); ?>
								</a>
								<a href="<?php the_permalink(); ?>" class="sodek-gb-details-link">
									<?php esc_html_e( 'View Details', 'glowbook' ); ?>
								</a>
							</div>
						</div>
					</div>

				<?php endwhile; ?>
			</div>

			<?php the_posts_pagination(); ?>

		<?php else : ?>
			<p class="sodek-gb-no-services">
				<?php esc_html_e( 'No services available at this time.', 'glowbook' ); ?>
			</p>
		<?php endif; ?>

	</div>
</div>

<style>
	.sodek-gb-services-archive {
		padding: 40px 20px;
		max-width: 1200px;
		margin: 0 auto;
	}

	.sodek-gb-archive-header {
		text-align: center;
		margin-bottom: 40px;
	}

	.sodek-gb-archive-title {
		font-size: 36px;
		font-weight: 700;
		margin: 0 0 12px 0;
		color: #1a1a1a;
	}

	.sodek-gb-archive-description {
		font-size: 18px;
		color: #666;
		margin: 0;
	}

	.sodek-gb-services-grid {
		display: grid;
		grid-template-columns: repeat(3, 1fr);
		gap: 30px;
	}

	@media (max-width: 992px) {
		.sodek-gb-services-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}

	@media (max-width: 576px) {
		.sodek-gb-services-grid {
			grid-template-columns: 1fr;
		}
	}

	.sodek-gb-service-card {
		background: #fff;
		border-radius: 12px;
		overflow: hidden;
		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
		transition: transform 0.3s ease, box-shadow 0.3s ease;
	}

	.sodek-gb-service-card:hover {
		transform: translateY(-6px);
		box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
	}

	.sodek-gb-service-card-image {
		aspect-ratio: 4/3;
		overflow: hidden;
	}

	.sodek-gb-service-card-image img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: transform 0.3s ease;
	}

	.sodek-gb-service-card:hover .sodek-gb-service-card-image img {
		transform: scale(1.05);
	}

	.sodek-gb-service-card-content {
		padding: 24px;
	}

	.sodek-gb-service-card-title {
		font-size: 20px;
		font-weight: 700;
		margin: 0 0 12px 0;
	}

	.sodek-gb-service-card-title a {
		color: #1a1a1a;
		text-decoration: none;
	}

	.sodek-gb-service-card-title a:hover {
		color: #C4A35A;
	}

	.sodek-gb-service-card-meta {
		display: flex;
		align-items: center;
		gap: 16px;
		margin-bottom: 12px;
	}

	.sodek-gb-service-card-price {
		font-size: 18px;
		font-weight: 700;
		color: #C4A35A;
	}

	.sodek-gb-service-card-duration {
		font-size: 14px;
		color: #888;
		background: #f5f5f5;
		padding: 4px 10px;
		border-radius: 20px;
	}

	.sodek-gb-service-card-excerpt {
		font-size: 14px;
		color: #666;
		line-height: 1.6;
		margin-bottom: 16px;
	}

	.sodek-gb-service-card-excerpt p {
		margin: 0;
	}

	.sodek-gb-service-card-actions {
		display: flex;
		align-items: center;
		gap: 12px;
	}

	.sodek-gb-book-button {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		padding: 12px 24px;
		background: #C4A35A;
		color: #fff;
		font-size: 14px;
		font-weight: 600;
		text-decoration: none;
		border-radius: 6px;
		transition: background 0.2s ease;
	}

	.sodek-gb-book-button:hover {
		background: #A88A45;
		color: #fff;
	}

	.sodek-gb-details-link {
		font-size: 14px;
		color: #666;
		text-decoration: none;
	}

	.sodek-gb-details-link:hover {
		color: #C4A35A;
	}

	.sodek-gb-no-services {
		text-align: center;
		font-size: 18px;
		color: #666;
		padding: 60px 20px;
	}
</style>

<?php
get_footer();
