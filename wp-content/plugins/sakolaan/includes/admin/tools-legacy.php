<?php
/** Sakolaan admin: tools-legacy. @package Sakolaan */
if ( ! defined( 'ABSPATH' ) ) { exit; }
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Exact legacy snapshots and ID-preserving restores cannot use object APIs or persistent caches.

/** Read the legacy news rows before deletion. Media files stay in the library. */
function sakolaan_legacy_news_snapshot() {
	global $wpdb;
	$posts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_type = %s OR (post_parent IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = %s) AND post_type IN ('revision', 'attachment')) ORDER BY ID", 'berita', 'berita' ), ARRAY_A );
	$ids = array_map( 'intval', array_column( array_values( array_filter( $posts, static function ( $post ) { return 'attachment' !== $post['post_type']; } ) ), 'ID' ) );
	$meta = array();
	$comments = array();
	$comment_meta = array();
	if ( $ids ) {
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->postmeta} WHERE post_id IN ($placeholders) ORDER BY meta_id", ...$ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN placeholders match the normalized integer IDs.
		$comments = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->comments} WHERE comment_post_ID IN ($placeholders) ORDER BY comment_ID", ...$ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN placeholders match the normalized integer IDs.
		$comment_ids = array_map( 'intval', array_column( $comments, 'comment_ID' ) );
		if ( $comment_ids ) {
			$comment_placeholders = implode( ',', array_fill( 0, count( $comment_ids ), '%d' ) );
			$comment_meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->commentmeta} WHERE comment_id IN ($comment_placeholders) ORDER BY meta_id", ...$comment_ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN placeholders match the normalized integer IDs.
		}
	}
	$taxonomies = array( 'kategori_berita', 'tag_berita' );
	$tax_placeholders = implode( ',', array_fill( 0, count( $taxonomies ), '%s' ) );
	$term_taxonomy = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ($tax_placeholders) ORDER BY term_taxonomy_id", ...$taxonomies ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN placeholders match the fixed taxonomy list.
	$term_ids = array_map( 'intval', array_column( $term_taxonomy, 'term_id' ) );
	$terms = array();
	$term_meta = array();
	$relationships = array();
	if ( $term_ids ) {
		$term_placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->terms} WHERE term_id IN ($term_placeholders) ORDER BY term_id", ...$term_ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN placeholders match the normalized integer IDs.
		$term_meta = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->termmeta} WHERE term_id IN ($term_placeholders) ORDER BY meta_id", ...$term_ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN placeholders match the normalized integer IDs.
		$taxonomy_ids = array_map( 'intval', array_column( $term_taxonomy, 'term_taxonomy_id' ) );
		$taxonomy_placeholders = implode( ',', array_fill( 0, count( $taxonomy_ids ), '%d' ) );
		$relationships = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ($taxonomy_placeholders) ORDER BY object_id, term_taxonomy_id", ...$taxonomy_ids ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Dynamic IN placeholders match the normalized integer IDs.
	}
	if ( $wpdb->last_error ) {
		return new WP_Error( 'snapshot_database', $wpdb->last_error );
	}
	$rows = array( 'posts' => $posts, 'postmeta' => $meta, 'comments' => $comments, 'commentmeta' => $comment_meta, 'terms' => $terms, 'term_taxonomy' => $term_taxonomy, 'termmeta' => $term_meta, 'term_relationships' => $relationships );
	return array_merge( array( 'format' => 'sakolaan-legacy-news', 'version' => 1, 'site' => home_url( '/' ), 'counts' => array_map( 'count', $rows ) ), $rows );
}

