<?php
use PHPUnit\Framework\TestCase;
use Sakolaan\Admission\Configuration;

final class ConfigurationTest extends TestCase {
	public function test_period_effective_status_uses_publication_and_schedule(): void {
		$period = array(
			'status'         => 'published',
			'opens_at'       => '2026-05-01 00:00:00',
			'closes_at'      => '2026-06-30 23:59:59',
			'quota'          => 20,
			'enrolled_count' => 5,
		);
		$this->assertSame( 'upcoming', Configuration::effective_period( $period, 'published', 'active', strtotime( '2026-04-01 UTC' ) ) );
		$this->assertSame( 'open', Configuration::effective_period( $period, 'published', 'active', strtotime( '2026-06-01 UTC' ) ) );
		$this->assertSame( 'closed', Configuration::effective_period( $period, 'published', 'active', strtotime( '2026-07-01 UTC' ) ) );
		$this->assertSame( 'full', Configuration::effective_period( array_merge( $period, array( 'enrolled_count' => 20 ) ), 'published', 'active', strtotime( '2026-06-01 UTC' ) ) );
		$this->assertSame( 'draft', Configuration::effective_period( array_merge( $period, array( 'status' => 'draft' ) ), 'published', 'active' ) );
		$this->assertSame( 'unavailable', Configuration::effective_period( $period, 'draft', 'active' ) );
	}

	public function test_program_without_period_is_not_scheduled(): void {
		$program = array( 'id' => 7, 'status' => 'published', 'quota' => null, 'enrolled_count' => 0 );
		$this->assertSame( 'not_scheduled', Configuration::effective_program( $program, array(), 'active' ) );
		$this->assertSame( 'draft', Configuration::effective_program( array_merge( $program, array( 'status' => 'draft' ) ), array(), 'active' ) );
	}

	public function test_program_publication_requires_active_dependencies(): void {
		$program = array( 'institution_id' => 3 );
		$active = array( 'id' => 3, 'status' => 'active' );
		$form = array( 'institution_id' => 3, 'status' => 'published', 'current_version_id' => 11 );
		$workflow = array( 'institution_id' => 3, 'status' => 'published', 'current_version_id' => 12 );
		$this->assertTrue( Configuration::validate_program_publication( $program, $active, $form, $workflow ) );
		$this->assertTrue( is_wp_error( Configuration::validate_program_publication( $program, $active, array_merge( $form, array( 'current_version_id' => null ) ), $workflow ) ) );
		$this->assertSame( 'institution_inactive', Configuration::validate_program_publication( $program, array_merge( $active, array( 'status' => 'inactive' ) ), $form, $workflow )->get_error_code() );
		$this->assertSame( 'workflow_unpublished', Configuration::validate_program_publication( $program, $active, $form, array_merge( $workflow, array( 'status' => 'draft' ) ) )->get_error_code() );
	}

	public function test_period_publication_requires_published_parent_and_valid_dates(): void {
		$institution = array( 'id' => 3, 'status' => 'active' );
		$program = array( 'institution_id' => 3, 'status' => 'published' );
		$period = array( 'institution_id' => 3, 'opens_at' => '2026-05-01 00:00:00', 'closes_at' => '2026-06-01 00:00:00' );
		$this->assertTrue( Configuration::validate_period_publication( $period, $institution, $program ) );
		$this->assertTrue( is_wp_error( Configuration::validate_period_publication( $period, $institution, array_merge( $program, array( 'status' => 'draft' ) ) ) ) );
		$this->assertTrue( is_wp_error( Configuration::validate_period_publication( array_merge( $period, array( 'closes_at' => '2026-04-01 00:00:00' ) ), $institution, $program ) ) );
	}

	public function test_announcement_publication_requires_title_and_content(): void {
		$this->assertTrue( Configuration::validate_announcement_publication( array( 'title' => 'Hasil Seleksi', 'content' => '<p>Silakan lihat portal.</p>' ) ) );
		$this->assertSame( 'announcement_incomplete', Configuration::validate_announcement_publication( array( 'title' => '', 'content' => '<p></p>' ) )->get_error_code() );
	}
}
