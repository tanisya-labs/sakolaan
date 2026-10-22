<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Validation and concurrency rules for editable admission configuration. */
final class Configuration {
	private const EDITABLE_TABLES = array( 'institutions', 'programs', 'periods', 'document_requirements', 'announcements' );

	public static function effective_period( array $period, string $program_status = 'published', string $institution_status = 'active', ?int $now = null ): string {
		if ( 'published' !== ( $period['status'] ?? 'draft' ) ) {
			return 'draft';
		}
		if ( 'published' !== $program_status || 'active' !== $institution_status ) {
			return 'unavailable';
		}
		return Availability::classify(
			array(
				'institution_status' => $institution_status,
				'program_status'     => $program_status,
				'period_status'      => $period['status'],
				'program_quota'      => $period['program_quota'] ?? null,
				'program_enrolled'   => $period['program_enrolled'] ?? 0,
				'period_quota'       => $period['quota'] ?? null,
				'period_enrolled'    => $period['enrolled_count'] ?? 0,
				'opens_at'           => $period['opens_at'] ?? '',
				'closes_at'          => $period['closes_at'] ?? '',
			),
			$now
		);
	}

	public static function effective_program( array $program, array $periods, string $institution_status = 'active', ?int $now = null ): string {
		if ( 'published' !== ( $program['status'] ?? 'draft' ) ) {
			return 'draft';
		}
		if ( 'active' !== $institution_status ) {
			return 'unavailable';
		}
		$states = array();
		foreach ( $periods as $period ) {
			if ( (int) ( $period['program_id'] ?? 0 ) !== (int) ( $program['id'] ?? 0 ) ) {
				continue;
			}
			$period['program_quota']    = $program['quota'] ?? null;
			$period['program_enrolled'] = $program['enrolled_count'] ?? 0;
			$states[]                   = self::effective_period( $period, 'published', $institution_status, $now );
		}
		foreach ( array( 'open', 'upcoming', 'full', 'closed' ) as $preferred ) {
			if ( in_array( $preferred, $states, true ) ) {
				return $preferred;
			}
		}
		return 'not_scheduled';
	}

	public static function validate_program_publication( array $program, ?array $institution, ?array $form, ?array $workflow ) {
		if ( ! $institution || 'active' !== $institution['status'] ) {
			return new \WP_Error( 'institution_inactive', __( 'Aktifkan institusi sebelum menerbitkan program.', 'sakolaan-admission' ) );
		}
		if ( ! $form || 'published' !== $form['status'] || empty( $form['current_version_id'] ) ) {
			return new \WP_Error( 'form_unpublished', __( 'Pilih formulir yang memiliki versi terbit sebelum menerbitkan program.', 'sakolaan-admission' ) );
		}
		if ( ! $workflow || 'published' !== $workflow['status'] || empty( $workflow['current_version_id'] ) ) {
			return new \WP_Error( 'workflow_unpublished', __( 'Pilih alur penerimaan yang memiliki versi terbit sebelum menerbitkan program.', 'sakolaan-admission' ) );
		}
		if ( (int) $program['institution_id'] !== (int) $form['institution_id'] || (int) $program['institution_id'] !== (int) $workflow['institution_id'] ) {
			return new \WP_Error( 'configuration_scope', __( 'Formulir dan alur harus berasal dari institusi yang sama.', 'sakolaan-admission' ) );
		}
		return true;
	}

	public static function validate_period_publication( array $period, ?array $institution, ?array $program ) {
		if ( ! $institution || 'active' !== $institution['status'] ) {
			return new \WP_Error( 'institution_inactive', __( 'Aktifkan institusi sebelum menerbitkan periode.', 'sakolaan-admission' ) );
		}
		if ( ! $program || 'published' !== $program['status'] || (int) $program['institution_id'] !== (int) $period['institution_id'] ) {
			return new \WP_Error( 'program_unpublished', __( 'Terbitkan program induk sebelum menerbitkan periode.', 'sakolaan-admission' ) );
		}
		$opens  = strtotime( (string) ( $period['opens_at'] ?? '' ) . ' UTC' );
		$closes = strtotime( (string) ( $period['closes_at'] ?? '' ) . ' UTC' );
		if ( ! $opens || ! $closes || $opens >= $closes ) {
			return new \WP_Error( 'invalid_schedule', __( 'Tanggal penutupan harus lebih akhir daripada tanggal pembukaan.', 'sakolaan-admission' ) );
		}
		return true;
	}

	public static function validate_announcement_publication( array $announcement ) {
		if ( '' === trim( (string) ( $announcement['title'] ?? '' ) ) || '' === trim( wp_strip_all_tags( (string) ( $announcement['content'] ?? '' ) ) ) ) {
			return new \WP_Error( 'announcement_incomplete', __( 'Judul dan isi pengumuman harus diisi sebelum diterbitkan.', 'sakolaan-admission' ) );
		}
		return true;
	}

	public static function update( string $table, int $id, int $institution_id, array $data, string $expected_updated_at ) {
		if ( ! in_array( $table, self::EDITABLE_TABLES, true ) || ! $id || ! $institution_id || ! $expected_updated_at ) {
			return new \WP_Error( 'invalid_configuration', __( 'Permintaan perubahan konfigurasi tidak valid.', 'sakolaan-admission' ) );
		}
		$current = Repository::get( $table, $id, 'institutions' === $table ? null : $institution_id );
		if ( ! $current || ( 'institutions' === $table && $id !== $institution_id ) ) {
			return new \WP_Error( 'configuration_not_found', __( 'Data konfigurasi tidak ditemukan pada institusi ini.', 'sakolaan-admission' ) );
		}
		if ( ! hash_equals( (string) $current['updated_at'], $expected_updated_at ) ) {
			return new \WP_Error( 'stale_configuration', __( 'Data telah diubah oleh pengguna lain. Muat ulang halaman sebelum menyimpan kembali.', 'sakolaan-admission' ) );
		}
		$updated_at = current_time( 'mysql', true );
		if ( $updated_at <= $expected_updated_at ) {
			$updated_at = gmdate( 'Y-m-d H:i:s', strtotime( $expected_updated_at . ' UTC' ) + 1 );
		}
		$data['updated_at'] = $updated_at;
		global $wpdb;
		$where = array(
			'id'         => $id,
			'updated_at' => $expected_updated_at,
		);
		if ( 'institutions' !== $table ) {
			$where['institution_id'] = $institution_id;
		}
		$updated = $wpdb->update( Installer::table( $table ), $data, $where );
		if ( false === $updated ) {
			return new \WP_Error( 'configuration_update_failed', __( 'Perubahan konfigurasi tidak dapat disimpan.', 'sakolaan-admission' ) );
		}
		if ( 0 === $updated ) {
			$fresh = Repository::get( $table, $id, 'institutions' === $table ? null : $institution_id );
			if ( ! $fresh || ! hash_equals( (string) $fresh['updated_at'], $expected_updated_at ) ) {
				return new \WP_Error( 'stale_configuration', __( 'Data telah diubah oleh pengguna lain. Muat ulang halaman sebelum menyimpan kembali.', 'sakolaan-admission' ) );
			}
		}
		return Repository::get( $table, $id, 'institutions' === $table ? null : $institution_id );
	}
}
