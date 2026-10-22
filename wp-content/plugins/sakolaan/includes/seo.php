<?php
/**
 * Metadata SEO, social preview, structured data, and sitemap integration.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Return public, queryable content types eligible for SEO fields. */
function sakolaan_seo_post_types() {
	$types = get_post_types( array( 'public' => true ), 'names' );
	unset( $types['attachment'] );
	$types = array_values( array_filter( $types, static function ( $type ) {
		return ! in_array( $type, array( 'hero_slide', 'berita' ), true ) && is_post_type_viewable( get_post_type_object( $type ) );
	} ) );
	return apply_filters( 'sakolaan_seo_post_types', $types );
}

/** Return public taxonomies eligible for SEO fields. */
function sakolaan_seo_taxonomies() {
	return apply_filters( 'sakolaan_seo_taxonomies', get_taxonomies( array( 'public' => true ), 'names' ) );
}

/** Register per-post SEO metadata in REST and Gutenberg. */
function sakolaan_seo_register_post_meta() {
	$fields = array(
		'_sakolaan_seo_title' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_text' ),
		'_sakolaan_seo_description' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_description' ),
		'_sakolaan_seo_image' => array( 'type' => 'integer', 'sanitize_callback' => 'sakolaan_seo_sanitize_image' ),
		'_sakolaan_seo_image_alt' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_text' ),
		'_sakolaan_seo_og_title' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_text' ),
		'_sakolaan_seo_og_description' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_description' ),
		'_sakolaan_seo_og_image' => array( 'type' => 'integer', 'sanitize_callback' => 'sakolaan_seo_sanitize_image' ),
		'_sakolaan_seo_og_image_alt' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_text' ),
		'_sakolaan_seo_canonical' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_url' ),
		'_sakolaan_seo_robots' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_robots' ),
	);
	foreach ( sakolaan_seo_post_types() as $post_type ) {
		foreach ( $fields as $key => $field ) {
			register_post_meta( $post_type, $key, array_merge( $field, array(
				'single' => true,
				'default' => 'integer' === $field['type'] ? 0 : '',
				'auth_callback' => 'sakolaan_seo_meta_auth',
				'show_in_rest' => true,
			) ) );
		}
	}
}
add_action( 'init', 'sakolaan_seo_register_post_meta', 20 );

/** Validate the user can edit the post whose SEO metadata is being changed. */
function sakolaan_seo_meta_auth( $allowed, $meta_key, $post_id ) {
	unset( $allowed, $meta_key );
	return current_user_can( 'edit_post', $post_id );
}

/** Sanitize an SEO image attachment ID. */
function sakolaan_seo_sanitize_image( $value ) {
	$id = is_scalar( $value ) ? absint( $value ) : 0;
	return $id && wp_attachment_is_image( $id ) ? $id : 0;
}

function sakolaan_seo_sanitize_text( $value ) {
	return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
}

function sakolaan_seo_sanitize_description( $value ) {
	return is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';
}

/** Sanitize an absolute HTTP(S) URL, returning empty when invalid. */
function sakolaan_seo_sanitize_url( $value ) {
	$value = is_scalar( $value ) ? trim( (string) $value ) : '';
	if ( '' === $value ) {
		return '';
	}
	$url = esc_url_raw( $value, array( 'http', 'https' ) );
	$parts = wp_parse_url( $url );
	return $url && is_array( $parts ) && in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) && ! empty( $parts['host'] ) ? $url : '';
}

/** Restrict robots override to supported combinations. */
function sakolaan_seo_sanitize_robots( $value ) {
	$value = is_scalar( $value ) ? sanitize_key( (string) $value ) : '';
	return in_array( $value, array( '', 'index_follow', 'index_nofollow', 'noindex_follow', 'noindex_nofollow' ), true ) ? $value : '';
}

