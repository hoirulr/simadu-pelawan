<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kecamatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function desas(): HasMany
    {
        return $this->hasMany(Desa::class);
    }

    public function balaiPenyuluh(): HasOne
    {
        return $this->hasOne(BalaiPenyuluh::class);
    }
}
