# Skema Basis Data

Semua nama tabel menggunakan `$wpdb->prefix` aktif diikuti `sakolaan_adm_`. Ranah konfigurasi dan transaksi dipisahkan menjadi institusi dan penugasan; program, periode, dan persyaratan; formulir/alur beserta versi tetap; pendaftar, pendaftaran, dan jawaban; dokumen dan pembayaran; rubrik dan penilaian; pendaftaran ulang; riwayat status; pengumuman; antrean notifikasi; serta log audit.

Tabel menggunakan InnoDB, charset/collation WordPress, kunci utama numerik, cakupan `institution_id` eksplisit, dan indeks sesuai jalur akses. Portabilitas WordPress dipertahankan dengan menegakkan referensi pada lapisan layanan, bukan foreign key basis data. Nomor pendaftaran, profil pengguna, kombinasi pendaftaran/program/periode, versi konfigurasi, kewajiban pembayaran, dan reservasi pendaftaran ulang memiliki batasan unik.

Versi skema disimpan pada `sakolaan_admission_schema_version`. Migrasi bersifat idempoten melalui `dbDelta()`. Pendaftaran yang diterbitkan menyimpan ID versi formulir dan alur sehingga perubahan berikutnya tidak menafsirkan ulang kiriman historis.

Pembaruan templat bawaan Indonesia juga membuat versi formulir baru hanya ketika versi aktif identik dengan templat Inggris bawaan. Skema lama tidak diubah dan tetap dapat dipakai oleh pendaftaran historis.
