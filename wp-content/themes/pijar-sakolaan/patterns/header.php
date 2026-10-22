<?php
/**
 * Title: Header
 * Slug: pijar-sakolaan/header
 * Categories: sakolaan-sections
 * Inserter: false
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_cta_label = pijar_sakolaan_get_setting( 'ppdb.cta_label' );
?>
<!-- wp:group {"tagName":"header","className":"sakolaan-header","layout":{"type":"constrained"}} -->
<header class="wp-block-group sakolaan-header"><!-- wp:group {"align":"wide","className":"sakolaan-header__inner","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide sakolaan-header__inner"><!-- wp:group {"className":"sakolaan-header__brand","layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group sakolaan-header__brand"><!-- wp:site-logo {"width":56,"shouldSyncIcon":false} /-->

<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:site-title {"level":0,"fontSize":"lg","fontFamily":"heading"} /-->

<!-- wp:site-tagline {"fontSize":"xs","textColor":"muted"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:navigation {"ariaLabel":"<?php echo esc_attr__( 'Navigasi utama', 'pijar-sakolaan' ); ?>","overlayMenu":"mobile","overlayBackgroundColor":"surface","overlayTextColor":"text","openSubmenusOnClick":true,"showSubmenuIcon":true,"icon":"menu","className":"sakolaan-header__nav","layout":{"type":"flex","justifyContent":"left"}} /-->

<!-- wp:buttons {"className":"sakolaan-header__cta"} -->
<div class="wp-block-buttons sakolaan-header__cta"><!-- wp:button {"className":"is-style-accent sakolaan-optional","metadata":{"bindings":{"url":{"source":"pijar-sakolaan/setting","args":{"key":"ppdb.url"}},"text":{"source":"pijar-sakolaan/setting","args":{"key":"ppdb.cta_label"}}}}} -->
<div class="wp-block-button is-style-accent sakolaan-optional"><a class="wp-block-button__link wp-element-button"><?php echo esc_html( $sakolaan_cta_label ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></header>
<!-- /wp:group -->
