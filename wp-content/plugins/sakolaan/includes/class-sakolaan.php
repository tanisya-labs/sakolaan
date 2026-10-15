<?php
/**
 * Loader utama Sakolaan.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Memuat modul dan mendaftarkan hook.
 */
final class Sakolaan {
	/**
	 * Error saat memuat modul.
	 *
	 * @var WP_Error|null
	 */
	private static $load_error = null;

	/**
	 * Error saat registrasi tipe konten pada init.
	 *
	 * @var WP_Error|null
	 */
	private static $registration_error = null;

	/**
	 * Modul yang dimuat, berurutan.
	 *
	 * @var string[]
	 */
	private static $files = array(
		'compatibility.php',
		'migration.php',
		'helpers/labels.php',
		'registry.php',
		'helpers/dates.php',
		'helpers/queries.php',
		'meta/sanitizers.php',
		'meta/schema.php',
		'meta/register.php',
		'taxonomies/news.php',
		'taxonomies/default-terms.php',
		'post-types/news.php',
		'settings/schema.php',
		'settings/defaults.php',
		'settings/sanitize.php',
		'helpers/get-setting.php',
		'settings/sync.php',
		'blocks.php',
		'admin/assets.php',
		'settings/page.php',
		'seo.php',
		'demo-media.php',
		'demo-data.php',
		'admin/demo-data-page.php',
		'admin/news-migration.php',
		'admin/menu.php',
		'admin/editor-panels.php',
		'admin/hero-slide-cta.php',
		'admin/columns.php',
		'admin/content-order.php',
		'admin/tools-health.php',
		'admin/tools-settings-transfer.php',
		'admin/tools-data-transfer.php',
		'admin/tools-legacy.php',
		'admin/tools-workflow.php',
		'admin/tools.php',
	);

	/**
	 * Hapus cache settings setelah diupdate.
	 *
	 * @return void
	 */
	public static function clear_settings_cache() {
		sakolaan_clear_settings_cache();
	}

