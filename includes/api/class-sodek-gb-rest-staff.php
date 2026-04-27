<?php
/**
 * Staff REST API Endpoints.
 *
 * @package GlowBook
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_REST_Staff class.
 */
class Sodek_GB_REST_Staff {

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
        // List all staff
        register_rest_route(
            $this->namespace,
            '/staff',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_staff_list' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'service_id' => array(
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'include_bio' => array(
                            'type'    => 'boolean',
                            'default' => true,
                        ),
                    ),
                ),
            )
        );

        // Get staff member details
        register_rest_route(
            $this->namespace,
            '/staff/(?P<id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_staff' ),
                    'permission_callback' => '__return_true',
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

        // Get staff availability for a date
        register_rest_route(
            $this->namespace,
            '/staff/(?P<id>\d+)/availability',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_staff_availability' ),
                    'permission_callback' => '__return_true',
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
                        'service_id' => array(
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
            )
        );

        // Get staff schedule (weekly)
        register_rest_route(
            $this->namespace,
            '/staff/(?P<id>\d+)/schedule',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_staff_schedule' ),
                    'permission_callback' => array( $this, 'check_admin_permission' ),
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
                    'callback'            => array( $this, 'update_staff_schedule' ),
                    'permission_callback' => array( $this, 'check_admin_permission' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'schedule' => array(
                            'required' => true,
                            'type'     => 'array',
                        ),
                    ),
                ),
            )
        );

        // Get staff time off
        register_rest_route(
            $this->namespace,
            '/staff/(?P<id>\d+)/time-off',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_time_off' ),
                    'permission_callback' => array( $this, 'check_admin_permission' ),
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
                    'callback'            => array( $this, 'add_time_off' ),
                    'permission_callback' => array( $this, 'check_admin_permission' ),
                    'args'                => array(
                        'id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'date_start' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'format'            => 'date',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'date_end' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'format'            => 'date',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'reason' => array(
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Delete time off
        register_rest_route(
            $this->namespace,
            '/staff/(?P<staff_id>\d+)/time-off/(?P<time_off_id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_time_off' ),
                    'permission_callback' => array( $this, 'check_admin_permission' ),
                    'args'                => array(
                        'staff_id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'time_off_id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
            )
        );

        // Get staff for a specific service
        register_rest_route(
            $this->namespace,
            '/staff/for-service/(?P<service_id>\d+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_staff_for_service' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'service_id' => array(
                            'required'          => true,
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                        'date' => array(
                            'type'              => 'string',
                            'format'            => 'date',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Get available slots combining all staff
        register_rest_route(
            $this->namespace,
            '/staff/available-slots',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_available_slots' ),
                    'permission_callback' => '__return_true',
                    'args'                => array(
                        'service_id' => array(
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
                        'staff_id' => array(
                            'type'              => 'integer',
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Get list of staff.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_staff_list( $request ) {
        $service_id = $request->get_param( 'service_id' );
        $include_bio = $request->get_param( 'include_bio' );

        $args = array(
            'post_type'      => 'sodek_gb_staff',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        );

        // Filter by service if provided
        if ( $service_id ) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_sodek_gb_services',
                    'value'   => sprintf( '"%d"', $service_id ),
                    'compare' => 'LIKE',
                ),
                array(
                    'key'     => '_sodek_gb_all_services',
                    'value'   => '1',
                    'compare' => '=',
                ),
            );
        }

        $posts = get_posts( $args );
        $staff = array();

        foreach ( $posts as $post ) {
            $staff_data = $this->format_staff( $post, $include_bio );
            if ( $staff_data ) {
                $staff[] = $staff_data;
            }
        }

        return rest_ensure_response( $staff );
    }

    /**
     * Get single staff member.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_staff( $request ) {
        $staff_id = $request->get_param( 'id' );
        $post = get_post( $staff_id );

        if ( ! $post || $post->post_type !== 'sodek_gb_staff' ) {
            return new WP_Error(
                'staff_not_found',
                __( 'Staff member not found.', 'glowbook' ),
                array( 'status' => 404 )
            );
        }

        $staff = $this->format_staff( $post, true );

        return rest_ensure_response( $staff );
    }

    /**
     * Get staff availability for a specific date.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_staff_availability( $request ) {
        $staff_id = $request->get_param( 'id' );
        $date = $request->get_param( 'date' );
        $service_id = $request->get_param( 'service_id' );

        // Validate date against the configured business timezone.
        if ( $date < Sodek_GB_Availability::current_date( 'Y-m-d' ) ) {
            return new WP_Error(
                'invalid_date',
                __( 'Cannot get availability for past dates.', 'glowbook' ),
                array( 'status' => 400 )
            );
        }

        $slots = Sodek_GB_Staff_Availability::get_available_slots( $staff_id, $date, $service_id );

        if ( is_wp_error( $slots ) ) {
            return $slots;
        }

        return rest_ensure_response(
            array(
                'date'  => $date,
                'slots' => $slots,
            )
        );
    }

    /**
     * Get staff weekly schedule.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_staff_schedule( $request ) {
        $staff_id = $request->get_param( 'id' );
        $schedule = Sodek_GB_Staff_Availability::get_schedule( $staff_id );

        return rest_ensure_response( $schedule );
    }

    /**
     * Update staff weekly schedule.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function update_staff_schedule( $request ) {
        $staff_id = $request->get_param( 'id' );
        $schedule = $request->get_param( 'schedule' );

        $result = Sodek_GB_Staff_Availability::update_schedule( $staff_id, $schedule );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __( 'Schedule updated.', 'glowbook' ),
            )
        );
    }

    /**
     * Get staff time off.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_time_off( $request ) {
        $staff_id = $request->get_param( 'id' );
        $time_off = Sodek_GB_Staff_Availability::get_time_off( $staff_id );

        return rest_ensure_response( $time_off );
    }

    /**
     * Add staff time off.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function add_time_off( $request ) {
        $staff_id = $request->get_param( 'id' );
        $date_start = $request->get_param( 'date_start' );
        $date_end = $request->get_param( 'date_end' );
        $reason = $request->get_param( 'reason' );

        $result = Sodek_GB_Staff_Availability::add_time_off( $staff_id, $date_start, $date_end, $reason );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'id'      => $result,
                'message' => __( 'Time off added.', 'glowbook' ),
            )
        );
    }

    /**
     * Delete staff time off.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function delete_time_off( $request ) {
        $staff_id = $request->get_param( 'staff_id' );
        $time_off_id = $request->get_param( 'time_off_id' );

        $result = Sodek_GB_Staff_Availability::delete_time_off( $time_off_id, $staff_id );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'message' => __( 'Time off removed.', 'glowbook' ),
            )
        );
    }

    /**
     * Get staff for a specific service.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_staff_for_service( $request ) {
        $service_id = $request->get_param( 'service_id' );
        $date = $request->get_param( 'date' );

        // Check if staff selection is enabled for this service
        $allow_selection = get_post_meta( $service_id, '_sodek_gb_allow_staff_selection', true );

        if ( $allow_selection === 'no' ) {
            return rest_ensure_response(
                array(
                    'show_selection' => false,
                    'staff'          => array(),
                )
            );
        }

        // Get staff that can perform this service
        $request->set_param( 'service_id', $service_id );
        $staff = $this->get_staff_list( $request )->get_data();

        // If a date is provided, filter to only available staff
        if ( $date && ! empty( $staff ) ) {
            $available_staff = array();

            foreach ( $staff as $member ) {
                $slots = Sodek_GB_Staff_Availability::get_available_slots( $member['id'], $date, $service_id );
                if ( ! empty( $slots ) ) {
                    $member['available_slots_count'] = count( $slots );
                    $available_staff[] = $member;
                }
            }

            $staff = $available_staff;
        }

        // Check if "Any Available" option should be shown
        $show_any = get_option( 'sodek_gb_show_any_available', true );

        return rest_ensure_response(
            array(
                'show_selection'    => true,
                'show_any_option'   => $show_any,
                'staff'             => $staff,
            )
        );
    }

    /**
     * Get available slots across all staff or specific staff.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error
     */
    public function get_available_slots( $request ) {
        $service_id = $request->get_param( 'service_id' );
        $date = $request->get_param( 'date' );
        $staff_id = $request->get_param( 'staff_id' );

        // Validate date
        if ( strtotime( $date ) < strtotime( 'today' ) ) {
            return new WP_Error(
                'invalid_date',
                __( 'Cannot get availability for past dates.', 'glowbook' ),
                array( 'status' => 400 )
            );
        }

        $addon_ids = self::parse_addon_ids_from_request( $request );

        if ( $staff_id ) {
            // Get slots for specific staff
            $slots = Sodek_GB_Staff_Availability::get_available_slots( $staff_id, $date, $service_id, $addon_ids );
        } else {
            // Get combined slots from all staff
            $slots = Sodek_GB_Staff_Availability::get_combined_available_slots( $service_id, $date, $addon_ids );
        }

        if ( is_wp_error( $slots ) ) {
            return $slots;
        }

        // Format slots for frontend
        $formatted_slots = array();
        foreach ( $slots as $slot ) {
            $time = is_array( $slot ) ? $slot['time'] : $slot;
            $formatted_slots[] = array(
                'time'       => $time,
                'label'      => date_i18n( get_option( 'time_format' ), strtotime( $time ) ),
                'staff_id'   => is_array( $slot ) ? ( $slot['staff_id'] ?? null ) : null,
                'staff_name' => is_array( $slot ) ? ( $slot['staff_name'] ?? null ) : null,
            );
        }

        return rest_ensure_response(
            array(
                'date'     => $date,
                'slots'    => $formatted_slots,
                'staff_id' => $staff_id,
            )
        );
    }

    /**
     * Format staff data for response.
     *
     * @param WP_Post $post        Staff post object.
     * @param bool    $include_bio Whether to include bio.
     * @return array|null
     */
    private function format_staff( $post, $include_bio = true ) {
        $staff_id = $post->ID;

        // Check if staff is active
        $is_active = get_post_meta( $staff_id, '_sodek_gb_active', true );
        if ( $is_active === 'no' ) {
            return null;
        }

        $data = array(
            'id'         => $staff_id,
            'name'       => $post->post_title,
            'photo'      => get_the_post_thumbnail_url( $staff_id, 'medium' ),
            'specialties' => get_post_meta( $staff_id, '_sodek_gb_specialties', true ),
        );

        if ( $include_bio ) {
            $data['bio'] = $post->post_content;
        }

        // Get staff rating if reviews are enabled
        if ( get_option( 'sodek_gb_show_staff_ratings', false ) ) {
            $data['rating'] = (float) get_post_meta( $staff_id, '_sodek_gb_average_rating', true ) ?: null;
            $data['review_count'] = (int) get_post_meta( $staff_id, '_sodek_gb_review_count', true );
        }

        return $data;
    }

    /**
     * Parse add-on IDs from a REST request.
     *
     * @param WP_REST_Request $request Request object.
     * @return array
     */
    private static function parse_addon_ids_from_request( $request ) {
        $raw_addon_ids = $request->get_param( 'addon_ids' );

        if ( empty( $raw_addon_ids ) ) {
            return array();
        }

        if ( is_string( $raw_addon_ids ) ) {
            $decoded = json_decode( wp_unslash( $raw_addon_ids ), true );

            if ( is_array( $decoded ) ) {
                $raw_addon_ids = $decoded;
            } else {
                $raw_addon_ids = array_filter(
                    array_map( 'trim', explode( ',', $raw_addon_ids ) ),
                    'strlen'
                );
            }
        }

        if ( ! is_array( $raw_addon_ids ) ) {
            return array();
        }

        return array_values(
            array_filter(
                array_map( 'absint', $raw_addon_ids )
            )
        );
    }

    /**
     * Check admin permission.
     *
     * @return bool
     */
    public function check_admin_permission() {
        return current_user_can( 'manage_options' );
    }
}
