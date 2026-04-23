<?php
/**
 * Custom Product Image/Gallery for Bookable Services.
 *
 * Modern, clean gallery design optimized for service bookings.
 *
 * @package GlowBook
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure product exists.
if ( ! is_a( $product, 'WC_Product' ) ) {
	return;
}

$columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id = $product->get_image_id();
$attachment_ids    = $product->get_gallery_image_ids();
$has_gallery       = ! empty( $attachment_ids );

// Get service data for additional display
$service_id = get_post_meta( $product->get_id(), '_sodek_gb_service_id', true );
$service    = $service_id ? Sodek_GB_Service::get_service( $service_id ) : null;

$wrapper_classes = array(
	'sodek-gb-product-gallery',
	$has_gallery ? 'sodek-gb-has-gallery' : 'sodek-gb-single-image',
);
?>

<div class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">

	<?php if ( $post_thumbnail_id ) : ?>
		<!-- Main Image -->
		<div class="sodek-gb-gallery-main">
			<div class="sodek-gb-gallery-main-image">
				<?php
				$main_image_url = wp_get_attachment_image_url( $post_thumbnail_id, 'woocommerce_single' );
				$full_image_url = wp_get_attachment_image_url( $post_thumbnail_id, 'full' );
				?>
				<a href="<?php echo esc_url( $full_image_url ); ?>"
				   class="sodek-gb-gallery-zoom"
				   data-fancybox="gallery"
				   aria-label="<?php esc_attr_e( 'View full image', 'glowbook' ); ?>">
					<img src="<?php echo esc_url( $main_image_url ); ?>"
						 alt="<?php echo esc_attr( $product->get_name() ); ?>"
						 class="sodek-gb-main-img">
					<span class="sodek-gb-zoom-icon">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<circle cx="11" cy="11" r="8"></circle>
							<line x1="21" y1="21" x2="16.65" y2="16.65"></line>
							<line x1="11" y1="8" x2="11" y2="14"></line>
							<line x1="8" y1="11" x2="14" y2="11"></line>
						</svg>
					</span>
				</a>
			</div>

			<?php if ( $service ) : ?>
			<!-- Service Quick Info Overlay -->
			<div class="sodek-gb-gallery-service-info">
				<?php if ( ! empty( $service['categories'] ) && isset( $service['categories'][0]['name'] ) ) : ?>
				<span class="sodek-gb-service-category-badge">
					<?php echo esc_html( $service['categories'][0]['name'] ); ?>
				</span>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>

		<?php if ( $has_gallery ) : ?>
		<!-- Thumbnails -->
		<div class="sodek-gb-gallery-thumbs">
			<!-- Main image as first thumb -->
			<button type="button"
					class="sodek-gb-gallery-thumb sodek-gb-thumb-active"
					data-image="<?php echo esc_url( $main_image_url ); ?>"
					data-full="<?php echo esc_url( $full_image_url ); ?>"
					aria-label="<?php esc_attr_e( 'View main image', 'glowbook' ); ?>">
				<?php echo wp_get_attachment_image( $post_thumbnail_id, 'thumbnail', false, array( 'class' => 'sodek-gb-thumb-img' ) ); ?>
			</button>

			<?php foreach ( $attachment_ids as $attachment_id ) : ?>
				<?php
				$thumb_url = wp_get_attachment_image_url( $attachment_id, 'woocommerce_single' );
				$full_url  = wp_get_attachment_image_url( $attachment_id, 'full' );
				?>
				<button type="button"
						class="sodek-gb-gallery-thumb"
						data-image="<?php echo esc_url( $thumb_url ); ?>"
						data-full="<?php echo esc_url( $full_url ); ?>"
						aria-label="<?php esc_attr_e( 'View image', 'glowbook' ); ?>">
					<?php echo wp_get_attachment_image( $attachment_id, 'thumbnail', false, array( 'class' => 'sodek-gb-thumb-img' ) ); ?>
				</button>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

	<?php else : ?>
		<!-- Placeholder when no image -->
		<div class="sodek-gb-gallery-placeholder">
			<div class="sodek-gb-placeholder-inner">
				<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
					<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
					<circle cx="8.5" cy="8.5" r="1.5"></circle>
					<polyline points="21 15 16 10 5 21"></polyline>
				</svg>
				<span><?php esc_html_e( 'Service Image', 'glowbook' ); ?></span>
			</div>
		</div>
	<?php endif; ?>

</div>
