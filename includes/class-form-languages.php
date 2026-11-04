<?php
/**
 * Languages of the public message form
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.15.0
 *
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */

/**
 * Languages of the public message form.
 *
 * The message form can be shown in any of the supported languages, regardless of the
 * site language. The language is taken from the selector (?lang=), then from the
 * remembered choice (cookie), then from the browser (Accept-Language), and finally the
 * site language.
 *
 * The texts configured for each retreat (custom message, legal disclaimer and its
 * acceptance text) are written in the site language. They can have a version for each
 * other language, written in the retreat settings; when there is none, they are
 * translated automatically (and cached) if a translation service is configured.
 *
 * @since      1.15.0
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */
class DFXPRL_Form_Languages {

	/**
	 * Text domain of the plugin.
	 *
	 * @since 1.15.0
	 * @var string
	 */
	const TEXT_DOMAIN = 'dfx-parish-retreat-letters';

	/**
	 * Name of the cookie that remembers the language chosen in the selector.
	 *
	 * @since 1.15.0
	 * @var string
	 */
	const COOKIE = 'dfxprl_lang';

	/**
	 * Retreat texts that can be translated.
	 *
	 * @since 1.15.0
	 * @var string[]
	 */
	const RETREAT_FIELDS = array( 'custom_message', 'disclaimer_text', 'disclaimer_acceptance_text' );

	/**
	 * Supported languages: WordPress locale => array( native name, Google Translate code ).
	 *
	 * @since 1.15.0
	 * @var array
	 */
	const LANGUAGES = array(
		'es_ES' => array( 'Español', 'es' ),
		'ca'    => array( 'Català', 'ca' ),
		'eu'    => array( 'Euskara', 'eu' ),
		'gl_ES' => array( 'Galego', 'gl' ),
		'en_US' => array( 'English', 'en' ),
		'pt_PT' => array( 'Português', 'pt-PT' ),
		'fr_FR' => array( 'Français', 'fr' ),
		'it_IT' => array( 'Italiano', 'it' ),
		'de_DE' => array( 'Deutsch', 'de' ),
	);

	/**
	 * Locale the form is currently shown in, once switched.
	 *
	 * @since 1.15.0
	 * @var string|null
	 */
	private static $current_locale = null;

	/**
	 * Get the supported languages.
	 *
	 * @since 1.15.0
	 * @return array Locale => native name.
	 */
	public static function get_languages() {
		return array_map( function( $language ) {
			return $language[0];
		}, self::LANGUAGES );
	}

	/**
	 * Get the Google Translate code of a supported locale.
	 *
	 * @since 1.15.0
	 * @param string $locale Locale.
	 * @return string
	 */
	public static function get_translation_code( $locale ) {
		return self::LANGUAGES[ $locale ][1] ?? 'en';
	}

	/**
	 * Get the main language: the language of the site, in which the retreat texts are written.
	 *
	 * @since 1.15.0
	 * @return string Supported locale.
	 */
	public static function get_main_locale() {
		$site_locale = get_option( 'WPLANG' );
		return self::match_locale( $site_locale ? $site_locale : 'en_US' ) ?? 'en_US';
	}

	/**
	 * Get the locale the form is shown in.
	 *
	 * @since 1.15.0
	 * @return string
	 */
	public static function get_current_locale() {
		return self::$current_locale ?? self::get_main_locale();
	}

	/**
	 * Find the supported locale for a language code (es, es-ES, pt-BR, gl_ES...).
	 *
	 * @since 1.15.0
	 * @param string $code Language code or locale.
	 * @return string|null Supported locale, or null if the language is not supported.
	 */
	public static function match_locale( $code ) {
		$code = str_replace( '-', '_', trim( (string) $code ) );
		if ( '' === $code ) {
			return null;
		}

		foreach ( array_keys( self::LANGUAGES ) as $locale ) {
			if ( strtolower( $locale ) === strtolower( $code ) ) {
				return $locale;
			}
		}

		// Any variant of a supported language (es_MX, pt_BR, en_GB...) uses that language
		$language = strtolower( strtok( $code, '_' ) );
		foreach ( array_keys( self::LANGUAGES ) as $locale ) {
			if ( strtolower( strtok( $locale, '_' ) ) === $language ) {
				return $locale;
			}
		}

		return null;
	}

	/**
	 * Check whether the letters form of a retreat is multilingual.
	 *
	 * @since 1.15.0
	 * @param object $retreat Retreat, or attendant with the retreat columns.
	 * @return bool
	 */
	public static function is_enabled_for( $retreat ) {
		return ! isset( $retreat->multilingual_enabled ) || (bool) $retreat->multilingual_enabled;
	}

