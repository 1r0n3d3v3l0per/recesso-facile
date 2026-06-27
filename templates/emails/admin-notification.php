<?php
/**
 * Email: notifica nuova richiesta di recesso (amministratore).
 *
 * Sovrascrivibile dal tema in: recesso-facile/emails/admin-notification.php
 * Variabili disponibili: $withdrawal, $order
 *
 * @package RecessoFacile/Templates/Emails
 */

if (!defined('ABSPATH')) {
    exit;
}

$view_url = admin_url('admin.php?page=recesso-facile-requests&action=view&id=' . $withdrawal->id);
?>
<h2><?php esc_html_e('Nuova richiesta di recesso', 'recesso-facile'); ?></h2>

<p><?php esc_html_e('È stata ricevuta una nuova richiesta di recesso.', 'recesso-facile'); ?></p>

<p><strong><?php esc_html_e('Numero richiesta', 'recesso-facile'); ?>:</strong> #<?php echo esc_html($withdrawal->id); ?></p>
<p><strong><?php esc_html_e('Ordine', 'recesso-facile'); ?>:</strong> #<?php echo esc_html($withdrawal->order_id); ?></p>
<p><strong><?php esc_html_e('Cliente', 'recesso-facile'); ?>:</strong> <?php echo esc_html($withdrawal->customer_name); ?> (<?php echo esc_html($withdrawal->email); ?>)</p>
<?php if (!empty($withdrawal->reason)): ?>
<p><strong><?php esc_html_e('Motivo', 'recesso-facile'); ?>:</strong> <?php echo esc_html($withdrawal->reason); ?></p>
<?php endif; ?>

<p><a href="<?php echo esc_url($view_url); ?>"><?php esc_html_e('Visualizza richiesta', 'recesso-facile'); ?></a></p>
