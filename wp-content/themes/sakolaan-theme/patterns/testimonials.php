<?php
/**
 * Title: Testimoni Alumni
 * Slug: sakolaan-theme/testimonials
 * Categories: sakolaan-sections
 * Description: Cerita singkat alumni.
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = sakolaan_theme_get_archive_url( 'testimoni' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--testimonials is-style-sakolaan-dark","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--testimonials is-style-sakolaan-dark"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo sakolaan_theme_section_header_markup( __( 'Testimoni Alumni', 'sakolaan-theme' ), $sakolaan_url, __( 'Baca cerita alumni', 'sakolaan-theme' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper aman.
?>

<?php
echo sakolaan_theme_build_loop_markup( array( 'post_type' => 'testimoni', 'per_page' => sakolaan_theme_homepage_item_count( 'testimonials', 3 ), 'orderby' => 'menu_order', 'order' => 'asc', 'class' => 'sakolaan-loop--testimonials', 'card' => 'testimoni', 'min' => '18rem', 'empty' => 'testimoni', 'query_id' => 10 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper aman.
?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
