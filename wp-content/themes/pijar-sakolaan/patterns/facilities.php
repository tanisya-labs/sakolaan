<?php
/**
 * Title: Fasilitas
 * Slug: pijar-sakolaan/facilities
 * Categories: sakolaan-sections
 * Description: Daftar fasilitas sekolah.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = pijar_sakolaan_get_archive_url( 'fasilitas' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--facilities is-style-sakolaan-tint","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--facilities is-style-sakolaan-tint"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo pijar_sakolaan_section_header_markup( __( 'Fasilitas', 'pijar-sakolaan' ), $sakolaan_url, __( 'Lihat semua fasilitas', 'pijar-sakolaan' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper aman.
?>

<?php
echo pijar_sakolaan_build_loop_markup( array( 'post_type' => 'fasilitas', 'per_page' => pijar_sakolaan_homepage_item_count( 'facilities', 6 ), 'orderby' => 'menu_order', 'order' => 'asc', 'class' => 'sakolaan-loop--facilities', 'card' => 'fasilitas', 'min' => '18rem', 'empty' => 'fasilitas', 'query_id' => 8 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper aman.
?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
