<?php
/**
 * Title: Ajakan PPDB
 * Slug: pijar-sakolaan/admission-cta
 * Categories: sakolaan-sections
 * Description: Ajakan mendaftar dengan tombol ke halaman PPDB.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--ppdb is-style-sakolaan-dark sakolaan-cta","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--ppdb is-style-sakolaan-dark sakolaan-cta"><!-- wp:group {"align":"wide","className":"sakolaan-cta__inner","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide sakolaan-cta__inner"><!-- wp:group {"className":"sakolaan-cta__copy","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-cta__copy"><!-- wp:paragraph {"className":"sakolaan-cta__eyebrow"} -->
<p class="sakolaan-cta__eyebrow"><?php esc_html_e( 'Bergabung bersama kami', 'pijar-sakolaan' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Penerimaan peserta didik baru', 'pijar-sakolaan' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sakolaan-optional","fontSize":"lg","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"ppdb.description"}}}}} -->
<p class="sakolaan-optional has-lg-font-size"><?php echo esc_html( pijar_sakolaan_get_setting( 'ppdb.description' ) ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons {"className":"sakolaan-cta__actions","layout":{"type":"flex","justifyContent":"right"}} -->
<div class="wp-block-buttons sakolaan-cta__actions"><!-- wp:button {"className":"is-style-accent sakolaan-optional","metadata":{"bindings":{"url":{"source":"pijar-sakolaan/setting","args":{"key":"ppdb.url"}},"text":{"source":"pijar-sakolaan/setting","args":{"key":"ppdb.cta_label"}}}}} -->
<div class="wp-block-button is-style-accent sakolaan-optional"><a class="wp-block-button__link wp-element-button"><?php echo esc_html( pijar_sakolaan_get_setting( 'ppdb.cta_label' ) ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
