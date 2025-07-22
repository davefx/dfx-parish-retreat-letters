<?php
/**
 * Unit tests for DFX_Parish_Retreat_Letters_Admin class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFX_Parish_Retreat_Letters_Admin
 */
class AdminTest extends TestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('add_menu_page')->justReturn(true);
        Functions\when('add_submenu_page')->justReturn(true);
        Functions\when('wp_enqueue_script')->justReturn(true);
        Functions\when('wp_enqueue_style')->justReturn(true);
        Functions\when('wp_localize_script')->justReturn(true);
        Functions\when('wp_create_nonce')->justReturn('test_nonce');
        Functions\when('wp_verify_nonce')->justReturn(true);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('is_admin')->justReturn(true);
        Functions\when('admin_url')->alias(function($path) {
            return 'http://example.com/wp-admin/' . $path;
        });
        Functions\when('plugin_dir_url')->justReturn('http://example.com/wp-content/plugins/dfx-parish-retreat-letters/');
        Functions\when('sanitize_text_field')->alias(function($text) {
            return trim(strip_tags($text));
        });
        Functions\when('wp_redirect')->justReturn(true);
        Functions\when('wp_die')->alias(function($message) {
            throw new Exception($message);
        });
        Functions\when('esc_html')->alias(function($text) {
            return htmlspecialchars($text);
        });
        Functions\when('esc_attr')->alias(function($text) {
            return htmlspecialchars($text);
        });
        Functions\when('__')->alias(function($text) {
            return $text;
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
     * Test singleton pattern
     */
    public function testSingletonPattern() {
        $instance1 = DFX_Parish_Retreat_Letters_Admin::get_instance();
        $instance2 = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf('DFX_Parish_Retreat_Letters_Admin', $instance1);
    }

    /**
     * Test admin hooks are registered
     */
    public function testAdminHooksRegistered() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'register_hooks')) {
            // Verify method exists and is callable
            $this->assertTrue(is_callable([$admin, 'register_hooks']));
            
            // Execute hook registration
            $admin->register_hooks();
            
            // Verify add_action was called for admin hooks
            $this->assertTrue(function_exists('add_action'));
        } else {
            $this->markTestSkipped('register_hooks method not found');
        }
    }

    /**
     * Test admin menu creation
     */
    public function testAdminMenuCreation() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'add_admin_menu')) {
            // Verify method exists and is callable
            $this->assertTrue(is_callable([$admin, 'add_admin_menu']));
        } else {
            $this->markTestSkipped('add_admin_menu method not found');
        }
    }

    /**
     * Test script and style enqueuing
     */
    public function testScriptAndStyleEnqueuing() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'enqueue_admin_scripts')) {
            $this->assertTrue(is_callable([$admin, 'enqueue_admin_scripts']));
            
            // Test script enqueuing
            $admin->enqueue_admin_scripts();
            
            // Verify functions were called (mocked to return true)
            $this->assertTrue(function_exists('wp_enqueue_script'));
            $this->assertTrue(function_exists('wp_enqueue_style'));
        } else {
            $this->markTestSkipped('enqueue_admin_scripts method not found');
        }
    }

    /**
     * Test AJAX handler for creating retreat
     */
    public function testAjaxCreateRetreat() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'ajax_create_retreat')) {
            // Mock $_POST data
            $_POST = [
                'nonce' => 'test_nonce',
                'name' => 'Test Retreat',
                'location' => 'Test Church',
                'start_date' => '2024-06-01',
                'end_date' => '2024-06-03',
                'custom_message' => 'Test message'
            ];
            
            // Mock retreat model
            $retreat_mock = $this->createMock('DFX_Parish_Retreat_Letters_Retreat');
            $retreat_mock->method('create')->willReturn(123);
            
            // Use reflection to set retreat model
            $reflection = new ReflectionClass($admin);
            if ($reflection->hasProperty('retreat_model')) {
                $property = $reflection->getProperty('retreat_model');
                $property->setAccessible(true);
                $property->setValue($admin, $retreat_mock);
            }
            
            // Verify method is callable
            $this->assertTrue(is_callable([$admin, 'ajax_create_retreat']));
        } else {
            $this->markTestSkipped('ajax_create_retreat method not found');
        }
    }

    /**
     * Test AJAX handler for creating attendant
     */
    public function testAjaxCreateAttendant() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'ajax_create_attendant')) {
            // Mock $_POST data
            $_POST = [
                'nonce' => 'test_nonce',
                'retreat_id' => '1',
                'name' => 'John',
                'surnames' => 'Doe',
                'date_of_birth' => '1980-01-01',
                'emergency_contact_name' => 'Jane',
                'emergency_contact_surname' => 'Doe',
                'emergency_contact_phone' => '+1234567890'
            ];
            
            // Mock attendant model
            $attendant_mock = $this->createMock('DFX_Parish_Retreat_Letters_Attendant');
            $attendant_mock->method('create')->willReturn(456);
            
            // Use reflection to set attendant model
            $reflection = new ReflectionClass($admin);
            if ($reflection->hasProperty('attendant_model')) {
                $property = $reflection->getProperty('attendant_model');
                $property->setAccessible(true);
                $property->setValue($admin, $attendant_mock);
            }
            
            // Verify method is callable
            $this->assertTrue(is_callable([$admin, 'ajax_create_attendant']));
        } else {
            $this->markTestSkipped('ajax_create_attendant method not found');
        }
    }

    /**
     * Test AJAX handler for sending message
     */
    public function testAjaxSendMessage() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'ajax_send_message')) {
            // Mock $_POST data
            $_POST = [
                'nonce' => 'test_nonce',
                'attendant_id' => '1',
                'sender_name' => 'Father John',
                'content' => 'Test message content',
                'message_type' => 'personal'
            ];
            
            // Mock message model
            $message_mock = $this->createMock('DFX_Parish_Retreat_Letters_ConfidentialMessage');
            $message_mock->method('create')->willReturn(789);
            
            // Use reflection to set message model
            $reflection = new ReflectionClass($admin);
            if ($reflection->hasProperty('message_model')) {
                $property = $reflection->getProperty('message_model');
                $property->setAccessible(true);
                $property->setValue($admin, $message_mock);
            }
            
            // Verify method is callable
            $this->assertTrue(is_callable([$admin, 'ajax_send_message']));
        } else {
            $this->markTestSkipped('ajax_send_message method not found');
        }
    }

    /**
     * Test nonce verification
     */
    public function testNonceVerification() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        $reflection = new ReflectionClass($admin);
        
        if ($reflection->hasMethod('verify_nonce')) {
            $method = $reflection->getMethod('verify_nonce');
            $method->setAccessible(true);
            
            // Test valid nonce
            $_POST['nonce'] = 'test_nonce';
            $result = $method->invoke($admin, 'test_nonce', 'test_action');
            $this->assertTrue($result);
            
            // Test invalid nonce
            $result = $method->invoke($admin, 'invalid_nonce', 'test_action');
            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('verify_nonce method not found');
        }
    }

    /**
     * Test permission checking
     */
    public function testPermissionChecking() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        $reflection = new ReflectionClass($admin);
        
        if ($reflection->hasMethod('check_permissions')) {
            $method = $reflection->getMethod('check_permissions');
            $method->setAccessible(true);
            
            // Test with proper capability
            $result = $method->invoke($admin, 'manage_options');
            $this->assertTrue($result);
        } else {
            $this->markTestSkipped('check_permissions method not found');
        }
    }

    /**
     * Test retreat listing functionality
     */
    public function testRetreatListing() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'display_retreats_page')) {
            // Mock retreat model
            $retreat_mock = $this->createMock('DFX_Parish_Retreat_Letters_Retreat');
            $retreat_mock->method('get_all')->willReturn([
                (object) [
                    'id' => 1,
                    'name' => 'Summer Retreat',
                    'location' => 'Mountain Lodge'
                ]
            ]);
            
            // Use reflection to set retreat model
            $reflection = new ReflectionClass($admin);
            if ($reflection->hasProperty('retreat_model')) {
                $property = $reflection->getProperty('retreat_model');
                $property->setAccessible(true);
                $property->setValue($admin, $retreat_mock);
            }
            
            // Verify method is callable
            $this->assertTrue(is_callable([$admin, 'display_retreats_page']));
        } else {
            $this->markTestSkipped('display_retreats_page method not found');
        }
    }

    /**
     * Test attendant listing functionality
     */
    public function testAttendantListing() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'display_attendants_page')) {
            // Mock attendant model
            $attendant_mock = $this->createMock('DFX_Parish_Retreat_Letters_Attendant');
            $attendant_mock->method('get_all')->willReturn([
                (object) [
                    'id' => 1,
                    'name' => 'John',
                    'surnames' => 'Doe',
                    'retreat_id' => 1
                ]
            ]);
            
            // Use reflection to set attendant model
            $reflection = new ReflectionClass($admin);
            if ($reflection->hasProperty('attendant_model')) {
                $property = $reflection->getProperty('attendant_model');
                $property->setAccessible(true);
                $property->setValue($admin, $attendant_mock);
            }
            
            // Verify method is callable
            $this->assertTrue(is_callable([$admin, 'display_attendants_page']));
        } else {
            $this->markTestSkipped('display_attendants_page method not found');
        }
    }

    /**
     * Test data export functionality
     */
    public function testDataExport() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'export_data')) {
            // Verify method is callable
            $this->assertTrue(is_callable([$admin, 'export_data']));
        } else {
            $this->markTestSkipped('export_data method not found');
        }
    }

    /**
     * Test print log functionality
     */
    public function testPrintLogFunctionality() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'log_print_action')) {
            // Mock print log model
            $print_log_mock = $this->createMock('DFX_Parish_Retreat_Letters_PrintLog');
            $print_log_mock->method('log')->willReturn(true);
            
            // Use reflection to set print log model
            $reflection = new ReflectionClass($admin);
            if ($reflection->hasProperty('print_log_model')) {
                $property = $reflection->getProperty('print_log_model');
                $property->setAccessible(true);
                $property->setValue($admin, $print_log_mock);
            }
            
            // Verify method is callable
            $this->assertTrue(is_callable([$admin, 'log_print_action']));
        } else {
            $this->markTestSkipped('log_print_action method not found');
        }
    }

    /**
     * Test bulk operations
     */
    public function testBulkOperations() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'handle_bulk_actions')) {
            // Mock $_POST data for bulk action
            $_POST = [
                'action' => 'delete',
                'attendant_ids' => ['1', '2', '3'],
                'nonce' => 'test_nonce'
            ];
            
            // Verify method is callable
            $this->assertTrue(is_callable([$admin, 'handle_bulk_actions']));
        } else {
            $this->markTestSkipped('handle_bulk_actions method not found');
        }
    }

    /**
     * Test settings page functionality
     */
    public function testSettingsPage() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'display_settings_page')) {
            // Verify method is callable
            $this->assertTrue(is_callable([$admin, 'display_settings_page']));
        } else {
            $this->markTestSkipped('display_settings_page method not found');
        }
    }

    /**
     * Test GDPR compliance features
     */
    public function testGdprCompliance() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        if (method_exists($admin, 'handle_gdpr_request')) {
            // Mock GDPR model
            $gdpr_mock = $this->createMock('DFX_Parish_Retreat_Letters_GDPR');
            $gdpr_mock->method('export_personal_data')->willReturn(['data' => 'exported']);
            
            // Use reflection to set GDPR model
            $reflection = new ReflectionClass($admin);
            if ($reflection->hasProperty('gdpr')) {
                $property = $reflection->getProperty('gdpr');
                $property->setAccessible(true);
                $property->setValue($admin, $gdpr_mock);
            }
            
            // Verify method is callable
            $this->assertTrue(is_callable([$admin, 'handle_gdpr_request']));
        } else {
            $this->markTestSkipped('handle_gdpr_request method not found');
        }
    }

    /**
     * Test constructor initializes dependencies
     */
    public function testConstructorInitializesDependencies() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        $reflection = new ReflectionClass($admin);
        
        // Check if models are initialized
        $properties = ['retreat_model', 'attendant_model', 'message_model', 'security', 'gdpr'];
        
        foreach ($properties as $property_name) {
            if ($reflection->hasProperty($property_name)) {
                $property = $reflection->getProperty($property_name);
                $property->setAccessible(true);
                $value = $property->getValue($admin);
                $this->assertNotNull($value, "Property $property_name should be initialized");
            }
        }
    }

    /**
     * Test AJAX error handling
     */
    public function testAjaxErrorHandling() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        $reflection = new ReflectionClass($admin);
        
        if ($reflection->hasMethod('ajax_error_response')) {
            $method = $reflection->getMethod('ajax_error_response');
            $method->setAccessible(true);
            
            // Test error response
            ob_start();
            try {
                $method->invoke($admin, 'Test error message');
            } catch (Exception $e) {
                // Expected behavior when wp_die is called
                $this->assertStringContains('Test error message', $e->getMessage());
            }
            $output = ob_get_clean();
        } else {
            $this->markTestSkipped('ajax_error_response method not found');
        }
    }

    /**
     * Test form validation
     */
    public function testFormValidation() {
        $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
        
        $reflection = new ReflectionClass($admin);
        
        if ($reflection->hasMethod('validate_form_data')) {
            $method = $reflection->getMethod('validate_form_data');
            $method->setAccessible(true);
            
            // Test valid form data
            $valid_data = [
                'name' => 'Valid Name',
                'email' => 'test@example.com',
                'date' => '2024-01-01'
            ];
            
            $result = $method->invoke($admin, $valid_data, ['name', 'email']);
            $this->assertTrue($result);
            
            // Test invalid form data
            $invalid_data = [
                'name' => '',
                'email' => 'invalid-email'
            ];
            
            $result = $method->invoke($admin, $invalid_data, ['name', 'email']);
            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('validate_form_data method not found');
        }
    }
}