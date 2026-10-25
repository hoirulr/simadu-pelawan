<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Models\BalaiPenyuluh;
use App\Models\DokumenLaporan;
use App\Models\EwsAlert;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Input & Checklist Laporan Anggaran Balai')] class extends Component
{
    public int $balaiId = 1;

    public int $periodeId = 1;

    public float $rencanaAnggaran = 0.0;

    public float $realisasiAnggaran = 0.0;

    public string $catatanBalai = '';

    public array $dokumenStatuses = [];

    public ?string $successMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if ($user && $user->isOperatorBalai() && $user->balai_id) {
            $this->balaiId = $user->balai_id;
        } elseif ($user && $user->balai_id) {
            $this->balaiId = $user->balai_id;
        } elseif (request()->has('balai_id')) {
            $this->balaiId = (int) request('balai_id');
        }

        $activePeriode = PeriodeAnggaran::where('is_active', true)->first();
        if ($activePeriode) {
            $this->periodeId = $activePeriode->id;
        }

        $this->loadLaporanData();
    }

    public function updatedBalaiId(): void
    {
        $user = Auth::user();
        if ($user && $user->isOperatorBalai() && $user->balai_id) {
            $this->balaiId = $user->balai_id;
        }

        $this->loadLaporanData();
    }

    public function updatedPeriodeId(): void
    {
        $this->loadLaporanData();
    }

    public function loadLaporanData(): void
    {
        $laporan = LaporanAnggaran::where('balai_id', $this->balaiId)
            ->where('periode_id', $this->periodeId)
            ->with(['dokumenLaporan', 'ewsAlerts'])
            ->first();

        if ($laporan) {
            $this->rencanaAnggaran = (float) $laporan->rencana_anggaran;
            $this->realisasiAnggaran = (float) $laporan->realisasi_anggaran;
            $this->catatanBalai = $laporan->catatan_balai ?? '';

            $this->dokumenStatuses = [];
            foreach ($laporan->dokumenLaporan as $dok) {
                $this->dokumenStatuses[$dok->id] = $dok->status_validasi;
            }
        } else {
            $periode = PeriodeAnggaran::find($this->periodeId);
            $this->rencanaAnggaran = $periode ? round((float) $periode->pagu_anggaran / 8, 2) : 0.0;
            $this->realisasiAnggaran = 0.0;
            $this->catatanBalai = '';
            $this->dokumenStatuses = [];
        }
    }

    public function toggleDokumenUpload(int $dokumenId): void
    {
        $dok = DokumenLaporan::with('laporanAnggaran')->find($dokumenId);
        $user = Auth::user();
        if ($dok && $user && $user->isOperatorBalai() && $dok->laporanAnggaran->balai_id !== $user->balai_id) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mengubah dokumen balai lain.');
        }

        if ($dok) {
            $newStatus = $dok->status_validasi === 'lengkap' ? 'kurang' : 'lengkap';
            $dok->update([
                'status_validasi' => $newStatus,
                'file_name' => $newStatus === 'lengkap' ? ($dok->file_name ?: 'berkas_dukung.pdf') : null,
                'file_size' => $newStatus === 'lengkap' ? 1024 * 512 : null,
            ]);
            $this->dokumenStatuses[$dokumenId] = $newStatus;
        }
    }

    public function submitReport(): void
    {
        $user = Auth::user();
        if ($user && $user->isOperatorBalai() && $user->balai_id) {
            $this->balaiId = $user->balai_id;
        }
        $serapan = $this->rencanaAnggaran > 0
            ? round(($this->realisasiAnggaran / $this->rencanaAnggaran) * 100, 2)
            : 0.0;

        $laporan = LaporanAnggaran::updateOrCreate(
            [
                'balai_id' => $this->balaiId,
                'periode_id' => $this->periodeId,
            ],
            [
                'rencana_anggaran' => $this->rencanaAnggaran,
                'realisasi_anggaran' => $this->realisasiAnggaran,
                'persentase_serapan' => $serapan,
                'catatan_balai' => $this->catatanBalai,
                'status_laporan' => ReportStatus::Submitted,
            ]
        );

        if (! $laporan->tgl_submit_pertama) {
            $laporan->update([
                'tgl_submit_pertama' => Carbon::now(),
            ]);

            // Cek apakah langsung lengkap
            $allComplete = $laporan->dokumenLaporan()->where('status_validasi', '!=', 'lengkap')->count() === 0;
            if ($allComplete && $laporan->dokumenLaporan()->count() > 0) {
                $laporan->update(['lengkap_pengiriman_pertama' => true]);
            }
        }

        $this->successMessage = 'Laporan anggaran berhasil disimpan dan dikirim ke Admin Dinas untuk verifikasi!';
    }

    public function with(): array
    {
        $balaiList = BalaiPenyuluh::orderBy('id', 'asc')->get();
        $periodes = PeriodeAnggaran::orderBy('tahun', 'desc')->get();
        $currentBalai = BalaiPenyuluh::find($this->balaiId);
        $currentPeriode = PeriodeAnggaran::find($this->periodeId);

        $laporan = LaporanAnggaran::where('balai_id', $this->balaiId)
            ->where('periode_id', $this->periodeId)
            ->with(['dokumenLaporan', 'ewsAlerts', 'skoringRisiko'])
            ->first();

        $persentaseSerapan = $this->rencanaAnggaran > 0
            ? round(($this->realisasiAnggaran / $this->rencanaAnggaran) * 100, 1)
            : 0.0;

        $activeAlerts = $laporan ? $laporan->ewsAlerts->whereIn('status_ews', [\App\Enums\EwsStatus::Open, \App\Enums\EwsStatus::Escalated]) : collect();

        return [
            'balaiList' => $balaiList,
            'periodes' => $periodes,
            'currentBalai' => $currentBalai,
            'currentPeriode' => $currentPeriode,
            'laporan' => $laporan,
            'persentaseSerapan' => $persentaseSerapan,
            'activeAlerts' => $activeAlerts,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header & Selection Bar -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    Modul Balai Penyuluh KB
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">Kec. {{ $currentBalai?->kecamatan }}</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-1">
                Pelaporan Anggaran: {{ $currentBalai?->nama_balai }}
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Input rencana & realisasi serapan anggaran serta unggah berkas pendukung wajib.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <!-- Balai Selector (Enabled for Admin only) -->
            @if(!Auth::user() || Auth::user()->isAdmin())
                <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-theme-xs">
                    <label for="balaiSelector" class="text-xs font-medium text-gray-500 dark:text-gray-400 pl-2">Balai:</label>
                    <select id="balaiSelector" wire:model.live="balaiId" class="text-sm font-semibold text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 py-1 pr-6 cursor-pointer">
                        @foreach($balaiList as $b)
                            <option value="{{ $b->id }}" class="dark:bg-gray-800">{{ $b->nama_balai }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <!-- Periode Selector -->
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-1.5 rounded-xl border border-gray-200 dark:border-gray-700 shadow-theme-xs">
                <label for="periodeSelector" class="text-xs font-medium text-gray-500 dark:text-gray-400 pl-2">Periode:</label>
                <select id="periodeSelector" wire:model.live="periodeId" class="text-sm font-semibold text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 py-1 pr-6 cursor-pointer">
                    @foreach($periodes as $p)
                        <option value="{{ $p->id }}" class="dark:bg-gray-800">{{ $p->tahun }} - {{ $p->tahap }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Success Feedback Alert -->
    @if($successMessage)
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/30 dark:text-emerald-300 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium">
                <svg class="size-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ $successMessage }}</span>
            </div>
            <button wire:click="$set('successMessage', null)" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif

    <!-- EWS Alert Banner for this Balai (if any) -->
    @if($activeAlerts->isNotEmpty())
        @foreach($activeAlerts as $alert)
            <div class="rounded-2xl border {{ $alert->status_ews === \App\Enums\EwsStatus::Escalated ? 'border-rose-300 bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10' : 'border-amber-300 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10' }} p-4.5 flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="size-9 rounded-xl {{ $alert->status_ews === \App\Enums\EwsStatus::Escalated ? 'bg-rose-500' : 'bg-amber-500' }} text-white flex items-center justify-center shrink-0">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold font-mono px-2 py-0.5 rounded {{ $alert->status_ews === \App\Enums\EwsStatus::Escalated ? 'bg-rose-600 text-white' : 'bg-amber-600 text-white' }}">
                                {{ $alert->kode_ews->value }}
                            </span>
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                {{ $alert->kode_ews->title() }}
                            </span>
                        </div>
                        <p class="text-sm font-semibold {{ $alert->status_ews === \App\Enums\EwsStatus::Escalated ? 'text-rose-900 dark:text-rose-300' : 'text-amber-900 dark:text-amber-300' }} mt-1">
                            {{ $alert->kondisi_pemicu }}
                        </p>
                        @if($alert->batas_waktu_koreksi)
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Tenggat Koreksi: <span class="font-mono font-bold">{{ $alert->batas_waktu_koreksi->format('d M Y H:i') }}</span>
                                ({{ $alert->batas_waktu_koreksi->diffForHumans() }})
                            </p>
                        @endif
                    </div>
                </div>
                <a href="/monitoring" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-gray-800 border border-gray-200 hover:bg-gray-50 shadow-theme-xs shrink-0">
                    Buka Tiket Perbaikan
                </a>
            </div>
        @endforeach
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <!-- Panel Input Anggaran (Col-span 5 on xl) -->
        <div class="xl:col-span-5 space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 mb-5">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Realisasi Anggaran Balai
                    </h3>
                    <span class="px-2.5 py-0.5 text-xs font-bold rounded-full {{ $persentaseSerapan >= ($currentPeriode->target_realisasi_persen ?? 50) ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400' }}">
                        Serapan: {{ $persentaseSerapan }}%
                    </span>
                </div>

                <form wire:submit.prevent="submitReport" class="space-y-4">
                    <!-- Rencana Anggaran / Pagu -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Rencana Anggaran / Pagu (Rp)
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-mono text-gray-400">Rp</span>
                            <input type="number" step="1000" wire:model.live="rencanaAnggaran"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm font-mono font-semibold text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-800 dark:text-white">
                        </div>
                    </div>

                    <!-- Realisasi Anggaran -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Realisasi Anggaran Aktual (Rp)
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-mono text-gray-400">Rp</span>
                            <input type="number" step="1000" wire:model.live="realisasiAnggaran"
                                   class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm font-mono font-semibold text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-800 dark:text-white">
                        </div>
                    </div>

                    <!-- Visual Progress Serapan Livewire 4 Reactive -->
                    <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between text-xs mb-1.5 font-medium">
                            <span class="text-gray-600 dark:text-gray-400">Progres Serapan Tahap I:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $persentaseSerapan }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                            <div class="h-2.5 rounded-full transition-all duration-300 {{ $persentaseSerapan >= 50 ? 'bg-emerald-500' : 'bg-brand-500' }}"
                                 style="width: {{ min(100, $persentaseSerapan) }}%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-gray-400 mt-2 font-mono">
                            <span>Target Minimum: 50%</span>
                            <span>Sisa Pagu: Rp {{ number_format(max(0, $rencanaAnggaran - $realisasiAnggaran), 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Catatan Balai -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Catatan Operasional / Deviasi Balai
                        </label>
                        <textarea wire:model="catatanBalai" rows="3"
                                  placeholder="Keterangan penyerapan anggaran atau kendala pelaksanaan lapangan..."
                                  class="w-full p-3 rounded-xl border border-gray-200 text-xs text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-800 dark:text-white"></textarea>
                    </div>

                    <!-- Unsaved Changes Notice ($wire.$dirty()) -->
                    <div wire:dirty class="p-2 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 text-xs flex items-center gap-1.5">
                        <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>Terdapat perubahan angka yang belum disimpan.</span>
                    </div>

                    <!-- Submit Button with data-loading -->
                    <button type="submit"
                            class="w-full py-3 px-4 rounded-xl bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm shadow-theme-xs flex items-center justify-center gap-2 transition data-loading:opacity-60 data-loading:pointer-events-none">
                        <span>Kirim & Perbarui Laporan</span>
                        <svg class="hidden data-loading:inline-block size-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Panel Checklist Dokumen Wajib (Col-span 7 on xl) -->
        <div class="xl:col-span-7">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 mb-5">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Checklist Dokumen Pendukung Wajib
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Kelengkapan dokumen wajib pada pengiriman pertama menentukan penilaian indikator First-Time Right.
                        </p>
                    </div>
                </div>

                <!-- Dokumen List Table -->
                <div class="space-y-3">
                    @forelse($laporan?->dokumenLaporan ?? collect() as $index => $dok)
                        <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 hover:border-gray-200 dark:hover:border-gray-700 bg-gray-50/50 dark:bg-gray-800/30 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 transition min-w-0">
                            <div class="flex items-start gap-3 min-w-0 flex-1">
                                <div class="size-8 rounded-lg {{ $dok->status_validasi === 'lengkap' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400' }} flex items-center justify-center shrink-0 mt-0.5">
                                    @if($dok->status_validasi === 'lengkap')
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white break-words leading-snug">
                                        {{ $dok->nama_dokumen_wajib }}
                                    </h4>
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1 text-xs text-gray-400 min-w-0">
                                        @if($dok->file_name)
                                            <span class="font-mono text-gray-600 dark:text-gray-300 truncate max-w-full sm:max-w-xs md:max-w-sm block" title="{{ $dok->file_name }}">
                                                {{ $dok->file_name }}
                                            </span>
                                            <span class="shrink-0">•</span>
                                            <span class="shrink-0 font-mono">{{ round($dok->file_size / 1024, 0) }} KB</span>
                                        @else
                                            <span class="italic text-amber-600 dark:text-amber-400">Berkas belum diunggah</span>
                                        @endif
                                    </div>
                                    @if($dok->catatan_dokumen)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic break-words">
                                            "{{ $dok->catatan_dokumen }}"
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0 self-start sm:self-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold border shrink-0 {{ $dok->status_validasi === 'lengkap' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-400' }}">
                                    {{ ucfirst($dok->status_validasi) }}
                                </span>

                                <button type="button" wire:click="toggleDokumenUpload({{ $dok->id }})"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 transition shrink-0">
                                    {{ $dok->status_validasi === 'lengkap' ? 'Batalkan' : 'Unggah File' }}
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400 text-sm">
                            Belum ada template checklist dokumen untuk periode ini.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>