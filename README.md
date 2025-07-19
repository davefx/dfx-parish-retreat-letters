# DFX Parish Retreat Letters

A WordPress plugin for managing parish retreat letters.

## Description

DFX Parish Retreat Letters is a WordPress plugin designed to help parishes manage retreat letters efficiently. This plugin provides a foundation for organizing and handling retreat-related correspondence.

## Features

- Object-Oriented Programming (OOP) architecture
- Singleton pattern implementation
- Internationalization (i18n) support
- Spanish translations included
- WordPress coding standards compliance
- Modern PHP practices (PHP 7.4+)

## Installation

1. Download the plugin files
2. Upload the `dfx-parish-retreat-letters` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## Plugin Structure

```
dfx-parish-retreat-letters/
├── dfx-parish-retreat-letters.php (main plugin file)
├── includes/
│   ├── class-dfx-parish-retreat-letters.php
│   └── class-i18n.php
├── languages/
│   ├── dfx-parish-retreat-letters.pot
│   ├── dfx-parish-retreat-letters-es_ES.po
│   └── dfx-parish-retreat-letters-es_ES.mo
└── README.md
```

## Development

This plugin follows WordPress coding standards and uses:

- **Singleton Pattern**: Main plugin class implements singleton pattern for single instance management
- **Internationalization**: Full i18n support with text domain `dfx-parish-retreat-letters`
- **OOP Architecture**: Clean, maintainable object-oriented code structure
- **Modern PHP**: Utilizes modern PHP practices and features

## Translations

The plugin is translation-ready and includes:

- **English** (default)
- **Spanish (es_ES)** - Complete translation available

To add new translations, create new `.po` and `.mo` files in the `languages/` directory using the provided `.pot` template.

## License

This plugin is licensed under the GPL v3 or later.

## Author

**DaveFX**
- GitHub: [@davefx](https://github.com/davefx)
- Repository: [dfx-parish-retreat-letters](https://github.com/davefx/dfx-parish-retreat-letters)

## Changelog

### 1.0.0
- Initial release
- Basic plugin structure with OOP and singleton pattern
- Internationalization support
- Spanish translations
- WordPress coding standards compliance