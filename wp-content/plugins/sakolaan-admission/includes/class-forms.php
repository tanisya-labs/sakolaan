<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

final class Forms {
	public static function field_types(): array {
		return (array) apply_filters( 'sakolaan_admission_form_field_types', array( 'text', 'textarea', 'email', 'phone', 'number', 'date', 'select', 'radio', 'checkbox', 'file', 'heading', 'info', 'conditional_group' ) );
	}

	public static function normalize_schema( array $schema ) {
		$normalized = array(
			'schema_version' => 1,
			'steps'          => array(),
		);
		$seen       = array();
		foreach ( (array) ( $schema['steps'] ?? array() ) as $step_index => $step ) {
			$step_id = sanitize_key( $step['id'] ?? 'step-' . ( $step_index + 1 ) );
			$fields  = array();
			foreach ( (array) ( $step['fields'] ?? array() ) as $field ) {
				$type = sanitize_key( $field['type'] ?? 'text' );
				$key  = sanitize_key( $field['key'] ?? '' );
				if ( ! in_array( $type, self::field_types(), true ) || ( ! in_array( $type, array( 'heading', 'info' ), true ) && ( ! $key || isset( $seen[ $key ] ) ) ) ) {
					continue;
				}
				if ( $key ) {
					$seen[ $key ] = true; }
				$item = array(
					'key'         => $key,
					'type'        => $type,
					'label'       => sanitize_text_field( $field['label'] ?? '' ),
					'help'        => sanitize_text_field( $field['help'] ?? '' ),
					'placeholder' => sanitize_text_field( $field['placeholder'] ?? '' ),
					'required'    => ! empty( $field['required'] ),
					'options'     => array(),
					'validation'  => array(),
					'visibility'  => array(),
				);
				foreach ( (array) ( $field['options'] ?? array() ) as $option ) {
					if ( is_array( $option ) ) {
						$item['options'][] = array(
							'value' => sanitize_key( $option['value'] ?? '' ),
							'label' => sanitize_text_field( $option['label'] ?? '' ),
						);
					}
				}
				foreach ( array( 'min', 'max', 'min_length', 'max_length', 'pattern' ) as $rule ) {
					if ( isset( $field['validation'][ $rule ] ) ) {
						$item['validation'][ $rule ] = sanitize_text_field( (string) $field['validation'][ $rule ] ); }
				}
				if ( ! empty( $field['visibility']['field'] ) ) {
					$item['visibility'] = array(
						'field'    => sanitize_key( $field['visibility']['field'] ),
						'operator' => in_array( $field['visibility']['operator'] ?? '', array( 'equals', 'not_equals', 'contains' ), true ) ? $field['visibility']['operator'] : 'equals',
						'value'    => sanitize_text_field( (string) ( $field['visibility']['value'] ?? '' ) ),
					);
				}
				$fields[] = $item;
			}
			$normalized['steps'][] = array(
				'id'     => $step_id,
				/* translators: %d: form step number. */
				'label'  => sanitize_text_field( $step['label'] ?? sprintf( __( 'Langkah %d', 'sakolaan-admission' ), $step_index + 1 ) ),
				'fields' => $fields,
			);
		}
		return $normalized['steps'] ? $normalized : new \WP_Error( 'invalid_form_schema', __( 'Formulir harus memiliki setidaknya satu langkah yang valid.', 'sakolaan-admission' ) );
	}

	public static function validate_answers( array $schema, array $answers, bool $is_final = false ) {
		$clean  = array();
		$errors = new \WP_Error();
		foreach ( $schema['steps'] as $step ) {
			foreach ( $step['fields'] as $field ) {
				$key = $field['key'];
				if ( ! $key || in_array( $field['type'], array( 'heading', 'info', 'conditional_group' ), true ) ) {
					continue; }
				if ( ! self::is_visible( $field, $answers ) ) {
					continue; }
				$value = $answers[ $key ] ?? null;
				$empty = null === $value || '' === $value || array() === $value;
				if ( $is_final && $field['required'] && $empty ) {
					/* translators: %s: form field label. */
					$errors->add( $key, sprintf( __( '%s wajib diisi.', 'sakolaan-admission' ), $field['label'] ) );
					continue;
				}
				if ( $empty ) {
					$clean[ $key ] = '';
					continue; }
				$sanitized = self::sanitize_value( $field, $value );
				if ( is_wp_error( $sanitized ) ) {
					$errors->add( $key, $sanitized->get_error_message() );
					continue; }
				$clean[ $key ] = $sanitized;
			}
		}
		return $errors->has_errors() ? $errors : $clean;
	}

