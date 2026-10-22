<?php
/**
 * Antarmuka admin dan handler aman untuk dataset demo.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Key transient laporan per pengguna.
 *
 * @return string
 */
function sakolaan_demo_report_key() {
	return 'sakolaan_demo_report_' . get_current_user_id();
}

/**
 * Simpan laporan singkat lalu kembali ke halaman Data Demo.
 *
 * @param array $report Laporan operasi.
 * @return void
 */
function sakolaan_demo_redirect_with_report( $report ) {
	set_transient( sakolaan_demo_report_key(), $report, 10 * MINUTE_IN_SECONDS );
	wp_safe_redirect( sakolaan_admin_url( 'more', array( 'panel' => 'demo' ) ) );
	exit;
}

/**
 * Validasi request destruktif dan cegah proses bersamaan.
 *
 * @param string $nonce_action Aksi nonce.
 * @param string $confirmation Teks konfirmasi yang diwajibkan.
 * @return void
 */
function sakolaan_demo_validate_request( $nonce_action, $confirmation ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Anda tidak memiliki izin untuk mengelola data demo.', 'sakolaan' ), 403 );
	}

	check_admin_referer( $nonce_action );

	$submitted = isset( $_POST['sakolaan_demo_confirmation'] )
		? sanitize_text_field( wp_unslash( $_POST['sakolaan_demo_confirmation'] ) )
		: '';

	if ( $confirmation !== $submitted ) {
		wp_die( esc_html__( 'Teks konfirmasi tidak sesuai. Tidak ada data yang diubah.', 'sakolaan' ), 400 );
	}

	$lock_time = absint( get_option( 'sakolaan_demo_lock', 0 ) );

	if ( $lock_time && ( time() - $lock_time ) < 15 * MINUTE_IN_SECONDS ) {
		wp_die( esc_html__( 'Proses data demo lain masih berjalan. Coba lagi setelah proses tersebut selesai.', 'sakolaan' ), 409 );
	}

	if ( $lock_time ) {
		delete_option( 'sakolaan_demo_lock' );
	}

	if ( ! add_option( 'sakolaan_demo_lock', time(), '', false ) ) {
		wp_die( esc_html__( 'Proses data demo lain baru saja dimulai. Coba lagi setelah proses tersebut selesai.', 'sakolaan' ), 409 );
	}
}

/**
 * Handler pemasangan pertama.
 *
 * @return void
 */
function sakolaan_handle_install_demo() {
	sakolaan_demo_validate_request( 'sakolaan_install_demo', 'PASANG DATA DEMO' );

	try {
		$report = sakolaan_demo_install();
	} catch ( Throwable $error ) {
		$report            = sakolaan_demo_new_report();
		$report['status']   = 'error';
		$report['message']  = __( 'Pemasangan data demo berhenti karena error.', 'sakolaan' );
		$report['errors'][] = $error->getMessage();
	} finally {
		delete_option( 'sakolaan_demo_lock' );
	}

	sakolaan_demo_redirect_with_report( $report );
}
add_action( 'admin_post_sakolaan_install_demo', 'sakolaan_handle_install_demo' );

/**
 * Handler pemasangan ulang. Nonce terpisah agar intent tetap eksplisit.
 *
 * @return void
 */
function sakolaan_handle_reinstall_demo() {
	sakolaan_demo_validate_request( 'sakolaan_reinstall_demo', 'PASANG DATA DEMO' );

	try {
		$report = sakolaan_demo_install();
	} catch ( Throwable $error ) {
		$report            = sakolaan_demo_new_report();
		$report['status']   = 'error';
		$report['message']  = __( 'Pemasangan ulang data demo berhenti karena error.', 'sakolaan' );
		$report['errors'][] = $error->getMessage();
	} finally {
		delete_option( 'sakolaan_demo_lock' );
	}

	sakolaan_demo_redirect_with_report( $report );
}
add_action( 'admin_post_sakolaan_reinstall_demo', 'sakolaan_handle_reinstall_demo' );

/**
 * Handler penghapusan dataset demo.
 *
 * @return void
 */
