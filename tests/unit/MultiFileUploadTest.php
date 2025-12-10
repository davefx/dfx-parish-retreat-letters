<?php
/**
 * Unit tests for multi-file upload handling
 *
 * Tests that file upload validation errors don't terminate script execution,
 * allowing subsequent files in a batch to be processed.
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for multi-file upload scenarios
 */
class MultiFileUploadTest extends TestCase {

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Include required plugin class files
		$plugin_dir = dirname(__DIR__, 2);
		if (!class_exists('DFXPRL_Security')) {
			require_once $plugin_dir . '/includes/class-security.php';
		}

		// Mock WordPress functions
		Functions\when('sanitize_file_name')->alias(function($filename) {
			return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
		});
		Functions\when('sanitize_text_field')->alias(function($text) {
			return strip_tags($text);
		});
		Functions\when('wp_delete_file')->alias(function($file) {
			return @unlink($file);
		});
	}

	/**
	 * Tear down test environment
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test that validation errors use E_USER_WARNING instead of E_USER_ERROR
	 * 
	 * This ensures that when one file fails validation in a batch upload,
	 * the script continues processing remaining files instead of terminating.
	 */
	public function testValidationErrorsDoNotTerminateExecution() {
		// Create temporary test files
		$temp_files = [];
		$temp_dir = sys_get_temp_dir();
		
		// Valid image file
		$valid_file = $temp_dir . '/test_valid.jpg';
		file_put_contents($valid_file, 'fake image content');
		$temp_files[] = $valid_file;
		
		// Invalid file (will fail MIME validation)
		$invalid_file = $temp_dir . '/test_invalid.jpg';
		file_put_contents($invalid_file, 'this is not an image');
		$temp_files[] = $invalid_file;
		
		// Another valid image
		$valid_file2 = $temp_dir . '/test_valid2.jpg';
		file_put_contents($valid_file2, 'fake image content 2');
		$temp_files[] = $valid_file2;

		// Test that we can process multiple files even when one fails
		$security = DFXPRL_Security::get_instance();
		$results = [];
		$script_terminated = false;

		// Set error handler to catch E_USER_WARNING (but not E_USER_ERROR)
		set_error_handler(function($errno, $errstr) use (&$script_terminated) {
			if ($errno === E_USER_ERROR) {
				$script_terminated = true;
				return false; // Let PHP handle it (will terminate)
			}
			// E_USER_WARNING - just log it
			return true; // Suppress the warning for clean test output
		});

		// Simulate processing multiple files
		for ($i = 0; $i < count($temp_files); $i++) {
			$file_data = [
				'name' => basename($temp_files[$i]),
				'tmp_name' => $temp_files[$i],
				'size' => filesize($temp_files[$i]),
				'type' => 'image/jpeg',
				'error' => UPLOAD_ERR_OK
			];

			$result = $security->validate_file_upload($file_data);
			$results[] = $result !== false;
			
			// If script would have terminated with E_USER_ERROR, we wouldn't reach here
			if ($script_terminated) {
				break;
			}
		}

		restore_error_handler();

		// Clean up temp files
		foreach ($temp_files as $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}

		// Assertions
		$this->assertFalse($script_terminated, 'Script should not terminate on validation error');
		$this->assertCount(3, $results, 'All three files should be processed');
		
		// Even though middle file fails, we should process all files
		// (results array should have 3 entries, even if one is false)
	}

	/**
	 * Test that E_USER_WARNING is used in validate_file_upload
	 * 
	 * Verifies the specific error level used when validation fails.
	 */
	public function testValidationUsesWarningNotError() {
		// Create a file that will fail MIME validation
		$temp_dir = sys_get_temp_dir();
		$test_file = $temp_dir . '/test_wrong_mime.jpg';
		file_put_contents($test_file, 'not a real jpeg image');

		$file_data = [
			'name' => 'test.jpg',
			'tmp_name' => $test_file,
			'size' => filesize($test_file),
			'type' => 'image/jpeg',
			'error' => UPLOAD_ERR_OK
		];

		$security = DFXPRL_Security::get_instance();
		
		$error_level = null;
		set_error_handler(function($errno, $errstr) use (&$error_level) {
			$error_level = $errno;
			return true; // Suppress error output
		});

		$result = $security->validate_file_upload($file_data);

		restore_error_handler();

		// Clean up
		if (file_exists($test_file)) {
			unlink($test_file);
		}

		// Assertions
		$this->assertFalse($result, 'Validation should fail for invalid MIME type');
		
		// This is the critical test - should be E_USER_WARNING not E_USER_ERROR
		if ($error_level !== null) {
			$this->assertEquals(E_USER_WARNING, $error_level, 
				'Validation errors should use E_USER_WARNING to allow script continuation');
			$this->assertNotEquals(E_USER_ERROR, $error_level,
				'Validation should NOT use E_USER_ERROR which terminates execution');
		}
	}

	/**
	 * Test that file upload loop continues after validation failure
	 * 
	 * Simulates the actual loop in handle_file_uploads method.
	 */
	public function testFileUploadLoopContinuesAfterFailure() {
		$temp_dir = sys_get_temp_dir();
		$files_processed = [];
		$files_skipped = [];

		// Simulate FILES array with multiple files
		$test_files = [
			['name' => 'valid1.txt', 'content' => 'test content 1'],
			['name' => 'invalid.jpg', 'content' => 'not an image'],
			['name' => 'valid2.txt', 'content' => 'test content 2'],
		];

		$security = DFXPRL_Security::get_instance();

		// Suppress error output during test
		set_error_handler(function() { return true; });

		foreach ($test_files as $file_info) {
			$temp_file = $temp_dir . '/' . $file_info['name'];
			file_put_contents($temp_file, $file_info['content']);

			$file_data = [
				'name' => $file_info['name'],
				'tmp_name' => $temp_file,
				'size' => strlen($file_info['content']),
				'type' => strpos($file_info['name'], '.jpg') !== false ? 'image/jpeg' : 'text/plain',
				'error' => UPLOAD_ERR_OK
			];

			$result = $security->validate_file_upload($file_data);
			
			if ($result !== false) {
				$files_processed[] = $file_info['name'];
			} else {
				$files_skipped[] = $file_info['name'];
			}

			// Clean up temp file
			if (file_exists($temp_file)) {
				unlink($temp_file);
			}
		}

		restore_error_handler();

		// Assertions - the key test is that we processed ALL files, not just up to the failure
		$total_handled = count($files_processed) + count($files_skipped);
		$this->assertEquals(3, $total_handled, 
			'All 3 files should be handled (processed or skipped), proving loop continues after validation failure');
		
		// We should have 2 valid text files and 1 skipped invalid jpg
		$this->assertGreaterThanOrEqual(1, count($files_skipped), 
			'At least one file should be skipped due to validation failure');
		$this->assertGreaterThanOrEqual(2, count($files_processed),
			'At least two valid files should be processed');
	}
}
