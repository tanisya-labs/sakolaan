<?php
/**
 * Pattern yang dihasilkan dari konfigurasi: isi arsip dan empty state per tipe konten.
 *
 * Satu generator menjamin semua arsip konsisten tanpa duplikasi markup. Pattern ini
 * dipakai oleh template archive-* dan taxonomy-* lewat blok core/pattern.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Konfigurasi arsip per post type.
 *
 * @return array
 */
function pijar_sakolaan_get_archive_configs() {
	$configs = array(
		'berita'          => array(
			'label'       => __( 'Berita', 'pijar-sakolaan' ),
			'filter'      => 'category',
			'card'        => 'berita',
			'min'         => '18rem',
			'empty_title' => __( 'Belum ada berita', 'pijar-sakolaan' ),
			'empty_text'  => __( 'Berita sekolah akan tampil di sini setelah dipublikasikan.', 'pijar-sakolaan' ),
		),
		'guru'            => array(
			'label'       => __( 'Guru & Tendik', 'pijar-sakolaan' ),
			'filter'      => 'kelompok_gtk',
			'card'        => 'guru',
			'min'         => '14rem',
			'empty_title' => __( 'Data guru belum tersedia', 'pijar-sakolaan' ),
			'empty_text'  => __( 'Profil guru dan tenaga kependidikan akan tampil di sini.', 'pijar-sakolaan' ),
		),
		'pengumuman'      => array(
			'label'       => __( 'Pengumuman', 'pijar-sakolaan' ),
			'filter'      => '',
			'card'        => 'pengumuman',
			'min'         => '20rem',
			'empty_title' => __( 'Belum ada pengumuman', 'pijar-sakolaan' ),
			'empty_text'  => __( 'Pengumuman terbaru dari sekolah akan tampil di sini.', 'pijar-sakolaan' ),
		),
		'agenda'          => array(
			'label'       => __( 'Agenda', 'pijar-sakolaan' ),
			'filter'      => '',
			'card'        => 'agenda',
			'min'         => '18rem',
			'empty_title' => __( 'Belum ada agenda', 'pijar-sakolaan' ),
			'empty_text'  => __( 'Jadwal kegiatan sekolah akan tampil di sini.', 'pijar-sakolaan' ),
		),
		'prestasi'        => array(
			'label'       => __( 'Prestasi', 'pijar-sakolaan' ),
			'filter'      => 'tingkat_prestasi',
			'card'        => 'prestasi',
			'min'         => '18rem',
			'empty_title' => __( 'Belum ada prestasi', 'pijar-sakolaan' ),
			'empty_text'  => __( 'Prestasi siswa dan sekolah akan tampil di sini.', 'pijar-sakolaan' ),
		),
		'ekstrakurikuler' => array(
			'label'       => __( 'Ekstrakurikuler', 'pijar-sakolaan' ),
			'filter'      => '',
			'card'        => 'ekskul',
			'min'         => '16rem',
			'empty_title' => __( 'Belum ada ekstrakurikuler', 'pijar-sakolaan' ),
			'empty_text'  => __( 'Kegiatan ekstrakurikuler akan tampil di sini.', 'pijar-sakolaan' ),
		),
		'galeri'          => array(
			'label'       => __( 'Galeri', 'pijar-sakolaan' ),
			'filter'      => 'album_galeri',
			'card'        => 'galeri',
			'min'         => '16rem',
			'empty_title' => __( 'Belum ada galeri', 'pijar-sakolaan' ),
			'empty_text'  => __( 'Foto kegiatan sekolah akan tampil di sini.', 'pijar-sakolaan' ),
		),
		'fasilitas'       => array(
			'label'       => __( 'Fasilitas', 'pijar-sakolaan' ),
			'filter'      => '',
			'card'        => 'fasilitas',
			'min'         => '18rem',
			'empty_title' => __( 'Fasilitas belum tersedia', 'pijar-sakolaan' ),
			'empty_text'  => __( 'Informasi fasilitas sekolah akan tampil di sini.', 'pijar-sakolaan' ),
		),
		'program_unggulan' => array(
			'label'       => __( 'Program Unggulan', 'pijar-sakolaan' ),
			'filter'      => '',
			'card'        => 'program',
			'min'         => '18rem',
			'empty_title' => __( 'Program unggulan belum tersedia', 'pijar-sakolaan' ),
			'empty_text'  => __( 'Program unggulan sekolah akan tampil di sini.', 'pijar-sakolaan' ),
		),
		'testimoni'       => array(
			'label'       => __( 'Testimoni Alumni', 'pijar-sakolaan' ),
			'filter'      => '',
			'card'        => 'testimoni',
			'min'         => '18rem',
			'empty_title' => __( 'Testimoni alumni belum tersedia', 'pijar-sakolaan' ),
			'empty_text'  => __( 'Cerita alumni akan tampil di sini.', 'pijar-sakolaan' ),
		),
	);

	/**
	 * Filter konfigurasi arsip.
	 *
	 * @param array $configs Konfigurasi per post type.
	 */
	return apply_filters( 'pijar_sakolaan_archive_configs', $configs );
}

