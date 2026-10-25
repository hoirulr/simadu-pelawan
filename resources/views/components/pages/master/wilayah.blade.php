<?php

declare(strict_types=1);

use App\Models\Desa;
use App\Models\Kecamatan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Master Data Kecamatan & Desa')] class extends Component
{
    public ?int $selectedKecamatanId = null;
    public string $search = '';

    public function with(): array
    {
        $kecamatans = Kecamatan::withCount('desas')->orderBy('id', 'asc')->get();

        $query = Desa::with('kecamatan');
        if ($this->selectedKecamatanId) {
            $query->where('kecamatan_id', $this->selectedKecamatanId);
        }
        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%");
            });
        }

        $desas = $query->orderBy('code', 'asc')->get();

        return [
            'kecamatans' => $kecamatans,
            'desas' => $desas,
            'totalDesa' => Desa::count(),
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
                    Master Data Wilayah
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">Kabupaten Bangka</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-1">
                Data Wilayah: 8 Kecamatan & 55 Desa/Kelurahan
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Pangkalan data wilayah binaan Balai Penyuluh KB di lingkungan DP2KBP3A Kabupaten Bangka.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300 shadow-theme-xs">
                Total: <strong class="text-brand-600 font-mono">8</strong> Kecamatan, <strong class="text-brand-600 font-mono">55</strong> Desa/Kelurahan
            </span>
        </div>
    </div>

    <!-- Kecamatan Filter Chips -->
    <div class="flex flex-wrap items-center gap-2">
        <button type="button" wire:click="$set('selectedKecamatanId', null)"
                class="px-3.5 py-1.5 rounded-full text-xs font-bold transition {{ $selectedKecamatanId === null ? 'bg-brand-500 text-white shadow-theme-xs' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
            Semua (55)
        </button>
        @foreach($kecamatans as $kec)
            <button type="button" wire:click="$set('selectedKecamatanId', {{ $kec->id }})"
                    class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition {{ $selectedKecamatanId === $kec->id ? 'bg-brand-500 text-white shadow-theme-xs' : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                {{ $kec->name }} <span class="font-mono text-[11px] opacity-75">({{ $kec->desas_count }})</span>
            </button>
        @endforeach
    </div>

    <!-- Search Input & Table -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-4">
            <div class="relative w-full max-w-sm">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama desa atau kode wilayah..."
                       class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 text-xs text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <svg class="size-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <span class="text-xs text-gray-400 font-mono">
                Menampilkan {{ $desas->count() }} data
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 text-[11px] uppercase tracking-wider text-gray-500 dark:bg-gray-800/50 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Kode Wilayah (Kemendagri)</th>
                        <th class="py-3 px-4">Nama Desa / Kelurahan</th>
                        <th class="py-3 px-4">Kecamatan</th>
                        <th class="py-3 px-4">Balai Penyuluh KB Pembina</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($desas as $index => $desa)
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition">
                            <td class="py-3 px-4 text-center font-mono text-xs text-gray-400">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3 px-4 font-mono text-xs font-bold text-brand-600 dark:text-brand-400">
                                {{ $desa->code }}
                            </td>
                            <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white text-xs">
                                {{ $desa->name }}
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <span class="font-mono text-gray-400 text-[11px]">{{ $desa->kecamatan?->code }}</span>
                                <span class="font-semibold text-gray-700 dark:text-gray-300 ml-1">{{ $desa->kecamatan?->name }}</span>
                            </td>
                            <td class="py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400">
                                Balai Penyuluh KB {{ $desa->kecamatan?->name }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>