<?php
/**
 * Data migration from the 1.0.x "lfa" naming to the "deimlofo" naming.
 *
 * This file is the ONLY place in the plugin where the legacy `animal` post type,
 * the legacy `_lfa_*` post meta keys and the legacy `lfa_settings` option are
 * referenced. Nothing here registers the old post type or the old shortcode; the
 * legacy names appear purely as migration source values.
 *
 * How the migration stays safe:
 *
 * - It runs under a lock, so two requests never migrate at the same time.
 * - Every step is idempotent and can be repeated after an interrupted run.
 * - Settings are sanitized exactly like the settings screen does it, written,
 *   and then read back from the database and compared before anything else
 *   happens.
 * - The legacy `lfa_settings` option is deleted only after the settings, the
 *   posts and the post meta have all been verified.
 * - `deimlofo_db_version` is written last. If any step fails it is not written,
 *   the legacy data stays in place and the migration is retried on the next
 *   request.
 *
 * @package   Deimos_Lost_Found_Animals
 * @author    Wojtek Kobylecki
 * @copyright Copyright (c) 2026 Wojtek Kobylecki
 * @license   GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy post meta keys mapped to their new equivalents.
 *
 * @return array Associative array of old meta key => new meta key.
 */
function deimlofo_legacy_meta_map() {
	$fields = array( 'type', 'status', 'location', 'breed', 'color', 'gender', 'age', 'found_date', 'microchip' );
	$map    = array();

	foreach ( $fields as $field ) {
		$map[ '_lfa_' . $field ] = '_deimlofo_' . $field;
	}

	return $map;
}

/**
 * Run the 1.0.x migration when the stored database version is out of date.
 *
 * Safe to call on every request: once the migration has succeeded it returns
 * after a single (autoloaded) option read.
 *
 * @return bool True when the database is up to date, false when the migration
 *              failed or is currently running in another request.
 */
function deimlofo_maybe_upgrade() {
	if ( DEIMLOFO_DB_VERSION === get_option( 'deimlofo_db_version', '' ) ) {
		return true;
	}

	if ( ! deimlofo_acquire_migration_lock() ) {
		return false;
	}

	$result = deimlofo_run_migration();

	deimlofo_release_migration_lock();

	if ( is_wp_error( $result ) ) {
		update_option( 'deimlofo_migration_error', $result->get_error_message(), false );
		return false;
	}

	delete_option( 'deimlofo_migration_error' );

	return true;
}
add_action( 'init', 'deimlofo_maybe_upgrade', 5 );

/**
 * Perform every migration step, verify the result and record the new version.
 *
 * @return true|WP_Error
 */
function deimlofo_run_migration() {
	if ( deimlofo_has_legacy_data() ) {
		$steps = array(
			'deimlofo_migrate_settings',
			'deimlofo_migrate_post_type',
			'deimlofo_migrate_menu_items',
			'deimlofo_migrate_post_meta',
			'deimlofo_verify_legacy_content_migrated',
		);

		foreach ( $steps as $step ) {
			$result = call_user_func( $step );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		// Everything is verified; only now may the legacy options go.
		delete_option( 'lfa_settings' );
		delete_option( 'lfa_flush_version' );

		if ( null !== deimlofo_read_option_from_db( 'lfa_settings' ) ) {
			return new WP_Error( 'deimlofo_legacy_cleanup', __( 'the legacy settings could not be removed', 'deimos-lost-found-animals' ) );
		}
	}

	delete_option( 'deimlofo_settings_migrated' );

	update_option( 'deimlofo_db_version', DEIMLOFO_DB_VERSION );

	if ( DEIMLOFO_DB_VERSION !== deimlofo_read_option_from_db( 'deimlofo_db_version' ) ) {
		return new WP_Error( 'deimlofo_db_version', __( 'the database version could not be saved', 'deimos-lost-found-animals' ) );
	}

	// The post type slug may have changed, so rewrite rules are rebuilt once
	// DEIMLOFO_Plugin::maybe_flush_rewrite_rules() runs later on this request.
	delete_option( 'deimlofo_flush_version' );

	return true;
}

/**
 * Whether the site holds data written by a 1.0.x release of the plugin.
 *
 * Version 1.0.x always created `lfa_settings` on activation and stored every
 * animal field as `_lfa_*` post meta, so either marker identifies an old
 * install. Legacy posts are only migrated when a marker is present, which keeps
 * the plugin away from `animal` posts that belong to some other plugin.
 *
 * @return bool
 */
function deimlofo_has_legacy_data() {
	return null !== deimlofo_read_option_from_db( 'lfa_settings' ) || deimlofo_has_legacy_meta();
}

/**
 * Read an option straight from the database, bypassing every cache.
 *
 * Used to confirm that a write really reached the database before the legacy
 * data it replaces is removed.
 *
 * @param string $name Option name.
 * @return mixed|null Unserialized value, or null when the option does not exist.
 */
function deimlofo_read_option_from_db( $name ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The point of this read is to bypass the options cache.
	$row = $wpdb->get_row(
		$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", $name )
	);

	if ( null === $row ) {
		return null;
	}

	return maybe_unserialize( $row->option_value );
}

/**
 * Take the migration lock.
 *
 * The lock is a plain row in the options table inserted with INSERT IGNORE, so
 * only one request can obtain it. A lock older than ten minutes is treated as
 * left over from an interrupted request and replaced.
 *
 * @return bool True when the lock was obtained.
 */
function deimlofo_acquire_migration_lock() {
	global $wpdb;

	$now = time();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Removing a stale lock must be atomic.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name = %s AND CAST( option_value AS UNSIGNED ) < %d",
			'deimlofo_migration_lock',
			$now - 10 * MINUTE_IN_SECONDS
		)
	);

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- add_option() is not atomic, INSERT IGNORE is.
	$inserted = $wpdb->query(
		$wpdb->prepare(
			"INSERT IGNORE INTO {$wpdb->options} ( option_name, option_value, autoload ) VALUES ( %s, %s, 'no' )",
			'deimlofo_migration_lock',
			(string) $now
		)
	);

	wp_cache_delete( 'deimlofo_migration_lock', 'options' );
	wp_cache_delete( 'notoptions', 'options' );

	return 1 === $inserted;
}

