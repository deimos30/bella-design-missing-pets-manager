<?php
/**
 * Custom Post Type: Animal
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
 * Registers the animal custom post type.
 */
class DEIMLOFO_Post_Type {

	/**
	 * Single instance
	 *
	 * @var DEIMLOFO_Post_Type|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return DEIMLOFO_Post_Type
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register the deimlofo_animal post type.
	 *
	 * @return void
	 */
	public function register() {
		$labels = array(
			'name'               => __( 'Animals', 'deimos-lost-found-animals' ),
			'singular_name'      => __( 'Animal', 'deimos-lost-found-animals' ),
			'menu_name'          => __( 'Lost & Found Animals', 'deimos-lost-found-animals' ),
			'add_new'            => __( 'Add New', 'deimos-lost-found-animals' ),
			'add_new_item'       => __( 'Add New Animal', 'deimos-lost-found-animals' ),
			'edit_item'          => __( 'Edit Animal', 'deimos-lost-found-animals' ),
			'new_item'           => __( 'New Animal', 'deimos-lost-found-animals' ),
			'view_item'          => __( 'View Animal', 'deimos-lost-found-animals' ),
			'search_items'       => __( 'Search Animals', 'deimos-lost-found-animals' ),
			'not_found'          => __( 'No animals found', 'deimos-lost-found-animals' ),
			'not_found_in_trash' => __( 'No animals found in trash', 'deimos-lost-found-animals' ),
			'all_items'          => __( 'All Animals', 'deimos-lost-found-animals' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'animal' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-pets',
			'supports'           => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'deimlofo_animal', $args );
	}
}

DEIMLOFO_Post_Type::instance();
