<?php
/**
 * Validator Class
 * Handles all validation logic
 *
 * @package RecessoFacile\Validators
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * RF_Validator Class
 */
class RF_Validator {

    /**
     * Validate withdrawal request
     *
     * @param array $data Request data
     * @return bool|WP_Error True if valid, error otherwise
     */
    public static function validate_withdrawal_request($data) {
        $errors = array();

        // Validate customer name
        if (empty($data['customer_name'])) {
            $errors['customer_name'] = __('Nome e cognome obbligatori.', 'recesso-facile');
        } elseif (strlen($data['customer_name']) < 3) {
            $errors['customer_name'] = __('Il nome deve contenere almeno 3 caratteri.', 'recesso-facile');
        } elseif (strlen($data['customer_name']) > 100) {
            $errors['customer_name'] = __('Il nome è troppo lungo (massimo 100 caratteri).', 'recesso-facile');
        }

        // Validate order ID
        if (empty($data['order_id'])) {
            $errors['order_id'] = __('ID ordine obbligatorio.', 'recesso-facile');
        } elseif (!is_numeric($data['order_id'])) {
            $errors['order_id'] = __('ID ordine non valido.', 'recesso-facile');
        }

        // Validate email
        if (empty($data['email'])) {
            $errors['email'] = __('Email obbligatoria.', 'recesso-facile');
        } elseif (!is_email($data['email'])) {
            $errors['email'] = __('Email non valida.', 'recesso-facile');
        }

        // Validate reason (if required)
        if (get_option('rf_require_reason', 'yes') === 'yes') {
            if (empty($data['reason'])) {
                $errors['reason'] = __('Motivo obbligatorio.', 'recesso-facile');
            } elseif (strlen($data['reason']) < 10) {
                $errors['reason'] = __('Il motivo deve contenere almeno 10 caratteri.', 'recesso-facile');
            }
        }

        // Validate IBAN if bank transfer is selected
        if (isset($data['refund_method']) && $data['refund_method'] === 'bank_transfer') {
            if (empty($data['refund_iban'])) {
                $errors['refund_iban'] = __('IBAN obbligatorio per bonifico bancario.', 'recesso-facile');
            } elseif (!self::validate_iban($data['refund_iban'])) {
                $errors['refund_iban'] = __('IBAN non valido.', 'recesso-facile');
            }
        }

        // Validate terms acceptance
        if (empty($data['accept_terms'])) {
            $errors['accept_terms'] = __('Devi accettare i termini e condizioni.', 'recesso-facile');
        }

        // Validate double confirmation
        if (get_option('rf_enable_double_confirmation', 'yes') === 'yes') {
            if (empty($data['double_confirmation'])) {
                $errors['double_confirmation'] = __('Devi confermare la richiesta di recesso.', 'recesso-facile');
            }
        }

        // Allow custom validation
        $errors = apply_filters('recesso_facile_validation_errors', $errors, $data);

        if (!empty($errors)) {
            $error_messages = implode(' ', $errors);
            return new WP_Error('validation_failed', $error_messages, array('errors' => $errors));
        }

        return true;
    }

    /**
     * Validate IBAN
     *
     * @param string $iban IBAN to validate
     * @return bool True if valid
     */
    public static function validate_iban($iban) {
        // Remove spaces and convert to uppercase
        $iban = strtoupper(str_replace(' ', '', $iban));

        // Check length (15-34 characters)
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }

