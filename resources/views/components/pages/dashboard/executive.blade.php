<?php

declare(strict_types=1);

use App\Enums\RiskCategory;
use App\Models\BalaiPenyuluh;
use App\Models\EwsAlert;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Services\EconomicValueCalculatorService;
use App\Services\RiskScoringService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Dashboard Eksekutif & Peta Risiko')] class extends Component
{
    public int $selectedPeriodeId = 1;

    public function mount(): void
    {
        $active = PeriodeAnggaran::where('is_active', true)->first();
        if ($active) {
            $this->selectedPeriodeId = $active->id;
        }
    }

    public function with(): array
    {
        $periodes = PeriodeAnggaran::orderBy('tahun', 'desc')->orderBy('tahap', 'asc')->get();
        $selectedPeriode = PeriodeAnggaran::find($this->selectedPeriodeId) ?? $periodes->first();

        // 8 Balai Penyuluh KB beserta laporan periode terpilih
        $balaiList = BalaiPenyuluh::with([
            'kecamatanModel',
            'laporanAnggaran' => function ($q) {
                $q->where('periode_id', $this->selectedPeriodeId)
                    ->with(['skoringRisiko', 'ewsAlerts']);
            },
        ])->orderBy('id', 'asc')->get();

        // Agregasi Metrik Top
        $totalPagu = 0.0;
        $totalRealisasi = 0.0;
        $totalLaporanMasuk = 0;
        $totalTepatWaktu = 0;
        $totalFirstTimeRight = 0;

        $riskSummary = [
            'Rendah' => 0,
            'Sedang' => 0,
            'Tinggi' => 0,
        ];

        $balaiChartData = [];

        foreach ($balaiList as $balai) {
            $lap = $balai->laporanAnggaran->first();
            $pagu = $lap ? (float) $lap->rencana_anggaran : 0.0;
            $realisasi = $lap ? (float) $lap->realisasi_anggaran : 0.0;

            $totalPagu += $pagu;
            $totalRealisasi += $realisasi;

            if ($lap && $lap->status_laporan !== \App\Enums\ReportStatus::Draft) {
                $totalLaporanMasuk++;
                if ($lap->tgl_submit_pertama && $selectedPeriode) {
                    if (\Carbon\Carbon::parse($lap->tgl_submit_pertama)->lessThanOrEqualTo($selectedPeriode->tgl_tenggat_lapor)) {
                        $totalTepatWaktu++;
                    }
                }
                if ($lap->lengkap_pengiriman_pertama) {
                    $totalFirstTimeRight++;
                }
            }

            if ($lap && $lap->skoringRisiko) {
                $cat = is_object($lap->skoringRisiko->kategori_risiko)
                    ? $lap->skoringRisiko->kategori_risiko->value
                    : $lap->skoringRisiko->kategori_risiko;
                if (isset($riskSummary[$cat])) {
                    $riskSummary[$cat]++;
                }
            }

            $balaiChartData[] = [
                'nama' => str_replace('Balai Penyuluh KB ', '', $balai->nama_balai),
                'pagu' => round($pagu / 1000000, 1),
                'realisasi' => round($realisasi / 1000000, 1),
                'serapan' => $pagu > 0 ? round(($realisasi / $pagu) * 100, 1) : 0,
            ];
        }

        $persentaseSerapanTotal = $totalPagu > 0 ? round(($totalRealisasi / $totalPagu) * 100, 1) : 0.0;
        $targetSerapan = $selectedPeriode ? (float) $selectedPeriode->target_realisasi_persen : 50.0;

        // Alerts aktif
        $activeAlerts = EwsAlert::whereHas('laporanAnggaran', function ($q) {
            $q->where('periode_id', $this->selectedPeriodeId);
        })->whereIn('status_ews', [\App\Enums\EwsStatus::Open, \App\Enums\EwsStatus::Escalated])->get();

        $escalatedCount = $activeAlerts->where('status_ews', \App\Enums\EwsStatus::Escalated)->count();
        $warningCount = $activeAlerts->where('status_ews', \App\Enums\EwsStatus::Open)->count();

        // 5 Indikator Nilai Ekonomis
        $economicStats = app(EconomicValueCalculatorService::class)->calculate($this->selectedPeriodeId);

        return [
            'periodes' => $periodes,
            'selectedPeriode' => $selectedPeriode,
            'balaiList' => $balaiList,
            'totalPagu' => $totalPagu,
            'totalRealisasi' => $totalRealisasi,
            'persentaseSerapanTotal' => $persentaseSerapanTotal,
            'targetSerapan' => $targetSerapan,
            'totalLaporanMasuk' => $totalLaporanMasuk,
            'totalTepatWaktu' => $totalTepatWaktu,
            'totalFirstTimeRight' => $totalFirstTimeRight,
            'riskSummary' => $riskSummary,
            'escalatedCount' => $escalatedCount,
            'warningCount' => $warningCount,
            'balaiChartData' => $balaiChartData,
            'economicStats' => $economicStats,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header Section with Period Filter -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    DP2KBP3A Kab. Bangka
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">8 Balai Penyuluh KB</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-1">
                Dashboard Eksekutif & Peta Risiko Anggaran
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Monitoring real-time serapan Tahap I ke Tahap II, Early Warning System (EWS), dan evaluasi Nilai Ekonomis.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-theme-xs">
                <label for="periodeFilter" class="text-xs font-medium text-gray-500 dark:text-gray-400 pl-2">Periode:</label>
                <select id="periodeFilter" wire:model.live="selectedPeriodeId"
                    class="text-sm font-semibold text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 py-1 pr-8 cursor-pointer">
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" class="dark:bg-gray-800">
                            {{ $p->tahun }} - {{ $p->tahap }} (Target {{ $p->target_realisasi_persen }}%)
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Active Escalation Alert Banner (If Any) -->
    @if($escalatedCount > 0)
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/30 dark:bg-rose-500/10 flex items-start gap-3">
            <div class="size-8 rounded-lg bg-rose-500 text-white flex items-center justify-center shrink-0">
                <svg class="size-5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-bold text-rose-800 dark:text-rose-400">
                    PERINGATAN DINI KRITIS: {{ $escalatedCount }} Balai Penyuluh Berstatus Escalated / Risiko Tinggi!
                </h4>
                <p class="text-xs text-rose-700 dark:text-rose-300 mt-0.5">
                    Terdapat keterlambatan tindak lanjut yang melewati batas toleransi SOP. Segera buka Meja Kerja Pemeriksaan untuk instruksi percepatan.
                </p>
            </div>
            <a href="/admin/workspace" class="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-xs font-semibold hover:bg-rose-700 shadow-sm transition">
                Tindak Lanjut &rarr;
            </a>
        </div>
    @endif

    <!-- 4 Top Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 md:gap-6">
        <!-- Card 1: Total Serapan Tahap I vs Target -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Realisasi vs Target</span>
                <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $persentaseSerapanTotal >= $targetSerapan ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400' }}">
                    Target {{ $targetSerapan }}%
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white tabular-nums">
                    {{ $persentaseSerapanTotal }}%
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    (Rp {{ number_format($totalRealisasi / 1000000, 1, ',', '.') }} Jt)
                </span>
            </div>
            <div class="mt-3 w-full bg-gray-100 rounded-full h-2 dark:bg-gray-800 overflow-hidden">
                <div class="h-2 rounded-full transition-all duration-500 {{ $persentaseSerapanTotal >= $targetSerapan ? 'bg-emerald-500' : 'bg-brand-500' }}"
                     style="width: {{ min(100, $persentaseSerapanTotal) }}%"></div>
            </div>
            <p class="text-[11px] text-gray-400 mt-2">Pagu Total: Rp {{ number_format($totalPagu, 0, ',', '.') }}</p>
        </div>

        <!-- Card 2: Ketepatan Waktu Balai -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Ketepatan Waktu Lapor</span>
                <span class="p-1 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white tabular-nums">
                    {{ $totalTepatWaktu }} <span class="text-lg font-medium text-gray-400">/ 8</span>
                </span>
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                    ({{ round(($totalTepatWaktu / 8) * 100, 1) }}%)
                </span>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-3">
                Balai yang menyampaikan laporan sebelum/tepat tenggat waktu SOP.
            </p>
        </div>

        <!-- Card 3: First-Time Right -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">First-Time Right (Lengkap)</span>
                <span class="p-1 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-gray-900 dark:text-white tabular-nums">
                    {{ $totalFirstTimeRight }} <span class="text-lg font-medium text-gray-400">/ 8</span>
                </span>
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                    ({{ round(($totalFirstTimeRight / 8) * 100, 1) }}%)
                </span>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-3">
                Laporan yang langsung tuntas tanpa pengembalian dokumen revisi.
            </p>
        </div>

        <!-- Card 4: Status Peta Risiko & EWS -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Sebaran Risiko 8 Balai</span>
                <span class="text-[11px] font-mono text-gray-400">0-15 Skor</span>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-1 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400">
                    {{ $riskSummary['Rendah'] }} Rendah
                </span>
                <span class="px-2 py-1 rounded-md text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/15 dark:text-amber-400">
                    {{ $riskSummary['Sedang'] }} Sedang
                </span>
                <span class="px-2 py-1 rounded-md text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-400">
                    {{ $riskSummary['Tinggi'] }} Tinggi
                </span>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-3">
                EWS Aktif: <strong class="text-rose-600">{{ $escalatedCount }} Kritis</strong>, <strong class="text-amber-600">{{ $warningCount }} Peringatan</strong>.
            </p>
        </div>
    </div>

    <!-- Peta Risiko 8 Balai Penyuluh KB (Table) -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                    Peta Risiko & Realisasi 8 Balai Penyuluh KB
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Matriks penilaian 5 parameter risiko ($P_1\text{--}P_5$), capaian serapan, dan indikator EWS per Balai.
                </p>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1 text-gray-500"><span class="size-2 rounded-full bg-emerald-500"></span> Rendah (0-4)</span>
                <span class="inline-flex items-center gap-1 text-gray-500"><span class="size-2 rounded-full bg-amber-500"></span> Sedang (5-9)</span>
                <span class="inline-flex items-center gap-1 text-gray-500"><span class="size-2 rounded-full bg-rose-500"></span> Tinggi (10-15)</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500 dark:bg-gray-800/50 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                    <tr>
                        <th class="py-3 px-4 text-center w-12">No</th>
                        <th class="py-3 px-4">Balai Penyuluh KB</th>
                        <th class="py-3 px-4 text-right">Rencana / Pagu</th>
                        <th class="py-3 px-4 text-right">Realisasi</th>
                        <th class="py-3 px-4 text-right">Serapan</th>
                        <th class="py-3 px-3 text-center">P1</th>
                        <th class="py-3 px-3 text-center">P2</th>
                        <th class="py-3 px-3 text-center">P3</th>
                        <th class="py-3 px-3 text-center">P4</th>
                        <th class="py-3 px-3 text-center">P5</th>
                        <th class="py-3 px-3 text-center font-bold">Total</th>
                        <th class="py-3 px-4 text-center">Kategori Risiko</th>
                        <th class="py-3 px-4 text-center">Indikator EWS</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-normal">
                    @foreach($balaiList as $index => $balai)
                        @php
                            $lap = $balai->laporanAnggaran->first();
                            $skor = $lap?->skoringRisiko;
                            $pagu = $lap ? (float) $lap->rencana_anggaran : 0.0;
                            $realisasi = $lap ? (float) $lap->realisasi_anggaran : 0.0;
                            $serapan = $lap ? (float) $lap->persentase_serapan : 0.0;
                        @endphp
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition">
                            <td class="py-3.5 px-4 text-center font-mono text-xs text-gray-400">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    {{ $balai->nama_balai }}
                                </div>
                                <div class="text-xs text-gray-400 flex items-center gap-1.5 mt-0.5">
                                    <span class="font-mono text-brand-600 dark:text-brand-400">{{ $balai->kode_balai }}</span>
                                    <span>•</span>
                                    <span>Kec. {{ $balai->kecamatan }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-xs tabular-nums text-gray-700 dark:text-gray-300">
                                Rp {{ number_format($pagu, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-xs tabular-nums font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($realisasi, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-xs tabular-nums">
                                <span class="inline-block font-bold {{ $serapan >= $targetSerapan ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ number_format($serapan, 1, ',', '.') }}%
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-center font-mono text-xs {{ ($skor?->skor_p1 ?? 0) >= 2 ? 'font-bold text-rose-600' : '' }}">
                                {{ $skor?->skor_p1 ?? '-' }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-mono text-xs {{ ($skor?->skor_p2 ?? 0) >= 2 ? 'font-bold text-rose-600' : '' }}">
                                {{ $skor?->skor_p2 ?? '-' }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-mono text-xs {{ ($skor?->skor_p3 ?? 0) >= 2 ? 'font-bold text-rose-600' : '' }}">
                                {{ $skor?->skor_p3 ?? '-' }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-mono text-xs {{ ($skor?->skor_p4 ?? 0) >= 2 ? 'font-bold text-rose-600' : '' }}">
                                {{ $skor?->skor_p4 ?? '-' }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-mono text-xs {{ ($skor?->skor_p5 ?? 0) >= 2 ? 'font-bold text-rose-600' : '' }}">
                                {{ $skor?->skor_p5 ?? '-' }}
                            </td>
                            <td class="py-3.5 px-3 text-center font-mono text-sm font-extrabold text-gray-900 dark:text-white">
                                {{ $skor ? $skor->total_skor : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($skor)
                                    <x-risk-badge :category="$skor->kategori_risiko" :score="$skor->total_skor" />
                                @else
                                    <span class="text-xs text-gray-400 italic">Belum Dinilai</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <x-ews-pill-indicator :ewsAlerts="$lap?->ewsAlerts ?? collect()" />
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <a href="/admin/workspace?balai_id={{ $balai->id }}&periode_id={{ $selectedPeriodeId }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-brand-500/20 dark:hover:text-brand-400 transition">
                                    <span>Periksa</span>
                                    <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Grafik Progress Serapan & 5 Indikator Nilai Ekonomis -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Grafik Serapan 8 Balai (Col-span 7) -->
        <div class="lg:col-span-7 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Perbandingan Pagu vs Realisasi 8 Balai (Juta Rp)
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Capaian serapan terhadap target kelancaran pencairan Tahap II ({{ $targetSerapan }}%)
                    </p>
                </div>
            </div>

            <!-- Visual Bar Comparison -->
            <div class="space-y-3.5 pt-2">
                @foreach($balaiChartData as $c)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $c['nama'] }}</span>
                            <span class="font-mono text-gray-500 dark:text-gray-400">
                                <strong>Rp {{ $c['realisasi'] }}Jt</strong> / Rp {{ $c['pagu'] }}Jt ({{ $c['serapan'] }}%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3 dark:bg-gray-800 overflow-hidden relative">
                            <!-- Target Indicator Line at 50% -->
                            <div class="absolute top-0 bottom-0 w-0.5 bg-gray-400 z-10" style="left: {{ $targetSerapan }}%" title="Target Batas Minimal"></div>
                            <div class="h-3 rounded-full transition-all duration-500 {{ $c['serapan'] >= $targetSerapan ? 'bg-gradient-to-r from-emerald-500 to-teal-400' : 'bg-gradient-to-r from-amber-500 to-orange-400' }}"
                                 style="width: {{ min(100, $c['serapan']) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 5 Indikator Nilai Ekonomis (Col-span 5) -->
        <div class="lg:col-span-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        5 Indikator Nilai Ekonomis
                    </h3>
                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                        Evaluasi Kinerja PKA
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Kalkulator agregasi otomatis efisiensi waktu, kualitas data pertama kali kirim, dan pengawasan anggaran.
                </p>

                <div class="space-y-3">
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold text-gray-700 dark:text-gray-300">1. Waktu Identifikasi Masalah</div>
                            <div class="text-[11px] text-gray-400">Rata-rata sejak submit ke skoring</div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold font-mono text-gray-900 dark:text-white">{{ $economicStats['avgIdentificationHours'] }}</span>
                            <span class="text-xs text-gray-500">Jam</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold text-gray-700 dark:text-gray-300">2. Laporan Tepat Waktu</div>
                            <div class="text-[11px] text-gray-400">Terhadap batas tenggat SOP</div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400">{{ $economicStats['onTimePercentage'] }}%</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold text-gray-700 dark:text-gray-300">3. First-Time Right</div>
                            <div class="text-[11px] text-gray-400">Dokumen langsung lengkap tanpa revisi</div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold font-mono text-brand-600 dark:text-brand-400">{{ $economicStats['firstTimeRightPercentage'] }}%</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold text-gray-700 dark:text-gray-300">4. Waktu Penyelesaian Temuan</div>
                            <div class="text-[11px] text-gray-400">Rata-rata tuntas verifikasi</div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold font-mono text-gray-900 dark:text-white">{{ $economicStats['avgResolutionHours'] }}</span>
                            <span class="text-xs text-gray-500">Jam</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <div>
                            <div class="text-xs font-semibold text-gray-700 dark:text-gray-300">5. Log Koordinasi & Pengingat</div>
                            <div class="text-[11px] text-gray-400">Total notifikasi EWS & tiket aktif</div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold font-mono text-gray-900 dark:text-white">{{ $economicStats['totalRecordedCoordinations'] }}</span>
                            <span class="text-xs text-gray-500">Aktivitas</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 dark:border-gray-800 mt-4">
                <a href="/nilai-ekonomis" class="w-full py-2.5 px-4 text-center rounded-xl bg-brand-500 text-white text-xs font-semibold hover:bg-brand-600 transition block">
                    Lihat Rincian Log Evaluasi Nilai Ekonomis &rarr;
                </a>
            </div>
        </div>
    </div>
</div>