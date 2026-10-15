<?php
/**
 * Title: Ekstrakurikuler
 * Slug: sakolaan-theme/extracurricular
 * Categories: sakolaan-sections
 * Description: Kegiatan ekstrakurikuler, diurutkan menurut urutan tampilan.
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = sakolaan_theme_get_archive_url( 'ekstrakurikuler' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--extracurricular","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--extracurricular"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo sakolaan_theme_section_header_markup( __( 'Ekstrakurikuler', 'sakolaan-theme' ), $sakolaan_url, __( 'Lihat semua ekstrakurikuler', 'sakolaan-theme' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Di-escape di helper.
?>

<?php
echo sakolaan_theme_build_loop_markup( array( 'post_type' => 'ekstrakurikuler', 'per_page' => sakolaan_theme_homepage_item_count( 'extracurricular', 6 ), 'orderby' => 'menu_order', 'order' => 'asc', 'class' => 'sakolaan-loop--extracurricular', 'card' => 'ekskul', 'min' => '18rem', 'empty' => 'ekstrakurikuler', 'query_id' => 6 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON dan atribut di-escape di helper.
?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
