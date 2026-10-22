<?php
/**
 * Render slider hero.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_slides = get_posts(
	array(
		'post_type'      => 'hero_slide',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'order'          => 'ASC',
	)
);

if ( empty( $sakolaan_slides ) ) {
	return;
}

$sakolaan_count    = count( $sakolaan_slides );
$sakolaan_autoplay = (bool) pijar_sakolaan_get_setting( 'homepage.hero_autoplay', true );
$sakolaan_interval = min( 10, max( 3, absint( pijar_sakolaan_get_setting( 'homepage.hero_interval', 6 ) ) ) );
$sakolaan_id       = wp_unique_id( 'sakolaan-hero-slider-' );
?>
<section
	<?php echo get_block_wrapper_attributes( array( 'class' => 'alignfull sakolaan-section sakolaan-section--hero is-style-sakolaan-dark sakolaan-hero sakolaan-hero-slider' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress helper. ?>
	id="<?php echo esc_attr( $sakolaan_id ); ?>"
	aria-roledescription="<?php esc_attr_e( 'carousel', 'sakolaan' ); ?>"
	aria-label="<?php esc_attr_e( 'Sorotan sekolah', 'sakolaan' ); ?>"
	data-sakolaan-hero-slider
	data-autoplay="<?php echo $sakolaan_autoplay ? 'true' : 'false'; ?>"
	data-interval="<?php echo esc_attr( (string) ( $sakolaan_interval * 1000 ) ); ?>"
	data-status-slide="<?php esc_attr_e( 'Slide', 'sakolaan' ); ?>"
	data-status-of="<?php esc_attr_e( 'dari', 'sakolaan' ); ?>"
>
	<div class="sakolaan-hero-slider__viewport">
		<?php foreach ( $sakolaan_slides as $sakolaan_index => $sakolaan_slide ) : ?>
			<?php
			$sakolaan_is_first = 0 === $sakolaan_index;
			$sakolaan_eyebrow  = pijar_sakolaan_get_raw_meta( $sakolaan_slide->ID, 'eyebrow' );
			$sakolaan_excerpt  = has_excerpt( $sakolaan_slide ) ? get_the_excerpt( $sakolaan_slide ) : '';
			$sakolaan_primary  = pijar_sakolaan_get_raw_meta( $sakolaan_slide->ID, 'cta_label' );
			$sakolaan_primary_url = esc_url( (string) pijar_sakolaan_get_raw_meta( $sakolaan_slide->ID, 'cta_url' ) );
			$sakolaan_primary_style = sanitize_key( (string) pijar_sakolaan_get_raw_meta( $sakolaan_slide->ID, 'cta_style' ) );
			$sakolaan_primary_style = in_array( $sakolaan_primary_style, array( 'accent', 'light', 'outline' ), true ) ? $sakolaan_primary_style : 'accent';
			$sakolaan_secondary = pijar_sakolaan_get_raw_meta( $sakolaan_slide->ID, 'cta2_label' );
			$sakolaan_secondary_url = esc_url( (string) pijar_sakolaan_get_raw_meta( $sakolaan_slide->ID, 'cta2_url' ) );
			$sakolaan_secondary_style = sanitize_key( (string) pijar_sakolaan_get_raw_meta( $sakolaan_slide->ID, 'cta2_style' ) );
			$sakolaan_secondary_style = in_array( $sakolaan_secondary_style, array( 'accent', 'light', 'outline' ), true ) ? $sakolaan_secondary_style : 'outline';
			$sakolaan_style_classes = array( 'accent' => 'is-style-accent', 'light' => 'is-style-sakolaan-light', 'outline' => 'is-style-outline' );
			?>
			<article
				class="sakolaan-hero-slider__slide<?php echo $sakolaan_is_first ? ' is-active' : ''; ?>"
				role="group"
				aria-roledescription="<?php esc_attr_e( 'slide', 'sakolaan' ); ?>"
				aria-label="<?php echo esc_attr( sprintf( /* translators: 1: current slide, 2: total slides. */ __( '%1$d dari %2$d', 'sakolaan' ), $sakolaan_index + 1, $sakolaan_count ) ); ?>"
				<?php echo $sakolaan_is_first ? '' : ' hidden'; ?>
			>
				<?php if ( has_post_thumbnail( $sakolaan_slide ) ) : ?>
					<?php
					echo get_the_post_thumbnail(
						$sakolaan_slide,
						'sakolaan-wide',
						array(
							'class'         => 'sakolaan-hero-slider__image',
							'loading'       => $sakolaan_is_first ? 'eager' : 'lazy',
							'fetchpriority' => $sakolaan_is_first ? 'high' : 'auto',
							'sizes'         => '100vw',
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output WordPress core.
					?>
				<?php endif; ?>
				<div class="sakolaan-hero-slider__overlay" aria-hidden="true"></div>
				<div class="sakolaan-hero-slider__content alignwide">
					<?php if ( '' !== $sakolaan_eyebrow ) : ?><p class="sakolaan-hero__eyebrow"><?php echo esc_html( $sakolaan_eyebrow ); ?></p><?php endif; ?>
					<h1 class="wp-block-heading has-4-xl-font-size"><?php echo esc_html( get_the_title( $sakolaan_slide ) ); ?></h1>
					<?php if ( '' !== $sakolaan_excerpt ) : ?><p class="sakolaan-hero-slider__caption"><?php echo esc_html( $sakolaan_excerpt ); ?></p><?php endif; ?>
					<?php if ( ( $sakolaan_primary && $sakolaan_primary_url ) || ( $sakolaan_secondary && $sakolaan_secondary_url ) ) : ?>
						<div class="wp-block-buttons">
							<?php if ( $sakolaan_primary && $sakolaan_primary_url ) : ?><div class="wp-block-button <?php echo esc_attr( $sakolaan_style_classes[ $sakolaan_primary_style ] ); ?>"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $sakolaan_primary_url ); ?>"><?php echo esc_html( $sakolaan_primary ); ?></a></div><?php endif; ?>
							<?php if ( $sakolaan_secondary && $sakolaan_secondary_url ) : ?><div class="wp-block-button <?php echo esc_attr( $sakolaan_style_classes[ $sakolaan_secondary_style ] ); ?>"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $sakolaan_secondary_url ); ?>"><?php echo esc_html( $sakolaan_secondary ); ?></a></div><?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<?php if ( $sakolaan_count > 1 ) : ?>
		<div class="sakolaan-hero-slider__controls" role="group" aria-label="<?php esc_attr_e( 'Kontrol sorotan sekolah', 'sakolaan' ); ?>" hidden>
			<button type="button" class="sakolaan-hero-slider__arrow" data-slider-previous aria-controls="<?php echo esc_attr( $sakolaan_id ); ?>" aria-label="<?php esc_attr_e( 'Slide sebelumnya', 'sakolaan' ); ?>">&larr;</button>
			<span class="sakolaan-hero-slider__count" aria-hidden="true"><span data-slider-current>01</span><span class="sakolaan-hero-slider__count-divider">/</span><?php echo esc_html( sprintf( '%02d', $sakolaan_count ) ); ?></span>
			<button type="button" class="sakolaan-hero-slider__arrow" data-slider-next aria-controls="<?php echo esc_attr( $sakolaan_id ); ?>" aria-label="<?php esc_attr_e( 'Slide berikutnya', 'sakolaan' ); ?>">&rarr;</button>
			<div class="sakolaan-hero-slider__dots" aria-label="<?php esc_attr_e( 'Pilih slide', 'sakolaan' ); ?>">
				<?php foreach ( $sakolaan_slides as $sakolaan_index => $sakolaan_slide ) : ?>
					<button type="button" data-slider-dot="<?php echo esc_attr( (string) $sakolaan_index ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number. */ __( 'Tampilkan slide %d', 'sakolaan' ), $sakolaan_index + 1 ) ); ?>"<?php echo 0 === $sakolaan_index ? ' aria-current="true"' : ''; ?>></button>
				<?php endforeach; ?>
			</div>
			<button type="button" class="sakolaan-hero-slider__toggle" data-slider-toggle data-play-label="<?php esc_attr_e( 'Putar slider', 'sakolaan' ); ?>" data-pause-label="<?php esc_attr_e( 'Jeda slider', 'sakolaan' ); ?>" aria-label="<?php esc_attr_e( 'Jeda slider', 'sakolaan' ); ?>"><span aria-hidden="true">&#10074;&#10074;</span></button>
		</div>
		<p class="sakolaan-visually-hidden" data-slider-status role="status" aria-live="polite"></p>
	<?php endif; ?>
</section>
