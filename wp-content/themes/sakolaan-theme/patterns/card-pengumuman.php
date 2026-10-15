<?php
/**
 * Title: Kartu pengumuman
 * Slug: sakolaan-theme/card-pengumuman
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
<div class="wp-block-group is-style-sakolaan-card sakolaan-card--link sakolaan-post-card"><!-- wp:group {"className":"sakolaan-card__head","layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group sakolaan-card__head"><!-- wp:paragraph {"className":"sakolaan-meta sakolaan-optional","fontSize":"sm","metadata":{"bindings":{"content":{"source":"sakolaan-theme/meta","args":{"key":"tanggal"}}}}} -->
<p class="sakolaan-meta sakolaan-optional has-sm-font-size"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-badge sakolaan-badge--warning sakolaan-optional","metadata":{"bindings":{"content":{"source":"sakolaan-theme/meta","args":{"key":"arsip_label"}}}}} -->
<p class="sakolaan-badge sakolaan-badge--warning sakolaan-optional"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"lg"} /-->

<!-- wp:post-excerpt {"excerptLength":24,"fontSize":"sm"} /--></div>
<!-- /wp:group -->
