<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\DokumenLaporan;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\TindakLanjutMonitoring;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
    Storage::fake('local');
});

test('file upload rejects disallowed file types like php or exe', function (): void {
    $operator = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();
    $periode = PeriodeAnggaran::where('is_active', true)->first();

    $laporan = LaporanAnggaran::create([
        'balai_id' => $operator->balai_id,
        'periode_id' => $periode->id,
        'rencana_anggaran' => 100000000,
        'realisasi_anggaran' => 50000000,
        'persentase_serapan' => 50.0,
        'status_laporan' => ReportStatus::Submitted,
    ]);

    $dokumen = DokumenLaporan::create([
        'laporan_id' => $laporan->id,
        'nama_dokumen_wajib' => 'Surat Pertanggungjawaban Mutlak (SPTJM)',
        'status_validasi' => 'kurang',
    ]);

    $maliciousFile = UploadedFile::fake()->create('exploit.php', 100, 'text/x-php');

    Livewire::actingAs($operator)
        ->test('pages.balai.report-submission')
        ->set("documentFiles.{$dokumen->id}", $maliciousFile)
        ->call('uploadDokumen', $dokumen->id)
        ->assertHasErrors(["documentFiles.{$dokumen->id}" => 'mimes']);
});

test('file upload rejects files exceeding 10MB limit', function (): void {
    $operator = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();
    $periode = PeriodeAnggaran::where('is_active', true)->first();

    $laporan = LaporanAnggaran::create([
        'balai_id' => $operator->balai_id,
        'periode_id' => $periode->id,
        'rencana_anggaran' => 100000000,
        'realisasi_anggaran' => 50000000,
        'persentase_serapan' => 50.0,
        'status_laporan' => ReportStatus::Submitted,
    ]);

    $dokumen = DokumenLaporan::create([
        'laporan_id' => $laporan->id,
        'nama_dokumen_wajib' => 'Laporan Realisasi Anggaran (LRA)',
        'status_validasi' => 'kurang',
    ]);

    $oversizedFile = UploadedFile::fake()->create('large_document.pdf', 12000, 'application/pdf');

    Livewire::actingAs($operator)
        ->test('pages.balai.report-submission')
        ->set("documentFiles.{$dokumen->id}", $oversizedFile)
        ->call('uploadDokumen', $dokumen->id)
        ->assertHasErrors(["documentFiles.{$dokumen->id}" => 'max']);
});

test('valid document is uploaded to private storage and metadata is recorded', function (): void {
    $operator = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();
    $periode = PeriodeAnggaran::where('is_active', true)->first();

    $laporan = LaporanAnggaran::create([
        'balai_id' => $operator->balai_id,
        'periode_id' => $periode->id,
        'rencana_anggaran' => 100000000,
        'realisasi_anggaran' => 50000000,
        'persentase_serapan' => 50.0,
        'status_laporan' => ReportStatus::Submitted,
    ]);

    $dokumen = DokumenLaporan::create([
        'laporan_id' => $laporan->id,
        'nama_dokumen_wajib' => 'Laporan Realisasi Anggaran (LRA)',
        'status_validasi' => 'kurang',
    ]);

    $pdfFile = UploadedFile::fake()->create('lra_sungailiat_2026.pdf', 1024, 'application/pdf');

    Livewire::actingAs($operator)
        ->test('pages.balai.report-submission')
        ->set("documentFiles.{$dokumen->id}", $pdfFile)
        ->call('uploadDokumen', $dokumen->id)
        ->assertHasNoErrors();

    $dokumen->refresh();

    expect($dokumen->status_validasi)->toBe('lengkap')
        ->and($dokumen->file_name)->toBe('lra_sungailiat_2026.pdf')
        ->and($dokumen->file_path)->not->toBeNull()
        ->and(str_starts_with($dokumen->file_path, "dokumen_laporan/{$laporan->id}/"))->toBeTrue();

    Storage::disk('local')->assertExists($dokumen->file_path);
});

test('re-uploading document replaces the old file in private storage', function (): void {
    $operator = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();
    $periode = PeriodeAnggaran::where('is_active', true)->first();

    $laporan = LaporanAnggaran::create([
        'balai_id' => $operator->balai_id,
        'periode_id' => $periode->id,
        'rencana_anggaran' => 100000000,
        'realisasi_anggaran' => 50000000,
        'persentase_serapan' => 50.0,
        'status_laporan' => ReportStatus::Submitted,
    ]);

    $dokumen = DokumenLaporan::create([
        'laporan_id' => $laporan->id,
        'nama_dokumen_wajib' => 'Buku Kas Umum & Kuitansi',
        'status_validasi' => 'kurang',
    ]);

    $firstFile = UploadedFile::fake()->create('bku_v1.pdf', 500, 'application/pdf');
    Livewire::actingAs($operator)
        ->test('pages.balai.report-submission')
        ->set("documentFiles.{$dokumen->id}", $firstFile)
        ->call('uploadDokumen', $dokumen->id);

    $dokumen->refresh();
    $firstPath = $dokumen->file_path;
    Storage::disk('local')->assertExists($firstPath);

    // Upload replacement
    $secondFile = UploadedFile::fake()->create('bku_v2_revisi.pdf', 600, 'application/pdf');
    Livewire::actingAs($operator)
        ->test('pages.balai.report-submission')
        ->set("documentFiles.{$dokumen->id}", $secondFile)
        ->call('uploadDokumen', $dokumen->id);

    $dokumen->refresh();
    $secondPath = $dokumen->file_path;

    expect($secondPath)->not->toBe($firstPath);
    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($secondPath);
});

