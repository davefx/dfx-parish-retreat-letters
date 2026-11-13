<?php
/**
 * Tests for the user manual, linked from every admin page of the plugin.
 *
 * @package DFXPRL
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;

/**
 * There is a manual for each profile, shown in the user's language: it falls back to another
 * variant of the same language and then to English, and exists for every bundled translation.
 */
class UserManualTest extends TestCase {

	/**
	 * Plugin root directory, with trailing slash.
	 *
	 * @var string
	 */
	private $plugin_dir;

	protected function setUp(): void {
		parent::setUp();

		$this->plugin_dir = dirname( __DIR__, 2 ) . '/';
		if ( ! defined( 'DFXPRL_PLUGIN_DIR' ) ) {
			define( 'DFXPRL_PLUGIN_DIR', $this->plugin_dir );
		}
		require_once $this->plugin_dir . 'includes/class-permissions.php';
		require_once $this->plugin_dir . 'includes/class-admin.php';
	}

	/**
	 * Locales of the translations bundled in languages/.
	 *
	 * @return string[]
	 */
	private function get_bundled_locales() {
		$locales = array();
		foreach ( glob( $this->plugin_dir . 'languages/dfx-parish-retreat-letters-*.po' ) as $file ) {
			$locales[] = substr( basename( $file, '.po' ), strlen( 'dfx-parish-retreat-letters-' ) );
		}
		return $locales;
	}

	public function test_there_is_a_manual_per_profile_for_english_and_every_bundled_translation() {
		$locales   = $this->get_bundled_locales();
		$locales[] = 'en_US';
		$this->assertGreaterThanOrEqual( 9, count( $locales ) );
		$this->assertSame( array( 'admin', 'manager', 'message_manager', 'read_only' ), DFXPRL_Admin::get_manual_profiles() );

		foreach ( DFXPRL_Admin::get_manual_profiles() as $profile ) {
			foreach ( $locales as $locale ) {
				$file = $this->plugin_dir . 'manual/' . $profile . '/' . $locale . '.html';
				$this->assertFileExists( $file, "Missing $profile manual for $locale" );
				$this->assertSame( $file, DFXPRL_Admin::get_manual_file( $locale, $profile ) );
			}
		}
	}

	public function test_other_variants_of_a_language_use_the_manual_of_that_language() {
		foreach ( DFXPRL_Admin::get_manual_profiles() as $profile ) {
			$dir = $this->plugin_dir . 'manual/' . $profile . '/';
			$this->assertSame( $dir . 'es_ES.html', DFXPRL_Admin::get_manual_file( 'es_MX', $profile ) );
			$this->assertSame( $dir . 'pt_PT.html', DFXPRL_Admin::get_manual_file( 'pt_BR', $profile ) );
			$this->assertSame( $dir . 'de_DE.html', DFXPRL_Admin::get_manual_file( 'de_DE_formal', $profile ) );
			$this->assertSame( $dir . 'en_US.html', DFXPRL_Admin::get_manual_file( 'en_GB', $profile ) );
		}
	}

	public function test_unknown_or_odd_locales_and_profiles_stay_inside_the_manual_folder() {
		foreach ( array( 'ja', 'nl_NL', '', '../../wp-config', 'es_ES/../../x' ) as $locale ) {
			$file = DFXPRL_Admin::get_manual_file( $locale, 'read_only' );
			$this->assertStringStartsWith( $this->plugin_dir . 'manual/read_only/', $file, "Locale '$locale' must stay inside manual/read_only/" );
			$this->assertFileExists( $file );
		}
		$this->assertSame( $this->plugin_dir . 'manual/read_only/en_US.html', DFXPRL_Admin::get_manual_file( 'ja', 'read_only' ) );

		// An unknown profile gets the complete manual
		foreach ( array( '', 'editor', '../admin', 'read_only/../../x' ) as $profile ) {
			$this->assertSame( $this->plugin_dir . 'manual/admin/es_ES.html', DFXPRL_Admin::get_manual_file( 'es_ES', $profile ) );
		}
		$this->assertSame( $this->plugin_dir . 'manual/admin/es_ES.html', DFXPRL_Admin::get_manual_file( 'es_ES' ) );
	}

	public function test_translations_keep_the_structure_of_the_spanish_manual() {
		$patterns = array( '<h2', '<h3', '<h4', '<table', '<tr', '<img', 'id="', 'href="#' );

		foreach ( DFXPRL_Admin::get_manual_profiles() as $profile ) {
			$dir    = $this->plugin_dir . 'manual/' . $profile . '/';
			$source = file_get_contents( $dir . 'es_ES.html' );

			foreach ( glob( $dir . '*.html' ) as $file ) {
				$name = $profile . '/' . basename( $file );
				$html = file_get_contents( $file );
				foreach ( $patterns as $pattern ) {
					$this->assertSame( substr_count( $source, $pattern ), substr_count( $html, $pattern ), "$name: count of $pattern" );
				}

				// Every internal link points to an anchor of the same manual
				preg_match_all( '/href="#([^"]+)"/', $html, $links );
				foreach ( array_unique( $links[1] ) as $anchor ) {
					$this->assertStringContainsString( 'id="' . $anchor . '"', $html, "$name: anchor #$anchor" );
				}
			}
		}
	}

