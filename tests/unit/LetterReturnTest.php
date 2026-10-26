<?php
/**
 * Tests for returning letters of withdrawn attendants to their senders.
 *
 * @package DFXPRL
 * @subpackage Tests
 */

/**
 * Test the sender email and letter return feature.
 *
 * These are source-level checks on the privacy- and security-relevant parts of the
 * feature, so they run without WordPress.
 */
class LetterReturnTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Get the source of a file of the plugin.
	 *
	 * @param string $file Path relative to the plugin root.
	 * @return string
	 */
	private function get_source( $file ) {
		return file_get_contents( dirname( __DIR__, 2 ) . '/' . $file );
	}

	/**
	 * Get the source of a method of a class file.
	 *
	 * @param string $file   Path relative to the plugin root.
	 * @param string $method Method name.
	 * @return string
	 */
	private function get_method_source( $file, $method ) {
		$source = $this->get_source( $file );
		$start  = strpos( $source, 'function ' . $method . '(' );
		$this->assertNotFalse( $start, "Method $method should exist in $file" );

		// The method ends where the next method's docblock starts
		$end = strpos( $source, "\n\t/**", $start );
		return substr( $source, $start, false === $end ? null : $end - $start );
	}

	public function test_sender_email_is_stored_encrypted_with_a_lookup_hash() {
		$schema = $this->get_method_source( 'includes/class-database.php', 'setup_tables' );
		$this->assertStringContainsString( 'encrypted_sender_email text NULL DEFAULT NULL', $schema );
		$this->assertStringContainsString( 'sender_email_hash varchar(64) NULL DEFAULT NULL', $schema );
		$this->assertStringNotContainsString( ' sender_email varchar', $schema, 'The email must never be stored in plain text' );

		$create = $this->get_method_source( 'includes/class-confidential-message.php', 'create' );
		$this->assertStringContainsString( "encrypt_data( \$sanitized_data['sender_email'] )", $create );
		$this->assertStringContainsString( 'hash_sender_email(', $create );
	}

	public function test_sender_email_must_be_typed_twice_and_be_valid() {
		$source = $this->get_method_source( 'includes/class-dfx-parish-retreat-letters.php', 'handle_message_submission' );
		$this->assertStringContainsString( "\$_POST['sender_email_confirm']", $source );
		$this->assertStringContainsString( 'strtolower( $sender_email ) !== strtolower( $sender_email_confirm )', $source );
		$this->assertStringContainsString( 'is_email( $sender_email )', $source );
	}

	public function test_return_ajax_actions_check_nonce_and_permissions() {
		$admin = $this->get_source( 'includes/class-admin.php' );
		foreach ( array( 'return_letter_preview', 'return_letter' ) as $action ) {
			$this->assertStringContainsString( "'wp_ajax_dfxprl_{$action}', array( \$this, 'ajax_{$action}' )", $admin );
			$this->assertStringContainsString( 'get_returnable_message_id_from_request()', $this->get_method_source( 'includes/class-admin.php', 'ajax_' . $action ) );
		}

		$check = $this->get_method_source( 'includes/class-admin.php', 'get_returnable_message_id_from_request' );
		$this->assertStringContainsString( "wp_verify_nonce( sanitize_text_field( wp_unslash( \$_POST['nonce'] ?? '' ) ), 'dfxprl_retreats_nonce' )", $check );
		$this->assertStringContainsString( 'current_user_can_manage_messages( $message->retreat_id )', $check );
	}

	public function test_only_letters_of_withdrawn_attendants_with_email_can_be_returned() {
		$source = $this->get_method_source( 'includes/class-letter-return.php', 'get_context' );
		$this->assertStringContainsString( 'DFXPRL_Attendant::is_withdrawn( $attendant )', $source );
		$this->assertStringContainsString( "'no_email'", $source );
	}

	public function test_letter_is_deleted_only_after_the_email_is_sent() {
		$source = $this->get_method_source( 'includes/class-letter-return.php', 'send' );
		$mail   = strpos( $source, 'wp_mail(' );
		$failed = strpos( $source, 'if ( ! $sent )' );
		$delete = strpos( $source, '$this->message_model->delete(' );

		$this->assertNotFalse( $mail );
		$this->assertNotFalse( $failed );
		$this->assertNotFalse( $delete );
		$this->assertLessThan( $delete, $failed, 'A failed email must return before the letter is deleted' );

		// Temporary attachments are removed whether the email was sent or not
		$this->assertLessThan( $failed, strpos( $source, 'remove_temp_files(', $mail ) );
	}

	public function test_deleting_a_message_also_deletes_its_print_history() {
		$source = $this->get_method_source( 'includes/class-confidential-message.php', 'delete' );
		$this->assertStringContainsString( 'get_message_print_log_table()', $source );
	}

	public function test_gdpr_requests_find_messages_by_sender_email() {
		foreach ( array( 'export_personal_data', 'erase_personal_data' ) as $method ) {
			$this->assertStringContainsString( 'm.sender_email_hash = %s', $this->get_method_source( 'includes/class-gdpr.php', $method ) );
		}
	}

	public function test_message_form_stylesheet_is_balanced() {
		$css = $this->get_source( 'assets/css/message-form.css' );
		$this->assertSame( substr_count( $css, '{' ), substr_count( $css, '}' ), 'Every CSS block must be closed' );
	}
}
