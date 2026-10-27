# Rekomendasi Perbaikan Hasil Audit Remediation

**Tanggal:** 27 Oktober 2026  
**Basis audit:** `REMEDIATION_PLAN.md` tanggal 26 Oktober 2026  
**Commit yang diaudit:** `5b36363 Perbaikan Bug P2`  
**Status rekomendasi:** Wajib ditindaklanjuti sebelum production

## 1. Ringkasan

Implementasi remediasi sudah menutup sebagian besar risiko utama pada autentikasi, authorization, upload dokumen, workflow tindak lanjut, scoring, audit log, dan export PDF. Namun aplikasi belum dapat dinyatakan production-ready karena masih terdapat gap terhadap acceptance criteria pada scheduler, validasi di layer business action, aturan realisasi anggaran, coverage authorization, CI security, dan verifikasi deployment.

Dokumen ini menjadi daftar tindakan perbaikan yang disarankan setelah audit dan dapat digunakan sebagai checklist tindak lanjut.

## 2. Prioritas P0 — Wajib sebelum production

### P0-R1. Perbaiki frekuensi scheduler EWS

**Temuan:**

`routes/console.php` menjadwalkan `simadu:scan-ews` dengan `dailyAt('08:00')`, sedangkan rencana remediasi mensyaratkan server menjalankan scheduler setiap menit.

**Tindakan:**

1. Tentukan frekuensi pemindaian sesuai kebutuhan bisnis. Rekomendasi default: `everyMinute()` bila EWS harus mendeteksi perubahan tepat waktu.
2. Pastikan server menjalankan `php artisan schedule:run` setiap menit melalui cron atau scheduler platform.
3. Pastikan `withoutOverlapping()` menggunakan cache/lock yang tersedia di production.
4. Dokumentasikan konfigurasi worker dan scheduler pada deployment checklist.
5. Pastikan command mencatat keberhasilan dan kegagalan dengan `Log` facade yang diimpor secara eksplisit.

**Acceptance criteria:**

- `php artisan schedule:list` menampilkan frekuensi yang disetujui.
- `simadu:scan-ews` berjalan sesuai jadwal di staging.
- Dua eksekusi bersamaan tidak membuat alert duplikat.
- Kegagalan command tercatat dan dapat dimonitor.

### P0-R2. Pindahkan validasi bisnis ke Action/service

**Temuan:**

Validasi laporan dan file terutama dilakukan di komponen Livewire. Action masih dapat dipanggil dengan nilai atau file yang belum tervalidasi.

**Tindakan:**

1. Tambahkan validasi eksplisit pada `SubmitLaporanAnggaranAction`.
2. Validasi ulang `periode_id` dan `balai_id` sebelum menyimpan.
3. Validasi kepemilikan balai berdasarkan actor di dalam Action.
4. Tambahkan validasi panjang catatan dan batas maksimum nominal.
5. Tambahkan validasi file pada `UploadDokumenLaporanAction` dan `SubmitTindakLanjutAction`.
6. Gunakan objek validasi atau value object terpisah bila validasi mulai kompleks.
7. Pertahankan validasi Livewire untuk feedback UI, tetapi jangan menjadikannya satu-satunya boundary keamanan.

**Acceptance criteria:**

- Pemanggilan Action secara langsung menolak input invalid.
- Operator tidak dapat mengirim laporan lintas balai walaupun Action dipanggil tanpa Livewire.
- File invalid, file kosong, MIME tidak diizinkan, dan file terlalu besar ditolak.
- Test Action langsung tersedia.

### P0-R3. Tetapkan aturan realisasi di atas rencana

**Temuan:**

Perhitungan serapan tersedia, tetapi belum ada aturan resmi ketika `realisasi_anggaran > rencana_anggaran`.

**Tindakan:**

1. Konfirmasi aturan bisnis dengan pemilik PRD.
2. Rekomendasi default: tolak realisasi yang melebihi rencana kecuali terdapat mekanisme persetujuan/justifikasi.
3. Jika diperbolehkan, tambahkan field justifikasi dan audit perubahan.
4. Dokumentasikan aturan pada dokumen scoring/aturan bisnis.
5. Tambahkan test pada kondisi sama dengan rencana, di bawah rencana, dan di atas rencana.

