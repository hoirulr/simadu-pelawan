<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EwsCode;
use App\Enums\EwsStatus;
use App\Enums\ReportStatus;
use App\Enums\RiskCategory;
use App\Models\EwsAlert;
use App\Models\LaporanAnggaran;
use App\Models\SkoringRisiko;
use App\Models\TindakLanjutMonitoring;
use App\Models\User;
use App\Services\RiskScoringService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SaveRiskAssessmentAction
{
    public function __construct(
        protected RiskScoringService $scoringService
    ) {}

    /**
     * Eksekusi penilaian skoring risiko, penerbitan EWS, pembuatan tiket perbaikan, dan transisi status laporan.
     *
     * @return array{
     *     skoring: SkoringRisiko,
     *     laporan: LaporanAnggaran,
     *     alert: ?EwsAlert,
     *     ticket: ?TindakLanjutMonitoring
     * }
     *
     * @throws DomainException
     */
    public function execute(
        LaporanAnggaran $laporan,
        int $p1,
        int $p2,
        int $p3,
        int $p4,
        int $p5,
        ?string $catatanPenilai,
        ?string $selectedEwsCode,
        ?string $penanggungJawab,
        ?string $instruksiPerbaikan,
        int $hariBatasKoreksi,
        User $admin
    ): array {
        Gate::forUser($admin)->authorize('score', $laporan);

        if (! $admin->isAdmin()) {
            throw new DomainException('Penilaian risiko dan penetapan status hanya dapat dilakukan oleh Admin Dinas DP2KBP3A.');
        }

        return DB::transaction(function () use (
            $laporan,
            $p1,
            $p2,
            $p3,
            $p4,
            $p5,
            $catatanPenilai,
            $selectedEwsCode,
            $penanggungJawab,
            $instruksiPerbaikan,
            $hariBatasKoreksi,
            $admin
        ) {
            $scoring = $this->scoringService->saveScore(
                $laporan,
                $p1,
                $p2,
                $p3,
                $p4,
                $p5,
                $admin->id,
                $catatanPenilai
            );

            $alert = null;
            $ticket = null;

            $hasInstruksi = ! empty(trim((string) $instruksiPerbaikan));

            // Jika total skor >= 5 (Risiko Sedang / Tinggi) atau terdapat instruksi perbaikan
            if ($scoring->total_skor >= 5 || $hasInstruksi) {
                $ewsCode = match ($selectedEwsCode) {
                    'EWS_1' => EwsCode::EWS_1,
                    'EWS_3' => EwsCode::EWS_3,
                    default => EwsCode::EWS_2,
                };

                $statusEws = $scoring->kategori_risiko === RiskCategory::Tinggi
                    ? EwsStatus::Escalated
                    : EwsStatus::Open;

                $alert = EwsAlert::updateOrCreate(
                    [
                        'laporan_id' => $laporan->id,
                        'kode_ews' => $ewsCode,
                    ],
                    [
                        'level_ews' => $scoring->kategori_risiko->value,
                        'kondisi_pemicu' => "Hasil skoring risiko: {$scoring->kategori_risiko->value} (Skor {$scoring->total_skor}/15).",
                        'penanggung_jawab' => $penanggungJawab ?: ($laporan->balaiPenyuluh?->kepala_balai ?? 'Kepala Balai'),
                        'batas_waktu_koreksi' => Carbon::now()->addDays(max(1, $hariBatasKoreksi)),
                        'status_ews' => $statusEws,
                    ]
                );

                if ($hasInstruksi) {
                    $ticket = TindakLanjutMonitoring::create([
                        'laporan_id' => $laporan->id,
                        'ews_id' => $alert->id,
                        'instruksi_perbaikan' => trim((string) $instruksiPerbaikan),
                        'waktu_mulai_temuan' => Carbon::now(),
                        'status' => 'Pending',
                        'verified_by' => $admin->id,
                    ]);
                }

                // Transisi status: Laporan memerlukan revisi / perbaikan
                $laporan->update(['status_laporan' => ReportStatus::Revisi]);
            } else {
                // Total skor <= 4 (Risiko Rendah) dan tanpa instruksi perbaikan -> Terverifikasi Tuntas
                $laporan->update(['status_laporan' => ReportStatus::Verified]);
            }

            return [
                'skoring' => $scoring,
                'laporan' => $laporan->fresh(['balaiPenyuluh', 'periodeAnggaran']),
                'alert' => $alert,
                'ticket' => $ticket,
            ];
        });
    }
}
