<?php
/**
 * Update Handler
 * Handles plugin updates and database migrations
 *
 * @package RecessoFacile
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Update Class
 */
class RF_Update {

    /**
     * Current database version
     */
    const DB_VERSION = '1.0.1';

    /**
     * Check and run updates
     */
    public static function check_version() {
        $current_db_version = get_option('recesso_facile_db_version', '1.0.0');

        if (version_compare($current_db_version, self::DB_VERSION, '<')) {
            self::run_updates($current_db_version);
            update_option('recesso_facile_db_version', self::DB_VERSION);
        }
    }

    /**
     * Run necessary updates
     *
     * @param string $current_version Current database version
     */
    private static function run_updates($current_version) {
        // Update to 1.0.1 - Add customer_name column
        if (version_compare($current_version, '1.0.1', '<')) {
            self::update_1_0_1();
        }
    }

    /**
     * Update to version 1.0.1
     * Adds customer_name column to withdrawals table
     */
    private static function update_1_0_1() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'rf_withdrawals';

        // Check if column already exists
        $column_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = %s
                AND TABLE_NAME = %s
                AND COLUMN_NAME = 'customer_name'",
                DB_NAME,
                $table_name
            )
        );

        if (empty($column_exists)) {
            // Add customer_name column after customer_id
            $wpdb->query(
                "ALTER TABLE $table_name
                ADD COLUMN customer_name varchar(100) NOT NULL DEFAULT ''
                AFTER customer_id"
            );

            // Populate customer_name from order billing data for existing records
            $existing_withdrawals = $wpdb->get_results(
                "SELECT id, order_id FROM $table_name WHERE customer_name = ''"
            );

            foreach ($existing_withdrawals as $withdrawal) {
                $order = wc_get_order($withdrawal->order_id);
                if ($order) {
                    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                    $wpdb->update(
                        $table_name,
                        array('customer_name' => sanitize_text_field(trim($customer_name))),
                        array('id' => $withdrawal->id),
                        array('%s'),
                        array('%d')
                    );
                }
            }

            RF_Activity_Logger::log(
                null,
                'update',
                __('Database aggiornato alla versione 1.0.1 - Aggiunta colonna customer_name', 'recesso-facile')
            );
        }
    }
}