/** Register the matching SEO meta contract for public terms. */
function sakolaan_seo_register_term_meta() {
	$fields = array(
	'sakolaan_seo_title' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_text' ),
	'sakolaan_seo_description' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_description' ),
		'sakolaan_seo_image' => array( 'type' => 'integer', 'sanitize_callback' => 'sakolaan_seo_sanitize_image' ),
	'sakolaan_seo_image_alt' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_text' ),
	'sakolaan_seo_og_title' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_text' ),
	'sakolaan_seo_og_description' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_description' ),
		'sakolaan_seo_og_image' => array( 'type' => 'integer', 'sanitize_callback' => 'sakolaan_seo_sanitize_image' ),
	'sakolaan_seo_og_image_alt' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_text' ),
		'sakolaan_seo_canonical' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_url' ),
		'sakolaan_seo_robots' => array( 'type' => 'string', 'sanitize_callback' => 'sakolaan_seo_sanitize_robots' ),
	);
	foreach ( sakolaan_seo_taxonomies() as $taxonomy ) {
		foreach ( $fields as $key => $field ) {
			register_term_meta( $taxonomy, $key, array_merge( $field, array( 'single' => true, 'default' => 'integer' === $field['type'] ? 0 : '', 'auth_callback' => 'sakolaan_seo_term_meta_auth', 'show_in_rest' => true ) ) );
		}
	}
}
add_action( 'init', 'sakolaan_seo_register_term_meta', 21 );

function sakolaan_seo_term_meta_auth( $allowed, $meta_key, $term_id ) {
	unset( $allowed, $meta_key );
	$term = get_term( $term_id );
	$taxonomy = $term && ! is_wp_error( $term ) ? get_taxonomy( $term->taxonomy ) : null;
	return $taxonomy && current_user_can( $taxonomy->cap->edit_terms );
}

/** Build Gutenberg SEO controls for the current public post type. */
function sakolaan_seo_editor_config() {
	$types = sakolaan_seo_post_types();
	$fields = array(
		array( 'key' => '_sakolaan_seo_title', 'label' => __( 'SEO title', 'sakolaan' ), 'control' => 'text' ),
		array( 'key' => '_sakolaan_seo_description', 'label' => __( 'Meta description', 'sakolaan' ), 'control' => 'textarea', 'help' => __( 'Kosongkan untuk memakai excerpt, lalu ringkasan konten.', 'sakolaan' ) ),
		array( 'key' => '_sakolaan_seo_image', 'label' => __( 'SEO / social image', 'sakolaan' ), 'control' => 'media', 'mediaTypes' => array( 'image' ) ),
		array( 'key' => '_sakolaan_seo_image_alt', 'label' => __( 'Alt text gambar SEO', 'sakolaan' ), 'control' => 'text' ),
		array( 'key' => '_sakolaan_seo_og_title', 'label' => __( 'OG / X title override', 'sakolaan' ), 'control' => 'text' ),
		array( 'key' => '_sakolaan_seo_og_description', 'label' => __( 'OG / X description override', 'sakolaan' ), 'control' => 'textarea' ),
		array( 'key' => '_sakolaan_seo_og_image', 'label' => __( 'OG / X image override', 'sakolaan' ), 'control' => 'media', 'mediaTypes' => array( 'image' ) ),
		array( 'key' => '_sakolaan_seo_og_image_alt', 'label' => __( 'Alt text OG / X image', 'sakolaan' ), 'control' => 'text' ),
		array( 'key' => '_sakolaan_seo_canonical', 'label' => __( 'Canonical URL', 'sakolaan' ), 'control' => 'url' ),
		array( 'key' => '_sakolaan_seo_robots', 'label' => __( 'Robots', 'sakolaan' ), 'control' => 'select', 'options' => array(
			array( 'label' => __( 'Gunakan default situs', 'sakolaan' ), 'value' => '' ),
			array( 'label' => 'index, follow', 'value' => 'index_follow' ),
			array( 'label' => 'index, nofollow', 'value' => 'index_nofollow' ),
			array( 'label' => 'noindex, follow', 'value' => 'noindex_follow' ),
			array( 'label' => 'noindex, nofollow', 'value' => 'noindex_nofollow' ),
		) ),
	);
	return array( 'postTypes' => $types, 'fields' => $fields );
}

