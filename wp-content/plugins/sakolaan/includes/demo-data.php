<?php
/**
 * Dataset demo lengkap untuk Sakolaan dan Pijar Sakolaan.
 *
 * Semua operasi hanya dijalankan dari handler admin yang dilindungi capability,
 * nonce, dan konfirmasi tertulis. Data lama yang dikelola Sakolaan dihapus
 * permanen sebelum fixture baru dibuat.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.SlowDBQuery -- Demo cleanup uses bounded reference checks and explicitly managed demo metadata.

/**
 * Versi struktur dataset demo.
 */
const SAKOLAAN_DEMO_VERSION = '2.2.0';

/**
 * Post type yang sepenuhnya dimiliki Sakolaan.
 *
 * @return string[]
 */
function sakolaan_demo_post_types() {
	return array_values( array_diff( array_keys( sakolaan_content_registry() ), array( 'post' ) ) );
}

/**
 * Taxonomy yang sepenuhnya dimiliki Sakolaan.
 *
 * @return string[]
 */
function sakolaan_demo_taxonomies() {
	return array_keys( sakolaan_taxonomy_registry() );
}

/**
 * Path halaman bawaan Pijar Sakolaan.
 *
 * Anak ditempatkan lebih dahulu agar dapat dihapus tanpa meninggalkan relasi.
 *
 * @return string[]
 */
function sakolaan_demo_page_paths() {
	return array(
		'profil/sejarah',
		'profil/visi-misi',
		'profil/struktur-organisasi',
		'profil/fasilitas',
		'profil/akreditasi',
		'profil',
		'akademik',
		'kesiswaan',
		'ppdb',
		'kontak',
	);
}

/**
 * Buat struktur laporan operasi.
 *
 * @return array
 */
function sakolaan_demo_new_report() {
	return array(
		'status'  => 'success',
		'message' => '',
		'counts'  => array(),
		'errors'  => array(),
		'links'   => array(),
	);
}

/**
 * Catat error tanpa menghentikan seluruh proses seed.
 *
 * @param array  $report Laporan operasi.
 * @param string $message Pesan error.
 * @return void
 */
function sakolaan_demo_add_error( &$report, $message ) {
	$report['status']   = 'warning';
	$report['errors'][] = sanitize_text_field( $message );
}

/**
 * Ambil seluruh ID post untuk tipe tertentu.
 *
 * @param string|string[] $post_type Post type.
 * @return int[]
 */
function sakolaan_demo_get_post_ids( $post_type ) {
	return array_map(
		'absint',
		get_posts(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'any',
				'fields'                 => 'ids',
				'posts_per_page'         => -1,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		)
	);
}

/**
 * Kumpulkan attachment yang direferensikan data sekolah sebelum data dihapus.
 *
 * @param int[] $managed_post_ids ID post yang akan dihapus.
 * @return int[]
 */
function sakolaan_demo_collect_attachment_ids( $managed_post_ids ) {
	$ids      = array();
	$settings = get_option( 'sakolaan_settings', array() );

	if ( is_array( $settings ) ) {
		foreach ( sakolaan_get_settings_schema() as $group => $definition ) {
			foreach ( $definition['fields'] as $key => $field ) {
				if ( 'image' === $field['type'] && ! empty( $settings[ $group ][ $key ] ) ) {
					$ids[] = absint( $settings[ $group ][ $key ] );
				}
			}
		}
	}

	foreach ( $managed_post_ids as $post_id ) {
		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $thumbnail_id ) {
			$ids[] = (int) $thumbnail_id;
		}

		$gallery_ids = get_post_meta( $post_id, '_sakolaan_gambar_ids', true );

		if ( is_array( $gallery_ids ) ) {
			$ids = array_merge( $ids, array_map( 'absint', $gallery_ids ) );
		}

		$attachment_id = absint( get_post_meta( $post_id, '_sakolaan_lampiran_id', true ) );

		if ( $attachment_id ) {
			$ids[] = $attachment_id;
		}
	}

	$manifest = get_option( 'sakolaan_demo_manifest', array() );

	if ( is_array( $manifest ) && ! empty( $manifest['attachments'] ) && is_array( $manifest['attachments'] ) ) {
		$ids = array_merge( $ids, array_map( 'absint', $manifest['attachments'] ) );
	}

	return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

/**
 * Apakah attachment juga digunakan post di luar data yang akan dihapus.
 *
 * @param int   $attachment_id Attachment ID.
 * @param int[] $managed_ids   Semua ID post/page/menu yang akan dihapus.
 * @return bool
 */
function sakolaan_demo_attachment_is_shared( $attachment_id, $managed_ids ) {
	global $wpdb;

	$attachment = get_post( $attachment_id );

	if ( ! $attachment instanceof WP_Post || 'attachment' !== $attachment->post_type ) {
		return false;
	}

	if ( $attachment->post_parent && ! in_array( (int) $attachment->post_parent, $managed_ids, true ) ) {
		return true;
	}

	$thumbnail_users = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d",
			$attachment_id
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Pemeriksaan referensi lintas post sebelum attachment dihapus.

	foreach ( array_map( 'absint', $thumbnail_users ) as $post_id ) {
		if ( in_array( $post_id, $managed_ids, true ) ) {
			continue;
		}

		return true;
	}

	$content_users = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type NOT IN ('attachment', 'revision') AND post_content LIKE %s",
			'%' . $wpdb->esc_like( 'wp-image-' . $attachment_id ) . '%'
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Pemeriksaan block image sebelum attachment dihapus.

	foreach ( array_map( 'absint', $content_users ) as $post_id ) {
		if ( ! in_array( $post_id, $managed_ids, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Hapus seluruh data Sakolaan yang termasuk cakupan demo.
 *
 * @param bool $reset_identity Reset identitas inti WordPress setelah penghapusan.
 * @return array Laporan penghapusan.
 */
function sakolaan_demo_purge( $reset_identity = true ) {
	$report           = sakolaan_demo_new_report();
	$managed_post_ids = sakolaan_demo_get_post_ids( array_values( array_diff( sakolaan_demo_post_types(), array( 'berita' ) ) ) );
	$demo_native_posts = get_posts(
		array(
			'post_type'      => array( 'post', 'berita' ),
			'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_sakolaan_demo_data',
			'meta_compare'   => 'EXISTS',
		)
	);
	$managed_post_ids = array_values( array_unique( array_merge( $managed_post_ids, array_map( 'absint', $demo_native_posts ) ) ) );
	$page_ids         = array();
	$all_pages        = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		)
	);

	foreach ( $all_pages as $page ) {
		$is_demo   = '' !== (string) get_post_meta( $page->ID, '_sakolaan_demo_data', true );

		if ( $is_demo ) {
			$page_ids[] = (int) $page->ID;
		}
	}
	$page_ids = array_values( array_unique( $page_ids ) );

	$manifest = get_option( 'sakolaan_demo_manifest', array() );
	$menu_ids = sakolaan_demo_get_post_ids( 'wp_navigation' );

	if ( is_array( $manifest ) && ! empty( $manifest['navigation'] ) && is_array( $manifest['navigation'] ) ) {
		$menu_ids = array_merge( $menu_ids, array_map( 'absint', $manifest['navigation'] ) );
	}
	$menu_ids = array_values( array_unique( array_filter( $menu_ids ) ) );
	$menu_ids = array_values(
		array_filter(
			$menu_ids,
			static function ( $menu_id ) {
				return (int) get_option( 'pijar_sakolaan_menu_seeded' ) === $menu_id || 'Menu utama' === get_the_title( $menu_id );
			}
		)
	);

	$all_managed_ids = array_values( array_unique( array_merge( $managed_post_ids, $page_ids, $menu_ids ) ) );
	$attachment_ids  = sakolaan_demo_collect_attachment_ids( $managed_post_ids );
	$shared_media    = array();

	foreach ( $attachment_ids as $attachment_id ) {
		if ( sakolaan_demo_attachment_is_shared( $attachment_id, $all_managed_ids ) ) {
			$shared_media[] = $attachment_id;
		}
	}

	foreach ( $managed_post_ids as $post_id ) {
		if ( ! wp_delete_post( $post_id, true ) ) {
			/* translators: %d: content post ID. */
			sakolaan_demo_add_error( $report, sprintf( __( 'Konten #%d gagal dihapus.', 'sakolaan' ), $post_id ) );
		}
	}
	$report['counts']['konten_dihapus'] = count( $managed_post_ids );

	foreach ( $page_ids as $page_id ) {
		if ( ! wp_delete_post( $page_id, true ) ) {
			/* translators: %d: page ID. */
			sakolaan_demo_add_error( $report, sprintf( __( 'Halaman #%d gagal dihapus.', 'sakolaan' ), $page_id ) );
		}
	}
	$report['counts']['halaman_dihapus'] = count( $page_ids );

	foreach ( $menu_ids as $menu_id ) {
		if ( ! wp_delete_post( $menu_id, true ) ) {
			/* translators: %d: navigation post ID. */
			sakolaan_demo_add_error( $report, sprintf( __( 'Navigasi #%d gagal dihapus.', 'sakolaan' ), $menu_id ) );
		}
	}

	$deleted_terms = 0;

	foreach ( sakolaan_demo_taxonomies() as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$term_ids = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( is_wp_error( $term_ids ) ) {
			sakolaan_demo_add_error( $report, $term_ids->get_error_message() );
			continue;
		}

		foreach ( $term_ids as $term_id ) {
			$result = wp_delete_term( (int) $term_id, $taxonomy );

			if ( is_wp_error( $result ) ) {
				sakolaan_demo_add_error( $report, $result->get_error_message() );
			} else {
				++$deleted_terms;
			}
		}
	}
	$report['counts']['term_dihapus'] = $deleted_terms;
	foreach ( array( 'category', 'post_tag' ) as $taxonomy ) {
		$demo_terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids', 'meta_key' => '_sakolaan_demo_data' ) );
		if ( is_wp_error( $demo_terms ) ) {
			sakolaan_demo_add_error( $report, $demo_terms->get_error_message() );
			continue;
		}
		foreach ( $demo_terms as $term_id ) {
			$term = get_term( $term_id, $taxonomy );
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			if ( (int) $term->count > 0 || ( 'category' === $taxonomy && (int) get_option( 'default_category' ) === (int) $term_id ) ) {
				delete_term_meta( $term_id, '_sakolaan_demo_data' );
			continue;
			}
			$result = wp_delete_term( (int) $term_id, $taxonomy );
			if ( is_wp_error( $result ) ) {
				sakolaan_demo_add_error( $report, $result->get_error_message() );
			}
		}
	}

	$deleted_media = 0;

	foreach ( array_diff( $attachment_ids, $shared_media ) as $attachment_id ) {
		if ( wp_delete_attachment( $attachment_id, true ) ) {
			++$deleted_media;
		} else {
			/* translators: %d: media attachment ID. */
			sakolaan_demo_add_error( $report, sprintf( __( 'Media #%d gagal dihapus.', 'sakolaan' ), $attachment_id ) );
		}
	}
	$report['counts']['media_dihapus'] = $deleted_media;
	$report['counts']['media_dipertahankan'] = count( $shared_media );

	delete_option( 'sakolaan_settings' );
	delete_option( 'sakolaan_demo_manifest' );
	delete_option( 'pijar_sakolaan_menu_seeded' );

	if ( $reset_identity ) {
		update_option( 'blogname', __( 'Situs Sekolah', 'sakolaan' ) );
		update_option( 'blogdescription', '' );
		delete_option( 'site_logo' );
		delete_option( 'site_icon' );
	}

	if ( function_exists( 'sakolaan_clear_settings_cache' ) ) {
		sakolaan_clear_settings_cache();
	}

	return $report;
}

/**
 * Import satu aset lokal ke Media Library.
 *
 * @param string $path    Path file sumber.
 * @param string $title   Judul media.
 * @param string $alt     Alt text gambar.
 * @param array  $report  Laporan operasi.
 * @return int Attachment ID atau 0.
 */
function sakolaan_demo_import_media( $path, $title, $alt, &$report ) {
	if ( ! is_readable( $path ) ) {
		/* translators: %s: missing demo asset filename. */
		sakolaan_demo_add_error( $report, sprintf( __( 'Aset demo tidak ditemukan: %s', 'sakolaan' ), basename( $path ) ) );
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- File lokal plugin.

	if ( false === $contents ) {
		/* translators: %s: unreadable demo asset filename. */
		sakolaan_demo_add_error( $report, sprintf( __( 'Aset demo gagal dibaca: %s', 'sakolaan' ), basename( $path ) ) );
		return 0;
	}

	$upload = wp_upload_bits( basename( $path ), null, $contents );

	if ( ! empty( $upload['error'] ) ) {
		sakolaan_demo_add_error( $report, $upload['error'] );
		return 0;
	}

	$filetype      = wp_check_filetype( $upload['file'] );
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_text_field( $title ),
			'post_content'   => '',
			'post_excerpt'   => sanitize_text_field( $alt ),
			'post_status'    => 'inherit',
		),
		$upload['file'],
		0,
		true
	);

	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $upload['file'] );
		sakolaan_demo_add_error( $report, $attachment_id->get_error_message() );
		return 0;
	}

	$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );

	if ( ! empty( $metadata ) ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
	update_post_meta( $attachment_id, '_sakolaan_demo_data', SAKOLAAN_DEMO_VERSION );

	return (int) $attachment_id;
}

