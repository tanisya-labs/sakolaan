<?php
/** Portable backup and restore for sakolaan-owned content. @package Sakolaan */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Import replacement uses explicit transaction boundaries; imported object caches are flushed immediately.

function sakolaan_data_backup_post_types() {
	return array_values( array_unique( array_merge( array( 'post', 'page', 'wp_navigation', 'wp_template', 'wp_template_part', 'wp_global_styles' ), array_keys( sakolaan_content_registry() ) ) ) );
}

function sakolaan_data_backup_taxonomies() {
	return array_values( array_unique( array_merge( array( 'category', 'post_tag', 'wp_theme' ), array_keys( sakolaan_taxonomy_registry() ) ) ) );
}

/** Build a normalized snapshot; media binaries are intentionally excluded. */
function sakolaan_build_data_snapshot() {
	$post_types = array_filter( sakolaan_data_backup_post_types(), 'post_type_exists' );
	$ids = get_posts( array( 'post_type' => $post_types, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'suppress_filters' => false ) );
	$posts = array(); $comments = array(); $media = array();
	foreach ( $ids as $id ) {
		$post = get_post( $id, ARRAY_A );
		if ( ! $post || in_array( $post['post_status'], array( 'auto-draft', 'trash' ), true ) ) { continue; }
		if ( in_array( $post['post_type'], array( 'wp_template', 'wp_template_part', 'wp_global_styles' ), true ) && taxonomy_exists( 'wp_theme' ) && ! has_term( get_stylesheet(), 'wp_theme', $id ) ) { continue; }
		$post['meta'] = get_post_meta( $id );
		unset( $post['meta']['_edit_lock'], $post['meta']['_edit_last'] );
		$post['terms'] = array();
		foreach ( get_object_taxonomies( $post['post_type'] ) as $taxonomy ) {
			$post['terms'][ $taxonomy ] = wp_get_object_terms( $id, $taxonomy, array( 'fields' => 'ids' ) );
		}
		$thumbnail = get_post_thumbnail_id( $id );
		if ( $thumbnail ) {
			$media[ $thumbnail ] = array( 'source_id' => $thumbnail, 'url' => wp_get_attachment_url( $thumbnail ), 'alt' => get_post_meta( $thumbnail, '_wp_attachment_image_alt', true ), 'title' => get_the_title( $thumbnail ) );
		}
		foreach ( get_comments( array( 'post_id' => $id, 'status' => 'all', 'orderby' => 'comment_ID', 'order' => 'ASC' ) ) as $comment ) {
			$row = get_object_vars( $comment ); $row['meta'] = get_comment_meta( $comment->comment_ID ); $comments[] = $row;
		}
		$posts[] = $post;
	}
	$terms = array();
	foreach ( array_filter( sakolaan_data_backup_taxonomies(), 'taxonomy_exists' ) as $taxonomy ) {
		foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) ) as $term ) {
			if ( is_wp_error( $term ) ) { continue; }
			$row = get_object_vars( $term ); $row['meta'] = get_term_meta( $term->term_id ); $terms[] = $row;
		}
	}
	return array(
		'format' => 'sakolaan-data', 'version' => 1, 'created_at' => gmdate( 'c' ),
		'source' => array( 'site' => home_url( '/' ), 'plugin_version' => SAKOLAAN_VERSION ),
		'counts' => array( 'posts' => count( $posts ), 'terms' => count( $terms ), 'comments' => count( $comments ), 'media' => count( $media ) ),
		'posts' => $posts, 'terms' => $terms, 'comments' => $comments, 'media' => array_values( $media ),
	);
}

