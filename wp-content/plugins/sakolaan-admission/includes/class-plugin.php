<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	private const MODULES = array(
		'class-installer.php',
		'class-access.php',
		'class-repository.php',
		'class-labels.php',
		'class-availability.php',
		'class-configuration.php',
		'class-mail-diagnostics.php',
		'class-forms.php',
		'class-workflow.php',
		'class-documents.php',
		'class-admin.php',
		'class-frontend.php',
		'class-rest.php',
		'class-privacy.php',
	);

	private static bool $ready = false;

	public static function core_status(): array {
		$core_file = WP_PLUGIN_DIR . '/sakolaan/sakolaan.php';
		$installed = is_file( $core_file ) && is_readable( $core_file );
		$headers   = $installed ? get_file_data(
			$core_file,
			array(
				'name'   => 'Plugin Name',
				'domain' => 'Text Domain',
			),
			'plugin'
		) : array();
		$identity  = $installed && 'Sakolaan' === ( $headers['name'] ?? '' ) && 'sakolaan' === ( $headers['domain'] ?? '' );
		$active    = defined( 'SAKOLAAN_VERSION' );
		$ready     = $active && class_exists( '\\Sakolaan' ) && method_exists( '\\Sakolaan', 'is_ready' ) && \Sakolaan::is_ready();
		$version   = $active ? (string) SAKOLAAN_VERSION : '';
		return array(
			'installed'  => $installed,
			'identity'   => $identity,
			'active'     => $active,
			'ready'      => $ready,
			'version'    => $version,
			'compatible' => $identity && $ready && version_compare( $version, SAKOLAAN_ADMISSION_MIN_CORE_VERSION, '>=' ),
		);
	}

	public static function boot(): void {
		add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'load' ), 20 );
	}

	public static function load(): void {
		$status = self::core_status();
		if ( ! $status['compatible'] ) {
			return;
		}

		load_plugin_textdomain( 'sakolaan-admission', false, dirname( plugin_basename( SAKOLAAN_ADMISSION_FILE ) ) . '/languages' );
		foreach ( self::MODULES as $module ) {
			$path = SAKOLAAN_ADMISSION_PATH . 'includes/' . $module;
			if ( ! is_readable( $path ) ) {
				add_action(
					'admin_notices',
					static function () use ( $module ) {
						/* translators: %s: missing module filename. */
						printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( sprintf( __( 'Modul Sakolaan Admission tidak ditemukan: %s', 'sakolaan-admission' ), $module ) ) );
					}
				);
				return;
			}
			require_once $path;
		}

		self::$ready = true;
		Installer::maybe_upgrade();
		Access::register();
		Admin::register();
		Frontend::register();
		Rest::register();
		Privacy::register();
		Documents::register();
	}

	public static function is_ready(): bool {
		return self::$ready;
	}

	public static function dependency_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$status = self::core_status();
		if ( $status['compatible'] ) {
			return;
		}
		if ( ! $status['installed'] ) {
			$message = __( 'Sakolaan Admission tidak aktif karena Sakolaan Core belum dipasang.', 'sakolaan-admission' );
		} elseif ( ! $status['identity'] ) {
			$message = __( 'Sakolaan Admission menemukan berkas plugin Sakolaan Core yang tidak valid.', 'sakolaan-admission' );
		} elseif ( ! $status['active'] ) {
			$message = __( 'Sakolaan Admission tidak aktif karena Sakolaan Core belum diaktifkan.', 'sakolaan-admission' );
		} elseif ( ! $status['ready'] ) {
			$message = __( 'Sakolaan Admission tidak aktif karena Sakolaan Core gagal dimuat sepenuhnya.', 'sakolaan-admission' );
		} else {
			/* translators: %s: minimum Sakolaan Core version. */
			$message = sprintf( __( 'Sakolaan Admission memerlukan Sakolaan Core versi %s atau lebih baru.', 'sakolaan-admission' ), SAKOLAAN_ADMISSION_MIN_CORE_VERSION );
		}
		printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
	}

	public static function activate(): void {
		$status = self::core_status();
		if ( ! $status['compatible'] ) {
			deactivate_plugins( plugin_basename( SAKOLAAN_ADMISSION_FILE ) );
			wp_die( esc_html__( 'Sakolaan Admission memerlukan Sakolaan Core yang aktif dan kompatibel.', 'sakolaan-admission' ) );
		}
		require_once SAKOLAAN_ADMISSION_PATH . 'includes/class-installer.php';
		Installer::install();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'sakolaan_admission_notification_worker' );
		flush_rewrite_rules();
	}
}