/**
 * Buat byte PDF sederhana dan valid untuk menguji lampiran pengumuman.
 *
 * @return string
 */
function sakolaan_demo_pdf_contents() {
	$stream  = "BT\n/F1 18 Tf\n72 760 Td\n(Lampiran Pengumuman Demo) Tj\n0 -30 Td\n/F1 11 Tf\n(Dokumen ini dibuat otomatis untuk pengujian Sakolaan.) Tj\n0 -18 Td\n(Silakan ganti dengan dokumen resmi sebelum situs dipublikasikan.) Tj\nET";
	$objects = array(
		'<< /Type /Catalog /Pages 2 0 R >>',
		'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
		'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
		'<< /Length ' . strlen( $stream ) . " >>\nstream\n" . $stream . "\nendstream",
		'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
	);
	$pdf     = "%PDF-1.4\n";
	$offsets = array( 0 );

	foreach ( $objects as $index => $object ) {
		$offsets[] = strlen( $pdf );
		$pdf      .= ( $index + 1 ) . " 0 obj\n" . $object . "\nendobj\n";
	}

	$xref = strlen( $pdf );
	$pdf .= "xref\n0 " . ( count( $objects ) + 1 ) . "\n0000000000 65535 f \n";

	for ( $i = 1; $i <= count( $objects ); $i++ ) {
		$pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
	}

	return $pdf . 'trailer' . "\n<< /Size " . ( count( $objects ) + 1 ) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
}

/**
 * Import PDF demo ke Media Library.
 *
 * @param array $report Laporan operasi.
 * @return int Attachment ID atau 0.
 */
function sakolaan_demo_import_pdf( &$report ) {
	$upload = wp_upload_bits( 'lampiran-pengumuman-demo.pdf', null, sakolaan_demo_pdf_contents() );

	if ( ! empty( $upload['error'] ) ) {
		sakolaan_demo_add_error( $report, $upload['error'] );
		return 0;
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'application/pdf',
			'post_title'     => __( 'Lampiran Pengumuman Demo', 'sakolaan' ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$upload['file'],
		0,
		true
	);

	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $upload['file'] );
		sakolaan_demo_add_error( $report, $attachment_id->get_error_message() );
		return 0;
	}

	update_post_meta( $attachment_id, '_sakolaan_demo_data', SAKOLAAN_DEMO_VERSION );

	return (int) $attachment_id;
}

/**
 * Import seluruh media demo.
 *
 * @param string $base        Direktori media tervalidasi.
 * @param string $school_name Nama sekolah saat installer dijalankan.
 * @param array  $manifest    Manifest seed.
 * @param array  $report      Laporan operasi.
 * @return array Peta key media ke attachment ID.
 */
function sakolaan_demo_seed_media( $base, $school_name, &$manifest, &$report ) {
	$base   = trailingslashit( $base );
	$assets = sakolaan_demo_media_assets( $school_name );
	$media = array();

	foreach ( $assets as $key => $asset ) {
		$media[ $key ] = sakolaan_demo_import_media( $base . $asset[0], $asset[1], $asset[2], $report );
	}
	$media['achievement'] = $media['achievement_math'];

	$media['attachment']     = sakolaan_demo_import_pdf( $report );
	$manifest['attachments'] = array_values( array_unique( array_filter( array_map( 'absint', $media ) ) ) );
	$report['counts']['media'] = count( $manifest['attachments'] );

	return $media;
}

/**
 * Buat term demo dan kembalikan peta term.
 *
 * @param array $manifest Manifest seed.
 * @param array $report   Laporan operasi.
 * @return array
 */
function sakolaan_demo_seed_terms( &$manifest, &$report ) {
	$definitions = array(
		'category'          => array( 'Umum', 'Akademik', 'Kegiatan Siswa', 'Kesiswaan', 'Keagamaan', 'Prestasi', 'PPDB' ),
		'post_tag'          => array( 'Siswa', 'Guru', 'Madrasah', 'Literasi', 'Teknologi', 'Sains', 'Pembelajaran', 'Karakter', 'Bahasa', 'OSIM', 'Kepemimpinan', 'Sosial', 'Olahraga', 'Kebersamaan', 'Ibadah' ),
		'kelompok_gtk'      => array( 'Guru', 'Tenaga Kependidikan' ),
		'kategori_prestasi' => array( 'Akademik', 'Olahraga', 'Seni' ),
		'tingkat_prestasi'  => array( 'Sekolah', 'Kecamatan', 'Kabupaten/Kota', 'Provinsi', 'Nasional', 'Internasional' ),
		'album_galeri'      => array( 'Kegiatan Sekolah', 'Pembelajaran', 'Prestasi Siswa', 'Ekstrakurikuler' ),
	);
	$terms = array();

	foreach ( $definitions as $taxonomy => $names ) {
		foreach ( $names as $name ) {
			$existing = term_exists( sanitize_title( $name ), $taxonomy );
			if ( ! $existing ) {
				$existing = term_exists( $name, $taxonomy );
			}
			$term_id = sakolaan_insert_term_once( $name, $taxonomy );

			if ( is_wp_error( $term_id ) ) {
				sakolaan_demo_add_error( $report, $term_id->get_error_message() );
				continue;
			}

			$terms[ $taxonomy ][ sanitize_title( $name ) ] = (int) $term_id;
			$manifest['terms'][] = (int) $term_id;
			if ( ! $existing && in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
				update_term_meta( $term_id, '_sakolaan_demo_data', SAKOLAAN_DEMO_VERSION );
			}
		}
	}

	$report['counts']['term'] = isset( $manifest['terms'] ) ? count( array_unique( $manifest['terms'] ) ) : 0;

	return $terms;
}

/**
 * Konten block sederhana yang tetap representatif untuk pengujian editor/theme.
 *
 * @param string   $lead    Paragraf pembuka.
 * @param string[] $points  Daftar poin.
 * @param string   $closing Paragraf penutup.
 * @return string
 */
function sakolaan_demo_block_content( $lead, $points = array(), $closing = '' ) {
	$content  = '<!-- wp:paragraph --><p>' . esc_html( $lead ) . '</p><!-- /wp:paragraph -->';

	if ( ! empty( $points ) ) {
		$content .= '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">' . esc_html__( 'Informasi utama', 'sakolaan' ) . '</h2><!-- /wp:heading -->';
		$content .= '<!-- wp:list --><ul class="wp-block-list">';

		foreach ( $points as $point ) {
			$content .= '<!-- wp:list-item --><li>' . esc_html( $point ) . '</li><!-- /wp:list-item -->';
		}

		$content .= '</ul><!-- /wp:list -->';
	}

	if ( $closing ) {
		$content .= '<!-- wp:paragraph --><p>' . esc_html( $closing ) . '</p><!-- /wp:paragraph -->';
	}

	return $content;
}

