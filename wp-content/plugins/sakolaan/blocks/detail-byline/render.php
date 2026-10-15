<?php
/**
 * Render metadata ringkas tepat di bawah judul detail.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_post_id   = ! empty( $block->context['postId'] ) ? (int) $block->context['postId'] : (int) get_the_ID();
$sakolaan_post_type = ! empty( $block->context['postType'] ) ? sanitize_key( $block->context['postType'] ) : get_post_type( $sakolaan_post_id );
$sakolaan_post      = get_post( $sakolaan_post_id );
$sakolaan_items     = array();
$sakolaan_taxonomies = array();

if ( ! $sakolaan_post ) {
	return;
}

if ( in_array( $sakolaan_post_type, array( 'post', 'berita' ), true ) ) {
	$sakolaan_items[] = sprintf(
		'<time class="sakolaan-detail__meta-item" datetime="%1$s">%2$s</time>',
		esc_attr( get_the_date( DATE_W3C, $sakolaan_post_id ) ),
		esc_html( get_the_date( '', $sakolaan_post_id ) )
	);

	$sakolaan_author = get_the_author_meta( 'display_name', (int) $sakolaan_post->post_author );
	if ( '' !== $sakolaan_author ) {
		$sakolaan_items[] = '<span class="sakolaan-detail__meta-item">' . esc_html(
			sprintf(
				/* translators: %s: nama tampilan penulis. */
				__( 'Oleh %s', 'sakolaan' ),
				$sakolaan_author
			)
		) . '</span>';
	}
}

switch ( $sakolaan_post_type ) {
	case 'post':
		$sakolaan_taxonomies = array( 'category' );
		break;
	case 'berita':
		$sakolaan_taxonomies = array( 'kategori_berita' );
		break;
	case 'prestasi':
		$sakolaan_taxonomies = array( 'tingkat_prestasi', 'kategori_prestasi' );
		break;
	case 'galeri':
		$sakolaan_date = sakolaan_theme_get_meta_value( $sakolaan_post_id, 'tanggal' );
		if ( '' !== $sakolaan_date ) {
			$sakolaan_items[] = '<span class="sakolaan-detail__meta-item">' . esc_html( $sakolaan_date ) . '</span>';
		}
		$sakolaan_taxonomies = array( 'album_galeri' );
		break;
}

foreach ( $sakolaan_taxonomies as $sakolaan_taxonomy ) {
	$sakolaan_terms = get_the_terms( $sakolaan_post_id, $sakolaan_taxonomy );

	if ( empty( $sakolaan_terms ) || is_wp_error( $sakolaan_terms ) ) {
		continue;
	}

	$sakolaan_term_links = array();
	foreach ( $sakolaan_terms as $sakolaan_term ) {
		$sakolaan_term_url = get_term_link( $sakolaan_term );
		if ( is_wp_error( $sakolaan_term_url ) ) {
			continue;
		}

		$sakolaan_term_links[] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $sakolaan_term_url ),
			esc_html( $sakolaan_term->name )
		);
	}

	if ( ! empty( $sakolaan_term_links ) ) {
		$sakolaan_items[] = '<span class="sakolaan-badge-list">' . implode( '', $sakolaan_term_links ) . '</span>';
	}
}

if ( empty( $sakolaan_items ) ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sakolaan-meta sakolaan-detail__byline' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dibersihkan oleh get_block_wrapper_attributes(). ?> aria-label="<?php esc_attr_e( 'Metadata konten', 'sakolaan' ); ?>">
	<?php echo implode( '', $sakolaan_items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Setiap item telah di-escape saat dibuat. ?>
</div>
