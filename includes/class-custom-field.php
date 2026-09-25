<?php
/**
 * The custom attendant field model class
 *
 * Handles per-retreat custom field definitions and their values for attendants.
 *
 * @link       https://github.com/davefx/dfx-parish-retreat-letters
 * @since      1.11.0
 *
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 */

/**
 * The custom attendant field model class.
 *
 * Each retreat can define its own free-form fields for attendants. A field has a
 * name, a slug (used for sorting, CSV matching and template placeholders), a type,
 * and permissions that decide who can view and edit it.
 *
 * @since      1.11.0
 * @package    DFXPRL
 * @subpackage DFXPRL/includes
 * @author     DaveFX
 */
class DFXPRL_Custom_Field {

	/**
	 * Only retreat managers (and plugin administrators).
	 *
	 * @since 1.11.0
	 * @var string
	 */
	const ACCESS_MANAGER = 'manager';

	/**
	 * Retreat managers and message managers.
	 *
	 * @since 1.11.0
	 * @var string
	 */
	const ACCESS_MESSAGE_MANAGER = 'message_manager';

	/**
	 * Slugs that cannot be used because they collide with built-in template placeholders.
	 *
	 * @since 1.11.0
	 * @var array
	 */
	const RESERVED_SLUGS = array(
		'id', 'retreat_id', 'name', 'surnames', 'attendant_name', 'attendant_surnames', 'date_of_birth',
		'emergency_contact_name', 'emergency_contact_surname', 'emergency_contact_phone',
		'emergency_contact_email', 'emergency_contact_relationship', 'invited_by', 'incompatibilities',
		'notes', 'internal_notes', 'messages_url', 'message_url_token', 'physical_letters',
		'total_letters', 'message_count', 'non_printed_count', 'retreat_name', 'retreat_location',
		'retreat_start_date', 'retreat_end_date', 'created_at', 'updated_at',
	);

	/**
	 * The database instance.
	 *
	 * @since 1.11.0
	 * @var DFXPRL_Database
	 */
	private $database;

	/**
	 * Per-request cache of field definitions, keyed by retreat ID.
	 *
	 * @since 1.11.0
	 * @var array
	 */
	private static $cache = array();

	/**
	 * Constructor.
	 *
	 * @since 1.11.0
	 */
	public function __construct() {
		$this->database = DFXPRL_Database::get_instance();
	}

	/**
	 * Get the available field types with their labels.
	 *
	 * @since 1.11.0
	 * @return array Type => label.
	 */
	public static function get_types() {
		return array(
			'text'     => __( 'Text', 'dfx-parish-retreat-letters' ),
			'textarea' => __( 'Long text', 'dfx-parish-retreat-letters' ),
			'number'   => __( 'Number', 'dfx-parish-retreat-letters' ),
			'date'     => __( 'Date', 'dfx-parish-retreat-letters' ),
			'checkbox' => __( 'Yes/No', 'dfx-parish-retreat-letters' ),
			'select'   => __( 'Options list', 'dfx-parish-retreat-letters' ),
		);
	}

	/**
	 * Get the custom fields defined for a retreat, in display order.
	 *
	 * @since 1.11.0
	 * @param int $retreat_id Retreat ID.
	 * @return array Array of field objects (with `options` decoded into an array).
	 */
	public function get_by_retreat( $retreat_id ) {
		global $wpdb;

		$retreat_id = absint( $retreat_id );
		if ( isset( self::$cache[ $retreat_id ] ) ) {
			return self::$cache[ $retreat_id ];
		}

		$table = $this->database->get_custom_fields_table();
		$fields = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE retreat_id = %d ORDER BY sort_order ASC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$retreat_id
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$fields = $fields ? $fields : array();
		foreach ( $fields as $field ) {
			$this->hydrate( $field );
		}

		self::$cache[ $retreat_id ] = $fields;
		return $fields;
	}

	/**
	 * Get a retreat's custom field by slug.
	 *
	 * @since 1.11.0
	 * @param int    $retreat_id Retreat ID.
	 * @param string $slug       Field slug.
	 * @return object|null
	 */
	public function get_by_slug( $retreat_id, $slug ) {
		foreach ( $this->get_by_retreat( $retreat_id ) as $field ) {
			if ( $field->slug === $slug ) {
				return $field;
			}
		}
		return null;
	}

