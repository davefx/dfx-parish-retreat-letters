<?php
/**
 * Test script for DFX Parish Retreat Letters Authorization System
 * 
 * This script can be used to verify that the core authorization system works correctly.
 * Place this file in the plugin directory and access it via browser (development only).
 */

// Prevent direct access except in development
if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
	die( 'This test script is only available in debug mode.' );
}

// Load WordPress if not already loaded
if ( ! function_exists( 'wp' ) ) {
	require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';
}

// Load our plugin classes
require_once __DIR__ . '/includes/class-database.php';
require_once __DIR__ . '/includes/class-security.php';
require_once __DIR__ . '/includes/class-permissions.php';
require_once __DIR__ . '/includes/class-invitations.php';

echo "<h1>DFX Parish Retreat Letters - Authorization System Test</h1>\n";

// Test database connection and tables
echo "<h2>1. Database Tests</h2>\n";
$database = DFX_Parish_Retreat_Letters_Database::get_instance();

echo "<p>Database version: " . $database->get_schema_version() . "</p>\n";
echo "<p>Database up to date: " . ( $database->is_database_up_to_date() ? 'Yes' : 'No' ) . "</p>\n";

// Check if tables exist
$table_checks = [
	'Retreats' => $database->get_retreats_table(),
	'Attendants' => $database->get_attendants_table(),
	'Messages' => $database->get_messages_table(),
	'Message Files' => $database->get_message_files_table(),
	'Print Log' => $database->get_message_print_log_table(),
	'Permissions' => $database->get_permissions_table(),
	'Invitations' => $database->get_invitations_table(),
	'Audit Log' => $database->get_audit_log_table(),
];

global $wpdb;
foreach ( $table_checks as $name => $table ) {
	$exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
	echo "<p>{$name} table: " . ( $exists ? 'Exists' : 'Missing' ) . "</p>\n";
}

// Test security functions
echo "<h2>2. Security Tests</h2>\n";
$security = DFX_Parish_Retreat_Letters_Security::get_instance();

echo "<p>Security requirements met: " . ( $security->verify_security_requirements() ? 'Yes' : 'No' ) . "</p>\n";

// Test encryption
$test_data = "This is a test message for encryption";
$encrypted = $security->encrypt_data( $test_data );
if ( $encrypted ) {
	$decrypted = $security->decrypt_data( $encrypted['encrypted'], $encrypted['salt'] );
	echo "<p>Encryption test: " . ( $decrypted === $test_data ? 'Passed' : 'Failed' ) . "</p>\n";
} else {
	echo "<p>Encryption test: Failed - Could not encrypt data</p>\n";
}

// Test token generation
$token = $security->generate_secure_token();
echo "<p>Token generation: " . ( strlen( $token ) === 64 ? 'Passed' : 'Failed' ) . " (Length: " . strlen( $token ) . ")</p>\n";

// Test permissions system
echo "<h2>3. Permissions System Tests</h2>\n";
$permissions = DFX_Parish_Retreat_Letters_Permissions::get_instance();

echo "<p>Permissions instance: " . ( $permissions ? 'Created' : 'Failed' ) . "</p>\n";

// Test current user capabilities
$current_user_id = get_current_user_id();
if ( $current_user_id ) {
	echo "<p>Current user ID: {$current_user_id}</p>\n";
	echo "<p>Can manage plugin: " . ( $permissions->current_user_can_manage_plugin() ? 'Yes' : 'No' ) . "</p>\n";
	
	// Get accessible retreats
	$accessible_retreats = $permissions->get_user_accessible_retreats( $current_user_id );
	echo "<p>Accessible retreats: " . count( $accessible_retreats ) . "</p>\n";
} else {
	echo "<p>No user logged in</p>\n";
}

// Test invitations system
echo "<h2>4. Invitations System Tests</h2>\n";
$invitations = DFX_Parish_Retreat_Letters_Invitations::get_instance();

echo "<p>Invitations instance: " . ( $invitations ? 'Created' : 'Failed' ) . "</p>\n";

// Test capabilities
echo "<h2>5. WordPress Capabilities Tests</h2>\n";
$admin_role = get_role( 'administrator' );
if ( $admin_role ) {
	echo "<p>Administrator role has manage_retreat_plugin capability: " . 
		( $admin_role->has_cap( 'manage_retreat_plugin' ) ? 'Yes' : 'No' ) . "</p>\n";
} else {
	echo "<p>Administrator role not found</p>\n";
}

// Test custom capability structure
if ( $current_user_id ) {
	$user = get_user_by( 'id', $current_user_id );
	if ( $user ) {
		echo "<p>Current user capabilities related to retreats:</p>\n";
		echo "<ul>\n";
		foreach ( $user->allcaps as $cap => $value ) {
			if ( strpos( $cap, 'retreat' ) !== false ) {
				echo "<li>{$cap}: " . ( $value ? 'Yes' : 'No' ) . "</li>\n";
			}
		}
		echo "</ul>\n";
	}
}

echo "<h2>Test Complete</h2>\n";
echo "<p>If all tests show 'Passed' or 'Yes', the authorization system is working correctly.</p>\n";
echo "<p><strong>Note:</strong> Remove this test file before deploying to production!</p>\n";