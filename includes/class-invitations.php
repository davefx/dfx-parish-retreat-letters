<?php
/**
 * The invitations management class
 *
 * Handles user invitation system for retreat permissions.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.3.0
 *
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */

/**
 * The invitations management class.
 *
 * This class handles the invitation system for granting retreat permissions
 * to new and existing users via secure email invitations.
 *
 * @since      1.3.0
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 * @author     DaveFX
 */
class DFXPRL_Invitations {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.3.0
	 * @var DFXPRL_Invitations|null
	 */
	private static $instance = null;

	/**
	 * The database instance.
	 *
	 * @since 1.3.0
	 * @var DFXPRL_Database
	 */
	private $database;

	/**
	 * The security instance.
	 *
	 * @since 1.3.0
	 * @var DFXPRL_Security
	 */
	private $security;

	/**
	 * The permissions instance.
	 *
	 * @since 1.3.0
	 * @var DFXPRL_Permissions
	 */
	private $permissions;

	/**
	 * Invitation status constants.
	 */
	const STATUS_PENDING = 'pending';
	const STATUS_ACCEPTED = 'accepted';
	const STATUS_EXPIRED = 'expired';
	const STATUS_CANCELLED = 'cancelled';

	/**
	 * Get the single instance of the class.
	 *
	 * @since 1.3.0
	 * @return DFXPRL_Invitations
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
	 * @since 1.3.0
	 */
	private function __construct() {
		$this->database = DFXPRL_Database::get_instance();
		$this->security = DFXPRL_Security::get_instance();
		$this->permissions = DFXPRL_Permissions::get_instance();
		
		// Initialize hooks for invitation handling
		add_action( 'init', array( $this, 'handle_invitation_routes' ) );
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
	 * Send an invitation to join a retreat with specific permissions.
	 *
	 * @since 1.3.0
	 * @param int    $retreat_id      Retreat ID.
	 * @param string $email           Email address to invite.
	 * @param string $name            Name of the person being invited.
	 * @param string $permission_level Permission level to grant.
	 * @param int    $invited_by      User ID who is sending the invitation.
	 * @return array Result array with success status and message.
	 */
	public function send_invitation( $retreat_id, $email, $name, $permission_level, $invited_by ) {
		// Validate inputs
		if ( ! is_email( $email ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid email address.', 'dfx-parish-retreat-letters' ),
			);
		}

		if ( ! in_array( $permission_level, array( 'manager', 'message_manager' ), true ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid permission level.', 'dfx-parish-retreat-letters' ),
			);
		}

		// Check if user already exists
		$existing_user = get_user_by( 'email', $email );
		if ( $existing_user ) {
			// If user exists, just grant permission directly
			$result = $this->permissions->grant_permission( $existing_user->ID, $retreat_id, $permission_level, $invited_by );
			if ( $result ) {
				return array(
					'success' => true,
					'message' => __( 'Permission granted to existing user.', 'dfx-parish-retreat-letters' ),
				);
			} else {
				return array(
					'success' => false,
					'message' => __( 'Failed to grant permission to existing user.', 'dfx-parish-retreat-letters' ),
				);
			}
		}

		// Check for existing pending invitation
		$existing_invitation = $this->get_pending_invitation( $retreat_id, $email, $permission_level );
		if ( $existing_invitation ) {
			return array(
				'success' => false,
				'message' => __( 'An invitation for this email and permission level is already pending.', 'dfx-parish-retreat-letters' ),
			);
		}

		// Generate secure token
		$token = $this->generate_invitation_token();
		
		// Set expiration (7 days from now)
		$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) );