/** Add the SEO panel to the existing editor script without mixing sakolaan-specific fields. */
function sakolaan_seo_enqueue_editor() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->post_type, sakolaan_seo_post_types(), true ) ) {
		return;
	}
	wp_add_inline_script( 'sakolaan-editor-panels', 'window.sakolaanSeo = ' . wp_json_encode( sakolaan_seo_editor_config(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
}
add_action( 'enqueue_block_editor_assets', 'sakolaan_seo_enqueue_editor', 20 );

/** Add SEO controls to public taxonomy create/edit forms. */
function sakolaan_seo_term_fields( $taxonomy ) {
	if ( ! in_array( $taxonomy, sakolaan_seo_taxonomies(), true ) ) {
		return;
	}
	$term_id = isset( $_GET['tag_ID'] ) ? absint( $_GET['tag_ID'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only form rendering.
	$values = array();
	foreach ( array( 'title', 'description', 'image', 'image_alt', 'og_title', 'og_description', 'og_image', 'og_image_alt', 'canonical', 'robots' ) as $key ) {
		$values[ $key ] = $term_id ? get_term_meta( $term_id, 'sakolaan_seo_' . $key, true ) : '';
	}
	$fields = array(
		'title' => __( 'SEO title', 'sakolaan' ), 'description' => __( 'Meta description', 'sakolaan' ),
		'image' => __( 'SEO / social image attachment ID', 'sakolaan' ), 'image_alt' => __( 'Alt text gambar SEO', 'sakolaan' ),
		'og_title' => __( 'OG / X title override', 'sakolaan' ), 'og_description' => __( 'OG / X description override', 'sakolaan' ),
		'og_image' => __( 'OG / X image attachment ID', 'sakolaan' ), 'og_image_alt' => __( 'Alt text OG / X image', 'sakolaan' ),
		'canonical' => __( 'Canonical URL', 'sakolaan' ),
	);
	$is_edit = false !== strpos( current_filter(), '_edit_form_fields' );
	foreach ( $fields as $key => $label ) {
		$id = 'sakolaan-seo-' . $taxonomy . '-' . $key;
		$open = $is_edit ? '<tr class="form-field sakolaan-seo-field"><th>' : '<div class="form-field sakolaan-seo-field"><label';
		$label_html = $is_edit ? '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td>' : ' for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
		$close = $is_edit ? '</td></tr>' : '</div>';
		if ( in_array( $key, array( 'image', 'og_image' ), true ) ) {
			$attachment_id = absint( $values[ $key ] );
			$preview = $attachment_id ? wp_get_attachment_image( $attachment_id, 'thumbnail', false, array( 'class' => 'sakolaan-seo-image-preview', 'alt' => '' ) ) : '';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Structural fragments are fixed markup; values and core image HTML are escaped upstream.
			printf( '%1$s%2$s<div class="sakolaan-seo-image" data-seo-image-field><input type="hidden" id="%3$s" name="sakolaan_seo[%4$s]" value="%5$s"><span data-seo-image-preview>%6$s</span><button type="button" class="button" data-seo-image-select>%7$s</button> <button type="button" class="button-link" data-seo-image-remove>%8$s</button></div>%9$s', $open, $label_html, esc_attr( $id ), esc_attr( $key ), esc_attr( (string) $attachment_id ), $preview, esc_html__( 'Pilih gambar', 'sakolaan' ), esc_html__( 'Hapus', 'sakolaan' ), $close );
			continue;
		}
		if ( in_array( $key, array( 'description', 'og_description' ), true ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Structural fragments are fixed markup and all values are escaped.
			printf( '%1$s%2$s<textarea id="%3$s" name="sakolaan_seo[%4$s]" rows="3" class="large-text">%5$s</textarea>%6$s', $open, $label_html, esc_attr( $id ), esc_attr( $key ), esc_textarea( (string) $values[ $key ] ), $close );
		} else {
			$type = false !== strpos( $key, 'image' ) ? 'number' : ( 'canonical' === $key ? 'url' : 'text' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Structural fragments are fixed markup and all values are escaped.
			printf( '%1$s%2$s<input id="%3$s" type="%4$s" name="sakolaan_seo[%5$s]" value="%6$s" class="regular-text"%7$s>%8$s', $open, $label_html, esc_attr( $id ), esc_attr( $type ), esc_attr( $key ), esc_attr( (string) $values[ $key ] ), 'number' === $type ? ' min="0"' : '', $close );
		}
	}
	$id = 'sakolaan-seo-' . $taxonomy . '-robots';
	printf( '%1$s%2$s<select id="%3$s" name="sakolaan_seo[robots]">', $is_edit ? '<tr class="form-field sakolaan-seo-field"><th>' : '<div class="form-field sakolaan-seo-field"><label', $is_edit ? '<label for="' . esc_attr( $id ) . '">' . esc_html__( 'Robots', 'sakolaan' ) . '</label></th><td>' : ' for="' . esc_attr( $id ) . '">' . esc_html__( 'Robots', 'sakolaan' ) . '</label>', esc_attr( $id ) );
	foreach ( array( '' => __( 'Gunakan default situs', 'sakolaan' ), 'index_follow' => 'index, follow', 'index_nofollow' => 'index, nofollow', 'noindex_follow' => 'noindex, follow', 'noindex_nofollow' => 'noindex, nofollow' ) as $value => $label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $value ), selected( $values['robots'], $value, false ), esc_html( $label ) );
	}
	echo $is_edit ? '</select></td></tr>' : '</select></div>';
	if ( $is_edit ) {
		echo '<tr class="sakolaan-seo-nonce" hidden><td colspan="2">';
		wp_nonce_field( 'sakolaan_save_term_seo', 'sakolaan_seo_nonce' );
		echo '</td></tr>';
	} else {
		wp_nonce_field( 'sakolaan_save_term_seo', 'sakolaan_seo_nonce' );
	}
}
function sakolaan_seo_add_term_fields( $taxonomy ) { sakolaan_seo_term_fields( $taxonomy ); }
function sakolaan_seo_edit_term_fields( $term, $taxonomy ) { unset( $term ); sakolaan_seo_term_fields( $taxonomy ); }
function sakolaan_seo_register_taxonomy_hooks() {
	foreach ( sakolaan_seo_taxonomies() as $taxonomy ) {
		add_action( $taxonomy . '_add_form_fields', 'sakolaan_seo_add_term_fields' );
		add_action( $taxonomy . '_edit_form_fields', 'sakolaan_seo_edit_term_fields', 10, 2 );
		add_action( 'created_' . $taxonomy, 'sakolaan_seo_save_term_fields' );
		add_action( 'edited_' . $taxonomy, 'sakolaan_seo_save_term_fields' );
	}
}
add_action( 'init', 'sakolaan_seo_register_taxonomy_hooks', 30 );

/** Keep the retired news CPT out of the native sitemap; posts are canonical news content. */
function sakolaan_seo_sitemap_post_types( $post_types ) {
	unset( $post_types['berita'], $post_types['hero_slide'] );
	return $post_types;
}
add_filter( 'wp_sitemaps_post_types', 'sakolaan_seo_sitemap_post_types' );

/** Load the Media Library picker on public taxonomy edit screens. */
function sakolaan_seo_enqueue_term_media( $hook ) {
	if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
		return;
	}
	$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only asset routing.
	if ( ! in_array( $taxonomy, sakolaan_seo_taxonomies(), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'jquery' );
	sakolaan_enqueue_admin_style();
	$script = <<<'JS'
jQuery(function($){
	$(document).on('click','[data-seo-image-select]',function(e){
		e.preventDefault();
		var field=$(this).closest('[data-seo-image-field]');
		var frame=wp.media({title:__SEO_TITLE__,button:{text:__SEO_BUTTON__},library:{type:'image'},multiple:false});
		frame.on('select',function(){
			var item=frame.state().get('selection').first().toJSON();
			field.find('input').val(item.id);
			field.find('[data-seo-image-preview]').empty();
			if(item.sizes&&item.sizes.thumbnail){field.find('[data-seo-image-preview]').append($('<img>',{class:'sakolaan-seo-image-preview',alt:'',src:item.sizes.thumbnail.url}));}
		});
		frame.open();
	});
	$(document).on('click','[data-seo-image-remove]',function(e){e.preventDefault();var field=$(this).closest('[data-seo-image-field]');field.find('input').val('0');field.find('[data-seo-image-preview]').empty();});
});
JS;
	$script = str_replace( array( '__SEO_TITLE__', '__SEO_BUTTON__' ), array( wp_json_encode( __( 'Pilih gambar SEO', 'sakolaan' ) ), wp_json_encode( __( 'Gunakan gambar', 'sakolaan' ) ) ), $script );
	wp_add_inline_script( 'jquery', $script, 'after' );
}
add_action( 'admin_enqueue_scripts', 'sakolaan_seo_enqueue_term_media' );

/** Save sanitized taxonomy SEO fields. */
function sakolaan_seo_save_term_fields( $term_id ) {
	$nonce = isset( $_POST['sakolaan_seo_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['sakolaan_seo_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'sakolaan_save_term_seo' ) ) {
		return;
	}
	$taxonomy = '';
	$current_hook = current_filter();
	foreach ( sakolaan_seo_taxonomies() as $registered_taxonomy ) {
		if ( in_array( $current_hook, array( 'created_' . $registered_taxonomy, 'edited_' . $registered_taxonomy ), true ) ) {
			$taxonomy = $registered_taxonomy;
			break;
		}
	}
	$taxonomy_object = get_taxonomy( $taxonomy );
	if ( ! in_array( $taxonomy, sakolaan_seo_taxonomies(), true ) || ! $taxonomy_object || ! current_user_can( $taxonomy_object->cap->edit_terms ) ) {
		return;
	}
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Each supported field is sanitized according to its schema below.
	$input = isset( $_POST['sakolaan_seo'] ) && is_array( $_POST['sakolaan_seo'] ) ? wp_unslash( $_POST['sakolaan_seo'] ) : array();
	foreach ( array( 'title', 'description', 'image', 'image_alt', 'og_title', 'og_description', 'og_image', 'og_image_alt', 'canonical', 'robots' ) as $key ) {
		$value = isset( $input[ $key ] ) ? $input[ $key ] : '';
		if ( in_array( $key, array( 'description', 'og_description' ), true ) ) {
			$value = sakolaan_seo_sanitize_description( $value );
		} elseif ( in_array( $key, array( 'image', 'og_image' ), true ) ) {
			$value = sakolaan_seo_sanitize_image( $value );
		} elseif ( 'canonical' === $key ) {
			$value = sakolaan_seo_sanitize_url( $value );
		} elseif ( 'robots' === $key ) {
			$value = sakolaan_seo_sanitize_robots( $value );
		} else {
			$value = sakolaan_seo_sanitize_text( $value );
		}
		update_term_meta( $term_id, 'sakolaan_seo_' . $key, $value );
	}
}

/** Current SEO subject (post, term, or archive). */
function sakolaan_seo_subject() {
	if ( is_singular() ) {
		return get_queried_object();
	}
	if ( is_category() || is_tag() || is_tax() ) {
		return get_queried_object();
	}
	return null;
}

/** Fetch a current-object SEO field with global fallback. */
function sakolaan_seo_value( $key, $default = '' ) {
	$subject = sakolaan_seo_subject();
	if ( $subject instanceof WP_Post ) {
		$value = get_post_meta( $subject->ID, '_sakolaan_seo_' . $key, true );
	} elseif ( $subject instanceof WP_Term ) {
		$value = get_term_meta( $subject->term_id, 'sakolaan_seo_' . $key, true );
	} else {
		$value = '';
	}
	return is_scalar( $value ) && '' !== (string) $value ? $value : $default;
}

/** Extract readable description content with a stable length cap. */
function sakolaan_seo_excerpt( $text ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $text ) ) ) );
	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $text, 0, 160 );
	}
	return substr( $text, 0, 160 );
}

