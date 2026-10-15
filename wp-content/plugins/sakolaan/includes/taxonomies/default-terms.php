<?php
/**
 * Term bawaan, dibuat saat aktivasi hanya jika belum ada.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Buat term bawaan.
 *
 * @param string $name     Nama term.
 * @param string $taxonomy Taxonomy tujuan.
 * @return int|WP_Error ID term, atau error jika insert gagal.
 */
function sakolaan_insert_term_once( $name, $taxonomy ) {
	$slug     = sanitize_title( $name );
	$existing = term_exists( $slug, $taxonomy );

	if ( ! $existing ) {
		$existing = term_exists( $name, $taxonomy );
	}

	if ( $existing ) {
		return is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;
	}

	$result = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );

	if ( is_wp_error( $result ) && 'term_exists' === $result->get_error_code() ) {
		$term_id = absint( $result->get_error_data( 'term_exists' ) );

		if ( $term_id ) {
			return $term_id;
		}
	}

	return is_wp_error( $result ) ? $result : (int) $result['term_id'];
}

/**
 * Buat term bawaan.
 *
 * @return true|WP_Error
 */
function sakolaan_create_default_terms() {
	$terms = array(
		'kelompok_gtk'      => array(
			__( 'Guru', 'sakolaan' ),
			__( 'Tenaga Kependidikan', 'sakolaan' ),
		),
		'tingkat_prestasi'  => array(
			__( 'Sekolah', 'sakolaan' ),
			__( 'Kecamatan', 'sakolaan' ),
			__( 'Kabupaten/Kota', 'sakolaan' ),
			__( 'Provinsi', 'sakolaan' ),
			__( 'Nasional', 'sakolaan' ),
			__( 'Internasional', 'sakolaan' ),
		),
		'kategori_prestasi' => array(
			__( 'Akademik', 'sakolaan' ),
			__( 'Olahraga', 'sakolaan' ),
			__( 'Seni', 'sakolaan' ),
		),
	);
	if ( ! get_option( 'sakolaan_legacy_news_purged', false ) ) {
		$terms['kategori_berita'] = array( __( 'Umum', 'sakolaan' ) );
	}

	foreach ( $terms as $taxonomy => $names ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return new WP_Error( 'sakolaan_taxonomy_unavailable', sprintf( /* translators: %s: taxonomy slug. */ __( 'Taxonomy belum tersedia: %s', 'sakolaan' ), $taxonomy ) );
		}

		foreach ( $names as $name ) {
			$result = sakolaan_insert_term_once( $name, $taxonomy );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
	}

	return true;
}
