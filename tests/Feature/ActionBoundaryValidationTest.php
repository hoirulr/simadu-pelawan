<?php

declare(strict_types=1);

use App\Actions\SubmitLaporanAnggaranAction;
use App\Actions\UploadDokumenLaporanAction;
use App\Enums\UserRole;
use App\Models\DokumenLaporan;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    $this->operator = User::where('role', UserRole::OperatorBalai)->firstOrFail();
    $this->otherBalaiId = User::where('role', UserRole::OperatorBalai)
        ->where('balai_id', '!=', $this->operator->balai_id)
        ->value('balai_id');
    $this->periode = PeriodeAnggaran::where('is_active', true)->firstOrFail();
});

test('direct report action rejects non numeric budget values', function (): void {
    $action = app(SubmitLaporanAnggaranAction::class);

    expect(fn () => $action->execute(
        $this->operator->balai_id,
        $this->periode->id,
        'not-a-number',
        100,
        null,
        $this->operator,
    ))->toThrow(ValidationException::class);
});

test('direct report action rejects realization above planned budget', function (): void {
    $action = app(SubmitLaporanAnggaranAction::class);

    expect(fn () => $action->execute(
        $this->operator->balai_id,
        $this->periode->id,
        100000,
        100001,
        null,
        $this->operator,
    ))->toThrow(ValidationException::class);
});

test('direct report action rejects cross balai submissions', function (): void {
    $action = app(SubmitLaporanAnggaranAction::class);

    expect(fn () => $action->execute(
        $this->otherBalaiId,
        $this->periode->id,
        100000,
        50000,
        null,
        $this->operator,
    ))->toThrow(AuthorizationException::class);

    expect(LaporanAnggaran::query()->where('balai_id', $this->otherBalaiId)->exists())->toBeFalse();
});

test('direct document action rejects disallowed file types', function (): void {
    $laporan = LaporanAnggaran::create([
        'balai_id' => $this->operator->balai_id,
        'periode_id' => $this->periode->id,
        'rencana_anggaran' => 100000,
        'realisasi_anggaran' => 50000,
        'persentase_serapan' => 50,
        'status_laporan' => 'submitted',
    ]);

    $dokumen = DokumenLaporan::create([
        'laporan_id' => $laporan->id,
        'nama_dokumen_wajib' => 'LRA',
        'status_validasi' => 'kurang',
    ]);

    expect(fn () => app(UploadDokumenLaporanAction::class)->execute(
        $dokumen,
        UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload'),
        $this->operator,
    ))->toThrow(ValidationException::class);
});
