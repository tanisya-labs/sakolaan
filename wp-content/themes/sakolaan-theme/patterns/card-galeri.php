<?php
/**
 * Title: Kartu galeri
 * Slug: sakolaan-theme/card-galeri
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"is-style-sakolaan-card sakolaan-card--link sakolaan-post-card sakolaan-post-card--gallery","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-sakolaan-card sakolaan-card--link sakolaan-post-card sakolaan-post-card--gallery"><!-- wp:post-featured-image {"isLink":false,"aspectRatio":"4/3","scale":"cover","sizeSlug":"sakolaan-card","className":"sakolaan-photo"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"lg"} /-->

<!-- wp:paragraph {"className":"sakolaan-meta sakolaan-optional","fontSize":"sm","metadata":{"bindings":{"content":{"source":"sakolaan-theme/meta","args":{"key":"tanggal"}}}}} -->
<p class="sakolaan-meta sakolaan-optional has-sm-font-size"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
