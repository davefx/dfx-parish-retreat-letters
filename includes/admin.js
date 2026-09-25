/**
 * Admin JavaScript for DFX Parish Retreat Letters
 *
 * @package DFXPRL
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Check if dfxprlAdmin is available
        if (typeof dfxprlAdmin === 'undefined') {
            console.warn('dfxprlAdmin is not defined. Admin functionality may be limited.');
            // Create a fallback object to prevent errors
            window.dfxprlAdmin = {
                ajaxurl: (typeof ajaxurl !== 'undefined') ? ajaxurl : '',
                nonce: '',
                messages: {
                    confirmDelete: 'Are you sure you want to delete this item?',
                    confirmDeleteAttendant: 'Are you sure you want to delete this attendant?',
                    confirmDeleteMessage: 'Are you sure you want to delete this message?',
                    deleteRetreatTitle: 'Delete Retreat - Confirmation Required',
                    deleteWarning: 'WARNING: This action cannot be undone!',
                    deleteWarningAttendants: 'All attendants for this retreat will be permanently deleted',
                    deleteWarningLetters: 'All letters and related information will be permanently deleted',
                    deleteWarningPermanent: 'This action is irreversible and cannot be restored',
                    typeRetreatName: 'To confirm deletion, please type the exact retreat name below:',
                    retreatNamePlaceholder: 'Type retreat name here...',
                    deleteButton: 'Delete Forever',
                    cancelButton: 'Cancel',
                    deleting: 'Deleting...',
                    deleteError: 'Error deleting item. Please try again.',
                    generating: 'Generating URL...',
                    generateError: 'Error generating message URL. Please try again.',
                    urlGenerated: 'Message URL generated successfully!',
                    urlCopied: 'URL copied to clipboard!',
                    copyError: 'Failed to copy URL. Please copy it manually.',
                    printing: 'Preparing for print...',
                    printError: 'Error preparing message for print. Please try again.',
                    downloading: 'Downloading...',
                    downloadError: 'Error downloading file. Please try again.',
                    messageDeleted: 'Message deleted successfully.'
                }
            };
        }
        // Add modal styles
        if (!$('#dfxprl-modal-styles').length) {
            $('<style id="dfxprl-modal-styles">' +
                '.dfxprl-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; display: none; }' +
                '.dfxprl-modal-dialog { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 4px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 500px; width: 90%; }' +
                '.dfxprl-modal-header { padding: 20px 20px 10px; border-bottom: 1px solid #ddd; position: relative; }' +
                '.dfxprl-modal-header h3 { margin: 0; font-size: 18px; color: #d63638; }' +
                '.dfxprl-modal-close { position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 24px; line-height: 1; cursor: pointer; color: #666; padding: 0; }' +
                '.dfxprl-modal-close:hover { color: #000; }' +
                '.dfxprl-modal-body { padding: 20px; }' +
                '.dfxprl-warning-message { background: #fff8e5; border: 1px solid #f0b849; border-radius: 4px; padding: 15px; margin-bottom: 20px; }' +
                '.dfxprl-warning-message p { margin: 0 0 10px; }' +
                '.dfxprl-warning-message ul { margin: 0; padding-left: 20px; }' +
                '.dfxprl-confirmation-section p { margin: 10px 0; }' +
                '.dfxprl-confirmation-section input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }' +
                '.dfxprl-modal-footer { padding: 15px 20px; border-top: 1px solid #ddd; text-align: right; }' +
                '.dfxprl-modal-footer .button { margin-left: 10px; }' +
                '#dfxprl-print-log-modal .dfxprl-modal-dialog { max-width: 700px; }' +
                '#dfxprl-print-log-modal .dfxprl-modal-body table { margin: 0; }' +
                '#dfxprl-print-log-modal .dfxprl-modal-body th, #dfxprl-print-log-modal .dfxprl-modal-body td { padding: 8px 12px; }' +
            '</style>').appendTo('head');
        }
        // Handle delete retreat button clicks
        $('.dfxprl-delete-retreat').on('click', function(e) {
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
                '<div id="dfxprl-delete-retreat-modal" class="dfxprl-modal-overlay">' +
                    '<div class="dfxprl-modal-dialog">' +
                        '<div class="dfxprl-modal-header">' +
                            '<h3>' + dfxprlAdmin.messages.deleteRetreatTitle + '</h3>' +
                        '</div>' +
                        '<div class="dfxprl-modal-body">' +
                            '<div class="dfxprl-warning-message">' +
                                '<p><strong>' + dfxprlAdmin.messages.deleteWarning + '</strong></p>' +
                                '<ul>' +
                                    '<li>' + dfxprlAdmin.messages.deleteWarningAttendants + '</li>' +
                                    '<li>' + dfxprlAdmin.messages.deleteWarningLetters + '</li>' +
                                    '<li>' + dfxprlAdmin.messages.deleteWarningPermanent + '</li>' +
                                '</ul>' +
                            '</div>' +
                            '<div class="dfxprl-confirmation-section">' +
                                '<p>' + dfxprlAdmin.messages.typeRetreatName + '</p>' +
                                '<p><strong>' + retreatName + '</strong></p>' +
                                '<input type="text" id="dfxprl-retreat-name-confirm" placeholder="' + dfxprlAdmin.messages.retreatNamePlaceholder + '" />' +
                            '</div>' +
                        '</div>' +
                        '<div class="dfxprl-modal-footer">' +
                            '<button type="button" id="dfxprl-confirm-delete" class="button button-primary" disabled>' + dfxprlAdmin.messages.deleteButton + '</button>' +
                            '<button type="button" id="dfxprl-cancel-delete" class="button">' + dfxprlAdmin.messages.cancelButton + '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            // Add modal to body
            $('body').append(modalHtml);

            var $modal = $('#dfxprl-delete-retreat-modal');
            var $confirmInput = $('#dfxprl-retreat-name-confirm');
            var $confirmButton = $('#dfxprl-confirm-delete');

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
            $('#dfxprl-cancel-delete, .dfxprl-modal-overlay').on('click', function(e) {
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
                $button.prop('disabled', true).text(dfxprlAdmin.messages.deleting);

                $.ajax({
                    url: dfxprlAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dfxprl_delete_retreat',
                        retreat_id: retreatId,
                        retreat_name: retreatName,
                        nonce: dfxprlAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Remove the row with animation
                            $row.fadeOut(300, function() {
                                $(this).remove();

                                // Show success message
                                $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                                    .insertAfter('.wp-header-end');
                            });
                        } else {
                            alert(response.data.message || dfxprlAdmin.messages.deleteError);
                            // Re-enable button
                            $button.prop('disabled', false).text(dfxprlAdmin.messages.deleteButton);
                        }
                    },
                    error: function() {
                        alert(dfxprlAdmin.messages.deleteError);
                        // Re-enable button
                        $button.prop('disabled', false).text(dfxprlAdmin.messages.deleteButton);
                    }
                });
            });
        }

        // Handle delete attendant button clicks
        $('.dfxprl-delete-attendant').on('click', function(e) {
            e.preventDefault();

            var attendantId = $(this).data('attendant-id');
            var $row = $(this).closest('tr');

            // Check if dfxprlAdmin is available
            var confirmMessage = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.messages && dfxprlAdmin.messages.confirmDeleteAttendant)
                ? dfxprlAdmin.messages.confirmDeleteAttendant
                : 'Are you sure you want to delete this attendant?';

            if (!confirm(confirmMessage)) {
                return;
            }

            // Disable button and show loading state
            $(this).prop('disabled', true).text('Deleting...');

            var ajaxUrl = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.ajaxurl)
                ? dfxprlAdmin.ajaxurl
                : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.nonce)
                ? dfxprlAdmin.nonce
                : '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfxprl_delete_attendant',
                    attendant_id: attendantId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the row with animation
                        $row.fadeOut(300, function() {
                            $(this).remove();

                            // Show success message
                            var successMessage = response.data.message || 'Attendant deleted successfully.';
                            $('<div class="notice notice-success is-dismissible"><p>' + successMessage + '</p></div>')
                                .insertAfter('.wp-header-end');
                        });
                    } else {
                        alert(response.data.message || 'Error deleting attendant.');
                        // Re-enable button
                        $('.dfxprl-delete-attendant[data-attendant-id="' + attendantId + '"]')
                            .prop('disabled', false)
                            .text('Delete');
                    }
                },
                error: function() {
                    alert('Error deleting attendant. Please try again.');
                    // Re-enable button
                    $('.dfxprl-delete-attendant[data-attendant-id="' + attendantId + '"]')
                        .prop('disabled', false)
                        .text('Delete');
                }
            });
        });

        // Handle delete all attendants button clicks
        $('.dfxprl-delete-all-attendants').on('click', function(e) {
            e.preventDefault();

            var retreatId = $(this).data('retreat-id');
            var retreatName = $(this).data('retreat-name');
            var attendantCount = $(this).data('attendant-count');
            var messageCount = $(this).data('message-count');
            var $button = $(this);

            // Show custom delete confirmation modal
            showDeleteAllAttendantsModal(retreatId, retreatName, attendantCount, messageCount, $button);
        });

        // Function to show delete all attendants confirmation modal
        function showDeleteAllAttendantsModal(retreatId, retreatName, attendantCount, messageCount, $button) {
            // Create modal HTML
            var modalHtml =
                '<div id="dfxprl-delete-all-attendants-modal" class="dfxprl-modal-overlay">' +
                    '<div class="dfxprl-modal-dialog">' +
                        '<div class="dfxprl-modal-header">' +
                            '<h3>' + dfxprlAdmin.messages.deleteAllAttendantsTitle + '</h3>' +
                        '</div>' +
                        '<div class="dfxprl-modal-body">' +
                            '<div class="dfxprl-warning-message">' +
                                '<p><strong>' + dfxprlAdmin.messages.deleteWarning + '</strong></p>' +
                                '<p>' + dfxprlAdmin.messages.deleteAllAttendantsWarning + '</p>' +
                                '<p><strong>' + dfxprlAdmin.messages.deleteAllAttendantsWarningCount + '</strong></p>' +
                                '<ul>' +
                                    '<li>' + dfxprlAdmin.messages.deleteAllAttendantsWarningAttendants.replace('%d', attendantCount) + '</li>' +
                                    '<li>' + dfxprlAdmin.messages.deleteAllAttendantsWarningMessages.replace('%d', messageCount) + '</li>' +
                                    '<li>' + dfxprlAdmin.messages.deleteWarningPermanent + '</li>' +
                                '</ul>' +
                            '</div>' +
                            '<div class="dfxprl-confirmation-section">' +
                                '<p><code style="background: #f0f0f1; padding: 5px 10px; display: inline-block;">' + dfxprlAdmin.messages.confirmationText + '</code></p>' +
                                '<p>' + dfxprlAdmin.messages.typeConfirmation + '</p>' +
                                '<input type="text" id="dfxprl-confirmation-text" placeholder="' + dfxprlAdmin.messages.confirmationPlaceholder + '" />' +
                            '</div>' +
                        '</div>' +
                        '<div class="dfxprl-modal-footer">' +
                            '<button type="button" id="dfxprl-confirm-delete-all" class="button button-primary" disabled>' + dfxprlAdmin.messages.deleteAllButton + '</button>' +
                            '<button type="button" id="dfxprl-cancel-delete-all" class="button">' + dfxprlAdmin.messages.cancelButton + '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            // Add modal to body
            $('body').append(modalHtml);

            var $modal = $('#dfxprl-delete-all-attendants-modal');
            var $confirmInput = $('#dfxprl-confirmation-text');
            var $confirmButton = $('#dfxprl-confirm-delete-all');

            // Show modal
            $modal.fadeIn();
            $confirmInput.focus();

            // Check input as user types
            $confirmInput.on('input', function() {
                var enteredText = $(this).val().trim();
                if (enteredText === dfxprlAdmin.messages.confirmationText) {
                    $confirmButton.prop('disabled', false);
                } else {
                    $confirmButton.prop('disabled', true);
                }
            });

            // Handle cancel
            $('#dfxprl-cancel-delete-all, .dfxprl-modal-overlay').on('click', function(e) {
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
                $button.prop('disabled', true).text(dfxprlAdmin.messages.deleting);

                $.ajax({
                    url: dfxprlAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dfxprl_delete_all_attendants',
                        retreat_id: retreatId,
                        confirmation_text: dfxprlAdmin.messages.confirmationText,
                        nonce: dfxprlAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message and reload the page to reflect changes
                            $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                                .insertAfter('.wp-header-end');
                            
                            // Reload page after a short delay to show the message
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            alert(response.data.message || dfxprlAdmin.messages.deleteError);
                            // Re-enable button
                            $button.prop('disabled', false).text(dfxprlAdmin.messages.deleteAllButton);
                        }
                    },
                    error: function() {
                        alert(dfxprlAdmin.messages.deleteError);
                        // Re-enable button
                        $button.prop('disabled', false).text(dfxprlAdmin.messages.deleteAllButton);
                    }
                });
            });
        }

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

        // Auto-dismiss notices - disabled to prevent automatic hiding
        // $('.notice.is-dismissible').delay(5000).fadeOut();

        // Handle generate message URL button clicks
        $('.dfxprl-generate-url').on('click', function(e) {
            e.preventDefault();

            var attendantId = $(this).data('attendant-id');
            var $button = $(this);

            // Get localized text with fallbacks
            var generatingText = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.messages && dfxprlAdmin.messages.generating)
                ? dfxprlAdmin.messages.generating
                : 'Generating URL...';
            var urlGeneratedText = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.messages && dfxprlAdmin.messages.urlGenerated)
                ? dfxprlAdmin.messages.urlGenerated
                : 'Message URL generated successfully!';
            var generateErrorText = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.messages && dfxprlAdmin.messages.generateError)
                ? dfxprlAdmin.messages.generateError
                : 'Error generating message URL. Please try again.';

            // Disable button and show loading state
            $button.prop('disabled', true).text(generatingText);

            var ajaxUrl = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.ajaxurl)
                ? dfxprlAdmin.ajaxurl
                : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.nonce)
                ? dfxprlAdmin.nonce
                : '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfxprl_generate_message_url',
                    attendant_id: attendantId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Get attendant name and surnames from response
                        var attendantName = response.data.attendant_name || '';
                        var attendantSurnames = response.data.attendant_surnames || '';
                        
                        // Replace button with copy URL button (including attendant data attributes)
                        var newButton = '<button type="button" class="button button-small button-primary dfxprl-copy-url" ' +
                                       'data-url="' + escapeHtmlAttribute(response.data.url) + '" ' +
                                       'data-attendant-name="' + escapeHtmlAttribute(attendantName) + '" ' +
                                       'data-attendant-surnames="' + escapeHtmlAttribute(attendantSurnames) + '">' +
                                       'Copy Message URL</button>';
                        $button.replaceWith(newButton);

                        // Show success message
                        $('<div class="notice notice-success is-dismissible"><p>' + urlGeneratedText + '</p></div>')
                            .insertAfter('.wp-header-end');

                        // Auto-copy URL to clipboard with anchor
                        var anchor = createUrlAnchor(attendantName, attendantSurnames);
                        copyToClipboard(response.data.url + anchor);
                    } else {
                        alert(response.data.message || generateErrorText);
                        // Re-enable button
                        $button.prop('disabled', false).text('Generate Message URL');
                    }
                },
                error: function() {
                    alert(generateErrorText);
                    // Re-enable button
                    $button.prop('disabled', false).text('Generate Message URL');
                }
            });
        });

        // Handle copy URL button clicks (using event delegation for dynamically added buttons)
        $(document).on('click', '.dfxprl-copy-url', function(e) {
            e.preventDefault();

            var url = $(this).data('url');
            var $button = $(this);
            
            // Get attendant name and surnames from data attributes
            var attendantName = $(this).data('attendant-name') || '';
            var attendantSurnames = $(this).data('attendant-surnames') || '';
            
            // Add anchor with attendant name and surnames
            var anchor = createUrlAnchor(attendantName, attendantSurnames);
            var urlWithAnchor = url + anchor;

            // Get localized text with fallbacks
            var urlCopiedText = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.messages && dfxprlAdmin.messages.urlCopied)
                ? dfxprlAdmin.messages.urlCopied
                : 'URL copied to clipboard!';

            if (copyToClipboard(urlWithAnchor)) {
                // Temporarily change button text
                var originalText = $button.text();
                $button.text(urlCopiedText);
                setTimeout(function() {
                    $button.text(originalText);
                }, 2000);
            }
        });

        // Handle print message button clicks
        $('.dfxprl-print-message').on('click', function(e) {
            e.preventDefault();

            var messageId = $(this).data('message-id');
            var $button = $(this);

            // Get localized text with fallbacks
            var printingText = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.messages && dfxprlAdmin.messages.printing)
                ? dfxprlAdmin.messages.printing
                : 'Preparing for print...';
            var printErrorText = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.messages && dfxprlAdmin.messages.printError)
                ? dfxprlAdmin.messages.printError
                : 'Error preparing message for print. Please try again.';

            // Disable button and show loading state
            $button.prop('disabled', true).text(printingText);

            var ajaxUrl = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.ajaxurl)
                ? dfxprlAdmin.ajaxurl
                : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.nonce)
                ? dfxprlAdmin.nonce
                : '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfxprl_print_message',
                    message_id: messageId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Refresh the Print Status cell in the row so the new count is reflected.
                        if (response.data && response.data.print_status_html) {
                            $button.closest('tr').find('.dfxprl-print-status-cell').html(response.data.print_status_html);
                        }
                        // Open print URL in new tab, forwarding any extra query params
                        var printUrl = response.data.print_url;
                        var params = new URLSearchParams(window.location.search);
                        // Remove admin page params — only forward dfxprl-specific extras
                        params.delete('page');
                        params.delete('attendant_id');
                        params.delete('paged');
                        params.delete('s');
                        params.delete('message_type');
                        params.delete('orderby');
                        params.delete('order');
                        var extra = params.toString();
                        if (extra) {
                            printUrl += (printUrl.indexOf('?') !== -1 ? '&' : '?') + extra;
                        }
                        window.open(printUrl, '_blank');
                    } else {
                        alert(response.data.message || printErrorText);
                    }
                    // Re-enable button
                    $button.prop('disabled', false).text('Print');
                },
                error: function() {
                    alert(printErrorText);
                    // Re-enable button
                    $button.prop('disabled', false).text('Print');
                }
            });
        });

        // Handle delete message button clicks
        $('.dfxprl-delete-message').on('click', function(e) {
            e.preventDefault();

            var messageId = $(this).data('message-id');
            var $row = $(this).closest('tr');

            // Get localized text with fallbacks
            var confirmMessage = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.messages && dfxprlAdmin.messages.confirmDeleteMessage)
                ? dfxprlAdmin.messages.confirmDeleteMessage
                : 'Are you sure you want to delete this message? This action cannot be undone.';
            var messageDeletedText = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.messages && dfxprlAdmin.messages.messageDeleted)
                ? dfxprlAdmin.messages.messageDeleted
                : 'Message deleted successfully.';

            if (!confirm(confirmMessage)) {
                return;
            }

            // Disable button and show loading state
            $(this).prop('disabled', true).text('Deleting...');

            var ajaxUrl = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.ajaxurl)
                ? dfxprlAdmin.ajaxurl
                : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.nonce)
                ? dfxprlAdmin.nonce
                : '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfxprl_delete_message',
                    message_id: messageId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the row with animation
                        $row.fadeOut(300, function() {
                            $(this).remove();

                            // Show success message
                            var successMessage = response.data.message || messageDeletedText;
                            $('<div class="notice notice-success is-dismissible"><p>' + successMessage + '</p></div>')
                                .insertAfter('.wp-header-end');
                        });
                    } else {
                        alert(response.data.message || 'Error deleting message.');
                        // Re-enable button
                        $('.dfxprl-delete-message[data-message-id="' + messageId + '"]')
                            .prop('disabled', false)
                            .text('Delete');
                    }
                },
                error: function() {
                    alert('Error deleting message. Please try again.');
                    // Re-enable button
                    $('.dfxprl-delete-message[data-message-id="' + messageId + '"]')
                        .prop('disabled', false)
                        .text('Delete');
                }
            });
        });

        // Handle view print log clicks (delegated so re-rendered cells still respond)
        $(document).on('click', '.dfxprl-view-print-log', function(e) {
            e.preventDefault();

            var messageId = $(this).data('message-id');

            var ajaxUrl = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.ajaxurl)
                ? dfxprlAdmin.ajaxurl
                : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = (typeof dfxprlAdmin !== 'undefined' && dfxprlAdmin.nonce)
                ? dfxprlAdmin.nonce
                : '';

            // Show loading state
            showPrintLogModal(messageId, null, true);

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfxprl_get_print_log',
                    message_id: messageId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        showPrintLogModal(messageId, response.data, false);
                    } else {
                        alert(response.data.message || 'Error loading print history.');
                        closePrintLogModal();
                    }
                },
                error: function() {
                    alert('Error loading print history. Please try again.');
                    closePrintLogModal();
                }
            });
        });

        // Function to show print log modal
        function showPrintLogModal(messageId, data, loading) {
            // Remove existing modal if any
            $('#dfxprl-print-log-modal').remove();

            var modalHtml = '';

            if (loading) {
                modalHtml =
                    '<div id="dfxprl-print-log-modal" class="dfxprl-modal-overlay">' +
                        '<div class="dfxprl-modal-dialog">' +
                            '<div class="dfxprl-modal-header">' +
                                '<h3>Print History</h3>' +
                                '<button type="button" class="dfxprl-modal-close" aria-label="Close">&times;</button>' +
                            '</div>' +
                            '<div class="dfxprl-modal-body">' +
                                '<p>Loading print history...</p>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
            } else {
                var logsTable = '';
                if (data.logs && data.logs.length > 0) {
                    logsTable = '<table class="widefat fixed striped">' +
                        '<thead>' +
                            '<tr>' +
                                '<th>User</th>' +
                                '<th>Date & Time</th>' +
                                '<th>IP Address</th>' +
                            '</tr>' +
                        '</thead>' +
                        '<tbody>';

                    for (var i = 0; i < data.logs.length; i++) {
                        var log = data.logs[i];
                        logsTable += '<tr>' +
                            '<td>' + log.user_name + '</td>' +
                            '<td>' + log.printed_at + '</td>' +
                            '<td>' + log.ip_address + '</td>' +
                        '</tr>';
                    }

                    logsTable += '</tbody></table>';
                } else {
                    logsTable = '<p>No print history available for this message.</p>';
                }

                modalHtml =
                    '<div id="dfxprl-print-log-modal" class="dfxprl-modal-overlay">' +
                        '<div class="dfxprl-modal-dialog" style="max-width: 700px;">' +
                            '<div class="dfxprl-modal-header">' +
                                '<h3>Print History (Total: ' + data.total_count + ')</h3>' +
                                '<button type="button" class="dfxprl-modal-close" aria-label="Close">&times;</button>' +
                            '</div>' +
                            '<div class="dfxprl-modal-body">' +
                                logsTable +
                            '</div>' +
                        '</div>' +
                    '</div>';
            }

            $('body').append(modalHtml);
            $('#dfxprl-print-log-modal').show();

            // Handle close button clicks
            $('.dfxprl-modal-close').on('click', function(e) {
                e.preventDefault();
                closePrintLogModal();
            });

            // Handle backdrop clicks
            $('#dfxprl-print-log-modal').on('click', function(e) {
                if (e.target === this) {
                    closePrintLogModal();
                }
            });
        }

        // Function to close print log modal
        function closePrintLogModal() {
            $('#dfxprl-print-log-modal').fadeOut(200, function() {
                $(this).remove();
            });
        }

        // Helper function to escape HTML attributes
        function escapeHtmlAttribute(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Function to create URL-safe anchor from attendant name and surnames using initials
        function createUrlAnchor(name, surnames) {
            // Combine name and surnames
            var fullName = (name + ' ' + surnames).trim();
            
            // Return empty string if no name provided
            if (!fullName) {
                return '';
            }
            
            // Normalize Unicode characters to decomposed form and remove diacritics
            // This converts accented characters to their base forms (e.g., é -> e, ñ -> n)
            var normalized = fullName;
            if (typeof fullName.normalize === 'function') {
                normalized = fullName.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            }
            
            // Split into words and extract first letter of each word
            var words = normalized.split(/\s+/);
            var initials = words
                .filter(function(word) {
                    // Filter out empty words
                    return word.length > 0;
                })
                .map(function(word) {
                    return word.charAt(0).toLowerCase();
                })
                .filter(function(initial) {
                    // Only keep single alphanumeric initials
                    return /^[a-z0-9]$/.test(initial);
                })
                .join('');
            
            return initials ? '/#' + initials : '';
        }

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

        // Handle invitation message button clicks
        $('.dfxprl-show-invitation-message').on('click', function(e) {
            e.preventDefault();
            
            var attendantId = $(this).data('attendant-id');
            var retreatId = $(this).data('retreat-id');
            var $button = $(this);
            
            // Show loading state
            $button.prop('disabled', true);
            var originalText = $button.text();
            $button.text(dfxprlAdmin.messages.loadingMessage || 'Loading...');
            
            // Make AJAX request to get the expanded message
            $.ajax({
                url: dfxprlAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dfxprl_get_invitation_message',
                    nonce: dfxprlAdmin.nonce,
                    attendant_id: attendantId,
                    retreat_id: retreatId
                },
                success: function(response) {
                    $button.prop('disabled', false);
                    $button.text(originalText);
                    
                    if (response.success) {
                        showInvitationMessageModal(response.data.message);
                    } else {
                        alert(response.data.message || dfxprlAdmin.messages.loadMessageError);
                    }
                },
                error: function() {
                    $button.prop('disabled', false);
                    $button.text(originalText);
                    alert(dfxprlAdmin.messages.loadMessageError);
                }
            });
        });

        // Show invitation message modal
        function showInvitationMessageModal(message) {
            // Create modal if it doesn't exist
            if (!$('#dfxprl-invitation-modal').length) {
                $('body').append(
                    '<div id="dfxprl-invitation-modal" class="dfxprl-modal-overlay">' +
                        '<div class="dfxprl-modal-dialog">' +
                            '<div class="dfxprl-modal-header">' +
                                '<h3>' + (dfxprlAdmin.messages.invitationMessageTitle || 'Invitation Message') + '</h3>' +
                                '<button class="dfxprl-modal-close">&times;</button>' +
                            '</div>' +
                            '<div class="dfxprl-modal-body">' +
                                '<div class="dfxprl-message-content" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 15px; white-space: pre-wrap; font-family: monospace; max-height: 400px; overflow-y: auto;"></div>' +
                            '</div>' +
                            '<div class="dfxprl-modal-footer">' +
                                '<button class="button button-primary dfxprl-copy-message">' + 'Copy Message' + '</button>' +
                                '<button class="button dfxprl-modal-close">' + (dfxprlAdmin.messages.cancelButton || 'Close') + '</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
                
                // Handle close button clicks
                $('#dfxprl-invitation-modal').on('click', '.dfxprl-modal-close', function() {
                    $('#dfxprl-invitation-modal').fadeOut(200);
                });
                
                // Handle copy button click
                $('#dfxprl-invitation-modal').on('click', '.dfxprl-copy-message', function() {
                    var messageText = $('#dfxprl-invitation-modal .dfxprl-message-content').text();
                    
                    if (copyToClipboard(messageText)) {
                        alert(dfxprlAdmin.messages.messageCopied || 'Message copied to clipboard!');
                        $('#dfxprl-invitation-modal').fadeOut(200);
                    } else {
                        alert(dfxprlAdmin.messages.messageCopyError || 'Failed to copy message.');
                    }
                });
                
                // Close on overlay click
                $('#dfxprl-invitation-modal').on('click', function(e) {
                    if ($(e.target).is('#dfxprl-invitation-modal')) {
                        $(this).fadeOut(200);
                    }
                });
            }
            
            // Set the message content and show the modal
            $('#dfxprl-invitation-modal .dfxprl-message-content').text(message);
            $('#dfxprl-invitation-modal').fadeIn(200);
        }
    });


    // Custom attendant fields editor (retreat add/edit page)
    $(function() {
        var $container = $('#dfxprl-custom-fields');
        if (!$container.length) {
            return;
        }

        function slugify(text) {
            return text.normalize('NFD').replace(/[̀-ͯ]/g, '')
                .toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').substring(0, 64);
        }

        function syncCard($card) {
            $card.find('.dfxprl-cf-options').toggle($card.find('.dfxprl-cf-type').val() === 'select');

            var showInList = $card.find('.dfxprl-cf-show-in-list').is(':checked');
            var $sortable = $card.find('.dfxprl-cf-sortable').prop('disabled', !showInList);
            if (!showInList) {
                $sortable.prop('checked', false);
            }

            // Editing can never be broader than viewing
            var managersOnly = $card.find('.dfxprl-cf-view').val() === 'manager';
            var $edit = $card.find('.dfxprl-cf-edit');
            $edit.find('option[value="message_manager"]').prop('disabled', managersOnly);
            if (managersOnly) {
                $edit.val('manager');
            }
        }

        $container.find('.dfxprl-cf-card').each(function() {
            syncCard($(this));
        });

        $container.on('change', '.dfxprl-cf-type, .dfxprl-cf-show-in-list, .dfxprl-cf-view', function() {
            syncCard($(this).closest('.dfxprl-cf-card'));
        });

        // Suggest a slug for new fields until the user types one
        $container.on('input', '.dfxprl-cf-new .dfxprl-cf-name', function() {
            var $slug = $(this).closest('.dfxprl-cf-card').find('.dfxprl-cf-slug');
            if (!$slug.data('touched')) {
                $slug.val(slugify($(this).val()));
            }
        });
        $container.on('input', '.dfxprl-cf-slug', function() {
            $(this).data('touched', true);
        });

        $('.dfxprl-add-custom-field').on('click', function() {
            var html = $('#dfxprl-custom-field-template').html().replace(/__INDEX__/g, 'new' + Date.now());
            var $card = $(html);
            $container.append($card);
            syncCard($card);
            $card.find('.dfxprl-cf-name').trigger('focus');
        });

        $container.on('click', '.dfxprl-remove-custom-field', function() {
            var $card = $(this).closest('.dfxprl-cf-card');
            if ($card.hasClass('dfxprl-cf-new')) {
                $card.remove();
                return;
            }
            if (confirm(dfxprlAdmin.messages.confirmRemoveCustomField)) {
                $card.find('.dfxprl-cf-delete').val('1');
                $card.hide();
            }
        });
    });

    // Attendant contact log (attendant edit page and list modal)
    $(function() {
        function request($log, data, $button) {
            $button.prop('disabled', true);
            data.nonce = dfxprlAdmin.nonce;
            return $.post(dfxprlAdmin.ajaxurl, data).done(function(response) {
                if (response.success) {
                    $log.find('.dfxprl-log-entries').html(response.data.html);
                    // Keep the Contact Log column of the attendants list in sync
                    if (response.data.cell) {
                        $('.dfxprl-attendants-table td[data-column="contact_log"]').filter(function() {
                            return String($(this).find('.dfxprl-open-log').data('attendant-id')) === String($log.data('attendant-id'));
                        }).replaceWith(response.data.cell);
                    }
                } else {
                    alert((response.data && response.data.message) || dfxprlAdmin.messages.logError);
                }
            }).fail(function() {
                alert(dfxprlAdmin.messages.logError);
            }).always(function() {
                $button.prop('disabled', false);
            });
        }

        $(document).on('click', '.dfxprl-attendant-log .dfxprl-log-add', function() {
            var $log = $(this).closest('.dfxprl-attendant-log');
            var $content = $log.find('.dfxprl-log-new .dfxprl-log-content');
            if (!$content.val().trim()) {
                alert(dfxprlAdmin.messages.logEmpty);
                return;
            }
            request($log, {
                action: 'dfxprl_log_add',
                attendant_id: $log.data('attendant-id'),
                entry_date: $log.find('.dfxprl-log-new .dfxprl-log-date').val(),
                content: $content.val()
            }, $(this)).done(function(response) {
                if (response.success) {
                    $content.val('');
                }
            });
        });

        $(document).on('click', '.dfxprl-attendant-log .dfxprl-log-edit', function() {
            var $entry = $(this).closest('.dfxprl-log-entry');
            var $form = $('<div class="dfxprl-log-edit-form">' +
                '<p><input type="date" class="dfxprl-log-date"></p>' +
                '<p><textarea class="large-text dfxprl-log-content" rows="3"></textarea></p>' +
                '<p><button type="button" class="button button-primary button-small dfxprl-log-save"></button> ' +
                '<button type="button" class="button button-small dfxprl-log-cancel"></button></p>' +
                '</div>');
            $form.find('.dfxprl-log-date').val($entry.attr('data-entry-date'));
            $form.find('.dfxprl-log-content').val($entry.attr('data-content'));
            $form.find('.dfxprl-log-save').text(dfxprlAdmin.messages.save);
            $form.find('.dfxprl-log-cancel').text(dfxprlAdmin.messages.cancelButton);
            $entry.children('.dfxprl-log-entry-content, .dfxprl-log-entry-actions').hide();
            $entry.children('.dfxprl-log-entry-meta').after($form);
        });

        $(document).on('click', '.dfxprl-attendant-log .dfxprl-log-cancel', function() {
            var $entry = $(this).closest('.dfxprl-log-entry');
            $entry.find('.dfxprl-log-edit-form').remove();
            $entry.children('.dfxprl-log-entry-content, .dfxprl-log-entry-actions').show();
        });

        $(document).on('click', '.dfxprl-attendant-log .dfxprl-log-save', function() {
            var $entry = $(this).closest('.dfxprl-log-entry');
            var content = $entry.find('.dfxprl-log-edit-form .dfxprl-log-content').val();
            if (!content.trim()) {
                alert(dfxprlAdmin.messages.logEmpty);
                return;
            }
            request($(this).closest('.dfxprl-attendant-log'), {
                action: 'dfxprl_log_update',
                entry_id: $entry.data('entry-id'),
                entry_date: $entry.find('.dfxprl-log-edit-form .dfxprl-log-date').val(),
                content: content
            }, $(this));
        });

        $(document).on('click', '.dfxprl-attendant-log .dfxprl-log-delete', function() {
            if (!confirm(dfxprlAdmin.messages.confirmDeleteLogEntry)) {
                return;
            }
            request($(this).closest('.dfxprl-attendant-log'), {
                action: 'dfxprl_log_delete',
                entry_id: $(this).closest('.dfxprl-log-entry').data('entry-id')
            }, $(this));
        });

        // Contact log modal from the attendants list
        function closeLogModal() {
            $('#dfxprl-log-modal').fadeOut(150, function() {
                $(this).remove();
            });
        }

        $(document).on('click', '.dfxprl-open-log', function() {
            var attendantId = $(this).data('attendant-id');
            var $modal = $('<div id="dfxprl-log-modal" class="dfxprl-modal-overlay">' +
                '<div class="dfxprl-modal-dialog">' +
                    '<div class="dfxprl-modal-header"><h3></h3>' +
                    '<button type="button" class="dfxprl-modal-close" aria-label="Close">&times;</button></div>' +
                    '<div class="dfxprl-modal-body"><p class="description"></p></div>' +
                '</div></div>');
            $modal.find('h3').text(dfxprlAdmin.messages.contactLogTitle + ' — ' + $(this).data('attendant-name'));
            $modal.find('.dfxprl-modal-close').attr('aria-label', dfxprlAdmin.messages.cancelButton);
            $modal.find('.dfxprl-modal-body .description').text(dfxprlAdmin.messages.loadingMessage);
            $('#dfxprl-log-modal').remove();
            $('body').append($modal);
            $modal.fadeIn(150);

            $.post(dfxprlAdmin.ajaxurl, {
                action: 'dfxprl_log_get',
                nonce: dfxprlAdmin.nonce,
                attendant_id: attendantId
            }).done(function(response) {
                if (response.success) {
                    $modal.find('.dfxprl-modal-body').html(response.data.html);
                    $modal.find('.dfxprl-log-new .dfxprl-log-content').trigger('focus');
                } else {
                    $modal.find('.dfxprl-modal-body').text((response.data && response.data.message) || dfxprlAdmin.messages.logError);
                }
            }).fail(function() {
                $modal.find('.dfxprl-modal-body').text(dfxprlAdmin.messages.logError);
            });
        });

        $(document).on('click', '#dfxprl-log-modal .dfxprl-modal-close', closeLogModal);
        $(document).on('click', '#dfxprl-log-modal', function(e) {
            if (e.target === this) {
                closeLogModal();
            }
        });
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#dfxprl-log-modal').length && !$(e.target).closest('.dfxprl-log-edit-form').length) {
                closeLogModal();
            }
        });
    });

    // Inline editing of attendant fields in the attendants list
    $(function() {
        var $table = $('.dfxprl-attendants-table');
        if (!$table.length) {
            return;
        }

        function buildEditor($cell) {
            var type = $cell.attr('data-edit-type');
            var value = $cell.attr('data-value') || '';
            var $input;

            switch (type) {
                case 'textarea':
                    $input = $('<textarea rows="3" class="dfxprl-inline-input"></textarea>').val(value);
                    break;
                case 'date':
                    $input = $('<input type="date" class="dfxprl-inline-input">').val(value);
                    break;
                case 'number':
                    $input = $('<input type="number" step="any" class="dfxprl-inline-input">').val(value);
                    if ($cell.attr('data-column') === 'physical_letters') {
                        $input.attr({ min: 0, step: 1 });
                    }
                    break;
                case 'checkbox':
                    $input = $('<label class="dfxprl-inline-input"><input type="checkbox" value="1"> ' + $('<span>').text(dfxprlAdmin.messages.yes).html() + '</label>');
                    $input.find('input').prop('checked', value === '1');
                    break;
                case 'select':
                    $input = $('<select class="dfxprl-inline-input"></select>');
                    $input.append($('<option value="">').text('—'));
                    var options = JSON.parse($cell.attr('data-options') || '[]');
                    if (value && options.indexOf(value) === -1) {
                        options.push(value);
                    }
                    $.each(options, function(i, option) {
                        $input.append($('<option>').val(option).text(option));
                    });
                    $input.val(value);
                    break;
                default:
                    $input = $('<input type="text" class="dfxprl-inline-input">').val(value);
            }

            return $('<div class="dfxprl-inline-editor"></div>').append($input).append(
                '<div class="dfxprl-inline-buttons">' +
                '<button type="button" class="button button-primary button-small dfxprl-inline-save"></button> ' +
                '<button type="button" class="button button-small dfxprl-inline-cancel"></button>' +
                '</div>'
            );
        }

        function editorValue($cell) {
            var $editor = $cell.find('.dfxprl-inline-editor');
            if ($cell.attr('data-edit-type') === 'checkbox') {
                return $editor.find('input[type=checkbox]').is(':checked') ? '1' : '';
            }
            return $editor.find('.dfxprl-inline-input').val();
        }

        function cancel($cell) {
            $cell.html($cell.data('original-html')).removeClass('dfxprl-inline-editing');
        }

        function save($cell) {
            var value = editorValue($cell);
            if ($cell.attr('data-required') && !String(value).trim()) {
                alert(dfxprlAdmin.messages.fieldRequired);
                return;
            }
            var $buttons = $cell.find('.dfxprl-inline-buttons button').prop('disabled', true);
            $.post(dfxprlAdmin.ajaxurl, {
                action: 'dfxprl_inline_update',
                nonce: dfxprlAdmin.nonce,
                attendant_id: $cell.attr('data-attendant-id'),
                column: $cell.attr('data-column'),
                value: value
            }).done(function(response) {
                if (response.success) {
                    var $row = $cell.closest('tr');
                    $cell.replaceWith(response.data.html);
                    $.each(response.data.related_cells || [], function(i, html) {
                        var $related = $(html);
                        $row.children('td[data-column="' + $related.attr('data-column') + '"]').replaceWith($related);
                    });
                    if (response.data.letters_summary) {
                        $('.dfxprl-letters-summary').html(response.data.letters_summary);
                    }
                } else {
                    alert((response.data && response.data.message) || dfxprlAdmin.messages.inlineError);
                    $buttons.prop('disabled', false);
                }
            }).fail(function() {
                alert(dfxprlAdmin.messages.inlineError);
                $buttons.prop('disabled', false);
            });
        }

        $table.on('click', 'td.dfxprl-inline-editable', function(e) {
            var $cell = $(this);
            // Links and buttons inside the cell keep working, and a cell being edited stays as is
            if ($cell.hasClass('dfxprl-inline-editing') || $(e.target).closest('a, button, .dfxprl-inline-editor').length) {
                return;
            }
            // Only one cell is edited at a time
            $table.find('td.dfxprl-inline-editing').each(function() {
                cancel($(this));
            });
            $cell.data('original-html', $cell.html()).addClass('dfxprl-inline-editing');
            var $editor = buildEditor($cell);
            $editor.find('.dfxprl-inline-save').text(dfxprlAdmin.messages.save);
            $editor.find('.dfxprl-inline-cancel').text(dfxprlAdmin.messages.cancelButton);
            $cell.empty().append($editor);
            $editor.find('input:not([type=checkbox]), select, textarea').first().trigger('focus').trigger('select');
        });

        $table.on('click', '.dfxprl-inline-save', function() {
            save($(this).closest('td'));
        });
        $table.on('click', '.dfxprl-inline-cancel', function() {
            cancel($(this).closest('td'));
        });
        $table.on('keydown', '.dfxprl-inline-editor', function(e) {
            var $cell = $(this).closest('td');
            if (e.key === 'Escape') {
                e.preventDefault();
                cancel($cell);
            } else if (e.key === 'Enter' && (!$(e.target).is('textarea') || e.ctrlKey || e.metaKey)) {
                // Enter saves; in long texts Enter adds a new line and Ctrl+Enter saves
                e.preventDefault();
                save($cell);
            }
        });
    });

    // Attendants list column reordering (retreat managers only)
    $(function() {
        var $table = $('.dfxprl-attendants-table[data-retreat-id]');
        if (!$table.length || !$.fn.sortable) {
            return;
        }
        var retreatId = $table.data('retreat-id');
        var $headerRow = $table.find('thead tr');
        var $reset = $('.dfxprl-reset-column-order');

        function currentOrder() {
            return $headerRow.children('th[data-column]').map(function() {
                return $(this).attr('data-column');
            }).get();
        }

        // Move every row's cells to match the header order (Actions stays last)
        function applyOrderToRows(order) {
            $table.find('tbody tr').each(function() {
                var $row = $(this);
                var $actions = $row.children('td:not([data-column])').last();
                $.each(order, function(i, key) {
                    var $cell = $row.children('td[data-column="' + key + '"]');
                    if ($cell.length) {
                        if ($actions.length) {
                            $cell.insertBefore($actions);
                        } else {
                            $row.append($cell);
                        }
                    }
                });
            });
        }

        function saveOrder(order) {
            $.post(dfxprlAdmin.ajaxurl, {
                action: 'dfxprl_save_column_order',
                nonce: dfxprlAdmin.nonce,
                retreat_id: retreatId,
                order: order
            }).done(function(response) {
                if (!response.success) {
                    alert((response.data && response.data.message) || dfxprlAdmin.messages.columnOrderError);
                }
            }).fail(function() {
                alert(dfxprlAdmin.messages.columnOrderError);
            });
        }

        $headerRow.sortable({
            items: '> th[data-column]',
            handle: '.dfxprl-column-handle',
            axis: 'x',
            tolerance: 'pointer',
            placeholder: 'dfxprl-column-placeholder',
            helper: 'clone',
            start: function(e, ui) {
                ui.placeholder.width(ui.item.width()).html('&nbsp;');
            },
            update: function() {
                var order = currentOrder();
                applyOrderToRows(order);
                saveOrder(order);
                $reset.show();
            }
        });

        $reset.on('click', function() {
            $.post(dfxprlAdmin.ajaxurl, {
                action: 'dfxprl_save_column_order',
                nonce: dfxprlAdmin.nonce,
                retreat_id: retreatId
            }).done(function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert((response.data && response.data.message) || dfxprlAdmin.messages.columnOrderError);
                }
            }).fail(function() {
                alert(dfxprlAdmin.messages.columnOrderError);
            });
        });
    });

})(jQuery);