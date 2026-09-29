<?php
/**
 * Plugin Name: Deimos Lost & Found Animals
 * Plugin URI: https://github.com/deimos30/bella-design-missing-pets-manager
 * Description: Manage lost and found animals with filtering and shortcode display. Works with any WordPress theme.
 * Version: 1.1.0
 * Author: Wojtek Kobylecki
 * Author URI: https://github.com/deimos30
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: deimos-lost-found-animals
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package   Deimos_Lost_Found_Animals
 * @author    Wojtek Kobylecki
 * @copyright Copyright (c) 2026 Wojtek Kobylecki
 * @license   GPL-2.0-or-later
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'DEIMLOFO_VERSION', '1.1.0' );
define( 'DEIMLOFO_DB_VERSION', '1.1.0' );
define( 'DEIMLOFO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DEIMLOFO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'DEIMLOFO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Default plugin settings.
 *
 * @return array
 */
function deimlofo_default_settings() {
	return array(
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
}

/**
 * Plugin activation.
 *
 * @return void
 */
function deimlofo_activate() {
	require_once DEIMLOFO_PLUGIN_DIR . 'includes/class-post-type.php';
	DEIMLOFO_Post_Type::instance()->register();

	$defaults = deimlofo_default_settings();
	$existing = get_option( 'deimlofo_settings', array() );
	if ( empty( $existing ) || ! is_array( $existing ) ) {
		add_option( 'deimlofo_settings', $defaults );
	} else {
		update_option( 'deimlofo_settings', array_merge( $defaults, $existing ) );
	}

	flush_rewrite_rules();
	update_option( 'deimlofo_flush_version', DEIMLOFO_VERSION );
}
register_activation_hook( __FILE__, 'deimlofo_activate' );

/**
 * Plugin deactivation.
 *
 * @return void
 */
function deimlofo_deactivate() {
	flush_rewrite_rules();
	delete_option( 'deimlofo_flush_version' );
}
register_deactivation_hook( __FILE__, 'deimlofo_deactivate' );

/**
 * Main Plugin Class
 */
final class DEIMLOFO_Plugin {

	/**
	 * Single instance
	 *
	 * @var DEIMLOFO_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return DEIMLOFO_Plugin
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
	 *
	 * @return void
	 */
	private function includes() {
		require_once DEIMLOFO_PLUGIN_DIR . 'includes/class-post-type.php';
		require_once DEIMLOFO_PLUGIN_DIR . 'includes/class-meta-boxes.php';
		require_once DEIMLOFO_PLUGIN_DIR . 'includes/class-shortcodes.php';
		require_once DEIMLOFO_PLUGIN_DIR . 'includes/class-admin.php';
		require_once DEIMLOFO_PLUGIN_DIR . 'includes/class-settings.php';
		require_once DEIMLOFO_PLUGIN_DIR . 'includes/class-upgrade.php';
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'after_setup_theme', array( $this, 'image_sizes' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 999 );
	}

	/**
	 * Flush rewrite rules once per version change.
	 *
	 * @return void
	 */
	public function maybe_flush_rewrite_rules() {
		if ( get_option( 'deimlofo_flush_version' ) !== DEIMLOFO_VERSION ) {
			flush_rewrite_rules();
			update_option( 'deimlofo_flush_version', DEIMLOFO_VERSION );
		}
	}

	/**
	 * Register custom image sizes
	 *
	 * @return void
	 */
	public function image_sizes() {
		add_image_size( 'deimlofo-card', 400, 300, true );
		add_image_size( 'deimlofo-large', 800, 600, true );
	}

	/**
	 * Whether plugin assets are needed on the current request.
	 *
	 * @return bool
	 */
	private function needs_assets() {
		if ( is_singular( 'deimlofo_animal' ) ) {
			return true;
		}

		$post = get_post();
		if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'deimlofo_animals' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue frontend scripts and styles
	 *
	 * @return void
	 */
	public function frontend_scripts() {
		if ( ! $this->needs_assets() ) {
			return;
		}

		wp_enqueue_style( 'deimlofo-frontend', DEIMLOFO_PLUGIN_URL . 'assets/css/frontend.css', array(), DEIMLOFO_VERSION );
		wp_enqueue_script( 'deimlofo-frontend', DEIMLOFO_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), DEIMLOFO_VERSION, true );

		$css = $this->build_inline_css();
		if ( '' !== $css ) {
			wp_add_inline_style( 'deimlofo-frontend', $css );
		}
	}

	/**
	 * Build the settings-dependent CSS.
	 *
	 * All values are validated before they reach the stylesheet: colours through
	 * sanitize_hex_color() and width/alignment through allowlists.
	 *
	 * @return string
	 */
	private function build_inline_css() {
		$options = get_option( 'deimlofo_settings', array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$defaults = deimlofo_default_settings();

		$filter_color       = $this->sanitize_color( isset( $options['filter_bar_color'] ) ? $options['filter_bar_color'] : '', $defaults['filter_bar_color'] );
		$reset_color        = $this->sanitize_color( isset( $options['reset_button_color'] ) ? $options['reset_button_color'] : '', $defaults['reset_button_color'] );
		$view_details_color = $this->sanitize_color( isset( $options['view_details_button_color'] ) ? $options['view_details_button_color'] : '', $defaults['view_details_button_color'] );

		// Width allowlist.
		$width_map    = array(
			'compact' => '520px',
			'medium'  => '720px',
			'large'   => '920px',
			'full'    => '100%',
		);
		$filter_width = isset( $options['filter_width'] ) ? $options['filter_width'] : '';
		$max_width    = isset( $width_map[ $filter_width ] ) ? $width_map[ $filter_width ] : $width_map['medium'];

		// Alignment allowlist.
		$alignment_map    = array(
			'left'   => array( '0', 'auto' ),
			'center' => array( 'auto', 'auto' ),
			'right'  => array( 'auto', '0' ),
		);
		$filter_alignment = isset( $options['filter_alignment'] ) ? $options['filter_alignment'] : '';
		$margins          = isset( $alignment_map[ $filter_alignment ] ) ? $alignment_map[ $filter_alignment ] : $alignment_map['left'];

		$css  = '.deimlofo-filters{';
		$css .= 'background-color:' . $filter_color . ' !important;';
		$css .= 'max-width:' . $max_width . ' !important;';
		$css .= 'margin-left:' . $margins[0] . ' !important;';
		$css .= 'margin-right:' . $margins[1] . ' !important;';
		$css .= '}';
		$css .= '.deimlofo-reset{background-color:' . $reset_color . ' !important;}';
		$css .= '.deimlofo-reset:hover{background-color:' . $this->adjust_brightness( $reset_color, -20 ) . ' !important;}';
		$css .= '.deimlofo-btn{background-color:' . $view_details_color . ' !important;}';
		$css .= '.deimlofo-btn:hover{background-color:' . $this->adjust_brightness( $view_details_color, -20 ) . ' !important;}';

		return $css;
	}

	/**
	 * Validate a hex colour, falling back to a known-good default.
	 *
	 * @param string $value    Raw colour value.
	 * @param string $fallback Fallback colour.
	 * @return string
	 */
	private function sanitize_color( $value, $fallback ) {
		$color = sanitize_hex_color( $value );
		return ( ! empty( $color ) ) ? $color : $fallback;
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

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) ) {
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
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function admin_scripts( $hook_suffix ) {
		$post_type = get_current_screen() ? get_current_screen()->post_type : '';

		if ( 'deimlofo_animal' === $post_type && in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_style( 'deimlofo-admin', DEIMLOFO_PLUGIN_URL . 'assets/css/admin.css', array(), DEIMLOFO_VERSION );
		}

		// Settings page - color picker.
		if ( 'deimlofo_animal_page_deimlofo-settings' === $hook_suffix ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'deimlofo-admin', DEIMLOFO_PLUGIN_URL . 'assets/css/admin.css', array(), DEIMLOFO_VERSION );
			wp_enqueue_script(
				'deimlofo-admin',
				DEIMLOFO_PLUGIN_URL . 'assets/js/admin.js',
				array( 'jquery', 'wp-color-picker' ),
				DEIMLOFO_VERSION,
				true
			);
		}
	}
}

/**
 * Initialize plugin
 *
 * @return DEIMLOFO_Plugin
 */
function deimlofo() {
	return DEIMLOFO_Plugin::instance();
}
add_action( 'plugins_loaded', 'deimlofo' );

/**
 * Get animal meta value
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key without prefix.
 * @return mixed
 */
function deimlofo_get_meta( $post_id, $key ) {
	return get_post_meta( $post_id, '_deimlofo_' . $key, true );
}

/**
 * Get plugin setting
 *
 * @param string $key           Setting key.
 * @param mixed  $default_value Default value.
 * @return mixed
 */
function deimlofo_get_setting( $key, $default_value = '' ) {
	$options = get_option( 'deimlofo_settings', array() );
	if ( ! is_array( $options ) ) {
		return $default_value;
	}
	return isset( $options[ $key ] ) ? $options[ $key ] : $default_value;
}

/**
 * Get featured image only (simplified - no gallery)
 *
 * @param int $post_id Post ID.
 * @return array
 */
function deimlofo_get_all_images( $post_id ) {
	$images = array();

	// Featured image only.
	if ( has_post_thumbnail( $post_id ) ) {
		$id       = get_post_thumbnail_id( $post_id );
		$images[] = array(
			'id'   => $id,
			'card' => get_the_post_thumbnail_url( $post_id, 'deimlofo-card' ),
			'full' => get_the_post_thumbnail_url( $post_id, 'large' ),
		);
	}

	return $images;
}

/**
 * Allowed animal statuses.
 *
 * @return array
 */
function deimlofo_get_statuses() {
	return array( 'Found Today', 'Found', 'Available', 'Reunited', 'Not Available' );
}

/**
 * Get status badge data
 *
 * The badge colour is expressed as a CSS modifier class rather than an inline
 * style, so the palette lives entirely in the stylesheet.
 *
 * @param string $status Status value.
 * @return array
 */
function deimlofo_get_badge( $status ) {
	$badges = array(
		'Found Today'   => array(
			'slug' => 'found-today',
			'text' => __( 'FOUND TODAY', 'deimos-lost-found-animals' ),
		),
		'Found'         => array(
			'slug' => 'found',
			'text' => __( 'FOUND', 'deimos-lost-found-animals' ),
		),
		'Available'     => array(
			'slug' => 'available',
			'text' => __( 'FOR ADOPTION', 'deimos-lost-found-animals' ),
		),
		'Reunited'      => array(
			'slug' => 'reunited',
			'text' => __( 'REUNITED!', 'deimos-lost-found-animals' ),
		),
		'Not Available' => array(
			'slug' => 'not-available',
			'text' => __( 'NOT AVAILABLE', 'deimos-lost-found-animals' ),
		),
	);

	if ( isset( $badges[ $status ] ) ) {
		return $badges[ $status ];
	}

	return array(
		'slug' => 'default',
		'text' => strtoupper( (string) $status ),
	);
}
