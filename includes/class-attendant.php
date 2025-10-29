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
				'retreat_id'                      => $sanitized_data['retreat_id'],
				'name'                            => $sanitized_data['name'],
				'surnames'                        => $sanitized_data['surnames'],
				'date_of_birth'                   => $sanitized_data['date_of_birth'],
				'emergency_contact_name'          => $sanitized_data['emergency_contact_name'],
				'emergency_contact_surname'       => $sanitized_data['emergency_contact_surname'],
				'emergency_contact_phone'         => $sanitized_data['emergency_contact_phone'],
				'emergency_contact_email'         => $sanitized_data['emergency_contact_email'],
				'emergency_contact_relationship'  => $sanitized_data['emergency_contact_relationship'],
				'invited_by'                      => $sanitized_data['invited_by'],
				'incompatibilities'               => $sanitized_data['incompatibilities'],
				'message_url_token'               => $message_url_token,
				'notes'                           => $sanitized_data['notes'],
				'internal_notes'                  => $sanitized_data['internal_notes'],
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
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

		$table_name = $this->database->get_attendants_table();
		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

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

		// Build update array - only include fields that are present in input data
		$update_fields = array(
			'retreat_id'                      => $sanitized_data['retreat_id'],
			'name'                            => $sanitized_data['name'],
			'surnames'                        => $sanitized_data['surnames'],
			'date_of_birth'                   => $sanitized_data['date_of_birth'],
			'emergency_contact_name'          => $sanitized_data['emergency_contact_name'],
			'emergency_contact_surname'       => $sanitized_data['emergency_contact_surname'],
			'emergency_contact_phone'         => $sanitized_data['emergency_contact_phone'],
			'emergency_contact_email'         => $sanitized_data['emergency_contact_email'],
			'emergency_contact_relationship'  => $sanitized_data['emergency_contact_relationship'],
			'invited_by'                      => $sanitized_data['invited_by'],
			'incompatibilities'               => $sanitized_data['incompatibilities'],
		);

		// Only include notes if it was provided in the input data
		if ( array_key_exists( 'notes', $data ) ) {
			$update_fields['notes'] = $sanitized_data['notes'];
		}

		// Only include internal_notes if it was provided in the input data
		if ( array_key_exists( 'internal_notes', $data ) ) {
			$update_fields['internal_notes'] = $sanitized_data['internal_notes'];
		}

		// Build format array based on fields being updated
		$formats = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
		if ( array_key_exists( 'notes', $data ) ) {
			$formats[] = '%s';
		}
		if ( array_key_exists( 'internal_notes', $data ) ) {
			$formats[] = '%s';
		}

		$result = $wpdb->update(
			$this->database->get_attendants_table(),
			$update_fields,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result !== false;
	}

	/**
	 * Delete an attendant with cascade delete for related messages.
	 * This method implements cascade delete functionality to replace database foreign key constraints.
	 *
	 * @since 1.0.0
	 * @param int $id Attendant ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $id ) {
		global $wpdb;

		// First delete all messages for this attendant (cascade delete)
		$message_model = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
		$message_model->delete_by_attendant( $id );

		// Then delete the attendant
		$result = $wpdb->delete(
			$this->database->get_attendants_table(),
			array( 'id' => $id ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

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
			'search'                       => '',
			'filter_name'                  => '',
			'filter_surnames'              => '',
			'filter_invited_by'            => '',
			'filter_incompatibilities'     => '',
			'filter_emergency_contact'     => '',
			'filter_notes'                 => '',
			'filter_internal_notes'        => '',
			'orderby'                      => 'name',
			'order'                        => 'ASC',
			'per_page'                     => 100,
			'page'                         => 1,
		);

		$args = wp_parse_args( $args, $defaults );

		$attendants_table = $this->database->get_attendants_table();
		$messages_table = $this->database->get_messages_table();
		
		$where_clause = "a.retreat_id = %d";
		$where_values = array( $retreat_id );

		// Add search functionality
		if ( ! empty( $args['search'] ) ) {
			$where_clause .= ' AND (a.name LIKE %s OR a.surnames LIKE %s OR a.emergency_contact_name LIKE %s OR a.emergency_contact_surname LIKE %s OR a.emergency_contact_email LIKE %s OR a.invited_by LIKE %s OR a.incompatibilities LIKE %s)';
			$search_term   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Add individual field filters
		if ( ! empty( $args['filter_name'] ) ) {
			$where_clause .= ' AND a.name LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['filter_name'] ) . '%';
		}
		if ( ! empty( $args['filter_surnames'] ) ) {
			$where_clause .= ' AND a.surnames LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['filter_surnames'] ) . '%';
		}
		if ( ! empty( $args['filter_invited_by'] ) ) {
			$where_clause .= ' AND a.invited_by LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['filter_invited_by'] ) . '%';
		}
		if ( ! empty( $args['filter_incompatibilities'] ) ) {
			$where_clause .= ' AND a.incompatibilities LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['filter_incompatibilities'] ) . '%';
		}
		if ( ! empty( $args['filter_emergency_contact'] ) ) {
			$where_clause .= ' AND (a.emergency_contact_name LIKE %s OR a.emergency_contact_surname LIKE %s OR a.emergency_contact_email LIKE %s OR a.emergency_contact_phone LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['filter_emergency_contact'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}
		if ( ! empty( $args['filter_notes'] ) ) {
			$where_clause .= ' AND a.notes LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['filter_notes'] ) . '%';
		}
		if ( ! empty( $args['filter_internal_notes'] ) ) {
			$where_clause .= ' AND a.internal_notes LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $args['filter_internal_notes'] ) . '%';
		}

		// Sanitize orderby and order
		$allowed_orderby = array( 
			'id', 'name', 'surnames', 'date_of_birth', 'emergency_contact_name', 
			'emergency_contact_surname', 'created_at', 'invited_by', 'incompatibilities',
			'message_count', 'non_printed_count'
		);
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'name';
		$order = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'ASC';

		// Build the SELECT clause - add message counts if sorting by them
		$select_clause = "a.*";
		$join_clause = "";
		$group_clause = "";
		
		if ( $orderby === 'message_count' || $orderby === 'non_printed_count' ) {
			$print_log_table = $this->database->get_message_print_log_table();
			$select_clause = "a.*, COUNT(DISTINCT m.id) as message_count, SUM(CASE WHEN m.id IS NOT NULL AND p.id IS NULL THEN 1 ELSE 0 END) as non_printed_count";
			$join_clause = "LEFT JOIN {$messages_table} m ON a.id = m.attendant_id LEFT JOIN {$print_log_table} p ON m.id = p.message_id";
			$group_clause = "GROUP BY a.id";
		}

		// Build ORDER BY clause
		$order_clause = "";
		if ( $orderby === 'message_count' || $orderby === 'non_printed_count' ) {
			$order_clause = "{$orderby} {$order}, a.name ASC";
		} else {
			$order_clause = "a.{$orderby} {$order}";
		}

		// Handle pagination - check if per_page is -1 (meaning get all records)
		if ( $args['per_page'] === -1 ) {
			// No pagination - get all records
			$sql = "SELECT {$select_clause} FROM {$attendants_table} a 
					{$join_clause}
					WHERE {$where_clause} 
					{$group_clause}
					ORDER BY {$order_clause}";
		} else {
			// Calculate offset for pagination
			$offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

			$sql = "SELECT {$select_clause} FROM {$attendants_table} a 
					{$join_clause}
					WHERE {$where_clause} 
					{$group_clause}
					ORDER BY {$order_clause} 
					LIMIT %d OFFSET %d";

			$where_values[] = absint( $args['per_page'] );
			$where_values[] = $offset;
		}

		$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		return $results ? $results : array();
	}

	/**
	 * Get the total count of attendants for a specific retreat.
	 *
	 * @since 1.0.0
	 * @param int   $retreat_id Retreat ID.
	 * @param string $search     Optional search term.
	 * @param array $filters    Optional filters array.
	 * @return int Total count.
	 */
	public function get_count_by_retreat( $retreat_id, $search = '', $filters = array() ) {
		global $wpdb;

		$where_clause = 'retreat_id = %d';
		$where_values = array( $retreat_id );

		if ( ! empty( $search ) ) {
			$where_clause .= ' AND (name LIKE %s OR surnames LIKE %s OR emergency_contact_name LIKE %s OR emergency_contact_surname LIKE %s OR emergency_contact_email LIKE %s OR invited_by LIKE %s OR incompatibilities LIKE %s)';
			$search_term   = '%' . $wpdb->esc_like( $search ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Add individual field filters
		if ( ! empty( $filters['filter_name'] ) ) {
			$where_clause .= ' AND name LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $filters['filter_name'] ) . '%';
		}
		if ( ! empty( $filters['filter_surnames'] ) ) {
			$where_clause .= ' AND surnames LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $filters['filter_surnames'] ) . '%';
		}
		if ( ! empty( $filters['filter_invited_by'] ) ) {
			$where_clause .= ' AND invited_by LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $filters['filter_invited_by'] ) . '%';
		}
		if ( ! empty( $filters['filter_incompatibilities'] ) ) {
			$where_clause .= ' AND incompatibilities LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $filters['filter_incompatibilities'] ) . '%';
		}
		if ( ! empty( $filters['filter_emergency_contact'] ) ) {
			$where_clause .= ' AND (emergency_contact_name LIKE %s OR emergency_contact_surname LIKE %s OR emergency_contact_email LIKE %s OR emergency_contact_phone LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $filters['filter_emergency_contact'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}
		if ( ! empty( $filters['filter_notes'] ) ) {
			$where_clause .= ' AND notes LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $filters['filter_notes'] ) . '%';
		}
		if ( ! empty( $filters['filter_internal_notes'] ) ) {
			$where_clause .= ' AND internal_notes LIKE %s';
			$where_values[] = '%' . $wpdb->esc_like( $filters['filter_internal_notes'] ) . '%';
		}

		$table_name = $this->database->get_attendants_table();
		$sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
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
		$table_name = $this->database->get_attendants_table();
		$sql = "SELECT retreat_id, COUNT(*) as count 
				FROM {$table_name} 
				WHERE retreat_id IN ($placeholders) 
				GROUP BY retreat_id"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $retreat_ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		$counts = array();
		foreach ( $results as $result ) {
			$counts[ $result->retreat_id ] = (int) $result->count;
		}

		return $counts;
	}

	/**
	 * Delete all attendants for a specific retreat with cascade delete for related messages.
	 * This method implements cascade delete functionality to replace database foreign key constraints.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id Retreat ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_by_retreat( $retreat_id ) {
		global $wpdb;

		// First get all attendant IDs for this retreat
		$table_name = $this->database->get_attendants_table();
		$attendant_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM {$table_name} WHERE retreat_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$retreat_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Delete all messages for these attendants (cascade delete)
		if ( ! empty( $attendant_ids ) ) {
			$message_model = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
			$message_model->delete_by_attendants( $attendant_ids );
		}

		// Then delete all attendants for this retreat
		$result = $wpdb->delete(
			$this->database->get_attendants_table(),
			array( 'retreat_id' => $retreat_id ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

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
		
		// Get retreat to check if notes are enabled
		$retreat_model = new DFX_Parish_Retreat_Letters_Retreat();
		$retreat = $retreat_model->get( $retreat_id );
		$notes_enabled = ! empty( $retreat->notes_enabled );

		$headers = array(
			__( 'Name', 'dfx-parish-retreat-letters' ),
			__( 'Surnames', 'dfx-parish-retreat-letters' ),
			__( 'Date of Birth', 'dfx-parish-retreat-letters' ),
			__( 'Emergency Contact Name', 'dfx-parish-retreat-letters' ),
			__( 'Emergency Contact Surname', 'dfx-parish-retreat-letters' ),
			__( 'Emergency Contact Phone', 'dfx-parish-retreat-letters' ),
			__( 'Emergency Contact Email', 'dfx-parish-retreat-letters' ),
			__( 'Emergency Contact Relationship', 'dfx-parish-retreat-letters' ),
			__( 'Invited By', 'dfx-parish-retreat-letters' ),
			__( 'Incompatibilities', 'dfx-parish-retreat-letters' ),
			__( 'Message URL', 'dfx-parish-retreat-letters' ),
		);
		
		if ( $notes_enabled ) {
			$headers[] = __( 'Notes', 'dfx-parish-retreat-letters' );
		}

		$rows = array();
		foreach ( $attendants as $attendant ) {
			// Generate message URL if token exists
			$message_url = ! empty( $attendant->message_url_token ) 
				? home_url( '/messages/' . $attendant->message_url_token )
				: '';

			$row = array(
				$attendant->name,
				$attendant->surnames,
				$attendant->date_of_birth,
				$attendant->emergency_contact_name,
				$attendant->emergency_contact_surname,
				$attendant->emergency_contact_phone,
				$attendant->emergency_contact_email,
				$attendant->emergency_contact_relationship ?? '',
				$attendant->invited_by ?? '',
				$attendant->incompatibilities ?? '',
				$message_url,
			);
			
			if ( $notes_enabled ) {
				$row[] = $attendant->notes ?? '';
			}
			
			$rows[] = $row;
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
			'retreat_id'                      => absint( $data['retreat_id'] ?? 0 ),
			'name'                            => sanitize_text_field( $data['name'] ?? '' ),
			'surnames'                        => sanitize_text_field( $data['surnames'] ?? '' ),
			'date_of_birth'                   => sanitize_text_field( $data['date_of_birth'] ?? '' ),
			'emergency_contact_name'          => sanitize_text_field( $data['emergency_contact_name'] ?? '' ),
			'emergency_contact_surname'       => sanitize_text_field( $data['emergency_contact_surname'] ?? '' ),
			'emergency_contact_phone'         => sanitize_text_field( $data['emergency_contact_phone'] ?? '' ),
			'emergency_contact_email'         => sanitize_email( $data['emergency_contact_email'] ?? '' ),
			'emergency_contact_relationship'  => sanitize_text_field( $data['emergency_contact_relationship'] ?? '' ),
			'invited_by'                      => sanitize_text_field( $data['invited_by'] ?? '' ),
			'incompatibilities'               => sanitize_textarea_field( $data['incompatibilities'] ?? '' ),
			'notes'                           => sanitize_textarea_field( $data['notes'] ?? '' ),
			'internal_notes'                  => sanitize_textarea_field( $data['internal_notes'] ?? '' ),
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
			 empty( $data['emergency_contact_phone'] ) ) {
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

		// Validate email format if provided (optional field)
		if ( ! empty( $data['emergency_contact_email'] ) && ! is_email( $data['emergency_contact_email'] ) ) {
			$admin_manager->add_admin_notice(
				__( 'Invalid emergency contact email format.', 'dfx-parish-retreat-letters' ),
				'error'
			);
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

		$table_name = $this->database->get_attendants_table();
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} 
			WHERE retreat_id = %d AND name = %s AND surnames = %s AND date_of_birth = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$retreat_id, $name, $surnames, $date_of_birth
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return (bool) $exists;
	}

	/**
	 * Get attendant ID by identity (name, surname, date of birth).
	 *
	 * @since 1.0.0
	 * @param int    $retreat_id      Retreat ID.
	 * @param string $name            Attendant name.
	 * @param string $surnames        Attendant surnames.
	 * @param string $date_of_birth   Date of birth.
	 * @return int|null The attendant ID or null if not found.
	 */
	public function get_id_by_identity( $retreat_id, $name, $surnames, $date_of_birth ) {
		global $wpdb;

		$table_name = $this->database->get_attendants_table();
		$attendant_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table_name} 
			WHERE retreat_id = %d AND name = %s AND surnames = %s AND date_of_birth = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$retreat_id, $name, $surnames, $date_of_birth
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $attendant_id ? (int) $attendant_id : null;
	}

	/**
	 * Update emergency contact information for an attendant.
	 *
	 * @since 1.0.0
	 * @param int   $id   Attendant ID.
	 * @param array $data Emergency contact data.
	 * @return bool True on success, false on failure.
	 */
	public function update_emergency_contact( $id, $data ) {
		global $wpdb;

		$sanitized_data = array(
			'emergency_contact_name'    => sanitize_text_field( $data['emergency_contact_name'] ?? '' ),
			'emergency_contact_surname' => sanitize_text_field( $data['emergency_contact_surname'] ?? '' ),
			'emergency_contact_phone'   => sanitize_text_field( $data['emergency_contact_phone'] ?? '' ),
			'emergency_contact_email'   => sanitize_email( $data['emergency_contact_email'] ?? '' ),
		);

		// Basic validation for emergency contact data
		if ( empty( $sanitized_data['emergency_contact_name'] ) || 
			 empty( $sanitized_data['emergency_contact_phone'] ) ) {
			return false;
		}

		// Basic phone number validation (allows various formats)
		if ( ! preg_match( '/^[\d\s\-\+\(\)\.]+$/', $sanitized_data['emergency_contact_phone'] ) ) {
			return false;
		}

		// Validate email format if provided (optional field)
		if ( ! empty( $sanitized_data['emergency_contact_email'] ) && ! is_email( $sanitized_data['emergency_contact_email'] ) ) {
			return false;
		}

		$result = $wpdb->update(
			$this->database->get_attendants_table(),
			$sanitized_data,
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result !== false;
	}
}