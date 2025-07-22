<?php
/**
 * The attendant model class
 *
 * Handles CRUD operations for attendants.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.0.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The attendant model class.
 *
 * This class handles all CRUD operations for attendants.
 *
 * @since      1.0.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters_Attendant {

	/**
	 * The database instance.
	 *
	 * @since 1.0.0
	 * @var DFX_Parish_Retreat_Letters_Database
	 */
	private $database;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->database = DFX_Parish_Retreat_Letters_Database::get_instance();
	}

	/**
	 * Create a new attendant.
	 *
	 * @since 1.0.0
	 * @param array $data Attendant data.
	 * @return int|false The attendant ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$sanitized_data = $this->sanitize_attendant_data( $data );
		if ( ! $this->validate_attendant_data( $sanitized_data ) ) {
			return false;
		}

		// Generate unique message URL token automatically
		$security = DFX_Parish_Retreat_Letters_Security::get_instance();
		$message_url_token = $security->generate_unique_message_token();

		$result = $wpdb->insert(
			$this->database->get_attendants_table(),
			array(
				'retreat_id'                => $sanitized_data['retreat_id'],
				'name'                      => $sanitized_data['name'],
				'surnames'                  => $sanitized_data['surnames'],
				'date_of_birth'             => $sanitized_data['date_of_birth'],
				'emergency_contact_name'    => $sanitized_data['emergency_contact_name'],
				'emergency_contact_surname' => $sanitized_data['emergency_contact_surname'],
				'emergency_contact_phone'   => $sanitized_data['emergency_contact_phone'],
				'message_url_token'         => $message_url_token,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get an attendant by ID.
	 *
	 * @since 1.0.0
	 * @param int $id Attendant ID.
	 * @return object|null The attendant object or null if not found.
	 */
	public function get( $id ) {
		global $wpdb;

		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->database->get_attendants_table()} WHERE id = %d",
			$id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $result;
	}

	/**
	 * Update an attendant.
	 *
	 * @since 1.0.0
	 * @param int   $id   Attendant ID.
	 * @param array $data Attendant data.
	 * @return bool True on success, false on failure.
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$sanitized_data = $this->sanitize_attendant_data( $data );
		if ( ! $this->validate_attendant_data( $sanitized_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$this->database->get_attendants_table(),
			array(
				'retreat_id'                => $sanitized_data['retreat_id'],
				'name'                      => $sanitized_data['name'],
				'surnames'                  => $sanitized_data['surnames'],
				'date_of_birth'             => $sanitized_data['date_of_birth'],
				'emergency_contact_name'    => $sanitized_data['emergency_contact_name'],
				'emergency_contact_surname' => $sanitized_data['emergency_contact_surname'],
				'emergency_contact_phone'   => $sanitized_data['emergency_contact_phone'],
			),
			array( 'id' => $id ),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete an attendant.
	 *
	 * @since 1.0.0
	 * @param int $id Attendant ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->database->get_attendants_table(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get all attendants for a specific retreat with optional filtering and pagination.
	 *
	 * @since 1.0.0
	 * @param int   $retreat_id Retreat ID.
	 * @param array $args       Query arguments.
	 * @return array Array of attendant objects.
	 */
	public function get_by_retreat( $retreat_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'search'   => '',
			'orderby'  => 'name',
			'order'    => 'ASC',
			'per_page' => 20,
			'page'     => 1,
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clause = 'retreat_id = %d';
		$where_values = array( $retreat_id );

		// Add search functionality
		if ( ! empty( $args['search'] ) ) {
			$where_clause .= ' AND (name LIKE %s OR surnames LIKE %s OR emergency_contact_name LIKE %s OR emergency_contact_surname LIKE %s)';
			$search_term   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Sanitize orderby and order
		$allowed_orderby = array( 'id', 'name', 'surnames', 'date_of_birth', 'emergency_contact_name', 'emergency_contact_surname', 'created_at' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'name';
		$order = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'ASC';

		// Handle pagination - check if per_page is -1 (meaning get all records)
		if ( $args['per_page'] === -1 ) {
			// No pagination - get all records
			$sql = "SELECT * FROM {$this->database->get_attendants_table()} 
					WHERE {$where_clause} 
					ORDER BY {$orderby} {$order}";
		} else {
			// Calculate offset for pagination
			$offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

			$sql = "SELECT * FROM {$this->database->get_attendants_table()} 
					WHERE {$where_clause} 
					ORDER BY {$orderby} {$order} 
					LIMIT %d OFFSET %d";

			$where_values[] = absint( $args['per_page'] );
			$where_values[] = $offset;
		}

		$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		return $results ? $results : array();
	}

	/**
	 * Get the total count of attendants for a specific retreat.
	 *
	 * @since 1.0.0
	 * @param int    $retreat_id Retreat ID.
	 * @param string $search     Optional search term.
	 * @return int Total count.
	 */
	public function get_count_by_retreat( $retreat_id, $search = '' ) {
		global $wpdb;

		$where_clause = 'retreat_id = %d';
		$where_values = array( $retreat_id );

		if ( ! empty( $search ) ) {
			$where_clause .= ' AND (name LIKE %s OR surnames LIKE %s OR emergency_contact_name LIKE %s OR emergency_contact_surname LIKE %s)';
			$search_term   = '%' . $wpdb->esc_like( $search ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$sql = "SELECT COUNT(*) FROM {$this->database->get_attendants_table()} WHERE {$where_clause}";

		$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get attendant count for multiple retreats.
	 *
	 * @since 1.0.0
	 * @param array $retreat_ids Array of retreat IDs.
	 * @return array Associative array with retreat_id as key and count as value.
	 */
	public function get_counts_for_retreats( $retreat_ids ) {
		global $wpdb;

		if ( empty( $retreat_ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $retreat_ids ), '%d' ) );
		$sql = "SELECT retreat_id, COUNT(*) as count 
				FROM {$this->database->get_attendants_table()} 
				WHERE retreat_id IN ($placeholders) 
				GROUP BY retreat_id";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $retreat_ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		$counts = array();
		foreach ( $results as $result ) {
			$counts[ $result->retreat_id ] = (int) $result->count;
		}

		return $counts;
	}

	/**
	 * Delete all attendants for a specific retreat.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id Retreat ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_by_retreat( $retreat_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->database->get_attendants_table(),
			array( 'retreat_id' => $retreat_id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Export attendants for a retreat as CSV data.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id Retreat ID.
	 * @return array CSV data with headers and rows.
	 */
	public function export_csv_data( $retreat_id ) {
		$attendants = $this->get_by_retreat( $retreat_id, array( 'per_page' => -1 ) );

		$headers = array(
			__( 'Name', 'dfx-parish-retreat-letters' ),
			__( 'Surnames', 'dfx-parish-retreat-letters' ),
			__( 'Date of Birth', 'dfx-parish-retreat-letters' ),
			__( 'Emergency Contact Name', 'dfx-parish-retreat-letters' ),
			__( 'Emergency Contact Surname', 'dfx-parish-retreat-letters' ),
			__( 'Emergency Contact Phone', 'dfx-parish-retreat-letters' ),
		);

		$rows = array();
		foreach ( $attendants as $attendant ) {
			$rows[] = array(
				$attendant->name,
				$attendant->surnames,
				$attendant->date_of_birth,
				$attendant->emergency_contact_name,
				$attendant->emergency_contact_surname,
				$attendant->emergency_contact_phone,
			);
		}

		return array(
			'headers' => $headers,
			'rows'    => $rows,
		);
	}

	/**
	 * Sanitize attendant data.
	 *
	 * @since 1.0.0
	 * @param array $data Raw data.
	 * @return array Sanitized data.
	 */
	private function sanitize_attendant_data( $data ) {
		return array(
			'retreat_id'                => absint( $data['retreat_id'] ?? 0 ),
			'name'                      => sanitize_text_field( $data['name'] ?? '' ),
			'surnames'                  => sanitize_text_field( $data['surnames'] ?? '' ),
			'date_of_birth'             => sanitize_text_field( $data['date_of_birth'] ?? '' ),
			'emergency_contact_name'    => sanitize_text_field( $data['emergency_contact_name'] ?? '' ),
			'emergency_contact_surname' => sanitize_text_field( $data['emergency_contact_surname'] ?? '' ),
			'emergency_contact_phone'   => sanitize_text_field( $data['emergency_contact_phone'] ?? '' ),
		);
	}

	/**
	 * Validate attendant data.
	 *
	 * @since 1.0.0
	 * @param array $data Sanitized data.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_attendant_data( $data ) {
		$admin_manager = DFX_Parish_Retreat_Letters_Admin::get_instance();


		// Check required fields
		if ( empty( $data['retreat_id'] ) || empty( $data['name'] ) || empty( $data['surnames'] ) || 
			 empty( $data['date_of_birth'] ) || empty( $data['emergency_contact_name'] ) || 
			 empty( $data['emergency_contact_surname'] ) || empty( $data['emergency_contact_phone'] ) ) {
			$admin_manager->add_admin_notice(
				__( 'All fields are required.', 'dfx-parish-retreat-letters' ),
				'error'
			);
			return false;
		}

		// Validate date format
		if ( ! $this->is_valid_date( $data['date_of_birth'] ) ) {
			$admin_manager->add_admin_notice(
				__( 'Invalid date of birth format. Please use YYYY-MM-DD.', 'dfx-parish-retreat-letters' ),
				'error'
			);
			return false;
		}

		// Basic phone number validation (allows various formats)
		if ( ! preg_match( '/^[\d\s\-\+\(\)\.]+$/', $data['emergency_contact_phone'] ) ) {
			$admin_manager->add_admin_notice(
				__( 'Invalid emergency contact phone number format.', 'dfx-parish-retreat-letters' ),
				'error'
			);
			return false;
		}

		// Check if retreat exists
		global $wpdb;
		$retreat_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->database->get_retreats_table()} WHERE id = %d",
			$data['retreat_id']
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $retreat_exists ) {
			$admin_manager->add_admin_notice(
				__( 'The specified retreat does not exist.', 'dfx-parish-retreat-letters' ),
				'error'
			);
			return false;
		}

		return true;
	}

	/**
	 * Check if a date string is valid.
	 *
	 * @since 1.0.0
	 * @param string $date Date string.
	 * @return bool True if valid, false otherwise.
	 */
	private function is_valid_date( $date ) {
		$parsed_date = date_parse( $date );
		return checkdate( $parsed_date['month'], $parsed_date['day'], $parsed_date['year'] );
	}

	public function exists( $retreat_id, $name, $surnames, $date_of_birth ) {
		global $wpdb;

		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$this->database->get_attendants_table()} 
			WHERE retreat_id = %d AND name = %s AND surnames = %s AND date_of_birth = %s",
			$retreat_id, $name, $surnames, $date_of_birth
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (bool) $exists;
	}
}