function sakolaan_handle_delete_demo() {
	sakolaan_demo_validate_request( 'sakolaan_delete_demo', 'HAPUS DATA DEMO' );

	try {
		$report            = sakolaan_demo_purge( true );
		$report['message'] = empty( $report['errors'] )
			? __( 'Seluruh data Sakolaan dalam cakupan demo telah dihapus.', 'sakolaan' )
			: __( 'Data telah dihapus dengan beberapa peringatan.', 'sakolaan' );
	} catch ( Throwable $error ) {
		$report            = sakolaan_demo_new_report();
		$report['status']   = 'error';
		$report['message']  = __( 'Penghapusan data demo berhenti karena error.', 'sakolaan' );
		$report['errors'][] = $error->getMessage();
	} finally {
		delete_option( 'sakolaan_demo_lock' );
	}

	flush_rewrite_rules( false );
	sakolaan_demo_redirect_with_report( $report );
}
add_action( 'admin_post_sakolaan_delete_demo', 'sakolaan_handle_delete_demo' );

/**
 * Render ringkasan laporan operasi terakhir.
 *
 * @param array $report Laporan operasi.
 * @return void
 */
function sakolaan_render_demo_report( $report ) {
	if ( empty( $report ) || ! is_array( $report ) ) {
		return;
	}

	$notice_class = 'notice-success';

	if ( 'warning' === $report['status'] ) {
		$notice_class = 'notice-warning';
	} elseif ( 'error' === $report['status'] ) {
		$notice_class = 'notice-error';
	}
	?>
	<div class="notice sakolaan-notice <?php echo esc_attr( $notice_class ); ?> inline">
		<p><strong><?php echo esc_html( $report['message'] ); ?></strong></p>
	</div>
	<?php if ( ! empty( $report['counts'] ) ) : ?>
		<div class="sakolaan-demo-summary" aria-label="<?php esc_attr_e( 'Ringkasan hasil operasi', 'sakolaan' ); ?>">
			<?php foreach ( $report['counts'] as $label => $count ) : ?>
				<div>
					<strong><?php echo esc_html( number_format_i18n( (int) $count ) ); ?></strong>
					<span><?php echo esc_html( ucwords( str_replace( '_', ' ', $label ) ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $report['errors'] ) ) : ?>
		<h4><?php esc_html_e( 'Peringatan', 'sakolaan' ); ?></h4>
		<ul class="ul-disc">
			<?php foreach ( $report['errors'] as $error ) : ?>
				<li><?php echo esc_html( $error ); ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( ! empty( $report['links'] ) ) : ?>
		<h4><?php esc_html_e( 'Tautan pengujian', 'sakolaan' ); ?></h4>
		<p class="sakolaan-demo-links">
			<?php foreach ( $report['links'] as $link ) : ?>
				<a class="button" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
			<?php endforeach; ?>
		</p>
	<?php endif; ?>
	<?php
}

/**
 * Render satu form tindakan demo.
 *
 * @param string $action       Admin-post action.
 * @param string $nonce_action Aksi nonce.
 * @param string $confirmation Teks konfirmasi.
 * @param string $button       Label tombol.
 * @param string $class        Class tombol.
 * @param bool   $disabled     Nonaktifkan seluruh form.
 * @return void
 */
function sakolaan_render_demo_form( $action, $nonce_action, $confirmation, $button, $class = 'button-primary', $disabled = false ) {
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sakolaan-demo-form">
		<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
		<?php wp_nonce_field( $nonce_action ); ?>
		<label class="sakolaan-demo-confirm">
			<?php
			printf(
				/* translators: %s: exact confirmation phrase. */
				esc_html__( 'Ketik %s untuk melanjutkan:', 'sakolaan' ),
				'<code>' . esc_html( $confirmation ) . '</code>'
			);
			?>
			<input type="text" name="sakolaan_demo_confirmation" required autocomplete="off" pattern="<?php echo esc_attr( $confirmation ); ?>" aria-label="<?php esc_attr_e( 'Teks konfirmasi', 'sakolaan' ); ?>" <?php disabled( $disabled ); ?>>
		</label>
		<button type="submit" name="submit" class="button <?php echo esc_attr( $class ); ?>" <?php disabled( $disabled ); ?>><?php echo esc_html( $button ); ?></button>
		<p class="sakolaan-submit-status" role="status" aria-live="polite"></p>
	</form>
	<?php
}

/**
 * Render isi halaman Data Demo.
 *
 * @return void
 */