        // Check format (2 letters, 2 digits, then alphanumeric)
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }

        // Validate Italian IBAN if it's IT
        if (substr($iban, 0, 2) === 'IT') {
            if (strlen($iban) !== 27) {
                return false;
            }
        }

        // IBAN checksum validation
        $country_code = substr($iban, 0, 2);
        $check_digits = substr($iban, 2, 2);
        $account = substr($iban, 4);

        // Move first 4 characters to end
        $rearranged = $account . $country_code . $check_digits;

        // Replace letters with numbers (A=10, B=11, ..., Z=35)
        $numeric_iban = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (is_numeric($char)) {
                $numeric_iban .= $char;
            } else {
                $numeric_iban .= (ord($char) - ord('A') + 10);
            }
        }

        // Perform mod-97 operation
        $checksum = self::mod97($numeric_iban);

        return $checksum === 1;
    }

    /**
     * Perform mod-97 operation for large numbers
     *
     * @param string $number Number as string
     * @return int Remainder
     */
    private static function mod97($number) {
        $remainder = 0;

        for ($i = 0; $i < strlen($number); $i++) {
            $remainder = ($remainder * 10 + intval($number[$i])) % 97;
        }

        return $remainder;
    }

    /**
     * Validate email against order
     *
     * @param string $email Email address
     * @param int $order_id Order ID
     * @return bool|WP_Error True if valid, error otherwise
     */
    public static function validate_email_for_order($email, $order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('invalid_order', __('Ordine non trovato.', 'recesso-facile'));
        }

        $order_email = $order->get_billing_email();

        if (strtolower($email) !== strtolower($order_email)) {
            return new WP_Error('email_mismatch', __('L\'email non corrisponde all\'ordine.', 'recesso-facile'));
        }

        return true;
    }

    /**
     * Sanitize withdrawal data
     *
     * @param array $data Raw data
     * @return array Sanitized data
     */
    public static function sanitize_withdrawal_data($data) {
        $sanitized = array();

        if (isset($data['customer_name'])) {
            $sanitized['customer_name'] = sanitize_text_field($data['customer_name']);
        }

        if (isset($data['order_id'])) {
            $sanitized['order_id'] = absint($data['order_id']);
        }

        if (isset($data['email'])) {
            $sanitized['email'] = sanitize_email($data['email']);
        }

        if (isset($data['reason'])) {
            $sanitized['reason'] = sanitize_textarea_field($data['reason']);
        }

        if (isset($data['additional_notes'])) {
            $sanitized['additional_notes'] = sanitize_textarea_field($data['additional_notes']);
        }

        if (isset($data['refund_method'])) {
            $sanitized['refund_method'] = sanitize_text_field($data['refund_method']);
        }

        if (isset($data['refund_iban'])) {
            $sanitized['refund_iban'] = strtoupper(str_replace(' ', '', sanitize_text_field($data['refund_iban'])));
        }

        if (isset($data['accept_terms'])) {
            $sanitized['accept_terms'] = (bool) $data['accept_terms'];
        }

        if (isset($data['double_confirmation'])) {
            $sanitized['double_confirmation'] = (bool) $data['double_confirmation'];
        }

        return apply_filters('recesso_facile_sanitize_data', $sanitized, $data);
    }

    /**
     * Validate exception data
     *
     * @param array $data Exception data
     * @return bool|WP_Error True if valid, error otherwise
     */
    public static function validate_exception($data) {
        $errors = array();

        // Must have either product_id or category_id
        if (empty($data['product_id']) && empty($data['category_id'])) {
            $errors['target'] = __('Devi specificare un prodotto o una categoria.', 'recesso-facile');
        }

        // Validate exception type
        $valid_types = array(
            'art_59_b', 'art_59_c', 'art_59_d', 'art_59_e', 'art_59_f',
            'art_59_g', 'art_59_h', 'art_59_i', 'art_59_l', 'art_59_m', 'art_59_n', 'custom'
        );

        if (empty($data['exception_type'])) {
            $errors['exception_type'] = __('Tipo di eccezione obbligatorio.', 'recesso-facile');
        } elseif (!in_array($data['exception_type'], $valid_types, true)) {
            $errors['exception_type'] = __('Tipo di eccezione non valido.', 'recesso-facile');
        }

        // Validate reason
        if (empty($data['reason'])) {
            $errors['reason'] = __('Motivazione obbligatoria.', 'recesso-facile');
        }

        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(' ', $errors), array('errors' => $errors));
        }

        return true;
    }
}
