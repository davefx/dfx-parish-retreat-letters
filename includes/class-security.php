<?php
/**
 * The security utilities class
 *
 * Handles encryption, token generation, and security-related functionality.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.2.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The security utilities class.
 *
 * This class handles all security-related functionality including
 * AES-256 encryption, token generation, and IP anonymization.
 *
 * @since      1.2.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters_Security {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.2.0
	 * @var DFX_Parish_Retreat_Letters_Security|null
	 */
	private static $instance = null;

	/**
	 * The encryption method to use.
	 *
	 * @since 1.2.0
	 * @var string
	 */
	const ENCRYPTION_METHOD = 'AES-256-CBC';

	/**
	 * The length of the salt/IV in bytes.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	const SALT_LENGTH = 32;

	/**
	 * The length of generated tokens.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	const TOKEN_LENGTH = 64;

	/**
	 * Get the single instance of the class.
	 *
	 * @since 1.2.0
	 * @return DFX_Parish_Retreat_Letters_Security
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->get_encryption_key(true); // Ensure key is initialized
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.2.0
	 */
	private function __construct() {
		// Ensure required encryption functions are available
		if ( ! function_exists( 'openssl_encrypt' ) || ! function_exists( 'openssl_decrypt' ) ) {
			add_action( 'admin_notices', array( $this, 'missing_openssl_notice' ) );
		}
	}

	/**
	 * Display admin notice when OpenSSL is not available.
	 *
	 * @since 1.2.0
	 */
	public function missing_openssl_notice() {
		echo '<div class="notice notice-error"><p>';
		esc_html_e( 'DFX Parish Retreat Letters: OpenSSL extension is required for the confidential message system.', 'dfx-parish-retreat-letters' );
		echo '</p></div>';
	}

	/**
	 * Get the encryption key.
	 * 
	 * This key should be stored securely outside the database.
	 * In production, consider storing in wp-config.php or environment variable.
	 *
	 * @since 1.2.0
	 * @return string The encryption key.
	 */
	private function get_encryption_key($show_message = false) {
		// Check if key is defined in wp-config.php (recommended for production)
		if ( defined( 'DFX_PARISH_RETREAT_LETTERS_ENCRYPTION_KEY' ) ) {

			// Check if we also have it in options, and if so, remove it to avoid duplication
			if ( get_option( 'dfx_parish_retreat_letters_encryption_key' ) ) {
				delete_option( 'dfx_parish_retreat_letters_encryption_key' );
			}

			return DFX_PARISH_RETREAT_LETTERS_ENCRYPTION_KEY;
		}

		// Generate and store key in options as fallback
		$key = get_option( 'dfx_parish_retreat_letters_encryption_key' );
		if ( ! $key ) {
			$key = $this->generate_secure_key();
			// Store the key in options for easy access
			update_option( 'dfx_parish_retreat_letters_encryption_key', $key );
		}

		// Generate a warning message in the backend, so the admin knows the key is stored in the database
		// and with instructions to move it to wp-config.php
		if ( $show_message && is_admin() && ! defined( 'DFX_PARISH_RETREAT_LETTERS_ENCRYPTION_KEY' ) ) {
			add_action( 'admin_notices', function() use ( $key ) {
				echo '<div class="notice notice-warning"><p>';
				esc_html_e( 'DFX Parish Retreat Letters: The encryption key is stored in the database. For better security, please define DFX_PARISH_RETREAT_LETTERS_ENCRYPTION_KEY in wp-config.php.', 'dfx-parish-retreat-letters' );
				echo '<br>';
				esc_html_e( 'You can generate a secure key using the following code:', 'dfx-parish-retreat-letters' );
				echo '<br/><code>define(\'DFX_PARISH_RETREAT_LETTERS_ENCRYPTION_KEY\', \'' . esc_html( $key ) . '\');</code><br/>';
				esc_html_e( 'Place this line in your wp-config.php file to secure the key.', 'dfx-parish-retreat-letters' );
				echo '</p></div>';
			} );
		}

		return $key;
	}

	/**
	 * Generate a cryptographically secure key.
	 *
	 * @since 1.2.0
	 * @param int $length Key length in bytes.
	 * @return string Base64 encoded key.
	 */
	private function generate_secure_key( $length = 32 ) {
		if ( function_exists( 'random_bytes' ) ) {
			return base64_encode( random_bytes( $length ) );
		} elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return base64_encode( openssl_random_pseudo_bytes( $length ) );
		} else {
			// Fallback to wp_generate_password for older systems
			return base64_encode( wp_generate_password( $length, true, true ) );
		}
	}

	/**
	 * Generate a cryptographically secure token.
	 *
	 * @since 1.2.0
	 * @param int $length Token length.
	 * @return string Secure token.
	 */
	public function generate_secure_token( $length = self::TOKEN_LENGTH ) {
		return wp_generate_password( $length, false, false );
	}

	/**
	 * Generate a unique message URL token.
	 *
	 * @since 1.2.0
	 * @return string Unique token that doesn't exist in database.
	 */
	public function generate_unique_message_token() {
		global $wpdb;
		$database = DFX_Parish_Retreat_Letters_Database::get_instance();
		
		do {
			$token = $this->generate_secure_token();
			// Check if token already exists
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$database->get_attendants_table()} WHERE message_url_token = %s",
				$token
			) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} while ( $exists > 0 );

		return $token;
	}

	/**
	 * Generate a salt for encryption.
	 *
	 * @since 1.2.0
	 * @param int $length Salt length in bytes.
	 * @return string Base64 encoded salt.
	 */
	public function generate_salt( $length = self::SALT_LENGTH ) {
		if ( function_exists( 'random_bytes' ) ) {
			return base64_encode( random_bytes( $length ) );
		} elseif ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return base64_encode( openssl_random_pseudo_bytes( $length ) );
		} else {
			// Fallback
			return base64_encode( wp_generate_password( $length, true, true ) );
		}
	}

	/**
	 * Encrypt data using AES-256-CBC.
	 *
	 * @since 1.2.0
	 * @param string $data Data to encrypt.
	 * @param string $salt Optional salt. If not provided, one will be generated.
	 * @return array Array with 'encrypted' data and 'salt', or false on failure.
	 */
	public function encrypt_data( $data, $salt = '' ) {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return false;
		}

		if ( empty( $salt ) ) {
			$salt = $this->generate_salt();
		}

		$key = $this->get_encryption_key();
		$iv_length = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
		$iv = substr( base64_decode( $salt ), 0, $iv_length );

		// Pad IV if necessary
		if ( strlen( $iv ) < $iv_length ) {
			$iv = str_pad( $iv, $iv_length, "\0" );
		}

		$encrypted = openssl_encrypt( $data, self::ENCRYPTION_METHOD, base64_decode( $key ), 0, $iv );

		if ( $encrypted === false ) {
			return false;
		}

		return array(
			'encrypted' => $encrypted,
			'salt'      => $salt,
		);
	}

	/**
	 * Decrypt data using AES-256-CBC.
	 *
	 * @since 1.2.0
	 * @param string $encrypted_data Encrypted data.
	 * @param string $salt Salt used for encryption.
	 * @return string|false Decrypted data or false on failure.
	 */
	public function decrypt_data( $encrypted_data, $salt ) {
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return false;
		}

		$key = $this->get_encryption_key();
		$iv_length = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
		$iv = substr( base64_decode( $salt ), 0, $iv_length );

		// Pad IV if necessary
		if ( strlen( $iv ) < $iv_length ) {
			$iv = str_pad( $iv, $iv_length, "\0" );
		}

		$decrypted = openssl_decrypt( $encrypted_data, self::ENCRYPTION_METHOD, base64_decode( $key ), 0, $iv );

		return $decrypted;
	}

	/**
	 * Hash an IP address for anonymization.
	 *
	 * @since 1.2.0
	 * @param string $ip_address IP address to hash.
	 * @return string Hashed IP address.
	 */
	public function hash_ip_address( $ip_address ) {
		$salt = $this->get_encryption_key();
		return hash( 'sha256', $ip_address . $salt );
	}

	/**
	 * Get the user's IP address.
	 *
	 * @since 1.2.0
	 * @return string IP address.
	 */
	public function get_user_ip() {
		// Check for IP from shared internet
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		}
		// Check for IP passed from proxy
		elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		}
		// Check for remote IP address
		elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		} else {
			$ip = 'unknown';
		}

		// Validate IP address
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) === false ) {
			$ip = 'unknown';
		}

		return $ip;
	}

	/**
	 * Anonymize old IP addresses in the database for GDPR compliance.
	 *
	 * @since 1.2.0
	 * @param int $days_old Number of days after which to anonymize IPs. Default 30.
	 * @return int Number of records anonymized.
	 */
	public function anonymize_old_ip_addresses( $days_old = 30 ) {
		global $wpdb;
		$database = DFX_Parish_Retreat_Letters_Database::get_instance();

		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );

		// Anonymize IPs in messages table
		$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE {$database->get_messages_table()} 
			 SET ip_hash = %s, ip_address = NULL, ip_anonymized_at = NOW() 
			 WHERE submitted_at < %s AND ip_address IS NOT NULL AND ip_anonymized_at IS NULL",
			'anonymized',
			$cutoff_date
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $updated ? $updated : 0;
	}

	/**
	 * Verify that required security extensions are available.
	 *
	 * @since 1.2.0
	 * @return bool True if all required extensions are available.
	 */
	public function verify_security_requirements() {
		$required_functions = array(
			'openssl_encrypt',
			'openssl_decrypt',
			'openssl_cipher_iv_length',
			'hash',
		);

		foreach ( $required_functions as $function ) {
			if ( ! function_exists( $function ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Validate and sanitize file upload.
	 *
	 * @since 1.2.0
	 * @param array $file $_FILES array element.
	 * @return array|false Validated file info or false on failure.
	 */
	public function validate_file_upload( $file ) {
		// Check for upload errors
		if ( ! isset( $file['error'] ) || $file['error'] !== UPLOAD_ERR_OK ) {
			return false;
		}

		// Check file size (5MB limit)
		$max_size = 5 * 1024 * 1024; // 5MB
		if ( $file['size'] > $max_size ) {
			return false;
		}

		// Allowed file types and MIME types
		$allowed_types = array(
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'txt'  => 'text/plain',
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
		);

		// Get file extension
		$file_info = pathinfo( $file['name'] );
		$extension = strtolower( $file_info['extension'] ?? '' );

		// Check if extension is allowed
		if ( ! array_key_exists( $extension, $allowed_types ) ) {
			return false;
		}

		// Verify MIME type
		$finfo = finfo_open( FILEINFO_MIME_TYPE );
		$mime_type = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );
		
		// Log detected MIME type for debugging (only in wp-config debug mode)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 
				'DFX File Upload Debug: File "%s" (extension: %s) detected MIME type: %s, expected: %s',
				$file['name'],
				$extension,
				$mime_type,
				$allowed_types[ $extension ]
			) );
		}

		if ( $mime_type !== $allowed_types[ $extension ] ) {
			// Allow common MIME type variations
			$allowed_variations = array(
				// Text files
				'text/x-Algol68' => array( 'text/plain' ),
				
				// DOC files - multiple possible MIME types
				'application/octet-stream' => array( 
					'application/pdf', 
					'application/msword',
					'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
				),
				'application/doc' => array( 'application/msword' ),
				'application/vnd.ms-office' => array( 
					'application/msword',
					'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
				),
				
				// DOCX files - can be reported as ZIP since DOCX is a ZIP-based format
				'application/zip' => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ),
				
				// Sometimes PDF files are reported as octet-stream
				'application/x-pdf' => array( 'application/pdf' ),
			);

			$is_valid = false;
			if ( isset( $allowed_variations[ $mime_type ] ) ) {
				$valid_types = is_array( $allowed_variations[ $mime_type ] ) 
					? $allowed_variations[ $mime_type ] 
					: array( $allowed_variations[ $mime_type ] );
				
				if ( in_array( $allowed_types[ $extension ], $valid_types, true ) ) {
					$is_valid = true;
				}
			}

			if ( ! $is_valid ) {
				return false;
			}
		}

		return array(
			'name'      => sanitize_file_name( $file['name'] ),
			'tmp_name'  => $file['tmp_name'],
			'size'      => $file['size'],
			'type'      => $mime_type,
			'extension' => $extension,
		);
	}

	/**
	 * Generate a secure filename for storing encrypted files.
	 *
	 * @since 1.2.0
	 * @param string $original_filename Original filename.
	 * @return string Secure filename.
	 */
	public function generate_secure_filename( $original_filename ) {
		$extension = pathinfo( $original_filename, PATHINFO_EXTENSION );
		$secure_name = $this->generate_secure_token( 32 );
		return $secure_name . '.' . $extension . '.enc';
	}

	/**
	 * Rate limiting check for IP address.
	 *
	 * @since 1.2.0
	 * @param string $ip_address IP address to check.
	 * @param int    $max_attempts Maximum attempts allowed.
	 * @param int    $time_window Time window in minutes.
	 * @return bool True if within rate limit, false if exceeded.
	 */
	public function check_rate_limit( $ip_address, $max_attempts = 5, $time_window = 60 ) {
		$transient_key = 'dfx_prl_message_rate_limit_' . md5( $ip_address );
		$attempts = get_transient( $transient_key );

		if ( $attempts === false ) {
			// No previous attempts recorded
			set_transient( $transient_key, 1, $time_window * 60 );
			return true;
		}

		if ( $attempts >= $max_attempts ) {
			return false;
		}

		// Increment attempts
		set_transient( $transient_key, $attempts + 1, $time_window * 60 );
		return true;
	}

	/**
	 * Log a rate limit violation.
	 *
	 * @since 1.2.0
	 * @param string $ip_address IP address that exceeded rate limit.
	 * @param string $action Action that was rate limited.
	 */
	public function log_rate_limit_violation( $ip_address, $action = 'message_submission' ) {
		$log_entry = array(
			'timestamp'  => current_time( 'mysql' ),
			'ip_address' => $ip_address,
			'action'     => $action,
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
		);

		// Store in transient for admin review
		$violations = get_transient( 'dfx_prl_message_rate_limit_violations' ) ?: array();
		$violations[] = $log_entry;
		
		// Keep only last 100 violations
		if ( count( $violations ) > 100 ) {
			$violations = array_slice( $violations, -100 );
		}
		
		set_transient( 'dfx_prl_message_rate_limit_violations', $violations, 24 * 60 * 60 ); // 24 hours
	}
}