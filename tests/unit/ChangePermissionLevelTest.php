<?php
/**
 * Tests for changing the access level of users already assigned to a retreat.
 *
 * @package DFXPRL
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Retreat managers can change the level of the other users of their retreat, except
 * that of the other retreat managers; plugin administrators can change anyone's.
 */
class ChangePermissionLevelTest extends TestCase {

	/**
	 * Levels per user on retreat 5.
	 *
	 * @var array
	 */
	private $levels;

	/**
	 * Grant and revoke calls made, as "grant:user:level" / "revoke:user:level".
	 *
	 * @var array
	 */
	private $calls;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		require_once dirname( __DIR__, 2 ) . '/includes/class-permissions.php';

		Functions\when( '__' )->returnArg();
		Functions\when( 'get_user_by' )->alias(
			function( $field, $id ) {
				// User 1 is a plugin administrator; everybody else is not
				return new class( 1 === (int) $id ) {
					private $admin;
					public function __construct( $admin ) {
						$this->admin = $admin;
					}
					public function has_cap( $cap ) {
						return $this->admin && 'manage_retreat_plugin' === $cap;
					}
				};
			}
		);

		$this->levels = array(
			7  => array( 'manager' ),
			8  => array( 'manager' ),
			9  => array( 'read_only' ),
			10 => array( 'message_manager', 'read_only' ),
		);
		$this->calls = array();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Permissions object backed by $this->levels, recording grants and revocations.
	 *
	 * @return DFXPRL_Permissions
	 */
	private function permissions() {
		$permissions = $this->getMockBuilder( DFXPRL_Permissions::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'user_has_retreat_permission', 'get_user_retreat_levels', 'grant_permission', 'revoke_permission' ) )
			->getMock();
		$permissions->method( 'user_has_retreat_permission' )->willReturnCallback(
			function( $user_id, $retreat_id, $level ) {
				return 5 === (int) $retreat_id && in_array( $level, $this->levels[ (int) $user_id ] ?? array(), true );
			}
		);
		$permissions->method( 'get_user_retreat_levels' )->willReturnCallback(
			function( $user_id, $retreat_id ) {
				return 5 === (int) $retreat_id ? ( $this->levels[ (int) $user_id ] ?? array() ) : array();
			}
		);
		$permissions->method( 'grant_permission' )->willReturnCallback(
			function( $user_id, $retreat_id, $level ) {
				$this->calls[] = "grant:$user_id:$level";
				return 99;
			}
		);
		$permissions->method( 'revoke_permission' )->willReturnCallback(
			function( $user_id, $retreat_id, $level ) {
				$this->calls[] = "revoke:$user_id:$level";
				return true;
			}
		);
		return $permissions;
	}

	public function test_retreat_manager_can_change_users_who_are_not_managers() {
		$permissions = $this->permissions();
		$this->assertTrue( $permissions->user_can_change_permission_level( 7, 9, 5 ) );
		$this->assertTrue( $permissions->user_can_change_permission_level( 7, 10, 5 ) );
	}

	public function test_retreat_manager_cannot_change_other_managers_nor_themselves() {
		$permissions = $this->permissions();
		$this->assertFalse( $permissions->user_can_change_permission_level( 7, 8, 5 ) );
		$this->assertFalse( $permissions->user_can_change_permission_level( 7, 7, 5 ) );
	}

	public function test_only_managers_of_that_retreat_can_change_levels() {
		$permissions = $this->permissions();
		$this->assertFalse( $permissions->user_can_change_permission_level( 9, 10, 5 ), 'read only' );
		$this->assertFalse( $permissions->user_can_change_permission_level( 10, 9, 5 ), 'message manager' );
		$this->assertFalse( $permissions->user_can_change_permission_level( 7, 9, 6 ), 'manager of another retreat' );
	}

	public function test_plugin_administrator_can_change_anyone_else() {
		$permissions = $this->permissions();
		$this->assertTrue( $permissions->user_can_change_permission_level( 1, 8, 5 ) );
		$this->assertTrue( $permissions->user_can_change_permission_level( 1, 9, 5 ) );
		$this->assertFalse( $permissions->user_can_change_permission_level( 1, 1, 5 ) );
	}

	public function test_changing_grants_the_new_level_before_revoking_the_old_ones() {
		$this->assertTrue( $this->permissions()->change_permission_level( 10, 5, 'manager', 7 ) );
		$this->assertSame( array( 'grant:10:manager', 'revoke:10:message_manager', 'revoke:10:read_only' ), $this->calls );
	}

	public function test_changing_to_a_level_the_user_has_keeps_only_that_one() {
		$this->assertTrue( $this->permissions()->change_permission_level( 10, 5, 'read_only', 7 ) );
		$this->assertSame( array( 'revoke:10:message_manager' ), $this->calls );
	}

	public function test_only_users_already_assigned_or_valid_levels_can_change() {
		$permissions = $this->permissions();
		$this->assertFalse( $permissions->change_permission_level( 11, 5, 'read_only', 7 ), 'not assigned' );
		$this->assertFalse( $permissions->change_permission_level( 9, 5, 'owner', 7 ), 'invalid level' );
		$this->assertSame( array(), $this->calls );
	}

	public function test_ajax_handler_checks_who_can_change_the_level() {
		$source = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-admin.php' );
		$this->assertStringContainsString( "'wp_ajax_dfxprl_change_permission_level'", $source );

		$start = strpos( $source, 'function ajax_change_permission_level(' );
		$this->assertNotFalse( $start );
		$handler = substr( $source, $start, strpos( $source, "\n\t/**", $start ) - $start );
		$this->assertStringContainsString( "wp_verify_nonce", $handler );
		$this->assertStringContainsString( 'user_can_change_permission_level( $current_user_id, $user_id, $retreat_id )', $handler );
		$this->assertLessThan(
			strpos( $handler, 'change_permission_level( $user_id' ),
			strpos( $handler, 'user_can_change_permission_level(' ),
			'The check must come before the change'
		);

		// The selector is shown only where the change is allowed
		$this->assertStringContainsString( 'user_can_change_permission_level( get_current_user_id(), $permission->user_id, $retreat->id )', $source );
	}
}
