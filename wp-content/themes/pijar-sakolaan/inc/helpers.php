<?php
/**
 * Helper functions.
 *
 * Kontrak dengan plugin Sakolaan: theme hanya membaca data lewat
 * pijar_sakolaan_get_setting(). Jika plugin tidak aktif atau nilai kosong,
 * default yang aman dipakai.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ambil satu nilai dari Pengaturan Sakolaan.
 *
 * Key memakai notasi titik, misalnya "contact.email" atau "footer.copyright".
 *
 * @param string $key     Key setting.
 * @param mixed  $default Nilai jika kosong. Jika null, default bawaan theme dipakai.
 * @return mixed
 */
function pijar_sakolaan_get_setting( $key, $default = null ) {
	$value = '';

	if ( pijar_sakolaan_is_core_active() ) {
		$value = sakolaan_get_setting( $key, '' );
	}

	if ( '' === $value || null === $value ) {
		return null === $default ? pijar_sakolaan_get_default_setting( $key ) : $default;
	}

	return $value;
}

/**
 * Default untuk setting yang kosong atau jika plugin tidak aktif.
 *
 * @param string $key Key setting.
 * @return string
 */
function pijar_sakolaan_get_default_setting( $key ) {
	switch ( $key ) {
		case 'identity.name':
			$value = get_bloginfo( 'name' );
			break;
		case 'identity.tagline':
		case 'footer.description':
		case 'homepage.hero_description':
			$value = get_bloginfo( 'description' );
			break;
		case 'homepage.hero_title':
			$value = get_bloginfo( 'name' );
			break;
		case 'ppdb.url':
		case 'homepage.hero_cta_url':
			$value = pijar_sakolaan_get_page_url( 'ppdb' );
			break;
		case 'ppdb.cta_label':
		case 'homepage.hero_cta_label':
			$value = __( 'Daftar PPDB', 'pijar-sakolaan' );
			break;
		case 'ppdb.description':
			$value = __( 'Informasi jadwal, persyaratan, dan alur pendaftaran peserta didik baru tersedia di halaman PPDB.', 'pijar-sakolaan' );
			break;
		case 'contact.whatsapp_url':
			$digits = preg_replace( '/\D/', '', (string) pijar_sakolaan_get_setting( 'contact.whatsapp', '' ) );
			$value  = $digits ? 'https://wa.me/' . $digits : '';
			break;
		case 'homepage.hero_cta2_label':
			$value = __( 'Kenali sekolah kami', 'pijar-sakolaan' );
			break;
		case 'homepage.hero_cta2_url':
		case 'profile.url':
			$value = pijar_sakolaan_get_page_url( 'profil' );
			break;
		case 'footer.copyright':
			$value = sprintf(
				/* translators: 1: year, 2: school name. */
				__( '© %1$s %2$s. Hak cipta dilindungi.', 'pijar-sakolaan' ),
				wp_date( 'Y' ),
				get_bloginfo( 'name' )
			);
			break;
		default:
			$value = '';
	}

	/**
	 * Filter default setting theme.
	 *
	 * @param string $value Nilai default.
	 * @param string $key   Key setting.
	 */
	return (string) apply_filters( 'pijar_sakolaan_default_setting', $value, $key );
}

/**
 * URL halaman terbit berdasarkan path (slug).
 *
 * @param string $path Path halaman, misalnya "ppdb".
 * @return string URL, atau string kosong jika halaman belum ada.
 */
function pijar_sakolaan_get_page_url( $path ) {
	$page = get_page_by_path( $path );

	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		return (string) get_permalink( $page );
	}

	return '';
}

/**
 * URL file di dalam theme.
 *
 * @param string $path Path relatif dari root theme.
 * @return string
 */
function pijar_sakolaan_asset_uri( $path ) {
	return get_theme_file_uri( ltrim( $path, '/' ) );
}

/**
 * Versi asset untuk cache busting (waktu modifikasi file, atau versi theme).
 *
 * @param string $path Path relatif dari root theme.
 * @return string
 */
function pijar_sakolaan_asset_version( $path ) {
	$file = get_theme_file_path( ltrim( $path, '/' ) );

	if ( is_readable( $file ) ) {
		return (string) filemtime( $file );
	}

	return PIJAR_SAKOLAAN_VERSION;
}
