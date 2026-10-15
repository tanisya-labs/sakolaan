<?php
/** Native list ordering persistence. @package Sakolaan */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function sakolaan_save_content_order() {
	$post_type = isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : '';

	if ( ! in_array( $post_type, sakolaan_get_orderable_post_types(), true ) ) {
		wp_die( esc_html__( 'Tipe konten tidak valid.', 'sakolaan' ), '', array( 'response' => 400 ) );
	}

	$object = get_post_type_object( $post_type );

	if ( ! $object || ! current_user_can( $object->cap->edit_posts ) ) {
		wp_die( esc_html__( 'Anda tidak memiliki izin untuk mengatur konten ini.', 'sakolaan' ), '', array( 'response' => 403 ) );
	}

	check_admin_referer( 'sakolaan_content_order_' . $post_type );

	$submitted = isset( $_POST['ordered_ids'] ) && is_array( $_POST['ordered_ids'] ) ? array_map( 'absint', wp_unslash( $_POST['ordered_ids'] ) ) : array();
	$submitted = array_values( array_unique( array_filter( $submitted ) ) );
	$valid     = array();
	$errors    = 0;

	foreach ( $submitted as $post_id ) {
		if ( $post_type === get_post_type( $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
			$valid[] = $post_id;
		} else {
			++$errors;
		}
	}

	$remaining = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'order'          => 'ASC',
		)
	);
	$remaining = array_values( array_diff( array_map( 'absint', $remaining ), $valid ) );
	$ordered = array_merge( $valid, array_map( 'absint', $remaining ) );

	foreach ( $ordered as $index => $post_id ) {
		if ( $post_type !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			++$errors;
			continue;
		}

		$result = wp_update_post(
			array(
				'ID'         => $post_id,
				'menu_order' => ( $index + 1 ) * 10,
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			++$errors;
		}
	}

	$url = admin_url( 'edit.php' . ( 'post' === $post_type ? '' : '?post_type=' . $post_type ) );
	$url = add_query_arg( 'sakolaan_order_status', $errors ? 'partial' : 'success', $url );
	wp_safe_redirect( $url );
	exit;
}
add_action( 'admin_post_sakolaan_save_content_order', 'sakolaan_save_content_order' );

/**
 * Tempatkan konten katalog baru setelah item terakhir.
 *
 * @param int     $post_id ID post.
 * @param WP_Post $post    Post.
 * @param bool    $update  Apakah update.
 * @return void
 */
function sakolaan_append_new_orderable_content( $post_id, $post, $update ) {
	static $running = false;
	unset( $update );

	if ( $running || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 0 !== (int) $post->menu_order || ! in_array( $post->post_type, sakolaan_get_orderable_post_types(), true ) || in_array( $post->post_status, array( 'auto-draft', 'trash' ), true ) ) {
		return;
	}

	$last = get_posts(
		array(
			'post_type'      => $post->post_type,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => 2,
			'fields'         => 'ids',
			'orderby'        => 'menu_order',
			'order'          => 'DESC',
		)
	);
	$last = array_values( array_diff( array_map( 'absint', $last ), array( (int) $post_id ) ) );
	$next = $last ? (int) get_post_field( 'menu_order', $last[0] ) + 10 : 10;

	$running = true;
	wp_update_post( array( 'ID' => $post_id, 'menu_order' => $next ) );
	$running = false;
}
add_action( 'save_post', 'sakolaan_append_new_orderable_content', 20, 3 );

/**
 * Isi urutan awal konten lama tanpa mengubah urutan tampil sebelumnya.
 *
 * @return void
 */
function sakolaan_migrate_content_order() {
	if ( '1' === get_option( 'sakolaan_content_order_migrated' ) ) {
		return;
	}

	foreach ( sakolaan_get_orderable_post_types() as $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			continue;
		}

		$orderby = in_array( $post_type, array( 'prestasi', 'galeri' ), true )
			? array( 'date' => 'DESC', 'title' => 'ASC' )
			: array( 'menu_order' => 'ASC', 'title' => 'ASC' );
		$ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => $orderby,
				'order'          => 'ASC',
			)
		);

		foreach ( $ids as $index => $post_id ) {
			wp_update_post( array( 'ID' => (int) $post_id, 'menu_order' => ( $index + 1 ) * 10 ) );
		}
	}

	update_option( 'sakolaan_content_order_migrated', '1', false );
}
