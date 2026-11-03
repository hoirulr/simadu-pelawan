<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periode_anggaran', function (Blueprint $table) {
            // Ubah tahap menjadi string agar fleksibel dan tidak kaku enum
            $table->string('tahap', 50)->default('Bulanan')->change();
        });
    }

    public function down(): void
    {
        Schema::table('periode_anggaran', function (Blueprint $table) {
            $table->string('tahap', 50)->change();
        });
    }
};
