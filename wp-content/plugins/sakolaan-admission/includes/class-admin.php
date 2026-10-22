<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

final class Admin {
	private const SECTIONS          = array( 'overview', 'institutions', 'programs', 'periods', 'forms', 'applicants', 'documents', 'payments', 'verification', 'assessment', 'selection', 'announcements', 'reports', 'settings' );
	private const NAVIGATION_GROUPS = array(
		'Ringkasan'   => array( 'overview' ),
		'Konfigurasi' => array( 'institutions', 'programs', 'periods', 'forms' ),
		'Operasional' => array( 'applicants', 'documents', 'payments', 'verification', 'assessment' ),
		'Hasil'       => array( 'selection', 'announcements', 'reports' ),
		'Sistem'      => array( 'settings' ),
	);

	public static function register(): void {
		add_filter( 'sakolaan_admin_navigation_registry', array( __CLASS__, 'navigation' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ), 20 );
		add_action( 'admin_post_sakolaan_admission_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_sakolaan_admission_config_update', array( __CLASS__, 'update_configuration' ) );
		add_action( 'admin_post_sakolaan_admission_config_status', array( __CLASS__, 'change_configuration_status' ) );
		add_action( 'admin_post_sakolaan_admission_export', array( __CLASS__, 'export_csv' ) );
		add_action( 'admin_post_sakolaan_admission_test_email', array( __CLASS__, 'test_email' ) );
		add_action( 'sakolaan_admission_application_submitted', array( __CLASS__, 'queue_submission_notice' ) );
		add_action( 'sakolaan_admission_payment_transitioned', array( __CLASS__, 'queue_payment_notice' ), 10, 3 );
		add_action( 'sakolaan_admission_decision_published', array( __CLASS__, 'queue_decision_notice' ), 10, 2 );
		add_action( 'sakolaan_admission_notification_worker', array( __CLASS__, 'send_notifications' ) );
		if ( ! wp_next_scheduled( 'sakolaan_admission_notification_worker' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'sakolaan_admission_notification_worker' );
		}
		self::seed_configurations();
		self::migrate_legacy_templates();
	}

	public static function navigation( array $registry ): array {
		$registry['admission'] = array(
			'label'           => __( 'Penerimaan', 'sakolaan-admission' ),
			'title'           => __( 'Penerimaan Sakolaan', 'sakolaan-admission' ),
			'description'     => __( 'Kelola program, pendaftaran, verifikasi, keputusan, dan pendaftaran ulang.', 'sakolaan-admission' ),
			'icon'            => 'dashicons-welcome-learn-more',
			'capability'      => 'sakolaan_admission_access_admin',
			'render_callback' => array( __CLASS__, 'render' ),
		);
		return $registry;
	}

	public static function assets( string $hook ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen routing.
		if ( 'toplevel_page_sakolaan' !== $hook || 'admission' !== ( isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '' ) ) {
			return; }
		wp_enqueue_style( 'sakolaan-admission-admin', SAKOLAAN_ADMISSION_URL . 'assets/css/admin.css', array( 'sakolaan-admin' ), SAKOLAAN_ADMISSION_VERSION );
		wp_enqueue_script( 'sakolaan-admission-admin', SAKOLAAN_ADMISSION_URL . 'assets/js/admin.js', array(), SAKOLAAN_ADMISSION_VERSION, true );
	}