/**
 * Release the migration lock.
 *
 * @return void
 */
function deimlofo_release_migration_lock() {
	delete_option( 'deimlofo_migration_lock' );
}

/**
 * Move the legacy `lfa_settings` values into `deimlofo_settings`.
 *
 * The result is built from, in increasing priority:
 *
 * 1. the current defaults, which fill settings that did not exist in 1.0.x;
 * 2. the legacy `lfa_settings` values;
 * 3. values in `deimlofo_settings` that differ from the defaults, which can only
 *    have been saved deliberately on the new settings screen.
 *
 * A `deimlofo_settings` option that merely holds the defaults (for example one
 * created by the 1.1.0 activation routine) therefore never hides the user's
 * legacy configuration. Everything is passed through deimlofo_sanitize_settings(),
 * saved, and read back from the database for verification.
 *
 * @return true|WP_Error
 */
function deimlofo_migrate_settings() {
	if ( get_option( 'deimlofo_settings_migrated' ) ) {
		// Verified by an earlier, interrupted run; later edits must not be undone.
		return true;
	}

	$legacy = deimlofo_read_option_from_db( 'lfa_settings' );

	if ( null === $legacy ) {
		return true;
	}

	if ( ! is_array( $legacy ) ) {
		$legacy = array();
	}

	$defaults = deimlofo_default_settings();
	$current  = deimlofo_read_option_from_db( 'deimlofo_settings' );
	$explicit = array();

	if ( is_array( $current ) ) {
		foreach ( deimlofo_sanitize_settings( array_merge( $defaults, $current ) ) as $key => $value ) {
			if ( array_key_exists( $key, $current ) && $defaults[ $key ] !== $value ) {
				$explicit[ $key ] = $value;
			}
		}
	}

	$legacy   = array_intersect_key( $legacy, $defaults );
	$settings = deimlofo_sanitize_settings( array_merge( $defaults, $legacy, $explicit ) );

	update_option( 'deimlofo_settings', $settings );

	if ( deimlofo_read_option_from_db( 'deimlofo_settings' ) !== $settings ) {
		return new WP_Error( 'deimlofo_settings', __( 'the settings could not be saved', 'deimos-lost-found-animals' ) );
	}

	update_option( 'deimlofo_settings_migrated', 1, false );

	return true;
}

/**
 * Move legacy `animal` posts onto the `deimlofo_animal` post type.
 *
 * @return true|WP_Error
 */
function deimlofo_migrate_post_type() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration of legacy rows; no API exists for changing a post type in bulk.
	$post_ids = $wpdb->get_col(
		$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'animal' )
	);

	if ( empty( $post_ids ) ) {
		return true;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration of legacy rows.
	$updated = $wpdb->update(
		$wpdb->posts,
		array( 'post_type' => 'deimlofo_animal' ),
		array( 'post_type' => 'animal' ),
		array( '%s' ),
		array( '%s' )
	);

	foreach ( $post_ids as $post_id ) {
		clean_post_cache( (int) $post_id );
	}

	if ( false === $updated ) {
		return new WP_Error( 'deimlofo_post_type', __( 'the animal posts could not be updated', 'deimos-lost-found-animals' ) );
	}

	return true;
}

/**
 * Point navigation menu items at the renamed post type.
 *
 * Menu items that link to a legacy animal post or to the legacy animal archive
 * store the post type name in `_menu_item_object`; without this step they would
 * be flagged as invalid once the posts move to `deimlofo_animal`.
 *
 * @return true|WP_Error
 */
