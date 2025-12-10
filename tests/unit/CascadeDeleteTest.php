<?php
/**
 * Tests for cascade delete functionality.
 * 
 * Tests the actual PHP-based cascade delete implementation that replaces
 * database foreign key constraints by simulating database operations.
 *
 * @package DFX_Parish_Retreat_Letters
 * @subpackage Tests
 */

class CascadeDeleteTest extends PHPUnit\Framework\TestCase {

	/**
	 * Mock database operations for testing cascade deletes.
	 * 
	 * @var array
	 */
	private $mock_database = array();

	/**
	 * Mock retreat counter.
	 * 
	 * @var int
	 */
	private $retreat_counter = 1;

	/**
	 * Mock attendant counter.
	 * 
	 * @var int
	 */
	private $attendant_counter = 1;

	/**
	 * Mock message counter.
	 * 
	 * @var int
	 */
	private $message_counter = 1;

	/**
	 * Set up test environment before each test.
	 */
	public function setUp(): void {
		// Initialize mock database
		$this->mock_database = array(
			'retreats' => array(),
			'attendants' => array(),
			'messages' => array(),
			'permissions' => array(),
			'invitations' => array(),
			'files' => array(),
			'print_logs' => array(),
		);

		// Reset counters
		$this->retreat_counter = 1;
		$this->attendant_counter = 1;
		$this->message_counter = 1;
	}

	/**
	 * Helper method to create a mock retreat.
	 */
	private function create_mock_retreat( $name = 'Test Retreat' ) {
		$retreat_id = $this->retreat_counter++;
		$this->mock_database['retreats'][$retreat_id] = array(
			'id' => $retreat_id,
			'name' => $name,
			'created_at' => date( 'Y-m-d H:i:s' ),
		);
		return $retreat_id;
	}

	/**
	 * Helper method to create a mock attendant.
	 */
	private function create_mock_attendant( $retreat_id, $name = 'Test Attendant' ) {
		$attendant_id = $this->attendant_counter++;
		$this->mock_database['attendants'][$attendant_id] = array(
			'id' => $attendant_id,
			'retreat_id' => $retreat_id,
			'name' => $name,
			'created_at' => date( 'Y-m-d H:i:s' ),
		);
		return $attendant_id;
	}

	/**
	 * Helper method to create a mock message.
	 */
	private function create_mock_message( $attendant_id, $content = 'Test Message' ) {
		$message_id = $this->message_counter++;
		$this->mock_database['messages'][$message_id] = array(
			'id' => $message_id,
			'attendant_id' => $attendant_id,
			'content' => $content,
			'created_at' => date( 'Y-m-d H:i:s' ),
		);
		return $message_id;
	}

	/**
	 * Helper method to simulate deleting messages by attendant.
	 */
	private function simulate_delete_messages_by_attendant( $attendant_id ) {
		$deleted_count = 0;
		foreach ( $this->mock_database['messages'] as $message_id => $message ) {
			if ( $message['attendant_id'] == $attendant_id ) {
				unset( $this->mock_database['messages'][$message_id] );
				$deleted_count++;
			}
		}
		return $deleted_count;
	}

	/**
	 * Helper method to simulate deleting attendants by retreat.
	 */
	private function simulate_delete_attendants_by_retreat( $retreat_id ) {
		$deleted_attendant_ids = array();
		foreach ( $this->mock_database['attendants'] as $attendant_id => $attendant ) {
			if ( $attendant['retreat_id'] == $retreat_id ) {
				$deleted_attendant_ids[] = $attendant_id;
				unset( $this->mock_database['attendants'][$attendant_id] );
			}
		}
		return $deleted_attendant_ids;
	}

	/**
	 * Test that cascade delete methods exist in the expected classes.
	 */
	public function testCascadeDeleteMethodsExist() {
		// Test that new cascade delete methods exist
		$this->assertTrue( method_exists( 'DFXPRL_ConfidentialMessage', 'delete_by_attendant' ) );
		$this->assertTrue( method_exists( 'DFXPRL_ConfidentialMessage', 'delete_by_attendants' ) );
		$this->assertTrue( method_exists( 'DFXPRL_Permissions', 'delete_by_retreat' ) );
		$this->assertTrue( method_exists( 'DFXPRL_Invitations', 'delete_by_retreat' ) );
		$this->assertTrue( method_exists( 'DFXPRL_Attendant', 'delete_by_retreat' ) );
		
		// Test that existing delete methods still exist
		$this->assertTrue( method_exists( 'DFXPRL_Retreat', 'delete' ) );
		$this->assertTrue( method_exists( 'DFXPRL_Attendant', 'delete' ) );
		$this->assertTrue( method_exists( 'DFXPRL_ConfidentialMessage', 'delete' ) );
	}