function sakolaan_legacy_news_checksum( $snapshot ) {
	return hash( 'sha256', wp_json_encode( $snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

function sakolaan_export_legacy_news() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'sakolaan_export_legacy_news' );
	$snapshot = sakolaan_legacy_news_snapshot();
	if ( is_wp_error( $snapshot ) ) {
		wp_die( esc_html( $snapshot->get_error_message() ) );
	}
	$encoded = wp_json_encode( $snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( ! $encoded || ! is_array( json_decode( $encoded, true ) ) || ! hash_equals( sakolaan_legacy_news_checksum( $snapshot ), sakolaan_legacy_news_checksum( json_decode( $encoded, true ) ) ) ) {
		wp_die( esc_html__( 'Backup tidak dapat divalidasi.', 'sakolaan' ) );
	}
	$checksum = sakolaan_legacy_news_checksum( $snapshot );
	$payload = array( 'checksum' => $checksum, 'signature' => hash_hmac( 'sha256', $checksum, wp_salt( 'auth' ) ), 'snapshot' => $snapshot );
	$output = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( false === $output ) {
		wp_die( esc_html__( 'Backup tidak dapat dikodekan menjadi JSON.', 'sakolaan' ), '', array( 'response' => 500 ) );
	}
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="sakolaan-berita-legacy-' . gmdate( 'Y-m-d' ) . '.json"' );
	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON backup download.
	exit;
}
add_action( 'admin_post_sakolaan_export_legacy_news', 'sakolaan_export_legacy_news' );

function sakolaan_render_legacy_news_tools() {
	$count = function_exists( 'sakolaan_news_remaining_count' ) ? sakolaan_news_remaining_count() : 0;
	/* translators: %d: number of legacy news records remaining. */
	printf( '<p>%s</p>', esc_html( sprintf( __( '%d berita lama tersisa. Buat backup sebelum pembersihan.', 'sakolaan' ), $count ) ) );
	if ( ! $count && ! get_option( 'sakolaan_legacy_news_purged', false ) ) {
		echo '<p>' . esc_html__( 'Tidak ada berita lama. Taksonomi lama dapat tetap berisi term kosong.', 'sakolaan' ) . '</p>';
	}
	if ( ! get_option( 'sakolaan_legacy_news_purged', false ) ) {
		echo '<p><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sakolaan_export_legacy_news' ), 'sakolaan_export_legacy_news' ) ) . '">' . esc_html__( 'Unduh backup berita lama', 'sakolaan' ) . '</a></p>';
		echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_purge_legacy_news">';
		wp_nonce_field( 'sakolaan_purge_legacy_news' );
		echo '<label for="sakolaan-legacy-backup">' . esc_html__( 'Unggah kembali backup yang baru diunduh untuk verifikasi:', 'sakolaan' ) . '</label> <input id="sakolaan-legacy-backup" type="file" name="backup_file" accept="application/json,.json" required>';
		echo '<p><label for="sakolaan-legacy-confirm">' . esc_html__( 'Ketik HAPUS BERITA LAMA:', 'sakolaan' ) . '</label> <input id="sakolaan-legacy-confirm" type="text" name="confirmation" required autocomplete="off"></p>';
		submit_button( __( 'Hapus data legacy', 'sakolaan' ), 'delete', 'submit', false );
		echo '</form>';
	}
	if ( get_option( 'sakolaan_legacy_news_purged', false ) ) {
		echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_restore_legacy_news">';
		wp_nonce_field( 'sakolaan_restore_legacy_news' );
		echo '<label for="sakolaan-legacy-restore">' . esc_html__( 'Pulihkan dari backup JSON:', 'sakolaan' ) . '</label> <input id="sakolaan-legacy-restore" type="file" name="backup_file" accept="application/json,.json" required>';
		submit_button( __( 'Pulihkan berita lama', 'sakolaan' ), 'secondary', 'submit', false );
		echo '</form>';
	}
}

