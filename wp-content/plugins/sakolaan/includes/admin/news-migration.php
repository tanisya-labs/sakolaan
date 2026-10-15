<?php
/** Compatibility for legacy news links and a read-only count. @package Sakolaan */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.SlowDBQuery -- Legacy compatibility performs bounded lookups against migration metadata.

function sakolaan_news_remaining_count() {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status NOT IN (%s, %s)",
		'berita', 'auto-draft', 'inherit'
	) );
}

function sakolaan_redirect_legacy_news() {
	if ( is_tax( array( 'kategori_berita', 'tag_berita' ) ) ) {
		$term = get_queried_object();
		if ( ! $term instanceof WP_Term || (int) $term->count > 0 ) {
			return;
		}
		$target_taxonomy = 'kategori_berita' === $term->taxonomy ? 'category' : 'post_tag';
		$target_id = absint( get_term_meta( $term->term_id, '_sakolaan_news_native_term', true ) );
		$url = $target_id && term_exists( $target_id, $target_taxonomy ) ? get_term_link( $target_id, $target_taxonomy ) : get_post_type_archive_link( 'berita' );
		if ( ! is_wp_error( $url ) && $url ) {
			wp_safe_redirect( $url, 301 );
			exit;
		}
	}

	if ( ! is_404() ) {
		return;
	}
	global $wp;
	$post_type = get_post_type_object( 'berita' );
	$base = $post_type && is_array( $post_type->rewrite ) && ! empty( $post_type->rewrite['slug'] ) ? trim( $post_type->rewrite['slug'], '/' ) : 'berita';
	if ( ! preg_match( '#^' . preg_quote( $base, '#' ) . '/([^/]+)/?$#', (string) $wp->request, $matches ) ) {
		return;
	}
	$slug = sanitize_title( rawurldecode( $matches[1] ) );
	$posts = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_key'       => '_sakolaan_news_legacy_slug',
		'meta_value'     => $slug,
		'fields'         => 'ids',
	) );
	if ( $posts && get_permalink( $posts[0] ) ) {
		wp_safe_redirect( get_permalink( $posts[0] ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'sakolaan_redirect_legacy_news', 5 );
