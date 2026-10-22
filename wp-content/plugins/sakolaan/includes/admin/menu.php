<?php
/** One Sakolaan menu with a single level of in-page navigation. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sakolaan_admin_url( $view = 'summary', $args = array() ) {
	return add_query_arg( array_merge( array( 'page' => 'sakolaan', 'view' => $view ), $args ), admin_url( 'admin.php' ) );
}

/** Single source of truth for Sakolaan dashboard navigation. */
function sakolaan_admin_navigation_registry() {
	$registry = array(
		'summary' => array(
			'label' => __( 'Ringkasan', 'sakolaan' ), 'title' => __( 'Ringkasan', 'sakolaan' ), 'description' => __( 'Status situs dan pekerjaan konten yang membutuhkan perhatian.', 'sakolaan' ),
			'icon' => 'dashicons-dashboard', 'capability' => 'edit_posts',
		),
		'identity' => array(
			'label' => __( 'Identitas', 'sakolaan' ), 'title' => __( 'Identitas Sekolah', 'sakolaan' ), 'description' => __( 'Kelola data resmi, pimpinan, kontak, dan profil utama sekolah.', 'sakolaan' ),
			'icon' => 'dashicons-building', 'capability' => 'manage_options', 'query_key' => 'panel',
			'children' => array(
				'identity' => array( 'label' => __( 'Identitas', 'sakolaan' ), 'description' => __( 'Nama, logo, alamat, dan informasi dasar sekolah.', 'sakolaan' ), 'icon' => 'dashicons-id' ),
				'principal' => array( 'label' => __( 'Kepala Sekolah', 'sakolaan' ), 'description' => __( 'Profil, foto, dan sambutan kepala sekolah.', 'sakolaan' ), 'icon' => 'dashicons-businessperson' ),
				'contact' => array( 'label' => __( 'Kontak', 'sakolaan' ), 'description' => __( 'Nomor telepon, email, WhatsApp, dan lokasi sekolah.', 'sakolaan' ), 'icon' => 'dashicons-phone' ),
				'social' => array( 'label' => __( 'Media sosial', 'sakolaan' ), 'description' => __( 'Tautan kanal media sosial resmi sekolah.', 'sakolaan' ), 'icon' => 'dashicons-share' ),
				'statistics' => array( 'label' => __( 'Statistik', 'sakolaan' ), 'description' => __( 'Angka utama yang ditampilkan pada beranda.', 'sakolaan' ), 'icon' => 'dashicons-chart-bar' ),
			),
		),
		'content' => array(
			'label' => __( 'Konten', 'sakolaan' ), 'title' => __( 'Konten', 'sakolaan' ), 'description' => __( 'Kelola isi situs, PPDB, serta tampilan halaman publik.', 'sakolaan' ),
			'icon' => 'dashicons-admin-page', 'capability' => 'edit_posts', 'query_key' => 'panel',
			'children' => array(
				'catalog' => array( 'label' => __( 'Daftar konten', 'sakolaan' ), 'description' => __( 'Kelola berita, agenda, guru, fasilitas, dan konten sekolah lainnya.', 'sakolaan' ), 'icon' => 'dashicons-list-view', 'capability' => 'edit_posts', 'action' => __( 'Buka daftar', 'sakolaan' ) ),
				'ppdb' => array( 'label' => __( 'PPDB', 'sakolaan' ), 'description' => __( 'Atur informasi dan tombol penerimaan peserta didik baru.', 'sakolaan' ), 'icon' => 'dashicons-welcome-learn-more', 'capability' => 'manage_options' ),
				'appearance' => array(
					'label' => __( 'Tampilan', 'sakolaan' ), 'description' => __( 'Atur header, footer, dan susunan konten beranda.', 'sakolaan' ), 'icon' => 'dashicons-admin-appearance', 'capability' => 'manage_options', 'action' => __( 'Pilih bagian', 'sakolaan' ), 'query_key' => 'area',
					'children' => array(
						'header' => array( 'label' => __( 'Header', 'sakolaan' ), 'description' => __( 'Perilaku header dan akses ke navigasi situs.', 'sakolaan' ), 'icon' => 'dashicons-align-wide' ),
						'footer' => array( 'label' => __( 'Footer', 'sakolaan' ), 'description' => __( 'Identitas, kontak, dan informasi pada bagian bawah situs.', 'sakolaan' ), 'icon' => 'dashicons-editor-insertmore' ),
						'homepage' => array(
							'label' => __( 'Beranda', 'sakolaan' ), 'description' => __( 'Susun section, hero, akses cepat, dan jumlah konten.', 'sakolaan' ), 'icon' => 'dashicons-admin-home', 'action' => __( 'Pilih bagian', 'sakolaan' ), 'query_key' => 'component',
							'children' => array(
								'sections' => array( 'label' => __( 'Section', 'sakolaan' ), 'description' => __( 'Aktifkan, urutkan, dan tentukan jumlah item setiap section.', 'sakolaan' ), 'icon' => 'dashicons-screenoptions' ),
								'hero' => array( 'label' => __( 'Hero', 'sakolaan' ), 'description' => __( 'Atur hero tunggal atau slider beserta tombol aksinya.', 'sakolaan' ), 'icon' => 'dashicons-format-image' ),
								'quick-links' => array( 'label' => __( 'Akses Cepat', 'sakolaan' ), 'description' => __( 'Kelola tautan penting yang tampil di beranda.', 'sakolaan' ), 'icon' => 'dashicons-admin-links' ),
								'listing' => array( 'label' => __( 'Tampilan Konten', 'sakolaan' ), 'description' => __( 'Tentukan jumlah konten pada halaman daftar publik.', 'sakolaan' ), 'icon' => 'dashicons-editor-ol' ),
							),
						),
					),
				),
			),
		),
		'seo' => array(
			'label' => __( 'SEO', 'sakolaan' ), 'title' => __( 'SEO', 'sakolaan' ), 'description' => __( 'Atur metadata fallback global untuk mesin pencari dan media sosial.', 'sakolaan' ),
			'icon' => 'dashicons-search', 'capability' => 'manage_options',
		),
		'more' => array(
			'label' => __( 'Lainnya', 'sakolaan' ), 'title' => __( 'Lainnya', 'sakolaan' ), 'description' => __( 'Buat data awal, pindahkan data, dan rawat sistem Sakolaan.', 'sakolaan' ),
			'icon' => 'dashicons-admin-tools', 'capability' => 'manage_options', 'query_key' => 'panel',
			'children' => array(
				'demo' => array( 'label' => __( 'Data Demo', 'sakolaan' ), 'description' => __( 'Buat atau bersihkan data contoh untuk menyiapkan situs.', 'sakolaan' ), 'icon' => 'dashicons-database-add' ),
				'system' => array(
					'label' => __( 'Sistem', 'sakolaan' ), 'description' => __( 'Backup, pemulihan, diagnostik, dan pemeliharaan.', 'sakolaan' ), 'icon' => 'dashicons-admin-generic', 'action' => __( 'Pilih alat', 'sakolaan' ), 'query_key' => 'tool',
					'children' => array(
						'settings-backup' => array( 'label' => __( 'Backup Pengaturan', 'sakolaan' ), 'description' => __( 'Ekspor atau pulihkan konfigurasi tanpa memindahkan konten.', 'sakolaan' ), 'icon' => 'dashicons-download' ),
						'data-backup' => array( 'label' => __( 'Backup Data', 'sakolaan' ), 'description' => __( 'Ekspor, pratinjau, dan pulihkan data Sakolaan.', 'sakolaan' ), 'icon' => 'dashicons-database-export' ),
						'maintenance' => array( 'label' => __( 'Pemeliharaan', 'sakolaan' ), 'description' => __( 'Periksa kesehatan sistem dan perbarui permalink.', 'sakolaan' ), 'icon' => 'dashicons-admin-tools' ),
						'legacy' => array( 'label' => __( 'Data Legacy', 'sakolaan' ), 'description' => __( 'Backup, bersihkan, atau pulihkan data berita lama.', 'sakolaan' ), 'icon' => 'dashicons-backup' ),
						'advanced' => array( 'label' => __( 'Lanjutan', 'sakolaan' ), 'description' => __( 'Konfigurasi inti untuk kebutuhan administrator.', 'sakolaan' ), 'icon' => 'dashicons-admin-settings' ),
					),
				),
			),
		),
	);

	/**
	 * Filter the Sakolaan dashboard navigation registry.
	 *
	 * Add-ons may register a top-level view with the same shape as the built-in
	 * nodes. A callable `render_callback` is invoked only after the node's
	 * capability has been checked.
	 *
	 * @param array $registry Dashboard navigation nodes.
	 */
	return apply_filters( 'sakolaan_admin_navigation_registry', $registry );
}

