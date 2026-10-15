<?php
/**
 * Render gambar dari Pengaturan Sakolaan.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_key = isset( $attributes['key'] ) ? (string) $attributes['key'] : '';

if ( ! preg_match( '/^[a-z0-9_.]+$/', $sakolaan_key ) ) {
	return;
}

$sakolaan_image_id = absint( sakolaan_theme_get_setting( $sakolaan_key, 0 ) );

if ( ! $sakolaan_image_id ) {
	return;
}

$sakolaan_size = isset( $attributes['size'] ) ? sanitize_key( $attributes['size'] ) : 'large';
$sakolaan_alt  = trim( (string) get_post_meta( $sakolaan_image_id, '_wp_attachment_image_alt', true ) );

if ( '' === $sakolaan_alt && ! empty( $attributes['fallbackAlt'] ) ) {
	$sakolaan_alt = wp_strip_all_tags( (string) $attributes['fallbackAlt'] );
}

$sakolaan_img_attrs = array(
	'alt'      => $sakolaan_alt,
	'class'    => 'sakolaan-setting-image__img',
	'decoding' => 'async',
	'loading'  => ! empty( $attributes['priority'] ) ? 'eager' : 'lazy',
);

if ( ! empty( $attributes['priority'] ) ) {
	$sakolaan_img_attrs['fetchpriority'] = 'high';
}

if ( ! empty( $attributes['sizes'] ) && preg_match( '/^[a-z0-9 ():,.%\-]+$/i', (string) $attributes['sizes'] ) ) {
	$sakolaan_img_attrs['sizes'] = (string) $attributes['sizes'];
}

$sakolaan_image = wp_get_attachment_image( $sakolaan_image_id, $sakolaan_size, false, $sakolaan_img_attrs );

if ( '' === $sakolaan_image ) {
	return;
}

$sakolaan_wrapper = array( 'class' => 'sakolaan-setting-image' );

if ( ! empty( $attributes['aspectRatio'] ) && preg_match( '/^\d+(\/\d+)?$/', (string) $attributes['aspectRatio'] ) ) {
	$sakolaan_wrapper['style'] = 'aspect-ratio:' . $attributes['aspectRatio'];
}
?>
<figure <?php echo get_block_wrapper_attributes( $sakolaan_wrapper ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dibersihkan oleh get_block_wrapper_attributes(). ?>>
	<?php echo $sakolaan_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output WordPress core. ?>
</figure>
