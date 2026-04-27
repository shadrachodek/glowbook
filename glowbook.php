<?php
/**
 *
 * Plugin Name: GlowBook
 * Plugin URI: https://github.com/shadrachodek/glowbook
 * Description: Premium standalone booking system for beauty salons and service businesses with deposit handling, customer portal, staff management, and optional WooCommerce integration.
 * Version: 2.3.18
 * Author: Shadrach Odekhiran
 * Author URI: https://shadrachodek.com
 * Text Domain: glowbook
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 *
 * @package GlowBook
 *
 * ## Changelog
 *
 * ### 2.3.18
 * - Harden standalone availability and slot locking across services and add-on-aware staff flows
 * - Fix timezone-sensitive booking and portal reschedule calculations
 * - Clean up standalone/block-theme rendering and production debug output
 *
 * ### 2.3.17
 * - Ship the production-ready standalone booking flow with Square checkout hardening
 * - Add configurable returning/new customer booking payments and global daily booking limits
 * - Improve booking availability, add-on duration handling, confirmation, portal, and mobile polish
 *
 * ### 2.3.16
 * - Add a dedicated GlowBook Booking Admin role with booking-only admin access
 * - Restrict booking admins away from unrelated WordPress admin screens and menus
 * - Improve the mobile admin experience for booking-focused users
 *
 * ### 2.3.15
 * - Fix reports warnings around missing booking fields and null date parsing
 * - Align standalone booking payment buttons with fixed returning/new customer booking-time payment rules
 * - Fix calendar date availability so only dates with real slots show as selectable
 * - Strengthen availability import/export relationship mapping for staff schedules and time off
 *
 * ### 2.3.14
 * - Add dedicated admin customer directory and full customer profile page
 * - Strengthen import/export relationship mapping for customers, staff, services, add-ons, and bookings
 * - Improve standalone customer detection, cancellation enforcement, and Square payment flows
 * - Refine portal actions, confirmation UX, and customer-facing admin polish
 *
 * ### 2.3.13
 * - Defer translated plugin boot logic to init to remove the admin translation timing notice
 *
 * ### 2.3.12
 * - Fix standalone portal magic-link token login
 * - Wire portal logout, reschedule helper, cancellation policy, and profile/preference AJAX actions
 * - Align portal dashboard scripts with the standalone portal backend
 *
 * ### 2.3.11
 * - Harden Square saved-card handling with real customer/card-on-file support
 * - Sync portal saved-card default and delete actions with backend processing
 *
 * ### 2.3.10
 * - Polish the standalone booking UI across details, payment, and mobile layouts
 * - Hide zero-price service amounts and add service image lightbox support to the appointment card
 *
 * ### 2.3.9
 * - Clean up standalone booking debug output
 * - Add a user-friendly add-on load error state
 * - Standardize standalone frontend config bootstrap
 * - Move textdomain loading to its own init hook
 *
 * ### 2.3.8
 * - Move standalone add-on filtering to the backend
 * - Add a friendlier loading state for standalone add-ons
 *
 * ### 2.3.7
 * - Fix add-on assignment saving for full edit and Quick Edit
 * - Fix standalone booking add-on filtering and asset cache busting
 *
 * ### 2.3.6
 * - Added debug logging for addon service selection save issues
 * - Cache busting for JavaScript changes
 *
 * ### 2.3.5
 * - Fixed customer portal login bug - "An error occurred. Please try again."
 * - Added missing sodekGbPortal.ajaxUrl JavaScript variable to portal templates
 * - Fixed login.php and dashboard.php templates to properly define AJAX URL
 *
 * ### 2.3.4
 * - Removed WooCommerce product auto-creation from services
 * - Services no longer create WC products (fully standalone mode)
 * - Removed WC product checkbox and linking from service admin
 * - Fixed [glowbook_portal] shortcode - added missing render_content() method
 *
 * ### 2.3.3
 * - Made service images smaller (80x80) with tap-to-zoom lightbox
 * - Added image support for add-ons with same lightbox functionality
 * - Images now show zoom indicator on hover
 *
 * ### 2.3.2
 * - Fixed add-on filtering by service - addons now properly filter based on their service associations
 *
 * ### 2.3.1
 * - Fixed "Show all add-ons" button not revealing hidden add-ons
 *
 * ### 2.3.0
 * - Added [glowbook_confirmation] shortcode for confirmation page
 * - Added Page Settings in admin to configure:
 *   - Confirmation Page (page with [glowbook_confirmation])
 *   - Customer Portal Page (page with [glowbook_portal])
 *   - Booking Page (page with [glowbook_booking])
 * - Plugin now redirects to configured pages after booking
 * - Shortcodes work on any page with proper URL parameters
 *
 * ### 2.2.6
 * - Fixed flash of category step when restoring from URL hash
 * - Added loading state to hide all steps until correct one is determined
 *
 * ### 2.2.5
 * - URL hash state persistence: refreshing browser maintains current step
 * - Supports browser back/forward navigation
 * - Hash format: #service/categoryId, #datetime/categoryId/serviceId
 *
 * ### 2.2.4
 * - Removed image from appointment card (images only shown on service list)
 *
 * ### 2.2.3
 * - Changed service thumbnail to medium_large size for better quality
 *
 * ### 2.2.2
 * - Redesigned service cards with larger images (180px) on left side
 * - Service name now uppercase, price more prominent
 * - SELECT button aligned to top-right
 * - Responsive: smaller images (120px) on mobile
 * - Added quick edit support for add-ons with services list
 * - Can now edit price, duration, and service associations from list view
 *
 * ### 2.2.0
 * - Added per-service display overrides for image and deposit visibility
 * - Each service can now override global settings (Use Global / Show / Hide)
 * - Service edit screen: Display Options meta box
 * - Global settings still control default behavior (both hidden by default)
 *
 * ### 2.1.2
 * - Added admin settings to show/hide service images and deposit on service cards
 * - Both settings default to hidden for cleaner default appearance
 * - Settings location: GlowBook > Settings > Display Settings > Service Cards
 *
 * ### 2.1.1
 * - Display deposit amount on service cards (e.g., "$15.00 deposit")
 *
 * ### 2.1.0
 * - Changed deposit slider to number input for better UX
 *   - Direct amount entry with currency symbol
 *   - Quick options: Minimum, 50%, Full
 *   - Auto-validation on blur (clamps to valid range)
 * - Fixed deposit amount not showing on payment step
 * - Added deposit data attributes to service rows
 *
 * ### 1.0.2
 * - Added flexible deposit feature allowing customers to pay more than minimum deposit
 *   - Range slider for intuitive deposit selection
 *   - Quick option buttons (Minimum, 50%, 75%, Pay in Full)
 *   - Real-time balance calculation display
 * - Added service add-ons support on frontend booking form
 *   - Checkbox selection for available add-ons
 *   - Dynamic pricing and duration updates
 *   - Add-ons included in booking summary and cart
 * - Made add-on "Extra Duration" field optional
 * - Fixed WooCommerce "Bookable Service" product type not saving
 * - Elementor Services Grid widget improvements:
 *   - Added option to filter by WooCommerce Product Categories (default)
 *   - Can now switch between GlowBook Service Categories and WooCommerce Product Categories
 * - Security improvements:
 *   - Added proper nonce verification with wp_unslash() and sanitize_text_field()
 *   - Added uninstall.php for proper plugin data cleanup
 * - Fixed duplicate cron filter registration
 * - Fixed JavaScript add-on deposit calculation for fixed deposit types
 * - Various bug fixes and code quality improvements
 *
 * ### 1.0.1
 * - Added Elementor integration with custom widgets:
 *   - Services Grid Widget: Display services in multiple layouts (Grid, Clean List, Icon Cards)
 *   - Service Card Widget: Individual service display with customizable styles
 *   - Booking Form Widget: Embeddable booking form for any page
 * - New layout styles for Services Grid:
 *   - Grid: Traditional card-based grid layout with images
 *   - Clean List (Style 1): Elegant horizontal list with price, duration, and CTA button
 *   - Icon Cards (Style 2): Centered icon/image cards with title and description
 * - Enhanced CSS with design system tokens, micro-interactions, and accessibility support
 * - Added responsive breakpoints and reduced-motion support
 *
 * ### 1.0.0
 * - Initial release
 * - WooCommerce-integrated booking system
 * - Deposit handling and payment processing
 * - WhatsApp notifications
 * - Staff management and availability
 * - Google Calendar integration
 * - Email reminders and notifications
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants
define( 'SODEK_GB_VERSION', '2.3.18' );
define( 'SODEK_GB_PLUGIN_FILE', __FILE__ );
define( 'SODEK_GB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SODEK_GB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SODEK_GB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Clean output buffer for AJAX requests to prevent PHP notices from breaking JSON
if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
    // phpcs:ignore WordPress.PHP.IniSet.display_errors_Blacklisted
    @ini_set( 'display_errors', '0' );
    if ( ! defined( 'SODEK_GB_AJAX_CLEAN' ) ) {
        define( 'SODEK_GB_AJAX_CLEAN', true );
        // Start output buffering early to catch any stray output
        ob_start();
    }
}

/**
 * Main plugin class.
 */
