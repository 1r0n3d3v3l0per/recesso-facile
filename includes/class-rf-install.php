<?php
/**
 * Installation and activation handler
 *
 * @package RecessoFacile
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Install Class
 */
class RF_Install {

    /**
     * Database version
     */
    const DB_VERSION = '1.0.0';

    /**
     * Activate plugin
     */
    public static function activate() {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(RF_PLUGIN_FILE));
            wp_die(
                __('Recesso Facile richiede WooCommerce. Installa e attiva WooCommerce prima di attivare questo plugin.', 'recesso-facile'),
                'Plugin dependency check',
                array('back_link' => true)
            );
        }

        self::create_tables();
        self::create_default_options();
        self::create_withdrawal_page();
        self::schedule_events();

        // Save version
        update_option('recesso_facile_version', RF_VERSION);
        update_option('recesso_facile_db_version', self::DB_VERSION);

        // Set activation flag for redirect
        set_transient('recesso_facile_activated', 1, 30);

        do_action('recesso_facile_activated');
    }

    /**
     * Deactivate plugin
     */
    public static function deactivate() {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        self::clear_scheduled_events();

        do_action('recesso_facile_deactivated');
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table for withdrawal requests
        $table_withdrawals = $wpdb->prefix . 'rf_withdrawals';

        $sql_withdrawals = "CREATE TABLE IF NOT EXISTS $table_withdrawals (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            customer_id bigint(20) UNSIGNED DEFAULT 0,
            customer_name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            reason text,
            additional_notes text,
            products_json longtext,
            request_date datetime NOT NULL,
            completion_date datetime DEFAULT NULL,
            receipt_hash varchar(64),
            ip_address varchar(45),
            user_agent varchar(255),
            refund_method varchar(50),
            refund_iban varchar(34),
            admin_notes text,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY customer_id (customer_id),
            KEY email (email),
            KEY status (status),
            KEY request_date (request_date),
            KEY receipt_hash (receipt_hash)
        ) $charset_collate;";

        // Table for product exceptions
        $table_exceptions = $wpdb->prefix . 'rf_product_exceptions';

        $sql_exceptions = "CREATE TABLE IF NOT EXISTS $table_exceptions (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id bigint(20) UNSIGNED DEFAULT NULL,
            category_id bigint(20) UNSIGNED DEFAULT NULL,
            exception_type varchar(50) NOT NULL,
            reason text NOT NULL,
            legal_reference varchar(100),
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY category_id (category_id),
            KEY exception_type (exception_type),
            KEY active (active)
        ) $charset_collate;";

        // Table for activity log
        $table_activity = $wpdb->prefix . 'rf_activity_log';

        $sql_activity = "CREATE TABLE IF NOT EXISTS $table_activity (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            withdrawal_id bigint(20) UNSIGNED DEFAULT NULL,
            action varchar(50) NOT NULL,
            description text,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            ip_address varchar(45),
            metadata longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY withdrawal_id (withdrawal_id),
            KEY action (action),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_withdrawals);
        dbDelta($sql_exceptions);
        dbDelta($sql_activity);
    }

    /**
     * Create default options
     */
    private static function create_default_options() {
        $default_options = array(
            // General settings
            'rf_enable_withdrawal' => 'yes',
            'rf_withdrawal_period' => '14',
            'rf_enable_sticky_button' => 'yes',
            'rf_button_text' => __('Richiedi Recesso', 'recesso-facile'),
            'rf_button_position' => 'bottom-right',

            // Email settings
            'rf_enable_customer_email' => 'yes',
            'rf_enable_admin_email' => 'yes',
            'rf_admin_email' => get_option('admin_email'),
            'rf_email_from_name' => get_bloginfo('name'),
            'rf_email_from_address' => get_option('admin_email'),

            // Form settings
            'rf_require_reason' => 'yes',
            'rf_enable_additional_notes' => 'yes',
            'rf_enable_guest_withdrawal' => 'yes',

            // PDF settings
            'rf_enable_pdf' => 'yes',
            'rf_pdf_company_name' => get_bloginfo('name'),
            'rf_pdf_company_address' => '',
            'rf_pdf_company_vat' => '',

            // Legal settings
            'rf_terms_page' => '',
            'rf_privacy_page' => '',
            'rf_enable_double_confirmation' => 'yes',

            // Refund settings
            'rf_default_refund_method' => 'original',
            'rf_enable_bank_transfer' => 'yes',
            'rf_enable_store_credit' => 'no',

            // Advanced settings
            'rf_enable_activity_log' => 'yes',
            'rf_auto_delete_old_requests' => 'no',
            'rf_delete_after_days' => '365',
        );

        foreach ($default_options as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Create withdrawal page
     */
    private static function create_withdrawal_page() {
        $page_id = get_option('rf_withdrawal_page');

        // Check if page exists
        if ($page_id && get_post($page_id)) {
            return;
        }

        // Create page
        $page_data = array(
            'post_title' => __('Richiesta di Recesso', 'recesso-facile'),
            'post_content' => '[recesso_facile_form]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id(),
            'comment_status' => 'closed',
        );

        $page_id = wp_insert_post($page_data);

        if ($page_id && !is_wp_error($page_id)) {
            update_option('rf_withdrawal_page', $page_id);
        }
    }

    /**
     * Schedule events
     */
    private static function schedule_events() {
        if (!wp_next_scheduled('recesso_facile_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'recesso_facile_daily_cleanup');
        }
    }

    /**
     * Clear scheduled events
     */
    private static function clear_scheduled_events() {
        wp_clear_scheduled_hook('recesso_facile_daily_cleanup');
    }

    /**
     * Drop tables (used on uninstall)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'rf_withdrawals',
            $wpdb->prefix . 'rf_product_exceptions',
            $wpdb->prefix . 'rf_activity_log',
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Delete all options (used on uninstall)
     */
    public static function delete_options() {
        global $wpdb;

        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'rf_%'");
        delete_option('recesso_facile_version');
        delete_option('recesso_facile_db_version');
    }
}
