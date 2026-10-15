<?php
/**
 * Sinkronisasi identitas sekolah ke pengaturan inti WordPress.
 *
 * Block core (site title, site logo, tagline, favicon) membaca option WordPress,
 * jadi nilai dari Pengaturan Sakolaan disalin ke sana. Nilai kosong tidak menimpa apa pun.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Salin identitas ke option WordPress.
 *
 * @param mixed $settings Nilai option sakolaan_settings yang baru.
 * @return void
 */
function sakolaan_sync_site_identity( $settings ) {
	if ( ! is_array( $settings ) || empty( $settings['identity'] ) ) {
		return;
	}

	$identity = $settings['identity'];

	if ( ! empty( $identity['name'] ) ) {
		update_option( 'blogname', $identity['name'] );
	}

	if ( ! empty( $identity['tagline'] ) ) {
		update_option( 'blogdescription', $identity['tagline'] );
	}

	if ( ! empty( $identity['logo'] ) ) {
		update_option( 'site_logo', absint( $identity['logo'] ) );
	}

	if ( ! empty( $identity['favicon'] ) ) {
		update_option( 'site_icon', absint( $identity['favicon'] ) );
	}
}

/**
 * Hook untuk update option yang sudah ada.
 *
 * @param mixed $old_value Nilai lama.
 * @param mixed $value     Nilai baru.
 * @return void
 */
function sakolaan_on_settings_updated( $old_value, $value ) {
	unset( $old_value );
	sakolaan_sync_site_identity( $value );
}
add_action( 'update_option_sakolaan_settings', 'sakolaan_on_settings_updated', 10, 2 );

/**
 * Hook untuk penyimpanan pertama.
 *
 * @param string $option Nama option.
 * @param mixed  $value  Nilai baru.
 * @return void
 */
function sakolaan_on_settings_added( $option, $value ) {
	unset( $option );
	sakolaan_sync_site_identity( $value );
}
add_action( 'add_option_sakolaan_settings', 'sakolaan_on_settings_added', 10, 2 );
