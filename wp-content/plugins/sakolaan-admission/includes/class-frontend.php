<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

final class Frontend {
	public static function register(): void {
		add_action( 'init', array( __CLASS__, 'shortcodes_blocks' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'editor_assets' ) );
		add_filter( 'the_content', array( __CLASS__, 'inject_portal' ), 20 );
		add_filter( 'pijar_sakolaan_homepage_section_patterns', array( __CLASS__, 'homepage_patterns' ) );
		add_filter( 'pijar_sakolaan_section_has_content', array( __CLASS__, 'homepage_has_content' ), 10, 2 );
	}

	public static function shortcodes_blocks(): void {
		$map = array(
			'sakolaan_admission_portal'        => array(
				'method' => 'portal',
				'title'  => __( 'Portal Penerimaan', 'sakolaan-admission' ),
			),
			'sakolaan_admission_open_programs' => array(
				'method' => 'open_programs',
				'title'  => __( 'Pendaftaran yang Dibuka', 'sakolaan-admission' ),
			),
			'sakolaan_admission_programs'      => array(
				'method' => 'programs',
				'title'  => __( 'Daftar Program Penerimaan', 'sakolaan-admission' ),
			),
			'sakolaan_admission_form'          => array(
				'method' => 'form',
				'title'  => __( 'Formulir Pendaftaran', 'sakolaan-admission' ),
			),
			'sakolaan_admission_dashboard'     => array(
				'method' => 'dashboard',
				'title'  => __( 'Dasbor Pendaftar', 'sakolaan-admission' ),
			),
			'sakolaan_admission_status'        => array(
				'method' => 'status',
				'title'  => __( 'Status Pendaftaran', 'sakolaan-admission' ),
			),
			'sakolaan_admission_announcement'  => array(
				'method' => 'announcement',
				'title'  => __( 'Pengumuman Penerimaan', 'sakolaan-admission' ),
			),
		);
		foreach ( $map as $shortcode => $block ) {
			$method = $block['method'];
			add_shortcode( $shortcode, array( __CLASS__, $method ) );
			$block_name = str_replace( '_', '-', str_replace( 'announcement', 'announcements', $method ) );
			register_block_type(
				'sakolaan-admission/' . $block_name,
				array(
					'api_version'     => 3,
					'title'           => $block['title'],
					'category'        => 'widgets',
					'attributes'      => array(
						'programId' => array(
							'type'    => 'integer',
							'default' => 0,
						),
					),
					'editor_script'   => 'sakolaan-admission-blocks',
					'render_callback' => static fn( array $attributes ): string => (string) call_user_func( array( __CLASS__, $method ), array( 'program_id' => (int) ( $attributes['programId'] ?? 0 ) ) ),
				)
			);
		}
		if ( function_exists( 'register_block_pattern' ) ) {
			register_block_pattern(
				'sakolaan-admission/homepage-open-programs',
				array(
					'title'      => __( 'Pendaftaran Dinamis', 'sakolaan-admission' ),
					'categories' => array( 'sakolaan-sections' ),
					'content'    => '<!-- wp:group {"align":"full","className":"sakolaan-section sakolaan-section--ppdb sakolaan-admission-home-section","layout":{"type":"constrained"}} --><div class="wp-block-group alignfull sakolaan-section sakolaan-section--ppdb sakolaan-admission-home-section"><!-- wp:sakolaan-admission/open-programs /--></div><!-- /wp:group -->',
				)
			);
		}
	}

