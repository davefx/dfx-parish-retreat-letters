<?php
/**
 * The GDPR compliance class
 *
 * Handles GDPR and privacy law compliance features.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.2.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The GDPR compliance class.
 *
 * This class handles all GDPR and privacy law compliance features
 * including data retention, right to erasure, and audit logging.
 *
 * @since      1.2.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters_GDPR {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.2.0
	 * @var DFX_Parish_Retreat_Letters_GDPR|null
	 */
	private static $instance = null;

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
	 * Get the single instance of the class.
	 *
	 * @since 1.2.0
	 * @return DFX_Parish_Retreat_Letters_GDPR
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
	 * @since 1.2.0
	 */
	private function __construct() {
		$this->database = DFX_Parish_Retreat_Letters_Database::get_instance();
		$this->security = DFX_Parish_Retreat_Letters_Security::get_instance();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.2.0
	 */
	private function init_hooks() {
		// Schedule daily cleanup if not already scheduled
		if ( ! wp_next_scheduled( 'dfx_prl_daily_gdpr_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'dfx_prl_daily_gdpr_cleanup' );
		}

		add_action( 'dfx_prl_daily_gdpr_cleanup', array( $this, 'run_daily_cleanup' ) );
		add_action( 'admin_init', array( $this, 'maybe_anonymize_ips' ) );
		
		// Only add AJAX hooks in admin
		if ( is_admin() ) {
			add_action( 'wp_ajax_dfx_prl_export_personal_data', array( $this, 'ajax_export_personal_data' ) );
			add_action( 'wp_ajax_dfx_prl_erase_personal_data', array( $this, 'ajax_erase_personal_data' ) );
		}
	}

	/**
	 * Run daily GDPR compliance cleanup.
	 *
	 * @since 1.2.0
	 */
	public function run_daily_cleanup() {
		// Anonymize IP addresses older than 30 days
		$anonymized_count = $this->security->anonymize_old_ip_addresses( 30 );

		// Clean up old audit logs (keep for 2 years)
		$this->cleanup_old_audit_logs( 730 );

		// Clean up old messages based on retention policy
		$retention_days = get_option( 'dfx_prl_message_retention_days', 365 );
		if ( $retention_days > 0 ) {
			$this->cleanup_old_messages( $retention_days );
		}

		// Log the cleanup operation
		$this->log_audit_event( 'gdpr_cleanup', array(
			'anonymized_ips' => $anonymized_count,
			'retention_days' => $retention_days,
		) );
	}

	/**
	 * Check and anonymize IP addresses if needed.
	 *
	 * @since 1.2.0
	 */
	public function maybe_anonymize_ips() {
		// Only run once per day
		$last_run = get_option( 'dfx_prl_last_ip_anonymization', 0 );
		if ( time() - $last_run < DAY_IN_SECONDS ) {
			return;
		}

		$anonymized_count = $this->security->anonymize_old_ip_addresses( 30 );
		update_option( 'dfx_prl_last_ip_anonymization', time() );

		if ( $anonymized_count > 0 ) {
			$this->log_audit_event( 'ip_anonymization', array(
				'count' => $anonymized_count,
			) );
		}
	}

	/**
	 * Export personal data for a given email or name.
	 *
	 * @since 1.2.0
	 * @param string $identifier Email or name to search for.
	 * @return array Export data.
	 */
	public function export_personal_data( $identifier ) {
		global $wpdb;

		$export_data = array(
			'messages' => array(),
			'files' => array(),
			'print_logs' => array(),
		);

		// Find messages by sender name or email
		$messages_table = $this->database->get_messages_table();
		$attendants_table = $this->database->get_attendants_table();
		$retreats_table = $this->database->get_retreats_table();
		$messages = $wpdb->get_results( $wpdb->prepare(
			"SELECT m.*, a.name as attendant_name, a.surnames as attendant_surnames, r.name as retreat_name
			 FROM `{$messages_table}` m
			 INNER JOIN `{$attendants_table}` a ON m.attendant_id = a.id
			 INNER JOIN `{$retreats_table}` r ON a.retreat_id = r.id
			 WHERE m.sender_name LIKE %s",
			'%' . $wpdb->esc_like( $identifier ) . '%'
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		foreach ( $messages as $message ) {
			$export_data['messages'][] = array(
				'message_id' => $message->id,
				'attendant' => $message->attendant_name . ' ' . $message->attendant_surnames,
				'retreat' => $message->retreat_name,
				'sender_name' => $message->sender_name,
				'submitted_at' => $message->submitted_at,
				'note' => __( 'Message content is encrypted and cannot be exported in plain text for security reasons.', 'dfx-parish-retreat-letters' ),
			);

			// Get files for this message
			$message_files_table = $this->database->get_message_files_table();
			$files = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM `{$message_files_table}` WHERE message_id = %d",
				$message->id
			) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			foreach ( $files as $file ) {
				$export_data['files'][] = array(
					'message_id' => $message->id,
					'original_filename' => $file->original_filename,
					'file_type' => $file->file_type,
					'file_size' => $file->file_size,
					'uploaded_at' => $file->uploaded_at,
					'note' => __( 'File content is encrypted and cannot be exported for security reasons.', 'dfx-parish-retreat-letters' ),
				);
			}
		}

		// Log the export request
		$this->log_audit_event( 'personal_data_export', array(
			'identifier' => $identifier,
			'messages_found' => count( $export_data['messages'] ),
			'files_found' => count( $export_data['files'] ),
		) );

		return $export_data;
	}

	/**
	 * Erase personal data for a given identifier.
	 *
	 * @since 1.2.0
	 * @param string $identifier Email or name to search for.
	 * @return array Erasure results.
	 */
	public function erase_personal_data( $identifier ) {
		global $wpdb;

		$erasure_results = array(
			'messages_erased' => 0,
			'files_erased' => 0,
			'items_retained' => array(),
		);

		// Find messages by sender name
		$messages_table = $this->database->get_messages_table();
		$messages = $wpdb->get_results( $wpdb->prepare(
			"SELECT m.id FROM `{$messages_table}` m
			 WHERE m.sender_name LIKE %s",
			'%' . $wpdb->esc_like( $identifier ) . '%'
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Erase each message and its associated data
		$message_model = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
		
		foreach ( $messages as $message ) {
			// Get file count before deletion
			$message_files_table = $this->database->get_message_files_table();
			$file_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM `{$message_files_table}` WHERE message_id = %d",
				$message->id
			) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// Delete the message (this will cascade to files and print logs)
			if ( $message_model->delete( $message->id ) ) {
				$erasure_results['messages_erased']++;
				$erasure_results['files_erased'] += $file_count;
			} else {
				$erasure_results['items_retained'][] = sprintf(
					/* translators: %d: message ID number */
					__( 'Message ID %d could not be deleted due to system error', 'dfx-parish-retreat-letters' ),
					$message->id
				);
			}
		}

		// Log the erasure request
		$this->log_audit_event( 'personal_data_erasure', array(
			'identifier' => $identifier,
			'messages_erased' => $erasure_results['messages_erased'],
			'files_erased' => $erasure_results['files_erased'],
			'items_retained' => count( $erasure_results['items_retained'] ),
		) );

		return $erasure_results;
	}

	/**
	 * Clean up old messages based on retention policy.
	 *
	 * @since 1.2.0
	 * @param int $retention_days Number of days to retain messages.
	 * @return int Number of messages deleted.
	 */
	public function cleanup_old_messages( $retention_days ) {
		$message_model = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
		$deleted_count = $message_model->cleanup_old_data( $retention_days );

		if ( $deleted_count > 0 ) {
			$this->log_audit_event( 'message_retention_cleanup', array(
				'retention_days' => $retention_days,
				'messages_deleted' => $deleted_count,
			) );
		}

		return $deleted_count;
	}

	/**
	 * Clean up old audit logs.
	 *
	 * @since 1.2.0
	 * @param int $retention_days Number of days to retain audit logs.
	 * @return int Number of logs deleted.
	 */
	public function cleanup_old_audit_logs( $retention_days ) {
		$logs = get_option( 'dfx_prl_audit_logs', array() );
		$cutoff_time = time() - ( $retention_days * DAY_IN_SECONDS );
		$original_count = count( $logs );

		// Filter out old logs
		$logs = array_filter( $logs, function( $log ) use ( $cutoff_time ) {
			return isset( $log['timestamp'] ) && $log['timestamp'] > $cutoff_time;
		} );

		update_option( 'dfx_prl_audit_logs', $logs );

		return $original_count - count( $logs );
	}

	/**
	 * Log an audit event.
	 *
	 * @since 1.2.0
	 * @param string $event_type Type of event.
	 * @param array  $data       Event data.
	 */
	public function log_audit_event( $event_type, $data = array() ) {
		$logs = get_option( 'dfx_prl_audit_logs', array() );

		$log_entry = array(
			'timestamp' => time(),
			'event_type' => $event_type,
			'user_id' => get_current_user_id(),
			'ip_address' => $this->security->get_user_ip(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'unknown',
			'data' => $data,
		);

		$logs[] = $log_entry;

		// Keep only last 10000 logs to prevent database bloat
		if ( count( $logs ) > 10000 ) {
			$logs = array_slice( $logs, -10000 );
		}

		update_option( 'dfx_prl_audit_logs', $logs );
	}

	/**
	 * Get audit logs with filtering.
	 *
	 * @since 1.2.0
	 * @param array $args Filter arguments.
	 * @return array Filtered audit logs.
	 */
	public function get_audit_logs( $args = array() ) {
		$defaults = array(
			'event_type' => '',
			'user_id' => 0,
			'limit' => 100,
			'offset' => 0,
			'date_from' => '',
			'date_to' => '',
		);

		$args = wp_parse_args( $args, $defaults );
		$logs = get_option( 'dfx_prl_audit_logs', array() );

		// Apply filters
		if ( $args['event_type'] ) {
			$logs = array_filter( $logs, function( $log ) use ( $args ) {
				return $log['event_type'] === $args['event_type'];
			} );
		}

		if ( $args['user_id'] ) {
			$logs = array_filter( $logs, function( $log ) use ( $args ) {
				return $log['user_id'] === $args['user_id'];
			} );
		}

		if ( $args['date_from'] ) {
			$from_timestamp = strtotime( $args['date_from'] );
			$logs = array_filter( $logs, function( $log ) use ( $from_timestamp ) {
				return $log['timestamp'] >= $from_timestamp;
			} );
		}

		if ( $args['date_to'] ) {
			$to_timestamp = strtotime( $args['date_to'] . ' 23:59:59' );
			$logs = array_filter( $logs, function( $log ) use ( $to_timestamp ) {
				return $log['timestamp'] <= $to_timestamp;
			} );
		}

		// Sort by timestamp descending
		usort( $logs, function( $a, $b ) {
			return $b['timestamp'] - $a['timestamp'];
		} );

		// Apply limit and offset
		if ( $args['limit'] > 0 ) {
			$logs = array_slice( $logs, $args['offset'], $args['limit'] );
		}

		return $logs;
	}

	/**
	 * AJAX handler for exporting personal data.
	 *
	 * @since 1.2.0
	 */
	public function ajax_export_personal_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'dfx-parish-retreat-letters' ) );
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_gdpr_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$identifier = sanitize_text_field( wp_unslash( $_POST['identifier'] ?? '' ) );
		if ( empty( $identifier ) ) {
			wp_send_json_error( array( 'message' => __( 'Please provide an identifier (name or email).', 'dfx-parish-retreat-letters' ) ) );
		}

		$export_data = $this->export_personal_data( $identifier );

		// Generate export file
		$filename = 'personal-data-export-' . sanitize_file_name( $identifier ) . '-' . gmdate( 'Y-m-d-H-i-s' ) . '.json';
		$export_json = wp_json_encode( $export_data, JSON_PRETTY_PRINT );

		// Set headers for download
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $export_json ) );

		echo $export_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * AJAX handler for erasing personal data.
	 *
	 * @since 1.2.0
	 */
	public function ajax_erase_personal_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'dfx-parish-retreat-letters' ) );
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_gdpr_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$identifier = sanitize_text_field( wp_unslash( $_POST['identifier'] ?? '' ) );
		$confirm = sanitize_text_field( wp_unslash( $_POST['confirm'] ?? '' ) );

		if ( empty( $identifier ) ) {
			wp_send_json_error( array( 'message' => __( 'Please provide an identifier (name or email).', 'dfx-parish-retreat-letters' ) ) );
		}

		if ( $confirm !== 'ERASE' ) {
			wp_send_json_error( array( 'message' => __( 'Please type "ERASE" to confirm data deletion.', 'dfx-parish-retreat-letters' ) ) );
		}

		$erasure_results = $this->erase_personal_data( $identifier );

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %1$d: number of messages erased, %2$d: number of files erased */
				__( 'Data erasure completed. Erased %1$d messages and %2$d files.', 'dfx-parish-retreat-letters' ),
				$erasure_results['messages_erased'],
				$erasure_results['files_erased']
			),
			'results' => $erasure_results,
		) );
	}

	/**
	 * Get data retention settings.
	 *
	 * @since 1.2.0
	 * @return array Retention settings.
	 */
	public function get_retention_settings() {
		return array(
			'message_retention_days' => get_option( 'dfx_prl_message_retention_days', 365 ),
			'ip_anonymization_days' => 30, // Fixed at 30 days for GDPR compliance
			'audit_log_retention_days' => get_option( 'dfx_prl_audit_log_retention_days', 730 ),
		);
	}

	/**
	 * Update data retention settings.
	 *
	 * @since 1.2.0
	 * @param array $settings New retention settings.
	 * @return bool True on success, false on failure.
	 */
	public function update_retention_settings( $settings ) {
		$updated = true;

		if ( isset( $settings['message_retention_days'] ) ) {
			$days = absint( $settings['message_retention_days'] );
			if ( $days >= 30 && $days <= 3650 ) { // Between 30 days and 10 years
				update_option( 'dfx_prl_message_retention_days', $days );
			} else {
				$updated = false;
			}
		}

		if ( isset( $settings['audit_log_retention_days'] ) ) {
			$days = absint( $settings['audit_log_retention_days'] );
			if ( $days >= 365 && $days <= 3650 ) { // Between 1 year and 10 years
				update_option( 'dfx_prl_audit_log_retention_days', $days );
			} else {
				$updated = false;
			}
		}

		if ( $updated ) {
			$this->log_audit_event( 'retention_settings_updated', $settings );
		}

		return $updated;
	}

	/**
	 * Get privacy compliance status.
	 *
	 * @since 1.2.0
	 * @return array Compliance status information.
	 */
	public function get_privacy_compliance_status() {
		$status = array(
			'encryption_enabled' => $this->security->verify_security_requirements(),
			'ip_anonymization_active' => true, // Always active
			'retention_policy_configured' => get_option( 'dfx_prl_message_retention_days', false ) !== false,
			'audit_logging_active' => true, // Always active
			'last_cleanup' => get_option( 'dfx_prl_last_gdpr_cleanup', 0 ),
			'messages_count' => $this->get_total_messages_count(),
			'files_count' => $this->get_total_files_count(),
			'audit_logs_count' => count( get_option( 'dfx_prl_audit_logs', array() ) ),
		);

		return $status;
	}

	/**
	 * Get total messages count.
	 *
	 * @since 1.2.0
	 * @return int Total messages count.
	 */
	private function get_total_messages_count() {
		global $wpdb;
		$messages_table = $this->database->get_messages_table();
		return (int) $wpdb->get_var( 
			"SELECT COUNT(*) FROM `{$messages_table}`"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get total files count.
	 *
	 * @since 1.2.0
	 * @return int Total files count.
	 */
	private function get_total_files_count() {
		global $wpdb;
		$message_files_table = $this->database->get_message_files_table();
		return (int) $wpdb->get_var( 
			"SELECT COUNT(*) FROM `{$message_files_table}`"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}