<?php
/**
 * Meta Boxes for Animal (Simplified - no gallery)
 *
 * @package Deimos_Lost_Found_Animals
 * @author  Wojtek Kobylecki / Bella Design Studio
 * @version 1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LFA_Meta_Boxes {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_animal', array( $this, 'save' ), 10, 2 );
        add_action( 'do_meta_boxes', array( $this, 'rename_featured_image' ) );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'lfa_details',
            __( 'Animal Details', 'deimos-lost-found-animals' ),
            array( $this, 'render_details' ),
            'animal',
            'normal',
            'high'
        );
    }

    public function rename_featured_image() {
        remove_meta_box( 'postimagediv', 'animal', 'side' );
        add_meta_box(
            'postimagediv',
            __( 'Main Photo (Featured Image)', 'deimos-lost-found-animals' ),
            'post_thumbnail_meta_box',
            'animal',
            'side',
            'high'
        );
    }

    public function render_details( $post ) {
        wp_nonce_field( 'lfa_save_animal_data', 'lfa_nonce' );

        $type       = get_post_meta( $post->ID, '_lfa_type', true );
        $location   = get_post_meta( $post->ID, '_lfa_location', true );
        $breed      = get_post_meta( $post->ID, '_lfa_breed', true );
        $color      = get_post_meta( $post->ID, '_lfa_color', true );
        $gender     = get_post_meta( $post->ID, '_lfa_gender', true );
        $age        = get_post_meta( $post->ID, '_lfa_age', true );
        $status     = get_post_meta( $post->ID, '_lfa_status', true );
        $found_date = get_post_meta( $post->ID, '_lfa_found_date', true );
        $microchip  = get_post_meta( $post->ID, '_lfa_microchip', true );

        if ( empty( $status ) ) {
            $status = 'Found';
        }
        if ( empty( $type ) ) {
            $type = 'Dog';
        }
        ?>
        <div class="lfa-help-box">
            <strong>📷 <?php esc_html_e( 'Photo:', 'deimos-lost-found-animals' ); ?></strong>
            <?php esc_html_e( 'Use "Main Photo (Featured Image)" in the right sidebar to add a photo.', 'deimos-lost-found-animals' ); ?>
        </div>

        <table class="lfa-table">
            <tr>
                <th><label for="lfa_type"><?php esc_html_e( 'Animal Type', 'deimos-lost-found-animals' ); ?></label></th>
                <td>
                    <select id="lfa_type" name="lfa_type">
                        <option value="Dog" <?php selected( $type, 'Dog' ); ?>><?php esc_html_e( 'Dog', 'deimos-lost-found-animals' ); ?></option>
                        <option value="Cat" <?php selected( $type, 'Cat' ); ?>><?php esc_html_e( 'Cat', 'deimos-lost-found-animals' ); ?></option>
                        <option value="Other" <?php selected( $type, 'Other' ); ?>><?php esc_html_e( 'Other', 'deimos-lost-found-animals' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="lfa_location"><?php esc_html_e( 'Location Found', 'deimos-lost-found-animals' ); ?></label></th>
                <td><input type="text" id="lfa_location" name="lfa_location" value="<?php echo esc_attr( $location ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="lfa_breed"><?php esc_html_e( 'Breed', 'deimos-lost-found-animals' ); ?></label></th>
                <td><input type="text" id="lfa_breed" name="lfa_breed" value="<?php echo esc_attr( $breed ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="lfa_color"><?php esc_html_e( 'Color / Markings', 'deimos-lost-found-animals' ); ?></label></th>
                <td><input type="text" id="lfa_color" name="lfa_color" value="<?php echo esc_attr( $color ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="lfa_gender"><?php esc_html_e( 'Gender', 'deimos-lost-found-animals' ); ?></label></th>
                <td>
                    <select id="lfa_gender" name="lfa_gender">
                        <option value=""><?php esc_html_e( 'Select Gender', 'deimos-lost-found-animals' ); ?></option>
                        <option value="Male" <?php selected( $gender, 'Male' ); ?>><?php esc_html_e( 'Male', 'deimos-lost-found-animals' ); ?></option>
                        <option value="Female" <?php selected( $gender, 'Female' ); ?>><?php esc_html_e( 'Female', 'deimos-lost-found-animals' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="lfa_age"><?php esc_html_e( 'Estimated Age', 'deimos-lost-found-animals' ); ?></label></th>
                <td><input type="text" id="lfa_age" name="lfa_age" value="<?php echo esc_attr( $age ); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="lfa_status"><?php esc_html_e( 'Status', 'deimos-lost-found-animals' ); ?></label></th>
                <td>
                    <select id="lfa_status" name="lfa_status">
                        <option value="Found Today" <?php selected( $status, 'Found Today' ); ?>><?php esc_html_e( 'Found Today', 'deimos-lost-found-animals' ); ?></option>
                        <option value="Found" <?php selected( $status, 'Found' ); ?>><?php esc_html_e( 'Found', 'deimos-lost-found-animals' ); ?></option>
                        <option value="Available" <?php selected( $status, 'Available' ); ?>><?php esc_html_e( 'Available for Adoption', 'deimos-lost-found-animals' ); ?></option>
                        <option value="Reunited" <?php selected( $status, 'Reunited' ); ?>><?php esc_html_e( 'Reunited', 'deimos-lost-found-animals' ); ?></option>
                        <option value="Not Available" <?php selected( $status, 'Not Available' ); ?>><?php esc_html_e( 'Not Available', 'deimos-lost-found-animals' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="lfa_found_date"><?php esc_html_e( 'Date Found', 'deimos-lost-found-animals' ); ?></label></th>
                <td><input type="date" id="lfa_found_date" name="lfa_found_date" value="<?php echo esc_attr( $found_date ); ?>"></td>
            </tr>
            <tr>
                <th><label for="lfa_microchip"><?php esc_html_e( 'Microchip', 'deimos-lost-found-animals' ); ?></label></th>
                <td>
                    <select id="lfa_microchip" name="lfa_microchip">
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

    public function save( $post_id, $post ) {
        if ( ! isset( $_POST['lfa_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lfa_nonce'] ) ), 'lfa_save_animal_data' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $fields = array( 'type', 'status', 'location', 'breed', 'color', 'gender', 'age', 'found_date', 'microchip' );
        foreach ( $fields as $field ) {
            $key = 'lfa_' . $field;
            if ( isset( $_POST[ $key ] ) ) {
                update_post_meta( $post_id, '_lfa_' . $field, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
            }
        }
    }
}

LFA_Meta_Boxes::instance();
