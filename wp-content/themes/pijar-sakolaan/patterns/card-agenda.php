<?php
/**
 * Title: Kartu agenda
 * Slug: pijar-sakolaan/card-agenda
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
<div class="wp-block-group is-style-sakolaan-card sakolaan-card--link sakolaan-post-card"><!-- wp:group {"className":"sakolaan-card__head","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group sakolaan-card__head"><!-- wp:group {"className":"sakolaan-date-tile","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-date-tile"><!-- wp:paragraph {"className":"sakolaan-date-tile__day sakolaan-optional","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"tanggal_mulai","format":"day"}}}}} -->
<p class="sakolaan-date-tile__day sakolaan-optional"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-date-tile__month sakolaan-optional","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"tanggal_mulai","format":"month"}}}}} -->
<p class="sakolaan-date-tile__month sakolaan-optional"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"sakolaan-badge sakolaan-optional","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"status_label"}}}}} -->
<p class="sakolaan-badge sakolaan-optional"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"lg"} /-->

<!-- wp:paragraph {"className":"sakolaan-meta sakolaan-optional","fontSize":"sm","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"waktu"}}}}} -->
<p class="sakolaan-meta sakolaan-optional has-sm-font-size"></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-meta sakolaan-optional","fontSize":"sm","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"lokasi"}}}}} -->
<p class="sakolaan-meta sakolaan-optional has-sm-font-size"></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