/**
 * Susun konten demo panjang dari beberapa paragraf dan poin informasi.
 *
 * @param string[] $paragraphs Paragraf konten.
 * @param string[] $points     Daftar poin.
 * @return string
 */
function sakolaan_demo_rich_content( $paragraphs, $points = array() ) {
	$content = '';

	foreach ( $paragraphs as $paragraph ) {
		$content .= '<!-- wp:paragraph --><p>' . esc_html( $paragraph ) . '</p><!-- /wp:paragraph -->';
	}

	if ( ! empty( $points ) ) {
		$content .= '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">' . esc_html__( 'Informasi utama', 'sakolaan' ) . '</h2><!-- /wp:heading -->';
		$content .= '<!-- wp:list --><ul class="wp-block-list">';

		foreach ( $points as $point ) {
			$content .= '<!-- wp:list-item --><li>' . esc_html( $point ) . '</li><!-- /wp:list-item -->';
		}

		$content .= '</ul><!-- /wp:list -->';
	}

	return $content;
}

/**
 * Perbarui isi halaman Kontak demo lama yang mengulang telepon dan email.
 *
 * Migrasi hanya menyentuh halaman bertanda demo dengan dua marker konten lama.
 * Halaman yang telah diedit pengguna atau bukan bagian dataset demo tidak diubah.
 *
 * @return void
 */
function sakolaan_demo_migrate_contact_page() {
	$migration_key = 'sakolaan_demo_contact_content_version';

	if ( '2' === get_option( $migration_key ) ) {
		return;
	}

	$page = get_page_by_path( 'kontak', OBJECT, 'page' );

	if ( ! $page || ! get_post_meta( $page->ID, '_sakolaan_demo_data', true ) ) {
		update_option( $migration_key, '2', false );
		return;
	}

	$has_legacy_phone = false !== strpos( $page->post_content, 'Telepon: 0800-0000-0000' );
	$has_legacy_email = false !== strpos( $page->post_content, 'Email: info@example.test' );

	if ( ! $has_legacy_phone || ! $has_legacy_email ) {
		update_option( $migration_key, '2', false );
		return;
	}

	$content = sakolaan_demo_block_content(
		__( 'Tim layanan informasi siap membantu kebutuhan siswa, orang tua, dan masyarakat.', 'sakolaan' ),
		array(
			__( 'Informasi akademik dan kesiswaan', 'sakolaan' ),
			__( 'Informasi penerimaan peserta didik baru', 'sakolaan' ),
			__( 'Konfirmasi kunjungan ke madrasah', 'sakolaan' ),
		),
		__( 'Seluruh isi halaman ini adalah data demo dan dapat diedit melalui WordPress.', 'sakolaan' )
	);

	$result = wp_update_post(
		wp_slash(
			array(
				'ID'           => $page->ID,
				'post_content' => $content,
			)
		),
		true
	);

	if ( ! is_wp_error( $result ) ) {
		update_option( $migration_key, '2', false );
	}
}
add_action( 'init', 'sakolaan_demo_migrate_contact_page', 30 );

/**
 * Insert satu post demo lengkap dengan meta, taxonomy, dan featured image.
 *
 * @param array $definition Definisi post.
 * @param array $manifest   Manifest seed.
 * @param array $report     Laporan operasi.
 * @return int
 */
function sakolaan_demo_insert_post( $definition, &$manifest, &$report ) {
	$postarr = array(
		'post_type'    => $definition['post_type'],
		'post_status'  => 'publish',
		'post_title'   => $definition['title'],
		'post_name'    => $definition['slug'],
		'post_content' => $definition['content'],
		'post_excerpt' => isset( $definition['excerpt'] ) ? $definition['excerpt'] : '',
		'menu_order'   => isset( $definition['menu_order'] ) ? (int) $definition['menu_order'] : 0,
	);

	if ( ! empty( $definition['date'] ) ) {
		$postarr['post_date']     = $definition['date'];
		$postarr['post_date_gmt'] = get_gmt_from_date( $definition['date'] );
	}

	$post_id = wp_insert_post( wp_slash( $postarr ), true );

	if ( is_wp_error( $post_id ) ) {
		sakolaan_demo_add_error( $report, $post_id->get_error_message() );
		return 0;
	}

	update_post_meta( $post_id, '_sakolaan_demo_data', SAKOLAAN_DEMO_VERSION );

	foreach ( isset( $definition['meta'] ) ? $definition['meta'] : array() as $key => $value ) {
		update_post_meta( $post_id, '_sakolaan_' . sanitize_key( $key ), $value );
	}

	if ( ! empty( $definition['thumbnail'] ) ) {
		set_post_thumbnail( $post_id, (int) $definition['thumbnail'] );
	}

	foreach ( isset( $definition['terms'] ) ? $definition['terms'] : array() as $taxonomy => $terms ) {
		$result = wp_set_object_terms( $post_id, $terms, $taxonomy, false );

		if ( is_wp_error( $result ) ) {
			sakolaan_demo_add_error( $report, $result->get_error_message() );
		}
	}

	$manifest['posts'][ $definition['post_type'] ][] = (int) $post_id;

	return (int) $post_id;
}

/**
 * Tanggal post relatif terhadap waktu situs.
 *
 * @param int $days_ago Hari sebelum hari ini.
 * @param int $hour     Jam lokal.
 * @return string
 */
function sakolaan_demo_post_date( $days_ago, $hour = 8 ) {
	$timestamp = current_time( 'timestamp' ) - ( absint( $days_ago ) * DAY_IN_SECONDS );
	return wp_date( 'Y-m-d', $timestamp ) . sprintf( ' %02d:00:00', $hour );
}

/**
 * Tanggal Y-m-d relatif terhadap hari ini.
 *
 * @param int $offset_days Selisih hari, boleh negatif.
 * @return string
 */
function sakolaan_demo_ymd( $offset_days ) {
	return wp_date( 'Y-m-d', current_time( 'timestamp' ) + ( (int) $offset_days * DAY_IN_SECONDS ) );
}

/**
 * Seed seluruh CPT beserta variasi meta dan taxonomy.
 *
 * @param array $media    Peta media.
 * @param string $school_name Nama sekolah saat installer dijalankan.
 * @param array $manifest Manifest seed.
 * @param array $report   Laporan operasi.
 * @return void
 */
