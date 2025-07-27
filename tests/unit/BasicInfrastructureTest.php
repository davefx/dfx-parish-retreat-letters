<?php
/**
 * Basic infrastructure test to verify PHPUnit works
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;

/**
 * Basic test class to verify test infrastructure
 */
class BasicInfrastructureTest extends TestCase {

    /**
     * Test that PHPUnit is working
     */
    public function testPhpUnitIsWorking() {
        $this->assertTrue(true);
        $this->assertEquals(1, 1);
    }

    /**
     * Test PHP version compatibility
     */
    public function testPhpVersionCompatibility() {
        $this->assertGreaterThanOrEqual('7.4.0', PHP_VERSION);
    }

    /**
     * Test that plugin constants can be defined
     */
    public function testPluginConstantsCanBeDefined() {
        // Test that we can define the constants
        if (!defined('DFX_PARISH_RETREAT_LETTERS_VERSION')) {
            define('DFX_PARISH_RETREAT_LETTERS_VERSION', '25.7.27');
        }
        
        $this->assertTrue(defined('DFX_PARISH_RETREAT_LETTERS_VERSION'));
        $this->assertEquals('25.7.27', DFX_PARISH_RETREAT_LETTERS_VERSION);
    }

    /**
     * Test that all required class files exist
     */
    public function testRequiredClassFilesExist() {
        $plugin_dir = dirname(__DIR__, 2);
        $required_files = [
            '/includes/class-dfx-parish-retreat-letters.php',
            '/includes/class-database.php',
            '/includes/class-retreat.php',
            '/includes/class-admin.php',
            '/includes/class-security.php'
        ];

        foreach ($required_files as $file) {
            $this->assertFileExists($plugin_dir . $file, "Required file missing: $file");
        }
    }
}