<?php
/**
 * The global settings model class
 *
 * Handles CRUD operations for global plugin settings.
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
 * This class handles all CRUD operations for global plugin settings.
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
	 * The database instance.
	 *
	 * @since 1.6.0
	 * @var DFX_Parish_Retreat_Letters_Database
	 */
	private $database;

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
		$this->database = DFX_Parish_Retreat_Letters_Database::get_instance();
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
		global $wpdb;
		$table = $this->database->get_settings_table();
		
		$value = $wpdb->get_var( $wpdb->prepare(
			"SELECT setting_value FROM {$table} WHERE setting_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$key
		) );
		
		if ( $value === null ) {
			return $default;
		}
		
		// Try to unserialize if it's a serialized value
		$unserialized = maybe_unserialize( $value );
		return $unserialized !== false ? $unserialized : $value;
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
		global $wpdb;
		$table = $this->database->get_settings_table();
		
		// Serialize complex values
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = serialize( $value );
		}
		
		$result = $wpdb->replace(
			$table,
			array(
				'setting_key'   => $key,
				'setting_value' => $value,
			),
			array( '%s', '%s' )
		);
		
		return $result !== false;
	}

	/**
	 * Delete a setting by key.
	 *
	 * @since 1.6.0
	 * @param string $key The setting key.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $key ) {
		global $wpdb;
		$table = $this->database->get_settings_table();
		
		$result = $wpdb->delete(
			$table,
			array( 'setting_key' => $key ),
			array( '%s' )
		);
		
		return $result !== false;
	}

	/**
	 * Get all settings as an associative array.
	 *
	 * @since 1.6.0
	 * @return array Array of setting_key => setting_value pairs.
	 */
	public function get_all() {
		global $wpdb;
		$table = $this->database->get_settings_table();
		
		$results = $wpdb->get_results(
			"SELECT setting_key, setting_value FROM {$table}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		
		if ( ! $results ) {
			return array();
		}
		
		$settings = array();
		foreach ( $results as $row ) {
			$value = maybe_unserialize( $row['setting_value'] );
			$settings[ $row['setting_key'] ] = $value !== false ? $value : $row['setting_value'];
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
		return (bool) $this->get( 'enable_per_retreat_customization', true );
	}

	/**
	 * Enable or disable per-retreat customization.
	 *
	 * @since 1.6.0
	 * @param bool $enabled Whether to enable per-retreat customization.
	 * @return bool
	 */
	public function set_per_retreat_customization_enabled( $enabled ) {
		return $this->set( 'enable_per_retreat_customization', (bool) $enabled );
	}
}