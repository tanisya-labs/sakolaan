<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

final class Workflow {
	public static function default_definition( bool $simple = false ): array {
		if ( $simple ) {
			return array(
				'states'      => array( 'draft', 'submitted', 'accepted', 'enrollment_confirmed', 'rejected' ),
				'transitions' => array(
					array(
						'from'       => 'draft',
						'to'         => 'submitted',
						'capability' => 'applicant',
						'guards'     => array( 'valid_form', 'open_period' ),
					),
					array(
						'from'       => 'submitted',
						'to'         => 'accepted',
						'capability' => 'sakolaan_admission_publish_decisions',
						'guards'     => array( 'payment_resolved' ),
					),
					array(
						'from'       => 'submitted',
						'to'         => 'rejected',
						'capability' => 'sakolaan_admission_publish_decisions',
					),
					array(
						'from'       => 'accepted',
						'to'         => 'enrollment_confirmed',
						'capability' => 'sakolaan_admission_confirm_enrollment',
						'guards'     => array( 'capacity' ),
					),
				),
			);
		}
		return array(
			'states'      => array( 'draft', 'submitted', 'document_verification', 'payment_verification', 'assessment', 'decision_pending', 'accepted', 'waitlisted', 'rejected', 'enrollment_confirmed' ),
			'transitions' => array(
				array(
					'from'       => 'draft',
					'to'         => 'submitted',
					'capability' => 'applicant',
					'guards'     => array( 'valid_form', 'open_period' ),
				),
				array(
					'from'       => 'submitted',
					'to'         => 'document_verification',
					'capability' => 'sakolaan_admission_manage_applications',
				),
				array(
					'from'       => 'document_verification',
					'to'         => 'payment_verification',
					'capability' => 'sakolaan_admission_verify_documents',
					'guards'     => array( 'documents_verified' ),
				),
				array(
					'from'       => 'document_verification',
					'to'         => 'assessment',
					'capability' => 'sakolaan_admission_verify_documents',
					'guards'     => array( 'documents_verified', 'payment_not_required' ),
				),
				array(
					'from'       => 'payment_verification',
					'to'         => 'assessment',
					'capability' => 'sakolaan_admission_verify_payments',
					'guards'     => array( 'payment_verified' ),
				),
				array(
					'from'       => 'assessment',
					'to'         => 'decision_pending',
					'capability' => 'sakolaan_admission_review_assessments',
				),
				array(
					'from'       => 'decision_pending',
					'to'         => 'accepted',
					'capability' => 'sakolaan_admission_publish_decisions',
				),
				array(
					'from'       => 'decision_pending',
					'to'         => 'waitlisted',
					'capability' => 'sakolaan_admission_publish_decisions',
				),
				array(
					'from'       => 'decision_pending',
					'to'         => 'rejected',
					'capability' => 'sakolaan_admission_publish_decisions',
				),
				array(
					'from'       => 'waitlisted',
					'to'         => 'accepted',
					'capability' => 'sakolaan_admission_publish_decisions',
				),
				array(
					'from'       => 'accepted',
					'to'         => 'enrollment_confirmed',
					'capability' => 'sakolaan_admission_confirm_enrollment',
					'guards'     => array( 'capacity' ),
				),
			),
		);
	}

