<?php
/**
 * Uninstall script for DFX Parish Retreat Letters
 *
 * @package DFX_Parish_Retreat_Letters
 */

// Prevent direct access
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Only run if user has proper capabilities
if ( ! current_user_can( 'activate_plugins' ) ) {
    return;
}

// Load plugin dependencies
require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';

// Initialize database class
$database = new DFX_PRL_Database();

// Drop the retreats table
$database->drop_tables();

// Remove plugin options
delete_option( 'dfx_prl_version' );

// Clear any cached data
wp_cache_flush();