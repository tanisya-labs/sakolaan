<?php
/**
 * Title: Panduan gaya (design system)
 * Slug: sakolaan-theme/design-system
 * Categories: sakolaan-sections
 * Description: Contoh warna, tipografi, tombol, kartu, badge, dan alert untuk memeriksa design system.
 * Keywords: design system, panduan gaya, style guide
 * Viewport Width: 1200
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:group {"className":"sakolaan-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group sakolaan-section"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading"><?php esc_html_e( 'Panduan gaya', 'sakolaan-theme' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Halaman ini memeriksa warna, tipografi, dan komponen dasar. Hapus halaman ini setelah selesai diperiksa.', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Warna', 'sakolaan-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"sakolaan-grid sakolaan-grid--4","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-grid sakolaan-grid--4"><!-- wp:group {"className":"sakolaan-swatch","backgroundColor":"primary","textColor":"surface","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-swatch has-surface-color has-primary-background-color has-text-color has-background"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Primer', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sakolaan-swatch","backgroundColor":"primary-dark","textColor":"surface","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-swatch has-surface-color has-primary-dark-background-color has-text-color has-background"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Primer gelap', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sakolaan-swatch","backgroundColor":"secondary","textColor":"surface","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-swatch has-surface-color has-secondary-background-color has-text-color has-background"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Sekunder', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sakolaan-swatch","backgroundColor":"accent","textColor":"primary-dark","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-swatch has-primary-dark-color has-accent-background-color has-text-color has-background"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Aksen', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sakolaan-swatch","backgroundColor":"highlight","textColor":"primary-dark","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-swatch has-primary-dark-color has-highlight-background-color has-text-color has-background"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Sorotan', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sakolaan-swatch","backgroundColor":"primary-tint","textColor":"primary-dark","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-swatch has-primary-dark-color has-primary-tint-background-color has-text-color has-background"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Primer muda', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sakolaan-swatch","backgroundColor":"surface","textColor":"text","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-swatch has-text-color has-surface-background-color has-background"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Permukaan', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sakolaan-swatch","backgroundColor":"success","textColor":"surface","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-swatch has-surface-color has-success-background-color has-text-color has-background"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Sukses', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"sakolaan-swatch","backgroundColor":"danger","textColor":"surface","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-swatch has-surface-color has-danger-background-color has-text-color has-background"><!-- wp:paragraph -->
<p><?php esc_html_e( 'Bahaya', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Tipografi', 'sakolaan-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php esc_html_e( 'Judul tingkat 3 memakai Public Sans tebal', 'sakolaan-theme' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Paragraf isi memakai font sans-serif dengan tinggi baris 1,65 agar nyaman dibaca. Tautan ditampilkan', 'sakolaan-theme' ); ?> <a href="#"><?php esc_html_e( 'dengan garis bawah', 'sakolaan-theme' ); ?></a>.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Tombol', 'sakolaan-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Utama', 'sakolaan-theme' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Garis tepi', 'sakolaan-theme' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-accent"} -->
<div class="wp-block-button is-style-accent"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Aksen', 'sakolaan-theme' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Kartu, badge, dan alert', 'sakolaan-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"sakolaan-grid sakolaan-grid--2","layout":{"type":"default"}} -->
<div class="wp-block-group sakolaan-grid sakolaan-grid--2"><!-- wp:group {"className":"is-style-sakolaan-card sakolaan-card--link","layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-sakolaan-card sakolaan-card--link"><!-- wp:paragraph {"className":"sakolaan-badge"} -->
<p class="sakolaan-badge"><?php esc_html_e( 'Kategori', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php esc_html_e( 'Judul kartu', 'sakolaan-theme' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"sakolaan-meta"} -->
<p class="sakolaan-meta"><?php esc_html_e( 'Tanggal terbit', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-sakolaan-dark","style":{"spacing":{"padding":{"top":"var:preset|spacing|lg","bottom":"var:preset|spacing|lg","left":"var:preset|spacing|lg","right":"var:preset|spacing|lg"}},"border":{"radius":"var(--wp--custom--radius--md)"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group is-style-sakolaan-dark" style="border-radius:var(--wp--custom--radius--md);padding-top:var(--wp--preset--spacing--lg);padding-right:var(--wp--preset--spacing--lg);padding-bottom:var(--wp--preset--spacing--lg);padding-left:var(--wp--preset--spacing--lg)"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php esc_html_e( 'Latar gelap', 'sakolaan-theme' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Teks, tautan, dan fokus keyboard menyesuaikan latar gelap.', 'sakolaan-theme' ); ?> <a href="#"><?php esc_html_e( 'Contoh tautan', 'sakolaan-theme' ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"className":"sakolaan-alert sakolaan-alert--success"} -->
<p class="sakolaan-alert sakolaan-alert--success"><?php esc_html_e( 'Pendaftaran dibuka. Lengkapi berkas sebelum batas waktu.', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"sakolaan-alert sakolaan-alert--warning"} -->
<p class="sakolaan-alert sakolaan-alert--warning"><?php esc_html_e( 'Jadwal dapat berubah. Periksa pengumuman terbaru.', 'sakolaan-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:pattern {"slug":"sakolaan-theme/empty-state"} /--></div>
<!-- /wp:group -->
