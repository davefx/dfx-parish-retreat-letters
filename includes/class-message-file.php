<?php
/**
 * The message file model class
 *
 * Handles CRUD operations for encrypted message file attachments.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.2.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The message file model class.
 *
 * This class handles all CRUD operations for encrypted message file attachments.
 *
 * @since      1.2.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters_MessageFile {

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
	 * The upload directory for encrypted files.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	private $upload_dir;

	/**
	 * Constructor.
	 *
	 * @since 1.2.0
	 */
	public function __construct() {
		$this->database = DFX_Parish_Retreat_Letters_Database::get_instance();
		$this->security = DFX_Parish_Retreat_Letters_Security::get_instance();
		$this->init_upload_directory();
	}

	/**
	 * Initialize the upload directory for encrypted files.
	 *
	 * @since 1.2.0
	 */
	private function init_upload_directory() {
		$wp_upload_dir = wp_upload_dir();
		$this->upload_dir = $wp_upload_dir['basedir'] . '/dfx-prl-confidential-files';

		// Create directory if it doesn't exist
		if ( ! file_exists( $this->upload_dir ) ) {
			wp_mkdir_p( $this->upload_dir );
		}

		// Create .htaccess to deny direct access
		$htaccess_file = $this->upload_dir . '/.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess_content = "Order Deny,Allow\nDeny from all\n";
			file_put_contents( $htaccess_file, $htaccess_content );
		}

		// Create index.php to prevent directory listing
		$index_file = $this->upload_dir . '/index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, '<?php // Silence is golden' );
		}
	}

	/**
	 * Create a new message file record and store encrypted file.
	 *
	 * @since 1.2.0
	 * @param array $data File data.
	 * @return int|false The file ID on success, false on failure.
	 */
	public function create( $data ) {
		global $wpdb;

		$sanitized_data = $this->sanitize_file_data( $data );
		if ( ! $this->validate_file_data( $sanitized_data ) ) {
			return false;
		}

		// Read and encrypt file content
		$file_content = file_get_contents( $sanitized_data['tmp_name'] );
		if ( $file_content === false ) {
			return false;
		}

		$encryption_result = $this->security->encrypt_data( $file_content );
		if ( $encryption_result === false ) {
			return false;
		}

		// Generate secure filename
		$encrypted_filename = $this->security->generate_secure_filename( $sanitized_data['original_filename'] );
		$file_path = $this->upload_dir . '/' . $encrypted_filename;

		// Store encrypted file
		if ( file_put_contents( $file_path, $encryption_result['encrypted'] ) === false ) {
			return false;
		}

		// Create database record
		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->database->get_message_files_table(),
			array(
				'message_id'         => $sanitized_data['message_id'],
				'original_filename'  => $sanitized_data['original_filename'],
				'encrypted_filename' => $encrypted_filename,
				'file_type'          => $sanitized_data['file_type'],
				'file_size'          => $sanitized_data['file_size'],
				'encrypted_file_path' => $file_path,
				'file_salt'          => $encryption_result['salt'],
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( $result ) {
			return $wpdb->insert_id;
		} else {
			// Clean up file if database insert failed
			wp_delete_file( $file_path );
			return false;
		}
	}

	/**
	 * Get a file by ID.
	 *
	 * @since 1.2.0
	 * @param int $id File ID.
	 * @return object|null The file object or null if not found.
	 */
	public function get( $id ) {
		global $wpdb;

		$table_name = $this->database->get_message_files_table();
		$result = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT * FROM `{$table_name}` WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$id
		) );

		return $result;
	}

	/**
	 * Get files by message ID.
	 *
	 * @since 1.2.0
	 * @param int $message_id Message ID.
	 * @return array Array of file objects.
	 */
	public function get_by_message( $message_id ) {
		global $wpdb;

		$table_name = $this->database->get_message_files_table();
		$results = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT * FROM `{$table_name}` WHERE message_id = %d ORDER BY uploaded_at ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$message_id
		) );

		return $results ? $results : array();
	}

	/**
	 * Get decrypted file content for printing/download.
	 * This should only be used for authorized operations.
	 *
	 * @since 1.2.0
	 * @param int $id File ID.
	 * @return array|false Array with file info and content, or false on failure.
	 */
	public function get_decrypted_file( $id ) {
		$file = $this->get( $id );
		if ( ! $file ) {
			return false;
		}

		// Check if file exists
		if ( ! file_exists( $file->encrypted_file_path ) ) {
			return false;
		}

		// Read encrypted content
		$encrypted_content = file_get_contents( $file->encrypted_file_path );
		if ( $encrypted_content === false ) {
			return false;
		}

		// Decrypt content
		$decrypted_content = $this->security->decrypt_data( $encrypted_content, $file->file_salt );
		if ( $decrypted_content === false ) {
			return false;
		}

		return array(
			'filename' => $file->original_filename,
			'content'  => $decrypted_content,
			'type'     => $file->file_type,
			'size'     => $file->file_size,
		);
	}

	/**
	 * Delete a file and its encrypted data.
	 *
	 * @since 1.2.0
	 * @param int $id File ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $id ) {
		global $wpdb;

		$file = $this->get( $id );
		if ( ! $file ) {
			return false;
		}

		// Delete file from filesystem
		if ( file_exists( $file->encrypted_file_path ) ) {
			wp_delete_file( $file->encrypted_file_path );
		}

		// Delete database record
		$result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->database->get_message_files_table(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return $result !== false;
	}

	/**
	 * Serve a file for download (with authorization check).
	 *
	 * @since 1.2.0
	 * @param int $file_id File ID.
	 * @param int $user_id User ID requesting the file.
	 * @return bool True if file was served, false otherwise.
	 */
	public function serve_file( $file_id, $user_id ) {
		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$file_data = $this->get_decrypted_file( $file_id );
		if ( ! $file_data ) {
			return false;
		}

		// Log the file access for audit purposes
		$this->log_file_access( $file_id, $user_id );

		// Set headers for file download
		$this->set_download_headers( $file_data['filename'], $file_data['type'], $file_data['size'] );

		// Output file content
		echo $file_data['content']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Set headers for file download.
	 *
	 * @since 1.2.0
	 * @param string $filename Original filename.
	 * @param string $type     MIME type.
	 * @param int    $size     File size.
	 */
	private function set_download_headers( $filename, $type, $size ) {
		// Prevent caching
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Set content type
		header( 'Content-Type: ' . $type );
		header( 'Content-Length: ' . $size );

		// Set filename
		header( 'Content-Disposition: attachment; filename="' . addslashes( $filename ) . '"' );

		// Security headers
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: DENY' );
	}

	/**
	 * Log file access for audit purposes.
	 *
	 * @since 1.2.0
	 * @param int $file_id File ID.
	 * @param int $user_id User ID.
	 */
	private function log_file_access( $file_id, $user_id ) {
		$access_log = get_option( 'dfx_prl_file_access_log', array() );
		
		$access_log[] = array(
			'file_id'    => $file_id,
			'user_id'    => $user_id,
			'timestamp'  => current_time( 'mysql' ),
			'ip_address' => $this->security->get_user_ip(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'unknown',
		);

		// Keep only last 1000 access logs
		if ( count( $access_log ) > 1000 ) {
			$access_log = array_slice( $access_log, -1000 );
		}

		update_option( 'dfx_prl_file_access_log', $access_log );
	}

	/**
	 * Get file statistics.
	 *
	 * @since 1.2.0
	 * @return array Statistics array.
	 */
	public function get_statistics() {
		global $wpdb;

		$stats = array();
		$table_name = $this->database->get_message_files_table();

		// Total files
		$stats['total_files'] = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT COUNT(*) FROM `{$table_name}`" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		// Files by type
		$file_types = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT file_type, COUNT(*) as count FROM `{$table_name}` GROUP BY file_type" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$stats['by_type'] = array();
		foreach ( $file_types as $type_data ) {
			$stats['by_type'][ $type_data->file_type ] = (int) $type_data->count;
		}

		// Total storage used
		$stats['total_size'] = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT SUM(file_size) FROM `{$table_name}`" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		return $stats;
	}

	/**
	 * Clean up orphaned files (files without corresponding database records).
	 *
	 * @since 1.2.0
	 * @return int Number of files cleaned up.
	 */
	public function cleanup_orphaned_files() {
		global $wpdb;

		// Get all files in upload directory
		$files_in_directory = glob( $this->upload_dir . '/*.enc' );
		$cleaned_count = 0;

		foreach ( $files_in_directory as $file_path ) {
			$filename = basename( $file_path );
			$table_name = $this->database->get_message_files_table();
			
			// Check if file exists in database
			$exists = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT COUNT(*) FROM `{$table_name}` WHERE encrypted_filename = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$filename
			) );

			if ( ! $exists ) {
				// File not in database, remove it
				if ( wp_delete_file( $file_path ) ) {
					$cleaned_count++;
				}
			}
		}

		return $cleaned_count;
	}

	/**
	 * Sanitize file data.
	 *
	 * @since 1.2.0
	 * @param array $data Raw data.
	 * @return array Sanitized data.
	 */
	private function sanitize_file_data( $data ) {
		return array(
			'message_id'        => absint( $data['message_id'] ?? 0 ),
			'original_filename' => sanitize_file_name( $data['original_filename'] ?? '' ),
			'file_type'         => sanitize_text_field( $data['file_type'] ?? '' ),
			'file_size'         => absint( $data['file_size'] ?? 0 ),
			'tmp_name'          => sanitize_text_field( $data['tmp_name'] ?? '' ),
		);
	}

	/**
	 * Validate file data.
	 *
	 * @since 1.2.0
	 * @param array $data Sanitized data.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_file_data( $data ) {
		// Check required fields
		if ( empty( $data['message_id'] ) || empty( $data['original_filename'] ) || 
			 empty( $data['file_type'] ) || empty( $data['tmp_name'] ) ) {
			return false;
		}

		// Check if message exists
		global $wpdb;
		$messages_table = $this->database->get_messages_table();
		$message_exists = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT COUNT(*) FROM `{$messages_table}` WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$data['message_id']
		) );

		if ( ! $message_exists ) {
			return false;
		}

		// Check if temporary file exists
		if ( ! file_exists( $data['tmp_name'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the upload directory path.
	 *
	 * @since 1.2.0
	 * @return string Upload directory path.
	 */
	public function get_upload_dir() {
		return $this->upload_dir;
	}

	/**
	 * Check if upload directory is properly secured.
	 *
	 * @since 1.2.0
	 * @return bool True if properly secured, false otherwise.
	 */
	public function is_upload_dir_secured() {
		$htaccess_file = $this->upload_dir . '/.htaccess';
		$index_file = $this->upload_dir . '/index.php';

		return file_exists( $htaccess_file ) && file_exists( $index_file );
	}
}