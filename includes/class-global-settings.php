<?php
/**
 * The global settings model class
 *
 * Handles CRUD operations for global plugin settings using WordPress options.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.6.0
 *
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 */

/**
 * The global settings model class.
 *
 * This class handles all CRUD operations for global plugin settings using WordPress options.
 * 
 * Since version 1.6.1, global settings are stored as WordPress options instead of a custom
 * database table. This provides better performance, built-in caching, and easier backup/restore.
 * All option names are prefixed with 'dfx_prl_global_' to avoid conflicts.
 *
 * @since      1.6.0
 * @package    DFX_Parish_Retreat_Letters
 * @subpackage DFX_Parish_Retreat_Letters/includes
 * @author     DaveFX
 */
class DFX_Parish_Retreat_Letters_GlobalSettings {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.6.0
	 * @var DFX_Parish_Retreat_Letters_GlobalSettings|null
	 */
	private static $instance = null;

	/**
	 * The option prefix for global settings.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	const OPTION_PREFIX = 'dfx_prl_global_';

	/**
	 * The option name for storing active setting keys.
	 *
	 * @since 1.6.1
	 * @var string
	 */
	const KEYS_OPTION_NAME = 'dfx_prl_global_keys_index';

	/**
	 * Get the single instance of the class.
	 *
	 * @since 1.6.0
	 * @return DFX_Parish_Retreat_Letters_GlobalSettings
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
	 * @since 1.6.0
	 */
	private function __construct() {
		// No database dependency needed - using WordPress options
	}

	/**
	 * Get a setting value by key.
	 *
	 * @since 1.6.0
	 * @param string $key The setting key.
	 * @param mixed  $default Default value if setting doesn't exist.
	 * @return mixed The setting value.
	 */
	public function get( $key, $default = null ) {
		$option_name = self::OPTION_PREFIX . $key;
		return get_option( $option_name, $default );
	}

	/**
	 * Set a setting value by key.
	 *
	 * @since 1.6.0
	 * @param string $key The setting key.
	 * @param mixed  $value The setting value.
	 * @return bool True on success, false on failure.
	 */
	public function set( $key, $value ) {
		$option_name = self::OPTION_PREFIX . $key;
		$result = update_option( $option_name, $value ) || get_option( $option_name ) == $value;
		
		// Maintain index of active keys
		if ( $result ) {
			$this->add_key_to_index( $key );
		}
		
		return $result;
	}

	/**
	 * Delete a setting by key.
	 *
	 * @since 1.6.0
	 * @param string $key The setting key.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $key ) {
		$option_name = self::OPTION_PREFIX . $key;
		$result = delete_option( $option_name );
		
		// Remove from index of active keys
		if ( $result ) {
			$this->remove_key_from_index( $key );
		}
		
		return $result;
	}

	/**
	 * Get all global settings as an associative array.
	 *
	 * Uses WordPress options API instead of direct database queries for better
	 * compliance with WordPress.org coding standards.
	 *
	 * @since 1.6.0
	 * @return array Array of setting_key => setting_value pairs.
	 */
	public function get_all() {
		// Get list of active setting keys
		$active_keys = get_option( self::KEYS_OPTION_NAME, array() );
		
		if ( empty( $active_keys ) ) {
			// If no keys index exists, build it from existing options
			$active_keys = $this->rebuild_keys_index();
		}
		
		$settings = array();
		foreach ( $active_keys as $key ) {
			$option_name = self::OPTION_PREFIX . $key;
			$value = get_option( $option_name );
			if ( false !== $value ) {
				$settings[ $key ] = $value;
			}
		}
		
		return $settings;
	}

	/**
	 * Get default header block ID.
	 *
	 * @since 1.6.0
	 * @return string|null
	 */
	public function get_default_header() {
		return $this->get( 'default_header_block_id' );
	}

	/**
	 * Set default header block ID.
	 *
	 * @since 1.6.0
	 * @param string|null $block_id The block ID.
	 * @return bool
	 */
	public function set_default_header( $block_id ) {
		return $this->set( 'default_header_block_id', $block_id );
	}

	/**
	 * Get default footer block ID.
	 *
	 * @since 1.6.0
	 * @return string|null
	 */
	public function get_default_footer() {
		return $this->get( 'default_footer_block_id' );
	}

	/**
	 * Set default footer block ID.
	 *
	 * @since 1.6.0
	 * @param string|null $block_id The block ID.
	 * @return bool
	 */
	public function set_default_footer( $block_id ) {
		return $this->set( 'default_footer_block_id', $block_id );
	}

	/**
	 * Get default CSS styles.
	 *
	 * @since 1.6.0
	 * @return string
	 */
	public function get_default_css() {
		return $this->get( 'default_css', '' );
	}

	/**
	 * Set default CSS styles.
	 *
	 * @since 1.6.0
	 * @param string $css The CSS styles.
	 * @return bool
	 */
	public function set_default_css( $css ) {
		return $this->set( 'default_css', $css );
	}

	/**
	 * Check if per-retreat customization is enabled.
	 *
	 * @since 1.6.0
	 * @return bool
	 */
	public function is_per_retreat_customization_enabled() {
		$value = $this->get( 'enable_per_retreat_customization', 'yes' );
		return $value === 'yes';
	}

	/**
	 * Enable or disable per-retreat customization.
	 *
	 * @since 1.6.0
	 * @param bool $enabled Whether to enable per-retreat customization.
	 * @return bool
	 */
	public function set_per_retreat_customization_enabled( $enabled ) {
		return $this->set( 'enable_per_retreat_customization', $enabled ? 'yes' : 'no' );
	}

	/**
	 * Add a key to the active keys index.
	 *
	 * @since 1.6.1
	 * @param string $key The setting key to add.
	 */
	private function add_key_to_index( $key ) {
		$active_keys = get_option( self::KEYS_OPTION_NAME, array() );
		if ( ! in_array( $key, $active_keys, true ) ) {
			$active_keys[] = $key;
			update_option( self::KEYS_OPTION_NAME, $active_keys );
		}
	}

	/**
	 * Remove a key from the active keys index.
	 *
	 * @since 1.6.1
	 * @param string $key The setting key to remove.
	 */
	private function remove_key_from_index( $key ) {
		$active_keys = get_option( self::KEYS_OPTION_NAME, array() );
		$updated_keys = array_diff( $active_keys, array( $key ) );
		
		if ( count( $updated_keys ) !== count( $active_keys ) ) {
			update_option( self::KEYS_OPTION_NAME, array_values( $updated_keys ) );
		}
	}

	/**
	 * Rebuild the keys index from existing options (fallback method).
	 *
	 * This method is used as a fallback when the keys index doesn't exist.
	 * It performs a single direct database query to rebuild the index, which is
	 * acceptable as a migration/recovery mechanism.
	 *
	 * @since 1.6.1
	 * @return array Array of active setting keys.
	 */
	private function rebuild_keys_index() {
		global $wpdb;
		
		// This is a one-time fallback query to rebuild the index
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		$prefix = self::OPTION_PREFIX;
		$options = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$prefix . '%'
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		
		$keys = array();
		if ( $options ) {
			foreach ( $options as $option_name ) {
				// Remove the prefix to get the setting key
				$key = str_replace( $prefix, '', $option_name );
				if ( ! empty( $key ) ) {
					$keys[] = $key;
				}
			}
		}
		
		// Save the rebuilt index
		update_option( self::KEYS_OPTION_NAME, $keys );
		
		return $keys;
	}
}