	public function test_each_user_gets_the_manuals_of_their_profiles() {
		$source = file_get_contents( $this->plugin_dir . 'includes/class-admin.php' );
		$method = substr( $source, strpos( $source, 'public function get_current_user_manual_profiles()' ), 900 );

		// Plugin administrators read every manual; retreat managers also those of the profiles they grant
		$this->assertStringContainsString( "if ( \$this->permissions->current_user_can_manage_plugin() ) {\n\t\t\treturn \$profiles;", $method );
		$this->assertStringContainsString( 'return array_slice( $profiles, 1 );', $method );
		$this->assertStringContainsString( 'array( DFXPRL_Permissions::PERMISSION_READ_ONLY )', $method );

		// The profile in the URL is only honoured if the user can read that manual
		$this->assertStringContainsString( "if ( ! in_array( \$profile, \$profiles, true ) ) {\n\t\t\t\$profile = \$profiles[0];", $source );
		$this->assertStringContainsString( 'public function get_user_permission_levels( $user_id )', file_get_contents( $this->plugin_dir . 'includes/class-permissions.php' ) );
	}

	public function test_the_manual_page_is_registered_and_styled() {
		$source = file_get_contents( $this->plugin_dir . 'includes/class-admin.php' );

		$this->assertContains( 'dfxprl-manual', DFXPRL_Admin::get_plugin_admin_page_slugs() );
		$this->assertStringContainsString( "'dfxprl-manual',\n\t\t\tarray( \$this, 'manual_page' )", $source );
		$this->assertStringContainsString( 'wp_kses_post( $content )', $source );
		$this->assertFileExists( $this->plugin_dir . 'assets/css/admin-manual.css' );
	}

	public function test_the_manual_is_linked_from_every_plugin_page_instead_of_the_menu() {
		$source = file_get_contents( $this->plugin_dir . 'includes/class-admin.php' );

		// Out of the menu: registered with an empty menu title and hidden like the messages page
		$this->assertStringContainsString( "__( 'User Manual', 'dfx-parish-retreat-letters' ),\n\t\t\t'',", $source );
		$this->assertStringContainsString( 'a[href$="page=dfxprl-manual"]', $source );

		// Linked from the header of every page of the plugin
		$this->assertStringContainsString( "add_action( 'in_admin_header', array( \$this, 'render_manual_link' ) );", $source );
		$this->assertStringContainsString( 'public function render_manual_link()', $source );
		$this->assertStringContainsString( '.dfxprl-manual-link', file_get_contents( $this->plugin_dir . 'assets/css/admin-manual.css' ) );
	}

	public function test_screenshots_exist_in_the_language_of_each_manual_and_have_alt_text() {
		$images = $this->plugin_dir . 'manual/images/';
		$spanish = array_map( 'basename', glob( $images . 'es_ES/*.webp' ) );
		$this->assertNotEmpty( $spanish );

		foreach ( DFXPRL_Admin::get_manual_profiles() as $profile ) {
			foreach ( glob( $this->plugin_dir . 'manual/' . $profile . '/*.html' ) as $file ) {
				$locale = basename( $file, '.html' );
				$name   = $profile . '/' . basename( $file );
				$this->assertSame( $spanish, array_map( 'basename', glob( $images . $locale . '/*.webp' ) ), "$name: screenshots of $locale" );

				preg_match_all( '/<img [^>]*>/', file_get_contents( $file ), $tags );
				$this->assertNotEmpty( $tags[0], "$name: no screenshots" );
				foreach ( $tags[0] as $tag ) {
					$this->assertMatchesRegularExpression( '#src="images/' . preg_quote( $locale, '#' ) . '/[a-z-]+\.webp"#', $tag, "$name: $tag" );
					preg_match( '#src="images/([^"]+)"#', $tag, $src );
					$this->assertFileExists( $images . $src[1], "$name: $tag" );
					$this->assertMatchesRegularExpression( '/alt="[^"]{10,}"/', $tag, "$name: alt text of $tag" );
					$this->assertMatchesRegularExpression( '/width="\d+" height="\d+"/', $tag, "$name: size of $tag" );
				}
			}
		}
	}

	public function test_screenshot_paths_point_to_the_plugin_url() {
		if ( ! defined( 'DFXPRL_PLUGIN_URL' ) ) {
			define( 'DFXPRL_PLUGIN_URL', 'https://example.org/wp-content/plugins/dfx-parish-retreat-letters/' );
		}
		$html = DFXPRL_Admin::link_manual_images( '<p>Text with images/ in it</p><a href="images/es_ES/form.webp"><img src="images/es_ES/form.webp" alt="x"></a>' );
		$url  = DFXPRL_PLUGIN_URL . 'manual/images/es_ES/form.webp';
		$this->assertSame( '<p>Text with images/ in it</p><a href="' . $url . '"><img src="' . $url . '" alt="x"></a>', $html );
	}
}
