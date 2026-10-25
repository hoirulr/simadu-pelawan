<?php

declare(strict_types=1);

namespace App\Enums;

enum RiskCategory: string
{
    case Rendah = 'Rendah';
    case Sedang = 'Sedang';
    case Tinggi = 'Tinggi';

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Rendah => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30',
            self::Sedang => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30',
            self::Tinggi => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-500/30',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Rendah => 'Rendah (Terkendali)',
            self::Sedang => 'Sedang (Perlu Perhatian)',
            self::Tinggi => 'Tinggi (Kritis / Prioritas)',
        };
    }
}
