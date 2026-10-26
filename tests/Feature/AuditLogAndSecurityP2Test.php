<?php

use App\Actions\SaveRiskAssessmentAction;
use App\Actions\SubmitLaporanAnggaranAction;
use App\Actions\SubmitTindakLanjutAction;
use App\Actions\UploadDokumenLaporanAction;
use App\Actions\VerifyTindakLanjutAction;
use App\Enums\EwsCode;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\BalaiPenyuluh;
use App\Models\DokumenLaporan;
use App\Models\EwsAlert;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('successful and failed login events are recorded in audit logs', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    // 1. Failed login
    Livewire::test('pages.auth.login')
        ->set('email', $admin->email)
        ->set('password', 'wrong-pass')
        ->call('authenticate');

    $failedLog = AuditLog::where('action', 'auth.login_failed')->first();
    expect($failedLog)->not->toBeNull()
        ->and($failedLog->new_values['attempted_email'])->toBe($admin->email);

    // 2. Successful login
    Livewire::test('pages.auth.login')
        ->set('email', $admin->email)
        ->set('password', 'password')
        ->call('authenticate');

    $successLog = AuditLog::where('action', 'auth.login')->first();
    expect($successLog)->not->toBeNull()
        ->and($successLog->actor_id)->toBe($admin->id);
});

test('logout event is recorded in audit logs and session is destroyed', function () {
    $admin = User::where('role', UserRole::Admin)->first();

    $this->actingAs($admin)->post('/logout');

    $logoutLog = AuditLog::where('action', 'auth.logout')->first();
    expect($logoutLog)->not->toBeNull()
        ->and($logoutLog->actor_id)->toBe($admin->id);
});

