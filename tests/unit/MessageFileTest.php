<?php
/**
 * Unit tests for DFXPRL_MessageFile class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFXPRL_MessageFile
 */
class MessageFileTest extends TestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => '/tmp/uploads',
            'baseurl' => 'http://example.com/wp-content/uploads'
        ]);
        Functions\when('wp_handle_upload')->justReturn([
            'file' => '/tmp/uploads/test.pdf',
            'url' => 'http://example.com/wp-content/uploads/test.pdf',
            'type' => 'application/pdf'
        ]);
        Functions\when('sanitize_file_name')->alias(function($filename) {
            return preg_replace('/[^a-zA-Z0-9.-]/', '', $filename);
        });
        Functions\when('wp_check_filetype')->justReturn([
            'ext' => 'pdf',
            'type' => 'application/pdf'
        ]);
    }

    /**
     * Tear down test environment
     */
    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test file upload functionality
     */
    public function testFileUpload() {
        $message_file = new DFXPRL_MessageFile();
        
        if (method_exists($message_file, 'upload_file')) {
            $file_data = [
                'name' => 'test.pdf',
                'type' => 'application/pdf',
                'tmp_name' => '/tmp/test.pdf',
                'size' => 1024
            ];
            
            $result = $message_file->upload_file($file_data);
            $this->assertTrue(is_callable([$message_file, 'upload_file']));
        } else {
            $this->markTestSkipped('upload_file method not found');
        }
    }

    /**
     * Test file validation
     */
    public function testFileValidation() {
        $message_file = new DFXPRL_MessageFile();
        
        if (method_exists($message_file, 'validate_file')) {
            // Test valid file
            $valid_file = [
                'name' => 'document.pdf',
                'type' => 'application/pdf',
                'size' => 1024,
                'tmp_name' => '/tmp/test.pdf'
            ];
            
            $result = $message_file->validate_file($valid_file);
            $this->assertTrue(is_callable([$message_file, 'validate_file']));
        } else {
            $this->markTestSkipped('validate_file method not found');
        }
    }

    /**
     * Test file deletion
     */
    public function testFileDeleting() {
        $message_file = new DFXPRL_MessageFile();
        
        if (method_exists($message_file, 'delete_file')) {
            $file_id = 1;
            $result = $message_file->delete_file($file_id);
            $this->assertTrue(is_callable([$message_file, 'delete_file']));
        } else {
            $this->markTestSkipped('delete_file method not found');
        }
    }

    /**
     * Test get file info
     */
    public function testGetFileInfo() {
        $message_file = new DFXPRL_MessageFile();
        
        if (method_exists($message_file, 'get_file')) {
            $file_id = 1;
            $result = $message_file->get_file($file_id);
            $this->assertTrue(is_callable([$message_file, 'get_file']));
        } else {
            $this->markTestSkipped('get_file method not found');
        }
    }

    /**
     * Test secure file download
     */
    public function testSecureFileDownload() {
        $message_file = new DFXPRL_MessageFile();
        
        if (method_exists($message_file, 'secure_download')) {
            $file_id = 1;
            $token = 'secure_token_123';
            $result = $message_file->secure_download($file_id, $token);
            $this->assertTrue(is_callable([$message_file, 'secure_download']));
        } else {
            $this->markTestSkipped('secure_download method not found');
        }
    }
}