	public static function transition( int $application_id, string $to, string $reason = '', ?int $expected_lock = null ) {
		global $wpdb;
		$app = Repository::get( 'applications', $application_id );
		if ( ! $app ) {
			return new \WP_Error( 'application_not_found', __( 'Data pendaftaran tidak ditemukan.', 'sakolaan-admission' ), array( 'status' => 404 ) ); }
		$is_owner = (bool) Repository::application_owned_by_user( $application_id, get_current_user_id() );
		if ( ! $is_owner && ! Access::can_access_institution( (int) $app['institution_id'] ) ) {
			return new \WP_Error( 'forbidden', __( 'Akses ditolak.', 'sakolaan-admission' ), array( 'status' => 403 ) ); }
		$version    = Repository::get( 'workflow_versions', (int) $app['workflow_version_id'], (int) $app['institution_id'] );
		$definition = $version ? json_decode( $version['definition_json'], true ) : self::default_definition();
		$match      = null;
		foreach ( (array) ( $definition['transitions'] ?? array() ) as $transition ) {
			if ( $transition['from'] === $app['status'] && $transition['to'] === $to ) {
				$match = $transition;
				break; }
		}
		if ( ! $match ) {
			return new \WP_Error( 'invalid_transition', __( 'Perpindahan status ini tidak diizinkan.', 'sakolaan-admission' ), array( 'status' => 409 ) ); }
		if ( 'applicant' === $match['capability'] ) {
			if ( ! $is_owner ) {
				return new \WP_Error( 'forbidden', __( 'Hanya pendaftar yang dapat mengirim pendaftaran ini.', 'sakolaan-admission' ), array( 'status' => 403 ) ); }
		} elseif ( ! current_user_can( $match['capability'] ) || ! Access::can_access_institution( (int) $app['institution_id'] ) ) {
			return new \WP_Error( 'forbidden', __( 'Anda tidak berwenang melakukan perpindahan status ini.', 'sakolaan-admission' ), array( 'status' => 403 ) );
		}
		foreach ( (array) ( $match['guards'] ?? array() ) as $guard ) {
			$result = self::guard( $guard, $app );
			$result = apply_filters( 'sakolaan_admission_workflow_transition_guard', $result, $guard, $app, $to );
			if ( is_wp_error( $result ) ) {
				return $result; }
		}
		$reserved = false;
		if ( 'enrollment_confirmed' === $to ) {
			$reservation = self::reserve_capacity( $app );
			if ( is_wp_error( $reservation ) ) {
				return $reservation; }
			$reserved = true;
		}
		$expected_lock = null === $expected_lock ? (int) $app['lock_version'] : $expected_lock;
		$now           = current_time( 'mysql', true );
		$set_sql       = 'status = %s, lock_version = lock_version + 1, updated_at = %s';
		$args          = array( $to, $now );
		if ( 'submitted' === $to ) {
			$set_sql .= ', submitted_at = %s';
			$args[]   = $now; }
		if ( in_array( $to, array( 'accepted', 'waitlisted', 'rejected' ), true ) ) {
			$set_sql .= ', decision_published_at = %s';
			$args[]   = $now; }
		$args = array_merge( $args, array( $application_id, $app['institution_id'], $app['status'], $expected_lock ) );
		// The optional timestamp placeholders are appended to both the SQL and arguments above.
		$updated = $wpdb->query( $wpdb->prepare( 'UPDATE ' . Installer::table( 'applications' ) . " SET {$set_sql} WHERE id = %d AND institution_id = %d AND status = %s AND lock_version = %d", ...$args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		if ( 1 !== $updated ) {
			if ( $reserved ) {
				self::release_capacity( $app ); }
			return new \WP_Error( 'stale_application', __( 'Data pendaftaran telah berubah. Muat ulang halaman, lalu coba kembali.', 'sakolaan-admission' ), array( 'status' => 409 ) );
		}
		Repository::insert(
			'status_history',
			array(
				'institution_id'  => $app['institution_id'],
				'application_id'  => $application_id,
				'previous_status' => $app['status'],
				'new_status'      => $to,
				'actor_id'        => get_current_user_id() ? get_current_user_id() : null,
				'reason'          => sanitize_textarea_field( $reason ),
				'created_at'      => $now,
			)
		);
		Repository::audit(
			'application_transition',
			'application',
			$application_id,
			(int) $app['institution_id'],
			array(
				'from' => $app['status'],
				'to'   => $to,
			)
		);
		if ( 'submitted' === $to ) {
			self::create_payment_if_required( $app );
			do_action( 'sakolaan_admission_application_submitted', $application_id ); }
		if ( in_array( $to, array( 'accepted', 'waitlisted', 'rejected' ), true ) ) {
			do_action( 'sakolaan_admission_decision_published', $application_id, $to ); }
		do_action( 'sakolaan_admission_application_transitioned', $application_id, $app['status'], $to );
		return Repository::get( 'applications', $application_id );
	}

	private static function guard( string $guard, array $app ) {
		global $wpdb;
		if ( 'open_period' === $guard ) {
			$period = Repository::get( 'periods', (int) $app['period_id'], (int) $app['institution_id'] );
			$now    = time();
			if ( ! $period || 'published' !== $period['status'] || strtotime( $period['opens_at'] . ' UTC' ) > $now || strtotime( $period['closes_at'] . ' UTC' ) < $now ) {
				return new \WP_Error( 'period_closed', __( 'Periode penerimaan sudah ditutup.', 'sakolaan-admission' ) ); }
		}
		if ( 'documents_verified' === $guard ) {
			$pending = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Installer::table( 'documents' ) . " WHERE application_id = %d AND status <> 'verified' AND replaced_by IS NULL", $app['id'] ) );
			if ( $pending ) {
				return new \WP_Error( 'documents_pending', __( 'Seluruh dokumen aktif harus sudah diverifikasi.', 'sakolaan-admission' ) ); }
		}
		if ( 'payment_verified' === $guard ) {
			$status = $wpdb->get_var( $wpdb->prepare( 'SELECT status FROM ' . Installer::table( 'payments' ) . ' WHERE application_id = %d', $app['id'] ) );
			if ( 'verified' !== $status ) {
				return new \WP_Error( 'payment_pending', __( 'Pembayaran harus sudah diverifikasi.', 'sakolaan-admission' ) ); }
		}
		if ( 'payment_not_required' === $guard ) {
			$program = Repository::get( 'programs', (int) $app['program_id'], (int) $app['institution_id'] );
			if ( $program && $program['fee_required'] ) {
				return new \WP_Error( 'payment_required', __( 'Program ini mewajibkan verifikasi pembayaran.', 'sakolaan-admission' ) ); }
		}
		if ( 'payment_resolved' === $guard ) {
			$program = Repository::get( 'programs', (int) $app['program_id'], (int) $app['institution_id'] );
			if ( $program && $program['fee_required'] ) {
				$status = $wpdb->get_var( $wpdb->prepare( 'SELECT status FROM ' . Installer::table( 'payments' ) . ' WHERE application_id = %d', $app['id'] ) );
				if ( 'verified' !== $status ) {
					return new \WP_Error( 'payment_pending', __( 'Pembayaran harus diverifikasi sebelum pendaftar dinyatakan diterima.', 'sakolaan-admission' ) ); }
			}
		}
		return true;
	}

