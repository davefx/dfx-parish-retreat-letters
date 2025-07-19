<?php
/**
 * The retreat model class
 *
 * Handles CRUD operations for retreats.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.0.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The retreat model class.
 *
 * This class handles all CRUD operations for retreats.
 *
 * @since      1.0.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters_Retreat {

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
	 * Create a new retreat.
	 *
	 * @since 1.0.0
	 * @param array $data Retreat data.
	 * @return int|false The retreat ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$sanitized_data = $this->sanitize_retreat_data( $data );
		if ( ! $this->validate_retreat_data( $sanitized_data ) ) {
			return false;
		}

		$result = $wpdb->insert(
			$this->database->get_retreats_table(),
			array(
				'name'           => $sanitized_data['name'],
				'location'       => $sanitized_data['location'],
				'start_date'     => $sanitized_data['start_date'],
				'end_date'       => $sanitized_data['end_date'],
				'custom_message' => $sanitized_data['custom_message'],
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get a retreat by ID.
	 *
	 * @since 1.0.0
	 * @param int $id Retreat ID.
	 * @return object|null The retreat object or null if not found.
	 */
	public function get( $id ) {
		global $wpdb;

		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->database->get_retreats_table()} WHERE id = %d",
			$id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $result;
	}

	/**
	 * Update a retreat.
	 *
	 * @since 1.0.0
	 * @param int   $id   Retreat ID.
	 * @param array $data Retreat data.
	 * @return bool True on success, false on failure.
	 */
	public function update( $id, $data ) {
		global $wpdb;

		$sanitized_data = $this->sanitize_retreat_data( $data );
		if ( ! $this->validate_retreat_data( $sanitized_data ) ) {
			return false;
		}

		$result = $wpdb->update(
			$this->database->get_retreats_table(),
			array(
				'name'           => $sanitized_data['name'],
				'location'       => $sanitized_data['location'],
				'start_date'     => $sanitized_data['start_date'],
				'end_date'       => $sanitized_data['end_date'],
				'custom_message' => $sanitized_data['custom_message'],
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Delete a retreat.
	 *
	 * @since 1.0.0
	 * @param int $id Retreat ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->database->get_retreats_table(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Get all retreats with optional filtering and pagination.
	 *
	 * @since 1.0.0
	 * @param array $args Query arguments.
	 * @return array Array of retreat objects.
	 */
	public function get_all( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'search'            => '',
			'orderby'           => 'start_date',
			'order'             => 'DESC',
			'per_page'          => 20,
			'page'              => 1,
			'include_attendant_count' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clause = '1=1';
		$where_values = array();

		// Add search functionality
		if ( ! empty( $args['search'] ) ) {
			$where_clause .= ' AND (name LIKE %s OR location LIKE %s)';
			$search_term   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Sanitize orderby and order
		$allowed_orderby = array( 'id', 'name', 'location', 'start_date', 'end_date', 'created_at' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'start_date';
		$order = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

		// Calculate offset
		$offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

		$sql = "SELECT * FROM {$this->database->get_retreats_table()} 
				WHERE {$where_clause} 
				ORDER BY {$orderby} {$order} 
				LIMIT %d OFFSET %d";

		$where_values[] = absint( $args['per_page'] );
		$where_values[] = $offset;

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		// Add attendant counts if requested
		if ( $args['include_attendant_count'] && ! empty( $results ) ) {
			$attendant_model = new DFX_Parish_Retreat_Letters_Attendant();
			$retreat_ids = wp_list_pluck( $results, 'id' );
			$attendant_counts = $attendant_model->get_counts_for_retreats( $retreat_ids );
			
			foreach ( $results as $retreat ) {
				$retreat->attendant_count = $attendant_counts[ $retreat->id ] ?? 0;
			}
		}

		return $results ? $results : array();
	}

	/**
	 * Get the total count of retreats.
	 *
	 * @since 1.0.0
	 * @param string $search Optional search term.
	 * @return int Total count.
	 */
	public function get_count( $search = '' ) {
		global $wpdb;

		$where_clause = '1=1';
		$where_values = array();

		if ( ! empty( $search ) ) {
			$where_clause .= ' AND (name LIKE %s OR location LIKE %s)';
			$search_term   = '%' . $wpdb->esc_like( $search ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$sql = "SELECT COUNT(*) FROM {$this->database->get_retreats_table()} WHERE {$where_clause}";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Sanitize retreat data.
	 *
	 * @since 1.0.0
	 * @param array $data Raw data.
	 * @return array Sanitized data.
	 */
	private function sanitize_retreat_data( $data ) {
		return array(
			'name'           => sanitize_text_field( $data['name'] ?? '' ),
			'location'       => sanitize_text_field( $data['location'] ?? '' ),
			'start_date'     => sanitize_text_field( $data['start_date'] ?? '' ),
			'end_date'       => sanitize_text_field( $data['end_date'] ?? '' ),
			'custom_message' => wp_kses_post( $data['custom_message'] ?? '' ),
		);
	}

	/**
	 * Validate retreat data.
	 *
	 * @since 1.0.0
	 * @param array $data Sanitized data.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_retreat_data( $data ) {
		// Check required fields
		if ( empty( $data['name'] ) || empty( $data['location'] ) || 
			 empty( $data['start_date'] ) || empty( $data['end_date'] ) ) {
			return false;
		}

		// Validate date formats
		if ( ! $this->is_valid_date( $data['start_date'] ) || ! $this->is_valid_date( $data['end_date'] ) ) {
			return false;
		}

		// Check that end date is not before start date
		if ( strtotime( $data['end_date'] ) < strtotime( $data['start_date'] ) ) {
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
}