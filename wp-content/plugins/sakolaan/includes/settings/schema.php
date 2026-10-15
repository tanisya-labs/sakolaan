<?php
/**
 * Skema Pengaturan Sakolaan. Satu option array: sakolaan_settings[group][key].
 *
 * Key diakses lewat sakolaan_get_setting( 'group.key' ). Kontrak dengan Sakolaan Theme:
 * identity.*, contact.*, social.*, ppdb.url, ppdb.cta_label, footer.description, footer.copyright.
 *
 * Tipe field: text, textarea, url, email, number, checkbox, select, image,
 * dan section_order.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Label section beranda dalam urutan bawaan.
 *
 * @return array<string,string>
 */
function sakolaan_get_homepage_section_labels() {
	return array(
		'quick_links'     => __( 'Akses cepat', 'sakolaan' ),
		'introduction'    => __( 'Pengenalan sekolah', 'sakolaan' ),
		'news'            => __( 'Berita terbaru', 'sakolaan' ),
		'announcements'   => __( 'Pengumuman', 'sakolaan' ),
		'events'          => __( 'Agenda mendatang', 'sakolaan' ),
		'statistics'      => __( 'Statistik sekolah', 'sakolaan' ),
		'principal'       => __( 'Sambutan kepala sekolah', 'sakolaan' ),
		'achievements'    => __( 'Prestasi', 'sakolaan' ),
		'facilities'      => __( 'Fasilitas', 'sakolaan' ),
		'programs'        => __( 'Program Unggulan', 'sakolaan' ),
		'extracurricular' => __( 'Ekstrakurikuler', 'sakolaan' ),
		'testimonials'    => __( 'Testimoni Alumni', 'sakolaan' ),
		'gallery'         => __( 'Galeri', 'sakolaan' ),
		'ppdb'            => __( 'Ajakan PPDB', 'sakolaan' ),
	);
}

/**
 * Skema pengaturan.
 *
 * @return array
 */
