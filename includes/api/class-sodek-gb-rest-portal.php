<?php
/**
 * Customer Portal REST API Endpoints.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_REST_Portal class.
 */
class Sodek_GB_REST_Portal {

    /**
     * Namespace.
     *
     * @var string
     */
    protected $namespace = 'sodek-gb/v1';

    /**
     * Register routes.
     */
    public function register_routes() {
        // Portal login
        register_rest_route(
            $this->namespace,
            '/portal/login',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'handle_login' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'identifier' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Verify login code
        register_rest_route(
            $this->namespace,
            '/portal/verify',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'verify_login' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'customer_id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'code' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Magic link login
        register_rest_route(
            $this->namespace,
            '/portal/magic-link',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'send_magic_link' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'email' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'format'            => 'email',
                            'sanitize_callback' => 'sanitize_email',
                        ),
                    ),
                ),
            )
        );

        // Logout
        register_rest_route(
            $this->namespace,
            '/portal/logout',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'handle_logout' ),
                    'permission_callback' => '__return_true',
                ),
            )
        );

        // Get my bookings
        register_rest_route(
            $this->namespace,
            '/portal/bookings',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_my_bookings' ),
                    'permission_callback' => array( $this, 'is_logged_in' ),
                    'args'                => array(
                        'type' => array(
                            'type'              => 'string',
                            'enum'              => array( 'upcoming', 'past', 'all' ),
                            'default'           => 'upcoming',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Reschedule booking
        register_rest_route(
            $this->namespace,
            '/portal/bookings/(?P<id>\d+)/reschedule',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'reschedule_booking' ),
                    'permission_callback' => array( $this, 'can_modify_booking' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'date' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'format'            => 'date',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'time' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Get available dates for reschedule
        register_rest_route(
            $this->namespace,
            '/portal/bookings/(?P<id>\d+)/reschedule/dates',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_reschedule_dates' ),
                    'permission_callback' => array( $this, 'can_modify_booking' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
            )
        );

        // Get available times for reschedule
        register_rest_route(
            $this->namespace,
            '/portal/bookings/(?P<id>\d+)/reschedule/times',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_reschedule_times' ),
                    'permission_callback' => array( $this, 'can_modify_booking' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'date' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'format'            => 'date',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Cancel booking
        register_rest_route(
            $this->namespace,
            '/portal/bookings/(?P<id>\d+)/cancel',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'cancel_booking' ),
                    'permission_callback' => array( $this, 'can_modify_booking' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'reason' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Get cancellation policy
        register_rest_route(
            $this->namespace,
            '/portal/bookings/(?P<id>\d+)/cancel-policy',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_cancel_policy' ),
                    'permission_callback' => array( $this, 'can_modify_booking' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
            )
        );

        // Pay booking balance
        register_rest_route(
            $this->namespace,
            '/portal/bookings/(?P<id>\d+)/pay-balance',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'pay_balance' ),
                    'permission_callback' => array( $this, 'can_modify_booking' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'card_id' => array(
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'card_nonce' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Update profile
        register_rest_route(
            $this->namespace,
            '/portal/profile',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_profile' ),
                    'permission_callback' => array( $this, 'is_logged_in' ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_profile' ),
                    'permission_callback' => array( $this, 'is_logged_in' ),
                    'args'                => array(
                        'first_name' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'last_name' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'email' => array(
                            'type'              => 'string',
                            'format'            => 'email',
                            'sanitize_callback' => 'sanitize_email',
                        ),
                        'hair_type' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'hair_length' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'allergies' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_textarea_field',
                        ),
                        'notes' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_textarea_field',
                        ),
                        'email_opt_in' => array(
                            'type' => 'boolean',
                        ),
                        'sms_opt_in' => array(
                            'type' => 'boolean',
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Handle login request.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function handle_login( $request ) {
        $identifier = $request->get_param( 'identifier' );

        // Determine if phone or email
        $is_phone = preg_match( '/^[\d\s\-\+\(\)]+$/', $identifier );

        if ( $is_phone ) {
            $customer = Sodek_GB_Customer::get_by_phone( $identifier );
        } else {
            $customer = Sodek_GB_Customer::get_by_email( $identifier );
        }

        if ( ! $customer ) {
            return new WP_Error(
                'customer_not_found',
                __( 'No account found with this phone number or email. Please check your entry or book an appointment to create an account.', 'glowbook' ),
                array( 'status' => 404 )
            );
        }

        // Generate verification code
        $code = Sodek_GB_Customer::generate_verification_code( $customer['id'] );

        if ( is_wp_error( $code ) ) {
            return $code;
        }

        // Send code via email
        $sent = $this->send_verification_email( $customer, $code );

        if ( ! $sent ) {
            return new WP_Error(
                'send_failed',
                __( 'Failed to send verification code. Please try again.', 'glowbook' ),
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response(
            array(
                'success'     => true,
                'customer_id' => $customer['id'],
                'message'     => $is_phone
                    ? sprintf( __( 'Verification code sent to your phone ending in %s', 'glowbook' ), substr( preg_replace( '/\D/', '', $customer['phone'] ), -4 ) )
                    : sprintf( __( 'Verification code sent to %s', 'glowbook' ), $this->mask_email( $customer['email'] ) ),
            )
        );
    }

    /**
     * Verify login code.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function verify_login( $request ) {
        $customer_id = $request->get_param( 'customer_id' );
        $code = $request->get_param( 'code' );

        $verified = Sodek_GB_Customer::verify_code( $customer_id, $code );

        if ( is_wp_error( $verified ) ) {
            return $verified;
        }

        // Create session
        $token = Sodek_GB_Customer::generate_login_token( $customer_id );

        // Set session cookie
        if ( ! headers_sent() ) {
            setcookie(
                'sodek_gb_portal_token',
                $token,
                time() + ( 30 * DAY_IN_SECONDS ),
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(),
                true
            );
        }

        // Store in session
        if ( ! session_id() ) {
            session_start();
        }
        $_SESSION['sodek_gb_customer_id'] = $customer_id;
        $_SESSION['sodek_gb_portal_token'] = $token;

        return rest_ensure_response(
            array(
                'success'      => true,
                'redirect_url' => Sodek_GB_Standalone_Booking::get_portal_url(),
            )
        );
    }

    /**
     * Send magic link email.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function send_magic_link( $request ) {
        $email = $request->get_param( 'email' );
        $customer = Sodek_GB_Customer::get_by_email( $email );

        if ( ! $customer ) {
            // Don't reveal if email exists
            return rest_ensure_response(
                array(
                    'success' => true,
                    'message' => __( 'If an account exists with this email, a magic link has been sent.', 'glowbook' ),
                )
            );
        }

        // Generate token
        $token = Sodek_GB_Customer::generate_login_token( $customer['id'] );

        // Create magic link URL
        $magic_link = add_query_arg(
            array(
                'magic_token' => $token,
            ),
            Sodek_GB_Standalone_Booking::get_portal_url()
        );

        // Send email
        $subject = sprintf(
            /* translators: %s: business name */
            __( 'Sign in to %s', 'glowbook' ),
            get_bloginfo( 'name' )
        );

        $message = sprintf(
            /* translators: 1: customer name, 2: magic link, 3: business name */
            __(
                "Hi %1\$s,\n\nClick the link below to sign in to your account:\n\n%2\$s\n\nThis link will expire in 30 minutes.\n\nIf you didn't request this link, you can ignore this email.\n\nThanks,\n%3\$s",
                'glowbook'
            ),
            $customer['first_name'] ?: __( 'there', 'glowbook' ),
            $magic_link,
            get_bloginfo( 'name' )
        );

        wp_mail( $customer['email'], $subject, $message );

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __( 'Magic link sent! Check your email.', 'glowbook' ),
            )
        );
    }

    /**
     * Handle logout.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function handle_logout( $request ) {
        // Clear cookie
        if ( ! headers_sent() ) {
            setcookie(
                'sodek_gb_portal_token',
                '',
                time() - HOUR_IN_SECONDS,
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(),
                true
            );
        }

        // Clear session
        if ( session_id() ) {
            unset( $_SESSION['sodek_gb_customer_id'] );
            unset( $_SESSION['sodek_gb_portal_token'] );
        }

        return rest_ensure_response(
            array(
                'success' => true,
            )
        );
    }

    /**
     * Get my bookings.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_my_bookings( $request ) {
        $customer = $this->get_current_customer();
        $type = $request->get_param( 'type' );

        $bookings = Sodek_GB_Customer::get_bookings( $customer['id'], $type );

        return rest_ensure_response( $bookings );
    }

    /**
     * Reschedule a booking.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function reschedule_booking( $request ) {
        $booking_id = $request->get_param( 'id' );
        $new_date = $request->get_param( 'date' );
        $new_time = $request->get_param( 'time' );

        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return new WP_Error(
                'booking_not_found',
                __( 'Booking not found.', 'glowbook' ),
                array( 'status' => 404 )
            );
        }

        // Check if slot is still available
        $staff_id = $booking['staff_id'];
        $service_id = $booking['service_id'];

        $available = Sodek_GB_Staff_Availability::is_slot_available(
            $staff_id,
            $new_date,
            $new_time,
            $service_id,
            $booking_id // Exclude current booking
        );

        if ( ! $available ) {
            return new WP_Error(
                'slot_unavailable',
                __( 'This time slot is no longer available. Please select another.', 'glowbook' ),
                array( 'status' => 409 )
            );
        }

        // Calculate new end time
        $duration = $booking['duration'] ?? 60;
        $new_end_time = gmdate( 'H:i:s', strtotime( $new_time ) + ( $duration * 60 ) );

        // Update booking
        $result = Sodek_GB_Booking::update_booking(
            $booking_id,
            array(
                'booking_date' => $new_date,
                'start_time'   => $new_time,
                'end_time'     => $new_end_time,
            )
        );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Send reschedule confirmation email
        do_action( 'sodek_gb_booking_rescheduled', $booking_id, $booking, $new_date, $new_time );

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __( 'Your appointment has been rescheduled.', 'glowbook' ),
                'booking' => Sodek_GB_Booking::get_booking( $booking_id ),
            )
        );
    }

    /**
     * Get available dates for rescheduling.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_reschedule_dates( $request ) {
        $booking_id = $request->get_param( 'id' );
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        $staff_id = $booking['staff_id'];
        $service_id = $booking['service_id'];

        // Get available dates for next 60 days
        $dates = array();
        $current = new DateTime();
        $end = new DateTime( '+60 days' );

        while ( $current <= $end ) {
            $date_str = $current->format( 'Y-m-d' );
            $slots = Sodek_GB_Staff_Availability::get_available_slots( $staff_id, $date_str, $service_id );

            if ( ! empty( $slots ) ) {
                $dates[] = $date_str;
            }

            $current->modify( '+1 day' );
        }

        return rest_ensure_response(
            array(
                'dates' => $dates,
            )
        );
    }

    /**
     * Get available times for rescheduling.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_reschedule_times( $request ) {
        $booking_id = $request->get_param( 'id' );
        $date = $request->get_param( 'date' );
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        $staff_id = $booking['staff_id'];
        $service_id = $booking['service_id'];

        $slots = Sodek_GB_Staff_Availability::get_available_slots( $staff_id, $date, $service_id );

        $times = array_map(
            function( $slot ) {
                $time = is_array( $slot ) ? $slot['time'] : $slot;
                return array(
                    'value' => $time,
                    'label' => date_i18n( get_option( 'time_format' ), strtotime( $time ) ),
                );
            },
            $slots
        );

        return rest_ensure_response(
            array(
                'times' => $times,
            )
        );
    }

    /**
     * Cancel a booking.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function cancel_booking( $request ) {
        $booking_id = $request->get_param( 'id' );
        $reason = $request->get_param( 'reason' );

        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return new WP_Error(
                'booking_not_found',
                __( 'Booking not found.', 'glowbook' ),
                array( 'status' => 404 )
            );
        }

        if ( ! Sodek_GB_Customer_Portal::customer_can_cancel_booking( $booking ) ) {
            return new WP_Error(
                'cancellation_not_allowed',
                __( 'This appointment can no longer be cancelled online.', 'glowbook' ),
                array( 'status' => 400 )
            );
        }

        // Update booking status
        $result = Sodek_GB_Booking::update_booking(
            $booking_id,
            array(
                'status' => 'cancelled',
            )
        );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Store cancellation reason
        if ( $reason ) {
            update_post_meta( $booking_id, '_sodek_gb_cancellation_reason', $reason );
        }
        update_post_meta( $booking_id, '_sodek_gb_cancelled_at', current_time( 'mysql' ) );
        update_post_meta( $booking_id, '_sodek_gb_cancelled_by', 'customer' );

        // Process refund if applicable
        $refund_info = $this->process_cancellation_refund( $booking );

        // Send cancellation email
        do_action( 'sodek_gb_booking_cancelled', $booking_id, $booking, 'customer' );

        // Notify waitlist
        Sodek_GB_Waitlist::notify_for_slot(
            $booking['booking_date'],
            $booking['start_time'],
            $booking['service_id'],
            $booking['staff_id']
        );

        return rest_ensure_response(
            array(
                'success'     => true,
                'message'     => __( 'Your appointment has been cancelled.', 'glowbook' ),
                'refund_info' => $refund_info,
            )
        );
    }

    /**
     * Get cancellation policy for a booking.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_cancel_policy( $request ) {
        $booking_id = $request->get_param( 'id' );
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        $booking_datetime = strtotime( $booking['booking_date'] . ' ' . $booking['start_time'] );
        $hours_until = ( $booking_datetime - time() ) / 3600;
        $cancel_notice = (int) get_option( 'sodek_gb_cancellation_notice', get_option( 'sodek_gb_cancel_notice', 24 ) );
        $info = Sodek_GB_Customer_Portal::get_customer_cancellation_info( $booking );

        return rest_ensure_response(
            array(
                'policy_text' => $info['message'] ?? '',
                'hours_until' => round( $hours_until, 1 ),
                'will_refund' => ! empty( $info['refund_amount'] ),
                'can_cancel'  => Sodek_GB_Customer_Portal::customer_can_cancel_booking( $booking ),
                'notice_hours'=> $cancel_notice,
            )
        );
    }

    /**
     * Pay booking balance.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function pay_balance( $request ) {
        $booking_id = $request->get_param( 'id' );
        $card_id = $request->get_param( 'card_id' );
        $card_nonce = $request->get_param( 'card_nonce' );
        $verification_token = $request->get_param( 'verification_token' );

        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return new WP_Error(
                'booking_not_found',
                __( 'Booking not found.', 'glowbook' ),
                array( 'status' => 404 )
            );
        }

        $balance = (float) get_post_meta( $booking_id, '_sodek_gb_balance_amount', true );

        if ( $balance <= 0 ) {
            return new WP_Error(
                'no_balance',
                __( 'There is no balance due for this booking.', 'glowbook' ),
                array( 'status' => 400 )
            );
        }

        $customer = $this->get_current_customer();

        $source_id = '';
        $payment_method = 'online_card';

        if ( $card_id ) {
            $saved_card = Sodek_GB_Customer::get_card( absint( $card_id ), $customer['id'] );

            if ( ! $saved_card || empty( $saved_card['card_id'] ) ) {
                return new WP_Error(
                    'saved_card_not_found',
                    __( 'The selected saved card is no longer available.', 'glowbook' ),
                    array( 'status' => 400 )
                );
            }

            $source_id = sanitize_text_field( $saved_card['card_id'] );
            $payment_method = 'saved_card';
        } elseif ( ! empty( $card_nonce ) ) {
            $source_id = sanitize_text_field( $card_nonce );
        }

        if ( empty( $source_id ) ) {
            return new WP_Error(
                'missing_payment_source',
                __( 'Please choose a saved card or enter a new card to continue.', 'glowbook' ),
                array( 'status' => 400 )
            );
        }

        $square_gateway = Sodek_GB_Payment_Manager::get_gateway( 'square' );
        $environment    = $square_gateway && method_exists( $square_gateway, 'get_environment' )
            ? $square_gateway->get_environment()
            : get_option( 'sodek_gb_square_environment', 'sandbox' );
        $currency       = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'sodek_gb_currency', 'USD' );

        $transaction_id = Sodek_GB_Transaction::create(
            array(
                'gateway'          => 'square',
                'environment'      => $environment,
                'amount'           => $balance,
                'currency'         => $currency,
                'transaction_type' => Sodek_GB_Transaction::TYPE_PAYMENT,
                'status'           => Sodek_GB_Transaction::STATUS_PENDING,
                'customer_email'   => $booking['customer_email'] ?? '',
                'customer_name'    => $booking['customer_name'] ?? '',
                'booking_id'       => $booking_id,
                'request_data'     => array(
                    'booking_id'          => $booking_id,
                    'customer_id'         => $customer['id'],
                    'payment_context'     => 'portal_balance_rest',
                    'saved_card_selected' => 'saved_card' === $payment_method,
                ),
            )
        );

        $payment_result = Sodek_GB_Payment_Manager::process_payment(
            'square',
            $balance,
            array(
                'source_id'          => $source_id,
                'customer_email'     => $booking['customer_email'] ?? '',
                'verification_token' => ! empty( $verification_token ) ? sanitize_text_field( $verification_token ) : '',
                'reference_id'       => 'GB-BAL-' . $booking_id,
                'note'               => sprintf( __( 'Balance payment for booking #%d', 'glowbook' ), $booking_id ),
                'metadata'           => array(
                    'booking_id'      => (string) $booking_id,
                    'customer_id'     => (string) $customer['id'],
                    'payment_context' => 'portal_balance_rest',
                    'balance_amount'  => (string) $balance,
                ),
            )
        );

        if ( empty( $payment_result['success'] ) ) {
            if ( $transaction_id ) {
                Sodek_GB_Transaction::update(
                    $transaction_id,
                    array(
                        'status'        => Sodek_GB_Transaction::STATUS_FAILED,
                        'error_code'    => $payment_result['error']['code'] ?? 'payment_failed',
                        'error_message' => $payment_result['error']['message'] ?? __( 'Payment failed.', 'glowbook' ),
                    )
                );
            }

            return new WP_Error(
                $payment_result['error']['code'] ?? 'payment_failed',
                $payment_result['error']['message'] ?? __( 'Payment failed.', 'glowbook' ),
                array( 'status' => 400 )
            );
        }

        if ( $transaction_id ) {
            Sodek_GB_Transaction::update(
                $transaction_id,
                array(
                    'status'             => Sodek_GB_Transaction::STATUS_COMPLETED,
                    'square_payment_id'  => $payment_result['data']['payment_id'] ?? '',
                    'square_receipt_url' => $payment_result['data']['receipt_url'] ?? '',
                    'square_card_brand'  => $payment_result['data']['card_brand'] ?? '',
                    'square_card_last4'  => $payment_result['data']['card_last4'] ?? '',
                    'response_data'      => $payment_result['data'] ?? array(),
                    'booking_id'         => $booking_id,
                )
            );
        }

        // Update balance
        update_post_meta( $booking_id, '_sodek_gb_balance_amount', 0 );
        update_post_meta( $booking_id, '_sodek_gb_balance_paid', '1' );
        update_post_meta( $booking_id, '_sodek_gb_balance_paid_at', current_time( 'mysql' ) );
        update_post_meta( $booking_id, '_sodek_gb_balance_payment_id', $payment_result['data']['payment_id'] ?? '' );
        update_post_meta( $booking_id, '_sodek_gb_balance_payment_method', $payment_method );
        update_post_meta( $booking_id, '_sodek_gb_balance_received_by', 'customer_portal' );
        update_post_meta( $booking_id, '_sodek_gb_balance_receipt_url', $payment_result['data']['receipt_url'] ?? '' );

        // Send receipt
        do_action( 'sodek_gb_balance_paid', $booking_id, $balance, $customer );

        return rest_ensure_response(
            array(
                'success'     => true,
                'message'     => __( 'Payment successful! Thank you.', 'glowbook' ),
                'receipt_url' => $payment_result['data']['receipt_url'] ?? '',
            )
        );
    }

    /**
     * Get current customer profile.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_profile( $request ) {
        $customer = $this->get_current_customer();

        // Remove sensitive data
        unset( $customer['last_verification_code'] );
        unset( $customer['verification_code_expires'] );

        return rest_ensure_response( $customer );
    }

    /**
     * Update customer profile.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function update_profile( $request ) {
        $customer = $this->get_current_customer();

        $data = array();
        $allowed = array(
            'first_name',
            'last_name',
            'email',
            'hair_type',
            'hair_length',
            'allergies',
            'notes',
            'email_opt_in',
            'sms_opt_in',
        );

        foreach ( $allowed as $field ) {
            if ( $request->has_param( $field ) ) {
                $data[ $field ] = $request->get_param( $field );
            }
        }

        $result = Sodek_GB_Customer::update( $customer['id'], $data );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __( 'Profile updated.', 'glowbook' ),
            )
        );
    }

    /**
     * Check if user is logged in to portal.
     *
     * @return bool|WP_Error
     */
    public function is_logged_in() {
        $customer = $this->get_current_customer();

        if ( ! $customer ) {
            return new WP_Error(
                'not_logged_in',
                __( 'Please sign in to continue.', 'glowbook' ),
                array( 'status' => 401 )
            );
        }

        return true;
    }

    /**
     * Check if user can modify this booking.
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error
     */
    public function can_modify_booking( $request ) {
        // Admin can modify any booking
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        $logged_in = $this->is_logged_in();
        if ( is_wp_error( $logged_in ) ) {
            return $logged_in;
        }

        $booking_id = $request->get_param( 'id' );
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return new WP_Error(
                'booking_not_found',
                __( 'Booking not found.', 'glowbook' ),
                array( 'status' => 404 )
            );
        }

        $customer = $this->get_current_customer();

        // Check if booking belongs to this customer
        $booking_customer_id = get_post_meta( $booking_id, '_sodek_gb_customer_id', true );

        if ( (int) $booking_customer_id !== (int) $customer['id'] ) {
            // Also check by email
            if ( $booking['customer_email'] !== $customer['email'] ) {
                return new WP_Error(
                    'unauthorized',
                    __( 'You do not have permission to modify this booking.', 'glowbook' ),
                    array( 'status' => 403 )
                );
            }
        }

        return true;
    }

    /**
     * Get current logged in customer.
     *
     * @return array|null
     */
    private function get_current_customer() {
        return Sodek_GB_Customer_Portal::get_logged_in_customer();
    }

    /**
     * Send verification email.
     *
     * @param array  $customer Customer data.
     * @param string $code     Verification code.
     * @return bool
     */
    private function send_verification_email( $customer, $code ) {
        if ( empty( $customer['email'] ) ) {
            return false;
        }

        $subject = sprintf(
            /* translators: %s: business name */
            __( 'Your sign in code for %s', 'glowbook' ),
            get_bloginfo( 'name' )
        );

        $message = sprintf(
            /* translators: 1: customer name, 2: verification code, 3: business name */
            __(
                "Hi %1\$s,\n\nYour sign in code is: %2\$s\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, you can ignore this email.\n\nThanks,\n%3\$s",
                'glowbook'
            ),
            $customer['first_name'] ?: __( 'there', 'glowbook' ),
            $code,
            get_bloginfo( 'name' )
        );

        return wp_mail( $customer['email'], $subject, $message );
    }

    /**
     * Mask email address.
     *
     * @param string $email Email address.
     * @return string
     */
    private function mask_email( $email ) {
        $parts = explode( '@', $email );
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        $masked = substr( $name, 0, 2 ) . str_repeat( '*', max( strlen( $name ) - 2, 3 ) );

        return $masked . '@' . $domain;
    }

    /**
     * Process cancellation refund.
     *
     * @param array $booking Booking data.
     * @return array
     */
    private function process_cancellation_refund( $booking ) {
        $booking_id = $booking['id'];
        $deposit_amount = (float) get_post_meta( $booking_id, '_sodek_gb_deposit_amount', true );

        if ( $deposit_amount <= 0 ) {
            return array(
                'refunded' => false,
                'amount'   => 0,
                'reason'   => 'no_deposit',
            );
        }

        $booking_datetime = strtotime( $booking['booking_date'] . ' ' . $booking['start_time'] );
        $hours_until = ( $booking_datetime - time() ) / 3600;
        $cancel_notice = (int) get_option( 'sodek_gb_cancellation_notice', get_option( 'sodek_gb_cancel_notice', 24 ) );
        $refund_policy = (string) get_option( 'sodek_gb_cancellation_refund', get_option( 'sodek_gb_cancel_refund_policy', 'full' ) );

        if ( $hours_until < $cancel_notice || $refund_policy === 'none' ) {
            return array(
                'refunded' => false,
                'amount'   => 0,
                'reason'   => $hours_until < $cancel_notice ? 'late_cancellation' : 'policy',
            );
        }

        $refund_amount = $deposit_amount;

        if ( $refund_policy === 'partial' ) {
            $partial_percent = (int) get_option( 'sodek_gb_cancellation_refund_percent', 50 );
            $refund_amount = $deposit_amount * ( $partial_percent / 100 );
        }

        $payment_id = $this->get_refundable_square_payment_id( $booking_id );

        if ( empty( $payment_id ) ) {
            return array(
                'refunded' => false,
                'amount'   => 0,
                'reason'   => 'missing_payment_id',
                'error'    => __( 'No Square payment record was found for this booking.', 'glowbook' ),
            );
        }

        $refund_reason = sprintf(
            /* translators: %d: booking ID */
            __( 'Customer cancellation refund for booking #%d', 'glowbook' ),
            $booking_id
        );

        $refund_transaction_id = Sodek_GB_Transaction::create(
            array(
                'gateway'          => 'square',
                'environment'      => get_option( 'sodek_gb_square_environment', 'sandbox' ),
                'amount'           => $refund_amount,
                'currency'         => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'sodek_gb_currency', 'USD' ),
                'transaction_type' => Sodek_GB_Transaction::TYPE_REFUND,
                'status'           => Sodek_GB_Transaction::STATUS_PENDING,
                'customer_email'   => $booking['customer_email'] ?? '',
                'customer_name'    => $booking['customer_name'] ?? '',
                'booking_id'       => $booking_id,
                'square_payment_id'=> $payment_id,
                'request_data'     => array(
                    'booking_id' => $booking_id,
                    'amount'     => $refund_amount,
                    'reason'     => $refund_reason,
                ),
            )
        );

        $refund_result = Sodek_GB_Payment_Manager::process_refund( 'square', $payment_id, $refund_amount, $refund_reason );

        if ( empty( $refund_result['success'] ) ) {
            if ( $refund_transaction_id ) {
                Sodek_GB_Transaction::update(
                    $refund_transaction_id,
                    array(
                        'status'        => Sodek_GB_Transaction::STATUS_FAILED,
                        'error_code'    => $refund_result['error']['code'] ?? 'refund_failed',
                        'error_message' => $refund_result['error']['message'] ?? __( 'Refund failed.', 'glowbook' ),
                    )
                );
            }

            return array(
                'refunded' => false,
                'amount'   => 0,
                'reason'   => 'refund_failed',
                'error'    => $refund_result['error']['message'] ?? __( 'Refund failed.', 'glowbook' ),
            );
        }

        if ( $refund_transaction_id ) {
            Sodek_GB_Transaction::update(
                $refund_transaction_id,
                array(
                    'status'        => Sodek_GB_Transaction::STATUS_COMPLETED,
                    'response_data' => $refund_result['data'] ?? array(),
                    'booking_id'    => $booking_id,
                )
            );
        }

        update_post_meta( $booking_id, '_sodek_gb_refund_amount', $refund_amount );
        update_post_meta( $booking_id, '_sodek_gb_refunded_at', current_time( 'mysql' ) );
        update_post_meta( $booking_id, '_sodek_gb_refund_payment_id', $refund_result['data']['refund_id'] ?? '' );

        return array(
            'refunded' => true,
            'amount'   => $refund_amount,
            'refund_id'=> $refund_result['data']['refund_id'] ?? '',
        );
    }

    /**
     * Locate the original Square payment to refund for a booking.
     *
     * Prefers the initial deposit payment and skips any later balance payment.
     *
     * @param int $booking_id Booking ID.
     * @return string
     */
    private function get_refundable_square_payment_id( int $booking_id ): string {
        $balance_payment_id = (string) get_post_meta( $booking_id, '_sodek_gb_balance_payment_id', true );
        $transactions       = Sodek_GB_Transaction::get_by_booking( $booking_id );

        foreach ( $transactions as $transaction ) {
            if (
                Sodek_GB_Transaction::TYPE_PAYMENT === ( $transaction['transaction_type'] ?? '' ) &&
                Sodek_GB_Transaction::STATUS_COMPLETED === ( $transaction['status'] ?? '' ) &&
                ! empty( $transaction['square_payment_id'] ) &&
                $transaction['square_payment_id'] !== $balance_payment_id
            ) {
                return (string) $transaction['square_payment_id'];
            }
        }

        foreach ( $transactions as $transaction ) {
            if (
                Sodek_GB_Transaction::TYPE_PAYMENT === ( $transaction['transaction_type'] ?? '' ) &&
                Sodek_GB_Transaction::STATUS_COMPLETED === ( $transaction['status'] ?? '' ) &&
                ! empty( $transaction['square_payment_id'] )
            ) {
                return (string) $transaction['square_payment_id'];
            }
        }

        return '';
    }
}
