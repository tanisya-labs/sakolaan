<?php
/**
 * Builder label dan registrasi post type / taxonomy.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Label post type dalam Bahasa Indonesia.
 *
 * @param string $singular Nama tunggal (sudah diterjemahkan).
 * @param string $plural   Nama jamak (sudah diterjemahkan).
 * @return array
 */
function sakolaan_get_post_type_labels( $singular, $plural ) {
	return array(
		'name'                  => $plural,
		'singular_name'         => $singular,
		'menu_name'             => $plural,
		'add_new'               => __( 'Tambah baru', 'sakolaan' ),
		/* translators: %s: nama konten, misalnya Guru. */
		'add_new_item'          => sprintf( __( 'Tambah %s baru', 'sakolaan' ), $singular ),
		/* translators: %s: nama konten. */
		'edit_item'             => sprintf( __( 'Edit %s', 'sakolaan' ), $singular ),
		/* translators: %s: nama konten. */
		'new_item'              => sprintf( __( '%s baru', 'sakolaan' ), $singular ),
		/* translators: %s: nama konten. */
		'view_item'             => sprintf( __( 'Lihat %s', 'sakolaan' ), $singular ),
		/* translators: %s: nama konten jamak. */
		'search_items'          => sprintf( __( 'Cari %s', 'sakolaan' ), $plural ),
		/* translators: %s: nama konten jamak. */
		'not_found'             => sprintf( __( '%s belum ada.', 'sakolaan' ), $plural ),
		/* translators: %s: nama konten jamak. */
		'not_found_in_trash'    => sprintf( __( 'Tidak ada %s di tong sampah.', 'sakolaan' ), $plural ),
		/* translators: %s: nama konten jamak. */
		'all_items'             => sprintf( __( 'Semua %s', 'sakolaan' ), $plural ),
		'featured_image'        => __( 'Foto utama', 'sakolaan' ),
		'set_featured_image'    => __( 'Pilih foto utama', 'sakolaan' ),
		'remove_featured_image' => __( 'Hapus foto utama', 'sakolaan' ),
		'use_featured_image'    => __( 'Gunakan sebagai foto utama', 'sakolaan' ),
	);
}

/**
 * Daftarkan post type dengan default bersama.
 *
 * @param string $post_type Slug post type (juga dasar URL).
 * @param string $singular  Nama tunggal.
 * @param string $plural    Nama jamak.
 * @param array  $args      Argumen yang menimpa default.
 * @return void
 */
function sakolaan_register_post_type( $post_type, $singular, $plural, $args = array() ) {
	/**
	 * Filter slug URL post type.
	 *
	 * @param string $slug      Slug bawaan.
	 * @param string $post_type Post type.
	 */
	$slug = apply_filters( 'sakolaan_post_type_slug', $post_type, $post_type );

	$defaults = array(
		'labels'       => sakolaan_get_post_type_labels( $singular, $plural ),
		'public'       => true,
		'has_archive'  => true,
		'show_in_menu' => 'sakolaan',
		'show_in_rest' => true,
		'rewrite'      => array(
			'slug'       => $slug,
			'with_front' => false,
		),
		'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
	);

	register_post_type( $post_type, array_merge( $defaults, $args ) );
}

/**
 * Daftarkan taxonomy dengan default bersama.
 *
 * @param string   $taxonomy     Slug taxonomy.
 * @param string[] $object_types Post type yang memakai taxonomy.
 * @param string   $singular     Nama tunggal.
 * @param string   $plural       Nama jamak.
 * @param array    $args         Argumen yang menimpa default.
 * @return void
 */
function sakolaan_register_taxonomy( $taxonomy, $object_types, $singular, $plural, $args = array() ) {
	$labels = array(
		'name'          => $plural,
		'singular_name' => $singular,
		'menu_name'     => $plural,
		/* translators: %s: nama taxonomy jamak. */
		'search_items'  => sprintf( __( 'Cari %s', 'sakolaan' ), $plural ),
		/* translators: %s: nama taxonomy jamak. */
		'all_items'     => sprintf( __( 'Semua %s', 'sakolaan' ), $plural ),
		/* translators: %s: nama taxonomy tunggal. */
		'edit_item'     => sprintf( __( 'Edit %s', 'sakolaan' ), $singular ),
		/* translators: %s: nama taxonomy tunggal. */
		'add_new_item'  => sprintf( __( 'Tambah %s baru', 'sakolaan' ), $singular ),
		/* translators: %s: nama taxonomy jamak. */
		'not_found'     => sprintf( __( '%s belum ada.', 'sakolaan' ), $plural ),
	);

	$defaults = array(
		'labels'            => $labels,
		'public'            => true,
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
	);

	register_taxonomy( $taxonomy, $object_types, array_merge( $defaults, $args ) );
}
