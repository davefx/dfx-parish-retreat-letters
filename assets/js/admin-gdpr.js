/**
 * GDPR Tools JavaScript
 * 
 * Handles GDPR data export and erasure functionality.
 * Requires jQuery and expects dfxprlGDPR object to be localized.
 * 
 * @package DFXPRL
 * @since 25.12.10
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Export personal data
		$('#export-data-btn').on('click', function() {
			var identifier = $('#export-identifier').val().trim();
			if (!identifier) {
				alert(dfxprlGDPR.i18n.pleaseEnterIdentifier);
				return;
			}

			var form = $('<form>', {
				method: 'POST',
				action: dfxprlGDPR.ajaxurl
			});

			form.append($('<input>', { type: 'hidden', name: 'action', value: 'dfxprl_export_personal_data' }));
			form.append($('<input>', { type: 'hidden', name: 'identifier', value: identifier }));
			form.append($('<input>', { type: 'hidden', name: 'nonce', value: dfxprlGDPR.nonce }));

			$('body').append(form);
			form.submit();
		});

		// Erase personal data
		$('#erase-data-btn').on('click', function() {
			var identifier = $('#erase-identifier').val().trim();
			var confirm = $('#erase-confirm').val().trim();

			if (!identifier) {
				alert(dfxprlGDPR.i18n.pleaseEnterIdentifier);
				return;
			}

			if (confirm !== dfxprlGDPR.i18n.confirmationText) {
				alert(dfxprlGDPR.i18n.invalidConfirmation);
				return;
			}

			if (!window.confirm(dfxprlGDPR.i18n.confirmErasure)) {
				return;
			}

			var $button = $(this);
			$button.prop('disabled', true).text(dfxprlGDPR.i18n.erasing);

			$.ajax({
				url: dfxprlGDPR.ajaxurl,
				type: 'POST',
				data: {
					action: 'dfxprl_erase_personal_data',
					identifier: identifier,
					nonce: dfxprlGDPR.nonce
				},
				success: function(response) {
					if (response.success) {
						alert(dfxprlGDPR.i18n.dataErased);
						$('#erase-identifier').val('');
						$('#erase-confirm').val('');
					} else {
						alert(response.data.message || dfxprlGDPR.i18n.erasureError);
					}
					$button.prop('disabled', false).text(dfxprlGDPR.i18n.eraseDataButton);
				},
				error: function() {
					alert(dfxprlGDPR.i18n.erasureError);
					$button.prop('disabled', false).text(dfxprlGDPR.i18n.eraseDataButton);
				}
			});
		});
	});

})(jQuery);
