<?php
/**
 * Admin Settings Page
 *
 * @package RecessoFacile\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Admin_Settings Class
 */
class RF_Admin_Settings {

    /**
     * Map of settings tabs to the options they own.
     *
     * Each tab's form only renders its own fields, so saving must be scoped to
     * the submitted tab. Saving every option on every submit would reset the
     * checkboxes belonging to the other tabs (they aren't in $_POST).
     *
     * 'checkbox' options default to 'no' when absent from the submission;
     * 'int' / 'email' / 'text' / 'textarea' are sanitized accordingly.
     *
     * @var array<string, array<string, string>>
     */
    private static function get_tab_settings() {
        return array(
            'general' => array(
                'rf_enable_withdrawal'    => 'checkbox',
                'rf_withdrawal_period'    => 'int',
                'rf_enable_sticky_button' => 'checkbox',
                'rf_button_text'          => 'text',
                'rf_legal_button_text'    => 'text',
                'rf_confirm_button_text'  => 'text',
                'rf_button_position'      => 'text',
                'rf_withdrawal_page'      => 'int',
            ),
            'emails' => array(
                'rf_enable_customer_email' => 'checkbox',
                'rf_enable_admin_email'    => 'checkbox',
                'rf_admin_email'           => 'email',
                'rf_email_from_name'       => 'text',
                'rf_email_from_address'    => 'email',
            ),
            'form' => array(
                'rf_require_reason'             => 'checkbox',
                'rf_enable_additional_notes'   => 'checkbox',
                'rf_enable_guest_withdrawal'   => 'checkbox',
                'rf_enable_double_confirmation' => 'checkbox',
                'rf_enable_bank_transfer'      => 'checkbox',
                'rf_enable_store_credit'       => 'checkbox',
            ),
            'pdf' => array(
                'rf_enable_pdf'          => 'checkbox',
                'rf_pdf_company_name'    => 'text',
                'rf_pdf_company_address' => 'textarea',
                'rf_pdf_company_vat'     => 'text',
            ),
            'advanced' => array(
                'rf_enable_activity_log'       => 'checkbox',
                'rf_auto_delete_old_requests'  => 'checkbox',
                'rf_delete_after_days'         => 'int',
            ),
        );
    }

