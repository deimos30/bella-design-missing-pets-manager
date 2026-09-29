<?php
/**
 * Tests for installation and the 1.0.x -> 1.1.1 migration.
 *
 * @package Deimos_Lost_Found_Animals
 */

/**
 * Migration tests.
 */
class DEIMLOFO_Migration_Test extends WP_UnitTestCase {

	/**
	 * Settings as 1.0.6 stored them, every value different from the defaults.
	 *
	 * @var array
	 */
	private $legacy_settings = array(
		'columns'                   => 2,
		'limit'                     => 12,
		'show_filters'              => 'no',
		'filter_width'              => 'full',
		'filter_alignment'          => 'right',
		'filter_bar_color'          => '#112233',
		'reset_button_color'        => '#445566',
		'view_details_button_color' => '#778899',
		'default_phone'             => '+44 1234 567890',
		'default_email'             => 'rescue@example.org',
	);

	/**
	 * Legacy meta written for each legacy animal, keyed by field name.
	 *
	 * @var array
	 */
	private $legacy_meta = array(
		array(
			'type'       => 'Dog',
			'status'     => 'Found Today',
			'location'   => 'High Street',
			'breed'      => 'Collie',
			'color'      => 'Black & white',
			'gender'     => 'Male',
			'age'        => '3 years',
			'found_date' => '2026-01-15',
			'microchip'  => 'Yes',
		),
		array(
			'type'       => 'Cat',
			'status'     => 'Available',
			'location'   => 'Park Lane',
			'breed'      => 'Siamese',
			'color'      => 'Cream',
			'gender'     => 'Female',
			'age'        => '1 year',
			'found_date' => '2026-02-01',
			'microchip'  => 'No',
		),
		array(
			'type'       => 'Other',
			'status'     => 'Reunited',
			'location'   => 'Station Road',
			'breed'      => 'Rabbit',
			'color'      => 'Grey',
			'gender'     => '',
			'age'        => '',
			'found_date' => '2025-12-24',
			'microchip'  => 'Unreadable',
		),
	);

	/**
	 * Start every test from a site on which the plugin has never run.
	 */
	public function set_up() {
		parent::set_up();

		foreach ( array( 'deimlofo_settings', 'deimlofo_db_version', 'deimlofo_flush_version', 'deimlofo_migration_error', 'deimlofo_migration_lock', 'deimlofo_settings_migrated', 'lfa_settings', 'lfa_flush_version' ) as $option ) {
			delete_option( $option );
		}
	}

	/**
	 * Create the data a 1.0.6 site holds: legacy options, `animal` posts,
	 * `_lfa_*` meta and featured images.
	 *
	 * @return array Map of post ID => array( 'meta' => ..., 'thumbnail' => ... ).
	 */
	private function create_legacy_site() {
		add_option( 'lfa_settings', $this->legacy_settings );

		$animals = array();
		foreach ( $this->legacy_meta as $index => $meta ) {
			$post_id = self::factory()->post->create(
				array(
					'post_type'   => 'animal',
					'post_title'  => 'Legacy animal ' . $index,
					'post_status' => 'publish',
				)
			);

			foreach ( $meta as $field => $value ) {
				add_post_meta( $post_id, '_lfa_' . $field, $value );
			}

			$thumbnail_id = self::factory()->attachment->create_object(
				'legacy-' . $index . '.jpg',
				$post_id,
				array(
					'post_mime_type' => 'image/jpeg',
					'post_type'      => 'attachment',
				)
			);
			set_post_thumbnail( $post_id, $thumbnail_id );

			$animals[ $post_id ] = array(
				'meta'      => $meta,
				'thumbnail' => $thumbnail_id,
			);
		}

		return $animals;
	}

	/**
	 * Every row of the options table for an option, straight from the database.
	 *
	 * @param string $name Option name.
	 * @return mixed|null
	 */
	private function db_option( $name ) {
		return deimlofo_read_option_from_db( $name );
	}

