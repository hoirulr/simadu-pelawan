<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // Update all user passwords to Bangka*1901*
        DB::table('users')->update([
            'password' => Hash::make('Bangka*1901*'),
        ]);
    }

    public function down(): void
    {
        // No-op or revert to default password
    }
};
