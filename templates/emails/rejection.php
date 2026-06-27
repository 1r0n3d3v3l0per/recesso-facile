<?php
/**
 * Email: richiesta di recesso non accettata (cliente).
 *
 * Sovrascrivibile dal tema in: recesso-facile/emails/rejection.php
 * Variabili disponibili: $withdrawal, $order, $reason
 *
 * @package RecessoFacile/Templates/Emails
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<h2><?php esc_html_e('Richiesta di recesso non accettata', 'recesso-facile'); ?></h2>

<p><?php esc_html_e('Ci dispiace informarti che non possiamo accettare la tua richiesta di recesso.', 'recesso-facile'); ?></p>

<?php if (!empty($reason)): ?>
<p><strong><?php esc_html_e('Motivo', 'recesso-facile'); ?>:</strong> <?php echo esc_html($reason); ?></p>
<?php endif; ?>

<p><strong><?php esc_html_e('Numero richiesta', 'recesso-facile'); ?>:</strong> #<?php echo esc_html($withdrawal->id); ?></p>
