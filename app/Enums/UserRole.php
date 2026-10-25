<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case OperatorBalai = 'operator_balai';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin Dinas DP2KBP3A',
            self::OperatorBalai => 'Operator Balai Penyuluh KB',
        };
    }
}
