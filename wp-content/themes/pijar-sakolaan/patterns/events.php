<?php
/**
 * Title: Agenda mendatang
 * Slug: pijar-sakolaan/events
 * Categories: sakolaan-sections
 * Description: Agenda yang belum selesai, terdekat lebih dulu.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sakolaan_url = pijar_sakolaan_get_archive_url( 'agenda' );
?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--events","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--events"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php
echo pijar_sakolaan_section_header_markup( __( 'Agenda mendatang', 'pijar-sakolaan' ), $sakolaan_url, __( 'Lihat semua agenda', 'pijar-sakolaan' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Di-escape di helper.
?>

<?php
echo pijar_sakolaan_build_loop_markup( array( 'post_type' => 'agenda', 'per_page' => pijar_sakolaan_homepage_item_count( 'events', 3 ), 'class' => 'sakolaan-query--upcoming', 'card' => 'agenda', 'min' => '18rem', 'empty' => 'agenda', 'query_id' => 4 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON dan atribut di-escape di helper.
?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
