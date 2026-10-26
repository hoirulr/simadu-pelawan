<?php

declare(strict_types=1);

use App\Actions\SaveRiskAssessmentAction;
use App\Enums\EwsStatus;
use App\Enums\ReportStatus;
use App\Enums\RiskCategory;
use App\Models\BalaiPenyuluh;
use App\Models\DokumenLaporan;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\User;
use App\Services\RiskScoringService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->admin = User::where('role', 'admin')->first();
    $this->balai = BalaiPenyuluh::first();
    $this->operator = User::where('role', 'operator_balai')->where('balai_id', $this->balai->id)->first();
    $this->periode = PeriodeAnggaran::where('is_active', true)->first();

    $this->laporan = LaporanAnggaran::firstOrCreate(
        [
            'balai_id' => $this->balai->id,
            'periode_id' => $this->periode->id,
        ],
        [
            'rencana_anggaran' => 100000000,
            'realisasi_anggaran' => 85000000,
            'persentase_serapan' => 85.00,
            'status_laporan' => ReportStatus::Submitted,
            'tgl_submit_pertama' => Carbon::now(),
        ]
    );
});

test('skor parameter di-clamp antara 0 dan 3 dan kategori risiko dihitung akurat', function () {
    $service = app(RiskScoringService::class);

    // Test batas bawah (0)
    $scoreZero = $service->saveScore($this->laporan, -1, 0, 0, 0, -5, $this->admin->id);
    expect($scoreZero->skor_p1)->toBe(0)
        ->and($scoreZero->skor_p5)->toBe(0)
        ->and($scoreZero->total_skor)->toBe(0)
        ->and($scoreZero->kategori_risiko)->toBe(RiskCategory::Rendah);

    // Test batas atas Rendah (4)
    $scoreFour = $service->saveScore($this->laporan, 1, 1, 1, 1, 0, $this->admin->id);
    expect($scoreFour->total_skor)->toBe(4)
        ->and($scoreFour->kategori_risiko)->toBe(RiskCategory::Rendah);

    // Test batas bawah Sedang (5)
    $scoreFive = $service->saveScore($this->laporan, 1, 1, 1, 1, 1, $this->admin->id);
    expect($scoreFive->total_skor)->toBe(5)
        ->and($scoreFive->kategori_risiko)->toBe(RiskCategory::Sedang);

    // Test batas atas Sedang (9)
    $scoreNine = $service->saveScore($this->laporan, 2, 2, 2, 2, 1, $this->admin->id);
    expect($scoreNine->total_skor)->toBe(9)
        ->and($scoreNine->kategori_risiko)->toBe(RiskCategory::Sedang);

    // Test batas bawah Tinggi (10)
    $scoreTen = $service->saveScore($this->laporan, 2, 2, 2, 2, 2, $this->admin->id);
    expect($scoreTen->total_skor)->toBe(10)
        ->and($scoreTen->kategori_risiko)->toBe(RiskCategory::Tinggi);

    // Test batas atas Tinggi dan clamping nilai berlebih (15)
    $scoreMax = $service->saveScore($this->laporan, 5, 10, 4, 3, 9, $this->admin->id);
    expect($scoreMax->skor_p1)->toBe(3)
        ->and($scoreMax->skor_p2)->toBe(3)
        ->and($scoreMax->skor_p3)->toBe(3)
        ->and($scoreMax->skor_p4)->toBe(3)
        ->and($scoreMax->skor_p5)->toBe(3)
        ->and($scoreMax->total_skor)->toBe(15)
        ->and($scoreMax->kategori_risiko)->toBe(RiskCategory::Tinggi);
});

test('perhitungan P2 memperlakukan dokumen kosong, kurang, dan tidak valid dengan tepat', function () {
    $service = app(RiskScoringService::class);

    // Kasus 1: Laporan tanpa dokumen sama sekali -> P2 = 3
    $this->laporan->dokumenLaporan()->delete();
    expect($service->calculateP2($this->laporan))->toBe(3);

    // Kasus 2: Semua 5 dokumen lengkap -> P2 = 0
    foreach (range(1, 5) as $i) {
        DokumenLaporan::create([
            'laporan_id' => $this->laporan->id,
            'nama_dokumen_wajib' => "Dokumen {$i}",
            'status_validasi' => 'lengkap',
        ]);
    }
    $this->laporan->load('dokumenLaporan');
    expect($service->calculateP2($this->laporan))->toBe(0);

    // Kasus 3: 1 dokumen kurang -> P2 = 1
    $firstDoc = $this->laporan->dokumenLaporan->first();
    $firstDoc->update(['status_validasi' => 'kurang']);
    $this->laporan->load('dokumenLaporan');
    expect($service->calculateP2($this->laporan))->toBe(1);

    // Kasus 4: 2 dokumen kurang -> P2 = 2
    $secondDoc = $this->laporan->dokumenLaporan->skip(1)->first();
    $secondDoc->update(['status_validasi' => 'kurang']);
    $this->laporan->load('dokumenLaporan');
    expect($service->calculateP2($this->laporan))->toBe(2);

    // Kasus 5: 1 dokumen tidak valid -> P2 = 3
    $thirdDoc = $this->laporan->dokumenLaporan->skip(2)->first();
    $thirdDoc->update(['status_validasi' => 'tidak_valid']);
    $this->laporan->load('dokumenLaporan');
    expect($service->calculateP2($this->laporan))->toBe(3);
});

