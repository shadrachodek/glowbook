<?php
/**
 * Booking Custom Post Type.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Booking class.
 */
class Sodek_GB_Booking {

    /**
     * Post type name.
     */
    const POST_TYPE = 'sodek_gb_booking';

    /**
     * Singular capability type.
     */
    const CAPABILITY_TYPE = 'sodek_gb_booking';

    /**
     * Plural capability type.
     */
    const CAPABILITY_TYPE_PLURAL = 'sodek_gb_bookings';

    /**
     * Booking statuses.
     */
    const STATUS_PENDING   = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW   = 'no-show';

    /**
     * Register the post type.
     */
    public static function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Bookings', 'Post type general name', 'glowbook' ),
            'singular_name'         => _x( 'Booking', 'Post type singular name', 'glowbook' ),
            'menu_name'             => _x( 'Bookings', 'Admin Menu text', 'glowbook' ),
            'add_new'               => __( 'Add New', 'glowbook' ),
            'add_new_item'          => __( 'Add New Booking', 'glowbook' ),
            'edit_item'             => __( 'Edit Booking', 'glowbook' ),
            'new_item'              => __( 'New Booking', 'glowbook' ),
            'view_item'             => __( 'View Booking', 'glowbook' ),
            'search_items'          => __( 'Search Bookings', 'glowbook' ),
            'not_found'             => __( 'No bookings found', 'glowbook' ),
            'not_found_in_trash'    => __( 'No bookings found in Trash', 'glowbook' ),
            'all_items'             => __( 'All Bookings', 'glowbook' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false, // Will be added to our custom menu
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => array( self::CAPABILITY_TYPE, self::CAPABILITY_TYPE_PLURAL ),
            'map_meta_cap'        => true,
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array( 'title' ),
            'show_in_rest'        => true,
        );

        register_post_type( self::POST_TYPE, $args );

