<?php
/**
 * Seed struktur halaman dan menu utama.
 *
 * Membuat halaman bawaan (Profil dan turunannya, Akademik, Kesiswaan, PPDB, Kontak)
 * saat theme diaktifkan, dan menu navigasi saat Sakolaan aktif. Aman dijalankan
 * berulang: halaman yang sudah ada tidak dibuat ulang, menu hanya dibuat sekali.
 * Semuanya dapat diedit atau dihapus oleh administrator.
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Struktur halaman bawaan. Slug membentuk URL dan tidak diterjemahkan.
 *
 * @return array
 */
function sakolaan_theme_get_page_tree() {
	$tree = array(
		'profil'    => array(
			'title'    => __( 'Profil', 'sakolaan-theme' ),
			'children' => array(
				'sejarah'             => __( 'Sejarah', 'sakolaan-theme' ),
				'visi-misi'           => __( 'Visi & Misi', 'sakolaan-theme' ),
				'struktur-organisasi' => __( 'Struktur Organisasi', 'sakolaan-theme' ),
				'fasilitas'           => __( 'Fasilitas', 'sakolaan-theme' ),
				'akreditasi'          => __( 'Akreditasi', 'sakolaan-theme' ),
			),
		),
		'akademik'  => array( 'title' => __( 'Akademik', 'sakolaan-theme' ) ),
		'kesiswaan' => array( 'title' => __( 'Kesiswaan', 'sakolaan-theme' ) ),
		'ppdb'      => array( 'title' => __( 'PPDB', 'sakolaan-theme' ) ),
		'kontak'    => array( 'title' => __( 'Kontak', 'sakolaan-theme' ) ),
	);

	/**
	 * Filter struktur halaman bawaan.
	 *
	 * @param array $tree Struktur halaman.
	 */
	return apply_filters( 'sakolaan_theme_seed_pages', $tree );
}

/**
 * Buat satu halaman jika belum ada.
 *
 * @param string $slug        Slug halaman.
 * @param string $title       Judul halaman.
 * @param int    $parent_id   ID halaman induk.
 * @param string $parent_slug Slug halaman induk.
 * @return int ID halaman, atau 0 jika gagal.
 */
function sakolaan_theme_insert_page( $slug, $title, $parent_id = 0, $parent_slug = '' ) {
	$path     = $parent_slug ? $parent_slug . '/' . $slug : $slug;
	$existing = get_page_by_path( $path );

	if ( $existing instanceof WP_Post ) {
		return (int) $existing->ID;
	}

	$content = '<!-- wp:paragraph --><p>' . esc_html__( 'Konten halaman ini belum diisi.', 'sakolaan-theme' ) . '</p><!-- /wp:paragraph -->';

	$page_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_parent'  => $parent_id,
				'post_content' => $content,
				'meta_input'   => 'kontak' === $slug ? array( '_wp_page_template' => 'page-kontak' ) : array(),
			)
		),
		true
	);

	return is_wp_error( $page_id ) ? 0 : (int) $page_id;
}

/**
 * Buat semua halaman bawaan.
 *
 * @return void
 */
function sakolaan_theme_seed_pages() {
	if ( ! current_user_can( 'publish_pages' ) ) {
		return;
	}

	foreach ( sakolaan_theme_get_page_tree() as $slug => $page ) {
		$parent_id = sakolaan_theme_insert_page( $slug, $page['title'] );

		if ( ! $parent_id || empty( $page['children'] ) ) {
			continue;
		}

		foreach ( $page['children'] as $child_slug => $child_title ) {
			sakolaan_theme_insert_page( $child_slug, $child_title, $parent_id, $slug );
		}
	}
}
add_action( 'after_switch_theme', 'sakolaan_theme_seed_pages' );

/**
 * Block link navigasi.
 *
 * @param string $label   Label.
 * @param string $url     URL.
 * @param int    $page_id ID halaman (opsional).
 * @param string $class   Class CSS (opsional).
 * @return array Parsed block.
 */
function sakolaan_theme_menu_link( $label, $url, $page_id = 0, $class = '' ) {
	$attrs = array(
		'label' => $label,
		'url'   => $url,
		'kind'  => 'custom',
	);

	if ( $page_id ) {
		$attrs['type'] = 'page';
		$attrs['id']   = $page_id;
		$attrs['kind'] = 'post-type';
	}

	if ( $class ) {
		$attrs['className'] = $class;
	}

	return array(
		'blockName'    => 'core/navigation-link',
		'attrs'        => $attrs,
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	);
}

