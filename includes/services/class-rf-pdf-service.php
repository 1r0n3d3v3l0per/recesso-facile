<?php
/**
 * PDF Service
 * Handles PDF generation for withdrawal receipts
 *
 * @package RecessoFacile\Services
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_PDF_Service Class
 */
class RF_PDF_Service {

    /**
     * Generate withdrawal receipt PDF
     *
     * @param int $withdrawal_id Withdrawal ID
     * @return string|false PDF file path or false on failure
     */
    public static function generate_receipt($withdrawal_id) {
        if (get_option('rf_enable_pdf', 'yes') !== 'yes') {
            return false;
        }

        $withdrawal = RF_Withdrawal_Service::get_withdrawal($withdrawal_id);
        if (!$withdrawal) {
            return false;
        }

        $order = wc_get_order($withdrawal->order_id);
        if (!$order) {
            return false;
        }

        // Generate HTML content
        $html = self::get_pdf_html($withdrawal, $order);

        // Generate PDF using native PHP (no external library needed for simple PDF)
        $pdf_content = self::html_to_pdf($html);

        // Save PDF file in a protected directory
        $pdf_dir = self::get_receipts_dir();

        $pdf_filename = self::get_pdf_filename($withdrawal);
        $pdf_path = $pdf_dir . $pdf_filename;

        $saved = file_put_contents($pdf_path, $pdf_content);

        if ($saved) {
            RF_Activity_Logger::log(
                $withdrawal_id,
                'pdf_generated',
                __('PDF ricevuta generato', 'recesso-facile')
            );

            return $pdf_path;
        }

        return false;
    }

