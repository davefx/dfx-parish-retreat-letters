<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.0.0
 *
 * @package    DFX_Parish_Retreat_Letters
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Drop plugin tables and clean up options.
 */
function dfx_parish_retreat_letters_uninstall() {
	// Load database class
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';
	
	// Remove database tables
	$database = DFX_Parish_Retreat_Letters_Database::get_instance();
	$database->drop_tables();
	
	// Clean up any remaining options
	delete_option( 'dfx_parish_retreat_letters_db_version' );
	delete_transient( 'dfx_admin_notices' );
}

// Run uninstall
dfx_parish_retreat_letters_uninstall();