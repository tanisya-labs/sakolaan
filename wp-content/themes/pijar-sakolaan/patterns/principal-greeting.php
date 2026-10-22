<?php
/**
 * Title: Sambutan kepala sekolah
 * Slug: pijar-sakolaan/principal-greeting
 * Categories: sakolaan-sections
 * Description: Foto, nama, jabatan, dan sambutan kepala sekolah.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_image_attrs = pijar_sakolaan_block_json(
	array(
		'key'         => 'principal.photo',
		'size'        => 'sakolaan-portrait',
		'aspectRatio' => '4/5',
		'sizes'       => '(min-width: 960px) 20rem, 60vw',
		'fallbackAlt' => pijar_sakolaan_get_setting( 'principal.name', '' ),
	)
);
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--principal is-style-sakolaan-tint","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--principal is-style-sakolaan-tint"><!-- wp:group {"align":"wide","className":"sakolaan-principal","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide sakolaan-principal"><!-- wp:pijar-sakolaan/setting-image <?php echo $sakolaan_image_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON dibuat oleh pijar_sakolaan_block_json(). ?> /-->

<!-- wp:group {"className":"sakolaan-principal__body","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-principal__body"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Sambutan kepala sekolah', 'pijar-sakolaan' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sakolaan-prewrap sakolaan-optional","fontSize":"lg","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"principal.greeting"}}}}} -->
<p class="sakolaan-prewrap sakolaan-optional has-lg-font-size"><?php echo esc_html( pijar_sakolaan_get_setting( 'principal.greeting' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-principal__name sakolaan-optional","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"principal.name"}}}}} -->
<p class="sakolaan-principal__name sakolaan-optional"><?php echo esc_html( pijar_sakolaan_get_setting( 'principal.name' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-text-muted sakolaan-optional","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"principal.title"}}}}} -->
<p class="sakolaan-text-muted sakolaan-optional"><?php echo esc_html( pijar_sakolaan_get_setting( 'principal.title' ) ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
