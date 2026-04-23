<?php
/**
 * Admin new booking email (HTML).
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

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p><?php esc_html_e( 'You have received a new booking. Details below:', 'glowbook' ); ?></p>

<?php echo Sodek_GB_Emails::get_booking_details_html( $booking ); ?>

<h3><?php esc_html_e( 'Customer Details', 'glowbook' ); ?></h3>
<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #e5e5e5; margin-bottom: 20px;" border="1">
    <tbody>
        <tr>
            <th style="text-align: left; padding: 10px; width: 30%;"><?php esc_html_e( 'Name', 'glowbook' ); ?></th>
            <td style="padding: 10px;"><?php echo esc_html( $booking['customer_name'] ); ?></td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 10px;"><?php esc_html_e( 'Email', 'glowbook' ); ?></th>
            <td style="padding: 10px;"><a href="mailto:<?php echo esc_attr( $booking['customer_email'] ); ?>"><?php echo esc_html( $booking['customer_email'] ); ?></a></td>
        </tr>
        <?php if ( ! empty( $booking['customer_phone'] ) ) : ?>
        <tr>
            <th style="text-align: left; padding: 10px;"><?php esc_html_e( 'Phone', 'glowbook' ); ?></th>
            <td style="padding: 10px;"><a href="tel:<?php echo esc_attr( $booking['customer_phone'] ); ?>"><?php echo esc_html( $booking['customer_phone'] ); ?></a></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if ( ! empty( $booking['notes'] ) ) : ?>
<h3><?php esc_html_e( 'Customer Notes', 'glowbook' ); ?></h3>
<div style="background: #fff8e5; border-left: 4px solid #ffb900; padding: 15px; margin-bottom: 20px;">
    <?php echo esc_html( $booking['notes'] ); ?>
</div>
<?php endif; ?>

<p>
    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $booking['id'] . '&action=edit' ) ); ?>" style="display: inline-block; padding: 12px 24px; background-color: #2271b1; color: #ffffff; text-decoration: none; border-radius: 4px; margin-top: 10px;">
        <?php esc_html_e( 'View Booking', 'glowbook' ); ?>
    </a>
</p>

<?php if ( $additional_content ) : ?>
    <p style="margin-top: 20px;"><?php echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); ?></p>
<?php endif; ?>

<?php
do_action( 'woocommerce_email_footer', $email );
