<?php
/**
 * Exception Service
 * Handles product and category exceptions (Art. 59)
 *
 * @package RecessoFacile\Services
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Exception_Service Class
 */
class RF_Exception_Service {

    /**
     * Add exception for product or category
     *
     * @param array $data Exception data
     * @return int|WP_Error Exception ID or error
     */
    public static function add_exception($data) {
        global $wpdb;

        // Validate
        $validation = RF_Validator::validate_exception($data);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Check for duplicate
        if (isset($data['product_id']) && self::has_product_exception($data['product_id'])) {
            return new WP_Error('duplicate', __('Esiste già un\'eccezione per questo prodotto.', 'recesso-facile'));
        }

        $insert_data = array(
            'product_id' => !empty($data['product_id']) ? absint($data['product_id']) : null,
            'category_id' => !empty($data['category_id']) ? absint($data['category_id']) : null,
            'exception_type' => sanitize_text_field($data['exception_type']),
            'reason' => sanitize_textarea_field($data['reason']),
            'legal_reference' => isset($data['legal_reference']) ? sanitize_text_field($data['legal_reference']) : '',
            'active' => isset($data['active']) ? (int) $data['active'] : 1,
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'rf_product_exceptions',
            $insert_data,
            array('%d', '%d', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore durante il salvataggio dell\'eccezione.', 'recesso-facile'));
        }

        $exception_id = $wpdb->insert_id;

        RF_Activity_Logger::log(
            null,
            'exception_added',
            sprintf(__('Eccezione #%d aggiunta', 'recesso-facile'), $exception_id)
        );

        do_action('recesso_facile_exception_added', $exception_id, $data);

