<?php
/**
 * Title: Keadaan kosong
 * Slug: pijar-sakolaan/empty-state
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"sakolaan-empty","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-empty"><!-- wp:heading {"level":2,"fontSize":"xl"} -->
<h2 class="wp-block-heading has-xl-font-size"><?php esc_html_e( 'Belum ada konten', 'pijar-sakolaan' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sakolaan-text-muted"} -->
<p class="sakolaan-text-muted"><?php esc_html_e( 'Konten untuk bagian ini belum tersedia. Silakan kembali lagi nanti.', 'pijar-sakolaan' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
