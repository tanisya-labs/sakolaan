<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {
	public const CAPS = array(
		'sakolaan_admission_access_admin',
		'sakolaan_admission_manage_settings',
		'sakolaan_admission_manage_institutions',
		'sakolaan_admission_manage_programs',
		'sakolaan_admission_manage_forms',
		'sakolaan_admission_manage_applications',
		'sakolaan_admission_verify_documents',
		'sakolaan_admission_verify_payments',
		'sakolaan_admission_review_assessments',
		'sakolaan_admission_publish_decisions',
		'sakolaan_admission_confirm_enrollment',
		'sakolaan_admission_manage_announcements',
		'sakolaan_admission_view_reports',
		'sakolaan_admission_view_audit',
		'sakolaan_admission_manage_privacy',
	);

	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'sakolaan_adm_' . $name;
	}

	public static function maybe_upgrade(): void {
		if ( (int) get_option( 'sakolaan_admission_schema_version', 0 ) < SAKOLAAN_ADMISSION_SCHEMA_VERSION ) {
			self::install();
		}
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$tables  = self::schema( $charset );
		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}
		self::install_roles();
		self::seed_defaults();
		update_option( 'sakolaan_admission_schema_version', SAKOLAAN_ADMISSION_SCHEMA_VERSION, false );
		update_option( 'sakolaan_admission_version', SAKOLAAN_ADMISSION_VERSION, false );
	}

	private static function schema( string $charset ): array {
		$t = static fn( string $name ): string => self::table( $name );
		return array(
			"CREATE TABLE {$t('institutions')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, code varchar(40) NOT NULL, name varchar(191) NOT NULL, slug varchar(191) NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'active', settings longtext NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL,
				PRIMARY KEY  (id), UNIQUE KEY code (code), UNIQUE KEY slug (slug), KEY status (status)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('institution_users')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, user_id bigint unsigned NOT NULL, assignment_role varchar(50) NOT NULL,
				created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY institution_user_role (institution_id,user_id,assignment_role), KEY user_id (user_id)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('forms')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, name varchar(191) NOT NULL, status varchar(20) NOT NULL DEFAULT 'draft', current_version_id bigint unsigned NULL,
				created_by bigint unsigned NOT NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), KEY institution_status (institution_id,status)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('form_versions')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, form_id bigint unsigned NOT NULL, version_no int unsigned NOT NULL, schema_json longtext NOT NULL,
				published_at datetime NULL, created_by bigint unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY form_version (form_id,version_no), KEY institution_id (institution_id)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('workflows')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, name varchar(191) NOT NULL, status varchar(20) NOT NULL DEFAULT 'draft', current_version_id bigint unsigned NULL,
				created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), KEY institution_status (institution_id,status)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('workflow_versions')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, workflow_id bigint unsigned NOT NULL, version_no int unsigned NOT NULL, definition_json longtext NOT NULL,
				published_at datetime NULL, created_by bigint unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY workflow_version (workflow_id,version_no), KEY institution_id (institution_id)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('programs')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, form_id bigint unsigned NOT NULL, workflow_id bigint unsigned NOT NULL,
				name varchar(191) NOT NULL, slug varchar(191) NOT NULL, description longtext NULL, intake varchar(100) NULL, quota int unsigned NULL, enrolled_count int unsigned NOT NULL DEFAULT 0,
				fee_required tinyint(1) NOT NULL DEFAULT 0, fee_amount decimal(18,2) NOT NULL DEFAULT 0, currency char(3) NOT NULL DEFAULT 'IDR', eligibility_json longtext NULL,
				status varchar(20) NOT NULL DEFAULT 'draft', created_at datetime NOT NULL, updated_at datetime NOT NULL,
				PRIMARY KEY  (id), UNIQUE KEY institution_slug (institution_id,slug), KEY institution_status (institution_id,status)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('periods')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, program_id bigint unsigned NOT NULL, name varchar(191) NOT NULL,
				opens_at datetime NOT NULL, closes_at datetime NOT NULL, payment_due_at datetime NULL, quota int unsigned NULL, enrolled_count int unsigned NOT NULL DEFAULT 0, status varchar(20) NOT NULL DEFAULT 'draft',
				created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), KEY program_status (program_id,status), KEY institution_dates (institution_id,opens_at,closes_at)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('document_requirements')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, program_id bigint unsigned NOT NULL, document_key varchar(80) NOT NULL, label varchar(191) NOT NULL,
				required tinyint(1) NOT NULL DEFAULT 1, allowed_mimes text NOT NULL, max_size int unsigned NOT NULL DEFAULT 5242880, sort_order int NOT NULL DEFAULT 0,
				PRIMARY KEY  (id), UNIQUE KEY program_document (program_id,document_key), KEY institution_id (institution_id)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('applicants')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, user_id bigint unsigned NOT NULL, email_verified_at datetime NULL, verification_hash char(64) NULL, verification_expires_at datetime NULL,
				created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY user_id (user_id)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('applications')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, applicant_id bigint unsigned NOT NULL, program_id bigint unsigned NOT NULL, period_id bigint unsigned NOT NULL,
				form_version_id bigint unsigned NOT NULL, workflow_version_id bigint unsigned NOT NULL, application_number varchar(80) NOT NULL, status varchar(40) NOT NULL DEFAULT 'draft', lock_version int unsigned NOT NULL DEFAULT 1,
				submitted_at datetime NULL, decision_published_at datetime NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL,
				PRIMARY KEY  (id), UNIQUE KEY application_number (application_number), UNIQUE KEY applicant_program_period (applicant_id,program_id,period_id), KEY institution_status (institution_id,status), KEY program_period (program_id,period_id)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('answers')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, application_id bigint unsigned NOT NULL, field_key varchar(100) NOT NULL, value_json longtext NULL,
				updated_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY application_field (application_id,field_key), KEY institution_id (institution_id)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('documents')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, application_id bigint unsigned NOT NULL, requirement_id bigint unsigned NULL, kind varchar(40) NOT NULL DEFAULT 'application',
				storage_key varchar(191) NOT NULL, original_name varchar(191) NOT NULL, mime_type varchar(100) NOT NULL, file_size bigint unsigned NOT NULL, sha256 char(64) NOT NULL,
				status varchar(30) NOT NULL DEFAULT 'pending', reviewer_id bigint unsigned NULL, reviewer_notes text NULL, reviewed_at datetime NULL, replaced_by bigint unsigned NULL,
				created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY storage_key (storage_key), KEY application_status (application_id,status), KEY institution_status (institution_id,status)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('payments')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, application_id bigint unsigned NOT NULL, amount decimal(18,2) NOT NULL, currency char(3) NOT NULL DEFAULT 'IDR',
				payment_reference varchar(100) NOT NULL, status varchar(30) NOT NULL DEFAULT 'unpaid', evidence_document_id bigint unsigned NULL, reviewer_id bigint unsigned NULL, reviewer_notes text NULL,
				due_at datetime NULL, verified_at datetime NULL, lock_version int unsigned NOT NULL DEFAULT 1, created_at datetime NOT NULL, updated_at datetime NOT NULL,
				PRIMARY KEY  (id), UNIQUE KEY application_id (application_id), UNIQUE KEY payment_reference (payment_reference), KEY institution_status (institution_id,status)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('rubrics')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, program_id bigint unsigned NOT NULL, name varchar(191) NOT NULL, current_version_id bigint unsigned NULL,
				created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), KEY program_id (program_id), KEY institution_id (institution_id)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('rubric_versions')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, rubric_id bigint unsigned NOT NULL, version_no int unsigned NOT NULL, criteria_json longtext NOT NULL,
				created_by bigint unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY rubric_version (rubric_id,version_no), KEY institution_id (institution_id)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('reviewer_assignments')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, application_id bigint unsigned NOT NULL, reviewer_id bigint unsigned NOT NULL, rubric_version_id bigint unsigned NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'assigned', assigned_by bigint unsigned NOT NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY application_reviewer_rubric (application_id,reviewer_id,rubric_version_id), KEY institution_status (institution_id,status), KEY reviewer_status (reviewer_id,status)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('assessments')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, application_id bigint unsigned NOT NULL, rubric_version_id bigint unsigned NOT NULL, reviewer_id bigint unsigned NOT NULL,
				scores_json longtext NOT NULL, total_score decimal(8,2) NOT NULL DEFAULT 0, recommendation varchar(30) NULL, notes text NULL, status varchar(20) NOT NULL DEFAULT 'draft',
				created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY application_reviewer_rubric (application_id,reviewer_id,rubric_version_id), KEY institution_status (institution_id,status)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('enrollments')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, application_id bigint unsigned NOT NULL, program_id bigint unsigned NOT NULL, period_id bigint unsigned NOT NULL,
				confirmed_by bigint unsigned NOT NULL, confirmed_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY application_id (application_id), KEY institution_program (institution_id,program_id)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('status_history')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, application_id bigint unsigned NOT NULL, previous_status varchar(40) NULL, new_status varchar(40) NOT NULL,
				actor_id bigint unsigned NULL, reason text NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), KEY application_created (application_id,created_at), KEY institution_status (institution_id,new_status)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('announcements')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, program_id bigint unsigned NULL, title varchar(191) NOT NULL, content longtext NOT NULL, status varchar(20) NOT NULL DEFAULT 'draft',
				published_at datetime NULL, created_by bigint unsigned NOT NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), KEY institution_status (institution_id,status), KEY program_id (program_id)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('notifications')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NOT NULL, application_id bigint unsigned NULL, event varchar(60) NOT NULL, recipient varchar(191) NOT NULL,
				subject varchar(191) NOT NULL, body longtext NOT NULL, status varchar(20) NOT NULL DEFAULT 'queued', attempts int unsigned NOT NULL DEFAULT 0, last_error text NULL, next_attempt_at datetime NULL,
				created_at datetime NOT NULL, sent_at datetime NULL, PRIMARY KEY  (id), KEY status_attempt (status,next_attempt_at), KEY institution_event (institution_id,event)
			) ENGINE=InnoDB $charset;",
			"CREATE TABLE {$t('audit_logs')} (
				id bigint unsigned NOT NULL AUTO_INCREMENT, institution_id bigint unsigned NULL, actor_id bigint unsigned NULL, action varchar(80) NOT NULL, object_type varchar(60) NOT NULL, object_id bigint unsigned NULL,
				request_id varchar(64) NOT NULL, ip_hash char(64) NULL, context_json longtext NULL, created_at datetime NOT NULL, PRIMARY KEY  (id), KEY institution_created (institution_id,created_at), KEY object_lookup (object_type,object_id)
			) ENGINE=InnoDB $charset;",
		);
	}

	private static function install_roles(): void {
		$all       = array_fill_keys( self::CAPS, true );
		$role_caps = array(
			'admission_super_administrator' => $all,
			'institution_administrator'     => array_diff_key( $all, array_flip( array( 'sakolaan_admission_manage_privacy', 'sakolaan_admission_manage_institutions', 'sakolaan_admission_manage_settings' ) ) ),
			'admission_officer'             => array_fill_keys( array( 'sakolaan_admission_access_admin', 'sakolaan_admission_manage_applications', 'sakolaan_admission_manage_programs', 'sakolaan_admission_view_reports' ), true ),
			'document_verifier'             => array_fill_keys( array( 'sakolaan_admission_access_admin', 'sakolaan_admission_verify_documents' ), true ),
			'payment_verifier'              => array_fill_keys( array( 'sakolaan_admission_access_admin', 'sakolaan_admission_verify_payments' ), true ),
			'assessment_reviewer'           => array_fill_keys( array( 'sakolaan_admission_access_admin', 'sakolaan_admission_review_assessments' ), true ),
			'admission_applicant'           => array( 'read' => true ),
		);
		$labels    = array(
			'admission_super_administrator' => __( 'Administrator Utama Penerimaan', 'sakolaan-admission' ),
			'institution_administrator'     => __( 'Administrator Institusi', 'sakolaan-admission' ),
			'admission_officer'             => __( 'Petugas Penerimaan', 'sakolaan-admission' ),
			'document_verifier'             => __( 'Pemeriksa Dokumen', 'sakolaan-admission' ),
			'payment_verifier'              => __( 'Pemeriksa Pembayaran', 'sakolaan-admission' ),
			'assessment_reviewer'           => __( 'Penilai Penerimaan', 'sakolaan-admission' ),
			'admission_applicant'           => __( 'Pendaftar', 'sakolaan-admission' ),
		);
		foreach ( $role_caps as $slug => $caps ) {
			add_role( $slug, $labels[ $slug ], array_merge( array( 'read' => true ), $caps ) );
			$role = get_role( $slug );
			if ( $role ) {
				$role->add_cap( 'read' );
				foreach ( self::CAPS as $cap ) {
					$role->remove_cap( $cap ); }
				foreach ( array_keys( $caps ) as $cap ) {
					$role->add_cap( $cap ); }
			}
		}
		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( self::CAPS as $cap ) {
				$administrator->add_cap( $cap );
			}
		}
	}

	private static function seed_defaults(): void {
		global $wpdb;
		$now = current_time( 'mysql', true );
		if ( ! $wpdb->get_var( 'SELECT id FROM ' . self::table( 'institutions' ) . ' LIMIT 1' ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$name = function_exists( 'sakolaan_get_setting' ) ? (string) sakolaan_get_setting( 'identity.name', get_bloginfo( 'name' ) ) : get_bloginfo( 'name' );
			$name = $name ? $name : get_bloginfo( 'name' );
			$wpdb->insert(
				self::table( 'institutions' ),
				array(
					'code'       => 'MAIN',
					'name'       => $name,
					'slug'       => 'main',
					'status'     => 'active',
					'settings'   => wp_json_encode( array() ),
					'created_at' => $now,
					'updated_at' => $now,
				)
			);
		}
		if ( false === get_option( 'sakolaan_admission_settings', false ) ) {
			add_option(
				'sakolaan_admission_settings',
				array(
					'mode'                     => 'single',
					'currency'                 => 'IDR',
					'max_upload_size'          => 5242880,
					'delete_data_on_uninstall' => false,
				),
				'',
				false
			);
		}
	}
}
