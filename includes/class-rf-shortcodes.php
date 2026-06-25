<?php
/**
 * Shortcodes Handler
 *
 * @package RecessoFacile
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Shortcodes Class
 */
class RF_Shortcodes {

    /**
     * Initialize shortcodes
     */
    public static function init() {
        add_shortcode('recesso_facile_form', array(__CLASS__, 'withdrawal_form'));
        add_shortcode('recesso_facile_button', array(__CLASS__, 'withdrawal_button'));
        add_shortcode('recesso_facile_status', array(__CLASS__, 'withdrawal_status'));
    }

    /**
     * Withdrawal form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Form HTML
     */
    public static function withdrawal_form($atts) {
        $atts = shortcode_atts(array(
            'order_id' => '',
        ), $atts);

        ob_start();
        include RF_PLUGIN_DIR . 'templates/withdrawal-form.php';
        return ob_get_clean();
    }

    /**
     * Withdrawal button shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Button HTML
     */
    public static function withdrawal_button($atts) {
        $atts = shortcode_atts(array(
            'text' => __('Richiedi Recesso', 'recesso-facile'),
            'class' => 'rf-button',
        ), $atts);

        $page_url = get_permalink(get_option('rf_withdrawal_page'));

        return sprintf(
            '<a href="%s" class="%s">%s</a>',
            esc_url($page_url),
            esc_attr($atts['class']),
            esc_html($atts['text'])
        );
    }

    /**
     * Withdrawal status shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Status HTML
     */
    public static function withdrawal_status($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Devi effettuare il login per visualizzare le tue richieste.', 'recesso-facile') . '</p>';
        }

        $customer_id = get_current_user_id();

        global $wpdb;
        $withdrawals = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rf_withdrawals
                WHERE customer_id = %d
                ORDER BY created_at DESC
                LIMIT 10",
                $customer_id
            )
        );

        if (empty($withdrawals)) {
            return '<p>' . __('Non hai richieste di recesso.', 'recesso-facile') . '</p>';
        }

        ob_start();
        ?>
        <div class="rf-withdrawal-status">
            <h3><?php _e('Le tue richieste di recesso', 'recesso-facile'); ?></h3>
            <table class="rf-status-table">
                <thead>
                    <tr>
                        <th><?php _e('Numero', 'recesso-facile'); ?></th>
                        <th><?php _e('Ordine', 'recesso-facile'); ?></th>
                        <th><?php _e('Data', 'recesso-facile'); ?></th>
                        <th><?php _e('Status', 'recesso-facile'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($withdrawals as $withdrawal): ?>
                    <tr>
                        <td>#<?php echo esc_html($withdrawal->id); ?></td>
                        <td>#<?php echo esc_html($withdrawal->order_id); ?></td>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($withdrawal->request_date)); ?></td>
                        <td><span class="rf-status rf-status-<?php echo esc_attr($withdrawal->status); ?>">
                            <?php echo esc_html(ucfirst($withdrawal->status)); ?>
                        </span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}
