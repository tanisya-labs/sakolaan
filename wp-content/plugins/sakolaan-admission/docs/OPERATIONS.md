# Panduan Operasional

## Mode satu institusi

Mode satu institusi membuat institusi utama dari identitas Sakolaan. Terbitkan sekurang-kurangnya satu program dan periode, pilih formulir serta alur penerimaan yang sudah terbit, kemudian tempatkan blok penerimaan pada `/ppdb/`. Pemilih institusi disembunyikan jika tidak diperlukan.

## Mode banyak institusi

Aktifkan mode banyak institusi, buat setiap institusi, lalu tugaskan petugas secara eksplisit. Pemilih konteks hanya menampilkan institusi yang diizinkan. Kueri repositori, mutasi REST, unduhan, ekspor, dan laporan menerapkan pembatasan yang sama di sisi server.

Administrator Utama Penerimaan dapat membuat institusi dan penugasan. Administrator Institusi hanya mengelola konfigurasi serta operasi institusi yang ditugaskan kepadanya. Petugas Penerimaan, Pemeriksa Dokumen, Pemeriksa Pembayaran, dan Penilai memiliki kapabilitas terpisah. Tetapkan peran WordPress sekaligus penugasan institusi sebelum memberikan akses kepada petugas.

## Alur pendaftar

Pendaftar membuat akun WordPress, memverifikasi email, membuat satu pendaftaran untuk setiap pasangan program/periode, menyimpan draf, mengunggah dokumen wajib, dan mengirim pendaftaran. Atur ulang kata sandi menggunakan fasilitas WordPress. Pendaftar tanpa peran petugas tidak dapat masuk ke wp-admin.

## Dokumen

Berkas privat harus berada di luar direktori publik WordPress. Jenis awal yang diizinkan ialah PDF, JPEG, PNG, dan WebP. Sistem memeriksa MIME terdeteksi beserta ekstensinya, memakai nama penyimpanan acak, memeriksa integritas sebelum unduhan, dan mengalirkan berkas hanya setelah pemeriksaan kepemilikan atau kewenangan petugas. Hubungkan pemindai malware melalui filter `sakolaan_admission_scan_file` jika diperlukan.

## Pembayaran dan keputusan

Atur rekening serta petunjuk pembayaran pada halaman Institusi. Pengiriman pendaftaran hanya membuat kewajiban manual jika program mewajibkan biaya. Unggahan bukti mengubah status menjadi Menunggu Verifikasi, tidak pernah langsung Terverifikasi. Pemeriksa Pembayaran tidak otomatis berwenang menerbitkan keputusan. Setiap perubahan tahap memeriksa versi data, mencatat riwayat, dan menulis audit.

## Privasi dan retensi

Penghapusan otomatis tidak aktif secara bawaan. Alat ekspor dan penghapusan privasi WordPress mencakup data penerimaan. Penghapusan akan menganonimkan jawaban, kecuali ID pendaftaran sedang berada dalam penangguhan hukum. Tinjau kewajiban institusi dan hukum sebelum melakukan pemusnahan data.

## Pencadangan dan pemulihan

Cadangkan semua tabel penerimaan dan berkas privat pada titik waktu yang sama. Pulihkan keduanya, periksa diagnostik skema, jalankan jalur peningkatan plugin, kemudian uji beberapa unduhan terlindungi. Jangan memulihkan berkas privat ke direktori yang dapat diakses melalui web.
## Penyuntingan dan publikasi konfigurasi

Gunakan tindakan **Edit** pada tabel konfigurasi untuk memperbarui data. Kode institusi dan kunci dokumen bersifat permanen karena keduanya menjaga hubungan dengan data historis. Simpan program, periode, dan pengumuman sebagai Draf selama belum siap; tombol **Terbitkan** menjalankan validasi server sebelum data dapat digunakan frontend.

- Program memerlukan institusi aktif, formulir terbit, dan alur terbit. Program terbit tanpa periode tetap tidak tampil sebagai pendaftaran terbuka.
- Periode memerlukan program induk terbit serta tanggal penutupan yang lebih akhir daripada tanggal pembukaan.
- Pengumuman memerlukan judul dan isi. Cakupan program bersifat opsional.
- Edit formulir atau alur selalu menerbitkan versi baru. Aplikasi lama tetap menunjuk versi sebelumnya.
- **Kembalikan ke Draf** menghentikan pendaftaran baru atau menyembunyikan pengumuman; transaksi historis tidak dihapus.

Jika perubahan ditolak karena data telah diperbarui pengguna lain, muat ulang halaman dan tinjau versi terbaru sebelum menyimpan kembali. Semua perubahan konfigurasi dan status dicatat di audit log.

## Pengiriman email dan SMTP

Penerimaan memakai `wp_mail()` sehingga transport email mengikuti konfigurasi WordPress, plugin SMTP/API, atau layanan hosting. Buka **Penerimaan → Pengaturan → Diagnostik sistem** untuk melihat jenis konektor dan mengirim email uji. Hasil “Diterima konektor” hanya berarti transport menerima permintaan; hasil tersebut bukan bukti bahwa email telah sampai ke kotak masuk penerima.

Pada development Local, MailHog atau Mailpit menangkap email ke kotak masuk lokal dan tidak meneruskannya ke internet. Periksa aplikasi penangkap email milik Local setelah menjalankan pengujian. Sebelum produksi, pasang dan uji konektor SMTP/API yang sesuai, kemudian periksa juga autentikasi domain, antrean penyedia, log kegagalan, dan folder spam.

Pengujian dibatasi lima kali per administrator per jam. Alamat tujuan disimpan hanya pada status diagnostik terakhir; audit log menyimpan hash alamat, hasil, waktu, dan jenis transport tanpa kredensial atau isi pesan.

