<?php
/**
 * Tests for the global settings functionality.
 */

use PHPUnit\Framework\TestCase;

/**
 * Global Settings Test
 */
class GlobalSettingsTest extends TestCase {

    public function setUp(): void {
        parent::setUp();
        
        // Only run tests if the plugin classes are available
        if (!class_exists('DFX_Parish_Retreat_Letters_GlobalSettings') || 
            !class_exists('DFX_Parish_Retreat_Letters_Database')) {
            $this->markTestSkipped('Plugin classes not available for testing');
        }
    }

    public function test_global_settings_class_exists() {
        $this->assertTrue(class_exists('DFX_Parish_Retreat_Letters_GlobalSettings'));
    }

    public function test_global_settings_singleton() {
        if (!class_exists('DFX_Parish_Retreat_Letters_GlobalSettings')) {
            $this->markTestSkipped('GlobalSettings class not available');
        }

        $instance1 = DFX_Parish_Retreat_Letters_GlobalSettings::get_instance();
        $instance2 = DFX_Parish_Retreat_Letters_GlobalSettings::get_instance();
        $this->assertSame($instance1, $instance2);
    }

    public function test_global_settings_uses_wordpress_options() {
        if (!class_exists('DFX_Parish_Retreat_Letters_GlobalSettings')) {
            $this->markTestSkipped('GlobalSettings class not available');
        }

        // Test that the GlobalSettings class has the expected option prefix constant
        $this->assertTrue(defined('DFX_Parish_Retreat_Letters_GlobalSettings::OPTION_PREFIX'));
        $this->assertEquals('dfx_prl_global_', DFX_Parish_Retreat_Letters_GlobalSettings::OPTION_PREFIX);
    }

    public function test_database_version_updated() {
        if (!class_exists('DFX_Parish_Retreat_Letters_Database')) {
            $this->markTestSkipped('Database class not available');
        }

        $this->assertEquals('1.8.0', DFX_Parish_Retreat_Letters_Database::DB_VERSION);
    }

    public function test_retreat_model_has_css_sanitization() {
        if (!class_exists('DFX_Parish_Retreat_Letters_Retreat')) {
            $this->markTestSkipped('Retreat class not available');
        }

        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        // Test that the retreat model can handle CSS data
        // This is tested indirectly by checking that the sanitize methods exist in the class
        $reflection = new ReflectionClass($retreat);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PRIVATE);
        
        $method_names = array_map(function($method) {
            return $method->getName();
        }, $methods);
        
        $this->assertContains('sanitize_css', $method_names);
    }

    public function test_admin_has_global_settings_methods() {
        if (!class_exists('DFX_Parish_Retreat_Letters_Admin')) {
            $this->markTestSkipped('Admin class not available');
        }

        // Test that the method exists without instantiating to avoid file system issues
        $reflection = new ReflectionClass('DFX_Parish_Retreat_Letters_Admin');
        $this->assertTrue($reflection->hasMethod('global_settings_page'));
        $this->assertTrue($reflection->hasMethod('handle_global_settings_page_submissions'));
    }
}