function sakolaan_data_snapshot_checksum( $snapshot ) {
	return hash( 'sha256', wp_json_encode( $snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

function sakolaan_download_data_snapshot( $snapshot, $prefix = 'sakolaan-data' ) {
	$payload = array( 'checksum' => sakolaan_data_snapshot_checksum( $snapshot ), 'snapshot' => $snapshot );
	$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( false === $json ) { wp_die( esc_html__( 'Backup data tidak dapat dibuat.', 'sakolaan' ) ); }
	nocache_headers(); header( 'Content-Type: application/json; charset=utf-8' ); header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $prefix . '-' . gmdate( 'Y-m-d-His' ) . '.json' ) . '"' );
	echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
	exit;
}

function sakolaan_export_data() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'sakolaan_export_data' );
	sakolaan_download_data_snapshot( sakolaan_build_data_snapshot() );
}
add_action( 'admin_post_sakolaan_export_data', 'sakolaan_export_data' );

/** @return array|WP_Error */
function sakolaan_validate_data_payload( $json ) {
	$payload = json_decode( $json, true ); $snapshot = $payload['snapshot'] ?? null;
	if ( ! is_array( $snapshot ) || ! hash_equals( sakolaan_data_snapshot_checksum( $snapshot ), (string) ( $payload['checksum'] ?? '' ) ) ) { return new WP_Error( 'checksum', __( 'Checksum backup data tidak cocok.', 'sakolaan' ) ); }
	$snapshot = sakolaan_migration_normalize_backup_payload( $snapshot, 'data' );
	if ( 'sakolaan-data' !== ( $snapshot['format'] ?? '' ) || 1 !== ( $snapshot['version'] ?? null ) ) { return new WP_Error( 'format', __( 'Format backup data tidak valid.', 'sakolaan' ) ); }
	foreach ( array( 'posts', 'terms', 'comments', 'media', 'counts' ) as $key ) { if ( ! isset( $snapshot[ $key ] ) || ! is_array( $snapshot[ $key ] ) ) { return new WP_Error( 'content', __( 'Isi backup data tidak lengkap.', 'sakolaan' ) ); } }
	foreach ( array( 'posts', 'terms', 'comments', 'media' ) as $key ) { if ( (int) ( $snapshot['counts'][ $key ] ?? -1 ) !== count( $snapshot[ $key ] ) ) { return new WP_Error( 'counts', __( 'Jumlah record backup tidak cocok.', 'sakolaan' ) ); } }
	return $snapshot;
}

function sakolaan_preview_data_import() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'sakolaan_preview_data' );
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload fields are normalized and validated individually below.
	$file = isset( $_FILES['data_file'] ) && is_array( $_FILES['data_file'] ) ? wp_unslash( $_FILES['data_file'] ) : null;
	$tmp_name = is_array( $file ) && isset( $file['tmp_name'] ) && is_string( $file['tmp_name'] ) ? sanitize_text_field( $file['tmp_name'] ) : '';
	$file_name = is_array( $file ) && isset( $file['name'] ) && is_string( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '';
	$file_error = is_array( $file ) && isset( $file['error'] ) ? absint( $file['error'] ) : -1;
	$file_size = is_array( $file ) && isset( $file['size'] ) ? absint( $file['size'] ) : 0;
	if ( UPLOAD_ERR_OK !== $file_error || $file_size > 52428800 || 'json' !== strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) ) || ! $tmp_name || ! is_uploaded_file( $tmp_name ) ) { wp_die( esc_html__( 'File backup tidak valid atau melebihi 50 MB.', 'sakolaan' ), '', array( 'response' => 400 ) ); }
	$snapshot = sakolaan_validate_data_payload( (string) file_get_contents( $tmp_name ) );
	if ( is_wp_error( $snapshot ) ) { wp_die( esc_html( $snapshot->get_error_message() ), '', array( 'response' => 400 ) ); }
	$conflicts = 0; $unknown = 0; $missing_media = 0;
	$allowed = sakolaan_data_backup_post_types();
	foreach ( $snapshot['posts'] as $post ) {
		$type = sanitize_key( $post['post_type'] ?? '' );
		if ( ! in_array( $type, $allowed, true ) ) { ++$unknown; continue; }
		if ( ! empty( $post['post_name'] ) && get_page_by_path( $post['post_name'], OBJECT, $type ) ) { ++$conflicts; }
	}
	foreach ( $snapshot['media'] as $item ) { if ( ! empty( $item['url'] ) && ! attachment_url_to_postid( $item['url'] ) ) { ++$missing_media; } }
	$key = wp_generate_password( 20, false, false );
	$temp_path = wp_tempnam( 'sakolaan-data-' . get_current_user_id() . '.json' );
	if ( ! $temp_path || ! copy( $tmp_name, $temp_path ) ) { wp_die( esc_html__( 'Backup tidak dapat disiapkan untuk pratinjau.', 'sakolaan' ), '', array( 'response' => 500 ) ); }
	$old_preview = get_transient( 'sakolaan_data_preview_' . get_current_user_id() );
	if ( is_array( $old_preview ) && ! empty( $old_preview['path'] ) && is_file( $old_preview['path'] ) ) { wp_delete_file( $old_preview['path'] ); }
	set_transient( 'sakolaan_data_preview_' . get_current_user_id(), array( 'key' => $key, 'path' => $temp_path, 'counts' => $snapshot['counts'], 'conflicts' => $conflicts, 'unknown' => $unknown, 'missing_media' => $missing_media ), HOUR_IN_SECONDS );
	wp_safe_redirect( sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'data-backup', 'preview' => $key ) ) ); exit;
}
add_action( 'admin_post_sakolaan_preview_data', 'sakolaan_preview_data_import' );

