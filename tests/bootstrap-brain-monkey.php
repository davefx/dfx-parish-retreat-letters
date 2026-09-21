<?php
/**
 * PHPUnit bootstrap file for Brain Monkey tests
 *
 * This bootstrap is for unit tests that use Brain Monkey for WordPress function mocking
 *
 * @package DFXPRL
 */

// Set error reporting. E_DEPRECATED is excluded because several tests call
// Reflection*::setAccessible(), which PHP 8.1+ deprecates (it is a harmless no-op there);
// the notices would otherwise flood the test output without affecting results.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 1);

// Define test environment
define('PHPUNIT_RUNNING', true);

// Mock WordPress constants that might be checked. ABSPATH points at tests/fixtures/ so that
// code doing `require_once ABSPATH . 'wp-admin/includes/upgrade.php'` (e.g. dbDelta) resolves
// to the stub fixture instead of a missing file.
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

// Patchwork must be loaded and initialized BEFORE anything else, so its stream wrapper can
// instrument every file included afterwards (including the WordPress function fallbacks below).
// Brain Monkey's lazy loader does not reliably initialize Patchwork on its own here, which left
// the whole Brain Monkey suite failing with "DefinedTooEarly". Requiring Patchwork.php first fixes it.
if (file_exists(dirname(__DIR__) . '/vendor/antecedent/patchwork/Patchwork.php')) {
    require_once dirname(__DIR__) . '/vendor/antecedent/patchwork/Patchwork.php';
}

// Include composer autoloader (Brain Monkey needs to be loaded before any WordPress functions)
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// Initialize Brain Monkey - this must be done BEFORE any WordPress functions are defined
// Load Brain Monkey functions
require_once dirname(__DIR__) . '/vendor/brain/monkey/inc/api.php';

// Fallback WordPress function definitions must live in a separate file that is required
// AFTER Patchwork (loaded via the Composer autoloader above). Functions defined directly
// in this bootstrap file cannot be instrumented by Patchwork, so Brain Monkey would fail
// to redefine them with "DefinedTooEarly" the moment a test stubs one (e.g. is_admin()).
require_once __DIR__ . '/wp-function-fallbacks.php';

// Provide a stub wpdb class so plugin code and PHPUnit mocks have a class to work with.
require_once __DIR__ . '/wpdb-stub.php';

// Define the plugin constants the class files may reference, then load the plugin classes.
// The advanced (Brain Monkey) test suite expects these classes to be available without each
// test requiring them individually.
$dfxprl_plugin_dir = dirname(__DIR__) . '/';

if (!defined('DFXPRL_VERSION')) {
    // Read the real version from the main plugin file so version-related tests stay in sync
    // with the actual plugin version without hardcoding it here.
    $dfxprl_main_file = $dfxprl_plugin_dir . 'dfx-parish-retreat-letters.php';
    $dfxprl_version   = 'test';
    if (is_readable($dfxprl_main_file)
        && preg_match("/define\\(\\s*'DFXPRL_VERSION',\\s*'([^']+)'/", file_get_contents($dfxprl_main_file), $dfxprl_m)
    ) {
        $dfxprl_version = $dfxprl_m[1];
    }
    define('DFXPRL_VERSION', $dfxprl_version);
    unset($dfxprl_main_file, $dfxprl_version, $dfxprl_m);
}
if (!defined('DFXPRL_PLUGIN_DIR')) {
    define('DFXPRL_PLUGIN_DIR', $dfxprl_plugin_dir);
}
if (!defined('DFXPRL_PLUGIN_URL')) {
    define('DFXPRL_PLUGIN_URL', 'http://example.com/wp-content/plugins/dfx-parish-retreat-letters/');
}
if (!defined('DFXPRL_PLUGIN_BASENAME')) {
    define('DFXPRL_PLUGIN_BASENAME', 'dfx-parish-retreat-letters/dfx-parish-retreat-letters.php');
}

// Plugin classes are flat (no inheritance) and contain no top-level code, so they can be
// loaded in any order. Glob keeps this in sync as class files are added or removed.
foreach (glob($dfxprl_plugin_dir . 'includes/class-*.php') as $dfxprl_class_file) {
    require_once $dfxprl_class_file;
}
unset($dfxprl_class_file);