<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $dokumenWajibBaru = [
            'Dokumen Pelaksanaan Anggaran (DPA)',
            'Surat Keputusan (SK)',
            'Kwitansi',
            'Dokumentasi Kegiatan',
            'Rincian Biaya Kegiatan',
            'Kartu Kendali',
        ];

        $jsonString = json_encode($dokumenWajibBaru, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Update default template berkas pada semua periode anggaran
        DB::table('periode_anggaran')->update([
            'dokumen_wajib_json' => $jsonString,
        ]);
    }

    public function down(): void
    {
        // No-op
    }
};
