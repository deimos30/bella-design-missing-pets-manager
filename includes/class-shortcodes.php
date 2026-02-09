<?php
/**
 * Shortcodes for displaying animals (simplified - single image only)
 *
 * @package Deimos_Lost_Found_Animals
 * @author  Wojtek Kobylecki / Bella Design Studio
 * @version 1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LFA_Shortcodes {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode( 'lost_found_animals', array( $this, 'render_grid' ) );
        add_filter( 'single_template', array( $this, 'single_template' ) );
    }

    public function render_grid( $atts ) {
        $default_columns      = lfa_get_setting( 'columns', 4 );
        $default_limit        = lfa_get_setting( 'limit', -1 );
        $default_show_filters = lfa_get_setting( 'show_filters', 'yes' );

        $atts = shortcode_atts(
            array(
                'limit'        => $default_limit,
                'status'       => '',
                'columns'      => $default_columns,
                'show_filters' => $default_show_filters,
            ),
            $atts
        );

        $show_filters = $atts['show_filters'];
        if ( 'false' === $show_filters || 'no' === $show_filters || false === $show_filters ) {
            $show_filters = false;
        } else {
            $show_filters = true;
        }

        $args = array(
            'post_type'      => 'animal',
            'posts_per_page' => intval( $atts['limit'] ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( ! empty( $atts['status'] ) ) {
            $args['meta_query'] = array(
                array(
                    'key'   => '_lfa_status',
                    'value' => sanitize_text_field( $atts['status'] ),
                ),
            );
        }

        $query   = new WP_Query( $args );
        $columns = intval( $atts['columns'] );
        if ( $columns < 1 || $columns > 4 ) {
            $columns = 4;
        }

        ob_start();
        ?>
        <div class="lfa-container">

            <?php if ( $show_filters ) : ?>
            <div class="lfa-filters">
                <select id="lfa-filter-status" class="lfa-select">
                    <option value=""><?php esc_html_e( 'All Status', 'deimos-lost-found-animals' ); ?></option>
                    <option value="Found Today"><?php esc_html_e( 'Found Today', 'deimos-lost-found-animals' ); ?></option>
                    <option value="Found"><?php esc_html_e( 'Found', 'deimos-lost-found-animals' ); ?></option>
                    <option value="Available"><?php esc_html_e( 'Available', 'deimos-lost-found-animals' ); ?></option>
                    <option value="Reunited"><?php esc_html_e( 'Reunited', 'deimos-lost-found-animals' ); ?></option>
                    <option value="Not Available"><?php esc_html_e( 'Not Available', 'deimos-lost-found-animals' ); ?></option>
                </select>

                <select id="lfa-filter-gender" class="lfa-select">
                    <option value=""><?php esc_html_e( 'All Genders', 'deimos-lost-found-animals' ); ?></option>
                    <option value="Male"><?php esc_html_e( 'Male', 'deimos-lost-found-animals' ); ?></option>
                    <option value="Female"><?php esc_html_e( 'Female', 'deimos-lost-found-animals' ); ?></option>
                </select>

                <select id="lfa-sort" class="lfa-select">
                    <option value="newest"><?php esc_html_e( 'Newest First', 'deimos-lost-found-animals' ); ?></option>
                    <option value="oldest"><?php esc_html_e( 'Oldest First', 'deimos-lost-found-animals' ); ?></option>
                    <option value="name-asc"><?php esc_html_e( 'Name A-Z', 'deimos-lost-found-animals' ); ?></option>
                    <option value="name-desc"><?php esc_html_e( 'Name Z-A', 'deimos-lost-found-animals' ); ?></option>
                </select>

                <button type="button" id="lfa-reset" class="lfa-reset"><?php esc_html_e( 'Reset', 'deimos-lost-found-animals' ); ?></button>

                <span class="lfa-count">
                    <?php
                    printf(
                        /* translators: %s: number of animals */
                        esc_html__( 'Showing %s animal(s)', 'deimos-lost-found-animals' ),
                        '<span id="lfa-count">' . esc_html( $query->found_posts ) . '</span>'
                    );
                    ?>
                </span>
            </div>
            <?php endif; ?>

            <div id="lfa-grid" class="lfa-grid lfa-cols-<?php echo esc_attr( $columns ); ?>">
                <?php
                if ( $query->have_posts() ) :
                    while ( $query->have_posts() ) :
                        $query->the_post();
                        $this->render_card( get_the_ID() );
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                <div class="lfa-empty">
                    <p><?php esc_html_e( 'No animals found.', 'deimos-lost-found-animals' ); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <div id="lfa-no-results" class="lfa-empty" style="display: none;">
                <p><?php esc_html_e( 'No animals match your filters.', 'deimos-lost-found-animals' ); ?></p>
                <button type="button" onclick="document.getElementById('lfa-reset').click();" class="lfa-btn"><?php esc_html_e( 'Reset Filters', 'deimos-lost-found-animals' ); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_card( $post_id ) {
        $status     = lfa_get_meta( $post_id, 'status' );
        $location   = lfa_get_meta( $post_id, 'location' );
        $breed      = lfa_get_meta( $post_id, 'breed' );
        $gender     = lfa_get_meta( $post_id, 'gender' );
        $found_date = lfa_get_meta( $post_id, 'found_date' );

        $badge = lfa_get_badge( $status );
        ?>
        <div class="lfa-card"
             data-status="<?php echo esc_attr( $status ); ?>"
             data-gender="<?php echo esc_attr( $gender ); ?>"
             data-date="<?php echo esc_attr( get_the_date( 'Y-m-d' ) ); ?>"
             data-name="<?php echo esc_attr( get_the_title() ); ?>">

            <a href="<?php the_permalink(); ?>" class="lfa-card-image">
                <?php if ( has_post_thumbnail( $post_id ) ) : ?>
                    <img src="<?php echo esc_url( get_the_post_thumbnail_url( $post_id, 'lfa-card' ) ); ?>" alt="<?php the_title_attribute(); ?>">
                <?php else : ?>
                    <div class="lfa-no-photo">
                        <span><?php esc_html_e( 'No Photo', 'deimos-lost-found-animals' ); ?></span>
                    </div>
                <?php endif; ?>

                <span class="lfa-badge" style="background: <?php echo esc_attr( $badge['color'] ); ?>;">
                    <?php echo esc_html( $badge['text'] ); ?>
                </span>
            </a>

            <div class="lfa-card-body">
                <h3 class="lfa-card-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>

                <p class="lfa-card-location">
                    <?php echo esc_html( $location ? $location : __( 'Unknown location', 'deimos-lost-found-animals' ) ); ?>
                </p>

                <div class="lfa-card-tags">
                    <?php if ( $breed ) : ?>
                        <span class="lfa-tag"><?php echo esc_html( $breed ); ?></span>
                    <?php endif; ?>
                    <?php if ( $gender ) : ?>
                        <span class="lfa-tag"><?php echo esc_html( $gender ); ?></span>
                    <?php endif; ?>
                    <?php if ( $found_date ) : ?>
                        <span class="lfa-tag"><?php echo esc_html( date_i18n( 'j M', strtotime( $found_date ) ) ); ?></span>
                    <?php endif; ?>
                </div>

                <a href="<?php the_permalink(); ?>" class="lfa-btn"><?php esc_html_e( 'View Details', 'deimos-lost-found-animals' ); ?></a>
            </div>
        </div>
        <?php
    }

    public function single_template( $template ) {
        global $post;

        if ( $post && 'animal' === $post->post_type ) {
            $plugin_template = LFA_PLUGIN_DIR . 'templates/single-animal.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }

        return $template;
    }
}

LFA_Shortcodes::instance();
