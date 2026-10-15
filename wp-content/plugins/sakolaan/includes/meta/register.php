<?php
/**
 * Registrasi meta dari skema.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daftarkan semua meta dengan sanitasi dan pengecekan capability.
 *
 * @return void
 */
function sakolaan_register_meta() {
	foreach ( sakolaan_get_meta_schema() as $post_type => $group ) {
		foreach ( $group['fields'] as $key => $field ) {
			$args = array(
				'type'              => $field['type'],
				'single'            => true,
				'default'           => isset( $field['default'] ) ? $field['default'] : ( 'integer' === $field['type'] ? 0 : '' ),
				'sanitize_callback' => $field['sanitize'],
				'auth_callback'     => 'sakolaan_meta_auth',
				'show_in_rest'      => true,
			);

			if ( 'array' === $field['type'] ) {
				$args['show_in_rest'] = array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				);
			}

			register_post_meta( $post_type, '_sakolaan_' . $key, $args );
		}
	}
}

/**
 * Izin edit meta: harus bisa mengedit post terkait.
 *
 * @param bool   $allowed  Status izin saat ini.
 * @param string $meta_key Meta key.
 * @param int    $post_id  ID post.
 * @return bool
 */
function sakolaan_meta_auth( $allowed, $meta_key, $post_id ) {
	unset( $allowed, $meta_key );

	return current_user_can( 'edit_post', $post_id );
}