function sakolaan_export_safety_data() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'sakolaan_export_safety_data' );
	set_transient( 'sakolaan_data_safety_' . get_current_user_id(), time(), HOUR_IN_SECONDS );
	sakolaan_download_data_snapshot( sakolaan_build_data_snapshot(), 'sakolaan-safety' );
}
add_action( 'admin_post_sakolaan_export_safety_data', 'sakolaan_export_safety_data' );

function sakolaan_delete_managed_data() {
	foreach ( sakolaan_data_backup_post_types() as $type ) {
		if ( ! post_type_exists( $type ) ) { continue; }
		foreach ( get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
			if ( in_array( $type, array( 'wp_template', 'wp_template_part', 'wp_global_styles' ), true ) && taxonomy_exists( 'wp_theme' ) && ! has_term( get_stylesheet(), 'wp_theme', $id ) ) { continue; }
			wp_delete_post( $id, true );
		}
	}
	foreach ( sakolaan_data_backup_taxonomies() as $taxonomy ) {
		if ( 'wp_theme' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) { continue; }
		foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids' ) ) as $term_id ) { wp_delete_term( $term_id, $taxonomy ); }
	}
}

/** Import normalized content and return counts or an error. */
function sakolaan_import_data_snapshot( $snapshot, $replace = false ) {
	if ( $replace ) { sakolaan_delete_managed_data(); }
	$term_map = array(); $created_terms = array(); $post_map = array(); $created_posts = array(); $result = array( 'posts' => 0, 'terms' => 0, 'comments' => 0, 'skipped' => 0, 'media_missing' => 0 );
	foreach ( $snapshot['terms'] as $row ) {
		$taxonomy = sanitize_key( $row['taxonomy'] ?? '' ); if ( ! taxonomy_exists( $taxonomy ) ) { ++$result['skipped']; continue; }
		$existing = term_exists( (string) ( $row['slug'] ?? '' ), $taxonomy );
		if ( $existing ) { $term_map[ (int) $row['term_id'] ] = (int) ( is_array( $existing ) ? $existing['term_id'] : $existing ); ++$result['skipped']; continue; }
		$inserted = wp_insert_term( (string) ( $row['name'] ?? '' ), $taxonomy, array( 'slug' => (string) ( $row['slug'] ?? '' ), 'description' => (string) ( $row['description'] ?? '' ) ) );
		if ( is_wp_error( $inserted ) ) { return $inserted; }
		$new_id = (int) $inserted['term_id']; $term_map[ (int) $row['term_id'] ] = $new_id; $created_terms[ (int) $row['term_id'] ] = true; ++$result['terms'];
		foreach ( (array) ( $row['meta'] ?? array() ) as $key => $values ) { foreach ( (array) $values as $value ) { add_term_meta( $new_id, $key, maybe_unserialize( $value ) ); } }
	}
	foreach ( $snapshot['terms'] as $row ) {
		$old_id = (int) ( $row['term_id'] ?? 0 ); $old_parent = (int) ( $row['parent'] ?? 0 ); $taxonomy = sanitize_key( $row['taxonomy'] ?? '' );
		if ( $old_parent && ! empty( $created_terms[ $old_id ] ) && isset( $term_map[ $old_id ], $term_map[ $old_parent ] ) ) { wp_update_term( $term_map[ $old_id ], $taxonomy, array( 'parent' => $term_map[ $old_parent ] ) ); }
	}
	foreach ( $snapshot['posts'] as $row ) {
		$type = sanitize_key( $row['post_type'] ?? '' ); if ( ! in_array( $type, sakolaan_data_backup_post_types(), true ) || ! post_type_exists( $type ) ) { ++$result['skipped']; continue; }
		$existing = ! empty( $row['post_name'] ) ? get_page_by_path( $row['post_name'], OBJECT, $type ) : null;
		if ( $existing && ! $replace ) { $post_map[ (int) $row['ID'] ] = (int) $existing->ID; ++$result['skipped']; continue; }
		$data = array_intersect_key( $row, array_flip( array( 'post_title', 'post_content', 'post_excerpt', 'post_status', 'post_type', 'post_name', 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'menu_order', 'comment_status', 'ping_status', 'post_password' ) ) );
		$data['post_author'] = get_current_user_id(); $data['post_parent'] = 0;
		$new_id = wp_insert_post( wp_slash( $data ), true ); if ( is_wp_error( $new_id ) ) { return $new_id; }
		$post_map[ (int) $row['ID'] ] = (int) $new_id; $created_posts[ (int) $row['ID'] ] = true; ++$result['posts'];
		foreach ( (array) ( $row['meta'] ?? array() ) as $key => $values ) {
			if ( '_thumbnail_id' === $key ) { continue; }
			foreach ( (array) $values as $value ) { add_post_meta( $new_id, $key, maybe_unserialize( $value ) ); }
		}
	}
	$media_map = array(); foreach ( $snapshot['media'] as $item ) { $found = ! empty( $item['url'] ) ? attachment_url_to_postid( $item['url'] ) : 0; if ( $found ) { $media_map[ (int) $item['source_id'] ] = $found; } else { ++$result['media_missing']; } }
	foreach ( $snapshot['posts'] as $row ) {
		$old_id = (int) ( $row['ID'] ?? 0 ); if ( empty( $post_map[ $old_id ] ) || empty( $created_posts[ $old_id ] ) ) { continue; } $new_id = $post_map[ $old_id ];
		$parent = (int) ( $row['post_parent'] ?? 0 ); if ( $parent && isset( $post_map[ $parent ] ) ) { wp_update_post( array( 'ID' => $new_id, 'post_parent' => $post_map[ $parent ] ) ); }
		if ( in_array( $row['post_type'] ?? '', array( 'wp_navigation', 'wp_template', 'wp_template_part' ), true ) && ! empty( $row['post_content'] ) ) {
			$content = preg_replace_callback( '/"(id|ref|postId)"\s*:\s*(\d+)/', static function ( $match ) use ( $post_map ) { $old = (int) $match[2]; return '"' . $match[1] . '":' . ( $post_map[ $old ] ?? $old ); }, (string) $row['post_content'] );
			wp_update_post( wp_slash( array( 'ID' => $new_id, 'post_content' => $content ) ) );
		}
		$thumbnail = isset( $row['meta']['_thumbnail_id'][0] ) ? (int) $row['meta']['_thumbnail_id'][0] : 0; if ( $thumbnail && isset( $media_map[ $thumbnail ] ) ) { set_post_thumbnail( $new_id, $media_map[ $thumbnail ] ); }
		foreach ( (array) ( $row['terms'] ?? array() ) as $taxonomy => $old_terms ) { if ( taxonomy_exists( $taxonomy ) ) { $mapped = array_values( array_filter( array_map( static function ( $id ) use ( $term_map ) { return $term_map[ (int) $id ] ?? 0; }, (array) $old_terms ) ) ); wp_set_object_terms( $new_id, $mapped, $taxonomy ); } }
	}
	foreach ( $snapshot['comments'] as $row ) {
		$old_post = (int) ( $row['comment_post_ID'] ?? 0 ); if ( empty( $post_map[ $old_post ] ) || empty( $created_posts[ $old_post ] ) ) { continue; }
		$meta = (array) ( $row['meta'] ?? array() ); unset( $row['meta'], $row['comment_ID'] ); $row['comment_post_ID'] = $post_map[ $old_post ]; $row['user_id'] = 0;
		$new_id = wp_insert_comment( wp_slash( $row ) ); if ( ! $new_id ) { continue; } ++$result['comments'];
		foreach ( $meta as $key => $values ) { foreach ( (array) $values as $value ) { add_comment_meta( $new_id, $key, maybe_unserialize( $value ) ); } }
	}
	return $result;
}

