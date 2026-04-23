<?php
/**
 * Service Add-on Custom Post Type.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Addon class.
 */
class Sodek_GB_Addon {

    /**
     * Post type name.
     */
    const POST_TYPE = 'sodek_gb_addon';

    /**
     * Initialize.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
    }

    /**
     * Register the post type.
     */
    public static function register_post_type() {
        $labels = array(
            'name'               => _x( 'Add-ons', 'Post type general name', 'glowbook' ),
            'singular_name'      => _x( 'Add-on', 'Post type singular name', 'glowbook' ),
            'menu_name'          => _x( 'Add-ons', 'Admin Menu text', 'glowbook' ),
            'add_new'            => __( 'Add New', 'glowbook' ),
            'add_new_item'       => __( 'Add New Add-on', 'glowbook' ),
            'edit_item'          => __( 'Edit Add-on', 'glowbook' ),
            'new_item'           => __( 'New Add-on', 'glowbook' ),
            'view_item'          => __( 'View Add-on', 'glowbook' ),
            'search_items'       => __( 'Search Add-ons', 'glowbook' ),
            'not_found'          => __( 'No add-ons found', 'glowbook' ),
            'not_found_in_trash' => __( 'No add-ons found in Trash', 'glowbook' ),
            'all_items'          => __( 'All Add-ons', 'glowbook' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'post',
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

        // Quick edit
        add_action( 'quick_edit_custom_box', array( __CLASS__, 'quick_edit_fields' ), 10, 2 );
        add_action( 'admin_footer-edit.php', array( __CLASS__, 'quick_edit_js' ) );
    }

    /**
     * Add meta boxes.
     */
    public static function add_meta_boxes() {
        add_meta_box(
            'sodek_gb_addon_details',
            __( 'Add-on Details', 'glowbook' ),
            array( __CLASS__, 'render_details_meta_box' ),
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'sodek_gb_addon_services',
            __( 'Available For Services', 'glowbook' ),
            array( __CLASS__, 'render_services_meta_box' ),
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    /**
     * Render add-on details meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_details_meta_box( $post ) {
        wp_nonce_field( 'sodek_gb_addon_meta', 'sodek_gb_addon_nonce' );

        $price       = get_post_meta( $post->ID, '_sodek_gb_addon_price', true ) ?: '';
        $duration    = get_post_meta( $post->ID, '_sodek_gb_addon_duration', true ) ?: 0;
        $description = get_post_meta( $post->ID, '_sodek_gb_addon_description', true ) ?: '';
        $image_id    = get_post_meta( $post->ID, '_sodek_gb_addon_image_id', true ) ?: '';
        $image_url   = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';

        // Enqueue media uploader
        wp_enqueue_media();
        ?>
        <table class="form-table">
            <tr>
                <th><label><?php esc_html_e( 'Image', 'glowbook' ); ?></label></th>
                <td>
                    <div class="sodek-gb-addon-image-field">
                        <input type="hidden" id="sodek_gb_addon_image_id" name="sodek_gb_addon_image_id" value="<?php echo esc_attr( $image_id ); ?>">
                        <div id="sodek-gb-addon-image-preview" style="margin-bottom: 10px; <?php echo $image_url ? '' : 'display: none;'; ?>">
                            <img src="<?php echo esc_url( $image_url ); ?>" style="max-width: 150px; height: auto; border-radius: 8px; border: 1px solid #ddd;">
                        </div>
                        <button type="button" class="button" id="sodek-gb-addon-image-upload">
                            <?php echo $image_id ? esc_html__( 'Change Image', 'glowbook' ) : esc_html__( 'Upload Image', 'glowbook' ); ?>
                        </button>
                        <button type="button" class="button" id="sodek-gb-addon-image-remove" style="<?php echo $image_id ? '' : 'display: none;'; ?>">
                            <?php esc_html_e( 'Remove', 'glowbook' ); ?>
                        </button>
                        <p class="description"><?php esc_html_e( 'Optional image to display with this add-on. Recommended size: 200x200px.', 'glowbook' ); ?></p>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_addon_price"><?php esc_html_e( 'Price', 'glowbook' ); ?></label></th>
                <td>
                    <input type="number" id="sodek_gb_addon_price" name="sodek_gb_addon_price"
                           value="<?php echo esc_attr( $price ); ?>" min="0" step="0.01" class="regular-text" required>
                    <p class="description"><?php esc_html_e( 'Additional price for this add-on.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_addon_duration"><?php esc_html_e( 'Extra Duration (optional)', 'glowbook' ); ?></label></th>
                <td>
                    <input type="number" id="sodek_gb_addon_duration" name="sodek_gb_addon_duration"
                           value="<?php echo esc_attr( $duration ); ?>" min="0" step="5" class="small-text" placeholder="0">
                    <span class="description"><?php esc_html_e( 'minutes', 'glowbook' ); ?></span>
                    <p class="description"><?php esc_html_e( 'Leave empty or 0 if this add-on doesn\'t require extra time.', 'glowbook' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="sodek_gb_addon_description"><?php esc_html_e( 'Description', 'glowbook' ); ?></label></th>
                <td>
                    <textarea id="sodek_gb_addon_description" name="sodek_gb_addon_description"
                              rows="3" class="large-text"><?php echo esc_textarea( $description ); ?></textarea>
                    <p class="description"><?php esc_html_e( 'Optional description shown to customers.', 'glowbook' ); ?></p>
                </td>
            </tr>
        </table>

        <div class="sodek-gb-addon-preview" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-left: 4px solid #2271b1;">
            <strong><?php esc_html_e( 'Preview:', 'glowbook' ); ?></strong>
            <div style="margin-top: 10px; font-size: 14px; display: flex; align-items: center; gap: 12px;">
                <div id="sodek-gb-addon-preview-image" style="<?php echo $image_url ? '' : 'display: none;'; ?>">
                    <img src="<?php echo esc_url( $image_url ); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                </div>
                <div>
                    <span id="sodek-gb-addon-preview-name"><?php echo esc_html( $post->post_title ?: __( 'Add-on Name', 'glowbook' ) ); ?></span><br>
                    <span style="color: #666;">
                        + <span id="sodek-gb-addon-preview-duration"><?php echo $duration ? esc_html( self::format_duration( $duration ) ) : '0 min'; ?></span>
                        @ <?php echo esc_html( get_woocommerce_currency_symbol() ); ?><span id="sodek-gb-addon-preview-price"><?php echo esc_html( number_format( (float) $price, 2 ) ); ?></span>
                    </span>
                </div>
            </div>
        </div>

        <script>
        jQuery(function($) {
            // Price/duration preview update
            function updatePreview() {
                var duration = parseInt($('#sodek_gb_addon_duration').val()) || 0;
                var price = parseFloat($('#sodek_gb_addon_price').val()) || 0;

                var durationText = duration >= 60
                    ? Math.floor(duration / 60) + ' hour' + (Math.floor(duration / 60) > 1 ? 's' : '') + (duration % 60 ? ' ' + (duration % 60) + ' min' : '')
                    : duration + ' min';

                $('#sodek-gb-addon-preview-duration').text(durationText);
                $('#sodek-gb-addon-preview-price').text(price.toFixed(2));
            }

            $('#sodek_gb_addon_price, #sodek_gb_addon_duration').on('input', updatePreview);

            // Image uploader
            var mediaUploader;

            $('#sodek-gb-addon-image-upload').on('click', function(e) {
                e.preventDefault();

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: '<?php echo esc_js( __( 'Select Add-on Image', 'glowbook' ) ); ?>',
                    button: {
                        text: '<?php echo esc_js( __( 'Use this image', 'glowbook' ) ); ?>'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });

                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    var imageUrl = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

                    $('#sodek_gb_addon_image_id').val(attachment.id);
                    $('#sodek-gb-addon-image-preview').show().find('img').attr('src', imageUrl);
                    $('#sodek-gb-addon-image-remove').show();
                    $('#sodek-gb-addon-image-upload').text('<?php echo esc_js( __( 'Change Image', 'glowbook' ) ); ?>');

                    // Update preview
                    $('#sodek-gb-addon-preview-image').show().find('img').attr('src', imageUrl);
                });

                mediaUploader.open();
            });

            $('#sodek-gb-addon-image-remove').on('click', function(e) {
                e.preventDefault();
                $('#sodek_gb_addon_image_id').val('');
                $('#sodek-gb-addon-image-preview').hide();
                $('#sodek-gb-addon-image-remove').hide();
                $('#sodek-gb-addon-image-upload').text('<?php echo esc_js( __( 'Upload Image', 'glowbook' ) ); ?>');
                $('#sodek-gb-addon-preview-image').hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Render services meta box.
     *
     * @param WP_Post $post Post object.
     */
    public static function render_services_meta_box( $post ) {
        $selected_services = get_post_meta( $post->ID, '_sodek_gb_addon_services', true ) ?: array();
        $all_for_all       = get_post_meta( $post->ID, '_sodek_gb_addon_all_services', true );

        // Get all services
        $services = get_posts( array(
            'post_type'      => 'sodek_gb_service',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
        ?>
        <p>
            <label>
                <input type="checkbox" name="sodek_gb_addon_all_services" value="yes" <?php checked( $all_for_all, 'yes' ); ?>>
                <strong><?php esc_html_e( 'Available for all services', 'glowbook' ); ?></strong>
            </label>
        </p>

        <input type="hidden" name="sodek_gb_addon_services_present" value="1">

        <div id="sodek-gb-addon-services-list" <?php echo 'yes' === $all_for_all ? 'style="display:none;"' : ''; ?>>
            <p><strong><?php esc_html_e( 'Or select specific services:', 'glowbook' ); ?></strong></p>
            <?php if ( empty( $services ) ) : ?>
                <p class="description"><?php esc_html_e( 'No services found. Create services first.', 'glowbook' ); ?></p>
            <?php else : ?>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                    <?php foreach ( $services as $service ) : ?>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="checkbox" name="sodek_gb_addon_services[]"
                                   class="sodek-gb-addon-service-checkbox"
                                   value="<?php echo esc_attr( $service->ID ); ?>"
                                   <?php checked( in_array( (int) $service->ID, array_map( 'intval', (array) $selected_services ), true ) ); ?>
                                   <?php disabled( 'yes' === $all_for_all ); ?>>
                            <?php echo esc_html( $service->post_title ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(function($) {
            const $allServices = $('input[name="sodek_gb_addon_all_services"]');
            const $servicesList = $('#sodek-gb-addon-services-list');
            const $serviceInputs = $servicesList.find('.sodek-gb-addon-service-checkbox');

            function syncServicesState() {
                const useAllServices = $allServices.is(':checked');
                $servicesList.toggle(!useAllServices);
                $serviceInputs.prop('disabled', useAllServices);

                // Hidden checked boxes still submit, so clear them when "all services" is enabled.
                if (useAllServices) {
                    $serviceInputs.prop('checked', false);
                }
            }

            $allServices.on('change', syncServicesState);
            syncServicesState();
        });
        </script>
        <?php
    }

    /**
     * Format duration for display.
     *
     * @param int $minutes Duration in minutes.
     * @return string
     */
    public static function format_duration( $minutes ) {
        if ( $minutes >= 60 ) {
            $hours = floor( $minutes / 60 );
            $mins  = $minutes % 60;
            if ( $mins > 0 ) {
                return sprintf(
                    /* translators: 1: hours, 2: minutes */
                    _n( '%1$d hour %2$d min', '%1$d hours %2$d min', $hours, 'glowbook' ),
                    $hours,
                    $mins
                );
            }
            return sprintf(
                /* translators: %d: hours */
                _n( '%d hour', '%d hours', $hours, 'glowbook' ),
                $hours
            );
        }
        return sprintf(
            /* translators: %d: minutes */
            __( '%d min', 'glowbook' ),
            $minutes
        );
    }

    /**
     * Normalize submitted service IDs.
     *
     * @param mixed $services Raw submitted services.
     * @return array
     */
    private static function normalize_service_ids( $services ) {
        if ( ! is_array( $services ) ) {
            return array();
        }

        $services = array_map( 'absint', wp_unslash( $services ) );
        $services = array_filter( $services );
        $services = array_values( array_unique( $services ) );

        sort( $services );

        return $services;
    }

    /**
     * Save meta data.
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public static function save_meta( $post_id, $post ) {
        // Check for regular edit nonce OR quick edit nonce
        $has_regular_nonce = isset( $_POST['sodek_gb_addon_nonce'] ) &&
            wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sodek_gb_addon_nonce'] ) ), 'sodek_gb_addon_meta' );
        $has_quick_nonce = isset( $_POST['sodek_gb_addon_quick_nonce'] ) &&
            wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sodek_gb_addon_quick_nonce'] ) ), 'sodek_gb_addon_quick_edit' );

        if ( ! $has_regular_nonce && ! $has_quick_nonce ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save fields (works for both regular and quick edit)
        if ( isset( $_POST['sodek_gb_addon_price'] ) ) {
            update_post_meta( $post_id, '_sodek_gb_addon_price', floatval( $_POST['sodek_gb_addon_price'] ) );
        }

        if ( isset( $_POST['sodek_gb_addon_duration'] ) ) {
            update_post_meta( $post_id, '_sodek_gb_addon_duration', absint( $_POST['sodek_gb_addon_duration'] ) );
        }

        // Only save these on regular edit (not quick edit)
        if ( $has_regular_nonce ) {
            if ( isset( $_POST['sodek_gb_addon_description'] ) ) {
                update_post_meta( $post_id, '_sodek_gb_addon_description', sanitize_textarea_field( $_POST['sodek_gb_addon_description'] ) );
            }

            // Save image
            if ( isset( $_POST['sodek_gb_addon_image_id'] ) ) {
                $image_id = absint( $_POST['sodek_gb_addon_image_id'] );
                if ( $image_id > 0 ) {
                    update_post_meta( $post_id, '_sodek_gb_addon_image_id', $image_id );
                } else {
                    delete_post_meta( $post_id, '_sodek_gb_addon_image_id' );
                }
            }
        }

        $all_services_enabled = isset( $_POST['sodek_gb_addon_all_services'] );
        update_post_meta( $post_id, '_sodek_gb_addon_all_services', $all_services_enabled ? 'yes' : 'no' );

        // Only update service associations when the assignment UI was present in the request.
        if ( isset( $_POST['sodek_gb_addon_services_present'] ) ) {
            if ( $all_services_enabled ) {
                update_post_meta( $post_id, '_sodek_gb_addon_services', array() );
            } else {
                update_post_meta(
                    $post_id,
                    '_sodek_gb_addon_services',
                    self::normalize_service_ids( $_POST['sodek_gb_addon_services'] ?? array() )
                );
            }
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
        $new_columns['cb']    = $columns['cb'];
        $new_columns['title'] = __( 'Add-on Name', 'glowbook' );
        $new_columns['sodek_gb_price']    = __( 'Price', 'glowbook' );
        $new_columns['sodek_gb_duration'] = __( 'Extra Time', 'glowbook' );
        $new_columns['sodek_gb_services'] = __( 'Services', 'glowbook' );
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
            case 'sodek_gb_price':
                $price = get_post_meta( $post_id, '_sodek_gb_addon_price', true );
                echo wc_price( $price );
                break;

            case 'sodek_gb_duration':
                $duration = (int) get_post_meta( $post_id, '_sodek_gb_addon_duration', true );
                if ( $duration > 0 ) {
                    echo '+ ' . esc_html( self::format_duration( $duration ) );
                } else {
                    echo '—';
                }
                break;

            case 'sodek_gb_services':
                $all_services = get_post_meta( $post_id, '_sodek_gb_addon_all_services', true );
                $services     = get_post_meta( $post_id, '_sodek_gb_addon_services', true );
                $price        = get_post_meta( $post_id, '_sodek_gb_addon_price', true );
                $duration     = get_post_meta( $post_id, '_sodek_gb_addon_duration', true );

                // Hidden data for quick edit
                printf(
                    '<span class="sodek-gb-addon-data hidden" data-price="%s" data-duration="%s" data-all-services="%s" data-services="%s"></span>',
                    esc_attr( $price ),
                    esc_attr( $duration ),
                    esc_attr( $all_services ),
                    esc_attr( is_array( $services ) ? implode( ',', $services ) : '' )
                );

                if ( 'yes' === $all_services ) {
                    echo '<em>' . esc_html__( 'All services', 'glowbook' ) . '</em>';
                } elseif ( ! empty( $services ) && is_array( $services ) ) {
                    $titles = array();
                    foreach ( array_slice( $services, 0, 3 ) as $service_id ) {
                        $service = get_post( $service_id );
                        if ( $service ) {
                            $titles[] = $service->post_title;
                        }
                    }
                    echo esc_html( implode( ', ', $titles ) );
                    if ( count( $services ) > 3 ) {
                        echo ' <em>+' . ( count( $services ) - 3 ) . ' ' . esc_html__( 'more', 'glowbook' ) . '</em>';
                    }
                } else {
                    echo '<span style="color: #d63638;">' . esc_html__( 'No services selected', 'glowbook' ) . '</span>';
                }
                break;
        }
    }

    /**
     * Quick edit fields.
     *
     * @param string $column_name Column name.
     * @param string $post_type   Post type.
     */
    public static function quick_edit_fields( $column_name, $post_type ) {
        if ( self::POST_TYPE !== $post_type || 'sodek_gb_services' !== $column_name ) {
            return;
        }

        // Get all services
        $services = get_posts( array(
            'post_type'      => 'sodek_gb_service',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        wp_nonce_field( 'sodek_gb_addon_quick_edit', 'sodek_gb_addon_quick_nonce' );
        ?>
        <fieldset class="inline-edit-col-right" style="margin-top: 20px;">
            <div class="inline-edit-col">
                <input type="hidden" name="sodek_gb_addon_services_present" value="1">
                <label class="inline-edit-group">
                    <span class="title"><?php esc_html_e( 'Price', 'glowbook' ); ?></span>
                    <span class="input-text-wrap">
                        <input type="number" name="sodek_gb_addon_price" step="0.01" min="0" class="sodek-gb-qe-price" style="width: 100px;">
                    </span>
                </label>
                <label class="inline-edit-group">
                    <span class="title"><?php esc_html_e( 'Duration', 'glowbook' ); ?></span>
                    <span class="input-text-wrap">
                        <input type="number" name="sodek_gb_addon_duration" step="5" min="0" class="sodek-gb-qe-duration" style="width: 80px;">
                        <span><?php esc_html_e( 'min', 'glowbook' ); ?></span>
                    </span>
                </label>
                <div class="inline-edit-group" style="margin-top: 10px;">
                    <label style="display: block; margin-bottom: 8px;">
                        <input type="checkbox" name="sodek_gb_addon_all_services" value="yes" class="sodek-gb-qe-all-services">
                        <strong><?php esc_html_e( 'All services', 'glowbook' ); ?></strong>
                    </label>
                    <div class="sodek-gb-qe-services-list" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 8px; background: #fff;">
                        <?php foreach ( $services as $service ) : ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="sodek_gb_addon_services[]" value="<?php echo esc_attr( $service->ID ); ?>" class="sodek-gb-qe-service">
                                <?php echo esc_html( $service->post_title ); ?>
                            </label>
                        <?php endforeach; ?>
                        <?php if ( empty( $services ) ) : ?>
                            <em><?php esc_html_e( 'No services found', 'glowbook' ); ?></em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Quick edit JavaScript.
     */
    public static function quick_edit_js() {
        global $current_screen;

        if ( ! $current_screen || self::POST_TYPE !== $current_screen->post_type ) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(function($) {
            var $inlineEdit = inlineEditPost.edit;

            inlineEditPost.edit = function(id) {
                $inlineEdit.apply(this, arguments);

                var postId = 0;
                if (typeof(id) === 'object') {
                    postId = parseInt(this.getId(id));
                }

                if (postId > 0) {
                    var $row = $('#post-' + postId);
                    var $editRow = $('#edit-' + postId);

                    // Get data from row
                    var price = $row.find('.sodek-gb-addon-data').data('price') || '';
                    var duration = $row.find('.sodek-gb-addon-data').data('duration') || '';
                    var allServices = $row.find('.sodek-gb-addon-data').data('all-services') || '';
                    var services = $row.find('.sodek-gb-addon-data').data('services') || '';

                    // Populate fields
                    $editRow.find('.sodek-gb-qe-price').val(price);
                    $editRow.find('.sodek-gb-qe-duration').val(duration);
                    $editRow.find('.sodek-gb-qe-all-services').prop('checked', allServices === 'yes');

                    // Services checkboxes
                    $editRow.find('.sodek-gb-qe-service').prop('checked', false);
                    if (services) {
                        var serviceIds = String(services).split(',');
                        serviceIds.forEach(function(sid) {
                            $editRow.find('.sodek-gb-qe-service[value="' + sid + '"]').prop('checked', true);
                        });
                    }

                    // Toggle services list visibility
                    syncQuickEditServices($editRow, allServices === 'yes');
                }
            };

            // Toggle services list when "All services" is checked
            $(document).on('change', '.sodek-gb-qe-all-services', function() {
                var $editRow = $(this).closest('tr.inline-editor');
                syncQuickEditServices($editRow, $(this).is(':checked'));
            });

            function syncQuickEditServices($editRow, useAllServices) {
                var $servicesList = $editRow.find('.sodek-gb-qe-services-list');
                var $serviceInputs = $editRow.find('.sodek-gb-qe-service');

                $servicesList.toggle(!useAllServices);
                $serviceInputs.prop('disabled', useAllServices);

                if (useAllServices) {
                    $serviceInputs.prop('checked', false);
                }
            }
        });
        </script>
        <?php
    }

    /**
     * Get add-on data.
     *
     * @param int $addon_id Add-on ID.
     * @return array|false
     */
    public static function get_addon( $addon_id ) {
        $post = get_post( $addon_id );

        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return false;
        }

        $image_id       = get_post_meta( $post->ID, '_sodek_gb_addon_image_id', true );
        $image_url      = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
        $image_url_full = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';

        // Get service filtering data
        $all_services = get_post_meta( $post->ID, '_sodek_gb_addon_all_services', true );
        $services     = get_post_meta( $post->ID, '_sodek_gb_addon_services', true );

        return array(
            'id'               => $post->ID,
            'title'            => $post->post_title,
            'price'            => (float) get_post_meta( $post->ID, '_sodek_gb_addon_price', true ),
            'duration'         => (int) get_post_meta( $post->ID, '_sodek_gb_addon_duration', true ),
            'description'      => get_post_meta( $post->ID, '_sodek_gb_addon_description', true ),
            'image_id'         => $image_id ? (int) $image_id : 0,
            'image_url'        => $image_url,
            'image_url_full'   => $image_url_full,
            'for_all_services' => 'yes' === $all_services,
            'service_ids'      => is_array( $services ) ? array_map( 'intval', $services ) : array(),
        );
    }

    /**
     * Get all published add-ons.
     *
     * @return array
     */
    public static function get_all_addons() {
        $addons = array();

        $posts = get_posts( array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        foreach ( $posts as $post ) {
            $addon = self::get_addon( $post->ID );
            if ( $addon ) {
                $addons[] = $addon;
            }
        }

        return $addons;
    }

    /**
     * Get add-ons for a specific service.
     *
     * @param int $service_id Service ID.
     * @return array
     */
    public static function get_addons_for_service( $service_id ) {
        $addons = array();

        $posts = get_posts( array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        foreach ( $posts as $post ) {
            $all_services = get_post_meta( $post->ID, '_sodek_gb_addon_all_services', true );

            if ( 'yes' === $all_services ) {
                $addon = self::get_addon( $post->ID );
                if ( $addon ) {
                    $addons[] = $addon;
                }
                continue;
            }

            $services = get_post_meta( $post->ID, '_sodek_gb_addon_services', true );
            if ( is_array( $services ) && in_array( (int) $service_id, array_map( 'intval', $services ), true ) ) {
                $addon = self::get_addon( $post->ID );
                if ( $addon ) {
                    $addons[] = $addon;
                }
            }
        }

        return $addons;
    }

    /**
     * Calculate total price and duration for selected add-ons.
     *
     * @param array $addon_ids Array of add-on IDs.
     * @return array
     */
    public static function calculate_addons_total( $addon_ids ) {
        $total_price    = 0;
        $total_duration = 0;
        $addon_details  = array();

        foreach ( $addon_ids as $addon_id ) {
            $addon = self::get_addon( $addon_id );
            if ( $addon ) {
                $total_price    += $addon['price'];
                $total_duration += $addon['duration'];
                $addon_details[] = $addon;
            }
        }

        return array(
            'price'    => $total_price,
            'duration' => $total_duration,
            'addons'   => $addon_details,
        );
    }

    /**
     * Calculate deposit for add-ons based on service deposit settings.
     *
     * @param array $addon_ids  Array of add-on IDs.
     * @param int   $service_id Service ID (to get deposit type/percentage).
     * @return float
     */
    public static function calculate_addons_deposit( $addon_ids, $service_id ) {
        $addons_total = self::calculate_addons_total( $addon_ids );
        $addons_price = $addons_total['price'];

        if ( $addons_price <= 0 ) {
            return 0;
        }

        // Get service deposit settings
        $deposit_type  = get_post_meta( $service_id, '_sodek_gb_deposit_type', true ) ?: 'percentage';
        $deposit_value = (float) get_post_meta( $service_id, '_sodek_gb_deposit_value', true );

        // Apply same deposit percentage to add-ons
        if ( 'percentage' === $deposit_type && $deposit_value > 0 ) {
            return round( $addons_price * ( $deposit_value / 100 ), 2 );
        }

        // For fixed deposit, include full add-on price (or could be a setting)
        return $addons_price;
    }

    /**
     * Format add-ons for display.
     *
     * @param array $addon_ids Array of add-on IDs.
     * @return string
     */
    public static function format_addons_display( $addon_ids ) {
        $lines = array();

        foreach ( $addon_ids as $addon_id ) {
            $addon = self::get_addon( $addon_id );
            if ( $addon ) {
                $duration_text = $addon['duration'] > 0 ? self::format_duration( $addon['duration'] ) : '';
                $price_text    = wc_price( $addon['price'] );

                if ( $duration_text ) {
                    $lines[] = sprintf( '%s + %s @ %s', $addon['title'], $duration_text, wp_strip_all_tags( $price_text ) );
                } else {
                    $lines[] = sprintf( '%s @ %s', $addon['title'], wp_strip_all_tags( $price_text ) );
                }
            }
        }

        return implode( "\n", $lines );
    }

    /**
     * Get add-on IDs from booking.
     *
     * @param int $booking_id Booking ID.
     * @return array
     */
    public static function get_booking_addons( $booking_id ) {
        $addon_ids = get_post_meta( $booking_id, '_sodek_gb_addon_ids', true );
        return is_array( $addon_ids ) ? $addon_ids : array();
    }

    /**
     * Save add-ons to booking.
     *
     * @param int   $booking_id Booking ID.
     * @param array $addon_ids  Array of add-on IDs.
     */
    public static function save_booking_addons( $booking_id, $addon_ids ) {
        if ( ! is_array( $addon_ids ) ) {
            $addon_ids = array();
        }

        $addon_ids = array_map( 'absint', $addon_ids );
        $addon_ids = array_filter( $addon_ids );

        update_post_meta( $booking_id, '_sodek_gb_addon_ids', $addon_ids );

        // Also save total for quick reference
        $totals = self::calculate_addons_total( $addon_ids );
        update_post_meta( $booking_id, '_sodek_gb_addons_total_price', $totals['price'] );
        update_post_meta( $booking_id, '_sodek_gb_addons_total_duration', $totals['duration'] );
    }

    /**
     * Validate that add-ons are available for a service.
     *
     * @param array $addon_ids  Array of add-on IDs.
     * @param int   $service_id Service ID.
     * @return array Valid add-on IDs.
     */
    public static function validate_addons_for_service( $addon_ids, $service_id ) {
        $valid_addons     = array();
        $available_addons = self::get_addons_for_service( $service_id );
        $available_ids    = wp_list_pluck( $available_addons, 'id' );

        foreach ( $addon_ids as $addon_id ) {
            if ( in_array( (int) $addon_id, $available_ids, true ) ) {
                $valid_addons[] = (int) $addon_id;
            }
        }

        return $valid_addons;
    }
}
