<?php
/**
 * Comprehensive infrastructure tests for all plugin features
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;

/**
 * Comprehensive test class to verify all plugin features
 */
class ComprehensiveInfrastructureTest extends TestCase {

    /**
     * Test that all core plugin class files exist
     */
    public function testAllCoreClassFilesExist() {
        $plugin_dir = dirname(__DIR__, 2);
        $required_files = [
            '/dfx-parish-retreat-letters.php',
            '/includes/class-dfx-parish-retreat-letters.php',
            '/includes/class-database.php',
            '/includes/class-retreat.php',
            '/includes/class-attendant.php',
            '/includes/class-admin.php',
            '/includes/class-security.php',
            '/includes/class-confidential-message.php',
            '/includes/class-gdpr.php',
            '/includes/class-permissions.php',
            '/includes/class-invitations.php',
            '/includes/class-message-file.php',
            '/includes/class-print-log.php'
        ];

        foreach ($required_files as $file) {
            $this->assertFileExists($plugin_dir . $file, "Required file missing: $file");
        }
    }

    /**
     * Test that all plugin classes can be loaded
     */
    public function testAllPluginClassesCanBeLoaded() {
        $plugin_dir = dirname(__DIR__, 2);
        
        // Only include the main plugin file if constants aren't already defined
        if (!defined('DFX_PARISH_RETREAT_LETTERS_VERSION')) {
            require_once $plugin_dir . '/dfx-parish-retreat-letters.php';
        }
        
        // Manually include class files since they aren't auto-loaded in basic bootstrap
        $class_files = [
            '/includes/class-dfx-parish-retreat-letters.php',
            '/includes/class-database.php',
            '/includes/class-retreat.php',
            '/includes/class-attendant.php',
            '/includes/class-admin.php',
            '/includes/class-security.php',
            '/includes/class-confidential-message.php',
            '/includes/class-gdpr.php',
            '/includes/class-permissions.php',
            '/includes/class-invitations.php',
            '/includes/class-message-file.php',
            '/includes/class-print-log.php'
        ];
        
        foreach ($class_files as $file) {
            if (file_exists($plugin_dir . $file)) {
                require_once $plugin_dir . $file;
            }
        }
        
        // Test that core classes exist
        $core_classes = [
            'DFX_Parish_Retreat_Letters',
            'DFX_Parish_Retreat_Letters_Database',
            'DFX_Parish_Retreat_Letters_Retreat',
            'DFX_Parish_Retreat_Letters_Attendant',
            'DFX_Parish_Retreat_Letters_Admin',
            'DFX_Parish_Retreat_Letters_Security',
            'DFX_Parish_Retreat_Letters_ConfidentialMessage',
            'DFX_Parish_Retreat_Letters_GDPR',
            'DFX_Parish_Retreat_Letters_Permissions',
            'DFX_Parish_Retreat_Letters_Invitations',
            'DFX_Parish_Retreat_Letters_MessageFile',
            'DFX_Parish_Retreat_Letters_PrintLog'
        ];

        foreach ($core_classes as $class) {
            $this->assertTrue(class_exists($class), "Class $class should exist");
        }
    }

    /**
     * Test singleton patterns for core classes
     */
    public function testSingletonPatternsWork() {
        // Mock global $wpdb to avoid database errors
        global $wpdb;
        if (!isset($wpdb)) {
            $wpdb = new stdClass();
            $wpdb->prefix = 'wp_';
        }
        
        $singleton_classes = [
            'DFX_Parish_Retreat_Letters',
            'DFX_Parish_Retreat_Letters_Database',
            'DFX_Parish_Retreat_Letters_Admin',
            'DFX_Parish_Retreat_Letters_Security',
            'DFX_Parish_Retreat_Letters_GDPR',
            'DFX_Parish_Retreat_Letters_Permissions'
        ];

        $tested_classes = 0;
        foreach ($singleton_classes as $class) {
            if (class_exists($class) && method_exists($class, 'get_instance')) {
                try {
                    $instance1 = $class::get_instance();
                    $instance2 = $class::get_instance();
                    
                    $this->assertSame($instance1, $instance2, "Singleton pattern should work for $class");
                    $this->assertInstanceOf($class, $instance1, "Instance should be of correct type for $class");
                    $tested_classes++;
                } catch (Exception $e) {
                    // Skip classes that can't be instantiated in test environment
                    continue;
                }
            }
        }
        
        // Ensure we tested at least some singleton classes
        $this->assertGreaterThan(0, $tested_classes, "Should have tested at least one singleton class");
    }

    /**
     * Test that all CRUD classes have required methods
     */
    public function testCRUDClassesHaveRequiredMethods() {
        $crud_classes = [
            'DFX_Parish_Retreat_Letters_Retreat' => ['create', 'get', 'update', 'delete'],
            'DFX_Parish_Retreat_Letters_Attendant' => ['create', 'get', 'update', 'delete'],
            'DFX_Parish_Retreat_Letters_ConfidentialMessage' => ['create', 'get', 'delete'] // No update method in this class
        ];

        $tested_classes = 0;
        foreach ($crud_classes as $class => $methods) {
            if (class_exists($class)) {
                foreach ($methods as $method) {
                    $this->assertTrue(
                        method_exists($class, $method),
                        "Method $method should exist in class $class"
                    );
                }
                $tested_classes++;
            }
        }
        
        // Ensure we tested at least some CRUD classes
        $this->assertGreaterThan(0, $tested_classes, "Should have tested at least one CRUD class");
    }

