<?php
/**
 * Plugin Name:       Sakolaan Admission
 * Plugin URI:        https://sakolaan.com/
 * Description:       Add-on pengelolaan penerimaan untuk Sakolaan Core.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Requires Plugins:  sakolaan
 * Author:            Tanisya
 * License:           GPL-2.0-or-later
 * Text Domain:       sakolaan-admission
 * Domain Path:       /languages
 *
 * @package Sakolaan_Admission
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SAKOLAAN_ADMISSION_VERSION', '0.1.0' );
define( 'SAKOLAAN_ADMISSION_SCHEMA_VERSION', 1 );
define( 'SAKOLAAN_ADMISSION_MIN_CORE_VERSION', '0.0.1' );
define( 'SAKOLAAN_ADMISSION_FILE', __FILE__ );
define( 'SAKOLAAN_ADMISSION_PATH', plugin_dir_path( __FILE__ ) );
define( 'SAKOLAAN_ADMISSION_URL', plugin_dir_url( __FILE__ ) );

require_once SAKOLAAN_ADMISSION_PATH . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'Sakolaan\\Admission\\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Sakolaan\\Admission\\Plugin', 'deactivate' ) );

Sakolaan\Admission\Plugin::boot();
