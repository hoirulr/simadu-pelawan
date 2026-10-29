<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('Seeder akun demo dilewati pada lingkungan produksi demi keamanan.');

            return;
        }

        // 1. Akun Admin Dinas DP2KBP3A
        User::updateOrCreate(
            ['email' => 'admin@simadupelawan.go.id'],
            [
                'name' => 'Admin Dinas DP2KBP3A',
                'password' => Hash::make('0b1H1JDIaTLmI6ts4h8c'),
                'role' => UserRole::Admin,
                'balai_id' => null,
            ]
        );

        // 2. Akun 8 Operator Balai Penyuluh KB
        $balaiUsers = [
            1 => ['name' => 'Operator KB Sungailiat', 'email' => 'operator.sungailiat@simadupelawan.go.id'],
            2 => ['name' => 'Operator KB Merawang', 'email' => 'operator.merawang@simadupelawan.go.id'],
            3 => ['name' => 'Operator KB Pemali', 'email' => 'operator.pemali@simadupelawan.go.id'],
            4 => ['name' => 'Operator KB Mendo Barat', 'email' => 'operator.mendobarat@simadupelawan.go.id'],
            5 => ['name' => 'Operator KB Belinyu', 'email' => 'operator.belinyu@simadupelawan.go.id'],
            6 => ['name' => 'Operator KB Riau Silip', 'email' => 'operator.riausilip@simadupelawan.go.id'],
            7 => ['name' => 'Operator KB Bakam', 'email' => 'operator.bakam@simadupelawan.go.id'],
            8 => ['name' => 'Operator KB Puding Besar', 'email' => 'operator.pudingbesar@simadupelawan.go.id'],
        ];

        foreach ($balaiUsers as $balaiId => $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'name' => $u['name'],
                    'password' => Hash::make('0b1H1JDIaTLmI6ts4h8c'),
                    'role' => UserRole::OperatorBalai,
                    'balai_id' => $balaiId,
                ]
            );
        }
    }
}
