<?php
/**
 * Pijar Sakolaan bootstrap.
 *
 * File ini hanya memuat modul di inc/. Jangan menambahkan logika di sini.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PIJAR_SAKOLAAN_VERSION' ) ) {
	define( 'PIJAR_SAKOLAAN_VERSION', '0.0.1' );
}
if ( ! defined( 'PIJAR_SAKOLAAN_SLUG' ) ) {
	define( 'PIJAR_SAKOLAAN_SLUG', 'pijar-sakolaan' );
}
if ( ! defined( 'PIJAR_SAKOLAAN_PLUGIN_BASENAME' ) ) {
	define( 'PIJAR_SAKOLAAN_PLUGIN_BASENAME', 'sakolaan/sakolaan.php' );
}

$pijar_sakolaan_includes = array(
	'inc/compat.php',
	'inc/helpers.php',
	'inc/setup.php',
	'inc/enqueue.php',
	'inc/patterns.php',
	'inc/sections.php',
);

foreach ( $pijar_sakolaan_includes as $pijar_sakolaan_file ) {
	require_once get_theme_file_path( $pijar_sakolaan_file );
}

if ( pijar_sakolaan_is_compatible() ) {
	$pijar_sakolaan_integration_files = array(
		'inc/post-meta.php',
		'inc/bindings.php',
		'inc/placeholders.php',
		'inc/queries.php',
		'inc/breadcrumbs.php',
		'inc/seed.php',
	);

	foreach ( $pijar_sakolaan_integration_files as $pijar_sakolaan_file ) {
		require_once get_theme_file_path( $pijar_sakolaan_file );
	}
}

unset( $pijar_sakolaan_includes, $pijar_sakolaan_integration_files, $pijar_sakolaan_file );
