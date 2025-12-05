<?php
/**
 * Tests for the Delete All Attendants feature
 *
 * @package DFX_Parish_Retreat_Letters
 * @subpackage Tests
 */

/**
 * Test the delete all attendants feature
 */
class DeleteAllAttendantsTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
		// Create the directory for MessageFile class if needed
		if (!file_exists('/tmp/uploads/dfx-prl-confidential-files')) {
			mkdir('/tmp/uploads/dfx-prl-confidential-files', 0755, true);
		}
	}

	/**
	 * Test that ajax_delete_all_attendants has proper security checks
	 */
	public function testAjaxHandlerHasSecurityChecks() {
		$admin_file = dirname(__FILE__) . '/../../includes/class-admin.php';
		$this->assertFileExists($admin_file, 'class-admin.php file should exist');
		
		$method_code = file_get_contents($admin_file);

		// Check for nonce verification
		$this->assertStringContainsString(
			'wp_verify_nonce',
			$method_code,
			'ajax_delete_all_attendants should verify nonce'
		);

		// Check for confirmation text verification
		$this->assertStringContainsString(
			'confirmation_text',
			$method_code,
			'ajax_delete_all_attendants should verify confirmation text'
		);

		// Check for permission check
		$this->assertStringContainsString(
			'current_user_can_manage_retreat',
			$method_code,
			'ajax_delete_all_attendants should check user permissions'
		);

		// Check for the new AJAX action method
		$this->assertStringContainsString(
			'function ajax_delete_all_attendants',
			$method_code,
			'ajax_delete_all_attendants method should exist'
		);

		// Check for DELETE ALL ATTENDANTS confirmation
		$this->assertStringContainsString(
			'DELETE ALL ATTENDANTS',
			$method_code,
			'Should require specific confirmation text'
		);
	}

	/**
	 * Test that the required localized strings are present
	 */
	public function testLocalizedStringsArePresent() {
		$admin_file = dirname(__FILE__) . '/../../includes/class-admin.php';
		$admin_content = file_get_contents($admin_file);

		// Check for required localized strings
		$required_strings = array(
			'deleteAllAttendantsTitle',
			'deleteAllAttendantsWarning',
			'deleteAllAttendantsWarningCount',
			'deleteAllAttendantsWarningAttendants',
			'deleteAllAttendantsWarningMessages',
			'confirmationText',
			'typeConfirmation',
			'confirmationPlaceholder',
			'deleteAllButton',
		);

		foreach ($required_strings as $string) {
			$this->assertStringContainsString(
				$string,
				$admin_content,
				"Localized string '$string' should be present"
			);
		}
	}

	/**
	 * Test that the JavaScript handler is present in admin.js
	 */
	public function testJavaScriptHandlerExists() {
		$js_file = dirname(__FILE__) . '/../../includes/admin.js';
		$this->assertFileExists($js_file, 'admin.js file should exist');

		$js_content = file_get_contents($js_file);

		// Check for the delete all attendants button handler
		$this->assertStringContainsString(
			'dfx-prl-delete-all-attendants',
			$js_content,
			'JavaScript should have handler for delete-all-attendants button'
		);

		// Check for the confirmation modal function
		$this->assertStringContainsString(
			'showDeleteAllAttendantsModal',
			$js_content,
			'JavaScript should have showDeleteAllAttendantsModal function'
		);

		// Check for AJAX action
		$this->assertStringContainsString(
			'dfx_prl_delete_all_attendants',
			$js_content,
			'JavaScript should call dfx_prl_delete_all_attendants AJAX action'
		);

		// Check for confirmation text verification using localized string
		$this->assertStringContainsString(
			'dfxPRLAdmin.messages.confirmationText',
			$js_content,
			'JavaScript should use localized confirmationText for verification'
		);
	}

	/**
	 * Test that the UI button is conditionally rendered
	 */
	public function testUIButtonIsConditionallyRendered() {
		$admin_file = dirname(__FILE__) . '/../../includes/class-admin.php';
		$this->assertFileExists($admin_file, 'class-admin.php file should exist');

		$admin_content = file_get_contents($admin_file);

		// Check that button is only shown when there are attendants and user has permission
		$this->assertStringContainsString(
			'dfx-prl-delete-all-attendants',
			$admin_content,
			'Admin should have delete-all-attendants button'
		);

		$this->assertStringContainsString(
			'$total_items > 0',
			$admin_content,
			'Button should only be shown when there are attendants'
		);

		$this->assertStringContainsString(
			'current_user_can_manage_retreat',
			$admin_content,
			'Button should check user permissions'
		);
	}

	/**
	 * Test that proper data attributes are set on the button
	 */
	public function testButtonHasProperDataAttributes() {
		$admin_file = dirname(__FILE__) . '/../../includes/class-admin.php';
		$admin_content = file_get_contents($admin_file);

		// Find the button in the content
		$button_pattern = '/class="[^"]*dfx-prl-delete-all-attendants[^"]*"[^>]*>/';
		$this->assertMatchesRegularExpression(
			$button_pattern,
			$admin_content,
			'Delete all attendants button should exist in admin'
		);

		// Check for required data attributes
		$required_attributes = array(
			'data-retreat-id',
			'data-retreat-name',
			'data-attendant-count',
			'data-message-count',
		);

		foreach ($required_attributes as $attribute) {
			$this->assertStringContainsString(
				$attribute,
				$admin_content,
				"Button should have $attribute attribute"
			);
		}
	}

	/**
	 * Test that AJAX action hook is registered
	 */
	public function testAjaxActionHookIsRegistered() {
		$admin_file = dirname(__FILE__) . '/../../includes/class-admin.php';
		$admin_content = file_get_contents($admin_file);

		// Check that the AJAX action is registered
		$this->assertStringContainsString(
			'wp_ajax_dfx_prl_delete_all_attendants',
			$admin_content,
			'AJAX action hook should be registered'
		);

		$this->assertStringContainsString(
			'ajax_delete_all_attendants',
			$admin_content,
			'AJAX handler method should be referenced in action hook'
		);
	}

	/**
	 * Test that the feature uses the existing delete_by_retreat method
	 */
	public function testFeatureUsesExistingDeleteByRetreatMethod() {
		$attendant_file = dirname(__FILE__) . '/../../includes/class-attendant.php';
		$this->assertFileExists($attendant_file, 'class-attendant.php file should exist');
		
		$attendant_content = file_get_contents($attendant_file);

		// Check that delete_by_retreat method exists and handles cascade deletion
		$this->assertStringContainsString(
			'function delete_by_retreat',
			$attendant_content,
			'delete_by_retreat method should exist'
		);

		// Check that it deletes messages (cascade delete)
		$this->assertStringContainsString(
			'delete_by_attendants',
			$attendant_content,
			'delete_by_retreat should cascade delete messages'
		);

		// Check that it's used in the AJAX handler
		$admin_file = dirname(__FILE__) . '/../../includes/class-admin.php';
		$admin_content = file_get_contents($admin_file);
		
		$this->assertStringContainsString(
			'delete_by_retreat',
			$admin_content,
			'AJAX handler should use delete_by_retreat method'
		);
	}
}
