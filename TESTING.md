# Testing Guide for DFX Parish Retreat Letters

This document describes how to run tests for the DFX Parish Retreat Letters WordPress plugin.

## Test Infrastructure Overview

The plugin features a comprehensive test infrastructure with multiple levels:

- **Basic Tests**: Quick verification without WordPress dependencies (4 tests)
- **Comprehensive Tests**: Complete feature coverage testing (22 tests, 112+ assertions)
- **Advanced Unit Tests**: Detailed class-by-class testing with mocks and Brain Monkey (separate configuration)
- **Integration Tests**: Full WordPress environment testing

## Quick Start

### Basic Testing (No Dependencies Required)
For quick verification that the test infrastructure is working:

```bash
# Run basic tests (4 tests, ~11 assertions)
phpunit --configuration phpunit-basic.xml --testsuite basic

# Run comprehensive feature tests (22 tests, 112+ assertions)
phpunit --configuration phpunit-basic.xml --testsuite comprehensive
```

### Advanced Testing (External Dependencies Required)
For unit tests with Brain Monkey mocking (requires composer dependencies):

```bash
# Install dependencies first
composer install

# Run advanced unit tests
phpunit --configuration phpunit-advanced.xml --testsuite advanced
```

### Full WordPress Testing
For complete testing with WordPress environment:

1. Install dependencies:
```bash
composer install
```

2. Set up WordPress test environment:
```bash
bin/install-wp-tests.sh wordpress_test root password localhost latest
```

3. Run advanced unit tests with Brain Monkey:
```bash
phpunit --configuration phpunit-basic.xml --testsuite advanced
```

4. Run full integration tests:
```bash
composer test
```

## Test Coverage

### ✅ Comprehensive Feature Testing (22 tests)

Our comprehensive test suite validates all plugin features:

#### Core Plugin Infrastructure
- **Plugin Loading**: All 12 core classes load correctly
- **Singleton Patterns**: 6 key classes implement singleton correctly
- **Constants & Version**: Plugin constants and version management
- **File Structure**: All required files exist and are accessible

#### Data Management & CRUD Operations
- **Retreat Management**: Create, read, update, delete, search functionality
- **Attendant Management**: Full CRUD with validation and token generation  
- **Message Management**: Encrypted message handling with decryption
- **Database Operations**: Table management, migrations, cleanup

#### Security Features
- **Encryption**: AES-256 data encryption and decryption
- **Token Generation**: Secure token creation and uniqueness validation
- **IP Handling**: User IP retrieval and anonymization
- **CSRF Protection**: Token generation and validation

#### Admin Interface & User Experience
- **Admin Menus**: Menu creation and registration
- **AJAX Handlers**: Create retreat, attendant, and message handlers
- **Script/Style Loading**: Asset enqueuing and localization
- **Bulk Operations**: Multi-item processing and validation

#### Privacy & Compliance
- **GDPR Features**: Data export, erasure, and anonymization
- **Print Logging**: Action tracking, audit trails, statistics
- **Permissions**: Role-based access control and custom capabilities
- **Data Retention**: Automated cleanup and retention policies

#### Communication & Files
- **Invitation System**: Email sending, templates, RSVP processing
- **File Management**: Upload, validation, secure serving
- **Message Files**: Encrypted file attachments and downloads
- **Email Templates**: Dynamic content generation

### 🧪 Advanced Unit Tests (Individual Classes)

Each major plugin class has dedicated test suites:

- `SecurityTest.php` - Encryption, tokens, rate limiting, CSRF
- `AttendantTest.php` - CRUD operations, validation, search
- `ConfidentialMessageTest.php` - Encrypted messaging, filtering
- `AdminTest.php` - Interface, AJAX, permissions, bulk ops
- `GDPRTest.php` - Data export, erasure, consent tracking
- `PermissionsTest.php` - Role-based access, capabilities
- `InvitationsTest.php` - Email, templates, RSVP processing
- `MessageFileTest.php` - File upload, validation, download
- `PrintLogTest.php` - Action logging, statistics, audit
- `DatabaseTest.php` - Table management, migrations
- `RetreatTest.php` - Extended CRUD, search, validation

## Prerequisites

- PHP 7.4 or higher
- Composer
- MySQL/MariaDB (for integration tests)
- WordPress test environment (for full test suite)

## Installation

### For Development
```bash
composer install
```

### For CI/CD (Production)
```bash
composer install --no-dev
```

### Run only unit tests:
```bash
composer test:unit
```

### Run only integration tests:
```bash
composer test:integration
```

### Generate coverage report:
```bash
composer test:coverage
```

This will generate an HTML coverage report in the `coverage/` directory.

## Test Structure

```
tests/
├── bootstrap.php          # Test bootstrap file
├── unit/                  # Unit tests
│   ├── DatabaseTest.php
│   ├── RetreatTest.php
│   ├── AttendantTest.php
│   ├── SecurityTest.php
│   ├── ConfidentialMessageTest.php
│   ├── AdminTest.php
│   ├── GDPRTest.php
│   ├── PermissionsTest.php
│   ├── InvitationsTest.php
│   ├── MessageFileTest.php
│   ├── PrintLogTest.php
│   ├── ComprehensiveInfrastructureTest.php
│   └── DFXParishRetreatLettersTest.php
└── integration/           # Integration tests
    └── PluginIntegrationTest.php
```

## Available Test Commands

