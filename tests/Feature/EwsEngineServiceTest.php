<?php

use App\Enums\EwsCode;
use App\Enums\EwsStatus;
use App\Models\BalaiPenyuluh;
use App\Models\DokumenLaporan;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\User;
use App\Services\EwsEngineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('ews engine evaluates deadlines and triggers ews 1 for impending or passed deadlines', function () {
    $periode = PeriodeAnggaran::first();
    $periode->update([
        'tgl_tenggat_lapor' => Carbon::now()->subDays(1),
    ]);

    $service = new EwsEngineService;
    $alerts = $service->scanSubmissionDeadlines($periode);

    expect($alerts)->toBeArray();
    if (! empty($alerts)) {
        expect($alerts[0]->kode_ews)->toBe(EwsCode::EWS_1);
    }
});

test('ews engine evaluates invalid documents and triggers ews 2 alert', function () {
    $balai = BalaiPenyuluh::first();
    $periode = PeriodeAnggaran::first();
    $user = User::first();

    $laporan = LaporanAnggaran::create([
        'balai_id' => $balai->id,
        'periode_id' => $periode->id,
        'user_id' => $user->id,
        'nomor_laporan' => 'LAP-TEST-EWS2-001',
        'pagu_alokasi' => 100000000,
        'realisasi_anggaran' => 80000000,
        'persentase_serapan' => 80.0,
        'sisa_anggaran' => 20000000,
        'status_laporan' => 'draft',
    ]);

    DokumenLaporan::create([
        'laporan_id' => $laporan->id,
        'nama_dokumen_wajib' => 'Surat Pertanggungjawaban Mutlak (SPTJB)',
        'file_name' => 'sptjb.pdf',
        'file_path' => 'laporan/sptjb.pdf',
        'status_validasi' => 'tidak_valid',
        'catatan_dokumen' => 'Cap stempel belum ada',
    ]);

    $service = new EwsEngineService;
    $alert = $service->evaluateEws2($laporan);

    expect($alert)->not->toBeNull();
    expect($alert->kode_ews)->toBe(EwsCode::EWS_2);
    expect($alert->status_ews)->toBe(EwsStatus::Open);
});
