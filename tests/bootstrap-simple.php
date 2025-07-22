<?php
/**
 * Simple PHPUnit bootstrap file for basic testing
 *
 * This bootstrap is for testing that doesn't require WordPress
 *
 * @package DFX_Parish_Retreat_Letters
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define test environment
define('PHPUNIT_RUNNING', true);

// Mock WordPress constants that might be checked
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

// Mock basic WordPress functions that are commonly used
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) {
        return basename(dirname($file)) . '/' . basename($file);
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $function) {
        // Mock function - do nothing in tests
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $function) {
        // Mock function - do nothing in tests
    }
}

if (!function_exists('did_action')) {
    function did_action($action) {
        return 0;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $function, $priority = 10, $accepted_args = 1) {
        // Mock function - do nothing in tests
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $function, $priority = 10, $accepted_args = 1) {
        // Mock function - do nothing in tests
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('add_option')) {
    function add_option($option, $value, $deprecated = '', $autoload = 'yes') {
        return true;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false) {
        return true;
    }
}

if (!function_exists('current_action')) {
    function current_action() {
        return '';
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = array()) {
        return false;
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = array()) {
        return true;
    }
}

if (!function_exists('wp_unschedule_event')) {
    function wp_unschedule_event($timestamp, $hook, $args = array()) {
        return true;
    }
}

if (!function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook($hook, $args = array()) {
        return 0;
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir($time = null, $create_dir = true, $refresh_cache = false) {
        return [
            'path' => '/tmp/uploads',
            'url' => 'http://example.com/wp-content/uploads',
            'subdir' => '',
            'basedir' => '/tmp/uploads',
            'baseurl' => 'http://example.com/wp-content/uploads',
            'error' => false
        ];
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) {
        return true;
    }
}

// Define plugin activation/deactivation functions for testing
if (!function_exists('activate_dfx_parish_retreat_letters')) {
    function activate_dfx_parish_retreat_letters() {
        // Mock activation function
    }
}

if (!function_exists('deactivate_dfx_parish_retreat_letters')) {
    function deactivate_dfx_parish_retreat_letters() {
        // Mock deactivation function
    }
}

if (!function_exists('run_dfx_parish_retreat_letters')) {
    function run_dfx_parish_retreat_letters() {
        // Mock run function
    }
}

// Include composer autoloader if available
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}