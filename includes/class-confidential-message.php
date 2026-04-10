<?php
/**
 * The confidential message model class
 *
 * Handles CRUD operations for confidential messages with encryption.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.2.0
 *
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */

/**
 * The confidential message model class.
 *
 * This class handles all CRUD operations for confidential messages
 * with built-in encryption and security features.
 *
 * @since      1.2.0
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 * @author     DaveFX
 */
class DFXPRL_ConfidentialMessage {

	/**
	 * The database instance.
	 *
	 * @since 1.2.0
	 * @var DFXPRL_Database
	 */
	private $database;

	/**
	 * The security instance.
	 *
	 * @since 1.2.0
	 * @var DFXPRL_Security
	 */
	private $security;

	/**
	 * Constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		$this->database = DFXPRL_Database::get_instance();
		$this->security = DFXPRL_Security::get_instance();
	}

	/**
	 * Create a new confidential message.
	 *
	 * @since 1.2.0
	 * @param array $data Message data.
	 * @return int|false The message ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$sanitized_data = $this->sanitize_message_data( $data );
		if ( ! $this->validate_message_data( $sanitized_data ) ) {
			return false;
		}

		// Encrypt the message content
		$encryption_result = $this->security->encrypt_data( $sanitized_data['content'] );
		if ( $encryption_result === false ) {
			return false;
		}

		$ip_address = $this->security->get_user_ip();

		$result = $wpdb->insert(
			$this->database->get_messages_table(),
			array(
				'attendant_id'      => $sanitized_data['attendant_id'],
				'sender_name'       => $sanitized_data['sender_name'],
				'encrypted_content' => $encryption_result['encrypted'],
				'content_salt'      => $encryption_result['salt'],
				'message_type'      => $sanitized_data['message_type'],
				'ip_address'        => $ip_address,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get a message by ID.
	 *
	 * @since 1.2.0
	 * @param int $id Message ID.
	 * @return object|null The message object or null if not found.
	 */
	public function get( $id ) {
		global $wpdb;

		$messages_table = $this->database->get_messages_table();
		$attendants_table = $this->database->get_attendants_table();

		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT m.*, a.retreat_id, a.name as attendant_name, a.surnames as attendant_surnames 
			 FROM `{$messages_table}` m
			 INNER JOIN `{$attendants_table}` a ON m.attendant_id = a.id
			 WHERE m.id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $result;
	}

	/**
	 * Get a message by ID with decrypted content.
	 * This should only be used for printing operations.
	 *
	 * @since 1.2.0
	 * @param int $id Message ID.
	 * @return object|null The message object with decrypted content or null if not found.
	 */
	public function get_with_decrypted_content( $id ) {
		$message = $this->get( $id );
		if ( ! $message ) {
			return null;
		}

		// Decrypt the content
		$decrypted_content = $this->security->decrypt_data( $message->encrypted_content, $message->content_salt );
		if ( $decrypted_content === false ) {
			return null;
		}

		$message->decrypted_content = $decrypted_content;
		return $message;
	}

