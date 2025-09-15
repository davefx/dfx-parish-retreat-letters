<?php
/**
 * The message print log model class
 *
 * Handles tracking of message print operations for audit purposes.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.2.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The message print log model class.
 *
 * This class handles all operations related to tracking when messages
 * are printed by administrators for audit and compliance purposes.
 *
 * @since      1.2.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters_PrintLog {

	/**
	 * The database instance.
	 *
	 * @since 1.2.0
	 * @var DFX_Parish_Retreat_Letters_Database
	 */
	private $database;

	/**
	 * The security instance.
	 *
	 * @since 1.2.0
	 * @var DFX_Parish_Retreat_Letters_Security
	 */
	private $security;

	/**
	 * Constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		$this->database = DFX_Parish_Retreat_Letters_Database::get_instance();
		$this->security = DFX_Parish_Retreat_Letters_Security::get_instance();
	}

	/**
	 * Log a message print operation.
	 *
	 * @since 1.2.0
	 * @param int $message_id Message ID that was printed.
	 * @param int $user_id    User ID who printed the message.
	 * @return int|false The log ID on success, false on failure.
	 */
	public function log_print( $message_id, $user_id ) {
		global $wpdb;

		$sanitized_data = $this->sanitize_print_data( array(
			'message_id' => $message_id,
			'user_id'    => $user_id,
		) );

		if ( ! $this->validate_print_data( $sanitized_data ) ) {
			return false;
		}

		$ip_address = $this->security->get_user_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'unknown';

		$result = $wpdb->insert(
			$this->database->get_message_print_log_table(),
			array(
				'message_id'  => $sanitized_data['message_id'],
				'user_id'     => $sanitized_data['user_id'],
				'ip_address'  => $ip_address,
				'user_agent'  => $user_agent,
			),
			array( '%d', '%d', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get print log by ID.
	 *
	 * @since 1.2.0
	 * @param int $id Log ID.
	 * @return object|null The log object or null if not found.
	 */
	public function get( $id ) {
		global $wpdb;

		$table_name = $this->database->get_message_print_log_table();
		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table_name} WHERE id = %d",
			$id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result;
	}

	/**
	 * Get print logs for a specific message.
	 *
	 * @since 1.2.0
	 * @param int   $message_id Message ID.
	 * @param array $args       Query arguments.
	 * @return array Array of print log objects with user info.
	 */
	public function get_by_message( $message_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'orderby'  => 'printed_at',
			'order'    => 'DESC',
			'per_page' => 20,
			'page'     => 1,
		);

		$args = wp_parse_args( $args, $defaults );

		// Sanitize orderby and order
		$allowed_orderby = array( 'id', 'printed_at', 'user_id' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'printed_at';
		$order = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

		// Calculate offset
		$offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

		$print_log_table = $this->database->get_message_print_log_table();
		$sql = "SELECT pl.*, u.display_name, u.user_login
				FROM {$print_log_table} pl
				LEFT JOIN {$wpdb->users} u ON pl.user_id = u.ID
				WHERE pl.message_id = %d 
				ORDER BY pl.{$orderby} {$order} 
				LIMIT %d OFFSET %d";

		$sql = $wpdb->prepare( $sql, $message_id, absint( $args['per_page'] ), $offset ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		return $results ? $results : array();
	}

	/**
	 * Get print count for a specific message.
	 *
	 * @since 1.2.0
	 * @param int $message_id Message ID.
	 * @return int Print count.
	 */
	public function get_print_count( $message_id ) {
		global $wpdb;

		$table_name = $this->database->get_message_print_log_table();
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table_name} WHERE message_id = %d",
			$message_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return (int) $count;
	}

	/**
	 * Get first print date for a specific message.
	 *
	 * @since 1.2.0
	 * @param int $message_id Message ID.
	 * @return string|null First print date or null if never printed.
	 */
	public function get_first_print_date( $message_id ) {
		global $wpdb;

		$table_name = $this->database->get_message_print_log_table();
		$date = $wpdb->get_var( $wpdb->prepare(
			"SELECT MIN(printed_at) FROM {$table_name} WHERE message_id = %d",
			$message_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $date;
	}

	/**
	 * Get last print date for a specific message.
	 *
	 * @since 1.2.0
	 * @param int $message_id Message ID.
	 * @return string|null Last print date or null if never printed.
	 */
	public function get_last_print_date( $message_id ) {
		global $wpdb;

		$table_name = $this->database->get_message_print_log_table();
		$date = $wpdb->get_var( $wpdb->prepare(
			"SELECT MAX(printed_at) FROM {$table_name} WHERE message_id = %d",
			$message_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $date;
	}

	/**
	 * Check if a message has been printed.
	 *
	 * @since 1.2.0
	 * @param int $message_id Message ID.
	 * @return bool True if printed, false otherwise.
	 */
	public function is_message_printed( $message_id ) {
		return $this->get_print_count( $message_id ) > 0;
	}

	/**
	 * Get print statistics for multiple messages.
	 *
	 * @since 1.2.0
	 * @param array $message_ids Array of message IDs.
	 * @return array Associative array with message_id as key and print data as value.
	 */
	public function get_print_stats_for_messages( $message_ids ) {
		global $wpdb;

		if ( empty( $message_ids ) ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $message_ids ), '%d' ) );
		$table_name = $this->database->get_message_print_log_table();
		$sql = "SELECT message_id, 
				       COUNT(*) as print_count,
				       MIN(printed_at) as first_printed,
				       MAX(printed_at) as last_printed
				FROM {$table_name} 
				WHERE message_id IN ($placeholders) 
				GROUP BY message_id";

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $message_ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		$stats = array();
		foreach ( $results as $result ) {
			$stats[ $result->message_id ] = array(
				'print_count'    => (int) $result->print_count,
				'first_printed'  => $result->first_printed,
				'last_printed'   => $result->last_printed,
				'is_printed'     => true,
			);
		}

		// Add entries for messages that haven't been printed
		foreach ( $message_ids as $message_id ) {
			if ( ! isset( $stats[ $message_id ] ) ) {
				$stats[ $message_id ] = array(
					'print_count'    => 0,
					'first_printed'  => null,
					'last_printed'   => null,
					'is_printed'     => false,
				);
			}
		}

		return $stats;
	}

	/**
	 * Get all print logs with filtering and pagination.
	 *
	 * @since 1.2.0
	 * @param array $args Query arguments.
	 * @return array Array of print log objects with message and user info.
	 */
	public function get_all_with_metadata( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'search'      => '',
			'retreat_id'  => 0,
			'user_id'     => 0,
			'orderby'     => 'printed_at',
			'order'       => 'DESC',
			'per_page'    => 20,
			'page'        => 1,
			'date_from'   => '',
			'date_to'     => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clause = '1=1';
		$where_values = array();

		// Filter by retreat
		if ( $args['retreat_id'] ) {
			$where_clause .= ' AND a.retreat_id = %d';
			$where_values[] = $args['retreat_id'];
		}

		// Filter by user
		if ( $args['user_id'] ) {
			$where_clause .= ' AND pl.user_id = %d';
			$where_values[] = $args['user_id'];
		}

		// Filter by date range
		if ( $args['date_from'] ) {
			$where_clause .= ' AND pl.printed_at >= %s';
			$where_values[] = $args['date_from'] . ' 00:00:00';
		}

		if ( $args['date_to'] ) {
			$where_clause .= ' AND pl.printed_at <= %s';
			$where_values[] = $args['date_to'] . ' 23:59:59';
		}

		// Add search functionality
		if ( ! empty( $args['search'] ) ) {
			$where_clause .= ' AND (a.name LIKE %s OR a.surnames LIKE %s OR u.display_name LIKE %s OR m.sender_name LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Sanitize orderby and order
		$allowed_orderby = array( 'id', 'printed_at', 'message_id', 'user_id', 'attendant_name' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'printed_at';
		$order = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

		// Map orderby to actual column names
		if ( $orderby === 'attendant_name' ) {
			$orderby = 'a.name';
		} elseif ( ! in_array( $orderby, array( 'id', 'message_id', 'user_id' ), true ) ) {
			$orderby = 'pl.' . $orderby;
		} else {
			$orderby = 'pl.' . $orderby;
		}

		// Calculate offset
		$offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );

		$print_log_table = $this->database->get_message_print_log_table();
		$messages_table = $this->database->get_messages_table();
		$attendants_table = $this->database->get_attendants_table();
		$retreats_table = $this->database->get_retreats_table();

		$sql = "SELECT pl.*, 
				       m.sender_name, m.message_type, m.submitted_at,
				       a.name as attendant_name, a.surnames as attendant_surnames,
				       r.name as retreat_name,
				       u.display_name as user_display_name, u.user_login
				FROM {$print_log_table} pl
				INNER JOIN {$messages_table} m ON pl.message_id = m.id
				INNER JOIN {$attendants_table} a ON m.attendant_id = a.id
				INNER JOIN {$retreats_table} r ON a.retreat_id = r.id
				LEFT JOIN {$wpdb->users} u ON pl.user_id = u.ID
				WHERE {$where_clause} 
				ORDER BY {$orderby} {$order} 
				LIMIT %d OFFSET %d";

		$where_values[] = absint( $args['per_page'] );
		$where_values[] = $offset;

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		return $results ? $results : array();
	}

	/**
	 * Get total count of print logs for admin interface.
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

		// Filter by user
		if ( ! empty( $args['user_id'] ) ) {
			$where_clause .= ' AND pl.user_id = %d';
			$where_values[] = $args['user_id'];
		}

		// Filter by date range
		if ( ! empty( $args['date_from'] ) ) {
			$where_clause .= ' AND pl.printed_at >= %s';
			$where_values[] = $args['date_from'] . ' 00:00:00';
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where_clause .= ' AND pl.printed_at <= %s';
			$where_values[] = $args['date_to'] . ' 23:59:59';
		}

		// Add search functionality
		if ( ! empty( $args['search'] ) ) {
			$where_clause .= ' AND (a.name LIKE %s OR a.surnames LIKE %s OR u.display_name LIKE %s OR m.sender_name LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$print_log_table = $this->database->get_message_print_log_table();
		$messages_table = $this->database->get_messages_table();
		$attendants_table = $this->database->get_attendants_table();

		$sql = "SELECT COUNT(*) 
				FROM {$print_log_table} pl
				INNER JOIN {$messages_table} m ON pl.message_id = m.id
				INNER JOIN {$attendants_table} a ON m.attendant_id = a.id
				LEFT JOIN {$wpdb->users} u ON pl.user_id = u.ID
				WHERE {$where_clause}";

		if ( ! empty( $where_values ) ) {
			$sql = $wpdb->prepare( $sql, $where_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get print statistics.
	 *
	 * @since 1.2.0
	 * @return array Statistics array.
	 */
	public function get_statistics() {
		global $wpdb;

		$stats = array();
		$table_name = $this->database->get_message_print_log_table();

		// Total print operations
		$stats['total_prints'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name}"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Unique messages printed
		$stats['unique_messages_printed'] = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT message_id) FROM {$table_name}"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Prints by user
		$user_prints = $wpdb->get_results(
			"SELECT u.display_name, COUNT(*) as print_count
			 FROM {$table_name} pl
			 LEFT JOIN {$wpdb->users} u ON pl.user_id = u.ID
			 GROUP BY pl.user_id
			 ORDER BY print_count DESC
			 LIMIT 10"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$stats['by_user'] = array();
		foreach ( $user_prints as $user_print ) {
			$stats['by_user'][] = array(
				'user_name'   => $user_print->display_name ?: __( 'Unknown User', 'dfx-parish-retreat-letters' ),
				'print_count' => (int) $user_print->print_count,
			);
		}

		// Recent print activity (last 30 days)
		$stats['recent_prints'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} 
			 WHERE printed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $stats;
	}

	/**
	 * Delete old print logs for cleanup.
	 *
	 * @since 1.2.0
	 * @param int $days_old Number of days after which to delete logs.
	 * @return int Number of logs deleted.
	 */
	public function cleanup_old_logs( $days_old = 730 ) { // 2 years default
		global $wpdb;

		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );
		$table_name = $this->database->get_message_print_log_table();

		$deleted = $wpdb->query( $wpdb->prepare(
			"DELETE FROM {$table_name} WHERE printed_at < %s",
			$cutoff_date
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $deleted ? $deleted : 0;
	}

	/**
	 * Sanitize print data.
	 *
	 * @since 1.2.0
	 * @param array $data Raw data.
	 * @return array Sanitized data.
	 */
	private function sanitize_print_data( $data ) {
		return array(
			'message_id' => absint( $data['message_id'] ?? 0 ),
			'user_id'    => absint( $data['user_id'] ?? 0 ),
		);
	}

	/**
	 * Validate print data.
	 *
	 * @since 1.2.0
	 * @param array $data Sanitized data.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_print_data( $data ) {
		// Check required fields
		if ( empty( $data['message_id'] ) || empty( $data['user_id'] ) ) {
			return false;
		}

		// Check if message exists
		global $wpdb;
		$messages_table = $this->database->get_messages_table();
		$message_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$messages_table} WHERE id = %d",
			$data['message_id']
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! $message_exists ) {
			return false;
		}

		// Check if user exists
		$user_exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->users} WHERE ID = %d",
			$data['user_id']
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! $user_exists ) {
			return false;
		}

		return true;
	}
}