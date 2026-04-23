<?php
/**
 * Booking reminder email (HTML).
 *
 * @package GlowBook
 * @var array  $booking            Booking data.
 * @var string $reminder_type      Reminder type (24h, 2h, etc.).
 * @var string $email_heading      Email heading.
 * @var string $additional_content Additional content.
 * @var bool   $sent_to_admin      Sent to admin.
 * @var bool   $plain_text         Plain text.
 * @var object $email              Email object.
 */

defined( 'ABSPATH' ) || exit;

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

<p>
    <?php
    printf(
        /* translators: %1$s: service name, %2$s: date, %3$s: time */
        esc_html__( 'This is a friendly reminder that your %1$s appointment is scheduled for %2$s at %3$s.', 'glowbook' ),
        '<strong>' . esc_html( $booking['service']['title'] ) . '</strong>',
        '<strong>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ) . '</strong>',
        '<strong>' . esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ) . '</strong>'
    );
    ?>
</p>

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
                <?php esc_html_e( 'Date & Time', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ); ?>
                <?php esc_html_e( 'at', 'glowbook' ); ?>
                <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?>
            </td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 12px; background: #f8f8f8;">
                <?php esc_html_e( 'Duration', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php echo esc_html( $booking['service']['duration'] ); ?> <?php esc_html_e( 'minutes', 'glowbook' ); ?>
            </td>
        </tr>
        <?php if ( $booking['total_price'] - $booking['deposit_amount'] > 0 ) : ?>
        <tr>
            <th style="text-align: left; padding: 12px; background: #fff3cd; color: #856404;">
                <?php esc_html_e( 'Balance Due', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px; background: #fff3cd; color: #856404; font-weight: bold;">
                <?php echo wp_kses_post( Sodek_GB_Emails::format_price( $booking['total_price'] - $booking['deposit_amount'] ) ); ?>
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if ( $additional_content ) : ?>
    <p><?php echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); ?></p>
<?php endif; ?>

<p><?php esc_html_e( 'See you soon!', 'glowbook' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );
