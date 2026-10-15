<?php
/**
 * Visibilitas section beranda.
 *
 * Block dengan class "sakolaan-section--{slug}" dikendalikan oleh Pengaturan Sakolaan
 * (tab "Section beranda") dan oleh ketersediaan datanya. Section yang dimatikan atau
 * belum punya data tidak dirender sama sekali: tidak ada HTML kosong dan query di
 * dalamnya tidak dijalankan.
 *
 * @package Sakolaan_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Apakah section diaktifkan di Pengaturan Sakolaan. Aktif secara default.
 *
 * @param string $slug Slug section, misalnya "news".
 * @return bool
 */
function sakolaan_theme_is_section_enabled( $slug ) {
	return (bool) sakolaan_theme_get_setting( 'sections.show_' . $slug, true );
}

/**
 * Section yang dapat diurutkan dan pattern sumbernya.
 *
 * @return array<string,string>
 */
function sakolaan_theme_get_homepage_section_patterns() {
	return array(
		'quick_links'     => 'sakolaan-theme/quick-links',
		'introduction'    => 'sakolaan-theme/sakolaan-introduction',
		'news'            => 'sakolaan-theme/latest-news',
		'announcements'   => 'sakolaan-theme/announcements',
		'events'          => 'sakolaan-theme/events',
		'statistics'      => 'sakolaan-theme/statistics',
		'principal'       => 'sakolaan-theme/principal-greeting',
		'achievements'    => 'sakolaan-theme/achievements',
		'facilities'      => 'sakolaan-theme/facilities',
		'programs'        => 'sakolaan-theme/featured-programs',
		'extracurricular' => 'sakolaan-theme/extracurricular',
		'testimonials'    => 'sakolaan-theme/testimonials',
		'gallery'         => 'sakolaan-theme/gallery',
		'ppdb'            => 'sakolaan-theme/admission-cta',
	);
}

/**
 * Normalisasi urutan tersimpan agar lengkap dan tanpa duplikat.
 *
 * @return string[]
 */
function sakolaan_theme_get_homepage_section_order() {
	$allowed = array_keys( sakolaan_theme_get_homepage_section_patterns() );
	$saved   = sakolaan_theme_get_setting( 'sections.order', $allowed );
	$order   = array();

	if ( is_array( $saved ) ) {
		foreach ( $saved as $slug ) {
			$slug = sanitize_key( is_scalar( $slug ) ? (string) $slug : '' );

			if ( in_array( $slug, $allowed, true ) && ! in_array( $slug, $order, true ) ) {
				$order[] = $slug;
			}
		}
	}

	return array_merge( $order, array_values( array_diff( $allowed, $order ) ) );
}

/** Render ikon quick link dari preset SVG yang diizinkan. */
function sakolaan_theme_get_quick_link_icon( $icon ) {
	$paths = array(
		'book'       => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5z"/><path d="M4 5.5v16M8 7h8m-8 4h8"/>',
		'calendar'   => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
		'bell'       => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
		'graduation' => '<path d="m2 10 10-6 10 6-10 6zM6 12.5V17c3.5 3 8.5 3 12 0v-4.5M22 10v6"/>',
		'phone'      => '<path d="M7 3H5a2 2 0 0 0-2 2c0 8.8 7.2 16 16 16a2 2 0 0 0 2-2v-2l-5-2-2 3a14 14 0 0 1-7-7l3-2z"/>',
		'school'     => '<path d="m3 9 9-5 9 5-9 5zM5 11v6c4 3 10 3 14 0v-6M21 9v7"/>',
		'external'   => '<path d="M14 3h7v7M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
	);

	if ( ! isset( $paths[ $icon ] ) ) {
		return '';
	}

	return '<svg class="sakolaan-quick-link__icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">' . $paths[ $icon ] . '</svg>';
}

/**
 * Render pattern beranda sesuai urutan setting.
 *
 * @return string
 */
function sakolaan_theme_render_homepage_sections( $layouts = array() ) {
	$patterns = sakolaan_theme_get_homepage_section_patterns();
	$registry = WP_Block_Patterns_Registry::get_instance();
	$output   = '';

	foreach ( sakolaan_theme_get_homepage_section_order() as $slug ) {
		if ( ! sakolaan_theme_is_section_enabled( $slug ) || ! sakolaan_theme_section_has_content( $slug ) ) {
			continue;
		}

		$pattern = $registry->get_registered( $patterns[ $slug ] );

		if ( empty( $pattern['content'] ) ) {
			continue;
		}

		$blocks = parse_blocks( $pattern['content'] );
		$blocks = sakolaan_theme_apply_homepage_section_layout( $blocks, $slug, is_array( $layouts ) && isset( $layouts[ $slug ] ) ? $layouts[ $slug ] : array() );
		$output .= do_blocks( serialize_blocks( $blocks ) );
	}

	return $output;
}

/** Apply Site Editor layout choices to a section's structural block class. */
function sakolaan_theme_apply_homepage_section_layout( $blocks, $slug, $layout ) {
	$columns = isset( $layout['columns'] ) && in_array( (string) $layout['columns'], array( '2', '3', '4', '5' ), true ) ? (string) $layout['columns'] : '';
	$card    = isset( $layout['card'] ) && in_array( $layout['card'], array( 'standard', 'compact', 'feature' ), true ) ? $layout['card'] : '';
	$image   = isset( $layout['image'] ) && in_array( $layout['image'], array( 'top', 'left', 'right' ), true ) ? $layout['image'] : '';
	$style   = 'quick_links' === $slug && isset( $layout['style'] ) && in_array( $layout['style'], array( 'link', 'pill', 'card' ), true ) ? $layout['style'] : '';
	$align   = 'quick_links' === $slug && isset( $layout['alignment'] ) && in_array( $layout['alignment'], array( 'start', 'center' ), true ) ? $layout['alignment'] : '';
	$target  = 'quick_links' === $slug ? 'sakolaan-quick-links' : 'sakolaan-section--' . str_replace( '_', '-', $slug );

	foreach ( $blocks as &$block ) {
		$class = isset( $block['attrs']['className'] ) ? (string) $block['attrs']['className'] : '';
		if ( false !== strpos( $class, $target ) ) {
			$additions = array();
			if ( $columns ) { $additions[] = 'sakolaan-home-layout--cols-' . $columns; }
			if ( 'quick_links' === $slug ) {
				if ( $style ) { $additions[] = 'sakolaan-home-layout--style-' . $style; }
				if ( $align ) { $additions[] = 'sakolaan-home-layout--align-' . $align; }
			} else {
				if ( $card ) { $additions[] = 'sakolaan-home-layout--card-' . $card; }
				if ( $image ) { $additions[] = 'sakolaan-home-layout--image-' . $image; }
			}
			$block['attrs']['className'] = trim( $class . ' ' . implode( ' ', $additions ) );

			// Patterns contain static HTML, so synchronize the class comment and saved markup.
			if ( ! empty( $additions ) && ! empty( $block['innerContent'] ) ) {
				foreach ( $block['innerContent'] as $index => $chunk ) {
					if ( ! is_string( $chunk ) || '' === trim( $chunk ) ) {
						continue;
					}

					$processor = new WP_HTML_Tag_Processor( $chunk );
					if ( $processor->next_tag() ) {
						$current = (string) $processor->get_attribute( 'class' );
						$processor->set_attribute( 'class', trim( $current . ' ' . implode( ' ', $additions ) ) );
						$block['innerContent'][ $index ] = $processor->get_updated_html();
					}
					break;
				}
			}
			break;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$block['innerBlocks'] = sakolaan_theme_apply_homepage_section_layout( $block['innerBlocks'], $slug, $layout );
		}
	}
	unset( $block );
	return $blocks;
}

/**
 * Apakah section punya data minimum untuk ditampilkan.
 *
 * Section berbasis konten (berita, agenda, dan seterusnya) selalu dianggap punya
 * data karena menampilkan empty state sendiri.
 *
 * @param string $slug Slug section.
 * @return bool
 */
function sakolaan_theme_section_has_content( $slug ) {
	switch ( $slug ) {
		case 'introduction':
			$has = '' !== trim( (string) sakolaan_theme_get_setting( 'homepage.intro_text', '' ) )
				|| absint( sakolaan_theme_get_setting( 'homepage.intro_image', 0 ) ) > 0;
			break;

		case 'principal':
			$has = '' !== trim( (string) sakolaan_theme_get_setting( 'principal.name', '' ) )
				|| '' !== trim( (string) sakolaan_theme_get_setting( 'principal.greeting', '' ) );
			break;

		case 'statistics':
			$has = false;

			for ( $i = 1; $i <= 5; $i++ ) {
				if ( '' !== trim( (string) sakolaan_theme_get_setting( 'homepage.stat_' . $i . '_value', '' ) ) ) {
					$has = true;
					break;
				}
			}
			break;

		case 'ppdb':
			$has = '' !== (string) sakolaan_theme_get_setting( 'ppdb.url' );
			break;

		default:
			$has = true;
	}

	/**
	 * Filter ketersediaan data section.
	 *
	 * @param bool   $has  Apakah section punya data.
	 * @param string $slug Slug section.
	 */
	return (bool) apply_filters( 'sakolaan_theme_section_has_content', $has, $slug );
}

/**
 * Lewati render section yang dimatikan atau belum punya data.
 *
 * @param string|null $pre_render  Hasil render sebelumnya.
 * @param array       $parsed_block Data block.
 * @return string|null
 */
function sakolaan_theme_skip_disabled_sections( $pre_render, $parsed_block ) {
	if ( ! sakolaan_theme_is_compatible() ) {
		return $pre_render;
	}

	if ( null !== $pre_render || empty( $parsed_block['attrs']['className'] ) ) {
		return $pre_render;
	}

	if ( ! preg_match( '/(?:^|\s)sakolaan-section--([a-z-]+)(?:\s|$)/', $parsed_block['attrs']['className'], $matches ) ) {
		return $pre_render;
	}

	$slug = str_replace( '-', '_', $matches[1] );

	if ( ! sakolaan_theme_is_section_enabled( $slug ) || ! sakolaan_theme_section_has_content( $slug ) ) {
		return '';
	}

	return $pre_render;
}
add_filter( 'pre_render_block', 'sakolaan_theme_skip_disabled_sections', 10, 2 );
