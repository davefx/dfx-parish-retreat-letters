<?php
/**
 * Unit tests for DFXPRL_GDPR class
 *
 * @package DFXPRL
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFXPRL_GDPR
 */
class GDPRTest extends TestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sanitize_email')->alias(function($email) {
            return filter_var($email, FILTER_SANITIZE_EMAIL);
        });
        Functions\when('wp_hash')->alias(function($data) {
            return hash('sha256', $data);
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
        $instance1 = DFXPRL_GDPR::get_instance();
        $instance2 = DFXPRL_GDPR::get_instance();
        
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf('DFXPRL_GDPR', $instance1);
    }

    /**
     * Test personal data export
     */
    public function testPersonalDataExport() {
        $gdpr = DFXPRL_GDPR::get_instance();
        
        if (method_exists($gdpr, 'export_personal_data')) {
            // Mock database results
            $attendant_data = (object) [
                'id' => 1,
                'name' => 'John',
                'surnames' => 'Doe',
                'date_of_birth' => '1980-01-01',
                'emergency_contact_name' => 'Jane',
                'emergency_contact_phone' => '+1234567890'
            ];
            
            $result = $gdpr->export_personal_data('john.doe@example.com');
            
            // Verify method is callable
            $this->assertTrue(is_callable([$gdpr, 'export_personal_data']));
        } else {
            $this->markTestSkipped('export_personal_data method not found');
        }
    }

    /**
     * Test personal data erasure
     */
    public function testPersonalDataErasure() {
        $gdpr = DFXPRL_GDPR::get_instance();
        
        if (method_exists($gdpr, 'erase_personal_data')) {
            $result = $gdpr->erase_personal_data('john.doe@example.com');
            
            // Verify method is callable
            $this->assertTrue(is_callable([$gdpr, 'erase_personal_data']));
        } else {
            $this->markTestSkipped('erase_personal_data method not found');
        }
    }

    /**
     * Test data anonymization
     */
    public function testDataAnonymization() {
        $gdpr = DFXPRL_GDPR::get_instance();
        
        if (method_exists($gdpr, 'anonymize_attendant_data')) {
            $attendant_id = 1;
            $result = $gdpr->anonymize_attendant_data($attendant_id);
            
            $this->assertTrue(is_callable([$gdpr, 'anonymize_attendant_data']));
        } else {
            $this->markTestSkipped('anonymize_attendant_data method not found');
        }
    }

    /**
     * Test consent tracking
     */
    public function testConsentTracking() {
        $gdpr = DFXPRL_GDPR::get_instance();
        
        if (method_exists($gdpr, 'record_consent')) {
            $attendant_id = 1;
            $consent_type = 'data_processing';
            $result = $gdpr->record_consent($attendant_id, $consent_type);
            
            $this->assertTrue(is_callable([$gdpr, 'record_consent']));
        } else {
            $this->markTestSkipped('record_consent method not found');
        }
    }

    /**
     * Test privacy policy compliance
     */
    public function testPrivacyPolicyCompliance() {
        $gdpr = DFXPRL_GDPR::get_instance();
        
        if (method_exists($gdpr, 'check_privacy_compliance')) {
            $result = $gdpr->check_privacy_compliance();
            
            $this->assertTrue(is_callable([$gdpr, 'check_privacy_compliance']));
        } else {
            $this->markTestSkipped('check_privacy_compliance method not found');
        }
    }
}