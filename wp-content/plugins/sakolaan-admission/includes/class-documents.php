<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

final class Documents {
	private const SAFE_MIMES = array( 'application/pdf', 'image/jpeg', 'image/png', 'image/webp' );

	public static function register(): void {
		add_action( 'admin_post_sakolaan_admission_download', array( __CLASS__, 'download' ) );
	}

	public static function storage_path() {
		$default    = dirname( rtrim( ABSPATH, '/\\' ) ) . DIRECTORY_SEPARATOR . 'sakolaan-admission-private';
		$path       = defined( 'SAKOLAAN_ADMISSION_PRIVATE_DIR' ) ? SAKOLAAN_ADMISSION_PRIVATE_DIR : $default;
		$path       = (string) apply_filters( 'sakolaan_admission_private_storage_path', $path );
		$normalized = wp_normalize_path( $path );
		if ( '' === $normalized || str_starts_with( trailingslashit( $normalized ), trailingslashit( wp_normalize_path( ABSPATH ) ) ) ) {
			return new \WP_Error( 'unsafe_storage', __( 'Penyimpanan privat harus berada di luar direktori publik WordPress.', 'sakolaan-admission' ) );
		}
		if ( ! is_dir( $path ) && ! wp_mkdir_p( $path ) ) {
			return new \WP_Error( 'storage_unavailable', __( 'Penyimpanan privat tidak dapat dibuat.', 'sakolaan-admission' ) ); }
		if ( ! is_writable( $path ) ) {
			return new \WP_Error( 'storage_unwritable', __( 'Penyimpanan privat tidak dapat ditulisi.', 'sakolaan-admission' ) ); }
		return untrailingslashit( $path );
	}

