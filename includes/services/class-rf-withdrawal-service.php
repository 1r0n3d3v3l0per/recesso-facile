<?php
/**
 * Withdrawal Service
 * Handles all withdrawal request logic
 *
 * @package RecessoFacile\Services
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Withdrawal_Service Class
 */
class RF_Withdrawal_Service {

    /**
     * Create a new withdrawal request
     *
     * @param array $data Withdrawal data
     * @return int|WP_Error Withdrawal ID or error
     */
    public static function create_withdrawal($data) {
        global $wpdb;

        // Throttle abusive/automated submissions (covers both the public AJAX
        // and REST entry points, neither of which is authenticated).
        $rate_limit = self::check_rate_limit();
        if (is_wp_error($rate_limit)) {
            return $rate_limit;
        }

        // Validate required fields
        $validation = RF_Validator::validate_withdrawal_request($data);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Check if order is eligible for withdrawal
        $eligibility = self::check_eligibility($data['order_id'], $data['email']);
        if (is_wp_error($eligibility)) {
            return $eligibility;
        }

        // Check for duplicate requests
        if (self::has_pending_withdrawal($data['order_id'])) {
            return new WP_Error(
                'duplicate_request',
                __('Esiste già una richiesta di recesso in corso per questo ordine.', 'recesso-facile')
            );
        }

        // Get order
        $order = wc_get_order($data['order_id']);
        if (!$order) {
            return new WP_Error('invalid_order', __('Ordine non valido.', 'recesso-facile'));
        }

        // Prepare products JSON
        $products_data = array();
        foreach ($order->get_items() as $item) {
            $products_data[] = array(
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => $item->get_total(),
            );
        }

        // Check if order has products
        if (empty($products_data)) {
            return new WP_Error('no_products', __('L\'ordine non contiene prodotti.', 'recesso-facile'));
        }

        // Generate receipt hash
        $receipt_hash = self::generate_receipt_hash($data, $order);

        // Insert withdrawal request
        $insert_data = array(
            'order_id' => absint($data['order_id']),
            'customer_id' => $order->get_customer_id(),
            'customer_name' => sanitize_text_field($data['customer_name']),
            'email' => sanitize_email($data['email']),
            'status' => 'pending',
            'reason' => isset($data['reason']) ? sanitize_textarea_field($data['reason']) : '',
            'additional_notes' => isset($data['additional_notes']) ? sanitize_textarea_field($data['additional_notes']) : '',
            'products_json' => wp_json_encode($products_data),
            'request_date' => current_time('mysql'),
            'receipt_hash' => $receipt_hash,
            'ip_address' => self::get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(substr($_SERVER['HTTP_USER_AGENT'], 0, 255)) : '',
            'refund_method' => isset($data['refund_method']) ? sanitize_text_field($data['refund_method']) : 'original',
            'refund_iban' => isset($data['refund_iban']) ? sanitize_text_field($data['refund_iban']) : '',
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'rf_withdrawals',
            $insert_data,
            array(
                '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
                '%s', '%s', '%s', '%s'
            )
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore durante il salvataggio della richiesta.', 'recesso-facile'));
        }

        $withdrawal_id = $wpdb->insert_id;

        // Log activity
        RF_Activity_Logger::log($withdrawal_id, 'created', __('Richiesta di recesso creata', 'recesso-facile'));

        // Send emails
        RF_Email_Service::send_customer_confirmation($withdrawal_id);
        RF_Email_Service::send_admin_notification($withdrawal_id);

        // Add order note
        $order->add_order_note(
            sprintf(
                __('Richiesta di recesso #%d ricevuta.', 'recesso-facile'),
                $withdrawal_id
            )
        );

        do_action('recesso_facile_withdrawal_created', $withdrawal_id, $data);

        return $withdrawal_id;
    }

