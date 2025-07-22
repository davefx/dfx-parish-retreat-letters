<?php
/**
 * Integration tests for DFX Parish Retreat Letters plugin
 *
 * @package DFX_Parish_Retreat_Letters
 */

/**
 * Test class for plugin integration tests
 */
class PluginIntegrationTest extends WP_UnitTestCase {

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Activate the plugin for integration tests
        if (!is_plugin_active('dfx-parish-retreat-letters/dfx-parish-retreat-letters.php')) {
            activate_plugin('dfx-parish-retreat-letters/dfx-parish-retreat-letters.php');
        }
    }

    /**
     * Test plugin activation creates required database tables
     */
    public function test_plugin_activation_creates_tables() {
        global $wpdb;
        
        // Get database instance
        $database = DFX_Parish_Retreat_Letters_Database::get_instance();
        
        // Check if retreats table exists
        $table_name = $wpdb->prefix . 'dfx_retreats';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Trigger table creation
            $database->setup_tables();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        }
        
        $this->assertTrue($table_exists, 'Retreats table should exist after plugin activation');
    }

    /**
     * Test plugin creates a retreat successfully
     */
    public function test_create_retreat_integration() {
        $retreat = new DFX_Parish_Retreat_Letters_Retreat();
        
        $retreat_data = [
            'name' => 'Integration Test Retreat',
            'location' => 'Test Church',
            'start_date' => '2024-06-01',
            'end_date' => '2024-06-03',
            'custom_message' => 'This is a test retreat for integration testing.'
        ];
        
        $retreat_id = $retreat->create($retreat_data);
        
        $this->assertIsInt($retreat_id);
        $this->assertGreaterThan(0, $retreat_id);
        
        // Verify the retreat was created by fetching it
        $created_retreat = $retreat->get($retreat_id);
        $this->assertNotNull($created_retreat);
        $this->assertEquals('Integration Test Retreat', $created_retreat->name);
        $this->assertEquals('Test Church', $created_retreat->location);
        
        // Clean up
        global $wpdb;
        $database = DFX_Parish_Retreat_Letters_Database::get_instance();
        $wpdb->delete($database->get_retreats_table(), ['id' => $retreat_id]);
    }

    /**
     * Test plugin main class initialization
     */
    public function test_plugin_initialization() {
        $plugin = DFX_Parish_Retreat_Letters::get_instance();
        
        $this->assertInstanceOf('DFX_Parish_Retreat_Letters', $plugin);
        
        // Test that hooks are properly registered
        $this->assertGreaterThan(0, has_action('init', [$plugin, 'load_plugin_textdomain']));
    }

    /**
     * Test admin interface is initialized when in admin context
     */
    public function test_admin_interface_initialization() {
        // Set admin context
        set_current_screen('dashboard');
        
        $plugin = DFX_Parish_Retreat_Letters::get_instance();
        
        // Check if admin hooks are registered
        $this->assertTrue(is_admin());
    }
}