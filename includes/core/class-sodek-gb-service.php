<?php
/**
 * Service Custom Post Type.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Service class.
 */
class Sodek_GB_Service {

    /**
     * Post type name.
     */
    const POST_TYPE = 'sodek_gb_service';

    /**
     * Taxonomy name.
     */
    const TAXONOMY = 'sodek_gb_service_cat';

    /**
     * Register the post type.
     */
    public static function register_post_type() {
        // Register taxonomy first
        self::register_taxonomy();
        $labels = array(
            'name'                  => _x( 'Services', 'Post type general name', 'glowbook' ),
            'singular_name'         => _x( 'Service', 'Post type singular name', 'glowbook' ),
            'menu_name'             => _x( 'Services', 'Admin Menu text', 'glowbook' ),
            'add_new'               => __( 'Add New', 'glowbook' ),
            'add_new_item'          => __( 'Add New Service', 'glowbook' ),
            'edit_item'             => __( 'Edit Service', 'glowbook' ),
            'new_item'              => __( 'New Service', 'glowbook' ),
            'view_item'             => __( 'View Service', 'glowbook' ),
            'search_items'          => __( 'Search Services', 'glowbook' ),
            'not_found'             => __( 'No services found', 'glowbook' ),
            'not_found_in_trash'    => __( 'No services found in Trash', 'glowbook' ),
            'all_items'             => __( 'All Services', 'glowbook' ),
            'archives'              => __( 'Service Archives', 'glowbook' ),
            'attributes'            => __( 'Service Attributes', 'glowbook' ),
            'insert_into_item'      => __( 'Insert into service', 'glowbook' ),
            'uploaded_to_this_item' => __( 'Uploaded to this service', 'glowbook' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false, // Will be added to our custom menu
            'show_in_nav_menus'   => true,  // Allow adding to navigation menus
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => 'service',
                'with_front' => false,
            ),
            'capability_type'     => 'post',
            'has_archive'         => true,  // Enable archive page at /service/
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'show_in_rest'        => true,
        );

        register_post_type( self::POST_TYPE, $args );

        // Register meta boxes
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 2 );

        // Filter frontend queries to only show visible services
        add_action( 'pre_get_posts', array( __CLASS__, 'filter_frontend_services' ) );

