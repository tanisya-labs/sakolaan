<?php
use PHPUnit\Framework\TestCase;
use Sakolaan\Admission\Frontend;

final class FrontendFieldTest extends TestCase {
	public function test_legacy_field_without_optional_help_and_placeholder_renders_safely(): void {
		$method = new ReflectionMethod( Frontend::class, 'field' );
		$html   = $method->invoke(
			null,
			array(
				'key'      => 'nama_lengkap',
				'type'     => 'text',
				'label'    => 'Nama lengkap',
				'required' => true,
			),
			'Alya'
		);

		$this->assertStringContainsString( 'name="nama_lengkap"', $html );
		$this->assertStringContainsString( 'placeholder=""', $html );
		$this->assertStringContainsString( 'value="Alya"', $html );
	}

	public function test_legacy_information_field_without_help_uses_label(): void {
		$method = new ReflectionMethod( Frontend::class, 'field' );
		$html   = $method->invoke( null, array( 'type' => 'info', 'label' => 'Siapkan dokumen asli.' ) );

		$this->assertSame( '<p>Siapkan dokumen asli.</p>', $html );
	}
}
