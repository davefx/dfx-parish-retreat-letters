<?php
/**
 * Main plugin class
 *
 * @package DFX_Parish_Retreat_Letters
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main DFX Parish Retreat Letters class
 */
class DFX_Parish_Retreat_Letters {

    /**
     * Plugin instance
     *
     * @var DFX_Parish_Retreat_Letters
     */
    private static $instance = null;

    /**
     * Database instance
     *
     * @var DFX_PRL_Database
     */
    public $database;

    /**
     * Admin instance
     *
     * @var DFX_PRL_Admin
     */
    public $admin;

    /**
     * Get instance (singleton pattern)
     *
     * @return DFX_Parish_Retreat_Letters
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        require_once DFX_PRL_PLUGIN_DIR . 'includes/class-retreat.php';
        require_once DFX_PRL_PLUGIN_DIR . 'includes/class-database.php';
        
        // Initialize database
        $this->database = new DFX_PRL_Database();

        // Load admin only in admin area
        if ( is_admin() ) {
            require_once DFX_PRL_PLUGIN_DIR . 'includes/class-admin.php';
            $this->admin = new DFX_PRL_Admin();
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain( 
            'dfx-parish-retreat-letters',
            false,
            dirname( DFX_PRL_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Plugin initialization logic here
        do_action( 'dfx_prl_init' );
    }

    /**
     * Plugin activation
     */
    public static function activate() {
        // Create database tables
        require_once DFX_PRL_PLUGIN_DIR . 'includes/class-database.php';
        $database = new DFX_PRL_Database();
        $database->create_tables();

        // Set default options
        add_option( 'dfx_prl_version', DFX_PRL_VERSION );
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function get_version() {
        return DFX_PRL_VERSION;
    }
}