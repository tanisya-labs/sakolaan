<?php
/** Sakolaan admin: tools-workflow. @package Sakolaan */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Return to the single dashboard from native WordPress edit screens. */
function sakolaan_render_dashboard_return_link() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->base, array( 'edit', 'post', 'edit-tags', 'term' ), true ) ) {
		return;
	}
	$post_type = $screen->post_type;
	if ( ! isset( sakolaan_content_registry()[ $post_type ] ) ) {
		return;
	}
	$url = 'hero_slide' === $post_type
		? sakolaan_admin_url( 'content', array( 'panel' => 'appearance', 'area' => 'homepage', 'component' => 'hero' ) )
		: sakolaan_admin_url( 'content', array( 'panel' => 'catalog' ) );
	printf( '<div class="notice sakolaan-notice notice-info inline sakolaan-return"><p><a href="%1$s">%2$s</a></p></div>', esc_url( $url ), esc_html__( '← Kembali ke Sakolaan', 'sakolaan' ) );
}
add_action( 'admin_notices', 'sakolaan_render_dashboard_return_link', 2 );

function sakolaan_announcement_bulk_actions( $actions ) {
	$actions['sakolaan_activate'] = __( 'Aktifkan pengumuman', 'sakolaan' );
	$actions['sakolaan_archive'] = __( 'Arsipkan pengumuman', 'sakolaan' );
	return $actions;
}
add_filter( 'bulk_actions-edit-pengumuman', 'sakolaan_announcement_bulk_actions' );

function sakolaan_handle_announcement_bulk( $redirect, $action, $post_ids ) {
	if ( ! in_array( $action, array( 'sakolaan_activate', 'sakolaan_archive' ), true ) ) {
		return $redirect;
	}
	$value = 'sakolaan_activate' === $action ? 'aktif' : 'arsip';
	$updated = 0;
	foreach ( array_map( 'absint', $post_ids ) as $post_id ) {
		if ( 'pengumuman' === get_post_type( $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
			update_post_meta( $post_id, '_sakolaan_status', $value );
			++$updated;
		}
	}
	return add_query_arg( 'sakolaan_bulk_updated', $updated, $redirect );
}
add_filter( 'handle_bulk_actions-edit-pengumuman', 'sakolaan_handle_announcement_bulk', 10, 3 );

function sakolaan_announcement_bulk_notice() {
	$screen = get_current_screen();
	if ( ! $screen || 'edit-pengumuman' !== $screen->id || ! isset( $_GET['sakolaan_bulk_updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Feedback only.
		return;
	}
	/* translators: %d: number of announcements updated by the bulk action. */
	printf( '<div class="notice sakolaan-notice notice-success is-dismissible"><p>%s</p></div>', esc_html( sprintf( __( '%d pengumuman diperbarui.', 'sakolaan' ), absint( $_GET['sakolaan_bulk_updated'] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Feedback only.
}
add_action( 'admin_notices', 'sakolaan_announcement_bulk_notice' );