function sakolaan_demo_seed_posts( $media, $school_name, &$manifest, &$report ) {
	$common_closing = __( 'Konten ini adalah data demo untuk pengujian tampilan dan harus diganti sebelum situs dipublikasikan.', 'sakolaan' );
	$teachers = array(
		array( 'Siti Rahmawati, S.Pd.', 'Kepala Madrasah', 'Manajemen Pendidikan', 'S2 Manajemen Pendidikan', 'Guru' ),
		array( 'Ahmad Fauzi, S.Pd.', 'Wakil Kepala Bidang Kurikulum', 'Matematika', 'S1 Pendidikan Matematika', 'Guru' ),
		array( 'Nur Aini, S.Ag.', 'Guru', 'Al-Qur’an Hadis', 'S1 Pendidikan Agama Islam', 'Guru' ),
		array( 'Muhammad Rizal, S.Pd.', 'Guru', 'Ilmu Pengetahuan Alam', 'S1 Pendidikan Biologi', 'Guru' ),
		array( 'Laila Fitriani, S.Pd.', 'Guru', 'Bahasa Indonesia', 'S1 Pendidikan Bahasa Indonesia', 'Guru' ),
		array( 'Fajar Hidayat, S.Pd.', 'Guru', 'Pendidikan Jasmani', 'S1 Pendidikan Olahraga', 'Guru' ),
		array( 'Dewi Kartika', 'Kepala Tata Usaha', 'Administrasi', 'D3 Administrasi Perkantoran', 'Tenaga Kependidikan' ),
		array( 'Roni Saputra', 'Operator Madrasah', 'Teknologi Informasi', 'S1 Sistem Informasi', 'Tenaga Kependidikan' ),
	);

	foreach ( $teachers as $index => $teacher ) {
		sakolaan_demo_insert_post(
			array(
				'post_type'  => 'guru',
				'title'      => $teacher[0],
				'slug'       => 'demo-guru-' . ( $index + 1 ),
				/* translators: %s: teacher name. */
				'content'    => sakolaan_demo_block_content( sprintf( __( '%s adalah profil pendidik dan tenaga kependidikan dalam dataset demo.', 'sakolaan' ), $teacher[0] ), array( __( 'Mendampingi siswa dengan pendekatan aktif dan ramah.', 'sakolaan' ), __( 'Berpartisipasi dalam program pengembangan mutu madrasah.', 'sakolaan' ) ), $common_closing ),
				'menu_order' => $index + 1,
				'thumbnail'  => 0 === $index ? $media['principal'] : ( 0 === $index % 2 ? $media['science'] : $media['hero'] ),
				'meta'       => array(
					'nip'        => '198' . sprintf( '%015d', $index + 1 ),
					'nuptk'      => '123456789' . sprintf( '%07d', $index + 1 ),
					'jabatan'    => $teacher[1],
					'mapel'      => $teacher[2],
					'pendidikan' => $teacher[3],
					'email'      => 'guru' . ( $index + 1 ) . '@example.test',
					'telepon'    => '0800-0000-' . sprintf( '%04d', $index + 1 ),
				),
				'terms'      => array( 'kelompok_gtk' => array( $teacher[4] ) ),
			),
			$manifest,
			$report
		);
	}

	$news = array(
		array( 'Gerakan Literasi Pagi Menumbuhkan Kebiasaan Membaca', 'demo-gerakan-literasi-pagi', 'Kegiatan membaca sebelum pelajaran membantu siswa membangun fokus, rasa ingin tahu, dan kebiasaan belajar mandiri.', array( $school_name . ' membiasakan siswa membaca selama lima belas menit sebelum pembelajaran dimulai. Kegiatan sederhana ini memberi ruang bagi siswa untuk memilih bacaan yang sesuai dengan minatnya.', 'Guru mendampingi kegiatan dengan memberikan rekomendasi buku, mengajak siswa mencatat gagasan penting, dan membuka percakapan singkat mengenai isi bacaan.', 'Program literasi menggunakan cerita, biografi, pengetahuan populer, dan karya sastra agar pengalaman membaca terasa dekat dengan kehidupan siswa.', 'Secara berkala, siswa membagikan ringkasan bacaan sehingga kemampuan berbicara, menyimak, dan menghargai pendapat turut berkembang.' ), array( 'Membaca lima belas menit sebelum pelajaran', 'Mencatat kosakata atau gagasan baru', 'Berbagi rekomendasi bacaan dengan teman' ), 'Akademik', array( 'Literasi', 'Siswa' ), 'article_literacy' ),
		array( 'Praktikum IPA Mengajak Siswa Belajar melalui Pengamatan', 'demo-praktikum-ipa', 'Siswa menguji konsep IPA melalui eksperimen sederhana, pengamatan, pencatatan data, dan diskusi kelompok.', array( 'Pembelajaran IPA di ' . $school_name . ' dilengkapi dengan praktikum agar siswa memahami hubungan antara konsep dan gejala yang diamati secara langsung.', 'Dalam kelompok kecil, siswa menyiapkan alat, mengikuti prosedur keselamatan, mencatat perubahan, lalu membandingkan hasil pengamatan dengan dugaan awal.', 'Guru membantu siswa menyusun pertanyaan, membaca data, dan memperbaiki langkah kerja ketika ditemukan hasil yang berbeda.', 'Setiap kelompok mempresentasikan kesimpulan dan menerima masukan untuk melatih ketelitian, kerja sama, serta keberanian menyampaikan alasan ilmiah.' ), array( 'Mengikuti prosedur keselamatan laboratorium', 'Mencatat hasil secara sistematis', 'Menyampaikan kesimpulan berdasarkan data' ), 'Akademik', array( 'Sains', 'Pembelajaran' ), 'article_science' ),
		array( 'Muhadharah Melatih Kepercayaan Diri dan Kemampuan Berbahasa', 'demo-kegiatan-muhadharah', 'Program muhadharah menjadi ruang latihan berbicara, menyusun gagasan, dan menyampaikan pesan dengan santun.', array( 'Muhadharah di ' . $school_name . ' dilaksanakan sebagai latihan berbicara di depan umum dengan tema keagamaan, pendidikan, dan kehidupan remaja.', 'Siswa bergiliran menjadi pembawa acara, penceramah, pembaca ayat, dan pemberi tanggapan dengan persiapan bersama pembina.', 'Latihan dilakukan bertahap agar siswa mampu mengatur suara, kontak mata, struktur materi, serta pilihan bahasa yang mudah dipahami.', 'Melalui evaluasi yang ramah, siswa belajar menerima masukan dan memperbaiki penampilannya tanpa kehilangan keberanian untuk mencoba.' ), array( 'Latihan pidato Bahasa Indonesia dan Bahasa Arab', 'Pembiasaan adab berbicara dan menyimak', 'Evaluasi bersama guru pembina' ), 'Kegiatan Siswa', array( 'Karakter', 'Bahasa' ), 'article_muhadharah' ),
		array( 'Latihan Kepemimpinan OSIM Membangun Budaya Kolaborasi', 'demo-latihan-kepemimpinan-osim', 'Pengurus OSIM belajar merencanakan program, membagi peran, menyelesaikan masalah, dan melayani warga madrasah.', array( 'Pengurus Organisasi Siswa Intra Madrasah ' . $school_name . ' mengikuti latihan kepemimpinan sebagai bekal menjalankan program kesiswaan selama satu periode.', 'Materi mencakup komunikasi, manajemen waktu, penyusunan kegiatan, pengelolaan rapat, dan pengambilan keputusan yang bertanggung jawab.', 'Melalui simulasi kelompok, siswa belajar membagi tugas berdasarkan kemampuan anggota serta mencari jalan keluar ketika rencana menghadapi kendala.', 'Kegiatan ditutup dengan komitmen kerja yang menekankan keteladanan, keterbukaan, dan pelayanan kepada seluruh siswa.' ), array( 'Komunikasi dan kerja tim', 'Perencanaan program yang realistis', 'Kepemimpinan yang melayani' ), 'Kesiswaan', array( 'OSIM', 'Kepemimpinan' ), 'article_osim' ),
		array( 'Bakti Sosial Menguatkan Kepedulian terhadap Sesama', 'demo-bakti-sosial-siswa', 'Siswa belajar bergotong royong menyiapkan bantuan dan memahami pentingnya kepedulian sosial.', array( 'Siswa dan guru ' . $school_name . ' bergotong royong menyiapkan paket kebutuhan pokok untuk kegiatan bakti sosial di lingkungan sekitar madrasah.', 'Panitia siswa mendata kebutuhan, mengelompokkan barang, serta memastikan paket tersusun rapi dan layak dibagikan.', 'Guru pendamping mengajak siswa memaknai kegiatan sebagai proses belajar tentang empati, amanah, dan cara membantu dengan menjaga martabat penerima.', 'Kegiatan dievaluasi agar program berikutnya semakin tepat sasaran dan melibatkan lebih banyak warga madrasah.' ), array( 'Pengumpulan bantuan secara sukarela', 'Pengemasan dilakukan bersama', 'Penyaluran didampingi guru' ), 'Kegiatan Siswa', array( 'Sosial', 'Karakter' ), 'article_social' ),
		array( 'Class Meeting Menjadi Ruang Sportivitas dan Kebersamaan', 'demo-class-meeting', 'Beragam pertandingan antarkelas mengisi akhir semester dengan suasana sehat, sportif, dan penuh kebersamaan.', array( 'Class meeting ' . $school_name . ' dilaksanakan setelah rangkaian penilaian semester sebagai ruang penyegaran sekaligus penguatan kebersamaan antarkelas.', 'Cabang kegiatan dipilih melalui musyawarah siswa dan guru, meliputi olahraga, permainan kerja sama, kebersihan kelas, dan penampilan kreatif.', 'Setiap peserta diingatkan untuk mematuhi aturan, menghormati lawan, menjaga keselamatan, dan menerima hasil secara dewasa.', 'Panitia siswa turut belajar menyusun jadwal, mencatat hasil, mengelola perlengkapan, dan menyelesaikan kendala selama kegiatan.' ), array( 'Pertandingan yang aman dan sportif', 'Panitia dikelola bersama siswa', 'Apresiasi untuk partisipasi setiap kelas' ), 'Kegiatan Siswa', array( 'Olahraga', 'Kebersamaan' ), 'article_classmeeting' ),
		array( 'Pembelajaran Digital yang Aman, Kreatif, dan Bertanggung Jawab', 'demo-pembelajaran-digital', 'Laboratorium komputer dimanfaatkan untuk melatih keterampilan digital sekaligus etika menggunakan teknologi.', array( 'Pembelajaran digital di ' . $school_name . ' membantu siswa menggunakan perangkat dan sumber informasi untuk mendukung tugas akademik secara efektif.', 'Siswa berlatih mengelola dokumen, menyajikan data, membuat presentasi, serta menilai apakah sebuah sumber daring dapat dipercaya.', 'Guru menekankan keamanan kata sandi, perlindungan data pribadi, hak cipta, dan adab berkomunikasi di ruang digital.', 'Tugas dirancang kolaboratif agar teknologi menjadi sarana berkarya dan memecahkan masalah, bukan sekadar alat konsumsi.' ), array( 'Literasi informasi dan verifikasi sumber', 'Perlindungan data pribadi', 'Karya digital yang menghargai hak cipta' ), 'Akademik', array( 'Teknologi', 'Literasi' ), 'article_digital' ),
		array( 'Kegiatan Keagamaan Membentuk Kebiasaan Baik Siswa', 'demo-kegiatan-keagamaan', 'Pembiasaan ibadah dan kajian singkat menjadi bagian dari penguatan karakter serta budaya madrasah.', array( 'Kegiatan keagamaan di ' . $school_name . ' dirancang sebagai pembiasaan yang menyatu dengan kehidupan belajar siswa setiap hari.', 'Tadarus, salat berjamaah, doa bersama, dan kajian singkat dilaksanakan dengan pendampingan guru agar siswa memahami maknanya.', 'Siswa memperoleh kesempatan memimpin doa, membaca ayat, dan menyampaikan pesan kebaikan sesuai kemampuan masing-masing.', 'Pembiasaan ini diharapkan menumbuhkan disiplin, ketenangan, tanggung jawab, serta sikap saling menghormati di lingkungan madrasah.' ), array( 'Tadarus dan doa bersama', 'Salat berjamaah secara tertib', 'Kajian singkat yang dekat dengan kehidupan siswa' ), 'Keagamaan', array( 'Ibadah', 'Karakter' ), 'article_religious' ),
	);

	foreach ( $news as $index => $item ) {
		sakolaan_demo_insert_post(
			array(
				'post_type' => 'post',
				'title'     => $item[0],
				'slug'      => $item[1],
				'date'      => sakolaan_demo_post_date( 1 + ( $index * 4 ), 8 + ( $index % 4 ) ),
				'excerpt'   => $item[2],
				'content'   => sakolaan_demo_rich_content( $item[3], $item[4] ),
				'thumbnail' => $media[ $item[7] ],
				'terms'     => array( 'category' => array( $item[5] ), 'post_tag' => $item[6] ),
			),
			$manifest,
			$report
		);
	}

	$announcements = array(
		array( 'Jadwal Penilaian Tengah Semester', 'aktif', 3, true ),
		array( 'Pembagian Rapor Semester Ganjil', 'aktif', 7, true ),
		array( 'Libur Kegiatan Belajar Mengajar', 'aktif', 14, false ),
		array( 'Pertemuan Wali Murid Kelas IX', 'aktif', 21, false ),
		array( 'Arsip Jadwal Daftar Ulang', 'arsip', -30, false ),
		array( 'Arsip Pengambilan Kartu Pelajar', 'arsip', -60, false ),
	);

	foreach ( $announcements as $index => $item ) {
		sakolaan_demo_insert_post(
			array(
				'post_type' => 'pengumuman',
				'title'     => $item[0],
				'slug'      => 'demo-pengumuman-' . ( $index + 1 ),
				'content'   => sakolaan_demo_block_content( __( 'Pengumuman ini disediakan sebagai contoh untuk menguji status aktif, arsip, dan unduhan lampiran.', 'sakolaan' ), array( __( 'Perhatikan tanggal pelaksanaan.', 'sakolaan' ), __( 'Hubungi tata usaha untuk informasi tambahan.', 'sakolaan' ) ), $common_closing ),
				'meta'      => array(
					'tanggal'     => sakolaan_demo_ymd( $item[2] ),
					'status'      => $item[1],
					'lampiran_id' => $item[3] ? $media['attachment'] : 0,
				),
			),
			$manifest,
			$report
		);
	}

	$events = array(
		array( 'Rapat Wali Murid Kelas IX', 'demo-agenda-rapat-wali-murid', 3, 3, '08:00', '10:30', 'Aula Madrasah', 'otomatis', 'Pertemuan membahas pendampingan belajar, persiapan asesmen, dan komunikasi antara wali kelas dengan orang tua.', 'event_parents' ),
		array( 'Simulasi Asesmen Madrasah', 'demo-agenda-simulasi-asesmen', 7, 7, '08:00', '11:30', 'Laboratorium Komputer', 'otomatis', 'Simulasi membantu siswa mengenali alur asesmen berbasis komputer dan membangun kesiapan teknis sebelum pelaksanaan utama.', 'event_assessment' ),
		array( 'Pekan Olahraga dan Seni', 'demo-agenda-pekan-olahraga-seni', 14, 16, '07:30', '15:00', 'Lapangan dan Aula Madrasah', 'otomatis', 'Kegiatan tiga hari yang mempertemukan cabang olahraga, seni, dan kreativitas siswa dalam suasana sportif.', 'event_porseni' ),
		array( 'Workshop Penguatan Pembelajaran bagi Guru', 'demo-agenda-workshop-guru', 21, 21, '09:00', '14:30', 'Ruang Pertemuan', 'ditunda', 'Workshop kolaboratif bagi guru untuk menyusun pembelajaran aktif dan asesmen yang memberi umpan balik bermakna.', 'event_workshop' ),
		array( 'Kunjungan Edukatif ke Museum', 'demo-agenda-kunjungan-edukatif', 30, 30, '07:00', '16:00', 'Museum Daerah', 'otomatis', 'Kunjungan lapangan mengajak siswa menghubungkan materi sejarah dan budaya dengan koleksi serta cerita lokal.', 'event_museum' ),
		array( 'Perkemahan Pramuka Madrasah', 'demo-agenda-kemah-pramuka', 45, 46, '07:00', '15:00', 'Bumi Perkemahan Madrasah', 'otomatis', 'Perkemahan dua hari melatih kemandirian, kedisiplinan, kepemimpinan, dan kerja sama regu dengan pengawasan pembina.', 'event_camp' ),
	);

	foreach ( $events as $index => $item ) {
		sakolaan_demo_insert_post(
			array(
				'post_type' => 'agenda',
				'title'     => $item[0],
				'slug'      => $item[1],
				'excerpt'   => $item[8],
				'content'   => sakolaan_demo_rich_content( array( $item[8], 'Agenda ini diselenggarakan oleh ' . $school_name . ' dengan pendampingan guru dan panitia sesuai bidang kegiatan.', 'Peserta diharapkan hadir tepat waktu, membawa perlengkapan yang diperlukan, dan mengikuti arahan panitia selama kegiatan.', 'Apabila terdapat perubahan jadwal, informasi terbaru akan disampaikan melalui kanal komunikasi resmi madrasah.' ), array( 'Mencatat tanggal dan waktu kegiatan', 'Menyiapkan perlengkapan pribadi', 'Menghubungi panitia bila memerlukan informasi tambahan' ) ),
				'thumbnail' => $media[ $item[9] ],
				'meta'      => array(
					'tanggal_mulai'   => sakolaan_demo_ymd( $item[2] ),
					'tanggal_selesai' => sakolaan_demo_ymd( $item[3] ),
					'waktu_mulai'     => $item[4],
					'waktu_selesai'   => $item[5],
					'lokasi'          => $item[6],
					'status'          => $item[7],
				),
			),
			$manifest,
			$report
		);
	}

	$achievements = array(
		array( 'Juara Olimpiade Matematika Tingkat Kabupaten', 'demo-prestasi-olimpiade-matematika', 'Alya Rahmani', 'Akademik', 'Kabupaten/Kota', 'Prestasi diraih melalui latihan rutin, pembahasan soal bertingkat, dan pendampingan guru matematika.', 'achievement_math' ),
		array( 'Juara Lomba Pidato Bahasa Arab Tingkat Provinsi', 'demo-prestasi-pidato-bahasa-arab', 'Muhammad Fikri', 'Akademik', 'Provinsi', 'Kemampuan menyusun naskah, pelafalan, intonasi, dan kepercayaan diri menjadi kekuatan utama dalam perlombaan.', 'achievement_speech' ),
		array( 'Finalis Kompetisi Sains Madrasah Tingkat Nasional', 'demo-prestasi-kompetisi-sains', 'Tim Sains Madrasah', 'Akademik', 'Nasional', 'Tim memadukan pengamatan, eksperimen, dan presentasi ilmiah untuk menyelesaikan tantangan kompetisi.', 'achievement_science' ),
		array( 'Juara Turnamen Bulu Tangkis Pelajar', 'demo-prestasi-bulu-tangkis', 'Tim Bulu Tangkis Putra', 'Olahraga', 'Kabupaten/Kota', 'Kedisiplinan latihan, komunikasi pasangan, dan sportivitas mengantarkan tim meraih hasil terbaik.', 'achievement_badminton' ),
		array( 'Juara Kaligrafi Pelajar Tingkat Kabupaten', 'demo-prestasi-kaligrafi', 'Nabila Zahra', 'Seni', 'Kabupaten/Kota', 'Karya kaligrafi dinilai dari komposisi, ketelitian goresan, kreativitas, dan keselarasan warna.', 'achievement_calligraphy' ),
		array( 'Penampil Terbaik Festival Hadrah Pelajar', 'demo-prestasi-festival-hadrah', 'Tim Hadrah Madrasah', 'Seni', 'Provinsi', 'Kekompakan irama, penguasaan panggung, dan latihan terjadwal menjadi dasar penampilan tim.', 'achievement_hadrah' ),
	);

	foreach ( $achievements as $index => $item ) {
		sakolaan_demo_insert_post(
			array(
				'post_type'  => 'prestasi',
				'title'      => $item[0],
				'slug'       => $item[1],
				'excerpt'    => $item[5],
				'content'    => sakolaan_demo_rich_content( array( $item[5], 'Pencapaian ini menjadi bagian dari proses pengembangan potensi siswa di ' . $school_name . ', baik melalui pembelajaran maupun kegiatan pembinaan.', 'Guru pembina mendampingi persiapan secara bertahap, mulai dari pemetaan kemampuan, latihan, evaluasi, hingga kesiapan mengikuti perlombaan.', 'Madrasah menyampaikan apresiasi kepada siswa, pembina, dan orang tua yang mendukung proses tersebut dengan penuh tanggung jawab.' ), array( 'Pembinaan dilakukan secara terjadwal', 'Proses dan sportivitas tetap menjadi prioritas', 'Prestasi menjadi motivasi untuk terus belajar' ) ),
				'menu_order' => ( $index + 1 ) * 10,
				'thumbnail'  => $media[ $item[6] ],
				'meta'       => array( 'tahun' => wp_date( 'Y' ), 'peraih' => $item[2] ),
				'terms'      => array( 'kategori_prestasi' => array( $item[3] ), 'tingkat_prestasi' => array( $item[4] ) ),
			),
			$manifest,
			$report
		);
	}

	$clubs = array(
		array( 'Pramuka', 'demo-ekstrakurikuler-pramuka', 'Ahmad Fauzan, S.Pd.', 'Sabtu, 14.00–16.00', 'Lapangan Madrasah', 'Kegiatan Pramuka mengembangkan kemandirian, kedisiplinan, kepemimpinan, dan kemampuan bekerja dalam regu.', 'club_scouting' ),
		array( 'Tahfiz Al-Qur’an', 'demo-ekstrakurikuler-tahfiz', 'Nur Aini, S.Ag.', 'Senin dan Rabu, 14.00–15.30', 'Musala Madrasah', 'Program Tahfiz membantu siswa membangun kebiasaan menghafal, memperbaiki bacaan, dan menjaga murajaah secara bertahap.', 'club_tahfiz' ),
		array( 'Seni Hadrah', 'demo-ekstrakurikuler-hadrah', 'Hasyim Asyari, S.Pd.I.', 'Sabtu, 13.00–15.00', 'Aula Madrasah', 'Seni Hadrah memadukan keterampilan ritmis, kekompakan kelompok, rasa percaya diri, dan kecintaan terhadap seni Islami.', 'club_hadrah' ),
		array( 'Paskibra', 'demo-ekstrakurikuler-paskibra', 'Dewi Lestari, S.Pd.', 'Selasa dan Kamis, 15.00–16.30', 'Lapangan Upacara', 'Paskibra melatih ketepatan gerak, konsentrasi, disiplin waktu, kebugaran, dan tanggung jawab sebagai satu tim.', 'club_paskibra' ),
		array( 'Futsal', 'demo-ekstrakurikuler-futsal', 'Rizky Pratama, S.Or.', 'Jumat, 14.00–16.00', 'Lapangan Olahraga', 'Latihan Futsal mengembangkan teknik dasar, kebugaran, strategi sederhana, komunikasi, dan sportivitas siswa.', 'club_futsal' ),
		array( 'Klub Sains', 'demo-ekstrakurikuler-klub-sains', 'Laila Fitriani, S.Pd.', 'Kamis, 14.00–16.00', 'Laboratorium IPA', 'Klub Sains menjadi ruang bagi siswa untuk bereksperimen, merancang proyek, dan memecahkan masalah secara kreatif.', 'club_science' ),
	);

	foreach ( $clubs as $index => $item ) {
		sakolaan_demo_insert_post(
			array(
				'post_type'  => 'ekstrakurikuler',
				'title'      => $item[0],
				'slug'       => $item[1],
				'excerpt'    => $item[5],
				'content'    => sakolaan_demo_rich_content( array( $item[5], 'Kegiatan terbuka bagi siswa ' . $school_name . ' yang berminat dan siap mengikuti jadwal latihan secara konsisten.', 'Pembina menyusun latihan dari tingkat dasar menuju keterampilan lanjutan serta memastikan kegiatan berlangsung aman dan sesuai usia siswa.', 'Perkembangan anggota diamati melalui kehadiran, kerja sama, penguasaan keterampilan, dan keberanian berpartisipasi dalam kegiatan sekolah.' ), array( 'Didampingi pembina yang berpengalaman', 'Mengutamakan keamanan dan kerja sama', 'Terbuka untuk pengembangan kemampuan bertahap' ) ),
				'menu_order' => $index + 1,
				'thumbnail'  => $media[ $item[6] ],
				'meta'       => array( 'pembina' => $item[2], 'jadwal' => $item[3], 'lokasi' => $item[4] ),
			),
			$manifest,
			$report
		);
	}

	$gallery_media = array_values( array_filter( array( $media['hero'], $media['science'], $media['scouting'], $media['achievement'], $media['principal'] ) ) );

	if ( empty( $gallery_media ) ) {
		$gallery_media = array( 0 );
	}

	$galleries     = array(
		array( 'Suasana Pembelajaran di Madrasah', 'Pembelajaran', 5 ),
		array( 'Kegiatan Pramuka dan Kerja Sama Siswa', 'Ekstrakurikuler', 4 ),
		array( 'Apresiasi Siswa Berprestasi', 'Prestasi Siswa', 4 ),
		array( 'Kegiatan Bersama Warga Madrasah', 'Kegiatan Sekolah', 5 ),
	);

	foreach ( $galleries as $index => $item ) {
		$images = array();

		for ( $i = 0; $i < $item[2]; $i++ ) {
			$images[] = $gallery_media[ ( $index + $i ) % count( $gallery_media ) ];
		}

		sakolaan_demo_insert_post(
			array(
				'post_type'  => 'galeri',
				'title'      => $item[0],
				'slug'       => 'demo-galeri-' . ( $index + 1 ),
				'content'    => sakolaan_demo_block_content( __( 'Album ini berisi media demo untuk menguji grid galeri dan lightbox.', 'sakolaan' ), array(), $common_closing ),
				'menu_order' => ( $index + 1 ) * 10,
				'thumbnail'  => reset( $images ),
				'meta'       => array( 'tanggal' => sakolaan_demo_ymd( -7 * $index ), 'gambar_ids' => $images ),
				'terms'      => array( 'album_galeri' => array( $item[1] ) ),
			),
			$manifest,
			$report
		);
	}

	$facilities = array(
		array( 'Laboratorium IPA', 'Gedung Akademik Lantai 1', 'science' ),
		array( 'Laboratorium Komputer', 'Gedung Akademik Lantai 2', 'science' ),
		array( 'Perpustakaan dan Ruang Literasi', 'Gedung Utama', 'hero' ),
		array( 'Musala Madrasah', 'Area Tengah Kampus', 'principal' ),
		array( 'Lapangan Serbaguna', 'Area Timur Kampus', 'scouting' ),
		array( 'Aula Pertemuan', 'Gedung Utama Lantai 2', 'achievement' ),
	);

	foreach ( $facilities as $index => $item ) {
		sakolaan_demo_insert_post(
			array(
				'post_type'  => 'fasilitas',
				'title'      => $item[0],
				'slug'       => 'demo-fasilitas-' . ( $index + 1 ),
				'excerpt'    => __( 'Fasilitas demo yang mendukung kegiatan belajar, pengembangan diri, dan kenyamanan warga madrasah.', 'sakolaan' ),
				/* translators: %s: facility name. */
				'content'    => sakolaan_demo_block_content( sprintf( __( '%s digunakan untuk mendukung pengalaman belajar siswa yang aman dan aktif.', 'sakolaan' ), $item[0] ), array( __( 'Dikelola dengan jadwal pemeliharaan berkala.', 'sakolaan' ), __( 'Pemakaian didampingi guru atau petugas.', 'sakolaan' ) ), $common_closing ),
				'menu_order' => ( $index + 1 ) * 10,
				'thumbnail'  => $media[ $item[2] ],
				'meta'       => array( 'lokasi' => $item[1] ),
			),
			$manifest,
			$report
		);
	}

	$programs = array(
		array( 'Tahfiz dan Penguatan Karakter', 'Seluruh siswa', 'Sepanjang tahun', 'principal' ),
		array( 'Kelas Sains Terapan', 'Siswa kelas VII–IX', 'Dua pertemuan per pekan', 'science' ),
		array( 'Literasi Digital dan Jurnalistik', 'Siswa kelas VIII–IX', 'Satu semester', 'hero' ),
		array( 'Pembinaan Prestasi dan Olimpiade', 'Siswa terpilih', 'Menjelang kompetisi', 'achievement' ),
	);

	foreach ( $programs as $index => $item ) {
		sakolaan_demo_insert_post(
			array(
				'post_type'  => 'program_unggulan',
				'title'      => $item[0],
				'slug'       => 'demo-program-unggulan-' . ( $index + 1 ),
				'excerpt'    => __( 'Program terarah yang memadukan pendampingan, praktik, dan evaluasi perkembangan siswa.', 'sakolaan' ),
				/* translators: %s: featured program name. */
				'content'    => sakolaan_demo_block_content( sprintf( __( '%s dirancang sebagai program unggulan yang relevan dengan kebutuhan siswa.', 'sakolaan' ), $item[0] ), array( __( 'Target pembelajaran disusun secara bertahap.', 'sakolaan' ), __( 'Perkembangan siswa dipantau dan dikomunikasikan.', 'sakolaan' ) ), $common_closing ),
				'menu_order' => ( $index + 1 ) * 10,
				'thumbnail'  => $media[ $item[3] ],
				'meta'       => array( 'sasaran' => $item[1], 'durasi' => $item[2] ),
			),
			$manifest,
			$report
		);
	}

	$testimonials = array(
		array( 'Aisyah Rahman', '2018', 'Mahasiswa Pendidikan Matematika', 'Madrasah membantu saya berani bertanya, bekerja sama, dan terus belajar dengan disiplin.', 'science' ),
		array( 'Rizky Maulana', '2019', 'Wirausaha Muda', 'Pengalaman organisasi dan kegiatan siswa menjadi bekal penting ketika saya mulai bekerja bersama masyarakat.', 'scouting' ),
		array( 'Nabila Hasanah', '2020', 'Mahasiswa Teknologi Informasi', 'Guru-guru memberi ruang untuk mencoba hal baru dan mendampingi kami saat mengalami kesulitan.', 'achievement' ),
		array( 'Fahmi Akbar', '2017', 'Pendamping Komunitas Literasi', 'Kebiasaan membaca dan berdiskusi yang dibangun di madrasah masih saya rasakan manfaatnya sampai sekarang.', 'hero' ),
	);

	foreach ( $testimonials as $index => $item ) {
		sakolaan_demo_insert_post(
			array(
				'post_type'  => 'testimoni',
				'title'      => $item[0],
				'slug'       => 'demo-testimoni-' . ( $index + 1 ),
				'excerpt'    => $item[3],
				'content'    => sakolaan_demo_block_content( $item[3], array( __( 'Testimoni ini merupakan data demo untuk pengujian.', 'sakolaan' ) ), $common_closing ),
				'menu_order' => ( $index + 1 ) * 10,
				'thumbnail'  => $media[ $item[4] ],
				'meta'       => array( 'tahun_lulus' => $item[1], 'aktivitas' => $item[2] ),
			),
			$manifest,
			$report
		);
	}

	$slides = array(
		array( 'Tumbuh dalam Ilmu, Kokoh dalam Karakter', 'Pendidikan bermakna dalam lingkungan madrasah yang ramah dan aktif.', 'Selamat datang', 'hero' ),
		array( 'Ruang Belajar untuk Mencoba dan Berkarya', 'Sains, literasi, teknologi, dan kreativitas tumbuh melalui pengalaman langsung.', 'Belajar aktif', 'science' ),
		array( 'Temukan Potensi Bersama Komunitas Sekolah', 'Program akademik dan pengembangan diri mendampingi setiap langkah siswa.', 'Bertumbuh bersama', 'scouting' ),
	);

	foreach ( $slides as $index => $item ) {
		sakolaan_demo_insert_post(
			array(
				'post_type'  => 'hero_slide',
				'title'      => $item[0],
				'slug'       => 'demo-hero-slide-' . ( $index + 1 ),
				'excerpt'    => $item[1],
				'content'    => '',
				'menu_order' => ( $index + 1 ) * 10,
				'thumbnail'  => $media[ $item[3] ],
				'meta'       => array(
					'eyebrow'    => $item[2],
					'cta_label'  => __( 'Jelajahi Profil', 'sakolaan' ),
					'cta_url'    => home_url( '/profil/' ),
					'cta2_label' => __( 'Informasi PPDB', 'sakolaan' ),
					'cta2_url'   => home_url( '/ppdb/' ),
				),
			),
			$manifest,
			$report
		);
	}

	foreach ( sakolaan_demo_post_types() as $post_type ) {
		$report['counts'][ $post_type ] = isset( $manifest['posts'][ $post_type ] ) ? count( $manifest['posts'][ $post_type ] ) : 0;
	}
	$report['counts']['post'] = isset( $manifest['posts']['post'] ) ? count( $manifest['posts']['post'] ) : 0;
}

