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
		return update_option( $option_name, $value ) || get_option( $option_name ) == $value;
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
		return delete_option( $option_name );
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
}