<?php
/**
 * Plugin Name: DFX Parish Retreat Letters
 * Plugin URI: https://github.com/davefx/dfx-parish-retreat-letters
 * Description: A WordPress plugin for managing parish retreat letters.
 * Version: 25.12.09
 * Author: David Marín Carreño
 * Author URI: https://davefx.com
 * Text Domain: dfx-parish-retreat-letters
 * Domain Path: /languages
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.0
 * Tested up to: 6.8
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
    define( 'DFXPRL_VERSION', '25.12.10' );
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
function activate_dfxprl() {
	// Load migration class first
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-prefix-migration.php';
	
	// Run migration to rename old tables (if they exist) before creating new ones
	// This ensures we don't end up with duplicate tables
	DFXPRL_Prefix_Migration::run_migration();
	
	// Now setup tables (will create only if they don't exist after migration)
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';
	$database = DFXPRL_Database::get_instance();
	$database->setup_tables();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_dfxprl() {
	// Keep data on deactivation - only remove on uninstall
}

register_activation_hook( __FILE__, 'activate_dfxprl' );
register_deactivation_hook( __FILE__, 'deactivate_dfxprl' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-dfx-parish-retreat-letters.php';

// Load the prefix migration utility for one-time migration
require plugin_dir_path( __FILE__ ) . 'includes/class-prefix-migration.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_dfxprl() {
	// Run prefix migration if needed (will only run once)
	// Use priority 0 to ensure it runs BEFORE database setup (which runs at priority 1 on init)
	add_action( 'init', array( 'DFXPRL_Prefix_Migration', 'run_migration' ), 0 );
	
	$plugin = DFXPRL::get_instance();
	$plugin->run();
}

// Initialize the plugin
run_dfxprl();
