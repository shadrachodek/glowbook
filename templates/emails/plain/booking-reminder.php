<?php
/**
 * Booking reminder email (Plain text).
 *
 * @package GlowBook
 * @var array  $booking            Booking data.
 * @var string $reminder_type      Reminder type.
 * @var string $email_heading      Email heading.
 * @var string $additional_content Additional content.
 * @var bool   $sent_to_admin      Sent to admin.
 * @var bool   $plain_text         Plain text.
 * @var object $email              Email object.
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

printf(
    /* translators: %s: customer name */
    esc_html__( 'Hi %s,', 'glowbook' ),
    esc_html( $booking['customer_name'] )
);
echo "\n\n";

printf(
    /* translators: %1$s: service name, %2$s: date, %3$s: time */
    esc_html__( 'This is a friendly reminder that your %1$s appointment is scheduled for %2$s at %3$s.', 'glowbook' ),
    esc_html( $booking['service']['title'] ),
    esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ),
    esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) )
);
echo "\n\n";

echo "----------------------------------------\n";

echo esc_html__( 'Service:', 'glowbook' ) . ' ' . esc_html( $booking['service']['title'] ) . "\n";
echo esc_html__( 'Date & Time:', 'glowbook' ) . ' ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ) . ' ' . esc_html__( 'at', 'glowbook' ) . ' ' . esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ) . "\n";
echo esc_html__( 'Duration:', 'glowbook' ) . ' ' . esc_html( $booking['service']['duration'] ) . ' ' . esc_html__( 'minutes', 'glowbook' ) . "\n";

if ( $booking['total_price'] - $booking['deposit_amount'] > 0 ) {
    echo esc_html__( 'Balance Due:', 'glowbook' ) . ' ' . wp_strip_all_tags( Sodek_GB_Emails::format_price( $booking['total_price'] - $booking['deposit_amount'] ) ) . "\n";
}

echo "----------------------------------------\n\n";

if ( $additional_content ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n";
}

echo esc_html__( 'See you soon!', 'glowbook' );
echo "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