/** Resolve title and description for the active request. */
function sakolaan_seo_title_description() {
	$site_name = (string) sakolaan_get_setting( 'identity.name', get_bloginfo( 'name' ) );
	$title = '';
	$description = '';
	$subject = sakolaan_seo_subject();
	if ( $subject instanceof WP_Post ) {
		$title = sakolaan_seo_value( 'title' );
		$title = $title ? $title : get_the_title( $subject );
		$description = sakolaan_seo_value( 'description' );
		if ( ! $description ) {
			$description = has_excerpt( $subject ) ? get_the_excerpt( $subject ) : sakolaan_seo_excerpt( $subject->post_content );
		}
	} elseif ( $subject instanceof WP_Term ) {
		$title = sakolaan_seo_value( 'title', $subject->name );
		$description = sakolaan_seo_value( 'description', $subject->description );
	} elseif ( is_front_page() ) {
		$title = (string) sakolaan_get_setting( 'seo.default_title', '' );
		$title = $title ? $title : $site_name;
		$description = (string) sakolaan_get_setting( 'seo.default_description', '' );
	} elseif ( is_post_type_archive() || is_category() || is_tag() || is_tax() || is_archive() ) {
		$title = wp_strip_all_tags( get_the_archive_title() );
		$description = (string) get_the_archive_description();
	} else {
		$title = (string) sakolaan_get_setting( 'seo.default_title', '' );
		$title = $title ? $title : $site_name;
		$description = (string) sakolaan_get_setting( 'seo.default_description', '' );
	}
	if ( ! $description ) {
		$description = (string) sakolaan_get_setting( 'seo.default_description', get_bloginfo( 'description' ) );
	}
	$template = (string) sakolaan_get_setting( 'seo.title_template', '%title% | %site_name%' );
	if ( $title && ! is_front_page() && ! ( $subject instanceof WP_Term && sakolaan_seo_value( 'title' ) ) && ! ( $subject instanceof WP_Post && sakolaan_seo_value( 'title' ) ) ) {
		$title = str_replace( array( '%title%', '%site_name%' ), array( $title, $site_name ), $template );
	}
	return array( 'title' => trim( $title ), 'description' => sakolaan_seo_excerpt( $description ) );
}

