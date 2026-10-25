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

    public function namaPeriode(): string
    {
        return "{$this->tahun} - {$this->tahap} (Bulan {$this->bulan})";
    }
}
