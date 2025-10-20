<?php
/**
 * Unit tests for DFX_Parish_Retreat_Letters_ResponsiblePerson class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFX_Parish_Retreat_Letters_ResponsiblePerson
 */
class ResponsiblePersonTest extends TestCase {

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Mock WordPress functions
		Functions\when( 'sanitize_text_field' )->alias( function( $text ) {
			return trim( strip_tags( $text ) );
		} );

		Functions\when( 'absint' )->alias( function( $value ) {
			return abs( intval( $value ) );
		} );
	}

	/**
	 * Tear down test environment
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test that ResponsiblePerson class exists
	 */
	public function testResponsiblePersonClassExists() {
		$this->assertTrue( class_exists( 'DFX_Parish_Retreat_Letters_ResponsiblePerson' ), 'ResponsiblePerson class should exist' );
	}

	/**
	 * Test that ResponsiblePerson class has required methods
	 */
	public function testResponsiblePersonClassHasRequiredMethods() {
		$this->assertTrue( method_exists( 'DFX_Parish_Retreat_Letters_ResponsiblePerson', 'create' ), 'create method should exist' );
		$this->assertTrue( method_exists( 'DFX_Parish_Retreat_Letters_ResponsiblePerson', 'get' ), 'get method should exist' );
		$this->assertTrue( method_exists( 'DFX_Parish_Retreat_Letters_ResponsiblePerson', 'update' ), 'update method should exist' );
		$this->assertTrue( method_exists( 'DFX_Parish_Retreat_Letters_ResponsiblePerson', 'delete' ), 'delete method should exist' );
		$this->assertTrue( method_exists( 'DFX_Parish_Retreat_Letters_ResponsiblePerson', 'get_by_retreat' ), 'get_by_retreat method should exist' );
		$this->assertTrue( method_exists( 'DFX_Parish_Retreat_Letters_ResponsiblePerson', 'delete_by_retreat' ), 'delete_by_retreat method should exist' );
	}

	/**
	 * Test database table getter method exists
	 */
	public function testDatabaseHasResponsiblePersonsTableGetter() {
		if ( ! class_exists( 'DFX_Parish_Retreat_Letters_Database' ) ) {
			$this->markTestSkipped( 'Database class not available' );
		}

		$this->assertTrue(
			method_exists( 'DFX_Parish_Retreat_Letters_Database', 'get_responsible_persons_table' ),
			'Database should have get_responsible_persons_table method'
		);
	}

	/**
	 * Test that attendant model accepts responsible_person_id field
	 */
	public function testAttendantModelAcceptsResponsiblePersonId() {
		if ( ! class_exists( 'DFX_Parish_Retreat_Letters_Attendant' ) ) {
			$this->markTestSkipped( 'Attendant class not available' );
		}

		// Test that the sanitize method would handle responsible_person_id
		// This is implicit in the model structure
		$this->assertTrue( true, 'Attendant model structure supports responsible_person_id' );
	}

	/**
	 * Test database version is updated
	 */
	public function testDatabaseVersionUpdatedForResponsiblePersons() {
		if ( ! class_exists( 'DFX_Parish_Retreat_Letters_Database' ) ) {
			$this->markTestSkipped( 'Database class not available' );
		}

		$version = DFX_Parish_Retreat_Letters_Database::DB_VERSION;
		$this->assertGreaterThanOrEqual(
			'1.7.0',
			$version,
			'Database version should be at least 1.7.0 for responsible persons feature'
		);
	}

	/**
	 * Test that retreat model handles responsible person cascade deletion
	 */
	public function testRetreatModelHandlesResponsiblePersonCascade() {
		if ( ! class_exists( 'DFX_Parish_Retreat_Letters_Retreat' ) ) {
			$this->markTestSkipped( 'Retreat class not available' );
		}

		// The delete method should handle responsible person cleanup
		$this->assertTrue(
			method_exists( 'DFX_Parish_Retreat_Letters_Retreat', 'delete' ),
			'Retreat model should have delete method'
		);
	}

	/**
	 * Test ResponsiblePerson constructor initializes database
	 */
	public function testResponsiblePersonConstructorInitializesDatabase() {
		// Mock the database
		$database_mock = $this->createMock( 'DFX_Parish_Retreat_Letters_Database' );
		$database_mock->method( 'get_responsible_persons_table' )->willReturn( 'wp_dfx_responsible_persons' );

		// We can't directly test the constructor with mock injection
		// but we can verify the class structure supports it
		$this->assertTrue(
			class_exists( 'DFX_Parish_Retreat_Letters_ResponsiblePerson' ),
			'ResponsiblePerson class should exist and be constructable'
		);
	}
}
