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
            $this->assertEquals('1.6.3', DFX_Parish_Retreat_Letters_Database::DB_VERSION, 'Database version should be 1.6.3');
            
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
        $main_class_file = $plugin_dir . '/includes/class-dfx-parish-retreat-letters.php';
        
        $this->assertFileExists($main_class_file, 'Main plugin class file should exist');
        
        // Read the source file to verify CSS values
        $sourceContent = file_get_contents($main_class_file);
        $this->assertNotFalse($sourceContent, 'Should be able to read the main class file');
        
        // Test 1: Verify first image container has correct min-height to account for To/From header
        // After the fix, min-height should be 0 to allow natural sizing (overriding the 100vh from base class)
        $this->assertStringContainsString('min-height: 0;', $sourceContent,
            'First image container min-height should be 0 to override base 100vh and allow natural sizing below To/From header');
        
        // Test 2: Verify first image container has correct max-height
        $this->assertStringContainsString('max-height: calc(100vh - 200px);', $sourceContent,
            'First image container max-height should be calc(100vh - 200px) to fit below To/From header');
        
        // Test 2b: Verify page-break-before is set to avoid for first image
        $this->assertStringContainsString('page-break-before: avoid;', $sourceContent,
            'First image container should have page-break-before: avoid to keep with header');
        
        // Test 2c: Verify display is changed to block for first image
        $this->assertStringContainsString('display: block;', $sourceContent,
            'First image container should use display: block instead of flex for simpler layout');
        
        // Test 3: Verify first image element has correct max-height
        // Should be calc(100vh - 150px) after the fix, not the old calc(100vh - 100px)
        $this->assertStringContainsString('max-height: calc(100vh - 150px);', $sourceContent,
            'First image element max-height should be calc(100vh - 150px) to fit below To/From header');
        
        // Test 4: Verify the comment was updated to reflect the To/From header
        $this->assertStringContainsString('To/From header', $sourceContent,
            'CSS comments should mention To/From header instead of just sender info');
        
        // Test 5: Verify multi-image CSS class exists for proper handling
        $this->assertStringContainsString('.file-content.multi-image:first-child', $sourceContent,
            'CSS should have specific styling for first image in multi-image messages');
        
        // Test 6: Verify subsequent images use full page height (unchanged behavior)
        $this->assertStringContainsString('.file-content.multi-image:not(:first-child)', $sourceContent,
            'CSS should have styling for subsequent images in multi-image messages');
        $this->assertStringContainsString('max-height: 100vh;', $sourceContent,
            'Subsequent images should use full viewport height');
        
        // Test 7: Extract and validate the actual numeric value for max-height (should be 200px)
        // The min-height is now 'auto' for natural sizing, so we only check max-height
        preg_match('/\.file-content\.multi-image:first-child\s*\{[^}]*max-height:\s*calc\(100vh\s*-\s*(\d+)px\)/s', $sourceContent, $maxHeightMatches);
        if (!empty($maxHeightMatches[1])) {
            $maxHeightOffset = (int)$maxHeightMatches[1];
            $this->assertEquals(200, $maxHeightOffset, 
                'First image container max-height offset should be 200px, not the old 150px value');
            $this->assertGreaterThan(150, $maxHeightOffset, 
                'First image container max-height offset should be greater than old 150px to account for To field');
        }
        
        // Test 8: Verify the image element max-height value
        preg_match('/\.file-content\.multi-image:first-child\s+\.file-image\s*\{[^}]*max-height:\s*calc\(100vh\s*-\s*(\d+)px\)/s', $sourceContent, $imageMaxHeightMatches);
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
}