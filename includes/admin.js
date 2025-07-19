/**
 * Admin JavaScript for DFX Parish Retreat Letters
 *
 * @package DFX_Parish_Retreat_Letters
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle delete retreat button clicks
        $('.dfx-delete-retreat').on('click', function(e) {
            e.preventDefault();
            
            var retreatId = $(this).data('retreat-id');
            var $row = $(this).closest('tr');
            
            if (!confirm(dfxRetreatsAdmin.messages.confirmDelete)) {
                return;
            }
            
            // Disable button and show loading state
            $(this).prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: dfxRetreatsAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'dfx_delete_retreat',
                    retreat_id: retreatId,
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
                        alert(response.data.message || 'Error deleting retreat.');
                        // Re-enable button
                        $('.dfx-delete-retreat[data-retreat-id="' + retreatId + '"]')
                            .prop('disabled', false)
                            .text('Delete');
                    }
                },
                error: function() {
                    alert('Error deleting retreat. Please try again.');
                    // Re-enable button
                    $('.dfx-delete-retreat[data-retreat-id="' + retreatId + '"]')
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
    });

})(jQuery);