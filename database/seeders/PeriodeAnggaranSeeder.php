<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PeriodeAnggaran;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PeriodeAnggaranSeeder extends Seeder
{
    public function run(): void
    {
        $dokumenWajibStandar = [
            'Laporan Realisasi Anggaran (LRA) Per Kegiatan',
            'Surat Pernyataan Tanggung Jawab Mutlak (SPTJM)',
            'Buku Kas Umum (BKU) & Rekapitulasi Kuitansi Sah',
            'Dokumentasi Bukti Fisik / Foto Pelaksanaan Kegiatan',
            'Rencana Kebutuhan Operasional & Penyerapan Lanjutan',
        ];

        // Periode Tahap I Tahun Berjalan (Aktif)
        PeriodeAnggaran::updateOrCreate(
            ['id' => 1],
            [
                'tahun' => 2026,
                'tahap' => 'Tahap I',
                'bulan' => 6,
                'pagu_anggaran' => 1250000000.00, // Rp 1,25 Miliar total pagu Tahap I (8 Balai)
                'target_realisasi_persen' => 50.00,
                'tgl_tenggat_lapor' => Carbon::now()->addDays(5),
                'dokumen_wajib_json' => $dokumenWajibStandar,
                'is_active' => true,
            ]
        );

        // Periode Tahap II Tahun Berjalan
        PeriodeAnggaran::updateOrCreate(
            ['id' => 2],
            [
                'tahun' => 2026,
                'tahap' => 'Tahap II',
                'bulan' => 12,
                'pagu_anggaran' => 1500000000.00, // Rp 1,5 Miliar total pagu Tahap II
                'target_realisasi_persen' => 95.00,
                'tgl_tenggat_lapor' => Carbon::now()->addMonths(6),
                'dokumen_wajib_json' => $dokumenWajibStandar,
                'is_active' => false,
            ]
        );
    }
}
