<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_anggaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->enum('tahap', ['Tahap I', 'Tahap II'])->default('Tahap I');
            $table->unsignedTinyInteger('bulan')->default(1);
            $table->decimal('pagu_anggaran', 15, 2)->default(0);
            $table->decimal('target_realisasi_persen', 5, 2)->default(50.00);
            $table->dateTime('tgl_tenggat_lapor');
            $table->json('dokumen_wajib_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_anggaran');
    }
};
