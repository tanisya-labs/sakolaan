<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Detects the WordPress mail transport without reading or storing credentials. */
final class Mail_Diagnostics {
	private const OPTION = 'sakolaan_admission_mail_test_status';

	/**
	 * Detect the active transport from runtime signals.
	 *
	 * The optional signals argument keeps detection deterministic in tests.
	 */
	public static function detect( ?array $signals = null ): array {
		$runtime       = null === $signals;
		$signals       = $signals ?? array(
			'sendmail_path'  => (string) ini_get( 'sendmail_path' ),
			'phpmailer_init' => (bool) has_action( 'phpmailer_init' ),
			'pre_wp_mail'    => (bool) has_filter( 'pre_wp_mail' ),
		);
		$sendmail_path = strtolower( (string) ( $signals['sendmail_path'] ?? '' ) );

		if ( str_contains( $sendmail_path, 'mailpit' ) ) {
			$status = array(
				'state'            => 'development_capture',
				'transport'        => 'mailpit',
				'label'            => __( 'Mailpit (development)', 'sakolaan-admission' ),
				'detail'           => __( 'Email ditangkap oleh Mailpit pada lingkungan lokal dan tidak dikirim ke alamat eksternal.', 'sakolaan-admission' ),
				'production_ready' => false,
			);
		} elseif ( str_contains( $sendmail_path, 'mailhog' ) ) {
			$status = array(
				'state'            => 'development_capture',
				'transport'        => 'mailhog',
				'label'            => __( 'MailHog (development)', 'sakolaan-admission' ),
				'detail'           => __( 'Email ditangkap oleh MailHog pada lingkungan lokal dan tidak dikirim ke alamat eksternal.', 'sakolaan-admission' ),
				'production_ready' => false,
			);
		} elseif ( ! empty( $signals['phpmailer_init'] ) || ! empty( $signals['pre_wp_mail'] ) ) {
			$status = array(
				'state'            => 'connector_detected',
				'transport'        => ! empty( $signals['pre_wp_mail'] ) ? 'wordpress_api_hook' : 'wordpress_smtp_hook',
				'label'            => __( 'Konektor email WordPress terdeteksi', 'sakolaan-admission' ),
				'detail'           => __( 'Hook pengiriman email ditemukan, tetapi koneksi dan penerimaan transport harus diperiksa dengan email uji.', 'sakolaan-admission' ),
				'production_ready' => true,
			);
		} else {
			$status = array(
				'state'            => 'unconfigured',
				'transport'        => 'wordpress_default',
				'label'            => __( 'Konektor email belum terdeteksi', 'sakolaan-admission' ),
				'detail'           => __( 'Konfigurasikan SMTP atau layanan email melalui WordPress atau penyedia hosting sebelum membuka penerimaan.', 'sakolaan-admission' ),
				'production_ready' => false,
			);
		}

		if ( $runtime || ! empty( $signals['apply_filter'] ) ) {
			/**
			 * Override the detected mail transport status.
			 *
			 * @param array $status  Normalized transport status.
			 * @param array $signals Runtime detection signals.
			 */
			$status = apply_filters( 'sakolaan_admission_mail_transport_status', $status, $signals );
		}

		return self::normalize_transport( is_array( $status ) ? $status : array() );
	}

	/** Return the latest sanitized test record. */
	public static function last_test(): array {
		$record = get_option( self::OPTION, array() );
		if ( ! is_array( $record ) || ! in_array( $record['status'] ?? '', array( 'accepted', 'failed' ), true ) ) {
			return array();
		}
		return array(
			'tested_at' => sanitize_text_field( (string) ( $record['tested_at'] ?? '' ) ),
			'status'    => sanitize_key( (string) $record['status'] ),
			'transport' => sanitize_key( (string) ( $record['transport'] ?? '' ) ),
			'recipient' => sanitize_email( (string) ( $record['recipient'] ?? '' ) ),
			'error'     => self::sanitize_error( (string) ( $record['error'] ?? '' ) ),
		);
	}