test('perhitungan P1 menghitung ketepatan waktu berdasarkan selisih hari kalender', function () {
    $service = app(RiskScoringService::class);
    $deadline = Carbon::parse('2026-10-20 23:59:59');

    $this->laporan->periodeAnggaran->update([
        'tgl_tenggat_lapor' => $deadline,
    ]);
    $this->laporan->load('periodeAnggaran');

    // Tepat waktu (submit sebelum atau pada deadline) -> P1 = 0
    $this->laporan->update(['tgl_submit_pertama' => '2026-10-20 10:00:00']);
    expect($service->calculateP1($this->laporan))->toBe(0);

    // Terlambat 1 hari (submit 21 Okt) -> P1 = 1
    $this->laporan->update(['tgl_submit_pertama' => '2026-10-21 23:59:59']);
    expect($service->calculateP1($this->laporan))->toBe(1);

    // Terlambat 4 hari (submit 24 Okt) -> P1 = 2
    $this->laporan->update(['tgl_submit_pertama' => '2026-10-24 23:59:59']);
    expect($service->calculateP1($this->laporan))->toBe(2);

    // Terlambat 7 hari (submit 27 Okt) -> P1 = 3
    $this->laporan->update(['tgl_submit_pertama' => '2026-10-27 23:59:59']);
    expect($service->calculateP1($this->laporan))->toBe(3);
});

test('SaveRiskAssessmentAction memvalidasi otorisasi dan transisi status laporan serta EWS', function () {
    $action = app(SaveRiskAssessmentAction::class);

    // 1. Non-admin tidak boleh melakukan skoring
    expect(fn () => $action->execute(
        $this->laporan,
        1, 1, 1, 1, 0,
        'Catatan',
        'EWS_2',
        'Kepala Balai',
        null,
        3,
        $this->operator
    ))->toThrow(AuthorizationException::class);

    // 2. Admin skoring Risiko Rendah (Skor 3) tanpa instruksi -> Laporan menjadi Verified
    $resultLow = $action->execute(
        $this->laporan,
        1, 1, 1, 0, 0,
        'Laporan rapi',
        'EWS_2',
        'Kepala Balai',
        null,
        3,
        $this->admin
    );

    expect($resultLow['skoring']->total_skor)->toBe(3)
        ->and($resultLow['laporan']->status_laporan)->toBe(ReportStatus::Verified)
        ->and($resultLow['alert'])->toBeNull()
        ->and($resultLow['ticket'])->toBeNull();

    // 3. Admin skoring Risiko Sedang (Skor 6) -> Laporan menjadi Revisi, terbit EwsAlert Open
    $resultMed = $action->execute(
        $this->laporan,
        2, 1, 1, 1, 1,
        'Perlu perbaikan',
        'EWS_2',
        'Kepala Balai',
        'Lengkapi kuitansi pembelian ATK',
        3,
        $this->admin
    );

    expect($resultMed['skoring']->total_skor)->toBe(6)
        ->and($resultMed['laporan']->status_laporan)->toBe(ReportStatus::Revisi)
        ->and($resultMed['alert'])->not->toBeNull()
        ->and($resultMed['alert']->status_ews)->toBe(EwsStatus::Open)
        ->and($resultMed['ticket'])->not->toBeNull()
        ->and($resultMed['ticket']->status)->toBe('Pending')
        ->and($resultMed['ticket']->verified_by)->toBe($this->admin->id);

    // 4. Admin skoring Risiko Tinggi (Skor 12) -> Laporan Revisi, EWS Escalated
    $resultHigh = $action->execute(
        $this->laporan,
        3, 3, 2, 2, 2,
        'Penyimpangan berat',
        'EWS_2',
        'Kepala Balai',
        'Audit fisik balai dan perbaiki seluruh SPJ',
        5,
        $this->admin
    );

    expect($resultHigh['skoring']->total_skor)->toBe(12)
        ->and($resultHigh['alert']->status_ews)->toBe(EwsStatus::Escalated);
});
