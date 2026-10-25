<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenLaporan extends Model
{
    use HasFactory;

    protected $table = 'dokumen_laporan';

    protected $fillable = [
        'laporan_id',
        'nama_dokumen_wajib',
        'file_path',
        'file_name',
        'file_size',
        'status_validasi',
        'catatan_dokumen',
    ];

    public function laporanAnggaran(): BelongsTo
    {
        return $this->belongsTo(LaporanAnggaran::class, 'laporan_id');
    }
}
