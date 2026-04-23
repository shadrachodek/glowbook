<?php
/**
 * WooCommerce checkout and order handling.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_WC_Checkout class.
 */
class Sodek_GB_WC_Checkout {

    /**
     * Initialize.
     */
    public static function init() {
        // Create booking on order payment complete
        add_action( 'woocommerce_payment_complete', array( __CLASS__, 'create_booking_from_order' ) );
        add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'create_booking_from_order' ) );
        add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'create_booking_from_order' ) );

        // Also handle free orders
        add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'handle_order_processed' ), 10, 3 );

        // Cancel booking when order cancelled/refunded
        add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'cancel_booking_from_order' ) );
        add_action( 'woocommerce_order_status_refunded', array( __CLASS__, 'cancel_booking_from_order' ) );

        // Add phone field requirement
        add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'modify_checkout_fields' ) );

        // Display booking info in order details
        add_action( 'woocommerce_order_item_meta_end', array( __CLASS__, 'display_order_item_booking_info' ), 10, 4 );

        // Add booking info to emails
        add_action( 'woocommerce_email_after_order_table', array( __CLASS__, 'add_booking_info_to_email' ), 10, 4 );
    }

    /**
     * Handle order processed (for free orders).
     *
     * @param int      $order_id Order ID.
     * @param array    $posted_data Posted data.
     * @param WC_Order $order Order.
     */
    public static function handle_order_processed( $order_id, $posted_data, $order ) {
        // For free orders, create booking immediately
        if ( $order->get_total() == 0 ) {
            self::create_booking_from_order( $order_id );
        }
    }

    /**
     * Create booking from WooCommerce order.
     *
     * @param int $order_id Order ID.
     */
    public static function create_booking_from_order( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        // Check if bookings already created
        $bookings_created = $order->get_meta( '_sodek_gb_bookings_created' );
        if ( $bookings_created ) {
            return;
        }

        $booking_ids = array();

        foreach ( $order->get_items() as $item_id => $item ) {
            $service_id = $item->get_meta( '_sodek_gb_service_id' );

            if ( ! $service_id ) {
                continue;
            }

            $booking_date = $item->get_meta( '_sodek_gb_booking_date' );
            $booking_time = $item->get_meta( '_sodek_gb_booking_time' );

            if ( ! $booking_date || ! $booking_time ) {
                continue;
            }

            // Get add-on data
            $addon_ids       = $item->get_meta( '_sodek_gb_addon_ids' );
            $addons_duration = (int) $item->get_meta( '_sodek_gb_addons_duration' );
            $addons_price    = (float) $item->get_meta( '_sodek_gb_addons_price' );

            // Calculate end time including add-ons
            $service = Sodek_GB_Service::get_service( $service_id );
            $total_duration = $service['duration'] + $addons_duration;
            $start = strtotime( $booking_time );
            $end_time = gmdate( 'H:i', $start + ( $total_duration * 60 ) );

            // Get notes
            $booking_notes = $item->get_meta( '_sodek_gb_booking_notes' );

            // Get full pricing from item
            $full_price     = (float) $item->get_meta( '_sodek_gb_full_price' );
            $deposit_amount = (float) $item->get_meta( '_sodek_gb_deposit_amount' );

            // Create the booking
            $booking_id = Sodek_GB_Booking::create_booking( array(
                'service_id'     => $service_id,
                'booking_date'   => $booking_date,
                'start_time'     => $booking_time,
                'end_time'       => $end_time,
                'customer_name'  => $order->get_formatted_billing_full_name(),
                'customer_email' => $order->get_billing_email(),
                'customer_phone' => $order->get_billing_phone(),
                'customer_id'    => $order->get_customer_id(),
                'order_id'       => $order_id,
                'status'         => Sodek_GB_Booking::STATUS_CONFIRMED,
                'notes'          => $booking_notes,
            ) );

            if ( ! is_wp_error( $booking_id ) ) {
                $booking_ids[] = $booking_id;

                // Mark deposit as paid
                update_post_meta( $booking_id, '_sodek_gb_deposit_paid', '1' );

                // Save full pricing (overriding service defaults if add-ons were added)
                if ( $full_price > 0 ) {
                    update_post_meta( $booking_id, '_sodek_gb_total_price', $full_price );
                }
                if ( $deposit_amount > 0 ) {
                    update_post_meta( $booking_id, '_sodek_gb_deposit_amount', $deposit_amount );
                }

                // Save add-ons to booking
                if ( ! empty( $addon_ids ) && is_array( $addon_ids ) ) {
                    Sodek_GB_Addon::save_booking_addons( $booking_id, $addon_ids );
                }

                // Save notes to booking
                if ( $booking_notes ) {
                    update_post_meta( $booking_id, '_sodek_gb_customer_notes', $booking_notes );
                }

                // Store booking ID on order item
                wc_update_order_item_meta( $item_id, '_sodek_gb_booking_id', $booking_id );

                // Trigger booking confirmed action
                do_action( 'sodek_gb_booking_confirmed', $booking_id, $order_id );
            }
        }

        if ( ! empty( $booking_ids ) ) {
            $order->update_meta_data( '_sodek_gb_bookings_created', true );
            $order->update_meta_data( '_sodek_gb_booking_ids', $booking_ids );
            $order->save();

            // Add order note
            $order->add_order_note(
                sprintf(
                    /* translators: %s: booking IDs */
                    __( 'Booking(s) created: %s', 'glowbook' ),
                    implode( ', ', array_map( function( $id ) {
                        return '#' . $id;
                    }, $booking_ids ) )
                )
            );
        }
    }

    /**
     * Cancel booking when order is cancelled.
     *
     * @param int $order_id Order ID.
     */
    public static function cancel_booking_from_order( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        $booking_ids = $order->get_meta( '_sodek_gb_booking_ids' );

        if ( empty( $booking_ids ) ) {
            return;
        }

        foreach ( $booking_ids as $booking_id ) {
            Sodek_GB_Booking::update_status( $booking_id, Sodek_GB_Booking::STATUS_CANCELLED );
        }

        $order->add_order_note( __( 'Associated booking(s) cancelled.', 'glowbook' ) );
    }

    /**
     * Modify checkout fields for bookings.
     *
     * @param array $fields Checkout fields.
     * @return array
     */
    public static function modify_checkout_fields( $fields ) {
        // Check if cart has booking items
        if ( ! WC()->cart ) {
            return $fields;
        }

        $has_booking = false;
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( isset( $cart_item['sodek_gb_booking'] ) ) {
                $has_booking = true;
                break;
            }
        }

        // Make phone required for bookings
        if ( $has_booking && isset( $fields['billing']['billing_phone'] ) ) {
            $fields['billing']['billing_phone']['required'] = true;
        }

        return $fields;
    }

    /**
     * Display booking info in order item details.
     *
     * @param int           $item_id Item ID.
     * @param WC_Order_Item $item    Item.
     * @param WC_Order      $order   Order.
     * @param bool          $plain_text Plain text.
     */
    public static function display_order_item_booking_info( $item_id, $item, $order, $plain_text ) {
        $booking_id = $item->get_meta( '_sodek_gb_booking_id' );

        if ( ! $booking_id ) {
            return;
        }

        $booking = Sodek_GB_Booking::get_booking( $booking_id );

        if ( ! $booking ) {
            return;
        }

        if ( $plain_text ) {
            echo "\n" . __( 'Booking Status:', 'glowbook' ) . ' ' . ucfirst( $booking['status'] );
        } else {
            $statuses = Sodek_GB_Booking::get_statuses();
            $status_label = isset( $statuses[ $booking['status'] ] ) ? $statuses[ $booking['status'] ] : $booking['status'];
            ?>
            <p class="sodek-gb-order-booking-status">
                <strong><?php esc_html_e( 'Booking Status:', 'glowbook' ); ?></strong>
                <span class="sodek-gb-status sodek-gb-status-<?php echo esc_attr( $booking['status'] ); ?>">
                    <?php echo esc_html( $status_label ); ?>
                </span>
            </p>
            <?php
        }
    }

    /**
     * Add booking info to order emails.
     *
     * @param WC_Order $order         Order.
     * @param bool     $sent_to_admin Sent to admin.
     * @param bool     $plain_text    Plain text.
     * @param WC_Email $email         Email.
     */
    public static function add_booking_info_to_email( $order, $sent_to_admin, $plain_text, $email = null ) {
        $booking_ids = $order->get_meta( '_sodek_gb_booking_ids' );

        if ( empty( $booking_ids ) ) {
            return;
        }

        $bookings = array();
        $total_service_value = 0;
        $total_deposit_paid = 0;

        foreach ( $booking_ids as $booking_id ) {
            $booking = Sodek_GB_Booking::get_booking( $booking_id );
            if ( $booking ) {
                $bookings[] = $booking;
                $total_service_value += floatval( $booking['total_price'] );
                $total_deposit_paid += floatval( $booking['deposit_amount'] );
            }
        }

        if ( empty( $bookings ) ) {
            return;
        }

        $total_balance_due = $total_service_value - $total_deposit_paid;

        if ( $plain_text ) {
            echo "\n" . str_repeat( '=', 50 ) . "\n";
            echo strtoupper( __( 'Appointment Details', 'glowbook' ) ) . "\n";
            echo str_repeat( '=', 50 ) . "\n\n";

            foreach ( $bookings as $booking ) {
                $balance = $booking['total_price'] - $booking['deposit_amount'];
                $total_duration = ( $booking['service']['duration'] ?? 0 ) + ( $booking['addons_duration'] ?? 0 );
                echo __( 'Service:', 'glowbook' ) . ' ' . $booking['service']['title'] . "\n";
                echo __( 'Date:', 'glowbook' ) . ' ' . date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) . "\n";
                echo __( 'Time:', 'glowbook' ) . ' ' . date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) . "\n";
                echo __( 'Duration:', 'glowbook' ) . ' ' . $total_duration . ' ' . __( 'minutes', 'glowbook' ) . "\n";
                if ( ! empty( $booking['addons'] ) ) {
                    echo __( 'Add-ons:', 'glowbook' ) . "\n";
                    foreach ( $booking['addons'] as $addon ) {
                        echo '  - ' . $addon['title'] . ' (' . wc_price( $addon['price'] ) . ")\n";
                    }
                }
                echo "\n";
            }

            echo str_repeat( '-', 50 ) . "\n";
            echo __( 'PAYMENT SUMMARY', 'glowbook' ) . "\n";
            echo str_repeat( '-', 50 ) . "\n";
            echo __( 'Total Service Value:', 'glowbook' ) . ' ' . strip_tags( wc_price( $total_service_value ) ) . "\n";
            echo __( 'Deposit Paid:', 'glowbook' ) . ' ' . strip_tags( wc_price( $total_deposit_paid ) ) . "\n";
            if ( $total_balance_due > 0 ) {
                echo __( 'BALANCE DUE AT APPOINTMENT:', 'glowbook' ) . ' ' . strip_tags( wc_price( $total_balance_due ) ) . "\n";
            }
            echo "\n";
            echo __( 'Please arrive on time for your appointment.', 'glowbook' ) . "\n";
            if ( $total_balance_due > 0 ) {
                echo __( 'Remember to bring payment for the remaining balance.', 'glowbook' ) . "\n";
            }
        } else {
            ?>
            <div style="margin-bottom: 30px; border: 2px solid #96588a; border-radius: 8px; overflow: hidden;">
                <h2 style="background: #96588a; color: #ffffff; padding: 15px 20px; margin: 0; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; font-size: 18px; font-weight: bold;">
                    <?php esc_html_e( 'Your Appointment Details', 'glowbook' ); ?>
                </h2>

                <div style="padding: 20px;">
                    <?php foreach ( $bookings as $booking ) :
                        $total_duration = ( $booking['service']['duration'] ?? 0 ) + ( $booking['addons_duration'] ?? 0 );
                    ?>
                    <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e5e5e5;">
                        <h3 style="margin: 0 0 10px 0; color: #333;"><?php echo esc_html( $booking['service']['title'] ); ?></h3>
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 5px 0; width: 40%;"><strong><?php esc_html_e( 'Date:', 'glowbook' ); ?></strong></td>
                                <td style="padding: 5px 0;"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $booking['booking_date'] ) ) ); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0;"><strong><?php esc_html_e( 'Time:', 'glowbook' ); ?></strong></td>
                                <td style="padding: 5px 0;"><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $booking['start_time'] ) ) ); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0;"><strong><?php esc_html_e( 'Duration:', 'glowbook' ); ?></strong></td>
                                <td style="padding: 5px 0;"><?php echo esc_html( $total_duration ); ?> <?php esc_html_e( 'minutes', 'glowbook' ); ?></td>
                            </tr>
                            <?php if ( ! empty( $booking['addons'] ) ) : ?>
                            <tr>
                                <td style="padding: 5px 0; vertical-align: top;"><strong><?php esc_html_e( 'Add-ons:', 'glowbook' ); ?></strong></td>
                                <td style="padding: 5px 0;">
                                    <?php foreach ( $booking['addons'] as $addon ) : ?>
                                        <div style="margin-bottom: 3px;"><?php echo esc_html( $addon['title'] ); ?> (<?php echo wc_price( $addon['price'] ); ?>)</div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <?php endforeach; ?>

                    <!-- Payment Summary Box -->
                    <div style="background: #f8f9fa; border-radius: 6px; padding: 20px; margin-top: 20px;">
                        <h3 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #96588a; padding-bottom: 10px;">
                            <?php esc_html_e( 'Payment Summary', 'glowbook' ); ?>
                        </h3>
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 8px 0;"><strong><?php esc_html_e( 'Total Service Value:', 'glowbook' ); ?></strong></td>
                                <td style="padding: 8px 0; text-align: right;"><?php echo wc_price( $total_service_value ); ?></td>
                            </tr>
                            <tr>
                                <td style="padding: 8px 0; color: #28a745;"><strong><?php esc_html_e( 'Deposit Paid:', 'glowbook' ); ?></strong></td>
                                <td style="padding: 8px 0; text-align: right; color: #28a745; font-weight: bold;"><?php echo wc_price( $total_deposit_paid ); ?></td>
                            </tr>
                            <?php if ( $total_balance_due > 0 ) : ?>
                            <tr style="border-top: 2px solid #dee2e6;">
                                <td style="padding: 12px 0; font-size: 16px;"><strong><?php esc_html_e( 'Balance Due at Appointment:', 'glowbook' ); ?></strong></td>
                                <td style="padding: 12px 0; text-align: right; font-size: 18px; color: #dc3545; font-weight: bold;"><?php echo wc_price( $total_balance_due ); ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>

                    <?php if ( $total_balance_due > 0 ) : ?>
                    <!-- Important Notice -->
                    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 15px; margin-top: 20px;">
                        <p style="margin: 0; color: #856404;">
                            <strong><?php esc_html_e( 'Important:', 'glowbook' ); ?></strong>
                            <?php esc_html_e( 'Please remember to bring payment for the remaining balance to your appointment. We accept cash and card payments.', 'glowbook' ); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Get bookings for an order.
     *
     * @param int $order_id Order ID.
     * @return array
     */
    public static function get_order_bookings( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return array();
        }

        $booking_ids = $order->get_meta( '_sodek_gb_booking_ids' );

        if ( empty( $booking_ids ) ) {
            return array();
        }

        $bookings = array();
        foreach ( $booking_ids as $booking_id ) {
            $booking = Sodek_GB_Booking::get_booking( $booking_id );
            if ( $booking ) {
                $bookings[] = $booking;
            }
        }

        return $bookings;
    }
}
