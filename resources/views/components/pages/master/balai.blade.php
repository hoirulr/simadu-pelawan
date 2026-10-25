<?php

declare(strict_types=1);

use App\Models\BalaiPenyuluh;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Master Data Balai Penyuluh KB')] class extends Component
{
    public function with(): array
    {
        $balaiList = BalaiPenyuluh::with('kecamatanModel.desas')->orderBy('id', 'asc')->get();

        return [
            'balaiList' => $balaiList,
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
                    Master Data Balai
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-mono text-gray-500 dark:text-gray-400">DP2KBP3A Kab. Bangka</span>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-1">
                8 Balai Penyuluh KB Kabupaten Bangka
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Daftar unit pelaksana penyuluhan KB di 8 kecamatan beserta desa binaan masing-masing.
            </p>
        </div>
    </div>

    <!-- 8 Balai Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
        @foreach($balaiList as $balai)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                            {{ $balai->kode_balai }}
                        </span>
                        <span class="text-xs text-gray-400 font-mono">
                            {{ $balai->kecamatanModel?->desas?->count() ?? 0 }} Desa
                        </span>
                    </div>

                    <h3 class="text-base font-bold text-gray-900 dark:text-white mt-3">
                        {{ $balai->nama_balai }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Kecamatan {{ $balai->kecamatan }}
                    </p>

                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between text-gray-500">
                            <span>Kepala Balai:</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $balai->kepala_balai }}</span>
                        </div>
                        <div class="flex items-center justify-between text-gray-500">
                            <span>Kontak:</span>
                            <span class="font-mono text-gray-700 dark:text-gray-300">{{ $balai->kontak }}</span>
                        </div>
                        <div class="text-[11px] text-gray-400 pt-1">
                            {{ $balai->alamat }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <a href="/admin/workspace?balai_id={{ $balai->id }}"
                       class="flex-1 py-1.5 text-center text-xs font-semibold rounded-lg bg-gray-100 hover:bg-brand-50 hover:text-brand-600 dark:bg-gray-800 dark:text-gray-300 transition">
                        Meja Kerja &rarr;
                    </a>
                    <a href="/balai/laporan?balai_id={{ $balai->id }}"
                       class="flex-1 py-1.5 text-center text-xs font-semibold rounded-lg bg-brand-500 hover:bg-brand-600 text-white transition">
                        Input Laporan
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>