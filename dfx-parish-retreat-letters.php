<?php
/**
 * Plugin Name: DFX Parish Retreat Letters
 * Plugin URI: https://github.com/davefx/dfx-parish-retreat-letters
 * Description: A WordPress plugin for managing parish retreat letters.
 * Version: 25.7.21
 * Author: DaveFX
 * Author URI: https://github.com/davefx
 * Text Domain: dfx-parish-retreat-letters
 * Domain Path: /languages
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 *
 * @package DFX_Parish_Retreat_Letters
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'DFX_PARISH_RETREAT_LETTERS_VERSION', '25.7.21' );

/**
 * Define plugin constants.
 */
define( 'DFX_PARISH_RETREAT_LETTERS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DFX_PARISH_RETREAT_LETTERS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DFX_PARISH_RETREAT_LETTERS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_dfx_parish_retreat_letters() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';
	$database = DFX_Parish_Retreat_Letters_Database::get_instance();
	$database->setup_tables();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_dfx_parish_retreat_letters() {
	// Keep data on deactivation - only remove on uninstall
}

register_activation_hook( __FILE__, 'activate_dfx_parish_retreat_letters' );
register_deactivation_hook( __FILE__, 'deactivate_dfx_parish_retreat_letters' );

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
function run_dfx_parish_retreat_letters() {
	$plugin = DFX_Parish_Retreat_Letters::get_instance();
	$plugin->run();
}

// Initialize the plugin
run_dfx_parish_retreat_letters();