function sakolaan_render_demo_data_page() {
	$report = get_transient( sakolaan_demo_report_key() );

	if ( false !== $report ) {
		delete_transient( sakolaan_demo_report_key() );
	}

	$manifest = get_option( 'sakolaan_demo_manifest', array() );
	$installed = is_array( $manifest ) && ! empty( $manifest['version'] );
	$media_config = sakolaan_demo_media_config();
	$media_ready  = ! is_wp_error( $media_config );
	?>
	<p><?php esc_html_e( 'Dataset ini mengisi seluruh fitur Sakolaan dan Pijar Sakolaan agar archive, single, filter, galeri, pengaturan beranda, serta kondisi status dapat diuji langsung.', 'sakolaan' ); ?></p>
	<div class="notice sakolaan-notice notice-error inline">
		<p><strong><?php esc_html_e( 'Perhatian: operasi ini permanen.', 'sakolaan' ); ?></strong> <?php esc_html_e( 'Konten Sakolaan, taxonomy, halaman bawaan theme, navigasi utama, pengaturan sekolah, dan media terkait akan dihapus tanpa backup.', 'sakolaan' ); ?></p>
	</div>

	<?php sakolaan_render_demo_report( $report ); ?>

	<?php if ( $media_ready ) : ?>
		<div class="notice sakolaan-notice notice-success inline"><p>
			<?php if ( 'release' === $media_config['source'] ) : ?>
				<?php esc_html_e( 'Konfigurasi media demo dari GitHub Release siap digunakan dan checksum SHA-256 telah dipatok di paket plugin.', 'sakolaan' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Konfigurasi media demo manual dari wp-config.php siap digunakan.', 'sakolaan' ); ?>
			<?php endif; ?>
		</p></div>
	<?php else : ?>
		<div class="notice sakolaan-notice notice-warning inline"><p><strong><?php esc_html_e( 'Pemasangan demo dinonaktifkan.', 'sakolaan' ); ?></strong> <?php echo esc_html( $media_config->get_error_message() ); ?></p></div>
	<?php endif; ?>

	<?php if ( $installed ) : ?>
		<p>
			<?php
			printf(
				/* translators: 1: dataset version, 2: installation time. */
				esc_html__( 'Dataset demo versi %1$s terpasang sejak %2$s.', 'sakolaan' ),
				esc_html( $manifest['version'] ),
				esc_html( isset( $manifest['created_at'] ) ? $manifest['created_at'] : '-' )
			);
			?>
		</p>
	<?php endif; ?>

	<div class="sakolaan-demo-grid">
		<div class="sakolaan-demo-panel">
			<h4><?php echo $installed ? esc_html__( 'Pasang ulang dataset', 'sakolaan' ) : esc_html__( 'Pasang dataset demo', 'sakolaan' ); ?></h4>
			<p><?php esc_html_e( 'Membuat 8 guru/tendik, 8 artikel, 6 pengumuman, 6 agenda, 6 prestasi, 6 ekstrakurikuler, 4 galeri, 6 fasilitas, 4 program unggulan, 4 testimoni, 3 slide hero, halaman informasi, media, dan seluruh Pengaturan Sakolaan.', 'sakolaan' ); ?></p>
			<?php
			if ( $installed ) {
				sakolaan_render_demo_form( 'sakolaan_reinstall_demo', 'sakolaan_reinstall_demo', 'PASANG DATA DEMO', __( 'Pasang Ulang Demo', 'sakolaan' ), 'button-primary', ! $media_ready );
			} else {
				sakolaan_render_demo_form( 'sakolaan_install_demo', 'sakolaan_install_demo', 'PASANG DATA DEMO', __( 'Hapus Data Lama & Pasang Demo', 'sakolaan' ), 'button-primary', ! $media_ready );
			}
			?>
		</div>

		<div class="sakolaan-demo-panel sakolaan-demo-panel--danger">
			<h4><?php esc_html_e( 'Hapus data demo', 'sakolaan' ); ?></h4>
			<p><?php esc_html_e( 'Menghapus seluruh data dalam cakupan Sakolaan dan mengembalikan identitas situs ke nilai generik. Tidak ada data contoh yang dibuat kembali.', 'sakolaan' ); ?></p>
			<?php sakolaan_render_demo_form( 'sakolaan_delete_demo', 'sakolaan_delete_demo', 'HAPUS DATA DEMO', __( 'Hapus Seluruh Data Demo', 'sakolaan' ), 'button-secondary' ); ?>
		</div>
	</div>
	<?php
}
