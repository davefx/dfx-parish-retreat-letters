# Building Distribution Package

This document describes how to build a production-ready distribution package for the DFX Parish Retreat Letters WordPress plugin.

## Overview

The plugin includes an automated build script that creates a clean, production-ready ZIP package suitable for:
- WordPress.org plugin repository distribution
- Manual installation on WordPress sites
- Private distribution to clients or organizations

## Prerequisites

The build script requires the following tools:
- **Bash shell** (Linux, macOS, WSL on Windows, or Git Bash)
- **rsync** (for efficient file copying)
- **zip** (for creating the distribution archive)

These tools are typically pre-installed on Linux and macOS systems. Windows users should use WSL, Git Bash, or install the tools manually.

## Quick Start

### Using Composer (Recommended)

```bash
# Build the distribution package
composer build
```

### Using the Script Directly

```bash
# Make the script executable (first time only)
chmod +x bin/build-dist.sh

# Run the build script
./bin/build-dist.sh
```

## Build Process

The build script performs the following operations:

1. **Version Detection**: Automatically extracts the plugin version from `dfx-parish-retreat-letters.php`
2. **Directory Preparation**: Creates a clean build directory structure
3. **File Copying**: Copies only production files, excluding:
   - Development and testing files (tests, vendor, phpunit configs)
   - Version control files (.git, .github, .gitignore)
   - IDE configuration files (.vscode, .idea)
   - Build artifacts and temporary files
   - CI/CD configuration files
4. **Archive Creation**: Creates a versioned ZIP file (e.g., `dfx-parish-retreat-letters-25.11.20.zip`)
5. **Verification**: Displays package information and file count

## Output

The build script creates the following structure:

```
build/
├── dfx-parish-retreat-letters/          # Staging directory
│   ├── dfx-parish-retreat-letters.php   # Main plugin file
│   ├── readme.txt                       # WordPress.org readme
│   ├── LICENSE                          # GPL v3 license
│   ├── uninstall.php                    # Uninstall handler
│   ├── includes/                        # Core plugin classes
│   └── languages/                       # Translation files
└── dfx-parish-retreat-letters-{VERSION}.zip  # Distribution package
```

### Package Contents

The distribution package includes:

✅ **Included**:
- Main plugin file and WordPress plugin headers
- All PHP class files in `includes/` directory
- JavaScript and CSS assets
- Translation files (.pot, .po, .mo)
- LICENSE file
- readme.txt (WordPress.org format)
- uninstall.php

❌ **Excluded**:
- Test files and test configuration
- Development dependencies (vendor directory)
- Version control files (.git, .gitignore)
- CI/CD configuration (.github directory)
- IDE settings (.vscode, .idea)
- Build scripts and tools (/bin directory)
- Developer documentation (BUILD.md, TESTING.md, etc.)
- Build artifacts from previous runs

## Customizing Exclusions

The build script uses `.distignore` to determine which files to exclude from the distribution package. To customize:

1. Edit `.distignore` in the plugin root directory
2. Add file patterns (one per line) to exclude
3. Lines starting with `#` are comments
4. Patterns follow rsync exclusion syntax

Example `.distignore` patterns:
```
/tests           # Exclude tests directory
*.md             # Exclude markdown files
.phpunit.*       # Exclude PHPUnit config files
composer.json    # Exclude composer configuration
```

## Verification

After building, verify the package contents:

```bash
# List files in the ZIP archive
unzip -l build/dfx-parish-retreat-letters-{VERSION}.zip

# Check that excluded files are not present
unzip -l build/dfx-parish-retreat-letters-{VERSION}.zip | grep -E "(test|vendor|phpunit)"
```

## Installation Testing

Test the distribution package by installing it on a WordPress site:

1. Extract the built ZIP file from the `build/` directory
2. Upload to WordPress via:
   - **Admin Interface**: Plugins → Add New → Upload Plugin
   - **Manual Installation**: Extract to `wp-content/plugins/` directory
   - **WP-CLI**: `wp plugin install /path/to/plugin.zip --activate`
3. Activate the plugin and verify functionality

## Cleanup

After distributing the package, clean up build artifacts:

```bash
# Remove the build directory
rm -rf build/
```

## Continuous Integration

The build process can be integrated into CI/CD pipelines:

```yaml
# Example GitHub Actions workflow step
- name: Build distribution package
  run: composer build

- name: Upload artifact
  uses: actions/upload-artifact@v3
  with:
    name: plugin-distribution
    path: build/*.zip
```

## Troubleshooting

### "rsync: command not found"

Install rsync for your platform:
- **Ubuntu/Debian**: `sudo apt-get install rsync`
- **macOS**: Pre-installed (or `brew install rsync`)
- **Windows**: Use WSL or Git Bash

### "zip: command not found"

Install zip utility:
- **Ubuntu/Debian**: `sudo apt-get install zip`
- **macOS**: Pre-installed
- **Windows**: Use WSL or install from http://gnuwin32.sourceforge.net/packages/zip.htm

### Permission Denied

Make the script executable:
```bash
chmod +x bin/build-dist.sh
```

### Version Not Detected

Ensure the main plugin file (`dfx-parish-retreat-letters.php`) contains:
```php
 * Version: X.Y.Z
```

## Release Checklist

Before creating a distribution package for release:

- [ ] Update version number in `dfx-parish-retreat-letters.php`
- [ ] Update `Stable tag:` in `readme.txt`
- [ ] Update `CHANGELOG` section in `readme.txt`
- [ ] Run tests: `composer test:comprehensive`
- [ ] Build package: `composer build`
- [ ] Verify package contents
- [ ] Test installation on clean WordPress site
- [ ] Tag release in version control
- [ ] Upload to WordPress.org (if applicable)

## Support

For issues or questions about the build process, please open an issue on the GitHub repository:
https://github.com/davefx/dfx-parish-retreat-letters/issues