	private static function create_payment_if_required( array $app ): void {
		$program = Repository::get( 'programs', (int) $app['program_id'], (int) $app['institution_id'] );
		if ( ! $program || ! $program['fee_required'] ) {
			return; }
		Repository::insert(
			'payments',
			array(
				'institution_id'    => $app['institution_id'],
				'application_id'    => $app['id'],
				'amount'            => $program['fee_amount'],
				'currency'          => $program['currency'],
				'payment_reference' => 'PAY-' . strtoupper( wp_generate_password( 12, false, false ) ),
				'status'            => 'unpaid',
				'created_at'        => current_time( 'mysql', true ),
				'updated_at'        => current_time( 'mysql', true ),
			)
		);
	}

	public static function payment_transition( int $payment_id, string $to, string $notes = '', ?int $expected_lock = null ) {
		global $wpdb;
		$payment = Repository::get( 'payments', $payment_id );
		if ( ! $payment || ! Access::can_access_institution( (int) $payment['institution_id'] ) || ! current_user_can( 'sakolaan_admission_verify_payments' ) ) {
			return new \WP_Error( 'forbidden', __( 'Akses ditolak.', 'sakolaan-admission' ), array( 'status' => 403 ) ); }
		$allowed = array(
			'pending_verification' => array( 'verified', 'rejected' ),
			'unpaid'               => array( 'expired' ),
			'rejected'             => array( 'expired' ),
		);
		if ( ! in_array( $to, $allowed[ $payment['status'] ] ?? array(), true ) ) {
			return new \WP_Error( 'invalid_payment_transition', __( 'Perpindahan status pembayaran tidak valid.', 'sakolaan-admission' ), array( 'status' => 409 ) ); }
		$lock   = null === $expected_lock ? (int) $payment['lock_version'] : $expected_lock;
		$now    = current_time( 'mysql', true );
		$result = $wpdb->query( $wpdb->prepare( 'UPDATE ' . Installer::table( 'payments' ) . ' SET status=%s, reviewer_id=%d, reviewer_notes=%s, verified_at=%s, lock_version=lock_version+1, updated_at=%s WHERE id=%d AND institution_id=%d AND status=%s AND lock_version=%d', $to, get_current_user_id(), sanitize_textarea_field( $notes ), 'verified' === $to ? $now : null, $now, $payment_id, $payment['institution_id'], $payment['status'], $lock ) );
		if ( 1 !== $result ) {
			return new \WP_Error( 'stale_payment', __( 'Data pembayaran telah berubah pada permintaan lain.', 'sakolaan-admission' ), array( 'status' => 409 ) ); }
		Repository::audit(
			'payment_transition',
			'payment',
			$payment_id,
			(int) $payment['institution_id'],
			array(
				'from' => $payment['status'],
				'to'   => $to,
			)
		);
		do_action( 'sakolaan_admission_payment_transitioned', $payment_id, $payment['status'], $to );
		return Repository::get( 'payments', $payment_id );
	}

