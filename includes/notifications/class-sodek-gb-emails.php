<?php
/**
 * Email notification handling.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Emails class.
 */
class Sodek_GB_Emails {

	/**
	 * Initialize.
	 */
	public static function init() {
		// Register custom emails with WooCommerce when available.
		add_filter( 'woocommerce_email_classes', array( __CLASS__, 'register_emails' ) );

		// Provide fallback wrappers when WooCommerce mail templates are unavailable.
		add_action( 'woocommerce_email_header', array( __CLASS__, 'render_email_header' ), 10, 2 );
		add_action( 'woocommerce_email_footer', array( __CLASS__, 'render_email_footer' ), 10, 1 );
		add_filter( 'woocommerce_email_footer_text', array( __CLASS__, 'get_email_footer_text' ) );

		// Email actions.
		add_action( 'sodek_gb_booking_confirmed', array( __CLASS__, 'send_confirmation' ), 10, 2 );
		add_action( 'sodek_gb_booking_rescheduled', array( __CLASS__, 'send_rescheduled' ), 10, 5 );
		add_action( 'sodek_gb_booking_status_changed', array( __CLASS__, 'send_status_update' ), 10, 2 );
	}

	/**
	 * Register custom email classes.
	 *
	 * @param array $emails Email classes.
	 * @return array
	 */
	public static function register_emails( $emails ) {
		if ( ! class_exists( 'WC_Email' ) ) {
			return $emails;
		}

		require_once SODEK_GB_PLUGIN_DIR . 'includes/notifications/emails/class-sodek-gb-email-booking-confirmation.php';
		require_once SODEK_GB_PLUGIN_DIR . 'includes/notifications/emails/class-sodek-gb-email-booking-reminder.php';
		require_once SODEK_GB_PLUGIN_DIR . 'includes/notifications/emails/class-sodek-gb-email-booking-cancelled.php';
		require_once SODEK_GB_PLUGIN_DIR . 'includes/notifications/emails/class-sodek-gb-email-booking-rescheduled.php';
		require_once SODEK_GB_PLUGIN_DIR . 'includes/notifications/emails/class-sodek-gb-email-admin-new-booking.php';

		$emails['Sodek_GB_Email_Booking_Confirmation'] = new Sodek_GB_Email_Booking_Confirmation();
		$emails['Sodek_GB_Email_Booking_Reminder']     = new Sodek_GB_Email_Booking_Reminder();
		$emails['Sodek_GB_Email_Booking_Cancelled']    = new Sodek_GB_Email_Booking_Cancelled();
		$emails['Sodek_GB_Email_Booking_Rescheduled']  = new Sodek_GB_Email_Booking_Rescheduled();
		$emails['Sodek_GB_Email_Admin_New_Booking']    = new Sodek_GB_Email_Admin_New_Booking();

		return $emails;
	}

	/**
	 * Send booking confirmation email.
	 *
	 * @param int      $booking_id Booking ID.
	 * @param int|null $order_id   Order ID (optional for standalone mode).
	 */
	public static function send_confirmation( $booking_id, $order_id = null ) {
		if ( self::has_woocommerce_mailer() ) {
			do_action( 'sodek_gb_email_booking_confirmation', $booking_id, $order_id );
			do_action( 'sodek_gb_email_admin_new_booking', $booking_id, $order_id );
			return;
		}

		self::send_standalone_email( 'booking_confirmation', $booking_id );
		self::send_standalone_email( 'admin_new_booking', $booking_id );
	}

	/**
	 * Send rescheduled email.
	 *
	 * @param int         $booking_id Booking ID.
	 * @param mixed       $arg2       Old date or booking array depending on caller.
	 * @param string|null $arg3       Old time or new date depending on caller.
	 * @param string|null $arg4       New date or new time depending on caller.
	 * @param string|null $arg5       New time (portal flow).
	 */
	public static function send_rescheduled( $booking_id, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = null ) {
		$context = self::normalize_reschedule_context( $booking_id, $arg2, $arg3, $arg4, $arg5 );

		if ( self::has_woocommerce_mailer() ) {
			do_action(
				'sodek_gb_email_booking_rescheduled',
				$booking_id,
				$context['old_date'],
				$context['old_time'],
				$context['new_date'],
				$context['new_time']
			);
			return;
		}

		self::send_standalone_email( 'booking_rescheduled', $booking_id, $context );
	}

