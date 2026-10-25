<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EwsCode;
use App\Enums\EwsStatus;
use App\Models\EwsAlert;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use Carbon\Carbon;

class EwsEngineService
{
    /**
     * Pindai tenggat waktu laporan untuk seluruh Balai pada periode anggaran aktif (EWS 1).
     */
    public function scanSubmissionDeadlines(PeriodeAnggaran $periode): array
    {
        $alertsGenerated = [];
        $now = Carbon::now();
        $deadline = Carbon::parse($periode->tgl_tenggat_lapor);

        $daysRemaining = $now->diffInDays($deadline, false);

        // Jika mendekati tenggat (H-3 s.d H-0) atau sudah melewati tenggat
        if ($daysRemaining <= 3) {
            $laporanList = LaporanAnggaran::where('periode_id', $periode->id)
                ->whereIn('status_laporan', ['draft', 'revisi'])
                ->with('balaiPenyuluh')
                ->get();

            foreach ($laporanList as $laporan) {
                $isPassed = $now->isAfter($deadline);
                $level = $isPassed ? 'Tinggi' : 'Sedang';
                $condition = $isPassed
                    ? "Laporan telah melewati batas waktu tenggat ({$deadline->format('d/m/Y H:i')})."
                    : "Laporan belum dikirim mendekati batas waktu ({$daysRemaining} hari tersisa).";

                $alert = EwsAlert::updateOrCreate(
                    [
                        'laporan_id' => $laporan->id,
                        'kode_ews' => EwsCode::EWS_1,
                    ],
                    [
                        'level_ews' => $level,
                        'kondisi_pemicu' => $condition,
                        'penanggung_jawab' => $laporan->balaiPenyuluh->kepala_balai ?? 'Operator Balai',
                        'batas_waktu_koreksi' => $deadline,
                        'status_ews' => EwsStatus::Open,
                    ]
                );

                $alertsGenerated[] = $alert;
            }
        }

        return $alertsGenerated;
    }

    /**
     * Evaluasi EWS 2 (Kelengkapan & Validitas Dokumen) pada saat verifikasi berkas.
     */
    public function evaluateEws2(LaporanAnggaran $laporan, ?string $penanggungJawab = null): ?EwsAlert
    {
        $laporan->loadMissing('dokumenLaporan', 'skoringRisiko');

        $hasInvalidOrMissing = $laporan->dokumenLaporan->contains(function ($doc) {
            return in_array($doc->status_validasi, ['kurang', 'tidak_valid'], true);
        });

        $hasHighDeviation = false;
        if ($laporan->skoringRisiko) {
            $hasHighDeviation = ($laporan->skoringRisiko->skor_p2 >= 2 || $laporan->skoringRisiko->skor_p3 >= 2 || $laporan->skoringRisiko->skor_p4 >= 2);
        }

        if ($hasInvalidOrMissing || $hasHighDeviation) {
            $level = ($laporan->skoringRisiko && $laporan->skoringRisiko->total_skor >= 10) ? 'Tinggi' : 'Sedang';

            return EwsAlert::updateOrCreate(
                [
                    'laporan_id' => $laporan->id,
                    'kode_ews' => EwsCode::EWS_2,
                ],
                [
                    'level_ews' => $level,
                    'kondisi_pemicu' => 'Terdapat kekurangan/ketidakabsahan berkas pendukung atau deviasi anggaran signifikan.',
                    'penanggung_jawab' => $penanggungJawab ?? $laporan->balaiPenyuluh->kepala_balai ?? 'Operator Balai',
                    'batas_waktu_koreksi' => Carbon::now()->addDays(3),
                    'status_ews' => EwsStatus::Open,
                ]
            );
        }

        return null;
    }

    /**
     * Pindai tiket perbaikan yang melewati batas waktu (EWS 3 - Eskalasi ke Admin Dinas).
     */
    public function scanOverdueCorrections(): array
    {
        $escalatedList = [];
        $now = Carbon::now();

        $alerts = EwsAlert::where('status_ews', EwsStatus::Open)
            ->whereNotNull('batas_waktu_koreksi')
            ->where('batas_waktu_koreksi', '<', $now)
            ->with('laporanAnggaran.balaiPenyuluh')
            ->get();

        foreach ($alerts as $alert) {
            $alert->update([
                'status_ews' => EwsStatus::Escalated,
                'level_ews' => 'Tinggi',
                'jumlah_pengingat' => $alert->jumlah_pengingat + 1,
            ]);

            $escalatedList[] = $alert;
        }

        return $escalatedList;
    }

    /**
     * Tutup alert EWS setelah verifikasi tuntas.
     */
    public function resolveAlert(EwsAlert $alert): void
    {
        $alert->update([
            'status_ews' => EwsStatus::Resolved,
        ]);
    }
}