	public static function editor_assets(): void {
		wp_register_script( 'sakolaan-admission-blocks', SAKOLAAN_ADMISSION_URL . 'assets/js/blocks.js', array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-server-side-render' ), SAKOLAAN_ADMISSION_VERSION, true );
		wp_localize_script(
			'sakolaan-admission-blocks',
			'sakolaanAdmissionBlocks',
			array(
				'programLabel' => __( 'ID program (opsional)', 'sakolaan-admission' ),
				'blocks'       => array(
					array(
						'name'  => 'portal',
						'title' => __( 'Portal Penerimaan', 'sakolaan-admission' ),
					),
					array(
						'name'  => 'open-programs',
						'title' => __( 'Pendaftaran yang Dibuka', 'sakolaan-admission' ),
					),
					array(
						'name'  => 'programs',
						'title' => __( 'Daftar Program Penerimaan', 'sakolaan-admission' ),
					),
					array(
						'name'  => 'form',
						'title' => __( 'Formulir Pendaftaran', 'sakolaan-admission' ),
					),
					array(
						'name'  => 'dashboard',
						'title' => __( 'Dasbor Pendaftar', 'sakolaan-admission' ),
					),
					array(
						'name'  => 'status',
						'title' => __( 'Status Pendaftaran', 'sakolaan-admission' ),
					),
					array(
						'name'  => 'announcements',
						'title' => __( 'Pengumuman Penerimaan', 'sakolaan-admission' ),
					),
				),
			)
		);
		wp_enqueue_script( 'sakolaan-admission-blocks' );
	}

	public static function assets(): void {
		$selected_program = absint( $_GET['admission_program'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$return_url       = $selected_program ? add_query_arg( 'admission_program', $selected_program, self::portal_url() ) : self::portal_url();
		wp_enqueue_style( 'sakolaan-admission', SAKOLAAN_ADMISSION_URL . 'assets/css/frontend.css', array(), SAKOLAAN_ADMISSION_VERSION );
		wp_enqueue_script( 'sakolaan-admission', SAKOLAAN_ADMISSION_URL . 'assets/js/portal.js', array(), SAKOLAAN_ADMISSION_VERSION, true );
		wp_localize_script(
			'sakolaan-admission',
			'sakolaanAdmission',
			array(
				'root'     => esc_url_raw( rest_url( 'sakolaan-admission/v1/' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'loginUrl' => wp_login_url( $return_url ),
				'messages' => array(
					'requestFailed'     => __( 'Permintaan tidak dapat diproses.', 'sakolaan-admission' ),
					'saving'            => __( 'Menyimpan…', 'sakolaan-admission' ),
					'submitted'         => __( 'Pendaftaran berhasil dikirim.', 'sakolaan-admission' ),
					'draftSaved'        => __( 'Draf berhasil disimpan.', 'sakolaan-admission' ),
					'creatingAccount'   => __( 'Membuat akun…', 'sakolaan-admission' ),
					'uploading'         => __( 'Mengunggah…', 'sakolaan-admission' ),
					'evidenceReceived'  => __( 'Bukti pembayaran diterima dan menunggu verifikasi.', 'sakolaan-admission' ),
					/* translators: %s: waktu penyimpanan draf. */
					'savedAt'           => __( 'Draf disimpan pukul %s.', 'sakolaan-admission' ),
					'completeRequired'  => __( 'Lengkapi bidang wajib sebelum melanjutkan.', 'sakolaan-admission' ),
					'confirmSubmission' => __( 'Centang konfirmasi sebelum mengirim pendaftaran.', 'sakolaan-admission' ),
					'checkEmail'        => __( 'Akun berhasil dibuat. Periksa email Anda untuk melakukan verifikasi.', 'sakolaan-admission' ),
					'resending'         => __( 'Mengirim ulang email verifikasi…', 'sakolaan-admission' ),
					'resent'            => __( 'Jika akun ditemukan, email verifikasi baru telah dikirim.', 'sakolaan-admission' ),
					'uploadComplete'    => __( 'Berkas berhasil diunggah.', 'sakolaan-admission' ),
					'notAnswered'       => __( 'Belum diisi', 'sakolaan-admission' ),
					'reviewAnswers'     => __( 'Data formulir', 'sakolaan-admission' ),
					'reviewDocuments'   => __( 'Dokumen', 'sakolaan-admission' ),
					'submissionSuccess' => __( 'Pendaftaran berhasil dikirim', 'sakolaan-admission' ),
					'applicationNumber' => __( 'Nomor pendaftaran', 'sakolaan-admission' ),
					'nextInstruction'   => __( 'Pantau status pendaftaran dan selesaikan pembayaran jika diwajibkan melalui dasbor pendaftar.', 'sakolaan-admission' ),
					'openDashboard'     => __( 'Buka dasbor pendaftar', 'sakolaan-admission' ),
					'documentReplaced'  => __( 'Dokumen perbaikan berhasil dikirim dan menunggu verifikasi.', 'sakolaan-admission' ),
				),
			)
		);
	}

	public static function inject_portal( string $content ): string {
		if ( is_admin() || ! is_main_query() || ! in_the_loop() || ! is_page( 'ppdb' ) ) {
			return $content;
		}
		$settings = (array) get_option( 'sakolaan_admission_settings', array() );
		if ( array_key_exists( 'portal_auto_render', $settings ) && ! $settings['portal_auto_render'] ) {
			return $content;
		}
		foreach ( array( 'sakolaan_admission_portal', 'sakolaan_admission_programs', 'sakolaan_admission_form', 'sakolaan_admission_dashboard' ) as $shortcode ) {
			if ( has_shortcode( $content, $shortcode ) ) {
				return $content;
			}
		}
		if ( has_block( 'sakolaan-admission/portal', $content ) || has_block( 'sakolaan-admission/programs', $content ) || has_block( 'sakolaan-admission/form', $content ) ) {
			return $content;
		}
		$placeholder = false !== strpos( wp_strip_all_tags( $content ), 'Konten halaman ini belum diisi.' );
		$portal      = self::portal();
		return $placeholder || '' === trim( wp_strip_all_tags( $content ) ) ? $portal : $content . $portal;
	}

	public static function homepage_patterns( array $patterns ): array {
		$settings = (array) get_option( 'sakolaan_admission_settings', array() );
		if ( ( ! array_key_exists( 'homepage_dynamic_section', $settings ) || $settings['homepage_dynamic_section'] ) && Availability::periods() ) {
			$patterns['ppdb'] = 'sakolaan-admission/homepage-open-programs';
		}
		return $patterns;
	}

	public static function homepage_has_content( bool $has, string $slug ): bool {
		if ( 'ppdb' !== $slug ) {
			return $has;
		}
		return Availability::periods() ? true : $has;
	}

	public static function portal(): string {
		$program_id = absint( $_GET['admission_program'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $program_id ) {
			return '<div class="sakolaan-admission-portal">' . self::journey( is_user_logged_in() ? 2 : 1 ) . self::form( array( 'program_id' => $program_id ) ) . '</div>';
		}
		$out = '<div class="sakolaan-admission-portal"><header class="sakolaan-admission-portal__hero"><p class="sakolaan-admission-kicker">' . esc_html__( 'Portal Penerimaan', 'sakolaan-admission' ) . '</p><h1>' . esc_html__( 'Mulai pendaftaran Anda', 'sakolaan-admission' ) . '</h1><p>' . esc_html__( 'Pilih program yang tersedia, siapkan akun dan dokumen, lalu ikuti setiap tahap sampai pendaftaran berhasil dikirim.', 'sakolaan-admission' ) . '</p></header>' . self::journey( 0 );
		if ( is_user_logged_in() ) {
			$out .= self::dashboard();
		}
		return $out . self::programs() . self::announcement() . '</div>';
	}

	public static function open_programs(): string {
		$rows = Availability::periods();
		$open = array_values( array_filter( $rows, static fn( array $row ): bool => 'open' === $row['availability'] ) );
		$next = array_values( array_filter( $rows, static fn( array $row ): bool => 'upcoming' === $row['availability'] ) );
		if ( ! $open && ! $next ) {
			return self::empty_state( __( 'Belum ada jadwal penerimaan', 'sakolaan-admission' ), __( 'Informasi penerimaan berikutnya akan ditampilkan setelah periode diterbitkan.', 'sakolaan-admission' ) );
		}
		$featured = array_slice( $open ? $open : $next, 0, 3 );
		$title    = $open ? __( 'Pendaftaran sedang dibuka', 'sakolaan-admission' ) : __( 'Pendaftaran akan dibuka', 'sakolaan-admission' );
		/* translators: %d: jumlah program penerimaan yang sedang dibuka. */
		$lead = $open ? sprintf( __( '%d program tersedia. Pilih program dan selesaikan pendaftaran sebelum batas waktu.', 'sakolaan-admission' ), count( $open ) ) : __( 'Siapkan data dan dokumen Anda sebelum jadwal pendaftaran dimulai.', 'sakolaan-admission' );
		$out  = '<div class="sakolaan-admission-open"><header class="sakolaan-admission-open__header"><div><p class="sakolaan-admission-kicker">' . esc_html__( 'Penerimaan Sakolaan', 'sakolaan-admission' ) . '</p><h2>' . esc_html( $title ) . '</h2><p>' . esc_html( $lead ) . '</p></div><a class="wp-element-button" href="' . esc_url( self::public_admission_url() ) . '">' . esc_html__( 'Buka portal penerimaan', 'sakolaan-admission' ) . '</a></header><div class="sakolaan-admission-programs">';
		foreach ( $featured as $row ) {
			$out .= self::program_card( $row );
		}
		return $out . '</div></div>';
	}

	public static function programs( $attributes = array() ): string {
		unset( $attributes );
		$rows = Availability::periods();
		if ( ! $rows ) {
			return self::empty_state( __( 'Belum ada program penerimaan', 'sakolaan-admission' ), __( 'Program yang telah diterbitkan akan tampil di halaman ini.', 'sakolaan-admission' ) );
		}
		$groups = array(
			'open'     => array( __( 'Pendaftaran sedang dibuka', 'sakolaan-admission' ), __( 'Pilih program dan kirim pendaftaran sebelum batas waktu.', 'sakolaan-admission' ) ),
			'upcoming' => array( __( 'Segera dibuka', 'sakolaan-admission' ), __( 'Program berikut belum dapat didaftarkan, tetapi jadwalnya sudah tersedia.', 'sakolaan-admission' ) ),
			'full'     => array( __( 'Kuota telah penuh', 'sakolaan-admission' ), __( 'Program berikut tidak lagi menerima pendaftaran baru.', 'sakolaan-admission' ) ),
		);
		$out    = '<div class="sakolaan-admission-program-groups">';
		foreach ( $groups as $status => $copy ) {
			$items = array_values( array_filter( $rows, static fn( array $row ): bool => $status === $row['availability'] ) );
			if ( ! $items ) {
				continue;
			}
			$out .= '<section class="sakolaan-admission-program-group"><header><h2>' . esc_html( $copy[0] ) . '</h2><p>' . esc_html( $copy[1] ) . '</p></header><div class="sakolaan-admission-programs">';
			foreach ( $items as $row ) {
				$out .= self::program_card( $row );
			}
			$out .= '</div></section>';
		}
		return $out . '</div>';
	}

	public static function form( $attributes = array() ): string {
		global $wpdb;
		$program_id = absint( $attributes['program_id'] ?? ( $_GET['admission_program'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $program_id ) {
			return self::programs(); }
		$availability = Availability::for_program( $program_id );
		$program      = $availability ? Repository::get( 'programs', $program_id, (int) $availability['institution_id'] ) : null;
		if ( ! $program || ! $availability ) {
			return '<div class="sakolaan-admission-error">' . esc_html__( 'Program penerimaan tidak ditemukan.', 'sakolaan-admission' ) . '</div>'; }
		if ( 'open' !== $availability['availability'] ) {
			$title = 'full' === $availability['availability'] ? __( 'Kuota pendaftaran telah penuh', 'sakolaan-admission' ) : __( 'Pendaftaran belum dibuka', 'sakolaan-admission' );
			return self::empty_state( $title, __( 'Silakan periksa jadwal program atau pilih program penerimaan lainnya.', 'sakolaan-admission' ) ); }
		$period  = Repository::get( 'periods', (int) $availability['period_id'], (int) $availability['institution_id'] );
		$form    = Repository::get( 'forms', (int) $program['form_id'], (int) $program['institution_id'] );
		$version = $form ? Repository::get( 'form_versions', (int) $form['current_version_id'], (int) $program['institution_id'] ) : null;
		if ( ! $version ) {
			return '<div class="sakolaan-admission-error">' . esc_html__( 'Formulir pendaftaran belum tersedia.', 'sakolaan-admission' ) . '</div>'; }
		if ( ! is_user_logged_in() ) {
			$return_url = add_query_arg( 'admission_program', $program_id, self::portal_url() );
			return '<section class="sakolaan-admission-auth"><div class="sakolaan-admission-auth__intro"><p class="sakolaan-admission-kicker">' . esc_html__( 'Tahap 2 dari 5 · Akun', 'sakolaan-admission' ) . '</p><h2>' . esc_html( $program['name'] ) . '</h2><p>' . esc_html__( 'Masuk dengan akun pendaftar atau buat akun baru. Setelah email terverifikasi, Anda dapat mulai mengisi formulir.', 'sakolaan-admission' ) . '</p><a class="wp-element-button is-secondary" href="' . esc_url( wp_login_url( $return_url ) ) . '">' . esc_html__( 'Masuk ke akun', 'sakolaan-admission' ) . '</a></div>' . self::registration_form( $program_id ) . '</section>';
		}
		$applicant = Repository::applicant_for_user( get_current_user_id() );
		if ( $applicant && empty( $applicant['email_verified_at'] ) ) {
			$user = wp_get_current_user();
			return '<section class="sakolaan-admission-verification"><span aria-hidden="true">✉</span><p class="sakolaan-admission-kicker">' . esc_html__( 'Tahap 2 dari 5 · Verifikasi akun', 'sakolaan-admission' ) . '</p><h2>' . esc_html__( 'Periksa email Anda', 'sakolaan-admission' ) . '</h2><p>' . esc_html__( 'Buka tautan verifikasi yang kami kirim sebelum memulai pengisian formulir.', 'sakolaan-admission' ) . '</p><form class="sakolaan-verification-resend"><input type="hidden" name="email" value="' . esc_attr( $user->user_email ) . '"><input type="hidden" name="program_id" value="' . (int) $program_id . '"><button class="wp-element-button is-secondary">' . esc_html__( 'Kirim ulang email verifikasi', 'sakolaan-admission' ) . '</button><p role="status" aria-live="polite"></p></form></section>';
		}
		$existing = $applicant ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Installer::table( 'applications' ) . ' WHERE applicant_id=%d AND program_id=%d AND period_id=%d', $applicant['id'], $program_id, $period['id'] ), ARRAY_A ) : null;
		if ( $existing && 'draft' !== $existing['status'] ) {
			return self::dashboard(); }
		if ( $existing ) {
			$version = Repository::get( 'form_versions', (int) $existing['form_version_id'], (int) $program['institution_id'] ); }
		$existing_answers   = array();
		$existing_documents = array();
		if ( $existing ) {
			foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT field_key,value_json FROM ' . Installer::table( 'answers' ) . ' WHERE application_id=%d', $existing['id'] ), ARRAY_A ) as $answer ) {
				$existing_answers[ $answer['field_key'] ] = json_decode( $answer['value_json'], true );
			} $existing_documents = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT requirement_id FROM ' . Installer::table( 'documents' ) . ' WHERE application_id=%d AND requirement_id IS NOT NULL AND replaced_by IS NULL', $existing['id'] ) ) ); }
		$schema       = json_decode( $version['schema_json'], true );
		$requirements = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . Installer::table( 'document_requirements' ) . ' WHERE program_id=%d ORDER BY sort_order,id', $program_id ), ARRAY_A );
		$initial_step = 0;
		foreach ( $schema['steps'] as $index => $step ) {
			$complete = true;
			foreach ( $step['fields'] as $field ) {
				if ( ! empty( $field['required'] ) && empty( $existing_answers[ $field['key'] ] ) ) {
					$complete = false;
					break;
				}
			}
			if ( $complete ) {
				$initial_step = $index + 1;
			} else {
				break;
			}
		}
		$required_ids = array_map( 'intval', array_column( array_filter( $requirements, static fn( array $item ): bool => ! empty( $item['required'] ) ), 'id' ) );
		if ( $initial_step >= count( $schema['steps'] ) && ! array_diff( $required_ids, $existing_documents ) ) {
			$initial_step = count( $schema['steps'] ) + 1;
		}
		$fee      = $program['fee_required'] ? Labels::currency( (float) $program['fee_amount'], $program['currency'] ) : __( 'Tanpa biaya', 'sakolaan-admission' );
		$out      = '<form class="sakolaan-admission-form" data-program="' . (int) $program_id . '" data-period="' . (int) $period['id'] . '" data-initial-step="' . (int) $initial_step . '"' . ( $existing ? ' data-application="' . (int) $existing['id'] . '"' : '' ) . '><header class="sakolaan-admission-form__header"><div><p class="sakolaan-admission-kicker">' . esc_html__( 'Tahap 3–5 · Pengisian pendaftaran', 'sakolaan-admission' ) . '</p><h2>' . esc_html( $program['name'] ) . '</h2><p>' . esc_html__( 'Data disimpan setiap kali Anda menekan Berikutnya. Anda juga dapat menyimpan draf kapan saja.', 'sakolaan-admission' ) . '</p></div><dl class="sakolaan-admission-form-summary"><div><dt>' . esc_html__( 'Periode', 'sakolaan-admission' ) . '</dt><dd>' . esc_html( $period['name'] ) . '</dd></div><div><dt>' . esc_html__( 'Batas waktu', 'sakolaan-admission' ) . '</dt><dd>' . esc_html( Labels::date( $period['closes_at'] ) ) . '</dd></div><div><dt>' . esc_html__( 'Biaya', 'sakolaan-admission' ) . '</dt><dd>' . esc_html( $fee ) . '</dd></div></dl></header><ol class="sakolaan-admission-progress" aria-label="' . esc_attr__( 'Tahapan formulir', 'sakolaan-admission' ) . '">';
		$progress = array_merge( array_column( $schema['steps'], 'label' ), array( __( 'Dokumen', 'sakolaan-admission' ), __( 'Tinjau & Kirim', 'sakolaan-admission' ) ) );
		foreach ( $progress as $index => $label ) {
			$out .= '<li' . ( $initial_step === $index ? ' aria-current="step"' : '' ) . ' data-progress-step="' . (int) $index . '"><span>' . esc_html( (string) ( $index + 1 ) ) . '</span><small>' . esc_html( $label ) . '</small></li>'; }
		$out .= '</ol><div class="sakolaan-admission-errors" role="alert" aria-live="assertive" hidden></div>';
		foreach ( $schema['steps'] as $index => $step ) {
			$out .= '<fieldset class="sakolaan-admission-step" data-step="' . (int) $index . '"' . ( $initial_step === $index ? '' : ' hidden' ) . '><legend>' . esc_html( $step['label'] ) . '</legend>';
			foreach ( $step['fields'] as $field ) {
				$out .= self::field( $field, $existing_answers[ $field['key'] ] ?? '' ); }
			$out .= '<div class="sakolaan-admission-actions">';
			if ( $index ) {
				$out .= '<button type="button" class="wp-element-button is-secondary" data-prev>← ' . esc_html__( 'Sebelumnya', 'sakolaan-admission' ) . '</button>';
			} $out .= '<button type="button" class="wp-element-button is-secondary" data-save>' . esc_html__( 'Simpan draf', 'sakolaan-admission' ) . '</button><button type="button" class="wp-element-button" data-next>' . esc_html__( 'Simpan & lanjutkan', 'sakolaan-admission' ) . ' →</button></div></fieldset>';
		}
		$document_step = count( $schema['steps'] );
		$out          .= '<fieldset class="sakolaan-admission-step" data-step="' . (int) $document_step . '"' . ( $initial_step === $document_step ? '' : ' hidden' ) . '><legend>' . esc_html__( 'Dokumen persyaratan', 'sakolaan-admission' ) . '</legend><p>' . esc_html__( 'Unggah berkas PDF, JPG, PNG, atau WebP. Status unggahan ditampilkan sebelum Anda melanjutkan.', 'sakolaan-admission' ) . '</p>';
		if ( $requirements ) {
			foreach ( $requirements as $requirement ) {
				$uploaded = in_array( (int) $requirement['id'], $existing_documents, true );
				$out     .= '<div class="sakolaan-admission-field sakolaan-admission-document" data-document-label="' . esc_attr( $requirement['label'] ) . '"><label for="sakolaan-document-' . (int) $requirement['id'] . '">' . esc_html( $requirement['label'] ) . ( $requirement['required'] ? ' <span class="sakolaan-admission-required" aria-label="' . esc_attr__( 'wajib', 'sakolaan-admission' ) . '">*</span>' : '' ) . '</label><span class="sakolaan-admission-document__status ' . ( $uploaded ? 'is-uploaded' : '' ) . '">' . esc_html( $uploaded ? __( 'Sudah diunggah', 'sakolaan-admission' ) : __( 'Belum diunggah', 'sakolaan-admission' ) ) . '</span><input id="sakolaan-document-' . (int) $requirement['id'] . '" type="file" name="document_' . esc_attr( $requirement['document_key'] ) . '" data-requirement="' . (int) $requirement['id'] . '" data-uploaded="' . ( $uploaded ? '1' : '0' ) . '" accept=".pdf,.jpg,.jpeg,.png,.webp"' . ( $requirement['required'] && ! $uploaded ? ' required' : '' ) . '><small>' . esc_html( $uploaded ? __( 'Pilih berkas baru hanya jika ingin menggantinya.', 'sakolaan-admission' ) : ( $requirement['required'] ? __( 'Dokumen ini wajib diunggah.', 'sakolaan-admission' ) : __( 'Dokumen ini bersifat opsional.', 'sakolaan-admission' ) ) ) . '</small></div>';
			}
		} else {
			$out .= '<p class="sakolaan-admission-muted">' . esc_html__( 'Program ini tidak memiliki persyaratan dokumen.', 'sakolaan-admission' ) . '</p>';
		}
		$out        .= '<div class="sakolaan-admission-actions"><button type="button" class="wp-element-button is-secondary" data-prev>← ' . esc_html__( 'Sebelumnya', 'sakolaan-admission' ) . '</button><button type="button" class="wp-element-button is-secondary" data-save>' . esc_html__( 'Simpan draf', 'sakolaan-admission' ) . '</button><button type="button" class="wp-element-button" data-next>' . esc_html__( 'Tinjau pendaftaran', 'sakolaan-admission' ) . ' →</button></div></fieldset>';
		$review_step = $document_step + 1;
		$out        .= '<fieldset class="sakolaan-admission-step sakolaan-admission-review" data-step="' . (int) $review_step . '"' . ( $initial_step === $review_step ? '' : ' hidden' ) . '><legend>' . esc_html__( 'Tinjau dan kirim', 'sakolaan-admission' ) . '</legend><p>' . esc_html__( 'Periksa kembali seluruh data. Setelah dikirim, jawaban tidak dapat diubah kecuali petugas meminta perbaikan.', 'sakolaan-admission' ) . '</p><div class="sakolaan-admission-review__content" data-review-content></div><label class="sakolaan-admission-confirm"><input type="checkbox" data-confirm-submit> <span>' . esc_html__( 'Saya menyatakan data yang saya berikan benar dan siap mengirim pendaftaran ini.', 'sakolaan-admission' ) . '</span></label><div class="sakolaan-admission-actions"><button type="button" class="wp-element-button is-secondary" data-prev>← ' . esc_html__( 'Ubah data', 'sakolaan-admission' ) . '</button><button type="button" class="wp-element-button is-secondary" data-save>' . esc_html__( 'Simpan draf', 'sakolaan-admission' ) . '</button><button type="submit" class="wp-element-button" data-submit>' . esc_html__( 'Kirim pendaftaran', 'sakolaan-admission' ) . '</button></div></fieldset>';
		return $out . '<p class="sakolaan-admission-status" role="status" aria-live="polite"></p></form>';
	}

	private static function field( array $field, $value = '' ): string {
		$type        = sanitize_key( $field['type'] ?? 'text' );
		$key         = sanitize_key( $field['key'] ?? '' );
		$label       = sanitize_text_field( $field['label'] ?? '' );
		$help        = sanitize_text_field( $field['help'] ?? '' );
		$placeholder = sanitize_text_field( $field['placeholder'] ?? '' );
		$is_required = ! empty( $field['required'] );
		$options     = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();
		$visibility  = isset( $field['visibility'] ) && is_array( $field['visibility'] ) ? $field['visibility'] : array();
		if ( 'heading' === $type ) {
			return '<h3>' . esc_html( $label ) . '</h3>'; }
		if ( 'info' === $type ) {
			return '<p>' . esc_html( $help ? $help : $label ) . '</p>'; }
		if ( 'conditional_group' === $type || '' === $key ) {
			return ''; }
		$required          = $is_required ? ' required' : '';
		$id                = 'sakolaan-field-' . $key;
		$visibility_markup = empty( $visibility['field'] ) ? '' : ' data-visible-field="' . esc_attr( $visibility['field'] ) . '" data-visible-operator="' . esc_attr( $visibility['operator'] ?? 'equals' ) . '" data-visible-value="' . esc_attr( $visibility['value'] ?? '' ) . '"';
		$out               = '<div class="sakolaan-admission-field" data-review-key="' . esc_attr( $key ) . '" data-review-label="' . esc_attr( $label ) . '"' . $visibility_markup . '><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . ( $is_required ? ' <span class="sakolaan-admission-required" aria-label="' . esc_attr__( 'wajib', 'sakolaan-admission' ) . '">*</span>' : '' ) . '</label>';
		if ( 'textarea' === $type ) {
			$out .= '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" placeholder="' . esc_attr( $placeholder ) . '"' . $required . '>' . esc_textarea( is_scalar( $value ) ? (string) $value : '' ) . '</textarea>'; } elseif ( in_array( $type, array( 'select', 'radio', 'checkbox' ), true ) ) {
			if ( 'select' === $type ) {
				$out .= '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '"' . $required . '><option value="">' . esc_html__( 'Pilih salah satu', 'sakolaan-admission' ) . '</option>';
				foreach ( $options as $option ) {
					$out .= '<option value="' . esc_attr( $option['value'] ) . '"' . selected( (string) $value, $option['value'], false ) . '>' . esc_html( $option['label'] ) . '</option>';
				} $out .= '</select>'; } else {
				foreach ( $options as $option ) {
					$checked = 'checkbox' === $type ? in_array( $option['value'], (array) $value, true ) : (string) $value === $option['value'];
					$out    .= '<label class="sakolaan-admission-choice"><input type="' . esc_attr( $type ) . '" name="' . esc_attr( $key ) . ( 'checkbox' === $type ? '[]' : '' ) . '" value="' . esc_attr( $option['value'] ) . '"' . checked( $checked, true, false ) . $required . '> ' . esc_html( $option['label'] ) . '</label>'; }
				}
			} elseif ( 'file' === $type ) {
				if ( $value ) {
						$out .= '<small class="is-success">' . esc_html__( 'Sudah diunggah. Pilih berkas hanya jika ingin menggantinya.', 'sakolaan-admission' ) . '</small><input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( (string) $value ) . '">';
				} $out .= '<input id="' . esc_attr( $id ) . '" type="file" name="' . esc_attr( $key ) . '" accept=".pdf,.jpg,.jpeg,.png,.webp"' . ( $value ? '' : $required ) . '>'; } else {
					$input_type = in_array( $type, array( 'email', 'number', 'date' ), true ) ? $type : ( 'phone' === $type ? 'tel' : 'text' );
					$out       .= '<input id="' . esc_attr( $id ) . '" type="' . esc_attr( $input_type ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( is_scalar( $value ) ? (string) $value : '' ) . '" placeholder="' . esc_attr( $placeholder ) . '"' . $required . '>'; }
				if ( $help ) {
					$out .= '<small>' . esc_html( $help ) . '</small>'; }
				return $out . '</div>';
	}

	private static function registration_form( int $program_id = 0 ): string {
		return '<form class="sakolaan-admission-register"><h3>' . esc_html__( 'Buat akun pendaftar', 'sakolaan-admission' ) . '</h3><p>' . esc_html__( 'Akun digunakan untuk menyimpan draf dan memantau perkembangan pendaftaran.', 'sakolaan-admission' ) . '</p><input type="hidden" name="program_id" value="' . (int) $program_id . '"><p class="sakolaan-admission-hp" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></p><label>' . esc_html__( 'Nama lengkap', 'sakolaan-admission' ) . '<input name="name" autocomplete="name" required></label><label>' . esc_html__( 'Alamat email', 'sakolaan-admission' ) . '<input type="email" name="email" autocomplete="email" required></label><label>' . esc_html__( 'Kata sandi', 'sakolaan-admission' ) . '<input type="password" minlength="12" name="password" autocomplete="new-password" required><small>' . esc_html__( 'Gunakan sedikitnya 12 karakter.', 'sakolaan-admission' ) . '</small></label><button class="wp-element-button">' . esc_html__( 'Buat akun dan verifikasi email', 'sakolaan-admission' ) . '</button><p role="status" aria-live="polite"></p></form>';
	}

	public static function dashboard(): string {
		if ( ! is_user_logged_in() ) {
			return '<div class="sakolaan-admission-callout"><h2>' . esc_html__( 'Masuk ke portal pendaftar', 'sakolaan-admission' ) . '</h2><p>' . esc_html__( 'Masuk untuk melihat status, dokumen, pembayaran, dan keputusan penerimaan Anda.', 'sakolaan-admission' ) . '</p><a class="wp-element-button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Masuk ke akun', 'sakolaan-admission' ) . '</a></div>'; }
		global $wpdb;
		$applicant = Repository::applicant_for_user( get_current_user_id() );
		if ( ! $applicant ) {
			return self::empty_state( __( 'Profil pendaftar belum tersedia', 'sakolaan-admission' ), __( 'Hubungi petugas penerimaan jika akun Anda seharusnya sudah terhubung.', 'sakolaan-admission' ) ); }
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT a.*, p.name program_name, pe.name period_name FROM ' . Installer::table( 'applications' ) . ' a INNER JOIN ' . Installer::table( 'programs' ) . ' p ON p.id=a.program_id INNER JOIN ' . Installer::table( 'periods' ) . ' pe ON pe.id=a.period_id WHERE a.applicant_id=%d ORDER BY a.created_at DESC', $applicant['id'] ), ARRAY_A );
		if ( ! $rows ) {
			return self::empty_state( __( 'Belum ada pendaftaran', 'sakolaan-admission' ), __( 'Pilih program penerimaan untuk memulai pendaftaran pertama Anda.', 'sakolaan-admission' ) ); }
		$out = '<div class="sakolaan-admission-dashboard"><header class="sakolaan-admission-dashboard__header"><div><p class="sakolaan-admission-kicker">' . esc_html__( 'Portal Pendaftar', 'sakolaan-admission' ) . '</p><h2>' . esc_html__( 'Pendaftaran saya', 'sakolaan-admission' ) . '</h2></div><a class="wp-element-button is-secondary" href="' . esc_url( remove_query_arg( 'admission_program', get_permalink() ) ) . '">' . esc_html__( 'Lihat program lain', 'sakolaan-admission' ) . '</a></header>';
		foreach ( $rows as $row ) {
			$documents            = $wpdb->get_results( $wpdb->prepare( 'SELECT id,requirement_id,original_name,status,reviewer_notes,created_at FROM ' . Installer::table( 'documents' ) . ' WHERE application_id=%d AND replaced_by IS NULL ORDER BY created_at DESC', $row['id'] ), ARRAY_A );
			$payment              = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Installer::table( 'payments' ) . ' WHERE application_id=%d', $row['id'] ), ARRAY_A );
			$history              = $wpdb->get_results( $wpdb->prepare( 'SELECT previous_status,new_status,reason,created_at FROM ' . Installer::table( 'status_history' ) . ' WHERE application_id=%d ORDER BY created_at DESC', $row['id'] ), ARRAY_A );
			$institution          = Repository::get( 'institutions', (int) $row['institution_id'] );
			$institution_settings = $institution ? (array) json_decode( (string) $institution['settings'], true ) : array();
			$global_settings      = (array) get_option( 'sakolaan_admission_settings', array() );
			$payment_instructions = $institution_settings['payment_instructions'] ?? ( $global_settings['payment_instructions'] ?? '' );
			$continue_url         = add_query_arg(
				array(
					'admission_program' => (int) $row['program_id'],
					'application'       => (int) $row['id'],
				),
				self::portal_url()
			);
			$revision_document    = current( array_filter( $documents, static fn( array $document ): bool => in_array( $document['status'], array( 'rejected', 'revision_requested' ), true ) ) );
			if ( 'draft' === $row['status'] ) {
				$action_label = __( 'Lanjutkan pengisian', 'sakolaan-admission' );
				$action_url   = $continue_url;
			} elseif ( $revision_document ) {
				$action_label = __( 'Unggah ulang dokumen', 'sakolaan-admission' );
				$action_url   = '#document-revision-' . (int) $revision_document['id'];
			} elseif ( $payment && in_array( $payment['status'], array( 'unpaid', 'rejected' ), true ) ) {
				$action_label = 'rejected' === $payment['status'] ? __( 'Unggah ulang bukti', 'sakolaan-admission' ) : __( 'Unggah bukti pembayaran', 'sakolaan-admission' );
				$action_url   = '#payment-' . (int) $payment['id'];
			} elseif ( in_array( $row['status'], array( 'accepted', 'waitlisted', 'rejected' ), true ) ) {
				$action_label = __( 'Lihat hasil', 'sakolaan-admission' );
				$action_url   = '#application-' . (int) $row['id'];
			} else {
				$action_label = __( 'Lihat perkembangan', 'sakolaan-admission' );
				$action_url   = '#application-' . (int) $row['id'];
			}
			$out .= '<article class="sakolaan-admission-application" id="application-' . (int) $row['id'] . '"><header class="sakolaan-admission-application__header"><div><p class="sakolaan-admission-kicker">' . esc_html( $row['period_name'] ) . '</p><h3>' . esc_html( $row['program_name'] ) . '</h3><p class="sakolaan-admission-number">' . esc_html__( 'Nomor pendaftaran:', 'sakolaan-admission' ) . ' <strong>' . esc_html( $row['application_number'] ) . '</strong></p></div><div class="sakolaan-admission-application__actions"><span class="sakolaan-admission-badge ' . esc_attr( Labels::badge_class( $row['status'] ) ) . '">' . esc_html( Labels::status( $row['status'] ) ) . '</span><a class="wp-element-button is-secondary" href="' . esc_url( $action_url ) . '">' . esc_html( $action_label ) . '</a></div></header><div class="sakolaan-admission-application__grid">';
			$out .= '<section class="sakolaan-admission-application__section"><h4>' . esc_html__( 'Riwayat status', 'sakolaan-admission' ) . '</h4><ol class="sakolaan-admission-timeline">';
			if ( $history ) {
				foreach ( $history as $event ) {
					$out .= '<li><span aria-hidden="true"></span><div><strong>' . esc_html( Labels::status( $event['new_status'] ) ) . '</strong><time datetime="' . esc_attr( mysql2date( DATE_W3C, $event['created_at'], false ) ) . '">' . esc_html( Labels::date( $event['created_at'] ) ) . '</time>' . ( $event['reason'] ? '<p>' . esc_html( $event['reason'] ) . '</p>' : '' ) . '</div></li>'; }
			} else {
				$out .= '<li><span aria-hidden="true"></span><div><strong>' . esc_html( Labels::status( $row['status'] ) ) . '</strong><time>' . esc_html( Labels::date( $row['created_at'] ) ) . '</time></div></li>'; }
			$out .= '</ol></section><section class="sakolaan-admission-application__section"><h4>' . esc_html__( 'Dokumen', 'sakolaan-admission' ) . '</h4>';
			if ( $documents ) {
				$out .= '<ul class="sakolaan-admission-records">';
				foreach ( $documents as $document ) {
					$download_url = wp_nonce_url(
						add_query_arg(
							array(
								'action'      => 'sakolaan_admission_download',
								'document_id' => (int) $document['id'],
							),
							admin_url( 'admin-post.php' )
						),
						'sakolaan_admission_download_' . (int) $document['id']
					);
					$out         .= '<li><div><a href="' . esc_url( $download_url ) . '">' . esc_html( $document['original_name'] ) . '</a><span class="sakolaan-admission-badge ' . esc_attr( Labels::badge_class( $document['status'] ) ) . '">' . esc_html( Labels::status( $document['status'] ) ) . '</span></div>';
					if ( $document['reviewer_notes'] ) {
						$out .= '<br><small>' . esc_html( $document['reviewer_notes'] ) . '</small>'; }
					if ( $document['requirement_id'] && in_array( $document['status'], array( 'rejected', 'revision_requested' ), true ) ) {
						$out .= '<form id="document-revision-' . (int) $document['id'] . '" class="sakolaan-document-replacement" data-application="' . (int) $row['id'] . '" data-document="' . (int) $document['id'] . '" data-requirement="' . (int) $document['requirement_id'] . '"><label>' . esc_html__( 'Berkas perbaikan', 'sakolaan-admission' ) . '<input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required></label><button class="wp-element-button is-secondary">' . esc_html__( 'Unggah ulang dokumen', 'sakolaan-admission' ) . '</button><p role="status" aria-live="polite"></p></form>';
					}
					$out .= '</li>';
				}
				$out .= '</ul>';
			} else {
				$out .= '<p class="sakolaan-admission-muted">' . esc_html__( 'Belum ada dokumen yang diunggah.', 'sakolaan-admission' ) . '</p>';
			}
			$out .= '</section></div>';
			if ( $payment ) {
				$out .= '<section id="payment-' . (int) $payment['id'] . '" class="sakolaan-admission-payment"><div class="sakolaan-admission-payment__heading"><div><p class="sakolaan-admission-kicker">' . esc_html__( 'Pembayaran', 'sakolaan-admission' ) . '</p><h4>' . esc_html( Labels::currency( (float) $payment['amount'], $payment['currency'] ) ) . '</h4></div><span class="sakolaan-admission-badge ' . esc_attr( Labels::badge_class( $payment['status'] ) ) . '">' . esc_html( Labels::status( $payment['status'] ) ) . '</span></div>';
				if ( $payment['due_at'] ) {
					$out .= '<p>' . esc_html__( 'Batas pembayaran:', 'sakolaan-admission' ) . ' <strong>' . esc_html( Labels::date( $payment['due_at'] ) ) . '</strong></p>'; }
				if ( $payment_instructions ) {
					$out .= '<div class="sakolaan-admission-instructions">' . wp_kses_post( wpautop( $payment_instructions ) ) . '</div>'; }
				if ( $payment['reviewer_notes'] ) {
					$out .= '<div class="sakolaan-admission-feedback"><strong>' . esc_html__( 'Catatan pemeriksa', 'sakolaan-admission' ) . '</strong><p>' . esc_html( $payment['reviewer_notes'] ) . '</p></div>'; }
				if ( in_array( $payment['status'], array( 'unpaid', 'rejected' ), true ) ) {
					$out .= '<form class="sakolaan-payment-evidence" data-payment="' . esc_attr( $payment['id'] ) . '"><label>' . esc_html__( 'Bukti pembayaran', 'sakolaan-admission' ) . '<input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required><small>' . esc_html__( 'Unggah PDF, JPG, PNG, atau WebP.', 'sakolaan-admission' ) . '</small></label><button class="wp-element-button">' . esc_html__( 'Kirim untuk diverifikasi', 'sakolaan-admission' ) . '</button><p role="status" aria-live="polite"></p></form>'; }
				$out .= '</section>';
			}
			$out .= '</article>';
		} return $out . '</div>';
	}

	public static function status(): string {
		return self::dashboard(); }

	public static function announcement( $attributes = array() ): string {
		global $wpdb;
		$program_id = absint( $attributes['program_id'] ?? 0 );
		$sql        = 'SELECT * FROM ' . Installer::table( 'announcements' ) . " WHERE status='published'";
		$args       = array();
		if ( $program_id ) {
			$sql   .= ' AND (program_id IS NULL OR program_id=%d)';
			$args[] = $program_id;
		} $sql .= ' ORDER BY published_at DESC LIMIT 20';
		$rows   = $args ? $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A ) : $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $rows ) {
			return self::empty_state( __( 'Belum ada pengumuman', 'sakolaan-admission' ), __( 'Pengumuman penerimaan yang telah diterbitkan akan tampil di sini.', 'sakolaan-admission' ) ); }
		$out = '<div class="sakolaan-admission-announcements">';
		foreach ( $rows as $row ) {
			$out .= '<article><p class="sakolaan-admission-kicker">' . esc_html__( 'Pengumuman Penerimaan', 'sakolaan-admission' ) . '</p><h3>' . esc_html( $row['title'] ) . '</h3>' . ( $row['published_at'] ? '<time datetime="' . esc_attr( mysql2date( DATE_W3C, $row['published_at'], false ) ) . '">' . esc_html( Labels::date( $row['published_at'], false ) ) . '</time>' : '' ) . '<div>' . wp_kses_post( wpautop( $row['content'] ) ) . '</div></article>';
		} return $out . '</div>';
	}

