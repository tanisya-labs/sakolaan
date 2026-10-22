<?php
/**
 * Data breadcrumb dan schema BreadcrumbList.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bangun daftar item breadcrumb untuk request saat ini.
 *
 * @return array[] Daftar item: array( 'label' => string, 'url' => string|null ).
 */
function pijar_sakolaan_get_breadcrumb_items() {
	if ( is_front_page() ) {
		return array();
	}

	$items = array(
		array(
			'label' => __( 'Beranda', 'pijar-sakolaan' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( is_home() ) {
		$posts_page = (int) get_option( 'page_for_posts' );
		$items[]    = array(
			'label' => $posts_page ? get_the_title( $posts_page ) : __( 'Tulisan', 'pijar-sakolaan' ),
			'url'   => null,
		);
	} elseif ( is_search() ) {
		$items[] = array(
			/* translators: %s: kata kunci pencarian. */
			'label' => sprintf( __( 'Hasil pencarian: %s', 'pijar-sakolaan' ), get_search_query() ),
			'url'   => null,
		);
	} elseif ( is_404() ) {
		$items[] = array(
			'label' => __( 'Halaman tidak ditemukan', 'pijar-sakolaan' ),
			'url'   => null,
		);
	} elseif ( is_singular() ) {
		$post      = get_queried_object();
		$post_type = get_post_type_object( $post->post_type );

		if ( is_post_type_hierarchical( $post->post_type ) ) {
			foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor_id ) {
				$items[] = array(
					'label' => get_the_title( $ancestor_id ),
					'url'   => get_permalink( $ancestor_id ),
				);
			}
		} elseif ( $post_type && $post_type->has_archive ) {
			$archive_url = get_post_type_archive_link( $post->post_type );

			if ( $archive_url ) {
				$items[] = array(
					'label' => $post_type->labels->name,
					'url'   => $archive_url,
				);
			}
		}

		$items[] = array(
			'label' => get_the_title( $post ),
			'url'   => null,
		);
	} elseif ( is_post_type_archive() ) {
		$items[] = array(
			'label' => post_type_archive_title( '', false ),
			'url'   => null,
		);
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term     = get_queried_object();
		$taxonomy = get_taxonomy( $term->taxonomy );

		if ( $taxonomy && ! empty( $taxonomy->object_type ) ) {
			$post_type = get_post_type_object( $taxonomy->object_type[0] );

			if ( $post_type && $post_type->has_archive ) {
				$archive_url = get_post_type_archive_link( $post_type->name );

				if ( $archive_url ) {
					$items[] = array(
						'label' => $post_type->labels->name,
						'url'   => $archive_url,
					);
				}
			}
		}

		foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, $term->taxonomy );
			$link     = $ancestor && ! is_wp_error( $ancestor ) ? get_term_link( $ancestor ) : null;

			if ( $link && ! is_wp_error( $link ) ) {
				$items[] = array(
					'label' => $ancestor->name,
					'url'   => $link,
				);
			}
		}

		$items[] = array(
			'label' => $term->name,
			'url'   => null,
		);
	} else {
		$items[] = array(
			'label' => wp_strip_all_tags( get_the_archive_title() ),
			'url'   => null,
		);
	}

	/**
	 * Filter item breadcrumb.
	 *
	 * @param array[] $items Daftar item breadcrumb.
	 */
	return apply_filters( 'pijar_sakolaan_breadcrumb_items', $items );
}

/**
 * Apakah plugin SEO yang sudah menghasilkan schema breadcrumb aktif.
 *
 * Jika ya, theme tidak mengeluarkan JSON-LD sendiri agar tidak bentrok atau duplikat.
 *
 * @return bool
 */
function pijar_sakolaan_has_seo_plugin() {
	$active = defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| defined( 'AIOSEO_VERSION' );

	/**
	 * Filter deteksi plugin SEO.
	 *
	 * @param bool $active Apakah plugin SEO aktif.
	 */
	return (bool) apply_filters( 'pijar_sakolaan_has_seo_plugin', $active );
}

/**
 * Hasilkan tag JSON-LD BreadcrumbList.
 *
 * @param array[] $items Item breadcrumb.
 * @return string HTML script tag, atau string kosong.
 */
function pijar_sakolaan_get_breadcrumb_schema( $items ) {
	if ( count( $items ) < 2 || pijar_sakolaan_has_seo_plugin() ) {
		return '';
	}

	$list = array();

	foreach ( array_values( $items ) as $index => $item ) {
		$entry = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'name'     => wp_strip_all_tags( $item['label'] ),
		);

		if ( ! empty( $item['url'] ) ) {
			$entry['item'] = $item['url'];
		}

		$list[] = $entry;
	}

	$data = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $list,
	);

	return '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>';
}
