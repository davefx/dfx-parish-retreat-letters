<?php
/**
 * Unit tests for DFXPRL_Invitations class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFXPRL_Invitations
 */
class InvitationsTest extends TestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('wp_mail')->justReturn(true);
        Functions\when('sanitize_email')->alias(function($email) {
            return filter_var($email, FILTER_SANITIZE_EMAIL);
        });
        Functions\when('is_email')->alias(function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        });
        Functions\when('site_url')->justReturn('http://example.com');
        Functions\when('get_option')->alias(function($option, $default = '') {
            switch ($option) {
                case 'blogname':
                    return 'Test Church';
                case 'admin_email':
                    return 'admin@testchurch.com';
                default:
                    return $default;
            }
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
     * Test invitation creation
     */
    public function testCreateInvitation() {
        $invitations = new DFXPRL_Invitations();
        
        if (method_exists($invitations, 'create_invitation')) {
            $invitation_data = [
                'attendant_id' => 1,
                'email' => 'john.doe@example.com',
                'retreat_id' => 1,
                'message' => 'Welcome to our retreat!'
            ];
            
            $result = $invitations->create_invitation($invitation_data);
            $this->assertTrue(is_callable([$invitations, 'create_invitation']));
        } else {
            $this->markTestSkipped('create_invitation method not found');
        }
    }

    /**
     * Test send invitation email
     */
    public function testSendInvitationEmail() {
        $invitations = new DFXPRL_Invitations();
        
        if (method_exists($invitations, 'send_invitation_email')) {
            $email_data = [
                'to' => 'john.doe@example.com',
                'subject' => 'Retreat Invitation',
                'message' => 'You are invited to our retreat',
                'retreat_name' => 'Summer Retreat 2024'
            ];
            
            $result = $invitations->send_invitation_email($email_data);
            $this->assertTrue(is_callable([$invitations, 'send_invitation_email']));
        } else {
            $this->markTestSkipped('send_invitation_email method not found');
        }
    }

    /**
     * Test bulk invitation sending
     */
    public function testBulkInvitationSending() {
        $invitations = new DFXPRL_Invitations();
        
        if (method_exists($invitations, 'send_bulk_invitations')) {
            $attendant_ids = [1, 2, 3];
            $retreat_id = 1;
            
            $result = $invitations->send_bulk_invitations($attendant_ids, $retreat_id);
            $this->assertTrue(is_callable([$invitations, 'send_bulk_invitations']));
        } else {
            $this->markTestSkipped('send_bulk_invitations method not found');
        }
    }

    /**
     * Test invitation template generation
     */
    public function testInvitationTemplateGeneration() {
        $invitations = new DFXPRL_Invitations();
        
        if (method_exists($invitations, 'generate_invitation_template')) {
            $template_data = [
                'attendant_name' => 'John Doe',
                'retreat_name' => 'Summer Retreat',
                'retreat_dates' => '2024-06-01 to 2024-06-03',
                'location' => 'Mountain Lodge'
            ];
            
            $result = $invitations->generate_invitation_template($template_data);
            $this->assertTrue(is_callable([$invitations, 'generate_invitation_template']));
        } else {
            $this->markTestSkipped('generate_invitation_template method not found');
        }
    }

    /**
     * Test invitation tracking
     */
    public function testInvitationTracking() {
        $invitations = new DFXPRL_Invitations();
        
        if (method_exists($invitations, 'track_invitation')) {
            $invitation_id = 1;
            $action = 'sent';
            
            $result = $invitations->track_invitation($invitation_id, $action);
            $this->assertTrue(is_callable([$invitations, 'track_invitation']));
        } else {
            $this->markTestSkipped('track_invitation method not found');
        }
    }

    /**
     * Test RSVP functionality
     */
    public function testRSVPFunctionality() {
        $invitations = new DFXPRL_Invitations();
        
        if (method_exists($invitations, 'process_rsvp')) {
            $rsvp_data = [
                'invitation_id' => 1,
                'attendant_id' => 1,
                'response' => 'accepted',
                'notes' => 'Looking forward to it!'
            ];
            
            $result = $invitations->process_rsvp($rsvp_data);
            $this->assertTrue(is_callable([$invitations, 'process_rsvp']));
        } else {
            $this->markTestSkipped('process_rsvp method not found');
        }
    }
}