/**
 * Encode atribut block ke JSON untuk block comment.
 *
 * @param array $attrs Atribut.
 * @return string
 */
function pijar_sakolaan_block_json( $attrs ) {
	$json = wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP );

	// Sama seperti serialisasi block core: hindari "--" yang menutup komentar HTML.
	return str_replace( '--', '\\u002d\\u002d', (string) $json );
}

/**
 * Markup isi arsip: filter taxonomy, Query Loop berisi card, pagination, empty state.
 *
 * @param string $post_type Post type.
 * @param array  $config    Konfigurasi arsip.
 * @return string
 */
function pijar_sakolaan_build_archive_markup( $post_type, $config ) {
	$query = array(
		'perPage' => min( 48, max( 1, absint( pijar_sakolaan_get_setting( 'display.archive_per_page', 12 ) ) ) ),
		'pages'   => 0,
		'offset'  => 0,
		'postType' => $post_type,
		'order'   => 'desc',
		'orderBy' => 'date',
		'author'  => '',
		'search'  => '',
		'exclude' => array(),
		'sticky'  => '',
		'inherit' => true,
	);

	$markup  = '<!-- wp:heading {"className":"sakolaan-visually-hidden"} -->' . "\n";
	$markup .= '<h2 class="wp-block-heading sakolaan-visually-hidden">' . esc_html(
		/* translators: %s: nama konten, misalnya Berita. */
		sprintf( __( 'Daftar %s', 'pijar-sakolaan' ), $config['label'] )
	) . '</h2>' . "\n";
	$markup .= "<!-- /wp:heading -->\n\n";
	$markup .= '<!-- wp:group ' . pijar_sakolaan_block_json( array( 'align' => 'wide', 'className' => 'sakolaan-archive-toolbar', 'layout' => array( 'type' => 'default' ) ) ) . " -->\n";
	$markup .= '<div class="wp-block-group alignwide sakolaan-archive-toolbar">';
	$markup .= '<!-- wp:pijar-sakolaan/archive-controls ' . pijar_sakolaan_block_json( array( 'postType' => $post_type ) ) . " /-->\n\n";

	if ( ! empty( $config['filter'] ) ) {
		$markup .= '<!-- wp:pijar-sakolaan/term-filter ' . pijar_sakolaan_block_json( array( 'taxonomy' => $config['filter'] ) ) . " /-->\n\n";
	}

	$markup .= "</div>\n<!-- /wp:group -->\n\n";

	$markup .= '<!-- wp:query ' . pijar_sakolaan_block_json(
		array(
			'queryId' => 0,
			'query'   => $query,
			'align'   => 'wide',
			'className' => 'sakolaan-listing sakolaan-listing--archive sakolaan-listing--' . $post_type,
		)
	) . " -->\n";
	$markup .= '<div class="wp-block-query alignwide sakolaan-listing sakolaan-listing--archive sakolaan-listing--' . esc_attr( $post_type ) . '">';
	$markup .= '<!-- wp:post-template ' . pijar_sakolaan_block_json(
		array(
			'layout' => array(
				'type'              => 'grid',
				'minimumColumnWidth' => $config['min'],
			),
		)
	) . " -->\n";
	$markup .= '<!-- wp:pattern ' . pijar_sakolaan_block_json( array( 'slug' => 'pijar-sakolaan/card-' . $config['card'] ) ) . " /-->\n";
	$markup .= "<!-- /wp:post-template -->\n\n";
	// Embed pagination blocks so they retain the active Query Loop context.
	$markup .= '<!-- wp:query-pagination {"paginationArrow":"arrow","layout":{"type":"flex","justifyContent":"center"}} -->' . "\n";
	$markup .= '<!-- wp:query-pagination-previous /-->' . "\n";
	$markup .= '<!-- wp:query-pagination-numbers /-->' . "\n";
	$markup .= '<!-- wp:query-pagination-next /-->' . "\n";
	$markup .= '<!-- /wp:query-pagination -->' . "\n\n";
	$markup .= "<!-- wp:query-no-results -->\n";
	$markup .= '<!-- wp:pattern ' . pijar_sakolaan_block_json( array( 'slug' => 'pijar-sakolaan/empty-' . $post_type ) ) . " /-->\n";
	$markup .= "<!-- /wp:query-no-results --></div>\n";
	$markup .= '<!-- /wp:query -->';

	return $markup;
}

