<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\TindakLanjutMonitoring;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
});

// =============================================================================
// P0.1: Switch User Privilege Escalation Prevention & Audit Log
// =============================================================================

test('guest cannot access switch user endpoint', function (): void {
    $admin = User::where('role', UserRole::Admin)->first();

    $response = $this->post("/switch-user/{$admin->id}");
    $response->assertRedirect('/');
});

test('operator balai cannot access switch user endpoint via GET or POST and receives 403', function (): void {
    $operator = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();
    $admin = User::where('role', UserRole::Admin)->first();

    // GET should be 404 or 405
    $getResponse = $this->actingAs($operator)->get("/switch-user/{$admin->id}");
    expect($getResponse->status())->toBeIn([404, 405]);

    // POST should be 403 Forbidden because route is protected by role:admin
    $postResponse = $this->actingAs($operator)->post("/switch-user/{$admin->id}");
    $postResponse->assertForbidden();

    // Verify operator did not become admin
    expect(Auth::id())->toBe($operator->id);
});

test('admin can switch user in non-production via POST and action is logged', function (): void {
    Log::spy();

    $admin = User::where('role', UserRole::Admin)->first();
    $targetOperator = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();

    $response = $this->actingAs($admin)->post("/switch-user/{$targetOperator->id}");

    $response->assertRedirect(route('balai.report'));
    expect(Auth::id())->toBe($targetOperator->id);

    Log::shouldHaveReceived('info')->withArgs(function ($message, $context) use ($admin, $targetOperator): bool {
        return str_contains($message, 'switch user')
            && $context['actor_id'] === $admin->id
            && $context['target_id'] === $targetOperator->id;
    })->once();
});

test('switch user is blocked in production environment', function (): void {
    $admin = User::where('role', UserRole::Admin)->first();
    $targetOperator = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();

    // Simulate production environment
    app()->detectEnvironment(fn () => 'production');

    $token = 'test-csrf-token-xyz';
    $response = $this->actingAs($admin)
        ->withSession(['_token' => $token])
        ->post("/switch-user/{$targetOperator->id}", ['_token' => $token]);

    $response->assertForbidden();
});

// =============================================================================
// P0.2 & P0.3: Balai Data Isolation & Server-Side Budget Validation
// =============================================================================

test('negative and non-numeric budget inputs are rejected by server validation', function (): void {
    $operator = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();

    Livewire::actingAs($operator)
        ->test('pages.balai.report-submission')
        ->set('rencanaAnggaran', -500000)
        ->set('realisasiAnggaran', -100000)
        ->call('submitReport')
        ->assertHasErrors([
            'rencanaAnggaran' => 'min',
            'realisasiAnggaran' => 'min',
        ]);
});

test('invalid periode_id is rejected by server validation', function (): void {
    $operator = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();

    Livewire::actingAs($operator)
        ->test('pages.balai.report-submission')
        ->set('periodeId', 999999)
        ->set('rencanaAnggaran', 100000000)
        ->set('realisasiAnggaran', 50000000)
        ->call('submitReport')
        ->assertHasErrors(['periodeId' => 'exists']);
});

test('operator balai cannot submit report for another balai', function (): void {
    // Sungailiat operator has balai_id = 1
    $operatorSungailiat = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();

    // Attempting to submit for Merawang (balai_id = 2)
    Livewire::actingAs($operatorSungailiat)
        ->test('pages.balai.report-submission')
        ->set('balaiId', 2)
        ->set('rencanaAnggaran', 100000000)
        ->set('realisasiAnggaran', 50000000)
        ->call('submitReport');

    // Verification: record for balai_id 2 should not have been created by Sungailiat
    $laporanBalai2 = LaporanAnggaran::where('balai_id', 2)->first();
    expect($laporanBalai2)->toBeNull();

    // Instead, it should enforce the operator's own balai_id = 1
    $laporanBalai1 = LaporanAnggaran::where('balai_id', 1)->first();
    expect($laporanBalai1)->not->toBeNull();
    expect((float) $laporanBalai1->rencana_anggaran)->toBe(100000000.0);
});

test('first submission timestamp tgl_submit_pertama does not change on resubmission', function (): void {
    $operator = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();
    $periode = PeriodeAnggaran::where('is_active', true)->first();

    $initialTime = Carbon::now()->subDays(3);

    $laporan = LaporanAnggaran::create([
        'balai_id' => $operator->balai_id,
        'periode_id' => $periode->id,
        'rencana_anggaran' => 100000000,
        'realisasiAnggaran' => 50000000,
        'persentase_serapan' => 50.0,
        'tgl_submit_pertama' => $initialTime,
        'lengkap_pengiriman_pertama' => false,
        'status_laporan' => ReportStatus::Submitted,
    ]);

    Livewire::actingAs($operator)
        ->test('pages.balai.report-submission')
        ->set('realisasiAnggaran', 75000000)
        ->call('submitReport')
        ->assertHasNoErrors();

    $laporan->refresh();
    expect((float) $laporan->realisasi_anggaran)->toBe(75000000.0)
        ->and($laporan->tgl_submit_pertama->toIso8601String())->toBe($initialTime->toIso8601String());
});

test('operator cannot view or respond to tickets belonging to another balai', function (): void {
    $operatorSungailiat = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();
    $periode = PeriodeAnggaran::where('is_active', true)->first();

    // Create Laporan and Ticket for Balai Merawang (balai_id = 2)
    $laporanMerawang = LaporanAnggaran::create([
        'balai_id' => 2,
        'periode_id' => $periode->id,
        'rencana_anggaran' => 100000000,
        'realisasi_anggaran' => 40000000,
        'persentase_serapan' => 40.0,
        'status_laporan' => ReportStatus::Submitted,
    ]);

    $ticketMerawang = TindakLanjutMonitoring::create([
        'laporan_id' => $laporanMerawang->id,
        'instruksi_perbaikan' => 'Lengkapi SPTJM Balai Merawang',
        'waktu_mulai_temuan' => now(),
        'status' => 'Pending',
    ]);

    // Sungailiat operator attempting to open modal or respond to Merawang ticket
    Livewire::actingAs($operatorSungailiat)
        ->test('pages.monitoring.followup-economic')
        ->call('openResponseModal', $ticketMerawang->id)
        ->assertForbidden();

    Livewire::actingAs($operatorSungailiat)
        ->test('pages.monitoring.followup-economic')
        ->set('selectedTicketId', $ticketMerawang->id)
        ->set('catatanBalai', 'Catatan tidak sah dari balai lain')
        ->call('submitBalaiResponse')
        ->assertForbidden();
});

// =============================================================================
// P0.5: Production Hardening
// =============================================================================

test('quick login demo presets are disabled in production environment', function (): void {
    app()->detectEnvironment(fn () => 'production');

    Livewire::test('pages.auth.login')
        ->call('quickLogin', 'admin@simadupelawan.go.id', 'password')
        ->assertForbidden();

    expect(Auth::check())->toBeFalse();
});