/**
 * Seed halaman informasi dengan konten Gutenberg.
 *
 * @param string $school_name Nama sekolah saat installer dijalankan.
 * @param array $manifest Manifest seed.
 * @param array $report   Laporan operasi.
 * @return array Peta path ke ID halaman.
 */
function sakolaan_demo_seed_pages( $school_name, &$manifest, &$report ) {
	$pages = array(
		/* translators: %s: school name. */
		'profil'                     => array( 'Profil Madrasah', 0, sprintf( __( '%s hadir sebagai ruang belajar yang menggabungkan ilmu pengetahuan, karakter, dan nilai keislaman.', 'sakolaan' ), $school_name ), array( 'Pembelajaran aktif dan terarah', 'Lingkungan aman dan ramah siswa', 'Kolaborasi bersama orang tua dan masyarakat' ) ),
		'profil/sejarah'             => array( 'Sejarah', 'profil', __( 'Madrasah berkembang dari semangat masyarakat untuk menghadirkan pendidikan menengah Islam yang dekat, bermutu, dan terjangkau.', 'sakolaan' ), array( 'Dirintis melalui gotong royong masyarakat', 'Berkembang bersama kebutuhan peserta didik', 'Terus meningkatkan mutu layanan pendidikan' ) ),
		'profil/visi-misi'           => array( 'Visi & Misi', 'profil', __( 'Visi madrasah adalah membentuk generasi berilmu, berakhlak, mandiri, dan siap berkontribusi bagi lingkungan.', 'sakolaan' ), array( 'Menyelenggarakan pembelajaran yang bermakna', 'Menumbuhkan kebiasaan ibadah dan akhlak mulia', 'Mengembangkan potensi akademik dan nonakademik', 'Membangun budaya literasi dan teknologi' ) ),
		'profil/struktur-organisasi' => array( 'Struktur Organisasi', 'profil', __( 'Pengelolaan madrasah dilakukan secara kolaboratif melalui pembagian peran yang jelas dan layanan yang terukur.', 'sakolaan' ), array( 'Kepala madrasah', 'Wakil kepala bidang kurikulum dan kesiswaan', 'Wali kelas dan guru mata pelajaran', 'Tata usaha dan operator madrasah' ) ),
		'profil/fasilitas'           => array( 'Fasilitas', 'profil', __( 'Fasilitas madrasah mendukung proses belajar, ibadah, kreativitas, dan aktivitas siswa sehari-hari.', 'sakolaan' ), array( 'Ruang kelas', 'Perpustakaan', 'Laboratorium komputer dan IPA', 'Musala', 'Lapangan kegiatan' ) ),
		'profil/akreditasi'          => array( 'Akreditasi', 'profil', __( 'Informasi ini merupakan data demo untuk menampilkan dokumen dan status mutu satuan pendidikan.', 'sakolaan' ), array( 'Status akreditasi: A (Demo)', 'Tahun penilaian: ' . wp_date( 'Y' ), 'Dokumen resmi harus menggantikan data demo sebelum publikasi' ) ),
		'akademik'                   => array( 'Akademik', 0, __( 'Program akademik dirancang untuk mengembangkan pemahaman konsep, kecakapan belajar, dan karakter siswa.', 'sakolaan' ), array( 'Kurikulum nasional dan kekhasan madrasah', 'Pendampingan belajar dan asesmen berkala', 'Program literasi, numerasi, dan sains', 'Pemanfaatan teknologi secara bertanggung jawab' ) ),
		'kesiswaan'                  => array( 'Kesiswaan', 0, __( 'Layanan kesiswaan membantu peserta didik tumbuh melalui organisasi, pembinaan karakter, dan kegiatan pengembangan diri.', 'sakolaan' ), array( 'Organisasi siswa', 'Bimbingan dan konseling', 'Ekstrakurikuler', 'Program kesehatan dan kedisiplinan' ) ),
		'ppdb'                       => array( 'PPDB', 0, __( 'Penerimaan Peserta Didik Baru terbuka bagi calon siswa yang ingin belajar dalam lingkungan madrasah yang aktif dan suportif.', 'sakolaan' ), array( 'Mengisi formulir pendaftaran', 'Menyerahkan dokumen persyaratan', 'Mengikuti verifikasi dan wawancara', 'Melakukan daftar ulang' ) ),
		'kontak'                     => array( 'Kontak', 0, __( 'Tim layanan informasi siap membantu kebutuhan siswa, orang tua, dan masyarakat.', 'sakolaan' ), array( 'Informasi akademik dan kesiswaan', 'Informasi penerimaan peserta didik baru', 'Konfirmasi kunjungan ke madrasah' ) ),
	);
	$ids = array();

	foreach ( $pages as $path => $page ) {
		$parent_id = $page[1] ? ( isset( $ids[ $page[1] ] ) ? $ids[ $page[1] ] : 0 ) : 0;
		$slug      = basename( $path );
		$page_id   = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_title'   => $page[0],
					'post_name'    => $slug,
					'post_parent'  => $parent_id,
					'post_content' => sakolaan_demo_block_content( $page[2], $page[3], __( 'Seluruh isi halaman ini adalah data demo dan dapat diedit melalui WordPress.', 'sakolaan' ) ),
					'meta_input'   => array(
						'_sakolaan_demo_data' => SAKOLAAN_DEMO_VERSION,
						'_wp_page_template'      => 'kontak' === $slug ? 'page-kontak' : 'default',
					),
				)
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			sakolaan_demo_add_error( $report, $page_id->get_error_message() );
			continue;
		}

		$ids[ $path ]         = (int) $page_id;
		$manifest['pages'][]  = (int) $page_id;
	}

	$report['counts']['halaman'] = count( $ids );

	return $ids;
}

