<?php
/**
 * Plugin Name: DFX Parish Retreat Letters
 * Plugin URI: https://github.com/davefx/dfx-parish-retreat-letters
 * Description: A WordPress plugin for managing parish retreat letters.
 * Version: 26.04.28
 * Author: David Marín Carreño
 * Author URI: https://davefx.com
 * Text Domain: dfx-parish-retreat-letters
 * Domain Path: /languages
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 *
 * @package DFXPRL
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Rename this for your plugin and update it as you release new versions.
 */
if ( ! defined( 'DFXPRL_VERSION' ) ) {
    define( 'DFXPRL_VERSION', '26.04.28' );
}

/**
 * Define plugin constants.
 */
if ( ! defined( 'DFXPRL_PLUGIN_DIR' ) ) {
    define( 'DFXPRL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'DFXPRL_PLUGIN_URL' ) ) {
    define( 'DFXPRL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
define( 'DFXPRL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function dfxprl_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';
	$database = DFXPRL_Database::get_instance();
	$database->setup_tables();
}

/**
 * The code that runs during plugin deactivation.
 */
function dfxprl_deactivate() {
	// Keep data on deactivation - only remove on uninstall
}

register_activation_hook( __FILE__, 'dfxprl_activate' );
register_deactivation_hook( __FILE__, 'dfxprl_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-dfx-parish-retreat-letters.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function dfxprl_run() {
	$plugin = DFXPRL::get_instance();
	$plugin->run();
}

// Initialize the plugin
dfxprl_run();
