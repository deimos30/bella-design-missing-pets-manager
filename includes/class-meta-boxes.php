<?php
/**
 * Meta Boxes for Animal (Simplified - no gallery)
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
 * Animal details meta box.
 */
class DEIMLOFO_Meta_Boxes {

	/**
	 * Single instance
	 *
	 * @var DEIMLOFO_Meta_Boxes|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return DEIMLOFO_Meta_Boxes
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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_deimlofo_animal', array( $this, 'save' ), 10, 2 );
		add_action( 'do_meta_boxes', array( $this, 'rename_featured_image' ) );
	}

	/**
	 * Register the Animal Details meta box.
	 *
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'deimlofo_details',
			__( 'Animal Details', 'deimos-lost-found-animals' ),
			array( $this, 'render_details' ),
			'deimlofo_animal',
			'normal',
			'high'
		);
	}

	/**
	 * Re-title the featured image box as the main photo.
	 *
	 * @return void
	 */
	public function rename_featured_image() {
		remove_meta_box( 'postimagediv', 'deimlofo_animal', 'side' );
		add_meta_box(
			'postimagediv',
			__( 'Main Photo (Featured Image)', 'deimos-lost-found-animals' ),
			'post_thumbnail_meta_box',
			'deimlofo_animal',
			'side',
			'high'
		);
	}

