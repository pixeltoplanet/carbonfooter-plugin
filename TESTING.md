# Testing Guide for CarbonFooter Plugin

This guide explains how to run tests for the CarbonFooter WordPress plugin.

## Test Types

The plugin has three types of tests:

### 1. PHP Unit Tests (Brain Monkey)
- Fast, isolated tests that don't require WordPress
- Use Brain Monkey to mock WordPress functions
- Run without a database

### 2. PHP Integration Tests (WordPress PHPUnit)
- Full WordPress environment tests
- Test real hooks, database operations, REST API
- Require a WordPress test database

### 3. JavaScript Tests (Jest)
- Test React components and utility functions
- Use @testing-library/react for component testing
- Mock WordPress global objects (@wordpress/scripts)

---

## Current Test Coverage

### Summary

| Category             | Tests   | Assertions | Status            |
| -------------------- | ------- | ---------- | ----------------- |
| **PHP Unit Tests**   | 113     | 288        | ✅ All Passing     |
| **JavaScript Tests** | 46      | -          | ✅ All Passing     |
| **Total**            | **159** | **288+**   | ✅ **All Passing** |

### JavaScript Coverage Details

| Directory   | Statements | Branches | Functions | Lines |
| ----------- | ---------- | -------- | --------- | ----- |
| **lib/**    | 100%       | 100%     | 100%      | 100%  |
| components/ | 0%         | 0%*      | 0%        | 0%    |
| views/      | 0%         | 0%       | 0%        | 0%    |
| **Overall** | 7.6%       | 8.15%    | 7.92%     | 7.5%  |

*Components and views have 0% coverage - these are React components that need additional tests.

### Fully Covered Libraries (100%)

- `src/lib/formatBytes.js` - Byte formatting utilities
- `src/lib/formatEmissions.js` - Emissions formatting utilities
- `src/lib/trees.js` - Tree calculation utilities
- `src/lib/drivingDistance.js` - Driving distance calculation utilities

---

## Quick Start - Unit Tests

Unit tests can be run immediately without any additional setup:

```bash
# Run all unit tests
composer test

# Or
composer test:unit

# Or directly
vendor/bin/phpunit --configuration=phpunit.xml.dist
```

---

## Setting Up Integration Tests

Integration tests require the WordPress test suite to be installed.

### Prerequisites

- MySQL/MariaDB server running
- PHP 8.0+
- Composer dependencies installed
- SVN (for downloading WordPress test library)

### Step 1: Create Test Database

Create a MySQL database for testing (it will be wiped during tests!):

```bash
mysql -u root -p -e "CREATE DATABASE wordpress_tests;"
```

### Step 2: Run Setup Script

Use the provided script to download WordPress and the test library:

```bash
# Usage: ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]

# Example with local MySQL:
./bin/install-wp-tests.sh wordpress_tests root '' localhost latest

# Example with password:
./bin/install-wp-tests.sh wordpress_tests myuser mypassword localhost 6.4
```

### Step 3: Run Integration Tests

```bash
# Set the test directory (if not using default)
export WP_TESTS_DIR=/tmp/wordpress-tests-lib

# Run integration tests
composer test:integration

# Or directly
vendor/bin/phpunit --configuration=phpunit-integration.xml.dist
```

---

## Quick Start - JavaScript Tests

JavaScript tests use Jest with @wordpress/scripts:

```bash
# Run all JavaScript tests
npm test

# Run tests in watch mode
npm run test:watch

# Run tests with coverage
npm run test:coverage

# Update snapshots
npm run test:update
```

### Running All Tests

```bash
# Run both unit and integration tests (PHP)
composer test:all

# Run JavaScript tests
npm test
```

---

## Test Structure

```
tests/                          # PHP Tests
├── bootstrap.php               # Unit test bootstrap (Brain Monkey)
├── AjaxHandlerTest.php         # Unit tests for AJAX handler
├── AdminHandlerTest.php        # Unit tests for Admin handler
├── BackgroundProcessorTest.php
├── CacheTest.php
├── ConstantsTest.php
├── EmissionsTest.php
├── HelpersTest.php
├── LoggerTest.php
├── RestApiHandlerTest.php      # Unit tests for REST API
├── ShortcodesTest.php          # Unit tests for Shortcodes
└── integration/
    ├── bootstrap.php               # Integration test bootstrap (WordPress)
    ├── TestCase.php                # Base test case for integration tests
    ├── AdminHandlerIntegrationTest.php
    ├── EmissionsIntegrationTest.php
    ├── RestApiHandlerIntegrationTest.php
    └── ShortcodesIntegrationTest.php

src/__tests__/                  # JavaScript Tests
├── lib/
│   ├── formatBytes.test.js
│   ├── formatEmissions.test.js
│   ├── trees.test.js
│   └── drivingDistance.test.js
├── components/                 # Component tests (future)
└── views/                      # View tests (future)
```

---

## Available Scripts

### PHP (Composer)

| Script                      | Description                         |
| --------------------------- | ----------------------------------- |
| `composer test`             | Run unit tests                      |
| `composer test:unit`        | Run unit tests (same as above)      |
| `composer test:integration` | Run integration tests               |
| `composer test:all`         | Run both unit and integration tests |
| `composer lint`             | Check code style                    |
| `composer fix`              | Auto-fix code style issues          |

### JavaScript (npm)

| Script                  | Description                    |
| ----------------------- | ------------------------------ |
| `npm test`              | Run all JavaScript tests       |
| `npm run test:watch`    | Run tests in watch mode        |
| `npm run test:coverage` | Run tests with coverage report |
| `npm run test:update`   | Update Jest snapshots          |



---

## Configuration Files

| File                           | Purpose                        |
| ------------------------------ | ------------------------------ |
| `phpunit.xml.dist`             | Unit test configuration        |
| `phpunit-integration.xml.dist` | Integration test configuration |
| `.phpcs.xml.dist`              | Code style rules               |

---

## Writing New Tests

### Unit Tests

Create a new file in `tests/` following this pattern:

```php
<?php

use function Brain\Monkey\Functions\when;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../inc/class-your-class.php';

class YourClassTest extends TestCase
{
    protected function setUp(): void
    {
        Brain\Monkey\setUp();
        
        // Mock WordPress functions
        when('__')->alias(function ($text) { return $text; });
        when('get_option')->justReturn(false);
    }

    protected function tearDown(): void
    {
        Brain\Monkey\tearDown();
    }

    public function test_your_method(): void
    {
        // Your test code
    }
}
```

### Integration Tests

Create a new file in `tests/integration/` extending the base TestCase:

```php
<?php

namespace CarbonfooterPlugin\Tests\Integration;

use CarbonfooterPlugin\YourClass;

class YourClassIntegrationTest extends TestCase
{
    public function test_with_real_wordpress(): void
    {
        // Create test posts
        $post_id = $this->factory->post->create([
            'post_title' => 'Test Post',
            'post_status' => 'publish',
        ]);
        
        // Test with real WordPress functions
        $result = get_post($post_id);
        
        $this->assertEquals('Test Post', $result->post_title);
    }
}
```

---

## Troubleshooting

### "Could not find WordPress test library"

Run the setup script:
```bash
./bin/install-wp-tests.sh wordpress_tests root '' localhost latest
```

### "Class not found" errors in unit tests

Make sure you have the required `require_once` statements at the top of your test file.

### Integration tests failing with database errors

- Verify your test database exists
- Check database credentials in the wp-tests-config.php
- Ensure MySQL/MariaDB is running

### Deprecation warnings

These are mostly from the patchwork library used by Brain Monkey. They don't affect test results.

---

## CI/CD Integration

For GitHub Actions, add this to your workflow:

```yaml
- name: Setup MySQL
  uses: shogo82148/actions-setup-mysql@v1
  with:
    mysql-version: '8.0'

- name: Install dependencies
  run: composer install --prefer-dist --no-progress

- name: Setup WordPress test suite
  run: ./bin/install-wp-tests.sh wordpress_tests root '' localhost latest

- name: Run unit tests
  run: composer test:unit

- name: Run integration tests
  run: composer test:integration
  env:
    WP_TESTS_DIR: /tmp/wordpress-tests-lib
```
