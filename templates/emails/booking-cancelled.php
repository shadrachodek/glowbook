<?php
/**
 * Booking cancelled email (HTML).
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

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
    <?php
    printf(
        /* translators: %s: customer name */
        esc_html__( 'Hi %s,', 'glowbook' ),
        esc_html( $booking['customer_name'] )
    );
    ?>
</p>

<p><?php esc_html_e( 'Your appointment has been cancelled.', 'glowbook' ); ?></p>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #e5e5e5; margin: 20px 0;" border="1">
    <tbody>
        <tr>
            <th style="text-align: left; padding: 12px; width: 30%; background: #f8f8f8;">
                <?php esc_html_e( 'Service', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php echo esc_html( $booking['service']['title'] ); ?>
            </td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 12px; background: #f8f8f8;">
                <?php esc_html_e( 'Original Date', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ); ?>
            </td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 12px; background: #f8f8f8;">
                <?php esc_html_e( 'Original Time', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?>
            </td>
        </tr>
    </tbody>
</table>

<?php if ( ! empty( $refund_details['has_refund'] ) ) : ?>
<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #e5e5e5; margin: 20px 0;" border="1">
    <tbody>
        <tr>
            <th style="text-align: left; padding: 12px; width: 30%; background: #f8f8f8;">
                <?php esc_html_e( 'Refund Status', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php esc_html_e( 'A refund has been issued for this cancellation.', 'glowbook' ); ?>
            </td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 12px; background: #f8f8f8;">
                <?php esc_html_e( 'Refund Amount', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php echo wp_kses_post( $refund_details['formatted_amount'] ); ?>
            </td>
        </tr>
        <?php if ( ! empty( $refund_details['refunded_at'] ) ) : ?>
        <tr>
            <th style="text-align: left; padding: 12px; background: #f8f8f8;">
                <?php esc_html_e( 'Processed On', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $refund_details['refunded_at'] ) ) ); ?>
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if ( $additional_content ) : ?>
    <p><?php echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); ?></p>
<?php endif; ?>

<p>
    <?php
    echo ! empty( $refund_details['has_refund'] )
        ? esc_html__( 'If you did not request this cancellation or have questions about your refund, please contact us.', 'glowbook' )
        : esc_html__( 'If you did not request this cancellation or would like to rebook, please contact us.', 'glowbook' );
    ?>
</p>

<?php
do_action( 'woocommerce_email_footer', $email );
