<?php
/**
 * Tests for the REST route that receives the letters of the public form.
 *
 * @package DFXPRL
 * @subpackage Tests
 */

/**
 * Letters are sent to a route of their own, so a firewall can make an exception just for it.
 *
 * Source-level checks, so they run without WordPress.
 */
class LetterRestRouteTest extends \PHPUnit\Framework\TestCase {

	private function get_source( $file ) {
		return file_get_contents( dirname( __DIR__, 2 ) . '/' . $file );
	}

	private function get_method_source( $file, $method ) {
		$source = $this->get_source( $file );
		$start  = strpos( $source, 'function ' . $method . '(' );
		$this->assertNotFalse( $start, "Method $method should exist in $file" );
		$end = strpos( $source, "\n\t/**", $start );
		return substr( $source, $start, false === $end ? null : $end - $start );
	}

	public function test_route_is_registered_and_uses_the_same_handler() {
		$file = 'includes/class-dfx-parish-retreat-letters.php';
		$this->assertStringContainsString( "add_action( 'rest_api_init', array( \$this, 'register_rest_routes' ) );", $this->get_source( $file ) );

		$register = $this->get_method_source( $file, 'register_rest_routes' );
		$this->assertStringContainsString( "'dfxprl/v1'", $register );
		$this->assertStringContainsString( "'/letters'", $register );
		$this->assertStringContainsString( "'methods'             => 'POST'", $register );
		$this->assertStringContainsString( "'permission_callback'", $register );

		// Same checks (nonce, CAPTCHA, rate limit, file validation) as the AJAX action
		$this->assertStringContainsString( '$this->handle_message_submission();', $this->get_method_source( $file, 'rest_submit_message' ) );
		$this->assertStringContainsString( "wp_verify_nonce( sanitize_text_field( wp_unslash( \$_POST['message_nonce'] ?? '' ) ), 'dfxprl_submit_message' )", $this->get_method_source( $file, 'handle_message_submission' ) );
	}

	public function test_ajax_action_is_kept_as_fallback() {
		$source = $this->get_source( 'includes/class-dfx-parish-retreat-letters.php' );
		$this->assertStringContainsString( "add_action( 'wp_ajax_nopriv_dfxprl_submit_message'", $source );
		$this->assertStringContainsString( "'resturl'   => self::get_letters_rest_url()", $source );
		$this->assertStringContainsString( "'restNonce' => wp_create_nonce( 'wp_rest' )", $source );
	}

	public function test_form_sends_to_the_route_and_falls_back_when_rest_is_off() {
		$js = $this->get_source( 'assets/js/message-form.js' );
		$this->assertStringContainsString( "useRest ? dfxprlMessageForm.resturl : dfxprlMessageForm.ajaxurl", $js );
		$this->assertStringContainsString( "'X-WP-Nonce': dfxprlMessageForm.restNonce", $js );
		// Only REST errors (route missing or API turned off) go to admin-ajax; a firewall block does not
		$this->assertStringContainsString( "xhr.status === 404 || restCode.indexOf('rest_') === 0", $js );
	}
}
