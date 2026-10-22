<?php
/**
 * Title: Program Unggulan
 * Slug: pijar-sakolaan/featured-programs
 * Categories: sakolaan-sections
 * Description: Program unggulan sekolah.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = pijar_sakolaan_get_archive_url( 'program_unggulan' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--programs","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--programs"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo pijar_sakolaan_section_header_markup( __( 'Program Unggulan', 'pijar-sakolaan' ), $sakolaan_url, __( 'Lihat semua program', 'pijar-sakolaan' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper aman.
?>

<?php
echo pijar_sakolaan_build_loop_markup( array( 'post_type' => 'program_unggulan', 'per_page' => pijar_sakolaan_homepage_item_count( 'programs', 4 ), 'orderby' => 'menu_order', 'order' => 'asc', 'class' => 'sakolaan-loop--programs', 'card' => 'program', 'min' => '18rem', 'empty' => 'program_unggulan', 'query_id' => 9 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper aman.
?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
