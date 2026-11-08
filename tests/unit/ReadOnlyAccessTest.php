<?php
/**
 * Tests for the read-only access level of a retreat.
 *
 * @package DFXPRL
 * @subpackage Tests
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Read-only users can look at a retreat's attendants and contact log, but never change
 * anything, read or print letters, or export attendants.
 */
class ReadOnlyAccessTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$plugin_dir = dirname( __DIR__, 2 );
		require_once $plugin_dir . '/includes/class-permissions.php';

		Functions\when( '__' )->returnArg();
		Functions\when( 'get_current_user_id' )->justReturn( 7 );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Get the source of a file of the plugin.
	 *
	 * @param string $file Path relative to the plugin root.
	 * @return string
	 */
	private function get_source( $file ) {
		return file_get_contents( dirname( __DIR__, 2 ) . '/' . $file );
	}

	/**
	 * Get the source of a method of a class file.
	 *
	 * @param string $file   Path relative to the plugin root.
	 * @param string $method Method name.
	 * @return string
	 */
	private function get_method_source( $file, $method ) {
		$source = $this->get_source( $file );
		$start  = strpos( $source, 'function ' . $method . '(' );
		$this->assertNotFalse( $start, "Method $method should exist in $file" );

		$end = strpos( $source, "\n\t/**", $start );
		return substr( $source, $start, false === $end ? null : $end - $start );
	}

	/**
	 * Permissions object whose user holds the given levels on retreat 5.
	 *
	 * @param array $levels Permission levels of user 7 on retreat 5.
	 * @param bool  $admin  Whether the user is a plugin administrator.
	 * @return DFXPRL_Permissions
	 */
	private function permissions_with( array $levels, $admin = false ) {
		$permissions = $this->getMockBuilder( DFXPRL_Permissions::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'current_user_can_manage_plugin', 'user_has_retreat_permission' ) )
			->getMock();
		$permissions->method( 'current_user_can_manage_plugin' )->willReturn( $admin );
		$permissions->method( 'user_has_retreat_permission' )->willReturnCallback(
			function( $user_id, $retreat_id, $level ) use ( $levels ) {
				return 7 === $user_id && 5 === $retreat_id && in_array( $level, $levels, true );
			}
		);
		return $permissions;
	}

	public function test_read_only_is_a_grantable_level_with_its_own_name() {
		$this->assertSame( 'read_only', DFXPRL_Permissions::PERMISSION_READ_ONLY );
		$this->assertTrue( DFXPRL_Permissions::is_valid_permission_level( 'read_only' ) );
		$this->assertTrue( DFXPRL_Permissions::is_valid_permission_level( 'manager' ) );
		$this->assertTrue( DFXPRL_Permissions::is_valid_permission_level( 'message_manager' ) );
		$this->assertFalse( DFXPRL_Permissions::is_valid_permission_level( 'admin' ) );
		$this->assertFalse( DFXPRL_Permissions::is_valid_permission_level( '' ) );

		$this->assertSame( 'Read Only', DFXPRL_Permissions::get_permission_level_label( 'read_only' ) );
		$this->assertSame( 'Retreat Manager', DFXPRL_Permissions::get_permission_level_label( 'manager' ) );
		$this->assertSame( 'Message Manager', DFXPRL_Permissions::get_permission_level_label( 'message_manager' ) );
	}

	public function test_read_only_user_can_view_but_never_manage() {
		$permissions = $this->permissions_with( array( 'read_only' ) );

		$this->assertTrue( $permissions->current_user_can_view_retreat( 5 ) );
		$this->assertTrue( $permissions->current_user_is_read_only( 5 ) );
		$this->assertFalse( $permissions->current_user_can_manage_retreat( 5 ) );
		$this->assertFalse( $permissions->current_user_can_manage_messages( 5 ) );

		// Only on the retreat they were given
		$this->assertFalse( $permissions->current_user_can_view_retreat( 6 ) );
		$this->assertFalse( $permissions->current_user_is_read_only( 6 ) );
	}

	public function test_a_broader_permission_wins_over_read_only() {
		$message_manager = $this->permissions_with( array( 'read_only', 'message_manager' ) );
		$this->assertFalse( $message_manager->current_user_is_read_only( 5 ) );
		$this->assertTrue( $message_manager->current_user_can_manage_messages( 5 ) );

		$admin = $this->permissions_with( array(), true );
		$this->assertFalse( $admin->current_user_is_read_only( 5 ) );
		$this->assertTrue( $admin->current_user_can_view_retreat( 5 ) );
	}

	public function test_schema_accepts_the_read_only_level() {
		$schema = $this->get_method_source( 'includes/class-database.php', 'setup_tables' );
		$this->assertSame( 3, substr_count( $schema, "permission_level enum('manager','message_manager','read_only')" ), 'Permissions, invitations and audit log' );
		$this->assertStringContainsString( "const DB_VERSION = '1.16.0';", $this->get_source( 'includes/class-database.php' ) );
	}

	public function test_read_only_can_be_granted_and_invited() {
		foreach ( array( 'ajax_grant_permission', 'ajax_revoke_permission', 'ajax_send_invitation' ) as $method ) {
			$this->assertStringContainsString( 'DFXPRL_Permissions::is_valid_permission_level( $permission_level )', $this->get_method_source( 'includes/class-admin.php', $method ) );
		}
		$this->assertStringContainsString( 'DFXPRL_Permissions::is_valid_permission_level( $permission_level )', $this->get_method_source( 'includes/class-invitations.php', 'send_invitation' ) );
		$this->assertStringContainsString( '<option value="read_only">', $this->get_source( 'includes/class-admin.php' ) );
		$this->assertStringContainsString( '<option value="read_only">', $this->get_source( 'assets/js/admin-retreat-edit.js' ) );
	}

	public function test_letters_are_only_reachable_by_message_managers() {
		$page = $this->get_method_source( 'includes/class-admin.php', 'messages_list_page' );
		$this->assertStringContainsString( 'current_user_can_manage_messages( $attendant->retreat_id )', $page );

		$delete = $this->get_method_source( 'includes/class-admin.php', 'handle_message_list_actions' );
		$this->assertStringContainsString( 'current_user_can_manage_messages( $message->retreat_id )', $delete );

		foreach ( array( 'ajax_print_message', 'ajax_get_print_log' ) as $method ) {
			$this->assertStringContainsString( 'require_message_access( $message_id )', $this->get_method_source( 'includes/class-admin.php', $method ) );
		}
		$this->assertStringContainsString( 'current_user_can_manage_messages( $message->retreat_id )', $this->get_method_source( 'includes/class-admin.php', 'require_message_access' ) );
	}

	public function test_attendant_page_is_shown_without_anything_editable() {
		$page = $this->get_method_source( 'includes/class-admin.php', 'attendant_edit_page' );
		$this->assertStringContainsString( 'current_user_can_view_retreat( $retreat_id )', $page );

		$form = $this->get_method_source( 'includes/class-admin.php', 'render_attendant_add_edit_page' );
		$this->assertStringContainsString( '$read_only = $this->permissions->current_user_is_read_only( $retreat->id );', $form );
		$this->assertStringContainsString( 'name="physical_letters"<?php disabled( $read_only ); ?>', $form );
		$this->assertStringNotContainsString( '<?php if ( ! $read_only ) : ?>', $form, 'Read-only users see the message URL' );
		$this->assertMatchesRegularExpression( '/if \( \$read_only \) : \?>\s*<a href="[^"]*" class="button">\s*<\?php esc_html_e\( \'Back to Attendants\'/', $form, 'No submit button for read-only users' );

		// Saving still requires managing the retreat or its messages
		$save = $this->get_method_source( 'includes/class-admin.php', 'handle_attendant_add_edit_submission' );
		$this->assertStringContainsString( 'if ( ! $can_manage_retreat && ( ! $attendant_id || ! $this->permissions->current_user_can_manage_messages( $retreat_id ) ) ) {', $save );
	}

	public function test_contact_log_is_readable_but_not_writable() {
		$this->assertStringContainsString( 'get_log_request_attendant( absint( $_POST[\'attendant_id\'] ?? 0 ), true )', $this->get_method_source( 'includes/class-admin.php', 'ajax_log_get' ) );
		foreach ( array( 'ajax_log_add', 'ajax_log_update', 'ajax_log_delete' ) as $method ) {
			$source = $this->get_method_source( 'includes/class-admin.php', $method );
			$this->assertStringContainsString( 'get_log_request_attendant(', $source );
			$this->assertStringNotContainsString( ', true )', $source, "$method must require message access" );
		}

		$panel = $this->get_method_source( 'includes/class-admin.php', 'render_attendant_log_panel' );
		$this->assertStringContainsString( 'current_user_can_manage_messages( $retreat->id ) ) : ?>' . "\n\t\t\t" . '<div class="dfxprl-log-new">', $panel );
	}

	public function test_read_only_can_get_the_letters_url_and_invitation_without_replacing_it() {
		$generate = $this->get_method_source( 'includes/class-admin.php', 'ajax_generate_message_url' );
		$this->assertStringContainsString( 'current_user_can_view_retreat( $attendant->retreat_id )', $generate );
		// An existing URL is returned as is to read-only users, never replaced
		$guard = strpos( $generate, "! empty( \$attendant->message_url_token ) && \$this->permissions->current_user_is_read_only( \$attendant->retreat_id )" );
		$this->assertNotFalse( $guard );
		$this->assertLessThan( strpos( $generate, 'generate_unique_message_token()' ), $guard, 'The existing URL is returned before a new token is generated' );

		$invitation = $this->get_method_source( 'includes/class-admin.php', 'ajax_get_invitation_message' );
		$this->assertStringContainsString( 'current_user_can_view_retreat( $retreat_id )', $invitation );

		// The list buttons are shown to read-only users
		$this->assertStringContainsString( "<?php if ( \$this->permissions->current_user_can_view_retreat( \$retreat->id ) ) : ?>\n\t\t\t\t\t\t\t\t\t\t\t<?php if ( ! empty( \$retreat->message_request_template ) ) : ?>", $this->get_source( 'includes/class-admin.php' ) );
	}

	public function test_read_only_sees_message_manager_fields_and_edits_none() {
		$view = $this->get_method_source( 'includes/class-custom-field.php', 'current_user_can_view' );
		$this->assertStringContainsString( 'self::ACCESS_MESSAGE_MANAGER === $field->view_permission && DFXPRL_Permissions::get_instance()->current_user_can_view_retreat( $retreat_id )', $view );

		$access = $this->get_method_source( 'includes/class-custom-field.php', 'current_user_has_access' );
		$this->assertStringNotContainsString( 'view_retreat', $access, 'Editing never relies on read access' );
	}

	public function test_write_actions_never_rely_on_read_access() {
		$admin = $this->get_source( 'includes/class-admin.php' );
		preg_match_all( "/'wp_ajax_dfxprl_(\w+)', array\( \\\$this, '(\w+)' \)/", $admin, $matches );
		$this->assertNotEmpty( $matches[2] );

		// Handlers that read-only users may use: they only read, or (the letters URL) create it once when missing
		$read_handlers = array( 'ajax_log_get', 'ajax_generate_message_url', 'ajax_get_invitation_message' );
		foreach ( $matches[2] as $method ) {
			if ( in_array( $method, $read_handlers, true ) ) {
				continue;
			}
			$source = $this->get_method_source( 'includes/class-admin.php', $method );
			$this->assertStringNotContainsString( 'current_user_can_view_retreat', $source, "$method must not let read-only users through" );
			$this->assertStringNotContainsString( 'current_user_is_read_only', $source, "$method must not let read-only users through" );
		}
	}
}
