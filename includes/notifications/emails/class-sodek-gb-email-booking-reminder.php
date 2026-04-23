<?php
/**
 * Booking reminder email.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Email_Booking_Reminder class.
 */
class Sodek_GB_Email_Booking_Reminder extends WC_Email {

    /**
     * Booking data.
     *
     * @var array
     */
    public $booking;

    /**
     * Reminder type.
     *
     * @var string
     */
    public $reminder_type;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'sodek_gb_booking_reminder';
        $this->customer_email = true;
        $this->title          = __( 'Booking Reminder', 'glowbook' );
        $this->description    = __( 'Booking reminder emails are sent to customers before their appointment.', 'glowbook' );
        $this->template_html  = 'emails/booking-reminder.php';
        $this->template_plain = 'emails/plain/booking-reminder.php';
        $this->template_base  = SODEK_GB_PLUGIN_DIR . 'templates/';
        $this->placeholders   = array(
            '{site_title}'     => $this->get_blogname(),
            '{booking_date}'   => '',
            '{booking_time}'   => '',
            '{service_name}'   => '',
            '{customer_name}'  => '',
            '{time_until}'     => '',
        );

        // Triggers
        add_action( 'sodek_gb_email_booking_reminder', array( $this, 'trigger' ), 10, 2 );

        parent::__construct();
    }

    /**
     * Get email subject.
     *
     * @return string
     */
    public function get_default_subject() {
        return __( 'Reminder: Your appointment is {time_until}', 'glowbook' );
    }

    /**
     * Get email heading.
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'Appointment Reminder', 'glowbook' );
    }

    /**
     * Trigger the email.
     *
     * @param int    $booking_id    Booking ID.
     * @param string $reminder_type Reminder type (24h, 2h, etc.).
     */
    public function trigger( $booking_id, $reminder_type = '24h' ) {
        $this->setup_locale();

        $this->booking = Sodek_GB_Booking::get_booking( $booking_id );
        $this->reminder_type = $reminder_type;

        if ( ! $this->booking ) {
            return;
        }

        $this->recipient = $this->booking['customer_email'];

        // Calculate time until appointment
        $timezone         = wp_timezone();
        $now              = new DateTimeImmutable( 'now', $timezone );
        $appointment_time = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            sprintf( '%s %s', $this->booking['booking_date'], $this->booking['start_time'] ),
            $timezone
        );

        if ( ! $appointment_time ) {
            $time_until = __( 'soon', 'glowbook' );
        } else {
            $time_diff = $appointment_time->getTimestamp() - $now->getTimestamp();

            if ( $time_diff < 3600 ) {
                $time_until = sprintf( __( 'in %d minutes', 'glowbook' ), round( $time_diff / 60 ) );
            } elseif ( $time_diff < 86400 ) {
                $hours = round( $time_diff / 3600 );
                $time_until = sprintf( _n( 'in %d hour', 'in %d hours', $hours, 'glowbook' ), $hours );
            } else {
                $time_until = __( 'tomorrow', 'glowbook' );
            }
        }

        $this->placeholders['{booking_date}']  = date_i18n( get_option( 'date_format' ), strtotime( $this->booking['booking_date'] ) );
        $this->placeholders['{booking_time}']  = date_i18n( get_option( 'time_format' ), strtotime( $this->booking['start_time'] ) );
        $this->placeholders['{service_name}']  = $this->booking['service']['title'];
        $this->placeholders['{customer_name}'] = $this->booking['customer_name'];
        $this->placeholders['{time_until}']    = $time_until;

        if ( $this->is_enabled() && $this->get_recipient() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        $this->restore_locale();
    }

    /**
     * Get content HTML.
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'booking'         => $this->booking,
                'reminder_type'   => $this->reminder_type,
                'email_heading'   => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'   => false,
                'plain_text'      => false,
                'email'           => $this,
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'booking'         => $this->booking,
                'reminder_type'   => $this->reminder_type,
                'email_heading'   => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'   => false,
                'plain_text'      => true,
                'email'           => $this,
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Default content to show below main email content.
     *
     * @return string
     */
    public function get_default_additional_content() {
        return __( 'We look forward to seeing you! If you need to reschedule, please contact us as soon as possible.', 'glowbook' );
    }
}
