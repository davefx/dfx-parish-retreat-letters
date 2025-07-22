# Testing Guide for DFX Parish Retreat Letters

This document describes how to run tests for the DFX Parish Retreat Letters WordPress plugin.

## Test Infrastructure Overview

The plugin uses PHPUnit for testing with both unit tests and integration tests:

- **Unit Tests**: Test individual classes and methods in isolation using mocks
- **Integration Tests**: Test the plugin functionality with a real WordPress environment

## Prerequisites

- PHP 7.4 or higher
- Composer
- MySQL/MariaDB
- WordPress test environment

## Installation

1. Install dependencies:
```bash
composer install
```

2. Set up WordPress test environment:
```bash
bin/install-wp-tests.sh wordpress_test root password localhost latest
```

Replace the database credentials with your own:
- `wordpress_test`: Test database name
- `root`: Database user
- `password`: Database password
- `localhost`: Database host
- `latest`: WordPress version (or specific version like `6.3`)

## Running Tests

### Run all tests:
```bash
composer test
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
│   └── DFXParishRetreatLettersTest.php
└── integration/           # Integration tests
    └── PluginIntegrationTest.php
```

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