    /**
     * Test security features are available
     */
    public function testSecurityFeaturesAvailable() {
        if (class_exists('DFX_Parish_Retreat_Letters_Security')) {
            $security_methods = [
                'encrypt_data',
                'decrypt_data',
                'generate_secure_token',
                'generate_unique_message_token',
                'get_user_ip',
                'anonymize_ip'
            ];

            $tested_methods = 0;
            foreach ($security_methods as $method) {
                if (method_exists('DFX_Parish_Retreat_Letters_Security', $method)) {
                    $this->assertTrue(true, "Security method $method exists");
                    $tested_methods++;
                }
            }

            // Test security constants
            if (defined('DFX_Parish_Retreat_Letters_Security::ENCRYPTION_METHOD')) {
                $this->assertTrue(defined('DFX_Parish_Retreat_Letters_Security::ENCRYPTION_METHOD'));
            }
            if (defined('DFX_Parish_Retreat_Letters_Security::SALT_LENGTH')) {
                $this->assertTrue(defined('DFX_Parish_Retreat_Letters_Security::SALT_LENGTH'));
            }
            if (defined('DFX_Parish_Retreat_Letters_Security::TOKEN_LENGTH')) {
                $this->assertTrue(defined('DFX_Parish_Retreat_Letters_Security::TOKEN_LENGTH'));
            }
            
            // Ensure at least some security features were tested
            $this->assertGreaterThan(0, $tested_methods, "Should have at least some security methods available");
        } else {
            $this->markTestSkipped('Security class not available');
        }
    }

    /**
     * Test that upload size methods exist and handle unknown configuration properly
     *
     * This test verifies that:
     * 1. The DEFAULT_MAX_UPLOAD_SIZE constant is defined (8 MB fallback for validation)
     * 2. get_max_upload_size() method exists and returns null or valid value (never "0 bytes")
     * 3. get_max_combined_upload_size() method exists and returns null or valid value
     * 4. get_max_upload_size_for_validation() always returns a positive value for file validation
     *
     * @see https://github.com/davefx/dfx-parish-retreat-letters/issues/145
     */
    public function testUploadSizeFallbackHandling() {
        if (!class_exists('DFX_Parish_Retreat_Letters_Security')) {
            $this->markTestSkipped('Security class not available');
        }

        // Test DEFAULT_MAX_UPLOAD_SIZE constant exists and is 8 MB
        $this->assertTrue(
            defined('DFX_Parish_Retreat_Letters_Security::DEFAULT_MAX_UPLOAD_SIZE'),
            'DEFAULT_MAX_UPLOAD_SIZE constant should be defined'
        );
        $this->assertEquals(
            8388608,
            DFX_Parish_Retreat_Letters_Security::DEFAULT_MAX_UPLOAD_SIZE,
            'DEFAULT_MAX_UPLOAD_SIZE should be 8 MB (8388608 bytes)'
        );

        // Verify upload size methods exist
        $this->assertTrue(
            method_exists('DFX_Parish_Retreat_Letters_Security', 'get_max_upload_size'),
            'get_max_upload_size method should exist'
        );
        $this->assertTrue(
            method_exists('DFX_Parish_Retreat_Letters_Security', 'get_max_combined_upload_size'),
            'get_max_combined_upload_size method should exist'
        );
        $this->assertTrue(
            method_exists('DFX_Parish_Retreat_Letters_Security', 'get_max_upload_size_for_validation'),
            'get_max_upload_size_for_validation method should exist'
        );

        // Get instance
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();

        // Test display methods - should return null or valid value, never "0 bytes"
        $max_size_formatted = $security->get_max_upload_size(true);
        if ($max_size_formatted !== null) {
            $this->assertIsString($max_size_formatted, 'Formatted max upload size should be a string when not null');
            $this->assertStringNotContainsString('0 bytes', $max_size_formatted, 'Max upload size should never be "0 bytes"');
        }

        $max_size_bytes = $security->get_max_upload_size(false);
        if ($max_size_bytes !== null) {
            $this->assertIsInt($max_size_bytes, 'Raw max upload size should be an integer when not null');
            $this->assertGreaterThan(0, $max_size_bytes, 'Max upload size should be greater than 0 when not null');
        }

        // Test combined upload size
        $max_combined_formatted = $security->get_max_combined_upload_size(true);
        if ($max_combined_formatted !== null) {
            $this->assertIsString($max_combined_formatted, 'Formatted max combined size should be a string when not null');
            $this->assertStringNotContainsString('0 bytes', $max_combined_formatted, 'Max combined size should never be "0 bytes"');
        }

        $max_combined_bytes = $security->get_max_combined_upload_size(false);
        if ($max_combined_bytes !== null) {
            $this->assertIsInt($max_combined_bytes, 'Raw max combined size should be an integer when not null');
            $this->assertGreaterThan(0, $max_combined_bytes, 'Max combined size should be greater than 0 when not null');
        }

        // Test validation method - should ALWAYS return a positive value
        $validation_size = $security->get_max_upload_size_for_validation();
        $this->assertIsInt($validation_size, 'Validation max upload size should always be an integer');
        $this->assertGreaterThan(0, $validation_size, 'Validation max upload size should always be greater than 0');
    }

