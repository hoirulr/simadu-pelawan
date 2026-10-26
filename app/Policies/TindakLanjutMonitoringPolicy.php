<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TindakLanjutMonitoring;
use App\Models\User;

class TindakLanjutMonitoringPolicy
{
    /**
     * Determine whether the user can view the ticket.
     */
    public function view(User $user, TindakLanjutMonitoring $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $laporan = $ticket->laporanAnggaran;

        return $laporan && $user->isOperatorBalai() && $user->balai_id === $laporan->balai_id;
    }

    /**
     * Determine whether the user can update or respond to the ticket.
     */
    public function update(User $user, TindakLanjutMonitoring $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $laporan = $ticket->laporanAnggaran;

        return $laporan && $user->isOperatorBalai() && $user->balai_id === $laporan->balai_id;
    }

    /**
     * Determine whether the user can verify the ticket.
     */
    public function verify(User $user, TindakLanjutMonitoring $ticket): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can download the correction evidence.
     */
    public function downloadEvidence(User $user, TindakLanjutMonitoring $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
