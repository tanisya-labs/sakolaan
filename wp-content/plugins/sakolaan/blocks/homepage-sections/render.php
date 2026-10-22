<?php
/**
 * Render seluruh section beranda setelah hero.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sakolaan-homepage-sections' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dibersihkan oleh get_block_wrapper_attributes(). ?>>
	<?php echo pijar_sakolaan_render_homepage_sections( isset( $attributes['sectionLayouts'] ) ? $attributes['sectionLayouts'] : array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pattern terdaftar dan masing-masing block meng-escape output. ?>
</div>
