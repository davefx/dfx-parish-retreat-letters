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
										<button type="button" class="button button-small button-link-delete dfx-delete-retreat" data-retreat-id="<?php echo esc_attr( $retreat->id ); ?>">
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
				$this->add_admin_notice( __( 'Error creating attendant. Please check your data.', 'dfx-parish-retreat-letters' ), 'error' );
			}
		}
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
}