	/** Return display status, giving the latest failed test the highest priority. */
	public static function status(): array {
		$status         = self::detect();
		$last_test      = self::last_test();
		$status['test'] = $last_test;

		if ( 'failed' === ( $last_test['status'] ?? '' ) ) {
			$status['state']  = 'test_failed';
			$status['detail'] = $last_test['error'] ? $last_test['error'] : __( 'Konektor email menolak email uji tanpa memberikan rincian kesalahan.', 'sakolaan-admission' );
		} elseif ( 'accepted' === ( $last_test['status'] ?? '' ) && ( $last_test['transport'] ?? '' ) === $status['transport'] && 'development_capture' !== $status['state'] ) {
			$status['state']  = 'test_accepted';
			$status['detail'] = __( 'Email uji diterima oleh konektor email. Hasil ini bukan bukti bahwa pesan telah sampai ke kotak masuk penerima.', 'sakolaan-admission' );
		}

		return $status;
	}

	/** Build consistent non-dismissible admin notice content. */
	public static function notice( array $status ): array {
		switch ( $status['state'] ?? 'unconfigured' ) {
			case 'test_failed':
				return array( 'error', __( 'Pengujian email terakhir gagal.', 'sakolaan-admission' ) . ' ' . (string) ( $status['detail'] ?? '' ) );
			case 'development_capture':
				return array( 'info', (string) ( $status['detail'] ?? '' ) . ' ' . __( 'Periksa pesan melalui kotak masuk development sebelum melanjutkan pengujian.', 'sakolaan-admission' ) );
			case 'connector_detected':
				return array( 'warning', __( 'Konektor email terdeteksi, tetapi belum ada pengujian yang berhasil.', 'sakolaan-admission' ) . ' ' . (string) ( $status['detail'] ?? '' ) );
			case 'test_accepted':
				return array( 'success', (string) ( $status['detail'] ?? '' ) );
			default:
				return array( 'warning', (string) ( $status['detail'] ?? '' ) );
		}
	}

	/** Store a normalized test result. */
	public static function record_test( bool $accepted, string $transport, string $recipient, string $error = '' ): array {
		$record = array(
			'tested_at' => current_time( 'mysql', true ),
			'status'    => $accepted ? 'accepted' : 'failed',
			'transport' => sanitize_key( $transport ),
			'recipient' => sanitize_email( $recipient ),
			'error'     => $accepted ? '' : self::sanitize_error( $error ),
		);
		update_option( self::OPTION, $record, false );
		return $record;
	}

	public static function sanitize_error( string $error ): string {
		$error = sanitize_text_field( $error );
		$error = preg_replace( '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[alamat disamarkan]', $error );
		$error = preg_replace( '/\b(password|passwd|api[_ -]?key|secret|token)\s*[:=]\s*[^\s,;]+/i', '$1=[disamarkan]', (string) $error );
		$error = preg_replace( '#(https?://)[^\s/@:]+:[^\s/@]+@#i', '$1[disamarkan]@', (string) $error );
		return substr( (string) $error, 0, 500 );
	}

	private static function normalize_transport( array $status ): array {
		$allowed_states = array( 'unconfigured', 'connector_detected', 'development_capture', 'test_accepted', 'test_failed' );
		$state          = sanitize_key( (string) ( $status['state'] ?? 'unconfigured' ) );
		if ( ! in_array( $state, $allowed_states, true ) ) {
			$state = 'unconfigured';
		}
		return array(
			'state'            => $state,
			'transport'        => sanitize_key( (string) ( $status['transport'] ?? 'wordpress_default' ) ),
			'label'            => sanitize_text_field( (string) ( $status['label'] ?? __( 'Konektor email belum terdeteksi', 'sakolaan-admission' ) ) ),
			'detail'           => sanitize_text_field( (string) ( $status['detail'] ?? '' ) ),
			'production_ready' => ! empty( $status['production_ready'] ),
		);
	}
}
