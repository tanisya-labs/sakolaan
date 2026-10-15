<?php
/**
 * Render kontrol pengurutan dan status arsip.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_post_type = isset( $attributes['postType'] ) ? sanitize_key( $attributes['postType'] ) : '';
$sakolaan_configs   = sakolaan_theme_get_archive_configs();

if ( ! isset( $sakolaan_configs[ $sakolaan_post_type ] ) ) {
	return;
}

$sakolaan_sort_options = sakolaan_theme_get_archive_sort_options( $sakolaan_post_type );
$sakolaan_statuses     = sakolaan_theme_get_archive_status_options( $sakolaan_post_type );
$sakolaan_sort         = sanitize_key( (string) get_query_var( 'sakolaan_sort' ) );
$sakolaan_status       = sanitize_key( (string) get_query_var( 'sakolaan_status' ) );
$sakolaan_default_sort = sakolaan_theme_is_manually_ordered_post_type( $sakolaan_post_type ) ? 'manual' : 'date_desc';
$sakolaan_sort         = isset( $sakolaan_sort_options[ $sakolaan_sort ] ) ? $sakolaan_sort : $sakolaan_default_sort;
$sakolaan_status       = isset( $sakolaan_statuses[ $sakolaan_status ] ) ? $sakolaan_status : '';
$sakolaan_action       = get_post_type_archive_link( $sakolaan_post_type );
$sakolaan_queried      = get_queried_object();

if ( $sakolaan_queried instanceof WP_Term ) {
	$sakolaan_term_link = get_term_link( $sakolaan_queried );

	if ( ! is_wp_error( $sakolaan_term_link ) ) {
		$sakolaan_action = $sakolaan_term_link;
	}
}

if ( ! $sakolaan_action ) {
	return;
}

$sakolaan_sort_id   = wp_unique_id( 'sakolaan-archive-sort-' );
$sakolaan_status_id = wp_unique_id( 'sakolaan-archive-status-' );
$sakolaan_is_active = $sakolaan_default_sort !== $sakolaan_sort || '' !== $sakolaan_status;
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'sakolaan-archive-controls' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dibersihkan oleh get_block_wrapper_attributes(). ?>>
	<form class="sakolaan-archive-controls__form" method="get" action="<?php echo esc_url( $sakolaan_action ); ?>">
		<div class="sakolaan-archive-controls__field sakolaan-archive-controls__field--sort">
			<label class="sakolaan-archive-controls__label" for="<?php echo esc_attr( $sakolaan_sort_id ); ?>"><?php esc_html_e( 'Urutkan', 'sakolaan' ); ?></label>
			<select id="<?php echo esc_attr( $sakolaan_sort_id ); ?>" name="sakolaan_sort">
				<?php foreach ( $sakolaan_sort_options as $sakolaan_value => $sakolaan_label ) : ?>
					<option value="<?php echo esc_attr( $sakolaan_value ); ?>"<?php selected( $sakolaan_sort, $sakolaan_value ); ?>><?php echo esc_html( $sakolaan_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<?php if ( ! empty( $sakolaan_statuses ) ) : ?>
			<div class="sakolaan-archive-controls__field sakolaan-archive-controls__field--status">
				<label class="sakolaan-archive-controls__label" for="<?php echo esc_attr( $sakolaan_status_id ); ?>"><?php esc_html_e( 'Status', 'sakolaan' ); ?></label>
				<select id="<?php echo esc_attr( $sakolaan_status_id ); ?>" name="sakolaan_status">
					<option value=""><?php esc_html_e( 'Semua status', 'sakolaan' ); ?></option>
					<?php foreach ( $sakolaan_statuses as $sakolaan_value => $sakolaan_label ) : ?>
						<option value="<?php echo esc_attr( $sakolaan_value ); ?>"<?php selected( $sakolaan_status, $sakolaan_value ); ?>><?php echo esc_html( $sakolaan_label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endif; ?>

		<div class="sakolaan-archive-controls__actions">
			<button class="sakolaan-archive-controls__submit" type="submit"><?php esc_html_e( 'Terapkan', 'sakolaan' ); ?></button>
			<?php if ( $sakolaan_is_active ) : ?>
				<a class="sakolaan-archive-controls__reset" href="<?php echo esc_url( $sakolaan_action ); ?>"><?php esc_html_e( 'Reset', 'sakolaan' ); ?></a>
			<?php endif; ?>
		</div>
	</form>
</div>
