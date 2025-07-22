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
	const DB_VERSION = '1.3.0';

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
		$this->retreats_table = $wpdb->prefix . 'dfx_prl_retreats';
		$this->attendants_table = $wpdb->prefix . 'dfx_prl_attendants';
		$this->messages_table = $wpdb->prefix . 'dfx_prl_confidential_messages';
		$this->message_files_table = $wpdb->prefix . 'dfx_prl_message_files';
		$this->message_print_log_table = $wpdb->prefix . 'dfx_prl_message_print_log';
		$this->permissions_table = $wpdb->prefix . 'dfx_prl_retreat_permissions';
		$this->invitations_table = $wpdb->prefix . 'dfx_prl_retreat_invitations';
		$this->audit_log_table = $wpdb->prefix . 'dfx_prl_permission_audit_log';
		
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
	 * @since 1.3.0 Renamed to setup_tables() and enhanced for comprehensive database setup
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
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_start_date (start_date),
			INDEX idx_end_date (end_date)
		) $charset_collate;";

		dbDelta( $retreats_sql );

		// Create attendants table (with message_url_token from v1.2.0)
		$attendants_sql = "CREATE TABLE {$this->attendants_table} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			retreat_id mediumint(9) NOT NULL,
			name varchar(255) NOT NULL,
			surnames varchar(255) NOT NULL,
			date_of_birth date NOT NULL,
			emergency_contact_name varchar(255) NOT NULL,
			emergency_contact_surname varchar(255) NOT NULL,
			emergency_contact_phone varchar(20) NOT NULL,
			message_url_token VARCHAR(255) NULL DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_retreat_id (retreat_id),
			INDEX idx_name (name),
			INDEX idx_surnames (surnames),
			INDEX idx_message_url_token (message_url_token),
			FOREIGN KEY (retreat_id) REFERENCES {$this->retreats_table}(id) ON DELETE CASCADE
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
			INDEX idx_ip_anonymized_at (ip_anonymized_at),
			FOREIGN KEY (attendant_id) REFERENCES {$this->attendants_table}(id) ON DELETE CASCADE
		) $charset_collate;";

		dbDelta( $messages_sql );

		// Create message files table (from v1.2.0)
		$files_sql = "CREATE TABLE {$this->message_files_table} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			message_id mediumint(9) NOT NULL,
			original_filename varchar(255) NOT NULL,
			encrypted_filename varchar(255) NOT NULL,
			file_type varchar(50) NOT NULL,
			file_size int(11) NOT NULL,
			encrypted_file_path text NOT NULL,
			file_salt varchar(255) NOT NULL,
			uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_message_id (message_id),
			INDEX idx_file_type (file_type),
			INDEX idx_uploaded_at (uploaded_at),
			FOREIGN KEY (message_id) REFERENCES {$this->messages_table}(id) ON DELETE CASCADE
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
			INDEX idx_printed_at (printed_at),
			FOREIGN KEY (message_id) REFERENCES {$this->messages_table}(id) ON DELETE CASCADE,
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
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
			INDEX idx_is_active (is_active),
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			FOREIGN KEY (retreat_id) REFERENCES {$this->retreats_table}(id) ON DELETE CASCADE,
			FOREIGN KEY (granted_by) REFERENCES {$wpdb->users}(ID) ON DELETE RESTRICT
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
			UNIQUE KEY unique_pending_invitation (retreat_id, email, permission_level, status),
			INDEX idx_retreat_id (retreat_id),
			INDEX idx_email (email),
			INDEX idx_token (token),
			INDEX idx_invited_by (invited_by),
			INDEX idx_invited_at (invited_at),
			INDEX idx_expires_at (expires_at),
			INDEX idx_status (status),
			FOREIGN KEY (retreat_id) REFERENCES {$this->retreats_table}(id) ON DELETE CASCADE,
			FOREIGN KEY (invited_by) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			FOREIGN KEY (created_user_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
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
			INDEX idx_performed_at (performed_at),
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			FOREIGN KEY (retreat_id) REFERENCES {$this->retreats_table}(id) ON DELETE CASCADE,
			FOREIGN KEY (performed_by) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
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
		$column_exists = $wpdb->get_results( $wpdb->prepare(
			"SHOW COLUMNS FROM {$this->attendants_table} LIKE %s",
			'message_url_token'
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $column_exists ) ) {
			return false;
		}

		// Check if custom_message column exists in retreats table
		$custom_message_exists = $wpdb->get_results( $wpdb->prepare(
			"SHOW COLUMNS FROM {$this->retreats_table} LIKE %s",
			'custom_message'
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

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
	 * This method checks both the stored database version and the actual database
	 * structure to determine if setup is needed. This handles cases where the
	 * stored version might be correct but the actual structure is incomplete.
	 *
	 * @since 1.0.0
	 */
	public function maybe_upgrade_database() {
		$current_version = $this->get_database_version();
		
		// Check if version differs OR if database structure is incomplete
		$version_differs = version_compare( $current_version, self::DB_VERSION, '<' );
		$structure_incomplete = ! $this->is_database_structure_complete();
		
		if ( $version_differs || $structure_incomplete ) {
			$this->upgrade_database( $current_version );
		}
	}

	/**
	 * Perform database upgrades based on the current version.
	 * 
	 * For fresh installations or incomplete structures, this method runs the
	 * comprehensive setup_tables() method which uses dbDelta to ensure all
	 * required tables and columns exist. For existing installations with 
	 * complete structures, it runs only the necessary version-specific upgrades.
	 *
	 * @since 1.0.0
	 * @param string $from_version The version to upgrade from.
	 */
	private function upgrade_database( $from_version ) {
		// If upgrading from version 0.0.0 (fresh install), no tables exist, or structure is incomplete,
		// run the comprehensive setup which handles all tables and columns using dbDelta
		if ( $from_version === '0.0.0' || ! $this->tables_exist() || ! $this->is_database_structure_complete() ) {
			$this->setup_tables();
			return;
		}

		// For existing installations with complete basic structure, run only necessary version-specific upgrades
		// This maintains backward compatibility for installations that were properly upgraded step-by-step
		if ( version_compare( $from_version, '1.1.0', '<' ) ) {
			$this->upgrade_to_1_1_0();
		}

		if ( version_compare( $from_version, '1.2.0', '<' ) ) {
			$this->upgrade_to_1_2_0();
		}

		if ( version_compare( $from_version, '1.2.1', '<' ) ) {
			$this->upgrade_to_1_2_1();
		}

		if ( version_compare( $from_version, '1.3.0', '<' ) ) {
			$this->upgrade_to_1_3_0();
		}

		// Update the database version to current
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
		
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
	 * @param bool $force_recreate Whether to drop and recreate all tables.
	 */
	public function force_upgrade( $force_recreate = false ) {
		if ( $force_recreate ) {
			$this->drop_tables();
			$this->setup_tables();
		} else {
			$current_version = $this->get_database_version();
			delete_option( self::DB_VERSION_OPTION );
			$this->upgrade_database( $current_version );
		}
	}

	/**
	 * Upgrade database to version 1.1.0.
	 * This upgrade adds the attendants table if it doesn't exist.
	 *
	 * @since 1.0.0
	 */
	private function upgrade_to_1_1_0() {
		global $wpdb;

		// Check if attendants table exists
		$attendants_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->attendants_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		
		if ( $attendants_exist !== $this->attendants_table ) {
			// Create attendants table
			$charset_collate = $wpdb->get_charset_collate();
			
			$attendants_sql = "CREATE TABLE {$this->attendants_table} (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				retreat_id mediumint(9) NOT NULL,
				name varchar(255) NOT NULL,
				surnames varchar(255) NOT NULL,
				date_of_birth date NOT NULL,
				emergency_contact_name varchar(255) NOT NULL,
				emergency_contact_surname varchar(255) NOT NULL,
				emergency_contact_phone varchar(20) NOT NULL,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				INDEX idx_retreat_id (retreat_id),
				INDEX idx_name (name),
				INDEX idx_surnames (surnames),
				FOREIGN KEY (retreat_id) REFERENCES {$this->retreats_table}(id) ON DELETE CASCADE
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $attendants_sql );
		}
	}

	/**
	 * Upgrade database to version 1.2.0.
	 * This upgrade adds the confidential message system tables and message_url_token to attendants.
	 *
	 * @since 1.2.0
	 */
	private function upgrade_to_1_2_0() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Add message_url_token to attendants table if it doesn't exist
		$column_exists = $wpdb->get_results( $wpdb->prepare(
			"SHOW COLUMNS FROM {$this->attendants_table} LIKE %s",
			'message_url_token'
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $column_exists ) ) {
			$wpdb->query( "ALTER TABLE {$this->attendants_table} ADD COLUMN message_url_token VARCHAR(255) NULL DEFAULT NULL AFTER emergency_contact_phone" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$this->attendants_table} ADD INDEX idx_message_url_token (message_url_token)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		// Generate tokens for existing attendants that don't have them
		$this->generate_missing_tokens();

		// Create confidential messages table
		$messages_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->messages_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		
		if ( $messages_exist !== $this->messages_table ) {
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
				INDEX idx_ip_anonymized_at (ip_anonymized_at),
				FOREIGN KEY (attendant_id) REFERENCES {$this->attendants_table}(id) ON DELETE CASCADE
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $messages_sql );
		}

		// Create message files table
		$files_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->message_files_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		
		if ( $files_exist !== $this->message_files_table ) {
			$files_sql = "CREATE TABLE {$this->message_files_table} (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				message_id mediumint(9) NOT NULL,
				original_filename varchar(255) NOT NULL,
				encrypted_filename varchar(255) NOT NULL,
				file_type varchar(50) NOT NULL,
				file_size int(11) NOT NULL,
				encrypted_file_path text NOT NULL,
				file_salt varchar(255) NOT NULL,
				uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				INDEX idx_message_id (message_id),
				INDEX idx_file_type (file_type),
				INDEX idx_uploaded_at (uploaded_at),
				FOREIGN KEY (message_id) REFERENCES {$this->messages_table}(id) ON DELETE CASCADE
			) $charset_collate;";

			dbDelta( $files_sql );
		}

		// Create message print log table
		$print_log_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->message_print_log_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		
		if ( $print_log_exist !== $this->message_print_log_table ) {
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
				INDEX idx_printed_at (printed_at),
				FOREIGN KEY (message_id) REFERENCES {$this->messages_table}(id) ON DELETE CASCADE,
				FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
			) $charset_collate;";

			dbDelta( $print_log_sql );
		}
	}

	/**
	 * Generate tokens for existing attendants that don't have them.
	 *
	 * @since 1.2.0
	 */
	private function generate_missing_tokens() {
		global $wpdb;
		
		// Get attendants without tokens
		$attendants_without_tokens = $wpdb->get_results(
			"SELECT id FROM {$this->attendants_table} WHERE message_url_token IS NULL OR message_url_token = ''"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $attendants_without_tokens ) ) {
			return;
		}

		// Only include security class if it's available
		if ( class_exists( 'DFX_Parish_Retreat_Letters_Security' ) ) {
			$security = DFX_Parish_Retreat_Letters_Security::get_instance();
			
			foreach ( $attendants_without_tokens as $attendant ) {
				$token = $security->generate_unique_message_token();
				$wpdb->update(
					$this->attendants_table,
					array( 'message_url_token' => $token ),
					array( 'id' => $attendant->id ),
					array( '%s' ),
					array( '%d' )
				);
			}
		}
	}

	/**
	 * Upgrade database to version 1.2.1.
	 * This upgrade adds custom_message field to retreats table.
	 *
	 * @since 1.2.1
	 */
	private function upgrade_to_1_2_1() {
		global $wpdb;

		// Add custom_message field to retreats table if it doesn't exist
		$column_exists = $wpdb->get_results( $wpdb->prepare(
			"SHOW COLUMNS FROM {$this->retreats_table} LIKE %s",
			'custom_message'
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $column_exists ) ) {
			$wpdb->query( "ALTER TABLE {$this->retreats_table} ADD COLUMN custom_message text NULL DEFAULT NULL AFTER end_date" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * Upgrade database to version 1.3.0.
	 * This upgrade adds the authorization system tables.
	 *
	 * @since 1.3.0
	 */
	private function upgrade_to_1_3_0() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Create retreat permissions table
		$permissions_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->permissions_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		
		if ( $permissions_exist !== $this->permissions_table ) {
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
				INDEX idx_is_active (is_active),
				FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
				FOREIGN KEY (retreat_id) REFERENCES {$this->retreats_table}(id) ON DELETE CASCADE,
				FOREIGN KEY (granted_by) REFERENCES {$wpdb->users}(ID) ON DELETE RESTRICT
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $permissions_sql );
		}

		// Create retreat invitations table
		$invitations_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->invitations_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		
		if ( $invitations_exist !== $this->invitations_table ) {
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
				UNIQUE KEY unique_pending_invitation (retreat_id, email, permission_level, status),
				INDEX idx_retreat_id (retreat_id),
				INDEX idx_email (email),
				INDEX idx_token (token),
				INDEX idx_invited_by (invited_by),
				INDEX idx_invited_at (invited_at),
				INDEX idx_expires_at (expires_at),
				INDEX idx_status (status),
				FOREIGN KEY (retreat_id) REFERENCES {$this->retreats_table}(id) ON DELETE CASCADE,
				FOREIGN KEY (invited_by) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
				FOREIGN KEY (created_user_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL
			) $charset_collate;";

			dbDelta( $invitations_sql );
		}

		// Create permission audit log table
		$audit_log_exist = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $this->audit_log_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		
		if ( $audit_log_exist !== $this->audit_log_table ) {
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
				INDEX idx_performed_at (performed_at),
				FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
				FOREIGN KEY (retreat_id) REFERENCES {$this->retreats_table}(id) ON DELETE CASCADE,
				FOREIGN KEY (performed_by) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
			) $charset_collate;";

			dbDelta( $audit_log_sql );
		}

		// Add the manage_retreat_plugin capability to administrator role
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'manage_retreat_plugin' );
		}
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
	 * To add a new database upgrade:
	 * 1. Increment the DB_VERSION constant (e.g., to '1.2.0')
	 * 2. Add a version check in upgrade_database() method
	 * 3. Create a new upgrade method following this pattern:
	 * 
	 * private function upgrade_to_1_2_0() {
	 *     global $wpdb;
	 *     
	 *     // Example: Add a new column to retreats table
	 *     $wpdb->query("ALTER TABLE {$this->retreats_table} ADD COLUMN new_field varchar(255) DEFAULT ''");
	 *     
	 *     // Example: Create a new table
	 *     $charset_collate = $wpdb->get_charset_collate();
	 *     $sql = "CREATE TABLE {$wpdb->prefix}dfx_prl_new_table (...) $charset_collate;";
	 *     require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	 *     dbDelta( $sql );
	 * }
	 *
	 * @since 1.0.0
	 * @codeCoverageIgnore
	 */
	private function example_upgrade_method() {
		// This method serves as documentation and should not be called
	}
}
