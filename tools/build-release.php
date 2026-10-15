<?php
/**
 * Build paket distribusi Sakolaan tanpa dependensi eksternal.
 *
 * Jalankan dari root WordPress: php tools/build-release.php
 */

declare(strict_types=1);

$root       = dirname( __DIR__ );
$dist       = $root . '/dist';
$source     = $root . '/release-assets/demo-media/source';
$stage      = sys_get_temp_dir() . '/sakolaan-release-' . bin2hex( random_bytes( 6 ) );
$product_version = '0.0.1';
$media_version   = '0.0.1';
$media_root = $stage . '/media-package';

/** Read one required version value from a source file. */
function sakolaan_build_read_version( string $path, string $pattern, string $label ): string {
	$contents = file_exists( $path ) ? file_get_contents( $path ) : false;
	if ( false === $contents || ! preg_match( $pattern, $contents, $matches ) ) {
		throw new RuntimeException( 'Versi tidak ditemukan pada ' . $label . ': ' . $path );
	}
	return trim( $matches[1] );
}

$release_versions = array(
	'plugin header'   => sakolaan_build_read_version( $root . '/wp-content/plugins/sakolaan/sakolaan.php', '/Version:\s*([0-9A-Za-z.+-]+)/', 'plugin header' ),
	'plugin constant' => sakolaan_build_read_version( $root . '/wp-content/plugins/sakolaan/sakolaan.php', "/define\(\s*'SAKOLAAN_VERSION'\s*,\s*'([^']+)'\s*\)/", 'plugin constant' ),
	'plugin readme'   => sakolaan_build_read_version( $root . '/wp-content/plugins/sakolaan/readme.txt', '/^Stable tag:[ \t]*([^\r\n]+)/mi', 'plugin readme' ),
	'plugin POT'      => sakolaan_build_read_version( $root . '/wp-content/plugins/sakolaan/languages/sakolaan.pot', '/Project-Id-Version: Sakolaan ([0-9A-Za-z.+-]+)/', 'plugin POT' ),
	'theme header'    => sakolaan_build_read_version( $root . '/wp-content/themes/sakolaan-theme/style.css', '/Version:\s*([0-9A-Za-z.+-]+)/', 'theme header' ),
	'theme constant'  => sakolaan_build_read_version( $root . '/wp-content/themes/sakolaan-theme/functions.php', "/define\(\s*'SAKOLAAN_THEME_VERSION'\s*,\s*'([^']+)'\s*\)/", 'theme constant' ),
	'theme readme'    => sakolaan_build_read_version( $root . '/wp-content/themes/sakolaan-theme/readme.txt', '/^Version:[ \t]*([^\r\n]+)/mi', 'theme readme' ),
	'theme POT'       => sakolaan_build_read_version( $root . '/wp-content/themes/sakolaan-theme/languages/sakolaan-theme.pot', '/Project-Id-Version: Sakolaan Theme ([0-9A-Za-z.+-]+)/', 'theme POT' ),
);

foreach ( $release_versions as $label => $detected_version ) {
	if ( $product_version !== $detected_version ) {
		throw new RuntimeException( sprintf( 'Build dibatalkan: versi %s adalah %s, seharusnya %s.', $label, $detected_version, $product_version ) );
	}
}

$plugin_notice_css = $root . '/wp-content/plugins/sakolaan/assets/css/admin-notices.css';
$theme_notice_css  = $root . '/wp-content/themes/sakolaan-theme/assets/css/admin-notices.css';
if (
	! file_exists( $plugin_notice_css ) ||
	! file_exists( $theme_notice_css ) ||
	! hash_equals( (string) hash_file( 'sha256', $plugin_notice_css ), (string) hash_file( 'sha256', $theme_notice_css ) )
) {
	throw new RuntimeException( 'Build dibatalkan: stylesheet notice admin plugin dan theme harus tersedia dan identik.' );
}

if ( ! class_exists( 'ZipArchive' ) || ! function_exists( 'imagewebp' ) ) {
	fwrite( STDERR, "Ekstensi PHP zip dan gd diperlukan.\n" );
	exit( 1 );
}

/** Hapus tree build sementara yang targetnya telah dibatasi. */
function sakolaan_build_remove_tree( string $path, string $allowed_root ): void {
	$path         = str_replace( '\\', '/', $path );
	$allowed_root = rtrim( str_replace( '\\', '/', $allowed_root ), '/' ) . '/';
	if ( ! str_starts_with( rtrim( $path, '/' ) . '/', $allowed_root ) || ! is_dir( $path ) ) {
		return;
	}
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $iterator as $item ) {
		$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
	}
	rmdir( $path );
}

/** Salin tree dengan filter path relatif. */
function sakolaan_build_copy_tree( string $from, string $to, callable $include ): void {
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $from, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST );
	foreach ( $iterator as $item ) {
		$relative = str_replace( '\\', '/', substr( $item->getPathname(), strlen( $from ) + 1 ) );
		if ( ! $include( $relative, $item ) ) {
			continue;
		}
		$target = $to . '/' . $relative;
		if ( $item->isDir() ) {
			if ( ! is_dir( $target ) ) {
				mkdir( $target, 0777, true );
			}
		} else {
			if ( ! is_dir( dirname( $target ) ) ) {
				mkdir( dirname( $target ), 0777, true );
			}
			copy( $item->getPathname(), $target );
		}
	}
}