function sakolaan_get_settings_schema() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$stats = array();

	for ( $i = 1; $i <= 5; $i++ ) {
		$stats[ 'stat_' . $i . '_value' ] = array(
			/* translators: %d: nomor statistik. */
			'label' => sprintf( __( 'Statistik %d: angka', 'sakolaan' ), $i ),
			'type'  => 'text',
		);
		$stats[ 'stat_' . $i . '_label' ] = array(
			/* translators: %d: nomor statistik. */
			'label' => sprintf( __( 'Statistik %d: keterangan', 'sakolaan' ), $i ),
			'type'  => 'text',
		);
	}

	$sections = array();

	$section_labels = sakolaan_get_homepage_section_labels();

	foreach ( $section_labels as $slug => $label ) {
		$sections[ 'show_' . $slug ] = array(
			'label'   => $label,
			'type'    => 'checkbox',
			'default' => true,
		);
	}

	$sections['order'] = array(
		'label'   => __( 'Urutan section', 'sakolaan' ),
		'type'    => 'section_order',
		'default' => array_keys( $section_labels ),
	);

	$home_counts = array(
		'news'            => 6,
		'announcements'   => 4,
		'events'          => 3,
		'achievements'    => 3,
		'facilities'      => 6,
		'programs'        => 4,
		'extracurricular' => 6,
		'testimonials'    => 3,
		'gallery'         => 6,
	);

	foreach ( $home_counts as $section => $count ) {
		$sections[ 'items_' . $section ] = array(
			/* translators: %s: homepage section label. */
			'label'   => sprintf( __( 'Jumlah %s di beranda', 'sakolaan' ), sakolaan_get_homepage_section_labels()[ $section ] ),
			'type'    => 'number',
			'default' => $count,
			'min'     => 1,
			'max'     => 12,
			'step'    => 1,
		);
	}

	$schema = array(
		'identity'  => array(
			'label'  => __( 'Identitas', 'sakolaan' ),
			'intro'  => __( 'Nama, tagline, logo, dan favicon disinkronkan ke Pengaturan Umum WordPress saat disimpan.', 'sakolaan' ),
			'fields' => array(
				'name'          => array( 'label' => __( 'Nama sekolah', 'sakolaan' ), 'type' => 'text' ),
				'short_name'    => array( 'label' => __( 'Nama pendek', 'sakolaan' ), 'type' => 'text' ),
				'tagline'       => array( 'label' => __( 'Tagline', 'sakolaan' ), 'type' => 'text' ),
				'logo'          => array( 'label' => __( 'Logo', 'sakolaan' ), 'type' => 'image' ),
				'favicon'       => array(
					'label'       => __( 'Favicon', 'sakolaan' ),
					'type'        => 'image',
					'description' => __( 'Gambar persegi, minimal 512 x 512 piksel.', 'sakolaan' ),
				),
				'npsn'          => array( 'label' => __( 'NPSN', 'sakolaan' ), 'type' => 'text' ),
				'accreditation' => array( 'label' => __( 'Status akreditasi', 'sakolaan' ), 'type' => 'text' ),
				'founded_year'  => array( 'label' => __( 'Tahun berdiri', 'sakolaan' ), 'type' => 'number' ),
			),
		),
		'contact'   => array(
			'label'  => __( 'Kontak', 'sakolaan' ),
			'fields' => array(
				'address'  => array( 'label' => __( 'Alamat', 'sakolaan' ), 'type' => 'textarea' ),
				'phone'    => array( 'label' => __( 'Nomor telepon', 'sakolaan' ), 'type' => 'text' ),
				'email'    => array( 'label' => __( 'Email', 'sakolaan' ), 'type' => 'email' ),
				'whatsapp' => array(
					'label'       => __( 'WhatsApp', 'sakolaan' ),
					'type'        => 'text',
					'description' => __( 'Nomor saja, misalnya 081234567890. Disimpan dalam format 62.', 'sakolaan' ),
					'sanitize'    => 'sakolaan_sanitize_phone_digits',
				),
				'maps_url' => array(
					'label'       => __( 'Google Maps', 'sakolaan' ),
					'type'        => 'text',
					'description' => __( 'Di Google Maps pilih Bagikan, lalu Sematkan peta, dan tempel kodenya di sini. Hanya alamat peta yang disimpan.', 'sakolaan' ),
					'sanitize'    => 'sakolaan_sanitize_maps_url',
				),
			),
		),
		'social'    => array(
			'label'  => __( 'Media sosial', 'sakolaan' ),
			'fields' => array(
				'facebook'  => array( 'label' => __( 'Facebook', 'sakolaan' ), 'type' => 'url' ),
				'instagram' => array( 'label' => __( 'Instagram', 'sakolaan' ), 'type' => 'url' ),
				'youtube'   => array( 'label' => __( 'YouTube', 'sakolaan' ), 'type' => 'url' ),
				'tiktok'    => array( 'label' => __( 'TikTok', 'sakolaan' ), 'type' => 'url' ),
			),
		),
		'homepage'  => array(
			'label'  => __( 'Beranda', 'sakolaan' ),
			'fields' => array_merge(
				array(
					'quick_links'       => array(
						'label'       => __( 'Akses cepat', 'sakolaan' ),
						'type'        => 'quick_links',
						'default'     => false,
						'description' => __( 'Kosongkan daftar untuk menyembunyikan akses cepat. Pada instalasi lama, tautan bawaan tetap digunakan hingga pengaturan ini disimpan.', 'sakolaan' ),
					),
					'hero_mode'         => array(
						'label'   => __( 'Mode hero', 'sakolaan' ),
						'type'    => 'select',
						'default' => 'single',
						'description' => __( 'Untuk mode Slider, tambahkan dan urutkan konten melalui menu Slide Hero.', 'sakolaan' ),
						'options' => array(
							'single' => __( 'Satu gambar', 'sakolaan' ),
							'slider' => __( 'Slider', 'sakolaan' ),
						),
					),
					'hero_autoplay'     => array(
						'label'       => __( 'Putar slider otomatis', 'sakolaan' ),
						'type'        => 'checkbox',
						'default'     => true,
						'description' => __( 'Tidak dijalankan bagi pengunjung yang memilih reduced motion.', 'sakolaan' ),
					),
					'hero_interval'     => array(
						'label'       => __( 'Jeda perpindahan slide', 'sakolaan' ),
						'type'        => 'number',
						'default'     => 6,
						'min'         => 3,
						'max'         => 10,
						'step'        => 1,
						'description' => __( 'Dalam detik.', 'sakolaan' ),
					),
					'hero_title'        => array( 'label' => __( 'Judul hero', 'sakolaan' ), 'type' => 'text' ),
					'hero_heading_width' => array(
						'label'       => __( 'Lebar nama sekolah di hero', 'sakolaan' ),
						'type'        => 'number',
						'default'     => 24,
						'min'         => 12,
						'max'         => 40,
						'step'        => 1,
						'description' => __( 'Lebar maksimum dalam jumlah karakter. Naikkan nilainya agar nama sekolah memakai lebih sedikit baris.', 'sakolaan' ),
					),
					'hero_description'  => array( 'label' => __( 'Deskripsi hero', 'sakolaan' ), 'type' => 'textarea' ),
					'hero_image'        => array( 'label' => __( 'Gambar hero', 'sakolaan' ), 'type' => 'image' ),
					'hero_cta_label'    => array( 'label' => __( 'Tombol utama: teks', 'sakolaan' ), 'type' => 'text' ),
					'hero_cta_url'      => array( 'label' => __( 'Tombol utama: tautan', 'sakolaan' ), 'type' => 'url' ),
					'hero_cta2_label'   => array( 'label' => __( 'Tombol sekunder: teks', 'sakolaan' ), 'type' => 'text' ),
					'hero_cta2_url'     => array( 'label' => __( 'Tombol sekunder: tautan', 'sakolaan' ), 'type' => 'url' ),
					'intro_image'       => array( 'label' => __( 'Foto sekolah (pengenalan)', 'sakolaan' ), 'type' => 'image' ),
					'intro_text'        => array( 'label' => __( 'Deskripsi singkat sekolah', 'sakolaan' ), 'type' => 'textarea' ),
				),
				$stats
			),
		),
		'sections'  => array(
			'label'  => __( 'Section beranda', 'sakolaan' ),
			'fields' => $sections,
		),
		'principal' => array(
			'label'  => __( 'Kepala sekolah', 'sakolaan' ),
			'fields' => array(
				'name'     => array( 'label' => __( 'Nama', 'sakolaan' ), 'type' => 'text' ),
				'title'    => array( 'label' => __( 'Jabatan', 'sakolaan' ), 'type' => 'text' ),
				'photo'    => array( 'label' => __( 'Foto', 'sakolaan' ), 'type' => 'image' ),
				'greeting' => array( 'label' => __( 'Sambutan', 'sakolaan' ), 'type' => 'textarea' ),
			),
		),
		'ppdb'      => array(
			'label'  => __( 'PPDB', 'sakolaan' ),
			'fields' => array(
				'url'         => array(
					'label'       => __( 'Tautan PPDB', 'sakolaan' ),
					'type'        => 'url',
					'description' => __( 'Kosongkan untuk memakai halaman dengan alamat /ppdb/.', 'sakolaan' ),
				),
				'cta_label'   => array( 'label' => __( 'Teks tombol', 'sakolaan' ), 'type' => 'text' ),
				'description' => array( 'label' => __( 'Deskripsi ajakan', 'sakolaan' ), 'type' => 'textarea' ),
			),
		),
		'footer'    => array(
			'label'  => __( 'Footer', 'sakolaan' ),
			'intro'  => __( 'Alamat, telepon, email, dan media sosial di footer diambil dari menu Identitas.', 'sakolaan' ),
			'fields' => array(
				'description' => array( 'label' => __( 'Deskripsi footer', 'sakolaan' ), 'type' => 'textarea' ),
				'copyright'   => array(
					'label'       => __( 'Teks hak cipta', 'sakolaan' ),
					'type'        => 'text',
					'description' => __( 'Kosongkan untuk teks otomatis dengan tahun berjalan.', 'sakolaan' ),
				),
			),
		),
		'header'    => array(
			'label'  => __( 'Header', 'sakolaan' ),
			'intro'  => __( 'Logo dan nama sekolah berasal dari Identitas. Tombol pendaftaran berasal dari PPDB.', 'sakolaan' ),
			'fields' => array(
				'behavior' => array(
					'label'   => __( 'Perilaku header', 'sakolaan' ),
					'type'    => 'select',
					'default' => 'inherit',
					'options' => array(
						'inherit' => __( 'Ikuti pengaturan Site Editor', 'sakolaan' ),
						'static'  => __( 'Statis', 'sakolaan' ),
						'sticky'  => __( 'Lengket di atas', 'sakolaan' ),
						'smart'   => __( 'Sembunyi saat menggulir turun', 'sakolaan' ),
					),
				),
			),
		),
		'advanced'  => array(
			'label'  => __( 'Lanjutan', 'sakolaan' ),
			'fields' => array(
				'delete_data' => array(
					'label'       => __( 'Hapus pengaturan saat plugin dihapus', 'sakolaan' ),
					'type'        => 'checkbox',
					'description' => __( 'Konten seperti guru dan berita tidak pernah dihapus.', 'sakolaan' ),
				),
			),
		),
		'display'   => array(
			'label'  => __( 'Tampilan konten', 'sakolaan' ),
			'intro'  => __( 'Atur jumlah konten di beranda dan arsip. Kolom dan gaya kartu diatur melalui Site Editor.', 'sakolaan' ),
			'fields' => array(
				'archive_per_page' => array(
					'label'   => __( 'Jumlah konten per halaman arsip', 'sakolaan' ),
					'type'    => 'number',
					'default' => 12,
					'min'     => 1,
					'max'     => 48,
					'step'    => 1,
				),
			),
		),
		'seo'       => array(
			'label'  => __( 'SEO', 'sakolaan' ),
			'intro'  => __( 'Metadata ini menjadi fallback global. Nilai per halaman, konten, atau term dapat diatur dari editor masing-masing.', 'sakolaan' ),
			'fields' => array(
				'title_template' => array(
					'label'   => __( 'Pola judul SEO', 'sakolaan' ),
					'type'    => 'text',
					'default' => '%title% | %site_name%',
					/* translators: Keep the literal %title% and %site_name% template tokens unchanged. */
					'description' => __( 'Gunakan %title% dan %site_name%.', 'sakolaan' ),
				),
				'default_title' => array( 'label' => __( 'Judul fallback situs', 'sakolaan' ), 'type' => 'text' ),
				'default_description' => array( 'label' => __( 'Deskripsi fallback situs', 'sakolaan' ), 'type' => 'textarea' ),
				'default_image' => array( 'label' => __( 'Gambar sosial fallback', 'sakolaan' ), 'type' => 'image' ),
				'default_image_alt' => array( 'label' => __( 'Alt text gambar sosial fallback', 'sakolaan' ), 'type' => 'text' ),
			),
		),
	);

	/**
	 * Filter skema pengaturan.
	 *
	 * @param array $schema Skema.
	 */
	$schema = apply_filters( 'sakolaan_settings_schema', $schema );

	// Hanya di-cache setelah init agar terjemahan dan filter dari plugin lain sudah siap.
	if ( did_action( 'init' ) ) {
		$cache = $schema;
	}

	return $schema;
}
