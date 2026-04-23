<?php
/**
 * Booking confirmation email (HTML).
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

<p><?php esc_html_e( 'Your appointment has been confirmed! Here are your booking details:', 'glowbook' ); ?></p>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #e5e5e5; margin-bottom: 20px;" border="1">
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
                <?php esc_html_e( 'Date', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ); ?>
            </td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 12px; background: #f8f8f8;">
                <?php esc_html_e( 'Time', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?>
            </td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 12px; background: #f8f8f8;">
                <?php esc_html_e( 'Duration', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php echo esc_html( (int) $booking['service']['duration'] + $addon_duration ); ?> <?php esc_html_e( 'minutes', 'glowbook' ); ?>
            </td>
        </tr>
        <?php if ( ! empty( $addons ) ) : ?>
        <tr>
            <th style="text-align: left; padding: 12px; background: #f8f8f8;">
                <?php esc_html_e( 'Add-ons', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php
                $addon_lines = array_map(
                    static function( $addon ) {
                        $title = $addon['title'] ?? '';
                        $price = isset( $addon['price'] ) ? wp_strip_all_tags( Sodek_GB_Emails::format_price( $addon['price'] ) ) : '';
                        return trim( $title . ( $price ? ' (' . $price . ')' : '' ) );
                    },
                    $addons
                );
                echo esc_html( implode( ', ', array_filter( $addon_lines ) ) );
                ?>
            </td>
        </tr>
        <?php endif; ?>
        <?php if ( $booking['total_price'] - $booking['deposit_amount'] > 0 ) : ?>
        <tr>
            <th style="text-align: left; padding: 12px; background: #f8f8f8;">
                <?php esc_html_e( 'Deposit Paid', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px;">
                <?php echo wp_kses_post( Sodek_GB_Emails::format_price( $booking['deposit_amount'] ) ); ?>
            </td>
        </tr>
        <tr>
            <th style="text-align: left; padding: 12px; background: #fff3cd; color: #856404;">
                <?php esc_html_e( 'Balance Due at Appointment', 'glowbook' ); ?>
            </th>
            <td style="padding: 12px; background: #fff3cd; color: #856404; font-weight: bold;">
                <?php echo wp_kses_post( Sodek_GB_Emails::format_price( $booking['total_price'] - $booking['deposit_amount'] ) ); ?>
            </td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>

<h3 style="margin-top: 30px;"><?php esc_html_e( 'Important Information', 'glowbook' ); ?></h3>

<ul>
    <li><?php esc_html_e( 'Please arrive on time for your appointment.', 'glowbook' ); ?></li>
    <li><?php esc_html_e( 'If you need to reschedule or cancel, please contact us as soon as possible.', 'glowbook' ); ?></li>
    <?php if ( $booking['total_price'] - $booking['deposit_amount'] > 0 ) : ?>
    <li><?php esc_html_e( 'Please remember to bring the remaining balance to your appointment.', 'glowbook' ); ?></li>
    <?php endif; ?>
</ul>

<div style="margin: 25px 0; text-align: center;">
    <p style="margin-bottom: 15px;"><strong><?php esc_html_e( 'Add to Your Calendar:', 'glowbook' ); ?></strong></p>
    <a href="<?php echo esc_url( Sodek_GB_Confirmation::get_ics_url( $booking['id'] ) ); ?>" style="display: inline-block; padding: 12px 24px; background-color: #2271b1; color: #ffffff; text-decoration: none; border-radius: 4px; margin-right: 10px;">
        <?php esc_html_e( 'Download Calendar File (.ics)', 'glowbook' ); ?>
    </a>
    <a href="<?php echo esc_url( Sodek_GB_Confirmation::get_google_calendar_url( $booking ) ); ?>" style="display: inline-block; padding: 12px 24px; background-color: #ffffff; color: #333333; text-decoration: none; border-radius: 4px; border: 1px solid #dddddd;" target="_blank">
        <?php esc_html_e( 'Add to Google Calendar', 'glowbook' ); ?>
    </a>
</div>

<?php if ( $additional_content ) : ?>
    <p><?php echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); ?></p>
<?php endif; ?>

<p><?php esc_html_e( 'We look forward to seeing you!', 'glowbook' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );
