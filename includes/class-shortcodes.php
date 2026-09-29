<?php
/**
 * Shortcodes for displaying animals (simplified - single image only)
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
 * Shortcode handler.
 */
class DEIMLOFO_Shortcodes {

	/**
	 * Single instance
	 *
	 * @var DEIMLOFO_Shortcodes|null
	 */
	private static $instance = null;

	/**
	 * Number of rendered shortcode instances on the current request.
	 *
	 * Used to keep markup unique when a page contains more than one grid.
	 *
	 * @var int
	 */
	private $render_count = 0;

	/**
	 * Get instance
	 *
	 * @return DEIMLOFO_Shortcodes
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
		add_shortcode( 'deimlofo_animals', array( $this, 'render_grid' ) );
		add_filter( 'single_template', array( $this, 'single_template' ) );
	}

	/**
	 * Render the animals grid.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_grid( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'        => deimlofo_get_setting( 'limit', -1 ),
				'status'       => '',
				'columns'      => deimlofo_get_setting( 'columns', 4 ),
				'show_filters' => deimlofo_get_setting( 'show_filters', 'yes' ),
			),
			$atts,
			'deimlofo_animals'
		);

		$show_filters = ! in_array( (string) $atts['show_filters'], array( 'false', 'no', '0', '' ), true );

		$args = array(
			'post_type'      => 'deimlofo_animal',
			'posts_per_page' => intval( $atts['limit'] ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// Only allow a known status to reach the meta query.
		$status = sanitize_text_field( (string) $atts['status'] );
		if ( '' !== $status && in_array( $status, deimlofo_get_statuses(), true ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Status filtering is the documented purpose of the shortcode.
				array(
					'key'   => '_deimlofo_status',
					'value' => $status,
				),
			);
		}

		$query = new WP_Query( $args );

		$columns = intval( $atts['columns'] );
		if ( $columns < 1 || $columns > 4 ) {
			$columns = 4;
		}

		++$this->render_count;

		ob_start();
		?>
		<div class="deimlofo-container" data-deimlofo-instance="<?php echo esc_attr( $this->render_count ); ?>">

			<?php if ( $show_filters ) : ?>
			<div class="deimlofo-filters">
				<select class="deimlofo-select deimlofo-filter-status" aria-label="<?php esc_attr_e( 'Filter by status', 'deimos-lost-found-animals' ); ?>">
					<option value=""><?php esc_html_e( 'All Status', 'deimos-lost-found-animals' ); ?></option>
					<option value="Found Today"><?php esc_html_e( 'Found Today', 'deimos-lost-found-animals' ); ?></option>
					<option value="Found"><?php esc_html_e( 'Found', 'deimos-lost-found-animals' ); ?></option>
					<option value="Available"><?php esc_html_e( 'Available', 'deimos-lost-found-animals' ); ?></option>
					<option value="Reunited"><?php esc_html_e( 'Reunited', 'deimos-lost-found-animals' ); ?></option>
					<option value="Not Available"><?php esc_html_e( 'Not Available', 'deimos-lost-found-animals' ); ?></option>
				</select>

				<select class="deimlofo-select deimlofo-filter-gender" aria-label="<?php esc_attr_e( 'Filter by gender', 'deimos-lost-found-animals' ); ?>">
					<option value=""><?php esc_html_e( 'All Genders', 'deimos-lost-found-animals' ); ?></option>
					<option value="Male"><?php esc_html_e( 'Male', 'deimos-lost-found-animals' ); ?></option>
					<option value="Female"><?php esc_html_e( 'Female', 'deimos-lost-found-animals' ); ?></option>
				</select>

				<select class="deimlofo-select deimlofo-sort" aria-label="<?php esc_attr_e( 'Sort animals', 'deimos-lost-found-animals' ); ?>">
					<option value="newest"><?php esc_html_e( 'Newest First', 'deimos-lost-found-animals' ); ?></option>
					<option value="oldest"><?php esc_html_e( 'Oldest First', 'deimos-lost-found-animals' ); ?></option>
					<option value="name-asc"><?php esc_html_e( 'Name A-Z', 'deimos-lost-found-animals' ); ?></option>
					<option value="name-desc"><?php esc_html_e( 'Name Z-A', 'deimos-lost-found-animals' ); ?></option>
				</select>

				<button type="button" class="deimlofo-reset"><?php esc_html_e( 'Reset', 'deimos-lost-found-animals' ); ?></button>

				<span class="deimlofo-count">
					<?php
					printf(
						/* translators: %s: number of animals currently shown. */
						esc_html__( 'Showing %s animal(s)', 'deimos-lost-found-animals' ),
						'<span class="deimlofo-count-value">' . esc_html( $query->found_posts ) . '</span>'
					);
					?>
				</span>
			</div>
			<?php endif; ?>

			<div class="deimlofo-grid deimlofo-cols-<?php echo esc_attr( $columns ); ?>">
				<?php
				if ( $query->have_posts() ) :
					while ( $query->have_posts() ) :
						$query->the_post();
						$this->render_card( get_the_ID() );
					endwhile;
					wp_reset_postdata();
				else :
					?>
				<div class="deimlofo-empty">
					<p><?php esc_html_e( 'No animals found.', 'deimos-lost-found-animals' ); ?></p>
				</div>
				<?php endif; ?>
			</div>

			<div class="deimlofo-empty deimlofo-no-results">
				<p><?php esc_html_e( 'No animals match your filters.', 'deimos-lost-found-animals' ); ?></p>
				<button type="button" class="deimlofo-btn deimlofo-reset-trigger"><?php esc_html_e( 'Reset Filters', 'deimos-lost-found-animals' ); ?></button>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render a single animal card.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	private function render_card( $post_id ) {
		$status     = deimlofo_get_meta( $post_id, 'status' );
		$location   = deimlofo_get_meta( $post_id, 'location' );
		$breed      = deimlofo_get_meta( $post_id, 'breed' );
		$gender     = deimlofo_get_meta( $post_id, 'gender' );
		$found_date = deimlofo_get_meta( $post_id, 'found_date' );

		$badge = deimlofo_get_badge( $status );
		?>
		<div class="deimlofo-card"
			data-status="<?php echo esc_attr( $status ); ?>"
			data-gender="<?php echo esc_attr( $gender ); ?>"
			data-date="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"
			data-name="<?php echo esc_attr( get_the_title() ); ?>">

			<a href="<?php the_permalink(); ?>" class="deimlofo-card-image">
				<?php if ( has_post_thumbnail( $post_id ) ) : ?>
					<img src="<?php echo esc_url( get_the_post_thumbnail_url( $post_id, 'deimlofo-card' ) ); ?>" alt="<?php the_title_attribute(); ?>">
				<?php else : ?>
					<div class="deimlofo-no-photo">
						<span><?php esc_html_e( 'No Photo', 'deimos-lost-found-animals' ); ?></span>
					</div>
				<?php endif; ?>

				<span class="deimlofo-badge deimlofo-status--<?php echo esc_attr( $badge['slug'] ); ?>">
					<?php echo esc_html( $badge['text'] ); ?>
				</span>
			</a>

			<div class="deimlofo-card-body">
				<h3 class="deimlofo-card-title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h3>

				<p class="deimlofo-card-location">
					<?php echo esc_html( $location ? $location : __( 'Unknown location', 'deimos-lost-found-animals' ) ); ?>
				</p>

				<div class="deimlofo-card-tags">
					<?php if ( $breed ) : ?>
						<span class="deimlofo-tag"><?php echo esc_html( $breed ); ?></span>
					<?php endif; ?>
					<?php if ( $gender ) : ?>
						<span class="deimlofo-tag"><?php echo esc_html( $gender ); ?></span>
					<?php endif; ?>
					<?php if ( $found_date ) : ?>
						<span class="deimlofo-tag"><?php echo esc_html( date_i18n( 'j M', strtotime( $found_date ) ) ); ?></span>
					<?php endif; ?>
				</div>

				<a href="<?php the_permalink(); ?>" class="deimlofo-btn"><?php esc_html_e( 'View Details', 'deimos-lost-found-animals' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Use the bundled template for single animal pages.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function single_template( $template ) {
		$post = get_post();

		if ( $post instanceof WP_Post && 'deimlofo_animal' === $post->post_type ) {
			$plugin_template = DEIMLOFO_PLUGIN_DIR . 'templates/single-animal.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}
}

DEIMLOFO_Shortcodes::instance();
