<?php
/**
 * Sakolaan Theme bootstrap.
 *
 * File ini hanya memuat modul di inc/. Jangan menambahkan logika di sini.
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SAKOLAAN_THEME_VERSION' ) ) {
	define( 'SAKOLAAN_THEME_VERSION', '0.0.1' );
}

$sakolaan_theme_includes = array(
	'inc/compat.php',
	'inc/helpers.php',
	'inc/setup.php',
	'inc/enqueue.php',
	'inc/patterns.php',
	'inc/sections.php',
);

foreach ( $sakolaan_theme_includes as $sakolaan_theme_file ) {
	require_once get_theme_file_path( $sakolaan_theme_file );
}

if ( sakolaan_theme_is_compatible() ) {
	$sakolaan_theme_integration_files = array(
		'inc/post-meta.php',
		'inc/bindings.php',
		'inc/placeholders.php',
		'inc/queries.php',
		'inc/breadcrumbs.php',
		'inc/seed.php',
	);

	foreach ( $sakolaan_theme_integration_files as $sakolaan_theme_file ) {
		require_once get_theme_file_path( $sakolaan_theme_file );
	}
}

unset( $sakolaan_theme_includes, $sakolaan_theme_integration_files, $sakolaan_theme_file );
