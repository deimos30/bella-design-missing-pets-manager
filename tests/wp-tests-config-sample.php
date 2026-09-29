<?php
/**
 * WordPress test suite configuration.
 *
 * Copy to tests/wp-tests-config.php (or point WP_PHPUNIT__TESTS_CONFIG at your
 * own copy) and adjust. The test suite DROPS every table that uses the prefix
 * below, so never reuse the prefix of a real site.
 *
 * @package Deimos_Lost_Found_Animals
 */

define( 'ABSPATH', getenv( 'WP_CORE_DIR' ) ? rtrim( getenv( 'WP_CORE_DIR' ), '/' ) . '/' : '/tmp/wordpress/' );

define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ? getenv( 'WP_TESTS_DB_NAME' ) : 'wordpress_test' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ? getenv( 'WP_TESTS_DB_USER' ) : 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) ? getenv( 'WP_TESTS_DB_PASSWORD' ) : '' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ? getenv( 'WP_TESTS_DB_HOST' ) : 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = 'dlfut_';

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
define( 'WP_DEBUG', true );