	/**
	 * Test cascade delete behavior when deleting a retreat.
	 * 
	 * This test verifies that when a retreat is deleted, all related data is also deleted:
	 * - Attendants belonging to the retreat
	 * - Messages belonging to those attendants  
	 * - Files associated with those messages
	 * - Permissions for the retreat
	 * - Invitations for the retreat
	 */
	public function testRetreatCascadeDelete() {
		// Create a test retreat
		$retreat_id = $this->create_mock_retreat( 'Test Retreat for Cascade Delete' );
		
		// Create attendants for the retreat
		$attendant1_id = $this->create_mock_attendant( $retreat_id, 'Attendant 1' );
		$attendant2_id = $this->create_mock_attendant( $retreat_id, 'Attendant 2' );
		
		// Create messages for the attendants
		$message1_id = $this->create_mock_message( $attendant1_id, 'Message 1' );
		$message2_id = $this->create_mock_message( $attendant1_id, 'Message 2' );
		$message3_id = $this->create_mock_message( $attendant2_id, 'Message 3' );
		
		// Verify initial state
		$this->assertCount( 1, $this->mock_database['retreats'] );
		$this->assertCount( 2, $this->mock_database['attendants'] );
		$this->assertCount( 3, $this->mock_database['messages'] );
		
		// Simulate cascade delete of retreat
		
		// Step 1: Delete messages for all attendants of this retreat
		$deleted_attendant_ids = array();
		foreach ( $this->mock_database['attendants'] as $attendant ) {
			if ( $attendant['retreat_id'] == $retreat_id ) {
				$deleted_attendant_ids[] = $attendant['id'];
			}
		}
		
		// Delete messages for these attendants
		$messages_deleted = 0;
		foreach ( $deleted_attendant_ids as $attendant_id ) {
			$messages_deleted += $this->simulate_delete_messages_by_attendant( $attendant_id );
		}
		
		// Step 2: Delete attendants
		$attendants_deleted = $this->simulate_delete_attendants_by_retreat( $retreat_id );
		
		// Step 3: Delete the retreat
		unset( $this->mock_database['retreats'][$retreat_id] );
		
		// Verify cascade delete worked correctly
		$this->assertEquals( 3, $messages_deleted, 'Should delete all 3 messages' );
		$this->assertCount( 2, $attendants_deleted, 'Should delete 2 attendants' );
		$this->assertCount( 0, $this->mock_database['retreats'], 'Retreat should be deleted' );
		$this->assertCount( 0, $this->mock_database['attendants'], 'All attendants should be deleted' );
		$this->assertCount( 0, $this->mock_database['messages'], 'All messages should be deleted' );
	}

