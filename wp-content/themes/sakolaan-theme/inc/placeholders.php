<?php
/**
 * Fallback gambar: kartu dan profil tetap rapi jika foto belum diunggah.
 *
 * Berlaku untuk block Featured Image yang diberi class "sakolaan-photo".
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ganti Featured Image kosong dengan gambar pengganti atau placeholder.
 *
 * @param string $block_content Hasil render block.
 * @param array  $block         Data block.
 * @return string
 */
function sakolaan_theme_featured_image_fallback( $block_content, $block ) {
	if ( '' !== $block_content || empty( $block['attrs']['className'] ) || false === strpos( $block['attrs']['className'], 'sakolaan-photo' ) ) {
		return $block_content;
	}

	$ratio = '16/10';

	if ( ! empty( $block['attrs']['aspectRatio'] ) && preg_match( '/^\d+(\/\d+)?$/', $block['attrs']['aspectRatio'] ) ) {
		$ratio = $block['attrs']['aspectRatio'];
	}

	$post_id = (int) get_the_ID();

	// Galeri tanpa foto utama memakai gambar pertama dari kumpulan gambarnya.
	if ( $post_id && 'galeri' === get_post_type( $post_id ) ) {
		$ids = get_post_meta( $post_id, '_sakolaan_gambar_ids', true );

		if ( is_array( $ids ) && ! empty( $ids ) ) {
			$size  = ! empty( $block['attrs']['sizeSlug'] ) ? sanitize_key( $block['attrs']['sizeSlug'] ) : 'sakolaan-card';
			$image = wp_get_attachment_image( absint( reset( $ids ) ), $size, false, array( 'alt' => '' ) );

			if ( $image ) {
				return '<figure class="wp-block-post-featured-image sakolaan-photo-fallback" style="aspect-ratio:' . esc_attr( $ratio ) . '">' . $image . '</figure>';
			}
		}
	}

	return '<div class="sakolaan-placeholder" style="aspect-ratio:' . esc_attr( $ratio ) . '" aria-hidden="true">'
		. '<svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false">'
		. '<rect x="3.5" y="5" width="17" height="14" rx="2"/><circle cx="9" cy="10" r="1.5"/><path d="M4 17l5-4 3 2.5 3-3L20 16"/>'
		. '</svg></div>';
}
add_filter( 'render_block_core/post-featured-image', 'sakolaan_theme_featured_image_fallback', 10, 2 );
