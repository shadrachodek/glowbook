<?php
/**
 * Transaction Model.
 *
 * Handles transaction CRUD operations.
 *
 * @package GlowBook
 * @since   1.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Transaction class.
 */
class Sodek_GB_Transaction {

    /**
     * Transaction statuses.
     */
    const STATUS_PENDING   = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED    = 'failed';
    const STATUS_REFUNDED  = 'refunded';
    const STATUS_PARTIAL   = 'partial_refund';

    /**
     * Transaction types.
     */
    const TYPE_PAYMENT = 'payment';
    const TYPE_REFUND  = 'refund';

    /**
     * Get the table name.
     *
     * @return string
     */
    private static function get_table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'sodek_gb_transactions';
    }

    /**
     * Ensure the transactions table exists.
     */
    private static function ensure_table_exists(): void {
        global $wpdb;

        $table = self::get_table_name();

        // Check if table exists
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
            return;
        }

        // Create table
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            transaction_id varchar(100) NOT NULL,
            booking_id bigint(20) UNSIGNED DEFAULT NULL,
            gateway varchar(50) NOT NULL DEFAULT 'square',
            environment varchar(20) NOT NULL DEFAULT 'sandbox',
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            transaction_type varchar(30) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'pending',
            customer_email varchar(255) DEFAULT NULL,
            customer_name varchar(255) DEFAULT NULL,
            square_payment_id varchar(100) DEFAULT NULL,
            square_receipt_url varchar(500) DEFAULT NULL,
            square_card_brand varchar(50) DEFAULT NULL,
            square_card_last4 varchar(4) DEFAULT NULL,
            error_code varchar(100) DEFAULT NULL,
            error_message text DEFAULT NULL,
            request_data longtext DEFAULT NULL,
            response_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY transaction_id (transaction_id),
            KEY booking_id (booking_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Create a new transaction.
     *
     * @param array $data Transaction data.
     * @return int|false Transaction ID on success, false on failure.
     */
    public static function create( array $data ) {
        global $wpdb;

        // Ensure table exists before inserting
        self::ensure_table_exists();

        $defaults = array(
            'transaction_id'     => wp_generate_uuid4(),
            'booking_id'         => null,
            'gateway'            => 'square',
            'environment'        => 'sandbox',
            'amount'             => 0.00,
            'currency'           => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : get_option( 'sodek_gb_currency', 'USD' ),
            'transaction_type'   => self::TYPE_PAYMENT,
            'status'             => self::STATUS_PENDING,
            'customer_email'     => null,
            'customer_name'      => null,
            'square_payment_id'  => null,
            'square_receipt_url' => null,
            'square_card_brand'  => null,
            'square_card_last4'  => null,
            'error_code'         => null,
            'error_message'      => null,
            'request_data'       => null,
            'response_data'      => null,
        );

        $data = wp_parse_args( $data, $defaults );

        // Sanitize data
        $insert_data = array(
            'transaction_id'     => sanitize_text_field( $data['transaction_id'] ),
            'booking_id'         => $data['booking_id'] ? absint( $data['booking_id'] ) : null,
            'gateway'            => sanitize_text_field( $data['gateway'] ),
            'environment'        => sanitize_text_field( $data['environment'] ),
            'amount'             => floatval( $data['amount'] ),
            'currency'           => sanitize_text_field( $data['currency'] ),
            'transaction_type'   => sanitize_text_field( $data['transaction_type'] ),
            'status'             => sanitize_text_field( $data['status'] ),
            'customer_email'     => $data['customer_email'] ? sanitize_email( $data['customer_email'] ) : null,
            'customer_name'      => $data['customer_name'] ? sanitize_text_field( $data['customer_name'] ) : null,
            'square_payment_id'  => $data['square_payment_id'] ? sanitize_text_field( $data['square_payment_id'] ) : null,
            'square_receipt_url' => $data['square_receipt_url'] ? esc_url_raw( $data['square_receipt_url'] ) : null,
            'square_card_brand'  => $data['square_card_brand'] ? sanitize_text_field( $data['square_card_brand'] ) : null,
            'square_card_last4'  => $data['square_card_last4'] ? sanitize_text_field( substr( $data['square_card_last4'], 0, 4 ) ) : null,
            'error_code'         => $data['error_code'] ? sanitize_text_field( $data['error_code'] ) : null,
            'error_message'      => $data['error_message'] ? sanitize_textarea_field( $data['error_message'] ) : null,
            'request_data'       => $data['request_data'] ? wp_json_encode( $data['request_data'] ) : null,
            'response_data'      => $data['response_data'] ? wp_json_encode( $data['response_data'] ) : null,
        );

        $format = array(
            '%s', // transaction_id
            '%d', // booking_id
            '%s', // gateway
            '%s', // environment
            '%f', // amount
            '%s', // currency
            '%s', // transaction_type
            '%s', // status
            '%s', // customer_email
            '%s', // customer_name
            '%s', // square_payment_id
            '%s', // square_receipt_url
            '%s', // square_card_brand
            '%s', // square_card_last4
            '%s', // error_code
            '%s', // error_message
            '%s', // request_data
            '%s', // response_data
        );

        $result = $wpdb->insert( self::get_table_name(), $insert_data, $format );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get a transaction by ID.
     *
     * @param int $id Transaction ID.
     * @return array|null
     */
    public static function get( int $id ): ?array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE id = %d",
                self::get_table_name(),
                $id
            ),
            ARRAY_A
        );

        return $row ? self::format_row( $row ) : null;
    }

    /**
     * Get a transaction by transaction ID.
     *
     * @param string $transaction_id Transaction ID (UUID).
     * @return array|null
     */
    public static function get_by_transaction_id( string $transaction_id ): ?array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE transaction_id = %s",
                self::get_table_name(),
                sanitize_text_field( $transaction_id )
            ),
            ARRAY_A
        );

        return $row ? self::format_row( $row ) : null;
    }

    /**
     * Get transactions by booking ID.
     *
     * @param int $booking_id Booking ID.
     * @return array
     */
    public static function get_by_booking( int $booking_id ): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE booking_id = %d ORDER BY created_at DESC",
                self::get_table_name(),
                $booking_id
            ),
            ARRAY_A
        );

        return array_map( array( __CLASS__, 'format_row' ), $rows );
    }

    /**
     * Get transactions by Square payment ID.
     *
     * @param string $payment_id Square payment ID.
     * @return array|null
     */
    public static function get_by_square_payment_id( string $payment_id ): ?array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM %i WHERE square_payment_id = %s",
                self::get_table_name(),
                sanitize_text_field( $payment_id )
            ),
            ARRAY_A
        );

        return $row ? self::format_row( $row ) : null;
    }

    /**
     * Update a transaction.
     *
     * @param int   $id   Transaction ID.
     * @param array $data Data to update.
     * @return bool
     */
    public static function update( int $id, array $data ): bool {
        global $wpdb;

        $allowed_fields = array(
            'booking_id',
            'status',
            'square_payment_id',
            'square_receipt_url',
            'square_card_brand',
            'square_card_last4',
            'error_code',
            'error_message',
            'response_data',
        );

        $update_data = array();
        $format      = array();

        foreach ( $data as $key => $value ) {
            if ( ! in_array( $key, $allowed_fields, true ) ) {
                continue;
            }

            switch ( $key ) {
                case 'booking_id':
                    $update_data[ $key ] = absint( $value );
                    $format[]            = '%d';
                    break;
                case 'response_data':
                    $update_data[ $key ] = is_array( $value ) ? wp_json_encode( $value ) : $value;
                    $format[]            = '%s';
                    break;
                case 'square_receipt_url':
                    $update_data[ $key ] = esc_url_raw( $value );
                    $format[]            = '%s';
                    break;
                default:
                    $update_data[ $key ] = sanitize_text_field( $value );
                    $format[]            = '%s';
            }
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        $result = $wpdb->update(
            self::get_table_name(),
            $update_data,
            array( 'id' => $id ),
            $format,
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Update transaction status.
     *
     * @param int    $id     Transaction ID.
     * @param string $status New status.
     * @return bool
     */
    public static function update_status( int $id, string $status ): bool {
        return self::update( $id, array( 'status' => $status ) );
    }

    /**
     * Delete a transaction.
     *
     * @param int $id Transaction ID.
     * @return bool
     */
    public static function delete( int $id ): bool {
        global $wpdb;

        $result = $wpdb->delete(
            self::get_table_name(),
            array( 'id' => $id ),
            array( '%d' )
        );

        return false !== $result;
    }

    /**
     * Get all transactions with pagination.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function get_all( array $args = array() ): array {
        global $wpdb;

        $defaults = array(
            'per_page' => 20,
            'page'     => 1,
            'status'   => '',
            'gateway'  => '',
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );

        $args   = wp_parse_args( $args, $defaults );
        $offset = ( $args['page'] - 1 ) * $args['per_page'];

        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = sanitize_text_field( $args['status'] );
        }

        if ( ! empty( $args['gateway'] ) ) {
            $where[]  = 'gateway = %s';
            $values[] = sanitize_text_field( $args['gateway'] );
        }

        $where_clause = implode( ' AND ', $where );
        $table        = self::get_table_name();
        $orderby      = in_array( $args['orderby'], array( 'created_at', 'amount', 'status' ), true ) ? $args['orderby'] : 'created_at';
        $order        = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        $query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

        $values[] = $args['per_page'];
        $values[] = $offset;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare( $query, $values ), ARRAY_A );

        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $total = $wpdb->get_var( $wpdb->prepare( $count_query, array_slice( $values, 0, -2 ) ) );

        return array(
            'transactions' => array_map( array( __CLASS__, 'format_row' ), $rows ),
            'total'        => (int) $total,
            'per_page'     => $args['per_page'],
            'page'         => $args['page'],
            'total_pages'  => ceil( $total / $args['per_page'] ),
        );
    }

    /**
     * Format a database row.
     *
     * @param array $row Database row.
     * @return array
     */
    private static function format_row( array $row ): array {
        $row['id']         = (int) $row['id'];
        $row['booking_id'] = $row['booking_id'] ? (int) $row['booking_id'] : null;
        $row['amount']     = (float) $row['amount'];

        // Decode JSON fields
        if ( ! empty( $row['request_data'] ) ) {
            $row['request_data'] = json_decode( $row['request_data'], true );
        }
        if ( ! empty( $row['response_data'] ) ) {
            $row['response_data'] = json_decode( $row['response_data'], true );
        }

        return $row;
    }

    /**
     * Get total revenue for a date range.
     *
     * @param string $start_date Start date (Y-m-d).
     * @param string $end_date   End date (Y-m-d).
     * @return float
     */
    public static function get_revenue( string $start_date, string $end_date ): float {
        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM %i
                WHERE transaction_type = %s
                AND status = %s
                AND DATE(created_at) BETWEEN %s AND %s",
                self::get_table_name(),
                self::TYPE_PAYMENT,
                self::STATUS_COMPLETED,
                $start_date,
                $end_date
            )
        );

        return (float) $result;
    }

    /**
     * Get transaction count by status.
     *
     * @return array
     */
    public static function get_counts_by_status(): array {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT status, COUNT(*) as count FROM %i GROUP BY status",
                self::get_table_name()
            ),
            ARRAY_A
        );

        $counts = array(
            self::STATUS_PENDING   => 0,
            self::STATUS_COMPLETED => 0,
            self::STATUS_FAILED    => 0,
            self::STATUS_REFUNDED  => 0,
        );

        foreach ( $results as $row ) {
            $counts[ $row['status'] ] = (int) $row['count'];
        }

        return $counts;
    }
}
