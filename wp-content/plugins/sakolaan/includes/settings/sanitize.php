<?php
/**
 * Sanitasi pengaturan.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitasi satu nilai berdasarkan tipe field.
 *
 * @param mixed  $value     Nilai mentah.
 * @param array  $field     Definisi field.
 * @param string $field_key Key unik untuk pesan validasi.
 * @return mixed
 */
function sakolaan_sanitize_field( $value, $field, $field_key = '' ) {
	if ( 'quick_links' === $field['type'] ) {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$output = array();
		foreach ( array_slice( $value, 0, 20 ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$raw_label = isset( $item['label'] ) && is_scalar( $item['label'] ) ? (string) $item['label'] : '';
			$raw_url   = isset( $item['url'] ) && is_scalar( $item['url'] ) ? trim( (string) $item['url'] ) : '';
			$label     = sanitize_text_field( $raw_label );
			$url       = esc_url_raw( $raw_url, array( 'http', 'https' ) );

			if ( '' !== $raw_url && '' === $url ) {
				add_settings_error( 'sakolaan_settings', 'sakolaan_invalid_quick_link', __( 'Salah satu URL akses cepat tidak valid.', 'sakolaan' ), 'error' );
			}

			if ( '' === $label || '' === $url ) {
				continue;
			}

			$icon_options = array( '', 'book', 'calendar', 'bell', 'graduation', 'phone', 'school', 'external' );
			$icon        = isset( $item['icon'] ) && is_scalar( $item['icon'] ) ? sanitize_key( (string) $item['icon'] ) : '';
			$icon_id     = isset( $item['icon_id'] ) ? absint( $item['icon_id'] ) : 0;

			if ( ! in_array( $icon, $icon_options, true ) ) {
				$icon = '';
			}

			if ( $icon_id && ( 'attachment' !== get_post_type( $icon_id ) || ! wp_attachment_is_image( $icon_id ) ) ) {
				add_settings_error( 'sakolaan_settings', 'sakolaan_invalid_quick_link_icon', __( 'Ikon akses cepat harus berupa gambar yang tersedia di Media Library.', 'sakolaan' ), 'error' );
				$icon_id = 0;
			}

			$output[] = array(
				'label'   => $label,
				'url'     => $url,
				'enabled' => ! empty( $item['enabled'] ),
				'icon'    => $icon,
				'icon_id' => $icon_id,
				'icon_alt' => sanitize_text_field( isset( $item['icon_alt'] ) && is_scalar( $item['icon_alt'] ) ? (string) $item['icon_alt'] : '' ),
			);
		}

		return $output;
	}

	if ( 'section_order' === $field['type'] ) {
		$allowed = array_keys( sakolaan_get_homepage_section_labels() );
		$items   = is_array( $value ) ? $value : array();
		$output  = array();

		foreach ( $items as $item ) {
			$item = sanitize_key( is_scalar( $item ) ? (string) $item : '' );

			if ( in_array( $item, $allowed, true ) && ! in_array( $item, $output, true ) ) {
				$output[] = $item;
			}
		}

		return array_merge( $output, array_values( array_diff( $allowed, $output ) ) );
	}

	if ( ! empty( $field['sanitize'] ) && is_callable( $field['sanitize'] ) ) {
		$sanitized = call_user_func( $field['sanitize'], $value );

		if ( is_scalar( $value ) && '' !== trim( (string) $value ) && '' === $sanitized ) {
			add_settings_error(
				'sakolaan_settings',
				'sakolaan_invalid_' . sanitize_key( $field_key ),
				sprintf(
					/* translators: %s: field label. */
					__( 'Nilai untuk “%s” tidak valid.', 'sakolaan' ),
					isset( $field['label'] ) ? $field['label'] : $field_key
				),
				'error'
			);
		}

		return $sanitized;
	}

	if ( 'checkbox' === $field['type'] ) {
		return ! empty( $value );
	}

	if ( ! is_scalar( $value ) ) {
		return sakolaan_get_field_default( $field );
	}

	$value = (string) $value;
	$code  = 'sakolaan_invalid_' . sanitize_key( $field_key ? $field_key : $field['type'] );

	switch ( $field['type'] ) {
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'url':
			$value = trim( $value );

			if ( '' !== $value && ! wp_http_validate_url( $value ) ) {
				add_settings_error( 'sakolaan_settings', $code, __( 'Tautan tidak valid. Gunakan alamat lengkap yang diawali http:// atau https://.', 'sakolaan' ), 'error' );
				return sakolaan_get_field_default( $field );
			}

			return esc_url_raw( $value, array( 'http', 'https' ) );
		case 'email':
			$value = trim( $value );

			if ( '' !== $value && ! is_email( $value ) ) {
				add_settings_error( 'sakolaan_settings', $code, __( 'Alamat email tidak valid.', 'sakolaan' ), 'error' );
				return sakolaan_get_field_default( $field );
			}

			return sanitize_email( $value );
		case 'number':
			if ( '' === trim( $value ) ) {
				return sakolaan_get_field_default( $field );
			}

			$number = absint( $value );

			if ( isset( $field['min'] ) ) {
				$number = max( (int) $field['min'], $number );
			}

			if ( isset( $field['max'] ) ) {
				$number = min( (int) $field['max'], $number );
			}

			return $number;
		case 'image':
			$attachment_id = absint( $value );

			if ( '' !== trim( $value ) && ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) ) {
				add_settings_error( 'sakolaan_settings', $code, __( 'Gambar yang dipilih tidak valid atau sudah tidak tersedia.', 'sakolaan' ), 'error' );
				return sakolaan_get_field_default( $field );
			}

			return $attachment_id;
		case 'select':
			$value   = sanitize_key( $value );
			$options = isset( $field['options'] ) && is_array( $field['options'] ) ? $field['options'] : array();

			return array_key_exists( $value, $options ) ? $value : sakolaan_get_field_default( $field );
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Sanitize callback untuk option sakolaan_settings.
 *
 * Form dibagi per tab. Hanya group yang dikirim (ditandai "__present") yang diproses;
 * group lain tetap memakai nilai tersimpan.
 *
 * Hidden companion "{key}_field" menandai field yang benar-benar ada pada panel.
 * Ini mencegah form parsial mereset field lain dalam group yang sama; checkbox
 * tanpa nilai tetapi memiliki marker diproses sebagai false.
 *
 * @param mixed $input Data dari form (sudah di-unslash oleh WordPress).
 * @return array
 */
function sakolaan_sanitize_settings( $input ) {
	// Input bukan array (null, dsb.): kembalikan data tersimpan (atau default).
	if ( ! is_array( $input ) ) {
		return get_option( 'sakolaan_settings', sakolaan_get_settings_defaults() );
	}

	/*
	 * Sanitasi ganda (double sanitize): WordPress memanggil sanitize_option()
	 * lagi di dalam add_option() dengan nilai yang sudah disanitasi. Kiriman form
	 * selalu punya penanda "__present" per group; nilai hasil sanitasi tidak pernah
	 * memilikinya. Jika tidak ada penanda sama sekali, nilai sudah bersih dan
	 * dikembalikan apa adanya agar data tidak ter-reset/terhapus.
	 */
	$has_marker = false;

	foreach ( $input as $group => $fields ) {
		if ( is_array( $fields ) && ! empty( $fields['__present'] ) ) {
			$has_marker = true;
			break;
		}
	}

	if ( ! $has_marker ) {
		return $input;
	}

	/*
	 * Basis: schema default digabung dengan nilai tersimpan (bila ada).
	 * Gunakan merge manual per group per field, bukan array_replace_recursive,
	 * agar field yang tidak ada di saved tetap mempertahankan nilai dari defaults,
	 * bukan dari saved (yang mungkin partial/ tidak lengkap).
	 */
	$saved    = get_option( 'sakolaan_settings', array() );
	$saved    = is_array( $saved ) ? $saved : array();
	$defaults = sakolaan_get_settings_defaults();
	$output   = $defaults;

	// Merge saved data ke dalam defaults — hanya field yang ada di schema.
	foreach ( $defaults as $group => $fields ) {
		if ( ! isset( $saved[ $group ] ) || ! is_array( $saved[ $group ] ) ) {
			continue;
		}
		foreach ( $fields as $key => $default_value ) {
			if ( array_key_exists( $key, $saved[ $group ] ) ) {
				$output[ $group ][ $key ] = $saved[ $group ][ $key ];
			}
		}
	}

	foreach ( sakolaan_get_settings_schema() as $group => $definition ) {
		if ( empty( $input[ $group ] ) || ! is_array( $input[ $group ] ) || empty( $input[ $group ]['__present'] ) ) {
			continue;
		}

		// Hapus __present agar tidak ikut tersimpan.
		unset( $input[ $group ]['__present'] );

		foreach ( $definition['fields'] as $key => $field ) {
			// Setiap form mengirim marker agar panel parsial tidak mereset field lain.
			$field_marker = $key . '_field';
			if ( ! isset( $input[ $group ][ $field_marker ] ) ) {
				continue;
			}
			unset( $input[ $group ][ $field_marker ] );
			$raw = isset( $input[ $group ][ $key ] ) ? $input[ $group ][ $key ] : '';

			$output[ $group ][ $key ] = sakolaan_sanitize_field( $raw, $field, $group . '_' . $key );
		}

		// Hapus sisa key non-field (berakhiran _field) agar tidak tersimpan.
		foreach ( array_keys( $input[ $group ] ) as $extra_key ) {
			if ( '_field' === substr( $extra_key, -6 ) ) {
				unset( $input[ $group ][ $extra_key ] );
			}
		}
	}

	return $output;
}
