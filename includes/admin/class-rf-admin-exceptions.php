<?php
/**
 * Admin Exceptions Page
 *
 * @package RecessoFacile\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Admin_Exceptions Class
 */
class RF_Admin_Exceptions {

    /**
     * Render exceptions page
     */
    public static function render() {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'recesso-facile'));
        }

        // Handle add/delete
        if (isset($_POST['rf_add_exception']) && check_admin_referer('rf_add_exception')) {
            self::handle_add_exception();
        }

        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && check_admin_referer('rf_delete_exception_' . absint($_GET['id']))) {
            self::handle_delete_exception(absint($_GET['id']));
        }

        $exceptions = RF_Exception_Service::get_exceptions();
        $exception_types = RF_Exception_Service::get_exception_types();

        ?>
        <div class="wrap">
            <h1><?php _e('Eccezioni Prodotti (Art. 59)', 'recesso-facile'); ?></h1>

            <p class="description">
                <?php _e('Gestisci i prodotti e le categorie esclusi dal diritto di recesso secondo l\'Art. 59 del Codice del Consumo.', 'recesso-facile'); ?>
            </p>

            <!-- Add Exception Form -->
            <div class="postbox">
                <h2><?php _e('Aggiungi Nuova Eccezione', 'recesso-facile'); ?></h2>
                <div class="inside">
                    <form method="post" action="">
                        <?php wp_nonce_field('rf_add_exception'); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="exception_type"><?php _e('Tipo Eccezione', 'recesso-facile'); ?></label>
                                </th>
                                <td>
                                    <select name="exception_type" id="exception_type" class="regular-text" required>
                                        <option value=""><?php _e('Seleziona tipo...', 'recesso-facile'); ?></option>
                                        <?php foreach ($exception_types as $type => $label): ?>
                                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="product_id"><?php _e('Prodotto', 'recesso-facile'); ?></label>
                                </th>
                                <td>
                                    <select name="product_id" id="product_id" class="wc-product-search" style="width: 300px;" data-placeholder="<?php esc_attr_e('Cerca prodotto...', 'recesso-facile'); ?>">
                                    </select>
                                    <p class="description"><?php _e('Lascia vuoto per applicare a una categoria.', 'recesso-facile'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="category_id"><?php _e('Categoria', 'recesso-facile'); ?></label>
                                </th>
                                <td>
                                    <?php
                                    wp_dropdown_categories(array(
                                        'name' => 'category_id',
                                        'id' => 'category_id',
                                        'taxonomy' => 'product_cat',
                                        'show_option_none' => __('Nessuna categoria', 'recesso-facile'),
                                        'hide_empty' => 0,
                                        'class' => 'regular-text',
                                    ));
                                    ?>
                                    <p class="description"><?php _e('Lascia vuoto per applicare a un singolo prodotto.', 'recesso-facile'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="reason"><?php _e('Motivazione', 'recesso-facile'); ?></label>
                                </th>
                                <td>
                                    <textarea name="reason" id="reason" rows="3" class="large-text" required placeholder="<?php esc_attr_e('Spiega perché questo prodotto/categoria è escluso dal recesso...', 'recesso-facile'); ?>"></textarea>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="legal_reference"><?php _e('Riferimento Legale', 'recesso-facile'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="legal_reference" id="legal_reference" class="regular-text" placeholder="<?php esc_attr_e('Es: Art. 59(c) D.Lgs. 206/2005', 'recesso-facile'); ?>">
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <button type="submit" name="rf_add_exception" class="button button-primary">
                                <?php _e('Aggiungi Eccezione', 'recesso-facile'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Exceptions List -->
            <h2><?php _e('Eccezioni Attive', 'recesso-facile'); ?></h2>

            <?php if (empty($exceptions)): ?>
                <p><?php _e('Nessuna eccezione configurata.', 'recesso-facile'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Prodotto/Categoria', 'recesso-facile'); ?></th>
                            <th><?php _e('Tipo Eccezione', 'recesso-facile'); ?></th>
                            <th><?php _e('Motivazione', 'recesso-facile'); ?></th>
                            <th><?php _e('Riferimento', 'recesso-facile'); ?></th>
                            <th><?php _e('Azioni', 'recesso-facile'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exceptions as $exception): ?>
                        <tr>
                            <td>
                                <?php if ($exception->product_id): ?>
                                    <strong><?php _e('Prodotto:', 'recesso-facile'); ?></strong>
                                    <?php
                                    $product = wc_get_product($exception->product_id);
                                    echo $product ? esc_html($product->get_name()) : sprintf(__('ID: %d', 'recesso-facile'), $exception->product_id);
                                    ?>
                                <?php elseif ($exception->category_id): ?>
                                    <strong><?php _e('Categoria:', 'recesso-facile'); ?></strong>
                                    <?php
                                    $term = get_term($exception->category_id, 'product_cat');
                                    echo $term && !is_wp_error($term) ? esc_html($term->name) : sprintf(__('ID: %d', 'recesso-facile'), $exception->category_id);
                                    ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(RF_Exception_Service::get_exception_type_label($exception->exception_type)); ?></td>
                            <td><?php echo esc_html($exception->reason); ?></td>
                            <td><?php echo esc_html($exception->legal_reference); ?></td>
                            <td>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=recesso-facile-exceptions&action=delete&id=' . $exception->id), 'rf_delete_exception_' . $exception->id)); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('Sei sicuro?', 'recesso-facile'); ?>');">
                                    <?php _e('Elimina', 'recesso-facile'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle add exception
     */
    private static function handle_add_exception() {
        $data = array(
            'product_id' => !empty($_POST['product_id']) ? absint($_POST['product_id']) : null,
            'category_id' => !empty($_POST['category_id']) ? absint($_POST['category_id']) : null,
            'exception_type' => isset($_POST['exception_type']) ? sanitize_text_field($_POST['exception_type']) : '',
            'reason' => isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '',
            'legal_reference' => isset($_POST['legal_reference']) ? sanitize_text_field($_POST['legal_reference']) : '',
        );

        $result = RF_Exception_Service::add_exception($data);

        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>' . esc_html__('Eccezione aggiunta con successo.', 'recesso-facile') . '</p></div>';
        }
    }

    /**
     * Handle delete exception
     */
    private static function handle_delete_exception($exception_id) {
        $result = RF_Exception_Service::delete_exception($exception_id);

        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>' . esc_html__('Eccezione eliminata con successo.', 'recesso-facile') . '</p></div>';
        }
    }
}
