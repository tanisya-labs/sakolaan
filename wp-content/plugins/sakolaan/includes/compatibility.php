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
		return new WP_Error( 'pijar_sakolaan_version_invalid', __( 'Versi Pijar Sakolaan tidak valid.', 'sakolaan' ) );
	}

	if ( SAKOLAAN_VERSION !== $theme_version ) {
		return new WP_Error( 'sakolaan_version_mismatch', __( 'Versi plugin Sakolaan dan tema default Pijar Sakolaan harus sama.', 'sakolaan' ) );
	}

	return true;
}

/**
 * Theme APIs required by plugin integrations and dynamic block renderers.
 *
 * @return string[]
 */
function sakolaan_companion_theme_required_apis() {
	return array(
		'pijar_sakolaan_dependency_status',
		'pijar_sakolaan_is_compatible',
		'pijar_sakolaan_get_setting',
		'pijar_sakolaan_get_page_tree',
		'pijar_sakolaan_get_archive_configs',
		'pijar_sakolaan_get_archive_sort_options',
		'pijar_sakolaan_get_archive_status_options',
		'pijar_sakolaan_is_manually_ordered_post_type',
		'pijar_sakolaan_get_breadcrumb_items',
		'pijar_sakolaan_get_breadcrumb_schema',
		'pijar_sakolaan_get_meta_value',
		'pijar_sakolaan_get_raw_meta',
		'pijar_sakolaan_render_homepage_sections',
		'pijar_sakolaan_enqueue_gallery_script',
		'pijar_sakolaan_get_meta_labels',
		'pijar_sakolaan_render_meta_value',
		'pijar_sakolaan_get_archive_url',
	);
}

/**
 * Inspect installation, activation, and version state of the companion theme.
 *
 * @return array{code:string,compatible:bool,installed:bool,active:bool,runtime_ready:bool,plugin_version:string,theme_version:string}
 */
function sakolaan_get_companion_theme_status() {
	$theme         = wp_get_theme( SAKOLAAN_DEFAULT_THEME_SLUG );
	$installed     = $theme->exists() && ! is_wp_error( $theme->errors() );
	$active        = SAKOLAAN_DEFAULT_THEME_SLUG === get_template();
	$theme_version = $installed ? trim( (string) $theme->get( 'Version' ) ) : '';
	$runtime_ready = false;
	$code          = 'compatible';

	if ( ! $installed ) {
		$code = 'theme_missing';
	} else {
		$validation = sakolaan_validate_companion_version( $theme_version );

		if ( is_wp_error( $validation ) ) {
			$code = $validation->get_error_code();
		} elseif ( ! $active ) {
			$code = 'theme_inactive';
		} else {
			$runtime_ready = defined( 'PIJAR_SAKOLAAN_VERSION' )
				&& defined( 'PIJAR_SAKOLAAN_SLUG' )
				&& PIJAR_SAKOLAAN_VERSION === $theme_version
				&& SAKOLAAN_DEFAULT_THEME_SLUG === PIJAR_SAKOLAAN_SLUG;

			foreach ( sakolaan_companion_theme_required_apis() as $function ) {
				$runtime_ready = $runtime_ready && function_exists( $function );
			}

			if ( $runtime_ready ) {
				$dependency_status = pijar_sakolaan_dependency_status();
				$runtime_ready = ! empty( $dependency_status['compatible'] ) && ! empty( $dependency_status['plugin_ready'] );
			}

			if ( ! $runtime_ready ) {
				$code = 'theme_incomplete';
			}
		}
	}

	return array(
		'code'           => $code,
		'compatible'     => 'compatible' === $code,
		'installed'      => $installed,
		'active'         => $active,
		'runtime_ready'  => $runtime_ready,
		'plugin_version' => SAKOLAAN_VERSION,
		'theme_version'  => $theme_version,
	);
}

/**
 * Whether the default theme is active and its runtime contract is ready.
 *
 * @return bool
 */
function sakolaan_companion_theme_is_ready() {
	$status = sakolaan_get_companion_theme_status();
	return ! empty( $status['compatible'] ) && ! empty( $status['runtime_ready'] );
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
	$label = __( 'Buka halaman Tema', 'sakolaan' );

	if ( 'theme_missing' === $status['code'] ) {
		$message = __( 'Plugin Sakolaan aktif, tetapi tema default Pijar Sakolaan belum terpasang. Unggah paket pijar-sakolaan.zip versi 0.0.1 lalu aktifkan tema.', 'sakolaan' );
		if ( current_user_can( 'install_themes' ) ) {
			$url   = admin_url( 'theme-install.php?upload' );
			$label = __( 'Unggah Pijar Sakolaan', 'sakolaan' );
		}
	} elseif ( 'theme_inactive' === $status['code'] ) {
		$message = __( 'Tema default Pijar Sakolaan versi 0.0.1 sudah terpasang tetapi belum aktif.', 'sakolaan' );
	} elseif ( 'theme_incomplete' === $status['code'] ) {
		$class = 'notice sakolaan-notice notice-error';
		$message = __( 'Tema default Pijar Sakolaan aktif tetapi API atau bootstrap-nya tidak lengkap. Instal ulang paket pijar-sakolaan.zip versi 0.0.1.', 'sakolaan' );
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
