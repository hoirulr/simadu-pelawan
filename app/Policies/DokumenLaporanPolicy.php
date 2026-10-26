<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\DokumenLaporan;
use App\Models\User;

class DokumenLaporanPolicy
{
    /**
     * Determine whether the user can view any documents.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperatorBalai();
    }

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, DokumenLaporan $dokumen): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $laporan = $dokumen->laporanAnggaran;

        return $laporan && $user->isOperatorBalai() && $user->balai_id === $laporan->balai_id;
    }

    /**
     * Determine whether the user can create documents.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isOperatorBalai();
    }

    /**
     * Determine whether the user can update the document.
     */
    public function update(User $user, DokumenLaporan $dokumen): bool
    {
        return $this->upload($user, $dokumen);
    }

    /**
     * Determine whether the user can delete the document.
     */
    public function delete(User $user, DokumenLaporan $dokumen): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can upload or re-upload the document.
     */
    public function upload(User $user, DokumenLaporan $dokumen): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $laporan = $dokumen->laporanAnggaran;

        if (! $laporan || ! $user->isOperatorBalai() || $user->balai_id !== $laporan->balai_id) {
            return false;
        }

        // Operator tidak boleh mengunggah ulang jika laporan sudah Terverifikasi Tuntas
        return $laporan->status_laporan !== ReportStatus::Verified;
    }

    /**
     * Determine whether the user can download the document.
     */
    public function download(User $user, DokumenLaporan $dokumen): bool
    {
        return $this->view($user, $dokumen);
    }

    /**
     * Determine whether the user can verify the document.
     */
    public function verify(User $user, DokumenLaporan $dokumen): bool
    {
        return $user->isAdmin();
    }
}
