<?php
/**
 * PHPUnit bootstrap file for MSC Stealth Login.
 *
 * @package MSCSL
 */

/**
 * PHPUnit bootstrap file
 */
define( 'MSCSL_PLUGIN_VERSION', '1.3.0' );
define( 'MSCSL_PLUGIN_FILE', dirname( __DIR__ ) . '/msc-stealth-login.php' );
define( 'MSCSL_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'MSCSL_PLUGIN_URL', 'http://example.org/wp-content/plugins/msc-stealth-login/' );
define( 'MSCSL_OPTION_KEY', 'mscsl_options' );

// Composer autoload
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// WordPress test suite
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
    // Default: look in the standard temp location
    $_tests_dir = '/tmp/msc-testing/wordpress-tests-lib';
}

// Fallback: check alternative paths
if ( ! file_exists( $_tests_dir . '/functions.php' ) ) {
    // Try the develop repository structure
    $_tests_dir_alt = '/tmp/msc-testing/wordpress-tests-lib';
    if ( file_exists( $_tests_dir_alt . '/functions.php' ) ) {
        $_tests_dir = $_tests_dir_alt;
    }
}

// Also ensure ABSPATH is set for WordPress
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/msc-testing/wordpress-develop/src/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
    define( 'WP_CONTENT_DIR', '/tmp/msc-testing/wordpress-develop/src/wp-content' );
}

if ( ! file_exists( $_tests_dir . '/functions.php' ) ) {
    echo "Could not find $_tests_dir/functions.php" . PHP_EOL;
    echo "Please run the setup script or set WP_TESTS_DIR environment variable." . PHP_EOL;
    exit( 1 );
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    require dirname( __DIR__ ) . '/msc-stealth-login.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment
require $_tests_dir . '/bootstrap.php';
