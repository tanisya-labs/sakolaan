<?php
/**
 * Title: Galeri
 * Slug: pijar-sakolaan/gallery
 * Categories: sakolaan-sections
 * Description: Galeri foto terbaru.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = pijar_sakolaan_get_archive_url( 'galeri' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--gallery","backgroundColor":"surface","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--gallery has-surface-background-color has-background"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo pijar_sakolaan_section_header_markup( __( 'Galeri', 'pijar-sakolaan' ), $sakolaan_url, __( 'Lihat semua galeri', 'pijar-sakolaan' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Di-escape di helper.
?>

<?php
echo pijar_sakolaan_build_loop_markup( array( 'post_type' => 'galeri', 'per_page' => pijar_sakolaan_homepage_item_count( 'gallery', 6 ), 'class' => 'sakolaan-loop--gallery', 'card' => 'galeri', 'min' => '14rem', 'empty' => 'galeri', 'query_id' => 7 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON dan atribut di-escape di helper.
?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
