<?php
/**
 * Title: Berita terbaru
 * Slug: pijar-sakolaan/latest-news
 * Categories: sakolaan-sections
 * Description: Grid berita terbaru dengan kartu sederhana dan seragam.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = pijar_sakolaan_get_archive_url( 'berita' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--news","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--news"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo pijar_sakolaan_section_header_markup( __( 'Berita terbaru', 'pijar-sakolaan' ), $sakolaan_url, __( 'Lihat semua berita', 'pijar-sakolaan' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Di-escape di helper.
?>

<!-- wp:group {"className":"sakolaan-news-layout","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-news-layout"><?php
echo pijar_sakolaan_build_loop_markup( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON dan atribut di-escape di helper.
	array(
		'post_type' => 'post',
		'per_page'  => pijar_sakolaan_homepage_item_count( 'news', 6 ),
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
