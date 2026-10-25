<?php

declare(strict_types=1);

use App\Models\PeriodeAnggaran;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Master Periode Anggaran')] class extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    public int $tahun = 2026;
    public string $tahap = 'Tahap I';
    public int $bulan = 6;
    public float $pagu_anggaran = 1250000000.00;
    public float $target_realisasi_persen = 50.00;
    public string $tgl_tenggat_lapor = '';
    public bool $is_active = false;

    /** @var array<int, string> */
    public array $dokumen_wajib = [];
    public string $newDokumenName = '';

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $this->tgl_tenggat_lapor = Carbon::now()->addDays(14)->format('Y-m-d\TH:i');
        $this->dokumen_wajib = [
            'Laporan Realisasi Anggaran (LRA) Per Kegiatan',
            'Surat Pernyataan Tanggung Jawab Mutlak (SPTJM)',
            'Buku Kas Umum (BKU) & Rekapitulasi Kuitansi Sah',
            'Dokumentasi Bukti Fisik / Foto Pelaksanaan Kegiatan',
            'Rencana Kebutuhan Operasional & Penyerapan Lanjutan',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->tahun = (int) date('Y');
        $this->tahap = 'Tahap I';
        $this->bulan = 6;
        $this->pagu_anggaran = 1250000000.00;
        $this->target_realisasi_persen = 50.00;
        $this->tgl_tenggat_lapor = Carbon::now()->addDays(14)->format('Y-m-d\TH:i');
        $this->is_active = false;
        $this->dokumen_wajib = [
            'Laporan Realisasi Anggaran (LRA) Per Kegiatan',
            'Surat Pernyataan Tanggung Jawab Mutlak (SPTJM)',
            'Buku Kas Umum (BKU) & Rekapitulasi Kuitansi Sah',
            'Dokumentasi Bukti Fisik / Foto Pelaksanaan Kegiatan',
            'Rencana Kebutuhan Operasional & Penyerapan Lanjutan',
        ];
        $this->newDokumenName = '';
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $periode = PeriodeAnggaran::findOrFail($id);

        $this->editingId = $periode->id;
        $this->tahun = (int) $periode->tahun;
        $this->tahap = $periode->tahap;
        $this->bulan = (int) $periode->bulan;
        $this->pagu_anggaran = (float) $periode->pagu_anggaran;
        $this->target_realisasi_persen = (float) $periode->target_realisasi_persen;
        $this->tgl_tenggat_lapor = $periode->tgl_tenggat_lapor ? Carbon::parse($periode->tgl_tenggat_lapor)->format('Y-m-d\TH:i') : '';
        $this->is_active = (bool) $periode->is_active;
        $this->dokumen_wajib = is_array($periode->dokumen_wajib_json) ? $periode->dokumen_wajib_json : [];
        $this->newDokumenName = '';
        $this->showModal = true;
    }

    public function addDokumenWajib(): void
    {
        $trimmed = trim($this->newDokumenName);
        if ($trimmed !== '' && ! in_array($trimmed, $this->dokumen_wajib, true)) {
            $this->dokumen_wajib[] = $trimmed;
            $this->newDokumenName = '';
        }
    }

    public function removeDokumenWajib(int $index): void
    {
        if (isset($this->dokumen_wajib[$index])) {
            unset($this->dokumen_wajib[$index]);
            $this->dokumen_wajib = array_values($this->dokumen_wajib);
        }
    }

    public function setActive(int $id): void
    {
        PeriodeAnggaran::query()->update(['is_active' => false]);
        $periode = PeriodeAnggaran::findOrFail($id);
        $periode->update(['is_active' => true]);

        $this->feedbackMessage = "Periode {$periode->tahun} - {$periode->tahap} telah ditetapkan sebagai Periode Aktif.";
    }

    public function savePeriode(): void
    {
        $this->validate([
            'tahun' => 'required|integer|min:2020|max:2035',
            'tahap' => 'required|in:Tahap I,Tahap II',
            'bulan' => 'required|integer|min:1|max:12',
            'pagu_anggaran' => 'required|numeric|min:0',
            'target_realisasi_persen' => 'required|numeric|min:0|max:100',
            'tgl_tenggat_lapor' => 'required|date',
        ]);

        if ($this->is_active) {
            PeriodeAnggaran::query()->update(['is_active' => false]);
        }

        $data = [
            'tahun' => $this->tahun,
            'tahap' => $this->tahap,
            'bulan' => $this->bulan,
            'pagu_anggaran' => $this->pagu_anggaran,
            'target_realisasi_persen' => $this->target_realisasi_persen,
            'tgl_tenggat_lapor' => $this->tgl_tenggat_lapor,
            'dokumen_wajib_json' => $this->dokumen_wajib,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            $periode = PeriodeAnggaran::findOrFail($this->editingId);
            $periode->update($data);
            $this->feedbackMessage = "Periode {$periode->tahun} - {$periode->tahap} berhasil diperbarui.";
        } else {
            $periode = PeriodeAnggaran::create($data);
            $this->feedbackMessage = "Periode baru {$periode->tahun} - {$periode->tahap} berhasil ditambahkan.";
        }

        $this->showModal = false;
    }

    public function deletePeriode(int $id): void
    {
        $periode = PeriodeAnggaran::withCount('laporanAnggaran')->findOrFail($id);
        if ($periode->laporan_anggaran_count > 0) {
            $this->feedbackMessage = "Periode tidak dapat dihapus karena telah memiliki {$periode->laporan_anggaran_count} data laporan anggaran balai.";
            return;
        }

        $periode->delete();
        $this->feedbackMessage = "Periode anggaran berhasil dihapus.";
    }

    public function with(): array
    {
        $periodes = PeriodeAnggaran::withCount('laporanAnggaran')
            ->orderBy('tahun', 'desc')
            ->orderBy('tahap', 'asc')
            ->get();

        return [
            'periodes' => $periodes,
        ];
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    Master Pengaturan Sistem
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">DP2KBP3A Kab. Bangka</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-1">
                Master Periode Anggaran & Dokumen Wajib
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Kelola tahapan pencairan (Tahap I & Tahap II), pagu total, target serapan minimal, dan template berkas wajib pelaporan.
            </p>
        </div>

        <button type="button" wire:click="openCreateModal"
                class="px-4 py-2.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold shadow-theme-xs flex items-center gap-2 transition shrink-0">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Periode Anggaran</span>
        </button>
    </div>

    <!-- Feedback Message -->
    @if($feedbackMessage)
        <div class="p-4 rounded-xl bg-brand-50 border border-brand-200 text-brand-800 dark:bg-brand-500/10 dark:border-brand-500/30 dark:text-brand-300 flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm font-medium">
                <svg class="size-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ $feedbackMessage }}</span>
            </div>
            <button wire:click="$set('feedbackMessage', null)" class="text-brand-500 hover:text-brand-700">&times;</button>
        </div>
    @endif

    <!-- Table Periode Anggaran -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                    Daftar Periode Anggaran Aktif & Terdaftar
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Hanya ada satu periode berstatus <strong class="text-emerald-600 font-semibold">Aktif</strong> sebagai acuan monitoring harian.
                </p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500 dark:bg-gray-800/50 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4">Tahun & Tahap</th>
                        <th class="py-3.5 px-4">Pagu Total (8 Balai)</th>
                        <th class="py-3.5 px-4">Target Serapan</th>
                        <th class="py-3.5 px-4">Tenggat Pelaporan</th>
                        <th class="py-3.5 px-4">Dokumen Wajib</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($periodes as $index => $p)
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition {{ $p->is_active ? 'bg-brand-50/20 dark:bg-brand-500/5' : '' }}">
                            <td class="py-4 px-4 text-center font-mono text-xs text-gray-400">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span>{{ $p->tahun }}</span>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded {{ $p->tahap === 'Tahap I' ? 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300' }}">
                                        {{ $p->tahap }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    Bulan Ke-{{ $p->bulan }} • {{ $p->laporan_anggaran_count }} Laporan Masuk
                                </div>
                            </td>
                            <td class="py-4 px-4 font-mono font-semibold text-gray-800 dark:text-gray-200">
                                Rp {{ number_format((float) $p->pagu_anggaran, 0, ',', '.') }}
                                <div class="text-[11px] text-gray-400 font-sans">
                                    ~ Rp {{ number_format((float) $p->pagu_anggaran / 8, 0, ',', '.') }}/balai
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-mono font-bold text-brand-600 dark:text-brand-400">
                                    {{ $p->target_realisasi_persen }}%
                                </div>
                                <div class="text-[11px] text-gray-400">
                                    {{ $p->tahap === 'Tahap I' ? 'Syarat Tahap II' : 'Target Akhir Tahun' }}
                                </div>
                            </td>
                            <td class="py-4 px-4 text-xs font-mono">
                                <div class="text-gray-900 dark:text-gray-200 font-semibold">
                                    {{ Carbon::parse($p->tgl_tenggat_lapor)->format('d M Y H:i') }}
                                </div>
                                <div class="text-[11px] text-gray-400">
                                    {{ Carbon::parse($p->tgl_tenggat_lapor)->diffForHumans() }}
                                </div>
                            </td>
                            <td class="py-4 px-4 text-xs">
                                @php
                                    $docs = is_array($p->dokumen_wajib_json) ? $p->dokumen_wajib_json : [];
                                @endphp
                                <span class="font-mono font-bold text-gray-700 dark:text-gray-300">
                                    {{ count($docs) }} Berkas
                                </span>
                                <div class="text-[11px] text-gray-400 line-clamp-1" title="{{ implode(', ', $docs) }}">
                                    {{ implode(', ', array_slice($docs, 0, 2)) }}...
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                @if($p->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400">
                                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                @else
                                    <button type="button" wire:click="setActive({{ $p->id }})"
                                            class="text-xs px-2.5 py-1 rounded-full border border-gray-200 hover:bg-gray-100 text-gray-500 dark:border-gray-700 dark:hover:bg-gray-800 transition">
                                        Jadikan Aktif
                                    </button>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" wire:click="openEditModal({{ $p->id }})"
                                            class="p-1.5 text-gray-500 hover:text-brand-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                            title="Ubah Periode">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @if($p->laporan_anggaran_count === 0 && ! $p->is_active)
                                        <button type="button" wire:click="deletePeriode({{ $p->id }})"
                                                wire:confirm="Yakin ingin menghapus periode ini?"
                                                class="p-1.5 text-gray-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20 transition"
                                                title="Hapus Periode">
                                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-gray-400 text-xs">
                                Belum ada data periode anggaran.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form Tambah/Ubah Periode -->
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm">
            <div class="relative w-full max-w-[620px] rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $editingId ? 'Ubah Periode Anggaran' : 'Tambah Periode Anggaran Baru' }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Konfigurasi target dan template berkas wajib pelaporan anggaran.
                        </p>
                    </div>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-light">
                        &times;
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-4 flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tahun Anggaran</label>
                            <input type="number" wire:model="tahun" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono">
                            @error('tahun') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tahap Pencairan</label>
                            <select wire:model="tahap" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                <option value="Tahap I">Tahap I (Januari - Juni)</option>
                                <option value="Tahap II">Tahap II (Juli - Desember)</option>
                            </select>
                            @error('tahap') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Bulan Acuan (1-12)</label>
                            <input type="number" wire:model="bulan" min="1" max="12" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono">
                            @error('bulan') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Target Serapan Minimal (%)</label>
                            <input type="number" step="0.01" wire:model="target_realisasi_persen" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono">
                            @error('target_realisasi_persen') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Total Pagu Anggaran (8 Balai) (Rp)</label>
                            <input type="number" step="1000" wire:model="pagu_anggaran" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono">
                            @error('pagu_anggaran') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal & Waktu Tenggat Pelaporan (Deadline EWS 1)</label>
                            <input type="datetime-local" wire:model="tgl_tenggat_lapor" class="w-full px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono">
                            @error('tgl_tenggat_lapor') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:col-span-2 flex items-center gap-2 pt-2">
                            <input type="checkbox" id="setIsActiveCheckbox" wire:model="is_active" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            <label for="setIsActiveCheckbox" class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Jadikan sebagai Periode Aktif Utama saat ini
                            </label>
                        </div>
                    </div>

                    <!-- Checklist Dokumen Wajib Management -->
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 space-y-2">
                        <label class="block text-xs font-bold text-gray-900 dark:text-white">
                            Daftar Template Dokumen Pendukung Wajib
                        </label>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            Dokumen yang wajib diunggah oleh setiap Balai untuk memenuhi kualifikasi First-Time Right.
                        </p>

                        <div class="space-y-1.5">
                            @foreach($dokumen_wajib as $idx => $dok)
                                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-800/80 border border-gray-100 dark:border-gray-800 text-xs">
                                    <span class="text-gray-800 dark:text-gray-200">{{ $idx + 1 }}. {{ $dok }}</span>
                                    <button type="button" wire:click="removeDokumenWajib({{ $idx }})" class="text-rose-500 hover:text-rose-700 px-1.5 py-0.5 rounded">
                                        &times;
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="text" wire:model="newDokumenName" wire:keydown.enter.prevent="addDokumenWajib" placeholder="Ketik nama dokumen wajib baru..."
                                   class="flex-1 px-3 py-1.5 text-xs rounded-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <button type="button" wire:click="addDokumenWajib" class="px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 text-xs font-semibold transition">
                                Tambah Berkas
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2 bg-gray-50 dark:bg-gray-800/40">
                    <button type="button" wire:click="$set('showModal', false)" class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-700 transition">
                        Batal
                    </button>
                    <button type="button" wire:click="savePeriode" class="px-4 py-2 rounded-xl text-xs font-semibold bg-brand-500 hover:bg-brand-600 text-white shadow-theme-xs transition">
                        Simpan Periode
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