test('secure document download enforces balai authorization and rejects cross-balai download', function (): void {
    $operatorSungailiat = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();
    $operatorMerawang = User::where('email', 'operator.merawang@simadupelawan.go.id')->first();
    $admin = User::where('role', UserRole::Admin)->first();
    $periode = PeriodeAnggaran::where('is_active', true)->first();

    // Create Sungailiat (balai_id = 1) document
    $laporanSungailiat = LaporanAnggaran::create([
        'balai_id' => 1,
        'periode_id' => $periode->id,
        'rencana_anggaran' => 100000000,
        'realisasi_anggaran' => 50000000,
        'persentase_serapan' => 50.0,
        'status_laporan' => ReportStatus::Submitted,
    ]);

    $storedPath = 'dokumen_laporan/1/secret_audit_report.pdf';
    Storage::disk('local')->put($storedPath, 'Konten Dokumen Sensitif Keuangan Sungailiat');

    $dokumenSungailiat = DokumenLaporan::create([
        'laporan_id' => $laporanSungailiat->id,
        'nama_dokumen_wajib' => 'Laporan Realisasi Anggaran (LRA)',
        'file_path' => $storedPath,
        'file_name' => 'lra_sungailiat.pdf',
        'file_size' => 1024,
        'status_validasi' => 'lengkap',
    ]);

    // 1. Guest is redirected to login
    $guestResponse = $this->get(route('dokumen.download', $dokumenSungailiat->id));
    $guestResponse->assertRedirect('/');

    // 2. Operator Balai Merawang (balai_id = 2) is forbidden (403) from downloading Sungailiat file
    $merawangResponse = $this->actingAs($operatorMerawang)->get(route('dokumen.download', $dokumenSungailiat->id));
    $merawangResponse->assertForbidden();

    // 3. Operator Sungailiat (balai_id = 1) can successfully download their own document
    $sungailiatResponse = $this->actingAs($operatorSungailiat)->get(route('dokumen.download', $dokumenSungailiat->id));
    $sungailiatResponse->assertOk();

    // 4. Admin Dinas can successfully download documents across all balai
    $adminResponse = $this->actingAs($admin)->get(route('dokumen.download', $dokumenSungailiat->id));
    $adminResponse->assertOk();
});

test('ticket correction evidence upload and secure download authorization', function (): void {
    $operatorSungailiat = User::where('email', 'operator.sungailiat@simadupelawan.go.id')->first();
    $operatorMerawang = User::where('email', 'operator.merawang@simadupelawan.go.id')->first();
    $admin = User::where('role', UserRole::Admin)->first();
    $periode = PeriodeAnggaran::where('is_active', true)->first();

    $laporanSungailiat = LaporanAnggaran::create([
        'balai_id' => 1,
        'periode_id' => $periode->id,
        'rencana_anggaran' => 100000000,
        'realisasi_anggaran' => 50000000,
        'persentase_serapan' => 50.0,
        'status_laporan' => ReportStatus::Submitted,
    ]);

    $ticket = TindakLanjutMonitoring::create([
        'laporan_id' => $laporanSungailiat->id,
        'instruksi_perbaikan' => 'Koreksi nota belanja dan kwitansi',
        'waktu_mulai_temuan' => now(),
        'status' => 'Pending',
    ]);

    $evidenceFile = UploadedFile::fake()->create('bukti_revisi_kwitansi.pdf', 300, 'application/pdf');

    // Sungailiat operator submits response with actual file upload
    Livewire::actingAs($operatorSungailiat)
        ->test('pages.monitoring.followup-economic')
        ->set('selectedTicketId', $ticket->id)
        ->set('catatanBalai', 'Bukti kwitansi telah kami sesuaikan dengan format SOP.')
        ->set('fileBuktiUpload', $evidenceFile)
        ->call('submitBalaiResponse')
        ->assertHasNoErrors();

    $ticket->refresh();
    expect($ticket->status)->toBe('In Progress')
        ->and($ticket->file_bukti_koreksi)->not->toBeNull();

    Storage::disk('local')->assertExists($ticket->file_bukti_koreksi);

    // Download evidence authorization test
    // Merawang operator is forbidden
    $this->actingAs($operatorMerawang)
        ->get(route('ticket.download-bukti', $ticket->id))
        ->assertForbidden();

    // Sungailiat operator can download
    $this->actingAs($operatorSungailiat)
        ->get(route('ticket.download-bukti', $ticket->id))
        ->assertOk();

    // Admin can download
    $this->actingAs($admin)
        ->get(route('ticket.download-bukti', $ticket->id))
        ->assertOk();
});