	/**
	 * Count meta rows for a post and key.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @return int
	 */
	private function count_meta_rows( $post_id, $key ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $post_id, $key )
		);
	}

	/**
	 * Snapshot everything the migration touches.
	 *
	 * @return array
	 */
	private function snapshot() {
		global $wpdb;

		return array(
			'settings' => $this->db_option( 'deimlofo_settings' ),
			'posts'    => $wpdb->get_results( "SELECT ID, post_type, post_title FROM {$wpdb->posts} WHERE post_type IN ( 'animal', 'deimlofo_animal' ) ORDER BY ID", ARRAY_A ),
			'meta'     => $wpdb->get_results( "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE '\\_deimlofo\\_%' OR meta_key LIKE '\\_lfa\\_%' OR meta_key = '_thumbnail_id' ORDER BY post_id, meta_key, meta_id", ARRAY_A ),
		);
	}

	/**
	 * Assert that the legacy animals were fully migrated.
	 *
	 * @param array $animals Output of create_legacy_site().
	 */
	private function assert_animals_migrated( $animals ) {
		foreach ( $animals as $post_id => $animal ) {
			clean_post_cache( $post_id );

			$this->assertSame( 'deimlofo_animal', get_post_type( $post_id ), "Post $post_id post type" );

			foreach ( $animal['meta'] as $field => $value ) {
				$this->assertSame( $value, get_post_meta( $post_id, '_deimlofo_' . $field, true ), "Post $post_id field $field" );
				$this->assertSame( 1, $this->count_meta_rows( $post_id, '_deimlofo_' . $field ), "Post $post_id field $field row count" );
				$this->assertSame( 0, $this->count_meta_rows( $post_id, '_lfa_' . $field ), "Post $post_id legacy $field removed" );
			}

			$this->assertTrue( has_post_thumbnail( $post_id ), "Post $post_id keeps its featured image" );
			$this->assertSame( $animal['thumbnail'], (int) get_post_thumbnail_id( $post_id ) );
			$this->assertSame( 1, $this->count_meta_rows( $post_id, '_thumbnail_id' ) );
		}
	}

	/**
	 * Clean install: defaults are written and the database version recorded.
	 */
	public function test_clean_install_creates_defaults() {
		deimlofo_activate();

		$this->assertSame( deimlofo_default_settings(), $this->db_option( 'deimlofo_settings' ) );
		$this->assertSame( DEIMLOFO_DB_VERSION, $this->db_option( 'deimlofo_db_version' ) );
		$this->assertSame( '1.1.1', DEIMLOFO_DB_VERSION );
		$this->assertNull( $this->db_option( 'lfa_settings' ) );
		$this->assertNull( $this->db_option( 'deimlofo_migration_error' ) );
		$this->assertNull( $this->db_option( 'deimlofo_migration_lock' ) );
	}

	/**
	 * Only the new post type and shortcode are registered.
	 */
	public function test_legacy_post_type_and_shortcode_not_registered() {
		$this->assertTrue( post_type_exists( 'deimlofo_animal' ) );
		$this->assertFalse( post_type_exists( 'animal' ) );
		$this->assertTrue( shortcode_exists( 'deimlofo_animals' ) );
		$this->assertFalse( shortcode_exists( 'lost_found_animals' ) );
	}

	/**
	 * Upgrade from 1.0.6 by deactivating and activating (the 1.1.0 bug path).
	 */
	public function test_upgrade_from_106_via_activation_keeps_everything() {
		$animals = $this->create_legacy_site();

		deimlofo_activate();

		$this->assertSame( $this->legacy_settings, $this->db_option( 'deimlofo_settings' ) );
		$this->assert_animals_migrated( $animals );
		$this->assertNull( $this->db_option( 'lfa_settings' ) );
		$this->assertSame( '1.1.1', $this->db_option( 'deimlofo_db_version' ) );
		$this->assertNull( $this->db_option( 'deimlofo_migration_error' ) );
	}

	/**
	 * Upgrade from 1.0.6 by replacing the files (no activation hook runs).
	 */
	public function test_upgrade_from_106_via_file_update_keeps_everything() {
		$animals = $this->create_legacy_site();

		$this->assertTrue( deimlofo_maybe_upgrade() );

		$this->assertSame( $this->legacy_settings, $this->db_option( 'deimlofo_settings' ) );
		$this->assert_animals_migrated( $animals );
		$this->assertNull( $this->db_option( 'lfa_settings' ) );
		$this->assertSame( '1.1.1', $this->db_option( 'deimlofo_db_version' ) );
	}

	/**
	 * Settings missing from an older lfa_settings get the new defaults.
	 */
	public function test_missing_legacy_settings_get_defaults() {
		// A 1.0.4 install had no width/alignment, button colour or contact settings.
		$legacy = array(
			'columns'            => 3,
			'limit'              => 8,
			'show_filters'       => 'yes',
			'filter_bar_color'   => '#abcdef',
			'reset_button_color' => '#fedcba',
		);
		add_option( 'lfa_settings', $legacy );

		deimlofo_activate();

		$expected = array_merge( deimlofo_default_settings(), $legacy );
		$this->assertSame( $expected, $this->db_option( 'deimlofo_settings' ) );
	}

	/**
	 * Migrated settings go through the same validation as the settings page.
	 */
	public function test_legacy_settings_are_sanitized_like_settings_page() {
		$dirty = array(
			'columns'                   => 9,
			'limit'                     => '-7',
			'show_filters'              => 'maybe',
			'filter_width'              => 'huge',
			'filter_alignment'          => 'top',
			'filter_bar_color'          => 'red',
			'reset_button_color'        => '#12345',
			'view_details_button_color' => '#ABC',
			'default_phone'             => "<b>+48 600</b>\n 100 200",
			'default_email'             => 'not an email',
			'unknown_key'               => 'dropped',
		);
		add_option( 'lfa_settings', $dirty );

		deimlofo_maybe_upgrade();

		$expected = array(
			'columns'                   => 4,
			'limit'                     => -1,
			'show_filters'              => 'no',
			'filter_width'              => 'medium',
			'filter_alignment'          => 'left',
			'filter_bar_color'          => '#f5f5f4',
			'reset_button_color'        => '#e7e5e4',
			'view_details_button_color' => '#ABC',
			'default_phone'             => '+48 600 100 200',
			'default_email'             => '',
		);
		$this->assertSame( $expected, $this->db_option( 'deimlofo_settings' ) );

		// The settings page callback produces the same result for the same input.
		$this->assertSame( $expected, DEIMLOFO_Settings::instance()->sanitize_settings( array_merge( deimlofo_default_settings(), $dirty ) ) );
	}

	/**
	 * Individual sanitization rules.
	 */
	public function test_sanitize_settings_rules() {
		$this->assertSame( 1, deimlofo_sanitize_settings( array( 'columns' => '1' ) )['columns'] );
		$this->assertSame( 4, deimlofo_sanitize_settings( array( 'columns' => 0 ) )['columns'] );
		$this->assertSame( 4, deimlofo_sanitize_settings( array( 'columns' => -2 ) )['columns'] );
		$this->assertSame( -1, deimlofo_sanitize_settings( array( 'limit' => -1 ) )['limit'] );
		$this->assertSame( 20, deimlofo_sanitize_settings( array( 'limit' => '20' ) )['limit'] );
		$this->assertSame( 'yes', deimlofo_sanitize_settings( array( 'show_filters' => 'yes' ) )['show_filters'] );
		$this->assertSame( 'compact', deimlofo_sanitize_settings( array( 'filter_width' => 'compact' ) )['filter_width'] );
		$this->assertSame( 'center', deimlofo_sanitize_settings( array( 'filter_alignment' => 'center' ) )['filter_alignment'] );
		$this->assertSame( '#f5f5f4', deimlofo_sanitize_settings( array( 'filter_bar_color' => array( '#000000' ) ) )['filter_bar_color'] );
		$this->assertSame( deimlofo_default_settings(), deimlofo_sanitize_settings( deimlofo_default_settings() ) );
		// Like the settings page: a missing show_filters value means "no".
		$this->assertSame( array_keys( deimlofo_default_settings() ), array_keys( deimlofo_sanitize_settings( 'not an array' ) ) );
		$this->assertSame( 'no', deimlofo_sanitize_settings( array() )['show_filters'] );
	}

	/**
	 * State left by the 1.1.0 activation bug: default deimlofo_settings exist
	 * next to the user's lfa_settings. The legacy values must win.
	 */
	public function test_default_new_settings_do_not_hide_legacy_settings() {
		add_option( 'deimlofo_settings', deimlofo_default_settings() );
		add_option( 'lfa_settings', $this->legacy_settings );

		deimlofo_activate();

		$this->assertSame( $this->legacy_settings, $this->db_option( 'deimlofo_settings' ) );
		$this->assertNull( $this->db_option( 'lfa_settings' ) );
	}

	/**
	 * Values deliberately saved on the new settings page are kept.
	 */
	public function test_explicit_new_settings_take_priority() {
		add_option( 'deimlofo_settings', array_merge( deimlofo_default_settings(), array( 'columns' => 1 ) ) );
		add_option( 'lfa_settings', $this->legacy_settings );

		deimlofo_maybe_upgrade();

		$expected            = $this->legacy_settings;
		$expected['columns'] = 1;
		$this->assertSame( $expected, $this->db_option( 'deimlofo_settings' ) );
	}

	/**
	 * Running the migration again changes nothing.
	 */
	public function test_second_run_is_a_no_op() {
		$animals = $this->create_legacy_site();

		deimlofo_activate();
		$first = $this->snapshot();

		// Force a full second pass, as if the version had never been stored.
		delete_option( 'deimlofo_db_version' );
		$this->assertTrue( deimlofo_maybe_upgrade() );
		deimlofo_activate();

		$this->assertSame( $first, $this->snapshot() );
		$this->assert_animals_migrated( $animals );
		$this->assertSame( '1.1.1', $this->db_option( 'deimlofo_db_version' ) );
	}

	/**
	 * Upgrading from 1.1.0 keeps the settings and records the new version.
	 */
	public function test_upgrade_from_110() {
		$settings = array_merge( deimlofo_default_settings(), array( 'limit' => 5 ) );
		add_option( 'deimlofo_settings', $settings );
		add_option( 'deimlofo_db_version', '1.1.0' );

		$this->assertTrue( deimlofo_maybe_upgrade() );

		$this->assertSame( $settings, $this->db_option( 'deimlofo_settings' ) );
		$this->assertSame( '1.1.1', $this->db_option( 'deimlofo_db_version' ) );
	}

	/**
	 * A failed settings write keeps the legacy data and allows a retry.
	 */
	public function test_failed_settings_write_keeps_legacy_data_and_retries() {
		$animals = $this->create_legacy_site();

		$block = static function ( $value, $old_value ) {
			return $old_value;
		};
		add_filter( 'pre_update_option_deimlofo_settings', $block, 10, 2 );

		$this->assertFalse( deimlofo_activate_and_report() );

		$this->assertSame( $this->legacy_settings, $this->db_option( 'lfa_settings' ) );
		$this->assertNull( $this->db_option( 'deimlofo_settings' ), 'No default settings may be created while legacy settings are pending.' );
		$this->assertNull( $this->db_option( 'deimlofo_db_version' ) );
		$this->assertIsString( $this->db_option( 'deimlofo_migration_error' ) );
		$this->assertNull( $this->db_option( 'deimlofo_migration_lock' ), 'The lock is released after a failure.' );

		remove_filter( 'pre_update_option_deimlofo_settings', $block, 10 );

		$this->assertTrue( deimlofo_maybe_upgrade() );

		$this->assertSame( $this->legacy_settings, $this->db_option( 'deimlofo_settings' ) );
		$this->assert_animals_migrated( $animals );
		$this->assertNull( $this->db_option( 'lfa_settings' ) );
		$this->assertNull( $this->db_option( 'deimlofo_migration_error' ) );
		$this->assertSame( '1.1.1', $this->db_option( 'deimlofo_db_version' ) );
	}

	/**
	 * A database error while renaming post meta keeps the legacy data.
	 */
	public function test_failed_meta_migration_keeps_legacy_data_and_retries() {
		global $wpdb;

		$animals = $this->create_legacy_site();

		$break = static function ( $query ) use ( $wpdb ) {
			if ( 0 === strpos( $query, "UPDATE `{$wpdb->postmeta}` SET `meta_key`" ) && false !== strpos( $query, "'_lfa_breed'" ) ) {
				return 'SELECT * FROM deimlofo_table_that_does_not_exist';
			}
			return $query;
		};
		add_filter( 'query', $break );
		$suppress = $wpdb->suppress_errors( true );

		$this->assertFalse( deimlofo_maybe_upgrade() );

		$wpdb->suppress_errors( $suppress );
		remove_filter( 'query', $break );

		$this->assertSame( $this->legacy_settings, $this->db_option( 'lfa_settings' ), 'Legacy settings are kept.' );
		$this->assertNull( $this->db_option( 'deimlofo_db_version' ) );
		$this->assertIsString( $this->db_option( 'deimlofo_migration_error' ) );
		foreach ( array_keys( $animals ) as $post_id ) {
			$this->assertSame( 1, $this->count_meta_rows( $post_id, '_lfa_breed' ), 'Legacy breed is kept.' );
		}

		$this->assertTrue( deimlofo_maybe_upgrade() );

		$this->assertSame( $this->legacy_settings, $this->db_option( 'deimlofo_settings' ) );
		$this->assert_animals_migrated( $animals );
		$this->assertNull( $this->db_option( 'lfa_settings' ) );
		$this->assertSame( '1.1.1', $this->db_option( 'deimlofo_db_version' ) );
	}

	/**
	 * A database error while changing the post type keeps the legacy data.
	 */
	public function test_failed_post_type_migration_keeps_legacy_data() {
		global $wpdb;

		$animals = $this->create_legacy_site();

		$break = static function ( $query ) use ( $wpdb ) {
			if ( 0 === strpos( $query, "UPDATE `{$wpdb->posts}` SET `post_type` = 'deimlofo_animal'" ) ) {
				return 'SELECT * FROM deimlofo_table_that_does_not_exist';
			}
			return $query;
		};
		add_filter( 'query', $break );
		$suppress = $wpdb->suppress_errors( true );

		$this->assertFalse( deimlofo_maybe_upgrade() );

		$wpdb->suppress_errors( $suppress );
		remove_filter( 'query', $break );

		$this->assertSame( $this->legacy_settings, $this->db_option( 'lfa_settings' ) );
		$this->assertNull( $this->db_option( 'deimlofo_db_version' ) );
		foreach ( array_keys( $animals ) as $post_id ) {
			clean_post_cache( $post_id );
			$this->assertSame( 'animal', get_post_type( $post_id ) );
		}

		$this->assertTrue( deimlofo_maybe_upgrade() );
		$this->assert_animals_migrated( $animals );
		$this->assertSame( $this->legacy_settings, $this->db_option( 'deimlofo_settings' ) );
	}

	/**
	 * Settings changed between an interrupted run and the retry are not undone.
	 */
	public function test_settings_verified_in_interrupted_run_are_not_rewritten() {
		$this->create_legacy_site();

		// First run: settings are migrated and verified, then the run stops.
		$this->assertTrue( deimlofo_migrate_settings() );
		$this->assertSame( $this->legacy_settings, $this->db_option( 'deimlofo_settings' ) );

		// The administrator changes a value back to its default in the meantime.
		$changed            = $this->legacy_settings;
		$changed['columns'] = 4;
		update_option( 'deimlofo_settings', $changed );

		$this->assertTrue( deimlofo_maybe_upgrade() );

		$this->assertSame( $changed, $this->db_option( 'deimlofo_settings' ) );
		$this->assertNull( $this->db_option( 'deimlofo_settings_migrated' ) );
	}

	/**
	 * A migration interrupted half way (some posts and keys done) resumes.
	 */
	public function test_interrupted_migration_resumes_without_duplicates() {
		global $wpdb;

		$animals  = $this->create_legacy_site();
		$post_ids = array_keys( $animals );

		// Simulate a request that died after moving the posts and a single key.
		$this->assertTrue( deimlofo_migrate_post_type() );
		$wpdb->update( $wpdb->postmeta, array( 'meta_key' => '_deimlofo_status' ), array( 'meta_key' => '_lfa_status' ) );
		// ...and a stale lock left behind by that request.
		add_option( 'deimlofo_migration_lock', (string) ( time() - HOUR_IN_SECONDS ), '', 'no' );
		wp_cache_flush();

		// An edit made in between: an empty new value and a newer non-empty one.
		update_post_meta( $post_ids[0], '_deimlofo_age', '' );
		update_post_meta( $post_ids[1], '_deimlofo_breed', 'Siamese mix' );
		$animals[ $post_ids[1] ]['meta']['breed'] = 'Siamese mix';

		$this->assertTrue( deimlofo_maybe_upgrade() );

		$this->assert_animals_migrated( $animals );
		$this->assertSame( $this->legacy_settings, $this->db_option( 'deimlofo_settings' ) );
		$this->assertNull( $this->db_option( 'lfa_settings' ) );
		$this->assertNull( $this->db_option( 'deimlofo_migration_lock' ) );
	}

	/**
	 * A lock held by a running migration is respected.
	 */
	public function test_active_lock_prevents_concurrent_migration() {
		$animals = $this->create_legacy_site();
		add_option( 'deimlofo_migration_lock', (string) time(), '', 'no' );

		$this->assertFalse( deimlofo_maybe_upgrade() );

		$this->assertSame( $this->legacy_settings, $this->db_option( 'lfa_settings' ) );
		$this->assertNull( $this->db_option( 'deimlofo_db_version' ) );
		foreach ( array_keys( $animals ) as $post_id ) {
			clean_post_cache( $post_id );
			$this->assertSame( 'animal', get_post_type( $post_id ) );
		}
	}

	/**
	 * Menu items pointing at legacy animals are updated.
	 */
	public function test_menu_items_are_migrated() {
		$animals = $this->create_legacy_site();
		$post_id = array_keys( $animals )[0];

		$menu_id = wp_create_nav_menu( 'Main' );
		$item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-type'      => 'post_type',
				'menu-item-object'    => 'animal',
				'menu-item-object-id' => $post_id,
				'menu-item-status'    => 'publish',
			)
		);

		deimlofo_maybe_upgrade();

		$this->assertSame( 'deimlofo_animal', get_post_meta( $item_id, '_menu_item_object', true ) );
		$item = wp_setup_nav_menu_item( get_post( $item_id ) );
		$this->assertFalse( (bool) $item->_invalid );
	}

	/**
	 * `animal` posts of another plugin are left alone on a clean install.
	 */
	public function test_foreign_animal_posts_are_not_touched() {
		$foreign = self::factory()->post->create( array( 'post_type' => 'animal' ) );

		deimlofo_activate();

		clean_post_cache( $foreign );
		$this->assertSame( 'animal', get_post_type( $foreign ) );
		$this->assertSame( '1.1.1', $this->db_option( 'deimlofo_db_version' ) );
	}

	/**
	 * Deactivating and reactivating keeps data and settings.
	 */
	public function test_deactivate_and_reactivate_keep_data() {
		$animals = $this->create_legacy_site();
		deimlofo_activate();
		$before = $this->snapshot();

		deimlofo_deactivate();
		deimlofo_activate();

		$this->assertSame( $before, $this->snapshot() );
		$this->assert_animals_migrated( $animals );
	}
}

/**
 * Run the activation routine and report whether the migration succeeded.
 *
 * @return bool
 */
function deimlofo_activate_and_report() {
	deimlofo_activate();
	return DEIMLOFO_DB_VERSION === deimlofo_read_option_from_db( 'deimlofo_db_version' );
}
