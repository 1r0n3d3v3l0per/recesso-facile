<?php
/**
 * Email: avviso di ricevimento della richiesta di recesso (cliente).
 *
 * Avviso di ricevimento su supporto durevole, con data e ora di ricezione
 * (Art. 54-bis / Dir. UE 2023/2673, art. 11-bis).
 *
 * Sovrascrivibile dal tema in: recesso-facile/emails/customer-confirmation.php
 * Variabili disponibili: $withdrawal, $order
 *
 * @package RecessoFacile/Templates/Emails
 */

if (!defined('ABSPATH')) {
    exit;
}

$received_at = date_i18n(
    get_option('date_format') . ' ' . get_option('time_format'),
    strtotime($withdrawal->request_date)
);
?>
<h2><?php esc_html_e('Avviso di ricevimento della richiesta di recesso', 'recesso-facile'); ?></h2>

<p><?php esc_html_e('Con la presente confermiamo di aver ricevuto la tua richiesta di recesso. Questo messaggio costituisce avviso di ricevimento su supporto durevole.', 'recesso-facile'); ?></p>

<p><strong><?php esc_html_e('Data e ora di ricezione', 'recesso-facile'); ?>:</strong> <?php echo esc_html($received_at); ?></p>
<p><strong><?php esc_html_e('Numero richiesta', 'recesso-facile'); ?>:</strong> #<?php echo esc_html($withdrawal->id); ?></p>
<p><strong><?php esc_html_e('Ordine', 'recesso-facile'); ?>:</strong> #<?php echo esc_html($withdrawal->order_id); ?></p>

<p><?php esc_html_e('Riceverai un aggiornamento via email appena elaboreremo la tua richiesta. In allegato trovi la ricevuta in formato PDF.', 'recesso-facile'); ?></p>

<p style="font-size:12px;color:#666;">
    <?php
    printf(
        /* translators: %s: receipt hash */
        esc_html__('Codice ricevuta: %s', 'recesso-facile'),
        esc_html($withdrawal->receipt_hash)
    );
    ?>
</p>
