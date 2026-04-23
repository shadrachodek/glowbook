<?php
/**
 * Booking rescheduled email (Plain text).
 *
 * @package GlowBook
 * @var array  $booking            Booking data.
 * @var string $old_date           Old date.
 * @var string $old_time           Old time.
 * @var string $new_date           New date.
 * @var string $new_time           New time.
 * @var string $email_heading      Email heading.
 * @var string $additional_content Additional content.
 * @var bool   $sent_to_admin      Sent to admin.
 * @var bool   $plain_text         Plain text.
 * @var object $email              Email object.
 */

defined( 'ABSPATH' ) || exit;

$active_date = $new_date ?: ( $booking['booking_date'] ?? '' );
$active_time = $new_time ?: ( $booking['start_time'] ?? '' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

printf(
	/* translators: %s: customer name */
	esc_html__( 'Hi %s,', 'glowbook' ),
	esc_html( $booking['customer_name'] )
);
echo "\n\n";

echo esc_html__( 'Your appointment has been rescheduled. Please review the updated details below.', 'glowbook' );
echo "\n\n";

echo "----------------------------------------\n";
echo esc_html__( 'Service:', 'glowbook' ) . ' ' . esc_html( $booking['service']['title'] ) . "\n";

if ( ! empty( $old_date ) || ! empty( $old_time ) ) {
	echo esc_html__( 'Previous Date & Time:', 'glowbook' ) . ' ';
	if ( ! empty( $old_date ) ) {
		echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $old_date ) ) );
	}
	if ( ! empty( $old_time ) ) {
		echo ' ' . esc_html__( 'at', 'glowbook' ) . ' ' . esc_html( date_i18n( get_option( 'time_format' ), strtotime( $old_time ) ) );
	}
	echo "\n";
}

echo esc_html__( 'Updated Date & Time:', 'glowbook' ) . ' ';
echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $active_date ) ) );
echo ' ' . esc_html__( 'at', 'glowbook' ) . ' ' . esc_html( date_i18n( get_option( 'time_format' ), strtotime( $active_time ) ) ) . "\n";
echo "----------------------------------------\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n";
}

echo esc_html__( 'If you need anything else, please reply to this email or contact us.', 'glowbook' );
echo "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
