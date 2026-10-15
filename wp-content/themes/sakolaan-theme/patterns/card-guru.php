<?php
/**
 * Title: Kartu guru
 * Slug: sakolaan-theme/card-guru
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"is-style-sakolaan-card sakolaan-card--link sakolaan-post-card","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-sakolaan-card sakolaan-card--link sakolaan-post-card"><!-- wp:post-featured-image {"isLink":false,"aspectRatio":"4/5","scale":"cover","sizeSlug":"sakolaan-portrait","className":"sakolaan-photo"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"lg"} /-->

<!-- wp:paragraph {"className":"sakolaan-optional sakolaan-text-muted","fontSize":"sm","metadata":{"bindings":{"content":{"source":"sakolaan-theme/meta","args":{"key":"jabatan"}}}}} -->
<p class="sakolaan-optional sakolaan-text-muted has-sm-font-size"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-optional sakolaan-text-muted","fontSize":"sm","metadata":{"bindings":{"content":{"source":"sakolaan-theme/meta","args":{"key":"mapel","prefix":"<?php echo esc_attr__( 'Mapel: ', 'sakolaan-theme' ); ?>"}}}}} -->
<p class="sakolaan-optional sakolaan-text-muted has-sm-font-size"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
