# Sakolaan Admission

Sakolaan Admission adalah add-on Sakolaan Core untuk pengelolaan penerimaan satu atau banyak institusi. Antarmuka produknya menggunakan nama **Penerimaan Sakolaan**. Persyaratan minimum: WordPress 6.7, PHP 8.0, dan Sakolaan Core 0.0.1.

## Penyiapan produksi

1. Cadangkan basis data dan sistem berkas WordPress.
2. Pasang dan aktifkan Sakolaan Core, kemudian Sakolaan Admission.
3. Pastikan **Sakolaan → Penerimaan → Pengaturan → Diagnostik sistem** menyatakan penyimpanan privat dapat ditulisi dan berada di luar `ABSPATH`. Jika lokasi bawaan tidak sesuai, tetapkan `SAKOLAAN_ADMISSION_PRIVATE_DIR` di `wp-config.php`.
4. Pilih mode satu atau banyak institusi. Berikan penugasan institusi kepada setiap petugas yang bukan administrator utama.
5. Terbitkan formulir, alur penerimaan, program, dan periode. Atur biaya serta persyaratan dokumen jika diperlukan.
6. Gunakan halaman `/ppdb/` sebagai portal terpadu. Add-on mengganti placeholder bawaan Pijar saat render atau menambahkan portal setelah konten khusus tanpa mengubah data halaman. Penyisipan otomatis dapat dimatikan pada Pengaturan Penerimaan. Nilai khusus `ppdb.url` yang sudah ada tidak pernah ditimpa otomatis.
7. Periksa cron WordPress dan pengiriman email. Nilai berhasil dari `wp_mail()` hanya menandakan WordPress menerima permintaan pengiriman, bukan bukti email telah diterima.

Portal memandu pendaftar melalui Program → Akun dan verifikasi → Formulir → Dokumen → Tinjau dan kirim. Tombol **Simpan & lanjutkan** menunggu konfirmasi penyimpanan server sebelum berpindah tahap. Beranda Pijar otomatis menampilkan hingga tiga program yang sedang dibuka atau akan datang; bila belum ada jadwal, CTA PPDB lama tetap digunakan.

Konfigurasi baru dapat disimpan sebagai **Draf** lalu diterbitkan secara manual. Program hanya dapat diterbitkan setelah formulir dan alurnya memiliki versi aktif; periode hanya dapat diterbitkan setelah program induk terbit dan jadwalnya valid. Status Efektif menjelaskan kondisi aktual—Belum Dijadwalkan, Segera Dibuka, Sedang Dibuka, Ditutup, atau Kuota Penuh—tanpa mengubah Status Publikasi. Mengembalikan program atau periode ke Draf menghentikan pendaftaran baru, tetapi aplikasi lama tetap dapat diproses.

Baca [panduan operasional](docs/OPERATIONS.md), [skema basis data](docs/SCHEMA.md), dan [integrasi pengembang](docs/DEVELOPER.md).

## Pencadangan dan peningkatan versi

Cadangkan seluruh tabel `*_sakolaan_adm_*` bersama direktori privat pada waktu yang sama. Migrasi basis data berjalan secara idempoten saat plugin dimuat. Penonaktifan tidak menghapus data. Penghapusan plugin tetap mempertahankan data, kecuali administrator utama memasukkan konfirmasi `DELETE` pada pengaturan Penerimaan sebelum menghapus plugin.

## Pembuatan paket

Dari akar repositori, jalankan `php tools/build-release.php`. Paket siap pasang ditulis ke `dist/sakolaan-admission.zip` dan diperiksa agar memiliki akar ZIP yang aman.
