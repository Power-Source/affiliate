<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "WordPress test suite not found in {$_tests_dir}.\n" );
	fwrite( STDERR, "Run: bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1 latest\n" );
	exit( 1 );
}

if ( filter_var( getenv( 'WP_MULTISITE' ), FILTER_VALIDATE_BOOLEAN ) && ! defined( 'WP_TESTS_MULTISITE' ) ) {
	define( 'WP_TESTS_MULTISITE', true );
}

if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );
}

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () {
		$plugin = 'affiliate/affiliate.php';

		if ( is_multisite() ) {
			update_site_option( 'active_sitewide_plugins', array( $plugin => time() ) );
		} else {
			update_option( 'active_plugins', array( $plugin ) );
		}

		require dirname( __DIR__ ) . '/affiliate.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';

require_once dirname( __DIR__ ) . '/affiliateincludes/classes/affiliateadmin.php';
require_once dirname( __DIR__ ) . '/affiliateincludes/addons/directory.php';
require_once dirname( __DIR__ ) . '/affiliateincludes/addons/kleinanzeigen.php';
require_once dirname( __DIR__ ) . '/affiliateincludes/addons/marketpress.php';
require_once dirname( __DIR__ ) . '/affiliateincludes/addons/membership2.php';
require_once dirname( __DIR__ ) . '/affiliateincludes/addons/supporter.php';

$GLOBALS['affadmin'] = new affiliateadmin();

require_once __DIR__ . '/includes/AffiliateTestCase.php';
