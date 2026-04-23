<?php
/**
 * Booking rescheduled email.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Email_Booking_Rescheduled class.
 */
class Sodek_GB_Email_Booking_Rescheduled extends WC_Email {

	/**
	 * Booking data.
	 *
	 * @var array
	 */
	public $booking;

	/**
	 * Old date.
	 *
	 * @var string
	 */
	public $old_date = '';

	/**
	 * Old time.
	 *
	 * @var string
	 */
	public $old_time = '';

	/**
	 * New date.
	 *
	 * @var string
	 */
	public $new_date = '';

	/**
	 * New time.
	 *
	 * @var string
	 */
	public $new_time = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'sodek_gb_booking_rescheduled';
		$this->customer_email = true;
		$this->title          = __( 'Booking Rescheduled', 'glowbook' );
		$this->description    = __( 'Booking rescheduled emails are sent when an appointment date or time changes.', 'glowbook' );
		$this->template_html  = 'emails/booking-rescheduled.php';
		$this->template_plain = 'emails/plain/booking-rescheduled.php';
		$this->template_base  = SODEK_GB_PLUGIN_DIR . 'templates/';
		$this->placeholders   = array(
			'{site_title}'    => $this->get_blogname(),
			'{booking_date}'  => '',
			'{booking_time}'  => '',
			'{service_name}'  => '',
			'{customer_name}' => '',
		);

		add_action( 'sodek_gb_email_booking_rescheduled', array( $this, 'trigger' ), 10, 5 );

		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Your booking has been updated - {service_name} on {booking_date}', 'glowbook' );
	}

	/**
	 * Default heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Booking Rescheduled', 'glowbook' );
	}

	/**
	 * Trigger email.
	 *
	 * @param int    $booking_id Booking ID.
	 * @param string $old_date   Old date.
	 * @param string $old_time   Old time.
	 * @param string $new_date   New date.
	 * @param string $new_time   New time.
	 */
	public function trigger( $booking_id, $old_date = '', $old_time = '', $new_date = '', $new_time = '' ) {
		$this->setup_locale();
		$this->booking  = Sodek_GB_Booking::get_booking( $booking_id );
		$this->old_date = $old_date;
		$this->old_time = $old_time;
		$this->new_date = $new_date;
		$this->new_time = $new_time;

		if ( ! $this->booking ) {
			return;
		}

		$this->recipient = $this->booking['customer_email'];

		$active_date = $this->new_date ?: $this->booking['booking_date'];
		$active_time = $this->new_time ?: $this->booking['start_time'];

		$this->placeholders['{booking_date}']  = date_i18n( get_option( 'date_format' ), strtotime( $active_date ) );
		$this->placeholders['{booking_time}']  = date_i18n( get_option( 'time_format' ), strtotime( $active_time ) );
		$this->placeholders['{service_name}']  = $this->booking['service']['title'];
		$this->placeholders['{customer_name}'] = $this->booking['customer_name'];

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Get html content.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'booking'            => $this->booking,
				'old_date'           => $this->old_date,
				'old_time'           => $this->old_time,
				'new_date'           => $this->new_date,
				'new_time'           => $this->new_time,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => false,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Get plain content.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'booking'            => $this->booking,
				'old_date'           => $this->old_date,
				'old_time'           => $this->old_time,
				'new_date'           => $this->new_date,
				'new_time'           => $this->new_time,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => false,
				'plain_text'         => true,
				'email'              => $this,
			),
			'',
			$this->template_base
		);
	}

	/**
	 * Default additional content.
	 *
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'Your appointment details have been updated. Please review the new date and time below.', 'glowbook' );
	}
}