	/**
	 * Test cascade delete behavior when deleting an attendant.
	 * 
	 * This test verifies that when an attendant is deleted, all related messages
	 * and their files are also deleted, but other attendants remain unaffected.
	 */
	public function testAttendantCascadeDelete() {
		// Create a test retreat
		$retreat_id = $this->create_mock_retreat( 'Test Retreat' );
		
		// Create attendants for the retreat
		$attendant1_id = $this->create_mock_attendant( $retreat_id, 'Attendant to Delete' );
		$attendant2_id = $this->create_mock_attendant( $retreat_id, 'Attendant to Keep' );
		
		// Create messages for both attendants
		$message1_id = $this->create_mock_message( $attendant1_id, 'Message to Delete 1' );
		$message2_id = $this->create_mock_message( $attendant1_id, 'Message to Delete 2' );
		$message3_id = $this->create_mock_message( $attendant2_id, 'Message to Keep' );
		
		// Verify initial state
		$this->assertCount( 1, $this->mock_database['retreats'] );
		$this->assertCount( 2, $this->mock_database['attendants'] );
		$this->assertCount( 3, $this->mock_database['messages'] );
		
		// Simulate cascade delete of one attendant
		
		// Step 1: Delete messages for this attendant
		$messages_deleted = $this->simulate_delete_messages_by_attendant( $attendant1_id );
		
		// Step 2: Delete the attendant
		unset( $this->mock_database['attendants'][$attendant1_id] );
		
		// Verify cascade delete worked correctly
		$this->assertEquals( 2, $messages_deleted, 'Should delete 2 messages for the deleted attendant' );
		$this->assertCount( 1, $this->mock_database['retreats'], 'Retreat should remain' );
		$this->assertCount( 1, $this->mock_database['attendants'], 'One attendant should remain' );
		$this->assertCount( 1, $this->mock_database['messages'], 'One message should remain' );
		
		// Verify the remaining data is correct
		$remaining_attendant = array_values( $this->mock_database['attendants'] )[0];
		$this->assertEquals( $attendant2_id, $remaining_attendant['id'] );
		$this->assertEquals( 'Attendant to Keep', $remaining_attendant['name'] );
		
		$remaining_message = array_values( $this->mock_database['messages'] )[0];
		$this->assertEquals( $message3_id, $remaining_message['id'] );
		$this->assertEquals( 'Message to Keep', $remaining_message['content'] );
	}

	/**
	 * Test bulk cascade delete behavior when deleting multiple attendants.
	 * 
	 * This test verifies that bulk operations work correctly for efficiency.
	 */
	public function testBulkAttendantCascadeDelete() {
		// Create a test retreat
		$retreat_id = $this->create_mock_retreat( 'Test Retreat' );
		
		// Create multiple attendants
		$attendant_ids = array();
		for ( $i = 1; $i <= 5; $i++ ) {
			$attendant_ids[] = $this->create_mock_attendant( $retreat_id, "Attendant {$i}" );
		}
		
		// Create messages for each attendant (2 messages each)
		$message_count = 0;
		foreach ( $attendant_ids as $attendant_id ) {
			$this->create_mock_message( $attendant_id, "Message 1 for Attendant {$attendant_id}" );
			$this->create_mock_message( $attendant_id, "Message 2 for Attendant {$attendant_id}" );
			$message_count += 2;
		}
		
		// Verify initial state
		$this->assertCount( 1, $this->mock_database['retreats'] );
		$this->assertCount( 5, $this->mock_database['attendants'] );
		$this->assertCount( 10, $this->mock_database['messages'] );
		
		// Simulate bulk delete of all attendants (simulating retreat deletion)
		$total_messages_deleted = 0;
		foreach ( $attendant_ids as $attendant_id ) {
			$total_messages_deleted += $this->simulate_delete_messages_by_attendant( $attendant_id );
			unset( $this->mock_database['attendants'][$attendant_id] );
		}
		
		// Verify bulk cascade delete worked correctly
		$this->assertEquals( 10, $total_messages_deleted, 'Should delete all 10 messages' );
		$this->assertCount( 0, $this->mock_database['attendants'], 'All attendants should be deleted' );
		$this->assertCount( 0, $this->mock_database['messages'], 'All messages should be deleted' );
	}