        // Load service template
        add_filter( 'single_template', array( __CLASS__, 'load_single_template' ) );
        add_filter( 'archive_template', array( __CLASS__, 'load_archive_template' ) );
    }

    /**
     * Filter frontend queries to only show services with "Show on Frontend" enabled.
     *
     * @param WP_Query $query The query object.
     */
    public static function filter_frontend_services( $query ) {
        // Only filter on frontend, main query, and for our post type
        if ( is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( $query->get( 'post_type' ) !== self::POST_TYPE && ! $query->is_post_type_archive( self::POST_TYPE ) ) {
            return;
        }

        // Add meta query to only show visible services
        $meta_query = $query->get( 'meta_query' ) ?: array();
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key'     => '_sodek_gb_show_on_frontend',
                'value'   => 'yes',
                'compare' => '=',
            ),
            array(
                'key'     => '_sodek_gb_show_on_frontend',
                'compare' => 'NOT EXISTS',
            ),
        );
        $query->set( 'meta_query', $meta_query );
    }

    /**
     * Load single service template.
     *
     * @param string $template Template path.
     * @return string
     */
    public static function load_single_template( $template ) {
        global $post;

        if ( $post && self::POST_TYPE === $post->post_type ) {
            // Check if theme has a template
            $theme_template = locate_template( array( 'single-sodek_gb_service.php' ) );
            if ( $theme_template ) {
                return $theme_template;
            }

            // Use plugin template
            $plugin_template = SODEK_GB_PLUGIN_DIR . 'templates/single-service.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Load archive service template.
     *
     * @param string $template Template path.
     * @return string
     */
    public static function load_archive_template( $template ) {
        if ( is_post_type_archive( self::POST_TYPE ) ) {
            // Check if theme has a template
            $theme_template = locate_template( array( 'archive-sodek_gb_service.php' ) );
            if ( $theme_template ) {
                return $theme_template;
            }

            // Use plugin template
            $plugin_template = SODEK_GB_PLUGIN_DIR . 'templates/archive-services.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Register service category taxonomy.
     */
    public static function register_taxonomy() {
        $labels = array(
            'name'              => _x( 'Service Categories', 'taxonomy general name', 'glowbook' ),
            'singular_name'     => _x( 'Service Category', 'taxonomy singular name', 'glowbook' ),
            'search_items'      => __( 'Search Categories', 'glowbook' ),
            'all_items'         => __( 'All Categories', 'glowbook' ),
            'parent_item'       => __( 'Parent Category', 'glowbook' ),
            'parent_item_colon' => __( 'Parent Category:', 'glowbook' ),
            'edit_item'         => __( 'Edit Category', 'glowbook' ),
            'update_item'       => __( 'Update Category', 'glowbook' ),
            'add_new_item'      => __( 'Add New Category', 'glowbook' ),
            'new_item_name'     => __( 'New Category Name', 'glowbook' ),
            'menu_name'         => __( 'Categories', 'glowbook' ),
            'not_found'         => __( 'No categories found', 'glowbook' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_in_menu'      => false,
            'show_admin_column' => true,
            'query_var'         => false,
            'rewrite'           => false,
            'show_in_rest'      => true,
        );

        register_taxonomy( self::TAXONOMY, self::POST_TYPE, $args );
    }

    /**
     * Get all service categories.
     *
     * @param array $args Optional. Arguments to pass to get_terms().
     * @return array
     */
    public static function get_categories( $args = array() ) {
        $defaults = array(
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        );

        $args  = wp_parse_args( $args, $defaults );
        $terms = get_terms( $args );

        if ( is_wp_error( $terms ) ) {
            return array();
        }

        $categories = array();
        foreach ( $terms as $term ) {
            $categories[] = array(
                'id'          => $term->term_id,
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'count'       => $term->count,
                'parent'      => $term->parent,
            );
        }

        return $categories;
    }

    /**
     * Get services by category.
     *
     * @param int|string $category Category ID or slug.
     * @return array
     */
    public static function get_services_by_category( $category ) {
        $tax_query = array(
            array(
                'taxonomy' => self::TAXONOMY,
                'field'    => is_numeric( $category ) ? 'term_id' : 'slug',
                'terms'    => $category,
            ),
        );

        $posts = get_posts( array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
            'tax_query'      => $tax_query,
        ) );

        $services = array();
        foreach ( $posts as $post ) {
            $services[] = self::get_service( $post->ID );
        }

        return $services;
    }

    /**
     * Get services grouped by category.
     *
     * @return array
     */
    public static function get_services_grouped_by_category() {
        // Get all categories from the taxonomy table
        $categories = self::get_categories( array( 'hide_empty' => false ) );
        $grouped    = array();

        foreach ( $categories as $category ) {
            $services = self::get_services_by_category( $category['id'] );
            $grouped[] = array(
                'category' => $category,
                'services' => $services,
            );
        }

        return $grouped;
    }

    /**
     * Add meta boxes.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'sodek_gb_service_details',
            __( 'Service Details', 'glowbook' ),
            array( __CLASS__, 'render_details_meta_box' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'sodek_gb_service_pricing',
            __( 'Pricing & Deposit', 'glowbook' ),
            array( __CLASS__, 'render_pricing_meta_box' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'sodek_gb_service_display',
            __( 'Display Options', 'glowbook' ),
            array( __CLASS__, 'render_display_meta_box' ),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * Render display options meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_display_meta_box( $post ) {
        $show_on_frontend = get_post_meta( $post->ID, '_sodek_gb_show_on_frontend', true );
        // Default to 'yes' for new services
        if ( '' === $show_on_frontend ) {
            $show_on_frontend = 'yes';
        }

        $enable_staff_selection = get_post_meta( $post->ID, '_sodek_gb_enable_staff_selection', true );
        $show_image_override    = get_post_meta( $post->ID, '_sodek_gb_show_image_override', true ) ?: 'global';
        $show_deposit_override  = get_post_meta( $post->ID, '_sodek_gb_show_deposit_override', true ) ?: 'global';
        ?>
        <p>
            <label>
                <input type="checkbox" name="sodek_gb_show_on_frontend" value="yes" <?php checked( $show_on_frontend, 'yes' ); ?>>
                <?php esc_html_e( 'Show on Frontend', 'glowbook' ); ?>
            </label>
        </p>
        <p class="description"><?php esc_html_e( 'Display this service on the website and allow bookings.', 'glowbook' ); ?></p>

        <hr style="margin: 15px 0;">

        <p>
            <label>
                <input type="checkbox" name="sodek_gb_enable_staff_selection" value="yes" <?php checked( $enable_staff_selection, 'yes' ); ?>>
                <?php esc_html_e( 'Allow Staff Selection', 'glowbook' ); ?>
            </label>
        </p>
        <p class="description"><?php esc_html_e( 'Let customers choose their preferred stylist for this service.', 'glowbook' ); ?></p>

        <hr style="margin: 15px 0;">

        <p>
            <label for="sodek_gb_show_image_override"><?php esc_html_e( 'Show Image', 'glowbook' ); ?></label><br>
            <select name="sodek_gb_show_image_override" id="sodek_gb_show_image_override" style="width: 100%;">
                <option value="global" <?php selected( $show_image_override, 'global' ); ?>><?php esc_html_e( 'Use global setting', 'glowbook' ); ?></option>
                <option value="show" <?php selected( $show_image_override, 'show' ); ?>><?php esc_html_e( 'Show', 'glowbook' ); ?></option>
                <option value="hide" <?php selected( $show_image_override, 'hide' ); ?>><?php esc_html_e( 'Hide', 'glowbook' ); ?></option>
            </select>
        </p>

        <p>
            <label for="sodek_gb_show_deposit_override"><?php esc_html_e( 'Show Deposit', 'glowbook' ); ?></label><br>
            <select name="sodek_gb_show_deposit_override" id="sodek_gb_show_deposit_override" style="width: 100%;">
                <option value="global" <?php selected( $show_deposit_override, 'global' ); ?>><?php esc_html_e( 'Use global setting', 'glowbook' ); ?></option>
                <option value="show" <?php selected( $show_deposit_override, 'show' ); ?>><?php esc_html_e( 'Show', 'glowbook' ); ?></option>
                <option value="hide" <?php selected( $show_deposit_override, 'hide' ); ?>><?php esc_html_e( 'Hide', 'glowbook' ); ?></option>
            </select>
        </p>
        <p class="description"><?php esc_html_e( 'Override global display settings for this service.', 'glowbook' ); ?></p>
        <?php
    }

    /**
     * Render service details meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_details_meta_box( $post ) {
        wp_nonce_field( 'sodek_gb_service_meta', 'sodek_gb_service_nonce' );

        $duration      = get_post_meta( $post->ID, '_sodek_gb_duration', true ) ?: 60;
        $buffer_before = get_post_meta( $post->ID, '_sodek_gb_buffer_before', true ) ?: 0;
        $buffer_after  = get_post_meta( $post->ID, '_sodek_gb_buffer_after', true ) ?: 15;
        $max_daily     = get_post_meta( $post->ID, '_sodek_gb_max_daily_bookings', true ) ?: '';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="sodek_gb_duration"><?php esc_html_e( 'Duration (minutes)', 'glowbook' ); ?></label></th>
                <td>
                    <input type="number" id="sodek_gb_duration" name="sodek_gb_duration" value="<?php echo esc_attr( $duration ); ?>" min="15" step="15" class="small-text" required>
                    <p class="description"><?php esc_html_e( 'How long the service takes.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_buffer_before"><?php esc_html_e( 'Buffer Before (minutes)', 'glowbook' ); ?></label></th>
                <td>
                    <input type="number" id="sodek_gb_buffer_before" name="sodek_gb_buffer_before" value="<?php echo esc_attr( $buffer_before ); ?>" min="0" step="5" class="small-text">
                    <p class="description"><?php esc_html_e( 'Preparation time before the appointment.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_buffer_after"><?php esc_html_e( 'Buffer After (minutes)', 'glowbook' ); ?></label></th>
                <td>
                    <input type="number" id="sodek_gb_buffer_after" name="sodek_gb_buffer_after" value="<?php echo esc_attr( $buffer_after ); ?>" min="0" step="5" class="small-text">
                    <p class="description"><?php esc_html_e( 'Cleanup/transition time after the appointment.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_max_daily"><?php esc_html_e( 'Max Daily Bookings', 'glowbook' ); ?></label></th>
                <td>
                    <input type="number" id="sodek_gb_max_daily" name="sodek_gb_max_daily" value="<?php echo esc_attr( $max_daily ); ?>" min="0" class="small-text">
                    <p class="description"><?php esc_html_e( 'Leave empty for unlimited.', 'glowbook' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render pricing meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_pricing_meta_box( $post ) {
        $price         = get_post_meta( $post->ID, '_sodek_gb_price', true ) ?: '';
        $deposit_type  = get_post_meta( $post->ID, '_sodek_gb_deposit_type', true ) ?: 'fixed';
        $deposit_value = get_post_meta( $post->ID, '_sodek_gb_deposit_value', true ) ?: 50;
        ?>
        <table class="form-table">
            <tr>
                <th><label for="sodek_gb_price"><?php esc_html_e( 'Base Price', 'glowbook' ); ?></label></th>
                <td>
                    <input type="number" id="sodek_gb_price" name="sodek_gb_price" value="<?php echo esc_attr( $price ); ?>" min="0" step="0.01" class="regular-text">
                    <p class="description">
                        <?php esc_html_e( 'Base service price. Set to 0 for addon-only services where price depends entirely on selected add-ons.', 'glowbook' ); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_deposit_type"><?php esc_html_e( 'Deposit Type', 'glowbook' ); ?></label></th>
                <td>
                    <select id="sodek_gb_deposit_type" name="sodek_gb_deposit_type">
                        <option value="fixed" <?php selected( $deposit_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount', 'glowbook' ); ?></option>
                        <option value="percentage" <?php selected( $deposit_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage', 'glowbook' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_deposit_value"><?php esc_html_e( 'Deposit Value', 'glowbook' ); ?></label></th>
                <td>
                    <input type="number" id="sodek_gb_deposit_value" name="sodek_gb_deposit_value" value="<?php echo esc_attr( $deposit_value ); ?>" min="0" step="0.01" class="small-text">
                    <span class="sodek-gb-deposit-suffix"><?php echo 'percentage' === $deposit_type ? '%' : esc_html( get_woocommerce_currency_symbol() ); ?></span>
                    <p class="description">
                        <?php esc_html_e( 'Amount customer pays upfront to confirm booking.', 'glowbook' ); ?>
                        <br>
                        <strong><?php esc_html_e( 'Percentage:', 'glowbook' ); ?></strong> <?php esc_html_e( 'Enter value like 50 for 50% of the full price.', 'glowbook' ); ?>
                        <br>
                        <strong><?php esc_html_e( 'Fixed Amount:', 'glowbook' ); ?></strong> <?php esc_html_e( 'Enter exact deposit amount (e.g., 50 for $50).', 'glowbook' ); ?>
                    </p>
                </td>
            </tr>
        </table>

        <script>
        jQuery(function($) {
            var currencySymbol = '<?php echo esc_js( get_woocommerce_currency_symbol() ); ?>';

            function updateDepositPreview() {
                var price = parseFloat($('#sodek_gb_price').val()) || 0;
                var depositType = $('#sodek_gb_deposit_type').val();
                var depositValue = parseFloat($('#sodek_gb_deposit_value').val()) || 0;
                var depositAmount = 0;

                if (depositType === 'percentage') {
                    depositAmount = price * (depositValue / 100);
                } else {
                    depositAmount = Math.min(depositValue, price);
                }

                var balance = price - depositAmount;

                $('.sodek-gb-deposit-preview').html(
                    '<strong><?php esc_html_e( 'Preview:', 'glowbook' ); ?></strong> ' +
                    '<?php esc_html_e( 'Deposit:', 'glowbook' ); ?> ' + currencySymbol + depositAmount.toFixed(2) +
                    ' | <?php esc_html_e( 'Balance due:', 'glowbook' ); ?> ' + currencySymbol + balance.toFixed(2)
                );
            }

            $('#sodek_gb_deposit_type').on('change', function() {
                $('.sodek-gb-deposit-suffix').text($(this).val() === 'percentage' ? '%' : currencySymbol);
                updateDepositPreview();
            });

            $('#sodek_gb_price, #sodek_gb_deposit_value').on('input', updateDepositPreview);

            // Add preview element if not exists
            if ($('.sodek-gb-deposit-preview').length === 0) {
                $('#sodek_gb_deposit_value').closest('td').append('<p class="sodek-gb-deposit-preview" style="margin-top: 10px; padding: 8px; background: #f0f0f1; border-left: 4px solid #2271b1;"></p>');
            }

            // Initial preview
            updateDepositPreview();
        });
        </script>
        <?php
    }

    /**
     * Save meta data.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public static function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['sodek_gb_service_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sodek_gb_service_nonce'] ) ), 'sodek_gb_service_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save fields
        $fields = array(
            'sodek_gb_duration'      => '_sodek_gb_duration',
            'sodek_gb_buffer_before' => '_sodek_gb_buffer_before',
            'sodek_gb_buffer_after'  => '_sodek_gb_buffer_after',
            'sodek_gb_max_daily'     => '_sodek_gb_max_daily_bookings',
            'sodek_gb_price'         => '_sodek_gb_price',
            'sodek_gb_deposit_type'  => '_sodek_gb_deposit_type',
            'sodek_gb_deposit_value' => '_sodek_gb_deposit_value',
        );

        foreach ( $fields as $field => $meta_key ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $field ] ) );
            }
        }

        // Save display options
        $show_on_frontend = isset( $_POST['sodek_gb_show_on_frontend'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_sodek_gb_show_on_frontend', $show_on_frontend );

        $enable_staff_selection = isset( $_POST['sodek_gb_enable_staff_selection'] ) ? 'yes' : 'no';
        update_post_meta( $post_id, '_sodek_gb_enable_staff_selection', $enable_staff_selection );

        // Save display overrides
        if ( isset( $_POST['sodek_gb_show_image_override'] ) ) {
            $show_image = sanitize_text_field( $_POST['sodek_gb_show_image_override'] );
            if ( in_array( $show_image, array( 'global', 'show', 'hide' ), true ) ) {
                update_post_meta( $post_id, '_sodek_gb_show_image_override', $show_image );
            }
        }
        if ( isset( $_POST['sodek_gb_show_deposit_override'] ) ) {
            $show_deposit = sanitize_text_field( $_POST['sodek_gb_show_deposit_override'] );
            if ( in_array( $show_deposit, array( 'global', 'show', 'hide' ), true ) ) {
                update_post_meta( $post_id, '_sodek_gb_show_deposit_override', $show_deposit );
            }
        }
    }

    /**
     * Calculate deposit amount for a service.
     *
     * @param int $service_id Service ID.
     * @return float
     */
    public static function calculate_deposit( $service_id ) {
        $price        = (float) get_post_meta( $service_id, '_sodek_gb_price', true );
        $deposit_type = get_post_meta( $service_id, '_sodek_gb_deposit_type', true ) ?: 'fixed';
        $deposit_value = (float) get_post_meta( $service_id, '_sodek_gb_deposit_value', true );

        if ( 'percentage' === $deposit_type ) {
            return round( $price * ( $deposit_value / 100 ), 2 );
        }

        return min( $deposit_value, $price );
    }

    /**
     * Get service data.
     *
     * @param int $service_id Service ID.
     * @return array|false
     */
    public static function get_service( $service_id ) {
        $post = get_post( $service_id );

        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return false;
        }

        // Get categories
        $terms = wp_get_post_terms( $post->ID, self::TAXONOMY );
        $categories = array();
        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $term ) {
                $categories[] = array(
                    'id'   => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                );
            }
        }

        $max_daily = get_post_meta( $post->ID, '_sodek_gb_max_daily_bookings', true );

        return array(
            'id'                 => $post->ID,
            'title'              => $post->post_title,
            'description'        => $post->post_content,
            'duration'           => (int) get_post_meta( $post->ID, '_sodek_gb_duration', true ) ?: 60,
            'buffer_before'      => (int) get_post_meta( $post->ID, '_sodek_gb_buffer_before', true ) ?: 0,
            'buffer_after'       => (int) get_post_meta( $post->ID, '_sodek_gb_buffer_after', true ) ?: 15,
            'price'              => (float) get_post_meta( $post->ID, '_sodek_gb_price', true ),
            'deposit_type'       => get_post_meta( $post->ID, '_sodek_gb_deposit_type', true ) ?: 'fixed',
            'deposit_value'      => (float) get_post_meta( $post->ID, '_sodek_gb_deposit_value', true ),
            'deposit_amount'     => self::calculate_deposit( $post->ID ),
            'thumbnail'              => get_the_post_thumbnail_url( $post->ID, 'medium' ),
            'thumbnail_full'         => get_the_post_thumbnail_url( $post->ID, 'large' ),
            'categories'             => $categories,
            'max_daily_bookings'     => $max_daily !== '' ? (int) $max_daily : 0,
            'show_image_override'    => get_post_meta( $post->ID, '_sodek_gb_show_image_override', true ) ?: 'global',
            'show_deposit_override'  => get_post_meta( $post->ID, '_sodek_gb_show_deposit_override', true ) ?: 'global',
        );
    }

    /**
     * Ensure WooCommerce product exists for service.
     *
     * @param int     $service_id Service post ID.
     * @param WP_Post $service    Service post object.
     * @return int WC product ID or 0
     */
    /**
     * Get all active services.
     *
     * @return array
     */
    public static function get_all_services() {
        $posts = get_posts( array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ) );

        $services = array();
        foreach ( $posts as $post ) {
            $services[] = self::get_service( $post->ID );
        }

        return $services;
    }
}