function deimlofo_migrate_menu_items() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration of legacy rows.
	$item_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT object_meta.post_id
			 FROM {$wpdb->postmeta} AS object_meta
			 INNER JOIN {$wpdb->postmeta} AS type_meta
				ON type_meta.post_id = object_meta.post_id
				AND type_meta.meta_key = %s
				AND type_meta.meta_value IN ( %s, %s )
			 WHERE object_meta.meta_key = %s AND object_meta.meta_value = %s",
			'_menu_item_type',
			'post_type',
			'post_type_archive',
			'_menu_item_object',
			'animal'
		)
	);

	foreach ( $item_ids as $item_id ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration of legacy rows.
		$updated = $wpdb->update(
			$wpdb->postmeta,
			array( 'meta_value' => 'deimlofo_animal' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- One-off update by post ID.
			array(
				'post_id'    => (int) $item_id,
				'meta_key'   => '_menu_item_object', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off update by post ID.
				'meta_value' => 'animal', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- One-off update by post ID.
			),
			array( '%s' ),
			array( '%d', '%s', '%s' )
		);

		wp_cache_delete( (int) $item_id, 'post_meta' );

		if ( false === $updated ) {
			return new WP_Error( 'deimlofo_menu_items', __( 'the menu items could not be updated', 'deimos-lost-found-animals' ) );
		}
	}

	return true;
}

/**
 * Rename legacy `_lfa_*` post meta keys to `_deimlofo_*`.
 *
 * When a post carries both keys (possible only after an interrupted migration
 * followed by an edit), a non-empty new value wins because it is the more
 * recent one; an empty new value is replaced by the legacy value.
 *
 * @return true|WP_Error
 */
function deimlofo_migrate_post_meta() {
	global $wpdb;

	$touched = array();
	$error   = new WP_Error( 'deimlofo_post_meta', __( 'the animal details could not be updated', 'deimos-lost-found-animals' ) );

	foreach ( deimlofo_legacy_meta_map() as $old_key => $new_key ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration of legacy rows.
		$post_ids = $wpdb->get_col(
			$wpdb->prepare( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", $old_key )
		);

		if ( empty( $post_ids ) ) {
			continue;
		}

		foreach ( $post_ids as $post_id ) {
			$touched[ (int) $post_id ] = true;
		}

		/*
		 * The subqueries are wrapped in derived tables because MySQL cannot
		 * reference the table being modified directly in a subquery.
		 */

		// Drop empty new values that a legacy value is about to replace.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration of legacy rows.
		$deleted_empty = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta}
				 WHERE meta_key = %s
				 AND meta_value = ''
				 AND post_id IN (
					SELECT post_id FROM (
						SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s
					) AS legacy
				 )",
				$new_key,
				$old_key
			)
		);

		// Drop legacy rows whose post already has a (non-empty) new value.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration of legacy rows.
		$deleted_legacy = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->postmeta}
				 WHERE meta_key = %s
				 AND post_id IN (
					SELECT post_id FROM (
						SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s
					) AS existing
				 )",
				$old_key,
				$new_key
			)
		);

		// Rename whatever legacy rows are left.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration of legacy rows.
		$renamed = $wpdb->update(
			$wpdb->postmeta,
			array( 'meta_key' => $new_key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off rename of legacy keys.
			array( 'meta_key' => $old_key ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off rename of legacy keys.
			array( '%s' ),
			array( '%s' )
		);

		if ( false === $deleted_empty || false === $deleted_legacy || false === $renamed ) {
			deimlofo_clean_meta_caches( array_keys( $touched ) );
			return $error;
		}
	}

	deimlofo_clean_meta_caches( array_keys( $touched ) );

	return true;
}

/**
 * Clear the caches of posts whose meta was changed with direct queries.
 *
 * @param int[] $post_ids Post IDs.
 * @return void
 */
function deimlofo_clean_meta_caches( $post_ids ) {
	foreach ( $post_ids as $post_id ) {
		wp_cache_delete( $post_id, 'post_meta' );
		clean_post_cache( $post_id );
	}
}

/**
 * Confirm that no legacy posts or legacy post meta remain.
 *
 * @return true|WP_Error
 */
function deimlofo_verify_legacy_content_migrated() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Verification must see the database, not a cache.
	$legacy_posts = (int) $wpdb->get_var(
		$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", 'animal' )
	);

	if ( 0 !== $legacy_posts || deimlofo_has_legacy_meta() ) {
		return new WP_Error( 'deimlofo_verify', __( 'some animals still use the old format', 'deimos-lost-found-animals' ) );
	}

	return true;
}

/**
 * Whether any legacy `_lfa_*` post meta rows remain.
 *
 * @return bool
 */
function deimlofo_has_legacy_meta() {
	global $wpdb;

	foreach ( array_keys( deimlofo_legacy_meta_map() ) as $old_key ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Migration check; must see the database, not a cache.
		$meta_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT 1", $old_key )
		);

		if ( null !== $meta_id ) {
			return true;
		}
	}

	return false;
}

/**
 * Tell administrators when the migration could not be completed.
 *
 * @return void
 */
function deimlofo_migration_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$error = get_option( 'deimlofo_migration_error', '' );
	if ( '' === $error || ! is_string( $error ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html(
			sprintf(
				/* translators: %s: reason the migration failed. */
				__( 'Deimos Lost & Found Animals could not finish updating data from version 1.0.x (%s). Your original data has been kept and the update will be retried automatically.', 'deimos-lost-found-animals' ),
				$error
			)
		)
	);
}
add_action( 'admin_notices', 'deimlofo_migration_notice' );