/**
 * Hapus halaman seed ekstra yang mungkin dibuat hook theme dalam request admin,
 * lalu kembalikan slug halaman manifest ke bentuk kanonik tanpa akhiran angka.
 *
 * @param array $pages  Peta path ke ID halaman yang dibuat seeder plugin.
 * @param array $report Laporan operasi.
 * @return void
 */
function sakolaan_demo_normalize_pages( $pages, &$report ) {
	$keep_ids   = array_map( 'absint', array_values( $pages ) );
	$page_slugs = array( 'profil', 'sejarah', 'visi-misi', 'struktur-organisasi', 'fasilitas', 'akreditasi', 'akademik', 'kesiswaan', 'ppdb', 'kontak' );
	$all_pages  = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		)
	);
	$deleted = 0;

	foreach ( $all_pages as $page ) {
		$base_slug = preg_replace( '/-\d+$/', '', $page->post_name );

		if ( in_array( $base_slug, $page_slugs, true ) && ! in_array( (int) $page->ID, $keep_ids, true ) ) {
			if ( wp_delete_post( $page->ID, true ) ) {
				++$deleted;
			} else {
				/* translators: %d: duplicate page ID. */
				sakolaan_demo_add_error( $report, sprintf( __( 'Halaman duplikat #%d gagal dibersihkan.', 'sakolaan' ), $page->ID ) );
			}
		}
	}

	foreach ( $pages as $path => $page_id ) {
		$result = wp_update_post(
			array(
				'ID'        => (int) $page_id,
				'post_name' => basename( $path ),
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			sakolaan_demo_add_error( $report, $result->get_error_message() );
		} else {
			clean_post_cache( (int) $page_id );
		}
	}

	$report['counts']['halaman_duplikat_dibersihkan'] = $deleted;
}

