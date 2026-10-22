<?php
/**
 * Membaca meta konten sekolah (guru, agenda, dan seterusnya) untuk presentasi.
 *
 * Kontrak dengan Sakolaan: meta disimpan dengan awalan "_sakolaan_". Theme hanya
 * membaca; jika plugin tidak aktif, semua nilai kosong dan block terkait tidak tampil.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format tanggal Y-m-d.
 *
 * @param mixed  $ymd    Tanggal Y-m-d.
 * @param string $format "day", "month", "year", atau kosong untuk format situs.
 * @return string
 */
function pijar_sakolaan_format_ymd( $ymd, $format = '' ) {
	if ( ! is_string( $ymd ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
		return '';
	}

	$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, new DateTimeZone( 'UTC' ) );

	if ( ! $date ) {
		return '';
	}

	$formats = array(
		'day'   => 'j',
		'month' => 'M',
		'year'  => 'Y',
	);

	$php_format = isset( $formats[ $format ] ) ? $formats[ $format ] : get_option( 'date_format' );

	return wp_date( $php_format, $date->getTimestamp(), new DateTimeZone( 'UTC' ) );
}

/**
 * Ambil satu meta string.
 *
 * @param int    $post_id ID post.
 * @param string $key     Key tanpa awalan.
 * @return string
 */
function pijar_sakolaan_get_raw_meta( $post_id, $key ) {
	$value = get_post_meta( $post_id, '_sakolaan_' . $key, true );

	return is_scalar( $value ) ? trim( (string) $value ) : '';
}

/**
 * Nilai meta yang siap tampil (teks polos, belum di-escape).
 *
 * Selain key meta asli, tersedia key virtual: "waktu", "tanggal_rentang",
 * "status_label", "arsip_label", dan "lampiran_url".
 *
 * @param int    $post_id ID post.
 * @param string $key     Key meta.
 * @param string $format  Format tanggal: day, month, year (opsional).
 * @return string
 */
function pijar_sakolaan_get_meta_value( $post_id, $key, $format = '' ) {
	$post_id = (int) $post_id;

	if ( ! $post_id || ! preg_match( '/^[a-z0-9_]+$/', $key ) ) {
		return '';
	}

	switch ( $key ) {
		case 'waktu':
			$start = str_replace( ':', '.', pijar_sakolaan_get_raw_meta( $post_id, 'waktu_mulai' ) );
			$end   = str_replace( ':', '.', pijar_sakolaan_get_raw_meta( $post_id, 'waktu_selesai' ) );

			if ( '' === $start ) {
				return '';
			}

			return ( '' !== $end && $end !== $start ) ? $start . ' – ' . $end : $start;

		case 'tanggal_rentang':
			$start = pijar_sakolaan_format_ymd( pijar_sakolaan_get_raw_meta( $post_id, 'tanggal_mulai' ) );
			$end   = pijar_sakolaan_format_ymd( pijar_sakolaan_get_raw_meta( $post_id, 'tanggal_selesai' ) );

			if ( '' === $start ) {
				return '';
			}

			return ( '' !== $end && $end !== $start ) ? $start . ' – ' . $end : $start;

		case 'status_label':
			if ( ! function_exists( 'sakolaan_get_event_status' ) ) {
				return '';
			}

			$labels = sakolaan_get_event_status_labels();
			$status = sakolaan_get_event_status( $post_id );

			return isset( $labels[ $status ] ) ? $labels[ $status ] : '';

		case 'arsip_label':
			return 'arsip' === pijar_sakolaan_get_raw_meta( $post_id, 'status' ) ? __( 'Arsip', 'pijar-sakolaan' ) : '';

		case 'lampiran_url':
			$attachment_id = absint( pijar_sakolaan_get_raw_meta( $post_id, 'lampiran_id' ) );
			$url           = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';

			return $url ? $url : '';

		case 'tanggal':
		case 'tanggal_mulai':
		case 'tanggal_selesai':
			return pijar_sakolaan_format_ymd( pijar_sakolaan_get_raw_meta( $post_id, $key ), $format );

		default:
			return pijar_sakolaan_get_raw_meta( $post_id, $key );
	}
}

/**
 * Label untuk setiap key di block meta-list.
 *
 * @return array
 */
function pijar_sakolaan_get_meta_labels() {
	return array(
		'jabatan'         => __( 'Jabatan', 'pijar-sakolaan' ),
		'mapel'           => __( 'Mata pelajaran', 'pijar-sakolaan' ),
		'pendidikan'      => __( 'Pendidikan terakhir', 'pijar-sakolaan' ),
		'nip'             => __( 'NIP', 'pijar-sakolaan' ),
		'nuptk'           => __( 'NUPTK', 'pijar-sakolaan' ),
		'email'           => __( 'Email', 'pijar-sakolaan' ),
		'telepon'         => __( 'Telepon', 'pijar-sakolaan' ),
		'tanggal'         => __( 'Tanggal', 'pijar-sakolaan' ),
		'tanggal_rentang' => __( 'Tanggal', 'pijar-sakolaan' ),
		'waktu'           => __( 'Waktu', 'pijar-sakolaan' ),
		'lokasi'          => __( 'Lokasi', 'pijar-sakolaan' ),
		'status_label'    => __( 'Status', 'pijar-sakolaan' ),
		'arsip_label'     => __( 'Status', 'pijar-sakolaan' ),
		'tahun'           => __( 'Tahun', 'pijar-sakolaan' ),
		'peraih'          => __( 'Peraih', 'pijar-sakolaan' ),
		'pembina'         => __( 'Pembina', 'pijar-sakolaan' ),
		'jadwal'          => __( 'Jadwal', 'pijar-sakolaan' ),
		'sasaran'         => __( 'Sasaran', 'pijar-sakolaan' ),
		'durasi'          => __( 'Durasi', 'pijar-sakolaan' ),
		'tahun_lulus'     => __( 'Tahun lulus', 'pijar-sakolaan' ),
		'aktivitas'       => __( 'Aktivitas saat ini', 'pijar-sakolaan' ),
		'lampiran_url'    => __( 'Lampiran', 'pijar-sakolaan' ),
	);
}

/**
 * HTML nilai untuk block meta-list. Semua output sudah di-escape.
 *
 * @param string $key   Key meta.
 * @param string $value Nilai polos.
 * @return string
 */
function pijar_sakolaan_render_meta_value( $key, $value ) {
	switch ( $key ) {
		case 'email':
			$email = sanitize_email( $value );

			return $email ? '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>' : esc_html( $value );

		case 'telepon':
			$digits = preg_replace( '/[^\d+]/', '', $value );

			return $digits ? '<a href="' . esc_url( 'tel:' . $digits ) . '">' . esc_html( $value ) . '</a>' : esc_html( $value );

		case 'lampiran_url':
			return '<a href="' . esc_url( $value ) . '" download>' . esc_html__( 'Unduh berkas', 'pijar-sakolaan' ) . '</a>';

		default:
			return esc_html( $value );
	}
}