/** Minifikasi CSS distribusi secara konservatif. */
function sakolaan_build_minify_css( string $css ): string {
	$css = preg_replace( '#/\*(?!\!)(.*?)\*/#s', '', $css );
	$css = preg_replace( '/\s+/', ' ', (string) $css );
	$css = preg_replace( '/\s*([{}:;,>])\s*/', '$1', (string) $css );
	return trim( (string) $css ) . "\n";
}

/** Kurangi komentar/baris kosong JavaScript tanpa mengubah token. */
function sakolaan_build_minify_js( string $javascript ): string {
	$javascript = preg_replace( '!/\*.*?\*/!s', '', $javascript );
	$lines      = preg_split( '/\R/', (string) $javascript );
	$output     = array();
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' !== $line && ! str_starts_with( $line, '//' ) ) {
			$output[] = $line;
		}
	}
	return implode( "\n", $output ) . "\n";
}

/** Buka raster yang didukung GD. */
function sakolaan_build_open_image( string $path ) {
	$mime = getimagesize( $path )['mime'] ?? '';
	return match ( $mime ) {
		'image/jpeg' => imagecreatefromjpeg( $path ),
		'image/png'  => imagecreatefrompng( $path ),
		'image/webp' => imagecreatefromwebp( $path ),
		default      => false,
	};
}

/** Simpan WebP yang metadata tambahannya telah dibuang. */
function sakolaan_build_webp( string $source, string $target, int $max_width = 1280, int $max_height = 1280 ): void {
	$image = sakolaan_build_open_image( $source );
	if ( ! $image ) {
		throw new RuntimeException( 'Gagal membaca gambar: ' . $source );
	}
	$width  = imagesx( $image );
	$height = imagesy( $image );
	$scale  = min( 1, $max_width / $width, $max_height / $height );
	$new_w  = max( 1, (int) round( $width * $scale ) );
	$new_h  = max( 1, (int) round( $height * $scale ) );
	$output = imagecreatetruecolor( $new_w, $new_h );
	imagecopyresampled( $output, $image, 0, 0, 0, 0, $new_w, $new_h, $width, $height );
	if ( ! imagewebp( $output, $target, 70 ) ) {
		throw new RuntimeException( 'Gagal menulis WebP: ' . $target );
	}
}

/** Simpan PNG teroptimasi dengan alpha tetap dipertahankan. */
function sakolaan_build_png( string $source, string $target ): void {
	$image = imagecreatefrompng( $source );
	imagealphablending( $image, false );
	imagesavealpha( $image, true );
	if ( ! imagepng( $image, $target, 9 ) ) {
		throw new RuntimeException( 'Gagal menulis PNG: ' . $target );
	}
}

/** Optimalkan screenshot theme untuk daftar theme WordPress. */
function sakolaan_build_screenshot( string $source, string $target ): void {
	$image  = imagecreatefrompng( $source );
	$output = imagecreatetruecolor( 900, 675 );
	imagecopyresampled( $output, $image, 0, 0, 0, 0, 900, 675, imagesx( $image ), imagesy( $image ) );
	imagetruecolortopalette( $output, true, 256 );
	if ( ! imagepng( $output, $target, 9 ) ) {
		throw new RuntimeException( 'Gagal mengoptimalkan screenshot theme.' );
	}
}

/** Buat ZIP dengan urutan dan timestamp stabil. */
function sakolaan_build_zip( string $zip_path, string $source_dir, string $root_name = '' ): void {
	$zip = new ZipArchive();
	if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		throw new RuntimeException( 'Gagal membuat ZIP: ' . $zip_path );
	}
	$paths = array();
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source_dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST );
	foreach ( $iterator as $item ) {
		$paths[] = $item->getPathname();
	}
	sort( $paths );
	if ( $root_name ) {
		$zip->addEmptyDir( $root_name );
		if ( method_exists( $zip, 'setMtimeName' ) ) {
			$zip->setMtimeName( $root_name . '/', 1760486400 );
		}
		if ( method_exists( $zip, 'setExternalAttributesName' ) ) {
			$zip->setExternalAttributesName( $root_name . '/', ZipArchive::OPSYS_UNIX, 040755 << 16 );
		}
	}
	foreach ( $paths as $path ) {
		$relative = str_replace( '\\', '/', substr( $path, strlen( $source_dir ) + 1 ) );
		$name     = $root_name ? $root_name . '/' . $relative : $relative;
		if ( is_dir( $path ) ) {
			$zip->addEmptyDir( $name );
			if ( method_exists( $zip, 'setExternalAttributesName' ) ) {
				$zip->setExternalAttributesName( rtrim( $name, '/' ) . '/', ZipArchive::OPSYS_UNIX, 040755 << 16 );
			}
		} else {
			$zip->addFromString( $name, file_get_contents( $path ) );
			$zip->setCompressionName( $name, ZipArchive::CM_DEFLATE, 9 );
			if ( method_exists( $zip, 'setExternalAttributesName' ) ) {
				$zip->setExternalAttributesName( $name, ZipArchive::OPSYS_UNIX, 0100644 << 16 );
			}
		}
		if ( method_exists( $zip, 'setMtimeName' ) ) {
			$zip->setMtimeName( is_dir( $path ) ? rtrim( $name, '/' ) . '/' : $name, 1760486400 );
		}
	}
	$zip->close();
}

