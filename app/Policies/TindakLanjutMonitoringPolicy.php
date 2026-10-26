<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TindakLanjutMonitoring;
use App\Models\User;

class TindakLanjutMonitoringPolicy
{
    /**
     * Determine whether the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperatorBalai();
    }

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
     * Determine whether the user can create tickets.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
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

        if (! $laporan || ! $user->isOperatorBalai() || $user->balai_id !== $laporan->balai_id) {
            return false;
        }

        // Tiket yang telah verified tidak dapat diedit kembali oleh operator balai
        return $ticket->status !== 'Verified';
    }

    /**
     * Determine whether the user can delete the ticket.
     */
    public function delete(User $user, TindakLanjutMonitoring $ticket): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can verify the ticket.
     */
    public function verify(User $user, TindakLanjutMonitoring $ticket): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can upload evidence for the ticket.
     */
    public function uploadEvidence(User $user, TindakLanjutMonitoring $ticket): bool
    {
        return $this->update($user, $ticket);
    }

    /**
     * Determine whether the user can download the correction evidence.
     */
    public function downloadEvidence(User $user, TindakLanjutMonitoring $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
