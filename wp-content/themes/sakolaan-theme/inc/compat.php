<?php
/**
 * Fail-safe compatibility layer for the Sakolaan companion plugin.
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load notice styles independently from the companion plugin.
 *
 * @return void
 */
function sakolaan_theme_enqueue_admin_notice_style() {
	$relative_path = 'assets/css/admin-notices.css';
	$path          = get_theme_file_path( $relative_path );
	$version       = is_file( $path ) ? (string) filemtime( $path ) : SAKOLAAN_THEME_VERSION;

	wp_enqueue_style( 'sakolaan-admin-notices', get_theme_file_uri( $relative_path ), array( 'common' ), $version );
}
add_action( 'admin_enqueue_scripts', 'sakolaan_theme_enqueue_admin_notice_style', 1 );

/**
 * Inspect plugin availability and enforce the exact companion version.
 *
 * @return array{code:string,compatible:bool,plugin_installed:bool,plugin_version:string,theme_version:string}
 */
function sakolaan_theme_dependency_status() {
	static $status = null;

	if ( is_array( $status ) ) {
		return $status;
	}

	$theme          = wp_get_theme( get_template() );
	$header_version = trim( (string) $theme->get( 'Version' ) );
	$plugin_file    = WP_PLUGIN_DIR . '/sakolaan/sakolaan.php';
	$plugin_version = defined( 'SAKOLAAN_VERSION' ) ? trim( (string) SAKOLAAN_VERSION ) : '';
	$code           = 'compatible';

	if ( SAKOLAAN_THEME_VERSION !== $header_version || ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', SAKOLAAN_THEME_VERSION ) ) {
		$code = 'theme_version_invalid';
	} elseif ( ! defined( 'SAKOLAAN_VERSION' ) ) {
		$code = file_exists( $plugin_file ) ? 'plugin_inactive' : 'plugin_missing';
	} elseif ( ! function_exists( 'sakolaan_validate_companion_version' ) || ! function_exists( 'sakolaan_get_setting' ) ) {
		$code = 'plugin_incomplete';
	} else {
		$validation = sakolaan_validate_companion_version( SAKOLAAN_THEME_VERSION );
		if ( is_wp_error( $validation ) ) {
			$code = $validation->get_error_code();
		}
	}

	$status = array(
		'code'             => $code,
		'compatible'       => 'compatible' === $code,
		'plugin_installed' => file_exists( $plugin_file ),
		'plugin_version'   => $plugin_version,
		'theme_version'    => SAKOLAAN_THEME_VERSION,
	);

	return $status;
}

/**
 * Whether all plugin-backed theme integrations may run.
 *
 * @return bool
 */
function sakolaan_theme_is_compatible() {
	$status = sakolaan_theme_dependency_status();
	return $status['compatible'];
}

/**
 * Backward-compatible helper used throughout the theme.
 *
 * @return bool
 */
function sakolaan_theme_is_core_active() {
	return sakolaan_theme_is_compatible();
}

/**
 * Explain the safe-mode state and direct administrators to the correct screen.
 *
 * @return void
 */
function sakolaan_theme_core_inactive_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$status = sakolaan_theme_dependency_status();

	if ( $status['compatible'] ) {
		return;
	}

	$class = 'notice sakolaan-notice notice-error';
	$url   = admin_url( 'plugins.php' );
	$label = __( 'Buka halaman Plugin', 'sakolaan-theme' );

	if ( 'plugin_missing' === $status['code'] ) {
		$message = __( 'Sakolaan Theme berjalan dalam mode aman karena plugin Sakolaan belum terpasang. Unggah sakolaan.zip versi 0.0.1 lalu aktifkan plugin.', 'sakolaan-theme' );
		if ( current_user_can( 'install_plugins' ) ) {
			$url   = admin_url( 'plugin-install.php?tab=upload' );
			$label = __( 'Unggah plugin Sakolaan', 'sakolaan-theme' );
		}
	} elseif ( 'plugin_inactive' === $status['code'] ) {
		$message = __( 'Sakolaan Theme berjalan dalam mode aman. Plugin Sakolaan versi 0.0.1 sudah terpasang tetapi belum aktif.', 'sakolaan-theme' );
	} else {
		$message = sprintf(
			/* translators: 1: theme version, 2: active plugin version. */
			__( 'Sakolaan Theme berjalan dalam mode aman karena versi tidak cocok atau instalasi plugin tidak lengkap. Theme: %1$s; plugin: %2$s. Instal pasangan dengan versi yang sama.', 'sakolaan-theme' ),
			$status['theme_version'],
			$status['plugin_version'] ?: __( 'tidak tersedia', 'sakolaan-theme' )
		);
	}

	printf(
		'<div class="%1$s"><p>%2$s <a href="%3$s">%4$s</a></p></div>',
		esc_attr( $class ),
		esc_html( $message ),
		esc_url( $url ),
		esc_html( $label )
	);
}
add_action( 'admin_notices', 'sakolaan_theme_core_inactive_notice' );

/**
 * Warn when a customized front page has not adopted the ordered renderer.
 *
 * @return void
 */
function sakolaan_theme_homepage_order_notice() {
	if ( ! sakolaan_theme_is_compatible() || ! current_user_can( 'edit_theme_options' ) || ! function_exists( 'get_block_template' ) ) {
		return;
	}

	$template = get_block_template( get_stylesheet() . '//front-page', 'wp_template' );

	if ( ! $template || 'custom' !== $template->source || false !== strpos( (string) $template->content, 'sakolaan-theme/homepage-sections' ) ) {
		return;
	}

	printf(
		'<div class="notice sakolaan-notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
		esc_html__( 'Template Beranda telah dikustomisasi dan belum memakai block Susunan Beranda. Pengaturan urutan section belum dapat diterapkan pada template tersebut.', 'sakolaan-theme' ),
		esc_url( admin_url( 'site-editor.php?path=/wp_template/all' ) ),
		esc_html__( 'Buka Editor', 'sakolaan-theme' )
	);
}
add_action( 'admin_notices', 'sakolaan_theme_homepage_order_notice' );
