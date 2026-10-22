<?php
/**
 * Enqueue CSS dan JS.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Berkas CSS sumber, berurutan.
 *
 * @return string[]
 */
function pijar_sakolaan_get_css_sources() {
	return array(
		'assets/css/base.css',
		'assets/css/utilities.css',
		'assets/css/components.css',
		'assets/css/site.css',
	);
}

/**
 * Pakai CSS sumber hanya saat mode aset pengembangan diaktifkan secara eksplisit.
 *
 * @return bool
 */
function pijar_sakolaan_use_combined_css() {
	$dev_assets = defined( 'PIJAR_SAKOLAAN_DEV_ASSETS' ) && PIJAR_SAKOLAAN_DEV_ASSETS;

	if ( $dev_assets ) {
		foreach ( pijar_sakolaan_get_css_sources() as $source ) {
			if ( ! is_readable( get_theme_file_path( $source ) ) ) {
				return true;
			}
		}

		return false;
	}

	return is_readable( get_theme_file_path( 'assets/css/theme.css' ) );
}

/**
 * CSS frontend.
 *
 * @return void
 */
function pijar_sakolaan_enqueue_assets() {
	if ( pijar_sakolaan_use_combined_css() ) {
		wp_enqueue_style(
			'pijar-sakolaan',
			pijar_sakolaan_asset_uri( 'assets/css/theme.css' ),
			array(),
			pijar_sakolaan_asset_version( 'assets/css/theme.css' )
		);

		return;
	}

	$previous = array();

	foreach ( pijar_sakolaan_get_css_sources() as $source ) {
		$handle = 'pijar-sakolaan-' . basename( $source, '.css' );

		wp_enqueue_style(
			$handle,
			pijar_sakolaan_asset_uri( $source ),
			$previous,
			pijar_sakolaan_asset_version( $source )
		);

		$previous = array( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'pijar_sakolaan_enqueue_assets' );

/** Load the small scroll-direction behavior used by the optional smart header style. */
function pijar_sakolaan_enqueue_header_script() {
	wp_enqueue_script( 'pijar-sakolaan-header', pijar_sakolaan_asset_uri( 'assets/js/header.js' ), array(), pijar_sakolaan_asset_version( 'assets/js/header.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
}
add_action( 'wp_enqueue_scripts', 'pijar_sakolaan_enqueue_header_script' );

/** Load the progressive viewport and interaction motion system. */
function pijar_sakolaan_enqueue_motion_script() {
	wp_enqueue_script( 'pijar-sakolaan-motion', pijar_sakolaan_asset_uri( 'assets/js/motion.js' ), array(), pijar_sakolaan_asset_version( 'assets/js/motion.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
}
add_action( 'wp_enqueue_scripts', 'pijar_sakolaan_enqueue_motion_script' );

/**
 * Terapkan lebar H1 hero dari Pengaturan Sakolaan tanpa membuat stylesheet baru.
 *
 * @return void
 */
function pijar_sakolaan_add_dynamic_hero_style() {
	$width = absint( pijar_sakolaan_get_setting( 'homepage.hero_heading_width', 24 ) );
	$width = min( 40, max( 12, $width ) );
	$handle = pijar_sakolaan_use_combined_css() ? 'pijar-sakolaan' : 'pijar-sakolaan-site';

	wp_add_inline_style(
		$handle,
		sprintf( ':root{--sakolaan-hero-heading-width:%dch;}', $width )
	);
}
add_action( 'wp_enqueue_scripts', 'pijar_sakolaan_add_dynamic_hero_style', 20 );

/**
 * Preload font yang dipakai di atas lipatan (body dan heading) untuk LCP lebih baik.
 *
 * @return void
 */
function pijar_sakolaan_preload_fonts() {
	$fonts = array(
		'assets/fonts/public-sans-latin-wght-normal.woff2',
	);

	foreach ( $fonts as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( pijar_sakolaan_asset_uri( $font ) )
		);
	}
}
add_action( 'wp_head', 'pijar_sakolaan_preload_fonts', 1 );

/**
 * Muat JavaScript lightbox galeri. Dipanggil oleh block meta-gallery saat dirender,
 * sehingga skrip hanya dimuat di halaman yang membutuhkannya.
 *
 * @return void
 */
function pijar_sakolaan_enqueue_gallery_script() {
	wp_enqueue_script(
		'pijar-sakolaan-gallery',
		pijar_sakolaan_asset_uri( 'assets/js/gallery.js' ),
		array(),
		pijar_sakolaan_asset_version( 'assets/js/gallery.js' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
