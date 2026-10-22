<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Label antarmuka berbahasa Indonesia tanpa mengubah nilai data internal. */
final class Labels {
	private const STATUS = array(
		'draft'                 => 'Draf',
		'published'             => 'Terbit',
		'archived'              => 'Diarsipkan',
		'active'                => 'Aktif',
		'inactive'              => 'Tidak Aktif',
		'submitted'             => 'Dikirim',
		'document_verification' => 'Verifikasi Dokumen',
		'payment_verification'  => 'Verifikasi Pembayaran',
		'assessment'            => 'Penilaian',
		'decision_pending'      => 'Menunggu Keputusan',
		'accepted'              => 'Diterima',
		'waitlisted'            => 'Daftar Tunggu',
		'rejected'              => 'Ditolak',
		'enrollment_confirmed'  => 'Pendaftaran Ulang Terkonfirmasi',
		'not_required'          => 'Tidak Diperlukan',
		'unpaid'                => 'Belum Dibayar',
		'pending'               => 'Menunggu Verifikasi',
		'pending_verification'  => 'Menunggu Verifikasi',
		'verified'              => 'Terverifikasi',
		'revision_requested'    => 'Perlu Perbaikan',
		'expired'               => 'Kedaluwarsa',
		'queued'                => 'Dalam Antrean',
		'failed'                => 'Gagal',
		'sent'                  => 'Terkirim',
		'not_scheduled'         => 'Belum Dijadwalkan',
		'upcoming'              => 'Segera Dibuka',
		'open'                  => 'Sedang Dibuka',
		'full'                  => 'Kuota Penuh',
		'closed'                => 'Ditutup',
		'unavailable'           => 'Tidak Tersedia',
	);

	private const SECTIONS = array(
		'overview'      => 'Ringkasan',
		'institutions'  => 'Institusi',
		'programs'      => 'Program',
		'periods'       => 'Periode',
		'forms'         => 'Formulir',
		'applicants'    => 'Pendaftar',
		'documents'     => 'Dokumen',
		'payments'      => 'Pembayaran',
		'verification'  => 'Verifikasi',
		'assessment'    => 'Penilaian',
		'selection'     => 'Seleksi',
		'announcements' => 'Pengumuman',
		'reports'       => 'Laporan',
		'settings'      => 'Pengaturan',
	);

	private const COLUMNS = array(
		'id'                  => 'ID',
		'code'                => 'Kode',
		'name'                => 'Nama',
		'slug'                => 'Slug',
		'status'              => 'Status',
		'user_id'             => 'ID Pengguna',
		'assignment_role'     => 'Peran Penugasan',
		'program_id'          => 'ID Program',
		'period_id'           => 'ID Periode',
		'application_id'      => 'ID Pendaftaran',
		'application_number'  => 'Nomor Pendaftaran',
		'applicant_id'        => 'ID Pendaftar',
		'intake'              => 'Tahun/Angkatan',
		'quota'               => 'Kuota',
		'amount'              => 'Jumlah',
		'fee_amount'          => 'Biaya',
		'currency'            => 'Mata Uang',
		'payment_reference'   => 'Referensi Pembayaran',
		'lock_version'        => 'Versi Data',
		'kind'                => 'Jenis',
		'original_name'       => 'Nama Berkas',
		'mime_type'           => 'Tipe Berkas',
		'reviewer_notes'      => 'Catatan Pemeriksa',
		'created_at'          => 'Dibuat',
		'updated_at'          => 'Diperbarui',
		'opens_at'            => 'Dibuka',
		'closes_at'           => 'Ditutup',
		'published_at'        => 'Diterbitkan',
		'submitted_at'        => 'Dikirim',
		'due_at'              => 'Jatuh Tempo',
		'total'               => 'Jumlah',
		'program'             => 'Program',
		'period'              => 'Periode',
		'item'                => 'Rincian',
		'title'               => 'Judul',
		'effective_status'    => 'Status Efektif',
		'actions'             => 'Tindakan',
		'current_version_id'  => 'ID Versi Aktif',
		'recommendation'      => 'Rekomendasi',
		'total_score'         => 'Nilai Total',
		'reviewer_id'         => 'ID Penilai',
		'document_key'        => 'Kunci Dokumen',
		'label'               => 'Nama Dokumen',
		'required'            => 'Wajib',
		'allowed_mimes'       => 'Format yang Diizinkan',
		'max_size'            => 'Ukuran Maksimum (byte)',
		'sort_order'          => 'Urutan',
		'payment_due_at'      => 'Tenggat Pembayaran',
		'form_id'             => 'ID Formulir',
		'workflow_id'         => 'ID Alur',
		'form_version_id'     => 'ID Versi Formulir',
		'workflow_version_id' => 'ID Versi Alur',
		'rubric_version_id'   => 'ID Versi Rubrik',
	);

	public static function status( ?string $value ): string {
		$value = (string) $value;
		return self::STATUS[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}

	public static function section( string $value ): string {
		return self::SECTIONS[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}

	public static function column( string $value ): string {
		return self::COLUMNS[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}

	public static function role( string $value ): string {
		$roles = array(
			'administrator'       => 'Administrator Institusi',
			'officer'             => 'Petugas Penerimaan',
			'document_verifier'   => 'Pemeriksa Dokumen',
			'payment_verifier'    => 'Pemeriksa Pembayaran',
			'assessment_reviewer' => 'Penilai',
		);
		return $roles[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}

	public static function badge_class( string $value ): string {
		if ( in_array( $value, array( 'active', 'published', 'verified', 'accepted', 'enrollment_confirmed', 'sent', 'open' ), true ) ) {
			return 'is-success';
		}
		if ( in_array( $value, array( 'rejected', 'expired', 'failed', 'inactive', 'closed', 'unavailable' ), true ) ) {
			return 'is-danger';
		}
		if ( in_array( $value, array( 'waitlisted', 'revision_requested', 'unpaid', 'upcoming', 'full', 'not_scheduled' ), true ) ) {
			return 'is-warning';
		}
		return 'is-info';
	}

	public static function number( float $value, ?int $decimals = null ): string {
		$decimals = null === $decimals ? ( (float) (int) $value === $value ? 0 : 2 ) : $decimals;
		return number_format( $value, $decimals, ',', '.' );
	}

	public static function currency( float $value, string $currency = 'IDR' ): string {
		return trim( strtoupper( sanitize_key( $currency ) ) . ' ' . self::number( $value ) );
	}

	public static function date( ?string $value, bool $with_time = true ): string {
		if ( ! $value ) {
			return '—';
		}
		$timestamp = strtotime( $value . ( preg_match( '/(?:Z|[+-]\d{2}:?\d{2})$/', $value ) ? '' : ' UTC' ) );
		if ( false === $timestamp ) {
			return '—';
		}
		$parts  = explode( '|', wp_date( 'j|n|Y|H:i', $timestamp ) );
		$months = array(
			1 => 'Januari',
			'Februari',
			'Maret',
			'April',
			'Mei',
			'Juni',
			'Juli',
			'Agustus',
			'September',
			'Oktober',
			'November',
			'Desember',
		);
		$date   = $parts[0] . ' ' . $months[ (int) $parts[1] ] . ' ' . $parts[2];
		return $with_time ? $date . ', ' . $parts[3] : $date;
	}
}
