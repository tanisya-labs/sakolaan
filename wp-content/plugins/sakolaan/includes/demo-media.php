<?php
/**
 * Pengunduhan dan validasi paket media demo.
 *
 * @package Sakolaan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SAKOLAAN_DEMO_MEDIA_MAX_BYTES = 8388608;

/**
 * Definisi media yang wajib tersedia dalam paket demo.
 *
 * @param string $school_name Nama sekolah.
 * @return array
 */
function sakolaan_demo_media_assets( $school_name = '' ) {
	$school_name = $school_name ? $school_name : __( 'Madrasah Kami', 'sakolaan' );

	return array(
		/* translators: %s: school name. */
		'logo'                    => array( 'sakolaan-logo.png', sprintf( __( 'Logo demo %s', 'sakolaan' ), $school_name ), __( 'Logo sekolah demo', 'sakolaan' ) ),
		'hero'                    => array( 'sakolaan-campus-hero.webp', __( 'Lingkungan sekolah', 'sakolaan' ), __( 'Siswa berjalan di halaman sekolah pada pagi hari', 'sakolaan' ) ),
		'principal'               => array( 'sakolaan-principal.webp', __( 'Kepala sekolah demo', 'sakolaan' ), __( 'Potret kepala sekolah demo', 'sakolaan' ) ),
		'science'                 => array( 'sakolaan-science-class.webp', __( 'Praktik sains', 'sakolaan' ), __( 'Guru membimbing siswa saat praktik sains', 'sakolaan' ) ),
		'scouting'                => array( 'sakolaan-scouting.webp', __( 'Kegiatan pramuka', 'sakolaan' ), __( 'Siswa berlatih kerja sama dalam kegiatan pramuka', 'sakolaan' ) ),
		'article_literacy'        => array( 'artikel-literasi-pagi.webp', __( 'Gerakan literasi pagi', 'sakolaan' ), __( 'Siswa mengikuti kegiatan literasi pagi di kelas', 'sakolaan' ) ),
		'article_science'         => array( 'artikel-praktikum-ipa.webp', __( 'Praktikum IPA', 'sakolaan' ), __( 'Guru membimbing siswa saat praktikum IPA', 'sakolaan' ) ),
		'article_muhadharah'      => array( 'artikel-muhadharah.webp', __( 'Kegiatan muhadharah', 'sakolaan' ), __( 'Siswa menyampaikan pidato dalam kegiatan muhadharah', 'sakolaan' ) ),
		'article_osim'            => array( 'artikel-kepemimpinan-osim.webp', __( 'Latihan kepemimpinan OSIM', 'sakolaan' ), __( 'Siswa mengikuti latihan kepemimpinan OSIM', 'sakolaan' ) ),
		'article_social'          => array( 'artikel-bakti-sosial.webp', __( 'Bakti sosial siswa', 'sakolaan' ), __( 'Siswa dan guru menyiapkan paket bakti sosial', 'sakolaan' ) ),
		'article_classmeeting'    => array( 'artikel-class-meeting.webp', __( 'Class meeting', 'sakolaan' ), __( 'Siswa mengikuti pertandingan class meeting', 'sakolaan' ) ),
		'article_digital'         => array( 'artikel-pembelajaran-digital.webp', __( 'Pembelajaran digital', 'sakolaan' ), __( 'Siswa belajar menggunakan komputer dengan pendampingan guru', 'sakolaan' ) ),
		'article_religious'       => array( 'artikel-kegiatan-keagamaan.webp', __( 'Kegiatan keagamaan', 'sakolaan' ), __( 'Siswa mengikuti kegiatan keagamaan bersama guru', 'sakolaan' ) ),
		'event_parents'           => array( 'agenda-rapat-wali-murid.webp', __( 'Rapat wali murid', 'sakolaan' ), __( 'Orang tua dan guru berdiskusi dalam rapat wali murid', 'sakolaan' ) ),
		'event_assessment'        => array( 'agenda-simulasi-asesmen.webp', __( 'Simulasi asesmen', 'sakolaan' ), __( 'Siswa mengikuti simulasi asesmen berbasis komputer', 'sakolaan' ) ),
		'event_porseni'           => array( 'agenda-porseni.webp', __( 'Pekan olahraga dan seni', 'sakolaan' ), __( 'Siswa mengikuti pembukaan pekan olahraga dan seni', 'sakolaan' ) ),
		'event_workshop'          => array( 'agenda-workshop-guru.webp', __( 'Workshop guru', 'sakolaan' ), __( 'Guru mengikuti workshop penguatan pembelajaran', 'sakolaan' ) ),
		'event_museum'            => array( 'agenda-kunjungan-edukatif.webp', __( 'Kunjungan edukatif', 'sakolaan' ), __( 'Siswa mendengarkan pemandu saat kunjungan museum', 'sakolaan' ) ),
		'event_camp'              => array( 'agenda-kemah-pramuka.webp', __( 'Perkemahan Pramuka', 'sakolaan' ), __( 'Siswa mendirikan tenda dalam perkemahan Pramuka', 'sakolaan' ) ),
		'achievement_math'        => array( 'prestasi-olimpiade-matematika.webp', __( 'Olimpiade matematika', 'sakolaan' ), __( 'Siswa menerima penghargaan olimpiade matematika', 'sakolaan' ) ),
		'achievement_speech'      => array( 'prestasi-pidato-arab.webp', __( 'Pidato Bahasa Arab', 'sakolaan' ), __( 'Siswa menerima penghargaan lomba pidato Bahasa Arab', 'sakolaan' ) ),
		'achievement_science'     => array( 'prestasi-kompetisi-sains.webp', __( 'Kompetisi sains', 'sakolaan' ), __( 'Tim sains siswa memperoleh penghargaan kompetisi', 'sakolaan' ) ),
		'achievement_badminton'   => array( 'prestasi-bulu-tangkis.webp', __( 'Turnamen bulu tangkis', 'sakolaan' ), __( 'Tim bulu tangkis siswa menunjukkan medali turnamen', 'sakolaan' ) ),
		'achievement_calligraphy' => array( 'prestasi-kaligrafi.webp', __( 'Lomba kaligrafi', 'sakolaan' ), __( 'Siswa menerima piala lomba kaligrafi', 'sakolaan' ) ),
		'achievement_hadrah'      => array( 'prestasi-hadrah.webp', __( 'Festival hadrah', 'sakolaan' ), __( 'Tim hadrah menerima penghargaan festival', 'sakolaan' ) ),
		'club_scouting'           => array( 'ekskul-pramuka.webp', __( 'Ekstrakurikuler Pramuka', 'sakolaan' ), __( 'Siswa berlatih keterampilan Pramuka di lapangan', 'sakolaan' ) ),
		'club_tahfiz'             => array( 'ekskul-tahfiz.webp', __( 'Ekstrakurikuler Tahfiz', 'sakolaan' ), __( 'Siswa mengikuti kegiatan Tahfiz Al-Quran', 'sakolaan' ) ),
		'club_hadrah'             => array( 'ekskul-hadrah.webp', __( 'Ekstrakurikuler Hadrah', 'sakolaan' ), __( 'Siswa berlatih memainkan alat musik hadrah', 'sakolaan' ) ),
		'club_paskibra'           => array( 'ekskul-paskibra.webp', __( 'Ekstrakurikuler Paskibra', 'sakolaan' ), __( 'Siswa mengikuti latihan baris-berbaris Paskibra', 'sakolaan' ) ),
		'club_futsal'             => array( 'ekskul-futsal.webp', __( 'Ekstrakurikuler Futsal', 'sakolaan' ), __( 'Siswa mengikuti latihan ekstrakurikuler futsal', 'sakolaan' ) ),
		'club_science'            => array( 'ekskul-sains.webp', __( 'Klub Sains', 'sakolaan' ), __( 'Siswa merakit proyek robotika dalam Klub Sains', 'sakolaan' ) ),
	);
}