        return $exception_id;
    }

    /**
     * Update exception
     *
     * @param int $exception_id Exception ID
     * @param array $data Updated data
     * @return bool|WP_Error Success or error
     */
    public static function update_exception($exception_id, $data) {
        global $wpdb;

        $update_data = array();

        if (isset($data['exception_type'])) {
            $update_data['exception_type'] = sanitize_text_field($data['exception_type']);
        }

        if (isset($data['reason'])) {
            $update_data['reason'] = sanitize_textarea_field($data['reason']);
        }

        if (isset($data['legal_reference'])) {
            $update_data['legal_reference'] = sanitize_text_field($data['legal_reference']);
        }

        if (isset($data['active'])) {
            $update_data['active'] = (int) $data['active'];
        }

        if (empty($update_data)) {
            return new WP_Error('no_data', __('Nessun dato da aggiornare.', 'recesso-facile'));
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'rf_product_exceptions',
            $update_data,
            array('id' => $exception_id),
            array('%s', '%s', '%s', '%d'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore durante l\'aggiornamento dell\'eccezione.', 'recesso-facile'));
        }

        do_action('recesso_facile_exception_updated', $exception_id, $data);

        return true;
    }

    /**
     * Delete exception
     *
     * @param int $exception_id Exception ID
     * @return bool|WP_Error Success or error
     */
    public static function delete_exception($exception_id) {
        global $wpdb;

        $result = $wpdb->delete(
            $wpdb->prefix . 'rf_product_exceptions',
            array('id' => $exception_id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Errore durante l\'eliminazione dell\'eccezione.', 'recesso-facile'));
        }

        RF_Activity_Logger::log(
            null,
            'exception_deleted',
            sprintf(__('Eccezione #%d eliminata', 'recesso-facile'), $exception_id)
        );

        do_action('recesso_facile_exception_deleted', $exception_id);

        return true;
    }

    /**
     * Check if product has exception
     *
     * @param int $product_id Product ID
     * @param array $category_ids Category IDs
     * @return bool True if has exception
     */
    public static function has_exception($product_id, $category_ids = array()) {
        global $wpdb;

        // Check product exception
        $product_exception = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rf_product_exceptions
                WHERE product_id = %d AND active = 1",
                $product_id
            )
        );

        if ($product_exception > 0) {
            return true;
        }

        // Check category exceptions
        if (!empty($category_ids)) {
            // Sanitize all category IDs to integers
            $category_ids = array_map('absint', $category_ids);
            $category_ids = array_filter($category_ids); // Remove zeros

            if (!empty($category_ids)) {
                $placeholders = implode(',', array_fill(0, count($category_ids), '%d'));
                $category_exception = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}rf_product_exceptions
                        WHERE category_id IN ($placeholders) AND active = 1",
                        ...$category_ids
                    )
                );

                if ($category_exception > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get exception by product ID
     *
     * @param int $product_id Product ID
     * @return object|null Exception object or null
     */
    public static function get_product_exception($product_id) {
        global $wpdb;

        $exception = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rf_product_exceptions
                WHERE product_id = %d AND active = 1
                ORDER BY created_at DESC LIMIT 1",
                $product_id
            )
        );

        return $exception;
    }

    /**
     * Check if product has active exception
     *
     * @param int $product_id Product ID
     * @return bool True if has active exception
     */
    public static function has_product_exception($product_id) {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}rf_product_exceptions
                WHERE product_id = %d AND active = 1",
                $product_id
            )
        );

        return $count > 0;
    }

    /**
     * Get all exceptions
     *
     * @param array $args Query arguments
     * @return array Exceptions
     */
    public static function get_exceptions($args = array()) {
        global $wpdb;

        $defaults = array(
            'active_only' => true,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 100,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        // Whitelist for ORDER BY to prevent SQL injection
        $allowed_orderby = array('created_at', 'id', 'exception_type', 'product_id', 'category_id');
        $allowed_order = array('ASC', 'DESC');

        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = in_array(strtoupper($args['order']), $allowed_order, true) ? strtoupper($args['order']) : 'DESC';

        $where = array();
        if ($args['active_only']) {
            $where[] = 'active = 1';
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rf_product_exceptions
            $where_clause
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );

        $exceptions = $wpdb->get_results($query);

        return $exceptions;
    }

    /**
     * Get exception types with labels
     *
     * @return array Exception types
     */
    public static function get_exception_types() {
        return array(
            'art_59_b' => __('Art. 59(b) - Servizi completamente eseguiti', 'recesso-facile'),
            'art_59_c' => __('Art. 59(c) - Beni confezionati su misura o personalizzati', 'recesso-facile'),
            'art_59_d' => __('Art. 59(d) - Beni deteriorabili rapidamente', 'recesso-facile'),
            'art_59_e' => __('Art. 59(e) - Beni sigillati aperti (igiene/salute)', 'recesso-facile'),
            'art_59_f' => __('Art. 59(f) - Beni inscindibilmente mescolati', 'recesso-facile'),
            'art_59_g' => __('Art. 59(g) - Bevande alcoliche', 'recesso-facile'),
            'art_59_h' => __('Art. 59(h) - Manutenzione o riparazione urgente', 'recesso-facile'),
            'art_59_i' => __('Art. 59(i) - Registrazioni audio/video/software sigillati aperti', 'recesso-facile'),
            'art_59_l' => __('Art. 59(l) - Giornali, periodici, riviste', 'recesso-facile'),
            'art_59_m' => __('Art. 59(m) - Aste pubbliche', 'recesso-facile'),
            'art_59_n' => __('Art. 59(n) - Alloggi, trasporto, noleggio auto, ristorazione', 'recesso-facile'),
            'custom' => __('Eccezione personalizzata', 'recesso-facile'),
        );
    }

    /**
     * Get exception type label
     *
     * @param string $type Exception type
     * @return string Label
     */
    public static function get_exception_type_label($type) {
        $types = self::get_exception_types();
        return isset($types[$type]) ? $types[$type] : $type;
    }

    /**
     * Get exception count
     *
     * @return int Count
     */
    public static function get_count() {
        global $wpdb;

        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rf_product_exceptions WHERE active = 1"
        );

        return absint($count);
    }
}