/** Map the shared Sakolaan dashboard capability without granting edit_posts. */
function sakolaan_map_admin_capability( $required_caps, $capability, $user_id ) {
	if ( 'sakolaan_access_admin' !== $capability ) {
		return $required_caps;
	}

	$capabilities = (array) apply_filters( 'sakolaan_admin_access_capabilities', array( 'edit_posts' ), $user_id );
	$user         = get_userdata( $user_id );
	if ( ! $user ) {
		return array( 'do_not_allow' );
	}

	foreach ( array_filter( array_map( 'sanitize_key', $capabilities ) ) as $candidate ) {
		if ( ! empty( $user->allcaps[ $candidate ] ) ) {
			return array( $candidate );
		}
	}

	return array( 'do_not_allow' );
}
add_filter( 'map_meta_cap', 'sakolaan_map_admin_capability', 10, 3 );

/** Find a navigation node from its view and descendant keys. */
function sakolaan_admin_navigation_node( $view, $path = array() ) {
	$registry = sakolaan_admin_navigation_registry();
	if ( ! isset( $registry[ $view ] ) ) {
		return null;
	}
	$node = $registry[ $view ];
	foreach ( $path as $key ) {
		if ( empty( $node['children'][ $key ] ) ) {
			return null;
		}
		$node = $node['children'][ $key ];
	}
	return $node;
}

