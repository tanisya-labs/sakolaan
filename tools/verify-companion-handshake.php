<?php
/**
 * Verify the Sakolaan/Pijar Sakolaan compatibility matrix without WordPress.
 *
 * @package Sakolaan
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$root = dirname( __DIR__ );

if ( '--child' === ( $argv[1] ?? '' ) ) {
	$side     = $argv[2] ?? '';
	$scenario = $argv[3] ?? '';

	define( 'ABSPATH', $root . '/' );

	class WP_Error {
		private $code;

		public function __construct( $code ) {
			$this->code = $code;
		}

		public function get_error_code() {
			return $this->code;
		}
	}

	class Sakolaan_Handshake_Test_Theme {
		private $exists;
		private $version;

		public function __construct( $exists, $version ) {
			$this->exists  = $exists;
			$this->version = $version;
		}

		public function exists() {
			return $this->exists;
		}

		public function errors() {
			return false;
		}

		public function get( $field ) {
			return 'Version' === $field ? $this->version : '';
		}
	}

	function __( $message ) {
		return $message;
	}

	function add_action() {}

	function is_wp_error( $value ) {
		return $value instanceof WP_Error;
	}

	function plugin_basename( $file ) {
		return 'sakolaan/sakolaan.php';
	}

	if ( 'plugin' === $side ) {
		$GLOBALS['handshake_template'] = 'theme_inactive' === $scenario ? 'twentytwentyfive' : 'pijar-sakolaan';
		$installed = 'theme_missing' !== $scenario;
		$version   = 'version_mismatch' === $scenario ? '0.0.2' : '0.0.1';
		$GLOBALS['handshake_theme'] = new Sakolaan_Handshake_Test_Theme( $installed, $version );

		define( 'SAKOLAAN_VERSION', '0.0.1' );
		define( 'SAKOLAAN_DEFAULT_THEME_SLUG', 'pijar-sakolaan' );
		define( 'PIJAR_SAKOLAAN_VERSION', '0.0.1' );
		define( 'PIJAR_SAKOLAAN_SLUG', 'pijar-sakolaan' );

		function wp_get_theme() {
			return $GLOBALS['handshake_theme'];
		}

		function get_template() {
			return $GLOBALS['handshake_template'];
		}

		function pijar_sakolaan_dependency_status() {
			return array( 'compatible' => true, 'plugin_ready' => true );
		}

		function pijar_sakolaan_is_compatible() {
			return true;
		}

		function pijar_sakolaan_get_setting() {}

		if ( 'theme_incomplete' !== $scenario ) {
			function pijar_sakolaan_get_page_tree() {}
			function pijar_sakolaan_get_archive_configs() {}
			function pijar_sakolaan_get_archive_sort_options() {}
			function pijar_sakolaan_get_archive_status_options() {}
			function pijar_sakolaan_is_manually_ordered_post_type() {}
			function pijar_sakolaan_get_breadcrumb_items() {}
			function pijar_sakolaan_get_breadcrumb_schema() {}
			function pijar_sakolaan_get_meta_value() {}
			function pijar_sakolaan_get_raw_meta() {}
			function pijar_sakolaan_render_homepage_sections() {}
			function pijar_sakolaan_enqueue_gallery_script() {}
			function pijar_sakolaan_get_meta_labels() {}
			function pijar_sakolaan_render_meta_value() {}
			function pijar_sakolaan_get_archive_url() {}
		}

		require $root . '/wp-content/plugins/sakolaan/includes/compatibility.php';
		$status = sakolaan_get_companion_theme_status();
	} elseif ( 'theme' === $side ) {
		$GLOBALS['handshake_template'] = 'theme_identity_invalid' === $scenario ? 'metadata-copy' : 'pijar-sakolaan';
		$header_version = 'theme_version_invalid' === $scenario ? '0.0.2' : '0.0.1';
		$GLOBALS['handshake_theme'] = new Sakolaan_Handshake_Test_Theme( true, $header_version );
		$GLOBALS['handshake_ready'] = 'plugin_incomplete' !== $scenario;

		define( 'PIJAR_SAKOLAAN_VERSION', '0.0.1' );
		define( 'PIJAR_SAKOLAAN_SLUG', 'pijar-sakolaan' );
		define( 'PIJAR_SAKOLAAN_PLUGIN_BASENAME', 'sakolaan/sakolaan.php' );
		define( 'WP_PLUGIN_DIR', 'plugin_missing' === $scenario ? $root . '/missing-plugins' : $root . '/wp-content/plugins' );

		if ( ! in_array( $scenario, array( 'plugin_missing', 'plugin_inactive' ), true ) ) {
			define( 'SAKOLAAN_VERSION', 'version_mismatch' === $scenario ? '0.0.2' : '0.0.1' );
			define( 'SAKOLAAN_FILE', $root . '/wp-content/plugins/sakolaan/sakolaan.php' );
		}

		function get_template() {
			return $GLOBALS['handshake_template'];
		}

		function wp_get_theme() {
			return $GLOBALS['handshake_theme'];
		}

		class Sakolaan {
			public static function is_ready() {
				return $GLOBALS['handshake_ready'];
			}
		}

		function sakolaan_validate_companion_version( $version ) {
			return defined( 'SAKOLAAN_VERSION' ) && SAKOLAAN_VERSION === $version
				? true
				: new WP_Error( 'sakolaan_version_mismatch' );
		}

		if ( 'plugin_incomplete' !== $scenario ) {
			function sakolaan_get_setting() {}
		}

		require $root . '/wp-content/themes/pijar-sakolaan/inc/compat.php';
		$status = pijar_sakolaan_dependency_status();
	} else {
		exit( 2 );
	}

	echo json_encode( $status, JSON_UNESCAPED_SLASHES );
	exit( 0 );
}

$cases = array(
	array( 'plugin', 'healthy', 'compatible', true, 'runtime_ready' ),
	array( 'plugin', 'child_theme', 'compatible', true, 'runtime_ready' ),
	array( 'plugin', 'theme_missing', 'theme_missing', false, 'runtime_ready' ),
	array( 'plugin', 'theme_inactive', 'theme_inactive', false, 'runtime_ready' ),
	array( 'plugin', 'theme_incomplete', 'theme_incomplete', false, 'runtime_ready' ),
	array( 'plugin', 'version_mismatch', 'sakolaan_version_mismatch', false, 'runtime_ready' ),
	array( 'theme', 'healthy', 'compatible', true, 'plugin_ready' ),
	array( 'theme', 'child_theme', 'compatible', true, 'plugin_ready' ),
	array( 'theme', 'plugin_missing', 'plugin_missing', false, 'plugin_ready' ),
	array( 'theme', 'plugin_inactive', 'plugin_inactive', false, 'plugin_ready' ),
	array( 'theme', 'plugin_incomplete', 'plugin_incomplete', false, 'plugin_ready' ),
	array( 'theme', 'version_mismatch', 'sakolaan_version_mismatch', true, 'plugin_ready' ),
	array( 'theme', 'theme_identity_invalid', 'theme_identity_invalid', true, 'plugin_ready' ),
	array( 'theme', 'theme_version_invalid', 'theme_version_invalid', true, 'plugin_ready' ),
);

$failed = array();

foreach ( $cases as $case ) {
	list( $side, $scenario, $expected_code, $expected_ready, $ready_key ) = $case;
	$command = array( PHP_BINARY, __FILE__, '--child', $side, $scenario );
	$pipes   = array();
	$process = proc_open(
		$command,
		array(
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		),
		$pipes,
		$root
	);

	if ( ! is_resource( $process ) ) {
		$failed[] = "$side/$scenario: process could not start";
		continue;
	}

	$output = stream_get_contents( $pipes[1] );
	$error  = stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	$exit_code = proc_close( $process );
	$status    = json_decode( $output, true );

	if ( 0 !== $exit_code || ! is_array( $status ) ) {
		$failed[] = "$side/$scenario: invalid child result $error";
		continue;
	}

	$actual_ready = ! empty( $status[ $ready_key ] );
	if ( $expected_code !== $status['code'] || $expected_ready !== $actual_ready ) {
		$failed[] = sprintf(
			'%s/%s: expected %s (%s=true: %s), received %s',
			$side,
			$scenario,
			$expected_code,
			$ready_key,
			$expected_ready ? 'yes' : 'no',
			$output
		);
		continue;
	}

	echo "PASS $side/$scenario: {$status['code']}\n";
}

if ( $failed ) {
	fwrite( STDERR, implode( "\n", $failed ) . "\n" );
	exit( 1 );
}

echo 'Companion handshake matrix passed: ' . count( $cases ) . " cases.\n";
