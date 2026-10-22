<?php
/**
 * Render kumpulan foto galeri.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_post_id = ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
$sakolaan_ids     = get_post_meta( $sakolaan_post_id, '_sakolaan_gambar_ids', true );

if ( ! is_array( $sakolaan_ids ) ) {
	return;
}

$sakolaan_ids = array_values( array_unique( array_filter( array_map( 'absint', $sakolaan_ids ) ) ) );

if ( empty( $sakolaan_ids ) ) {
	return;
}

$sakolaan_title = get_the_title( $sakolaan_post_id );
$sakolaan_title = '' !== trim( $sakolaan_title ) ? $sakolaan_title : __( 'Galeri foto', 'sakolaan' );
$sakolaan_items = array();

foreach ( $sakolaan_ids as $sakolaan_id ) {
	$sakolaan_full = wp_get_attachment_image_url( $sakolaan_id, 'large' );

	if ( ! $sakolaan_full || ! wp_attachment_is_image( $sakolaan_id ) ) {
		continue;
	}

	$sakolaan_items[] = array(
		'id'   => $sakolaan_id,
		'url'  => $sakolaan_full,
		'alt'  => trim( (string) get_post_meta( $sakolaan_id, '_wp_attachment_image_alt', true ) ),
	);
}

if ( empty( $sakolaan_items ) ) {
	return;
}

pijar_sakolaan_enqueue_gallery_script();
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sakolaan-gallery', 'role' => 'region', 'aria-label' => sprintf( /* translators: %s: gallery title. */ __( 'Galeri foto: %s', 'sakolaan' ), $sakolaan_title ), 'data-sakolaan-gallery' => '', 'data-label-dialog' => __( 'Penampil foto', 'sakolaan' ), 'data-label-close' => __( 'Tutup', 'sakolaan' ), 'data-label-prev' => __( 'Foto sebelumnya', 'sakolaan' ), 'data-label-next' => __( 'Foto berikutnya', 'sakolaan' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dibersihkan oleh get_block_wrapper_attributes(). ?>>
	<ul class="sakolaan-gallery__list" aria-label="<?php esc_attr_e( 'Daftar foto', 'sakolaan' ); ?>">
		<?php foreach ( $sakolaan_items as $sakolaan_index => $sakolaan_item ) : ?>
			<?php
			$sakolaan_alt = $sakolaan_item['alt'];

			if ( '' === $sakolaan_alt ) {
				$sakolaan_alt = sprintf(
					/* translators: 1: judul galeri, 2: nomor foto. */
					__( '%1$s, foto %2$d', 'sakolaan' ),
					$sakolaan_title,
					$sakolaan_index + 1
				);
			}
			?>
			<li>
				<a href="<?php echo esc_url( $sakolaan_item['url'] ); ?>" data-sakolaan-lightbox aria-label="<?php echo esc_attr( sprintf( /* translators: %d: photo number. */ __( 'Buka foto %d', 'sakolaan' ), $sakolaan_index + 1 ) ); ?>">
					<?php echo wp_get_attachment_image( $sakolaan_item['id'], 'sakolaan-card', false, array( 'alt' => $sakolaan_alt ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output WordPress core. ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