    /**
     * Update withdrawal status
     *
     * @param int $withdrawal_id Withdrawal ID
     * @param string $new_status New status
     * @param string $admin_notes Optional admin notes
     * @return bool|WP_Error Success or error
     */
    public static function update_status($withdrawal_id, $new_status, $admin_notes = '') {
        global $wpdb;

        $valid_statuses = array('pending', 'approved', 'rejected', 'completed', 'cancelled');
        if (!in_array($new_status, $valid_statuses, true)) {
            return new WP_Error('invalid_status', __('Status non valido.', 'recesso-facile'));
        }

        $withdrawal = self::get_withdrawal($withdrawal_id);
        if (!$withdrawal) {
            return new WP_Error('not_found', __('Richiesta di recesso non trovata.', 'recesso-facile'));
        }

        $old_status = $withdrawal->status;

        $update_data = array(
            'status' => $new_status,
        );

        if ($admin_notes) {
            $update_data['admin_notes'] = sanitize_textarea_field($admin_notes);
        }

        if ($new_status === 'completed') {
            $update_data['completion_date'] = current_time('mysql');
        }

        // $update_data is built dynamically (1-3 keys), so a fixed positional
        // format array would misalign. All these columns are strings; passing
        // null lets WordPress format every value as %s by key.
        $result = $wpdb->update(
            $wpdb->prefix . 'rf_withdrawals',
            $update_data,
            array('id' => $withdrawal_id),
            null,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore durante l\'aggiornamento dello status.', 'recesso-facile'));
        }

        // Log activity
        RF_Activity_Logger::log(
            $withdrawal_id,
            'status_changed',
            sprintf(
                __('Status cambiato da %s a %s', 'recesso-facile'),
                $old_status,
                $new_status
            )
        );

        // Send status update email
        RF_Email_Service::send_status_update($withdrawal_id, $new_status);

        // Add order note
        $order = wc_get_order($withdrawal->order_id);
        if ($order) {
            $order->add_order_note(
                sprintf(
                    __('Richiesta di recesso #%d: status aggiornato a %s', 'recesso-facile'),
                    $withdrawal_id,
                    $new_status
                )
            );
        }

        do_action('recesso_facile_withdrawal_status_changed', $withdrawal_id, $new_status, $old_status);

        return true;
    }

    /**
     * Check if order is eligible for withdrawal
     *
     * @param int $order_id Order ID
     * @param string $email Customer email
     * @return bool|WP_Error True if eligible, error otherwise
     */
    public static function check_eligibility($order_id, $email) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('invalid_order', __('Ordine non trovato.', 'recesso-facile'));
        }

        // Verify email matches order
        if (strtolower($order->get_billing_email()) !== strtolower($email)) {
            return new WP_Error('email_mismatch', __('L\'email non corrisponde all\'ordine.', 'recesso-facile'));
        }

        // Check order status
        $allowed_statuses = apply_filters('recesso_facile_allowed_order_statuses', array('completed', 'processing'));
        if (!in_array($order->get_status(), $allowed_statuses, true)) {
            return new WP_Error(
                'invalid_order_status',
                __('L\'ordine non è in uno stato valido per il recesso.', 'recesso-facile')
            );
        }

        // Check withdrawal period
        $withdrawal_period = absint(get_option('rf_withdrawal_period', 14));
        $order_date = $order->get_date_completed() ?: $order->get_date_created();

        if (!$order_date) {
            return new WP_Error('invalid_date', __('Data ordine non valida.', 'recesso-facile'));
        }

        $days_since_order = floor((time() - $order_date->getTimestamp()) / DAY_IN_SECONDS);

        if ($days_since_order > $withdrawal_period) {
            return new WP_Error(
                'period_expired',
                sprintf(
                    __('Il periodo di recesso di %d giorni è scaduto.', 'recesso-facile'),
                    $withdrawal_period
                )
            );
        }

        // Check if any products have exceptions
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            $product = $item->get_product();

            if ($product && RF_Exception_Service::has_exception($product_id, $product->get_category_ids())) {
                $exception = RF_Exception_Service::get_product_exception($product_id);
                return new WP_Error(
                    'product_exception',
                    sprintf(
                        __('Il prodotto "%s" non è idoneo al recesso: %s', 'recesso-facile'),
                        $item->get_name(),
                        $exception ? $exception->reason : __('Prodotto escluso', 'recesso-facile')
                    )
                );
            }
        }

        return apply_filters('recesso_facile_check_eligibility', true, $order_id, $email);
    }

    /**
     * Get withdrawal by ID
     *
     * @param int $withdrawal_id Withdrawal ID
     * @return object|null Withdrawal object or null
     */
    public static function get_withdrawal($withdrawal_id) {
        global $wpdb;

        $withdrawal = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rf_withdrawals WHERE id = %d",
                $withdrawal_id
            )
        );

        return $withdrawal;
    }

    /**
     * Get withdrawals by order ID
     *
     * @param int $order_id Order ID
     * @return array Withdrawals
     */
    public static function get_by_order($order_id) {
        global $wpdb;

        $withdrawals = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rf_withdrawals WHERE order_id = %d ORDER BY created_at DESC",
                $order_id
            )
        );

        return $withdrawals;
    }

    /**
     * Check if order has pending withdrawal
     *
     * @param int $order_id Order ID
     * @return bool True if has pending withdrawal
     */
    public static function has_pending_withdrawal($order_id) {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rf_withdrawals
                WHERE order_id = %d AND status IN ('pending', 'approved')",
                $order_id
            )
        );

        return $count > 0;
    }

    /**
     * Generate receipt hash for legal compliance
     *
     * @param array $data Request data
     * @param WC_Order $order Order object
     * @return string SHA256 hash
     */
    private static function generate_receipt_hash($data, $order) {
        $hash_data = array(
            'order_id' => $order->get_id(),
            'order_key' => $order->get_order_key(),
            'email' => $data['email'],
            'timestamp' => time(),
            'site_url' => get_site_url(),
        );

        $hash_string = wp_json_encode($hash_data);
        return hash('sha256', $hash_string);
    }

    /**
     * Simple per-IP rate limiter for withdrawal creation.
     *
     * Allows a burst of requests within a sliding window using a transient.
     * Logged-in shop managers are exempt. Defaults: 5 requests / 10 minutes,
     * filterable via 'recesso_facile_rate_limit'.
     *
     * @return true|WP_Error
     */
    private static function check_rate_limit() {
        if (current_user_can('manage_woocommerce')) {
            return true;
        }

        $limits = apply_filters('recesso_facile_rate_limit', array(
            'max'    => 5,
            'window' => 10 * MINUTE_IN_SECONDS,
        ));

        $ip  = self::get_client_ip();
        $key = 'rf_rl_' . md5($ip);

        $count = (int) get_transient($key);
        if ($count >= (int) $limits['max']) {
            return new WP_Error(
                'rate_limited',
                __('Troppe richieste. Riprova tra qualche minuto.', 'recesso-facile')
            );
        }

        set_transient($key, $count + 1, (int) $limits['window']);

        return true;
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private static function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );

        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get withdrawal statistics
     *
     * @return array Statistics
     */
    public static function get_statistics() {
        global $wpdb;

        $table = $wpdb->prefix . 'rf_withdrawals';

        $stats = array(
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'"),
            'approved' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'approved'"),
            'rejected' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'rejected'"),
            'completed' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'completed'"),
            'this_month' => $wpdb->get_var(
                "SELECT COUNT(*) FROM $table
                WHERE YEAR(created_at) = YEAR(CURRENT_DATE)
                AND MONTH(created_at) = MONTH(CURRENT_DATE)"
            ),
        );

        return $stats;
    }

    /**
     * Delete old withdrawal requests
     *
     * @param int $days Delete requests older than X days
     * @return int Number of deleted requests
     */
    public static function delete_old_requests($days = 365) {
        global $wpdb;

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}rf_withdrawals
                WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
                AND status IN ('completed', 'rejected', 'cancelled')",
                $days
            )
        );

        if ($deleted > 0) {
            RF_Activity_Logger::log(
                null,
                'cleanup',
                sprintf(__('Eliminate %d richieste vecchie', 'recesso-facile'), $deleted)
            );
        }

        return $deleted;
    }
}
