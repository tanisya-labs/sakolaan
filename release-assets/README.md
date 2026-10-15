# Paket Rilis Sakolaan

Jalankan builder dari root WordPress:

```sh
php tools/build-release.php
```

Untuk mensimulasikan hasil dari branch selain `main`, gunakan suffix berupa slug
huruf kecil dengan panjang maksimal 63 karakter:

```sh
php tools/build-release.php --suffix=0-0-1
```

Builder menghasilkan:

- `dist/sakolaan.zip`
- `dist/sakolaan-theme.zip`
- `dist/sakolaan-demo-media-***.zip`
- `dist/sakolaan-demo-media-***.zip.sha256`

Dengan `--suffix=0-0-1`, nama file menjadi `sakolaan-0-0-1.zip`,
`sakolaan-theme-0-0-1.zip`, dan
`sakolaan-demo-media-***-0-0-1.zip` beserta checksum-nya. Builder menolak
suffix kosong, karakter tidak aman, dan suffix yang terlalu panjang.

## GitHub Actions

Workflow `.github/workflows/release.yml` berjalan pada setiap push ke branch:

- `main` memakai nama paket standar tanpa suffix branch, membuat release normal,
  dan menandainya sebagai latest.
- Branch lain dinormalisasi menjadi slug aman, dipakai sebagai suffix paket, dan
  menghasilkan prerelease.
- Setiap commit memakai tag unik. Format main adalah
  `build-<versi>-<sha-pendek>`, sedangkan branch lain memakai
  `build-<versi>-<slug-branch>-<sha-pendek>`.
- Keempat file juga tersedia sebagai GitHub Actions artifact selama 14 hari.
- Menjalankan ulang workflow pada commit yang sama memperbarui release dan
  mengganti aset, bukan membuat duplikat.

Workflow memakai `GITHUB_TOKEN` bawaan dengan izin `contents: write`; secret
tambahan tidak diperlukan. Builder memverifikasi versi, struktur ZIP, checksum,
kesamaan stylesheet notice, serta batas ukuran sebelum file dipublikasikan.

Unggah paket media ke host HTTPS, lalu tambahkan konfigurasi berikut ke `wp-config.php` sebelum tombol Data Demo digunakan:

```php
define( 'SAKOLAAN_DEMO_MEDIA_URL', 'https://example.com/sakolaan-demo-media-***.zip' );
define( 'SAKOLAAN_DEMO_MEDIA_SHA256', 'nilai-dari-file-sha256' );
```

Sumber gambar berada di `release-assets/demo-media/source` dan tidak ikut masuk ke ZIP plugin. Jangan mengubah versi paket media tanpa menaikkan `SAKOLAAN_DEMO_VERSION` serta membangun ulang seluruh arsip.

CSS theme gabungan digunakan pada paket produksi. Untuk memakai CSS sumber di workspace pengembangan, definisikan `SAKOLAAN_THEME_DEV_ASSETS` bernilai `true`.
