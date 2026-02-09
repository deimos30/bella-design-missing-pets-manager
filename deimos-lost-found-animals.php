<?php
/**
 * Plugin Name: Deimos Lost & Found Animals
 * Plugin URI: https://github.com/deimos30/deimos-lost-found-animals
 * Description: Manage lost and found animals with filtering and shortcode display. Works with any WordPress theme.
 * Version: 1.0.6
 * Author: Wojtek Kobylecki / Bella Design Studio
 * Author URI: https://github.com/deimos30
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: deimos-lost-found-animals
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'LFA_VERSION', '1.0.6' );
define( 'LFA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LFA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LFA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class
 */
final class Lost_Found_Animal {

    /**
     * Single instance
     *
     * @var Lost_Found_Animal|null
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Lost_Found_Animal
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
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once LFA_PLUGIN_DIR . 'includes/class-post-type.php';
        require_once LFA_PLUGIN_DIR . 'includes/class-meta-boxes.php';
        require_once LFA_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once LFA_PLUGIN_DIR . 'includes/class-admin.php';
        require_once LFA_PLUGIN_DIR . 'includes/class-settings.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_action( 'after_setup_theme', array( $this, 'image_sizes' ) );
        add_action( 'wp_head', array( $this, 'custom_dynamic_css' ), 100 );

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Register custom image sizes
     */
    public function image_sizes() {
        add_image_size( 'lfa-card', 400, 300, true );
        add_image_size( 'lfa-large', 800, 600, true );
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_scripts() {
        global $post;

        // Load only when needed
        $should_load = false;
        if ( is_singular( 'animal' ) ) {
            $should_load = true;
        } elseif ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'lost_found_animals' ) ) {
            $should_load = true;
        }

