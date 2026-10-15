<?php
/** Shared assets for Sakolaan admin components. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sakolaan_admin_asset_version( $relative_path ) {
	$path = SAKOLAAN_PATH . $relative_path;
	return is_file( $path ) ? (string) filemtime( $path ) : SAKOLAAN_VERSION;
}

function sakolaan_enqueue_admin_style() {
	wp_enqueue_style( 'sakolaan-admin', SAKOLAAN_URL . 'assets/css/admin.css', array( 'common', 'buttons', 'dashicons', 'sakolaan-admin-notices' ), sakolaan_admin_asset_version( 'assets/css/admin.css' ) );
	if ( 'sakolaan-theme' === get_template() && function_exists( 'wp_get_global_settings' ) ) {
		$palette = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );
		$map = array( 'primary' => 'primary', 'primary-dark' => 'primary-dark', 'primary-tint' => 'tint', 'accent' => 'gold', 'text' => 'text', 'muted' => 'muted', 'border' => 'border', 'surface' => 'surface', 'background' => 'subtle', 'danger' => 'danger' );
		$variables = array();
		foreach ( (array) $palette as $color ) {
			$slug = $color['slug'] ?? '';
			$value = isset( $color['color'] ) ? sanitize_hex_color( $color['color'] ) : '';
			if ( isset( $map[ $slug ] ) && $value ) {
				$variables[] = '--sakolaan-' . $map[ $slug ] . ':' . $value;
			}
		}
		if ( $variables ) {
			wp_add_inline_style( 'sakolaan-admin', '.sakolaan-screen{' . implode( ';', $variables ) . '}' );
		}
	}
}

function sakolaan_is_admin_ui_screen() {
	$screen = get_current_screen();
	if ( ! $screen ) {
		return false;
	}
	if ( 'toplevel_page_sakolaan' === $screen->id || false !== strpos( $screen->id, '_page_sakolaan-' ) ) {
		return true;
	}
	if ( in_array( $screen->base, array( 'post', 'edit' ), true ) ) {
		return isset( sakolaan_get_meta_schema()[ $screen->post_type ] ) || in_array( $screen->post_type, sakolaan_seo_post_types(), true );
	}
	return in_array( $screen->base, array( 'edit-tags', 'term' ), true ) && in_array( $screen->taxonomy, sakolaan_seo_taxonomies(), true );
}

function sakolaan_enqueue_admin_ui() {
	if ( sakolaan_is_admin_ui_screen() ) {
		sakolaan_enqueue_admin_style();
		$screen = get_current_screen();
		if ( $screen && 'toplevel_page_sakolaan' === $screen->id ) {
			wp_enqueue_script( 'sakolaan-admin-navigation', SAKOLAAN_URL . 'assets/js/admin-navigation.js', array(), sakolaan_admin_asset_version( 'assets/js/admin-navigation.js' ), true );
			wp_script_add_data( 'sakolaan-admin-navigation', 'defer', true );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'sakolaan_enqueue_admin_ui', 5 );

function sakolaan_admin_ui_body_class( $classes ) {
	return sakolaan_is_admin_ui_screen() ? $classes . ' sakolaan-screen' : $classes;
}
add_filter( 'admin_body_class', 'sakolaan_admin_ui_body_class' );
