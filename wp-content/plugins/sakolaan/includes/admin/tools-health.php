<?php
/** Sakolaan admin: tools-health. @package Sakolaan */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- The diagnostic query checks cross-table orphan relations and is intentionally uncached.

function sakolaan_missing_fields( $post_id ) {
	$post_type = get_post_type( $post_id );
	$registry = sakolaan_content_registry();
	if ( ! isset( $registry[ $post_type ] ) ) {
		return array();
	}
	$post = get_post( $post_id );
	$missing = array();
	foreach ( $registry[ $post_type ]['required'] as $field ) {
		if ( 'title' === $field && ! trim( (string) $post->post_title ) ) {
			$missing[] = __( 'Judul', 'sakolaan' );
		} elseif ( 'editor' === $field && ! trim( wp_strip_all_tags( (string) $post->post_content ) ) ) {
			$missing[] = __( 'Isi', 'sakolaan' );
		} elseif ( 'thumbnail' === $field && ! has_post_thumbnail( $post_id ) ) {
			$missing[] = __( 'Foto utama', 'sakolaan' );
		} elseif ( ! in_array( $field, array( 'title', 'editor', 'thumbnail' ), true ) && '' === trim( (string) get_post_meta( $post_id, '_sakolaan_' . $field, true ) ) ) {
			$schema = sakolaan_get_meta_schema();
			$missing[] = $schema[ $post_type ]['fields'][ $field ]['label'] ?? $field;
		}
	}
	return $missing;
}

