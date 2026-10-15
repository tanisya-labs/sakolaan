<?php
/**
 * Render daftar detail konten (dl).
 *
 * Atribut "keys": daftar key dipisahkan koma, misalnya "jabatan,mapel,email".
 * Hanya key yang dikenal di sakolaan_theme_get_meta_labels() yang diproses.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_post_id = ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
$sakolaan_labels  = sakolaan_theme_get_meta_labels();
$sakolaan_keys    = array_filter( array_map( 'trim', explode( ',', isset( $attributes['keys'] ) ? (string) $attributes['keys'] : '' ) ) );
$sakolaan_rows    = array();

foreach ( $sakolaan_keys as $sakolaan_key ) {
	if ( ! isset( $sakolaan_labels[ $sakolaan_key ] ) ) {
		continue;
	}

	$sakolaan_value = sakolaan_theme_get_meta_value( $sakolaan_post_id, $sakolaan_key );

	if ( '' === $sakolaan_value ) {
		continue;
	}

	$sakolaan_rows[] = array(
		'label' => $sakolaan_labels[ $sakolaan_key ],
		'html'  => sakolaan_theme_render_meta_value( $sakolaan_key, $sakolaan_value ),
	);
}

if ( empty( $sakolaan_rows ) ) {
	return;
}
?>
<dl <?php echo get_block_wrapper_attributes( array( 'class' => 'sakolaan-meta-list' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dibersihkan oleh get_block_wrapper_attributes(). ?>>
	<?php foreach ( $sakolaan_rows as $sakolaan_row ) : ?>
		<div class="sakolaan-meta-list__row">
			<dt><?php echo esc_html( $sakolaan_row['label'] ); ?></dt>
			<dd><?php echo $sakolaan_row['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Di-escape di sakolaan_theme_render_meta_value(). ?></dd>
		</div>
	<?php endforeach; ?>
</dl>
