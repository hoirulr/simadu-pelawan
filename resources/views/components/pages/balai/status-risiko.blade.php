<?php

declare(strict_types=1);

use App\Enums\EwsStatus;
use App\Enums\RiskCategory;
use App\Models\BalaiPenyuluh;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Status Risiko & Peringatan Dini (EWS) Balai')] class extends Component
{
    public int $balaiId = 1;
    public int $periodeId = 1;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user && $user->isOperatorBalai()) {
            $this->balaiId = (int) $user->balai_id;
        } elseif ($user && $user->balai_id) {
            $this->balaiId = (int) $user->balai_id;
        } elseif (request()->has('balai_id')) {
            $this->balaiId = (int) request('balai_id');
        }

        $activePeriode = PeriodeAnggaran::where('is_active', true)->first();
        if ($activePeriode) {
            $this->periodeId = $activePeriode->id;
        }
    }

    public function updatedBalaiId(): void
    {
        $user = Auth::user();
        if ($user && $user->isOperatorBalai()) {
            $this->balaiId = (int) $user->balai_id;
        }
    }

    public function with(): array
    {
        $user = Auth::user();
        if ($user && $user->isOperatorBalai()) {
            $this->balaiId = (int) $user->balai_id;
        }

        $balaiList = ($user && $user->isAdmin())
            ? BalaiPenyuluh::orderBy('id', 'asc')->get()
            : BalaiPenyuluh::where('id', $this->balaiId)->get();

        $periodes = PeriodeAnggaran::orderBy('tahun', 'desc')->get();
        $currentBalai = BalaiPenyuluh::find($this->balaiId);
        $currentPeriode = PeriodeAnggaran::find($this->periodeId);

        $laporan = LaporanAnggaran::where('balai_id', $this->balaiId)
            ->where('periode_id', $this->periodeId)
            ->with(['skoringRisiko.penilai', 'dokumenLaporan', 'ewsAlerts', 'tindakLanjutMonitoring'])
            ->first();

        if ($laporan) {
            \Illuminate\Support\Facades\Gate::authorize('view', $laporan);
        }

        $activeAlerts = $laporan
            ? $laporan->ewsAlerts->whereIn('status_ews', [EwsStatus::Open, EwsStatus::Escalated])
            : collect();

        $allAlerts = $laporan ? $laporan->ewsAlerts : collect();

        return [
            'balaiList' => $balaiList,
            'periodes' => $periodes,
            'currentBalai' => $currentBalai,
            'currentPeriode' => $currentPeriode,
            'laporan' => $laporan,
            'activeAlerts' => $activeAlerts,
            'allAlerts' => $allAlerts,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header & Selection Controls -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    Modul Transparansi & Pengawasan Balai
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">FR-02 & FR-03 DP2KBP3A</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-1">
                Status Risiko & Early Warning System (EWS)
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pemantauan transparan hasil skoring 5 parameter risiko dan peringatan dini anggaran untuk {{ $currentBalai?->nama_balai }}.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Balai Selector (Enabled for Admin only) -->
            @if(!Auth::user() || Auth::user()->isAdmin())
                <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-theme-xs">
                    <label for="balaiSelectorRisk" class="text-xs font-medium text-gray-500 dark:text-gray-400 pl-2">Balai:</label>
                    <select id="balaiSelectorRisk" wire:model.live="balaiId" class="text-sm font-semibold text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 py-1 pr-6 cursor-pointer">
                        @foreach($balaiList as $b)
                            <option value="{{ $b->id }}" class="dark:bg-gray-800">{{ $b->nama_balai }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Periode Selector -->
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-theme-xs">
                <label for="periodeSelectorRisk" class="text-xs font-medium text-gray-500 dark:text-gray-400 pl-2">Periode:</label>
                <select id="periodeSelectorRisk" wire:model.live="periodeId" class="text-sm font-semibold text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 py-1 pr-6 cursor-pointer">
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" class="dark:bg-gray-800">{{ $p->tahun }} - {{ $p->tahap }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Active EWS Alerts (if any) -->
    @if($activeAlerts->isNotEmpty())
        <div class="space-y-3">
            @foreach($activeAlerts as $alert)
                <div class="rounded-2xl border {{ $alert->status_ews === EwsStatus::Escalated ? 'border-rose-300 bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10' : 'border-amber-300 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10' }} p-5 shadow-theme-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="size-11 rounded-2xl {{ $alert->status_ews === EwsStatus::Escalated ? 'bg-rose-600 text-white shadow-rose-500/20' : 'bg-amber-500 text-white shadow-amber-500/20' }} flex items-center justify-center shrink-0 shadow-lg">
                            <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-bold font-mono px-2.5 py-0.5 rounded {{ $alert->status_ews === EwsStatus::Escalated ? 'bg-rose-600 text-white' : 'bg-amber-600 text-white' }}">
                                    {{ $alert->kode_ews->value }}
                                </span>
                                <span class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                    {{ $alert->kode_ews->title() }}
                                </span>
                                <span class="text-xs text-gray-400">•</span>
                                <span class="text-xs font-semibold {{ $alert->status_ews === EwsStatus::Escalated ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    Status: {{ $alert->status_ews->value }} (Pengingat ke-{{ $alert->jumlah_pengingat }})
                                </span>
                            </div>
                            <p class="text-sm font-semibold {{ $alert->status_ews === EwsStatus::Escalated ? 'text-rose-950 dark:text-rose-200' : 'text-amber-950 dark:text-amber-200' }} mt-1">
                                {{ $alert->kondisi_pemicu }}
                            </p>
                            @if($alert->batas_waktu_koreksi)
                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 flex items-center gap-2 font-mono">
                                    <span>Batas Waktu Koreksi: <strong class="font-bold text-gray-900 dark:text-white">{{ $alert->batas_waktu_koreksi->format('d M Y H:i') }} WIB</strong></span>
                                    <span>({{ $alert->batas_waktu_koreksi->diffForHumans() }})</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="/balai/tindak-lanjut?balai_id={{ $balaiId }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition">
                            Tindak Lanjut & Bukti &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Main Grid: Risk Card & Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Col-span 5: Overall Risk Card -->
        <div class="lg:col-span-5 space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 mb-5">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Kategori Risiko Balai
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Periode {{ $currentPeriode?->namaPeriode() }}
                        </p>
                    </div>

                    @if($laporan?->skoringRisiko)
                        @php
                            $cat = $laporan->skoringRisiko->kategori_risiko instanceof RiskCategory
                                ? $laporan->skoringRisiko->kategori_risiko->value
                                : (string) $laporan->skoringRisiko->kategori_risiko;
                        @endphp
                        <x-risk-badge :kategori="$cat" />
                    @else
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                            Belum Dinilai
                        </span>
                    @endif
                </div>

                @if($laporan?->skoringRisiko)
                    <div class="p-6 rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100/70 dark:from-gray-800 dark:to-gray-800/40 border border-gray-200 dark:border-gray-700 text-center">
                        <span class="text-xs uppercase tracking-wider font-bold text-gray-400">Total Skor Risiko (0 - 15)</span>
                        <div class="mt-2 flex items-baseline justify-center gap-1">
                            <span class="text-5xl font-black font-mono tracking-tight text-gray-900 dark:text-white">
                                {{ $laporan->skoringRisiko->total_skor }}
                            </span>
                            <span class="text-sm font-semibold text-gray-400">/ 15</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            Skor dihitung dari akumulasi 5 parameter ($P_1 + P_2 + P_3 + P_4 + P_5$)
                        </p>
                    </div>

                    <!-- Rekomendasi Resmi Sistem DP2KBP3A -->
                    <div class="mt-5 p-4 rounded-xl bg-brand-50/60 dark:bg-brand-950/20 border border-brand-100 dark:border-brand-900/40">
                        <div class="flex items-center gap-2 text-xs font-bold text-brand-700 dark:text-brand-300">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Rekomendasi Tindakan Sistem:</span>
                        </div>
                        <p class="text-xs text-gray-700 dark:text-gray-300 mt-1 leading-relaxed">
                            {{ $laporan->skoringRisiko->rekomendasi_sistem }}
                        </p>
                        @if($laporan->skoringRisiko->penilai)
                            <div class="mt-2 pt-2 border-t border-brand-100 dark:border-brand-900/40 text-[11px] text-gray-400 font-mono">
                                Divalidasi oleh: {{ $laporan->skoringRisiko->penilai->name }} ({{ $laporan->skoringRisiko->updated_at->format('d M Y H:i') }})
                            </div>
                        @endif
                    </div>
                @else
                    <div class="py-12 text-center text-gray-400 text-xs">
                        <svg class="size-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Laporan periode ini belum selesai dinilai di Meja Kerja Admin.
                    </div>
                @endif

                <!-- Progres Serapan & Status Laporan -->
                <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">Status Laporan:</span>
                        <span class="font-bold uppercase tracking-wider {{ $laporan?->status_laporan === \App\Enums\ReportStatus::Verified ? 'text-emerald-600' : 'text-brand-600' }}">
                            {{ $laporan?->status_laporan->value ?? 'Belum Submit' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">First-Time Right:</span>
                        <span class="font-semibold {{ $laporan?->lengkap_pengiriman_pertama ? 'text-emerald-600' : 'text-gray-400' }}">
                            {{ $laporan?->lengkap_pengiriman_pertama ? 'Ya (Lengkap Awal)' : 'Belum / Revisi' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500">Realisasi Serapan:</span>
                        <span class="font-mono font-bold text-gray-900 dark:text-white">
                            {{ $laporan?->persentase_serapan ?? 0 }}%
                            <span class="text-gray-400 font-normal">(Target: {{ $currentPeriode?->target_realisasi_persen ?? 50 }}%)</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Col-span 7: Breakdown 5 Parameter Skor -->
        <div class="lg:col-span-7 space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 mb-5">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Rincian Penilaian 5 Parameter Risiko ($P_1 - P_5$)
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Skala bobot: 0 (Terbaik/Sesuai), 1 (Deviasi Ringan), 2 (Perlu Pengingat), 3 (Tinggi/Kritis).
                        </p>
                    </div>
                </div>

                <div class="space-y-3.5">
                    @php
                        $skor = $laporan?->skoringRisiko;
                        $params = [
                            [
                                'code' => 'P1',
                                'title' => 'Ketepatan Waktu Pelaporan',
                                'desc' => 'Dihitung dari selisih waktu pengiriman pertama terhadap tenggat periode.',
                                'val' => $skor?->skor_p1 ?? 0,
                                'labels' => ['0: Tepat Waktu', '1: Terlambat 1-2 Hari (Toleransi)', '2: Terlambat 3-5 Hari', '3: Melewati >5 Hari'],
                            ],
                            [
                                'code' => 'P2',
                                'title' => 'Kelengkapan Dokumen Wajib',
                                'desc' => 'Evaluasi kelengkapan SPTJM, LRA, BKU, kuitansi, dan foto fisik kegiatan.',
                                'val' => $skor?->skor_p2 ?? 0,
                                'labels' => ['0: Lengkap Seluruhnya', '1: Kurang Minor', '2: >= 2 Dokumen Kurang', '3: Dokumen Utama Kurang / Tidak Valid'],
                            ],
                            [
                                'code' => 'P3',
                                'title' => 'Akurasi & Konsistensi Data',
                                'desc' => 'Kesesuaian angka laporan dengan kuitansi sah dan dokumen pendukung.',
                                'val' => $skor?->skor_p3 ?? 0,
                                'labels' => ['0: Konsisten & Valid', '1: Selisih Ringan Terkonfirmasi', '2: Ada Ketidaksesuaian Belum Jelas', '3: Deviasi Signifikan / Tidak Akurat'],
                            ],
                            [
                                'code' => 'P4',
                                'title' => 'Kesesuaian Realisasi dgn Rencana',
                                'desc' => 'Tingkat deviasi serapan anggaran terhadap rencana operasional balai.',
                                'val' => $skor?->skor_p4 ?? 0,
                                'labels' => ['0: Sesuai Rencana Target', '1: Deviasi Kecil dgn Keterangan', '2: Deviasi Perlu Klarifikasi', '3: Deviasi Berat / Tanpa Alasan'],
                            ],
                            [
                                'code' => 'P5',
                                'title' => 'Tindak Lanjut Hasil Monitoring',
                                'desc' => 'Kecepatan dan kepatuhan dalam menyelesaikan temuan kekurangan berkas.',
                                'val' => $skor?->skor_p5 ?? 0,
                                'labels' => ['0: Tuntas Tepat Waktu', '1: Keterlambatan Minor', '2: Melewati Tenggat / Pengingat', '3: Berulang / Belum Ditindaklanjuti'],
                            ],
                        ];
                    @endphp

                    @foreach($params as $p)
                        <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="size-7 rounded-lg bg-white dark:bg-gray-800 text-brand-600 dark:text-brand-400 font-mono font-bold text-xs flex items-center justify-center border border-gray-200 dark:border-gray-700 shadow-2xs">
                                        {{ $p['code'] }}
                                    </span>
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-900 dark:text-white">
                                            {{ $p['title'] }}
                                        </h4>
                                        <p class="text-[11px] text-gray-400">
                                            {{ $p['desc'] }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold font-mono px-2 py-0.5 rounded {{ $p['val'] === 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : ($p['val'] === 1 ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' : ($p['val'] === 2 ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300')) }}">
                                        Skor: {{ $p['val'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400 italic">
                                Keterangan status: {{ $p['labels'][$p['val']] ?? '-' }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <a href="/balai/laporan?balai_id={{ $balaiId }}" class="text-xs font-semibold text-brand-600 dark:text-brand-400 hover:underline flex items-center gap-1">
                        &larr; Periksa Input & Dokumen Laporan
                    </a>
                    <a href="/balai/tindak-lanjut?balai_id={{ $balaiId }}" class="text-xs font-semibold text-brand-600 dark:text-brand-400 hover:underline flex items-center gap-1">
                        Buka Tiket Tindak Lanjut &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
