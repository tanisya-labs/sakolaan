<?php
/**
 * Variasi Query Loop untuk section beranda.
 *
 * Query Loop core tidak dapat memfilter meta. Block Query diberi class, dan filter
 * query_loop_block_query_vars menyesuaikan query-nya di server:
 *
 * - sakolaan-query--upcoming: agenda yang belum selesai, tanggal mulai terdekat dulu.
 * - sakolaan-query--active: pengumuman yang tidak diarsipkan.
 *
 * Jika Sakolaan tidak aktif, post type tidak ada dan query tidak menghasilkan apa-apa.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Opsi pengurutan publik untuk halaman arsip.
 *
 * @return array<string,string>
 */
function pijar_sakolaan_get_archive_sort_options( $post_type = '' ) {
	$options = array(
		'date_desc'  => __( 'Terbaru dibuat', 'pijar-sakolaan' ),
		'date_asc'   => __( 'Terlama dibuat', 'pijar-sakolaan' ),
		'title_asc'  => __( 'Judul A–Z', 'pijar-sakolaan' ),
		'title_desc' => __( 'Judul Z–A', 'pijar-sakolaan' ),
	);

	if ( pijar_sakolaan_is_manually_ordered_post_type( $post_type ) ) {
		$options = array( 'manual' => __( 'Urutan pilihan', 'pijar-sakolaan' ) ) + $options;
	}

	return $options;
}

/**
 * Apakah post type memakai urutan manual.
 *
 * @param string $post_type Post type.
 * @return bool
 */
function pijar_sakolaan_is_manually_ordered_post_type( $post_type ) {
	return in_array( $post_type, array( 'guru', 'ekstrakurikuler', 'prestasi', 'galeri', 'fasilitas', 'program_unggulan', 'testimoni' ), true );
}

/**
 * Opsi status yang tersedia untuk post type tertentu.
 *
 * @param string $post_type Post type.
 * @return array<string,string>
 */
function pijar_sakolaan_get_archive_status_options( $post_type ) {
	if ( 'pengumuman' === $post_type ) {
		return array(
			'aktif' => __( 'Aktif', 'pijar-sakolaan' ),
			'arsip' => __( 'Arsip', 'pijar-sakolaan' ),
		);
	}

	if ( 'agenda' === $post_type ) {
		return array(
			'terjadwal'   => __( 'Terjadwal', 'pijar-sakolaan' ),
			'berlangsung' => __( 'Berlangsung', 'pijar-sakolaan' ),
			'selesai'     => __( 'Selesai', 'pijar-sakolaan' ),
			'ditunda'     => __( 'Ditunda', 'pijar-sakolaan' ),
			'dibatalkan'  => __( 'Dibatalkan', 'pijar-sakolaan' ),
		);
	}

	return array();
}

/**
 * Daftarkan query var arsip agar nilai GET tersedia secara konsisten.
 *
 * @param string[] $vars Query vars publik.
 * @return string[]
 */
function pijar_sakolaan_archive_query_vars( $vars ) {
	$vars[] = 'sakolaan_sort';
	$vars[] = 'sakolaan_status';

	return $vars;
}
add_filter( 'query_vars', 'pijar_sakolaan_archive_query_vars' );

/**
 * Tentukan post type Sakolaan dari query arsip atau taxonomy.
 *
 * @param WP_Query $query Query utama.
 * @return string
 */
function pijar_sakolaan_get_archive_query_post_type( $query ) {
	if ( $query->is_post_type_archive( 'berita' ) || $query->is_category() || $query->is_tag() ) {
		return 'berita';
	}

	$post_type = $query->get( 'post_type' );

	if ( is_string( $post_type ) && '' !== $post_type ) {
		return sanitize_key( $post_type );
	}

	if ( is_array( $post_type ) && 1 === count( $post_type ) ) {
		return sanitize_key( reset( $post_type ) );
	}

	foreach ( array_keys( pijar_sakolaan_get_archive_configs() ) as $candidate ) {
		$taxonomies = get_object_taxonomies( $candidate );

		if ( ! empty( $taxonomies ) && $query->is_tax( $taxonomies ) ) {
			return $candidate;
		}
	}

	$taxonomy_name = (string) $query->get( 'taxonomy' );
	$taxonomy      = $taxonomy_name ? get_taxonomy( $taxonomy_name ) : null;

	return $taxonomy && ! empty( $taxonomy->object_type ) ? sanitize_key( $taxonomy->object_type[0] ) : '';
}