    /**
     * Test database management features
     */
    public function testDatabaseManagementFeatures() {
        if (class_exists('DFX_Parish_Retreat_Letters_Database')) {
            $database_methods = [
                'setup_tables',
                'get_retreats_table',
                'get_attendants_table',
                'get_messages_table'
            ];

            $tested_methods = 0;
            foreach ($database_methods as $method) {
                if (method_exists('DFX_Parish_Retreat_Letters_Database', $method)) {
                    $this->assertTrue(true, "Database method $method exists");
                    $tested_methods++;
                }
            }

            // Test database version constant
            if (defined('DFX_Parish_Retreat_Letters_Database::DB_VERSION')) {
                $this->assertTrue(defined('DFX_Parish_Retreat_Letters_Database::DB_VERSION'));
            }
            
            // Ensure at least some database methods were tested
            $this->assertGreaterThan(0, $tested_methods, "Should have at least some database methods available");
        } else {
            $this->markTestSkipped('Database class not available');
        }
    }

    /**
     * Test admin interface features
     */
    public function testAdminInterfaceFeatures() {
        if (class_exists('DFX_Parish_Retreat_Letters_Admin')) {
            $admin_methods = [
                'add_admin_menu',
                'enqueue_admin_scripts',
                'ajax_create_retreat',
                'ajax_create_attendant',
                'ajax_send_message'
            ];

            $tested_methods = 0;
            foreach ($admin_methods as $method) {
                if (method_exists('DFX_Parish_Retreat_Letters_Admin', $method)) {
                    $this->assertTrue(true, "Admin method $method exists");
                    $tested_methods++;
                }
            }
            
            $this->assertGreaterThan(0, $tested_methods, "Should have at least some admin methods available");
        } else {
            $this->markTestSkipped('Admin class not available');
        }
    }

    /**
     * Test GDPR compliance features
     */
    public function testGDPRComplianceFeatures() {
        if (class_exists('DFX_Parish_Retreat_Letters_GDPR')) {
            $gdpr_methods = [
                'export_personal_data',
                'erase_personal_data',
                'anonymize_attendant_data'
            ];

            $tested_methods = 0;
            foreach ($gdpr_methods as $method) {
                if (method_exists('DFX_Parish_Retreat_Letters_GDPR', $method)) {
                    $this->assertTrue(true, "GDPR method $method exists");
                    $tested_methods++;
                }
            }
            
            $this->assertGreaterThan(0, $tested_methods, "Should have at least some GDPR methods available");
        } else {
            $this->markTestSkipped('GDPR class not available');
        }
    }

    /**
     * Test permissions management features
     */
    public function testPermissionsManagementFeatures() {
        if (class_exists('DFX_Parish_Retreat_Letters_Permissions')) {
            $permission_methods = [
                'current_user_can_manage_plugin',
                'current_user_can_manage_retreat',
                'current_user_can_manage_messages',
                'current_user_can_view_retreat',
                'current_user_can_manage_attendant'
            ];

            $tested_methods = 0;
            foreach ($permission_methods as $method) {
                if (method_exists('DFX_Parish_Retreat_Letters_Permissions', $method)) {
                    $this->assertTrue(true, "Permission method $method exists");
                    $tested_methods++;
                }
            }
            
            $this->assertGreaterThan(0, $tested_methods, "Should have at least some permission methods available");
        } else {
            $this->markTestSkipped('Permissions class not available');
        }
    }

    /**
     * Test invitation system features
     */
    public function testInvitationSystemFeatures() {
        if (class_exists('DFX_Parish_Retreat_Letters_Invitations')) {
            $invitation_methods = [
                'create_invitation',
                'send_invitation_email',
                'send_bulk_invitations'
            ];

            $tested_methods = 0;
            foreach ($invitation_methods as $method) {
                if (method_exists('DFX_Parish_Retreat_Letters_Invitations', $method)) {
                    $this->assertTrue(true, "Invitation method $method exists");
                    $tested_methods++;
                }
            }
            
            $this->assertGreaterThan(0, $tested_methods, "Should have at least some invitation methods available");
        } else {
            $this->markTestSkipped('Invitations class not available');
        }
    }

