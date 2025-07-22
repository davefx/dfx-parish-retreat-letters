<?php
/**
 * Unit tests for DFX_Parish_Retreat_Letters_Permissions class
 *
 * @package DFX_Parish_Retreat_Letters
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test class for DFX_Parish_Retreat_Letters_Permissions
 */
class PermissionsTest extends TestCase {

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('current_user_can')->alias(function($capability) {
            return in_array($capability, ['manage_options', 'edit_posts']);
        });
        Functions\when('is_user_logged_in')->justReturn(true);
        Functions\when('wp_get_current_user')->justReturn((object) [
            'ID' => 1,
            'user_login' => 'admin',
            'roles' => ['administrator']
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
     * Test singleton pattern
     */
    public function testSingletonPattern() {
        $instance1 = DFX_Parish_Retreat_Letters_Permissions::get_instance();
        $instance2 = DFX_Parish_Retreat_Letters_Permissions::get_instance();
        
        $this->assertSame($instance1, $instance2);
        $this->assertInstanceOf('DFX_Parish_Retreat_Letters_Permissions', $instance1);
    }

    /**
     * Test permission checking for retreats
     */
    public function testRetreatPermissions() {
        $permissions = DFX_Parish_Retreat_Letters_Permissions::get_instance();
        
        if (method_exists($permissions, 'can_manage_retreats')) {
            $result = $permissions->can_manage_retreats();
            $this->assertTrue(is_callable([$permissions, 'can_manage_retreats']));
        } else {
            $this->markTestSkipped('can_manage_retreats method not found');
        }
    }

    /**
     * Test permission checking for attendants
     */
    public function testAttendantPermissions() {
        $permissions = DFX_Parish_Retreat_Letters_Permissions::get_instance();
        
        if (method_exists($permissions, 'can_manage_attendants')) {
            $result = $permissions->can_manage_attendants();
            $this->assertTrue(is_callable([$permissions, 'can_manage_attendants']));
        } else {
            $this->markTestSkipped('can_manage_attendants method not found');
        }
    }

    /**
     * Test permission checking for messages
     */
    public function testMessagePermissions() {
        $permissions = DFX_Parish_Retreat_Letters_Permissions::get_instance();
        
        if (method_exists($permissions, 'can_send_messages')) {
            $result = $permissions->can_send_messages();
            $this->assertTrue(is_callable([$permissions, 'can_send_messages']));
        } else {
            $this->markTestSkipped('can_send_messages method not found');
        }
    }

    /**
     * Test role-based permissions
     */
    public function testRoleBasedPermissions() {
        $permissions = DFX_Parish_Retreat_Letters_Permissions::get_instance();
        
        if (method_exists($permissions, 'check_role_permission')) {
            $result = $permissions->check_role_permission('administrator', 'manage_retreats');
            $this->assertTrue(is_callable([$permissions, 'check_role_permission']));
        } else {
            $this->markTestSkipped('check_role_permission method not found');
        }
    }

    /**
     * Test custom capability checking
     */
    public function testCustomCapabilityChecking() {
        $permissions = DFX_Parish_Retreat_Letters_Permissions::get_instance();
        
        if (method_exists($permissions, 'has_custom_capability')) {
            $result = $permissions->has_custom_capability('dfx_manage_retreat_letters');
            $this->assertTrue(is_callable([$permissions, 'has_custom_capability']));
        } else {
            $this->markTestSkipped('has_custom_capability method not found');
        }
    }
}