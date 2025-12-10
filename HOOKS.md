# DFX Parish Retreat Letters - Extension Hooks

This document describes the WordPress hooks available for extending the plugin functionality, particularly for re-enabling CSS customization features in premium plugins.

## Available Hooks

### Filters

#### `dfxprl_retreat_custom_css`

Filters the custom CSS value before saving it to a retreat.

**Parameters:**
- `$css` (string) - The CSS value being saved (default: empty string)
- `$posted_css` (string) - The raw CSS value from the form submission
- `$retreat_id` (int|null) - The retreat ID being edited, or null for new retreats

**Example:**
```php
add_filter( 'dfxprl_retreat_custom_css', function( $css, $posted_css, $retreat_id ) {
    // Only allow CSS if user has premium features
    if ( has_premium_features() ) {
        return sanitize_textarea_field( $posted_css );
    }
    return $css; // Return empty string by default
}, 10, 3 );
```

#### `dfxprl_global_default_css`

Filters the global default CSS value before saving it to settings.

**Parameters:**
- `$css` (string) - The CSS value being saved (default: empty string)
- `$posted_css` (string) - The raw CSS value from the form submission

**Example:**
```php
add_filter( 'dfxprl_global_default_css', function( $css, $posted_css ) {
    // Only allow CSS if user has premium features
    if ( has_premium_features() ) {
        return sanitize_textarea_field( $posted_css );
    }
    return $css; // Return empty string by default
}, 10, 2 );
```

### Actions

#### `dfxprl_after_retreat_customization_fields`

Fires after the retreat customization fields (header/footer blocks) in the retreat edit form.

Use this to add custom CSS fields or other customization options to individual retreat forms.

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
            <label for="custom_css"><?php esc_html_e( 'Custom CSS Styles', 'your-plugin-textdomain' ); ?></label>
        </th>
        <td>
            <textarea id="custom_css" name="custom_css" rows="10" cols="80" class="large-text code"><?php 
                echo esc_textarea( $retreat->custom_css ?? '' ); 
            ?></textarea>
            <p class="description"><?php 
                esc_html_e( 'CSS styles specific to this retreat\'s message form page.', 'your-plugin-textdomain' ); 
            ?></p>
        </td>
    </tr>
    <?php
} );
```

#### `dfxprl_after_global_customization_fields`

Fires after the global customization fields (header/footer blocks) in the global settings form.

Use this to add a global default CSS field or other global customization options.

**Example:**
```php
add_action( 'dfxprl_after_global_customization_fields', function() {
    if ( ! has_premium_features() ) {
        return;
    }
    
    $global_settings = DFXPRL_GlobalSettings::get_instance();
    $default_css = $global_settings->get_default_css();
    ?>
    <tr>
        <th scope="row">
            <label for="default_css"><?php esc_html_e( 'Default CSS Styles', 'your-plugin-textdomain' ); ?></label>
        </th>
        <td>
            <textarea id="default_css" name="default_css" rows="15" cols="80" class="large-text code"><?php 
                echo esc_textarea( $default_css ); 
            ?></textarea>
            <p class="description"><?php 
                esc_html_e( 'CSS styles to be applied to all retreat message form pages.', 'your-plugin-textdomain' ); 
            ?></p>
        </td>
    </tr>
    <?php
} );
```

## Complete Premium Plugin Example

Here's a complete example of how to create a premium plugin that re-enables CSS customization:

```php
<?php
/**
 * Plugin Name: DFX Parish Retreat Letters - Premium CSS
 * Description: Re-enables CSS customization features for DFX Parish Retreat Letters
 * Version: 1.0.0
 * Requires Plugins: dfx-parish-retreat-letters
 */

// Check if premium license is valid
function dfxprl_premium_has_valid_license() {
    // Your license validation logic here
    return true; // Replace with actual validation
}

// Filter retreat custom CSS to allow saving
add_filter( 'dfxprl_retreat_custom_css', function( $css, $posted_css, $retreat_id ) {
    if ( dfxprl_premium_has_valid_license() ) {
        return sanitize_textarea_field( $posted_css );
    }
    return $css;
}, 10, 3 );

// Filter global default CSS to allow saving
add_filter( 'dfxprl_global_default_css', function( $css, $posted_css ) {
    if ( dfxprl_premium_has_valid_license() ) {
        return sanitize_textarea_field( $posted_css );
    }
    return $css;
}, 10, 2 );

// Add CSS field to retreat edit form
add_action( 'dfxprl_after_retreat_customization_fields', function( $retreat ) {
    if ( ! dfxprl_premium_has_valid_license() ) {
        return;
    }
    ?>
    <tr>
        <th scope="row">
            <label for="custom_css"><?php esc_html_e( 'Custom CSS Styles', 'dfxprl-premium' ); ?></label>
        </th>
        <td>
            <textarea id="custom_css" name="custom_css" rows="10" cols="80" class="large-text code"><?php 
                echo esc_textarea( $retreat->custom_css ?? '' ); 
            ?></textarea>
            <p class="description"><?php 
                esc_html_e( 'CSS styles specific to this retreat\'s message form page. Do not include &lt;style&gt; tags.', 'dfxprl-premium' ); 
            ?></p>
        </td>
    </tr>
    <?php
} );

// Add CSS field to global settings form
add_action( 'dfxprl_after_global_customization_fields', function() {
    if ( ! dfxprl_premium_has_valid_license() ) {
        return;
    }
    
    $global_settings = DFXPRL_GlobalSettings::get_instance();
    $default_css = $global_settings->get_default_css();
    ?>
    <tr>
        <th scope="row">
            <label for="default_css"><?php esc_html_e( 'Default CSS Styles', 'dfxprl-premium' ); ?></label>
        </th>
        <td>
            <textarea id="default_css" name="default_css" rows="15" cols="80" class="large-text code"><?php 
                echo esc_textarea( $default_css ); 
            ?></textarea>
            <p class="description"><?php 
                esc_html_e( 'CSS styles to be applied to all retreat message form pages. Do not include &lt;style&gt; tags.', 'dfxprl-premium' ); 
            ?></p>
        </td>
    </tr>
    <?php
} );
```

## Notes

- The base plugin now saves empty strings for CSS values by default (WordPress.org requirement)
- All backend functionality (database schema, models, frontend CSS output) remains unchanged
- Premium plugins can use these hooks to re-enable CSS customization
- Always sanitize user input when processing CSS values
- Consider implementing license validation to restrict premium features
