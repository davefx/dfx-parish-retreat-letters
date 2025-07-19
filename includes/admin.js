/**
 * Admin JavaScript for DFX Parish Retreat Letters
 *
 * @package DFX_Parish_Retreat_Letters
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Add modal styles
        if (!$('#dfx-modal-styles').length) {
            $('<style id="dfx-modal-styles">' +
                '.dfx-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; display: none; }' +
                '.dfx-modal-dialog { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 4px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 500px; width: 90%; }' +
                '.dfx-modal-header { padding: 20px 20px 10px; border-bottom: 1px solid #ddd; }' +
                '.dfx-modal-header h3 { margin: 0; font-size: 18px; color: #d63638; }' +
                '.dfx-modal-body { padding: 20px; }' +
                '.dfx-warning-message { background: #fff8e5; border: 1px solid #f0b849; border-radius: 4px; padding: 15px; margin-bottom: 20px; }' +
                '.dfx-warning-message p { margin: 0 0 10px; }' +
                '.dfx-warning-message ul { margin: 0; padding-left: 20px; }' +
                '.dfx-confirmation-section p { margin: 10px 0; }' +
                '.dfx-confirmation-section input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }' +
                '.dfx-modal-footer { padding: 15px 20px; border-top: 1px solid #ddd; text-align: right; }' +
                '.dfx-modal-footer .button { margin-left: 10px; }' +
            '</style>').appendTo('head');
        }
        // Handle delete retreat button clicks
        $('.dfx-delete-retreat').on('click', function(e) {
            e.preventDefault();
            
            var retreatId = $(this).data('retreat-id');
            var retreatName = $(this).data('retreat-name');
            var $row = $(this).closest('tr');
            var $button = $(this);
            
            // Show custom delete confirmation modal
            showDeleteRetreatModal(retreatId, retreatName, $row, $button);
        });

        // Function to show delete retreat confirmation modal
        function showDeleteRetreatModal(retreatId, retreatName, $row, $button) {
            // Create modal HTML
            var modalHtml = 
                '<div id="dfx-delete-retreat-modal" class="dfx-modal-overlay">' +
                    '<div class="dfx-modal-dialog">' +
                        '<div class="dfx-modal-header">' +
                            '<h3>' + dfxRetreatsAdmin.messages.deleteRetreatTitle + '</h3>' +
                        '</div>' +
                        '<div class="dfx-modal-body">' +
                            '<div class="dfx-warning-message">' +
                                '<p><strong>' + dfxRetreatsAdmin.messages.deleteWarning + '</strong></p>' +
                                '<ul>' +
                                    '<li>' + dfxRetreatsAdmin.messages.deleteWarningAttendants + '</li>' +
                                    '<li>' + dfxRetreatsAdmin.messages.deleteWarningLetters + '</li>' +
                                    '<li>' + dfxRetreatsAdmin.messages.deleteWarningPermanent + '</li>' +
                                '</ul>' +
                            '</div>' +
                            '<div class="dfx-confirmation-section">' +
                                '<p>' + dfxRetreatsAdmin.messages.typeRetreatName + '</p>' +
                                '<p><strong>' + retreatName + '</strong></p>' +
                                '<input type="text" id="dfx-retreat-name-confirm" placeholder="' + dfxRetreatsAdmin.messages.retreatNamePlaceholder + '" />' +
                            '</div>' +
                        '</div>' +
                        '<div class="dfx-modal-footer">' +
                            '<button type="button" id="dfx-confirm-delete" class="button button-primary" disabled>' + dfxRetreatsAdmin.messages.deleteButton + '</button>' +
                            '<button type="button" id="dfx-cancel-delete" class="button">' + dfxRetreatsAdmin.messages.cancelButton + '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            
            // Add modal to body
            $('body').append(modalHtml);
            
            var $modal = $('#dfx-delete-retreat-modal');
            var $confirmInput = $('#dfx-retreat-name-confirm');
            var $confirmButton = $('#dfx-confirm-delete');
            
            // Show modal
            $modal.fadeIn();
            $confirmInput.focus();
            
            // Check input as user types
            $confirmInput.on('input', function() {
                var enteredName = $(this).val().trim();
                if (enteredName === retreatName) {
                    $confirmButton.prop('disabled', false);
                } else {
                    $confirmButton.prop('disabled', true);
                }
            });
            
            // Handle cancel
            $('#dfx-cancel-delete, .dfx-modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $modal.fadeOut(function() {
                        $modal.remove();
                    });
                }
            });
            
            // Handle confirm delete
            $confirmButton.on('click', function() {
                $modal.fadeOut(function() {
                    $modal.remove();
                });
                
                // Disable button and show loading state
                $button.prop('disabled', true).text(dfxRetreatsAdmin.messages.deleting);
                
                $.ajax({
                    url: dfxRetreatsAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dfx_delete_retreat',
                        retreat_id: retreatId,
                        retreat_name: retreatName,
                        nonce: dfxRetreatsAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Remove the row with animation
                            $row.fadeOut(300, function() {
                                $(this).remove();
                                
                                // Show success message
                                $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                                    .insertAfter('.wp-header-end')
                                    .delay(3000)
                                    .fadeOut();
                            });
                        } else {
                            alert(response.data.message || dfxRetreatsAdmin.messages.deleteError);
                            // Re-enable button
                            $button.prop('disabled', false).text(dfxRetreatsAdmin.messages.deleteButton);
                        }
                    },
                    error: function() {
                        alert(dfxRetreatsAdmin.messages.deleteError);
                        // Re-enable button
                        $button.prop('disabled', false).text(dfxRetreatsAdmin.messages.deleteButton);
                    }
                });
            });
        }

        // Handle delete attendant button clicks
        $('.dfx-delete-attendant').on('click', function(e) {
            e.preventDefault();
            
            var attendantId = $(this).data('attendant-id');
            var $row = $(this).closest('tr');
            
            if (!confirm(dfxRetreatsAdmin.messages.confirmDeleteAttendant || dfxRetreatsAdmin.messages.confirmDelete)) {
                return;
            }
            
            // Disable button and show loading state
            $(this).prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: dfxRetreatsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dfx_delete_attendant',
                    attendant_id: attendantId,
                    nonce: dfxRetreatsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the row with animation
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Show success message
                            $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                                .insertAfter('.wp-header-end')
                                .delay(3000)
                                .fadeOut();
                        });
                    } else {
                        alert(response.data.message || 'Error deleting attendant.');
                        // Re-enable button
                        $('.dfx-delete-attendant[data-attendant-id="' + attendantId + '"]')
                            .prop('disabled', false)
                            .text('Delete');
                    }
                },
                error: function() {
                    alert('Error deleting attendant. Please try again.');
                    // Re-enable button
                    $('.dfx-delete-attendant[data-attendant-id="' + attendantId + '"]')
                        .prop('disabled', false)
                        .text('Delete');
                }
            });
        });
        
        // Form validation for add/edit retreat
        $('form').on('submit', function(e) {
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();
            
            if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                e.preventDefault();
                alert('End date cannot be before start date.');
                $('#end_date').focus();
                return false;
            }
        });
        
        // Auto-dismiss notices
        $('.notice.is-dismissible').delay(5000).fadeOut();

        // Handle generate message URL button clicks
        $('.dfx-generate-url').on('click', function(e) {
            e.preventDefault();
            
            var attendantId = $(this).data('attendant-id');
            var $button = $(this);
            
            // Disable button and show loading state
            $button.prop('disabled', true).text(dfxRetreatsAdmin.messages.generating);
            
            $.ajax({
                url: dfxRetreatsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dfx_generate_message_url',
                    attendant_id: attendantId,
                    nonce: dfxRetreatsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Replace button with copy URL button
                        var newButton = '<button type="button" class="button button-small button-primary dfx-copy-url" data-url="' + response.data.url + '">' +
                                       'Copy Message URL</button>';
                        $button.replaceWith(newButton);
                        
                        // Show success message
                        $('<div class="notice notice-success is-dismissible"><p>' + dfxRetreatsAdmin.messages.urlGenerated + '</p></div>')
                            .insertAfter('.wp-header-end')
                            .delay(3000)
                            .fadeOut();
                            
                        // Auto-copy URL to clipboard
                        copyToClipboard(response.data.url);
                    } else {
                        alert(response.data.message || dfxRetreatsAdmin.messages.generateError);
                        // Re-enable button
                        $button.prop('disabled', false).text('Generate Message URL');
                    }
                },
                error: function() {
                    alert(dfxRetreatsAdmin.messages.generateError);
                    // Re-enable button
                    $button.prop('disabled', false).text('Generate Message URL');
                }
            });
        });

        // Handle copy URL button clicks (using event delegation for dynamically added buttons)
        $(document).on('click', '.dfx-copy-url', function(e) {
            e.preventDefault();
            
            var url = $(this).data('url');
            var $button = $(this);
            
            if (copyToClipboard(url)) {
                // Temporarily change button text
                var originalText = $button.text();
                $button.text(dfxRetreatsAdmin.messages.urlCopied);
                setTimeout(function() {
                    $button.text(originalText);
                }, 2000);
            }
        });

        // Handle print message button clicks
        $('.dfx-print-message').on('click', function(e) {
            e.preventDefault();
            
            var messageId = $(this).data('message-id');
            var $button = $(this);
            
            // Disable button and show loading state
            $button.prop('disabled', true).text(dfxRetreatsAdmin.messages.printing);
            
            $.ajax({
                url: dfxRetreatsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dfx_print_message',
                    message_id: messageId,
                    nonce: dfxRetreatsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showPrintModal(response.data.html);
                    } else {
                        alert(response.data.message || dfxRetreatsAdmin.messages.printError);
                    }
                    // Re-enable button
                    $button.prop('disabled', false).text('Print');
                },
                error: function() {
                    alert(dfxRetreatsAdmin.messages.printError);
                    // Re-enable button
                    $button.prop('disabled', false).text('Print');
                }
            });
        });

        // Handle delete message button clicks
        $('.dfx-delete-message').on('click', function(e) {
            e.preventDefault();
            
            var messageId = $(this).data('message-id');
            var $row = $(this).closest('tr');
            
            if (!confirm(dfxRetreatsAdmin.messages.confirmDeleteMessage)) {
                return;
            }
            
            // Disable button and show loading state
            $(this).prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: dfxRetreatsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dfx_delete_message',
                    message_id: messageId,
                    nonce: dfxRetreatsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the row with animation
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Show success message
                            $('<div class="notice notice-success is-dismissible"><p>' + dfxRetreatsAdmin.messages.messageDeleted + '</p></div>')
                                .insertAfter('.wp-header-end')
                                .delay(3000)
                                .fadeOut();
                        });
                    } else {
                        alert(response.data.message || 'Error deleting message.');
                        // Re-enable button
                        $('.dfx-delete-message[data-message-id="' + messageId + '"]')
                            .prop('disabled', false)
                            .text('Delete');
                    }
                },
                error: function() {
                    alert('Error deleting message. Please try again.');
                    // Re-enable button
                    $('.dfx-delete-message[data-message-id="' + messageId + '"]')
                        .prop('disabled', false)
                        .text('Delete');
                }
            });
        });

        // Function to copy text to clipboard
        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                // Use the Clipboard API if available
                navigator.clipboard.writeText(text).then(function() {
                    return true;
                }).catch(function() {
                    return fallbackCopyToClipboard(text);
                });
                return true;
            } else {
                // Fallback for older browsers
                return fallbackCopyToClipboard(text);
            }
        }

        // Fallback copy method for older browsers
        function fallbackCopyToClipboard(text) {
            var textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                var successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                return successful;
            } catch (err) {
                document.body.removeChild(textArea);
                return false;
            }
        }

        // Function to show print modal
        function showPrintModal(html) {
            // Remove any existing modal
            $('#dfx-print-modal').remove();
            
            // Create modal HTML
            var modalHtml = '<div id="dfx-print-modal">' +
                           '<div class="dfx-print-actions no-print">' +
                           '<button type="button" id="dfx-print-btn" class="button button-primary">Print</button>' +
                           '<button type="button" id="dfx-close-print" class="button">Close</button>' +
                           '</div>' +
                           '<div id="dfx-print-content">' + html + '</div>' +
                           '</div>';
            
            // Add modal to body
            $('body').append(modalHtml);
            
            // Show modal
            $('#dfx-print-modal').show();
            
            // Handle print button
            $('#dfx-print-btn').on('click', function() {
                window.print();
            });
            
            // Handle close button
            $('#dfx-close-print').on('click', function() {
                $('#dfx-print-modal').remove();
            });
            
            // Handle escape key
            $(document).on('keyup.printModal', function(e) {
                if (e.keyCode === 27) { // Escape key
                    $('#dfx-print-modal').remove();
                    $(document).off('keyup.printModal');
                }
            });
        }
    });

})(jQuery);