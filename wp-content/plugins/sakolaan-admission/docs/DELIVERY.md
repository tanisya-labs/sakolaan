# Catatan Penyerahan

## Berkas add-on

- Bootstrap dan siklus hidup: `sakolaan-admission.php`, `uninstall.php`, `includes/class-plugin.php`, dan `includes/class-installer.php`.
- Domain dan keamanan: kelas akses, ketersediaan program, repositori, formulir, alur, dokumen, REST, serta privasi pada `includes/`.
- Antarmuka: `class-admin.php`, `class-frontend.php`, `class-labels.php`, CSS admin/portal, dan JavaScript ringan.
- Pengujian: bootstrap PHPUnit, pengujian formulir/alur/label/migrasi templat, smoke test layanan, dan smoke test skema.
- Dokumentasi Indonesia: README, readme WordPress, changelog, panduan operasional, skema, integrasi pengembang, serta POT berisi 368 string.

## Peningkatan UI dan bahasa

- Judul antarmuka menggunakan **Penerimaan Sakolaan**, sementara nama plugin, slug, kontrak API, status internal, dan URL tetap kompatibel.
- Navigasi admin dikelompokkan menjadi Ringkasan, Konfigurasi, Operasional, Hasil, dan Sistem tanpa mengubah parameter `section`.
- Ringkasan memiliki kartu metrik dan antrean tindakan. Tabel, formulir, badge, empty state, diagnostik, serta konteks institusi memakai sistem visual Sakolaan.
- Portal memiliki kartu program, indikator langkah, ringkasan kesalahan, status penyimpanan, kartu pendaftaran, linimasa status, panel dokumen/pembayaran, dan empty state yang responsif.
- Label status, peran, bagian, kolom, angka, mata uang, dan tanggal Indonesia dipusatkan dalam kelas `Labels`.
- Teks proses JavaScript dikirim melalui `wp_localize_script()`. Tidak ada pesan antarmuka yang disalin ke berkas portal atau blok JavaScript.
- Templat Inggris bawaan hanya dimigrasikan ketika skema aktif identik. Sistem menerbitkan versi Indonesia baru dan mempertahankan versi historis.

## Portal dan visibilitas penerimaan

- `/ppdb/` memiliki portal terpadu yang merutekan pilihan program, akun dan verifikasi, formulir, dokumen, tinjau, pengiriman, pembayaran, dan pelacakan status tanpa menumpuk shortcode lama.
- Placeholder Pijar diganti hanya saat render. Konten khusus administrator dipertahankan dan portal ditambahkan setelahnya; penyisipan otomatis dapat dimatikan.
- Tombol Berikutnya menyimpan draf di server dan baru berpindah setelah berhasil. Draf dibuka kembali pada tahap pertama yang belum lengkap.
- Layanan `Availability` menjadi sumber bersama untuk portal, REST, dan beranda. Section PPDB Pijar menampilkan program aktif atau mendatang dan kembali ke CTA lama bila tidak ada jadwal.
- Kirim ulang verifikasi memakai respons anti-enumerasi, pembatasan per hash email dan IP, rotasi token, serta audit.

## Integrasi repositori

- Sakolaan Core menyediakan registry navigasi dan kapabilitas dasbor yang difilter untuk add-on.
- `tools/build-release.php` membuat dan memvalidasi `sakolaan-admission.zip` secara deterministik.
- `tools/generate-admission-pot.php` membangun ulang templat terjemahan tanpa ketergantungan WP-CLI.
- Workflow rilis, aturan gitignore, Composer, PHPCS, dan PHPUnit mencakup add-on penerimaan.

## Pemeriksaan 16 Oktober 2026

- Lint PHP: lulus untuk 21 berkas plugin dan pengujian serta hook tema yang diubah.
- WordPress Coding Standards/PHPCompatibility: lulus tanpa temuan.
- PHPUnit 9.6: lulus, 14 pengujian dan 77 asersi.
- Smoke test formulir/alur: lulus.
- Smoke test skema: lulus dan menghasilkan 23 definisi tabel.
- Sintaks JavaScript: lulus untuk admin, portal, dan blok.
- Composer: valid; hanya ada peringatan nonblokir bahwa paket akar belum memiliki lisensi.
- Matriks handshake Core–tema: lulus 14 kasus.
- POT: berhasil dibuat ulang dengan 368 string sumber Indonesia.
- Paket rilis: lulus dengan akar ZIP yang aman dan tidak menyertakan pengujian/vendor.
- Determinisme: dua pembuatan berurutan menghasilkan checksum SHA-256 yang sama.

## Batasan lingkungan

WordPress tidak dapat menyelesaikan `wp-load.php` karena koneksi basis data lokal ditolak. Domain Local juga tidak dapat diresolusi. Karena itu, aktivasi nyata, migrasi MySQL, alur admin/portal dalam peramban, dan pemeriksaan visual pada lebar 320/768/1024/1440 piksel belum dapat dijalankan. Pemeriksaan statis, unit, smoke, dan paket di atas tidak menggantikan uji integrasi tersebut. Aktifkan layanan situs dan basis data Local, lalu jalankan skenario penerimaan dengan data uji nonpribadi sebelum rilis produksi.
