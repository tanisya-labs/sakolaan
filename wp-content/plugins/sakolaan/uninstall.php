<?php
/**
 * Uninstall Sakolaan.
 *
 * Konten (guru, berita, dan seterusnya) TIDAK pernah dihapus. Pengaturan sekolah
 * hanya dihapus jika administrator mencentang opsi di tab "Lanjutan".
 *
 * @package Sakolaan
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$sakolaan_settings = get_option( 'sakolaan_settings', array() );

if ( ! empty( $sakolaan_settings['advanced']['delete_data'] ) ) {
	delete_option( 'sakolaan_settings' );
	delete_option( 'sakolaan_version' );
	delete_option( 'sakolaan_schema_version' );
	delete_option( 'sakolaan_rewrite_signature' );
	delete_option( 'sakolaan_content_order_migrated' );

	$sakolaan_uploads   = wp_get_upload_dir();
	$sakolaan_cache_dir = wp_normalize_path( trailingslashit( $sakolaan_uploads['basedir'] ) . 'sakolaan-demo-cache' );
	$sakolaan_base_dir  = wp_normalize_path( trailingslashit( $sakolaan_uploads['basedir'] ) );
	if ( empty( $sakolaan_uploads['error'] ) && is_dir( $sakolaan_cache_dir ) && 0 === strpos( trailingslashit( $sakolaan_cache_dir ), $sakolaan_base_dir ) ) {
		$sakolaan_iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $sakolaan_cache_dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $sakolaan_iterator as $sakolaan_item ) {
			if ( $sakolaan_item->isDir() ) {
				rmdir( $sakolaan_item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Cache plugin tervalidasi.
			} else {
				wp_delete_file( $sakolaan_item->getPathname() );
			}
		}
		rmdir( $sakolaan_cache_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Cache plugin tervalidasi.
	}
}
