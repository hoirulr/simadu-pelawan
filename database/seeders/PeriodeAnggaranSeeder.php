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
            'Dokumen Pelaksanaan Anggaran (DPA)',
            'Surat Keputusan (SK)',
            'Kwitansi',
            'Dokumentasi Kegiatan',
            'Rincian Biaya Kegiatan',
            'Kartu Kendali',
        ];

        $tahun = (int) now()->year;
        $bulanSekarang = (int) now()->month;

        // Buat 12 Periode Bulanan untuk tahun berjalan
        for ($b = 1; $b <= 12; $b++) {
            $namaBulan = PeriodeAnggaran::BULAN_NAMES[$b] ?? "Bulan {$b}";
            // Tenggat lapor aktif fleksibel di masa depan (misal: 10 hari ke depan)
            $tenggatLapor = Carbon::now()->addDays(10 + ($b * 5));

            PeriodeAnggaran::updateOrCreate(
                ['id' => $b],
                [
                    'tahun' => $tahun,
                    'tahap' => $namaBulan,
                    'bulan' => $b,
                    'pagu_anggaran' => 200000000.00, // Rp 200 Juta pagu bulanan (8 Balai)
                    'target_realisasi_persen' => round(($b / 12) * 100, 2),
                    'tgl_tenggat_lapor' => $tenggatLapor,
                    'dokumen_wajib_json' => $dokumenWajibStandar,
                    'is_active' => ($b === $bulanSekarang),
                ]
            );
        }
    }
}
