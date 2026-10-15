<?php
/**
 * Title: Hasil tidak ditemukan
 * Slug: sakolaan-theme/no-results
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"sakolaan-empty","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-empty"><!-- wp:heading {"level":2,"fontSize":"xl"} -->
<h2 class="wp-block-heading has-xl-font-size"><?php esc_html_e( 'Konten tidak ditemukan', 'sakolaan-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sakolaan-text-muted"} -->
<p class="sakolaan-text-muted"><?php esc_html_e( 'Belum ada konten yang cocok. Coba kata kunci lain.', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:search {"label":"<?php echo esc_attr__( 'Cari', 'sakolaan-theme' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr__( 'Cari di situs ini', 'sakolaan-theme' ); ?>","buttonText":"<?php echo esc_attr__( 'Cari', 'sakolaan-theme' ); ?>"} /--></div>
<!-- /wp:group -->
