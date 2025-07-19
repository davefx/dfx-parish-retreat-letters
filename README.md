# DFX Parish Retreat Letters

A comprehensive WordPress plugin for managing parish retreat letters and retreat data.

## Features

### Retreat Management
- **Complete CRUD Operations**: Create, read, update, and delete retreats
- **Comprehensive Retreat Data**: Name, location, start date, end date with validation
- **Search & Filter**: Search retreats by name or location, filter by date ranges
- **Professional Admin Interface**: WordPress-style admin tables and forms

### Technical Features
- **Database Integration**: Custom table with proper indexes and validation
- **Security**: WordPress nonces, capability checks, and data sanitization
- **AJAX Operations**: Smooth delete operations with confirmation
- **Internationalization**: Full i18n support with translation files
- **Responsive Design**: Mobile-friendly admin interface
- **OOP Architecture**: Clean, maintainable code with singleton patterns

## Installation

1. Download the plugin files
2. Upload to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to 'Retreats' in your admin menu to start managing retreats

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## File Structure

```
dfx-parish-retreat-letters/
├── dfx-parish-retreat-letters.php    # Main plugin file
├── includes/
│   ├── class-dfx-parish-retreat-letters.php  # Main plugin class
│   ├── class-admin.php               # Admin functionality
│   ├── class-database.php            # Database operations
│   └── class-retreat.php             # Retreat entity model
├── admin/
│   ├── css/
│   │   └── admin.css                 # Admin styles
│   ├── js/
│   │   └── admin.js                  # Admin JavaScript
│   └── partials/
│       ├── retreats-list.php         # Retreats listing page
│       └── retreat-form.php          # Add/edit retreat form
└── languages/
    └── dfx-parish-retreat-letters.pot # Translation template
```

## Database Schema

The plugin creates a `wp_dfx_prl_retreats` table with the following structure:

- `id` (Primary Key)
- `name` (VARCHAR 255, Required)
- `location` (VARCHAR 255, Required)
- `start_date` (DATE, Required)
- `end_date` (DATE, Required)
- `created_at` (DATETIME)
- `updated_at` (DATETIME)

## Usage

### Adding a Retreat
1. Go to **Retreats > Add New** in your WordPress admin
2. Fill in the required fields:
   - Retreat Name
   - Location
   - Start Date
   - End Date
3. Click **Add Retreat**

### Managing Retreats
1. Go to **Retreats > All Retreats**
2. Use the search box to find specific retreats
3. Filter by date range using the date filters
4. Edit or delete retreats using the action links

### Search and Filtering
- **Search**: Enter retreat name or location in the search box
- **Date Filters**: Use "Start Date From" and "Start Date To" to filter retreats by date range
- **Clear Filters**: Click "Clear Filters" to reset all search criteria

## Development

### Extending the Plugin
The plugin is built with extensibility in mind. You can:

1. **Add Custom Fields**: Extend the `DFX_PRL_Retreat` class
2. **Add Hooks**: Use the `dfx_prl_init` action hook
3. **Customize Templates**: Override templates in your theme
4. **Add Translations**: Use the provided `.pot` file

### Hooks and Filters
- `dfx_prl_init` - Fired when the plugin is initialized

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Complete retreat management system
- Admin interface with CRUD operations
- Search and filtering functionality
- AJAX operations
- Internationalization support