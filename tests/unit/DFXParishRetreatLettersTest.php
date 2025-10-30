<?php
/**
 * Unit tests for DFX_Parish_Retreat_Letters main class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFX_Parish_Retreat_Letters
 */
class DFXParishRetreatLettersTest extends TestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('plugin_dir_path')->justReturn('/path/to/plugin/');
        Functions\when('plugin_dir_url')->justReturn('http://example.com/wp-content/plugins/dfx-parish-retreat-letters/');
        Functions\when('plugin_basename')->justReturn('dfx-parish-retreat-letters/dfx-parish-retreat-letters.php');
        Functions\when('get_locale')->justReturn('en_US');
        Functions\when('file_exists')->justReturn(false);
        Functions\when('__')->returnArg();
        Functions\when('is_admin')->justReturn(true);
        Functions\when('add_action')->justReturn(true);
        Functions\when('add_filter')->justReturn(true);
        Functions\when('load_plugin_textdomain')->justReturn(true);
        
        // Define constants if not already defined
        if (!defined('DFX_PARISH_RETREAT_LETTERS_VERSION')) {
            define('DFX_PARISH_RETREAT_LETTERS_VERSION', '25.10.29');
        }
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
        $instance1 = DFX_Parish_Retreat_Letters::get_instance();
        $instance2 = DFX_Parish_Retreat_Letters::get_instance();
        
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf('DFX_Parish_Retreat_Letters', $instance1);
    }

    /**
     * Test plugin version is set correctly
     */
    public function test_plugin_version_is_set() {
        $plugin = DFX_Parish_Retreat_Letters::get_instance();
        
        $reflection = new ReflectionClass($plugin);
        $version_property = $reflection->getProperty('version');
        $version_property->setAccessible(true);
        $version = $version_property->getValue($plugin);
        
        $this->assertEquals('25.10.29', $version);
    }

    /**
     * Test plugin name is set correctly
     */
    public function test_plugin_name_is_set() {
        $plugin = DFX_Parish_Retreat_Letters::get_instance();
        
        $reflection = new ReflectionClass($plugin);
        $name_property = $reflection->getProperty('plugin_name');
        $name_property->setAccessible(true);
        $name = $name_property->getValue($plugin);
        
        $this->assertEquals('dfx-parish-retreat-letters', $name);
    }

    /**
     * Test database instance is initialized
     */
    public function test_database_instance_initialized() {
        $plugin = DFX_Parish_Retreat_Letters::get_instance();
        
        $reflection = new ReflectionClass($plugin);
        $database_property = $reflection->getProperty('database');
        $database_property->setAccessible(true);
        $database = $database_property->getValue($plugin);
        
        $this->assertNotNull($database);
    }

    /**
     * Test run method exists and is callable
     */
    public function test_run_method_exists() {
        $plugin = DFX_Parish_Retreat_Letters::get_instance();
        
        $this->assertTrue(method_exists($plugin, 'run'));
        $this->assertTrue(is_callable([$plugin, 'run']));
    }

    /**
     * Test maybe_load_plugin_textdomain method exists and is callable
     */
    public function test_maybe_load_plugin_textdomain_method_exists() {
        $plugin = DFX_Parish_Retreat_Letters::get_instance();
        
        $this->assertTrue(method_exists($plugin, 'maybe_load_plugin_textdomain'));
        $this->assertTrue(is_callable([$plugin, 'maybe_load_plugin_textdomain']));
    }

    /**
     * Test translation loading is properly hooked
     */
    public function test_translation_loading_hooked() {
        // Mock add_action to capture the hook registration
        Functions\expect('add_action')
            ->once()
            ->with('plugins_loaded', \Mockery::type('array'));
        
        $plugin = DFX_Parish_Retreat_Letters::get_instance();
        $plugin->run();
    }
}
