<?php
/**
 * Elementor Integration.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Elementor class.
 */
class Sodek_GB_Elementor {

    /**
     * Minimum Elementor version.
     */
    const MINIMUM_ELEMENTOR_VERSION = '3.0.0';

    /**
     * Widget category slug.
     */
    const CATEGORY_SLUG = 'glowbook';

    /**
     * Initialize.
     */
    public static function init() {
        // Check if Elementor is active
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        // Check Elementor version
        if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'admin_notice_minimum_elementor_version' ) );
            return;
        }

        // Register widget category
        add_action( 'elementor/elements/categories_registered', array( __CLASS__, 'register_category' ) );

        // Register widgets
        add_action( 'elementor/widgets/register', array( __CLASS__, 'register_widgets' ) );

        // Register styles
        add_action( 'elementor/frontend/after_enqueue_styles', array( __CLASS__, 'enqueue_styles' ) );

        // Editor scripts
        add_action( 'elementor/editor/after_enqueue_scripts', array( __CLASS__, 'editor_scripts' ) );
    }

    /**
     * Admin notice for minimum Elementor version.
     */
    public static function admin_notice_minimum_elementor_version() {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <?php
                printf(
                    /* translators: 1: Plugin name, 2: Elementor, 3: Minimum version */
                    esc_html__( '%1$s requires %2$s version %3$s or greater.', 'glowbook' ),
                    '<strong>GlowBook</strong>',
                    '<strong>Elementor</strong>',
                    self::MINIMUM_ELEMENTOR_VERSION
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Register widget category.
     *
     * @param \Elementor\Elements_Manager $elements_manager Elements manager.
     */
    public static function register_category( $elements_manager ) {
        $elements_manager->add_category(
            self::CATEGORY_SLUG,
            array(
                'title' => __( 'GlowBook', 'glowbook' ),
                'icon'  => 'fa fa-calendar-check',
            )
        );
    }

    /**
     * Register widgets.
     *
     * @param \Elementor\Widgets_Manager $widgets_manager Widgets manager.
     */
    public static function register_widgets( $widgets_manager ) {
        // Include widget files
        require_once SODEK_GB_PLUGIN_DIR . 'includes/integrations/elementor/widgets/class-sodek-gb-widget-booking-form.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/integrations/elementor/widgets/class-sodek-gb-widget-services-grid.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/integrations/elementor/widgets/class-sodek-gb-widget-service-card.php';

        // Register widgets
        $widgets_manager->register( new Sodek_GB_Widget_Booking_Form() );
        $widgets_manager->register( new Sodek_GB_Widget_Services_Grid() );
        $widgets_manager->register( new Sodek_GB_Widget_Service_Card() );
    }

    /**
     * Enqueue frontend styles.
     */
    public static function enqueue_styles() {
        wp_enqueue_style( 'sodek-gb-public' );
    }

    /**
     * Editor scripts.
     */
    public static function editor_scripts() {
        wp_enqueue_style(
            'sodek-gb-elementor-editor',
            SODEK_GB_PLUGIN_URL . 'assets/css/elementor-editor.css',
            array(),
            SODEK_GB_VERSION
        );
    }

    /**
     * Get services for Elementor controls.
     *
     * @return array
     */
    public static function get_services_options() {
        $services = Sodek_GB_Service::get_all_services();
        $options  = array(
            '' => __( '-- Select Service --', 'glowbook' ),
        );

        foreach ( $services as $service ) {
            $options[ $service['id'] ] = $service['title'];
        }

        return $options;
    }

    /**
     * Get categories for Elementor controls.
     *
     * @return array
     */
    public static function get_categories_options() {
        $categories = Sodek_GB_Service::get_categories();
        $options    = array(
            '' => __( '-- All Categories --', 'glowbook' ),
        );

        foreach ( $categories as $category ) {
            $options[ $category['id'] ] = $category['name'];
        }

        return $options;
    }

    /**
     * Get WooCommerce product categories for Elementor controls.
     *
     * @return array
     */
    public static function get_wc_categories_options() {
        $options = array(
            '' => __( '-- All Categories --', 'glowbook' ),
        );

        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return $options;
        }

        $categories = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );

        if ( is_wp_error( $categories ) ) {
            return $options;
        }

        foreach ( $categories as $category ) {
            $options[ $category->term_id ] = $category->name;
        }

        return $options;
    }
}