/** Return a bounded homepage item count while retaining the legacy count as default. */
function pijar_sakolaan_homepage_item_count( $section, $default ) {
	$key   = 'sections.items_' . sanitize_key( $section );
	$count = absint( pijar_sakolaan_get_setting( $key, $default ) );
	return min( 12, max( 1, $count ? $count : (int) $default ) );
}

/**
 * Markup empty state.
 *
 * @param string $title Judul.
 * @param string $text  Teks penjelas.
 * @return string
 */
function pijar_sakolaan_build_empty_markup( $title, $text ) {
	return '<!-- wp:group {"className":"sakolaan-empty","layout":{"type":"constrained"}} -->' . "\n"
		. '<div class="wp-block-group sakolaan-empty"><!-- wp:heading {"level":2,"fontSize":"xl"} -->' . "\n"
		. '<h2 class="wp-block-heading has-xl-font-size">' . esc_html( $title ) . '</h2>' . "\n"
		. '<!-- /wp:heading -->' . "\n\n"
		. '<!-- wp:paragraph {"className":"sakolaan-text-muted"} -->' . "\n"
		. '<p class="sakolaan-text-muted">' . esc_html( $text ) . '</p>' . "\n"
		. '<!-- /wp:paragraph --></div>' . "\n"
		. '<!-- /wp:group -->';
}

/**
 * Daftarkan pattern hasil generator.
 *
 * @return void
 */
function pijar_sakolaan_register_generated_patterns() {
	if ( ! pijar_sakolaan_is_compatible() ) {
		return;
	}

	foreach ( pijar_sakolaan_get_archive_configs() as $post_type => $config ) {
		register_block_pattern(
			'pijar-sakolaan/archive-' . $post_type,
			array(
				/* translators: %s: nama konten, misalnya Berita. */
				'title'      => sprintf( __( 'Daftar %s', 'pijar-sakolaan' ), $config['label'] ),
				'inserter'   => false,
				'categories' => array( 'sakolaan-sections' ),
				'content'    => pijar_sakolaan_build_archive_markup( $post_type, $config ),
			)
		);

		register_block_pattern(
			'pijar-sakolaan/empty-' . $post_type,
			array(
				/* translators: %s: nama konten, misalnya Berita. */
				'title'      => sprintf( __( 'Keadaan kosong: %s', 'pijar-sakolaan' ), $config['label'] ),
				'inserter'   => false,
				'categories' => array( 'sakolaan-sections' ),
				'content'    => pijar_sakolaan_build_empty_markup( $config['empty_title'], $config['empty_text'] ),
			)
		);
	}

	$registry = WP_Block_Patterns_Registry::get_instance();

	// Tema aktif dapat menyimpan daftar file pattern sebelum file baru ditambahkan.
	if ( ! $registry->is_registered( 'pijar-sakolaan/quick-links' ) ) {
		ob_start();
		require get_theme_file_path( 'patterns/quick-links.php' );
		$content = trim( (string) ob_get_clean() );

		if ( '' !== $content ) {
			register_block_pattern(
				'pijar-sakolaan/quick-links',
				array(
					'title'       => __( 'Akses cepat', 'pijar-sakolaan' ),
					'description' => __( 'Lima tujuan utama untuk pengunjung situs sekolah.', 'pijar-sakolaan' ),
					'categories'  => array( 'sakolaan-sections' ),
					'content'     => $content,
				)
			);
		}
	}

	$new_file_patterns = array(
		'facilities'         => array( __( 'Fasilitas', 'pijar-sakolaan' ), true ),
		'featured-programs'  => array( __( 'Program Unggulan', 'pijar-sakolaan' ), true ),
		'testimonials'       => array( __( 'Testimoni Alumni', 'pijar-sakolaan' ), true ),
		'card-fasilitas'     => array( __( 'Kartu fasilitas', 'pijar-sakolaan' ), false ),
		'card-program'       => array( __( 'Kartu program unggulan', 'pijar-sakolaan' ), false ),
		'card-testimoni'     => array( __( 'Kartu testimoni alumni', 'pijar-sakolaan' ), false ),
	);

	foreach ( $new_file_patterns as $file_slug => $pattern_data ) {
		$pattern_slug = 'pijar-sakolaan/' . $file_slug;

		if ( $registry->is_registered( $pattern_slug ) ) {
			continue;
		}

		$file = get_theme_file_path( 'patterns/' . $file_slug . '.php' );

		if ( ! is_readable( $file ) ) {
			continue;
		}

		ob_start();
		require $file;
		$content = trim( (string) ob_get_clean() );

		if ( '' === $content ) {
			continue;
		}

		register_block_pattern(
			$pattern_slug,
			array(
				'title'      => $pattern_data[0],
				'inserter'   => (bool) $pattern_data[1],
				'categories' => array( 'sakolaan-sections' ),
				'content'    => $content,
			)
		);
	}
}
add_action( 'init', 'pijar_sakolaan_register_generated_patterns', 20 );

