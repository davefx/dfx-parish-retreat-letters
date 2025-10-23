<?php
/**
 * The database management class
 *
 * Handles database table creation and management for the plugin.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.0.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The database management class.
 *
 * This class handles the creation and management of custom database tables
 * required by the plugin.
 *
 * @since      1.0.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters_Database {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 * @var DFX_Parish_Retreat_Letters_Database|null
	 */
	private static $instance = null;

	/**
	 * The current database version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const DB_VERSION = '1.7.0';

	/**
	 * The database version option name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const DB_VERSION_OPTION = 'dfx_parish_retreat_letters_db_version';

	/**
	 * The table name for retreats.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $retreats_table;

	/**
	 * The table name for attendants.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $attendants_table;

	/**
	 * The table name for confidential messages.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	private $messages_table;

	/**
	 * The table name for message files.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	private $message_files_table;

	/**
	 * The table name for message print log.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	private $message_print_log_table;

	/**
	 * The table name for retreat permissions.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	private $permissions_table;

	/**
	 * The table name for retreat invitations.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	private $invitations_table;

	/**
	 * The table name for permission audit log.
	 *
	 * @since 1.3.0
	 * @var string
	 */
	private $audit_log_table;

	/**
	 * Get the single instance of the class.
	 *
	 * @since 1.0.0
	 * @return DFX_Parish_Retreat_Letters_Database
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
	 * @since 1.0.0
	 */
	private function __construct() {
		global $wpdb;
		
		// Ensure $wpdb and prefix are properly set, fallback to 'wp_' if not available
		$prefix = ( isset( $wpdb->prefix ) && ! empty( $wpdb->prefix ) ) ? $wpdb->prefix : 'wp_';
		
		$this->retreats_table = $prefix . 'dfx_prl_retreats';
		$this->attendants_table = $prefix . 'dfx_prl_attendants';
		$this->messages_table = $prefix . 'dfx_prl_confidential_messages';
		$this->message_files_table = $prefix . 'dfx_prl_message_files';
		$this->message_print_log_table = $prefix . 'dfx_prl_message_print_log';
		$this->permissions_table = $prefix . 'dfx_prl_retreat_permissions';
		$this->invitations_table = $prefix . 'dfx_prl_retreat_invitations';
		$this->audit_log_table = $prefix . 'dfx_prl_permission_audit_log';
		
		// Only check for database upgrades if WordPress is fully loaded
		if ( did_action( 'init' ) || current_action() === 'init' ) {
			$this->maybe_upgrade_database();
		} else {
			// Hook to check for upgrades after WordPress is fully loaded
			add_action( 'init', array( $this, 'maybe_upgrade_database' ), 1 );
		}
	}

	/**
	 * Setup all database tables, columns, and capabilities for the current version.
	 * 
	 * This method ensures the database structure is complete and up-to-date using
	 * WordPress's dbDelta function. It can be safely called multiple times as
	 * dbDelta is idempotent (creates missing tables/columns without affecting existing data).
	 * 
	 * This method handles both fresh installations and upgrades, making it suitable
	 * for fixing incomplete database structures regardless of the stored version.
	 *
	 * @since 1.0.0 Originally create_tables()
	 * @since 1.4.0 Renamed to setup_tables() and enhanced for comprehensive database setup
	 */
	public function setup_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create retreats table
		$retreats_sql = "CREATE TABLE {$this->retreats_table} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			location varchar(255) NOT NULL,
			start_date date NOT NULL,
			end_date date NOT NULL,
			custom_message text NULL DEFAULT NULL,
			disclaimer_text text NULL DEFAULT NULL,
			disclaimer_acceptance_text varchar(500) NULL DEFAULT NULL,
			custom_header_block_id varchar(100) NULL DEFAULT NULL,
			custom_footer_block_id varchar(100) NULL DEFAULT NULL,
			custom_css text NULL DEFAULT NULL,
			notes_enabled tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_start_date (start_date),
			INDEX idx_end_date (end_date),
			INDEX idx_custom_header_block_id (custom_header_block_id),
			INDEX idx_custom_footer_block_id (custom_footer_block_id)
		) $charset_collate;";

		dbDelta( $retreats_sql );

		// Create attendants table (with message_url_token from v1.2.0, new fields from v1.7.0)
		$attendants_sql = "CREATE TABLE {$this->attendants_table} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			retreat_id mediumint(9) NOT NULL,
			name varchar(255) NOT NULL,
			surnames varchar(255) NOT NULL,
			date_of_birth date NOT NULL,
			emergency_contact_name varchar(255) NOT NULL,
			emergency_contact_surname varchar(255) NOT NULL,
			emergency_contact_phone varchar(20) NOT NULL,
			emergency_contact_email varchar(255) NULL DEFAULT NULL,
			emergency_contact_relationship varchar(255) NULL DEFAULT NULL,
			invited_by varchar(255) NULL DEFAULT NULL,
			incompatibilities text NULL DEFAULT NULL,
			message_url_token VARCHAR(255) NULL DEFAULT NULL,
			notes text NULL DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_retreat_id (retreat_id),
			INDEX idx_name (name),
			INDEX idx_surnames (surnames),
			INDEX idx_emergency_contact_email (emergency_contact_email),
			INDEX idx_message_url_token (message_url_token)
		) $charset_collate;";

		dbDelta( $attendants_sql );

		// Create confidential messages table (from v1.2.0)
		$messages_sql = "CREATE TABLE {$this->messages_table} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			attendant_id mediumint(9) NOT NULL,
			sender_name varchar(255) DEFAULT '',
			encrypted_content longtext NOT NULL,
			content_salt varchar(255) NOT NULL,
			message_type enum('text','file') NOT NULL DEFAULT 'text',
			ip_address varchar(45) DEFAULT NULL,
			ip_hash varchar(255) DEFAULT NULL,
			submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
			ip_anonymized_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			INDEX idx_attendant_id (attendant_id),
			INDEX idx_submitted_at (submitted_at),
			INDEX idx_message_type (message_type),
			INDEX idx_ip_anonymized_at (ip_anonymized_at)
		) $charset_collate;";

		dbDelta( $messages_sql );

		// Create message files table (from v1.2.0)
		$files_sql = "CREATE TABLE {$this->message_files_table} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			message_id mediumint(9) NOT NULL,
			original_filename varchar(255) NOT NULL,
			encrypted_filename varchar(255) NOT NULL,
			file_type varchar(255) NOT NULL,
			file_size int(11) NOT NULL,
			encrypted_file_path text NOT NULL,
			file_salt varchar(255) NOT NULL,
			uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_message_id (message_id),
			INDEX idx_file_type (file_type),
			INDEX idx_uploaded_at (uploaded_at)
		) $charset_collate;";

		dbDelta( $files_sql );

		// Create message print log table (from v1.2.0)
		$print_log_sql = "CREATE TABLE {$this->message_print_log_table} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			message_id mediumint(9) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			printed_at datetime DEFAULT CURRENT_TIMESTAMP,
			ip_address varchar(45) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			PRIMARY KEY (id),
			INDEX idx_message_id (message_id),
			INDEX idx_user_id (user_id),
			INDEX idx_printed_at (printed_at)
		) $charset_collate;";

		dbDelta( $print_log_sql );

		// Create retreat permissions table (from v1.3.0)
		$permissions_sql = "CREATE TABLE {$this->permissions_table} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			retreat_id mediumint(9) NOT NULL,
			permission_level enum('manager','message_manager') NOT NULL DEFAULT 'message_manager',
			granted_by bigint(20) unsigned NOT NULL,
			granted_at datetime DEFAULT CURRENT_TIMESTAMP,
			revoked_at datetime DEFAULT NULL,
			is_active tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			UNIQUE KEY unique_active_permission (user_id, retreat_id, permission_level, is_active),
			INDEX idx_user_id (user_id),
			INDEX idx_retreat_id (retreat_id),
			INDEX idx_permission_level (permission_level),
			INDEX idx_granted_by (granted_by),
			INDEX idx_granted_at (granted_at),
			INDEX idx_is_active (is_active)
		) $charset_collate;";

		dbDelta( $permissions_sql );

		// Create retreat invitations table (from v1.3.0)
		$invitations_sql = "CREATE TABLE {$this->invitations_table} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			retreat_id mediumint(9) NOT NULL,
			email varchar(255) NOT NULL,
			name varchar(255) NOT NULL,
			permission_level enum('manager','message_manager') NOT NULL DEFAULT 'message_manager',
			token varchar(255) NOT NULL,
			invited_by bigint(20) unsigned NOT NULL,
			invited_at datetime DEFAULT CURRENT_TIMESTAMP,
			expires_at datetime NOT NULL,
			accepted_at datetime DEFAULT NULL,
			status enum('pending','accepted','expired','cancelled') NOT NULL DEFAULT 'pending',
			created_user_id bigint(20) unsigned DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY unique_token (token),
			INDEX idx_retreat_id (retreat_id),
			INDEX idx_email (email),
			INDEX idx_token (token),
			INDEX idx_invited_by (invited_by),
			INDEX idx_invited_at (invited_at),
			INDEX idx_expires_at (expires_at),
			INDEX idx_status (status),
			INDEX idx_unique_pending (retreat_id, email, permission_level, status)
		) $charset_collate;";

		dbDelta( $invitations_sql );

		// Create permission audit log table (from v1.3.0)
		$audit_log_sql = "CREATE TABLE {$this->audit_log_table} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			retreat_id mediumint(9) NOT NULL,
			action enum('granted','revoked','invitation_sent','invitation_accepted','invitation_cancelled') NOT NULL,
			permission_level enum('manager','message_manager') NOT NULL,
			performed_by bigint(20) unsigned NOT NULL,
			performed_at datetime DEFAULT CURRENT_TIMESTAMP,
			details text DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			PRIMARY KEY (id),
			INDEX idx_user_id (user_id),
			INDEX idx_retreat_id (retreat_id),
			INDEX idx_action (action),
			INDEX idx_permission_level (permission_level),
			INDEX idx_performed_by (performed_by),
			INDEX idx_performed_at (performed_at)
		) $charset_collate;";

		dbDelta( $audit_log_sql );

		// Add the manage_retreat_plugin capability to administrator role (from v1.3.0)
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'manage_retreat_plugin' );
		}

		// Store the database version for future upgrades
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Backward compatibility alias for setup_tables().
	 * 
	 * @since 1.0.0
	 * @deprecated 1.3.0 Use setup_tables() instead.
	 */
	public function create_tables() {
		$this->setup_tables();
	}

	/**
	 * Drop the retreats table.
	 *
	 * @since 1.0.0
	 */
	public function drop_tables() {
		global $wpdb;
		// Drop tables in reverse order due to foreign key constraints
		$wpdb->query( "DROP TABLE IF EXISTS {$this->audit_log_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$this->invitations_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$this->permissions_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$this->message_print_log_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$this->message_files_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$this->messages_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$this->attendants_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$this->retreats_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		
		// Remove custom capabilities
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->remove_cap( 'manage_retreat_plugin' );
		}
		
		delete_option( self::DB_VERSION_OPTION );
	}

	/**
	 * Get the retreats table name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_retreats_table() {
		return $this->retreats_table;
	}

	/**
	 * Get the attendants table name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_attendants_table() {
		return $this->attendants_table;
	}

	/**
	 * Get the confidential messages table name.
	 *
	 * @since 1.2.0
	 * @return string
	 */
	public function get_messages_table() {
		return $this->messages_table;
	}

	/**
	 * Get the message files table name.
	 *
	 * @since 1.2.0
	 * @return string
	 */
	public function get_message_files_table() {
		return $this->message_files_table;
	}

	/**
	 * Get the message print log table name.
	 *
	 * @since 1.2.0
	 * @return string
	 */
	public function get_message_print_log_table() {
		return $this->message_print_log_table;
	}

	/**
	 * Get the retreat permissions table name.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_permissions_table() {
		return $this->permissions_table;
	}

	/**
	 * Get the retreat invitations table name.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_invitations_table() {
		return $this->invitations_table;
	}

	/**
	 * Get the permission audit log table name.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_audit_log_table() {
		return $this->audit_log_table;
	}

	/**
	 * Check if the basic database tables exist (for backward compatibility).
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function tables_exist() {
		global $wpdb;
		$retreats_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->retreats_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$attendants_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->attendants_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $retreats_exist === $this->retreats_table && $attendants_exist === $this->attendants_table;
	}

	/**
	 * Check if the message system tables exist.
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	public function message_tables_exist() {
		global $wpdb;
		$messages_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->messages_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$files_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->message_files_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$print_log_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->message_print_log_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		
		return $messages_exist === $this->messages_table && 
		       $files_exist === $this->message_files_table && 
		       $print_log_exist === $this->message_print_log_table;
	}

	/**
	 * Check if the authorization system tables exist.
	 *
	 * @since 1.3.0
	 * @return bool
	 */
	public function authorization_tables_exist() {
		global $wpdb;
		$permissions_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->permissions_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$invitations_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->invitations_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$audit_log_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->audit_log_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		
		return $permissions_exist === $this->permissions_table && 
		       $invitations_exist === $this->invitations_table && 
		       $audit_log_exist === $this->audit_log_table;
	}

	/**
	 * Check if the database structure is complete for the current version.
	 * This is a comprehensive check that verifies all required tables, columns,
	 * and capabilities exist for database version 1.3.0.
	 *
	 * @since 1.3.0
	 * @return bool True if database structure is complete, false otherwise.
	 */
	public function is_database_structure_complete() {
		global $wpdb;

		// Check if all required tables exist
		if ( ! $this->tables_exist() || ! $this->message_tables_exist() || ! $this->authorization_tables_exist() ) {
			return false;
		}

		// Check if message_url_token column exists in attendants table
		$attendants_table = $this->attendants_table;
		$column_exists = $wpdb->get_results( $wpdb->prepare(
			"SHOW COLUMNS FROM {$attendants_table} LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'message_url_token'
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $column_exists ) ) {
			return false;
		}

		// Check if custom_message column exists in retreats table
		$retreats_table = $this->retreats_table;
		$custom_message_exists = $wpdb->get_results( $wpdb->prepare(
			"SHOW COLUMNS FROM {$retreats_table} LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'custom_message'
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $custom_message_exists ) ) {
			return false;
		}

		// Check if administrator role has the required capability
		$admin_role = get_role( 'administrator' );
		if ( ! $admin_role || ! $admin_role->has_cap( 'manage_retreat_plugin' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the current database version from the database.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_database_version() {
		return get_option( self::DB_VERSION_OPTION, '0.0.0' );
	}

	/**
	 * Check if the database is up to date.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_database_up_to_date() {
		$current_version = $this->get_database_version();
		return version_compare( $current_version, self::DB_VERSION, '>=' );
	}

	/**
	 * Check if a database upgrade is needed and perform it.
	 * 
	 * This method only checks the stored database version to determine if setup is needed.
	 * The version was bumped to 1.4.0 to trigger setup once for existing installations,
	 * ensuring all database structure issues are resolved without expensive per-request checks.
	 *
	 * @since 1.0.0
	 * @since 1.4.0 Simplified to only check version, removing expensive per-request structure validation
	 */
	public function maybe_upgrade_database() {
		$current_version = $this->get_database_version();
		
		// Only check version - if it's less than current, run upgrade
		if ( version_compare( $current_version, self::DB_VERSION, '<' ) ) {
			$this->upgrade_database( $current_version );
		}
	}

	/**
	 * Migrate custom block IDs from bigint format to varchar prefixed format.
	 * 
	 * This method converts existing numeric block IDs to the new prefixed format
	 * (e.g., 123 becomes 'block_123') to support multiple block types including
	 * reusable blocks, patterns, and registered patterns.
	 *
	 * @since 1.5.0
	 */
	private function migrate_custom_block_ids() {
		global $wpdb;
		
		// Check if the retreats table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->retreats_table ) );
		if ( $table_exists !== $this->retreats_table ) {
			return; // Table doesn't exist, nothing to migrate
		}
		
		// First, create temporary columns with the new varchar format
		$retreats_table = $this->retreats_table;
		$wpdb->query( "ALTER TABLE {$retreats_table} 
			ADD COLUMN custom_header_block_id_new varchar(100) NULL DEFAULT NULL AFTER custom_header_block_id,
			ADD COLUMN custom_footer_block_id_new varchar(100) NULL DEFAULT NULL AFTER custom_footer_block_id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		
		// Convert existing numeric values to the new prefixed format
		$retreats_with_blocks = $wpdb->get_results( 
			"SELECT id, custom_header_block_id, custom_footer_block_id 
			FROM {$retreats_table} 
			WHERE custom_header_block_id IS NOT NULL OR custom_footer_block_id IS NOT NULL" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		
		foreach ( $retreats_with_blocks as $retreat ) {
			$header_value = null;
			$footer_value = null;
			
			// Convert header block ID
			if ( ! empty( $retreat->custom_header_block_id ) && is_numeric( $retreat->custom_header_block_id ) ) {
				$header_value = 'block_' . absint( $retreat->custom_header_block_id );
			}
			
			// Convert footer block ID  
			if ( ! empty( $retreat->custom_footer_block_id ) && is_numeric( $retreat->custom_footer_block_id ) ) {
				$footer_value = 'block_' . absint( $retreat->custom_footer_block_id );
			}
			
			// Update the new columns with converted values
			if ( $header_value || $footer_value ) {
				$wpdb->update(
					$this->retreats_table,
					array(
						'custom_header_block_id_new' => $header_value,
						'custom_footer_block_id_new' => $footer_value,
					),
					array( 'id' => $retreat->id ),
					array( '%s', '%s' ),
					array( '%d' )
				);
			}
		}
		
		// Drop the old columns and rename the new ones
		$wpdb->query( "ALTER TABLE {$retreats_table} 
			DROP COLUMN custom_header_block_id,
			DROP COLUMN custom_footer_block_id" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			
		$wpdb->query( "ALTER TABLE {$retreats_table} 
			CHANGE COLUMN custom_header_block_id_new custom_header_block_id varchar(100) NULL DEFAULT NULL,
			CHANGE COLUMN custom_footer_block_id_new custom_footer_block_id varchar(100) NULL DEFAULT NULL" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		
		// Log the migration if debug mode is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
			error_log( sprintf( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log -- Debug-only logging when WP_DEBUG enabled
				'DFX Parish Retreat Letters: Migrated %d retreats with custom block IDs to new format',
				count( $retreats_with_blocks )
			) );
		}
	}



	/**
	 * Remove foreign key constraints from audit log table.
	 * 
	 * This method removes any existing foreign key constraints from the audit log table
	 * that may have been created in older versions of the plugin. These constraints
	 * can cause issues when logging permission actions with user_id = 0 (e.g., for invitations).
	 *
	 * @since 1.4.1
	 */
	private function remove_audit_log_foreign_keys() {
		global $wpdb;
		
		// Check if the audit log table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->audit_log_table ) );
		if ( $table_exists !== $this->audit_log_table ) {
			return; // Table doesn't exist, nothing to do
		}
		
		// Get all foreign key constraints for the audit log table
		$constraints = $wpdb->get_results( $wpdb->prepare(
			"SELECT CONSTRAINT_NAME 
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
			WHERE TABLE_SCHEMA = DATABASE() 
			AND TABLE_NAME = %s 
			AND REFERENCED_TABLE_NAME IS NOT NULL",
			$this->audit_log_table
		) );
		
		// Remove each foreign key constraint
		foreach ( $constraints as $constraint ) {
			$constraint_name = $constraint->CONSTRAINT_NAME;
			
			// Skip if it's not a foreign key constraint we want to remove
			if ( strpos( $constraint_name, 'ibfk' ) === false && strpos( $constraint_name, 'fk_' ) === false ) {
				continue;
			}
			
			// Drop the foreign key constraint
			$wpdb->query( sprintf(
				"ALTER TABLE %s DROP FOREIGN KEY %s", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				esc_sql( $this->audit_log_table ),
				esc_sql( $constraint_name )
			) );
			
			// Log the removal if debug mode is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
				error_log( sprintf( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log -- Debug-only logging when WP_DEBUG enabled
					'DFX Parish Retreat Letters: Removed foreign key constraint %s from audit log table',
					$constraint_name
				) );
			}
		}
	}

	/**
	 * Fix invitations table unique constraint.
	 * 
	 * This method removes the problematic unique constraint that includes status
	 * and prevents duplicate entries when updating invitations to cancelled status.
	 *
	 * @since 1.4.3
	 */
	private function fix_invitations_unique_constraint() {
		global $wpdb;
		
		// Check if the invitations table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->invitations_table ) );
		if ( $table_exists !== $this->invitations_table ) {
			return; // Table doesn't exist, nothing to do
		}
		
		// Check if the problematic unique constraint exists
		$constraint_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) 
			FROM INFORMATION_SCHEMA.STATISTICS 
			WHERE TABLE_SCHEMA = DATABASE() 
			AND TABLE_NAME = %s 
			AND INDEX_NAME = 'unique_pending_invitation'",
			$this->invitations_table
		) );
		
		if ( $constraint_exists > 0 ) {
			// Drop the problematic unique constraint
			$wpdb->query( sprintf(
				"ALTER TABLE %s DROP INDEX %s", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				esc_sql( $this->invitations_table ),
				esc_sql( 'unique_pending_invitation' )
			) );
			
			// Log the removal if debug mode is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
				error_log( 'DFX Parish Retreat Letters: Removed problematic unique_pending_invitation constraint from invitations table' );
			}
		}
	}

	/**
	 * Perform database upgrades based on the current version.
	 * 
	 * This method runs the comprehensive setup_tables() method which uses dbDelta 
	 * to ensure all required tables and columns exist. This approach is simpler 
	 * and more reliable than incremental upgrades, as dbDelta safely handles 
	 * both fresh installations and upgrades without affecting existing data.
	 *
	 * @since 1.0.0
	 * @since 1.4.0 Simplified to always use comprehensive setup_tables() method
	 * @since 1.4.1 Added foreign key constraint removal for audit log table
	 * @since 1.4.3 Added invitations unique constraint fix
	 * @param string $from_version The version to upgrade from.
	 */
	private function upgrade_database( $from_version ) {
		// Remove foreign key constraints from audit log table before running setup
		// This prevents issues with user_id = 0 in permission audit logs
		$this->remove_audit_log_foreign_keys();
		
		// Fix the problematic unique constraint in invitations table
		// This prevents duplicate entry errors when cancelling invitations
		$this->fix_invitations_unique_constraint();
		
		// Migrate custom block ID format from bigint to varchar (v1.5.0)
		if ( version_compare( $from_version, '1.5.0', '<' ) ) {
			$this->migrate_custom_block_ids();
		}
		
		// Always run the comprehensive setup which handles all tables and columns using dbDelta
		// This is safer and simpler than incremental upgrades and works for both fresh installs
		// and upgrades from any previous version
		$this->setup_tables();
		
		// Log the upgrade (if in debug mode)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && function_exists( 'error_log' ) ) {
			error_log( sprintf(
				'DFX Parish Retreat Letters: Database upgraded from version %s to %s',
				$from_version,
				self::DB_VERSION
			) );
		}
	}

	/**
	 * Force a database upgrade (useful for development and testing).
	 *
	 * @since 1.0.0
	 */
	public function force_upgrade() {
		$current_version = $this->get_database_version();
		delete_option( self::DB_VERSION_OPTION );
		$this->upgrade_database( $current_version );
	}

	/**
	 * Get the current database schema version.
	 * This is the version defined in the class constant.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_schema_version() {
		return self::DB_VERSION;
	}

	/**
	 * Example method for future database upgrades.
	 * 
	 * With the simplified database management approach in v1.4.0, adding new 
	 * database changes is straightforward:
	 * 
	 * 1. Increment the DB_VERSION constant (e.g., to '1.5.0')
	 * 2. Add the new table/column definitions to setup_tables() method
	 * 3. The dbDelta function will automatically handle creating missing elements
	 * 
	 * Example adding a new column to retreats table:
	 * - Add the column definition in the CREATE TABLE statement in setup_tables()
	 * - dbDelta will automatically add the missing column to existing tables
	 * 
	 * This approach is much simpler and more reliable than version-specific 
	 * upgrade methods, as dbDelta safely handles both fresh installs and upgrades.
	 *
	 * @since 1.0.0
	 * @since 1.4.0 Updated documentation to reflect simplified approach
	 * @codeCoverageIgnore
	 */
	private function example_upgrade_method() {
		// This method serves as documentation and should not be called
	}
}
