<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TindakLanjutMonitoring;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubmitTindakLanjutAction
{
    /**
     * Eksekusi pengajuan klarifikasi / bukti perbaikan tindak lanjut oleh balai.
     *
     * @throws DomainException
     */
    public function execute(
        TindakLanjutMonitoring $ticket,
        string $catatanBalai,
        ?UploadedFile $fileBukti,
        User $actor
    ): TindakLanjutMonitoring {
        $ticket->loadMissing('laporanAnggaran');
        Gate::forUser($actor)->authorize('update', $ticket);

        // Guard status tiket: Tiket 'Verified' tidak dapat diedit operator
        if ($ticket->status === 'Verified' && ! $actor->isAdmin()) {
            throw new DomainException('Tiket telah berstatus Terverifikasi Tuntas dan tidak dapat diedit kembali.');
        }

        return DB::transaction(function () use ($ticket, $catatanBalai, $fileBukti) {
            $filePath = $ticket->file_bukti_koreksi;

            if ($fileBukti) {
                // Hapus berkas lama jika ada di private storage
                if ($filePath && Storage::disk('local')->exists($filePath)) {
                    Storage::disk('local')->delete($filePath);
                }

                $ext = $fileBukti->getClientOriginalExtension() ?: 'pdf';
                $storedName = Str::uuid()->toString().'.'.$ext;
                $filePath = $fileBukti->storeAs('bukti_koreksi/'.$ticket->id, $storedName, 'local');
            }

            $ticket->update([
                'catatan_balai' => $catatanBalai,
                'file_bukti_koreksi' => $filePath,
                'status' => 'In Progress', // Transisi status: Pending/Escalated -> In Progress
            ]);

            return $ticket->fresh(['laporanAnggaran', 'ewsAlert']);
        });
    }
}
