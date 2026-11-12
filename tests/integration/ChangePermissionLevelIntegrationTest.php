<?php
/**
 * Changing access levels against a real WordPress database.
 *
 * @package DFXPRL
 */

/**
 * Runs the level change end to end: permission rows, audit log and capabilities.
 */
class ChangePermissionLevelIntegrationTest extends WP_UnitTestCase {

	public function test_manager_changes_the_level_of_a_user_of_their_retreat() {
		global $wpdb;

		$permissions = DFXPRL_Permissions::get_instance();
		$retreat_id  = 4242;
		$manager     = self::factory()->user->create();
		$other       = self::factory()->user->create();
		$helper      = self::factory()->user->create();

		$permissions->grant_permission( $manager, $retreat_id, 'manager', 1 );
		$permissions->grant_permission( $other, $retreat_id, 'manager', 1 );
		$permissions->grant_permission( $helper, $retreat_id, 'message_manager', 1 );
		$permissions->grant_permission( $helper, $retreat_id, 'read_only', 1 );

		$this->assertTrue( $permissions->user_can_change_permission_level( $manager, $helper, $retreat_id ) );
		$this->assertFalse( $permissions->user_can_change_permission_level( $manager, $other, $retreat_id ) );
		$this->assertFalse( $permissions->user_can_change_permission_level( $manager, $manager, $retreat_id ) );
		$this->assertFalse( $permissions->user_can_change_permission_level( $helper, $manager, $retreat_id ) );

		$this->assertTrue( $permissions->change_permission_level( $helper, $retreat_id, 'read_only', $manager ) );
		$this->assertSame( array( 'read_only' ), $permissions->get_user_retreat_levels( $helper, $retreat_id ) );

		$this->assertTrue( $permissions->change_permission_level( $helper, $retreat_id, 'manager', $manager ) );
		$this->assertSame( array( 'manager' ), $permissions->get_user_retreat_levels( $helper, $retreat_id ) );

		// Now a manager, the helper is out of reach for the other managers
		$this->assertFalse( $permissions->user_can_change_permission_level( $manager, $helper, $retreat_id ) );

		// Users who were never assigned are granted access, not changed
		$stranger = self::factory()->user->create();
		$this->assertFalse( $permissions->change_permission_level( $stranger, $retreat_id, 'read_only', $manager ) );
		$this->assertSame( array(), $permissions->get_user_retreat_levels( $stranger, $retreat_id ) );

		// Every step is in the audit log, attributed to the manager
		$log = $permissions->get_permission_audit_log( $retreat_id, 50 );
		$by_manager = array_filter( $log, function( $row ) use ( $helper, $manager ) {
			return (int) $row->user_id === $helper && (int) $row->performed_by === $manager;
		} );
		$this->assertCount( 3, $by_manager, 'revoke message_manager, grant manager, revoke read_only' );
	}

	public function test_plugin_administrator_changes_any_retreat_manager() {
		$permissions = DFXPRL_Permissions::get_instance();
		$admin       = self::factory()->user->create( array( 'role' => 'administrator' ) );
		get_user_by( 'id', $admin )->add_cap( 'manage_retreat_plugin' );
		$manager     = self::factory()->user->create();
		$permissions->grant_permission( $manager, 4243, 'manager', $admin );

		$this->assertTrue( $permissions->user_can_change_permission_level( $admin, $manager, 4243 ) );
		$this->assertTrue( $permissions->change_permission_level( $manager, 4243, 'message_manager', $admin ) );
		$this->assertSame( array( 'message_manager' ), $permissions->get_user_retreat_levels( $manager, 4243 ) );

		// Back to a level the user had before and lost
		$this->assertTrue( $permissions->change_permission_level( $manager, 4243, 'manager', $admin ) );
		$this->assertSame( array( 'manager' ), $permissions->get_user_retreat_levels( $manager, 4243 ) );
		$this->assertTrue( $permissions->change_permission_level( $manager, 4243, 'message_manager', $admin ) );
		$this->assertSame( array( 'message_manager' ), $permissions->get_user_retreat_levels( $manager, 4243 ) );
	}
}
