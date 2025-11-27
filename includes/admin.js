/**
 * Admin JavaScript for DFX Parish Retreat Letters
 *
 * @package DFX_Parish_Retreat_Letters
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Check if dfxPRLAdmin is available
        if (typeof dfxPRLAdmin === 'undefined') {
            console.warn('dfxPRLAdmin is not defined. Admin functionality may be limited.');
            // Create a fallback object to prevent errors
            window.dfxPRLAdmin = {
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
        if (!$('#dfx-prl-modal-styles').length) {
            $('<style id="dfx-prl-modal-styles">' +
                '.dfx-prl-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000; display: none; }' +
                '.dfx-prl-modal-dialog { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 4px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 500px; width: 90%; }' +
                '.dfx-prl-modal-header { padding: 20px 20px 10px; border-bottom: 1px solid #ddd; position: relative; }' +
                '.dfx-prl-modal-header h3 { margin: 0; font-size: 18px; color: #d63638; }' +
                '.dfx-prl-modal-close { position: absolute; top: 15px; right: 20px; background: none; border: none; font-size: 24px; line-height: 1; cursor: pointer; color: #666; padding: 0; }' +
                '.dfx-prl-modal-close:hover { color: #000; }' +
                '.dfx-prl-modal-body { padding: 20px; }' +
                '.dfx-prl-warning-message { background: #fff8e5; border: 1px solid #f0b849; border-radius: 4px; padding: 15px; margin-bottom: 20px; }' +
                '.dfx-prl-warning-message p { margin: 0 0 10px; }' +
                '.dfx-prl-warning-message ul { margin: 0; padding-left: 20px; }' +
                '.dfx-prl-confirmation-section p { margin: 10px 0; }' +
                '.dfx-prl-confirmation-section input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }' +
                '.dfx-prl-modal-footer { padding: 15px 20px; border-top: 1px solid #ddd; text-align: right; }' +
                '.dfx-prl-modal-footer .button { margin-left: 10px; }' +
                '#dfx-prl-print-log-modal .dfx-prl-modal-dialog { max-width: 700px; }' +
                '#dfx-prl-print-log-modal .dfx-prl-modal-body table { margin: 0; }' +
                '#dfx-prl-print-log-modal .dfx-prl-modal-body th, #dfx-prl-print-log-modal .dfx-prl-modal-body td { padding: 8px 12px; }' +
            '</style>').appendTo('head');
        }
        // Handle delete retreat button clicks
        $('.dfx-prl-delete-retreat').on('click', function(e) {
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
                '<div id="dfx-prl-delete-retreat-modal" class="dfx-prl-modal-overlay">' +
                    '<div class="dfx-prl-modal-dialog">' +
                        '<div class="dfx-prl-modal-header">' +
                            '<h3>' + dfxPRLAdmin.messages.deleteRetreatTitle + '</h3>' +
                        '</div>' +
                        '<div class="dfx-prl-modal-body">' +
                            '<div class="dfx-prl-warning-message">' +
                                '<p><strong>' + dfxPRLAdmin.messages.deleteWarning + '</strong></p>' +
                                '<ul>' +
                                    '<li>' + dfxPRLAdmin.messages.deleteWarningAttendants + '</li>' +
                                    '<li>' + dfxPRLAdmin.messages.deleteWarningLetters + '</li>' +
                                    '<li>' + dfxPRLAdmin.messages.deleteWarningPermanent + '</li>' +
                                '</ul>' +
                            '</div>' +
                            '<div class="dfx-prl-confirmation-section">' +
                                '<p>' + dfxPRLAdmin.messages.typeRetreatName + '</p>' +
                                '<p><strong>' + retreatName + '</strong></p>' +
                                '<input type="text" id="dfx-prl-retreat-name-confirm" placeholder="' + dfxPRLAdmin.messages.retreatNamePlaceholder + '" />' +
                            '</div>' +
                        '</div>' +
                        '<div class="dfx-prl-modal-footer">' +
                            '<button type="button" id="dfx-prl-confirm-delete" class="button button-primary" disabled>' + dfxPRLAdmin.messages.deleteButton + '</button>' +
                            '<button type="button" id="dfx-prl-cancel-delete" class="button">' + dfxPRLAdmin.messages.cancelButton + '</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            // Add modal to body
            $('body').append(modalHtml);

            var $modal = $('#dfx-prl-delete-retreat-modal');
            var $confirmInput = $('#dfx-prl-retreat-name-confirm');
            var $confirmButton = $('#dfx-prl-confirm-delete');

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
            $('#dfx-prl-cancel-delete, .dfx-prl-modal-overlay').on('click', function(e) {
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
                $button.prop('disabled', true).text(dfxPRLAdmin.messages.deleting);

                $.ajax({
                    url: dfxPRLAdmin.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'dfx_prl_delete_retreat',
                        retreat_id: retreatId,
                        retreat_name: retreatName,
                        nonce: dfxPRLAdmin.nonce
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
                            alert(response.data.message || dfxPRLAdmin.messages.deleteError);
                            // Re-enable button
                            $button.prop('disabled', false).text(dfxPRLAdmin.messages.deleteButton);
                        }
                    },
                    error: function() {
                        alert(dfxPRLAdmin.messages.deleteError);
                        // Re-enable button
                        $button.prop('disabled', false).text(dfxPRLAdmin.messages.deleteButton);
                    }
                });
            });
        }

        // Handle delete attendant button clicks
        $('.dfx-prl-delete-attendant').on('click', function(e) {
            e.preventDefault();

            var attendantId = $(this).data('attendant-id');
            var $row = $(this).closest('tr');

            // Check if dfxPRLAdmin is available
            var confirmMessage = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.messages && dfxPRLAdmin.messages.confirmDeleteAttendant)
                ? dfxPRLAdmin.messages.confirmDeleteAttendant
                : 'Are you sure you want to delete this attendant?';

            if (!confirm(confirmMessage)) {
                return;
            }

            // Disable button and show loading state
            $(this).prop('disabled', true).text('Deleting...');

            var ajaxUrl = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.ajaxurl)
                ? dfxPRLAdmin.ajaxurl
                : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.nonce)
                ? dfxPRLAdmin.nonce
                : '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfx_prl_delete_attendant',
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
                        $('.dfx-prl-delete-attendant[data-attendant-id="' + attendantId + '"]')
                            .prop('disabled', false)
                            .text('Delete');
                    }
                },
                error: function() {
                    alert('Error deleting attendant. Please try again.');
                    // Re-enable button
                    $('.dfx-prl-delete-attendant[data-attendant-id="' + attendantId + '"]')
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

        // Auto-dismiss notices - disabled to prevent automatic hiding
        // $('.notice.is-dismissible').delay(5000).fadeOut();

        // Handle generate message URL button clicks
        $('.dfx-prl-generate-url').on('click', function(e) {
            e.preventDefault();

            var attendantId = $(this).data('attendant-id');
            var $button = $(this);

            // Get localized text with fallbacks
            var generatingText = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.messages && dfxPRLAdmin.messages.generating)
                ? dfxPRLAdmin.messages.generating
                : 'Generating URL...';
            var urlGeneratedText = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.messages && dfxPRLAdmin.messages.urlGenerated)
                ? dfxPRLAdmin.messages.urlGenerated
                : 'Message URL generated successfully!';
            var generateErrorText = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.messages && dfxPRLAdmin.messages.generateError)
                ? dfxPRLAdmin.messages.generateError
                : 'Error generating message URL. Please try again.';

            // Disable button and show loading state
            $button.prop('disabled', true).text(generatingText);

            var ajaxUrl = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.ajaxurl)
                ? dfxPRLAdmin.ajaxurl
                : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.nonce)
                ? dfxPRLAdmin.nonce
                : '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfx_prl_generate_message_url',
                    attendant_id: attendantId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Get attendant name and surnames from response
                        var attendantName = response.data.attendant_name || '';
                        var attendantSurnames = response.data.attendant_surnames || '';
                        
                        // Replace button with copy URL button (including attendant data attributes)
                        var newButton = '<button type="button" class="button button-small button-primary dfx-prl-copy-url" ' +
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
        $(document).on('click', '.dfx-prl-copy-url', function(e) {
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
            var urlCopiedText = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.messages && dfxPRLAdmin.messages.urlCopied)
                ? dfxPRLAdmin.messages.urlCopied
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
        $('.dfx-prl-print-message').on('click', function(e) {
            e.preventDefault();

            var messageId = $(this).data('message-id');
            var $button = $(this);

            // Get localized text with fallbacks
            var printingText = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.messages && dfxPRLAdmin.messages.printing)
                ? dfxPRLAdmin.messages.printing
                : 'Preparing for print...';
            var printErrorText = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.messages && dfxPRLAdmin.messages.printError)
                ? dfxPRLAdmin.messages.printError
                : 'Error preparing message for print. Please try again.';

            // Disable button and show loading state
            $button.prop('disabled', true).text(printingText);

            var ajaxUrl = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.ajaxurl)
                ? dfxPRLAdmin.ajaxurl
                : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.nonce)
                ? dfxPRLAdmin.nonce
                : '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfx_prl_print_message',
                    message_id: messageId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Open print URL in new tab
                        window.open(response.data.print_url, '_blank');
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
        $('.dfx-prl-delete-message').on('click', function(e) {
            e.preventDefault();

            var messageId = $(this).data('message-id');
            var $row = $(this).closest('tr');

            // Get localized text with fallbacks
            var confirmMessage = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.messages && dfxPRLAdmin.messages.confirmDeleteMessage)
                ? dfxPRLAdmin.messages.confirmDeleteMessage
                : 'Are you sure you want to delete this message? This action cannot be undone.';
            var messageDeletedText = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.messages && dfxPRLAdmin.messages.messageDeleted)
                ? dfxPRLAdmin.messages.messageDeleted
                : 'Message deleted successfully.';

            if (!confirm(confirmMessage)) {
                return;
            }

            // Disable button and show loading state
            $(this).prop('disabled', true).text('Deleting...');

            var ajaxUrl = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.ajaxurl)
                ? dfxPRLAdmin.ajaxurl
                : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.nonce)
                ? dfxPRLAdmin.nonce
                : '';

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfx_prl_delete_message',
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
                        $('.dfx-prl-delete-message[data-message-id="' + messageId + '"]')
                            .prop('disabled', false)
                            .text('Delete');
                    }
                },
                error: function() {
                    alert('Error deleting message. Please try again.');
                    // Re-enable button
                    $('.dfx-prl-delete-message[data-message-id="' + messageId + '"]')
                        .prop('disabled', false)
                        .text('Delete');
                }
            });
        });

        // Handle view print log clicks
        $('.dfx-prl-view-print-log').on('click', function(e) {
            e.preventDefault();

            var messageId = $(this).data('message-id');

            var ajaxUrl = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.ajaxurl)
                ? dfxPRLAdmin.ajaxurl
                : (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
            var nonce = (typeof dfxPRLAdmin !== 'undefined' && dfxPRLAdmin.nonce)
                ? dfxPRLAdmin.nonce
                : '';

            // Show loading state
            showPrintLogModal(messageId, null, true);

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfx_prl_get_print_log',
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
            $('#dfx-prl-print-log-modal').remove();

            var modalHtml = '';

            if (loading) {
                modalHtml =
                    '<div id="dfx-prl-print-log-modal" class="dfx-prl-modal-overlay">' +
                        '<div class="dfx-prl-modal-dialog">' +
                            '<div class="dfx-prl-modal-header">' +
                                '<h3>Print History</h3>' +
                                '<button type="button" class="dfx-prl-modal-close" aria-label="Close">&times;</button>' +
                            '</div>' +
                            '<div class="dfx-prl-modal-body">' +
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
                    '<div id="dfx-prl-print-log-modal" class="dfx-prl-modal-overlay">' +
                        '<div class="dfx-prl-modal-dialog" style="max-width: 700px;">' +
                            '<div class="dfx-prl-modal-header">' +
                                '<h3>Print History (Total: ' + data.total_count + ')</h3>' +
                                '<button type="button" class="dfx-prl-modal-close" aria-label="Close">&times;</button>' +
                            '</div>' +
                            '<div class="dfx-prl-modal-body">' +
                                logsTable +
                            '</div>' +
                        '</div>' +
                    '</div>';
            }

            $('body').append(modalHtml);
            $('#dfx-prl-print-log-modal').show();

            // Handle close button clicks
            $('.dfx-prl-modal-close').on('click', function(e) {
                e.preventDefault();
                closePrintLogModal();
            });

            // Handle backdrop clicks
            $('#dfx-prl-print-log-modal').on('click', function(e) {
                if (e.target === this) {
                    closePrintLogModal();
                }
            });
        }

        // Function to close print log modal
        function closePrintLogModal() {
            $('#dfx-prl-print-log-modal').fadeOut(200, function() {
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
        $('.dfx-prl-show-invitation-message').on('click', function(e) {
            e.preventDefault();
            
            var attendantId = $(this).data('attendant-id');
            var retreatId = $(this).data('retreat-id');
            var $button = $(this);
            
            // Show loading state
            $button.prop('disabled', true);
            var originalText = $button.text();
            $button.text(dfxPRLAdmin.messages.loadingMessage || 'Loading...');
            
            // Make AJAX request to get the expanded message
            $.ajax({
                url: dfxPRLAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dfx_prl_get_invitation_message',
                    nonce: dfxPRLAdmin.nonce,
                    attendant_id: attendantId,
                    retreat_id: retreatId
                },
                success: function(response) {
                    $button.prop('disabled', false);
                    $button.text(originalText);
                    
                    if (response.success) {
                        showInvitationMessageModal(response.data.message);
                    } else {
                        alert(response.data.message || dfxPRLAdmin.messages.loadMessageError);
                    }
                },
                error: function() {
                    $button.prop('disabled', false);
                    $button.text(originalText);
                    alert(dfxPRLAdmin.messages.loadMessageError);
                }
            });
        });

        // Show invitation message modal
        function showInvitationMessageModal(message) {
            // Create modal if it doesn't exist
            if (!$('#dfx-prl-invitation-modal').length) {
                $('body').append(
                    '<div id="dfx-prl-invitation-modal" class="dfx-prl-modal-overlay">' +
                        '<div class="dfx-prl-modal-dialog">' +
                            '<div class="dfx-prl-modal-header">' +
                                '<h3>' + (dfxPRLAdmin.messages.invitationMessageTitle || 'Invitation Message') + '</h3>' +
                                '<button class="dfx-prl-modal-close">&times;</button>' +
                            '</div>' +
                            '<div class="dfx-prl-modal-body">' +
                                '<div class="dfx-prl-message-content" style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 15px; white-space: pre-wrap; font-family: monospace; max-height: 400px; overflow-y: auto;"></div>' +
                            '</div>' +
                            '<div class="dfx-prl-modal-footer">' +
                                '<button class="button button-primary dfx-prl-copy-message">' + 'Copy Message' + '</button>' +
                                '<button class="button dfx-prl-modal-close">' + (dfxPRLAdmin.messages.cancelButton || 'Close') + '</button>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
                
                // Handle close button clicks
                $('#dfx-prl-invitation-modal').on('click', '.dfx-prl-modal-close', function() {
                    $('#dfx-prl-invitation-modal').fadeOut(200);
                });
                
                // Handle copy button click
                $('#dfx-prl-invitation-modal').on('click', '.dfx-prl-copy-message', function() {
                    var messageText = $('#dfx-prl-invitation-modal .dfx-prl-message-content').text();
                    
                    if (copyToClipboard(messageText)) {
                        alert(dfxPRLAdmin.messages.messageCopied || 'Message copied to clipboard!');
                        $('#dfx-prl-invitation-modal').fadeOut(200);
                    } else {
                        alert(dfxPRLAdmin.messages.messageCopyError || 'Failed to copy message.');
                    }
                });
                
                // Close on overlay click
                $('#dfx-prl-invitation-modal').on('click', function(e) {
                    if ($(e.target).is('#dfx-prl-invitation-modal')) {
                        $(this).fadeOut(200);
                    }
                });
            }
            
            // Set the message content and show the modal
            $('#dfx-prl-invitation-modal .dfx-prl-message-content').text(message);
            $('#dfx-prl-invitation-modal').fadeIn(200);
        }
    });

})(jQuery);