<?php
/**
 * Return of letters to their senders
 *
 * @package DFXPRL
 * @since 1.14.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return of letters to their senders.
 *
 * When an attendant is withdrawn, the letters received through the public form
 * will not be delivered. If the sender gave an email address, the letter can be
 * sent back by email (attached, so it can be used again for a future retreat),
 * and then its digital record is destroyed.
 *
 * @since 1.14.0
 */
class DFXPRL_LetterReturn {

	/**
	 * Message model.
	 *
	 * @since 1.14.0
	 * @var DFXPRL_ConfidentialMessage
	 */
	private $message_model;

	/**
	 * Constructor.
	 *
	 * @since 1.14.0
	 */
	public function __construct() {
		$this->message_model = new DFXPRL_ConfidentialMessage();
	}

	/**
	 * Load everything needed to return a letter, checking that it can be returned.
	 *
	 * @since 1.14.0
	 * @param int $message_id Message ID.
	 * @return array|WP_Error { message, attendant, retreat, email } or an error.
	 */
	public function get_context( $message_id ) {
		$message = $this->message_model->get( $message_id );
		if ( ! $message ) {
			return new WP_Error( 'not_found', __( 'Message not found.', 'dfx-parish-retreat-letters' ) );
		}

		$attendant = ( new DFXPRL_Attendant() )->get( $message->attendant_id );
		if ( ! $attendant || ! DFXPRL_Attendant::is_withdrawn( $attendant ) ) {
			return new WP_Error( 'not_withdrawn', __( 'Only letters for withdrawn attendants can be returned.', 'dfx-parish-retreat-letters' ) );
		}

		$email = $this->message_model->get_sender_email( $message );
		if ( '' === $email ) {
			return new WP_Error( 'no_email', __( 'The sender of this letter did not give an email address, so it cannot be returned.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat = ( new DFXPRL_Retreat() )->get( $attendant->retreat_id );
		if ( ! $retreat ) {
			return new WP_Error( 'not_found', __( 'Retreat not found.', 'dfx-parish-retreat-letters' ) );
		}

		return compact( 'message', 'attendant', 'retreat', 'email' );
	}

	/**
	 * Build the return email of a letter, without sending it.
	 *
	 * @since 1.14.0
	 * @param int  $message_id        Message ID.
	 * @param bool $with_attachments Whether to decrypt the attachments' content (not needed for a preview).
	 * @return array|WP_Error { to, subject, text, html, attachments: [ { filename, content } ], print_count } or an error.
	 */
	public function build( $message_id, $with_attachments = true ) {
		$context = $this->get_context( $message_id );
		if ( is_wp_error( $context ) ) {
			return $context;
		}

		$message   = $context['message'];
		$attendant = $context['attendant'];
		$retreat   = $context['retreat'];

		$date_format  = get_option( 'date_format' );
		$replacements = array(
			'[sender_name]'        => $message->sender_name,
			'[attendant_name]'     => $attendant->name,
			'[attendant_surnames]' => $attendant->surnames,
			'[retreat_name]'       => $retreat->name,
			'[retreat_location]'   => $retreat->location,
			'[retreat_start_date]' => $retreat->start_date ? date_i18n( $date_format, strtotime( $retreat->start_date ) ) : '',
			'[retreat_end_date]'   => $retreat->end_date ? date_i18n( $date_format, strtotime( $retreat->end_date ) ) : '',
			'[message_date]'       => date_i18n( $date_format, strtotime( $message->submitted_at ) ),
		);

		$subject = str_replace( array_keys( $replacements ), array_values( $replacements ), DFXPRL_Retreat::get_return_email_subject( $retreat ) );
		$text    = str_replace( array_keys( $replacements ), array_values( $replacements ), DFXPRL_Retreat::get_return_email_template( $retreat ) );

		$attachments = $this->get_attachments( $message, $attendant, $retreat, $with_attachments );
		if ( is_wp_error( $attachments ) ) {
			return $attachments;
		}

		return array(
			'to'          => $context['email'],
			'subject'     => wp_strip_all_tags( $subject ),
			'text'        => $text,
			'html'        => $this->get_email_html( $text ),
			'attachments' => $attachments,
			'print_count' => ( new DFXPRL_PrintLog() )->get_print_count( $message->id ),
		);
	}

	/**
	 * Send the letter back to its sender and, if the email was sent, delete the letter.
	 *
	 * @since 1.14.0
	 * @param int $message_id Message ID.
	 * @return array|WP_Error { to, print_count } or an error. On error, the letter is kept.
	 */
	public function send( $message_id ) {
		$email = $this->build( $message_id );
		if ( is_wp_error( $email ) ) {
			return $email;
		}

		// Attachments are written to a private temporary folder, so they keep their file names
		$temp_dir = trailingslashit( get_temp_dir() ) . 'dfxprl-return-' . wp_generate_password( 20, false );
		if ( ! wp_mkdir_p( $temp_dir ) ) {
			return new WP_Error( 'temp_dir', __( 'The email could not be sent. The letter has not been deleted.', 'dfx-parish-retreat-letters' ) );
		}

		$paths = array();
		foreach ( $email['attachments'] as $attachment ) {
			$path = $temp_dir . '/' . $attachment['filename'];
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Short-lived private temporary file
			if ( false === file_put_contents( $path, $attachment['content'] ) ) {
				$this->remove_temp_files( $temp_dir, $paths );
				return new WP_Error( 'temp_file', __( 'The email could not be sent. The letter has not been deleted.', 'dfx-parish-retreat-letters' ) );
			}
			chmod( $path, 0600 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
			$paths[] = $path;
		}

		$sent = wp_mail(
			$email['to'],
			$email['subject'],
			$email['html'],
			array( 'Content-Type: text/html; charset=UTF-8' ),
			$paths
		);

		$this->remove_temp_files( $temp_dir, $paths );

		if ( ! $sent ) {
			return new WP_Error( 'mail_failed', __( 'The email could not be sent. The letter has not been deleted.', 'dfx-parish-retreat-letters' ) );
		}

		// The letter is back with its sender: destroy its digital record
		$message = $this->message_model->get( $message_id );
		$this->log_returned( $message );
		$this->message_model->delete( $message_id );

		return array(
			'to'          => $email['to'],
			'print_count' => $email['print_count'],
		);
	}

	/**
	 * Count, per attendant, the letters whose sender gave an email address.
	 *
	 * @since 1.14.0
	 * @param int[] $attendant_ids Attendant IDs.
	 * @return array Map of attendant ID => count.
	 */
	public function get_returnable_counts( $attendant_ids ) {
		global $wpdb;

		$attendant_ids = array_filter( array_map( 'absint', (array) $attendant_ids ) );
		if ( empty( $attendant_ids ) ) {
			return array();
		}

		$messages_table = DFXPRL_Database::get_instance()->get_messages_table();
		$placeholders   = implode( ',', array_fill( 0, count( $attendant_ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT attendant_id, COUNT(*) AS total FROM {$messages_table} WHERE attendant_id IN ($placeholders) AND encrypted_sender_email IS NOT NULL AND encrypted_sender_email <> '' GROUP BY attendant_id", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$attendant_ids
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$counts = array();
		foreach ( $rows as $row ) {
			$counts[ (int) $row->attendant_id ] = (int) $row->total;
		}
		return $counts;
	}

	/**
	 * Count the letters of the withdrawn attendants of a retreat that can still be returned.
	 *
	 * @since 1.15.0
	 * @param int $retreat_id Retreat ID.
	 * @return int
	 */
	public function get_returnable_count_by_retreat( $retreat_id ) {
		global $wpdb;

		$database         = DFXPRL_Database::get_instance();
		$messages_table   = $database->get_messages_table();
		$attendants_table = $database->get_attendants_table();

		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$messages_table} m INNER JOIN {$attendants_table} a ON a.id = m.attendant_id WHERE a.retreat_id = %d AND a.withdrawn_at IS NOT NULL AND m.encrypted_sender_email IS NOT NULL AND m.encrypted_sender_email <> ''", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$retreat_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Build the files attached to the return email.
	 *
	 * A text letter is attached as a standalone HTML document; a letter with
	 * files is returned with its original files.
	 *
	 * @since 1.14.0
	 * @param object $message          Message object.
	 * @param object $attendant        Attendant object.
	 * @param object $retreat          Retreat object.
	 * @param bool   $with_content     Whether to decrypt the content.
	 * @return array|WP_Error List of { filename, content }, or an error.
	 */
	private function get_attachments( $message, $attendant, $retreat, $with_content ) {
		$attendant_full_name = trim( $attendant->name . ' ' . $attendant->surnames );

		if ( 'file' !== $message->message_type ) {
			$filename = sanitize_file_name( sprintf(
				/* translators: %s: attendant full name */
				__( 'Letter for %s', 'dfx-parish-retreat-letters' ),
				$attendant_full_name
			) . '.html' );

			$content = '';
			if ( $with_content ) {
				$decrypted = $this->message_model->get_with_decrypted_content( $message->id );
				if ( ! $decrypted ) {
					return new WP_Error( 'decrypt', __( 'The letter could not be decrypted.', 'dfx-parish-retreat-letters' ) );
				}
				$content = $this->get_letter_document( $decrypted, $attendant_full_name, $retreat );
			}

			return array( array( 'filename' => $filename, 'content' => $content ) );
		}

		$file_model  = new DFXPRL_MessageFile();
		$attachments = array();
		$used_names  = array();
		foreach ( $file_model->get_by_message( $message->id ) as $file ) {
			$filename = sanitize_file_name( $file->original_filename );
			if ( '' === $filename ) {
				$filename = 'file';
			}
			// Two files with the same name would overwrite each other in the temporary folder
			$base = $filename;
			for ( $i = 2; isset( $used_names[ strtolower( $filename ) ] ); $i++ ) {
				$filename = $i . '-' . $base;
			}
			$used_names[ strtolower( $filename ) ] = true;

			$content = '';
			if ( $with_content ) {
				$decrypted = $file_model->get_decrypted_file( $file->id );
				if ( ! $decrypted || ! isset( $decrypted['content'] ) ) {
					return new WP_Error( 'decrypt', __( 'The letter could not be decrypted.', 'dfx-parish-retreat-letters' ) );
				}
				$content = $decrypted['content'];
			}

			$attachments[] = array( 'filename' => $filename, 'content' => $content );
		}

		if ( empty( $attachments ) ) {
			return new WP_Error( 'no_files', __( 'The files of this letter could not be found.', 'dfx-parish-retreat-letters' ) );
		}

		return $attachments;
	}

	/**
	 * Build a standalone HTML document with a text letter, ready to open or print.
	 *
	 * @since 1.14.0
	 * @param object $message             Message with decrypted_content.
	 * @param string $attendant_full_name Attendant full name.
	 * @param object $retreat             Retreat object.
	 * @return string
	 */
	private function get_letter_document( $message, $attendant_full_name, $retreat ) {
		$title = sprintf(
			/* translators: %s: attendant full name */
			__( 'Letter for %s', 'dfx-parish-retreat-letters' ),
			$attendant_full_name
		);

		ob_start();
		?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( str_replace( '_', '-', get_locale() ) ); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( $title ); ?></title>
<style>
body { font-family: Georgia, "Times New Roman", serif; max-width: 700px; margin: 2rem auto; padding: 0 1rem; line-height: 1.6; color: #222; }
header { font-family: Arial, Helvetica, sans-serif; font-size: 0.9rem; color: #555; border-bottom: 1px solid #ccc; margin-bottom: 1.5rem; padding-bottom: 0.75rem; }
header p { margin: 0.2rem 0; }
img { max-width: 100%; height: auto; }
</style>
</head>
<body>
<header>
<p><strong><?php esc_html_e( 'To', 'dfx-parish-retreat-letters' ); ?>:</strong> <?php echo esc_html( $attendant_full_name ); ?></p>
<?php if ( ! empty( $message->sender_name ) ) : ?>
<p><strong><?php esc_html_e( 'From', 'dfx-parish-retreat-letters' ); ?>:</strong> <?php echo esc_html( $message->sender_name ); ?></p>
<?php endif; ?>
<p><strong><?php esc_html_e( 'Retreat', 'dfx-parish-retreat-letters' ); ?>:</strong> <?php echo esc_html( $retreat->name ); ?></p>
<p><strong><?php esc_html_e( 'Submitted', 'dfx-parish-retreat-letters' ); ?>:</strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $message->submitted_at ) ) ); ?></p>
</header>
<main>
<?php echo wp_kses_post( $message->decrypted_content ); ?>
</main>
</body>
</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build the HTML body of the return email from its plain text.
	 *
	 * @since 1.14.0
	 * @param string $text Email text with the placeholders already replaced.
	 * @return string
	 */
	private function get_email_html( $text ) {
		return '<div style="font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #222;">'
			. wpautop( esc_html( $text ) )
			. '</div>';
	}

	/**
	 * Remove the temporary attachments.
	 *
	 * @since 1.14.0
	 * @param string   $temp_dir Temporary folder.
	 * @param string[] $paths    Files written in it.
	 */
	private function remove_temp_files( $temp_dir, $paths ) {
		foreach ( $paths as $path ) {
			wp_delete_file( $path );
		}
		@rmdir( $temp_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
	}

	/**
	 * Log, without personal data, that a letter has been returned.
	 *
	 * @since 1.14.0
	 * @param object|null $message Message object.
	 */
	private function log_returned( $message ) {
		if ( ! $message || ! class_exists( 'DFXPRL_GDPR' ) ) {
			return;
		}

		$gdpr = DFXPRL_GDPR::get_instance();
		$gdpr->log_audit_event( 'letter_returned', array(
			'message_id'         => (int) $message->id,
			'attendant_id'       => (int) $message->attendant_id,
			'attendant_initials' => $gdpr->anonymize_name( $message->attendant_name, $message->attendant_surnames ),
		) );
	}
}
