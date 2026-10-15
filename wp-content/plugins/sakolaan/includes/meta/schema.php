<?php
/**
 * Skema meta: satu sumber kebenaran untuk registrasi REST dan panel editor.
 *
 * Meta key disimpan dengan awalan "_sakolaan_" (disembunyikan dari UI Custom Fields).
 * Field yang sudah ada di WordPress tidak diduplikasi: nama = title, foto = featured
 * image, bio/deskripsi = editor, urutan tampilan = menu_order, penulis dan tanggal
 * berita = field native.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Skema meta per post type.
 *
 * Setiap field: label, type (string|integer|array), control (text|email|tel|date|time|
 * select|textarea|media|media-multi), default, sanitize, options, help, media_types.
 *
 * @return array
 */
function sakolaan_get_meta_schema() {
	$schema = array(
		'guru'            => array(
			'title'  => __( 'Data guru', 'sakolaan' ),
			'fields' => array(
				'nip'        => array(
					'label'    => __( 'NIP', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'nuptk'      => array(
					'label'    => __( 'NUPTK', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'jabatan'    => array(
					'label'    => __( 'Jabatan', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'mapel'      => array(
					'label'    => __( 'Mata pelajaran', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'pendidikan' => array(
					'label'    => __( 'Pendidikan terakhir', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'email'      => array(
					'label'    => __( 'Email', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'email',
					'sanitize' => 'sanitize_email',
				),
				'telepon'    => array(
					'label'    => __( 'Telepon', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'tel',
					'sanitize' => 'sanitize_text_field',
				),
			),
		),
		'pengumuman'      => array(
			'title'  => __( 'Detail pengumuman', 'sakolaan' ),
			'fields' => array(
				'tanggal'     => array(
					'label'    => __( 'Tanggal', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'date',
					'sanitize' => 'sakolaan_sanitize_date',
				),
				'status'      => array(
					'label'    => __( 'Status', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'select',
					'default'  => 'aktif',
					'sanitize' => 'sakolaan_sanitize_announcement_status',
					'options'  => array(
						'aktif' => __( 'Aktif', 'sakolaan' ),
						'arsip' => __( 'Arsip', 'sakolaan' ),
					),
				),
				'lampiran_id' => array(
					'label'       => __( 'Lampiran', 'sakolaan' ),
					'type'        => 'integer',
					'control'     => 'media',
					'default'     => 0,
					'sanitize'    => 'absint',
					'media_types' => array( 'application', 'image' ),
				),
			),
		),
		'agenda'          => array(
			'title'  => __( 'Detail agenda', 'sakolaan' ),
			'fields' => array(
				'tanggal_mulai'   => array(
					'label'    => __( 'Tanggal mulai', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'date',
					'sanitize' => 'sakolaan_sanitize_date',
				),
				'tanggal_selesai' => array(
					'label'    => __( 'Tanggal selesai', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'date',
					'help'     => __( 'Kosongkan jika agenda satu hari.', 'sakolaan' ),
					'sanitize' => 'sakolaan_sanitize_date',
				),
				'waktu_mulai'     => array(
					'label'    => __( 'Waktu mulai', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'time',
					'sanitize' => 'sakolaan_sanitize_time',
				),
				'waktu_selesai'   => array(
					'label'    => __( 'Waktu selesai', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'time',
					'sanitize' => 'sakolaan_sanitize_time',
				),
				'lokasi'          => array(
					'label'    => __( 'Lokasi', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'status'          => array(
					'label'    => __( 'Status', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'select',
					'default'  => 'otomatis',
					'help'     => __( 'Otomatis: dihitung dari tanggal.', 'sakolaan' ),
					'sanitize' => 'sakolaan_sanitize_event_status',
					'options'  => array(
						'otomatis'   => __( 'Otomatis', 'sakolaan' ),
						'ditunda'    => __( 'Ditunda', 'sakolaan' ),
						'dibatalkan' => __( 'Dibatalkan', 'sakolaan' ),
					),
				),
			),
		),
		'prestasi'        => array(
			'title'  => __( 'Detail prestasi', 'sakolaan' ),
			'fields' => array(
				'tahun'  => array(
					'label'    => __( 'Tahun', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'help'     => __( '4 digit, misalnya 2026.', 'sakolaan' ),
					'sanitize' => 'sakolaan_sanitize_year',
				),
				'peraih' => array(
					'label'    => __( 'Peraih', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'help'     => __( 'Nama siswa atau tim.', 'sakolaan' ),
					'sanitize' => 'sanitize_text_field',
				),
			),
		),
		'ekstrakurikuler' => array(
			'title'  => __( 'Detail ekstrakurikuler', 'sakolaan' ),
			'fields' => array(
				'pembina' => array(
					'label'    => __( 'Pembina', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'jadwal'  => array(
					'label'    => __( 'Jadwal', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'help'     => __( 'Misalnya: Sabtu, 14.00-16.00.', 'sakolaan' ),
					'sanitize' => 'sanitize_text_field',
				),
				'lokasi'  => array(
					'label'    => __( 'Lokasi', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
			),
		),
		'fasilitas'       => array(
			'title'  => __( 'Detail fasilitas', 'sakolaan' ),
			'fields' => array(
				'lokasi' => array(
					'label'    => __( 'Lokasi', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
			),
		),
		'program_unggulan' => array(
			'title'  => __( 'Detail program unggulan', 'sakolaan' ),
			'fields' => array(
				'sasaran' => array(
					'label'    => __( 'Sasaran program', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'durasi'  => array(
					'label'    => __( 'Durasi atau periode', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
			),
		),
		'testimoni'       => array(
			'title'  => __( 'Profil alumni', 'sakolaan' ),
			'fields' => array(
				'tahun_lulus' => array(
					'label'    => __( 'Tahun lulus', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sakolaan_sanitize_year',
				),
				'aktivitas'   => array(
					'label'    => __( 'Aktivitas atau jabatan saat ini', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
			),
		),
		'hero_slide'      => array(
			'title'  => __( 'Isi slide hero', 'sakolaan' ),
			'fields' => array(
				'eyebrow'    => array(
					'label'    => __( 'Teks kecil di atas judul', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'cta_label'  => array(
					'label'    => __( 'Tombol utama: teks', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'cta_url'    => array(
					'label'    => __( 'Tombol utama: tautan', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'url',
					'sanitize' => 'esc_url_raw',
				),
				'cta_style'  => array(
					'label'    => __( 'Tombol utama: gaya', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'select',
					'default'  => 'accent',
					'sanitize' => 'sakolaan_sanitize_hero_cta_style',
				),
				'cta2_label' => array(
					'label'    => __( 'Tombol sekunder: teks', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'text',
					'sanitize' => 'sanitize_text_field',
				),
				'cta2_url'   => array(
					'label'    => __( 'Tombol sekunder: tautan', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'url',
					'sanitize' => 'esc_url_raw',
				),
				'cta2_style' => array(
					'label'    => __( 'Tombol sekunder: gaya', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'select',
					'default'  => 'outline',
					'sanitize' => 'sakolaan_sanitize_hero_cta_style',
				),
			),
		),
		'galeri'          => array(
			'title'  => __( 'Detail galeri', 'sakolaan' ),
			'fields' => array(
				'tanggal'    => array(
					'label'    => __( 'Tanggal', 'sakolaan' ),
					'type'     => 'string',
					'control'  => 'date',
					'sanitize' => 'sakolaan_sanitize_date',
				),
				'gambar_ids' => array(
					'label'       => __( 'Gambar', 'sakolaan' ),
					'type'        => 'array',
					'control'     => 'media-multi',
					'default'     => array(),
					'sanitize'    => 'sakolaan_sanitize_ids',
					'media_types' => array( 'image' ),
				),
			),
		),
	);

	/**
	 * Filter skema meta.
	 *
	 * @param array $schema Skema meta.
	 */
	return apply_filters( 'sakolaan_meta_schema', $schema );
}