        // Register meta boxes
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 2 );

        // Custom columns
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'add_columns' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_columns' ), 10, 2 );
        add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
    }

    /**
     * Get all booking statuses.
     *
     * @return array
     */
    public static function get_statuses() {
        return array(
            self::STATUS_PENDING   => __( 'Pending', 'glowbook' ),
            self::STATUS_CONFIRMED => __( 'Confirmed', 'glowbook' ),
            self::STATUS_COMPLETED => __( 'Completed', 'glowbook' ),
            self::STATUS_CANCELLED => __( 'Cancelled', 'glowbook' ),
            self::STATUS_NO_SHOW   => __( 'No Show', 'glowbook' ),
        );
    }

    /**
     * Add meta boxes.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'sodek_gb_booking_details',
            __( 'Booking Details', 'glowbook' ),
            array( __CLASS__, 'render_details_meta_box' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'sodek_gb_booking_customer',
            __( 'Customer Information', 'glowbook' ),
            array( __CLASS__, 'render_customer_meta_box' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'sodek_gb_booking_status',
            __( 'Booking Status', 'glowbook' ),
            array( __CLASS__, 'render_status_meta_box' ),
            self::POST_TYPE,
            'side',
            'high'
        );

        add_meta_box(
            'sodek_gb_customer_notes',
            __( 'Customer Notes / Special Requests', 'glowbook' ),
            array( __CLASS__, 'render_customer_notes_meta_box' ),
            self::POST_TYPE,
            'normal',
            'default'
        );

        add_meta_box(
            'sodek_gb_booking_notes',
            __( 'Admin Notes', 'glowbook' ),
            array( __CLASS__, 'render_notes_meta_box' ),
            self::POST_TYPE,
            'normal',
            'default'
        );

        // Show cancellation/refund meta box for cancelled bookings
        $status = get_post_meta( get_the_ID(), '_sodek_gb_status', true );
        if ( 'cancelled' === $status ) {
            add_meta_box(
                'sodek_gb_cancellation_details',
                __( 'Cancellation & Refund', 'glowbook' ),
                array( __CLASS__, 'render_cancellation_meta_box' ),
                self::POST_TYPE,
                'side',
                'default'
            );
        }

        // Payment meta box
        add_meta_box(
            'sodek_gb_payment_details',
            __( 'Payment Details', 'glowbook' ),
            array( __CLASS__, 'render_payment_meta_box' ),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * Render booking details meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_details_meta_box( $post ) {
        wp_nonce_field( 'sodek_gb_booking_meta', 'sodek_gb_booking_nonce' );

        $service_id    = get_post_meta( $post->ID, '_sodek_gb_service_id', true );
        $booking_date  = get_post_meta( $post->ID, '_sodek_gb_booking_date', true );
        $start_time    = get_post_meta( $post->ID, '_sodek_gb_start_time', true );
        $end_time      = get_post_meta( $post->ID, '_sodek_gb_end_time', true );
        $staff_id      = get_post_meta( $post->ID, '_sodek_gb_staff_id', true );
        $order_id      = get_post_meta( $post->ID, '_sodek_gb_order_id', true );

        // Get services
        $services = Sodek_GB_Service::get_all_services();

        // Get staff (users with specific roles)
        $staff_users = get_users( array(
            'role__in' => array( 'administrator', 'shop_manager', 'sodek_gb_staff' ),
        ) );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="sodek_gb_service_id"><?php esc_html_e( 'Service', 'glowbook' ); ?></label></th>
                <td>
                    <select id="sodek_gb_service_id" name="sodek_gb_service_id" required>
                        <option value=""><?php esc_html_e( 'Select a service', 'glowbook' ); ?></option>
                        <?php foreach ( $services as $service ) : ?>
                            <option value="<?php echo esc_attr( $service['id'] ); ?>" <?php selected( $service_id, $service['id'] ); ?>>
                                <?php echo esc_html( $service['title'] ); ?> (<?php echo esc_html( $service['duration'] ); ?> min)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_booking_date"><?php esc_html_e( 'Date', 'glowbook' ); ?></label></th>
                <td>
                    <input type="date" id="sodek_gb_booking_date" name="sodek_gb_booking_date" value="<?php echo esc_attr( $booking_date ); ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_start_time"><?php esc_html_e( 'Start Time', 'glowbook' ); ?></label></th>
                <td>
                    <input type="time" id="sodek_gb_start_time" name="sodek_gb_start_time" value="<?php echo esc_attr( $start_time ); ?>" required>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_end_time"><?php esc_html_e( 'End Time', 'glowbook' ); ?></label></th>
                <td>
                    <input type="time" id="sodek_gb_end_time" name="sodek_gb_end_time" value="<?php echo esc_attr( $end_time ); ?>">
                    <p class="description"><?php esc_html_e( 'Auto-calculated from service duration if left empty.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_staff_id"><?php esc_html_e( 'Assigned Staff', 'glowbook' ); ?></label></th>
                <td>
                    <select id="sodek_gb_staff_id" name="sodek_gb_staff_id">
                        <option value=""><?php esc_html_e( 'Unassigned', 'glowbook' ); ?></option>
                        <?php foreach ( $staff_users as $user ) : ?>
                            <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $staff_id, $user->ID ); ?>>
                                <?php echo esc_html( $user->display_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php if ( $order_id ) : ?>
            <tr>
                <th><?php esc_html_e( 'WooCommerce Order', 'glowbook' ); ?></th>
                <td>
                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ); ?>" target="_blank">
                        #<?php echo esc_html( $order_id ); ?>
                    </a>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    /**
     * Render customer meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_customer_meta_box( $post ) {
        $customer_name  = get_post_meta( $post->ID, '_sodek_gb_customer_name', true );
        $customer_email = get_post_meta( $post->ID, '_sodek_gb_customer_email', true );
        $customer_phone = get_post_meta( $post->ID, '_sodek_gb_customer_phone', true );
        $customer_id    = get_post_meta( $post->ID, '_sodek_gb_customer_id', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="sodek_gb_customer_name"><?php esc_html_e( 'Name', 'glowbook' ); ?></label></th>
                <td>
                    <input type="text" id="sodek_gb_customer_name" name="sodek_gb_customer_name" value="<?php echo esc_attr( $customer_name ); ?>" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_customer_email"><?php esc_html_e( 'Email', 'glowbook' ); ?></label></th>
                <td>
                    <input type="email" id="sodek_gb_customer_email" name="sodek_gb_customer_email" value="<?php echo esc_attr( $customer_email ); ?>" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_customer_phone"><?php esc_html_e( 'Phone', 'glowbook' ); ?></label></th>
                <td>
                    <input type="tel" id="sodek_gb_customer_phone" name="sodek_gb_customer_phone" value="<?php echo esc_attr( $customer_phone ); ?>" class="regular-text">
                </td>
            </tr>
            <?php if ( $customer_id ) : ?>
            <tr>
                <th><?php esc_html_e( 'User Account', 'glowbook' ); ?></th>
                <td>
                    <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $customer_id ) ); ?>" target="_blank">
                        <?php echo esc_html( get_userdata( $customer_id )->display_name ); ?>
                    </a>
                </td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    /**
     * Render status meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_status_meta_box( $post ) {
        $status = get_post_meta( $post->ID, '_sodek_gb_status', true ) ?: self::STATUS_PENDING;
        $statuses = self::get_statuses();
        $deposit_paid = get_post_meta( $post->ID, '_sodek_gb_deposit_paid', true );
        $balance_paid = get_post_meta( $post->ID, '_sodek_gb_balance_paid', true );
        ?>
        <p>
            <label for="sodek_gb_status"><strong><?php esc_html_e( 'Status', 'glowbook' ); ?></strong></label>
            <select id="sodek_gb_status" name="sodek_gb_status" style="width: 100%; margin-top: 5px;">
                <?php foreach ( $statuses as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <hr>
        <p>
            <label>
                <input type="checkbox" name="sodek_gb_deposit_paid" value="1" <?php checked( $deposit_paid, '1' ); ?>>
                <?php esc_html_e( 'Deposit Paid', 'glowbook' ); ?>
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="sodek_gb_balance_paid" value="1" <?php checked( $balance_paid, '1' ); ?>>
                <?php esc_html_e( 'Balance Paid', 'glowbook' ); ?>
            </label>
        </p>
        <?php
    }

    /**
     * Render notes meta box.
     *
     * @param WP_Post $post Post object.
     */
    /**
     * Render customer notes meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_customer_notes_meta_box( $post ) {
        $notes = get_post_meta( $post->ID, '_sodek_gb_customer_notes', true );

        if ( $notes ) {
            ?>
            <div style="background: #fff8e5; border-left: 4px solid #ffb900; padding: 15px; margin-bottom: 10px;">
                <?php echo wp_kses_post( wpautop( esc_html( $notes ) ) ); ?>
            </div>
            <?php
        } else {
            echo '<p class="description">' . esc_html__( 'No special requests or notes from customer.', 'glowbook' ) . '</p>';
        }
    }

    /**
     * Render admin notes meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_notes_meta_box( $post ) {
        $notes = get_post_meta( $post->ID, '_sodek_gb_admin_notes', true );
        ?>
        <textarea id="sodek_gb_admin_notes" name="sodek_gb_admin_notes" rows="5" style="width: 100%;"><?php echo esc_textarea( $notes ); ?></textarea>
        <p class="description"><?php esc_html_e( 'Private notes visible only to staff.', 'glowbook' ); ?></p>
        <?php
    }

    /**
     * Render cancellation details meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_cancellation_meta_box( $post ) {
        $cancellation_type = get_post_meta( $post->ID, '_sodek_gb_cancellation_type', true );
        $cancelled_at = get_post_meta( $post->ID, '_sodek_gb_cancelled_at', true );
        $cancelled_by = get_post_meta( $post->ID, '_sodek_gb_cancelled_by', true );
        $refund_type = get_post_meta( $post->ID, '_sodek_gb_refund_type', true );
        $refund_amount = get_post_meta( $post->ID, '_sodek_gb_refund_amount', true );
        $credit_amount = get_post_meta( $post->ID, '_sodek_gb_credit_amount', true );
        $refund_requested = get_post_meta( $post->ID, '_sodek_gb_refund_requested', true );
        $refund_processed = get_post_meta( $post->ID, '_sodek_gb_refund_processed', true );
        $credit_issued = get_post_meta( $post->ID, '_sodek_gb_credit_issued', true );
        ?>
        <div class="sodek-gb-cancellation-details">
            <?php if ( $cancelled_at ) : ?>
                <p>
                    <strong><?php esc_html_e( 'Cancelled:', 'glowbook' ); ?></strong><br>
                    <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $cancelled_at ) ) ); ?>
                </p>
            <?php endif; ?>

            <?php if ( $cancelled_by ) : ?>
                <p>
                    <strong><?php esc_html_e( 'Cancelled By:', 'glowbook' ); ?></strong>
                    <?php echo esc_html( 'customer' === $cancelled_by ? __( 'Customer', 'glowbook' ) : __( 'Admin', 'glowbook' ) ); ?>
                </p>
            <?php endif; ?>

            <?php if ( $cancellation_type ) : ?>
                <p>
                    <strong><?php esc_html_e( 'Cancellation Type:', 'glowbook' ); ?></strong>
                    <?php echo esc_html( 'late' === $cancellation_type ? __( 'Late Cancellation', 'glowbook' ) : __( 'Standard', 'glowbook' ) ); ?>
                </p>
            <?php endif; ?>

            <hr>

            <?php if ( 'refund' === $refund_type && $refund_amount > 0 ) : ?>
                <p>
                    <strong><?php esc_html_e( 'Refund Amount:', 'glowbook' ); ?></strong>
                    <?php echo wc_price( $refund_amount ); ?>
                </p>

                <?php if ( 'yes' === $refund_requested && 'yes' !== $refund_processed ) : ?>
                    <p class="sodek-gb-refund-pending" style="background: #fcf0e3; padding: 10px; border-radius: 4px;">
                        <span class="dashicons dashicons-warning" style="color: #9a6700;"></span>
                        <?php esc_html_e( 'Refund pending - requires manual processing', 'glowbook' ); ?>
                    </p>
                    <p>
                        <label>
                            <input type="checkbox" name="sodek_gb_mark_refund_processed" value="1">
                            <?php esc_html_e( 'Mark refund as processed', 'glowbook' ); ?>
                        </label>
                    </p>
                <?php elseif ( 'yes' === $refund_processed ) : ?>
                    <p class="sodek-gb-refund-completed" style="background: #d4edda; padding: 10px; border-radius: 4px;">
                        <span class="dashicons dashicons-yes" style="color: #155724;"></span>
                        <?php esc_html_e( 'Refund processed', 'glowbook' ); ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ( 'credit' === $refund_type && $credit_amount > 0 ) : ?>
                <p>
                    <strong><?php esc_html_e( 'Store Credit:', 'glowbook' ); ?></strong>
                    <?php echo wc_price( $credit_amount ); ?>
                </p>
                <?php if ( 'yes' === $credit_issued ) : ?>
                    <p class="sodek-gb-credit-issued" style="background: #d4edda; padding: 10px; border-radius: 4px;">
                        <span class="dashicons dashicons-yes" style="color: #155724;"></span>
                        <?php esc_html_e( 'Credit issued to customer account', 'glowbook' ); ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ( 'none' === $refund_type || ( empty( $refund_amount ) && empty( $credit_amount ) ) ) : ?>
                <p>
                    <em><?php esc_html_e( 'No refund or credit per policy.', 'glowbook' ); ?></em>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render payment details meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_payment_meta_box( $post ) {
        $order_id = get_post_meta( $post->ID, '_sodek_gb_order_id', true );
        $deposit_amount = get_post_meta( $post->ID, '_sodek_gb_deposit_amount', true );
        $deposit_paid = get_post_meta( $post->ID, '_sodek_gb_deposit_paid', true );
        $balance_amount = (float) get_post_meta( $post->ID, '_sodek_gb_balance_amount', true );
        $balance_paid = get_post_meta( $post->ID, '_sodek_gb_balance_paid', true );
        $balance_paid_at = get_post_meta( $post->ID, '_sodek_gb_balance_paid_at', true );
        $balance_payment_method = get_post_meta( $post->ID, '_sodek_gb_balance_payment_method', true );
        $total_price = (float) get_post_meta( $post->ID, '_sodek_gb_total_price', true );
        $expected_balance = max( 0, $total_price - (float) $deposit_amount );
        $payment_method_labels = array(
            '' => __( 'Select payment method', 'glowbook' ),
            'cash_in_person' => __( 'Cash at appointment', 'glowbook' ),
            'card_reader_in_person' => __( 'Card reader at appointment', 'glowbook' ),
            'bank_transfer' => __( 'Bank transfer', 'glowbook' ),
            'online_card' => __( 'Online card payment', 'glowbook' ),
            'saved_card' => __( 'Saved card on file', 'glowbook' ),
            'manual_other' => __( 'Other / manual entry', 'glowbook' ),
        );
        ?>
        <div class="sodek-gb-payment-details">
            <?php if ( $order_id ) : ?>
                <p>
                    <strong><?php esc_html_e( 'WooCommerce Order:', 'glowbook' ); ?></strong><br>
                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) ); ?>">
                        #<?php echo esc_html( $order_id ); ?>
                    </a>
                </p>
            <?php endif; ?>

            <p>
                <strong><?php esc_html_e( 'Deposit:', 'glowbook' ); ?></strong>
                <?php echo wc_price( $deposit_amount ); ?>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="sodek_gb_deposit_paid" value="1" <?php checked( $deposit_paid, '1' ); ?>>
                    <?php esc_html_e( 'Deposit Paid', 'glowbook' ); ?>
                </label>
            </p>

            <?php if ( $expected_balance > 0 || '1' === $balance_paid ) : ?>
                <hr>
                <p>
                    <strong><?php echo '1' === $balance_paid ? esc_html__( 'Balance Received:', 'glowbook' ) : esc_html__( 'Balance Due:', 'glowbook' ); ?></strong>
                    <?php echo wc_price( '1' === $balance_paid ? $expected_balance : $balance_amount ); ?>
                </p>
                <p>
                    <label>
                        <input type="checkbox" name="sodek_gb_balance_paid" value="1" <?php checked( $balance_paid, '1' ); ?>>
                        <?php esc_html_e( 'Balance Paid', 'glowbook' ); ?>
                    </label>
                </p>
                <p>
                    <label for="sodek_gb_balance_payment_method"><strong><?php esc_html_e( 'Balance Payment Method', 'glowbook' ); ?></strong></label><br>
                    <select id="sodek_gb_balance_payment_method" name="sodek_gb_balance_payment_method" style="width: 100%; margin-top: 5px;">
                        <?php foreach ( $payment_method_labels as $method_key => $method_label ) : ?>
                            <option value="<?php echo esc_attr( $method_key ); ?>" <?php selected( $balance_payment_method, $method_key ); ?>>
                                <?php echo esc_html( $method_label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <?php if ( ! empty( $balance_paid_at ) ) : ?>
                    <p>
                        <em>
                            <?php
                            printf(
                                /* translators: %s: payment received datetime */
                                esc_html__( 'Marked received on %s.', 'glowbook' ),
                                esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $balance_paid_at ) ) )
                            );
                            ?>
                        </em>
                    </p>
                <?php else : ?>
                    <p><em><?php esc_html_e( 'Use this when the remaining balance is paid in salon or manually reconciled by staff.', 'glowbook' ); ?></em></p>
                <?php endif; ?>
            <?php endif; ?>

            <hr>
            <p>
                <strong><?php esc_html_e( 'Total:', 'glowbook' ); ?></strong>
                <?php echo wc_price( $total_price ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Save meta data.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public static function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['sodek_gb_booking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sodek_gb_booking_nonce'] ) ), 'sodek_gb_booking_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Text/select fields
        $text_fields = array(
            'sodek_gb_service_id'     => '_sodek_gb_service_id',
            'sodek_gb_booking_date'   => '_sodek_gb_booking_date',
            'sodek_gb_start_time'     => '_sodek_gb_start_time',
            'sodek_gb_end_time'       => '_sodek_gb_end_time',
            'sodek_gb_staff_id'       => '_sodek_gb_staff_id',
            'sodek_gb_customer_name'  => '_sodek_gb_customer_name',
            'sodek_gb_customer_email' => '_sodek_gb_customer_email',
            'sodek_gb_customer_phone' => '_sodek_gb_customer_phone',
            'sodek_gb_status'         => '_sodek_gb_status',
            'sodek_gb_admin_notes'    => '_sodek_gb_admin_notes',
        );

        foreach ( $text_fields as $field => $meta_key ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        $deposit_amount = (float) get_post_meta( $post_id, '_sodek_gb_deposit_amount', true );
        $total_price    = (float) get_post_meta( $post_id, '_sodek_gb_total_price', true );
        $expected_balance = max( 0, $total_price - $deposit_amount );
        $balance_was_paid = '1' === get_post_meta( $post_id, '_sodek_gb_balance_paid', true );
        $balance_is_paid  = isset( $_POST['sodek_gb_balance_paid'] );

        // Checkbox fields
        update_post_meta( $post_id, '_sodek_gb_deposit_paid', isset( $_POST['sodek_gb_deposit_paid'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_sodek_gb_balance_paid', $balance_is_paid ? '1' : '0' );

        if ( isset( $_POST['sodek_gb_balance_payment_method'] ) ) {
            update_post_meta( $post_id, '_sodek_gb_balance_payment_method', sanitize_text_field( wp_unslash( $_POST['sodek_gb_balance_payment_method'] ) ) );
        }

        if ( $balance_is_paid ) {
            update_post_meta( $post_id, '_sodek_gb_balance_amount', 0 );
            if ( ! $balance_was_paid ) {
                update_post_meta( $post_id, '_sodek_gb_balance_paid_at', current_time( 'mysql' ) );
            }

            $payment_method = isset( $_POST['sodek_gb_balance_payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['sodek_gb_balance_payment_method'] ) ) : '';
            if ( empty( $payment_method ) ) {
                update_post_meta( $post_id, '_sodek_gb_balance_payment_method', 'manual_other' );
            }
        } else {
            if ( $balance_was_paid && $expected_balance > 0 ) {
                update_post_meta( $post_id, '_sodek_gb_balance_amount', $expected_balance );
            }
            delete_post_meta( $post_id, '_sodek_gb_balance_paid_at' );
            delete_post_meta( $post_id, '_sodek_gb_balance_payment_method' );
            delete_post_meta( $post_id, '_sodek_gb_balance_payment_id' );
        }

        // Mark refund as processed
        if ( isset( $_POST['sodek_gb_mark_refund_processed'] ) && '1' === $_POST['sodek_gb_mark_refund_processed'] ) {
            update_post_meta( $post_id, '_sodek_gb_refund_processed', 'yes' );
            update_post_meta( $post_id, '_sodek_gb_refund_processed_at', current_time( 'mysql' ) );
            update_post_meta( $post_id, '_sodek_gb_refund_processed_by', get_current_user_id() );
        }

        // Calculate end time if not provided
        if ( empty( $_POST['sodek_gb_end_time'] ) && ! empty( $_POST['sodek_gb_service_id'] ) && ! empty( $_POST['sodek_gb_start_time'] ) ) {
            $service = Sodek_GB_Service::get_service( absint( $_POST['sodek_gb_service_id'] ) );
            if ( $service ) {
                $start = strtotime( $_POST['sodek_gb_start_time'] );
                $end = $start + ( $service['duration'] * 60 );
                update_post_meta( $post_id, '_sodek_gb_end_time', gmdate( 'H:i', $end ) );
            }
        }

        // Update booked slots table
        self::update_booked_slot( $post_id );
    }

    /**
     * Update booked slot in custom table.
     *
     * @param int $booking_id Booking ID.
     */
    private static function update_booked_slot( $booking_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'sodek_gb_booked_slots';

        // Delete existing slot
        $wpdb->delete( $table, array( 'booking_id' => $booking_id ) );

        // Get booking data
        $status = get_post_meta( $booking_id, '_sodek_gb_status', true );

        // Only insert if not cancelled
        if ( self::STATUS_CANCELLED !== $status ) {
            $wpdb->insert(
                $table,
                array(
                    'booking_id'  => $booking_id,
                    'slot_date'   => get_post_meta( $booking_id, '_sodek_gb_booking_date', true ),
                    'start_time'  => get_post_meta( $booking_id, '_sodek_gb_start_time', true ),
                    'end_time'    => get_post_meta( $booking_id, '_sodek_gb_end_time', true ),
                    'service_id'  => get_post_meta( $booking_id, '_sodek_gb_service_id', true ),
                    'status'      => $status,
                ),
                array( '%d', '%s', '%s', '%s', '%d', '%s' )
            );
        }
    }

    /**
     * Add custom columns.
     *
     * @param array $columns Columns.
     * @return array
     */
    public static function add_columns( $columns ) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = __( 'Booking', 'glowbook' );
        $new_columns['sodek_gb_customer'] = __( 'Customer', 'glowbook' );
        $new_columns['sodek_gb_service'] = __( 'Service', 'glowbook' );
        $new_columns['sodek_gb_datetime'] = __( 'Date & Time', 'glowbook' );
        $new_columns['sodek_gb_status'] = __( 'Status', 'glowbook' );
        $new_columns['date'] = $columns['date'];
        return $new_columns;
    }

    /**
     * Render custom columns.
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public static function render_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'sodek_gb_customer':
                $name = get_post_meta( $post_id, '_sodek_gb_customer_name', true );
                $email = get_post_meta( $post_id, '_sodek_gb_customer_email', true );
                echo esc_html( $name );
                if ( $email ) {
                    echo '<br><small>' . esc_html( $email ) . '</small>';
                }
                break;

            case 'sodek_gb_service':
                $service_id = get_post_meta( $post_id, '_sodek_gb_service_id', true );
                if ( $service_id ) {
                    $service = get_post( $service_id );
                    if ( $service ) {
                        echo esc_html( $service->post_title );
                    }
                }
                break;

            case 'sodek_gb_datetime':
                $date = get_post_meta( $post_id, '_sodek_gb_booking_date', true );
                $time = get_post_meta( $post_id, '_sodek_gb_start_time', true );
                if ( $date ) {
                    echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $date ) ) );
                    if ( $time ) {
                        echo '<br><small>' . esc_html( date_i18n( get_option( 'time_format' ), strtotime( $time ) ) ) . '</small>';
                    }
                }
                break;

            case 'sodek_gb_status':
                $status = get_post_meta( $post_id, '_sodek_gb_status', true ) ?: self::STATUS_PENDING;
                $statuses = self::get_statuses();
                $status_classes = array(
                    self::STATUS_PENDING   => 'warning',
                    self::STATUS_CONFIRMED => 'success',
                    self::STATUS_COMPLETED => 'info',
                    self::STATUS_CANCELLED => 'error',
                    self::STATUS_NO_SHOW   => 'error',
                );
                $class = isset( $status_classes[ $status ] ) ? $status_classes[ $status ] : '';
                echo '<span class="sodek-gb-status sodek-gb-status-' . esc_attr( $class ) . '">' . esc_html( $statuses[ $status ] ?? $status ) . '</span>';
                break;
        }
    }

    /**
     * Sortable columns.
     *
     * @param array $columns Columns.
     * @return array
     */
    public static function sortable_columns( $columns ) {
        $columns['sodek_gb_datetime'] = 'sodek_gb_datetime';
        $columns['sodek_gb_status'] = 'sodek_gb_status';
        return $columns;
    }

    /**
     * Create a new booking.
     *
     * @param array $data Booking data.
     * @return int|WP_Error Booking ID or error.
     */
    public static function create_booking( $data ) {
        $defaults = array(
            'service_id'      => 0,
            'booking_date'    => '',
            'start_time'      => '',
            'end_time'        => '',
            'customer_name'   => '',
            'customer_email'  => '',
            'customer_phone'  => '',
            'customer_id'     => 0,
            'order_id'        => 0,
            'status'          => self::STATUS_PENDING,
            'addon_ids'       => array(),
            'total_price'     => null,
            'deposit_amount'  => null,
            'deposit_paid'    => false,
            'balance_paid'    => false,
            'notes'           => '',
            'payment_method'  => '',
            'transaction_id'  => '',
            'staff_id'        => 0,
        );

        $data = wp_parse_args( $data, $defaults );

        // Validate required fields
        if ( empty( $data['service_id'] ) || empty( $data['booking_date'] ) || empty( $data['start_time'] ) ) {
            return new WP_Error( 'missing_data', __( 'Missing required booking data.', 'glowbook' ) );
        }

        // Get service info
        $service = Sodek_GB_Service::get_service( $data['service_id'] );
        if ( ! $service ) {
            return new WP_Error( 'invalid_service', __( 'Invalid service.', 'glowbook' ) );
        }

        // Calculate addon totals
        $addons_price = 0;
        $addons_duration = 0;
        if ( ! empty( $data['addon_ids'] ) && is_array( $data['addon_ids'] ) ) {
            foreach ( $data['addon_ids'] as $addon_id ) {
                $addon = Sodek_GB_Addon::get_addon( $addon_id );
                if ( $addon ) {
                    $addons_price += (float) $addon['price'];
                    $addons_duration += (int) $addon['duration'];
                }
            }
        }

        // Calculate end time if not provided (include addon duration)
        if ( empty( $data['end_time'] ) ) {
            $start = strtotime( $data['start_time'] );
            $total_duration = $service['duration'] + $addons_duration;
            $end = $start + ( $total_duration * 60 );
            $data['end_time'] = gmdate( 'H:i', $end );
        }

        // Use provided total_price or calculate from service + addons
        $total_price = $data['total_price'] ?? ( $service['price'] + $addons_price );

        // Use provided deposit_amount or service default
        $deposit_amount = $data['deposit_amount'] ?? $service['deposit_amount'];

        // Create booking title
        $title = sprintf(
            '%s - %s %s',
            $data['customer_name'],
            $service['title'],
            date_i18n( 'M j, Y', strtotime( $data['booking_date'] ) )
        );

        // Create post
        $post_id = wp_insert_post( array(
            'post_type'   => self::POST_TYPE,
            'post_title'  => $title,
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Save core meta
        update_post_meta( $post_id, '_sodek_gb_service_id', $data['service_id'] );
        update_post_meta( $post_id, '_sodek_gb_booking_date', $data['booking_date'] );
        update_post_meta( $post_id, '_sodek_gb_start_time', $data['start_time'] );
        update_post_meta( $post_id, '_sodek_gb_end_time', $data['end_time'] );
        update_post_meta( $post_id, '_sodek_gb_customer_name', $data['customer_name'] );
        update_post_meta( $post_id, '_sodek_gb_customer_email', $data['customer_email'] );
        update_post_meta( $post_id, '_sodek_gb_customer_phone', $data['customer_phone'] );
        update_post_meta( $post_id, '_sodek_gb_customer_id', $data['customer_id'] );
        update_post_meta( $post_id, '_sodek_gb_order_id', $data['order_id'] );
        update_post_meta( $post_id, '_sodek_gb_status', $data['status'] );
        update_post_meta( $post_id, '_sodek_gb_total_price', $total_price );
        update_post_meta( $post_id, '_sodek_gb_deposit_amount', $deposit_amount );
        update_post_meta( $post_id, '_sodek_gb_deposit_paid', $data['deposit_paid'] ? 1 : 0 );
        update_post_meta( $post_id, '_sodek_gb_balance_paid', $data['balance_paid'] ? 1 : 0 );
        update_post_meta( $post_id, '_sodek_gb_customer_notes', $data['notes'] );

        // Save staff assignment
        if ( ! empty( $data['staff_id'] ) ) {
            update_post_meta( $post_id, '_sodek_gb_staff_id', $data['staff_id'] );
        }

        // Save payment info
        if ( ! empty( $data['payment_method'] ) ) {
            update_post_meta( $post_id, '_sodek_gb_payment_method', $data['payment_method'] );
        }
        if ( ! empty( $data['transaction_id'] ) ) {
            update_post_meta( $post_id, '_sodek_gb_transaction_id', $data['transaction_id'] );
        }

        // Save addon data
        if ( ! empty( $data['addon_ids'] ) && is_array( $data['addon_ids'] ) ) {
            update_post_meta( $post_id, '_sodek_gb_addon_ids', $data['addon_ids'] );
            update_post_meta( $post_id, '_sodek_gb_addons_total_price', $addons_price );
            update_post_meta( $post_id, '_sodek_gb_addons_total_duration', $addons_duration );
        }

        // Update booked slots table
        self::update_booked_slot( $post_id );

        return $post_id;
    }

    /**
     * Get booking data.
     *
     * @param int $booking_id Booking ID.
     * @return array|false
     */
    public static function get_booking( $booking_id ) {
        $post = get_post( $booking_id );

        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return false;
        }

        $service_id = get_post_meta( $post->ID, '_sodek_gb_service_id', true );

        // Get add-on data
        $addon_ids     = Sodek_GB_Addon::get_booking_addons( $post->ID );
        $addons        = array();
        $addons_price  = (float) get_post_meta( $post->ID, '_sodek_gb_addons_total_price', true );
        $addons_duration = (int) get_post_meta( $post->ID, '_sodek_gb_addons_total_duration', true );

        if ( ! empty( $addon_ids ) ) {
            foreach ( $addon_ids as $addon_id ) {
                $addon = Sodek_GB_Addon::get_addon( $addon_id );
                if ( $addon ) {
                    $addons[] = $addon;
                }
            }
        }

        return array(
            'id'              => $post->ID,
            'title'           => $post->post_title,
            'service_id'      => $service_id,
            'service'         => Sodek_GB_Service::get_service( $service_id ),
            'booking_date'    => get_post_meta( $post->ID, '_sodek_gb_booking_date', true ),
            'start_time'      => get_post_meta( $post->ID, '_sodek_gb_start_time', true ),
            'end_time'        => get_post_meta( $post->ID, '_sodek_gb_end_time', true ),
            'customer_name'   => get_post_meta( $post->ID, '_sodek_gb_customer_name', true ),
            'customer_email'  => get_post_meta( $post->ID, '_sodek_gb_customer_email', true ),
            'customer_phone'  => get_post_meta( $post->ID, '_sodek_gb_customer_phone', true ),
            'customer_id'     => get_post_meta( $post->ID, '_sodek_gb_customer_id', true ),
            'staff_id'        => get_post_meta( $post->ID, '_sodek_gb_staff_id', true ),
            'order_id'        => get_post_meta( $post->ID, '_sodek_gb_order_id', true ),
            'status'          => get_post_meta( $post->ID, '_sodek_gb_status', true ) ?: self::STATUS_PENDING,
            'deposit_amount'  => get_post_meta( $post->ID, '_sodek_gb_deposit_amount', true ),
            'total_price'     => get_post_meta( $post->ID, '_sodek_gb_total_price', true ),
            'deposit_paid'    => get_post_meta( $post->ID, '_sodek_gb_deposit_paid', true ),
            'balance_paid'    => get_post_meta( $post->ID, '_sodek_gb_balance_paid', true ),
            'notes'           => get_post_meta( $post->ID, '_sodek_gb_customer_notes', true ),
            'admin_notes'     => get_post_meta( $post->ID, '_sodek_gb_admin_notes', true ),
            'addon_ids'       => $addon_ids,
            'addons'          => $addons,
            'addons_price'    => $addons_price,
            'addons_duration' => $addons_duration,
            'created_at'      => $post->post_date,
        );
    }

    /**
     * Update booking status.
     *
     * @param int    $booking_id Booking ID.
     * @param string $status     New status.
     * @return bool
     */
    public static function update_status( $booking_id, $status ) {
        if ( ! array_key_exists( $status, self::get_statuses() ) ) {
            return false;
        }

        update_post_meta( $booking_id, '_sodek_gb_status', $status );
        self::update_booked_slot( $booking_id );

        do_action( 'sodek_gb_booking_status_changed', $booking_id, $status );

        return true;
    }

    /**
     * Update editable booking fields.
     *
     * Supports the REST portal flows that need to reschedule or cancel an
     * existing booking without rebuilding the record from scratch.
     *
     * @param int   $booking_id Booking ID.
     * @param array $data       Data to update.
     * @return true|WP_Error
     */
    public static function update_booking( $booking_id, array $data ) {
        $booking = self::get_booking( $booking_id );

        if ( ! $booking ) {
            return new WP_Error( 'booking_not_found', __( 'Booking not found.', 'glowbook' ) );
        }

        if ( isset( $data['booking_date'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_booking_date', sanitize_text_field( $data['booking_date'] ) );
        }

        if ( isset( $data['start_time'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_start_time', sanitize_text_field( $data['start_time'] ) );
        }

        if ( isset( $data['end_time'] ) ) {
            update_post_meta( $booking_id, '_sodek_gb_end_time', sanitize_text_field( $data['end_time'] ) );
        }

        if ( isset( $data['status'] ) ) {
            $updated = self::update_status( $booking_id, sanitize_text_field( $data['status'] ) );
            if ( ! $updated ) {
                return new WP_Error( 'invalid_status', __( 'Invalid booking status.', 'glowbook' ) );
            }
        } else {
            self::update_booked_slot( $booking_id );
        }

        return true;
    }
}
