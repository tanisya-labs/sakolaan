# Integrasi Pengembang

## REST dan API konten

Namespace REST tetap `sakolaan-admission/v1`. Mutasi dengan autentikasi cookie memerlukan nonce REST WordPress. Endpoint publik hanya mengembalikan program terbit dan hasil pendaftaran akun; data pendaftar serta operasional memerlukan kepemilikan atau kapabilitas yang sesuai.

Shortcode dan blok dinamis tersedia untuk portal terpadu, program yang sedang dibuka, daftar program, formulir, dasbor pendaftar, status, dan pengumuman:

- `[sakolaan_admission_portal]` / `sakolaan-admission/portal`
- `[sakolaan_admission_open_programs]` / `sakolaan-admission/open-programs`
- `[sakolaan_admission_programs]`, `[sakolaan_admission_form]`, `[sakolaan_admission_dashboard]`, `[sakolaan_admission_status]`, dan `[sakolaan_admission_announcement]`

`GET /programs` memakai layanan `Availability` yang sama dengan portal dan section beranda. `POST /verification/resend` selalu memberi respons generik, membatasi permintaan berdasarkan hash email dan alamat IP, serta merotasi token verifikasi yang belum digunakan.

## Filter

- `sakolaan_admission_private_storage_path`
- `sakolaan_admission_form_field_types`
- `sakolaan_admission_application_number`
- `sakolaan_admission_workflow_transition_guard`
- `sakolaan_admission_notification_placeholders`
- `sakolaan_admission_scan_file`
- `sakolaan_admission_mail_transport_status`

Filter `sakolaan_admission_mail_transport_status` menerima array berisi `state`, `transport`, `label`, `detail`, dan `production_ready`. Penyedia hosting atau plugin email dapat menggunakannya untuk melaporkan transport terkelola tanpa pemeriksaan nama plugin. Nilai `state` yang didukung ialah `unconfigured`, `connector_detected`, `development_capture`, `test_accepted`, dan `test_failed`. Jangan memasukkan kredensial atau rahasia koneksi ke nilai filter.

Core menyediakan `sakolaan_admin_navigation_registry` dan `sakolaan_admin_access_capabilities` untuk integrasi add-on yang dapat digunakan kembali.
Pijar Sakolaan menyediakan `pijar_sakolaan_homepage_section_patterns`; add-on mengganti pola `ppdb` hanya ketika fitur dinamis aktif dan jadwal penerimaan tersedia.

## Action

- `sakolaan_admission_application_submitted`
- `sakolaan_admission_document_reviewed`
- `sakolaan_admission_payment_evidence_received`
- `sakolaan_admission_payment_transitioned`
- `sakolaan_admission_application_transitioned`
- `sakolaan_admission_decision_published`
- `sakolaan_admission_enrollment_confirmed`

Jangan melewati layanan `Access`, `Repository`, `Documents`, atau `Workflow` untuk operasi yang dibatasi berdasarkan institusi. Nilai internal status dan kontrak API tetap berbahasa teknis demi kompatibilitas; gunakan `Labels` untuk menampilkannya kepada pengguna.
