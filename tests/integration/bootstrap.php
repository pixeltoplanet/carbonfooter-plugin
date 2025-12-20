<?php
/**
 * WordPress Integration Test Bootstrap
 * 
 * This bootstrap loads WordPress and the plugin for integration testing.
 * It requires a working WordPress test database.
 * 
 * @package CarbonFooter
 */

// Composer autoloader
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

// Path to the WordPress test library
$_tests_dir = getenv('WP_TESTS_DIR');
if (! $_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Try to find wordpress-develop-tests
if (! file_exists($_tests_dir . '/includes/functions.php')) {
    // Try the composer package
    $_tests_dir = dirname(dirname(__DIR__)) . '/vendor/wp-phpunit/wp-phpunit';
}

if (! file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find WordPress test library. Please run:" . PHP_EOL;
    echo "  export WP_TESTS_DIR=/path/to/wordpress-tests-lib" . PHP_EOL;
    echo "  or set up WordPress test suite." . PHP_EOL;
    exit(1);
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    require dirname(dirname(__DIR__)) . '/carbonfooter.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load our test base class
require_once __DIR__ . '/TestCase.php';
