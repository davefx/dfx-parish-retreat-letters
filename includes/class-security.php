<?php
/**
 * The security utilities class
 *
 * Handles encryption, token generation, and security-related functionality.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.2.0
 *
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */

/**
 * The security utilities class.
 *
 * This class handles all security-related functionality including
 * AES-256 encryption, token generation, and IP anonymization.
 *
 * @since      1.2.0
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 * @author     DaveFX
 */
class DFXPRL_Security {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.2.0
	 * @var DFXPRL_Security|null
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
	 * @return DFXPRL_Security
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
		// Support both new and old constant names for backward compatibility
		$constant_key = null;
		$constant_name = null;
		
		if ( defined( 'DFXPRL_ENCRYPTION_KEY' ) ) {
			$constant_key = DFXPRL_ENCRYPTION_KEY;
			$constant_name = 'DFXPRL_ENCRYPTION_KEY';
		} elseif ( defined( 'DFX_PARISH_RETREAT_LETTERS_ENCRYPTION_KEY' ) ) {
			// Backward compatibility: support old constant name
			$constant_key = DFX_PARISH_RETREAT_LETTERS_ENCRYPTION_KEY;
			$constant_name = 'DFX_PARISH_RETREAT_LETTERS_ENCRYPTION_KEY';
		}
		
		if ( $constant_key !== null ) {
			// Check if we also have it in options, and if so, remove it to avoid duplication
			$defined_in_option = get_option( 'dfxprl_encryption_key' );
			if ( $defined_in_option ) {
				if ( $defined_in_option !== $constant_key ) {
					// If the option value does not match the defined constant, show a critical error
					// message in the backend with an option to remove the database key
					add_action( 'admin_notices', array( $this, 'display_encryption_key_mismatch_notice' ) );
				} else {
					// If they match, we can safely delete the option
					delete_option( 'dfxprl_encryption_key' );
				}
			}

			return $constant_key;
		}

		// Generate and store key in options as fallback
		$key = get_option( 'dfxprl_encryption_key' );
		if ( ! $key ) {
			$key = $this->generate_secure_key();
			// Store the key in options for easy access
			update_option( 'dfxprl_encryption_key', $key );
		}

		// Generate a warning message in the backend, so the admin knows the key is stored in the database
		// and with instructions to move it to wp-config.php
		if ( $show_message && is_admin() && ! defined( 'DFXPRL_ENCRYPTION_KEY' ) && ! defined( 'DFX_PARISH_RETREAT_LETTERS_ENCRYPTION_KEY' ) ) {
			add_action( 'admin_notices', function() use ( $key ) {
				echo '<div class="notice notice-warning"><p>';
				esc_html_e( 'DFX Parish Retreat Letters: The encryption key is stored in the database. For better security, please define DFXPRL_ENCRYPTION_KEY in wp-config.php.', 'dfx-parish-retreat-letters' );
				echo '<br>';
				esc_html_e( 'You can generate a secure key using the following code:', 'dfx-parish-retreat-letters' );
				echo '<br/><code>define(\'DFXPRL_ENCRYPTION_KEY\', \'' . esc_html( $key ) . '\');</code><br/>';
				esc_html_e( 'Place this line in your wp-config.php file to secure the key.', 'dfx-parish-retreat-letters' );
				echo '</p></div>';
			} );
		}

