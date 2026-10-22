<?php
/**
 * Title: Akses cepat
 * Slug: pijar-sakolaan/quick-links
 * Categories: sakolaan-sections
 * Description: Lima tujuan utama untuk pengunjung situs sekolah.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_ppdb_url = pijar_sakolaan_get_setting( 'ppdb.url', '' );

if ( '' === $sakolaan_ppdb_url ) {
	$sakolaan_ppdb_url = pijar_sakolaan_get_page_url( 'ppdb' );
}

$sakolaan_quick_links = pijar_sakolaan_get_setting( 'homepage.quick_links', false );

if ( false === $sakolaan_quick_links ) {
	$sakolaan_quick_links = array(
		array( 'label' => __( 'Berita', 'pijar-sakolaan' ), 'url' => pijar_sakolaan_get_archive_url( 'berita' ), 'enabled' => true ),
		array( 'label' => __( 'Pengumuman', 'pijar-sakolaan' ), 'url' => pijar_sakolaan_get_archive_url( 'pengumuman' ), 'enabled' => true ),
		array( 'label' => __( 'Agenda', 'pijar-sakolaan' ), 'url' => pijar_sakolaan_get_archive_url( 'agenda' ), 'enabled' => true ),
		array( 'label' => __( 'PPDB', 'pijar-sakolaan' ), 'url' => $sakolaan_ppdb_url, 'enabled' => true ),
		array( 'label' => __( 'Kontak', 'pijar-sakolaan' ), 'url' => pijar_sakolaan_get_page_url( 'kontak' ), 'enabled' => true ),
	);
}

$sakolaan_quick_links = array_values(
	array_filter(
		$sakolaan_quick_links,
		static function ( $sakolaan_link ) {
			return is_array( $sakolaan_link ) && ! empty( $sakolaan_link['enabled'] ) && ! empty( $sakolaan_link['label'] ) && ! empty( $sakolaan_link['url'] );
		}
	)
);

if ( empty( $sakolaan_quick_links ) ) {
	return;
}
?>
<!-- wp:group {"tagName":"nav","className":"sakolaan-quick-links","layout":{"type":"constrained"}} -->
<nav class="wp-block-group sakolaan-quick-links"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"level":2,"className":"sakolaan-visually-hidden"} -->
<h2 class="wp-block-heading sakolaan-visually-hidden"><?php esc_html_e( 'Akses cepat', 'pijar-sakolaan' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"sakolaan-quick-links__grid","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-quick-links__grid"><?php foreach ( $sakolaan_quick_links as $sakolaan_quick_link ) : ?>
<!-- wp:paragraph {"className":"sakolaan-quick-link"} -->
<p class="sakolaan-quick-link"><a href="<?php echo esc_url( $sakolaan_quick_link['url'] ); ?>"><?php
				if ( ! empty( $sakolaan_quick_link['icon_id'] ) && wp_attachment_is_image( absint( $sakolaan_quick_link['icon_id'] ) ) ) {
					$icon_alt = isset( $sakolaan_quick_link['icon_alt'] ) ? (string) $sakolaan_quick_link['icon_alt'] : '';
					if ( '' === $icon_alt ) {
						$icon_alt = (string) get_post_meta( absint( $sakolaan_quick_link['icon_id'] ), '_wp_attachment_image_alt', true );
					}
					echo wp_get_attachment_image( absint( $sakolaan_quick_link['icon_id'] ), 'thumbnail', false, array( 'class' => 'sakolaan-quick-link__image', 'alt' => $icon_alt, 'aria-hidden' => '' === $icon_alt ? 'true' : 'false' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output image WordPress sudah di-escape.
				} elseif ( ! empty( $sakolaan_quick_link['icon'] ) ) {
					echo pijar_sakolaan_get_quick_link_icon( $sakolaan_quick_link['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG statis dari allowlist.
				}
				?><span class="sakolaan-quick-link__label"><?php echo esc_html( $sakolaan_quick_link['label'] ); ?></span><span class="sakolaan-quick-link__arrow" aria-hidden="true">&rarr;</span></a></p>
<!-- /wp:paragraph -->
<?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:group --></nav>
<!-- /wp:group -->