/** @return array|WP_Error */
function sakolaan_read_legacy_backup_upload() {
	// Nonce and capability verification are performed by both admin-post callers before this helper is reached.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload fields are normalized and validated individually below.
	$file = isset( $_FILES['backup_file'] ) && is_array( $_FILES['backup_file'] ) ? wp_unslash( $_FILES['backup_file'] ) : null;
	$tmp_name = is_array( $file ) && isset( $file['tmp_name'] ) && is_string( $file['tmp_name'] ) ? sanitize_text_field( $file['tmp_name'] ) : '';
	$file_name = is_array( $file ) && isset( $file['name'] ) && is_string( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '';
	$file_error = is_array( $file ) && isset( $file['error'] ) ? absint( $file['error'] ) : -1;
	$file_size = is_array( $file ) && isset( $file['size'] ) ? absint( $file['size'] ) : 0;
	if ( UPLOAD_ERR_OK !== $file_error || $file_size > 52428800 || 'json' !== strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) ) || ! $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
		return new WP_Error( 'invalid_upload', __( 'File backup tidak valid atau terlalu besar.', 'sakolaan' ) );
	}
	$payload = json_decode( (string) file_get_contents( $tmp_name ), true );
	$snapshot = $payload['snapshot'] ?? null;
	$checksum = is_array( $snapshot ) ? sakolaan_legacy_news_checksum( $snapshot ) : '';
	if ( ! is_array( $snapshot ) || 'sakolaan-legacy-news' !== ( $snapshot['format'] ?? '' ) || 1 !== ( $snapshot['version'] ?? null ) || ( $snapshot['site'] ?? '' ) !== home_url( '/' ) || ! hash_equals( (string) ( $payload['checksum'] ?? '' ), $checksum ) || ! hash_equals( (string) ( $payload['signature'] ?? '' ), hash_hmac( 'sha256', $checksum, wp_salt( 'auth' ) ) ) ) {
		return new WP_Error( 'invalid_backup', __( 'Checksum backup atau alamat situs tidak cocok.', 'sakolaan' ) );
	}
	foreach ( array( 'posts', 'postmeta', 'comments', 'commentmeta', 'terms', 'term_taxonomy', 'termmeta', 'term_relationships' ) as $key ) {
		if ( ! isset( $snapshot[ $key ] ) || ! is_array( $snapshot[ $key ] ) || ! isset( $snapshot['counts'][ $key ] ) || (int) $snapshot['counts'][ $key ] !== count( $snapshot[ $key ] ) ) {
			return new WP_Error( 'incomplete_backup', __( 'Isi backup tidak lengkap.', 'sakolaan' ) );
		}
	}
	return $snapshot;
}

function sakolaan_purge_legacy_news() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'sakolaan_purge_legacy_news' );
	if ( get_option( 'sakolaan_legacy_news_purged', false ) ) {
		wp_die( esc_html__( 'Data legacy sudah dibersihkan.', 'sakolaan' ), '', array( 'response' => 409 ) );
	}
	$confirmation = isset( $_POST['confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmation'] ) ) : '';
	if ( 'HAPUS BERITA LAMA' !== $confirmation ) {
		wp_die( esc_html__( 'Konfirmasi tidak valid.', 'sakolaan' ), '', array( 'response' => 400 ) );
	}
	$snapshot = sakolaan_read_legacy_backup_upload();
	$current = sakolaan_legacy_news_snapshot();
	if ( is_wp_error( $snapshot ) || is_wp_error( $current ) || ! hash_equals( sakolaan_legacy_news_checksum( $current ), sakolaan_legacy_news_checksum( $snapshot ) ) ) {
		wp_die( esc_html__( 'Backup tidak cocok dengan data saat ini. Unduh backup baru dan coba lagi.', 'sakolaan' ), '', array( 'response' => 409 ) );
	}
	global $wpdb;
	if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
		wp_die( esc_html__( 'Database tidak mendukung transaksi pembersihan.', 'sakolaan' ), '', array( 'response' => 500 ) );
	}
	$failed = false;
	foreach ( $snapshot['posts'] as $post ) {
		if ( 'attachment' === $post['post_type'] && get_post( (int) $post['ID'] ) ) {
			$result = wp_update_post( array( 'ID' => (int) $post['ID'], 'post_parent' => 0 ), true );
			if ( is_wp_error( $result ) || ! $result ) {
				$failed = true;
				break;
			}
		}
	}
	if ( ! $failed ) {
		foreach ( $snapshot['posts'] as $post ) {
			if ( 'berita' === $post['post_type'] && ! wp_delete_post( (int) $post['ID'], true ) ) {
				$failed = true;
				break;
			}
		}
	}
	if ( ! $failed ) {
		foreach ( $snapshot['term_taxonomy'] as $term ) {
			$deleted = wp_delete_term( (int) $term['term_id'], $term['taxonomy'] );
			if ( is_wp_error( $deleted ) || false === $deleted ) {
				$failed = true;
				break;
			}
		}
	}
	if ( $failed ) {
		$wpdb->query( 'ROLLBACK' );
	} else {
		$wpdb->query( 'COMMIT' );
	}
	if ( $failed ) {
		wp_die( esc_html__( 'Pembersihan gagal; transaksi database dibatalkan.', 'sakolaan' ), '', array( 'response' => 500 ) );
	}
	update_option( 'sakolaan_legacy_news_purged', true, false );
	wp_safe_redirect( sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'legacy', 'updated' => 'legacy' ) ) );
	exit;
}
add_action( 'admin_post_sakolaan_purge_legacy_news', 'sakolaan_purge_legacy_news' );

