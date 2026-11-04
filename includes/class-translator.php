<?php
/**
 * Automatic translation of the retreat texts with Google Cloud Translation
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.15.0
 *
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */

/**
 * Automatic translation of the retreat texts with Google Cloud Translation (API v2).
 *
 * Only the texts configured for a retreat (custom message, legal disclaimer and its
 * acceptance text) are sent to Google: never letters or personal data. Translations
 * are cached in the retreat, together with a hash of the original text, so each text
 * is translated once per language and again only when it changes.
 *
 * @since      1.15.0
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */
class DFXPRL_Translator {

	/**
	 * Google Cloud Translation API endpoint.
	 *
	 * @since 1.15.0
	 * @var string
	 */
	const ENDPOINT = 'https://translation.googleapis.com/language/translate/v2';

	/**
	 * Transient set after a failed request, to stop retrying on every page view for a while.
	 *
	 * @since 1.15.0
	 * @var string
	 */
	const ERROR_TRANSIENT = 'dfxprl_translation_error';

	/**
	 * The single instance of the class.
	 *
	 * @since 1.15.0
	 * @var DFXPRL_Translator|null
	 */
	private static $instance = null;

	/**
	 * Get the single instance of the class.
	 *
	 * @since 1.15.0
	 * @return DFXPRL_Translator
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get the Google Cloud Translation API key.
	 *
	 * It can be defined in wp-config.php as DFXPRL_GOOGLE_TRANSLATE_API_KEY, or saved in
	 * the global settings.
	 *
	 * @since 1.15.0
	 * @return string
	 */
	public function get_api_key() {
		if ( defined( 'DFXPRL_GOOGLE_TRANSLATE_API_KEY' ) && DFXPRL_GOOGLE_TRANSLATE_API_KEY ) {
			return (string) DFXPRL_GOOGLE_TRANSLATE_API_KEY;
		}
		return (string) DFXPRL_GlobalSettings::get_instance()->get( 'google_translate_api_key', '' );
	}

