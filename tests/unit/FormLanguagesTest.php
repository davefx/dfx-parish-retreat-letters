<?php
/**
 * Tests for the languages of the public letters form.
 *
 * @package DFXPRL
 * @subpackage Tests
 */

require_once dirname( __DIR__, 2 ) . '/includes/class-form-languages.php';

/**
 * Test language detection and the multilingual letters form.
 *
 * The language matching is pure PHP; the rest are source-level checks, so they run
 * without WordPress.
 */
class FormLanguagesTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Get the source of a method of a class file.
	 *
	 * @param string $file   Path relative to the plugin root.
	 * @param string $method Method name.
	 * @return string
	 */
	private function get_method_source( $file, $method ) {
		$source = file_get_contents( dirname( __DIR__, 2 ) . '/' . $file );
		$start  = strpos( $source, 'function ' . $method . '(' );
		$this->assertNotFalse( $start, "Method $method should exist in $file" );

		// The method ends where the next method's docblock starts
		$end = strpos( $source, "\n\t/**", $start );
		return substr( $source, $start, false === $end ? null : $end - $start );
	}

	public function test_language_codes_are_matched_to_supported_locales() {
		$this->assertSame( 'es_ES', DFXPRL_Form_Languages::match_locale( 'es' ) );
		$this->assertSame( 'es_ES', DFXPRL_Form_Languages::match_locale( 'es-MX' ) );
		$this->assertSame( 'gl_ES', DFXPRL_Form_Languages::match_locale( 'gl' ) );
		$this->assertSame( 'ca', DFXPRL_Form_Languages::match_locale( 'ca-ES' ) );
		$this->assertSame( 'pt_PT', DFXPRL_Form_Languages::match_locale( 'pt-BR' ) );
		$this->assertSame( 'en_US', DFXPRL_Form_Languages::match_locale( 'en_GB' ) );
		$this->assertSame( 'de_DE', DFXPRL_Form_Languages::match_locale( 'DE-de' ) );
		$this->assertNull( DFXPRL_Form_Languages::match_locale( 'ja' ) );
		$this->assertNull( DFXPRL_Form_Languages::match_locale( '' ) );
		$this->assertNull( DFXPRL_Form_Languages::match_locale( '../../etc/passwd' ) );
	}

	public function test_browser_language_follows_the_accept_language_preferences() {
		$this->assertSame( 'ca', DFXPRL_Form_Languages::match_accept_language( 'ca,es;q=0.9,en;q=0.8' ) );
		$this->assertSame( 'eu', DFXPRL_Form_Languages::match_accept_language( 'es;q=0.5,eu;q=0.9' ) );
		$this->assertSame( 'fr_FR', DFXPRL_Form_Languages::match_accept_language( 'ja,fr-CA;q=0.7,de;q=0.6' ) );
		$this->assertSame( 'it_IT', DFXPRL_Form_Languages::match_accept_language( 'it, de' ) );
		$this->assertNull( DFXPRL_Form_Languages::match_accept_language( 'ja,zh;q=0.8' ) );
		$this->assertNull( DFXPRL_Form_Languages::match_accept_language( 'es;q=0' ) );
		$this->assertNull( DFXPRL_Form_Languages::match_accept_language( '' ) );
	}

	public function test_every_supported_language_has_a_translation_of_the_form() {
		foreach ( array_keys( DFXPRL_Form_Languages::LANGUAGES ) as $locale ) {
			if ( 'en_US' === $locale ) {
				continue;
			}
			$this->assertFileExists( dirname( __DIR__, 2 ) . "/languages/dfx-parish-retreat-letters-$locale.mo" );
		}
	}

	public function test_submission_answers_in_the_language_of_the_form() {
		$source = $this->get_method_source( 'includes/class-dfx-parish-retreat-letters.php', 'handle_message_submission' );
		$this->assertLessThan(
			strpos( $source, 'wp_verify_nonce(' ),
			strpos( $source, 'DFXPRL_Form_Languages::switch_locale( $locale )' ),
			'The language must be switched before the first error message'
		);

		$form = $this->get_method_source( 'includes/class-dfx-parish-retreat-letters.php', 'render_message_form' );
		$this->assertStringContainsString( 'name="dfxprl_lang"', $form );
	}

	public function test_automatic_translations_are_cached_by_hash_of_the_original() {
		$source = $this->get_method_source( 'includes/class-translator.php', 'get_cached_retreat_translations' );
		$this->assertStringContainsString( "hash_equals( (string) ( \$entry['h'] ?? '' ), \$this->hash_text( \$text ) )", $source );
		$this->assertStringContainsString( 'get_transient( self::ERROR_TRANSIENT )', $source, 'Failures must not be retried on every page view' );
		$this->assertStringContainsString( 'store_cache_entries(', $source );
	}

	public function test_translate_button_checks_nonce_and_permissions() {
		$source = $this->get_method_source( 'includes/class-admin.php', 'ajax_translate_form_texts' );
		$this->assertStringContainsString( "wp_verify_nonce( sanitize_text_field( wp_unslash( \$_POST['nonce'] ?? '' ) ), 'dfxprl_retreats_nonce' )", $source );
		$this->assertStringContainsString( 'current_user_can_manage_retreat( $retreat_id )', $source );
	}

	public function test_translations_never_call_data_invalid_or_disabled_in_romance_languages() {
		$words = array(
			'es_ES' => '/\binválid|discapacitad/iu',
			'ca'    => '/\binvàlid|discapacitat/iu',
			'gl_ES' => '/\binválid|discapacitad/iu',
			'pt_PT' => '/\binválid|deficiente/iu',
			'it_IT' => '/\binvalid|disabile/iu',
		);
		foreach ( $words as $locale => $pattern ) {
			$po = file_get_contents( dirname( __DIR__, 2 ) . "/languages/dfx-parish-retreat-letters-$locale.po" );
			preg_match_all( '/^msgstr "(.*)"$/m', $po, $matches );
			$this->assertDoesNotMatchRegularExpression( $pattern, implode( "\n", $matches[1] ), $locale );
		}
	}

	public function test_every_text_marked_for_live_translation_is_sent_by_the_server() {
		$source  = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-dfx-parish-retreat-letters.php' );
		$strings = $this->get_method_source( 'includes/class-dfx-parish-retreat-letters.php', 'get_page_strings' );
		preg_match_all( '/data-dfxprl-i18n(?:-placeholder|-title)?="([a-z_]+)(?:_<\?php)?/', $source, $matches );
		$this->assertNotEmpty( $matches[1] );
		foreach ( array_unique( $matches[1] ) as $key ) {
			$this->assertMatchesRegularExpression( "/'" . preg_quote( rtrim( $key, '_' ), '/' ) . "(_1)?'(\s*=>|\]\s*=)/", $strings, "Missing text for $key" );
		}
	}

	public function test_changing_the_language_only_loads_the_texts() {
		$source = $this->get_method_source( 'includes/class-dfx-parish-retreat-letters.php', 'ajax_get_form_strings' );
		$this->assertStringContainsString( 'check_rate_limit(', $source );
		$this->assertStringContainsString( 'get_attendant_by_token( $token )', $source );
		$this->assertStringContainsString( 'DFXPRL_Form_Languages::remember_locale( $locale )', $source );

		$script = file_get_contents( dirname( __DIR__, 2 ) . '/assets/js/message-form.js' );
		$this->assertStringContainsString( "action: 'dfxprl_form_strings'", $script );
		$this->assertStringNotContainsString( "closest('form').trigger('submit')", $script, 'The page must not be reloaded' );
	}

	public function test_multilingual_form_can_be_disabled_per_retreat() {
		$this->assertTrue( DFXPRL_Form_Languages::is_enabled_for( (object) array() ), 'Enabled by default' );
		$this->assertTrue( DFXPRL_Form_Languages::is_enabled_for( (object) array( 'multilingual_enabled' => '1' ) ) );
		$this->assertFalse( DFXPRL_Form_Languages::is_enabled_for( (object) array( 'multilingual_enabled' => '0' ) ) );

		$display = $this->get_method_source( 'includes/class-dfx-parish-retreat-letters.php', 'display_message_form' );
		$this->assertStringContainsString( 'if ( DFXPRL_Form_Languages::is_enabled_for( $attendant ) ) {', $display );
		$selector = $this->get_method_source( 'includes/class-dfx-parish-retreat-letters.php', 'render_language_selector' );
		$this->assertStringContainsString( 'if ( ! DFXPRL_Form_Languages::is_enabled_for( $attendant ) ) {', $selector );
		$strings = $this->get_method_source( 'includes/class-dfx-parish-retreat-letters.php', 'ajax_get_form_strings' );
		$this->assertStringContainsString( '! DFXPRL_Form_Languages::is_enabled_for( $attendant )', $strings );
	}
}
