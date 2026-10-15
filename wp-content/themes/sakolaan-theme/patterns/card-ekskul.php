<?php
/**
 * Title: Kartu ekstrakurikuler
 * Slug: sakolaan-theme/card-ekskul
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"is-style-sakolaan-card sakolaan-card--link sakolaan-post-card sakolaan-post-card--activity","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-sakolaan-card sakolaan-card--link sakolaan-post-card sakolaan-post-card--activity"><!-- wp:post-featured-image {"isLink":false,"aspectRatio":"16/10","scale":"cover","sizeSlug":"sakolaan-card","className":"sakolaan-photo"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"lg"} /-->

<!-- wp:paragraph {"className":"sakolaan-optional sakolaan-text-muted","fontSize":"sm","metadata":{"bindings":{"content":{"source":"sakolaan-theme/meta","args":{"key":"pembina","prefix":"<?php echo esc_attr__( 'Pembina: ', 'sakolaan-theme' ); ?>"}}}}} -->
<p class="sakolaan-optional sakolaan-text-muted has-sm-font-size"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-optional sakolaan-text-muted","fontSize":"sm","metadata":{"bindings":{"content":{"source":"sakolaan-theme/meta","args":{"key":"jadwal","prefix":"<?php echo esc_attr__( 'Jadwal: ', 'sakolaan-theme' ); ?>"}}}}} -->
<p class="sakolaan-optional sakolaan-text-muted has-sm-font-size"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
