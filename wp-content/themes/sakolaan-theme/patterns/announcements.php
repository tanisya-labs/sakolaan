<?php
/**
 * Title: Pengumuman
 * Slug: sakolaan-theme/announcements
 * Categories: sakolaan-sections
 * Description: Pengumuman aktif terbaru.
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = sakolaan_theme_get_archive_url( 'pengumuman' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--announcements","backgroundColor":"surface","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--announcements has-surface-background-color has-background"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo sakolaan_theme_section_header_markup( __( 'Pengumuman', 'sakolaan-theme' ), $sakolaan_url, __( 'Lihat semua pengumuman', 'sakolaan-theme' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Di-escape di helper.
?>

<?php
echo sakolaan_theme_build_loop_markup( array( 'post_type' => 'pengumuman', 'per_page' => sakolaan_theme_homepage_item_count( 'announcements', 4 ), 'class' => 'sakolaan-query--active', 'card' => 'pengumuman', 'min' => '20rem', 'empty' => 'pengumuman', 'query_id' => 3 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON dan atribut di-escape di helper.
?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