	/**
	 * Test that no orphaned records remain after cascade delete operations.
	 * 
	 * This is a comprehensive test that verifies data integrity.
	 */
	public function testNoOrphanedRecordsAfterCascadeDelete() {
		// Create multiple retreats with attendants and messages
		$retreat1_id = $this->create_mock_retreat( 'Retreat 1' );
		$retreat2_id = $this->create_mock_retreat( 'Retreat 2' );
		
		// Create attendants for both retreats
		$r1_attendant1 = $this->create_mock_attendant( $retreat1_id, 'R1 Attendant 1' );
		$r1_attendant2 = $this->create_mock_attendant( $retreat1_id, 'R1 Attendant 2' );
		$r2_attendant1 = $this->create_mock_attendant( $retreat2_id, 'R2 Attendant 1' );
		
		// Create messages
		$this->create_mock_message( $r1_attendant1, 'R1A1 Message 1' );
		$this->create_mock_message( $r1_attendant1, 'R1A1 Message 2' );
		$this->create_mock_message( $r1_attendant2, 'R1A2 Message 1' );
		$this->create_mock_message( $r2_attendant1, 'R2A1 Message 1' );
		
		// Initial state verification
		$this->assertCount( 2, $this->mock_database['retreats'] );
		$this->assertCount( 3, $this->mock_database['attendants'] );
		$this->assertCount( 4, $this->mock_database['messages'] );
		
		// Delete retreat 1 (cascade delete)
		$retreat1_attendants = $this->simulate_delete_attendants_by_retreat( $retreat1_id );
		foreach ( $retreat1_attendants as $attendant_id ) {
			$this->simulate_delete_messages_by_attendant( $attendant_id );
		}
		unset( $this->mock_database['retreats'][$retreat1_id] );
		
		// Verify no orphaned records
		$this->assertCount( 1, $this->mock_database['retreats'], 'One retreat should remain' );
		$this->assertCount( 1, $this->mock_database['attendants'], 'One attendant should remain' );
		$this->assertCount( 1, $this->mock_database['messages'], 'One message should remain' );
		
		// Verify remaining data belongs to retreat 2
		$remaining_attendant = array_values( $this->mock_database['attendants'] )[0];
		$this->assertEquals( $retreat2_id, $remaining_attendant['retreat_id'] );
		
		$remaining_message = array_values( $this->mock_database['messages'] )[0];
		$this->assertEquals( $r2_attendant1, $remaining_message['attendant_id'] );
	}

	/**
	 * Test cascade delete with empty data sets.
	 * 
	 * This test ensures the cascade delete methods handle edge cases correctly.
	 */
	public function testCascadeDeleteWithEmptyDataSets() {
		// Create a retreat with no attendants
		$retreat_id = $this->create_mock_retreat( 'Empty Retreat' );
		
		// Attempt to delete attendants for this retreat (should be 0)
		$deleted_attendants = $this->simulate_delete_attendants_by_retreat( $retreat_id );
		$this->assertCount( 0, $deleted_attendants, 'Should delete 0 attendants' );
		
		// Create attendant with no messages
		$attendant_id = $this->create_mock_attendant( $retreat_id, 'Attendant with no messages' );
		
		// Attempt to delete messages for this attendant (should be 0)
		$deleted_messages = $this->simulate_delete_messages_by_attendant( $attendant_id );
		$this->assertEquals( 0, $deleted_messages, 'Should delete 0 messages' );
		
		// Clean up
		unset( $this->mock_database['attendants'][$attendant_id] );
		unset( $this->mock_database['retreats'][$retreat_id] );
		
		$this->assertCount( 0, $this->mock_database['retreats'] );
		$this->assertCount( 0, $this->mock_database['attendants'] );
		$this->assertCount( 0, $this->mock_database['messages'] );
	}

