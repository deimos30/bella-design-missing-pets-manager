<?php
/**
 * One-time data migration from the 1.0.x "lfa" naming to the 1.1.0 "deimlofo" naming.
 *
 * This file is the ONLY place in the plugin where the legacy `animal` post type,
 * the legacy `_lfa_*` post meta keys and the legacy `lfa_settings` option are
 * referenced. Nothing here registers the old post type or the old shortcode; the
 * legacy names appear purely as migration source values.
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
 * Legacy post meta keys mapped to their 1.1.0 equivalents.
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
 * Run the 1.0.6 -> 1.1.0 migration when needed.
 *
 * The routine is idempotent: it is safe to call repeatedly, and a second run is
 * a no-op because no legacy rows remain after the first successful pass. Legacy
 * options are removed only once every step has completed without error.
 *
 * @return void
 */
function deimlofo_maybe_upgrade() {
	$installed = get_option( 'deimlofo_db_version', '' );

	if ( DEIMLOFO_DB_VERSION === $installed ) {
		return;
	}

	$posts_ok    = deimlofo_migrate_post_type();
	$meta_ok     = deimlofo_migrate_post_meta();
	$settings_ok = deimlofo_migrate_settings();

	if ( $posts_ok && $meta_ok && $settings_ok ) {
		// Legacy data is fully carried over, so the old records can go.
		delete_option( 'lfa_settings' );
		delete_option( 'lfa_flush_version' );

		update_option( 'deimlofo_db_version', DEIMLOFO_DB_VERSION );

		// Post type slug changed, so rewrite rules must be rebuilt once.
		flush_rewrite_rules();
	}
}
add_action( 'admin_init', 'deimlofo_maybe_upgrade' );

/**
 * Move legacy `animal` posts onto the `deimlofo_animal` post type.
 *
 * @return bool True on success.
 */
function deimlofo_migrate_post_type() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration; no cache exists for legacy rows.
	$post_ids = $wpdb->get_col(
		$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'animal' )
	);

	if ( empty( $post_ids ) ) {
		return true;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration.
	$updated = $wpdb->update(
		$wpdb->posts,
		array( 'post_type' => 'deimlofo_animal' ),
		array( 'post_type' => 'animal' ),
		array( '%s' ),
		array( '%s' )
	);

	if ( false === $updated ) {
		return false;
	}

	foreach ( $post_ids as $post_id ) {
		clean_post_cache( (int) $post_id );
	}

	return true;
}

/**
 * Rename legacy `_lfa_*` post meta keys to `_deimlofo_*`.
 *
 * Where a post already carries the new key, the legacy row is dropped instead of
 * renamed so no duplicate meta is created.
 *
 * @return bool True on success.
 */
function deimlofo_migrate_post_meta() {
	global $wpdb;

	$touched = array();

	foreach ( deimlofo_legacy_meta_map() as $old_key => $new_key ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration.
		$post_ids = $wpdb->get_col(
			$wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", $old_key )
		);

		if ( empty( $post_ids ) ) {
			continue;
		}

		/*
		 * Drop legacy rows whose target key already exists. The inner query is
		 * wrapped in a derived table because MySQL cannot reference the table
		 * being modified directly in a subquery.
		 */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration.
		$deleted = $wpdb->query(
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

		if ( false === $deleted ) {
			return false;
		}

		// Rename whatever legacy rows are left.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time migration.
		$renamed = $wpdb->update(
			$wpdb->postmeta,
			array( 'meta_key' => $new_key ),
			array( 'meta_key' => $old_key ),
			array( '%s' ),
			array( '%s' )
		);

		if ( false === $renamed ) {
			return false;
		}

		foreach ( $post_ids as $post_id ) {
			$touched[ (int) $post_id ] = true;
		}
	}

	foreach ( array_keys( $touched ) as $post_id ) {
		clean_post_cache( $post_id );
		wp_cache_delete( $post_id, 'post_meta' );
	}

	return true;
}

/**
 * Copy the legacy `lfa_settings` option to `deimlofo_settings`.
 *
 * The legacy values are only used when the new option does not exist yet, so a
 * configuration saved under 1.1.0 is never overwritten.
 *
 * @return bool True on success.
 */
function deimlofo_migrate_settings() {
	$legacy = get_option( 'lfa_settings', null );

	if ( ! is_array( $legacy ) || empty( $legacy ) ) {
		return true;
	}

	$current = get_option( 'deimlofo_settings', null );

	if ( is_array( $current ) && ! empty( $current ) ) {
		// Already configured under the new name; leave it untouched.
		return true;
	}

	return (bool) update_option( 'deimlofo_settings', array_merge( deimlofo_default_settings(), $legacy ) );
}
