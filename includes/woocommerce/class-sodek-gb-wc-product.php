<?php
/**
 * WooCommerce custom product type integration.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_WC_Product class.
 */
class Sodek_GB_WC_Product {

    /**
     * Initialize.
     */
    public static function init() {
        // Register product type - use multiple hooks to ensure it's registered
        add_action( 'init', array( __CLASS__, 'register_product_type' ), 5 );
        add_action( 'woocommerce_init', array( __CLASS__, 'register_product_type' ) );
        add_filter( 'product_type_selector', array( __CLASS__, 'add_product_type' ) );

        // Product data tabs
        add_filter( 'woocommerce_product_data_tabs', array( __CLASS__, 'add_product_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( __CLASS__, 'product_tab_content' ) );
        add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_meta' ) );

        // Save custom product type - must run before WooCommerce processes the product (priority 5)
        add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_type' ), 5 );

        // Admin scripts for product type handling
        add_action( 'admin_footer', array( __CLASS__, 'product_type_admin_js' ) );

        // Custom add-to-cart template for bookable_service product type
        // WooCommerce uses action hook: woocommerce_{product_type}_add_to_cart
        add_action( 'woocommerce_bookable_service_add_to_cart', array( __CLASS__, 'add_to_cart_template' ) );
        add_filter( 'woocommerce_locate_template', array( __CLASS__, 'locate_template' ), 10, 3 );

        // Hide regular add to cart for bookable products
        add_filter( 'woocommerce_is_purchasable', array( __CLASS__, 'is_purchasable' ), 10, 2 );

        // Product type class
        add_filter( 'woocommerce_product_class', array( __CLASS__, 'product_class' ), 10, 2 );

        // Custom Add to Cart button text
        add_filter( 'woocommerce_product_single_add_to_cart_text', array( __CLASS__, 'custom_add_to_cart_text' ), 10, 2 );
        add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'custom_add_to_cart_text' ), 10, 2 );

        // Include bookable_service in product type queries
        add_filter( 'woocommerce_product_related_posts_query', array( __CLASS__, 'include_in_related_products' ) );
        add_filter( 'woocommerce_products_widget_query_args', array( __CLASS__, 'include_in_product_widgets' ) );
        add_filter( 'woocommerce_shortcode_products_query', array( __CLASS__, 'include_in_shortcode_query' ), 10, 2 );

        // Make bookable_service products visible in catalog
        add_filter( 'woocommerce_product_is_visible', array( __CLASS__, 'product_is_visible' ), 10, 2 );

        // Support for Elementor WooCommerce widgets
        add_filter( 'elementor/query/query_args', array( __CLASS__, 'elementor_include_product_type' ), 10, 2 );

        // General WooCommerce product query support
        add_filter( 'woocommerce_product_query_tax_query', array( __CLASS__, 'modify_product_query_tax_query' ), 10, 2 );

        // Ensure add to cart URL works correctly
        add_filter( 'woocommerce_loop_add_to_cart_link', array( __CLASS__, 'loop_add_to_cart_link' ), 10, 3 );

        // Custom product gallery for bookable services (controlled by setting)
        // DISABLED: add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'maybe_replace_product_gallery' ), 5 );

        // CSS class injection disabled - theme conflicts
        // add_action( 'wp_body_open', array( __CLASS__, 'inject_bookable_class_script' ) );
        // add_filter( 'woocommerce_post_class', array( __CLASS__, 'add_product_wrapper_class' ), 10, 2 );

        // Redirect bookable services straight to checkout
        add_filter( 'woocommerce_add_to_cart_redirect', array( __CLASS__, 'redirect_to_checkout' ), 10, 2 );

        // Product layout customization - properly remove images for centered/compact layouts
        add_action( 'woocommerce_before_single_product', array( __CLASS__, 'apply_product_layout' ), 5 );

        // Full-width layout for bookable services - DISABLED (causes blank page)
        // add_action( 'woocommerce_before_single_product', array( __CLASS__, 'maybe_start_fullwidth_layout' ), 5 );
        // add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'maybe_modify_product_layout' ), 1 );
    }

    /**
     * Apply product layout based on per-product setting.
     * Uses WooCommerce's proper hooks to remove/modify product images.
     *
     * @see https://woocommerce.github.io/code-reference/hooks/hooks.html
     */
    public static function apply_product_layout() {
        global $product;

        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return;
        }

        // Check if this is a bookable service product
        $is_booking = get_post_meta( $product->get_id(), '_sodek_gb_is_booking_product', true );
        if ( 'yes' !== $is_booking ) {
            return;
        }

        // Get layout setting
        $layout = get_post_meta( $product->get_id(), '_sodek_gb_product_layout', true ) ?: 'default';

        // For centered and compact layouts, remove the product images
        if ( in_array( $layout, array( 'centered', 'compact' ), true ) ) {
            // Remove the product images action (priority 20)
            // This is the WooCommerce-recommended way to hide product images
            remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );

