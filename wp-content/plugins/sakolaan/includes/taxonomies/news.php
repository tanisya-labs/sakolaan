<?php
/**
 * Taxonomy berita: kategori dan tag.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daftarkan kategori dan tag berita.
 *
 * @return void
 */
function sakolaan_register_news_taxonomies() {
	sakolaan_register_taxonomy(
		'kategori_berita',
		array( 'berita' ),
		__( 'Kategori Berita', 'sakolaan' ),
		__( 'Kategori Berita', 'sakolaan' ),
		array( 'show_in_menu' => false, 'rewrite' => array( 'slug' => 'berita/kategori', 'with_front' => false ) )
	);

	sakolaan_register_taxonomy(
		'tag_berita',
		array( 'berita' ),
		__( 'Tag Berita', 'sakolaan' ),
		__( 'Tag Berita', 'sakolaan' ),
		array(
			'hierarchical' => false,
			'show_in_menu' => false,
			'rewrite'      => array( 'slug' => 'berita/tag', 'with_front' => false ),
		)
	);
}