/** Build a dashboard URL from the navigation hierarchy. */
function sakolaan_admin_navigation_url( $view, $path = array() ) {
	$args = array();
	$node = sakolaan_admin_navigation_node( $view );
	foreach ( $path as $key ) {
		if ( ! $node || empty( $node['query_key'] ) || empty( $node['children'][ $key ] ) ) {
			break;
		}
		$args[ $node['query_key'] ] = $key;
		$node = $node['children'][ $key ];
	}
	return sakolaan_admin_url( $view, $args );
}

function sakolaan_admin_navigation_can_view( $item, $fallback_capability = 'manage_options' ) {
	return current_user_can( $item['capability'] ?? $fallback_capability );
}

/** Read and validate a route parameter without silently opening another editor. */
function sakolaan_admin_route_value( $query_key, $children, &$invalid = false, $fallback_capability = 'manage_options' ) {
	if ( ! isset( $_GET[ $query_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navigation only.
		return '';
	}
	$value = sanitize_key( wp_unslash( $_GET[ $query_key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navigation only.
	if ( ! isset( $children[ $value ] ) || ! sakolaan_admin_navigation_can_view( $children[ $value ], $fallback_capability ) ) {
		$invalid = true;
		return '';
	}
	return $value;
}

function sakolaan_admin_views() {
	$views = array();
	foreach ( sakolaan_admin_navigation_registry() as $key => $item ) { if ( current_user_can( $item['capability'] ) ) { $views[ $key ] = $item['label']; } }
	return $views;
}

/** Redirect obsolete dashboard routes once so saved admin bookmarks stay useful. */
function sakolaan_redirect_legacy_admin_routes() {
	if ( ! is_admin() || ! current_user_can( 'edit_posts' ) || 'sakolaan' !== ( isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route normalization.
		return;
	}
	$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route normalization.
	$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route normalization.
	$panel = isset( $_GET['panel'] ) ? sanitize_key( wp_unslash( $_GET['panel'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only route normalization.
	if ( 'home' === $view ) {
		wp_safe_redirect( sakolaan_admin_url( 'content', array( 'panel' => 'appearance', 'area' => 'homepage', 'component' => 'hero' ) ) );
		exit;
	}
	if ( 'tools' === $view ) {
		wp_safe_redirect( sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'maintenance' ) ) );
		exit;
	}
	if ( 'more' === $view && in_array( $panel, array( 'seo', 'transfer', 'backup', 'maintenance' ), true ) ) {
		if ( 'seo' === $panel ) { $url = sakolaan_admin_url( 'seo' ); }
		else {
			$tool = array( 'transfer' => 'settings-backup', 'backup' => 'legacy', 'maintenance' => 'maintenance' )[ $panel ];
			$url = sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => $tool ) );
		}
		wp_safe_redirect( $url ); exit;
	}
	if ( 'content' === $view && in_array( $panel, array( 'homepage', 'principal', 'footer', 'display' ), true ) ) {
		if ( 'principal' === $panel ) { $url = sakolaan_admin_url( 'identity', array( 'panel' => 'principal' ) ); }
		elseif ( 'footer' === $panel ) { $url = sakolaan_admin_url( 'content', array( 'panel' => 'appearance', 'area' => 'footer' ) ); }
		else { $url = sakolaan_admin_url( 'content', array( 'panel' => 'appearance', 'area' => 'homepage', 'component' => 'display' === $panel ? 'listing' : 'sections' ) ); }
		wp_safe_redirect( $url ); exit;
	}
	if ( 'settings' !== $view ) {
		return;
	}
	if ( in_array( $tab, array( 'identity', 'contact', 'social' ), true ) ) {
		$url = sakolaan_admin_url( 'identity', array( 'panel' => $tab ) );
	} elseif ( 'principal' === $tab ) {
		$url = sakolaan_admin_url( 'identity', array( 'panel' => 'principal' ) );
	} elseif ( in_array( $tab, array( 'homepage', 'sections' ), true ) ) {
		$url = sakolaan_admin_url( 'content', array( 'panel' => 'appearance', 'area' => 'homepage', 'component' => 'sections' ) );
	} elseif ( 'ppdb' === $tab ) {
		$url = sakolaan_admin_url( 'content', array( 'panel' => 'ppdb' ) );
	} elseif ( in_array( $tab, array( 'footer', 'display' ), true ) ) {
		$url = sakolaan_admin_url( 'content', array( 'panel' => 'appearance', 'area' => 'footer' === $tab ? 'footer' : 'homepage', 'component' => 'listing' ) );
	} else {
		$url = 'seo' === $tab ? sakolaan_admin_url( 'seo' ) : sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'advanced' ) );
	}
	wp_safe_redirect( $url );
	exit;
}
add_action( 'admin_init', 'sakolaan_redirect_legacy_admin_routes', 20 );

function sakolaan_register_admin_menu() {
	add_menu_page( __( 'Sakolaan', 'sakolaan' ), __( 'Sakolaan', 'sakolaan' ), 'sakolaan_access_admin', 'sakolaan', 'sakolaan_render_admin_dashboard', 'dashicons-building', 20 );
}
add_action( 'admin_menu', 'sakolaan_register_admin_menu', 5 );

function sakolaan_remove_auto_submenu() {
	remove_submenu_page( 'sakolaan', 'sakolaan' );
	remove_menu_page( 'edit.php' );
}
add_action( 'admin_menu', 'sakolaan_remove_auto_submenu', 999 );

function sakolaan_render_admin_dashboard() {
	if ( ! current_user_can( 'sakolaan_access_admin' ) ) {
		wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) );
	}
	$views = sakolaan_admin_views();
	$requested_view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'summary'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navigation only.
	$invalid_view = ! isset( $views[ $requested_view ] );
	$view = $invalid_view ? ( isset( $views['summary'] ) ? 'summary' : (string) array_key_first( $views ) ) : $requested_view;
	if ( '' === $view ) {
		wp_die( esc_html__( 'Tidak ada halaman Sakolaan yang dapat Anda akses.', 'sakolaan' ), '', array( 'response' => 403 ) );
	}
	echo '<div class="wrap sakolaan-ui sakolaan-dashboard">';
	echo '<header class="sakolaan-dashboard__header"><span class="sakolaan-admin-kicker">' . esc_html__( 'Platform website sekolah', 'sakolaan' ) . '</span><h1>' . esc_html__( 'Sakolaan', 'sakolaan' ) . '</h1><p>' . esc_html__( 'Kelola identitas, konten, tampilan, dan sistem website sekolah dari satu tempat.', 'sakolaan' ) . '</p><a class="sakolaan-dashboard__official-link" href="https://sakolaan.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Website resmi Sakolaan', 'sakolaan' ) . '<span class="dashicons dashicons-external" aria-hidden="true"></span></a></header>';
	echo '<div class="sakolaan-dashboard__navigation">';
	printf( '<button type="button" class="sakolaan-dashboard__nav-toggle" aria-expanded="true" aria-controls="sakolaan-primary-navigation"><span>%1$s</span><strong>%2$s</strong><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>', esc_html__( 'Menu', 'sakolaan' ), esc_html( $views[ $view ] ) );
	echo '<nav id="sakolaan-primary-navigation" class="sakolaan-dashboard__nav" aria-label="' . esc_attr__( 'Navigasi Sakolaan', 'sakolaan' ) . '">';
	foreach ( $views as $key => $label ) {
		printf( '<a href="%1$s"%3$s>%2$s</a>', esc_url( sakolaan_admin_url( $key ) ), esc_html( $label ), $key === $view ? ' aria-current="page"' : '' );
	}
	echo '</nav></div><main class="sakolaan-dashboard__main">';
	$updated = isset( $_GET['updated'] ) ? sanitize_key( wp_unslash( $_GET['updated'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Feedback only.
	$notices = array( 'permalinks' => __( 'Permalink berhasil diperbarui.', 'sakolaan' ), 'settings' => __( 'Pengaturan berhasil diimpor.', 'sakolaan' ), 'data' => __( 'Backup data berhasil diimpor.', 'sakolaan' ), 'legacy' => __( 'Data berita lama berhasil dibersihkan.', 'sakolaan' ), 'restored' => __( 'Data berita lama berhasil dipulihkan.', 'sakolaan' ), 'migration-complete' => __( 'Migrasi identitas Sakolaan berhasil diselesaikan.', 'sakolaan' ), 'migration-restored' => __( 'Snapshot sebelum migrasi berhasil dipulihkan.', 'sakolaan' ), 'migration-failed' => __( 'Migrasi belum dapat diselesaikan. Periksa status migrasi untuk rinciannya.', 'sakolaan' ) );
	if ( isset( $notices[ $updated ] ) ) {
		echo '<div class="notice sakolaan-notice notice-success inline"><p>' . esc_html( $notices[ $updated ] ) . '</p></div>';
	}
	if ( $invalid_view ) {
		sakolaan_render_invalid_route_notice();
	}
	$node = sakolaan_admin_navigation_node( $view );
	if ( ! empty( $node['render_callback'] ) && is_callable( $node['render_callback'] ) ) {
		call_user_func( $node['render_callback'] );
	} else {
	switch ( $view ) {
		case 'identity': sakolaan_render_identity_panel(); break;
		case 'content': sakolaan_render_content_panel(); break;
		case 'seo': sakolaan_render_seo_panel(); break;
		case 'more': sakolaan_render_more_page(); break;
		default: sakolaan_render_summary_panel();
	}
	}
	echo '</main></div>';
}

function sakolaan_content_list_url( $post_type ) {
	return admin_url( 'edit.php' . ( 'post' === $post_type ? '' : '?post_type=' . $post_type ) );
}

function sakolaan_render_content_catalog( $group = '' ) {
	if ( ! $group ) {
		echo '<h3>' . esc_html__( 'Jenis Konten', 'sakolaan' ) . '</h3>';
		echo '<p class="sakolaan-lead">' . esc_html__( 'Buka daftar konten untuk menyunting, menambah, atau mengurutkan item.', 'sakolaan' ) . '</p>';
	}
	echo '<div class="sakolaan-catalog">';
	$previous_group = '';
	foreach ( sakolaan_content_registry() as $post_type => $item ) {
		if ( ( $group && $item['group'] !== $group ) || ( ! $group && 'home' === $item['group'] ) ) {
			continue;
		}
		$object = get_post_type_object( $post_type );
		if ( ! $object || ! current_user_can( $object->cap->edit_posts ) ) {
			continue;
		}
		if ( ! $group && $previous_group !== $item['group'] ) {
			$group_label = 'publication' === $item['group'] ? __( 'Publikasi', 'sakolaan' ) : __( 'Profil & Kegiatan', 'sakolaan' );
			echo '<h3 class="sakolaan-catalog__group">' . esc_html( $group_label ) . '</h3>';
			$previous_group = $item['group'];
		}
		$counts = wp_count_posts( $post_type );
		$total = $counts ? (int) ( $counts->publish ?? 0 ) : 0;
		$missing = sakolaan_count_incomplete_content( $post_type );
		printf( '<article class="sakolaan-catalog__item"><div><span class="dashicons %1$s" aria-hidden="true"></span><h4>%2$s</h4><p>%3$s</p></div><div class="sakolaan-catalog__actions"><span>%4$s</span><a class="button" href="%5$s">%6$s</a>', esc_attr( $item['icon'] ), esc_html( $item['label'] ), esc_html( sprintf( /* translators: 1: published count, 2: incomplete count. */ __( '%1$d terbit · %2$d perlu dilengkapi', 'sakolaan' ), $total, $missing ) ), esc_html( number_format_i18n( $total ) ), esc_url( sakolaan_content_list_url( $post_type ) ), esc_html__( 'Kelola', 'sakolaan' ) );
		if ( current_user_can( $object->cap->create_posts ) ) {
			printf( '<a class="button button-primary" href="%1$s">%2$s</a>', esc_url( admin_url( 'post-new.php' . ( 'post' === $post_type ? '' : '?post_type=' . $post_type ) ) ), esc_html__( 'Tambah', 'sakolaan' ) );
		}
		foreach ( $item['taxonomies'] as $taxonomy_slug ) {
			$taxonomy = get_taxonomy( $taxonomy_slug );
			if ( $taxonomy && current_user_can( $taxonomy->cap->manage_terms ) ) {
				printf( '<a class="button" href="%1$s">%2$s</a>', esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy_slug . '&post_type=' . $post_type ) ), esc_html( $taxonomy->labels->name ) );
			}
		}
		echo '</div></article>';
	}
	echo '</div>';
}

function sakolaan_render_summary_panel() {
	sakolaan_render_page_header( 'summary' );
	sakolaan_render_health_summary();
	printf( '<p><a class="button button-primary" href="%1$s">%2$s</a></p>', esc_url( sakolaan_admin_url( 'content' ) ), esc_html__( 'Buka katalog konten', 'sakolaan' ) );
}

/** Render the current hierarchy, page title, description, and parent link. */
function sakolaan_render_page_header( $view, $path = array() ) {
	$root = sakolaan_admin_navigation_node( $view );
	$node = sakolaan_admin_navigation_node( $view, $path );
	if ( ! $root || ! $node ) {
		return;
	}
	if ( $path ) {
		echo '<nav class="sakolaan-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'sakolaan' ) . '"><ol>';
		printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( sakolaan_admin_navigation_url( $view ) ), esc_html( $root['title'] ?? $root['label'] ) );
		$trail = array();
		foreach ( $path as $index => $key ) {
			$trail[] = $key;
			$item = sakolaan_admin_navigation_node( $view, $trail );
			if ( ! $item ) {
				continue;
			}
			if ( $index === array_key_last( $path ) ) {
				printf( '<li aria-current="page">%s</li>', esc_html( $item['label'] ) );
			} else {
				printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( sakolaan_admin_navigation_url( $view, $trail ) ), esc_html( $item['label'] ) );
			}
		}
		echo '</ol></nav>';
		$parent_path = $path;
		array_pop( $parent_path );
		$parent = sakolaan_admin_navigation_node( $view, $parent_path );
		printf( '<a class="sakolaan-back-link" href="%1$s"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>%2$s</a>', esc_url( sakolaan_admin_navigation_url( $view, $parent_path ) ), esc_html( sprintf( /* translators: %s: parent page name. */ __( 'Kembali ke %s', 'sakolaan' ), $parent['label'] ?? $root['label'] ) ) );
	}
	echo '<header class="sakolaan-page-header">';
	printf( '<h2>%s</h2>', esc_html( $node['title'] ?? $node['label'] ) );
	if ( ! empty( $node['description'] ) ) {
		printf( '<p class="sakolaan-lead">%s</p>', esc_html( $node['description'] ) );
	}
	echo '</header>';
}