	/**
	 * Check whether automatic translation is available.
	 *
	 * @since 1.15.0
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->get_api_key();
	}

	/**
	 * Translate texts with Google Cloud Translation.
	 *
	 * @since 1.15.0
	 * @param array  $texts         Key => text. Texts may contain HTML.
	 * @param string $target_locale Supported locale to translate to.
	 * @param string $source_locale Supported locale of the texts.
	 * @return array|WP_Error Key => translated text.
	 */
	public function translate( $texts, $target_locale, $source_locale ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'not_configured', __( 'Automatic translation is not configured.', 'dfx-parish-retreat-letters' ) );
		}

		$texts = array_filter( $texts, function( $text ) {
			return '' !== trim( (string) $text );
		} );
		if ( ! $texts ) {
			return array();
		}

		$response = wp_remote_post(
			add_query_arg( 'key', rawurlencode( $this->get_api_key() ), self::ENDPOINT ),
			array(
				'timeout' => 15,
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'    => wp_json_encode( array(
					'q'      => array_values( $texts ),
					'source' => DFXPRL_Form_Languages::get_translation_code( $source_locale ),
					'target' => DFXPRL_Form_Languages::get_translation_code( $target_locale ),
					'format' => 'html',
				) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) || empty( $body['data']['translations'] ) ) {
			$message = $body['error']['message'] ?? wp_remote_retrieve_response_message( $response );
			return new WP_Error(
				'translation_failed',
				/* translators: %s: error message returned by Google */
				sprintf( __( 'Google Cloud Translation error: %s', 'dfx-parish-retreat-letters' ), $message )
			);
		}

		$translated = array();
		foreach ( array_keys( $texts ) as $index => $key ) {
			if ( isset( $body['data']['translations'][ $index ]['translatedText'] ) ) {
				$translated[ $key ] = $body['data']['translations'][ $index ]['translatedText'];
			}
		}
		return $translated;
	}

	/**
	 * Translate texts of a retreat form and sanitize the result like the original field.
	 *
	 * @since 1.15.0
	 * @param array  $texts         Field => text (fields from DFXPRL_Form_Languages::RETREAT_FIELDS).
	 * @param string $target_locale Supported locale to translate to.
	 * @return array|WP_Error Field => translated text.
	 */
	public function translate_retreat_texts( $texts, $target_locale ) {
		$translated = $this->translate( $texts, $target_locale, DFXPRL_Form_Languages::get_main_locale() );
		if ( is_wp_error( $translated ) ) {
			return $translated;
		}

		foreach ( $translated as $field => $text ) {
			// The acceptance text is plain text: the HTML format of the request escapes some characters
			$translated[ $field ] = 'disclaimer_acceptance_text' === $field
				? sanitize_text_field( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) )
				: wp_kses_post( $text );
		}
		return $translated;
	}

	/**
	 * Get automatic translations of retreat texts, from the cache or from Google.
	 *
	 * Texts that cannot be translated (no API key, or Google fails) are left out, so the
	 * caller shows the original text.
	 *
	 * @since 1.15.0
	 * @param object $retreat Retreat object (with form_translations_cache).
	 * @param array  $texts   Field => original text.
	 * @param string $locale  Supported locale to translate to.
	 * @return array Field => translated text.
	 */
	public function get_cached_retreat_translations( $retreat, $texts, $locale ) {
		$cache  = $this->decode_cache( $retreat->form_translations_cache ?? '' );
		$result = array();
		$pending = array();

		foreach ( $texts as $field => $text ) {
			$entry = $cache[ $locale ][ $field ] ?? null;
			if ( $entry && hash_equals( (string) ( $entry['h'] ?? '' ), $this->hash_text( $text ) ) ) {
				$result[ $field ] = (string) $entry['t'];
			} else {
				$pending[ $field ] = $text;
			}
		}

		if ( ! $pending || ! $this->is_configured() || get_transient( self::ERROR_TRANSIENT ) ) {
			return $result;
		}

		$translated = $this->translate_retreat_texts( $pending, $locale );
		if ( is_wp_error( $translated ) ) {
			set_transient( self::ERROR_TRANSIENT, $translated->get_error_message(), 10 * MINUTE_IN_SECONDS );
			return $result;
		}

		$new_entries = array();
		foreach ( $translated as $field => $text ) {
			$result[ $field ]      = $text;
			$new_entries[ $field ] = array(
				'h' => $this->hash_text( $pending[ $field ] ),
				't' => $text,
			);
		}
		$this->store_cache_entries( (int) $retreat->id, $locale, $new_entries );

		return $result;
	}

	/**
	 * Store new automatic translations in the cache of a retreat.
	 *
	 * The cache is read again just before writing, so translations stored meanwhile
	 * for other languages are kept.
	 *
	 * @since 1.15.0
	 * @param int    $retreat_id Retreat ID.
	 * @param string $locale     Locale of the translations.
	 * @param array  $entries    Field => array( 'h' => hash of the original, 't' => translation ).
	 */
	private function store_cache_entries( $retreat_id, $locale, $entries ) {
		global $wpdb;

		$table = DFXPRL_Database::get_instance()->get_retreats_table();
		$cache = $this->decode_cache( $wpdb->get_var( $wpdb->prepare(
			"SELECT form_translations_cache FROM {$table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$retreat_id
		) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$cache[ $locale ] = array_merge( $cache[ $locale ] ?? array(), $entries );

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$table,
			array( 'form_translations_cache' => wp_json_encode( $cache ) ),
			array( 'id' => $retreat_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Decode the translation cache of a retreat.
	 *
	 * @since 1.15.0
	 * @param string|null $json Stored cache.
	 * @return array Locale => field => array( 'h', 't' ).
	 */
	private function decode_cache( $json ) {
		$cache = json_decode( (string) $json, true );
		return is_array( $cache ) ? $cache : array();
	}

	/**
	 * Hash of an original text, to know when its cached translation is outdated.
	 *
	 * The main language is part of the hash, so changing the site language also
	 * invalidates the cache.
	 *
	 * @since 1.15.0
	 * @param string $text Original text.
	 * @return string
	 */
	private function hash_text( $text ) {
		return md5( DFXPRL_Form_Languages::get_main_locale() . "\n" . $text );
	}
}
