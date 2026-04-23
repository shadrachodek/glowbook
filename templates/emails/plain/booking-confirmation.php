<?php
/**
 * Booking confirmation email (Plain text).
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

$addons = ! empty( $booking['addons'] ) && is_array( $booking['addons'] ) ? $booking['addons'] : array();
$addon_duration = ! empty( $booking['addons_duration'] ) ? (int) $booking['addons_duration'] : 0;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

printf(
    /* translators: %s: customer name */
    esc_html__( 'Hi %s,', 'glowbook' ),
    esc_html( $booking['customer_name'] )
);
echo "\n\n";

echo esc_html__( 'Your appointment has been confirmed! Here are your booking details:', 'glowbook' );
echo "\n\n";

echo "----------------------------------------\n";

echo esc_html__( 'Service:', 'glowbook' ) . ' ' . esc_html( $booking['service']['title'] ) . "\n";
echo esc_html__( 'Date:', 'glowbook' ) . ' ' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ) . "\n";
echo esc_html__( 'Time:', 'glowbook' ) . ' ' . esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ) . "\n";
echo esc_html__( 'Duration:', 'glowbook' ) . ' ' . esc_html( (int) $booking['service']['duration'] + $addon_duration ) . ' ' . esc_html__( 'minutes', 'glowbook' ) . "\n";

if ( ! empty( $addons ) ) {
    $addon_lines = array_map(
        static function( $addon ) {
            $title = $addon['title'] ?? '';
            $price = isset( $addon['price'] ) ? wp_strip_all_tags( Sodek_GB_Emails::format_price( $addon['price'] ) ) : '';
            return trim( $title . ( $price ? ' (' . $price . ')' : '' ) );
        },
        $addons
    );
    echo esc_html__( 'Add-ons:', 'glowbook' ) . ' ' . esc_html( implode( ', ', array_filter( $addon_lines ) ) ) . "\n";
}

if ( $booking['total_price'] - $booking['deposit_amount'] > 0 ) {
    echo esc_html__( 'Deposit Paid:', 'glowbook' ) . ' ' . wp_strip_all_tags( Sodek_GB_Emails::format_price( $booking['deposit_amount'] ) ) . "\n";
    echo esc_html__( 'Balance Due at Appointment:', 'glowbook' ) . ' ' . wp_strip_all_tags( Sodek_GB_Emails::format_price( $booking['total_price'] - $booking['deposit_amount'] ) ) . "\n";
}

echo "----------------------------------------\n\n";

echo esc_html__( 'Important Information:', 'glowbook' ) . "\n";
echo "- " . esc_html__( 'Please arrive on time for your appointment.', 'glowbook' ) . "\n";
echo "- " . esc_html__( 'If you need to reschedule or cancel, please contact us as soon as possible.', 'glowbook' ) . "\n";

if ( $booking['total_price'] - $booking['deposit_amount'] > 0 ) {
    echo "- " . esc_html__( 'Please remember to bring the remaining balance to your appointment.', 'glowbook' ) . "\n";
}

echo "\n";

if ( $additional_content ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n";
}

echo esc_html__( 'We look forward to seeing you!', 'glowbook' );
echo "\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