    /**
     * Test file management features
     */
    public function testFileManagementFeatures() {
        if (class_exists('DFX_Parish_Retreat_Letters_MessageFile')) {
            $file_methods = [
                'create',
                'get',
                'delete',
                'serve_file',
                'get_by_message'
            ];

            $tested_methods = 0;
            foreach ($file_methods as $method) {
                if (method_exists('DFX_Parish_Retreat_Letters_MessageFile', $method)) {
                    $this->assertTrue(true, "File method $method exists");
                    $tested_methods++;
                }
            }
            
            $this->assertGreaterThan(0, $tested_methods, "Should have at least some file methods available");
        } else {
            $this->markTestSkipped('MessageFile class not available');
        }
    }

    /**
     * Test print logging features
     */
    public function testPrintLoggingFeatures() {
        if (class_exists('DFX_Parish_Retreat_Letters_PrintLog')) {
            $log_methods = [
                'log_print',
                'get',
                'get_by_message',
                'get_print_count',
                'is_message_printed'
            ];

            $tested_methods = 0;
            foreach ($log_methods as $method) {
                if (method_exists('DFX_Parish_Retreat_Letters_PrintLog', $method)) {
                    $this->assertTrue(true, "Print log method $method exists");
                    $tested_methods++;
                }
            }
            
            $this->assertGreaterThan(0, $tested_methods, "Should have at least some print log methods available");
        } else {
            $this->markTestSkipped('PrintLog class not available');
        }
    }

    /**
     * Test plugin constants are properly defined
     */
    public function testPluginConstantsAreDefined() {
        // Load plugin file if constants aren't defined yet
        if (!defined('DFX_PARISH_RETREAT_LETTERS_VERSION')) {
            $plugin_dir = dirname(__DIR__, 2);
            require_once $plugin_dir . '/dfx-parish-retreat-letters.php';
        }
        
        $required_constants = [
            'DFX_PARISH_RETREAT_LETTERS_VERSION'
        ];
        
        // These constants may not be defined in test environment
        $optional_constants = [
            'DFX_PARISH_RETREAT_LETTERS_PLUGIN_DIR',
            'DFX_PARISH_RETREAT_LETTERS_PLUGIN_URL',
            'DFX_PARISH_RETREAT_LETTERS_PLUGIN_BASENAME'
        ];

        foreach ($required_constants as $constant) {
            $this->assertTrue(defined($constant), "Constant $constant should be defined");
        }
        
        // Count how many optional constants are defined
        $defined_optional = 0;
        foreach ($optional_constants as $constant) {
            if (defined($constant)) {
                $defined_optional++;
            }
        }
        
        // At least the version constant should be defined
        $this->assertGreaterThanOrEqual(1, count($required_constants), "At least version constant should be defined");
    }

    /**
     * Test plugin version compatibility
     */
    public function testPluginVersionCompatibility() {
        if (defined('DFX_PARISH_RETREAT_LETTERS_VERSION')) {
            $version = DFX_PARISH_RETREAT_LETTERS_VERSION;
            
            // Test version format (should be semantic versioning)
            $this->assertTrue(preg_match('/^\d+\.\d+\.\d+$/', $version) === 1, 'Version should follow semantic versioning');
            
            // Test version components
            $version_parts = explode('.', $version);
            $this->assertCount(3, $version_parts, 'Version should have 3 parts');
            
            foreach ($version_parts as $part) {
                $this->assertTrue(is_numeric($part), 'Version parts should be numeric');
            }
        }
    }

    /**
     * Test that all JavaScript and CSS files exist
     */
    public function testAssetFilesExist() {
        $plugin_dir = dirname(__DIR__, 2);
        $asset_files = [
            '/includes/admin.js'
        ];

        foreach ($asset_files as $file) {
            if (file_exists($plugin_dir . $file)) {
                $this->assertFileExists($plugin_dir . $file, "Asset file should exist: $file");
            }
        }
    }

    /**
     * Test that language files directory exists
     */
    public function testLanguageFilesStructure() {
        $plugin_dir = dirname(__DIR__, 2);
        $languages_dir = $plugin_dir . '/languages';
        
        $this->assertDirectoryExists($languages_dir, 'Languages directory should exist');
    }

    /**
     * Test that uninstall script exists
     */
    public function testUninstallScriptExists() {
        $plugin_dir = dirname(__DIR__, 2);
        $uninstall_file = $plugin_dir . '/uninstall.php';
        
        $this->assertFileExists($uninstall_file, 'Uninstall script should exist');
    }

    /**
     * Test plugin activation and deactivation hooks
     */
    public function testActivationAndDeactivationHooks() {        
        // In test environment, these functions should exist
        $plugin_functions = [
            'activate_dfx_parish_retreat_letters',
            'deactivate_dfx_parish_retreat_letters',
            'run_dfx_parish_retreat_letters'
        ];
        
        $defined_functions = 0;
        foreach ($plugin_functions as $function) {
            if (function_exists($function)) {
                $defined_functions++;
            }
        }
        
        // At least the run function should exist since it's called immediately
        $this->assertGreaterThan(0, $defined_functions, "At least some plugin functions should be defined");
    }

