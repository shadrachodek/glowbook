<?php
/**
 * Admin new booking email (Plain text).
 *
 * @package GlowBook
 * @var array  $booking            Booking data
 * @var string $email_heading      Email heading
 * @var string $additional_content Additional content
 * @var bool   $sent_to_admin      Whether sent to admin
 * @var bool   $plain_text         Whether plain text
 * @var object $email              Email object
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'You have received a new booking. Details below:', 'glowbook' ) . "\n\n";

echo "----------------------------------------\n";
echo esc_html__( 'Booking Details', 'glowbook' ) . "\n";
echo "----------------------------------------\n";
echo esc_html__( 'Service:', 'glowbook' ) . ' ' . esc_html( $booking['service']['title'] ) . "\n";
echo esc_html__( 'Date:', 'glowbook' ) . ' ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ) . "\n";
echo esc_html__( 'Time:', 'glowbook' ) . ' ' . esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ) . "\n";
echo esc_html__( 'Duration:', 'glowbook' ) . ' ' . esc_html( $booking['service']['duration'] ) . ' ' . esc_html__( 'minutes', 'glowbook' ) . "\n";

$balance = floatval( $booking['total_price'] ) - floatval( $booking['deposit_amount'] );
if ( $balance > 0 ) {
    echo esc_html__( 'Deposit:', 'glowbook' ) . ' ' . wp_strip_all_tags( Sodek_GB_Emails::format_price( $booking['deposit_amount'] ) ) . "\n";
    echo esc_html__( 'Balance Due:', 'glowbook' ) . ' ' . wp_strip_all_tags( Sodek_GB_Emails::format_price( $balance ) ) . "\n";
}

echo "\n----------------------------------------\n";
echo esc_html__( 'Customer Details', 'glowbook' ) . "\n";
echo "----------------------------------------\n";
echo esc_html__( 'Name:', 'glowbook' ) . ' ' . esc_html( $booking['customer_name'] ) . "\n";
echo esc_html__( 'Email:', 'glowbook' ) . ' ' . esc_html( $booking['customer_email'] ) . "\n";
if ( ! empty( $booking['customer_phone'] ) ) {
    echo esc_html__( 'Phone:', 'glowbook' ) . ' ' . esc_html( $booking['customer_phone'] ) . "\n";
}

if ( ! empty( $booking['notes'] ) ) {
    echo "\n----------------------------------------\n";
    echo esc_html__( 'Customer Notes', 'glowbook' ) . "\n";
    echo "----------------------------------------\n";
    echo esc_html( $booking['notes'] ) . "\n";
}

echo "\n" . esc_html__( 'View Booking:', 'glowbook' ) . ' ' . esc_url( admin_url( 'post.php?post=' . $booking['id'] . '&action=edit' ) ) . "\n";

if ( $additional_content ) {
    echo "\n" . esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n";
}

echo "\n----------------------------------------\n";
echo esc_html( get_bloginfo( 'name' ) ) . "\n";
