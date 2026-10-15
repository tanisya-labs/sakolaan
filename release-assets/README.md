# Paket Rilis Sakolaan

Jalankan builder dari root WordPress:

```sh
php tools/build-release.php
```

Builder menghasilkan:

- `dist/sakolaan.zip`
- `dist/sakolaan-theme.zip`
- `dist/sakolaan-demo-media-***.zip`
- `dist/sakolaan-demo-media-***.zip.sha256`

Unggah paket media ke host HTTPS, lalu tambahkan konfigurasi berikut ke `wp-config.php` sebelum tombol Data Demo digunakan:

```php
define( 'SAKOLAAN_DEMO_MEDIA_URL', 'https://example.com/sakolaan-demo-media-***.zip' );
define( 'SAKOLAAN_DEMO_MEDIA_SHA256', 'nilai-dari-file-sha256' );
```

Sumber gambar berada di `release-assets/demo-media/source` dan tidak ikut masuk ke ZIP plugin. Jangan mengubah versi paket media tanpa menaikkan `SAKOLAAN_DEMO_VERSION` serta membangun ulang seluruh arsip.

CSS theme gabungan digunakan pada paket produksi. Untuk memakai CSS sumber di workspace pengembangan, definisikan `SAKOLAAN_THEME_DEV_ASSETS` bernilai `true`.
