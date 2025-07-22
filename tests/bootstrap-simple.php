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

// Include composer autoloader if available
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}