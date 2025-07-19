<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.0.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 * @var DFX_Parish_Retreat_Letters|null
	 */
	private static $instance = null;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      DFX_Parish_Retreat_Letters_I18n    $i18n    Manages plugin internationalization.
	 */
	protected $i18n;

	/**
	 * The database management instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      DFX_Parish_Retreat_Letters_Database    $database    Manages database operations.
	 */
	protected $database;

	/**
	 * The admin interface instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      DFX_Parish_Retreat_Letters_Admin    $admin    Manages admin interface.
	 */
	protected $admin;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Main DFX_Parish_Retreat_Letters Instance.
	 *
	 * Ensures only one instance of DFX_Parish_Retreat_Letters is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return DFX_Parish_Retreat_Letters - Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	private function __construct() {
		if ( defined( 'DFX_PARISH_RETREAT_LETTERS_VERSION' ) ) {
			$this->version = DFX_PARISH_RETREAT_LETTERS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'dfx-parish-retreat-letters';

		$this->load_dependencies();
		$this->set_locale();
		$this->init_database();
		$this->init_admin();
	}

	/**
	 * Prevent cloning of the instance.
	 *
	 * @since 1.0.0
	 */
	private function __clone() {
		// Prevent cloning
	}

	/**
	 * Prevent unserializing of the instance.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		// Prevent unserializing
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - DFX_Parish_Retreat_Letters_I18n. Defines internationalization functionality.
	 * - DFX_Parish_Retreat_Letters_Database. Manages database operations.
	 * - DFX_Parish_Retreat_Letters_Retreat. Handles retreat CRUD operations.
	 * - DFX_Parish_Retreat_Letters_Admin. Manages admin interface.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-i18n.php';

		/**
		 * The class responsible for defining database functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-database.php';

		/**
		 * The class responsible for retreat CRUD operations.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-retreat.php';

		/**
		 * The class responsible for defining admin interface functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-admin.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the DFX_Parish_Retreat_Letters_I18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$this->i18n = new DFX_Parish_Retreat_Letters_I18n();
	}

	/**
	 * Initialize the database management.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_database() {
		$this->database = DFX_Parish_Retreat_Letters_Database::get_instance();
	}

	/**
	 * Initialize the admin interface.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_admin() {
		if ( is_admin() ) {
			$this->admin = DFX_Parish_Retreat_Letters_Admin::get_instance();
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->i18n->load_plugin_textdomain();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get the database instance.
	 *
	 * @since     1.0.0
	 * @return    DFX_Parish_Retreat_Letters_Database    The database instance.
	 */
	public function get_database() {
		return $this->database;
	}
}