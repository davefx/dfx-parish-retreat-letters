<?php
/**
 * The responsible person model class
 *
 * Handles CRUD operations for responsible persons who can be assigned to attendants.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.7.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The responsible person model class.
 *
 * This class handles all CRUD operations for responsible persons.
 *
 * @since      1.7.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters_ResponsiblePerson {

	/**
	 * The database instance.
	 *
	 * @since 1.7.0
	 * @var DFX_Parish_Retreat_Letters_Database
	 */
	private $database;

	/**
	 * Constructor.
	 *
	 * @since 1.7.0
	 */
	public function __construct() {
		$this->database = DFX_Parish_Retreat_Letters_Database::get_instance();
	}

	/**
	 * Create a new responsible person.
	 *
	 * @since 1.7.0
	 * @param array $data Responsible person data.
	 * @return int|false The responsible person ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$sanitized_data = $this->sanitize_data( $data );
		if ( ! $this->validate_data( $sanitized_data ) ) {
			return false;
		}

		$result = $wpdb->insert(
			$this->database->get_responsible_persons_table(),
			array(
				'retreat_id' => $sanitized_data['retreat_id'],
				'name'       => $sanitized_data['name'],
			),
			array( '%d', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get a responsible person by ID.
	 *
	 * @since 1.7.0
	 * @param int $id Responsible person ID.
	 * @return object|null The responsible person object or null if not found.
	 */
	public function get( $id ) {
		global $wpdb;

		$table_name = $this->database->get_responsible_persons_table();
		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result;
	}

	/**
	 * Update a responsible person.
	 *
	 * @since 1.7.0
	 * @param int   $id   Responsible person ID.
	 * @param array $data Responsible person data.
	 * @return bool True on success, false on failure.
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$sanitized_data = $this->sanitize_data( $data );
		if ( ! $this->validate_data( $sanitized_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$this->database->get_responsible_persons_table(),
			array(
				'retreat_id' => $sanitized_data['retreat_id'],
				'name'       => $sanitized_data['name'],
			),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result !== false;
	}

	/**
	 * Delete a responsible person.
	 *
	 * @since 1.7.0
	 * @param int $id Responsible person ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $id ) {
		global $wpdb;

		// Check if this responsible person is assigned to any attendants
		$attendants_table = $this->database->get_attendants_table();
		$assigned_count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$attendants_table} WHERE responsible_person_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $assigned_count > 0 ) {
			// Set responsible_person_id to NULL for all attendants assigned to this person
			$wpdb->update(
				$attendants_table,
				array( 'responsible_person_id' => null ),
				array( 'responsible_person_id' => $id ),
				array( '%d' ),
				array( '%d' )
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		$result = $wpdb->delete(
			$this->database->get_responsible_persons_table(),
			array( 'id' => $id ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result !== false;
	}

	/**
	 * Get all responsible persons for a specific retreat.
	 *
	 * @since 1.7.0
	 * @param int $retreat_id Retreat ID.
	 * @return array Array of responsible person objects.
	 */
	public function get_by_retreat( $retreat_id ) {
		global $wpdb;

		$table_name = $this->database->get_responsible_persons_table();
		$sql = $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE retreat_id = %d ORDER BY name ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$retreat_id
		);

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		return $results ? $results : array();
	}

	/**
	 * Delete all responsible persons for a specific retreat.
	 *
	 * @since 1.7.0
	 * @param int $retreat_id Retreat ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_by_retreat( $retreat_id ) {
		global $wpdb;

		// First, get all responsible person IDs for this retreat
		$table_name = $this->database->get_responsible_persons_table();
		$person_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$table_name} WHERE retreat_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$retreat_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Clear responsible_person_id from all attendants assigned to these persons
		if ( ! empty( $person_ids ) ) {
			$attendants_table = $this->database->get_attendants_table();
			$placeholders = implode( ', ', array_fill( 0, count( $person_ids ), '%d' ) );
			$sql = "UPDATE {$attendants_table} SET responsible_person_id = NULL WHERE responsible_person_id IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( $wpdb->prepare( $sql, $person_ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		}

		// Then delete all responsible persons for this retreat
		$result = $wpdb->delete(
			$this->database->get_responsible_persons_table(),
			array( 'retreat_id' => $retreat_id ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result !== false;
	}

	/**
	 * Sanitize responsible person data.
	 *
	 * @since 1.7.0
	 * @param array $data Raw data.
	 * @return array Sanitized data.
	 */
	private function sanitize_data( $data ) {
		return array(
			'retreat_id' => absint( $data['retreat_id'] ?? 0 ),
			'name'       => sanitize_text_field( $data['name'] ?? '' ),
		);
	}

	/**
	 * Validate responsible person data.
	 *
	 * @since 1.7.0
	 * @param array $data Sanitized data.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_data( $data ) {
		// Check required fields
		if ( empty( $data['retreat_id'] ) || empty( $data['name'] ) ) {
			return false;
		}

		// Check if retreat exists
		global $wpdb;
		$retreats_table = $this->database->get_retreats_table();
		$retreat_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$retreats_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$data['retreat_id']
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! $retreat_exists ) {
			return false;
		}

		return true;
	}
}