### Basic Testing Commands
```bash
# Quick basic tests (4 tests, ~11 assertions)
phpunit --configuration phpunit-basic.xml --testsuite basic

# Comprehensive feature tests (22 tests, 101+ assertions)  
phpunit --configuration phpunit-basic.xml --testsuite comprehensive

# All basic tests (combines basic + comprehensive)
phpunit --configuration phpunit-basic.xml

# Verbose output for debugging
phpunit --configuration phpunit-basic.xml --testsuite comprehensive --verbose
```

### Advanced Testing Commands (Requires Dependencies)
```bash
# Advanced unit tests with Brain Monkey (requires composer install)
phpunit --configuration phpunit-advanced.xml --testsuite advanced

# Full WordPress integration tests
phpunit --configuration phpunit.xml

# Using composer scripts
composer test:basic     # Basic tests only
composer test:unit      # Unit tests with WordPress
composer test:coverage  # Generate coverage report
composer test           # Complete test suite
```

## Test Configuration Files

The plugin uses separate PHPUnit configurations for different test types:

### Configuration Overview
- **`phpunit-basic.xml`**: Standalone tests with mock WordPress functions
  - Bootstrap: `tests/bootstrap-simple.php` 
  - Test suites: `basic` (4 tests), `comprehensive` (22 tests)
  - No external dependencies required
  
- **`phpunit-advanced.xml`**: Unit tests with Brain Monkey mocking
  - Bootstrap: `tests/bootstrap-brain-monkey.php`
  - Test suite: `advanced` (12+ test classes) 
  - Requires: `composer install` for Brain Monkey
  
- **`phpunit.xml`**: Full WordPress integration tests
  - Bootstrap: `tests/bootstrap.php`
  - Test suites: `unit`, `integration`
  - Requires: Full WordPress test environment

### Specific Test Commands
```bash
# Run specific test class
phpunit tests/unit/SecurityTest.php

# Run specific test method
phpunit --filter testEncryptionDecryption tests/unit/SecurityTest.php

# Run tests with coverage
phpunit --coverage-html coverage/

# Debug failing tests
phpunit --stop-on-failure --verbose tests/unit/
```

## Test Results Examples

### Successful Comprehensive Test Run
```
PHPUnit 8.5.42 by Sebastian Bergmann and contributors.

......................                                            22 / 22 (100%)

Time: 76 ms, Memory: 17.14 MB

OK (22 tests, 112 assertions)
```

### Test Coverage Summary
- ✅ **Plugin Loading**: 12/12 core classes
- ✅ **Singleton Patterns**: 6/6 key classes  
- ✅ **CRUD Operations**: 3/3 data models
- ✅ **Security Features**: Encryption, tokens, IP handling
- ✅ **Admin Interface**: Menus, AJAX, scripts, permissions
- ✅ **GDPR Compliance**: Export, erasure, anonymization
- ✅ **File Management**: Upload, validation, secure serving
- ✅ **Print Logging**: Audit trails, statistics
- ✅ **Invitation System**: Email, templates, RSVP
- ✅ **Permissions**: Role-based access control

## Writing Tests

### Unit Tests

Unit tests should extend `PHPUnit\Framework\TestCase` and use Brain Monkey for WordPress function mocking:

```php
<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class MyClassTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        
        // Mock WordPress functions
        Functions\when('sanitize_text_field')->alias(function($text) {
            return trim(strip_tags($text));
        });
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_my_method() {
        // Your test code here
    }
}
```

### Integration Tests

Integration tests should extend `WP_UnitTestCase` and test with real WordPress environment:

```php
<?php
class MyIntegrationTest extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        // Set up test data
    }

    public function test_plugin_functionality() {
        // Test with real WordPress functions and database
    }
}
```

## Continuous Integration

The plugin uses GitHub Actions for CI/CD. Tests run automatically on:

- Push to `main` or `develop` branches
- Pull requests to `main` or `develop` branches

The CI pipeline tests against multiple PHP and WordPress versions:
- PHP: 7.4, 8.0, 8.1, 8.2
- WordPress: 6.0, 6.1, 6.2, 6.3, latest

## Best Practices

1. **Test Coverage**: Aim for high test coverage of critical functionality
2. **Mock External Dependencies**: Use mocks for WordPress functions in unit tests
3. **Test Edge Cases**: Include tests for error conditions and edge cases
4. **Keep Tests Fast**: Unit tests should run quickly; use integration tests sparingly
5. **Descriptive Names**: Use descriptive test method names that explain what is being tested

## Debugging Tests

### Run specific test:
```bash
./vendor/bin/phpunit tests/unit/DatabaseTest.php
```

### Run with verbose output:
```bash
./vendor/bin/phpunit --verbose
```

### Debug with var_dump:
```bash
./vendor/bin/phpunit --debug
```

## Test Database

Integration tests use a separate test database. The test environment:
- Creates temporary tables for each test
- Rolls back changes after each test
- Provides WordPress core functionality

## Contributing

When adding new functionality:

1. Write unit tests for new classes and methods
2. Add integration tests for complex workflows
3. Ensure all tests pass before submitting PR
4. Maintain or improve test coverage

## Troubleshooting

### Common Issues

1. **MySQL Connection Error**: Ensure MySQL is running and credentials are correct
2. **WordPress Not Found**: Run the install script to set up WordPress test environment
3. **Memory Limit**: Increase PHP memory limit if tests fail due to memory issues
4. **Permissions**: Ensure write permissions for test directories

### Getting Help

- Check the GitHub Actions logs for CI failures
- Review PHPUnit documentation for advanced testing features
- Consult WordPress unit testing documentation for WordPress-specific testing