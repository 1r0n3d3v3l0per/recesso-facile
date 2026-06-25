<?php
/**
 * Email Service
 * Handles all email notifications
 *
 * @package RecessoFacile\Services
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Email_Service Class
 */
class RF_Email_Service {

    /**
     * Send customer confirmation email
     *
     * @param int $withdrawal_id Withdrawal ID
     * @return bool Success status
     */
    public static function send_customer_confirmation($withdrawal_id) {
        if (get_option('rf_enable_customer_email', 'yes') !== 'yes') {
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

        $to = $withdrawal->email;
        $subject = sprintf(
            __('Conferma ricezione richiesta di recesso #%d', 'recesso-facile'),
            $withdrawal_id
        );

        $args = array(
            'withdrawal' => $withdrawal,
            'order' => $order,
        );

        $message = self::get_email_content('customer-confirmation', $args);
        $headers = self::get_email_headers();

        // Prepare PDF attachment if enabled
        $attachments = array();
        if (get_option('rf_enable_pdf', 'yes') === 'yes') {
            $pdf_path = RF_PDF_Service::generate_receipt($withdrawal_id);
            if ($pdf_path && file_exists($pdf_path)) {
                $attachments[] = $pdf_path;
            }
        }

        // Send email once with optional attachment
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);

        if ($sent) {
            RF_Activity_Logger::log(
                $withdrawal_id,
                'email_sent',
                __('Email di conferma inviata al cliente', 'recesso-facile')
            );
        }

        return $sent;
    }

    /**
     * Send admin notification email
     *
     * @param int $withdrawal_id Withdrawal ID
     * @return bool Success status
     */
    public static function send_admin_notification($withdrawal_id) {
        if (get_option('rf_enable_admin_email', 'yes') !== 'yes') {
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

        $admin_email = get_option('rf_admin_email', get_option('admin_email'));
        $to = $admin_email;
        $subject = sprintf(
            __('[Recesso Facile] Nuova richiesta di recesso #%d', 'recesso-facile'),
            $withdrawal_id
        );

        $args = array(
            'withdrawal' => $withdrawal,
            'order' => $order,
        );

        $message = self::get_email_content('admin-notification', $args);
        $headers = self::get_email_headers();

        $sent = wp_mail($to, $subject, $message, $headers);

        if ($sent) {
            RF_Activity_Logger::log(
                $withdrawal_id,
                'email_sent',
                __('Email di notifica inviata all\'admin', 'recesso-facile')
            );
        }

        return $sent;
    }

    /**
     * Send status update email
     *
     * @param int $withdrawal_id Withdrawal ID
     * @param string $new_status New status
     * @return bool Success status
     */
    public static function send_status_update($withdrawal_id, $new_status) {
        $withdrawal = RF_Withdrawal_Service::get_withdrawal($withdrawal_id);
        if (!$withdrawal) {
            return false;
        }

        $order = wc_get_order($withdrawal->order_id);
        if (!$order) {
            return false;
        }

        $to = $withdrawal->email;
        $subject = sprintf(
            __('Aggiornamento richiesta di recesso #%d', 'recesso-facile'),
            $withdrawal_id
        );

        $args = array(
            'withdrawal' => $withdrawal,
            'order' => $order,
            'new_status' => $new_status,
        );

        $message = self::get_email_content('status-update', $args);
        $headers = self::get_email_headers();

        $sent = wp_mail($to, $subject, $message, $headers);

        if ($sent) {
            RF_Activity_Logger::log(
                $withdrawal_id,
                'email_sent',
                sprintf(
                    __('Email di aggiornamento status (%s) inviata al cliente', 'recesso-facile'),
                    $new_status
                )
            );
        }

        return $sent;
    }

    /**
     * Send rejection email
     *
     * @param int $withdrawal_id Withdrawal ID
     * @param string $reason Rejection reason
     * @return bool Success status
     */
    public static function send_rejection($withdrawal_id, $reason) {
        $withdrawal = RF_Withdrawal_Service::get_withdrawal($withdrawal_id);
        if (!$withdrawal) {
            return false;
        }

        $order = wc_get_order($withdrawal->order_id);
        if (!$order) {
            return false;
        }

        $to = $withdrawal->email;
        $subject = sprintf(
            __('Richiesta di recesso #%d - Aggiornamento', 'recesso-facile'),
            $withdrawal_id
        );

        $args = array(
            'withdrawal' => $withdrawal,
            'order' => $order,
            'reason' => $reason,
        );

        $message = self::get_email_content('rejection', $args);
        $headers = self::get_email_headers();

        $sent = wp_mail($to, $subject, $message, $headers);

        if ($sent) {
            RF_Activity_Logger::log(
                $withdrawal_id,
                'email_sent',
                __('Email di rifiuto inviata al cliente', 'recesso-facile')
            );
        }

        return $sent;
    }

    /**
     * Get email headers
     *
     * @return array Email headers
     */
    private static function get_email_headers() {
        $from_name = get_option('rf_email_from_name', get_bloginfo('name'));
        $from_email = get_option('rf_email_from_address', get_option('admin_email'));

        // Validate email address
        if (!is_email($from_email)) {
            $from_email = get_option('admin_email');
        }

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', sanitize_text_field($from_name), sanitize_email($from_email)),
        );

        return apply_filters('recesso_facile_email_headers', $headers);
    }

