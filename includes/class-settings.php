<?php
/**
 * Settings Page for Deimos Lost & Found Animals
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
 * Plugin settings page.
 */
class DEIMLOFO_Settings {

	/**
	 * Single instance
	 *
	 * @var DEIMLOFO_Settings|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return DEIMLOFO_Settings
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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register the settings page under the Animals menu.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=deimlofo_animal',
			__( 'Settings', 'deimos-lost-found-animals' ),
			__( 'Settings', 'deimos-lost-found-animals' ),
			'manage_options',
			'deimlofo-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register the setting, its sections and fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'deimlofo_settings_group', 'deimlofo_settings', array( $this, 'sanitize_settings' ) );

		// === DISPLAY SECTION ===
		add_settings_section(
			'deimlofo_display_section',
			__( 'Grid Settings', 'deimos-lost-found-animals' ),
			array( $this, 'display_section_callback' ),
			'deimlofo-settings'
		);

		add_settings_field( 'columns', __( 'Grid Columns', 'deimos-lost-found-animals' ), array( $this, 'columns_field' ), 'deimlofo-settings', 'deimlofo_display_section' );
		add_settings_field( 'limit', __( 'Animals Limit', 'deimos-lost-found-animals' ), array( $this, 'limit_field' ), 'deimlofo-settings', 'deimlofo_display_section' );

		// === FILTER BAR SECTION ===
		add_settings_section(
			'deimlofo_filter_section',
			__( 'Filter Bar Settings', 'deimos-lost-found-animals' ),
			array( $this, 'filter_section_callback' ),
			'deimlofo-settings'
		);

		add_settings_field( 'show_filters', __( 'Show Filter Bar', 'deimos-lost-found-animals' ), array( $this, 'show_filters_field' ), 'deimlofo-settings', 'deimlofo_filter_section' );
		add_settings_field( 'filter_width', __( 'Filter Bar Width', 'deimos-lost-found-animals' ), array( $this, 'filter_width_field' ), 'deimlofo-settings', 'deimlofo_filter_section' );
		add_settings_field( 'filter_alignment', __( 'Filter Bar Alignment', 'deimos-lost-found-animals' ), array( $this, 'filter_alignment_field' ), 'deimlofo-settings', 'deimlofo_filter_section' );

		// === COLORS SECTION ===
		add_settings_section(
			'deimlofo_colors_section',
			__( 'Color Settings', 'deimos-lost-found-animals' ),
			array( $this, 'colors_section_callback' ),
			'deimlofo-settings'
		);

		add_settings_field( 'filter_bar_color', __( 'Filter Bar Background', 'deimos-lost-found-animals' ), array( $this, 'filter_bar_color_field' ), 'deimlofo-settings', 'deimlofo_colors_section' );
		add_settings_field( 'reset_button_color', __( 'Reset Button Color', 'deimos-lost-found-animals' ), array( $this, 'reset_button_color_field' ), 'deimlofo-settings', 'deimlofo_colors_section' );
		add_settings_field( 'view_details_button_color', __( 'View Details Button Color', 'deimos-lost-found-animals' ), array( $this, 'view_details_button_color_field' ), 'deimlofo-settings', 'deimlofo_colors_section' );

		// === CONTACT SECTION ===
		add_settings_section(
			'deimlofo_contact_section',
			__( 'Contact Settings', 'deimos-lost-found-animals' ),
			array( $this, 'contact_section_callback' ),
			'deimlofo-settings'
		);

		add_settings_field( 'default_phone', __( 'Default Phone Number', 'deimos-lost-found-animals' ), array( $this, 'default_phone_field' ), 'deimlofo-settings', 'deimlofo_contact_section' );
		add_settings_field( 'default_email', __( 'Default Contact Email', 'deimos-lost-found-animals' ), array( $this, 'default_email_field' ), 'deimlofo-settings', 'deimlofo_contact_section' );
	}

	/**
	 * Sanitize settings submitted from the settings page.
	 *
	 * @param mixed $input Raw settings.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		return deimlofo_sanitize_settings( $input );
	}

	/**
	 * Grid section description.
	 *
	 * @return void
	 */
	public function display_section_callback() {
		echo '<p>' . esc_html__( 'Configure how the animals grid is displayed.', 'deimos-lost-found-animals' ) . '</p>';
	}

	/**
	 * Filter bar section description.
	 *
	 * @return void
	 */
	public function filter_section_callback() {
		echo '<p>' . esc_html__( 'Configure the filter bar appearance and position.', 'deimos-lost-found-animals' ) . '</p>';
	}