**Acceptance criteria:**

- Aturan tertulis dan disetujui.
- Perilaku server konsisten dengan aturan.
- Persentase serapan dan status laporan diuji pada boundary.

### P0-R4. Lengkapi test input invalid dan IDOR

Tambahkan test untuk:

- string non-numeric pada nominal;
- nominal melebihi batas maksimum;
- `periode_id` tidak ada;
- `balai_id` tidak ada;
- submit lintas balai melalui Action langsung;
- file kosong;
- MIME palsu/content mismatch;
- file lebih besar dari batas;
- path dokumen yang tidak valid;
- download dokumen lintas balai;
- direct Livewire call untuk seluruh action penting;
- authorization `view`, `viewAny`, `create`, `update`, `delete`, `verify`, `score`, dan download.

## 3. Prioritas P1 — Wajib sebelum sign-off PRD

### P1-R1. Formalkan transisi status

Implementasikan validator atau state transition service untuk status laporan:

```text
draft -> submitted
submitted -> revisi
submitted -> verified
revisi -> submitted
revisi -> verified
```

Implementasikan aturan status tiket:

```text
Pending -> In Progress
In Progress -> Verified
Pending -> Escalated
```

**Rekomendasi:** gunakan enum untuk status tiket agar tidak bergantung pada string literal seperti `Pending`, `In Progress`, dan `Verified`.

**Acceptance criteria:**

- Transisi yang tidak diizinkan ditolak dengan exception/domain error.
- Actor dan waktu perubahan status dicatat.
- Tiket `Verified` tidak dapat diedit operator.
- EWS hanya `Resolved` setelah verifikasi yang sah.

### P1-R2. Lengkapi authorization untuk semua komponen Livewire

Audit dan tambahkan authorization eksplisit pada:

- halaman master periode;
- halaman master user;
- workspace admin;
- status risiko;
- dashboard yang menampilkan data domain;
- action delete/update/verify pada setiap komponen.

Query operator sebaiknya langsung dibatasi menggunakan `auth()->user()->balai_id`, bukan hanya mengandalkan nilai property Livewire yang dapat dimanipulasi.

### P1-R3. Pisahkan validasi, authorization, dan operasi bisnis

Refactor bertahap:

1. Komponen Livewire mengelola state dan interaksi.
2. Validator/Form Request atau validator class mengelola validasi.
3. Policy mengelola authorization.
4. Action/service mengelola operasi bisnis dan transaction.
5. Audit logger dipanggil dari boundary operasi bisnis.

Prioritaskan halaman master dan komponen yang masih melakukan mutation langsung terhadap model.

### P1-R4. Buat traceability matrix PRD

Buat matrix internal yang memetakan:

| Requirement | Implementasi | Policy/authorization | Test | Status |
|---|---|---|---|---|
| Upload dokumen | Action + Livewire | Dokumen policy | Upload/storage test | Selesai/sebagian |
| Scoring risiko | Risk scoring service | Skoring policy | Risk scoring test | Selesai/sebagian |
| EWS | EWS service + command | EWS policy | EWS test | Selesai/sebagian |
| Export PDF | Export controller/service | Periode policy | PDF test | Selesai |

Matrix harus mencakup seluruh requirement utama PRD, bukan hanya area keamanan.

## 4. Prioritas P2 — Penguatan kualitas dan operasional

### P2-R1. Lengkapi pipeline CI security

Tambahkan langkah berikut ke workflow CI:

```bash
composer validate --strict
composer audit --locked
vendor/bin/pint --test
vendor/bin/phpstan analyse
php artisan test --compact
npm audit
npm run build
```

Jangan menyatakan pipeline aman sebelum semua langkah memiliki hasil yang dapat diverifikasi.

### P2-R2. Stabilkan pemeriksaan PHPStan dan full test suite

1. Jalankan PHPStan dengan konfigurasi yang sesuai untuk CI.
2. Identifikasi penyebab timeout pada lingkungan lokal.
3. Pisahkan test suite bila diperlukan, tetapi tetap sediakan job full suite.
4. Simpan artifact/log ketika test gagal.
5. Pastikan test menggunakan database dan storage yang konsisten pada CI.

### P2-R3. Verifikasi frontend build

