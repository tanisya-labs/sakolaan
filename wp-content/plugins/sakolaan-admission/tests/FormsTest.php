<?php
use PHPUnit\Framework\TestCase;
use Sakolaan\Admission\Forms;
use Sakolaan\Admission\Workflow;

final class FormsTest extends TestCase {
	public function test_all_starter_templates_normalize(): void {
		$this->assertCount( 5, Forms::templates() );
		foreach ( Forms::templates() as $template ) {
			$normalized = Forms::normalize_schema( $template );
			$this->assertFalse( is_wp_error( $normalized ) );
			$this->assertNotEmpty( $normalized['steps'] );
		}
	}

	public function test_required_and_email_validation(): void {
		$schema = Forms::normalize_schema( Forms::templates()['minimal'] );
		$missing = Forms::validate_answers( $schema, array(), true );
		$this->assertTrue( is_wp_error( $missing ) );
		$valid = Forms::validate_answers( $schema, array( 'full_name' => 'Alya', 'email' => 'alya@example.test', 'phone' => '+62 812', 'notes' => '' ), true );
		$this->assertFalse( is_wp_error( $valid ) );
		$this->assertSame( 'alya@example.test', $valid['email'] );
	}

	public function test_workflows_do_not_allow_direct_draft_acceptance(): void {
		foreach ( array( Workflow::default_definition(), Workflow::default_definition( true ) ) as $workflow ) {
			foreach ( $workflow['transitions'] as $transition ) {
				$this->assertFalse( 'draft' === $transition['from'] && 'accepted' === $transition['to'] );
			}
		}
	}
}
