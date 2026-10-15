<?php
/** Sakolaan admin: secondary tools. @package Sakolaan */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function sakolaan_render_more_page() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) ); }
	$children = sakolaan_admin_navigation_node( 'more' )['children'];
	$invalid = false;
	$panel = sakolaan_admin_route_value( 'panel', $children, $invalid );
	if ( ! $panel ) {
		sakolaan_render_page_header( 'more' );
		if ( $invalid ) { sakolaan_render_invalid_route_notice(); }
		sakolaan_render_navigation_cards( 'more' );
		return;
	}
	if ( 'demo' === $panel ) { sakolaan_render_page_header( 'more', array( 'demo' ) ); echo '<section class="sakolaan-tool-panel">'; sakolaan_render_demo_data_page(); echo '</section>'; return; }
	sakolaan_render_system_panel();
}

function sakolaan_render_system_panel() {
	$path = array( 'system' );
	$children = sakolaan_admin_navigation_node( 'more', $path )['children'];
	$invalid = false;
	$tool = sakolaan_admin_route_value( 'tool', $children, $invalid );
	if ( ! $tool ) {
		sakolaan_render_page_header( 'more', $path );
		if ( $invalid ) { sakolaan_render_invalid_route_notice(); }
		sakolaan_render_navigation_cards( 'more', $path );
		return;
	}
	sakolaan_render_page_header( 'more', array( 'system', $tool ) );
	echo '<section class="sakolaan-tool-panel">';
	if ( 'settings-backup' === $tool ) {
		printf( '<p><a class="button" href="%1$s">%2$s</a></p>', esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sakolaan_export_settings' ), 'sakolaan_export_settings' ) ), esc_html__( 'Unduh backup pengaturan', 'sakolaan' ) );
		echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_preview_settings"><label for="sakolaan-import">' . esc_html__( 'Pilih backup pengaturan JSON', 'sakolaan' ) . '</label> <input id="sakolaan-import" type="file" name="settings_file" accept="application/json,.json" required>'; wp_nonce_field( 'sakolaan_preview_settings' ); submit_button( __( 'Pratinjau pengaturan', 'sakolaan' ), 'secondary', 'submit', false ); echo '</form>'; sakolaan_render_settings_preview();
	} elseif ( 'data-backup' === $tool ) {
		sakolaan_render_data_backup_tools();
	} elseif ( 'maintenance' === $tool ) {
		sakolaan_render_migration_tools();
		sakolaan_render_health_summary();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sakolaan_rebuild_permalinks">'; wp_nonce_field( 'sakolaan_rebuild_permalinks' ); submit_button( __( 'Perbarui permalink', 'sakolaan' ), 'secondary', 'submit', false ); echo '</form>';
	} elseif ( 'legacy' === $tool ) {
		sakolaan_render_legacy_news_tools();
	} else {
		sakolaan_render_settings_page( true, array( 'advanced' ) );
	}
	echo '</section>';
}

function sakolaan_rebuild_permalinks() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Akses ditolak.', 'sakolaan' ), '', array( 'response' => 403 ) ); }
	check_admin_referer( 'sakolaan_rebuild_permalinks' ); flush_rewrite_rules( false );
	wp_safe_redirect( sakolaan_admin_url( 'more', array( 'panel' => 'system', 'tool' => 'maintenance', 'updated' => 'permalinks' ) ) ); exit;
}
add_action( 'admin_post_sakolaan_rebuild_permalinks', 'sakolaan_rebuild_permalinks' );
