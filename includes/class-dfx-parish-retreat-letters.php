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
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
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
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 * @author     DaveFX
 */
class DFXPRL {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 * @var DFXPRL|null
	 */
	private static $instance = null;

	/**
	 * The database management instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      DFXPRL_Database    $database    Manages database operations.
	 */
	protected $database;

	/**
	 * The admin interface instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      DFXPRL_Admin    $admin    Manages admin interface.
	 */
	protected $admin;

	/**
	 * The security instance.
	 *
	 * @since    1.2.0
	 * @access   protected
	 * @var      DFXPRL_Security    $security    Manages security operations.
	 */
	protected $security;

	/**
	 * The GDPR compliance instance.
	 *
	 * @since    1.2.0
	 * @access   protected
	 * @var      DFXPRL_GDPR    $gdpr    Manages GDPR compliance.
	 */
	protected $gdpr;

	/**
	 * The permissions management instance.
	 *
	 * @since    1.3.0
	 * @access   protected
	 * @var      DFXPRL_Permissions    $permissions    Manages permissions system.
	 */
	protected $permissions;

	/**
	 * The invitations management instance.
	 *
	 * @since    1.3.0
	 * @access   protected
	 * @var      DFXPRL_Invitations    $invitations    Manages invitation system.
	 */
	protected $invitations;

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
	 * Main DFXPRL Instance.
	 *
	 * Ensures only one instance of DFXPRL is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @return DFXPRL - Main instance.
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
		if ( defined( 'DFXPRL_VERSION' ) ) {
			$this->version = DFXPRL_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'dfx-parish-retreat-letters';

		$this->load_dependencies();
		$this->init_database();
		$this->init_security();
		$this->init_gdpr();
		$this->init_permissions();
		$this->init_invitations();
		$this->init_admin();
		$this->init_public_hooks();
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
	 * - DFXPRL_I18n. Defines internationalization functionality.
	 * - DFXPRL_Database. Manages database operations.
	 * - DFXPRL_Security. Handles encryption and security.
	 * - DFXPRL_Retreat. Handles retreat CRUD operations.
	 * - DFXPRL_Attendant. Handles attendant CRUD operations.
	 * - DFXPRL_ConfidentialMessage. Handles message CRUD operations.
	 * - DFXPRL_MessageFile. Handles file CRUD operations.
	 * - DFXPRL_PrintLog. Handles print logging.
	 * - DFXPRL_Admin. Manages admin interface.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * The class responsible for defining database functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-database.php';

		/**
		 * The class responsible for security and encryption functionality.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-security.php';

		/**
		 * The class responsible for retreat CRUD operations.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-retreat.php';

		/**
		 * The class responsible for attendant CRUD operations.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-attendant.php';

		/**
		 * The class responsible for confidential message CRUD operations.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-confidential-message.php';

		/**
		 * The class responsible for message file CRUD operations.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-message-file.php';

		/**
		 * The class responsible for print log operations.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-print-log.php';

		/**
		 * The class responsible for GDPR compliance.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-gdpr.php';

		/**
		 * The class responsible for permissions management.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-permissions.php';

		/**
		 * The class responsible for invitation system.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-invitations.php';

		/**
		 * The class responsible for global settings management.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-global-settings.php';

		/**
		 * The class responsible for defining admin interface functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-admin.php';
	}

	/**
	 * Initialize the database management.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_database() {
		$this->database = DFXPRL_Database::get_instance();
	}

	/**
	 * Initialize the security management.
	 *
	 * @since    1.2.0
	 * @access   private
	 */
	private function init_security() {
		$this->security = DFXPRL_Security::get_instance();
	}

	/**
	 * Initialize the GDPR compliance management.
	 *
	 * @since    1.2.0
	 * @access   private
	 */
	private function init_gdpr() {
		$this->gdpr = DFXPRL_GDPR::get_instance();
	}

	/**
	 * Initialize the permissions management.
	 *
	 * @since    1.3.0
	 * @access   private
	 */
	private function init_permissions() {
		$this->permissions = DFXPRL_Permissions::get_instance();
	}

	/**
	 * Initialize the invitations management.
	 *
	 * @since    1.3.0
	 * @access   private
	 */
	private function init_invitations() {
		$this->invitations = DFXPRL_Invitations::get_instance();
	}

	/**
	 * Initialize the admin interface.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function init_admin() {
		if ( is_admin() ) {
			$this->admin = DFXPRL_Admin::get_instance();
		}
	}

	/**
	 * Initialize public hooks for message submission.
	 *
	 * @since    1.2.0
	 * @access   private
	 */
	private function init_public_hooks() {
		add_action( 'wp_loaded', array( $this, 'handle_message_url_routing' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_scripts' ) );
		add_filter( 'body_class', array( $this, 'add_message_form_body_class' ) );
		add_action( 'wp_ajax_nopriv_dfxprl_submit_message', array( $this, 'handle_message_submission' ) );
		add_action( 'wp_ajax_dfxprl_submit_message', array( $this, 'handle_message_submission' ) );

		// Schedule cleanup tasks
		add_action( 'init', array( $this, 'schedule_cleanup_tasks' ) );
		add_action( 'dfxprl_retreat_cleanup_hook', array( $this, 'run_cleanup_tasks' ) );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		// Load plugin text domain for translations on 'init' action to prevent WordPress 6.7+ warnings
		// about text domain loading being triggered too early
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * WordPress best practice is to load text domains on the 'init' action or later
	 * to prevent warnings in WordPress 6.7+ about text domain loading being triggered too early.
	 *
	 * @since 25.9.12
	 */
	public function load_plugin_textdomain() {
		// Load plugin text domain for translations
		$plugin_dir = plugin_basename( dirname( dirname( __FILE__ ) ) );
		call_user_func(
			'load_plugin_textdomain',
			'dfx-parish-retreat-letters',
			false,
			$plugin_dir . '/languages/'
		);
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
	 * @return    DFXPRL_Database    The database instance.
	 */
	public function get_database() {
		return $this->database;
	}

	/**
	 * Handle URL routing for message submission.
	 *
	 * @since 1.2.0
	 */
	public function handle_message_url_routing() {

    // Check if we're on a message URL pattern: /messages/[token] or /print/[token]
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

    // Ensure we have a valid string
		if ( ! is_string( $request_uri ) || empty( $request_uri ) ) {
			return;
		}

		// Remove query string
		$request_uri = strtok( $request_uri, '?' );
		if ( $request_uri === false ) {
			return;
		}

		// Get the site's base path
		$site_url = wp_parse_url( home_url(), PHP_URL_PATH );
		$site_path = ( is_string( $site_url ) && ! empty( $site_url ) ) ? $site_url : '/';

		// Normalize paths and ensure site_path is a string
		$site_path = (string) $site_path;
		if ( $site_path !== '/' ) {
			$site_path = rtrim( $site_path, '/' );
		}

		// Create patterns for both message submission and print URLs
		$messages_pattern = '#^' . preg_quote( $site_path, '#' ) . '/messages/([a-zA-Z0-9]+)/?$#';
		$print_pattern = '#^' . preg_quote( $site_path, '#' ) . '/print/([a-zA-Z0-9]+)/?$#';

		// Also try without the site path for root installations
		$root_messages_pattern = '#^/messages/([a-zA-Z0-9]+)/?$#';
		$root_print_pattern = '#^/print/([a-zA-Z0-9]+)/?$#';

		$token = null;
		$is_print = false;

		// Check for message submission URLs
		if ( preg_match( $messages_pattern, $request_uri, $matches ) ) {
			$token = sanitize_text_field( $matches[1] );
		} elseif ( preg_match( $root_messages_pattern, $request_uri, $matches ) ) {
			$token = sanitize_text_field( $matches[1] );
		}
		// Check for print URLs
		elseif ( preg_match( $print_pattern, $request_uri, $matches ) ) {
			$token = sanitize_text_field( $matches[1] );
			$is_print = true;
		} elseif ( preg_match( $root_print_pattern, $request_uri, $matches ) ) {
			$token = sanitize_text_field( $matches[1] );
			$is_print = true;
		}

		if ( $token ) {
			if ( $is_print ) {
				$this->handle_print_request( $token );
				exit;
			}

			$shown = $this->display_message_form( $token );
			if ( $shown ) {
				exit;
			}

		}
	}

	/**
	 * Display the message submission form for a given token.
	 *
	 * @since 1.2.0
	 * @param string $token Message URL token.
	 */
	private function display_message_form( $token ) {
		// Verify token and get attendant
		$attendant = $this->get_attendant_by_token( $token );

		if ( ! $attendant ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			return false;
		}

		// Get retreat data for custom header/footer blocks
		$retreat = $this->get_retreat_by_token( $token );

		// Rate limiting check (skip for logged-in users)
		if ( ! is_user_logged_in() ) {
			$ip_address = $this->security->get_user_ip();
			if ( ! $this->security->check_rate_limit( $ip_address, 10, 60 ) ) {
				// Rate limit exceeded
				$this->security->log_rate_limit_violation( $ip_address, 'message_form_access' );
				wp_die(
					esc_html__( 'Too many requests. Please wait before trying again.', 'dfx-parish-retreat-letters' ),
					esc_html__( 'Rate Limit Exceeded', 'dfx-parish-retreat-letters' ),
					array( 'response' => 429 )
				);
			}
		}

		// Set up WordPress environment for form
		status_header( 200 );
		nocache_headers();

		// Try to render custom header, fallback to default
		$custom_header_rendered = false;
		if ( $retreat && ! empty( $retreat->custom_header_block_id ) ) {
			// Enqueue output buffering to capture wp_head output
			ob_start();
			echo '<!-- DFX Debug: Attempting to render custom header with ID: ' . esc_html( $retreat->custom_header_block_id ) . ' -->';
			$custom_header_rendered = $this->render_custom_block( $retreat->custom_header_block_id );
			echo '<!-- DFX Debug: Custom header render result: ' . ( $custom_header_rendered ? 'success' : 'failed' ) . ' -->';
			$generated_head = ob_get_clean();

			if ( $custom_header_rendered ) {
				?>
				<!DOCTYPE html>
				<html <?php language_attributes(); ?>>
				<head>
					<meta charset="<?php bloginfo( 'charset' ); ?>">
					<meta name="viewport" content="width=device-width, initial-scale=1">
					<?php wp_head(); ?>
				</head>
				<body <?php body_class(); ?>>
				<?php
				wp_body_open();
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Contains WordPress-generated HTML from custom blocks
				echo $generated_head;
			}
		}

		if ( ! $custom_header_rendered ) {
			// Include WordPress header as fallback (theme-agnostic)
			$this->render_theme_header();
		}

		// Display the form
		$this->render_message_form( $attendant, $retreat );

		// Try to render custom footer, fallback to default
		$custom_footer_rendered = false;
		if ( $retreat && ! empty( $retreat->custom_footer_block_id ) ) {
			// Debug: Output what we're trying to render
			echo '<!-- DFX Debug: Attempting to render custom footer with ID: ' . esc_html( $retreat->custom_footer_block_id ) . ' -->';
			$custom_footer_rendered = $this->render_custom_block( $retreat->custom_footer_block_id );
			echo '<!-- DFX Debug: Custom footer render result: ' . ( $custom_footer_rendered ? 'success' : 'failed' ) . ' -->';
			if ( $custom_footer_rendered ) {
				// Remove deprecated skip link hook to prevent deprecation warning
				if ( function_exists( 'wp_enqueue_block_template_skip_link' ) ) {
					remove_action( 'wp_footer', 'the_block_template_skip_link' );
					wp_enqueue_block_template_skip_link();
				}
				wp_footer();
				?>
				</body>
				</html>
				<?php
			}
		}

		if ( ! $custom_footer_rendered ) {
			// Include WordPress footer as fallback (theme-agnostic)
			echo '<!-- DFX Debug: Rendering fallback theme footer -->';
			$this->render_theme_footer();
			echo '<!-- DFX Debug: Fallback theme footer rendered -->';
		}

		return true;
	}

	/**
	 * Get attendant by message URL token.
	 *
	 * @since 1.2.0
	 * @param string $token Message URL token.
	 * @return object|null Attendant object or null if not found.
	 */
	private function get_attendant_by_token( $token ) {
		global $wpdb;

		$attendants_table = $this->database->get_attendants_table();
		$retreats_table = $this->database->get_retreats_table();

		$attendant = $wpdb->get_row( $wpdb->prepare(
			"SELECT a.*, r.name as retreat_name, r.location as retreat_location, r.start_date, r.end_date, r.custom_message, r.disclaimer_text, r.disclaimer_acceptance_text
			 FROM `{$attendants_table}` a
			 INNER JOIN `{$retreats_table}` r ON a.retreat_id = r.id
			 WHERE a.message_url_token = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$token
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $attendant;
	}

	/**
	 * Render the message submission form.
	 *
	 * @since 1.2.0
	 * @param object $attendant Attendant object.
	 * @param object|null $retreat Retreat object (optional, for custom CSS).
	 */
	private function render_message_form( $attendant, $retreat = null ) {
		?>
		<div class="dfxprl-message-form-container">
			<div class="dfxprl-message-form-content">
				<h1><?php esc_html_e( 'Send a Confidential Message', 'dfx-parish-retreat-letters' ); ?></h1>

				<div class="dfxprl-retreat-info">
					<h2><?php esc_html_e( 'For Retreat Attendant', 'dfx-parish-retreat-letters' ); ?></h2>
					<p><strong><?php echo esc_html( $attendant->name . ' ' . $attendant->surnames ); ?></strong></p>
					<p><?php echo esc_html( $attendant->retreat_name ); ?> - <?php echo esc_html( $attendant->retreat_location ); ?></p>
					<p><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $attendant->start_date ) ) ); ?> - <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $attendant->end_date ) ) ); ?></p>
				</div>

				<?php if ( ! empty( $attendant->custom_message ) ) : ?>
				<div class="dfxprl-custom-message">
					<?php echo wp_kses_post( wpautop( $attendant->custom_message ) ); ?>
				</div>
				<?php endif; ?>

				<div id="dfxprl-message-notices"></div>

				<form id="dfxprl-message-form" enctype="multipart/form-data">
					<?php wp_nonce_field( 'dfxprl_submit_message', 'message_nonce' ); ?>
					<input type="hidden" name="action" value="dfxprl_submit_message">
					<input type="hidden" name="attendant_id" value="<?php echo esc_attr( $attendant->id ); ?>">

					<div class="dfxprl-form-group">
						<label for="sender_name"><?php esc_html_e( 'Your Name', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
						<input type="text" id="sender_name" name="sender_name" required maxlength="255" placeholder="<?php esc_attr_e( 'Enter your name', 'dfx-parish-retreat-letters' ); ?>">
					</div>

					<div class="dfxprl-message-mode">
						<h3><?php esc_html_e( 'Choose message type:', 'dfx-parish-retreat-letters' ); ?></h3>
						<div class="dfxprl-mode-selector">
							<label>
								<input type="radio" name="message_mode" value="text" checked>
								<?php esc_html_e( 'Text Message', 'dfx-parish-retreat-letters' ); ?>
							</label>
							<label>
								<input type="radio" name="message_mode" value="file">
								<?php esc_html_e( 'File Upload', 'dfx-parish-retreat-letters' ); ?>
							</label>
						</div>
					</div>

					<div class="dfxprl-form-group" id="dfxprl-text-group">
						<label for="message_content"><?php esc_html_e( 'Your Message', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
						<div id="dfxprl-editor-container">
							<div class="dfxprl-editor-toolbar">
								<button type="button" data-command="bold" title="<?php esc_attr_e( 'Bold', 'dfx-parish-retreat-letters' ); ?>"><strong>B</strong></button>
								<button type="button" data-command="italic" title="<?php esc_attr_e( 'Italic', 'dfx-parish-retreat-letters' ); ?>"><em>I</em></button>
								<button type="button" data-command="underline" title="<?php esc_attr_e( 'Underline', 'dfx-parish-retreat-letters' ); ?>"><u>U</u></button>
								<button type="button" data-command="insertUnorderedList" title="<?php esc_attr_e( 'Bullet List', 'dfx-parish-retreat-letters' ); ?>">• <?php esc_html_e( 'List', 'dfx-parish-retreat-letters' ); ?></button>
								<button type="button" data-command="insertOrderedList" title="<?php esc_attr_e( 'Numbered List', 'dfx-parish-retreat-letters' ); ?>">1. <?php esc_html_e( 'List', 'dfx-parish-retreat-letters' ); ?></button>
								<button type="button" data-command="undo" title="<?php esc_attr_e( 'Undo', 'dfx-parish-retreat-letters' ); ?>"><?php esc_html_e( 'Undo', 'dfx-parish-retreat-letters' ); ?></button>
								<button type="button" data-command="redo" title="<?php esc_attr_e( 'Redo', 'dfx-parish-retreat-letters' ); ?>"><?php esc_html_e( 'Redo', 'dfx-parish-retreat-letters' ); ?></button>
							</div>
							<div id="message_content" contenteditable="true" class="dfxprl-editor" placeholder="<?php
								$attendant_full_name = trim( esc_html( $attendant->name ) . ' ' . esc_html( $attendant->surnames ) );
								/* translators: %s: attendant full name (name and surnames) */
								echo esc_attr( sprintf( __( 'Write your confidential message for %s here...', 'dfx-parish-retreat-letters' ), $attendant_full_name ) );
							?>"></div>
							<textarea id="message_content_hidden" name="message_content" style="display: none;"></textarea>
						</div>
						<p class="dfxprl-help-text"><?php esc_html_e( 'You can use the toolbar to format your text, create lists, and paste images. HTML is allowed but will be filtered for security.', 'dfx-parish-retreat-letters' ); ?></p>
					</div>

					<div class="dfxprl-form-group" id="dfxprl-file-group" style="display: none;">
						<label for="message_files"><?php esc_html_e( 'Attach Files', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
						<input type="file" id="message_files" name="message_files[]" multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif">
						<p class="dfxprl-help-text">
							<?php
							$max_per_file = $this->security->get_max_upload_size();
							$max_combined = $this->security->get_max_combined_upload_size();

							// Only display size limits if we can reliably determine them
							if ( $max_per_file !== null && $max_combined !== null ) {
								printf(
									/* translators: %1$s: maximum size per file, %2$s: maximum combined size */
									esc_html__( 'Allowed file types: PDF, DOC, DOCX, TXT, JPG, PNG, GIF. Maximum %1$s per file, %2$s total.', 'dfx-parish-retreat-letters' ),
									esc_html( $max_per_file ),
									esc_html( $max_combined )
								);
							} elseif ( $max_per_file !== null ) {
								printf(
									/* translators: %s: maximum size per file */
									esc_html__( 'Allowed file types: PDF, DOC, DOCX, TXT, JPG, PNG, GIF. Maximum %s per file.', 'dfx-parish-retreat-letters' ),
									esc_html( $max_per_file )
								);
							} elseif ( $max_combined !== null ) {
								printf(
									/* translators: %s: maximum combined size */
									esc_html__( 'Allowed file types: PDF, DOC, DOCX, TXT, JPG, PNG, GIF. Maximum %s total.', 'dfx-parish-retreat-letters' ),
									esc_html( $max_combined )
								);
							} else {
								// Server configuration unknown - just show allowed types
								esc_html_e( 'Allowed file types: PDF, DOC, DOCX, TXT, JPG, PNG, GIF.', 'dfx-parish-retreat-letters' );
							}
							?>
						</p>
						<div id="dfxprl-file-list"></div>
					</div>

					<div class="dfxprl-form-group">
						<div class="dfxprl-captcha-container">
							<label for="captcha_answer"><?php esc_html_e( 'Security Check', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
							<div id="dfxprl-captcha-question"></div>
							<input type="text" id="captcha_answer" name="captcha_answer" required autocomplete="off" placeholder="<?php esc_html_e( 'Please enter the result', 'dfx-parish-retreat-letters' );?>">
							<input type="hidden" id="captcha_token" name="captcha_token">
						</div>
					</div>

					<?php if ( ! empty( $attendant->disclaimer_text ) ) : ?>
					<div class="dfxprl-form-group">
						<div class="dfxprl-disclaimer-container">
							<h3><?php esc_html_e( 'Legal Disclaimer', 'dfx-parish-retreat-letters' ); ?></h3>
							<div class="dfxprl-disclaimer-text">
								<?php echo wp_kses_post( wpautop( $attendant->disclaimer_text ) ); ?>
							</div>
							<div class="dfxprl-disclaimer-acceptance">
								<label class="dfxprl-checkbox-label">
									<input type="checkbox" id="disclaimer_accepted" name="disclaimer_accepted" required>
									<span class="required">*</span>
									<?php
									$acceptance_text = ! empty( $attendant->disclaimer_acceptance_text )
										? $attendant->disclaimer_acceptance_text
										: __( 'I accept the terms and conditions stated above', 'dfx-parish-retreat-letters' );
									echo esc_html( $acceptance_text );
									?>
								</label>
							</div>
						</div>
					</div>
					<?php endif; ?>

					<div class="dfxprl-form-group">
						<button type="submit" id="dfxprl-submit-btn" class="dfxprl-submit-button">
							<span class="dfxprl-submit-text"><?php esc_html_e( 'Send Confidential Message', 'dfx-parish-retreat-letters' ); ?></span>
							<span class="dfxprl-loading-spinner" style="display: none;">
								<?php esc_html_e( 'Sending...', 'dfx-parish-retreat-letters' ); ?>
							</span>
						</button>
					</div>
				</form>

				<div class="dfxprl-privacy-notice">
					<h3><?php esc_html_e( 'Privacy & Security', 'dfx-parish-retreat-letters' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'Your message will be encrypted and stored securely.', 'dfx-parish-retreat-letters' ); ?></li>
						<li><?php esc_html_e( 'Only authorized retreat administrators can access your message.', 'dfx-parish-retreat-letters' ); ?></li>
						<li><?php esc_html_e( 'Messages can only be viewed when printed - no content is displayed on screen.', 'dfx-parish-retreat-letters' ); ?></li>
						<li><?php esc_html_e( 'Your IP address will be anonymized after 30 days for privacy compliance.', 'dfx-parish-retreat-letters' ); ?></li>
						<li><?php esc_html_e( 'All data is handled in accordance with GDPR and Spanish privacy laws.', 'dfx-parish-retreat-letters' ); ?></li>
					</ul>
				</div>
			</div>
		</div>

		<?php
		// Styles for message form are properly enqueued via enqueue_public_scripts()
		// from assets/css/message-form.css with custom CSS via wp_add_inline_style()
	}


	/**
	 * Enqueue scripts and styles for the public message form.
	 *
	 * @since 1.2.0
	 */
	public function enqueue_public_scripts() {
		// Only enqueue on message URLs
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		// Ensure we have a valid string
		if ( ! is_string( $request_uri ) || empty( $request_uri ) ) {
			return;
		}

		$request_uri = strtok( $request_uri, '?' );
		if ( $request_uri === false ) {
			return;
		}

		// Get the site's base path (same logic as handle_message_url_routing)
		$site_url = wp_parse_url( home_url(), PHP_URL_PATH );
		$site_path = ( is_string( $site_url ) && ! empty( $site_url ) ) ? $site_url : '/';

		// Normalize paths and ensure site_path is a string
		$site_path = (string) $site_path;
		if ( $site_path !== '/' ) {
			$site_path = rtrim( $site_path, '/' );
		}

		// Create the pattern based on the site's base path
		$pattern = '#^' . preg_quote( $site_path, '#' ) . '/messages/([a-zA-Z0-9]+)/?$#';

		// Also try without the site path for root installations
		$root_pattern = '#^/messages/([a-zA-Z0-9]+)/?$#';

		$is_message_url = preg_match( $pattern, $request_uri ) || preg_match( $root_pattern, $request_uri );

		if ( $is_message_url ) {
			// Enqueue jQuery (dependency for our script)
			wp_enqueue_script( 'jquery' );

			// Enqueue styles for message form from external CSS file
			wp_enqueue_style(
				'dfxprl-message-form',
				DFXPRL_PLUGIN_URL . 'assets/css/message-form.css',
				array(),
				DFXPRL_VERSION
			);


			// Enqueue message form JavaScript
			wp_enqueue_script(
				'dfxprl-message-form',
				DFXPRL_PLUGIN_URL . 'assets/js/message-form.js',
				array( 'jquery' ),
				DFXPRL_VERSION,
				true // Load in footer
			);

			// Localize script with translatable strings and AJAX URL
			wp_localize_script(
				'dfxprl-message-form',
				'dfxprlMessageForm',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'i18n'    => array(
						'captchaPrefix'              => __( 'Please solve: ', 'dfx-parish-retreat-letters' ),
						'remove'                     => __( 'Remove', 'dfx-parish-retreat-letters' ),
						'pleaseEnterMessage'         => __( 'Please enter a message.', 'dfx-parish-retreat-letters' ),
						'pleaseSelectFile'           => __( 'Please select at least one file.', 'dfx-parish-retreat-letters' ),
						'pleaseCompleteSecurityCheck' => __( 'Please complete the security check.', 'dfx-parish-retreat-letters' ),
						'pleaseAcceptDisclaimer'     => __( 'Please accept the legal disclaimer to proceed.', 'dfx-parish-retreat-letters' ),
						'successMessage'             => __( 'Your message has been sent successfully and securely stored.', 'dfx-parish-retreat-letters' ),
						'errorSendingMessage'        => __( 'An error occurred while sending your message.', 'dfx-parish-retreat-letters' ),
						'requestTimeout'             => __( 'The request timed out. Please check your connection and try again.', 'dfx-parish-retreat-letters' ),
						'requestCancelled'           => __( 'The request was cancelled. Please try again.', 'dfx-parish-retreat-letters' ),
						'cannotConnectToServer'      => __( 'Cannot connect to the server. Please check your internet connection.', 'dfx-parish-retreat-letters' ),
						'problemWithRequest'         => __( 'There was a problem with your request. Please refresh the page and try again.', 'dfx-parish-retreat-letters' ),
						'accessDenied'               => __( 'Access denied. Please refresh the page and try again.', 'dfx-parish-retreat-letters' ),
						'uploadedFilesTooLarge'      => __( 'The uploaded files are too large. Please reduce the file size and try again.', 'dfx-parish-retreat-letters' ),
						'serverError'                => __( 'A server error occurred. Please try again later or contact support if the problem persists.', 'dfx-parish-retreat-letters' ),
						'problemProcessingResponse'  => __( 'There was a problem processing the server response. Please try again.', 'dfx-parish-retreat-letters' ),
						'networkError'               => __( 'A network error occurred. Please try again.', 'dfx-parish-retreat-letters' ),
					),
				)
			);
		}
	}

	/**
	 * Add body classes to the message form pages.
	 *
	 * Always adds 'dfxprl-message-form'. Also adds any global and per-retreat
	 * additional classes configured in the plugin settings.
	 *
	 * @since 25.12.10
	 * @param array $classes Existing body classes.
	 * @return array Modified body classes.
	 */
	public function add_message_form_body_class( $classes ) {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		if ( ! is_string( $request_uri ) || empty( $request_uri ) ) {
			return $classes;
		}

		$request_uri = strtok( $request_uri, '?' );
		if ( $request_uri === false ) {
			return $classes;
		}

		$site_url  = wp_parse_url( home_url(), PHP_URL_PATH );
		$site_path = ( is_string( $site_url ) && ! empty( $site_url ) ) ? rtrim( $site_url, '/' ) : '';

		$pattern      = '#^' . preg_quote( $site_path, '#' ) . '/messages/([a-zA-Z0-9]+)/?$#';
		$root_pattern = '#^/messages/([a-zA-Z0-9]+)/?$#';

		$token = null;
		if ( preg_match( $pattern, $request_uri, $matches ) || preg_match( $root_pattern, $request_uri, $matches ) ) {
			$token = $matches[1];
		}

		if ( null === $token ) {
			return $classes;
		}

		$classes[] = 'dfxprl-message-form';

		// Add global additional body classes.
		$global_settings = DFXPRL_GlobalSettings::get_instance();
		$global_classes  = $global_settings->get_body_classes();
		if ( ! empty( $global_classes ) ) {
			foreach ( explode( ' ', $global_classes ) as $class ) {
				$class = sanitize_html_class( $class );
				if ( ! empty( $class ) ) {
					$classes[] = $class;
				}
			}
		}

		// Add per-retreat additional body classes.
		if ( $global_settings->is_per_retreat_customization_enabled() ) {
			$attendant = $this->get_attendant_by_token( $token );
			if ( $attendant ) {
				$retreat_model   = new DFXPRL_Retreat();
				$retreat         = $retreat_model->get( $attendant->retreat_id );
				$retreat_classes = $retreat->body_classes ?? '';
				if ( ! empty( $retreat_classes ) ) {
					foreach ( explode( ' ', $retreat_classes ) as $class ) {
						$class = sanitize_html_class( $class );
						if ( ! empty( $class ) ) {
							$classes[] = $class;
						}
					}
				}
			}
		}

		return $classes;
	}

	/**
	 * Output JavaScript for the message form.
	 *
	 * @since 1.2.0
	 * @deprecated 25.12.10 JavaScript now loaded from assets/js/message-form.js via wp_enqueue_script()
	 */
	public function output_message_form_script() {
		// JavaScript is now properly enqueued via enqueue_public_scripts()
		// and loaded from assets/js/message-form.js
		// This method is kept for backwards compatibility but does nothing
	}

	/**
	 * Handle AJAX message submission.
	 *
	 * @since 1.2.0
	 */
	public function handle_message_submission() {
		// Verify nonce
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['message_nonce'] ?? '' ) ), 'dfxprl_submit_message' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Rate limiting (skip for logged-in users)
		$ip_address = $this->security->get_user_ip();
		if ( ! is_user_logged_in() ) {
   		// Rate limiting - only check, don't increment yet
		  if ( ! $this->security->is_within_rate_limit( $ip_address, 20, 60 ) ) {
			  $this->security->log_rate_limit_violation( $ip_address, 'message_submission' );
			  wp_send_json_error( array( 'message' => __( 'Too many submission attempts. Please wait before trying again.', 'dfx-parish-retreat-letters' ) ) );
      }
    }

		// Validate CAPTCHA
		$user_answer = sanitize_text_field( wp_unslash( $_POST['captcha_answer'] ?? '' ) );
		$captcha_token = sanitize_text_field( wp_unslash( $_POST['captcha_token'] ?? '' ) );

		if ( '' === $user_answer || empty( $captcha_token ) ) {
			wp_send_json_error( array( 'message' => __( 'Please complete the security check.', 'dfx-parish-retreat-letters' ) ) );
		}

		$expected_answer = base64_decode( $captcha_token );
		if ( $user_answer != $expected_answer ) {
			wp_send_json_error( array( 'message' => __( 'Incorrect security answer.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Validate attendant
		$attendant_id = absint( $_POST['attendant_id'] ?? 0 );
		if ( ! $attendant_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid attendant.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Get attendant and retreat data to check for disclaimer requirements
		global $wpdb;
		$attendants_table = $this->database->get_attendants_table();
		$retreats_table = $this->database->get_retreats_table();

		$retreat_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT r.disclaimer_text, r.disclaimer_acceptance_text
			 FROM `{$attendants_table}` a
			 INNER JOIN `{$retreats_table}` r ON a.retreat_id = r.id
			 WHERE a.id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$attendant_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		// Validate disclaimer if required
		if ( ! empty( $retreat_data->disclaimer_text ) ) {
			$disclaimer_accepted = sanitize_text_field( wp_unslash( $_POST['disclaimer_accepted'] ?? '' ) );
			if ( empty( $disclaimer_accepted ) || $disclaimer_accepted !== 'on' ) {
				wp_send_json_error( array( 'message' => __( 'You must accept the legal disclaimer to proceed.', 'dfx-parish-retreat-letters' ) ) );
			}
		}

		// Validate sender name (now required)
		$sender_name = sanitize_text_field( wp_unslash( $_POST['sender_name'] ?? '' ) );
		if ( empty( trim( $sender_name ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Sender name is required.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Validate message mode
		$message_mode = sanitize_text_field( wp_unslash( $_POST['message_mode'] ?? 'text' ) );
		if ( ! in_array( $message_mode, array( 'text', 'file' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid message mode.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Validate based on message mode
		$content = '';
		$message_type = 'text';

		if ( $message_mode === 'text' ) {
			// Validate message content
			$content = wp_kses_post( wp_unslash( $_POST['message_content'] ?? '' ) );

			// Check if content has actual text, not just HTML tags
			$text_content = trim( wp_strip_all_tags( $content ) );
			if ( empty( $content ) || empty( $text_content ) ) {
				wp_send_json_error( array( 'message' => __( 'Message content is required.', 'dfx-parish-retreat-letters' ) ) );
			}
			$message_type = 'text';
		} else {
			// Validate file upload
			if ( ! isset( $_FILES['message_files'] ) || empty( $_FILES['message_files']['name'][0] ) ) {
				wp_send_json_error( array( 'message' => __( 'At least one file is required.', 'dfx-parish-retreat-letters' ) ) );
			}
			
			// Sanitize file names array immediately upon first access
			$message_files = $_FILES['message_files'];
			if ( isset( $message_files['name'] ) && is_array( $message_files['name'] ) ) {
				$message_files['name'] = array_map( 'sanitize_file_name', $message_files['name'] );
			}
			
			$content = __( 'File upload message', 'dfx-parish-retreat-letters' );
			$message_type = 'file';
		}

		// Create message
		$message_model = new DFXPRL_ConfidentialMessage();
		$message_data = array(
			'attendant_id' => $attendant_id,
			'sender_name'  => $sender_name,
			'content'      => $content,
			'message_type' => $message_type,
		);

		$message_id = $message_model->create( $message_data );
		if ( ! $message_id ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save message.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Handle file uploads only if in file mode
		$upload_result = array( 'uploaded_count' => 0, 'errors' => array() );
		if ( $message_mode === 'file' && ! empty( $message_files['name'][0] ) ) {
			// Check if expected file count matches received file count
			// This detects when post_max_size is exceeded and PHP truncates the upload
			$expected_file_count = isset( $_POST['expected_file_count'] ) ? absint( $_POST['expected_file_count'] ) : 0;
			$received_file_count = count( array_filter( $message_files['name'] ) ); // Count non-empty filenames

			if ( $expected_file_count > 0 && $received_file_count < $expected_file_count ) {
				// Delete the message since the upload was incomplete
				$message_model->delete( $message_id );

				$max_combined_size = $this->security->get_max_combined_upload_size();
				$error_message = sprintf(
					/* translators: %1$d: number of files received, %2$d: number of files expected, %3$s: maximum combined upload size */
					__( 'Only %1$d of %2$d files were received. The total file size likely exceeds the server upload limit of %3$s. Please reduce the file sizes or upload fewer files at once.', 'dfx-parish-retreat-letters' ),
					$received_file_count,
					$expected_file_count,
					$max_combined_size
				);

				wp_send_json_error( array( 'message' => $error_message ) );
			}

			$upload_result = $this->handle_file_uploads( $message_id, $message_files );

			// If no files were uploaded successfully, return an error
			if ( $upload_result['uploaded_count'] === 0 ) {
				// Delete the message since no files were saved
				$message_model->delete( $message_id );

				$error_message = __( 'No files could be uploaded.', 'dfx-parish-retreat-letters' );
				if ( ! empty( $upload_result['errors'] ) ) {
					$error_message .= ' ' . implode( ' ', $upload_result['errors'] );
				}

				wp_send_json_error( array( 'message' => $error_message ) );
			}

			// If expected file count was provided and some files failed to upload, return an error
			if ( $expected_file_count > 0 && $upload_result['uploaded_count'] < $expected_file_count ) {
				// Delete the message since upload was incomplete
				$message_model->delete( $message_id );

				$error_message = sprintf(
					/* translators: %1$d: number of files uploaded, %2$d: number of files expected */
					__( 'Only %1$d of %2$d files could be uploaded. ', 'dfx-parish-retreat-letters' ),
					$upload_result['uploaded_count'],
					$expected_file_count
				);

				if ( ! empty( $upload_result['errors'] ) ) {
					$error_message .= implode( ' ', $upload_result['errors'] );
				} else {
					$max_combined_size = $this->security->get_max_combined_upload_size();
					$error_message .= sprintf(
						/* translators: %s: maximum combined upload size */
						__( 'Some files may be too large or the total size exceeds the server limit of %s. Please reduce file sizes or upload fewer files.', 'dfx-parish-retreat-letters' ),
						$max_combined_size
					);
				}

				wp_send_json_error( array( 'message' => $error_message ) );
			}
		}

		$success_message = __( 'Message sent successfully.', 'dfx-parish-retreat-letters' );

		// Add file upload info to success message if applicable
		if ( $message_mode === 'file' && $upload_result['uploaded_count'] > 0 ) {
			$success_message = sprintf(
				/* translators: %d: number of files uploaded */
				__( 'Message sent successfully with %d file(s).', 'dfx-parish-retreat-letters' ),
				$upload_result['uploaded_count']
			);

			// Add warning if some files failed
			if ( ! empty( $upload_result['errors'] ) ) {
				$success_message .= ' ' . sprintf(
					/* translators: %s: comma-separated list of file names that failed to upload */
					__( 'Note: Some files could not be uploaded: %s', 'dfx-parish-retreat-letters' ),
					implode( ', ', $upload_result['errors'] )
				);
			}
		}

		// Message was successfully created - now increment the rate limit
		$this->security->increment_rate_limit( $ip_address, 60 );

		wp_send_json_success( array( 'message' => $success_message ) );
	}

	/**
	 * Handle file uploads for a message.
	 *
	 * @since 1.2.0
	 * @param int   $message_id Message ID.
	 * @param array $files      Files array from $_FILES.
	 * @return array Array with success status and any error messages.
	 */
	private function handle_file_uploads( $message_id, $files ) {
		$file_model = new DFXPRL_MessageFile();

		$file_count = count( $files['name'] );
		$uploaded_count = 0;
		$errors = array();

		for ( $i = 0; $i < $file_count; $i++ ) {
			$filename = sanitize_file_name( $files['name'][$i] );

			// Skip empty file slots
			if ( empty( $filename ) ) {
				continue;
			}

			// Check for upload errors
			if ( $files['error'][$i] !== UPLOAD_ERR_OK ) {
				// Provide specific error messages for common upload errors
				$error_message = '';
				switch ( $files['error'][$i] ) {
					case UPLOAD_ERR_INI_SIZE:
						$error_message = sprintf(
							/* translators: %s: file name */
							__( 'File "%s" exceeds the maximum upload size limit.', 'dfx-parish-retreat-letters' ),
							$filename
						);
						break;
					case UPLOAD_ERR_FORM_SIZE:
						$error_message = sprintf(
							/* translators: %s: file name */
							__( 'File "%s" is too large.', 'dfx-parish-retreat-letters' ),
							$filename
						);
						break;
					case UPLOAD_ERR_PARTIAL:
						$error_message = sprintf(
							/* translators: %s: file name */
							__( 'File "%s" was only partially uploaded. Please try again.', 'dfx-parish-retreat-letters' ),
							$filename
						);
						break;
					case UPLOAD_ERR_NO_FILE:
						// Skip empty file slots silently
						continue 2;
					default:
						$error_message = sprintf(
							/* translators: %1$s: file name, %2$d: error code number */
							__( 'File "%1$s" upload failed with error code %2$d.', 'dfx-parish-retreat-letters' ),
							$filename,
							$files['error'][$i]
						);
				}
				$errors[] = $error_message;
				continue;
			}

			$file_data = array(
				'name'     => $filename,
				'tmp_name' => $files['tmp_name'][$i],
				'size'     => $files['size'][$i],
				'type'     => $files['type'][$i],
				'error'    => $files['error'][$i],
			);

			$validated_file = $this->security->validate_file_upload( $file_data );
			if ( ! $validated_file ) {
				$errors[] = sprintf(
					/* translators: %s: file name */
					__( 'File "%s" failed validation (unsupported type or too large).', 'dfx-parish-retreat-letters' ),
					$filename
				);
				continue;
			}

			$file_model_data = array(
				'message_id'        => $message_id,
				'original_filename' => $validated_file['name'],
				'file_type'         => $validated_file['type'],
				'file_size'         => $validated_file['size'],
				'tmp_name'          => $validated_file['tmp_name'],
			);

			$file_id = $file_model->create( $file_model_data );
			if ( $file_id ) {
				$uploaded_count++;
			} else {
				$errors[] = sprintf(
					/* translators: %s: file name */
					__( 'File "%s" could not be saved to database.', 'dfx-parish-retreat-letters' ),
					$filename
				);
			}
		}

		return array(
			'uploaded_count' => $uploaded_count,
			'errors' => $errors,
		);
	}

	/**
	 * Handle print requests for messages via clean URLs.
	 *
	 * @since 1.2.0
	 * @param string $token Print token.
	 */
	private function handle_print_request( $token ) {
		// Verify token
		if ( ! $token ) {
			wp_die( esc_html__( 'Invalid print request.', 'dfx-parish-retreat-letters' ) );
		}

		$message_id = get_transient( 'dfxprl_print_token_' . $token );
		if ( ! $message_id ) {
			wp_die( esc_html__( 'Print token expired or invalid.', 'dfx-parish-retreat-letters' ) );
		}

		// Delete the token after use
		delete_transient( 'dfxprl_print_token_' . $token );

		// Initialize models
		$message_model = new DFXPRL_ConfidentialMessage();
		$file_model = new DFXPRL_MessageFile();

		// Get message with decrypted content
		$message = $message_model->get_with_decrypted_content( $message_id );
		if ( ! $message ) {
			wp_die( esc_html__( 'Message not found.', 'dfx-parish-retreat-letters' ) );
		}

		// Get attached files if any
		$files = $file_model->get_by_message( $message_id );

		// For file messages with a single file, serve directly (or with header for PDFs).
		// Use ?fallback=header to force the HTML header-only page (for testing).
		// Use ?fallback=raw to serve the raw PDF without any header processing.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameter for debug/testing
		$fallback_mode = isset( $_GET['fallback'] ) ? sanitize_text_field( wp_unslash( $_GET['fallback'] ) ) : '';

		if ( $message->message_type === 'file' && count( $files ) === 1 ) {
			if ( $fallback_mode === 'raw' ) {
				// Serve the raw PDF embedded in a minimal HTML page with print and close.
				$this->serve_pdf_printable( $files[0], $file_model );
			} elseif ( $files[0]->file_type === 'application/pdf' ) {
				if ( $fallback_mode === 'header' ) {
					$this->render_print_header_only( $message, $files[0], $file_model );
				} else {
					$this->serve_pdf_with_header( $files[0], $file_model, $message );
				}
			} else {
				$this->serve_file_directly( $files[0], $file_model );
			}
			return;
		}

		// Check if we have multiple files with mixed types (containing non-images)
		if ( count( $files ) > 1 ) {
			$has_non_image = false;
			foreach ( $files as $file ) {
				if ( strpos( $file->file_type, 'image/' ) !== 0 ) {
					$has_non_image = true;
					break;
				}
			}

			// If there are non-image files, generate ZIP for download
			if ( $has_non_image ) {
				$this->serve_files_as_zip( $files, $file_model, $message );
				return;
			}
		}

		// For all other cases, render the clean print page
		$this->render_print_page( $message, $files );
	}

	/**
	 * Serve file directly for printing.
	 *
	 * @since 1.2.1
	 * @param object $file File object.
	 * @param DFXPRL_MessageFile $file_model File model instance.
	 */
	private function serve_file_directly( $file, $file_model ) {
		$decrypted_file = $file_model->get_decrypted_file( $file->id );
		if ( ! $decrypted_file ) {
			wp_die( esc_html__( 'File not found.', 'dfx-parish-retreat-letters' ) );
		}

		// Sanitize filename for header
		$safe_filename = sanitize_file_name( $decrypted_file['filename'] );

		// Set headers based on file type
		header( 'Content-Type: ' . $file->file_type );
		header( 'Content-Disposition: inline; filename="' . $safe_filename . '"' );
		header( 'Content-Length: ' . strlen( $decrypted_file['content'] ) );
		header( 'Cache-Control: private, no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output file content
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary file content for download
		echo $decrypted_file['content'];
		exit;
	}

	/**
	 * Serve a PDF embedded in an HTML page with auto-print and auto-close.
	 *
	 * @since 26.04.10
	 * @param object             $file       File object.
	 * @param DFXPRL_MessageFile $file_model File model instance.
	 */
	private function serve_pdf_printable( $file, $file_model ) {
		$decrypted_file = $file_model->get_decrypted_file( $file->id );
		if ( ! $decrypted_file ) {
			wp_die( esc_html__( 'File not found.', 'dfx-parish-retreat-letters' ) );
		}

		$pdf_data = base64_encode( $decrypted_file['content'] );
		unset( $decrypted_file['content'] );
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php esc_html_e( 'Print PDF', 'dfx-parish-retreat-letters' ); ?></title>
			<style>
				html, body, iframe { margin: 0; padding: 0; width: 100%; height: 100%; border: none; }
			</style>
		</head>
		<body>
			<embed src="data:application/pdf;base64,<?php echo $pdf_data; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Base64 PDF data ?>" type="application/pdf" width="100%" height="100%" style="position:fixed;top:0;left:0;">
			<script>
				(function() {
					var printTime = 0;

					function closeIfOurPrint() {
						// Only close if enough time passed since our window.print() call.
						if (printTime > 0 && (Date.now() - printTime) > 300) {
							window.close();
						}
					}

					window.addEventListener('load', function() {
						setTimeout(function() {
							printTime = Date.now();
							window.print();
						}, 1500);
					});

					window.addEventListener('afterprint', function() {
						setTimeout(closeIfOurPrint, 100);
					});

					// Focus fallback for browsers that don't fire afterprint.
					window.addEventListener('focus', function() {
						if (printTime > 0) {
							setTimeout(closeIfOurPrint, 500);
						}
					});
				})();
			</script>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Serve a PDF file with a From/To header on the first page.
	 *
	 * Uses FPDI to import the original PDF pages and FPDF to add a header
	 * strip at the top of the first page, scaling down the original content
	 * to make room.
	 *
	 * @since 26.04.10
	 * @param object             $file       File object.
	 * @param DFXPRL_MessageFile $file_model File model instance.
	 * @param object             $message    Message object (with attendant_name, sender_name, etc.).
	 */
	private function serve_pdf_with_header( $file, $file_model, $message ) {
		// Reserve memory so the catch block can execute after an out-of-memory error.
		$memory_reserve = str_repeat( 'x', 1024 * 1024 ); // 1 MB reserve.

		// Build header: To and From lines (cheap, do before any heavy work).
		$to_text = '';
		if ( ! empty( $message->attendant_name ) ) {
			$to_text = $message->attendant_name;
			if ( ! empty( $message->attendant_surnames ) ) {
				$to_text .= ' ' . $message->attendant_surnames;
			}
		}
		$from_text = ! empty( $message->sender_name ) ? $message->sender_name : '';

		// If no header info available, serve the raw PDF.
		if ( empty( $to_text ) && empty( $from_text ) ) {
			unset( $memory_reserve );
			$this->serve_file_directly( $file, $file_model );
			return;
		}

		// If FPDI/TCPDF is not available, fall back.
		if ( ! class_exists( '\setasign\Fpdi\TcpdfFpdi' ) ) {
			unset( $memory_reserve );
			$this->serve_pdf_with_cover_page( $file, $file_model, $message, $to_text, $from_text );
			return;
		}

		// Buffer output so partial/corrupt data can be discarded on error.
		ob_start();

		try {
			// TCPDF is memory-intensive; raise the limit before decrypting the file.
			wp_raise_memory_limit( 'admin' );

			$decrypted_file = $file_model->get_decrypted_file( $file->id );
			if ( ! $decrypted_file ) {
				ob_end_clean();
				unset( $memory_reserve );
				wp_die( esc_html__( 'File not found.', 'dfx-parish-retreat-letters' ) );
			}

			// Write decrypted PDF to a temp file (FPDI needs a file path).
			$tmp_file = tempnam( sys_get_temp_dir(), 'dfxprl_pdf_' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $tmp_file, $decrypted_file['content'] );

			// Free the in-memory copy now that it's on disk.
			unset( $decrypted_file['content'] );

			$pdf = new \setasign\Fpdi\TcpdfFpdi();
			$pdf->setPrintHeader( false );
			$pdf->setPrintFooter( false );
			$pdf->SetMargins( 0, 0, 0 );
			$pdf->SetAutoPageBreak( false );

			$page_count = $pdf->setSourceFile( $tmp_file );

			// Header dimensions (mm).
			$header_height   = 16; // Height of header strip.
			$header_margin   = 4;  // Top/left margin for header text.
			$line_height     = 5;  // Line height for header text.
			$bottom_margin   = 10; // Bottom margin below scaled content.

			for ( $page_no = 1; $page_no <= $page_count; $page_no++ ) {
				$tpl_id = $pdf->importPage( $page_no );
				$size   = $pdf->getTemplateSize( $tpl_id );

				if ( $page_no === 1 ) {
					// First page: keep original page size, scale content down to fit below header.
					$page_width  = $size['width'];
					$page_height = $size['height'];
					$available_height = $page_height - $header_height - $bottom_margin;
					$scale = $available_height / $size['height'];
					$scaled_width  = $size['width'] * $scale;
					$x_offset = ( $page_width - $scaled_width ) / 2; // Center horizontally.

					$pdf->AddPage( $size['width'] > $size['height'] ? 'L' : 'P', array( $page_width, $page_height ) );

					// Write header text (TCPDF handles UTF-8 natively).
					$pdf->SetFont( 'helvetica', 'B', 10 );
					$pdf->SetTextColor( 51, 51, 51 );

					// Use a fixed-width label column so "To:" and "From:" align.
					$label_width = max(
						$pdf->GetStringWidth( __( 'From', 'dfx-parish-retreat-letters' ) . ': ' ),
						$pdf->GetStringWidth( __( 'To', 'dfx-parish-retreat-letters' ) . ': ' )
					) + 1; // 1mm extra padding.

					$y_pos = $header_margin;
					if ( ! empty( $to_text ) ) {
						$pdf->SetXY( $header_margin, $y_pos );
						$pdf->Cell( $label_width, $line_height, __( 'To', 'dfx-parish-retreat-letters' ) . ': ', 0, 0, 'L' );
						$pdf->Cell( $page_width - $header_margin - $label_width - $header_margin, $line_height, $to_text, 0, 1, 'L' );
						$y_pos += $line_height;
					}
					if ( ! empty( $from_text ) ) {
						$pdf->SetXY( $header_margin, $y_pos );
						$pdf->Cell( $label_width, $line_height, __( 'From', 'dfx-parish-retreat-letters' ) . ': ', 0, 0, 'L' );
						$pdf->Cell( $page_width - $header_margin - $label_width - $header_margin, $line_height, $from_text, 0, 1, 'L' );
					}

					// Place the original page content scaled down below the header.
					// Only pass width — FPDI calculates height preserving aspect ratio.
					$actual_size = $pdf->useTemplate( $tpl_id, $x_offset, $header_height, $scaled_width );

					// Draw a border around the scaled-down original page.
					$pdf->SetDrawColor( 180, 180, 180 );
					$pdf->Rect( $x_offset, $header_height, $actual_size['width'], $actual_size['height'], 'D' );
				} else {
					// Subsequent pages: import as-is.
					$pdf->AddPage( $size['width'] > $size['height'] ? 'L' : 'P', array( $size['width'], $size['height'] ) );
					$pdf->useTemplate( $tpl_id, 0, 0, $size['width'], $size['height'] );
				}
			}

			// Clean up temp file.
			wp_delete_file( $tmp_file );

			// Generate the full PDF into a string before sending anything.
			// TCPDF Output('S') may write to the buffer instead of returning.
			$pdf_output = $pdf->Output( 'S' );
			$buffered = ob_get_clean();
			unset( $pdf );

			if ( empty( $pdf_output ) && ! empty( $buffered ) ) {
				$pdf_output = $buffered;
			}
			unset( $buffered );

			$safe_filename = sanitize_file_name( $decrypted_file['filename'] );
			header( 'Content-Type: application/pdf' );
			header( 'Content-Disposition: inline; filename="' . $safe_filename . '"' );
			header( 'Content-Length: ' . strlen( $pdf_output ) );
			header( 'Cache-Control: private, no-cache, no-store, must-revalidate' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary PDF output
			echo $pdf_output;
			exit;

		} catch ( \Exception $e ) {
			// Discard any partial output, free memory, clean up.
			ob_end_clean();
			unset( $memory_reserve );
			if ( isset( $tmp_file ) && file_exists( $tmp_file ) ) {
				wp_delete_file( $tmp_file );
			}
			$this->serve_pdf_with_cover_page( $file, $file_model, $message, $to_text, $from_text );
			return;
		} catch ( \Error $e ) {
			// Discard any partial output, free reserved memory so the fallback can execute.
			ob_end_clean();
			unset( $memory_reserve );
			if ( isset( $tmp_file ) && file_exists( $tmp_file ) ) {
				wp_delete_file( $tmp_file );
			}
			$this->serve_pdf_with_cover_page( $file, $file_model, $message, $to_text, $from_text );
			return;
		}
	}

	/**
	 * Serve a PDF with a prepended cover page showing From/To info.
	 *
	 * Lightweight fallback when full PDF re-rendering runs out of memory.
	 * Tries external tools first (Ghostscript, pdfunite), then falls back
	 * to the HTML print page.
	 *
	 * @since 26.04.10
	 * @param object             $file       File object.
	 * @param DFXPRL_MessageFile $file_model File model instance.
	 * @param object             $message    Message object.
	 * @param string             $to_text    Recipient name.
	 * @param string             $from_text  Sender name.
	 */
	private function serve_pdf_with_cover_page( $file, $file_model, $message, $to_text, $from_text ) {
		// Try using external tools to concatenate a cover page with the original PDF.
		if ( $this->serve_pdf_with_cover_page_external( $file, $file_model, $to_text, $from_text ) ) {
			return;
		}

		// Final fallback: render a simple HTML page with From/To header.
		// Don't attempt to embed the PDF (memory-heavy and won't print via window.print()).
		$this->render_print_header_only( $message, $file, $file_model );
	}

	/**
	 * Render a minimal HTML print page with only the From/To header.
	 *
	 * Used as a last-resort fallback when PDF manipulation is not possible.
	 * The user prints this page for the header info, then prints the PDF separately.
	 *
	 * @since 26.04.10
	 * @param object $message Message object.
	 */
	private function render_print_header_only( $message, $file = null, $file_model = null ) {
		// Generate a new one-time token so the user can open the raw PDF for printing.
		$raw_pdf_url = '';
		if ( $message->id ) {
			$raw_token = wp_generate_password( 32, false );
			set_transient( 'dfxprl_print_token_' . $raw_token, $message->id, 5 * MINUTE_IN_SECONDS );
			// The raw URL uses &fallback=raw to serve the file directly without header processing.
			$raw_pdf_url = home_url( '/print/' . $raw_token . '?fallback=raw' );
		}
		// Try to get PDF page count from the file (skip if memory is tight).
		$page_count = 0;
		if ( $file && $file_model ) {
			try {
				$decrypted_file = $file_model->get_decrypted_file( $file->id );
				if ( $decrypted_file && ! empty( $decrypted_file['content'] ) ) {
					// Count pages by looking for /Type /Page (not /Pages) in the raw PDF.
					preg_match_all( '/\/Type\s*\/Page[^s]/i', $decrypted_file['content'], $matches );
					$page_count = count( $matches[0] );
					unset( $matches, $decrypted_file );
				}
			} catch ( \Error $e ) {
				// Memory exhaustion — just skip the page count.
				$page_count = 0;
			}
		}

		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e( 'Print Message', 'dfx-parish-retreat-letters' ); ?></title>
			<script>
			(function() {
				var pdfHref = null;
				var done = false;

				function onPrintDone() {
					if (done) return;
					done = true;
					if (pdfHref) {
						window.location.href = pdfHref;
					} else {
						window.close();
					}
				}

				window.addEventListener('load', function() {
					var pdfLink = document.getElementById('dfxprl-open-pdf-btn');
					pdfHref = pdfLink ? pdfLink.href : null;

					setTimeout(function() {
						window.print();
						// Fallback: 3 seconds if afterprint doesn't fire.
						setTimeout(onPrintDone, 3000);
					}, 100);
				});

				window.addEventListener('afterprint', function() {
					setTimeout(onPrintDone, 100);
				});
			})();
			</script>
			<style>
				body { font-family: sans-serif; margin: 40px; }
				.message-header { margin-bottom: 20px; font-size: 16px; }
				.message-header strong { display: inline-block; width: 60px; }
				.message-notice { margin-top: 30px; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; font-size: 14px; color: #555; }
				@media print { .message-notice, .message-actions { display: none; } }
			</style>
		</head>
		<body>
			<div class="message-header">
				<?php if ( ! empty( $message->attendant_name ) ) : ?>
					<p><strong><?php echo esc_html__( 'To', 'dfx-parish-retreat-letters' ) . ':'; ?></strong> <?php echo esc_html( $message->attendant_name . ( ! empty( $message->attendant_surnames ) ? ' ' . $message->attendant_surnames : '' ) ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $message->sender_name ) ) : ?>
					<p><strong><?php echo esc_html__( 'From', 'dfx-parish-retreat-letters' ) . ':'; ?></strong> <?php echo esc_html( $message->sender_name ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $page_count > 0 ) : ?>
				<p style="font-size: 14px; color: #555;">
					<?php
					printf(
						/* translators: %d: number of pages in the attached PDF */
						esc_html( _n( 'Attached document: %d page', 'Attached document: %d pages', $page_count, 'dfx-parish-retreat-letters' ) ),
						$page_count
					);
					?>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $raw_pdf_url ) ) : ?>
				<a id="dfxprl-open-pdf-btn" href="<?php echo esc_url( $raw_pdf_url ); ?>" style="display:none;"></a>
			<?php endif; ?>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Try to prepend a cover page using external tools (Ghostscript or pdfunite).
	 *
	 * Creates a one-page cover PDF with TCPDF (lightweight, no imports), writes
	 * the original PDF to a temp file, and uses an external tool to merge them.
	 *
	 * @since 26.04.10
	 * @param object             $file       File object.
	 * @param DFXPRL_MessageFile $file_model File model instance.
	 * @param string             $to_text    Recipient name.
	 * @param string             $from_text  Sender name.
	 * @return bool True if the merged PDF was served, false if external tools unavailable.
	 */
	private function serve_pdf_with_cover_page_external( $file, $file_model, $to_text, $from_text ) {
		// Detect available merge tool.
		$gs_path      = $this->find_executable( 'gs' );
		$pdfunite_path = $this->find_executable( 'pdfunite' );

		if ( ! $gs_path && ! $pdfunite_path ) {
			return false;
		}

		try {
			// Create a lightweight cover page PDF with TCPDF (no FPDI imports).
			$cover_pdf = new \TCPDF();
			$cover_pdf->setPrintHeader( false );
			$cover_pdf->setPrintFooter( false );
			$cover_pdf->SetMargins( 20, 20, 20 );
			$cover_pdf->SetAutoPageBreak( false );

			$cover_pdf->AddPage();
			$cover_pdf->SetFont( 'helvetica', 'B', 14 );
			$cover_pdf->SetTextColor( 51, 51, 51 );

			$line_height = 8;
			$label_width = max(
				$cover_pdf->GetStringWidth( __( 'From', 'dfx-parish-retreat-letters' ) . ': ' ),
				$cover_pdf->GetStringWidth( __( 'To', 'dfx-parish-retreat-letters' ) . ': ' )
			) + 2;

			$cover_pdf->SetY( 40 );
			if ( ! empty( $to_text ) ) {
				$cover_pdf->SetX( 20 );
				$cover_pdf->Cell( $label_width, $line_height, __( 'To', 'dfx-parish-retreat-letters' ) . ': ', 0, 0, 'L' );
				$cover_pdf->Cell( 0, $line_height, $to_text, 0, 1, 'L' );
			}
			if ( ! empty( $from_text ) ) {
				$cover_pdf->SetX( 20 );
				$cover_pdf->Cell( $label_width, $line_height, __( 'From', 'dfx-parish-retreat-letters' ) . ': ', 0, 0, 'L' );
				$cover_pdf->Cell( 0, $line_height, $from_text, 0, 1, 'L' );
			}

			// Write cover page to temp file.
			// TCPDF Output('S') may write to stdout instead of returning cleanly.
			// Capture from the buffer to be safe.
			ob_start();
			$cover_content = $cover_pdf->Output( 'S' );
			$buffered = ob_get_clean();
			unset( $cover_pdf );

			// Use whichever has content: return value or captured buffer.
			if ( empty( $cover_content ) && ! empty( $buffered ) ) {
				$cover_content = $buffered;
			}
			unset( $buffered );

			$cover_file = tempnam( sys_get_temp_dir(), 'dfxprl_cover_' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $cover_file, $cover_content );
			unset( $cover_content );

			// Write original PDF to temp file.
			$decrypted_file = $file_model->get_decrypted_file( $file->id );
			if ( ! $decrypted_file ) {
				wp_delete_file( $cover_file );
				return false;
			}
			$original_file = tempnam( sys_get_temp_dir(), 'dfxprl_orig_' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $original_file, $decrypted_file['content'] );
			unset( $decrypted_file['content'] );

			$output_file = tempnam( sys_get_temp_dir(), 'dfxprl_merged_' );

			// Merge using available tool.
			// Prefer pdfunite — it does a simple concatenation that preserves
			// the original PDF structure (forms, fonts, encoding).
			// Ghostscript re-encodes everything, which can mangle form fields.
			$success = false;
			if ( $pdfunite_path ) {
				$cmd = sprintf(
					'%s %s %s %s 2>/dev/null',
					escapeshellarg( $pdfunite_path ),
					escapeshellarg( $cover_file ),
					escapeshellarg( $original_file ),
					escapeshellarg( $output_file )
				);
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
				exec( $cmd, $output, $return_code );
				$success = ( $return_code === 0 && file_exists( $output_file ) && filesize( $output_file ) > 0 );
			}

			if ( ! $success && $gs_path ) {
				$cmd = sprintf(
					'%s -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=%s %s %s 2>/dev/null',
					escapeshellarg( $gs_path ),
					escapeshellarg( $output_file ),
					escapeshellarg( $cover_file ),
					escapeshellarg( $original_file )
				);
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
				exec( $cmd, $output, $return_code );
				$success = ( $return_code === 0 && file_exists( $output_file ) && filesize( $output_file ) > 0 );
			}

			// Clean up input temp files.
			wp_delete_file( $cover_file );
			wp_delete_file( $original_file );

			if ( ! $success ) {
				wp_delete_file( $output_file );
				return false;
			}

			// Serve the merged PDF.
			$safe_filename = sanitize_file_name( $decrypted_file['filename'] );
			header( 'Content-Type: application/pdf' );
			header( 'Content-Disposition: inline; filename="' . $safe_filename . '"' );
			header( 'Content-Length: ' . filesize( $output_file ) );
			header( 'Cache-Control: private, no-cache, no-store, must-revalidate' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
			readfile( $output_file );
			wp_delete_file( $output_file );
			exit;

		} catch ( \Exception $e ) {
			return false;
		} catch ( \Error $e ) {
			return false;
		}
	}

	/**
	 * Find an executable in the system PATH.
	 *
	 * @since 26.04.10
	 * @param string $name Executable name (e.g. 'gs', 'pdfunite').
	 * @return string|false Full path to the executable, or false if not found.
	 */
	private function find_executable( $name ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		exec( 'which ' . escapeshellarg( $name ) . ' 2>/dev/null', $output, $return_code );
		if ( $return_code === 0 && ! empty( $output[0] ) ) {
			return $output[0];
		}
		return false;
	}

	/**
	 * Serve multiple files as a ZIP download.
	 *
	 * @since 1.2.2
	 * @param array  $files Array of file objects.
	 * @param DFXPRL_MessageFile $file_model File model instance.
	 * @param object $message Message object.
	 */
	private function serve_files_as_zip( $files, $file_model, $message ) {
		// Check if ZipArchive class is available
		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_die( esc_html__( 'ZIP functionality is not available on this server.', 'dfx-parish-retreat-letters' ) );
		}

		// Create a temporary file for the ZIP
		$temp_zip_path = wp_tempnam( 'message-files-', '.zip' );
		if ( ! $temp_zip_path ) {
			wp_die( esc_html__( 'Unable to create temporary file for ZIP download.', 'dfx-parish-retreat-letters' ) );
		}

		$zip = new ZipArchive();
		$result = $zip->open( $temp_zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

		if ( $result !== TRUE ) {
			wp_delete_file( $temp_zip_path );
			wp_die( esc_html__( 'Unable to create ZIP file.', 'dfx-parish-retreat-letters' ) );
		}

		// Add each file to the ZIP
		foreach ( $files as $file ) {
			$decrypted_file = $file_model->get_decrypted_file( $file->id );
			if ( $decrypted_file ) {
				// Sanitize filename for ZIP
				$safe_filename = sanitize_file_name( $decrypted_file['filename'] );
				$zip->addFromString( $safe_filename, $decrypted_file['content'] );
			}
		}

		$zip->close();

		// Check if ZIP file was created successfully
		if ( ! file_exists( $temp_zip_path ) || filesize( $temp_zip_path ) === 0 ) {
			if ( file_exists( $temp_zip_path ) ) {
				wp_delete_file( $temp_zip_path );
			}
			wp_die( esc_html__( 'Failed to generate ZIP file.', 'dfx-parish-retreat-letters' ) );
		}

		// Generate a safe filename for the ZIP
		$zip_filename = 'message-files';
		if ( ! empty( $message->sender_name ) ) {
			$sender_safe = sanitize_file_name( $message->sender_name );
			$zip_filename = "message-files-{$sender_safe}";
		}
		$zip_filename .= '.zip';

		// Send headers for ZIP download
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $zip_filename . '"' );
		header( 'Content-Length: ' . filesize( $temp_zip_path ) );
		header( 'Cache-Control: private, no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output the ZIP file using WP_Filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		global $wp_filesystem;
		
		if ( $wp_filesystem && $wp_filesystem->exists( $temp_zip_path ) ) {
			echo $wp_filesystem->get_contents( $temp_zip_path ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary ZIP file content
		} else {
			wp_delete_file( $temp_zip_path );
			wp_die( esc_html__( 'Unable to read ZIP file for download.', 'dfx-parish-retreat-letters' ) );
		}

		// Clean up temporary file
		wp_delete_file( $temp_zip_path );
		exit;
	}

	/**
	 * Clean MSO (Microsoft Office) artifacts from message content.
	 * This removes MSO style definitions that may have been saved in older messages.
	 *
	 * @since 1.2.0
	 * @param string $content The message content to clean.
	 * @return string Cleaned content.
	 */
	private function clean_mso_content( $content ) {
		// Remove style tags and their content (backup for old data)
		$content = preg_replace( '/<style[^>]*>[\s\S]*?<\/style>/i', '', $content );
		
		// Remove MSO conditional comments
		$content = preg_replace( '/<!--\[if[^\]]*\]>[\s\S]*?<!\[endif\]-->/i', '', $content );
		
		// Remove regular HTML comments
		$content = preg_replace( '/<!--[\s\S]*?-->/', '', $content );
		
		// Remove CSS-like MSO definitions that appear as text (must come before other patterns)
		// Pattern: "/* Style Definitions */ table.MsoNormalTable {...}"
		$content = preg_replace( '/\/\*\s*Style\s+Definitions\s*\*\/[\s\S]*?(?=<|$)/i', '', $content );
		
		// Remove MSO class/style text patterns that appear as loose text
		// Pattern: "Normal 0 21 false false false ES X-NONE X-NONE" or variations
		// This needs to be comprehensive to catch all MSO metadata text
		$content = preg_replace( '/\b(?:Normal|false|true)\s+\d+(?:\s+\d+)*(?:\s+(?:false|true))*(?:\s+[A-Z][-A-Z]*)*\s*/i', '', $content );
		
		// Remove standalone MSO table class definitions
		$content = preg_replace( '/\btable\.Mso\w+\s*\{[^}]+\}/i', '', $content );
		
		// Clean up multiple consecutive whitespace/newlines
		$content = preg_replace( '/\s+/', ' ', $content );
		
		// Trim whitespace
		$content = trim( $content );
		
		return $content;
	}

	/**
	 * Render clean print page without WordPress headers/footers.
	 *
	 * @since 1.2.0
	 * @param object $message Message object with decrypted content.
	 * @param array  $files   Array of file objects.
	 */
	private function render_print_page( $message, $files ) {
		// Enqueue print page assets using WordPress functions
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_print_page_assets' ) );
		
		// Trigger the enqueue action to register our assets
		do_action( 'wp_enqueue_scripts' );
		
		// Output clean HTML for printing
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e( 'Print Message', 'dfx-parish-retreat-letters' ); ?></title>
			<?php
			// Output only the enqueued styles (no other wp_head content)
			wp_print_styles();
			?>
		</head>
		<body>
			<?php
			// Display recipient name first, then sender name
			echo '<div class="message-header" style="margin-bottom: 20px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 10px;">';
			
			// Display recipient (attendant) name if available
			if ( ! empty( $message->attendant_name ) ) {
				echo esc_html__( 'To', 'dfx-parish-retreat-letters' ) . ': ' . esc_html( $message->attendant_name );
				if ( ! empty( $message->attendant_surnames ) ) {
					echo ' ' . esc_html( $message->attendant_surnames );
				}
				echo '<br>';
			}
			
			// Display sender name if available
			if ( ! empty( $message->sender_name ) ) {
				echo esc_html__( 'From', 'dfx-parish-retreat-letters' ) . ': ' . esc_html( $message->sender_name );
			}
			
			echo '</div>';

			if ( $message->message_type === 'text' ) {
				// For text messages, display the content
				// Clean MSO artifacts that may exist in older messages
				$cleaned_content = $this->clean_mso_content( $message->decrypted_content );
				echo '<div class="message-content">';
				echo wp_kses_post( $cleaned_content );
				echo '</div>';
			} elseif ( $message->message_type === 'file' && ! empty( $files ) ) {
				// Initialize file model for decryption
				$file_model = new DFXPRL_MessageFile();

				// Count image files to determine if multi-image styling should be applied
				$image_files = array_filter( $files, function( $file ) {
					return strpos( $file->file_type, 'image/' ) === 0;
				});
				$is_multi_image = count( $image_files ) > 1;

				// For file messages, display the actual file content
				foreach ( $files as $file ) {
					$decrypted_file = $file_model->get_decrypted_file( $file->id );
					if ( $decrypted_file ) {
						$is_image = strpos( $file->file_type, 'image/' ) === 0;
						$css_class = ( $is_multi_image && $is_image ) ? 'file-content multi-image' : 'file-content';
						echo '<div class="' . esc_attr( $css_class ) . '">';

						// Handle different file types
						if ( $file->file_type === 'text/plain' ) {
							echo '<h3>' . esc_html( $decrypted_file['filename'] ) . '</h3>';
							echo '<div class="file-text">' . esc_html( $decrypted_file['content'] ) . '</div>';
						} elseif ( strpos( $file->file_type, 'image/' ) === 0 ) {
							// For images, create a data URL and display
							$image_data = base64_encode( $decrypted_file['content'] );
							echo '<h3>' . esc_html( $decrypted_file['filename'] ) . '</h3>';
							echo '<img src="data:' . esc_attr( $file->file_type ) . ';base64,' . esc_attr( $image_data ) . '" class="file-image" alt="' . esc_attr( $decrypted_file['filename'] ) . '">';
						} elseif ( $file->file_type === 'application/pdf' ) {
							// For PDFs, we'll try to embed them
							$pdf_data = base64_encode( $decrypted_file['content'] );
							echo '<h3>' . esc_html( $decrypted_file['filename'] ) . '</h3>';
							echo '<embed src="data:application/pdf;base64,' . esc_attr( $pdf_data ) . '" type="application/pdf" width="100%" height="800px" style="border: none;">';
						} elseif ( in_array( $file->file_type, array( 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ) ) ) {
							// For Word documents, show that they need to be opened separately
							echo '<h3>' . esc_html( $decrypted_file['filename'] ) . '</h3>';
							echo '<p>' . esc_html__( 'Word document content cannot be displayed for printing. Please download and print separately.', 'dfx-parish-retreat-letters' ) . '</p>';
							echo '<p>' . esc_html__( 'File size:', 'dfx-parish-retreat-letters' ) . ' ' . esc_html( size_format( $decrypted_file['size'] ) ) . '</p>';
						} else {
							// For other file types, try to display as text if possible
							$content = $decrypted_file['content'] ?? '';

							// Check if content is text-like
							if ( is_string( $content ) && mb_check_encoding( $content, 'UTF-8' ) && ctype_print( str_replace( array( "\n", "\r", "\t" ), '', $content ) ) ) {
								echo '<h3>' . esc_html( $decrypted_file['filename'] ) . '</h3>';
								echo '<div class="file-text">' . esc_html( $content ) . '</div>';
							} else {
								// Binary file that can't be printed as text
								echo '<h3>' . esc_html( $decrypted_file['filename'] ) . '</h3>';
								echo '<p>' . sprintf(
									/* translators: %1$s: file type, %2$s: formatted file size */
									esc_html__( 'File type: %1$s, Size: %2$s - Cannot display content for printing.', 'dfx-parish-retreat-letters' ),
									esc_html( $file->file_type ),
									esc_html( size_format( $decrypted_file['size'] ) )
								) . '</p>';
							}
						}

						echo '</div>';
					}
				}
			}
			?>

			<?php
			// Output only the enqueued scripts (no other wp_footer content)
			wp_print_scripts();
			?>
		</body>
		</html>
		<?php
		exit; // Important: exit after rendering to prevent WordPress from adding headers/footers
	}

	/**
	 * Enqueue assets for the print page.
	 *
	 * @since 25.12.10
	 */
	public function enqueue_print_page_assets() {
		// Enqueue print page styles
		wp_enqueue_style(
			'dfxprl-print-page',
			DFXPRL_PLUGIN_URL . 'assets/css/print-page.css',
			array(),
			DFXPRL_VERSION
		);

		// Enqueue print page script
		wp_enqueue_script(
			'dfxprl-print-page',
			DFXPRL_PLUGIN_URL . 'assets/js/print-page.js',
			array(),
			DFXPRL_VERSION,
			true
		);
	}

	/**
	 * Schedule cleanup tasks.
	 *
	 * @since 1.3.0
	 */
	public function schedule_cleanup_tasks() {
		if ( ! wp_next_scheduled( 'dfxprl_retreat_cleanup_hook' ) ) {
			wp_schedule_event( time(), 'daily', 'dfxprl_retreat_cleanup_hook' );
		}
	}

	/**
	 * Run scheduled cleanup tasks.
	 *
	 * @since 1.3.0
	 */
	public function run_cleanup_tasks() {
		// Clean up expired invitations
		if ( $this->invitations ) {
			$this->invitations->cleanup_expired_invitations();
		}

		// Clean up old permissions
		if ( $this->permissions ) {
			$this->permissions->cleanup_permissions();
		}

		// Anonymize old IP addresses
		if ( $this->security ) {
			$this->security->anonymize_old_ip_addresses();
		}
	}

	/**
	 * Check if the current theme supports WordPress blocks.
	 *
	 * @since 1.5.0
	 * @return bool True if theme supports blocks, false otherwise.
	 */
	private function theme_supports_blocks() {
		// Check for block theme support
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ||
			   current_theme_supports( 'block-templates' ) ||
			   current_theme_supports( 'block-template-parts' );
	}

	/**
	 * Check if the current theme is a block theme.
	 *
	 * @since 1.5.3
	 * @return bool True if it's a block theme, false otherwise.
	 */
	private function is_block_theme() {
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	/**
	 * Render theme-agnostic header.
	 * For block themes: renders minimal HTML structure with wp_head()
	 * For classic themes: uses get_header()
	 *
	 * @since 1.5.3
	 */
	private function render_theme_header() {
		if ( $this->is_block_theme() ) {
			?>
			<!DOCTYPE html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<?php wp_head(); ?>
			</head>
			<body <?php body_class(); ?>>
			<?php wp_body_open(); ?>
			<?php
		} else {
			get_header();
		}
	}

	/**
	 * Render theme-agnostic footer.
	 * For block themes: renders minimal HTML structure with wp_footer()
	 * For classic themes: uses get_footer()
	 *
	 * @since 1.5.3
	 */
	private function render_theme_footer() {
		if ( $this->is_block_theme() ) {
			// Remove deprecated skip link hook to prevent deprecation warning
			if ( function_exists( 'wp_enqueue_block_template_skip_link' ) ) {
				remove_action( 'wp_footer', 'the_block_template_skip_link' );
				wp_enqueue_block_template_skip_link();
			}
			?>
			<?php wp_footer(); ?>
			</body>
			</html>
			<?php
		} else {
			get_footer();
		}
	}

	/**
	 * Render a custom header or footer block.
	 *
	 * @since 1.5.0
	 * @param string|int|null $block_selection Block selection (prefixed or legacy format).
	 * @return bool True if block was rendered, false otherwise.
	 */
	private function render_custom_block( $block_selection ) {
		if ( empty( $block_selection ) ) {
			return false;
		}

		// Parse the selection to determine type and ID
		$type = null;
		$id = null;

		if ( is_numeric( $block_selection ) ) {
			// Legacy format - treat as reusable block
			$type = 'block';
			$id = absint( $block_selection );
		} elseif ( strpos( $block_selection, 'block_' ) === 0 ) {
			// Reusable block
			$type = 'block';
			$id = absint( str_replace( 'block_', '', $block_selection ) );
		} elseif ( strpos( $block_selection, 'templatepart_' ) === 0 ) {
			// Template part
			$type = 'templatepart';
			$id = absint( str_replace( 'templatepart_', '', $block_selection ) );
		} elseif ( strpos( $block_selection, 'pattern_' ) === 0 ) {
			// Block pattern post
			$type = 'pattern';
			$id = absint( str_replace( 'pattern_', '', $block_selection ) );
		} elseif ( strpos( $block_selection, 'registered_' ) === 0 ) {
			// Registered pattern
			$type = 'registered_pattern';
			$id = str_replace( 'registered_', '', $block_selection );
		} else {
			// Debug log unrecognized format
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'DFX Parish Retreat Letters: Unrecognized block selection format: ' . $block_selection ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log -- Conditional debug logging only when WP_DEBUG is enabled
			}
			return false;
		}

		// Debug log what we're trying to render
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'DFX Parish Retreat Letters: Attempting to render ' . $type . ' with ID: ' . $id ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log -- Conditional debug logging only when WP_DEBUG is enabled
		}

		// Render based on type
		switch ( $type ) {
			case 'block':
				$result = $this->render_reusable_block( $id );
				break;
			case 'templatepart':
				$result = $this->render_template_part( $id );
				break;
			case 'pattern':
				$result = $this->render_pattern_post( $id );
				break;
			case 'registered_pattern':
				$result = $this->render_registered_pattern( $id );
				break;
			default:
				$result = false;
		}

		// Debug log result
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'DFX Parish Retreat Letters: Render result for ' . $type . ' ID ' . $id . ': ' . ( $result ? 'success' : 'failed' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log -- Conditional debug logging only when WP_DEBUG is enabled
		}

		return $result;
	}

	/**
	 * Render a reusable block (wp_block post type).
	 *
	 * @since 1.5.1
	 * @param int $block_id Block post ID.
	 * @return bool True if rendered successfully, false otherwise.
	 */
	private function render_reusable_block( $block_id ) {
		if ( ! $block_id ) {
			return false;
		}

		// Get the block post
		$block_post = get_post( $block_id );
		if ( ! $block_post || $block_post->post_type !== 'wp_block' ) {
			return false;
		}

		// Parse and render the block content
		$block_content = $block_post->post_content;
		if ( ! empty( $block_content ) ) {
			// Parse blocks and render them
			$blocks = parse_blocks( $block_content );
			foreach ( $blocks as $block ) {
				echo render_block( $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			return true;
		}

		return false;
	}

	/**
	 * Render a template part (wp_template_part post type).
	 *
	 * @since 1.5.2
	 * @param int $template_part_id Template part post ID.
	 * @return bool True if rendered successfully, false otherwise.
	 */
	private function render_template_part( $template_part_id ) {
		if ( ! $template_part_id ) {
			echo '<!-- DFX Debug: Template part ID is empty -->';
			return false;
		}

		// Get the template part post
		$template_part_post = get_post( $template_part_id );
		if ( ! $template_part_post || $template_part_post->post_type !== 'wp_template_part' ) {
			echo '<!-- DFX Debug: Template part post not found or wrong type. Post type: ' . ( $template_part_post ? esc_html( $template_part_post->post_type ) : 'null' ) . ' -->';
			return false;
		}

		echo '<!-- DFX Debug: Found template part: ' . esc_html( $template_part_post->post_title ) . ' (ID: ' . esc_html( $template_part_id ) . ') -->';

		// Get template part content
		$template_content = $template_part_post->post_content;
		if ( empty( $template_content ) ) {
			echo '<!-- DFX Debug: Template part content is empty -->';
			return false;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Debug comment with integer value
		echo '<!-- DFX Debug: Template part content length: ' . absint( strlen( $template_content ) ) . ' characters -->';

		// Template parts in block themes often use more complex block structures
		// We need to ensure WordPress's block rendering context is available
		try {
			// Set up global post data for proper block rendering context
			global $post;
			$original_post = $post;
			$post = $template_part_post;
			setup_postdata( $post );

			echo '<!-- DFX Debug: Starting template part rendering -->';

			// Parse and render blocks with proper WordPress context
			if ( function_exists( 'do_blocks' ) ) {
				// Use do_blocks if available (WordPress 5.0+)
				echo '<!-- DFX Debug: Using do_blocks function -->';
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress core function renders blocks
				echo do_blocks( $template_content );
			} else {
				// Fallback to manual block parsing
				echo '<!-- DFX Debug: Using manual block parsing -->';
				$blocks = parse_blocks( $template_content );

				if ( ! empty( $blocks ) ) {
					echo '<!-- DFX Debug: Parsed ' . count( $blocks ) . ' blocks -->';
					foreach ( $blocks as $index => $block ) {
						echo '<!-- DFX Debug: Rendering block ' . absint( $index ) . ' -->';
						if ( function_exists( 'render_block' ) ) {
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress core function renders blocks
							echo render_block( $block );
						}
					}
				} else {
					echo '<!-- DFX Debug: No blocks parsed from content -->';
				}
			}

			echo '<!-- DFX Debug: Template part rendering completed -->';

			// Restore original post data
			$post = $original_post;
			if ( $original_post ) {
				setup_postdata( $post );
			} else {
				wp_reset_postdata();
			}

			return true;

		} catch ( Exception $e ) {
			echo '<!-- DFX Debug: Exception during template part rendering: ' . esc_html( $e->getMessage() ) . ' -->';

			// Restore post data in case of error
			global $post;
			$post = $original_post ?? null;
			if ( $original_post ) {
				setup_postdata( $post );
			} else {
				wp_reset_postdata();
			}

			// Log error if WordPress debug is enabled
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'DFX Parish Retreat Letters: Template part rendering error: ' . $e->getMessage() );
			}
		}

		return false;
	}

	/**
	 * Render a pattern post (wp_block_pattern post type if available).
	 *
	 * @since 1.5.1
	 * @param int $pattern_id Pattern post ID.
	 * @return bool True if rendered successfully, false otherwise.
	 */
	private function render_pattern_post( $pattern_id ) {
		if ( ! $pattern_id || ! post_type_exists( 'wp_block_pattern' ) ) {
			return false;
		}

		// Get the pattern post
		$pattern_post = get_post( $pattern_id );
		if ( ! $pattern_post || $pattern_post->post_type !== 'wp_block_pattern' ) {
			return false;
		}

		// Parse and render the pattern content
		$pattern_content = $pattern_post->post_content;
		if ( ! empty( $pattern_content ) ) {
			// Parse blocks and render them
			$blocks = parse_blocks( $pattern_content );
			foreach ( $blocks as $block ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress core function renders blocks
				echo render_block( $block );
			}
			return true;
		}

		return false;
	}

	/**
	 * Render a registered pattern.
	 *
	 * @since 1.5.1
	 * @param string $pattern_name Pattern name.
	 * @return bool True if rendered successfully, false otherwise.
	 */
	private function render_registered_pattern( $pattern_name ) {
		if ( ! $pattern_name || ! function_exists( 'WP_Block_Patterns_Registry' ) ) {
			return false;
		}

		$pattern_registry = WP_Block_Patterns_Registry::get_instance();
		if ( ! method_exists( $pattern_registry, 'get_registered' ) ) {
			return false;
		}

		$pattern = $pattern_registry->get_registered( $pattern_name );
		if ( ! $pattern || ! isset( $pattern['content'] ) ) {
			return false;
		}

		// Parse and render the pattern content
		$pattern_content = $pattern['content'];
		if ( ! empty( $pattern_content ) ) {
			// Parse blocks and render them
			$blocks = parse_blocks( $pattern_content );
			foreach ( $blocks as $block ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress core function renders blocks
				echo render_block( $block );
			}
			return true;
		}

		return false;
	}

	/**
	 * Get retreat data by attendant token (includes custom header/footer block IDs).
	 *
	 * @since 1.5.0
	 * @param string $token Message URL token.
	 * @return object|null Retreat object with block IDs or null if not found.
	 */
	private function get_retreat_by_token( $token ) {
		global $wpdb;

		$attendants_table = $this->database->get_attendants_table();
		$retreats_table = $this->database->get_retreats_table();

		$retreat = $wpdb->get_row( $wpdb->prepare(
			"SELECT r.custom_header_block_id, r.custom_footer_block_id, r.id, r.name
			 FROM `{$attendants_table}` a
			 INNER JOIN `{$retreats_table}` r ON a.retreat_id = r.id
			 WHERE a.message_url_token = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$token
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $retreat;
	}
}
