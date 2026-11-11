<?php
/**
 * Basic infrastructure test to verify PHPUnit works
 *
 * @package DFXPRL
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
        // The constant must match the Version header of the plugin, whichever version that is
        preg_match('/^\s*\*\s*Version:\s*(\S+)/m', file_get_contents(dirname(__DIR__, 2) . '/dfx-parish-retreat-letters.php'), $header);
        $this->assertNotEmpty($header[1] ?? '', 'The plugin header has no Version');

        if (!defined('DFXPRL_VERSION')) {
            define('DFXPRL_VERSION', $header[1]);
        }

        $this->assertTrue(defined('DFXPRL_VERSION'));
        $this->assertEquals($header[1], DFXPRL_VERSION);
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