		// Insert invitation
		global $wpdb;
		$result = $wpdb->insert(
			$this->database->get_invitations_table(),
			array(
				'retreat_id'       => $retreat_id,
				'email'            => $email,
				'name'             => sanitize_text_field( $name ),
				'permission_level' => $permission_level,
				'token'            => $token,
				'invited_by'       => $invited_by,
				'invited_at'       => current_time( 'mysql' ),
				'expires_at'       => $expires_at,
				'status'           => self::STATUS_PENDING,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( $result === false ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to create invitation.', 'dfx-parish-retreat-letters' ),
			);
		}

		$invitation_id = $wpdb->insert_id;

		// Send invitation email
		$email_sent = $this->send_invitation_email( $invitation_id, $retreat_id, $email, $name, $permission_level, $token );
		
		if ( ! $email_sent ) {
			// Delete the invitation if email failed
			$wpdb->delete( $this->database->get_invitations_table(), array( 'id' => $invitation_id ) );
			return array(
				'success' => false,
				'message' => __( 'Failed to send invitation email.', 'dfx-parish-retreat-letters' ),
			);
		}

		// Log the invitation
		$this->permissions->log_permission_action( 
			0, // No user ID yet
			$retreat_id, 
			'invitation_sent', 
			$permission_level, 
			$invited_by,
			sprintf( 'Invitation sent to %1$s (%2$s)', $name, $email )
		);

		return array(
			'success' => true,
			'message' => __( 'Invitation sent successfully.', 'dfx-parish-retreat-letters' ),
			'invitation_id' => $invitation_id,
		);
	}

	/**
	 * Accept an invitation and create user account if needed.
	 *
	 * @since 1.3.0
	 * @param string $token         Invitation token.
	 * @param array  $user_data     User data for account creation.
	 * @return array Result array with success status and message.
	 */
	public function accept_invitation( $token, $user_data = array() ) {
		// Get invitation by token
		$invitation = $this->get_invitation_by_token( $token );
		if ( ! $invitation ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid or expired invitation.', 'dfx-parish-retreat-letters' ),
			);
		}

		// Check if invitation is still valid
		if ( $invitation->status !== self::STATUS_PENDING ) {
			return array(
				'success' => false,
				'message' => __( 'This invitation has already been processed.', 'dfx-parish-retreat-letters' ),
			);
		}

		// Check expiration
		if ( strtotime( $invitation->expires_at ) < time() ) {
			$this->mark_invitation_expired( $invitation->id );
			return array(
				'success' => false,
				'message' => __( 'This invitation has expired.', 'dfx-parish-retreat-letters' ),
			);
		}

		// Check if user already exists
		$user = get_user_by( 'email', $invitation->email );
		
		if ( ! $user ) {
			// Create new user account
			$username = $this->generate_unique_username( $invitation->email );
			$password = wp_generate_password( 12, false );
			
			$user_id = wp_create_user( $username, $password, $invitation->email );
			
			if ( is_wp_error( $user_id ) ) {
				return array(
					'success' => false,
					'message' => __( 'Failed to create user account.', 'dfx-parish-retreat-letters' ),
				);
			}

			// Update user profile
			wp_update_user( array(
				'ID'           => $user_id,
				'display_name' => $invitation->name,
				'first_name'   => $user_data['first_name'] ?? '',
				'last_name'    => $user_data['last_name'] ?? '',
			) );

			$user = get_user_by( 'id', $user_id );

			// Send welcome email with login details
			$this->send_welcome_email( $user, $password );
		} else {
			$user_id = $user->ID;
		}

		// Grant permission
		$permission_granted = $this->permissions->grant_permission( 
			$user_id, 
			$invitation->retreat_id, 
			$invitation->permission_level, 
			$invitation->invited_by 
		);

		if ( ! $permission_granted ) {
			return array(
				'success' => false,
				'message' => __( 'Failed to grant permissions.', 'dfx-parish-retreat-letters' ),
			);
		}

		// Mark invitation as accepted
		global $wpdb;
		$wpdb->update(
			$this->database->get_invitations_table(),
			array(
				'status'          => self::STATUS_ACCEPTED,
				'accepted_at'     => current_time( 'mysql' ),
				'created_user_id' => $user_id,
			),
			array( 'id' => $invitation->id ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);

		// Log the acceptance
		$this->permissions->log_permission_action( 
			$user_id, 
			$invitation->retreat_id, 
			'invitation_accepted', 
			$invitation->permission_level, 
			$user_id,
			sprintf( 'User accepted invitation and account created/updated' )
		);

		return array(
			'success' => true,
			'message' => __( 'Invitation accepted successfully.', 'dfx-parish-retreat-letters' ),
			'user_id' => $user_id,
		);
	}

