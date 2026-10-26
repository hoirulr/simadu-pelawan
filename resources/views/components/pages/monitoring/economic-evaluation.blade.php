<?php

declare(strict_types=1);

use App\Models\BalaiPenyuluh;
use App\Models\PeriodeAnggaran;
use App\Services\EconomicValueCalculatorService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Evaluasi Nilai Ekonomis & Kinerja Inovasi')] class extends Component
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
        $periodes = PeriodeAnggaran::orderBy('tahun', 'desc')->get();
        $selectedPeriode = PeriodeAnggaran::find($this->selectedPeriodeId) ?? $periodes->first();

        $economicStats = app(EconomicValueCalculatorService::class)->calculate($this->selectedPeriodeId);

        // Data 8 Balai pada periode terpilih
        $balaiList = BalaiPenyuluh::with([
            'laporanAnggaran' => function ($q) {
                $q->where('periode_id', $this->selectedPeriodeId)
                    ->with(['skoringRisiko', 'ewsAlerts', 'tindakLanjutMonitoring']);
            },
        ])->orderBy('id', 'asc')->get();

        return [
            'periodes' => $periodes,
            'selectedPeriode' => $selectedPeriode,
            'economicStats' => $economicStats,
            'balaiList' => $balaiList,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Non-print Header & Toolbar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between print:hidden">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    Modul Evaluasi Kinerja Inovasi
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">Bukti Aksi Perubahan / Diklat PKA</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-1">
                Kalkulator & Rekapitulasi Nilai Ekonomis
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Penyajian 5 indikator terukur nilai ekonomis dan efisiensi tata kelola pelaporan anggaran DP2KBP3A Kabupaten Bangka.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-theme-xs">
                <label for="periodeFilterEco" class="text-xs font-medium text-gray-500 dark:text-gray-400 pl-2">Periode:</label>
                <select id="periodeFilterEco" wire:model.live="selectedPeriodeId" class="text-sm font-semibold text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 py-1 pr-6 cursor-pointer">
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" class="dark:bg-gray-800">{{ $p->tahun }} - {{ $p->tahap }}</option>
                    @endforeach
                </select>
            </div>

            <a href="{{ route('economic.export-pdf', $selectedPeriode->id) }}"
               class="px-4 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold shadow-theme-xs flex items-center gap-2 transition">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span>Unduh PDF Resmi</span>
            </a>

            <button type="button" onclick="window.print()"
                    class="px-4 py-2.5 rounded-xl bg-gray-900 hover:bg-black text-white dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 text-sm font-semibold shadow-theme-xs flex items-center gap-2 transition">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Cetak Lembar</span>
            </button>
        </div>
    </div>


    <!-- Official Document Header (Visible only when Printing) -->
    <div class="hidden print:block text-center border-b-2 border-gray-900 pb-4 mb-6">
        <h2 class="text-base font-bold uppercase tracking-wider text-gray-900">Pemerintah Kabupaten Bangka</h2>
        <h1 class="text-lg font-black uppercase text-gray-900">
            Dinas Pengendalian Penduduk, Keluarga Berencana, Pemberdayaan Perempuan dan Perlindungan Anak
        </h1>
        <p class="text-xs text-gray-700">Jalan Ahmad Yani, Sungailiat, Kabupaten Bangka, Kepulauan Bangka Belitung</p>
        <div class="mt-3 pt-2 border-t border-gray-400">
            <h3 class="text-sm font-bold uppercase text-gray-900">
                Laporan Evaluasi Indikator Nilai Ekonomis & Akuntabilitas Anggaran Balai KB
            </h3>
            <p class="text-xs text-gray-600 font-mono">
                Periode: {{ $selectedPeriode?->tahun }} - {{ $selectedPeriode?->tahap }} | Dicetak: {{ Carbon::now()->format('d F Y H:i') }} WIB
            </p>
        </div>
    </div>

    <!-- 5 Kartu Indikator Nilai Ekonomis Utama (FR-04.2) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        <!-- Indikator 1 -->
        <div class="p-5 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-400 font-medium">
                    <span>Indikator 1</span>
                    <span class="size-6 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-[10px]">P1</span>
                </div>
                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 mt-2">
                    Kecepatan Identifikasi
                </h4>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-3xl font-black font-mono text-gray-900 dark:text-white">
                        {{ $economicStats['avgIdentificationHours'] }}
                    </span>
                    <span class="text-xs font-semibold text-gray-400">Jam</span>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 mt-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                Rata-rata selisih waktu dari submit awal balai hingga validasi meja kerja.
            </p>
        </div>

        <!-- Indikator 2 -->
        <div class="p-5 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-400 font-medium">
                    <span>Indikator 2</span>
                    <span class="size-6 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-[10px]">P2</span>
                </div>
                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 mt-2">
                    Kepatuhan Tenggat Waktu
                </h4>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-3xl font-black font-mono text-emerald-600 dark:text-emerald-400">
                        {{ $economicStats['onTimePercentage'] }}%
                    </span>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 mt-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                {{ $economicStats['totalTepatWaktu'] }} dari 8 Balai KB submit sebelum atau tepat tenggat.
            </p>
        </div>

        <!-- Indikator 3 -->
        <div class="p-5 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-400 font-medium">
                    <span>Indikator 3</span>
                    <span class="size-6 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-[10px]">P3</span>
                </div>
                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 mt-2">
                    First-Time Right (FTR)
                </h4>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-3xl font-black font-mono text-brand-600 dark:text-brand-400">
                        {{ $economicStats['firstTimeRightPercentage'] }}%
                    </span>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 mt-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                {{ $economicStats['totalLengkapPertama'] }} Balai mengunggah seluruh berkas lengkap pada submit pertama.
            </p>
        </div>

        <!-- Indikator 4 -->
        <div class="p-5 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-400 font-medium">
                    <span>Indikator 4</span>
                    <span class="size-6 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-[10px]">P4</span>
                </div>
                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 mt-2">
                    Waktu Resolusi Temuan
                </h4>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-3xl font-black font-mono text-gray-900 dark:text-white">
                        {{ $economicStats['avgResolutionHours'] }}
                    </span>
                    <span class="text-xs font-semibold text-gray-400">Jam</span>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 mt-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                Rata-rata durasi penyelesaian koreksi berkas hingga status Verified.
            </p>
        </div>

        <!-- Indikator 5 -->
        <div class="p-5 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-theme-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between text-xs text-gray-400 font-medium">
                    <span>Indikator 5</span>
                    <span class="size-6 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-[10px]">P5</span>
                </div>
                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 mt-2">
                    Efisiensi Koordinasi
                </h4>
                <div class="mt-3 flex items-baseline gap-1.5">
                    <span class="text-3xl font-black font-mono text-gray-900 dark:text-white">
                        {{ $economicStats['totalRecordedCoordinations'] }}
                    </span>
                    <span class="text-xs font-semibold text-gray-400">Log</span>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 mt-3 pt-2 border-t border-gray-100 dark:border-gray-800">
                Akumulasi peringatan otomatis EWS dan tiket tindak lanjut terekam.
            </p>
        </div>
    </div>

    <!-- Evaluasi Serapan Anggaran Tahap I ke Tahap II Banner -->
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                    Evaluasi Serapan Anggaran Bertahap (Tahap I &rarr; Tahap II)
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Target Tahap I: Minimal 50.00% untuk persetujuan pencairan Tahap II. Target Akhir Tahun: Minimal 95.00%.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                    Pagu Total: Rp {{ number_format((float) ($selectedPeriode?->pagu_anggaran ?? 0), 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-5">
            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800">
                <span class="text-xs font-semibold text-gray-500">Tahap Periode Aktif</span>
                <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ $selectedPeriode?->tahap }}</div>
                <div class="text-xs text-gray-400 mt-1">Bulan Ke-{{ $selectedPeriode?->bulan }} Tahun {{ $selectedPeriode?->tahun }}</div>
            </div>

            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800">
                <span class="text-xs font-semibold text-gray-500">Target Serapan Minimal</span>
                <div class="text-xl font-bold font-mono text-brand-600 dark:text-brand-400 mt-1">
                    {{ $selectedPeriode?->target_realisasi_persen ?? 50 }}%
                </div>
                <div class="text-xs text-gray-400 mt-1">Acuan kelancaran pencairan berikutnya</div>
            </div>

            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800">
                <span class="text-xs font-semibold text-gray-500">Tenggat Akhir Pelaporan</span>
                <div class="text-xl font-bold font-mono text-gray-900 dark:text-white mt-1">
                    {{ $selectedPeriode?->tgl_tenggat_lapor ? Carbon::parse($selectedPeriode->tgl_tenggat_lapor)->format('d M Y') : '-' }}
                </div>
                <div class="text-xs text-gray-400 mt-1">Pemicu Early Warning System (EWS 1)</div>
            </div>
        </div>
    </div>

    <!-- Matriks Rekapitulasi Kinerja 8 Balai Penyuluh KB (Print & Screen Table) -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                    Matriks Evaluasi Kinerja Pelaporan 8 Balai Penyuluh KB
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Data komparatif serapan, ketepatan waktu, skor risiko, dan kelengkapan berkas antar-balai.
                </p>
            </div>
            <span class="text-xs font-mono text-gray-400">8 Balai Terdaftar</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500 dark:bg-gray-800/50 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4">Balai Penyuluh KB</th>
                        <th class="py-3.5 px-4 text-right">Rencana Pagu</th>
                        <th class="py-3.5 px-4 text-right">Realisasi Serapan</th>
                        <th class="py-3.5 px-4 text-center">Serapan (%)</th>
                        <th class="py-3.5 px-4 text-center">First-Time Right</th>
                        <th class="py-3.5 px-4 text-center">Kategori Risiko</th>
                        <th class="py-3.5 px-4 text-center">Skor (0-15)</th>
                        <th class="py-3.5 px-4 text-center">Tiket Temuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($balaiList as $index => $b)
                        @php
                            $lap = $b->laporanAnggaran->first();
                            $pagu = $lap ? (float) $lap->rencana_anggaran : 0.0;
                            $realisasi = $lap ? (float) $lap->realisasi_anggaran : 0.0;
                            $serapan = $pagu > 0 ? round(($realisasi / $pagu) * 100, 1) : 0.0;
                            $target = (float) ($selectedPeriode?->target_realisasi_persen ?? 50);
                            $skorObj = $lap?->skoringRisiko;
                        @endphp
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition">
                            <td class="py-3.5 px-4 text-center font-mono text-xs text-gray-400">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-gray-900 dark:text-white">
                                    {{ $b->nama_balai }}
                                </div>
                                <div class="text-[11px] text-gray-400">
                                    Kecamatan {{ $b->kecamatan }} • Kepala: {{ $b->kepala_balai }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-xs text-gray-700 dark:text-gray-300">
                                Rp {{ number_format($pagu, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-xs font-semibold text-gray-900 dark:text-white">
                                Rp {{ number_format($realisasi, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded {{ $serapan >= $target ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' }}">
                                    {{ $serapan }}%
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($lap?->lengkap_pengiriman_pertama)
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Lengkap
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">Belum / Revisi</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($skorObj)
                                    @php
                                        $cat = is_object($skorObj->kategori_risiko) ? $skorObj->kategori_risiko->value : $skorObj->kategori_risiko;
                                    @endphp
                                    <x-risk-badge :kategori="$cat" />
                                @else
                                    <span class="text-xs text-gray-400 font-mono">-</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-xs text-gray-900 dark:text-white">
                                {{ $skorObj?->total_skor ?? '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @php
                                    $ticketsCount = $lap?->tindakLanjutMonitoring?->count() ?? 0;
                                    $resolvedCount = $lap?->tindakLanjutMonitoring?->where('status', 'Verified')->count() ?? 0;
                                @endphp
                                @if($ticketsCount > 0)
                                    <span class="text-xs font-mono font-semibold {{ $resolvedCount === $ticketsCount ? 'text-emerald-600' : 'text-amber-600' }}">
                                        {{ $resolvedCount }}/{{ $ticketsCount }} Tuntas
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 font-mono">0 Tiket</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Official Signature Block (Visible on Print) -->
    <div class="hidden print:block mt-12 pt-6">
        <div class="flex justify-between items-start text-xs text-gray-800">
            <div class="text-center w-64">
                <p>Mengetahui,</p>
                <p class="font-bold uppercase mt-1">Kepala Dinas P2KBP3A</p>
                <p class="uppercase">Kabupaten Bangka</p>
                <div class="h-20"></div>
                <p class="font-bold underline uppercase">( .................................................... )</p>
                <p class="font-mono text-[11px]">NIP. .........................................</p>
            </div>

            <div class="text-center w-64">
                <p>Sungailiat, {{ Carbon::now()->translatedFormat('d F Y') }}</p>
                <p class="font-bold uppercase mt-1">Verifikator Anggaran & Inovasi</p>
                <p class="uppercase">SIMADU PELAWAN</p>
                <div class="h-20"></div>
                <p class="font-bold underline uppercase">{{ Auth::user()->name ?? 'Admin Dinas DP2KBP3A' }}</p>
                <p class="font-mono text-[11px]">Pengelola Data Anggaran</p>
            </div>
        </div>
    </div>
</div>
