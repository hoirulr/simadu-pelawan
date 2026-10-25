<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Admin\Workspace;

use App\Enums\EwsCode;
use App\Enums\EwsStatus;
use App\Enums\ReportStatus;
use App\Enums\RiskCategory;
use App\Models\BalaiPenyuluh;
use App\Models\DokumenLaporan;
use App\Models\EwsAlert;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\TindakLanjutMonitoring;
use App\Services\RiskScoringService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Meja Kerja Pemeriksaan & Skoring')] class extends Component
{
    public int $selectedBalaiId = 1;

    public int $selectedPeriodeId = 1;

    // 5 Parameter Skor Risiko
    public int $skorP1 = 0;

    public int $skorP2 = 0;

    public int $skorP3 = 0;

    public int $skorP4 = 0;

    public int $skorP5 = 0;

    public string $catatanPenilai = '';

    // EWS Form Fields
    public bool $pemicuEwsActive = false;

    public string $selectedEwsCode = 'EWS_2';

    public string $instruksiPerbaikan = '';

    public string $penanggungJawab = '';

    public int $hariBatasKoreksi = 3;

    public ?string $notificationMessage = null;

    public function mount(): void
    {
        if (request()->has('balai_id')) {
            $this->selectedBalaiId = (int) request('balai_id');
        }
        if (request()->has('periode_id')) {
            $this->selectedPeriodeId = (int) request('periode_id');
        }

        $this->loadBalaiAssessment();
    }

    public function updatedSelectedBalaiId(): void
    {
        $this->loadBalaiAssessment();
    }

    public function updatedSelectedPeriodeId(): void
    {
        $this->loadBalaiAssessment();
    }

    public function loadBalaiAssessment(): void
    {
        $laporan = LaporanAnggaran::where('balai_id', $this->selectedBalaiId)
            ->where('periode_id', $this->selectedPeriodeId)
            ->with(['skoringRisiko', 'dokumenLaporan', 'balaiPenyuluh', 'ewsAlerts'])
            ->first();

        if ($laporan && $laporan->skoringRisiko) {
            $this->skorP1 = (int) $laporan->skoringRisiko->skor_p1;
            $this->skorP2 = (int) $laporan->skoringRisiko->skor_p2;
            $this->skorP3 = (int) $laporan->skoringRisiko->skor_p3;
            $this->skorP4 = (int) $laporan->skoringRisiko->skor_p4;
            $this->skorP5 = (int) $laporan->skoringRisiko->skor_p5;
            $this->catatanPenilai = $laporan->skoringRisiko->catatan_penilai ?? '';
        } elseif ($laporan) {
            // Auto pra-kalkulasi P1 & P2 via service
            $service = app(RiskScoringService::class);
            $this->skorP1 = $service->calculateP1($laporan);
            $this->skorP2 = $service->calculateP2($laporan);
            $this->skorP3 = 0;
            $this->skorP4 = 0;
            $this->skorP5 = 0;
            $this->catatanPenilai = '';
        }

        $this->penanggungJawab = $laporan?->balaiPenyuluh?->kepala_balai ?? 'Kepala Balai';
    }

    public function updateDokumenStatus(int $dokumenId, string $status): void
    {
        $dok = DokumenLaporan::find($dokumenId);
        if ($dok && in_array($status, ['lengkap', 'kurang', 'tidak_valid'], true)) {
            $dok->update(['status_validasi' => $status]);

            // Re-calculate P2
            $laporan = $dok->laporanAnggaran;
            if ($laporan) {
                $this->skorP2 = app(RiskScoringService::class)->calculateP2($laporan);
            }
        }
    }

    public function saveAssessment(): void
    {
        $laporan = LaporanAnggaran::where('balai_id', $this->selectedBalaiId)
            ->where('periode_id', $this->selectedPeriodeId)
            ->first();

        if (! $laporan) {
            return;
        }

        $adminId = Auth::id() ?? 1;
        $scoringService = app(RiskScoringService::class);

        $scoring = $scoringService->saveScore(
            $laporan,
            $this->skorP1,
            $this->skorP2,
            $this->skorP3,
            $this->skorP4,
            $this->skorP5,
            $adminId,
            $this->catatanPenilai
        );

        // Jika total skor >= 5 atau ada instruksi perbaikan, picu tiket EWS
        if ($scoring->total_skor >= 5 || ! empty($this->instruksiPerbaikan)) {
            $ewsCode = match ($this->selectedEwsCode) {
                'EWS_1' => EwsCode::EWS_1,
                'EWS_3' => EwsCode::EWS_3,
                default => EwsCode::EWS_2,
            };

            $alert = EwsAlert::updateOrCreate(
                [
                    'laporan_id' => $laporan->id,
                    'kode_ews' => $ewsCode,
                ],
                [
                    'level_ews' => $scoring->kategori_risiko->value,
                    'kondisi_pemicu' => "Hasil skoring risiko: {$scoring->kategori_risiko->value} (Skor {$scoring->total_skor}/15).",
                    'penanggung_jawab' => $this->penanggungJawab,
                    'batas_waktu_koreksi' => Carbon::now()->addDays($this->hariBatasKoreksi),
                    'status_ews' => $scoring->kategori_risiko === RiskCategory::Tinggi ? EwsStatus::Escalated : EwsStatus::Open,
                ]
            );

            if (! empty($this->instruksiPerbaikan)) {
                TindakLanjutMonitoring::create([
                    'laporan_id' => $laporan->id,
                    'ews_id' => $alert->id,
                    'instruksi_perbaikan' => $this->instruksiPerbaikan,
                    'waktu_mulai_temuan' => Carbon::now(),
                    'status' => 'Pending',
                    'verified_by' => $adminId,
                ]);
            }

            $laporan->update(['status_laporan' => ReportStatus::Revisi]);
            $this->notificationMessage = "Penilaian tersimpan. Peringatan Dini ({$ewsCode->value}) dan tiket instruksi perbaikan berhasil diterbitkan.";
        } else {
            $laporan->update(['status_laporan' => ReportStatus::Verified]);
            $this->notificationMessage = 'Penilaian tersimpan. Laporan dinyatakan Terverifikasi Tuntas (Risiko Rendah).';
        }
    }

    public function with(): array
    {
        $balaiList = BalaiPenyuluh::orderBy('id', 'asc')->get();
        $periodes = PeriodeAnggaran::orderBy('tahun', 'desc')->get();

        $currentBalai = BalaiPenyuluh::find($this->selectedBalaiId);
        $currentPeriode = PeriodeAnggaran::find($this->selectedPeriodeId);

        $laporan = LaporanAnggaran::where('balai_id', $this->selectedBalaiId)
            ->where('periode_id', $this->selectedPeriodeId)
            ->with(['dokumenLaporan', 'skoringRisiko', 'ewsAlerts', 'tindakLanjut'])
            ->first();

        $totalSkorLive = $this->skorP1 + $this->skorP2 + $this->skorP3 + $this->skorP4 + $this->skorP5;
        $kategoriLive = app(RiskScoringService::class)->determineCategory($totalSkorLive);
        $rekomendasiLive = app(RiskScoringService::class)->getSystemRecommendation($kategoriLive, $totalSkorLive);

        return [
            'balaiList' => $balaiList,
            'periodes' => $periodes,
            'currentBalai' => $currentBalai,
            'currentPeriode' => $currentPeriode,
            'laporan' => $laporan,
            'totalSkorLive' => $totalSkorLive,
            'kategoriLive' => $kategoriLive,
            'rekomendasiLive' => $rekomendasiLive,
        ];
    }
};
