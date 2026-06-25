<?php
/**
 * REST API Handler
 *
 * @package RecessoFacile\API
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_REST_API Class
 */
class RF_REST_API {

    /**
     * Namespace for API
     */
    const NAMESPACE = 'recesso-facile/v1';

    /**
     * Initialize REST API
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    /**
     * Register REST routes
     */
    public static function register_routes() {
        // Get withdrawals
        register_rest_route(self::NAMESPACE, '/withdrawals', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_withdrawals'),
            'permission_callback' => array(__CLASS__, 'check_permissions'),
        ));

        // Get single withdrawal
        register_rest_route(self::NAMESPACE, '/withdrawals/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_withdrawal'),
            'permission_callback' => array(__CLASS__, 'check_permissions'),
        ));

        // Create withdrawal
        register_rest_route(self::NAMESPACE, '/withdrawals', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'create_withdrawal'),
            'permission_callback' => '__return_true',
        ));

        // Update withdrawal status
        register_rest_route(self::NAMESPACE, '/withdrawals/(?P<id>\d+)/status', array(
            'methods' => 'PUT',
            'callback' => array(__CLASS__, 'update_status'),
            'permission_callback' => array(__CLASS__, 'check_permissions'),
        ));

        // Get statistics
        register_rest_route(self::NAMESPACE, '/statistics', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_statistics'),
            'permission_callback' => array(__CLASS__, 'check_permissions'),
        ));

        // Get exceptions
        register_rest_route(self::NAMESPACE, '/exceptions', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_exceptions'),
            'permission_callback' => array(__CLASS__, 'check_permissions'),
        ));
    }

    /**
     * Check permissions
     *
     * @return bool
     */
    public static function check_permissions() {
        return current_user_can('manage_woocommerce');
    }

    /**
     * Get withdrawals
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_withdrawals($request) {
        global $wpdb;

        $per_page = $request->get_param('per_page') ?: 20;
        $page = $request->get_param('page') ?: 1;
        $offset = ($page - 1) * $per_page;
        $status = $request->get_param('status');

        $where = '';
        if ($status) {
            $where = $wpdb->prepare('WHERE status = %s', $status);
        }

        $withdrawals = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rf_withdrawals
                $where
                ORDER BY created_at DESC
                LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rf_withdrawals $where");

        return new WP_REST_Response(array(
            'withdrawals' => $withdrawals,
            'total' => absint($total),
            'page' => $page,
            'per_page' => $per_page,
        ), 200);
    }

    /**
     * Get single withdrawal
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_withdrawal($request) {
        $id = $request->get_param('id');
        $withdrawal = RF_Withdrawal_Service::get_withdrawal($id);

        if (!$withdrawal) {
            return new WP_REST_Response(array(
                'error' => __('Richiesta non trovata.', 'recesso-facile')
            ), 404);
        }

        return new WP_REST_Response(array(
            'withdrawal' => $withdrawal
        ), 200);
    }

    /**
     * Create withdrawal
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function create_withdrawal($request) {
        $data = array(
            'order_id' => $request->get_param('order_id'),
            'email' => $request->get_param('email'),
            'reason' => $request->get_param('reason'),
            'additional_notes' => $request->get_param('additional_notes'),
            'refund_method' => $request->get_param('refund_method'),
            'refund_iban' => $request->get_param('refund_iban'),
            'accept_terms' => $request->get_param('accept_terms'),
            'double_confirmation' => $request->get_param('double_confirmation'),
        );

        $result = RF_Withdrawal_Service::create_withdrawal($data);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'error' => $result->get_error_message()
            ), 400);
        }

        return new WP_REST_Response(array(
            'withdrawal_id' => $result,
            'message' => __('Richiesta creata con successo.', 'recesso-facile')
        ), 201);
    }

    /**
     * Update withdrawal status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function update_status($request) {
        $id = $request->get_param('id');
        $new_status = $request->get_param('status');
        $admin_notes = $request->get_param('admin_notes');

        $result = RF_Withdrawal_Service::update_status($id, $new_status, $admin_notes);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'error' => $result->get_error_message()
            ), 400);
        }

        return new WP_REST_Response(array(
            'message' => __('Status aggiornato con successo.', 'recesso-facile')
        ), 200);
    }

    /**
     * Get statistics
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_statistics($request) {
        $stats = RF_Withdrawal_Service::get_statistics();

        return new WP_REST_Response(array(
            'statistics' => $stats
        ), 200);
    }

    /**
     * Get exceptions
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public static function get_exceptions($request) {
        $exceptions = RF_Exception_Service::get_exceptions();

        return new WP_REST_Response(array(
            'exceptions' => $exceptions
        ), 200);
    }
}
