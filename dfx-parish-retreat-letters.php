<?php
/**
 * Plugin Name: DFX Parish Retreat Letters
 * Plugin URI: https://github.com/davefx/dfx-parish-retreat-letters
 * Description: A WordPress plugin for managing parish retreat letters and retreat data.
 * Version: 1.0.0
 * Author: DaveFX
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dfx-parish-retreat-letters
 * Domain Path: /languages
 *
 * @package DFX_Parish_Retreat_Letters
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'DFX_PRL_VERSION', '1.0.0' );
define( 'DFX_PRL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DFX_PRL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DFX_PRL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
require_once DFX_PRL_PLUGIN_DIR . 'includes/class-dfx-parish-retreat-letters.php';

/**
 * Initialize the plugin
 */
function dfx_prl_init() {
    return DFX_Parish_Retreat_Letters::get_instance();
}

// Initialize the plugin
dfx_prl_init();

/**
 * Activation hook
 */
register_activation_hook( __FILE__, array( 'DFX_Parish_Retreat_Letters', 'activate' ) );

/**
 * Deactivation hook
 */
register_deactivation_hook( __FILE__, array( 'DFX_Parish_Retreat_Letters', 'deactivate' ) );