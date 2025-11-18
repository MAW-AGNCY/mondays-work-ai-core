<?php
/**
 * PHPUnit Bootstrap File
 *
 * Bootstrap file for PHPUnit tests. Loads WordPress test suite and plugin.
 *
 * @package MondaysWork\AI\Core
 * @subpackage Tests
 * @since 1.0.0
 */

// Composer autoloader.
$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
}

// WordPress tests directory.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 *
 * @since 1.0.0
 * @return void
 */
function _manually_load_plugin() {
	// Load plugin dependencies if needed.
	$plugin_file = dirname( __DIR__ ) . '/mondays-work-ai-core.php';
	
	if ( file_exists( $plugin_file ) ) {
		require $plugin_file;
	}
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Set up test environment constants.
 *
 * @since 1.0.0
 * @return void
 */
function _setup_test_environment() {
	// Define test encryption key.
	if ( ! defined( 'MONDAYS_WORK_AI_ENCRYPTION_KEY' ) ) {
		define( 'MONDAYS_WORK_AI_ENCRYPTION_KEY', hash( 'sha256', 'test-encryption-key-for-phpunit', true ) );
	}

	// Define WordPress salts for testing.
	if ( ! defined( 'AUTH_KEY' ) ) {
		define( 'AUTH_KEY', 'test-auth-key-for-phpunit' );
	}

	if ( ! defined( 'SECURE_AUTH_KEY' ) ) {
		define( 'SECURE_AUTH_KEY', 'test-secure-auth-key-for-phpunit' );
	}
}

tests_add_filter( 'muplugins_loaded', '_setup_test_environment', 1 );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

// Load test utilities.
require_once __DIR__ . '/includes/TestCase.php';
require_once __DIR__ . '/includes/FactoryHelper.php';

echo "Bootstrap loaded successfully.\n";
