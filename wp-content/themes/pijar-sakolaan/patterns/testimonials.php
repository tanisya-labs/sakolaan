<?php
/**
 * Title: Testimoni Alumni
 * Slug: pijar-sakolaan/testimonials
 * Categories: sakolaan-sections
 * Description: Cerita singkat alumni.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = pijar_sakolaan_get_archive_url( 'testimoni' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--testimonials is-style-sakolaan-dark","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--testimonials is-style-sakolaan-dark"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo pijar_sakolaan_section_header_markup( __( 'Testimoni Alumni', 'pijar-sakolaan' ), $sakolaan_url, __( 'Baca cerita alumni', 'pijar-sakolaan' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper aman.
?>

<?php
echo pijar_sakolaan_build_loop_markup( array( 'post_type' => 'testimoni', 'per_page' => pijar_sakolaan_homepage_item_count( 'testimonials', 3 ), 'orderby' => 'menu_order', 'order' => 'asc', 'class' => 'sakolaan-loop--testimonials', 'card' => 'testimoni', 'min' => '18rem', 'empty' => 'testimoni', 'query_id' => 10 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper aman.
?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
