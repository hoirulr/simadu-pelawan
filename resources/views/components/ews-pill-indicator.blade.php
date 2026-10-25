@props([
    'ewsAlerts' => collect(),
])

@php
    $ews1 = $ewsAlerts->firstWhere('kode_ews.value', 'EWS_1') ?? $ewsAlerts->firstWhere('kode_ews', 'EWS_1');
    $ews2 = $ewsAlerts->firstWhere('kode_ews.value', 'EWS_2') ?? $ewsAlerts->firstWhere('kode_ews', 'EWS_2');
    $ews3 = $ewsAlerts->firstWhere('kode_ews.value', 'EWS_3') ?? $ewsAlerts->firstWhere('kode_ews', 'EWS_3');

    $getPillClass = function($alert) {
        if (! $alert || $alert->status_ews === \App\Enums\EwsStatus::Resolved || (is_string($alert->status_ews) && $alert->status_ews === 'Resolved')) {
            return 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500 border-gray-200 dark:border-gray-700';
        }
        $level = is_object($alert->level_ews) ? $alert->level_ews->value : $alert->level_ews;
        $status = is_object($alert->status_ews) ? $alert->status_ews->value : $alert->status_ews;

        if ($status === 'Escalated' || $level === 'Tinggi') {
            return 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-400 border-rose-300 dark:border-rose-500/30 animate-pulse font-bold';
        }
        return 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400 border-amber-300 dark:border-amber-500/30 font-semibold';
    };
@endphp

<div class="inline-flex items-center gap-1.5">
    <!-- EWS 1: Ketepatan Waktu -->
    <span title="{{ $ews1 ? $ews1->kondisi_pemicu : 'EWS 1: Ketepatan Waktu Pelaporan (Aman)' }}"
          class="px-2 py-0.5 rounded text-[11px] font-mono border cursor-help {{ $getPillClass($ews1) }}">
        EWS-1
    </span>

    <!-- EWS 2: Kelengkapan Dokumen -->
    <span title="{{ $ews2 ? $ews2->kondisi_pemicu : 'EWS 2: Kelengkapan & Validitas Data (Aman)' }}"
          class="px-2 py-0.5 rounded text-[11px] font-mono border cursor-help {{ $getPillClass($ews2) }}">
        EWS-2
    </span>

    <!-- EWS 3: Tindak Lanjut -->
    <span title="{{ $ews3 ? $ews3->kondisi_pemicu : 'EWS 3: Tindak Lanjut Monitoring (Aman)' }}"
          class="px-2 py-0.5 rounded text-[11px] font-mono border cursor-help {{ $getPillClass($ews3) }}">
        EWS-3
    </span>
</div>
