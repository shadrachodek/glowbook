<?php
/**
 * Customer Profile Management.
 *
 * Handles phone-based customer profiles, verification, preferences, and cards on file.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Customer class.
 */
class Sodek_GB_Customer {

    /**
     * Customer data.
     *
     * @var array
     */
    private $data = array();

    /**
     * Constructor.
     *
     * @param int|array $customer Customer ID or data array.
     */
    public function __construct( $customer = 0 ) {
        if ( is_numeric( $customer ) && $customer > 0 ) {
            $this->data = self::get_by_id( $customer );
        } elseif ( is_array( $customer ) ) {
            $this->data = $customer;
        }
    }

    /**
     * Get customer property.
     *
     * @param string $key Property key.
     * @return mixed
     */
    public function get( $key ) {
        return $this->data[ $key ] ?? null;
    }

    /**
     * Get all customer data.
     *
     * @return array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * Check if customer exists.
     *
     * @return bool
     */
    public function exists() {
        return ! empty( $this->data['id'] );
    }

    /**
     * Get customer by ID.
     *
     * @param int $customer_id Customer ID.
     * @return array|null
     */
    public static function get_by_id( $customer_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $customer_id ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Get customer by phone number.
     *
     * @param string $phone Phone number.
     * @return array|null
     */
    public static function get_by_phone( $phone ) {
        global $wpdb;

        $phone = self::normalize_phone( $phone );
        $table = $wpdb->prefix . 'sodek_gb_customers';

        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE phone = %s", $phone ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Get customer by email.
     *
     * @param string $email Email address.
     * @return array|null
     */
    public static function get_by_email( $email ) {
        global $wpdb;

        $email = self::normalize_email( $email );

        if ( empty( $email ) ) {
            return null;
        }

        $table = $wpdb->prefix . 'sodek_gb_customers';
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE email = %s", $email ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Get or create customer by phone.
     *
     * @param string $phone Phone number.
     * @param array  $data  Optional additional data.
     * @return array Customer data with 'id' key.
     */
    public static function get_or_create_by_phone( $phone, $data = array() ) {
        $existing = self::get_by_phone( $phone );

        if ( $existing ) {
            // Update with any new data provided
            if ( ! empty( $data ) ) {
                self::update( $existing['id'], $data );
                $existing = self::get_by_id( $existing['id'] );
            }
            return $existing;
        }

        // Create new customer
        $data['phone'] = $phone;
        $id = self::create( $data );

        return self::get_by_id( $id );
    }

    /**
     * Get or create customer by email.
     *
     * Ensures email-only customers still get a unique profile record even when
     * the customer table requires a unique phone column.
     *
     * @param string $email Email address.
     * @param array  $data  Optional additional data.
     * @return array|null Customer data with 'id' key.
     */
    public static function get_or_create_by_email( $email, $data = array() ) {
        $email = self::normalize_email( $email );

        if ( empty( $email ) ) {
            return null;
        }

        $existing = self::get_by_email( $email );

        if ( $existing ) {
            if ( ! empty( $data ) ) {
                $update_data = $data;
                unset( $update_data['phone'] );
                self::update( $existing['id'], $update_data );
                $existing = self::get_by_id( $existing['id'] );
            }

            return $existing;
        }

        $data['email'] = $email;

        if ( empty( $data['phone'] ) ) {
            $data['phone'] = self::generate_placeholder_phone( $email );
        }

        $id = self::create( $data );

        return $id ? self::get_by_id( $id ) : null;
    }

    /**
     * Determine whether a customer should be treated as returning.
     *
     * Uses the customer table first, then falls back to booking history matched
     * by email or phone so older/imported bookings still qualify.
     *
     * @param string $email Email address.
     * @param string $phone Phone number.
     * @return bool
     */
    public static function is_returning_customer( $email = '', $phone = '' ) {
        $email = self::normalize_email( $email );
        $phone = self::normalize_phone( $phone );

        if ( empty( $email ) && empty( $phone ) ) {
            return false;
        }

        $customer = null;

        if ( ! empty( $email ) ) {
            $customer = self::get_by_email( $email );
        }

        if ( ! $customer && ! empty( $phone ) ) {
            $customer = self::get_by_phone( $phone );
        }

        if ( $customer ) {
            if ( ! empty( $customer['total_bookings'] ) && (int) $customer['total_bookings'] > 0 ) {
                return true;
            }

            if ( ! empty( self::get_bookings( $customer['id'], '', 1 ) ) ) {
                return true;
            }
        }

        return self::has_booking_history( $email, $phone );
    }

    /**
     * Create a new customer.
     *
     * @param array $data Customer data.
     * @return int|false Customer ID or false on failure.
     */
    public static function create( $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';

        // Normalize phone
        if ( ! empty( $data['phone'] ) ) {
            $data['phone'] = self::normalize_phone( $data['phone'] );
        }

        if ( ! empty( $data['email'] ) ) {
            $data['email'] = self::normalize_email( $data['email'] );
        }

        if ( empty( $data['phone'] ) && ! empty( $data['email'] ) ) {
            $data['phone'] = self::generate_placeholder_phone( $data['email'] );
        }

        // Prepare insert data
        $insert_data = array(
            'phone'              => $data['phone'] ?? '',
            'phone_country_code' => $data['phone_country_code'] ?? '+1',
            'email'              => $data['email'] ?? null,
            'first_name'         => $data['first_name'] ?? null,
            'last_name'          => $data['last_name'] ?? null,
            'wp_user_id'         => $data['wp_user_id'] ?? null,
            'preferred_staff_id' => $data['preferred_staff_id'] ?? null,
            'hair_type'          => $data['hair_type'] ?? null,
            'hair_length'        => $data['hair_length'] ?? null,
            'allergies'          => $data['allergies'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'sms_opt_in'         => $data['sms_opt_in'] ?? 1,
            'email_opt_in'       => $data['email_opt_in'] ?? 1,
        );

        $result = $wpdb->insert( $table, $insert_data );

        if ( ! $result ) {
            return false;
        }

        $customer_id = $wpdb->insert_id;

        do_action( 'sodek_gb_customer_created', $customer_id, $insert_data );

        return $customer_id;
    }

    /**
     * Update customer data.
     *
     * @param int   $customer_id Customer ID.
     * @param array $data        Data to update.
     * @return bool
     */
    public static function update( $customer_id, $data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';

        // Normalize phone if being updated
        if ( ! empty( $data['phone'] ) ) {
            $data['phone'] = self::normalize_phone( $data['phone'] );
        }

        if ( array_key_exists( 'email', $data ) ) {
            $data['email'] = self::normalize_email( $data['email'] );
        }

        if (
            array_key_exists( 'phone', $data ) &&
            empty( $data['phone'] ) &&
            ! empty( $data['email'] )
        ) {
            $data['phone'] = self::generate_placeholder_phone( $data['email'], $customer_id );
        }

        // Remove any keys that shouldn't be directly updated
        unset( $data['id'], $data['created_at'] );

        $result = $wpdb->update(
            $table,
            $data,
            array( 'id' => $customer_id )
        );

        if ( false !== $result ) {
            do_action( 'sodek_gb_customer_updated', $customer_id, $data );
        }

        return false !== $result;
    }

    /**
     * Normalize phone number.
     *
     * Removes all non-numeric characters except leading +.
     *
     * @param string $phone Phone number.
     * @return string
     */
    public static function normalize_phone( $phone ) {
        // Remove all non-numeric except leading +
        $phone = preg_replace( '/[^0-9+]/', '', $phone );

        // Ensure starts with + if it has country code
        if ( strlen( $phone ) > 10 && substr( $phone, 0, 1 ) !== '+' ) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Normalize email address for lookups and writes.
     *
     * @param string $email Email address.
     * @return string
     */
    public static function normalize_email( $email ) {
        $email = sanitize_email( wp_unslash( (string) $email ) );
        return strtolower( trim( $email ) );
    }

    /**
     * Generate a unique placeholder phone value for email-only customers.
     *
     * @param string   $email               Email address.
     * @param int|null $ignore_customer_id  Existing customer ID to ignore.
     * @return string
     */
    private static function generate_placeholder_phone( $email, $ignore_customer_id = null ) {
        global $wpdb;

        $table   = $wpdb->prefix . 'sodek_gb_customers';
        $seed    = self::normalize_email( $email );
        $counter = 0;

        do {
            $suffix = substr( sprintf( '%u', crc32( $seed . '|' . $counter ) ), 0, 10 );
            $phone  = '+999' . $suffix;
            $found  = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table WHERE phone = %s",
                    $phone
                )
            );
            $counter++;
        } while ( $found && (int) $found !== (int) $ignore_customer_id );

        return $phone;
    }

    /**
     * Generate verification code.
     *
     * @param int $customer_id Customer ID.
     * @return string 6-digit code.
     */
    public static function generate_verification_code( $customer_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';
        $code = str_pad( wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
        $expires = gmdate( 'Y-m-d H:i:s', strtotime( '+10 minutes' ) );

        $wpdb->update(
            $table,
            array(
                'last_verification_code'    => $code,
                'verification_code_expires' => $expires,
            ),
            array( 'id' => $customer_id )
        );

        return $code;
    }

    /**
     * Verify customer phone with code.
     *
     * @param int    $customer_id Customer ID.
     * @param string $code        Verification code.
     * @return bool|WP_Error
     */
    public static function verify_code( $customer_id, $code ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';
        $customer = self::get_by_id( $customer_id );

        if ( ! $customer ) {
            return new WP_Error( 'invalid_customer', __( 'Customer not found.', 'glowbook' ) );
        }

        // Check if code matches
        if ( $customer['last_verification_code'] !== $code ) {
            return new WP_Error( 'invalid_code', __( 'Invalid verification code.', 'glowbook' ) );
        }

        // Check if code expired
        if ( strtotime( $customer['verification_code_expires'] ) < time() ) {
            return new WP_Error( 'expired_code', __( 'Verification code has expired.', 'glowbook' ) );
        }

        // Mark as verified
        $wpdb->update(
            $table,
            array(
                'phone_verified'            => 1,
                'phone_verified_at'         => current_time( 'mysql' ),
                'last_verification_code'    => null,
                'verification_code_expires' => null,
            ),
            array( 'id' => $customer_id )
        );

        do_action( 'sodek_gb_customer_verified', $customer_id );

        return true;
    }

    /**
     * Check if phone is verified.
     *
     * @param int $customer_id Customer ID.
     * @return bool
     */
    public static function is_verified( $customer_id ) {
        $customer = self::get_by_id( $customer_id );
        return $customer && (bool) $customer['phone_verified'];
    }

    /**
     * Get customer's full name.
     *
     * @param int $customer_id Customer ID.
     * @return string
     */
    public static function get_full_name( $customer_id ) {
        $customer = self::get_by_id( $customer_id );

        if ( ! $customer ) {
            return '';
        }

        $name = trim( ( $customer['first_name'] ?? '' ) . ' ' . ( $customer['last_name'] ?? '' ) );

        return $name ?: __( 'Guest', 'glowbook' );
    }

    /**
     * Increment booking count and total spent.
     *
     * @param int   $customer_id Customer ID.
     * @param float $amount      Amount spent.
     * @return bool
     */
    public static function record_booking( $customer_id, $amount = 0 ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';

        return (bool) $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table SET total_bookings = total_bookings + 1, total_spent = total_spent + %f WHERE id = %d",
                $amount,
                $customer_id
            )
        );
    }

    /**
     * Record a no-show.
     *
     * @param int $customer_id Customer ID.
     * @return bool
     */
    public static function record_no_show( $customer_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';

        return (bool) $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table SET no_show_count = no_show_count + 1 WHERE id = %d",
                $customer_id
            )
        );
    }

