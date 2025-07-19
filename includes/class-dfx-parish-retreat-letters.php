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
	 * The security instance.
	 *
	 * @since    1.2.0
	 * @access   protected
	 * @var      DFX_Parish_Retreat_Letters_Security    $security    Manages security operations.
	 */
	protected $security;

	/**
	 * The GDPR compliance instance.
	 *
	 * @since    1.2.0
	 * @access   protected
	 * @var      DFX_Parish_Retreat_Letters_GDPR    $gdpr    Manages GDPR compliance.
	 */
	protected $gdpr;

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
		$this->init_security();
		$this->init_gdpr();
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
	 * - DFX_Parish_Retreat_Letters_I18n. Defines internationalization functionality.
	 * - DFX_Parish_Retreat_Letters_Database. Manages database operations.
	 * - DFX_Parish_Retreat_Letters_Security. Handles encryption and security.
	 * - DFX_Parish_Retreat_Letters_Retreat. Handles retreat CRUD operations.
	 * - DFX_Parish_Retreat_Letters_Attendant. Handles attendant CRUD operations.
	 * - DFX_Parish_Retreat_Letters_ConfidentialMessage. Handles message CRUD operations.
	 * - DFX_Parish_Retreat_Letters_MessageFile. Handles file CRUD operations.
	 * - DFX_Parish_Retreat_Letters_PrintLog. Handles print logging.
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
	 * Initialize the security management.
	 *
	 * @since    1.2.0
	 * @access   private
	 */
	private function init_security() {
		$this->security = DFX_Parish_Retreat_Letters_Security::get_instance();
	}

	/**
	 * Initialize the GDPR compliance management.
	 *
	 * @since    1.2.0
	 * @access   private
	 */
	private function init_gdpr() {
		$this->gdpr = DFX_Parish_Retreat_Letters_GDPR::get_instance();
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
	 * Initialize public hooks for message submission.
	 *
	 * @since    1.2.0
	 * @access   private
	 */
	private function init_public_hooks() {
		add_action( 'wp_loaded', array( $this, 'handle_message_url_routing' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_scripts' ) );
		add_action( 'wp_ajax_nopriv_dfx_submit_message', array( $this, 'handle_message_submission' ) );
		add_action( 'wp_ajax_dfx_submit_message', array( $this, 'handle_message_submission' ) );
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

	/**
	 * Handle URL routing for message submission.
	 *
	 * @since 1.2.0
	 */
	public function handle_message_url_routing() {
		// Check if we're on a message URL pattern: /messages/[token] or /print/[token]
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		
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
		$site_url = parse_url( home_url(), PHP_URL_PATH );
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
			} else {
				$this->display_message_form( $token );
			}
			exit;
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
			// Invalid token - show 404
			status_header( 404 );
			nocache_headers();
			include get_404_template();
			return;
		}

		// Rate limiting check
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

		// Set up WordPress environment for form
		status_header( 200 );
		nocache_headers();
		
		// Include WordPress header
		get_header();
		
		// Display the form
		$this->render_message_form( $attendant );
		
		// Include WordPress footer
		get_footer();
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
		
		$attendant = $wpdb->get_row( $wpdb->prepare(
			"SELECT a.*, r.name as retreat_name, r.location as retreat_location, r.start_date, r.end_date, r.custom_message
			 FROM {$this->database->get_attendants_table()} a
			 INNER JOIN {$this->database->get_retreats_table()} r ON a.retreat_id = r.id
			 WHERE a.message_url_token = %s",
			$token
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return $attendant;
	}

	/**
	 * Render the message submission form.
	 *
	 * @since 1.2.0
	 * @param object $attendant Attendant object.
	 */
	private function render_message_form( $attendant ) {
		?>
		<div class="dfx-message-form-container">
			<div class="dfx-message-form-content">
				<h1><?php esc_html_e( 'Send a Confidential Message', 'dfx-parish-retreat-letters' ); ?></h1>
				
				<div class="dfx-retreat-info">
					<h2><?php esc_html_e( 'For Retreat Attendant', 'dfx-parish-retreat-letters' ); ?></h2>
					<p><strong><?php echo esc_html( $attendant->name . ' ' . $attendant->surnames ); ?></strong></p>
					<p><?php echo esc_html( $attendant->retreat_name ); ?> - <?php echo esc_html( $attendant->retreat_location ); ?></p>
					<p><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $attendant->start_date ) ) ); ?> - <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $attendant->end_date ) ) ); ?></p>
				</div>

				<?php if ( ! empty( $attendant->custom_message ) ) : ?>
				<div class="dfx-custom-message">
					<?php echo wp_kses_post( wpautop( $attendant->custom_message ) ); ?>
				</div>
				<?php endif; ?>

				<div id="dfx-message-notices"></div>

				<form id="dfx-message-form" enctype="multipart/form-data">
					<?php wp_nonce_field( 'dfx_submit_message', 'message_nonce' ); ?>
					<input type="hidden" name="action" value="dfx_submit_message">
					<input type="hidden" name="attendant_id" value="<?php echo esc_attr( $attendant->id ); ?>">

					<div class="dfx-form-group">
						<label for="sender_name"><?php esc_html_e( 'Your Name', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
						<input type="text" id="sender_name" name="sender_name" required maxlength="255" placeholder="<?php esc_attr_e( 'Enter your name', 'dfx-parish-retreat-letters' ); ?>">
					</div>

					<div class="dfx-message-mode">
						<h3><?php esc_html_e( 'Choose message type:', 'dfx-parish-retreat-letters' ); ?></h3>
						<div class="dfx-mode-selector">
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

					<div class="dfx-form-group" id="dfx-text-group">
						<label for="message_content"><?php esc_html_e( 'Your Message', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
						<div id="dfx-editor-container">
							<div class="dfx-editor-toolbar">
								<button type="button" data-command="bold" title="<?php esc_attr_e( 'Bold', 'dfx-parish-retreat-letters' ); ?>"><strong>B</strong></button>
								<button type="button" data-command="italic" title="<?php esc_attr_e( 'Italic', 'dfx-parish-retreat-letters' ); ?>"><em>I</em></button>
								<button type="button" data-command="underline" title="<?php esc_attr_e( 'Underline', 'dfx-parish-retreat-letters' ); ?>"><u>U</u></button>
								<button type="button" data-command="insertUnorderedList" title="<?php esc_attr_e( 'Bullet List', 'dfx-parish-retreat-letters' ); ?>">• <?php esc_html_e( 'List', 'dfx-parish-retreat-letters' ); ?></button>
								<button type="button" data-command="insertOrderedList" title="<?php esc_attr_e( 'Numbered List', 'dfx-parish-retreat-letters' ); ?>">1. <?php esc_html_e( 'List', 'dfx-parish-retreat-letters' ); ?></button>
								<button type="button" data-command="undo" title="<?php esc_attr_e( 'Undo', 'dfx-parish-retreat-letters' ); ?>"><?php esc_html_e( 'Undo', 'dfx-parish-retreat-letters' ); ?></button>
								<button type="button" data-command="redo" title="<?php esc_attr_e( 'Redo', 'dfx-parish-retreat-letters' ); ?>"><?php esc_html_e( 'Redo', 'dfx-parish-retreat-letters' ); ?></button>
							</div>
							<div id="message_content" contenteditable="true" class="dfx-editor" placeholder="<?php esc_attr_e( 'Write your confidential message here...', 'dfx-parish-retreat-letters' ); ?>"></div>
							<textarea id="message_content_hidden" name="message_content" style="display: none;"></textarea>
						</div>
						<p class="dfx-help-text"><?php esc_html_e( 'You can use the toolbar to format your text, create lists, and paste images. HTML is allowed but will be filtered for security.', 'dfx-parish-retreat-letters' ); ?></p>
					</div>

					<div class="dfx-form-group" id="dfx-file-group" style="display: none;">
						<label for="message_files"><?php esc_html_e( 'Attach Files', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
						<input type="file" id="message_files" name="message_files[]" multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif">
						<p class="dfx-help-text">
							<?php esc_html_e( 'Allowed file types: PDF, DOC, DOCX, TXT, JPG, PNG, GIF. Maximum 5MB per file.', 'dfx-parish-retreat-letters' ); ?>
						</p>
						<div id="dfx-file-list"></div>
					</div>

					<div class="dfx-form-group">
						<div class="dfx-captcha-container">
							<label for="captcha_answer"><?php esc_html_e( 'Security Check', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
							<div id="dfx-captcha-question"></div>
							<input type="text" id="captcha_answer" name="captcha_answer" required autocomplete="off">
							<input type="hidden" id="captcha_token" name="captcha_token">
						</div>
					</div>

					<div class="dfx-form-group">
						<button type="submit" id="dfx-submit-btn" class="dfx-submit-button">
							<span class="dfx-submit-text"><?php esc_html_e( 'Send Confidential Message', 'dfx-parish-retreat-letters' ); ?></span>
							<span class="dfx-loading-spinner" style="display: none;">
								<?php esc_html_e( 'Sending...', 'dfx-parish-retreat-letters' ); ?>
							</span>
						</button>
					</div>
				</form>

				<div class="dfx-privacy-notice">
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

		<style>
		.dfx-message-form-container {
			max-width: 800px;
			margin: 2rem auto;
			padding: 2rem;
			background: #fff;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
			border-radius: 8px;
		}

		.dfx-message-form-content h1 {
			color: #333;
			margin-bottom: 1rem;
			text-align: center;
		}

		.dfx-retreat-info {
			background: #f8f9fa;
			padding: 1rem;
			border-radius: 4px;
			margin-bottom: 2rem;
			border-left: 4px solid #007cba;
		}

		.dfx-retreat-info h2 {
			margin-top: 0;
			color: #007cba;
			font-size: 1.1rem;
		}

		.dfx-custom-message {
			background: #e7f3ff;
			padding: 1.5rem;
			border-radius: 4px;
			margin-bottom: 2rem;
			border-left: 4px solid #0073aa;
		}

		.dfx-custom-message p {
			margin-bottom: 1rem;
		}

		.dfx-custom-message p:last-child {
			margin-bottom: 0;
		}

		.dfx-message-mode {
			margin-bottom: 1.5rem;
			padding: 1rem;
			background: #f8f9fa;
			border-radius: 4px;
			border: 1px solid #ddd;
		}

		.dfx-message-mode h3 {
			margin-top: 0;
			margin-bottom: 1rem;
			color: #333;
		}

		.dfx-mode-selector {
			display: flex;
			gap: 2rem;
		}

		.dfx-mode-selector label {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			cursor: pointer;
			font-weight: 600;
		}

		.dfx-mode-selector input[type="radio"] {
			margin: 0;
		}

		.dfx-form-group {
			margin-bottom: 1.5rem;
		}

		.dfx-form-group label {
			display: block;
			margin-bottom: 0.5rem;
			font-weight: 600;
			color: #333;
		}

		.required {
			color: #d63384;
		}

		.dfx-form-group input[type="text"],
		.dfx-form-group textarea,
		.dfx-form-group input[type="file"] {
			width: 100%;
			padding: 0.75rem;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-size: 1rem;
		}

		.dfx-form-group textarea {
			resize: vertical;
			min-height: 150px;
		}

		.dfx-editor {
			min-height: 150px;
			max-height: 400px;
			overflow-y: auto;
			padding: 0.75rem;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-size: 1rem;
			font-family: inherit;
			line-height: 1.5;
			background: white;
			outline: none;
			margin-top: 0.5rem;
		}

		.dfx-editor:focus {
			border-color: #007cba;
			box-shadow: 0 0 0 1px #007cba;
		}

		.dfx-editor[contenteditable="true"]:empty:before {
			content: attr(placeholder);
			color: #999;
			pointer-events: none;
		}

		.dfx-editor.empty:before {
			content: attr(placeholder);
			color: #999;
			pointer-events: none;
		}

		.dfx-editor img {
			max-width: 100%;
			height: auto;
			border-radius: 4px;
			margin: 0.5rem 0;
		}

		.dfx-editor ul, .dfx-editor ol {
			margin: 1rem 0;
			padding-left: 2rem;
		}

		.dfx-editor li {
			margin-bottom: 0.25rem;
		}

		.dfx-editor-toolbar {
			margin-bottom: 0.5rem;
			display: flex;
			gap: 0.5rem;
			flex-wrap: wrap;
			padding: 0.5rem;
			background: #f8f9fa;
			border: 1px solid #ddd;
			border-radius: 4px;
		}

		.dfx-editor-toolbar button {
			padding: 0.375rem 0.75rem;
			border: 1px solid #ddd;
			background: white;
			cursor: pointer;
			border-radius: 3px;
			font-size: 0.875rem;
			transition: all 0.2s;
		}

		.dfx-editor-toolbar button:hover {
			background: #e9ecef;
			border-color: #adb5bd;
		}

		.dfx-editor-toolbar button:active,
		.dfx-editor-toolbar button.active {
			background: #007cba;
			color: white;
			border-color: #005a87;
		}

		.dfx-help-text {
			font-size: 0.9rem;
			color: #666;
			margin-top: 0.25rem;
		}

		.dfx-captcha-container {
			background: #f8f9fa;
			padding: 1rem;
			border-radius: 4px;
			border: 1px solid #ddd;
		}

		#dfx-captcha-question {
			font-weight: 600;
			margin-bottom: 0.5rem;
			color: #333;
		}

		.dfx-submit-button {
			background: #007cba;
			color: white;
			padding: 1rem 2rem;
			border: none;
			border-radius: 4px;
			font-size: 1.1rem;
			cursor: pointer;
			width: 100%;
			transition: background-color 0.3s;
		}

		.dfx-submit-button:hover:not(:disabled) {
			background: #005a87;
		}

		.dfx-submit-button:disabled {
			background: #6c757d;
			cursor: not-allowed;
		}

		.dfx-privacy-notice {
			margin-top: 2rem;
			padding: 1rem;
			background: #f8f9fa;
			border-radius: 4px;
		}

		.dfx-privacy-notice h3 {
			margin-top: 0;
			color: #333;
		}

		.dfx-privacy-notice ul {
			margin-bottom: 0;
		}

		.dfx-privacy-notice li {
			margin-bottom: 0.5rem;
		}

		.dfx-notice {
			padding: 1rem;
			margin-bottom: 1rem;
			border-radius: 4px;
		}

		.dfx-notice.success {
			background: #d4edda;
			color: #155724;
			border: 1px solid #c3e6cb;
		}

		.dfx-notice.error {
			background: #f8d7da;
			color: #721c24;
			border: 1px solid #f5c6cb;
		}

		#dfx-file-list {
			margin-top: 0.5rem;
		}

		.dfx-file-item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 0.5rem;
			background: #f8f9fa;
			border: 1px solid #ddd;
			border-radius: 4px;
			margin-bottom: 0.5rem;
		}

		.dfx-file-remove {
			background: #dc3545;
			color: white;
			border: none;
			padding: 0.25rem 0.5rem;
			border-radius: 2px;
			cursor: pointer;
			font-size: 0.8rem;
		}

		@media (max-width: 768px) {
			.dfx-message-form-container {
				margin: 1rem;
				padding: 1rem;
			}

			.dfx-mode-selector {
				flex-direction: column;
				gap: 1rem;
			}
		}
		</style>
		<?php
	}

	/**
	 * Enqueue scripts for the public message form.
	 *
	 * @since 1.2.0
	 */
	public function enqueue_public_scripts() {
		// Only enqueue on message URLs
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		
		// Ensure we have a valid string
		if ( ! is_string( $request_uri ) || empty( $request_uri ) ) {
			return;
		}
		
		$request_uri = strtok( $request_uri, '?' );
		if ( $request_uri === false ) {
			return;
		}
		
		// Get the site's base path (same logic as handle_message_url_routing)
		$site_url = parse_url( home_url(), PHP_URL_PATH );
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
			wp_enqueue_script( 'jquery' );
			
			// Inline script for message form functionality
			add_action( 'wp_footer', array( $this, 'output_message_form_script' ) );
		}
	}

	/**
	 * Output JavaScript for the message form.
	 *
	 * @since 1.2.0
	 */
	public function output_message_form_script() {
		?>
		<script>
		jQuery(document).ready(function($) {
			var editor = $('#message_content');
			var hiddenInput = $('#message_content_hidden');
			
			// Initialize editor
			if (editor.length) {
				// Set initial placeholder state
				if (editor.text().trim() === '') {
					editor.addClass('empty');
				}
				
				// Make sure contenteditable is properly set
				editor.attr('contenteditable', 'true');
			}
			
			// Generate and display CAPTCHA immediately
			generateCaptcha();
			
			// Message mode switching
			$('input[name="message_mode"]').on('change', function() {
				var mode = $(this).val();
				if (mode === 'text') {
					$('#dfx-text-group').show();
					$('#dfx-file-group').hide();
					hiddenInput.prop('required', true);
					$('#message_files').prop('required', false);
				} else {
					$('#dfx-text-group').hide();
					$('#dfx-file-group').show();
					hiddenInput.prop('required', false);
					$('#message_files').prop('required', true);
				}
			});
			
			// Editor functionality
			editor.on('input paste keyup', function() {
				// Sync contenteditable content to hidden textarea
				hiddenInput.val($(this).html());
				
				// Update placeholder visibility
				if ($(this).text().trim() === '') {
					$(this).addClass('empty');
				} else {
					$(this).removeClass('empty');
				}
			});
			
			// Handle paste events to clean up content
			editor.on('paste', function(e) {
				e.preventDefault();
				
				var paste = (e.originalEvent.clipboardData || window.clipboardData).getData('text/html');
				if (!paste) {
					paste = (e.originalEvent.clipboardData || window.clipboardData).getData('text/plain');
					paste = paste.replace(/\n/g, '<br>');
				}
				
				// Clean the pasted content
				var cleanHTML = cleanPastedContent(paste);
				document.execCommand('insertHTML', false, cleanHTML);
				
				// Update hidden input
				hiddenInput.val(editor.html());
			});
			
			// File handling
			$('#message_files').on('change', function() {
				displaySelectedFiles(this.files);
			});
			
			// Form submission
			$('#dfx-message-form').on('submit', function(e) {
				console.log('Form submit event triggered');
				e.preventDefault();
				e.stopPropagation();
				
				// Sync editor content before submission
				if (editor.length) {
					hiddenInput.val(editor.html());
				}
				
				console.log('About to call submitMessage()');
				submitMessage();
				
				// Ensure no actual form submission
				return false;
			});
			
			// Editor toolbar
			$('.dfx-editor-toolbar button').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				
				var command = $(this).data('command');
				var button = $(this);
				
				// Focus the editor first
				editor.focus();
				
				// Get the current selection
				var selection = window.getSelection();
				
				// Handle different commands
				switch(command) {
					case 'bold':
						applyFormat('b', 'font-weight: bold;');
						break;
					case 'italic':
						applyFormat('i', 'font-style: italic;');
						break;
					case 'underline':
						applyFormat('u', 'text-decoration: underline;');
						break;
					case 'insertUnorderedList':
						toggleList('ul');
						break;
					case 'insertOrderedList':
						toggleList('ol');
						break;
					case 'undo':
					case 'redo':
						// These still work with execCommand in most browsers
						try {
							document.execCommand(command, false, null);
						} catch (error) {
							console.warn('Undo/Redo not supported:', error);
						}
						break;
				}
				
				// Update content and toolbar states
				setTimeout(function() {
					hiddenInput.val(editor.html());
					updateToolbarStates();
				}, 10);
				
				// Keep focus on editor
				editor.focus();
			});
			
			function applyFormat(tag, style) {
				var selection = window.getSelection();
				
				if (selection.rangeCount === 0) {
					return;
				}
				
				var range = selection.getRangeAt(0);
				var selectedText = range.toString();
				
				if (selectedText.length === 0) {
					// No selection, just place cursor and toggle state for next typing
					return;
				}
				
				try {
					// Check if selection is already formatted
					var ancestor = range.commonAncestorContainer;
					var isFormatted = false;
					
					// Check if we're inside the formatting tag
					var parent = ancestor.nodeType === Node.TEXT_NODE ? ancestor.parentNode : ancestor;
					while (parent && parent !== editor[0]) {
						if (parent.tagName && parent.tagName.toLowerCase() === tag) {
							isFormatted = true;
							break;
						}
						parent = parent.parentNode;
					}
					
					if (isFormatted) {
						// Remove formatting by unwrapping
						document.execCommand('removeFormat', false, null);
					} else {
						// Apply formatting
						var formattedElement = document.createElement(tag);
						try {
							range.surroundContents(formattedElement);
						} catch (e) {
							// Fallback to execCommand for complex selections
							document.execCommand(tag === 'b' ? 'bold' : tag === 'i' ? 'italic' : 'underline', false, null);
						}
					}
				} catch (e) {
					// Fallback to execCommand
					var execCommand = tag === 'b' ? 'bold' : tag === 'i' ? 'italic' : 'underline';
					document.execCommand(execCommand, false, null);
				}
			}
			
			function toggleList(listType) {
				try {
					var command = listType === 'ul' ? 'insertUnorderedList' : 'insertOrderedList';
					document.execCommand(command, false, null);
				} catch (e) {
					console.warn('List formatting not supported:', e);
				}
			}
			
			// Update toolbar button states based on current selection
			function updateToolbarStates() {
				$('.dfx-editor-toolbar button').each(function() {
					var command = $(this).data('command');
					var isActive = false;
					
					try {
						// Check if cursor is within formatted text
						var selection = window.getSelection();
						if (selection.rangeCount > 0) {
							var range = selection.getRangeAt(0);
							var element = range.startContainer;
							
							// Traverse up to find formatting
							while (element && element !== editor[0]) {
								if (element.nodeType === Node.ELEMENT_NODE) {
									var tagName = element.tagName.toLowerCase();
									if ((command === 'bold' && tagName === 'b') ||
										(command === 'italic' && tagName === 'i') ||
										(command === 'underline' && tagName === 'u') ||
										(command === 'insertUnorderedList' && tagName === 'ul') ||
										(command === 'insertOrderedList' && tagName === 'ol')) {
										isActive = true;
										break;
									}
								}
								element = element.parentNode;
							}
						}
					} catch (e) {
						// Fallback to queryCommandState if available
						try {
							isActive = document.queryCommandState(command);
						} catch (e2) {
							// Command not supported
						}
					}
					
					$(this).toggleClass('active', isActive);
				});
			}
			
			// Update toolbar states when selection changes
			editor.on('mouseup keyup', function() {
				setTimeout(updateToolbarStates, 10);
			});
			
			function cleanPastedContent(html) {
				// Create a temporary div to clean the content
				var temp = $('<div>').html(html);
				
				// Remove potentially dangerous elements and attributes
				temp.find('script, style, meta, link').remove();
				temp.find('*').removeAttr('style class id onclick onload onerror');
				
				// Convert common formatting
				temp.find('div').replaceWith(function() {
					return '<p>' + $(this).html() + '</p>';
				});
				
				return temp.html();
			}

			function generateCaptcha() {
				var num1 = Math.floor(Math.random() * 10) + 1;
				var num2 = Math.floor(Math.random() * 10) + 1;
				var operations = ['+', '-', '*'];
				var operation = operations[Math.floor(Math.random() * operations.length)];
				
				var question = num1 + ' ' + operation + ' ' + num2 + ' = ?';
				var answer;
				
				switch(operation) {
					case '+': answer = num1 + num2; break;
					case '-': answer = num1 - num2; break;
					case '*': answer = num1 * num2; break;
				}
				
				// Set question text with proper string
				var questionElement = $('#dfx-captcha-question');
				if (questionElement.length) {
					questionElement.html('<?php echo esc_js( __( 'Please solve: ', 'dfx-parish-retreat-letters' ) ); ?><strong>' + question + '</strong>');
				} else {
					console.error('CAPTCHA question element not found');
				}
				
				// Set token and clear answer
				var tokenElement = $('#captcha_token');
				var answerElement = $('#captcha_answer');
				
				if (tokenElement.length) {
					try {
						tokenElement.val(btoa(answer.toString()));
					} catch(e) {
						console.error('Error encoding CAPTCHA token:', e);
					}
				} else {
					console.error('CAPTCHA token element not found');
				}
				
				if (answerElement.length) {
					answerElement.val('');
				} else {
					console.error('CAPTCHA answer element not found');
				}
				
				console.log('CAPTCHA generated: ' + question + ' = ' + answer);
			}

			function displaySelectedFiles(files) {
				var container = $('#dfx-file-list');
				container.empty();
				
				Array.from(files).forEach(function(file, index) {
					var fileItem = $('<div class="dfx-file-item">' +
						'<span>' + file.name + ' (' + formatFileSize(file.size) + ')</span>' +
						'<button type="button" class="dfx-file-remove" data-index="' + index + '"><?php echo esc_js( __( 'Remove', 'dfx-parish-retreat-letters' ) ); ?></button>' +
						'</div>');
					
					container.append(fileItem);
				});
			}

			function formatFileSize(bytes) {
				if (bytes === 0) return '0 Bytes';
				var k = 1024;
				var sizes = ['Bytes', 'KB', 'MB', 'GB'];
				var i = Math.floor(Math.log(bytes) / Math.log(k));
				return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
			}

			$(document).on('click', '.dfx-file-remove', function() {
				var fileInput = $('#message_files')[0];
				var files = Array.from(fileInput.files);
				var indexToRemove = parseInt($(this).data('index'));
				
				files.splice(indexToRemove, 1);
				
				var dt = new DataTransfer();
				files.forEach(file => dt.items.add(file));
				fileInput.files = dt.files;
				
				displaySelectedFiles(fileInput.files);
			});

			function submitMessage() {
				var form = $('#dfx-message-form')[0];
				var formData = new FormData(form);
				
				// Validate mode-specific requirements
				var mode = $('input[name="message_mode"]:checked').val();
				if (mode === 'text') {
					var content = hiddenInput.val().trim();
					// Also check the text content without HTML
					var textContent = editor.text().trim();
					if (!content || !textContent) {
						showNotice('<?php echo esc_js( __( 'Please enter a message.', 'dfx-parish-retreat-letters' ) ); ?>', 'error');
						return;
					}
				} else {
					var files = $('#message_files')[0].files;
					if (!files || files.length === 0) {
						showNotice('<?php echo esc_js( __( 'Please select at least one file.', 'dfx-parish-retreat-letters' ) ); ?>', 'error');
						return;
					}
				}
				
				// Validate CAPTCHA
				var userAnswer = $('#captcha_answer').val().trim();
				var expectedAnswerB64 = $('#captcha_token').val();
				
				if (!userAnswer) {
					showNotice('<?php echo esc_js( __( 'Please complete the security check.', 'dfx-parish-retreat-letters' ) ); ?>', 'error');
					return;
				}
				
				if (!expectedAnswerB64) {
					showNotice('<?php echo esc_js( __( 'Security check error. Please refresh the page.', 'dfx-parish-retreat-letters' ) ); ?>', 'error');
					return;
				}
				
				var expectedAnswer;
				try {
					expectedAnswer = atob(expectedAnswerB64);
				} catch (e) {
					showNotice('<?php echo esc_js( __( 'Security check error. Please try again.', 'dfx-parish-retreat-letters' ) ); ?>', 'error');
					generateCaptcha();
					return;
				}
				
				if (parseInt(userAnswer) != parseInt(expectedAnswer)) {
					showNotice('<?php echo esc_js( __( 'Incorrect security answer. Please try again.', 'dfx-parish-retreat-letters' ) ); ?>', 'error');
					generateCaptcha();
					$('#captcha_answer').val('');
					return;
				}
				
				// Add message mode to form data
				formData.append('message_mode', mode);
				
				// Disable submit button
				$('#dfx-submit-btn').prop('disabled', true);
				$('.dfx-submit-text').hide();
				$('.dfx-loading-spinner').show();
				
				$.ajax({
					url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
					type: 'POST',
					data: formData,
					processData: false,
					contentType: false,
					success: function(response) {
						if (response.success) {
							showNotice('<?php echo esc_js( __( 'Your message has been sent successfully and securely stored.', 'dfx-parish-retreat-letters' ) ); ?>', 'success');
							$('#dfx-message-form')[0].reset();
							editor.html('').addClass('empty');
							hiddenInput.val('');
							$('#dfx-file-list').empty();
							// Reset to text mode
							$('input[name="message_mode"][value="text"]').prop('checked', true).trigger('change');
							generateCaptcha();
						} else {
							var errorMessage = '<?php echo esc_js( __( 'An error occurred while sending your message.', 'dfx-parish-retreat-letters' ) ); ?>';
							if (response.data && response.data.message) {
								errorMessage = response.data.message;
							}
							showNotice(errorMessage, 'error');
						}
					},
					error: function(xhr, status, error) {
						console.error('AJAX Error:', status, error);
						showNotice('<?php echo esc_js( __( 'A network error occurred. Please try again.', 'dfx-parish-retreat-letters' ) ); ?>', 'error');
					},
					complete: function() {
						$('#dfx-submit-btn').prop('disabled', false);
						$('.dfx-submit-text').show();
						$('.dfx-loading-spinner').hide();
					}
				});
			}

			function showNotice(message, type) {
				var notice = $('<div class="dfx-notice ' + type + '">' + message + '</div>');
				$('#dfx-message-notices').html(notice);
				
				$('html, body').animate({
					scrollTop: notice.offset().top - 20
				}, 500);
				
				if (type === 'success') {
					setTimeout(function() {
						notice.fadeOut();
					}, 5000);
				}
			}
		});
		</script>
		<?php
	}

	/**
	 * Handle AJAX message submission.
	 *
	 * @since 1.2.0
	 */
	public function handle_message_submission() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['message_nonce'] ?? '', 'dfx_submit_message' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Rate limiting
		$ip_address = $this->security->get_user_ip();
		if ( ! $this->security->check_rate_limit( $ip_address, 3, 60 ) ) {
			$this->security->log_rate_limit_violation( $ip_address, 'message_submission' );
			wp_send_json_error( array( 'message' => __( 'Too many submission attempts. Please wait before trying again.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Validate CAPTCHA
		$user_answer = sanitize_text_field( $_POST['captcha_answer'] ?? '' );
		$captcha_token = sanitize_text_field( $_POST['captcha_token'] ?? '' );
		
		if ( empty( $user_answer ) || empty( $captcha_token ) ) {
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

		// Validate sender name (now required)
		$sender_name = sanitize_text_field( $_POST['sender_name'] ?? '' );
		if ( empty( trim( $sender_name ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Sender name is required.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Validate message mode
		$message_mode = sanitize_text_field( $_POST['message_mode'] ?? 'text' );
		if ( ! in_array( $message_mode, array( 'text', 'file' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid message mode.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Validate based on message mode
		$content = '';
		$message_type = 'text';
		
		if ( $message_mode === 'text' ) {
			// Validate message content
			$content = wp_kses_post( $_POST['message_content'] ?? '' );
			
			// Check if content has actual text, not just HTML tags
			$text_content = trim( wp_strip_all_tags( $content ) );
			if ( empty( $content ) || empty( $text_content ) ) {
				wp_send_json_error( array( 'message' => __( 'Message content is required.', 'dfx-parish-retreat-letters' ) ) );
			}
			$message_type = 'text';
		} else {
			// Validate file upload
			if ( empty( $_FILES['message_files']['name'][0] ) ) {
				wp_send_json_error( array( 'message' => __( 'At least one file is required.', 'dfx-parish-retreat-letters' ) ) );
			}
			$content = __( 'File upload message', 'dfx-parish-retreat-letters' );
			$message_type = 'file';
		}

		// Create message
		$message_model = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
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
		if ( $message_mode === 'file' && ! empty( $_FILES['message_files']['name'][0] ) ) {
			$this->handle_file_uploads( $message_id, $_FILES['message_files'] );
		}

		wp_send_json_success( array( 'message' => __( 'Message sent successfully.', 'dfx-parish-retreat-letters' ) ) );
	}

	/**
	 * Handle file uploads for a message.
	 *
	 * @since 1.2.0
	 * @param int   $message_id Message ID.
	 * @param array $files      Files array from $_FILES.
	 */
	private function handle_file_uploads( $message_id, $files ) {
		$file_model = new DFX_Parish_Retreat_Letters_MessageFile();
		
		$file_count = count( $files['name'] );
		
		for ( $i = 0; $i < $file_count; $i++ ) {
			if ( $files['error'][$i] !== UPLOAD_ERR_OK ) {
				continue;
			}
			
			$file_data = array(
				'name'     => $files['name'][$i],
				'tmp_name' => $files['tmp_name'][$i],
				'size'     => $files['size'][$i],
				'type'     => $files['type'][$i],
				'error'    => $files['error'][$i],
			);
			
			$validated_file = $this->security->validate_file_upload( $file_data );
			if ( ! $validated_file ) {
				continue;
			}
			
			$file_model_data = array(
				'message_id'        => $message_id,
				'original_filename' => $validated_file['name'],
				'file_type'         => $validated_file['type'],
				'file_size'         => $validated_file['size'],
				'tmp_name'          => $validated_file['tmp_name'],
			);
			
			$file_model->create( $file_model_data );
		}
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
			wp_die( __( 'Invalid print request.', 'dfx-parish-retreat-letters' ) );
		}

		$message_id = get_transient( 'dfx_print_token_' . $token );
		if ( ! $message_id ) {
			wp_die( __( 'Print token expired or invalid.', 'dfx-parish-retreat-letters' ) );
		}

		// Delete the token after use
		delete_transient( 'dfx_print_token_' . $token );

		// Initialize models
		$message_model = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
		$file_model = new DFX_Parish_Retreat_Letters_MessageFile();

		// Get message with decrypted content
		$message = $message_model->get_with_decrypted_content( $message_id );
		if ( ! $message ) {
			wp_die( __( 'Message not found.', 'dfx-parish-retreat-letters' ) );
		}

		// Get attached files if any
		$files = $file_model->get_by_message( $message_id );

		// For file messages with a single file, serve the file directly
		if ( $message->message_type === 'file' && count( $files ) === 1 ) {
			$this->serve_file_directly( $files[0], $file_model );
			return;
		}

		// For all other cases, render the clean print page
		$this->render_print_page( $message, $files );
	}

	/**
	 * Serve file directly for printing.
	 *
	 * @since 1.2.1
	 * @param object $file File object.
	 * @param DFX_Parish_Retreat_Letters_MessageFile $file_model File model instance.
	 */
	private function serve_file_directly( $file, $file_model ) {
		$decrypted_file = $file_model->get_decrypted_file( $file->id );
		if ( ! $decrypted_file ) {
			wp_die( __( 'File not found.', 'dfx-parish-retreat-letters' ) );
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
		echo $decrypted_file['content'];
		exit;
	}

	/**
	 * Render clean print page without WordPress headers/footers.
	 *
	 * @since 1.2.0
	 * @param object $message Message object with decrypted content.
	 * @param array  $files   Array of file objects.
	 */
	private function render_print_page( $message, $files ) {
		// Output clean HTML for printing
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<title><?php esc_html_e( 'Print Message', 'dfx-parish-retreat-letters' ); ?></title>
			<style>
				body {
					font-family: Arial, sans-serif;
					line-height: 1.6;
					margin: 20px;
					color: #333;
				}
				.message-content {
					margin: 0;
					padding: 0;
				}
				.file-content {
					margin-top: 20px;
					padding: 20px;
					border: 1px solid #ddd;
					background: #f9f9f9;
				}
				.file-content h3 {
					margin-top: 0;
				}
				.file-text {
					white-space: pre-wrap;
					font-family: 'Courier New', monospace;
					background: white;
					padding: 15px;
					border: 1px solid #ccc;
				}
				.file-image {
					max-width: 100%;
					height: auto;
				}
				@media print {
					body { margin: 0; }
					.no-print { display: none !important; }
				}
			</style>
		</head>
		<body>
			<?php
			if ( $message->message_type === 'text' ) {
				// For text messages, display the content
				echo '<div class="message-content">';
				echo wp_kses_post( $message->decrypted_content );
				echo '</div>';
			} elseif ( $message->message_type === 'file' && ! empty( $files ) ) {
				// Initialize file model for decryption
				$file_model = new DFX_Parish_Retreat_Letters_MessageFile();
				
				// For file messages, display the actual file content
				foreach ( $files as $file ) {
					$decrypted_file = $file_model->get_decrypted_file( $file->id );
					if ( $decrypted_file ) {
						echo '<div class="file-content">';
						
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
									esc_html__( 'File type: %s, Size: %s - Cannot display content for printing.', 'dfx-parish-retreat-letters' ),
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
			
			<script>
				// Auto-trigger print dialog when page loads
				window.addEventListener('load', function() {
					window.print();
				});
				
				// Close tab after printing or when print dialog is cancelled
				window.addEventListener('afterprint', function() {
					window.close();
				});
			</script>
		</body>
		</html>
		<?php
		exit; // Important: exit after rendering to prevent WordPress from adding headers/footers
	}
}