/**
 * Validasi konfigurasi paket media.
 *
 * @return array|WP_Error
 */
function sakolaan_demo_media_config() {
	if ( ! defined( 'SAKOLAAN_DEMO_MEDIA_URL' ) || ! defined( 'SAKOLAAN_DEMO_MEDIA_SHA256' ) ) {
		return new WP_Error( 'sakolaan_demo_media_config_missing', __( 'Konfigurasi paket media demo belum lengkap. Tetapkan SAKOLAAN_DEMO_MEDIA_URL dan SAKOLAAN_DEMO_MEDIA_SHA256 di wp-config.php.', 'sakolaan' ) );
	}

	$url      = esc_url_raw( SAKOLAAN_DEMO_MEDIA_URL );
	$checksum = strtolower( trim( (string) SAKOLAAN_DEMO_MEDIA_SHA256 ) );

	if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
		return new WP_Error( 'sakolaan_demo_media_url_invalid', __( 'URL paket media demo harus menggunakan HTTPS.', 'sakolaan' ) );
	}

	if ( ! preg_match( '/^[a-f0-9]{64}$/', $checksum ) ) {
		return new WP_Error( 'sakolaan_demo_media_checksum_invalid', __( 'Checksum SHA-256 paket media demo tidak valid.', 'sakolaan' ) );
	}

	return array( 'url' => $url, 'checksum' => $checksum );
}

