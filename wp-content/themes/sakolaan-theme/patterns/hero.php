<?php
/**
 * Title: Hero beranda
 * Slug: sakolaan-theme/hero
 * Categories: sakolaan-sections
 * Description: Judul, deskripsi, dua tombol, dan gambar hero dari Pengaturan Sakolaan.
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_hero_mode = (string) sakolaan_theme_get_setting( 'homepage.hero_mode', 'single' );
$sakolaan_has_slides = 'slider' === $sakolaan_hero_mode && post_type_exists( 'hero_slide' ) && (bool) get_posts(
	array(
		'post_type'      => 'hero_slide',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);

if ( $sakolaan_has_slides ) {
	echo '<!-- wp:sakolaan-theme/hero-slider /-->';
	return;
}

$sakolaan_alt = sprintf(
	/* translators: %s: nama sekolah. */
	__( 'Foto %s', 'sakolaan-theme' ),
	get_bloginfo( 'name' )
);
$sakolaan_name       = get_bloginfo( 'name' );
$sakolaan_hero_title = trim( (string) sakolaan_theme_get_setting( 'homepage.hero_title', '' ) );
$sakolaan_show_title = '' !== $sakolaan_hero_title && 0 !== strcasecmp( wp_strip_all_tags( $sakolaan_hero_title ), wp_strip_all_tags( $sakolaan_name ) );
$sakolaan_image_attrs = sakolaan_theme_block_json(
	array(
		'key'         => 'homepage.hero_image',
		'size'        => 'sakolaan-hero',
		'sizes'       => '100vw',
		'priority'    => true,
		'fallbackAlt' => $sakolaan_alt,
	)
);
?>
<!-- wp:group {"align":"full","className":"sakolaan-section sakolaan-section--hero is-style-sakolaan-dark sakolaan-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull sakolaan-section sakolaan-section--hero is-style-sakolaan-dark sakolaan-hero"><!-- wp:sakolaan-theme/setting-image <?php echo $sakolaan_image_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON dibuat oleh sakolaan_theme_block_json(). ?> /-->

<!-- wp:group {"align":"wide","className":"sakolaan-hero__inner","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide sakolaan-hero__inner"><!-- wp:group {"className":"sakolaan-hero__text","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-hero__text"><?php if ( $sakolaan_show_title ) : ?><!-- wp:paragraph {"className":"sakolaan-hero__eyebrow"} -->
<p class="sakolaan-hero__eyebrow"><?php echo esc_html( $sakolaan_hero_title ); ?></p>
<!-- /wp:paragraph -->

<?php endif; ?><!-- wp:heading {"level":1,"fontSize":"4xl"} -->
<h1 class="wp-block-heading has-4-xl-font-size"><?php echo esc_html( $sakolaan_name ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sakolaan-optional","fontSize":"lg","metadata":{"bindings":{"content":{"source":"sakolaan-theme/setting","args":{"key":"homepage.hero_description"}}}}} -->
<p class="sakolaan-optional has-lg-font-size"><?php echo esc_html( sakolaan_theme_get_setting( 'homepage.hero_description' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-accent sakolaan-optional","metadata":{"bindings":{"url":{"source":"sakolaan-theme/setting","args":{"key":"homepage.hero_cta_url"}},"text":{"source":"sakolaan-theme/setting","args":{"key":"homepage.hero_cta_label"}}}}} -->
<div class="wp-block-button is-style-accent sakolaan-optional"><a class="wp-block-button__link wp-element-button"><?php echo esc_html( sakolaan_theme_get_setting( 'homepage.hero_cta_label' ) ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline sakolaan-optional","metadata":{"bindings":{"url":{"source":"sakolaan-theme/setting","args":{"key":"homepage.hero_cta2_url"}},"text":{"source":"sakolaan-theme/setting","args":{"key":"homepage.hero_cta2_label"}}}}} -->
<div class="wp-block-button is-style-outline sakolaan-optional"><a class="wp-block-button__link wp-element-button"><?php echo esc_html( sakolaan_theme_get_setting( 'homepage.hero_cta2_label' ) ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
