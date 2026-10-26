<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LaporanAnggaran extends Model
{
    use HasFactory;

    protected $table = 'laporan_anggaran';

    protected $fillable = [
        'balai_id',
        'periode_id',
        'rencana_anggaran',
        'realisasi_anggaran',
        'persentase_serapan',
        'tgl_submit_pertama',
        'lengkap_pengiriman_pertama',
        'status_laporan',
        'catatan_balai',
    ];

    protected function casts(): array
    {
        return [
            'rencana_anggaran' => 'decimal:2',
            'realisasi_anggaran' => 'decimal:2',
            'persentase_serapan' => 'decimal:2',
            'tgl_submit_pertama' => 'datetime',
            'lengkap_pengiriman_pertama' => 'boolean',
            'status_laporan' => ReportStatus::class,
        ];
    }

    public function balaiPenyuluh(): BelongsTo
    {
        return $this->belongsTo(BalaiPenyuluh::class, 'balai_id');
    }

    public function periodeAnggaran(): BelongsTo
    {
        return $this->belongsTo(PeriodeAnggaran::class, 'periode_id');
    }

    public function dokumenLaporan(): HasMany
    {
        return $this->hasMany(DokumenLaporan::class, 'laporan_id');
    }

    public function skoringRisiko(): HasOne
    {
        return $this->hasOne(SkoringRisiko::class, 'laporan_id');
    }

    public function ewsAlerts(): HasMany
    {
        return $this->hasMany(EwsAlert::class, 'laporan_id');
    }

    public function tindakLanjut(): HasMany
    {
        return $this->hasMany(TindakLanjutMonitoring::class, 'laporan_id');
    }

    public function tindakLanjutMonitoring(): HasMany
    {
        return $this->hasMany(TindakLanjutMonitoring::class, 'laporan_id');
    }
}
