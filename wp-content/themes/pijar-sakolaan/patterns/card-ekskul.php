<?php
/**
 * Title: Kartu ekstrakurikuler
 * Slug: pijar-sakolaan/card-ekskul
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"is-style-sakolaan-card sakolaan-card--link sakolaan-post-card sakolaan-post-card--activity","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-sakolaan-card sakolaan-card--link sakolaan-post-card sakolaan-post-card--activity"><!-- wp:post-featured-image {"isLink":false,"aspectRatio":"16/10","scale":"cover","sizeSlug":"sakolaan-card","className":"sakolaan-photo"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"lg"} /-->

<!-- wp:paragraph {"className":"sakolaan-optional sakolaan-text-muted","fontSize":"sm","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"pembina","prefix":"<?php echo esc_attr__( 'Pembina: ', 'pijar-sakolaan' ); ?>"}}}}} -->
<p class="sakolaan-optional sakolaan-text-muted has-sm-font-size"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-optional sakolaan-text-muted","fontSize":"sm","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"jadwal","prefix":"<?php echo esc_attr__( 'Jadwal: ', 'pijar-sakolaan' ); ?>"}}}}} -->
<p class="sakolaan-optional sakolaan-text-muted has-sm-font-size"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
