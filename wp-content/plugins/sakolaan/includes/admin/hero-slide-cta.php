<?php
/**
 * Kontrol CTA slide pada area utama editor.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return array<string,string>
 */
function sakolaan_hero_cta_styles() {
	return array(
		'accent'  => __( 'Aksen emas', 'sakolaan' ),
		'light'   => __( 'Solid terang', 'sakolaan' ),
		'outline' => __( 'Outline', 'sakolaan' ),
	);
}

/**
 * @return void
 */
function sakolaan_add_hero_cta_box() {
	add_meta_box(
		'sakolaan-hero-cta',
		__( 'Tombol Slide', 'sakolaan' ),
		'sakolaan_render_hero_cta_box',
		'hero_slide',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes_hero_slide', 'sakolaan_add_hero_cta_box' );

/**
 * @param WP_Post $post Slide aktif.
 * @return void
 */
function sakolaan_render_hero_cta_box( $post ) {
	wp_nonce_field( 'sakolaan_save_hero_cta', 'sakolaan_hero_cta_nonce' );
	$buttons = array(
		array( 'cta', __( 'Tombol utama', 'sakolaan' ), 'accent' ),
		array( 'cta2', __( 'Tombol kedua', 'sakolaan' ), 'outline' ),
	);
	?>
	<div class="sakolaan-slide-cta">
		<?php foreach ( $buttons as $button ) : ?>
			<?php
			$prefix = $button[0];
			$label  = (string) get_post_meta( $post->ID, '_sakolaan_' . $prefix . '_label', true );
			$url    = (string) get_post_meta( $post->ID, '_sakolaan_' . $prefix . '_url', true );
			$style  = (string) get_post_meta( $post->ID, '_sakolaan_' . $prefix . '_style', true );
			$style  = isset( sakolaan_hero_cta_styles()[ $style ] ) ? $style : $button[2];
			?>
			<fieldset class="sakolaan-slide-cta__item">
				<legend><?php echo esc_html( $button[1] ); ?></legend>
				<div class="sakolaan-slide-cta__fields">
					<p><label for="sakolaan-<?php echo esc_attr( $prefix ); ?>-label"><?php esc_html_e( 'Teks tombol', 'sakolaan' ); ?></label>
					<input class="widefat" id="sakolaan-<?php echo esc_attr( $prefix ); ?>-label" name="sakolaan_slide_cta[<?php echo esc_attr( $prefix ); ?>_label]" type="text" value="<?php echo esc_attr( $label ); ?>" data-sakolaan-cta-label></p>
					<p><label for="sakolaan-<?php echo esc_attr( $prefix ); ?>-url"><?php esc_html_e( 'Tautan', 'sakolaan' ); ?></label>
					<input class="widefat" id="sakolaan-<?php echo esc_attr( $prefix ); ?>-url" name="sakolaan_slide_cta[<?php echo esc_attr( $prefix ); ?>_url]" type="text" inputmode="url" value="<?php echo esc_attr( $url ); ?>" placeholder="https://example.org/ atau /ppdb/" data-sakolaan-cta-url></p>
					<p><label for="sakolaan-<?php echo esc_attr( $prefix ); ?>-style"><?php esc_html_e( 'Gaya tombol', 'sakolaan' ); ?></label>
					<select class="widefat" id="sakolaan-<?php echo esc_attr( $prefix ); ?>-style" name="sakolaan_slide_cta[<?php echo esc_attr( $prefix ); ?>_style]">
						<?php foreach ( sakolaan_hero_cta_styles() as $style_key => $style_label ) : ?>
							<option value="<?php echo esc_attr( $style_key ); ?>"<?php selected( $style, $style_key ); ?>><?php echo esc_html( $style_label ); ?></option>
						<?php endforeach; ?>
					</select></p>
				</div>
				<p class="sakolaan-slide-cta__warning" role="status" aria-live="polite" data-sakolaan-cta-warning data-warning="<?php esc_attr_e( 'Isi teks dan tautan agar tombol tampil di slide.', 'sakolaan' ); ?>"><?php echo (bool) $label !== (bool) $url ? esc_html__( 'Isi teks dan tautan agar tombol tampil di slide.', 'sakolaan' ) : ''; ?></p>
			</fieldset>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * @param int $post_id ID slide.
 * @return void
 */
function sakolaan_save_hero_cta_box( $post_id ) {
	if ( ! isset( $_POST['sakolaan_hero_cta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sakolaan_hero_cta_nonce'] ) ), 'sakolaan_save_hero_cta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each supported scalar field is sanitized according to its schema below.
	$submitted = isset( $_POST['sakolaan_slide_cta'] ) && is_array( $_POST['sakolaan_slide_cta'] ) ? wp_unslash( $_POST['sakolaan_slide_cta'] ) : array();
	foreach ( array( 'cta' => 'accent', 'cta2' => 'outline' ) as $prefix => $default_style ) {
		$label_key = $prefix . '_label';
		$url_key   = $prefix . '_url';
		$style_key = $prefix . '_style';
		$label     = isset( $submitted[ $label_key ] ) && is_scalar( $submitted[ $label_key ] ) ? sanitize_text_field( $submitted[ $label_key ] ) : '';
		$url       = isset( $submitted[ $url_key ] ) && is_scalar( $submitted[ $url_key ] ) ? esc_url_raw( trim( (string) $submitted[ $url_key ] ) ) : '';
		$style     = isset( $submitted[ $style_key ] ) ? sakolaan_sanitize_hero_cta_style( $submitted[ $style_key ] ) : $default_style;
		$style     = isset( sakolaan_hero_cta_styles()[ $style ] ) ? $style : $default_style;
		update_post_meta( $post_id, '_sakolaan_' . $label_key, $label );
		update_post_meta( $post_id, '_sakolaan_' . $url_key, $url );
		update_post_meta( $post_id, '_sakolaan_' . $style_key, $style );
	}
}
add_action( 'save_post_hero_slide', 'sakolaan_save_hero_cta_box' );

/**
 * @return void
 */
function sakolaan_enqueue_hero_cta_box_assets() {
	$screen = get_current_screen();
	if ( ! $screen || 'hero_slide' !== $screen->post_type || 'post' !== $screen->base ) {
		return;
	}
	sakolaan_enqueue_admin_style();
	wp_enqueue_script( 'sakolaan-hero-cta', SAKOLAAN_URL . 'assets/js/hero-slide-cta.js', array(), sakolaan_admin_asset_version( 'assets/js/hero-slide-cta.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'sakolaan_enqueue_hero_cta_box_assets' );
