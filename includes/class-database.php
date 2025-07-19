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
		add_option( 'dfx_parish_retreat_letters_db_version', '1.0.0' );
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
		delete_option( 'dfx_parish_retreat_letters_db_version' );
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
}