	/**
	 * Decide the language of the form for the current request.
	 *
	 * @since 1.15.0
	 * @return string Supported locale.
	 */
	public static function detect_locale() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only language choice
		$chosen = self::match_locale( sanitize_text_field( wp_unslash( $_GET['lang'] ?? '' ) ) );
		if ( $chosen ) {
			return $chosen;
		}

		$remembered = self::match_locale( sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ?? '' ) ) );
		if ( $remembered ) {
			return $remembered;
		}

		$browser = self::match_accept_language( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '' ) ) );
		if ( $browser ) {
			return $browser;
		}

		return self::get_main_locale();
	}

	/**
	 * Find the preferred supported language in an Accept-Language header.
	 *
	 * @since 1.15.0
	 * @param string $header Accept-Language header, e.g. "ca,es;q=0.9,en;q=0.8".
	 * @return string|null Supported locale, or null if none is accepted.
	 */
	public static function match_accept_language( $header ) {
		$candidates = array();
		foreach ( explode( ',', (string) $header ) as $position => $part ) {
			$pieces = explode( ';', trim( $part ) );
			$code   = trim( $pieces[0] );
			$weight = 1.0;
			if ( isset( $pieces[1] ) && preg_match( '/q\s*=\s*([0-9.]+)/', $pieces[1], $matches ) ) {
				$weight = (float) $matches[1];
			}
			if ( '' !== $code && '*' !== $code && $weight > 0 ) {
				// Keep the order of the header among languages with the same weight
				$candidates[] = array( $code, $weight, $position );
			}
		}

		usort( $candidates, function( $a, $b ) {
			return $b[1] <=> $a[1] ?: $a[2] <=> $b[2];
		} );

		foreach ( $candidates as $candidate ) {
			$locale = self::match_locale( $candidate[0] );
			if ( $locale ) {
				return $locale;
			}
		}
		return null;
	}

	/**
	 * Remember the language chosen in the selector for the next pages.
	 *
	 * @since 1.15.0
	 * @param string $locale Supported locale.
	 */
	public static function remember_locale( $locale ) {
		if ( ! headers_sent() ) {
			setcookie( self::COOKIE, $locale, time() + YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * Show the plugin texts of this request in the given language.
	 *
	 * WordPress' switch_to_locale() only accepts locales with core translations installed,
	 * so the plugin text domain is reloaded directly instead.
	 *
	 * @since 1.15.0
	 * @param string $locale Supported locale.
	 */
	public static function switch_locale( $locale ) {
		self::$current_locale = $locale;

		// Do not let WordPress load the site language again on the next translation
		unload_textdomain( self::TEXT_DOMAIN );
		self::load_translations( $locale );

		add_filter( 'language_attributes', array( __CLASS__, 'filter_language_attributes' ) );
	}

	/**
	 * Load the plugin translations of a language.
	 *
	 * WordPress uses only the language pack of translate.wordpress.org when the site has
	 * one, and those packs may lack recent texts. So both files are loaded: first the
	 * language pack, whose translations take precedence, and then the file bundled with
	 * the plugin, which fills in the texts the pack does not translate yet. WordPress
	 * uses the first loaded translation of each text.
	 *
	 * @since 1.15.0
	 * @param string $locale Locale.
	 */
	public static function load_translations( $locale ) {
		if ( 'en_US' === $locale ) {
			return;
		}

		$file_name = self::TEXT_DOMAIN . '-' . $locale . '.mo';
		load_textdomain( self::TEXT_DOMAIN, WP_LANG_DIR . '/plugins/' . $file_name, $locale );
		load_textdomain( self::TEXT_DOMAIN, DFXPRL_PLUGIN_DIR . 'languages/' . $file_name, $locale );
	}

	/**
	 * Set the lang attribute of the page to the language of the form.
	 *
	 * @since 1.15.0
	 * @param string $output Language attributes.
	 * @return string
	 */
	public static function filter_language_attributes( $output ) {
		$lang = esc_attr( str_replace( '_', '-', self::get_current_locale() ) );
		if ( preg_match( '/lang="[^"]*"/', $output ) ) {
			return preg_replace( '/lang="[^"]*"/', 'lang="' . $lang . '"', $output );
		}
		return trim( $output . ' lang="' . $lang . '"' );
	}

	/**
	 * Format a date in the language of the form.
	 *
	 * @since 1.15.0
	 * @param string $date Date (Y-m-d).
	 * @return string
	 */
	public static function format_date( $date ) {
		$timestamp = strtotime( $date );
		if ( ! $timestamp ) {
			return '';
		}

		$locale = self::get_current_locale();

		// In the site language, keep the site date format
		if ( $locale === self::get_main_locale() ) {
			return date_i18n( get_option( 'date_format' ), $timestamp );
		}

		if ( class_exists( 'IntlDateFormatter' ) ) {
			$formatter = new IntlDateFormatter( $locale, IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'UTC' );
			$formatted = $formatter->format( $timestamp );
			if ( false !== $formatted ) {
				return $formatted;
			}
		}

		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * Get the stored versions of the retreat texts written for each language.
	 *
	 * @since 1.15.0
	 * @param object $retreat Retreat object.
	 * @return array Locale => array( field => text ).
	 */
	public static function get_manual_translations( $retreat ) {
		$translations = json_decode( (string) ( $retreat->form_translations ?? '' ), true );
		return is_array( $translations ) ? $translations : array();
	}

	/**
	 * Sanitize the versions of the retreat texts for each language, as sent by the retreat form.
	 *
	 * @since 1.15.0
	 * @param mixed $translations Locale => array( field => text ).
	 * @return string|null JSON to store, or null if there are no translations.
	 */
	public static function sanitize_manual_translations( $translations ) {
		if ( is_string( $translations ) ) {
			$translations = json_decode( $translations, true );
		}
		if ( ! is_array( $translations ) ) {
			return null;
		}

		$main_locale = self::get_main_locale();
		$clean       = array();
		foreach ( array_keys( self::LANGUAGES ) as $locale ) {
			if ( $locale === $main_locale || empty( $translations[ $locale ] ) || ! is_array( $translations[ $locale ] ) ) {
				continue;
			}
			$fields = array(
				'custom_message'             => wp_kses_post( $translations[ $locale ]['custom_message'] ?? '' ),
				'disclaimer_text'            => wp_kses_post( $translations[ $locale ]['disclaimer_text'] ?? '' ),
				'disclaimer_acceptance_text' => sanitize_text_field( $translations[ $locale ]['disclaimer_acceptance_text'] ?? '' ),
			);
			$fields = array_filter( $fields, function( $text ) {
				return '' !== trim( $text );
			} );
			if ( $fields ) {
				$clean[ $locale ] = $fields;
			}
		}

		return $clean ? wp_json_encode( $clean ) : null;
	}

	/**
	 * Get the retreat texts to show in the form in a language.
	 *
	 * Uses, in this order: the version written for that language, a cached or new
	 * automatic translation, and the original text.
	 *
	 * @since 1.15.0
	 * @param object $retreat Retreat object (with all its columns).
	 * @param string $locale  Supported locale.
	 * @return array Field => array( 'text' => string, 'automatic' => bool ).
	 */
	public static function get_retreat_texts( $retreat, $locale ) {
		$texts = array();
		foreach ( self::RETREAT_FIELDS as $field ) {
			$texts[ $field ] = array(
				'text'      => (string) ( $retreat->$field ?? '' ),
				'automatic' => false,
			);
		}

		if ( $locale === self::get_main_locale() ) {
			return $texts;
		}

		$manual  = self::get_manual_translations( $retreat )[ $locale ] ?? array();
		$missing = array();
		foreach ( self::RETREAT_FIELDS as $field ) {
			if ( '' === trim( $texts[ $field ]['text'] ) ) {
				continue;
			}
			if ( ! empty( $manual[ $field ] ) ) {
				$texts[ $field ]['text'] = $manual[ $field ];
			} else {
				$missing[ $field ] = $texts[ $field ]['text'];
			}
		}

		if ( $missing ) {
			$automatic = DFXPRL_Translator::get_instance()->get_cached_retreat_translations( $retreat, $missing, $locale );
			foreach ( $automatic as $field => $text ) {
				$texts[ $field ] = array(
					'text'      => $text,
					'automatic' => true,
				);
			}
		}

		return $texts;
	}

	/**
	 * Render the language selector of the public form pages.
	 *
	 * It is a GET form, so it works without JavaScript. With JavaScript, the script
	 * loads the texts of the chosen language and replaces them without reloading.
	 *
	 * @since 1.15.0
	 * @param string $label_html Safe HTML of the label; by default, "Language".
	 */
	public static function render_selector( $label_html = '' ) {
		$current = self::get_current_locale();
		if ( '' === $label_html ) {
			$label_html = esc_html__( 'Language', 'dfx-parish-retreat-letters' );
		}
		?>
		<form method="get" action="" class="dfxprl-language-selector">
			<label for="dfxprl-lang"><span aria-hidden="true">&#127760;</span> <?php echo $label_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Safe HTML ?></label>
			<select id="dfxprl-lang" name="lang">
				<?php foreach ( self::get_languages() as $locale => $name ) : ?>
					<option value="<?php echo esc_attr( $locale ); ?>" lang="<?php echo esc_attr( str_replace( '_', '-', $locale ) ); ?>" <?php selected( $locale, $current ); ?>><?php echo esc_html( $name ); ?></option>
				<?php endforeach; ?>
			</select>
			<noscript><button type="submit"><?php esc_html_e( 'Change', 'dfx-parish-retreat-letters' ); ?></button></noscript>
		</form>
		<?php
	}
}