            // Add body class for additional styling
            add_filter( 'body_class', function( $classes ) use ( $layout ) {
                $classes[] = 'sodek-gb-' . $layout . '-layout';
                $classes[] = 'sodek-gb-no-product-image';
                return $classes;
            } );
        }
    }

    /**
     * Inject JavaScript to add body class for bookable products.
     * Uses wp_body_open hook which is safer than body_class filter.
     */
    public static function inject_bookable_class_script() {
        global $post;

        // Only on single product pages
        if ( ! $post || 'product' !== $post->post_type ) {
            return;
        }

        // Check if this is a bookable service product
        $product_type_terms = get_the_terms( $post->ID, 'product_type' );
        $is_bookable = false;

        if ( $product_type_terms && ! is_wp_error( $product_type_terms ) ) {
            foreach ( $product_type_terms as $term ) {
                if ( 'bookable_service' === $term->slug ) {
                    $is_bookable = true;
                    break;
                }
            }
        }

        if ( $is_bookable ) {
            // Inject inline script to add body class immediately
            echo '<script>document.body.classList.add("sodek-gb-bookable-product");</script>';
        }
    }

    /**
     * Add wrapper class to product container.
     * Alternative to body class - adds class directly to product element.
     *
     * @param array      $classes Product classes.
     * @param WC_Product $product Product object.
     * @return array
     */
    public static function add_product_wrapper_class( $classes, $product ) {
        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return $classes;
        }

        if ( 'bookable_service' === $product->get_type() ) {
            $classes[] = 'sodek-gb-bookable-service';
            $classes[] = 'sodek-gb-booking-product';
        }

        return $classes;
    }

    /**
     * Start full-width layout wrapper for bookable services.
     */
    public static function maybe_start_fullwidth_layout() {
        if ( is_admin() ) {
            return;
        }

        // Check if full-width layout is enabled
        if ( ! get_option( 'sodek_gb_fullwidth_layout', 1 ) ) {
            return;
        }

        try {
            global $product;

            if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
                $product = wc_get_product( get_the_ID() );
            }

            if ( $product && is_a( $product, 'WC_Product' ) && 'bookable_service' === $product->get_type() ) {
                // Add wrapper div for full-width layout
                echo '<div class="sodek-gb-fullwidth-product">';

                // Hook to close the wrapper after product
                add_action( 'woocommerce_after_single_product', array( __CLASS__, 'close_fullwidth_layout' ), 99 );
            }
        } catch ( \Throwable $e ) {
            // Silently fail
        }
    }

    /**
     * Close full-width layout wrapper.
     */
    public static function close_fullwidth_layout() {
        echo '</div><!-- .sodek-gb-fullwidth-product -->';
    }

    /**
     * Modify product layout for bookable services - remove sidebar elements.
     */
    public static function maybe_modify_product_layout() {
        if ( is_admin() ) {
            return;
        }

        // Check if full-width layout is enabled
        if ( ! get_option( 'sodek_gb_fullwidth_layout', 1 ) ) {
            return;
        }

        try {
            global $product;

            if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
                $product = wc_get_product( get_the_ID() );
            }

            if ( ! $product || ! is_a( $product, 'WC_Product' ) || 'bookable_service' !== $product->get_type() ) {
                return;
            }

            // Remove default WooCommerce elements we don't need for booking page
            remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
            remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
            remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
            remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
            remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
            remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

            // Remove product tabs and related products for cleaner booking experience
            remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
            remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
            remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

            // Add our custom header with title and service info
            add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'render_booking_header' ), 15 );

        } catch ( \Throwable $e ) {
            // Silently fail
        }
    }

    /**
     * Render custom booking header with title and service info.
     */
    public static function render_booking_header() {
        global $product;

        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return;
        }

        $service_id = get_post_meta( $product->get_id(), '_sodek_gb_service_id', true );
        $service = $service_id ? Sodek_GB_Service::get_service( $service_id ) : null;
        ?>
        <div class="sodek-gb-booking-header">
            <h1 class="sodek-gb-product-title"><?php echo esc_html( $product->get_name() ); ?></h1>
            <?php if ( $product->get_short_description() ) : ?>
                <div class="sodek-gb-product-description">
                    <?php echo wp_kses_post( $product->get_short_description() ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Conditionally replace product gallery for bookable services.
     */
    public static function maybe_replace_product_gallery() {
        // Only run on frontend
        if ( is_admin() ) {
            return;
        }

        // Check if user wants to use theme gallery instead
        if ( get_option( 'sodek_gb_use_theme_gallery', 1 ) ) {
            return; // Let theme handle the gallery
        }

        try {
            global $product;

            // Try to get product if not set
            if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
                $post_id = get_the_ID();
                if ( ! $post_id ) {
                    return;
                }
                $product = wc_get_product( $post_id );
            }

            if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
                return;
            }

            // Check if this is a bookable service product
            if ( 'bookable_service' === $product->get_type() ) {
                // Remove default WooCommerce gallery
                remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
                // Add our custom gallery
                add_action( 'woocommerce_before_single_product_summary', array( __CLASS__, 'show_bookable_product_gallery' ), 20 );
            }
        } catch ( \Throwable $e ) {
            // Silently fail - don't break the page
        }
    }

    /**
     * Display custom product gallery for bookable services.
     */
    public static function show_bookable_product_gallery() {
        wc_get_template(
            'single-product/product-image-bookable.php',
            array(),
            '',
            SODEK_GB_PLUGIN_DIR . 'templates/'
        );
    }

    /**
     * Output the add to cart template for bookable_service products.
     * This is called by WooCommerce's woocommerce_bookable_service_add_to_cart action hook.
     */
    public static function add_to_cart_template() {
        global $product;

        // Load our custom template
        wc_get_template(
            'single-product/add-to-cart/bookable_service.php',
            array( 'product' => $product ),
            '',
            SODEK_GB_PLUGIN_DIR . 'templates/'
        );
    }

    /**
     * Locate custom template for bookable_service product type.
     *
     * @param string $template      Template path.
     * @param string $template_name Template name.
     * @param string $template_path Template path prefix.
     * @return string
     */
    public static function locate_template( $template, $template_name, $template_path ) {
        // Only override the bookable_service add-to-cart template
        if ( 'single-product/add-to-cart/bookable_service.php' === $template_name ) {
            $plugin_template = SODEK_GB_PLUGIN_DIR . 'templates/single-product/add-to-cart/bookable_service.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
        return $template;
    }

    /**
     * Modify WooCommerce product query tax_query to include bookable_service.
     *
     * @param array    $tax_query Tax query.
     * @param WC_Query $query     WooCommerce query object.
     * @return array
     */
    public static function modify_product_query_tax_query( $tax_query, $query ) {
        foreach ( $tax_query as $key => $query_part ) {
            if ( is_array( $query_part ) && isset( $query_part['taxonomy'] ) && 'product_type' === $query_part['taxonomy'] ) {
                if ( isset( $query_part['terms'] ) ) {
                    $terms = (array) $query_part['terms'];
                    if ( ! in_array( 'bookable_service', $terms, true ) ) {
                        $terms[] = 'bookable_service';
                        $tax_query[ $key ]['terms'] = $terms;
                    }
                }
            }
        }

        return $tax_query;
    }

    /**
     * Modify add to cart link for bookable service products in loops.
     *
     * @param string     $html    Add to cart HTML.
     * @param WC_Product $product Product object.
     * @param array      $args    Arguments.
     * @return string
     */
    public static function loop_add_to_cart_link( $html, $product, $args = array() ) {
        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return $html;
        }

        if ( 'bookable_service' === $product->get_type() ) {
            // Build attributes string with compatibility for older WooCommerce
            $attributes_html = '';
            if ( isset( $args['attributes'] ) && is_array( $args['attributes'] ) ) {
                if ( function_exists( 'wc_implode_html_attributes' ) ) {
                    $attributes_html = wc_implode_html_attributes( $args['attributes'] );
                } else {
                    // Fallback for older WooCommerce versions
                    foreach ( $args['attributes'] as $key => $value ) {
                        $attributes_html .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
                    }
                }
            }

            // Link to product page instead of AJAX add to cart
            $html = sprintf(
                '<a href="%s" class="%s"%s>%s</a>',
                esc_url( $product->get_permalink() ),
                esc_attr( isset( $args['class'] ) ? $args['class'] : 'button' ),
                $attributes_html ? ' ' . $attributes_html : '',
                esc_html( $product->add_to_cart_text() )
            );
        }

        return $html;
    }

    /**
     * Include bookable_service in related products query.
     *
     * @param array $query Query args.
     * @return array
     */
    public static function include_in_related_products( $query ) {
        return $query;
    }

    /**
     * Include bookable_service in product widgets query.
     *
     * @param array $query_args Query args.
     * @return array
     */
    public static function include_in_product_widgets( $query_args ) {
        if ( ! isset( $query_args['tax_query'] ) ) {
            $query_args['tax_query'] = array();
        }

        // Remove any product_type restrictions that exclude bookable_service
        if ( isset( $query_args['tax_query'] ) ) {
            foreach ( $query_args['tax_query'] as $key => $tax_query ) {
                if ( is_array( $tax_query ) && isset( $tax_query['taxonomy'] ) && 'product_type' === $tax_query['taxonomy'] ) {
                    if ( isset( $tax_query['terms'] ) && is_array( $tax_query['terms'] ) && ! in_array( 'bookable_service', $tax_query['terms'], true ) ) {
                        $query_args['tax_query'][ $key ]['terms'][] = 'bookable_service';
                    }
                }
            }
        }

        return $query_args;
    }

    /**
     * Include bookable_service in shortcode product queries.
     *
     * @param array  $query_args Query args.
     * @param array  $attributes Shortcode attributes.
     * @return array
     */
    public static function include_in_shortcode_query( $query_args, $attributes ) {
        // Add bookable_service to tax_query if filtering by product_type
        if ( isset( $query_args['tax_query'] ) ) {
            foreach ( $query_args['tax_query'] as $key => $tax_query ) {
                if ( is_array( $tax_query ) && isset( $tax_query['taxonomy'] ) && 'product_type' === $tax_query['taxonomy'] ) {
                    if ( isset( $tax_query['terms'] ) && is_array( $tax_query['terms'] ) && ! in_array( 'bookable_service', $tax_query['terms'], true ) ) {
                        $query_args['tax_query'][ $key ]['terms'][] = 'bookable_service';
                    }
                }
            }
        }

        return $query_args;
    }

    /**
     * Ensure bookable_service products are visible in catalog.
     *
     * @param bool $visible Whether the product is visible.
     * @param int  $product_id Product ID.
     * @return bool
     */
    public static function product_is_visible( $visible, $product_id ) {
        // Use direct term check to avoid infinite loops from wc_get_product()
        $product_type_terms = wp_get_post_terms( $product_id, 'product_type', array( 'fields' => 'slugs' ) );

        if ( ! is_wp_error( $product_type_terms ) && in_array( 'bookable_service', $product_type_terms, true ) ) {
            // Check catalog visibility from post meta directly
            $visibility = get_post_meta( $product_id, '_visibility', true );

            // WooCommerce 3.0+ stores visibility differently
            if ( ! $visibility ) {
                $catalog_visibility = wp_get_post_terms( $product_id, 'product_visibility', array( 'fields' => 'slugs' ) );
                if ( is_wp_error( $catalog_visibility ) || empty( $catalog_visibility ) ) {
                    // Default visibility is visible
                    return true;
                }
                // If hidden from catalog/search, it won't be in catalog_visibility terms
                if ( ! in_array( 'exclude-from-catalog', $catalog_visibility, true ) ) {
                    return true;
                }
            } elseif ( in_array( $visibility, array( 'visible', 'catalog' ), true ) ) {
                return true;
            }
        }

        return $visible;
    }

    /**
     * Include bookable_service in Elementor WooCommerce widget queries.
     *
     * @param array  $query_args Query arguments.
     * @param object $widget     Widget instance.
     * @return array
     */
    public static function elementor_include_product_type( $query_args, $widget ) {
        // Check if this is a products-related query
        if ( ! isset( $query_args['post_type'] ) || 'product' !== $query_args['post_type'] ) {
            return $query_args;
        }

        // Include bookable_service in the product type tax query
        if ( isset( $query_args['tax_query'] ) && is_array( $query_args['tax_query'] ) ) {
            foreach ( $query_args['tax_query'] as $key => $tax_query ) {
                if ( is_array( $tax_query ) && isset( $tax_query['taxonomy'] ) && 'product_type' === $tax_query['taxonomy'] ) {
                    // If terms is an array and doesn't include bookable_service, add it
                    if ( isset( $tax_query['terms'] ) ) {
                        $terms = (array) $tax_query['terms'];
                        if ( ! in_array( 'bookable_service', $terms, true ) ) {
                            $terms[] = 'bookable_service';
                            $query_args['tax_query'][ $key ]['terms'] = $terms;
                        }
                    }
                }
            }
        }

        return $query_args;
    }

    /**
     * Register custom product type.
     */
    public static function register_product_type() {
        // Ensure WooCommerce is loaded
        if ( ! class_exists( 'WC_Product' ) ) {
            return;
        }

        if ( ! class_exists( 'WC_Product_Bookable_Service' ) ) {
            require_once SODEK_GB_PLUGIN_DIR . 'includes/woocommerce/class-wc-product-bookable-service.php';
        }

        // Check if product_type taxonomy exists
        if ( ! taxonomy_exists( 'product_type' ) ) {
            return;
        }

        // Always verify the term exists (quick check) - don't rely solely on transient
        if ( ! term_exists( 'bookable_service', 'product_type' ) ) {
            $result = wp_insert_term( 'bookable_service', 'product_type' );

            // Clear any stale transient if term was just created
            if ( ! is_wp_error( $result ) ) {
                delete_transient( 'sodek_gb_product_type_registered' );
            }
        }
    }

    /**
     * Add product type to selector.
     *
     * @param array $types Product types.
     * @return array
     */
    public static function add_product_type( $types ) {
        $types['bookable_service'] = __( 'Bookable Service', 'glowbook' );
        return $types;
    }

    /**
     * Save custom product type when product is saved.
     *
     * @param int $post_id Product ID.
     */
    public static function save_product_type( $post_id ) {
        // Verify nonce (WooCommerce sets this nonce on product edit)
        if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
            return;
        }

        // Check user capability
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['product-type'] ) && 'bookable_service' === sanitize_text_field( wp_unslash( $_POST['product-type'] ) ) ) {
            // Set the product type term
            wp_set_object_terms( $post_id, 'bookable_service', 'product_type' );

            // Ensure virtual and sold_individually meta are set
            update_post_meta( $post_id, '_virtual', 'yes' );
            update_post_meta( $post_id, '_sold_individually', 'yes' );
        }
    }

    /**
     * Add admin JavaScript for product type handling.
     */
    public static function product_type_admin_js() {
        global $post, $pagenow;

        // Only on product edit/add screens
        if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        if ( ! $post || 'product' !== get_post_type( $post ) ) {
            return;
        }

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Show/hide tabs based on product type
            function showHideBookableServiceOptions() {
                var productType = $('select#product-type').val();

                if ('bookable_service' === productType) {
                    // Show general tab, booking settings tab
                    $('.show_if_bookable_service').show();
                    // Hide shipping, linked products for services
                    $('.show_if_simple:not(.show_if_bookable_service)').show();
                    // Hide inventory for this type (optional)
                    // $('.inventory_options').hide();
                    // $('.shipping_options').hide();

                    // Enable virtual by default
                    $('#_virtual').prop('checked', true).trigger('change');
                } else {
                    $('.show_if_bookable_service').hide();
                }
            }

            // Run on page load
            showHideBookableServiceOptions();

            // Run when product type changes
            $('select#product-type').on('change', function() {
                showHideBookableServiceOptions();
            });

            // Ensure our product type is included in the product type options
            if ($('select#product-type option[value="bookable_service"]').length === 0) {
                $('select#product-type').append(
                    $('<option>', {
                        value: 'bookable_service',
                        text: '<?php echo esc_js( __( 'Bookable Service', 'glowbook' ) ); ?>'
                    })
                );
            }
        });
        </script>
        <?php
    }

    /**
     * Add product data tab.
     *
     * @param array $tabs Tabs.
     * @return array
     */
    public static function add_product_tab( $tabs ) {
        $tabs['sodek_gb_booking'] = array(
            'label'    => __( 'Booking Settings', 'glowbook' ),
            'target'   => 'sodek_gb_booking_data',
            'class'    => array( 'show_if_bookable_service', 'show_if_simple' ),
            'priority' => 25,
        );
        return $tabs;
    }

    /**
     * Product tab content.
     */
    public static function product_tab_content() {
        global $post;

        $service_id = get_post_meta( $post->ID, '_sodek_gb_service_id', true );
        $services = Sodek_GB_Service::get_all_services();
        $override_deposit = get_post_meta( $post->ID, '_sodek_gb_override_deposit', true );
        $deposit_type = get_post_meta( $post->ID, '_sodek_gb_product_deposit_type', true ) ?: 'percentage';
        $deposit_value = get_post_meta( $post->ID, '_sodek_gb_product_deposit_value', true );
        ?>
        <div id="sodek_gb_booking_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <p class="form-field">
                    <label for="_sodek_gb_service_id"><?php esc_html_e( 'Linked Service', 'glowbook' ); ?></label>
                    <select id="_sodek_gb_service_id" name="_sodek_gb_service_id" class="select short">
                        <option value=""><?php esc_html_e( 'Select a service', 'glowbook' ); ?></option>
                        <?php foreach ( $services as $service ) : ?>
                            <option value="<?php echo esc_attr( $service['id'] ); ?>" <?php selected( $service_id, $service['id'] ); ?>>
                                <?php echo esc_html( $service['title'] ); ?> - <?php echo wc_price( $service['price'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
                <p class="description" style="padding-left: 150px;">
                    <?php esc_html_e( 'Link this product to a booking service. The deposit amount will be used as the product price.', 'glowbook' ); ?>
                </p>

                <?php
                woocommerce_wp_checkbox( array(
                    'id'          => '_sodek_gb_is_booking_product',
                    'label'       => __( 'Enable Booking', 'glowbook' ),
                    'description' => __( 'Customers must select a date/time before adding to cart.', 'glowbook' ),
                ) );
                ?>
            </div>

            <div class="options_group">
                <h4 style="padding-left: 12px;"><?php esc_html_e( 'Deposit Override', 'glowbook' ); ?></h4>

                <?php
                woocommerce_wp_checkbox( array(
                    'id'          => '_sodek_gb_override_deposit',
                    'label'       => __( 'Override Service Deposit', 'glowbook' ),
                    'description' => __( 'Use a custom deposit amount for this product instead of the linked service deposit.', 'glowbook' ),
                ) );

                woocommerce_wp_select( array(
                    'id'          => '_sodek_gb_product_deposit_type',
                    'label'       => __( 'Deposit Type', 'glowbook' ),
                    'options'     => array(
                        'percentage' => __( 'Percentage of service price', 'glowbook' ),
                        'flat'       => __( 'Flat amount', 'glowbook' ),
                    ),
                    'value'       => $deposit_type,
                    'wrapper_class' => 'sodek-gb-deposit-override-field',
                ) );

                woocommerce_wp_text_input( array(
                    'id'          => '_sodek_gb_product_deposit_value',
                    'label'       => __( 'Deposit Value', 'glowbook' ),
                    'description' => __( 'Enter percentage (e.g., 50) or flat amount depending on type selected.', 'glowbook' ),
                    'desc_tip'    => true,
                    'type'        => 'number',
                    'custom_attributes' => array(
                        'step' => '0.01',
                        'min'  => '0',
                    ),
                    'value'       => $deposit_value,
                    'wrapper_class' => 'sodek-gb-deposit-override-field',
                ) );
                ?>

                <div class="sodek-gb-deposit-preview-field" style="padding-left: 150px; margin-bottom: 15px;">
                    <p id="sodek-gb-product-deposit-preview" style="padding: 8px; background: #f0f0f1; border-left: 4px solid #2271b1; display: none;"></p>
                </div>
            </div>

            <div class="options_group">
                <h4 style="padding-left: 12px;"><?php esc_html_e( 'Button Text Override', 'glowbook' ); ?></h4>
                <p class="description" style="padding-left: 12px; margin-bottom: 10px;">
                    <?php esc_html_e( 'Override the global "Add to Cart" button text for this product only.', 'glowbook' ); ?>
                </p>

                <?php
                $button_text_override = get_post_meta( $post->ID, '_sodek_gb_button_text_override', true );

                woocommerce_wp_select( array(
                    'id'          => '_sodek_gb_button_text_override',
                    'label'       => __( 'Button Text', 'glowbook' ),
                    'options'     => array(
                        ''                 => __( 'Use Global Setting', 'glowbook' ),
                        'book_now'         => __( 'Book Now', 'glowbook' ),
                        'book_appointment' => __( 'Book Appointment', 'glowbook' ),
                        'schedule'         => __( 'Schedule Now', 'glowbook' ),
                        'custom'           => __( 'Custom Text', 'glowbook' ),
                    ),
                    'value'       => $button_text_override,
                ) );

                $custom_button_text = get_post_meta( $post->ID, '_sodek_gb_custom_button_text', true );

                woocommerce_wp_text_input( array(
                    'id'          => '_sodek_gb_custom_button_text',
                    'label'       => __( 'Custom Text', 'glowbook' ),
                    'placeholder' => __( 'e.g., Reserve Your Spot', 'glowbook' ),
                    'value'       => $custom_button_text,
                    'wrapper_class' => 'sodek-gb-custom-button-text-field',
                ) );
                ?>

                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    function toggleCustomButtonTextField() {
                        var val = $('#_sodek_gb_button_text_override').val();
                        if (val === 'custom') {
                            $('.sodek-gb-custom-button-text-field').show();
                        } else {
                            $('.sodek-gb-custom-button-text-field').hide();
                        }
                    }

                    toggleCustomButtonTextField();
                    $('#_sodek_gb_button_text_override').on('change', toggleCustomButtonTextField);
                });
                </script>
            </div>

            <div class="options_group">
                <h4 style="padding-left: 12px;"><?php esc_html_e( 'Display Settings', 'glowbook' ); ?></h4>
                <p class="description" style="padding-left: 12px; margin-bottom: 10px;">
                    <?php esc_html_e( 'Override global display settings for this product.', 'glowbook' ); ?>
                </p>

                <?php
                $show_duration_override = get_post_meta( $post->ID, '_sodek_gb_show_duration_override', true );

                woocommerce_wp_select( array(
                    'id'          => '_sodek_gb_show_duration_override',
                    'label'       => __( 'Show Duration', 'glowbook' ),
                    'options'     => array(
                        ''    => __( 'Use Global Setting', 'glowbook' ),
                        'yes' => __( 'Yes - Show duration', 'glowbook' ),
                        'no'  => __( 'No - Hide duration', 'glowbook' ),
                    ),
                    'value'       => $show_duration_override,
                    'desc_tip'    => true,
                    'description' => __( 'Override the global "Display service duration" setting for this product.', 'glowbook' ),
                ) );
                ?>
            </div>

            <div class="options_group">
                <h4 style="padding-left: 12px;"><?php esc_html_e( 'Product Layout', 'glowbook' ); ?></h4>
                <p class="description" style="padding-left: 12px; margin-bottom: 10px;">
                    <?php esc_html_e( 'Choose how this product page should be displayed.', 'glowbook' ); ?>
                </p>

                <?php
                $product_layout = get_post_meta( $post->ID, '_sodek_gb_product_layout', true ) ?: 'default';

                woocommerce_wp_select( array(
                    'id'          => '_sodek_gb_product_layout',
                    'label'       => __( 'Layout Style', 'glowbook' ),
                    'options'     => array(
                        'default'  => __( 'Default (Image + Form)', 'glowbook' ),
                        'centered' => __( 'Centered (No Image - Best for services without photos)', 'glowbook' ),
                        'compact'  => __( 'Compact Card (Minimal, form focused)', 'glowbook' ),
                    ),
                    'value'       => $product_layout,
                    'desc_tip'    => true,
                    'description' => __( 'Centered layout is recommended for services without product images.', 'glowbook' ),
                ) );
                ?>

                <div class="sodek-gb-layout-preview" style="padding-left: 150px; margin-bottom: 15px;">
                    <div id="sodek-gb-layout-preview-box" style="padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                        <strong><?php esc_html_e( 'Preview:', 'glowbook' ); ?></strong>
                        <p class="sodek-gb-layout-desc" style="margin: 5px 0 0; font-style: italic; color: #666;"></p>
                    </div>
                </div>

                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    var layoutDescriptions = {
                        'default': '<?php echo esc_js( __( 'Standard WooCommerce layout with product image on left and booking form on right.', 'glowbook' ) ); ?>',
                        'centered': '<?php echo esc_js( __( 'Clean centered layout - booking form is prominent, no image area. Perfect for services without photos.', 'glowbook' ) ); ?>',
                        'compact': '<?php echo esc_js( __( 'Minimal card-style layout with tight spacing. Great for simple services.', 'glowbook' ) ); ?>'
                    };

                    function updateLayoutPreview() {
                        var layout = $('#_sodek_gb_product_layout').val();
                        $('.sodek-gb-layout-desc').text(layoutDescriptions[layout] || '');
                    }

                    updateLayoutPreview();
                    $('#_sodek_gb_product_layout').on('change', updateLayoutPreview);
                });
                </script>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function toggleDepositFields() {
                var isOverride = $('#_sodek_gb_override_deposit').is(':checked');
                $('.sodek-gb-deposit-override-field').toggle(isOverride);
                if (isOverride) {
                    updateDepositPreview();
                } else {
                    $('#sodek-gb-product-deposit-preview').hide();
                }
            }

            function updateDepositPreview() {
                var serviceId = $('#_sodek_gb_service_id').val();
                var depositType = $('#_sodek_gb_product_deposit_type').val();
                var depositValue = parseFloat($('#_sodek_gb_product_deposit_value').val()) || 0;
                var isOverride = $('#_sodek_gb_override_deposit').is(':checked');

                if (!isOverride || !serviceId || depositValue <= 0) {
                    $('#sodek-gb-product-deposit-preview').hide();
                    return;
                }

                // Get service price from selected option text
                var selectedOption = $('#_sodek_gb_service_id option:selected').text();
                var priceMatch = selectedOption.match(/[\d,]+\.?\d*/);
                if (!priceMatch) {
                    $('#sodek-gb-product-deposit-preview').hide();
                    return;
                }

                var servicePrice = parseFloat(priceMatch[0].replace(/,/g, ''));
                var depositAmount;

                if (depositType === 'percentage') {
                    depositAmount = (servicePrice * depositValue) / 100;
                    $('#sodek-gb-product-deposit-preview').html(
                        '<strong><?php esc_html_e( 'Deposit Preview:', 'glowbook' ); ?></strong> ' +
                        depositValue + '% of ' + '<?php echo get_woocommerce_currency_symbol(); ?>' + servicePrice.toLocaleString() +
                        ' = <strong><?php echo get_woocommerce_currency_symbol(); ?>' + depositAmount.toLocaleString() + '</strong>'
                    ).show();
                } else {
                    $('#sodek-gb-product-deposit-preview').html(
                        '<strong><?php esc_html_e( 'Deposit Preview:', 'glowbook' ); ?></strong> ' +
                        '<strong><?php echo get_woocommerce_currency_symbol(); ?>' + depositValue.toLocaleString() + '</strong> (flat amount)'
                    ).show();
                }
            }

            // Initial state
            toggleDepositFields();

            // Events
            $('#_sodek_gb_override_deposit').on('change', toggleDepositFields);
            $('#_sodek_gb_service_id, #_sodek_gb_product_deposit_type, #_sodek_gb_product_deposit_value').on('change input', updateDepositPreview);
        });
        </script>
        <?php
    }

    /**
     * Save product meta.
     *
     * @param int $post_id Product ID.
     */
    public static function save_product_meta( $post_id ) {
        // Verify nonce (WooCommerce sets this nonce on product edit)
        if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
            return;
        }

        // Check user capability
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Check if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        $service_id = isset( $_POST['_sodek_gb_service_id'] ) ? absint( $_POST['_sodek_gb_service_id'] ) : 0;
        $is_booking = isset( $_POST['_sodek_gb_is_booking_product'] ) ? 'yes' : 'no';
        $override_deposit = isset( $_POST['_sodek_gb_override_deposit'] ) ? 'yes' : 'no';
        $deposit_type = isset( $_POST['_sodek_gb_product_deposit_type'] ) ? sanitize_text_field( wp_unslash( $_POST['_sodek_gb_product_deposit_type'] ) ) : 'percentage';
        $deposit_value = isset( $_POST['_sodek_gb_product_deposit_value'] ) ? floatval( $_POST['_sodek_gb_product_deposit_value'] ) : 0;

        // Button text fields
        $button_text_override = isset( $_POST['_sodek_gb_button_text_override'] ) ? sanitize_text_field( wp_unslash( $_POST['_sodek_gb_button_text_override'] ) ) : '';
        $custom_button_text = isset( $_POST['_sodek_gb_custom_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['_sodek_gb_custom_button_text'] ) ) : '';

        // Product layout
        $product_layout = isset( $_POST['_sodek_gb_product_layout'] ) ? sanitize_text_field( wp_unslash( $_POST['_sodek_gb_product_layout'] ) ) : 'default';

        // Display settings override
        $show_duration_override = isset( $_POST['_sodek_gb_show_duration_override'] ) ? sanitize_text_field( wp_unslash( $_POST['_sodek_gb_show_duration_override'] ) ) : '';

        update_post_meta( $post_id, '_sodek_gb_service_id', $service_id );
        update_post_meta( $post_id, '_sodek_gb_is_booking_product', $is_booking );
        update_post_meta( $post_id, '_sodek_gb_override_deposit', $override_deposit );
        update_post_meta( $post_id, '_sodek_gb_product_deposit_type', $deposit_type );
        update_post_meta( $post_id, '_sodek_gb_product_deposit_value', $deposit_value );
        update_post_meta( $post_id, '_sodek_gb_button_text_override', $button_text_override );
        update_post_meta( $post_id, '_sodek_gb_custom_button_text', $custom_button_text );
        update_post_meta( $post_id, '_sodek_gb_product_layout', $product_layout );
        update_post_meta( $post_id, '_sodek_gb_show_duration_override', $show_duration_override );

        // Update price to deposit amount
        if ( $service_id ) {
            $deposit = self::calculate_product_deposit( $post_id, $service_id );
            update_post_meta( $post_id, '_price', $deposit );
            update_post_meta( $post_id, '_regular_price', $deposit );
        }
    }

    /**
     * Custom Add to Cart button text for GlowBook products.
     *
     * @param string     $text    Button text.
     * @param WC_Product $product Product object.
     * @return string
     */
    public static function custom_add_to_cart_text( $text, $product = null ) {
        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return $text;
        }

        $product_id = $product->get_id();
        $service_id = get_post_meta( $product_id, '_sodek_gb_service_id', true );

        // Only apply to products linked to a GlowBook service
        if ( ! $service_id ) {
            return $text;
        }

        // Check for per-product override first
        $button_override = get_post_meta( $product_id, '_sodek_gb_button_text_override', true );

        if ( $button_override ) {
            return self::get_button_text_by_type( $button_override, $product_id );
        }

        // Fall back to global setting
        $global_type = get_option( 'sodek_gb_button_text_type', 'default' );

        if ( 'default' === $global_type ) {
            return $text; // Use WooCommerce default
        }

        return self::get_button_text_by_type( $global_type );
    }

    /**
     * Get button text by type.
     *
     * @param string $type       Button text type.
     * @param int    $product_id Product ID (for custom text lookup).
     * @return string
     */
    private static function get_button_text_by_type( $type, $product_id = 0 ) {
        switch ( $type ) {
            case 'book_now':
                return __( 'Book Now', 'glowbook' );

            case 'book_appointment':
                return __( 'Book Appointment', 'glowbook' );

            case 'schedule':
                return __( 'Schedule Now', 'glowbook' );

            case 'custom':
                if ( $product_id ) {
                    $custom_text = get_post_meta( $product_id, '_sodek_gb_custom_button_text', true );
                    if ( $custom_text ) {
                        return $custom_text;
                    }
                }
                // Fall back to global custom text
                $global_custom = get_option( 'sodek_gb_button_text_custom', '' );
                return $global_custom ? $global_custom : __( 'Book Now', 'glowbook' );

            default:
                return __( 'Add to cart', 'glowbook' );
        }
    }

    /**
     * Calculate product deposit amount.
     *
     * @param int $product_id Product ID.
     * @param int $service_id Service ID (optional, will be fetched if not provided).
     * @return float
     */
    public static function calculate_product_deposit( $product_id, $service_id = null ) {
        if ( ! $service_id ) {
            $service_id = get_post_meta( $product_id, '_sodek_gb_service_id', true );
        }

        if ( ! $service_id ) {
            return 0;
        }

        $service = Sodek_GB_Service::get_service( $service_id );
        if ( ! $service ) {
            return 0;
        }

        $override_deposit = get_post_meta( $product_id, '_sodek_gb_override_deposit', true );

        // If override is enabled, calculate based on product settings
        if ( 'yes' === $override_deposit ) {
            $deposit_type = get_post_meta( $product_id, '_sodek_gb_product_deposit_type', true ) ?: 'percentage';
            $deposit_value = floatval( get_post_meta( $product_id, '_sodek_gb_product_deposit_value', true ) );

            if ( 'percentage' === $deposit_type ) {
                return ( $service['price'] * $deposit_value ) / 100;
            } else {
                return $deposit_value;
            }
        }

        // Otherwise use service deposit
        return Sodek_GB_Service::calculate_deposit( $service_id );
    }

    /**
     * Get full service data with product deposit override applied.
     *
     * @param int $product_id Product ID.
     * @return array|null
     */
    public static function get_service_with_product_deposit( $product_id ) {
        $service_id = get_post_meta( $product_id, '_sodek_gb_service_id', true );
        if ( ! $service_id ) {
            return null;
        }

        $service = Sodek_GB_Service::get_service( $service_id );
        if ( ! $service ) {
            return null;
        }

        // Apply product deposit override
        $service['deposit_amount'] = self::calculate_product_deposit( $product_id, $service_id );

        return $service;
    }

    /**
     * Show booking form on product page.
     */
    public static function booking_form_before_cart() {
        global $product;

        if ( ! $product ) {
            return;
        }

        $is_booking = get_post_meta( $product->get_id(), '_sodek_gb_is_booking_product', true );
        $service_id = get_post_meta( $product->get_id(), '_sodek_gb_service_id', true );

        if ( 'yes' !== $is_booking || ! $service_id ) {
            return;
        }

        // Get service with product deposit override applied
        $service = self::get_service_with_product_deposit( $product->get_id() );
        if ( ! $service ) {
            return;
        }

        // Calculate balance
        $balance = $service['price'] - $service['deposit_amount'];

        // Get add-ons for this service
        $addons = Sodek_GB_Addon::get_addons_for_service( $service_id );

        // Get deposit settings
        $deposit_type = get_post_meta( $service_id, '_sodek_gb_deposit_type', true ) ?: 'percentage';
        $deposit_value = get_post_meta( $service_id, '_sodek_gb_deposit_value', true ) ?: 50;

        // Output booking form
        ?>
        <div class="sodek-gb-product-booking-form" data-service-id="<?php echo esc_attr( $service_id ); ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
            <input type="hidden" name="sodek_gb_service_id" value="<?php echo esc_attr( $service_id ); ?>">
            <input type="hidden" name="sodek_gb_booking_date" id="sodek_gb_booking_date" value="">
            <input type="hidden" name="sodek_gb_booking_time" id="sodek_gb_booking_time" value="">

            <div class="sodek-gb-service-info">
                <p><strong><?php esc_html_e( 'Duration:', 'glowbook' ); ?></strong> <?php echo esc_html( $service['duration'] ); ?> <?php esc_html_e( 'minutes', 'glowbook' ); ?></p>
                <div class="sodek-gb-price-info">
                    <p>
                        <strong><?php esc_html_e( 'Full Price:', 'glowbook' ); ?></strong>
                        <span><?php echo wc_price( $service['price'] ); ?></span>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Minimum Deposit:', 'glowbook' ); ?></strong>
                        <span><?php echo wc_price( $service['deposit_amount'] ); ?></span>
                    </p>
                    <?php if ( $balance > 0 ) : ?>
                    <p class="sodek-gb-balance-info">
                        <strong><?php esc_html_e( 'Balance due at appointment:', 'glowbook' ); ?></strong>
                        <span><?php echo wc_price( $balance ); ?></span>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ( ! empty( $addons ) ) : ?>
            <fieldset class="sodek-gb-addons-section">
                <legend><?php esc_html_e( 'Enhance Your Service (Optional)', 'glowbook' ); ?></legend>
                <p class="description"><?php esc_html_e( 'Select any add-ons you\'d like to include with your appointment.', 'glowbook' ); ?></p>
                <div class="sodek-gb-addons-list">
                    <?php foreach ( $addons as $addon ) : ?>
                    <label class="sodek-gb-addon-item <?php echo ! empty( $addon['image_url'] ) ? 'has-image' : ''; ?>">
                        <input type="checkbox" name="sodek_gb_addon_ids[]" value="<?php echo esc_attr( $addon['id'] ); ?>"
                               data-price="<?php echo esc_attr( $addon['price'] ); ?>"
                               data-duration="<?php echo esc_attr( $addon['duration'] ); ?>">
                        <?php if ( ! empty( $addon['image_url'] ) ) : ?>
                        <span class="sodek-gb-addon-image">
                            <img src="<?php echo esc_url( $addon['image_url'] ); ?>" alt="<?php echo esc_attr( $addon['title'] ); ?>">
                        </span>
                        <?php endif; ?>
                        <span class="sodek-gb-addon-info">
                            <span class="sodek-gb-addon-name"><?php echo esc_html( $addon['title'] ); ?></span>
                            <?php if ( $addon['description'] ) : ?>
                                <span class="sodek-gb-addon-desc"><?php echo esc_html( $addon['description'] ); ?></span>
                            <?php endif; ?>
                            <span class="sodek-gb-addon-meta">
                                <span class="sodek-gb-addon-price">+ <?php echo wc_price( $addon['price'] ); ?></span>
                                <?php if ( $addon['duration'] > 0 ) : ?>
                                    <span class="sodek-gb-addon-duration">+ <?php echo esc_html( $addon['duration'] ); ?> <?php esc_html_e( 'min', 'glowbook' ); ?></span>
                                <?php endif; ?>
                            </span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="sodek-gb-addons-total" style="display: none;">
                    <strong><?php esc_html_e( 'Add-ons Total:', 'glowbook' ); ?></strong>
                    <span class="sodek-gb-addons-total-price"></span>
                    <span class="sodek-gb-addons-total-duration"></span>
                </div>
            </fieldset>
            <?php endif; ?>

            <fieldset class="sodek-gb-date-picker-section">
                <legend class="screen-reader-text"><?php esc_html_e( 'Step 1: Select a date', 'glowbook' ); ?></legend>
                <label id="sodek-gb-date-label"><?php esc_html_e( 'Select Date:', 'glowbook' ); ?></label>
                <div id="sodek-gb-calendar-inline" class="sodek-gb-calendar" role="application" aria-labelledby="sodek-gb-date-label">
                    <div class="sodek-gb-loading" aria-live="polite"><?php esc_html_e( 'Loading calendar...', 'glowbook' ); ?></div>
                </div>
            </fieldset>

            <fieldset class="sodek-gb-time-slots-container" style="display: none;" aria-hidden="true">
                <legend class="screen-reader-text"><?php esc_html_e( 'Step 2: Select a time', 'glowbook' ); ?></legend>
                <label id="sodek-gb-time-label"><?php esc_html_e( 'Select Time:', 'glowbook' ); ?></label>
                <div id="sodek-gb-time-slots" class="sodek-gb-time-slots" role="listbox" aria-labelledby="sodek-gb-time-label"></div>
            </fieldset>

            <div class="sodek-gb-notes-field" style="display: none;" aria-hidden="true">
                <label for="sodek_gb_booking_notes"><?php esc_html_e( 'Special Requests / Notes', 'glowbook' ); ?></label>
                <textarea name="sodek_gb_booking_notes" id="sodek_gb_booking_notes" rows="3" placeholder="<?php esc_attr_e( 'Any special requests or notes for your appointment...', 'glowbook' ); ?>" aria-describedby="sodek-gb-notes-description"></textarea>
                <p class="description" id="sodek-gb-notes-description"><?php esc_html_e( 'Optional: Share any relevant details or preferences for your appointment.', 'glowbook' ); ?></p>
            </div>

            <div class="sodek-gb-booking-summary" style="display: none;" aria-hidden="true" role="status" aria-live="polite">
                <h4><?php esc_html_e( 'Your Appointment', 'glowbook' ); ?></h4>
                <p>
                    <span class="sodek-gb-summary-date"></span> <?php esc_html_e( 'at', 'glowbook' ); ?>
                    <span class="sodek-gb-summary-time"></span>
                </p>
                <p class="sodek-gb-summary-duration">
                    <?php esc_html_e( 'Duration:', 'glowbook' ); ?>
                    <span class="sodek-gb-summary-duration-value"><?php echo esc_html( $service['duration'] ); ?></span> <?php esc_html_e( 'min', 'glowbook' ); ?>
                </p>
                <div class="sodek-gb-summary-addons" style="display: none;">
                    <p><strong><?php esc_html_e( 'Add-ons:', 'glowbook' ); ?></strong></p>
                    <ul class="sodek-gb-summary-addons-list"></ul>
                </div>
                <div class="sodek-gb-summary-pricing">
                    <p>
                        <?php esc_html_e( 'Service Price:', 'glowbook' ); ?>
                        <span><?php echo wc_price( $service['price'] ); ?></span>
                    </p>
                    <p class="sodek-gb-summary-addons-price" style="display: none;">
                        <?php esc_html_e( 'Add-ons:', 'glowbook' ); ?>
                        <span class="sodek-gb-summary-addons-price-value"></span>
                    </p>
                    <p class="sodek-gb-summary-total">
                        <strong><?php esc_html_e( 'Total:', 'glowbook' ); ?></strong>
                        <strong class="sodek-gb-summary-total-value"
                                data-base-price="<?php echo esc_attr( $service['price'] ); ?>">
                            <?php echo wc_price( $service['price'] ); ?>
                        </strong>
                    </p>
                </div>

                <!-- Flexible Deposit Section -->
                <div class="sodek-gb-deposit-section">
                    <h5><?php esc_html_e( 'Choose Your Deposit Amount', 'glowbook' ); ?></h5>
                    <p class="description"><?php esc_html_e( 'Pay the minimum deposit or more to reduce your balance at the appointment.', 'glowbook' ); ?></p>

                    <div class="sodek-gb-deposit-input-wrapper">
                        <label for="sodek_gb_custom_deposit" class="screen-reader-text"><?php esc_html_e( 'Deposit amount', 'glowbook' ); ?></label>
                        <span class="sodek-gb-currency-symbol"><?php echo esc_html( get_woocommerce_currency_symbol() ); ?></span>
                        <input type="number"
                               id="sodek_gb_custom_deposit"
                               name="sodek_gb_custom_deposit"
                               class="sodek-gb-deposit-input"
                               min="<?php echo esc_attr( $service['deposit_amount'] ); ?>"
                               max="<?php echo esc_attr( $service['price'] ); ?>"
                               value="<?php echo esc_attr( $service['deposit_amount'] ); ?>"
                               step="0.01"
                               data-min-deposit="<?php echo esc_attr( $service['deposit_amount'] ); ?>"
                               data-base-price="<?php echo esc_attr( $service['price'] ); ?>"
                               data-deposit-type="<?php echo esc_attr( $deposit_type ); ?>"
                               data-deposit-value="<?php echo esc_attr( $deposit_value ); ?>"
                               aria-describedby="sodek-gb-deposit-range-info">
                        <span id="sodek-gb-deposit-range-info" class="sodek-gb-deposit-range-info">
                            <?php
                            printf(
                                /* translators: 1: minimum deposit amount, 2: maximum (full price) amount */
                                esc_html__( 'Min: %1$s — Full: %2$s', 'glowbook' ),
                                wc_price( $service['deposit_amount'] ),
                                wc_price( $service['price'] )
                            );
                            ?>
                        </span>
                    </div>

                    <div class="sodek-gb-deposit-quick-options">
                        <button type="button" class="sodek-gb-deposit-option" data-amount="min">
                            <?php esc_html_e( 'Minimum', 'glowbook' ); ?>
                        </button>
                        <button type="button" class="sodek-gb-deposit-option" data-amount="50">
                            <?php esc_html_e( '50%', 'glowbook' ); ?>
                        </button>
                        <button type="button" class="sodek-gb-deposit-option" data-amount="75">
                            <?php esc_html_e( '75%', 'glowbook' ); ?>
                        </button>
                        <button type="button" class="sodek-gb-deposit-option" data-amount="full">
                            <?php esc_html_e( 'Pay in Full', 'glowbook' ); ?>
                        </button>
                    </div>

                    <div class="sodek-gb-deposit-summary-box">
                        <div class="sodek-gb-deposit-paying">
                            <span><?php esc_html_e( 'Paying Now:', 'glowbook' ); ?></span>
                            <strong class="sodek-gb-chosen-deposit"><?php echo wc_price( $service['deposit_amount'] ); ?></strong>
                        </div>
                        <div class="sodek-gb-deposit-remaining">
                            <span><?php esc_html_e( 'Balance at Appointment:', 'glowbook' ); ?></span>
                            <strong class="sodek-gb-remaining-balance"><?php echo wc_price( $balance ); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Redirect bookable services straight to checkout.
     *
     * @param string $url     Redirect URL.
     * @param mixed  $product Product added (WC_Product object or null).
     * @return string
     */
    public static function redirect_to_checkout( $url, $product = null ) {
        // Check if we just added a bookable service
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Just checking product type
        if ( isset( $_POST['sodek_gb_service_id'] ) && ! empty( $_POST['sodek_gb_service_id'] ) ) {
            return wc_get_checkout_url();
        }

        return $url;
    }

    /**
     * Check if product is purchasable (requires booking selection).
     *
     * @param bool       $purchasable Is purchasable.
     * @param WC_Product $product     Product.
     * @return bool
     */
    public static function is_purchasable( $purchasable, $product ) {
        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return $purchasable;
        }

        $is_booking = get_post_meta( $product->get_id(), '_sodek_gb_is_booking_product', true );

        // Bookable products are purchasable but require JS validation
        if ( 'yes' === $is_booking ) {
            return true;
        }

        return $purchasable;
    }

    /**
     * Get product class.
     *
     * @param string $classname  Class name.
     * @param string $product_type Product type.
     * @return string
     */
    public static function product_class( $classname, $product_type ) {
        if ( 'bookable_service' === $product_type ) {
            // Ensure the class is loaded
            if ( ! class_exists( 'WC_Product_Bookable_Service' ) ) {
                require_once SODEK_GB_PLUGIN_DIR . 'includes/woocommerce/class-wc-product-bookable-service.php';
            }
            return 'WC_Product_Bookable_Service';
        }
        return $classname;
    }
}
