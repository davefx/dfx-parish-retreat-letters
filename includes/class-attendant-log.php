<?php
/**
 * The attendant contact log model class
 *
 * Handles the contact log ("bitácora") that managers keep for each attendant.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.11.0
 *
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */

/**
 * The attendant contact log model class.
 *
 * Every entry records a date, the user who wrote it and a free-text description.
 * Changes are never destructive: each creation, edit and deletion stores a
 * revision so retreat managers can review the full history.
 *
 * Rules:
 * - Retreat and message managers can add entries.
 * - Authors can edit their own entries during EDIT_WINDOW seconds after creating them,
 *   and delete them at any time.
 * - Retreat managers can edit and delete any entry at any time, and see the history.
 *
 * @since      1.11.0
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 * @author     DaveFX
 */
class DFXPRL_Attendant_Log {

	/**
	 * How long (in seconds) authors can edit their own entries.
	 *
	 * @since 1.11.0
	 * @var int
	 */
	const EDIT_WINDOW = 900; // 15 minutes

	/**
	 * The database instance.
	 *
	 * @since 1.11.0
	 * @var DFXPRL_Database
	 */
	private $database;

	/**
	 * The permissions instance.
	 *
	 * @since 1.11.0
	 * @var DFXPRL_Permissions
	 */
	private $permissions;

	/**
	 * Constructor.
	 *
	 * @since 1.11.0
	 */
	public function __construct() {
		$this->database = DFXPRL_Database::get_instance();
		$this->permissions = DFXPRL_Permissions::get_instance();
	}

	/**
	 * Add a new entry.
	 *
	 * @since 1.11.0
	 * @param int    $attendant_id Attendant ID.
	 * @param string $entry_date   Date of the contact (Y-m-d).
	 * @param string $content      Description.
	 * @param int    $user_id      Author.
	 * @return int|false New entry ID or false on failure.
	 */
	public function add( $attendant_id, $entry_date, $content, $user_id ) {
		global $wpdb;

		$now = current_time( 'mysql', true );
		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->database->get_attendant_log_table(),
			array(
				'attendant_id' => $attendant_id,
				'user_id'      => $user_id,
				'entry_date'   => $entry_date,
				'content'      => $content,
				'created_at'   => $now,
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);

		if ( ! $result ) {
			return false;
		}

		$entry_id = (int) $wpdb->insert_id;
		$this->add_revision( $entry_id, 'created', $entry_date, $content, $user_id, $now );

		return $entry_id;
	}

	/**
	 * Edit an entry.
	 *
	 * @since 1.11.0
	 * @param int    $entry_id   Entry ID.
	 * @param string $entry_date Date of the contact (Y-m-d).
	 * @param string $content    Description.
	 * @param int    $user_id    User making the change.
	 * @return bool
	 */
	public function update( $entry_id, $entry_date, $content, $user_id ) {
		global $wpdb;

		$now = current_time( 'mysql', true );
		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->database->get_attendant_log_table(),
			array(
				'entry_date' => $entry_date,
				'content'    => $content,
				'updated_at' => $now,
				'updated_by' => $user_id,
			),
			array( 'id' => $entry_id ),
			array( '%s', '%s', '%s', '%d' ),
			array( '%d' )
		);

		if ( false === $result ) {
			return false;
		}

