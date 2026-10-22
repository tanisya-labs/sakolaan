<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

final class Privacy {
	public static function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'erasers' ) );
	}

	public static function exporters( array $exporters ): array {
		$exporters['sakolaan-admission'] = array(
			'exporter_friendly_name' => __( 'Penerimaan Sakolaan', 'sakolaan-admission' ),
			'callback'               => array( __CLASS__, 'export' ),
		);
		return $exporters;
	}

	public static function erasers( array $erasers ): array {
		$erasers['sakolaan-admission'] = array(
			'eraser_friendly_name' => __( 'Penerimaan Sakolaan', 'sakolaan-admission' ),
			'callback'             => array( __CLASS__, 'erase' ),
		);
		return $erasers;
	}

	public static function export( string $email, int $page = 1 ): array {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return array(
				'data' => array(),
				'done' => true,
			); }
		$applicant = Repository::applicant_for_user( (int) $user->ID );
		if ( ! $applicant ) {
			return array(
				'data' => array(),
				'done' => true,
			); }
		global $wpdb;
		$applications = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . Installer::table( 'applications' ) . ' WHERE applicant_id=%d ORDER BY id LIMIT 100 OFFSET %d', $applicant['id'], max( 0, $page - 1 ) * 100 ), ARRAY_A );
		$data         = array();
		foreach ( $applications as $app ) {
			$answers = $wpdb->get_results( $wpdb->prepare( 'SELECT field_key,value_json FROM ' . Installer::table( 'answers' ) . ' WHERE application_id=%d', $app['id'] ), ARRAY_A );
			$items   = array(
				array(
					'name'  => __( 'Nomor pendaftaran', 'sakolaan-admission' ),
					'value' => $app['application_number'],
				),
				array(
					'name'  => __( 'Status', 'sakolaan-admission' ),
					'value' => $app['status'],
				),
			);
			foreach ( $answers as $answer ) {
				$items[] = array(
					'name'  => $answer['field_key'],
					'value' => wp_json_encode( json_decode( $answer['value_json'], true ) ),
				); }
			$data[] = array(
				'group_id'    => 'sakolaan-admission',
				'group_label' => __( 'Pendaftaran penerimaan', 'sakolaan-admission' ),
				'item_id'     => 'application-' . $app['id'],
				'data'        => $items,
			);
		}
		Repository::audit( 'privacy_exported', 'applicant', (int) $applicant['id'], null, array( 'page' => $page ) );
		return array(
			'data' => $data,
			'done' => count( $applications ) < 100,
		);
	}

	public static function erase( string $email, int $page = 1 ): array {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			); }
		$applicant = Repository::applicant_for_user( (int) $user->ID );
		if ( ! $applicant ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			); }
		global $wpdb;
		$settings = (array) get_option( 'sakolaan_admission_settings', array() );
		$holds    = array_map( 'intval', (array) ( $settings['legal_hold_applications'] ?? array() ) );
		$apps     = $wpdb->get_results( $wpdb->prepare( 'SELECT id,institution_id FROM ' . Installer::table( 'applications' ) . ' WHERE applicant_id=%d ORDER BY id LIMIT 100 OFFSET %d', $applicant['id'], max( 0, $page - 1 ) * 100 ), ARRAY_A );
		$removed  = false;
		$retained = false;
		foreach ( $apps as $app ) {
			if ( in_array( (int) $app['id'], $holds, true ) ) {
				$retained = true;
				continue;
			} $wpdb->query( $wpdb->prepare( 'UPDATE ' . Installer::table( 'answers' ) . " SET value_json='null' WHERE application_id=%d", $app['id'] ) );
			Repository::audit( 'application_anonymized', 'application', (int) $app['id'], (int) $app['institution_id'] );
			$removed = true; }
		return array(
			'items_removed'  => $removed,
			'items_retained' => $retained,
			'messages'       => $retained ? array( __( 'Sebagian data dipertahankan karena sedang berada dalam penangguhan hukum.', 'sakolaan-admission' ) ) : array(),
			'done'           => count( $apps ) < 100,
		);
	}
}
