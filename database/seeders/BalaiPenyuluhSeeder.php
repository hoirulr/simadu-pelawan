<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BalaiPenyuluh;
use Illuminate\Database\Seeder;

class BalaiPenyuluhSeeder extends Seeder
{
    public function run(): void
    {
        $balaiList = [
            [
                'id' => 1,
                'kecamatan_id' => 1,
                'kode_balai' => 'BALAI-SGL',
                'nama_balai' => 'Balai Penyuluh KB Sungailiat',
                'kecamatan' => 'Sungailiat',
                'kepala_balai' => 'Hj. Mardiana, S.Sos',
                'kontak' => '0812-7890-0001',
                'alamat' => 'Jl. Jenderal Sudirman No. 12, Sungailiat',
            ],
            [
                'id' => 2,
                'kecamatan_id' => 2,
                'kode_balai' => 'BALAI-MRW',
                'nama_balai' => 'Balai Penyuluh KB Merawang',
                'kecamatan' => 'Merawang',
                'kepala_balai' => 'Bambang Irawan, SKM',
                'kontak' => '0812-7890-0002',
                'alamat' => 'Jl. Raya Baturusa - Merawang',
            ],
            [
                'id' => 3,
                'kecamatan_id' => 3,
                'kode_balai' => 'BALAI-PML',
                'nama_balai' => 'Balai Penyuluh KB Pemali',
                'kecamatan' => 'Pemali',
                'kepala_balai' => 'Nuraini, S.AP',
                'kontak' => '0812-7890-0003',
                'alamat' => 'Jl. Air Duren, Pemali',
            ],
            [
                'id' => 4,
                'kecamatan_id' => 4,
                'kode_balai' => 'BALAI-MDB',
                'nama_balai' => 'Balai Penyuluh KB Mendo Barat',
                'kecamatan' => 'Mendo Barat',
                'kepala_balai' => 'Zulkifli, S.IP',
                'kontak' => '0812-7890-0004',
                'alamat' => 'Jl. Petaling - Mendo Barat',
            ],
            [
                'id' => 5,
                'kecamatan_id' => 5,
                'kode_balai' => 'BALAI-BLY',
                'nama_balai' => 'Balai Penyuluh KB Belinyu',
                'kecamatan' => 'Belinyu',
                'kepala_balai' => 'Hendri Kusuma, S.Sos',
                'kontak' => '0812-7890-0005',
                'alamat' => 'Jl. Depati Amir No. 45, Belinyu',
            ],
            [
                'id' => 6,
                'kecamatan_id' => 6,
                'kode_balai' => 'BALAI-RSL',
                'nama_balai' => 'Balai Penyuluh KB Riau Silip',
                'kecamatan' => 'Riau Silip',
                'kepala_balai' => 'Siti Rohimah, S.E.',
                'kontak' => '0812-7890-0006',
                'alamat' => 'Jl. Raya Silip No. 08',
            ],
            [
                'id' => 7,
                'kecamatan_id' => 7,
                'kode_balai' => 'BALAI-BKM',
                'nama_balai' => 'Balai Penyuluh KB Bakam',
                'kecamatan' => 'Bakam',
                'kepala_balai' => 'Drs. Supriyanto',
                'kontak' => '0812-7890-0007',
                'alamat' => 'Jl. Raya Pangkalpinang - Muntok KM 42, Bakam',
            ],
            [
                'id' => 8,
                'kecamatan_id' => 8,
                'kode_balai' => 'BALAI-PDB',
                'nama_balai' => 'Balai Penyuluh KB Puding Besar',
                'kecamatan' => 'Puding Besar',
                'kepala_balai' => 'Fitria Handayani, S.KM',
                'kontak' => '0812-7890-0008',
                'alamat' => 'Jl. Raya Puding Besar No. 17',
            ],
        ];

        foreach ($balaiList as $balai) {
            BalaiPenyuluh::updateOrCreate(
                ['id' => $balai['id']],
                $balai
            );
        }
    }
}
