<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ews_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporan_anggaran')->cascadeOnDelete();
            $table->enum('kode_ews', ['EWS_1', 'EWS_2', 'EWS_3']);
            $table->enum('level_ews', ['Rendah', 'Sedang', 'Tinggi'])->default('Sedang');
            $table->text('kondisi_pemicu');
            $table->text('penyebab')->nullable();
            $table->string('penanggung_jawab')->nullable();
            $table->dateTime('batas_waktu_koreksi')->nullable();
            $table->enum('status_ews', ['Open', 'Escalated', 'Resolved'])->default('Open');
            $table->unsignedSmallInteger('jumlah_pengingat')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ews_alerts');
    }
};
