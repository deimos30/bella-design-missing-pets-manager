<?php
/**
 * PHPUnit bootstrap.
 *
 * @package Deimos_Lost_Found_Animals
 */

require_once dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

if ( ! getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );
}

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';

require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__ ) . '/deimos-lost-found-animals.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';
