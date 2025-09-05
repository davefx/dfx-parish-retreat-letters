# DFX Parish Retreat Letters - WordPress Plugin Development Guide

**CRITICAL**: Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Project Overview
DFX Parish Retreat Letters is an enterprise-grade WordPress plugin for managing parish retreat programs with confidentiality, GDPR compliance, and advanced user management. This is a **runtime PHP plugin** that requires **no build process** - all files are ready to use as-is.

## Technology Stack & Requirements
- **WordPress**: 5.0+ (tested up to 6.8)
- **PHP**: 7.4+ (tested with PHP 8.3)
- **Database**: MySQL 5.6+ for WordPress integration
- **Dependencies**: Composer (development only)
- **Architecture**: Object-oriented PHP with singleton patterns
- **No Build Required**: This is a runtime plugin, not a compiled application

## Quick Start & Validation Commands

### Essential Setup (Required Every Time)
```bash
# Verify PHP version (must be 7.4+)
php --version

# Install development dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader
# TIMING: Takes approximately 15 seconds. Set timeout to 60 seconds.
```

### Immediate Validation (Always Run These)
```bash
# Test basic infrastructure (4 tests, 11 assertions)
./vendor/bin/phpunit --configuration phpunit-basic.xml --testsuite basic
# TIMING: <1 second. Always passes.

# Test comprehensive features (31 tests, 190 assertions) 
./vendor/bin/phpunit --configuration phpunit-basic.xml --testsuite comprehensive
# TIMING: <1 second. Tests all core plugin functionality.

# Verify plugin syntax
php -l dfx-parish-retreat-letters.php
find includes/ -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"
# TIMING: <5 seconds. Should show "No syntax errors detected"
```

### Available Test Commands
```bash
# Quick infrastructure validation
composer test:basic                    # 4 tests, basic checks

# Complete feature testing  
composer test:comprehensive            # 31 tests, 190+ assertions

# Safe default testing (same as comprehensive)
composer test:safe                     # 31 tests, preferred for CI

# Default test suite
composer test                          # Runs comprehensive tests

# Advanced tests (requires WordPress environment - see limitations)
composer test:advanced                 # May fail in restricted environments
```

## Critical Timing & Cancellation Warnings

**NEVER CANCEL** any of these operations:
- `composer install`: Takes 15 seconds. **Set timeout to 60+ seconds**.
- All test suites: Complete in under 1 second each.
- Plugin syntax checks: Complete in under 5 seconds.

**Note**: This plugin has exceptionally fast operations compared to typical build systems.

## Environment Limitations & Workarounds

### Network Restrictions
- **WordPress.org downloads FAIL** in sandboxed environments
- **Integration tests require WordPress test environment** which cannot be set up without network access
- **Composer dependencies download correctly** from Packagist

### Working vs. Non-Working Features
✅ **Always Available**:
- Basic infrastructure tests (4 tests)
- Comprehensive feature tests (31 tests, 190+ assertions)
- PHP syntax validation
- Composer dependency management
- Plugin file structure validation

❌ **Requires Network/WordPress Environment**:
- Integration tests with real WordPress
- Coverage report generation (`composer test:coverage`)
- Advanced unit tests with Brain Monkey (depends on environment)
- WordPress test environment setup via `bin/install-wp-tests.sh`

### Alternative Validation Approach
When full WordPress integration isn't available:
```bash
# Run maximum available test coverage
composer test:comprehensive

# Verify all plugin classes load correctly
php -r "
require_once 'dfx-parish-retreat-letters.php';
echo 'Plugin loaded successfully\n';
"

# Check plugin structure integrity
ls -la includes/class-*.php | wc -l
# Should show 12 core class files
```

## Manual Validation Scenarios

After making changes, **ALWAYS** run these validation workflows:

### 1. Core Plugin Integrity
```bash
# Verify all core classes exist and have valid syntax
for file in includes/class-*.php; do
    echo "Checking $file..."
    php -l "$file" || exit 1
done

# Confirm main plugin file structure
grep -q "Plugin Name: DFX Parish Retreat Letters" dfx-parish-retreat-letters.php
echo "Plugin header validated"
```

