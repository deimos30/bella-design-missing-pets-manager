<?php
/**
 * Admin functionality - columns
 *
 * @package Deimos_Lost_Found_Animals
 * @author  Wojtek Kobylecki / Bella Design Studio
 * @version 1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LFA_Admin {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'manage_animal_posts_columns', array( $this, 'columns' ) );
        add_action( 'manage_animal_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
        add_filter( 'manage_edit-animal_sortable_columns', array( $this, 'sortable' ) );
    }

    public function columns( $columns ) {
        $new               = array();
        $new['cb']         = $columns['cb'];
        $new['lfa_photo']  = __( 'Photo', 'deimos-lost-found-animals' );
        $new['title']      = $columns['title'];
        $new['lfa_status'] = __( 'Status', 'deimos-lost-found-animals' );
        $new['lfa_location'] = __( 'Location', 'deimos-lost-found-animals' );
        $new['lfa_breed']  = __( 'Breed', 'deimos-lost-found-animals' );
        $new['lfa_type']   = __( 'Type', 'deimos-lost-found-animals' );
        $new['date']       = $columns['date'];
        return $new;
    }

    public function column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'lfa_photo':
                if ( has_post_thumbnail( $post_id ) ) {
                    echo get_the_post_thumbnail( $post_id, array( 50, 50 ), array( 'style' => 'border-radius:4px;' ) );
                } else {
                    echo '<span style="color:#999;">' . esc_html__( 'No photo', 'deimos-lost-found-animals' ) . '</span>';
                }
                break;
            case 'lfa_status':
                $status = get_post_meta( $post_id, '_lfa_status', true );
                $badge  = lfa_get_badge( $status );
                echo '<span style="background:' . esc_attr( $badge['color'] ) . ';color:#fff;padding:3px 8px;border-radius:3px;font-size:11px;">' . esc_html( $badge['text'] ) . '</span>';
                break;
            case 'lfa_location':
                echo esc_html( get_post_meta( $post_id, '_lfa_location', true ) );
                break;
            case 'lfa_breed':
                echo esc_html( get_post_meta( $post_id, '_lfa_breed', true ) );
                break;
            case 'lfa_type':
                $type = get_post_meta( $post_id, '_lfa_type', true );
                echo esc_html( $type ? $type : 'Dog' );
                break;
        }
    }

    public function sortable( $columns ) {
        $columns['lfa_status'] = 'lfa_status';
        return $columns;
    }
}

LFA_Admin::instance();
