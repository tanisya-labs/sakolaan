<?php
/**
 * Title: Kartu guru
 * Slug: pijar-sakolaan/card-guru
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"is-style-sakolaan-card sakolaan-card--link sakolaan-post-card","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-sakolaan-card sakolaan-card--link sakolaan-post-card"><!-- wp:post-featured-image {"isLink":false,"aspectRatio":"4/5","scale":"cover","sizeSlug":"sakolaan-portrait","className":"sakolaan-photo"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"lg"} /-->

<!-- wp:paragraph {"className":"sakolaan-optional sakolaan-text-muted","fontSize":"sm","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"jabatan"}}}}} -->
<p class="sakolaan-optional sakolaan-text-muted has-sm-font-size"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-optional sakolaan-text-muted","fontSize":"sm","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"mapel","prefix":"<?php echo esc_attr__( 'Mapel: ', 'pijar-sakolaan' ); ?>"}}}}} -->
<p class="sakolaan-optional sakolaan-text-muted has-sm-font-size"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
