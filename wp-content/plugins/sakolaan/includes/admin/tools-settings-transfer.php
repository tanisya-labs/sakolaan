<?php
/** Sakolaan admin: tools-settings-transfer. @package Sakolaan */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Convert image IDs to portable URLs while preserving all other settings. */
function sakolaan_settings_for_export() {
	$settings = sakolaan_get_settings();
	foreach ( sakolaan_get_settings_schema() as $group => $definition ) {
		foreach ( $definition['fields'] as $key => $field ) {
			if ( 'image' === $field['type'] ) {
				$id = absint( $settings[ $group ][ $key ] ?? 0 );
				$settings[ $group ][ $key ] = $id ? (string) wp_get_attachment_url( $id ) : '';
			}
			if ( 'quick_links' === $field['type'] && is_array( $settings[ $group ][ $key ] ?? null ) ) {
				foreach ( $settings[ $group ][ $key ] as &$link ) {
					if ( is_array( $link ) && ! empty( $link['icon_id'] ) ) {
						$link['icon_url'] = (string) wp_get_attachment_url( (int) $link['icon_id'] );
						unset( $link['icon_id'] );
					}
				}
				unset( $link );
			}
		}
	}
	return $settings;
}

function sakolaan_export_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'sakolaan_export_settings' );
	$payload = array( 'format' => 'sakolaan-settings', 'version' => 1, 'settings' => sakolaan_settings_for_export() );
	$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	if ( false === $json ) {
		wp_die( esc_html__( 'Pengaturan tidak dapat dikodekan menjadi JSON.', 'sakolaan' ), '', array( 'response' => 500 ) );
	}
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="sakolaan-settings-' . gmdate( 'Y-m-d' ) . '.json"' );
	echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
	exit;
}
add_action( 'admin_post_sakolaan_export_settings', 'sakolaan_export_settings' );

/** @return array|WP_Error */
function sakolaan_parse_settings_import( $json ) {
	$payload = json_decode( $json, true );
	$payload = sakolaan_migration_normalize_backup_payload( $payload, 'settings' );
	if ( ! is_array( $payload ) || 'sakolaan-settings' !== ( $payload['format'] ?? '' ) || 1 !== ( $payload['version'] ?? null ) || ! is_array( $payload['settings'] ?? null ) ) {
		return new WP_Error( 'invalid_format', __( 'Format JSON Sakolaan tidak valid.', 'sakolaan' ) );
	}
	$schema = sakolaan_get_settings_schema();
	$settings = sakolaan_get_settings_defaults();
	$warnings = array();
	foreach ( $payload['settings'] as $group => $fields ) {
		if ( ! isset( $schema[ $group ] ) || ! is_array( $fields ) ) {
			return new WP_Error( 'unknown_group', __( 'JSON berisi grup pengaturan yang tidak dikenal.', 'sakolaan' ) );
		}
		foreach ( $fields as $key => $value ) {
			if ( ! isset( $schema[ $group ]['fields'][ $key ] ) ) {
				/* translators: %s: unknown settings key. */
				return new WP_Error( 'unknown_field', sprintf( __( 'Pengaturan tidak dikenal: %s.', 'sakolaan' ), $group . '.' . $key ) );
			}
			$field = $schema[ $group ]['fields'][ $key ];
			if ( 'quick_links' === $field['type'] && false === $value ) {
				$settings[ $group ][ $key ] = false;
				continue;
			}
			if ( 'image' === $field['type'] ) {
				$url = is_string( $value ) ? esc_url_raw( $value ) : '';
				$id = $url ? attachment_url_to_postid( $url ) : 0;
				if ( $url && ! $id ) {
				/* translators: %s: image settings key. */
					$warnings[] = sprintf( __( 'Media %s tidak ditemukan secara lokal; field dikosongkan.', 'sakolaan' ), $group . '.' . $key );
				}
				$value = $id;
			}
			if ( 'quick_links' === $field['type'] && is_array( $value ) ) {
				foreach ( $value as &$link ) {
					if ( ! is_array( $link ) ) {
						continue;
					}
					$icon_url = isset( $link['icon_url'] ) && is_string( $link['icon_url'] ) ? esc_url_raw( $link['icon_url'] ) : '';
					$link['icon_id'] = $icon_url ? attachment_url_to_postid( $icon_url ) : 0;
					if ( $icon_url && ! $link['icon_id'] ) {
						$warnings[] = __( 'Salah satu ikon akses cepat tidak ditemukan secara lokal.', 'sakolaan' );
					}
					unset( $link['icon_url'] );
				}
				unset( $link );
			}
			$settings[ $group ][ $key ] = sakolaan_sanitize_field( $value, $field, $group . '_' . $key );
		}
	}
	return array( 'settings' => $settings, 'warnings' => $warnings );
}

