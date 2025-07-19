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
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_dfx_delete_retreat', array( $this, 'ajax_delete_retreat' ) );
		add_action( 'wp_ajax_dfx_delete_attendant', array( $this, 'ajax_delete_attendant' ) );
		add_action( 'wp_ajax_dfx_export_attendants_csv', array( $this, 'ajax_export_attendants_csv' ) );
	}

	/**
	 * Add admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Retreats', 'dfx-parish-retreat-letters' ),
			__( 'Retreats', 'dfx-parish-retreat-letters' ),
			'manage_options',
			'dfx-retreats',
			array( $this, 'retreats_list_page' ),
			'dashicons-groups',
			30
		);

		add_submenu_page(
			'dfx-retreats',
			__( 'All Retreats', 'dfx-parish-retreat-letters' ),
			__( 'All Retreats', 'dfx-parish-retreat-letters' ),
			'manage_options',
			'dfx-retreats',
			array( $this, 'retreats_list_page' )
		);

		add_submenu_page(
			'dfx-retreats',
			__( 'Add New Retreat', 'dfx-parish-retreat-letters' ),
			__( 'Add New', 'dfx-parish-retreat-letters' ),
			'manage_options',
			'dfx-retreats-add',
			array( $this, 'retreat_add_page' )
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'dfx-retreats' ) === false ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script(
			'dfx-retreats-admin',
			DFX_PARISH_RETREAT_LETTERS_PLUGIN_URL . 'includes/admin.js',
			array( 'jquery' ),
			DFX_PARISH_RETREAT_LETTERS_VERSION,
			true
		);

		wp_localize_script(
			'dfx-retreats-admin',
			'dfxRetreatsAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'dfx_retreats_nonce' ),
				'messages' => array(
					'confirmDelete' => __( 'Are you sure you want to delete this retreat?', 'dfx-parish-retreat-letters' ),
					'confirmDeleteAttendant' => __( 'Are you sure you want to delete this attendant?', 'dfx-parish-retreat-letters' ),
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
		$action = sanitize_text_field( $_GET['action'] ?? '' );
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
		// Handle form submissions
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$this->handle_list_page_actions();
		}

		// Get query parameters
		$search   = sanitize_text_field( $_GET['s'] ?? '' );
		$page_num = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$per_page = 20;

		// Get retreats with attendant counts
		$retreats = $this->retreat_model->get_all( array(
			'search'   => $search,
			'per_page' => $per_page,
			'page'     => $page_num,
			'include_attendant_count' => true,
		) );

		$total_items = $this->retreat_model->get_count( $search );
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

		// Handle form submission
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$this->handle_add_edit_submission( $retreat_id );
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
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'dfx_retreats_action' ) ) {
			wp_die( __( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$action = sanitize_text_field( $_POST['action'] ?? '' );
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
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'dfx_retreats_add_edit' ) ) {
			wp_die( __( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$data = array(
			'name'       => sanitize_text_field( $_POST['name'] ?? '' ),
			'location'   => sanitize_text_field( $_POST['location'] ?? '' ),
			'start_date' => sanitize_text_field( $_POST['start_date'] ?? '' ),
			'end_date'   => sanitize_text_field( $_POST['end_date'] ?? '' ),
		);

		if ( $retreat_id ) {
			// Update existing retreat
			if ( $this->retreat_model->update( $retreat_id, $data ) ) {
				$this->add_admin_notice( __( 'Retreat updated successfully.', 'dfx-parish-retreat-letters' ), 'success' );
				wp_redirect( admin_url( 'admin.php?page=dfx-retreats' ) );
				exit;
			} else {
				$this->add_admin_notice( __( 'Error updating retreat. Please check your data.', 'dfx-parish-retreat-letters' ), 'error' );
			}
		} else {
			// Create new retreat
			$new_id = $this->retreat_model->create( $data );
			if ( $new_id ) {
				$this->add_admin_notice( __( 'Retreat created successfully.', 'dfx-parish-retreat-letters' ), 'success' );
				wp_redirect( admin_url( 'admin.php?page=dfx-retreats' ) );
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
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'dfx_retreats_nonce' ) ) {
			wp_die( __( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat_id = absint( $_POST['retreat_id'] ?? 0 );
		$retreat_name = sanitize_text_field( $_POST['retreat_name'] ?? '' );

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
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats-add' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'dfx-parish-retreat-letters' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php $this->display_admin_notices(); ?>

			<form method="get" action="">
				<input type="hidden" name="page" value="dfx-retreats">
				<p class="search-box">
					<label class="screen-reader-text" for="retreat-search-input"><?php esc_html_e( 'Search Retreats:', 'dfx-parish-retreat-letters' ); ?></label>
					<input type="search" id="retreat-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
					<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Retreats', 'dfx-parish-retreat-letters' ); ?>">
				</p>
			</form>

			<form method="post" action="">
				<?php wp_nonce_field( 'dfx_retreats_action' ); ?>
				<div class="tablenav top">
					<div class="alignleft actions">
						<p><?php printf( esc_html__( 'Total retreats: %d', 'dfx-parish-retreat-letters' ), $total_items ); ?></p>
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
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats-add&edit=' . $retreat->id ) ); ?>">
												<?php echo esc_html( $retreat->name ); ?>
											</a>
										</strong>
									</td>
									<td><?php echo esc_html( $retreat->location ); ?></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $retreat->start_date ) ) ); ?></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $retreat->end_date ) ) ); ?></td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>">
											<?php
											$count = $retreat->attendant_count ?? 0;
											printf(
												/* translators: %d: Number of attendants */
												esc_html( _n( '%d attendant', '%d attendants', $count, 'dfx-parish-retreat-letters' ) ),
												$count
											);
											?>
										</a>
									</td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats-add&edit=' . $retreat->id ) ); ?>" class="button button-small">
											<?php esc_html_e( 'Edit', 'dfx-parish-retreat-letters' ); ?>
										</a>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>" class="button button-small">
											<?php esc_html_e( 'Attendants', 'dfx-parish-retreat-letters' ); ?>
										</a>
										<button type="button" class="button button-small button-link-delete dfx-delete-retreat" data-retreat-id="<?php echo esc_attr( $retreat->id ); ?>" data-retreat-name="<?php echo esc_attr( $retreat->name ); ?>">
											<?php esc_html_e( 'Delete', 'dfx-parish-retreat-letters' ); ?>
										</button>
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
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats-add' ) ); ?>">
											<?php esc_html_e( 'Add the first retreat', 'dfx-parish-retreat-letters' ); ?>
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</form>
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

			<form method="post" action="">
				<?php wp_nonce_field( 'dfx_retreats_add_edit' ); ?>
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
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr( $is_edit ? __( 'Update Retreat', 'dfx-parish-retreat-letters' ) : __( 'Add Retreat', 'dfx-parish-retreat-letters' ) ); ?>">
					<?php if ( $is_edit ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>" class="button">
							<?php esc_html_e( 'Manage Attendants', 'dfx-parish-retreat-letters' ); ?>
						</a>
					<?php endif; ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats' ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'dfx-parish-retreat-letters' ); ?>
					</a>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Add an admin notice.
	 *
	 * @since 1.0.0
	 * @param string $message Notice message.
	 * @param string $type    Notice type (success, error, warning, info).
	 */
	private function add_admin_notice( $message, $type = 'info' ) {
		$notices = get_transient( 'dfx_admin_notices' ) ?: array();
		$notices[] = array(
			'message' => $message,
			'type'    => $type,
		);
		set_transient( 'dfx_admin_notices', $notices, 30 );
	}

	/**
	 * Display admin notices.
	 *
	 * @since 1.0.0
	 */
	private function display_admin_notices() {
		$notices = get_transient( 'dfx_admin_notices' );
		if ( ! $notices ) {
			return;
		}

		foreach ( $notices as $notice ) {
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);
		}

		delete_transient( 'dfx_admin_notices' );
	}

	/**
	 * Display the attendants list page for a specific retreat.
	 *
	 * @since 1.0.0
	 * @param int $retreat_id Retreat ID.
	 */
	private function attendants_list_page( $retreat_id ) {
		if ( ! $retreat_id ) {
			wp_die( __( 'Invalid retreat ID.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat = $this->retreat_model->get( $retreat_id );
		if ( ! $retreat ) {
			wp_die( __( 'Retreat not found.', 'dfx-parish-retreat-letters' ) );
		}

		// Handle form submissions
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$this->handle_attendant_list_actions( $retreat_id );
		}

		// Get query parameters
		$search   = sanitize_text_field( $_GET['s'] ?? '' );
		$page_num = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$per_page = 20;

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
			wp_die( __( 'Invalid retreat ID.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat = $this->retreat_model->get( $retreat_id );
		if ( ! $retreat ) {
			wp_die( __( 'Retreat not found.', 'dfx-parish-retreat-letters' ) );
		}

		// Handle form submission
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$this->handle_attendant_add_edit_submission( $retreat_id );
			return;
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
			wp_die( __( 'Invalid retreat or attendant ID.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat = $this->retreat_model->get( $retreat_id );
		$attendant = $this->attendant_model->get( $attendant_id );

		if ( ! $retreat || ! $attendant || $attendant->retreat_id != $retreat_id ) {
			wp_die( __( 'Retreat or attendant not found, or attendant does not belong to this retreat.', 'dfx-parish-retreat-letters' ) );
		}

		// Handle form submission
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$this->handle_attendant_add_edit_submission( $retreat_id, $attendant_id );
			return;
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
			wp_die( __( 'Invalid retreat ID.', 'dfx-parish-retreat-letters' ) );
		}

		$retreat = $this->retreat_model->get( $retreat_id );
		if ( ! $retreat ) {
			wp_die( __( 'Retreat not found.', 'dfx-parish-retreat-letters' ) );
		}

		// Handle form submission
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			$this->handle_csv_import( $retreat_id );
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
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'dfx_attendants_action' ) ) {
			wp_die( __( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$action = sanitize_text_field( $_POST['action'] ?? '' );
		$attendant_id = absint( $_POST['attendant_id'] ?? 0 );

		if ( $action === 'delete' && $attendant_id ) {
			if ( $this->attendant_model->delete( $attendant_id ) ) {
				$this->add_admin_notice( __( 'Attendant deleted successfully.', 'dfx-parish-retreat-letters' ), 'success' );
			} else {
				$this->add_admin_notice( __( 'Error deleting attendant.', 'dfx-parish-retreat-letters' ), 'error' );
			}
		} elseif ( $action === 'export_csv' ) {
			$this->export_attendants_csv( $retreat_id );
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
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'dfx_attendants_add_edit' ) ) {
			wp_die( __( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		$data = array(
			'retreat_id'                => $retreat_id,
			'name'                      => sanitize_text_field( $_POST['name'] ?? '' ),
			'surnames'                  => sanitize_text_field( $_POST['surnames'] ?? '' ),
			'date_of_birth'             => sanitize_text_field( $_POST['date_of_birth'] ?? '' ),
			'emergency_contact_name'    => sanitize_text_field( $_POST['emergency_contact_name'] ?? '' ),
			'emergency_contact_surname' => sanitize_text_field( $_POST['emergency_contact_surname'] ?? '' ),
			'emergency_contact_phone'   => sanitize_text_field( $_POST['emergency_contact_phone'] ?? '' ),
		);

		if ( $attendant_id ) {
			// Update existing attendant
			if ( $this->attendant_model->update( $attendant_id, $data ) ) {
				$this->add_admin_notice( __( 'Attendant updated successfully.', 'dfx-parish-retreat-letters' ), 'success' );
				wp_redirect( admin_url( 'admin.php?page=dfx-retreats&action=attendants&retreat_id=' . $retreat_id ) );
				exit;
			} else {
				$this->add_admin_notice( __( 'Error updating attendant. Please check your data.', 'dfx-parish-retreat-letters' ), 'error' );
			}
		} else {
			// Create new attendant
			$new_id = $this->attendant_model->create( $data );
			if ( $new_id ) {
				$this->add_admin_notice( __( 'Attendant created successfully.', 'dfx-parish-retreat-letters' ), 'success' );
				wp_redirect( admin_url( 'admin.php?page=dfx-retreats&action=attendants&retreat_id=' . $retreat_id ) );
				exit;
			} else {
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
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'dfx_attendants_import' ) ) {
			wp_die( __( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
		}

		// Save the date format preference
		$date_format_preference = sanitize_text_field( $_POST['date_format_preference'] ?? 'dmy' );
		if ( in_array( $date_format_preference, array( 'dmy', 'mdy' ), true ) ) {
			update_option( 'dfx_retreat_letters_date_format', $date_format_preference );
		}

		if ( ! isset( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
			$this->add_admin_notice( __( 'Please select a valid CSV file.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		$file = $_FILES['csv_file'];
		$file_path = $file['tmp_name'];

		// Basic file validation
		if ( $file['size'] > 2 * 1024 * 1024 ) { // 2MB limit
			$this->add_admin_notice( __( 'CSV file is too large. Maximum size is 2MB.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			$this->add_admin_notice( __( 'Unable to read CSV file.', 'dfx-parish-retreat-letters' ), 'error' );
			return;
		}

		$imported = 0;
		$errors = 0;
		$line_number = 0;
		$error_details = array();
		$ambiguous_dates = array();

		// Read header row for field mapping
		$headers = fgetcsv( $handle );
		$line_number++;
		
		if ( ! $headers ) {
			$this->add_admin_notice( __( 'CSV file appears to be empty or invalid.', 'dfx-parish-retreat-letters' ), 'error' );
			fclose( $handle );
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
			fclose( $handle );
			return;
		}

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$line_number++;

			// Skip empty rows
			if ( empty( array_filter( $row ) ) ) {
				continue;
			}

			// Map row data using field mapping
			$mapped_data = $this->map_csv_row_data( $row, $field_map, $ambiguous_dates );
			
			if ( ! $mapped_data ) {
				$errors++;
				$error_details[] = sprintf( __( 'Line %d: Invalid data format', 'dfx-parish-retreat-letters' ), $line_number );
				continue;
			}

			$mapped_data['retreat_id'] = $retreat_id;

			if ( $this->attendant_model->create( $mapped_data ) ) {
				$imported++;
			} else {
				$errors++;
				$error_details[] = sprintf( __( 'Line %d: Failed to create attendant', 'dfx-parish-retreat-letters' ), $line_number );
			}
		}

		fclose( $handle );

		if ( $imported > 0 ) {
			$this->add_admin_notice( 
				sprintf(
					/* translators: %d: Number of imported attendants */
					__( 'Successfully imported %d attendants.', 'dfx-parish-retreat-letters' ),
					$imported
				), 
				'success' 
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
				$error_message .= '<br><strong>' . __( 'Error details:', 'dfx-parish-retreat-letters' ) . '</strong><br>';
				$error_message .= implode( '<br>', array_slice( $error_details, 0, 10 ) );
				if ( count( $error_details ) > 10 ) {
					$error_message .= '<br>' . __( '...and more errors.', 'dfx-parish-retreat-letters' );
				}
			}
			
			$this->add_admin_notice( $error_message, 'warning' );
		}

		// Warn about ambiguous dates if any were found
		if ( ! empty( $ambiguous_dates ) ) {
			$unique_ambiguous = array_unique( $ambiguous_dates );
			$current_preference = get_option( 'dfx_retreat_letters_date_format', 'dmy' );
			
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
				$ambiguous_message .= '<br><strong>' . __( 'Ambiguous dates found:', 'dfx-parish-retreat-letters' ) . '</strong> ' . implode( ', ', $unique_ambiguous );
			}
			
			$ambiguous_message .= '<br>' . __( 'To avoid ambiguity in future imports, consider using YYYY-MM-DD format.', 'dfx-parish-retreat-letters' );
			
			$this->add_admin_notice( $ambiguous_message, 'info' );
		}

		wp_redirect( admin_url( 'admin.php?page=dfx-retreats&action=attendants&retreat_id=' . $retreat_id ) );
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
			'name' => array(
				'en' => array( 'name', 'first name', 'nombre' ),
				'es' => array( 'nombre' ),
			),
			'surnames' => array(
				'en' => array( 'surnames', 'last name', 'surname', 'apellidos' ),
				'es' => array( 'apellidos' ),
			),
			'date_of_birth' => array(
				'en' => array( 'date of birth', 'birth date', 'dob', 'birthdate', 'fecha de nacimiento' ),
				'es' => array( 'fecha de nacimiento' ),
			),
			'emergency_contact_name' => array(
				'en' => array( 'emergency contact name', 'emergency name', 'contact name', 'nombre del contacto de emergencia' ),
				'es' => array( 'nombre del contacto de emergencia', 'contacto emergencia nombre' ),
			),
			'emergency_contact_surname' => array(
				'en' => array( 'emergency contact surname', 'emergency surname', 'contact surname', 'apellido del contacto de emergencia' ),
				'es' => array( 'apellido del contacto de emergencia', 'contacto emergencia apellido' ),
			),
			'emergency_contact_phone' => array(
				'en' => array( 'emergency contact phone', 'emergency phone', 'contact phone', 'phone', 'teléfono del contacto de emergencia' ),
				'es' => array( 'teléfono del contacto de emergencia', 'contacto emergencia teléfono', 'teléfono' ),
			),
		);

		// Normalize headers (lowercase, trim)
		$normalized_headers = array_map( function( $header ) {
			return strtolower( trim( $header ) );
		}, $headers );

		// Map each field
		foreach ( $field_mappings as $field => $mappings ) {
			$all_possible_names = array_merge( $mappings['en'], $mappings['es'] );
			
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
		$required_fields = array( 'name', 'surnames', 'date_of_birth', 'emergency_contact_name', 'emergency_contact_surname', 'emergency_contact_phone' );
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
			$preferred_format = get_option( 'dfx_retreat_letters_date_format', 'dmy' );
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
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'dfx_retreats_nonce' ) ) {
			wp_die( __( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
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

		$csv_data = $this->attendant_model->export_csv_data( $retreat_id );
		$filename = 'retreat-' . sanitize_file_name( $retreat->name ) . '-attendants-' . date( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );

		// Write BOM for UTF-8
		fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );

		// Write headers
		fputcsv( $output, $csv_data['headers'] );

		// Write data
		foreach ( $csv_data['rows'] as $row ) {
			fputcsv( $output, $row );
		}

		fclose( $output );
		exit;
	}

	/**
	 * AJAX handler for CSV export.
	 *
	 * @since 1.0.0
	 */
	public function ajax_export_attendants_csv() {
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'dfx_retreats_nonce' ) ) {
			wp_die( __( 'Security check failed.', 'dfx-parish-retreat-letters' ) );
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
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=add_attendant&retreat_id=' . $retreat->id ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New Attendant', 'dfx-parish-retreat-letters' ); ?>
			</a>
			<hr class="wp-header-end">

			<!-- Breadcrumb -->
			<p class="description">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats' ) ); ?>"><?php esc_html_e( 'Retreats', 'dfx-parish-retreat-letters' ); ?></a>
				&gt; <?php echo esc_html( $retreat->name ); ?>
				&gt; <?php esc_html_e( 'Attendants', 'dfx-parish-retreat-letters' ); ?>
			</p>

			<?php $this->display_admin_notices(); ?>

			<form method="get" action="">
				<input type="hidden" name="page" value="dfx-retreats">
				<input type="hidden" name="action" value="attendants">
				<input type="hidden" name="retreat_id" value="<?php echo esc_attr( $retreat->id ); ?>">
				<p class="search-box">
					<label class="screen-reader-text" for="attendant-search-input"><?php esc_html_e( 'Search Attendants:', 'dfx-parish-retreat-letters' ); ?></label>
					<input type="search" id="attendant-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
					<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Attendants', 'dfx-parish-retreat-letters' ); ?>">
				</p>
			</form>

			<form method="post" action="">
				<?php wp_nonce_field( 'dfx_attendants_action' ); ?>
				<div class="tablenav top">
					<div class="alignleft actions">
						<p><?php printf( esc_html__( 'Total attendants: %d', 'dfx-parish-retreat-letters' ), $total_items ); ?></p>
						<button type="submit" name="action" value="export_csv" class="button">
							<?php esc_html_e( 'Export CSV', 'dfx-parish-retreat-letters' ); ?>
						</button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=import_attendants&retreat_id=' . $retreat->id ) ); ?>" class="button">
							<?php esc_html_e( 'Import CSV', 'dfx-parish-retreat-letters' ); ?>
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
							<th scope="col" class="manage-column"><?php esc_html_e( 'Actions', 'dfx-parish-retreat-letters' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $attendants ) ) : ?>
							<?php foreach ( $attendants as $attendant ) : ?>
								<tr>
									<td>
										<strong>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=edit_attendant&retreat_id=' . $retreat->id . '&attendant_id=' . $attendant->id ) ); ?>">
												<?php echo esc_html( $attendant->name ); ?>
											</a>
										</strong>
									</td>
									<td><?php echo esc_html( $attendant->surnames ); ?></td>
									<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $attendant->date_of_birth ) ) ); ?></td>
									<td>
										<?php echo esc_html( $attendant->emergency_contact_name . ' ' . $attendant->emergency_contact_surname ); ?><br>
										<small><?php echo esc_html( $attendant->emergency_contact_phone ); ?></small>
									</td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=edit_attendant&retreat_id=' . $retreat->id . '&attendant_id=' . $attendant->id ) ); ?>" class="button button-small">
											<?php esc_html_e( 'Edit', 'dfx-parish-retreat-letters' ); ?>
										</a>
										<button type="button" class="button button-small button-link-delete dfx-delete-attendant" data-attendant-id="<?php echo esc_attr( $attendant->id ); ?>">
											<?php esc_html_e( 'Delete', 'dfx-parish-retreat-letters' ); ?>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="5">
									<?php if ( $search ) : ?>
										<?php esc_html_e( 'No attendants found for your search.', 'dfx-parish-retreat-letters' ); ?>
									<?php else : ?>
										<?php esc_html_e( 'No attendants found for this retreat.', 'dfx-parish-retreat-letters' ); ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=add_attendant&retreat_id=' . $retreat->id ) ); ?>">
											<?php esc_html_e( 'Add the first attendant', 'dfx-parish-retreat-letters' ); ?>
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</form>
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
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats' ) ); ?>"><?php esc_html_e( 'Retreats', 'dfx-parish-retreat-letters' ); ?></a>
				&gt; <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>"><?php echo esc_html( $retreat->name ); ?></a>
				&gt; <?php esc_html_e( 'Attendants', 'dfx-parish-retreat-letters' ); ?>
				&gt; <?php echo esc_html( $is_edit ? __( 'Edit', 'dfx-parish-retreat-letters' ) : __( 'Add New', 'dfx-parish-retreat-letters' ) ); ?>
			</p>

			<?php $this->display_admin_notices(); ?>

			<form method="post" action="">
				<?php wp_nonce_field( 'dfx_attendants_add_edit' ); ?>
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
								<label for="emergency_contact_surname"><?php esc_html_e( 'Emergency Contact Surname', 'dfx-parish-retreat-letters' ); ?> <span class="description">(<?php esc_html_e( 'required', 'dfx-parish-retreat-letters' ); ?>)</span></label>
							</th>
							<td>
								<input type="text" id="emergency_contact_surname" name="emergency_contact_surname" value="<?php echo esc_attr( $attendant->emergency_contact_surname ?? '' ); ?>" class="regular-text" required>
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
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo esc_attr( $is_edit ? __( 'Update Attendant', 'dfx-parish-retreat-letters' ) : __( 'Add Attendant', 'dfx-parish-retreat-letters' ) ); ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'dfx-parish-retreat-letters' ); ?>
					</a>
				</p>
			</form>
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
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats' ) ); ?>"><?php esc_html_e( 'Retreats', 'dfx-parish-retreat-letters' ); ?></a>
				&gt; <a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>"><?php echo esc_html( $retreat->name ); ?></a>
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
						<li><?php esc_html_e( 'Use dates where day > 12 (e.g., 15/01/2025 is clearly January 15th)', 'dfx-parish-retreat-letters' ); ?></li>
						<li><?php esc_html_e( 'Set your preferred date format below to ensure consistent interpretation', 'dfx-parish-retreat-letters' ); ?></li>
					</ul>
				</div>
			</div>

			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'dfx_attendants_import' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="date_format_preference"><?php esc_html_e( 'Date Format Preference', 'dfx-parish-retreat-letters' ); ?></label>
							</th>
							<td>
								<?php $current_preference = get_option( 'dfx_retreat_letters_date_format', 'dmy' ); ?>
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
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=dfx-retreats&action=attendants&retreat_id=' . $retreat->id ) ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'dfx-parish-retreat-letters' ); ?>
					</a>
				</p>
			</form>
		</div>
		<?php
	}
}