/**
 * Syarat meta untuk item berstatus otomatis, bukan ditunda/dibatalkan.
 *
 * @return array
 */
function pijar_sakolaan_get_automatic_event_meta_query() {
	return array(
		'relation' => 'OR',
		array(
			'key'     => '_sakolaan_status',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_sakolaan_status',
			'value'   => array( '', 'otomatis' ),
			'compare' => 'IN',
		),
	);
}

/**
 * Bangun meta query untuk filter status frontend.
 *
 * @param string $post_type Post type.
 * @param string $status    Status terpilih.
 * @return array
 */
function pijar_sakolaan_get_archive_status_meta_query( $post_type, $status ) {
	if ( 'pengumuman' === $post_type ) {
		if ( 'arsip' === $status ) {
			return array(
				'key'     => '_sakolaan_status',
				'value'   => 'arsip',
				'compare' => '=',
			);
		}

		if ( 'aktif' === $status ) {
			return array(
				'relation' => 'OR',
				array(
					'key'     => '_sakolaan_status',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_sakolaan_status',
					'value'   => array( '', 'aktif' ),
					'compare' => 'IN',
				),
			);
		}
	}

	if ( 'agenda' !== $post_type ) {
		return array();
	}

	if ( in_array( $status, array( 'ditunda', 'dibatalkan' ), true ) ) {
		return array(
			'key'     => '_sakolaan_status',
			'value'   => $status,
			'compare' => '=',
		);
	}

	$today     = wp_date( 'Y-m-d' );
	$automatic = pijar_sakolaan_get_automatic_event_meta_query();

	if ( 'terjadwal' === $status ) {
		return array(
			'relation' => 'AND',
			$automatic,
			array(
				'relation' => 'OR',
				array(
					'key'     => '_sakolaan_tanggal_mulai',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_sakolaan_tanggal_mulai',
					'value'   => '',
					'compare' => '=',
				),
				array(
					'key'     => '_sakolaan_tanggal_mulai',
					'value'   => $today,
					'compare' => '>',
					'type'    => 'DATE',
				),
			),
		);
	}

	if ( 'berlangsung' === $status ) {
		return array(
			'relation' => 'AND',
			$automatic,
			array(
				'key'     => '_sakolaan_tanggal_mulai',
				'value'   => $today,
				'compare' => '<=',
				'type'    => 'DATE',
			),
			array(
				'relation' => 'OR',
				array(
					'key'     => '_sakolaan_tanggal_selesai',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
				array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => '_sakolaan_tanggal_selesai',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_sakolaan_tanggal_selesai',
							'value'   => '',
							'compare' => '=',
						),
					),
					array(
						'key'     => '_sakolaan_tanggal_mulai',
						'value'   => $today,
						'compare' => '=',
						'type'    => 'DATE',
					),
				),
			),
		);
	}

	if ( 'selesai' === $status ) {
		return array(
			'relation' => 'AND',
			$automatic,
			array(
				'relation' => 'OR',
				array(
					'key'     => '_sakolaan_tanggal_selesai',
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				),
				array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => '_sakolaan_tanggal_selesai',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_sakolaan_tanggal_selesai',
							'value'   => '',
							'compare' => '=',
						),
					),
					array(
						'key'     => '_sakolaan_tanggal_mulai',
						'value'   => $today,
						'compare' => '<',
						'type'    => 'DATE',
					),
				),
			),
		);
	}

	return array();
}

/**
 * Terapkan pengurutan dan status pada query utama halaman arsip.
 *
 * @param WP_Query $query Query WordPress.
 * @return void
 */
