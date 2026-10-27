<?php

use App\Models\PeriodeAnggaran;
use App\Services\EwsEngineService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('simadu:scan-ews', function (EwsEngineService $ewsService) {
    $this->info('Scanning EWS deadlines and overdue corrections...');

    try {
        $periode = PeriodeAnggaran::where('is_active', true)->first();
        $alertsCount = 0;

        if ($periode) {
            $alerts = $ewsService->scanSubmissionDeadlines($periode);
            $alertsCount = count($alerts);
            $this->info('EWS 1 alerts generated/updated: '.$alertsCount);
        }

        $escalated = $ewsService->scanOverdueCorrections();
        $escalatedCount = count($escalated);
        $this->info('EWS 3 escalated alerts: '.$escalatedCount);

        Log::info('Scheduler simadu:scan-ews executed successfully', [
            'active_periode_id' => $periode?->id,
            'alerts_count' => $alertsCount,
            'escalated_count' => $escalatedCount,
        ]);
    } catch (Throwable $e) {
        Log::error('Scheduler simadu:scan-ews failed with exception: '.$e->getMessage(), [
            'exception' => $e,
        ]);

        $this->error('EWS scan failed: '.$e->getMessage());

        return 1;
    }
})->purpose('Pindai tenggat pelaporan (EWS 1) dan eskalasi koreksi (EWS 3) SI MADU PELAWAN');

Schedule::command('simadu:scan-ews')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->onFailure(function () {
        Log::error('Schedule simadu:scan-ews failed during background execution.');
    });
