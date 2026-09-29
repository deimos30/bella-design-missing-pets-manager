<?php
/**
 * Single Animal Template (Simplified - single image only)
 *
 * @package   Deimos_Lost_Found_Animals
 * @author    Wojtek Kobylecki
 * @copyright Copyright (c) 2026 Wojtek Kobylecki
 * @license   GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$deimlofo_type       = deimlofo_get_meta( get_the_ID(), 'type' );
$deimlofo_status     = deimlofo_get_meta( get_the_ID(), 'status' );
$deimlofo_location   = deimlofo_get_meta( get_the_ID(), 'location' );
$deimlofo_breed      = deimlofo_get_meta( get_the_ID(), 'breed' );
$deimlofo_color      = deimlofo_get_meta( get_the_ID(), 'color' );
$deimlofo_gender     = deimlofo_get_meta( get_the_ID(), 'gender' );
$deimlofo_age        = deimlofo_get_meta( get_the_ID(), 'age' );
$deimlofo_found_date = deimlofo_get_meta( get_the_ID(), 'found_date' );
$deimlofo_microchip  = deimlofo_get_meta( get_the_ID(), 'microchip' );

$deimlofo_badge = deimlofo_get_badge( $deimlofo_status );

if ( empty( $deimlofo_type ) ) {
	$deimlofo_type = 'Animal';
}

// Contact settings.
$deimlofo_default_phone = deimlofo_get_setting( 'default_phone', '' );
$deimlofo_default_email = deimlofo_get_setting( 'default_email', '' );
?>

<div class="deimlofo-single">

	<nav class="deimlofo-breadcrumb">
		<a href="<?php echo esc_url( home_url() ); ?>"><?php esc_html_e( 'Home', 'deimos-lost-found-animals' ); ?></a>
		<span>&rsaquo;</span>
		<span><?php the_title(); ?></span>
	</nav>

	<div class="deimlofo-single-grid">

		<div class="deimlofo-gallery-col">
			<div class="deimlofo-main-photo">
				<?php if ( has_post_thumbnail() ) : ?>
					<img src="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'large' ) ); ?>" alt="<?php the_title_attribute(); ?>">
				<?php else : ?>
					<div class="deimlofo-no-photo deimlofo-no-photo--large">
						<span><?php esc_html_e( 'No Photo', 'deimos-lost-found-animals' ); ?></span>
					</div>
				<?php endif; ?>
				<span class="deimlofo-main-badge deimlofo-status--<?php echo esc_attr( $deimlofo_badge['slug'] ); ?>">
					<?php echo esc_html( $deimlofo_badge['text'] ); ?>
				</span>
			</div>
		</div>

		<div class="deimlofo-details-col">
			<h1 class="deimlofo-single-title"><?php the_title(); ?></h1>

			<?php if ( $deimlofo_location ) : ?>
				<div class="deimlofo-single-location">
					&#128205; <?php esc_html_e( 'Found at:', 'deimos-lost-found-animals' ); ?> <strong><?php echo esc_html( $deimlofo_location ); ?></strong>
				</div>
			<?php endif; ?>

			<div class="deimlofo-details-grid">
				<?php if ( $deimlofo_type ) : ?>
					<div class="deimlofo-detail-box">
						<div class="deimlofo-detail-label"><?php esc_html_e( 'Type', 'deimos-lost-found-animals' ); ?></div>
						<div class="deimlofo-detail-value"><?php echo esc_html( $deimlofo_type ); ?></div>
					</div>
				<?php endif; ?>
				<?php if ( $deimlofo_breed ) : ?>
					<div class="deimlofo-detail-box">
						<div class="deimlofo-detail-label"><?php esc_html_e( 'Breed', 'deimos-lost-found-animals' ); ?></div>
						<div class="deimlofo-detail-value"><?php echo esc_html( $deimlofo_breed ); ?></div>
					</div>
				<?php endif; ?>
				<?php if ( $deimlofo_gender ) : ?>
					<div class="deimlofo-detail-box">
						<div class="deimlofo-detail-label"><?php esc_html_e( 'Gender', 'deimos-lost-found-animals' ); ?></div>
						<div class="deimlofo-detail-value"><?php echo esc_html( $deimlofo_gender ); ?></div>
					</div>
				<?php endif; ?>
				<?php if ( $deimlofo_age ) : ?>
					<div class="deimlofo-detail-box">
						<div class="deimlofo-detail-label"><?php esc_html_e( 'Age', 'deimos-lost-found-animals' ); ?></div>
						<div class="deimlofo-detail-value"><?php echo esc_html( $deimlofo_age ); ?></div>
					</div>
				<?php endif; ?>
				<?php if ( $deimlofo_color ) : ?>
					<div class="deimlofo-detail-box">
						<div class="deimlofo-detail-label"><?php esc_html_e( 'Color', 'deimos-lost-found-animals' ); ?></div>
						<div class="deimlofo-detail-value"><?php echo esc_html( $deimlofo_color ); ?></div>
					</div>
				<?php endif; ?>
				<?php if ( $deimlofo_found_date ) : ?>
					<div class="deimlofo-detail-box">
						<div class="deimlofo-detail-label"><?php esc_html_e( 'Date Found', 'deimos-lost-found-animals' ); ?></div>
						<div class="deimlofo-detail-value"><?php echo esc_html( date_i18n( 'j F Y', strtotime( $deimlofo_found_date ) ) ); ?></div>
					</div>
				<?php endif; ?>
				<?php if ( $deimlofo_microchip ) : ?>
					<div class="deimlofo-detail-box">
						<div class="deimlofo-detail-label"><?php esc_html_e( 'Microchip', 'deimos-lost-found-animals' ); ?></div>
						<div class="deimlofo-detail-value"><?php echo esc_html( $deimlofo_microchip ); ?></div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( get_the_content() ) : ?>
				<div class="deimlofo-description">
					<h2>
				<?php
				printf(
					/* translators: %s: animal type, e.g. Dog. */
					esc_html__( 'About This %s', 'deimos-lost-found-animals' ),
					esc_html( $deimlofo_type )
				);
				?>
