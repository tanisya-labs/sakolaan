<?php
use PHPUnit\Framework\TestCase;
use Sakolaan\Admission\Availability;

final class AvailabilityTest extends TestCase {
	private function row( array $overrides = array() ): array {
		return array_merge(
			array(
				'institution_status' => 'active',
				'program_status'     => 'published',
				'period_status'      => 'published',
				'program_quota'      => 100,
				'program_enrolled'   => 20,
				'period_quota'       => 50,
				'period_enrolled'    => 10,
				'opens_at'           => '2026-01-01 00:00:00',
				'closes_at'          => '2026-12-31 23:59:59',
			),
			$overrides
		);
	}

	public function test_open_period_is_available(): void {
		$this->assertSame( 'open', Availability::classify( $this->row(), strtotime( '2026-06-01 UTC' ) ) );
	}

	public function test_future_period_is_upcoming(): void {
		$this->assertSame( 'upcoming', Availability::classify( $this->row(), strtotime( '2025-12-01 UTC' ) ) );
	}

	public function test_past_period_is_closed(): void {
		$this->assertSame( 'closed', Availability::classify( $this->row(), strtotime( '2027-01-01 UTC' ) ) );
		$this->assertSame( 'closed', Availability::classify( $this->row( array( 'period_enrolled' => 50 ) ), strtotime( '2027-01-01 UTC' ) ) );
	}

	public function test_capacity_is_checked_at_program_and_period_level(): void {
		$this->assertSame( 'full', Availability::classify( $this->row( array( 'program_enrolled' => 100 ) ), strtotime( '2026-06-01 UTC' ) ) );
		$this->assertSame( 'full', Availability::classify( $this->row( array( 'period_enrolled' => 50 ) ), strtotime( '2026-06-01 UTC' ) ) );
		$this->assertSame( 40, Availability::remaining( $this->row() ) );
	}

	public function test_unpublished_or_inactive_configuration_is_unavailable(): void {
		$this->assertSame( 'unavailable', Availability::classify( $this->row( array( 'institution_status' => 'inactive' ) ), strtotime( '2026-06-01 UTC' ) ) );
		$this->assertSame( 'unavailable', Availability::classify( $this->row( array( 'program_status' => 'draft' ) ), strtotime( '2026-06-01 UTC' ) ) );
		$this->assertSame( 'unavailable', Availability::classify( $this->row( array( 'period_status' => 'draft' ) ), strtotime( '2026-06-01 UTC' ) ) );
	}

	public function test_unlimited_capacity_has_no_remaining_value(): void {
		$this->assertNull( Availability::remaining( $this->row( array( 'program_quota' => null, 'period_quota' => null ) ) ) );
	}
}
