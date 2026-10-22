<?php
define( 'ABSPATH', __DIR__ . '/' );
class SchemaSmokeWpdb { public string $prefix = 'wp_'; }
$GLOBALS['wpdb'] = new SchemaSmokeWpdb();
require dirname( __DIR__ ) . '/includes/class-installer.php';
$method = new ReflectionMethod( \Sakolaan\Admission\Installer::class, 'schema' );
if ( PHP_VERSION_ID < 80500 ) { $method->setAccessible( true ); }
$schema = $method->invoke( null, 'DEFAULT CHARACTER SET utf8mb4' );
if ( count( $schema ) < 20 || ! str_contains( $schema[0], 'wp_sakolaan_adm_institutions' ) ) { fwrite( STDERR, "Schema generation failed.\n" ); exit( 1 ); }
echo count( $schema ) . " schema tables generated.\n";