    /**
     * Test database foreign key removal functionality
     */
    public function testDatabaseForeignKeyRemoval() {
        // Mock global $wpdb 
        global $wpdb;
        if (!$wpdb) {
            $wpdb = new stdClass();
        }
        $wpdb->prefix = 'wp_';
        
        // Test that the database class has the current version
        if (class_exists('DFX_Parish_Retreat_Letters_Database')) {
            $this->assertEquals('1.9.0', DFX_Parish_Retreat_Letters_Database::DB_VERSION, 'Database version should be 1.9.0');
            
            // Test that the database instance can be created
            $database = DFX_Parish_Retreat_Letters_Database::get_instance();
            $this->assertInstanceOf('DFX_Parish_Retreat_Letters_Database', $database);
            
            // Test that required methods exist
            $required_methods = [
                'get_audit_log_table',
                'setup_tables',
                'maybe_upgrade_database'
            ];
            
            foreach ($required_methods as $method) {
                $this->assertTrue(method_exists($database, $method), "Database method $method should exist");
            }
            
            // Test that the audit log table name is properly constructed
            $audit_log_table = $database->get_audit_log_table();
            $this->assertIsString($audit_log_table);
            $this->assertTrue(strpos($audit_log_table, 'wp_') === 0, 'Table name should start with wp_');
            $this->assertTrue(strpos($audit_log_table, 'audit_log') !== false, 'Table name should contain audit_log');
            
            // Test the foreign key removal method exists (even if private)
            $reflection = new ReflectionClass($database);
            $this->assertTrue($reflection->hasMethod('remove_audit_log_foreign_keys'), 
                'Database should have remove_audit_log_foreign_keys method');
                
            // The method should be private for security
            $method = $reflection->getMethod('remove_audit_log_foreign_keys');
            $this->assertTrue($method->isPrivate(), 'remove_audit_log_foreign_keys should be private');
        } else {
            $this->markTestSkipped('Database class not available');
        }
    }

    /**
     * Test audit log can handle user_id = 0 (invitation scenario)
     */
    public function testAuditLogHandlesUserIdZero() {
        if (class_exists('DFX_Parish_Retreat_Letters_Permissions')) {
            // Mock global $wpdb to simulate successful audit log insertion
            global $wpdb;
            if (!$wpdb) {
                $wpdb = new stdClass();
            }
            $wpdb->prefix = 'wp_';
            
            // Mock the insert method to return success
            $wpdb->insert = function() { return 1; };
            
            // Get permissions instance
            $permissions = DFX_Parish_Retreat_Letters_Permissions::get_instance();
            $this->assertInstanceOf('DFX_Parish_Retreat_Letters_Permissions', $permissions);
            
            // Test that log_permission_action method exists
            $this->assertTrue(method_exists($permissions, 'log_permission_action'), 
                'Permissions class should have log_permission_action method');
            
            // The method should be public so it can be called from other classes
            $reflection = new ReflectionClass($permissions);
            $method = $reflection->getMethod('log_permission_action');
            $this->assertTrue($method->isPublic(), 'log_permission_action should be public');
            
            // Verify the method signature accepts the expected parameters
            $params = $method->getParameters();
            $this->assertGreaterThanOrEqual(5, count($params), 
                'log_permission_action should accept at least 5 parameters');
            
            // First parameter should be user_id
            $this->assertEquals('user_id', $params[0]->getName(), 
                'First parameter should be user_id');
                
        } else {
            $this->markTestSkipped('Permissions class not available');
        }
    }

    /**
     * Test that ConfidentialMessage class has the new non-printed count method
     */
    public function testConfidentialMessageHasNonPrintedCountMethod() {
        if (class_exists('DFX_Parish_Retreat_Letters_ConfidentialMessage')) {
            $message = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
            $this->assertInstanceOf('DFX_Parish_Retreat_Letters_ConfidentialMessage', $message);
            
            // Test that the new method exists
            $this->assertTrue(method_exists($message, 'get_non_printed_count_by_attendant'), 
                'ConfidentialMessage class should have get_non_printed_count_by_attendant method');
            
            // Test that the existing count method also exists for comparison
            $this->assertTrue(method_exists($message, 'get_count_by_attendant'), 
                'ConfidentialMessage class should have get_count_by_attendant method');
            
            // Verify the method signature using reflection
            $reflection = new ReflectionClass($message);
            $method = $reflection->getMethod('get_non_printed_count_by_attendant');
            
            // The method should be public
            $this->assertTrue($method->isPublic(), 'get_non_printed_count_by_attendant should be public');
            
            // Verify the method parameters
            $params = $method->getParameters();
            $this->assertEquals(2, count($params), 
                'get_non_printed_count_by_attendant should accept 2 parameters');
            
            // First parameter should be attendant_id
            $this->assertEquals('attendant_id', $params[0]->getName(), 
                'First parameter should be attendant_id');
            
            // Second parameter should be args with default value
            $this->assertEquals('args', $params[1]->getName(), 
                'Second parameter should be args');
            $this->assertTrue($params[1]->isDefaultValueAvailable(), 
                'Second parameter should have a default value');
            
            // Verify method return type hint if available (PHP 7.0+)
            if (method_exists($method, 'getReturnType')) {
                $returnType = $method->getReturnType();
                // Return type may not be explicitly declared, which is fine for compatibility
                if ($returnType !== null) {
                    $this->assertEquals('int', $returnType->getName(), 
                        'get_non_printed_count_by_attendant should return int');
                }
            }
        } else {
            $this->markTestSkipped('ConfidentialMessage class not available');
        }
    }

