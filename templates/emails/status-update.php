<?php
/**
 * Email: aggiornamento stato della richiesta di recesso (cliente).
 *
 * Sovrascrivibile dal tema in: recesso-facile/emails/status-update.php
 * Variabili disponibili: $withdrawal, $order, $new_status
 *
 * @package RecessoFacile/Templates/Emails
 */

if (!defined('ABSPATH')) {
    exit;
}

$status_labels = array(
    'approved'  => __('approvata', 'recesso-facile'),
    'rejected'  => __('rifiutata', 'recesso-facile'),
    'completed' => __('completata', 'recesso-facile'),
    'cancelled' => __('annullata', 'recesso-facile'),
);
$status_label = isset($status_labels[$new_status]) ? $status_labels[$new_status] : $new_status;
?>
<h2><?php esc_html_e('Aggiornamento richiesta di recesso', 'recesso-facile'); ?></h2>

<p>
    <?php esc_html_e('La tua richiesta di recesso è stata', 'recesso-facile'); ?>
    <strong><?php echo esc_html($status_label); ?></strong>.
</p>

<p><strong><?php esc_html_e('Numero richiesta', 'recesso-facile'); ?>:</strong> #<?php echo esc_html($withdrawal->id); ?></p>
<p><strong><?php esc_html_e('Ordine', 'recesso-facile'); ?>:</strong> #<?php echo esc_html($withdrawal->order_id); ?></p>
