<?php
/**
 * Post type berita.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daftarkan post type berita.
 *
 * @return void
 */
function sakolaan_register_news_post_type() {
	sakolaan_register_post_type(
		'berita',
		__( 'Berita', 'sakolaan' ),
		__( 'Berita', 'sakolaan' ),
		array(
			'show_in_menu'  => false,
			'menu_icon'     => 'dashicons-media-document',
			'menu_position' => 22,
			'capabilities'  => array( 'create_posts' => 'do_not_allow' ),
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
			'taxonomies'    => array( 'kategori_berita', 'tag_berita' ),
		)
	);
}