/** Use the SEO title through core's title-tag mechanism. */
function sakolaan_seo_document_title( $title ) {
	if ( is_admin() || sakolaan_seo_plugin_active() ) {
		return $title;
	}
	$data = sakolaan_seo_title_description();
	return $data['title'] ? $data['title'] : $title;
}
add_filter( 'pre_get_document_title', 'sakolaan_seo_document_title', 20 );

/** Determine if an external SEO plugin owns front-end metadata. */
function sakolaan_seo_plugin_active() {
	$active = defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' );
	return (bool) apply_filters( 'sakolaan_seo_external_plugin_active', $active );
}

/** Current canonical URL, including validated custom override. */
function sakolaan_seo_canonical() {
	$custom = sakolaan_seo_value( 'canonical' );
	if ( $custom && sakolaan_seo_sanitize_url( $custom ) ) {
		return $custom;
	}
	$subject = sakolaan_seo_subject();
	if ( $subject instanceof WP_Post ) {
		return get_permalink( $subject );
	}
	if ( $subject instanceof WP_Term ) {
		$link = get_term_link( $subject );
		return is_wp_error( $link ) ? '' : $link;
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_post_type_archive() || is_archive() || is_home() ) {
		return get_pagenum_link( max( 1, absint( get_query_var( 'paged' ) ) ) );
	}
	return '';
}
function sakolaan_seo_filter_canonical( $canonical, $post ) {
	if ( sakolaan_seo_plugin_active() || ! $post ) {
		return $canonical;
	}
	$custom = get_post_meta( $post->ID, '_sakolaan_seo_canonical', true );
	return $custom && sakolaan_seo_sanitize_url( $custom ) ? $custom : $canonical;
}
add_filter( 'get_canonical_url', 'sakolaan_seo_filter_canonical', 20, 2 );

