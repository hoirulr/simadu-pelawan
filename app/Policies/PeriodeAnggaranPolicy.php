<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PeriodeAnggaran;
use App\Models\User;

class PeriodeAnggaranPolicy
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
    public function view(User $user, PeriodeAnggaran $periode): bool
    {
        return $user->isAdmin() || $user->isOperatorBalai();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PeriodeAnggaran $periode): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PeriodeAnggaran $periode): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can export official summary reports (PDF).
     */
    public function exportPdf(User $user, PeriodeAnggaran $periode): bool
    {
        return $user->isAdmin();
    }
}
