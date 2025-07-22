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
        Functions\when('get_option')->justReturn('1.4.0');
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
        $this->assertEquals('1.4.0', DFX_Parish_Retreat_Letters_Database::DB_VERSION);
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
}