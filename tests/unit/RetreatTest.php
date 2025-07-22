<?php
/**
 * Unit tests for DFX_Parish_Retreat_Letters_Retreat class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFX_Parish_Retreat_Letters_Retreat
 */
class RetreatTest extends TestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('sanitize_text_field')->alias(function($text) {
            return trim(strip_tags($text));
        });
        Functions\when('sanitize_textarea_field')->alias(function($text) {
            return trim(strip_tags($text));
        });
    }

    /**
     * Tear down test environment
     */
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test retreat creation with valid data
     */
    public function test_create_retreat_with_valid_data() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_retreats_table')->willReturn('wp_dfx_retreats');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->insert_id = 123;
        $wpdb->expects($this->once())
             ->method('insert')
             ->willReturn(true);
        
        // Create retreat instance and inject mocked database
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        // Use reflection to set the private database property
        $reflection = new ReflectionClass($retreat);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($retreat, $database_mock);
        
        $valid_data = [
            'name' => 'Test Retreat',
            'location' => 'Test Location',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-03',
            'custom_message' => 'Test message'
        ];
        
        $result = $retreat->create($valid_data);
        $this->assertEquals(123, $result);
    }

    /**
     * Test retreat constructor initializes database
     */
    public function test_constructor_initializes_database() {
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        $reflection = new ReflectionClass($retreat);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_value = $database_property->getValue($retreat);
        
        $this->assertNotNull($database_value);
    }

    /**
     * Test get method returns retreat data
     */
    public function test_get_returns_retreat_data() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_retreats_table')->willReturn('wp_dfx_retreats');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $expected_retreat = (object) [
            'id' => 1,
            'name' => 'Test Retreat',
            'location' => 'Test Location'
        ];
        
        $wpdb->expects($this->once())
             ->method('get_row')
             ->willReturn($expected_retreat);
             
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_dfx_retreats WHERE id = 1");
        
        // Create retreat instance and inject mocked database
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        // Use reflection to set the private database property
        $reflection = new ReflectionClass($retreat);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($retreat, $database_mock);
        
        $result = $retreat->get(1);
        $this->assertEquals($expected_retreat, $result);
    }
}