function pijar_sakolaan_filter_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ( ! $query->is_post_type_archive() && ! $query->is_tax() && ! $query->is_category() && ! $query->is_tag() ) ) {
		return;
	}

	$post_type = pijar_sakolaan_get_archive_query_post_type( $query );
	$configs   = pijar_sakolaan_get_archive_configs();

	if ( ! isset( $configs[ $post_type ] ) ) {
		return;
	}
	if ( $query->is_post_type_archive( 'berita' ) ) {
		$query->set( 'post_type', 'post' );
	}

	$query->set( 'posts_per_page', min( 48, max( 1, absint( pijar_sakolaan_get_setting( 'display.archive_per_page', 12 ) ) ) ) );

	$sort_options = pijar_sakolaan_get_archive_sort_options( $post_type );
	$sort         = sanitize_key( (string) $query->get( 'sakolaan_sort' ) );
	$default_sort = pijar_sakolaan_is_manually_ordered_post_type( $post_type ) ? 'manual' : 'date_desc';
	$sort         = isset( $sort_options[ $sort ] ) ? $sort : $default_sort;

	if ( 'manual' === $sort ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
		$query->set( 'order', 'ASC' );
	} else {
		$sort_parts = explode( '_', $sort );
		$query->set( 'orderby', $sort_parts[0] );
		$query->set( 'order', strtoupper( $sort_parts[1] ) );
	}

	$status_options = pijar_sakolaan_get_archive_status_options( $post_type );
	$status         = sanitize_key( (string) $query->get( 'sakolaan_status' ) );

	if ( ! isset( $status_options[ $status ] ) ) {
		return;
	}

	$status_query = pijar_sakolaan_get_archive_status_meta_query( $post_type, $status );

	if ( empty( $status_query ) ) {
		return;
	}

	if ( isset( $status_query['key'] ) ) {
		$status_query = array( $status_query );
	}

	$existing_meta_query = $query->get( 'meta_query' );

	if ( ! empty( $existing_meta_query ) ) {
		$status_query = array(
			'relation' => 'AND',
			$existing_meta_query,
			$status_query,
		);
	}

	$query->set( 'meta_query', $status_query );
}
add_action( 'pre_get_posts', 'pijar_sakolaan_filter_archive_query' );

/**
 * Terapkan variasi query berdasarkan class block.
 *
 * @param array    $query Argumen WP_Query.
 * @param WP_Block $block Block Query.
 * @return array
 */
function pijar_sakolaan_filter_query_loop_vars( $query, $block ) {
	$class         = isset( $block->parsed_block['attrs']['className'] ) ? (string) $block->parsed_block['attrs']['className'] : '';
	$context_query = isset( $block->context['query'] ) && is_array( $block->context['query'] ) ? $block->context['query'] : array();
	$query_id      = isset( $block->context['queryId'] ) ? (int) $block->context['queryId'] : 0;
	$post_type     = isset( $query['post_type'] ) ? (string) $query['post_type'] : '';
	$standalone    = empty( $context_query['inherit'] );
	$is_upcoming   = false !== strpos( $class, 'sakolaan-query--upcoming' ) || ( $standalone && 4 === $query_id && 'agenda' === $post_type );
	$is_active     = false !== strpos( $class, 'sakolaan-query--active' ) || ( $standalone && 3 === $query_id && 'pengumuman' === $post_type );

	if ( $standalone && pijar_sakolaan_is_manually_ordered_post_type( $post_type ) && ! $is_upcoming && ! $is_active ) {
		$query['orderby'] = array( 'menu_order' => 'ASC', 'title' => 'ASC' );
		unset( $query['order'] );
	}

	/*
	 * Core menjalankan filter ini untuk block post-template, bukan block query
	 * induknya, sehingga className query tidak selalu tersedia. Query ID dan
	 * post type menjadi fallback deterministik untuk pattern bawaan theme.
	 */
	if ( $is_upcoming ) {
		$today = wp_date( 'Y-m-d' );

		$query['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'     => '_sakolaan_tanggal_selesai',
				'value'   => $today,
				'compare' => '>=',
				'type'    => 'DATE',
			),
			array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => '_sakolaan_tanggal_selesai',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_sakolaan_tanggal_selesai',
						'value'   => '',
						'compare' => '=',
					),
				),
				'sakolaan_start' => array(
					'key'     => '_sakolaan_tanggal_mulai',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		);
		$query['orderby']    = array( 'sakolaan_start' => 'ASC' );
		unset( $query['order'] );
	}

	if ( $is_active ) {
		$query['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'     => '_sakolaan_status',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_sakolaan_status',
				'value'   => 'arsip',
				'compare' => '!=',
			),
		);
	}

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'pijar_sakolaan_filter_query_loop_vars', 10, 2 );
