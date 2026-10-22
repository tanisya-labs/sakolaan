<?php
/**
 * Title: Pengenalan sekolah
 * Slug: pijar-sakolaan/sakolaan-introduction
 * Categories: sakolaan-sections
 * Description: Foto sekolah, deskripsi singkat, dan tautan ke halaman profil.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_alt = sprintf(
	/* translators: %s: nama sekolah. */
	__( 'Lingkungan %s', 'pijar-sakolaan' ),
	get_bloginfo( 'name' )
);
$sakolaan_image_attrs = pijar_sakolaan_block_json(
	array(
		'key'         => 'homepage.intro_image',
		'size'        => 'sakolaan-hero',
		'aspectRatio' => '4/3',
		'sizes'       => '(min-width: 960px) 45vw, 100vw',
		'fallbackAlt' => $sakolaan_alt,
	)
);
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--introduction","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--introduction"><!-- wp:group {"align":"wide","className":"sakolaan-split","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide sakolaan-split"><!-- wp:pijar-sakolaan/setting-image <?php echo $sakolaan_image_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON dibuat oleh pijar_sakolaan_block_json(). ?> /-->

<!-- wp:group {"className":"sakolaan-split__body","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-split__body"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Tentang sekolah kami', 'pijar-sakolaan' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sakolaan-prewrap sakolaan-optional","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"homepage.intro_text"}}}}} -->
<p class="sakolaan-prewrap sakolaan-optional"><?php echo esc_html( pijar_sakolaan_get_setting( 'homepage.intro_text' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline sakolaan-optional","metadata":{"bindings":{"url":{"source":"pijar-sakolaan/setting","args":{"key":"profile.url"}}}}} -->
<div class="wp-block-button is-style-outline sakolaan-optional"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Selengkapnya tentang sekolah', 'pijar-sakolaan' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
