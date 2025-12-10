<?php
/**
 * The prefix migration utility class
 *
 * Handles migration of options and transients from old dfx/dfx_prl prefix to new dfxprl prefix.
 * This class is designed to run once automatically and will be removed in future versions.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      25.12.10
 *
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */

/**
 * The prefix migration utility class.
 *
 * This class handles automatic migration of options and transients from the old prefix
 * (dfx_parish_retreat_letters_, dfx_prl_) to the new prefix (dfxprl_).
 *
 * @since      25.12.10
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 * @author     DaveFX
 */
class DFXPRL_Prefix_Migration {

	/**
	 * The migration completion flag option name.
	 *
	 * @since 25.12.10
	 * @var string
	 */
	const MIGRATION_FLAG = 'dfxprl_prefix_migration_completed';

	/**
	 * Check if migration has already been completed.
	 *
	 * @since 25.12.10
	 * @return bool True if migration has been completed, false otherwise.
	 */
	public static function is_migration_completed() {
		return (bool) get_option( self::MIGRATION_FLAG, false );
	}

	/**
	 * Mark migration as completed.
	 *
	 * @since 25.12.10
	 * @return bool True on success, false on failure.
	 */
	private static function mark_migration_completed() {
		return update_option( self::MIGRATION_FLAG, true, false );
	}

	/**
	 * Run the migration process.
	 *
	 * This method migrates all options, transients, and database tables from the old prefix to the new prefix.
	 * It is idempotent and can be safely called multiple times.
	 *
	 * @since 25.12.10
	 * @return array Results of the migration process.
	 */
	public static function run_migration() {
		// Check if migration has already been completed
		if ( self::is_migration_completed() ) {
			return array(
				'status' => 'skipped',
				'message' => 'Migration already completed.',
			);
		}

		$results = array(
			'options_migrated' => 0,
			'transients_migrated' => 0,
			'tables_migrated' => 0,
			'errors' => array(),
		);

		global $wpdb;

		// Migrate database tables first
		$tables_to_migrate = array(
			'dfx_prl_retreats' => 'dfxprl_retreats',
			'dfx_prl_attendants' => 'dfxprl_attendants',
			'dfx_prl_confidential_messages' => 'dfxprl_confidential_messages',
			'dfx_prl_message_files' => 'dfxprl_message_files',
			'dfx_prl_message_print_log' => 'dfxprl_message_print_log',
			'dfx_prl_retreat_permissions' => 'dfxprl_retreat_permissions',
			'dfx_prl_retreat_invitations' => 'dfxprl_retreat_invitations',
			'dfx_prl_permission_audit_log' => 'dfxprl_permission_audit_log',
		);

		foreach ( $tables_to_migrate as $old_table => $new_table ) {
			$old_table_name = $wpdb->prefix . $old_table;
			$new_table_name = $wpdb->prefix . $new_table;

			// Check if old table exists
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for migration
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_table_name ) );

			if ( $table_exists ) {
				// Check if new table already exists
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for migration
				$new_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table_name ) );

				if ( ! $new_table_exists ) {
					// Rename the table
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Required for migration
					$renamed = $wpdb->query( "RENAME TABLE `{$old_table_name}` TO `{$new_table_name}`" );
					
					if ( false !== $renamed ) {
						$results['tables_migrated']++;
					} else {
						$results['errors'][] = "Failed to rename table: {$old_table_name}";
					}
				}
			}
		}

		// Migrate options
		$options_to_migrate = self::get_options_to_migrate();
		foreach ( $options_to_migrate as $old_name => $new_name ) {
			$value = get_option( $old_name );
			if ( $value !== false ) {
				// Add the new option
				if ( update_option( $new_name, $value, false ) ) {
					// Delete the old option
					delete_option( $old_name );
					$results['options_migrated']++;
				} else {
					$results['errors'][] = "Failed to migrate option: {$old_name}";
				}
			}
		}

		// Migrate transients (requires direct database access for pattern matching)
		// Get all transients with old prefix
		$old_transient_patterns = array(
			'_transient_dfx_prl_%',
			'_transient_timeout_dfx_prl_%',
		);

		foreach ( $old_transient_patterns as $pattern ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for migration
			$transients = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
					$pattern
				)
			);

			if ( $transients ) {
				foreach ( $transients as $transient ) {
					$old_name = $transient->option_name;
					$new_name = str_replace( 'dfx_prl_', 'dfxprl_', $old_name );
					
					// Insert new transient
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required for migration
					$inserted = $wpdb->insert(
						$wpdb->options,
						array(
							'option_name' => $new_name,
							'option_value' => $transient->option_value,
							'autoload' => 'no',
						),
						array( '%s', '%s', '%s' )
					);

					if ( $inserted ) {
						// Delete old transient
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for migration
						$wpdb->delete(
							$wpdb->options,
							array( 'option_name' => $old_name ),
							array( '%s' )
						);
						$results['transients_migrated']++;
					} else {
						$results['errors'][] = "Failed to migrate transient: {$old_name}";
					}
				}
			}
		}

		// Mark migration as completed
		self::mark_migration_completed();

		$results['status'] = 'completed';
		$results['message'] = sprintf(
			'Migration completed. Migrated %d tables, %d options and %d transients.',
			$results['tables_migrated'],
			$results['options_migrated'],
			$results['transients_migrated']
		);

		return $results;
	}

	/**
	 * Get the mapping of old option names to new option names.
	 *
	 * @since 25.12.10
	 * @return array Array of old option name => new option name pairs.
	 */
	private static function get_options_to_migrate() {
		global $wpdb;

		// Define prefix mappings
		$mappings = array();

		// Get all options with old prefixes
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for migration
		$old_options = $wpdb->get_col(
			"SELECT option_name FROM {$wpdb->options} 
			WHERE option_name LIKE 'dfx_parish_retreat_letters_%'
			OR option_name LIKE 'dfx_prl_%'
			AND option_name NOT LIKE '_transient_%'"
		);

		foreach ( $old_options as $old_name ) {
			// Replace both old prefixes with the new one
			$new_name = str_replace(
				array( 'dfx_parish_retreat_letters_', 'dfx_prl_' ),
				'dfxprl_',
				$old_name
			);
			
			// Special case: if the option name still has 'dfx_' at the start after replacement,
			// it means it had both patterns. Handle this correctly.
			if ( strpos( $old_name, 'dfx_parish_retreat_letters_' ) === 0 ) {
				$new_name = 'dfxprl_' . substr( $old_name, strlen( 'dfx_parish_retreat_letters_' ) );
			} elseif ( strpos( $old_name, 'dfx_prl_' ) === 0 ) {
				$new_name = 'dfxprl_' . substr( $old_name, strlen( 'dfx_prl_' ) );
			}

			$mappings[ $old_name ] = $new_name;
		}

		return $mappings;
	}
}
