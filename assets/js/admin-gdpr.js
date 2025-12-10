/**
 * GDPR Tools JavaScript
 * 
 * Handles GDPR data export and erasure functionality.
 * Requires jQuery and expects dfxPRLGDPR object to be localized.
 * 
 * @package DFX_Parish_Retreat_Letters
 * @since 25.12.10
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Export personal data
		$('#export-data-btn').on('click', function() {
			var identifier = $('#export-identifier').val().trim();
			if (!identifier) {
				alert(dfxPRLGDPR.i18n.pleaseEnterIdentifier);
				return;
			}

			var form = $('<form>', {
				method: 'POST',
				action: dfxPRLGDPR.ajaxurl
			});

			form.append($('<input>', { type: 'hidden', name: 'action', value: 'dfx_prl_export_personal_data' }));
			form.append($('<input>', { type: 'hidden', name: 'identifier', value: identifier }));
			form.append($('<input>', { type: 'hidden', name: 'nonce', value: dfxPRLGDPR.nonce }));

			$('body').append(form);
			form.submit();
		});

		// Erase personal data
		$('#erase-data-btn').on('click', function() {
			var identifier = $('#erase-identifier').val().trim();
			var confirm = $('#erase-confirm').val().trim();

			if (!identifier) {
				alert(dfxPRLGDPR.i18n.pleaseEnterIdentifier);
				return;
			}

			if (confirm !== dfxPRLGDPR.i18n.confirmationText) {
				alert(dfxPRLGDPR.i18n.invalidConfirmation);
				return;
			}

			if (!window.confirm(dfxPRLGDPR.i18n.confirmErasure)) {
				return;
			}

			var $button = $(this);
			$button.prop('disabled', true).text(dfxPRLGDPR.i18n.erasing);

			$.ajax({
				url: dfxPRLGDPR.ajaxurl,
				type: 'POST',
				data: {
					action: 'dfx_prl_erase_personal_data',
					identifier: identifier,
					nonce: dfxPRLGDPR.nonce
				},
				success: function(response) {
					if (response.success) {
						alert(dfxPRLGDPR.i18n.dataErased);
						$('#erase-identifier').val('');
						$('#erase-confirm').val('');
					} else {
						alert(response.data.message || dfxPRLGDPR.i18n.erasureError);
					}
					$button.prop('disabled', false).text(dfxPRLGDPR.i18n.eraseDataButton);
				},
				error: function() {
					alert(dfxPRLGDPR.i18n.erasureError);
					$button.prop('disabled', false).text(dfxPRLGDPR.i18n.eraseDataButton);
				}
			});
		});
	});

})(jQuery);
