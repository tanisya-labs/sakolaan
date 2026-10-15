<?php
/**
 * Panel meta di sidebar editor blok untuk setiap post type.
 *
 * Konfigurasi field diturunkan dari skema meta, sehingga tidak ada duplikasi.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Konfigurasi panel untuk JavaScript.
 *
 * @return array
 */
function sakolaan_get_editor_config() {
	$config           = array();
	$schema           = sakolaan_get_meta_schema();
	$allowed_controls = array( 'text', 'email', 'tel', 'url', 'date', 'time', 'select', 'textarea', 'media', 'media-multi' );

	if ( ! is_array( $schema ) ) {
		$schema = array();
	}

	foreach ( $schema as $post_type => $group ) {
		if ( ! is_array( $group ) || empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) {
			continue;
		}

		$fields = array();

		foreach ( $group['fields'] as $key => $field ) {
			if ( 'hero_slide' === $post_type && in_array( $key, array( 'cta_label', 'cta_url', 'cta_style', 'cta2_label', 'cta2_url', 'cta2_style' ), true ) ) {
				continue;
			}
			if ( ! is_array( $field ) || empty( $field['label'] ) || empty( $field['control'] ) || ! in_array( $field['control'], $allowed_controls, true ) ) {
				continue;
			}

			$options = array();

			if ( ! empty( $field['options'] ) ) {
				foreach ( $field['options'] as $value => $label ) {
					$options[] = array(
						'label' => is_scalar( $label ) ? (string) $label : '',
						'value' => is_scalar( $value ) ? (string) $value : '',
					);
				}
			}

			$fields[] = array(
				'key'        => '_sakolaan_' . $key,
				'label'      => (string) $field['label'],
				'control'    => $field['control'],
				'help'       => isset( $field['help'] ) && is_scalar( $field['help'] ) ? (string) $field['help'] : '',
				'options'    => $options,
				'mediaTypes' => isset( $field['media_types'] ) && is_array( $field['media_types'] ) ? array_values( array_map( 'sanitize_mime_type', $field['media_types'] ) ) : array( 'image' ),
			);
		}

		if ( empty( $fields ) ) {
			continue;
		}

		$config[ sanitize_key( $post_type ) ] = array(
			'title'  => isset( $group['title'] ) && is_scalar( $group['title'] ) ? (string) $group['title'] : __( 'Data sekolah', 'sakolaan' ),
			'fields' => $fields,
		);
	}

	$config['i18n'] = array(
		'select'  => __( 'Pilih', 'sakolaan' ),
		'replace' => __( 'Ganti', 'sakolaan' ),
		'remove'  => __( 'Hapus', 'sakolaan' ),
		'add'     => __( 'Tambah gambar', 'sakolaan' ),
		'edit'    => __( 'Ubah gambar', 'sakolaan' ),
		'noFile'  => __( 'Belum ada berkas.', 'sakolaan' ),
		'selected' => __( 'Media dipilih.', 'sakolaan' ),
		'removed'  => __( 'Media dihapus.', 'sakolaan' ),
		'galleryUpdated' => __( 'Pilihan gambar diperbarui.', 'sakolaan' ),
	);

	return $config;
}

/**
 * Muat panel hanya di editor post type yang punya meta.
 *
 * @return void
 */
function sakolaan_enqueue_editor_panels() {
	$screen = get_current_screen();

	$has_sakolaan_fields = $screen && isset( sakolaan_get_meta_schema()[ $screen->post_type ] );
	$has_seo_fields    = $screen && function_exists( 'sakolaan_seo_post_types' ) && in_array( $screen->post_type, sakolaan_seo_post_types(), true );

	if ( ! $has_sakolaan_fields && ! $has_seo_fields ) {
		return;
	}

	sakolaan_enqueue_admin_style();

	wp_enqueue_script(
		'sakolaan-editor-panels',
		SAKOLAAN_URL . 'assets/js/editor-panels.js',
		array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-block-editor', 'wp-core-data' ),
		sakolaan_admin_asset_version( 'assets/js/editor-panels.js' ),
		true
	);

	$config_json = wp_json_encode( sakolaan_get_editor_config(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

	wp_add_inline_script(
		'sakolaan-editor-panels',
		'window.sakolaanMeta = ' . ( false === $config_json ? '{}' : $config_json ) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'sakolaan_enqueue_editor_panels' );
