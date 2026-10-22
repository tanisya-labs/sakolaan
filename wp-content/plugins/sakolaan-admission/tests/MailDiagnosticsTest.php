<?php
use PHPUnit\Framework\TestCase;
use Sakolaan\Admission\Mail_Diagnostics;

final class MailDiagnosticsTest extends TestCase {
	protected function tearDown(): void {
		remove_all_filters( 'sakolaan_admission_mail_transport_status' );
		$GLOBALS['sakolaan_test_options'] = array();
	}

	public function test_reports_unconfigured_without_transport_signals(): void {
		$status = Mail_Diagnostics::detect( array() );
		$this->assertSame( 'unconfigured', $status['state'] );
		$this->assertSame( 'wordpress_default', $status['transport'] );
		$this->assertFalse( $status['production_ready'] );
	}

	public function test_detects_smtp_and_api_hooks(): void {
		$smtp = Mail_Diagnostics::detect( array( 'phpmailer_init' => true ) );
		$api  = Mail_Diagnostics::detect( array( 'pre_wp_mail' => true ) );

		$this->assertSame( 'connector_detected', $smtp['state'] );
		$this->assertSame( 'wordpress_smtp_hook', $smtp['transport'] );
		$this->assertSame( 'connector_detected', $api['state'] );
		$this->assertSame( 'wordpress_api_hook', $api['transport'] );
	}

	public function test_detects_development_capture_transports(): void {
		$mailhog = Mail_Diagnostics::detect( array( 'sendmail_path' => 'mailhog sendmail --smtp-addr=localhost:1025' ) );
		$mailpit = Mail_Diagnostics::detect( array( 'sendmail_path' => '/usr/local/bin/mailpit sendmail' ) );

		$this->assertSame( 'development_capture', $mailhog['state'] );
		$this->assertSame( 'mailhog', $mailhog['transport'] );
		$this->assertSame( 'development_capture', $mailpit['state'] );
		$this->assertSame( 'mailpit', $mailpit['transport'] );
		$this->assertFalse( $mailpit['production_ready'] );
	}

	public function test_filter_can_override_hosting_transport_status(): void {
		add_filter(
			'sakolaan_admission_mail_transport_status',
			static function (): array {
				return array(
					'state'            => 'connector_detected',
					'transport'        => 'managed_email_api',
					'label'            => 'API email hosting',
					'detail'           => 'Dikelola oleh penyedia hosting.',
					'production_ready' => true,
				);
			}
		);

		$status = Mail_Diagnostics::detect( array( 'apply_filter' => true ) );
		$this->assertSame( 'managed_email_api', $status['transport'] );
		$this->assertSame( 'connector_detected', $status['state'] );
		$this->assertTrue( $status['production_ready'] );
	}

	public function test_notice_describes_connector_acceptance_not_delivery(): void {
		list( $type, $message ) = Mail_Diagnostics::notice(
			array(
				'state'  => 'test_accepted',
				'detail' => 'Email uji diterima oleh konektor email. Hasil ini bukan bukti bahwa pesan telah sampai ke kotak masuk penerima.',
			)
		);

		$this->assertSame( 'success', $type );
		$this->assertStringContainsString( 'bukan bukti', $message );
	}

	public function test_latest_failure_has_priority_over_detected_connector(): void {
		add_filter(
			'sakolaan_admission_mail_transport_status',
			static function (): array {
				return array(
					'state'            => 'connector_detected',
					'transport'        => 'managed_email_api',
					'label'            => 'API email hosting',
					'detail'           => 'Konektor terdeteksi.',
					'production_ready' => true,
				);
			}
		);
		Mail_Diagnostics::record_test( false, 'managed_email_api', 'admin@example.test', 'SMTP timeout' );

		$status = Mail_Diagnostics::status();
		$this->assertSame( 'test_failed', $status['state'] );
		$this->assertSame( 'SMTP timeout', $status['detail'] );
	}

	public function test_accepted_result_is_not_reused_for_a_different_transport(): void {
		Mail_Diagnostics::record_test( true, 'old_smtp', 'admin@example.test' );
		$status = Mail_Diagnostics::status();

		$this->assertSame( 'unconfigured', $status['state'] );
	}

	public function test_error_is_sanitized_and_length_limited(): void {
		$error = Mail_Diagnostics::sanitize_error( '<b>Rahasia</b> admin@example.test password=sangat-rahasia ' . str_repeat( 'x', 600 ) );
		$this->assertStringNotContainsString( '<b>', $error );
		$this->assertStringNotContainsString( 'admin@example.test', $error );
		$this->assertStringNotContainsString( 'sangat-rahasia', $error );
		$this->assertLessThanOrEqual( 500, strlen( $error ) );
	}
}
