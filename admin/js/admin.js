/**
 * Admin JavaScript for DFX Parish Retreat Letters
 */

(function($) {
    'use strict';

    /**
     * Main admin object
     */
    var DFX_PRL_Admin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initDateValidation();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Delete retreat
            $(document).on('click', '.dfx-prl-delete-retreat', this.handleDeleteRetreat);
            
            // Form validation
            $(document).on('submit', '.dfx-prl-retreat-form', this.validateRetreatForm);
            
            // Clear search filters
            $(document).on('click', '.dfx-prl-clear-filters', this.clearFilters);
        },

        /**
         * Initialize date validation
         */
        initDateValidation: function() {
            var $startDate = $('#retreat-start-date');
            var $endDate = $('#retreat-end-date');
            
            if ($startDate.length && $endDate.length) {
                $startDate.on('change', function() {
                    var startDate = $(this).val();
                    if (startDate) {
                        $endDate.attr('min', startDate);
                    }
                });
                
                $endDate.on('change', function() {
                    var endDate = $(this).val();
                    if (endDate) {
                        $startDate.attr('max', endDate);
                    }
                });
            }
        },

        /**
         * Handle delete retreat
         */
        handleDeleteRetreat: function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var retreatId = $this.data('retreat-id');
            var $row = $('#retreat-' + retreatId);
            
            // Confirm deletion
            if (!confirm(dfx_prl_ajax.confirm_delete)) {
                return;
            }
            
            // Add loading state
            $row.addClass('dfx-prl-deleting');
            $this.append('<span class="dfx-prl-loading"></span>');
            
            // Send AJAX request
            $.ajax({
                url: dfx_prl_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'dfx_prl_delete_retreat',
                    retreat_id: retreatId,
                    nonce: dfx_prl_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Fade out and remove row
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            DFX_PRL_Admin.showMessage(response.data.message, 'success');
                            DFX_PRL_Admin.updateResultsCount();
                        });
                    } else {
                        DFX_PRL_Admin.showMessage(response.data.message, 'error');
                        $row.removeClass('dfx-prl-deleting');
                        $this.find('.dfx-prl-loading').remove();
                    }
                },
                error: function() {
                    DFX_PRL_Admin.showMessage('An error occurred while deleting the retreat.', 'error');
                    $row.removeClass('dfx-prl-deleting');
                    $this.find('.dfx-prl-loading').remove();
                }
            });
        },

        /**
         * Validate retreat form
         */
        validateRetreatForm: function(e) {
            var $form = $(this);
            var isValid = true;
            var errors = [];
            
            // Get form values
            var name = $form.find('input[name="name"]').val().trim();
            var location = $form.find('input[name="location"]').val().trim();
            var startDate = $form.find('input[name="start_date"]').val();
            var endDate = $form.find('input[name="end_date"]').val();
            
            // Validate required fields
            if (!name) {
                errors.push('Retreat name is required.');
                isValid = false;
            }
            
            if (!location) {
                errors.push('Location is required.');
                isValid = false;
            }
            
            if (!startDate) {
                errors.push('Start date is required.');
                isValid = false;
            }
            
            if (!endDate) {
                errors.push('End date is required.');
                isValid = false;
            }
            
            // Validate date range
            if (startDate && endDate && startDate > endDate) {
                errors.push('Start date cannot be after end date.');
                isValid = false;
            }
            
            // Show errors if any
            if (!isValid) {
                e.preventDefault();
                DFX_PRL_Admin.showMessage(errors.join('<br>'), 'error');
                
                // Focus on first invalid field
                $form.find('input[required]').each(function() {
                    if (!$(this).val().trim()) {
                        $(this).focus();
                        return false;
                    }
                });
            }
            
            return isValid;
        },

        /**
         * Clear search filters
         */
        clearFilters: function(e) {
            e.preventDefault();
            
            var url = window.location.href.split('?')[0] + '?page=dfx-prl-retreats';
            window.location.href = url;
        },

        /**
         * Show message
         */
        showMessage: function(message, type) {
            type = type || 'info';
            
            // Remove existing messages
            $('.dfx-prl-message').remove();
            
            // Create new message
            var $message = $('<div class="dfx-prl-message ' + type + '">' + message + '</div>');
            
            // Insert message after page title
            $('.wrap h1').after($message);
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut();
                }, 5000);
            }
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 300);
        },

        /**
         * Update results count
         */
        updateResultsCount: function() {
            var $table = $('.wp-list-table tbody');
            var count = $table.find('tr').length;
            
            if (count === 0) {
                // Show no retreats message
                var $noRetreats = $('<div class="no-retreats"><p>No retreats found.</p></div>');
                $table.closest('.wp-list-table').replaceWith($noRetreats);
            } else {
                // Update count in results info
                var $resultsInfo = $('.dfx-prl-results-info p');
                if ($resultsInfo.length) {
                    var text = $resultsInfo.text();
                    var newText = text.replace(/Showing \d+/, 'Showing ' + count);
                    $resultsInfo.text(newText);
                }
            }
        },

        /**
         * Format date for display
         */
        formatDate: function(dateString) {
            if (!dateString) return '';
            
            var date = new Date(dateString);
            var options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            
            return date.toLocaleDateString(undefined, options);
        },

        /**
         * Calculate duration between two dates
         */
        calculateDuration: function(startDate, endDate) {
            if (!startDate || !endDate) return 0;
            
            var start = new Date(startDate);
            var end = new Date(endDate);
            var diffTime = Math.abs(end - start);
            var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            return diffDays + 1; // Include both start and end days
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        DFX_PRL_Admin.init();
    });

    /**
     * Additional utilities
     */
    
    // Auto-resize textareas
    $(document).on('input', 'textarea', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Enhanced tooltips for action links
    $(document).on('mouseenter', '.row-actions a', function() {
        var $this = $(this);
        var title = $this.text();
        
        if (!$this.attr('title')) {
            $this.attr('title', title);
        }
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + N = Add new retreat
        if ((e.ctrlKey || e.metaKey) && e.which === 78) {
            var addUrl = 'admin.php?page=dfx-prl-add-retreat';
            if (window.location.href.indexOf(addUrl) === -1) {
                e.preventDefault();
                window.location.href = addUrl;
            }
        }
    });

})(jQuery);