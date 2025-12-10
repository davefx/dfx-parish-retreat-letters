<?php
/**
 * Unit tests for DFXPRL_Attendant class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFXPRL_Attendant
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
        $database_mock = $this->createMock('DFXPRL_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock the security instance
        $security_mock = $this->createMock('DFXPRL_Security');
        $security_mock->method('generate_unique_message_token')->willReturn('test_token_123456789');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->insert_id = 456;
        $wpdb->expects($this->once())
             ->method('insert')
             ->willReturn(true);
        
        // Create attendant instance
        $attendant = new DFXPRL_Attendant();
        
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
        $attendant = new DFXPRL_Attendant();
        
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
        $database_mock = $this->createMock('DFXPRL_Database');
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
        $attendant = new DFXPRL_Attendant();
        
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
        $database_mock = $this->createMock('DFXPRL_Database');
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
        $attendant = new DFXPRL_Attendant();
        
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
        $database_mock = $this->createMock('DFXPRL_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('update')
             ->willReturn(1);
        
        // Create attendant instance and inject mocked database
        $attendant = new DFXPRL_Attendant();
        
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
        $database_mock = $this->createMock('DFXPRL_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('delete')
             ->willReturn(1);
        
        // Create attendant instance and inject mocked database
        $attendant = new DFXPRL_Attendant();
        
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
        $database_mock = $this->createMock('DFXPRL_Database');
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
        $attendant = new DFXPRL_Attendant();
        
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
        $attendant = new DFXPRL_Attendant();
        
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
        $attendant = new DFXPRL_Attendant();
        
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
        $database_mock = $this->createMock('DFXPRL_Database');
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
        $attendant = new DFXPRL_Attendant();
        
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
        $attendant = new DFXPRL_Attendant();
        
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
        $attendant = new DFXPRL_Attendant();
        
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
        $database_mock = $this->createMock('DFXPRL_Database');
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
        $attendant = new DFXPRL_Attendant();
        
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

    /**
     * Test CSV export includes message URL
     */
    public function testExportCsvDataIncludesMessageUrl() {
        // Mock the database instance
        $database_mock = $this->createMock('DFXPRL_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock the retreat model
        $retreat_mock = $this->createMock('DFXPRL_Retreat');
        
        // Create a mock retreat object with notes_enabled
        $retreat_obj = (object) [
            'id' => 1,
            'name' => 'Test Retreat',
            'notes_enabled' => false
        ];
        
        $retreat_mock->method('get')->willReturn($retreat_obj);
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        // Create mock attendants with message_url_token
        $mock_attendants = [
            (object) [
                'id' => 1,
                'retreat_id' => 1,
                'name' => 'John',
                'surnames' => 'Doe',
                'date_of_birth' => '1980-01-01',
                'emergency_contact_name' => 'Jane',
                'emergency_contact_surname' => 'Doe',
                'emergency_contact_phone' => '+1234567890',
                'emergency_contact_email' => 'jane@example.com',
                'message_url_token' => 'test_token_123',
                'notes' => ''
            ],
            (object) [
                'id' => 2,
                'retreat_id' => 1,
                'name' => 'Alice',
                'surnames' => 'Smith',
                'date_of_birth' => '1985-05-15',
                'emergency_contact_name' => 'Bob',
                'emergency_contact_surname' => 'Smith',
                'emergency_contact_phone' => '+0987654321',
                'emergency_contact_email' => 'bob@example.com',
                'message_url_token' => '',  // Empty token
                'notes' => ''
            ]
        ];
        
        $wpdb->method('get_results')->willReturn($mock_attendants);
        $wpdb->method('prepare')->willReturn('SELECT * FROM wp_dfx_attendants WHERE retreat_id = 1');
        
        // Mock home_url function
        Functions\when('home_url')->alias(function($path) {
            return 'https://example.com' . $path;
        });
        
        // Mock wp_parse_args function
        Functions\when('wp_parse_args')->alias(function($args, $defaults) {
            return array_merge($defaults, $args);
        });
        
        // Create attendant instance and inject mocked database
        $attendant = new DFXPRL_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);
        
        // Call export_csv_data
        $csv_data = $attendant->export_csv_data(1);
        
        // Verify headers include Message URL
        $this->assertIsArray($csv_data);
        $this->assertArrayHasKey('headers', $csv_data);
        $this->assertArrayHasKey('rows', $csv_data);
        $this->assertContains('Message URL', $csv_data['headers']);
        
        // Verify Message URL is in the correct position (after Emergency Contact Email)
        $header_index = array_search('Message URL', $csv_data['headers']);
        $this->assertNotFalse($header_index, 'Message URL header should be present');
        
        // Verify rows contain message URLs
        $this->assertCount(2, $csv_data['rows']);
        
        // First attendant has a token, should have a full URL
        $first_row = $csv_data['rows'][0];
        $this->assertEquals('https://example.com/messages/test_token_123', $first_row[$header_index]);
        
        // Second attendant has no token, should have empty string
        $second_row = $csv_data['rows'][1];
        $this->assertEquals('', $second_row[$header_index]);
    }

    /**
     * Test that new optional fields are properly sanitized
     */
    public function testSanitizeNewOptionalFields() {
        $attendant = new DFXPRL_Attendant();
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($attendant);
        $sanitize_method = $reflection->getMethod('sanitize_attendant_data');
        $sanitize_method->setAccessible(true);
        
        $test_data = [
            'retreat_id' => '1',
            'name' => 'John',
            'surnames' => 'Doe',
            'date_of_birth' => '1980-01-01',
            'emergency_contact_name' => 'Jane',
            'emergency_contact_surname' => 'Doe',
            'emergency_contact_phone' => '+1234567890',
            'emergency_contact_email' => 'jane@example.com',
            'emergency_contact_relationship' => 'Wife  ',  // Extra spaces
            'invited_by' => '  John Smith',  // Leading spaces
            'incompatibilities' => "Mary Johnson\nBob Williams",  // Multiline text
            'notes' => 'Some notes',
            'internal_notes' => 'Internal notes only  '  // With trailing spaces
        ];
        
        $result = $sanitize_method->invoke($attendant, $test_data);
        
        // Verify new fields are properly sanitized
        $this->assertEquals('Wife', $result['emergency_contact_relationship'], 'Relationship should be trimmed');
        $this->assertEquals('John Smith', $result['invited_by'], 'Invited by should be trimmed');
        $this->assertIsString($result['incompatibilities'], 'Incompatibilities should be a string');
        $this->assertStringContainsString('Mary Johnson', $result['incompatibilities'], 'Incompatibilities should contain the text');
        $this->assertEquals('Internal notes only', $result['internal_notes'], 'Internal notes should be trimmed');
        
        // Verify these fields are present even when empty
        $empty_data = [
            'retreat_id' => '1',
            'name' => 'John',
            'surnames' => 'Doe',
            'date_of_birth' => '1980-01-01',
            'emergency_contact_name' => 'Jane',
            'emergency_contact_surname' => 'Doe',
            'emergency_contact_phone' => '+1234567890',
        ];
        
        $empty_result = $sanitize_method->invoke($attendant, $empty_data);
        $this->assertArrayHasKey('emergency_contact_relationship', $empty_result);
        $this->assertArrayHasKey('invited_by', $empty_result);
        $this->assertArrayHasKey('incompatibilities', $empty_result);
        $this->assertArrayHasKey('internal_notes', $empty_result);
        $this->assertEquals('', $empty_result['emergency_contact_relationship']);
        $this->assertEquals('', $empty_result['invited_by']);
        $this->assertEquals('', $empty_result['incompatibilities']);
        $this->assertEquals('', $empty_result['internal_notes']);
    }

    /**
     * Test that internal_notes field is NOT included in CSV export
     */
    public function testInternalNotesNotExportedInCSV() {
        // Mock translate function
        Functions\when('__')->alias(function($text) {
            return $text;
        });
        
        // Mock database
        $database_mock = $this->createMock('DFXPRL_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        // Mock retreat with both notes and internal_notes enabled
        $mock_retreat = (object)[
            'id' => 1,
            'notes_enabled' => true,
            'internal_notes_enabled' => true
        ];
        
        // Mock attendant with both notes and internal_notes
        $mock_attendants = [
            (object)[
                'name' => 'John',
                'surnames' => 'Doe',
                'date_of_birth' => '1980-01-01',
                'emergency_contact_name' => 'Jane',
                'emergency_contact_surname' => 'Doe',
                'emergency_contact_phone' => '+1234567890',
                'emergency_contact_email' => 'jane@example.com',
                'emergency_contact_relationship' => 'Wife',
                'invited_by' => 'Bob',
                'incompatibilities' => '',
                'message_url_token' => 'test_token',
                'notes' => 'This should be exported',
                'internal_notes' => 'This should NOT be exported'
            ]
        ];
        
        $wpdb->method('get_results')->willReturn($mock_attendants);
        $wpdb->method('prepare')->willReturn('SELECT * FROM wp_dfx_attendants');
        
        // Mock home_url
        Functions\when('home_url')->alias(function($path) {
            return 'https://example.com' . $path;
        });
        
        // Mock wp_parse_args
        Functions\when('wp_parse_args')->alias(function($args, $defaults) {
            return array_merge($defaults, $args);
        });
        
        // Create attendant instance
        $attendant = new DFXPRL_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);
        
        // Mock the retreat model to return our mock retreat
        $retreat_model_mock = $this->getMockBuilder('DFXPRL_Retreat')
            ->disableOriginalConstructor()
            ->getMock();
        $retreat_model_mock->method('get')->willReturn($mock_retreat);
        
        // We need to mock the new statement in export_csv_data
        // Since we can't easily do that, we'll verify the behavior by checking the export structure
        
        // Call export_csv_data
        $csv_data = $attendant->export_csv_data(1);
        
        // Verify that 'Notes' header is present (when notes_enabled is true)
        $this->assertContains('Notes', $csv_data['headers'], 'Notes header should be present when notes_enabled is true');
        
        // Verify that 'Internal Notes' header is NOT present (should never be exported)
        $this->assertNotContains('Internal Notes', $csv_data['headers'], 'Internal Notes header should NEVER be present in CSV export');
        
        // Verify that the notes value is in the row data
        $first_row = $csv_data['rows'][0];
        $notes_index = array_search('Notes', $csv_data['headers']);
        if ($notes_index !== false) {
            $this->assertEquals('This should be exported', $first_row[$notes_index], 'Notes value should be in the exported data');
        }
        
        // Verify the row doesn't have more columns than headers (which would happen if internal_notes was added)
        $this->assertCount(count($csv_data['headers']), $first_row, 'Row should have same number of columns as headers');
    }

    /**
     * Test get_by_retreat with filter_name parameter
     */
    public function testGetByRetreatWithNameFilter() {
        // Mock the database
        $database_mock = $this->createMock('DFXPRL_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');

        // Mock wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturnCallback(function($sql, $params) {
                 // Verify that the filter is included in the query
                 $this->assertStringContainsString('a.name LIKE', $sql);
                 return $sql;
             });
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn([]);
        $wpdb->method('esc_like')->willReturnArgument(0);

        // Mock WordPress functions
        Functions\when('wp_parse_args')->alias(function($args, $defaults) {
            return array_merge($defaults, $args);
        });

        $attendant = new DFXPRL_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);

        // Call get_by_retreat with filter
        $attendant->get_by_retreat(1, ['filter_name' => 'John']);
    }

    /**
     * Test get_by_retreat with multiple filters
     */
    public function testGetByRetreatWithMultipleFilters() {
        // Mock the database
        $database_mock = $this->createMock('DFXPRL_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');

        // Mock wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturnCallback(function($sql, $params) {
                 // Verify that multiple filters are included in the query
                 $this->assertStringContainsString('a.name LIKE', $sql);
                 $this->assertStringContainsString('a.surnames LIKE', $sql);
                 $this->assertStringContainsString('a.invited_by LIKE', $sql);
                 return $sql;
             });
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn([]);
        $wpdb->method('esc_like')->willReturnArgument(0);

        // Mock WordPress functions
        Functions\when('wp_parse_args')->alias(function($args, $defaults) {
            return array_merge($defaults, $args);
        });

        $attendant = new DFXPRL_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);

        // Call get_by_retreat with multiple filters
        $attendant->get_by_retreat(1, [
            'filter_name' => 'John',
            'filter_surnames' => 'Doe',
            'filter_invited_by' => 'Jane'
        ]);
    }

    /**
     * Test get_by_retreat with message_count ordering
     */
    public function testGetByRetreatWithMessageCountOrdering() {
        // Mock the database
        $database_mock = $this->createMock('DFXPRL_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        $database_mock->method('get_message_print_log_table')->willReturn('wp_dfx_print_log');

        // Mock wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturnCallback(function($sql, $params) {
                 // Verify that JOIN is included when ordering by message_count
                 $this->assertStringContainsString('LEFT JOIN', $sql);
                 $this->assertStringContainsString('message_count', $sql);
                 $this->assertStringContainsString('GROUP BY', $sql);
                 return $sql;
             });
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn([]);
        $wpdb->method('esc_like')->willReturnArgument(0);

        // Mock WordPress functions
        Functions\when('wp_parse_args')->alias(function($args, $defaults) {
            return array_merge($defaults, $args);
        });

        $attendant = new DFXPRL_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);

        // Call get_by_retreat with message_count ordering
        $attendant->get_by_retreat(1, ['orderby' => 'message_count', 'order' => 'DESC']);
    }

    /**
     * Test get_by_retreat with non_printed_count ordering
     */
    public function testGetByRetreatWithNonPrintedCountOrdering() {
        // Mock the database
        $database_mock = $this->createMock('DFXPRL_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        $database_mock->method('get_message_print_log_table')->willReturn('wp_dfx_print_log');

        // Mock wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturnCallback(function($sql, $params) {
                 // Verify that JOIN is included when ordering by non_printed_count
                 $this->assertStringContainsString('LEFT JOIN', $sql);
                 $this->assertStringContainsString('non_printed_count', $sql);
                 $this->assertStringContainsString('GROUP BY', $sql);
                 // Update assertion: we check for p.id IS NULL, not printed_at IS NULL
                 $this->assertStringContainsString('p.id IS NULL', $sql);
                 return $sql;
             });
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn([]);
        $wpdb->method('esc_like')->willReturnArgument(0);

        // Mock WordPress functions
        Functions\when('wp_parse_args')->alias(function($args, $defaults) {
            return array_merge($defaults, $args);
        });

        $attendant = new DFXPRL_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);

        // Call get_by_retreat with non_printed_count ordering
        $attendant->get_by_retreat(1, ['orderby' => 'non_printed_count', 'order' => 'ASC']);
    }

    /**
     * Test get_count_by_retreat with filters
     */
    public function testGetCountByRetreatWithFilters() {
        // Mock the database
        $database_mock = $this->createMock('DFXPRL_Database');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');

        // Mock wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturnCallback(function($sql, $params) {
                 // Verify that filters are included in the count query
                 $this->assertStringContainsString('name LIKE', $sql);
                 $this->assertStringContainsString('invited_by LIKE', $sql);
                 return $sql;
             });
        $wpdb->expects($this->once())
             ->method('get_var')
             ->willReturn(5);
        $wpdb->method('esc_like')->willReturnArgument(0);

        $attendant = new DFXPRL_Attendant();
        
        $reflection = new ReflectionClass($attendant);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($attendant, $database_mock);

        // Call get_count_by_retreat with filters
        $count = $attendant->get_count_by_retreat(1, '', [
            'filter_name' => 'John',
            'filter_invited_by' => 'Jane'
        ]);

        $this->assertEquals(5, $count);
    }
}