<?php

use App\Models\PeriodeAnggaran;
use App\Services\EwsEngineService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('simadu:scan-ews', function (EwsEngineService $ewsService) {
    $this->info('Scanning EWS deadlines and overdue corrections...');
    $periode = PeriodeAnggaran::where('is_active', true)->first();
    if ($periode) {
        $alerts = $ewsService->scanSubmissionDeadlines($periode);
        $this->info('EWS 1 alerts generated/updated: '.count($alerts));
    }
    $escalated = $ewsService->scanOverdueCorrections();
    $this->info('EWS 3 escalated alerts: '.count($escalated));
})->purpose('Pindai tenggat pelaporan (EWS 1) dan eskalasi koreksi (EWS 3) SI MADU PELAWAN');

Schedule::command('simadu:scan-ews')->dailyAt('08:00');
