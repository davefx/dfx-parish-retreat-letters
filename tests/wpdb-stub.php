<?php
/**
 * Minimal stub of the WordPress $wpdb class for Brain Monkey unit tests.
 *
 * WordPress is not loaded in the Brain Monkey suite, so the real wpdb class is absent.
 * PHPUnit's createMock('wpdb') and the plugin code both need the class to exist; tests
 * mock the methods they care about, so the bodies here are only sensible defaults.
 *
 * @package DFXPRL
 */

if ( ! class_exists( 'wpdb' ) ) {
	class wpdb {
		/** @var string Table name prefix. */
		public $prefix = 'wp_';

		/** @var string Users table name. */
		public $users = 'wp_users';

		/** @var string Options table name. */
		public $options = 'wp_options';

		/** @var int ID generated for an AUTO_INCREMENT column by the last INSERT. */
		public $insert_id = 0;

		/** @var int Number of rows returned/affected by the last query. */
		public $num_rows = 0;

		/** @var string Last error message. */
		public $last_error = '';

		/** @var string Last executed query. */
		public $last_query = '';

		public function prepare( $query, ...$args ) {
			return $query;
		}

		public function query( $query ) {
			return 0;
		}

		public function get_var( $query = null, $x = 0, $y = 0 ) {
			return null;
		}

		public function get_row( $query = null, $output = OBJECT, $y = 0 ) {
			return null;
		}

		public function get_col( $query = null, $x = 0 ) {
			return array();
		}

		public function get_results( $query = null, $output = OBJECT ) {
			return array();
		}

		public function insert( $table, $data, $format = null ) {
			return 1;
		}

		public function update( $table, $data, $where, $format = null, $where_format = null ) {
			return 1;
		}

		public function delete( $table, $where, $where_format = null ) {
			return 1;
		}

		public function esc_like( $text ) {
			return addcslashes( (string) $text, '_%\\' );
		}

		public function get_charset_collate() {
			return '';
		}
	}
}

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}
