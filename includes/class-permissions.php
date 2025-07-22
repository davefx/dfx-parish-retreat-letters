<?php
/**
 * The permissions management class
 *
 * Handles the three-tier authorization system for retreat management.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.3.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The permissions management class.
 *
 * This class handles the three-tier authorization system:
 * - Plugin Administrators (global access)
 * - Retreat Managers (retreat-specific control) 
 * - Message Managers (message-only access)
 *
 * @since      1.3.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters_Permissions {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.3.0
	 * @var DFX_Parish_Retreat_Letters_Permissions|null
	 */
	private static $instance = null;

	/**
	 * The database instance.
	 *
	 * @since 1.3.0
	 * @var DFX_Parish_Retreat_Letters_Database
	 */
	private $database;

	/**
	 * Permission levels constants.
	 */
	const PERMISSION_MANAGER = 'manager';
	const PERMISSION_MESSAGE_MANAGER = 'message_manager';

	/**
	 * Get the single instance of the class.
	 *
	 * @since 1.3.0
	 * @return DFX_Parish_Retreat_Letters_Permissions
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 */
	private function __construct() {
		$this->database = DFX_Parish_Retreat_Letters_Database::get_instance();

		// Maybe add capabilities to admin role
		add_action( 'admin_init', array( $this, 'maybe_add_admin_capabilities' ), 10, 0 );

		// Initialize WooCommerce admin access handling
		$this->init_woocommerce_handling();
	}

	/**
	 * Check if current user can manage the retreat plugin globally.
	 *
	 * @since 1.3.0
	 * @return bool True if user has global plugin management access.
	 */
	public function current_user_can_manage_plugin() {
		// If the user is an administrator, they can manage the plugin
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_retreat_plugin' ) ;
	}

	/**
	 * Check if current user can manage a specific retreat.
	 *
	 * @since 1.3.0
	 * @param int $retreat_id Retreat ID.
	 * @return bool True if user can manage the retreat.
	 */
	public function current_user_can_manage_retreat( $retreat_id ) {
		// Plugin administrators have global access
		if ( $this->current_user_can_manage_plugin() ) {
			return true;
		}

		// Check retreat-specific permission
		return $this->user_has_retreat_permission( get_current_user_id(), $retreat_id, self::PERMISSION_MANAGER );
	}

	/**
	 * Check if current user can manage messages for a specific retreat.
	 *
	 * @since 1.3.0
	 * @param int $retreat_id Retreat ID.
	 * @return bool True if user can manage messages for the retreat.
	 */
	public function current_user_can_manage_messages( $retreat_id ) {
		// Plugin administrators have global access
		if ( $this->current_user_can_manage_plugin() ) {
			return true;
		}

		// Retreat managers can manage messages
		if ( $this->current_user_can_manage_retreat( $retreat_id ) ) {
			return true;
		}

		// Check message-specific permission
		return $this->user_has_retreat_permission( get_current_user_id(), $retreat_id, self::PERMISSION_MESSAGE_MANAGER );
	}

	/**
	 * Check if current user can view a specific retreat.
	 *
	 * @since 1.3.0
	 * @param int $retreat_id Retreat ID.
	 * @return bool True if user can view the retreat.
	 */
	public function current_user_can_view_retreat( $retreat_id ) {
		// Any level of permission allows viewing
		return $this->current_user_can_manage_messages( $retreat_id );
	}

	/**
	 * Check if current user can manage a specific attendant.
	 *
	 * @since 1.3.0
	 * @param int $attendant_id Attendant ID.
	 * @return bool True if user can manage the attendant.
	 */
	public function current_user_can_manage_attendant( $attendant_id ) {
		global $wpdb;
		
		// Get the retreat ID for this attendant
		$retreat_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT retreat_id FROM {$this->database->get_attendants_table()} WHERE id = %d",
			$attendant_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $retreat_id ) {
			return false;
		}

		// Only retreat managers and plugin administrators can manage attendants
		return $this->current_user_can_manage_retreat( $retreat_id );
	}

	/**
	 * Check if a user has a specific permission for a retreat.
	 *
	 * @since 1.3.0
	 * @param int    $user_id         User ID.
	 * @param int    $retreat_id      Retreat ID.
	 * @param string $permission_level Permission level to check.
	 * @return bool True if user has the permission.
	 */
	public function user_has_retreat_permission( $user_id, $retreat_id, $permission_level ) {
		global $wpdb;

		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->database->get_permissions_table()} 
			 WHERE user_id = %d AND retreat_id = %d AND permission_level = %s AND is_active = 1",
			$user_id,
			$retreat_id,
			$permission_level
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $count > 0;
	}

	/**
	 * Grant permission to a user for a retreat.
	 *
	 * @since 1.3.0
	 * @param int    $user_id         User ID to grant permission to.
	 * @param int    $retreat_id      Retreat ID.
	 * @param string $permission_level Permission level to grant.
	 * @param int    $granted_by      User ID who is granting the permission.
	 * @return bool|int Permission ID on success, false on failure.
	 */
	public function grant_permission( $user_id, $retreat_id, $permission_level, $granted_by ) {
		// Validate permission level
		if ( ! in_array( $permission_level, array( self::PERMISSION_MANAGER, self::PERMISSION_MESSAGE_MANAGER ), true ) ) {
			return false;
		}

		// Check if permission already exists
		if ( $this->user_has_retreat_permission( $user_id, $retreat_id, $permission_level ) ) {
			return false;
		}

		global $wpdb;

		// Insert permission
		$result = $wpdb->insert(
			$this->database->get_permissions_table(),
			array(
				'user_id'          => $user_id,
				'retreat_id'       => $retreat_id,
				'permission_level' => $permission_level,
				'granted_by'       => $granted_by,
				'granted_at'       => current_time( 'mysql' ),
				'is_active'        => 1,
			),
			array( '%d', '%d', '%s', '%d', '%s', '%d' )
		);

		if ( $result === false ) {
			return false;
		}

		$permission_id = $wpdb->insert_id;

		// Log the action
		$this->log_permission_action( $user_id, $retreat_id, 'granted', $permission_level, $granted_by );

		// Add dynamic capability
		$this->add_dynamic_capability( $user_id, $retreat_id, $permission_level );

		return $permission_id;
	}

	/**
	 * Revoke permission from a user for a retreat.
	 *
	 * @since 1.3.0
	 * @param int    $user_id         User ID to revoke permission from.
	 * @param int    $retreat_id      Retreat ID.
	 * @param string $permission_level Permission level to revoke.
	 * @param int    $revoked_by      User ID who is revoking the permission.
	 * @return bool True on success, false on failure.
	 */
	public function revoke_permission( $user_id, $retreat_id, $permission_level, $revoked_by ) {
		global $wpdb;

		// Update permission to inactive
		$result = $wpdb->update(
			$this->database->get_permissions_table(),
			array(
				'is_active'  => 0,
				'revoked_at' => current_time( 'mysql' ),
			),
			array(
				'user_id'          => $user_id,
				'retreat_id'       => $retreat_id,
				'permission_level' => $permission_level,
				'is_active'        => 1,
			),
			array( '%d', '%s' ),
			array( '%d', '%d', '%s', '%d' )
		);

		if ( $result === false ) {
			return false;
		}

		// Log the action
		$this->log_permission_action( $user_id, $retreat_id, 'revoked', $permission_level, $revoked_by );

		// Remove dynamic capability
		$this->remove_dynamic_capability( $user_id, $retreat_id, $permission_level );

		return true;
	}

	/**
	 * Get all users with permissions for a specific retreat.
	 *
	 * @since 1.3.0
	 * @param int $retreat_id Retreat ID.
	 * @return array Array of user permission objects.
	 */
	public function get_retreat_permissions( $retreat_id ) {
		global $wpdb;

		$permissions = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.*, u.display_name, u.user_email, u.user_login, gb.display_name as granted_by_name
			 FROM {$this->database->get_permissions_table()} p
			 INNER JOIN {$wpdb->users} u ON p.user_id = u.ID
			 INNER JOIN {$wpdb->users} gb ON p.granted_by = gb.ID
			 WHERE p.retreat_id = %d AND p.is_active = 1
			 ORDER BY p.permission_level DESC, u.display_name ASC",
			$retreat_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $permissions ? $permissions : array();
	}

	/**
	 * Get all retreats that a user has access to.
	 *
	 * @since 1.3.0
	 * @param int $user_id User ID.
	 * @return array Array of retreat IDs.
	 */
	public function get_user_accessible_retreats( $user_id ) {
		// Plugin administrators have access to all retreats
		$user = get_user_by( 'id', $user_id );
		if ( $user && $user->has_cap( 'manage_retreat_plugin' ) ) {
			global $wpdb;
			$retreat_ids = $wpdb->get_col( "SELECT id FROM {$this->database->get_retreats_table()}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return array_map( 'intval', $retreat_ids );
		}

		// Get retreats with specific permissions
		global $wpdb;
		$retreat_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT retreat_id FROM {$this->database->get_permissions_table()} 
			 WHERE user_id = %d AND is_active = 1",
			$user_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( 'intval', $retreat_ids );
	}

	/**
	 * Add dynamic capability to user meta for performance.
	 *
	 * @since 1.3.0
	 * @param int    $user_id         User ID.
	 * @param int    $retreat_id      Retreat ID.
	 * @param string $permission_level Permission level.
	 */
	private function add_dynamic_capability( $user_id, $retreat_id, $permission_level ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		$capability = $this->get_dynamic_capability_name( $retreat_id, $permission_level );
		$user->add_cap( $capability );

		// Ensure user has admin access - add 'read' capability if they don't have admin access
		if ( ! $user->has_cap( 'read' ) && ! user_can( $user_id, 'edit_posts' ) ) {
			$user->add_cap( 'read' );
		}

		// Handle WooCommerce admin access blocking - the global handler will take care of this
		// No need to add additional hooks here since init_woocommerce_handling already set up the handler
	}

	/**
	 * Remove dynamic capability from user meta.
	 *
	 * @since 1.3.0
	 * @param int    $user_id         User ID.
	 * @param int    $retreat_id      Retreat ID.
	 * @param string $permission_level Permission level.
	 */
	private function remove_dynamic_capability( $user_id, $retreat_id, $permission_level ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		$capability = $this->get_dynamic_capability_name( $retreat_id, $permission_level );
		$user->remove_cap( $capability );

		// Check if user still has any retreat permissions
		$accessible_retreats = $this->get_user_accessible_retreats( $user_id );
		
		// If user has no more retreat permissions and doesn't have other admin capabilities,
		// we should consider removing the 'read' capability we may have added
		// However, we'll be conservative and only remove it if the user has no other admin-related capabilities
		if ( empty( $accessible_retreats ) && 
			 ! $user->has_cap( 'edit_posts' ) && 
			 ! $user->has_cap( 'manage_options' ) &&
			 ! $user->has_cap( 'manage_retreat_plugin' ) ) {
			// Only remove 'read' if it was likely added by us (user has subscriber-like role)
			$user_roles = $user->roles;
			if ( in_array( 'subscriber', $user_roles, true ) || empty( $user_roles ) ) {
				$user->remove_cap( 'read' );
			}
		}
	}

	/**
	 * Get the dynamic capability name for a retreat and permission level.
	 *
	 * @since 1.3.0
	 * @param int    $retreat_id      Retreat ID.
	 * @param string $permission_level Permission level.
	 * @return string Capability name.
	 */
	private function get_dynamic_capability_name( $retreat_id, $permission_level ) {
		if ( $permission_level === self::PERMISSION_MANAGER ) {
			return "manage_retreat_{$retreat_id}";
		} else {
			return "manage_retreat_messages_{$retreat_id}";
		}
	}

	/**
	 * Log a permission action for audit purposes.
	 *
	 * @since 1.3.0
	 * @param int    $user_id         User ID affected.
	 * @param int    $retreat_id      Retreat ID.
	 * @param string $action          Action performed.
	 * @param string $permission_level Permission level.
	 * @param int    $performed_by    User ID who performed the action.
	 * @param string $details         Optional additional details.
	 */
	public function log_permission_action( $user_id, $retreat_id, $action, $permission_level, $performed_by, $details = '' ) {
		global $wpdb;

		// Get user agent and IP for security logging
		$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$ip_address = '';
		if ( class_exists( 'DFX_Parish_Retreat_Letters_Security' ) ) {
			$security = DFX_Parish_Retreat_Letters_Security::get_instance();
			$ip_address = $security->get_user_ip();
		}

		$wpdb->insert(
			$this->database->get_audit_log_table(),
			array(
				'user_id'          => $user_id,
				'retreat_id'       => $retreat_id,
				'action'           => $action,
				'permission_level' => $permission_level,
				'performed_by'     => $performed_by,
				'performed_at'     => current_time( 'mysql' ),
				'details'          => $details,
				'ip_address'       => $ip_address,
				'user_agent'       => $user_agent,
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get permission audit log for a retreat.
	 *
	 * @since 1.3.0
	 * @param int $retreat_id Retreat ID.
	 * @param int $limit      Maximum number of records to return.
	 * @return array Array of audit log entries.
	 */
	public function get_permission_audit_log( $retreat_id, $limit = 50 ) {
		global $wpdb;

		$audit_log = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.*, u.display_name, u.user_email, pb.display_name as performed_by_name
			 FROM {$this->database->get_audit_log_table()} a
			 INNER JOIN {$wpdb->users} u ON a.user_id = u.ID
			 INNER JOIN {$wpdb->users} pb ON a.performed_by = pb.ID
			 WHERE a.retreat_id = %d
			 ORDER BY a.performed_at DESC
			 LIMIT %d",
			$retreat_id,
			$limit
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $audit_log ? $audit_log : array();
	}

	/**
	 * Check if a user can delegate permissions for a retreat.
	 *
	 * @since 1.3.0
	 * @param int $user_id    User ID.
	 * @param int $retreat_id Retreat ID.
	 * @return bool True if user can delegate permissions.
	 */
	public function user_can_delegate_permissions( $user_id, $retreat_id ) {
		// Plugin administrators can always delegate
		$user = get_user_by( 'id', $user_id );
		if ( $user && $user->has_cap( 'manage_retreat_plugin' ) ) {
			return true;
		}

		// Retreat managers can delegate for their retreats
		return $this->user_has_retreat_permission( $user_id, $retreat_id, self::PERMISSION_MANAGER );
	}

	/**
	 * Clean up expired and inactive permissions.
	 *
	 * @since 1.3.0
	 * @return int Number of permissions cleaned up.
	 */
	public function cleanup_permissions() {
		global $wpdb;

		// Get users with inactive permissions to clean their capabilities
		$inactive_permissions = $wpdb->get_results(
			"SELECT user_id, retreat_id, permission_level FROM {$this->database->get_permissions_table()} 
			 WHERE is_active = 0"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( $inactive_permissions as $permission ) {
			$this->remove_dynamic_capability( $permission->user_id, $permission->retreat_id, $permission->permission_level );
		}

		// Delete old inactive permissions (older than 1 year)
		$deleted = $wpdb->query(
			"DELETE FROM {$this->database->get_permissions_table()} 
			 WHERE is_active = 0 AND revoked_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $deleted ? $deleted : 0;
	}

	/**
	 * Delete all permissions and audit logs for a specific retreat.
	 * This method implements cascade delete functionality to replace database foreign key constraints.
	 *
	 * @since 1.4.0
	 * @param int $retreat_id Retreat ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_by_retreat( $retreat_id ) {
		global $wpdb;

		// First remove dynamic capabilities for all users with permissions on this retreat
		$permissions = $wpdb->get_results( $wpdb->prepare(
			"SELECT user_id, permission_level FROM {$this->database->get_permissions_table()} 
			 WHERE retreat_id = %d AND is_active = 1",
			$retreat_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( $permissions as $permission ) {
			$this->remove_dynamic_capability( $permission->user_id, $retreat_id, $permission->permission_level );
		}

		// Delete all permissions for this retreat (both active and inactive)
		$permissions_deleted = $wpdb->delete(
			$this->database->get_permissions_table(),
			array( 'retreat_id' => $retreat_id ),
			array( '%d' )
		);

		// Delete all audit log entries for this retreat
		$audit_deleted = $wpdb->delete(
			$this->database->get_audit_log_table(),
			array( 'retreat_id' => $retreat_id ),
			array( '%d' )
		);

		return $permissions_deleted !== false && $audit_deleted !== false;
	}

	/**
	 * Ensure WooCommerce doesn't block admin access for users with retreat permissions.
	 *
	 * @since 1.3.0
	 * @param int $user_id User ID.
	 */
	private function ensure_woocommerce_admin_access( $user_id ) {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Check if the user should have admin access based on retreat permissions
		$accessible_retreats = $this->get_user_accessible_retreats( $user_id );
		if ( empty( $accessible_retreats ) ) {
			return;
		}

		// Add a high priority action to handle admin_init and remove WooCommerce restrictions
		add_action( 'admin_init', array( $this, 'handle_woocommerce_admin_restriction' ), 5 );
	}

	/**
	 * Maybe add capabilities to the admin role for retreat management.
	 *
	 * @since 1.3.0
	 */
	public function maybe_add_admin_capabilities() {
		// Check if the current user is an administrator
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get the administrator role
		$admin_role = get_role( 'administrator' );
		if ( ! $admin_role ) {
			return;
		}

		// Check if the role already has the capability
		if ( $admin_role->has_cap( 'manage_retreat_plugin' ) ) {
			return; // Already has the capability
		}

		// Add capabilities for retreat management
		$admin_role->add_cap( 'manage_retreat_plugin' );

	}

	/**
	 * Initialize WooCommerce admin access handling.
	 *
	 * @since 1.3.0
	 */
	private function init_woocommerce_handling() {

		add_action( 'admin_init', function() {
			// Only add the handler if WooCommerce is active
			if ( class_exists( 'WooCommerce' ) ) {
				// Add early admin_init hook to handle WooCommerce restrictions
				add_action( 'admin_init', array( $this, 'handle_woocommerce_admin_restriction' ), 5 );
			}
		}, 0 );
	}

	/**
	 * Handle WooCommerce admin restriction for users with retreat permissions.
	 *
	 * @since 1.3.0
	 */
	public function handle_woocommerce_admin_restriction() {
		// Only run for users who should have retreat access
		$current_user_id = get_current_user_id();
		if ( ! $current_user_id ) {
			return;
		}

		// Check if user has retreat permissions
		$accessible_retreats = $this->get_user_accessible_retreats( $current_user_id );
		if ( empty( $accessible_retreats ) ) {
			return;
		}

		// Check if WooCommerce's wc_disable_admin is hooked to admin_init
		if ( ! function_exists( 'wc_disable_admin_bar' ) ) {
			return;
		}

		// Get the current user
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return;
		}

		// Check if user has admin-type capabilities or retreat permissions
		$has_admin_access = $user->has_cap( 'edit_posts' ) || 
							$user->has_cap( 'manage_options' ) || 
							$user->has_cap( 'manage_retreat_plugin' );

		// If user doesn't have standard admin access but has retreat permissions, 
		// we need to prevent WooCommerce from redirecting them
		if ( ! $has_admin_access && ! empty( $accessible_retreats ) ) {
			// Remove WooCommerce's admin restrictions
			$this->remove_woocommerce_admin_restrictions();
		}
	}

	/**
	 * Remove WooCommerce admin restrictions for users with retreat permissions.
	 *
	 * @since 1.3.0
	 */
	private function remove_woocommerce_admin_restrictions() {
		// Check if WooCommerce functions exist
		if ( ! function_exists( 'wc_disable_admin_bar' ) ) {
			return;
		}

		// Remove WooCommerce admin restrictions
		remove_action( 'admin_init', 'wc_disable_admin_bar', 10 );
		
		// Also try to remove if it's hooked differently
		if ( function_exists( 'wc_prevent_admin_access' ) ) {
			remove_action( 'admin_init', 'wc_prevent_admin_access', 10 );
		}

		if ( function_exists( 'wc_disable_admin' ) ) {
			remove_action( 'admin_init', 'wc_disable_admin', 10 );
		}

		// Remove any other WooCommerce admin restrictions that might be in place
		global $wp_filter;
		if ( isset( $wp_filter['admin_init'] ) ) {
			foreach ( $wp_filter['admin_init']->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $key => $callback ) {
					// Look for WooCommerce admin restriction callbacks
					if ( is_array( $callback['function'] ) && 
						 is_object( $callback['function'][0] ) && 
						 get_class( $callback['function'][0] ) === 'WC_Admin' &&
						 $callback['function'][1] === 'prevent_admin_access' ) {
						remove_action( 'admin_init', $callback['function'], $priority );
					}
				}
			}
		}
	}
}
