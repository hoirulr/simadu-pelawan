<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EwsCode;
use App\Enums\EwsStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EwsAlert extends Model
{
    use HasFactory;

    protected $table = 'ews_alerts';

    protected $fillable = [
        'laporan_id',
        'kode_ews',
        'level_ews',
        'kondisi_pemicu',
        'penyebab',
        'penanggung_jawab',
        'batas_waktu_koreksi',
        'status_ews',
        'jumlah_pengingat',
    ];

    protected function casts(): array
    {
        return [
            'kode_ews' => EwsCode::class,
            'status_ews' => EwsStatus::class,
            'batas_waktu_koreksi' => 'datetime',
            'jumlah_pengingat' => 'integer',
        ];
    }

    public function laporanAnggaran(): BelongsTo
    {
        return $this->belongsTo(LaporanAnggaran::class, 'laporan_id');
    }

    public function tindakLanjut(): HasMany
    {
        return $this->hasMany(TindakLanjutMonitoring::class, 'ews_id');
    }
}