if ( ! is_dir( $source ) ) {
	fwrite( STDERR, "Sumber media tidak ditemukan: {$source}\n" );
	exit( 1 );
}
mkdir( $stage, 0777, true );
if ( ! is_dir( $dist ) ) {
	mkdir( $dist, 0777, true );
}
mkdir( $media_root . '/media', 0777, true );

$shared = array(
	'sakolaan-campus-hero.jpg' => 'sakolaan-campus-hero.webp',
	'sakolaan-principal.jpg'   => 'sakolaan-principal.webp',
	'sakolaan-science-class.jpg' => 'sakolaan-science-class.webp',
	'sakolaan-scouting.jpg'    => 'sakolaan-scouting.webp',
);
$files = array();
foreach ( $shared as $input => $output ) {
	sakolaan_build_webp( $source . '/' . $input, $media_root . '/media/' . $output );
}
foreach ( glob( $source . '/*.webp' ) as $input ) {
	sakolaan_build_webp( $input, $media_root . '/media/' . basename( $input ), 1280, 800 );
}
sakolaan_build_png( $source . '/sakolaan-logo.png', $media_root . '/media/sakolaan-logo.png' );

foreach ( glob( $media_root . '/media/*' ) as $path ) {
	$info = getimagesize( $path );
	$files[ basename( $path ) ] = array(
		'sha256' => hash_file( 'sha256', $path ),
		'mime'   => $info['mime'],
		'width'  => $info[0],
		'height' => $info[1],
	);
}
ksort( $files );
file_put_contents( $media_root . '/manifest.json', json_encode( array( 'version' => $media_version, 'files' => $files ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );

$plugin_stage = $stage . '/sakolaan';
$theme_stage  = $stage . '/sakolaan-theme';
sakolaan_build_copy_tree(
	$root . '/wp-content/plugins/sakolaan',
	$plugin_stage,
	static fn( string $relative, SplFileInfo $item ): bool => ! str_starts_with( $relative, 'assets/demo' ) && ! str_ends_with( $relative, '.map' ) && '.gitkeep' !== basename( $relative )
);
sakolaan_build_copy_tree(
	$root . '/wp-content/themes/sakolaan-theme',
	$theme_stage,
	static function ( string $relative, SplFileInfo $item ): bool {
		$excluded_css = array( 'assets/css/base.css', 'assets/css/utilities.css', 'assets/css/components.css', 'assets/css/site.css' );
		return ! in_array( $relative, $excluded_css, true ) && ! str_ends_with( $relative, '.map' ) && '.gitkeep' !== basename( $relative );
	}
);

foreach ( array_merge( glob( $plugin_stage . '/assets/css/*.css' ), glob( $theme_stage . '/assets/css/*.css' ) ) as $css ) {
	file_put_contents( $css, sakolaan_build_minify_css( file_get_contents( $css ) ) );
}
foreach ( array_merge( glob( $plugin_stage . '/assets/js/*.js' ), glob( $theme_stage . '/assets/js/*.js' ) ) as $javascript ) {
	file_put_contents( $javascript, sakolaan_build_minify_js( file_get_contents( $javascript ) ) );
}
sakolaan_build_screenshot( $root . '/wp-content/themes/sakolaan-theme/screenshot.png', $theme_stage . '/screenshot.png' );

$plugin_zip = $dist . '/sakolaan.zip';
$theme_zip  = $dist . '/sakolaan-theme.zip';
$media_zip  = $dist . '/sakolaan-demo-media-' . $media_version . '.zip';
sakolaan_build_zip( $plugin_zip, $plugin_stage, 'sakolaan' );
sakolaan_build_zip( $theme_zip, $theme_stage, 'sakolaan-theme' );
sakolaan_build_zip( $media_zip, $media_root );
file_put_contents( $media_zip . '.sha256', hash_file( 'sha256', $media_zip ) . "  " . basename( $media_zip ) . "\n" );

$result = array(
	'plugin' => array( 'path' => $plugin_zip, 'bytes' => filesize( $plugin_zip ) ),
	'theme'  => array( 'path' => $theme_zip, 'bytes' => filesize( $theme_zip ) ),
	'media'  => array( 'path' => $media_zip, 'bytes' => filesize( $media_zip ), 'sha256' => hash_file( 'sha256', $media_zip ) ),
);
echo json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;

sakolaan_build_remove_tree( $stage, sys_get_temp_dir() );