test('report submission is recorded in audit logs', function () {
    $operator = User::where('role', UserRole::OperatorBalai)->first();
    $periode = PeriodeAnggaran::first();

    $action = app(SubmitLaporanAnggaranAction::class);
    $laporan = $action->execute(
        balaiId: $operator->balai_id,
        periodeId: $periode->id,
        rencanaAnggaran: 100000000.0,
        realisasiAnggaran: 85000000.0,
        catatanBalai: 'Laporan pengajuan tahap I',
        actor: $operator
    );

    $audit = AuditLog::where('action', 'laporan.submit')
        ->where('subject_type', LaporanAnggaran::class)
        ->where('subject_id', $laporan->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($operator->id)
        ->and($audit->new_values['persentase_serapan'])->toEqual(85);
});

test('document upload, download, and deletion are recorded in audit logs', function () {
    Storage::fake('local');
    $operator = User::where('role', UserRole::OperatorBalai)->first();
    $periode = PeriodeAnggaran::first();

    $laporan = LaporanAnggaran::create([
        'balai_id' => $operator->balai_id,
        'periode_id' => $periode->id,
        'rencana_anggaran' => 100000000.0,
        'realisasi_anggaran' => 80000000.0,
        'status_laporan' => ReportStatus::Draft,
    ]);

    $dokumen = DokumenLaporan::create([
        'laporan_id' => $laporan->id,
        'nama_dokumen_wajib' => 'Laporan Realisasi Anggaran (LRA)',
        'status_validasi' => 'kurang',
    ]);

    $fakeFile = UploadedFile::fake()->create('lra_dokumen.pdf', 500, 'application/pdf');

    // 1. Upload
    $uploadAction = app(UploadDokumenLaporanAction::class);
    $uploadAction->execute($dokumen, $fakeFile, $operator);

    $uploadAudit = AuditLog::where('action', 'dokumen.upload')
        ->where('subject_type', DokumenLaporan::class)
        ->where('subject_id', $dokumen->id)
        ->first();

    expect($uploadAudit)->not->toBeNull()
        ->and($uploadAudit->actor_id)->toBe($operator->id)
        ->and($uploadAudit->new_values['file_name'])->toBe('lra_dokumen.pdf');

    // 2. Download
    $this->actingAs($operator)->get(route('dokumen.download', $dokumen))->assertOk();

    $downloadAudit = AuditLog::where('action', 'dokumen.download')
        ->where('subject_type', DokumenLaporan::class)
        ->where('subject_id', $dokumen->id)
        ->first();

    expect($downloadAudit)->not->toBeNull()
        ->and($downloadAudit->actor_id)->toBe($operator->id);

    // 3. Deletion via report-submission component
    Livewire::actingAs($operator)
        ->test('pages.balai.report-submission')
        ->set('balaiId', $operator->balai_id)
        ->set('periodeId', $periode->id)
        ->call('deleteDokumen', $dokumen->id);

    $deleteAudit = AuditLog::where('action', 'dokumen.delete')
        ->where('subject_type', DokumenLaporan::class)
        ->where('subject_id', $dokumen->id)
        ->first();

    expect($deleteAudit)->not->toBeNull()
        ->and($deleteAudit->actor_id)->toBe($operator->id);
});

test('risk scoring and ticket follow-up workflow record audit logs', function () {
    Storage::fake('local');
    $admin = User::where('role', UserRole::Admin)->first();
    $operator = User::where('role', UserRole::OperatorBalai)->first();
    $periode = PeriodeAnggaran::first();

    $laporan = LaporanAnggaran::create([
        'balai_id' => $operator->balai_id,
        'periode_id' => $periode->id,
        'rencana_anggaran' => 100000000.0,
        'realisasi_anggaran' => 70000000.0,
        'status_laporan' => ReportStatus::Submitted,
    ]);

    // 1. Skoring Risiko oleh Admin
    $scoreAction = app(SaveRiskAssessmentAction::class);
    $result = $scoreAction->execute(
        laporan: $laporan,
        p1: 2,
        p2: 2,
        p3: 1,
        p4: 1,
        p5: 1,
        catatanPenilai: 'Catatan deviasi anggaran',
        selectedEwsCode: 'EWS_2',
        penanggungJawab: 'Kepala Balai Sungailiat',
        instruksiPerbaikan: 'Lengkapi bukti SPJ kuitansi',
        hariBatasKoreksi: 3,
        admin: $admin
    );

    $scoreAudit = AuditLog::where('action', 'skoring.save')->first();
    expect($scoreAudit)->not->toBeNull()
        ->and($scoreAudit->actor_id)->toBe($admin->id)
        ->and($scoreAudit->new_values['total_skor'])->toBe(7);

    $ticket = $result['ticket'];
    expect($ticket)->not->toBeNull();

    // 2. Submit Bukti Koreksi Tiket oleh Operator
    $evidenceFile = UploadedFile::fake()->create('bukti_revisi.pdf', 300, 'application/pdf');
    $submitTicketAction = app(SubmitTindakLanjutAction::class);
    $submitTicketAction->execute(
        ticket: $ticket,
        catatanBalai: 'Berikut bukti revisi kami lampirkan.',
        fileBukti: $evidenceFile,
        actor: $operator
    );

    $ticketSubmitAudit = AuditLog::where('action', 'tiket.submit')
        ->where('subject_id', $ticket->id)
        ->first();

    expect($ticketSubmitAudit)->not->toBeNull()
        ->and($ticketSubmitAudit->actor_id)->toBe($operator->id);

    // 3. Download Bukti Koreksi
    $this->actingAs($admin)->get(route('ticket.download-bukti', $ticket))->assertOk();
    $downloadBuktiAudit = AuditLog::where('action', 'tiket.download_bukti')
        ->where('subject_id', $ticket->id)
        ->first();
    expect($downloadBuktiAudit)->not->toBeNull();

    // 4. Verifikasi Tiket oleh Admin
    $verifyTicketAction = app(VerifyTindakLanjutAction::class);
    $verifyTicketAction->execute(
        ticket: $ticket,
        admin: $admin
    );

    $ticketVerifyAudit = AuditLog::where('action', 'tiket.verify')
        ->where('subject_id', $ticket->id)
        ->first();

    expect($ticketVerifyAudit)->not->toBeNull()
        ->and($ticketVerifyAudit->actor_id)->toBe($admin->id);
});

test('user management creates, updates, and deletes are recorded in audit logs', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $balai = BalaiPenyuluh::first();

    // 1. Create User
    Livewire::actingAs($admin)
        ->test('pages.master.users')
        ->set('name', 'Operator Baru Belinyu')
        ->set('email', 'operator.baru@simadupelawan.go.id')
        ->set('role', 'operator_balai')
        ->set('balai_id', $balai->id)
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('saveUser');

    $createdUser = User::where('email', 'operator.baru@simadupelawan.go.id')->first();
    expect($createdUser)->not->toBeNull();

    $createAudit = AuditLog::where('action', 'user.create')
        ->where('subject_id', $createdUser->id)
        ->first();
    expect($createAudit)->not->toBeNull()
        ->and($createAudit->actor_id)->toBe($admin->id);

    // 2. Update User
    Livewire::actingAs($admin)
        ->test('pages.master.users')
        ->set('editingUserId', $createdUser->id)
        ->set('name', 'Operator Belinyu Terupdate')
        ->set('email', 'operator.baru@simadupelawan.go.id')
        ->set('role', 'operator_balai')
        ->set('balai_id', $balai->id)
        ->call('saveUser');

    $updateAudit = AuditLog::where('action', 'user.update')
        ->where('subject_id', $createdUser->id)
        ->first();
    expect($updateAudit)->not->toBeNull()
        ->and($updateAudit->new_values['name'])->toBe('Operator Belinyu Terupdate');

    // 3. Delete User
    Livewire::actingAs($admin)
        ->test('pages.master.users')
        ->call('deleteUser', $createdUser->id);

    $deleteAudit = AuditLog::where('action', 'user.delete')
        ->where('subject_id', $createdUser->id)
        ->first();
    expect($deleteAudit)->not->toBeNull();
});