		return $key;
	}

	/**
	 * Display admin notice for encryption key mismatch with option to remove database key.
	 *
	 * @since 25.11.28
	 */
	public function display_encryption_key_mismatch_notice() {
		// Only show to users who can manage the plugin
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$nonce = wp_create_nonce( 'dfx_prl_remove_db_encryption_key' );
		
		// Enqueue jQuery and add inline script for the button handler
		wp_enqueue_script( 'jquery' );
		$this->enqueue_encryption_key_mismatch_script( $nonce );
		?>
		<div class="notice notice-error" id="dfxprl-encryption-key-mismatch-notice">
			<p>
				<strong><?php esc_html_e( 'DFX Parish Retreat Letters: Encryption Key Mismatch', 'dfx-parish-retreat-letters' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'The encryption key defined in wp-config.php does not match the one stored in the database.', 'dfx-parish-retreat-letters' ); ?>
			</p>
			<p>
				<strong><?php esc_html_e( 'Warning:', 'dfx-parish-retreat-letters' ); ?></strong>
				<?php esc_html_e( 'Only the key defined in wp-config.php will be used. If any messages were already encrypted using the database key, they will become unreadable.', 'dfx-parish-retreat-letters' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'You can either:', 'dfx-parish-retreat-letters' ); ?>
			</p>
			<ul style="list-style: disc; margin-left: 20px;">
				<li><?php esc_html_e( 'Update the key in wp-config.php to match the database key, or', 'dfx-parish-retreat-letters' ); ?></li>
				<li><?php esc_html_e( 'Remove the key from the database to use the wp-config.php key (click the button below)', 'dfx-parish-retreat-letters' ); ?></li>
			</ul>
			<p>
				<button type="button" id="dfxprl-remove-db-key-btn" class="button button-primary" data-nonce="<?php echo esc_attr( $nonce ); ?>">
					<?php esc_html_e( 'Remove Database Key and Use wp-config.php Key', 'dfx-parish-retreat-letters' ); ?>
				</button>
				<span id="dfxprl-remove-db-key-status" style="margin-left: 10px;"></span>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue inline script for encryption key mismatch notice.
	 *
	 * @since 25.12.10
	 * @param string $nonce The nonce for the AJAX request.
	 */
	public function enqueue_encryption_key_mismatch_script( $nonce ) {
		$confirm_message = esc_js( __( 'Are you sure you want to remove the encryption key from the database? Any messages encrypted with the old key will become unreadable. This action cannot be undone.', 'dfx-parish-retreat-letters' ) );
		$removing_text = esc_js( __( 'Removing...', 'dfx-parish-retreat-letters' ) );
		$error_text = esc_js( __( 'An error occurred. Please try again.', 'dfx-parish-retreat-letters' ) );
		
		$script = sprintf(
			'jQuery(document).ready(function($) {
	$("#dfxprl-remove-db-key-btn").on("click", function() {
		var $button = $(this);
		var $status = $("#dfxprl-remove-db-key-status");
		var nonce = $button.data("nonce");

		if (!confirm("%1$s")) {
			return;
		}

		$button.prop("disabled", true);
		$status.text("%2$s");

		$.ajax({
			url: ajaxurl,
			type: "POST",
			data: {
				action: "dfxprl_remove_db_encryption_key",
				nonce: nonce
			},
			success: function(response) {
				if (response.success) {
					$status.html(\'<span style="color: green;">\' + response.data.message + \'</span>\');
					$("#dfxprl-encryption-key-mismatch-notice").fadeOut(2000, function() {
						$(this).remove();
					});
				} else {
					$status.html(\'<span style="color: red;">\' + response.data.message + \'</span>\');
					$button.prop("disabled", false);
				}
			},
			error: function() {
				$status.html(\'<span style="color: red;">%3$s</span>\');
				$button.prop("disabled", false);
			}
		});
	});
});',
			$confirm_message,
			$removing_text,
			$error_text
		);
		
		wp_add_inline_script( 'jquery', $script );
	}

	/**
	 * Remove the encryption key from the database.
	 *
	 * This method should only be called when the key in wp-config.php should take precedence
	 * over the database key, typically when there's a mismatch.
	 *
	 * @since 25.11.28
	 * @return bool True if the key was successfully removed, false otherwise.
	 */
	public function remove_encryption_key_from_database() {
		return delete_option( 'dfxprl_encryption_key' );
	}

	/**
	 * Check if there is an encryption key stored in the database.
	 *
	 * @since 25.11.28
	 * @return bool True if a non-empty key exists in the database, false otherwise.
	 */
	public function has_database_encryption_key() {
		$key = get_option( 'dfxprl_encryption_key' );
		return ! empty( $key );
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
		$database = DFXPRL_Database::get_instance();

		do {
			$token = $this->generate_secure_token();
			// Check if token already exists
			$attendants_table = $database->get_attendants_table();
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$attendants_table} WHERE message_url_token = %s",
				$token
			) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
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
		$database = DFXPRL_Database::get_instance();

		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );

		// Anonymize IPs in messages table
		$messages_table = $database->get_messages_table();
		$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE {$messages_table} 
			 SET ip_hash = %s, ip_address = NULL, ip_anonymized_at = NOW() 
			 WHERE submitted_at < %s AND ip_address IS NOT NULL AND ip_anonymized_at IS NULL",
			'anonymized',
			$cutoff_date
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

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
	 * Parse size string (e.g., "2M", "8M") to bytes.
	 *
	 * @since 1.2.0
	 * @param string $size Size string from PHP configuration.
	 * @return int Size in bytes.
	 */
	private function parse_size( $size ) {
		$unit = preg_replace( '/[^bkmgtpezy]/i', '', $size );
		$size = preg_replace( '/[^0-9\.]/', '', $size );
		
		if ( $unit ) {
			return (int) round( $size * pow( 1024, stripos( 'bkmgtpezy', $unit[0] ) ) );
		}
		
		return (int) round( $size );
	}

	/**
	 * Default fallback upload size in bytes (8 MB).
	 * Used internally for file validation when server configuration cannot be determined.
	 *
	 * @since 25.11.28
	 * @var int
	 */
	const DEFAULT_MAX_UPLOAD_SIZE = 8388608;

	/**
	 * Get the maximum upload file size from server configuration for display purposes.
	 *
	 * Returns null when the server configuration cannot be reliably determined,
	 * so the UI can choose not to display potentially incorrect information.
	 *
	 * @since 1.2.0
	 * @param bool $formatted Whether to return formatted string or bytes.
	 * @return string|int|null Formatted string (e.g., "2 MB"), bytes, or null if unknown.
	 */
	public function get_max_upload_size( $formatted = true ) {
		$max_bytes = $this->get_max_upload_size_internal();

		// Return null if we couldn't determine the size reliably
		if ( $max_bytes <= 0 ) {
			return null;
		}

		if ( $formatted ) {
			return $this->format_bytes( $max_bytes );
		}

		return $max_bytes;
	}

	/**
	 * Get the maximum upload file size for internal validation purposes.
	 *
	 * This method always returns a usable value (falls back to 8 MB default)
	 * to ensure file uploads are not incorrectly blocked when server is misconfigured.
	 *
	 * @since 25.11.28
	 * @return int Size in bytes (always > 0).
	 */
	public function get_max_upload_size_for_validation() {
		$max_bytes = $this->get_max_upload_size_internal();

		// If the server reports 0 or empty, use WordPress's wp_max_upload_size() as fallback
		if ( $max_bytes <= 0 ) {
			if ( function_exists( 'wp_max_upload_size' ) ) {
				$max_bytes = wp_max_upload_size();
			}

			// If still 0 or invalid, use a reasonable default (8 MB)
			if ( $max_bytes <= 0 ) {
				$max_bytes = self::DEFAULT_MAX_UPLOAD_SIZE;
			}
		}

		return $max_bytes;
	}

	/**
	 * Get the maximum upload file size from server configuration (internal helper).
	 *
	 * @since 25.11.28
	 * @return int Size in bytes (may be 0 if server is misconfigured).
	 */
	private function get_max_upload_size_internal() {
		$upload_max_filesize = ini_get( 'upload_max_filesize' );
		$post_max_size = ini_get( 'post_max_size' );

		$upload_bytes = $this->parse_size( $upload_max_filesize );
		$post_bytes = $this->parse_size( $post_max_size );

		// The effective limit is the minimum of the two (only if both are > 0)
		if ( $upload_bytes > 0 && $post_bytes > 0 ) {
			return min( $upload_bytes, $post_bytes );
		} elseif ( $upload_bytes > 0 ) {
			return $upload_bytes;
		} elseif ( $post_bytes > 0 ) {
			return $post_bytes;
		}

		return 0;
	}

	/**
	 * Get the maximum combined upload size (post_max_size) for display purposes.
	 *
	 * Returns null when the server configuration cannot be reliably determined,
	 * so the UI can choose not to display potentially incorrect information.
	 *
	 * @since 1.2.0
	 * @param bool $formatted Whether to return formatted string or bytes.
	 * @return string|int|null Formatted string (e.g., "8 MB"), bytes, or null if unknown.
	 */
	public function get_max_combined_upload_size( $formatted = true ) {
		$post_max_size = ini_get( 'post_max_size' );
		$post_bytes = $this->parse_size( $post_max_size );

		// Return null if we couldn't determine the size reliably
		if ( $post_bytes <= 0 ) {
			return null;
		}

		if ( $formatted ) {
			return $this->format_bytes( $post_bytes );
		}

		return $post_bytes;
	}

	/**
	 * Format bytes to human-readable string.
	 *
	 * @since 1.2.0
	 * @param int $bytes Number of bytes.
	 * @return string Formatted string (e.g., "2 MB").
	 */
	private function format_bytes( $bytes ) {
		if ( $bytes >= 1073741824 ) {
			return number_format( $bytes / 1073741824, 2 ) . ' GB';
		} elseif ( $bytes >= 1048576 ) {
			return number_format( $bytes / 1048576, 2 ) . ' MB';
		} elseif ( $bytes >= 1024 ) {
			return number_format( $bytes / 1024, 2 ) . ' KB';
		} else {
			return $bytes . ' bytes';
		}
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

		// Check file size against server limit (uses fallback when server is misconfigured)
		$max_size = $this->get_max_upload_size_for_validation();
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
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log -- Conditional debug logging only when WP_DEBUG is enabled
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
				/* translators: 1: file name, 2: file extension, 3: detected MIME type, 4: expected MIME type */
				trigger_error( sprintf(
					'DFX File Upload Error: File "%s" (extension: %s) detected MIME type: %s, expected: %s',
					$file['name'],
					$extension,
					$mime_type,
					$allowed_types[ $extension ]
				), E_USER_WARNING );

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
	 * Check if IP address is within rate limit (without incrementing).
	 *
	 * @since 1.2.0
	 * @param string $ip_address IP address to check.
	 * @param int    $max_attempts Maximum attempts allowed.
	 * @param int    $time_window Time window in minutes.
	 * @return bool True if within rate limit, false if exceeded.
	 */
	public function is_within_rate_limit( $ip_address, $max_attempts = 5, $time_window = 60 ) {
		$transient_key = 'dfxprl_message_rate_limit_' . md5( $ip_address );
		$attempts = get_transient( $transient_key );

		if ( $attempts === false ) {
			// No previous attempts recorded
			return true;
		}

		return $attempts < $max_attempts;
	}

	/**
	 * Increment rate limit counter for IP address.
	 *
	 * @since 1.2.0
	 * @param string $ip_address IP address to increment.
	 * @param int    $time_window Time window in minutes.
	 * @return int New attempt count.
	 */
	public function increment_rate_limit( $ip_address, $time_window = 60 ) {
		$transient_key = 'dfxprl_message_rate_limit_' . md5( $ip_address );
		$attempts = get_transient( $transient_key );

		if ( $attempts === false ) {
			// No previous attempts recorded
			set_transient( $transient_key, 1, $time_window * 60 );
			return 1;
		}

		// Increment attempts
		$new_attempts = $attempts + 1;
		set_transient( $transient_key, $new_attempts, $time_window * 60 );
		return $new_attempts;
	}

	/**
	 * Rate limiting check for IP address (legacy method for backwards compatibility).
	 * This method both checks and increments - use is_within_rate_limit() and increment_rate_limit() instead.
	 *
	 * @since 1.2.0
	 * @param string $ip_address IP address to check.
	 * @param int    $max_attempts Maximum attempts allowed.
	 * @param int    $time_window Time window in minutes.
	 * @return bool True if within rate limit, false if exceeded.
	 */
	public function check_rate_limit( $ip_address, $max_attempts = 5, $time_window = 60 ) {
		if ( ! $this->is_within_rate_limit( $ip_address, $max_attempts, $time_window ) ) {
			return false;
		}

		$this->increment_rate_limit( $ip_address, $time_window );
		return true;
	}

	/**
	 * Reset rate limit for a specific IP address.
	 *
	 * @since 1.2.0
	 * @param string $ip_address IP address to reset.
	 * @return bool True on success, false on failure.
	 */
	public function reset_rate_limit( $ip_address ) {
		$transient_key = 'dfxprl_message_rate_limit_' . md5( $ip_address );
		return delete_transient( $transient_key );
	}

	/**
	 * Reset all rate limits.
	 *
	 * @since 1.2.0
	 * @return int Number of rate limits reset.
	 */
	public function reset_all_rate_limits() {
		global $wpdb;

		// Delete all rate limit transients
		$count = $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_dfxprl_message_rate_limit_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// Also delete timeout transients
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_dfxprl_message_rate_limit_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		
		return $count ? $count : 0;
	}

	/**
	 * Get current rate limit status for an IP address.
	 *
	 * @since 1.2.0
	 * @param string $ip_address IP address to check.
	 * @return array Array with 'attempts' and 'time_remaining' keys.
	 */
	public function get_rate_limit_status( $ip_address ) {
		$transient_key = 'dfxprl_message_rate_limit_' . md5( $ip_address );
		$attempts = get_transient( $transient_key );

		if ( $attempts === false ) {
			return array(
				'attempts' => 0,
				'time_remaining' => 0
			);
		}

		// Get the timeout for this transient
		$timeout_key = '_transient_timeout_' . $transient_key;
		$timeout = get_option( $timeout_key );
		$time_remaining = $timeout ? max( 0, $timeout - time() ) : 0;

		return array(
			'attempts' => $attempts,
			'time_remaining' => $time_remaining
		);
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
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'unknown',
		);

		// Store in transient for admin review
		$violations = get_transient( 'dfxprl_message_rate_limit_violations' ) ?: array();
		$violations[] = $log_entry;

		// Keep only last 100 violations
		if ( count( $violations ) > 100 ) {
			$violations = array_slice( $violations, -100 );
		}

		set_transient( 'dfxprl_message_rate_limit_violations', $violations, 24 * 60 * 60 ); // 24 hours
	}
}