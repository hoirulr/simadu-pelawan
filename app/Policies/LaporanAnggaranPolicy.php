<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LaporanAnggaran;
use App\Models\User;

class LaporanAnggaranPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperatorBalai();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LaporanAnggaran $laporan): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOperatorBalai() && $user->balai_id === $laporan->balai_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOperatorBalai();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LaporanAnggaran $laporan): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isOperatorBalai() && $user->balai_id === $laporan->balai_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LaporanAnggaran $laporan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can score the model.
     */
    public function score(User $user, LaporanAnggaran $laporan): bool
    {
        return $user->isAdmin();
    }
}
