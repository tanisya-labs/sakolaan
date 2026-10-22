<?php
/**
 * Render block breadcrumb.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
	// Pratinjau di editor: conditional tag query tidak tersedia di request REST.
	$sakolaan_items = array(
		array(
			'label' => __( 'Beranda', 'sakolaan' ),
			'url'   => '#',
		),
		array(
			'label' => __( 'Halaman induk', 'sakolaan' ),
			'url'   => '#',
		),
		array(
			'label' => __( 'Halaman saat ini', 'sakolaan' ),
			'url'   => null,
		),
	);
	$sakolaan_schema = '';
} else {
	$sakolaan_items  = pijar_sakolaan_get_breadcrumb_items();
	$sakolaan_schema = pijar_sakolaan_get_breadcrumb_schema( $sakolaan_items );
}

if ( empty( $sakolaan_items ) ) {
	return;
}

$sakolaan_last = count( $sakolaan_items ) - 1;
$sakolaan_visual_items = array_slice( $sakolaan_items, 0, $sakolaan_last );

if ( empty( $sakolaan_visual_items ) ) {
	return;
}
?>
<nav <?php echo get_block_wrapper_attributes( array( 'aria-label' => __( 'Jejak halaman', 'sakolaan' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dibersihkan oleh get_block_wrapper_attributes(). ?>>
	<ol class="sakolaan-breadcrumb__list">
		<?php foreach ( array_values( $sakolaan_visual_items ) as $sakolaan_index => $sakolaan_item ) : ?>
			<li class="sakolaan-breadcrumb__item">
				<a href="<?php echo esc_url( $sakolaan_item['url'] ); ?>">
					<?php if ( 0 === $sakolaan_index ) : ?>
						<svg class="sakolaan-breadcrumb__home-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
							<path d="M3 10.8 12 3l9 7.8v9.45a.75.75 0 0 1-.75.75h-5.5v-6.25h-5.5V21h-5.5a.75.75 0 0 1-.75-.75V10.8Z"></path>
						</svg>
					<?php endif; ?>
					<span><?php echo esc_html( $sakolaan_item['label'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php echo $sakolaan_schema; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD dibuat dengan wp_json_encode() dan JSON_HEX_TAG. ?>
</nav>
