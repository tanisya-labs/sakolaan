<?php
/**
 * Render filter term untuk arsip.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_taxonomy = isset( $attributes['taxonomy'] ) ? sanitize_key( $attributes['taxonomy'] ) : '';

if ( 'category' === $sakolaan_taxonomy && is_tax( array( 'kategori_berita', 'tag_berita' ) ) ) {
	return;
}

if ( '' === $sakolaan_taxonomy || ! taxonomy_exists( $sakolaan_taxonomy ) ) {
	return;
}

$sakolaan_terms = get_terms(
	array(
		'taxonomy'   => $sakolaan_taxonomy,
		'hide_empty' => true,
	)
);

if ( is_wp_error( $sakolaan_terms ) || empty( $sakolaan_terms ) ) {
	return;
}

$sakolaan_tax       = get_taxonomy( $sakolaan_taxonomy );

if ( ! $sakolaan_tax || empty( $sakolaan_tax->object_type ) || empty( $sakolaan_tax->labels->name ) ) {
	return;
}

$sakolaan_post_type = ! empty( $sakolaan_tax->object_type ) ? $sakolaan_tax->object_type[0] : '';
$sakolaan_all_url   = 'category' === $sakolaan_taxonomy ? pijar_sakolaan_get_archive_url( 'berita' ) : ( $sakolaan_post_type ? get_post_type_archive_link( $sakolaan_post_type ) : '' );
$sakolaan_current   = ( is_tax( $sakolaan_taxonomy ) || ( 'category' === $sakolaan_taxonomy && is_category() ) ) ? (int) get_queried_object_id() : 0;
$sakolaan_query_args = array_filter(
	array(
		'sakolaan_sort'   => sanitize_key( (string) get_query_var( 'sakolaan_sort' ) ),
		'sakolaan_status' => sanitize_key( (string) get_query_var( 'sakolaan_status' ) ),
	)
);

if ( $sakolaan_all_url && ! empty( $sakolaan_query_args ) ) {
	$sakolaan_all_url = add_query_arg( $sakolaan_query_args, $sakolaan_all_url );
}
?>
<nav <?php echo get_block_wrapper_attributes( array( 'class' => 'sakolaan-term-filter', 'aria-label' => sprintf( /* translators: %s: nama taxonomy. */ __( 'Saring menurut %s', 'sakolaan' ), $sakolaan_tax->labels->name ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dibersihkan oleh get_block_wrapper_attributes(). ?>>
	<ul class="sakolaan-filter__list">
		<?php if ( $sakolaan_all_url ) : ?>
			<li>
				<a href="<?php echo esc_url( $sakolaan_all_url ); ?>"<?php echo ( 0 === $sakolaan_current && is_post_type_archive( 'category' === $sakolaan_taxonomy ? 'berita' : $sakolaan_post_type ) ) ? ' aria-current="page"' : ''; ?>><?php esc_html_e( 'Semua', 'sakolaan' ); ?></a>
			</li>
		<?php endif; ?>
		<?php foreach ( $sakolaan_terms as $sakolaan_term ) : ?>
			<?php if ( ! $sakolaan_term instanceof WP_Term ) : ?>
				<?php continue; ?>
			<?php endif; ?>
			<?php $sakolaan_link = get_term_link( $sakolaan_term ); ?>
			<?php if ( is_wp_error( $sakolaan_link ) ) : ?>
				<?php continue; ?>
			<?php endif; ?>
			<?php if ( ! empty( $sakolaan_query_args ) ) : ?>
				<?php $sakolaan_link = add_query_arg( $sakolaan_query_args, $sakolaan_link ); ?>
			<?php endif; ?>
			<li>
				<a href="<?php echo esc_url( $sakolaan_link ); ?>"<?php echo (int) $sakolaan_term->term_id === $sakolaan_current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $sakolaan_term->name ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