function sakolaan_restore_legacy_news() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'sakolaan_restore_legacy_news' );
	if ( ! get_option( 'sakolaan_legacy_news_purged', false ) ) {
		wp_die( esc_html__( 'Pemulihan hanya tersedia setelah pembersihan legacy.', 'sakolaan' ), '', array( 'response' => 409 ) );
	}
	$snapshot = sakolaan_read_legacy_backup_upload();
	if ( is_wp_error( $snapshot ) ) {
		wp_die( esc_html( $snapshot->get_error_message() ), '', array( 'response' => 400 ) );
	}
	global $wpdb;
	$tables = array(
		'posts' => array( $wpdb->posts, 'ID' ),
		'terms' => array( $wpdb->terms, 'term_id' ),
		'term_taxonomy' => array( $wpdb->term_taxonomy, 'term_taxonomy_id' ),
		'postmeta' => array( $wpdb->postmeta, 'meta_id' ),
		'termmeta' => array( $wpdb->termmeta, 'meta_id' ),
		'comments' => array( $wpdb->comments, 'comment_ID' ),
		'commentmeta' => array( $wpdb->commentmeta, 'meta_id' ),
	);
	foreach ( $tables as $key => $table ) {
		foreach ( $snapshot[ $key ] as $row ) {
			if ( 'posts' === $key && 'attachment' === ( $row['post_type'] ?? '' ) ) {
				if ( ! get_post( (int) $row['ID'] ) ) {
					wp_die( esc_html__( 'Media backup tidak ditemukan; pemulihan dibatalkan.', 'sakolaan' ), '', array( 'response' => 409 ) );
				}
				continue;
			}
			$id = $row[ $table[1] ] ?? null;
			if ( ! $id || $wpdb->get_var( $wpdb->prepare( "SELECT {$table[1]} FROM {$table[0]} WHERE {$table[1]} = %d", $id ) ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table and key fixed above.
				wp_die( esc_html__( 'ID backup sudah digunakan. Pemulihan dibatalkan.', 'sakolaan' ), '', array( 'response' => 409 ) );
			}
		}
	}
	if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
		wp_die( esc_html__( 'Database tidak mendukung transaksi pemulihan.', 'sakolaan' ), '', array( 'response' => 500 ) );
	}
	$failed = false;
	foreach ( $tables as $key => $table ) {
		foreach ( $snapshot[ $key ] as $row ) {
			if ( 'posts' === $key && 'attachment' === ( $row['post_type'] ?? '' ) ) {
				continue;
			}
			if ( false === $wpdb->insert( $table[0], $row ) ) {
				$failed = true;
				break 2;
			}
		}
	}
	if ( ! $failed ) {
		foreach ( $snapshot['term_relationships'] as $row ) {
			if ( false === $wpdb->insert( $wpdb->term_relationships, $row ) ) {
				$failed = true;
				break;
			}
		}
	}
	if ( ! $failed ) {
		foreach ( $snapshot['posts'] as $post ) {
			if ( 'attachment' === $post['post_type'] && false === $wpdb->update( $wpdb->posts, array( 'post_parent' => (int) $post['post_parent'] ), array( 'ID' => (int) $post['ID'] ) ) ) {
				$failed = true;
				break;
			}
		}
	}
	if ( $failed ) {
		$wpdb->query( 'ROLLBACK' );
	} else {
		$wpdb->query( 'COMMIT' );
	}
	if ( $failed ) {
		wp_die( esc_html__( 'Pemulihan database gagal; tidak ada perubahan yang disimpan.', 'sakolaan' ), '', array( 'response' => 500 ) );
	}
	foreach ( $snapshot['posts'] as $post ) {
		clean_post_cache( (int) $post['ID'] );
	}
	foreach ( $snapshot['term_taxonomy'] as $term ) {
		clean_term_cache( (int) $term['term_id'], $term['taxonomy'] );
	}
	update_option( 'sakolaan_legacy_news_purged', false, false );
	wp_safe_redirect( sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'legacy', 'updated' => 'restored' ) ) );
	exit;
}
add_action( 'admin_post_sakolaan_restore_legacy_news', 'sakolaan_restore_legacy_news' );

