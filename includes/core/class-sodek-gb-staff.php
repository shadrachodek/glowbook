<?php
/**
 * Staff Management.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Staff class.
 */
class Sodek_GB_Staff {

    /**
     * Staff role name.
     */
    const ROLE_NAME = 'sodek_gb_staff';

    /**
     * Initialize.
     */
    public static function init() {
        // Role is registered during activation
        add_action( 'admin_init', array( __CLASS__, 'register_role' ) );

        // Staff meta fields
        add_action( 'show_user_profile', array( __CLASS__, 'add_staff_fields' ) );
        add_action( 'edit_user_profile', array( __CLASS__, 'add_staff_fields' ) );
        add_action( 'personal_options_update', array( __CLASS__, 'save_staff_fields' ) );
        add_action( 'edit_user_profile_update', array( __CLASS__, 'save_staff_fields' ) );

        // Add staff column to users list
        add_filter( 'manage_users_columns', array( __CLASS__, 'add_user_column' ) );
        add_filter( 'manage_users_custom_column', array( __CLASS__, 'render_user_column' ), 10, 3 );
    }

    /**
     * Register staff role.
     */
    public static function register_role() {
        $role = get_role( self::ROLE_NAME );

        if ( ! $role ) {
            add_role(
                self::ROLE_NAME,
                __( 'Booking Staff', 'glowbook' ),
                array(
                    'read'         => true,
                    'edit_posts'   => false,
                    'delete_posts' => false,
                )
            );
        }
    }

