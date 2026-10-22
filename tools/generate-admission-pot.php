<?php
/** Generate the Sakolaan Admission translation template without WP-CLI. */

$root   = dirname( __DIR__ ) . '/wp-content/plugins/sakolaan-admission';
$output = $root . '/languages/sakolaan-admission.pot';
$items  = array();
$files  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );

foreach ( $files as $file ) {
	if ( ! in_array( $file->getExtension(), array( 'php', 'js' ), true ) || str_contains( $file->getPathname(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	$contents = file_get_contents( $file->getPathname() );
	if ( false === $contents ) {
		continue;
	}
	$pattern = "/\\b(?:__|esc_html__|esc_attr__)\\(\\s*'((?:\\\\.|[^'])*)'\\s*,\\s*'sakolaan-admission'/";
	if ( ! preg_match_all( $pattern, $contents, $matches, PREG_OFFSET_CAPTURE ) ) {
		continue;
	}
	foreach ( $matches[1] as $match ) {
		$msgid = stripcslashes( $match[0] );
		$line  = substr_count( substr( $contents, 0, $match[1] ), "\n" ) + 1;
		$ref   = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) ) . ':' . $line;
		$items[ $msgid ][] = $ref;
	}
}

ksort( $items, SORT_NATURAL | SORT_FLAG_CASE );
$escape = static fn( string $value ): string => addcslashes( $value, "\\\"\n\r\t" );
$pot    = "# Sakolaan Admission translation template.\n";
$pot   .= "msgid \"\"\nmsgstr \"\"\n";
$pot   .= '"Project-Id-Version: Sakolaan Admission 0.1.0\\n"' . "\n";
$pot   .= '"POT-Creation-Date: ' . gmdate( 'Y-m-d H:iO' ) . '\\n"' . "\n";
$pot   .= '"Content-Type: text/plain; charset=UTF-8\\n"' . "\n";
$pot   .= '"Content-Transfer-Encoding: 8bit\\n"' . "\n\n";

foreach ( $items as $msgid => $refs ) {
	$pot .= '#: ' . implode( ' ', array_unique( $refs ) ) . "\n";
	$pot .= 'msgid "' . $escape( $msgid ) . '"' . "\n";
	$pot .= "msgstr \"\"\n\n";
}

if ( ! is_dir( dirname( $output ) ) ) {
	mkdir( dirname( $output ), 0777, true );
}
file_put_contents( $output, $pot );
echo 'Generated ' . count( $items ) . " strings in {$output}.\n";
