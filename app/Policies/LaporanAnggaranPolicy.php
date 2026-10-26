<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ReportStatus;
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
     * Note: Verified reports cannot be modified by balai operator without admin intervention.
     */
    public function update(User $user, LaporanAnggaran $laporan): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isOperatorBalai() || $user->balai_id !== $laporan->balai_id) {
            return false;
        }

        // Operator tidak boleh mengedit laporan yang sudah terverifikasi tuntas
        return $laporan->status_laporan !== ReportStatus::Verified;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LaporanAnggaran $laporan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can verify the model.
     */
    public function verify(User $user, LaporanAnggaran $laporan): bool
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
