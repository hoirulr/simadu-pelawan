# Rencana Perbaikan Aplikasi SI MADU PELAWAN

**Tanggal:** 26 Oktober 2026  
**Dasar:** Audit keamanan dan kesesuaian aplikasi terhadap PRD  
**Status awal:** Functional prototype / staging candidate; belum production-ready

## Tujuan

1. Menutup privilege escalation dan akses lintas balai.
2. Memastikan seluruh input bisnis tervalidasi di server.
3. Mengimplementasikan upload dokumen dan bukti koreksi secara nyata serta aman.
4. Memastikan setiap requirement PRD memiliki implementasi dan test yang dapat diverifikasi.
5. Mengamankan konfigurasi production.
6. Menyiapkan deployment, audit trail, dan pengujian regresi.

## Prioritas P0 — Wajib sebelum production

### P0.1 Tutup privilege escalation melalui switch user

**Perubahan:**

- Hapus `/switch-user/{id}` dari production, atau batasi hanya untuk admin.
- Ubah mekanisme dari GET menjadi POST dengan CSRF.
- Nonaktifkan quick switcher saat `APP_ENV=production`.
- Tambahkan audit log untuk actor, target, waktu, dan IP.

**Acceptance criteria:**

- Operator tidak dapat mengakses endpoint switch user.
- Operator tidak dapat berubah menjadi admin.
- Impersonation hanya dapat dilakukan oleh role yang diizinkan.
- Setiap impersonation tercatat.
- Tersedia test untuk privilege escalation.

### P0.2 Perbaiki isolasi data antar balai

**Resource:** `LaporanAnggaran`, `DokumenLaporan`, `TindakLanjutMonitoring`, dan `EwsAlert`.

- Buat Laravel Policy untuk resource domain.
- Gunakan authorization pada setiap action Livewire yang membaca atau memutasi data.
- Batasi query operator berdasarkan `auth()->user()->balai_id`.
- Jangan mengandalkan ID global tanpa pemeriksaan kepemilikan.
- Tambahkan test unauthorized/forbidden untuk setiap action penting.

### P0.3 Validasi laporan anggaran di server

- `rencana_anggaran` dan `realisasi_anggaran` harus numeric serta minimal 0.
- `periode_id` dan `balai_id` harus valid.
- Operator hanya dapat mengirim laporan untuk balainya sendiri.
- Tetapkan aturan resmi untuk realisasi di atas rencana.
- Batasi panjang catatan dan batas maksimum angka.

**Acceptance criteria:** nilai negatif, non-numeric, ID invalid, dan submit lintas balai ditolak; persentase serapan konsisten dan diuji.

### P0.4 Implementasikan upload dokumen yang sebenarnya

- Gunakan `WithFileUploads` pada komponen Livewire.
- Validasi MIME/type dan ukuran file dengan allowlist format PRD.
- Simpan file pada private disk menggunakan path acak/UUID.
- Simpan path internal, bukan nama file user sebagai path.
- Buat endpoint download yang melakukan authorization.
- Hapus atau arsipkan file lama saat file diganti.
- Jangan menyimpan file bukti pada direktori public.

**Acceptance criteria:** file invalid/terlalu besar ditolak; file valid tersimpan private; operator hanya dapat mengakses file balainya; test upload, download, replacement, dan unauthorized access tersedia.

### P0.5 Amankan konfigurasi production

Konfigurasi minimum:

    APP_ENV=production
    APP_DEBUG=false
    LOG_LEVEL=warning
    APP_URL=https://domain-resmi
    SESSION_SECURE_COOKIE=true

- Gunakan HTTPS.
- Pastikan `APP_KEY` unik dan `.env` tidak masuk Git.
- Matikan quick login demo di production.
- Jangan menjalankan seeder akun demo di production.
- Pastikan halaman error tidak menampilkan stack trace.

## Prioritas P1 — Wajib sebelum sign-off PRD

### P1.1 Refactor authorization dengan Policy

Policy yang disarankan: `LaporanAnggaranPolicy`, `DokumenLaporanPolicy`, `TindakLanjutMonitoringPolicy`, `EwsAlertPolicy`, `SkoringRisikoPolicy`, `UserPolicy`, dan `PeriodeAnggaranPolicy`.

Definisikan kemampuan `view`, `viewAny`, `create`, `update`, `delete`, `verify`, `score`, `uploadEvidence`, dan `downloadEvidence`.

### P1.2 Pisahkan tanggung jawab komponen Livewire

- Komponen Livewire menangani state dan interaksi UI.
- Validator/Form Request menangani validasi.
- Action/service menangani operasi bisnis.
- Policy menangani otorisasi.
- Transaction digunakan untuk operasi multi-tabel.

Action yang disarankan: `SubmitLaporanAnggaranAction`, `UploadDokumenLaporanAction`, `SubmitTindakLanjutAction`, `VerifyTindakLanjutAction`, dan `SaveRiskAssessmentAction`.

### P1.3 Perbaiki alur status laporan dan tiket

Validasi transisi laporan: `draft -> submitted`, `submitted -> revisi`, `submitted -> verified`, `revisi -> submitted`, dan `revisi -> verified`.

Validasi transisi tiket: `Pending -> In Progress`, `In Progress -> Verified`, dan `Pending -> Escalated`.

