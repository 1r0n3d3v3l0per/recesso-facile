<?php
/**
 * Admin Dashboard
 *
 * @package RecessoFacile\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Admin_Dashboard Class
 */
class RF_Admin_Dashboard {

    /**
     * Render dashboard page
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'recesso-facile'));
        }

        $stats = RF_Withdrawal_Service::get_statistics();
        $exception_count = RF_Exception_Service::get_count();

        global $wpdb;

        // Get recent withdrawals
        $recent_withdrawals = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rf_withdrawals
            ORDER BY created_at DESC
            LIMIT 10"
        );

        ?>
        <div class="wrap rf-admin-wrap">
            <h1><?php _e('Dashboard Recesso Facile', 'recesso-facile'); ?></h1>

            <!-- Statistics Cards -->
            <div class="rf-stats-grid">
                <div class="rf-stat-card">
                    <div class="rf-stat-icon">📊</div>
                    <div class="rf-stat-content">
                        <h3><?php echo esc_html($stats['total']); ?></h3>
                        <p><?php _e('Richieste Totali', 'recesso-facile'); ?></p>
                    </div>
                </div>

                <div class="rf-stat-card rf-stat-pending">
                    <div class="rf-stat-icon">⏳</div>
                    <div class="rf-stat-content">
                        <h3><?php echo esc_html($stats['pending']); ?></h3>
                        <p><?php _e('In Attesa', 'recesso-facile'); ?></p>
                    </div>
                </div>

                <div class="rf-stat-card rf-stat-approved">
                    <div class="rf-stat-icon">✓</div>
                    <div class="rf-stat-content">
                        <h3><?php echo esc_html($stats['approved']); ?></h3>
                        <p><?php _e('Approvate', 'recesso-facile'); ?></p>
                    </div>
                </div>

                <div class="rf-stat-card rf-stat-completed">
                    <div class="rf-stat-icon">✔</div>
                    <div class="rf-stat-content">
                        <h3><?php echo esc_html($stats['completed']); ?></h3>
                        <p><?php _e('Completate', 'recesso-facile'); ?></p>
                    </div>
                </div>

                <div class="rf-stat-card rf-stat-rejected">
                    <div class="rf-stat-icon">✗</div>
                    <div class="rf-stat-content">
                        <h3><?php echo esc_html($stats['rejected']); ?></h3>
                        <p><?php _e('Rifiutate', 'recesso-facile'); ?></p>
                    </div>
                </div>

                <div class="rf-stat-card rf-stat-month">
                    <div class="rf-stat-icon">📅</div>
                    <div class="rf-stat-content">
                        <h3><?php echo esc_html($stats['this_month']); ?></h3>
                        <p><?php _e('Questo Mese', 'recesso-facile'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="rf-quick-actions">
                <h2><?php _e('Azioni Rapide', 'recesso-facile'); ?></h2>
                <div class="rf-action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=recesso-facile-requests'); ?>" class="button button-primary">
                        <?php _e('Gestisci Richieste', 'recesso-facile'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=recesso-facile-exceptions'); ?>" class="button">
                        <?php _e('Eccezioni Prodotti', 'recesso-facile'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=recesso-facile-activity'); ?>" class="button">
                        <?php _e('Registro Attività', 'recesso-facile'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=recesso-facile-settings'); ?>" class="button">
                        <?php _e('Impostazioni', 'recesso-facile'); ?>
                    </a>
                </div>
            </div>

            <!-- Recent Withdrawals -->
            <div class="rf-recent-section">
                <h2><?php _e('Richieste Recenti', 'recesso-facile'); ?></h2>

                <?php if (empty($recent_withdrawals)): ?>
                    <p><?php _e('Nessuna richiesta di recesso ancora.', 'recesso-facile'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'recesso-facile'); ?></th>
                                <th><?php _e('Ordine', 'recesso-facile'); ?></th>
                                <th><?php _e('Email', 'recesso-facile'); ?></th>
                                <th><?php _e('Data', 'recesso-facile'); ?></th>
                                <th><?php _e('Status', 'recesso-facile'); ?></th>
                                <th><?php _e('Azioni', 'recesso-facile'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_withdrawals as $withdrawal): ?>
                            <tr>
                                <td><strong>#<?php echo esc_html($withdrawal->id); ?></strong></td>
                                <td>
                                    <a href="<?php echo admin_url('post.php?post=' . $withdrawal->order_id . '&action=edit'); ?>">
                                        #<?php echo esc_html($withdrawal->order_id); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($withdrawal->email); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($withdrawal->request_date)); ?></td>
                                <td>
                                    <span class="rf-status-badge rf-status-<?php echo esc_attr($withdrawal->status); ?>">
                                        <?php echo esc_html(ucfirst($withdrawal->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=recesso-facile-requests&action=view&id=' . $withdrawal->id); ?>" class="button button-small">
                                        <?php _e('Visualizza', 'recesso-facile'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p>
                        <a href="<?php echo admin_url('admin.php?page=recesso-facile-requests'); ?>">
                            <?php _e('Visualizza tutte le richieste →', 'recesso-facile'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>

            <!-- System Info -->
            <div class="rf-system-info">
                <h2><?php _e('Informazioni Sistema', 'recesso-facile'); ?></h2>
                <table class="widefat">
                    <tbody>
                        <tr>
                            <th><?php _e('Versione Plugin:', 'recesso-facile'); ?></th>
                            <td><?php echo RF_VERSION; ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Periodo Recesso:', 'recesso-facile'); ?></th>
                            <td><?php echo esc_html(get_option('rf_withdrawal_period', 14)); ?> <?php _e('giorni', 'recesso-facile'); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Eccezioni Attive:', 'recesso-facile'); ?></th>
                            <td><?php echo esc_html($exception_count); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Email Admin:', 'recesso-facile'); ?></th>
                            <td><?php echo esc_html(get_option('rf_admin_email', get_option('admin_email'))); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
