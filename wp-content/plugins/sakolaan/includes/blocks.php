<?php
/**
 * Register blocks used by Pijar Sakolaan, the default theme for Sakolaan.
 *
 * Block names use the pijar-sakolaan namespace shared by the default theme's
 * templates, patterns, and newly saved content.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the companion theme's blocks only while that parent theme is active.
 *
 * The render callbacks use presentation helpers supplied by Pijar Sakolaan, so
 * the blocks must remain unavailable under unrelated themes.
 *
 * @return void
 */
function sakolaan_register_theme_blocks() {
	$status = function_exists( 'sakolaan_get_companion_theme_status' ) ? sakolaan_get_companion_theme_status() : array();

	if ( empty( $status['compatible'] ) || empty( $status['runtime_ready'] ) || ! function_exists( 'pijar_sakolaan_is_compatible' ) || ! pijar_sakolaan_is_compatible() ) {
		return;
	}

	$blocks = array(
		'archive-controls',
		'breadcrumb',
		'detail-byline',
		'hero-slider',
		'homepage-sections',
		'map',
		'meta-gallery',
		'meta-list',
		'setting-image',
		'social-links',
		'term-filter',
	);

	foreach ( $blocks as $block ) {
		register_block_type( SAKOLAAN_PATH . 'blocks/' . $block );
	}
}
add_action( 'init', 'sakolaan_register_theme_blocks', 20 );