	/**
	 * Save the full set of field definitions for a retreat.
	 *
	 * Rows with an `id` update that field, rows without one create a new field and
	 * rows flagged with `delete` remove the field together with all its values.
	 *
	 * @since 1.11.0
	 * @param int   $retreat_id  Retreat ID.
	 * @param array $definitions Raw field definitions, in display order.
	 * @return array List of error messages (empty on full success).
	 */
	public function save_definitions( $retreat_id, $definitions ) {
		global $wpdb;

		$retreat_id = absint( $retreat_id );
		$table = $this->database->get_custom_fields_table();
		$errors = array();

		$existing = array();
		foreach ( $this->get_by_retreat( $retreat_id ) as $field ) {
			$existing[ (int) $field->id ] = $field;
		}

		// Process deletions first so their slugs become available again
		foreach ( $definitions as $definition ) {
			$id = absint( $definition['id'] ?? 0 );
			if ( $id && ! empty( $definition['delete'] ) && isset( $existing[ $id ] ) ) {
				$this->delete_field( $id );
				unset( $existing[ $id ] );
			}
		}

		$used_slugs = array();
		foreach ( $existing as $field ) {
			$used_slugs[ $field->slug ] = (int) $field->id;
		}

		$position = 0;
		foreach ( $definitions as $definition ) {
			$id = absint( $definition['id'] ?? 0 );
			if ( ! empty( $definition['delete'] ) ) {
				continue;
			}
			if ( $id && ! isset( $existing[ $id ] ) ) {
				continue;
			}

			$data = $this->sanitize_definition( $definition );

			if ( '' === $data['name'] ) {
				if ( ! $id ) {
					// Ignore blank new rows
					continue;
				}
				$errors[] = __( 'Custom field names cannot be empty.', 'dfx-parish-retreat-letters' );
				continue;
			}

			if ( '' === $data['slug'] ) {
				$data['slug'] = $this->slugify( $data['name'] );
			}
			if ( '' === $data['slug'] ) {
				$data['slug'] = 'field';
			}

			if ( in_array( $data['slug'], self::RESERVED_SLUGS, true ) ) {
				$errors[] = sprintf(
					/* translators: %s: custom field slug */
					__( 'The slug "%s" is reserved. Please choose a different one.', 'dfx-parish-retreat-letters' ),
					$data['slug']
				);
				continue;
			}

			if ( isset( $used_slugs[ $data['slug'] ] ) && $used_slugs[ $data['slug'] ] !== $id ) {
				$errors[] = sprintf(
					/* translators: %s: custom field slug */
					__( 'The slug "%s" is already used by another field of this retreat.', 'dfx-parish-retreat-letters' ),
					$data['slug']
				);
				continue;
			}

			if ( 'select' === $data['field_type'] && empty( $data['options'] ) ) {
				$errors[] = sprintf(
					/* translators: %s: custom field name */
					__( 'The options list field "%s" needs at least one option.', 'dfx-parish-retreat-letters' ),
					$data['name']
				);
				continue;
			}

			$row = array(
				'retreat_id'      => $retreat_id,
				'name'            => $data['name'],
				'slug'            => $data['slug'],
				'field_type'      => $data['field_type'],
				'options'         => wp_json_encode( $data['options'] ),
				'show_in_list'    => $data['show_in_list'],
				'sortable'        => $data['sortable'],
				'view_permission' => $data['view_permission'],
				'edit_permission' => $data['edit_permission'],
				'importable'      => $data['importable'],
				'exportable'      => $data['exportable'],
				'sort_order'      => $position,
			);
			$formats = array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%d' );

			if ( $id ) {
				$result = $wpdb->update( $table, $row, array( 'id' => $id ), $formats, array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			} else {
				$result = $wpdb->insert( $table, $row, $formats ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$id = $result ? (int) $wpdb->insert_id : 0;
			}

			if ( false === $result ) {
				$errors[] = sprintf(
					/* translators: %s: custom field name */
					__( 'Error saving the custom field "%s".', 'dfx-parish-retreat-letters' ),
					$data['name']
				);
				continue;
			}

			$used_slugs[ $data['slug'] ] = $id;
			$position++;
		}

		unset( self::$cache[ $retreat_id ] );

		return $errors;
	}

	/**
	 * Delete a field definition and all its values.
	 *
	 * @since 1.11.0
	 * @param int $field_id Field ID.
	 * @return bool
	 */
	public function delete_field( $field_id ) {
		global $wpdb;

		$wpdb->delete( $this->database->get_custom_field_values_table(), array( 'field_id' => $field_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $this->database->get_custom_fields_table(), array( 'id' => $field_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		self::$cache = array();

		return false !== $result;
	}

	/**
	 * Delete all field definitions (and values) of a retreat.
	 *
	 * @since 1.11.0
	 * @param int $retreat_id Retreat ID.
	 */
	public function delete_by_retreat( $retreat_id ) {
		foreach ( $this->get_by_retreat( $retreat_id ) as $field ) {
			$this->delete_field( $field->id );
		}
	}

	/**
	 * Delete all custom field values of the given attendants.
	 *
	 * @since 1.11.0
	 * @param array $attendant_ids Attendant IDs.
	 */
	public function delete_values_by_attendants( $attendant_ids ) {
		global $wpdb;

		$attendant_ids = array_filter( array_map( 'absint', (array) $attendant_ids ) );
		if ( empty( $attendant_ids ) ) {
			return;
		}

		$table = $this->database->get_custom_field_values_table();
		$placeholders = implode( ', ', array_fill( 0, count( $attendant_ids ), '%d' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE attendant_id IN ($placeholders)", $attendant_ids ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Get the custom field values of one attendant.
	 *
	 * @since 1.11.0
	 * @param int $attendant_id Attendant ID.
	 * @return array field_id => value.
	 */
	public function get_values( $attendant_id ) {
		$values = $this->get_values_for_attendants( array( $attendant_id ) );
		return $values[ (int) $attendant_id ] ?? array();
	}

	/**
	 * Get the custom field values of several attendants in a single query.
	 *
	 * @since 1.11.0
	 * @param array $attendant_ids Attendant IDs.
	 * @return array attendant_id => ( field_id => value ).
	 */
	public function get_values_for_attendants( $attendant_ids ) {
		global $wpdb;

		$attendant_ids = array_filter( array_map( 'absint', (array) $attendant_ids ) );
		if ( empty( $attendant_ids ) ) {
			return array();
		}

		$table = $this->database->get_custom_field_values_table();
		$placeholders = implode( ', ', array_fill( 0, count( $attendant_ids ), '%d' ) );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT field_id, attendant_id, value FROM {$table} WHERE attendant_id IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$attendant_ids
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		$values = array();
		foreach ( (array) $rows as $row ) {
			$values[ (int) $row->attendant_id ][ (int) $row->field_id ] = $row->value;
		}

		return $values;
	}

	/**
	 * Store (or clear, when empty) a field value for an attendant.
	 *
	 * The value must already be normalized with normalize_value().
	 *
	 * @since 1.11.0
	 * @param int    $field_id     Field ID.
	 * @param int    $attendant_id Attendant ID.
	 * @param string $value        Normalized value.
	 * @return bool
	 */
	public function set_value( $field_id, $attendant_id, $value ) {
		global $wpdb;

		$table = $this->database->get_custom_field_values_table();

		if ( '' === $value || null === $value ) {
			$result = $wpdb->delete( $table, array( 'field_id' => $field_id, 'attendant_id' => $attendant_id ), array( '%d', '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			return false !== $result;
		}

		$result = $wpdb->query( $wpdb->prepare(
			"INSERT INTO {$table} (field_id, attendant_id, value) VALUES (%d, %d, %s) ON DUPLICATE KEY UPDATE value = VALUES(value)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$field_id,
			$attendant_id,
			$value
		) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return false !== $result;
	}

	/**
	 * Normalize a raw value for a field.
	 *
	 * @since 1.11.0
	 * @param object $field Field object.
	 * @param mixed  $raw   Raw value (form input or CSV cell).
	 * @return string|WP_Error Normalized value ('' means empty), or WP_Error when invalid.
	 */
	public function normalize_value( $field, $raw ) {
		$raw = is_scalar( $raw ) ? trim( (string) $raw ) : '';

		switch ( $field->field_type ) {
			case 'checkbox':
				$truthy = array( '1', 'yes', 'y', 'true', 'x', 'si', 'on', strtolower( remove_accents( __( 'Yes', 'dfx-parish-retreat-letters' ) ) ) );
				return in_array( strtolower( remove_accents( $raw ) ), $truthy, true ) ? '1' : '';

			case 'number':
				if ( '' === $raw ) {
					return '';
				}
				$number = str_replace( ',', '.', $raw );
				if ( ! is_numeric( $number ) ) {
					return $this->invalid_value_error( $field, $raw );
				}
				return (string) ( $number + 0 );

			case 'date':
				if ( '' === $raw ) {
					return '';
				}
				$date = DateTime::createFromFormat( '!Y-m-d', $raw );
				$date_errors = DateTime::getLastErrors();
				if ( ! $date || ( is_array( $date_errors ) && ( $date_errors['warning_count'] > 0 || $date_errors['error_count'] > 0 ) ) ) {
					return $this->invalid_value_error( $field, $raw );
				}
				return $date->format( 'Y-m-d' );

			case 'select':
				if ( '' === $raw ) {
					return '';
				}
				foreach ( $field->options as $option ) {
					if ( 0 === strcasecmp( $option, $raw ) ) {
						return $option;
					}
				}
				return $this->invalid_value_error( $field, $raw );

			case 'textarea':
				return sanitize_textarea_field( $raw );

			default:
				return sanitize_text_field( $raw );
		}
	}

	/**
	 * Format a stored value for display (list, templates, CSV export).
	 *
	 * @since 1.11.0
	 * @param object $field Field object.
	 * @param string $value Stored value.
	 * @return string
	 */
	public function format_value( $field, $value ) {
		$value = (string) $value;

		switch ( $field->field_type ) {
			case 'checkbox':
				return '1' === $value ? __( 'Yes', 'dfx-parish-retreat-letters' ) : __( 'No', 'dfx-parish-retreat-letters' );

			case 'date':
				return '' !== $value ? date_i18n( get_option( 'date_format' ), strtotime( $value ) ) : '';

			default:
				return $value;
		}
	}

	/**
	 * Get the SQL expression used to sort by a field's value column.
	 *
	 * @since 1.11.0
	 * @param object $field  Field object.
	 * @param string $column Qualified value column (e.g. "cfv.value").
	 * @return string
	 */
	public function get_sort_expression( $field, $column ) {
		global $wpdb;

		switch ( $field->field_type ) {
			case 'number':
			case 'checkbox':
				return "CAST({$column} AS DECIMAL(20,6))";
			case 'select':
				// Follow the order in which the options were defined
				if ( ! empty( $field->options ) ) {
					$placeholders = implode( ', ', array_fill( 0, count( $field->options ), '%s' ) );
					return $wpdb->prepare( "FIELD({$column}, {$placeholders})", $field->options ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				}
				return $column;
			default:
				return $column;
		}
	}

	/**
	 * Check whether the current user can view a field on the given retreat.
	 *
	 * @since 1.11.0
	 * @param object $field      Field object.
	 * @param int    $retreat_id Retreat ID.
	 * @return bool
	 */
	public function current_user_can_view( $field, $retreat_id ) {
		return $this->current_user_has_access( $field->view_permission, $retreat_id );
	}

	/**
	 * Check whether the current user can edit a field on the given retreat.
	 *
	 * @since 1.11.0
	 * @param object $field      Field object.
	 * @param int    $retreat_id Retreat ID.
	 * @return bool
	 */
	public function current_user_can_edit( $field, $retreat_id ) {
		return $this->current_user_can_view( $field, $retreat_id )
			&& $this->current_user_has_access( $field->edit_permission, $retreat_id );
	}

	/**
	 * Get the fields of a retreat the current user can view.
	 *
	 * @since 1.11.0
	 * @param int $retreat_id Retreat ID.
	 * @return array
	 */
	public function get_viewable_by_retreat( $retreat_id ) {
		return array_values( array_filter( $this->get_by_retreat( $retreat_id ), function( $field ) use ( $retreat_id ) {
			return $this->current_user_can_view( $field, $retreat_id );
		} ) );
	}

	/**
	 * Check an access level against the current user's permissions.
	 *
	 * @since 1.11.0
	 * @param string $access     Access level (manager|message_manager).
	 * @param int    $retreat_id Retreat ID.
	 * @return bool
	 */
	private function current_user_has_access( $access, $retreat_id ) {
		$permissions = DFXPRL_Permissions::get_instance();

		if ( self::ACCESS_MESSAGE_MANAGER === $access ) {
			return $permissions->current_user_can_manage_messages( $retreat_id );
		}

		return $permissions->current_user_can_manage_retreat( $retreat_id );
	}

	/**
	 * Sanitize a raw field definition.
	 *
	 * @since 1.11.0
	 * @param array $definition Raw definition.
	 * @return array
	 */
	private function sanitize_definition( $definition ) {
		$types = array_keys( self::get_types() );
		$access_levels = array( self::ACCESS_MANAGER, self::ACCESS_MESSAGE_MANAGER );

		$type = sanitize_key( $definition['field_type'] ?? 'text' );
		$view = sanitize_key( $definition['view_permission'] ?? self::ACCESS_MESSAGE_MANAGER );
		$edit = sanitize_key( $definition['edit_permission'] ?? self::ACCESS_MANAGER );

		if ( ! in_array( $view, $access_levels, true ) ) {
			$view = self::ACCESS_MESSAGE_MANAGER;
		}
		if ( ! in_array( $edit, $access_levels, true ) ) {
			$edit = self::ACCESS_MANAGER;
		}
		// Editing can never be broader than viewing
		if ( self::ACCESS_MANAGER === $view ) {
			$edit = self::ACCESS_MANAGER;
		}

		$options = array();
		if ( 'select' === $type ) {
			$lines = preg_split( '/\r\n|\r|\n/', (string) ( $definition['options'] ?? '' ) );
			foreach ( $lines as $line ) {
				$line = sanitize_text_field( $line );
				if ( '' !== $line && ! in_array( $line, $options, true ) ) {
					$options[] = $line;
				}
			}
		}

		$show_in_list = empty( $definition['show_in_list'] ) ? 0 : 1;

		return array(
			'name'            => sanitize_text_field( $definition['name'] ?? '' ),
			'slug'            => $this->slugify( $definition['slug'] ?? '' ),
			'field_type'      => in_array( $type, $types, true ) ? $type : 'text',
			'options'         => $options,
			'show_in_list'    => $show_in_list,
			// Sorting happens from the list columns, so it needs the field to be shown there
			'sortable'        => ( $show_in_list && ! empty( $definition['sortable'] ) ) ? 1 : 0,
			'view_permission' => $view,
			'edit_permission' => $edit,
			'importable'      => empty( $definition['importable'] ) ? 0 : 1,
			'exportable'      => empty( $definition['exportable'] ) ? 0 : 1,
		);
	}

	/**
	 * Turn a string into a valid field slug (lowercase letters, digits and underscores).
	 *
	 * @since 1.11.0
	 * @param string $text Source text.
	 * @return string
	 */
	public function slugify( $text ) {
		$slug = strtolower( remove_accents( (string) $text ) );
		$slug = preg_replace( '/[^a-z0-9]+/', '_', $slug );
		return substr( trim( $slug, '_' ), 0, 64 );
	}

	/**
	 * Decode stored columns into their runtime types.
	 *
	 * @since 1.11.0
	 * @param object $field Raw field row.
	 */
	private function hydrate( $field ) {
		$options = json_decode( (string) $field->options, true );
		$field->options = is_array( $options ) ? $options : array();
		$field->id = (int) $field->id;
		$field->show_in_list = (int) $field->show_in_list;
		$field->sortable = (int) $field->sortable;
		$field->importable = (int) $field->importable;
		$field->exportable = (int) $field->exportable;
	}

	/**
	 * Build the error returned for an invalid value.
	 *
	 * @since 1.11.0
	 * @param object $field Field object.
	 * @param string $raw   Raw value.
	 * @return WP_Error
	 */
	private function invalid_value_error( $field, $raw ) {
		return new WP_Error(
			'dfxprl_invalid_custom_field_value',
			sprintf(
				/* translators: 1: invalid value, 2: custom field name */
				__( 'Invalid value "%1$s" for field "%2$s".', 'dfx-parish-retreat-letters' ),
				$raw,
				$field->name
			)
		);
	}
}
