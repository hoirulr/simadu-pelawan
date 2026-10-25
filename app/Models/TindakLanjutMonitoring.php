<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TindakLanjutMonitoring extends Model
{
    use HasFactory;

    protected $table = 'tindak_lanjut_monitoring';

    protected $fillable = [
        'laporan_id',
        'ews_id',
        'instruksi_perbaikan',
        'file_bukti_koreksi',
        'catatan_balai',
        'waktu_mulai_temuan',
        'waktu_selesai_verifikasi',
        'verified_by',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'waktu_mulai_temuan' => 'datetime',
            'waktu_selesai_verifikasi' => 'datetime',
        ];
    }

    public function laporanAnggaran(): BelongsTo
    {
        return $this->belongsTo(LaporanAnggaran::class, 'laporan_id');
    }

    public function ewsAlert(): BelongsTo
    {
        return $this->belongsTo(EwsAlert::class, 'ews_id');
    }

    public function verifikatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