/** Apply per-object robots directives while preserving WordPress core rules. */
function sakolaan_seo_robots( $robots ) {
	if ( sakolaan_seo_plugin_active() ) {
		return $robots;
	}
	$value = sakolaan_seo_value( 'robots' );
	if ( ! $value ) {
		return $robots;
	}
	$subject = sakolaan_seo_subject();
	if ( is_preview() || ( $subject instanceof WP_Post && ( 'publish' !== $subject->post_status || post_password_required( $subject ) ) ) ) {
		return $robots;
	}
	$parts = explode( '_', $value );
	if ( ! get_option( 'blog_public' ) && 'index' === $parts[0] ) {
		return $robots;
	}
	$robots['noindex'] = 'noindex' === $parts[0];
	$robots['nofollow'] = 'nofollow' === $parts[1];
	return $robots;
}
add_filter( 'wp_robots', 'sakolaan_seo_robots', 20 );

/** Resolve image URL/alt with SEO, featured image, then global fallback. */
function sakolaan_seo_image_data( $social = false ) {
	$subject = sakolaan_seo_subject();
	$image_id = (int) sakolaan_seo_value( $social ? 'og_image' : 'image' );
	$alt = (string) sakolaan_seo_value( $social ? 'og_image_alt' : 'image_alt' );
	if ( ! $image_id && $social ) {
		$image_id = (int) sakolaan_seo_value( 'image' );
		$alt = $alt ? $alt : (string) sakolaan_seo_value( 'image_alt' );
	}
	if ( ! $image_id && $subject instanceof WP_Post && has_post_thumbnail( $subject ) ) {
		$image_id = get_post_thumbnail_id( $subject );
	}
	if ( ! $image_id ) {
		$image_id = absint( sakolaan_get_setting( 'seo.default_image', 0 ) );
		$alt = $alt ? $alt : (string) sakolaan_get_setting( 'seo.default_image_alt', '' );
	}
	$url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
	if ( $image_id && ! $alt ) {
		$alt = (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true );
	}
	return array( 'url' => $url ? $url : '', 'alt' => $alt, 'id' => $image_id );
}

