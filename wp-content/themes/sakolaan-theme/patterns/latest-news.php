<?php
/**
 * Title: Berita terbaru
 * Slug: sakolaan-theme/latest-news
 * Categories: sakolaan-sections
 * Description: Grid berita terbaru dengan kartu sederhana dan seragam.
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = sakolaan_theme_get_archive_url( 'berita' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--news","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--news"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo sakolaan_theme_section_header_markup( __( 'Berita terbaru', 'sakolaan-theme' ), $sakolaan_url, __( 'Lihat semua berita', 'sakolaan-theme' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Di-escape di helper.
?>

<!-- wp:group {"className":"sakolaan-news-layout","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-news-layout"><?php
echo sakolaan_theme_build_loop_markup( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON dan atribut di-escape di helper.
	array(
		'post_type' => 'post',
		'per_page'  => sakolaan_theme_homepage_item_count( 'news', 6 ),
		'class'     => 'sakolaan-news-grid',
		'card'      => 'berita',
		'layout'    => 'grid',
		'min'       => '18rem',
		'empty'     => 'berita',
		'query_id'  => 1,
	)
);
?></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
