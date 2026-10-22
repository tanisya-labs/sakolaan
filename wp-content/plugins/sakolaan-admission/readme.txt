=== Sakolaan Admission ===
Contributors: tanisya
Requires at least: 6.7
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later

Pengelolaan penerimaan institusi pendidikan yang terintegrasi dengan Sakolaan Core.

== Description ==

Sakolaan Admission menyediakan program dan periode berbasis institusi, formulir berversi, akun pendaftar, dokumen privat, verifikasi pembayaran manual, penilaian berbobot, keputusan terkontrol, pengendalian kuota pendaftaran ulang, laporan, notifikasi, perangkat privasi, blok, dan shortcode.

Sakolaan Core 0.0.1 atau yang lebih baru harus terpasang dan aktif. Data tetap disimpan saat plugin dinonaktifkan dan, secara bawaan, saat plugin dihapus.

== Installation ==

1. Pasang dan aktifkan Sakolaan Core.
2. Unggah dan aktifkan `sakolaan-admission.zip`.
3. Buka Sakolaan > Penerimaan > Pengaturan, lalu periksa diagnostik penyimpanan privat dan pengiriman email.
4. Atur institusi, program, periode, formulir, dan alur penerimaan.
5. Buka `/ppdb/`. Portal terpadu ditampilkan otomatis pada placeholder Pijar; gunakan blok Portal Penerimaan bila ingin menempatkannya secara manual.

== Shortcode ==

* `[sakolaan_admission_programs]`
* `[sakolaan_admission_portal]`
* `[sakolaan_admission_open_programs]`
* `[sakolaan_admission_form]`
* `[sakolaan_admission_dashboard]`
* `[sakolaan_admission_status]`
* `[sakolaan_admission_announcement]`

== Changelog ==

= 0.1.0 =
* Rilis awal add-on penerimaan yang berorientasi produksi.
* Portal terpadu, wizard dengan penyimpanan per langkah, dan section program aktif pada beranda Pijar.
* Diagnostik konektor SMTP/API, deteksi MailHog/Mailpit, peringatan admin, dan email uji terbatas.
