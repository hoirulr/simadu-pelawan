<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DokumenLaporan;
use App\Models\User;

class DokumenLaporanPolicy
{
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
     * Determine whether the user can upload or re-upload the document.
     */
    public function upload(User $user, DokumenLaporan $dokumen): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $laporan = $dokumen->laporanAnggaran;

        return $laporan && $user->isOperatorBalai() && $user->balai_id === $laporan->balai_id;
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