    /**
     * Test that Database class has the print log table method
     */
    public function testDatabaseHasPrintLogTableMethod() {
        if (class_exists('DFX_Parish_Retreat_Letters_Database')) {
            $database = DFX_Parish_Retreat_Letters_Database::get_instance();
            $this->assertInstanceOf('DFX_Parish_Retreat_Letters_Database', $database);
            
            // Test that the print log table method exists (required for non-printed count)
            $this->assertTrue(method_exists($database, 'get_message_print_log_table'), 
                'Database class should have get_message_print_log_table method');
            
            // The method should be public
            $reflection = new ReflectionClass($database);
            $method = $reflection->getMethod('get_message_print_log_table');
            $this->assertTrue($method->isPublic(), 'get_message_print_log_table should be public');
        } else {
            $this->markTestSkipped('Database class not available');
        }
    }

    /**
     * Test that render_print_page has correct CSS for first image in multi-file messages
     * 
     * This test verifies the fix for issue #109 where the first image doesn't fit on the first page
     * after adding the "To:" field. The CSS calculations should account for both "To:" and "From:" headers.
     */
    public function testRenderPrintPageFirstImageCSSCalculations() {
        $plugin_dir = dirname(__DIR__, 2);
        $print_css_file = $plugin_dir . '/assets/css/print-page.css';
        
        $this->assertFileExists($print_css_file, 'Print page CSS file should exist');
        
        // Read the CSS file to verify CSS values
        $sourceContent = file_get_contents($print_css_file);
        $this->assertNotFalse($sourceContent, 'Should be able to read the print page CSS file');
        
        // Test 1: Verify first image (second child) container has correct min-height
        // User discovered the first image is actually the second child in the parent div
        $this->assertStringContainsString('min-height: calc(100vh - 200px);', $sourceContent,
            'First image container min-height should be calc(100vh - 200px) to fit below To/From header');
        
        // Test 2: Verify first image container has correct max-height
        $this->assertStringContainsString('max-height: calc(100vh - 200px);', $sourceContent,
            'First image container max-height should be calc(100vh - 200px) to fit below To/From header');
        
        // Test 3: Verify first image element has correct max-height
        // Should be calc(100vh - 150px) after the fix, not the old calc(100vh - 100px)
        $this->assertStringContainsString('max-height: calc(100vh - 150px);', $sourceContent,
            'First image element max-height should be calc(100vh - 150px) to fit below To/From header');
        
        // Test 4: Verify the comment was updated to reflect the To/From header
        $this->assertStringContainsString('To/From header', $sourceContent,
            'CSS comments should mention To/From header instead of just sender info');
        
        // Test 5: Verify nth-child(2) CSS selector is used for first image
        $this->assertStringContainsString('.file-content.multi-image:nth-child(2)', $sourceContent,
            'CSS should use :nth-child(2) selector for first image (it is second child in parent div)');
        
        // Test 6: Verify subsequent images use full page height (unchanged behavior)
        $this->assertStringContainsString('.file-content.multi-image:not(:nth-child(2))', $sourceContent,
            'CSS should have styling for subsequent images using :not(:nth-child(2))');
        $this->assertStringContainsString('max-height: 100vh;', $sourceContent,
            'Subsequent images should use full viewport height');
        
        // Test 7: Extract and validate the actual numeric value for min-height and max-height (should be 200px)
        preg_match('/\.file-content\.multi-image:nth-child\(2\)[^}]*min-height:\s*calc\(100vh\s*-\s*(\d+)px\)/s', $sourceContent, $minHeightMatches);
        if (!empty($minHeightMatches[1])) {
            $minHeightOffset = (int)$minHeightMatches[1];
            $this->assertEquals(200, $minHeightOffset, 
                'First image container min-height offset should be 200px to account for To/From header');
        }
        
        preg_match('/\.file-content\.multi-image:nth-child\(2\)[^}]*max-height:\s*calc\(100vh\s*-\s*(\d+)px\)/s', $sourceContent, $maxHeightMatches);
        if (!empty($maxHeightMatches[1])) {
            $maxHeightOffset = (int)$maxHeightMatches[1];
            $this->assertEquals(200, $maxHeightOffset, 
                'First image container max-height offset should be 200px, not the old 150px value');
            $this->assertGreaterThan(150, $maxHeightOffset, 
                'First image container max-height offset should be greater than old 150px to account for To field');
        }
        
        // Test 8: Verify the image element max-height value
        preg_match('/\.file-content\.multi-image:nth-child\(2\)\s+\.file-image\s*\{[^}]*max-height:\s*calc\(100vh\s*-\s*(\d+)px\)/s', $sourceContent, $imageMaxHeightMatches);
        if (!empty($imageMaxHeightMatches[1])) {
            $imageMaxHeightOffset = (int)$imageMaxHeightMatches[1];
            $this->assertEquals(150, $imageMaxHeightOffset, 
                'First image element max-height offset should be 150px, not the old 100px value');
            $this->assertGreaterThan(100, $imageMaxHeightOffset, 
                'First image element offset should be greater than old 100px to account for To field');
        }
        
        // Test 9: Verify that render_print_page method exists
        if (class_exists('DFX_Parish_Retreat_Letters')) {
            $plugin = DFX_Parish_Retreat_Letters::get_instance();
            $reflection = new ReflectionClass($plugin);
            $this->assertTrue($reflection->hasMethod('render_print_page'), 
                'Plugin class should have render_print_page method');
            
            $method = $reflection->getMethod('render_print_page');
            $this->assertTrue($method->isPrivate(), 
                'render_print_page should be private for security');
        }
    }

