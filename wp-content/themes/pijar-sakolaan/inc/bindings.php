<?php
/**
 * Block Bindings: sumber data milik theme.
 *
 * - pijar-sakolaan/setting: nilai Pengaturan Sakolaan. args: key.
 * - pijar-sakolaan/meta: meta konten saat ini (guru, agenda, dan seterusnya).
 *   args: key, format (day|month|year, opsional), prefix (teks di depan nilai, opsional).
 *
 * Contoh:
 * "metadata":{"bindings":{"content":{"source":"pijar-sakolaan/meta","args":{"key":"jabatan"}}}}
 *
 * Keduanya hanya membaca dan tetap aman jika plugin Sakolaan tidak aktif.
 * Tambahkan class "sakolaan-optional" agar block tidak dirender saat nilainya kosong.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daftarkan sumber binding.
 *
 * @return void
 */
function pijar_sakolaan_register_binding_source() {
	if ( ! function_exists( 'register_block_bindings_source' ) ) {
		return;
	}

	register_block_bindings_source(
		'pijar-sakolaan/setting',
		array(
			'label'              => __( 'Pengaturan sekolah', 'pijar-sakolaan' ),
			'get_value_callback' => 'pijar_sakolaan_binding_get_value',
		)
	);

	register_block_bindings_source(
		'pijar-sakolaan/meta',
		array(
			'label'              => __( 'Data konten sekolah', 'pijar-sakolaan' ),
			'get_value_callback' => 'pijar_sakolaan_meta_binding_get_value',
			'uses_context'       => array( 'postId', 'postType' ),
		)
	);
}
add_action( 'init', 'pijar_sakolaan_register_binding_source' );

/**
 * Nilai untuk binding pijar-sakolaan/setting.
 *
 * @param array  $source_args     Argumen dari block, harus memuat "key".
 * @param object $block_instance  Instance block (tidak dipakai).
 * @param string $attribute_name  Nama atribut block yang di-bind.
 * @return string|null
 */
function pijar_sakolaan_binding_get_value( $source_args, $block_instance, $attribute_name ) {
	unset( $block_instance );

	if ( empty( $source_args['key'] ) || ! is_string( $source_args['key'] ) ) {
		return null;
	}

	if ( ! preg_match( '/^[a-z0-9_.]+$/', $source_args['key'] ) ) {
		return null;
	}

	$value = pijar_sakolaan_get_setting( $source_args['key'] );

	if ( ! is_scalar( $value ) ) {
		return '';
	}

	if ( 'url' === $attribute_name ) {
		return esc_url_raw( (string) $value );
	}

	return wp_strip_all_tags( (string) $value );
}

/**
 * Nilai meta mentah (tanpa prefix) dari argumen binding.
 *
 * @param array         $args     Argumen binding.
 * @param WP_Block|null $instance Instance block untuk konteks post.
 * @return string
 */
function pijar_sakolaan_resolve_meta_binding( $args, $instance = null ) {
	if ( empty( $args['key'] ) || ! is_string( $args['key'] ) ) {
		return '';
	}

	$post_id = 0;

	if ( $instance instanceof WP_Block && ! empty( $instance->context['postId'] ) ) {
		$post_id = (int) $instance->context['postId'];
	}

	if ( ! $post_id ) {
		$post_id = (int) get_the_ID();
	}

	$format = ( isset( $args['format'] ) && is_string( $args['format'] ) && preg_match( '/^[a-z]+$/', $args['format'] ) ) ? $args['format'] : '';

	return wp_strip_all_tags( pijar_sakolaan_get_meta_value( $post_id, $args['key'], $format ) );
}

/**
 * Nilai untuk binding pijar-sakolaan/meta.
 *
 * @param array    $source_args    Argumen binding.
 * @param WP_Block $block_instance Instance block.
 * @param string   $attribute_name Nama atribut (tidak dipakai).
 * @return string
 */
function pijar_sakolaan_meta_binding_get_value( $source_args, $block_instance, $attribute_name ) {
	unset( $attribute_name );

	$value = pijar_sakolaan_resolve_meta_binding( $source_args, $block_instance );

	if ( '' !== $value && ! empty( $source_args['prefix'] ) && is_string( $source_args['prefix'] ) ) {
		$value = wp_strip_all_tags( $source_args['prefix'] ) . $value;
	}

	return $value;
}

/**
 * Sembunyikan block opsional jika nilai binding-nya kosong.
 *
 * Tambahkan class "sakolaan-optional" pada block yang memakai binding. Jika salah satu
 * nilai kosong, block tidak dirender sama sekali (tidak ada HTML kosong).
 *
 * @param string        $block_content Hasil render block.
 * @param array         $block         Data block.
 * @param WP_Block|null $instance      Instance block.
 * @return string
 */
function pijar_sakolaan_hide_empty_bound_blocks( $block_content, $block, $instance = null ) {
	if ( '' === $block_content || empty( $block['attrs']['className'] ) ) {
		return $block_content;
	}

	if ( false === strpos( $block['attrs']['className'], 'sakolaan-optional' ) ) {
		return $block_content;
	}

	$bindings = isset( $block['attrs']['metadata']['bindings'] ) ? $block['attrs']['metadata']['bindings'] : array();

	foreach ( $bindings as $binding ) {
		if ( empty( $binding['source'] ) || empty( $binding['args']['key'] ) ) {
			continue;
		}

		if ( 'pijar-sakolaan/setting' === $binding['source'] ) {
			$value = pijar_sakolaan_get_setting( $binding['args']['key'] );
			$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		} elseif ( 'pijar-sakolaan/meta' === $binding['source'] ) {
			$value = pijar_sakolaan_resolve_meta_binding( $binding['args'], $instance );
		} else {
			continue;
		}

		if ( '' === $value ) {
			return '';
		}
	}

	return $block_content;
}
add_filter( 'render_block', 'pijar_sakolaan_hide_empty_bound_blocks', 10, 3 );
