<?php
/**
 * Title: Program Unggulan
 * Slug: sakolaan-theme/featured-programs
 * Categories: sakolaan-sections
 * Description: Program unggulan sekolah.
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = sakolaan_theme_get_archive_url( 'program_unggulan' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--programs","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--programs"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo sakolaan_theme_section_header_markup( __( 'Program Unggulan', 'sakolaan-theme' ), $sakolaan_url, __( 'Lihat semua program', 'sakolaan-theme' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper aman.
?>

<?php
echo sakolaan_theme_build_loop_markup( array( 'post_type' => 'program_unggulan', 'per_page' => sakolaan_theme_homepage_item_count( 'programs', 4 ), 'orderby' => 'menu_order', 'order' => 'asc', 'class' => 'sakolaan-loop--programs', 'card' => 'program', 'min' => '18rem', 'empty' => 'program_unggulan', 'query_id' => 9 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper aman.
?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
