<?php
/**
 * REST API endpoints.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_REST_API class.
 */
class Sodek_GB_REST_API {

    /**
     * Namespace.
     */
    const NAMESPACE = 'sodek-gb/v1';

    /**
     * Initialize.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    /**
     * Register REST routes.
     */
    public static function register_routes() {
        // Get services
        register_rest_route(
            self::NAMESPACE,
            '/services',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_services' ),
                'permission_callback' => '__return_true',
            )
        );

        // Get single service
        register_rest_route(
            self::NAMESPACE,
            '/services/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_service' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        },
                    ),
                ),
            )
        );

        // Get service categories
        register_rest_route(
            self::NAMESPACE,
            '/categories',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_categories' ),
                'permission_callback' => '__return_true',
            )
        );

        // Get services grouped by category
        register_rest_route(
            self::NAMESPACE,
            '/services/grouped',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_services_grouped' ),
                'permission_callback' => '__return_true',
            )
        );

        // Get services by category
        register_rest_route(
            self::NAMESPACE,
            '/categories/(?P<category>[\w-]+)/services',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_services_by_category' ),
                'permission_callback' => '__return_true',
            )
        );

        // Get add-ons for a service
        register_rest_route(
            self::NAMESPACE,
            '/services/(?P<id>\d+)/addons',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_service_addons' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'id' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        },
                    ),
                ),
            )
        );

        // Calculate booking total with add-ons
        register_rest_route(
            self::NAMESPACE,
            '/calculate-total',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'calculate_booking_total' ),
                'permission_callback' => '__return_true',
            )
        );

        // Get available dates
        register_rest_route(
            self::NAMESPACE,
            '/availability/dates',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_available_dates' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'service_id' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        },
                    ),
                    'year' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param ) && $param >= 2020 && $param <= 2100;
                        },
                    ),
                    'month' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param ) && $param >= 1 && $param <= 12;
                        },
                    ),
                ),
            )
        );

        // Get available time slots
        register_rest_route(
            self::NAMESPACE,
            '/availability/slots',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_available_slots' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'service_id' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        },
                    ),
                    'date' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $param );
                        },
                    ),
                ),
            )
        );

        // Check slot availability
        register_rest_route(
            self::NAMESPACE,
            '/availability/check',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'check_slot_availability' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'service_id' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        },
                    ),
                    'date' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $param );
                        },
                    ),
                    'time' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return preg_match( '/^\d{2}:\d{2}$/', $param );
                        },
                    ),
                ),
            )
        );

        // Admin endpoints (require authentication)
        register_rest_route(
            self::NAMESPACE,
            '/bookings',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_bookings' ),
                'permission_callback' => array( __CLASS__, 'admin_permission_check' ),
                'args'                => array(
                    'status' => array(
                        'required' => false,
                    ),
                    'date'   => array(
                        'required' => false,
                    ),
                    'per_page' => array(
                        'required' => false,
                        'default'  => 20,
                    ),
                    'page' => array(
                        'required' => false,
                        'default'  => 1,
                    ),
                ),
            )
        );

        // Get single booking
        register_rest_route(
            self::NAMESPACE,
            '/bookings/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_booking' ),
                'permission_callback' => array( __CLASS__, 'admin_permission_check' ),
            )
        );

        // Update booking
        register_rest_route(
            self::NAMESPACE,
            '/bookings/(?P<id>\d+)',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'update_booking' ),
                'permission_callback' => array( __CLASS__, 'admin_permission_check' ),
            )
        );

        // Blocked dates
        register_rest_route(
            self::NAMESPACE,
            '/availability/blocked',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_blocked_dates' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'year'  => array( 'required' => true ),
                    'month' => array( 'required' => true ),
                ),
            )
        );

        // Admin: Add blocked date
        register_rest_route(
            self::NAMESPACE,
            '/availability/block',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'add_blocked_date' ),
                'permission_callback' => array( __CLASS__, 'admin_permission_check' ),
            )
        );

        // Admin: Remove blocked date
        register_rest_route(
            self::NAMESPACE,
            '/availability/block/(?P<id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array( __CLASS__, 'remove_blocked_date' ),
                'permission_callback' => array( __CLASS__, 'admin_permission_check' ),
            )
        );

        // Calendar endpoint - get bookings for date range
        register_rest_route(
            self::NAMESPACE,
            '/bookings/calendar',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_calendar_bookings' ),
                'permission_callback' => array( __CLASS__, 'admin_permission_check' ),
                'args'                => array(
                    'start' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return preg_match( '/^\d{4}-\d{2}-\d{2}/', $param );
                        },
                    ),
                    'end' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return preg_match( '/^\d{4}-\d{2}-\d{2}/', $param );
                        },
                    ),
                    'service_id' => array( 'required' => false ),
                    'status'     => array( 'required' => false ),
                ),
            )
        );

        // Reschedule booking
        register_rest_route(
            self::NAMESPACE,
            '/bookings/(?P<id>\d+)/reschedule',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'reschedule_booking' ),
                'permission_callback' => array( __CLASS__, 'admin_permission_check' ),
                'args'                => array(
                    'id'   => array( 'required' => true ),
                    'date' => array( 'required' => true ),
                    'time' => array( 'required' => true ),
                ),
            )
        );

        // Update booking status
        register_rest_route(
            self::NAMESPACE,
            '/bookings/(?P<id>\d+)/status',
            array(
                'methods'             => 'POST',
                'callback'            => array( __CLASS__, 'update_booking_status' ),
                'permission_callback' => array( __CLASS__, 'admin_permission_check' ),
                'args'                => array(
                    'id'     => array( 'required' => true ),
                    'status' => array( 'required' => true ),
                ),
            )
        );
    }

    /**
     * Admin permission check.
     *
     * @return bool
     */
    public static function admin_permission_check() {
        return current_user_can( 'manage_woocommerce' );
    }

    /**
     * Get all services.
     *
     * @return WP_REST_Response
     */
    public static function get_services() {
        $services = Sodek_GB_Service::get_all_services();
        return rest_ensure_response( $services );
    }

    /**
     * Get single service.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public static function get_service( $request ) {
        $service = Sodek_GB_Service::get_service( $request['id'] );

        if ( ! $service ) {
            return new WP_Error( 'not_found', __( 'Service not found.', 'glowbook' ), array( 'status' => 404 ) );
        }

        // Include available add-ons
        $service['addons'] = Sodek_GB_Addon::get_addons_for_service( $request['id'] );

        return rest_ensure_response( $service );
    }

    /**
     * Get service categories.
     *
     * @return WP_REST_Response
     */
    public static function get_categories() {
        $categories = Sodek_GB_Service::get_categories();
        return rest_ensure_response( $categories );
    }

    /**
     * Get services grouped by category.
     *
     * @return WP_REST_Response
     */
    public static function get_services_grouped() {
        $grouped = Sodek_GB_Service::get_services_grouped_by_category();
        return rest_ensure_response( $grouped );
    }

    /**
     * Get services by category.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public static function get_services_by_category( $request ) {
        $category = $request['category'];
        $services = Sodek_GB_Service::get_services_by_category( $category );
        return rest_ensure_response( $services );
    }

    /**
     * Get add-ons for a service.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public static function get_service_addons( $request ) {
        $service_id = (int) $request['id'];
        $addons = Sodek_GB_Addon::get_addons_for_service( $service_id );
        return rest_ensure_response( array(
            'service_id' => $service_id,
            'addons'     => $addons,
        ) );
    }

    /**
     * Calculate booking total with add-ons.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public static function calculate_booking_total( $request ) {
        $params = $request->get_json_params();

        $service_id = isset( $params['service_id'] ) ? absint( $params['service_id'] ) : 0;
        $addon_ids  = isset( $params['addon_ids'] ) && is_array( $params['addon_ids'] ) ? array_map( 'absint', $params['addon_ids'] ) : array();

        if ( ! $service_id ) {
            return new WP_Error( 'missing_service', __( 'Service ID is required.', 'glowbook' ), array( 'status' => 400 ) );
        }

        $service = Sodek_GB_Service::get_service( $service_id );
        if ( ! $service ) {
            return new WP_Error( 'invalid_service', __( 'Invalid service.', 'glowbook' ), array( 'status' => 404 ) );
        }

        // Validate add-ons
        $addon_ids = Sodek_GB_Addon::validate_addons_for_service( $addon_ids, $service_id );

        // Calculate add-ons
        $addons_total   = Sodek_GB_Addon::calculate_addons_total( $addon_ids );
        $addons_deposit = Sodek_GB_Addon::calculate_addons_deposit( $addon_ids, $service_id );

        // Calculate totals
        $base_price     = $service['price'];
        $base_deposit   = $service['deposit_amount'];
        $base_duration  = $service['duration'];

        $total_price    = $base_price + $addons_total['price'];
        $total_deposit  = $base_deposit + $addons_deposit;
        $total_duration = $base_duration + $addons_total['duration'];
        $balance_due    = $total_price - $total_deposit;

        return rest_ensure_response( array(
            'service'         => array(
                'id'       => $service['id'],
                'title'    => $service['title'],
                'price'    => $base_price,
                'deposit'  => $base_deposit,
                'duration' => $base_duration,
            ),
            'addons'          => $addons_total['addons'],
            'addons_price'    => $addons_total['price'],
            'addons_deposit'  => $addons_deposit,
            'addons_duration' => $addons_total['duration'],
            'total_price'     => $total_price,
            'total_deposit'   => $total_deposit,
            'total_duration'  => $total_duration,
            'balance_due'     => $balance_due,
        ) );
    }

    /**
     * Get available dates for a month.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public static function get_available_dates( $request ) {
        $addon_ids = self::parse_addon_ids_from_request( $request );
        $dates = Sodek_GB_Availability::get_available_dates(
            (int) $request['year'],
            (int) $request['month'],
            (int) $request['service_id'],
            $addon_ids
        );

        return rest_ensure_response( array(
            'dates'      => $dates,
            'year'       => (int) $request['year'],
            'month'      => (int) $request['month'],
            'service_id' => (int) $request['service_id'],
        ) );
    }

    /**
     * Get available time slots for a date.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public static function get_available_slots( $request ) {
        $service_id = (int) $request['service_id'];
        $date = $request['date'];
        $addon_ids = self::parse_addon_ids_from_request( $request );

        $slots = Sodek_GB_Availability::get_available_slots( $date, $service_id, $addon_ids );
        $remaining_service = Sodek_GB_Availability::get_remaining_service_slots( $service_id, $date );
        $remaining_day     = Sodek_GB_Availability::get_remaining_daily_slots( $date );

        if ( null === $remaining_service ) {
            $remaining = $remaining_day;
        } elseif ( null === $remaining_day ) {
            $remaining = $remaining_service;
        } else {
            $remaining = min( $remaining_service, $remaining_day );
        }

        return rest_ensure_response( array(
            'slots'           => $slots,
            'date'            => $date,
            'service_id'      => $service_id,
            'remaining_slots' => $remaining, // null = unlimited, 0 = fully booked
            'daily_limit'     => Sodek_GB_Availability::get_daily_booking_limit( $date ),
            'daily_remaining' => $remaining_day,
        ) );
    }

    /**
     * Check if a specific slot is available.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public static function check_slot_availability( $request ) {
        $available = Sodek_GB_Availability::is_slot_available(
            $request['date'],
            $request['time'],
            (int) $request['service_id'],
            self::parse_addon_ids_from_request( $request )
        );

        return rest_ensure_response( array(
            'available'  => $available,
            'date'       => $request['date'],
            'time'       => $request['time'],
            'service_id' => (int) $request['service_id'],
        ) );
    }

    /**
     * Parse selected add-on IDs from a REST request.
     *
     * @param WP_REST_Request $request Request.
     * @return array
     */
    private static function parse_addon_ids_from_request( $request ) {
        $raw = $request->get_param( 'addon_ids' );

        if ( empty( $raw ) ) {
            return array();
        }

        if ( is_array( $raw ) ) {
            return array_filter( array_map( 'absint', $raw ) );
        }

        $decoded = json_decode( (string) $raw, true );
        if ( is_array( $decoded ) ) {
            return array_filter( array_map( 'absint', $decoded ) );
        }

        return array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) );
    }

    /**
     * Get bookings (admin).
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public static function get_bookings( $request ) {
        $args = array(
            'post_type'      => Sodek_GB_Booking::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => (int) $request['per_page'],
            'paged'          => (int) $request['page'],
            'orderby'        => 'meta_value',
            'meta_key'       => '_sodek_gb_booking_date',
            'order'          => 'ASC',
        );

        if ( ! empty( $request['status'] ) ) {
            $args['meta_query'][] = array(
                'key'   => '_sodek_gb_status',
                'value' => sanitize_text_field( $request['status'] ),
            );
        }

        if ( ! empty( $request['date'] ) ) {
            $args['meta_query'][] = array(
                'key'   => '_sodek_gb_booking_date',
                'value' => sanitize_text_field( $request['date'] ),
            );
        }

        $query = new WP_Query( $args );
        $bookings = array();

        foreach ( $query->posts as $post ) {
            $bookings[] = Sodek_GB_Booking::get_booking( $post->ID );
        }

        return rest_ensure_response( array(
            'bookings'   => $bookings,
            'total'      => $query->found_posts,
            'pages'      => $query->max_num_pages,
            'page'       => (int) $request['page'],
            'per_page'   => (int) $request['per_page'],
        ) );
    }

    /**
     * Get single booking (admin).
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public static function get_booking( $request ) {
        $booking = Sodek_GB_Booking::get_booking( $request['id'] );

        if ( ! $booking ) {
            return new WP_Error( 'not_found', __( 'Booking not found.', 'glowbook' ), array( 'status' => 404 ) );
        }

        return rest_ensure_response( $booking );
    }

    /**
     * Update booking (admin).
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public static function update_booking( $request ) {
        $booking_id = $request['id'];
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return new WP_Error( 'not_found', __( 'Booking not found.', 'glowbook' ), array( 'status' => 404 ) );
        }

        $params = $request->get_json_params();

        // Update status
        if ( isset( $params['status'] ) ) {
            Sodek_GB_Booking::update_status( $booking_id, sanitize_text_field( $params['status'] ) );
        }

        // Update staff
        if ( isset( $params['staff_id'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_staff_id', absint( $params['staff_id'] ) );
        }

        // Update notes
        if ( isset( $params['admin_notes'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_admin_notes', sanitize_textarea_field( $params['admin_notes'] ) );
        }

        // Update payment flags
        if ( isset( $params['deposit_paid'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_deposit_paid', $params['deposit_paid'] ? '1' : '0' );
        }

        if ( isset( $params['balance_paid'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_balance_paid', $params['balance_paid'] ? '1' : '0' );
        }

        return rest_ensure_response( Sodek_GB_Booking::get_booking( $booking_id ) );
    }

    /**
     * Get blocked dates.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public static function get_blocked_dates( $request ) {
        $blocked = Sodek_GB_Availability::get_blocked_dates(
            (int) $request['year'],
            (int) $request['month']
        );

        return rest_ensure_response( $blocked );
    }

    /**
     * Add blocked date (admin).
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public static function add_blocked_date( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params['date'] ) ) {
            return new WP_Error( 'missing_date', __( 'Date is required.', 'glowbook' ), array( 'status' => 400 ) );
        }

        $id = Sodek_GB_Availability::block_date(
            sanitize_text_field( $params['date'] ),
            isset( $params['reason'] ) ? sanitize_text_field( $params['reason'] ) : ''
        );

        if ( ! $id ) {
            return new WP_Error( 'failed', __( 'Failed to block date.', 'glowbook' ), array( 'status' => 500 ) );
        }

        return rest_ensure_response( array(
            'id'   => $id,
            'date' => $params['date'],
        ) );
    }

    /**
     * Remove blocked date (admin).
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public static function remove_blocked_date( $request ) {
        $result = Sodek_GB_Availability::delete_override( $request['id'] );

        if ( ! $result ) {
            return new WP_Error( 'failed', __( 'Failed to remove blocked date.', 'glowbook' ), array( 'status' => 500 ) );
        }

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    /**
     * Get bookings for calendar view.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public static function get_calendar_bookings( $request ) {
        // Parse date range (FullCalendar sends ISO format with time)
        $start_date = substr( $request['start'], 0, 10 );
        $end_date = substr( $request['end'], 0, 10 );

        error_log( 'GlowBook Calendar: Fetching bookings from ' . $start_date . ' to ' . $end_date );

        $args = array(
            'post_type'      => Sodek_GB_Booking::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_sodek_gb_booking_date',
                    'value'   => array( $start_date, $end_date ),
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ),
            ),
        );

        // Debug: Also get ALL bookings to see what exists
        $all_bookings = get_posts( array(
            'post_type'      => Sodek_GB_Booking::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 10,
        ) );
        foreach ( $all_bookings as $b ) {
            $date = get_post_meta( $b->ID, '_sodek_gb_booking_date', true );
            error_log( 'GlowBook Calendar Debug: Booking #' . $b->ID . ' has date: "' . $date . '"' );
        }

        // Filter by service
        if ( ! empty( $request['service_id'] ) ) {
            $args['meta_query'][] = array(
                'key'   => '_sodek_gb_service_id',
                'value' => absint( $request['service_id'] ),
            );
        }

        // Filter by status
        if ( ! empty( $request['status'] ) ) {
            $args['meta_query'][] = array(
                'key'   => '_sodek_gb_status',
                'value' => sanitize_text_field( $request['status'] ),
            );
        }

        $query = new WP_Query( $args );
        $bookings = array();

        foreach ( $query->posts as $post ) {
            $booking = Sodek_GB_Booking::get_booking( $post->ID );
            if ( $booking ) {
                // Calculate actual duration including add-ons
                $base_duration   = $booking['service']['duration'] ?? 0;
                $addons_duration = $booking['addons_duration'] ?? 0;
                $total_duration  = $base_duration + $addons_duration;

                $bookings[] = array(
                    'id'              => $booking['id'],
                    'date'            => $booking['booking_date'],
                    'start_time'      => $booking['start_time'],
                    'end_time'        => $booking['end_time'],
                    'status'          => $booking['status'],
                    'customer_name'   => $booking['customer_name'],
                    'customer_email'  => $booking['customer_email'],
                    'customer_phone'  => $booking['customer_phone'],
                    'service_id'      => $booking['service']['id'] ?? 0,
                    'service_name'    => $booking['service']['title'] ?? '',
                    'duration'        => $total_duration,
                    'deposit_amount'  => $booking['deposit_amount'],
                    'deposit_paid'    => $booking['deposit_paid'],
                    'notes'           => $booking['notes'] ?? '',
                    'addons'          => $booking['addons'] ?? array(),
                    'addons_duration' => $addons_duration,
                );
            }
        }

        return rest_ensure_response( $bookings );
    }

    /**
     * Reschedule a booking.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public static function reschedule_booking( $request ) {
        $booking_id = absint( $request['id'] );
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return new WP_Error( 'not_found', __( 'Booking not found.', 'glowbook' ), array( 'status' => 404 ) );
        }

        $params = $request->get_json_params();
        $new_date = sanitize_text_field( $params['date'] );
        $new_time = sanitize_text_field( $params['time'] );

        // Validate the new date/time format
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $new_date ) ) {
            return new WP_Error( 'invalid_date', __( 'Invalid date format.', 'glowbook' ), array( 'status' => 400 ) );
        }

        if ( ! preg_match( '/^\d{2}:\d{2}$/', $new_time ) ) {
            return new WP_Error( 'invalid_time', __( 'Invalid time format.', 'glowbook' ), array( 'status' => 400 ) );
        }

        // Check if the slot is available
        $service_id = $booking['service']['id'] ?? 0;
        if ( $service_id && ! Sodek_GB_Availability::is_slot_available( $new_date, $new_time, $service_id ) ) {
            return rest_ensure_response( array(
                'success' => false,
                'message' => __( 'The selected time slot is not available.', 'glowbook' ),
            ) );
        }

        // Get old date/time for the booked slots table
        $old_date = $booking['booking_date'];
        $old_start_time = $booking['start_time'];

        // Calculate new end time
        $duration = $booking['service']['duration'] ?? 60;
        $new_end_time = gmdate( 'H:i:s', strtotime( $new_time ) + ( $duration * 60 ) );

        // Update booking meta
        update_post_meta( $booking_id, '_sodek_gb_booking_date', $new_date );
        update_post_meta( $booking_id, '_sodek_gb_start_time', $new_time . ':00' );
        update_post_meta( $booking_id, '_sodek_gb_end_time', $new_end_time );

        // Update booked slots table
        global $wpdb;
        $table = $wpdb->prefix . 'sodek_gb_booked_slots';

        $wpdb->update(
            $table,
            array(
                'slot_date'   => $new_date,
                'start_time'  => $new_time . ':00',
                'end_time'    => $new_end_time,
            ),
            array(
                'booking_id' => $booking_id,
            ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        // Add note about reschedule
        $note = sprintf(
            /* translators: 1: old date, 2: old time, 3: new date, 4: new time */
            __( 'Rescheduled from %1$s at %2$s to %3$s at %4$s by admin.', 'glowbook' ),
            $old_date,
            $old_start_time,
            $new_date,
            $new_time
        );
        $existing_notes = get_post_meta( $booking_id, '_sodek_gb_admin_notes', true );
        $updated_notes = $existing_notes ? $existing_notes . "\n" . $note : $note;
        update_post_meta( $booking_id, '_sodek_gb_admin_notes', $updated_notes );

        return rest_ensure_response( array(
            'success' => true,
            'booking' => Sodek_GB_Booking::get_booking( $booking_id ),
        ) );
    }

    /**
     * Update booking status.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response|WP_Error
     */
    public static function update_booking_status( $request ) {
        $booking_id = absint( $request['id'] );
        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return new WP_Error( 'not_found', __( 'Booking not found.', 'glowbook' ), array( 'status' => 404 ) );
        }

        $params = $request->get_json_params();
        $new_status = sanitize_text_field( $params['status'] );

        // Validate status
        $valid_statuses = array( 'pending', 'confirmed', 'completed', 'cancelled', 'no-show' );
        if ( ! in_array( $new_status, $valid_statuses, true ) ) {
            return new WP_Error( 'invalid_status', __( 'Invalid status.', 'glowbook' ), array( 'status' => 400 ) );
        }

        // Update status
        Sodek_GB_Booking::update_status( $booking_id, $new_status );

        return rest_ensure_response( array(
            'success' => true,
            'status'  => $new_status,
        ) );
    }
}
