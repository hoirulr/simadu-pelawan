<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RiskCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkoringRisiko extends Model
{
    use HasFactory;

    protected $table = 'skoring_risiko';

    protected $fillable = [
        'laporan_id',
        'penilai_id',
        'skor_p1',
        'skor_p2',
        'skor_p3',
        'skor_p4',
        'skor_p5',
        'total_skor',
        'kategori_risiko',
        'rekomendasi_sistem',
        'catatan_penilai',
    ];

    protected function casts(): array
    {
        return [
            'skor_p1' => 'integer',
            'skor_p2' => 'integer',
            'skor_p3' => 'integer',
            'skor_p4' => 'integer',
            'skor_p5' => 'integer',
            'total_skor' => 'integer',
            'kategori_risiko' => RiskCategory::class,
        ];
    }

    public function laporanAnggaran(): BelongsTo
    {
        return $this->belongsTo(LaporanAnggaran::class, 'laporan_id');
    }

    public function penilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penilai_id');
    }
}
