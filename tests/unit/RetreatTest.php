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

    /**
     * Test retreat update functionality
     */
    public function test_update_retreat() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_retreats_table')->willReturn('wp_dfx_retreats');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('update')
             ->willReturn(1);
        
        // Create retreat instance and inject mocked database
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        $reflection = new ReflectionClass($retreat);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($retreat, $database_mock);
        
        $update_data = [
            'name' => 'Updated Retreat Name',
            'location' => 'Updated Location',
            'custom_message' => 'Updated message'
        ];
        
        $result = $retreat->update(1, $update_data);
        $this->assertTrue($result);
    }

    /**
     * Test retreat deletion
     */
    public function test_delete_retreat() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_retreats_table')->willReturn('wp_dfx_retreats');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('delete')
             ->willReturn(1);
        
        // Create retreat instance and inject mocked database
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        $reflection = new ReflectionClass($retreat);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($retreat, $database_mock);
        
        $result = $retreat->delete(1);
        $this->assertTrue($result);
    }

    /**
     * Test get all retreats
     */
    public function test_get_all_retreats() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_retreats_table')->willReturn('wp_dfx_retreats');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $expected_retreats = [
            (object) [
                'id' => 1,
                'name' => 'Summer Retreat',
                'location' => 'Mountain Lodge'
            ],
            (object) [
                'id' => 2,
                'name' => 'Winter Retreat',
                'location' => 'City Center'
            ]
        ];
        
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn($expected_retreats);
        
        // Create retreat instance and inject mocked database
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        $reflection = new ReflectionClass($retreat);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($retreat, $database_mock);
        
        if (method_exists($retreat, 'get_all')) {
            $result = $retreat->get_all();
            $this->assertEquals($expected_retreats, $result);
            $this->assertCount(2, $result);
        } else {
            $this->markTestSkipped('get_all method not found');
        }
    }

    /**
     * Test retreat data validation with invalid dates
     */
    public function test_validate_retreat_data_with_invalid_dates() {
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        $reflection = new ReflectionClass($retreat);
        
        if ($reflection->hasMethod('validate_retreat_data')) {
            $method = $reflection->getMethod('validate_retreat_data');
            $method->setAccessible(true);
            
            // Test with end date before start date
            $invalid_data = [
                'name' => 'Test Retreat',
                'location' => 'Test Location',
                'start_date' => '2024-01-03',
                'end_date' => '2024-01-01', // Before start date
                'custom_message' => 'Test message'
            ];
            
            $this->assertFalse($method->invoke($retreat, $invalid_data));
        } else {
            $this->markTestSkipped('validate_retreat_data method not found');
        }
    }

    /**
     * Test retreat search functionality
     */
    public function test_search_retreats() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_retreats_table')->willReturn('wp_dfx_retreats');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $search_results = [
            (object) [
                'id' => 1,
                'name' => 'Summer Retreat 2024',
                'location' => 'Mountain Lodge'
            ]
        ];
        
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn($search_results);
        
        // Create retreat instance and inject mocked database
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        $reflection = new ReflectionClass($retreat);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($retreat, $database_mock);
        
        if (method_exists($retreat, 'search')) {
            $search_term = 'Summer';
            $results = $retreat->search($search_term);
            
            $this->assertIsArray($results);
            $this->assertCount(1, $results);
            $this->assertStringContains('Summer', $results[0]->name);
        } else {
            $this->markTestSkipped('search method not found');
        }
    }

    /**
     * Test get active retreats
     */
    public function test_get_active_retreats() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_retreats_table')->willReturn('wp_dfx_retreats');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $active_retreats = [
            (object) [
                'id' => 1,
                'name' => 'Current Retreat',
                'start_date' => '2024-06-01',
                'end_date' => '2024-06-03'
            ]
        ];
        
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn($active_retreats);
        
        // Create retreat instance and inject mocked database
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        $reflection = new ReflectionClass($retreat);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($retreat, $database_mock);
        
        if (method_exists($retreat, 'get_active')) {
            $results = $retreat->get_active();
            
            $this->assertIsArray($results);
            $this->assertCount(1, $results);
        } else {
            $this->markTestSkipped('get_active method not found');
        }
    }

    /**
     * Test retreat creation with disclaimer fields
     */
    public function test_create_retreat_with_disclaimer_fields() {
        // Add wp_kses_post mock for disclaimer text sanitization
        Functions\when('wp_kses_post')->alias(function($text) {
            return trim(strip_tags($text, '<p><br><strong><em>'));
        });

        // Mock the database instance  
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_retreats_table')->willReturn('wp_dfx_retreats');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->insert_id = 123;
        
        // Expect insert to be called with disclaimer fields
        $wpdb->expects($this->once())
             ->method('insert')
             ->with(
                 'wp_dfx_retreats',
                 $this->callback(function($data) {
                     return isset($data['disclaimer_text']) && 
                            isset($data['disclaimer_acceptance_text']) &&
                            $data['disclaimer_text'] === 'Legal disclaimer text' &&
                            $data['disclaimer_acceptance_text'] === 'I accept the terms';
                 }),
                 ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
             )
             ->willReturn(true);
        
        // Create retreat instance and inject mocked database
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        $reflection = new ReflectionClass($retreat);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($retreat, $database_mock);
        
        // Test data with disclaimer fields
        $retreat_data = [
            'name' => 'Test Retreat',
            'location' => 'Test Location', 
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-03',
            'custom_message' => 'Test message',
            'disclaimer_text' => 'Legal disclaimer text',
            'disclaimer_acceptance_text' => 'I accept the terms'
        ];
        
        $result = $retreat->create($retreat_data);
        $this->assertEquals(123, $result);
    }

    /**
     * Test retreat creation with empty disclaimer fields (should work)
     */
    public function test_create_retreat_with_empty_disclaimer_fields() {
        // Add wp_kses_post mock
        Functions\when('wp_kses_post')->alias(function($text) {
            return trim(strip_tags($text, '<p><br><strong><em>'));
        });

        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_retreats_table')->willReturn('wp_dfx_retreats');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->insert_id = 124;
        
        // Expect insert to be called with empty disclaimer fields
        $wpdb->expects($this->once())
             ->method('insert')
             ->with(
                 'wp_dfx_retreats',
                 $this->callback(function($data) {
                     return isset($data['disclaimer_text']) && 
                            isset($data['disclaimer_acceptance_text']) &&
                            $data['disclaimer_text'] === '' &&
                            $data['disclaimer_acceptance_text'] === '';
                 }),
                 ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
             )
             ->willReturn(true);
        
        // Create retreat instance and inject mocked database
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        $reflection = new ReflectionClass($retreat);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($retreat, $database_mock);
        
        // Test data without disclaimer fields (should default to empty)
        $retreat_data = [
            'name' => 'Test Retreat',
            'location' => 'Test Location',
            'start_date' => '2024-01-01', 
            'end_date' => '2024-01-03',
            'custom_message' => 'Test message'
        ];
        
        $result = $retreat->create($retreat_data);
        $this->assertEquals(124, $result);
    }
}