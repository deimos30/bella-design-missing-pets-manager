<?php
/**
 * Custom Post Type: Animal
 *
 * @package Deimos_Lost_Found_Animals
 * @author  Wojtek Kobylecki / Bella Design Studio
 * @version 1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LFA_Post_Type {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register' ) );
    }

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
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array( 'slug' => 'animal' ),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-pets',
            'supports'            => array( 'title', 'editor', 'thumbnail' ),
            'show_in_rest'        => false,
        );

        register_post_type( 'animal', $args );
    }
}

LFA_Post_Type::instance();
