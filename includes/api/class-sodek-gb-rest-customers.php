<?php
/**
 * Customer REST API Endpoints.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_REST_Customers class.
 */
class Sodek_GB_REST_Customers {

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
        // Create/find customer by phone or email
        register_rest_route(
            $this->namespace,
            '/customers',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_or_find_customer' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'phone' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'email' => array(
                            'type'              => 'string',
                            'format'            => 'email',
                            'sanitize_callback' => 'sanitize_email',
                        ),
                        'first_name' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'last_name' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Send verification code
        register_rest_route(
            $this->namespace,
            '/customers/verify',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'send_verification_code' ),
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

        // Confirm verification code
        register_rest_route(
            $this->namespace,
            '/customers/verify/confirm',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'confirm_verification' ),
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

        // Get customer's saved cards
        register_rest_route(
            $this->namespace,
            '/customers/(?P<id>\d+)/cards',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_cards' ),
                    'permission_callback' => array( $this, 'check_customer_permission' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'add_card' ),
                    'permission_callback' => array( $this, 'check_customer_permission' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'card_nonce' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Delete a saved card
        register_rest_route(
            $this->namespace,
            '/customers/(?P<customer_id>\d+)/cards/(?P<card_id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_card' ),
                    'permission_callback' => array( $this, 'check_customer_card_permission' ),
                    'args'                => array(
                        'customer_id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'card_id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
            )
        );

        // Set default card
        register_rest_route(
            $this->namespace,
            '/customers/(?P<customer_id>\d+)/cards/(?P<card_id>\d+)/default',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'set_default_card' ),
                    'permission_callback' => array( $this, 'check_customer_card_permission' ),
                    'args'                => array(
                        'customer_id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'card_id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
            )
        );

