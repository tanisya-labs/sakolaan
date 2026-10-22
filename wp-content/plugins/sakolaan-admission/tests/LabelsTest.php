<?php
use PHPUnit\Framework\TestCase;
use Sakolaan\Admission\Forms;
use Sakolaan\Admission\Labels;

final class LabelsTest extends TestCase {
	public function test_all_public_statuses_have_indonesian_labels(): void {
		$expected = array(
			'draft'                 => 'Draf',
			'submitted'             => 'Dikirim',
			'document_verification' => 'Verifikasi Dokumen',
			'payment_verification'  => 'Verifikasi Pembayaran',
			'assessment'            => 'Penilaian',
			'decision_pending'      => 'Menunggu Keputusan',
			'accepted'              => 'Diterima',
			'waitlisted'            => 'Daftar Tunggu',
			'rejected'              => 'Ditolak',
			'enrollment_confirmed'  => 'Pendaftaran Ulang Terkonfirmasi',
			'pending_verification'  => 'Menunggu Verifikasi',
			'revision_requested'    => 'Perlu Perbaikan',
			'expired'               => 'Kedaluwarsa',
		);
		foreach ( $expected as $status => $label ) {
			$this->assertSame( $label, Labels::status( $status ) );
		}
	}

	public function test_starter_templates_use_indonesian_labels(): void {
		foreach ( Forms::templates() as $key => $template ) {
			$this->assertNotSame( $key, Forms::template_label( $key ) );
			$this->assertSame( 'Identitas Pendaftar', $template['steps'][0]['label'] );
			$this->assertSame( 'Informasi Tambahan', $template['steps'][1]['label'] );
		}
	}

	public function test_numbers_and_currency_use_indonesian_separators(): void {
		$this->assertSame( '1.250', Labels::number( 1250.0 ) );
		$this->assertSame( '1.250,50', Labels::number( 1250.5 ) );
		$this->assertSame( 'IDR 125.000', Labels::currency( 125000.0, 'IDR' ) );
	}

	public function test_only_exact_legacy_template_is_detected(): void {
		$legacy = array(
			'schema_version' => 1,
			'steps'          => array(
				array(
					'id'     => 'identity',
					'label'  => 'Identity',
					'fields' => array(
						array( 'key' => 'full_name', 'type' => 'text', 'label' => 'Full name', 'required' => true ),
						array( 'key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true ),
						array( 'key' => 'phone', 'type' => 'phone', 'label' => 'Phone', 'required' => true ),
					),
				),
				array(
					'id'     => 'review',
					'label'  => 'Additional information',
					'fields' => array(
						array( 'key' => 'notes', 'type' => 'textarea', 'label' => 'Notes', 'required' => false ),
					),
				),
			),
		);
		$this->assertSame( 'minimal', Forms::legacy_template_key( $legacy ) );
		$legacy['steps'][0]['fields'][0]['label'] = 'Nama khusus';
		$this->assertNull( Forms::legacy_template_key( $legacy ) );
	}

	public function test_old_visible_messages_are_not_in_runtime_sources(): void {
		$root    = dirname( __DIR__ );
		$sources = file_get_contents( $root . '/includes/class-rest.php' ) . file_get_contents( $root . '/assets/js/portal.js' );
		foreach ( array( 'Application not found.', 'Choose a file.', 'Registration rejected.', 'Saving...', 'Submit application' ) as $english ) {
			$this->assertStringNotContainsString( $english, $sources );
		}
	}
}
