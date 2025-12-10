/**
 * Admin Footer Positioning JavaScript
 * 
 * Adjusts admin footer positioning based on WordPress admin menu state.
 * Requires jQuery.
 * 
 * @package DFXPRL
 * @since 25.12.10
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Adjust footer positioning based on admin menu state
		function adjustFooterPosition() {
			var $footer = $('.dfxprl-plugin-footer');
			var $adminMenu = $('#adminmenumain');

			if ($adminMenu.length && $adminMenu.hasClass('folded')) {
				// Menu is collapsed
				$footer.css('left', '36px');
			} else {
				// Menu is expanded
				$footer.css('left', '160px');
			}
		}

		// Initial adjustment
		adjustFooterPosition();

		// Listen for menu fold/unfold events
		$(document).on('wp-collapse-menu', adjustFooterPosition);

		// Fallback: monitor window resize
		$(window).on('resize', adjustFooterPosition);
	});

})(jQuery);