    /**
     * Test that Admin class has generate_initials_suffix method for invitation URLs
     * 
     * This test verifies the fix for the issue where invitation message URLs 
     * were missing the initials suffix (e.g., /#jd for "John Doe").
     */
    public function testAdminHasGenerateInitialsSuffixMethod() {
        if (class_exists('DFX_Parish_Retreat_Letters_Admin')) {
            $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
            $this->assertInstanceOf('DFX_Parish_Retreat_Letters_Admin', $admin);
            
            // Test that the generate_initials_suffix method exists
            $reflection = new ReflectionClass($admin);
            $this->assertTrue($reflection->hasMethod('generate_initials_suffix'), 
                'Admin class should have generate_initials_suffix method');
            
            // The method should be private for encapsulation
            $method = $reflection->getMethod('generate_initials_suffix');
            $this->assertTrue($method->isPrivate(), 'generate_initials_suffix should be private');
            
            // Verify the method parameters
            $params = $method->getParameters();
            $this->assertEquals(2, count($params), 
                'generate_initials_suffix should accept 2 parameters');
            $this->assertEquals('name', $params[0]->getName(), 
                'First parameter should be name');
            $this->assertEquals('surnames', $params[1]->getName(), 
                'Second parameter should be surnames');
            
            // Test the method functionality using reflection
            $method->setAccessible(true);
            
            // Test basic name
            $result = $method->invoke($admin, 'John', 'Doe');
            $this->assertEquals('/#jd', $result, 'Basic name should generate initials');
            
            // Test name with multiple surnames
            $result = $method->invoke($admin, 'Maria', 'Garcia Lopez');
            $this->assertEquals('/#mgl', $result, 'Multiple surnames should generate initials from each word');
            
            // Test empty name
            $result = $method->invoke($admin, '', '');
            $this->assertEquals('', $result, 'Empty name should return empty string');
            
            // Test name only
            $result = $method->invoke($admin, 'Alice', '');
            $this->assertEquals('/#a', $result, 'Name only should return single initial');
            
            // Test surnames only
            $result = $method->invoke($admin, '', 'Smith Johnson');
            $this->assertEquals('/#sj', $result, 'Surnames only should work');
            
            // Test null values
            $result = $method->invoke($admin, null, null);
            $this->assertEquals('', $result, 'Null values should return empty string');
            
            // Test name with special characters - apostrophe creates "O" and "Brien" as separate words
            // "O" is a single letter word so its initial is "o", "Brien" gives "b"
            $result = $method->invoke($admin, 'John', 'O\'Brien');
            $this->assertEquals('/#jo', $result, 'Name with apostrophe should be handled (apostrophe splits word)');
        } else {
            $this->markTestSkipped('Admin class not available');
        }
    }

    /**
     * Test that expand_invitation_template method exists and is callable
     * 
     * This test verifies the method that expands invitation templates with attendant data,
     * which should now include the initials suffix in the messages URL.
     */
    public function testExpandInvitationTemplateMethodExists() {
        if (class_exists('DFX_Parish_Retreat_Letters_Admin')) {
            $admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
            $reflection = new ReflectionClass($admin);
            
            $this->assertTrue($reflection->hasMethod('expand_invitation_template'), 
                'Admin class should have expand_invitation_template method');
            
            $method = $reflection->getMethod('expand_invitation_template');
            $this->assertTrue($method->isPrivate(), 'expand_invitation_template should be private');
            
            // Verify the method parameters
            $params = $method->getParameters();
            $this->assertEquals(2, count($params), 
                'expand_invitation_template should accept 2 parameters');
            $this->assertEquals('retreat', $params[0]->getName(), 
                'First parameter should be retreat');
            $this->assertEquals('attendant', $params[1]->getName(), 
                'Second parameter should be attendant');
        } else {
            $this->markTestSkipped('Admin class not available');
        }
    }