/**
 * Hapus direktori cache yang telah dipastikan berada di uploads.
 *
 * @param string $directory Direktori cache.
 * @return void
 */
function sakolaan_demo_media_remove_cache( $directory ) {
	$uploads = wp_get_upload_dir();
	$root    = wp_normalize_path( trailingslashit( $uploads['basedir'] ) . 'sakolaan-demo-cache/' );
	$target  = wp_normalize_path( trailingslashit( $directory ) );

	if ( 0 !== strpos( $target, $root ) || ! is_dir( $directory ) ) {
		return;
	}

	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $iterator as $item ) {
		if ( $item->isDir() ) {
			rmdir( $item->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Cache lokal tervalidasi.
		} else {
			wp_delete_file( $item->getPathname() );
		}
	}
	rmdir( $directory ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Cache lokal tervalidasi.
}

/**
 * Validasi manifest dan seluruh file hasil ekstraksi.
 *
 * @param string $directory Direktori cache versi.
 * @return true|WP_Error
 */
function sakolaan_demo_media_validate_directory( $directory ) {
	$manifest_path = trailingslashit( $directory ) . 'manifest.json';
	$media_path    = trailingslashit( $directory ) . 'media/';

	if ( ! is_readable( $manifest_path ) ) {
		return new WP_Error( 'sakolaan_demo_media_manifest_missing', __( 'Manifest paket media demo tidak ditemukan.', 'sakolaan' ) );
	}

	$manifest = json_decode( file_get_contents( $manifest_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Cache lokal.
	$expected = array_column( sakolaan_demo_media_assets(), 0 );
	sort( $expected );
	$listed = isset( $manifest['files'] ) && is_array( $manifest['files'] ) ? array_keys( $manifest['files'] ) : array();
	sort( $listed );

	if ( ! is_array( $manifest ) || SAKOLAAN_DEMO_VERSION !== ( $manifest['version'] ?? '' ) || $expected !== $listed ) {
		return new WP_Error( 'sakolaan_demo_media_manifest_invalid', __( 'Manifest paket media demo tidak sesuai dengan versi plugin.', 'sakolaan' ) );
	}

	foreach ( $expected as $filename ) {
		$definition = $manifest['files'][ $filename ];
		$path       = $media_path . $filename;
		$image      = is_readable( $path ) ? getimagesize( $path ) : false;

		if ( ! $image || ! isset( $definition['sha256'], $definition['mime'], $definition['width'], $definition['height'] ) ) {
			return new WP_Error(
				'sakolaan_demo_media_file_invalid',
				sprintf(
					/* translators: %s: media filename. */
					__( 'Media demo tidak valid: %s', 'sakolaan' ),
					$filename
				)
			);
		}

		if ( ! hash_equals( strtolower( $definition['sha256'] ), hash_file( 'sha256', $path ) ) || $definition['mime'] !== $image['mime'] || (int) $definition['width'] !== $image[0] || (int) $definition['height'] !== $image[1] ) {
			return new WP_Error(
				'sakolaan_demo_media_file_mismatch',
				sprintf(
					/* translators: %s: media filename. */
					__( 'Checksum atau metadata media demo tidak cocok: %s', 'sakolaan' ),
					$filename
				)
			);
		}
	}

	return true;
}

/**
 * Ekstrak hanya entri paket yang diizinkan.
 *
 * @param string $zip_path  Path ZIP.
 * @param string $directory Direktori cache versi.
 * @return true|WP_Error
 */
function sakolaan_demo_media_extract_package( $zip_path, $directory ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'sakolaan_demo_media_zip_unavailable', __( 'Ekstensi ZIP PHP diperlukan untuk memasang media demo.', 'sakolaan' ) );
	}

	$zip = new ZipArchive();
	if ( true !== $zip->open( $zip_path ) ) {
		return new WP_Error( 'sakolaan_demo_media_zip_invalid', __( 'Paket media demo tidak dapat dibuka.', 'sakolaan' ) );
	}

	$allowed = array( 'manifest.json', 'media/' );
	foreach ( array_column( sakolaan_demo_media_assets(), 0 ) as $filename ) {
		$allowed[] = 'media/' . $filename;
	}
	sort( $allowed );
	$entries = array();

	for ( $index = 0; $index < $zip->numFiles; $index++ ) {
		$name = $zip->getNameIndex( $index );
		$entries[] = $name;
		$operations = 0;
		$attributes = 0;
		$zip->getExternalAttributesIndex( $index, $operations, $attributes );
		$is_symlink = 0120000 === ( ( $attributes >> 16 ) & 0170000 );
		if ( $is_symlink || false !== strpos( $name, '..' ) || 0 === strpos( $name, '/' ) || ! in_array( $name, $allowed, true ) ) {
			$zip->close();
			return new WP_Error( 'sakolaan_demo_media_zip_entry_invalid', __( 'Paket media demo memuat path atau file yang tidak diizinkan.', 'sakolaan' ) );
		}
	}
	sort( $entries );
	if ( $allowed !== $entries ) {
		$zip->close();
		return new WP_Error( 'sakolaan_demo_media_zip_incomplete', __( 'Isi paket media demo tidak lengkap.', 'sakolaan' ) );
	}

	wp_mkdir_p( trailingslashit( $directory ) . 'media' );
	foreach ( $entries as $name ) {
		if ( 'media/' === $name ) {
			continue;
		}
		$contents = $zip->getFromName( $name );
		if ( false === $contents || false === file_put_contents( trailingslashit( $directory ) . $name, $contents ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Ekstraksi cache tervalidasi.
			$zip->close();
			return new WP_Error( 'sakolaan_demo_media_extract_failed', __( 'Paket media demo gagal diekstrak ke cache.', 'sakolaan' ) );
		}
	}
	$zip->close();

	return sakolaan_demo_media_validate_directory( $directory );
}

/**
 * Siapkan paket media tervalidasi sebelum dataset lama dihapus.
 *
 * @return string|WP_Error Path direktori media cache.
 */
function sakolaan_demo_prepare_media_package() {
	$config = sakolaan_demo_media_config();
	if ( is_wp_error( $config ) ) {
		return $config;
	}

	$uploads  = wp_get_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return new WP_Error( 'sakolaan_demo_media_uploads_unavailable', $uploads['error'] );
	}
	$cache    = trailingslashit( $uploads['basedir'] ) . 'sakolaan-demo-cache/' . SAKOLAAN_DEMO_VERSION . '-' . substr( $config['checksum'], 0, 12 );
	$existing = sakolaan_demo_media_validate_directory( $cache );
	if ( true === $existing ) {
		return trailingslashit( $cache ) . 'media';
	}
	if ( is_dir( $cache ) ) {
		sakolaan_demo_media_remove_cache( $cache );
	}
	if ( ! wp_mkdir_p( $cache ) ) {
		return new WP_Error( 'sakolaan_demo_media_cache_unavailable', __( 'Direktori cache media demo tidak dapat dibuat.', 'sakolaan' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	$temp = wp_tempnam( 'sakolaan-demo-media.zip' );
	$response = wp_safe_remote_get(
		$config['url'],
		array(
			'timeout'             => 60,
			'redirection'         => 3,
			'stream'              => true,
			'filename'            => $temp,
			'limit_response_size' => SAKOLAAN_DEMO_MEDIA_MAX_BYTES + 1,
		)
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		wp_delete_file( $temp );
		sakolaan_demo_media_remove_cache( $cache );
		return is_wp_error( $response ) ? $response : new WP_Error( 'sakolaan_demo_media_http_error', __( 'Server media demo tidak mengembalikan respons yang valid.', 'sakolaan' ) );
	}

	if ( ! is_readable( $temp ) || filesize( $temp ) > SAKOLAAN_DEMO_MEDIA_MAX_BYTES || ! hash_equals( $config['checksum'], hash_file( 'sha256', $temp ) ) ) {
		wp_delete_file( $temp );
		sakolaan_demo_media_remove_cache( $cache );
		return new WP_Error( 'sakolaan_demo_media_package_invalid', __( 'Ukuran atau checksum paket media demo tidak valid.', 'sakolaan' ) );
	}

	$result = sakolaan_demo_media_extract_package( $temp, $cache );
	wp_delete_file( $temp );
	if ( is_wp_error( $result ) ) {
		sakolaan_demo_media_remove_cache( $cache );
		return $result;
	}

	return trailingslashit( $cache ) . 'media';
}