function sakolaan_render_invalid_route_notice() {
	echo '<div class="notice sakolaan-notice notice-warning inline"><p>' . esc_html__( 'Bagian yang diminta tidak tersedia atau Anda tidak memiliki izin untuk membukanya. Silakan pilih bagian yang tersedia.', 'sakolaan' ) . '</p></div>';
}

/** Render the child routes as one accessible card grid. */
function sakolaan_render_navigation_cards( $view, $path = array(), $fallback_capability = 'manage_options' ) {
	$node = sakolaan_admin_navigation_node( $view, $path );
	if ( empty( $node['children'] ) ) {
		return;
	}
	echo '<div class="sakolaan-navigation-cards">';
	foreach ( $node['children'] as $key => $item ) {
		if ( ! sakolaan_admin_navigation_can_view( $item, $fallback_capability ) ) {
			continue;
		}
		$item_path = array_merge( $path, array( $key ) );
		$action = $item['action'] ?? ( ! empty( $item['children'] ) ? __( 'Pilih bagian', 'sakolaan' ) : __( 'Buka pengaturan', 'sakolaan' ) );
		$status = '';
		if ( ! empty( $item['children'] ) ) {
			$status = sprintf( /* translators: %d: number of available sections. */ _n( '%d bagian', '%d bagian', count( $item['children'] ), 'sakolaan' ), count( $item['children'] ) );
		} elseif ( array( 'appearance', 'homepage', 'hero' ) === $item_path ) {
			$settings = sakolaan_get_settings();
			$status = 'slider' === ( $settings['homepage']['hero_mode'] ?? 'single' ) ? __( 'Mode slider', 'sakolaan' ) : __( 'Mode tunggal', 'sakolaan' );
		}
		echo '<article class="sakolaan-navigation-card">';
		printf( '<a href="%1$s"><span class="sakolaan-navigation-card__icon dashicons %2$s" aria-hidden="true"></span><span class="sakolaan-navigation-card__body"><span class="sakolaan-navigation-card__title">%3$s</span><span class="sakolaan-navigation-card__description">%4$s</span></span>', esc_url( sakolaan_admin_navigation_url( $view, $item_path ) ), esc_attr( $item['icon'] ?? 'dashicons-arrow-right-alt2' ), esc_html( $item['label'] ), esc_html( $item['description'] ?? '' ) );
		if ( $status ) {
			printf( '<span class="sakolaan-navigation-card__status">%s</span>', esc_html( $status ) );
		}
		printf( '<span class="sakolaan-navigation-card__action">%1$s<span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></span></a>', esc_html( $action ) );
		echo '</article>';
	}
	echo '</div>';
}