</h2>
					<div class="deimlofo-description-content"><?php the_content(); ?></div>
				</div>
			<?php endif; ?>

			<?php if ( 'Reunited' !== $deimlofo_status && 'Not Available' !== $deimlofo_status ) : ?>
				<div class="deimlofo-contact-box">
					<h3>
				<?php
				printf(
					/* translators: %s: lowercased animal type, e.g. dog. */
					esc_html__( 'Is this your %s?', 'deimos-lost-found-animals' ),
					esc_html( strtolower( $deimlofo_type ) )
				);
				?>
</h3>
					<p><?php esc_html_e( 'If you recognize this animal, please contact us immediately.', 'deimos-lost-found-animals' ); ?></p>
					<div class="deimlofo-contact-btns">
						<?php if ( ! empty( $deimlofo_default_phone ) ) : ?>
							<a href="tel:<?php echo esc_attr( $deimlofo_default_phone ); ?>" class="deimlofo-btn-call">&#128222; <?php esc_html_e( 'Call Us', 'deimos-lost-found-animals' ); ?></a>
						<?php endif; ?>

						<?php
						if ( ! empty( $deimlofo_default_email ) ) :
							$deimlofo_email_subject = sprintf(
								/* translators: %s: animal name/title */
								__( 'Lost & Found Animal: %s', 'deimos-lost-found-animals' ),
								get_the_title()
							);
							$deimlofo_email_body = sprintf(
								/* translators: %s: link to animal page */
								__( 'Hello, I think this might be my animal: %s', 'deimos-lost-found-animals' ),
								get_permalink()
							);
							$deimlofo_mailto_link = 'mailto:' . $deimlofo_default_email . '?subject=' . rawurlencode( $deimlofo_email_subject ) . '&body=' . rawurlencode( $deimlofo_email_body );
							?>
							<a href="<?php echo esc_url( $deimlofo_mailto_link ); ?>" class="deimlofo-btn-msg">&#9993; <?php esc_html_e( 'Send Message', 'deimos-lost-found-animals' ); ?></a>
						<?php else : ?>
							<a href="<?php echo esc_url( home_url( '/#contact' ) ); ?>" class="deimlofo-btn-msg">&#9993; <?php esc_html_e( 'Send Message', 'deimos-lost-found-animals' ); ?></a>
						<?php endif; ?>
					</div>
				</div>
			<?php elseif ( 'Reunited' === $deimlofo_status ) : ?>
				<div class="deimlofo-reunited-box">
					<span class="deimlofo-reunited-heart">&#10084;</span>
					<h3><?php esc_html_e( 'Happy Reunion!', 'deimos-lost-found-animals' ); ?></h3>
					<p><?php esc_html_e( 'This animal has been reunited with their owner.', 'deimos-lost-found-animals' ); ?></p>
				</div>
			<?php else : ?>
				<div class="deimlofo-reunited-box deimlofo-reunited-box--muted">
					<h3><?php esc_html_e( 'Not Available', 'deimos-lost-found-animals' ); ?></h3>
					<p><?php esc_html_e( 'This animal is currently not available.', 'deimos-lost-found-animals' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="deimlofo-share">
				<div class="deimlofo-share-label"><?php esc_html_e( 'Share', 'deimos-lost-found-animals' ); ?></div>
				<div class="deimlofo-share-btns">
					<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_url( rawurlencode( get_permalink() ) ); ?>" target="_blank" rel="noopener noreferrer" class="deimlofo-share-btn deimlofo-share-fb">Facebook</a>
					<a href="https://twitter.com/intent/tweet?url=<?php echo esc_url( rawurlencode( get_permalink() ) ); ?>&amp;text=<?php echo esc_attr( rawurlencode( get_the_title() ) ); ?>" target="_blank" rel="noopener noreferrer" class="deimlofo-share-btn deimlofo-share-tw">Twitter</a>
					<a href="https://wa.me/?text=<?php echo esc_url( rawurlencode( get_the_title() . ' - ' . get_permalink() ) ); ?>" target="_blank" rel="noopener noreferrer" class="deimlofo-share-btn deimlofo-share-wa">WhatsApp</a>
				</div>
			</div>
		</div>
	</div>

	<?php $deimlofo_archive = get_post_type_archive_link( 'deimlofo_animal' ); ?>
	<a href="<?php echo esc_url( $deimlofo_archive ? $deimlofo_archive : home_url( '/' ) ); ?>" class="deimlofo-back">&larr; <?php esc_html_e( 'Back', 'deimos-lost-found-animals' ); ?></a>
</div>

<?php get_footer(); ?>
