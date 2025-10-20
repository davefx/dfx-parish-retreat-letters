<?php
/**
 * The admin interface class
 *
 * Handles all admin interface functionality for the plugin.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.0.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The admin interface class.
 *
 * This class handles all admin interface functionality including menus,
 * pages, and AJAX handlers.
 *
 * @since      1.0.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters_Admin {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 * @var DFX_Parish_Retreat_Letters_Admin|null
	 */
	private static $instance = null;

	/**
	 * The retreat model instance.
	 *
	 * @since 1.0.0
	 * @var DFX_Parish_Retreat_Letters_Retreat
	 */
	private $retreat_model;

	/**
	 * The attendant model instance.
	 *
	 * @since 1.0.0
	 * @var DFX_Parish_Retreat_Letters_Attendant
	 */
	private $attendant_model;

	/**
	 * The confidential message model instance.
	 *
	 * @since 1.2.0
	 * @var DFX_Parish_Retreat_Letters_ConfidentialMessage
	 */
	private $message_model;

	/**
	 * The message file model instance.
	 *
	 * @since 1.2.0
	 * @var DFX_Parish_Retreat_Letters_MessageFile
	 */
	private $file_model;

	/**
	 * The print log model instance.
	 *
	 * @since 1.2.0
	 * @var DFX_Parish_Retreat_Letters_PrintLog
	 */
	private $print_log_model;

	/**
	 * The security instance.
	 *
	 * @since 1.2.0
	 * @var DFX_Parish_Retreat_Letters_Security
	 */
	private $security;

	/**
	 * The GDPR compliance instance.
	 *
	 * @since 1.2.0
	 * @var DFX_Parish_Retreat_Letters_GDPR
	 */
	private $gdpr;

	/**
	 * The permissions management instance.
	 *
	 * @since 1.3.0
	 * @var DFX_Parish_Retreat_Letters_Permissions
	 */
	private $permissions;

	/**
	 * The global settings instance.
	 *
	 * @since 1.6.0
	 * @var DFX_Parish_Retreat_Letters_GlobalSettings
	 */
	private $global_settings;

	/**
	 * The responsible person model instance.
	 *
	 * @since 1.7.0
	 * @var DFX_Parish_Retreat_Letters_ResponsiblePerson
	 */
	private $responsible_person_model;

	/**
	 * Get the single instance of the class.
	 *
	 * @since 1.0.0
	 * @return DFX_Parish_Retreat_Letters_Admin
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->retreat_model = new DFX_Parish_Retreat_Letters_Retreat();
		$this->attendant_model = new DFX_Parish_Retreat_Letters_Attendant();
		$this->message_model = new DFX_Parish_Retreat_Letters_ConfidentialMessage();
		$this->file_model = new DFX_Parish_Retreat_Letters_MessageFile();
		$this->print_log_model = new DFX_Parish_Retreat_Letters_PrintLog();
		$this->responsible_person_model = new DFX_Parish_Retreat_Letters_ResponsiblePerson();
		$this->security = DFX_Parish_Retreat_Letters_Security::get_instance();
		$this->gdpr = DFX_Parish_Retreat_Letters_GDPR::get_instance();
		$this->permissions = DFX_Parish_Retreat_Letters_Permissions::get_instance();
		$this->global_settings = DFX_Parish_Retreat_Letters_GlobalSettings::get_instance();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_admin_form_submissions' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_dfx_prl_delete_retreat', array( $this, 'ajax_delete_retreat' ) );
		add_action( 'wp_ajax_dfx_prl_delete_attendant', array( $this, 'ajax_delete_attendant' ) );
		add_action( 'wp_ajax_dfx_prl_export_attendants_csv', array( $this, 'ajax_export_attendants_csv' ) );
		add_action( 'wp_ajax_dfx_prl_generate_message_url', array( $this, 'ajax_generate_message_url' ) );
		add_action( 'wp_ajax_dfx_prl_print_message', array( $this, 'ajax_print_message' ) );
		add_action( 'wp_ajax_dfx_prl_download_file', array( $this, 'ajax_download_file' ) );
		add_action( 'wp_ajax_dfx_prl_delete_message', array( $this, 'ajax_delete_message' ) );
		add_action( 'wp_ajax_dfx_prl_get_print_log', array( $this, 'ajax_get_print_log' ) );

		// Add new AJAX handlers for permission system
		add_action( 'wp_ajax_dfx_prl_search_users', array( $this, 'ajax_search_users' ) );
		add_action( 'wp_ajax_dfx_prl_grant_permission', array( $this, 'ajax_grant_permission' ) );
		add_action( 'wp_ajax_dfx_prl_revoke_permission', array( $this, 'ajax_revoke_permission' ) );
		add_action( 'wp_ajax_dfx_prl_send_invitation', array( $this, 'ajax_send_invitation' ) );
		add_action( 'wp_ajax_dfx_prl_cancel_invitation', array( $this, 'ajax_cancel_invitation' ) );
		add_action( 'wp_ajax_dfx_prl_reset_rate_limits', array( $this, 'ajax_reset_rate_limits' ) );

		// Add AJAX handlers for responsible persons
		add_action( 'wp_ajax_dfx_prl_add_responsible_person', array( $this, 'ajax_add_responsible_person' ) );
		add_action( 'wp_ajax_dfx_prl_delete_responsible_person', array( $this, 'ajax_delete_responsible_person' ) );
	}

	/**
	 * Handle admin form submissions early to avoid header conflicts.
	 *
	 * @since 1.0.0
	 */
	public function handle_admin_form_submissions() {
		// Only process POST requests
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return;
		}

		// Check for our plugin pages
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );
		$allowed_pages = array( 'dfx-prl-retreats', 'dfx-prl-retreats-add', 'dfx-prl-messages', 'dfx-prl-privacy', 'dfx-prl-global-settings' );

		if ( ! in_array( $page, $allowed_pages, true ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) );
		$retreat_id = absint( $_GET['retreat_id'] ?? 0 );

		// Handle different form submissions based on page and action
		switch ( $page ) {
			case 'dfx-prl-retreats':
				$this->handle_retreats_page_submissions( $action, $retreat_id );
				break;

			case 'dfx-prl-retreats-add':
				$this->handle_retreat_add_edit_submissions();
				break;

			case 'dfx-prl-messages':
				$this->handle_messages_page_submissions();
				break;

			case 'dfx-prl-privacy':
				$this->handle_privacy_page_submissions();
				break;

			case 'dfx-prl-global-settings':
				$this->handle_global_settings_page_submissions();
				break;
		}
	}

	/**
	 * Handle form submissions on the main retreats page.
	 *
	 * @since 1.0.0
	 * @param string $action The current action.
	 * @param int $retreat_id The retreat ID.
	 */
	private function handle_retreats_page_submissions( $action, $retreat_id ) {
		switch ( $action ) {
			case 'add_attendant':
				$this->handle_attendant_add_edit_submission( $retreat_id, 0 );
				break;

			case 'edit_attendant':
				$attendant_id = absint( $_GET['attendant_id'] ?? 0 );
				$this->handle_attendant_add_edit_submission( $retreat_id, $attendant_id );
				break;

			case 'attendants':
				// Handle CSV export early (it calls exit)
				$form_action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) );
				if ( $form_action === 'export_csv' ) {
					if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'dfx_prl_attendants_action' ) ) {
						wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
					}
					if ( ! $this->permissions->current_user_can_manage_retreat( $retreat_id ) ) {
						wp_die( esc_html__( 'You do not have permission to export attendants for this retreat.', 'dfx-parish-retreat-letters' ) );
					}
					$this->export_attendants_csv( $retreat_id );
					// The export method calls exit, so this won't be reached
				} else {
					$this->handle_attendant_list_actions( $retreat_id );
				}
				break;

			case 'import_attendants':
				$this->handle_csv_import( $retreat_id );
				break;

			default:
				// Main retreats list actions
				$this->handle_list_page_actions();
				break;
		}
	}

	/**
	 * Handle form submissions on the retreat add/edit page.
	 *
	 * @since 1.0.0
	 */
	private function handle_retreat_add_edit_submissions() {
		$retreat_id = absint( $_GET['edit'] ?? 0 );
		$this->handle_add_edit_submission( $retreat_id );
	}

	/**
	 * Handle form submissions on the messages page.
	 *
	 * @since 1.0.0
	 */
	private function handle_messages_page_submissions() {
		$this->handle_message_list_actions();
	}

	/**
	 * Handle form submissions on the privacy page.
	 *
	 * @since 1.0.0
	 */
	private function handle_privacy_page_submissions() {
		$this->handle_privacy_compliance_actions();
	}

	/**
	 * Add admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		// Check if user has any level of retreat access
		if ( ! $this->user_has_retreat_access() ) {
			return;
		}

		add_menu_page(
			__( 'Retreats', 'dfx-parish-retreat-letters' ),
			__( 'Retreats', 'dfx-parish-retreat-letters' ),
			'read', // Basic capability - we'll do specific checks in pages
			'dfx-prl-retreats',
			array( $this, 'retreats_list_page' ),
			'dashicons-groups',
			30
		);

		add_submenu_page(
			'dfx-prl-retreats',
			__( 'All Retreats', 'dfx-parish-retreat-letters' ),
			__( 'All Retreats', 'dfx-parish-retreat-letters' ),
			'read',
			'dfx-prl-retreats',
			array( $this, 'retreats_list_page' )
		);

		// Register the add/edit page for all users with retreat access (needed for retreat managers to edit their retreats)
		add_submenu_page(
			'dfx-prl-retreats',
			__( 'Add New Retreat', 'dfx-parish-retreat-letters' ),
			'', // Empty menu title to hide from menu display initially
			'read', // Use basic capability since we check specific permissions in the page method
			'dfx-prl-retreats-add',
			array( $this, 'retreat_add_page' )
		);

		// Only plugin administrators can see the "Add New" menu item and access privacy settings
		if ( $this->permissions->current_user_can_manage_plugin() ) {
			// Show the "Add New" menu item for plugin administrators by updating the submenu
			global $submenu;
			if ( isset( $submenu['dfx-prl-retreats'] ) ) {
				foreach ( $submenu['dfx-prl-retreats'] as $index => $menu_item ) {
					if ( $menu_item[2] === 'dfx-prl-retreats-add' ) {
						$submenu['dfx-prl-retreats'][$index][0] = __( 'Add New', 'dfx-parish-retreat-letters' );
						break;
					}
				}
			}

			add_submenu_page(
				'dfx-prl-retreats',
				__( 'Privacy & Compliance', 'dfx-parish-retreat-letters' ),
				__( 'Privacy & Compliance', 'dfx-parish-retreat-letters' ),
				'read', // Use basic capability since we already check permissions above
				'dfx-prl-privacy',
				array( $this, 'privacy_compliance_page' )
			);

			add_submenu_page(
				'dfx-prl-retreats',
				__( 'Global Settings', 'dfx-parish-retreat-letters' ),
				__( 'Global Settings', 'dfx-parish-retreat-letters' ),
				'read', // Use basic capability since we already check permissions above
				'dfx-prl-global-settings',
				array( $this, 'global_settings_page' )
			);
		}

		// Hidden submenu page for messages (accessed only through attendant links)
		add_submenu_page(
			'dfx-prl-retreats',
			__( 'Confidential Messages', 'dfx-parish-retreat-letters' ),
			'',  // Empty menu title to hide from menu display
			'read',
			'dfx-prl-messages',
			array( $this, 'messages_list_page' )
		);

		// Hide the messages submenu item from displaying in the menu
		add_action( 'admin_head', array( $this, 'hide_messages_submenu' ) );
	}

	/**
	 * Check if current user has any level of retreat access.
	 *
	 * @since 1.3.0
	 * @return bool True if user has retreat access.
	 */
	private function user_has_retreat_access() {
		// Plugin administrators always have access
		if ( $this->permissions->current_user_can_manage_plugin() ) {
			return true;
		}

		// Check if user has permissions for any retreats
		$accessible_retreats = $this->permissions->get_user_accessible_retreats( get_current_user_id() );
		return ! empty( $accessible_retreats );
	}

	/**
	 * Hide the messages submenu item from displaying in the admin menu.
	 *
	 * @since 1.2.1
	 */
	public function hide_messages_submenu() {
		$style = '#toplevel_page_dfx-prl-retreats .wp-submenu li[class*="dfx-prl-messages"] {
			display: none !important;
		}';

		wp_add_inline_style( 'dfx-prl-admin-styles', $style );
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		// Check for our admin pages - be more flexible with the hook detection
		$our_pages = array( 'dfx-prl-retreats', 'dfx-prl-messages', 'dfx-prl-privacy', 'dfx-prl-global-settings' );
		$is_our_page = false;

		// Ensure hook_suffix is a string
		$hook_suffix = (string) ( $hook_suffix ?? '' );

		foreach ( $our_pages as $page ) {
			// Ensure page is a string
			$page = (string) $page;
			if ( strpos( $hook_suffix, $page ) !== false ) {
				$is_our_page = true;
				break;
			}
		}

		// Also check for query parameters that indicate our pages
		if ( ! $is_our_page && isset( $_GET['page'] ) ) {
			$page_param = sanitize_text_field( wp_unslash( $_GET['page'] ) );
			// Ensure page_param is a string
			$page_param = (string) ( $page_param ?? '' );
			foreach ( $our_pages as $page ) {
				// Ensure page is a string
				$page = (string) $page;
				if ( strpos( $page_param, $page ) !== false ) {
					$is_our_page = true;
					break;
				}
			}
		}

		if ( ! $is_our_page ) {
			return;
		}

		wp_enqueue_script( 'jquery' );

		// Enqueue admin styles
		wp_enqueue_style(
			'dfx-prl-admin-styles',
			'', // No external file
			array(),
			DFX_PARISH_RETREAT_LETTERS_VERSION
		);

		// Add base admin styles
		$base_styles = '
		#permission-management-section {
			margin-top: 20px;
			padding: 20px;
		}
		.permission-badge {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
		}
		.permission-badge.permission-manager {
			background: #d4edda;
			color: #155724;
		}
		.permission-badge.permission-message_manager {
			background: #fff3cd;
			color: #856404;
		}
		.dfx-prl-permissions-list, .dfx-prl-invitations-list {
			margin-bottom: 15px;
		}
		.dfx-prl-permission-item, .dfx-prl-invitation-item {
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
			margin-bottom: 8px;
			background: #fff;
		}
		.dfx-prl-status-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 20px;
			margin-bottom: 30px;
		}
		.dfx-prl-status-item {
			padding: 20px;
			border: 1px solid #ddd;
			border-radius: 8px;
			background: #fff;
			text-align: center;
		}
		.dfx-prl-status-item.status-good {
			border-color: #46b450;
			background: #f7fcf7;
		}
		.dfx-prl-status-item.status-warning {
			border-color: #ffb900;
			background: #fffbf0;
		}
		';

		wp_add_inline_style( 'dfx-prl-admin-styles', $base_styles );

		// Enqueue Select2 for user management on global settings page
		if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'dfx-prl-global-settings' ) {      
			wp_enqueue_script( 'select2' );
			wp_enqueue_style( 'select2' );
		}

		wp_enqueue_script(
			'dfx-prl-retreats-admin',
			DFX_PARISH_RETREAT_LETTERS_PLUGIN_URL . 'includes/admin.js',
			array( 'jquery' ),
			DFX_PARISH_RETREAT_LETTERS_VERSION,
			true
		);

		wp_localize_script(
			'dfx-prl-retreats-admin',
			'dfxPRLAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'dfx_prl_retreats_nonce' ),
				'messages' => array(
					'confirmDelete' => __( 'Are you sure you want to delete this retreat?', 'dfx-parish-retreat-letters' ),
					'confirmDeleteAttendant' => __( 'Are you sure you want to delete this attendant?', 'dfx-parish-retreat-letters' ),
					'confirmDeleteMessage' => __( 'Are you sure you want to delete this message? This action cannot be undone.', 'dfx-parish-retreat-letters' ),
					'deleteRetreatTitle' => __( 'Delete Retreat - Confirmation Required', 'dfx-parish-retreat-letters' ),
					'deleteWarning' => __( 'WARNING: This action cannot be undone!', 'dfx-parish-retreat-letters' ),
					'deleteWarningAttendants' => __( 'All attendants for this retreat will be permanently deleted', 'dfx-parish-retreat-letters' ),
					'deleteWarningLetters' => __( 'All letters and related information will be permanently deleted', 'dfx-parish-retreat-letters' ),
					'deleteWarningPermanent' => __( 'This action is irreversible and cannot be restored', 'dfx-parish-retreat-letters' ),
					'typeRetreatName' => __( 'To confirm deletion, please type the exact retreat name below:', 'dfx-parish-retreat-letters' ),
					'retreatNamePlaceholder' => __( 'Type retreat name here...', 'dfx-parish-retreat-letters' ),
					'deleteButton' => __( 'Delete Forever', 'dfx-parish-retreat-letters' ),
					'cancelButton' => __( 'Cancel', 'dfx-parish-retreat-letters' ),
					'deleting' => __( 'Deleting...', 'dfx-parish-retreat-letters' ),
					'deleteError' => __( 'Error deleting retreat. Please try again.', 'dfx-parish-retreat-letters' ),
					'generating' => __( 'Generating URL...', 'dfx-parish-retreat-letters' ),
					'generateError' => __( 'Error generating message URL. Please try again.', 'dfx-parish-retreat-letters' ),
					'urlGenerated' => __( 'Message URL generated successfully!', 'dfx-parish-retreat-letters' ),
					'urlCopied' => __( 'URL copied to clipboard!', 'dfx-parish-retreat-letters' ),
					'copyError' => __( 'Failed to copy URL. Please copy it manually.', 'dfx-parish-retreat-letters' ),
					'printing' => __( 'Preparing for print...', 'dfx-parish-retreat-letters' ),
					'printError' => __( 'Error preparing message for print. Please try again.', 'dfx-parish-retreat-letters' ),
					'downloading' => __( 'Downloading...', 'dfx-parish-retreat-letters' ),
					'downloadError' => __( 'Error downloading file. Please try again.', 'dfx-parish-retreat-letters' ),
					'messageDeleted' => __( 'Message deleted successfully.', 'dfx-parish-retreat-letters' ),
				),
			)
		);
	}

	/**
	 * Display the retreats list page.
	 *
	 * @since 1.0.0
	 */
	public function retreats_list_page() {
		// Handle different actions
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) );
		$retreat_id = absint( $_GET['retreat_id'] ?? 0 );

		switch ( $action ) {
			case 'attendants':
				$this->attendants_list_page( $retreat_id );
				break;
			case 'add_attendant':
				$this->attendant_add_page( $retreat_id );
				break;
			case 'edit_attendant':
				$attendant_id = absint( $_GET['attendant_id'] ?? 0 );
				$this->attendant_edit_page( $retreat_id, $attendant_id );
				break;
			case 'import_attendants':
				$this->attendants_import_page( $retreat_id );
				break;
			case 'responsible_persons':
				$this->responsible_persons_page( $retreat_id );
				break;
			default:
				$this->display_retreats_list();
				break;
		}
	}

	/**
	 * Display the main retreats list.
	 *
	 * @since 1.0.0
	 */
	private function display_retreats_list() {
		// Get query parameters
		$search   = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$page_num = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$per_page = 20;

		// Get retreats filtered by user permissions
		$get_all_options = array(
			'search'   => $search,
			'per_page' => $per_page,
			'page'     => $page_num,
			'include_attendant_count' => true,
		);

		// Filter retreats based on user permissions
		if ( ! $this->permissions->current_user_can_manage_plugin() ) {
			$accessible_retreats = $this->permissions->get_user_accessible_retreats( get_current_user_id() );
			if ( empty( $accessible_retreats ) ) {
				// User has no retreat access
				$retreats = array();
				$total_items = 0;
			} else {
				$get_all_options['retreat_ids'] = $accessible_retreats;
				$retreats = $this->retreat_model->get_all( $get_all_options );
				$total_items = $this->retreat_model->get_count( $search, $accessible_retreats );
			}
		} else {
			// Plugin administrators see all retreats
			$retreats = $this->retreat_model->get_all( $get_all_options );
			$total_items = $this->retreat_model->get_count( $search );
		}

		$total_pages = ceil( $total_items / $per_page );

		$this->render_list_page( $retreats, $search, $page_num, $total_pages, $total_items );
	}

	/**
	 * Display the add/edit retreat page.
	 *
	 * @since 1.0.0
	 */
	public function retreat_add_page() {
		$retreat_id = absint( $_GET['edit'] ?? 0 );
		$retreat = $retreat_id ? $this->retreat_model->get( $retreat_id ) : null;

		// Check permissions
		if ( $retreat_id ) {
			// Editing existing retreat - check if user can manage this retreat
			if ( ! $this->permissions->current_user_can_manage_retreat( $retreat_id ) ) {
				wp_die( esc_html__( 'You do not have permission to edit this retreat.', 'dfx-parish-retreat-letters' ) );
			}
		} else {
			// Adding new retreat - only plugin administrators can do this
			if ( ! $this->permissions->current_user_can_manage_plugin() ) {
				wp_die( esc_html__( 'You do not have permission to add new retreats.', 'dfx-parish-retreat-letters' ) );
			}
		}

		// Handle form submission
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			// Form submission already handled in admin_init, just return to prevent double processing
			return;
		}

		$this->render_add_edit_page( $retreat );
	}

	/**
	 * Handle list page actions.
	 *
	 * @since 1.0.0
	 */
	private function handle_list_page_actions() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'dfx_prl_retreats_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) );
		$retreat_id = absint( $_POST['retreat_id'] ?? 0 );

		if ( $action === 'delete' && $retreat_id ) {
			if ( $this->retreat_model->delete( $retreat_id ) ) {
				$this->add_admin_notice( __( 'Retreat deleted successfully.', 'dfx-parish-retreat-letters' ), 'success' );
			} else {
				$this->add_admin_notice( __( 'Error deleting retreat.', 'dfx-parish-retreat-letters' ), 'error' );
			}
		}
	}

	/**
	 * Handle add/edit form submission.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id Retreat ID for editing, 0 for adding.
	 */
	private function handle_add_edit_submission( $retreat_id = 0 ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'dfx_prl_retreats_add_edit' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$data = array(
			'name'                       => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'location'                   => sanitize_text_field( wp_unslash( $_POST['location'] ?? '' ) ),
			'start_date'                 => sanitize_text_field( wp_unslash( $_POST['start_date'] ?? '' ) ),
			'end_date'                   => sanitize_text_field( wp_unslash( $_POST['end_date'] ?? '' ) ),
			'custom_message'             => wp_kses_post( wp_unslash( $_POST['custom_message'] ?? '' ) ),
			'disclaimer_text'            => wp_kses_post( wp_unslash( $_POST['disclaimer_text'] ?? '' ) ),
			'disclaimer_acceptance_text' => sanitize_text_field( wp_unslash( $_POST['disclaimer_acceptance_text'] ?? '' ) ),
			'custom_header_block_id'     => $this->parse_block_selection( sanitize_text_field( wp_unslash( $_POST['custom_header_block_id'] ?? '' ) ) ),
			'custom_footer_block_id'     => $this->parse_block_selection( sanitize_text_field( wp_unslash( $_POST['custom_footer_block_id'] ?? '' ) ) ),
			'custom_css'                 => sanitize_textarea_field( wp_unslash( $_POST['custom_css'] ?? '' ) ),
			'notes_enabled'              => isset( $_POST['notes_enabled'] ) ? 1 : 0,
		);

		if ( $retreat_id ) {
			// Update existing retreat
			if ( $this->retreat_model->update( $retreat_id, $data ) ) {
				$this->add_admin_notice( __( 'Retreat updated successfully.', 'dfx-parish-retreat-letters' ), 'success' );
				wp_redirect( admin_url( 'admin.php?page=dfx-prl-retreats' ) );
				exit;
			} else {
				$this->add_admin_notice( __( 'Error updating retreat. Please check your data.', 'dfx-parish-retreat-letters' ), 'error' );
			}
		} else {
			// Create new retreat
			$new_id = $this->retreat_model->create( $data );
			if ( $new_id ) {
				$this->add_admin_notice( __( 'Retreat created successfully.', 'dfx-parish-retreat-letters' ), 'success' );
				wp_redirect( admin_url( 'admin.php?page=dfx-prl-retreats' ) );
				exit;
			} else {
				$this->add_admin_notice( __( 'Error creating retreat. Please check your data.', 'dfx-parish-retreat-letters' ), 'error' );
			}
		}
	}

	/**
	 * AJAX handler for deleting retreats.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_retreat() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat_id = absint( $_POST['retreat_id'] ?? 0 );
		$retreat_name = sanitize_text_field( wp_unslash( $_POST['retreat_name'] ?? '' ) );

		// Get the retreat to verify the name
		$retreat = $this->retreat_model->get( $retreat_id );
		if ( ! $retreat ) {
			wp_send_json_error( array( 'message' => __( 'Retreat not found.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Verify the retreat name matches exactly
		if ( $retreat->name !== $retreat_name ) {
			wp_send_json_error( array( 'message' => __( 'Retreat name verification failed. Deletion cancelled for security.', 'dfx-parish-retreat-letters' ) ) );
		}

		if ( $this->retreat_model->delete( $retreat_id ) ) {
			wp_send_json_success( array( 'message' => __( 'Retreat deleted successfully.', 'dfx-parish-retreat-letters' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Error deleting retreat.', 'dfx-parish-retreat-letters' ) ) );
		}
	}

	/**
	 * Render the list page.
	 *
	 * @since 1.0.0
	 * @param array $retreats     Array of retreat objects.
	 * @param string $search      Current search term.
	 * @param int    $page_num    Current page number.
	 * @param int    $total_pages Total number of pages.
	 * @param int    $total_items Total number of items.
	 */
	private function render_list_page( $retreats, $search, $page_num, $total_pages, $total_items ) {
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Retreats', 'dfx-parish-retreat-letters' ); ?></h1>
			<?php if ( $this->permissions->current_user_can_manage_plugin() ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats-add' ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Add New', 'dfx-parish-retreat-letters' ); ?>
				</a>
			<?php endif; ?>
			<hr class="wp-header-end">

			<?php $this->display_admin_notices(); ?>

			<form method="get" action="">
				<input type="hidden" name="page" value="dfx-prl-retreats">
				<p class="search-box">
					<label class="screen-reader-text" for="retreat-search-input"><?php esc_html_e( 'Search Retreats:', 'dfx-parish-retreat-letters' ); ?></label>
					<input type="search" id="retreat-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
					<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Retreats', 'dfx-parish-retreat-letters' ); ?>">
				</p>
			</form>

			<form method="post" action="">
				<?php wp_nonce_field( 'dfx_prl_retreats_action' ); ?>
				<div class="tablenav top">
					<div class="alignleft actions">
						<p><?php
						/* translators: %d: number of retreats */
						printf( esc_html__( 'Total retreats: %d', 'dfx-parish-retreat-letters' ), esc_html( $total_items ) ); ?></p>
					</div>
					<?php if ( $total_pages > 1 ) : ?>
						<div class="tablenav-pages">
							<?php
							echo paginate_links( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'base'    => add_query_arg( 'paged', '%#%' ),
								'format'  => '',
								'current' => $page_num,
								'total'   => $total_pages,
							) );
							?>
						</div>
					<?php endif; ?>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Name', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Location', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Start Date', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'End Date', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Attendants', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Actions', 'dfx-parish-retreat-letters' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $retreats ) ) : ?>
							<?php foreach ( $retreats as $retreat ) : ?>
								<tr>
									<td>
										<strong>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats-add&edit=' . $retreat->id ) ); ?>">
												<?php echo esc_html( $retreat->name ); ?>
											</a>
										</strong>
									</td>
									<td><?php echo esc_html( $retreat->location ); ?></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $retreat->start_date ) ) ); ?></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $retreat->end_date ) ) ); ?></td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>">
											<?php
											$count = $retreat->attendant_count ?? 0;
											printf(
												/* translators: %d: Number of attendants */
												esc_html( _n( '%d attendant', '%d attendants', $count, 'dfx-parish-retreat-letters' ) ),
												absint( $count )
											);
											?>
										</a>
									</td>
									<td>
										<?php if ( $this->permissions->current_user_can_manage_retreat( $retreat->id ) ) : ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats-add&edit=' . $retreat->id ) ); ?>" class="button button-small">
												<?php esc_html_e( 'Edit', 'dfx-parish-retreat-letters' ); ?>
											</a>
										<?php endif; ?>

										<?php if ( $this->permissions->current_user_can_view_retreat( $retreat->id ) ) : ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>" class="button button-small">
												<?php esc_html_e( 'Attendants', 'dfx-parish-retreat-letters' ); ?>
											</a>
										<?php endif; ?>

										<?php if ( $this->permissions->current_user_can_manage_plugin() ) : ?>
											<button type="button" class="button button-small button-link-delete dfx-prl-delete-retreat" data-retreat-id="<?php echo esc_attr( $retreat->id ); ?>" data-retreat-name="<?php echo esc_attr( $retreat->name ); ?>">
												<?php esc_html_e( 'Delete', 'dfx-parish-retreat-letters' ); ?>
											</button>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6">
									<?php if ( $search ) : ?>
										<?php esc_html_e( 'No retreats found for your search.', 'dfx-parish-retreat-letters' ); ?>
									<?php else : ?>
										<?php esc_html_e( 'No retreats found.', 'dfx-parish-retreat-letters' ); ?>
										<?php if ( $this->permissions->current_user_can_manage_plugin() ) : ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats-add' ) ); ?>">
												<?php esc_html_e( 'Add the first retreat', 'dfx-parish-retreat-letters' ); ?>
											</a>
										<?php endif; ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</form>

			<?php $this->render_plugin_footer(); ?>
		</div>
		<?php
	}

	/**
	 * Render the add/edit page.
	 *
	 * @since 1.0.0
	 * @param object|null $retreat Retreat object for editing, null for adding.
	 */
	private function render_add_edit_page( $retreat = null ) {
		$is_edit = ! is_null( $retreat );
		$title = $is_edit ? __( 'Edit Retreat', 'dfx-parish-retreat-letters' ) : __( 'Add New Retreat', 'dfx-parish-retreat-letters' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $title ); ?></h1>
			<hr class="wp-header-end">

			<?php $this->display_admin_notices(); ?>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<form method="post" action="">
							<?php wp_nonce_field( 'dfx_prl_retreats_add_edit' ); ?>
							<table class="form-table">
								<tbody>
									<tr>
										<th scope="row">
											<label for="name"><?php esc_html_e( 'Retreat Name', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'required', 'dfx-parish-retreat-letters' ); ?>)</span></label>
										</th>
										<td>
											<input type="text" id="name" name="name" value="<?php echo esc_attr( $retreat->name ?? '' ); ?>" class="regular-text" required>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="location"><?php esc_html_e( 'Location', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'required', 'dfx-parish-retreat-letters' ); ?>)</span></label>
										</th>
										<td>
											<input type="text" id="location" name="location" value="<?php echo esc_attr( $retreat->location ?? '' ); ?>" class="regular-text" required>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="start_date"><?php esc_html_e( 'Start Date', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'required', 'dfx-parish-retreat-letters' ); ?>)</span></label>
										</th>
										<td>
											<input type="date" id="start_date" name="start_date" value="<?php echo esc_attr( $retreat->start_date ?? '' ); ?>" required>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="end_date"><?php esc_html_e( 'End Date', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'required', 'dfx-parish-retreat-letters' ); ?>)</span></label>
										</th>
										<td>
											<input type="date" id="end_date" name="end_date" value="<?php echo esc_attr( $retreat->end_date ?? '' ); ?>" required>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="custom_message"><?php esc_html_e( 'Custom Message for Letter Senders', 'dfx-parish-retreat-letters' ); ?></label>
										</th>
										<td>
											<textarea id="custom_message" name="custom_message" rows="5" class="large-text" placeholder="<?php esc_attr_e( 'Optional message that will be displayed to senders in the letters form.', 'dfx-parish-retreat-letters' ); ?>"><?php echo esc_textarea( $retreat->custom_message ?? '' ); ?></textarea>
											<p class="description"><?php esc_html_e( 'This message will be displayed before the message submission form for all attendants of this retreat. HTML is allowed.', 'dfx-parish-retreat-letters' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="disclaimer_text"><?php esc_html_e( 'Legal Disclaimer Text', 'dfx-parish-retreat-letters' ); ?></label>
										</th>
										<td>
											<textarea id="disclaimer_text" name="disclaimer_text" rows="5" class="large-text" placeholder="<?php esc_attr_e( 'Optional legal disclaimer that users must accept before submitting messages.', 'dfx-parish-retreat-letters' ); ?>"><?php echo esc_textarea( $retreat->disclaimer_text ?? '' ); ?></textarea>
											<p class="description"><?php esc_html_e( 'If provided, this disclaimer will be displayed in the message form and users must accept it to submit messages. HTML is allowed.', 'dfx-parish-retreat-letters' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="disclaimer_acceptance_text"><?php esc_html_e( 'Disclaimer Acceptance Text', 'dfx-parish-retreat-letters' ); ?></label>
										</th>
										<td>
											<input type="text" id="disclaimer_acceptance_text" name="disclaimer_acceptance_text" value="<?php echo esc_attr( $retreat->disclaimer_acceptance_text ?? '' ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'I accept the terms and conditions', 'dfx-parish-retreat-letters' ); ?>">
											<p class="description"><?php esc_html_e( 'This text will appear next to the disclaimer acceptance checkbox. Only used if disclaimer text is provided.', 'dfx-parish-retreat-letters' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="notes_enabled"><?php esc_html_e( 'Enable Notes Field', 'dfx-parish-retreat-letters' ); ?></label>
										</th>
										<td>
											<label>
												<input type="checkbox" id="notes_enabled" name="notes_enabled" value="1" <?php checked( ! empty( $retreat->notes_enabled ) ); ?>>
												<?php esc_html_e( 'Enable optional notes field for attendants', 'dfx-parish-retreat-letters' ); ?>
											</label>
											<p class="description"><?php esc_html_e( 'When enabled, an optional notes field will be available for each attendant and displayed in the attendants list.', 'dfx-parish-retreat-letters' ); ?></p>
										</td>
									</tr>
									<?php if ( post_type_exists( 'wp_block' ) ) : ?>
									<?php
									$per_retreat_customization_enabled = $this->global_settings->is_per_retreat_customization_enabled();
									$header_default_text = $per_retreat_customization_enabled ?
										__( 'Use default header', 'dfx-parish-retreat-letters' ) :
										__( 'Use global default header', 'dfx-parish-retreat-letters' );
									$footer_default_text = $per_retreat_customization_enabled ?
										__( 'Use default footer', 'dfx-parish-retreat-letters' ) :
										__( 'Use global default footer', 'dfx-parish-retreat-letters' );
									?>

									<?php if ( $per_retreat_customization_enabled ) : ?>
									<tr>
										<th scope="row">
											<label for="custom_header_block_id"><?php esc_html_e( 'Custom Header Block', 'dfx-parish-retreat-letters' ); ?></label>
										</th>
										<td>
											<?php $this->render_block_selector( 'custom_header_block_id', $retreat->custom_header_block_id ?? null, $header_default_text ); ?>
											<p class="description"><?php esc_html_e( 'Select a reusable block, template part, or pattern to display as custom header in the letters form page. Leave empty to use the default header.', 'dfx-parish-retreat-letters' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="custom_footer_block_id"><?php esc_html_e( 'Custom Footer Block', 'dfx-parish-retreat-letters' ); ?></label>
										</th>
										<td>
											<?php $this->render_block_selector( 'custom_footer_block_id', $retreat->custom_footer_block_id ?? null, $footer_default_text ); ?>
											<p class="description"><?php esc_html_e( 'Select a reusable block, template part, or pattern to display as custom footer in the letters form page. Leave empty to use the default footer.', 'dfx-parish-retreat-letters' ); ?></p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="custom_css"><?php esc_html_e( 'Custom CSS Styles', 'dfx-parish-retreat-letters' ); ?></label>
										</th>
										<td>
											<textarea id="custom_css" name="custom_css" rows="10" cols="80" class="large-text code"><?php echo esc_textarea( $retreat->custom_css ?? '' ); ?></textarea>
											<p class="description"><?php esc_html_e( 'CSS styles specific to this retreat\'s message form page. Do not include &lt;style&gt; tags. Leave empty to use only the global default CSS.', 'dfx-parish-retreat-letters' ); ?></p>
										</td>
									</tr>
									<?php endif; ?>
									<?php endif; ?>
								</tbody>
							</table>

							<p class="submit">
								<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr( $is_edit ? __( 'Update Retreat', 'dfx-parish-retreat-letters' ) : __( 'Add Retreat', 'dfx-parish-retreat-letters' ) ); ?>">
								<?php if ( $is_edit ) : ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>" class="button">
										<?php esc_html_e( 'Manage Attendants', 'dfx-parish-retreat-letters' ); ?>
									</a>
								<?php endif; ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats' ) ); ?>" class="button">
									<?php esc_html_e( 'Cancel', 'dfx-parish-retreat-letters' ); ?>
								</a>
							</p>
						</form>
					</div>

					<div id="postbox-container-1" class="postbox-container">
						<div id="side-sortables" class="meta-box-sortables">
							<?php if ( $is_edit && $this->permissions->user_can_delegate_permissions( get_current_user_id(), $retreat->id ) ) : ?>
								<?php $this->render_permission_management_sidebar( $retreat ); ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

			<?php $this->render_plugin_footer(); ?>
		</div>
		<?php
	}

	/**
	 * Render the permission management section for a retreat.
	 *
	 * @since 1.3.0
	 * @param object $retreat Retreat object.
	 */
	private function render_permission_management_section( $retreat ) {
		$permissions = $this->permissions->get_retreat_permissions( $retreat->id );
		$invitations = DFX_Parish_Retreat_Letters_Invitations::get_instance();
		$pending_invitations = $invitations->get_retreat_invitations( $retreat->id, 'pending' );
		?>
		<div id="permission-management-section" class="card">
			<h2><?php esc_html_e( 'Access Management', 'dfx-parish-retreat-letters' ); ?></h2>

			<div id="permission-notices"></div>

			<!-- Current Permissions -->
			<div class="dfx-prl-permissions-current">
				<h3><?php esc_html_e( 'Current Permissions', 'dfx-parish-retreat-letters' ); ?></h3>
				<?php if ( ! empty( $permissions ) ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'User', 'dfx-parish-retreat-letters' ); ?></th>
								<th><?php esc_html_e( 'Role', 'dfx-parish-retreat-letters' ); ?></th>
								<th><?php esc_html_e( 'Granted By', 'dfx-parish-retreat-letters' ); ?></th>
								<th><?php esc_html_e( 'Date', 'dfx-parish-retreat-letters' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'dfx-parish-retreat-letters' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $permissions as $permission ) : ?>
								<tr data-user-id="<?php echo esc_attr( $permission->user_id ); ?>" data-permission="<?php echo esc_attr( $permission->permission_level ); ?>">
									<td>
										<strong><?php echo esc_html( $permission->display_name ); ?></strong><br>
										<small><?php echo esc_html( $permission->user_email ); ?></small>
									</td>
									<td>
										<span class="permission-badge permission-<?php echo esc_attr( $permission->permission_level ); ?>">
											<?php
											echo esc_html( $permission->permission_level === 'manager'
												? __( 'Retreat Manager', 'dfx-parish-retreat-letters' )
												: __( 'Message Manager', 'dfx-parish-retreat-letters' )
											);
											?>
										</span>
									</td>
									<td><?php echo esc_html( $permission->granted_by_name ); ?></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $permission->granted_at ) ) ); ?></td>
									<td>
										<?php if ( $permission->user_id !== get_current_user_id() ) : ?>
											<button type="button" class="button button-small revoke-permission"
													data-user-id="<?php echo esc_attr( $permission->user_id ); ?>"
													data-permission="<?php echo esc_attr( $permission->permission_level ); ?>">
												<?php esc_html_e( 'Revoke', 'dfx-parish-retreat-letters' ); ?>
											</button>
										<?php else : ?>
											<em><?php esc_html_e( 'You cannot revoke your own permissions', 'dfx-parish-retreat-letters' ); ?></em>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php esc_html_e( 'No users have been granted permissions for this retreat yet.', 'dfx-parish-retreat-letters' ); ?></p>
				<?php endif; ?>
			</div>

			<!-- Pending Invitations -->
			<?php if ( ! empty( $pending_invitations ) ) : ?>
				<div class="dfx-prl-permissions-invitations">
					<h3><?php esc_html_e( 'Pending Invitations', 'dfx-parish-retreat-letters' ); ?></h3>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Name', 'dfx-parish-retreat-letters' ); ?></th>
								<th><?php esc_html_e( 'Email', 'dfx-parish-retreat-letters' ); ?></th>
								<th><?php esc_html_e( 'Role', 'dfx-parish-retreat-letters' ); ?></th>
								<th><?php esc_html_e( 'Invited', 'dfx-parish-retreat-letters' ); ?></th>
								<th><?php esc_html_e( 'Expires', 'dfx-parish-retreat-letters' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'dfx-parish-retreat-letters' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $pending_invitations as $invitation ) : ?>
								<tr data-invitation-id="<?php echo esc_attr( $invitation->id ); ?>">
									<td><?php echo esc_html( $invitation->name ); ?></td>
									<td><?php echo esc_html( $invitation->email ); ?></td>
									<td>
										<span class="permission-badge permission-<?php echo esc_attr( $invitation->permission_level ); ?>">
											<?php
											echo esc_html( $invitation->permission_level === 'manager'
												? __( 'Retreat Manager', 'dfx-parish-retreat-letters' )
												: __( 'Message Manager', 'dfx-parish-retreat-letters' )
											);
											?>
										</span>
									</td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $invitation->invited_at ) ) ); ?></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $invitation->expires_at ) ) ); ?></td>
									<td>
										<button type="button" class="button button-small cancel-invitation"
												data-invitation-id="<?php echo esc_attr( $invitation->id ); ?>">
											<?php esc_html_e( 'Cancel', 'dfx-parish-retreat-letters' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<!-- Add Permissions -->
			<div class="dfx-prl-permissions-add">
				<h3><?php esc_html_e( 'Grant Access', 'dfx-parish-retreat-letters' ); ?></h3>

				<!-- Tab Navigation -->
				<div class="nav-tab-wrapper">
					<a href="#existing-users" class="nav-tab nav-tab-active" data-tab="existing-users">
						<?php esc_html_e( 'Existing Users', 'dfx-parish-retreat-letters' ); ?>
					</a>
					<a href="#invite-users" class="nav-tab" data-tab="invite-users">
						<?php esc_html_e( 'Invite New Users', 'dfx-parish-retreat-letters' ); ?>
					</a>
				</div>

				<!-- Existing Users Tab -->
				<div id="existing-users" class="tab-content active">
					<div class="dfx-prl-user-search">
						<h4><?php esc_html_e( 'Search and Grant Permission to Existing Users', 'dfx-parish-retreat-letters' ); ?></h4>
						<div class="search-form">
							<input type="text" id="user-search" placeholder="<?php esc_attr_e( 'Search by username, email, or name...', 'dfx-parish-retreat-letters' ); ?>" autocomplete="off">
							<div id="user-search-results"></div>
						</div>
					</div>
				</div>

				<!-- Invite Users Tab -->
				<div id="invite-users" class="tab-content">
					<div class="dfx-prl-invite-form">
						<h4><?php esc_html_e( 'Send Invitation to New User', 'dfx-parish-retreat-letters' ); ?></h4>
						<form id="invitation-form">
							<div class="form-row">
								<div class="form-field">
									<label for="invite-name"><?php esc_html_e( 'Name', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
									<input type="text" id="invite-name" name="name" required>
								</div>
								<div class="form-field">
									<label for="invite-email"><?php esc_html_e( 'Email', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
									<input type="email" id="invite-email" name="email" required>
								</div>
							</div>
							<div class="form-row">
								<div class="form-field">
									<label for="invite-permission"><?php esc_html_e( 'Role', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
									<select id="invite-permission" name="permission_level" required>
										<option value=""><?php esc_html_e( 'Select role...', 'dfx-parish-retreat-letters' ); ?></option>
										<option value="manager"><?php esc_html_e( 'Retreat Manager', 'dfx-parish-retreat-letters' ); ?></option>
										<option value="message_manager"><?php esc_html_e( 'Message Manager', 'dfx-parish-retreat-letters' ); ?></option>
									</select>
								</div>
							</div>
							<button type="submit" class="button button-primary">
								<?php esc_html_e( 'Send Invitation', 'dfx-parish-retreat-letters' ); ?>
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>

		<style>
		#permission-management-section {
			margin-top: 20px;
			padding: 20px;
		}

		.permission-badge {
			display: inline-block;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
		}

		.permission-badge.permission-manager {
			background: #d4edda;
			color: #155724;
		}

		.permission-badge.permission-message_manager {
			background: #cce5ff;
			color: #004085;
		}

		.dfx-prl-permissions-current, .dfx-prl-permissions-invitations, .dfx-prl-permissions-add {
			margin-bottom: 30px;
		}

		.dfx-prl-user-search {
			position: relative;
		}

		#user-search {
			width: 100%;
			max-width: 400px;
			padding: 8px 12px;
			margin-bottom: 10px;
		}

		#user-search-results {
			position: absolute;
			top: 100%;
			left: 0;
			right: 0;
			background: white;
			border: 1px solid #ddd;
			border-top: none;
			max-height: 200px;
			overflow-y: auto;
			z-index: 1000;
			display: none;
		}

		.user-result {
			padding: 10px;
			border-bottom: 1px solid #eee;
			cursor: pointer;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.user-result:hover {
			background: #f8f9fa;
		}

		.user-info {
			flex: 1;
		}

		.user-info .name {
			font-weight: 600;
		}

		.user-info .email {
			font-size: 12px;
			color: #666;
		}

		.grant-permission-controls {
			display: flex;
			gap: 10px;
			align-items: center;
		}

		.grant-permission-controls select {
			min-width: 150px;
		}

		.tab-content {
			display: none;
			padding: 20px 0;
		}

		.tab-content.active {
			display: block;
		}

		.form-row {
			display: flex;
			gap: 20px;
			margin-bottom: 15px;
		}

		.form-field {
			flex: 1;
		}

		.form-field label {
			display: block;
			margin-bottom: 5px;
			font-weight: 600;
		}

		.form-field input, .form-field select {
			width: 100%;
			padding: 8px 12px;
		}

		.required {
			color: #d63384;
		}

		.permission-notice {
			padding: 10px;
			margin: 10px 0;
			border-radius: 4px;
		}

		.permission-notice.success {
			background: #d4edda;
			color: #155724;
			border: 1px solid #c3e6cb;
		}

		.permission-notice.error {
			background: #f8d7da;
			color: #721c24;
			border: 1px solid #f5c6cb;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			var nonce = '<?php echo esc_js( wp_create_nonce( 'dfx_prl_retreats_nonce' ) ); ?>';
			var retreatId = <?php echo absint( $retreat->id ); ?>;
			var searchTimeout;

			// Tab switching
			$('.nav-tab').on('click', function(e) {
				e.preventDefault();
				var tabId = $(this).data('tab');

				$('.nav-tab').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');

				$('.tab-content').removeClass('active');
				$('#' + tabId).addClass('active');
			});

			// User search
			$('#user-search').on('input', function() {
				var searchTerm = $(this).val().trim();

				clearTimeout(searchTimeout);

				if (searchTerm.length < 2) {
					$('#user-search-results').hide();
					return;
				}

				searchTimeout = setTimeout(function() {
					searchUsers(searchTerm);
				}, 300);
			});

			// Hide search results when clicking outside
			$(document).on('click', function(e) {
				if (!$(e.target).closest('.dfx-prl-user-search').length) {
					$('#user-search-results').hide();
				}
			});

			function searchUsers(searchTerm) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'dfx_prl_search_users',
						nonce: nonce,
						retreat_id: retreatId,
						search: searchTerm
					},
					success: function(response) {
						if (response.success) {
							displaySearchResults(response.data.users);
						} else {
							showNotice(response.data.message, 'error');
						}
					},
					error: function() {
						showNotice('<?php esc_html_e( 'Search failed. Please try again.', 'dfx-parish-retreat-letters' ); ?>', 'error');
					}
				});
			}

			function displaySearchResults(users) {
				var $results = $('#user-search-results');
				$results.empty();

				if (users.length === 0) {
					$results.html('<div class="user-result"><?php esc_html_e( 'No users found.', 'dfx-parish-retreat-letters' ); ?></div>');
				} else {
					users.forEach(function(user) {
						var $userResult = $('<div class="user-result">');
						$userResult.html(
							'<div class="user-info">' +
								'<div class="name">' + escapeHtml(user.display_name) + '</div>' +
								'<div class="email">' + escapeHtml(user.email) + '</div>' +
							'</div>' +
							'<div class="grant-permission-controls">' +
								'<select class="permission-select">' +
									'<option value=""><?php esc_html_e( 'Select role...', 'dfx-parish-retreat-letters' ); ?></option>' +
									'<option value="manager"><?php esc_html_e( 'Retreat Manager', 'dfx-parish-retreat-letters' ); ?></option>' +
									'<option value="message_manager"><?php esc_html_e( 'Message Manager', 'dfx-parish-retreat-letters' ); ?></option>' +
								'</select>' +
								'<button type="button" class="button button-small grant-btn" data-user-id="' + user.id + '"><?php esc_html_e( 'Grant', 'dfx-parish-retreat-letters' ); ?></button>' +
							'</div>'
						);
						$results.append($userResult);
					});
				}

				$results.show();
			}

			// Grant permission to existing user
			$(document).on('click', '.grant-btn', function() {
				var userId = $(this).data('user-id');
				var permissionLevel = $(this).siblings('.permission-select').val();

				if (!permissionLevel) {
					alert('<?php esc_html_e( 'Please select a role.', 'dfx-parish-retreat-letters' ); ?>');
					return;
				}

				grantPermission(userId, permissionLevel);
			});

			function grantPermission(userId, permissionLevel) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'dfx_prl_grant_permission',
						nonce: nonce,
						retreat_id: retreatId,
						user_id: userId,
						permission_level: permissionLevel
					},
					success: function(response) {
						if (response.success) {
							showNotice(response.data.message, 'success');
							$('#user-search').val('');
							$('#user-search-results').hide();
							// Refresh the page to show the new permission
							setTimeout(function() {
								location.reload();
							}, 1500);
						} else {
							showNotice(response.data.message, 'error');
						}
					},
					error: function() {
						showNotice('<?php esc_html_e( 'Failed to grant permission. Please try again.', 'dfx-parish-retreat-letters' ); ?>', 'error');
					}
				});
			}

			// Revoke permission
			$('.revoke-permission').on('click', function() {
				var userId = $(this).data('user-id');
				var permissionLevel = $(this).data('permission');

				if (confirm('<?php esc_html_e( 'Are you sure you want to revoke this permission?', 'dfx-parish-retreat-letters' ); ?>')) {
					revokePermission(userId, permissionLevel);
				}
			});

			function revokePermission(userId, permissionLevel) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'dfx_prl_revoke_permission',
						nonce: nonce,
						retreat_id: retreatId,
						user_id: userId,
						permission_level: permissionLevel
					},
					success: function(response) {
						if (response.success) {
							showNotice(response.data.message, 'success');
							// Remove the row from the table
							$('tr[data-user-id="' + userId + '"][data-permission="' + permissionLevel + '"]').fadeOut();
						} else {
							showNotice(response.data.message, 'error');
						}
					},
					error: function() {
						showNotice('<?php esc_html_e( 'Failed to revoke permission. Please try again.', 'dfx-parish-retreat-letters' ); ?>', 'error');
					}
				});
			}

			// Send invitation
			$('#invitation-form').on('submit', function(e) {
				e.preventDefault();

				var formData = {
					action: 'dfx_prl_send_invitation',
					nonce: nonce,
					retreat_id: retreatId,
					name: $('#invite-name').val(),
					email: $('#invite-email').val(),
					permission_level: $('#invite-permission').val()
				};

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: formData,
					success: function(response) {
						if (response.success) {
							showNotice(response.data.message, 'success');
							$('#invitation-form')[0].reset();
							// Refresh to show the pending invitation
							setTimeout(function() {
								location.reload();
							}, 1500);
						} else {
							showNotice(response.data.message, 'error');
						}
					},
					error: function() {
						showNotice('<?php esc_html_e( 'Failed to send invitation. Please try again.', 'dfx-parish-retreat-letters' ); ?>', 'error');
					}
				});
			});

			// Cancel invitation
			$('.cancel-invitation').on('click', function() {
				var invitationId = $(this).data('invitation-id');

				if (confirm('<?php esc_html_e( 'Are you sure you want to cancel this invitation?', 'dfx-parish-retreat-letters' ); ?>')) {
					cancelInvitation(invitationId);
				}
			});

			function cancelInvitation(invitationId) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'dfx_prl_cancel_invitation',
						nonce: nonce,
						retreat_id: retreatId,
						invitation_id: invitationId
					},
					success: function(response) {
						if (response.success) {
							showNotice(response.data.message, 'success');
							$('tr[data-invitation-id="' + invitationId + '"]').fadeOut();
						} else {
							showNotice(response.data.message, 'error');
						}
					},
					error: function() {
						showNotice('<?php esc_html_e( 'Failed to cancel invitation. Please try again.', 'dfx-parish-retreat-letters' ); ?>', 'error');
					}
				});
			}

			function showNotice(message, type) {
				var $notice = $('<div class="permission-notice ' + type + '">' + escapeHtml(message) + '</div>');
				$('#permission-notices').html($notice);

				$('html, body').animate({
					scrollTop: $notice.offset().top - 20
				}, 500);

				if (type === 'success') {
					setTimeout(function() {
						$notice.fadeOut();
					}, 5000);
				}
			}

			function escapeHtml(text) {
				var map = {
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;',
					"'": '&#039;'
				};
				return text.replace(/[&<>"']/g, function(m) { return map[m]; });
			}
		});
		</script>
		<?php
	}

	/**
	 * Render the permission management section for the sidebar.
	 *
	 * @since 1.3.0
	 * @param object $retreat Retreat object.
	 */
	private function render_permission_management_sidebar( $retreat ) {
		$permissions = $this->permissions->get_retreat_permissions( $retreat->id );
		$invitations = DFX_Parish_Retreat_Letters_Invitations::get_instance();
		$pending_invitations = $invitations->get_retreat_invitations( $retreat->id, 'pending' );
		?>
		<div class="postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php esc_html_e( 'Access Management', 'dfx-parish-retreat-letters' ); ?></h2>
			</div>
			<div class="inside">
				<div id="permission-notices"></div>

				<!-- Current Permissions -->
				<div class="dfx-prl-permissions-current">
					<h3><?php esc_html_e( 'Current Permissions', 'dfx-parish-retreat-letters' ); ?></h3>
					<?php if ( ! empty( $permissions ) ) : ?>
						<div class="dfx-prl-permissions-list">
							<?php foreach ( $permissions as $permission ) : ?>
								<div class="dfx-prl-permission-item" data-user-id="<?php echo esc_attr( $permission->user_id ); ?>" data-permission="<?php echo esc_attr( $permission->permission_level ); ?>">
									<div class="dfx-prl-permission-user">
										<strong><?php echo esc_html( $permission->display_name ); ?></strong>
										<small class="dfx-prl-user-email"><?php echo esc_html( $permission->user_email ); ?></small>
									</div>
									<div class="dfx-prl-permission-role">
										<span class="permission-badge permission-<?php echo esc_attr( $permission->permission_level ); ?>">
											<?php
											echo esc_html( $permission->permission_level === 'manager'
												? __( 'Retreat Manager', 'dfx-parish-retreat-letters' )
												: __( 'Message Manager', 'dfx-parish-retreat-letters' )
											);
											?>
										</span>
									</div>
									<div class="dfx-prl-permission-meta">
										<small><?php
										/* translators: %s: name of the person who granted the permission */
										printf( esc_html__( 'By %s', 'dfx-parish-retreat-letters' ), esc_html( $permission->granted_by_name ) ); ?></small>
										<small><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $permission->granted_at ) ) ); ?></small>
									</div>
									<?php if ( $permission->user_id !== get_current_user_id() ) : ?>
										<div class="dfx-prl-permission-actions">
											<button type="button" class="button button-small revoke-permission"
													data-user-id="<?php echo esc_attr( $permission->user_id ); ?>"
													data-permission="<?php echo esc_attr( $permission->permission_level ); ?>">
												<?php esc_html_e( 'Revoke', 'dfx-parish-retreat-letters' ); ?>
											</button>
										</div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'No users have been granted permissions for this retreat yet.', 'dfx-parish-retreat-letters' ); ?></p>
					<?php endif; ?>
				</div>

				<!-- Pending Invitations -->
				<?php if ( ! empty( $pending_invitations ) ) : ?>
					<div class="dfx-prl-permissions-invitations">
						<h3><?php esc_html_e( 'Pending Invitations', 'dfx-parish-retreat-letters' ); ?></h3>
						<div class="dfx-prl-invitations-list">
							<?php foreach ( $pending_invitations as $invitation ) : ?>
								<div class="dfx-prl-invitation-item" data-invitation-id="<?php echo esc_attr( $invitation->id ); ?>">
									<div class="dfx-prl-invitation-user">
										<strong><?php echo esc_html( $invitation->name ); ?></strong>
										<small class="dfx-prl-user-email"><?php echo esc_html( $invitation->email ); ?></small>
									</div>
									<div class="dfx-prl-invitation-role">
										<span class="permission-badge permission-<?php echo esc_attr( $invitation->permission_level ); ?>">
											<?php
											echo esc_html( $invitation->permission_level === 'manager'
												? __( 'Retreat Manager', 'dfx-parish-retreat-letters' )
												: __( 'Message Manager', 'dfx-parish-retreat-letters' )
											);
											?>
										</span>
									</div>
									<div class="dfx-prl-invitation-meta">
										<small><?php
										/* translators: %s: formatted expiration date */
										printf( esc_html__( 'Expires %s', 'dfx-parish-retreat-letters' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $invitation->expires_at ) ) ) ); ?></small>
									</div>
									<div class="dfx-prl-invitation-actions">
										<button type="button" class="button button-small cancel-invitation"
												data-invitation-id="<?php echo esc_attr( $invitation->id ); ?>">
											<?php esc_html_e( 'Cancel', 'dfx-parish-retreat-letters' ); ?>
										</button>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<!-- Add Permissions -->
				<div class="dfx-prl-permissions-add">
					<h3><?php esc_html_e( 'Grant Access', 'dfx-parish-retreat-letters' ); ?></h3>

					<!-- Tab Navigation -->
					<div class="dfx-prl-tab-wrapper">
						<button type="button" class="dfx-prl-tab-button active" data-tab="existing-users">
							<?php esc_html_e( 'Existing Users', 'dfx-parish-retreat-letters' ); ?>
						</button>
						<button type="button" class="dfx-prl-tab-button" data-tab="invite-users">
							<?php esc_html_e( 'Invite New Users', 'dfx-parish-retreat-letters' ); ?>
						</button>
					</div>

					<!-- Existing Users Tab -->
					<div id="existing-users" class="dfx-prl-tab-content active">
						<div class="dfx-prl-user-search">
							<h4><?php esc_html_e( 'Search and Grant Permission to Existing Users', 'dfx-parish-retreat-letters' ); ?></h4>
							<div class="search-form">
								<input type="text" id="user-search" placeholder="<?php esc_attr_e( 'Search by username, email, or name...', 'dfx-parish-retreat-letters' ); ?>" autocomplete="off">
								<div id="user-search-results"></div>
							</div>
						</div>
					</div>

					<!-- Invite Users Tab -->
					<div id="invite-users" class="dfx-prl-tab-content">
						<div class="dfx-prl-invite-form">
							<h4><?php esc_html_e( 'Send Invitation to New User', 'dfx-parish-retreat-letters' ); ?></h4>
							<form id="invitation-form">
								<div class="form-field">
									<label for="invite-name"><?php esc_html_e( 'Name', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
									<input type="text" id="invite-name" name="name" required>
								</div>
								<div class="form-field">
									<label for="invite-email"><?php esc_html_e( 'Email', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
									<input type="email" id="invite-email" name="email" required>
								</div>
								<div class="form-field">
									<label for="invite-permission"><?php esc_html_e( 'Role', 'dfx-parish-retreat-letters' ); ?> <span class="required">*</span></label>
									<select id="invite-permission" name="permission_level" required>
										<option value=""><?php esc_html_e( 'Select role...', 'dfx-parish-retreat-letters' ); ?></option>
										<option value="manager"><?php esc_html_e( 'Retreat Manager', 'dfx-parish-retreat-letters' ); ?></option>
										<option value="message_manager"><?php esc_html_e( 'Message Manager', 'dfx-parish-retreat-letters' ); ?></option>
									</select>
								</div>
								<button type="submit" class="button button-primary">
									<?php esc_html_e( 'Send Invitation', 'dfx-parish-retreat-letters' ); ?>
								</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>

		<style>
		.dfx-prl-permissions-list, .dfx-prl-invitations-list {
			margin-bottom: 15px;
		}

		.dfx-prl-permission-item, .dfx-prl-invitation-item {
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
			margin-bottom: 8px;
			background: #fff;
		}

		.dfx-prl-permission-user, .dfx-prl-invitation-user {
			margin-bottom: 5px;
		}

		.dfx-prl-permission-user strong, .dfx-prl-invitation-user strong {
			display: block;
			font-size: 13px;
		}

		.dfx-prl-user-email {
			color: #666;
			font-size: 12px;
		}

		.dfx-prl-permission-role, .dfx-prl-invitation-role {
			margin-bottom: 5px;
		}

		.permission-badge {
			display: inline-block;
			padding: 2px 6px;
			border-radius: 3px;
			font-size: 10px;
			font-weight: 600;
			text-transform: uppercase;
		}

		.permission-badge.permission-manager {
			background: #d4edda;
			color: #155724;
		}

		.permission-badge.permission-message_manager {
			background: #cce5ff;
			color: #004085;
		}

		.dfx-prl-permission-meta, .dfx-prl-invitation-meta {
			margin-bottom: 5px;
		}

		.dfx-prl-permission-meta small, .dfx-prl-invitation-meta small {
			display: block;
			color: #666;
			font-size: 11px;
		}

		.dfx-prl-permission-actions, .dfx-prl-invitation-actions {
			text-align: right;
		}

		.dfx-prl-tab-wrapper {
			margin-bottom: 10px;
		}

		.dfx-prl-tab-button {
			background: #f1f1f1;
			border: 1px solid #ddd;
			padding: 8px 12px;
			cursor: pointer;
			font-size: 12px;
			margin-right: 5px;
		}

		.dfx-prl-tab-button.active {
			background: #fff;
			border-bottom-color: #fff;
		}

		.dfx-prl-tab-content {
			display: none;
			border: 1px solid #ddd;
			padding: 10px;
			background: #fff;
		}

		.dfx-prl-tab-content.active {
			display: block;
		}

		.dfx-prl-user-search {
			position: relative;
		}

		#user-search {
			width: 100%;
			padding: 6px 8px;
			margin-bottom: 8px;
			font-size: 12px;
		}

		#user-search-results {
			position: absolute;
			top: 100%;
			left: 0;
			right: 0;
			background: white;
			border: 1px solid #ddd;
			border-top: none;
			max-height: 150px;
			overflow-y: auto;
			z-index: 1000;
			display: none;
		}

		.user-result {
			padding: 8px;
			border-bottom: 1px solid #eee;
			cursor: pointer;
			display: flex;
			justify-content: space-between;
			align-items: center;
			font-size: 12px;
		}

		.user-result:hover {
			background: #f8f9fa;
		}

		.user-info .name {
			font-weight: 600;
		}

		.user-info .email {
			font-size: 11px;
			color: #666;
		}

		.grant-permission-controls {
			display: flex;
			gap: 5px;
			align-items: center;
		}

		.grant-permission-controls select {
			font-size: 11px;
			padding: 2px 4px;
		}

		.dfx-prl-invite-form .form-field {
			margin-bottom: 10px;
		}

		.dfx-prl-invite-form label {
			display: block;
			margin-bottom: 3px;
			font-weight: 600;
			font-size: 12px;
		}

		.dfx-prl-invite-form input, .dfx-prl-invite-form select {
			width: 100%;
			padding: 6px 8px;
			font-size: 12px;
		}

		.required {
			color: #d63384;
		}

		.permission-notice {
			padding: 8px;
			margin: 8px 0;
			border-radius: 4px;
			font-size: 12px;
		}

		.permission-notice.success {
			background: #d4edda;
			color: #155724;
			border: 1px solid #c3e6cb;
		}

		.permission-notice.error {
			background: #f8d7da;
			color: #721c24;
			border: 1px solid #f5c6cb;
		}

		.dfx-prl-permissions-current h3,
		.dfx-prl-permissions-invitations h3,
		.dfx-prl-permissions-add h3 {
			margin: 15px 0 10px 0;
			font-size: 14px;
		}

		.dfx-prl-permissions-current h4,
		.dfx-prl-invite-form h4 {
			margin: 10px 0 8px 0;
			font-size: 13px;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			var nonce = '<?php echo esc_js( wp_create_nonce( 'dfx_prl_retreats_nonce' ) ); ?>';
			var retreatId = <?php echo absint( $retreat->id ); ?>;
			var searchTimeout;

			// Tab switching
			$('.dfx-prl-tab-button').on('click', function(e) {
				e.preventDefault();
				var tabId = $(this).data('tab');

				$('.dfx-prl-tab-button').removeClass('active');
				$(this).addClass('active');

				$('.dfx-prl-tab-content').removeClass('active');
				$('#' + tabId).addClass('active');
			});

			// User search
			$('#user-search').on('input', function() {
				var searchTerm = $(this).val().trim();

				clearTimeout(searchTimeout);

				if (searchTerm.length < 2) {
					$('#user-search-results').hide();
					return;
				}

				searchTimeout = setTimeout(function() {
					searchUsers(searchTerm);
				}, 300);
			});

			// Hide search results when clicking outside
			$(document).on('click', function(e) {
				if (!$(e.target).closest('.dfx-prl-user-search').length) {
					$('#user-search-results').hide();
				}
			});

			function searchUsers(searchTerm) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'dfx_prl_search_users',
						nonce: nonce,
						retreat_id: retreatId,
						search: searchTerm
					},
					success: function(response) {
						if (response.success) {
							displaySearchResults(response.data.users);
						} else {
							showNotice(response.data.message, 'error');
						}
					},
					error: function() {
						showNotice('<?php esc_html_e( 'Search failed. Please try again.', 'dfx-parish-retreat-letters' ); ?>', 'error');
					}
				});
			}

			function displaySearchResults(users) {
				var $results = $('#user-search-results');
				$results.empty();

				if (users.length === 0) {
					$results.html('<div class="user-result"><?php esc_html_e( 'No users found.', 'dfx-parish-retreat-letters' ); ?></div>');
				} else {
					users.forEach(function(user) {
						var $userResult = $('<div class="user-result">');
						$userResult.html(
							'<div class="user-info">' +
								'<div class="name">' + escapeHtml(user.display_name) + '</div>' +
								'<div class="email">' + escapeHtml(user.email) + '</div>' +
							'</div>' +
							'<div class="grant-permission-controls">' +
								'<select class="permission-select">' +
									'<option value=""><?php esc_html_e( 'Select role...', 'dfx-parish-retreat-letters' ); ?></option>' +
									'<option value="manager"><?php esc_html_e( 'Retreat Manager', 'dfx-parish-retreat-letters' ); ?></option>' +
									'<option value="message_manager"><?php esc_html_e( 'Message Manager', 'dfx-parish-retreat-letters' ); ?></option>' +
								'</select>' +
								'<button type="button" class="button button-small grant-permission-btn" data-user-id="' + user.id + '">' +
									'<?php esc_html_e( 'Grant', 'dfx-parish-retreat-letters' ); ?>' +
								'</button>' +
							'</div>'
						);
						$results.append($userResult);
					});
				}

				$results.show();
			}

			// Grant permission to existing user
			$(document).on('click', '.grant-permission-btn', function() {
				var userId = $(this).data('user-id');
				var permissionLevel = $(this).siblings('.permission-select').val();

				if (!permissionLevel) {
					alert('<?php esc_html_e( 'Please select a role first.', 'dfx-parish-retreat-letters' ); ?>');
					return;
				}

				grantPermission(userId, permissionLevel);
			});

			// Send invitation form
			$('#invitation-form').on('submit', function(e) {
				e.preventDefault();

				var formData = {
					action: 'dfx_prl_send_invitation',
					nonce: nonce,
					retreat_id: retreatId,
					name: $('#invite-name').val(),
					email: $('#invite-email').val(),
					permission_level: $('#invite-permission').val()
				};

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: formData,
					success: function(response) {
						if (response.success) {
							showNotice(response.data.message, 'success');
							$('#invitation-form')[0].reset();
							// Reload page after success to show new invitation
							setTimeout(function() {
								window.location.reload();
							}, 2000);
						} else {
							showNotice(response.data.message, 'error');
						}
					},
					error: function() {
						showNotice('<?php esc_html_e( 'Failed to send invitation. Please try again.', 'dfx-parish-retreat-letters' ); ?>', 'error');
					}
				});
			});

			// Revoke permission
			$(document).on('click', '.revoke-permission', function() {
				var userId = $(this).data('user-id');
				var permissionLevel = $(this).data('permission');

				if (confirm('<?php esc_html_e( 'Are you sure you want to revoke this permission?', 'dfx-parish-retreat-letters' ); ?>')) {
					revokePermission(userId, permissionLevel);
				}
			});

			// Cancel invitation
			$(document).on('click', '.cancel-invitation', function() {
				var invitationId = $(this).data('invitation-id');

				if (confirm('<?php esc_html_e( 'Are you sure you want to cancel this invitation?', 'dfx-parish-retreat-letters' ); ?>')) {
					cancelInvitation(invitationId);
				}
			});

			function grantPermission(userId, permissionLevel) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'dfx_prl_grant_permission',
						nonce: nonce,
						retreat_id: retreatId,
						user_id: userId,
						permission_level: permissionLevel
					},
					success: function(response) {
						if (response.success) {
							showNotice(response.data.message, 'success');
							$('#user-search').val('');
							$('#user-search-results').hide();
							// Reload to show new permission
							setTimeout(function() {
								window.location.reload();
							}, 2000);
						} else {
							showNotice(response.data.message, 'error');
						}
					},
					error: function() {
						showNotice('<?php esc_html_e( 'Failed to grant permission. Please try again.', 'dfx-parish-retreat-letters' ); ?>', 'error');
					}
				});
			}

			function revokePermission(userId, permissionLevel) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'dfx_prl_revoke_permission',
						nonce: nonce,
						retreat_id: retreatId,
						user_id: userId,
						permission_level: permissionLevel
					},
					success: function(response) {
						if (response.success) {
							showNotice(response.data.message, 'success');
							// Remove the item from the list
							$('.dfx-prl-permission-item[data-user-id="' + userId + '"][data-permission="' + permissionLevel + '"]').fadeOut();
						} else {
							showNotice(response.data.message, 'error');
						}
					},
					error: function() {
						showNotice('<?php esc_html_e( 'Failed to revoke permission. Please try again.', 'dfx-parish-retreat-letters' ); ?>', 'error');
					}
				});
			}

			function cancelInvitation(invitationId) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'dfx_prl_cancel_invitation',
						nonce: nonce,
						retreat_id: retreatId,
						invitation_id: invitationId
					},
					success: function(response) {
						if (response.success) {
							showNotice(response.data.message, 'success');
							$('.dfx-prl-invitation-item[data-invitation-id="' + invitationId + '"]').fadeOut();
						} else {
							showNotice(response.data.message, 'error');
						}
					},
					error: function() {
						showNotice('<?php esc_html_e( 'Failed to cancel invitation. Please try again.', 'dfx-parish-retreat-letters' ); ?>', 'error');
					}
				});
			}

			function showNotice(message, type) {
				var $notice = $('<div class="permission-notice ' + type + '">' + escapeHtml(message) + '</div>');
				$('#permission-notices').html($notice);

				if (type === 'success') {
					setTimeout(function() {
						$notice.fadeOut();
					}, 5000);
				}
			}

			function escapeHtml(text) {
				var map = {
					'&': '&amp;',
					'<': '&lt;',
					'>': '&gt;',
					'"': '&quot;',
					"'": '&#039;'
				};
				return text.replace(/[&<>"']/g, function(m) { return map[m]; });
			}
		});
		</script>
		<?php
	}

	/**
	 * Add an admin notice.
	 *
	 * @since 1.0.0
	 * @param string $message Notice message.
	 * @param string $type    Notice type (success, error, warning, info).
	 */
	public function add_admin_notice( $message, $type = 'info', $is_dismissible = true ) {
		$notices = get_transient( 'dfx_prl_admin_notices' ) ?: array();
		$notices[] = array(
			'message' => $message,
			'type'    => $type,
			'dismissible' => $is_dismissible,
		);
		set_transient( 'dfx_prl_admin_notices', $notices, 30 );
	}

	/**
	 * Display admin notices.
	 *
	 * @since 1.0.0
	 */
	private function display_admin_notices($is_dismissible = true) {
		$notices = get_transient( 'dfx_prl_admin_notices' );
		if ( ! $notices ) {
			return;
		}

		foreach ( $notices as $notice ) {
			printf(
				'<div class="notice notice-%s %s"><p>%s</p></div>',
				esc_attr( $notice['type'] ),
				$notice['dismissible'] && $is_dismissible ? 'is-dismissible' : '',
				wp_kses_post( $notice['message'] )
			);
		}

		delete_transient( 'dfx_prl_admin_notices' );
	}

	/**
	 * Display the attendants list page for a specific retreat.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id Retreat ID.
	 */
	private function attendants_list_page( $retreat_id ) {
		if ( ! $retreat_id ) {
			wp_die( esc_html__( 'Invalid retreat ID.', 'dfx-parish-retreat-letters' ) );
		}

		// Check permissions
		if ( ! $this->permissions->current_user_can_view_retreat( $retreat_id ) ) {
			wp_die( esc_html__( 'You do not have permission to view this retreat.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat = $this->retreat_model->get( $retreat_id );
		if ( ! $retreat ) {
			wp_die( esc_html__( 'Retreat not found.', 'dfx-parish-retreat-letters' ) );
		}

		// Get query parameters
		$search   = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$page_num = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$per_page = 100;

		// Get attendants
		$attendants = $this->attendant_model->get_by_retreat( $retreat_id, array(
			'search'   => $search,
			'per_page' => $per_page,
			'page'     => $page_num,
		) );

		$total_items = $this->attendant_model->get_count_by_retreat( $retreat_id, $search );
		$total_pages = ceil( $total_items / $per_page );

		$this->render_attendants_list_page( $retreat, $attendants, $search, $page_num, $total_pages, $total_items );
	}

	/**
	 * Display the add attendant page.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id Retreat ID.
	 */
	private function attendant_add_page( $retreat_id ) {
		if ( ! $retreat_id ) {
			wp_die( esc_html__( 'Invalid retreat ID.', 'dfx-parish-retreat-letters' ) );
		}

		// Check permissions - only retreat managers and plugin administrators can add attendants
		if ( ! $this->permissions->current_user_can_manage_retreat( $retreat_id ) ) {
			wp_die( esc_html__( 'You do not have permission to add attendants to this retreat.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat = $this->retreat_model->get( $retreat_id );
		if ( ! $retreat ) {
			wp_die( esc_html__( 'Retreat not found.', 'dfx-parish-retreat-letters' ) );
		}

		$this->render_attendant_add_edit_page( $retreat );
	}

	/**
	 * Display the edit attendant page.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id   Retreat ID.
	 * @param int $attendant_id Attendant ID.
	 */
	private function attendant_edit_page( $retreat_id, $attendant_id ) {
		if ( ! $retreat_id || ! $attendant_id ) {
			wp_die( esc_html__( 'Invalid retreat or attendant ID.', 'dfx-parish-retreat-letters' ) );
		}

		// Check permissions - only retreat managers and plugin administrators can edit attendants
		if ( ! $this->permissions->current_user_can_manage_retreat( $retreat_id ) ) {
			wp_die( esc_html__( 'You do not have permission to edit attendants for this retreat.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat = $this->retreat_model->get( $retreat_id );
		$attendant = $this->attendant_model->get( $attendant_id );

		if ( ! $retreat || ! $attendant || $attendant->retreat_id != $retreat_id ) {
			wp_die( esc_html__( 'Retreat or attendant not found, or attendant does not belong to this retreat.', 'dfx-parish-retreat-letters' ) );
		}

		$this->render_attendant_add_edit_page( $retreat, $attendant );
	}

	/**
	 * Display the attendants CSV import page.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id Retreat ID.
	 */
	private function attendants_import_page( $retreat_id ) {
		if ( ! $retreat_id ) {
			wp_die( esc_html__( 'Invalid retreat ID.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat = $this->retreat_model->get( $retreat_id );
		if ( ! $retreat ) {
			wp_die( esc_html__( 'Retreat not found.', 'dfx-parish-retreat-letters' ) );
		}

		// Check permissions - only retreat managers and plugin administrators can import attendants
		if ( ! $this->permissions->current_user_can_manage_retreat( $retreat_id ) ) {
			wp_die( esc_html__( 'You do not have permission to import attendants for this retreat.', 'dfx-parish-retreat-letters' ) );
		}

		// Handle form submission
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			// Form submission already handled in admin_init, just return to prevent double processing
			return;
		}

		$this->render_csv_import_page( $retreat );
	}

	/**
	 * Handle attendant list page actions.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id Retreat ID.
	 */
	private function handle_attendant_list_actions( $retreat_id ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'dfx_prl_attendants_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) );
		$attendant_id = absint( $_POST['attendant_id'] ?? 0 );

		if ( $action === 'delete' && $attendant_id ) {
			if ( $this->attendant_model->delete( $attendant_id ) ) {
				$this->add_admin_notice( __( 'Attendant deleted successfully.', 'dfx-parish-retreat-letters' ), 'success' );
			} else {
				$this->add_admin_notice( __( 'Error deleting attendant.', 'dfx-parish-retreat-letters' ), 'error' );
			}
		}
	}

	/**
	 * Handle attendant add/edit form submission.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id   Retreat ID.
	 * @param int $attendant_id Attendant ID for editing, 0 for adding.
	 */
	private function handle_attendant_add_edit_submission( $retreat_id, $attendant_id = 0 ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'dfx_prl_attendants_add_edit' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$data = array(
			'retreat_id'                => $retreat_id,
			'name'                      => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'surnames'                  => sanitize_text_field( wp_unslash( $_POST['surnames'] ?? '' ) ),
			'date_of_birth'             => sanitize_text_field( wp_unslash( $_POST['date_of_birth'] ?? '' ) ),
			'emergency_contact_name'    => sanitize_text_field( wp_unslash( $_POST['emergency_contact_name'] ?? '' ) ),
			'emergency_contact_surname' => sanitize_text_field( wp_unslash( $_POST['emergency_contact_surname'] ?? '' ) ),
			'emergency_contact_phone'   => sanitize_text_field( wp_unslash( $_POST['emergency_contact_phone'] ?? '' ) ),
			'emergency_contact_email'   => sanitize_email( wp_unslash( $_POST['emergency_contact_email'] ?? '' ) ),
			'notes'                     => sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ),
		);

		if ( $attendant_id ) {
			// Update existing attendant
			if ( $this->attendant_model->update( $attendant_id, $data ) ) {
				$this->add_admin_notice( __( 'Attendant updated successfully.', 'dfx-parish-retreat-letters' ), 'success' );
				wp_redirect( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat_id ) );
				exit;
			} else {
				$this->add_admin_notice( __( 'Error updating attendant. Please check your data.', 'dfx-parish-retreat-letters' ), 'error' );
			}
		} else {
			// Create new attendant
			$new_id = $this->attendant_model->create( $data );
			if ( $new_id ) {
				$this->add_admin_notice( __( 'Attendant created successfully.', 'dfx-parish-retreat-letters' ), 'success' );
				wp_redirect( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat_id ) );
				exit;
			} else {
				$this->add_admin_notice( __( 'Error creating attendant. Please check your data.', 'dfx-parish-retreat-letters' ), 'error' );
			}
		}
	}

	/**
	 * Handle CSV import submission.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id Retreat ID.
	 */
	private function handle_csv_import( $retreat_id ) {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'dfx_prl_attendants_import' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		// Save the date format preference
		$date_format_preference = sanitize_text_field( wp_unslash( $_POST['date_format_preference'] ?? 'dmy' ) );
		if ( in_array( $date_format_preference, array( 'dmy', 'mdy' ), true ) ) {
			update_option( 'dfx_prl_retreat_letters_date_format', $date_format_preference );
		}

		if ( ! isset( $_FILES['csv_file'] ) || ! isset( $_FILES['csv_file']['error'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
			$this->add_admin_notice( __( 'Please select a valid CSV file.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		$file = array_map( 'sanitize_text_field', $_FILES['csv_file'] );
		$file_path = $file['tmp_name'];

		// Basic file validation
		if ( $file['size'] > 2 * 1024 * 1024 ) { // 2MB limit
			$this->add_admin_notice( __( 'CSV file is too large. Maximum size is 2MB.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		// Initialize WordPress filesystem for compatibility, though we need to use direct file operations for CSV streaming
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		$handle = fopen( $file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $handle ) {
			$this->add_admin_notice( __( 'Unable to read CSV file.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		$imported = 0;
		$updated = 0;
		$skipped = 0;
		$errors = 0;
		$line_number = 0;
		$error_details = array();
		$ambiguous_dates = array();

		// Read header row for field mapping
		$headers = fgetcsv($handle, 0, ',', '"', '\\');
		$line_number++;

		if ( ! $headers ) {
			$this->add_admin_notice( __( 'CSV file appears to be empty or invalid.', 'dfx-parish-retreat-letters' ), 'error' );
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return;
		}

		// Create field mapping from headers
		$field_map = $this->create_field_mapping( $headers );

		// Check if we have the required fields
		$missing_fields = $this->get_missing_required_fields( $field_map );
		if ( ! empty( $missing_fields ) ) {
			$this->add_admin_notice(
				sprintf(
					/* translators: %s: List of missing field names */
					__( 'Required fields missing from CSV: %s', 'dfx-parish-retreat-letters' ),
					implode( ', ', $missing_fields )
				),
				'error'
			);
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return;
		}

		while ( ( $row = fgetcsv($handle, 0, ',', '"', '\\') ) !== false ) {
			$line_number++;

			// Skip empty rows
			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			// Map row data using field mapping
			$mapped_data = $this->map_csv_row_data( $row, $field_map, $ambiguous_dates );

			if ( ! $mapped_data ) {
				$errors++;
				/* translators: %d: line number where the error occurred */
				$error_details[] = sprintf( __( 'Line %d: Invalid data format', 'dfx-parish-retreat-letters' ), $line_number );
				continue;
			}

			$mapped_data['retreat_id'] = $retreat_id;

			// Check if attendant already exists with the same name and date of birth
			$existing_attendant_id = $this->attendant_model->get_id_by_identity(
				$retreat_id,
				$mapped_data['name'],
				$mapped_data['surnames'],
				$mapped_data['date_of_birth']
			);

			if ( $existing_attendant_id ) {
				// Update emergency contact information for existing attendant
				$emergency_contact_data = array(
					'emergency_contact_name'    => $mapped_data['emergency_contact_name'],
					'emergency_contact_surname' => $mapped_data['emergency_contact_surname'],
					'emergency_contact_phone'   => $mapped_data['emergency_contact_phone'],
					'emergency_contact_email'   => $mapped_data['emergency_contact_email'],
				);

				if ( $this->attendant_model->update_emergency_contact( $existing_attendant_id, $emergency_contact_data ) ) {
					$updated++;
				} else {
					$errors++;
					/* translators: %d: line number where the error occurred */
					$error_details[] = sprintf( __( 'Line %d: Failed to update emergency contact for existing attendant', 'dfx-parish-retreat-letters' ), $line_number );
				}
			} else {
				// Create new attendant
				if ( $this->attendant_model->create( $mapped_data ) ) {
					$imported++;
				} else {
					$errors++;
					/* translators: %d: line number where the error occurred */
					$error_details[] = sprintf( __( 'Line %d: Failed to create attendant', 'dfx-parish-retreat-letters' ), $line_number );
				}
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( $imported > 0 ) {
			$this->add_admin_notice(
				sprintf(
					/* translators: %d: Number of imported attendants */
					__( 'Successfully imported %d new attendants.', 'dfx-parish-retreat-letters' ),
					$imported
				),
				'success'
			);
		}

		if ( $updated > 0 ) {
			$this->add_admin_notice(
				sprintf(
					/* translators: %d: Number of updated attendants */
					__( 'Successfully updated emergency contact information for %d existing attendants.', 'dfx-parish-retreat-letters' ),
					$updated
				),
				'success'
			);
		}

		if ( $skipped > 0 ) {
			$this->add_admin_notice(
				sprintf(
					/* translators: %d: Number of skipped attendants */
					__( '%d rows were skipped due to data issues.', 'dfx-parish-retreat-letters' ),
					$skipped
				),
				'info'
			);
		}

		if ( $errors > 0 ) {
			$error_message = sprintf(
				/* translators: %d: Number of errors */
				__( '%d rows had errors and were not imported.', 'dfx-parish-retreat-letters' ),
				$errors
			);

			// Add detailed error information if available
			if ( ! empty( $error_details ) && count( $error_details ) <= 10 ) {
				$error_message .= '<br><strong>' . esc_html__( 'Error details:', 'dfx-parish-retreat-letters' ) . '</strong><br>';
				$error_message .= implode( '<br>', array_slice( $error_details, 0, 10 ) );
				if ( count( $error_details ) > 10 ) {
					$error_message .= '<br>' . esc_html__( '...and more errors.', 'dfx-parish-retreat-letters' );
				}
			}

			$this->add_admin_notice( $error_message, 'warning' );
		}

		// Warn about ambiguous dates if any were found
		if ( ! empty( $ambiguous_dates ) ) {
			$unique_ambiguous = array_unique( $ambiguous_dates );
			$current_preference = get_option( 'dfx_prl_retreat_letters_date_format', 'dmy' );

			$preference_text = '';
			switch ( $current_preference ) {
				case 'dmy':
					$preference_text = __( 'DD/MM/YYYY (Day/Month/Year)', 'dfx-parish-retreat-letters' );
					break;
				case 'mdy':
					$preference_text = __( 'MM/DD/YYYY (Month/Day/Year)', 'dfx-parish-retreat-letters' );
					break;
				default:
					$preference_text = __( 'DD/MM/YYYY (Day/Month/Year)', 'dfx-parish-retreat-letters' );
					break;
			}

			$ambiguous_message = sprintf(
				/* translators: %1$d: Number of ambiguous dates, %2$s: Current preference */
				__( 'Warning: %1$d ambiguous date(s) were interpreted using your current preference (%2$s).', 'dfx-parish-retreat-letters' ),
				count( $unique_ambiguous ),
				$preference_text
			);

			if ( count( $unique_ambiguous ) <= 5 ) {
				$ambiguous_message .= '<br><strong>' . esc_html__( 'Ambiguous dates found:', 'dfx-parish-retreat-letters' ) . '</strong> ' . esc_html( implode( ', ', $unique_ambiguous ) );
			}

			$ambiguous_message .= '<br>' . esc_html__( 'To avoid ambiguity in future imports, consider using YYYY-MM-DD format.', 'dfx-parish-retreat-letters' );

			$this->add_admin_notice( $ambiguous_message, 'info' );
		}

		wp_redirect( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat_id ) );
		exit;
	}

	/**
	 * Create field mapping from CSV headers.
	 *
	 * @since 1.0.0
	 * @param array $headers CSV headers.
	 * @return array Field mapping array.
	 */
	private function create_field_mapping( $headers ) {
		$field_map = array();

		// Define field mappings for English and Spanish
		$field_mappings = array(
			/* translators: JSON string with list of allowed headers for field name. */
			'name' => json_decode( __('["name", "first name"]', 'dfx-parish-retreat-letters' ), true ),
			/* translators: JSON string with list of allowed headers for field surname */
			'surnames' => json_decode( __('["surname", "surnames", "last name"]', 'dfx-parish-retreat-letters' ), true ),
			/* translators: JSON string with list of allowed headers for field date_of_birth */
			'date_of_birth' => json_decode( __('["date of birth", "birth date", "dob", "birthdate"]', 'dfx-parish-retreat-letters' ), true ),
			/* translators: JSON string with list of allowed headers for field emergency_contact_name */
			'emergency_contact_name' => json_decode( __('["emergency contact name", "emergency name", "contact name","emergency contact first name", "emergency first name", "contact first name"]', 'dfx-parish-retreat-letters' ), true ),
			/* translators: JSON string with list of allowed headers for field emergency_contact_surname */
			'emergency_contact_surname' => json_decode( __('["emergency contact surname", "emergency surname", "contact surname", "emergency contact last name", "emergency last name", "contact last name"]', 'dfx-parish-retreat-letters' ), true ),
			/* translators: JSON string with list of allowed headers for field emergency_contact_phone */
			'emergency_contact_phone' => json_decode( __('["emergency contact phone", "emergency phone", "contact phone", "phone"]', 'dfx-parish-retreat-letters' ), true ),
			/* translators: JSON string with list of allowed headers for field emergency_contact_email */
			'emergency_contact_email' => json_decode( __('["emergency contact email", "emergency email", "contact email", "email"]', 'dfx-parish-retreat-letters' ), true ),
			/* translators: JSON string with list of allowed headers for field notes */
			'notes' => json_decode( __('["notes", "note", "comments", "comment"]', 'dfx-parish-retreat-letters' ), true ),
		);

		// Normalize headers (lowercase, trim)
		$normalized_headers = array_map( function( $header ) {
			// Remove any BOM (Byte Order Mark) if present
			$header = preg_replace( '/^\xEF\xBB\xBF/', '', $header );
			// Convert accents to ASCII, lowercase, and trim whitespace
			$header = remove_accents( $header );
			$header = preg_replace( '/\s+/', ' ', $header ); // Replace multiple spaces with single space
			return strtolower( trim( $header ) );
		}, $headers );

		$normalized_field_mappings = array_map( function( $names ) {
			return array_map( function( $name ) {
				// Convert accents to ASCII, lowercase, and trim whitespace
				$name = remove_accents( $name );
				$name = preg_replace( '/\s+/', ' ', $name ); // Replace multiple spaces with single space
				return strtolower( trim( $name ) );
			}, $names );
		}, $field_mappings );

		// Map each field
		foreach ( $normalized_field_mappings as $field => $all_possible_names ) {

			foreach ( $normalized_headers as $index => $header ) {
				if ( in_array( $header, $all_possible_names, true ) ) {
					$field_map[ $field ] = $index;
					break;
				}
			}
		}

		return $field_map;
	}

	/**
	 * Get missing required fields from field mapping.
	 *
	 * @since 1.0.0
	 * @param array $field_map Field mapping array.
	 * @return array Array of missing required field names.
	 */
	private function get_missing_required_fields( $field_map ) {
		$required_fields = array( 'name', 'surnames', 'date_of_birth', 'emergency_contact_name', 'emergency_contact_phone' );
		$missing_fields = array();

		foreach ( $required_fields as $field ) {
			if ( ! isset( $field_map[ $field ] ) ) {
				// Convert field name to user-friendly name
				switch ( $field ) {
					case 'name':
						$missing_fields[] = __( 'Name', 'dfx-parish-retreat-letters' );
						break;
					case 'surnames':
						$missing_fields[] = __( 'Surnames', 'dfx-parish-retreat-letters' );
						break;
					case 'date_of_birth':
						$missing_fields[] = __( 'Date of Birth', 'dfx-parish-retreat-letters' );
						break;
					case 'emergency_contact_name':
						$missing_fields[] = __( 'Emergency Contact Name', 'dfx-parish-retreat-letters' );
						break;
					case 'emergency_contact_surname':
						$missing_fields[] = __( 'Emergency Contact Surname', 'dfx-parish-retreat-letters' );
						break;
					case 'emergency_contact_phone':
						$missing_fields[] = __( 'Emergency Contact Phone', 'dfx-parish-retreat-letters' );
						break;
				}
			}
		}

		return $missing_fields;
	}

	/**
	 * Map CSV row data using field mapping.
	 *
	 * @since 1.0.0
	 * @param array $row CSV row data.
	 * @param array $field_map Field mapping array.
	 * @return array|false Mapped data or false on failure.
	 */
	private function map_csv_row_data( $row, $field_map, &$ambiguous_dates = null ) {
		$mapped_data = array();

		// Map each required field
		foreach ( $field_map as $field => $index ) {
			if ( ! isset( $row[ $index ] ) ) {
				return false;
			}
			$mapped_data[ $field ] = trim( $row[ $index ] );
		}

		// Special handling for date of birth - try to parse different formats
		if ( isset( $mapped_data['date_of_birth'] ) ) {
			// Check if date is ambiguous before parsing
			if ( is_array( $ambiguous_dates ) && $this->is_ambiguous_date( $mapped_data['date_of_birth'] ) ) {
				$ambiguous_dates[] = $mapped_data['date_of_birth'];
			}

			$parsed_date = $this->parse_flexible_date( $mapped_data['date_of_birth'] );
			if ( $parsed_date ) {
				$mapped_data['date_of_birth'] = $parsed_date;
			} else {
				// If date parsing fails, return false
				return false;
			}
		}

		// Ensure emergency_contact_email is always present (optional field)
		if ( ! isset( $mapped_data['emergency_contact_email'] ) ) {
			$mapped_data['emergency_contact_email'] = '';
		}
		
		// Ensure notes is always present (optional field)
		if ( ! isset( $mapped_data['notes'] ) ) {
			$mapped_data['notes'] = '';
		}

		return $mapped_data;
	}

	/**
	 * Parse date in various formats and return standardized format.
	 *
	 * @since 1.0.0
	 * @param string $date_string Date string in various formats.
	 * @param string $preferred_format Optional preferred format hint.
	 * @return string|false Standardized date (Y-m-d) or false on failure.
	 */
	private function parse_flexible_date( $date_string, $preferred_format = '' ) {
		if ( empty( $date_string ) ) {
			return false;
		}

		// Remove extra whitespace
		$date_string = trim( $date_string );

		// Get user's preferred date format from settings (only for ambiguous dates)
		if ( empty( $preferred_format ) ) {
			$preferred_format = get_option( 'dfx_prl_retreat_letters_date_format', 'dmy' );
		}

		// Try to auto-detect format based on unambiguous dates first
		$detected_format = $this->detect_date_format( $date_string );
		if ( $detected_format ) {
			$date = DateTime::createFromFormat( $detected_format, $date_string );
			if ( $date && $date->format( $detected_format ) === $date_string ) {
				if ( $this->is_reasonable_date( $date ) ) {
					return $date->format( 'Y-m-d' );
				}
			}
		}

		// For ambiguous dates, use user preference
		$formats = $this->get_date_formats_by_preference( $preferred_format );

		foreach ( $formats as $format ) {
			$date = DateTime::createFromFormat( $format, $date_string );
			if ( $date && $date->format( $format ) === $date_string ) {
				if ( $this->is_reasonable_date( $date ) ) {
					return $date->format( 'Y-m-d' );
				}
			}
		}

		// Try natural language parsing as last resort
		$timestamp = strtotime( $date_string );
		if ( $timestamp !== false ) {
			$date = new DateTime();
			$date->setTimestamp( $timestamp );

			if ( $this->is_reasonable_date( $date ) ) {
				return $date->format( 'Y-m-d' );
			}
		}

		return false;
	}

	/**
	 * Detect date format for unambiguous dates.
	 *
	 * @since 1.0.0
	 * @param string $date_string Date string.
	 * @return string|false Detected format or false if ambiguous.
	 */
	private function detect_date_format( $date_string ) {
		// Regular expressions for different date formats
		$patterns = array(
			'Y-m-d' => '/^(\d{4})-(\d{1,2})-(\d{1,2})$/',    // 2023-01-15
			'Y/m/d' => '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/',   // 2023/01/15
			'd/m/Y' => '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/',   // 15/01/2023
			'm/d/Y' => '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/',   // 01/15/2023 (same pattern as d/m/Y)
			'd-m-Y' => '/^(\d{1,2})-(\d{1,2})-(\d{4})$/',     // 15-01-2023
			'm-d-Y' => '/^(\d{1,2})-(\d{1,2})-(\d{4})$/',     // 01-15-2023 (same pattern as d-m-Y)
			'd.m.Y' => '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/',   // 15.01.2023
			'm.d.Y' => '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/',   // 01.15.2023 (same pattern as d.m.Y)
		);

		foreach ( $patterns as $format => $pattern ) {
			if ( preg_match( $pattern, $date_string, $matches ) ) {
				// For YYYY-MM-DD and YYYY/MM/DD formats, they're unambiguous
				if ( in_array( $format, array( 'Y-m-d', 'Y/m/d' ), true ) ) {
					return $format;
				}

				// For other formats, check if day > 12 to determine if it's unambiguous
				$part1 = intval( $matches[1] );
				$part2 = intval( $matches[2] );

				// If either part is > 12, we can determine the format
				if ( $part1 > 12 ) {
					// First part is day, so format is d/m/Y, d-m-Y, or d.m.Y
					if ( strpos( $format, 'd/' ) === 0 || strpos( $format, 'd-' ) === 0 || strpos( $format, 'd.' ) === 0 ) {
						return $format;
					}
				} elseif ( $part2 > 12 ) {
					// Second part is day, so format is m/d/Y, m-d-Y, or m.d.Y
					if ( strpos( $format, 'm/' ) === 0 || strpos( $format, 'm-' ) === 0 || strpos( $format, 'm.' ) === 0 ) {
						return $format;
					}
				}
			}
		}

		return false; // Ambiguous or not recognized
	}

	/**
	 * Get date formats ordered by preference.
	 *
	 * @since 1.0.0
	 * @param string $preferred_format User's preferred format.
	 * @return array Ordered array of date formats.
	 */
	private function get_date_formats_by_preference( $preferred_format ) {
		switch ( $preferred_format ) {
			case 'dmy':
				// Day/Month/Year preference - prioritize d/m/Y formats
				return array(
					'Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y', 'd-m-y',
					'm/d/Y', 'm-d-Y', 'm.d.Y', 'm/d/y', 'm-d-y'
				);
			case 'mdy':
				// Month/Day/Year preference - prioritize m/d/Y formats
				return array(
					'Y-m-d', 'Y/m/d', 'm/d/Y', 'm-d-Y', 'm.d.Y', 'm/d/y', 'm-d-y',
					'd/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y', 'd-m-y'
				);
			default:
				// Default to Day/Month/Year format if invalid preference
				return array(
					'Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'd/m/y', 'd-m-y',
					'm/d/Y', 'm-d-Y', 'm.d.Y', 'm/d/y', 'm-d-y'
				);
		}
	}

	/**
	 * Check if a date is within reasonable bounds.
	 *
	 * @since 1.0.0
	 * @param DateTime $date Date object to validate.
	 * @return bool True if reasonable, false otherwise.
	 */
	private function is_reasonable_date( $date ) {
		$now = new DateTime();
		$min_date = new DateTime( '1900-01-01' );

		return $date <= $now && $date >= $min_date;
	}

	/**
	 * Check if a date string is ambiguous.
	 *
	 * @since 1.0.0
	 * @param string $date_string Date string to check.
	 * @return bool True if ambiguous, false if unambiguous.
	 */
	private function is_ambiguous_date( $date_string ) {
		return $this->detect_date_format( $date_string ) === false;
	}

	/**
	 * AJAX handler for deleting attendants.
	 *
	 * @since 1.0.0
	 */
	public function ajax_delete_attendant() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$attendant_id = absint( $_POST['attendant_id'] ?? 0 );

		if ( $this->attendant_model->delete( $attendant_id ) ) {
			wp_send_json_success( array( 'message' => __( 'Attendant deleted successfully.', 'dfx-parish-retreat-letters' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Error deleting attendant.', 'dfx-parish-retreat-letters' ) ) );
		}
	}

	/**
	 * Export attendants CSV.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id Retreat ID.
	 */
	private function export_attendants_csv( $retreat_id ) {
		$retreat = $this->retreat_model->get( $retreat_id );
		if ( ! $retreat ) {
			return;
		}

		// Clear any existing output buffers to prevent HTML from mixing with CSV
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		$csv_data = $this->attendant_model->export_csv_data( $retreat_id );
		// translators: %1$s is the retreat name, %2$s is the current date (YYYY-MM-DD)
		$filename = sprintf(__('retreat-%1$s-attendants-%2$s.csv', 'dfx-parish-retreat-letters'),
			sanitize_file_name( $retreat->name ), gmdate( 'Y-m-d' ) );

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// Write BOM for UTF-8
		fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );

		// Write headers
		fputcsv($output, $csv_data['headers'], ',', '"', '\\');

		// Write data
		foreach ( $csv_data['rows'] as $row ) {
			fputcsv($output, $row, ',', '"', '\\');
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * AJAX handler for CSV export.
	 *
	 * @since 1.0.0
	 */
	public function ajax_export_attendants_csv() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat_id = absint( $_POST['retreat_id'] ?? 0 );
		$this->export_attendants_csv( $retreat_id );
	}

	/**
	 * Render the attendants list page.
	 *
	 * @since 1.0.0
	 * @param object $retreat     Retreat object.
	 * @param array  $attendants  Array of attendant objects.
	 * @param string $search      Current search term.
	 * @param int    $page_num    Current page number.
	 * @param int    $total_pages Total number of pages.
	 * @param int    $total_items Total number of items.
	 */
	private function render_attendants_list_page( $retreat, $attendants, $search, $page_num, $total_pages, $total_items ) {
		// Check if retreat has any responsible persons defined
		$responsible_persons = $this->responsible_person_model->get_by_retreat( $retreat->id );
		$has_responsible_persons = ! empty( $responsible_persons );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php
				printf(
					/* translators: %s: Retreat name */
					esc_html__( 'Attendants for %s', 'dfx-parish-retreat-letters' ),
					esc_html( $retreat->name )
				);
				?>
			</h1>
			<?php if ( $this->permissions->current_user_can_manage_retreat( $retreat->id ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=add_attendant&retreat_id=' . $retreat->id ) ); ?>" class="page-title-action">
					<?php esc_html_e( 'Add New Attendant', 'dfx-parish-retreat-letters' ); ?>
				</a>
			<?php endif; ?>
			<hr class="wp-header-end">

			<!-- Breadcrumb -->
			<p class="description">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats' ) ); ?>"><?php esc_html_e( 'Retreats', 'dfx-parish-retreat-letters' ); ?></a>
				&gt; <?php echo esc_html( $retreat->name ); ?>
				&gt; <?php esc_html_e( 'Attendants', 'dfx-parish-retreat-letters' ); ?>
			</p>

			<?php $this->display_admin_notices(); ?>

			<form method="get" action="">
				<input type="hidden" name="page" value="dfx-prl-retreats">
				<input type="hidden" name="action" value="attendants">
				<input type="hidden" name="retreat_id" value="<?php echo esc_attr( $retreat->id ); ?>">
				<p class="search-box">
					<label class="screen-reader-text" for="attendant-search-input"><?php esc_html_e( 'Search Attendants:', 'dfx-parish-retreat-letters' ); ?></label>
					<input type="search" id="attendant-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
					<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Attendants', 'dfx-parish-retreat-letters' ); ?>">
				</p>
			</form>

			<form method="post" action="">
				<?php wp_nonce_field( 'dfx_prl_attendants_action' ); ?>
				<div class="tablenav top">
					<div class="alignleft actions">
						<p><?php
						/* translators: %d: number of attendants */
						printf( esc_html__( 'Total attendants: %d', 'dfx-parish-retreat-letters' ), esc_html( $total_items ) ); ?></p>
						<button type="submit" name="action" value="export_csv" class="button">
							<?php esc_html_e( 'Export CSV', 'dfx-parish-retreat-letters' ); ?>
						</button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=import_attendants&retreat_id=' . $retreat->id ) ); ?>" class="button">
							<?php esc_html_e( 'Import CSV', 'dfx-parish-retreat-letters' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=responsible_persons&retreat_id=' . $retreat->id ) ); ?>" class="button">
							<?php esc_html_e( 'Manage Responsible Persons', 'dfx-parish-retreat-letters' ); ?>
						</a>
					</div>
					<?php if ( $total_pages > 1 ) : ?>
						<div class="tablenav-pages">
							<?php
							echo paginate_links( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'base'    => add_query_arg( 'paged', '%#%' ),
								'format'  => '',
								'current' => $page_num,
								'total'   => $total_pages,
							) );
							?>
						</div>
					<?php endif; ?>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Name', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Surnames', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Date of Birth', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Emergency Contact', 'dfx-parish-retreat-letters' ); ?></th>
							<?php if ( $has_responsible_persons ) : ?>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Responsible Person', 'dfx-parish-retreat-letters' ); ?></th>
							<?php endif; ?>
							<?php if ( ! empty( $retreat->notes_enabled ) ) : ?>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Notes', 'dfx-parish-retreat-letters' ); ?></th>
							<?php endif; ?>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Messages', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Actions', 'dfx-parish-retreat-letters' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $attendants ) ) : ?>
							<?php foreach ( $attendants as $attendant ) : ?>
								<?php
								// Get message count for this attendant
								$message_count = $this->message_model->get_count_by_attendant( $attendant->id );
								// Get non-printed message count for this attendant
								$non_printed_count = $this->message_model->get_non_printed_count_by_attendant( $attendant->id );

								// Get responsible person if assigned
								$responsible_person = null;
								if ( ! empty( $attendant->responsible_person_id ) ) {
									$responsible_person = $this->responsible_person_model->get( $attendant->responsible_person_id );
								}
								?>
								<tr>
									<td>
										<strong>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=edit_attendant&retreat_id=' . $retreat->id . '&attendant_id=' . $attendant->id ) ); ?>">
												<?php echo esc_html( $attendant->name ); ?>
											</a>
										</strong>
									</td>
									<td><?php echo esc_html( $attendant->surnames ); ?></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $attendant->date_of_birth ) ) ); ?></td>
									<td>
										<?php echo esc_html( $attendant->emergency_contact_name . ' ' . $attendant->emergency_contact_surname ); ?><br>
										<small><?php echo esc_html( $attendant->emergency_contact_phone ); ?></small>
										<?php if ( ! empty( $attendant->emergency_contact_email ) ) : ?>
											<br><small><?php echo esc_html( $attendant->emergency_contact_email ); ?></small>
										<?php endif; ?>
									</td>
									<?php if ( $has_responsible_persons ) : ?>
									<td>
										<?php
										if ( $responsible_person ) {
											echo esc_html( $responsible_person->name );
										} else {
											echo '<span class="description">' . esc_html__( 'Not assigned', 'dfx-parish-retreat-letters' ) . '</span>';
										}
										?>
									</td>
									<?php endif; ?>
									<?php if ( ! empty( $retreat->notes_enabled ) ) : ?>
									<td>
										<?php
										if ( ! empty( $attendant->notes ) ) {
											echo wpautop( esc_html( $attendant->notes ) );
										} else {
											echo '<span class="description">' . esc_html__( 'No notes', 'dfx-parish-retreat-letters' ) . '</span>';
										}
										?>
									</td>
									<?php endif; ?>
									<td>
										<?php if ( $message_count > 0 ) : ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-messages&attendant_id=' . $attendant->id ) ); ?>">
												<?php
												printf(
													/* translators: %d: Number of messages */
													esc_html( _n( '%d message', '%d messages', $message_count, 'dfx-parish-retreat-letters' ) ),
													esc_html( $message_count )
												);
												// Show non-printed count if there are any non-printed messages
												if ( $non_printed_count > 0 ) {
													printf(
														/* translators: %d: Number of non-printed messages */
														esc_html__( ' (%d non-printed)', 'dfx-parish-retreat-letters' ),
														esc_html( $non_printed_count )
													);
												}
												?>
											</a>
										<?php else : ?>
											<span class="description"><?php esc_html_e( 'No messages', 'dfx-parish-retreat-letters' ); ?></span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ( $this->permissions->current_user_can_manage_retreat( $retreat->id ) ) : ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=edit_attendant&retreat_id=' . $retreat->id . '&attendant_id=' . $attendant->id ) ); ?>" class="button button-small">
												<?php esc_html_e( 'Edit', 'dfx-parish-retreat-letters' ); ?>
											</a>
										<?php endif; ?>

										<?php if ( $this->permissions->current_user_can_manage_messages( $retreat->id ) ) : ?>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-messages&attendant_id=' . $attendant->id ) ); ?>" class="button button-small">
												<?php esc_html_e( 'Messages', 'dfx-parish-retreat-letters' ); ?>
											</a>
										<?php endif; ?>

										<?php if ( $this->permissions->current_user_can_manage_retreat( $retreat->id ) ) : ?>
											<?php if ( empty( $attendant->message_url_token ) ) : ?>
												<button type="button" class="button button-small dfx-prl-generate-url" data-attendant-id="<?php echo esc_attr( $attendant->id ); ?>">
													<?php esc_html_e( 'Generate Message URL', 'dfx-parish-retreat-letters' ); ?>
												</button>
											<?php else : ?>
												<button type="button" class="button button-small button-primary dfx-prl-copy-url" data-url="<?php echo esc_url( home_url( '/messages/' . $attendant->message_url_token ) ); ?>">
													<?php esc_html_e( 'Copy Message URL', 'dfx-parish-retreat-letters' ); ?>
												</button>
											<?php endif; ?>
										<?php endif; ?>

										<?php if ( $this->permissions->current_user_can_manage_retreat( $retreat->id ) ) : ?>
											<button type="button" class="button button-small button-link-delete dfx-prl-delete-attendant" data-attendant-id="<?php echo esc_attr( $attendant->id ); ?>">
												<?php esc_html_e( 'Delete', 'dfx-parish-retreat-letters' ); ?>
											</button>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6">
									<?php if ( $search ) : ?>
										<?php esc_html_e( 'No attendants found for your search.', 'dfx-parish-retreat-letters' ); ?>
									<?php else : ?>
										<?php esc_html_e( 'No attendants found for this retreat.', 'dfx-parish-retreat-letters' ); ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=add_attendant&retreat_id=' . $retreat->id ) ); ?>">
											<?php esc_html_e( 'Add the first attendant', 'dfx-parish-retreat-letters' ); ?>
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<?php if ( $total_pages > 1 ) : ?>
					<div class="notice notice-info inline" style="margin: 15px 0; padding: 10px;">
						<p>
							<strong><?php esc_html_e( 'Note:', 'dfx-parish-retreat-letters' ); ?></strong>
							<?php
							printf(
								/* translators: %1$d: current page, %2$d: total pages, %3$d: items per page */
								esc_html__( 'Showing page %1$d of %2$d. There are more attendants available. Use the pagination controls above to view all %3$d attendants per page.', 'dfx-parish-retreat-letters' ),
								esc_html( $page_num ),
								esc_html( $total_pages ),
								100
							);
							?>
						</p>
					</div>
				<?php endif; ?>
			</form>

			<?php $this->render_plugin_footer(); ?>
		</div>
		<?php
	}

	/**
	 * Render the attendant add/edit page.
	 *
	 * @since 1.0.0
	 * @param object      $retreat   Retreat object.
	 * @param object|null $attendant Attendant object for editing, null for adding.
	 */
	private function render_attendant_add_edit_page( $retreat, $attendant = null ) {
		$is_edit = ! is_null( $attendant );
		$title = $is_edit ? __( 'Edit Attendant', 'dfx-parish-retreat-letters' ) : __( 'Add New Attendant', 'dfx-parish-retreat-letters' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $title ); ?></h1>
			<hr class="wp-header-end">

			<!-- Breadcrumb -->
			<p class="description">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats' ) ); ?>"><?php esc_html_e( 'Retreats', 'dfx-parish-retreat-letters' ); ?></a>
				&gt; <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>"><?php echo esc_html( $retreat->name ); ?></a>
				&gt; <?php esc_html_e( 'Attendants', 'dfx-parish-retreat-letters' ); ?>
				&gt; <?php echo esc_html( $is_edit ? __( 'Edit', 'dfx-parish-retreat-letters' ) : __( 'Add New', 'dfx-parish-retreat-letters' ) ); ?>
			</p>

			<?php $this->display_admin_notices(); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'dfx_prl_attendants_add_edit' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="name"><?php esc_html_e( 'Name', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'required', 'dfx-parish-retreat-letters' ); ?>)</span></label>
							</th>
							<td>
								<input type="text" id="name" name="name" value="<?php echo esc_attr( $attendant->name ?? '' ); ?>" class="regular-text" required>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="surnames"><?php esc_html_e( 'Surnames', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'required', 'dfx-parish-retreat-letters' ); ?>)</span></label>
							</th>
							<td>
								<input type="text" id="surnames" name="surnames" value="<?php echo esc_attr( $attendant->surnames ?? '' ); ?>" class="regular-text" required>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="date_of_birth"><?php esc_html_e( 'Date of Birth', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'required', 'dfx-parish-retreat-letters' ); ?>)</span></label>
							</th>
							<td>
								<input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo esc_attr( $attendant->date_of_birth ?? '' ); ?>" required>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="emergency_contact_name"><?php esc_html_e( 'Emergency Contact Name', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'required', 'dfx-parish-retreat-letters' ); ?>)</span></label>
							</th>
							<td>
								<input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo esc_attr( $attendant->emergency_contact_name ?? '' ); ?>" class="regular-text" required>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="emergency_contact_surname"><?php esc_html_e( 'Emergency Contact Surname', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'optional', 'dfx-parish-retreat-letters' ); ?>)</span></label>
							</th>
							<td>
								<input type="text" id="emergency_contact_surname" name="emergency_contact_surname" value="<?php echo esc_attr( $attendant->emergency_contact_surname ?? '' ); ?>" class="regular-text">
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="emergency_contact_phone"><?php esc_html_e( 'Emergency Contact Phone', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'required', 'dfx-parish-retreat-letters' ); ?>)</span></label>
							</th>
							<td>
								<input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo esc_attr( $attendant->emergency_contact_phone ?? '' ); ?>" class="regular-text" required>
								<p class="description"><?php esc_html_e( 'Enter phone number with area code.', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="emergency_contact_email"><?php esc_html_e( 'Emergency Contact Email', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'optional', 'dfx-parish-retreat-letters' ); ?>)</span></label>
							</th>
							<td>
								<input type="email" id="emergency_contact_email" name="emergency_contact_email" value="<?php echo esc_attr( $attendant->emergency_contact_email ?? '' ); ?>" class="regular-text">
								<p class="description"><?php esc_html_e( 'Enter email address for emergency contact.', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>
						<?php if ( ! empty( $retreat->notes_enabled ) ) : ?>
						<tr>
							<th scope="row">
								<label for="notes"><?php esc_html_e( 'Notes', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'optional', 'dfx-parish-retreat-letters' ); ?>)</span></label>
							</th>
							<td>
								<textarea id="notes" name="notes" rows="4" class="large-text"><?php echo esc_textarea( $attendant->notes ?? '' ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Optional notes for this attendant.', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>
						<?php endif; ?>
						<?php
						// Only show responsible person field if there are responsible persons defined for this retreat
						$responsible_persons = $this->responsible_person_model->get_by_retreat( $retreat->id );
						if ( ! empty( $responsible_persons ) ) :
						?>
						<tr>
							<th scope="row">
								<label for="responsible_person_id"><?php esc_html_e( 'Responsible Person', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'optional', 'dfx-parish-retreat-letters' ); ?>)</span></label>
							</th>
							<td>
								<select id="responsible_person_id" name="responsible_person_id" class="regular-text">
									<option value=""><?php esc_html_e( '-- Select Responsible Person --', 'dfx-parish-retreat-letters' ); ?></option>
									<?php foreach ( $responsible_persons as $person ) : ?>
										<option value="<?php echo esc_attr( $person->id ); ?>" <?php selected( $attendant->responsible_person_id ?? '', $person->id ); ?>>
											<?php echo esc_html( $person->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php esc_html_e( 'Select the person responsible for this attendant.', 'dfx-parish-retreat-letters' ); ?>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=responsible_persons&retreat_id=' . $retreat->id ) ); ?>" target="_blank">
										<?php esc_html_e( 'Manage responsible persons', 'dfx-parish-retreat-letters' ); ?>
									</a>
								</p>
							</td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<?php if ( $is_edit ) : ?>
					<?php
					// Get message count for this attendant
					$message_count = $this->message_model->get_count_by_attendant( $attendant->id );
					?>
					<hr>
					<h2><?php esc_html_e( 'Confidential Messages', 'dfx-parish-retreat-letters' ); ?></h2>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><?php esc_html_e( 'Messages', 'dfx-parish-retreat-letters' ); ?></th>
								<td>
									<?php if ( $message_count > 0 ) : ?>
										<p>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-messages&attendant_id=' . $attendant->id ) ); ?>" class="button">
												<?php
												printf(
													/* translators: %d: Number of messages */
													esc_html( _n( 'View %d message', 'View %d messages', $message_count, 'dfx-parish-retreat-letters' ) ),
													esc_html( $message_count )
												);
												?>
											</a>
										</p>
									<?php else : ?>
										<p class="description"><?php esc_html_e( 'No messages received yet.', 'dfx-parish-retreat-letters' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Message URL', 'dfx-parish-retreat-letters' ); ?></th>
								<td>
									<?php if ( empty( $attendant->message_url_token ) ) : ?>
										<p>
											<button type="button" class="button dfx-prl-generate-url" data-attendant-id="<?php echo esc_attr( $attendant->id ); ?>">
												<?php esc_html_e( 'Generate Message URL', 'dfx-parish-retreat-letters' ); ?>
											</button>
										</p>
										<p class="description"><?php esc_html_e( 'Generate a secure URL that can be shared with this attendant to receive confidential messages.', 'dfx-parish-retreat-letters' ); ?></p>
									<?php else : ?>
										<p>
											<button type="button" class="button button-primary dfx-prl-copy-url" data-url="<?php echo esc_url( home_url( '/messages/' . $attendant->message_url_token ) ); ?>">
												<?php esc_html_e( 'Copy Message URL', 'dfx-parish-retreat-letters' ); ?>
											</button>
										</p>
										<p class="description"><?php esc_html_e( 'Share this secure URL with the attendant to receive confidential messages.', 'dfx-parish-retreat-letters' ); ?></p>
									<?php endif; ?>
								</td>
							</tr>
						</tbody>
					</table>
				<?php endif; ?>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr( $is_edit ? __( 'Update Attendant', 'dfx-parish-retreat-letters' ) : __( 'Add Attendant', 'dfx-parish-retreat-letters' ) ); ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'dfx-parish-retreat-letters' ); ?>
					</a>
				</p>
			</form>

			<?php $this->render_plugin_footer(); ?>
		</div>
		<?php
	}

	/**
	 * Render the CSV import page.
	 *
	 * @since 1.0.0
	 * @param object $retreat Retreat object.
	 */
	private function render_csv_import_page( $retreat ) {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import Attendants from CSV', 'dfx-parish-retreat-letters' ); ?></h1>
			<hr class="wp-header-end">

			<!-- Breadcrumb -->
			<p class="description">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats' ) ); ?>"><?php esc_html_e( 'Retreats', 'dfx-parish-retreat-letters' ); ?></a>
				&gt; <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>"><?php echo esc_html( $retreat->name ); ?></a>
				&gt; <?php esc_html_e( 'Attendants', 'dfx-parish-retreat-letters' ); ?>
				&gt; <?php esc_html_e( 'Import CSV', 'dfx-parish-retreat-letters' ); ?>
			</p>

			<?php $this->display_admin_notices(); ?>

			<div class="notice notice-info">
				<p><strong><?php esc_html_e( 'CSV Import Instructions', 'dfx-parish-retreat-letters' ); ?></strong></p>
				<p><?php esc_html_e( 'Your CSV file should contain the following required columns. Column order is flexible and the system will automatically identify columns by their names:', 'dfx-parish-retreat-letters' ); ?></p>
				<ul>
					<li><strong><?php esc_html_e( 'Name', 'dfx-parish-retreat-letters' ); ?></strong> <?php esc_html_e( '(or "Nombre")', 'dfx-parish-retreat-letters' ); ?></li>
					<li><strong><?php esc_html_e( 'Surnames', 'dfx-parish-retreat-letters' ); ?></strong> <?php esc_html_e( '(or "Apellidos")', 'dfx-parish-retreat-letters' ); ?></li>
					<li><strong><?php esc_html_e( 'Date of Birth', 'dfx-parish-retreat-letters' ); ?></strong> <?php esc_html_e( '(or "Fecha de Nacimiento")', 'dfx-parish-retreat-letters' ); ?></li>
					<li><strong><?php esc_html_e( 'Emergency Contact Name', 'dfx-parish-retreat-letters' ); ?></strong> <?php esc_html_e( '(or "Nombre del Contacto de Emergencia")', 'dfx-parish-retreat-letters' ); ?></li>
					<li><strong><?php esc_html_e( 'Emergency Contact Surname', 'dfx-parish-retreat-letters' ); ?></strong> <?php esc_html_e( '(or "Apellido del Contacto de Emergencia")', 'dfx-parish-retreat-letters' ); ?></li>
					<li><strong><?php esc_html_e( 'Emergency Contact Phone', 'dfx-parish-retreat-letters' ); ?></strong> <?php esc_html_e( '(or "Teléfono del Contacto de Emergencia")', 'dfx-parish-retreat-letters' ); ?></li>
					<li><strong><?php esc_html_e( 'Emergency Contact Email', 'dfx-parish-retreat-letters' ); ?></strong> <?php esc_html_e( '(optional, or "Correo del Contacto de Emergencia")', 'dfx-parish-retreat-letters' ); ?></li>
				</ul>
				<p><?php esc_html_e( 'Additional features:', 'dfx-parish-retreat-letters' ); ?></p>
				<ul>
					<li><?php esc_html_e( 'Column order can be any order', 'dfx-parish-retreat-letters' ); ?></li>
					<li><?php esc_html_e( 'Extra columns are allowed and will be ignored', 'dfx-parish-retreat-letters' ); ?></li>
					<li><?php esc_html_e( 'Date formats supported: YYYY-MM-DD, DD/MM/YYYY, MM/DD/YYYY, DD-MM-YYYY, MM-DD-YYYY, DD.MM.YYYY', 'dfx-parish-retreat-letters' ); ?></li>
					<li><?php esc_html_e( 'Column names can be in English or Spanish', 'dfx-parish-retreat-letters' ); ?></li>
					<li><?php esc_html_e( 'The first row must contain column headers', 'dfx-parish-retreat-letters' ); ?></li>
				</ul>
				<div class="notice notice-warning">
					<p><strong><?php esc_html_e( 'Important Note about Date Formats:', 'dfx-parish-retreat-letters' ); ?></strong></p>
					<p><?php esc_html_e( 'For ambiguous dates like "01/10/2025", the system cannot determine if this means "January 10th" or "October 1st". To avoid confusion:', 'dfx-parish-retreat-letters' ); ?></p>
					<ul>
						<li><?php esc_html_e( 'Use unambiguous formats like YYYY-MM-DD (2025-01-10)', 'dfx-parish-retreat-letters' ); ?></li>
						<li><?php esc_html_e( 'Set your preferred date format below to ensure consistent interpretation', 'dfx-parish-retreat-letters' ); ?></li>
					</ul>
				</div>
			</div>

			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'dfx_prl_attendants_import' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="date_format_preference"><?php esc_html_e( 'Date Format Preference', 'dfx-parish-retreat-letters' ); ?></label>
							</th>
							<td>
								<?php $current_preference = get_option( 'dfx_prl_retreat_letters_date_format', 'dmy' ); ?>
								<select id="date_format_preference" name="date_format_preference">
									<option value="dmy" <?php selected( $current_preference, 'dmy' ); ?>><?php esc_html_e( 'DD/MM/YYYY (Day/Month/Year)', 'dfx-parish-retreat-letters' ); ?></option>
									<option value="mdy" <?php selected( $current_preference, 'mdy' ); ?>><?php esc_html_e( 'MM/DD/YYYY (Month/Day/Year)', 'dfx-parish-retreat-letters' ); ?></option>
								</select>
								<p class="description">
									<?php esc_html_e( 'This preference is used to interpret ambiguous dates like "01/10/2025". Unambiguous dates are always parsed correctly regardless of this setting.', 'dfx-parish-retreat-letters' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="csv_file"><?php esc_html_e( 'CSV File', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'required', 'dfx-parish-retreat-letters' ); ?>)</span></label>
							</th>
							<td>
								<input type="file" id="csv_file" name="csv_file" accept=".csv" required>
								<p class="description"><?php esc_html_e( 'Select a CSV file to import. Maximum file size: 2MB.', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Import Attendants', 'dfx-parish-retreat-letters' ); ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'dfx-parish-retreat-letters' ); ?>
					</a>
				</p>
			</form>

			<?php $this->render_plugin_footer(); ?>
		</div>
		<?php
	}

	/**
	 * AJAX handler for generating message URLs.
	 *
	 * @since 1.2.0
	 */
	public function ajax_generate_message_url() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$attendant_id = absint( $_POST['attendant_id'] ?? 0 );
		if ( ! $attendant_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid attendant ID.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Check if attendant exists
		$attendant = $this->attendant_model->get( $attendant_id );
		if ( ! $attendant ) {
			wp_send_json_error( array( 'message' => __( 'Attendant not found.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Generate unique token
		$token = $this->security->generate_unique_message_token();

		// Update attendant with token
		global $wpdb;
		$database = DFX_Parish_Retreat_Letters_Database::get_instance();
		
		// Direct database query needed for atomic token update with custom table
		// Cache isn't applicable for this security-sensitive operation
		$result = $wpdb->update(
			$database->get_attendants_table(),
			array( 'message_url_token' => $token ),
			array( 'id' => $attendant_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( $result === false ) {
			wp_send_json_error( array( 'message' => __( 'Failed to generate message URL.', 'dfx-parish-retreat-letters' ) ) );
		}

		$message_url = home_url( '/messages/' . $token );

		wp_send_json_success( array(
			'message' => __( 'Message URL generated successfully.', 'dfx-parish-retreat-letters' ),
			'url' => $message_url
		) );
	}

	/**
	 * Display the messages list page.
	 *
	 * @since 1.2.0
	 */
	public function messages_list_page() {
		// Get query parameters
		$search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$retreat_id = absint( $_GET['retreat_id'] ?? 0 );
		$attendant_id = absint( $_GET['attendant_id'] ?? 0 );
		$message_type = sanitize_text_field( wp_unslash( $_GET['message_type'] ?? '' ) );
		$page_num = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$per_page = 100;

		// Messages can only be accessed through attendants - redirect if no attendant_id
		if ( ! $attendant_id ) {
			wp_redirect( admin_url( 'admin.php?page=dfx-prl-retreats' ) );
			exit;
		}

		// Get attendant info for breadcrumb and validation
		$attendant = $this->attendant_model->get( $attendant_id );
		if ( ! $attendant ) {
			wp_redirect( admin_url( 'admin.php?page=dfx-prl-retreats' ) );
			exit;
		}

		// Get messages with metadata
		$args = array(
			'search'       => $search,
			'message_type' => $message_type,
			'per_page'     => $per_page,
			'page'         => $page_num,
		);

		$messages = $this->message_model->get_by_attendant( $attendant_id, $args );
		$total_items = $this->message_model->get_count_by_attendant( $attendant_id, $args );
		$total_pages = ceil( $total_items / $per_page );

		$this->render_messages_list_page( $messages, array(), $search, 0, $attendant_id, $message_type, $page_num, $total_pages, $total_items, $attendant );
	}

	/**
	 * Render the messages list page.
	 *
	 * @since 1.2.0
	 * @param array  $messages     Array of message objects.
	 * @param array  $retreats     Array of retreat objects for filter.
	 * @param string $search       Current search term.
	 * @param int    $retreat_id   Current retreat filter.
	 * @param int    $attendant_id Current attendant filter.
	 * @param string $message_type Current message type filter.
	 * @param int    $page_num     Current page number.
	 * @param int    $total_pages  Total number of pages.
	 * @param int    $total_items  Total number of items.
	 * @param object $attendant    Attendant object if filtering by attendant.
	 */
	private function render_messages_list_page( $messages, $retreats, $search, $retreat_id, $attendant_id, $message_type, $page_num, $total_pages, $total_items, $attendant = null ) {
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php
				if ( $attendant ) {
					printf(
						/* translators: %s: Attendant name */
						esc_html__( 'Messages for %s', 'dfx-parish-retreat-letters' ),
						esc_html( $attendant->name . ' ' . $attendant->surnames )
					);
				} else {
					esc_html_e( 'Confidential Messages', 'dfx-parish-retreat-letters' );
				}
				?>
			</h1>
			<hr class="wp-header-end">

			<?php if ( $attendant ) : ?>
				<?php
				// Get retreat information for breadcrumb
				$retreat = $this->retreat_model->get( $attendant->retreat_id );
				?>
				<!-- Breadcrumb -->
				<p class="description">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats' ) ); ?>"><?php esc_html_e( 'Retreats', 'dfx-parish-retreat-letters' ); ?></a>
					<?php if ( $retreat ) : ?>
						&gt; <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>"><?php echo esc_html( $retreat->name ); ?></a>
					<?php endif; ?>
					&gt; <?php echo esc_html( $attendant->name . ' ' . $attendant->surnames ); ?>
				</p>
			<?php endif; ?>

			<?php $this->display_admin_notices(); ?>

			<!-- Security Notice -->
			<div class="notice notice-warning">
				<p><strong><?php esc_html_e( 'Security Notice:', 'dfx-parish-retreat-letters' ); ?></strong> <?php esc_html_e( 'Message content is encrypted and can only be viewed when printed. This page shows metadata only for privacy and security compliance.', 'dfx-parish-retreat-letters' ); ?></p>
			</div>

			<!-- Filters -->
			<form method="get" action="">
				<input type="hidden" name="page" value="dfx-prl-messages">
				<input type="hidden" name="attendant_id" value="<?php echo esc_attr( $attendant_id ); ?>">

				<div class="tablenav top">
					<div class="alignleft actions">
						<select name="message_type">
							<option value=""><?php esc_html_e( 'All Types', 'dfx-parish-retreat-letters' ); ?></option>
							<option value="text" <?php selected( $message_type, 'text' ); ?>><?php esc_html_e( 'Text Messages', 'dfx-parish-retreat-letters' ); ?></option>
							<option value="file" <?php selected( $message_type, 'file' ); ?>><?php esc_html_e( 'Messages with Files', 'dfx-parish-retreat-letters' ); ?></option>
						</select>

						<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'dfx-parish-retreat-letters' ); ?>">
					</div>

					<p class="search-box">
						<label class="screen-reader-text" for="message-search-input"><?php esc_html_e( 'Search Messages:', 'dfx-parish-retreat-letters' ); ?></label>
						<input type="search" id="message-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by sender...', 'dfx-parish-retreat-letters' ); ?>">
						<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Messages', 'dfx-parish-retreat-letters' ); ?>">
					</p>
				</div>
			</form>

			<form method="post" action="">
				<?php wp_nonce_field( 'dfx_prl_messages_action' ); ?>
				<div class="tablenav top">
					<div class="alignleft actions">
						<p><?php
						/* translators: %d: number of messages */
						printf( esc_html__( 'Total messages: %d', 'dfx-parish-retreat-letters' ), esc_html( $total_items ) ); ?></p>
					</div>
					<?php if ( $total_pages > 1 ) : ?>
						<div class="tablenav-pages">
							<?php
							echo paginate_links( array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								'base'    => add_query_arg( 'paged', '%#%' ),
								'format'  => '',
								'current' => $page_num,
								'total'   => $total_pages,
							) );
							?>
						</div>
					<?php endif; ?>
				</div>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="manage-column"><?php esc_html_e( 'From', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Type', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Submitted', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Print Status', 'dfx-parish-retreat-letters' ); ?></th>
							<th scope="col" class="manage-column"><?php esc_html_e( 'Actions', 'dfx-parish-retreat-letters' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $messages ) ) : ?>
							<?php foreach ( $messages as $message ) : ?>
								<tr>
									<td><?php echo esc_html( $message->sender_name ?: __( 'Anonymous', 'dfx-parish-retreat-letters' ) ); ?></td>
									<td>
										<?php if ( $message->message_type === 'text' ) : ?>
											<span class="dashicons dashicons-text" title="<?php esc_attr_e( 'Text Message', 'dfx-parish-retreat-letters' ); ?>"></span>
											<?php esc_html_e( 'Text', 'dfx-parish-retreat-letters' ); ?>
										<?php else : ?>
											<span class="dashicons dashicons-paperclip" title="<?php esc_attr_e( 'Message with Files', 'dfx-parish-retreat-letters' ); ?>"></span>
											<?php esc_html_e( 'Files', 'dfx-parish-retreat-letters' ); ?>
											<?php if ( $message->file_count > 0 ) : ?>
												(<?php echo esc_html( $message->file_count ); ?>)
											<?php endif; ?>
										<?php endif; ?>
									</td>
									<td>
										<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $message->submitted_at ) ) ); ?>
									</td>
									<td>
										<?php if ( $message->print_count > 0 ) : ?>
											<span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="<?php esc_attr_e( 'Printed', 'dfx-parish-retreat-letters' ); ?>"></span>
											<a href="#" class="dfx-prl-view-print-log" data-message-id="<?php echo esc_attr( $message->id ); ?>" style="text-decoration: none;">
												<?php
												printf(
													/* translators: %1$d: Print count, %2$s: First print date */
													esc_html( _n(
														'Printed %1$d time, first: %2$s.',
														'Printed %1$d times, first: %2$s.',
														$message->print_count,
														'dfx-parish-retreat-letters'
													) ),
													esc_html( $message->print_count ),
													esc_html( date_i18n(
														get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
														strtotime( $message->first_printed_at )
													) )
												);
												?>
											</a>
											<small style="display: block; margin-top: 2px; color: #666;">
												<?php esc_html_e( 'Click to view print history', 'dfx-parish-retreat-letters' ); ?>
											</small>
										<?php else : ?>
											<span class="dashicons dashicons-warning" style="color: #dba617;" title="<?php esc_attr_e( 'Not Printed', 'dfx-parish-retreat-letters' ); ?>"></span>
											<?php esc_html_e( 'Not printed', 'dfx-parish-retreat-letters' ); ?>
										<?php endif; ?>
									</td>
									<td>
										<button type="button" class="button button-small button-primary dfx-prl-print-message" data-message-id="<?php echo esc_attr( $message->id ); ?>">
											<?php esc_html_e( 'Print', 'dfx-parish-retreat-letters' ); ?>
										</button>

										<?php
										// Check if user can delete messages for this retreat
										$can_delete = false;
										if ( $attendant ) {
											// Message managers and retreat managers can delete messages
											$can_delete = $this->permissions->current_user_can_manage_messages( $attendant->retreat_id );
										} else {
											// If no specific attendant, we need to check the message's attendant retreat
											// For now, allow plugin administrators to delete any message
											$can_delete = $this->permissions->current_user_can_manage_plugin();
										}
										?>

										<?php if ( $can_delete ) : ?>
											<button type="button" class="button button-small button-link-delete dfx-prl-delete-message" data-message-id="<?php echo esc_attr( $message->id ); ?>">
												<?php esc_html_e( 'Delete', 'dfx-parish-retreat-letters' ); ?>
											</button>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="5">
									<?php if ( $search || $retreat_id || $message_type ) : ?>
										<?php esc_html_e( 'No messages found for your search/filter criteria.', 'dfx-parish-retreat-letters' ); ?>
									<?php else : ?>
										<?php esc_html_e( 'No confidential messages have been received yet.', 'dfx-parish-retreat-letters' ); ?>
									<?php endif; ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<?php if ( $total_pages > 1 ) : ?>
					<div class="notice notice-info inline" style="margin: 15px 0; padding: 10px;">
						<p>
							<strong><?php esc_html_e( 'Note:', 'dfx-parish-retreat-letters' ); ?></strong>
							<?php
							printf(
								/* translators: %1$d: current page, %2$d: total pages, %3$d: items per page */
								esc_html__( 'Showing page %1$d of %2$d. There are more messages available. Use the pagination controls above to view all %3$d messages per page.', 'dfx-parish-retreat-letters' ),
								esc_html( $page_num ),
								esc_html( $total_pages ),
								100
							);
							?>
						</p>
					</div>
				<?php endif; ?>
			</form>

			<?php $this->render_plugin_footer(); ?>
		</div>
		<?php
	}

	/**
	 * Handle message list page actions.
	 *
	 * @since 1.2.0
	 */
	private function handle_message_list_actions() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'dfx_prl_messages_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) );
		$message_id = absint( $_POST['message_id'] ?? 0 );

		if ( $action === 'delete' && $message_id ) {
			if ( $this->message_model->delete( $message_id ) ) {
				$this->add_admin_notice( __( 'Message deleted successfully.', 'dfx-parish-retreat-letters' ), 'success' );
			} else {
				$this->add_admin_notice( __( 'Error deleting message.', 'dfx-parish-retreat-letters' ), 'error' );
			}
		}
	}

	/**
	 * AJAX handler for printing messages.
	 *
	 * @since 1.2.0
	 */
	public function ajax_print_message() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$message_id = absint( $_POST['message_id'] ?? 0 );
		if ( ! $message_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid message ID.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Generate a secure token for the print URL
		$print_token = wp_generate_password( 32, false );

		// Store the print token temporarily (valid for 5 minutes)
		set_transient( 'dfx_prl_print_token_' . $print_token, $message_id, 5 * MINUTE_IN_SECONDS );

		// Log the print operation
		$this->print_log_model->log_print( $message_id, get_current_user_id() );

		// Return the print URL - use clean URL instead of admin interface
		$print_url = home_url( '/print/' . $print_token );
		wp_send_json_success( array( 'print_url' => $print_url ) );
	}

	/**
	 * AJAX handler for downloading files.
	 *
	 * @since 1.2.0
	 */
	public function ajax_download_file() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$file_id = absint( $_POST['file_id'] ?? 0 );
		if ( ! $file_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid file ID.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Serve the file (this will exit)
		$this->file_model->serve_file( $file_id, get_current_user_id() );

		// If we get here, file serving failed
		wp_send_json_error( array( 'message' => __( 'File not found or access denied.', 'dfx-parish-retreat-letters' ) ) );
	}

	/**
	 * AJAX handler for deleting messages.
	 *
	 * @since 1.2.0
	 */
	public function ajax_delete_message() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$message_id = absint( $_POST['message_id'] ?? 0 );
		if ( ! $message_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid message ID.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Get message to check permissions
		$message = $this->message_model->get( $message_id );
		if ( ! $message ) {
			wp_send_json_error( array( 'message' => __( 'Message not found.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Check if user has permission to delete messages for this retreat
		if ( ! $this->permissions->current_user_can_manage_messages( $message->retreat_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to delete messages for this retreat.', 'dfx-parish-retreat-letters' ) ) );
		}

		if ( $this->message_model->delete( $message_id ) ) {
			wp_send_json_success( array( 'message' => __( 'Message deleted successfully.', 'dfx-parish-retreat-letters' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Error deleting message.', 'dfx-parish-retreat-letters' ) ) );
		}
	}

	/**
	 * AJAX handler for getting message print log.
	 *
	 * @since 1.2.1
	 */
	public function ajax_get_print_log() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$message_id = absint( $_POST['message_id'] ?? 0 );
		if ( ! $message_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid message ID.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Get print logs for this message
		$print_log_model = new DFX_Parish_Retreat_Letters_PrintLog();
		$print_logs = $print_log_model->get_by_message( $message_id, array( 'per_page' => 100 ) );

		// Format the data for display
		$formatted_logs = array();
		foreach ( $print_logs as $log ) {
			$formatted_logs[] = array(
				'user_name' => $log->display_name ?: $log->user_login ?: __( 'Unknown User', 'dfx-parish-retreat-letters' ),
				'printed_at' => date_i18n(
					get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
					strtotime( $log->printed_at )
				),
				'ip_address' => $log->ip_address,
			);
		}

		wp_send_json_success( array(
			'logs' => $formatted_logs,
			'total_count' => count( $formatted_logs )
		) );
	}

	/**
	 * AJAX handler for searching users.
	 *
	 * @since 1.3.0
	 */
	public function ajax_search_users() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat_id = absint( $_POST['retreat_id'] ?? 0 );
		if ( ! $retreat_id || ! $this->permissions->user_can_delegate_permissions( get_current_user_id(), $retreat_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'dfx-parish-retreat-letters' ) ) );
		}

		$search_term = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		if ( strlen( $search_term ) < 2 ) {
			wp_send_json_error( array( 'message' => __( 'Search term must be at least 2 characters.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Search users by username, email, or display name
		$users = get_users( array(
			'search'         => '*' . esc_attr( $search_term ) . '*',
			'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
			'number'         => 10,
			'fields'         => array( 'ID', 'user_login', 'user_email', 'display_name' ),
		) );

		$results = array();
		foreach ( $users as $user ) {
			// Skip users who already have permissions for this retreat
			if ( $this->permissions->user_has_retreat_permission( $user->ID, $retreat_id, 'manager' ) ||
				 $this->permissions->user_has_retreat_permission( $user->ID, $retreat_id, 'message_manager' ) ) {
				continue;
			}

			$results[] = array(
				'id'           => $user->ID,
				'username'     => $user->user_login,
				'email'        => $user->user_email,
				'display_name' => $user->display_name ?: $user->user_login,
			);
		}

		wp_send_json_success( array( 'users' => $results ) );
	}

	/**
	 * AJAX handler for granting permissions.
	 *
	 * @since 1.3.0
	 */
	public function ajax_grant_permission() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat_id = absint( $_POST['retreat_id'] ?? 0 );
		$user_id = absint( $_POST['user_id'] ?? 0 );
		$permission_level = sanitize_text_field( wp_unslash( $_POST['permission_level'] ?? '' ) );

		if ( ! $retreat_id || ! $user_id || ! in_array( $permission_level, array( 'manager', 'message_manager' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'dfx-parish-retreat-letters' ) ) );
		}

		if ( ! $this->permissions->user_can_delegate_permissions( get_current_user_id(), $retreat_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'dfx-parish-retreat-letters' ) ) );
		}

		$result = $this->permissions->grant_permission( $user_id, $retreat_id, $permission_level, get_current_user_id() );

		if ( $result ) {
			$user = get_user_by( 'id', $user_id );
			$permission_name = $permission_level === 'manager'
				? __( 'Retreat Manager', 'dfx-parish-retreat-letters' )
				: __( 'Message Manager', 'dfx-parish-retreat-letters' );

			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %1$s: user's display name, %2$s: permission level name */
					__( 'Permission granted to %1$s as %2$s.', 'dfx-parish-retreat-letters' ),
					$user->display_name,
					$permission_name
				),
				'user' => array(
					'id'           => $user->ID,
					'display_name' => $user->display_name,
					'email'        => $user->user_email,
					'permission'   => $permission_level,
				),
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to grant permission.', 'dfx-parish-retreat-letters' ) ) );
		}
	}

	/**
	 * AJAX handler for revoking permissions.
	 *
	 * @since 1.3.0
	 */
	public function ajax_revoke_permission() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat_id = absint( $_POST['retreat_id'] ?? 0 );
		$user_id = absint( $_POST['user_id'] ?? 0 );
		$permission_level = sanitize_text_field( wp_unslash( $_POST['permission_level'] ?? '' ) );

		if ( ! $retreat_id || ! $user_id || ! in_array( $permission_level, array( 'manager', 'message_manager' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'dfx-parish-retreat-letters' ) ) );
		}

		if ( ! $this->permissions->user_can_delegate_permissions( get_current_user_id(), $retreat_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'dfx-parish-retreat-letters' ) ) );
		}

		// Prevent users from revoking their own permissions
		if ( $user_id === get_current_user_id() ) {
			wp_send_json_error( array( 'message' => __( 'You cannot revoke your own permissions.', 'dfx-parish-retreat-letters' ) ) );
		}

		$result = $this->permissions->revoke_permission( $user_id, $retreat_id, $permission_level, get_current_user_id() );

		if ( $result ) {
			$user = get_user_by( 'id', $user_id );
			wp_send_json_success( array(
				'message' => sprintf(
					/* translators: %s: user's display name */
					__( 'Permission revoked from %s.', 'dfx-parish-retreat-letters' ),
					$user->display_name
				),
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to revoke permission.', 'dfx-parish-retreat-letters' ) ) );
		}
	}

	/**
	 * AJAX handler for sending invitations.
	 *
	 * @since 1.3.0
	 */
	public function ajax_send_invitation() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat_id = absint( $_POST['retreat_id'] ?? 0 );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$permission_level = sanitize_text_field( wp_unslash( $_POST['permission_level'] ?? '' ) );

		if ( ! $retreat_id || ! $email || ! $name || ! in_array( $permission_level, array( 'manager', 'message_manager' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'dfx-parish-retreat-letters' ) ) );
		}

		if ( ! $this->permissions->user_can_delegate_permissions( get_current_user_id(), $retreat_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'dfx-parish-retreat-letters' ) ) );
		}

		$invitations = DFX_Parish_Retreat_Letters_Invitations::get_instance();
		$result = $invitations->send_invitation( $retreat_id, $email, $name, $permission_level, get_current_user_id() );

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}
	}

	/**
	 * AJAX handler for cancelling invitations.
	 *
	 * @since 1.3.0
	 */
	public function ajax_cancel_invitation() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$invitation_id = absint( $_POST['invitation_id'] ?? 0 );
		$retreat_id = absint( $_POST['retreat_id'] ?? 0 );

		if ( ! $invitation_id || ! $retreat_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters.', 'dfx-parish-retreat-letters' ) ) );
		}

		if ( ! $this->permissions->user_can_delegate_permissions( get_current_user_id(), $retreat_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'dfx-parish-retreat-letters' ) ) );
		}

		$invitations = DFX_Parish_Retreat_Letters_Invitations::get_instance();
		$result = $invitations->cancel_invitation( $invitation_id, get_current_user_id() );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Invitation cancelled successfully.', 'dfx-parish-retreat-letters' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to cancel invitation.', 'dfx-parish-retreat-letters' ) ) );
		}
	}

	/**
	 * Display the privacy and compliance page.
	 *
	 * @since 1.2.0
	 */
	public function privacy_compliance_page() {
		// Handle form submissions
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			// Form submission already handled in admin_init, just return to prevent double processing
			return;
		}

		$compliance_status = $this->gdpr->get_privacy_compliance_status();
		$retention_settings = $this->gdpr->get_retention_settings();
		$recent_audit_logs = $this->gdpr->get_audit_logs( array( 'limit' => 10 ) );

		$this->render_privacy_compliance_page( $compliance_status, $retention_settings, $recent_audit_logs );
	}

	/**
	 * Handle privacy compliance page actions.
	 *
	 * @since 1.2.0
	 */
	private function handle_privacy_compliance_actions() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'dfx_prl_privacy_action' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) );

		switch ( $action ) {
			case 'update_retention':
				$settings = array(
					'message_retention_days' => absint( $_POST['message_retention_days'] ?? 365 ),
					'audit_log_retention_days' => absint( $_POST['audit_log_retention_days'] ?? 730 ),
				);

				if ( $this->gdpr->update_retention_settings( $settings ) ) {
					$this->add_admin_notice( __( 'Retention settings updated successfully.', 'dfx-parish-retreat-letters' ), 'success' );
				} else {
					$this->add_admin_notice( __( 'Error updating retention settings. Please check your values.', 'dfx-parish-retreat-letters' ), 'error' );
				}
				break;

			case 'run_cleanup':
				$this->gdpr->run_daily_cleanup();
				$this->add_admin_notice( __( 'Privacy cleanup completed successfully.', 'dfx-parish-retreat-letters' ), 'success' );
				break;
		}
	}

	/**
	 * Render the privacy compliance page.
	 *
	 * @since 1.2.0
	 * @param array $compliance_status Privacy compliance status.
	 * @param array $retention_settings Data retention settings.
	 * @param array $recent_audit_logs Recent audit log entries.
	 */
	private function render_privacy_compliance_page( $compliance_status, $retention_settings, $recent_audit_logs ) {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Privacy & Compliance', 'dfx-parish-retreat-letters' ); ?></h1>
			<hr class="wp-header-end">

			<?php $this->display_admin_notices(); ?>

			<!-- Compliance Status Overview -->
			<div class="dfx-prl-compliance-overview">
				<h2><?php esc_html_e( 'Compliance Status Overview', 'dfx-parish-retreat-letters' ); ?></h2>
				<div class="dfx-prl-status-grid">
					<div class="dfx-prl-status-item <?php echo $compliance_status['encryption_enabled'] ? 'status-good' : 'status-warning'; ?>">
						<span class="dashicons <?php echo $compliance_status['encryption_enabled'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
						<strong><?php esc_html_e( 'Encryption', 'dfx-parish-retreat-letters' ); ?></strong>
						<p><?php echo $compliance_status['encryption_enabled'] ? esc_html__( 'AES-256 encryption active', 'dfx-parish-retreat-letters' ) : esc_html__( 'Encryption requirements not met', 'dfx-parish-retreat-letters' ); ?></p>
					</div>

					<div class="dfx-prl-status-item <?php echo $compliance_status['ip_anonymization_active'] ? 'status-good' : 'status-warning'; ?>">
						<span class="dashicons <?php echo $compliance_status['ip_anonymization_active'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
						<strong><?php esc_html_e( 'IP Anonymization', 'dfx-parish-retreat-letters' ); ?></strong>
						<p><?php esc_html_e( 'IPs anonymized after 30 days', 'dfx-parish-retreat-letters' ); ?></p>
					</div>

					<div class="dfx-prl-status-item <?php echo $compliance_status['retention_policy_configured'] ? 'status-good' : 'status-warning'; ?>">
						<span class="dashicons <?php echo $compliance_status['retention_policy_configured'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
						<strong><?php esc_html_e( 'Data Retention', 'dfx-parish-retreat-letters' ); ?></strong>
						<p><?php echo $compliance_status['retention_policy_configured'] ? esc_html__( 'Policy configured', 'dfx-parish-retreat-letters' ) : esc_html__( 'Policy needs configuration', 'dfx-parish-retreat-letters' ); ?></p>
					</div>

					<div class="dfx-prl-status-item <?php echo $compliance_status['audit_logging_active'] ? 'status-good' : 'status-warning'; ?>">
						<span class="dashicons <?php echo $compliance_status['audit_logging_active'] ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
						<strong><?php esc_html_e( 'Audit Logging', 'dfx-parish-retreat-letters' ); ?></strong>
						<p><?php esc_html_e( 'All actions are logged', 'dfx-parish-retreat-letters' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Data Statistics -->
			<div class="dfx-prl-data-stats">
				<h2><?php esc_html_e( 'Data Statistics', 'dfx-parish-retreat-letters' ); ?></h2>
				<div class="dfx-prl-stats-grid">
					<div class="dfx-prl-stat-item">
						<span class="dfx-prl-stat-number"><?php echo esc_html( $compliance_status['messages_count'] ); ?></span>
						<span class="dfx-prl-stat-label"><?php esc_html_e( 'Confidential Messages', 'dfx-parish-retreat-letters' ); ?></span>
					</div>
					<div class="dfx-prl-stat-item">
						<span class="dfx-prl-stat-number"><?php echo esc_html( $compliance_status['files_count'] ); ?></span>
						<span class="dfx-prl-stat-label"><?php esc_html_e( 'Encrypted Files', 'dfx-parish-retreat-letters' ); ?></span>
					</div>
					<div class="dfx-prl-stat-item">
						<span class="dfx-prl-stat-number"><?php echo esc_html( $compliance_status['audit_logs_count'] ); ?></span>
						<span class="dfx-prl-stat-label"><?php esc_html_e( 'Audit Log Entries', 'dfx-parish-retreat-letters' ); ?></span>
					</div>
					<div class="dfx-prl-stat-item">
						<span class="dfx-prl-stat-number"><?php echo $compliance_status['last_cleanup'] ? esc_html( human_time_diff( $compliance_status['last_cleanup'] ) . ' ago' ) : esc_html__( 'Never', 'dfx-parish-retreat-letters' ); ?></span>
						<span class="dfx-prl-stat-label"><?php esc_html_e( 'Last Cleanup', 'dfx-parish-retreat-letters' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Data Retention Settings -->
			<form method="post" action="">
				<?php wp_nonce_field( 'dfx_prl_privacy_action' ); ?>
				<input type="hidden" name="action" value="update_retention">

				<h2><?php esc_html_e( 'Data Retention Policy', 'dfx-parish-retreat-letters' ); ?></h2>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="message_retention_days"><?php esc_html_e( 'Message Retention Period', 'dfx-parish-retreat-letters' ); ?></label>
							</th>
							<td>
								<input type="number" id="message_retention_days" name="message_retention_days" value="<?php echo esc_attr( $retention_settings['message_retention_days'] ); ?>" min="30" max="3650" required>
								<p class="description"><?php esc_html_e( 'Number of days to keep confidential messages (30-3650 days). Set to 0 to keep indefinitely.', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="audit_log_retention_days"><?php esc_html_e( 'Audit Log Retention Period', 'dfx-parish-retreat-letters' ); ?></label>
							</th>
							<td>
								<input type="number" id="audit_log_retention_days" name="audit_log_retention_days" value="<?php echo esc_attr( $retention_settings['audit_log_retention_days'] ); ?>" min="365" max="3650" required>
								<p class="description"><?php esc_html_e( 'Number of days to keep audit logs (365-3650 days).', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'IP Address Anonymization', 'dfx-parish-retreat-letters' ); ?>
							</th>
							<td>
								<p><strong><?php esc_html_e( '30 days (Fixed for GDPR compliance)', 'dfx-parish-retreat-letters' ); ?></strong></p>
								<p class="description"><?php esc_html_e( 'IP addresses are automatically anonymized after 30 days to comply with GDPR requirements.', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Update Retention Settings', 'dfx-parish-retreat-letters' ); ?>">
				</p>
			</form>

			<!-- Privacy Tools -->
			<h2><?php esc_html_e( 'Privacy Tools', 'dfx-parish-retreat-letters' ); ?></h2>
			<div class="dfx-prl-privacy-tools">
				<div class="dfx-prl-tool-section">
					<h3><?php esc_html_e( 'Manual Cleanup', 'dfx-parish-retreat-letters' ); ?></h3>
					<p><?php esc_html_e( 'Run manual privacy cleanup to anonymize old IP addresses and clean up expired data.', 'dfx-parish-retreat-letters' ); ?></p>
					<form method="post" action="" style="display: inline;">
						<?php wp_nonce_field( 'dfx_prl_privacy_action' ); ?>
						<input type="hidden" name="action" value="run_cleanup">
						<input type="submit" class="button" value="<?php esc_attr_e( 'Run Cleanup Now', 'dfx-parish-retreat-letters' ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to run the privacy cleanup?', 'dfx-parish-retreat-letters' ); ?>');">
					</form>
				</div>

				<div class="dfx-prl-tool-section">
					<h3><?php esc_html_e( 'Rate Limit Management', 'dfx-parish-retreat-letters' ); ?></h3>
					<p><?php esc_html_e( 'Reset message submission rate limits to allow users to submit messages again after encountering errors.', 'dfx-parish-retreat-letters' ); ?></p>
					<button type="button" id="reset-rate-limits-btn" class="button"><?php esc_html_e( 'Reset All Rate Limits', 'dfx-parish-retreat-letters' ); ?></button>
				</div>

				<div class="dfx-prl-tool-section">
					<h3><?php esc_html_e( 'Personal Data Export/Erasure', 'dfx-parish-retreat-letters' ); ?></h3>
					<p><?php esc_html_e( 'Export or erase personal data by sender name for GDPR compliance.', 'dfx-parish-retreat-letters' ); ?></p>

					<div class="dfx-prl-gdpr-tools">
						<div class="dfx-prl-gdpr-export">
							<h4><?php esc_html_e( 'Export Personal Data', 'dfx-parish-retreat-letters' ); ?></h4>
							<input type="text" id="export-identifier" placeholder="<?php esc_attr_e( 'Enter sender name or email', 'dfx-parish-retreat-letters' ); ?>">
							<button type="button" id="export-data-btn" class="button"><?php esc_html_e( 'Export Data', 'dfx-parish-retreat-letters' ); ?></button>
						</div>

						<div class="dfx-prl-gdpr-erase">
							<h4><?php esc_html_e( 'Erase Personal Data', 'dfx-parish-retreat-letters' ); ?></h4>
							<input type="text" id="erase-identifier" placeholder="<?php esc_attr_e( 'Enter sender name or email', 'dfx-parish-retreat-letters' ); ?>">
							<input type="text" id="erase-confirm" placeholder="<?php esc_attr_e( 'Type ERASE to confirm', 'dfx-parish-retreat-letters' ); ?>">
							<button type="button" id="erase-data-btn" class="button button-link-delete"><?php esc_html_e( 'Erase Data', 'dfx-parish-retreat-letters' ); ?></button>
						</div>
					</div>
				</div>
			</div>

			<!-- Recent Audit Logs -->
			<h2><?php esc_html_e( 'Recent Audit Activity', 'dfx-parish-retreat-letters' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Date/Time', 'dfx-parish-retreat-letters' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Event Type', 'dfx-parish-retreat-letters' ); ?></th>
						<th scope="col"><?php esc_html_e( 'User', 'dfx-parish-retreat-letters' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Details', 'dfx-parish-retreat-letters' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $recent_audit_logs ) ) : ?>
						<?php foreach ( $recent_audit_logs as $log ) : ?>
							<tr>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log['timestamp'] ) ); ?></td>
								<td><?php echo esc_html( ucwords( str_replace( '_', ' ', (string) ( $log['event_type'] ?? '' ) ) ) ); ?></td>
								<td>
									<?php
									if ( $log['user_id'] ) {
										$user = get_user_by( 'id', $log['user_id'] );
										echo esc_html( $user ? $user->display_name : __( 'Unknown User', 'dfx-parish-retreat-letters' ) );
									} else {
										esc_html_e( 'System', 'dfx-parish-retreat-letters' );
									}
									?>
								</td>
								<td>
									<?php
									if ( ! empty( $log['data'] ) ) {
										echo esc_html( wp_json_encode( $log['data'] ) );
									} else {
										echo '<em>' . esc_html__( 'No additional details', 'dfx-parish-retreat-letters' ) . '</em>';
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="4"><?php esc_html_e( 'No audit logs found.', 'dfx-parish-retreat-letters' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<?php $this->render_plugin_footer(); ?>
		</div>

		<style>
		.dfx-prl-status-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
			gap: 20px;
			margin-bottom: 30px;
		}

		.dfx-prl-status-item {
			padding: 20px;
			border: 1px solid #ddd;
			border-radius: 8px;
			background: #fff;
			text-align: center;
		}

		.dfx-prl-status-item.status-good {
			border-color: #46b450;
			background: #f7fcf7;
		}

		.dfx-prl-status-item.status-warning {
			border-color: #ffb900;
			background: #fffbf0;
		}

		.dfx-prl-status-item .dashicons {
			font-size: 32px;
			width: 32px;
			height: 32px;
			margin-bottom: 10px;
		}

		.dfx-prl-status-item.status-good .dashicons {
			color: #46b450;
		}

		.dfx-prl-status-item.status-warning .dashicons {
			color: #ffb900;
		}

		.dfx-prl-stats-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
			gap: 20px;
			margin-bottom: 30px;
		}

		.dfx-prl-stat-item {
			text-align: center;
			padding: 20px;
			border: 1px solid #ddd;
			border-radius: 8px;
			background: #fff;
		}

		.dfx-prl-stat-number {
			display: block;
			font-size: 2em;
			font-weight: bold;
			color: #007cba;
			margin-bottom: 5px;
		}

		.dfx-prl-stat-label {
			font-size: 0.9em;
			color: #666;
		}

		.dfx-prl-privacy-tools {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 30px;
			margin-bottom: 30px;
		}

		.dfx-prl-tool-section {
			padding: 20px;
			border: 1px solid #ddd;
			border-radius: 8px;
			background: #fff;
		}

		.dfx-prl-tool-section h3 {
			margin-top: 0;
			color: #333;
		}

		.dfx-prl-gdpr-tools {
			margin-top: 15px;
		}

		.dfx-prl-gdpr-export, .dfx-prl-gdpr-erase {
			margin-bottom: 20px;
		}

		.dfx-prl-gdpr-export h4, .dfx-prl-gdpr-erase h4 {
			margin-bottom: 10px;
			color: #555;
		}

		.dfx-prl-gdpr-export input, .dfx-prl-gdpr-erase input {
			width: 200px;
			margin-right: 10px;
			margin-bottom: 5px;
		}

		@media (max-width: 768px) {
			.dfx-prl-status-grid, .dfx-prl-stats-grid {
				grid-template-columns: 1fr;
			}

			.dfx-prl-privacy-tools {
				grid-template-columns: 1fr;
			}

			.dfx-prl-gdpr-export input, .dfx-prl-gdpr-erase input {
				width: 100%;
				margin-bottom: 10px;
			}
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			$('#export-data-btn').on('click', function() {
				var identifier = $('#export-identifier').val().trim();
				if (!identifier) {
					alert('<?php esc_html_e( 'Please enter a sender name or email.', 'dfx-parish-retreat-letters' ); ?>');
					return;
				}

				var form = $('<form>', {
					method: 'POST',
					action: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>'
				});

				form.append($('<input>', { type: 'hidden', name: 'action', value: 'dfx_prl_export_personal_data' }));
				form.append($('<input>', { type: 'hidden', name: 'identifier', value: identifier }));
				form.append($('<input>', { type: 'hidden', name: 'nonce', value: '<?php echo esc_attr( wp_create_nonce( 'dfx_prl_gdpr_nonce' ) ); ?>' }));

				$('body').append(form);
				form.submit();
			});

			$('#erase-data-btn').on('click', function() {
				var identifier = $('#erase-identifier').val().trim();
				var confirm = $('#erase-confirm').val().trim();

				if (!identifier) {
					alert('<?php esc_html_e( 'Please enter a sender name or email.', 'dfx-parish-retreat-letters' ); ?>');
					return;
				}

				if (confirm !== 'ERASE') {
					alert('<?php esc_html_e( 'Please type "ERASE" to confirm data deletion.', 'dfx-parish-retreat-letters' ); ?>');
					return;
				}

				if (!window.confirm('<?php esc_html_e( 'Are you sure you want to permanently erase all data for this identifier? This action cannot be undone.', 'dfx-parish-retreat-letters' ); ?>')) {
					return;
				}

				$.ajax({
					url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
					type: 'POST',
					data: {
						action: 'dfx_prl_erase_personal_data',
						identifier: identifier,
						confirm: confirm,
						nonce: '<?php echo esc_attr( wp_create_nonce( 'dfx_prl_gdpr_nonce' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
							$('#erase-identifier, #erase-confirm').val('');
							location.reload();
						} else {
							alert(response.data.message || '<?php esc_html_e( 'An error occurred during data erasure.', 'dfx-parish-retreat-letters' ); ?>');
						}
					},
					error: function() {
						alert('<?php esc_html_e( 'A network error occurred. Please try again.', 'dfx-parish-retreat-letters' ); ?>');
					}
				});
			});

			// Rate limit reset functionality
			$('#reset-rate-limits-btn').click(function() {
				if (!confirm('<?php esc_html_e( 'Are you sure you want to reset all rate limits? This will allow all IP addresses to submit messages again.', 'dfx-parish-retreat-letters' ); ?>')) {
					return;
				}

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'dfx_prl_reset_rate_limits',
						nonce: '<?php echo esc_attr( wp_create_nonce( 'dfx_prl_retreats_nonce' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							alert(response.data.message);
						} else {
							alert(response.data.message || '<?php esc_html_e( 'An error occurred while resetting rate limits.', 'dfx-parish-retreat-letters' ); ?>');
						}
					},
					error: function() {
						alert('<?php esc_html_e( 'A network error occurred. Please try again.', 'dfx-parish-retreat-letters' ); ?>');
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX handler for resetting rate limits.
	 *
	 * @since 1.2.0
	 */
	public function ajax_reset_rate_limits() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_retreats_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		// Only plugin administrators can reset rate limits
		if ( ! $this->permissions->current_user_can_manage_plugin() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to reset rate limits.', 'dfx-parish-retreat-letters' ) ) );
		}

		$count = $this->security->reset_all_rate_limits();

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: number of rate limits that were reset */
				__( 'Successfully reset %d rate limit(s).', 'dfx-parish-retreat-letters' ),
				absint( $count )
			)
		) );
	}

	/**
	 * Display the responsible persons management page.
	 *
	 * @since 1.7.0
	 * @param int $retreat_id Retreat ID.
	 */
	private function responsible_persons_page( $retreat_id ) {
		// Check permissions
		if ( ! $this->permissions->current_user_can_manage_retreat( $retreat_id ) ) {
			wp_die( esc_html__( 'You do not have permission to manage this retreat.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat = $this->retreat_model->get( $retreat_id );
		if ( ! $retreat ) {
			wp_die( esc_html__( 'Retreat not found.', 'dfx-parish-retreat-letters' ) );
		}

		$responsible_persons = $this->responsible_person_model->get_by_retreat( $retreat_id );
		$this->render_responsible_persons_page( $retreat, $responsible_persons );
	}

	/**
	 * Render the responsible persons management page.
	 *
	 * @since 1.7.0
	 * @param object $retreat Retreat object.
	 * @param array  $responsible_persons List of responsible persons.
	 */
	private function render_responsible_persons_page( $retreat, $responsible_persons ) {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Manage Responsible Persons', 'dfx-parish-retreat-letters' ); ?></h1>
			<hr class="wp-header-end">

			<!-- Breadcrumb -->
			<p class="description">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats' ) ); ?>"><?php esc_html_e( 'Retreats', 'dfx-parish-retreat-letters' ); ?></a>
				&gt; <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>"><?php echo esc_html( $retreat->name ); ?></a>
				&gt; <?php esc_html_e( 'Responsible Persons', 'dfx-parish-retreat-letters' ); ?>
			</p>

			<?php $this->display_admin_notices(); ?>

			<div class="card">
				<h2><?php esc_html_e( 'Add Responsible Person', 'dfx-parish-retreat-letters' ); ?></h2>
				<form id="dfx-prl-add-responsible-person-form">
					<?php wp_nonce_field( 'dfx_prl_add_responsible_person', 'dfx_prl_responsible_person_nonce' ); ?>
					<input type="hidden" name="retreat_id" value="<?php echo esc_attr( $retreat->id ); ?>">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row">
									<label for="responsible_person_name"><?php esc_html_e( 'Name', 'dfx-parish-retreat-letters' ); ?></label>
								</th>
								<td>
									<input type="text" id="responsible_person_name" name="name" class="regular-text" required>
									<p class="description"><?php esc_html_e( 'Enter the name of the person responsible for attendants.', 'dfx-parish-retreat-letters' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Person', 'dfx-parish-retreat-letters' ); ?></button>
					</p>
				</form>
			</div>

			<h2><?php esc_html_e( 'Current Responsible Persons', 'dfx-parish-retreat-letters' ); ?></h2>

			<?php if ( empty( $responsible_persons ) ) : ?>
				<p><?php esc_html_e( 'No responsible persons have been added yet. Add one above to get started.', 'dfx-parish-retreat-letters' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'dfx-parish-retreat-letters' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'dfx-parish-retreat-letters' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $responsible_persons as $person ) : ?>
							<tr data-person-id="<?php echo esc_attr( $person->id ); ?>">
								<td><?php echo esc_html( $person->name ); ?></td>
								<td>
									<button type="button" class="button button-small dfx-prl-delete-responsible-person" data-person-id="<?php echo esc_attr( $person->id ); ?>" data-retreat-id="<?php echo esc_attr( $retreat->id ); ?>">
										<?php esc_html_e( 'Delete', 'dfx-parish-retreat-letters' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-prl-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>" class="button">
					<?php esc_html_e( 'Back to Attendants', 'dfx-parish-retreat-letters' ); ?>
				</a>
			</p>

			<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Handle add responsible person form submission
				$('#dfx-prl-add-responsible-person-form').on('submit', function(e) {
					e.preventDefault();
					
					var formData = $(this).serialize();
					formData += '&action=dfx_prl_add_responsible_person';
					
					$.post(ajaxurl, formData, function(response) {
						if (response.success) {
							location.reload();
						} else {
							alert(response.data.message || '<?php esc_html_e( 'Error adding responsible person.', 'dfx-parish-retreat-letters' ); ?>');
						}
					});
				});

				// Handle delete responsible person
				$('.dfx-prl-delete-responsible-person').on('click', function() {
					if (!confirm('<?php esc_html_e( 'Are you sure you want to delete this responsible person? This will not delete attendants, but will unassign this person from all attendants.', 'dfx-parish-retreat-letters' ); ?>')) {
						return;
					}
					
					var personId = $(this).data('person-id');
					var retreatId = $(this).data('retreat-id');
					var $row = $(this).closest('tr');
					
					$.post(ajaxurl, {
						action: 'dfx_prl_delete_responsible_person',
						person_id: personId,
						retreat_id: retreatId,
						nonce: '<?php echo esc_js( wp_create_nonce( 'dfx_prl_delete_responsible_person' ) ); ?>'
					}, function(response) {
						if (response.success) {
							$row.fadeOut(function() {
								$(this).remove();
								
								// Check if table is empty
								if ($('table tbody tr').length === 0) {
									location.reload();
								}
							});
						} else {
							alert(response.data.message || '<?php esc_html_e( 'Error deleting responsible person.', 'dfx-parish-retreat-letters' ); ?>');
						}
					});
				});
			});
			</script>
		</div>
		<?php
	}

	/**
	 * AJAX handler for adding a responsible person.
	 *
	 * @since 1.7.0
	 */
	public function ajax_add_responsible_person() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dfx_prl_responsible_person_nonce'] ?? '' ) ), 'dfx_prl_add_responsible_person' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'dfx-parish-retreat-letters' ) ) );
		}

		$retreat_id = absint( $_POST['retreat_id'] ?? 0 );

		// Check permissions
		if ( ! $this->permissions->current_user_can_manage_retreat( $retreat_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to manage this retreat.', 'dfx-parish-retreat-letters' ) ) );
		}

		$result = $this->responsible_person_model->create( array(
			'retreat_id' => $retreat_id,
			'name'       => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
		) );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Responsible person added successfully.', 'dfx-parish-retreat-letters' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Error adding responsible person.', 'dfx-parish-retreat-letters' ) ) );
		}
	}

	/**
	 * AJAX handler for deleting a responsible person.
	 *
	 * @since 1.7.0
	 */
	public function ajax_delete_responsible_person() {
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'dfx_prl_delete_responsible_person' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'dfx-parish-retreat-letters' ) ) );
		}

		$person_id = absint( $_POST['person_id'] ?? 0 );
		$retreat_id = absint( $_POST['retreat_id'] ?? 0 );

		// Check permissions
		if ( ! $this->permissions->current_user_can_manage_retreat( $retreat_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to manage this retreat.', 'dfx-parish-retreat-letters' ) ) );
		}

		$result = $this->responsible_person_model->delete( $person_id );

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Responsible person deleted successfully.', 'dfx-parish-retreat-letters' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Error deleting responsible person.', 'dfx-parish-retreat-letters' ) ) );
		}
	}

	/**
	 * Parse block selection from form input.
	 * Handles both new prefixed format and legacy numeric format.
	 *
	 * @since 1.5.1
	 * @param string $selection The block selection value from the form.
	 * @return string|null The formatted block identifier or null if empty.
	 */
	private function parse_block_selection( $selection ) {
		if ( empty( $selection ) ) {
			return null;
		}

		// If it starts with a prefix, return as-is
		if ( strpos( $selection, 'block_' ) === 0 ||
			 strpos( $selection, 'templatepart_' ) === 0 ||
			 strpos( $selection, 'pattern_' ) === 0 ||
			 strpos( $selection, 'registered_' ) === 0 ) {
			return sanitize_text_field( $selection );
		}

		// Legacy numeric format - convert to block_ prefix for backward compatibility
		if ( is_numeric( $selection ) ) {
			return 'block_' . absint( $selection );
		}

		return null;
	}

	/**
	 * Get the actual block/pattern ID from a stored selection.
	 *
	 * @since 1.5.1
	 * @param string|null $selection The stored block selection.
	 * @return int|string|null The actual ID or pattern name.
	 */
	private function get_block_id_from_selection( $selection ) {
		if ( empty( $selection ) ) {
			return null;
		}

		// Handle prefixed format
		if ( strpos( $selection, 'block_' ) === 0 ) {
			return absint( str_replace( 'block_', '', $selection ) );
		}

		if ( strpos( $selection, 'templatepart_' ) === 0 ) {
			return absint( str_replace( 'templatepart_', '', $selection ) );
		}

		if ( strpos( $selection, 'pattern_' ) === 0 ) {
			return absint( str_replace( 'pattern_', '', $selection ) );
		}

		if ( strpos( $selection, 'registered_' ) === 0 ) {
			return str_replace( 'registered_', '', $selection );
		}

		// Legacy numeric format
		if ( is_numeric( $selection ) ) {
			return absint( $selection );
		}

		return $selection;
	}

	/**
	 * Get available reusable blocks, patterns, and template parts for header/footer selection.
	 *
	 * @since 1.5.0
	 * @return array Array of reusable blocks, patterns, and template parts with id and title.
	 */
	private function get_available_blocks() {
		$block_options = array();

		// Get reusable blocks (wp_block post type)
		$blocks = get_posts( array(
			'post_type'      => 'wp_block',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );

		foreach ( $blocks as $block ) {
			$reusable_block = __( 'Reusable Block', 'dfx-parish-retreat-letters' );
			$block_options[ 'block_' . $block->ID ] = $block->post_title . ' [' . $reusable_block . ']';
		}

		// Get template parts (wp_template_part post type)
		if ( post_type_exists( 'wp_template_part' ) ) {
			$template_parts = get_posts( array(
				'post_type'      => 'wp_template_part',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			) );

			foreach ( $template_parts as $template_part ) {
				// Get template part area for better identification
				$area = get_post_meta( $template_part->ID, 'area', true );
				$area_label = '';
				if ( $area ) {
					$area_label = ' (' . ucfirst( $area ) . ')';
				}

				$template_part_string = __( 'Template Part', 'dfx-parish-retreat-letters' );
				$block_options[ 'templatepart_' . $template_part->ID ] = $template_part->post_title . $area_label . ' [' . $template_part_string . ']';
			}
		}

		// Get block patterns (wp_block_pattern post type, if it exists)
		// This post type was introduced in WordPress 6.0+ for user-created patterns
		if ( post_type_exists( 'wp_block_pattern' ) ) {
			$patterns = get_posts( array(
				'post_type'      => 'wp_block_pattern',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			) );

			foreach ( $patterns as $pattern ) {
				$block_pattern = __( 'Block Pattern', 'dfx-parish-retreat-letters' );
				$block_options[ 'pattern_' . $pattern->ID ] = $pattern->post_title  . ' [' . $block_pattern . ']';
			}
		}

		// Try to get registered block patterns if the functions exist
		if ( function_exists( 'WP_Block_Patterns_Registry' ) ) {
			$pattern_registry = WP_Block_Patterns_Registry::get_instance();
			if ( method_exists( $pattern_registry, 'get_all_registered' ) ) {
				$registered_patterns = $pattern_registry->get_all_registered();

				foreach ( $registered_patterns as $pattern_name => $pattern_data ) {
					// Focus on header and footer patterns
					if ( isset( $pattern_data['categories'] ) &&
						 ( in_array( 'header', $pattern_data['categories'] ) ||
						   in_array( 'footer', $pattern_data['categories'] ) ) ) {
						$title = isset( $pattern_data['title'] ) ? $pattern_data['title'] : $pattern_name;
						$registered_pattern = __( 'Registered Pattern', 'dfx-parish-retreat-letters' );
						$block_options[ 'registered_' . $pattern_name ] = $title . ' [' . $registered_pattern . ']';
					}
				}
			}
		}

		return $block_options;
	}

	/**
	 * Render a block selection dropdown.
	 *
	 * @since 1.5.0
	 * @param string $name Field name.
	 * @param string|int|null $selected_value Currently selected block ID or selection string.
	 * @param string $default_text Default option text.
	 */
	private function render_block_selector( $name, $selected_value = null, $default_text = '' ) {
		$blocks = $this->get_available_blocks();

		// Convert legacy numeric values to new format for comparison
		$normalized_selected = null;
		if ( ! empty( $selected_value ) ) {
			if ( is_numeric( $selected_value ) ) {
				// Legacy format - convert to new prefixed format
				$normalized_selected = 'block_' . absint( $selected_value );
			} else {
				$normalized_selected = $selected_value;
			}
		}

		echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" class="regular-text">';
		echo '<option value="">' . esc_html( $default_text ) . '</option>';

		if ( empty( $blocks ) ) {
			echo '<option value="" disabled>' . esc_html__( 'No reusable blocks or patterns found - create one first', 'dfx-parish-retreat-letters' ) . '</option>';
		} else {
			foreach ( $blocks as $block_id => $block_title ) {
				$selected = selected( $normalized_selected, $block_id, false );
				echo '<option value="' . esc_attr( $block_id ) . '"' . esc_attr( $selected ) . '>';
				echo esc_html( $block_title );
				echo '</option>';
			}
		}

		echo '</select>';

		// Add helpful information about how to create reusable blocks and patterns
		if ( empty( $blocks ) ) {
			echo '<br><small class="description">';
			echo wp_kses_post( 
				sprintf( 
					/* translators: %s: URL to WordPress documentation about reusable blocks */
					__( 'To create reusable blocks: Go to <strong>Appearance > Editor > Patterns</strong> or edit any page/post and create a block, then save it as a reusable block. For template parts, use <strong>Appearance > Editor > Patterns > Template Parts</strong>. For block patterns, use the Site Editor. <a href="%s" target="_blank">Learn more</a>', 'dfx-parish-retreat-letters' ),
					'https://wordpress.org/documentation/article/reusable-blocks/'
				)
			);
			echo '</small>';
		}
	}

	/**
	 * Display the global settings page.
	 *
	 * @since 1.6.0
	 */
	public function global_settings_page() {
		// Check permissions - only plugin administrators can access this page
		if ( ! $this->permissions->current_user_can_manage_plugin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'dfx-parish-retreat-letters' ) );
		}

		// Display admin notices
		$this->display_admin_notices();

		// Get current settings
		$default_header = $this->global_settings->get_default_header();
		$default_footer = $this->global_settings->get_default_footer();
		$default_css = $this->global_settings->get_default_css();
		$per_retreat_customization_enabled = $this->global_settings->is_per_retreat_customization_enabled();

		?>
		<div class="wrap">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Global Settings', 'dfx-parish-retreat-letters' ); ?>
			</h1>
			<hr class="wp-header-end">

			<form method="post" action="">
				<?php wp_nonce_field( 'dfx_prl_global_settings_nonce' ); ?>

				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="enable_per_retreat_customization"><?php esc_html_e( 'Per-Retreat Customization', 'dfx-parish-retreat-letters' ); ?></label>
							</th>
							<td>
								<input type="checkbox" id="enable_per_retreat_customization" name="enable_per_retreat_customization" value="1" <?php checked( $per_retreat_customization_enabled ); ?>>
								<label for="enable_per_retreat_customization"><?php esc_html_e( 'Allow individual retreats to customize headers, footers, and CSS', 'dfx-parish-retreat-letters' ); ?></label>
								<p class="description"><?php esc_html_e( 'When disabled, all retreats will use the global default settings defined below.', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>

						<?php if ( post_type_exists( 'wp_block' ) ) : ?>
						<tr>
							<th scope="row">
								<label for="default_header_block_id"><?php esc_html_e( 'Default Header Block', 'dfx-parish-retreat-letters' ); ?></label>
							</th>
							<td>
								<?php $this->render_block_selector( 'default_header_block_id', $default_header, __( 'Use default WordPress header', 'dfx-parish-retreat-letters' ) ); ?>
								<p class="description"><?php esc_html_e( 'Select a reusable block, template part, or pattern to display as the default header in all retreat message form pages.', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="default_footer_block_id"><?php esc_html_e( 'Default Footer Block', 'dfx-parish-retreat-letters' ); ?></label>
							</th>
							<td>
								<?php $this->render_block_selector( 'default_footer_block_id', $default_footer, __( 'Use default WordPress footer', 'dfx-parish-retreat-letters' ) ); ?>
								<p class="description"><?php esc_html_e( 'Select a reusable block, template part, or pattern to display as the default footer in all retreat message form pages.', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>
						<?php endif; ?>

						<tr>
							<th scope="row">
								<label for="default_css"><?php esc_html_e( 'Default CSS Styles', 'dfx-parish-retreat-letters' ); ?></label>
							</th>
							<td>
								<textarea id="default_css" name="default_css" rows="15" cols="80" class="large-text code"><?php echo esc_textarea( $default_css ); ?></textarea>
								<p class="description"><?php esc_html_e( 'CSS styles to be applied to all retreat message form pages. Do not include &lt;style&gt; tags.', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'dfx-parish-retreat-letters' ); ?>">
				</p>
			</form>

			<hr>

			<h2><?php esc_html_e( 'User Management', 'dfx-parish-retreat-letters' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Grant or revoke global retreat management permissions. Users with this permission can manage all retreats and create new ones.', 'dfx-parish-retreat-letters' ); ?></p>

			<?php
			// Get current global retreat managers
			$global_managers = $this->permissions->get_global_retreat_managers();
			$non_admin_users = $this->permissions->get_non_admin_users();
			?>

			<?php if ( ! empty( $global_managers ) ) : ?>
			<h3><?php esc_html_e( 'Current Global Retreat Managers', 'dfx-parish-retreat-letters' ); ?></h3>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'User', 'dfx-parish-retreat-letters' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Email', 'dfx-parish-retreat-letters' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Role', 'dfx-parish-retreat-letters' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'dfx-parish-retreat-letters' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $global_managers as $manager ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $manager->display_name ); ?></strong></td>
						<td><?php echo esc_html( $manager->user_email ); ?></td>
						<td><?php echo esc_html( implode( ', ', $manager->roles ) ); ?></td>
						<td>
							<form method="post" style="display: inline-block;">
								<?php wp_nonce_field( 'dfx_prl_user_management_nonce' ); ?>
								<input type="hidden" name="user_management_action" value="revoke">
								<input type="hidden" name="user_id" value="<?php echo esc_attr( $manager->ID ); ?>">
								<button type="submit" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to revoke global retreat management access from this user?', 'dfx-parish-retreat-letters' ); ?>')">
									<?php esc_html_e( 'Revoke Access', 'dfx-parish-retreat-letters' ); ?>
								</button>
							</form>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<br>
			<?php endif; ?>

			<h3><?php esc_html_e( 'Grant Global Retreat Management Access', 'dfx-parish-retreat-letters' ); ?></h3>

			<?php if ( empty( $non_admin_users ) ) : ?>
				<p><em><?php esc_html_e( 'No non-administrator users available.', 'dfx-parish-retreat-letters' ); ?></em></p>
			<?php else : ?>
				<form method="post">
					<?php wp_nonce_field( 'dfx_prl_user_management_nonce' ); ?>
					<input type="hidden" name="user_management_action" value="grant">

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="user_id"><?php esc_html_e( 'Select User', 'dfx-parish-retreat-letters' ); ?></label>
							</th>
							<td>
								<select name="user_id" id="user_id" class="dfx-user-select" style="width: 300px;" required>
									<option value=""><?php esc_html_e( 'Choose a user...', 'dfx-parish-retreat-letters' ); ?></option>
									<?php foreach ( $non_admin_users as $user ) : ?>
										<?php if ( ! $user->has_cap( 'manage_retreat_plugin' ) ) : ?>
										<option value="<?php echo esc_attr( $user->ID ); ?>">
											<?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
										</option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e( 'Select a user to grant global retreat management permissions.', 'dfx-parish-retreat-letters' ); ?></p>
							</td>
						</tr>
					</table>

					<p class="submit">
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Grant Access', 'dfx-parish-retreat-letters' ); ?>
						</button>
					</p>
				</form>
			<?php endif; ?>

			<hr>

			<h3><?php esc_html_e( 'Per-Retreat Permissions', 'dfx-parish-retreat-letters' ); ?></h3>
			<p class="description"><?php esc_html_e( 'For retreat-specific permissions (individual retreat management), use the corresponding retreat edition page', 'dfx-parish-retreat-letters' ); ?></p>

			<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Initialize Select2 for user selection
				if ($.fn.select2) {
					$('.dfx-user-select').select2({
						placeholder: '<?php esc_html_e( 'Choose a user...', 'dfx-parish-retreat-letters' ); ?>',
						allowClear: true,
						width: '100%'
					});
				}
			});
			</script>

			<?php $this->render_plugin_footer(); ?>
		</div>
		<?php
	}

	/**
	 * Handle global settings page form submissions.
	 *
	 * @since 1.6.0
	 */
	private function handle_global_settings_page_submissions() {
		// Check permissions first
		if ( ! $this->permissions->current_user_can_manage_plugin() ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'dfx-parish-retreat-letters' ) );
		}

		// Handle user management actions
		if ( isset( $_POST['user_management_action'] ) ) {
			$this->handle_user_management_actions();
			return;
		}

		// Handle global settings form submission
		if ( ! isset( $_POST['submit'] ) ) {
			return;
		}

		// Verify nonce
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'dfx_prl_global_settings_nonce' ) ) {
			$this->add_admin_notice( __( 'Security check failed. Please try again.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		// Process form submission
		$per_retreat_customization = isset( $_POST['enable_per_retreat_customization'] ) ? 1 : 0;
		$default_header = $this->parse_block_selection( sanitize_text_field( wp_unslash( $_POST['default_header_block_id'] ?? '' ) ) );
		$default_footer = $this->parse_block_selection( sanitize_text_field( wp_unslash( $_POST['default_footer_block_id'] ?? '' ) ) );
		$default_css = sanitize_textarea_field( wp_unslash( $_POST['default_css'] ?? '' ) );

		// Save settings
		$success = true;
		$success = $this->global_settings->set_per_retreat_customization_enabled( $per_retreat_customization ) && $success;
		$success = $this->global_settings->set_default_header( $default_header ) && $success;
		$success = $this->global_settings->set_default_footer( $default_footer ) && $success;
		$success = $this->global_settings->set_default_css( $default_css ) && $success;

		if ( $success ) {
			$this->add_admin_notice( __( 'Global settings saved successfully.', 'dfx-parish-retreat-letters' ), 'success' );
		} else {
			$this->add_admin_notice( __( 'Error saving global settings. Please try again.', 'dfx-parish-retreat-letters' ), 'error' );
		}
	}

	/**
	 * Handle user management actions.
	 *
	 * @since 1.6.1
	 */
	private function handle_user_management_actions() {
		// Verify nonce
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'dfx_prl_user_management_nonce' ) ) {
			$this->add_admin_notice( __( 'Security check failed. Please try again.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		$action = isset( $_POST['user_management_action'] ) ? sanitize_text_field( wp_unslash( $_POST['user_management_action'] ) ) : '';
		$user_id = absint( $_POST['user_id'] ?? 0 );

		if ( ! $action ) {
			$this->add_admin_notice( __( 'Invalid action.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		if ( ! $user_id ) {
			$this->add_admin_notice( __( 'Invalid user selected.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			$this->add_admin_notice( __( 'User not found.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		// Don't allow managing administrators
		if ( $user->has_cap( 'manage_options' ) ) {
			$this->add_admin_notice( __( 'Cannot modify permissions for administrators.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		switch ( $action ) {
			case 'grant':
				if ( $this->permissions->grant_global_retreat_management( $user_id ) ) {
					/* translators: %s: User display name */
					$this->add_admin_notice( sprintf( esc_html__( 'Global retreat management access granted to %s.', 'dfx-parish-retreat-letters' ), esc_html( $user->display_name ) ), 'success' );
				} else {
					$this->add_admin_notice( __( 'Failed to grant global retreat management access.', 'dfx-parish-retreat-letters' ), 'error' );
				}
				break;

			case 'revoke':
				if ( $this->permissions->revoke_global_retreat_management( $user_id ) ) {
					/* translators: %s: User display name */
					$this->add_admin_notice( sprintf( esc_html__( 'Global retreat management access revoked from %s.', 'dfx-parish-retreat-letters' ), esc_html( $user->display_name ) ), 'success' );
				} else {
					$this->add_admin_notice( __( 'Failed to revoke global retreat management access.', 'dfx-parish-retreat-letters' ), 'error' );
				}
				break;

			default:
				$this->add_admin_notice( __( 'Invalid action.', 'dfx-parish-retreat-letters' ), 'error' );
				break;
		}
	}

	/**
	 * Render the plugin footer for admin pages.
	 *
	 * @since 1.0.0
	 */
	private function render_plugin_footer() {
		?>
		<div class="dfx-prl-plugin-footer" style="position: fixed; bottom: 0; right: 0; left: 200px; z-index: 1000; background: #f1f1f1; border-top: 1px solid #ddd; padding: 10px 20px;">
			<p style="margin: 0; color: #666; font-size: 12px; text-align: right;">
				<?php
				echo wp_kses_post( sprintf(
					/* translators: %1$s: Plugin name, %2$s: Author link */
					__( 'Retreat letters management features provided via %1$s plugin by %2$s. A.M.D.G.', 'dfx-parish-retreat-letters' ),
					'<strong>DFX Parish Retreat Letters</strong>',
					sprintf('<a href="%s">David Marín Carreño</a>', esc_url( __( 'https://davefx.com/en/wordpress-plugins/', 'dfx-parish-retreat-letters' ) ) )
				) );
				?>
			</p>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Adjust footer positioning based on admin menu state
			function adjustFooterPosition() {
				var $footer = $('.dfx-prl-plugin-footer');
				var $adminMenu = $('#adminmenumain');

				if ($adminMenu.length && $adminMenu.hasClass('folded')) {
					// Menu is collapsed
					$footer.css('left', '36px');
				} else {
					// Menu is expanded
					$footer.css('left', '160px');
				}
			}

			// Initial adjustment
			adjustFooterPosition();

			// Listen for menu fold/unfold events
			$(document).on('wp-collapse-menu', adjustFooterPosition);

			// Fallback: monitor window resize
			$(window).on('resize', adjustFooterPosition);
		});
		</script>
		<?php
	}
}
