<?php
/**
 * Theme setup: supports, image sizes, block styles, pattern categories.
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme supports dan textdomain.
 *
 * @return void
 */
function sakolaan_theme_setup() {
	load_theme_textdomain( 'sakolaan-theme', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	// Gaya yang sama dipakai di editor agar tampilan Site Editor sesuai frontend.
	add_editor_style(
		array(
			'assets/css/base.css',
			'assets/css/utilities.css',
			'assets/css/components.css',
			'assets/css/site.css',
		)
	);

	// Ukuran gambar khusus. Nilai dipakai juga sebagai sizeSlug di block featured image.
	add_image_size( 'sakolaan-card', 640, 400, true );
	add_image_size( 'sakolaan-portrait', 480, 600, true );
	add_image_size( 'sakolaan-hero', 1200, 900, true );
	add_image_size( 'sakolaan-wide', 1280, 720, true );
}
add_action( 'after_setup_theme', 'sakolaan_theme_setup' );

/**
 * Tampilkan ukuran gambar khusus di pilihan ukuran editor.
 *
 * @param array $sizes Ukuran yang tersedia.
 * @return array
 */
function sakolaan_theme_image_size_names( $sizes ) {
	return array_merge(
		$sizes,
		array(
			'sakolaan-card'     => __( 'Kartu (16:10)', 'sakolaan-theme' ),
			'sakolaan-portrait' => __( 'Potret (4:5)', 'sakolaan-theme' ),
			'sakolaan-hero'     => __( 'Hero (4:3)', 'sakolaan-theme' ),
			'sakolaan-wide'     => __( 'Lebar (16:9)', 'sakolaan-theme' ),
		)
	);
}
add_filter( 'image_size_names_choose', 'sakolaan_theme_image_size_names' );

/**
 * Daftarkan kategori pattern.
 *
 * @return void
 */
function sakolaan_theme_register_pattern_categories() {
	register_block_pattern_category(
		'sakolaan-sections',
		array( 'label' => __( 'Sekolah: Section', 'sakolaan-theme' ) )
	);
	register_block_pattern_category(
		'sakolaan-pages',
		array( 'label' => __( 'Sekolah: Halaman', 'sakolaan-theme' ) )
	);
}
add_action( 'init', 'sakolaan_theme_register_pattern_categories' );

/**
 * Daftarkan block style. CSS-nya ada di assets/css/components.css.
 *
 * @return void
 */
function sakolaan_theme_register_block_styles() {
	register_block_style(
		'core/button',
		array(
			'name'  => 'accent',
			'label' => __( 'Aksen', 'sakolaan-theme' ),
		)
	);

	register_block_style(
		'core/group',
		array(
			'name'  => 'sakolaan-card',
			'label' => __( 'Kartu', 'sakolaan-theme' ),
		)
	);

	register_block_style(
		'core/group',
		array(
			'name'  => 'sakolaan-tint',
			'label' => __( 'Latar muda', 'sakolaan-theme' ),
		)
	);

	register_block_style(
		'core/group',
		array(
			'name'  => 'sakolaan-dark',
			'label' => __( 'Latar gelap', 'sakolaan-theme' ),
		)
	);

	foreach ( array(
		'sakolaan-header-rounded' => __( 'Header: rounded', 'sakolaan-theme' ),
		'sakolaan-header-sticky'  => __( 'Header: sticky', 'sakolaan-theme' ),
		'sakolaan-header-floating' => __( 'Header: floating', 'sakolaan-theme' ),
		'sakolaan-header-smart'   => __( 'Header: floating, sembunyi saat scroll turun', 'sakolaan-theme' ),
	) as $name => $label ) {
		register_block_style( 'core/group', array( 'name' => $name, 'label' => $label ) );
	}

	register_block_style( 'core/group', array( 'name' => 'sakolaan-card-quiet', 'label' => __( 'Kartu: sederhana', 'sakolaan-theme' ) ) );
	register_block_style( 'core/group', array( 'name' => 'sakolaan-panel', 'label' => __( 'Panel informasi', 'sakolaan-theme' ) ) );
	register_block_style( 'core/list', array( 'name' => 'sakolaan-list-check', 'label' => __( 'Daftar: centang', 'sakolaan-theme' ) ) );
	register_block_style( 'core/button', array( 'name' => 'sakolaan-button-outline', 'label' => __( 'Tombol: outline', 'sakolaan-theme' ) ) );
}
add_action( 'init', 'sakolaan_theme_register_block_styles' );

/** Apply the Sakolaan header behavior without rewriting Site Editor content. */
function sakolaan_theme_apply_header_behavior( $block_content, $block ) {
	$class_name = (string) ( $block['attrs']['className'] ?? '' );
	if ( 'core/group' !== ( $block['blockName'] ?? '' ) || ! preg_match( '/(?:^|\s)sakolaan-header(?:\s|$)/', $class_name ) || ! function_exists( 'sakolaan_theme_get_setting' ) ) {
		return $block_content;
	}
	$behavior = (string) sakolaan_theme_get_setting( 'header.behavior', 'inherit' );
	if ( 'inherit' === $behavior ) {
		return $block_content;
	}
	$block_content = preg_replace( '/\s+is-style-sakolaan-header-(?:sticky|smart|floating)\b/', '', $block_content );
	if ( in_array( $behavior, array( 'sticky', 'smart' ), true ) ) {
		$class = ' is-style-sakolaan-header-' . $behavior;
		$block_content = preg_replace( '/class="([^"]*\bsakolaan-header\b[^"]*)"/', 'class="$1' . $class . '"', $block_content, 1 );
	}
	return $block_content;
}
add_filter( 'render_block_core/group', 'sakolaan_theme_apply_header_behavior', 10, 2 );
