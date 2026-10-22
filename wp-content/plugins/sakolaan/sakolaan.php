<?php
/**
 * Plugin Name:       Sakolaan
 * Plugin URI:        https://sakolaan.com/
 * Description:       Platform pengelolaan identitas, konten, PPDB, SEO, dan konfigurasi website sekolah untuk ekosistem Sakolaan.
 * Version:           0.0.1
 * Author:            Tanisya
 * Author URI:        https://tanisya.com/
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sakolaan
 * Domain Path:       /languages
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SAKOLAAN_VERSION', '0.0.1' );
define( 'SAKOLAAN_SCHEMA_VERSION', 2 );
define( 'SAKOLAAN_MIGRATION_VERSION', 1 );
define( 'SAKOLAAN_DEFAULT_THEME_SLUG', 'pijar-sakolaan' );
define( 'SAKOLAAN_DEFAULT_THEME_NAME', 'Pijar Sakolaan' );
define( 'SAKOLAAN_FILE', __FILE__ );
define( 'SAKOLAAN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SAKOLAAN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load critical notice styles even when the plugin loader is unavailable.
 *
 * @return void
 */
function sakolaan_enqueue_admin_notice_style() {
	$relative_path = 'assets/css/admin-notices.css';
	$path          = SAKOLAAN_PATH . $relative_path;
	$version       = is_file( $path ) ? (string) filemtime( $path ) : SAKOLAAN_VERSION;

	wp_enqueue_style( 'sakolaan-admin-notices', SAKOLAAN_URL . $relative_path, array( 'common' ), $version );
}
add_action( 'admin_enqueue_scripts', 'sakolaan_enqueue_admin_notice_style', 1 );

$sakolaan_loader = SAKOLAAN_PATH . 'includes/class-sakolaan.php';

if ( ! is_readable( $sakolaan_loader ) ) {
	add_action(
		'admin_notices',
		static function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				echo '<div class="notice sakolaan-notice notice-error"><p>' . esc_html__( 'Sakolaan tidak dapat dimuat karena file loader tidak tersedia. Instal ulang plugin.', 'sakolaan' ) . '</p></div>';
			}
		}
	);
	return;
}

require_once $sakolaan_loader;

/** Add a quiet, useful link to the official product website in the plugin row. */
function sakolaan_plugin_action_links( $links ) {
	$links[] = '<a href="https://sakolaan.com/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Website resmi', 'sakolaan' ) . '</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sakolaan_plugin_action_links' );

register_activation_hook( __FILE__, array( 'Sakolaan', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Sakolaan', 'deactivate' ) );

Sakolaan::run();