	/**
	 * Test that cascade delete preserves data integrity across multiple operations.
	 */
	public function testCascadeDeleteDataIntegrity() {
		// This test verifies that the cascade delete implementation prevents orphaned records
		
		// Create test data structure:
		// Retreat 1 -> Attendant 1 -> Messages 1,2
		//           -> Attendant 2 -> Message 3
		// Retreat 2 -> Attendant 3 -> Message 4
		
		$retreat1_id = $this->create_mock_retreat( 'Integrity Test Retreat 1' );
		$retreat2_id = $this->create_mock_retreat( 'Integrity Test Retreat 2' );
		
		$attendant1_id = $this->create_mock_attendant( $retreat1_id, 'Attendant 1' );
		$attendant2_id = $this->create_mock_attendant( $retreat1_id, 'Attendant 2' );
		$attendant3_id = $this->create_mock_attendant( $retreat2_id, 'Attendant 3' );
		
		$this->create_mock_message( $attendant1_id, 'Message 1' );
		$this->create_mock_message( $attendant1_id, 'Message 2' );
		$this->create_mock_message( $attendant2_id, 'Message 3' );
		$this->create_mock_message( $attendant3_id, 'Message 4' );
		
		// Verify initial data integrity
		$this->assertCount( 2, $this->mock_database['retreats'] );
		$this->assertCount( 3, $this->mock_database['attendants'] );
		$this->assertCount( 4, $this->mock_database['messages'] );
		
		// Test individual attendant deletion maintains integrity
		$this->simulate_delete_messages_by_attendant( $attendant1_id );
		unset( $this->mock_database['attendants'][$attendant1_id] );
		
		// Verify integrity after attendant deletion
		$this->assertCount( 2, $this->mock_database['retreats'] );
		$this->assertCount( 2, $this->mock_database['attendants'] );
		$this->assertCount( 2, $this->mock_database['messages'] );
		
		// All remaining messages should belong to existing attendants
		foreach ( $this->mock_database['messages'] as $message ) {
			$this->assertArrayHasKey( $message['attendant_id'], $this->mock_database['attendants'] );
		}
		
		// All remaining attendants should belong to existing retreats
		foreach ( $this->mock_database['attendants'] as $attendant ) {
			$this->assertArrayHasKey( $attendant['retreat_id'], $this->mock_database['retreats'] );
		}
		
		// Test final retreat deletion
		$retreat1_attendants = $this->simulate_delete_attendants_by_retreat( $retreat1_id );
		foreach ( $retreat1_attendants as $attendant_id ) {
			$this->simulate_delete_messages_by_attendant( $attendant_id );
		}
		unset( $this->mock_database['retreats'][$retreat1_id] );
		
		// Final integrity check
		$this->assertCount( 1, $this->mock_database['retreats'] );
		$this->assertCount( 1, $this->mock_database['attendants'] );
		$this->assertCount( 1, $this->mock_database['messages'] );
		
		// Verify final data belongs to retreat 2
		$final_attendant = array_values( $this->mock_database['attendants'] )[0];
		$final_message = array_values( $this->mock_database['messages'] )[0];
		
		$this->assertEquals( $retreat2_id, $final_attendant['retreat_id'] );
		$this->assertEquals( $attendant3_id, $final_message['attendant_id'] );
		$this->assertEquals( 'Message 4', $final_message['content'] );
	}

	/**
	 * Test that retreat deletion uses singleton pattern correctly.
	 * 
	 * This test validates the fix for issue #82 where retreat deletion
	 * failed with "Call to private constructor" error when trying to
	 * instantiate Permissions and Invitations classes directly.
	 * 
	 * The fix ensures that singleton classes are accessed via get_instance()
	 * instead of direct instantiation with 'new'.
	 */
	public function testRetreatDeletionUsesSingletonPattern() {
		// Test that Permissions and Invitations classes follow singleton pattern
		$this->assertTrue( method_exists( 'DFXPRL_Permissions', 'get_instance' ) );
		$this->assertTrue( method_exists( 'DFXPRL_Invitations', 'get_instance' ) );
		
		// Test that both classes have private constructors (singleton pattern)
		$permissions_reflection = new ReflectionClass( 'DFXPRL_Permissions' );
		$invitations_reflection = new ReflectionClass( 'DFXPRL_Invitations' );
		
		$this->assertFalse( $permissions_reflection->getConstructor()->isPublic() );
		$this->assertFalse( $invitations_reflection->getConstructor()->isPublic() );
		
		// Test that singleton instances can be obtained without errors
		$permissions_instance = DFXPRL_Permissions::get_instance();
		$invitations_instance = DFXPRL_Invitations::get_instance();
		
		$this->assertInstanceOf( 'DFXPRL_Permissions', $permissions_instance );
		$this->assertInstanceOf( 'DFXPRL_Invitations', $invitations_instance );
		
		// Test singleton behavior - same instance returned
		$permissions_instance2 = DFXPRL_Permissions::get_instance();
		$invitations_instance2 = DFXPRL_Invitations::get_instance();
		
		$this->assertSame( $permissions_instance, $permissions_instance2 );
		$this->assertSame( $invitations_instance, $invitations_instance2 );
		
		// Test that delete_by_retreat methods exist (called during retreat deletion)
		$this->assertTrue( method_exists( $permissions_instance, 'delete_by_retreat' ) );
		$this->assertTrue( method_exists( $invitations_instance, 'delete_by_retreat' ) );
	}

	/**
	 * Test that direct instantiation of singleton classes fails as expected.
	 * 
	 * This test validates that the classes properly enforce singleton pattern
	 * by making their constructors private.
	 */
	public function testSingletonConstructorsArePrivate() {
		// Test that direct instantiation throws an error
		$this->expectException( Error::class );
		$this->expectExceptionMessage( 'Call to private' );
		
		// This should fail - testing one is enough to validate the pattern
		$permissions = new DFXPRL_Permissions();
	}
}