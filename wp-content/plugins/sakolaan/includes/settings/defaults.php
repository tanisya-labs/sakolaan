<?php
/**
 * Default pengaturan, diturunkan dari skema.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default untuk satu field.
 *
 * @param array $field Definisi field.
 * @return mixed
 */
function sakolaan_get_field_default( $field ) {
	if ( array_key_exists( 'default', $field ) ) {
		return $field['default'];
	}

	switch ( $field['type'] ) {
		case 'checkbox':
			return false;
		case 'image':
			return 0;
		default:
			return '';
	}
}

/**
 * Default seluruh pengaturan.
 *
 * @return array
 */
function sakolaan_get_settings_defaults() {
	$defaults = array();

	foreach ( sakolaan_get_settings_schema() as $group => $definition ) {
		foreach ( $definition['fields'] as $key => $field ) {
			$defaults[ $group ][ $key ] = sakolaan_get_field_default( $field );
		}
	}

	return $defaults;
}
