<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skoring_risiko', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->unique()->constrained('laporan_anggaran')->cascadeOnDelete();
            $table->foreignId('penilai_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('skor_p1')->default(0);
            $table->unsignedTinyInteger('skor_p2')->default(0);
            $table->unsignedTinyInteger('skor_p3')->default(0);
            $table->unsignedTinyInteger('skor_p4')->default(0);
            $table->unsignedTinyInteger('skor_p5')->default(0);
            $table->unsignedTinyInteger('total_skor')->default(0);
            $table->enum('kategori_risiko', ['Rendah', 'Sedang', 'Tinggi'])->default('Rendah');
            $table->text('rekomendasi_sistem')->nullable();
            $table->text('catatan_penilai')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skoring_risiko');
    }
};
