<?php
/**
 * Sanitizer untuk meta dan setting.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tanggal Y-m-d yang valid, atau string kosong.
 *
 * @param mixed $value Nilai input.
 * @return string
 */
function sakolaan_sanitize_date( $value ) {
	$value = is_string( $value ) ? trim( $value ) : '';
	$date  = DateTime::createFromFormat( 'Y-m-d', $value );

	return ( $date && $date->format( 'Y-m-d' ) === $value ) ? $value : '';
}

/**
 * Waktu H:i yang valid, atau string kosong.
 *
 * @param mixed $value Nilai input.
 * @return string
 */
function sakolaan_sanitize_time( $value ) {
	$value = is_string( $value ) ? trim( $value ) : '';

	return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : '';
}

/**
 * Tahun 4 digit, atau string kosong.
 *
 * @param mixed $value Nilai input.
 * @return string
 */
function sakolaan_sanitize_year( $value ) {
	$value = is_scalar( $value ) ? trim( (string) $value ) : '';

	return preg_match( '/^\d{4}$/', $value ) ? $value : '';
}

/**
 * Batasi gaya CTA slide pada preset yang didukung theme.
 *
 * @param mixed $value Nilai input.
 * @return string
 */
function sakolaan_sanitize_hero_cta_style( $value ) {
	$value = is_scalar( $value ) ? sanitize_key( (string) $value ) : '';

	return in_array( $value, array( 'accent', 'light', 'outline' ), true ) ? $value : '';
}

/**
 * Daftar ID attachment (bilangan bulat positif, tanpa duplikat).
 *
 * @param mixed $value Nilai input.
 * @return int[]
 */
function sakolaan_sanitize_ids( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
}

/**
 * Status pengumuman: aktif atau arsip.
 *
 * @param mixed $value Nilai input.
 * @return string
 */
function sakolaan_sanitize_announcement_status( $value ) {
	return in_array( $value, array( 'aktif', 'arsip' ), true ) ? $value : 'aktif';
}

/**
 * Status agenda: otomatis, ditunda, atau dibatalkan.
 *
 * @param mixed $value Nilai input.
 * @return string
 */
function sakolaan_sanitize_event_status( $value ) {
	return in_array( $value, array( 'otomatis', 'ditunda', 'dibatalkan' ), true ) ? $value : 'otomatis';
}

/**
 * Nomor WhatsApp: hanya digit, awalan 0 diganti 62.
 *
 * @param mixed $value Nilai input.
 * @return string
 */
function sakolaan_sanitize_phone_digits( $value ) {
	$digits = preg_replace( '/\D/', '', is_scalar( $value ) ? (string) $value : '' );

	if ( 0 === strpos( $digits, '0' ) ) {
		$digits = '62' . substr( $digits, 1 );
	}

	return $digits;
}

/**
 * Alamat embed Google Maps. Menerima URL atau seluruh kode iframe; hanya src https dari
 * domain Google Maps yang disimpan.
 *
 * @param mixed $value Nilai input.
 * @return string
 */
function sakolaan_sanitize_maps_url( $value ) {
	$value = is_string( $value ) ? trim( $value ) : '';

	// Jika input adalah iframe, ekstrak src.
	if ( preg_match( '/src\s*=\s*["\']([^"\']+)["\']/i', $value, $matches ) ) {
		$value = html_entity_decode( $matches[1], ENT_QUOTES );
	}

	$url  = esc_url_raw( $value, array( 'https' ) );
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	$path = (string) wp_parse_url( $url, PHP_URL_PATH );

	// Validasi host: google.com, www.google.com, maps.google.com.
	if ( ! in_array( $host, array( 'www.google.com', 'maps.google.com', 'google.com' ), true ) ) {
		return '';
	}

	// Validasi path: harus dimulai dengan /maps atau /embed (untuk format embed v1/v2).
	if ( 0 !== strpos( $path, '/maps' ) && 0 !== strpos( $path, '/embed' ) ) {
		return '';
	}

	return $url;
}
