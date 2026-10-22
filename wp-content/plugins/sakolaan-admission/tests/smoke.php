<?php
require __DIR__ . '/bootstrap.php';

$failures = array();
foreach ( \Sakolaan\Admission\Forms::templates() as $name => $template ) {
	$normalized = \Sakolaan\Admission\Forms::normalize_schema( $template );
	if ( is_wp_error( $normalized ) ) { $failures[] = "Template {$name} failed normalization"; }
}
$schema = \Sakolaan\Admission\Forms::normalize_schema( \Sakolaan\Admission\Forms::templates()['minimal'] );
if ( ! is_wp_error( \Sakolaan\Admission\Forms::validate_answers( $schema, array(), true ) ) ) { $failures[] = 'Required validation did not fail'; }
$valid = \Sakolaan\Admission\Forms::validate_answers( $schema, array( 'full_name' => 'Alya', 'email' => 'alya@example.test', 'phone' => '+62 812', 'notes' => '' ), true );
if ( is_wp_error( $valid ) ) { $failures[] = 'Valid form was rejected'; }
if ( $failures ) { fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL ); exit( 1 ); }
echo "Sakolaan Admission smoke tests passed.\n";