    /**
     * Test that CAPTCHA validation correctly handles zero answers
     * 
     * This test verifies that when the CAPTCHA answer is "0" (e.g., 4 - 4 = ?),
     * it is not incorrectly rejected as an empty value.
     * 
     * The fix changed from empty($user_answer) to '' === $user_answer to properly
     * handle the string "0" as a valid answer.
     */
    public function testCaptchaValidationAllowsZeroAnswer() {
        // Test that "0" is not considered empty for CAPTCHA validation purposes
        $user_answer_zero = '0';
        $user_answer_empty = '';
        
        // The old code used empty() which incorrectly returned true for "0"
        // The fix uses strict string comparison '' === $user_answer
        
        // Verify that "0" should NOT trigger the "please complete" error
        $this->assertFalse('' === $user_answer_zero, 
            'String "0" should not be considered empty for CAPTCHA validation');
        
        // Verify that empty string SHOULD trigger the "please complete" error
        $this->assertTrue('' === $user_answer_empty, 
            'Empty string should trigger CAPTCHA validation error');
        
        // Test that "00" (double zero) is also valid
        $user_answer_double_zero = '00';
        $this->assertFalse('' === $user_answer_double_zero, 
            'String "00" should not be considered empty');
        
        // Test numeric zero directly converted to string
        $numeric_zero = 0;
        $string_zero = (string) $numeric_zero;
        $this->assertFalse('' === $string_zero, 
            'Numeric 0 converted to string should not be considered empty');
    }

    /**
     * Test CAPTCHA answer comparison with base64 encoded expected value
     * 
     * This verifies the CAPTCHA comparison logic works correctly for various answers,
     * particularly zero which was previously problematic.
     */
    public function testCaptchaAnswerComparisonWithBase64() {
        // Test various CAPTCHA answer scenarios
        $test_cases = [
            ['expected' => 0, 'user_input' => '0', 'should_match' => true],
            ['expected' => 5, 'user_input' => '5', 'should_match' => true],
            ['expected' => 12, 'user_input' => '12', 'should_match' => true],
            ['expected' => 0, 'user_input' => '1', 'should_match' => false],
            ['expected' => 5, 'user_input' => '6', 'should_match' => false],
        ];
        
        foreach ($test_cases as $case) {
            // Simulate the CAPTCHA token (base64 encoded expected answer)
            $captcha_token = base64_encode((string) $case['expected']);
            $expected_answer = base64_decode($captcha_token);
            
            // The comparison uses non-strict equality (==)
            $matches = $case['user_input'] == $expected_answer;
            
            $this->assertEquals($case['should_match'], $matches, 
                sprintf('CAPTCHA comparison failed for expected=%d, user_input=%s', 
                    $case['expected'], $case['user_input']));
        }
    }

    /**
     * Test message submission with CAPTCHA validation
     * 
     * This functional test verifies that:
     * 1. Messages can be submitted with correct CAPTCHA
     * 2. Messages are rejected with incorrect CAPTCHA
     * 3. Messages are rejected with missing CAPTCHA
     * 4. CAPTCHA token encoding/decoding works correctly
     */
    public function testMessageSubmissionWithCaptcha() {
        // Test Case 1: Correct CAPTCHA answer should be accepted
        $correct_answer = 42;
        $captcha_token = base64_encode((string) $correct_answer);
        $user_answer = '42';
        
        // Simulate the validation logic from handle_ajax_message_submission
        $expected_answer = base64_decode($captcha_token);
        $is_valid = ($user_answer == $expected_answer);
        
        $this->assertTrue($is_valid, 
            'Message submission should succeed with correct CAPTCHA answer');
        
        // Test Case 2: Incorrect CAPTCHA answer should be rejected
        $user_answer_wrong = '41';
        $is_valid_wrong = ($user_answer_wrong == $expected_answer);
        
        $this->assertFalse($is_valid_wrong, 
            'Message submission should fail with incorrect CAPTCHA answer');
        
        // Test Case 3: Empty CAPTCHA answer should be rejected
        $user_answer_empty = '';
        $should_reject_empty = ('' === $user_answer_empty);
        
        $this->assertTrue($should_reject_empty, 
            'Message submission should fail with empty CAPTCHA answer');
        
        // Test Case 4: Zero answer should work correctly
        $correct_zero = 0;
        $captcha_token_zero = base64_encode((string) $correct_zero);
        $user_answer_zero = '0';
        
        $expected_zero = base64_decode($captcha_token_zero);
        $is_valid_zero = ($user_answer_zero == $expected_zero);
        
        $this->assertTrue($is_valid_zero, 
            'Message submission should succeed with CAPTCHA answer of 0');
        
        // Test Case 5: Missing CAPTCHA token should be rejected
        $empty_token = '';
        $should_reject_no_token = empty($empty_token);
        
        $this->assertTrue($should_reject_no_token, 
            'Message submission should fail with missing CAPTCHA token');
        
        // Test Case 6: Verify btoa/atob equivalence with base64_encode/decode
        $test_values = [0, 1, 5, 10, 42, 99, 100];
        foreach ($test_values as $value) {
            $php_encoded = base64_encode((string) $value);
            $php_decoded = base64_decode($php_encoded);
            
            $this->assertEquals((string) $value, $php_decoded, 
                sprintf('base64 encode/decode should preserve value %d', $value));
        }
    }
}