	/**
	 * Cancel an invitation.
	 *
	 * @since 1.3.0
	 * @param int $invitation_id Invitation ID.
	 * @param int $cancelled_by  User ID who is cancelling.
	 * @return bool True on success, false on failure.
	 */
	public function cancel_invitation( $invitation_id, $cancelled_by ) {
		global $wpdb;

		$invitations_table = $this->database->get_invitations_table();
		$invitation = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$invitations_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from get_invitations_table()
			$invitation_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $invitation || $invitation->status !== self::STATUS_PENDING ) {
			return false;
		}

		$result = $wpdb->update(
			$this->database->get_invitations_table(),
			array( 'status' => self::STATUS_CANCELLED ),
			array( 'id' => $invitation_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			// Log the cancellation
			$this->permissions->log_permission_action( 
				0, // No user ID for cancelled invitations
				$invitation->retreat_id, 
				'invitation_cancelled', 
				$invitation->permission_level, 
				$cancelled_by,
				sprintf( 'Invitation cancelled for %1$s (%2$s)', $invitation->name, $invitation->email )
			);
		}

		return $result !== false;
	}

	/**
	 * Get all invitations for a retreat.
	 *
	 * @since 1.3.0
	 * @param int    $retreat_id Retreat ID.
	 * @param string $status     Optional status filter.
	 * @return array Array of invitation objects.
	 */
	public function get_retreat_invitations( $retreat_id, $status = null ) {
		global $wpdb;

		$where_clause = "WHERE retreat_id = %d";
		$params = array( $retreat_id );

		if ( $status ) {
			$where_clause .= " AND status = %s";
			$params[] = $status;
		}

		$invitations_table = $this->database->get_invitations_table();
		$query = "SELECT i.*, ib.display_name as invited_by_name
		          FROM {$invitations_table} i
		          INNER JOIN {$wpdb->users} ib ON i.invited_by = ib.ID
		          {$where_clause}
		          ORDER BY i.invited_at DESC";
		$invitations = $wpdb->get_results( $wpdb->prepare(
			$query,
			...$params
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return $invitations ? $invitations : array();
	}

	/**
	 * Handle invitation URL routing.
	 *
	 * @since 1.3.0
	 */
	public function handle_invitation_routes() {
		// Check if we're on an invitation URL: /retreat-invitation/[token]
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		
		if ( ! is_string( $request_uri ) || empty( $request_uri ) ) {
			return;
		}
		
		$request_uri = strtok( $request_uri, '?' );
		if ( $request_uri === false ) {
			return;
		}
		
		// Get the site's base path
		$site_url = wp_parse_url( home_url(), PHP_URL_PATH );
		$site_path = ( is_string( $site_url ) && ! empty( $site_url ) ) ? $site_url : '/';
		
		if ( $site_path !== '/' ) {
			$site_path = rtrim( $site_path, '/' );
		}
		
		$pattern = '#^' . preg_quote( $site_path, '#' ) . '/retreat-invitation/([a-zA-Z0-9]+)/?$#';
		$root_pattern = '#^/retreat-invitation/([a-zA-Z0-9]+)/?$#';
		
		$token = null;
		if ( preg_match( $pattern, $request_uri, $matches ) ) {
			$token = sanitize_text_field( $matches[1] );
		} elseif ( preg_match( $root_pattern, $request_uri, $matches ) ) {
			$token = sanitize_text_field( $matches[1] );
		}
		
		if ( $token ) {
			$this->display_invitation_page( $token );
			exit;
		}
	}

	/**
	 * Display the invitation acceptance page.
	 *
	 * @since 1.3.0
	 * @param string $token Invitation token.
	 */
	private function display_invitation_page( $token ) {
		$invitation = $this->get_invitation_by_token( $token );
		
		if ( ! $invitation || $invitation->status !== self::STATUS_PENDING ) {
			wp_die( esc_html__( 'Invalid or expired invitation.', 'dfx-parish-retreat-letters' ) );
		}

		// Check expiration
		if ( strtotime( $invitation->expires_at ) < time() ) {
			$this->mark_invitation_expired( $invitation->id );
			wp_die( esc_html__( 'This invitation has expired.', 'dfx-parish-retreat-letters' ) );
		}

		// Get retreat details
		global $wpdb;
		$retreats_table = $this->database->get_retreats_table();
		$retreat = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$retreats_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from get_retreats_table()
			$invitation->retreat_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $retreat ) {
			wp_die( esc_html__( 'Retreat not found.', 'dfx-parish-retreat-letters' ) );
		}

		// Handle form submission
		if ( ! empty( $_POST ) && isset( $_POST['invitation_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['invitation_nonce'] ) ), 'accept_invitation_' . $token ) ) {
			$user_data = array(
				'first_name' => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
				'last_name'  => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
			);

			$result = $this->accept_invitation( $token, $user_data );
			
			if ( $result['success'] ) {
				// Redirect to success page or login
				wp_safe_redirect( wp_login_url() . '?invitation=accepted' );
				exit;
			} else {
				$error_message = $result['message'];
			}
		}

		// Display the form
		$this->render_invitation_page( $invitation, $retreat, $error_message ?? null );
	}

	/**
	 * Render the invitation acceptance page.
	 *
	 * @since 1.3.0
	 * @param object $invitation   Invitation object.
	 * @param object $retreat      Retreat object.
	 * @param string $error_message Optional error message.
	 */
	private function render_invitation_page( $invitation, $retreat, $error_message = null ) {
		$permission_name = $invitation->permission_level === 'manager' 
			? __( 'Retreat Manager', 'dfx-parish-retreat-letters' )
			: __( 'Message Manager', 'dfx-parish-retreat-letters' );

		// Enqueue invitation page styles
		add_action( 'wp_head', array( $this, 'output_invitation_styles' ), 100 );

		$this->render_theme_header();
		?>
		<div class="dfxprl-invitation-container">
			<div class="dfxprl-invitation-content">
				<h1><?php esc_html_e( 'Retreat Invitation', 'dfx-parish-retreat-letters' ); ?></h1>
				
				<?php if ( $error_message ) : ?>
					<div class="dfxprl-error-notice">
						<?php echo esc_html( $error_message ); ?>
					</div>
				<?php endif; ?>

				<div class="dfxprl-invitation-details">
					<h2><?php esc_html_e( 'You have been invited to:', 'dfx-parish-retreat-letters' ); ?></h2>
					<div class="dfxprl-retreat-info">
						<h3><?php echo esc_html( $retreat->name ); ?></h3>
						<p><strong><?php esc_html_e( 'Location:', 'dfx-parish-retreat-letters' ); ?></strong> <?php echo esc_html( $retreat->location ); ?></p>
						<p><strong><?php esc_html_e( 'Dates:', 'dfx-parish-retreat-letters' ); ?></strong> 
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $retreat->start_date ) ) ); ?> - 
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $retreat->end_date ) ) ); ?>
						</p>
						<p><strong><?php esc_html_e( 'Role:', 'dfx-parish-retreat-letters' ); ?></strong> <?php echo esc_html( $permission_name ); ?></p>
					</div>
				</div>

				<form method="post" class="dfxprl-invitation-form">
					<?php wp_nonce_field( 'accept_invitation_' . $invitation->token, 'invitation_nonce' ); ?>
					
					<h3><?php esc_html_e( 'Complete Your Profile', 'dfx-parish-retreat-letters' ); ?></h3>
					
					<div class="dfxprl-form-group">
						<label for="first_name"><?php esc_html_e( 'First Name', 'dfx-parish-retreat-letters' ); ?></label>
						<input type="text" id="first_name" name="first_name" value="<?php echo esc_attr( isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified on line 529 before processing ?>" required>
					</div>

					<div class="dfxprl-form-group">
						<label for="last_name"><?php esc_html_e( 'Last Name', 'dfx-parish-retreat-letters' ); ?></label>
						<input type="text" id="last_name" name="last_name" value="<?php echo esc_attr( isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified on line 529 before processing ?>" required>
					</div>

					<div class="dfxprl-form-group">
						<button type="submit" class="dfxprl-accept-button">
							<?php esc_html_e( 'Accept Invitation', 'dfx-parish-retreat-letters' ); ?>
						</button>
					</div>
				</form>

				<div class="dfxprl-invitation-info">
					<h3><?php esc_html_e( 'What happens next?', 'dfx-parish-retreat-letters' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'A user account will be created for you automatically', 'dfx-parish-retreat-letters' ); ?></li>
						<li><?php esc_html_e( 'You will receive login credentials via email', 'dfx-parish-retreat-letters' ); ?></li>
						<li><?php esc_html_e( 'You can then access the retreat management system', 'dfx-parish-retreat-letters' ); ?></li>
					</ul>
				</div>
			</div>
		</div>

		<?php
		// Styles are now output via output_invitation_styles() method
		$this->render_theme_footer();
	}

	/**
	 * Output styles for invitation page.
	 *
	 * @since 25.12.10
	 */
	public function output_invitation_styles() {
		$styles = '
		.dfx-prl-invitation-container {
			max-width: 600px;
			margin: 2rem auto;
			padding: 2rem;
			background: #fff;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
			border-radius: 8px;
		}

		.dfxprl-invitation-content h1 {
			text-align: center;
			color: #333;
			margin-bottom: 2rem;
		}

		.dfxprl-error-notice {
			background: #f8d7da;
			color: #721c24;
			padding: 1rem;
			border-radius: 4px;
			border: 1px solid #f5c6cb;
			margin-bottom: 1.5rem;
		}

		.dfxprl-retreat-info {
			background: #f8f9fa;
			padding: 1.5rem;
			border-radius: 4px;
			border-left: 4px solid #007cba;
			margin: 1rem 0;
		}

		.dfxprl-retreat-info h3 {
			margin-top: 0;
			color: #007cba;
		}

		.dfxprl-form-group {
			margin-bottom: 1.5rem;
		}

		.dfxprl-form-group label {
			display: block;
			margin-bottom: 0.5rem;
			font-weight: 600;
			color: #333;
		}

		.required {
			color: #d63384;
		}

		.dfx-prl-form-group input {
			width: 100%;
			padding: 0.75rem;
			border: 1px solid #ddd;
			border-radius: 4px;
			font-size: 1rem;
		}

		.dfx-prl-form-group input:focus {
			outline: none;
			border-color: #007cba;
		}

		.dfx-prl-submit-button {
			width: 100%;
			padding: 1rem;
			background: #007cba;
			color: #fff;
			border: none;
			border-radius: 4px;
			font-size: 1.1rem;
			font-weight: 600;
			cursor: pointer;
			transition: background-color 0.3s;
		}

		.dfx-prl-submit-button:hover {
			background: #005a87;
		}

		.dfx-prl-invitation-info {
			background: #e7f3ff;
			padding: 1.5rem;
			border-radius: 4px;
			margin: 1.5rem 0;
			border-left: 4px solid #0073aa;
		}

		.dfxprl-invitation-info h3 {
			margin-top: 0;
			color: #0073aa;
		}

		.dfx-prl-invitation-info ul {
			margin: 0;
			padding-left: 1.5rem;
		}

		.dfxprl-invitation-info li {
			margin-bottom: 0.5rem;
		}
		';

		// Register a dummy style handle and add inline styles
		wp_register_style( 'dfx-prl-invitation', false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'dfx-prl-invitation' );
		wp_add_inline_style( 'dfx-prl-invitation', $styles );
	}

	/**
	 * Send invitation email.
	 *
	 * @since 1.3.0
	 * @param int    $invitation_id   Invitation ID.
	 * @param int    $retreat_id      Retreat ID.
	 * @param string $email           Email address.
	 * @param string $name            Name of invitee.
	 * @param string $permission_level Permission level.
	 * @param string $token           Invitation token.
	 * @return bool True if email sent successfully.
	 */
	private function send_invitation_email( $invitation_id, $retreat_id, $email, $name, $permission_level, $token ) {
		// Get retreat details
		global $wpdb;
		$retreats_table = $this->database->get_retreats_table();
		$retreat = $wpdb->get_row( $wpdb->prepare(
			"SELECT name, location, start_date, end_date FROM {$retreats_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from get_retreats_table()
			$retreat_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $retreat ) {
			return false;
		}

		$permission_name = $permission_level === 'manager' 
			? __( 'Retreat Manager', 'dfx-parish-retreat-letters' )
			: __( 'Message Manager', 'dfx-parish-retreat-letters' );

		$invitation_url = home_url( "/retreat-invitation/{$token}" );
		
		$subject = sprintf(
			/* translators: %s: retreat name */
			__( 'Invitation to manage retreat: %s', 'dfx-parish-retreat-letters' ),
			$retreat->name
		);

		// Create HTML message
		$message_html = sprintf(
			'<p>%s</p>
			<p>%s</p>
			<div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #007cba;">
				<p><strong>%s:</strong> %s</p>
				<p><strong>%s:</strong> %s</p>
				<p><strong>%s:</strong> %s - %s</p>
				<p><strong>%s:</strong> %s</p>
			</div>
			<p><a href="%s" style="background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">%s</a></p>
			<p style="color: #666; font-size: 14px;"><em>%s</em></p>
			<p>%s</p>
			<p>%s<br>%s</p>',
			/* translators: %s: person's name */
			sprintf( __( 'Hello %s,', 'dfx-parish-retreat-letters' ), $name ),
			__( 'You have been invited to participate in the management of the following retreat:', 'dfx-parish-retreat-letters' ),
			__( 'Retreat', 'dfx-parish-retreat-letters' ),
			esc_html( $retreat->name ),
			__( 'Location', 'dfx-parish-retreat-letters' ),
			esc_html( $retreat->location ),
			__( 'Dates', 'dfx-parish-retreat-letters' ),
			esc_html( date_i18n( get_option( 'date_format' ), strtotime( $retreat->start_date ) ) ),
			esc_html( date_i18n( get_option( 'date_format' ), strtotime( $retreat->end_date ) ) ),
			__( 'Your Role', 'dfx-parish-retreat-letters' ),
			esc_html( $permission_name ),
			esc_url( $invitation_url ),
			__( 'Accept Invitation', 'dfx-parish-retreat-letters' ),
			__( 'This invitation will expire in 7 days.', 'dfx-parish-retreat-letters' ),
			__( 'If you have any questions, please contact the retreat administrator.', 'dfx-parish-retreat-letters' ),
			__( 'Best regards,', 'dfx-parish-retreat-letters' ),
			esc_html( get_bloginfo( 'name' ) )
		);

		// Set content type to HTML
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_mail_content_type' ) );
		
		$result = wp_mail( $email, $subject, $message_html );
		
		// Remove content type filter
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_mail_content_type' ) );

		return $result;
	}

	/**
	 * Send welcome email to new user.
	 *
	 * @since 1.3.0
	 * @param WP_User $user     User object.
	 * @param string  $password Generated password.
	 * @return bool True if email sent successfully.
	 */
	private function send_welcome_email( $user, $password ) {
		$subject = sprintf(
			/* translators: %s: site name */
			__( 'Welcome to %s - Your account details', 'dfx-parish-retreat-letters' ),
			get_bloginfo( 'name' )
		);

		// Create HTML message
		$message_html = sprintf(
			'<p>%s</p>
			<p><strong>%s</strong></p>
			<div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0; border-left: 4px solid #007cba;">
				<p><strong>%s:</strong> %s</p>
				<p><strong>%s:</strong> %s</p>
				<p><strong>%s:</strong> <code style="background: #e9ecef; padding: 2px 6px; border-radius: 3px;">%s</code></p>
			</div>
			<p><a href="%s" style="background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">%s</a></p>
			<p style="color: #dc3545; font-weight: bold;">%s</p>
			<p>%s<br>%s</p>',
			/* translators: %s: user's display name */
			sprintf( __( 'Welcome %s,', 'dfx-parish-retreat-letters' ), $user->display_name ),
			__( 'Your account has been created successfully!', 'dfx-parish-retreat-letters' ),
			__( 'Username', 'dfx-parish-retreat-letters' ),
			esc_html( $user->user_login ),
			__( 'Email', 'dfx-parish-retreat-letters' ),
			esc_html( $user->user_email ),
			__( 'Password', 'dfx-parish-retreat-letters' ),
			esc_html( $password ),
			esc_url( wp_login_url() ),
			__( 'Login Now', 'dfx-parish-retreat-letters' ),
			__( 'For security, please change your password after your first login.', 'dfx-parish-retreat-letters' ),
			__( 'Best regards,', 'dfx-parish-retreat-letters' ),
			esc_html( get_bloginfo( 'name' ) )
		);

		// Set content type to HTML
		add_filter( 'wp_mail_content_type', array( $this, 'set_html_mail_content_type' ) );
		
		$result = wp_mail( $user->user_email, $subject, $message_html );
		
		// Remove content type filter
		remove_filter( 'wp_mail_content_type', array( $this, 'set_html_mail_content_type' ) );

		return $result;
	}

	/**
	 * Generate a unique invitation token.
	 *
	 * @since 1.3.0
	 * @return string Unique token.
	 */
	private function generate_invitation_token() {
		global $wpdb;
		
		do {
			$token = $this->security->generate_secure_token();
			$invitations_table = $this->database->get_invitations_table();
			$exists = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$invitations_table} WHERE token = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from get_invitations_table()
				$token
			) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} while ( $exists > 0 );

		return $token;
	}

	/**
	 * Generate a unique username from email.
	 *
	 * @since 1.3.0
	 * @param string $email Email address.
	 * @return string Unique username.
	 */
	private function generate_unique_username( $email ) {
		$base_username = sanitize_user( substr( $email, 0, strpos( $email, '@' ) ) );
		$username = $base_username;
		$counter = 1;

		while ( username_exists( $username ) ) {
			$username = $base_username . $counter;
			$counter++;
		}

		return $username;
	}

	/**
	 * Get invitation by token.
	 *
	 * @since 1.3.0
	 * @param string $token Invitation token.
	 * @return object|null Invitation object or null.
	 */
	private function get_invitation_by_token( $token ) {
		global $wpdb;

		$invitations_table = $this->database->get_invitations_table();
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$invitations_table} WHERE token = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from get_invitations_table()
			$token
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Get pending invitation for email and permission level.
	 *
	 * @since 1.3.0
	 * @param int    $retreat_id      Retreat ID.
	 * @param string $email           Email address.
	 * @param string $permission_level Permission level.
	 * @return object|null Invitation object or null.
	 */
	private function get_pending_invitation( $retreat_id, $email, $permission_level ) {
		global $wpdb;

		$invitations_table = $this->database->get_invitations_table();
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$invitations_table} // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from get_invitations_table()
			 WHERE retreat_id = %d AND email = %s AND permission_level = %s AND status = %s",
			$retreat_id,
			$email,
			$permission_level,
			self::STATUS_PENDING
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Mark invitation as expired.
	 *
	 * @since 1.3.0
	 * @param int $invitation_id Invitation ID.
	 */
	private function mark_invitation_expired( $invitation_id ) {
		global $wpdb;

		$wpdb->update(
			$this->database->get_invitations_table(),
			array( 'status' => self::STATUS_EXPIRED ),
			array( 'id' => $invitation_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Clean up expired invitations.
	 *
	 * @since 1.3.0
	 * @return int Number of invitations cleaned up.
	 */
	public function cleanup_expired_invitations() {
		global $wpdb;

		$invitations_table = $this->database->get_invitations_table();
		
		// Mark expired invitations
		$wpdb->query(
			"UPDATE {$invitations_table} // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from get_invitations_table()
			 SET status = 'expired'
			 WHERE status = 'pending' AND expires_at < NOW()"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		// Delete old invitations (older than 1 year)
		$deleted = $wpdb->query(
			"DELETE FROM {$invitations_table} // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from get_invitations_table()
			 WHERE status IN ('expired', 'cancelled') AND invited_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)"
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $deleted ? $deleted : 0;
	}

	/**
	 * Set HTML content type for wp_mail.
	 *
	 * @since 1.3.0
	 * @return string HTML content type.
	 */
	public function set_html_mail_content_type() {
		return 'text/html';
	}

	/**
	 * Delete all invitations for a specific retreat.
	 * This method implements cascade delete functionality to replace database foreign key constraints.
	 *
	 * @since 1.4.0
	 * @param int $retreat_id Retreat ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_by_retreat( $retreat_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->database->get_invitations_table(),
			array( 'retreat_id' => $retreat_id ),
			array( '%d' )
		);

		return $result !== false;
	}
}