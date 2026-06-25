<?php
/**
 * AJAX Handler
 * Handles all AJAX requests
 *
 * @package RecessoFacile
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Ajax_Handler Class
 */
class RF_Ajax_Handler {

    /**
     * Initialize AJAX hooks
     */
    public static function init() {
        // Frontend AJAX
        add_action('wp_ajax_rf_verify_order', array(__CLASS__, 'verify_order'));
        add_action('wp_ajax_nopriv_rf_verify_order', array(__CLASS__, 'verify_order'));

        add_action('wp_ajax_rf_submit_withdrawal', array(__CLASS__, 'submit_withdrawal'));
        add_action('wp_ajax_nopriv_rf_submit_withdrawal', array(__CLASS__, 'submit_withdrawal'));

        add_action('wp_ajax_rf_check_eligibility', array(__CLASS__, 'check_eligibility'));
        add_action('wp_ajax_nopriv_rf_check_eligibility', array(__CLASS__, 'check_eligibility'));

        // Admin AJAX
        add_action('wp_ajax_rf_update_withdrawal_status', array(__CLASS__, 'update_withdrawal_status'));
        add_action('wp_ajax_rf_delete_withdrawal', array(__CLASS__, 'delete_withdrawal'));
        add_action('wp_ajax_rf_add_exception', array(__CLASS__, 'add_exception'));
        add_action('wp_ajax_rf_delete_exception', array(__CLASS__, 'delete_exception'));
        add_action('wp_ajax_rf_get_activities', array(__CLASS__, 'get_activities'));
    }

    /**
     * Verify order and email
     */
    public static function verify_order() {
        check_ajax_referer('recesso_facile_frontend', 'nonce');

        $customer_name = isset($_POST['customer_name']) ? sanitize_text_field($_POST['customer_name']) : '';
        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (!$customer_name || !$order_id || !$email) {
            wp_send_json_error(array(
                'message' => __('Dati mancanti.', 'recesso-facile')
            ));
        }

        // Validate customer name length
        if (strlen($customer_name) < 3) {
            wp_send_json_error(array(
                'message' => __('Inserisci un nome valido (almeno 3 caratteri).', 'recesso-facile')
            ));
        }

        // Verify order exists
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error(array(
                'message' => __('Ordine non trovato. Verifica il numero d\'ordine.', 'recesso-facile')
            ));
        }

        // Verify email
        if (strtolower($order->get_billing_email()) !== strtolower($email)) {
            wp_send_json_error(array(
                'message' => __('L\'email non corrisponde all\'ordine indicato.', 'recesso-facile')
            ));
        }

        // Check eligibility
        $eligibility = RF_Withdrawal_Service::check_eligibility($order_id, $email);
        if (is_wp_error($eligibility)) {
            wp_send_json_error(array(
                'message' => $eligibility->get_error_message()
            ));
        }

        // Check for pending withdrawal
        if (RF_Withdrawal_Service::has_pending_withdrawal($order_id)) {
            wp_send_json_error(array(
                'message' => __('Esiste già una richiesta di recesso in corso per questo ordine.', 'recesso-facile')
            ));
        }

        // Return order details
        $products = array();
        foreach ($order->get_items() as $item) {
            $products[] = array(
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => wc_price($item->get_total()),
            );
        }

