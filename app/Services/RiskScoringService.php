<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RiskCategory;
use App\Models\LaporanAnggaran;
use App\Models\SkoringRisiko;
use Carbon\Carbon;

class RiskScoringService
{
    /**
     * Hitung prakiraan skor P1 (Ketepatan Waktu) berdasarkan tanggal submit pertama vs tenggat lapor.
     */
    public function calculateP1(LaporanAnggaran $laporan): int
    {
        if (! $laporan->tgl_submit_pertama) {
            $tenggat = $laporan->periodeAnggaran->tgl_tenggat_lapor;
            if (Carbon::now()->isAfter($tenggat)) {
                return 3;
            }

            return 1;
        }

        $submitTime = Carbon::parse($laporan->tgl_submit_pertama);
        $deadline = Carbon::parse($laporan->periodeAnggaran->tgl_tenggat_lapor);

        if ($submitTime->lessThanOrEqualTo($deadline)) {
            return 0; // Tepat waktu
        }

        $daysLate = (int) abs($submitTime->diffInDays($deadline));

        if ($daysLate <= 2) {
            return 1; // Terlambat ringan / dalam batas toleransi
        }

        if ($daysLate <= 5) {
            return 2; // Terlambat & perlu pengingat
        }

        return 3; // Melewati batas waktu > 5 hari

    }

    /**
     * Hitung prakiraan skor P2 (Kelengkapan Dokumen) berdasarkan status validasi dokumen.
     */
    public function calculateP2(LaporanAnggaran $laporan): int
    {
        $dokumen = $laporan->dokumenLaporan;

        if ($dokumen->isEmpty()) {
            return 3;
        }

        $tidakValidCount = $dokumen->where('status_validasi', 'tidak_valid')->count();
        $kurangCount = $dokumen->where('status_validasi', 'kurang')->count();

        if ($tidakValidCount > 0) {
            return 3; // Ada dokumen tidak valid
        }

        if ($kurangCount >= 2) {
            return 2; // >= 2 dokumen kurang
        }

        if ($kurangCount === 1) {
            return 1; // 1 dokumen kurang minor
        }

        return 0; // Semua lengkap
    }

    /**
     * Tentukan Kategori Risiko berdasarkan total skor (0-15).
     */
    public function determineCategory(int $totalScore): RiskCategory
    {
        if ($totalScore <= 4) {
            return RiskCategory::Rendah;
        }

        if ($totalScore <= 9) {
            return RiskCategory::Sedang;
        }

        return RiskCategory::Tinggi;
    }

    /**
     * Dapatkan rekomendasi tindakan otomatis dari kategori risiko.
     */
    public function getSystemRecommendation(RiskCategory $category, int $totalScore): string
    {
        return match ($category) {
            RiskCategory::Rendah => "Kondisi Balai terkendali (Skor {$totalScore}/15). Lakukan pemantauan berkala dan pertahankan ketepatan pelaporan untuk kelancaran pencairan Tahap II.",
            RiskCategory::Sedang => "Terdapat catatan kekurangan/deviasi yang memerlukan perhatian (Skor {$totalScore}/15). Diterbitkan tiket peringatan dini (EWS) dan instruksi perbaikan dokumen/klarifikasi.",
            RiskCategory::Tinggi => "Kondisi berisiko tinggi menghambat akuntabilitas dan pencairan anggaran (Skor {$totalScore}/15). Diperlukan intervensi prioritas, koordinasi langsung, dan tindak lanjut segera oleh Admin Dinas DP2KBP3A.",
        };
    }

    /**
     * Simpan atau update skoring risiko untuk suatu laporan.
     */
    public function saveScore(
        LaporanAnggaran $laporan,
        int $p1,
        int $p2,
        int $p3,
        int $p4,
        int $p5,
        ?int $penilaiId = null,
        ?string $catatan = null
    ): SkoringRisiko {
        $p1 = max(0, min(3, $p1));
        $p2 = max(0, min(3, $p2));
        $p3 = max(0, min(3, $p3));
        $p4 = max(0, min(3, $p4));
        $p5 = max(0, min(3, $p5));

        $totalScore = $p1 + $p2 + $p3 + $p4 + $p5;
        $category = $this->determineCategory($totalScore);
        $recommendation = $this->getSystemRecommendation($category, $totalScore);

        return SkoringRisiko::updateOrCreate(
            ['laporan_id' => $laporan->id],
            [
                'penilai_id' => $penilaiId,
                'skor_p1' => $p1,
                'skor_p2' => $p2,
                'skor_p3' => $p3,
                'skor_p4' => $p4,
                'skor_p5' => $p5,
                'total_skor' => $totalScore,
                'kategori_risiko' => $category,
                'rekomendasi_sistem' => $recommendation,
                'catatan_penilai' => $catatan,
            ]
        );
    }
}