function sakolaan_apply_data_import() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'sakolaan_apply_data' );
	$preview = get_transient( 'sakolaan_data_preview_' . get_current_user_id() ); $key = sanitize_text_field( wp_unslash( $_POST['preview_key'] ?? '' ) ); $mode = sanitize_key( wp_unslash( $_POST['import_mode'] ?? 'merge' ) );
	if ( ! is_array( $preview ) || ! hash_equals( (string) ( $preview['key'] ?? '' ), $key ) || ! in_array( $mode, array( 'merge', 'replace' ), true ) ) { wp_die( esc_html__( 'Pratinjau impor tidak valid atau kedaluwarsa.', 'sakolaan' ), '', array( 'response' => 409 ) ); }
	if ( empty( $preview['path'] ) || ! is_readable( $preview['path'] ) ) { wp_die( esc_html__( 'File pratinjau tidak lagi tersedia.', 'sakolaan' ), '', array( 'response' => 409 ) ); }
	$snapshot = sakolaan_validate_data_payload( (string) file_get_contents( $preview['path'] ) );
	if ( is_wp_error( $snapshot ) ) { wp_die( esc_html( $snapshot->get_error_message() ), '', array( 'response' => 409 ) ); }
	if ( 'replace' === $mode ) {
		$confirmation = sanitize_text_field( wp_unslash( $_POST['confirmation'] ?? '' ) );
		if ( 'GANTI DATA SEKOLAH' !== $confirmation || ! get_transient( 'sakolaan_data_safety_' . get_current_user_id() ) ) { wp_die( esc_html__( 'Unduh backup pengaman dan masukkan konfirmasi sebelum mengganti data.', 'sakolaan' ), '', array( 'response' => 409 ) ); }
	}
	global $wpdb;
	$transaction = false !== $wpdb->query( 'START TRANSACTION' );
	if ( 'replace' === $mode && ! $transaction ) { wp_die( esc_html__( 'Database tidak mendukung transaksi aman untuk mode ganti.', 'sakolaan' ), '', array( 'response' => 500 ) ); }
	$result = sakolaan_import_data_snapshot( $snapshot, 'replace' === $mode );
	if ( is_wp_error( $result ) ) {
		if ( $transaction ) { $wpdb->query( 'ROLLBACK' ); wp_cache_flush(); }
		wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 500 ) );
	}
	if ( $transaction ) { $wpdb->query( 'COMMIT' ); }
	wp_cache_flush();
	wp_delete_file( $preview['path'] );
	delete_transient( 'sakolaan_data_preview_' . get_current_user_id() ); delete_transient( 'sakolaan_data_safety_' . get_current_user_id() );
	set_transient( 'sakolaan_data_result_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
	wp_safe_redirect( sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'data-backup', 'updated' => 'data' ) ) ); exit;
}
add_action( 'admin_post_sakolaan_apply_data', 'sakolaan_apply_data_import' );