/** @return int[] */
function sakolaan_incomplete_ids( $post_type ) {
	static $cache = array();
	if ( isset( $cache[ $post_type ] ) ) {
		return $cache[ $post_type ];
	}
	if ( ! isset( sakolaan_content_registry()[ $post_type ] ) ) {
		return array();
	}
	$ids = get_posts( array( 'post_type' => $post_type, 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
	$cache[ $post_type ] = array_values( array_filter( array_map( 'absint', $ids ), static function ( $id ) { return current_user_can( 'read_post', $id ) && sakolaan_missing_fields( $id ); } ) );
	return $cache[ $post_type ];
}

function sakolaan_count_incomplete_content( $post_type ) {
	return count( sakolaan_incomplete_ids( $post_type ) );
}

function sakolaan_render_incomplete_filter( $post_type, $which ) {
	if ( 'top' !== $which || ! isset( sakolaan_content_registry()[ $post_type ] ) ) {
		return;
	}
	$active = isset( $_GET['sakolaan_incomplete'] ) && '1' === sanitize_key( wp_unslash( $_GET['sakolaan_incomplete'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter.
	printf( '<label class="sakolaan-incomplete-filter"><input type="checkbox" name="sakolaan_incomplete" value="1" %1$s> %2$s</label>', checked( $active, true, false ), esc_html__( 'Perlu dilengkapi', 'sakolaan' ) );
	if ( $active ) {
		printf( '<a href="%1$s">%2$s</a>', esc_url( sakolaan_content_list_url( $post_type ) ), esc_html__( 'Hapus filter', 'sakolaan' ) );
	}
}
add_action( 'restrict_manage_posts', 'sakolaan_render_incomplete_filter', 30, 2 );

function sakolaan_filter_incomplete_query( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || empty( $_GET['sakolaan_incomplete'] ) || '1' !== sanitize_key( wp_unslash( $_GET['sakolaan_incomplete'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filter.
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$post_type = (string) ( $query->get( 'post_type' ) ?: ( $screen && 'edit' === $screen->base ? $screen->post_type : '' ) );
	if ( ! isset( sakolaan_content_registry()[ $post_type ] ) ) {
		return;
	}
	$ids = sakolaan_incomplete_ids( $post_type );
	$query->set( 'post__in', $ids ? $ids : array( 0 ) );
}
add_action( 'pre_get_posts', 'sakolaan_filter_incomplete_query', 30 );

/** @return array<string,array{status:string,message:string}> */
function sakolaan_health_checks() {
	$checks = array();
	$theme_status = function_exists( 'sakolaan_get_companion_theme_status' ) ? sakolaan_get_companion_theme_status() : array();
	if ( ! empty( $theme_status['compatible'] ) && ! empty( $theme_status['runtime_ready'] ) ) {
		$theme_message = sprintf(
			/* translators: %s: matching plugin and theme version. */
			__( 'Plugin Sakolaan dan tema default Pijar Sakolaan aktif dengan versi yang sama: %s.', 'sakolaan' ),
			$theme_status['plugin_version']
		);
	} elseif ( 'theme_missing' === ( $theme_status['code'] ?? '' ) ) {
		$theme_message = __( 'Tema default Pijar Sakolaan belum terpasang.', 'sakolaan' );
	} elseif ( 'theme_inactive' === ( $theme_status['code'] ?? '' ) ) {
		$theme_message = __( 'Tema default Pijar Sakolaan sudah terpasang tetapi belum aktif.', 'sakolaan' );
	} elseif ( 'theme_incomplete' === ( $theme_status['code'] ?? '' ) ) {
		$theme_message = __( 'Tema default Pijar Sakolaan aktif tetapi API atau bootstrap-nya tidak lengkap.', 'sakolaan' );
	} else {
		$theme_message = sprintf(
			/* translators: 1: plugin version, 2: installed theme version. */
			__( 'Versi plugin (%1$s) dan theme (%2$s) tidak kompatibel.', 'sakolaan' ),
			$theme_status['plugin_version'] ?? SAKOLAAN_VERSION,
			$theme_status['theme_version'] ?? ''
		);
	}
	$checks['theme'] = array( 'status' => ! empty( $theme_status['compatible'] ) && ! empty( $theme_status['runtime_ready'] ) ? 'good' : 'warning', 'message' => $theme_message );
	$rules = get_option( 'rewrite_rules', array() );
	$checks['permalinks'] = array( 'status' => is_array( $rules ) && $rules ? 'good' : 'warning', 'message' => is_array( $rules ) && $rules ? __( 'Aturan permalink tersedia.', 'sakolaan' ) : __( 'Aturan permalink belum tersedia.', 'sakolaan' ) );
	foreach ( array( 'berita', 'kontak', 'ppdb' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( 'berita' === $slug ) {
			$ready = $page || post_type_exists( 'berita' );
		} else {
			$ready = $page instanceof WP_Post && 'publish' === $page->post_status;
		}
		/* translators: %s: required page slug. */
		$available_message = sprintf( __( 'Halaman %s tersedia.', 'sakolaan' ), $slug );
		/* translators: %s: required page slug. */
		$warning_message = sprintf( __( 'Halaman %s perlu diperiksa.', 'sakolaan' ), $slug );
		$checks[ 'page_' . $slug ] = array( 'status' => $ready ? 'good' : 'warning', 'message' => $ready ? $available_message : $warning_message );
	}
	foreach ( array( 'identity.name', 'contact.address', 'contact.whatsapp' ) as $key ) {
		$ready = (bool) sakolaan_get_setting( $key, '' );
		/* translators: %s: settings field key. */
		$complete_message = sprintf( __( '%s sudah diisi.', 'sakolaan' ), $key );
		/* translators: %s: settings field key. */
		$incomplete_message = sprintf( __( '%s belum diisi.', 'sakolaan' ), $key );
		$checks[ 'setting_' . $key ] = array( 'status' => $ready ? 'good' : 'warning', 'message' => $ready ? $complete_message : $incomplete_message );
	}
	$missing = 0;
	$broken_media = 0;
	$empty_types = 0;
	foreach ( sakolaan_content_registry() as $post_type => $item ) {
		$missing += sakolaan_count_incomplete_content( $post_type );
		$counts = wp_count_posts( $post_type );
		if ( empty( $counts->publish ) ) {
			++$empty_types;
		}
		$ids = get_posts( array( 'post_type' => $post_type, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
		foreach ( $ids as $id ) {
			$thumb = (int) get_post_meta( $id, '_thumbnail_id', true );
			if ( $thumb && 'attachment' !== get_post_type( $thumb ) ) {
				++$broken_media;
			}
			if ( 'galeri' === $post_type ) {
				foreach ( (array) get_post_meta( $id, '_sakolaan_gambar_ids', true ) as $media_id ) {
					if ( $media_id && 'attachment' !== get_post_type( (int) $media_id ) ) {
						++$broken_media;
					}
				}
			}
		}
	}
	/* translators: %d: number of incomplete content records. */
	$checks['content'] = array( 'status' => $missing ? 'warning' : 'good', 'message' => sprintf( __( '%d konten perlu dilengkapi.', 'sakolaan' ), $missing ) );
	/* translators: %d: number of content types without published items. */
	$checks['empty'] = array( 'status' => $empty_types ? 'warning' : 'good', 'message' => sprintf( __( '%d jenis konten belum memiliki item terbit.', 'sakolaan' ), $empty_types ) );
	/* translators: %d: number of broken media relations. */
	$checks['media'] = array( 'status' => $broken_media ? 'warning' : 'good', 'message' => sprintf( __( '%d relasi media rusak.', 'sakolaan' ), $broken_media ) );
	global $wpdb;
	$taxonomies = array_keys( sakolaan_taxonomy_registry() );
	$placeholders = implode( ',', array_fill( 0, count( $taxonomies ), '%s' ) );
	$broken_terms = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_relationships} rel INNER JOIN {$wpdb->term_taxonomy} tax ON tax.term_taxonomy_id = rel.term_taxonomy_id LEFT JOIN {$wpdb->posts} posts ON posts.ID = rel.object_id WHERE tax.taxonomy IN ($placeholders) AND posts.ID IS NULL", ...$taxonomies ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN placeholders match the bounded registry taxonomy list.
	/* translators: %d: number of orphaned taxonomy relationships. */
	$term_message = sprintf( __( '%d relasi taxonomy yatim.', 'sakolaan' ), $broken_terms );
	$checks['terms'] = array( 'status' => $broken_terms || $wpdb->last_error ? 'warning' : 'good', 'message' => $wpdb->last_error ? __( 'Relasi taxonomy belum dapat diperiksa.', 'sakolaan' ) : $term_message );
	return $checks;
}

function sakolaan_render_health_summary() {
	echo '<section class="sakolaan-health"><h3>' . esc_html__( 'Kesehatan situs', 'sakolaan' ) . '</h3><ul>';
	foreach ( sakolaan_health_checks() as $check ) {
		printf( '<li class="is-%1$s">%2$s</li>', esc_attr( $check['status'] ), esc_html( $check['message'] ) );
	}
	echo '</ul></section>';
}