test('user impersonation switch-user records audit log', function () {
    $admin = User::where('role', UserRole::Admin)->first();
    $operator = User::where('role', UserRole::OperatorBalai)->first();

    $this->actingAs($admin)
        ->post("/switch-user/{$operator->id}")
        ->assertRedirect('/balai/laporan');

    $audit = AuditLog::where('action', 'user.impersonate')
        ->where('subject_id', $operator->id)
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($admin->id);
});

test('security headers are present on responses for P2.4 compliance', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-XSS-Protection', '1; mode=block');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
});

test('scheduler simadu:scan-ews is idempotent and does not create duplicate alerts', function () {
    $periode = PeriodeAnggaran::first();
    $periode->update([
        'is_active' => true,
        'tgl_tenggat_lapor' => Carbon::now()->addDays(2), // H-2 -> triggers EWS 1
    ]);

    $operator = User::where('role', UserRole::OperatorBalai)->first();
    $laporan = LaporanAnggaran::create([
        'balai_id' => $operator->balai_id,
        'periode_id' => $periode->id,
        'rencana_anggaran' => 50000000.0,
        'realisasi_anggaran' => 0.0,
        'status_laporan' => ReportStatus::Draft,
    ]);

    // Jalankan scan pertama
    Artisan::call('simadu:scan-ews');
    $initialAlertCount = EwsAlert::where('laporan_id', $laporan->id)->where('kode_ews', EwsCode::EWS_1)->count();
    expect($initialAlertCount)->toBe(1);

    // Jalankan scan kedua (harus idempotent / tidak duplikasi)
    Artisan::call('simadu:scan-ews');
    $secondAlertCount = EwsAlert::where('laporan_id', $laporan->id)->where('kode_ews', EwsCode::EWS_1)->count();
    expect($secondAlertCount)->toBe(1);
});
