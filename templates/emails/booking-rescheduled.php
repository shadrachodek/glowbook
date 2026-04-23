<?php
/**
 * Booking rescheduled email (HTML).
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

<p><?php esc_html_e( 'Your appointment has been rescheduled. Please review the updated details below.', 'glowbook' ); ?></p>

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
		<?php if ( ! empty( $old_date ) || ! empty( $old_time ) ) : ?>
		<tr>
			<th style="text-align: left; padding: 12px; background: #f8f8f8;">
				<?php esc_html_e( 'Previous Date & Time', 'glowbook' ); ?>
			</th>
			<td style="padding: 12px;">
				<?php if ( ! empty( $old_date ) ) : ?>
					<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $old_date ) ) ); ?>
				<?php endif; ?>
				<?php if ( ! empty( $old_time ) ) : ?>
					<?php esc_html_e( 'at', 'glowbook' ); ?>
					<?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $old_time ) ) ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<th style="text-align: left; padding: 12px; background: #f8f8f8;">
				<?php esc_html_e( 'Updated Date & Time', 'glowbook' ); ?>
			</th>
			<td style="padding: 12px;">
				<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $active_date ) ) ); ?>
				<?php esc_html_e( 'at', 'glowbook' ); ?>
				<?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $active_time ) ) ); ?>
			</td>
		</tr>
	</tbody>
</table>

<?php if ( $additional_content ) : ?>
	<p><?php echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); ?></p>
<?php endif; ?>

<p><?php esc_html_e( 'If you need anything else, please reply to this email or contact us.', 'glowbook' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );
