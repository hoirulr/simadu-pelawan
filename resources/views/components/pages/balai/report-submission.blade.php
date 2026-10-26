<?php

declare(strict_types=1);

use App\Enums\EwsStatus;
use App\Enums\ReportStatus;
use App\Models\BalaiPenyuluh;
use App\Models\DokumenLaporan;
use App\Models\EwsAlert;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] #[Title('Input & Checklist Laporan Anggaran Balai')] class extends Component
{
    use WithFileUploads;

    public int $balaiId = 1;

    public int $periodeId = 1;

    public float $rencanaAnggaran = 0.0;

    public float $realisasiAnggaran = 0.0;

    public string $catatanBalai = '';

    public array $dokumenStatuses = [];

    /** @var array<int, mixed> */
    public array $documentFiles = [];

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

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

        $this->loadLaporanData();
    }

    public function updatedBalaiId(): void
    {
        $user = Auth::user();
        if ($user && $user->isOperatorBalai()) {
            $this->balaiId = (int) $user->balai_id;
        }

        $this->loadLaporanData();
    }

    public function updatedPeriodeId(): void
    {
        $this->loadLaporanData();
    }

    public function loadLaporanData(): void
    {
        $user = Auth::user();
        if ($user && $user->isOperatorBalai() && (int) $this->balaiId !== (int) $user->balai_id) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses ke data balai lain.');
        }

        $laporan = LaporanAnggaran::where('balai_id', $this->balaiId)
            ->where('periode_id', $this->periodeId)
            ->with(['dokumenLaporan', 'ewsAlerts'])
            ->first();

        if ($laporan) {
            $this->rencanaAnggaran = (float) $laporan->rencana_anggaran;
            $this->realisasiAnggaran = (float) $laporan->realisasi_anggaran;
            $this->catatanBalai = $laporan->catatan_balai ?? '';

            // Pastikan checklist dokumen wajib diinisialisasi jika kosong
            if ($laporan->dokumenLaporan->isEmpty()) {
                $periode = PeriodeAnggaran::find($this->periodeId);
                $dokWajib = $periode?->dokumen_wajib_json ?? [
                    'Laporan Realisasi Anggaran (LRA)',
                    'Surat Pertanggungjawaban Mutlak (SPTJM)',
                    'Buku Kas Umum & Kuitansi',
                    'Dokumentasi Kegiatan',
                    'Rencana Kebutuhan Tahap II',
                ];
                foreach ($dokWajib as $namaDok) {
                    DokumenLaporan::create([
                        'laporan_id' => $laporan->id,
                        'nama_dokumen_wajib' => $namaDok,
                        'status_validasi' => 'kurang',
                    ]);
                }
                $laporan->load('dokumenLaporan');
            }

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

    public function submitReport(): void
    {
        $this->resetValidation();
        $this->errorMessage = null;

        $user = Auth::user();
        if ($user && $user->isOperatorBalai()) {
            $this->balaiId = (int) $user->balai_id;
        }

        $this->validate([
            'balaiId' => ['required', 'integer', 'exists:balai_penyuluh,id'],
            'periodeId' => ['required', 'integer', 'exists:periode_anggaran,id'],
            'rencanaAnggaran' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'realisasiAnggaran' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'catatanBalai' => ['nullable', 'string', 'max:2000'],
        ], [
            'balaiId.required' => 'Balai wajib dipilih.',
            'balaiId.exists' => 'Balai tidak valid.',
            'periodeId.required' => 'Periode anggaran wajib dipilih.',
            'periodeId.exists' => 'Periode anggaran tidak valid.',
            'rencanaAnggaran.required' => 'Pagu/rencana anggaran wajib diisi.',
            'rencanaAnggaran.numeric' => 'Rencana anggaran harus berupa angka.',
            'rencanaAnggaran.min' => 'Rencana anggaran tidak boleh bernilai negatif.',
            'rencanaAnggaran.max' => 'Rencana anggaran melebihi batas maksimum.',
            'realisasiAnggaran.required' => 'Realisasi anggaran wajib diisi.',
            'realisasiAnggaran.numeric' => 'Realisasi anggaran harus berupa angka.',
            'realisasiAnggaran.min' => 'Realisasi anggaran tidak boleh bernilai negatif.',
            'realisasiAnggaran.max' => 'Realisasi anggaran melebihi batas maksimum.',
            'catatanBalai.max' => 'Catatan balai maksimal 2000 karakter.',
        ]);

        if ($user && $user->isOperatorBalai() && (int) $this->balaiId !== (int) $user->balai_id) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengirim laporan untuk balai lain.');
        }

        try {
            app(\App\Actions\SubmitLaporanAnggaranAction::class)->execute(
                $this->balaiId,
                $this->periodeId,
                $this->rencanaAnggaran,
                $this->realisasiAnggaran,
                $this->catatanBalai,
                $user
            );

            $this->loadLaporanData();
            $this->successMessage = 'Laporan anggaran berhasil disimpan dan dikirim ke Admin Dinas untuk verifikasi!';
        } catch (\DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function uploadDokumen(int $dokumenId): void
    {
        $this->resetValidation();
        $dok = DokumenLaporan::with('laporanAnggaran')->findOrFail($dokumenId);

        $this->validate([
            "documentFiles.{$dokumenId}" => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
                'max:10240',
            ],
        ], [
            "documentFiles.{$dokumenId}.required" => 'Silakan pilih berkas dokumen untuk diunggah.',
            "documentFiles.{$dokumenId}.file" => 'Berkas harus berupa file yang valid.',
            "documentFiles.{$dokumenId}.mimes" => 'Format berkas harus PDF, DOC, DOCX, XLS, XLSX, JPG, atau PNG.',
            "documentFiles.{$dokumenId}.max" => 'Ukuran berkas maksimal 10 MB.',
        ]);

        $uploaded = $this->documentFiles[$dokumenId];

        try {
            app(\App\Actions\UploadDokumenLaporanAction::class)->execute(
                $dok,
                $uploaded,
                Auth::user()
            );

            unset($this->documentFiles[$dokumenId]);
            $this->dokumenStatuses[$dokumenId] = 'lengkap';
            $this->successMessage = 'Dokumen "'.$dok->nama_dokumen_wajib.'" berhasil diunggah.';
        } catch (\DomainException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }


    public function deleteDokumen(int $dokumenId): void
    {
        $dok = DokumenLaporan::with('laporanAnggaran')->findOrFail($dokumenId);
        Gate::authorize('upload', $dok);

        if ($dok->file_path && Storage::disk('local')->exists($dok->file_path)) {
            Storage::disk('local')->delete($dok->file_path);
        }

        $dok->update([
            'file_path' => null,
            'file_name' => null,
            'file_size' => null,
            'status_validasi' => 'kurang',
        ]);

        unset($this->documentFiles[$dokumenId]);
        $this->dokumenStatuses[$dokumenId] = 'kurang';
        $this->successMessage = 'Dokumen "'.$dok->nama_dokumen_wajib.'" berhasil dihapus.';
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
            ->with(['dokumenLaporan', 'ewsAlerts', 'skoringRisiko'])
            ->first();

        $persentaseSerapan = $this->rencanaAnggaran > 0
            ? round(($this->realisasiAnggaran / $this->rencanaAnggaran) * 100, 1)
            : 0.0;

        $activeAlerts = $laporan ? $laporan->ewsAlerts->whereIn('status_ews', [EwsStatus::Open, EwsStatus::Escalated]) : collect();

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
            <button wire:click="$set('successMessage', null)" class="text-emerald-500 hover:text-emerald-700 font-bold">&times;</button>
        </div>
    @endif

    <!-- Error Feedback Alert -->
    @if($errorMessage)
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 dark:bg-rose-500/10 dark:border-rose-500/30 dark:text-rose-300 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium">
                <svg class="size-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>{{ $errorMessage }}</span>
            </div>
            <button wire:click="$set('errorMessage', null)" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
        </div>
    @endif

    <!-- EWS Alert Banner for this Balai (if any) -->
    @if($activeAlerts->isNotEmpty())
        @foreach($activeAlerts as $alert)
            <div class="rounded-2xl border {{ $alert->status_ews === EwsStatus::Escalated ? 'border-rose-300 bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10' : 'border-amber-300 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10' }} p-4.5 flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="size-9 rounded-xl {{ $alert->status_ews === EwsStatus::Escalated ? 'bg-rose-500' : 'bg-amber-500' }} text-white flex items-center justify-center shrink-0">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold font-mono px-2 py-0.5 rounded {{ $alert->status_ews === EwsStatus::Escalated ? 'bg-rose-600 text-white' : 'bg-amber-600 text-white' }}">
                                {{ $alert->kode_ews->value }}
                            </span>
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                {{ $alert->kode_ews->title() }}
                            </span>
                        </div>
                        <p class="text-sm font-semibold {{ $alert->status_ews === EwsStatus::Escalated ? 'text-rose-900 dark:text-rose-300' : 'text-amber-900 dark:text-amber-300' }} mt-1">
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
                <a href="/balai/tindak-lanjut" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-gray-800 border border-gray-200 hover:bg-gray-50 shadow-theme-xs shrink-0">
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
                        @error('rencanaAnggaran') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
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
                        @error('realisasiAnggaran') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
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
                            <span>Target Minimum: {{ $currentPeriode->target_realisasi_persen ?? 50 }}%</span>
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
                        @error('catatanBalai') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
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
                            class="w-full py-3 px-4 rounded-xl bg-brand-500 hover:bg-brand-600 text-white font-semibold text-sm shadow-theme-xs flex items-center justify-center gap-2 transition data-loading:opacity-60 data-loading:pointer-events-none cursor-pointer">
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
                            Kelengkapan dokumen wajib pada pengiriman pertama menentukan penilaian indikator First-Time Right. Format: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG (Maks 10 MB).
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
                                        @if($dok->file_path && $dok->file_name)
                                            <a href="{{ route('dokumen.download', $dok->id) }}"
                                               target="_blank"
                                               class="font-mono text-brand-600 dark:text-brand-400 hover:underline inline-flex items-center gap-1 font-semibold truncate max-w-full sm:max-w-xs block"
                                               title="Unduh Berkas: {{ $dok->file_name }}">
                                                <svg class="size-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                <span>{{ $dok->file_name }}</span>
                                            </a>
                                            <span class="shrink-0">•</span>
                                            <span class="shrink-0 font-mono">{{ round(($dok->file_size ?: 0) / 1024, 0) }} KB</span>
                                        @else
                                            <span class="italic text-amber-600 dark:text-amber-400">Berkas belum diunggah</span>
                                        @endif
                                    </div>
                                    @if($dok->catatan_dokumen)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 italic break-words">
                                            "{{ $dok->catatan_dokumen }}"
                                        </p>
                                    @endif

                                    <!-- File Upload Error -->
                                    @error("documentFiles.{$dok->id}")
                                        <span class="text-xs text-rose-500 font-semibold block mt-1.5">{{ $message }}</span>
                                    @enderror

                                    <div wire:loading wire:target="documentFiles.{{ $dok->id }}" class="text-xs text-brand-600 font-medium mt-1">
                                        Memproses berkas terpilih...
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0 self-start sm:self-center">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold border shrink-0 {{ $dok->status_validasi === 'lengkap' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-400' }}">
                                    {{ ucfirst($dok->status_validasi) }}
                                </span>

                                <div class="flex items-center gap-1.5">
                                    <label class="cursor-pointer px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-gray-700 border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 transition flex items-center gap-1">
                                        <svg class="size-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                        </svg>
                                        <span>{{ $dok->file_path ? 'Ganti' : 'Pilih File' }}</span>
                                        <input type="file" wire:model="documentFiles.{{ $dok->id }}" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                                    </label>

                                    @if(isset($documentFiles[$dok->id]))
                                        <button type="button" wire:click="uploadDokumen({{ $dok->id }})"
                                                wire:loading.attr="disabled"
                                                class="px-3 py-1.5 rounded-lg text-xs font-bold bg-brand-500 hover:bg-brand-600 text-white shadow-sm flex items-center gap-1 transition cursor-pointer">
                                            <span wire:loading.remove wire:target="uploadDokumen({{ $dok->id }})">Simpan</span>
                                            <span wire:loading wire:target="uploadDokumen({{ $dok->id }})">...</span>
                                        </button>
                                    @endif

                                    @if($dok->file_path)
                                        <button type="button" wire:click="deleteDokumen({{ $dok->id }})"
                                                wire:confirm="Apakah Anda yakin ingin menghapus berkas dokumen ini?"
                                                class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                                                title="Hapus Berkas Dokumen">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400 text-sm">
                            Silakan simpan laporan anggaran di sebelah kiri terlebih dahulu untuk memunculkan daftar checklist dokumen wajib.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>