    /**
     * Render settings page
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'recesso-facile'));
        }

        // Save settings (scoped to the submitted tab)
        if (isset($_POST['rf_save_settings']) && check_admin_referer('rf_save_settings')) {
            $saved_tab = isset($_POST['rf_current_tab']) ? sanitize_key($_POST['rf_current_tab']) : 'general';
            self::save_settings($saved_tab);
            echo '<div class="notice notice-success"><p>' . esc_html__('Impostazioni salvate con successo.', 'recesso-facile') . '</p></div>';
        }

        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

        ?>
        <div class="wrap rf-admin-wrap">
            <h1><?php _e('Impostazioni Recesso Facile', 'recesso-facile'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="?page=recesso-facile-settings&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Generale', 'recesso-facile'); ?>
                </a>
                <a href="?page=recesso-facile-settings&tab=emails" class="nav-tab <?php echo $current_tab === 'emails' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Email', 'recesso-facile'); ?>
                </a>
                <a href="?page=recesso-facile-settings&tab=form" class="nav-tab <?php echo $current_tab === 'form' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Modulo', 'recesso-facile'); ?>
                </a>
                <a href="?page=recesso-facile-settings&tab=pdf" class="nav-tab <?php echo $current_tab === 'pdf' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('PDF', 'recesso-facile'); ?>
                </a>
                <a href="?page=recesso-facile-settings&tab=advanced" class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Avanzate', 'recesso-facile'); ?>
                </a>
            </nav>

            <form method="post" action="">
                <?php wp_nonce_field('rf_save_settings'); ?>
                <input type="hidden" name="rf_current_tab" value="<?php echo esc_attr($current_tab); ?>">

                <?php
                switch ($current_tab) {
                    case 'general':
                        self::render_general_settings();
                        break;
                    case 'emails':
                        self::render_email_settings();
                        break;
                    case 'form':
                        self::render_form_settings();
                        break;
                    case 'pdf':
                        self::render_pdf_settings();
                        break;
                    case 'advanced':
                        self::render_advanced_settings();
                        break;
                }
                ?>

                <p class="submit">
                    <button type="submit" name="rf_save_settings" class="button button-primary">
                        <?php _e('Salva Impostazioni', 'recesso-facile'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render general settings
     */
    private static function render_general_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rf_enable_withdrawal"><?php _e('Abilita Recesso', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_enable_withdrawal" id="rf_enable_withdrawal" value="yes" <?php checked(get_option('rf_enable_withdrawal', 'yes'), 'yes'); ?>>
                    <p class="description"><?php _e('Abilita o disabilita il sistema di recesso.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_withdrawal_period"><?php _e('Periodo di Recesso', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="number" name="rf_withdrawal_period" id="rf_withdrawal_period" value="<?php echo esc_attr(get_option('rf_withdrawal_period', 14)); ?>" min="1" max="365" class="small-text">
                    <span><?php _e('giorni', 'recesso-facile'); ?></span>
                    <p class="description"><?php _e('Numero di giorni entro cui i clienti possono richiedere il recesso (legale: 14 giorni).', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_enable_sticky_button"><?php _e('Bottone Sticky', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_enable_sticky_button" id="rf_enable_sticky_button" value="yes" <?php checked(get_option('rf_enable_sticky_button', 'yes'), 'yes'); ?>>
                    <p class="description"><?php _e('Mostra un bottone fisso sulla pagina per accedere al modulo di recesso.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_button_text"><?php _e('Testo Bottone Flottante', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="text" name="rf_button_text" id="rf_button_text" value="<?php echo esc_attr(get_option('rf_button_text', __('Richiedi Recesso', 'recesso-facile'))); ?>" class="regular-text">
                    <p class="description"><?php _e('Testo del bottone fisso che porta al modulo di recesso.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_legal_button_text"><?php _e('Dicitura Funzione di Recesso', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="text" name="rf_legal_button_text" id="rf_legal_button_text" value="<?php echo esc_attr(get_option('rf_legal_button_text', __('Recedere dal contratto qui', 'recesso-facile'))); ?>" class="regular-text">
                    <p class="description"><?php _e('Dicitura mostrata in cima al modulo. La legge (Art. 54-bis / Dir. UE 2023/2673) richiede "Recedere dal contratto qui" o formulazione equivalente inequivocabile.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_confirm_button_text"><?php _e('Testo Pulsante di Conferma', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="text" name="rf_confirm_button_text" id="rf_confirm_button_text" value="<?php echo esc_attr(get_option('rf_confirm_button_text', __('Conferma recesso', 'recesso-facile'))); ?>" class="regular-text">
                    <p class="description"><?php _e('Testo del pulsante finale di invio. La legge richiede "Conferma recesso" o formulazione equivalente inequivocabile.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_button_position"><?php _e('Posizione Bottone', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <select name="rf_button_position" id="rf_button_position">
                        <option value="bottom-right" <?php selected(get_option('rf_button_position', 'bottom-right'), 'bottom-right'); ?>>
                            <?php _e('Basso Destra', 'recesso-facile'); ?>
                        </option>
                        <option value="bottom-left" <?php selected(get_option('rf_button_position', 'bottom-right'), 'bottom-left'); ?>>
                            <?php _e('Basso Sinistra', 'recesso-facile'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_withdrawal_page"><?php _e('Pagina Recesso', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages(array(
                        'name' => 'rf_withdrawal_page',
                        'id' => 'rf_withdrawal_page',
                        'selected' => get_option('rf_withdrawal_page'),
                        'show_option_none' => __('Seleziona una pagina', 'recesso-facile'),
                    ));
                    ?>
                    <p class="description"><?php _e('Pagina che contiene il modulo di recesso [recesso_facile_form].', 'recesso-facile'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render email settings
     */
    private static function render_email_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rf_enable_customer_email"><?php _e('Email Cliente', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_enable_customer_email" id="rf_enable_customer_email" value="yes" <?php checked(get_option('rf_enable_customer_email', 'yes'), 'yes'); ?>>
                    <p class="description"><?php _e('Invia email di conferma al cliente.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_enable_admin_email"><?php _e('Email Admin', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_enable_admin_email" id="rf_enable_admin_email" value="yes" <?php checked(get_option('rf_enable_admin_email', 'yes'), 'yes'); ?>>
                    <p class="description"><?php _e('Invia notifica email all\'amministratore.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_admin_email"><?php _e('Email Amministratore', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="email" name="rf_admin_email" id="rf_admin_email" value="<?php echo esc_attr(get_option('rf_admin_email', get_option('admin_email'))); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_email_from_name"><?php _e('Nome Mittente', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="text" name="rf_email_from_name" id="rf_email_from_name" value="<?php echo esc_attr(get_option('rf_email_from_name', get_bloginfo('name'))); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_email_from_address"><?php _e('Email Mittente', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="email" name="rf_email_from_address" id="rf_email_from_address" value="<?php echo esc_attr(get_option('rf_email_from_address', get_option('admin_email'))); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render form settings
     */
    private static function render_form_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rf_require_reason"><?php _e('Motivo Obbligatorio', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_require_reason" id="rf_require_reason" value="yes" <?php checked(get_option('rf_require_reason', 'yes'), 'yes'); ?>>
                    <p class="description"><?php _e('Richiedi la motivazione del recesso.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_enable_additional_notes"><?php _e('Note Aggiuntive', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_enable_additional_notes" id="rf_enable_additional_notes" value="yes" <?php checked(get_option('rf_enable_additional_notes', 'yes'), 'yes'); ?>>
                    <p class="description"><?php _e('Permetti ai clienti di aggiungere note aggiuntive.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_enable_guest_withdrawal"><?php _e('Recesso Ospiti', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_enable_guest_withdrawal" id="rf_enable_guest_withdrawal" value="yes" <?php checked(get_option('rf_enable_guest_withdrawal', 'yes'), 'yes'); ?>>
                    <p class="description"><?php _e('Permetti agli ospiti (non registrati) di richiedere il recesso.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_enable_double_confirmation"><?php _e('Doppia Conferma', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_enable_double_confirmation" id="rf_enable_double_confirmation" value="yes" <?php checked(get_option('rf_enable_double_confirmation', 'yes'), 'yes'); ?>>
                    <p class="description"><?php _e('Richiedi doppia conferma prima di inviare (Art. 54-bis compliance).', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_enable_bank_transfer"><?php _e('Bonifico Bancario', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_enable_bank_transfer" id="rf_enable_bank_transfer" value="yes" <?php checked(get_option('rf_enable_bank_transfer', 'yes'), 'yes'); ?>>
                    <p class="description"><?php _e('Permetti il rimborso tramite bonifico bancario.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_enable_store_credit"><?php _e('Credito Negozio', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_enable_store_credit" id="rf_enable_store_credit" value="yes" <?php checked(get_option('rf_enable_store_credit', 'no'), 'yes'); ?>>
                    <p class="description"><?php _e('Permetti il rimborso come credito sul negozio.', 'recesso-facile'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render PDF settings
     */
    private static function render_pdf_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rf_enable_pdf"><?php _e('Abilita PDF', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_enable_pdf" id="rf_enable_pdf" value="yes" <?php checked(get_option('rf_enable_pdf', 'yes'), 'yes'); ?>>
                    <p class="description"><?php _e('Genera ricevuta PDF con hash SHA256 per ogni richiesta.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_pdf_company_name"><?php _e('Nome Azienda', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="text" name="rf_pdf_company_name" id="rf_pdf_company_name" value="<?php echo esc_attr(get_option('rf_pdf_company_name', get_bloginfo('name'))); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_pdf_company_address"><?php _e('Indirizzo Azienda', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <textarea name="rf_pdf_company_address" id="rf_pdf_company_address" rows="3" class="large-text"><?php echo esc_textarea(get_option('rf_pdf_company_address', '')); ?></textarea>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_pdf_company_vat"><?php _e('P.IVA', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="text" name="rf_pdf_company_vat" id="rf_pdf_company_vat" value="<?php echo esc_attr(get_option('rf_pdf_company_vat', '')); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render advanced settings
     */
    private static function render_advanced_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rf_enable_activity_log"><?php _e('Registro Attività', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_enable_activity_log" id="rf_enable_activity_log" value="yes" <?php checked(get_option('rf_enable_activity_log', 'yes'), 'yes'); ?>>
                    <p class="description"><?php _e('Mantieni un registro dettagliato di tutte le attività (audit trail).', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_auto_delete_old_requests"><?php _e('Elimina Vecchie Richieste', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="rf_auto_delete_old_requests" id="rf_auto_delete_old_requests" value="yes" <?php checked(get_option('rf_auto_delete_old_requests', 'no'), 'yes'); ?>>
                    <p class="description"><?php _e('Elimina automaticamente le richieste completate dopo un certo periodo.', 'recesso-facile'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="rf_delete_after_days"><?php _e('Elimina Dopo', 'recesso-facile'); ?></label>
                </th>
                <td>
                    <input type="number" name="rf_delete_after_days" id="rf_delete_after_days" value="<?php echo esc_attr(get_option('rf_delete_after_days', 365)); ?>" min="30" max="3650" class="small-text">
                    <span><?php _e('giorni', 'recesso-facile'); ?></span>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save settings for a single tab.
     *
     * Only the options belonging to $tab are touched, so saving one tab never
     * resets the checkboxes of the others. Checkboxes absent from the POST are
     * treated as unchecked ('no') — but only within the submitted tab.
     *
     * @param string $tab The tab whose form was submitted
     */
    private static function save_settings($tab) {
        $tabs = self::get_tab_settings();

        if (!isset($tabs[$tab])) {
            return;
        }

        foreach ($tabs[$tab] as $setting => $type) {
            switch ($type) {
                case 'checkbox':
                    // Unchecked checkboxes send no POST data.
                    $value = isset($_POST[$setting]) ? 'yes' : 'no';
                    break;

                case 'email':
                    $value = isset($_POST[$setting]) ? sanitize_email(wp_unslash($_POST[$setting])) : '';
                    break;

                case 'int':
                    $value = isset($_POST[$setting]) ? absint($_POST[$setting]) : 0;
                    break;

                case 'textarea':
                    $value = isset($_POST[$setting]) ? sanitize_textarea_field(wp_unslash($_POST[$setting])) : '';
                    break;

                case 'text':
                default:
                    $value = isset($_POST[$setting]) ? sanitize_text_field(wp_unslash($_POST[$setting])) : '';
                    break;
            }

            update_option($setting, $value);
        }

        RF_Activity_Logger::log(null, 'settings_updated', __('Impostazioni aggiornate', 'recesso-facile'));
    }
}
