<?php
/**
 * Admin new booking notification email.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Email_Admin_New_Booking class.
 */
class Sodek_GB_Email_Admin_New_Booking extends WC_Email {

    /**
     * Booking data.
     *
     * @var array
     */
    public $booking;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'sodek_gb_admin_new_booking';
        $this->customer_email = false;
        $this->title          = __( 'New Booking (Admin)', 'glowbook' );
        $this->description    = __( 'New booking notification emails are sent to admin when a customer makes a booking.', 'glowbook' );
        $this->template_html  = 'emails/admin-new-booking.php';
        $this->template_plain = 'emails/plain/admin-new-booking.php';
        $this->template_base  = SODEK_GB_PLUGIN_DIR . 'templates/';
        $this->placeholders   = array(
            '{site_title}'     => $this->get_blogname(),
            '{booking_date}'   => '',
            '{booking_time}'   => '',
            '{service_name}'   => '',
            '{customer_name}'  => '',
        );

        // Default recipient is admin email
        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );

        // Triggers
        add_action( 'sodek_gb_email_admin_new_booking', array( $this, 'trigger' ), 10, 2 );

        parent::__construct();
    }

    /**
     * Get email subject.
     *
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] New Booking: {service_name} - {customer_name}', 'glowbook' );
    }

    /**
     * Get email heading.
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'New Booking Received', 'glowbook' );
    }

    /**
     * Trigger the email.
     *
     * @param int $booking_id Booking ID.
     * @param int $order_id   Order ID.
     */
    public function trigger( $booking_id, $order_id = 0 ) {
        $this->setup_locale();

        $this->booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $this->booking ) {
            return;
        }

        $this->placeholders['{booking_date}']  = date_i18n( get_option( 'date_format' ), strtotime( $this->booking['booking_date'] ) );
        $this->placeholders['{booking_time}']  = date_i18n( get_option( 'time_format' ), strtotime( $this->booking['start_time'] ) );
        $this->placeholders['{service_name}']  = $this->booking['service']['title'];
        $this->placeholders['{customer_name}'] = $this->booking['customer_name'];

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
                'booking'            => $this->booking,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
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
                'booking'            => $this->booking,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
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
        return __( 'View this booking in your dashboard to manage it.', 'glowbook' );
    }

    /**
     * Initialize form fields.
     */
    public function init_form_fields() {
        parent::init_form_fields();

        $this->form_fields['recipient'] = array(
            'title'       => __( 'Recipient(s)', 'glowbook' ),
            'type'        => 'text',
            'description' => sprintf(
                /* translators: %s: default email address */
                __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'glowbook' ),
                '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>'
            ),
            'placeholder' => '',
            'default'     => '',
            'desc_tip'    => true,
        );
    }
}
