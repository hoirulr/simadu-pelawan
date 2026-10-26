<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Models\BalaiPenyuluh;
use App\Models\LaporanAnggaran;
use App\Models\PeriodeAnggaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->admin = User::where('role', 'admin')->first();
    $this->balai = BalaiPenyuluh::first();
    $this->operator = User::where('role', 'operator_balai')->where('balai_id', $this->balai->id)->first();
    $this->periode = PeriodeAnggaran::where('is_active', true)->first();

    LaporanAnggaran::firstOrCreate(
        [
            'balai_id' => $this->balai->id,
            'periode_id' => $this->periode->id,
        ],
        [
            'rencana_anggaran' => 100000000,
            'realisasi_anggaran' => 90000000,
            'persentase_serapan' => 90.00,
            'status_laporan' => ReportStatus::Submitted,
        ]
    );
});

test('tamu tidak dapat mendownload PDF rekapitulasi dan diarahkan ke login', function () {
    $response = $this->get(route('economic.export-pdf', $this->periode->id));
    $response->assertRedirect('/');
});

test('operator balai dilarang mendownload PDF rekapitulasi nilai ekonomis (403 Forbidden)', function () {
    $response = $this->actingAs($this->operator)
        ->get(route('economic.export-pdf', $this->periode->id));

    $response->assertStatus(403);
});

test('admin dinas dapat mendownload berkas PDF resmi dengan content-type application/pdf dan format biner valid', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('economic.export-pdf', $this->periode->id));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader(
        'Content-Disposition',
        'attachment; filename="Rekapitulasi_Nilai_Ekonomis_2026_Tahap I.pdf"'
    );

    $content = $response->getContent();

    // Verifikasi penanda standar biner PDF 1.4 ISO 32000-1
    expect($content)->toStartWith('%PDF-1.4')
        ->and($content)->toContain('%%EOF')
        ->and($content)->toContain('/Catalog')
        ->and($content)->toContain('/Pages')
        ->and($content)->toContain('PEMERINTAH KABUPATEN BANGKA')
        ->and($content)->toContain('DP2KBP3A');
});