	public static function store_upload( int $application_id, array $file, ?int $requirement_id = null, string $kind = 'application', bool $allow_revision = false ) {
		$app   = Repository::application_owned_by_user( $application_id, get_current_user_id() );
		$staff = false;
		if ( ! $app ) {
			$app   = Repository::get( 'applications', $application_id );
			$staff = $app && Access::can_access_institution( (int) $app['institution_id'] ) && current_user_can( 'sakolaan_admission_manage_applications' );
		}
		if ( ! $app || ( ! $staff && 'draft' !== $app['status'] && 'payment_evidence' !== $kind && ! $allow_revision ) ) {
			return new \WP_Error( 'forbidden', __( 'Anda tidak dapat mengunggah berkas untuk pendaftaran ini.', 'sakolaan-admission' ), array( 'status' => 403 ) ); }
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new \WP_Error( 'invalid_upload', __( 'Pengunggahan berkas tidak selesai.', 'sakolaan-admission' ) ); }
		$settings = (array) get_option( 'sakolaan_admission_settings', array() );
		$max      = max( 1024, (int) ( $settings['max_upload_size'] ?? 5242880 ) );
		if ( (int) $file['size'] < 1 || (int) $file['size'] > $max ) {
			return new \WP_Error( 'file_too_large', __( 'Ukuran berkas melebihi batas yang diizinkan.', 'sakolaan-admission' ) ); }
		$finfo   = new \finfo( FILEINFO_MIME_TYPE );
		$mime    = (string) $finfo->file( $file['tmp_name'] );
		$allowed = self::SAFE_MIMES;
		if ( $requirement_id ) {
			$req = Repository::get( 'document_requirements', $requirement_id, (int) $app['institution_id'] );
			if ( ! $req || (int) $req['program_id'] !== (int) $app['program_id'] ) {
				return new \WP_Error( 'invalid_requirement', __( 'Persyaratan dokumen tidak valid.', 'sakolaan-admission' ) ); }
			$allowed = array_values( array_intersect( $allowed, array_map( 'trim', explode( ',', $req['allowed_mimes'] ) ) ) );
			if ( (int) $file['size'] > (int) $req['max_size'] ) {
				return new \WP_Error( 'file_too_large', __( 'Ukuran berkas melebihi batas untuk dokumen ini.', 'sakolaan-admission' ) ); }
		}
		if ( ! in_array( $mime, $allowed, true ) ) {
			return new \WP_Error( 'invalid_mime', __( 'Jenis berkas ini tidak diizinkan.', 'sakolaan-admission' ) ); }
		$check = wp_check_filetype_and_ext( $file['tmp_name'], sanitize_file_name( $file['name'] ) );
		if ( empty( $check['ext'] ) || empty( $check['type'] ) || $check['type'] !== $mime ) {
			return new \WP_Error( 'mime_mismatch', __( 'Ekstensi nama berkas tidak sesuai dengan isi berkas.', 'sakolaan-admission' ) ); }
		$scan = apply_filters( 'sakolaan_admission_scan_file', true, $file['tmp_name'], $mime, $app );
		if ( is_wp_error( $scan ) || false === $scan ) {
			return is_wp_error( $scan ) ? $scan : new \WP_Error( 'scan_failed', __( 'Berkas tidak lolos pemeriksaan keamanan.', 'sakolaan-admission' ) ); }
		$base = self::storage_path();
		if ( is_wp_error( $base ) ) {
			return $base; }
		$relative = gmdate( 'Y/m' ) . '/' . wp_generate_uuid4() . '.' . $check['ext'];
		$target   = $base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $relative );
		if ( ! wp_mkdir_p( dirname( $target ) ) || ! move_uploaded_file( $file['tmp_name'], $target ) ) {
			return new \WP_Error( 'store_failed', __( 'Berkas privat tidak dapat disimpan.', 'sakolaan-admission' ) ); }
		$id = Repository::insert(
			'documents',
			array(
				'institution_id' => $app['institution_id'],
				'application_id' => $application_id,
				'requirement_id' => $requirement_id,
				'kind'           => sanitize_key( $kind ),
				'storage_key'    => $relative,
				'original_name'  => sanitize_file_name( $file['name'] ),
				'mime_type'      => $mime,
				'file_size'      => filesize( $target ),
				'sha256'         => hash_file( 'sha256', $target ),
				'status'         => 'pending',
				'created_at'     => current_time( 'mysql', true ),
				'updated_at'     => current_time( 'mysql', true ),
			)
		);
		if ( ! $id ) {
			wp_delete_file( $target );
			return new \WP_Error( 'record_failed', __( 'Data dokumen tidak dapat dibuat.', 'sakolaan-admission' ) ); }
		Repository::audit(
			'document_uploaded',
			'document',
			$id,
			(int) $app['institution_id'],
			array(
				'kind'  => $kind,
				'mime'  => $mime,
				'bytes' => (int) $file['size'],
			)
		);
		return Repository::get( 'documents', $id, (int) $app['institution_id'] );
	}

	public static function review( int $document_id, string $status, string $notes = '' ) {
		if ( ! in_array( $status, array( 'verified', 'rejected', 'revision_requested' ), true ) ) {
			return new \WP_Error( 'invalid_status', __( 'Status dokumen tidak valid.', 'sakolaan-admission' ) ); }
		$document = Repository::get( 'documents', $document_id );
		if ( ! $document || ! current_user_can( 'sakolaan_admission_verify_documents' ) || ! Access::can_access_institution( (int) $document['institution_id'] ) ) {
			return new \WP_Error( 'forbidden', __( 'Akses ditolak.', 'sakolaan-admission' ), array( 'status' => 403 ) ); }
		Repository::update(
			'documents',
			$document_id,
			array(
				'status'         => $status,
				'reviewer_id'    => get_current_user_id(),
				'reviewer_notes' => sanitize_textarea_field( $notes ),
				'reviewed_at'    => current_time( 'mysql', true ),
				'updated_at'     => current_time( 'mysql', true ),
			),
			(int) $document['institution_id']
		);
		Repository::audit(
			'document_reviewed',
			'document',
			$document_id,
			(int) $document['institution_id'],
			array(
				'from' => $document['status'],
				'to'   => $status,
			)
		);
		do_action( 'sakolaan_admission_document_reviewed', $document_id, $status );
		return Repository::get( 'documents', $document_id, (int) $document['institution_id'] );
	}

	public static function download(): void {
		$document_id = isset( $_GET['document_id'] ) ? absint( $_GET['document_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( 'sakolaan_admission_download_' . $document_id );
		$document  = Repository::get( 'documents', $document_id );
		$app_owned = $document ? Repository::application_owned_by_user( (int) $document['application_id'], get_current_user_id() ) : null;
		$staff     = $document && Access::can_access_institution( (int) $document['institution_id'] ) && ( current_user_can( 'sakolaan_admission_verify_documents' ) || current_user_can( 'sakolaan_admission_verify_payments' ) );
		if ( ! $document || ( ! $app_owned && ! $staff ) ) {
			status_header( 403 );
			exit; }
		$base = self::storage_path();
		if ( is_wp_error( $base ) || ! preg_match( '#^[0-9]{4}/[0-9]{2}/[a-f0-9-]+\.[a-z0-9]+$#', $document['storage_key'] ) ) {
			status_header( 404 );
			exit; }
		$path = $base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $document['storage_key'] );
		if ( ! is_file( $path ) || ! hash_equals( $document['sha256'], (string) hash_file( 'sha256', $path ) ) ) {
			status_header( 404 );
			exit; }
		nocache_headers();
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Type: ' . $document['mime_type'] );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( $document['original_name'] ) . '"' );
		Repository::audit( 'document_downloaded', 'document', $document_id, (int) $document['institution_id'] );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}
}
