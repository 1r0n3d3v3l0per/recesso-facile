<?php
/**
 * Admin Activity Log Page
 *
 * @package RecessoFacile\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Admin_Activity Class
 */
class RF_Admin_Activity {

    /**
     * Render activity log page
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'recesso-facile'));
        }

        $per_page = 50;
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $action_filter = isset($_GET['action_filter']) ? sanitize_key($_GET['action_filter']) : '';

        $args = array(
            'limit' => $per_page,
            'offset' => ($current_page - 1) * $per_page,
        );

        if ($action_filter) {
            $args['action'] = $action_filter;
        }

        $activities = RF_Activity_Logger::get_all_activities($args);
        $total = RF_Activity_Logger::get_count($action_filter ? array('action' => $action_filter) : array());
        $total_pages = ceil($total / $per_page);

        ?>
        <div class="wrap">
            <h1><?php _e('Registro Attività', 'recesso-facile'); ?></h1>

            <p class="description">
                <?php _e('Audit trail completo di tutte le attività del sistema.', 'recesso-facile'); ?>
            </p>

            <!-- Action Filter -->
            <ul class="subsubsub">
                <li><a href="?page=recesso-facile-activity" class="<?php echo empty($action_filter) ? 'current' : ''; ?>">
                    <?php _e('Tutte', 'recesso-facile'); ?>
                </a> | </li>
                <?php
                $action_types = RF_Activity_Logger::get_action_types();
                $i = 0;
                foreach ($action_types as $action => $label):
                    $i++;
                ?>
                    <li><a href="?page=recesso-facile-activity&action_filter=<?php echo esc_attr($action); ?>" class="<?php echo $action_filter === $action ? 'current' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a><?php echo $i < count($action_types) ? ' | ' : ''; ?></li>
                <?php endforeach; ?>
            </ul>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="150"><?php _e('Data/Ora', 'recesso-facile'); ?></th>
                        <th width="100"><?php _e('Richiesta', 'recesso-facile'); ?></th>
                        <th width="150"><?php _e('Azione', 'recesso-facile'); ?></th>
                        <th><?php _e('Descrizione', 'recesso-facile'); ?></th>
                        <th width="100"><?php _e('Utente', 'recesso-facile'); ?></th>
                        <th width="120"><?php _e('IP', 'recesso-facile'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="6"><?php _e('Nessuna attività registrata.', 'recesso-facile'); ?></td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($activity->created_at))); ?></td>
                            <td>
                                <?php if ($activity->withdrawal_id): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=recesso-facile-requests&action=view&id=' . $activity->withdrawal_id)); ?>">
                                        #<?php echo esc_html($activity->withdrawal_id); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="description"><?php _e('Sistema', 'recesso-facile'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="rf-activity-badge rf-activity-<?php echo esc_attr($activity->action); ?>">
                                    <?php echo esc_html(RF_Activity_Logger::get_action_label($activity->action)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($activity->description); ?></td>
                            <td>
                                <?php
                                if ($activity->user_id) {
                                    $user = get_userdata($activity->user_id);
                                    echo $user ? esc_html($user->display_name) : sprintf(__('Utente #%d', 'recesso-facile'), $activity->user_id);
                                } else {
                                    echo '<span class="description">' . esc_html__('Sistema', 'recesso-facile') . '</span>';
                                }
                                ?>
                            </td>
                            <td><code><?php echo esc_html($activity->ip_address); ?></code></td>
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
}
