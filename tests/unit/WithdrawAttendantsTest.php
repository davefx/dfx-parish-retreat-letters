<?php
/**
 * Tests for withdrawing attendants ("dar de baja").
 *
 * @package DFXPRL
 * @subpackage Tests
 */

/**
 * Test the attendant withdrawal feature.
 *
 * These are source-level checks on the security-relevant parts of the feature, so
 * they run without WordPress.
 */
class WithdrawAttendantsTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Get the source of a method of a class file.
	 *
	 * @param string $file   Path relative to the plugin root.
	 * @param string $method Method name.
	 * @return string
	 */
	private function get_method_source( $file, $method ) {
		$source = file_get_contents( dirname( __DIR__, 2 ) . '/' . $file );
		$start  = strpos( $source, 'function ' . $method . '(' );
		$this->assertNotFalse( $start, "Method $method should exist in $file" );

		// The method ends where the next method's docblock starts
		$end = strpos( $source, "\n\t/**", $start );
		return substr( $source, $start, false === $end ? null : $end - $start );
	}

	public function test_attendants_table_stores_the_withdrawal() {
		$source = $this->get_method_source( 'includes/class-database.php', 'setup_tables' );
		$this->assertStringContainsString( 'withdrawn_at datetime NULL DEFAULT NULL', $source );
		$this->assertStringContainsString( 'withdrawn_by bigint(20) unsigned NULL DEFAULT NULL', $source );
	}

	public function test_status_ajax_actions_are_registered() {
		$source = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-admin.php' );
		foreach ( array( 'withdraw_attendant', 'reactivate_attendant', 'withdraw_all_attendants', 'delete_attendant' ) as $action ) {
			$this->assertStringContainsString( "'wp_ajax_dfxprl_{$action}', array( \$this, 'ajax_{$action}' )", $source );
		}
		$this->assertStringNotContainsString( 'wp_ajax_dfxprl_delete_all_attendants', $source, 'Deleting all attendants at once is no longer possible' );
	}

	public function test_single_attendant_actions_check_nonce_and_permissions() {
		foreach ( array( 'ajax_withdraw_attendant', 'ajax_reactivate_attendant', 'ajax_delete_attendant' ) as $method ) {
			$source = $this->get_method_source( 'includes/class-admin.php', $method );
			$this->assertStringContainsString( "wp_verify_nonce", $source, "$method should verify the nonce" );
			$this->assertStringContainsString( 'get_managed_attendant_from_request()', $source, "$method should check the retreat manager permission" );
		}

		$helper = $this->get_method_source( 'includes/class-admin.php', 'get_managed_attendant_from_request' );
		$this->assertStringContainsString( 'current_user_can_manage_retreat( $attendant->retreat_id )', $helper );
	}

	public function test_only_withdrawn_attendants_can_be_deleted() {
		$source = $this->get_method_source( 'includes/class-admin.php', 'ajax_delete_attendant' );
		$this->assertMatchesRegularExpression(
			'/if \( ! DFXPRL_Attendant::is_withdrawn\( \$attendant \) \) \{\s*wp_send_json_error/',
			$source,
			'Deleting an active attendant must be refused'
		);
		$this->assertLessThan(
			strpos( $source, '->delete(' ),
			strpos( $source, 'is_withdrawn' ),
			'The withdrawal check must happen before deleting'
		);
	}

	public function test_withdraw_all_requires_typed_confirmation_and_permission() {
		$source = $this->get_method_source( 'includes/class-admin.php', 'ajax_withdraw_all_attendants' );
		$this->assertStringContainsString( 'wp_verify_nonce', $source );
		$this->assertStringContainsString( 'current_user_can_manage_retreat( $retreat_id )', $source );
		$this->assertStringContainsString( "__( 'WITHDRAW ALL ATTENDANTS', 'dfx-parish-retreat-letters' )", $source );
		$this->assertStringContainsString( 'withdraw_all_by_retreat', $source );
		$this->assertStringNotContainsString( 'delete_by_retreat', $source, 'Withdrawing all attendants must not delete them' );
	}

	public function test_withdrawn_attendants_are_not_editable() {
		$this->assertStringContainsString(
			'is_withdrawn',
			$this->get_method_source( 'includes/class-admin.php', 'get_list_cell_editor' ),
			'Inline editing must be disabled for withdrawn attendants'
		);
		$this->assertStringContainsString(
			'is_withdrawn',
			$this->get_method_source( 'includes/class-admin.php', 'handle_attendant_add_edit_submission' ),
			'Saving the attendant form must be refused for withdrawn attendants'
		);
		$this->assertStringContainsString(
			'is_withdrawn',
			$this->get_method_source( 'includes/class-admin.php', 'attendant_edit_page' ),
			'The edit page must not open for withdrawn attendants'
		);
	}

	public function test_withdrawn_attendants_do_not_receive_messages() {
		$this->assertStringContainsString(
			'withdrawn_at',
			$this->get_method_source( 'includes/class-dfx-parish-retreat-letters.php', 'render_message_form' ),
			'The public form must not be shown for withdrawn attendants'
		);
		$this->assertMatchesRegularExpression(
			'/if \( ! empty\( \$retreat_data->withdrawn_at \) \) \{\s*wp_send_json_error/',
			$this->get_method_source( 'includes/class-dfx-parish-retreat-letters.php', 'handle_message_submission' ),
			'Submissions for withdrawn attendants must be refused'
		);
	}

	public function test_regular_list_only_shows_active_attendants_by_default() {
		$source = $this->get_method_source( 'includes/class-attendant.php', 'get_by_retreat' );
		$this->assertStringContainsString( "'withdrawn'                    => false", $source );
		$this->assertStringContainsString( 'get_withdrawn_clause( $args[\'withdrawn\'], \'a.\' )', $source );
	}
}