	private static function journey( int $current ): string {
		$steps = array(
			__( 'Pilih program', 'sakolaan-admission' ),
			__( 'Akun & verifikasi', 'sakolaan-admission' ),
			__( 'Isi formulir', 'sakolaan-admission' ),
			__( 'Unggah dokumen', 'sakolaan-admission' ),
			__( 'Tinjau & kirim', 'sakolaan-admission' ),
		);
		$out   = '<ol class="sakolaan-admission-journey" aria-label="' . esc_attr__( 'Alur pendaftaran', 'sakolaan-admission' ) . '">';
		foreach ( $steps as $index => $label ) {
			$out .= '<li class="' . ( $index < $current ? 'is-complete' : ( $index === $current ? 'is-current' : '' ) ) . '"' . ( $index === $current ? ' aria-current="step"' : '' ) . '><span>' . esc_html( (string) ( $index + 1 ) ) . '</span><strong>' . esc_html( $label ) . '</strong></li>';
		}
		return $out . '</ol>';
	}

	private static function program_card( array $row ): string {
		$status    = array(
			'open'     => array( __( 'Pendaftaran dibuka', 'sakolaan-admission' ), 'is-success', __( 'Daftar sekarang', 'sakolaan-admission' ) ),
			'upcoming' => array( __( 'Segera dibuka', 'sakolaan-admission' ), 'is-info', __( 'Belum dapat didaftarkan', 'sakolaan-admission' ) ),
			'full'     => array( __( 'Kuota penuh', 'sakolaan-admission' ), 'is-warning', __( 'Pendaftaran ditutup', 'sakolaan-admission' ) ),
		);
		$copy      = $status[ $row['availability'] ] ?? array( __( 'Tidak tersedia', 'sakolaan-admission' ), 'is-info', __( 'Tidak tersedia', 'sakolaan-admission' ) );
		$remaining = Availability::remaining( $row );
		$fee       = $row['fee_required'] ? Labels::currency( (float) $row['fee_amount'], $row['currency'] ) : __( 'Tanpa biaya', 'sakolaan-admission' );
		$url       = add_query_arg( 'admission_program', (int) $row['program_id'], self::portal_url() );
		$out       = '<article class="sakolaan-admission-program-card"><div class="sakolaan-admission-program-card__header"><p class="sakolaan-admission-kicker">' . esc_html( $row['institution_name'] ) . '</p><span class="sakolaan-admission-badge ' . esc_attr( $copy[1] ) . '">' . esc_html( $copy[0] ) . '</span></div><h3>' . esc_html( $row['program_name'] ) . '</h3>';
		if ( $row['description'] ) {
			$out .= '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( $row['description'] ), 24 ) ) . '</p>';
		}
		$out .= '<dl class="sakolaan-admission-program-meta"><div><dt>' . esc_html__( 'Periode', 'sakolaan-admission' ) . '</dt><dd>' . esc_html( $row['period_name'] ) . '</dd></div><div><dt>' . esc_html__( 'Jadwal', 'sakolaan-admission' ) . '</dt><dd>' . esc_html( Labels::date( $row['opens_at'], false ) . ' – ' . Labels::date( $row['closes_at'], false ) ) . '</dd></div><div><dt>' . esc_html__( 'Biaya', 'sakolaan-admission' ) . '</dt><dd>' . esc_html( $fee ) . '</dd></div>';
		if ( null !== $remaining ) {
			$out .= '<div><dt>' . esc_html__( 'Sisa kuota', 'sakolaan-admission' ) . '</dt><dd>' . esc_html( Labels::number( (float) $remaining ) ) . '</dd></div>';
		}
		$out .= '</dl>';
		if ( 'open' === $row['availability'] ) {
			$out .= '<a class="wp-element-button" href="' . esc_url( $url ) . '"><span>' . esc_html( $copy[2] ) . '</span><span aria-hidden="true">→</span></a>';
		} else {
			$out .= '<span class="wp-element-button is-disabled" aria-disabled="true">' . esc_html( $copy[2] ) . '</span>';
		}
		return $out . '</article>';
	}

	private static function portal_url(): string {
		$page = get_page_by_path( 'ppdb' );
		return $page instanceof \WP_Post && 'publish' === $page->post_status ? (string) get_permalink( $page ) : home_url( '/ppdb/' );
	}

	private static function public_admission_url(): string {
		if ( function_exists( 'sakolaan_get_setting' ) ) {
			$url = (string) sakolaan_get_setting( 'ppdb.url', '' );
			if ( $url ) {
				return $url;
			}
		}
		return self::portal_url();
	}

	private static function empty_state( string $title, string $description ): string {
		return '<div class="sakolaan-admission-empty"><span class="sakolaan-admission-empty__icon" aria-hidden="true">◎</span><h3>' . esc_html( $title ) . '</h3><p>' . esc_html( $description ) . '</p></div>';
	}
}
