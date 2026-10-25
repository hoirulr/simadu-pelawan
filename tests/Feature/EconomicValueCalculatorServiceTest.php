<?php

use App\Models\BalaiPenyuluh;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\SkoringRisiko;
use App\Models\TindakLanjutMonitoring;
use App\Models\User;
use App\Services\EconomicValueCalculatorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('economic value calculator computes 5 indicators correctly', function () {
    $periode = PeriodeAnggaran::first();
    $balai = BalaiPenyuluh::first();
    $user = User::first();

    // Buat laporan tepat waktu & first time right
    $laporan = LaporanAnggaran::create([
        'balai_id' => $balai->id,
        'periode_id' => $periode->id,
        'user_id' => $user->id,
        'nomor_laporan' => 'LAP-TEST-ECON-001',
        'pagu_alokasi' => 100000000,
        'realisasi_anggaran' => 50000000,
        'persentase_serapan' => 50.0,
        'sisa_anggaran' => 50000000,
        'status_laporan' => 'verified',
        'tgl_submit_pertama' => Carbon::now()->subDays(2),
        'lengkap_pengiriman_pertama' => true,
    ]);

    // Skoring risiko dibuat 2 jam setelah submit
    SkoringRisiko::create([
        'laporan_id' => $laporan->id,
        'penilai_id' => $user->id,
        'skor_p1' => 0,
        'skor_p2' => 0,
        'skor_p3' => 0,
        'skor_p4' => 0,
        'skor_p5' => 0,
        'total_skor' => 0,
        'kategori_risiko' => 'Rendah',
        'created_at' => Carbon::now()->subDays(2)->addHours(2),
    ]);

    // Tiket perbaikan yang diselesaikan
    TindakLanjutMonitoring::create([
        'laporan_id' => $laporan->id,
        'instruksi_perbaikan' => 'Lengkapi rincian kuitansi',
        'status' => 'Verified',
        'waktu_mulai_temuan' => Carbon::now()->subDays(2),
        'waktu_selesai_verifikasi' => Carbon::now()->subDays(1), // 24 jam
        'verified_by' => $user->id,
    ]);

    $service = new EconomicValueCalculatorService;
    $stats = $service->calculate($periode->id);

    expect($stats)->toBeArray();
    expect($stats['totalLaporan'])->toBeGreaterThan(0);
    expect($stats['firstTimeRightPercentage'])->toBeGreaterThan(0);
    expect($stats['onTimePercentage'])->toBeGreaterThan(0);
    expect($stats['avgResolutionHours'])->toBeGreaterThan(0);
});
