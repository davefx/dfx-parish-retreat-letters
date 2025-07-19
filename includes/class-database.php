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
	const DB_VERSION = '1.1.0';

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
		$this->retreats_table = $wpdb->prefix . 'dfx_retreats';
		$this->attendants_table = $wpdb->prefix . 'dfx_attendants';
		
		// Only check for database upgrades if WordPress is fully loaded
		if ( did_action( 'init' ) || current_action() === 'init' ) {
			$this->maybe_upgrade_database();
		} else {
			// Hook to check for upgrades after WordPress is fully loaded
			add_action( 'init', array( $this, 'maybe_upgrade_database' ), 1 );
		}
	}

	/**
	 * Create the retreats table.
	 *
	 * @since 1.0.0
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Create retreats table
		$sql = "CREATE TABLE {$this->retreats_table} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			location varchar(255) NOT NULL,
			start_date date NOT NULL,
			end_date date NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_start_date (start_date),
			INDEX idx_end_date (end_date)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Create attendants table
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

		dbDelta( $attendants_sql );

		// Store the database version for future upgrades
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Drop the retreats table.
	 *
	 * @since 1.0.0
	 */
	public function drop_tables() {
		global $wpdb;
		// Drop attendants table first due to foreign key constraint
		$wpdb->query( "DROP TABLE IF EXISTS {$this->attendants_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$this->retreats_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
	 * Check if the database tables exist.
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
	 * @since 1.0.0
	 */
	public function maybe_upgrade_database() {
		$current_version = $this->get_database_version();
		
		// If no version is stored or version is different, upgrade
		if ( version_compare( $current_version, self::DB_VERSION, '<' ) ) {
			$this->upgrade_database( $current_version );
		}
	}

	/**
	 * Perform database upgrades based on the current version.
	 *
	 * @since 1.0.0
	 * @param string $from_version The version to upgrade from.
	 */
	private function upgrade_database( $from_version ) {
		// If upgrading from version 0.0.0 (fresh install) or no tables exist, create all tables
		if ( $from_version === '0.0.0' || ! $this->tables_exist() ) {
			$this->create_tables();
			return;
		}

		// Version-specific upgrades
		if ( version_compare( $from_version, '1.1.0', '<' ) ) {
			$this->upgrade_to_1_1_0();
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
			$this->create_tables();
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
	 *     $sql = "CREATE TABLE {$wpdb->prefix}dfx_new_table (...) $charset_collate;";
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