/**
 * Block submenu navigasi.
 *
 * @param string  $label    Label.
 * @param array[] $children Parsed block anak.
 * @return array|null Parsed block, atau null jika tidak punya anak.
 */
function sakolaan_theme_menu_submenu( $label, $children ) {
	$children = array_values( array_filter( $children ) );

	if ( empty( $children ) ) {
		return null;
	}

	return array(
		'blockName'    => 'core/navigation-submenu',
		'attrs'        => array(
			'label' => $label,
			'kind'  => 'custom',
		),
		'innerBlocks'  => $children,
		'innerHTML'    => '',
		'innerContent' => array_fill( 0, count( $children ), null ),
	);
}

/**
 * Link ke halaman berdasarkan path.
 *
 * @param string $path  Path halaman.
 * @param string $label Label.
 * @param string $class Class CSS (opsional).
 * @return array|null
 */
function sakolaan_theme_menu_page_link( $path, $label, $class = '' ) {
	$page = get_page_by_path( $path );

	if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
		return null;
	}

	return sakolaan_theme_menu_link( $label, (string) get_permalink( $page ), (int) $page->ID, $class );
}

/**
 * Link ke arsip post type, hanya jika post type terdaftar dan punya arsip.
 *
 * @param string $post_type Post type.
 * @param string $label     Label.
 * @return array|null
 */
function sakolaan_theme_menu_archive_link( $post_type, $label ) {
	$url = get_post_type_archive_link( $post_type );

	return $url ? sakolaan_theme_menu_link( $label, $url ) : null;
}

/**
 * Susun block menu utama.
 *
 * @return array[]
 */
function sakolaan_theme_build_menu_blocks() {
	$blocks = array(
		sakolaan_theme_menu_submenu(
			__( 'Profil', 'sakolaan-theme' ),
			array(
				sakolaan_theme_menu_page_link( 'profil', __( 'Profil sekolah', 'sakolaan-theme' ) ),
				sakolaan_theme_menu_page_link( 'profil/sejarah', __( 'Sejarah', 'sakolaan-theme' ) ),
				sakolaan_theme_menu_page_link( 'profil/visi-misi', __( 'Visi & Misi', 'sakolaan-theme' ) ),
				sakolaan_theme_menu_page_link( 'profil/struktur-organisasi', __( 'Struktur Organisasi', 'sakolaan-theme' ) ),
				sakolaan_theme_menu_page_link( 'profil/fasilitas', __( 'Fasilitas', 'sakolaan-theme' ) ),
				sakolaan_theme_menu_page_link( 'profil/akreditasi', __( 'Akreditasi', 'sakolaan-theme' ) ),
			)
		),
		sakolaan_theme_menu_page_link( 'akademik', __( 'Akademik', 'sakolaan-theme' ) ),
		sakolaan_theme_menu_page_link( 'kesiswaan', __( 'Kesiswaan', 'sakolaan-theme' ) ),
		sakolaan_theme_menu_archive_link( 'guru', __( 'Guru & Tendik', 'sakolaan-theme' ) ),
		sakolaan_theme_menu_submenu(
			__( 'Informasi', 'sakolaan-theme' ),
			array(
				sakolaan_theme_menu_archive_link( 'berita', __( 'Berita', 'sakolaan-theme' ) ),
				sakolaan_theme_menu_archive_link( 'pengumuman', __( 'Pengumuman', 'sakolaan-theme' ) ),
				sakolaan_theme_menu_archive_link( 'agenda', __( 'Agenda', 'sakolaan-theme' ) ),
			)
		),
		sakolaan_theme_menu_submenu(
			__( 'Kegiatan', 'sakolaan-theme' ),
			array(
				sakolaan_theme_menu_archive_link( 'prestasi', __( 'Prestasi', 'sakolaan-theme' ) ),
				sakolaan_theme_menu_archive_link( 'ekstrakurikuler', __( 'Ekstrakurikuler', 'sakolaan-theme' ) ),
				sakolaan_theme_menu_archive_link( 'galeri', __( 'Galeri', 'sakolaan-theme' ) ),
			)
		),
		sakolaan_theme_menu_page_link( 'kontak', __( 'Kontak', 'sakolaan-theme' ) ),
	);

	return array_values( array_filter( $blocks ) );
}

/**
 * Cari menu utama yang sudah pernah dibuat.
 *
 * @return int ID navigation post, atau 0 jika tidak ada.
 */