	/**
	 * Send status update email.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $status     New status.
	 */
	public static function send_status_update( $booking_id, $status ) {
		if ( Sodek_GB_Booking::STATUS_CANCELLED !== $status ) {
			return;
		}

		if ( self::has_woocommerce_mailer() ) {
			do_action( 'sodek_gb_email_booking_cancelled', $booking_id );
			return;
		}

		self::send_standalone_email( 'booking_cancelled', $booking_id );
	}

	/**
	 * Send reminder email.
	 *
	 * @param int    $booking_id    Booking ID.
	 * @param string $reminder_type Reminder type.
	 */
	public static function send_reminder( $booking_id, $reminder_type = '24h' ) {
		if ( self::has_woocommerce_mailer() ) {
			do_action( 'sodek_gb_email_booking_reminder', $booking_id, $reminder_type );
			return;
		}

		self::send_standalone_email(
			'booking_reminder',
			$booking_id,
			array(
				'reminder_type' => $reminder_type,
			)
		);
	}

	/**
	 * Check if the WooCommerce mailer stack is available.
	 *
	 * @return bool
	 */
	public static function has_woocommerce_mailer() {
		return class_exists( 'WC_Email' ) && function_exists( 'WC' ) && WC()->mailer();
	}

	/**
	 * Format a monetary amount.
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	public static function format_price( $amount ) {
		$amount = (float) $amount;

		if ( function_exists( 'wc_price' ) ) {
			return wc_price( $amount );
		}

		$currency = function_exists( 'get_woocommerce_currency_symbol' )
			? get_woocommerce_currency_symbol()
			: '$';

		return sprintf( '%s%s', $currency, number_format_i18n( $amount, 2 ) );
	}

	/**
	 * Get email template path.
	 *
	 * @param string $template Template name.
	 * @return string
	 */
	public static function get_template_path( $template ) {
		return SODEK_GB_PLUGIN_DIR . 'templates/emails/' . $template;
	}

	/**
	 * Render fallback email header.
	 *
	 * @param string $email_heading Email heading.
	 * @param object $email         Email object.
	 */
	public static function render_email_header( $email_heading, $email = null ) {
		if ( self::has_woocommerce_mailer() ) {
			return;
		}
		?>
		<div style="background:#f6f3ef;padding:24px 0;">
			<div style="max-width:680px;margin:0 auto;background:#ffffff;border:1px solid #ece4dc;border-radius:16px;overflow:hidden;">
				<div style="padding:28px 32px;border-bottom:1px solid #f1ebe4;background:linear-gradient(180deg,#fff7f0 0%,#ffffff 100%);">
					<div style="font:600 12px/1.4 -apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;letter-spacing:.18em;text-transform:uppercase;color:#9b734f;margin-bottom:10px;">
						<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
					</div>
					<h1 style="margin:0;font:600 30px/1.2 Georgia,serif;color:#221b17;">
						<?php echo esc_html( $email_heading ); ?>
					</h1>
				</div>
				<div style="padding:32px;">
		<?php
	}

