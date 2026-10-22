<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

final class Repository {
	private const ALLOWED_TABLES = array( 'institutions', 'institution_users', 'forms', 'form_versions', 'workflows', 'workflow_versions', 'programs', 'periods', 'document_requirements', 'applicants', 'applications', 'answers', 'documents', 'payments', 'rubrics', 'rubric_versions', 'reviewer_assignments', 'assessments', 'enrollments', 'status_history', 'announcements', 'notifications', 'audit_logs' );

	public static function get( string $table, int $id, ?int $institution_id = null ): ?array {
		global $wpdb;
		self::assert_table( $table );
		$sql  = 'SELECT * FROM ' . Installer::table( $table ) . ' WHERE id = %d';
		$args = array( $id );
		if ( null !== $institution_id && 'applicants' !== $table ) {
			$sql   .= ' AND institution_id = %d';
			$args[] = $institution_id;
		}
		$row = $wpdb->get_row( $wpdb->prepare( $sql, ...$args ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	public static function list( string $table, int $institution_id, array $where = array(), int $limit = 100 ): array {
		global $wpdb;
		self::assert_table( $table );
		if ( $institution_id < 1 ) {
			return array(); }
		$sql  = 'SELECT * FROM ' . Installer::table( $table ) . ' WHERE institution_id = %d';
		$args = array( $institution_id );
		foreach ( $where as $column => $value ) {
			if ( ! preg_match( '/^[a-z_]+$/', $column ) ) {
				continue; }
			$sql   .= " AND {$column} = %s";
			$args[] = (string) $value;
		}
		$sql   .= ' ORDER BY id DESC LIMIT %d';
		$args[] = max( 1, min( 500, $limit ) );
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A );
	}

	public static function insert( string $table, array $data ): int {
		global $wpdb;
		self::assert_table( $table );
		$result = $wpdb->insert( Installer::table( $table ), $data );
		return false === $result ? 0 : (int) $wpdb->insert_id;
	}

	public static function update( string $table, int $id, array $data, int $institution_id ): bool {
		global $wpdb;
		self::assert_table( $table );
		return false !== $wpdb->update(
			Installer::table( $table ),
			$data,
			array(
				'id'             => $id,
				'institution_id' => $institution_id,
			)
		);
	}

	public static function applicant_for_user( int $user_id, bool $create = false ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . Installer::table( 'applicants' ) . ' WHERE user_id = %d', $user_id ), ARRAY_A );
		if ( ! $row && $create ) {
			$now = current_time( 'mysql', true );
			$id  = self::insert(
				'applicants',
				array(
					'user_id'    => $user_id,
					'created_at' => $now,
					'updated_at' => $now,
				)
			);
			return $id ? self::get( 'applicants', $id ) : null;
		}
		return is_array( $row ) ? $row : null;
	}

	public static function application_owned_by_user( int $application_id, int $user_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT a.* FROM ' . Installer::table( 'applications' ) . ' a INNER JOIN ' . Installer::table( 'applicants' ) . ' p ON p.id = a.applicant_id WHERE a.id = %d AND p.user_id = %d',
				$application_id,
				$user_id
			),
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public static function audit( string $action, string $object_type, ?int $object_id, ?int $institution_id, array $context = array() ): void {
		$request_id = wp_generate_uuid4();
		$ip         = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		self::insert(
			'audit_logs',
			array(
				'institution_id' => $institution_id ? $institution_id : null,
				'actor_id'       => get_current_user_id() ? get_current_user_id() : null,
				'action'         => sanitize_key( $action ),
				'object_type'    => sanitize_key( $object_type ),
				'object_id'      => $object_id,
				'request_id'     => $request_id,
				'ip_hash'        => $ip ? hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ) : null,
				'context_json'   => wp_json_encode( $context ),
				'created_at'     => current_time( 'mysql', true ),
			)
		);
	}

	private static function assert_table( string $table ): void {
		if ( ! in_array( $table, self::ALLOWED_TABLES, true ) ) {
			throw new \InvalidArgumentException( 'Unsupported admission table.' );
		}
	}
}