	/**
	 * Get messages by attendant ID.
	 *
	 * @since 1.2.0
	 * @param int   $attendant_id Attendant ID.
	 * @param array $args         Query arguments.
	 * @return array Array of message objects.
	 */
	public function get_by_attendant( $attendant_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'orderby'      => 'submitted_at',
			'order'        => 'DESC',
			'per_page'     => 100,
			'page'         => 1,
			'search'       => '',
			'message_type' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clause = 'm.attendant_id = %d';
		$where_values = array( $attendant_id );

		// Filter by message type
		if ( ! empty( $args['message_type'] ) ) {
			$where_clause .= ' AND m.message_type = %s';
			$where_values[] = $args['message_type'];
		}

		// Add search functionality
		if ( ! empty( $args['search'] ) ) {
			$where_clause .= ' AND (a.name LIKE %s OR a.surnames LIKE %s OR m.sender_name LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Sanitize orderby and order
		$allowed_orderby = array( 'id', 'sender_name', 'message_type', 'submitted_at', 'attendant_name', 'print_count' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'submitted_at';
		$order = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

		// Map orderby to actual column names
		if ( $orderby === 'attendant_name' ) {
			$orderby = 'a.name';
		} elseif ( $orderby === 'print_count' ) {
			$orderby = 'print_count';
		} elseif ( $orderby !== 'id' ) {
			$orderby = 'm.' . $orderby;
		} else {
			$orderby = 'm.id';
		}

		// Calculate offset
		$offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

		$sql = 'SELECT m.*, a.name as attendant_name, a.surnames as attendant_surnames, 
				       r.name as retreat_name, r.id as retreat_id,
				       (SELECT COUNT(*) FROM ' . $this->database->get_message_files_table() . ' f WHERE f.message_id = m.id) as file_count,
				       (SELECT COUNT(*) FROM ' . $this->database->get_message_print_log_table() . ' p WHERE p.message_id = m.id) as print_count,
				       (SELECT MIN(printed_at) FROM ' . $this->database->get_message_print_log_table() . ' p WHERE p.message_id = m.id) as first_printed_at
				FROM ' . $this->database->get_messages_table() . ' m
				INNER JOIN ' . $this->database->get_attendants_table() . ' a ON m.attendant_id = a.id
				INNER JOIN ' . $this->database->get_retreats_table() . ' r ON a.retreat_id = r.id
				WHERE ' . $where_clause . ' 
				ORDER BY ' . $orderby . ' ' . $order . ' 
				LIMIT %d OFFSET %d';

		$where_values[] = absint( $args['per_page'] );
		$where_values[] = $offset;

		$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		return $results ? $results : array();
	}

	/**
	 * Get message count by attendant ID.
	 *
	 * @since 1.2.0
	 * @param int   $attendant_id Attendant ID.
	 * @param array $args         Query arguments.
	 * @return int Message count.
	 */
	public function get_count_by_attendant( $attendant_id, $args = array() ) {
		global $wpdb;

		$where_clause = 'm.attendant_id = %d';
		$where_values = array( $attendant_id );

		// Filter by message type
		if ( ! empty( $args['message_type'] ) ) {
			$where_clause .= ' AND m.message_type = %s';
			$where_values[] = $args['message_type'];
		}

		// Add search functionality
		if ( ! empty( $args['search'] ) ) {
			$where_clause .= ' AND (a.name LIKE %s OR a.surnames LIKE %s OR m.sender_name LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$sql = 'SELECT COUNT(*) 
				FROM ' . $this->database->get_messages_table() . ' m
				INNER JOIN ' . $this->database->get_attendants_table() . ' a ON m.attendant_id = a.id
				WHERE ' . $where_clause;

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$count = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		return (int) $count;
	}

	/**
	 * Get count of non-printed messages by attendant ID.
	 *
	 * @since 1.7.0
	 * @param int   $attendant_id Attendant ID.
	 * @param array $args         Query arguments.
	 * @return int Non-printed message count.
	 */
	public function get_non_printed_count_by_attendant( $attendant_id, $args = array() ) {
		global $wpdb;

		$where_clause = 'm.attendant_id = %d';
		$where_values = array( $attendant_id );

		// Filter by message type
		if ( ! empty( $args['message_type'] ) ) {
			$where_clause .= ' AND m.message_type = %s';
			$where_values[] = $args['message_type'];
		}

		// Add search functionality
		if ( ! empty( $args['search'] ) ) {
			$where_clause .= ' AND (a.name LIKE %s OR a.surnames LIKE %s OR m.sender_name LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$sql = 'SELECT COUNT(*) 
				FROM ' . $this->database->get_messages_table() . ' m
				INNER JOIN ' . $this->database->get_attendants_table() . ' a ON m.attendant_id = a.id
				LEFT JOIN ' . $this->database->get_message_print_log_table() . ' p ON m.id = p.message_id
				WHERE ' . $where_clause . ' AND p.id IS NULL';

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$count = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		return (int) $count;
	}

	/**
	 * Get message count by retreat ID.
	 *
	 * @since 1.8.0
	 * @param int $retreat_id Retreat ID.
	 * @return int Message count.
	 */
	public function get_count_by_retreat( $retreat_id ) {
		global $wpdb;

		$sql = 'SELECT COUNT(*) 
				FROM ' . $this->database->get_messages_table() . ' m
				INNER JOIN ' . $this->database->get_attendants_table() . ' a ON m.attendant_id = a.id
				WHERE a.retreat_id = %d';

		$sql = $wpdb->prepare( $sql, $retreat_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$count = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		return (int) $count;
	}

	/**
	 * Get count of non-printed messages by retreat ID.
	 *
	 * @since 1.8.0
	 * @param int $retreat_id Retreat ID.
	 * @return int Non-printed message count.
	 */
	public function get_non_printed_count_by_retreat( $retreat_id ) {
		global $wpdb;

		$sql = 'SELECT COUNT(*) 
				FROM ' . $this->database->get_messages_table() . ' m
				INNER JOIN ' . $this->database->get_attendants_table() . ' a ON m.attendant_id = a.id
				LEFT JOIN ' . $this->database->get_message_print_log_table() . ' p ON m.id = p.message_id
				WHERE a.retreat_id = %d AND p.id IS NULL';

		$sql = $wpdb->prepare( $sql, $retreat_id ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$count = $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		return (int) $count;
	}

	/**
	 * Get all messages with metadata for admin interface.
	 *
	 * @since 1.2.0
	 * @param array $args Query arguments.
	 * @return array Array of message objects with attendant info.
	 */
	public function get_all_with_metadata( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'search'      => '',
			'retreat_id'  => 0,
			'orderby'     => 'submitted_at',
			'order'       => 'DESC',
			'per_page'    => 20,
			'page'        => 1,
			'message_type' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clause = '1=1';
		$where_values = array();

		// Filter by retreat
		if ( $args['retreat_id'] ) {
			$where_clause .= ' AND a.retreat_id = %d';
			$where_values[] = $args['retreat_id'];
		}

		// Filter by message type
		if ( $args['message_type'] ) {
			$where_clause .= ' AND m.message_type = %s';
			$where_values[] = $args['message_type'];
		}

		// Add search functionality
		if ( ! empty( $args['search'] ) ) {
			$where_clause .= ' AND (a.name LIKE %s OR a.surnames LIKE %s OR m.sender_name LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Sanitize orderby and order
		$allowed_orderby = array( 'id', 'sender_name', 'message_type', 'submitted_at', 'attendant_name', 'print_count' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'submitted_at';
		$order = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

		// Map orderby to actual column names
		if ( $orderby === 'attendant_name' ) {
			$orderby = 'a.name';
		} elseif ( $orderby === 'print_count' ) {
			$orderby = 'print_count';
		} elseif ( $orderby !== 'id' ) {
			$orderby = 'm.' . $orderby;
		} else {
			$orderby = 'm.id';
		}

		// Calculate offset
		$offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

		$sql = 'SELECT m.*, a.name as attendant_name, a.surnames as attendant_surnames, 
				       r.name as retreat_name, r.id as retreat_id,
				       (SELECT COUNT(*) FROM ' . $this->database->get_message_files_table() . ' f WHERE f.message_id = m.id) as file_count,
				       (SELECT COUNT(*) FROM ' . $this->database->get_message_print_log_table() . ' p WHERE p.message_id = m.id) as print_count,
				       (SELECT MIN(printed_at) FROM ' . $this->database->get_message_print_log_table() . ' p WHERE p.message_id = m.id) as first_printed_at
				FROM ' . $this->database->get_messages_table() . ' m
				INNER JOIN ' . $this->database->get_attendants_table() . ' a ON m.attendant_id = a.id
				INNER JOIN ' . $this->database->get_retreats_table() . ' r ON a.retreat_id = r.id
				WHERE ' . $where_clause . ' 
				ORDER BY ' . $orderby . ' ' . $order . ' 
				LIMIT %d OFFSET %d';

		$where_values[] = absint( $args['per_page'] );
		$where_values[] = $offset;

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared

		return $results ? $results : array();
	}

	/**
	 * Get total count of messages for admin interface.
	 *
	 * @since 1.2.0
	 * @param array $args Query arguments.
	 * @return int Total count.
	 */
	public function get_total_count( $args = array() ) {
		global $wpdb;

		$where_clause = '1=1';
		$where_values = array();

		// Filter by retreat
		if ( ! empty( $args['retreat_id'] ) ) {
			$where_clause .= ' AND a.retreat_id = %d';
			$where_values[] = $args['retreat_id'];
		}

		// Filter by message type
		if ( ! empty( $args['message_type'] ) ) {
			$where_clause .= ' AND m.message_type = %s';
			$where_values[] = $args['message_type'];
		}

		// Add search functionality
		if ( ! empty( $args['search'] ) ) {
			$where_clause .= ' AND (a.name LIKE %s OR a.surnames LIKE %s OR m.sender_name LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$sql = 'SELECT COUNT(*) 
				FROM ' . $this->database->get_messages_table() . ' m
				INNER JOIN ' . $this->database->get_attendants_table() . ' a ON m.attendant_id = a.id
				WHERE ' . $where_clause;

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Delete a message and all associated files.
	 *
	 * @since 1.2.0
	 * @param int $id Message ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $id ) {
		global $wpdb;

		// First delete associated files from filesystem
		$file_model = new DFXPRL_MessageFile();
		$files = $file_model->get_by_message( $id );
		foreach ( $files as $file ) {
			$file_model->delete( $file->id );
		}

		// Delete the message (cascading will handle related records)
		$result = $wpdb->delete(
			$this->database->get_messages_table(),
			array( 'id' => $id ),
			array( '%d' )
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $result !== false;
	}

	/**
	 * Get statistics for dashboard.
	 *
	 * @since 1.2.0
	 * @return array Statistics array.
	 */
	public function get_statistics() {
		global $wpdb;

		$stats = array();
		$messages_table = $this->database->get_messages_table();

		// Total messages
		$stats['total_messages'] = (int) $wpdb->get_var( 
			"SELECT COUNT(*) FROM `{$messages_table}`" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		// Messages by type
		$stats['text_messages'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$messages_table}` WHERE message_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'text'
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$stats['file_messages'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$messages_table}` WHERE message_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'file'
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		// Printed vs unprinted
		$print_log_table = $this->database->get_message_print_log_table();
		$stats['printed_messages'] = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT message_id) FROM `{$print_log_table}`" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$stats['unprinted_messages'] = $stats['total_messages'] - $stats['printed_messages'];

		// Messages from last 30 days
		$stats['recent_messages'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM `{$messages_table}` 
			 WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $stats;
	}

	/**
	 * Sanitize message data.
	 *
	 * @since 1.2.0
	 * @param array $data Raw data.
	 * @return array Sanitized data.
	 */
	private function sanitize_message_data( $data ) {
		return array(
			'attendant_id'  => absint( $data['attendant_id'] ?? 0 ),
			'sender_name'   => sanitize_text_field( $data['sender_name'] ?? '' ),
			'content'       => wp_kses_post( $data['content'] ?? '' ),
			'message_type'  => sanitize_text_field( $data['message_type'] ?? 'text' ),
		);
	}

	/**
	 * Validate message data.
	 *
	 * @since 1.2.0
	 * @param array $data Sanitized data.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_message_data( $data ) {
		// Check required fields
		if ( empty( $data['attendant_id'] ) || empty( $data['content'] ) ) {
			return false;
		}

		// Validate message type
		if ( ! in_array( $data['message_type'], array( 'text', 'file' ), true ) ) {
			return false;
		}

		// Check if attendant exists
		global $wpdb;
		$attendants_table = $this->database->get_attendants_table();
		$attendant_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$attendants_table}` WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$data['attendant_id']
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $attendant_exists ) {
			return false;
		}

		return true;
	}

	/**
	 * Clean up old data for GDPR compliance.
	 *
	 * @since 1.2.0
	 * @param int $days_old Number of days after which to clean up data.
	 * @return int Number of messages cleaned up.
	 */
	public function cleanup_old_data( $days_old = 365 ) {
		global $wpdb;

		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );
		$messages_table = $this->database->get_messages_table();

		// Get messages to delete
		$messages_table = $this->database->get_messages_table();
		$message_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM `{$messages_table}` WHERE submitted_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$cutoff_date
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$deleted_count = 0;
		foreach ( $message_ids as $message_id ) {
			if ( $this->delete( $message_id ) ) {
				$deleted_count++;
			}
		}

		return $deleted_count;
	}

	/**
	 * Delete all messages for a specific attendant.
	 * This method implements cascade delete functionality to replace database foreign key constraints.
	 *
	 * @since 1.4.0
	 * @param int $attendant_id Attendant ID.
	 * @return int Number of messages deleted.
	 */
	public function delete_by_attendant( $attendant_id ) {
		global $wpdb;

		// Get all message IDs for this attendant
		$messages_table = $this->database->get_messages_table();
		$message_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT id FROM `{$messages_table}` WHERE attendant_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$attendant_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$deleted_count = 0;
		foreach ( $message_ids as $message_id ) {
			if ( $this->delete( $message_id ) ) {
				$deleted_count++;
			}
		}

		return $deleted_count;
	}

	/**
	 * Delete all messages for multiple attendants.
	 * This method implements cascade delete functionality to replace database foreign key constraints.
	 *
	 * @since 1.4.0
	 * @param array $attendant_ids Array of attendant IDs.
	 * @return int Number of messages deleted.
	 */
	public function delete_by_attendants( $attendant_ids ) {
		if ( empty( $attendant_ids ) || ! is_array( $attendant_ids ) ) {
			return 0;
		}

		$attendant_ids = array_map( 'absint', $attendant_ids );
		$attendant_ids = array_filter( $attendant_ids ); // Remove any zeros

		if ( empty( $attendant_ids ) ) {
			return 0;
		}

		global $wpdb;

		// Get all message IDs for these attendants
		$messages_table = $this->database->get_messages_table();
		$placeholders = implode( ',', array_fill( 0, count( $attendant_ids ), '%d' ) );
		$query = "SELECT id FROM `{$messages_table}` WHERE attendant_id IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$message_ids = $wpdb->get_col( $wpdb->prepare( $query, $attendant_ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		$deleted_count = 0;
		foreach ( $message_ids as $message_id ) {
			if ( $this->delete( $message_id ) ) {
				$deleted_count++;
			}
		}

		return $deleted_count;
	}
}