	/**
	 * Render fallback email footer.
	 *
	 * @param object $email Email object.
	 */
	public static function render_email_footer( $email = null ) {
		if ( self::has_woocommerce_mailer() ) {
			return;
		}
		?>
				</div>
				<div style="padding:18px 32px;border-top:1px solid #f1ebe4;background:#faf8f5;color:#7b7068;font:400 13px/1.6 -apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
					<?php echo esc_html( self::get_email_footer_text( '' ) ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get fallback footer text.
	 *
	 * @param string $footer_text Existing footer text.
	 * @return string
	 */
	public static function get_email_footer_text( $footer_text ) {
		if ( ! empty( $footer_text ) ) {
			return $footer_text;
		}

		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$site_url  = home_url( '/' );

		return sprintf(
			/* translators: 1: site name, 2: site url */
			__( '%1$s · %2$s', 'glowbook' ),
			$site_name,
			$site_url
		);
	}

	/**
	 * Get formatted booking details for email.
	 *
	 * @param array $booking Booking data.
	 * @return string
	 */
	public static function get_booking_details_html( $booking ) {
		ob_start();
		?>
		<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #e5e5e5; margin-bottom: 20px;" border="1">
			<tbody>
				<tr>
					<th style="text-align: left; padding: 10px; width: 30%;"><?php esc_html_e( 'Service', 'glowbook' ); ?></th>
					<td style="padding: 10px;"><?php echo esc_html( $booking['service']['title'] ); ?></td>
				</tr>
				<tr>
					<th style="text-align: left; padding: 10px;"><?php esc_html_e( 'Date', 'glowbook' ); ?></th>
					<td style="padding: 10px;"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ); ?></td>
				</tr>
				<tr>
					<th style="text-align: left; padding: 10px;"><?php esc_html_e( 'Time', 'glowbook' ); ?></th>
					<td style="padding: 10px;"><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?></td>
				</tr>
				<tr>
					<th style="text-align: left; padding: 10px;"><?php esc_html_e( 'Duration', 'glowbook' ); ?></th>
					<td style="padding: 10px;"><?php echo esc_html( $booking['service']['duration'] ); ?> <?php esc_html_e( 'minutes', 'glowbook' ); ?></td>
				</tr>
				<?php if ( $booking['total_price'] - $booking['deposit_amount'] > 0 ) : ?>
				<tr>
					<th style="text-align: left; padding: 10px;"><?php esc_html_e( 'Balance Due', 'glowbook' ); ?></th>
					<td style="padding: 10px;"><?php echo wp_kses_post( self::format_price( $booking['total_price'] - $booking['deposit_amount'] ) ); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get refund details for a booking.
	 *
	 * @param int $booking_id Booking ID.
	 * @return array
	 */
	public static function get_refund_details( $booking_id ) {
		$refund_type   = get_post_meta( $booking_id, '_sodek_gb_refund_type', true );
		$refund_amount = (float) get_post_meta( $booking_id, '_sodek_gb_refund_amount', true );
		$refunded_at   = get_post_meta( $booking_id, '_sodek_gb_refunded_at', true );

		return array(
			'refund_type'     => $refund_type ?: 'none',
			'refund_amount'   => max( 0, $refund_amount ),
			'refunded_at'     => $refunded_at,
			'has_refund'      => 'refund' === $refund_type && $refund_amount > 0,
			'formatted_amount'=> self::format_price( $refund_amount ),
		);
	}

	/**
	 * Normalize reschedule context.
	 *
	 * @param int         $booking_id Booking ID.
	 * @param mixed       $arg2       Old date or booking array.
	 * @param string|null $arg3       Old time or new date.
	 * @param string|null $arg4       New date or new time.
	 * @param string|null $arg5       New time.
	 * @return array
	 */
	private static function normalize_reschedule_context( $booking_id, $arg2 = null, $arg3 = null, $arg4 = null, $arg5 = null ) {
		$booking = Sodek_GB_Booking::get_booking( $booking_id );
		$context = array(
			'old_date' => '',
			'old_time' => '',
			'new_date' => '',
			'new_time' => '',
		);

		if ( is_array( $arg2 ) ) {
			$context['old_date'] = $arg2['booking_date'] ?? '';
			$context['old_time'] = $arg2['start_time'] ?? '';
			$context['new_date'] = (string) $arg3;
			$context['new_time'] = (string) $arg4;
		} else {
			$context['old_date'] = (string) $arg2;
			$context['old_time'] = (string) $arg3;
			$context['new_date'] = (string) $arg4;
			$context['new_time'] = (string) $arg5;
		}

		if ( empty( $context['new_date'] ) && ! empty( $booking['booking_date'] ) ) {
			$context['new_date'] = $booking['booking_date'];
		}

		if ( empty( $context['new_time'] ) && ! empty( $booking['start_time'] ) ) {
			$context['new_time'] = $booking['start_time'];
		}

		return $context;
	}

	/**
	 * Send standalone email.
	 *
	 * @param string $email_type Email type key.
	 * @param int    $booking_id  Booking ID.
	 * @param array  $context     Additional context.
	 * @return bool
	 */
	private static function send_standalone_email( $email_type, $booking_id, array $context = array() ) {
		$booking = Sodek_GB_Booking::get_booking( $booking_id );
		if ( ! $booking ) {
			return false;
		}

		$email_data = self::get_standalone_email_data( $email_type, $booking, $context );
		if ( empty( $email_data['recipient'] ) ) {
			return false;
		}

		$args = array_merge(
			array(
				'booking'            => $booking,
				'email_heading'      => $email_data['heading'],
				'additional_content' => $email_data['additional_content'],
				'sent_to_admin'      => ! empty( $email_data['sent_to_admin'] ),
				'plain_text'         => false,
				'email'              => null,
				'refund_details'     => self::get_refund_details( $booking_id ),
			),
			$context
		);

		$html = self::render_template(
			$email_data['template_html'],
			$args
		);

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		return wp_mail( $email_data['recipient'], $email_data['subject'], $html, $headers );
	}

	/**
	 * Build standalone email data.
	 *
	 * @param string $email_type Email type.
	 * @param array  $booking    Booking data.
	 * @param array  $context    Context data.
	 * @return array
	 */
	private static function get_standalone_email_data( $email_type, array $booking, array $context = array() ) {
		$site_title = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$service    = $booking['service']['title'] ?? __( 'Appointment', 'glowbook' );
		$date       = ! empty( $booking['booking_date'] ) ? date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) : '';
		$time       = ! empty( $booking['start_time'] ) ? date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) : '';

		switch ( $email_type ) {
			case 'admin_new_booking':
				return array(
					'recipient'          => get_option( 'admin_email' ),
					'subject'            => sprintf( '[%s] %s: %s - %s', $site_title, __( 'New Booking', 'glowbook' ), $service, $booking['customer_name'] ?? __( 'Customer', 'glowbook' ) ),
					'heading'            => __( 'New Booking Received', 'glowbook' ),
					'template_html'      => 'admin-new-booking.php',
					'additional_content' => __( 'View this booking in your dashboard to manage it.', 'glowbook' ),
					'sent_to_admin'      => true,
				);

			case 'booking_cancelled':
				return array(
					'recipient'          => $booking['customer_email'] ?? '',
					'subject'            => sprintf( __( 'Your booking has been cancelled - %s', 'glowbook' ), $service ),
					'heading'            => __( 'Booking Cancelled', 'glowbook' ),
					'template_html'      => 'booking-cancelled.php',
					'additional_content' => __( 'If you would like to rebook, please visit our website.', 'glowbook' ),
				);

			case 'booking_reminder':
				$time_until = self::get_time_until_text( $booking );
				return array(
					'recipient'          => $booking['customer_email'] ?? '',
					'subject'            => sprintf( __( 'Reminder: Your appointment is %s', 'glowbook' ), $time_until ),
					'heading'            => __( 'Appointment Reminder', 'glowbook' ),
					'template_html'      => 'booking-reminder.php',
					'additional_content' => __( 'We look forward to seeing you! If you need to reschedule, please contact us as soon as possible.', 'glowbook' ),
				);

			case 'booking_rescheduled':
				return array(
					'recipient'          => $booking['customer_email'] ?? '',
					'subject'            => sprintf( __( 'Your booking has been updated - %s on %s', 'glowbook' ), $service, $date ),
					'heading'            => __( 'Booking Rescheduled', 'glowbook' ),
					'template_html'      => 'booking-rescheduled.php',
					'additional_content' => __( 'Your appointment details have been updated. Please review the new date and time below.', 'glowbook' ),
				);

			case 'booking_confirmation':
			default:
				return array(
					'recipient'          => $booking['customer_email'] ?? '',
					'subject'            => sprintf( __( 'Your booking is confirmed - %1$s on %2$s at %3$s', 'glowbook' ), $service, $date, $time ),
					'heading'            => __( 'Booking Confirmed!', 'glowbook' ),
					'template_html'      => 'booking-confirmation.php',
					'additional_content' => __( 'Please arrive on time for your appointment. The balance shown is due at your appointment.', 'glowbook' ),
				);
		}
	}

