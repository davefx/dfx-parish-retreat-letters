<?php
/**
 * Tests for emptying a finished retreat.
 *
 * @package DFXPRL
 * @subpackage Tests
 */

/**
 * Test the "Empty retreat" action and the permission check when deleting a retreat.
 *
 * These are source-level checks, so they run without WordPress.
 */
class EmptyRetreatTest extends \PHPUnit\Framework\TestCase {

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

	public function test_a_retreat_has_ended_only_after_its_end_date() {
		$source = $this->get_method_source( 'includes/class-retreat.php', 'has_ended' );
		$this->assertStringContainsString( "current_time( 'Y-m-d' ) > \$retreat->end_date", $source );
	}

	public function test_emptying_checks_nonce_permission_end_date_and_name_before_deleting() {
		$source = $this->get_method_source( 'includes/class-admin.php', 'ajax_empty_retreat' );
		$delete = strpos( $source, 'delete_by_retreat(' );
		$this->assertNotFalse( $delete );

		foreach ( array(
			"wp_verify_nonce( sanitize_text_field( wp_unslash( \$_POST['nonce'] ?? '' ) ), 'dfxprl_retreats_nonce' )",
			'current_user_can_manage_retreat( $retreat_id )',
			'DFXPRL_Retreat::has_ended( $retreat )',
			'$retreat->name !== $retreat_name',
		) as $check ) {
			$position = strpos( $source, $check );
			$this->assertNotFalse( $position, "Missing check: $check" );
			$this->assertLessThan( $delete, $position, "$check must run before deleting" );
		}

		$this->assertStringContainsString( "'retreat_emptied'", $source );
	}

	public function test_empty_button_is_only_shown_for_finished_retreats() {
		$source = $this->get_method_source( 'includes/class-admin.php', 'render_attendants_list_page' );
		$this->assertStringContainsString( '$can_manage_retreat && DFXPRL_Retreat::has_ended( $retreat )', $source );
	}

	public function test_only_plugin_administrators_can_delete_a_retreat() {
		$source = $this->get_method_source( 'includes/class-admin.php', 'ajax_delete_retreat' );
		$this->assertLessThan(
			strpos( $source, '->delete( $retreat_id )' ),
			strpos( $source, 'current_user_can_manage_plugin()' )
		);
	}
}