final class Sodek_GlowBook {

    /**
     * Single instance of the class.
     *
     * @var Sodek_GlowBook
     */
    private static $instance = null;

    /**
     * Get single instance.
     *
     * @return Sodek_GlowBook
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->check_requirements();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Check plugin requirements.
     */
    private function check_requirements() {
        // WooCommerce is now optional - standalone mode works without it
        if ( ! $this->is_woocommerce_active() && get_option( 'sodek_gb_mode', 'standalone' ) === 'woocommerce' ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
        }
    }

    /**
     * Check if WooCommerce is active.
     *
     * @return bool
     */
    public function is_woocommerce_active() {
        return class_exists( 'WooCommerce' ) || in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true );
    }

    /**
     * Check if running in standalone mode (no WooCommerce).
     *
     * @return bool
     */
    public function is_standalone_mode() {
        return get_option( 'sodek_gb_mode', 'standalone' ) === 'standalone' || ! $this->is_woocommerce_active();
    }

    /**
     * WooCommerce missing notice.
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <?php esc_html_e( 'GlowBook is running in standalone mode. Install WooCommerce for cart/checkout integration, or continue using the standalone booking system.', 'glowbook' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=glowbook-settings' ) ); ?>"><?php esc_html_e( 'Configure Settings', 'glowbook' ); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Include required files.
     */
    private function includes() {
        // Core classes
        require_once SODEK_GB_PLUGIN_DIR . 'includes/class-sodek-gb-activator.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/core/class-sodek-gb-service.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/core/class-sodek-gb-booking.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/core/class-sodek-gb-availability.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/core/class-sodek-gb-staff.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/core/class-sodek-gb-addon.php';

        // New standalone core classes
        require_once SODEK_GB_PLUGIN_DIR . 'includes/core/class-sodek-gb-customer.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/core/class-sodek-gb-staff-availability.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/core/class-sodek-gb-waitlist.php';

        // WooCommerce integration (only if WooCommerce is active)
        if ( $this->is_woocommerce_active() ) {
            require_once SODEK_GB_PLUGIN_DIR . 'includes/woocommerce/class-sodek-gb-wc-product.php';
            require_once SODEK_GB_PLUGIN_DIR . 'includes/woocommerce/class-sodek-gb-wc-cart.php';
            require_once SODEK_GB_PLUGIN_DIR . 'includes/woocommerce/class-sodek-gb-wc-checkout.php';
        }

        // Standalone booking system
        require_once SODEK_GB_PLUGIN_DIR . 'includes/standalone/class-sodek-gb-standalone-booking.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/standalone/class-sodek-gb-booking-page.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/standalone/class-sodek-gb-confirmation-page.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/standalone/class-sodek-gb-customer-portal.php';

        // Notifications
        require_once SODEK_GB_PLUGIN_DIR . 'includes/notifications/class-sodek-gb-emails.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/notifications/class-sodek-gb-reminders.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/notifications/class-sodek-gb-whatsapp.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/notifications/class-sodek-gb-twilio.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/notifications/class-sodek-gb-sms.php';

        // REST API
        require_once SODEK_GB_PLUGIN_DIR . 'includes/api/class-sodek-gb-rest-api.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/api/class-sodek-gb-rest-customers.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/api/class-sodek-gb-rest-staff.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/api/class-sodek-gb-rest-portal.php';

        // Payment gateway classes
        require_once SODEK_GB_PLUGIN_DIR . 'includes/payments/gateways/interface-sodek-gb-payment-gateway.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/payments/gateways/abstract-class-sodek-gb-gateway.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/payments/gateways/class-sodek-gb-square-gateway.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/payments/class-sodek-gb-transaction.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/payments/class-sodek-gb-payment-manager.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/payments/class-sodek-gb-standalone-checkout.php';

        // Integrations
        require_once SODEK_GB_PLUGIN_DIR . 'includes/integrations/class-sodek-gb-google-calendar.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/integrations/elementor/class-sodek-gb-elementor.php';

        // Admin
        if ( is_admin() ) {
            require_once SODEK_GB_PLUGIN_DIR . 'admin/class-sodek-gb-admin.php';
        }

        // Public/Frontend
        require_once SODEK_GB_PLUGIN_DIR . 'public/class-sodek-gb-public.php';
        require_once SODEK_GB_PLUGIN_DIR . 'public/class-sodek-gb-shortcodes.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/frontend/class-sodek-gb-my-account.php';
        require_once SODEK_GB_PLUGIN_DIR . 'includes/frontend/class-sodek-gb-confirmation.php';
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        register_activation_hook( SODEK_GB_PLUGIN_FILE, array( 'Sodek_GB_Activator', 'activate' ) );
        register_deactivation_hook( SODEK_GB_PLUGIN_FILE, array( 'Sodek_GB_Activator', 'deactivate' ) );

        add_action( 'init', array( $this, 'load_textdomain' ), 0 );
        add_action( 'init', array( $this, 'on_plugins_loaded' ), 5 );
        add_action( 'init', array( $this, 'init' ), 1 );
    }

    /**
     * Check if database needs upgrade and run upgrade if needed.
     */
    private function maybe_upgrade_database() {
        $current_db_version = get_option( 'sodek_gb_db_version', '1.0.0' );
        $required_db_version = Sodek_GB_Activator::DB_VERSION;

        if ( version_compare( $current_db_version, $required_db_version, '<' ) ) {
            // Run the activation routine to create/update tables
            Sodek_GB_Activator::activate();
        }
    }

    /**
     * When plugins are loaded.
     */
    public function on_plugins_loaded() {
        // Check if database needs upgrade
        $this->maybe_upgrade_database();

        // Initialize WooCommerce integration (if available and enabled)
        if ( $this->is_woocommerce_active() && ! $this->is_standalone_mode() ) {
            Sodek_GB_WC_Product::init();
            Sodek_GB_WC_Cart::init();
            Sodek_GB_WC_Checkout::init();
            Sodek_GB_My_Account::init();
        }

        // Initialize standalone booking system (always available)
        Sodek_GB_Standalone_Booking::init();
        Sodek_GB_Booking_Page::init();
        Sodek_GB_Confirmation_Page::init();
        Sodek_GB_Customer_Portal::init();

        // Initialize emails
        Sodek_GB_Emails::init();
        Sodek_GB_Reminders::init();
        Sodek_GB_WhatsApp::init();

        // Initialize SMS (Twilio)
        Sodek_GB_SMS::init();

        // Initialize REST API
        Sodek_GB_REST_API::init();
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

        // Initialize admin
        if ( is_admin() ) {
            Sodek_GB_Admin::init();

            // Check and create missing pages for existing installations
            add_action( 'admin_init', array( $this, 'maybe_create_pages' ) );
        }

        // Initialize frontend
        Sodek_GB_Public::init();
        Sodek_GB_Shortcodes::init();
        Sodek_GB_Confirmation::init();

        // Initialize integrations
        Sodek_GB_Google_Calendar::init();
        Sodek_GB_Elementor::init();

        // Initialize staff management
        Sodek_GB_Staff::init();

        // Initialize add-ons
        Sodek_GB_Addon::init();

        // Initialize payment system
        Sodek_GB_Payment_Manager::init();
        Sodek_GB_Standalone_Checkout::init();
    }

    /**
     * Register additional REST API routes.
     */
    public function register_rest_routes() {
        $customers_api = new Sodek_GB_REST_Customers();
        $customers_api->register_routes();

        $staff_api = new Sodek_GB_REST_Staff();
        $staff_api->register_routes();

        $portal_api = new Sodek_GB_REST_Portal();
        $portal_api->register_routes();
    }

    /**
     * Init plugin.
     */
    public function init() {
        // Register CPTs
        Sodek_GB_Service::register_post_type();
        Sodek_GB_Booking::register_post_type();

        // Add rewrite rules for standalone booking
        $this->add_rewrite_rules();

        // Check if rewrite rules need to be flushed
        $this->maybe_flush_rewrite_rules();
    }

    /**
     * Load plugin translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'glowbook', false, dirname( SODEK_GB_PLUGIN_BASENAME ) . '/languages' );
    }

    /**
     * Maybe flush rewrite rules if needed.
     */
    private function maybe_flush_rewrite_rules() {
        // Check if we need to flush rewrite rules (includes revision for forced flush on code changes)
        $flush_revision = '2'; // Increment this when rewrite rules change
        $flush_key = 'sodek_gb_flush_rewrite_' . SODEK_GB_VERSION . '_r' . $flush_revision;
        if ( get_option( $flush_key ) !== 'done' ) {
            flush_rewrite_rules();
            update_option( $flush_key, 'done' );
        }
    }

    /**
     * Add rewrite rules for standalone booking system.
     */
    private function add_rewrite_rules() {
        $booking_slug = get_option( 'sodek_gb_booking_slug', 'book' );
        $portal_slug = get_option( 'sodek_gb_portal_slug', 'my-appointments' );

        // Booking page rules
        add_rewrite_rule(
            '^' . $booking_slug . '/?$',
            'index.php?sodek_gb_page=booking',
            'top'
        );

        // Confirmation page rule
        add_rewrite_rule(
            '^' . $booking_slug . '/confirmation/([^/]+)/?$',
            'index.php?sodek_gb_page=confirmation&sodek_gb_key=$matches[1]',
            'top'
        );

        // Customer portal rules
        add_rewrite_rule(
            '^' . $portal_slug . '/?$',
            'index.php?sodek_gb_page=portal',
            'top'
        );

        add_rewrite_rule(
            '^' . $portal_slug . '/login/?$',
            'index.php?sodek_gb_page=portal_login',
            'top'
        );

        add_rewrite_rule(
            '^' . $portal_slug . '/reschedule/?$',
            'index.php?sodek_gb_page=portal_reschedule',
            'top'
        );

        add_rewrite_rule(
            '^' . $portal_slug . '/cancel/?$',
            'index.php?sodek_gb_page=portal_cancel',
            'top'
        );

        // Register query vars
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );

        // Handle template redirect
        add_action( 'template_redirect', array( $this, 'handle_standalone_pages' ) );
    }

    /**
     * Add custom query vars.
     *
     * @param array $vars Query vars.
     * @return array
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'sodek_gb_page';
        $vars[] = 'sodek_gb_key';
        return $vars;
    }

    /**
     * Handle standalone page templates.
     */
    public function handle_standalone_pages() {
        $page = get_query_var( 'sodek_gb_page' );

        if ( ! $page ) {
            return;
        }

        switch ( $page ) {
            case 'booking':
                Sodek_GB_Booking_Page::render();
                exit;

            case 'confirmation':
                Sodek_GB_Confirmation_Page::render();
                exit;

            case 'portal':
                Sodek_GB_Customer_Portal::render();
                exit;

            case 'portal_login':
                Sodek_GB_Customer_Portal::render_login();
                exit;

            case 'portal_reschedule':
                Sodek_GB_Customer_Portal::render_reschedule();
                exit;

            case 'portal_cancel':
                Sodek_GB_Customer_Portal::render_cancel();
                exit;
        }
    }

    /**
     * Get the plugin URL.
     *
     * @return string
     */
    public function plugin_url() {
        return SODEK_GB_PLUGIN_URL;
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function plugin_path() {
        return SODEK_GB_PLUGIN_DIR;
    }

    /**
     * Create pages if they don't exist (for existing installations).
     */
    public function maybe_create_pages() {
        $pages_created_key = 'sodek_gb_pages_created_v2';

        // Only run once
        if ( get_option( $pages_created_key ) === 'yes' ) {
            return;
        }

        // Check if booking page exists
        $booking_page_id = get_option( 'sodek_gb_booking_page_id' );
        if ( ! $booking_page_id || get_post_status( $booking_page_id ) !== 'publish' ) {
            Sodek_GB_Activator::create_pages();
        }

        update_option( $pages_created_key, 'yes' );
    }
}

/**
 * Returns the main instance of GlowBook.
 *
 * @return Sodek_GlowBook
 */
function sodek_gb() {
    return Sodek_GlowBook::instance();
}

// Initialize the plugin
sodek_gb();