	/**
	 * Render a template file.
	 *
	 * @param string $template Template file name.
	 * @param array  $args     Template args.
	 * @return string
	 */
	private static function render_template( $template, array $args ) {
		$template_path = self::get_template_path( $template );
		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		ob_start();
		extract( $args, EXTR_SKIP );
		include $template_path;
		return ob_get_clean();
	}

	/**
	 * Calculate human-friendly time until appointment.
	 *
	 * @param array $booking Booking data.
	 * @return string
	 */
	private static function get_time_until_text( array $booking ) {
		$timezone = wp_timezone();
		$now      = new DateTimeImmutable( 'now', $timezone );
		$appt     = DateTimeImmutable::createFromFormat(
			'Y-m-d H:i',
			sprintf( '%s %s', $booking['booking_date'] ?? '', $booking['start_time'] ?? '' ),
			$timezone
		);

		if ( ! $appt ) {
			return __( 'soon', 'glowbook' );
		}

		$time_diff = $appt->getTimestamp() - $now->getTimestamp();

		if ( $time_diff < HOUR_IN_SECONDS ) {
			return sprintf( __( 'in %d minutes', 'glowbook' ), max( 1, (int) round( $time_diff / MINUTE_IN_SECONDS ) ) );
		}

		if ( $time_diff < DAY_IN_SECONDS ) {
			$hours = max( 1, (int) round( $time_diff / HOUR_IN_SECONDS ) );
			return sprintf( _n( 'in %d hour', 'in %d hours', $hours, 'glowbook' ), $hours );
		}

		return __( 'tomorrow', 'glowbook' );
	}
}
