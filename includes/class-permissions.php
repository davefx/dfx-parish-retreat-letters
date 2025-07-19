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
	}

	/**
	 * Check if current user can manage the retreat plugin globally.
	 *
	 * @since 1.3.0
	 * @return bool True if user has global plugin management access.
	 */
	public function current_user_can_manage_plugin() {
		return current_user_can( 'manage_retreat_plugin' );
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
}