function sakolaan_render_identity_panel() {
	$children = sakolaan_admin_navigation_node( 'identity' )['children'];
	$invalid = false;
	$panel = sakolaan_admin_route_value( 'panel', $children, $invalid );
	if ( ! $panel ) {
		sakolaan_render_page_header( 'identity' );
		if ( $invalid ) { sakolaan_render_invalid_route_notice(); }
		sakolaan_render_navigation_cards( 'identity' );
		return;
	}
	sakolaan_render_page_header( 'identity', array( $panel ) );
	if ( 'statistics' === $panel ) {
		$keys = array();
		for ( $i = 1; $i <= 5; $i++ ) { $keys[] = 'stat_' . $i . '_value'; $keys[] = 'stat_' . $i . '_label'; }
		sakolaan_render_settings_subset( array( 'homepage' => $keys ) );
	} else {
		sakolaan_render_settings_page( true, array( $panel ) );
	}
}

function sakolaan_render_content_panel() {
	$children = sakolaan_admin_navigation_node( 'content' )['children'];
	$invalid = false;
	$panel = sakolaan_admin_route_value( 'panel', $children, $invalid, 'edit_posts' );
	if ( ! $panel ) {
		sakolaan_render_page_header( 'content' );
		if ( $invalid ) { sakolaan_render_invalid_route_notice(); }
		sakolaan_render_navigation_cards( 'content', array(), 'edit_posts' );
		return;
	}
	if ( 'catalog' === $panel ) {
		sakolaan_render_page_header( 'content', array( 'catalog' ) );
		sakolaan_render_content_catalog();
		return;
	}
	if ( 'ppdb' === $panel ) { sakolaan_render_page_header( 'content', array( 'ppdb' ) ); sakolaan_render_settings_page( true, array( 'ppdb' ) ); return; }
	sakolaan_render_appearance_panel();
}

