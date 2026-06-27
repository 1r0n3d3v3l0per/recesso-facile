<?php
/**
 * Withdrawal Form Template
 * 3-step withdrawal request form
 *
 * @package RecessoFacile/Templates
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="rf-withdrawal-form-wrapper">
    <!-- Legal withdrawal-function heading (Art. 54-bis / EU Dir. 2023/2673) -->
    <h2 class="rf-legal-heading"><?php echo esc_html(get_option('rf_legal_button_text', __('Recedere dal contratto qui', 'recesso-facile'))); ?></h2>

    <!-- Progress Indicator -->
    <div class="rf-progress">
        <div class="rf-progress-step rf-step-active" data-step="1">
            <span class="rf-step-number">1</span>
            <span class="rf-step-label"><?php _e('Verifica Ordine', 'recesso-facile'); ?></span>
        </div>
        <div class="rf-progress-step" data-step="2">
            <span class="rf-step-number">2</span>
            <span class="rf-step-label"><?php _e('Motivazione', 'recesso-facile'); ?></span>
        </div>
        <div class="rf-progress-step" data-step="3">
            <span class="rf-step-number">3</span>
            <span class="rf-step-label"><?php _e('Conferma', 'recesso-facile'); ?></span>
        </div>
    </div>

    <form id="rf-withdrawal-form" class="rf-form">
        <!-- Step 1: Order Verification -->
        <div class="rf-form-step rf-step-1 rf-step-active">
            <h2><?php _e('Verifica il tuo Ordine', 'recesso-facile'); ?></h2>
            <p class="rf-description">
                <?php _e('Inserisci il numero d\'ordine e l\'email utilizzata per l\'acquisto.', 'recesso-facile'); ?>
            </p>

            <div class="rf-form-group">
                <label for="rf-customer-name">
                    <?php _e('Nome e Cognome', 'recesso-facile'); ?> *
                </label>
                <input
                    type="text"
                    id="rf-customer-name"
                    name="customer_name"
                    class="rf-input"
                    required
                    placeholder="<?php esc_attr_e('Mario Rossi', 'recesso-facile'); ?>"
                >
                <span class="rf-field-error"></span>
            </div>

            <div class="rf-form-group">
                <label for="rf-order-id">
                    <?php _e('Numero Ordine', 'recesso-facile'); ?> *
                </label>
                <input
                    type="number"
                    id="rf-order-id"
                    name="order_id"
                    class="rf-input"
                    required
                    placeholder="<?php esc_attr_e('Es: 12345', 'recesso-facile'); ?>"
                >
                <span class="rf-field-error"></span>
            </div>

            <div class="rf-form-group">
                <label for="rf-email">
                    <?php _e('Email', 'recesso-facile'); ?> *
                </label>
                <input
                    type="email"
                    id="rf-email"
                    name="email"
                    class="rf-input"
                    required
                    placeholder="<?php esc_attr_e('tua@email.com', 'recesso-facile'); ?>"
                >
                <span class="rf-field-error"></span>
            </div>

            <div id="rf-order-details" class="rf-order-details" style="display: none;">
                <h3><?php _e('Dettagli Ordine', 'recesso-facile'); ?></h3>
                <div class="rf-order-info">
                    <p><strong><?php _e('Numero:', 'recesso-facile'); ?></strong> <span id="rf-detail-order-number"></span></p>
                    <p><strong><?php _e('Data:', 'recesso-facile'); ?></strong> <span id="rf-detail-order-date"></span></p>
                    <p><strong><?php _e('Totale:', 'recesso-facile'); ?></strong> <span id="rf-detail-order-total"></span></p>
                </div>
                <div class="rf-products-list">
                    <h4><?php _e('Prodotti', 'recesso-facile'); ?></h4>
                    <div id="rf-products"></div>
                </div>
            </div>

            <div class="rf-form-actions">
                <button type="button" class="rf-button rf-button-primary" id="rf-verify-order">
                    <?php _e('Verifica Ordine', 'recesso-facile'); ?>
                </button>
                <button type="button" class="rf-button rf-button-next" id="rf-next-step-1" style="display: none;">
                    <?php _e('Continua', 'recesso-facile'); ?> →
                </button>
            </div>
        </div>

        <!-- Step 2: Reason and Details -->
        <div class="rf-form-step rf-step-2">
            <h2><?php _e('Motivazione del Recesso', 'recesso-facile'); ?></h2>
            <p class="rf-description">
                <?php _e('Indica il motivo per cui desideri recedere dall\'acquisto.', 'recesso-facile'); ?>
            </p>

            <div class="rf-form-group">
                <label for="rf-reason">
                    <?php _e('Motivo del recesso', 'recesso-facile'); ?>
                    <?php if (get_option('rf_require_reason', 'yes') === 'yes'): ?>*<?php endif; ?>
                </label>
                <textarea
                    id="rf-reason"
                    name="reason"
                    class="rf-textarea"
                    rows="4"
                    <?php echo get_option('rf_require_reason', 'yes') === 'yes' ? 'required' : ''; ?>
                    placeholder="<?php esc_attr_e('Descrivi brevemente il motivo del tuo recesso...', 'recesso-facile'); ?>"
                ></textarea>
                <span class="rf-field-help"><?php _e('Minimo 10 caratteri', 'recesso-facile'); ?></span>
                <span class="rf-field-error"></span>
            </div>

            <?php if (get_option('rf_enable_additional_notes', 'yes') === 'yes'): ?>
            <div class="rf-form-group">
                <label for="rf-additional-notes">
                    <?php _e('Note aggiuntive (opzionale)', 'recesso-facile'); ?>
                </label>
                <textarea
                    id="rf-additional-notes"
                    name="additional_notes"
                    class="rf-textarea"
                    rows="3"
                    placeholder="<?php esc_attr_e('Eventuali informazioni aggiuntive...', 'recesso-facile'); ?>"
                ></textarea>
            </div>
            <?php endif; ?>

            <div class="rf-form-group">
                <label><?php _e('Modalità di rimborso preferita', 'recesso-facile'); ?> *</label>
                <div class="rf-radio-group">
                    <label class="rf-radio">
                        <input type="radio" name="refund_method" value="original" checked>
                        <span><?php _e('Metodo di pagamento originale', 'recesso-facile'); ?></span>
                    </label>

                    <?php if (get_option('rf_enable_bank_transfer', 'yes') === 'yes'): ?>
                    <label class="rf-radio">
                        <input type="radio" name="refund_method" value="bank_transfer">
                        <span><?php _e('Bonifico bancario', 'recesso-facile'); ?></span>
                    </label>
                    <?php endif; ?>

                    <?php if (get_option('rf_enable_store_credit', 'no') === 'yes'): ?>
                    <label class="rf-radio">
                        <input type="radio" name="refund_method" value="store_credit">
                        <span><?php _e('Credito sul negozio', 'recesso-facile'); ?></span>
                    </label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rf-form-group rf-iban-group" style="display: none;">
                <label for="rf-refund-iban">
                    <?php _e('IBAN per bonifico', 'recesso-facile'); ?> *
                </label>
                <input
                    type="text"
                    id="rf-refund-iban"
                    name="refund_iban"
                    class="rf-input"
                    placeholder="<?php esc_attr_e('IT00X0000000000000000000000', 'recesso-facile'); ?>"
                    maxlength="34"
                >
                <span class="rf-field-help"><?php _e('Inserisci un IBAN valido italiano', 'recesso-facile'); ?></span>
                <span class="rf-field-error"></span>
            </div>

            <div class="rf-form-actions">
                <button type="button" class="rf-button rf-button-secondary" id="rf-prev-step-2">
                    ← <?php _e('Indietro', 'recesso-facile'); ?>
                </button>
                <button type="button" class="rf-button rf-button-next" id="rf-next-step-2">
                    <?php _e('Continua', 'recesso-facile'); ?> →
                </button>
            </div>
        </div>

        <!-- Step 3: Confirmation -->
        <div class="rf-form-step rf-step-3">
            <h2><?php _e('Conferma Richiesta di Recesso', 'recesso-facile'); ?></h2>
            <p class="rf-description">
                <?php _e('Verifica i dati inseriti e conferma la tua richiesta.', 'recesso-facile'); ?>
            </p>

            <div class="rf-summary">
                <h3><?php _e('Riepilogo', 'recesso-facile'); ?></h3>
                <div class="rf-summary-item">
                    <strong><?php _e('Nome:', 'recesso-facile'); ?></strong>
                    <span id="rf-summary-name"></span>
                </div>
                <div class="rf-summary-item">
                    <strong><?php _e('Ordine:', 'recesso-facile'); ?></strong>
                    <span id="rf-summary-order"></span>
                </div>
                <div class="rf-summary-item">
                    <strong><?php _e('Email:', 'recesso-facile'); ?></strong>
                    <span id="rf-summary-email"></span>
                </div>
                <div class="rf-summary-item">
                    <strong><?php _e('Motivo:', 'recesso-facile'); ?></strong>
                    <span id="rf-summary-reason"></span>
                </div>
                <div class="rf-summary-item">
                    <strong><?php _e('Rimborso:', 'recesso-facile'); ?></strong>
                    <span id="rf-summary-refund"></span>
                </div>
            </div>

            <div class="rf-legal-info">
                <h4><?php _e('Informazioni Legali', 'recesso-facile'); ?></h4>
                <p>
                    <?php _e('Ai sensi degli articoli 52 e seguenti del Codice del Consumo (D.Lgs. 206/2005), hai il diritto di recedere dal presente contratto entro 14 giorni senza dover fornire alcuna motivazione.', 'recesso-facile'); ?>
                </p>
                <p>
                    <?php _e('Il rimborso delle somme versate sarà effettuato entro 14 giorni dalla ricezione della merce.', 'recesso-facile'); ?>
                </p>
            </div>

            <div class="rf-form-group rf-checkbox-group">
                <label class="rf-checkbox">
                    <input type="checkbox" name="accept_terms" id="rf-accept-terms" required>
                    <span>
                        <?php
                        $terms_page = get_option('rf_terms_page');
                        if ($terms_page) {
                            printf(
                                __('Ho letto e accetto i <a href="%s" target="_blank">termini e condizioni</a> *', 'recesso-facile'),
                                get_permalink($terms_page)
                            );
                        } else {
                            _e('Confermo di aver letto le informazioni sul diritto di recesso *', 'recesso-facile');
                        }
                        ?>
                    </span>
                </label>
                <span class="rf-field-error"></span>
            </div>

            <?php if (get_option('rf_enable_double_confirmation', 'yes') === 'yes'): ?>
            <div class="rf-form-group rf-checkbox-group">
                <label class="rf-checkbox">
                    <input type="checkbox" name="double_confirmation" id="rf-double-confirmation" required>
                    <span>
                        <?php _e('Confermo di voler procedere con la richiesta di recesso per l\'ordine indicato *', 'recesso-facile'); ?>
                    </span>
                </label>
                <span class="rf-field-error"></span>
            </div>
            <?php endif; ?>

            <div class="rf-form-actions">
                <button type="button" class="rf-button rf-button-secondary" id="rf-prev-step-3">
                    ← <?php _e('Indietro', 'recesso-facile'); ?>
                </button>
                <button type="submit" class="rf-button rf-button-primary rf-button-submit" id="rf-submit-withdrawal">
                    <?php echo esc_html(get_option('rf_confirm_button_text', __('Conferma recesso', 'recesso-facile'))); ?>
                </button>
            </div>
        </div>

        <!-- Success Message -->
        <div class="rf-form-step rf-step-success" style="display: none;">
            <div class="rf-success-icon">✓</div>
            <h2><?php _e('Richiesta Inviata con Successo!', 'recesso-facile'); ?></h2>
            <p>
                <?php _e('Abbiamo ricevuto la tua richiesta di recesso e ti abbiamo inviato una conferma via email.', 'recesso-facile'); ?>
            </p>
            <div class="rf-receipt-info">
                <p><strong><?php _e('Numero Richiesta:', 'recesso-facile'); ?></strong> <span id="rf-receipt-number"></span></p>
                <p><strong><?php _e('Hash di Sicurezza:', 'recesso-facile'); ?></strong></p>
                <code id="rf-receipt-hash"></code>
                <p class="rf-receipt-note">
                    <?php _e('Conserva questo numero e l\'hash per eventuali future comunicazioni.', 'recesso-facile'); ?>
                </p>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div class="rf-loading" style="display: none;">
            <div class="rf-spinner"></div>
            <p><?php _e('Elaborazione in corso...', 'recesso-facile'); ?></p>
        </div>

        <!-- Error Message -->
        <div class="rf-error-message" style="display: none;"></div>
    </form>
</div>