function sakolaan_preview_settings_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'sakolaan_preview_settings' );
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Upload fields are normalized and validated individually below.
	$file = isset( $_FILES['settings_file'] ) && is_array( $_FILES['settings_file'] ) ? wp_unslash( $_FILES['settings_file'] ) : null;
	$tmp_name = is_array( $file ) && isset( $file['tmp_name'] ) && is_string( $file['tmp_name'] ) ? sanitize_text_field( $file['tmp_name'] ) : '';
	$file_name = is_array( $file ) && isset( $file['name'] ) && is_string( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '';
	$file_error = is_array( $file ) && isset( $file['error'] ) ? absint( $file['error'] ) : -1;
	$file_size = is_array( $file ) && isset( $file['size'] ) ? absint( $file['size'] ) : 0;
	if ( UPLOAD_ERR_OK !== $file_error || $file_size > 1048576 || 'json' !== strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) ) || ! $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
		wp_die( esc_html__( 'File JSON tidak valid atau terlalu besar.', 'sakolaan' ), '', array( 'response' => 400 ) );
	}
	$parsed = sakolaan_parse_settings_import( (string) file_get_contents( $tmp_name ) );
	if ( is_wp_error( $parsed ) ) {
		wp_die( esc_html( $parsed->get_error_message() ), '', array( 'response' => 400 ) );
	}
	set_transient( 'sakolaan_settings_preview_' . get_current_user_id(), $parsed, 15 * MINUTE_IN_SECONDS );
	wp_safe_redirect( sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'settings-backup', 'preview' => 'settings' ) ) );
	exit;
}
add_action( 'admin_post_sakolaan_preview_settings', 'sakolaan_preview_settings_import' );

function sakolaan_render_settings_preview() {
	$preview = get_transient( 'sakolaan_settings_preview_' . get_current_user_id() );
	if ( ! is_array( $preview ) ) {
		return;
	}
	$old = sakolaan_get_settings();
	$changes = array();
	foreach ( $preview['settings'] as $group => $fields ) {
		foreach ( $fields as $key => $value ) {
			if ( ( $old[ $group ][ $key ] ?? null ) !== $value ) {
				$changes[] = $group . '.' . $key;
			}
		}
	}
	/* translators: %d: number of settings that will change. */
	echo '<div class="sakolaan-import-preview"><h4>' . esc_html__( 'Pratinjau perubahan', 'sakolaan' ) . '</h4><p>' . esc_html( sprintf( __( '%d pengaturan akan berubah.', 'sakolaan' ), count( $changes ) ) ) . '</p>';
	if ( $changes ) {
		echo '<p>' . esc_html( implode( ', ', $changes ) ) . '</p>';
	}
	foreach ( $preview['warnings'] as $warning ) {
		echo '<p class="notice sakolaan-notice notice-warning">' . esc_html( $warning ) . '</p>';
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_apply_settings">';
	wp_nonce_field( 'sakolaan_apply_settings' );
	submit_button( __( 'Terapkan impor', 'sakolaan' ), 'primary', 'submit', false );
	echo '</form></div>';
}

function sakolaan_apply_settings_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'sakolaan_apply_settings' );
	$preview = get_transient( 'sakolaan_settings_preview_' . get_current_user_id() );
	if ( ! is_array( $preview ) || ! is_array( $preview['settings'] ?? null ) ) {
		wp_die( esc_html__( 'Pratinjau impor telah kedaluwarsa.', 'sakolaan' ), '', array( 'response' => 409 ) );
	}
	update_option( 'sakolaan_settings', $preview['settings'], false );
	sakolaan_clear_settings_cache();
	delete_transient( 'sakolaan_settings_preview_' . get_current_user_id() );
	wp_safe_redirect( sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'settings-backup', 'updated' => 'settings' ) ) );
	exit;
}
add_action( 'admin_post_sakolaan_apply_settings', 'sakolaan_apply_settings_import' );

