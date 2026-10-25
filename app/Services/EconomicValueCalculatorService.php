<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EwsAlert;
use App\Models\LaporanAnggaran;
use App\Models\TindakLanjutMonitoring;
use Illuminate\Support\Carbon;

class EconomicValueCalculatorService
{
    /**
     * Hitung 5 Indikator Nilai Ekonomis secara otomatis berdasarkan filter periode anggaran.
     *
     * @return array{
     *     avgIdentificationHours: float,
     *     onTimePercentage: float,
     *     firstTimeRightPercentage: float,
     *     avgResolutionHours: float,
     *     totalRecordedCoordinations: int,
     *     totalLaporan: int,
     *     totalLengkapPertama: int,
     *     totalTepatWaktu: int
     * }
     */
    public function calculate(?int $periodeId = null): array
    {
        $query = LaporanAnggaran::query();
        if ($periodeId) {
            $query->where('periode_id', $periodeId);
        }

        $laporanList = $query->with(['skoringRisiko', 'periodeAnggaran'])->get();
        $totalLaporan = $laporanList->count();

        // 1. Rata-rata waktu identifikasi laporan bermasalah (jam dari submit ke penilaian)
        $totalIdentHours = 0;
        $scoredCount = 0;
        foreach ($laporanList as $lap) {
            if ($lap->tgl_submit_pertama && $lap->skoringRisiko) {
                $submit = Carbon::parse($lap->tgl_submit_pertama);
                $scored = Carbon::parse($lap->skoringRisiko->created_at);
                $diffHours = max(0, $submit->diffInMinutes($scored) / 60);
                $totalIdentHours += $diffHours;
                $scoredCount++;
            }
        }
        $avgIdentificationHours = $scoredCount > 0 ? round($totalIdentHours / $scoredCount, 1) : 0.0;

        // 2. Persentase laporan tepat waktu
        $tepatWaktuCount = 0;
        foreach ($laporanList as $lap) {
            if ($lap->tgl_submit_pertama && $lap->periodeAnggaran) {
                $submit = Carbon::parse($lap->tgl_submit_pertama);
                $deadline = Carbon::parse($lap->periodeAnggaran->tgl_tenggat_lapor);
                if ($submit->lessThanOrEqualTo($deadline)) {
                    $tepatWaktuCount++;
                }
            }
        }
        $onTimePercentage = $totalLaporan > 0 ? round(($tepatWaktuCount / $totalLaporan) * 100, 1) : 0.0;

        // 3. Persentase First-Time Right (lengkap pengiriman pertama)
        $firstTimeRightCount = $laporanList->where('lengkap_pengiriman_pertama', true)->count();
        $firstTimeRightPercentage = $totalLaporan > 0 ? round(($firstTimeRightCount / $totalLaporan) * 100, 1) : 0.0;

        // 4. Rata-rata waktu penyelesaian kekurangan/temuan (jam dari mulai temuan ke verified)
        $tindakLanjutQuery = TindakLanjutMonitoring::where('status', 'Verified')
            ->whereNotNull('waktu_selesai_verifikasi');

        if ($periodeId) {
            $tindakLanjutQuery->whereHas('laporanAnggaran', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            });
        }

        $resolvedTickets = $tindakLanjutQuery->get();
        $totalResHours = 0;
        foreach ($resolvedTickets as $ticket) {
            $start = Carbon::parse($ticket->waktu_mulai_temuan);
            $end = Carbon::parse($ticket->waktu_selesai_verifikasi);
            $totalResHours += max(0, $start->diffInMinutes($end) / 60);
        }
        $avgResolutionHours = $resolvedTickets->count() > 0 ? round($totalResHours / $resolvedTickets->count(), 1) : 0.0;

        // 5. Total koordinasi dan pengingat tercatat
        $alertsQuery = EwsAlert::query();
        if ($periodeId) {
            $alertsQuery->whereHas('laporanAnggaran', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            });
        }
        $totalPengingat = (int) $alertsQuery->sum('jumlah_pengingat');

        $ticketsQuery = TindakLanjutMonitoring::query();
        if ($periodeId) {
            $ticketsQuery->whereHas('laporanAnggaran', function ($q) use ($periodeId) {
                $q->where('periode_id', $periodeId);
            });
        }
        $totalTiket = $ticketsQuery->count();

        $totalRecordedCoordinations = $totalPengingat + $totalTiket;

        return [
            'avgIdentificationHours' => $avgIdentificationHours,
            'onTimePercentage' => $onTimePercentage,
            'firstTimeRightPercentage' => $firstTimeRightPercentage,
            'avgResolutionHours' => $avgResolutionHours,
            'totalRecordedCoordinations' => $totalRecordedCoordinations,
            'totalLaporan' => $totalLaporan,
            'totalLengkapPertama' => $firstTimeRightCount,
            'totalTepatWaktu' => $tepatWaktuCount,
        ];
    }
}
