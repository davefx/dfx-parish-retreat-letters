<?php
/**
 * Unit tests for DFX_Parish_Retreat_Letters_ConfidentialMessage class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFX_Parish_Retreat_Letters_ConfidentialMessage
 */
class ConfidentialMessageTest extends TestCase {

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
        Functions\when('wp_kses_post')->alias(function($text) {
            return strip_tags($text, '<p><br><strong><em><ul><ol><li>');
        });
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('is_user_logged_in')->justReturn(true);
    }

    /**
     * Tear down test environment
     */
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test message creation with encryption
     */
    public function testCreateMessageWithEncryption() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        
        // Mock the security instance
        $security_mock = $this->createMock('DFX_Parish_Retreat_Letters_Security');
        $security_mock->method('encrypt_data')
                     ->willReturn([
                         'encrypted' => 'encrypted_content_here',
                         'salt' => 'random_salt_123456'
                     ]);
        $security_mock->method('get_user_ip')->willReturn('192.168.1.100');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->insert_id = 789;
        $wpdb->expects($this->once())
             ->method('insert')
             ->willReturn(true);
        
        // Create message instance
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        // Use reflection to set the private properties
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $security_property = $reflection->getProperty('security');
        $security_property->setAccessible(true);
        $security_property->setValue($message, $security_mock);
        
        $valid_data = [
            'attendant_id' => 1,
            'sender_name' => 'Father John',
            'content' => 'This is a confidential message for the retreat attendant.',
            'message_type' => 'personal'
        ];
        
        $result = $message->create($valid_data);
        $this->assertEquals(789, $result);
    }

    /**
     * Test message creation with invalid data
     */
    public function testCreateMessageWithInvalidData() {
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $invalid_data = [
            'attendant_id' => '', // Missing required field
            'content' => '', // Empty content
        ];
        
        $result = $message->create($invalid_data);
        $this->assertFalse($result);
    }

    /**
     * Test message creation when encryption fails
     */
    public function testCreateMessageWithEncryptionFailure() {
        // Mock the security instance to return false (encryption failure)
        $security_mock = $this->createMock('DFX_Parish_Retreat_Letters_Security');
        $security_mock->method('encrypt_data')->willReturn(false);
        
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        // Use reflection to set the security property
        $reflection = new ReflectionClass($message);
        $security_property = $reflection->getProperty('security');
        $security_property->setAccessible(true);
        $security_property->setValue($message, $security_mock);
        
        $valid_data = [
            'attendant_id' => 1,
            'sender_name' => 'Father John',
            'content' => 'This is a test message.',
            'message_type' => 'personal'
        ];
        
        $result = $message->create($valid_data);
        $this->assertFalse($result);
    }

    /**
     * Test get message by ID with decryption
     */
    public function testGetMessageByIdWithDecryption() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        
        // Mock the security instance
        $security_mock = $this->createMock('DFX_Parish_Retreat_Letters_Security');
        $security_mock->method('decrypt_data')
                     ->willReturn('Decrypted message content');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $encrypted_message = (object) [
            'id' => 1,
            'attendant_id' => 1,
            'sender_name' => 'Father John',
            'encrypted_content' => 'encrypted_content_here',
            'content_salt' => 'random_salt_123456',
            'message_type' => 'personal',
            'created_at' => '2024-01-01 12:00:00'
        ];
        
        $wpdb->expects($this->once())
             ->method('get_row')
             ->willReturn($encrypted_message);
             
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_dfx_messages WHERE id = 1");
        
        // Create message instance and inject mocked dependencies
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $security_property = $reflection->getProperty('security');
        $security_property->setAccessible(true);
        $security_property->setValue($message, $security_mock);
        
        $result = $message->get(1);
        
        $this->assertNotNull($result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Decrypted message content', $result->content);
    }

    /**
     * Test get messages by attendant ID
     */
    public function testGetMessagesByAttendantId() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        
        // Mock the security instance
        $security_mock = $this->createMock('DFX_Parish_Retreat_Letters_Security');
        $security_mock->method('decrypt_data')
                     ->willReturn('Decrypted message content');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $encrypted_messages = [
            (object) [
                'id' => 1,
                'attendant_id' => 1,
                'sender_name' => 'Father John',
                'encrypted_content' => 'encrypted_content_1',
                'content_salt' => 'salt_1',
                'message_type' => 'personal'
            ],
            (object) [
                'id' => 2,
                'attendant_id' => 1,
                'sender_name' => 'Sister Mary',
                'encrypted_content' => 'encrypted_content_2',
                'content_salt' => 'salt_2',
                'message_type' => 'group'
            ]
        ];
        
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn($encrypted_messages);
             
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_dfx_messages WHERE attendant_id = 1 ORDER BY created_at DESC");
        
        // Create message instance and inject mocked dependencies
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $security_property = $reflection->getProperty('security');
        $security_property->setAccessible(true);
        $security_property->setValue($message, $security_mock);
        
        if (method_exists($message, 'get_by_attendant_id')) {
            $result = $message->get_by_attendant_id(1);
            
            $this->assertIsArray($result);
            $this->assertCount(2, $result);
            $this->assertEquals('Decrypted message content', $result[0]->content);
            $this->assertEquals('Decrypted message content', $result[1]->content);
        } else {
            $this->markTestSkipped('get_by_attendant_id method not found');
        }
    }

    /**
     * Test update message
     */
    public function testUpdateMessage() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        
        // Mock the security instance
        $security_mock = $this->createMock('DFX_Parish_Retreat_Letters_Security');
        $security_mock->method('encrypt_data')
                     ->willReturn([
                         'encrypted' => 'new_encrypted_content',
                         'salt' => 'new_salt_123456'
                     ]);
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('update')
             ->willReturn(1);
        
        // Create message instance and inject mocked dependencies
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $security_property = $reflection->getProperty('security');
        $security_property->setAccessible(true);
        $security_property->setValue($message, $security_mock);
        
        $update_data = [
            'sender_name' => 'Father Updated',
            'content' => 'Updated message content',
            'message_type' => 'urgent'
        ];
        
        $result = $message->update(1, $update_data);
        $this->assertTrue($result);
    }

    /**
     * Test delete message
     */
    public function testDeleteMessage() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('delete')
             ->willReturn(1);
        
        // Create message instance and inject mocked database
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $result = $message->delete(1);
        $this->assertTrue($result);
    }

    /**
     * Test message data validation
     */
    public function testMessageDataValidation() {
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        
        if ($reflection->hasMethod('validate_message_data')) {
            $method = $reflection->getMethod('validate_message_data');
            $method->setAccessible(true);
            
            // Test valid data
            $valid_data = [
                'attendant_id' => 1,
                'sender_name' => 'Father John',
                'content' => 'This is a valid message.',
                'message_type' => 'personal'
            ];
            
            $this->assertTrue($method->invoke($message, $valid_data));
            
            // Test invalid data - missing required fields
            $invalid_data = [
                'sender_name' => 'Father John'
                // Missing attendant_id and content
            ];
            
            $this->assertFalse($method->invoke($message, $invalid_data));
            
            // Test invalid message type
            $invalid_type_data = [
                'attendant_id' => 1,
                'sender_name' => 'Father John',
                'content' => 'Test message',
                'message_type' => 'invalid_type'
            ];
            
            $this->assertFalse($method->invoke($message, $invalid_type_data));
        } else {
            $this->markTestSkipped('validate_message_data method not found');
        }
    }

    /**
     * Test message data sanitization
     */
    public function testMessageDataSanitization() {
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        
        if ($reflection->hasMethod('sanitize_message_data')) {
            $method = $reflection->getMethod('sanitize_message_data');
            $method->setAccessible(true);
            
            $dirty_data = [
                'attendant_id' => '1',
                'sender_name' => '<script>alert("xss")</script>Father John',
                'content' => '  <p>This is a message with HTML</p><script>evil();</script>  ',
                'message_type' => '  personal  '
            ];
            
            $sanitized = $method->invoke($message, $dirty_data);
            
            $this->assertEquals(1, $sanitized['attendant_id']);
            $this->assertEquals('Father John', $sanitized['sender_name']); // Script tags removed
            $this->assertStringContains('<p>', $sanitized['content']); // Allowed HTML preserved
            $this->assertStringNotContains('<script>', $sanitized['content']); // Script tags removed
            $this->assertEquals('personal', $sanitized['message_type']); // Trimmed
            
            // Test that MSO style definitions are properly handled by wp_kses_post
            // Note: The actual cleaning happens in JavaScript cleanPastedContent, but we verify
            // that wp_kses_post also removes style tags if they make it through
            $mso_data = [
                'attendant_id' => '1',
                'sender_name' => 'Test User',
                'content' => '<p>Normal message</p><style>/* Style Definitions */ table.MsoNormalTable {mso-style-name:"Tabla normal";}</style>',
                'message_type' => 'text'
            ];
            
            $sanitized_mso = $method->invoke($message, $mso_data);
            
            // wp_kses_post should remove style tags and their content
            $this->assertStringNotContains('<style>', $sanitized_mso['content']);
            $this->assertStringNotContains('MsoNormalTable', $sanitized_mso['content']);
            $this->assertStringContains('<p>Normal message</p>', $sanitized_mso['content']);
        } else {
            $this->markTestSkipped('sanitize_message_data method not found');
        }
    }

    /**
     * Test message search functionality
     */
    public function testSearchMessages() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        
        // Mock the security instance
        $security_mock = $this->createMock('DFX_Parish_Retreat_Letters_Security');
        $security_mock->method('decrypt_data')
                     ->willReturn('Search result message');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $search_results = [
            (object) [
                'id' => 1,
                'sender_name' => 'Father John',
                'encrypted_content' => 'encrypted_content',
                'content_salt' => 'salt'
            ]
        ];
        
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn($search_results);
        
        // Create message instance and inject mocked dependencies
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $security_property = $reflection->getProperty('security');
        $security_property->setAccessible(true);
        $security_property->setValue($message, $security_mock);
        
        if (method_exists($message, 'search')) {
            $search_term = 'Father';
            $results = $message->search($search_term);
            
            $this->assertIsArray($results);
            $this->assertCount(1, $results);
            $this->assertEquals('Search result message', $results[0]->content);
        } else {
            $this->markTestSkipped('search method not found');
        }
    }

    /**
     * Test mark message as read
     */
    public function testMarkMessageAsRead() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('update')
             ->with(
                 'wp_dfx_messages',
                 ['read_at' => $this->anything()],
                 ['id' => 1]
             )
             ->willReturn(1);
        
        // Create message instance and inject mocked database
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        if (method_exists($message, 'mark_as_read')) {
            $result = $message->mark_as_read(1);
            $this->assertTrue($result);
        } else {
            $this->markTestSkipped('mark_as_read method not found');
        }
    }

    /**
     * Test get unread message count
     */
    public function testGetUnreadMessageCount() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->expects($this->once())
             ->method('get_var')
             ->willReturn('3');
             
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT COUNT(*) FROM wp_dfx_messages WHERE attendant_id = 1 AND read_at IS NULL");
        
        // Create message instance and inject mocked database
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        if (method_exists($message, 'get_unread_count')) {
            $count = $message->get_unread_count(1);
            $this->assertEquals(3, $count);
        } else {
            $this->markTestSkipped('get_unread_count method not found');
        }
    }

    /**
     * Test constructor initializes dependencies
     */
    public function testConstructorInitializesDependencies() {
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_value = $database_property->getValue($message);
        $this->assertNotNull($database_value);
        
        $security_property = $reflection->getProperty('security');
        $security_property->setAccessible(true);
        $security_value = $security_property->getValue($message);
        $this->assertNotNull($security_value);
    }

    /**
     * Test that get() method includes attendant name and surnames
     */
    public function testGetIncludesAttendantInformation() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        
        $message_with_attendant = (object) [
            'id' => 1,
            'attendant_id' => 1,
            'sender_name' => 'Father John',
            'encrypted_content' => 'encrypted_content_here',
            'content_salt' => 'random_salt_123456',
            'message_type' => 'personal',
            'retreat_id' => 1,
            'attendant_name' => 'John',
            'attendant_surnames' => 'Doe',
            'created_at' => '2024-01-01 12:00:00'
        ];
        
        $wpdb->expects($this->once())
             ->method('get_row')
             ->willReturn($message_with_attendant);
             
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT m.*, a.retreat_id, a.name as attendant_name, a.surnames as attendant_surnames FROM wp_dfx_messages m INNER JOIN wp_dfx_attendants a ON m.attendant_id = a.id WHERE m.id = 1");
        
        // Create message instance and inject mocked dependencies
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $result = $message->get(1);
        
        $this->assertNotNull($result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('John', $result->attendant_name);
        $this->assertEquals('Doe', $result->attendant_surnames);
        $this->assertEquals('Father John', $result->sender_name);
    }

    /**
     * Test get count by attendant ID
     */
    public function testGetCountByAttendant() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->method('esc_like')->willReturnCallback(function($text) {
            return addslashes($text);
        });
        $wpdb->expects($this->once())
             ->method('get_var')
             ->willReturn('5');
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT COUNT(*) FROM wp_dfx_messages m INNER JOIN wp_dfx_attendants a ON m.attendant_id = a.id WHERE m.attendant_id = 1");
        
        // Create message instance and inject mocked database
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $count = $message->get_count_by_attendant(1);
        $this->assertEquals(5, $count);
    }

    /**
     * Test get non-printed count by attendant ID
     */
    public function testGetNonPrintedCountByAttendant() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        $database_mock->method('get_message_print_log_table')->willReturn('wp_dfx_message_print_log');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->method('esc_like')->willReturnCallback(function($text) {
            return addslashes($text);
        });
        $wpdb->expects($this->once())
             ->method('get_var')
             ->willReturn('2');
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT COUNT(*) FROM wp_dfx_messages m INNER JOIN wp_dfx_attendants a ON m.attendant_id = a.id LEFT JOIN wp_dfx_message_print_log p ON m.id = p.message_id WHERE m.attendant_id = 1 AND p.id IS NULL");
        
        // Create message instance and inject mocked database
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $count = $message->get_non_printed_count_by_attendant(1);
        $this->assertEquals(2, $count);
    }

    /**
     * Test get non-printed count with message type filter
     */
    public function testGetNonPrintedCountWithMessageTypeFilter() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        $database_mock->method('get_message_print_log_table')->willReturn('wp_dfx_message_print_log');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->method('esc_like')->willReturnCallback(function($text) {
            return addslashes($text);
        });
        $wpdb->expects($this->once())
             ->method('get_var')
             ->willReturn('1');
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT COUNT(*) FROM wp_dfx_messages m INNER JOIN wp_dfx_attendants a ON m.attendant_id = a.id LEFT JOIN wp_dfx_message_print_log p ON m.id = p.message_id WHERE m.attendant_id = 1 AND m.message_type = 'personal' AND p.id IS NULL");
        
        // Create message instance and inject mocked database
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $count = $message->get_non_printed_count_by_attendant(1, ['message_type' => 'personal']);
        $this->assertEquals(1, $count);
    }

    /**
     * Test get non-printed count with search filter
     */
    public function testGetNonPrintedCountWithSearchFilter() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        $database_mock->method('get_message_print_log_table')->willReturn('wp_dfx_message_print_log');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->method('esc_like')->willReturnCallback(function($text) {
            return addslashes($text);
        });
        $wpdb->expects($this->once())
             ->method('get_var')
             ->willReturn('1');
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT COUNT(*) FROM wp_dfx_messages m INNER JOIN wp_dfx_attendants a ON m.attendant_id = a.id LEFT JOIN wp_dfx_message_print_log p ON m.id = p.message_id WHERE m.attendant_id = 1 AND (a.name LIKE '%John%' OR a.surnames LIKE '%John%' OR m.sender_name LIKE '%John%') AND p.id IS NULL");
        
        // Create message instance and inject mocked database
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $count = $message->get_non_printed_count_by_attendant(1, ['search' => 'John']);
        $this->assertEquals(1, $count);
    }

    /**
     * Test get non-printed count returns zero when all messages are printed
     */
    public function testGetNonPrintedCountReturnsZeroWhenAllPrinted() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        $database_mock->method('get_message_print_log_table')->willReturn('wp_dfx_message_print_log');
        
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->method('esc_like')->willReturnCallback(function($text) {
            return addslashes($text);
        });
        $wpdb->expects($this->once())
             ->method('get_var')
             ->willReturn('0');
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT COUNT(*) FROM wp_dfx_messages m INNER JOIN wp_dfx_attendants a ON m.attendant_id = a.id LEFT JOIN wp_dfx_message_print_log p ON m.id = p.message_id WHERE m.attendant_id = 1 AND p.id IS NULL");
        
        // Create message instance and inject mocked database
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $count = $message->get_non_printed_count_by_attendant(1);
        $this->assertEquals(0, $count);
    }

    /**
     * Test get non-printed count returns integer type
     */
    public function testGetNonPrintedCountReturnsInteger() {
        // Mock the database instance
        $database_mock = $this->createMock('DFX_Parish_Retreat_Letters_Database');
        $database_mock->method('get_messages_table')->willReturn('wp_dfx_messages');
        $database_mock->method('get_attendants_table')->willReturn('wp_dfx_attendants');
        $database_mock->method('get_message_print_log_table')->willReturn('wp_dfx_message_print_log');
        
        // Mock global wpdb - return string like real database would
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->method('esc_like')->willReturnCallback(function($text) {
            return addslashes($text);
        });
        $wpdb->expects($this->once())
             ->method('get_var')
             ->willReturn('3'); // Return string, not integer
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT COUNT(*) FROM wp_dfx_messages m INNER JOIN wp_dfx_attendants a ON m.attendant_id = a.id LEFT JOIN wp_dfx_message_print_log p ON m.id = p.message_id WHERE m.attendant_id = 1 AND p.id IS NULL");
        
        // Create message instance and inject mocked database
        $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
        
        $reflection = new ReflectionClass($message);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database_property->setValue($message, $database_mock);
        
        $count = $message->get_non_printed_count_by_attendant(1);
        
        // Verify it returns an integer, not a string
        $this->assertIsInt($count);
        $this->assertEquals(3, $count);
    }
}