- Tiket `Verified` tidak dapat diedit operator.
- EWS hanya `Resolved` setelah tiket diverifikasi.
- Verifikasi hanya boleh dilakukan admin.
- Perubahan status mencatat actor dan waktu.

### P1.4 Dokumentasikan dan uji aturan scoring

Konfirmasi definisi P1–P5, batas skor, kategori risiko, rekomendasi, hubungan skor dengan EWS, aturan eskalasi, perhitungan hari kerja/kalender, dan perlakuan dokumen kosong/kurang/tidak valid.

### P1.5 Pastikan export/cetak memenuhi PRD

Jika PRD mensyaratkan PDF, implementasikan generator PDF sesungguhnya, validasi content type `application/pdf`, sertakan metadata dokumen, dan terapkan authorization sebelum download.

## Prioritas P2 — Penguatan kualitas dan operasional

### P2.1 Audit log

Catat login berhasil/gagal, logout, perubahan user/role, perubahan laporan, upload/hapus dokumen, scoring, EWS, verifikasi tiket, impersonation, dan download dokumen sensitif.

Field minimum: `actor_id`, `action`, `subject_type`, `subject_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, dan `created_at`.

### P2.2 Database integrity

- Tinjau foreign key dan unique constraint.
- Tambahkan index pada `laporan_id`, `balai_id`, `periode_id`, `status_laporan`, `status_ews`, dan `batas_waktu_koreksi`.
- Tambahkan constraint nilai keuangan dan skor bila didukung database.

### P2.3 Scheduler dan queue production

Pastikan server menjalankan `php artisan schedule:run` setiap menit. Pastikan `simadu:scan-ews` idempotent, alert tidak terduplikasi, kegagalan tercatat, dan queue worker aktif bila dibutuhkan.

### P2.4 Hardening HTTP dan session

Periksa CSRF, method HTTP mutation, rate limit, session regeneration/invalidation, security headers, dan validasi redirect.

### P2.5 Dependency dan CI security

Pipeline CI sebaiknya menjalankan `composer validate --strict`, `composer audit --locked`, Pint, PHPStan, Pest, `npm audit`, dan `npm run build`.

## Rencana pengujian

### Autentikasi dan otorisasi

- Guest ditolak dari route protected.
- Operator ditolak dari admin route dan switch user.
- Operator tidak dapat menjadi admin atau mengakses balai lain.
- Direct Livewire call ke record balai lain ditolak.

### Laporan dan upload

- Angka negatif/non-numeric ditolak.
- Periode invalid dan submit lintas balai ditolak.
- Timestamp first submission tidak berubah saat submit ulang.
- File invalid, MIME palsu, terlalu besar, atau kosong ditolak.
- File disimpan private dan download lintas balai ditolak.

### Scoring dan EWS

- Skor tiap parameter 0–3 dan total 0–15.
- Kategori diuji pada batas 4/5 dan 9/10.
- EWS 1, EWS 2, EWS 3, idempotensi command, dan penyelesaian tiket diuji.

### Skenario end-to-end PRD

1. Admin membuat periode.
2. Operator login.
3. Operator mengunggah laporan dan dokumen.
4. Admin memvalidasi dokumen.
5. Sistem menghitung risiko dan membuat EWS bila diperlukan.
6. Operator mengirim tindak lanjut.
7. Admin memverifikasi tiket.
8. Sistem memperbarui laporan dan EWS.
9. Admin mengunduh rekap PDF.

## Urutan pelaksanaan

### Sprint 1 — Keamanan akses

Tutup switch user, implementasikan Policy, perbaiki isolasi balai, ubah logout menjadi POST-only, dan tambahkan test authorization.

**Output:** tidak ada privilege escalation atau IDOR yang diketahui.

### Sprint 2 — Validasi dan integritas data

Validasi laporan/scoring, validasi transisi status, tambahkan constraint/index, dan gunakan transaction.

**Output:** data invalid tidak dapat disimpan.

### Sprint 3 — Upload dan dokumen

Implementasikan upload aktual, private storage, secure download, metadata file, serta test access control.

**Output:** requirement dokumen PRD terpenuhi secara teknis.

### Sprint 4 — PRD dan workflow

Finalisasi scoring, validasi EWS, perbaiki alur tiket, implementasikan PDF, dan jalankan skenario end-to-end.

**Output:** traceability matrix PRD dapat dibuktikan dengan test.

### Sprint 5 — Production hardening

Terapkan konfigurasi production, security headers, scheduler, queue, audit log, CI security checks, dan deployment checklist.

**Output:** aplikasi siap staging/UAT.

## Definition of Done

- Semua temuan P0 ditutup.
- Test authorization dan IDOR lulus.
- Upload file aktual tersedia dan aman.
- `APP_DEBUG=false` pada staging/production.
- Quick login/demo tidak aktif di production.
- Tidak ada akses lintas balai.
- Semua mutation menggunakan method HTTP yang sesuai.
- PRD formal tersedia dan disetujui.
- Requirement utama memiliki bukti implementasi dan test.
- Composer audit, Pint, PHPStan, dan Pest lulus.
- Scheduler EWS diuji di staging.
- Rollback plan dan backup database tersedia.

## Status yang direkomendasikan untuk `PROGRESS.md`

> Functional prototype / staging candidate — belum production-ready karena masih terdapat temuan privilege escalation, object-level authorization, validasi input, dan upload dokumen.
