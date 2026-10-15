<?php
/**
 * Helper tanggal dan status agenda.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Label status agenda.
 *
 * @return array
 */
function sakolaan_get_event_status_labels() {
	return array(
		'terjadwal'   => __( 'Terjadwal', 'sakolaan' ),
		'berlangsung' => __( 'Berlangsung', 'sakolaan' ),
		'selesai'     => __( 'Selesai', 'sakolaan' ),
		'ditunda'     => __( 'Ditunda', 'sakolaan' ),
		'dibatalkan'  => __( 'Dibatalkan', 'sakolaan' ),
	);
}

/**
 * Status agenda: dihitung dari tanggal, kecuali dioverride menjadi ditunda atau dibatalkan.
 *
 * @param int $post_id ID agenda.
 * @return string Salah satu key dari sakolaan_get_event_status_labels().
 */
function sakolaan_get_event_status( $post_id ) {
	$override = (string) get_post_meta( $post_id, '_sakolaan_status', true );

	if ( in_array( $override, array( 'ditunda', 'dibatalkan' ), true ) ) {
		return $override;
	}

	$start = (string) get_post_meta( $post_id, '_sakolaan_tanggal_mulai', true );
	$end   = (string) get_post_meta( $post_id, '_sakolaan_tanggal_selesai', true );

	if ( '' === $start ) {
		return 'terjadwal';
	}

	if ( '' === $end ) {
		$end = $start;
	}

	$today = wp_date( 'Y-m-d' );

	if ( $today < $start ) {
		return 'terjadwal';
	}

	return $today > $end ? 'selesai' : 'berlangsung';
}

/**
 * Format tanggal Y-m-d sesuai pengaturan situs.
 *
 * @param string $date Tanggal Y-m-d.
 * @return string Tanggal terformat, atau string kosong jika tidak valid.
 */
function sakolaan_format_date( $date ) {
	$timestamp = $date ? strtotime( $date . ' 00:00:00 UTC' ) : false;

	return $timestamp ? wp_date( get_option( 'date_format' ), $timestamp, new DateTimeZone( 'UTC' ) ) : '';
}
