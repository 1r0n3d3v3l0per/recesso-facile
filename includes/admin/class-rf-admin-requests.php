<?php
/**
 * Admin Requests Page
 *
 * @package RecessoFacile\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Admin_Requests Class
 */
class RF_Admin_Requests {

    /**
     * Render requests page
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'recesso-facile'));
        }

        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';

        switch ($action) {
            case 'view':
                self::render_view_page();
                break;
            default:
                self::render_list_page();
                break;
        }
    }

    /**
     * Render list page
     */
    private static function render_list_page() {
        global $wpdb;

        $per_page = 20;
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $offset = ($current_page - 1) * $per_page;
        $status_filter = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';

        $where = '';
        if ($status_filter) {
            $where = $wpdb->prepare('WHERE status = %s', $status_filter);
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rf_withdrawals $where");
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

        $total_pages = $per_page > 0 ? ceil($total / $per_page) : 1;

        ?>
        <div class="wrap">
            <h1><?php _e('Richieste di Recesso', 'recesso-facile'); ?></h1>

            <!-- Status Filter -->
            <ul class="subsubsub">
                <li><a href="?page=recesso-facile-requests" class="<?php echo empty($status_filter) ? 'current' : ''; ?>">
                    <?php _e('Tutte', 'recesso-facile'); ?>
                </a> | </li>
                <li><a href="?page=recesso-facile-requests&status=pending" class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">
                    <?php _e('In Attesa', 'recesso-facile'); ?>
                </a> | </li>
                <li><a href="?page=recesso-facile-requests&status=approved" class="<?php echo $status_filter === 'approved' ? 'current' : ''; ?>">
                    <?php _e('Approvate', 'recesso-facile'); ?>
                </a> | </li>
                <li><a href="?page=recesso-facile-requests&status=completed" class="<?php echo $status_filter === 'completed' ? 'current' : ''; ?>">
                    <?php _e('Completate', 'recesso-facile'); ?>
                </a> | </li>
                <li><a href="?page=recesso-facile-requests&status=rejected" class="<?php echo $status_filter === 'rejected' ? 'current' : ''; ?>">
                    <?php _e('Rifiutate', 'recesso-facile'); ?>
                </a></li>
            </ul>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="50"><?php _e('ID', 'recesso-facile'); ?></th>
                        <th><?php _e('Ordine', 'recesso-facile'); ?></th>
                        <th><?php _e('Cliente', 'recesso-facile'); ?></th>
                        <th><?php _e('Data Richiesta', 'recesso-facile'); ?></th>
                        <th><?php _e('Status', 'recesso-facile'); ?></th>
                        <th><?php _e('Metodo Rimborso', 'recesso-facile'); ?></th>
                        <th><?php _e('Azioni', 'recesso-facile'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($withdrawals)): ?>
                    <tr>
                        <td colspan="7"><?php _e('Nessuna richiesta trovata.', 'recesso-facile'); ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($withdrawals as $withdrawal): ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($withdrawal->id); ?></strong></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('post.php?post=' . $withdrawal->order_id . '&action=edit')); ?>">
                                    #<?php echo esc_html($withdrawal->order_id); ?>
                                </a>
                            </td>
                            <td>
                                <strong><?php echo esc_html($withdrawal->customer_name); ?></strong><br>
                                <?php echo esc_html($withdrawal->email); ?><br>
                                <small class="description">IP: <?php echo esc_html($withdrawal->ip_address); ?></small>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($withdrawal->request_date))); ?></td>
                            <td>
                                <span class="rf-status-badge rf-status-<?php echo esc_attr($withdrawal->status); ?>">
                                    <?php echo esc_html(ucfirst($withdrawal->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($withdrawal->refund_method); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=recesso-facile-requests&action=view&id=' . $withdrawal->id)); ?>" class="button button-small">
                                    <?php _e('Visualizza', 'recesso-facile'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page,
                    ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render view/edit page
     */
    private static function render_view_page() {
        $withdrawal_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $withdrawal = RF_Withdrawal_Service::get_withdrawal($withdrawal_id);

        if (!$withdrawal) {
            wp_die(__('Richiesta non trovata.', 'recesso-facile'));
        }

        $order = wc_get_order($withdrawal->order_id);
        $products = json_decode($withdrawal->products_json, true);
        if (!is_array($products)) {
            $products = array();
        }
        $activities = RF_Activity_Logger::get_activities($withdrawal_id);

        ?>
        <div class="wrap">
            <h1><?php printf(__('Richiesta di Recesso #%d', 'recesso-facile'), $withdrawal->id); ?></h1>

            <div class="rf-withdrawal-detail">
                <div class="rf-detail-main">
                    <!-- Withdrawal Info -->
                    <div class="postbox">
                        <h2><?php _e('Informazioni Richiesta', 'recesso-facile'); ?></h2>
                        <div class="inside">
                            <table class="widefat">
                                <tr>
                                    <th><?php _e('Status:', 'recesso-facile'); ?></th>
                                    <td>
                                        <span class="rf-status-badge rf-status-<?php echo esc_attr($withdrawal->status); ?>">
                                            <?php echo esc_html(ucfirst($withdrawal->status)); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e('Ordine:', 'recesso-facile'); ?></th>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $withdrawal->order_id . '&action=edit')); ?>">
                                            #<?php echo esc_html($withdrawal->order_id); ?>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e('Nome Cliente:', 'recesso-facile'); ?></th>
                                    <td><strong><?php echo esc_html($withdrawal->customer_name); ?></strong></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Email Cliente:', 'recesso-facile'); ?></th>
                                    <td><?php echo esc_html($withdrawal->email); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Data Richiesta:', 'recesso-facile'); ?></th>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($withdrawal->request_date))); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Indirizzo IP:', 'recesso-facile'); ?></th>
                                    <td><?php echo esc_html($withdrawal->ip_address); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Hash Ricevuta:', 'recesso-facile'); ?></th>
                                    <td><code><?php echo esc_html($withdrawal->receipt_hash); ?></code></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Reason -->
                    <?php if ($withdrawal->reason): ?>
                    <div class="postbox">
                        <h2><?php _e('Motivazione', 'recesso-facile'); ?></h2>
                        <div class="inside">
                            <p><?php echo esc_html($withdrawal->reason); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Additional Notes -->
                    <?php if ($withdrawal->additional_notes): ?>
                    <div class="postbox">
                        <h2><?php _e('Note Aggiuntive', 'recesso-facile'); ?></h2>
                        <div class="inside">
                            <p><?php echo esc_html($withdrawal->additional_notes); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Products -->
                    <div class="postbox">
                        <h2><?php _e('Prodotti', 'recesso-facile'); ?></h2>
                        <div class="inside">
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Prodotto', 'recesso-facile'); ?></th>
                                        <th><?php _e('Quantità', 'recesso-facile'); ?></th>
                                        <th><?php _e('Totale', 'recesso-facile'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?php echo esc_html($product['name']); ?></td>
                                        <td><?php echo esc_html($product['quantity']); ?></td>
                                        <td><?php echo wp_kses_post(wc_price($product['total'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Activities -->
                    <div class="postbox">
                        <h2><?php _e('Registro Attività', 'recesso-facile'); ?></h2>
                        <div class="inside">
                            <table class="widefat">
                                <thead>
                                    <tr>
                                        <th><?php _e('Data', 'recesso-facile'); ?></th>
                                        <th><?php _e('Azione', 'recesso-facile'); ?></th>
                                        <th><?php _e('Descrizione', 'recesso-facile'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($activity->created_at))); ?></td>
                                        <td><?php echo esc_html(RF_Activity_Logger::get_action_label($activity->action)); ?></td>
                                        <td><?php echo esc_html($activity->description); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="rf-detail-sidebar">
                    <!-- Update Status -->
                    <div class="postbox">
                        <h2><?php _e('Aggiorna Status', 'recesso-facile'); ?></h2>
                        <div class="inside">
                            <form method="post" action="">
                                <?php wp_nonce_field('rf_update_status_' . $withdrawal_id); ?>
                                <p>
                                    <select name="new_status" class="widefat">
                                        <option value="pending" <?php selected($withdrawal->status, 'pending'); ?>><?php _e('In Attesa', 'recesso-facile'); ?></option>
                                        <option value="approved" <?php selected($withdrawal->status, 'approved'); ?>><?php _e('Approvata', 'recesso-facile'); ?></option>
                                        <option value="rejected" <?php selected($withdrawal->status, 'rejected'); ?>><?php _e('Rifiutata', 'recesso-facile'); ?></option>
                                        <option value="completed" <?php selected($withdrawal->status, 'completed'); ?>><?php _e('Completata', 'recesso-facile'); ?></option>
                                        <option value="cancelled" <?php selected($withdrawal->status, 'cancelled'); ?>><?php _e('Annullata', 'recesso-facile'); ?></option>
                                    </select>
                                </p>
                                <p>
                                    <textarea name="admin_notes" class="widefat" rows="3" placeholder="<?php esc_attr_e('Note admin (opzionale)', 'recesso-facile'); ?>"></textarea>
                                </p>
                                <p>
                                    <button type="submit" name="update_status" class="button button-primary">
                                        <?php _e('Aggiorna Status', 'recesso-facile'); ?>
                                    </button>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        // Handle status update
        if (isset($_POST['update_status']) && check_admin_referer('rf_update_status_' . $withdrawal_id)) {
            $new_status = isset($_POST['new_status']) ? sanitize_text_field($_POST['new_status']) : '';
            $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';

            if ($new_status) {
                $result = RF_Withdrawal_Service::update_status($withdrawal_id, $new_status, $admin_notes);

                if (!is_wp_error($result)) {
                    echo '<div class="notice notice-success"><p>' . esc_html__('Status aggiornato con successo.', 'recesso-facile') . '</p></div>';
                    echo '<script>window.location.reload();</script>';
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
                }
            }
        }
    }
}
