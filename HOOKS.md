# DFX Parish Retreat Letters - Extension Hooks

This document describes the WordPress hooks available for extending the plugin functionality with custom fields and settings.

## Available Hooks

### Filters

#### `dfxprl_save_retreat_custom_field`

Filters a custom field value before saving it to a retreat.

**Parameters:**
- `$value` (mixed) - The value being saved (default: empty string)
- `$field_name` (string) - The name of the field being saved (e.g., 'custom_css')
- `$posted_value` (mixed) - The raw value from the form submission
- `$retreat_id` (int|null) - The retreat ID being edited, or null for new retreats

**Example:**
```php
add_filter( 'dfxprl_save_retreat_custom_field', function( $value, $field_name, $posted_value, $retreat_id ) {
    // Only allow custom_css if user has premium features
    if ( $field_name === 'custom_css' && has_premium_features() ) {
        return sanitize_textarea_field( $posted_value );
    }
    return $value; // Return default value
}, 10, 4 );
```

#### `dfxprl_save_global_custom_field`

Filters a global custom field value before saving it to settings.

**Parameters:**
- `$value` (mixed) - The value being saved (default: empty string)
- `$field_name` (string) - The name of the field being saved (e.g., 'default_css')
- `$posted_value` (mixed) - The raw value from the form submission

**Example:**
```php
add_filter( 'dfxprl_save_global_custom_field', function( $value, $field_name, $posted_value ) {
    // Only allow default_css if user has premium features
    if ( $field_name === 'default_css' && has_premium_features() ) {
        return sanitize_textarea_field( $posted_value );
    }
    return $value; // Return default value
}, 10, 3 );
```

### Actions

#### `dfxprl_after_retreat_customization_fields`

Fires after the retreat customization fields in the retreat edit form.

Use this to add custom fields to individual retreat forms.

**Parameters:**
- `$retreat` (object|null) - The retreat object being edited, or null for new retreats

**Example:**
```php
add_action( 'dfxprl_after_retreat_customization_fields', function( $retreat ) {
    if ( ! has_premium_features() ) {
        return;
    }
    ?>
    <tr>
        <th scope="row">
            <label for="custom_field"><?php esc_html_e( 'Custom Field', 'your-plugin-textdomain' ); ?></label>
        </th>
        <td>
            <textarea id="custom_field" name="custom_field" rows="10" cols="80" class="large-text code"><?php 
                echo esc_textarea( $retreat->custom_field ?? '' ); 
            ?></textarea>
            <p class="description"><?php 
                esc_html_e( 'Custom field specific to this retreat.', 'your-plugin-textdomain' ); 
            ?></p>
        </td>
    </tr>
    <?php
} );
```

#### `dfxprl_after_global_customization_fields`

Fires after the global customization fields in the global settings form.

Use this to add custom fields to the global settings form.

**Example:**
```php
add_action( 'dfxprl_after_global_customization_fields', function() {
    if ( ! has_premium_features() ) {
        return;
    }
    
    $global_settings = DFXPRL_GlobalSettings::get_instance();
    $custom_field_value = $global_settings->get( 'custom_field', '' );
    ?>
    <tr>
        <th scope="row">
            <label for="custom_field"><?php esc_html_e( 'Custom Field', 'your-plugin-textdomain' ); ?></label>
        </th>
        <td>
            <textarea id="custom_field" name="custom_field" rows="15" cols="80" class="large-text code"><?php 
                echo esc_textarea( $custom_field_value ); 
            ?></textarea>
            <p class="description"><?php 
                esc_html_e( 'Custom field for global settings.', 'your-plugin-textdomain' ); 
            ?></p>
        </td>
    </tr>
    <?php
} );
```

## Complete Premium Plugin Example

Here's a complete example of how to create a premium plugin that adds custom fields:

```php
<?php
/**
 * Plugin Name: DFX Parish Retreat Letters - Premium Extension
 * Description: Adds custom fields to DFX Parish Retreat Letters
 * Version: 1.0.0
 * Requires Plugins: dfx-parish-retreat-letters
 */

// Check if premium license is valid
function dfxprl_premium_has_valid_license() {
    // Your license validation logic here
    return true; // Replace with actual validation
}

// Filter retreat custom fields to allow saving
add_filter( 'dfxprl_save_retreat_custom_field', function( $value, $field_name, $posted_value, $retreat_id ) {
    if ( $field_name === 'custom_css' && dfxprl_premium_has_valid_license() ) {
        return sanitize_textarea_field( $posted_value );
    }
    return $value;
}, 10, 4 );

// Filter global custom fields to allow saving
add_filter( 'dfxprl_save_global_custom_field', function( $value, $field_name, $posted_value ) {
    if ( $field_name === 'default_css' && dfxprl_premium_has_valid_license() ) {
        return sanitize_textarea_field( $posted_value );
    }
    return $value;
}, 10, 3 );

// Add custom field to retreat edit form
add_action( 'dfxprl_after_retreat_customization_fields', function( $retreat ) {
    if ( ! dfxprl_premium_has_valid_license() ) {
        return;
    }
    ?>
    <tr>
        <th scope="row">
            <label for="custom_css"><?php esc_html_e( 'Custom Styles', 'dfxprl-premium' ); ?></label>
        </th>
        <td>
            <textarea id="custom_css" name="custom_css" rows="10" cols="80" class="large-text code"><?php 
                echo esc_textarea( $retreat->custom_css ?? '' ); 
            ?></textarea>
            <p class="description"><?php 
                esc_html_e( 'Custom styles specific to this retreat.', 'dfxprl-premium' ); 
            ?></p>
        </td>
    </tr>
    <?php
} );

// Add custom field to global settings form
add_action( 'dfxprl_after_global_customization_fields', function() {
    if ( ! dfxprl_premium_has_valid_license() ) {
        return;
    }
    
    $global_settings = DFXPRL_GlobalSettings::get_instance();
    $default_css = $global_settings->get_default_css();
    ?>
    <tr>
        <th scope="row">
            <label for="default_css"><?php esc_html_e( 'Default Custom Styles', 'dfxprl-premium' ); ?></label>
        </th>
        <td>
            <textarea id="default_css" name="default_css" rows="15" cols="80" class="large-text code"><?php 
                echo esc_textarea( $default_css ); 
            ?></textarea>
            <p class="description"><?php 
                esc_html_e( 'Custom styles to be applied to all retreats by default.', 'dfxprl-premium' ); 
            ?></p>
        </td>
    </tr>
    <?php
} );
```

## Notes

- The base plugin saves empty strings for custom fields by default (WordPress.org compliant)
- All backend functionality (database schema, models, frontend output) remains unchanged
- Premium plugins can use these hooks to add and save custom fields
- Always sanitize user input when processing custom field values
- Consider implementing license validation to restrict premium features
