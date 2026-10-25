<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_anggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('balai_id')->constrained('balai_penyuluh')->cascadeOnDelete();
            $table->foreignId('periode_id')->constrained('periode_anggaran')->cascadeOnDelete();
            $table->decimal('rencana_anggaran', 15, 2)->default(0);
            $table->decimal('realisasi_anggaran', 15, 2)->default(0);
            $table->decimal('persentase_serapan', 5, 2)->default(0);
            $table->dateTime('tgl_submit_pertama')->nullable();
            $table->boolean('lengkap_pengiriman_pertama')->default(false);
            $table->enum('status_laporan', ['draft', 'submitted', 'revisi', 'verified'])->default('draft');
            $table->text('catatan_balai')->nullable();
            $table->timestamps();

            $table->unique(['balai_id', 'periode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_anggaran');
    }
};
