<?php
/**
 * Automatic translation of the retreat texts with DeepL
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.15.0
 *
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */

/**
 * Automatic translation of the retreat texts with DeepL (API v2).
 *
 * The free plan, DeepL API Free, translates 500,000 characters a month; its keys end in
 * ":fx" and use their own endpoint. DeepL replaced Google Cloud Translation in 26.11.05,
 * because its translations are better.
 *
 * Only the texts configured for a retreat (custom message, legal disclaimer and its
 * acceptance text) are sent to DeepL: never letters or personal data. Translations
 * are cached in the retreat, together with a hash of the original text, so each text
 * is translated once per language and again only when it changes.
 *
 * @since      1.15.0
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */
class DFXPRL_Translator {

	/**
	 * DeepL API endpoint for paid keys.
	 *
	 * @since 26.11.05
	 * @var string
	 */
	const ENDPOINT = 'https://api.deepl.com/v2/translate';

	/**
	 * DeepL API endpoint for DeepL API Free keys (ending in ":fx").
	 *
	 * @since 26.11.05
	 * @var string
	 */
	const ENDPOINT_FREE = 'https://api-free.deepl.com/v2/translate';

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
	 * Get the DeepL API key.
	 *
	 * It can be defined in wp-config.php as DFXPRL_DEEPL_API_KEY, or saved in the global
	 * settings.
	 *
	 * @since 1.15.0
	 * @return string
	 */
	public function get_api_key() {
		if ( defined( 'DFXPRL_DEEPL_API_KEY' ) && DFXPRL_DEEPL_API_KEY ) {
			return (string) DFXPRL_DEEPL_API_KEY;
		}
		return (string) DFXPRL_GlobalSettings::get_instance()->get( 'deepl_api_key', '' );
	}

	/**
	 * Get the DeepL endpoint for an API key: the free one for DeepL API Free keys.
	 *
	 * @since 26.11.05
	 * @param string $api_key API key.
	 * @return string
	 */
	public static function get_endpoint( $api_key ) {
		return ':fx' === substr( (string) $api_key, -3 ) ? self::ENDPOINT_FREE : self::ENDPOINT;
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
	 * Translate texts with DeepL.
	 *
	 * Catalan, Basque and Galician only work with DeepL's quality model, which is the
	 * default one (no model is requested), and without formality.
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

		$request = array(
			'text'         => array_values( $texts ),
			// The source language without variant (EN, PT); the target with it (EN-US, PT-PT)
			'source_lang'  => strtok( DFXPRL_Form_Languages::get_translation_code( $source_locale ), '-' ),
			'target_lang'  => DFXPRL_Form_Languages::get_translation_code( $target_locale ),
			'tag_handling' => 'html',
		);
		$formality = DFXPRL_Form_Languages::get_formality( $target_locale );
		if ( $formality ) {
			$request['formality'] = $formality;
		}

		$api_key  = $this->get_api_key();
		$response = wp_remote_post(
			self::get_endpoint( $api_key ),
			array(
				'timeout' => 15,
				'headers' => array(
					'Content-Type'  => 'application/json; charset=utf-8',
					'Authorization' => 'DeepL-Auth-Key ' . $api_key,
				),
				'body'    => wp_json_encode( $request ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) || empty( $body['translations'] ) ) {
			$message = $body['message'] ?? wp_remote_retrieve_response_message( $response );
			return new WP_Error(
				'translation_failed',
				/* translators: %s: error message returned by DeepL */
				sprintf( __( 'DeepL error: %s', 'dfx-parish-retreat-letters' ), $message )
			);
		}

		$translated = array();
		foreach ( array_keys( $texts ) as $index => $key ) {
			if ( isset( $body['translations'][ $index ]['text'] ) ) {
				$translated[ $key ] = $body['translations'][ $index ]['text'];
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
	 * Get automatic translations of retreat texts, from the cache or from DeepL.
	 *
	 * Texts that cannot be translated (no API key, or DeepL fails) are left out, so the
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
	 * invalidates the cache. So is the translation service: the translations cached
	 * from Google before 26.11.05 are done again with DeepL.
	 *
	 * @since 1.15.0
	 * @param string $text Original text.
	 * @return string
	 */
	private function hash_text( $text ) {
		return md5( 'deepl' . "\n" . DFXPRL_Form_Languages::get_main_locale() . "\n" . $text );
	}
}
