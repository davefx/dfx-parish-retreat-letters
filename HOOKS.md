# DFX Parish Retreat Letters - Extension Hooks

This document describes the WordPress hooks available for extending the plugin functionality with custom fields and settings.

## Available Hooks

### Filters

#### `dfxprl_save_retreat_data`

Filters the entire retreat data array before saving.

**Note:** The base plugin does not include `custom_css` in the data array by default. If your plugin needs to manage this field, you must handle it in this filter. If not provided in the filtered data array, the Retreat model's sanitize method will use an empty string as the default value, which will overwrite any existing data in the database.

**Parameters:**
- `$data` (array) - The retreat data array containing all fields
- `$retreat_id` (int|null) - The retreat ID being edited, or null for new retreats
- `$post_data` (array) - The raw POST data

**Example:**
```php
add_filter( 'dfxprl_save_retreat_data', function( $data, $retreat_id, $post_data ) {
    // Preserve existing custom_css or use new value if provided
    if ( has_premium_features() ) {
        if ( isset( $post_data['custom_css'] ) ) {
            // Use the value from POST
            $data['custom_css'] = sanitize_textarea_field( $post_data['custom_css'] );
        } elseif ( $retreat_id ) {
            // Preserve existing value when editing
            $retreat_model = new DFXPRL_Retreat();
            $existing_retreat = $retreat_model->get( $retreat_id );
            if ( $existing_retreat && ! empty( $existing_retreat->custom_css ) ) {
                $data['custom_css'] = $existing_retreat->custom_css;
            }
        }
    }
    
    // Add any other custom fields
    if ( has_premium_features() && isset( $post_data['my_custom_field'] ) ) {
        $data['my_custom_field'] = sanitize_text_field( $post_data['my_custom_field'] );
    }
    
    return $data;
}, 10, 3 );
```

#### `dfxprl_save_global_settings_data`

Filters the entire global settings data array before saving.

**Note:** The base plugin does not include `default_css` in the settings array by default. If your plugin needs to manage this field, you must handle it in this filter. The field will only be saved if provided in the filtered array.

**Parameters:**
- `$settings_data` (array) - The global settings data array containing all fields
- `$post_data` (array) - The raw POST data

**Example:**
```php
add_filter( 'dfxprl_save_global_settings_data', function( $settings_data, $post_data ) {
    // Preserve existing default_css or use new value if provided
    if ( has_premium_features() ) {
        if ( isset( $post_data['default_css'] ) ) {
            // Use the value from POST
            $settings_data['default_css'] = sanitize_textarea_field( $post_data['default_css'] );
        } else {
            // Preserve existing value
            $global_settings = DFXPRL_GlobalSettings::get_instance();
            $existing_css = $global_settings->get_default_css();
            if ( ! empty( $existing_css ) ) {
                $settings_data['default_css'] = $existing_css;
            }
        }
    }
    
    // Add any other custom settings
    if ( has_premium_features() && isset( $post_data['my_custom_setting'] ) ) {
        $settings_data['my_custom_setting'] = sanitize_text_field( $post_data['my_custom_setting'] );
    }
    
    return $settings_data;
}, 10, 2 );
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

// Filter retreat data to preserve and update custom_css
add_filter( 'dfxprl_save_retreat_data', function( $data, $retreat_id, $post_data ) {
    if ( ! dfxprl_premium_has_valid_license() ) {
        return $data;
    }
    
    // Handle custom_css field
    if ( isset( $post_data['custom_css'] ) ) {
        // Use the value from POST
        $data['custom_css'] = sanitize_textarea_field( $post_data['custom_css'] );
    } elseif ( $retreat_id ) {
        // Preserve existing value when editing
        $retreat_model = new DFXPRL_Retreat();
        $existing_retreat = $retreat_model->get( $retreat_id );
        if ( $existing_retreat && ! empty( $existing_retreat->custom_css ) ) {
            $data['custom_css'] = $existing_retreat->custom_css;
        }
    }
    
    return $data;
}, 10, 3 );

// Filter global settings data to preserve and update default_css
add_filter( 'dfxprl_save_global_settings_data', function( $settings_data, $post_data ) {
    if ( ! dfxprl_premium_has_valid_license() ) {
        return $settings_data;
    }
    
    // Handle default_css field
    if ( isset( $post_data['default_css'] ) ) {
        // Use the value from POST
        $settings_data['default_css'] = sanitize_textarea_field( $post_data['default_css'] );
    } else {
        // Preserve existing value
        $global_settings = DFXPRL_GlobalSettings::get_instance();
        $existing_css = $global_settings->get_default_css();
        if ( ! empty( $existing_css ) ) {
            $settings_data['default_css'] = $existing_css;
        }
    }
    
    return $settings_data;
}, 10, 2 );

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