/**
 * URL arsip post type, atau string kosong jika tipe konten tidak ada.
 *
 * @param string $post_type Post type.
 * @return string
 */
function pijar_sakolaan_get_archive_url( $post_type ) {
	$url = get_post_type_archive_link( $post_type );

	return $url ? (string) $url : '';
}

/**
 * Markup kepala section: judul h2 dan tautan "lihat semua" (jika URL ada).
 *
 * @param string $title      Judul section.
 * @param string $url        URL tautan, boleh kosong.
 * @param string $link_label Teks tautan.
 * @return string Markup block, semua bagian sudah di-escape.
 */
function pijar_sakolaan_section_header_markup( $title, $url = '', $link_label = '' ) {
	$markup  = '<!-- wp:group {"className":"sakolaan-section-header","layout":{"type":"default"}} -->' . "\n";
	$markup .= '<div class="wp-block-group sakolaan-section-header"><!-- wp:heading -->' . "\n";
	$markup .= '<h2 class="wp-block-heading">' . esc_html( $title ) . '</h2>' . "\n";
	$markup .= '<!-- /wp:heading -->';

	if ( '' !== $url && '' !== $link_label ) {
		$markup .= "\n\n" . '<!-- wp:paragraph -->' . "\n";
		$markup .= '<p><a href="' . esc_url( $url ) . '">' . esc_html( $link_label ) . '</a></p>' . "\n";
		$markup .= '<!-- /wp:paragraph -->';
	}

	return $markup . '</div>' . "\n" . '<!-- /wp:group -->';
}

/**
 * Markup Query Loop untuk section beranda (tanpa pagination, tidak mewarisi query utama).
 *
 * Opsi: post_type, per_page, offset, orderby, order, class, card (slug pattern tanpa
 * awalan), layout (grid|list), min (lebar minimum kolom grid), empty (slug pattern
 * empty state, opsional), query_id.
 *
 * @param array $args Opsi.
 * @return string
 */
function pijar_sakolaan_build_loop_markup( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'post_type' => 'post',
			'per_page'  => 3,
			'offset'    => 0,
			'orderby'   => 'date',
			'order'     => 'desc',
			'class'     => '',
			'card'      => '',
			'layout'    => 'grid',
			'min'       => '18rem',
			'empty'     => '',
			'query_id'  => 1,
		)
	);

	$class = trim( 'sakolaan-loop ' . $args['class'] );

	$attrs = array(
		'queryId'   => (int) $args['query_id'],
		'query'     => array(
			'perPage'  => (int) $args['per_page'],
			'pages'    => 0,
			'offset'   => (int) $args['offset'],
			'postType' => $args['post_type'],
			'order'    => $args['order'],
			'orderBy'  => $args['orderby'],
			'author'   => '',
			'search'   => '',
			'exclude'  => array(),
			'sticky'   => '',
			'inherit'  => false,
		),
		'className' => $class,
	);

	$markup  = '<!-- wp:query ' . pijar_sakolaan_block_json( $attrs ) . " -->\n";
	$markup .= '<div class="wp-block-query ' . esc_attr( $class ) . '">';

	if ( 'grid' === $args['layout'] ) {
		$template_attrs = array(
			'layout' => array(
				'type'               => 'grid',
				'minimumColumnWidth' => $args['min'],
			),
		);
		$markup .= '<!-- wp:post-template ' . pijar_sakolaan_block_json( $template_attrs ) . " -->\n";
	} else {
		$markup .= "<!-- wp:post-template -->\n";
	}

	$markup .= '<!-- wp:pattern ' . pijar_sakolaan_block_json( array( 'slug' => 'pijar-sakolaan/card-' . $args['card'] ) ) . " /-->\n";
	$markup .= '<!-- /wp:post-template -->';

	if ( '' !== $args['empty'] ) {
		$markup .= "\n\n<!-- wp:query-no-results -->\n";
		$markup .= '<!-- wp:pattern ' . pijar_sakolaan_block_json( array( 'slug' => 'pijar-sakolaan/empty-' . $args['empty'] ) ) . " /-->\n";
		$markup .= '<!-- /wp:query-no-results -->';
	}

	return $markup . "</div>\n<!-- /wp:query -->";
}
