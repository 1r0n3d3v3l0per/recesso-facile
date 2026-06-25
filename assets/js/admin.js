/**
 * Recesso Facile - Admin JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Initialize
     */
    $(document).ready(function() {
        initStatusUpdate();
        initDeleteActions();
        initExceptionForm();
    });

    /**
     * Initialize status update
     */
    function initStatusUpdate() {
        $('.rf-update-status-btn').on('click', function(e) {
            e.preventDefault();

            const $btn = $(this);
            const withdrawalId = $btn.data('withdrawal-id');
            const newStatus = $btn.data('status');

            if (!confirm(recessoFacileAdmin.i18n.confirm_update)) {
                return;
            }

            updateWithdrawalStatus(withdrawalId, newStatus);
        });
    }

    /**
     * Update withdrawal status via AJAX
     */
    function updateWithdrawalStatus(withdrawalId, newStatus, adminNotes = '') {
        $.ajax({
            url: recessoFacileAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'rf_update_withdrawal_status',
                nonce: recessoFacileAdmin.nonce,
                withdrawal_id: withdrawalId,
                status: newStatus,
                admin_notes: adminNotes
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotice('error', response.data.message || recessoFacileAdmin.i18n.error);
                }
            },
            error: function() {
                showNotice('error', recessoFacileAdmin.i18n.error);
            }
        });
    }

    /**
     * Initialize delete actions
     */
    function initDeleteActions() {
        // Delete withdrawal
        $('.rf-delete-withdrawal').on('click', function(e) {
            if (!confirm(recessoFacileAdmin.i18n.confirm_delete)) {
                e.preventDefault();
                return false;
            }
        });

        // Delete exception
        $('.rf-delete-exception').on('click', function(e) {
            if (!confirm(recessoFacileAdmin.i18n.confirm_delete)) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Initialize exception form
     */
    function initExceptionForm() {
        // Product/Category toggle
        $('#product_id').on('change', function() {
            if ($(this).val()) {
                $('#category_id').val('').prop('disabled', true);
            } else {
                $('#category_id').prop('disabled', false);
            }
        });

        $('#category_id').on('change', function() {
            if ($(this).val()) {
                $('#product_id').val('').prop('disabled', true);
            } else {
                $('#product_id').prop('disabled', false);
            }
        });

        // WooCommerce product search (if available)
        if ($.fn.selectWoo) {
            $('.wc-product-search').selectWoo({
                ajax: {
                    url: recessoFacileAdmin.ajax_url,
                    dataType: 'json',
                    data: function(params) {
                        return {
                            term: params.term,
                            action: 'woocommerce_json_search_products',
                            security: recessoFacileAdmin.nonce
                        };
                    },
                    processResults: function(data) {
                        const results = [];
                        $.each(data, function(id, text) {
                            results.push({
                                id: id,
                                text: text
                            });
                        });
                        return {
                            results: results
                        };
                    }
                },
                minimumInputLength: 3
            });
        }
    }

    /**
     * Show admin notice
     */
    function showNotice(type, message) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

        $('.rf-admin-wrap h1').after($notice);

        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    /**
     * Initialize chart (if needed in future)
     */
    function initChart() {
        // Placeholder for future chart implementation
        // Could use Chart.js for statistics visualization
    }

    /**
     * Export data (future feature)
     */
    function exportData(format) {
        // Placeholder for CSV/PDF export functionality
        window.location.href = recessoFacileAdmin.ajax_url +
            '?action=rf_export_data&format=' + format +
            '&nonce=' + recessoFacileAdmin.nonce;
    }

})(jQuery);
