<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('operator_balai')->after('password');
            $table->foreignId('balai_id')->nullable()->after('role')->constrained('balai_penyuluh')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['balai_id']);
            $table->dropColumn(['role', 'balai_id']);
        });
    }
};