function sakolaan_render_data_backup_tools() {
	echo '<p>' . esc_html__( 'Mencakup konten sekolah, halaman, taxonomy, navigasi, dan kustomisasi Site Editor. File media tidak disalin.', 'sakolaan' ) . '</p>';
	$result = get_transient( 'sakolaan_data_result_' . get_current_user_id() );
	if ( is_array( $result ) ) {
		/* translators: 1: imported content count, 2: imported term count, 3: imported comment count, 4: skipped record count, 5: missing media count. */
		printf( '<div class="notice sakolaan-notice notice-success inline"><p>%s</p></div>', esc_html( sprintf( __( '%1$d konten, %2$d term, dan %3$d komentar diimpor; %4$d record dilewati dan %5$d media tidak tersedia.', 'sakolaan' ), $result['posts'], $result['terms'], $result['comments'], $result['skipped'], $result['media_missing'] ) ) );
		delete_transient( 'sakolaan_data_result_' . get_current_user_id() );
	}
	printf( '<p><a class="button" href="%1$s">%2$s</a></p>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sakolaan_export_data' ), 'sakolaan_export_data' ) ), esc_html__( 'Unduh backup data', 'sakolaan' ) );
	echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_preview_data"><label for="sakolaan-data-import">' . esc_html__( 'Pilih backup data JSON', 'sakolaan' ) . '</label> <input id="sakolaan-data-import" type="file" name="data_file" accept="application/json,.json" required>';
	wp_nonce_field( 'sakolaan_preview_data' ); submit_button( __( 'Periksa backup', 'sakolaan' ), 'secondary', 'submit', false ); echo '</form>';
	$preview = get_transient( 'sakolaan_data_preview_' . get_current_user_id() );
	$key = isset( $_GET['preview'] ) ? sanitize_text_field( wp_unslash( $_GET['preview'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Preview selector.
	if ( ! is_array( $preview ) || ! $key || ! hash_equals( (string) $preview['key'], $key ) ) { return; }
	echo '<div class="sakolaan-import-preview"><h4>' . esc_html__( 'Pratinjau Backup Data', 'sakolaan' ) . '</h4>';
	/* translators: 1: content count, 2: term count, 3: comment count, 4: conflict count, 5: missing media count. */
	printf( '<p>%s</p>', esc_html( sprintf( __( '%1$d konten, %2$d term, %3$d komentar, %4$d konflik, dan %5$d media tidak ditemukan.', 'sakolaan' ), $preview['counts']['posts'], $preview['counts']['terms'], $preview['counts']['comments'], $preview['conflicts'], $preview['missing_media'] ) ) );
	if ( $preview['unknown'] ) {
		/* translators: %d: number of skipped records with unknown content types. */
		printf( '<p class="notice sakolaan-notice notice-warning inline">%s</p>', esc_html( sprintf( __( '%d record memakai tipe yang tidak dikenal dan akan dilewati.', 'sakolaan' ), $preview['unknown'] ) ) );
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_apply_data"><input type="hidden" name="preview_key" value="' . esc_attr( $key ) . '">'; wp_nonce_field( 'sakolaan_apply_data' );
	echo '<p><label><input type="radio" name="import_mode" value="merge" checked> ' . esc_html__( 'Gabungkan aman; konflik dilewati', 'sakolaan' ) . '</label></p><p><label><input type="radio" name="import_mode" value="replace"> ' . esc_html__( 'Ganti seluruh data sekolah', 'sakolaan' ) . '</label></p>';
	printf( '<p><a class="button" href="%1$s">%2$s</a></p>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sakolaan_export_safety_data' ), 'sakolaan_export_safety_data' ) ), esc_html__( 'Unduh backup pengaman sebelum mengganti', 'sakolaan' ) );
	echo '<p><label for="sakolaan-replace-confirm">' . esc_html__( 'Untuk mode ganti, ketik GANTI DATA SEKOLAH', 'sakolaan' ) . '</label> <input id="sakolaan-replace-confirm" type="text" name="confirmation" autocomplete="off"></p>';
	submit_button( __( 'Mulai impor data', 'sakolaan' ), 'primary', 'submit', false ); echo '</form></div>';
}
