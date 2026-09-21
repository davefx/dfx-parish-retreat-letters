<?php
/**
 * Regression tests for CSV import date parsing.
 *
 * Ensures dates written with a single-digit day or month (e.g. "19/6/1946")
 * are imported correctly, while genuinely invalid dates are rejected.
 *
 * @package DFXPRL
 */

use PHPUnit\Framework\TestCase;

/**
 * Test class for DFXPRL_Admin date parsing used during CSV attendant import.
 */
class DateImportTest extends TestCase {

	/**
	 * Admin instance created without running the constructor.
	 *
	 * @var DFXPRL_Admin
	 */
	private $admin;

	/**
	 * Accessible reflection of the private parse_flexible_date() method.
	 *
	 * @var ReflectionMethod
	 */
	private $parse_method;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		require_once dirname( __DIR__, 2 ) . '/includes/class-admin.php';

		$reflection         = new ReflectionClass( 'DFXPRL_Admin' );
		$this->admin        = $reflection->newInstanceWithoutConstructor();
		$this->parse_method = $reflection->getMethod( 'parse_flexible_date' );

		// setAccessible() is a no-op (and deprecated) on PHP 8.1+, where private
		// methods are reachable through reflection by default; call it only on older runtimes.
		if ( PHP_VERSION_ID < 80100 ) {
			$this->parse_method->setAccessible( true );
		}
	}

	/**
	 * Parse a date string through the private method.
	 *
	 * @param string $date_string Date string to parse.
	 * @return string|false Parsed Y-m-d date or false.
	 */
	private function parse( $date_string ) {
		return $this->parse_method->invoke( $this->admin, $date_string );
	}

	/**
	 * Dates with single-digit day or month must parse correctly.
	 *
	 * These are the exact values that previously failed on lines 2-5 of a
	 * real attendant CSV, because the strict format round-trip rejected any
	 * value whose day or month lacked a leading zero.
	 *
	 * @dataProvider singleDigitDateProvider
	 *
	 * @param string $input    Raw date from the CSV.
	 * @param string $expected Expected normalized Y-m-d value.
	 */
	public function test_single_digit_dates_are_parsed( $input, $expected ) {
		$this->assertSame( $expected, $this->parse( $input ) );
	}

	/**
	 * Data provider of single-digit day/month dates.
	 *
	 * @return array<string, array{0:string,1:string}>
	 */
	public function singleDigitDateProvider() {
		return array(
			'single-digit month'         => array( '19/6/1946', '1946-06-19' ),
			'single-digit month (Aug)'   => array( '19/8/1988', '1988-08-19' ),
			'single-digit month (Jul)'   => array( '16/7/1982', '1982-07-16' ),
			'single-digit month (Apr)'   => array( '24/4/1980', '1980-04-24' ),
			'single-digit day and month' => array( '1/2/1990', '1990-02-01' ),
		);
	}

	/**
	 * Two-digit and ISO dates must keep working.
	 *
	 * @dataProvider validDateProvider
	 *
	 * @param string $input    Raw date.
	 * @param string $expected Expected normalized Y-m-d value.
	 */
	public function test_standard_dates_still_parse( $input, $expected ) {
		$this->assertSame( $expected, $this->parse( $input ) );
	}

	/**
	 * Data provider of already-supported date formats.
	 *
	 * @return array<string, array{0:string,1:string}>
	 */
	public function validDateProvider() {
		return array(
			'iso'                => array( '1980-01-01', '1980-01-01' ),
			'dmy two digit'      => array( '15/12/2020', '2020-12-15' ),
			'unambiguous day'    => array( '31/12/1999', '1999-12-31' ),
		);
	}

	/**
	 * Invalid or out-of-range dates must still be rejected.
	 *
	 * @dataProvider invalidDateProvider
	 *
	 * @param string $input Raw date.
	 */
	public function test_invalid_dates_are_rejected( $input ) {
		$this->assertFalse( $this->parse( $input ) );
	}

	/**
	 * Data provider of values that must not parse.
	 *
	 * @return array<string, array{0:string}>
	 */
	public function invalidDateProvider() {
		return array(
			'day and month out of range' => array( '32/13/2020' ),
			'month out of range'         => array( '19/13/1946' ),
			'not a date'                 => array( 'not-a-date' ),
			'empty'                      => array( '' ),
		);
	}
}
