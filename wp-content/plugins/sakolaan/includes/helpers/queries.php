<?php
/**
 * Pengaturan query arsip.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Atur jumlah per halaman dan urutan arsip tipe konten sekolah.
 *
 * - Semua arsip: 12 item per halaman (grid 2, 3, atau 4 kolom terbagi rata).
 * - Konten katalog: urutan tampilan (menu_order), lalu judul.
 * - Agenda: tanggal mulai terbaru lebih dulu; agenda tanpa tanggal tetap tampil.
 *
 * @param WP_Query $query Query utama.
 * @return void
 */
function sakolaan_adjust_archive_queries( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$post_types = array_merge( sakolaan_public_post_types(), array( 'berita' ) );
	$taxonomies = array_keys( sakolaan_taxonomy_registry() );
	if ( ! get_option( 'sakolaan_legacy_news_purged', false ) ) {
		$taxonomies = array_merge( $taxonomies, array( 'kategori_berita', 'tag_berita' ) );
	}

	if ( ! $query->is_post_type_archive( $post_types ) && ! $query->is_tax( $taxonomies ) ) {
		return;
	}

	// Arsip /berita memuat Post bawaan sekaligus CPT lama tanpa memindahkan data.
	if ( $query->is_post_type_archive( 'berita' ) ) {
		$query->set( 'post_type', array( 'post', 'berita' ) );
	}

	// Dataset demo memakai 9 item agar sepuluh berita langsung menguji pagination.
	$per_page = get_option( 'sakolaan_demo_manifest', false ) ? 9 : 12;
	$query->set( 'posts_per_page', $per_page );

	$orderable_public = array_values( array_diff( sakolaan_get_orderable_post_types(), array( 'hero_slide' ) ) );

	if ( $query->is_post_type_archive( $orderable_public ) || $query->is_tax( array( 'kelompok_gtk', 'kategori_prestasi', 'tingkat_prestasi', 'album_galeri' ) ) ) {
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			)
		);
	}

	if ( $query->is_post_type_archive( 'agenda' ) ) {
		$query->set(
			'meta_query',
			array(
				'relation'       => 'OR',
				'sakolaan_start'   => array(
					'key'     => '_sakolaan_tanggal_mulai',
					'compare' => 'EXISTS',
				),
				'sakolaan_nostart' => array(
					'key'     => '_sakolaan_tanggal_mulai',
					'compare' => 'NOT EXISTS',
				),
			)
		);
		$query->set(
			'orderby',
			array(
				'sakolaan_start' => 'DESC',
				'date'         => 'DESC',
			)
		);
	}
}
add_action( 'pre_get_posts', 'sakolaan_adjust_archive_queries' );
