<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update all users with @simadupelawan.go.id to @simelatibangka.go.id
        DB::table('users')
            ->where('email', 'like', '%@simadupelawan.go.id')
            ->get()
            ->each(function ($user) {
                $newEmail = str_replace('@simadupelawan.go.id', '@simelatibangka.go.id', $user->email);
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['email' => $newEmail]);
            });
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'like', '%@simelatibangka.go.id')
            ->get()
            ->each(function ($user) {
                $oldEmail = str_replace('@simelatibangka.go.id', '@simadupelawan.go.id', $user->email);
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['email' => $oldEmail]);
            });
    }
};
