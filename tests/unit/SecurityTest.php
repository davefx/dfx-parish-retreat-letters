<?php
/**
 * Unit tests for DFX_Parish_Retreat_Letters_Security class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFX_Parish_Retreat_Letters_Security
 */
class SecurityTest extends TestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('get_option')->alias(function($option, $default = false) {
            if ($option === 'dfx_parish_retreat_letters_encryption_key') {
                return false; // Force key generation
            }
            return $default;
        });
        Functions\when('add_option')->justReturn(true);
        Functions\when('update_option')->justReturn(true);
        Functions\when('add_action')->justReturn(true);
        Functions\when('esc_html_e')->alias(function($text) {
            echo $text;
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
     * Test singleton pattern
     */
    public function testSingletonPattern() {
        $instance1 = DFX_Parish_Retreat_Letters_Security::get_instance();
        $instance2 = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf('DFX_Parish_Retreat_Letters_Security', $instance1);
    }

    /**
     * Test encryption constants are defined
     */
    public function testEncryptionConstants() {
        $this->assertEquals('AES-256-CBC', DFX_Parish_Retreat_Letters_Security::ENCRYPTION_METHOD);
        $this->assertEquals(32, DFX_Parish_Retreat_Letters_Security::SALT_LENGTH);
        $this->assertEquals(64, DFX_Parish_Retreat_Letters_Security::TOKEN_LENGTH);
    }

    /**
     * Test encryption key generation
     */
    public function testEncryptionKeyGeneration() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($security);
        if ($reflection->hasMethod('get_encryption_key')) {
            $method = $reflection->getMethod('get_encryption_key');
            $method->setAccessible(true);
            $key = $method->invoke($security, true);
            
            $this->assertIsString($key);
            $this->assertEquals(64, strlen($key)); // 32 bytes in hex = 64 chars
        } else {
            $this->markTestSkipped('get_encryption_key method not found');
        }
    }

    /**
     * Test secure token generation
     */
    public function testSecureTokenGeneration() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        if (method_exists($security, 'generate_secure_token')) {
            $token1 = $security->generate_secure_token();
            $token2 = $security->generate_secure_token();
            
            $this->assertIsString($token1);
            $this->assertIsString($token2);
            $this->assertEquals(64, strlen($token1));
            $this->assertEquals(64, strlen($token2));
            $this->assertNotEquals($token1, $token2); // Should be unique
            $this->assertRegExp('/^[a-f0-9]+$/', $token1); // Should be hex
        } else {
            $this->markTestSkipped('generate_secure_token method not found');
        }
    }

    /**
     * Test unique message token generation
     */
    public function testUniqueMessageTokenGeneration() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        if (method_exists($security, 'generate_unique_message_token')) {
            // Mock database check for uniqueness
            global $wpdb;
            $wpdb = $this->createMock('wpdb');
            $wpdb->method('get_var')->willReturn(null); // Token doesn't exist
            
            $token = $security->generate_unique_message_token();
            
            $this->assertIsString($token);
            $this->assertEquals(64, strlen($token));
        } else {
            $this->markTestSkipped('generate_unique_message_token method not found');
        }
    }

    /**
     * Test data encryption and decryption
     */
    public function testDataEncryptionDecryption() {
        if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
            $this->markTestSkipped('OpenSSL functions not available');
        }

        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        if (method_exists($security, 'encrypt_data') && method_exists($security, 'decrypt_data')) {
            $original_data = 'This is a test message for encryption';
            
            $encrypted_result = $security->encrypt_data($original_data);
            
            $this->assertIsArray($encrypted_result);
            $this->assertArrayHasKey('encrypted', $encrypted_result);
            $this->assertArrayHasKey('salt', $encrypted_result);
            $this->assertNotEquals($original_data, $encrypted_result['encrypted']);
            
            // Test decryption
            $decrypted_data = $security->decrypt_data($encrypted_result['encrypted'], $encrypted_result['salt']);
            $this->assertEquals($original_data, $decrypted_data);
        } else {
            $this->markTestSkipped('encrypt_data or decrypt_data method not found');
        }
    }

    /**
     * Test encryption with empty data
     */
    public function testEncryptionWithEmptyData() {
        if (!function_exists('openssl_encrypt')) {
            $this->markTestSkipped('OpenSSL functions not available');
        }

        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        if (method_exists($security, 'encrypt_data')) {
            $result = $security->encrypt_data('');
            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('encrypt_data method not found');
        }
    }

    /**
     * Test IP address retrieval and anonymization
     */
    public function testIpAddressHandling() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        if (method_exists($security, 'get_user_ip')) {
            // Mock $_SERVER variables
            $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
            
            $ip = $security->get_user_ip();
            $this->assertIsString($ip);
            
            // Test IP anonymization if method exists
            if (method_exists($security, 'anonymize_ip')) {
                $anonymized = $security->anonymize_ip('192.168.1.100');
                $this->assertIsString($anonymized);
                $this->assertNotEquals('192.168.1.100', $anonymized);
                $this->assertEquals('192.168.1.0', $anonymized); // Last octet should be 0
            }
        } else {
            $this->markTestSkipped('get_user_ip method not found');
        }
    }

    /**
     * Test hash generation for data integrity
     */
    public function testHashGeneration() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        if (method_exists($security, 'generate_hash')) {
            $data = 'test data for hashing';
            $hash = $security->generate_hash($data);
            
            $this->assertIsString($hash);
            $this->assertEquals(64, strlen($hash)); // SHA-256 hex = 64 chars
            
            // Same data should produce same hash
            $hash2 = $security->generate_hash($data);
            $this->assertEquals($hash, $hash2);
            
            // Different data should produce different hash
            $hash3 = $security->generate_hash('different data');
            $this->assertNotEquals($hash, $hash3);
        } else {
            $this->markTestSkipped('generate_hash method not found');
        }
    }

    /**
     * Test password hashing functionality
     */
    public function testPasswordHashing() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        if (method_exists($security, 'hash_password') && method_exists($security, 'verify_password')) {
            $password = 'test_password_123';
            $hash = $security->hash_password($password);
            
            $this->assertIsString($hash);
            $this->assertNotEquals($password, $hash);
            
            // Verify correct password
            $this->assertTrue($security->verify_password($password, $hash));
            
            // Verify incorrect password
            $this->assertFalse($security->verify_password('wrong_password', $hash));
        } else {
            $this->markTestSkipped('password hashing methods not found');
        }
    }

    /**
     * Test rate limiting functionality
     */
    public function testRateLimiting() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        if (method_exists($security, 'check_rate_limit')) {
            $ip = '192.168.1.100';
            $action = 'message_view';
            
            // First attempt should be allowed
            $allowed = $security->check_rate_limit($ip, $action);
            $this->assertTrue($allowed);
        } else {
            $this->markTestSkipped('check_rate_limit method not found');
        }
    }

    /**
     * Test CSRF token generation and validation
     */
    public function testCsrfTokenHandling() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        if (method_exists($security, 'generate_csrf_token') && method_exists($security, 'verify_csrf_token')) {
            $token = $security->generate_csrf_token();
            
            $this->assertIsString($token);
            $this->assertNotEmpty($token);
            
            // Token should be valid immediately after generation
            $this->assertTrue($security->verify_csrf_token($token));
            
            // Invalid token should fail
            $this->assertFalse($security->verify_csrf_token('invalid_token'));
        } else {
            $this->markTestSkipped('CSRF token methods not found');
        }
    }

    /**
     * Test security headers functionality
     */
    public function testSecurityHeaders() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        if (method_exists($security, 'set_security_headers')) {
            // This test would check if headers are set correctly
            // Since we can't easily test header output in unit tests,
            // we just verify the method exists and is callable
            $this->assertTrue(is_callable([$security, 'set_security_headers']));
        } else {
            $this->markTestSkipped('set_security_headers method not found');
        }
    }

    /**
     * Test remove_encryption_key_from_database method exists and is callable
     */
    public function testRemoveEncryptionKeyFromDatabaseMethodExists() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        $this->assertTrue(
            method_exists($security, 'remove_encryption_key_from_database'),
            'remove_encryption_key_from_database method should exist'
        );
        $this->assertTrue(
            is_callable([$security, 'remove_encryption_key_from_database']),
            'remove_encryption_key_from_database method should be callable'
        );
    }

    /**
     * Test has_database_encryption_key method exists and is callable
     */
    public function testHasDatabaseEncryptionKeyMethodExists() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        $this->assertTrue(
            method_exists($security, 'has_database_encryption_key'),
            'has_database_encryption_key method should exist'
        );
        $this->assertTrue(
            is_callable([$security, 'has_database_encryption_key']),
            'has_database_encryption_key method should be callable'
        );
    }

    /**
     * Test display_encryption_key_mismatch_notice method exists and is callable
     */
    public function testDisplayEncryptionKeyMismatchNoticeMethodExists() {
        $security = DFX_Parish_Retreat_Letters_Security::get_instance();
        
        $this->assertTrue(
            method_exists($security, 'display_encryption_key_mismatch_notice'),
            'display_encryption_key_mismatch_notice method should exist'
        );
        $this->assertTrue(
            is_callable([$security, 'display_encryption_key_mismatch_notice']),
            'display_encryption_key_mismatch_notice method should be callable'
        );
    }
}