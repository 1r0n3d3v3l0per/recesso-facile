<?php
/**
 * Activity Logger Service
 * Handles activity logging for audit trail
 *
 * @package RecessoFacile\Services
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Activity_Logger Class
 */
class RF_Activity_Logger {

    /**
     * Log an activity
     *
     * @param int|null $withdrawal_id Withdrawal ID (null for system activities)
     * @param string $action Action type
     * @param string $description Description
     * @param array $metadata Additional metadata
     * @return int|false Activity ID or false on failure
     */
    public static function log($withdrawal_id, $action, $description, $metadata = array()) {
        // Check if activity logging is enabled
        if (get_option('rf_enable_activity_log', 'yes') !== 'yes') {
            return false;
        }

        global $wpdb;

        $insert_data = array(
            'withdrawal_id' => $withdrawal_id ? absint($withdrawal_id) : null,
            'action' => sanitize_key($action),
            'description' => sanitize_text_field($description),
            'user_id' => get_current_user_id(),
            'ip_address' => self::get_client_ip(),
            'metadata' => !empty($metadata) ? wp_json_encode($metadata) : null,
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'rf_activity_log',
            $insert_data,
            array('%d', '%s', '%s', '%d', '%s', '%s')
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get activities for a withdrawal
     *
     * @param int $withdrawal_id Withdrawal ID
     * @param array $args Query arguments
     * @return array Activities
     */
    public static function get_activities($withdrawal_id, $args = array()) {
        global $wpdb;

        $defaults = array(
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        // Whitelist for ORDER BY to prevent SQL injection
        $allowed_orderby = array('created_at', 'id', 'action');
        $allowed_order = array('ASC', 'DESC');

        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = in_array(strtoupper($args['order']), $allowed_order, true) ? strtoupper($args['order']) : 'DESC';

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rf_activity_log
            WHERE withdrawal_id = %d
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d",
            $withdrawal_id,
            $args['limit'],
            $args['offset']
        );

        $activities = $wpdb->get_results($query);

        // Decode metadata for each activity
        foreach ($activities as $activity) {
            if ($activity->metadata) {
                $activity->metadata = json_decode($activity->metadata, true);
            }
        }

        return $activities;
    }

    /**
     * Get all activities
     *
     * @param array $args Query arguments
     * @return array Activities
     */
    public static function get_all_activities($args = array()) {
        global $wpdb;

        $defaults = array(
            'action' => '',
            'user_id' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 100,
            'offset' => 0,
            'date_from' => '',
            'date_to' => '',
        );

        $args = wp_parse_args($args, $defaults);

        // Whitelist for ORDER BY to prevent SQL injection
        $allowed_orderby = array('created_at', 'id', 'action', 'withdrawal_id', 'user_id');
        $allowed_order = array('ASC', 'DESC');

        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = in_array(strtoupper($args['order']), $allowed_order, true) ? strtoupper($args['order']) : 'DESC';

        $where = array();

        if ($args['action']) {
            $where[] = $wpdb->prepare('action = %s', $args['action']);
        }

        if ($args['user_id']) {
            $where[] = $wpdb->prepare('user_id = %d', $args['user_id']);
        }

        if ($args['date_from']) {
            $where[] = $wpdb->prepare('created_at >= %s', $args['date_from']);
        }

        if ($args['date_to']) {
            $where[] = $wpdb->prepare('created_at <= %s', $args['date_to']);
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rf_activity_log
            $where_clause
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );

        $activities = $wpdb->get_results($query);

        // Decode metadata
        foreach ($activities as $activity) {
            if ($activity->metadata) {
                $activity->metadata = json_decode($activity->metadata, true);
            }
        }

        return $activities;
    }

    /**
     * Get activity count
     *
     * @param array $args Query arguments
     * @return int Count
     */
    public static function get_count($args = array()) {
        global $wpdb;

        $where = array();

        if (!empty($args['action'])) {
            $where[] = $wpdb->prepare('action = %s', $args['action']);
        }

        if (!empty($args['withdrawal_id'])) {
            $where[] = $wpdb->prepare('withdrawal_id = %d', $args['withdrawal_id']);
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rf_activity_log $where_clause"
        );

        return absint($count);
    }

    /**
     * Delete old activities
     *
     * @param int $days Delete activities older than X days
     * @return int Number of deleted activities
     */
    public static function delete_old_activities($days = 365) {
        global $wpdb;

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}rf_activity_log
                WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );

        return $deleted ? $deleted : 0;
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
     * Get action types
     *
     * @return array Action types with labels
     */
    public static function get_action_types() {
        return array(
            'created' => __('Richiesta creata', 'recesso-facile'),
            'status_changed' => __('Status modificato', 'recesso-facile'),
            'email_sent' => __('Email inviata', 'recesso-facile'),
            'pdf_generated' => __('PDF generato', 'recesso-facile'),
            'exception_added' => __('Eccezione aggiunta', 'recesso-facile'),
            'exception_deleted' => __('Eccezione eliminata', 'recesso-facile'),
            'settings_updated' => __('Impostazioni aggiornate', 'recesso-facile'),
            'cleanup' => __('Pulizia automatica', 'recesso-facile'),
        );
    }

    /**
     * Get action label
     *
     * @param string $action Action type
     * @return string Label
     */
    public static function get_action_label($action) {
        $types = self::get_action_types();
        return isset($types[$action]) ? $types[$action] : $action;
    }
}
