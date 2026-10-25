<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EwsCode;
use App\Enums\EwsStatus;
use App\Enums\ReportStatus;
use App\Enums\RiskCategory;
use App\Models\DokumenLaporan;
use App\Models\EwsAlert;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\SkoringRisiko;
use App\Models\TindakLanjutMonitoring;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LaporanDummySeeder extends Seeder
{
    public function run(): void
    {
        $periode = PeriodeAnggaran::find(1);
        if (! $periode) {
            return;
        }

        $admin = User::where('role', 'admin')->first();
        $adminId = $admin?->id;

        $dokumenWajib = $periode->dokumen_wajib_json ?? [
            'Laporan Realisasi Anggaran (LRA)',
            'Surat Pertanggungjawaban Mutlak (SPTJM)',
            'Buku Kas Umum & Kuitansi',
            'Dokumentasi Kegiatan',
            'Rencana Kebutuhan Tahap II',
        ];

        $balaiData = [
            1 => ['pagu' => 200000000, 'realisasi' => 110000000, 'status' => ReportStatus::Verified, 'p1' => 0, 'p2' => 0, 'p3' => 1, 'p4' => 1, 'p5' => 1, 'first_right' => true, 'late_days' => -2],
            2 => ['pagu' => 175000000, 'realisasi' => 95000000, 'status' => ReportStatus::Verified, 'p1' => 0, 'p2' => 1, 'p3' => 1, 'p4' => 1, 'p5' => 1, 'first_right' => true, 'late_days' => -1],
            3 => ['pagu' => 150000000, 'realisasi' => 65000000, 'status' => ReportStatus::Submitted, 'p1' => 1, 'p2' => 2, 'p3' => 2, 'p4' => 1, 'p5' => 1, 'first_right' => false, 'late_days' => 1],
            4 => ['pagu' => 160000000, 'realisasi' => 84000000, 'status' => ReportStatus::Verified, 'p1' => 0, 'p2' => 0, 'p3' => 1, 'p4' => 0, 'p5' => 1, 'first_right' => true, 'late_days' => -3],
            5 => ['pagu' => 180000000, 'realisasi' => 75000000, 'status' => ReportStatus::Submitted, 'p1' => 1, 'p2' => 1, 'p3' => 2, 'p4' => 1, 'p5' => 1, 'first_right' => false, 'late_days' => 1],
            6 => ['pagu' => 135000000, 'realisasi' => 45000000, 'status' => ReportStatus::Revisi, 'p1' => 3, 'p2' => 3, 'p3' => 2, 'p4' => 2, 'p5' => 2, 'first_right' => false, 'late_days' => 4],
            7 => ['pagu' => 120000000, 'realisasi' => 62000000, 'status' => ReportStatus::Verified, 'p1' => 0, 'p2' => 1, 'p3' => 1, 'p4' => 0, 'p5' => 1, 'first_right' => true, 'late_days' => -2],
            8 => ['pagu' => 130000000, 'realisasi' => 58000000, 'status' => ReportStatus::Revisi, 'p1' => 2, 'p2' => 2, 'p3' => 2, 'p4' => 1, 'p5' => 1, 'first_right' => false, 'late_days' => 2],
        ];

        foreach ($balaiData as $balaiId => $d) {
            $serapan = round(($d['realisasi'] / $d['pagu']) * 100, 2);
            $submitTime = Carbon::now()->addDays($d['late_days']);

            $laporan = LaporanAnggaran::updateOrCreate(
                [
                    'balai_id' => $balaiId,
                    'periode_id' => $periode->id,
                ],
                [
                    'rencana_anggaran' => $d['pagu'],
                    'realisasi_anggaran' => $d['realisasi'],
                    'persentase_serapan' => $serapan,
                    'tgl_submit_pertama' => $submitTime,
                    'lengkap_pengiriman_pertama' => $d['first_right'],
                    'status_laporan' => $d['status'],
                    'catatan_balai' => "Laporan operasional dan program Bangga Kencana Balai Penyuluh KB {$balaiId}.",
                ]
            );

            // Dokumen Laporan
            foreach ($dokumenWajib as $idx => $namaDok) {
                $statusVal = 'lengkap';
                if (! $d['first_right'] && $idx >= 3) {
                    $statusVal = $d['p2'] >= 3 ? 'tidak_valid' : 'kurang';
                }

                DokumenLaporan::updateOrCreate(
                    [
                        'laporan_id' => $laporan->id,
                        'nama_dokumen_wajib' => $namaDok,
                    ],
                    [
                        'file_path' => 'dokumen/sample.pdf',
                        'file_name' => str_replace(' ', '_', strtolower($namaDok)).'.pdf',
                        'file_size' => 1024 * rand(200, 1500),
                        'status_validasi' => $statusVal,
                        'catatan_dokumen' => $statusVal !== 'lengkap' ? 'Perlu perbaikan kelengkapan tanda tangan atau lampiran bukti fisik.' : 'Terverifikasi lengkap dan sah.',
                    ]
                );
            }

            // Skoring Risiko
            $totalSkor = $d['p1'] + $d['p2'] + $d['p3'] + $d['p4'] + $d['p5'];
            $kategori = $totalSkor <= 4 ? RiskCategory::Rendah : ($totalSkor <= 9 ? RiskCategory::Sedang : RiskCategory::Tinggi);

            SkoringRisiko::updateOrCreate(
                ['laporan_id' => $laporan->id],
                [
                    'penilai_id' => $adminId,
                    'skor_p1' => $d['p1'],
                    'skor_p2' => $d['p2'],
                    'skor_p3' => $d['p3'],
                    'skor_p4' => $d['p4'],
                    'skor_p5' => $d['p5'],
                    'total_skor' => $totalSkor,
                    'kategori_risiko' => $kategori,
                    'rekomendasi_sistem' => match ($kategori) {
                        RiskCategory::Rendah => 'Kondisi terkendali. Siap untuk proses rekomendasi pencairan Tahap II.',
                        RiskCategory::Sedang => 'Perlu pemantauan berkala dan perbaikan dokumen pendukung minor.',
                        RiskCategory::Tinggi => 'Perlu pendampingan intensif dari tim dinas untuk percepatan realisasi dan akuntabilitas dokumen.',
                    },
                    'catatan_penilai' => 'Penilaian rutin tim verifikasi dinas DP2KBP3A.',
                ]
            );

            // Sample EWS Alert jika ada temuan
            if ($totalSkor >= 5) {
                $alert = EwsAlert::updateOrCreate(
                    [
                        'laporan_id' => $laporan->id,
                        'kode_ews' => $d['p1'] >= 2 ? EwsCode::EWS_1 : EwsCode::EWS_2,
                    ],
                    [
                        'level_ews' => $totalSkor >= 10 ? 'Tinggi' : 'Sedang',
                        'kondisi_pemicu' => $d['p1'] >= 2 ? 'Keterlambatan penyampaian laporan' : 'Terdapat kekurangan kelengkapan bukti transaksi/kuitansi',
                        'penyebab' => 'Kendala pengumpulan berkas dari lapangan',
                        'penanggung_jawab' => 'Kepala Balai & PLKB',
                        'batas_waktu_koreksi' => Carbon::now()->addDays(2),
                        'status_ews' => $totalSkor >= 10 ? EwsStatus::Escalated : EwsStatus::Open,
                        'jumlah_pengingat' => $totalSkor >= 10 ? 2 : 1,
                    ]
                );

                // Sample Tiket Tindak Lanjut
                TindakLanjutMonitoring::updateOrCreate(
                    [
                        'laporan_id' => $laporan->id,
                        'ews_id' => $alert->id,
                    ],
                    [
                        'instruksi_perbaikan' => 'Lengkapi rincian bukti kas pengeluaran dan rekapitulasi kuitansi sah sebelum batas waktu koreksi.',
                        'file_bukti_koreksi' => null,
                        'catatan_balai' => 'Sedang dalam proses konfirmasi ke koordinator lapangan.',
                        'waktu_mulai_temuan' => Carbon::now()->subDays(1),
                        'waktu_selesai_verifikasi' => null,
                        'status' => 'In Progress',
                    ]
                );
            }
        }
    }
}
