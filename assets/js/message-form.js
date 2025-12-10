/**
 * Message Form JavaScript
 * 
 * Handles the confidential message submission form functionality.
 * Requires jQuery and expects dfxprlMessageForm object to be localized.
 * 
 * @package DFXPRL
 * @since 25.12.10
 */

(function($) {
	'use strict';

	// Wait for DOM to be ready
	$(document).ready(function() {
		var editor = $('#message_content');
		var hiddenInput = $('#message_content_hidden');

		// Initialize editor
		if (editor.length) {
			// Set initial placeholder state
			if (editor.text().trim() === '') {
				editor.addClass('empty');
			}

			// Make sure contenteditable is properly set
			editor.attr('contenteditable', 'true');
		}

		// Generate and display CAPTCHA immediately
		generateCaptcha();

		// Initialize submit button state based on disclaimer
		updateSubmitButtonState();

		// Handle disclaimer checkbox changes
		$('#disclaimer_accepted').on('change', function() {
			updateSubmitButtonState();
		});

		// Message mode switching
		$('input[name="message_mode"]').on('change', function() {
			var mode = $(this).val();
			if (mode === 'text') {
				$('#dfxprl-text-group').show();
				$('#dfxprl-file-group').hide();
				hiddenInput.prop('required', true);
				$('#message_files').prop('required', false);
			} else {
				$('#dfxprl-text-group').hide();
				$('#dfxprl-file-group').show();
				hiddenInput.prop('required', false);
				$('#message_files').prop('required', true);
			}
		});

		// Editor functionality
		editor.on('input paste keyup', function() {
			// Sync contenteditable content to hidden textarea
			hiddenInput.val($(this).html());

			// Update placeholder visibility
			if ($(this).text().trim() === '') {
				$(this).addClass('empty');
			} else {
				$(this).removeClass('empty');
			}
		});

		// Handle paste events to clean up content
		editor.on('paste', function(e) {
			e.preventDefault();

			var paste = (e.originalEvent.clipboardData || window.clipboardData).getData('text/html');
			if (!paste) {
				paste = (e.originalEvent.clipboardData || window.clipboardData).getData('text/plain');
				paste = paste.replace(/\n/g, '<br>');
			}

			// Clean the pasted content
			var cleanHTML = cleanPastedContent(paste);
			document.execCommand('insertHTML', false, cleanHTML);

			// Update hidden input
			hiddenInput.val(editor.html());
		});

		// File handling
		$('#message_files').on('change', function() {
			displaySelectedFiles(this.files);
		});

		// Form submission
		$('#dfxprl-message-form').on('submit', function(e) {
			e.preventDefault();
			e.stopPropagation();

			// Sync editor content before submission
			if (editor.length) {
				hiddenInput.val(editor.html());
			}

			submitMessage();

			// Ensure no actual form submission
			return false;
		});

		// Editor toolbar
		$('.dfxprl-editor-toolbar button').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var command = $(this).data('command');
			var button = $(this);

			// Focus the editor first
			editor.focus();

			// Get the current selection
			var selection = window.getSelection();

			// Handle different commands
			switch(command) {
				case 'bold':
					applyFormat('b', 'font-weight: bold;');
					break;
				case 'italic':
					applyFormat('i', 'font-style: italic;');
					break;
				case 'underline':
					applyFormat('u', 'text-decoration: underline;');
					break;
				case 'insertUnorderedList':
					toggleList('ul');
					break;
				case 'insertOrderedList':
					toggleList('ol');
					break;
				case 'undo':
				case 'redo':
					// These still work with execCommand in most browsers
					try {
						document.execCommand(command, false, null);
					} catch (error) {
						console.warn('Undo/Redo not supported:', error);
					}
					break;
			}

			// Update content and toolbar states
			setTimeout(function() {
				hiddenInput.val(editor.html());
				updateToolbarStates();
			}, 10);

			// Keep focus on editor
			editor.focus();
		});

		function applyFormat(tag, style) {
			var selection = window.getSelection();

			if (selection.rangeCount === 0) {
				return;
			}

			var range = selection.getRangeAt(0);
			var selectedText = range.toString();

			if (selectedText.length === 0) {
				// No selection, just place cursor and toggle state for next typing
				return;
			}

			try {
				// Check if selection is already formatted
				var ancestor = range.commonAncestorContainer;
				var isFormatted = false;

				// Check if we're inside the formatting tag
				var parent = ancestor.nodeType === Node.TEXT_NODE ? ancestor.parentNode : ancestor;
				while (parent && parent !== editor[0]) {
					if (parent.tagName && parent.tagName.toLowerCase() === tag) {
						isFormatted = true;
						break;
					}
					parent = parent.parentNode;
				}

				if (isFormatted) {
					// Remove formatting by unwrapping
					document.execCommand('removeFormat', false, null);
				} else {
					// Apply formatting
					var formattedElement = document.createElement(tag);
					try {
						range.surroundContents(formattedElement);
					} catch (e) {
						// Fallback to execCommand for complex selections
						document.execCommand(tag === 'b' ? 'bold' : tag === 'i' ? 'italic' : 'underline', false, null);
					}
				}
			} catch (e) {
				// Fallback to execCommand
				var execCommand = tag === 'b' ? 'bold' : tag === 'i' ? 'italic' : 'underline';
				document.execCommand(execCommand, false, null);
			}
		}

		function toggleList(listType) {
			try {
				var command = listType === 'ul' ? 'insertUnorderedList' : 'insertOrderedList';
				document.execCommand(command, false, null);
			} catch (e) {
				console.warn('List formatting not supported:', e);
			}
		}

		// Update toolbar button states based on current selection
		function updateToolbarStates() {
			$('.dfxprl-editor-toolbar button').each(function() {
				var command = $(this).data('command');
				var isActive = false;

				try {
					// Check if cursor is within formatted text
					var selection = window.getSelection();
					if (selection.rangeCount > 0) {
						var range = selection.getRangeAt(0);
						var element = range.startContainer;

						// Traverse up to find formatting
						while (element && element !== editor[0]) {
							if (element.nodeType === Node.ELEMENT_NODE) {
								var tagName = element.tagName.toLowerCase();
								if ((command === 'bold' && tagName === 'b') ||
									(command === 'italic' && tagName === 'i') ||
									(command === 'underline' && tagName === 'u') ||
									(command === 'insertUnorderedList' && tagName === 'ul') ||
									(command === 'insertOrderedList' && tagName === 'ol')) {
									isActive = true;
									break;
								}
							}
							element = element.parentNode;
						}
					}
				} catch (e) {
					// Fallback to queryCommandState if available
					try {
						isActive = document.queryCommandState(command);
					} catch (e2) {
						// Command not supported
					}
				}

				$(this).toggleClass('active', isActive);
			});
		}

		// Update toolbar states when selection changes
		editor.on('mouseup keyup', function() {
			setTimeout(updateToolbarStates, 10);
		});

		function cleanPastedContent(html) {
			// First, remove style tags and their content using regex before DOM manipulation
			// This prevents any MSO style definitions from being parsed
			html = html.replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '');
			
			// Remove MSO conditional comments
			html = html.replace(/<!--\[if[^\]]*\]>[\s\S]*?<!\[endif\]-->/gi, '');
			
			// Remove regular comments (often contain MSO metadata)
			html = html.replace(/<!--[\s\S]*?-->/g, '');
			
			// Create a temporary div to clean the content
			var temp = $('<div>').html(html);

			// Remove potentially dangerous elements
			temp.find('script, meta, link').remove();

			// For images, preserve src attribute but validate it's a data URL
			temp.find('img').each(function() {
				var $img = $(this);
				var src = $img.attr('src');

				// Remove all attributes first
				$img.removeAttr('style class id onclick onload onerror');

				// Only keep src if it's a valid data URL (base64 image)
				if (src && src.match(/^data:image\/(jpeg|jpg|png|gif|webp|bmp|svg\+xml);base64,[A-Za-z0-9+/=]+$/i)) {
					$img.attr('src', src);
				}
			});

			// For all other elements, remove dangerous attributes
			temp.find('*:not(img)').removeAttr('style class id onclick onload onerror');

			// Convert common formatting
			temp.find('div').replaceWith(function() {
				return '<p>' + $(this).html() + '</p>';
			});

			return temp.html();
		}

		function generateCaptcha() {
			var num1 = Math.floor(Math.random() * 10) + 1;
			var num2 = Math.floor(Math.random() * 10) + 1;
			var operations = ['+', '-', '×', '/'];
			var operation = operations[Math.floor(Math.random() * operations.length)];

			// Ensure no negative numbers in subtraction
			if (operation === '-' && num1 < num2) {
				var temp = num1;
				num1 = num2;
				num2 = temp;
			}

			// Ensure clean division (no remainders)
			if (operation === '/') {
				num1 = num2 * (Math.floor(Math.random() * 9) + 1); // num1 = num2 * (1-9)
			}

			var question = num1 + ' ' + operation + ' ' + num2 + ' = ?';
			var answer;

			switch(operation) {
				case '+': answer = num1 + num2; break;
				case '-': answer = num1 - num2; break;
				case '×': answer = num1 * num2; break;
				case '/': answer = num1 / num2; break;
			}

			// Set question text with proper string
			var questionElement = $('#dfxprl-captcha-question');
			if (questionElement.length) {
				questionElement.html(dfxprlMessageForm.i18n.captchaPrefix + '<strong>' + question + '</strong>');
			}

			// Set token and clear answer
			var tokenElement = $('#captcha_token');
			var answerElement = $('#captcha_answer');

			if (tokenElement.length) {
				try {
					tokenElement.val(btoa(answer.toString()));
				} catch(e) {
					// Silent fail - CAPTCHA will be invalid
				}
			}

			if (answerElement.length) {
				answerElement.val('');
			}
		}

		function displaySelectedFiles(files) {
			var container = $('#dfxprl-file-list');
			container.empty();

			Array.from(files).forEach(function(file, index) {
				var fileItem = $('<div class="dfxprl-file-item">' +
					'<span>' + file.name + ' (' + formatFileSize(file.size) + ')</span>' +
					'<button type="button" class="dfxprl-file-remove" data-index="' + index + '">' + dfxprlMessageForm.i18n.remove + '</button>' +
					'</div>');

				container.append(fileItem);
			});
		}

		function formatFileSize(bytes) {
			if (bytes === 0) return '0 Bytes';
			var k = 1024;
			var sizes = ['Bytes', 'KB', 'MB', 'GB'];
			var i = Math.floor(Math.log(bytes) / Math.log(k));
			return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
		}

		function updateSubmitButtonState() {
			var disclaimerCheckbox = $('#disclaimer_accepted');
			var submitButton = $('#dfxprl-submit-btn');

			// If disclaimer exists, button is enabled only if checked
			if (disclaimerCheckbox.length > 0) {
				submitButton.prop('disabled', !disclaimerCheckbox.prop('checked'));
			} else {
				// No disclaimer, button is always enabled
				submitButton.prop('disabled', false);
			}
		}

		function submitMessage() {
			var form = $('#dfxprl-message-form')[0];
			var formData = new FormData(form);
			var submitButton = $('#dfxprl-submit-btn');
			var submitText = submitButton.find('.dfxprl-submit-text');
			var loadingSpinner = submitButton.find('.dfxprl-loading-spinner');

			// Validate based on mode
			var mode = $('input[name="message_mode"]:checked').val();
			
			if (mode === 'text') {
				var messageContent = $('#message_content_hidden').val();
				if (!messageContent || messageContent.trim() === '') {
					alert(dfxprlMessageForm.i18n.pleaseEnterMessage);
					return;
				}
			} else {
				var files = $('#message_files')[0].files;
				if (!files || files.length === 0) {
					alert(dfxprlMessageForm.i18n.pleaseSelectFile);
					return;
				}
			}

			// Validate CAPTCHA
			var captchaAnswer = $('#captcha_answer').val();
			var captchaToken = $('#captcha_token').val();

			if (!captchaAnswer || !captchaToken) {
				alert(dfxprlMessageForm.i18n.pleaseCompleteSecurityCheck);
				return;
			}

			// Validate disclaimer if present
			var disclaimerCheckbox = $('#disclaimer_accepted');
			if (disclaimerCheckbox.length > 0 && !disclaimerCheckbox.prop('checked')) {
				alert(dfxprlMessageForm.i18n.pleaseAcceptDisclaimer);
				return;
			}

			// Add action for WordPress AJAX
			formData.append('action', 'dfxprl_submit_message');

			// Disable submit button and show loading
			submitButton.prop('disabled', true);
			submitText.hide();
			loadingSpinner.show();

			$.ajax({
				url: dfxprlMessageForm.ajaxurl,
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				timeout: 60000, // 60 second timeout
				success: function(response) {
					if (response.success) {
						// Show success message
						var successHtml = '<div class="dfxprl-success-message">' + 
							dfxprlMessageForm.i18n.successMessage + '</div>';
						
						// Replace form with success message
						$('#dfxprl-message-form').replaceWith(successHtml);
					} else {
						// Show error message
						var errorMessage = response.data && response.data.message ? 
							response.data.message : dfxprlMessageForm.i18n.errorSendingMessage;
						alert(errorMessage);

						// Re-enable submit button
						submitButton.prop('disabled', false);
						submitText.show();
						loadingSpinner.hide();

						// Regenerate CAPTCHA on error
						generateCaptcha();
					}
				},
				error: function(xhr, status, error) {
					console.error('AJAX error:', status, error, xhr);

					var errorMessage;
					if (status === 'timeout') {
						errorMessage = dfxprlMessageForm.i18n.requestTimeout;
					} else if (status === 'abort') {
						errorMessage = dfxprlMessageForm.i18n.requestCancelled;
					} else if (xhr.status === 0) {
						errorMessage = dfxprlMessageForm.i18n.cannotConnectToServer;
					} else if (xhr.status === 400) {
						errorMessage = dfxprlMessageForm.i18n.problemWithRequest;
					} else if (xhr.status === 403) {
						errorMessage = dfxprlMessageForm.i18n.accessDenied;
					} else if (xhr.status === 413) {
						errorMessage = dfxprlMessageForm.i18n.uploadedFilesTooLarge;
					} else if (xhr.status >= 500) {
						errorMessage = dfxprlMessageForm.i18n.serverError;
					} else if (status === 'parsererror') {
						errorMessage = dfxprlMessageForm.i18n.problemProcessingResponse;
					} else {
						errorMessage = dfxprlMessageForm.i18n.networkError;
					}

					alert(errorMessage);

					// Re-enable submit button
					submitButton.prop('disabled', false);
					submitText.show();
					loadingSpinner.hide();

					// Regenerate CAPTCHA on error
					generateCaptcha();
				}
			});
		}
	});

})(jQuery);
