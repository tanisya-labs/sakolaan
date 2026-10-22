<?php
/**
 * Title: Detail kontak dan peta
 * Slug: pijar-sakolaan/contact-details
 * Categories: sakolaan-pages
 * Description: Alamat, telepon, email, tombol WhatsApp, dan peta dari Pengaturan Sakolaan.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_labels = array(
	'address' => __( 'Alamat: ', 'pijar-sakolaan' ),
	'phone'   => __( 'Telepon: ', 'pijar-sakolaan' ),
	'email'   => __( 'Email: ', 'pijar-sakolaan' ),
);
?>
<!-- wp:group {"align":"wide","className":"sakolaan-contact","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide sakolaan-contact"><!-- wp:group {"className":"sakolaan-contact__info","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-contact__info"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Hubungi kami', 'pijar-sakolaan' ); ?></h2>
<!-- /wp:heading -->
<?php foreach ( $sakolaan_labels as $sakolaan_key => $sakolaan_label ) : ?>

<!-- wp:paragraph {"className":"sakolaan-optional","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"contact.<?php echo esc_attr( $sakolaan_key ); ?>","prefix":"<?php echo esc_attr( $sakolaan_label ); ?>"}}}}} -->
<p class="sakolaan-optional"><?php echo esc_html( pijar_sakolaan_get_setting( 'contact.' . $sakolaan_key ) ); ?></p>
<!-- /wp:paragraph -->
<?php endforeach; ?>

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"sakolaan-button--whatsapp sakolaan-optional","metadata":{"bindings":{"url":{"source":"pijar-sakolaan/setting","args":{"key":"contact.whatsapp_url"}}}}} -->
<div class="wp-block-button sakolaan-button--whatsapp sakolaan-optional"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Chat via WhatsApp', 'pijar-sakolaan' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:pijar-sakolaan/map /--></div>
<!-- /wp:group -->