function sakolaan_render_appearance_panel() {
	$path = array( 'appearance' );
	$children = sakolaan_admin_navigation_node( 'content', $path )['children'];
	$invalid = false;
	$area = sakolaan_admin_route_value( 'area', $children, $invalid );
	if ( ! $area ) {
		sakolaan_render_page_header( 'content', $path );
		if ( $invalid ) { sakolaan_render_invalid_route_notice(); }
		sakolaan_render_navigation_cards( 'content', $path );
		return;
	}
	if ( 'header' === $area ) {
		sakolaan_render_page_header( 'content', array( 'appearance', 'header' ) );
		sakolaan_render_settings_page( true, array( 'header' ) );
		printf( '<p class="sakolaan-related-links"><a class="button" href="%1$s">%2$s</a> <a class="button" href="%3$s">%4$s</a></p>', esc_url( admin_url( 'site-editor.php?path=/navigation' ) ), esc_html__( 'Kelola navigasi', 'sakolaan' ), esc_url( admin_url( 'site-editor.php?path=/wp_template_part/all&categoryId=header' ) ), esc_html__( 'Buka template header', 'sakolaan' ) );
		return;
	}
	if ( 'footer' === $area ) { sakolaan_render_page_header( 'content', array( 'appearance', 'footer' ) ); sakolaan_render_settings_page( true, array( 'footer' ) ); return; }
	sakolaan_render_homepage_panel();
}

