<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SkoringRisiko;
use App\Models\User;

class SkoringRisikoPolicy
{
    /**
     * Determine whether the user can view any risk scores.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperatorBalai();
    }

    /**
     * Determine whether the user can view the risk scoring.
     */
    public function view(User $user, SkoringRisiko $skoring): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $laporan = $skoring->laporanAnggaran;

        return $laporan && $user->isOperatorBalai() && $user->balai_id === $laporan->balai_id;
    }

    /**
     * Determine whether the user can create risk scorings.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can assess or update the risk scoring.
     */
    public function update(User $user, SkoringRisiko $skoring): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the risk scoring.
     */
    public function delete(User $user, SkoringRisiko $skoring): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can score or evaluate risk.
     */
    public function score(User $user, SkoringRisiko $skoring): bool
    {
        return $user->isAdmin();
    }
}
