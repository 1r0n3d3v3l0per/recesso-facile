<?php
/**
 * WooCommerce Integration
 * Adds withdrawal button to order pages
 *
 * @package RecessoFacile
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_WooCommerce_Integration Class
 */
class RF_WooCommerce_Integration {

    /**
     * Initialize hooks
     */
    public static function init() {
        // Add button to My Account > Order Details page
        add_action('woocommerce_order_details_after_order_table', array(__CLASS__, 'add_withdrawal_button_to_order_details'), 10, 1);

        // Add button to Order Confirmation page (Thank You page)
        add_action('woocommerce_thankyou', array(__CLASS__, 'add_withdrawal_info_to_thankyou'), 20, 1);

        // Add link to order confirmation email
        add_action('woocommerce_email_after_order_table', array(__CLASS__, 'add_withdrawal_link_to_email'), 20, 4);

        // Add metabox to admin order page
        add_action('add_meta_boxes', array(__CLASS__, 'add_admin_order_metabox'));
    }

    /**
     * Add withdrawal button to order details page (My Account)
     *
     * @param WC_Order $order Order object
     */
    public static function add_withdrawal_button_to_order_details($order) {
        if (!$order) {
            return;
        }

        // Check if withdrawal is enabled
        if (get_option('rf_enable_withdrawal', 'yes') !== 'yes') {
            return;
        }

        // Check if order is eligible
        $eligibility = RF_Withdrawal_Service::check_eligibility($order->get_id(), $order->get_billing_email());

        // Check if already has pending withdrawal
        $has_pending = RF_Withdrawal_Service::has_pending_withdrawal($order->get_id());

        ?>
        <section class="rf-withdrawal-section woocommerce-order-details">
            <h2 class="woocommerce-order-details__title"><?php _e('Diritto di Recesso', 'recesso-facile'); ?></h2>

            <?php if ($has_pending): ?>
                <div class="woocommerce-message woocommerce-message--info">
                    <?php _e('Hai già una richiesta di recesso in corso per questo ordine.', 'recesso-facile'); ?>
                </div>
            <?php elseif (is_wp_error($eligibility)): ?>
                <div class="woocommerce-info">
                    <strong><?php _e('Questo ordine non è idoneo al recesso:', 'recesso-facile'); ?></strong><br>
                    <?php echo esc_html($eligibility->get_error_message()); ?>
                </div>
            <?php else: ?>
                <p>
                    <?php
                    $withdrawal_period = absint(get_option('rf_withdrawal_period', 14));
                    printf(
                        __('Ai sensi del Codice del Consumo, hai %d giorni di tempo per recedere da questo ordine senza dover fornire alcuna motivazione.', 'recesso-facile'),
                        $withdrawal_period
                    );
                    ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(self::get_withdrawal_page_url()); ?>" class="button rf-withdrawal-button">
                        <?php echo esc_html(get_option('rf_button_text', __('Richiedi Recesso', 'recesso-facile'))); ?>
                    </a>
                </p>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Add withdrawal info to Thank You page
     *
     * @param int $order_id Order ID
     */
    public static function add_withdrawal_info_to_thankyou($order_id) {
        if (!$order_id) {
            return;
        }

        // Check if withdrawal is enabled
        if (get_option('rf_enable_withdrawal', 'yes') !== 'yes') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $eligibility = RF_Withdrawal_Service::check_eligibility($order_id, $order->get_billing_email());

        if (!is_wp_error($eligibility)) {
            ?>
            <section class="rf-withdrawal-thankyou woocommerce-order-details">
                <h2><?php _e('Diritto di Recesso', 'recesso-facile'); ?></h2>
                <p>
                    <?php
                    $withdrawal_period = absint(get_option('rf_withdrawal_period', 14));
                    printf(
                        __('Ricorda: hai %d giorni di tempo per recedere da questo acquisto. Puoi farlo facilmente dalla pagina del tuo ordine.', 'recesso-facile'),
                        $withdrawal_period
                    );
                    ?>
                </p>
            </section>
            <?php
        }
    }

    /**
     * Add withdrawal link to order confirmation email
     *
     * @param WC_Order $order Order object
     * @param bool $sent_to_admin Sent to admin
     * @param bool $plain_text Plain text email
     * @param WC_Email $email Email object
     */
    public static function add_withdrawal_link_to_email($order, $sent_to_admin, $plain_text, $email) {
        // Only add to customer emails (not admin)
        if ($sent_to_admin) {
            return;
        }

        // Only add to order confirmation emails
        if (!in_array($email->id, array('customer_completed_order', 'customer_processing_order'))) {
            return;
        }

        // Check if withdrawal is enabled
        if (get_option('rf_enable_withdrawal', 'yes') !== 'yes') {
            return;
        }

        $eligibility = RF_Withdrawal_Service::check_eligibility($order->get_id(), $order->get_billing_email());

        if (is_wp_error($eligibility)) {
            return;
        }

        $withdrawal_period = absint(get_option('rf_withdrawal_period', 14));
        $withdrawal_url = self::get_withdrawal_page_url();

        if ($plain_text) {
            echo "\n\n" . str_repeat('-', 50) . "\n\n";
            echo strtoupper(__('Diritto di Recesso', 'recesso-facile')) . "\n\n";
            printf(
                __('Hai %d giorni di tempo per recedere da questo acquisto.', 'recesso-facile'),
                $withdrawal_period
            );
            echo "\n\n";
            echo __('Per richiedere il recesso, visita:', 'recesso-facile') . "\n";
            echo esc_url($withdrawal_url) . "\n\n";
        } else {
            ?>
            <div style="margin: 40px 0; padding: 20px; background: #f7f7f7; border-left: 4px solid #2ea2cc;">
                <h2 style="margin-top: 0;"><?php _e('Diritto di Recesso', 'recesso-facile'); ?></h2>
                <p>
                    <?php
                    printf(
                        __('Hai %d giorni di tempo per recedere da questo acquisto senza dover fornire alcuna motivazione.', 'recesso-facile'),
                        $withdrawal_period
                    );
                    ?>
                </p>
                <p>
                    <a href="<?php echo esc_url($withdrawal_url); ?>"
                       style="display: inline-block; padding: 12px 24px; background: #2ea2cc; color: #ffffff; text-decoration: none; border-radius: 3px; font-weight: bold;">
                        <?php echo esc_html(get_option('rf_button_text', __('Richiedi Recesso', 'recesso-facile'))); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Add metabox to admin order page
     */
    public static function add_admin_order_metabox() {
        // Resolve the correct screen for HPOS (custom order tables) vs the
        // legacy post-based order screen, guarding the container lookup so it
        // never fatals on environments where HPOS classes aren't available.
        $screen = 'shop_order';

        if (function_exists('wc_get_container')
            && class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController')) {
            try {
                $controller = wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class);
                if ($controller && $controller->custom_orders_table_usage_is_enabled()) {
                    $screen = wc_get_page_screen_id('shop-order');
                }
            } catch (\Exception $e) {
                $screen = 'shop_order';
            }
        }

        add_meta_box(
            'rf_order_withdrawal',
            __('Recesso Facile', 'recesso-facile'),
            array(__CLASS__, 'render_admin_order_metabox'),
            $screen,
            'side',
            'default'
        );
    }

    /**
     * Render admin order metabox
     *
     * @param WP_Post|WC_Order $post_or_order Post or Order object
     */
    public static function render_admin_order_metabox($post_or_order) {
        $order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order($post_or_order->ID);

        if (!$order) {
            return;
        }

        $order_id = $order->get_id();
        $withdrawals = RF_Withdrawal_Service::get_by_order($order_id);
        $eligibility = RF_Withdrawal_Service::check_eligibility($order_id, $order->get_billing_email());

        ?>
        <div class="rf-admin-order-metabox">
            <?php if (!empty($withdrawals)): ?>
                <h4><?php _e('Richieste di Recesso', 'recesso-facile'); ?></h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($withdrawals as $withdrawal): ?>
                    <li>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=recesso-facile-requests&action=view&id=' . $withdrawal->id)); ?>">
                            #<?php echo esc_html($withdrawal->id); ?>
                        </a>
                        - <span class="rf-status-badge rf-status-<?php echo esc_attr($withdrawal->status); ?>">
                            <?php echo esc_html(ucfirst($withdrawal->status)); ?>
                        </span>
                        <br>
                        <small><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($withdrawal->request_date))); ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><em><?php _e('Nessuna richiesta di recesso per questo ordine.', 'recesso-facile'); ?></em></p>
            <?php endif; ?>

            <hr>

            <h4><?php _e('Idoneità Recesso', 'recesso-facile'); ?></h4>
            <?php if (is_wp_error($eligibility)): ?>
                <p style="color: #dc3232;">
                    <strong><?php _e('Non idoneo:', 'recesso-facile'); ?></strong><br>
                    <?php echo esc_html($eligibility->get_error_message()); ?>
                </p>
            <?php else: ?>
                <p style="color: #46b450;">
                    <strong><?php _e('✓ Idoneo al recesso', 'recesso-facile'); ?></strong>
                </p>
                <?php
                $withdrawal_period = absint(get_option('rf_withdrawal_period', 14));
                $order_date = $order->get_date_completed() ?: $order->get_date_created();
                if ($order_date) {
                    $days_since_order = floor((time() - $order_date->getTimestamp()) / DAY_IN_SECONDS);
                    $days_remaining = $withdrawal_period - $days_since_order;
                    ?>
                    <p>
                        <small>
                            <?php
                            printf(
                                __('Giorni rimanenti: %d di %d', 'recesso-facile'),
                                max(0, $days_remaining),
                                $withdrawal_period
                            );
                            ?>
                        </small>
                    </p>
                <?php } ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get withdrawal page URL
     *
     * @return string URL
     */
    private static function get_withdrawal_page_url() {
        $page_id = get_option('rf_withdrawal_page');

        if ($page_id && get_post($page_id)) {
            return get_permalink($page_id);
        }

        return home_url('/richiesta-recesso/');
    }
}
