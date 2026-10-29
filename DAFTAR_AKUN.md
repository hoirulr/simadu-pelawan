# Daftar Akun dan Kredensial Pengguna
**SI MADU PELAWAN**  
*Sistem Informasi Monitoring, Dukungan, dan Pengawasan Pelaporan Anggaran Balai KB*  
Dinas Pengendalian Penduduk, Keluarga Berencana, Pemberdayaan Perempuan dan Perlindungan Anak (DP2KBP3A) Kabupaten Bangka

---

## 1. Informasi Akses Sistem

- **URL Halaman Masuk:** `/login` atau `/` (Halaman Utama)
- **Field Username:** Masukkan alamat email pengguna (contoh: `admin@simadupelawan.go.id` atau `operator.[kecamatan]@simadupelawan.go.id`)
- **Field Password:** Masukkan kata sandi sesuai daftar di bawah

---

## 2. Akun Administrator Dinas DP2KBP3A

| Parameter | Detail |
| :--- | :--- |
| **Nama Lengkap** | Admin Dinas DP2KBP3A |
| **Peran (Role)** | `admin` (Administrator Dinas) |
| **Username / Email** | `admin@simadupelawan.go.id` |
| **Password** | `0b1H1JDIaTLmI6ts4h8c` |
| **Hak Akses** | Akses penuh seluruh sistem: Dashboard Utama, Monitoring Balai KB, Pengesahan & Validasi Laporan, Analisis Realisasi & Risiko, Penyesuaian Nilai Ekonomis, Manajemen Pengguna & Balai KB, Pengaturan Periode Pelaporan. |

---

## 3. Akun Operator Balai Penyuluh KB (8 Kecamatan)

Semua akun operator Balai Penyuluh KB menggunakan kata sandi terstandar berikut:
> **Password Default Operator:** `0b1H1JDIaTLmI6ts4h8c`

| No | Balai Penyuluh KB | Kecamatan | Username / Email Login | Password | Kepala Balai | Kontak |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | Balai KB Sungailiat | Sungailiat | `operator.sungailiat@simadupelawan.go.id` | `0b1H1JDIaTLmI6ts4h8c` | Hj. Mardiana, S.Sos | 0812-7890-0001 |
| 2 | Balai KB Merawang | Merawang | `operator.merawang@simadupelawan.go.id` | `0b1H1JDIaTLmI6ts4h8c` | Bambang Irawan, SKM | 0812-7890-0002 |
| 3 | Balai KB Pemali | Pemali | `operator.pemali@simadupelawan.go.id` | `0b1H1JDIaTLmI6ts4h8c` | Nuraini, S.AP | 0812-7890-0003 |
| 4 | Balai KB Mendo Barat | Mendo Barat | `operator.mendobarat@simadupelawan.go.id` | `0b1H1JDIaTLmI6ts4h8c` | Zulkifli, S.IP | 0812-7890-0004 |
| 5 | Balai KB Belinyu | Belinyu | `operator.belinyu@simadupelawan.go.id` | `0b1H1JDIaTLmI6ts4h8c` | Hendri Kusuma, S.Sos | 0812-7890-0005 |
| 6 | Balai KB Riau Silip | Riau Silip | `operator.riausilip@simadupelawan.go.id` | `0b1H1JDIaTLmI6ts4h8c` | Siti Rohimah, S.E. | 0812-7890-0006 |
| 7 | Balai KB Bakam | Bakam | `operator.bakam@simadupelawan.go.id` | `0b1H1JDIaTLmI6ts4h8c` | Drs. Supriyanto | 0812-7890-0007 |
| 8 | Balai KB Puding Besar | Puding Besar | `operator.pudingbesar@simadupelawan.go.id` | `0b1H1JDIaTLmI6ts4h8c` | Fitria Handayani, S.KM | 0812-7890-0008 |

**Hak Akses Operator Balai:**
- Akses portal penginputan laporan kegiatan dan anggaran (`/balai/laporan`).
- Pengunggahan dokumen bukti pertanggungjawaban (SPJ, Kuitansi, Notula, Dokumentasi Foto).
- Monitoring status review dinas dan respon tindak lanjut rekomendasi ekonomis (`/balai/status-risiko`).
- Terisolasi hanya untuk data Balai KB masing-masing.

---

## 4. Perintah Seeder Database

Untuk mereset atau menerapkan seluruh akun di atas ke database aplikasi:

```bash
php artisan db:seed --class=RoleUserSeeder
```

Atau jalankan seeder lengkap:

```bash
php artisan migrate:fresh --seed
```

---
*Dokumen ini dibuat otomatis dan disinkronkan dengan seeder `database/seeders/RoleUserSeeder.php`.*

