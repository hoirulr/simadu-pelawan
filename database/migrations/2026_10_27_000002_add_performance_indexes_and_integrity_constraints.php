<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_anggaran', function (Blueprint $table) {
            $table->index('status_laporan');
            $table->index('tgl_submit_pertama');
        });

        Schema::table('ews_alerts', function (Blueprint $table) {
            $table->index('status_ews');
            $table->index('batas_waktu_koreksi');
            $table->index('kode_ews');
            $table->unique(['laporan_id', 'kode_ews']);
        });

        Schema::table('tindak_lanjut_monitoring', function (Blueprint $table) {
            $table->index('status');
            $table->index('waktu_mulai_temuan');
        });

        Schema::table('dokumen_laporan', function (Blueprint $table) {
            $table->index('status_validasi');
        });

        Schema::table('periode_anggaran', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('periode_anggaran', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('dokumen_laporan', function (Blueprint $table) {
            $table->dropIndex(['status_validasi']);
        });

        Schema::table('tindak_lanjut_monitoring', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['waktu_mulai_temuan']);
        });

        Schema::table('ews_alerts', function (Blueprint $table) {
            $table->dropUnique(['laporan_id', 'kode_ews']);
            $table->dropIndex(['kode_ews']);
            $table->dropIndex(['batas_waktu_koreksi']);
            $table->dropIndex(['status_ews']);
        });

        Schema::table('laporan_anggaran', function (Blueprint $table) {
            $table->dropIndex(['status_laporan']);
            $table->dropIndex(['tgl_submit_pertama']);
        });
    }
};