    /**
     * Get PDF HTML content
     *
     * @param object $withdrawal Withdrawal object
     * @param WC_Order $order Order object
     * @return string HTML content
     */
    private static function get_pdf_html($withdrawal, $order) {
        $company_name = get_option('rf_pdf_company_name', get_bloginfo('name'));
        $company_address = get_option('rf_pdf_company_address', '');
        $company_vat = get_option('rf_pdf_company_vat', '');

        $products_data = json_decode($withdrawal->products_json, true);

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #000; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                .company-info { margin-bottom: 20px; }
                .title { font-size: 20px; font-weight: bold; margin: 20px 0; text-align: center; }
                .section { margin-bottom: 20px; }
                .section-title { font-size: 14px; font-weight: bold; margin-bottom: 10px; background: #f0f0f0; padding: 5px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                table th { background: #f0f0f0; padding: 8px; text-align: left; border: 1px solid #ddd; }
                table td { padding: 8px; border: 1px solid #ddd; }
                .info-row { margin-bottom: 5px; }
                .info-label { font-weight: bold; display: inline-block; width: 150px; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #000; font-size: 10px; text-align: center; }
                .hash-box { background: #f9f9f9; padding: 10px; border: 1px solid #ddd; word-break: break-all; font-family: monospace; }
                .legal-notice { font-size: 10px; color: #666; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo esc_html($company_name); ?></h1>
                <?php if ($company_address): ?>
                    <p><?php echo nl2br(esc_html($company_address)); ?></p>
                <?php endif; ?>
                <?php if ($company_vat): ?>
                    <p><strong>P.IVA:</strong> <?php echo esc_html($company_vat); ?></p>
                <?php endif; ?>
            </div>

            <div class="title">
                RICEVUTA RICHIESTA DI RECESSO<br>
                (ai sensi dell'Art. 54-bis e Art. 52 del Codice del Consumo)
            </div>

            <div class="section">
                <div class="section-title">Informazioni Richiesta</div>
                <div class="info-row">
                    <span class="info-label">Numero Richiesta:</span>
                    <span>#<?php echo esc_html($withdrawal->id); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Data Richiesta:</span>
                    <span><?php echo date_i18n('d/m/Y H:i', strtotime($withdrawal->request_date)); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Numero Ordine:</span>
                    <span>#<?php echo esc_html($withdrawal->order_id); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Data Ordine:</span>
                    <span><?php echo date_i18n('d/m/Y', $order->get_date_created()->getTimestamp()); ?></span>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Dati Cliente</div>
                <div class="info-row">
                    <span class="info-label">Nome:</span>
                    <span><?php echo esc_html($withdrawal->customer_name); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span><?php echo esc_html($withdrawal->email); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Indirizzo:</span>
                    <span><?php echo esc_html($order->get_formatted_billing_address()); ?></span>
                </div>
            </div>

            <?php if ($products_data): ?>
            <div class="section">
                <div class="section-title">Prodotti</div>
                <table>
                    <thead>
                        <tr>
                            <th>Prodotto</th>
                            <th>Quantità</th>
                            <th>Importo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products_data as $product): ?>
                        <tr>
                            <td><?php echo esc_html($product['name']); ?></td>
                            <td><?php echo esc_html($product['quantity']); ?></td>
                            <td><?php echo wc_price($product['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="info-row">
                    <span class="info-label">Totale Ordine:</span>
                    <span><strong><?php echo $order->get_formatted_order_total(); ?></strong></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($withdrawal->reason): ?>
            <div class="section">
                <div class="section-title">Motivazione</div>
                <p><?php echo nl2br(esc_html($withdrawal->reason)); ?></p>
            </div>
            <?php endif; ?>

            <div class="section">
                <div class="section-title">Modalità di Rimborso</div>
                <div class="info-row">
                    <span class="info-label">Metodo:</span>
                    <span><?php echo self::get_refund_method_label($withdrawal->refund_method); ?></span>
                </div>
                <?php if ($withdrawal->refund_iban): ?>
                <div class="info-row">
                    <span class="info-label">IBAN:</span>
                    <span><?php echo esc_html($withdrawal->refund_iban); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div class="section">
                <div class="section-title">Hash di Sicurezza (SHA256)</div>
                <div class="hash-box">
                    <?php echo esc_html($withdrawal->receipt_hash); ?>
                </div>
                <p style="font-size: 10px; margin-top: 10px;">
                    Questo hash garantisce l'autenticità e l'integrità del presente documento.
                </p>
            </div>

            <div class="legal-notice">
                <p><strong>Informazioni Legali:</strong></p>
                <p>
                    La presente ricevuta certifica la ricezione della richiesta di recesso ai sensi degli artt. 52 e seguenti
                    del Codice del Consumo (D.Lgs. 206/2005). Il diritto di recesso può essere esercitato entro 14 giorni
                    dalla ricezione del bene, senza necessità di indicare il motivo e senza costi aggiuntivi.
                </p>
                <p>
                    Il rimborso delle somme versate sarà effettuato entro 14 giorni dalla data in cui siamo venuti a conoscenza
                    della decisione di recedere dal contratto, utilizzando lo stesso mezzo di pagamento utilizzato per la
                    transazione iniziale, salvo diverso accordo.
                </p>
            </div>

            <div class="footer">
                <p>
                    Documento generato automaticamente il <?php echo date_i18n('d/m/Y H:i:s'); ?><br>
                    <?php echo get_bloginfo('name'); ?> - <?php echo get_bloginfo('url'); ?>
                </p>
            </div>
        </body>
        </html>
        <?php

        return ob_get_clean();
    }

    /**
     * Convert HTML to PDF (simplified version using FPDF-style output)
     * For production, consider using a library like TCPDF or Dompdf
     *
     * @param string $html HTML content
     * @return string PDF content
     */
    private static function html_to_pdf($html) {
        // For a production-ready solution, you would integrate TCPDF, Dompdf, or similar
        // For now, we'll create a simple text-based PDF structure

        // Strip HTML tags for basic PDF generation
        $text = wp_strip_all_tags($html);

        // Create a basic PDF structure (this is simplified)
        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n";
        $pdf .= "4 0 obj\n<< /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >>\nendobj\n";

        // Content stream
        $stream = "BT\n/F1 12 Tf\n50 750 Td\n";

        // Add text lines (simplified)
        $lines = explode("\n", wordwrap($text, 80));
        $y = 750;
        foreach ($lines as $line) {
            if ($y < 50) break; // Prevent overflow
            $stream .= "(" . addslashes($line) . ") Tj\n0 -15 Td\n";
            $y -= 15;
        }

        $stream .= "ET\n";

        $pdf .= "5 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n$stream\nendstream\nendobj\n";

        // Cross-reference table
        $pdf .= "xref\n0 6\n";
        $pdf .= "0000000000 65535 f\n";
        $pdf .= "0000000009 00000 n\n";
        $pdf .= "0000000056 00000 n\n";
        $pdf .= "0000000115 00000 n\n";
        $pdf .= "0000000214 00000 n\n";
        $pdf .= "0000000308 00000 n\n";

        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . (strlen($pdf) - 50) . "\n%%EOF";

        return apply_filters('recesso_facile_pdf_content', $pdf, $html);
    }

    /**
     * Get refund method label
     *
     * @param string $method Refund method
     * @return string Label
     */
    private static function get_refund_method_label($method) {
        $methods = array(
            'original' => __('Metodo di pagamento originale', 'recesso-facile'),
            'bank_transfer' => __('Bonifico bancario', 'recesso-facile'),
            'store_credit' => __('Credito negozio', 'recesso-facile'),
        );

        return isset($methods[$method]) ? $methods[$method] : $method;
    }

    /**
     * Get the protected receipts directory, creating it (and its access
     * guards) if needed.
     *
     * Receipts contain personal data (name, email, order, reason, IBAN), so
     * the directory is hardened against direct web access with an .htaccess
     * deny rule and an empty index.php. PDFs are served only through the
     * authenticated download() method below, never via a public URL.
     *
     * @return string Absolute path with trailing slash
     */
    public static function get_receipts_dir() {
        $upload_dir = wp_upload_dir();
        $pdf_dir = trailingslashit($upload_dir['basedir']) . 'recesso-facile/receipts/';

        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
        }

        // Deny direct HTTP access (Apache). Nginx setups should block the
        // /uploads/recesso-facile/ path at the server level.
        $htaccess = $pdf_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Order Deny,Allow\nDeny from all\n");
        }

        // Prevent directory listing.
        $index = $pdf_dir . 'index.php';
        if (!file_exists($index)) {
            file_put_contents($index, "<?php\n// Silence is golden.\n");
        }

        return $pdf_dir;
    }

    /**
     * Build the receipt filename from the unguessable receipt hash so files
     * cannot be enumerated by sequential ID.
     *
     * @param object $withdrawal Withdrawal object
     * @return string Filename
     */
    private static function get_pdf_filename($withdrawal) {
        $token = !empty($withdrawal->receipt_hash)
            ? $withdrawal->receipt_hash
            : hash('sha256', (string) $withdrawal->id . wp_salt());

        return 'ricevuta-recesso-' . $token . '.pdf';
    }

    /**
     * Get absolute path to a withdrawal's receipt PDF (no web URL is exposed).
     *
     * @param int $withdrawal_id Withdrawal ID
     * @return string|false Path or false if not found
     */
    public static function get_pdf_path($withdrawal_id) {
        $withdrawal = RF_Withdrawal_Service::get_withdrawal($withdrawal_id);
        if (!$withdrawal) {
            return false;
        }

        $pdf_path = self::get_receipts_dir() . self::get_pdf_filename($withdrawal);

        return file_exists($pdf_path) ? $pdf_path : false;
    }

    /**
     * Check whether the current request is allowed to access a receipt.
     *
     * Allowed: shop managers, the logged-in customer who owns the order, or a
     * request carrying the matching receipt_hash token (the value e-mailed to
     * the customer). This is what makes the download authenticated.
     *
     * @param object $withdrawal Withdrawal object
     * @param string $token      Optional receipt_hash supplied by the request
     * @return bool
     */
    private static function can_access_receipt($withdrawal, $token = '') {
        if (current_user_can('manage_woocommerce')) {
            return true;
        }

        if (is_user_logged_in()
            && (int) $withdrawal->customer_id === get_current_user_id()
            && (int) $withdrawal->customer_id !== 0) {
            return true;
        }

        if (!empty($token)
            && !empty($withdrawal->receipt_hash)
            && hash_equals($withdrawal->receipt_hash, $token)) {
            return true;
        }

        return false;
    }

    /**
     * Stream a receipt PDF to the browser after an authorization check.
     *
     * @param int    $withdrawal_id Withdrawal ID
     * @param string $token         Optional receipt_hash for token-based access
     * @return void|WP_Error
     */
    public static function download($withdrawal_id, $token = '') {
        $withdrawal = RF_Withdrawal_Service::get_withdrawal($withdrawal_id);
        if (!$withdrawal) {
            return new WP_Error('not_found', __('Ricevuta non trovata.', 'recesso-facile'));
        }

        if (!self::can_access_receipt($withdrawal, $token)) {
            return new WP_Error('forbidden', __('Non sei autorizzato ad accedere a questa ricevuta.', 'recesso-facile'));
        }

        $pdf_path = self::get_receipts_dir() . self::get_pdf_filename($withdrawal);
        if (!file_exists($pdf_path)) {
            $pdf_path = self::generate_receipt($withdrawal_id);
        }

        if ($pdf_path && file_exists($pdf_path)) {
            nocache_headers();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="ricevuta-recesso-' . absint($withdrawal_id) . '.pdf"');
            header('Content-Length: ' . filesize($pdf_path));
            readfile($pdf_path);
            exit;
        }

        return new WP_Error('generation_failed', __('Impossibile generare la ricevuta.', 'recesso-facile'));
    }
}