		$this->add_revision( $entry_id, 'edited', $entry_date, $content, $user_id, $now );
		return true;
	}

	/**
	 * Delete an entry. The entry is kept (hidden) so its history remains available.
	 *
	 * @since 1.11.0
	 * @param int $entry_id Entry ID.
	 * @param int $user_id  User deleting the entry.
	 * @return bool
	 */
	public function delete( $entry_id, $user_id ) {
		global $wpdb;

		$entry = $this->get( $entry_id );
		if ( ! $entry ) {
			return false;
		}

		$now = current_time( 'mysql', true );
		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$this->database->get_attendant_log_table(),
			array(
				'deleted_at' => $now,
				'deleted_by' => $user_id,
			),
			array( 'id' => $entry_id ),
			array( '%s', '%d' ),
			array( '%d' )
		);

		if ( false === $result ) {
			return false;
		}

		$this->add_revision( $entry_id, 'deleted', $entry->entry_date, $entry->content, $user_id, $now );
		return true;
	}

	/**
	 * Get an entry by ID.
	 *
	 * @since 1.11.0
	 * @param int $entry_id Entry ID.
	 * @return object|null
	 */
	public function get( $entry_id ) {
		global $wpdb;

		$table = $this->database->get_attendant_log_table();
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$entry_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get the entries of an attendant, most recent contact first.
	 *
	 * @since 1.11.0
	 * @param int  $attendant_id    Attendant ID.
	 * @param bool $include_deleted Whether to include deleted entries.
	 * @return array
	 */
	public function get_by_attendant( $attendant_id, $include_deleted = false ) {
		global $wpdb;

		$table = $this->database->get_attendant_log_table();
		$deleted_clause = $include_deleted ? '' : 'AND deleted_at IS NULL';

		$entries = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE attendant_id = %d {$deleted_clause} ORDER BY entry_date DESC, created_at DESC, id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$attendant_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return $entries ? $entries : array();
	}

	/**
	 * Get the revisions of several entries, oldest first.
	 *
	 * @since 1.11.0
	 * @param array $entry_ids Entry IDs.
	 * @return array entry_id => array of revisions.
	 */
	public function get_revisions_for_entries( $entry_ids ) {
		global $wpdb;

		$entry_ids = array_filter( array_map( 'absint', (array) $entry_ids ) );
		if ( empty( $entry_ids ) ) {
			return array();
		}

		$table = $this->database->get_attendant_log_revisions_table();
		$placeholders = implode( ', ', array_fill( 0, count( $entry_ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE log_id IN ($placeholders) ORDER BY changed_at ASC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$entry_ids
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		$revisions = array();
		foreach ( (array) $rows as $row ) {
			$revisions[ (int) $row->log_id ][] = $row;
		}

		return $revisions;
	}

	/**
	 * Permanently remove all entries (and revisions) of the given attendants.
	 * Used when attendants are deleted.
	 *
	 * @since 1.11.0
	 * @param array $attendant_ids Attendant IDs.
	 */
	public function delete_by_attendants( $attendant_ids ) {
		global $wpdb;

		$attendant_ids = array_filter( array_map( 'absint', (array) $attendant_ids ) );
		if ( empty( $attendant_ids ) ) {
			return;
		}

		$log_table = $this->database->get_attendant_log_table();
		$revisions_table = $this->database->get_attendant_log_revisions_table();
		$placeholders = implode( ', ', array_fill( 0, count( $attendant_ids ), '%d' ) );

		$wpdb->query( $wpdb->prepare( "DELETE r FROM {$revisions_table} r INNER JOIN {$log_table} l ON r.log_id = l.id WHERE l.attendant_id IN ($placeholders)", $attendant_ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$log_table} WHERE attendant_id IN ($placeholders)", $attendant_ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Check whether the current user can edit an entry.
	 *
	 * @since 1.11.0
	 * @param object $entry      Entry object.
	 * @param int    $retreat_id Retreat the entry's attendant belongs to.
	 * @return bool
	 */
	public function current_user_can_edit( $entry, $retreat_id ) {
		if ( ! empty( $entry->deleted_at ) ) {
			return false;
		}
		if ( $this->permissions->current_user_can_manage_retreat( $retreat_id ) ) {
			return true;
		}
		return $this->is_own_entry( $entry, $retreat_id ) && $this->get_edit_seconds_left( $entry ) > 0;
	}

	/**
	 * Check whether the current user can delete an entry.
	 *
	 * @since 1.11.0
	 * @param object $entry      Entry object.
	 * @param int    $retreat_id Retreat the entry's attendant belongs to.
	 * @return bool
	 */
	public function current_user_can_delete( $entry, $retreat_id ) {
		if ( ! empty( $entry->deleted_at ) ) {
			return false;
		}
		if ( $this->permissions->current_user_can_manage_retreat( $retreat_id ) ) {
			return true;
		}
		return $this->is_own_entry( $entry, $retreat_id );
	}

	/**
	 * Seconds left in the author's edit window (0 when expired).
	 *
	 * @since 1.11.0
	 * @param object $entry Entry object.
	 * @return int
	 */
	public function get_edit_seconds_left( $entry ) {
		$created = strtotime( $entry->created_at . ' UTC' );
		return max( 0, ( $created + self::EDIT_WINDOW ) - time() );
	}

	/**
	 * Check whether an entry was written by the current user, who still has message access.
	 *
	 * @since 1.11.0
	 * @param object $entry      Entry object.
	 * @param int    $retreat_id Retreat ID.
	 * @return bool
	 */
	private function is_own_entry( $entry, $retreat_id ) {
		return (int) $entry->user_id === get_current_user_id()
			&& $this->permissions->current_user_can_manage_messages( $retreat_id );
	}

	/**
	 * Store a revision.
	 *
	 * @since 1.11.0
	 * @param int    $entry_id   Entry ID.
	 * @param string $action     created|edited|deleted.
	 * @param string $entry_date Entry date at this point.
	 * @param string $content    Content at this point.
	 * @param int    $user_id    User making the change.
	 * @param string $changed_at UTC datetime.
	 */
	private function add_revision( $entry_id, $action, $entry_date, $content, $user_id, $changed_at ) {
		global $wpdb;

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$this->database->get_attendant_log_revisions_table(),
			array(
				'log_id'     => $entry_id,
				'action'     => $action,
				'entry_date' => $entry_date,
				'content'    => $content,
				'user_id'    => $user_id,
				'changed_at' => $changed_at,
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s' )
		);
	}
}
