<?php
/**
 * Admin functionality - columns
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
 * Admin list table columns for the animal post type.
 */
class DEIMLOFO_Admin {

	/**
	 * Single instance
	 *
	 * @var DEIMLOFO_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return DEIMLOFO_Admin
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
		add_filter( 'manage_deimlofo_animal_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_deimlofo_animal_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
		add_filter( 'manage_edit-deimlofo_animal_sortable_columns', array( $this, 'sortable' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'list_table_styles' ) );
	}

	/**
	 * Load the admin stylesheet on the animal list table.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function list_table_styles( $hook_suffix ) {
		if ( 'edit.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen && 'deimlofo_animal' === $screen->post_type ) {
			wp_enqueue_style( 'deimlofo-admin', DEIMLOFO_PLUGIN_URL . 'assets/css/admin.css', array(), DEIMLOFO_VERSION );
		}
	}

	/**
	 * Define the list table columns.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function columns( $columns ) {
		$new                      = array();
		$new['cb']                = isset( $columns['cb'] ) ? $columns['cb'] : '';
		$new['deimlofo_photo']    = __( 'Photo', 'deimos-lost-found-animals' );
		$new['title']             = isset( $columns['title'] ) ? $columns['title'] : __( 'Title', 'deimos-lost-found-animals' );
		$new['deimlofo_status']   = __( 'Status', 'deimos-lost-found-animals' );
		$new['deimlofo_location'] = __( 'Location', 'deimos-lost-found-animals' );
		$new['deimlofo_breed']    = __( 'Breed', 'deimos-lost-found-animals' );
		$new['deimlofo_type']     = __( 'Type', 'deimos-lost-found-animals' );
		$new['date']              = isset( $columns['date'] ) ? $columns['date'] : __( 'Date', 'deimos-lost-found-animals' );

		return $new;
	}

	/**
	 * Render a custom column.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function column_content( $column, $post_id ) {
		switch ( $column ) {
			case 'deimlofo_photo':
				if ( has_post_thumbnail( $post_id ) ) {
					echo wp_kses_post(
						get_the_post_thumbnail(
							$post_id,
							array( 50, 50 ),
							array( 'class' => 'deimlofo-admin-thumb' )
						)
					);
				} else {
					echo '<span class="deimlofo-admin-muted">' . esc_html__( 'No photo', 'deimos-lost-found-animals' ) . '</span>';
				}
				break;

			case 'deimlofo_status':
				$status = get_post_meta( $post_id, '_deimlofo_status', true );
				$badge  = deimlofo_get_badge( $status );
				printf(
					'<span class="deimlofo-admin-badge deimlofo-status--%1$s">%2$s</span>',
					esc_attr( $badge['slug'] ),
					esc_html( $badge['text'] )
				);
				break;

			case 'deimlofo_location':
				echo esc_html( get_post_meta( $post_id, '_deimlofo_location', true ) );
				break;

			case 'deimlofo_breed':
				echo esc_html( get_post_meta( $post_id, '_deimlofo_breed', true ) );
				break;

			case 'deimlofo_type':
				$type = get_post_meta( $post_id, '_deimlofo_type', true );
				echo esc_html( $type ? $type : __( 'Dog', 'deimos-lost-found-animals' ) );
				break;
		}
	}

	/**
	 * Mark the status column as sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function sortable( $columns ) {
		$columns['deimlofo_status'] = 'deimlofo_status';
		return $columns;
	}
}

DEIMLOFO_Admin::instance();