    /**
     * Get all staff members.
     *
     * @param array $args Optional. Additional WP_User_Query args.
     * @return array
     */
    public static function get_all_staff( $args = array() ) {
        $default_args = array(
            'role__in' => array( 'administrator', 'shop_manager', self::ROLE_NAME ),
            'orderby'  => 'display_name',
            'order'    => 'ASC',
        );

        // Only include users marked as staff
        $default_args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'     => '_sodek_gb_is_staff',
                'value'   => '1',
                'compare' => '=',
            ),
            array(
                'key'     => '_sodek_gb_is_staff',
                'compare' => 'NOT EXISTS',
            ),
        );

        $args = wp_parse_args( $args, $default_args );
        $users = get_users( $args );

        $staff = array();
        foreach ( $users as $user ) {
            // Include if explicitly marked as staff OR is shop_manager/admin without explicit exclusion
            $is_staff = get_user_meta( $user->ID, '_sodek_gb_is_staff', true );

            if ( '1' === $is_staff || ( '' === $is_staff && in_array( 'administrator', $user->roles, true ) ) ) {
                $staff[] = self::format_staff_data( $user );
            }
        }

        return $staff;
    }

    /**
     * Get active staff members (available for booking).
     *
     * @return array
     */
    public static function get_active_staff() {
        $all_staff = self::get_all_staff();

        return array_filter( $all_staff, function( $staff ) {
            return ! empty( $staff['is_active'] );
        } );
    }

    /**
     * Get single staff member.
     *
     * @param int $user_id User ID.
     * @return array|null
     */
    public static function get_staff( $user_id ) {
        $user = get_userdata( $user_id );

        if ( ! $user ) {
            return null;
        }

        return self::format_staff_data( $user );
    }

    /**
     * Format staff data.
     *
     * @param WP_User $user User object.
     * @return array
     */
    private static function format_staff_data( $user ) {
        return array(
            'id'           => $user->ID,
            'name'         => $user->display_name,
            'email'        => $user->user_email,
            'phone'        => get_user_meta( $user->ID, '_sodek_gb_staff_phone', true ),
            'bio'          => get_user_meta( $user->ID, '_sodek_gb_staff_bio', true ),
            'photo'        => get_user_meta( $user->ID, '_sodek_gb_staff_photo', true ),
            'is_staff'     => get_user_meta( $user->ID, '_sodek_gb_is_staff', true ) === '1',
            'is_active'    => get_user_meta( $user->ID, '_sodek_gb_staff_active', true ) !== '0',
            'services'     => get_user_meta( $user->ID, '_sodek_gb_staff_services', true ) ?: array(),
            'color'        => get_user_meta( $user->ID, '_sodek_gb_staff_color', true ) ?: '#3788d8',
            'avatar'       => get_avatar_url( $user->ID, array( 'size' => 96 ) ),
        );
    }

    /**
     * Add staff fields to user profile.
     *
     * @param WP_User $user User object.
     */
    public static function add_staff_fields( $user ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $is_staff = get_user_meta( $user->ID, '_sodek_gb_is_staff', true );
        $is_active = get_user_meta( $user->ID, '_sodek_gb_staff_active', true );
        $phone = get_user_meta( $user->ID, '_sodek_gb_staff_phone', true );
        $bio = get_user_meta( $user->ID, '_sodek_gb_staff_bio', true );
        $services = get_user_meta( $user->ID, '_sodek_gb_staff_services', true ) ?: array();
        $color = get_user_meta( $user->ID, '_sodek_gb_staff_color', true ) ?: '#3788d8';

        // Get all services
        $all_services = Sodek_GB_Service::get_all_services();
        ?>
        <h3><?php esc_html_e( 'Booking Staff Settings', 'glowbook' ); ?></h3>

        <table class="form-table">
            <tr>
                <th><label for="sodek_gb_is_staff"><?php esc_html_e( 'Is Staff Member', 'glowbook' ); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="sodek_gb_is_staff" id="sodek_gb_is_staff" value="1" <?php checked( $is_staff, '1' ); ?>>
                        <?php esc_html_e( 'This user can be assigned to bookings', 'glowbook' ); ?>
                    </label>
                </td>
            </tr>
            <tr class="sodek-gb-staff-field" <?php echo '1' !== $is_staff ? 'style="display:none;"' : ''; ?>>
                <th><label for="sodek_gb_staff_active"><?php esc_html_e( 'Status', 'glowbook' ); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="sodek_gb_staff_active" id="sodek_gb_staff_active" value="1" <?php checked( $is_active !== '0' ); ?>>
                        <?php esc_html_e( 'Active (available for new bookings)', 'glowbook' ); ?>
                    </label>
                </td>
            </tr>
            <tr class="sodek-gb-staff-field" <?php echo '1' !== $is_staff ? 'style="display:none;"' : ''; ?>>
                <th><label for="sodek_gb_staff_phone"><?php esc_html_e( 'Phone', 'glowbook' ); ?></label></th>
                <td>
                    <input type="tel" name="sodek_gb_staff_phone" id="sodek_gb_staff_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text">
                </td>
            </tr>
            <tr class="sodek-gb-staff-field" <?php echo '1' !== $is_staff ? 'style="display:none;"' : ''; ?>>
                <th><label for="sodek_gb_staff_bio"><?php esc_html_e( 'Bio', 'glowbook' ); ?></label></th>
                <td>
                    <textarea name="sodek_gb_staff_bio" id="sodek_gb_staff_bio" rows="4" class="regular-text"><?php echo esc_textarea( $bio ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Short biography shown to customers (if staff selection is enabled).', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr class="sodek-gb-staff-field" <?php echo '1' !== $is_staff ? 'style="display:none;"' : ''; ?>>
                <th><label for="sodek_gb_staff_color"><?php esc_html_e( 'Calendar Color', 'glowbook' ); ?></label></th>
                <td>
                    <input type="color" name="sodek_gb_staff_color" id="sodek_gb_staff_color" value="<?php echo esc_attr( $color ); ?>">
                    <p class="description"><?php esc_html_e( 'Color used to identify this staff member on the admin calendar.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr class="sodek-gb-staff-field" <?php echo '1' !== $is_staff ? 'style="display:none;"' : ''; ?>>
                <th><label><?php esc_html_e( 'Services', 'glowbook' ); ?></label></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text"><?php esc_html_e( 'Services this staff member can perform', 'glowbook' ); ?></legend>
                        <?php if ( ! empty( $all_services ) ) : ?>
                            <?php foreach ( $all_services as $service ) : ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="sodek_gb_staff_services[]" value="<?php echo esc_attr( $service['id'] ); ?>"
                                        <?php checked( in_array( $service['id'], $services, true ) || in_array( (string) $service['id'], $services, true ) ); ?>>
                                    <?php echo esc_html( $service['title'] ); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="description"><?php esc_html_e( 'No services available. Create services first.', 'glowbook' ); ?></p>
                        <?php endif; ?>
                    </fieldset>
                    <p class="description"><?php esc_html_e( 'Select which services this staff member can perform. Leave all unchecked for all services.', 'glowbook' ); ?></p>
                </td>
            </tr>
        </table>

        <script>
        jQuery(function($) {
            $('#sodek_gb_is_staff').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.sodek-gb-staff-field').show();
                } else {
                    $('.sodek-gb-staff-field').hide();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Save staff fields.
     *
     * @param int $user_id User ID.
     */
    public static function save_staff_fields( $user_id ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return;
        }

        update_user_meta( $user_id, '_sodek_gb_is_staff', isset( $_POST['sodek_gb_is_staff'] ) ? '1' : '0' );
        update_user_meta( $user_id, '_sodek_gb_staff_active', isset( $_POST['sodek_gb_staff_active'] ) ? '1' : '0' );
        update_user_meta( $user_id, '_sodek_gb_staff_phone', sanitize_text_field( $_POST['sodek_gb_staff_phone'] ?? '' ) );
        update_user_meta( $user_id, '_sodek_gb_staff_bio', sanitize_textarea_field( $_POST['sodek_gb_staff_bio'] ?? '' ) );
        update_user_meta( $user_id, '_sodek_gb_staff_color', sanitize_hex_color( $_POST['sodek_gb_staff_color'] ?? '#3788d8' ) );

        $services = isset( $_POST['sodek_gb_staff_services'] ) ? array_map( 'absint', $_POST['sodek_gb_staff_services'] ) : array();
        update_user_meta( $user_id, '_sodek_gb_staff_services', $services );
    }

    /**
     * Add staff column to users list.
     *
     * @param array $columns Columns.
     * @return array
     */
    public static function add_user_column( $columns ) {
        $columns['sodek_gb_staff'] = __( 'Booking Staff', 'glowbook' );
        return $columns;
    }

    /**
     * Render staff column.
     *
     * @param string $output      Column output.
     * @param string $column_name Column name.
     * @param int    $user_id     User ID.
     * @return string
     */
    public static function render_user_column( $output, $column_name, $user_id ) {
        if ( 'sodek_gb_staff' !== $column_name ) {
            return $output;
        }

        $is_staff = get_user_meta( $user_id, '_sodek_gb_is_staff', true );
        $is_active = get_user_meta( $user_id, '_sodek_gb_staff_active', true );

        if ( '1' === $is_staff ) {
            if ( '0' === $is_active ) {
                return '<span style="color: #999;">' . esc_html__( 'Inactive', 'glowbook' ) . '</span>';
            }
            return '<span style="color: green;">&#10003; ' . esc_html__( 'Active', 'glowbook' ) . '</span>';
        }

        return '&mdash;';
    }

    /**
     * Get staff for a specific service.
     *
     * @param int $service_id Service ID.
     * @return array
     */
    public static function get_staff_for_service( $service_id ) {
        $all_staff = self::get_active_staff();

        return array_filter( $all_staff, function( $staff ) use ( $service_id ) {
            // If no services specified, staff can do all services
            if ( empty( $staff['services'] ) ) {
                return true;
            }
            return in_array( $service_id, $staff['services'], true ) || in_array( (string) $service_id, $staff['services'], true );
        } );
    }

    /**
     * Get bookings count for a staff member.
     *
     * @param int    $user_id User ID.
     * @param string $period  Period: 'today', 'week', 'month', 'all'.
     * @return int
     */
    public static function get_bookings_count( $user_id, $period = 'all' ) {
        global $wpdb;

        $where = "pm1.meta_key = '_sodek_gb_staff_id' AND pm1.meta_value = %d";
        $params = array( $user_id );

        if ( 'today' === $period ) {
            $where .= " AND pm2.meta_key = '_sodek_gb_booking_date' AND pm2.meta_value = %s";
            $params[] = current_time( 'Y-m-d' );
        } elseif ( 'week' === $period ) {
            $week_start = date( 'Y-m-d', strtotime( 'monday this week' ) );
            $week_end = date( 'Y-m-d', strtotime( 'sunday this week' ) );
            $where .= " AND pm2.meta_key = '_sodek_gb_booking_date' AND pm2.meta_value BETWEEN %s AND %s";
            $params[] = $week_start;
            $params[] = $week_end;
        } elseif ( 'month' === $period ) {
            $month_start = date( 'Y-m-01' );
            $month_end = date( 'Y-m-t' );
            $where .= " AND pm2.meta_key = '_sodek_gb_booking_date' AND pm2.meta_value BETWEEN %s AND %s";
            $params[] = $month_start;
            $params[] = $month_end;
        }

        $sql = "SELECT COUNT(DISTINCT p.ID)
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
                INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
                WHERE p.post_type = 'sodek_gb_booking'
                AND p.post_status = 'publish'
                AND $where";

        return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
    }
}