/** Render social tags and JSON-LD in the initial HTML response. */
function sakolaan_seo_render_head() {
	if ( is_admin() || is_feed() || is_robots() || sakolaan_seo_plugin_active() || is_404() || is_search() || is_preview() ) {
		return;
	}
	$data = sakolaan_seo_title_description();
	$url = sakolaan_seo_canonical();
	if ( ! is_singular() && $url ) {
		printf( "<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $url ) );
	}
	$og_title = (string) sakolaan_seo_value( 'og_title', $data['title'] );
	$og_description = (string) sakolaan_seo_value( 'og_description', $data['description'] );
	$image = sakolaan_seo_image_data( true );
	$subject = sakolaan_seo_subject();
	$type = $subject instanceof WP_Post && 'post' === $subject->post_type ? 'article' : 'website';
	if ( $data['description'] ) {
		printf( "\n<meta name=\"description\" content=\"%s\" />\n", esc_attr( $data['description'] ) );
	}
	printf( "<meta property=\"og:title\" content=\"%s\" />\n<meta property=\"og:type\" content=\"%s\" />\n", esc_attr( $og_title ), esc_attr( $type ) );
	if ( $og_description ) { printf( "<meta property=\"og:description\" content=\"%s\" />\n", esc_attr( $og_description ) ); }
	if ( $url ) { printf( "<meta property=\"og:url\" content=\"%s\" />\n", esc_url( $url ) ); }
	if ( $image['url'] ) {
		printf( "<meta property=\"og:image\" content=\"%s\" />\n<meta property=\"og:image:alt\" content=\"%s\" />\n", esc_url( $image['url'] ), esc_attr( $image['alt'] ) );
	}
	printf( "<meta name=\"twitter:card\" content=\"%s\" />\n<meta name=\"twitter:title\" content=\"%s\" />\n", $image['url'] ? 'summary_large_image' : 'summary', esc_attr( $og_title ) );
	if ( $og_description ) { printf( "<meta name=\"twitter:description\" content=\"%s\" />\n", esc_attr( $og_description ) ); }
	if ( $image['url'] ) { printf( "<meta name=\"twitter:image\" content=\"%s\" />\n<meta name=\"twitter:image:alt\" content=\"%s\" />\n", esc_url( $image['url'] ), esc_attr( $image['alt'] ) ); }

	$graphs = array( sakolaan_seo_organization_schema() );
	$page = array( '@type' => 'WebPage', '@id' => $url . '#webpage', 'url' => $url, 'name' => $data['title'], 'description' => $data['description'], 'isPartOf' => array( '@id' => home_url( '/#website' ) ) );
	if ( $subject instanceof WP_Post && 'post' === $subject->post_type ) {
		$page['mainEntity'] = array( '@type' => 'BlogPosting', 'headline' => $data['title'], 'datePublished' => get_the_date( DATE_W3C, $subject ), 'dateModified' => get_the_modified_date( DATE_W3C, $subject ), 'author' => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', $subject->post_author ) ), 'mainEntityOfPage' => $url );
		if ( $image['url'] ) { $page['mainEntity']['image'] = $image['url']; }
	}
	$graphs[] = $page;
	$graphs[] = array( '@type' => 'WebSite', '@id' => home_url( '/#website' ), 'url' => home_url( '/' ), 'name' => (string) sakolaan_get_setting( 'identity.name', get_bloginfo( 'name' ) ), 'publisher' => array( '@id' => home_url( '/#school' ) ) );
	$breadcrumbs = sakolaan_seo_breadcrumb_schema();
	if ( $breadcrumbs ) { $graphs[] = $breadcrumbs; }
	$schema = array( '@context' => 'https://schema.org', '@graph' => array_values( array_filter( $graphs ) ) );
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with HTML-safe flags.
}
add_action( 'wp_head', 'sakolaan_seo_render_head', 2 );

/** Organization node based on existing school identity/contact settings. */
function sakolaan_seo_organization_schema() {
	$name = (string) sakolaan_get_setting( 'identity.name', get_bloginfo( 'name' ) );
	$node = array( '@type' => 'School', '@id' => home_url( '/#school' ), 'name' => $name, 'url' => home_url( '/' ) );
	$logo = absint( sakolaan_get_setting( 'identity.logo', 0 ) );
	if ( $logo ) { $node['logo'] = wp_get_attachment_image_url( $logo, 'full' ); }
	$npsn = (string) sakolaan_get_setting( 'identity.npsn', '' );
	if ( $npsn ) { $node['identifier'] = array( '@type' => 'PropertyValue', 'propertyID' => 'NPSN', 'value' => $npsn ); }
	$founded = (string) sakolaan_get_setting( 'identity.founded_year', '' );
	if ( $founded ) { $node['foundingDate'] = $founded; }
	$accreditation = (string) sakolaan_get_setting( 'identity.accreditation', '' );
	if ( $accreditation ) { $node['award'] = $accreditation; }
	$address = (string) sakolaan_get_setting( 'contact.address', '' );
	if ( $address ) { $node['address'] = array( '@type' => 'PostalAddress', 'streetAddress' => $address ); }
	$phone = (string) sakolaan_get_setting( 'contact.phone', '' );
	$email = (string) sakolaan_get_setting( 'contact.email', '' );
	if ( $phone ) { $node['telephone'] = $phone; }
	if ( $email ) { $node['email'] = $email; }
	$map = (string) sakolaan_get_setting( 'contact.maps_url', '' );
	if ( $map ) { $node['hasMap'] = $map; }
	$same_as = array();
	foreach ( array( 'facebook', 'instagram', 'youtube', 'tiktok' ) as $network ) {
		$url = (string) sakolaan_get_setting( 'social.' . $network, '' );
		if ( $url ) { $same_as[] = $url; }
	}
	if ( $same_as ) { $node['sameAs'] = $same_as; }
	return $node;
}

/** Build breadcrumb schema from active theme breadcrumb data when available. */
function sakolaan_seo_breadcrumb_schema() {
	if ( ! function_exists( 'pijar_sakolaan_get_breadcrumb_items' ) ) { return null; }
	$items = pijar_sakolaan_get_breadcrumb_items();
	if ( count( $items ) < 2 ) { return null; }
	$list = array();
	foreach ( array_values( $items ) as $index => $item ) {
		$entry = array( '@type' => 'ListItem', 'position' => $index + 1, 'name' => wp_strip_all_tags( $item['label'] ) );
		if ( ! empty( $item['url'] ) ) { $entry['item'] = $item['url']; }
		$list[] = $entry;
	}
	return array( '@type' => 'BreadcrumbList', 'itemListElement' => $list );
}

/** Tell Pijar Sakolaan not to emit its standalone breadcrumb schema. */
function sakolaan_seo_owns_theme_schema( $active ) { return $active || ! sakolaan_seo_plugin_active(); }
add_filter( 'pijar_sakolaan_has_seo_plugin', 'sakolaan_seo_owns_theme_schema' );

/** Tell native sitemap queries to honor per-object noindex. */
function sakolaan_seo_sitemap_post_query( $args, $post_type ) {
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Sitemap exclusion requires the registered per-object robots metadata.
	$args['meta_query'] = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
	$args['meta_query'][] = array( 'relation' => 'OR', array( 'key' => '_sakolaan_seo_robots', 'compare' => 'NOT EXISTS' ), array( 'key' => '_sakolaan_seo_robots', 'value' => array( '', 'index_follow', 'index_nofollow' ), 'compare' => 'IN' ) );
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'sakolaan_seo_sitemap_post_query', 10, 2 );

/** Exclude explicit noindex terms from the native term sitemap. */
function sakolaan_seo_sitemap_term_query( $args, $taxonomy ) {
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Sitemap exclusion requires the registered per-term robots metadata.
	$args['meta_query'] = isset( $args['meta_query'] ) ? $args['meta_query'] : array();
	$args['meta_query'][] = array( 'relation' => 'OR', array( 'key' => 'sakolaan_seo_robots', 'compare' => 'NOT EXISTS' ), array( 'key' => 'sakolaan_seo_robots', 'value' => array( '', 'index_follow', 'index_nofollow' ), 'compare' => 'IN' ) );
	return $args;
}
add_filter( 'wp_sitemaps_taxonomies_query_args', 'sakolaan_seo_sitemap_term_query', 10, 2 );

