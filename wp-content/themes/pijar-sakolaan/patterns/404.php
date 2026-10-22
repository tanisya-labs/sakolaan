<?php
/**
 * Title: Konten 404
 * Slug: pijar-sakolaan/404
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
<div class="wp-block-group sakolaan-empty"><!-- wp:heading {"level":1,"fontSize":"2xl"} -->
<h1 class="wp-block-heading has-2-xl-font-size"><?php esc_html_e( 'Halaman tidak ditemukan', 'pijar-sakolaan' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sakolaan-text-muted"} -->
<p class="sakolaan-text-muted"><?php esc_html_e( 'Alamat yang Anda buka tidak ada atau sudah dipindahkan. Coba cari, atau kembali ke beranda.', 'pijar-sakolaan' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:search {"label":"<?php echo esc_attr__( 'Cari', 'pijar-sakolaan' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr__( 'Cari di situs ini', 'pijar-sakolaan' ); ?>","buttonText":"<?php echo esc_attr__( 'Cari', 'pijar-sakolaan' ); ?>"} /-->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Kembali ke beranda', 'pijar-sakolaan' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
