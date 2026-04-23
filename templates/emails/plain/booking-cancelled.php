<?php
/**
 * Booking cancelled email (Plain text).
 *
 * @package GlowBook
 * @var array  $booking            Booking data.
 * @var string $email_heading      Email heading.
 * @var string $additional_content Additional content.
 * @var bool   $sent_to_admin      Sent to admin.
 * @var bool   $plain_text         Plain text.
 * @var object $email              Email object.
 */

defined( 'ABSPATH' ) || exit;

$refund_details = isset( $refund_details ) && is_array( $refund_details ) ? $refund_details : Sodek_GB_Emails::get_refund_details( $booking['id'] );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

printf(
    /* translators: %s: customer name */
    esc_html__( 'Hi %s,', 'glowbook' ),
    esc_html( $booking['customer_name'] )
);
echo "\n\n";

echo esc_html__( 'Your appointment has been cancelled.', 'glowbook' );
echo "\n\n";

echo "----------------------------------------\n";

echo esc_html__( 'Service:', 'glowbook' ) . ' ' . esc_html( $booking['service']['title'] ) . "\n";
echo esc_html__( 'Original Date:', 'glowbook' ) . ' ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ) . "\n";
echo esc_html__( 'Original Time:', 'glowbook' ) . ' ' . esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ) . "\n";

if ( ! empty( $refund_details['has_refund'] ) ) {
    echo esc_html__( 'Refund Status:', 'glowbook' ) . ' ' . esc_html__( 'A refund has been issued for this cancellation.', 'glowbook' ) . "\n";
    echo esc_html__( 'Refund Amount:', 'glowbook' ) . ' ' . wp_strip_all_tags( $refund_details['formatted_amount'] ) . "\n";
    if ( ! empty( $refund_details['refunded_at'] ) ) {
        echo esc_html__( 'Processed On:', 'glowbook' ) . ' ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $refund_details['refunded_at'] ) ) ) . "\n";
    }
}

echo "----------------------------------------\n\n";

if ( $additional_content ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n";
}

echo ! empty( $refund_details['has_refund'] )
    ? esc_html__( 'If you did not request this cancellation or have questions about your refund, please contact us.', 'glowbook' )
    : esc_html__( 'If you did not request this cancellation or would like to rebook, please contact us.', 'glowbook' );
echo "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