### 2. Test Coverage Validation
```bash
# Run complete test suite
composer test:comprehensive
# EXPECTED: 31 tests, 190+ assertions, all passing

# Verify test coverage includes:
# - Plugin loading (12 core classes)
# - Singleton patterns (6 key classes) 
# - CRUD operations (3 data models)
# - Security features (encryption, tokens)
# - Admin interface functionality
# - GDPR compliance features
# - File management operations
```

### 3. Database Schema Validation (When Possible)
```bash
# If WordPress environment available, check database operations
php validate-constraint-fixes.php
# Validates database constraint fixes are properly applied
```

## Common Development Tasks

### Adding New Features
1. **Always test first**: `composer test:comprehensive`
2. **Create/modify PHP classes** in `includes/` directory
3. **Follow WordPress coding standards**
4. **Test changes**: `composer test:comprehensive` 
5. **Verify syntax**: `php -l path/to/new/file.php`

### Working with Tests
```bash
# Run specific test file
./vendor/bin/phpunit tests/unit/SecurityTest.php

# Run with verbose output for debugging
./vendor/bin/phpunit --configuration phpunit-basic.xml --testsuite comprehensive --verbose

# Test specific functionality
./vendor/bin/phpunit --filter testEncryptionDecryption tests/unit/SecurityTest.php
```

### Plugin Structure & Key Files
```
dfx-parish-retreat-letters/
├── dfx-parish-retreat-letters.php    # Main plugin entry point
├── includes/                         # Core plugin classes (12 files)
│   ├── class-dfx-parish-retreat-letters.php  # Main singleton
│   ├── class-admin.php                        # Admin interface
│   ├── class-database.php                     # Database operations
│   ├── class-security.php                     # Encryption & security
│   ├── class-permissions.php                  # Authorization system
│   └── [7 other core classes]
├── tests/                            # Test infrastructure
│   ├── unit/                         # Unit tests (12+ test classes)
│   └── integration/                  # WordPress integration tests
├── languages/                        # i18n files (EN/ES)
├── composer.json                     # PHP dependencies
└── bin/install-wp-tests.sh          # WordPress test setup
```

### Internationalization
The plugin includes complete i18n support:
- **English**: Default language
- **Spanish**: Professional translation (es_ES)
- **Template**: `languages/dfx-parish-retreat-letters.pot` for new translations

## Repository Information Summary

### Test Results Cache
```bash
# Current test status (from .phpunit.result.cache)
ls -la .phpunit.result.cache  # Shows last test run results
```

### Version Information
- **Current Version**: 25.7.27 (from plugin header)
- **PHP Requirement**: 7.4+ 
- **WordPress Compatibility**: 5.0 to 6.8+
- **Database Schema Version**: Managed by Database class

### Known Working Commands Reference
```bash
# Core development workflow (copy-pasteable)
composer install
composer test:comprehensive
php -l dfx-parish-retreat-letters.php
echo "✅ Plugin validated successfully"
```

## Troubleshooting

### Common Issues & Solutions
1. **"Class not found" errors**: Run `composer install` first
2. **"WordPress functions not found"**: Expected in non-WordPress environments, use basic/comprehensive tests instead
3. **Network timeouts**: Normal in restricted environments, use offline testing approach
4. **MySQL connection errors**: Integration tests require MySQL, use comprehensive tests as alternative

### Debugging Test Failures
```bash
# Run tests with detailed output
./vendor/bin/phpunit --configuration phpunit-basic.xml --testsuite comprehensive --verbose

# Check specific test class
./vendor/bin/phpunit tests/unit/ComprehensiveInfrastructureTest.php --verbose

# Verify plugin loading
php -r "echo 'PHP version: ' . PHP_VERSION . PHP_EOL;"
```

### Validation Checklist
Before committing changes:
- [ ] `composer test:comprehensive` passes (31 tests, 190+ assertions)
- [ ] All PHP files have valid syntax
- [ ] Plugin header information intact
- [ ] No new external dependencies required
- [ ] Changes tested in isolation where possible

## Success Indicators
A successful development environment will show:
- ✅ Composer install completes in ~15 seconds
- ✅ Basic tests: 4 tests, 11 assertions pass
- ✅ Comprehensive tests: 31 tests, 190+ assertions pass  
- ✅ PHP syntax validation passes for all files
- ✅ Plugin loads without fatal errors

**Remember**: This plugin emphasizes runtime validation over build processes. Focus on comprehensive test coverage and PHP best practices rather than complex build pipelines.