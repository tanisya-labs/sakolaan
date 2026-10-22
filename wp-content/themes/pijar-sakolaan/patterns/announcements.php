<?php
/**
 * Title: Pengumuman
 * Slug: pijar-sakolaan/announcements
 * Categories: sakolaan-sections
 * Description: Pengumuman aktif terbaru.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = pijar_sakolaan_get_archive_url( 'pengumuman' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--announcements","backgroundColor":"surface","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--announcements has-surface-background-color has-background"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo pijar_sakolaan_section_header_markup( __( 'Pengumuman', 'pijar-sakolaan' ), $sakolaan_url, __( 'Lihat semua pengumuman', 'pijar-sakolaan' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Di-escape di helper.
?>

<?php
echo pijar_sakolaan_build_loop_markup( array( 'post_type' => 'pengumuman', 'per_page' => pijar_sakolaan_homepage_item_count( 'announcements', 4 ), 'class' => 'sakolaan-query--active', 'card' => 'pengumuman', 'min' => '20rem', 'empty' => 'pengumuman', 'query_id' => 3 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON dan atribut di-escape di helper.
?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