        // Get customer profile
        register_rest_route(
            $this->namespace,
            '/customers/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_customer' ),
                    'permission_callback' => array( $this, 'check_customer_permission' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_customer' ),
                    'permission_callback' => array( $this, 'check_customer_permission' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
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
                    ),
                ),
            )
        );

        // Get customer bookings
        register_rest_route(
            $this->namespace,
            '/customers/(?P<id>\d+)/bookings',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_bookings' ),
                    'permission_callback' => array( $this, 'check_customer_permission' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'status' => array(
                            'type'              => 'string',
                            'enum'              => array( 'upcoming', 'past', 'all' ),
                            'default'           => 'all',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Create or find a customer.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function create_or_find_customer( $request ) {
        $phone = $request->get_param( 'phone' );
        $email = $request->get_param( 'email' );

        if ( empty( $phone ) && empty( $email ) ) {
            return new WP_Error(
                'missing_identifier',
                __( 'Phone or email is required.', 'glowbook' ),
                array( 'status' => 400 )
            );
        }

        $data = array(
            'first_name' => $request->get_param( 'first_name' ),
            'last_name'  => $request->get_param( 'last_name' ),
            'email'      => $email,
        );

        // Try phone first, then email
        if ( $phone ) {
            $customer = Sodek_GB_Customer::get_or_create_by_phone( $phone, $data );
        } else {
            $customer = Sodek_GB_Customer::get_or_create_by_email( $email, $data );
        }

        if ( is_wp_error( $customer ) ) {
            return $customer;
        }

        return rest_ensure_response(
            array(
                'customer_id' => $customer['id'],
                'is_new'      => ! empty( $customer['is_new'] ),
                'verified'    => ! empty( $customer['phone_verified'] ) || ! empty( $customer['email_verified'] ),
            )
        );
    }

    /**
     * Send verification code.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function send_verification_code( $request ) {
        $identifier = $request->get_param( 'identifier' );

        // Determine if phone or email
        $is_phone = preg_match( '/^[\d\s\-\+\(\)]+$/', $identifier );

        if ( $is_phone ) {
            $customer = Sodek_GB_Customer::get_by_phone( $identifier );
            $method = 'phone';
        } else {
            $customer = Sodek_GB_Customer::get_by_email( $identifier );
            $method = 'email';
        }

        if ( ! $customer ) {
            return new WP_Error(
                'customer_not_found',
                __( 'No account found with this phone number or email.', 'glowbook' ),
                array( 'status' => 404 )
            );
        }

        // Generate and send code
        $code = Sodek_GB_Customer::generate_verification_code( $customer['id'] );

        if ( is_wp_error( $code ) ) {
            return $code;
        }

        // Send code via email (SMS when enabled)
        if ( $method === 'email' || ! get_option( 'sodek_gb_sms_enabled', false ) ) {
            $sent = self::send_verification_email( $customer, $code );
        } else {
            // SMS sending would go here when enabled
            $sent = self::send_verification_email( $customer, $code );
        }

        if ( ! $sent ) {
            return new WP_Error(
                'send_failed',
                __( 'Failed to send verification code.', 'glowbook' ),
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response(
            array(
                'customer_id' => $customer['id'],
                'method'      => $method,
                'message'     => $method === 'email'
                    ? sprintf( __( 'Verification code sent to %s', 'glowbook' ), self::mask_email( $customer['email'] ) )
                    : sprintf( __( 'Verification code sent to %s', 'glowbook' ), self::mask_phone( $customer['phone'] ) ),
            )
        );
    }

    /**
     * Confirm verification code.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function confirm_verification( $request ) {
        $customer_id = $request->get_param( 'customer_id' );
        $code = $request->get_param( 'code' );

        $verified = Sodek_GB_Customer::verify_code( $customer_id, $code );

        if ( is_wp_error( $verified ) ) {
            return $verified;
        }

        // Generate login token
        $token = Sodek_GB_Customer::generate_login_token( $customer_id );

        return rest_ensure_response(
            array(
                'verified' => true,
                'token'    => $token,
            )
        );
    }

    /**
     * Get customer profile.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_customer( $request ) {
        $customer_id = $request->get_param( 'id' );
        $customer = Sodek_GB_Customer::get( $customer_id );

        if ( ! $customer ) {
            return new WP_Error(
                'customer_not_found',
                __( 'Customer not found.', 'glowbook' ),
                array( 'status' => 404 )
            );
        }

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
    public function update_customer( $request ) {
        $customer_id = $request->get_param( 'id' );

        $data = array();
        $allowed = array( 'first_name', 'last_name', 'email', 'hair_type', 'hair_length', 'allergies', 'notes' );

        foreach ( $allowed as $field ) {
            if ( $request->has_param( $field ) ) {
                $data[ $field ] = $request->get_param( $field );
            }
        }

        $updated = Sodek_GB_Customer::update( $customer_id, $data );

        if ( is_wp_error( $updated ) ) {
            return $updated;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __( 'Profile updated.', 'glowbook' ),
            )
        );
    }

    /**
     * Get customer's saved cards.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_cards( $request ) {
        $customer_id = $request->get_param( 'id' );
        $cards = Sodek_GB_Customer::get_cards( $customer_id );

        // Mask card numbers for security
        $cards = array_map(
            function( $card ) {
                return array(
                    'id'         => $card['id'],
                    'brand'      => $card['card_brand'],
                    'last4'      => $card['card_last4'],
                    'exp_month'  => $card['card_exp_month'],
                    'exp_year'   => $card['card_exp_year'],
                    'is_default' => (bool) $card['is_default'],
                );
            },
            $cards
        );

        return rest_ensure_response( $cards );
    }

    /**
     * Add a new card.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function add_card( $request ) {
        $customer_id = $request->get_param( 'id' );
        $card_nonce = $request->get_param( 'card_nonce' );

        // Process card with Square
        $card_data = apply_filters( 'sodek_gb_process_card_nonce', null, $card_nonce, $customer_id );

        if ( is_wp_error( $card_data ) ) {
            return $card_data;
        }

        if ( ! $card_data ) {
            return new WP_Error(
                'card_processing_failed',
                __( 'Failed to process card.', 'glowbook' ),
                array( 'status' => 400 )
            );
        }

        $saved = Sodek_GB_Customer::save_card( $customer_id, $card_data );

        if ( is_wp_error( $saved ) ) {
            return $saved;
        }

        $saved_card = Sodek_GB_Customer::get_card( $saved, $customer_id );

        return rest_ensure_response(
            array(
                'success' => true,
                'card'    => array(
                    'id'         => $saved,
                    'brand'      => $card_data['card_brand'],
                    'last4'      => $card_data['card_last4'],
                    'is_default' => ! empty( $saved_card['is_default'] ),
                ),
            )
        );
    }

    /**
     * Delete a saved card.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_card( $request ) {
        $customer_id = $request->get_param( 'customer_id' );
        $card_id = $request->get_param( 'card_id' );

        $card = Sodek_GB_Customer::get_card( $card_id, $customer_id );

        if ( ! $card ) {
            return new WP_Error(
                'card_not_found',
                __( 'Saved card not found.', 'glowbook' ),
                array( 'status' => 404 )
            );
        }

        $remote_delete = apply_filters( 'sodek_gb_delete_saved_card', null, $card, $customer_id );

        if ( is_wp_error( $remote_delete ) ) {
            return $remote_delete;
        }

        $deleted = Sodek_GB_Customer::delete_card( $card_id, $customer_id );

        if ( is_wp_error( $deleted ) ) {
            return $deleted;
        }

        if ( ! $deleted ) {
            return new WP_Error(
                'card_delete_failed',
                __( 'We could not remove that card.', 'glowbook' ),
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __( 'Card removed.', 'glowbook' ),
            )
        );
    }

    /**
     * Set a card as default.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function set_default_card( $request ) {
        $customer_id = $request->get_param( 'customer_id' );
        $card_id = $request->get_param( 'card_id' );

        $result = Sodek_GB_Customer::set_default_card( $card_id, $customer_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        if ( ! $result ) {
            return new WP_Error(
                'default_card_update_failed',
                __( 'We could not update the default card.', 'glowbook' ),
                array( 'status' => 500 )
            );
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __( 'Default card updated.', 'glowbook' ),
            )
        );
    }

    /**
     * Get customer bookings.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_bookings( $request ) {
        $customer_id = $request->get_param( 'id' );
        $status = $request->get_param( 'status' );

        $bookings = Sodek_GB_Customer::get_bookings( $customer_id, $status );

        return rest_ensure_response( $bookings );
    }

    /**
     * Check if the requester has access to this customer.
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error
     */
    public function check_customer_permission( $request ) {
        // Admin can access any customer
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Check portal session
        $customer_id = $request->get_param( 'id' );
        $session_customer = Sodek_GB_Customer_Portal::get_logged_in_customer();

        if ( $session_customer && (int) $session_customer['id'] === (int) $customer_id ) {
            return true;
        }

        // Check auth token in header
        $auth_token = $request->get_header( 'X-Customer-Token' );
        if ( $auth_token ) {
            $token_customer = Sodek_GB_Customer::validate_login_token( $auth_token );
            if ( $token_customer && (int) $token_customer['id'] === (int) $customer_id ) {
                return true;
            }
        }

        return new WP_Error(
            'unauthorized',
            __( 'You do not have permission to access this customer.', 'glowbook' ),
            array( 'status' => 403 )
        );
    }

    /**
     * Check if the requester has access to this customer's card.
     *
     * @param WP_REST_Request $request Request object.
     * @return bool|WP_Error
     */
    public function check_customer_card_permission( $request ) {
        $request->set_param( 'id', $request->get_param( 'customer_id' ) );
        return $this->check_customer_permission( $request );
    }

    /**
     * Send verification email.
     *
     * @param array  $customer Customer data.
     * @param string $code     Verification code.
     * @return bool
     */
    private static function send_verification_email( $customer, $code ) {
        if ( empty( $customer['email'] ) ) {
            return false;
        }

        $subject = sprintf(
            /* translators: %s: business name */
            __( 'Your verification code for %s', 'glowbook' ),
            get_bloginfo( 'name' )
        );

        $message = sprintf(
            /* translators: 1: customer name, 2: verification code, 3: business name */
            __(
                "Hi %1\$s,\n\nYour verification code is: %2\$s\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, you can ignore this email.\n\nThanks,\n%3\$s",
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
    private static function mask_email( $email ) {
        $parts = explode( '@', $email );
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        $masked = substr( $name, 0, 2 ) . str_repeat( '*', max( strlen( $name ) - 2, 3 ) );

        return $masked . '@' . $domain;
    }

    /**
     * Mask phone number.
     *
     * @param string $phone Phone number.
     * @return string
     */
    private static function mask_phone( $phone ) {
        $digits = preg_replace( '/\D/', '', $phone );
        $length = strlen( $digits );

        if ( $length > 4 ) {
            return str_repeat( '*', $length - 4 ) . substr( $digits, -4 );
        }

        return $phone;
    }
}
