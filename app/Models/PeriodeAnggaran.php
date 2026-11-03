<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodeAnggaran extends Model
{
    use HasFactory;

    protected $table = 'periode_anggaran';

    protected $fillable = [
        'tahun',
        'tahap',
        'bulan',
        'pagu_anggaran',
        'target_realisasi_persen',
        'tgl_tenggat_lapor',
        'dokumen_wajib_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'dokumen_wajib_json' => 'array',
            'tgl_tenggat_lapor' => 'datetime',
            'pagu_anggaran' => 'decimal:2',
            'target_realisasi_persen' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function laporanAnggaran(): HasMany
    {
        return $this->hasMany(LaporanAnggaran::class, 'periode_id');
    }

    public const BULAN_NAMES = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function namaBulan(): string
    {
        return self::BULAN_NAMES[(int) $this->bulan] ?? "Bulan {$this->bulan}";
    }

    public function namaPeriode(): string
    {
        $namaBulan = $this->namaBulan();

        // Jika tahap sudah berupa nama bulan atau default 'Bulanan'
        if ($this->tahap === 'Bulanan' || $this->tahap === $namaBulan) {
            return "{$namaBulan} {$this->tahun}";
        }

        return "{$namaBulan} {$this->tahun} ({$this->tahap})";
    }
}