function sakolaan_theme_find_main_menu() {
	$stored_id = absint( get_option( 'sakolaan_theme_menu_seeded' ) );

	if ( $stored_id && 'wp_navigation' === get_post_type( $stored_id ) && 'trash' !== get_post_status( $stored_id ) ) {
		return $stored_id;
	}

	$existing = get_posts(
		array(
			'post_type'              => 'wp_navigation',
			'post_status'            => 'any',
			'title'                  => __( 'Menu utama', 'sakolaan-theme' ),
			'fields'                 => 'ids',
			'posts_per_page'         => 1,
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return empty( $existing ) ? 0 : (int) $existing[0];
}

/**
 * Buat menu navigasi utama sekali saja, setelah Sakolaan aktif.
 *
 * @return void
 */
function sakolaan_theme_maybe_seed_menu() {
	if ( ! sakolaan_theme_is_core_active() ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$existing_menu_id = sakolaan_theme_find_main_menu();

	if ( $existing_menu_id ) {
		update_option( 'sakolaan_theme_menu_seeded', $existing_menu_id, false );
		return;
	}

	sakolaan_theme_seed_pages();

	$blocks = sakolaan_theme_build_menu_blocks();

	if ( empty( $blocks ) ) {
		return;
	}

	$menu_id = wp_insert_post(
		wp_slash(
			array(
				'post_type'    => 'wp_navigation',
				'post_status'  => 'publish',
				'post_title'   => __( 'Menu utama', 'sakolaan-theme' ),
				'post_content' => serialize_blocks( $blocks ),
			)
		),
		true
	);

	if ( ! is_wp_error( $menu_id ) ) {
		update_option( 'sakolaan_theme_menu_seeded', (int) $menu_id, false );
	}
}
add_action( 'admin_init', 'sakolaan_theme_maybe_seed_menu' );

/**
 * Hapus tautan PPDB dari block menu tanpa mengubah item navigasi lainnya.
 *
 * @param array[] $blocks  Parsed navigation blocks.
 * @param int     $page_id ID halaman PPDB.
 * @param string  $url     URL halaman PPDB.
 * @param bool    $changed Apakah ada block yang dihapus.
 * @return array[]
 */
function sakolaan_theme_remove_ppdb_menu_blocks( $blocks, $page_id, $url, &$changed ) {
	$filtered = array();

	foreach ( $blocks as $block ) {
		if ( 'core/navigation-link' === $block['blockName'] ) {
			$block_id  = isset( $block['attrs']['id'] ) ? absint( $block['attrs']['id'] ) : 0;
			$block_url = isset( $block['attrs']['url'] ) ? untrailingslashit( (string) $block['attrs']['url'] ) : '';

			if ( ( $page_id && $block_id === $page_id ) || ( $url && $block_url === untrailingslashit( $url ) ) ) {
				$changed = true;
				continue;
			}
		}

		if ( ! empty( $block['innerBlocks'] ) ) {
			$block['innerBlocks']  = sakolaan_theme_remove_ppdb_menu_blocks( $block['innerBlocks'], $page_id, $url, $changed );
			$block['innerContent'] = array_fill( 0, count( $block['innerBlocks'] ), null );
		}

		$filtered[] = $block;
	}

	return $filtered;
}

/**
 * Migrasikan menu seed lama agar CTA PPDB tidak diduplikasi oleh item navigasi.
 *
 * @return void
 */
function sakolaan_theme_migrate_unique_ppdb_header_cta() {
	if ( '3' === get_option( 'sakolaan_theme_ppdb_header_nav_version' ) ) {
		return;
	}

	$menu_id = sakolaan_theme_find_main_menu();
	$page    = get_page_by_path( 'ppdb' );

	if ( ! $menu_id || ! $page instanceof WP_Post ) {
		return;
	}

	$menu = get_post( $menu_id );

	if ( ! $menu instanceof WP_Post ) {
		return;
	}

	$changed = false;
	$blocks  = sakolaan_theme_remove_ppdb_menu_blocks(
		parse_blocks( $menu->post_content ),
		(int) $page->ID,
		(string) get_permalink( $page ),
		$changed
	);

	if ( $changed ) {
		$result = wp_update_post(
			wp_slash(
				array(
					'ID'           => $menu_id,
					'post_content' => serialize_blocks( $blocks ),
				)
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return;
		}
	}

	update_option( 'sakolaan_theme_ppdb_header_nav_version', '3', false );
}
add_action( 'init', 'sakolaan_theme_migrate_unique_ppdb_header_cta', 30 );
