<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tindak_lanjut_monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporan_anggaran')->cascadeOnDelete();
            $table->foreignId('ews_id')->nullable()->constrained('ews_alerts')->nullOnDelete();
            $table->text('instruksi_perbaikan');
            $table->string('file_bukti_koreksi')->nullable();
            $table->text('catatan_balai')->nullable();
            $table->dateTime('waktu_mulai_temuan');
            $table->dateTime('waktu_selesai_verifikasi')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['Pending', 'In Progress', 'Verified'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tindak_lanjut_monitoring');
    }
};