    /**
     * Get email content
     *
     * @param string $template Template name
     * @param array $args Template arguments
     * @return string Email content
     */
    private static function get_email_content($template, $args) {
        // Check for custom template in theme
        $template_path = locate_template(array(
            'recesso-facile/emails/' . $template . '.php',
            'recesso-facile-emails/' . $template . '.php',
        ));

        // Use plugin template if not found in theme
        if (!$template_path) {
            $template_path = RF_PLUGIN_DIR . 'templates/emails/' . $template . '.php';
        }

        if (!file_exists($template_path)) {
            // Fallback to default content
            return self::get_default_email_content($template, $args);
        }

        ob_start();
        // Pass variables to template safely without extract()
        $withdrawal = isset($args['withdrawal']) ? $args['withdrawal'] : null;
        $order = isset($args['order']) ? $args['order'] : null;
        $new_status = isset($args['new_status']) ? $args['new_status'] : null;
        $reason = isset($args['reason']) ? $args['reason'] : null;
        include $template_path;
        $content = ob_get_clean();

        return apply_filters('recesso_facile_email_content', $content, $template, $args);
    }

    /**
     * Get default email content (fallback)
     *
     * @param string $template Template name
     * @param array $args Template arguments
     * @return string Email content
     */
    private static function get_default_email_content($template, $args) {
        // Access variables directly from $args without extract()
        $withdrawal = isset($args['withdrawal']) ? $args['withdrawal'] : null;
        $order = isset($args['order']) ? $args['order'] : null;
        $new_status = isset($args['new_status']) ? $args['new_status'] : null;
        $reason = isset($args['reason']) ? $args['reason'] : null;

        $content = '';

        switch ($template) {
            case 'customer-confirmation':
                $content = sprintf(
                    '<h2>%s</h2>
                    <p>%s</p>
                    <p><strong>%s:</strong> #%d</p>
                    <p><strong>%s:</strong> #%d</p>
                    <p><strong>%s:</strong> %s</p>
                    <p>%s</p>',
                    __('Richiesta di recesso ricevuta', 'recesso-facile'),
                    __('Abbiamo ricevuto la tua richiesta di recesso e la stiamo elaborando.', 'recesso-facile'),
                    __('Numero richiesta', 'recesso-facile'),
                    $withdrawal->id,
                    __('Ordine', 'recesso-facile'),
                    $withdrawal->order_id,
                    __('Data richiesta', 'recesso-facile'),
                    date_i18n(get_option('date_format'), strtotime($withdrawal->request_date)),
                    __('Riceverai un aggiornamento via email appena elaboreremo la tua richiesta.', 'recesso-facile')
                );
                break;

            case 'admin-notification':
                $content = sprintf(
                    '<h2>%s</h2>
                    <p>%s</p>
                    <p><strong>%s:</strong> #%d</p>
                    <p><strong>%s:</strong> #%d</p>
                    <p><strong>%s:</strong> %s</p>
                    <p><strong>%s:</strong> %s</p>
                    <p><a href="%s">%s</a></p>',
                    __('Nuova richiesta di recesso', 'recesso-facile'),
                    __('È stata ricevuta una nuova richiesta di recesso.', 'recesso-facile'),
                    __('Numero richiesta', 'recesso-facile'),
                    $withdrawal->id,
                    __('Ordine', 'recesso-facile'),
                    $withdrawal->order_id,
                    __('Cliente', 'recesso-facile'),
                    $withdrawal->email,
                    __('Motivo', 'recesso-facile'),
                    esc_html($withdrawal->reason),
                    admin_url('admin.php?page=recesso-facile-requests&action=view&id=' . $withdrawal->id),
                    __('Visualizza richiesta', 'recesso-facile')
                );
                break;

            case 'status-update':
                $status_labels = array(
                    'approved' => __('approvata', 'recesso-facile'),
                    'rejected' => __('rifiutata', 'recesso-facile'),
                    'completed' => __('completata', 'recesso-facile'),
                    'cancelled' => __('annullata', 'recesso-facile'),
                );

                $status_label = isset($status_labels[$new_status]) ? $status_labels[$new_status] : $new_status;

                $content = sprintf(
                    '<h2>%s</h2>
                    <p>%s <strong>%s</strong>.</p>
                    <p><strong>%s:</strong> #%d</p>
                    <p><strong>%s:</strong> #%d</p>',
                    __('Aggiornamento richiesta di recesso', 'recesso-facile'),
                    __('La tua richiesta di recesso è stata', 'recesso-facile'),
                    $status_label,
                    __('Numero richiesta', 'recesso-facile'),
                    $withdrawal->id,
                    __('Ordine', 'recesso-facile'),
                    $withdrawal->order_id
                );
                break;

            case 'rejection':
                $content = sprintf(
                    '<h2>%s</h2>
                    <p>%s</p>
                    <p><strong>%s:</strong> %s</p>
                    <p><strong>%s:</strong> #%d</p>',
                    __('Richiesta di recesso non accettata', 'recesso-facile'),
                    __('Ci dispiace informarti che non possiamo accettare la tua richiesta di recesso.', 'recesso-facile'),
                    __('Motivo', 'recesso-facile'),
                    esc_html($reason),
                    __('Numero richiesta', 'recesso-facile'),
                    $withdrawal->id
                );
                break;
        }

        // Wrap in email template
        $wrapped_content = self::wrap_email_content($content);

        return $wrapped_content;
    }

    /**
     * Wrap email content in template
     *
     * @param string $content Email content
     * @return string Wrapped content
     */
    private static function wrap_email_content($content) {
        $header = sprintf(
            '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>%s</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    h2 { color: #2c3e50; }
                    .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999; }
                </style>
            </head>
            <body>
                <div class="container">',
            get_bloginfo('name')
        );

        $footer = sprintf(
            '       <div class="footer">
                        <p>%s<br>%s</p>
                    </div>
                </div>
            </body>
            </html>',
            get_bloginfo('name'),
            get_bloginfo('url')
        );

        return $header . $content . $footer;
    }
}