        if ( $should_load ) {
            wp_enqueue_style( 'lfa-frontend', LFA_PLUGIN_URL . 'assets/css/frontend.css', array(), LFA_VERSION );
            wp_enqueue_script( 'lfa-frontend', LFA_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), LFA_VERSION, true );
        }
    }

    /**
     * Output dynamic CSS from Settings
     */
    public function custom_dynamic_css() {
        global $post;

        // Only output on pages with shortcode or single animal
        $should_output = false;
        if ( is_singular( 'animal' ) ) {
            $should_output = true;
        } elseif ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'lost_found_animals' ) ) {
            $should_output = true;
        }

        if ( ! $should_output ) {
            return;
        }

        $options              = get_option( 'lfa_settings', array() );
        $filter_color         = ! empty( $options['filter_bar_color'] ) ? $options['filter_bar_color'] : '#f5f5f4';
        $reset_color          = ! empty( $options['reset_button_color'] ) ? $options['reset_button_color'] : '#e7e5e4';
        $view_details_color   = ! empty( $options['view_details_button_color'] ) ? $options['view_details_button_color'] : '#059669';
        $filter_width         = ! empty( $options['filter_width'] ) ? $options['filter_width'] : 'medium';
        $filter_alignment     = ! empty( $options['filter_alignment'] ) ? $options['filter_alignment'] : 'left';

        // Width values
        $width_map = array(
            'compact' => '520px',
            'medium'  => '720px',
            'large'   => '920px',
            'full'    => '100%',
        );
        $max_width = isset( $width_map[ $filter_width ] ) ? $width_map[ $filter_width ] : '720px';

        // Alignment margins
        $margin_left  = '0';
        $margin_right = 'auto';
        if ( 'center' === $filter_alignment ) {
            $margin_left  = 'auto';
            $margin_right = 'auto';
        } elseif ( 'right' === $filter_alignment ) {
            $margin_left  = 'auto';
            $margin_right = '0';
        }

        echo '<style id="lfa-dynamic-css">';
        echo '.lfa-filters{';
        echo 'background-color:' . esc_attr( $filter_color ) . '!important;';
        echo 'max-width:' . esc_attr( $max_width ) . '!important;';
        echo 'margin-left:' . esc_attr( $margin_left ) . '!important;';
        echo 'margin-right:' . esc_attr( $margin_right ) . '!important;';
        echo '}';
        echo '.lfa-reset{background-color:' . esc_attr( $reset_color ) . '!important;}';
        echo '.lfa-reset:hover{background-color:' . esc_attr( $this->adjust_brightness( $reset_color, -20 ) ) . '!important;}';
        echo '.lfa-btn{background-color:' . esc_attr( $view_details_color ) . '!important;}';
        echo '.lfa-btn:hover{background-color:' . esc_attr( $this->adjust_brightness( $view_details_color, -20 ) ) . '!important;}';
        echo '</style>';
    }

    /**
     * Adjust color brightness
     *
     * @param string $hex   Hex color.
     * @param int    $steps Steps to adjust.
     * @return string
     */
    private function adjust_brightness( $hex, $steps ) {
        $hex = ltrim( $hex, '#' );
        if ( strlen( $hex ) !== 6 ) {
            return '#' . $hex;
        }

        $r = max( 0, min( 255, hexdec( substr( $hex, 0, 2 ) ) + $steps ) );
        $g = max( 0, min( 255, hexdec( substr( $hex, 2, 2 ) ) + $steps ) );
        $b = max( 0, min( 255, hexdec( substr( $hex, 4, 2 ) ) + $steps ) );

        return '#' . sprintf( '%02x%02x%02x', $r, $g, $b );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook.
     */
    public function admin_scripts( $hook ) {
        global $post_type;

        if ( 'animal' === $post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            wp_enqueue_media();
            wp_enqueue_style( 'lfa-admin', LFA_PLUGIN_URL . 'assets/css/admin.css', array(), LFA_VERSION );
        }

        // Settings page - color picker
        if ( 'animal_page_lfa-settings' === $hook ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
            wp_enqueue_style( 'lfa-admin', LFA_PLUGIN_URL . 'assets/css/admin.css', array(), LFA_VERSION );
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        LFA_Post_Type::instance()->register();

        // Set default options
        $defaults = array(
            'columns'                   => 4,
            'limit'                     => -1,
            'show_filters'              => 'yes',
            'filter_width'              => 'medium',
            'filter_alignment'          => 'left',
            'filter_bar_color'          => '#f5f5f4',
            'reset_button_color'        => '#e7e5e4',
            'view_details_button_color' => '#059669',
            'default_phone'             => '',
            'default_email'             => '',
        );

        $existing = get_option( 'lfa_settings', array() );
        if ( empty( $existing ) ) {
            add_option( 'lfa_settings', $defaults );
        } else {
            // Merge new defaults with existing (for upgrades)
            $merged = array_merge( $defaults, $existing );
            update_option( 'lfa_settings', $merged );
        }

        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

/**
 * Initialize plugin
 *
 * @return Lost_Found_Animal
 */
function lfa() {
    return Lost_Found_Animal::instance();
}
add_action( 'plugins_loaded', 'lfa' );

/**
 * Get animal meta value
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key without prefix.
 * @return mixed
 */
function lfa_get_meta( $post_id, $key ) {
    return get_post_meta( $post_id, '_lfa_' . $key, true );
}

/**
 * Get plugin setting
 *
 * @param string $key     Setting key.
 * @param mixed  $default Default value.
 * @return mixed
 */
function lfa_get_setting( $key, $default = '' ) {
    $options = get_option( 'lfa_settings', array() );
    return isset( $options[ $key ] ) ? $options[ $key ] : $default;
}

/**
 * Get featured image only (simplified - no gallery)
 *
 * @param int $post_id Post ID.
 * @return array
 */
function lfa_get_all_images( $post_id ) {
    $images = array();

    // Featured image only
    if ( has_post_thumbnail( $post_id ) ) {
        $id       = get_post_thumbnail_id( $post_id );
        $images[] = array(
            'id'   => $id,
            'card' => get_the_post_thumbnail_url( $post_id, 'lfa-card' ),
            'full' => get_the_post_thumbnail_url( $post_id, 'large' ),
        );
    }

    return $images;
}

/**
 * Get status badge data
 *
 * @param string $status Status value.
 * @return array
 */
function lfa_get_badge( $status ) {
    $badges = array(
        'Found Today'   => array(
            'color' => '#f97316',
            'text'  => 'FOUND TODAY',
        ),
        'Found'         => array(
            'color' => '#3b82f6',
            'text'  => 'FOUND',
        ),
        'Available'     => array(
            'color' => '#8b5cf6',
            'text'  => 'FOR ADOPTION',
        ),
        'Reunited'      => array(
            'color' => '#10b981',
            'text'  => 'REUNITED!',
        ),
        'Not Available' => array(
            'color' => '#6b7280',
            'text'  => 'NOT AVAILABLE',
        ),
    );

    return isset( $badges[ $status ] ) ? $badges[ $status ] : array(
        'color' => '#6b7280',
        'text'  => strtoupper( $status ),
    );
}