Kegagalan lokal menunjukkan masalah `spawn EPERM` dan dependency native Tailwind Oxide. Tindakan:

1. Jalankan `npm ci` dari lockfile.
2. Pastikan dependency native untuk platform CI terpasang.
3. Bersihkan dan install ulang `node_modules` bila diperlukan.
4. Verifikasi Node.js version yang digunakan lokal sama dengan CI.
5. Jalankan `npm run build` di CI dan staging.

### P2-R4. Dokumentasikan queue worker production

Karena production menggunakan `QUEUE_CONNECTION=database`, dokumentasikan:

- command worker;
- jumlah worker;
- restart strategy;
- failed jobs monitoring;
- Supervisor/systemd/Laravel Cloud configuration;
- prosedur deploy dan rollback.

### P2-R5. Lengkapi production deployment checklist

Checklist minimum:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- `APP_KEY` unik dan tersimpan aman;
- `APP_URL` HTTPS;
- secure, HTTP-only, dan SameSite cookie;
- database credential dengan privilege minimum;
- `.env` tidak masuk Git;
- scheduler aktif setiap menit;
- queue worker aktif;
- backup database berhasil;
- restore backup pernah diuji;
- error page tidak menampilkan stack trace;
- storage file tetap private;
- rollback plan tersedia.

### P2-R6. Perkuat audit log

Pastikan audit log mencakup minimal:

- login berhasil/gagal;
- logout;
- perubahan user dan role;
- perubahan laporan;
- upload, penggantian, dan penghapusan dokumen;
- download dokumen sensitif;
- scoring;
- perubahan EWS;
- submit dan verifikasi tiket;
- impersonation;
- actor, subject, IP, user agent, waktu, nilai lama, dan nilai baru.

Tambahkan test untuk memastikan mutation pada halaman master juga tercatat.

## 5. Urutan implementasi yang disarankan

### Tahap 1 — Tutup blocker production

1. Perbaiki scheduler EWS.
2. Tambahkan validasi di Action/service.
3. Tetapkan aturan realisasi di atas rencana.
4. Tambahkan test input invalid dan direct Action.
5. Verifikasi semua download dan mutation authorization.

### Tahap 2 — Stabilkan workflow dan authorization

1. Formalisasi state transition.
2. Refactor mutation halaman master.
3. Lengkapi Policy test.
4. Lengkapi traceability matrix PRD.

### Tahap 3 — CI dan deployment

1. Tambahkan Composer audit, npm audit, PHPStan, dan full test ke CI.
2. Perbaiki frontend build.
3. Konfigurasikan scheduler dan queue worker production.
4. Uji backup/restore dan rollback.
5. Jalankan staging/UAT end-to-end.

## 6. Definition of Done tambahan

Perbaikan dapat dinyatakan selesai apabila seluruh kondisi berikut terpenuhi:

- Scheduler EWS berjalan sesuai frekuensi yang disetujui.
- Semua Action menolak input invalid tanpa bergantung pada Livewire.
- Aturan realisasi di atas rencana telah disetujui dan diuji.
- Tidak ada akses lintas balai pada route, Livewire action, Action, dan download.
- Semua status workflow memiliki transisi yang tervalidasi.
- Test authorization, upload, IDOR, scoring, EWS, PDF, dan audit log lulus.
- `composer validate --strict` lulus.
- `composer audit --locked` lulus atau exception terdokumentasi.
- Pint lulus.
- PHPStan lulus.
- Full Pest suite lulus.
- `npm audit` dan `npm run build` lulus.
- Scheduler, queue, backup, restore, dan rollback teruji di staging.
- Tidak ada secret production di repository.
- `APP_DEBUG=false` terverifikasi pada environment staging/production.

## 7. Kesimpulan

Remediasi saat ini berada pada status **staging candidate dengan beberapa blocker sebelum production**. Fokus utama yang harus diselesaikan terlebih dahulu adalah scheduler EWS, validasi business boundary, aturan realisasi anggaran, kelengkapan test authorization/IDOR, dan pipeline CI security. Setelah seluruh item P0-R1 sampai P0-R4 selesai dan terverifikasi, aplikasi dapat dilanjutkan ke UAT dengan risiko yang lebih terkendali.