	private static function seed_configurations(): void {
		if ( get_option( 'sakolaan_admission_defaults_seeded' ) ) {
			return; }
		global $wpdb;
		$institution_ids = array_map( 'intval', $wpdb->get_col( 'SELECT id FROM ' . Installer::table( 'institutions' ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$now             = current_time( 'mysql', true );
		foreach ( $institution_ids as $institution_id ) {
			self::seed_institution( $institution_id, $now );
		}
		update_option( 'sakolaan_admission_defaults_seeded', 1, false );
	}

	private static function seed_institution( int $institution_id, ?string $now = null ): void {
		global $wpdb;
		$now    = $now ? $now : current_time( 'mysql', true );
		$exists = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Installer::table( 'forms' ) . ' WHERE institution_id=%d', $institution_id ) );
		if ( $exists ) {
			return; }
		foreach ( Forms::templates() as $key => $schema ) {
			$form_id    = Repository::insert(
				'forms',
				array(
					'institution_id' => $institution_id,
					'name'           => Forms::template_label( $key ),
					'status'         => 'published',
					'created_by'     => get_current_user_id(),
					'created_at'     => $now,
					'updated_at'     => $now,
				)
			);
			$version_id = Repository::insert(
				'form_versions',
				array(
					'institution_id' => $institution_id,
					'form_id'        => $form_id,
					'version_no'     => 1,
					'schema_json'    => wp_json_encode( $schema ),
					'published_at'   => $now,
					'created_by'     => get_current_user_id(),
					'created_at'     => $now,
				)
			);
			Repository::update( 'forms', $form_id, array( 'current_version_id' => $version_id ), $institution_id );
		}
		foreach ( array(
			'Alur Penerimaan Lengkap'    => false,
			'Alur Pendaftaran Sederhana' => true,
		) as $name => $simple ) {
			$workflow_id = Repository::insert(
				'workflows',
				array(
					'institution_id' => $institution_id,
					'name'           => $name,
					'status'         => 'published',
					'created_at'     => $now,
					'updated_at'     => $now,
				)
			);
			$version_id  = Repository::insert(
				'workflow_versions',
				array(
					'institution_id'  => $institution_id,
					'workflow_id'     => $workflow_id,
					'version_no'      => 1,
					'definition_json' => wp_json_encode( Workflow::default_definition( $simple ) ),
					'published_at'    => $now,
					'created_by'      => get_current_user_id(),
					'created_at'      => $now,
				)
			);
			Repository::update( 'workflows', $workflow_id, array( 'current_version_id' => $version_id ), $institution_id );
		}
	}

	/**
	 * Publish Indonesian replacements for untouched built-in English templates.
	 *
	 * Existing versions remain immutable so historic applications continue to
	 * render and validate against the schema they originally used.
	 */
	private static function migrate_legacy_templates(): void {
		if ( get_option( 'sakolaan_admission_indonesian_templates_migrated' ) ) {
			return;
		}
		global $wpdb;
		$rows      = (array) $wpdb->get_results( 'SELECT f.id, f.institution_id, f.current_version_id, fv.version_no, fv.schema_json FROM ' . Installer::table( 'forms' ) . ' f INNER JOIN ' . Installer::table( 'form_versions' ) . ' fv ON fv.id=f.current_version_id', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$templates = Forms::templates();
		$now       = current_time( 'mysql', true );
		$complete  = true;

		foreach ( $rows as $row ) {
			$schema = json_decode( (string) $row['schema_json'], true );
			$key    = is_array( $schema ) ? Forms::legacy_template_key( $schema ) : null;
			if ( ! $key || ! isset( $templates[ $key ] ) ) {
				continue;
			}
			$version_id = Repository::insert(
				'form_versions',
				array(
					'institution_id' => (int) $row['institution_id'],
					'form_id'        => (int) $row['id'],
					'version_no'     => (int) $row['version_no'] + 1,
					'schema_json'    => wp_json_encode( $templates[ $key ] ),
					'published_at'   => $now,
					'created_by'     => get_current_user_id(),
					'created_at'     => $now,
				)
			);
			if ( $version_id ) {
				Repository::update(
					'forms',
					(int) $row['id'],
					array(
						'name'               => Forms::template_label( $key ),
						'current_version_id' => $version_id,
						'updated_at'         => $now,
					),
					(int) $row['institution_id']
				);
			} else {
				$complete = false;
			}
		}
		if ( $complete ) {
			update_option( 'sakolaan_admission_indonesian_templates_migrated', 1, false );
		}
	}

	public static function render(): void {
		if ( ! current_user_can( 'sakolaan_admission_access_admin' ) ) {
			wp_die( esc_html__( 'Akses ditolak.', 'sakolaan-admission' ), '', array( 'response' => 403 ) ); }
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen routing.
		$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : 'overview';
		if ( ! in_array( $section, self::SECTIONS, true ) ) {
			$section = 'overview'; }
		$institution_id = Access::current_institution();
		echo '<div class="sakolaan-admission-admin">';
		echo '<header class="sakolaan-admission-header"><div><span class="sakolaan-admission-eyebrow">' . esc_html__( 'Manajemen Penerimaan', 'sakolaan-admission' ) . '</span><h2>' . esc_html( Labels::section( $section ) ) . '</h2><p>' . esc_html( self::section_description( $section ) ) . '</p></div>';
		self::context_selector( $institution_id );
		echo '</header><nav class="sakolaan-admission-navigation" aria-label="' . esc_attr__( 'Bagian Penerimaan', 'sakolaan-admission' ) . '">';
		foreach ( self::NAVIGATION_GROUPS as $group => $items ) {
			echo '<div class="sakolaan-admission-navigation__group"><span>' . esc_html( $group ) . '</span><div>';
			foreach ( $items as $item ) {
				$url = add_query_arg(
					array(
						'page'           => 'sakolaan',
						'view'           => 'admission',
						'section'        => $item,
						'institution_id' => $institution_id,
					),
					admin_url( 'admin.php' )
				);
				printf( '<a href="%1$s"%2$s>%3$s</a>', esc_url( $url ), $section === $item ? ' aria-current="page"' : '', esc_html( Labels::section( $item ) ) );
			}
			echo '</div></div>';
		}
		echo '</nav>';
		self::render_feedback();
		self::render_mail_notice();
		match ( $section ) {
			'overview' => self::overview( $institution_id ),
			'institutions' => self::institutions(),
			'programs' => self::programs( $institution_id ),
			'periods' => self::periods( $institution_id ),
			'forms' => self::forms( $institution_id ),
			'applicants' => self::applications( $institution_id ),
			'documents', 'verification' => self::documents( $institution_id ),
			'payments' => self::payments( $institution_id ),
			'assessment' => self::assessments( $institution_id ),
			'selection' => self::selection( $institution_id ),
			'announcements' => self::announcements( $institution_id ),
			'reports' => self::reports( $institution_id ),
			'settings' => self::settings(),
		};
		echo '</div>';
	}

	private static function section_description( string $section ): string {
		$descriptions = array(
			'overview'      => __( 'Pantau aktivitas penting dan pekerjaan yang perlu ditindaklanjuti.', 'sakolaan-admission' ),
			'institutions'  => __( 'Kelola institusi, penugasan petugas, dan informasi pembayaran.', 'sakolaan-admission' ),
			'programs'      => __( 'Atur program penerimaan, kuota, biaya, formulir, dan alur kerja.', 'sakolaan-admission' ),
			'periods'       => __( 'Tentukan gelombang, jadwal pendaftaran, dan kuota setiap periode.', 'sakolaan-admission' ),
			'forms'         => __( 'Terbitkan formulir dan alur kerja berversi untuk pendaftar.', 'sakolaan-admission' ),
			'applicants'    => __( 'Tinjau seluruh pendaftaran pada institusi yang sedang dipilih.', 'sakolaan-admission' ),
			'documents'     => __( 'Periksa dokumen pribadi melalui penyimpanan yang terlindungi.', 'sakolaan-admission' ),
			'verification'  => __( 'Selesaikan antrean verifikasi dokumen secara terkontrol.', 'sakolaan-admission' ),
			'payments'      => __( 'Verifikasi bukti pembayaran manual dan catat hasil pemeriksaan.', 'sakolaan-admission' ),
			'assessment'    => __( 'Kelola rubrik berbobot dan penugasan penilai.', 'sakolaan-admission' ),
			'selection'     => __( 'Terbitkan keputusan melalui perpindahan status yang diizinkan.', 'sakolaan-admission' ),
			'announcements' => __( 'Terbitkan informasi umum tanpa membuka data pribadi pendaftar.', 'sakolaan-admission' ),
			'reports'       => __( 'Analisis perkembangan penerimaan dan ekspor data sesuai kewenangan.', 'sakolaan-admission' ),
			'settings'      => __( 'Atur mode operasi, penyimpanan, portal, dan kebijakan penghapusan.', 'sakolaan-admission' ),
		);
		return $descriptions[ $section ] ?? '';
	}

	private static function render_feedback(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Feedback codes do not mutate state.
		$notice = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : ( isset( $_GET['saved'] ) ? 'saved' : '' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Feedback codes do not mutate state.
		$error            = isset( $_GET['config_error'] ) ? sanitize_key( wp_unslash( $_GET['config_error'] ) ) : '';
		$success_messages = array(
			'saved'              => __( 'Data penerimaan berhasil disimpan.', 'sakolaan-admission' ),
			'updated'            => __( 'Perubahan konfigurasi berhasil disimpan.', 'sakolaan-admission' ),
			'published'          => __( 'Konfigurasi berhasil diterbitkan.', 'sakolaan-admission' ),
			'draft_saved'        => __( 'Konfigurasi berhasil disimpan sebagai Draf.', 'sakolaan-admission' ),
			'drafted'            => __( 'Konfigurasi dikembalikan ke Draf. Pendaftaran baru tidak lagi menampilkannya.', 'sakolaan-admission' ),
			'activated'          => __( 'Institusi berhasil diaktifkan.', 'sakolaan-admission' ),
			'inactivated'        => __( 'Institusi dinonaktifkan. Pendaftaran baru untuk institusi ini dihentikan.', 'sakolaan-admission' ),
			'versioned'          => __( 'Versi baru berhasil diterbitkan tanpa mengubah versi historis.', 'sakolaan-admission' ),
			'mail_test_accepted' => __( 'Email uji diterima oleh konektor email. Periksa kotak masuk tujuan atau penangkap email development.', 'sakolaan-admission' ),
		);
		$error_messages   = array(
			'institution_inactive'        => __( 'Aktifkan institusi sebelum menerbitkan konfigurasi ini.', 'sakolaan-admission' ),
			'form_unpublished'            => __( 'Program memerlukan formulir dengan versi terbit.', 'sakolaan-admission' ),
			'workflow_unpublished'        => __( 'Program memerlukan alur dengan versi terbit.', 'sakolaan-admission' ),
			'configuration_scope'         => __( 'Referensi konfigurasi berasal dari institusi yang berbeda.', 'sakolaan-admission' ),
			'program_unpublished'         => __( 'Terbitkan program induk sebelum menerbitkan periode.', 'sakolaan-admission' ),
			'invalid_schedule'            => __( 'Tanggal penutupan harus lebih akhir daripada tanggal pembukaan.', 'sakolaan-admission' ),
			'announcement_incomplete'     => __( 'Lengkapi judul dan isi pengumuman sebelum menerbitkannya.', 'sakolaan-admission' ),
			'stale_configuration'         => __( 'Data telah diubah oleh pengguna lain. Muat ulang halaman sebelum mencoba lagi.', 'sakolaan-admission' ),
			'configuration_not_found'     => __( 'Data tidak ditemukan pada institusi yang sedang dipilih.', 'sakolaan-admission' ),
			'configuration_update_failed' => __( 'Perubahan tidak dapat disimpan. Periksa kembali isian Anda.', 'sakolaan-admission' ),
			'invalid_configuration'       => __( 'Permintaan perubahan konfigurasi tidak valid.', 'sakolaan-admission' ),
			'forbidden'                   => __( 'Anda tidak memiliki izin untuk melakukan perubahan tersebut.', 'sakolaan-admission' ),
			'invalid_email'               => __( 'Masukkan alamat email tujuan yang valid.', 'sakolaan-admission' ),
			'mail_test_failed'            => __( 'Konektor email tidak menerima email uji. Periksa rincian pada Diagnostik sistem.', 'sakolaan-admission' ),
			'mail_test_rate_limited'      => __( 'Batas lima pengujian email per jam telah tercapai. Coba kembali setelah satu jam.', 'sakolaan-admission' ),
		);
		if ( $error ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( $error_messages[ $error ] ?? __( 'Perubahan konfigurasi gagal disimpan.', 'sakolaan-admission' ) ) . '</p></div>';
		} elseif ( $notice ) {
			echo '<div class="notice notice-success inline"><p>' . esc_html( $success_messages[ $notice ] ?? $success_messages['saved'] ) . '</p></div>';
		}
	}

	private static function render_mail_notice(): void {
		$status                 = Mail_Diagnostics::status();
		list( $type, $message ) = Mail_Diagnostics::notice( $status );
		$settings_url           = add_query_arg(
			array(
				'page'    => 'sakolaan',
				'view'    => 'admission',
				'section' => 'settings',
			),
			admin_url( 'admin.php' )
		);
		echo '<div class="notice notice-' . esc_attr( $type ) . ' inline sakolaan-admission-mail-notice"><p><strong>' . esc_html__( 'Status pengiriman email:', 'sakolaan-admission' ) . '</strong> ' . esc_html( $message );
		if ( current_user_can( 'sakolaan_admission_manage_settings' ) ) {
			echo ' <a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Buka Diagnostik sistem', 'sakolaan-admission' ) . '</a>';
		}
		echo '</p></div>';
	}

	private static function context_selector( int $selected ): void {
		$ids = Access::institutions_for_user();
		if ( count( $ids ) < 2 ) {
			return; }
		echo '<form class="sakolaan-institution-context" method="get"><input type="hidden" name="page" value="sakolaan"><input type="hidden" name="view" value="admission"><label><span>' . esc_html__( 'Institusi aktif', 'sakolaan-admission' ) . '</span><select name="institution_id" onchange="this.form.submit()">';
		foreach ( $ids as $id ) {
			$row = Repository::get( 'institutions', $id );
			if ( $row ) {
				printf( '<option value="%1$d"%2$s>%3$s</option>', absint( $id ), selected( $selected, $id, false ), esc_html( $row['name'] ) ); }
		}
		echo '</select></label><noscript><button class="button">' . esc_html__( 'Terapkan', 'sakolaan-admission' ) . '</button></noscript></form>';
	}

	private static function overview( int $institution_id ): void {
		global $wpdb;
		$metrics = array(
			'programs'     => array(
				'label'   => __( 'Program', 'sakolaan-admission' ),
				'icon'    => 'dashicons-welcome-learn-more',
				'section' => 'programs',
			),
			'applications' => array(
				'label'   => __( 'Pendaftaran', 'sakolaan-admission' ),
				'icon'    => 'dashicons-id-alt',
				'section' => 'applicants',
			),
			'documents'    => array(
				'label'   => __( 'Dokumen', 'sakolaan-admission' ),
				'icon'    => 'dashicons-media-document',
				'section' => 'documents',
			),
			'payments'     => array(
				'label'   => __( 'Pembayaran', 'sakolaan-admission' ),
				'icon'    => 'dashicons-money-alt',
				'section' => 'payments',
			),
		);
		foreach ( $metrics as $table => &$metric ) {
			$metric['count'] = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Installer::table( $table ) . ' WHERE institution_id=%d', $institution_id ) ); }
		unset( $metric );
		echo '<div class="sakolaan-admission-cards">';
		foreach ( $metrics as $metric ) {
			$url = add_query_arg(
				array(
					'page'           => 'sakolaan',
					'view'           => 'admission',
					'section'        => $metric['section'],
					'institution_id' => $institution_id,
				),
				admin_url( 'admin.php' )
			);
			printf( '<a class="sakolaan-admission-metric" href="%1$s"><span class="dashicons %2$s" aria-hidden="true"></span><span><strong>%3$s</strong><small>%4$s</small></span><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></a>', esc_url( $url ), esc_attr( $metric['icon'] ), esc_html( Labels::number( (float) $metric['count'] ) ), esc_html( $metric['label'] ) ); }
		echo '</div>';
		$pending_documents = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Installer::table( 'documents' ) . " WHERE institution_id=%d AND status IN ('pending','revision_requested')", $institution_id ) );
		$pending_payments  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . Installer::table( 'payments' ) . " WHERE institution_id=%d AND status='pending_verification'", $institution_id ) );
		echo '<section class="sakolaan-admission-panel"><div class="sakolaan-admission-panel__heading"><div><h3>' . esc_html__( 'Perlu ditindaklanjuti', 'sakolaan-admission' ) . '</h3><p>' . esc_html__( 'Antrean yang memerlukan pemeriksaan petugas.', 'sakolaan-admission' ) . '</p></div></div><div class="sakolaan-admission-queue"><a href="' . esc_url(
			add_query_arg(
				array(
					'page'           => 'sakolaan',
					'view'           => 'admission',
					'section'        => 'verification',
					'institution_id' => $institution_id,
				),
				admin_url( 'admin.php' )
			)
		) . '"><strong>' . esc_html( Labels::number( (float) $pending_documents ) ) . '</strong><span>' . esc_html__( 'dokumen menunggu pemeriksaan', 'sakolaan-admission' ) . '</span></a><a href="' . esc_url(
			add_query_arg(
				array(
					'page'           => 'sakolaan',
					'view'           => 'admission',
					'section'        => 'payments',
					'institution_id' => $institution_id,
				),
				admin_url( 'admin.php' )
			)
		) . '"><strong>' . esc_html( Labels::number( (float) $pending_payments ) ) . '</strong><span>' . esc_html__( 'pembayaran menunggu verifikasi', 'sakolaan-admission' ) . '</span></a></div></section>';
		$storage = Documents::storage_path();
		if ( is_wp_error( $storage ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( $storage->get_error_message() ) . '</p></div>'; }
	}

	private static function institutions(): void {
		if ( ! current_user_can( 'sakolaan_admission_manage_institutions' ) && ! current_user_can( 'sakolaan_admission_manage_programs' ) ) {
			self::denied();
			return; }
		global $wpdb;
		$current_id = Access::current_institution();
		$rows       = current_user_can( 'sakolaan_admission_manage_institutions' ) ? $wpdb->get_results( 'SELECT * FROM ' . Installer::table( 'institutions' ) . ' ORDER BY name', ARRAY_A ) : array_filter( array( Repository::get( 'institutions', $current_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::guidance(
			__( 'Untuk apa data institusi digunakan?', 'sakolaan-admission' ),
			__( 'Institusi adalah batas kepemilikan dan keamanan seluruh data penerimaan. Menonaktifkannya menghentikan pendaftaran baru tanpa menghapus aplikasi lama.', 'sakolaan-admission' ),
			array(
				'id'     => __( 'Identitas internal untuk isolasi data institusi.', 'sakolaan-admission' ),
				'code'   => __( 'Kode permanen yang dapat dipakai dalam nomor pendaftaran dan laporan.', 'sakolaan-admission' ),
				'status' => __( 'Institusi Tidak Aktif tidak dapat menampilkan program baru di frontend.', 'sakolaan-admission' ),
			)
		);
		$institution_columns = array( 'id', 'code', 'name', 'status' );
		$institution_options = array();
		if ( current_user_can( 'sakolaan_admission_manage_institutions' ) ) {
			$institution_columns[] = 'actions';
			$institution_options   = array(
				'entity'         => 'institution',
				'section'        => 'institutions',
				'institution_id' => $current_id,
			);
		}
		self::table( $rows, $institution_columns, $institution_options );
		if ( current_user_can( 'sakolaan_admission_manage_institutions' ) ) {
			$edit_id = isset( $_GET['edit_institution'] ) ? absint( $_GET['edit_institution'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only edit routing.
			$edit    = $edit_id ? Repository::get( 'institutions', $edit_id ) : null;
			if ( $edit ) {
				self::configuration_form_start( 'institution', $edit, (int) $edit['id'] );
				echo '<h3>' . esc_html__( 'Edit institusi', 'sakolaan-admission' ) . '</h3><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'Kode institusi', 'sakolaan-admission' ) . ' <input value="' . esc_attr( $edit['code'] ) . '" disabled><small>' . esc_html__( 'Kode tidak dapat diubah setelah institusi dibuat.', 'sakolaan-admission' ) . '</small></label></p><p><label>' . esc_html__( 'Nama institusi', 'sakolaan-admission' ) . ' <input name="name" value="' . esc_attr( $edit['name'] ) . '" required></label></p></div><div class="sakolaan-admission-form-actions"><button class="button button-primary">' . esc_html__( 'Simpan perubahan', 'sakolaan-admission' ) . '</button></div>';
				self::form_end();
			}
			self::table( Repository::list( 'institution_users', $current_id ), array( 'user_id', 'assignment_role', 'created_at' ) );
			self::form_start( 'institution' );
			echo '<h3>' . esc_html__( 'Tambah institusi', 'sakolaan-admission' ) . '</h3><p class="description">' . esc_html__( 'Gunakan kode singkat yang unik untuk nomor pendaftaran dan laporan.', 'sakolaan-admission' ) . '</p><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'Kode institusi', 'sakolaan-admission' ) . ' <input name="code" required></label></p><p><label>' . esc_html__( 'Nama institusi', 'sakolaan-admission' ) . ' <input name="name" required></label></p></div>';
			submit_button( __( 'Tambah institusi', 'sakolaan-admission' ) );
			self::form_end();
			self::form_start( 'assignment', $current_id );
			echo '<h3>' . esc_html__( 'Tugaskan petugas', 'sakolaan-admission' ) . '</h3><p class="description">' . esc_html__( 'Pengguna juga harus memiliki peran WordPress yang sesuai dengan tugasnya.', 'sakolaan-admission' ) . '</p><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'ID pengguna WordPress', 'sakolaan-admission' ) . ' <input type="number" min="1" name="user_id" required></label></p><p><label>' . esc_html__( 'Peran penugasan', 'sakolaan-admission' ) . ' <select name="assignment_role"><option value="administrator">Administrator Institusi</option><option value="officer">Petugas Penerimaan</option><option value="document_verifier">Pemeriksa Dokumen</option><option value="payment_verifier">Pemeriksa Pembayaran</option><option value="assessment_reviewer">Penilai</option></select></label></p></div>';
			submit_button( __( 'Tugaskan petugas', 'sakolaan-admission' ) );
			self::form_end();
		}
		$current = $current_id ? Repository::get( 'institutions', $current_id ) : null;
		if ( $current ) {
			$institution_settings = (array) json_decode( (string) $current['settings'], true );
			self::form_start( 'institution_settings', $current_id );
			echo '<h3>' . esc_html__( 'Informasi pembayaran institusi', 'sakolaan-admission' ) . '</h3><p class="description">' . esc_html__( 'Informasi ini hanya ditampilkan kepada pendaftar yang memiliki kewajiban pembayaran.', 'sakolaan-admission' ) . '</p><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'Bank atau tujuan pembayaran', 'sakolaan-admission' ) . ' <input name="bank_name" value="' . esc_attr( $institution_settings['bank_name'] ?? '' ) . '"></label></p><p><label>' . esc_html__( 'Nomor rekening', 'sakolaan-admission' ) . ' <input name="account_number" value="' . esc_attr( $institution_settings['account_number'] ?? '' ) . '"></label></p><p><label>' . esc_html__( 'Nama pemilik rekening', 'sakolaan-admission' ) . ' <input name="account_holder" value="' . esc_attr( $institution_settings['account_holder'] ?? '' ) . '"></label></p></div><p><label>' . esc_html__( 'Petunjuk pembayaran', 'sakolaan-admission' ) . '<textarea name="payment_instructions">' . esc_textarea( $institution_settings['payment_instructions'] ?? '' ) . '</textarea></label></p>';
			submit_button( __( 'Simpan informasi pembayaran', 'sakolaan-admission' ) );
			self::form_end();
		}
	}

	private static function programs( int $institution_id ): void {
		if ( ! current_user_can( 'sakolaan_admission_manage_programs' ) ) {
			self::denied();
			return; }
		$programs    = Repository::list( 'programs', $institution_id );
		$periods     = Repository::list( 'periods', $institution_id, array(), 500 );
		$institution = Repository::get( 'institutions', $institution_id );
		foreach ( $programs as &$program_row ) {
			$program_row['effective_status'] = Configuration::effective_program( $program_row, $periods, $institution['status'] ?? 'inactive' );
		}
		unset( $program_row );
		self::guidance(
			__( 'Untuk apa data program digunakan?', 'sakolaan-admission' ),
			__( 'Program menghubungkan formulir, alur penerimaan, kuota, biaya, dan periode. Hanya program Terbit dengan periode yang efektif yang muncul di portal pendaftar.', 'sakolaan-admission' ),
			array(
				'id'               => __( 'Identitas internal program yang dipakai oleh periode, aplikasi, dokumen, dan laporan.', 'sakolaan-admission' ),
				'form_id'          => __( 'Formulir berversi yang diisi pendaftar.', 'sakolaan-admission' ),
				'workflow_id'      => __( 'Alur perpindahan tahap aplikasi setelah dikirim.', 'sakolaan-admission' ),
				'status'           => __( 'Persetujuan publikasi manual: Draf atau Terbit.', 'sakolaan-admission' ),
				'effective_status' => __( 'Kondisi tampil aktual berdasarkan institusi, periode, jadwal, dan kuota.', 'sakolaan-admission' ),
			)
		);
		self::table(
			$programs,
			array( 'id', 'name', 'form_id', 'workflow_id', 'intake', 'quota', 'fee_amount', 'currency', 'status', 'effective_status', 'actions' ),
			array(
				'entity'         => 'program',
				'section'        => 'programs',
				'institution_id' => $institution_id,
				'empty_message'  => __( 'Terbitkan formulir dan alur terlebih dahulu, lalu buat program penerimaan.', 'sakolaan-admission' ),
			)
		);
		$forms     = Repository::list( 'forms', $institution_id, array( 'status' => 'published' ) );
		$workflows = Repository::list( 'workflows', $institution_id, array( 'status' => 'published' ) );
		$edit_id   = isset( $_GET['edit_program'] ) ? absint( $_GET['edit_program'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only edit routing.
		$edit      = $edit_id ? Repository::get( 'programs', $edit_id, $institution_id ) : null;
		if ( $edit ) {
			self::configuration_form_start( 'program', $edit, $institution_id );
		} else {
			self::form_start( 'program', $institution_id );
		}
		echo '<h3>' . esc_html( $edit ? __( 'Edit program penerimaan', 'sakolaan-admission' ) : __( 'Tambah program penerimaan', 'sakolaan-admission' ) ) . '</h3><p class="description">' . esc_html__( 'Simpan sebagai Draf selama konfigurasi belum lengkap. Terbitkan setelah formulir dan alur siap.', 'sakolaan-admission' ) . '</p><div class="sakolaan-admission-form-grid">';
		echo '<p><label>' . esc_html__( 'Nama program', 'sakolaan-admission' ) . ' <input name="name" value="' . esc_attr( $edit['name'] ?? '' ) . '" required><small>' . esc_html__( 'Nama yang dilihat pendaftar pada kartu program.', 'sakolaan-admission' ) . '</small></label></p><p><label>' . esc_html__( 'Tahun atau angkatan', 'sakolaan-admission' ) . ' <input name="intake" value="' . esc_attr( $edit['intake'] ?? '' ) . '"><small>' . esc_html__( 'Contoh: 2027/2028 atau Angkatan Januari 2027.', 'sakolaan-admission' ) . '</small></label></p><p><label>' . esc_html__( 'Kuota program', 'sakolaan-admission' ) . ' <input type="number" min="1" name="quota" value="' . esc_attr( $edit['quota'] ?? '' ) . '"><small>' . esc_html__( 'Kosong berarti tidak dibatasi pada tingkat program.', 'sakolaan-admission' ) . '</small></label></p>';
		self::select( 'form_id', __( 'Formulir', 'sakolaan-admission' ), $forms, absint( $edit['form_id'] ?? 0 ) );
		self::select( 'workflow_id', __( 'Alur penerimaan', 'sakolaan-admission' ), $workflows, absint( $edit['workflow_id'] ?? 0 ) );
		echo '</div><p><label>' . esc_html__( 'Deskripsi program', 'sakolaan-admission' ) . '<textarea name="description">' . esc_textarea( $edit['description'] ?? '' ) . '</textarea><small>' . esc_html__( 'Ringkasan ini tampil pada kartu program di frontend.', 'sakolaan-admission' ) . '</small></label></p><p><label class="sakolaan-admission-check"><input type="checkbox" name="fee_required" value="1"' . checked( ! empty( $edit['fee_required'] ), true, false ) . '> ' . esc_html__( 'Program memerlukan biaya pendaftaran', 'sakolaan-admission' ) . '</label></p><p><label>' . esc_html__( 'Jumlah biaya (IDR)', 'sakolaan-admission' ) . ' <input type="number" min="0" step="0.01" name="fee_amount" value="' . esc_attr( $edit['fee_amount'] ?? '0' ) . '"><small>' . esc_html__( 'Diabaikan bila biaya pendaftaran tidak diaktifkan.', 'sakolaan-admission' ) . '</small></label></p>';
		self::publication_buttons( (string) ( $edit['status'] ?? 'draft' ) );
		self::form_end();
		self::form_start( 'document_requirement', $institution_id );
		echo '<h3>' . esc_html__( 'Tambah persyaratan dokumen', 'sakolaan-admission' ) . '</h3>';
		self::select( 'program_id', __( 'Program', 'sakolaan-admission' ), Repository::list( 'programs', $institution_id ) );
		echo '<div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'Kunci dokumen', 'sakolaan-admission' ) . ' <input name="document_key" required><small>' . esc_html__( 'Identitas teknis permanen, misalnya kartu_keluarga.', 'sakolaan-admission' ) . '</small></label></p><p><label>' . esc_html__( 'Nama dokumen', 'sakolaan-admission' ) . ' <input name="label" required><small>' . esc_html__( 'Nama yang dibaca pendaftar.', 'sakolaan-admission' ) . '</small></label></p></div><p><label class="sakolaan-admission-check"><input type="checkbox" name="required" value="1" checked> ' . esc_html__( 'Dokumen wajib diunggah', 'sakolaan-admission' ) . '</label></p>';
		submit_button( __( 'Tambah persyaratan', 'sakolaan-admission' ) );
		self::form_end();
		$requirements = Repository::list( 'document_requirements', $institution_id, array(), 500 );
		self::table(
			$requirements,
			array( 'id', 'program_id', 'document_key', 'label', 'required', 'allowed_mimes', 'max_size', 'sort_order', 'actions' ),
			array(
				'entity'         => 'requirement',
				'section'        => 'programs',
				'institution_id' => $institution_id,
			)
		);
		$requirement_id = isset( $_GET['edit_requirement'] ) ? absint( $_GET['edit_requirement'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only edit routing.
		$requirement    = $requirement_id ? Repository::get( 'document_requirements', $requirement_id, $institution_id ) : null;
		if ( $requirement ) {
			self::configuration_form_start( 'requirement', $requirement, $institution_id );
			echo '<h3>' . esc_html__( 'Edit persyaratan dokumen', 'sakolaan-admission' ) . '</h3><p><label>' . esc_html__( 'Kunci dokumen', 'sakolaan-admission' ) . ' <input value="' . esc_attr( $requirement['document_key'] ) . '" disabled><small>' . esc_html__( 'Kunci tidak dapat diubah agar dokumen historis tetap terhubung.', 'sakolaan-admission' ) . '</small></label></p><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'Nama dokumen', 'sakolaan-admission' ) . ' <input name="label" value="' . esc_attr( $requirement['label'] ) . '" required></label></p><p><label>' . esc_html__( 'Ukuran maksimum (byte)', 'sakolaan-admission' ) . ' <input type="number" min="1024" name="max_size" value="' . esc_attr( $requirement['max_size'] ) . '" required></label></p><p><label>' . esc_html__( 'MIME yang diizinkan', 'sakolaan-admission' ) . ' <input name="allowed_mimes" value="' . esc_attr( $requirement['allowed_mimes'] ) . '" required><small>' . esc_html__( 'Pisahkan dengan koma. Hanya PDF, JPEG, PNG, dan WebP yang diterima.', 'sakolaan-admission' ) . '</small></label></p><p><label>' . esc_html__( 'Urutan', 'sakolaan-admission' ) . ' <input type="number" name="sort_order" value="' . esc_attr( $requirement['sort_order'] ) . '"></label></p></div><p><label class="sakolaan-admission-check"><input type="checkbox" name="required" value="1"' . checked( ! empty( $requirement['required'] ), true, false ) . '> ' . esc_html__( 'Dokumen wajib diunggah', 'sakolaan-admission' ) . '</label></p>';
			submit_button( __( 'Simpan perubahan persyaratan', 'sakolaan-admission' ) );
			self::form_end();
		}
	}

	private static function periods( int $institution_id ): void {
		if ( ! current_user_can( 'sakolaan_admission_manage_programs' ) ) {
			self::denied();
			return; }
		$periods     = Repository::list( 'periods', $institution_id, array(), 500 );
		$programs    = Repository::list( 'programs', $institution_id, array(), 500 );
		$institution = Repository::get( 'institutions', $institution_id );
		$program_map = array();
		foreach ( $programs as $program ) {
			$program_map[ (int) $program['id'] ] = $program;
		}
		foreach ( $periods as &$period_row ) {
			$parent                         = $program_map[ (int) $period_row['program_id'] ] ?? array();
			$period_row['program_quota']    = $parent['quota'] ?? null;
			$period_row['program_enrolled'] = $parent['enrolled_count'] ?? 0;
			$period_row['effective_status'] = Configuration::effective_period( $period_row, $parent['status'] ?? 'draft', $institution['status'] ?? 'inactive' );
		}
		unset( $period_row );
		self::guidance(
			__( 'Untuk apa data periode digunakan?', 'sakolaan-admission' ),
			__( 'Periode menentukan kapan sebuah program menerima pendaftaran. Status Terbit adalah persetujuan manual; Status Efektif berubah otomatis mengikuti jadwal dan kuota.', 'sakolaan-admission' ),
			array(
				'program_id'         => __( 'ID program induk yang menggunakan periode ini.', 'sakolaan-admission' ),
				'opens_at/closes_at' => __( 'Jadwal otomatis untuk status Segera Dibuka, Sedang Dibuka, atau Ditutup.', 'sakolaan-admission' ),
				'payment_due_at'     => __( 'Tenggat pembayaran untuk kewajiban yang dibuat setelah aplikasi dikirim.', 'sakolaan-admission' ),
				'effective_status'   => __( 'Status aktual yang dipakai frontend tanpa mengubah nilai publikasi.', 'sakolaan-admission' ),
			)
		);
		self::table(
			$periods,
			array( 'id', 'program_id', 'name', 'opens_at', 'closes_at', 'payment_due_at', 'quota', 'status', 'effective_status', 'actions' ),
			array(
				'entity'         => 'period',
				'section'        => 'periods',
				'institution_id' => $institution_id,
				'empty_message'  => __( 'Buat program terlebih dahulu, kemudian tambahkan jadwal periode agar program dapat tampil di frontend.', 'sakolaan-admission' ),
			)
		);
		$edit_id = isset( $_GET['edit_period'] ) ? absint( $_GET['edit_period'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only edit routing.
		$edit    = $edit_id ? Repository::get( 'periods', $edit_id, $institution_id ) : null;
		if ( $edit ) {
			self::configuration_form_start( 'period', $edit, $institution_id );
		} else {
			self::form_start( 'period', $institution_id );
		}
		// translators: %s: configured WordPress site timezone.
		echo '<h3>' . esc_html( $edit ? __( 'Edit periode penerimaan', 'sakolaan-admission' ) : __( 'Tambah periode penerimaan', 'sakolaan-admission' ) ) . '</h3><p class="description">' . esc_html( sprintf( __( 'Tanggal mengendalikan ketersediaan frontend setelah periode diterbitkan. Waktu mengikuti zona situs: %s.', 'sakolaan-admission' ), wp_timezone_string() ) ) . '</p>';
		self::select( 'program_id', __( 'Program', 'sakolaan-admission' ), $programs, absint( $edit['program_id'] ?? 0 ) );
		echo '<div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'Nama periode atau gelombang', 'sakolaan-admission' ) . ' <input name="name" value="' . esc_attr( $edit['name'] ?? '' ) . '" required><small>' . esc_html__( 'Contoh: Gelombang 1 atau Pendaftaran Januari.', 'sakolaan-admission' ) . '</small></label></p><p><label>' . esc_html__( 'Mulai pendaftaran', 'sakolaan-admission' ) . ' <input type="datetime-local" name="opens_at" value="' . esc_attr( self::datetime_input( $edit['opens_at'] ?? '' ) ) . '" required></label></p><p><label>' . esc_html__( 'Akhir pendaftaran', 'sakolaan-admission' ) . ' <input type="datetime-local" name="closes_at" value="' . esc_attr( self::datetime_input( $edit['closes_at'] ?? '' ) ) . '" required></label></p><p><label>' . esc_html__( 'Tenggat pembayaran', 'sakolaan-admission' ) . ' <input type="datetime-local" name="payment_due_at" value="' . esc_attr( self::datetime_input( $edit['payment_due_at'] ?? '' ) ) . '"><small>' . esc_html__( 'Opsional; digunakan saat program mewajibkan biaya.', 'sakolaan-admission' ) . '</small></label></p><p><label>' . esc_html__( 'Kuota periode', 'sakolaan-admission' ) . ' <input type="number" min="1" name="quota" value="' . esc_attr( $edit['quota'] ?? '' ) . '"><small>' . esc_html__( 'Kosong berarti mengikuti batas program saja.', 'sakolaan-admission' ) . '</small></label></p></div>';
		self::publication_buttons( (string) ( $edit['status'] ?? 'draft' ) );
		self::form_end();
	}

	private static function forms( int $institution_id ): void {
		if ( ! current_user_can( 'sakolaan_admission_manage_forms' ) ) {
			self::denied();
			return; }
		self::guidance(
			__( 'Mengapa formulir dan alur memakai versi?', 'sakolaan-admission' ),
			__( 'Setiap penerbitan membuat versi immutable. Aplikasi lama tetap memakai versi yang disematkan, sedangkan aplikasi baru memakai versi aktif terbaru.', 'sakolaan-admission' ),
			array(
				'current_version_id' => __( 'ID versi terbit yang digunakan untuk aplikasi baru.', 'sakolaan-admission' ),
				'schema_json'        => __( 'Definisi langkah, bidang, validasi, dan kondisi formulir.', 'sakolaan-admission' ),
				'definition_json'    => __( 'Definisi status, transisi, kapabilitas, dan pemeriksaan alur.', 'sakolaan-admission' ),
			)
		);
		$forms = Repository::list( 'forms', $institution_id );
		self::table(
			$forms,
			array( 'id', 'name', 'status', 'current_version_id', 'updated_at', 'actions' ),
			array(
				'entity'         => 'form',
				'section'        => 'forms',
				'institution_id' => $institution_id,
			)
		);
		$edit_form_id = isset( $_GET['edit_form'] ) ? absint( $_GET['edit_form'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only edit routing.
		$edit_form    = $edit_form_id ? Repository::get( 'forms', $edit_form_id, $institution_id ) : null;
		$form_version = $edit_form && $edit_form['current_version_id'] ? Repository::get( 'form_versions', (int) $edit_form['current_version_id'], $institution_id ) : null;
		self::form_start( 'form', $institution_id );
		echo '<h3>' . esc_html( $edit_form ? __( 'Terbitkan versi baru formulir', 'sakolaan-admission' ) : __( 'Terbitkan formulir', 'sakolaan-admission' ) ) . '</h3><input type="hidden" name="form_id" value="' . absint( $edit_form_id ) . '"><input type="hidden" name="expected_updated_at" value="' . esc_attr( (string) ( $edit_form['updated_at'] ?? '' ) ) . '"><p class="description">' . esc_html__( 'Versi aktif tidak berubah sampai skema baru lolos validasi dan berhasil diterbitkan. Gunakan tindakan Edit pada tabel untuk membuat versi berikutnya.', 'sakolaan-admission' ) . '</p><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'Nama formulir', 'sakolaan-admission' ) . ' <input name="name" value="' . esc_attr( $edit_form['name'] ?? '' ) . '" required></label></p><p><label>' . esc_html__( 'Templat awal', 'sakolaan-admission' ) . ' <select name="template">';
		foreach ( array_keys( Forms::templates() ) as $template ) {
			printf( '<option value="%1$s">%2$s</option>', esc_attr( $template ), esc_html( Forms::template_label( $template ) ) ); }
		echo '</select></label></p></div><p><label>' . esc_html__( 'Skema JSON tervalidasi (opsional)', 'sakolaan-admission' ) . '<textarea class="large-text code" rows="14" name="schema_json">' . esc_textarea( $form_version['schema_json'] ?? '' ) . '</textarea></label><span class="description">' . esc_html__( 'Edit skema aktif untuk membuat versi berikutnya. Jika kosong pada formulir baru, templat awal digunakan.', 'sakolaan-admission' ) . '</span></p>';
		submit_button( $edit_form ? __( 'Validasi dan terbitkan versi baru', 'sakolaan-admission' ) : __( 'Terbitkan formulir', 'sakolaan-admission' ) );
		self::form_end();
		$workflows = Repository::list( 'workflows', $institution_id );
		self::table(
			$workflows,
			array( 'id', 'name', 'status', 'current_version_id', 'updated_at', 'actions' ),
			array(
				'entity'         => 'workflow',
				'section'        => 'forms',
				'institution_id' => $institution_id,
			)
		);
		$edit_workflow_id = isset( $_GET['edit_workflow'] ) ? absint( $_GET['edit_workflow'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only edit routing.
		$edit_workflow    = $edit_workflow_id ? Repository::get( 'workflows', $edit_workflow_id, $institution_id ) : null;
		$workflow_version = $edit_workflow && $edit_workflow['current_version_id'] ? Repository::get( 'workflow_versions', (int) $edit_workflow['current_version_id'], $institution_id ) : null;
		$workflow_schema  = $workflow_version ? json_decode( (string) $workflow_version['definition_json'], true ) : array();
		$simple_workflow  = $workflow_schema && ! in_array( 'document_verification', (array) ( $workflow_schema['states'] ?? array() ), true );
		self::form_start( 'workflow', $institution_id );
		echo '<h3>' . esc_html( $edit_workflow ? __( 'Terbitkan versi baru alur', 'sakolaan-admission' ) : __( 'Terbitkan alur penerimaan', 'sakolaan-admission' ) ) . '</h3><input type="hidden" name="workflow_id" value="' . absint( $edit_workflow_id ) . '"><input type="hidden" name="expected_updated_at" value="' . esc_attr( (string) ( $edit_workflow['updated_at'] ?? '' ) ) . '"><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'Nama alur', 'sakolaan-admission' ) . ' <input name="name" value="' . esc_attr( $edit_workflow['name'] ?? '' ) . '" required></label></p><p><label>' . esc_html__( 'Jenis alur', 'sakolaan-admission' ) . ' <select name="workflow_type"><option value="default"' . selected( $simple_workflow, false, false ) . '>' . esc_html__( 'Penerimaan lengkap', 'sakolaan-admission' ) . '</option><option value="simple"' . selected( $simple_workflow, true, false ) . '>' . esc_html__( 'Pendaftaran sederhana', 'sakolaan-admission' ) . '</option></select><small>' . esc_html__( 'Penerimaan lengkap mencakup dokumen, pembayaran, penilaian, dan keputusan.', 'sakolaan-admission' ) . '</small></label></p></div>';
		if ( $workflow_version ) {
			echo '<details class="sakolaan-admission-preview"><summary>' . esc_html__( 'Pratinjau definisi versi aktif', 'sakolaan-admission' ) . '</summary><pre>' . esc_html( wp_json_encode( $workflow_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ) . '</pre></details>';
		}
		submit_button( $edit_workflow ? __( 'Terbitkan versi baru alur', 'sakolaan-admission' ) : __( 'Terbitkan alur', 'sakolaan-admission' ) );
		self::form_end();
	}

	private static function applications( int $institution_id ): void {
		self::guidance(
			__( 'Untuk apa data pendaftaran digunakan?', 'sakolaan-admission' ),
			__( 'Daftar ini adalah catatan transaksi setiap pendaftar. ID teknis menghubungkan pendaftaran dengan program, periode, formulir, dokumen, pembayaran, dan riwayat status.', 'sakolaan-admission' ),
			array(
				'program_id'         => __( 'Program yang dipilih saat draf dibuat.', 'sakolaan-admission' ),
				'period_id'          => __( 'Gelombang pendaftaran yang dipilih.', 'sakolaan-admission' ),
				'application_number' => __( 'Nomor publik untuk komunikasi; bukan kunci akses.', 'sakolaan-admission' ),
			)
		);
		self::table( Repository::list( 'applications', $institution_id ), array( 'application_number', 'program_id', 'period_id', 'status', 'submitted_at', 'updated_at' ) );
		if ( current_user_can( 'sakolaan_admission_manage_applications' ) ) {
			self::form_start( 'verify_applicant', $institution_id );
			echo '<h3>' . esc_html__( 'Verifikasi email secara manual', 'sakolaan-admission' ) . '</h3><p class="description">' . esc_html__( 'Gunakan hanya jika pendaftar tidak dapat menerima email verifikasi otomatis.', 'sakolaan-admission' ) . '</p><p><label>' . esc_html__( 'ID pengguna WordPress', 'sakolaan-admission' ) . ' <input type="number" min="1" name="user_id" required></label></p>';
			submit_button( __( 'Verifikasi email', 'sakolaan-admission' ) );
			self::form_end(); } }
	private static function documents( int $institution_id ): void {
		self::guidance(
			__( 'Untuk apa data dokumen digunakan?', 'sakolaan-admission' ),
			__( 'Dokumen adalah berkas privat milik aplikasi dan hanya dapat diunduh melalui pemeriksaan izin. Pemeriksaan tidak mengubah berkas historis.', 'sakolaan-admission' ),
			array(
				'application_id' => __( 'ID pendaftaran pemilik dokumen.', 'sakolaan-admission' ),
				'kind'           => __( 'Kelompok penggunaan berkas, misalnya dokumen aplikasi atau bukti pembayaran.', 'sakolaan-admission' ),
				'mime_type'      => __( 'Format berkas yang dideteksi dan divalidasi saat unggah.', 'sakolaan-admission' ),
			)
		);
		self::table( Repository::list( 'documents', $institution_id ), array( 'id', 'application_id', 'kind', 'original_name', 'mime_type', 'status', 'reviewer_notes', 'created_at' ) );
		if ( current_user_can( 'sakolaan_admission_verify_documents' ) ) {
			self::form_start( 'document_review', $institution_id );
			echo '<h3>' . esc_html__( 'Periksa dokumen', 'sakolaan-admission' ) . '</h3><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'ID dokumen', 'sakolaan-admission' ) . ' <input type="number" name="document_id" required></label></p><p><label>' . esc_html__( 'Hasil pemeriksaan', 'sakolaan-admission' ) . ' <select name="status"><option value="verified">Terverifikasi</option><option value="rejected">Ditolak</option><option value="revision_requested">Perlu perbaikan</option></select></label></p></div><p><label>' . esc_html__( 'Catatan untuk pendaftar', 'sakolaan-admission' ) . '<textarea name="notes"></textarea></label></p>';
			submit_button( __( 'Simpan hasil pemeriksaan', 'sakolaan-admission' ) );
			self::form_end(); } }
	private static function payments( int $institution_id ): void {
		self::guidance(
			__( 'Untuk apa data pembayaran digunakan?', 'sakolaan-admission' ),
			__( 'Kewajiban pembayaran dibuat setelah aplikasi dikirim. Bukti unggahan selalu menunggu pemeriksaan manual dan tidak pernah otomatis dianggap lunas.', 'sakolaan-admission' ),
			array(
				'application_id'    => __( 'ID pendaftaran yang memiliki kewajiban pembayaran.', 'sakolaan-admission' ),
				'payment_reference' => __( 'Referensi unik untuk pencocokan transaksi.', 'sakolaan-admission' ),
				'lock_version'      => __( 'Nomor pengaman agar dua petugas tidak menimpa perubahan secara bersamaan.', 'sakolaan-admission' ),
			)
		);
		self::table( Repository::list( 'payments', $institution_id ), array( 'id', 'application_id', 'amount', 'currency', 'payment_reference', 'status', 'lock_version', 'updated_at' ) );
		if ( current_user_can( 'sakolaan_admission_verify_payments' ) ) {
			self::form_start( 'payment_review', $institution_id );
			echo '<h3>' . esc_html__( 'Periksa pembayaran', 'sakolaan-admission' ) . '</h3><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'ID pembayaran', 'sakolaan-admission' ) . ' <input type="number" name="payment_id" required></label></p><p><label>' . esc_html__( 'Hasil pemeriksaan', 'sakolaan-admission' ) . ' <select name="status"><option value="verified">Terverifikasi</option><option value="rejected">Ditolak</option><option value="expired">Kedaluwarsa</option></select></label></p></div><p><label>' . esc_html__( 'Catatan untuk pendaftar', 'sakolaan-admission' ) . '<textarea name="notes"></textarea></label></p>';
			submit_button( __( 'Simpan hasil pemeriksaan', 'sakolaan-admission' ) );
			self::form_end(); } }
	private static function assessments( int $institution_id ): void {
		self::guidance(
			__( 'Untuk apa data penilaian digunakan?', 'sakolaan-admission' ),
			__( 'Rubrik menentukan kriteria dan bobot; penugasan menghubungkan aplikasi, versi rubrik, dan petugas penilai. Nilai tidak menerbitkan keputusan secara otomatis.', 'sakolaan-admission' ),
			array(
				'reviewer_id'       => __( 'ID pengguna WordPress yang ditugaskan menilai.', 'sakolaan-admission' ),
				'rubric_version_id' => __( 'Versi rubrik tetap yang menjaga hasil historis dapat ditelusuri.', 'sakolaan-admission' ),
				'application_id'    => __( 'ID pendaftaran yang dinilai.', 'sakolaan-admission' ),
			)
		);
		self::table( Repository::list( 'assessments', $institution_id ), array( 'application_id', 'reviewer_id', 'total_score', 'recommendation', 'status', 'updated_at' ) );
		if ( ! current_user_can( 'sakolaan_admission_manage_forms' ) && ! current_user_can( 'sakolaan_admission_manage_applications' ) ) {
			return; }
		self::form_start( 'rubric', $institution_id );
		echo '<h3>' . esc_html__( 'Buat rubrik berbobot', 'sakolaan-admission' ) . '</h3>';
		self::select( 'program_id', __( 'Program', 'sakolaan-admission' ), Repository::list( 'programs', $institution_id ) );
		echo '<p><label>' . esc_html__( 'Nama rubrik', 'sakolaan-admission' ) . ' <input name="name" required></label></p><p><label>' . esc_html__( 'Kriteria JSON', 'sakolaan-admission' ) . '<textarea class="large-text code" rows="8" name="criteria_json" required>[{"key":"akademik","label":"Akademik","weight":60,"max_score":100},{"key":"wawancara","label":"Wawancara","weight":40,"max_score":100}]</textarea></label></p>';
		submit_button( __( 'Buat rubrik', 'sakolaan-admission' ) );
		self::form_end();
		self::form_start( 'reviewer_assignment', $institution_id );
		echo '<h3>' . esc_html__( 'Tugaskan penilai', 'sakolaan-admission' ) . '</h3><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'ID pendaftaran', 'sakolaan-admission' ) . ' <input type="number" name="application_id" required></label></p><p><label>' . esc_html__( 'ID pengguna penilai', 'sakolaan-admission' ) . ' <input type="number" name="reviewer_id" required></label></p><p><label>' . esc_html__( 'ID versi rubrik', 'sakolaan-admission' ) . ' <input type="number" name="rubric_version_id" required></label></p></div>';
		submit_button( __( 'Tugaskan penilai', 'sakolaan-admission' ) );
		self::form_end();
	}
	private static function selection( int $institution_id ): void {
		self::guidance(
			__( 'Untuk apa data seleksi digunakan?', 'sakolaan-admission' ),
			__( 'Layar ini memindahkan aplikasi melalui tahap yang diizinkan. Setiap perubahan dicatat dalam riwayat dan hanya dapat dilakukan oleh petugas berwenang.', 'sakolaan-admission' ),
			array(
				'application_number'    => __( 'Nomor pendaftaran untuk komunikasi dengan pendaftar.', 'sakolaan-admission' ),
				'lock_version'          => __( 'Versi data untuk mencegah perubahan status bersamaan.', 'sakolaan-admission' ),
				'decision_published_at' => __( 'Waktu keputusan mulai dapat dilihat oleh pendaftar.', 'sakolaan-admission' ),
			)
		);
		self::table( Repository::list( 'applications', $institution_id ), array( 'id', 'application_number', 'program_id', 'status', 'lock_version', 'decision_published_at', 'updated_at' ) );
		if ( current_user_can( 'sakolaan_admission_manage_applications' ) || current_user_can( 'sakolaan_admission_publish_decisions' ) || current_user_can( 'sakolaan_admission_confirm_enrollment' ) ) {
			self::form_start( 'application_transition', $institution_id );
			echo '<h3>' . esc_html__( 'Ubah tahap penerimaan', 'sakolaan-admission' ) . '</h3><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'ID pendaftaran', 'sakolaan-admission' ) . ' <input type="number" name="application_id" required></label></p><p><label>' . esc_html__( 'Tahap baru', 'sakolaan-admission' ) . ' <select name="to"><option value="document_verification">Verifikasi Dokumen</option><option value="payment_verification">Verifikasi Pembayaran</option><option value="assessment">Penilaian</option><option value="decision_pending">Menunggu Keputusan</option><option value="accepted">Diterima</option><option value="waitlisted">Daftar Tunggu</option><option value="rejected">Ditolak</option><option value="enrollment_confirmed">Pendaftaran Ulang Terkonfirmasi</option></select></label></p></div><p><label>' . esc_html__( 'Alasan atau catatan', 'sakolaan-admission' ) . '<textarea name="reason"></textarea></label></p>';
			submit_button( __( 'Terapkan perubahan tahap', 'sakolaan-admission' ) );
			self::form_end(); } }

	private static function announcements( int $institution_id ): void {
		if ( ! current_user_can( 'sakolaan_admission_manage_announcements' ) ) {
			self::denied();
			return; }
		self::guidance(
			__( 'Untuk apa data pengumuman digunakan?', 'sakolaan-admission' ),
			__( 'Pengumuman adalah informasi publik pada portal penerimaan. Gunakan cakupan program bila informasi hanya relevan untuk satu program dan jangan memasukkan data pribadi pendaftar.', 'sakolaan-admission' ),
			array(
				'program_id'   => __( 'Kosong berarti pengumuman berlaku umum untuk institusi.', 'sakolaan-admission' ),
				'published_at' => __( 'Waktu pertama atau terakhir pengumuman diterbitkan.', 'sakolaan-admission' ),
			)
		);
		$announcements = Repository::list( 'announcements', $institution_id );
		self::table(
			$announcements,
			array( 'id', 'program_id', 'title', 'status', 'published_at', 'actions' ),
			array(
				'entity'         => 'announcement',
				'section'        => 'announcements',
				'institution_id' => $institution_id,
				'empty_message'  => __( 'Buat pengumuman umum atau pilih program tertentu, lalu terbitkan setelah isinya siap.', 'sakolaan-admission' ),
			)
		);
		$edit_id = isset( $_GET['edit_announcement'] ) ? absint( $_GET['edit_announcement'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only edit routing.
		$edit    = $edit_id ? Repository::get( 'announcements', $edit_id, $institution_id ) : null;
		if ( $edit ) {
			self::configuration_form_start( 'announcement', $edit, $institution_id );
		} else {
			self::form_start( 'announcement', $institution_id );
		}
		echo '<h3>' . esc_html( $edit ? __( 'Edit pengumuman', 'sakolaan-admission' ) : __( 'Tambah pengumuman', 'sakolaan-admission' ) ) . '</h3><p class="description">' . esc_html__( 'Simpan sebagai Draf sampai isi siap dilihat publik.', 'sakolaan-admission' ) . '</p><p><label>' . esc_html__( 'Judul', 'sakolaan-admission' ) . ' <input name="title" value="' . esc_attr( $edit['title'] ?? '' ) . '" required></label></p>';
		echo '<p><label>' . esc_html__( 'Cakupan program', 'sakolaan-admission' ) . ' <select name="program_id"><option value="0">' . esc_html__( 'Semua program', 'sakolaan-admission' ) . '</option>';
		foreach ( Repository::list( 'programs', $institution_id ) as $program ) {
			printf( '<option value="%1$d"%2$s>%3$s</option>', (int) $program['id'], selected( absint( $edit['program_id'] ?? 0 ), (int) $program['id'], false ), esc_html( $program['name'] ) );
		}
		echo '</select><small>' . esc_html__( 'Pilih Semua program untuk informasi umum institusi.', 'sakolaan-admission' ) . '</small></label></p><p><label>' . esc_html__( 'Isi pengumuman', 'sakolaan-admission' ) . '<textarea name="content" required>' . esc_textarea( $edit['content'] ?? '' ) . '</textarea></label></p>';
		self::publication_buttons( (string) ( $edit['status'] ?? 'draft' ) );
		self::form_end();
	}

	private static function reports( int $institution_id ): void {
		if ( ! current_user_can( 'sakolaan_admission_view_reports' ) ) {
			self::denied();
			return; }
		self::guidance(
			__( 'Untuk apa laporan digunakan?', 'sakolaan-admission' ),
			__( 'Laporan merangkum data pada institusi aktif. Ekspor CSV mengikuti konteks institusi, menetralkan rumus spreadsheet, dan dicatat pada audit.', 'sakolaan-admission' ),
			array(
				'total'                => __( 'Jumlah transaksi dalam kelompok laporan.', 'sakolaan-admission' ),
				'program_id/period_id' => __( 'Referensi teknis yang dapat dicocokkan dengan tabel konfigurasi.', 'sakolaan-admission' ),
			)
		);
		global $wpdb;
		echo '<h3>' . esc_html__( 'Status pendaftaran', 'sakolaan-admission' ) . '</h3>';
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) total FROM ' . Installer::table( 'applications' ) . ' WHERE institution_id=%d GROUP BY status ORDER BY status', $institution_id ), ARRAY_A );
		self::table( $rows, array( 'status', 'total' ) );
		echo '<h3>' . esc_html__( 'Pendaftaran per program dan periode', 'sakolaan-admission' ) . '</h3>';
		$program_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT p.name program, pe.name period, COUNT(a.id) total FROM ' . Installer::table( 'applications' ) . ' a INNER JOIN ' . Installer::table( 'programs' ) . ' p ON p.id=a.program_id INNER JOIN ' . Installer::table( 'periods' ) . ' pe ON pe.id=a.period_id WHERE a.institution_id=%d GROUP BY a.program_id,a.period_id,p.name,pe.name ORDER BY p.name,pe.name', $institution_id ), ARRAY_A );
		self::table( $program_rows, array( 'program', 'period', 'total' ) );
		echo '<h3>' . esc_html__( 'Perkembangan verifikasi', 'sakolaan-admission' ) . '</h3>';
		$document_rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) total FROM ' . Installer::table( 'documents' ) . ' WHERE institution_id=%d GROUP BY status', $institution_id ), ARRAY_A );
		$payment_rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) total FROM ' . Installer::table( 'payments' ) . ' WHERE institution_id=%d GROUP BY status', $institution_id ), ARRAY_A );
		foreach ( $document_rows as &$row ) {
			$row['item'] = __( 'Dokumen', 'sakolaan-admission' ) . ': ' . Labels::status( $row['status'] ); }
		unset( $row );
		foreach ( $payment_rows as &$row ) {
			$row['item'] = __( 'Pembayaran', 'sakolaan-admission' ) . ': ' . Labels::status( $row['status'] ); }
		unset( $row );
		self::table( array_merge( $document_rows, $payment_rows ), array( 'item', 'total' ) );
		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action'         => 'sakolaan_admission_export',
					'institution_id' => $institution_id,
				),
				admin_url( 'admin-post.php' )
			),
			'sakolaan_admission_export_' . $institution_id
		);
		printf( '<p><a class="button button-primary" href="%1$s"><span class="dashicons dashicons-download" aria-hidden="true"></span>%2$s</a></p>', esc_url( $url ), esc_html__( 'Ekspor pendaftaran ke CSV', 'sakolaan-admission' ) );
	}

	private static function settings(): void {
		if ( ! current_user_can( 'sakolaan_admission_manage_settings' ) ) {
			self::denied();
			return; }
		$settings = (array) get_option( 'sakolaan_admission_settings', array() );
		self::guidance(
			__( 'Untuk apa pengaturan sistem digunakan?', 'sakolaan-admission' ),
			__( 'Pengaturan ini mengendalikan mode institusi, batas unggahan privat, integrasi portal, dan kebijakan penghapusan. Perubahan berlaku untuk operasi berikutnya dan tidak menghapus data historis.', 'sakolaan-admission' ),
			array(
				'mode'                     => __( 'Satu institusi menyederhanakan konteks; banyak institusi mewajibkan isolasi akses per institusi.', 'sakolaan-admission' ),
				'max_upload_size'          => __( 'Batas aplikasi sebelum batas server; nilai paling ketat yang berlaku.', 'sakolaan-admission' ),
				'delete_data_on_uninstall' => __( 'Tetap nonaktif kecuali administrator mengetik konfirmasi eksplisit.', 'sakolaan-admission' ),
			)
		);
		self::form_start( 'settings' );
		echo '<h3>' . esc_html__( 'Pengaturan Penerimaan', 'sakolaan-admission' ) . '</h3><div class="sakolaan-admission-form-grid"><p><label>' . esc_html__( 'Mode institusi', 'sakolaan-admission' ) . ' <select name="mode"><option value="single"' . selected( $settings['mode'] ?? '', 'single', false ) . '>Satu institusi</option><option value="multi"' . selected( $settings['mode'] ?? '', 'multi', false ) . '>Banyak institusi</option></select></label></p><p><label>' . esc_html__( 'Ukuran unggahan maksimum (byte)', 'sakolaan-admission' ) . ' <input type="number" min="1024" name="max_upload_size" value="' . esc_attr( $settings['max_upload_size'] ?? 5242880 ) . '"></label></p></div><p><label>' . esc_html__( 'Petunjuk pembayaran bawaan', 'sakolaan-admission' ) . '<textarea name="payment_instructions">' . esc_textarea( $settings['payment_instructions'] ?? '' ) . '</textarea></label></p><fieldset class="sakolaan-admission-options"><legend>' . esc_html__( 'Integrasi portal', 'sakolaan-admission' ) . '</legend><label class="sakolaan-admission-check"><input type="checkbox" name="portal_auto_render" value="1"' . checked( $settings['portal_auto_render'] ?? true, true, false ) . '> ' . esc_html__( 'Tampilkan portal otomatis pada halaman /ppdb/', 'sakolaan-admission' ) . '</label><label class="sakolaan-admission-check"><input type="checkbox" name="homepage_dynamic_section" value="1"' . checked( $settings['homepage_dynamic_section'] ?? true, true, false ) . '> ' . esc_html__( 'Tampilkan program aktif pada section PPDB beranda Pijar', 'sakolaan-admission' ) . '</label><label class="sakolaan-admission-check"><input type="checkbox" name="create_ppdb_page" value="1"> ' . esc_html__( 'Buat halaman portal /ppdb/ jika belum tersedia', 'sakolaan-admission' ) . '</label><label class="sakolaan-admission-check"><input type="checkbox" name="use_ppdb_url" value="1"> ' . esc_html__( 'Gunakan portal ini sebagai URL PPDB Sakolaan (dapat mengganti tautan khusus)', 'sakolaan-admission' ) . '</label></fieldset><div class="sakolaan-admission-danger-zone"><h4>' . esc_html__( 'Zona berbahaya', 'sakolaan-admission' ) . '</h4><p><label>' . esc_html__( 'Ketik DELETE untuk mengizinkan penghapusan data ketika plugin dicopot', 'sakolaan-admission' ) . ' <input name="uninstall_confirmation" autocomplete="off"></label></p></div>';
		submit_button( __( 'Simpan pengaturan', 'sakolaan-admission' ) );
		self::form_end();
		$storage   = Documents::storage_path();
		$mail      = Mail_Diagnostics::status();
		$last_test = $mail['test'];
		echo '<section class="sakolaan-admission-panel"><div class="sakolaan-admission-panel__heading"><div><h3>' . esc_html__( 'Diagnostik sistem', 'sakolaan-admission' ) . '</h3><p>' . esc_html__( 'Periksa kesiapan komponen penting sebelum membuka pendaftaran.', 'sakolaan-admission' ) . '</p></div></div><ul class="sakolaan-admission-diagnostics"><li><span>' . esc_html__( 'Versi skema', 'sakolaan-admission' ) . '</span><strong>' . esc_html( get_option( 'sakolaan_admission_schema_version' ) ) . '</strong></li><li><span>HTTPS</span><strong>' . esc_html( is_ssl() ? 'Aktif' : 'Perlu perhatian' ) . '</strong></li><li><span>WP-Cron</span><strong>' . esc_html( wp_next_scheduled( 'sakolaan_admission_notification_worker' ) ? 'Terjadwal' : 'Tidak tersedia' ) . '</strong></li><li><span>' . esc_html__( 'Penyimpanan privat', 'sakolaan-admission' ) . '</span><strong>' . esc_html( is_wp_error( $storage ) ? $storage->get_error_message() : 'Siap digunakan' ) . '</strong></li><li><span>' . esc_html__( 'Pengiriman email', 'sakolaan-admission' ) . '</span><strong>' . esc_html( $mail['label'] ) . '</strong></li>';
		if ( $last_test ) {
			echo '<li><span>' . esc_html__( 'Pengujian email terakhir', 'sakolaan-admission' ) . '</span><strong>' . esc_html( 'accepted' === $last_test['status'] ? __( 'Diterima konektor', 'sakolaan-admission' ) : __( 'Gagal', 'sakolaan-admission' ) ) . ' — ' . esc_html( Labels::date( $last_test['tested_at'] ) ) . '</strong></li>';
		}
		echo '</ul><p class="description"><strong>' . esc_html__( 'Keterangan:', 'sakolaan-admission' ) . '</strong> ' . esc_html( $mail['detail'] ) . ' ' . esc_html__( 'Penerimaan oleh transport bukan bukti bahwa email telah sampai ke kotak masuk penerima.', 'sakolaan-admission' ) . '</p></section>';

		$current_user = wp_get_current_user();
		echo '<section class="sakolaan-admission-panel"><div class="sakolaan-admission-panel__heading"><div><h3>' . esc_html__( 'Kirim email uji', 'sakolaan-admission' ) . '</h3><p>' . esc_html__( 'Periksa apakah wp_mail() diterima oleh konektor email yang aktif. Maksimal lima percobaan per pengguna per jam.', 'sakolaan-admission' ) . '</p></div></div>';
		if ( in_array( $mail['transport'], array( 'mailhog', 'mailpit' ), true ) ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Lingkungan ini memakai MailHog atau Mailpit. Pesan uji hanya masuk ke kotak masuk development dan tidak dikirim ke alamat eksternal.', 'sakolaan-admission' ) . '</p></div>';
		}
		echo '<form class="sakolaan-admission-form-card" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_admission_test_email">';
		wp_nonce_field( 'sakolaan_admission_test_email' );
		echo '<p><label for="sakolaan-admission-test-recipient"><strong>' . esc_html__( 'Alamat email tujuan', 'sakolaan-admission' ) . '</strong></label><input id="sakolaan-admission-test-recipient" class="regular-text" type="email" name="recipient" value="' . esc_attr( $current_user->user_email ) . '" required><small>' . esc_html__( 'Alamat ini disimpan pada hasil diagnostik terakhir, tetapi hanya hash alamat yang ditulis ke audit log.', 'sakolaan-admission' ) . '</small></p>';
		submit_button( __( 'Kirim email uji', 'sakolaan-admission' ), 'secondary' );
		echo '</form></section>';
	}

	/** Update editable configuration without trusting the submitted institution context. */
	public static function update_configuration(): void {
		$entity         = isset( $_POST['entity'] ) ? sanitize_key( wp_unslash( $_POST['entity'] ) ) : '';
		$object_id      = absint( $_POST['object_id'] ?? 0 );
		$institution_id = absint( $_POST['institution_id'] ?? 0 );
		$expected       = sanitize_text_field( wp_unslash( $_POST['expected_updated_at'] ?? '' ) );
		check_admin_referer( 'sakolaan_admission_update_' . $entity . '_' . $object_id );
		if ( ! self::can_manage_configuration( $entity ) || ( ! Access::is_super() && ! Access::can_access_institution( $institution_id ) ) ) {
			self::configuration_redirect( $entity, $institution_id, '', 'forbidden' );
		}

		$table = self::configuration_table( $entity );
		$row   = $table ? Repository::get( $table, $object_id, 'institutions' === $table ? null : $institution_id ) : null;
		if ( ! $row || ( 'institutions' === $table && $object_id !== $institution_id ) ) {
			self::configuration_redirect( $entity, $institution_id, '', 'configuration_not_found' );
		}

		$data   = array();
		$intent = isset( $_POST['save_intent'] ) ? sanitize_key( wp_unslash( $_POST['save_intent'] ) ) : '';
		if ( 'institution' === $entity ) {
			$data['name'] = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
			if ( '' === $data['name'] ) {
				self::configuration_redirect( $entity, $institution_id, '', 'invalid_configuration' );
			}
		} elseif ( 'program' === $entity ) {
			$form_id     = absint( $_POST['form_id'] ?? 0 );
			$workflow_id = absint( $_POST['workflow_id'] ?? 0 );
			$quota       = absint( $_POST['quota'] ?? 0 );
			$data        = array(
				'form_id'      => $form_id,
				'workflow_id'  => $workflow_id,
				'name'         => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'description'  => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
				'intake'       => sanitize_text_field( wp_unslash( $_POST['intake'] ?? '' ) ),
				'quota'        => $quota ? $quota : null,
				'fee_required' => empty( $_POST['fee_required'] ) ? 0 : 1,
				'fee_amount'   => max( 0, (float) sanitize_text_field( wp_unslash( $_POST['fee_amount'] ?? '0' ) ) ),
				'status'       => 'published' === $intent ? 'published' : 'draft',
			);
			$form        = Repository::get( 'forms', $form_id, $institution_id );
			$workflow    = Repository::get( 'workflows', $workflow_id, $institution_id );
			if ( '' === $data['name'] || ! $form || ! $workflow ) {
				self::configuration_redirect( $entity, $institution_id, '', 'configuration_scope' );
			}
			$candidate = array_merge( $row, $data );
			if ( 'published' === $data['status'] ) {
				$result = Configuration::validate_program_publication( $candidate, Repository::get( 'institutions', $institution_id ), $form, $workflow );
				if ( is_wp_error( $result ) ) {
					self::configuration_redirect( $entity, $institution_id, '', $result->get_error_code() );
				}
			}
		} elseif ( 'period' === $entity ) {
			$program_id     = absint( $_POST['program_id'] ?? 0 );
			$quota          = absint( $_POST['quota'] ?? 0 );
			$opens_at       = sanitize_text_field( wp_unslash( $_POST['opens_at'] ?? '' ) );
			$closes_at      = sanitize_text_field( wp_unslash( $_POST['closes_at'] ?? '' ) );
			$payment_due_at = sanitize_text_field( wp_unslash( $_POST['payment_due_at'] ?? '' ) );
			$data           = array(
				'program_id'     => $program_id,
				'name'           => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
				'opens_at'       => self::utc_datetime( $opens_at ),
				'closes_at'      => self::utc_datetime( $closes_at ),
				'payment_due_at' => self::utc_datetime( $payment_due_at, true ),
				'quota'          => $quota ? $quota : null,
				'status'         => 'published' === $intent ? 'published' : 'draft',
			);
			$program        = Repository::get( 'programs', $program_id, $institution_id );
			if ( '' === $data['name'] || ! $program ) {
				self::configuration_redirect( $entity, $institution_id, '', 'configuration_scope' );
			}
			$candidate = array_merge( $row, $data );
			if ( ! $data['opens_at'] || ! $data['closes_at'] ) {
				self::configuration_redirect( $entity, $institution_id, '', 'invalid_schedule' );
			}
			if ( 'published' === $data['status'] ) {
				$result = Configuration::validate_period_publication( $candidate, Repository::get( 'institutions', $institution_id ), $program );
				if ( is_wp_error( $result ) ) {
					self::configuration_redirect( $entity, $institution_id, '', $result->get_error_code() );
				}
			}
		} elseif ( 'requirement' === $entity ) {
			if ( ! hash_equals( self::configuration_token( $row ), $expected ) ) {
				self::configuration_redirect( $entity, $institution_id, '', 'stale_configuration' );
			}
			$mimes = self::allowed_mimes( sanitize_text_field( wp_unslash( $_POST['allowed_mimes'] ?? '' ) ) );
			$data  = array(
				'label'         => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
				'required'      => empty( $_POST['required'] ) ? 0 : 1,
				'allowed_mimes' => implode( ',', $mimes ),
				'max_size'      => min( 26214400, max( 1024, absint( $_POST['max_size'] ?? 5242880 ) ) ),
				'sort_order'    => (int) sanitize_text_field( wp_unslash( $_POST['sort_order'] ?? '0' ) ),
			);
			if ( '' === $data['label'] || ! $mimes ) {
				self::configuration_redirect( $entity, $institution_id, '', 'configuration_update_failed' );
			}
			$changed = false;
			foreach ( $data as $key => $value ) {
				$changed = $changed || (string) ( $row[ $key ] ?? '' ) !== (string) $value;
			}
			if ( $changed ) {
				global $wpdb;
				$updated = $wpdb->update(
					Installer::table( 'document_requirements' ),
					$data,
					array(
						'id'             => $object_id,
						'institution_id' => $institution_id,
						'program_id'     => $row['program_id'],
						'document_key'   => $row['document_key'],
						'label'          => $row['label'],
						'required'       => $row['required'],
						'allowed_mimes'  => $row['allowed_mimes'],
						'max_size'       => $row['max_size'],
						'sort_order'     => $row['sort_order'],
					)
				);
				if ( false === $updated ) {
					self::configuration_redirect( $entity, $institution_id, '', 'configuration_update_failed' );
				}
				if ( 0 === $updated ) {
					self::configuration_redirect( $entity, $institution_id, '', 'stale_configuration' );
				}
			}
			self::audit_configuration( $entity, $object_id, $institution_id, $row, array_merge( $row, $data ) );
			self::configuration_redirect( $entity, $institution_id, 'updated' );
		} elseif ( 'announcement' === $entity ) {
			$program_id = absint( $_POST['program_id'] ?? 0 );
			if ( $program_id && ! Repository::get( 'programs', $program_id, $institution_id ) ) {
				self::configuration_redirect( $entity, $institution_id, '', 'configuration_scope' );
			}
			$data = array(
				'program_id'   => $program_id ? $program_id : null,
				'title'        => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
				'content'      => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
				'status'       => 'published' === $intent ? 'published' : 'draft',
				'published_at' => 'published' === $intent ? current_time( 'mysql', true ) : null,
			);
			if ( 'published' === $data['status'] ) {
				$result = Configuration::validate_announcement_publication( array_merge( $row, $data ) );
				if ( is_wp_error( $result ) ) {
					self::configuration_redirect( $entity, $institution_id, '', $result->get_error_code() );
				}
			}
		} else {
			self::configuration_redirect( $entity, $institution_id, '', 'invalid_configuration' );
		}

		$result = Configuration::update( $table, $object_id, $institution_id, $data, $expected );
		if ( is_wp_error( $result ) ) {
			self::configuration_redirect( $entity, $institution_id, '', $result->get_error_code() );
		}
		self::audit_configuration( $entity, $object_id, $institution_id, $row, $result );
		$notice = isset( $data['status'] ) ? ( 'published' === $data['status'] ? 'published' : ( 'published' === ( $row['status'] ?? '' ) ? 'drafted' : 'draft_saved' ) ) : 'updated';
		self::configuration_redirect( $entity, $institution_id, $notice );
	}

	/** Change only an allowlisted publication or activation status. */
	public static function change_configuration_status(): void {
		$entity         = isset( $_POST['entity'] ) ? sanitize_key( wp_unslash( $_POST['entity'] ) ) : '';
		$object_id      = absint( $_POST['object_id'] ?? 0 );
		$institution_id = absint( $_POST['institution_id'] ?? 0 );
		$target         = sanitize_key( wp_unslash( $_POST['target_status'] ?? '' ) );
		$expected       = sanitize_text_field( wp_unslash( $_POST['expected_updated_at'] ?? '' ) );
		check_admin_referer( 'sakolaan_admission_status_' . $entity . '_' . $object_id );
		if ( ! self::can_manage_configuration( $entity ) || ( ! Access::is_super() && ! Access::can_access_institution( $institution_id ) ) ) {
			self::configuration_redirect( $entity, $institution_id, '', 'forbidden' );
		}
		$table   = self::configuration_table( $entity );
		$allowed = 'institution' === $entity ? array( 'active', 'inactive' ) : array( 'published', 'draft' );
		$row     = $table ? Repository::get( $table, $object_id, 'institutions' === $table ? null : $institution_id ) : null;
		if ( ! $row || ( 'institutions' === $table && $object_id !== $institution_id ) || ! in_array( $target, $allowed, true ) ) {
			self::configuration_redirect( $entity, $institution_id, '', 'invalid_configuration' );
		}
		if ( 'published' === $target && 'program' === $entity ) {
			$result = Configuration::validate_program_publication( $row, Repository::get( 'institutions', $institution_id ), Repository::get( 'forms', (int) $row['form_id'], $institution_id ), Repository::get( 'workflows', (int) $row['workflow_id'], $institution_id ) );
		} elseif ( 'published' === $target && 'period' === $entity ) {
			$result = Configuration::validate_period_publication( $row, Repository::get( 'institutions', $institution_id ), Repository::get( 'programs', (int) $row['program_id'], $institution_id ) );
		} elseif ( 'published' === $target && 'announcement' === $entity ) {
			$result = Configuration::validate_announcement_publication( $row );
		} else {
			$result = true;
		}
		if ( is_wp_error( $result ) ) {
			self::configuration_redirect( $entity, $institution_id, '', $result->get_error_code() );
		}
		$data = array( 'status' => $target );
		if ( 'announcement' === $entity ) {
			$data['published_at'] = 'published' === $target ? current_time( 'mysql', true ) : null;
		}
		$result = Configuration::update( $table, $object_id, $institution_id, $data, $expected );
		if ( is_wp_error( $result ) ) {
			self::configuration_redirect( $entity, $institution_id, '', $result->get_error_code() );
		}
		self::audit_configuration( $entity, $object_id, $institution_id, $row, $result );
		$notice = 'active' === $target ? 'activated' : ( 'inactive' === $target ? 'inactivated' : ( 'published' === $target ? 'published' : 'drafted' ) );
		self::configuration_redirect( $entity, $institution_id, $notice );
	}

	public static function save(): void {
		check_admin_referer( 'sakolaan_admission_save' );
		$entity         = isset( $_POST['entity'] ) ? sanitize_key( wp_unslash( $_POST['entity'] ) ) : '';
		$institution_id = isset( $_POST['institution_id'] ) ? absint( $_POST['institution_id'] ) : Access::current_institution();
		$now            = current_time( 'mysql', true );
		if ( 'institution' === $entity && current_user_can( 'sakolaan_admission_manage_institutions' ) ) {
			$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
			$code = strtoupper( sanitize_key( wp_unslash( $_POST['code'] ?? '' ) ) );
			if ( $name && $code ) {
				$new_id = Repository::insert(
					'institutions',
					array(
						'code'       => $code,
						'name'       => $name,
						'slug'       => sanitize_title( $name ) . '-' . strtolower( $code ),
						'status'     => 'active',
						'settings'   => '{}',
						'created_at' => $now,
						'updated_at' => $now,
					)
				);
				if ( $new_id ) {
					self::seed_institution( $new_id ); }
			}
		} elseif ( 'assignment' === $entity && current_user_can( 'sakolaan_admission_manage_institutions' ) && Access::can_access_institution( $institution_id ) ) {
			$user_id = absint( $_POST['user_id'] ?? 0 );
			$role    = sanitize_key( wp_unslash( $_POST['assignment_role'] ?? '' ) );
			if ( get_userdata( $user_id ) && in_array( $role, array( 'administrator', 'officer', 'document_verifier', 'payment_verifier', 'assessment_reviewer' ), true ) ) {
				Repository::insert(
					'institution_users',
					array(
						'institution_id'  => $institution_id,
						'user_id'         => $user_id,
						'assignment_role' => $role,
						'created_at'      => $now,
					)
				); }
		} elseif ( ! Access::can_access_institution( $institution_id ) && ! Access::is_super() ) {
			self::forbidden();
		} elseif ( 'institution_settings' === $entity && current_user_can( 'sakolaan_admission_manage_programs' ) ) {
			$institution = Repository::get( 'institutions', $institution_id );
			if ( $institution ) {
				$institution_settings                         = (array) json_decode( (string) $institution['settings'], true );
				$institution_settings['bank_name']            = sanitize_text_field( wp_unslash( $_POST['bank_name'] ?? '' ) );
				$institution_settings['account_number']       = sanitize_text_field( wp_unslash( $_POST['account_number'] ?? '' ) );
				$institution_settings['account_holder']       = sanitize_text_field( wp_unslash( $_POST['account_holder'] ?? '' ) );
				$institution_settings['payment_instructions'] = sanitize_textarea_field( wp_unslash( $_POST['payment_instructions'] ?? '' ) );
				Repository::update( 'institutions', $institution_id, array( 'settings' => wp_json_encode( $institution_settings ) ), $institution_id );
				Repository::audit( 'institution_payment_settings_updated', 'institution', $institution_id, $institution_id );
			}
		} elseif ( 'program' === $entity && current_user_can( 'sakolaan_admission_manage_programs' ) ) {
			$name           = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
			$quota          = absint( $_POST['quota'] ?? 0 );
			$program_status = sanitize_key( wp_unslash( $_POST['save_intent'] ?? ( $_POST['status'] ?? '' ) ) );
			$fee_amount     = (float) sanitize_text_field( wp_unslash( $_POST['fee_amount'] ?? '0' ) );
			$form_id        = absint( $_POST['form_id'] ?? 0 );
			$workflow_id    = absint( $_POST['workflow_id'] ?? 0 );
			$program_data   = array(
				'institution_id' => $institution_id,
				'form_id'        => $form_id,
				'workflow_id'    => $workflow_id,
				'name'           => $name,
				'slug'           => sanitize_title( $name ),
				'description'    => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
				'intake'         => sanitize_text_field( wp_unslash( $_POST['intake'] ?? '' ) ),
				'quota'          => $quota ? $quota : null,
				'fee_required'   => empty( $_POST['fee_required'] ) ? 0 : 1,
				'fee_amount'     => max( 0, $fee_amount ),
				'currency'       => 'IDR',
				'status'         => 'published' === $program_status ? 'published' : 'draft',
				'created_at'     => $now,
				'updated_at'     => $now,
			);
			if ( ! $name || ! Repository::get( 'forms', $form_id, $institution_id ) || ! Repository::get( 'workflows', $workflow_id, $institution_id ) ) {
				self::configuration_redirect( 'program', $institution_id, '', 'configuration_scope' );
			}
			if ( 'published' === $program_data['status'] ) {
				$result = Configuration::validate_program_publication( $program_data, Repository::get( 'institutions', $institution_id ), Repository::get( 'forms', $form_id, $institution_id ), Repository::get( 'workflows', $workflow_id, $institution_id ) );
				if ( is_wp_error( $result ) ) {
					self::configuration_redirect( 'program', $institution_id, '', $result->get_error_code() );
				}
			}
			Repository::insert(
				'programs',
				$program_data
			);
		} elseif ( 'period' === $entity && current_user_can( 'sakolaan_admission_manage_programs' ) ) {
			$open_value        = sanitize_text_field( wp_unslash( $_POST['opens_at'] ?? '' ) );
			$close_value       = sanitize_text_field( wp_unslash( $_POST['closes_at'] ?? '' ) );
			$payment_due_value = sanitize_text_field( wp_unslash( $_POST['payment_due_at'] ?? '' ) );
			$open              = self::utc_datetime( $open_value );
			$close             = self::utc_datetime( $close_value );
			if ( $open && $close ) {
				$quota         = absint( $_POST['quota'] ?? 0 );
				$period_status = sanitize_key( wp_unslash( $_POST['save_intent'] ?? ( $_POST['status'] ?? '' ) ) );
				$program_id    = absint( $_POST['program_id'] ?? 0 );
				$period_data   = array(
					'institution_id' => $institution_id,
					'program_id'     => $program_id,
					'name'           => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
					'opens_at'       => $open,
					'closes_at'      => $close,
					'payment_due_at' => self::utc_datetime( $payment_due_value, true ),
					'quota'          => $quota ? $quota : null,
					'status'         => 'published' === $period_status ? 'published' : 'draft',
					'created_at'     => $now,
					'updated_at'     => $now,
				);
				$program       = Repository::get( 'programs', $program_id, $institution_id );
				if ( ! $program ) {
					self::configuration_redirect( 'period', $institution_id, '', 'configuration_scope' );
				}
				if ( 'published' === $period_data['status'] ) {
					$result = Configuration::validate_period_publication( $period_data, Repository::get( 'institutions', $institution_id ), $program );
					if ( is_wp_error( $result ) ) {
						self::configuration_redirect( 'period', $institution_id, '', $result->get_error_code() );
					}
				}
				Repository::insert(
					'periods',
					$period_data
				); } else {
				self::configuration_redirect( 'period', $institution_id, '', 'invalid_schedule' );
				}
		} elseif ( 'document_requirement' === $entity && current_user_can( 'sakolaan_admission_manage_programs' ) ) {
			$program = Repository::get( 'programs', absint( $_POST['program_id'] ?? 0 ), $institution_id );
			if ( $program ) {
				Repository::insert(
					'document_requirements',
					array(
						'institution_id' => $institution_id,
						'program_id'     => $program['id'],
						'document_key'   => sanitize_key( wp_unslash( $_POST['document_key'] ?? '' ) ),
						'label'          => sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) ),
						'required'       => empty( $_POST['required'] ) ? 0 : 1,
						'allowed_mimes'  => 'application/pdf,image/jpeg,image/png,image/webp',
						'max_size'       => 5242880,
						'sort_order'     => 0,
					)
				); }
		} elseif ( 'form' === $entity && current_user_can( 'sakolaan_admission_manage_forms' ) ) {
			$raw    = trim( wp_unslash( $_POST['schema_json'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON is decoded and normalized by the controlled form schema below.
			$schema = $raw ? json_decode( $raw, true ) : ( Forms::templates()[ sanitize_key( $_POST['template'] ?? 'minimal' ) ] ?? Forms::templates()['minimal'] );
			$schema = is_array( $schema ) ? Forms::normalize_schema( $schema ) : new \WP_Error( 'json', __( 'JSON tidak valid.', 'sakolaan-admission' ) );
			if ( is_wp_error( $schema ) ) {
				self::configuration_redirect( 'form', $institution_id, '', 'invalid_configuration' );
			} else {
				$form_id  = absint( $_POST['form_id'] ?? 0 );
				$existing = $form_id ? Repository::get( 'forms', $form_id, $institution_id ) : null;
				$expected = sanitize_text_field( wp_unslash( $_POST['expected_updated_at'] ?? '' ) );
				$name     = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
				if ( '' === $name ) {
					self::configuration_redirect( 'form', $institution_id, '', 'invalid_configuration' );
				}
				if ( $form_id && ( ! $existing || ! $expected || ! hash_equals( (string) $existing['updated_at'], $expected ) ) ) {
					self::configuration_redirect( 'form', $institution_id, '', $existing ? 'stale_configuration' : 'configuration_not_found' );
				}
				$parent_updated_at = $now;
				if ( $existing ) {
					$claim = self::claim_version_parent( 'forms', $form_id, $institution_id, $expected );
					if ( is_wp_error( $claim ) ) {
						self::configuration_redirect( 'form', $institution_id, '', $claim->get_error_code() );
					}
					$parent_updated_at = $claim;
				}
				if ( ! $existing ) {
					$form_id = Repository::insert(
						'forms',
						array(
							'institution_id' => $institution_id,
							'name'           => $name,
							'status'         => 'published',
							'created_by'     => get_current_user_id(),
							'created_at'     => $now,
							'updated_at'     => $now,
						)
					);
				} global $wpdb;
				$version_no = 1 + (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(version_no) FROM ' . Installer::table( 'form_versions' ) . ' WHERE form_id=%d', $form_id ) );
				$version_id = Repository::insert(
					'form_versions',
					array(
						'institution_id' => $institution_id,
						'form_id'        => $form_id,
						'version_no'     => $version_no,
						'schema_json'    => wp_json_encode( $schema ),
						'published_at'   => $now,
						'created_by'     => get_current_user_id(),
						'created_at'     => $now,
					)
				);
				if ( ! $version_id ) {
					if ( ! $existing ) {
						Repository::update( 'forms', $form_id, array( 'status' => 'draft' ), $institution_id );
					}
					self::configuration_redirect( 'form', $institution_id, '', 'configuration_update_failed' );
				}
				Repository::update(
					'forms',
					$form_id,
					array(
						'name'               => $name,
						'current_version_id' => $version_id,
						'updated_at'         => $parent_updated_at,
					),
					$institution_id
				); }
		} elseif ( 'workflow' === $entity && current_user_can( 'sakolaan_admission_manage_forms' ) ) {
			$workflow_id = absint( $_POST['workflow_id'] ?? 0 );
			$existing    = $workflow_id ? Repository::get( 'workflows', $workflow_id, $institution_id ) : null;
			$expected    = sanitize_text_field( wp_unslash( $_POST['expected_updated_at'] ?? '' ) );
			$name        = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
			if ( '' === $name ) {
				self::configuration_redirect( 'workflow', $institution_id, '', 'invalid_configuration' );
			}
			if ( $workflow_id && ( ! $existing || ! $expected || ! hash_equals( (string) $existing['updated_at'], $expected ) ) ) {
				self::configuration_redirect( 'workflow', $institution_id, '', $existing ? 'stale_configuration' : 'configuration_not_found' );
			}
			$parent_updated_at = $now;
			if ( $existing ) {
				$claim = self::claim_version_parent( 'workflows', $workflow_id, $institution_id, $expected );
				if ( is_wp_error( $claim ) ) {
					self::configuration_redirect( 'workflow', $institution_id, '', $claim->get_error_code() );
				}
				$parent_updated_at = $claim;
			}
			if ( ! $existing ) {
				$workflow_id = Repository::insert(
					'workflows',
					array(
						'institution_id' => $institution_id,
						'name'           => $name,
						'status'         => 'published',
						'created_at'     => $now,
						'updated_at'     => $now,
					)
				);
			}
			global $wpdb;
			$version_no = 1 + (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(version_no) FROM ' . Installer::table( 'workflow_versions' ) . ' WHERE workflow_id=%d', $workflow_id ) );
			$version_id = Repository::insert(
				'workflow_versions',
				array(
					'institution_id'  => $institution_id,
					'workflow_id'     => $workflow_id,
					'version_no'      => $version_no,
					'definition_json' => wp_json_encode( Workflow::default_definition( 'simple' === sanitize_key( wp_unslash( $_POST['workflow_type'] ?? '' ) ) ) ),
					'published_at'    => $now,
					'created_by'      => get_current_user_id(),
					'created_at'      => $now,
				)
			);
			if ( ! $version_id ) {
				if ( ! $existing ) {
					Repository::update( 'workflows', $workflow_id, array( 'status' => 'draft' ), $institution_id );
				}
				self::configuration_redirect( 'workflow', $institution_id, '', 'configuration_update_failed' );
			}
			Repository::update(
				'workflows',
				$workflow_id,
				array(
					'name'               => $name,
					'current_version_id' => $version_id,
					'updated_at'         => $parent_updated_at,
				),
				$institution_id
			);
		} elseif ( 'rubric' === $entity && current_user_can( 'sakolaan_admission_manage_forms' ) ) {
			$decoded  = json_decode( wp_unslash( $_POST['criteria_json'] ?? '' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Every decoded rubric field is normalized below.
			$criteria = array();
			$weight   = 0.0;
			foreach ( is_array( $decoded ) ? $decoded : array() as $item ) {
				$key         = sanitize_key( $item['key'] ?? '' );
				$item_weight = max( 0, (float) ( $item['weight'] ?? 0 ) );
				if ( $key && $item_weight ) {
					$criteria[] = array(
						'key'       => $key,
						'label'     => sanitize_text_field( $item['label'] ?? $key ),
						'weight'    => $item_weight,
						'max_score' => max( 1, (float) ( $item['max_score'] ?? 100 ) ),
					);
					$weight    += $item_weight;
				}
			} if ( $criteria && $weight > 0 ) {
				$rubric_id  = Repository::insert(
					'rubrics',
					array(
						'institution_id' => $institution_id,
						'program_id'     => absint( $_POST['program_id'] ?? 0 ),
						'name'           => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
						'created_at'     => $now,
						'updated_at'     => $now,
					)
				);
				$version_id = Repository::insert(
					'rubric_versions',
					array(
						'institution_id' => $institution_id,
						'rubric_id'      => $rubric_id,
						'version_no'     => 1,
						'criteria_json'  => wp_json_encode( array( 'criteria' => $criteria ) ),
						'created_by'     => get_current_user_id(),
						'created_at'     => $now,
					)
				);
				Repository::update( 'rubrics', $rubric_id, array( 'current_version_id' => $version_id ), $institution_id ); }
		} elseif ( 'reviewer_assignment' === $entity && current_user_can( 'sakolaan_admission_manage_applications' ) ) {
			$app         = Repository::get( 'applications', absint( $_POST['application_id'] ?? 0 ), $institution_id );
			$version     = Repository::get( 'rubric_versions', absint( $_POST['rubric_version_id'] ?? 0 ), $institution_id );
			$reviewer_id = absint( $_POST['reviewer_id'] ?? 0 );
			if ( $app && $version && user_can( $reviewer_id, 'sakolaan_admission_review_assessments' ) ) {
				Repository::insert(
					'reviewer_assignments',
					array(
						'institution_id'    => $institution_id,
						'application_id'    => $app['id'],
						'reviewer_id'       => $reviewer_id,
						'rubric_version_id' => $version['id'],
						'status'            => 'assigned',
						'assigned_by'       => get_current_user_id(),
						'created_at'        => $now,
					)
				); }
		} elseif ( 'verify_applicant' === $entity && current_user_can( 'sakolaan_admission_manage_applications' ) ) {
			$applicant = Repository::applicant_for_user( absint( $_POST['user_id'] ?? 0 ) );
			if ( $applicant ) {
				global $wpdb;
				$wpdb->update(
					Installer::table( 'applicants' ),
					array(
						'email_verified_at'       => $now,
						'verification_hash'       => null,
						'verification_expires_at' => null,
						'updated_at'              => $now,
					),
					array( 'id' => $applicant['id'] )
				); }
		} elseif ( 'document_review' === $entity && current_user_can( 'sakolaan_admission_verify_documents' ) ) {
			$result = Documents::review( absint( $_POST['document_id'] ?? 0 ), sanitize_key( wp_unslash( $_POST['status'] ?? '' ) ), sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ) );
			if ( is_wp_error( $result ) ) {
				self::operation_error( $result ); }
		} elseif ( 'payment_review' === $entity && current_user_can( 'sakolaan_admission_verify_payments' ) ) {
			$result = Workflow::payment_transition( absint( $_POST['payment_id'] ?? 0 ), sanitize_key( wp_unslash( $_POST['status'] ?? '' ) ), sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) ) );
			if ( is_wp_error( $result ) ) {
				self::operation_error( $result ); }
		} elseif ( 'application_transition' === $entity ) {
			$result = Workflow::transition( absint( $_POST['application_id'] ?? 0 ), sanitize_key( wp_unslash( $_POST['to'] ?? '' ) ), sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) ) );
			if ( is_wp_error( $result ) ) {
				self::operation_error( $result ); }
		} elseif ( 'announcement' === $entity && current_user_can( 'sakolaan_admission_manage_announcements' ) ) {
			$publish    = 'published' === sanitize_key( wp_unslash( $_POST['save_intent'] ?? '' ) );
			$program_id = absint( $_POST['program_id'] ?? 0 );
			$data       = array(
				'institution_id' => $institution_id,
				'program_id'     => $program_id ? $program_id : null,
				'title'          => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
				'content'        => wp_kses_post( wp_unslash( $_POST['content'] ?? '' ) ),
				'status'         => $publish ? 'published' : 'draft',
				'published_at'   => $publish ? $now : null,
				'created_by'     => get_current_user_id(),
				'created_at'     => $now,
				'updated_at'     => $now,
			);
			if ( $program_id && ! Repository::get( 'programs', $program_id, $institution_id ) ) {
				self::configuration_redirect( 'announcement', $institution_id, '', 'configuration_scope' );
			}
			if ( $publish ) {
				$result = Configuration::validate_announcement_publication( $data );
				if ( is_wp_error( $result ) ) {
					self::configuration_redirect( 'announcement', $institution_id, '', $result->get_error_code() );
				}
			}
			Repository::insert(
				'announcements',
				$data
			);
		} elseif ( 'settings' === $entity && current_user_can( 'sakolaan_admission_manage_settings' ) ) {
			$old                             = (array) get_option( 'sakolaan_admission_settings', array() );
			$old['mode']                     = 'multi' === sanitize_key( wp_unslash( $_POST['mode'] ?? '' ) ) ? 'multi' : 'single';
			$old['max_upload_size']          = max( 1024, absint( $_POST['max_upload_size'] ?? 5242880 ) );
			$old['payment_instructions']     = sanitize_textarea_field( wp_unslash( $_POST['payment_instructions'] ?? '' ) );
			$old['portal_auto_render']       = ! empty( $_POST['portal_auto_render'] );
			$old['homepage_dynamic_section'] = ! empty( $_POST['homepage_dynamic_section'] );
			$old['delete_data_on_uninstall'] = hash_equals( 'DELETE', sanitize_text_field( wp_unslash( $_POST['uninstall_confirmation'] ?? '' ) ) );
			update_option( 'sakolaan_admission_settings', $old, false );
			$page = get_page_by_path( 'ppdb' );
			if ( ! $page && ! empty( $_POST['create_ppdb_page'] ) ) {
				$page_id = wp_insert_post(
					array(
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_title'   => __( 'Penerimaan', 'sakolaan-admission' ),
						'post_name'    => 'ppdb',
						'post_content' => '[sakolaan_admission_portal]',
					),
					true
				);
				$page    = is_wp_error( $page_id ) ? null : get_post( $page_id );
			} if ( $page && ! empty( $_POST['use_ppdb_url'] ) ) {
				$core = (array) get_option( 'sakolaan_settings', array() );
				if ( empty( $core['ppdb'] ) || ! is_array( $core['ppdb'] ) ) {
					$core['ppdb'] = array();
				} $core['ppdb']['url'] = get_permalink( $page );
				update_option( 'sakolaan_settings', $core ); }
		} else {
			self::forbidden(); }
		Repository::audit( 'admin_saved_' . $entity, $entity, null, $institution_id ? $institution_id : null );
		$section_map = array(
			'institution'            => 'institutions',
			'assignment'             => 'institutions',
			'institution_settings'   => 'institutions',
			'program'                => 'programs',
			'document_requirement'   => 'programs',
			'period'                 => 'periods',
			'form'                   => 'forms',
			'workflow'               => 'forms',
			'rubric'                 => 'assessment',
			'reviewer_assignment'    => 'assessment',
			'verify_applicant'       => 'applicants',
			'document_review'        => 'documents',
			'payment_review'         => 'payments',
			'application_transition' => 'selection',
			'announcement'           => 'announcements',
			'settings'               => 'settings',
		);
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => 'sakolaan',
					'view'           => 'admission',
					'section'        => $section_map[ $entity ] ?? 'overview',
					'institution_id' => $institution_id,
					'notice'         => in_array( $entity, array( 'form', 'workflow' ), true ) ? 'versioned' : ( in_array( $entity, array( 'program', 'period', 'announcement' ), true ) ? ( 'published' === sanitize_key( wp_unslash( $_POST['save_intent'] ?? '' ) ) ? 'published' : 'draft_saved' ) : 'saved' ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/** Send a rate-limited diagnostic message through the configured WordPress transport. */
	public static function test_email(): void {
		check_admin_referer( 'sakolaan_admission_test_email' );
		if ( ! current_user_can( 'sakolaan_admission_manage_settings' ) ) {
			self::forbidden();
		}

		$user_id   = get_current_user_id();
		$limit_key = 'sak_adm_mail_test_' . $user_id;
		$limit     = get_transient( $limit_key );
		$limit     = is_array( $limit ) ? $limit : array(
			'count'   => 0,
			'started' => time(),
		);
		$elapsed   = max( 0, time() - absint( $limit['started'] ?? time() ) );
		if ( $elapsed >= HOUR_IN_SECONDS ) {
			$limit = array(
				'count'   => 0,
				'started' => time(),
			);
		}
		if ( absint( $limit['count'] ?? 0 ) >= 5 ) {
			self::mail_test_redirect( '', 'mail_test_rate_limited' );
		}

		$recipient = sanitize_email( wp_unslash( $_POST['recipient'] ?? '' ) );
		if ( ! is_email( $recipient ) ) {
			self::mail_test_redirect( '', 'invalid_email' );
		}
		++$limit['count'];
		set_transient( $limit_key, $limit, max( 1, HOUR_IN_SECONDS - $elapsed ) );

		$transport = Mail_Diagnostics::detect();
		$failure   = '';
		$listener  = static function ( $error ) use ( &$failure ): void {
			if ( is_wp_error( $error ) ) {
				$failure = $error->get_error_message();
			}
		};
		add_action( 'wp_mail_failed', $listener );
		try {
			$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
			$subject   = sprintf(
				/* translators: %s: site name. */
				__( 'Pengujian email Penerimaan Sakolaan — %s', 'sakolaan-admission' ),
				$site_name
			);
			$message = sprintf(
				/* translators: %s: site name. */
				__( 'Ini adalah email uji dari Penerimaan Sakolaan pada %s.', 'sakolaan-admission' ),
				$site_name
			);
			$message .= "\n\n" . sprintf(
				/* translators: %s: UTC timestamp. */
				__( 'Waktu pengujian (UTC): %s', 'sakolaan-admission' ),
				current_time( 'mysql', true )
			);
			$message .= "\n\n" . __( 'Penerimaan pesan oleh konektor tidak menjamin bahwa pesan telah sampai ke kotak masuk.', 'sakolaan-admission' );
			$accepted = wp_mail( $recipient, $subject, $message );
		} catch ( \Throwable $exception ) {
			$accepted = false;
			$failure  = $exception->getMessage();
		} finally {
			remove_action( 'wp_mail_failed', $listener );
		}

		if ( ! $accepted && '' === $failure ) {
			$failure = __( 'wp_mail() mengembalikan nilai gagal tanpa rincian tambahan.', 'sakolaan-admission' );
		}
		$record = Mail_Diagnostics::record_test( (bool) $accepted, $transport['transport'], $recipient, $failure );
		Repository::audit(
			'mail_test_attempted',
			'mail_test',
			null,
			null,
			array(
				'recipient_hash' => hash_hmac( 'sha256', strtolower( $recipient ), wp_salt( 'auth' ) ),
				'result'         => $record['status'],
				'tested_at'      => $record['tested_at'],
				'transport'      => $record['transport'],
			)
		);

		self::mail_test_redirect( $accepted ? 'mail_test_accepted' : '', $accepted ? '' : 'mail_test_failed' );
	}

	private static function mail_test_redirect( string $notice = '', string $error = '' ): void {
		$args = array(
			'page'    => 'sakolaan',
			'view'    => 'admission',
			'section' => 'settings',
		);
		if ( $notice ) {
			$args['notice'] = $notice;
		}
		if ( $error ) {
			$args['config_error'] = $error;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function export_csv(): void {
		$institution_id = absint( $_GET['institution_id'] ?? 0 );
		check_admin_referer( 'sakolaan_admission_export_' . $institution_id );
		if ( ! current_user_can( 'sakolaan_admission_view_reports' ) || ! Access::can_access_institution( $institution_id ) ) {
			self::forbidden(); }
		$rows = Repository::list( 'applications', $institution_id, array(), 500 );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="sakolaan-admission-applications.csv"' );
		$out = fopen( 'php://output', 'wb' );
		fwrite( $out, "\xEF\xBB\xBF" );
		fputcsv( $out, array( 'application_number', 'program_id', 'period_id', 'status', 'submitted_at', 'updated_at' ) );
		foreach ( $rows as $row ) {
			$line = array();
			foreach ( array( 'application_number', 'program_id', 'period_id', 'status', 'submitted_at', 'updated_at' ) as $key ) {
				$value  = (string) $row[ $key ];
				$line[] = preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value;
			} fputcsv( $out, $line ); }
		fclose( $out );
		Repository::audit( 'applications_exported', 'application', null, $institution_id, array( 'count' => count( $rows ) ) );
		exit;
	}

	public static function queue_submission_notice( int $application_id ): void {
		self::queue_for_application( $application_id, 'application_submitted', __( 'Pendaftaran berhasil dikirim', 'sakolaan-admission' ), __( 'Pendaftaran {application_number} telah berhasil dikirim.', 'sakolaan-admission' ) ); }
	public static function queue_payment_notice( int $payment_id, string $from, string $to ): void {
		$payment = Repository::get( 'payments', $payment_id );
		if ( $payment ) {
			self::queue_for_application( (int) $payment['application_id'], 'payment_' . $to, __( 'Status pembayaran diperbarui', 'sakolaan-admission' ), __( 'Status pembayaran Anda sekarang: {status}.', 'sakolaan-admission' ), array( 'status' => Labels::status( $to ) ) ); } }
	public static function queue_decision_notice( int $application_id, string $status ): void {
		self::queue_for_application( $application_id, 'decision_published', __( 'Keputusan penerimaan telah diterbitkan', 'sakolaan-admission' ), __( 'Keputusan untuk pendaftaran {application_number} tersedia di portal pendaftar.', 'sakolaan-admission' ), array( 'status' => Labels::status( $status ) ) ); }

	private static function queue_for_application( int $application_id, string $event, string $subject, string $body, array $extra = array() ): void {
		$app = Repository::get( 'applications', $application_id );
		if ( ! $app ) {
			return;
		} $applicant = Repository::get( 'applicants', (int) $app['applicant_id'] );
		$user        = $applicant ? get_userdata( (int) $applicant['user_id'] ) : null;
		if ( ! $user ) {
			return; }
		$placeholders = apply_filters(
			'sakolaan_admission_notification_placeholders',
			array_merge(
				array(
					'application_number' => $app['application_number'],
					'status'             => $app['status'],
				),
				$extra
			),
			$app,
			$event
		);
		foreach ( $placeholders as $key => $value ) {
			$subject = str_replace( '{' . $key . '}', sanitize_text_field( (string) $value ), $subject );
			$body    = str_replace( '{' . $key . '}', sanitize_text_field( (string) $value ), $body ); }
		Repository::insert(
			'notifications',
			array(
				'institution_id'  => $app['institution_id'],
				'application_id'  => $application_id,
				'event'           => $event,
				'recipient'       => $user->user_email,
				'subject'         => $subject,
				'body'            => $body,
				'status'          => 'queued',
				'attempts'        => 0,
				'next_attempt_at' => current_time( 'mysql', true ),
				'created_at'      => current_time( 'mysql', true ),
			)
		);
	}

	public static function send_notifications(): void {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . Installer::table( 'notifications' ) . " WHERE status IN ('queued','failed') AND attempts < 5 AND (next_attempt_at IS NULL OR next_attempt_at <= %s) ORDER BY id LIMIT 20", current_time( 'mysql', true ) ), ARRAY_A );
		foreach ( $rows as $row ) {
			$sent = wp_mail( $row['recipient'], $row['subject'], $row['body'] );
			$wpdb->update(
				Installer::table( 'notifications' ),
				array(
					'status'          => $sent ? 'accepted' : 'failed',
					'attempts'        => (int) $row['attempts'] + 1,
					'last_error'      => $sent ? null : 'wp_mail returned false',
					'next_attempt_at' => $sent ? null : gmdate( 'Y-m-d H:i:s', time() + 3600 ),
					'sent_at'         => $sent ? current_time( 'mysql', true ) : null,
				),
				array( 'id' => $row['id'] )
			); }
	}

	private static function form_start( string $entity, int $institution_id = 0 ): void {
		echo '<form class="sakolaan-admission-form-card" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_admission_save"><input type="hidden" name="entity" value="' . esc_attr( $entity ) . '"><input type="hidden" name="institution_id" value="' . absint( $institution_id ) . '">';
		wp_nonce_field( 'sakolaan_admission_save' ); }
	private static function configuration_form_start( string $entity, array $row, int $institution_id ): void {
		$token = ! empty( $row['updated_at'] ) ? (string) $row['updated_at'] : self::configuration_token( $row );
		echo '<form class="sakolaan-admission-form-card" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_admission_config_update"><input type="hidden" name="entity" value="' . esc_attr( $entity ) . '"><input type="hidden" name="object_id" value="' . absint( $row['id'] ) . '"><input type="hidden" name="institution_id" value="' . absint( $institution_id ) . '"><input type="hidden" name="expected_updated_at" value="' . esc_attr( $token ) . '">';
		wp_nonce_field( 'sakolaan_admission_update_' . $entity . '_' . absint( $row['id'] ) );
	}
	private static function configuration_token( array $row ): string {
		ksort( $row );
		return hash( 'sha256', wp_json_encode( $row ) );
	}
	private static function publication_buttons( string $status ): void {
		echo '<div class="sakolaan-admission-form-actions"><button type="submit" class="button button-secondary" name="save_intent" value="draft">' . esc_html( 'published' === $status ? __( 'Kembalikan ke Draf', 'sakolaan-admission' ) : __( 'Simpan sebagai Draf', 'sakolaan-admission' ) ) . '</button><button type="submit" class="button button-primary" name="save_intent" value="published">' . esc_html__( 'Terbitkan', 'sakolaan-admission' ) . '</button></div>';
	}
	private static function form_end(): void {
		echo '</form>'; }
	private static function select( string $name, string $label, array $rows, int $selected_id = 0 ): void {
		echo '<p><label>' . esc_html( $label ) . ' <select name="' . esc_attr( $name ) . '" required>';
		if ( ! $rows ) {
			echo '<option value="">' . esc_html__( 'Belum ada pilihan yang tersedia', 'sakolaan-admission' ) . '</option>'; }
		foreach ( $rows as $row ) {
			printf( '<option value="%1$d"%2$s>%3$s</option>', (int) $row['id'], selected( $selected_id, (int) $row['id'], false ), esc_html( $row['name'] ) );
		} echo '</select></label></p>'; }
	private static function table( array $rows, array $columns, array $options = array() ): void {
		if ( ! $rows ) {
			$empty_message = isset( $options['empty_message'] ) ? (string) $options['empty_message'] : __( 'Data akan tampil di sini setelah ditambahkan atau diterima oleh sistem.', 'sakolaan-admission' );
			echo '<div class="sakolaan-admission-empty"><span class="dashicons dashicons-database" aria-hidden="true"></span><strong>' . esc_html__( 'Belum ada data', 'sakolaan-admission' ) . '</strong><p>' . esc_html( $empty_message ) . '</p></div>';
			return;
		} echo '<div class="sakolaan-table-wrap"><table class="widefat striped sakolaan-admission-table"><thead><tr>';
		foreach ( $columns as $column ) {
			echo '<th scope="col">' . esc_html( Labels::column( $column ) ) . '</th>';
		} echo '</tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr>';
			foreach ( $columns as $column ) {
				$value = (string) ( $row[ $column ] ?? '' );
				if ( 'actions' === $column ) {
					echo '<td>' . self::row_actions( $row, $options ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from escaped URLs, fixed labels, and nonce fields.
				} elseif ( in_array( $column, array( 'status', 'effective_status' ), true ) ) {
					echo '<td><span class="sakolaan-admission-badge ' . esc_attr( Labels::badge_class( $value ) ) . '">' . esc_html( Labels::status( $value ) ) . '</span></td>';
				} elseif ( 'assignment_role' === $column ) {
					echo '<td>' . esc_html( Labels::role( $value ) ) . '</td>';
				} elseif ( in_array( $column, array( 'created_at', 'updated_at', 'opens_at', 'closes_at', 'published_at', 'submitted_at', 'due_at', 'payment_due_at', 'decision_published_at' ), true ) ) {
					echo '<td>' . esc_html( Labels::date( $value ) ) . '</td>';
				} elseif ( in_array( $column, array( 'amount', 'fee_amount' ), true ) ) {
					echo '<td>' . esc_html( Labels::number( (float) $value, 2 ) ) . '</td>';
				} elseif ( 'required' === $column ) {
					echo '<td>' . esc_html( $value ? __( 'Ya', 'sakolaan-admission' ) : __( 'Tidak', 'sakolaan-admission' ) ) . '</td>';
				} else {
					echo '<td>' . esc_html( '' === $value ? '—' : $value ) . '</td>';
				}
			} echo '</tr>';
		} echo '</tbody></table></div>'; }

	private static function row_actions( array $row, array $options ): string {
		$entity         = sanitize_key( $options['entity'] ?? '' );
		$section        = sanitize_key( $options['section'] ?? '' );
		$institution_id = absint( $options['institution_id'] ?? 0 );
		$id             = absint( $row['id'] ?? 0 );
		if ( 'institution' === $entity ) {
			$institution_id = $id;
		}
		if ( ! $entity || ! $section || ! $id ) {
			return '—';
		}
		$edit_url = add_query_arg(
			array(
				'page'            => 'sakolaan',
				'view'            => 'admission',
				'section'         => $section,
				'institution_id'  => $institution_id,
				'edit_' . $entity => $id,
			),
			admin_url( 'admin.php' )
		);
		$out      = '<div class="sakolaan-admission-row-actions"><a class="button button-small" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'sakolaan-admission' ) . '</a>';
		if ( in_array( $entity, array( 'program', 'period', 'announcement', 'institution' ), true ) ) {
			$current = (string) ( $row['status'] ?? '' );
			if ( 'institution' === $entity ) {
				$target = 'active' === $current ? 'inactive' : 'active';
				$label  = 'active' === $target ? __( 'Aktifkan', 'sakolaan-admission' ) : __( 'Nonaktifkan', 'sakolaan-admission' );
			} else {
				$target = 'published' === $current ? 'draft' : 'published';
				$label  = 'published' === $target ? __( 'Terbitkan', 'sakolaan-admission' ) : __( 'Kembalikan ke Draf', 'sakolaan-admission' );
			}
			$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_admission_config_status"><input type="hidden" name="entity" value="' . esc_attr( $entity ) . '"><input type="hidden" name="object_id" value="' . $id . '"><input type="hidden" name="institution_id" value="' . $institution_id . '"><input type="hidden" name="target_status" value="' . esc_attr( $target ) . '"><input type="hidden" name="expected_updated_at" value="' . esc_attr( (string) ( $row['updated_at'] ?? '' ) ) . '">';
			$out .= wp_nonce_field( 'sakolaan_admission_status_' . $entity . '_' . $id, '_wpnonce', true, false );
			$out .= '<button class="button button-small ' . ( in_array( $target, array( 'published', 'active' ), true ) ? 'button-primary' : '' ) . '" data-confirm="' . esc_attr__( 'Yakin ingin mengubah status konfigurasi ini?', 'sakolaan-admission' ) . '">' . esc_html( $label ) . '</button></form>';
		}
		return $out . '</div>';
	}

	private static function guidance( string $title, string $description, array $terms = array() ): void {
		echo '<section class="sakolaan-admission-guidance"><div><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><div><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( $description ) . '</p></div></div>';
		if ( $terms ) {
			echo '<details><summary>' . esc_html__( 'Glosarium data teknis', 'sakolaan-admission' ) . '</summary><dl>';
			foreach ( $terms as $term => $meaning ) {
				echo '<div><dt><code>' . esc_html( $term ) . '</code></dt><dd>' . esc_html( $meaning ) . '</dd></div>';
			}
			echo '</dl></details>';
		}
		echo '</section>';
	}

	private static function datetime_input( ?string $value ): string {
		$timestamp = $value ? strtotime( $value . ' UTC' ) : false;
		return $timestamp ? wp_date( 'Y-m-d\TH:i', $timestamp, wp_timezone() ) : '';
	}
	private static function utc_datetime( string $value, bool $nullable = false ): ?string {
		$value = sanitize_text_field( $value );
		if ( '' === $value ) {
			return $nullable ? null : '';
		}
		$date = \DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i', $value, wp_timezone() );
		return $date ? $date->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) : ( $nullable ? null : '' );
	}
	private static function allowed_mimes( string $value ): array {
		$allowed = array( 'application/pdf', 'image/jpeg', 'image/png', 'image/webp' );
		$values  = array_filter( array_map( 'trim', explode( ',', strtolower( sanitize_text_field( $value ) ) ) ) );
		return array_values( array_intersect( $allowed, $values ) );
	}
	private static function claim_version_parent( string $table, int $id, int $institution_id, string $expected_updated_at ) {
		if ( ! in_array( $table, array( 'forms', 'workflows' ), true ) ) {
			return new \WP_Error( 'invalid_configuration', __( 'Permintaan perubahan konfigurasi tidak valid.', 'sakolaan-admission' ) );
		}
		$claimed_at = current_time( 'mysql', true );
		if ( $claimed_at <= $expected_updated_at ) {
			$claimed_at = gmdate( 'Y-m-d H:i:s', strtotime( $expected_updated_at . ' UTC' ) + 1 );
		}
		global $wpdb;
		$updated = $wpdb->update(
			Installer::table( $table ),
			array( 'updated_at' => $claimed_at ),
			array(
				'id'             => $id,
				'institution_id' => $institution_id,
				'updated_at'     => $expected_updated_at,
			)
		);
		if ( 1 !== $updated ) {
			return new \WP_Error( false === $updated ? 'configuration_update_failed' : 'stale_configuration', __( 'Versi aktif telah berubah. Muat ulang halaman sebelum menerbitkan versi baru.', 'sakolaan-admission' ) );
		}
		return $claimed_at;
	}
	private static function configuration_table( string $entity ): string {
		$tables = array(
			'institution'  => 'institutions',
			'program'      => 'programs',
			'period'       => 'periods',
			'requirement'  => 'document_requirements',
			'announcement' => 'announcements',
		);
		return $tables[ $entity ] ?? '';
	}
	private static function can_manage_configuration( string $entity ): bool {
		$caps = array(
			'institution'  => 'sakolaan_admission_manage_institutions',
			'program'      => 'sakolaan_admission_manage_programs',
			'period'       => 'sakolaan_admission_manage_programs',
			'requirement'  => 'sakolaan_admission_manage_programs',
			'announcement' => 'sakolaan_admission_manage_announcements',
		);
		return isset( $caps[ $entity ] ) && current_user_can( $caps[ $entity ] );
	}
	private static function configuration_section( string $entity ): string {
		return array(
			'institution'  => 'institutions',
			'program'      => 'programs',
			'period'       => 'periods',
			'requirement'  => 'programs',
			'form'         => 'forms',
			'workflow'     => 'forms',
			'announcement' => 'announcements',
		)[ $entity ] ?? 'overview';
	}
	private static function configuration_redirect( string $entity, int $institution_id, string $notice = '', string $error = '' ): void {
		$args = array(
			'page'           => 'sakolaan',
			'view'           => 'admission',
			'section'        => self::configuration_section( $entity ),
			'institution_id' => $institution_id,
		);
		if ( $notice ) {
			$args['notice'] = $notice;
		}
		if ( $error ) {
			$args['config_error'] = $error;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
	private static function audit_configuration( string $entity, int $object_id, int $institution_id, array $before, array $after ): void {
		$changes = array();
		foreach ( $after as $key => $value ) {
			if ( in_array( $key, array( 'updated_at', 'created_at' ), true ) || (string) ( $before[ $key ] ?? '' ) === (string) $value ) {
				continue;
			}
			$changes[ $key ] = array(
				'from' => $before[ $key ] ?? null,
				'to'   => $value,
			);
		}
		$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Called only after the entity-specific admin-post nonce is verified.
		Repository::audit(
			isset( $changes['status'] ) ? 'configuration_status_changed' : 'configuration_updated',
			$entity,
			$object_id,
			$institution_id,
			array(
				'changes' => $changes,
				'reason'  => $reason,
			)
		);
	}
	private static function denied(): void {
		echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Anda tidak memiliki izin untuk melihat bagian ini.', 'sakolaan-admission' ) . '</p></div>'; }
	private static function operation_error( \WP_Error $error ): void {
		$data   = $error->get_error_data();
		$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 400;
		wp_die(
			esc_html( $error->get_error_message() ),
			esc_html__( 'Operasi penerimaan gagal', 'sakolaan-admission' ),
			array(
				'response'  => absint( $status ),
				'back_link' => true,
			)
		); }
	private static function forbidden(): void {
		wp_die( esc_html__( 'Akses ditolak.', 'sakolaan-admission' ), '', array( 'response' => 403 ) ); }
}
