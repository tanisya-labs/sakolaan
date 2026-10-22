<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; }

$settings = (array) get_option( 'sakolaan_admission_settings', array() );
if ( empty( $settings['delete_data_on_uninstall'] ) ) {
	return; }

global $wpdb;
$tables = array( 'audit_logs', 'notifications', 'announcements', 'status_history', 'enrollments', 'assessments', 'reviewer_assignments', 'rubric_versions', 'rubrics', 'payments', 'documents', 'answers', 'applications', 'applicants', 'document_requirements', 'periods', 'programs', 'workflow_versions', 'workflows', 'form_versions', 'forms', 'institution_users', 'institutions' );
foreach ( $tables as $table ) {
	$name = $wpdb->prefix . 'sakolaan_adm_' . $table;
	$wpdb->query( "DROP TABLE IF EXISTS `{$name}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$private_path = defined( 'SAKOLAAN_ADMISSION_PRIVATE_DIR' ) ? SAKOLAAN_ADMISSION_PRIVATE_DIR : dirname( rtrim( ABSPATH, '/\\' ) ) . DIRECTORY_SEPARATOR . 'sakolaan-admission-private';
$normalized   = wp_normalize_path( $private_path );
if ( is_dir( $private_path ) && ! str_starts_with( trailingslashit( $normalized ), trailingslashit( wp_normalize_path( ABSPATH ) ) ) && str_ends_with( $normalized, 'sakolaan-admission-private' ) ) {
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $private_path, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $iterator as $item ) {
		$item->isDir() ? rmdir( $item->getPathname() ) : wp_delete_file( $item->getPathname() ); }
	rmdir( $private_path );
}

foreach ( array( 'sakolaan_admission_schema_version', 'sakolaan_admission_version', 'sakolaan_admission_settings', 'sakolaan_admission_defaults_seeded', 'sakolaan_admission_mail_test_status' ) as $option ) {
	delete_option( $option ); }
$roles = array( 'admission_super_administrator', 'institution_administrator', 'admission_officer', 'document_verifier', 'payment_verifier', 'assessment_reviewer', 'admission_applicant' );
foreach ( $roles as $role_slug ) {
	remove_role( $role_slug ); }
$administrator = get_role( 'administrator' );
if ( $administrator ) {
	foreach ( array( 'sakolaan_admission_access_admin', 'sakolaan_admission_manage_settings', 'sakolaan_admission_manage_institutions', 'sakolaan_admission_manage_programs', 'sakolaan_admission_manage_forms', 'sakolaan_admission_manage_applications', 'sakolaan_admission_verify_documents', 'sakolaan_admission_verify_payments', 'sakolaan_admission_review_assessments', 'sakolaan_admission_publish_decisions', 'sakolaan_admission_confirm_enrollment', 'sakolaan_admission_manage_announcements', 'sakolaan_admission_view_reports', 'sakolaan_admission_view_audit', 'sakolaan_admission_manage_privacy' ) as $cap ) {
		$administrator->remove_cap( $cap ); }
}