/**
 * Isi seluruh Pengaturan Sakolaan dengan data demo.
 *
 * @param array $media  Peta media.
 * @param array $pages  Peta halaman.
 * @param array $identity Identitas situs yang dipertahankan.
 * @param array $report Laporan operasi.
 * @return void
 */
function sakolaan_demo_seed_settings( $media, $pages, $identity, &$report ) {
	$settings = sakolaan_get_settings_defaults();
	$home_url = home_url( '/' );
	$school_name = $identity['name'];

	$settings['identity'] = array(
		'name'          => $school_name,
		'short_name'    => $identity['short_name'],
		'tagline'       => $identity['tagline'],
		'logo'          => $media['logo'],
		'favicon'       => $media['logo'],
		'npsn'          => '00000000',
		'accreditation' => 'A (Data Demo)',
		'founded_year'  => 2006,
	);
	$settings['contact'] = array(
		'address'  => 'Jl. Pendidikan No. 1, Kecamatan Contoh, Kabupaten Contoh (Data Demo)',
		'phone'    => '0800-0000-0000',
		'email'    => 'info@example.test',
		'whatsapp' => '6280000000000',
		'maps_url' => 'https://www.google.com/maps?q=Indonesia&output=embed',
	);
	$settings['social'] = array(
		'facebook'  => 'https://example.com/demo-facebook',
		'instagram' => 'https://example.com/demo-instagram',
		'youtube'   => 'https://example.com/demo-youtube',
		'tiktok'    => 'https://example.com/demo-tiktok',
	);
	$settings['homepage'] = array(
		'hero_mode'        => 'slider',
		'hero_autoplay'    => true,
		'hero_interval'    => 6,
		'hero_title'       => 'Tumbuh dalam ilmu, kokoh dalam karakter',
		'hero_heading_width' => 24,
		'hero_description' => 'Lingkungan belajar yang aktif, ramah, dan berlandaskan nilai keislaman untuk mempersiapkan generasi masa depan.',
		'hero_image'       => $media['hero'],
		'hero_cta_label'   => 'Jelajahi Profil',
		'hero_cta_url'     => isset( $pages['profil'] ) ? get_permalink( $pages['profil'] ) : $home_url,
		'hero_cta2_label'  => 'Informasi PPDB',
		'hero_cta2_url'    => isset( $pages['ppdb'] ) ? get_permalink( $pages['ppdb'] ) : $home_url,
		'intro_image'      => $media['science'],
		'intro_text'       => $school_name . ' mendampingi siswa melalui pembelajaran bermakna, budaya literasi, penguatan karakter, dan beragam kegiatan pengembangan diri.',
		'stat_1_value'     => '320+',
		'stat_1_label'     => 'Siswa aktif',
		'stat_2_value'     => '28',
		'stat_2_label'     => 'Guru dan tendik',
		'stat_3_value'     => '12',
		'stat_3_label'     => 'Rombongan belajar',
		'stat_4_value'     => '8',
		'stat_4_label'     => 'Ekstrakurikuler',
		'stat_5_value'     => 'A',
		'stat_5_label'     => 'Akreditasi demo',
	);
	$settings['sections'] = array(
		'show_quick_links'     => true,
		'show_introduction'    => true,
		'show_principal'       => true,
		'show_statistics'      => true,
		'show_news'            => true,
		'show_announcements'   => true,
		'show_events'          => true,
		'show_achievements'    => true,
		'show_facilities'      => true,
		'show_programs'        => true,
		'show_extracurricular' => true,
		'show_testimonials'    => true,
		'show_gallery'         => true,
		'show_ppdb'            => true,
		'order'                => array_keys( sakolaan_get_homepage_section_labels() ),
	);
	$settings['principal'] = array(
		'name'     => 'Siti Rahmawati, S.Pd.',
		'title'    => 'Kepala ' . $school_name,
		'photo'    => $media['principal'],
		'greeting' => 'Selamat datang di website madrasah. Kami percaya pendidikan terbaik tumbuh dari kerja sama siswa, guru, orang tua, dan masyarakat. Mari membangun lingkungan belajar yang aman, aktif, dan penuh keteladanan.',
	);
	$settings['ppdb'] = array(
		'url'         => isset( $pages['ppdb'] ) ? get_permalink( $pages['ppdb'] ) : $home_url,
		'cta_label'   => 'Lihat Informasi PPDB',
		'description' => 'Kenali program, persyaratan, dan tahapan penerimaan peserta didik baru melalui halaman PPDB.',
	);
	$settings['footer'] = array(
		'description' => 'Madrasah yang mendampingi siswa bertumbuh dalam ilmu, karakter, kreativitas, dan kepedulian sosial.',
		'copyright'   => '© ' . wp_date( 'Y' ) . ' ' . $school_name . '. Data demo untuk pengujian.',
	);
	$settings['advanced'] = array( 'delete_data' => false );

	update_option( 'sakolaan_settings', $settings, false );
	sakolaan_sync_site_identity( $settings );

	if ( function_exists( 'sakolaan_clear_settings_cache' ) ) {
		sakolaan_clear_settings_cache();
	}

	$report['counts']['pengaturan'] = count( $settings );
}

