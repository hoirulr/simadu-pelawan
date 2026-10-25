<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BalaiPenyuluh extends Model
{
    use HasFactory;

    protected $table = 'balai_penyuluh';

    protected $fillable = [
        'kecamatan_id',
        'kode_balai',
        'nama_balai',
        'kecamatan',
        'kepala_balai',
        'kontak',
        'alamat',
    ];

    public function kecamatanModel(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'balai_id');
    }

    public function laporanAnggaran(): HasMany
    {
        return $this->hasMany(LaporanAnggaran::class, 'balai_id');
    }
}
