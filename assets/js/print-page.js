/**
 * Print Page JavaScript
 * 
 * Auto-triggers print dialog and handles window closing after printing.
 * 
 * @package DFXPRL
 * @since 25.12.10
 */

(function() {
	'use strict';

	// Auto-trigger print dialog when page loads
	window.addEventListener('load', function() {
		// Small delay to ensure page is fully rendered
		setTimeout(function() {
			window.print();

			// Fallback: close window after a reasonable time if afterprint doesn't fire
			setTimeout(function() {
				if (!window.closed) {
					window.close();
				}
			}, 3000); // 3 seconds fallback
		}, 100);
	});

	// Close tab after printing
	window.addEventListener('afterprint', function() {
		setTimeout(function() {
			window.close();
		}, 100);
	});

	// Handle print dialog cancellation (beforeprint + timeout)
	var printStarted = false;
	window.addEventListener('beforeprint', function() {
		printStarted = true;
	});

	// Additional fallback for browsers that don't support afterprint
	window.addEventListener('focus', function() {
		if (printStarted) {
			setTimeout(function() {
				if (!window.closed) {
					window.close();
				}
			}, 500);
		}
	});

})();