	private static function is_visible( array $field, array $answers ): bool {
		$rule = $field['visibility'] ?? array();
		if ( empty( $rule['field'] ) ) {
			return true; }
		$actual   = $answers[ $rule['field'] ] ?? '';
		$expected = $rule['value'] ?? '';
		return match ( $rule['operator'] ?? 'equals' ) {
			'not_equals' => (string) $actual !== (string) $expected,
			'contains'   => is_array( $actual ) ? in_array( $expected, $actual, true ) : str_contains( (string) $actual, (string) $expected ),
			default      => (string) $actual === (string) $expected,
		};
	}

	private static function sanitize_value( array $field, $value ) {
		$type = $field['type'];
		if ( 'checkbox' === $type ) {
			$allowed = array_column( $field['options'], 'value' );
			return array_values( array_intersect( array_map( 'sanitize_key', (array) $value ), $allowed ) );
		}
		$value = is_scalar( $value ) ? wp_unslash( (string) $value ) : '';
		if ( 'email' === $type ) {
			$value = sanitize_email( $value );
			if ( ! is_email( $value ) ) {
				return new \WP_Error( 'invalid_email', __( 'Masukkan alamat email yang valid.', 'sakolaan-admission' ) ); }
		} elseif ( 'number' === $type ) {
			if ( ! is_numeric( $value ) ) {
				return new \WP_Error( 'invalid_number', __( 'Masukkan angka yang valid.', 'sakolaan-admission' ) ); }
			$value = (float) $value;
		} elseif ( 'date' === $type ) {
			$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', $value );
			if ( ! $date || $date->format( 'Y-m-d' ) !== $value ) {
				return new \WP_Error( 'invalid_date', __( 'Masukkan tanggal yang valid.', 'sakolaan-admission' ) ); }
		} elseif ( in_array( $type, array( 'select', 'radio' ), true ) ) {
			$value = sanitize_key( $value );
			if ( ! in_array( $value, array_column( $field['options'], 'value' ), true ) ) {
				return new \WP_Error( 'invalid_option', __( 'Pilih opsi yang valid.', 'sakolaan-admission' ) ); }
		} elseif ( 'textarea' === $type ) {
			$value = sanitize_textarea_field( $value );
		} else {
			$value = sanitize_text_field( $value );
		}
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value ) : strlen( (string) $value );
		if ( isset( $field['validation']['min_length'] ) && $length < (int) $field['validation']['min_length'] ) {
			return new \WP_Error( 'too_short', __( 'Isian terlalu pendek.', 'sakolaan-admission' ) ); }
		if ( isset( $field['validation']['max_length'] ) && $length > (int) $field['validation']['max_length'] ) {
			return new \WP_Error( 'too_long', __( 'Isian terlalu panjang.', 'sakolaan-admission' ) ); }
		if ( 'number' === $type && isset( $field['validation']['min'] ) && $value < (float) $field['validation']['min'] ) {
			return new \WP_Error( 'too_small', __( 'Angka lebih kecil daripada batas minimum.', 'sakolaan-admission' ) ); }
		if ( 'number' === $type && isset( $field['validation']['max'] ) && $value > (float) $field['validation']['max'] ) {
			return new \WP_Error( 'too_large', __( 'Angka lebih besar daripada batas maksimum.', 'sakolaan-admission' ) ); }
		return $value;
	}

	public static function templates(): array {
		$base = static function ( array $extra = array() ): array {
			return array(
				'schema_version' => 1,
				'steps'          => array(
					array(
						'id'     => 'identity',
						'label'  => __( 'Identitas Pendaftar', 'sakolaan-admission' ),
						'fields' => array_merge(
							array(
								array(
									'key'      => 'full_name',
									'type'     => 'text',
									'label'    => __( 'Nama lengkap', 'sakolaan-admission' ),
									'required' => true,
								),
								array(
									'key'      => 'email',
									'type'     => 'email',
									'label'    => __( 'Alamat email', 'sakolaan-admission' ),
									'required' => true,
								),
								array(
									'key'      => 'phone',
									'type'     => 'phone',
									'label'    => __( 'Nomor telepon', 'sakolaan-admission' ),
									'required' => true,
								),
							),
							$extra
						),
					),
					array(
						'id'     => 'review',
						'label'  => __( 'Informasi Tambahan', 'sakolaan-admission' ),
						'fields' => array(
							array(
								'key'      => 'notes',
								'type'     => 'textarea',
								'label'    => __( 'Catatan', 'sakolaan-admission' ),
								'required' => false,
							),
						),
					),
				),
			);
		};
		return array(
			'school'          => $base(
				array(
					array(
						'key'      => 'birth_date',
						'type'     => 'date',
						'label'    => __( 'Tanggal lahir', 'sakolaan-admission' ),
						'required' => true,
					),
					array(
						'key'   => 'previous_school',
						'type'  => 'text',
						'label' => __( 'Institusi asal', 'sakolaan-admission' ),
					),
				)
			),
			'university'      => $base(
				array(
					array(
						'key'      => 'qualification',
						'type'     => 'text',
						'label'    => __( 'Kualifikasi pendidikan terakhir', 'sakolaan-admission' ),
						'required' => true,
					),
					array(
						'key'   => 'study_interest',
						'type'  => 'text',
						'label' => __( 'Bidang studi yang diminati', 'sakolaan-admission' ),
					),
				)
			),
			'boarding-school' => $base(
				array(
					array(
						'key'      => 'guardian_name',
						'type'     => 'text',
						'label'    => __( 'Nama orang tua atau wali', 'sakolaan-admission' ),
						'required' => true,
					),
					array(
						'key'   => 'medical_notes',
						'type'  => 'textarea',
						'label' => __( 'Catatan kesehatan', 'sakolaan-admission' ),
					),
				)
			),
			'training'        => $base(
				array(
					array(
						'key'   => 'experience',
						'type'  => 'textarea',
						'label' => __( 'Pengalaman yang relevan', 'sakolaan-admission' ),
					),
				)
			),
			'minimal'         => $base(),
		);
	}

	public static function template_label( string $key ): string {
		$labels = array(
			'school'          => __( 'Penerimaan sekolah umum', 'sakolaan-admission' ),
			'university'      => __( 'Penerimaan perguruan tinggi', 'sakolaan-admission' ),
			'boarding-school' => __( 'Penerimaan pesantren atau asrama', 'sakolaan-admission' ),
			'training'        => __( 'Pendaftaran pelatihan atau kursus', 'sakolaan-admission' ),
			'minimal'         => __( 'Pendaftaran umum sederhana', 'sakolaan-admission' ),
		);
		return $labels[ $key ] ?? Labels::status( $key );
	}

	/**
	 * Identify an untouched English starter template from releases before 0.1.0.
	 *
	 * The comparison is intentionally strict. Administrator-edited schemas must
	 * never be replaced automatically, even when their field keys still match.
	 */
	public static function legacy_template_key( array $schema ): ?string {
		foreach ( self::legacy_templates() as $key => $template ) {
			if ( $template === $schema ) {
				return $key;
			}
		}
		return null;
	}

	private static function legacy_templates(): array {
		$base = static function ( array $extra = array() ): array {
			return array(
				'schema_version' => 1,
				'steps'          => array(
					array(
						'id'     => 'identity',
						'label'  => 'Identity',
						'fields' => array_merge(
							array(
								array(
									'key'      => 'full_name',
									'type'     => 'text',
									'label'    => 'Full name',
									'required' => true,
								),
								array(
									'key'      => 'email',
									'type'     => 'email',
									'label'    => 'Email',
									'required' => true,
								),
								array(
									'key'      => 'phone',
									'type'     => 'phone',
									'label'    => 'Phone',
									'required' => true,
								),
							),
							$extra
						),
					),
					array(
						'id'     => 'review',
						'label'  => 'Additional information',
						'fields' => array(
							array(
								'key'      => 'notes',
								'type'     => 'textarea',
								'label'    => 'Notes',
								'required' => false,
							),
						),
					),
				),
			);
		};

		return array(
			'school'          => $base(
				array(
					array(
						'key'      => 'birth_date',
						'type'     => 'date',
						'label'    => 'Birth date',
						'required' => true,
					),
					array(
						'key'   => 'previous_school',
						'type'  => 'text',
						'label' => 'Previous institution',
					),
				)
			),
			'university'      => $base(
				array(
					array(
						'key'      => 'qualification',
						'type'     => 'text',
						'label'    => 'Previous qualification',
						'required' => true,
					),
					array(
						'key'   => 'study_interest',
						'type'  => 'text',
						'label' => 'Study interest',
					),
				)
			),
			'boarding-school' => $base(
				array(
					array(
						'key'      => 'guardian_name',
						'type'     => 'text',
						'label'    => 'Guardian name',
						'required' => true,
					),
					array(
						'key'   => 'medical_notes',
						'type'  => 'textarea',
						'label' => 'Medical notes',
					),
				)
			),
			'training'        => $base(
				array(
					array(
						'key'   => 'experience',
						'type'  => 'textarea',
						'label' => 'Relevant experience',
					),
				)
			),
			'minimal'         => $base(),
		);
	}
}