/**
 * Buat navigasi utama memakai helper aktif milik Pijar Sakolaan.
 *
 * @param array $manifest Manifest seed.
 * @param array $report   Laporan operasi.
 * @return void
 */
function sakolaan_demo_seed_navigation( &$manifest, &$report ) {
	$theme_ready = function_exists( 'sakolaan_companion_theme_is_ready' ) && sakolaan_companion_theme_is_ready();
	if ( ! $theme_ready || ! function_exists( 'pijar_sakolaan_build_menu_blocks' ) ) {
		sakolaan_demo_add_error( $report, __( 'Pijar Sakolaan tidak aktif sehingga navigasi utama tidak dibuat.', 'sakolaan' ) );
		return;
	}

	delete_option( 'pijar_sakolaan_menu_seeded' );
	$blocks = pijar_sakolaan_build_menu_blocks();

	if ( empty( $blocks ) ) {
		sakolaan_demo_add_error( $report, __( 'Navigasi utama tidak memiliki link yang dapat dibuat.', 'sakolaan' ) );
		return;
	}

	$menu_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'wp_navigation',
				'post_status'  => 'publish',
				'post_title'   => __( 'Menu utama', 'sakolaan' ),
				'post_content' => serialize_blocks( $blocks ),
			)
		),
		true
	);

	if ( is_wp_error( $menu_id ) ) {
		sakolaan_demo_add_error( $report, $menu_id->get_error_message() );
		return;
	}

	$menu_id = (int) $menu_id;
	update_option( 'pijar_sakolaan_menu_seeded', $menu_id, false );
	update_post_meta( $menu_id, '_sakolaan_demo_data', SAKOLAAN_DEMO_VERSION );
	$manifest['navigation'][]     = $menu_id;
	$report['counts']['navigasi'] = 1;
}

/**
 * Pasang ulang seluruh dataset demo.
 *
 * @return array Laporan operasi.
 */
function sakolaan_demo_install() {
	$theme_ready = function_exists( 'sakolaan_companion_theme_is_ready' ) && sakolaan_companion_theme_is_ready();
	if ( ! $theme_ready || ! function_exists( 'pijar_sakolaan_get_page_tree' ) ) {
		$report = sakolaan_demo_new_report();
		sakolaan_demo_add_error( $report, __( 'Pijar Sakolaan harus aktif sebelum data demo dipasang.', 'sakolaan' ) );
		$report['status']  = 'error';
		$report['message'] = __( 'Instalasi dibatalkan karena Pijar Sakolaan tidak aktif. Tidak ada data yang dihapus.', 'sakolaan' );
		return $report;
	}

	$school_name = trim( wp_strip_all_tags( get_bloginfo( 'name' ) ) );
	if ( '' === $school_name ) {
		$school_name = __( 'Madrasah Kami', 'sakolaan' );
	}

	$existing_settings = get_option( 'sakolaan_settings', array() );
	$existing_identity = isset( $existing_settings['identity'] ) && is_array( $existing_settings['identity'] ) ? $existing_settings['identity'] : array();
	$identity = array(
		'name'       => $school_name,
		'short_name' => ! empty( $existing_identity['short_name'] ) ? sanitize_text_field( $existing_identity['short_name'] ) : $school_name,
		'tagline'    => trim( wp_strip_all_tags( get_bloginfo( 'description' ) ) ),
	);
	if ( '' === $identity['tagline'] ) {
		$identity['tagline'] = __( 'Madrasah berilmu, berakhlak, dan berdaya', 'sakolaan' );
	}

	$media_base = sakolaan_demo_prepare_media_package();
	if ( is_wp_error( $media_base ) ) {
		$report = sakolaan_demo_new_report();
		$report['status'] = 'error';
		$report['message'] = __( 'Instalasi dibatalkan karena paket media demo tidak siap. Tidak ada data yang dihapus.', 'sakolaan' );
		$report['errors'][] = $media_base->get_error_message();
		return $report;
	}

	$report = sakolaan_demo_purge( false );

	$manifest = array(
		'version'     => SAKOLAAN_DEMO_VERSION,
		'created_at'  => current_time( 'mysql' ),
		'posts'       => array(),
		'pages'       => array(),
		'terms'       => array(),
		'attachments' => array(),
		'navigation'  => array(),
	);

	$media = sakolaan_demo_seed_media( $media_base, $school_name, $manifest, $report );
	update_option( 'sakolaan_demo_manifest', $manifest, false );
	sakolaan_demo_seed_terms( $manifest, $report );
	update_option( 'sakolaan_demo_manifest', $manifest, false );
	$pages = sakolaan_demo_seed_pages( $school_name, $manifest, $report );
	update_option( 'sakolaan_demo_manifest', $manifest, false );
	sakolaan_demo_seed_posts( $media, $school_name, $manifest, $report );
	update_option( 'sakolaan_demo_manifest', $manifest, false );
	sakolaan_demo_normalize_pages( $pages, $report );
	sakolaan_demo_seed_settings( $media, $pages, $identity, $report );
	sakolaan_demo_seed_navigation( $manifest, $report );

	update_option( 'sakolaan_demo_manifest', $manifest, false );
	flush_rewrite_rules( false );

	$report['message'] = empty( $report['errors'] )
		? __( 'Dataset demo berhasil dipasang dan siap diuji.', 'sakolaan' )
		: __( 'Dataset demo terpasang dengan beberapa peringatan. Periksa rincian di bawah.', 'sakolaan' );
	$report['links'] = sakolaan_demo_test_links();

	return $report;
}

/**
 * Tautan cepat untuk pengujian frontend.
 *
 * @return array[]
 */
function sakolaan_demo_test_links() {
	$links = array(
		array( 'label' => __( 'Beranda', 'sakolaan' ), 'url' => home_url( '/' ) ),
	);

	foreach ( sakolaan_demo_post_types() as $post_type ) {
		$object = get_post_type_object( $post_type );
		$url    = get_post_type_archive_link( $post_type );

		if ( $object && $url ) {
			$links[] = array( 'label' => $object->labels->name, 'url' => $url );
		}
	}

	$links[] = array( 'label' => __( 'Pencarian demo', 'sakolaan' ), 'url' => add_query_arg( 's', 'demo', home_url( '/' ) ) );
	$links[] = array( 'label' => __( 'Halaman 404', 'sakolaan' ), 'url' => home_url( '/halaman-demo-tidak-ada/' ) );

	return $links;
}