function sakolaan_render_homepage_panel() {
	$path = array( 'appearance', 'homepage' );
	$children = sakolaan_admin_navigation_node( 'content', $path )['children'];
	$invalid = false;
	$component = sakolaan_admin_route_value( 'component', $children, $invalid );
	if ( ! $component ) {
		sakolaan_render_page_header( 'content', $path );
		if ( $invalid ) { sakolaan_render_invalid_route_notice(); }
		sakolaan_render_navigation_cards( 'content', $path );
		return;
	}
	sakolaan_render_page_header( 'content', array( 'appearance', 'homepage', $component ) );
	if ( 'sections' === $component ) {
		echo '<div class="sakolaan-settings"><form method="post" action="options.php" class="sakolaan-settings-form">'; settings_fields( 'sakolaan_settings_group' ); sakolaan_render_homepage_section_manager();
		echo '<section class="sakolaan-settings-group"><h3>' . esc_html__( 'Pengenalan Sekolah', 'sakolaan' ) . '</h3>';
		$schema = sakolaan_get_settings_schema(); echo '<input type="hidden" name="sakolaan_settings[homepage][__present]" value="1"><table class="form-table" role="presentation"><tbody>';
		foreach ( array( 'intro_image', 'intro_text' ) as $key ) { $field = $schema['homepage']['fields'][ $key ]; echo '<tr><th scope="row"><label for="sakolaan-homepage-' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>'; sakolaan_render_field( array( 'group' => 'homepage', 'key' => $key, 'field' => $field, 'label_for' => 'sakolaan-homepage-' . $key ) ); echo '</td></tr>'; }
		echo '</tbody></table></section>'; sakolaan_render_settings_submit_button(); echo '<p class="sakolaan-submit-status" role="status" aria-live="polite"></p></form></div>'; return;
	}
	if ( 'hero' === $component ) { sakolaan_render_hero_panel(); return; }
	if ( 'quick-links' === $component ) { sakolaan_render_settings_subset( array( 'homepage' => array( 'quick_links' ) ) ); return; }
	sakolaan_render_settings_page( true, array( 'display' ) );
}

function sakolaan_render_hero_panel() {
	$settings = sakolaan_get_settings();
	$mode = $settings['homepage']['hero_mode'] ?? 'single';
	$show = isset( $_GET['show'] ) ? sanitize_key( wp_unslash( $_GET['show'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Navigation only.
	$effective = in_array( $show, array( 'single', 'slider' ), true ) ? $show : $mode;
	sakolaan_render_settings_subset( array( 'homepage' => array( 'hero_mode' ) ) );
	if ( 'slider' === $effective ) {
		if ( 'slider' === $mode ) { sakolaan_render_settings_subset( array( 'homepage' => array( 'hero_autoplay', 'hero_interval' ) ) ); }
		echo '<h3>' . esc_html__( 'Slide Hero', 'sakolaan' ) . '</h3><p>' . esc_html( 'slider' === $mode ? __( 'Mode slider aktif. Kelola gambar, teks, tombol, dan urutan setiap slide dari daftar berikut.', 'sakolaan' ) : __( 'Data slider tersimpan tetapi sedang tidak aktif.', 'sakolaan' ) ) . '</p>';
		sakolaan_render_content_catalog( 'home' );
		printf( '<p><a class="button" href="%s">%s</a></p>', esc_url( sakolaan_admin_url( 'content', array( 'panel' => 'appearance', 'area' => 'homepage', 'component' => 'hero', 'show' => 'single' ) ) ), esc_html__( 'Lihat data hero tunggal', 'sakolaan' ) );
		return;
	}
	if ( 'slider' === $mode ) { echo '<p class="description">' . esc_html__( 'Data hero tunggal tersimpan tetapi sedang tidak aktif.', 'sakolaan' ) . '</p>'; }
	sakolaan_render_settings_subset( array( 'homepage' => array( 'hero_title', 'hero_heading_width', 'hero_description', 'hero_image', 'hero_cta_label', 'hero_cta_url', 'hero_cta2_label', 'hero_cta2_url' ) ) );
	printf( '<p><a class="button" href="%s">%s</a></p>', esc_url( sakolaan_admin_url( 'content', array( 'panel' => 'appearance', 'area' => 'homepage', 'component' => 'hero', 'show' => 'slider' ) ) ), esc_html__( 'Lihat data slider', 'sakolaan' ) );
}

function sakolaan_render_seo_panel() {
	sakolaan_render_page_header( 'seo' );
	sakolaan_render_settings_page( true, array( 'seo' ) );
}

function sakolaan_register_dashboard_help() {
	$screen = get_current_screen();
	if ( $screen && 'toplevel_page_sakolaan' === $screen->id ) {
		$screen->add_help_tab( array( 'id' => 'sakolaan-help', 'title' => __( 'Panduan', 'sakolaan' ), 'content' => '<p>' . esc_html__( 'Identitas memuat profil sekolah. Konten memuat isi dan tampilan situs. SEO memiliki halaman sendiri. Data demo, backup, dan pemeliharaan tersedia di Lainnya.', 'sakolaan' ) . '</p>' ) );
	}
}
add_action( 'current_screen', 'sakolaan_register_dashboard_help' );
