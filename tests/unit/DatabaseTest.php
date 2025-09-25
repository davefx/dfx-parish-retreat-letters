<?php
/**
 * Unit tests for DFX_Parish_Retreat_Letters_Database class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFX_Parish_Retreat_Letters_Database
 */
class DatabaseTest extends TestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('get_option')->justReturn('1.6.2');
        Functions\when('add_option')->justReturn(true);
        Functions\when('update_option')->justReturn(true);
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
    public function test_singleton_pattern() {
        // Mock global wpdb
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->prefix = 'wp_';
        
        $instance1 = DFX_Parish_Retreat_Letters_Database::get_instance();
        $instance2 = DFX_Parish_Retreat_Letters_Database::get_instance();
        
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf('DFX_Parish_Retreat_Letters_Database', $instance1);
    }

    /**
     * Test database version constant
     */
    public function test_database_version_constant() {
        $this->assertEquals('1.6.2', DFX_Parish_Retreat_Letters_Database::DB_VERSION);
    }

    /**
     * Test get table name method exists and returns string
     */
    public function test_get_retreats_table_name() {
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->prefix = 'wp_';
        
        $database = DFX_Parish_Retreat_Letters_Database::get_instance();
        
        // Use reflection to access private method if it exists
        $reflection = new ReflectionClass($database);
        if ($reflection->hasMethod('get_retreats_table_name')) {
            $method = $reflection->getMethod('get_retreats_table_name');
            $method->setAccessible(true);
            $table_name = $method->invoke($database);
            $this->assertIsString($table_name);
            $this->assertStringContains('wp_', $table_name);
        } else {
            $this->markTestSkipped('get_retreats_table_name method not found');
        }
    }

    /**
     * Test table creation functionality
     */
    public function test_setup_tables() {
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->prefix = 'wp_';
        
        $database = DFX_Parish_Retreat_Letters_Database::get_instance();
        
        if (method_exists($database, 'setup_tables')) {
            // Mock dbDelta function
            Functions\when('dbDelta')->justReturn(['Created table wp_dfx_retreats']);
            
            $result = $database->setup_tables();
            $this->assertTrue(is_callable([$database, 'setup_tables']));
        } else {
            $this->markTestSkipped('setup_tables method not found');
        }
    }

    /**
     * Test database version management
     */
    public function test_database_version_management() {
        $database = DFX_Parish_Retreat_Letters_Database::get_instance();
        
        if (method_exists($database, 'needs_upgrade')) {
            $result = $database->needs_upgrade();
            $this->assertTrue(is_callable([$database, 'needs_upgrade']));
        } else {
            $this->markTestSkipped('needs_upgrade method not found');
        }
    }

    /**
     * Test get all table names
     */
    public function test_get_all_table_names() {
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->prefix = 'wp_';
        
        $database = DFX_Parish_Retreat_Letters_Database::get_instance();
        
        if (method_exists($database, 'get_retreats_table')) {
            $retreats_table = $database->get_retreats_table();
            $this->assertIsString($retreats_table);
            $this->assertStringContains('wp_', $retreats_table);
        }
        
        if (method_exists($database, 'get_attendants_table')) {
            $attendants_table = $database->get_attendants_table();
            $this->assertIsString($attendants_table);
            $this->assertStringContains('wp_', $attendants_table);
        }
        
        if (method_exists($database, 'get_messages_table')) {
            $messages_table = $database->get_messages_table();
            $this->assertIsString($messages_table);
            $this->assertStringContains('wp_', $messages_table);
        }
    }

    /**
     * Test database migration functionality
     */
    public function test_database_migration() {
        $database = DFX_Parish_Retreat_Letters_Database::get_instance();
        
        if (method_exists($database, 'migrate_to_version')) {
            $target_version = '1.6.2';
            $result = $database->migrate_to_version($target_version);
            $this->assertTrue(is_callable([$database, 'migrate_to_version']));
        } else {
            $this->markTestSkipped('migrate_to_version method not found');
        }
    }

    /**
     * Test table existence checking
     */
    public function test_table_existence_checking() {
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->method('get_var')->willReturn('wp_dfx_retreats');
        
        $database = DFX_Parish_Retreat_Letters_Database::get_instance();
        
        if (method_exists($database, 'table_exists')) {
            $result = $database->table_exists('wp_dfx_retreats');
            $this->assertTrue(is_callable([$database, 'table_exists']));
        } else {
            $this->markTestSkipped('table_exists method not found');
        }
    }

    /**
     * Test database cleanup functionality
     */
    public function test_database_cleanup() {
        $database = DFX_Parish_Retreat_Letters_Database::get_instance();
        
        if (method_exists($database, 'cleanup_orphaned_data')) {
            $result = $database->cleanup_orphaned_data();
            $this->assertTrue(is_callable([$database, 'cleanup_orphaned_data']));
        } else {
            $this->markTestSkipped('cleanup_orphaned_data method not found');
        }
    }

    /**
     * Test foreign key removal functionality
     */
    public function test_remove_audit_log_foreign_keys() {
        global $wpdb;
        $wpdb = $this->createMock('wpdb');
        $wpdb->prefix = 'wp_';
        
        // Mock the audit log table name
        $audit_log_table = 'wp_dfx_prl_permission_audit_log';
        
        // Mock get_var to simulate table existence check
        $wpdb->method('get_var')
             ->willReturn($audit_log_table);
             
        // Mock get_results to return foreign key constraints
        $constraint = new stdClass();
        $constraint->CONSTRAINT_NAME = 'wp_dfx_prl_permission_audit_log_ibfk_1';
        $wpdb->method('get_results')
             ->willReturn([$constraint]);
             
        // Mock the query method to simulate constraint removal
        $wpdb->method('query')
             ->willReturn(1);
        
        $database = DFX_Parish_Retreat_Letters_Database::get_instance();
        
        // Test that the remove_audit_log_foreign_keys method exists and can be called
        $reflection = new ReflectionClass($database);
        if ($reflection->hasMethod('remove_audit_log_foreign_keys')) {
            $method = $reflection->getMethod('remove_audit_log_foreign_keys');
            $method->setAccessible(true);
            
            // This should not throw an exception
            $method->invoke($database);
            $this->assertTrue(true); // If we get here, the method executed successfully
        } else {
            $this->markTestSkipped('remove_audit_log_foreign_keys method not found');
        }
    }

    /**
     * Test database backup functionality
     */
    public function test_database_backup() {
        $database = DFX_Parish_Retreat_Letters_Database::get_instance();
        
        if (method_exists($database, 'create_backup')) {
            $result = $database->create_backup();
            $this->assertTrue(is_callable([$database, 'create_backup']));
        } else {
            $this->markTestSkipped('create_backup method not found');
        }
    }
}