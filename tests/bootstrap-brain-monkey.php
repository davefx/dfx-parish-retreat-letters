<?php
/**
 * PHPUnit bootstrap file for Brain Monkey tests
 *
 * This bootstrap is for unit tests that use Brain Monkey for WordPress function mocking
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

// Include composer autoloader first (Brain Monkey needs to be loaded before any WordPress functions)
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// Initialize Brain Monkey - this must be done BEFORE any WordPress functions are defined
// Load Brain Monkey functions
require_once dirname(__DIR__) . '/vendor/brain/monkey/inc/api.php';

// Only define WordPress functions if they don't exist and Brain Monkey hasn't loaded
// Brain Monkey will handle WordPress function mocking
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

// Only define these if Brain Monkey hasn't already handled them
if (!function_exists('did_action')) {
    function did_action($action) {
        return 0;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
}

if (!function_exists('current_action')) {
    function current_action() {
        return '';
    }
}