/**
 * Recesso Facile - Frontend JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Check if script is loaded properly
    if (typeof recessoFacile === 'undefined') {
        console.error('Recesso Facile: Script not loaded correctly. Missing localized data.');
        return;
    }

    // Form state
    let currentStep = 1;
    let formData = {};
    let orderVerified = false;

    /**
     * Initialize
     */
    $(document).ready(function() {
        initFormSteps();
        initOrderVerification();
        initFormSubmission();
        initRefundMethodToggle();
    });

    /**
     * Initialize form step navigation
     */
    function initFormSteps() {
        // Next button handlers
        $('#rf-next-step-1').on('click', function() {
            if (orderVerified) {
                goToStep(2);
            } else {
                showError(recessoFacile.i18n.error);
            }
        });

        $('#rf-next-step-2').on('click', function() {
            if (validateStep2()) {
                collectFormData();
                updateSummary();
                goToStep(3);
            }
        });

        // Previous button handlers
        $('#rf-prev-step-2').on('click', function() {
            goToStep(1);
        });

        $('#rf-prev-step-3').on('click', function() {
            goToStep(2);
        });
    }

    /**
     * Go to specific step
     */
    function goToStep(step) {
        // Hide all steps
        $('.rf-form-step').removeClass('rf-step-active');

        // Show target step
        $('.rf-step-' + step).addClass('rf-step-active');

        // Update progress indicator
        $('.rf-progress-step').removeClass('rf-step-active rf-step-completed');

        for (let i = 1; i < step; i++) {
            $('.rf-progress-step[data-step="' + i + '"]').addClass('rf-step-completed');
        }

        $('.rf-progress-step[data-step="' + step + '"]').addClass('rf-step-active');

        // Scroll to top
        $('html, body').animate({
            scrollTop: $('.rf-withdrawal-form-wrapper').offset().top - 50
        }, 300);

        currentStep = step;
    }

    /**
     * Initialize order verification
     */
    function initOrderVerification() {
        $('#rf-verify-order').on('click', function() {
            const customerName = $('#rf-customer-name').val().trim();
            const orderId = $('#rf-order-id').val().trim();
            const email = $('#rf-email').val().trim();

            // Basic validation
            if (!customerName || !orderId || !email) {
                showError('Inserisci tutti i campi obbligatori.');
                return;
            }

            if (customerName.length < 3) {
                showError('Inserisci un nome valido (almeno 3 caratteri).');
                return;
            }

            if (!isValidEmail(email)) {
                showError('Inserisci un indirizzo email valido.');
                return;
            }

            verifyOrder(customerName, orderId, email);
        });
    }

    /**
     * Verify order via AJAX
     */
    function verifyOrder(customerName, orderId, email) {
        showLoading(true);
        hideError();

        $.ajax({
            url: recessoFacile.ajax_url,
            type: 'POST',
            data: {
                action: 'rf_verify_order',
                nonce: recessoFacile.nonce,
                customer_name: customerName,
                order_id: orderId,
                email: email
            },
            success: function(response) {
                showLoading(false);

                if (response.success) {
                    orderVerified = true;
                    displayOrderDetails(response.data);
                    $('#rf-verify-order').hide();
                    $('#rf-next-step-1').show();
                } else {
                    showError(response.data.message || recessoFacile.i18n.error);
                }
            },
            error: function() {
                showLoading(false);
                showError(recessoFacile.i18n.error);
            }
        });
    }

    /**
     * Display order details
     */
    function displayOrderDetails(data) {
        $('#rf-detail-order-number').text('#' + data.order_number);
        $('#rf-detail-order-date').text(data.order_date);
        $('#rf-detail-order-total').text(data.order_total);

        // Display products
        let productsHTML = '<ul>';
        data.products.forEach(function(product) {
            productsHTML += '<li>' + product.name + ' (x' + product.quantity + ') - ' + product.total + '</li>';
        });
        productsHTML += '</ul>';
        $('#rf-products').html(productsHTML);

        $('#rf-order-details').slideDown();
    }

    /**
     * Validate step 2
     */
    function validateStep2() {
        let isValid = true;
        clearErrors();

        // Check reason length (if provided or required)
        const reason = $('#rf-reason').val().trim();
        const reasonRequired = $('#rf-reason').prop('required');

        if (reasonRequired && !reason) {
            showFieldError('rf-reason', 'Il motivo è obbligatorio.');
            isValid = false;
        } else if (reason && reason.length < 10) {
            showFieldError('rf-reason', 'Il motivo deve contenere almeno 10 caratteri.');
            isValid = false;
        }

        // Check IBAN if bank transfer selected
        const refundMethod = $('input[name="refund_method"]:checked').val();
        if (refundMethod === 'bank_transfer') {
            const iban = $('#rf-refund-iban').val().trim();
            if (!iban) {
                showFieldError('rf-refund-iban', 'IBAN obbligatorio per bonifico bancario.');
                isValid = false;
            } else if (!isValidIBAN(iban)) {
                showFieldError('rf-refund-iban', 'IBAN non valido.');
                isValid = false;
            }
        }

        return isValid;
    }

    /**
     * Collect form data
     */
    function collectFormData() {
        formData = {
            customer_name: $('#rf-customer-name').val(),
            order_id: $('#rf-order-id').val(),
            email: $('#rf-email').val(),
            reason: $('#rf-reason').val(),
            additional_notes: $('#rf-additional-notes').val(),
            refund_method: $('input[name="refund_method"]:checked').val(),
            refund_iban: $('#rf-refund-iban').val(),
        };
    }

    /**
     * Update summary in step 3
     */
    function updateSummary() {
        $('#rf-summary-name').text(formData.customer_name || 'Non specificato');
        $('#rf-summary-order').text('#' + formData.order_id);
        $('#rf-summary-email').text(formData.email);
        $('#rf-summary-reason').text(formData.reason || 'Non specificato');

        let refundLabel = 'Metodo originale';
        if (formData.refund_method === 'bank_transfer') {
            refundLabel = 'Bonifico bancario (' + formData.refund_iban + ')';
        } else if (formData.refund_method === 'store_credit') {
            refundLabel = 'Credito sul negozio';
        }
        $('#rf-summary-refund').text(refundLabel);
    }

    /**
     * Initialize form submission
     */
    function initFormSubmission() {
        $('#rf-withdrawal-form').on('submit', function(e) {
            e.preventDefault();

            if (!validateStep3()) {
                return;
            }

            submitWithdrawal();
        });
    }

    /**
     * Validate step 3
     */
    function validateStep3() {
        let isValid = true;
        clearErrors();

        // Check terms acceptance
        if (!$('#rf-accept-terms').is(':checked')) {
            showFieldError('rf-accept-terms', 'Devi accettare i termini e condizioni.');
            isValid = false;
        }

        // Check double confirmation
        if ($('#rf-double-confirmation').length && !$('#rf-double-confirmation').is(':checked')) {
            showFieldError('rf-double-confirmation', 'Devi confermare la richiesta di recesso.');
            isValid = false;
        }

        return isValid;
    }

    /**
     * Submit withdrawal via AJAX
     */
    function submitWithdrawal() {
        showLoading(true);
        hideError();

        const submitData = {
            action: 'rf_submit_withdrawal',
            nonce: recessoFacile.nonce,
            ...formData,
            accept_terms: $('#rf-accept-terms').is(':checked') ? 'yes' : 'no',
            double_confirmation: $('#rf-double-confirmation').is(':checked') ? 'yes' : 'no'
        };

        $.ajax({
            url: recessoFacile.ajax_url,
            type: 'POST',
            data: submitData,
            success: function(response) {
                showLoading(false);

                if (response.success) {
                    displaySuccess(response.data);
                } else {
                    showError(response.data.message || recessoFacile.i18n.error);
                }
            },
            error: function() {
                showLoading(false);
                showError(recessoFacile.i18n.error);
            }
        });
    }

    /**
     * Display success message
     */
    function displaySuccess(data) {
        $('.rf-form-step').removeClass('rf-step-active');
        $('.rf-step-success').show();
        $('#rf-receipt-number').text('#' + data.withdrawal_id);
        $('#rf-receipt-hash').text(data.receipt_hash);

        // Update progress
        $('.rf-progress-step').addClass('rf-step-completed');

        // Scroll to top
        $('html, body').animate({
            scrollTop: $('.rf-withdrawal-form-wrapper').offset().top - 50
        }, 300);
    }

    /**
     * Initialize refund method toggle
     */
    function initRefundMethodToggle() {
        $('input[name="refund_method"]').on('change', function() {
            const method = $(this).val();

            if (method === 'bank_transfer') {
                $('.rf-iban-group').slideDown();
                $('#rf-refund-iban').prop('required', true);
            } else {
                $('.rf-iban-group').slideUp();
                $('#rf-refund-iban').prop('required', false).val(''); // Clear value too
            }
        });

        // Trigger on page load to set correct state
        $('input[name="refund_method"]:checked').trigger('change');
    }

    /**
     * Show loading overlay
     */
    function showLoading(show) {
        if (show) {
            $('.rf-loading').fadeIn(200);
        } else {
            $('.rf-loading').fadeOut(200);
        }
    }

    /**
     * Show error message
     */
    function showError(message) {
        $('.rf-error-message')
            .html('<strong>Errore:</strong> ' + message)
            .slideDown();

        $('html, body').animate({
            scrollTop: $('.rf-error-message').offset().top - 100
        }, 300);
    }

    /**
     * Hide error message
     */
    function hideError() {
        $('.rf-error-message').slideUp();
    }

    /**
     * Show field error
     */
    function showFieldError(fieldId, message) {
        const $field = $('#' + fieldId);
        const $group = $field.closest('.rf-form-group, .rf-checkbox-group');

        $group.addClass('rf-has-error');
        $group.find('.rf-field-error').text(message).show();
    }

    /**
     * Clear all errors
     */
    function clearErrors() {
        $('.rf-form-group, .rf-checkbox-group').removeClass('rf-has-error');
        $('.rf-field-error').hide();
    }

    /**
     * Validate email
     */
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    /**
     * Validate IBAN (basic check)
     */
    function isValidIBAN(iban) {
        // Remove spaces and convert to uppercase
        iban = iban.replace(/\s/g, '').toUpperCase();

        // Check length
        if (iban.length < 15 || iban.length > 34) {
            return false;
        }

        // Check format (2 letters, 2 digits, then alphanumeric)
        const regex = /^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/;
        if (!regex.test(iban)) {
            return false;
        }

        // Italian IBAN must be 27 characters
        if (iban.startsWith('IT') && iban.length !== 27) {
            return false;
        }

        return true;
    }

})(jQuery);