        wp_send_json_success(array(
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'order_date' => date_i18n(get_option('date_format'), $order->get_date_created()->getTimestamp()),
            'order_total' => $order->get_formatted_order_total(),
            'products' => $products,
        ));
    }

    /**
     * Check eligibility
     */
    public static function check_eligibility() {
        check_ajax_referer('recesso_facile_frontend', 'nonce');

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (!$order_id || !$email) {
            wp_send_json_error(array(
                'message' => __('Dati mancanti.', 'recesso-facile')
            ));
        }

        $eligibility = RF_Withdrawal_Service::check_eligibility($order_id, $email);

        if (is_wp_error($eligibility)) {
            wp_send_json_error(array(
                'message' => $eligibility->get_error_message()
            ));
        }

        wp_send_json_success(array(
            'eligible' => true,
            'message' => __('L\'ordine è idoneo al recesso.', 'recesso-facile')
        ));
    }

    /**
     * Submit withdrawal request
     */
    public static function submit_withdrawal() {
        check_ajax_referer('recesso_facile_frontend', 'nonce');

        // Get and sanitize data
        $data = RF_Validator::sanitize_withdrawal_data($_POST);

        // Create withdrawal
        $result = RF_Withdrawal_Service::create_withdrawal($data);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'errors' => $result->get_error_data()
            ));
        }

        $withdrawal = RF_Withdrawal_Service::get_withdrawal($result);

        wp_send_json_success(array(
            'withdrawal_id' => $result,
            'receipt_hash' => $withdrawal->receipt_hash,
            'message' => __('Richiesta di recesso inviata con successo!', 'recesso-facile')
        ));
    }

    /**
     * Update withdrawal status (Admin)
     */
    public static function update_withdrawal_status() {
        check_ajax_referer('recesso_facile_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array(
                'message' => __('Permessi insufficienti.', 'recesso-facile')
            ));
        }

        $withdrawal_id = isset($_POST['withdrawal_id']) ? absint($_POST['withdrawal_id']) : 0;
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';

        if (!$withdrawal_id || !$new_status) {
            wp_send_json_error(array(
                'message' => __('Dati mancanti.', 'recesso-facile')
            ));
        }

        $result = RF_Withdrawal_Service::update_status($withdrawal_id, $new_status, $admin_notes);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        wp_send_json_success(array(
            'message' => __('Status aggiornato con successo.', 'recesso-facile')
        ));
    }

    /**
     * Delete withdrawal (Admin)
     */
    public static function delete_withdrawal() {
        check_ajax_referer('recesso_facile_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array(
                'message' => __('Permessi insufficienti.', 'recesso-facile')
            ));
        }

        $withdrawal_id = isset($_POST['withdrawal_id']) ? absint($_POST['withdrawal_id']) : 0;

        if (!$withdrawal_id) {
            wp_send_json_error(array(
                'message' => __('ID richiesta mancante.', 'recesso-facile')
            ));
        }

        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'rf_withdrawals',
            array('id' => $withdrawal_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array(
                'message' => __('Errore durante l\'eliminazione.', 'recesso-facile')
            ));
        }

        wp_send_json_success(array(
            'message' => __('Richiesta eliminata con successo.', 'recesso-facile')
        ));
    }

    /**
     * Add exception (Admin)
     */
    public static function add_exception() {
        check_ajax_referer('recesso_facile_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array(
                'message' => __('Permessi insufficienti.', 'recesso-facile')
            ));
        }

        $data = array(
            'product_id' => isset($_POST['product_id']) ? absint($_POST['product_id']) : null,
            'category_id' => isset($_POST['category_id']) ? absint($_POST['category_id']) : null,
            'exception_type' => isset($_POST['exception_type']) ? sanitize_text_field($_POST['exception_type']) : '',
            'reason' => isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '',
            'legal_reference' => isset($_POST['legal_reference']) ? sanitize_text_field($_POST['legal_reference']) : '',
        );

        $result = RF_Exception_Service::add_exception($data);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        wp_send_json_success(array(
            'exception_id' => $result,
            'message' => __('Eccezione aggiunta con successo.', 'recesso-facile')
        ));
    }

    /**
     * Delete exception (Admin)
     */
    public static function delete_exception() {
        check_ajax_referer('recesso_facile_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array(
                'message' => __('Permessi insufficienti.', 'recesso-facile')
            ));
        }

        $exception_id = isset($_POST['exception_id']) ? absint($_POST['exception_id']) : 0;

        if (!$exception_id) {
            wp_send_json_error(array(
                'message' => __('ID eccezione mancante.', 'recesso-facile')
            ));
        }

        $result = RF_Exception_Service::delete_exception($exception_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        wp_send_json_success(array(
            'message' => __('Eccezione eliminata con successo.', 'recesso-facile')
        ));
    }

    /**
     * Get activities (Admin)
     */
    public static function get_activities() {
        check_ajax_referer('recesso_facile_admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array(
                'message' => __('Permessi insufficienti.', 'recesso-facile')
            ));
        }

        $withdrawal_id = isset($_POST['withdrawal_id']) ? absint($_POST['withdrawal_id']) : 0;

        if (!$withdrawal_id) {
            wp_send_json_error(array(
                'message' => __('ID richiesta mancante.', 'recesso-facile')
            ));
        }

        $activities = RF_Activity_Logger::get_activities($withdrawal_id);

        wp_send_json_success(array(
            'activities' => $activities
        ));
    }
}
