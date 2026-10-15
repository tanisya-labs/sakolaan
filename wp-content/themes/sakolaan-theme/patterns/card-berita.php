<?php
/**
 * Title: Kartu berita
 * Slug: sakolaan-theme/card-berita
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"sakolaan-card--link sakolaan-post-card sakolaan-news-card sakolaan-news-card--simple","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-card--link sakolaan-post-card sakolaan-news-card sakolaan-news-card--simple"><!-- wp:post-featured-image {"isLink":false,"aspectRatio":"16/10","scale":"cover","sizeSlug":"sakolaan-card","className":"sakolaan-photo"} /-->

<!-- wp:group {"className":"sakolaan-news-card__meta","layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group sakolaan-news-card__meta"><!-- wp:post-date {"fontSize":"xs","textColor":"muted"} /-->

<!-- wp:post-terms {"term":"category","separator":" · ","fontSize":"xs","textColor":"muted"} /-->

<!-- /wp:group -->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"lg"} /-->

<!-- wp:post-excerpt {"excerptLength":24,"fontSize":"sm"} /--></div>
<!-- /wp:group -->
