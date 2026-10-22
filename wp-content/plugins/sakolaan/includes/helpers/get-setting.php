<?php
/**
 * API publik untuk membaca Pengaturan Sakolaan. Dipakai oleh Pijar Sakolaan.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seluruh pengaturan, digabung dengan default.
 *
 * @return array
 */
function sakolaan_get_settings( $force_refresh = false ) {
	static $cache = null;

	if ( $force_refresh ) {
		$cache = null;
	}

	if ( null !== $cache ) {
		return $cache;
	}

	$saved    = get_option( 'sakolaan_settings', array() );
	$saved    = is_array( $saved ) ? $saved : array();
	$settings = sakolaan_get_settings_defaults();

	foreach ( $settings as $group => $fields ) {
		if ( isset( $saved[ $group ] ) && is_array( $saved[ $group ] ) ) {
			$settings[ $group ] = array_merge( $fields, array_intersect_key( $saved[ $group ], $fields ) );
		}
	}

	if ( did_action( 'init' ) ) {
		$cache = $settings;
	}

	return $settings;
}

/**
 * Hapus cache statis sakolaan_get_settings().
 *
 * Dipanggil saat option diupdate agar halaman settings menggunakan data terkini.
 *
 * @return void
 */
function sakolaan_clear_settings_cache() {
	sakolaan_get_settings( true );
}

/**
 * Ambil satu pengaturan dengan notasi titik, misalnya "contact.email".
 *
 * Nilai kosong ('' , null, 0 untuk gambar) mengembalikan $default. Checkbox mengembalikan
 * bool apa adanya. Field gambar mengembalikan ID attachment.
 *
 * @param string $key     Key "group.field".
 * @param mixed  $default Nilai jika kosong atau tidak ada.
 * @return mixed
 */
function sakolaan_get_setting( $key, $default = '' ) {
	$parts = explode( '.', (string) $key, 2 );

	if ( 2 !== count( $parts ) ) {
		return $default;
	}

	$settings = sakolaan_get_settings();

	if ( ! isset( $settings[ $parts[0] ][ $parts[1] ] ) ) {
		return $default;
	}

	$value = $settings[ $parts[0] ][ $parts[1] ];

	if ( is_bool( $value ) ) {
		return $value;
	}

	return ( '' === $value || 0 === $value ) ? $default : $value;
}

/**
 * Apakah section beranda aktif.
 *
 * @param string $section Slug section, misalnya "news".
 * @return bool
 */
function sakolaan_is_section_enabled( $section ) {
	return (bool) sakolaan_get_setting( 'sections.show_' . $section, true );
}

/**
 * URL gambar dari field gambar.
 *
 * @param string $key  Key "group.field".
 * @param string $size Ukuran gambar.
 * @return string URL, atau string kosong.
 */
function sakolaan_get_setting_image_url( $key, $size = 'full' ) {
	$attachment_id = (int) sakolaan_get_setting( $key, 0 );

	if ( ! $attachment_id ) {
		return '';
	}

	$url = wp_get_attachment_image_url( $attachment_id, $size );

	return $url ? $url : '';
}