    /**
     * Check booking history directly by email or phone.
     *
     * @param string $email Email address.
     * @param string $phone Phone number.
     * @return bool
     */
    private static function has_booking_history( $email = '', $phone = '' ) {
        $meta_query = array( 'relation' => 'OR' );

        if ( ! empty( $email ) ) {
            $meta_query[] = array(
                'key'   => '_sodek_gb_customer_email',
                'value' => $email,
            );
        }

        if ( ! empty( $phone ) ) {
            $meta_query[] = array(
                'key'   => '_sodek_gb_customer_phone',
                'value' => $phone,
            );
        }

        if ( count( $meta_query ) <= 1 ) {
            return false;
        }

        $query = new WP_Query( array(
            'post_type'      => 'sodek_gb_booking',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => $meta_query,
        ) );

        return ! empty( $query->posts );
    }

    /**
     * Get customer's saved cards.
     *
     * @param int $customer_id Customer ID.
     * @return array
     */
    public static function get_cards( $customer_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customer_cards';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE customer_id = %d ORDER BY is_default DESC, created_at DESC",
                $customer_id
            ),
            ARRAY_A
        );
    }

    /**
     * Get a single saved card for the customer.
     *
     * @param int $card_id     Local card record ID.
     * @param int $customer_id Customer ID.
     * @return array|null
     */
    public static function get_card( $card_id, $customer_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customer_cards';

        $card = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d AND customer_id = %d",
                $card_id,
                $customer_id
            ),
            ARRAY_A
        );

        return $card ?: null;
    }

    /**
     * Save a card for the customer.
     *
     * @param int   $customer_id Customer ID.
     * @param array $card_data   Card data from gateway.
     * @return int|false Card ID or false.
     */
    public static function save_card( $customer_id, $card_data ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customer_cards';

        // Check if this card already exists
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE customer_id = %d AND gateway = %s AND card_id = %s",
                $customer_id,
                $card_data['gateway'] ?? 'square',
                $card_data['card_id']
            )
        );

        if ( $existing ) {
            return (int) $existing;
        }

        // If this is the first card, make it default
        $card_count = $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE customer_id = %d", $customer_id )
        );

        $result = $wpdb->insert(
            $table,
            array(
                'customer_id'    => $customer_id,
                'gateway'        => $card_data['gateway'] ?? 'square',
                'card_id'        => $card_data['card_id'],
                'card_brand'     => $card_data['card_brand'] ?? null,
                'card_last4'     => $card_data['card_last4'] ?? null,
                'card_exp_month' => $card_data['card_exp_month'] ?? null,
                'card_exp_year'  => $card_data['card_exp_year'] ?? null,
                'is_default'     => $card_count == 0 ? 1 : 0,
            )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Delete a saved card.
     *
     * @param int $card_id     Card ID.
     * @param int $customer_id Customer ID (for security).
     * @return bool
     */
    public static function delete_card( $card_id, $customer_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customer_cards';

        $card = self::get_card( $card_id, $customer_id );

        if ( ! $card ) {
            return false;
        }

        $deleted = (bool) $wpdb->delete(
            $table,
            array(
                'id'          => $card_id,
                'customer_id' => $customer_id,
            )
        );

        if ( $deleted && ! empty( $card['is_default'] ) ) {
            $next_card_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM $table WHERE customer_id = %d ORDER BY id ASC LIMIT 1",
                    $customer_id
                )
            );

            if ( $next_card_id ) {
                self::set_default_card( (int) $next_card_id, $customer_id );
            }
        }

        return $deleted;
    }

    /**
     * Set default card.
     *
     * @param int $card_id     Card ID.
     * @param int $customer_id Customer ID.
     * @return bool
     */
    public static function set_default_card( $card_id, $customer_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customer_cards';

        // Unset all defaults for this customer
        $wpdb->update(
            $table,
            array( 'is_default' => 0 ),
            array( 'customer_id' => $customer_id )
        );

        // Set new default
        return (bool) $wpdb->update(
            $table,
            array( 'is_default' => 1 ),
            array(
                'id'          => $card_id,
                'customer_id' => $customer_id,
            )
        );
    }

    /**
     * Get customer-specific meta stored outside the core customer table.
     *
     * @param int    $customer_id Customer ID.
     * @param string $key         Meta key.
     * @param mixed  $default     Default value.
     * @return mixed
     */
    public static function get_meta( $customer_id, $key, $default = null ) {
        $meta = get_option( self::get_meta_option_name( $customer_id ), array() );

        if ( ! is_array( $meta ) ) {
            return $default;
        }

        return array_key_exists( $key, $meta ) ? $meta[ $key ] : $default;
    }

    /**
     * Update customer-specific meta stored outside the core customer table.
     *
     * @param int    $customer_id Customer ID.
     * @param string $key         Meta key.
     * @param mixed  $value       Meta value.
     * @return bool
     */
    public static function update_meta( $customer_id, $key, $value ) {
        $option_name = self::get_meta_option_name( $customer_id );
        $meta        = get_option( $option_name, array() );

        if ( ! is_array( $meta ) ) {
            $meta = array();
        }

        $meta[ $key ] = $value;

        if ( false === get_option( $option_name, false ) ) {
            return add_option( $option_name, $meta, '', false );
        }

        return update_option( $option_name, $meta, false );
    }

    /**
     * Delete customer-specific meta stored outside the core customer table.
     *
     * @param int    $customer_id Customer ID.
     * @param string $key         Meta key.
     * @return bool
     */
    public static function delete_meta( $customer_id, $key ) {
        $option_name = self::get_meta_option_name( $customer_id );
        $meta        = get_option( $option_name, array() );

        if ( ! is_array( $meta ) || ! array_key_exists( $key, $meta ) ) {
            return true;
        }

        unset( $meta[ $key ] );

        if ( empty( $meta ) ) {
            return delete_option( $option_name );
        }

        return update_option( $option_name, $meta, false );
    }

    /**
     * Build the option name used for customer meta storage.
     *
     * @param int $customer_id Customer ID.
     * @return string
     */
    private static function get_meta_option_name( $customer_id ) {
        return 'sodek_gb_customer_meta_' . absint( $customer_id );
    }

    /**
     * Get customer's bookings.
     *
     * @param int    $customer_id Customer ID.
     * @param string $status      Optional status filter.
     * @param int    $limit       Max results.
     * @return array
     */
    public static function get_bookings( $customer_id, $status = '', $limit = 20 ) {
        $customer = self::get_by_id( $customer_id );

        if ( ! $customer ) {
            return array();
        }

        $args = array(
            'post_type'      => 'sodek_gb_booking',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'meta_query'     => array(
                'relation' => 'OR',
                array(
                    'key'   => '_sodek_gb_customer_id',
                    'value' => $customer_id,
                ),
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => '_sodek_gb_booking_date',
            'order'          => 'DESC',
        );

        // Also match by email/phone if available
        if ( ! empty( $customer['email'] ) ) {
            $args['meta_query'][] = array(
                'key'   => '_sodek_gb_customer_email',
                'value' => $customer['email'],
            );
        }

        if ( ! empty( $customer['phone'] ) ) {
            $args['meta_query'][] = array(
                'key'   => '_sodek_gb_customer_phone',
                'value' => $customer['phone'],
            );
        }

        if ( ! empty( $status ) ) {
            $args['meta_query'][] = array(
                'key'   => '_sodek_gb_status',
                'value' => $status,
            );
        }

        $query = new WP_Query( $args );
        $bookings = array();

        foreach ( $query->posts as $post ) {
            $bookings[] = Sodek_GB_Booking::get_booking( $post->ID );
        }

        return $bookings;
    }

    /**
     * Get upcoming bookings for customer.
     *
     * @param int $customer_id Customer ID.
     * @param int $limit       Max results.
     * @return array
     */
    public static function get_upcoming_bookings( $customer_id, $limit = 10 ) {
        $customer = self::get_by_id( $customer_id );

        if ( ! $customer ) {
            return array();
        }

        $args = array(
            'post_type'      => 'sodek_gb_booking',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key'   => '_sodek_gb_customer_id',
                        'value' => $customer_id,
                    ),
                    array(
                        'key'   => '_sodek_gb_customer_email',
                        'value' => $customer['email'] ?? '',
                    ),
                    array(
                        'key'   => '_sodek_gb_customer_phone',
                        'value' => $customer['phone'] ?? '',
                    ),
                ),
                array(
                    'key'     => '_sodek_gb_booking_date',
                    'value'   => current_time( 'Y-m-d' ),
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
                array(
                    'key'     => '_sodek_gb_status',
                    'value'   => array( 'pending', 'confirmed' ),
                    'compare' => 'IN',
                ),
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => '_sodek_gb_booking_date',
            'order'          => 'ASC',
        );

        $query = new WP_Query( $args );
        $bookings = array();

        foreach ( $query->posts as $post ) {
            $bookings[] = Sodek_GB_Booking::get_booking( $post->ID );
        }

        return $bookings;
    }

    /**
     * Get past bookings for customer.
     *
     * @param int $customer_id Customer ID.
     * @param int $limit       Max results.
     * @return array
     */
    public static function get_past_bookings( $customer_id, $limit = 20 ) {
        $customer = self::get_by_id( $customer_id );

        if ( ! $customer ) {
            return array();
        }

        $args = array(
            'post_type'      => 'sodek_gb_booking',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'relation' => 'OR',
                    array(
                        'key'   => '_sodek_gb_customer_id',
                        'value' => $customer_id,
                    ),
                    array(
                        'key'   => '_sodek_gb_customer_email',
                        'value' => $customer['email'] ?? '',
                    ),
                ),
                array(
                    'key'     => '_sodek_gb_booking_date',
                    'value'   => current_time( 'Y-m-d' ),
                    'compare' => '<',
                    'type'    => 'DATE',
                ),
            ),
            'orderby'        => 'meta_value',
            'meta_key'       => '_sodek_gb_booking_date',
            'order'          => 'DESC',
        );

        $query = new WP_Query( $args );
        $bookings = array();

        foreach ( $query->posts as $post ) {
            $bookings[] = Sodek_GB_Booking::get_booking( $post->ID );
        }

        return $bookings;
    }

    /**
     * Link customer to WordPress user.
     *
     * @param int $customer_id Customer ID.
     * @param int $user_id     WordPress user ID.
     * @return bool
     */
    public static function link_to_user( $customer_id, $user_id ) {
        return self::update( $customer_id, array( 'wp_user_id' => $user_id ) );
    }

    /**
     * Get customer by WordPress user ID.
     *
     * @param int $user_id WordPress user ID.
     * @return array|null
     */
    public static function get_by_user_id( $user_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE wp_user_id = %d", $user_id ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Get the account state for a customer profile.
     *
     * GlowBook intentionally supports a hybrid model:
     * - guest: booked but no reusable portal path is fully ready yet
     * - portal: customer can return through magic link / SMS verification
     * - wordpress: customer is linked to a native WordPress user account
     *
     * @param array $customer Customer row.
     * @return string
     */
    public static function get_account_state( $customer ) {
        if ( empty( $customer ) || ! is_array( $customer ) ) {
            return 'guest';
        }

        if ( ! empty( $customer['wp_user_id'] ) ) {
            return 'wordpress';
        }

        $has_email_path = ! empty( $customer['email'] ) && ! empty( $customer['email_opt_in'] );
        $has_sms_path   = ! empty( $customer['phone'] ) && ! empty( $customer['sms_opt_in'] );

        if ( $has_email_path || $has_sms_path ) {
            return 'portal';
        }

        return 'guest';
    }

    /**
     * Get a human label for a customer account state.
     *
     * @param array $customer Customer row.
     * @return string
     */
    public static function get_account_state_label( $customer ) {
        switch ( self::get_account_state( $customer ) ) {
            case 'wordpress':
                return __( 'Signed In', 'glowbook' );
            case 'portal':
                return __( 'Portal Ready', 'glowbook' );
            default:
                return __( 'Guest Booking', 'glowbook' );
        }
    }

    /**
     * Get a short description for a customer account state.
     *
     * @param array $customer Customer row.
     * @return string
     */
    public static function get_account_state_description( $customer ) {
        switch ( self::get_account_state( $customer ) ) {
            case 'wordpress':
                return __( 'This customer can use native WordPress authentication and stays linked to their GlowBook profile.', 'glowbook' );
            case 'portal':
                return __( 'This customer can return through magic links or verification-based portal access without a full WordPress account.', 'glowbook' );
            default:
                return __( 'This customer can still book appointments, but they do not have a reusable account path set up yet.', 'glowbook' );
        }
    }

    /**
     * Search customers.
     *
     * @param string $search Search term.
     * @param int    $limit  Max results.
     * @return array
     */
    public static function search( $search, $limit = 20 ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';
        $search_term = '%' . $wpdb->esc_like( $search ) . '%';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                WHERE phone LIKE %s
                   OR email LIKE %s
                   OR first_name LIKE %s
                   OR last_name LIKE %s
                ORDER BY created_at DESC
                LIMIT %d",
                $search_term,
                $search_term,
                $search_term,
                $search_term,
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Get all customers with pagination.
     *
     * @param int $page     Page number.
     * @param int $per_page Per page.
     * @return array
     */
    public static function get_all( $page = 1, $per_page = 20 ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_customers';
        $offset = ( $page - 1 ) * $per_page;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table" );

        return array(
            'customers' => $results,
            'total'     => (int) $total,
            'pages'     => ceil( $total / $per_page ),
            'page'      => $page,
            'per_page'  => $per_page,
        );
    }

    /**
     * Generate a portal login token.
     *
     * @param int $customer_id Customer ID.
     * @return string
     */
    public static function generate_login_token( $customer_id ) {
        $customer = self::get_by_id( $customer_id );

        if ( ! $customer ) {
            return '';
        }

        $token_data = array(
            'customer_id' => $customer_id,
            'phone'       => $customer['phone'],
            'expires'     => time() + HOUR_IN_SECONDS,
        );

        return base64_encode( wp_json_encode( $token_data ) ) . '.' . wp_hash( wp_json_encode( $token_data ) );
    }

    /**
     * Validate a portal login token.
     *
     * @param string $token Token string.
     * @return int|false Customer ID or false.
     */
    public static function validate_login_token( $token ) {
        $parts = explode( '.', $token );

        if ( count( $parts ) !== 2 ) {
            return false;
        }

        $data_json = base64_decode( $parts[0] );
        $hash = $parts[1];

        // Verify hash
        if ( wp_hash( $data_json ) !== $hash ) {
            return false;
        }

        $data = json_decode( $data_json, true );

        if ( ! $data || empty( $data['customer_id'] ) || empty( $data['expires'] ) ) {
            return false;
        }

        // Check expiration
        if ( $data['expires'] < time() ) {
            return false;
        }

        return (int) $data['customer_id'];
    }
}
