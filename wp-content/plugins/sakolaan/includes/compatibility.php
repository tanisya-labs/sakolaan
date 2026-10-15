<?php
/**
 * Compatibility contract between the Sakolaan plugin and companion theme.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validate a companion theme version against the running plugin.
 *
 * @param mixed $theme_version Theme version read from style.css.
 * @return true|WP_Error
 */
function sakolaan_validate_companion_version( $theme_version ) {
	$theme_version = is_scalar( $theme_version ) ? trim( (string) $theme_version ) : '';

	if ( '' === $theme_version || ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $theme_version ) ) {
		return new WP_Error( 'sakolaan_theme_version_invalid', __( 'Versi Sakolaan Theme tidak valid.', 'sakolaan' ) );
	}

	if ( SAKOLAAN_VERSION !== $theme_version ) {
		return new WP_Error( 'sakolaan_version_mismatch', __( 'Versi plugin dan theme Sakolaan harus sama.', 'sakolaan' ) );
	}

	return true;
}

/**
 * Inspect installation, activation, and version state of the companion theme.
 *
 * @return array{code:string,compatible:bool,installed:bool,active:bool,plugin_version:string,theme_version:string}
 */
function sakolaan_get_companion_theme_status() {
	$theme         = wp_get_theme( 'sakolaan-theme' );
	$installed     = $theme->exists() && ! is_wp_error( $theme->errors() );
	$active        = 'sakolaan-theme' === get_template();
	$theme_version = $installed ? trim( (string) $theme->get( 'Version' ) ) : '';
	$code          = 'compatible';

	if ( ! $installed ) {
		$code = 'theme_missing';
	} else {
		$validation = sakolaan_validate_companion_version( $theme_version );

		if ( is_wp_error( $validation ) ) {
			$code = $validation->get_error_code();
		} elseif ( ! $active ) {
			$code = 'theme_inactive';
		}
	}

	return array(
		'code'           => $code,
		'compatible'     => 'compatible' === $code,
		'installed'      => $installed,
		'active'         => $active,
		'plugin_version' => SAKOLAAN_VERSION,
		'theme_version'  => $theme_version,
	);
}

/**
 * Tell administrators how to install or activate the matching companion theme.
 *
 * @return void
 */
function sakolaan_companion_theme_notice() {
	if ( ! current_user_can( 'switch_themes' ) ) {
		return;
	}

	$status = sakolaan_get_companion_theme_status();

	if ( $status['compatible'] ) {
		return;
	}

	$class = 'notice sakolaan-notice notice-warning';
	$url   = admin_url( 'themes.php' );
	$label = __( 'Buka halaman Theme', 'sakolaan' );

	if ( 'theme_missing' === $status['code'] ) {
		$message = __( 'Plugin Sakolaan aktif, tetapi Sakolaan Theme belum terpasang. Unggah paket sakolaan-theme.zip versi 0.0.1 lalu aktifkan theme.', 'sakolaan' );
		if ( current_user_can( 'install_themes' ) ) {
			$url   = admin_url( 'theme-install.php?upload' );
			$label = __( 'Unggah Sakolaan Theme', 'sakolaan' );
		}
	} elseif ( 'theme_inactive' === $status['code'] ) {
		$message = __( 'Sakolaan Theme versi 0.0.1 sudah terpasang tetapi belum aktif.', 'sakolaan' );
	} else {
		$class = 'notice sakolaan-notice notice-error';
		$message = sprintf(
			/* translators: 1: plugin version, 2: installed theme version. */
			__( 'Versi Sakolaan tidak cocok. Plugin: %1$s; theme: %2$s. Instal pasangan plugin dan theme dengan versi yang sama.', 'sakolaan' ),
			$status['plugin_version'],
			$status['theme_version'] ?: __( 'tidak valid', 'sakolaan' )
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
add_action( 'admin_notices', 'sakolaan_companion_theme_notice' );
