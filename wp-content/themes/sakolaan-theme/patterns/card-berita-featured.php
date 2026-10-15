<?php
/**
 * Title: Kartu berita utama
 * Slug: sakolaan-theme/card-berita-featured
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"is-style-sakolaan-card sakolaan-card--link sakolaan-post-card sakolaan-news-card sakolaan-news-card--featured","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-sakolaan-card sakolaan-card--link sakolaan-post-card sakolaan-news-card sakolaan-news-card--featured"><!-- wp:post-featured-image {"isLink":false,"aspectRatio":"16/9","scale":"cover","sizeSlug":"sakolaan-wide","className":"sakolaan-photo"} /-->

<!-- wp:post-terms {"term":"category","separator":" ","className":"sakolaan-badge-list"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"xl"} /-->

<!-- wp:post-excerpt {"excerptLength":32} /-->

<!-- wp:post-date {"fontSize":"sm","textColor":"muted"} /--></div>
<!-- /wp:group -->
