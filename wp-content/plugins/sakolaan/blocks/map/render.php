<?php
/**
 * Render peta lokasi.
 *
 * Hanya alamat embed Google Maps (https) yang diterima. Peta dimuat lazy, dan tautan
 * petunjuk arah berbasis alamat selalu tersedia sebagai alternatif.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_map_url = esc_url_raw( (string) sakolaan_theme_get_setting( 'contact.maps_url', '' ), array( 'https' ) );
$sakolaan_host    = strtolower( (string) wp_parse_url( $sakolaan_map_url, PHP_URL_HOST ) );
$sakolaan_path    = (string) wp_parse_url( $sakolaan_map_url, PHP_URL_PATH );
$sakolaan_valid   = '' !== $sakolaan_map_url
	&& in_array( $sakolaan_host, array( 'www.google.com', 'maps.google.com', 'google.com' ), true )
	&& 0 === strpos( $sakolaan_path, '/maps' );

$sakolaan_address   = (string) sakolaan_theme_get_setting( 'contact.address', '' );
$sakolaan_direction = '' !== $sakolaan_address
	? add_query_arg(
		array(
			'api'   => 1,
			'query' => $sakolaan_address,
		),
		'https://www.google.com/maps/search/'
	)
	: '';

if ( ! $sakolaan_valid && '' === $sakolaan_direction ) {
	return;
}

$sakolaan_title = sprintf(
	/* translators: %s: nama sekolah. */
	__( 'Peta lokasi %s', 'sakolaan' ),
	get_bloginfo( 'name' )
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sakolaan-map' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dibersihkan oleh get_block_wrapper_attributes(). ?>>
	<?php if ( $sakolaan_valid ) : ?>
		<iframe
			class="sakolaan-map__frame"
			src="<?php echo esc_url( $sakolaan_map_url ); ?>"
			title="<?php echo esc_attr( $sakolaan_title ); ?>"
			loading="lazy"
			referrerpolicy="no-referrer-when-downgrade"
			aria-label="<?php echo esc_attr( $sakolaan_title ); ?>"
			allowfullscreen></iframe>
	<?php endif; ?>
	<?php if ( '' !== $sakolaan_direction ) : ?>
		<p class="sakolaan-map__link">
			<a href="<?php echo esc_url( $sakolaan_direction ); ?>" rel="noopener noreferrer"><?php esc_html_e( 'Petunjuk arah di Google Maps', 'sakolaan' ); ?></a>
		</p>
	<?php endif; ?>
</div>
