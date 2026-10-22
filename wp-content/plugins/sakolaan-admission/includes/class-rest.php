<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

final class Rest {
	private const NS = 'sakolaan-admission/v1';

	public static function register(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) ); }

	public static function routes(): void {
		register_rest_route(
			self::NS,
			'/programs',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'programs' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/register',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'register_applicant' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/verify-email',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'verify_email' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/verification/resend',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'resend_verification' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/applications',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'applications' ),
					'permission_callback' => array( __CLASS__, 'logged_in' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create_application' ),
					'permission_callback' => array( __CLASS__, 'logged_in' ),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/applications/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'application' ),
					'permission_callback' => array( __CLASS__, 'logged_in' ),
				),
				array(
					'methods'             => 'PATCH',
					'callback'            => array( __CLASS__, 'save_answers' ),
					'permission_callback' => array( __CLASS__, 'logged_in' ),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/applications/(?P<id>\d+)/submit',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'submit' ),
				'permission_callback' => array( __CLASS__, 'logged_in' ),
			)
		);
		register_rest_route(
			self::NS,
			'/applications/(?P<id>\d+)/transition',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'transition' ),
				'permission_callback' => array( __CLASS__, 'staff' ),
			)
		);
		register_rest_route(
			self::NS,
			'/applications/(?P<id>\d+)/documents',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'upload_document' ),
				'permission_callback' => array( __CLASS__, 'logged_in' ),
			)
		);
		register_rest_route(
			self::NS,
			'/documents/(?P<id>\d+)/review',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'review_document' ),
				'permission_callback' => array( __CLASS__, 'staff' ),
			)
		);
		register_rest_route(
			self::NS,
			'/payments/(?P<id>\d+)/evidence',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'payment_evidence' ),
				'permission_callback' => array( __CLASS__, 'logged_in' ),
			)
		);
		register_rest_route(
			self::NS,
			'/payments/(?P<id>\d+)/review',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'review_payment' ),
				'permission_callback' => array( __CLASS__, 'staff' ),
			)
		);
		register_rest_route(
			self::NS,
			'/assessments',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'save_assessment' ),
				'permission_callback' => array( __CLASS__, 'staff' ),
			)
		);
	}

	public static function logged_in(): bool {
		return is_user_logged_in(); }
	public static function staff(): bool {
		return current_user_can( 'sakolaan_admission_access_admin' ); }

	public static function programs(): \WP_REST_Response {
		return rest_ensure_response( Availability::periods() );
	}

	public static function register_applicant( \WP_REST_Request $request ) {
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key   = 'sak_adm_reg_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
		$count = (int) get_transient( $key );
		if ( $count >= 5 ) {
			return new \WP_Error( 'rate_limited', __( 'Terlalu banyak percobaan pendaftaran. Silakan coba lagi nanti.', 'sakolaan-admission' ), array( 'status' => 429 ) ); }
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		if ( $request->get_param( 'website' ) ) {
			return new \WP_Error( 'spam', __( 'Pendaftaran akun ditolak.', 'sakolaan-admission' ), array( 'status' => 400 ) ); }
		$email    = sanitize_email( $request->get_param( 'email' ) );
		$name     = sanitize_text_field( $request->get_param( 'name' ) );
		$password = (string) $request->get_param( 'password' );
		if ( ! is_email( $email ) || ! $name || strlen( $password ) < 12 ) {
			return new \WP_Error( 'invalid_registration', __( 'Masukkan nama, alamat email yang valid, dan kata sandi minimal 12 karakter.', 'sakolaan-admission' ), array( 'status' => 400 ) ); }
		if ( email_exists( $email ) ) {
			return new \WP_Error( 'email_exists', __( 'Alamat email ini sudah digunakan oleh akun lain.', 'sakolaan-admission' ), array( 'status' => 409 ) ); }
		$username = sanitize_user( strtok( $email, '@' ), true );
		$base     = $username ? $username : 'applicant';
		$suffix   = 1;
		while ( username_exists( $username ) ) {
			$username = $base . ( $suffix++ ); }
		$user_id = wp_insert_user(
			array(
				'user_login'   => $username,
				'user_email'   => $email,
				'display_name' => $name,
				'user_pass'    => $password,
				'role'         => 'admission_applicant',
			)
		);
		if ( is_wp_error( $user_id ) ) {
			return $user_id; }
		$applicant = Repository::applicant_for_user( (int) $user_id, true );
		$token     = wp_generate_password( 48, false, false );
		$hash      = hash( 'sha256', $token );
		global $wpdb;
		$wpdb->update(
			Installer::table( 'applicants' ),
			array(
				'verification_hash'       => $hash,
				'verification_expires_at' => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
				'updated_at'              => current_time( 'mysql', true ),
			),
			array( 'id' => $applicant['id'] )
		);
		$program_id = absint( $request->get_param( 'program_id' ) );
		$verify_url = add_query_arg(
			array(
				'sakolaan_verify'   => $applicant['id'],
				'token'             => $token,
				'admission_program' => $program_id ? $program_id : false,
			),
			home_url( '/ppdb/' )
		);
		/* translators: %s: email verification URL. */
		$accepted = wp_mail( $email, __( 'Verifikasi email pendaftar', 'sakolaan-admission' ), sprintf( __( 'Buka tautan berikut untuk memverifikasi email Anda: %s', 'sakolaan-admission' ), $verify_url ) );
		Repository::audit( 'applicant_registered', 'applicant', (int) $applicant['id'], null, array( 'mail_accepted' => $accepted ) );
		return new \WP_REST_Response(
			array(
				'created'       => true,
				'mail_accepted' => $accepted,
				'message'       => $accepted ? __( 'Akun berhasil dibuat. Periksa email Anda untuk melakukan verifikasi.', 'sakolaan-admission' ) : __( 'Akun berhasil dibuat, tetapi email verifikasi tidak dapat dikirim. Hubungi petugas penerimaan.', 'sakolaan-admission' ),
			),
			201
		);
	}

	public static function verify_email( \WP_REST_Request $request ) {
		global $wpdb;
		$id        = absint( $request->get_param( 'applicant_id' ) );
		$token     = (string) $request->get_param( 'token' );
		$applicant = Repository::get( 'applicants', $id );
		if ( ! $applicant || ! $applicant['verification_hash'] || ! hash_equals( $applicant['verification_hash'], hash( 'sha256', $token ) ) || strtotime( $applicant['verification_expires_at'] . ' UTC' ) < time() ) {
			return new \WP_Error( 'invalid_token', __( 'Tautan verifikasi tidak valid atau sudah kedaluwarsa.', 'sakolaan-admission' ), array( 'status' => 400 ) ); }
		$wpdb->update(
			Installer::table( 'applicants' ),
			array(
				'email_verified_at'       => current_time( 'mysql', true ),
				'verification_hash'       => null,
				'verification_expires_at' => null,
				'updated_at'              => current_time( 'mysql', true ),
			),
			array( 'id' => $id )
		);
		return rest_ensure_response( array( 'verified' => true ) );
	}

	public static function resend_verification( \WP_REST_Request $request ): \WP_REST_Response {
		$email      = sanitize_email( $request->get_param( 'email' ) );
		$program_id = absint( $request->get_param( 'program_id' ) );
		$ip         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$ip_key     = 'sak_adm_verify_ip_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 24 );
		$email_key  = 'sak_adm_verify_mail_' . substr( hash_hmac( 'sha256', strtolower( $email ), wp_salt( 'nonce' ) ), 0, 24 );
		$ip_count   = (int) get_transient( $ip_key );
		$mail_count = (int) get_transient( $email_key );
		$message    = __( 'Jika akun ditemukan, email verifikasi baru telah dikirim.', 'sakolaan-admission' );
		if ( $ip_count >= 10 || $mail_count >= 3 || ! is_email( $email ) ) {
			return rest_ensure_response(
				array(
					'accepted' => true,
					'message'  => $message,
				)
			);
		}
		set_transient( $ip_key, $ip_count + 1, HOUR_IN_SECONDS );
		set_transient( $email_key, $mail_count + 1, HOUR_IN_SECONDS );
		$user      = get_user_by( 'email', $email );
		$applicant = $user ? Repository::applicant_for_user( (int) $user->ID ) : null;
		if ( $applicant && empty( $applicant['email_verified_at'] ) ) {
			$token = wp_generate_password( 48, false, false );
			global $wpdb;
			$wpdb->update(
				Installer::table( 'applicants' ),
				array(
					'verification_hash'       => hash( 'sha256', $token ),
					'verification_expires_at' => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
					'updated_at'              => current_time( 'mysql', true ),
				),
				array( 'id' => $applicant['id'] )
			);
			$verify_url = add_query_arg(
				array(
					'sakolaan_verify'   => $applicant['id'],
					'token'             => $token,
					'admission_program' => $program_id ? $program_id : false,
				),
				home_url( '/ppdb/' )
			);
			/* translators: %s: email verification URL. */
			$accepted = wp_mail( $email, __( 'Verifikasi email pendaftar', 'sakolaan-admission' ), sprintf( __( 'Buka tautan berikut untuk memverifikasi email Anda: %s', 'sakolaan-admission' ), $verify_url ) );
			Repository::audit( 'applicant_verification_resent', 'applicant', (int) $applicant['id'], null, array( 'mail_accepted' => $accepted ) );
		}
		return rest_ensure_response(
			array(
				'accepted' => true,
				'message'  => $message,
			)
		);
	}

	public static function applications(): \WP_REST_Response {
		global $wpdb;
		$applicant = Repository::applicant_for_user( get_current_user_id() );
		if ( ! $applicant ) {
			return rest_ensure_response( array() ); }
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id,institution_id,program_id,period_id,application_number,status,submitted_at,updated_at FROM ' . Installer::table( 'applications' ) . ' WHERE applicant_id=%d ORDER BY created_at DESC', $applicant['id'] ), ARRAY_A );
		return rest_ensure_response( $rows );
	}

	public static function create_application( \WP_REST_Request $request ) {
		$program_id = absint( $request->get_param( 'program_id' ) );
		$period_id  = absint( $request->get_param( 'period_id' ) );
		$available  = Availability::for_period( $program_id, $period_id );
		$program    = $available ? Repository::get( 'programs', $program_id, (int) $available['institution_id'] ) : null;
		$period     = $available ? Repository::get( 'periods', $period_id, (int) $available['institution_id'] ) : null;
		if ( ! $program || ! $period || ! $available ) {
			return new \WP_Error( 'registration_unavailable', __( 'Program atau periode penerimaan tidak tersedia.', 'sakolaan-admission' ), array( 'status' => 400 ) ); }
		if ( 'open' !== $available['availability'] ) {
			return new \WP_Error( 'period_closed', __( 'Periode penerimaan sudah ditutup.', 'sakolaan-admission' ), array( 'status' => 409 ) ); }
		$applicant = Repository::applicant_for_user( get_current_user_id(), true );
		if ( ! $applicant['email_verified_at'] ) {
			return new \WP_Error( 'email_unverified', __( 'Verifikasi email Anda sebelum memulai pendaftaran.', 'sakolaan-admission' ), array( 'status' => 403 ) ); }
		$form     = Repository::get( 'forms', (int) $program['form_id'], (int) $program['institution_id'] );
		$workflow = Repository::get( 'workflows', (int) $program['workflow_id'], (int) $program['institution_id'] );
		if ( ! $form || ! $workflow || ! $form['current_version_id'] || ! $workflow['current_version_id'] ) {
			return new \WP_Error( 'configuration_incomplete', __( 'Konfigurasi program belum lengkap.', 'sakolaan-admission' ), array( 'status' => 500 ) ); }
		$number = apply_filters( 'sakolaan_admission_application_number', strtoupper( substr( preg_replace( '/[^A-Z0-9]/', '', (string) $program['slug'] ), 0, 8 ) . '-' . gmdate( 'ymd' ) . '-' . wp_generate_password( 8, false, false ) ), $program, $period, $applicant );
		$now    = current_time( 'mysql', true );
		$id     = Repository::insert(
			'applications',
			array(
				'institution_id'      => $program['institution_id'],
				'applicant_id'        => $applicant['id'],
				'program_id'          => $program_id,
				'period_id'           => $period_id,
				'form_version_id'     => $form['current_version_id'],
				'workflow_version_id' => $workflow['current_version_id'],
				'application_number'  => sanitize_text_field( $number ),
				'status'              => 'draft',
				'lock_version'        => 1,
				'created_at'          => $now,
				'updated_at'          => $now,
			)
		);
		if ( ! $id ) {
			return new \WP_Error( 'duplicate_application', __( 'Anda sudah memiliki pendaftaran untuk program dan periode ini.', 'sakolaan-admission' ), array( 'status' => 409 ) ); }
		Repository::audit( 'application_created', 'application', $id, (int) $program['institution_id'] );
		return new \WP_REST_Response( Repository::get( 'applications', $id ), 201 );
	}

	public static function application( \WP_REST_Request $request ) {
		$app = Repository::application_owned_by_user( absint( $request['id'] ), get_current_user_id() );
		if ( ! $app ) {
			return new \WP_Error( 'not_found', __( 'Data pendaftaran tidak ditemukan.', 'sakolaan-admission' ), array( 'status' => 404 ) ); }
		global $wpdb;
		$answers        = $wpdb->get_results( $wpdb->prepare( 'SELECT field_key,value_json FROM ' . Installer::table( 'answers' ) . ' WHERE application_id=%d', $app['id'] ), ARRAY_A );
		$app['answers'] = array();
		foreach ( $answers as $answer ) {
			$app['answers'][ $answer['field_key'] ] = json_decode( $answer['value_json'], true ); }
		return rest_ensure_response( $app );
	}

	public static function save_answers( \WP_REST_Request $request ) {
		global $wpdb;
		$app = Repository::application_owned_by_user( absint( $request['id'] ), get_current_user_id() );
		if ( ! $app || 'draft' !== $app['status'] ) {
			return new \WP_Error( 'not_editable', __( 'Pendaftaran ini tidak dapat diubah.', 'sakolaan-admission' ), array( 'status' => 403 ) ); }
		$version   = Repository::get( 'form_versions', (int) $app['form_version_id'], (int) $app['institution_id'] );
		$schema    = $version ? json_decode( $version['schema_json'], true ) : null;
		$answers   = $request->get_param( 'answers' );
		$validated = is_array( $schema ) && is_array( $answers ) ? Forms::validate_answers( $schema, $answers, false ) : new \WP_Error( 'invalid_answers', __( 'Isian formulir tidak valid.', 'sakolaan-admission' ) );
		if ( is_wp_error( $validated ) ) {
			return $validated; }
		$now = current_time( 'mysql', true );
		foreach ( $validated as $key => $value ) {
			$wpdb->query( $wpdb->prepare( 'INSERT INTO ' . Installer::table( 'answers' ) . ' (institution_id,application_id,field_key,value_json,updated_at) VALUES (%d,%d,%s,%s,%s) ON DUPLICATE KEY UPDATE value_json=VALUES(value_json),updated_at=VALUES(updated_at)', $app['institution_id'], $app['id'], $key, wp_json_encode( $value ), $now ) ); }
		$wpdb->update( Installer::table( 'applications' ), array( 'updated_at' => $now ), array( 'id' => $app['id'] ) );
		Repository::audit( 'application_draft_saved', 'application', (int) $app['id'], (int) $app['institution_id'], array( 'field_count' => count( $validated ) ) );
		return rest_ensure_response(
			array(
				'saved'          => true,
				'application_id' => (int) $app['id'],
			)
		);
	}

	public static function submit( \WP_REST_Request $request ) {
		global $wpdb;
		$id  = absint( $request['id'] );
		$app = Repository::application_owned_by_user( $id, get_current_user_id() );
		if ( ! $app || 'draft' !== $app['status'] ) {
			return new \WP_Error( 'not_submittable', __( 'Pendaftaran ini tidak dapat dikirim.', 'sakolaan-admission' ), array( 'status' => 409 ) ); }
		$version = Repository::get( 'form_versions', (int) $app['form_version_id'], (int) $app['institution_id'] );
		$rows    = $wpdb->get_results( $wpdb->prepare( 'SELECT field_key,value_json FROM ' . Installer::table( 'answers' ) . ' WHERE application_id=%d', $id ), ARRAY_A );
		$answers = array();
		foreach ( $rows as $row ) {
			$answers[ $row['field_key'] ] = json_decode( $row['value_json'], true );
		} $validated = Forms::validate_answers( json_decode( $version['schema_json'], true ), $answers, true );
		if ( is_wp_error( $validated ) ) {
			return $validated; }
		$required = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Installer::table( 'document_requirements' ) . ' r LEFT JOIN ' . Installer::table( 'documents' ) . ' d ON d.requirement_id=r.id AND d.application_id=%d AND d.replaced_by IS NULL WHERE r.program_id=%d AND r.required=1 AND d.id IS NULL', $id, $app['program_id'] ) );
		if ( $required ) {
			return new \WP_Error( 'documents_required', __( 'Unggah seluruh dokumen wajib sebelum mengirim pendaftaran.', 'sakolaan-admission' ), array( 'status' => 409 ) ); }
		$lock_version = absint( $request->get_param( 'lock_version' ) );
		return Workflow::transition( $id, 'submitted', sanitize_textarea_field( $request->get_param( 'reason' ) ), $lock_version ? $lock_version : null );
	}

	public static function transition( \WP_REST_Request $request ) {
		$lock_version = absint( $request->get_param( 'lock_version' ) );
		return Workflow::transition( absint( $request['id'] ), sanitize_key( $request->get_param( 'to' ) ), sanitize_textarea_field( $request->get_param( 'reason' ) ), $lock_version ? $lock_version : null ); }
	public static function upload_document( \WP_REST_Request $request ) {
		$application_id = absint( $request['id'] );
		$files          = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			return new \WP_Error( 'missing_file', __( 'Pilih berkas yang akan diunggah.', 'sakolaan-admission' ), array( 'status' => 400 ) );
		}
		$requirement_id = absint( $request->get_param( 'requirement_id' ) );
		$replace_id     = absint( $request->get_param( 'replace_document_id' ) );
		$allow_revision = false;
		if ( $replace_id ) {
			$owned          = Repository::application_owned_by_user( $application_id, get_current_user_id() );
			$old            = $owned ? Repository::get( 'documents', $replace_id, (int) $owned['institution_id'] ) : null;
			$allow_revision = $old && (int) $old['application_id'] === $application_id && (int) $old['requirement_id'] === $requirement_id && ! $old['replaced_by'] && in_array( $old['status'], array( 'rejected', 'revision_requested' ), true );
			if ( ! $allow_revision ) {
				return new \WP_Error( 'invalid_replacement', __( 'Dokumen ini tidak dapat diganti.', 'sakolaan-admission' ), array( 'status' => 403 ) );
			}
		}
		$stored = Documents::store_upload( $application_id, $files['file'], $requirement_id ? $requirement_id : null, 'application', $allow_revision );
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}
		if ( $replace_id ) {
			$old   = Repository::get( 'documents', $replace_id, (int) $stored['institution_id'] );
			$owned = Repository::application_owned_by_user( $application_id, get_current_user_id() );
			if ( $old && $owned && (int) $old['application_id'] === $application_id && ! $old['replaced_by'] ) {
				Repository::update(
					'documents',
					$replace_id,
					array(
						'replaced_by' => $stored['id'],
						'updated_at'  => current_time( 'mysql', true ),
					),
					(int) $stored['institution_id']
				);
				Repository::audit( 'document_replaced', 'document', $replace_id, (int) $stored['institution_id'], array( 'replacement_id' => (int) $stored['id'] ) );
			}
		} return $stored; }
	public static function review_document( \WP_REST_Request $request ) {
		return Documents::review( absint( $request['id'] ), sanitize_key( $request->get_param( 'status' ) ), sanitize_textarea_field( $request->get_param( 'notes' ) ) ); }

	public static function payment_evidence( \WP_REST_Request $request ) {
		global $wpdb;
		$payment = Repository::get( 'payments', absint( $request['id'] ) );
		$app     = $payment ? Repository::application_owned_by_user( (int) $payment['application_id'], get_current_user_id() ) : null;
		if ( ! $payment || ! $app || ! in_array( $payment['status'], array( 'unpaid', 'rejected' ), true ) ) {
			return new \WP_Error( 'forbidden', __( 'Bukti pembayaran tidak dapat diunggah untuk pendaftaran ini.', 'sakolaan-admission' ), array( 'status' => 403 ) );
		} $files = $request->get_file_params();
		if ( empty( $files['file'] ) ) {
			return new \WP_Error( 'missing_file', __( 'Pilih berkas yang akan diunggah.', 'sakolaan-admission' ) ); }
		$document = Documents::store_upload( (int) $app['id'], $files['file'], null, 'payment_evidence' );
		if ( is_wp_error( $document ) ) {
			return $document;
		} $updated = $wpdb->update(
			Installer::table( 'payments' ),
			array(
				'evidence_document_id' => $document['id'],
				'status'               => 'pending_verification',
				'lock_version'         => (int) $payment['lock_version'] + 1,
				'updated_at'           => current_time( 'mysql', true ),
			),
			array(
				'id'           => $payment['id'],
				'lock_version' => $payment['lock_version'],
				'status'       => $payment['status'],
			)
		);
		if ( 1 !== $updated ) {
			return new \WP_Error( 'stale_payment', __( 'Data pembayaran telah berubah pada permintaan lain.', 'sakolaan-admission' ), array( 'status' => 409 ) );
		} Repository::audit( 'payment_evidence_uploaded', 'payment', (int) $payment['id'], (int) $payment['institution_id'], array( 'document_id' => (int) $document['id'] ) );
		do_action( 'sakolaan_admission_payment_evidence_received', (int) $payment['id'], (int) $document['id'] );
		return Repository::get( 'payments', (int) $payment['id'] );
	}

	public static function review_payment( \WP_REST_Request $request ) {
		$lock_version = absint( $request->get_param( 'lock_version' ) );
		return Workflow::payment_transition( absint( $request['id'] ), sanitize_key( $request->get_param( 'status' ) ), sanitize_textarea_field( $request->get_param( 'notes' ) ), $lock_version ? $lock_version : null ); }

	public static function save_assessment( \WP_REST_Request $request ) {
		if ( ! current_user_can( 'sakolaan_admission_review_assessments' ) ) {
			return new \WP_Error( 'forbidden', __( 'Akses ditolak.', 'sakolaan-admission' ), array( 'status' => 403 ) ); }
		$app     = Repository::get( 'applications', absint( $request->get_param( 'application_id' ) ) );
		$version = Repository::get( 'rubric_versions', absint( $request->get_param( 'rubric_version_id' ) ) );
		if ( ! $app || ! $version || (int) $app['institution_id'] !== (int) $version['institution_id'] || ! Access::can_access_institution( (int) $app['institution_id'] ) ) {
			return new \WP_Error( 'forbidden', __( 'Akses ditolak.', 'sakolaan-admission' ), array( 'status' => 403 ) ); }
		global $wpdb;
		$assigned = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Installer::table( 'reviewer_assignments' ) . " WHERE application_id=%d AND reviewer_id=%d AND rubric_version_id=%d AND status='assigned'", $app['id'], get_current_user_id(), $version['id'] ) );
		if ( ! $assigned ) {
			return new \WP_Error( 'review_not_assigned', __( 'Pendaftaran ini tidak ditugaskan kepada Anda.', 'sakolaan-admission' ), array( 'status' => 403 ) ); }
		$criteria     = json_decode( $version['criteria_json'], true );
		$submitted    = (array) $request->get_param( 'scores' );
		$scores       = array();
		$total        = 0.0;
		$weight_total = 0.0;
		foreach ( (array) ( $criteria['criteria'] ?? array() ) as $criterion ) {
			$key            = sanitize_key( $criterion['key'] ?? '' );
			$weight         = max( 0, (float) ( $criterion['weight'] ?? 0 ) );
			$max            = max( 1, (float) ( $criterion['max_score'] ?? 100 ) );
			$score          = min( $max, max( 0, (float) ( $submitted[ $key ] ?? 0 ) ) );
			$scores[ $key ] = $score;
			$total         += ( $score / $max ) * $weight;
			$weight_total  += $weight;
		} $total = $weight_total ? ( $total / $weight_total ) * 100 : 0;
		$id      = Repository::insert(
			'assessments',
			array(
				'institution_id'    => $app['institution_id'],
				'application_id'    => $app['id'],
				'rubric_version_id' => $version['id'],
				'reviewer_id'       => get_current_user_id(),
				'scores_json'       => wp_json_encode( $scores ),
				'total_score'       => round( $total, 2 ),
				'recommendation'    => sanitize_key( $request->get_param( 'recommendation' ) ),
				'notes'             => sanitize_textarea_field( $request->get_param( 'notes' ) ),
				'status'            => 'submitted',
				'created_at'        => current_time( 'mysql', true ),
				'updated_at'        => current_time( 'mysql', true ),
			)
		);
		if ( ! $id ) {
			return new \WP_Error( 'assessment_exists', __( 'Penilaian untuk penilai dan rubrik ini sudah tersedia.', 'sakolaan-admission' ), array( 'status' => 409 ) );
		} Repository::audit( 'assessment_submitted', 'assessment', $id, (int) $app['institution_id'], array( 'total' => round( $total, 2 ) ) );
		return new \WP_REST_Response( Repository::get( 'assessments', $id, (int) $app['institution_id'] ), 201 );
	}
}
