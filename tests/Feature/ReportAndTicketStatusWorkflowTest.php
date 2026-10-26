<?php

declare(strict_types=1);

use App\Actions\SubmitLaporanAnggaranAction;
use App\Actions\SubmitTindakLanjutAction;
use App\Actions\UploadDokumenLaporanAction;
use App\Actions\VerifyTindakLanjutAction;
use App\Enums\EwsCode;
use App\Enums\EwsStatus;
use App\Enums\ReportStatus;
use App\Models\BalaiPenyuluh;
use App\Models\DokumenLaporan;
use App\Models\EwsAlert;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\TindakLanjutMonitoring;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    $this->seed();

    $this->admin = User::where('role', 'admin')->first();

    $balais = BalaiPenyuluh::take(2)->get();
    $this->balaiA = $balais->first();
    $this->balaiB = $balais->last();

    $this->operatorA = User::where('role', 'operator_balai')->where('balai_id', $this->balaiA->id)->first();
    $this->operatorB = User::where('role', 'operator_balai')->where('balai_id', $this->balaiB->id)->first();

    $this->periode = PeriodeAnggaran::where('is_active', true)->first();

    $this->laporan = LaporanAnggaran::firstOrCreate(
        [
            'balai_id' => $this->balaiA->id,
            'periode_id' => $this->periode->id,
        ],
        [
            'rencana_anggaran' => 50000000,
            'realisasi_anggaran' => 40000000,
            'persentase_serapan' => 80.00,
            'status_laporan' => ReportStatus::Submitted,
        ]
    );
});

test('operator tidak dapat mengedit laporan yang telah terverifikasi tuntas (Verified)', function () {
    $this->laporan->update(['status_laporan' => ReportStatus::Verified]);

    $action = app(SubmitLaporanAnggaranAction::class);

    expect(fn () => $action->execute(
        $this->balaiA->id,
        $this->periode->id,
        60000000,
        50000000,
        'Mencoba ubah data terverifikasi',
        $this->operatorA
    ))->toThrow(AuthorizationException::class);
});

test('operator tidak dapat mengunggah dokumen baru pada laporan yang telah Verified', function () {
    $this->laporan->update(['status_laporan' => ReportStatus::Verified]);

    $dokumen = DokumenLaporan::create([
        'laporan_id' => $this->laporan->id,
        'nama_dokumen_wajib' => 'LRA',
        'status_validasi' => 'lengkap',
    ]);

    $file = UploadedFile::fake()->create('lra_revisi.pdf', 500, 'application/pdf');
    $action = app(UploadDokumenLaporanAction::class);

    expect(fn () => $action->execute($dokumen, $file, $this->operatorA))
        ->toThrow(AuthorizationException::class);
});

test('balai menanggapi tiket pending mengubah status menjadi in progress dan menyimpan bukti', function () {
    $alert = EwsAlert::create([
        'laporan_id' => $this->laporan->id,
        'kode_ews' => EwsCode::EWS_2,
        'level_ews' => 'Sedang',
        'kondisi_pemicu' => 'Deviasi kuitansi dan SPJ',
        'status_ews' => EwsStatus::Open,
    ]);

    $ticket = TindakLanjutMonitoring::create([
        'laporan_id' => $this->laporan->id,
        'ews_id' => $alert->id,
        'instruksi_perbaikan' => 'Lengkapi nota kuitansi belanja bahan sosialisasi',
        'waktu_mulai_temuan' => Carbon::now(),
        'status' => 'Pending',
    ]);

    $fileBukti = UploadedFile::fake()->create('bukti_kuitansi.pdf', 300, 'application/pdf');

    $action = app(SubmitTindakLanjutAction::class);
    $updatedTicket = $action->execute(
        $ticket,
        'Kuitansi telah dilengkapi dan dilampirkan kembali sesuai instruksi.',
        $fileBukti,
        $this->operatorA
    );

    expect($updatedTicket->status)->toBe('In Progress')
        ->and($updatedTicket->catatan_balai)->toContain('Kuitansi telah dilengkapi')
        ->and($updatedTicket->file_bukti_koreksi)->not->toBeNull();

    Storage::disk('local')->assertExists($updatedTicket->file_bukti_koreksi);
});

test('operator balai lain tidak dapat merespons tiket balai lain', function () {
    $alert = EwsAlert::create([
        'laporan_id' => $this->laporan->id,
        'kode_ews' => EwsCode::EWS_2,
        'level_ews' => 'Sedang',
        'kondisi_pemicu' => 'Deviasi kuitansi dan SPJ',
        'status_ews' => EwsStatus::Open,
    ]);

    $ticket = TindakLanjutMonitoring::create([
        'laporan_id' => $this->laporan->id,
        'ews_id' => $alert->id,
        'instruksi_perbaikan' => 'Instruksi untuk Balai A',
        'waktu_mulai_temuan' => Carbon::now(),
        'status' => 'Pending',
    ]);

    $action = app(SubmitTindakLanjutAction::class);

    expect(fn () => $action->execute(
        $ticket,
        'Balai B mencoba menjawab',
        null,
        $this->operatorB
    ))->toThrow(AuthorizationException::class);
});

test('tiket yang telah berstatus Verified tidak dapat diedit kembali oleh operator balai', function () {
    $ticket = TindakLanjutMonitoring::create([
        'laporan_id' => $this->laporan->id,
        'instruksi_perbaikan' => 'Perbaikan sudah tuntas',
        'status' => 'Verified',
        'waktu_mulai_temuan' => Carbon::now()->subDays(2),
        'waktu_selesai_verifikasi' => Carbon::now(),
        'verified_by' => $this->admin->id,
    ]);

    $action = app(SubmitTindakLanjutAction::class);

    expect(fn () => $action->execute(
        $ticket,
        'Mencoba ubah tiket selesai',
        null,
        $this->operatorA
    ))->toThrow(AuthorizationException::class);
});

test('hanya Admin yang dapat memverifikasi tiket dan otomatis meresolve alert EWS', function () {
    $alert = EwsAlert::create([
        'laporan_id' => $this->laporan->id,
        'kode_ews' => EwsCode::EWS_2,
        'level_ews' => 'Sedang',
        'kondisi_pemicu' => 'Deviasi kuitansi dan SPJ',
        'status_ews' => EwsStatus::Open,
    ]);

    $ticket = TindakLanjutMonitoring::create([
        'laporan_id' => $this->laporan->id,
        'ews_id' => $alert->id,
        'instruksi_perbaikan' => 'Lengkapi SPTJM',
        'waktu_mulai_temuan' => Carbon::now(),
        'status' => 'In Progress',
    ]);

    $verifyAction = app(VerifyTindakLanjutAction::class);

    // Operator ditolak untuk verifikasi
    expect(fn () => $verifyAction->execute($ticket, $this->operatorA))
        ->toThrow(AuthorizationException::class);

    // Admin berhasil memverifikasi tiket
    $verifiedTicket = $verifyAction->execute($ticket, $this->admin);

    expect($verifiedTicket->status)->toBe('Verified')
        ->and($verifiedTicket->waktu_selesai_verifikasi)->not->toBeNull()
        ->and($verifiedTicket->verified_by)->toBe($this->admin->id);

    // Alert EWS otomatis berubah menjadi Resolved
    $alert->refresh();
    expect($alert->status_ews)->toBe(EwsStatus::Resolved);
});
