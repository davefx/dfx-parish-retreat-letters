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
	
	// Clean up scheduled tasks
	wp_clear_scheduled_hook( 'dfx_prl_retreat_cleanup_hook' );
	
	// Clean up custom capabilities from all roles
	$roles = wp_roles();
	if ( $roles && $roles->roles ) {
		foreach ( $roles->roles as $role_name => $role_data ) {
			$role = get_role( $role_name );
			if ( $role ) {
				// Remove global capability
				$role->remove_cap( 'manage_retreat_plugin' );
				
				// Remove any dynamic retreat-specific capabilities
				if ( isset( $role_data['capabilities'] ) && is_array( $role_data['capabilities'] ) ) {
					foreach ( $role_data['capabilities'] as $cap => $value ) {
						if ( strpos( $cap, 'manage_retreat_' ) === 0 || strpos( $cap, 'manage_retreat_messages_' ) === 0 ) {
							$role->remove_cap( $cap );
						}
					}
				}
			}
		}
	}
	
	// Clean up any remaining options
	delete_option( 'dfx_parish_retreat_letters_db_version' );
	delete_option( 'dfx_parish_retreat_letters_encryption_key' );
	delete_transient( 'dfx_prl_admin_notices' );
	delete_transient( 'dfx_prl_message_rate_limit_violations' );
	
	// Clean up any remaining rate limit transients
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin cleanup requires direct database access for transient cleanup
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dfx_prl_message_rate_limit_%'" );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin cleanup requires direct database access for transient cleanup
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dfx_prl_message_rate_limit_%'" );
}

// Run uninstall
dfx_parish_retreat_letters_uninstall();