<?php
namespace Sakolaan\Admission;

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

final class Access {
	public static function register(): void {
		add_filter( 'sakolaan_admin_access_capabilities', array( __CLASS__, 'dashboard_capability' ) );
		add_filter( 'show_admin_bar', array( __CLASS__, 'hide_applicant_bar' ) );
		add_action( 'admin_init', array( __CLASS__, 'block_applicant_admin' ) );
	}

	public static function dashboard_capability( array $caps ): array {
		$caps[] = 'sakolaan_admission_access_admin';
		return array_values( array_unique( $caps ) );
	}

	public static function is_super( ?int $user_id = null ): bool {
		$user_id = $user_id ? $user_id : get_current_user_id();
		return user_can( $user_id, 'sakolaan_admission_manage_institutions' ) && user_can( $user_id, 'sakolaan_admission_manage_privacy' );
	}

	public static function institutions_for_user( ?int $user_id = null ): array {
		global $wpdb;
		$user_id = $user_id ? $user_id : get_current_user_id();
		if ( self::is_super( $user_id ) ) {
			return array_map( 'intval', $wpdb->get_col( 'SELECT id FROM ' . Installer::table( 'institutions' ) . " WHERE status = 'active'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT institution_id FROM ' . Installer::table( 'institution_users' ) . ' WHERE user_id = %d', $user_id ) ) );
	}

	public static function can_access_institution( int $institution_id, ?int $user_id = null ): bool {
		return $institution_id > 0 && in_array( $institution_id, self::institutions_for_user( $user_id ), true );
	}

	public static function current_institution(): int {
		$allowed = self::institutions_for_user();
		if ( ! $allowed ) {
			return 0; }
		$selected = isset( $_REQUEST['institution_id'] ) ? absint( wp_unslash( $_REQUEST['institution_id'] ) ) : absint( get_user_meta( get_current_user_id(), 'sakolaan_admission_institution', true ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $selected, $allowed, true ) ? $selected : $allowed[0];
	}

	public static function hide_applicant_bar( bool $show ): bool {
		$user = wp_get_current_user();
		return in_array( 'admission_applicant', (array) $user->roles, true ) && 1 === count( $user->roles ) ? false : $show;
	}

	public static function block_applicant_admin(): void {
		if ( wp_doing_ajax() || current_user_can( 'sakolaan_admission_access_admin' ) ) {
			return; }
		$user = wp_get_current_user();
		if ( in_array( 'admission_applicant', (array) $user->roles, true ) ) {
			wp_safe_redirect( home_url( '/ppdb/' ) );
			exit;
		}
	}
}