	private static function reserve_capacity( array $app ) {
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		$period  = Repository::get( 'periods', (int) $app['period_id'], (int) $app['institution_id'] );
		$program = Repository::get( 'programs', (int) $app['program_id'], (int) $app['institution_id'] );
		if ( ! $period || ! $program ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'capacity_missing', __( 'Konfigurasi kapasitas tidak tersedia.', 'sakolaan-admission' ) ); }
		if ( null !== $period['quota'] && (int) $period['enrolled_count'] >= (int) $period['quota'] ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'period_full', __( 'Kuota periode ini sudah penuh.', 'sakolaan-admission' ) ); }
		if ( null !== $program['quota'] && (int) $program['enrolled_count'] >= (int) $program['quota'] ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'program_full', __( 'Kuota program ini sudah penuh.', 'sakolaan-admission' ) ); }
		$inserted = Repository::insert(
			'enrollments',
			array(
				'institution_id' => $app['institution_id'],
				'application_id' => $app['id'],
				'program_id'     => $app['program_id'],
				'period_id'      => $app['period_id'],
				'confirmed_by'   => get_current_user_id(),
				'confirmed_at'   => current_time( 'mysql', true ),
			)
		);
		if ( ! $inserted ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'already_enrolled', __( 'Data pendaftaran ulang sudah tersedia.', 'sakolaan-admission' ) ); }
		$program_ok = $wpdb->query( $wpdb->prepare( 'UPDATE ' . Installer::table( 'programs' ) . ' SET enrolled_count=enrolled_count+1 WHERE id=%d AND institution_id=%d AND (quota IS NULL OR enrolled_count < quota)', $app['program_id'], $app['institution_id'] ) );
		$period_ok  = $wpdb->query( $wpdb->prepare( 'UPDATE ' . Installer::table( 'periods' ) . ' SET enrolled_count=enrolled_count+1 WHERE id=%d AND institution_id=%d AND (quota IS NULL OR enrolled_count < quota)', $app['period_id'], $app['institution_id'] ) );
		if ( 1 !== $program_ok || 1 !== $period_ok ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'capacity_race', __( 'Kuota terakhir telah digunakan oleh pendaftar lain.', 'sakolaan-admission' ) ); }
		$wpdb->query( 'COMMIT' );
		do_action( 'sakolaan_admission_enrollment_confirmed', $inserted, $app['id'] );
		return true;
	}

	private static function release_capacity( array $app ): void {
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		$deleted = $wpdb->delete(
			Installer::table( 'enrollments' ),
			array(
				'application_id' => $app['id'],
				'institution_id' => $app['institution_id'],
			)
		);
		if ( $deleted ) {
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . Installer::table( 'programs' ) . ' SET enrolled_count=GREATEST(0,enrolled_count-1) WHERE id=%d AND institution_id=%d', $app['program_id'], $app['institution_id'] ) );
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . Installer::table( 'periods' ) . ' SET enrolled_count=GREATEST(0,enrolled_count-1) WHERE id=%d AND institution_id=%d', $app['period_id'], $app['institution_id'] ) );
		}
		$wpdb->query( 'COMMIT' );
	}
}
