<?php
/**
 * Title: Statistik sekolah
 * Slug: pijar-sakolaan/statistics
 * Categories: sakolaan-sections
 * Description: Hingga lima angka (siswa, guru, tahun berdiri, dan lainnya) dari Pengaturan Sakolaan.
 *
 * @package Pijar_Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"sakolaan-section sakolaan-section--statistics","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section sakolaan-section--statistics"><!-- wp:group {"align":"wide","className":"sakolaan-statistics__inner","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide sakolaan-statistics__inner"><!-- wp:group {"className":"sakolaan-section-header sakolaan-section-header--statistics","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-section-header sakolaan-section-header--statistics"><!-- wp:paragraph {"className":"sakolaan-statistics__eyebrow"} -->
<p class="sakolaan-statistics__eyebrow"><?php esc_html_e( 'Profil singkat', 'pijar-sakolaan' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Sekolah dalam angka', 'pijar-sakolaan' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Gambaran singkat komunitas dan fasilitas belajar kami.', 'pijar-sakolaan' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sakolaan-stats","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-stats"><?php for ( $sakolaan_i = 1; $sakolaan_i <= 5; $sakolaan_i++ ) : ?>
<!-- wp:group {"className":"sakolaan-stat","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-stat"><!-- wp:paragraph {"className":"sakolaan-stat__value sakolaan-optional","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"homepage.stat_<?php echo (int) $sakolaan_i; ?>_value"}}}}} -->
<p class="sakolaan-stat__value sakolaan-optional"><?php echo esc_html( pijar_sakolaan_get_setting( 'homepage.stat_' . $sakolaan_i . '_value' ) ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-stat__label sakolaan-optional","metadata":{"bindings":{"content":{"source":"pijar-sakolaan/setting","args":{"key":"homepage.stat_<?php echo (int) $sakolaan_i; ?>_label"}}}}} -->
<p class="sakolaan-stat__label sakolaan-optional"><?php echo esc_html( pijar_sakolaan_get_setting( 'homepage.stat_' . $sakolaan_i . '_label' ) ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
<?php endfor; ?></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
