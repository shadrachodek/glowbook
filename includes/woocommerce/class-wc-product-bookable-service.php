<?php
/**
 * Bookable Service product type.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Product_Bookable_Service class.
 */
class WC_Product_Bookable_Service extends WC_Product {

    /**
     * Product type.
     *
     * @var string
     */
    protected $product_type = 'bookable_service';

    /**
     * Constructor.
     *
     * @param int|WC_Product|object $product Product ID, object, or data.
     */
    public function __construct( $product = 0 ) {
        parent::__construct( $product );
    }

    /**
     * Get product type.
     *
     * @return string
     */
    public function get_type() {
        return 'bookable_service';
    }

    /**
     * Check if product is virtual (services are always virtual).
     *
     * @param string $context Context.
     * @return bool
     */
    public function is_virtual( $context = 'view' ) {
        return true;
    }

    /**
     * Check if product is sold individually.
     *
     * @param string $context Context.
     * @return bool
     */
    public function is_sold_individually( $context = 'view' ) {
        return true;
    }

    /**
     * Check if product is purchasable.
     *
     * @return bool
     */
    public function is_purchasable() {
        return true;
    }

    /**
     * Get linked service ID.
     *
     * @return int
     */
    public function get_service_id() {
        return (int) $this->get_meta( '_sodek_gb_service_id' );
    }

    /**
     * Get linked service data.
     *
     * @return array|false
     */
    public function get_service() {
        $service_id = $this->get_service_id();
        if ( ! $service_id ) {
            return false;
        }
        return Sodek_GB_Service::get_service( $service_id );
    }

    /**
     * Get deposit amount.
     *
     * @return float
     */
    public function get_deposit_amount() {
        $service_id = $this->get_service_id();
        if ( ! $service_id ) {
            return 0;
        }
        return Sodek_GB_Service::calculate_deposit( $service_id );
    }

    /**
     * Get full service price.
     *
     * @return float
     */
    public function get_full_price() {
        $service = $this->get_service();
        return $service ? (float) $service['price'] : 0;
    }

    /**
     * Get balance due.
     *
     * @return float
     */
    public function get_balance_due() {
        return $this->get_full_price() - $this->get_deposit_amount();
    }

    /**
     * Get price (returns deposit amount).
     *
     * @param string $context Context.
     * @return string
     */
    public function get_price( $context = 'view' ) {
        $deposit = $this->get_deposit_amount();
        return $deposit > 0 ? $deposit : parent::get_price( $context );
    }

    /**
     * Check if this is an addon-only service (base price is 0).
     *
     * @return bool
     */
    public function is_addon_only() {
        $service = $this->get_service();
        if ( ! $service ) {
            return false;
        }

        // Check if service has addons
        $addons = Sodek_GB_Addon::get_addons_for_service( $service['id'] );
        $has_addons = ! empty( $addons );

        return ( $service['price'] <= 0 && $has_addons );
    }

    /**
     * Get the lowest addon price for display.
     *
     * @return float
     */
    public function get_lowest_addon_price() {
        $service = $this->get_service();
        if ( ! $service ) {
            return 0;
        }

        $addons = Sodek_GB_Addon::get_addons_for_service( $service['id'] );
        if ( empty( $addons ) ) {
            return 0;
        }

        $lowest = PHP_FLOAT_MAX;
        foreach ( $addons as $addon ) {
            if ( isset( $addon['price'] ) && $addon['price'] < $lowest ) {
                $lowest = (float) $addon['price'];
            }
        }

        return $lowest < PHP_FLOAT_MAX ? $lowest : 0;
    }

    /**
     * Get price HTML - customized for addon-only services.
     *
     * @param string $price_html Default price HTML.
     * @return string
     */
    public function get_price_html( $price_html = '' ) {
        if ( $this->is_addon_only() ) {
            $lowest_price = $this->get_lowest_addon_price();
            if ( $lowest_price > 0 ) {
                return '<span class="sodek-gb-price-from">' .
                       esc_html__( 'From', 'glowbook' ) . ' ' .
                       wc_price( $lowest_price ) .
                       '</span>';
            }
            return '<span class="sodek-gb-price-varies">' .
                   esc_html__( 'Price varies by selection', 'glowbook' ) .
                   '</span>';
        }

        return parent::get_price_html( $price_html );
    }

    /**
     * Returns whether or not the product has additional options that need
     * selecting before adding to cart.
     *
     * @return boolean
     */
    public function has_options() {
        return true;
    }

    /**
     * Disable AJAX add to cart - booking products require the full form submission.
     *
     * @return bool
     */
    public function supports_ajax_add_to_cart() {
        return false;
    }

    /**
     * Get the add to cart button text.
     *
     * @return string
     */
    public function add_to_cart_text() {
        return apply_filters( 'woocommerce_product_add_to_cart_text', __( 'Book Now', 'glowbook' ), $this );
    }

    /**
     * Get the add to cart button text for the single page.
     *
     * @return string
     */
    public function single_add_to_cart_text() {
        return apply_filters( 'woocommerce_product_single_add_to_cart_text', __( 'Book Now', 'glowbook' ), $this );
    }
}
