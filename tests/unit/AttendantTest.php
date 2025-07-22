<?php
/**
 * Unit tests for DFX_Parish_Retreat_Letters_Attendant class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFX_Parish_Retreat_Letters_Attendant
 */
class AttendantTest extends TestCase {

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
        Functions\when('sanitize_email')->alias(function($email) {
            return filter_var($email, FILTER_SANITIZE_EMAIL);
        });
        Functions\when('is_email')->alias(function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
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
     * Test attendant creation with valid data
     */
    public function testCreateAttendantWithValidData() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock the security instance
        $security_mock = $this->createMock('DFX_Parish_Retreat_Letters_Security');
        $security_mock->method('generate_unique_message_token')->willReturn('test_token_123456789');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->insert_id = 456;
        $wpdb->expects($this->once())
             ->method('insert')
             ->willReturn(true);
        
        // Create attendant instance
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        // Use reflection to set the private properties
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);
        
        $valid_data = [
            'retreat_id' => 1,
            'name' => 'John',
            'surnames' => 'Doe',
            'date_of_birth' => '1980-01-01',
            'emergency_contact_name' => 'Jane',
            'emergency_contact_surname' => 'Doe',
            'emergency_contact_phone' => '+1234567890'
        ];
        
        $result = $attendant->create($valid_data);
        $this->assertEquals(456, $result);
    }

    /**
     * Test attendant creation with invalid data
     */
    public function testCreateAttendantWithInvalidData() {
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        $invalid_data = [
            'retreat_id' => '', // Missing required field
            'name' => '', // Empty name
            'surnames' => 'Doe'
        ];
        
        $result = $attendant->create($invalid_data);
        $this->assertFalse($result);
    }

    /**
     * Test get attendant by ID
     */
    public function testGetAttendantById() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $expected_attendant = (object) [
            'id' => 1,
            'retreat_id' => 1,
            'name' => 'John',
            'surnames' => 'Doe',
            'date_of_birth' => '1980-01-01',
            'message_url_token' => 'token123'
        ];
        
        $wpdb->expects($this->once())
             ->method('get_row')
             ->willReturn($expected_attendant);
             
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_dfx_attendants WHERE id = 1");
        
        // Create attendant instance and inject mocked database
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);
        
        $result = $attendant->get(1);
        $this->assertEquals($expected_attendant, $result);
    }

    /**
     * Test get non-existent attendant
     */
    public function testGetNonExistentAttendant() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $wpdb->expects($this->once())
             ->method('get_row')
             ->willReturn(null);
             
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_dfx_attendants WHERE id = 999");
        
        // Create attendant instance and inject mocked database
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);
        
        $result = $attendant->get(999);
        $this->assertNull($result);
    }

    /**
     * Test update attendant
     */
    public function testUpdateAttendant() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('update')
             ->willReturn(1);
        
        // Create attendant instance and inject mocked database
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);
        
        $update_data = [
            'name' => 'Jane',
            'surnames' => 'Smith',
            'emergency_contact_phone' => '+0987654321'
        ];
        
        $result = $attendant->update(1, $update_data);
        $this->assertTrue($result);
    }

    /**
     * Test delete attendant
     */
    public function testDeleteAttendant() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('delete')
             ->willReturn(1);
        
        // Create attendant instance and inject mocked database
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);
        
        $result = $attendant->delete(1);
        $this->assertTrue($result);
    }

    /**
     * Test get attendants by retreat ID
     */
    public function testGetAttendantsByRetreatId() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $expected_attendants = [
            (object) [
                'id' => 1,
                'retreat_id' => 1,
                'name' => 'John',
                'surnames' => 'Doe'
            ],
            (object) [
                'id' => 2,
                'retreat_id' => 1,
                'name' => 'Jane',
                'surnames' => 'Smith'
            ]
        ];
        
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn($expected_attendants);
             
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_dfx_attendants WHERE retreat_id = 1");
        
        // Create attendant instance and inject mocked database
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);
        
        if (method_exists($attendant, 'get_by_retreat_id')) {
            $result = $attendant->get_by_retreat_id(1);
            $this->assertEquals($expected_attendants, $result);
            $this->assertCount(2, $result);
        } else {
            $this->markTestSkipped('get_by_retreat_id method not found');
        }
    }

    /**
     * Test attendant data validation
     */
    public function testAttendantDataValidation() {
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        
        if ($reflection->hasMethod('validate_attendant_data')) {
            $method = $reflection->getMethod('validate_attendant_data');
            $method->setAccessible(true);
            
            // Test valid data
            $valid_data = [
                'retreat_id' => 1,
                'name' => 'John',
                'surnames' => 'Doe',
                'date_of_birth' => '1980-01-01',
                'emergency_contact_name' => 'Jane',
                'emergency_contact_surname' => 'Doe',
                'emergency_contact_phone' => '+1234567890'
            ];
            
            $this->assertTrue($method->invoke($attendant, $valid_data));
            
            // Test invalid data - missing required fields
            $invalid_data = [
                'name' => 'John'
                // Missing other required fields
            ];
            
            $this->assertFalse($method->invoke($attendant, $invalid_data));
            
            // Test invalid date format
            $invalid_date_data = [
                'retreat_id' => 1,
                'name' => 'John',
                'surnames' => 'Doe',
                'date_of_birth' => 'invalid-date',
                'emergency_contact_name' => 'Jane',
                'emergency_contact_surname' => 'Doe',
                'emergency_contact_phone' => '+1234567890'
            ];
            
            $this->assertFalse($method->invoke($attendant, $invalid_date_data));
        } else {
            $this->markTestSkipped('validate_attendant_data method not found');
        }
    }

    /**
     * Test attendant data sanitization
     */
    public function testAttendantDataSanitization() {
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        
        if ($reflection->hasMethod('sanitize_attendant_data')) {
            $method = $reflection->getMethod('sanitize_attendant_data');
            $method->setAccessible(true);
            
            $dirty_data = [
                'retreat_id' => '1',
                'name' => '<script>alert("xss")</script>John',
                'surnames' => '  Doe  ',
                'date_of_birth' => '1980-01-01',
                'emergency_contact_name' => 'Jane<>',
                'emergency_contact_surname' => 'Doe',
                'emergency_contact_phone' => '+1234567890'
            ];
            
            $sanitized = $method->invoke($attendant, $dirty_data);
            
            $this->assertEquals(1, $sanitized['retreat_id']);
            $this->assertEquals('John', $sanitized['name']); // Script tags removed
            $this->assertEquals('Doe', $sanitized['surnames']); // Trimmed
            $this->assertEquals('Jane', $sanitized['emergency_contact_name']); // HTML chars removed
        } else {
            $this->markTestSkipped('sanitize_attendant_data method not found');
        }
    }

    /**
     * Test get attendant by message token
     */
    public function testGetAttendantByMessageToken() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $expected_attendant = (object) [
            'id' => 1,
            'name' => 'John',
            'surnames' => 'Doe',
            'message_url_token' => 'test_token_123'
        ];
        
        $wpdb->expects($this->once())
             ->method('get_row')
             ->willReturn($expected_attendant);
             
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_dfx_attendants WHERE message_url_token = 'test_token_123'");
        
        // Create attendant instance and inject mocked database
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);
        
        if (method_exists($attendant, 'get_by_message_token')) {
            $result = $attendant->get_by_message_token('test_token_123');
            $this->assertEquals($expected_attendant, $result);
        } else {
            $this->markTestSkipped('get_by_message_token method not found');
        }
    }

    /**
     * Test attendant constructor initializes dependencies
     */
    public function testConstructorInitializesDependencies() {
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_value = $database_property->getValue($attendant);
        
        $this->assertNotNull($database_value);
    }

    /**
     * Test age calculation functionality
     */
    public function testAgeCalculation() {
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        if (method_exists($attendant, 'calculate_age')) {
            $birth_date = '1990-01-01';
            $age = $attendant->calculate_age($birth_date);
            
            $this->assertIsInt($age);
            $this->assertGreaterThan(0, $age);
            $this->assertLessThan(150, $age); // Reasonable age limit
            
            // Test with specific date
            $current_date = '2024-01-01';
            $age_specific = $attendant->calculate_age($birth_date, $current_date);
            $this->assertEquals(34, $age_specific);
        } else {
            $this->markTestSkipped('calculate_age method not found');
        }
    }

    /**
     * Test search functionality
     */
    public function testSearchAttendants() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $expected_results = [
            (object) [
                'id' => 1,
                'name' => 'John',
                'surnames' => 'Doe'
            ]
        ];
        
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn($expected_results);
        
        // Create attendant instance and inject mocked database
        $attendant = new DFX_Parish_Retreat_Letters_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);
        
        if (method_exists($attendant, 'search')) {
            $search_term = 'John';
            $results = $attendant->search($search_term);
            
            $this->assertIsArray($results);
            $this->assertCount(1, $results);
            $this->assertEquals('John', $results[0]->name);
        } else {
            $this->markTestSkipped('search method not found');
        }
    }
}