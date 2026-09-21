<?php
/**
 * Test fixture standing in for WordPress's wp-admin/includes/upgrade.php.
 *
 * DFXPRL_Database::setup_tables() does `require_once ABSPATH . 'wp-admin/includes/upgrade.php'`
 * to load dbDelta(). In the Brain Monkey suite ABSPATH points at tests/fixtures/, so this file
 * satisfies that require. dbDelta() is only defined if a test has not already mocked it.
 *
 * @package DFXPRL
 */

if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( $queries = '', $execute = true ) {
		return array();
	}
}
