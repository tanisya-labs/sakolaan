<?php
/**
 * Title: Kartu prestasi
 * Slug: pijar-sakolaan/card-prestasi
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"is-style-sakolaan-card sakolaan-card--link sakolaan-post-card sakolaan-post-card--achievement","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-sakolaan-card sakolaan-card--link sakolaan-post-card sakolaan-post-card--achievement"><!-- wp:post-featured-image {"isLink":false,"aspectRatio":"16/10","scale":"cover","sizeSlug":"sakolaan-card","className":"sakolaan-photo"} /-->

<!-- wp:post-terms {"term":"tingkat_prestasi","separator":" ","className":"sakolaan-badge-list"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"lg"} /-->

<!-- wp:paragraph {"className":"sakolaan-optional","fontSize":"sm","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"peraih"}}}}} -->
<p class="sakolaan-optional has-sm-font-size"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-meta sakolaan-optional","fontSize":"sm","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"tahun"}}}}} -->
<p class="sakolaan-meta sakolaan-optional has-sm-font-size"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
