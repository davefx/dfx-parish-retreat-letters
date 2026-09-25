<?php
/**
 * Unit tests for DFXPRL main class
 *
 * @package DFXPRL
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFXPRL
 */
class DFXParishRetreatLettersTest extends TestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions. plugin_dir_path points at the real plugin root so the
        // unconditional require_once calls in load_dependencies() resolve to the class files
        // already loaded by the bootstrap (require_once then becomes a no-op).
        Functions\when('plugin_dir_path')->justReturn(dirname(__DIR__, 2) . '/');
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
        if (!defined('DFXPRL_VERSION')) {
            define('DFXPRL_VERSION', '26.09.26.1');
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
        $instance1 = DFXPRL::get_instance();
        $instance2 = DFXPRL::get_instance();
        
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf('DFXPRL', $instance1);
    }

    /**
     * Test plugin version is set correctly
     */
    public function test_plugin_version_is_set() {
        $plugin = DFXPRL::get_instance();
        
        $reflection = new ReflectionClass($plugin);
        $version_property = $reflection->getProperty('version');
        $version_property->setAccessible(true);
        $version = $version_property->getValue($plugin);

        // The plugin copies DFXPRL_VERSION into its version property; assert against the
        // constant rather than a hardcoded number so version bumps do not break this test.
        $this->assertEquals(DFXPRL_VERSION, $version);
    }

    /**
     * Test plugin name is set correctly
     */
    public function test_plugin_name_is_set() {
        $plugin = DFXPRL::get_instance();
        
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
        $plugin = DFXPRL::get_instance();
        
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
        $plugin = DFXPRL::get_instance();
        
        $this->assertTrue(method_exists($plugin, 'run'));
        $this->assertTrue(is_callable([$plugin, 'run']));
    }

    /**
     * Test maybe_load_plugin_textdomain method exists and is callable
     */
    public function test_maybe_load_plugin_textdomain_method_exists() {
        $plugin = DFXPRL::get_instance();
        
        $this->assertTrue(method_exists($plugin, 'load_plugin_textdomain'));
        $this->assertTrue(is_callable([$plugin, 'load_plugin_textdomain']));
    }

    /**
     * Test translation loading is properly hooked
     */
    public function test_translation_loading_hooked() {
        // Capture add_action calls. Using an alias (rather than expect()) keeps this robust
        // against the singleton constructor's own add_action calls and the setUp when() stub.
        $hooked = array();
        Functions\when('add_action')->alias(function ($hook, $callback = null) use (&$hooked) {
            $hooked[$hook][] = $callback;
            return true;
        });

        $plugin = DFXPRL::get_instance();

        // Reset so we only assert on what run() registers.
        $hooked = array();
        $plugin->run();

        // run() registers the textdomain loader on the 'init' hook (WordPress 6.7+ convention).
        $this->assertArrayHasKey('init', $hooked);
        $this->assertSame(array($plugin, 'load_plugin_textdomain'), $hooked['init'][0]);
    }
}
