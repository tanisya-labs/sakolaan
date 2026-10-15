<?php
/**
 * Satu registri untuk konten yang dikelola Sakolaan.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string,array<string,mixed>>
 */
function sakolaan_content_registry() {
	$common = array( 'title', 'editor', 'thumbnail', 'custom-fields' );
	return array(
		'post'               => array( 'label' => __( 'Berita', 'sakolaan' ), 'group' => 'publication', 'icon' => 'dashicons-media-document', 'orderable' => false, 'taxonomies' => array( 'category', 'post_tag' ), 'required' => array( 'title' ), 'columns' => array() ),
		'pengumuman'        => array( 'label' => __( 'Pengumuman', 'sakolaan' ), 'group' => 'publication', 'icon' => 'dashicons-megaphone', 'orderable' => false, 'taxonomies' => array(), 'required' => array( 'title', 'tanggal' ), 'supports' => array( 'title', 'editor', 'revisions', 'custom-fields' ), 'columns' => array( 'tanggal', 'status' ) ),
		'agenda'            => array( 'label' => __( 'Agenda', 'sakolaan' ), 'group' => 'publication', 'icon' => 'dashicons-calendar-alt', 'orderable' => false, 'taxonomies' => array(), 'required' => array( 'title', 'tanggal_mulai' ), 'supports' => $common, 'columns' => array( 'tanggal_mulai', 'lokasi', 'event_status' ) ),
		'guru'              => array( 'label' => __( 'Guru & Tendik', 'sakolaan' ), 'singular' => __( 'Guru', 'sakolaan' ), 'group' => 'profile', 'icon' => 'dashicons-groups', 'orderable' => true, 'taxonomies' => array( 'kelompok_gtk' ), 'required' => array( 'title', 'thumbnail', 'jabatan' ), 'supports' => array_merge( $common, array( 'page-attributes' ) ), 'columns' => array( 'photo', 'jabatan', 'mapel', 'order' ) ),
		'prestasi'          => array( 'label' => __( 'Prestasi', 'sakolaan' ), 'group' => 'profile', 'icon' => 'dashicons-awards', 'orderable' => true, 'taxonomies' => array( 'kategori_prestasi', 'tingkat_prestasi' ), 'required' => array( 'title', 'tahun' ), 'supports' => array_merge( $common, array( 'page-attributes' ) ), 'columns' => array( 'photo', 'peraih', 'tahun', 'order' ) ),
		'ekstrakurikuler'   => array( 'label' => __( 'Ekstrakurikuler', 'sakolaan' ), 'group' => 'profile', 'icon' => 'dashicons-tickets-alt', 'orderable' => true, 'taxonomies' => array(), 'required' => array( 'title' ), 'supports' => array_merge( $common, array( 'page-attributes' ) ), 'columns' => array( 'photo', 'pembina', 'order' ) ),
		'fasilitas'         => array( 'label' => __( 'Fasilitas', 'sakolaan' ), 'group' => 'profile', 'icon' => 'dashicons-building', 'orderable' => true, 'taxonomies' => array(), 'required' => array( 'title', 'thumbnail' ), 'supports' => array_merge( $common, array( 'excerpt', 'page-attributes', 'revisions' ) ), 'columns' => array( 'photo', 'lokasi', 'order' ) ),
		'program_unggulan'  => array( 'label' => __( 'Program Unggulan', 'sakolaan' ), 'group' => 'profile', 'icon' => 'dashicons-star-filled', 'orderable' => true, 'taxonomies' => array(), 'required' => array( 'title', 'thumbnail' ), 'supports' => array_merge( $common, array( 'excerpt', 'page-attributes', 'revisions' ) ), 'rewrite' => 'program-unggulan', 'columns' => array( 'photo', 'sasaran', 'order' ) ),
		'galeri'            => array( 'label' => __( 'Galeri', 'sakolaan' ), 'group' => 'profile', 'icon' => 'dashicons-format-gallery', 'orderable' => true, 'taxonomies' => array( 'album_galeri' ), 'required' => array( 'title', 'thumbnail' ), 'supports' => array_merge( $common, array( 'page-attributes' ) ), 'columns' => array( 'photo', 'gallery_count', 'tanggal', 'order' ) ),
		'testimoni'         => array( 'label' => __( 'Testimoni Alumni', 'sakolaan' ), 'group' => 'profile', 'icon' => 'dashicons-format-quote', 'orderable' => true, 'taxonomies' => array(), 'required' => array( 'title', 'editor' ), 'supports' => array_merge( $common, array( 'excerpt', 'page-attributes', 'revisions' ) ), 'columns' => array( 'photo', 'aktivitas', 'order' ) ),
		'hero_slide'        => array( 'label' => __( 'Slide Hero', 'sakolaan' ), 'group' => 'home', 'icon' => 'dashicons-images-alt2', 'orderable' => true, 'taxonomies' => array(), 'required' => array( 'title', 'thumbnail' ), 'supports' => array( 'title', 'excerpt', 'thumbnail', 'page-attributes', 'revisions' ), 'private' => true, 'columns' => array( 'photo', 'order' ) ),
	);
}

