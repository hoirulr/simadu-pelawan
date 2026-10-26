<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EwsAlert;
use App\Models\User;

class EwsAlertPolicy
{
    /**
     * Determine whether the user can view the EWS alert.
     */
    public function view(User $user, EwsAlert $alert): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $laporan = $alert->laporanAnggaran;

        return $laporan && $user->isOperatorBalai() && $user->balai_id === $laporan->balai_id;
    }

    /**
     * Determine whether the user can update the EWS alert.
     */
    public function update(User $user, EwsAlert $alert): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the EWS alert.
     */
    public function delete(User $user, EwsAlert $alert): bool
    {
        return $user->isAdmin();
    }
}
