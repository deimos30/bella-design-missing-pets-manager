<?php
/**
 * Single Animal Template (Simplified - single image only)
 *
 * @package Deimos_Lost_Found_Animals
 * @author  Wojtek Kobylecki / Bella Design Studio
 * @version 1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$type       = lfa_get_meta( get_the_ID(), 'type' );
$status     = lfa_get_meta( get_the_ID(), 'status' );
$location   = lfa_get_meta( get_the_ID(), 'location' );
$breed      = lfa_get_meta( get_the_ID(), 'breed' );
$color      = lfa_get_meta( get_the_ID(), 'color' );
$gender     = lfa_get_meta( get_the_ID(), 'gender' );
$age        = lfa_get_meta( get_the_ID(), 'age' );
$found_date = lfa_get_meta( get_the_ID(), 'found_date' );
$microchip  = lfa_get_meta( get_the_ID(), 'microchip' );

$badge = lfa_get_badge( $status );

if ( empty( $type ) ) {
    $type = 'Animal';
}

// Get contact settings
$default_phone = lfa_get_setting( 'default_phone', '' );
$default_email = lfa_get_setting( 'default_email', '' );
?>

<div class="lfa-single">

    <nav class="lfa-breadcrumb">
        <a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Home', 'deimos-lost-found-animals' ); ?></a>
        <span>&rsaquo;</span>
        <span><?php the_title(); ?></span>
    </nav>

    <div class="lfa-single-grid">

        <div class="lfa-gallery-col">
            <div class="lfa-main-photo">
                <?php if ( has_post_thumbnail() ) : ?>
                    <img src="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'large' ) ); ?>" alt="<?php the_title_attribute(); ?>">
                <?php else : ?>
                    <div class="lfa-no-photo" style="height:450px;">
                        <span><?php esc_html_e( 'No Photo', 'deimos-lost-found-animals' ); ?></span>
                    </div>
                <?php endif; ?>
                <span class="lfa-main-badge" style="background:<?php echo esc_attr( $badge['color'] ); ?>;">
                    <?php echo esc_html( $badge['text'] ); ?>
                </span>
            </div>
        </div>

        <div class="lfa-details-col">
            <h1 class="lfa-single-title"><?php the_title(); ?></h1>

            <?php if ( $location ) : ?>
                <div class="lfa-single-location">
                    &#128205; <?php esc_html_e( 'Found at:', 'deimos-lost-found-animals' ); ?> <strong><?php echo esc_html( $location ); ?></strong>
                </div>
            <?php endif; ?>

            <div class="lfa-details-grid">
                <?php if ( $type ) : ?>
                    <div class="lfa-detail-box">
                        <div class="lfa-detail-label"><?php esc_html_e( 'Type', 'deimos-lost-found-animals' ); ?></div>
                        <div class="lfa-detail-value"><?php echo esc_html( $type ); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ( $breed ) : ?>
                    <div class="lfa-detail-box">
                        <div class="lfa-detail-label"><?php esc_html_e( 'Breed', 'deimos-lost-found-animals' ); ?></div>
                        <div class="lfa-detail-value"><?php echo esc_html( $breed ); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ( $gender ) : ?>
                    <div class="lfa-detail-box">
                        <div class="lfa-detail-label"><?php esc_html_e( 'Gender', 'deimos-lost-found-animals' ); ?></div>
                        <div class="lfa-detail-value"><?php echo esc_html( $gender ); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ( $age ) : ?>
                    <div class="lfa-detail-box">
                        <div class="lfa-detail-label"><?php esc_html_e( 'Age', 'deimos-lost-found-animals' ); ?></div>
                        <div class="lfa-detail-value"><?php echo esc_html( $age ); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ( $color ) : ?>
                    <div class="lfa-detail-box">
                        <div class="lfa-detail-label"><?php esc_html_e( 'Color', 'deimos-lost-found-animals' ); ?></div>
                        <div class="lfa-detail-value"><?php echo esc_html( $color ); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ( $found_date ) : ?>
                    <div class="lfa-detail-box">
                        <div class="lfa-detail-label"><?php esc_html_e( 'Date Found', 'deimos-lost-found-animals' ); ?></div>
                        <div class="lfa-detail-value"><?php echo esc_html( date_i18n( 'j F Y', strtotime( $found_date ) ) ); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ( $microchip ) : ?>
                    <div class="lfa-detail-box">
                        <div class="lfa-detail-label"><?php esc_html_e( 'Microchip', 'deimos-lost-found-animals' ); ?></div>
                        <div class="lfa-detail-value"><?php echo esc_html( $microchip ); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ( get_the_content() ) : ?>
                <div class="lfa-description">
                    <h2><?php printf( esc_html__( 'About This %s', 'deimos-lost-found-animals' ), esc_html( $type ) ); ?></h2>
                    <div class="lfa-description-content"><?php the_content(); ?></div>
                </div>
            <?php endif; ?>

            <?php if ( 'Reunited' !== $status && 'Not Available' !== $status ) : ?>
                <div class="lfa-contact-box">
                    <h3><?php printf( esc_html__( 'Is this your %s?', 'deimos-lost-found-animals' ), esc_html( strtolower( $type ) ) ); ?></h3>
                    <p><?php esc_html_e( 'If you recognize this animal, please contact us immediately.', 'deimos-lost-found-animals' ); ?></p>
                    <div class="lfa-contact-btns">
                        <?php if ( ! empty( $default_phone ) ) : ?>
                            <a href="tel:<?php echo esc_attr( $default_phone ); ?>" class="lfa-btn-call">&#128222; <?php esc_html_e( 'Call Us', 'deimos-lost-found-animals' ); ?></a>
                        <?php endif; ?>

                        <?php
                        if ( ! empty( $default_email ) ) :
                            $email_subject = sprintf(
                                /* translators: %s: animal name/title */
                                __( 'Lost & Found Animal: %s', 'deimos-lost-found-animals' ),
                                get_the_title()
                            );
                            $email_body = sprintf(
                                /* translators: %s: link to animal page */
                                __( 'Hello, I think this might be my animal: %s', 'deimos-lost-found-animals' ),
                                get_permalink()
                            );
                            $mailto_link = 'mailto:' . $default_email . '?subject=' . rawurlencode( $email_subject ) . '&body=' . rawurlencode( $email_body );
                            ?>
                            <a href="<?php echo esc_url( $mailto_link ); ?>" class="lfa-btn-msg">&#9993; <?php esc_html_e( 'Send Message', 'deimos-lost-found-animals' ); ?></a>
                        <?php else : ?>
                            <a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" class="lfa-btn-msg">&#9993; <?php esc_html_e( 'Send Message', 'deimos-lost-found-animals' ); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ( 'Reunited' === $status ) : ?>
                <div class="lfa-reunited-box">
                    <span style="font-size:48px;">&#10084;</span>
                    <h3><?php esc_html_e( 'Happy Reunion!', 'deimos-lost-found-animals' ); ?></h3>
                    <p><?php esc_html_e( 'This animal has been reunited with their owner.', 'deimos-lost-found-animals' ); ?></p>
                </div>
            <?php else : ?>
                <div class="lfa-reunited-box" style="background:#f3f4f6;border-color:#e5e7eb;">
                    <h3 style="color:#374151;"><?php esc_html_e( 'Not Available', 'deimos-lost-found-animals' ); ?></h3>
                    <p style="color:#6b7280;"><?php esc_html_e( 'This animal is currently not available.', 'deimos-lost-found-animals' ); ?></p>
                </div>
            <?php endif; ?>

            <div class="lfa-share">
                <div class="lfa-share-label"><?php esc_html_e( 'Share', 'deimos-lost-found-animals' ); ?></div>
                <div class="lfa-share-btns">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_url( rawurlencode( get_permalink() ) ); ?>" target="_blank" rel="noopener noreferrer" class="lfa-share-btn lfa-share-fb">Facebook</a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo esc_url( rawurlencode( get_permalink() ) ); ?>&amp;text=<?php echo esc_attr( rawurlencode( get_the_title() ) ); ?>" target="_blank" rel="noopener noreferrer" class="lfa-share-btn lfa-share-tw">Twitter</a>
                    <a href="https://wa.me/?text=<?php echo esc_url( rawurlencode( get_the_title() . ' - ' . get_permalink() ) ); ?>" target="_blank" rel="noopener noreferrer" class="lfa-share-btn lfa-share-wa">WhatsApp</a>
                </div>
            </div>
        </div>
    </div>

    <a href="javascript:history.back();" class="lfa-back">&larr; <?php esc_html_e( 'Back', 'deimos-lost-found-animals' ); ?></a>
</div>

<?php get_footer(); ?>
