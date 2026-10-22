<?php
/**
 * Title: Prestasi
 * Slug: pijar-sakolaan/achievements
 * Categories: sakolaan-sections
 * Description: Prestasi terbaru.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = pijar_sakolaan_get_archive_url( 'prestasi' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--achievements is-style-sakolaan-tint","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--achievements is-style-sakolaan-tint"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo pijar_sakolaan_section_header_markup( __( 'Prestasi', 'pijar-sakolaan' ), $sakolaan_url, __( 'Lihat semua prestasi', 'pijar-sakolaan' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Di-escape di helper.
?>

<?php
echo pijar_sakolaan_build_loop_markup( array( 'post_type' => 'prestasi', 'per_page' => pijar_sakolaan_homepage_item_count( 'achievements', 3 ), 'class' => 'sakolaan-loop--achievements', 'card' => 'prestasi', 'min' => '18rem', 'empty' => 'prestasi', 'query_id' => 5 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON dan atribut di-escape di helper.
?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
