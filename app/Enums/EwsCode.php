<?php

declare(strict_types=1);

namespace App\Enums;

enum EwsCode: string
{
    case EWS_1 = 'EWS_1';
    case EWS_2 = 'EWS_2';
    case EWS_3 = 'EWS_3';

    public function title(): string
    {
        return match ($this) {
            self::EWS_1 => 'Ketepatan Waktu Pelaporan',
            self::EWS_2 => 'Kelengkapan & Validitas Data',
            self::EWS_3 => 'Tindak Lanjut Monitoring',
        };
    }
}
