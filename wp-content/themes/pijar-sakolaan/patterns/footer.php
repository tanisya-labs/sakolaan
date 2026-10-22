<?php
/**
 * Title: Footer
 * Slug: pijar-sakolaan/footer
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_description = pijar_sakolaan_get_setting( 'footer.description' );
$sakolaan_address     = pijar_sakolaan_get_setting( 'contact.address' );
$sakolaan_phone       = pijar_sakolaan_get_setting( 'contact.phone' );
$sakolaan_email       = pijar_sakolaan_get_setting( 'contact.email' );
$sakolaan_copyright   = pijar_sakolaan_get_setting( 'footer.copyright' );
?>
<!-- wp:group {"tagName":"footer","className":"is-style-sakolaan-dark sakolaan-footer","layout":{"type":"constrained"}} -->
<footer class="wp-block-group is-style-sakolaan-dark sakolaan-footer"><!-- wp:group {"align":"wide","className":"sakolaan-footer__main","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide sakolaan-footer__main"><!-- wp:group {"className":"sakolaan-footer__brand","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-footer__brand"><!-- wp:group {"className":"sakolaan-footer__identity","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group sakolaan-footer__identity"><!-- wp:site-logo {"width":64} /-->

<!-- wp:site-title {"level":0,"fontSize":"xl","fontFamily":"heading"} /--></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"sakolaan-optional","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"footer.description"}}}}} -->
<p class="sakolaan-optional"><?php echo esc_html( $sakolaan_description ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:pijar-sakolaan/social-links /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sakolaan-footer__nav","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-footer__nav"><!-- wp:heading {"level":2,"fontSize":"md","className":"sakolaan-footer__heading"} -->
<h2 class="wp-block-heading sakolaan-footer__heading has-md-font-size"><?php esc_html_e( 'Jelajahi', 'pijar-sakolaan' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:page-list /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sakolaan-footer__contact","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-footer__contact"><!-- wp:heading {"level":2,"fontSize":"md","className":"sakolaan-footer__heading"} -->
<h2 class="wp-block-heading sakolaan-footer__heading has-md-font-size"><?php esc_html_e( 'Hubungi sekolah', 'pijar-sakolaan' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sakolaan-optional sakolaan-footer__contact-item sakolaan-footer__contact-item--address","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"contact.address"}}}}} -->
<p class="sakolaan-optional sakolaan-footer__contact-item sakolaan-footer__contact-item--address"><?php echo esc_html( $sakolaan_address ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-optional sakolaan-footer__contact-item sakolaan-footer__contact-item--phone","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"contact.phone"}}}}} -->
<p class="sakolaan-optional sakolaan-footer__contact-item sakolaan-footer__contact-item--phone"><?php echo esc_html( $sakolaan_phone ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-optional sakolaan-footer__contact-item sakolaan-footer__contact-item--email","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"contact.email"}}}}} -->
<p class="sakolaan-optional sakolaan-footer__contact-item sakolaan-footer__contact-item--email"><?php echo esc_html( $sakolaan_email ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"sakolaan-footer__whatsapp"} -->
<div class="wp-block-buttons sakolaan-footer__whatsapp"><!-- wp:button {"className":"sakolaan-button--whatsapp sakolaan-optional","metadata":{"bindings":{"url":{"source":"pijar-sakolaan/setting","args":{"key":"contact.whatsapp_url"}}}}} -->
<div class="wp-block-button sakolaan-button--whatsapp sakolaan-optional"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Chat via WhatsApp', 'pijar-sakolaan' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","className":"sakolaan-footer__bottom","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide sakolaan-footer__bottom"><!-- wp:paragraph {"className":"sakolaan-optional","fontSize":"sm","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"footer.copyright"}}}}} -->
<p class="sakolaan-optional has-sm-font-size"><?php echo esc_html( $sakolaan_copyright ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></footer>
<!-- /wp:group -->
