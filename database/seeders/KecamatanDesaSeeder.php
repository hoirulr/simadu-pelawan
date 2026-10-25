<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class KecamatanDesaSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // 8 Kecamatan Kabupaten Bangka dari lampiran user
        $kecamatans = [
            ['id' => 1, 'name' => 'Sungailiat', 'code' => '19.01.01'],
            ['id' => 2, 'name' => 'Merawang', 'code' => '19.01.02'],
            ['id' => 3, 'name' => 'Pemali', 'code' => '19.01.03'],
            ['id' => 4, 'name' => 'Mendo Barat', 'code' => '19.01.04'],
            ['id' => 5, 'name' => 'Belinyu', 'code' => '19.01.05'],
            ['id' => 6, 'name' => 'Riau Silip', 'code' => '19.01.06'],
            ['id' => 7, 'name' => 'Bakam', 'code' => '19.01.07'],
            ['id' => 8, 'name' => 'Puding Besar', 'code' => '19.01.08'],
        ];

        foreach ($kecamatans as $kecamatan) {
            Kecamatan::updateOrCreate(
                ['id' => $kecamatan['id']],
                [
                    'name' => $kecamatan['name'],
                    'code' => $kecamatan['code'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // 55 Desa/Kelurahan persis sesuai file CSV yang dilampirkan user
        $desas = [
            ['id' => 1, 'kecamatan_id' => 1, 'name' => 'Sungailiat', 'code' => '19.01.01.1001'],
            ['id' => 2, 'kecamatan_id' => 1, 'name' => 'Kudai', 'code' => '19.01.01.2001'],
            ['id' => 3, 'kecamatan_id' => 1, 'name' => 'Kenanga', 'code' => '19.01.01.2002'],
            ['id' => 4, 'kecamatan_id' => 1, 'name' => 'Sinar Baru', 'code' => '19.01.01.2003'],
            ['id' => 5, 'kecamatan_id' => 1, 'name' => 'Sri Menanti', 'code' => '19.01.01.2004'],
            ['id' => 6, 'kecamatan_id' => 1, 'name' => 'Surya Timur', 'code' => '19.01.01.2005'],
            ['id' => 7, 'kecamatan_id' => 1, 'name' => 'Jelitik', 'code' => '19.01.01.2006'],
            ['id' => 8, 'kecamatan_id' => 1, 'name' => 'Matras', 'code' => '19.01.01.2007'],
            ['id' => 9, 'kecamatan_id' => 2, 'name' => 'Merawang', 'code' => '19.01.02.2001'],
            ['id' => 10, 'kecamatan_id' => 2, 'name' => 'Kimak', 'code' => '19.01.02.2002'],
            ['id' => 11, 'kecamatan_id' => 2, 'name' => 'Baturusa', 'code' => '19.01.02.2003'],
            ['id' => 12, 'kecamatan_id' => 2, 'name' => 'Balunijuk', 'code' => '19.01.02.2004'],
            ['id' => 13, 'kecamatan_id' => 2, 'name' => 'Riding Panjang', 'code' => '19.01.02.2005'],
            ['id' => 14, 'kecamatan_id' => 2, 'name' => 'Jada Bahrin', 'code' => '19.01.02.2006'],
            ['id' => 15, 'kecamatan_id' => 2, 'name' => 'Pagarawan', 'code' => '19.01.02.2007'],
            ['id' => 16, 'kecamatan_id' => 2, 'name' => 'Dwi Makmur', 'code' => '19.01.02.2008'],
            ['id' => 17, 'kecamatan_id' => 3, 'name' => 'Pemali', 'code' => '19.01.03.2001'],
            ['id' => 18, 'kecamatan_id' => 3, 'name' => 'Penyamun', 'code' => '19.01.03.2002'],
            ['id' => 19, 'kecamatan_id' => 3, 'name' => 'Air Duren', 'code' => '19.01.03.2003'],
            ['id' => 20, 'kecamatan_id' => 3, 'name' => 'Karya Makmur', 'code' => '19.01.03.2004'],
            ['id' => 21, 'kecamatan_id' => 3, 'name' => 'Sempan', 'code' => '19.01.03.2005'],
            ['id' => 22, 'kecamatan_id' => 3, 'name' => 'Seri Kembang', 'code' => '19.01.03.2006'],
            ['id' => 23, 'kecamatan_id' => 4, 'name' => 'Kota Kapur', 'code' => '19.01.04.2001'],
            ['id' => 24, 'kecamatan_id' => 4, 'name' => 'Kemuja', 'code' => '19.01.04.2002'],
            ['id' => 25, 'kecamatan_id' => 4, 'name' => 'Petaling', 'code' => '19.01.04.2003'],
            ['id' => 26, 'kecamatan_id' => 4, 'name' => 'Air Buluh', 'code' => '19.01.04.2004'],
            ['id' => 27, 'kecamatan_id' => 4, 'name' => 'Air Menduk', 'code' => '19.01.04.2005'],
            ['id' => 28, 'kecamatan_id' => 4, 'name' => 'Paya Benua', 'code' => '19.01.04.2006'],
            ['id' => 29, 'kecamatan_id' => 4, 'name' => 'Cengkong Abang', 'code' => '19.01.04.2007'],
            ['id' => 30, 'kecamatan_id' => 4, 'name' => 'Zed', 'code' => '19.01.04.2008'],
            ['id' => 31, 'kecamatan_id' => 5, 'name' => 'Belinyu', 'code' => '19.01.05.2001'],
            ['id' => 32, 'kecamatan_id' => 5, 'name' => 'Gunung Muda', 'code' => '19.01.05.2002'],
            ['id' => 33, 'kecamatan_id' => 5, 'name' => 'Air Jukung', 'code' => '19.01.05.2003'],
            ['id' => 34, 'kecamatan_id' => 5, 'name' => 'Riding Panjang', 'code' => '19.01.05.2004'],
            ['id' => 35, 'kecamatan_id' => 5, 'name' => 'Bukit Ketok', 'code' => '19.01.05.2005'],
            ['id' => 36, 'kecamatan_id' => 5, 'name' => 'Lumut', 'code' => '19.01.05.2006'],
            ['id' => 37, 'kecamatan_id' => 5, 'name' => 'Kuto Panji', 'code' => '19.01.05.2007'],
            ['id' => 38, 'kecamatan_id' => 6, 'name' => 'Riau', 'code' => '19.01.06.2001'],
            ['id' => 39, 'kecamatan_id' => 6, 'name' => 'Silip', 'code' => '19.01.06.2002'],
            ['id' => 40, 'kecamatan_id' => 6, 'name' => 'Deniang', 'code' => '19.01.06.2003'],
            ['id' => 41, 'kecamatan_id' => 6, 'name' => 'Berbura', 'code' => '19.01.06.2004'],
            ['id' => 42, 'kecamatan_id' => 6, 'name' => 'Pugul', 'code' => '19.01.06.2005'],
            ['id' => 43, 'kecamatan_id' => 6, 'name' => 'Pangkal Niur', 'code' => '19.01.06.2006'],
            ['id' => 44, 'kecamatan_id' => 7, 'name' => 'Bakam', 'code' => '19.01.07.2001'],
            ['id' => 45, 'kecamatan_id' => 7, 'name' => 'Dalil', 'code' => '19.01.07.2002'],
            ['id' => 46, 'kecamatan_id' => 7, 'name' => 'Kapuk', 'code' => '19.01.07.2003'],
            ['id' => 47, 'kecamatan_id' => 7, 'name' => 'Mabat', 'code' => '19.01.07.2004'],
            ['id' => 48, 'kecamatan_id' => 7, 'name' => 'Mangka', 'code' => '19.01.07.2005'],
            ['id' => 49, 'kecamatan_id' => 7, 'name' => 'Neknang', 'code' => '19.01.07.2006'],
            ['id' => 50, 'kecamatan_id' => 8, 'name' => 'Puding Besar', 'code' => '19.01.08.2001'],
            ['id' => 51, 'kecamatan_id' => 8, 'name' => 'Nibung', 'code' => '19.01.08.2002'],
            ['id' => 52, 'kecamatan_id' => 8, 'name' => 'Saing', 'code' => '19.01.08.2003'],
            ['id' => 53, 'kecamatan_id' => 8, 'name' => 'Sempan', 'code' => '19.01.08.2004'],
            ['id' => 54, 'kecamatan_id' => 8, 'name' => 'Labuh Air Pandan', 'code' => '19.01.08.2005'],
            ['id' => 55, 'kecamatan_id' => 8, 'name' => 'Tanah Bawah', 'code' => '19.01.08.2006'],
        ];

        foreach ($desas as $desa) {
            Desa::updateOrCreate(
                ['id' => $desa['id']],
                [
                    'kecamatan_id' => $desa['kecamatan_id'],
                    'name' => $desa['name'],
                    'code' => $desa['code'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
