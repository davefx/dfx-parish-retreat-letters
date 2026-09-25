<?php
/**
 * Unit tests for custom attendant fields and the contact log edit window.
 *
 * @package DFXPRL
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal WP_Error stand-in for tests running without WordPress.
	 */
	class WP_Error {
		private $message;
		public function __construct( $code = '', $message = '' ) {
			$this->message = $message;
		}
		public function get_error_message() {
			return $this->message;
		}
	}
}

/**
 * Test class for DFXPRL_Custom_Field and DFXPRL_Attendant_Log.
 */
class CustomFieldTest extends TestCase {

	/**
	 * Custom field model created without running the constructor (no database needed).
	 *
	 * @var DFXPRL_Custom_Field
	 */
	private $model;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$plugin_dir = dirname( __DIR__, 2 );
		require_once $plugin_dir . '/includes/class-custom-field.php';
		require_once $plugin_dir . '/includes/class-attendant-log.php';

		Functions\when( '__' )->returnArg();
		Functions\when( 'sanitize_text_field' )->alias(
			function( $text ) {
				return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $text ) ) );
			}
		);
		Functions\when( 'sanitize_textarea_field' )->alias(
			function( $text ) {
				return trim( strip_tags( (string) $text ) );
			}
		);
		Functions\when( 'sanitize_key' )->alias(
			function( $key ) {
				return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
			}
		);
		Functions\when( 'remove_accents' )->alias(
			function( $text ) {
				return strtr( $text, array( 'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'Ú' => 'U', 'Í' => 'I' ) );
			}
		);

		$this->model = ( new ReflectionClass( 'DFXPRL_Custom_Field' ) )->newInstanceWithoutConstructor();
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Build a field object.
	 *
	 * @param string $type    Field type.
	 * @param array  $options Options for select fields.
	 * @return object
	 */
	private function field( $type, $options = array() ) {
		return (object) array(
			'name'       => 'Field',
			'field_type' => $type,
			'options'    => $options,
		);
	}

	/**
	 * Call the private sanitize_definition() method.
	 *
	 * @param array $definition Raw definition.
	 * @return array
	 */
	private function sanitize_definition( $definition ) {
		$method = new ReflectionMethod( 'DFXPRL_Custom_Field', 'sanitize_definition' );
		if ( PHP_VERSION_ID < 80100 ) {
			$method->setAccessible( true );
		}
		return $method->invoke( $this->model, $definition );
	}

	public function test_slugify_builds_ascii_underscore_slugs() {
		$this->assertSame( 'numero_de_habitacion', $this->model->slugify( 'Número de habitación' ) );
		$this->assertSame( 'talla_camiseta', $this->model->slugify( '  Talla -- camiseta!! ' ) );
		$this->assertSame( 64, strlen( $this->model->slugify( str_repeat( 'a', 100 ) ) ) );
	}

	public function test_checkbox_values_are_normalized() {
		$field = $this->field( 'checkbox' );
		foreach ( array( '1', 'yes', 'Sí', 'x', 'TRUE', 'on' ) as $truthy ) {
			$this->assertSame( '1', $this->model->normalize_value( $field, $truthy ), $truthy );
		}
		foreach ( array( '', '0', 'no', 'whatever' ) as $falsy ) {
			$this->assertSame( '', $this->model->normalize_value( $field, $falsy ), $falsy );
		}
	}

	public function test_number_values_accept_decimal_comma_and_reject_text() {
		$field = $this->field( 'number' );
		$this->assertSame( '1.5', $this->model->normalize_value( $field, '1,5' ) );
		$this->assertSame( '42', $this->model->normalize_value( $field, ' 42 ' ) );
		$this->assertSame( '', $this->model->normalize_value( $field, '' ) );
		$this->assertInstanceOf( 'WP_Error', $this->model->normalize_value( $field, 'abc' ) );
	}

	public function test_date_values_must_be_valid_iso_dates() {
		$field = $this->field( 'date' );
		$this->assertSame( '2026-09-01', $this->model->normalize_value( $field, '2026-09-01' ) );
		$this->assertInstanceOf( 'WP_Error', $this->model->normalize_value( $field, '2026-02-30' ) );
		$this->assertInstanceOf( 'WP_Error', $this->model->normalize_value( $field, '01/09/2026' ) );
	}

	public function test_select_values_must_match_an_option_case_insensitively() {
		$field = $this->field( 'select', array( 'S', 'M', 'L' ) );
		$this->assertSame( 'M', $this->model->normalize_value( $field, 'm' ) );
		$this->assertInstanceOf( 'WP_Error', $this->model->normalize_value( $field, 'XL' ) );
	}

	public function test_text_values_are_sanitized() {
		$this->assertSame( 'hello world', $this->model->normalize_value( $this->field( 'text' ), " <b>hello</b>\n world " ) );
		$this->assertSame( "line 1\nline 2", $this->model->normalize_value( $this->field( 'textarea' ), "line 1\nline 2" ) );
	}

	public function test_edit_permission_cannot_be_broader_than_view_permission() {
		$data = $this->sanitize_definition( array(
			'name'            => 'Secret',
			'view_permission' => 'manager',
			'edit_permission' => 'message_manager',
		) );
		$this->assertSame( 'manager', $data['edit_permission'] );
	}

	public function test_sortable_requires_show_in_list() {
		$this->assertSame( 0, $this->sanitize_definition( array( 'name' => 'A', 'sortable' => 1 ) )['sortable'] );
		$this->assertSame( 1, $this->sanitize_definition( array( 'name' => 'A', 'sortable' => 1, 'show_in_list' => 1 ) )['sortable'] );
	}

	public function test_definition_type_and_options_are_sanitized() {
		$data = $this->sanitize_definition( array(
			'name'       => 'Size',
			'field_type' => 'select',
			'options'    => "S\r\nM\n\nM\nL ",
		) );
		$this->assertSame( array( 'S', 'M', 'L' ), $data['options'] );

		$this->assertSame( 'text', $this->sanitize_definition( array( 'name' => 'X', 'field_type' => 'bogus' ) )['field_type'] );
	}

	public function test_log_edit_window_is_fifteen_minutes() {
		$log = ( new ReflectionClass( 'DFXPRL_Attendant_Log' ) )->newInstanceWithoutConstructor();

		$recent = (object) array( 'created_at' => gmdate( 'Y-m-d H:i:s', time() - 60 ) );
		$this->assertGreaterThan( 13 * 60, $log->get_edit_seconds_left( $recent ) );
		$this->assertLessThanOrEqual( 14 * 60, $log->get_edit_seconds_left( $recent ) );

		$old = (object) array( 'created_at' => gmdate( 'Y-m-d H:i:s', time() - 16 * 60 ) );
		$this->assertSame( 0, $log->get_edit_seconds_left( $old ) );
	}
}