	/**
	 * Render the Animal Details meta box.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public function render_details( $post ) {
		wp_nonce_field( 'deimlofo_save_animal_data', 'deimlofo_nonce' );

		$type       = get_post_meta( $post->ID, '_deimlofo_type', true );
		$location   = get_post_meta( $post->ID, '_deimlofo_location', true );
		$breed      = get_post_meta( $post->ID, '_deimlofo_breed', true );
		$color      = get_post_meta( $post->ID, '_deimlofo_color', true );
		$gender     = get_post_meta( $post->ID, '_deimlofo_gender', true );
		$age        = get_post_meta( $post->ID, '_deimlofo_age', true );
		$status     = get_post_meta( $post->ID, '_deimlofo_status', true );
		$found_date = get_post_meta( $post->ID, '_deimlofo_found_date', true );
		$microchip  = get_post_meta( $post->ID, '_deimlofo_microchip', true );

		if ( empty( $status ) ) {
			$status = 'Found';
		}
		if ( empty( $type ) ) {
			$type = 'Dog';
		}
		?>
		<div class="deimlofo-help-box">
			<strong>📷 <?php esc_html_e( 'Photo:', 'deimos-lost-found-animals' ); ?></strong>
			<?php esc_html_e( 'Use "Main Photo (Featured Image)" in the right sidebar to add a photo.', 'deimos-lost-found-animals' ); ?>
		</div>

		<table class="deimlofo-table">
			<tr>
				<th><label for="deimlofo_type"><?php esc_html_e( 'Animal Type', 'deimos-lost-found-animals' ); ?></label></th>
				<td>
					<select id="deimlofo_type" name="deimlofo_type">
						<option value="Dog" <?php selected( $type, 'Dog' ); ?>><?php esc_html_e( 'Dog', 'deimos-lost-found-animals' ); ?></option>
						<option value="Cat" <?php selected( $type, 'Cat' ); ?>><?php esc_html_e( 'Cat', 'deimos-lost-found-animals' ); ?></option>
						<option value="Other" <?php selected( $type, 'Other' ); ?>><?php esc_html_e( 'Other', 'deimos-lost-found-animals' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="deimlofo_location"><?php esc_html_e( 'Location Found', 'deimos-lost-found-animals' ); ?></label></th>
				<td><input type="text" id="deimlofo_location" name="deimlofo_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="deimlofo_breed"><?php esc_html_e( 'Breed', 'deimos-lost-found-animals' ); ?></label></th>
				<td><input type="text" id="deimlofo_breed" name="deimlofo_breed" value="<?php echo esc_attr( $breed ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="deimlofo_color"><?php esc_html_e( 'Color / Markings', 'deimos-lost-found-animals' ); ?></label></th>
				<td><input type="text" id="deimlofo_color" name="deimlofo_color" value="<?php echo esc_attr( $color ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="deimlofo_gender"><?php esc_html_e( 'Gender', 'deimos-lost-found-animals' ); ?></label></th>
				<td>
					<select id="deimlofo_gender" name="deimlofo_gender">
						<option value=""><?php esc_html_e( 'Select Gender', 'deimos-lost-found-animals' ); ?></option>
						<option value="Male" <?php selected( $gender, 'Male' ); ?>><?php esc_html_e( 'Male', 'deimos-lost-found-animals' ); ?></option>
						<option value="Female" <?php selected( $gender, 'Female' ); ?>><?php esc_html_e( 'Female', 'deimos-lost-found-animals' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="deimlofo_age"><?php esc_html_e( 'Estimated Age', 'deimos-lost-found-animals' ); ?></label></th>
				<td><input type="text" id="deimlofo_age" name="deimlofo_age" value="<?php echo esc_attr( $age ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="deimlofo_status"><?php esc_html_e( 'Status', 'deimos-lost-found-animals' ); ?></label></th>
				<td>
					<select id="deimlofo_status" name="deimlofo_status">
						<option value="Found Today" <?php selected( $status, 'Found Today' ); ?>><?php esc_html_e( 'Found Today', 'deimos-lost-found-animals' ); ?></option>
						<option value="Found" <?php selected( $status, 'Found' ); ?>><?php esc_html_e( 'Found', 'deimos-lost-found-animals' ); ?></option>
						<option value="Available" <?php selected( $status, 'Available' ); ?>><?php esc_html_e( 'Available for Adoption', 'deimos-lost-found-animals' ); ?></option>
						<option value="Reunited" <?php selected( $status, 'Reunited' ); ?>><?php esc_html_e( 'Reunited', 'deimos-lost-found-animals' ); ?></option>
						<option value="Not Available" <?php selected( $status, 'Not Available' ); ?>><?php esc_html_e( 'Not Available', 'deimos-lost-found-animals' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="deimlofo_found_date"><?php esc_html_e( 'Date Found', 'deimos-lost-found-animals' ); ?></label></th>
				<td><input type="date" id="deimlofo_found_date" name="deimlofo_found_date" value="<?php echo esc_attr( $found_date ); ?>"></td>
			</tr>
			<tr>
				<th><label for="deimlofo_microchip"><?php esc_html_e( 'Microchip', 'deimos-lost-found-animals' ); ?></label></th>
				<td>
					<select id="deimlofo_microchip" name="deimlofo_microchip">
						<option value=""><?php esc_html_e( 'Unknown', 'deimos-lost-found-animals' ); ?></option>
						<option value="Yes" <?php selected( $microchip, 'Yes' ); ?>><?php esc_html_e( 'Yes', 'deimos-lost-found-animals' ); ?></option>
						<option value="No" <?php selected( $microchip, 'No' ); ?>><?php esc_html_e( 'No', 'deimos-lost-found-animals' ); ?></option>
						<option value="Unreadable" <?php selected( $microchip, 'Unreadable' ); ?>><?php esc_html_e( 'Unreadable', 'deimos-lost-found-animals' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Allowed values for the enumerated fields.
	 *
	 * @return array Field name => list of permitted values.
	 */
	private function allowed_values() {
		return array(
			'type'      => array( 'Dog', 'Cat', 'Other' ),
			'status'    => deimlofo_get_statuses(),
			'gender'    => array( '', 'Male', 'Female' ),
			'microchip' => array( '', 'Yes', 'No', 'Unreadable' ),
		);
	}

	/**
	 * Persist the meta box values.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		unset( $post );

		if ( ! isset( $_POST['deimlofo_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['deimlofo_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'deimlofo_save_animal_data' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$allowed = $this->allowed_values();
		$fields  = array( 'type', 'status', 'location', 'breed', 'color', 'gender', 'age', 'found_date', 'microchip' );

		foreach ( $fields as $field ) {
			$key = 'deimlofo_' . $field;

			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );

			if ( isset( $allowed[ $field ] ) && ! in_array( $value, $allowed[ $field ], true ) ) {
				// Reject anything outside the allowlist rather than storing it.
				continue;
			}

			if ( 'found_date' === $field && '' !== $value ) {
				$parts = explode( '-', $value );
				if ( 3 !== count( $parts ) || ! checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] ) ) {
					continue;
				}
			}

			update_post_meta( $post_id, '_deimlofo_' . $field, $value );
		}
	}
}

DEIMLOFO_Meta_Boxes::instance();