	/**
	 * Muat file dan pasang hook.
	 *
	 * @return void
	 */
	public static function run() {
		$loaded = self::load_files();

		if ( is_wp_error( $loaded ) ) {
			self::$load_error = $loaded;
			add_action( 'admin_notices', array( __CLASS__, 'render_load_error' ) );
			return;
		}

		add_action( 'init', array( __CLASS__, 'register_content_types_on_init' ), 5 );
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ), 25 );
		add_action( 'update_option_sakolaan_settings', array( __CLASS__, 'clear_settings_cache' ) );
		add_action( 'add_option_sakolaan_settings', array( __CLASS__, 'clear_settings_cache' ) );
	}

	/**
	 * Muat semua modul yang terdaftar.
	 *
	 * @return true|WP_Error
	 */
	private static function load_files() {
		foreach ( self::$files as $file ) {
			$path = SAKOLAAN_PATH . 'includes/' . $file;

			if ( ! is_readable( $path ) ) {
				return new WP_Error(
					'sakolaan_missing_module',
					sprintf(
						/* translators: %s: relative module path. */
						__( 'Modul Sakolaan tidak ditemukan: %s', 'sakolaan' ),
						$file
					)
				);
			}

			require_once $path;
		}

		return true;
	}

	/**
	 * Tampilkan kegagalan loader kepada administrator.
	 *
	 * @return void
	 */
	public static function render_load_error() {
		if ( ! current_user_can( 'activate_plugins' ) || ! is_wp_error( self::$load_error ) ) {
			return;
		}

		printf(
			'<div class="notice sakolaan-notice notice-error"><p>%s</p></div>',
			esc_html( self::$load_error->get_error_message() )
		);
	}

	/**
	 * Registrasi tipe konten untuk hook init dan laporkan kegagalannya.
	 *
	 * @return void
	 */
	public static function register_content_types_on_init() {
		$result = self::register_content_types();

		if ( is_wp_error( $result ) ) {
			self::$registration_error = $result;
			add_action( 'admin_notices', array( __CLASS__, 'render_registration_error' ) );
		}
	}

	/**
	 * Tampilkan kegagalan registrasi kepada administrator.
	 *
	 * @return void
	 */
	public static function render_registration_error() {
		if ( ! current_user_can( 'activate_plugins' ) || ! is_wp_error( self::$registration_error ) ) {
			return;
		}

		printf(
			'<div class="notice sakolaan-notice notice-error"><p>%s</p></div>',
			esc_html( self::$registration_error->get_error_message() )
		);
	}

	/**
	 * Daftarkan taxonomy, post type, lalu meta (urutan penting).
	 *
	 * @return true|WP_Error
	 */
	public static function register_content_types() {
		try {
			if ( ! get_option( 'sakolaan_legacy_news_purged', false ) ) {
				sakolaan_register_news_taxonomies();
			}
			// Retain the empty /berita/ archive route; Sakolaan Theme queries native Posts there.
			sakolaan_register_news_post_type();
			sakolaan_register_registry_content();

			sakolaan_register_meta();
		} catch ( Throwable $error ) {
			return new WP_Error( 'sakolaan_registration_failed', $error->getMessage() );
		}

		$taxonomies = array_keys( sakolaan_taxonomy_registry() );
		$post_types = array_values( array_diff( array_keys( sakolaan_content_registry() ), array( 'post' ) ) );
		$post_types[] = 'berita';
		if ( ! get_option( 'sakolaan_legacy_news_purged', false ) ) {
			$taxonomies = array_merge( $taxonomies, array( 'kategori_berita', 'tag_berita' ) );
		}

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				return new WP_Error( 'sakolaan_taxonomy_missing', sprintf( /* translators: %s: taxonomy slug. */ __( 'Taxonomy gagal didaftarkan: %s', 'sakolaan' ), $taxonomy ) );
			}
		}

		foreach ( $post_types as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				return new WP_Error( 'sakolaan_post_type_missing', sprintf( /* translators: %s: post type slug. */ __( 'Post type gagal didaftarkan: %s', 'sakolaan' ), $post_type ) );
			}
		}

		return true;
	}

	/**
	 * Aktivasi: daftarkan tipe konten, buat term bawaan, perbarui permalink.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( is_wp_error( self::$load_error ) ) {
			wp_die( esc_html( self::$load_error->get_error_message() ) );
		}

		$migration = sakolaan_run_legacy_migration();
		if ( is_wp_error( $migration ) ) {
			update_option( 'sakolaan_migration_status', 'failed', false );
			update_option( 'sakolaan_migration_error', $migration->get_error_message(), false );
		}

		$registered = self::register_content_types();

		if ( is_wp_error( $registered ) ) {
			wp_die( esc_html( $registered->get_error_message() ) );
		}

		$terms_created = sakolaan_create_default_terms();

		if ( is_wp_error( $terms_created ) ) {
			wp_die( esc_html( $terms_created->get_error_message() ) );
		}

		flush_rewrite_rules();
		update_option( 'sakolaan_rewrite_signature', self::rewrite_signature(), false );
		update_option( 'sakolaan_schema_version', SAKOLAAN_SCHEMA_VERSION, false );
		update_option( 'sakolaan_version', SAKOLAAN_VERSION, false );
	}

	/**
	 * Deaktivasi: perbarui permalink. Data tidak dihapus.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Signature struktur URL publik untuk flush rewrite yang tepat waktu.
	 *
	 * @return string
	 */
	private static function rewrite_signature() {
		$routes = array();
		foreach ( sakolaan_content_registry() as $slug => $item ) {
			if ( 'post' !== $slug && empty( $item['private'] ) ) {
				$routes[ $slug ] = ! empty( $item['rewrite'] ) ? $item['rewrite'] : apply_filters( 'sakolaan_post_type_slug', $slug, $slug );
			}
		}
		foreach ( sakolaan_taxonomy_registry() as $slug => $item ) {
			$routes[ $slug ] = $item['rewrite'];
		}
		$routes['berita_archive'] = 'berita';
		$routes['legacy_purged'] = (bool) get_option( 'sakolaan_legacy_news_purged', false );
		if ( ! $routes['legacy_purged'] ) {
			$routes['kategori_berita'] = 'berita/kategori';
			$routes['tag_berita'] = 'berita/tag';
		}
		return hash( 'sha256', wp_json_encode( $routes ) );
	}

	/** Jalankan migrasi versi dan perbarui rewrite hanya bila strukturnya berubah. */
	public static function maybe_upgrade() {
		$schema_changed = (int) get_option( 'sakolaan_schema_version', 0 ) < SAKOLAAN_SCHEMA_VERSION;
		if ( $schema_changed && function_exists( 'sakolaan_migrate_content_order' ) ) {
			sakolaan_migrate_content_order();
			update_option( 'sakolaan_schema_version', SAKOLAAN_SCHEMA_VERSION, false );
		}
		$signature = self::rewrite_signature();
		if ( $signature !== get_option( 'sakolaan_rewrite_signature' ) || ! get_option( 'rewrite_rules' ) ) {
			flush_rewrite_rules( false );
			update_option( 'sakolaan_rewrite_signature', $signature, false );
		}
		if ( SAKOLAAN_VERSION !== get_option( 'sakolaan_version' ) ) {
			update_option( 'sakolaan_version', SAKOLAAN_VERSION, false );
		}
	}
}