/** @return array<string,array<string,mixed>> */
function sakolaan_taxonomy_registry() {
	return array(
		'kelompok_gtk'      => array( 'label' => __( 'Kelompok GTK', 'sakolaan' ), 'singular' => __( 'Kelompok', 'sakolaan' ), 'post_types' => array( 'guru' ), 'hierarchical' => true, 'rewrite' => 'guru/kelompok' ),
		'kategori_prestasi' => array( 'label' => __( 'Kategori Prestasi', 'sakolaan' ), 'post_types' => array( 'prestasi' ), 'hierarchical' => true, 'rewrite' => 'prestasi/kategori' ),
		'tingkat_prestasi'  => array( 'label' => __( 'Tingkat Prestasi', 'sakolaan' ), 'post_types' => array( 'prestasi' ), 'hierarchical' => true, 'rewrite' => 'prestasi/tingkat' ),
		'album_galeri'      => array( 'label' => __( 'Album Galeri', 'sakolaan' ), 'singular' => __( 'Album', 'sakolaan' ), 'post_types' => array( 'galeri' ), 'hierarchical' => true, 'rewrite' => 'galeri/album' ),
	);
}

/** @return string[] */
function sakolaan_get_orderable_post_types() {
	return array_keys( array_filter( sakolaan_content_registry(), static function ( $item ) { return ! empty( $item['orderable'] ); } ) );
}

/** @return string[] */
function sakolaan_public_post_types() {
	return array_keys( array_filter( sakolaan_content_registry(), static function ( $item, $slug ) { return 'post' !== $slug && empty( $item['private'] ); }, ARRAY_FILTER_USE_BOTH ) );
}

/** Register native school content from the same definitions used by the admin. */
function sakolaan_register_registry_content() {
	foreach ( sakolaan_taxonomy_registry() as $slug => $item ) {
		sakolaan_register_taxonomy( $slug, $item['post_types'], $item['singular'] ?? $item['label'], $item['label'], array( 'hierarchical' => $item['hierarchical'], 'show_in_menu' => false, 'rewrite' => array( 'slug' => $item['rewrite'], 'with_front' => false ) ) );
	}
	foreach ( sakolaan_content_registry() as $slug => $item ) {
		if ( 'post' === $slug ) {
			continue;
		}
		$args = array( 'menu_icon' => $item['icon'], 'supports' => $item['supports'], 'taxonomies' => $item['taxonomies'], 'show_in_menu' => false );
		if ( ! empty( $item['rewrite'] ) ) {
			$args['rewrite'] = array( 'slug' => $item['rewrite'], 'with_front' => false );
		}
		if ( ! empty( $item['private'] ) ) {
			$args = array_merge( $args, array( 'public' => false, 'publicly_queryable' => false, 'show_ui' => true, 'has_archive' => false, 'rewrite' => false ) );
		}
		sakolaan_register_post_type( $slug, $item['singular'] ?? $item['label'], $item['label'], $args );
	}
}
