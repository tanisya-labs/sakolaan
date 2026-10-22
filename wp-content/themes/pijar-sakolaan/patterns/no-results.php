<?php
/**
 * Title: Hasil tidak ditemukan
 * Slug: pijar-sakolaan/no-results
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
<h2 class="wp-block-heading has-xl-font-size"><?php esc_html_e( 'Konten tidak ditemukan', 'pijar-sakolaan' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sakolaan-text-muted"} -->
<p class="sakolaan-text-muted"><?php esc_html_e( 'Belum ada konten yang cocok. Coba kata kunci lain.', 'pijar-sakolaan' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:search {"label":"<?php echo esc_attr__( 'Cari', 'pijar-sakolaan' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr__( 'Cari di situs ini', 'pijar-sakolaan' ); ?>","buttonText":"<?php echo esc_attr__( 'Cari', 'pijar-sakolaan' ); ?>"} /--></div>
<!-- /wp:group -->
