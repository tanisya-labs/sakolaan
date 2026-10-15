<?php
/**
 * Register blocks used by the companion Sakolaan Theme.
 *
 * Block names intentionally retain the sakolaan-theme namespace so existing
 * templates, patterns, and saved content continue to work unchanged.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the companion theme's blocks only while that parent theme is active.
 *
 * The render callbacks use presentation helpers supplied by Sakolaan Theme, so
 * the blocks must remain unavailable under unrelated themes.
 *
 * @return void
 */
function sakolaan_register_theme_blocks() {
	$status = function_exists( 'sakolaan_get_companion_theme_status' ) ? sakolaan_get_companion_theme_status() : array();

	if ( empty( $status['compatible'] ) || ! function_exists( 'sakolaan_theme_is_compatible' ) || ! sakolaan_theme_is_compatible() || ! function_exists( 'sakolaan_theme_get_setting' ) ) {
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
