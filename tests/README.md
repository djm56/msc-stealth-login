# MSC Stealth Login - PHPUnit Testing

## Overview

This directory contains the PHPUnit test suite for MSC Stealth Login plugin.

## Requirements

- PHP 7.4+
- Composer
- MySQL/MariaDB (for WordPress test database)
- WordPress test suite (automatically downloaded on first run)

## Setup

### 1. Set up the WordPress test database

```bash
# Create the database
mysql -u root -e "CREATE DATABASE wordpress_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Or for MAMP:
mysql -u root -proot -e "CREATE DATABASE wordpress_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2. Install dependencies

```bash
composer install
```

### 3. Set up WordPress test suite

The WordPress test suite will be automatically set up on first run, or you can manually download it:

```bash
# Create test directory
mkdir -p /tmp/msc-testing

# Download WordPress test suite
cd /tmp/msc-testing
svn checkout https://develop.svn.wordpress.org/trunk/tests/phpunit/includes/ wordpress-tests-lib
svn checkout https://develop.svn.wordpress.org/trunk/tests/phpunit/data/ wordpress-tests-data

# Download WordPress source
svn checkout https://develop.svn.wordpress.org/trunk/src/ wordpress-develop/src

# Create config file
cp /tmp/msc-testing/wordpress-tests-lib/wp-tests-config-sample.php /tmp/msc-testing/wp-tests-config.php

# Edit wp-tests-config.php with your database credentials
```

### 4. Configure wp-tests-config.php

Edit `/tmp/msc-testing/wp-tests-config.php`:

```php
define( 'DB_NAME', 'wordpress_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );  // Set your password
define( 'DB_HOST', 'localhost' );
define( 'ABSPATH', __DIR__ . '/wordpress-develop/src/' );
define( 'WP_CONTENT_DIR', __DIR__ . '/wordpress-develop/src/wp-content' );
```

## Running Tests

### Run all tests
```bash
WP_TESTS_DIR=/tmp/msc-testing/wordpress-tests-lib vendor/bin/phpunit
```

### Run with test dox (nicer output)
```bash
WP_TESTS_DIR=/tmp/msc-testing/wordpress-tests-lib vendor/bin/phpunit --testdox
```

### Run specific test file
```bash
WP_TESTS_DIR=/tmp/msc-testing/wordpress-tests-lib vendor/bin/phpunit tests/settings-test.php
```

### Run specific test
```bash
WP_TESTS_DIR=/tmp/msc-testing/wordpress-tests-lib vendor/bin/phpunit --filter test_default_options
```

### Run with coverage report
```bash
WP_TESTS_DIR=/tmp/msc-testing/wordpress-tests-lib vendor/bin/phpunit --coverage-html coverage
```

## Test Structure

- `bootstrap.php` - Bootstrap file that loads WordPress test suite and the plugin
- `settings-test.php` - Tests for settings/options functionality
- `core-test.php` - Tests for core plugin functionality
- `security-test.php` - Tests for security features (brute force, XML-RPC, REST API)
- `module-test.php` - Tests for module behavior (URL rewriting, redirects)

## Writing Tests

### Basic test structure

```php
<?php
/**
 * Test my feature
 */
class Test_My_Feature extends WP_UnitTestCase {

    protected $plugin;

    public function set_up() {
        parent::set_up();
        $this->plugin = MSCSL\Plugin::instance();
    }

    public function tear_down() {
        // Clean up any test data
        delete_option( 'mscsl_options' );
        parent::tear_down();
    }

    public function test_my_feature() {
        $this->assertTrue( something );
    }
}
```

### Testing private methods

Use reflection:

```php
$reflection = new \ReflectionClass( $this->module );
$method = $reflection->getMethod( 'private_method_name' );
$method->setAccessible( true );
$result = $method->invoke( $this->module );
```

### Testing hooks

```php
$fired = false;
add_action( 'my_hook', function() use ( &$fired ) {
    $fired = true;
} );

do_action( 'my_hook' );

$this->assertTrue( $fired );
```

## Troubleshooting

### "Error establishing database connection"

Make sure MySQL is running and the database credentials in wp-tests-config.php are correct.

### "Could not find functions.php"

Set the WP_TESTS_DIR environment variable:
```bash
export WP_TESTS_DIR=/tmp/msc-testing/wordpress-tests-lib
```

### Tests fail with "Call to undefined function"

The WordPress test suite bootstrap may not have loaded correctly. Make sure bootstrap.php is properly configured.

## CI/CD

In a CI environment, you can use Docker or a CI-specific database setup:

```bash
# Example: Using environment variables
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
mysql -e "CREATE DATABASE IF NOT EXISTS wordpress_test;"
vendor/bin/phpunit
```

## Notes

- Tests use the WordPress UnitTestCase which provides WordPress test fixtures
- Each test should clean up after itself in tearDown()
- Tests do not affect production data
- Tests are excluded from WordPress.org distribution zips