	/**
	 * Colors section description.
	 *
	 * @return void
	 */
	public function colors_section_callback() {
		echo '<p>' . esc_html__( 'Customize the colors to match your theme.', 'deimos-lost-found-animals' ) . '</p>';
	}

	/**
	 * Contact section description.
	 *
	 * @return void
	 */
	public function contact_section_callback() {
		echo '<p>' . esc_html__( 'Set default contact information displayed on single animal pages.', 'deimos-lost-found-animals' ) . '</p>';
	}

	/**
	 * Grid columns field.
	 *
	 * @return void
	 */
	public function columns_field() {
		$options = get_option( 'deimlofo_settings', array() );
		$value   = isset( $options['columns'] ) ? $options['columns'] : 4;
		?>
		<select name="deimlofo_settings[columns]" id="deimlofo_columns">
			<option value="1" <?php selected( $value, 1 ); ?>>1 <?php esc_html_e( 'Column', 'deimos-lost-found-animals' ); ?></option>
			<option value="2" <?php selected( $value, 2 ); ?>>2 <?php esc_html_e( 'Columns', 'deimos-lost-found-animals' ); ?></option>
			<option value="3" <?php selected( $value, 3 ); ?>>3 <?php esc_html_e( 'Columns', 'deimos-lost-found-animals' ); ?></option>
			<option value="4" <?php selected( $value, 4 ); ?>>4 <?php esc_html_e( 'Columns', 'deimos-lost-found-animals' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Number of columns in the animals grid.', 'deimos-lost-found-animals' ); ?></p>
		<?php
	}

	/**
	 * Animals limit field.
	 *
	 * @return void
	 */
	public function limit_field() {
		$options = get_option( 'deimlofo_settings', array() );
		$value   = isset( $options['limit'] ) ? $options['limit'] : -1;
		?>
		<input type="number" name="deimlofo_settings[limit]" id="deimlofo_limit" value="<?php echo esc_attr( $value ); ?>" min="-1" class="small-text">
		<p class="description"><?php esc_html_e( 'Maximum number of animals to display. Use -1 for unlimited.', 'deimos-lost-found-animals' ); ?></p>
		<?php
	}

	/**
	 * Show filter bar field.
	 *
	 * @return void
	 */
	public function show_filters_field() {
		$options = get_option( 'deimlofo_settings', array() );
		$value   = isset( $options['show_filters'] ) ? $options['show_filters'] : 'yes';
		?>
		<label class="deimlofo-radio-label">
			<input type="radio" name="deimlofo_settings[show_filters]" value="yes" <?php checked( $value, 'yes' ); ?>>
			<?php esc_html_e( 'Yes', 'deimos-lost-found-animals' ); ?>
		</label>
		<label>
			<input type="radio" name="deimlofo_settings[show_filters]" value="no" <?php checked( $value, 'no' ); ?>>
			<?php esc_html_e( 'No', 'deimos-lost-found-animals' ); ?>
		</label>
		<?php
	}

	/**
	 * Filter bar width field.
	 *
	 * @return void
	 */
	public function filter_width_field() {
		$options = get_option( 'deimlofo_settings', array() );
		$value   = isset( $options['filter_width'] ) ? $options['filter_width'] : 'medium';
		?>
		<select name="deimlofo_settings[filter_width]" id="deimlofo_filter_width">
			<option value="compact" <?php selected( $value, 'compact' ); ?>><?php esc_html_e( 'Compact', 'deimos-lost-found-animals' ); ?> (520px)</option>
			<option value="medium" <?php selected( $value, 'medium' ); ?>><?php esc_html_e( 'Medium', 'deimos-lost-found-animals' ); ?> (720px)</option>
			<option value="large" <?php selected( $value, 'large' ); ?>><?php esc_html_e( 'Large', 'deimos-lost-found-animals' ); ?> (920px)</option>
			<option value="full" <?php selected( $value, 'full' ); ?>><?php esc_html_e( 'Full Width', 'deimos-lost-found-animals' ); ?> (100%)</option>
		</select>
		<p class="description"><?php esc_html_e( 'Maximum width of the filter bar.', 'deimos-lost-found-animals' ); ?></p>
		<?php
	}

	/**
	 * Filter bar alignment field.
	 *
	 * @return void
	 */
	public function filter_alignment_field() {
		$options = get_option( 'deimlofo_settings', array() );
		$value   = isset( $options['filter_alignment'] ) ? $options['filter_alignment'] : 'left';
		?>
		<label class="deimlofo-radio-label">
			<input type="radio" name="deimlofo_settings[filter_alignment]" value="left" <?php checked( $value, 'left' ); ?>>
			<?php esc_html_e( 'Left', 'deimos-lost-found-animals' ); ?>
		</label>
		<label class="deimlofo-radio-label">
			<input type="radio" name="deimlofo_settings[filter_alignment]" value="center" <?php checked( $value, 'center' ); ?>>
			<?php esc_html_e( 'Center', 'deimos-lost-found-animals' ); ?>
		</label>
		<label>
			<input type="radio" name="deimlofo_settings[filter_alignment]" value="right" <?php checked( $value, 'right' ); ?>>
			<?php esc_html_e( 'Right', 'deimos-lost-found-animals' ); ?>
		</label>
		<?php
	}

	/**
	 * Filter bar background colour field.
	 *
	 * @return void
	 */
	public function filter_bar_color_field() {
		$options = get_option( 'deimlofo_settings', array() );
		$value   = isset( $options['filter_bar_color'] ) ? $options['filter_bar_color'] : '#f5f5f4';
		?>
		<input type="text" name="deimlofo_settings[filter_bar_color]" id="deimlofo_filter_bar_color" value="<?php echo esc_attr( $value ); ?>" class="deimlofo-color-picker" data-default-color="#f5f5f4">
		<?php
	}

	/**
	 * Reset button colour field.
	 *
	 * @return void
	 */
	public function reset_button_color_field() {
		$options = get_option( 'deimlofo_settings', array() );
		$value   = isset( $options['reset_button_color'] ) ? $options['reset_button_color'] : '#e7e5e4';
		?>
		<input type="text" name="deimlofo_settings[reset_button_color]" id="deimlofo_reset_button_color" value="<?php echo esc_attr( $value ); ?>" class="deimlofo-color-picker" data-default-color="#e7e5e4">
		<?php
	}

	/**
	 * View Details button colour field.
	 *
	 * @return void
	 */
	public function view_details_button_color_field() {
		$options = get_option( 'deimlofo_settings', array() );
		$value   = isset( $options['view_details_button_color'] ) ? $options['view_details_button_color'] : '#059669';
		?>
		<input type="text" name="deimlofo_settings[view_details_button_color]" id="deimlofo_view_details_button_color" value="<?php echo esc_attr( $value ); ?>" class="deimlofo-color-picker" data-default-color="#059669">
		<p class="description"><?php esc_html_e( 'Color of the "View Details" button on animal cards.', 'deimos-lost-found-animals' ); ?></p>
		<?php
	}

	/**
	 * Default phone field.
	 *
	 * @return void
	 */
	public function default_phone_field() {
		$options = get_option( 'deimlofo_settings', array() );
		$value   = isset( $options['default_phone'] ) ? $options['default_phone'] : '';
		?>
		<input type="text" name="deimlofo_settings[default_phone]" id="deimlofo_default_phone" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="+44 1234 567890">
		<p class="description"><?php esc_html_e( 'Phone number displayed on single animal pages. Leave empty to hide the Call button.', 'deimos-lost-found-animals' ); ?></p>
		<?php
	}

	/**
	 * Default email field.
	 *
	 * @return void
	 */
	public function default_email_field() {
		$options = get_option( 'deimlofo_settings', array() );
		$value   = isset( $options['default_email'] ) ? $options['default_email'] : '';
		?>
		<input type="email" name="deimlofo_settings[default_email]" id="deimlofo_default_email" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="info@example.com">
		<p class="description"><?php esc_html_e( 'Email address for the Send Message button. Leave empty to link to homepage contact section.', 'deimos-lost-found-animals' ); ?></p>
		<?php
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only notice flag set by the Settings API redirect.
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( 'deimlofo_messages', 'deimlofo_message', __( 'Settings saved.', 'deimos-lost-found-animals' ), 'updated' );
		}

		settings_errors( 'deimlofo_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'deimlofo_settings_group' );
				do_settings_sections( 'deimlofo-settings' );
				submit_button( __( 'Save Settings', 'deimos-lost-found-animals' ) );
				?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Shortcode Usage', 'deimos-lost-found-animals' ); ?></h2>
			<p><?php esc_html_e( 'Use the shortcode below to display animals on any page:', 'deimos-lost-found-animals' ); ?></p>
			<code class="deimlofo-shortcode">[deimlofo_animals]</code>

			<p class="deimlofo-spaced"><?php esc_html_e( 'Override settings with parameters:', 'deimos-lost-found-animals' ); ?></p>
			<code class="deimlofo-shortcode">[deimlofo_animals limit="8" columns="2" show_filters="false"]</code>
		</div>

		<?php
	}
}

DEIMLOFO_Settings::instance();
